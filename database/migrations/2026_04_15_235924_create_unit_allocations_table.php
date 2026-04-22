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
        Schema::connection($this->connection)->create('unit_allocations', function (Blueprint $table) {
            $table->string('allocation_id')->primary();

            $table->string('evacuation_id');
            $table->string('unit_id');

            $table->string('assigned_by')->nullable();
            $table->boolean('selected_by_resident')->default(false);

            $table->dateTime('created_at')->nullable();

            $table->foreign('evacuation_id')
                ->references('evacuation_id')
                ->on('evacuation_records')
                ->cascadeOnDelete();

            $table->foreign('unit_id')
                ->references('unit_id')
                ->on('accommodation_units');

            $table->foreign('assigned_by')
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
        Schema::connection($this->connection)->dropIfExists('unit_allocations');
    }
};
