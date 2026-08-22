<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Interview;
use App\Models\LegacyGalleryItem;
use App\Models\LegacyUser;
use App\Models\Facility;
use App\Models\News;
use App\Models\Student;
use App\Support\V3Sync;
use Illuminate\Console\Command;

class V3BackfillAllCommand extends Command
{
    protected $signature = 'v3:backfill-all {--fresh-status-history : Tambahkan ulang status history dari snapshot status saat ini}';
    protected $description = 'Backfill data legacy ke schema v3 additive.';

    public function handle(): int
    {
        $this->info('Sinkron akun siswa...');
        LegacyUser::query()->orderBy('user_id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                V3Sync::ensureLegacyUserAccount($row);
            }
        });

        $this->info('Sinkron akun admin & role...');
        Admin::query()->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                V3Sync::ensureLegacyAdminAccount($row);
            }
        });

        $this->info('Sinkron aplikasi PPDB & dokumen...');
        Student::query()->with('documents')->orderBy('id')->chunk(50, function ($rows) {
            foreach ($rows as $row) {
                V3Sync::syncStudent($row);
            }
        });

        if ($this->option('fresh-status-history')) {
            $this->warn('Menambahkan status history berdasarkan snapshot status saat ini...');
            Student::query()->orderBy('id')->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    V3Sync::syncStudentStatus($row, null, 'Backfill snapshot status', 'system', null);
                }
            });
        }

        $this->info('Sinkron jadwal interview...');
        Interview::query()->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                V3Sync::syncInterview($row->student_id, $row);
            }
        });

        $this->info('Sinkron konten berita...');
        News::query()->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                V3Sync::syncNews($row);
            }
        });

        $this->info('Sinkron galeri v3...');
        if (class_exists(LegacyGalleryItem::class)) {
            LegacyGalleryItem::query()->orderBy('id')->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    V3Sync::syncGallery($row);
                }
            });
        }

        $this->info('Sinkron media fasilitas v3...');
        Facility::query()->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                V3Sync::syncFacilityMedia($row);
            }
        });

        $this->info('Backfill v3 selesai.');
        return self::SUCCESS;
    }
}
