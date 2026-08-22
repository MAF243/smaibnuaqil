<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PortalController extends Controller
{
    public function index()
    {
        $faq = class_exists(Page::class)
            ? Page::where('page_key', 'faq_ppdb')->where('is_published', true)->first()
            : null;

        return view('portal', ['faq' => $faq]);
    }
}
