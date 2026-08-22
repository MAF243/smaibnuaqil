<?php

namespace App\Http\Controllers;

use App\Models\DashboardNotification;
use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Support\Cutover;
use App\Support\V3Sync;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    private function v3StudentForUser(int $legacyUserId): ?StudentApplication
    {
        $account = V3Sync::ensureLegacyUserAccount($legacyUserId);
        if (!$account) {
            return null;
        }

        return StudentApplication::with(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account'])
            ->where('account_id', $account->id)
            ->orderByDesc('id')
            ->first();
    }

    private function legacyStudentForUser(int $legacyUserId): ?Student
    {
        return Student::with(['statusHistories', 'interview'])->where('user_id', $legacyUserId)->orderByDesc('id')->first();
    }

    public function index(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');
        $student = null;
        $notifications = collect();

        if (Cutover::ppdbReadFromV3()) {
            $student = $this->v3StudentForUser($userId);
            if (!$student) {
                $legacy = $this->legacyStudentForUser($userId);
                if ($legacy) {
                    V3Sync::syncStudent($legacy);
                    $student = $this->v3StudentForUser($userId);
                }
            }

            if ($student?->account_id) {
                $notifications = Notification::query()
                    ->where('account_id', $student->account_id)
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
            }
        }

        if (!$student) {
            $student = $this->legacyStudentForUser($userId);
            $notifications = DashboardNotification::where('recipient_type', 'user')
                ->where('recipient_id', $userId)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return view('student.dashboard', [
            'student' => $student,
            'notifications' => $notifications,
        ]);
    }

    public function readNotification(Request $request, int $id)
    {
        $userId = (int) $request->session()->get('user_id');

        if (Cutover::ppdbReadFromV3()) {
            $student = $this->v3StudentForUser($userId);
            if ($student?->account_id) {
                $item = Notification::where('account_id', $student->account_id)->findOrFail($id);
                $item->is_read = true;
                $item->read_at = now();
                $item->save();
                return back();
            }
        }

        $item = DashboardNotification::where('recipient_type', 'user')
            ->where('recipient_id', $userId)
            ->findOrFail($id);

        $item->is_read = true;
        $item->read_at = now();
        $item->save();

        return back();
    }
}
