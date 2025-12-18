<?php

namespace App\Http\Controllers\laboratories;

use App\Http\Controllers\Controller;
use App\Models\Laboratory;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\ComputerActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaboratoryReportController extends Controller
{
    /**
     * Get laboratory usage report with filters
     */
    public function getUsageReport(Request $request)
    {
        $request->validate([
            'laboratory_id' => 'nullable|string',
            'filter_type' => 'required|in:day,month,year',
            'date' => 'nullable|date',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        $filterType = $request->filter_type;
        $laboratoryId = $request->laboratory_id;

        // Build date range based on filter type
        $startDate = null;
        $endDate = null;

        switch ($filterType) {
            case 'day':
                $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
                $startDate = $date->copy()->startOfDay();
                $endDate = $date->copy()->endOfDay();
                break;
            case 'month':
                $year = $request->year ?? Carbon::now()->year;
                $month = $request->month ?? Carbon::now()->month;
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                break;
            case 'year':
                $year = $request->year ?? Carbon::now()->year;
                $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
                $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear();
                break;
        }

        // Get laboratories
        $laboratoriesQuery = Laboratory::with(['computers']);

        if ($laboratoryId && $laboratoryId !== 'all') {
            $laboratoriesQuery->where('id', $laboratoryId);
        }

        $laboratories = $laboratoriesQuery->get();

        // Get computer IDs for selected laboratories
        $computerIds = Computer::when($laboratoryId && $laboratoryId !== 'all', function ($query) use ($laboratoryId) {
            return $query->where('laboratory_id', $laboratoryId);
        })->pluck('id');

        // Get session statistics
        $sessionsQuery = ComputerLog::whereIn('computer_id', $computerIds)
            ->whereBetween('start_time', [$startDate, $endDate]);

        $totalSessions = $sessionsQuery->count();

        $totalUptime = ComputerLog::whereIn('computer_id', $computerIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->sum('uptime');

        $avgSessionDuration = $totalSessions > 0
            ? round($totalUptime / $totalSessions / 60, 2)
            : 0; // in minutes

        $uniqueStudents = ComputerLog::whereIn('computer_id', $computerIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->distinct('student_id')
            ->count('student_id');

        // Get computer status summary
        $computerStatusSummary = Computer::when($laboratoryId && $laboratoryId !== 'all', function ($query) use ($laboratoryId) {
            return $query->where('laboratory_id', $laboratoryId);
        })
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) as online,
            SUM(CASE WHEN is_online = 0 THEN 1 ELSE 0 END) as offline,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = "inactive" THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status = "maintenance" THEN 1 ELSE 0 END) as maintenance
        ')
        ->first();

        // Get activity logs summary
        $activitySummary = ComputerActivityLog::whereIn('computer_id', $computerIds)
            ->whereBetween('logged_at', [$startDate, $endDate])
            ->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->get()
            ->pluck('count', 'activity_type')
            ->toArray();

        // Get daily/monthly usage data for charts
        $usageData = $this->getUsageChartData($computerIds, $startDate, $endDate, $filterType);

        // Get top used computers
        $topComputers = ComputerLog::whereIn('computer_logs.computer_id', $computerIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->join('computers', 'computer_logs.computer_id', '=', 'computers.id')
            ->leftJoin('laboratories', 'computers.laboratory_id', '=', 'laboratories.id')
            ->selectRaw('
                computers.id,
                computers.computer_number,
                laboratories.name as laboratory_name,
                COUNT(*) as session_count,
                SUM(computer_logs.uptime) as total_uptime
            ')
            ->groupBy('computers.id', 'computers.computer_number', 'laboratories.name')
            ->orderByDesc('session_count')
            ->limit(10)
            ->get();

        // Get sessions by program
        $sessionsByProgram = ComputerLog::whereIn('computer_id', $computerIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('program')
            ->selectRaw('program, COUNT(*) as count')
            ->groupBy('program')
            ->orderByDesc('count')
            ->get();

        // Get sessions by year level
        $sessionsByYearLevel = ComputerLog::whereIn('computer_id', $computerIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotNull('year_level')
            ->selectRaw('year_level, COUNT(*) as count')
            ->groupBy('year_level')
            ->orderBy('year_level')
            ->get();

        // Get peak usage hours
        $peakHours = ComputerLog::whereIn('computer_id', $computerIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as count')
            ->groupBy(DB::raw('HOUR(start_time)'))
            ->orderByDesc('count')
            ->get();

        // Laboratory summary
        $laboratorySummary = [];
        foreach ($laboratories as $lab) {
            $labComputerIds = $lab->computers->pluck('id');

            $labSessions = ComputerLog::whereIn('computer_id', $labComputerIds)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->count();

            $labUptime = ComputerLog::whereIn('computer_id', $labComputerIds)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->sum('uptime');

            $laboratorySummary[] = [
                'id' => $lab->id,
                'name' => $lab->name,
                'code' => $lab->code,
                'total_computers' => $lab->computers->count(),
                'online_computers' => $lab->computers->where('is_online', true)->count(),
                'total_sessions' => $labSessions,
                'total_uptime_minutes' => round($labUptime / 60, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'filter' => [
                    'type' => $filterType,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'laboratory_id' => $laboratoryId,
                ],
                'summary' => [
                    'total_sessions' => $totalSessions,
                    'unique_students' => $uniqueStudents,
                    'total_uptime_hours' => round($totalUptime / 3600, 2),
                    'avg_session_duration_minutes' => $avgSessionDuration,
                ],
                'computer_status' => $computerStatusSummary,
                'activity_summary' => $activitySummary,
                'usage_chart_data' => $usageData,
                'top_computers' => $topComputers,
                'sessions_by_program' => $sessionsByProgram,
                'sessions_by_year_level' => $sessionsByYearLevel,
                'peak_hours' => $peakHours,
                'laboratory_summary' => $laboratorySummary,
            ],
        ]);
    }

    /**
     * Get usage chart data based on filter type
     */
    private function getUsageChartData($computerIds, $startDate, $endDate, $filterType)
    {
        switch ($filterType) {
            case 'day':
                // Hourly data for a single day
                return ComputerLog::whereIn('computer_id', $computerIds)
                    ->whereBetween('start_time', [$startDate, $endDate])
                    ->selectRaw('HOUR(start_time) as label, COUNT(*) as sessions')
                    ->groupBy(DB::raw('HOUR(start_time)'))
                    ->orderBy('label')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => sprintf('%02d:00', $item->label),
                            'sessions' => $item->sessions,
                        ];
                    });

            case 'month':
                // Daily data for a month
                return ComputerLog::whereIn('computer_id', $computerIds)
                    ->whereBetween('start_time', [$startDate, $endDate])
                    ->selectRaw('DATE(start_time) as label, COUNT(*) as sessions')
                    ->groupBy(DB::raw('DATE(start_time)'))
                    ->orderBy('label')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => Carbon::parse($item->label)->format('M d'),
                            'sessions' => $item->sessions,
                        ];
                    });

            case 'year':
                // Monthly data for a year
                return ComputerLog::whereIn('computer_id', $computerIds)
                    ->whereBetween('start_time', [$startDate, $endDate])
                    ->selectRaw('MONTH(start_time) as month, YEAR(start_time) as year, COUNT(*) as sessions')
                    ->groupBy(DB::raw('YEAR(start_time)'), DB::raw('MONTH(start_time)'))
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                            'sessions' => $item->sessions,
                        ];
                    });
        }

        return [];
    }

    /**
     * Get all laboratories for dropdown
     */
    public function getLaboratories()
    {
        $laboratories = Laboratory::select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $laboratories,
        ]);
    }
}
