<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('accommodation_units', function (Blueprint $table) {
            $table->id('unit_id');
            $table->string('center_id', 255)->nullable();
            $table->string('name', 100);
            $table->foreignId('type_id')->nullable()->constrained('accommodation_types', 'type_id')->onDelete('set null');
            $table->integer('max_capacity')->nullable();
            $table->integer('current_occupancy')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->foreign('center_id')
                  ->references('evacuation_center_id')
                  ->on('evacuation_centers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('accommodation_units');
    }
};