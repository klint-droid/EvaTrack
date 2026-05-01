<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('notification_statuses', function (Blueprint $table) {
            $table->id('status_id');
            $table->string('status_key', 50)->unique();
            $table->string('status_label', 100);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('notification_statuses');
    }
};