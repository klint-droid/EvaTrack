<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('severity_levels', function (Blueprint $table) {
            $table->id('severity_id');
            $table->string('severity_key', 50)->unique();
            $table->string('severity_label', 100);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('severity_levels');
    }
};