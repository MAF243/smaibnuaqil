<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardNotification;
use App\Models\Notification;
use App\Support\Cutover;
use App\Support\V3Sync;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        if (Cutover::ppdbReadFromV3()) {
            $adminAccount = V3Sync::ensureLegacyAdminAccount((int) session('admin_id'));
            if ($adminAccount) {
                $items = Notification::where('account_id', $adminAccount->id)
                    ->orderByDesc('created_at')
                    ->paginate(20);

                return view('admin.notifications.index', ['items' => $items]);
            }
        }

        $items = DashboardNotification::where('recipient_type', 'admin')
            ->where('recipient_id', (int) session('admin_id'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.notifications.index', ['items' => $items]);
    }

    public function read(int $id)
    {
        if (Cutover::ppdbReadFromV3()) {
            $adminAccount = V3Sync::ensureLegacyAdminAccount((int) session('admin_id'));
            if ($adminAccount) {
                $item = Notification::where('account_id', $adminAccount->id)->findOrFail($id);
                $item->is_read = true;
                $item->read_at = now();
                $item->save();
                return back()->with('ok', 'Notifikasi ditandai sudah dibaca.');
            }
        }

        $item = DashboardNotification::where('recipient_type', 'admin')
            ->where('recipient_id', (int) session('admin_id'))
            ->findOrFail($id);

        $item->is_read = true;
        $item->read_at = now();
        $item->save();

        return back()->with('ok', 'Notifikasi ditandai sudah dibaca.');
    }
}
