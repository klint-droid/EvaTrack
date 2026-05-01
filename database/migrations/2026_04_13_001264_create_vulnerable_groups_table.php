<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('vulnerable_groups', function (Blueprint $table) {
            $table->id('vulnerable_group_id');
            $table->string('vulnerable_group_key', 20)->unique();
            $table->string('vulnerable_group_label', 20);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('vulnerable_groups');
    }
};