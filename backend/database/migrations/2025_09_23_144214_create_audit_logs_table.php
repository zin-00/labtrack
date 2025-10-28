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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            $table->string('action'); // e.g. login, logout, create, update, delete
            $table->string('entity_type')->nullable(); // e.g. Laboratory, Computer, User
            $table->json('entity_id')->nullable(); // ID of the entity

            $table->json('old_data')->nullable(); // nullable for login/logout/create
            $table->json('new_data')->nullable(); // nullable for logout/delete

            $table->text('description')->nullable();
            $table->ipAddress('ip_address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
