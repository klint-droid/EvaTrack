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
            $table->enum('channel', ['sms', 'push', 'both'])->default('both')->after('urgency_level_id');
            $table->enum('status', ['pending', 'sent', 'scheduled', 'failed'])->default('pending')->after('channel');
            $table->enum('target_filter', ['all', 'evacuated', 'not_evacuated'])->default('all')->after('status');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('notifications', function (Blueprint $table) {
            $table->dropColumn(['channel', 'status', 'target_filter']);
        });
    }
};