<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\GalleryItem;
use App\Models\MediaAsset;
use App\Models\NewsPost;
use App\Support\AdminActivity;
use App\Support\V3Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    private function adminAccountId(): ?int
    {
        $adminId = (int) session('admin_id');
        return $adminId ? V3Sync::ensureLegacyAdminAccount($adminId)?->id : null;
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $collection = trim((string) $request->query('collection', ''));

        $query = MediaAsset::query()->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('original_name', 'like', "%{$q}%")
                    ->orWhere('file_path', 'like', "%{$q}%")
                    ->orWhere('alt_text', 'like', "%{$q}%");
            });
        }
        if ($collection !== '') {
            $query->where('collection', $collection);
        }

        $items = $query->paginate(24)->withQueryString();
        $collections = MediaAsset::query()->select('collection')->whereNotNull('collection')->distinct()->orderBy('collection')->pluck('collection');

        return view('admin.media.index', compact('items', 'q', 'collection', 'collections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'collection' => ['nullable','string','max:60'],
            'alt_text' => ['nullable','string','max:255'],
            'files' => ['required'],
            'files.*' => ['file','max:8192','mimes:jpg,jpeg,png,webp,gif,pdf'],
        ]);

        $collection = trim((string) ($data['collection'] ?? 'media')) ?: 'media';
        $dir = public_path('uploads/media/' . $collection);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $created = 0;
        foreach ((array) $request->file('files', []) as $file) {
            if (!$file || !$file->isValid()) continue;
            $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $safeBase = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $name = 'media_' . now()->format('Ymd_His') . '_' . Str::random(8) . '_' . ($safeBase ?: 'file') . '.' . $ext;
            $file->move($dir, $name);
            $path = 'uploads/media/' . $collection . '/' . $name;

            V3Sync::ensureMediaAsset(
                $path,
                $data['alt_text'] ?? null,
                $this->adminAccountId(),
                $collection,
                $file->getClientOriginalName(),
                $file->getMimeType(),
                (int) $file->getSize()
            );
            $created++;
        }

        AdminActivity::log((int) session('admin_id'), 'media.uploaded', MediaAsset::class, null, 'Upload media v3', ['count' => $created, 'collection' => $collection]);

        return redirect()->route('admin.media.index')->with('ok', $created . ' file media berhasil diupload.');
    }

    public function destroy(int $id)
    {
        $item = MediaAsset::query()->findOrFail($id);

        $usageCount = 0;
        try { $usageCount += NewsPost::query()->where('featured_image_id', $item->id)->count(); } catch (\Throwable $e) {}
        try { $usageCount += GalleryItem::query()->where('image_asset_id', $item->id)->count(); } catch (\Throwable $e) {}
        try { $usageCount += Facility::query()->where('image_asset_id', $item->id)->count(); } catch (\Throwable $e) {}

        if ($usageCount > 0) {
            return redirect()->route('admin.media.index')->with('error', 'Media tidak bisa dihapus karena masih dipakai oleh konten website. Lepaskan media dari konten terkait terlebih dahulu.');
        }

        AdminActivity::log((int) session('admin_id'), 'media.deleted', MediaAsset::class, $item->id, 'Menghapus media v3', ['file_path' => $item->file_path]);

        $path = public_path($item->file_path);
        if ($item->file_path && is_file($path)) {
            @unlink($path);
        }
        $item->delete();

        return redirect()->route('admin.media.index')->with('ok', 'Media berhasil dihapus.');
    }
}
