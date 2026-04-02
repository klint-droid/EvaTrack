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
        Schema::create('rooms', function (Blueprint $table) {
            $table->string('room_id')->primary();

            $table->string('evacuation_center_id');
            $table->foreign('evacuation_center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers')
                ->cascadeOnDelete();

            $table->string('room_number', 255);
            $table->integer('max_capacity');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
