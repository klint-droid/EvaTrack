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
        Schema::create('room_assignments', function (Blueprint $table) {
            $table->string('room_assignment_id', 36)->primary();

            $table->string('evacuation_id', 36);
            $table->string('room_id', 36);
            $table->string('assigned_by', 36);
            $table->string('household_id', 36);

            $table->boolean('is_self_selected')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('evacuation_id')
                ->references('evacuation_id')
                ->on('evacuation_records')
                ->cascadeOnDelete();

            $table->foreign('room_id')
                ->references('room_id')
                ->on('rooms')
                ->cascadeOnDelete();

            $table->foreign('assigned_by')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('household_id')
                ->references('household_id')
                ->on('households')
                ->cascadeOnDelete();

            // Constraints
            $table->unique(['household_id', 'evacuation_id']);
            $table->unique(['room_id', 'household_id']);

            // Indexes
            $table->index('room_id');
            $table->index('evacuation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
    }
};
