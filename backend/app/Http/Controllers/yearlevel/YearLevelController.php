<?php

namespace App\Http\Controllers\yearlevel;

use App\Http\Controllers\Controller;
use App\Models\YearLevel;
use Illuminate\Http\Request;

class YearLevelController extends Controller
{
    public function index(Request $request){
        $query = YearLevel::query();

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $yearLevels = $query->orderBy('created_at', 'desc')->paginate(7);
        $yearLevelsNotPaginated = YearLevel::all();

        return response()->json([
            'yearLevels' => $yearLevels,
            'yearLevelsNotPaginated' => $yearLevelsNotPaginated,
            'message' => 'Year levels retrieved successfully'
        ]);
    }

    public function store(Request $request){
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        $yearLevels = YearLevel::create($data);

        return response()->json([
            'message' => 'Year level registered successfully',
            'yearLevels' => $yearLevels
        ], 201);
    }

    public function update(Request $request, $id){

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        $yearLevel = YearLevel::findOrFail($id);

        if (!$yearLevel) {
            return response()->json(['message' => 'Year level not found'], 404);
        }

        $yearLevel->update($data);

        return response()->json([
            'message' => 'Year level updated successfully',
            'yearLevel' => $yearLevel
        ]);
    }

    public function destroy($id){
        $yearLevel = YearLevel::find($id);

        if (!$yearLevel) {
            return response()->json(['message' => 'Year level not found'], 404);
        }

        $yearLevel->delete();

        return response()->json([
            'message' => 'Year level deleted successfully'
        ]);
    }
}
