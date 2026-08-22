<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Models\ApplicationGuardian;
use App\Models\ApplicationStatusHistory;
use App\Models\Document;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\StudentProfile;
use App\Support\Cutover;
use App\Support\Notifier;
use App\Support\StudentWorkflow;
use App\Support\V3Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PPDBController extends Controller
{
    private function v3ApplicationForUser(int $legacyUserId): ?StudentApplication
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

    private function ensureDraftApplication(int $legacyUserId): StudentApplication
    {
        $account = V3Sync::ensureLegacyUserAccount($legacyUserId);
        $application = StudentApplication::query()
            ->where('account_id', $account->id)
            ->orderByDesc('id')
            ->first();

        if (!$application) {
            $year = (int) now()->format('Y');
            $application = StudentApplication::create([
                'account_id' => $account->id,
                'academic_year' => $year . '/' . ($year + 1),
                'current_status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            ApplicationStatusHistory::create([
                'application_id' => $application->id,
                'student_id' => null,
                'from_status' => null,
                'to_status' => 'draft',
                'actor_type' => 'user',
                'actor_id' => $legacyUserId,
                'changed_by_account_id' => $account->id,
                'note' => 'Data pendaftaran mulai dibuat.',
                'created_at' => now(),
            ]);
        }

        return $application->loadMissing(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account']);
    }

    private function getStudentForUser(int $userId): Student|StudentApplication|null
    {
        if (Cutover::ppdbReadFromV3()) {
            $application = $this->v3ApplicationForUser($userId);
            if ($application) {
                return $application;
            }

            $legacy = Student::with(['statusHistories', 'interview'])->where('user_id', $userId)->orderByDesc('id')->first();
            if ($legacy) {
                V3Sync::syncStudent($legacy);
                return $this->v3ApplicationForUser($userId) ?: $legacy;
            }

            return null;
        }

        return Student::with(['statusHistories', 'interview'])->where('user_id', $userId)->orderByDesc('id')->first();
    }

    private function requireStudent(Request $request): Student|StudentApplication
    {
        $userId = (int) $request->session()->get('user_id');
        $student = $this->getStudentForUser($userId);
        if (!$student) {
            abort(404, 'Data pendaftaran belum dibuat. Mulai dari Step 1.');
        }
        return $student;
    }

    public function step1(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');
        $student = $this->getStudentForUser($userId);

        return view('ppdb.step1', ['student' => $student]);
    }

    public function step1Store(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');

        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'dob' => ['required','date'],
            'phone' => ['required','string','max:20'],
            'address' => ['required','string'],
            'birthplace' => ['nullable','string','max:100'],
            'gender' => ['nullable','string','max:20'],
            'religion_child' => ['nullable','string','max:30'],
            'student_hobby' => ['nullable','string','max:100'],
            'goal' => ['nullable','string','max:100'],
            'motivation' => ['nullable','string','max:255'],
        ]);

        if (Cutover::ppdbWriteToV3()) {
            $application = $this->ensureDraftApplication($userId);
            StudentProfile::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'full_name' => $data['name'],
                    'birth_date' => $data['dob'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'birth_place' => $data['birthplace'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'religion' => $data['religion_child'] ?? null,
                    'hobby' => $data['student_hobby'] ?? null,
                    'goal' => $data['goal'] ?? null,
                    'motivation' => $data['motivation'] ?? null,
                    'updated_at' => now(),
                ]
            );
            $application->refresh()->loadMissing(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account']);

            if (Cutover::ppdbMirrorLegacy()) {
                V3Sync::mirrorApplicationToLegacy($application);
            }

            return redirect()->route('ppdb.step2')->with('ok', 'Data siswa berhasil disimpan.');
        }

        $student = Student::with(['statusHistories', 'interview'])->where('user_id', $userId)->orderByDesc('id')->first();
        $isNew = false;
        if (!$student) {
            $student = new Student();
            $student->user_id = $userId;
            $student->status = 'draft';
            $isNew = true;
        }

        $student->fill($data);
        $student->save();

        if ($isNew) {
            StudentWorkflow::transition($student, 'draft', 'Data pendaftaran mulai dibuat.', 'user', $userId);
        } else {
            V3Sync::syncStudent($student);
        }

        return redirect()->route('ppdb.step2')->with('ok', 'Data siswa berhasil disimpan.');
    }

    public function step2(Request $request)
    {
        $student = $this->requireStudent($request);
        return view('ppdb.step2', ['student' => $student]);
    }

    public function step2Store(Request $request)
    {
        $student = $this->requireStudent($request);

        $data = $request->validate([
            'father_name' => ['nullable','string','max:100'],
            'father_phone' => ['nullable','string','max:20'],
            'father_job' => ['nullable','string','max:100'],
            'father_email' => ['nullable','email','max:100'],
            'father_income' => ['nullable','string','max:50'],
            'father_birthplace' => ['nullable','string','max:100'],
            'father_dob' => ['nullable','date'],
            'father_religion' => ['nullable','string','max:30'],

            'mother_name' => ['nullable','string','max:100'],
            'mother_phone' => ['nullable','string','max:20'],
            'mother_job' => ['nullable','string','max:100'],
            'mother_email' => ['nullable','email','max:100'],
            'mother_income' => ['nullable','string','max:50'],
            'mother_birthplace' => ['nullable','string','max:100'],
            'mother_dob' => ['nullable','date'],
            'mother_religion' => ['nullable','string','max:30'],

            'guardian_name' => ['nullable','string','max:100'],
            'guardian_phone' => ['nullable','string','max:20'],
            'guardian_relation' => ['nullable','string','max:50'],
            'guardian_email' => ['nullable','email','max:100'],
            'guardian_income' => ['nullable','string','max:50'],
            'guardian_birthplace' => ['nullable','string','max:100'],
            'guardian_dob' => ['nullable','date'],
            'guardian_religion' => ['nullable','string','max:30'],
        ]);

        if ($student instanceof StudentApplication && Cutover::ppdbWriteToV3()) {
            $map = [
                'father' => [
                    'full_name' => $data['father_name'] ?? null,
                    'phone' => $data['father_phone'] ?? null,
                    'email' => $data['father_email'] ?? null,
                    'occupation' => $data['father_job'] ?? null,
                    'income_range' => $data['father_income'] ?? null,
                    'birth_place' => $data['father_birthplace'] ?? null,
                    'birth_date' => $data['father_dob'] ?? null,
                    'religion' => $data['father_religion'] ?? null,
                    'relationship_to_student' => 'Ayah',
                ],
                'mother' => [
                    'full_name' => $data['mother_name'] ?? null,
                    'phone' => $data['mother_phone'] ?? null,
                    'email' => $data['mother_email'] ?? null,
                    'occupation' => $data['mother_job'] ?? null,
                    'income_range' => $data['mother_income'] ?? null,
                    'birth_place' => $data['mother_birthplace'] ?? null,
                    'birth_date' => $data['mother_dob'] ?? null,
                    'religion' => $data['mother_religion'] ?? null,
                    'relationship_to_student' => 'Ibu',
                ],
                'guardian' => [
                    'full_name' => $data['guardian_name'] ?? null,
                    'phone' => $data['guardian_phone'] ?? null,
                    'email' => $data['guardian_email'] ?? null,
                    'occupation' => null,
                    'income_range' => $data['guardian_income'] ?? null,
                    'birth_place' => $data['guardian_birthplace'] ?? null,
                    'birth_date' => $data['guardian_dob'] ?? null,
                    'religion' => $data['guardian_religion'] ?? null,
                    'relationship_to_student' => $data['guardian_relation'] ?? null,
                ],
            ];

            foreach ($map as $type => $payload) {
                $filled = collect($payload)->filter(fn($v) => $v !== null && $v !== '')->isNotEmpty();
                if ($filled) {
                    ApplicationGuardian::query()->updateOrCreate(
                        ['application_id' => $student->id, 'guardian_type' => $type],
                        $payload
                    );
                }
            }

            $student->refresh()->loadMissing(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account']);
            if (Cutover::ppdbMirrorLegacy()) {
                V3Sync::mirrorApplicationToLegacy($student);
            }

            return redirect()->route('ppdb.step3')->with('ok', 'Data orang tua / wali berhasil disimpan.');
        }

        $student->fill($data);
        $student->save();
        V3Sync::syncStudent($student);

        return redirect()->route('ppdb.step3')->with('ok', 'Data orang tua / wali berhasil disimpan.');
    }

    public function step3(Request $request)
    {
        $student = $this->requireStudent($request);
        return view('ppdb.step3', ['student' => $student]);
    }

    private function storeUpload(Request $request, Student|StudentApplication $student, string $field, string $column): void
    {
        if (!$request->hasFile($field)) {
            return;
        }

        $file = $request->file($field);
        if (!$file->isValid()) {
            return;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = now()->format('Ymd_His') . '_' . $field . '_' . Str::random(6) . '_' . ($safeName ?: 'file') . '.' . $ext;

        $folderId = $student instanceof StudentApplication ? ('application_' . $student->id) : $student->id;
        $path = $file->storeAs('ppdb/' . $folderId, $filename, 'public');

        $typeMap = [
            'kk_file' => 'kk',
            'akta_lahir' => 'akta',
            'nilai_rapor' => 'rapor',
            'ktp_father' => 'ktp_father',
            'ktp_mother' => 'ktp_mother',
            'ktp_guardian' => 'ktp_guardian',
            'kk_guardian' => 'kk_guardian',
        ];

        if ($student instanceof StudentApplication && Cutover::ppdbWriteToV3()) {
            $type = $typeMap[$column] ?? null;
            if ($type) {
                ApplicationDocument::query()->updateOrCreate(
                    ['application_id' => $student->id, 'document_type' => $type],
                    [
                        'legacy_source' => 'cutover_v3',
                        'original_name' => $file->getClientOriginalName(),
                        'stored_name' => $filename,
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by_account_id' => $student->account_id,
                        'updated_at' => now(),
                    ]
                );
            }
            return;
        }

        $student->{$column} = $path;

        if (isset($typeMap[$column])) {
            Document::updateOrCreate(
                ['student_id' => $student->id, 'document_type' => $typeMap[$column]],
                ['file_path' => $path, 'uploaded_at' => now()]
            );
        }
    }

    public function step3Store(Request $request)
    {
        $student = $this->requireStudent($request);

        $request->validate([
            'kk_file' => ['nullable','file','max:5120','mimes:pdf,jpg,jpeg,png'],
            'ktp_father' => ['nullable','file','max:5120','mimes:pdf,jpg,jpeg,png'],
            'ktp_mother' => ['nullable','file','max:5120','mimes:pdf,jpg,jpeg,png'],
            'akta_lahir' => ['nullable','file','max:5120','mimes:pdf,jpg,jpeg,png'],
            'nilai_rapor' => ['nullable','file','max:5120','mimes:pdf,jpg,jpeg,png'],
            'ktp_guardian' => ['nullable','file','max:5120','mimes:pdf,jpg,jpeg,png'],
            'kk_guardian' => ['nullable','file','max:5120','mimes:pdf,jpg,jpeg,png'],
        ]);

        $this->storeUpload($request, $student, 'kk_file', 'kk_file');
        $this->storeUpload($request, $student, 'ktp_father', 'ktp_father');
        $this->storeUpload($request, $student, 'ktp_mother', 'ktp_mother');
        $this->storeUpload($request, $student, 'akta_lahir', 'akta_lahir');
        $this->storeUpload($request, $student, 'nilai_rapor', 'nilai_rapor');
        $this->storeUpload($request, $student, 'ktp_guardian', 'ktp_guardian');
        $this->storeUpload($request, $student, 'kk_guardian', 'kk_guardian');

        if ($student instanceof StudentApplication && Cutover::ppdbWriteToV3()) {
            $student->updated_at = now();
            $student->save();
            $student->refresh()->loadMissing(['profile', 'guardians', 'documents', 'statusHistories', 'interview', 'result', 'account']);
            if (Cutover::ppdbMirrorLegacy()) {
                V3Sync::mirrorApplicationToLegacy($student);
            }
        } else {
            $student->save();
            V3Sync::syncStudent($student);
        }

        return redirect()->route('ppdb.confirm')->with('ok', 'Berkas berhasil diperbarui.');
    }

    public function confirm(Request $request)
    {
        $student = $this->requireStudent($request);
        return view('ppdb.confirm', ['student' => $student]);
    }

    public function submit(Request $request)
    {
        $student = $this->requireStudent($request);
        $userId = (int) $request->session()->get('user_id');

        $isComplete = $student->is_documents_complete;
        $note = $isComplete
            ? 'Formulir pendaftaran berhasil dikirim.'
            : 'Formulir dikirim, namun masih ada berkas yang perlu dilengkapi.';
        $toStatus = $isComplete ? 'submitted' : 'incomplete';

        if ($student instanceof StudentApplication && Cutover::ppdbWriteToV3()) {
            $from = $student->current_status;
            if (!$student->registration_no && in_array($toStatus, ['submitted', 'under_review', 'interview', 'accepted', 'completed', 'rejected'], true)) {
                $student->registration_no = StudentWorkflow::generateRegistrationNumber();
            }
            $student->current_status = $toStatus;
            $student->last_status_note = $note;
            if ($toStatus === 'submitted' && !$student->submitted_at) {
                $student->submitted_at = now();
            }
            $student->updated_at = now();
            $student->save();

            $account = $student->account ?: V3Sync::ensureLegacyUserAccount($userId);
            ApplicationStatusHistory::create([
                'application_id' => $student->id,
                'student_id' => $student->legacy_student_id,
                'from_status' => StudentWorkflow::normalizeStatus($from),
                'to_status' => $toStatus,
                'actor_type' => 'user',
                'actor_id' => $userId,
                'changed_by_account_id' => $account?->id,
                'note' => $note,
                'created_at' => now(),
            ]);

            if (Cutover::ppdbMirrorLegacy()) {
                $legacy = V3Sync::mirrorApplicationToLegacy($student);
                if ($legacy) {
                    V3Sync::syncStudent($legacy);
                }
            }
        } else {
            StudentWorkflow::transition($student, $toStatus, $note, 'user', $userId);
        }

        Notifier::user($userId, 'Pendaftaran berhasil dikirim', $note, $isComplete ? 'success' : 'warning', route('student.dashboard'));
        Notifier::admins('Pendaftaran baru masuk', 'Ada pendaftar baru: ' . $student->name . ' (' . ($student->registration_number ?: 'belum ada nomor') . ')', 'info', route('admin.students.show', $student->id));

        return redirect()->route('ppdb.success');
    }

    public function success(Request $request)
    {
        $student = $this->requireStudent($request);
        return view('ppdb.success', ['student' => $student]);
    }

    public function download(Request $request, int $studentId, string $column)
    {
        $userId = (int) $request->session()->get('user_id');
        $allowed = ['kk_file','ktp_father','ktp_mother','akta_lahir','nilai_rapor','ktp_guardian','kk_guardian'];
        abort_unless(in_array($column, $allowed, true), 404);

        if (Cutover::ppdbReadFromV3()) {
            $student = $this->v3ApplicationForUser($userId);
            abort_unless($student && (int) $student->id === $studentId, 404);
            $path = (string) ($student->{$column} ?? '');
            abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);
            return Storage::disk('public')->download($path);
        }

        $student = Student::where('id', $studentId)->where('user_id', $userId)->firstOrFail();
        $path = (string) ($student->{$column} ?? '');
        abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path);
    }
}
