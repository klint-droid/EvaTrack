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

            $table->string('evacuated_member_id')->primary();

            $table->string('evacuation_id');
            $table->string('member_id');

            $table->dateTime('verified_at')->nullable();

            $table->foreign('evacuation_id')
                ->references('evacuation_id')
                ->on('evacuation_records')
                ->cascadeOnDelete();

            $table->foreign('member_id')
                ->references('member_id')
                ->on('household_members')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('evacuated_members');
    }
};