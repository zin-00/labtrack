<?php

namespace App\Http\Controllers\students\configuration;

use App\Events\configuration\ConfigEvent;
use App\Http\Controllers\Controller;
use App\Models\ComputerStudent;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function index(Request $request)
    {
        $query = ComputerStudent::with(['computer.laboratory', 'student.program', 'student.year_level', 'student.section']);

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Program filter
        if ($request->has('program') && !empty($request->program)) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('program_id', $request->program);
            });
        }

        // Section filter
        if ($request->has('section') && !empty($request->section)) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('section_id', $request->section);
            });
        }

        // Year level filter
        if ($request->has('year_level') && !empty($request->year_level)) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('year_level_id', $request->year_level);
            });
        }

        // Laboratory filter
        if ($request->has('laboratory') && !empty($request->laboratory)) {
            $query->whereHas('computer', function ($q) use ($request) {
                $q->where('laboratory_id', $request->laboratory);
            });
        }

        // Check if requesting all records (for PDF export)
        if ($request->has('all') && $request->all === 'true') {
            $assignedStudents = $query->orderBy('created_at', 'desc')->get();
        } else {
            $assignedStudents = $query->orderBy('created_at', 'desc')->paginate(10);
        }

        return response()->json([
            'assigned_students' => $assignedStudents,
            'message' => 'Assigned students retrieved successfully',
        ]);
    }

    public function destroy($id)
    {
        $assignment = ComputerStudent::findOrFail($id);
        $oldData = $assignment->toArray();

        $assignment->delete();

        broadcast(new ConfigEvent('deleted', $oldData));

        return response()->json(['message' => 'Assignment deleted successfully']);
    }
}
