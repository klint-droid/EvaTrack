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

            $table->string('household_id')->primary();

            $table->string('household_name', 100);
            $table->integer('member_count');

            $table->string('address_id')->nullable();

            $table->string('contact_number', 50)->nullable();

            $table->dateTime('created_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->foreign('address_id')
                ->references('address_id')
                ->on('addresses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('households');
    }
};