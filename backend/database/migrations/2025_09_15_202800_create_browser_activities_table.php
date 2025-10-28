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
        Schema::create('browser_activities', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->unsignedBigInteger('computer_id');
            $table->string('browser_name')->nullable();
            $table->string('title')->nullable();
            $table->text('url')->nullable();
            $table->string('duration')->nullable();
            $table->timestamps();

            $table->foreign('computer_id')->references('id')->on('computers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('browser_activities');
    }
};
