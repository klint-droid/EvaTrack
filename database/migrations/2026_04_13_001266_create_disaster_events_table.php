<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('disaster_events', function (Blueprint $table) {
            $table->string('event_id', 255)->primary();
            $table->string('name', 100);
            $table->string('type', 50)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('disaster_events');
    }
};