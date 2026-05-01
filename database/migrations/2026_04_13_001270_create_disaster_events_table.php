<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disaster_events', function (Blueprint $table) {
            $table->string('event_id')->primary();
            $table->string('name', 100);
            $table->foreignId('type_id')
                  ->constrained('disaster_types', 'type_id')
                  ->onDelete('restrict');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type_id');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disaster_events');
    }
};