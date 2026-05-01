<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('center_issue_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_key', 50)->unique();
            $table->string('category_label', 100);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('center_issue_categories');
    }
};