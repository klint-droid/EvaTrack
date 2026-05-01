<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('households', function (Blueprint $table) {
            $table->string('household_id', 255)->primary();
            $table->string('household_name', 100);
            $table->foreignId('address_id')->nullable()->constrained('addresses', 'address_id')->onDelete('set null');
            $table->string('contact_number', 50)->nullable();
            $table->string('emergency_contact', 50)->nullable();
            $table->string('created_by', 255);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('households');
    }
};