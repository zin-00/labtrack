<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        DB::table('sections')->insert([
            ['name' => 'Section A', 'description' => 'First section of the batch', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Section B', 'description' => 'Second section of the batch', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Section C', 'description' => 'Third section of the batch', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
