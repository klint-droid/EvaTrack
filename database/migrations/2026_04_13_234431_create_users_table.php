<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('users', function (Blueprint $table) {
            $table->string('user_id', 255)->primary();
            $table->string('name', 100);
            $table->string('username', 100)->nullable();
            $table->string('email', 255)->unique()->nullable();
            $table->string('password', 255);
            $table->foreignId('role_id')->nullable()->constrained('roles', 'role_id')->onDelete('set null');
            $table->string('contact_number', 50)->nullable();
            $table->string('assigned_center_id', 255)->nullable();
            $table->string('household_id', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->foreign('household_id')
                  ->references('household_id')
                  ->on('households')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('users');
    }
};