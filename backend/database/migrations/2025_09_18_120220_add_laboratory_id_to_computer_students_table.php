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
    Schema::table('computer_students', function (Blueprint $table) {
        $table->foreignId('laboratory_id')->nullable()->after('student_id');
        $table->unique(['student_id', 'laboratory_id']);
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('computer_students', function (Blueprint $table) {
            $table->dropForeign(['laboratory_id']);
            $table->dropColumn('laboratory_id');
            $table->dropUnique(['student_id', 'laboratory_id']);
        });
    }
};
