<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('household_id', 255)->nullable();
            $table->string('player_id', 255)->unique();
            $table->integer('battery_level')->nullable();
            $table->integer('signal_strength')->nullable();
            $table->dateTime('logged_at')->nullable();
            $table->timestamps();

            $table->foreign('household_id')
                  ->references('household_id')
                  ->on('households')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('device_tokens');
    }
};