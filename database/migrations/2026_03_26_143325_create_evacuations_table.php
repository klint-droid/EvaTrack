<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evacuation_records', function (Blueprint $table) {
            $table->string('evacuation_id')->primary();
            $table->string('household_id');
            $table->foreign('household_id')
                ->references('household_id')
                ->on('households')
                ->cascadeOnDelete();
            $table->string('evacuation_center_id')->nullable();
            $table->foreign('evacuation_center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers')
                ->nullOnDelete();
            $table->string('room_assignment_id')
                ->references('room_id')
                ->on('room')
                ->nullOnDelete();
            $table->enum('status', ['evacuated', 'not evacuated'])->default('not evacuated');
            $table->string('verified_by')->nullable();
            $table->foreign('verified_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
            $table->unique('household_id');
            $table->enum('method', ['qr', 'manual'])->default('manual');
            $table->timestamp('verified_at')->nullable();   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evacuations');
    }
};
