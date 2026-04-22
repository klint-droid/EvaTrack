<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('addresses', function (Blueprint $table) {

            $table->string('address_id')->primary();

            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('barangay')->nullable();

            $table->string('street')->nullable();
            $table->string('purok')->nullable();
            $table->string('full_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('addresses');
    }
};