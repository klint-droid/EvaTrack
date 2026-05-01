<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disaster_types', function (Blueprint $table) {
            $table->id('type_id'); 
            $table->string('type_code', 20)->unique();
            $table->string('type_name', 100);
            $table->foreignId('severity_level')
                  ->constrained('severity_levels', 'severity_id')
                  ->onDelete('restrict');
            $table->boolean('is_active')->default(true);
            $table->timestamps(); 
            $table->softDeletes(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disaster_types');
    }
};