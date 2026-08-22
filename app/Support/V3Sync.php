<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Admin;
use App\Models\ApplicationDocument;
use App\Models\ApplicationGuardian;
use App\Models\ApplicationInterview;
use App\Models\ApplicationResult;
use App\Models\ApplicationStatusHistory;
use App\Models\Document;
use App\Models\Facility;
use App\Models\GalleryItem;
use App\Models\LegacyGalleryItem;
use App\Models\Interview;
use App\Models\LegacyUser;
use App\Models\MediaAsset;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class V3Sync
{
    public static function accountForLegacyUserId(?int $legacyUserId): ?Account
    {
        if (!$legacyUserId || !Schema::hasTable('accounts')) return null;
        return Account::query()->where('legacy_user_id', $legacyUserId)->first();
    }

    public static function accountForLegacyAdminId(?int $legacyAdminId): ?Account
    {
        if (!$legacyAdminId || !Schema::hasTable('accounts')) return null;
        return Account::query()->where('legacy_admin_id', $legacyAdminId)->first();
    }

    public static function ensureLegacyUserAccount(LegacyUser|int|null $user): ?Account
    {
        if (!Schema::hasTable('accounts')) return null;
        if (is_int($user)) {
            $user = LegacyUser::query()->find($user);
        }
        if (!$user) return null;

        return DB::transaction(function () use ($user) {
            return Account::query()->updateOrCreate(
                ['legacy_user_id' => $user->user_id],
                [
                    'account_type' => 'student',
                    'username' => $user->username,
                    'password_hash' => $user->password,
                    'is_active' => true,
                    'last_login_at' => now(),
                ]
            );
        });
    }

    public static function ensureLegacyAdminAccount(Admin|int|null $admin): ?Account
    {
        if (!Schema::hasTable('accounts')) return null;
        if (is_int($admin)) {
            $admin = Admin::query()->find($admin);
        }
        if (!$admin) return null;

        $roleCode = trim((string) ($admin->role ?: 'superadmin')) ?: 'superadmin';

        return DB::transaction(function () use ($admin, $roleCode) {
            $account = Account::query()->updateOrCreate(
                ['legacy_admin_id' => $admin->id],
                [
                    'account_type' => 'staff',
                    'username' => $admin->username,
                    'password_hash' => $admin->password,
                    'is_active' => true,
                    'last_login_at' => now(),
                ]
            );

            if (Schema::hasTable('roles') && Schema::hasTable('account_roles')) {
                $role = Role::query()->where('code', $roleCode)->first() ?: Role::query()->where('code', 'superadmin')->first();
                if ($role) {
                    $account->roles()->syncWithoutDetaching([$role->id]);
                }
            }

            return $account;
        });
    }

    public static function syncStudent(Student|int|null $student): ?StudentApplication
    {
        if (!Schema::hasTable('student_applications')) return null;
        if (is_int($student)) {
            $student = Student::query()->with('documents')->find($student);
        }
        if (!$student) return null;

        return DB::transaction(function () use ($student) {
            $account = self::ensureLegacyUserAccount((int) $student->user_id);
            $reviewerAccount = self::ensureLegacyAdminAccount($student->reviewed_by_admin_id ? (int) $student->reviewed_by_admin_id : null);

            $application = StudentApplication::query()->updateOrCreate(
                ['legacy_student_id' => $student->id],
                [
                    'account_id' => $account?->id,
                    'registration_no' => $student->registration_number,
                    'academic_year' => self::academicYearFrom($student->created_at),
                    'current_status' => StudentWorkflow::normalizeStatus($student->status),
                    'submitted_at' => $student->submitted_at,
                    'reviewed_at' => $student->reviewed_at,
                    'reviewed_by_account_id' => $reviewerAccount?->id,
                    'rejection_reason' => $student->rejection_reason,
                    'result_notes' => $student->result_note,
                    'announcement_published_at' => $student->announcement_published_at,
                    'last_status_note' => $student->last_status_note,
                    'created_at' => $student->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            StudentProfile::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'full_name' => $student->name,
                    'gender' => $student->gender,
                    'birth_place' => $student->birthplace,
                    'birth_date' => $student->dob,
                    'religion' => $student->religion_child,
                    'phone' => $student->phone,
                    'address' => $student->address,
                    'hobby' => $student->student_hobby,
                    'goal' => $student->goal,
                    'motivation' => $student->motivation,
                ]
            );

            self::upsertGuardian($application->id, 'father', [
                'full_name' => $student->father_name,
                'phone' => $student->father_phone,
                'email' => $student->father_email,
                'occupation' => $student->father_job,
                'income_range' => $student->father_income,
                'birth_place' => $student->father_birthplace,
                'birth_date' => $student->father_dob,
                'religion' => $student->father_religion,
                'relationship_to_student' => 'Ayah',
            ]);
            self::upsertGuardian($application->id, 'mother', [
                'full_name' => $student->mother_name,
                'phone' => $student->mother_phone,
                'email' => $student->mother_email,
                'occupation' => $student->mother_job,
                'income_range' => $student->mother_income,
                'birth_place' => $student->mother_birthplace,
                'birth_date' => $student->mother_dob,
                'religion' => $student->mother_religion,
                'relationship_to_student' => 'Ibu',
            ]);
            self::upsertGuardian($application->id, 'guardian', [
                'full_name' => $student->guardian_name,
                'phone' => $student->guardian_phone,
                'email' => $student->guardian_email,
                'occupation' => null,
                'income_range' => $student->guardian_income,
                'birth_place' => $student->guardian_birthplace,
                'birth_date' => $student->guardian_dob,
                'religion' => $student->guardian_religion,
                'relationship_to_student' => $student->guardian_relation,
            ]);

            self::syncDocuments($application, $student);
            self::syncResult($application, $student, $reviewerAccount?->id);

            return $application;
        });
    }

    private static function upsertGuardian(int $applicationId, string $type, array $data): void
    {
        $filled = collect($data)->filter(fn($v) => $v !== null && $v !== '')->isNotEmpty();
        if (!$filled) {
            return;
        }

        ApplicationGuardian::query()->updateOrCreate(
            ['application_id' => $applicationId, 'guardian_type' => $type],
            $data
        );
    }

    private static function syncDocuments(StudentApplication $application, Student $student): void
    {
        if (!Schema::hasTable('application_documents')) return;

        $account = self::accountForLegacyUserId((int) $student->user_id);
        $columnMap = [
            'kk_file' => 'kk',
            'akta_lahir' => 'akta',
            'nilai_rapor' => 'rapor',
            'ktp_father' => 'ktp_father',
            'ktp_mother' => 'ktp_mother',
            'ktp_guardian' => 'ktp_guardian',
            'kk_guardian' => 'kk_guardian',
        ];

        foreach ($columnMap as $column => $type) {
            $path = (string) ($student->{$column} ?? '');
            if ($path === '') continue;
            self::upsertApplicationDocument($application->id, $type, $path, 'students', $student->id, $account?->id);
        }

        if (Schema::hasTable('documents')) {
            $docs = Document::query()->where('student_id', $student->id)->get();
            foreach ($docs as $doc) {
                if (!$doc->file_path) continue;
                self::upsertApplicationDocument($application->id, (string) $doc->document_type, (string) $doc->file_path, 'documents', $doc->id, $account?->id, $doc->uploaded_at);
            }
        }
    }

    private static function upsertApplicationDocument(int $applicationId, string $type, string $path, string $source, int $sourceId, ?int $accountId = null, $createdAt = null): void
    {
        $original = basename($path);
        ApplicationDocument::query()->updateOrCreate(
            ['application_id' => $applicationId, 'document_type' => $type],
            [
                'legacy_source' => $source,
                'legacy_source_id' => $sourceId,
                'original_name' => $original,
                'stored_name' => $original,
                'file_path' => $path,
                'uploaded_by_account_id' => $accountId,
                'created_at' => $createdAt ?: now(),
                'updated_at' => now(),
            ]
        );
    }

    public static function syncStudentStatus(Student $student, ?string $fromStatus = null, ?string $note = null, string $actorType = 'system', ?int $actorId = null): void
    {
        if (!Schema::hasTable('application_status_histories')) return;
        $application = self::syncStudent($student);
        if (!$application) return;

        $accountId = null;
        if ($actorType === 'admin' && $actorId) {
            $accountId = self::ensureLegacyAdminAccount($actorId)?->id;
        } elseif ($actorType === 'user' && $actorId) {
            $accountId = self::ensureLegacyUserAccount($actorId)?->id;
        }

        ApplicationStatusHistory::query()->create([
            'application_id' => $application->id,
            'student_id' => $student->id,
            'from_status' => $fromStatus ? StudentWorkflow::normalizeStatus($fromStatus) : null,
            'to_status' => StudentWorkflow::normalizeStatus($student->status),
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'changed_by_account_id' => $accountId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    public static function syncInterview(Student|int|null $student, Interview|int|null $interview = null): ?ApplicationInterview
    {
        if (!Schema::hasTable('application_interviews')) return null;
        if (is_int($student)) $student = Student::query()->find($student);
        if (!$student) return null;
        if (is_int($interview)) $interview = Interview::query()->find($interview);
        if (!$interview) {
            $interview = Interview::query()->where('student_id', $student->id)->latest('scheduled_at')->first();
        }
        if (!$interview) return null;

        $application = self::syncStudent($student);
        if (!$application) return null;
        $creatorAccount = self::ensureLegacyAdminAccount($interview->created_by_admin_id ? (int) $interview->created_by_admin_id : null);

        return ApplicationInterview::query()->updateOrCreate(
            ['application_id' => $application->id, 'scheduled_at' => $interview->scheduled_at],
            [
                'interview_type' => 'interview',
                'location' => $interview->location,
                'meeting_link' => $interview->meeting_link,
                'attendance_status' => $interview->attendance_status,
                'notes' => $interview->notes,
                'created_by_account_id' => $creatorAccount?->id,
                'updated_at' => now(),
            ]
        );
    }

    public static function syncResult(StudentApplication $application, Student $student, ?int $publisherAccountId = null): ?ApplicationResult
    {
        if (!Schema::hasTable('application_results')) return null;

        $status = StudentWorkflow::normalizeStatus($student->status);
        if (!in_array($status, ['accepted', 'rejected', 'completed'], true)) {
            return ApplicationResult::query()->firstOrCreate(
                ['application_id' => $application->id],
                ['result_status' => 'waiting_list']
            );
        }

        $title = match ($status) {
            'accepted' => 'Pengumuman Kelulusan PPDB',
            'completed' => 'PPDB Selesai',
            default => 'Hasil Seleksi PPDB',
        };
        $body = $student->result_note ?: ($status === 'rejected' ? ($student->rejection_reason ?: 'Mohon maaf, Anda belum dinyatakan lolos.') : 'Selamat, Anda dinyatakan lolos ke tahap berikutnya.');

        return ApplicationResult::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'result_status' => $status === 'completed' ? 'accepted' : $status,
                'announcement_title' => $title,
                'announcement_body' => $body,
                'published_at' => $student->announcement_published_at ?: now(),
                'published_by_account_id' => $publisherAccountId,
                'updated_at' => now(),
            ]
        );
    }

    public static function syncNews(News|int|null $news, ?int $adminId = null): ?NewsPost
    {
        if (!Schema::hasTable('news_posts')) return null;
        if (is_int($news)) $news = News::query()->find($news);
        if (!$news) return null;

        $title = trim((string) ($news->title ?: $news->news_title));
        $content = (string) ($news->content ?: $news->news_content);
        $imagePath = trim((string) ($news->image_path ?: $news->news_image_path));
        $slugBase = Str::slug($title ?: ('news-'.$news->id));
        $slug = self::uniqueNewsSlug($slugBase, $news->id);
        $account = $adminId ? self::ensureLegacyAdminAccount($adminId) : null;

        $mediaId = $imagePath !== '' ? self::ensureMediaAsset($imagePath, $title, $account?->id)?->id : null;
        $categoryId = NewsCategory::query()->where('slug', 'umum')->value('id');

        return NewsPost::query()->withTrashed()->updateOrCreate(
            ['legacy_news_id' => $news->id],
            [
                'category_id' => $categoryId,
                'slug' => $slug,
                'title' => $title,
                'excerpt' => Str::limit(strip_tags($content), 180),
                'content' => $content,
                'featured_image_id' => $mediaId,
                'status' => ((int) ($news->is_published ?? 0)) === 1 ? 'published' : 'draft',
                'published_at' => ((int) ($news->is_published ?? 0)) === 1 ? ($news->created_at ?: now()) : null,
                'published_by_account_id' => $account?->id,
                'created_by_account_id' => $account?->id,
                'updated_by_account_id' => $account?->id,
                'created_at' => $news->created_at ?: now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );
    }

    public static function deleteNews(?int $legacyNewsId): void
    {
        if (!Schema::hasTable('news_posts') || !$legacyNewsId) return;
        $post = NewsPost::query()->where('legacy_news_id', $legacyNewsId)->first();
        if ($post) $post->delete();
    }

    public static function ensureMediaAsset(string $filePath, ?string $altText = null, ?int $accountId = null, ?string $collection = null, ?string $originalName = null, ?string $mimeType = null, ?int $fileSize = null): ?MediaAsset
    {
        if (!Schema::hasTable('media_assets') || trim($filePath) === '') return null;

        $asset = MediaAsset::query()->firstOrNew(['file_path' => $filePath]);
        if (!$asset->exists) {
            $asset->disk = 'public';
            $asset->original_name = $originalName ?: basename($filePath);
            $asset->mime_type = $mimeType;
            $asset->file_size = $fileSize;
            $asset->alt_text = $altText;
            $asset->uploaded_by_account_id = $accountId;
            if (Schema::hasColumn('media_assets', 'collection')) {
                $asset->collection = $collection;
            }
            if (Schema::hasColumn('media_assets', 'is_public')) {
                $asset->is_public = true;
            }
            $asset->save();
            return $asset;
        }

        $dirty = false;
        if ($altText && !$asset->alt_text) { $asset->alt_text = $altText; $dirty = true; }
        if ($accountId && !$asset->uploaded_by_account_id) { $asset->uploaded_by_account_id = $accountId; $dirty = true; }
        if ($collection && Schema::hasColumn('media_assets', 'collection') && !$asset->collection) { $asset->collection = $collection; $dirty = true; }
        if ($mimeType && !$asset->mime_type) { $asset->mime_type = $mimeType; $dirty = true; }
        if ($fileSize && !$asset->file_size) { $asset->file_size = $fileSize; $dirty = true; }
        if ($dirty) $asset->save();

        return $asset;
    }

    public static function syncGallery(LegacyGalleryItem|int|null $gallery, ?int $adminId = null): ?GalleryItem
    {
        if (!Schema::hasTable('gallery_items')) return null;
        if (is_int($gallery)) $gallery = LegacyGalleryItem::query()->find($gallery);
        if (!$gallery) return null;

        $account = $adminId ? self::ensureLegacyAdminAccount($adminId) : null;
        $media = $gallery->image_path ? self::ensureMediaAsset(
            (string) $gallery->image_path,
            $gallery->title,
            $account?->id,
            'gallery'
        ) : null;

        return GalleryItem::query()->withTrashed()->updateOrCreate(
            ['legacy_gallery_id' => $gallery->id],
            [
                'title' => $gallery->title,
                'description' => $gallery->description,
                'image_asset_id' => $media?->id,
                'is_published' => (bool) $gallery->is_published,
                'sort_order' => 0,
                'published_at' => ((bool) $gallery->is_published) ? ($gallery->uploaded_at ?: now()) : null,
                'created_by_account_id' => $account?->id,
                'updated_by_account_id' => $account?->id,
                'created_at' => $gallery->uploaded_at ?: now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );
    }

    public static function syncFacilityMedia(Facility|int|null $facility, ?int $adminId = null): ?Facility
    {
        if (!Schema::hasTable('facilities')) return null;
        if (is_int($facility)) $facility = Facility::query()->find($facility);
        if (!$facility) return null;

        $account = $adminId ? self::ensureLegacyAdminAccount($adminId) : null;
        if ($facility->modal_image_path && Schema::hasTable('media_assets')) {
            $asset = self::ensureMediaAsset((string) $facility->modal_image_path, $facility->name, $account?->id, 'facilities');
            if ($asset && !$facility->image_asset_id) {
                $facility->image_asset_id = $asset->id;
            }
        }
        if (!$facility->slug) {
            $facility->slug = Str::slug($facility->facility_key ?: $facility->name ?: ('facility-'.$facility->id));
        }
        if (!$facility->content) {
            $facility->content = $facility->modal_description ?: $facility->short_description;
        }
        if ($facility->is_published && !$facility->published_at && Schema::hasColumn('facilities', 'published_at')) {
            $facility->published_at = $facility->created_at ?: now();
        }
        if ($account) {
            if (!$facility->created_by_account_id) $facility->created_by_account_id = $account->id;
            $facility->updated_by_account_id = $account->id;
        }
        $facility->save();
        return $facility;
    }


    public static function mirrorApplicationToLegacy(StudentApplication|int|null $application): ?Student
    {
        if (!Schema::hasTable('students')) return null;
        if (is_int($application)) {
            $application = StudentApplication::query()->with(['account', 'profile', 'guardians', 'documents', 'interview'])->find($application);
        }
        if (!$application) return null;

        $legacyUserId = $application->account?->legacy_user_id;
        if (!$legacyUserId) {
            return null;
        }

        return DB::transaction(function () use ($application, $legacyUserId) {
            $student = null;
            if ($application->legacy_student_id) {
                $student = Student::query()->find($application->legacy_student_id);
            }
            if (!$student) {
                $student = Student::query()->where('user_id', $legacyUserId)->orderByDesc('id')->first();
            }
            if (!$student) {
                $student = new Student();
                $student->user_id = $legacyUserId;
                $student->created_at = $application->created_at ?: now();
            }

            $profile = $application->profile;
            $father = $application->guardians->firstWhere('guardian_type', 'father');
            $mother = $application->guardians->firstWhere('guardian_type', 'mother');
            $guardian = $application->guardians->firstWhere('guardian_type', 'guardian');

            $student->registration_number = $application->registration_no;
            $student->status = $application->current_status;
            $student->submitted_at = $application->submitted_at;
            $student->reviewed_at = $application->reviewed_at;
            $student->rejection_reason = $application->rejection_reason;
            $student->result_note = $application->result_notes;
            $student->announcement_published_at = $application->announcement_published_at;
            $student->last_status_note = $application->last_status_note;

            $reviewer = $application->reviewed_by_account_id ? Account::query()->find($application->reviewed_by_account_id) : null;
            if ($reviewer?->legacy_admin_id) {
                $student->reviewed_by_admin_id = $reviewer->legacy_admin_id;
            }

            if ($profile) {
                $student->name = $profile->full_name;
                $student->gender = $profile->gender;
                $student->birthplace = $profile->birth_place;
                $student->dob = $profile->birth_date;
                $student->religion_child = $profile->religion;
                $student->phone = $profile->phone;
                $student->address = $profile->address;
                $student->student_hobby = $profile->hobby;
                $student->goal = $profile->goal;
                $student->motivation = $profile->motivation;
            }

            foreach ([['father', $father], ['mother', $mother], ['guardian', $guardian]] as [$type, $item]) {
                if (!$item) continue;
                $prefix = $type . '_';
                $student->{$prefix.'name'} = $item->full_name;
                $student->{$prefix.'phone'} = $item->phone;
                $student->{$prefix.'email'} = $item->email;
                if (in_array($type, ['father', 'mother'], true)) {
                    $student->{$prefix.'job'} = $item->occupation;
                }
                $student->{$prefix.'income'} = $item->income_range;
                $student->{$prefix.'birthplace'} = $item->birth_place;
                $student->{$prefix.'dob'} = $item->birth_date;
                $student->{$prefix.'religion'} = $item->religion;
                if ($type === 'guardian') {
                    $student->guardian_relation = $item->relationship_to_student;
                }
            }

            $docColumnMap = [
                'kk' => 'kk_file',
                'akta' => 'akta_lahir',
                'rapor' => 'nilai_rapor',
                'ktp_father' => 'ktp_father',
                'ktp_mother' => 'ktp_mother',
                'ktp_guardian' => 'ktp_guardian',
                'kk_guardian' => 'kk_guardian',
            ];
            foreach ($application->documents as $doc) {
                $column = $docColumnMap[$doc->document_type] ?? null;
                if ($column) {
                    $student->{$column} = $doc->file_path;
                }
            }

            $student->save();

            if ((int) ($application->legacy_student_id ?: 0) !== (int) $student->id) {
                $application->legacy_student_id = $student->id;
                $application->save();
            }

            foreach ($application->documents as $doc) {
                if (!in_array($doc->document_type, ['kk', 'akta', 'rapor'], true)) continue;
                Document::query()->updateOrCreate(
                    ['student_id' => $student->id, 'document_type' => $doc->document_type],
                    ['file_path' => $doc->file_path, 'uploaded_at' => $doc->created_at ?: now()]
                );
            }

            if ($application->interview) {
                $creator = $application->interview->created_by_account_id ? Account::query()->find($application->interview->created_by_account_id) : null;
                Interview::query()->updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'scheduled_at' => $application->interview->scheduled_at,
                        'location' => $application->interview->location,
                        'meeting_link' => $application->interview->meeting_link,
                        'notes' => $application->interview->notes,
                        'attendance_status' => $application->interview->attendance_status,
                        'created_by_admin_id' => $creator?->legacy_admin_id,
                        'updated_at' => now(),
                    ]
                );
            }

            return $student;
        });
    }

    public static function mirrorNewsPostToLegacy(NewsPost|int|null $post): ?News
    {
        if (!Schema::hasTable('news')) return null;
        if (is_int($post)) {
            $post = NewsPost::query()->with('featuredImage')->find($post);
        }
        if (!$post) return null;

        return DB::transaction(function () use ($post) {
            $legacy = null;
            if ($post->legacy_news_id) {
                $legacy = News::query()->find($post->legacy_news_id);
            }
            if (!$legacy) {
                $legacy = new News();
            }

            $image = $post->featuredImage?->file_path;
            $legacy->title = $post->title;
            $legacy->content = $post->content;
            $legacy->image_path = $image;
            $legacy->news_title = $post->title;
            $legacy->news_content = $post->content;
            $legacy->news_image_path = $image;
            $legacy->is_published = $post->status === 'published';
            $legacy->created_at = $legacy->exists ? $legacy->created_at : ($post->published_at ?: now());
            $legacy->updated_at = now();
            $legacy->save();

            if ((int) ($post->legacy_news_id ?: 0) !== (int) $legacy->id) {
                $post->legacy_news_id = $legacy->id;
                $post->save();
            }

            return $legacy;
        });
    }

    private static function academicYearFrom($createdAt): string
    {
        $year = $createdAt ? (int) date('Y', strtotime((string) $createdAt)) : (int) now()->format('Y');
        return $year . '/' . ($year + 1);
    }

    private static function uniqueNewsSlug(string $base, int $legacyNewsId): string
    {
        $base = $base ?: ('news-' . $legacyNewsId);
        $slug = $base;
        $i = 2;
        while (NewsPost::query()->where('slug', $slug)->where('legacy_news_id', '!=', $legacyNewsId)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
