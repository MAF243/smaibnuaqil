@extends('layouts.site')

@php
  $bg = $heroImage ?? 'asset/gedunghd.png';
  $bgUrl = str_starts_with($bg, 'http') ? $bg : asset($bg);
  $mapTitle = \App\Models\SiteSetting::query()->find('map_title')?->setting_value ?: 'Lokasi Sekolah';
  $mapAddress = \App\Models\SiteSetting::query()->find('map_address')?->setting_value ?: "Alamat lengkap SMA Ibnu'Aqil";
@endphp

@section('content')
  <section class="relative overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-cover bg-center opacity-35" style="background-image:url('{{ $bgUrl }}')"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/80 to-slate-950/45"></div>
    <div class="absolute -left-20 top-10 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl"></div>
    <div class="absolute right-0 top-0 h-72 w-72 rounded-full bg-sky-500/15 blur-3xl"></div>
    <div class="ui-container relative z-10 grid min-h-[620px] items-center gap-10 py-16 lg:grid-cols-[1.2fr_.8fr]">
      <div>
        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[.18em] text-emerald-100 backdrop-blur">
          <x-ui.icon name="badge-check" class="h-3.5 w-3.5" />Website Resmi & PPDB Online
        </div>
        <h1 class="max-w-4xl text-4xl font-black tracking-tight md:text-6xl">{{ $heroTitle }}</h1>
        <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-200">{{ $heroSubtitle }}</p>
        <div class="mt-8 flex flex-wrap gap-3">
          <a class="ui-btn ui-btn-primary ui-btn-lg" href="{{ route('portal') }}"><x-ui.icon name="library" class="h-5 w-5" />Mulai PPDB</a>
          <a class="ui-btn ui-btn-outline ui-btn-lg border-white/20 bg-white/10 text-white hover:bg-white/15" href="{{ route('site.pages.profile') }}"><x-ui.icon name="building-2" class="h-5 w-5" />Profil Sekolah</a>
        </div>
        <div class="mt-8 flex flex-wrap gap-3 text-sm text-slate-200">
          <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2"><x-ui.icon name="badge-check" class="h-4 w-4 text-emerald-300" />Lingkungan belajar yang tertata</span>
          <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2"><x-ui.icon name="users" class="h-4 w-4 text-emerald-300" />Komunikasi sekolah lebih jelas</span>
          <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2"><x-ui.icon name="file-text" class="h-4 w-4 text-emerald-300" />Administrasi PPDB lebih rapi</span>
        </div>
      </div>
      <div class="space-y-5">
        <div class="ui-hero-panel p-6 md:p-7">
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-white"><x-ui.icon name="bell" class="h-6 w-6" /></div>
            <div>
              <div class="text-sm font-black uppercase tracking-[.18em] text-emerald-100">Highlight</div>
              <div class="text-xl font-black tracking-tight">Informasi Utama Sekolah</div>
            </div>
          </div>
          @if($announcement)
            <div class="text-2xl font-black tracking-tight">{{ $announcement->title }}</div>
            @if($announcement->excerpt)<div class="mt-3 text-sm leading-6 text-slate-100">{{ $announcement->excerpt }}</div>@endif
            <div class="mt-3 text-sm leading-7 text-slate-200">{!! \Illuminate\Support\Str::limit(strip_tags($announcement->content), 220) !!}</div>
          @else
            <div class="text-2xl font-black tracking-tight">Website sekolah dan sistem PPDB dalam satu platform</div>
            <p class="mt-3 text-sm leading-7 text-slate-200">Gunakan website ini untuk melihat profil sekolah, berita terbaru, galeri kegiatan, fasilitas pembelajaran, dan melakukan pendaftaran peserta didik baru secara online.</p>
          @endif
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="ui-hero-panel p-5"><div class="text-sm font-black uppercase tracking-[.16em] text-emerald-100">Layanan</div><div class="mt-2 text-2xl font-black">Portal PPDB</div><p class="mt-2 text-sm leading-6 text-slate-200">Akses formulir pendaftaran, unggah dokumen, dan pantau status seleksi dengan dashboard pribadi.</p></div>
          <div class="ui-hero-panel p-5"><div class="text-sm font-black uppercase tracking-[.16em] text-emerald-100">Informasi</div><div class="mt-2 text-2xl font-black">Berita Sekolah</div><p class="mt-2 text-sm leading-6 text-slate-200">Publikasi kegiatan, agenda, pengumuman, dan dokumentasi sekolah tersusun lebih profesional.</p></div>
        </div>
      </div>
    </div>
  </section>

  <section class="ui-section">
    <div class="ui-container grid gap-4 lg:grid-cols-3">
      <x-ui.card title="Profil Sekolah" description="Kenali identitas sekolah, karakter pembelajaran, dan suasana pendidikan yang dibangun.">
        <x-ui.button :href="route('site.pages.profile')" variant="secondary" iconRight="arrow-right">Baca Profil</x-ui.button>
      </x-ui.card>
      <x-ui.card title="Visi & Misi" description="Pelajari arah pengembangan akademik, akhlak, dan budaya sekolah jangka panjang.">
        <x-ui.button :href="route('site.pages.vision')" variant="secondary" iconRight="arrow-right">Lihat Visi Misi</x-ui.button>
      </x-ui.card>
      <x-ui.card title="FAQ PPDB" description="Pertanyaan yang paling sering diajukan orang tua dan calon siswa tersedia di satu halaman.">
        <x-ui.button :href="route('site.pages.faq')" variant="secondary" iconRight="arrow-right">Buka FAQ</x-ui.button>
      </x-ui.card>
    </div>
  </section>

  <section class="ui-section pt-0">
    <div class="ui-container">
      <x-ui.page-header title="Keunggulan yang ingin kami tampilkan" description="Struktur website dibuat agar orang tua dan calon siswa lebih mudah memahami kualitas layanan sekolah sejak halaman pertama." kicker="Why Choose Us" icon="badge-check">
        <x-ui.button :href="route('portal')" size="sm" icon="library">Daftar PPDB</x-ui.button>
      </x-ui.page-header>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
          ['Akademik Tertata','Informasi sekolah, program, dan layanan tersusun lebih jelas dan profesional.','book-open'],
          ['Komunikasi Lebih Cepat','Calon pendaftar dapat menerima update status dan informasi tanpa harus bertanya berulang.','message-square-more'],
          ['PPDB Lebih Terukur','Data pendaftaran dan dokumen tersimpan rapi sehingga proses verifikasi lebih efisien.','check-circle'],
          ['Citra Sekolah Lebih Baik','Tampilan modern membantu website tampil lebih meyakinkan di mata orang tua dan calon siswa.','badge-check'],
        ] as [$heading,$copy,$icon])
          <x-ui.card padding="p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon :name="$icon" class="h-6 w-6" /></div>
            <h3 class="mt-5 text-lg font-black tracking-tight">{{ $heading }}</h3>
            <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ $copy }}</p>
          </x-ui.card>
        @endforeach
      </div>
    </div>
  </section>

  <section class="ui-section pt-0">
    <div class="ui-container">
      <x-ui.page-header title="Fasilitas pendukung pembelajaran" description="Fasilitas sekolah ditampilkan secara visual dan informatif agar pengunjung memperoleh gambaran yang lebih meyakinkan." kicker="Sarana Sekolah" icon="building">
        <x-ui.button :href="route('site.facilities.index')" size="sm" variant="secondary">Lihat Semua</x-ui.button>
      </x-ui.page-header>
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($facilities as $f)
          <x-ui.card padding="p-6">
            <div class="flex gap-4">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon name="building" class="h-6 w-6" /></div>
              <div class="min-w-0 flex-1">
                <h3 class="text-lg font-black tracking-tight">{{ $f->name }}</h3>
                <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ \Illuminate\Support\Str::limit(strip_tags($f->short_description ?? ''), 140) }}</p>
                <details class="ui-disclosure mt-4">
                  <summary class="inline-flex items-center gap-2 rounded-xl bg-secondary px-3 py-2 text-xs font-black text-secondary-foreground">Baca Detail <x-ui.icon name="arrow-right" class="h-3.5 w-3.5" /></summary>
                  <div class="ui-disclosure-panel">
                    @if($f->image_path)<img src="{{ asset($f->image_path) }}" alt="" class="mb-4 h-48 w-full rounded-2xl object-cover">@endif
                    <div class="text-base font-black tracking-tight">{{ $f->modal_title ?: $f->name }}</div>
                    <p class="mt-2 text-sm leading-7 text-muted-foreground">{!! nl2br(e($f->content ?: $f->modal_description ?: $f->short_description ?: '')) !!}</p>
                  </div>
                </details>
              </div>
            </div>
          </x-ui.card>
        @empty
          <div class="md:col-span-2 lg:col-span-3"><x-empty-state icon="building" title="Data fasilitas belum tersedia" description="Fasilitas sekolah akan ditampilkan di sini setelah admin menambahkan konten." /></div>
        @endforelse
      </div>
    </div>
  </section>

  <section class="ui-section pt-0">
    <div class="ui-container">
      <x-ui.page-header title="Berita dan pembaruan terbaru" description="Konten berita membantu website terasa aktif, informatif, dan memberi gambaran nyata tentang aktivitas sekolah." kicker="Kabar Sekolah" icon="newspaper">
        <x-ui.button :href="route('site.news.index')" size="sm" variant="secondary">Semua Berita</x-ui.button>
      </x-ui.page-header>
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($news as $n)
          <x-ui.card padding="p-0" class="overflow-hidden">
            @if($n->image_normalized)<img src="{{ asset($n->image_normalized) }}" class="h-52 w-full object-cover" alt="">@endif
            <div class="p-6">
              <div class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">Berita Sekolah</div>
              <h3 class="mt-3 text-lg font-black tracking-tight text-slate-950">{{ $n->title_normalized }}</h3>
              <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ $n->excerpt }}</p>
              <x-ui.button :href="route('site.news.show', $n->id)" size="sm" class="mt-4" iconRight="arrow-right">Baca Selengkapnya</x-ui.button>
            </div>
          </x-ui.card>
        @empty
          <div class="md:col-span-2 lg:col-span-3"><x-empty-state icon="newspaper" title="Belum ada berita yang dipublikasikan" description="Berita sekolah akan muncul setelah admin menambahkan dan mempublikasikan konten." /></div>
        @endforelse
      </div>
    </div>
  </section>

  <section class="ui-section pt-0">
    <div class="ui-container">
      <x-ui.page-header title="Galeri kegiatan sekolah" description="Dokumentasi visual memberi kesan hidup dan membantu orang tua melihat suasana kegiatan di sekolah." kicker="Dokumentasi" icon="images">
        <x-ui.button :href="route('site.gallery.index')" size="sm" variant="secondary">Lihat Galeri</x-ui.button>
      </x-ui.page-header>
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @forelse($gallery as $g)
          <a href="{{ asset($g->image_path) }}" target="_blank" class="group block overflow-hidden rounded-3xl border border-border bg-white shadow-soft">
            <img src="{{ asset($g->image_path) }}" class="h-44 w-full object-cover transition duration-300 group-hover:scale-105" alt="">
          </a>
        @empty
          <div class="col-span-full"><x-empty-state icon="images" title="Galeri masih kosong" description="Foto kegiatan akan muncul di sini ketika admin menambahkan dokumentasi sekolah." /></div>
        @endforelse
      </div>
    </div>
  </section>

  <section class="ui-section pt-0">
    <div class="ui-container">
      <x-ui.card :title="$mapTitle" :description="$mapAddress" padding="p-5 md:p-6">
        <div class="aspect-video overflow-hidden rounded-3xl border border-border bg-muted">
          @if($mapIframeSrc)
            <iframe src="{{ $mapIframeSrc }}" class="h-full w-full border-0" allowfullscreen loading="lazy"></iframe>
          @else
            <div class="flex h-full items-center justify-center text-center text-muted-foreground"><div><x-ui.icon name="map-pin" class="mx-auto mb-3 h-9 w-9" /><div>Pengaturan peta belum tersedia. Silakan atur dari panel admin.</div></div></div>
          @endif
        </div>
      </x-ui.card>
    </div>
  </section>
@endsection
