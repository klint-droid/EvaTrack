<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('household_members', function (Blueprint $table) {

            $table->string('member_id')->primary();

            $table->string('household_id');

            $table->string('name', 100);
            $table->integer('age');

            $table->enum('gender', ['male', 'female', 'other']);

            $table->boolean('is_pwd')->default(false);
            $table->boolean('is_pregnant')->default(false);

            $table->string('relation', 50);

            $table->foreign('household_id')
                ->references('household_id')
                ->on('households')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('household_members');
    }
};