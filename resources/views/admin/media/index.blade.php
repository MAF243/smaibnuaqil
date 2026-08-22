@extends('layouts.admin')
@php($pageTitle = 'Media Library')
@section('content')
<div class="admin-surface p-6 mb-6">
  <div class="mb-5"><div class="text-xl font-black tracking-tight text-white">Media Library</div><p class="mt-1 text-sm text-slate-400">Simpan gambar dan dokumen yang akan digunakan oleh berita, galeri, fasilitas, dan halaman website.</p></div>
  <form method="get" action="{{ route('admin.media.index') }}" class="grid gap-4 lg:grid-cols-[1fr_.7fr_auto]">
    <div><label class="mb-2 block text-sm font-bold text-slate-300">Cari media</label><input type="text" name="q" value="{{ $q }}" class="ui-input bg-slate-950 text-white border-slate-700" placeholder="Nama file, path, atau alt text"></div>
    <div><label class="mb-2 block text-sm font-bold text-slate-300">Koleksi</label><select name="collection" class="ui-select bg-slate-950 text-white border-slate-700"><option value="">Semua koleksi</option>@foreach($collections as $col)<option value="{{ $col }}" @selected($collection === $col)>{{ $col }}</option>@endforeach</select></div>
    <div class="flex items-end gap-2"><x-ui.button type="submit" icon="search">Cari</x-ui.button><x-ui.button :href="route('admin.media.index')" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800">Reset</x-ui.button></div>
  </form>
</div>
<div class="admin-surface p-6 mb-6">
  <div class="mb-5 text-lg font-black tracking-tight text-white">Upload media baru</div>
  <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-[.8fr_.9fr_1fr_auto]" data-loading="Mengupload media…">
    @csrf
    <x-ui.field label="Koleksi" name="collection" :value="old('collection', 'media')" placeholder="gallery / facilities / pages" class="bg-slate-950 text-white border-slate-700" />
    <x-ui.field label="Alt text" name="alt_text" :value="old('alt_text')" placeholder="Deskripsi singkat gambar" class="bg-slate-950 text-white border-slate-700" />
    <label class="block"><span class="mb-2 block text-sm font-bold text-slate-300">File</span><input type="file" name="file-text[]" class="ui-input bg-slate-950 text-white border-slate-700" multiple required accept="image/*,application/pdf"></label>
    <div class="flex items-end"><x-ui.button type="submit" icon="upload-cloud">Upload</x-ui.button></div>
    <x-field-error name="file-text" />
    <x-field-error name="file-text.*" />
  </form>
</div>
<div class="admin-surface p-6">
  @if($items->count() === 0)
    <x-empty-state icon="folder-open" title="Media masih kosong" description="Upload gambar atau dokumen agar dapat digunakan pada modul CMS v3." />
  @else
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">@foreach($items as $item)@php($isImage = str_starts_with((string) $item->mime_type, 'image/') || preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $item->file_path))<div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 p-4">@if($isImage)<img src="{{ asset($item->file_path) }}" alt="{{ $item->alt_text }}" class="h-44 w-full rounded-2xl object-cover border border-slate-800">@else<div class="flex h-44 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900"><x-ui.icon name="file-text" class="h-8 w-8 text-slate-500" /></div>@endif<div class="mt-4 font-black text-white line-clamp-1">{{ $item->original_name ?: basename($item->file_path) }}</div><div class="mt-1 text-xs text-slate-400 line-clamp-2">{{ $item->file_path }}</div><div class="mt-3 flex flex-wrap gap-2"><span class="rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[11px] font-bold text-slate-300">{{ $item->collection ?: 'media' }}</span><span class="rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[11px] font-bold text-slate-300">{{ number_format(($item->file_size ?? 0) / 1024, 1) }} KB</span></div><div class="mt-4 flex gap-2"><x-ui.button :href="asset($item->file_path)" target="_blank" size="sm" variant="secondary" icon="arrow-right" class="flex-1">Buka</x-ui.button><form method="post" action="{{ route('admin.media.destroy', $item->id) }}" data-loading="Menghapus…">@csrf @method('DELETE')<x-ui.button type="submit" size="sm" variant="danger" icon="trash" data-confirm="Hapus media ini? File fisik juga akan dihapus."></x-ui.button></form></div></div>@endforeach</div>
    <div class="mt-4">{{ $items->links('pagination.simple') }}</div>
  @endif
</div>
@endsection
