<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_key', 50)->unique();
            $table->string('role_name', 100);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('roles');
    }
};