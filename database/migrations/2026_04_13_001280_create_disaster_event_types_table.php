<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disaster_event_types', function (Blueprint $table) {
            $table->id('event_type_id');
            $table->string('event_id'); 
            $table->foreignId('type_id')
                  ->constrained('disaster_types', 'type_id')
                  ->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes(); 
            
            // Foreign key for string event_id
            $table->foreign('event_id')
                  ->references('event_id')
                  ->on('disaster_events')
                  ->onDelete('cascade');
            
            // Prevent duplicate combinations
            $table->unique(['event_id', 'type_id']);
            
            // Indexes
            $table->index('event_id');
            $table->index('type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disaster_event_types');
    }
};