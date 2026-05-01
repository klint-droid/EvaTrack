<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('evacuated_members', function (Blueprint $table) {
            $table->id('evacuated_member_id');
            $table->foreignId('evacuation_id')->nullable()->constrained('evacuation_records', 'evacuation_id')->onDelete('cascade');
            $table->string('member_id', 255)->nullable();
            $table->dateTime('verified_at')->nullable();

            $table->foreign('member_id')
                  ->references('member_id')
                  ->on('household_members')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('evacuated_members');
    }
};