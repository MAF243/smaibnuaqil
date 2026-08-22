<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $module = trim((string) $request->query('module', ''));

        $query = AuditLog::query()->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('action', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('target_type', 'like', "%{$q}%");
            });
        }
        if ($module !== '') {
            $query->where('module', $module);
        }

        $items = $query->paginate(20)->withQueryString();
        $modules = AuditLog::query()->select('module')->whereNotNull('module')->distinct()->orderBy('module')->pluck('module');

        return view('admin.audit.index', compact('items', 'q', 'module', 'modules'));
    }
}
