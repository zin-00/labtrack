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
        Schema::create('request_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('id_number')->unique();
            $table->string('fullname');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'faculty', 'staff', 'student']);
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_accesses');
    }
};
