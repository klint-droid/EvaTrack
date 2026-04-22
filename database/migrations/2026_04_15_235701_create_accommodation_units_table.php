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
        Schema::connection($this->connection)->create('accommodation_units', function (Blueprint $table) {
            $table->string('unit_id')->primary();

            $table->string('center_id');
            $table->string('name');

            $table->string('type_id');

            $table->integer('max_capacity');
            $table->integer('current_occupancy')->default(0);

            $table->dateTime('created_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->foreign('center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers')
                ->cascadeOnDelete();

            $table->foreign('type_id')
                ->references('type_id')
                ->on('accommodation_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('accommodation_units');
    }
};
