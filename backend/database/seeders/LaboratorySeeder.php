<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaboratorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('laboratories')->insert([
            [
                'name' => 'Computer Lab 1',
                'code' => '201',
                'description' => 'Main computer lab with 30 PCs',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Computer Lab 2',
                'code' => '202',
                'description' => 'Networking lab for IT students',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Computer Lab 3',
                'code' => '203',
                'description' => 'Backup lab used during peak hours',
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
