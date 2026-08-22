@extends('layouts.site')
@php($title = $page->title)
@section('content')
<section class="ui-section">
  <div class="ui-container max-w-5xl">
    <article class="ui-card p-6 md:p-10">
      <div class="max-w-3xl">
        <div class="ui-kicker"><x-ui.icon name="file-text" class="h-4 w-4" />Halaman Informasi</div>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 md:text-5xl">{{ $page->title }}</h1>
        @if($page->excerpt)
          <p class="mt-4 text-base leading-8 text-slate-600 md:text-lg">{{ $page->excerpt }}</p>
        @endif
      </div>
      <div class="prose-clean mt-8">{!! $page->content !!}</div>
    </article>
  </div>
</section>
@endsection
