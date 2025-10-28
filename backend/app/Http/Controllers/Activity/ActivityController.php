<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\BrowserActivity;
use App\Models\Computer;
use App\Models\ComputerActivityLog;
use Illuminate\Http\Request;

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
        $data = $request->validate([
            'ip_address' => 'required|string|max:255',
            'url'        => 'nullable|string',
            'title'      => 'nullable|string',
            'browser'    => 'nullable|string',
            'duration'   => 'nullable|string',
        ]);

        $computer = Computer::where('ip_address', $data['ip_address'])->first();

        if (!$computer) {
            return response()->json([
                'message' => 'Computer not found for IP: ' . $data['ip_address']
            ], 404);
        }

        $activity = BrowserActivity::create([
            'ip_address'   => $data['ip_address'],
            'computer_id'  => $computer->id,
            'browser_name' => $data['browser'] ?? null,
            'title'        => $data['title'] ?? null,
            'url'          => $data['url'] ?? null,
            'duration'     => $data['duration'] ?? null,
        ]);

        return response()->json([
            'message' => 'Browser activity logged successfully',
            'activity' => $activity
        ], 201);
    }

    public function getBrowserActivities(Request $request)
    {
        $activities = BrowserActivity::orderBy('created_at', 'desc')
            ->with('computer')
            ->paginate(7)
            ->appends($request->query());

        return response()->json([
            'activities' => $activities,
            'message' => 'Browser activities retrieved successfully'
        ]);
    }



}
