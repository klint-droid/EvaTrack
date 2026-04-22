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

            $table->string('notification_id', 50);
            $table->string('household_id', 50);

            $table->enum('channel', ['sms', 'push']);
            $table->enum('status', ['sent', 'failed', 'retrying']);

            $table->timestamp('sent_at')->nullable();
            $table->integer('retry_count')->default(0);

            $table->string('external_message_id', 100)->nullable();

            $table->timestamps();
            $table->index('notification_id');
            $table->index('household_id');
            $table->index('external_message_id');
            $table->index(['notification_id', 'household_id']);

            $table->foreign('notification_id')
                ->references('notif_id')
                ->on('notifications')
                ->cascadeOnDelete();

            $table->foreign('household_id')
                ->references('household_id')
                ->on('households')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('notification_logs');
    }
};