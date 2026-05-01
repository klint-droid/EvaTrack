<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('member_vulnerable_groups', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 255);
            $table->foreignId('vulnerable_group_id')->constrained('vulnerable_groups', 'vulnerable_group_id')->onDelete('cascade');

            $table->foreign('member_id')
                  ->references('member_id')
                  ->on('household_members')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('member_vulnerable_groups');
    }
};