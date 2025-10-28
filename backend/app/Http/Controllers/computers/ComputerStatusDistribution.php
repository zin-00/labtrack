<?php

namespace App\Http\Controllers\computers;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\Student;
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


        return response()->json([
            'locked_count' => $lockedCount,
            'online_count' => $onlineCount,
            'offline_count' => $offlineCount,
            'maintenance_count' => $maintenanceCount,
            'active_count' => $activeCount,
            'inactive_count' => $inactiveCount,
            'computers' => $computers,
            'latest_logs' => $latestLogs,
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
        } elseif ($period === 'today') {
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
        } else {
            // Default fallback to monthly zeros
            $activeUnits = array_fill(0, 12, 0);
            $inactiveUnits = array_fill(0, 12, 0);
            $maintenanceUnits = array_fill(0, 12, 0);
        }

        $registeredStudents = Student::count();

        return response()->json([
            'activeUnits' => $activeUnits,
            'inactiveUnits' => $inactiveUnits,
            'maintenanceUnits' => $maintenanceUnits,
            'registeredStudents' => $registeredStudents,
        ]);
    }




}
