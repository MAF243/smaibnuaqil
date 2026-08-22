<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Models\MediaAsset;
use App\Support\AdminActivity;
use App\Support\V3Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    private function adminAccountId(): ?int
    {
        $adminId = (int) session('admin_id');
        return $adminId ? V3Sync::ensureLegacyAdminAccount($adminId)?->id : null;
    }

    private function storeMedia(Request $request, string $field, ?string $altText = null): ?MediaAsset
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        if (!$file || !$file->isValid()) {
            return null;
        }

        $dir = public_path('uploads/gallery');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $safeBase = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = 'gallery_' . now()->format('Ymd_His') . '_' . Str::random(8) . '_' . ($safeBase ?: 'image') . '.' . $ext;

        $file->move($dir, $name);
        $path = 'uploads/gallery/' . $name;

        return V3Sync::ensureMediaAsset(
            $path,
            $altText,
            $this->adminAccountId(),
            'gallery',
            $file->getClientOriginalName(),
            $file->getMimeType(),
            (int) $file->getSize()
        );
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = GalleryItem::query()->with('imageAsset')->orderByDesc('published_at')->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $items = $query->paginate(20)->withQueryString();

        return view('admin.gallery.index', ['items' => $items, 'q' => $q]);
    }

    public function create()
    {
        $item = new GalleryItem();
        $item->is_published = true;
        return view('admin.gallery.form', ['item' => $item, 'mode' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable','string','max:255'],
            'description' => ['nullable','string'],
            'is_published' => ['nullable'],
            'sort_order' => ['nullable','integer','min:0','max:9999'],
            'image' => ['required','image','max:4096'],
        ]);

        $media = $this->storeMedia($request, 'image', $data['title'] ?? null);

        $item = GalleryItem::create([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'image_asset_id' => $media?->id,
            'is_published' => (bool) ($request->input('is_published', true)),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'published_at' => $request->boolean('is_published', true) ? now() : null,
            'created_by_account_id' => $this->adminAccountId(),
            'updated_by_account_id' => $this->adminAccountId(),
        ]);

        AdminActivity::log((int) session('admin_id'), 'gallery.created', GalleryItem::class, $item->id, 'Menambah galeri v3', ['title' => $item->title]);

        return redirect()->route('admin.gallery.index')->with('ok', 'Foto galeri berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $item = GalleryItem::query()->with('imageAsset')->findOrFail($id);
        return view('admin.gallery.form', ['item' => $item, 'mode' => 'edit']);
    }

    public function update(Request $request, int $id)
    {
        $item = GalleryItem::query()->with('imageAsset')->findOrFail($id);

        $data = $request->validate([
            'title' => ['nullable','string','max:255'],
            'description' => ['nullable','string'],
            'is_published' => ['nullable'],
            'sort_order' => ['nullable','integer','min:0','max:9999'],
            'image' => ['nullable','image','max:4096'],
            'remove_image' => ['nullable'],
        ]);

        $item->title = $data['title'] ?? null;
        $item->description = $data['description'] ?? null;
        $item->is_published = (bool) ($request->input('is_published', false));
        $item->sort_order = (int) ($data['sort_order'] ?? $item->sort_order ?? 0);
        $item->published_at = $item->is_published ? ($item->published_at ?: now()) : null;
        $item->updated_by_account_id = $this->adminAccountId();

        if ($request->boolean('remove_image')) {
            $item->image_asset_id = null;
        }

        if ($media = $this->storeMedia($request, 'image', $item->title)) {
            $item->image_asset_id = $media->id;
        }

        $item->save();
        AdminActivity::log((int) session('admin_id'), 'gallery.updated', GalleryItem::class, $item->id, 'Memperbarui galeri v3', ['title' => $item->title]);

        return redirect()->route('admin.gallery.index')->with('ok', 'Galeri berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $item = GalleryItem::query()->findOrFail($id);
        AdminActivity::log((int) session('admin_id'), 'gallery.deleted', GalleryItem::class, $item->id, 'Menghapus galeri v3', ['title' => $item->title]);
        $item->delete();

        return redirect()->route('admin.gallery.index')->with('ok', 'Foto galeri berhasil dihapus.');
    }
}
