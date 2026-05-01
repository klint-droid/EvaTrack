<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('unit_allocations', function (Blueprint $table) {
            $table->id('allocation_id');
            $table->foreignId('evacuation_id')->nullable()->constrained('evacuation_records', 'evacuation_id')->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('accommodation_units', 'unit_id')->onDelete('cascade');
            $table->string('assigned_by', 255)->nullable();
            $table->boolean('selected_by_resident')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->foreign('assigned_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('unit_allocations');
    }
};