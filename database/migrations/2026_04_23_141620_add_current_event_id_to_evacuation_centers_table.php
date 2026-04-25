<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection('mysql_v2')->table('evacuation_centers', function (Blueprint $table) {
            $table->string('current_event_id')->nullable()->after('evacuation_center_id');

            $table->foreign('current_event_id')
                ->references('event_id')
                ->on('evacuation_events')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_v2')->table('evacuation_centers', function (Blueprint $table) {
            $table->dropForeign(['current_event_id']);
            $table->dropColumn('current_event_id');
        });
    }
};
