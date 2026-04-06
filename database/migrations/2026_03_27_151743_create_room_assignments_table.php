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

            $table->string('room_assignment_id', 100)->primary();

            $table->string('evacuation_center_id', 100);
            $table->string('room_id', 100);
            $table->string('assigned_by_user_id', 100);
            $table->string('household_id', 100);

            $table->boolean('is_self_selected')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // Foreign keys
            $table->foreign('evacuation_center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers')
                ->cascadeOnDelete();

            $table->foreign('room_id')
                ->references('room_id')
                ->on('rooms')
                ->cascadeOnDelete();

            $table->foreign('assigned_by_user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('household_id')
                ->references('household_id')
                ->on('households')
                ->cascadeOnDelete();

            // Indexes
            $table->index('room_id');
            $table->index('evacuation_center_id');
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
