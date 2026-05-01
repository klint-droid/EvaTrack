<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('urgency_levels', function (Blueprint $table) {
            $table->id('urgency_id');
            $table->string('urgency_key', 50)->unique();
            $table->string('urgency_label', 100);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('urgency_levels');
    }
};