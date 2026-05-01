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
            $table->string('member_id', 255)->primary();
            $table->string('household_id', 255)->nullable();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->date('birth_date');
            $table->foreignId('gender_id')->nullable()->constrained('genders', 'gender_id')->onDelete('set null');
            $table->foreignId('relationship_id')->nullable()->constrained('relationships', 'relationship_id')->onDelete('set null');
            $table->foreignId('civil_status_id')->nullable()->constrained('civil_statuses', 'status_id')->onDelete('set null');

            $table->foreign('household_id')
                  ->references('household_id')
                  ->on('households')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('household_members');
    }
};