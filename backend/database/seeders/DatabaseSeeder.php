<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // $this->call(
        //     LaboratorySeeder::class
        // );
        // $this->call(
        //     ProgramSeeder::class
        // );
        // $this->call(StudentSeeder::class);

        $this->call(ComputerSeeder::class);

        $this->call(YearLevelSeeder::class);
        $this->call(SectionSeeder::class);

    }
}
