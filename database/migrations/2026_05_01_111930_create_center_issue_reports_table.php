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
            $table->string('report_id', 255)->primary();
            $table->string('evacuation_center_id', 255)->nullable();
            $table->string('reported_by', 255)->nullable();
            $table->string('handled_by', 255)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('center_issue_categories', 'category_id')->onDelete('set null');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->foreignId('severity_id')->nullable()->constrained('severity_levels', 'severity_id')->onDelete('set null');
            $table->foreignId('status_id')->nullable()->constrained('center_issue_report_statuses', 'status_id')->onDelete('set null');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Manual foreign keys for string primary keys
            $table->foreign('evacuation_center_id')
                  ->references('evacuation_center_id')
                  ->on('evacuation_centers')
                  ->onDelete('cascade');

            $table->foreign('reported_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('handled_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('center_issue_reports');
    }
};