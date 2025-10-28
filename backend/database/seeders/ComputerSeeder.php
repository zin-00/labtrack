<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Computer;
use Illuminate\Support\Str;

class ComputerSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Computer::create([
                'computer_number' => $i,
                'ip_address' => "192.168.1." . $i,
                'mac_address' => $this->generateMac(),
                'is_lock' => fake()->boolean(30), // 30% chance to be locked
                'is_online' => fake()->boolean(70), // 70% chance to be online
                'status' => fake()->randomElement(['active', 'inactive', 'maintenance']),
            ]);
        }
    }

    private function generateMac(): string
    {
        $mac = [];
        for ($i = 0; $i < 6; $i++) {
            $mac[] = strtoupper(Str::padLeft(dechex(mt_rand(0, 255)), 2, '0'));
        }
        return implode(':', $mac);
    }
}
