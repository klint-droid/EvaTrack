<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('genders', function (Blueprint $table) {
            $table->id('gender_id');
            $table->string('gender_key', 20)->unique();
            $table->string('gender_label', 20);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('genders');
    }
};