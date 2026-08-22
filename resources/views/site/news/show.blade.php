@extends('layouts.site')
@php($title = $news->title_normalized)
@section('content')
<section class="ui-section">
  <div class="ui-container max-w-5xl">
    <a href="{{ route('site.news.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm mb-5"><x-ui.icon name="arrow-left" class="h-4 w-4" />Kembali ke daftar berita</a>
    <article class="ui-card overflow-hidden">
      @if($news->image_normalized)
        <img src="{{ asset($news->image_normalized) }}" alt="{{ $news->title_normalized }}" class="h-[280px] w-full object-cover md:h-[420px]">
      @endif
      <div class="p-6 md:p-10">
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[.16em] text-emerald-700">Berita Sekolah</div>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 md:text-5xl">{{ $news->title_normalized }}</h1>
        <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-slate-500">
          <span class="inline-flex items-center gap-2"><x-ui.icon name="calendar" class="h-4 w-4" />{{ optional($news->created_at)->format('d F Y, H:i') }}</span>
          <span class="inline-flex items-center gap-2"><x-ui.icon name="building-2" class="h-4 w-4" />SMA Ibnu'Aqil</span>
        </div>
        <div class="prose-clean mt-8">{!! nl2br(e($news->content_normalized)) !!}</div>
      </div>
    </article>
  </div>
</section>
@endsection
