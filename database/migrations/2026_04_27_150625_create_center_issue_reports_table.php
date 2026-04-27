<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('center_issue_reports', function (Blueprint $table) {
            $table->string('report_id')->primary();

            $table->string('evacuation_center_id');
            $table->string('reported_by');
            $table->string('handled_by')->nullable();

            $table->enum('category', [
                'incident',
                'facility_issue',
                'health_issue',
                'safety_issue',
                'other'
            ]);

            $table->string('title', 150);
            $table->text('description');

            $table->enum('severity', [
                'low',
                'medium',
                'high',
                'critical'
            ])->default('medium');

            $table->enum('status', [
                'open',
                'in_progress',
                'resolved',
                'closed'
            ])->default('open');

            $table->timestamps();

            $table->foreign('evacuation_center_id')
                ->references('evacuation_center_id')
                ->on('evacuation_centers')
                ->cascadeOnDelete();

            $table->foreign('reported_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('handled_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('center_issue_reports');
    }
};