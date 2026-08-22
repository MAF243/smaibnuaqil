<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (Schema::hasTable('news_posts') && NewsPost::query()->count() > 0) {
            $query = NewsPost::query()->where('status', 'published')->orderByDesc('published_at')->orderByDesc('created_at');
            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%");
                });
            }
            $items = $query->paginate(9)->withQueryString();
            return view('site.news.index', ['items' => $items, 'q' => $q]);
        }

        $query = News::query()->where('is_published', true)->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('news_title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%")
                    ->orWhere('news_content', 'like', "%{$q}%");
            });
        }

        $items = $query->paginate(9)->withQueryString();

        return view('site.news.index', ['items' => $items, 'q' => $q]);
    }

    public function show(int $id)
    {
        if (Schema::hasTable('news_posts') && NewsPost::query()->count() > 0) {
            $news = NewsPost::query()->where('status', 'published')->findOrFail($id);
            return view('site.news.show', ['news' => $news]);
        }

        $news = News::query()->where('is_published', true)->findOrFail($id);
        return view('site.news.show', ['news' => $news]);
    }
}
