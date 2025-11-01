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
        Schema::create('computers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id')->constrained('laboratories')->cascadeOnDelete();
            $table->string('computer_number');
            $table->string('ip_address');
            $table->string('mac_address')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance']);
            $table->boolean('is_lock')->default(false);
            $table->boolean('is_online')->default(true);
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};
