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
            $table->string('request_id', 255)->primary();
            $table->string('evacuation_center_id', 255)->nullable();
            $table->string('requested_by', 255)->nullable();
            $table->string('handled_by', 255)->nullable();
            $table->string('resource_type', 100);
            $table->integer('quantity')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('urgency_id')->nullable()->constrained('urgency_levels', 'urgency_id')->onDelete('set null');
            $table->foreignId('status_id')->nullable()->constrained('resource_request_statuses', 'status_id')->onDelete('set null');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Manual foreign keys for string primary keys
            $table->foreign('evacuation_center_id')
                  ->references('evacuation_center_id')
                  ->on('evacuation_centers')
                  ->onDelete('cascade');

            $table->foreign('requested_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('handled_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('resource_requests');
    }
};