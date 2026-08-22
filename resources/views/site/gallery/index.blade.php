@extends('layouts.site')
@php($title = 'Galeri Kegiatan')
@section('content')
<section class="ui-section">
  <div class="ui-container">
    <x-ui.page-header title="Galeri Kegiatan" description="Dokumentasi kegiatan siswa, program sekolah, dan suasana belajar yang ingin kami tampilkan secara lebih hidup." kicker="Visual Story" icon="images" />
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
      @forelse($items as $g)
        <a href="{{ asset($g->image_path) }}" target="_blank" class="group overflow-hidden rounded-3xl border border-border bg-white shadow-soft">
          <img src="{{ asset($g->image_path) }}" class="h-52 w-full object-cover transition duration-300 group-hover:scale-105" alt="{{ $g->title ?: 'Galeri sekolah' }}">
          <div class="p-4">
            <div class="line-clamp-1 text-sm font-black tracking-tight text-slate-950">{{ $g->title ?: 'Dokumentasi Kegiatan' }}</div>
            @if($g->description)
              <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ $g->description }}</p>
            @endif
          </div>
        </a>
      @empty
        <div class="col-span-full"><x-empty-state icon="images" title="Galeri belum tersedia" description="Foto kegiatan sekolah akan tampil di halaman ini setelah admin menambahkan dokumentasi." /></div>
      @endforelse
    </div>
  </div>
</section>
@endsection
