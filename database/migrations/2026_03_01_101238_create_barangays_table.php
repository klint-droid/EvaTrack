<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('barangays', function (Blueprint $table) {
            $table->id('barangay_id');
            $table->string('barangay_name', 100);
            $table->foreignId('city_id')->constrained('cities', 'city_id')->onDelete('cascade');
            $table->foreignId('province_id')->constrained('provinces', 'province_id')->onDelete('cascade');
            $table->foreignId('region_id')->constrained('regions', 'region_id')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('barangays');
    }
};