<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Facility;

class FacilityController extends Controller
{
    public function index()
    {
        $items = Facility::query()
            ->where('is_published', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return view('site.facilities.index', ['items' => $items]);
    }
}
