<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ComputerLog;
use App\Models\Computer;
use App\Models\Student;
use Carbon\Carbon;

class ComputerLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all students and computers with their relationships
        $students = Student::with(['program', 'year_level'])->get();
        $computers = Computer::where('status', 'active')->get();

        if ($students->isEmpty() || $computers->isEmpty()) {
            $this->command->warn('No students or computers found. Please seed students and computers first.');
            return;
        }

        $this->command->info('Generating computer logs for the last 7 days...');

        // Generate logs for the last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            // Determine number of sessions for this day (more on weekdays, less on weekends)
            $isWeekend = $date->isWeekend();
            $minSessions = $isWeekend ? 5 : 15;
            $maxSessions = $isWeekend ? 15 : 35;
            $sessionsCount = rand($minSessions, $maxSessions);

            $this->command->info("Day {$date->format('Y-m-d')} ({$date->format('l')}): Generating {$sessionsCount} sessions");

            for ($j = 0; $j < $sessionsCount; $j++) {
                // Random student and computer
                $student = $students->random();
                $computer = $computers->random();

                // Random session time between 8 AM and 5 PM
                $startHour = rand(8, 16); // 8 AM to 4 PM
                $startMinute = rand(0, 59);

                $startTime = $date->copy()
                    ->setHour($startHour)
                    ->setMinute($startMinute)
                    ->setSecond(rand(0, 59));

                // Session duration: 30 minutes to 4 hours (in seconds)
                $durationSeconds = rand(30 * 60, 4 * 60 * 60);

                $endTime = $startTime->copy()->addSeconds($durationSeconds);

                // Get program code and year level
                $programCode = $student->program->program_code ?? $this->getRandomProgram();
                $yearLevel = $student->year_level->name ?? (string)rand(1, 4);

                // Create computer log entry
                ComputerLog::create([
                    'student_id' => $student->id,
                    'computer_id' => $computer->id,
                    'ip_address' => $computer->ip_address ?? '192.168.1.' . rand(1, 254),
                    'mac_address' => $this->generateMacAddress(),
                    'program' => $programCode,
                    'year_level' => $yearLevel,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'uptime' => $durationSeconds,
                    'created_at' => $startTime,
                    'updated_at' => $endTime,
                ]);
            }
        }

        $totalLogs = ComputerLog::count();
        $this->command->info("✅ Successfully created {$totalLogs} computer log entries!");

        // Display summary
        $this->displaySummary();
    }

    /**
     * Generate a random MAC address
     */
    private function generateMacAddress(): string
    {
        return sprintf(
            '%02X:%02X:%02X:%02X:%02X:%02X',
            rand(0, 255),
            rand(0, 255),
            rand(0, 255),
            rand(0, 255),
            rand(0, 255),
            rand(0, 255)
        );
    }

    /**
     * Get random program
     */
    private function getRandomProgram(): string
    {
        $programs = ['BSIT', 'BSCS', 'ACT'];
        return $programs[array_rand($programs)];
    }

    /**
     * Display summary of generated data
     */
    private function displaySummary(): void
    {
        $this->command->info("\n📊 Weekly Session Summary:");
        $this->command->info("─────────────────────────────────────");

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            $totalSeconds = ComputerLog::whereDate('created_at', $date->toDateString())
                ->sum('uptime');

            $hours = round($totalSeconds / 3600, 1);
            $sessionsCount = ComputerLog::whereDate('created_at', $date->toDateString())->count();

            $dayName = str_pad($date->format('D'), 3);
            $dateStr = str_pad($date->format('M d'), 6);

            $this->command->info(sprintf(
                "%s %s: %3d sessions = %6.1f hours",
                $dayName,
                $dateStr,
                $sessionsCount,
                $hours
            ));
        }

        $totalWeekSeconds = ComputerLog::where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->sum('uptime');
        $totalWeekHours = round($totalWeekSeconds / 3600, 1);

        $this->command->info("─────────────────────────────────────");
        $this->command->info("📈 Total Weekly Hours: {$totalWeekHours}h");
        $this->command->info("📈 Average Per Day: " . round($totalWeekHours / 7, 1) . "h");
    }
}
