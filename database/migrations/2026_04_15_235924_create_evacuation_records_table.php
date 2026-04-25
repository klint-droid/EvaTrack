<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('evacuation_records', function (Blueprint $table) {
            $table->string('evacuation_id')->primary();

            $table->string('event_id');
            $table->string('household_id');
            $table->string('center_id');

            $table->enum('status', ['pending', 'partial', 'evacuated', 'cancelled']);

            $table->integer('evacuated_count');
            $table->enum('method', ['qr', 'manual']);

            $table->string('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('event_id')
                ->references('event_id')
                ->on('evacuation_events');

            $table->foreign('household_id')
                ->references('household_id')
                ->on('households')
                ->onDelete('cascade');

            $table->foreign('center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers');

            $table->foreign('verified_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('evacuation_records');
    }
};