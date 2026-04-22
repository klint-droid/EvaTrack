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

            $table->string('notif_id', 50)->primary();

            $table->text('message');

            $table->string('sent_by', 50);

            $table->string('evacuation_event_id', 50)->nullable();
            $table->string('evacuation_center_id', 50)->nullable();

            $table->string('urgency_level_id', 50);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('sent_by');
            $table->index('evacuation_event_id');
            $table->index('evacuation_center_id');
            $table->index('urgency_level_id');

            $table->foreign('sent_by')
                  ->references('user_id')
                  ->on('users');

            $table->foreign('evacuation_event_id')
                  ->references('event_id')
                  ->on('evacuation_events');

            $table->foreign('evacuation_center_id')
                  ->references('evacuation_center_id')
                  ->on('evacuation_centers');

            $table->foreign('urgency_level_id')
                  ->references('urgency_id')
                  ->on('urgency_levels');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('notifications');
    }
};