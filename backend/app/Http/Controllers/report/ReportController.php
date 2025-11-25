<?php

namespace App\Http\Controllers\report;

use App\Events\MainEvent;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with('student');

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('student_id', 'like', "%{$search}%")
                        ->orWhere('rfid_uid', 'like', "%{$search}%");
                  });
            });
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Status filter (if you add status to reports table later)
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'reports' => $reports,
            'message' => 'Reports retrieved successfully'
        ]);
    }

    public function store( Request $request)
    {
        $request->validate([
            'input' => ['required', 'string'],
            'description' => ['required', 'string'],
        ]);

        $student = Student::where('student_id', $request->input)->orWhere('rfid_uid', $request->input)->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $report = Report::create([
            'student_id' => $student->id,
            'fullname' => $student->fullname,
            'description' => $request->description,
            'status' => 'pending',

        ]);

        // Send notification to all admins about new report
        NotificationService::broadcast(
            'report',
            'New Report Submitted',
            "A new report has been submitted by {$student->fullname}: {$request->description}",
            [
                'link' => '/reports',
                'data' => ['report_id' => $report->id, 'student_id' => $student->id]
            ]
        );

        broadcast(new MainEvent('report', 'created', $report));
        return response()->json([
            'message' => 'Report created successfully',
            'report' => $report,
        ]);
    }

    public function resolve(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $report->update('status', 'resolved');

        return response()->json([
            'message' => 'Report resolved successfully',
        ]);
    }


    public function destroy($id)
    {
        $report = Report::findOrFail($id);
        $report->delete();

        return response()->json([
            'message' => 'Report deleted successfully',
        ]);
    }

}
