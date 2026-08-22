@extends('layouts.site')
@php($title = 'Fasilitas Sekolah')
@section('content')
<section class="ui-section">
  <div class="ui-container">
    <x-ui.page-header title="Fasilitas Sekolah" description="Fasilitas yang kami tampilkan dirancang untuk memberi gambaran yang lebih meyakinkan tentang lingkungan belajar siswa." kicker="Facilities" icon="building" />
    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
      @forelse($items as $f)
        <x-ui.card padding="p-6">
          <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon name="building" class="h-6 w-6" /></div>
            <div class="min-w-0 flex-1">
              <h3 class="text-lg font-black tracking-tight text-slate-950">{{ $f->name }}</h3>
              <p class="mt-2 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($f->short_description ?? ''), 160) }}</p>
              <details class="ui-disclosure mt-4">
                <summary class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-secondary px-3 py-2 text-xs font-black text-secondary-foreground">Baca Detail <x-ui.icon name="arrow-right" class="h-3.5 w-3.5" /></summary>
                <div class="ui-disclosure-panel">
                  @if($f->image_path)<img src="{{ asset($f->image_path) }}" alt="{{ $f->name }}" class="mb-4 h-56 w-full rounded-2xl object-cover">@endif
                  <div class="text-lg font-black tracking-tight text-slate-950">{{ $f->modal_title ?: $f->name }}</div>
                  <p class="mt-2 text-sm leading-7 text-slate-600">{!! nl2br(e($f->content ?: $f->modal_description ?: $f->short_description ?: '')) !!}</p>
                </div>
              </details>
            </div>
          </div>
        </x-ui.card>
      @empty
        <div class="md:col-span-2 lg:col-span-3"><x-empty-state icon="building" title="Data fasilitas belum tersedia" description="Fasilitas sekolah akan ditampilkan setelah admin menambahkan konten pendukung." /></div>
      @endforelse
    </div>
  </div>
</section>
@endsection
