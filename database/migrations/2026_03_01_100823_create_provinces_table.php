<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('provinces', function (Blueprint $table) {
            $table->id('province_id');
            $table->string('province_name', 100);
            $table->foreignId('region_id')->constrained('regions', 'region_id')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('provinces');
    }
};