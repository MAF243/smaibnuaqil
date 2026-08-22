<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class Cutover
{
    public static function ppdbReadFromV3(): bool
    {
        return (bool) config('cutover.ppdb.read_from_v3', true)
            && Schema::hasTable('student_applications')
            && Schema::hasTable('accounts');
    }

    public static function ppdbWriteToV3(): bool
    {
        return (bool) config('cutover.ppdb.write_to_v3', true)
            && Schema::hasTable('student_applications')
            && Schema::hasTable('accounts');
    }

    public static function ppdbMirrorLegacy(): bool
    {
        return (bool) config('cutover.ppdb.mirror_legacy', false) && Schema::hasTable('students');
    }

    public static function cmsReadFromV3(): bool
    {
        return (bool) config('cutover.cms.read_from_v3', true) && Schema::hasTable('news_posts');
    }

    public static function cmsWriteToV3(): bool
    {
        return (bool) config('cutover.cms.write_to_v3', true) && Schema::hasTable('news_posts');
    }

    public static function cmsMirrorLegacy(): bool
    {
        return (bool) config('cutover.cms.mirror_legacy', false) && Schema::hasTable('news');
    }

    public static function auditPrimaryV3(): bool
    {
        return (bool) config('cutover.audit.primary_v3', true) && Schema::hasTable('audit_logs');
    }

    public static function auditMirrorLegacy(): bool
    {
        return (bool) config('cutover.audit.mirror_legacy', false) && Schema::hasTable('admin_activity_logs');
    }
}

