<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_v2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('evacuation_centers', function (Blueprint $table) {
            $table->string('evacuation_center_id', 255)->primary();
            $table->string('current_event_id')->nullable();

            $table->string('name', 100);
            $table->foreignId('address_id')->nullable()->constrained('addresses', 'address_id')->onDelete('set null');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('capacity')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->foreign('current_event_id')
                ->references('event_id')
                ->on('disaster_events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('evacuation_centers');
    }
};