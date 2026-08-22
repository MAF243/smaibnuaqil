<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;

class GalleryController extends Controller
{
    public function index()
    {
        $items = GalleryItem::query()
            ->where('is_published', true)
            ->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('created_at')
            ->paginate(18);

        return view('site.gallery.index', ['items' => $items]);
    }
}
