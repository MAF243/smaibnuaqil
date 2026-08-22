<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Support\AdminActivity;
use App\Support\Cutover;
use App\Support\V3Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    private function storeImage(Request $request, ?string $currentPath = null): ?string
    {
        if (!$request->hasFile('image')) {
            return $currentPath;
        }

        $file = $request->file('image');
        if (!$file || !$file->isValid()) {
            return $currentPath;
        }

        $dir = public_path('uploads/news');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $safeBase = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = 'news_' . now()->format('Ymd_His') . '_' . Str::random(6) . '_' . ($safeBase ?: 'image') . '.' . $ext;

        $file->move($dir, $name);
        $newPath = 'uploads/news/' . $name;

        if ($currentPath && str_starts_with($currentPath, 'uploads/news/')) {
            $old = public_path($currentPath);
            if (is_file($old)) {
                @unlink($old);
            }
        }

        return $newPath;
    }

    private function categoryId(): ?int
    {
        return NewsCategory::query()->where('slug', 'umum')->value('id');
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (Cutover::cmsReadFromV3()) {
            $query = NewsPost::query()->with('featuredImage')->orderByDesc('published_at')->orderByDesc('created_at');
            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%");
                });
            }
            $items = $query->paginate(15)->withQueryString();
            return view('admin.news.index', ['items' => $items, 'q' => $q]);
        }

        $query = News::query()->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('news_title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%")
                    ->orWhere('news_content', 'like', "%{$q}%");
            });
        }

        $items = $query->paginate(15)->withQueryString();

        return view('admin.news.index', ['items' => $items, 'q' => $q]);
    }

    public function create()
    {
        if (Cutover::cmsWriteToV3()) {
            $item = new NewsPost();
            $item->status = 'published';
            return view('admin.news.form', ['item' => $item, 'mode' => 'create']);
        }

        return view('admin.news.form', ['item' => new News(), 'mode' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'content' => ['required','string'],
            'is_published' => ['nullable'],
            'image' => ['nullable','image','max:4096'],
        ]);

        if (Cutover::cmsWriteToV3()) {
            $path = $this->storeImage($request, null);
            $account = V3Sync::ensureLegacyAdminAccount((int) session('admin_id'));
            $mediaId = $path ? V3Sync::ensureMediaAsset($path, $data['title'], $account?->id)?->id : null;

            $post = new NewsPost();
            $post->category_id = $this->categoryId();
            $post->slug = Str::slug($data['title']) ?: ('news-' . now()->timestamp);
            $post->title = $data['title'];
            $post->excerpt = Str::limit(strip_tags($data['content']), 180);
            $post->content = $data['content'];
            $post->featured_image_id = $mediaId;
            $post->status = $request->boolean('is_published', true) ? 'published' : 'draft';
            $post->published_at = $post->status === 'published' ? now() : null;
            $post->published_by_account_id = $account?->id;
            $post->created_by_account_id = $account?->id;
            $post->updated_by_account_id = $account?->id;
            $post->save();

            if (Cutover::cmsMirrorLegacy()) {
                V3Sync::mirrorNewsPostToLegacy($post);
            }

            AdminActivity::log((int) session('admin_id'), 'news.created', NewsPost::class, $post->id, 'Menambah berita baru', ['title' => $post->title]);
            return redirect()->route('admin.news.index')->with('ok', 'Berita berhasil ditambahkan.');
        }

        $news = new News();
        $news->title = $data['title'];
        $news->content = $data['content'];
        $news->is_published = (bool) ($request->input('is_published', true));
        $news->image_path = $this->storeImage($request, null);
        $news->news_title = $news->title;
        $news->news_content = $news->content;
        $news->news_image_path = $news->image_path;
        $news->save();
        V3Sync::syncNews($news, (int) session('admin_id'));
        AdminActivity::log((int) session('admin_id'), 'news.created', News::class, $news->id, 'Menambah berita baru', ['title' => $news->title]);

        return redirect()->route('admin.news.index')->with('ok', 'Berita berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        if (Cutover::cmsReadFromV3()) {
            $item = NewsPost::query()->with('featuredImage')->findOrFail($id);
            return view('admin.news.form', ['item' => $item, 'mode' => 'edit']);
        }

        $item = News::query()->findOrFail($id);
        return view('admin.news.form', ['item' => $item, 'mode' => 'edit']);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'content' => ['required','string'],
            'is_published' => ['nullable'],
            'image' => ['nullable','image','max:4096'],
            'remove_image' => ['nullable'],
        ]);

        if (Cutover::cmsWriteToV3()) {
            $item = NewsPost::query()->with('featuredImage')->findOrFail($id);
            $currentPath = $item->featuredImage?->file_path;
            if ($request->boolean('remove_image') && $currentPath && str_starts_with($currentPath, 'uploads/news/')) {
                $old = public_path($currentPath);
                if (is_file($old)) {
                    @unlink($old);
                }
                $currentPath = null;
                $item->featured_image_id = null;
            }
            $path = $this->storeImage($request, $currentPath);
            $account = V3Sync::ensureLegacyAdminAccount((int) session('admin_id'));
            if ($path) {
                $item->featured_image_id = V3Sync::ensureMediaAsset($path, $data['title'], $account?->id)?->id;
            }

            $item->title = $data['title'];
            $item->excerpt = Str::limit(strip_tags($data['content']), 180);
            $item->content = $data['content'];
            $item->status = (bool) $request->boolean('is_published', false) ? 'published' : 'draft';
            $item->published_at = $item->status === 'published' ? ($item->published_at ?: now()) : null;
            $item->updated_by_account_id = $account?->id;
            $item->save();

            if (Cutover::cmsMirrorLegacy()) {
                V3Sync::mirrorNewsPostToLegacy($item);
            }

            AdminActivity::log((int) session('admin_id'), 'news.updated', NewsPost::class, $item->id, 'Memperbarui berita', ['title' => $item->title]);
            return redirect()->route('admin.news.index')->with('ok', 'Berita berhasil diperbarui.');
        }

        $item = News::query()->findOrFail($id);
        $item->title = $data['title'];
        $item->content = $data['content'];
        $item->is_published = (bool) ($request->input('is_published', false));

        if ($request->boolean('remove_image')) {
            if ($item->image_path && str_starts_with($item->image_path, 'uploads/news/')) {
                $old = public_path($item->image_path);
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            $item->image_path = null;
        }

        $item->image_path = $this->storeImage($request, $item->image_path);
        $item->news_title = $item->title;
        $item->news_content = $item->content;
        $item->news_image_path = $item->image_path;
        $item->save();
        V3Sync::syncNews($item, (int) session('admin_id'));
        AdminActivity::log((int) session('admin_id'), 'news.updated', News::class, $item->id, 'Memperbarui berita', ['title' => $item->title]);

        return redirect()->route('admin.news.index')->with('ok', 'Berita berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        if (Cutover::cmsWriteToV3()) {
            $item = NewsPost::query()->with('featuredImage')->findOrFail($id);
            $path = $item->featuredImage?->file_path;
            if ($path && str_starts_with($path, 'uploads/news/')) {
                $old = public_path($path);
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            if (Cutover::cmsMirrorLegacy()) {
                $legacy = $item->legacy_news_id;
                $item->delete();
                if ($legacy) {
                    $legacyRow = News::query()->find($legacy);
                    if ($legacyRow) {
                        $legacyRow->delete();
                    }
                }
            } else {
                $item->delete();
            }
            AdminActivity::log((int) session('admin_id'), 'news.deleted', NewsPost::class, $item->id, 'Menghapus berita', ['title' => $item->title]);
            return redirect()->route('admin.news.index')->with('ok', 'Berita berhasil dihapus.');
        }

        $item = News::query()->findOrFail($id);
        if ($item->image_path && str_starts_with($item->image_path, 'uploads/news/')) {
            $old = public_path($item->image_path);
            if (is_file($old)) {
                @unlink($old);
            }
        }

        V3Sync::deleteNews($item->id);
        AdminActivity::log((int) session('admin_id'), 'news.deleted', News::class, $item->id, 'Menghapus berita', ['title' => $item->title]);
        $item->delete();

        return redirect()->route('admin.news.index')->with('ok', 'Berita berhasil dihapus.');
    }
}
