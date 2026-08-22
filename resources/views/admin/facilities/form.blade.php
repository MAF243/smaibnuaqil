@extends('layouts.admin')
@php($pageTitle = $mode === 'create' ? 'Tambah Fasilitas' : 'Edit Fasilitas')
@section('content')
<div class="admin-surface p-6">
  <div class="mb-6 max-w-3xl"><div class="text-xl font-black tracking-tight text-white">{{ $mode === 'create' ? 'Tambah fasilitas sekolah' : 'Perbarui fasilitas sekolah' }}</div><p class="mt-1 text-sm leading-7 text-slate-400">Susun informasi fasilitas dengan deskripsi yang singkat namun meyakinkan agar calon siswa dan orang tua memperoleh gambaran sarana sekolah secara lebih profesional.</p></div>
  <form method="post" action="{{ $mode === 'create' ? route('admin.facilities.store') : route('admin.facilities.update', $item->id) }}" enctype="multipart/form-data" data-loading="Menyimpan…" class="grid gap-6 lg:grid-cols-[1fr_.78fr]">
    @csrf @if($mode !== 'create') @method('PUT') @endif
    <div class="space-y-4">
      <div class="grid gap-4 md:grid-cols-[1fr_.35fr]">
        <x-ui.field label="Nama Fasilitas" name="name" :value="old('name', $item->name)" class="bg-slate-950 text-white border-slate-700" required />
        <x-ui.field label="Display Order" name="display_order" type="number" :value="old('display_order', $item->display_order ?? 0)" class="bg-slate-950 text-white border-slate-700" hint="Semakin kecil, semakin atas." />
      </div>
      <x-ui.textarea label="Deskripsi Singkat" name="short_description" :value="old('short_description', $item->short_description)" rows="3" class="bg-slate-950 text-white border-slate-700" />
      <div class="grid gap-4 md:grid-cols-2">
        <x-ui.field label="Icon Class" name="icon_class" :value="old('icon_class', $item->icon_class)" class="bg-slate-950 text-white border-slate-700" hint="Contoh: bi bi-building" />
        <x-ui.field label="Facility Key" name="facility_key" :value="old('facility_key', $item->facility_key)" class="bg-slate-950 text-white border-slate-700" hint="Digunakan sebagai identitas unik. Kosongkan jika ingin otomatis." />
      </div>
      <x-ui.field label="Judul Detail" name="modal_title" :value="old('modal_title', $item->modal_title)" class="bg-slate-950 text-white border-slate-700" />
      <x-ui.textarea label="Konten Detail v3" name="content" :value="old('content', $item->content)" rows="5" class="bg-slate-950 text-white border-slate-700" />
      <x-ui.textarea label="Deskripsi Modal Legacy" name="modal_description" :value="old('modal_description', $item->modal_description)" rows="5" class="bg-slate-950 text-white border-slate-700" />
    </div>
    <div class="space-y-4 rounded-[1.35rem] border border-slate-800 bg-slate-950/55 p-5">
      <div class="text-lg font-black tracking-tight text-white">Gambar & Status</div>
      <label class="block"><span class="mb-2 block text-sm font-bold text-slate-300">Gambar Fasilitas</span><input type="file" name="modal_image" class="ui-input bg-slate-950 text-white border-slate-700" accept="image/*"></label>
      <x-field-error name="modal_image" />
      @if($item->image_path)
        <img src="{{ asset($item->image_path) }}" alt="Preview" class="h-56 w-full rounded-2xl border border-slate-800 object-cover">
        <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-300"><input class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-emerald-500" type="checkbox" name="remove_image" value="1">Hapus gambar saat ini</label>
      @endif
      <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-300"><input class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-emerald-500" type="checkbox" name="is_published" value="1" {{ old('is_published', $item->is_published ? '1' : '') ? 'checked' : '' }}>Tampilkan di website publik</label>
    </div>
    <div class="lg:col-span-2 flex flex-wrap gap-3"><x-ui.button type="submit" icon="check-circle">Simpan Fasilitas</x-ui.button><x-ui.button :href="route('admin.facilities.index')" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" icon="arrow-left">Kembali</x-ui.button></div>
  </form>
</div>
@endsection
