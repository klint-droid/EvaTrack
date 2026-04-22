<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected $table = 'device_tokens';
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection('mysql_v2')->create('device_tokens', function (Blueprint $table) {
            $table->id();

            $table->string('household_id', 50);
            $table->string('player_id', 255)->unique();

            $table->timestamps();

            $table->index('household_id');

            $table->foreign('household_id')
                ->references('household_id')
                ->on('households')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_v2')->dropIfExists('device_tokens');
    }
};
