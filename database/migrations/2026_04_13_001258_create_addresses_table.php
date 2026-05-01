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
            $table->id('address_id');
            $table->text('street_address')->nullable();
            $table->foreignId('purok_id')->nullable()->constrained('puroks', 'purok_id')->onDelete('cascade');
            $table->foreignId('sitio_id')->nullable()->constrained('sitios', 'sitio_id')->onDelete('cascade');
            $table->foreignId('barangay_id')->nullable()->constrained('barangays', 'barangay_id')->onDelete('cascade');
            $table->foreignId('city_id')->nullable()->constrained('cities', 'city_id')->onDelete('cascade');
            $table->foreignId('province_id')->nullable()->constrained('provinces', 'province_id')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions', 'region_id')->onDelete('cascade');
            $table->foreignId('zip_code')->nullable()->constrained('zip_codes', 'zipcode_id')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('addresses');
    }
};