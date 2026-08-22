<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

class AdminActivity
{
    public static function log(?int $adminId, string $action, ?string $subjectType = null, $subjectId = null, ?string $description = null, array $properties = []): void
    {
        $v3Written = false;

        if (Cutover::auditPrimaryV3()) {
            try {
                $accountId = $adminId ? V3Sync::ensureLegacyAdminAccount($adminId)?->id : null;
                AuditLog::create([
                    'actor_account_id' => $accountId,
                    'module' => self::moduleFromAction($action),
                    'action' => $action,
                    'target_type' => $subjectType,
                    'target_id' => $subjectId,
                    'summary' => $description,
                    'after_json' => $properties ? json_encode($properties, JSON_UNESCAPED_UNICODE) : null,
                    'ip_address' => Request::ip(),
                    'user_agent' => substr((string) Request::userAgent(), 0, 1000),
                    'created_at' => now(),
                ]);
                $v3Written = true;
            } catch (\Throwable $e) {
                $v3Written = false;
            }
        }

        // Stage 3: legacy activity log hanya mirror opsional. Jika v3 gagal total,
        // fallback legacy tetap dicoba supaya tidak kehilangan catatan aktivitas.
        if (Cutover::auditMirrorLegacy() || !$v3Written) {
            try {
                if (class_exists(AdminActivityLog::class) && Schema::hasTable('admin_activity_logs')) {
                    AdminActivityLog::create([
                        'admin_id' => $adminId,
                        'action' => $action,
                        'subject_type' => $subjectType,
                        'subject_id' => $subjectId,
                        'description' => $description,
                        'properties' => $properties ? json_encode($properties, JSON_UNESCAPED_UNICODE) : null,
                        'created_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                // silent fail for optional logging layer
            }
        }
    }

    private static function moduleFromAction(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'news.') => 'cms',
            str_starts_with($action, 'gallery.') => 'cms',
            str_starts_with($action, 'facility.') => 'cms',
            str_starts_with($action, 'media.') => 'cms',
            str_starts_with($action, 'student.') => 'ppdb',
            str_starts_with($action, 'admin.') => 'auth',
            default => 'system',
        };
    }
}
