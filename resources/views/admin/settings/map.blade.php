@extends('layouts.admin')
@php($pageTitle = 'Pengaturan Peta & Lokasi')
@section('content')
<div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
  <div class="admin-surface p-6">
    <div class="mb-5"><div class="text-xl font-black tracking-tight text-white">Pengaturan lokasi sekolah</div><p class="mt-1 text-sm text-slate-400">Section peta membantu orang tua dan pengunjung menemukan lokasi sekolah dengan lebih cepat.</p></div>
    <form method="post" action="{{ route('admin.settings.map.update') }}" data-loading="Menyimpan…" class="space-y-4">
      @csrf
      <x-ui.field label="Judul Section" name="map_title" :value="old('map_title', $map_title)" class="bg-slate-950 text-white border-slate-700" />
      <x-ui.field label="Alamat" name="map_address" :value="old('map_address', $map_address)" class="bg-slate-950 text-white border-slate-700" />
      <x-ui.textarea label="Google Maps iframe src" name="map_iframe_src" :value="old('map_iframe_src', $map_iframe_src)" rows="6" hint="Tempelkan nilai src dari iframe Google Maps agar peta dapat tampil di homepage." class="bg-slate-950 text-white border-slate-700" />
      <div class="flex flex-wrap gap-3 pt-2"><x-ui.button type="submit" icon="check-circle">Simpan Pengaturan</x-ui.button><x-ui.button :href="route('site.home')" target="_blank" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" icon="eye">Lihat di Homepage</x-ui.button></div>
    </form>
  </div>
  <div class="admin-surface p-6">
    <div class="text-lg font-black tracking-tight text-white">Preview Peta</div>
    <p class="mt-1 text-sm text-slate-400">Pastikan iframe menampilkan lokasi yang benar dan mudah dikenali pengunjung.</p>
    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/55" style="aspect-ratio:16/11;">
      @if($map_iframe_src)
        <iframe src="{{ $map_iframe_src }}" class="h-full w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      @else
        <div class="flex h-full items-center justify-center text-center text-sm text-slate-500">Belum ada embed map. Tempelkan src iframe Google Maps pada form di sebelah kiri.</div>
      @endif
    </div>
  </div>
</div>
@endsection
