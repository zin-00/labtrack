<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [];

        for ($i = 1; $i <= 10; $i++) {
            $students[] = [
                'student_id' => 10000 + $i, // 10001 to 10010
                'first_name' => fake()->firstName(),
                'middle_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'rfid_uid' => strval(mt_rand(1000000000, 9999999999)), // 10-digit number
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('students')->insert($students);
    }
}
