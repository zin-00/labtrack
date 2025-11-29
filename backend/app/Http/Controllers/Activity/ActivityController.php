<?php

namespace App\Http\Controllers\Activity;

use App\Events\MainEvent;
use App\Http\Controllers\Controller;
use App\Models\BrowserActivity;
use App\Models\Computer;
use App\Models\ComputerActivityLog;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index(){
        $activity = ComputerActivityLog::orderBy('created_at', 'desc')
        ->with('computer')
        ->paginate(7)->appends(request()->query());

        return response()->json([
            'activity' => $activity,
            'message' => 'Activity retrieved successfully'
        ]);
    }


public function store(Request $request)
{
    // Add rfid_uid to validation
    $data = $request->validate([
        'ip_address' => 'required|string|max:255',
        'url'        => 'nullable|string',
        'title'      => 'nullable|string',
        'browser'    => 'nullable|string',
        'duration'   => 'nullable|string',
        'rfid_uid'   => 'nullable|string|max:255',
    ]);

    $computer = Computer::where('ip_address', $data['ip_address'])->first();

    if (!$computer) {
        return response()->json([
            'message' => 'Computer not found for IP: ' . $data['ip_address']
        ], 404);
    }

    // Look up student by rfid_uid
    $studentId = null;
    if (!empty($data['rfid_uid'])) {
        Log::info('Looking up student with RFID: ' . $data['rfid_uid']); // ← Add this log

        $student = \App\Models\Student::where('rfid_uid', $data['rfid_uid'])->first();

        if ($student) {
            $studentId = $student->student_id;
            Log::info('Found student_id: ' . $studentId);
        } else {
            Log::warning('No student found with RFID: ' . $data['rfid_uid']);
        }
    } else {
        Log::warning('No rfid_uid provided in request');
    }

    $activity = BrowserActivity::create([
        'ip_address'   => $data['ip_address'],
        'computer_id'  => $computer->id,
        'student_id'   => $studentId,
        'browser_name' => $data['browser'] ?? null,
        'title'        => $data['title'] ?? null,
        'url'          => $data['url'] ?? null,
        'duration'     => $data['duration'] ?? null,
    ]);

    broadcast(new MainEvent('browser_activity', 'created', $activity))->toOthers();
    return response()->json([
        'message' => 'Browser activity logged successfully',
        'activity' => $activity,
        'student_id' => $studentId,
        'rfid_uid' => $data['rfid_uid'] ?? null,
    ], 201);
}
    public function getBrowserActivities(Request $request)
    {
        $query = BrowserActivity::query()->with('computer');

        // Search filter (browser_name, title, url, ip_address)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('browser_name', 'like', '%' . $search . '%')
                  ->orWhere('title', 'like', '%' . $search . '%')
                  ->orWhere('url', 'like', '%' . $search . '%')
                  ->orWhere('ip_address', 'like', '%' . $search . '%');
            });
        }

        // Date range filters
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->orderBy('created_at', 'desc')
            ->paginate(7)
            ->appends($request->query());

        return response()->json([
            'activities' => $activities,
            'message' => 'Browser activities retrieved successfully'
        ]);
    }



}
