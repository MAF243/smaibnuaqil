@extends('layouts.site')
@php($title = 'Berita Sekolah')
@section('content')
<section class="ui-section">
  <div class="ui-container">
    <x-ui.page-header title="Berita Sekolah" description="Kumpulan informasi, kegiatan, dan pembaruan terbaru dari SMA Ibnu'Aqil." kicker="Newsroom" icon="newspaper">
      <form method="get" action="{{ route('site.news.index') }}" class="flex w-full max-w-sm gap-2">
        <input class="ui-input" type="text" name="q" value="{{ $q }}" placeholder="Cari judul atau isi berita...">
        <x-ui.button type="submit" icon="search">Cari</x-ui.button>
      </form>
    </x-ui.page-header>
    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
      @forelse($items as $n)
        <x-ui.card padding="p-0" class="overflow-hidden">
          @if($n->image_normalized)
            <img src="{{ asset($n->image_normalized) }}" class="h-56 w-full object-cover" alt="{{ $n->title_normalized }}">
          @endif
          <div class="p-6">
            <div class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">{{ optional($n->created_at)->format('d M Y') ?: 'Berita Sekolah' }}</div>
            <h2 class="mt-3 text-xl font-black tracking-tight text-slate-950">{{ $n->title_normalized }}</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">{{ $n->excerpt }}</p>
            <x-ui.button :href="route('site.news.show', $n->id)" size="sm" class="mt-5" iconRight="arrow-right">Baca Selengkapnya</x-ui.button>
          </div>
        </x-ui.card>
      @empty
        <div class="md:col-span-2 lg:col-span-3">
          <x-empty-state icon="newspaper" title="Belum ada berita yang sesuai" description="Konten berita akan tampil setelah admin mempublikasikannya." />
        </div>
      @endforelse
    </div>
    <div class="mt-8">{{ $items->links('pagination.simple') }}</div>
  </div>
</section>
@endsection
