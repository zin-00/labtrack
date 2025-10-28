<?php

namespace App\Http\Controllers\audit;

use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $audit_logs = AuditLogs::orderBy('created_at', 'asc')
        ->with('user')
        ->paginate(4)
        ->appends($request->query());
        return response()->json([
            'audit_logs' => $audit_logs,
            'message' => 'Audit logs retrieved successfully'
        ]);
    }
}
