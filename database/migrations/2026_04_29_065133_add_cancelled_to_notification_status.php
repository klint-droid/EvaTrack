<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::connection('mysql_v2')->statement(
            "ALTER TABLE notifications MODIFY COLUMN status 
            ENUM('pending','sent','scheduled','failed','cancelled') 
            DEFAULT 'pending'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_status', function (Blueprint $table) {
            //
        });
    }
};
