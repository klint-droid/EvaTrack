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
        Schema::connection($this->connection)->create('accommodation_types', function (Blueprint $table) {
            $table->string('type_id')->primary();

            $table->string('type_key')->unique();
            $table->string('type_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('accommodation_types');
    }
};
