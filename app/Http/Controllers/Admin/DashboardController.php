<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AuditLog;
use App\Models\DashboardNotification;
use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Support\Cutover;
use App\Support\V3Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $weekStart = Carbon::now()->subDays(6)->startOfDay();

        if (Cutover::ppdbReadFromV3()) {
            $summary = [
                'total' => StudentApplication::count(),
                'submitted_today' => StudentApplication::whereDate('submitted_at', $today)->count(),
                'under_review' => StudentApplication::where('current_status', 'under_review')->count(),
                'interview' => StudentApplication::where('current_status', 'interview')->count(),
                'accepted' => StudentApplication::where('current_status', 'accepted')->count(),
                'rejected' => StudentApplication::where('current_status', 'rejected')->count(),
                'completed' => StudentApplication::where('current_status', 'completed')->count(),
            ];

            $weekly = StudentApplication::selectRaw('DATE(COALESCE(submitted_at, created_at)) as d, COUNT(*) as total')
                ->whereRaw('DATE(COALESCE(submitted_at, created_at)) >= ?', [$weekStart->toDateString()])
                ->groupBy('d')
                ->orderBy('d')
                ->get()
                ->keyBy('d');

            $weeklyChart = collect(range(0, 6))->map(function ($i) use ($weekly) {
                $date = Carbon::now()->subDays(6 - $i)->toDateString();
                return [
                    'label' => Carbon::parse($date)->translatedFormat('d M'),
                    'value' => (int) ($weekly[$date]->total ?? 0),
                ];
            });

            $monthly = StudentApplication::selectRaw('MONTH(COALESCE(submitted_at, created_at)) as m, COUNT(*) as total')
                ->whereRaw('COALESCE(submitted_at, created_at) >= ?', [$monthStart->copy()->subMonths(5)->startOfMonth()->toDateTimeString()])
                ->groupBy('m')
                ->orderBy('m')
                ->get()
                ->keyBy('m');

            $monthlyChart = collect(range(0, 5))->map(function ($i) use ($monthly) {
                $month = Carbon::now()->subMonths(5 - $i);
                return [
                    'label' => $month->translatedFormat('M Y'),
                    'value' => (int) ($monthly[(int) $month->format('n')]->total ?? 0),
                ];
            });

            $recentStudents = StudentApplication::with(['profile', 'guardians', 'documents', 'account'])
                ->orderByDesc(DB::raw('COALESCE(submitted_at, created_at)'))
                ->limit(8)
                ->get();

            $recentActivities = class_exists(AuditLog::class)
                ? AuditLog::orderByDesc('created_at')->limit(10)->get()
                : collect();

            $adminAccount = V3Sync::ensureLegacyAdminAccount((int) session('admin_id'));
            $notifications = $adminAccount
                ? Notification::where('account_id', $adminAccount->id)->orderByDesc('created_at')->limit(8)->get()
                : collect();

            return view('admin.dashboard.index', compact('summary', 'weeklyChart', 'monthlyChart', 'recentStudents', 'recentActivities', 'notifications'));
        }

        $summary = [
            'total' => Student::count(),
            'submitted_today' => Student::whereDate('submitted_at', $today)->count(),
            'under_review' => Student::where('status', 'under_review')->count(),
            'interview' => Student::where('status', 'interview')->count(),
            'accepted' => Student::whereIn('status', ['accepted', 'verified'])->count(),
            'rejected' => Student::where('status', 'rejected')->count(),
            'completed' => Student::where('status', 'completed')->count(),
        ];

        $weekly = Student::selectRaw('DATE(COALESCE(submitted_at, created_at)) as d, COUNT(*) as total')
            ->whereRaw('DATE(COALESCE(submitted_at, created_at)) >= ?', [$weekStart->toDateString()])
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $weeklyChart = collect(range(0, 6))->map(function ($i) use ($weekly) {
            $date = Carbon::now()->subDays(6 - $i)->toDateString();
            return [
                'label' => Carbon::parse($date)->translatedFormat('d M'),
                'value' => (int) ($weekly[$date]->total ?? 0),
            ];
        });

        $monthly = Student::selectRaw('MONTH(COALESCE(submitted_at, created_at)) as m, COUNT(*) as total')
            ->whereRaw('COALESCE(submitted_at, created_at) >= ?', [$monthStart->copy()->subMonths(5)->startOfMonth()->toDateTimeString()])
            ->groupBy('m')
            ->orderBy('m')
            ->get()
            ->keyBy('m');

        $monthlyChart = collect(range(0, 5))->map(function ($i) use ($monthly) {
            $month = Carbon::now()->subMonths(5 - $i);
            return [
                'label' => $month->translatedFormat('M Y'),
                'value' => (int) ($monthly[(int) $month->format('n')]->total ?? 0),
            ];
        });

        $recentStudents = Student::orderByDesc(DB::raw('COALESCE(submitted_at, created_at)'))->limit(8)->get();
        $recentActivities = class_exists(AdminActivityLog::class)
            ? AdminActivityLog::orderByDesc('created_at')->limit(10)->get()
            : collect();
        $notifications = DashboardNotification::where('recipient_type', 'admin')
            ->where('recipient_id', (int) session('admin_id'))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard.index', compact('summary', 'weeklyChart', 'monthlyChart', 'recentStudents', 'recentActivities', 'notifications'));
    }
}
