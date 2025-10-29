<?php

namespace App\Http\Controllers\audit;

use App\Http\Controllers\Controller;
use App\Models\Activity\AuditLogs;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLogs::query()->with('user');

        // Search filter (action, entity_type, user name, description)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', '%' . $search . '%')
                  ->orWhere('entity_type', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Date range filters
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audit_logs = $query->orderBy('created_at', 'desc')
            ->paginate(7)
            ->appends($request->query());

        return response()->json([
            'audit_logs' => $audit_logs,
            'message' => 'Audit logs retrieved successfully'
        ]);
    }
}
