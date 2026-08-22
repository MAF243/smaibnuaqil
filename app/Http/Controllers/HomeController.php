<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\GalleryItem;
use App\Models\HomepageBanner;
use App\Models\News;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    private function setting(string $key, ?string $default = null): ?string
    {
        $row = SiteSetting::query()->find($key);
        $val = $row?->setting_value;
        $val = is_string($val) ? trim($val) : null;
        return ($val !== null && $val !== '') ? $val : $default;
    }

    public function index()
    {
        $heroTitle = $this->setting('hero_title', "SMA Ibnu'Aqil");
        $heroSubtitle = $this->setting('hero_subtitle', 'Serba Bisa, Pasti Bisa SUKSES!');
        $heroImage = $this->setting('hero_image_path', 'asset/gedunghd.png');
        $mapIframeSrc = $this->setting('map_iframe_src', null);

        $news = (Schema::hasTable('news_posts') && NewsPost::query()->count() > 0)
            ? NewsPost::query()->where('status', 'published')->orderByDesc('published_at')->limit(3)->get()
            : News::query()->where('is_published', true)->orderByDesc('created_at')->limit(3)->get();
        $gallery = GalleryItem::query()->where('is_published', true)->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('created_at')->limit(8)->get();
        $facilities = Facility::query()->where('is_published', true)->orderBy('display_order')->orderBy('id')->get();

        $announcement = class_exists(Page::class)
            ? Page::query()->where('page_key', 'homepage_announcement')->where(function ($q) { $q->where('is_published', true)->orWhere('status', 'published'); })->first()
            : null;
        $banner = Schema::hasTable('homepage_banners')
            ? HomepageBanner::query()->where('is_active', true)->orderByDesc('id')->first()
            : null;

        return view('site.home', compact('heroTitle', 'heroSubtitle', 'heroImage', 'mapIframeSrc', 'news', 'gallery', 'facilities', 'announcement', 'banner'));
    }
}
