<?php

namespace App\Http\Controllers\sections;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $query = Section::query();

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sections = $query->orderBy('created_at', 'desc')->paginate(7);
        $secNotPaginated = Section::all();

        return response()->json([
            'sections' => $sections,
            'secNotPaginated' => $secNotPaginated,
            'message' => 'Sections retrieved successfully'
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $section = Section::create($data);

        return response()->json([
            'message' => 'Section registered successfully',
            'section' => $section
        ], 201);
    }

    public function show($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        return response()->json($section);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $section = Section::findOrFail($id);

        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->update($data);

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section
        ]);
    }

    public function destroy($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully'
        ]);
    }
}
