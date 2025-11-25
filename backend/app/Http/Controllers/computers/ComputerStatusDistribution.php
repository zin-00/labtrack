<?php

namespace App\Http\Controllers\computers;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\Student;
use App\Models\BrowserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComputerStatusDistribution extends Controller
{
    public function index(Request $request)
    {
        $lockedCount = Computer::where('is_lock', true)->count();
        $onlineCount = Computer::where('is_online', true)->count();
        $offlineCount = Computer::where('is_online', false)->count();
        $maintenanceCount = Computer::where('status', 'maintenance')->count();
        $activeCount = Computer::where('status', 'active')->count();
        $inactiveCount = Computer::where('status', 'inactive')->count();

        $computers = Computer::count();
        $latestLogs = ComputerLog::with('student', 'computer.laboratory')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // Get top 3 most visited websites
        $topWebsites = BrowserActivity::select('url', 'title', DB::raw('COUNT(*) as visit_count'))
            ->groupBy('url', 'title')
            ->orderByDesc('visit_count')
            ->take(3)
            ->get();

        // Get weekly session hours (last 7 days)
        $weeklySessionHours = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();

            // Calculate total session hours for this day
            $totalSeconds = ComputerLog::whereDate('created_at', $date)
                ->sum('uptime');

            // Convert seconds to hours (rounded to 1 decimal)
            $hours = $totalSeconds > 0 ? round($totalSeconds / 3600, 1) : 0;
            $weeklySessionHours[] = $hours;
        }

        // Get student status distribution
        $activeStudents = Student::where('status', 'active')->count();
        $inactiveStudents = Student::where('status', 'inactive')->count();
        $restrictedStudents = Student::where('status', 'restricted')->count();
        $totalStudents = Student::count();

        // Get laboratory usage statistics with session counts
        $laboratoryUsage = DB::table('computers')
            ->join('laboratories', 'computers.laboratory_id', '=', 'laboratories.id')
            ->leftJoin('computer_logs', function($join) {
                $join->on('computers.id', '=', 'computer_logs.computer_id')
                     ->whereDate('computer_logs.created_at', '=', now()->toDateString());
            })
            ->select(
                'laboratories.id as lab_id',
                'laboratories.name as lab_name',
                'laboratories.code as lab_code',
                DB::raw('COUNT(DISTINCT computers.id) as total_computers'),
                DB::raw('SUM(CASE WHEN computers.is_online = 1 THEN 1 ELSE 0 END) as online_count'),
                DB::raw('SUM(CASE WHEN computers.is_online = 0 THEN 1 ELSE 0 END) as offline_count'),
                DB::raw('COUNT(DISTINCT computer_logs.id) as session_count')
            )
            ->whereNotNull('computers.laboratory_id')
            ->groupBy('laboratories.id', 'laboratories.name', 'laboratories.code')
            ->orderBy('laboratories.name')
            ->get();

        return response()->json([
            'locked_count' => $lockedCount,
            'online_count' => $onlineCount,
            'offline_count' => $offlineCount,
            'maintenance_count' => $maintenanceCount,
            'active_count' => $activeCount,
            'inactive_count' => $inactiveCount,
            'computers' => $computers,
            'latest_logs' => $latestLogs,
            'top_websites' => $topWebsites,
            'weekly_session_hours' => $weeklySessionHours,
            'laboratory_usage' => $laboratoryUsage,
            'student_stats' => [
                'active' => $activeStudents,
                'inactive' => $inactiveStudents,
                'restricted' => $restrictedStudents,
                'total' => $totalStudents,
            ],
        ]);
    }

   public function getDataDistribution(Request $request)
    {
        $period = $request->query('period', 'month');

        if ($period === 'month') {
            // Return monthly arrays as above
            $getMonthlyCounts = function($status) {
                $counts = Computer::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                    ->where('status', $status)
                    ->groupBy('month')
                    ->pluck('count', 'month')
                    ->toArray();

                $result = [];
                for ($m = 1; $m <= 12; $m++) {
                    $result[$m] = $counts[$m] ?? 0;
                }
                return array_values($result);
            };

            $activeUnits = $getMonthlyCounts('active');
            $inactiveUnits = $getMonthlyCounts('inactive');
            $maintenanceUnits = $getMonthlyCounts('maintenance');

            // Laboratory usage for month
            $laboratoryUsage = DB::table('computers')
                ->join('laboratories', 'computers.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('computer_logs', function($join) {
                    $join->on('computers.id', '=', 'computer_logs.computer_id')
                         ->whereYear('computer_logs.created_at', '=', now()->year);
                })
                ->select(
                    'laboratories.id as lab_id',
                    'laboratories.name as lab_name',
                    'laboratories.code as lab_code',
                    DB::raw('COUNT(DISTINCT computers.id) as total_computers'),
                    DB::raw('SUM(CASE WHEN computers.is_online = 1 THEN 1 ELSE 0 END) as online_count'),
                    DB::raw('SUM(CASE WHEN computers.is_online = 0 THEN 1 ELSE 0 END) as offline_count'),
                    DB::raw('COUNT(DISTINCT computer_logs.id) as session_count')
                )
                ->whereNotNull('computers.laboratory_id')
                ->groupBy('laboratories.id', 'laboratories.name', 'laboratories.code')
                ->orderBy('laboratories.name')
                ->get();

        } elseif ($period === 'week') {
            // Get daily counts for the last 7 days
            $getDailyCounts = function($status) {
                $startDate = now()->subDays(6)->startOfDay();
                $counts = Computer::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->where('status', $status)
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                $result = [];
                for ($i = 0; $i < 7; $i++) {
                    $date = $startDate->copy()->addDays($i)->toDateString();
                    $result[] = $counts[$date] ?? 0;
                }
                return $result;
            };

            $activeUnits = $getDailyCounts('active');
            $inactiveUnits = $getDailyCounts('inactive');
            $maintenanceUnits = $getDailyCounts('maintenance');

            // Laboratory usage for week (last 7 days)
            $laboratoryUsage = DB::table('computers')
                ->join('laboratories', 'computers.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('computer_logs', function($join) {
                    $join->on('computers.id', '=', 'computer_logs.computer_id')
                         ->where('computer_logs.created_at', '>=', now()->subDays(6)->startOfDay());
                })
                ->select(
                    'laboratories.id as lab_id',
                    'laboratories.name as lab_name',
                    'laboratories.code as lab_code',
                    DB::raw('COUNT(DISTINCT computers.id) as total_computers'),
                    DB::raw('SUM(CASE WHEN computers.is_online = 1 THEN 1 ELSE 0 END) as online_count'),
                    DB::raw('SUM(CASE WHEN computers.is_online = 0 THEN 1 ELSE 0 END) as offline_count'),
                    DB::raw('COUNT(DISTINCT computer_logs.id) as session_count')
                )
                ->whereNotNull('computers.laboratory_id')
                ->groupBy('laboratories.id', 'laboratories.name', 'laboratories.code')
                ->orderBy('laboratories.name')
                ->get();

        } elseif ($period === 'day' || $period === 'today') {
            // Get count only for today
            $today = now()->startOfDay();
            $activeUnits = Computer::where('status', 'active')
                ->whereDate('created_at', $today)
                ->count();
            $inactiveUnits = Computer::where('status', 'inactive')
                ->whereDate('created_at', $today)
                ->count();
            $maintenanceUnits = Computer::where('status', 'maintenance')
                ->whereDate('created_at', $today)
                ->count();

            // Convert to array for frontend consistency
            $activeUnits = [$activeUnits];
            $inactiveUnits = [$inactiveUnits];
            $maintenanceUnits = [$maintenanceUnits];

            // Laboratory usage for today
            $laboratoryUsage = DB::table('computers')
                ->join('laboratories', 'computers.laboratory_id', '=', 'laboratories.id')
                ->leftJoin('computer_logs', function($join) {
                    $join->on('computers.id', '=', 'computer_logs.computer_id')
                         ->whereDate('computer_logs.created_at', '=', now()->toDateString());
                })
                ->select(
                    'laboratories.id as lab_id',
                    'laboratories.name as lab_name',
                    'laboratories.code as lab_code',
                    DB::raw('COUNT(DISTINCT computers.id) as total_computers'),
                    DB::raw('SUM(CASE WHEN computers.is_online = 1 THEN 1 ELSE 0 END) as online_count'),
                    DB::raw('SUM(CASE WHEN computers.is_online = 0 THEN 1 ELSE 0 END) as offline_count'),
                    DB::raw('COUNT(DISTINCT computer_logs.id) as session_count')
                )
                ->whereNotNull('computers.laboratory_id')
                ->groupBy('laboratories.id', 'laboratories.name', 'laboratories.code')
                ->orderBy('laboratories.name')
                ->get();
        } else {
            // Default fallback to monthly zeros
            $activeUnits = array_fill(0, 12, 0);
            $inactiveUnits = array_fill(0, 12, 0);
            $maintenanceUnits = array_fill(0, 12, 0);

            // Empty laboratory usage for unknown period
            $laboratoryUsage = [];
        }

        $registeredStudents = Student::count();

        return response()->json([
            'activeUnits' => $activeUnits,
            'inactiveUnits' => $inactiveUnits,
            'maintenanceUnits' => $maintenanceUnits,
            'registeredStudents' => $registeredStudents,
            'laboratory_usage' => $laboratoryUsage,
        ]);
    }




}
