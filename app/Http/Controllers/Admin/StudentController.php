<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationInterview;
use App\Models\ApplicationResult;
use App\Models\ApplicationStatusHistory;
use App\Models\Interview;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Support\AdminActivity;
use App\Support\Cutover;
use App\Support\Notifier;
use App\Support\StudentWorkflow;
use App\Support\V3Sync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    private function v3BaseQuery(): Builder
    {
        return StudentApplication::query()->with(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'account'])->orderByDesc('submitted_at')->orderByDesc('created_at');
    }

    private function applyV3Filters(Builder $query, string $q = '', string $status = '', bool $missingDocs = false): Builder
    {
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('registration_no', 'like', "%{$q}%")
                  ->orWhereHas('profile', function ($p) use ($q) {
                      $p->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                  })
                  ->orWhereHas('guardians', function ($g) use ($q) {
                      $g->where('full_name', 'like', "%{$q}%");
                  });
            });
        }

        if ($status !== '') {
            $query->where('current_status', $status);
        }

        if ($missingDocs) {
            foreach (['kk', 'akta', 'rapor', 'ktp_father', 'ktp_mother'] as $type) {
                $query->whereDoesntHave('documents', function ($d) use ($type) {
                    $d->where('document_type', $type);
                }, 'or');
            }
        }

        return $query;
    }

    private function resolveV3Student(int $id): ?StudentApplication
    {
        return StudentApplication::with(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account'])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('legacy_student_id', $id);
            })
            ->first();
    }

    private function syncAndMirrorV3(StudentApplication $application): void
    {
        if (Cutover::ppdbMirrorLegacy()) {
            $legacy = V3Sync::mirrorApplicationToLegacy($application);
            if ($legacy) {
                V3Sync::syncStudent($legacy);
            }
        }
    }

    private function updateV3Result(StudentApplication $application, string $status, ?string $resultNote = null): void
    {
        if (!in_array($status, ['accepted', 'rejected', 'completed'], true)) {
            return;
        }

        $title = match ($status) {
            'accepted' => 'Pengumuman Kelulusan PPDB',
            'completed' => 'PPDB Selesai',
            default => 'Hasil Seleksi PPDB',
        };

        ApplicationResult::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'result_status' => $status === 'completed' ? 'accepted' : $status,
                'announcement_title' => $title,
                'announcement_body' => $resultNote ?: ($status === 'rejected' ? ($application->rejection_reason ?: 'Mohon maaf, Anda belum dinyatakan lolos.') : 'Selamat, Anda dinyatakan lolos ke tahap berikutnya.'),
                'published_at' => $application->announcement_published_at ?: now(),
                'published_by_account_id' => V3Sync::ensureLegacyAdminAccount((int) session('admin_id'))?->id,
                'updated_at' => now(),
            ]
        );
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $missingDocs = $request->boolean('missing_docs');

        if (Cutover::ppdbReadFromV3()) {
            $query = $this->applyV3Filters($this->v3BaseQuery(), $q, $status, $missingDocs);
            $students = $query->paginate(15)->withQueryString();

            $counts = [
                'draft' => StudentApplication::where('current_status', 'draft')->count(),
                'submitted' => StudentApplication::where('current_status', 'submitted')->count(),
                'under_review' => StudentApplication::where('current_status', 'under_review')->count(),
                'interview' => StudentApplication::where('current_status', 'interview')->count(),
                'accepted' => StudentApplication::where('current_status', 'accepted')->count(),
                'rejected' => StudentApplication::where('current_status', 'rejected')->count(),
                'completed' => StudentApplication::where('current_status', 'completed')->count(),
                'incomplete' => StudentApplication::where('current_status', 'incomplete')->count(),
            ];

            return view('admin.students.index', compact('students', 'q', 'status', 'counts', 'missingDocs'));
        }

        $query = Student::query()->orderByDesc('submitted_at')->orderByDesc('created_at');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%")
                  ->orWhere('registration_number', 'like', "%{$q}%")
                  ->orWhere('father_name', 'like', "%{$q}%")
                  ->orWhere('mother_name', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($missingDocs) {
            $query->where(function ($q) {
                $q->whereNull('kk_file')
                  ->orWhereNull('akta_lahir')
                  ->orWhereNull('nilai_rapor')
                  ->orWhereNull('ktp_father')
                  ->orWhereNull('ktp_mother');
            });
        }

        $students = $query->paginate(15)->withQueryString();

        $counts = [
            'draft' => Student::where('status', 'draft')->count(),
            'submitted' => Student::where('status', 'submitted')->count(),
            'under_review' => Student::where('status', 'under_review')->count(),
            'interview' => Student::where('status', 'interview')->count(),
            'accepted' => Student::whereIn('status', ['accepted','verified'])->count(),
            'rejected' => Student::where('status', 'rejected')->count(),
            'completed' => Student::where('status', 'completed')->count(),
            'incomplete' => Student::where('status', 'incomplete')->count(),
        ];

        return view('admin.students.index', compact('students', 'q', 'status', 'counts', 'missingDocs'));
    }

    public function show(int $id)
    {
        if (Cutover::ppdbReadFromV3()) {
            $student = $this->resolveV3Student($id);
            if ($student) {
                return view('admin.students.show', ['student' => $student, 'availableStatuses' => [
                    'draft', 'submitted', 'under_review', 'interview', 'accepted', 'rejected', 'completed', 'incomplete',
                ]]);
            }
        }

        $student = Student::with(['user', 'statusHistories', 'interview'])->findOrFail($id);
        return view('admin.students.show', ['student' => $student, 'availableStatuses' => [
            'draft', 'submitted', 'under_review', 'interview', 'accepted', 'rejected', 'completed', 'incomplete',
        ]]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required','string','max:30'],
            'note' => ['nullable','string'],
            'result_note' => ['nullable','string'],
        ]);

        if (Cutover::ppdbReadFromV3()) {
            $student = $this->resolveV3Student($id);
            if ($student) {
                $from = $student->current_status;
                $to = StudentWorkflow::normalizeStatus($data['status']);
                $student->result_notes = $data['result_note'] ?? $student->result_notes;
                $student->last_status_note = $data['note'] ?? null;
                $student->current_status = $to;
                $student->reviewed_at = now();
                $student->reviewed_by_account_id = V3Sync::ensureLegacyAdminAccount((int) session('admin_id'))?->id;
                if ($to === 'rejected') {
                    $student->rejection_reason = $data['note'] ?? $student->rejection_reason;
                } elseif ($to !== 'rejected') {
                    $student->rejection_reason = null;
                }
                if (!$student->registration_no && in_array($to, ['submitted', 'under_review', 'interview', 'accepted', 'completed', 'rejected'], true)) {
                    $student->registration_no = StudentWorkflow::generateRegistrationNumber();
                }
                if ($to === 'submitted' && !$student->submitted_at) {
                    $student->submitted_at = now();
                }
                if (in_array($to, ['accepted', 'rejected', 'completed'], true) && !$student->announcement_published_at) {
                    $student->announcement_published_at = now();
                }
                $student->save();

                ApplicationStatusHistory::create([
                    'application_id' => $student->id,
                    'student_id' => $student->legacy_student_id,
                    'from_status' => StudentWorkflow::normalizeStatus($from),
                    'to_status' => $to,
                    'actor_type' => 'admin',
                    'actor_id' => (int) session('admin_id'),
                    'changed_by_account_id' => $student->reviewed_by_account_id,
                    'note' => $data['note'] ?? null,
                    'created_at' => now(),
                ]);

                $this->updateV3Result($student, $to, $data['result_note'] ?? null);
                $this->syncAndMirrorV3($student->fresh(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account']));

                if ($student->account?->legacy_user_id) {
                    $note = $data['note'] ?? null;
                    Notifier::user((int) $student->account->legacy_user_id, 'Status PPDB diperbarui', 'Status pendaftaran Anda kini: ' . StudentWorkflow::label($student->current_status) . ($note ? ' — ' . $note : ''), $student->current_status === 'rejected' ? 'danger' : 'info', route('student.dashboard'));
                }
                AdminActivity::log((int) session('admin_id'), 'student.status_updated', StudentApplication::class, $student->id, 'Mengubah status pendaftar menjadi ' . $student->current_status, ['note' => $data['note'] ?? null]);

                return back()->with('ok', 'Status pendaftar berhasil diperbarui.');
            }
        }

        $student = Student::findOrFail($id);
        $student->result_note = $data['result_note'] ?? $student->result_note;
        if (($data['status'] ?? '') === 'rejected') {
            $student->rejection_reason = $data['note'] ?? $student->rejection_reason;
        } elseif (($data['status'] ?? '') !== 'rejected') {
            $student->rejection_reason = null;
        }
        $student->save();

        StudentWorkflow::transition($student, $data['status'], $data['note'] ?? null, 'admin', (int) session('admin_id'));

        $note = $data['note'] ?? null;
        V3Sync::syncStudent($student);
        Notifier::user((int) $student->user_id, 'Status PPDB diperbarui', 'Status pendaftaran Anda kini: ' . StudentWorkflow::label($student->status) . ($note ? ' — ' . $note : ''), $student->status === 'rejected' ? 'danger' : 'info', route('student.dashboard'));
        AdminActivity::log((int) session('admin_id'), 'student.status_updated', Student::class, $student->id, 'Mengubah status pendaftar menjadi ' . $student->status, ['note' => $note]);

        return back()->with('ok', 'Status pendaftar berhasil diperbarui.');
    }

    public function verify(Request $request, int $id)
    {
        $request->merge(['status' => 'accepted', 'note' => 'Pendaftar dinyatakan diterima.']);
        return $this->updateStatus($request, $id);
    }

    public function reject(Request $request, int $id)
    {
        $request->merge(['status' => 'rejected', 'note' => $request->input('rejection_reason')]);
        return $this->updateStatus($request, $id);
    }

    public function scheduleInterview(Request $request, int $id)
    {
        $data = $request->validate([
            'scheduled_at' => ['required','date'],
            'location' => ['nullable','string','max:255'],
            'meeting_link' => ['nullable','url','max:255'],
            'notes' => ['nullable','string'],
        ]);

        if (Cutover::ppdbReadFromV3()) {
            $student = $this->resolveV3Student($id);
            if ($student) {
                $interview = ApplicationInterview::updateOrCreate(
                    ['application_id' => $student->id, 'scheduled_at' => $data['scheduled_at']],
                    [
                        'interview_type' => 'interview',
                        'location' => $data['location'] ?? null,
                        'meeting_link' => $data['meeting_link'] ?? null,
                        'attendance_status' => 'scheduled',
                        'notes' => $data['notes'] ?? null,
                        'created_by_account_id' => V3Sync::ensureLegacyAdminAccount((int) session('admin_id'))?->id,
                        'updated_at' => now(),
                    ]
                );

                if ($student->current_status !== 'interview') {
                    $request->merge(['status' => 'interview', 'note' => 'Jadwal interview / tes telah dibuat.', 'result_note' => $student->result_notes]);
                    $this->updateStatus($request, $student->id);
                }

                $this->syncAndMirrorV3($student->fresh(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account']));
                if ($student->account?->legacy_user_id) {
                    Notifier::user((int) $student->account->legacy_user_id, 'Jadwal interview / tes tersedia', 'Silakan cek jadwal interview Anda di dashboard PPDB.', 'info', route('student.dashboard'));
                }
                AdminActivity::log((int) session('admin_id'), 'student.interview_scheduled', ApplicationInterview::class, $interview->id, 'Menjadwalkan interview', ['application_id' => $student->id]);

                return back()->with('ok', 'Jadwal interview berhasil disimpan.');
            }
        }

        $student = Student::findOrFail($id);
        $interview = Interview::updateOrCreate(
            ['student_id' => $student->id],
            [
                'scheduled_at' => $data['scheduled_at'],
                'location' => $data['location'] ?? null,
                'meeting_link' => $data['meeting_link'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attendance_status' => 'scheduled',
                'created_by_admin_id' => (int) session('admin_id'),
                'updated_at' => now(),
            ]
        );

        if ($student->status_normalized !== 'interview') {
            StudentWorkflow::transition($student, 'interview', 'Jadwal interview / tes telah dibuat.', 'admin', (int) session('admin_id'));
        }

        Notifier::user((int) $student->user_id, 'Jadwal interview / tes tersedia', 'Silakan cek jadwal interview Anda di dashboard PPDB.', 'info', route('student.dashboard'));
        V3Sync::syncInterview($student, $interview);
        AdminActivity::log((int) session('admin_id'), 'student.interview_scheduled', Interview::class, $interview->id, 'Menjadwalkan interview', ['student_id' => $student->id]);

        return back()->with('ok', 'Jadwal interview berhasil disimpan.');
    }

    public function updateInterviewAttendance(Request $request, int $id)
    {
        $data = $request->validate([
            'attendance_status' => ['required','in:scheduled,present,absent,rescheduled'],
        ]);

        if (Cutover::ppdbReadFromV3()) {
            $student = $this->resolveV3Student($id);
            if ($student && $student->interview) {
                $student->interview->attendance_status = $data['attendance_status'];
                $student->interview->updated_at = now();
                $student->interview->save();
                $this->syncAndMirrorV3($student->fresh(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account']));
                AdminActivity::log((int) session('admin_id'), 'student.interview_attendance_updated', ApplicationInterview::class, $student->interview->id, 'Mengubah status kehadiran interview', ['status' => $data['attendance_status']]);
                return back()->with('ok', 'Status kehadiran interview diperbarui.');
            }
        }

        $student = Student::with('interview')->findOrFail($id);
        abort_unless($student->interview, 404);
        $student->interview->attendance_status = $data['attendance_status'];
        $student->interview->updated_at = now();
        $student->interview->save();

        V3Sync::syncInterview($student, $student->interview);
        AdminActivity::log((int) session('admin_id'), 'student.interview_attendance_updated', Interview::class, $student->interview->id, 'Mengubah status kehadiran interview', ['status' => $data['attendance_status']]);

        return back()->with('ok', 'Status kehadiran interview diperbarui.');
    }

    public function download(int $id, string $column)
    {
        $allowed = ['kk_file','ktp_father','ktp_mother','akta_lahir','nilai_rapor','ktp_guardian','kk_guardian'];
        abort_unless(in_array($column, $allowed, true), 404);

        if (Cutover::ppdbReadFromV3()) {
            $student = $this->resolveV3Student($id);
            if ($student) {
                $path = (string) ($student->{$column} ?? '');
                abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);
                return Storage::disk('public')->download($path);
            }
        }

        $student = Student::findOrFail($id);
        $path = (string) ($student->{$column} ?? '');
        abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $status = trim((string) $request->query('status', ''));
        $missingDocs = $request->boolean('missing_docs');
        $filename = 'ppdb-export-' . now()->format('Ymd_His') . '.csv';

        if (Cutover::ppdbReadFromV3()) {
            $query = $this->applyV3Filters($this->v3BaseQuery()->orderBy('id'), '', $status, $missingDocs);
            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'No Pendaftaran', 'Nama', 'No HP', 'Status', 'Ayah', 'Ibu', 'Submitted At', 'Dokumen Lengkap']);
                foreach ($query->cursor() as $student) {
                    fputcsv($out, [
                        $student->id,
                        $student->registration_number,
                        $student->name,
                        $student->phone,
                        $student->status_label,
                        $student->father_name,
                        $student->mother_name,
                        optional($student->submitted_at ?: $student->created_at)->format('Y-m-d H:i:s'),
                        $student->is_documents_complete ? 'Ya' : 'Tidak',
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        $query = Student::query()->orderBy('id');
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($missingDocs) {
            $query->where(function ($q) {
                $q->whereNull('kk_file')
                    ->orWhereNull('akta_lahir')
                    ->orWhereNull('nilai_rapor')
                    ->orWhereNull('ktp_father')
                    ->orWhereNull('ktp_mother');
            });
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'No Pendaftaran', 'Nama', 'No HP', 'Status', 'Ayah', 'Ibu', 'Submitted At', 'Dokumen Lengkap']);
            foreach ($query->cursor() as $student) {
                fputcsv($out, [
                    $student->id,
                    $student->registration_number,
                    $student->name,
                    $student->phone,
                    $student->status_label,
                    $student->father_name,
                    $student->mother_name,
                    optional($student->submitted_at)->format('Y-m-d H:i:s'),
                    $student->is_documents_complete ? 'Ya' : 'Tidak',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
