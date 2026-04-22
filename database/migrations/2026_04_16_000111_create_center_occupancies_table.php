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
        Schema::connection($this->connection)->create('center_occupancies', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('evacuation_center_id');

            $table->integer('current_occupancy');
            $table->dateTime('last_updated');

            $table->foreign('evacuation_center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('center_occupancies');
    }
};
