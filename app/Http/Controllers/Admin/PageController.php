<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        $items = Page::orderBy('title')->get();
        return view('admin.pages.index', ['items' => $items]);
    }

    public function edit(int $id)
    {
        $item = Page::findOrFail($id);
        return view('admin.pages.form', ['item' => $item]);
    }

    public function update(Request $request, int $id)
    {
        $item = Page::findOrFail($id);
        $data = $request->validate([
            'title' => ['required','string','max:150'],
            'excerpt' => ['nullable','string'],
            'content' => ['nullable','string'],
            'is_published' => ['nullable'],
        ]);

        $item->fill($data);
        $item->is_published = (bool) $request->boolean('is_published');
        $item->status = $item->is_published ? 'published' : 'draft';
        $item->slug = $item->slug ?: Str::slug($item->page_key ?: $item->title);
        $item->published_at = $item->is_published ? ($item->published_at ?: now()) : null;
        $item->updated_at = now();
        $item->save();

        AdminActivity::log((int) session('admin_id'), 'page.updated', Page::class, $item->id, 'Memperbarui halaman ' . $item->title, ['page_key' => $item->page_key]);

        return redirect()->route('admin.pages.index')->with('ok', 'Halaman berhasil diperbarui.');
    }
}
