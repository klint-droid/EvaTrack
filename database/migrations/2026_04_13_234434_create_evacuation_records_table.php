<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('evacuation_records', function (Blueprint $table) {
            $table->id('evacuation_id');
            $table->string('event_id', 255)->nullable();
            $table->string('household_id', 255)->nullable();
            $table->string('center_id', 255)->nullable();
            $table->foreignId('status_id')->nullable()->constrained('household_statuses', 'status_id')->onDelete('set null');
            $table->integer('evacuated_count')->nullable();
            $table->enum('method', ['qr', 'manual'])->default('manual');
            $table->string('verified_by', 255)->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys for string primary keys
            $table->foreign('event_id')
                  ->references('event_id')
                  ->on('disaster_events')
                  ->onDelete('set null');

            $table->foreign('household_id')
                  ->references('household_id')
                  ->on('households')
                  ->onDelete('cascade');

            $table->foreign('center_id')
                  ->references('evacuation_center_id')
                  ->on('evacuation_centers')
                  ->onDelete('set null');

            $table->foreign('verified_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('evacuation_records');
    }
};