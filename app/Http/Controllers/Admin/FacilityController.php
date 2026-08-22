<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\MediaAsset;
use App\Support\AdminActivity;
use App\Support\V3Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FacilityController extends Controller
{
    private function adminAccountId(): ?int
    {
        $adminId = (int) session('admin_id');
        return $adminId ? V3Sync::ensureLegacyAdminAccount($adminId)?->id : null;
    }

    private function storeMedia(Request $request, ?string $altText = null): ?MediaAsset
    {
        if (!$request->hasFile('modal_image')) {
            return null;
        }

        $file = $request->file('modal_image');
        if (!$file || !$file->isValid()) {
            return null;
        }

        $dir = public_path('uploads/facilities');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $safeBase = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = 'facility_' . now()->format('Ymd_His') . '_' . Str::random(8) . '_' . ($safeBase ?: 'image') . '.' . $ext;
        $file->move($dir, $name);
        $path = 'uploads/facilities/' . $name;

        return V3Sync::ensureMediaAsset(
            $path,
            $altText,
            $this->adminAccountId(),
            'facilities',
            $file->getClientOriginalName(),
            $file->getMimeType(),
            (int) $file->getSize()
        );
    }

    public function index(Request $request)
    {
        $items = Facility::query()->with('imageAsset')->orderBy('display_order')->orderBy('id')->paginate(20);
        return view('admin.facilities.index', ['items' => $items]);
    }

    public function create()
    {
        $item = new Facility();
        $item->is_published = true;
        $item->display_order = 0;
        return view('admin.facilities.form', ['item' => $item, 'mode' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'short_description' => ['nullable','string','max:500'],
            'content' => ['nullable','string'],
            'icon_class' => ['nullable','string','max:100'],
            'facility_key' => ['nullable','string','max:100'],
            'modal_title' => ['nullable','string','max:255'],
            'modal_description' => ['nullable','string'],
            'is_published' => ['nullable'],
            'display_order' => ['nullable','integer','min:0','max:9999'],
            'modal_image' => ['nullable','image','max:4096'],
        ]);

        $key = trim((string) ($data['facility_key'] ?? '')) ?: Str::slug($data['name']);
        $media = $this->storeMedia($request, $data['name']);
        $isPublished = (bool) $request->input('is_published', true);

        $item = Facility::create([
            'name' => $data['name'],
            'slug' => Str::slug($key),
            'short_description' => $data['short_description'] ?? null,
            'content' => $data['content'] ?? ($data['modal_description'] ?? null),
            'icon_class' => $data['icon_class'] ?? 'bi bi-building',
            'facility_key' => $key,
            'modal_title' => $data['modal_title'] ?? $data['name'],
            'modal_description' => $data['modal_description'] ?? ($data['content'] ?? null),
            'modal_image_path' => $media?->file_path,
            'image_asset_id' => $media?->id,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
            'display_order' => (int) ($data['display_order'] ?? 0),
            'created_by_account_id' => $this->adminAccountId(),
            'updated_by_account_id' => $this->adminAccountId(),
        ]);

        AdminActivity::log((int) session('admin_id'), 'facility.created', Facility::class, $item->id, 'Menambah fasilitas v3', ['name' => $item->name]);

        return redirect()->route('admin.facilities.index')->with('ok', 'Fasilitas berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $item = Facility::query()->with('imageAsset')->findOrFail($id);
        return view('admin.facilities.form', ['item' => $item, 'mode' => 'edit']);
    }

    public function update(Request $request, int $id)
    {
        $item = Facility::query()->with('imageAsset')->findOrFail($id);

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'short_description' => ['nullable','string','max:500'],
            'content' => ['nullable','string'],
            'icon_class' => ['nullable','string','max:100'],
            'facility_key' => ['nullable','string','max:100'],
            'modal_title' => ['nullable','string','max:255'],
            'modal_description' => ['nullable','string'],
            'is_published' => ['nullable'],
            'display_order' => ['nullable','integer','min:0','max:9999'],
            'modal_image' => ['nullable','image','max:4096'],
            'remove_image' => ['nullable'],
        ]);

        $key = trim((string) ($data['facility_key'] ?? '')) ?: ($item->facility_key ?: Str::slug($data['name']));
        $isPublished = (bool) $request->input('is_published', false);

        $item->name = $data['name'];
        $item->slug = Str::slug($key);
        $item->short_description = $data['short_description'] ?? null;
        $item->content = $data['content'] ?? ($data['modal_description'] ?? null);
        $item->icon_class = $data['icon_class'] ?? $item->icon_class;
        $item->facility_key = $key;
        $item->modal_title = $data['modal_title'] ?? $item->name;
        $item->modal_description = $data['modal_description'] ?? ($item->content ?? null);
        $item->is_published = $isPublished;
        $item->published_at = $isPublished ? ($item->published_at ?: now()) : null;
        $item->display_order = (int) ($data['display_order'] ?? $item->display_order);
        $item->updated_by_account_id = $this->adminAccountId();

        if ($request->boolean('remove_image')) {
            $item->image_asset_id = null;
            $item->modal_image_path = null;
        }

        if ($media = $this->storeMedia($request, $item->name)) {
            $item->image_asset_id = $media->id;
            $item->modal_image_path = $media->file_path;
        }

        $item->save();
        AdminActivity::log((int) session('admin_id'), 'facility.updated', Facility::class, $item->id, 'Memperbarui fasilitas v3', ['name' => $item->name]);

        return redirect()->route('admin.facilities.index')->with('ok', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $item = Facility::query()->findOrFail($id);
        AdminActivity::log((int) session('admin_id'), 'facility.deleted', Facility::class, $item->id, 'Menghapus fasilitas v3', ['name' => $item->name]);
        $item->delete();

        return redirect()->route('admin.facilities.index')->with('ok', 'Fasilitas berhasil dihapus.');
    }
}
