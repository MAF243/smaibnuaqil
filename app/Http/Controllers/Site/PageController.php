<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function show(string $pageKey)
    {
        $page = Page::where('page_key', $pageKey)
            ->where(function ($q) {
                $q->where('is_published', true)
                  ->orWhere('status', 'published');
            })
            ->firstOrFail();
        return view('site.pages.show', ['page' => $page]);
    }
}
