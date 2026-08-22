<?php

namespace App\Support;

use App\Models\DashboardNotification;
use App\Models\Notification;
use Illuminate\Support\Facades\Schema;

class Notifier
{
    public static function user(int $userId, string $title, ?string $message = null, string $type = 'info', ?string $link = null): void
    {
        self::push('user', $userId, $title, $message, $type, $link);
    }

    public static function admin(int $adminId, string $title, ?string $message = null, string $type = 'info', ?string $link = null): void
    {
        self::push('admin', $adminId, $title, $message, $type, $link);
    }

    public static function admins(string $title, ?string $message = null, string $type = 'info', ?string $link = null): void
    {
        try {
            $ids = \App\Models\Admin::query()->pluck('id');
            foreach ($ids as $id) {
                self::push('admin', (int) $id, $title, $message, $type, $link);
            }
        } catch (\Throwable $e) {
            // ignore optional notifications layer issues
        }
    }

    private static function push(string $recipientType, int $recipientId, string $title, ?string $message, string $type, ?string $link): void
    {
        try {
            DashboardNotification::create([
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'is_read' => false,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore optional notifications layer issues
        }

        try {
            if (!Schema::hasTable('notifications')) {
                return;
            }

            $account = $recipientType === 'admin'
                ? V3Sync::accountForLegacyAdminId($recipientId)
                : V3Sync::accountForLegacyUserId($recipientId);

            if (!$account) {
                $account = $recipientType === 'admin'
                    ? V3Sync::ensureLegacyAdminAccount($recipientId)
                    : V3Sync::ensureLegacyUserAccount($recipientId);
            }

            if (!$account) {
                return;
            }

            Notification::create([
                'account_id' => $account->id,
                'category' => $recipientType === 'admin' ? 'ppdb' : 'ppdb',
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'url' => $link,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore v3 notification sync failures
        }
    }
}
