<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('notification_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('notification_id')->nullable()->constrained('notifications', 'notif_id')->onDelete('cascade');
            $table->string('household_id', 255)->nullable();
            $table->foreignId('channel_id')->nullable()->constrained('notification_channels', 'channel_id')->onDelete('set null');
            $table->foreignId('status_id')->nullable()->constrained('notification_statuses', 'status_id')->onDelete('set null');
            $table->dateTime('sent_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('external_message_id', 255)->nullable();

            $table->foreign('household_id')
                  ->references('household_id')
                  ->on('households')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('notification_logs');
    }
};