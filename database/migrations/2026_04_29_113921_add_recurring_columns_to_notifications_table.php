<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->table('notifications', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('scheduled_at');
            $table->enum('recurrence_type', ['hourly', 'daily', 'weekly'])->nullable()->after('is_recurring');
            $table->dateTime('recurrence_end_at')->nullable()->after('recurrence_type');
            $table->dateTime('last_sent_at')->nullable()->after('recurrence_end_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('notifications', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'recurrence_type', 'recurrence_end_at', 'last_sent_at']);
        });
    }
};