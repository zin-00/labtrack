<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('computer_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('computer_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // offline, online, locked, unlocked, etc.
            $table->string('reason')->nullable(); // missed_heartbeats, manual, shutdown, etc.
            $table->text('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index('computer_id');
            $table->index('activity_type');
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('computer_activity_logs');
    }
};
