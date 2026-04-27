<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('resource_requests', function (Blueprint $table) {
            $table->string('request_id')->primary();

            $table->string('evacuation_center_id');
            $table->string('requested_by');
            $table->string('handled_by')->nullable();

            $table->enum('request_type', [
                'resource',
                'personnel'
            ])->default('resource');

            $table->string('resource_type', 100);
            $table->integer('quantity')->default(1);
            $table->text('description')->nullable();

            $table->string('urgency_id');

            $table->enum('status', [
                'pending',
                'acknowledged',
                'approved',
                'rejected',
                'delivered'
            ])->default('pending');

            $table->string('target_agency', 100)->default('ResQperation');

            $table->timestamps();

            $table->foreign('evacuation_center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers')
                ->cascadeOnDelete();

            $table->foreign('requested_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('handled_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('urgency_id')
                ->references('urgency_id')
                ->on('urgency_levels')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('resource_requests');
    }
};