<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('recurrence_types', function (Blueprint $table) {
            $table->id('type_id');
            $table->string('type_key', 50)->unique();
            $table->string('type_label', 100);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('recurrence_types');
    }
};