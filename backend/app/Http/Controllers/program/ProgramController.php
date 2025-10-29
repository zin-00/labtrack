<?php

namespace App\Http\Controllers\program;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = Program::query();

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('program_name', 'like', "%{$search}%")
                  ->orWhere('program_code', 'like', "%{$search}%")
                  ->orWhere('program_description', 'like', "%{$search}%");
            });
        }

        $paginatedProgram = $query->orderBy('created_at', 'desc')->paginate(5);
        $allPrograms = Program::all();

        return response()->json([
            'programs' => $allPrograms,          // non-paginated
            'paginatedPrograms' => $paginatedProgram, // paginated
            'message' => 'Programs retrieved successfully',
        ]);
    }


    public function store(Request $request){
        $data = $request->validate([
            'program_name' => 'required|string|max:255',
            'program_code' => 'required|string|max:255',
            'program_description' => 'required|string|max:255',
        ]);

        $program = Program::create($data);

        return response()->json([
            'message' => 'Program registered successfully',
            'program' => $program
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $data = Program::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|boolean',
        ]);

        $data->fill($validated);

        if ($data->isDirty()) { // only save if something actually changed
            $data->save();
        }

        return response()->json([
            'message' => 'Program updated successfully',
            'program' => $data
        ]);
    }

    public function destroy($id){
        $data = Program::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Program deleted successfully'
        ]);
    }
}
