<?php

namespace App\Support;

use App\Models\ApplicationStatusHistory;
use App\Models\Student;
use App\Models\StudentApplication;
use Illuminate\Support\Facades\DB;

class StudentWorkflow
{
    public const ALLOWED_STATUSES = [
        'draft',
        'submitted',
        'under_review',
        'interview',
        'accepted',
        'rejected',
        'completed',
        'verified', // legacy alias
        'incomplete',
        'pending',
    ];

    public static function normalizeStatus(?string $status): string
    {
        $status = trim((string) $status);
        return match ($status) {
            '', 'pending', 'belum_daftar' => 'draft',
            'verified' => 'accepted',
            default => in_array($status, self::ALLOWED_STATUSES, true) ? $status : 'draft',
        };
    }

    public static function label(string $status): string
    {
        return match (self::normalizeStatus($status)) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'interview' => 'Interview',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'incomplete' => 'Perlu Dilengkapi',
            default => ucfirst(self::normalizeStatus($status)),
        };
    }

    public static function progressPercent(string $status): int
    {
        return match (self::normalizeStatus($status)) {
            'draft' => 10,
            'submitted' => 30,
            'under_review' => 50,
            'interview' => 70,
            'accepted' => 90,
            'completed' => 100,
            'rejected' => 100,
            'incomplete' => 25,
            default => 10,
        };
    }

    public static function generateRegistrationNumber(?int $year = null): string
    {
        $year = $year ?: (int) now()->format('Y');
        $prefix = 'PPDB-' . $year . '-';

        $latest = null;
        if (\App\Support\Cutover::ppdbWriteToV3() && class_exists(StudentApplication::class)) {
            $latest = StudentApplication::query()
                ->whereNotNull('registration_no')
                ->where('registration_no', 'like', $prefix . '%')
                ->orderByDesc('registration_no')
                ->value('registration_no');
        }

        if (!$latest) {
            $latest = Student::query()
                ->whereNotNull('registration_number')
                ->where('registration_number', 'like', $prefix . '%')
                ->orderByDesc('registration_number')
                ->value('registration_number');
        }

        $next = 1;
        if ($latest && preg_match('/(\d{4})$/', $latest, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function transition(Student $student, string $toStatus, ?string $note = null, string $actorType = 'system', ?int $actorId = null): void
    {
        $from = self::normalizeStatus($student->status);
        $to = self::normalizeStatus($toStatus);

        DB::transaction(function () use ($student, $from, $to, $note, $actorType, $actorId) {
            if ($student->registration_number === null && in_array($to, ['submitted','under_review','interview','accepted','completed','rejected'], true)) {
                $student->registration_number = self::generateRegistrationNumber();
            }

            $student->status = $to;
            $student->last_status_note = $note;
            if ($to === 'submitted' && $student->submitted_at === null) {
                $student->submitted_at = now();
            }
            if (in_array($to, ['under_review','interview','accepted','rejected','completed'], true)) {
                $student->reviewed_at = now();
                if ($actorType === 'admin' && $actorId) {
                    $student->reviewed_by_admin_id = $actorId;
                }
            }
            if (in_array($to, ['accepted','rejected','completed'], true) && $student->announcement_published_at === null) {
                $student->announcement_published_at = now();
            }
            $student->save();

            $accountId = null;
            if ($actorType === 'admin' && $actorId) {
                $accountId = V3Sync::ensureLegacyAdminAccount($actorId)?->id;
            } elseif ($actorType === 'user' && $actorId) {
                $accountId = V3Sync::ensureLegacyUserAccount($actorId)?->id;
            }

            $application = V3Sync::syncStudent($student);

            ApplicationStatusHistory::create([
                'application_id' => $application?->id,
                'student_id' => $student->id,
                'from_status' => $from,
                'to_status' => $to,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'changed_by_account_id' => $accountId,
                'note' => $note,
                'created_at' => now(),
            ]);
        });
    }
}
