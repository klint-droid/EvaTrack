<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('evacuation_centers', function (Blueprint $table) {

            $table->string('evacuation_center_id')->primary();

            $table->string('name', 100);

            $table->string('address_id');

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->integer('capacity');

            $table->dateTime('created_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->foreign('address_id')
                ->references('address_id')
                ->on('addresses')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('evacuation_centers');
    }
};