<?php

namespace App\Http\Controllers\laboratories;

use App\Events\Audit\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use App\Models\Laboratory;
use Illuminate\Http\Request;

class LabController extends Controller
{
    public function index(Request $request){
        $laboratories = Laboratory::all();
        return response()->json([
            'laboratories' => $laboratories,
            'message' => 'Laboratories retrieved successfully'
        ]);
    }
    public function store(Request $request){
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:laboratories,code'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $laboratory = Laboratory::create($data);

        $audit_logs = AuditLogs::create([
            'user_id'    => $request->user()->id,
            'action'     => 'create',
            'entity_type'=> 'Laboratory',
            'entity_id'  => $laboratory->id,
            'ip_address' => $request->ip(),
            'description'=> 'Added new laboratory',
        ]);

        AuditEvent::dispatch($audit_logs);
        return response()->json([
            'message' => 'Laboratory created successfully',
            'laboratory' => $laboratory
        ], 201);
    }
    public function update(Request $request, $id){
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:laboratories,code,' . $id],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $laboratory = Laboratory::findOrFail($id);
        $oldData = $laboratory->toArray();

        if (!$laboratory) {
            return response()->json(['message' => 'Laboratory not found'], 404);
        }

        $laboratory->update($data);
        $newData = $laboratory->toArray();

        $changes = [];

        foreach($newData as $key => $value){
            if(array_key_exists($key, $oldData) && $oldData[$key] != $value){
                $changes[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value
                ];
            }
        }

        if(!empty($changes)){
            $audit_logs = AuditLogs::create([
                'user_id'     => $request->user()->id,
                'action'      => 'update',
                'entity_type' => 'Laboratory',
                'entity_id'   => $laboratory->id,
                'ip_address'  => $request->ip(),
                'old_data'    => array_column($changes, 'old', 'field'),
                'new_data'    => array_column($changes, 'new', 'field'),
                'description' => 'Updated laboratory record with specific field changes',
            ]);

            AuditEvent::dispatch($audit_logs);
        }

        return response()->json([
            'message' => 'Laboratory updated successfully',
            'laboratory' => $laboratory
        ]);
    }
    public function destroy(Request $request, $id){
        $laboratory = Laboratory::findOrFail($id);
        if (!$laboratory) {
            return response()->json(['message' => 'Laboratory not found'], 404);
        }

        $laboratory->delete();

        return response()->json([
            'message' => 'Laboratory deleted successfully'
        ]);
    }
}
