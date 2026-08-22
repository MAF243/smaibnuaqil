@extends('layouts.admin')
@php($pageTitle = $mode === 'create' ? 'Tambah Foto Galeri' : 'Edit Foto Galeri')
@section('content')
<div class="admin-surface p-6">
  <div class="mb-6 max-w-3xl"><div class="text-xl font-black tracking-tight text-white">{{ $mode === 'create' ? 'Upload dokumentasi baru' : 'Perbarui dokumentasi' }}</div><p class="mt-1 text-sm leading-7 text-slate-400">Gunakan judul dan deskripsi singkat agar setiap dokumentasi lebih informatif saat dilihat pengunjung website.</p></div>
  <form method="post" action="{{ $mode === 'create' ? route('admin.gallery.store') : route('admin.gallery.update', $item->id) }}" enctype="multipart/form-data" data-loading="Menyimpan…" class="grid gap-6 lg:grid-cols-[1fr_.75fr]">
    @csrf @if($mode !== 'create') @method('PUT') @endif
    <div class="space-y-4">
      <x-ui.field label="Judul" name="title" :value="old('title', $item->title)" class="bg-slate-950 text-white border-slate-700" hint="Opsional, tetapi disarankan agar dokumentasi lebih informatif." />
      <x-ui.textarea label="Deskripsi" name="description" :value="old('description', $item->description)" rows="5" class="bg-slate-950 text-white border-slate-700" />
      <x-ui.field label="Urutan Tampil" name="sort_order" type="number" :value="old('sort_order', $item->sort_order ?? 0)" class="bg-slate-950 text-white border-slate-700" hint="Semakin kecil, semakin awal tampil di halaman galeri." />
    </div>
    <div class="space-y-4 rounded-[1.35rem] border border-slate-800 bg-slate-950/55 p-5">
      <div class="text-lg font-black tracking-tight text-white">File & Publikasi</div>
      <label class="block"><span class="mb-2 block text-sm font-bold text-slate-300">Gambar {{ $mode === 'create' ? '(wajib)' : '(opsional)' }}</span><input type="file" name="image" class="ui-input bg-slate-950 text-white border-slate-700" accept="image/*" {{ $mode === 'create' ? 'required' : '' }}></label>
      <x-field-error name="image" />
      @if($item->image_path)
        <img src="{{ asset($item->image_path) }}" alt="Preview" class="h-56 w-full rounded-2xl border border-slate-800 object-cover">
        <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-300"><input class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-emerald-500" type="checkbox" name="remove_image" value="1">Hapus gambar saat ini</label>
      @endif
      <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-300"><input class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-emerald-500" type="checkbox" name="is_published" value="1" {{ old('is_published', $item->is_published ? '1' : '') ? 'checked' : '' }}>Tampilkan di website publik</label>
    </div>
    <div class="lg:col-span-2 flex flex-wrap gap-3"><x-ui.button type="submit" icon="check-circle">Simpan Foto</x-ui.button><x-ui.button :href="route('admin.gallery.index')" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" icon="arrow-left">Kembali</x-ui.button></div>
  </form>
</div>
@endsection
