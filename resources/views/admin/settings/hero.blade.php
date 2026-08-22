@extends('layouts.admin')
@php($pageTitle = 'Pengaturan Hero Website')
@section('content')
<div class="grid gap-6 lg:grid-cols-[1fr_.7fr]">
  <div class="admin-surface p-6">
    <div class="mb-5"><div class="text-xl font-black tracking-tight text-white">Atur hero homepage</div><p class="mt-1 text-sm text-slate-400">Bagian hero adalah area pertama yang dilihat pengunjung. Gunakan judul singkat, kuat, dan meyakinkan.</p></div>
    <form method="post" action="{{ route('admin.settings.hero.update') }}" enctype="multipart/form-data" data-loading="Menyimpan…" class="space-y-4">
      @csrf
      <x-ui.field label="Judul Hero" name="hero_title" :value="old('hero_title', $hero_title)" class="bg-slate-950 text-white border-slate-700" />
      <x-ui.textarea label="Subjudul Hero" name="hero_subtitle" :value="old('hero_subtitle', $hero_subtitle)" rows="5" hint="Gunakan 1–2 kalimat yang menjelaskan keunggulan sekolah dan portal PPDB." class="bg-slate-950 text-white border-slate-700" />
      <label class="block"><span class="mb-2 block text-sm font-bold text-slate-300">Gambar Hero</span><input type="file" name="hero_image" accept="image/*" class="ui-input bg-slate-950 text-white border-slate-700"></label>
      <x-field-error name="hero_image" />
      <div class="inline-flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/55 px-4 py-3 text-sm font-bold text-slate-300"><input class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-emerald-500" type="checkbox" name="remove_hero_image" value="1" id="rmHero"><label for="rmHero">Hapus gambar hero custom</label></div>
      <div class="flex flex-wrap gap-3 pt-2"><x-ui.button type="submit" icon="check-circle">Simpan Pengaturan</x-ui.button><x-ui.button :href="route('site.home')" target="_blank" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" icon="eye">Preview Website</x-ui.button></div>
    </form>
  </div>
  <div class="admin-surface p-6">
    <div class="text-lg font-black tracking-tight text-white">Preview Gambar Hero</div>
    <p class="mt-1 text-sm text-slate-400">Gunakan gambar dengan komposisi landscape yang rapi agar headline tetap mudah terbaca.</p>
    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/55">
      @if($hero_image_path)
        <img src="{{ asset($hero_image_path) }}" alt="Hero preview" class="h-80 w-full object-cover">
      @else
        <div class="flex h-80 items-center justify-center text-center text-sm text-slate-500">Belum ada gambar hero custom. Sistem akan memakai gambar default.</div>
      @endif
    </div>
  </div>
</div>
@endsection
