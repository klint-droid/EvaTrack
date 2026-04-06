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
        Schema::table('users', function (Blueprint $table) {

            // Prevent duplicate column error
            if (!Schema::hasColumn('users', 'assigned_evacuation_center_id')) {

                $table->string('assigned_evacuation_center_id', 100)
                      ->nullable()
                      ->after('user_id');

                $table->foreign('assigned_evacuation_center_id')
                    ->references('evacuation_center_id')
                    ->on('evacuation_centers')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Drop foreign key first
            $table->dropForeign(['assigned_evacuation_center_id']);

            // Then drop column
            $table->dropColumn('assigned_evacuation_center_id');
        });
    }
};
