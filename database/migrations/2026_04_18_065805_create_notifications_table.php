<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('notifications', function (Blueprint $table) {
            $table->id('notif_id');
            $table->text('message')->nullable();
            $table->string('sent_by', 255)->nullable();
            $table->string('evacuation_event_id', 255)->nullable();
            $table->string('evacuation_center_id', 255)->nullable();
            $table->foreignId('urgency_level_id')->nullable()->constrained('urgency_levels', 'urgency_id')->onDelete('set null');
            $table->dateTime('scheduled_at')->nullable();

            $table->boolean('is_recurring')->default(false);
            $table->foreignId('recurrence_type_id')->nullable()->constrained('recurrence_types', 'type_id')->onDelete('set null');
            $table->dateTime('recurrence_end_at')->nullable();
            $table->dateTime('last_sent_at')->nullable();

            $table->string('channel', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('target_filter', 50)->nullable();

            $table->timestamp('created_at')->nullable();
            // Manual foreign keys for string primary keys
            $table->foreign('sent_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('evacuation_event_id')
                  ->references('event_id')
                  ->on('disaster_events')
                  ->onDelete('set null');

            $table->foreign('evacuation_center_id')
                  ->references('evacuation_center_id')
                  ->on('evacuation_centers')
                  ->onDelete('set null');
            
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('notifications');
    }
};