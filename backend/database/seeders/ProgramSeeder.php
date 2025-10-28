<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;

class ProgramSeeder extends Seeder
{
    public function run()
    {
        $programs = [
            [
                'program_name' => 'Bachelor of Science in Information Technology',
                'program_code' => 'BSIT',
                'program_description' => 'Focuses on software development, network administration, and IT systems.',
            ],
            [
                'program_name' => 'Bachelor of Science in Criminology',
                'program_code' => 'BSCRIM',
                'program_description' => 'Prepares students for careers in law enforcement and public safety.',
            ],
            [
                'program_name' => 'Bachelor of Science in Accountancy',
                'program_code' => 'BSA',
                'program_description' => 'Covers financial accounting, auditing, and taxation.',
            ],
            [
                'program_name' => 'Bachelor of Science in Accounting Information System',
                'program_code' => 'BSAIS',
                'program_description' => 'Combines accounting principles with information systems management.',
            ],
            [
                'program_name' => 'Bachelor of Science in Business Administration Major in Financial Management',
                'program_code' => 'BSBA-FM',
                'program_description' => 'Focuses on corporate finance, investments, and banking.',
            ],
            [
                'program_name' => 'Bachelor of Science in Business Administration Major in Marketing Management',
                'program_code' => 'BSBA-MM',
                'program_description' => 'Covers sales strategies, market research, and brand management.',
            ],
        ];

        foreach ($programs as $program) {
            Program::create($program);
        }
    }
}
