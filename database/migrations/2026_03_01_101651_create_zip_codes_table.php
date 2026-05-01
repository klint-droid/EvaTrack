<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('zip_codes', function (Blueprint $table) {
            $table->id('zipcode_id');
            $table->string('zipcode_name', 100);
            $table->foreignId('purok_id')->nullable()->constrained('puroks', 'purok_id')->onDelete('cascade');
            $table->foreignId('sitio_id')->nullable()->constrained('sitios', 'sitio_id')->onDelete('cascade');
            $table->foreignId('barangay_id')->nullable()->constrained('barangays', 'barangay_id')->onDelete('cascade');
            $table->foreignId('city_id')->constrained('cities', 'city_id')->onDelete('cascade');
            $table->foreignId('province_id')->constrained('provinces', 'province_id')->onDelete('cascade');
            $table->foreignId('region_id')->constrained('regions', 'region_id')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('zip_codes');
    }
};