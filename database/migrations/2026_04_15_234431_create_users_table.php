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
            $table->string('user_id')->primary();
            $table->string('name');
            $table->string('password');

            $table->unsignedBigInteger('role_id');

            $table->string('contact_number')->unique();

            $table->string('assigned_center_id')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('role_id')
                ->references('role_id')
                ->on('roles')
                ->cascadeOnDelete();

            $table->foreign('assigned_center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('users');
    }
};