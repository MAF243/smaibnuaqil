<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? "SMA Ibnu'Aqil" }}</title>
  <x-ui.assets />
  @stack('head')
</head>
<body class="ui-gradient min-h-screen text-foreground antialiased">
  <div class="border-b border-emerald-200/70 bg-emerald-50/80 text-emerald-900 backdrop-blur">
    <div class="ui-container flex flex-wrap items-center justify-between gap-3 py-2 text-xs font-bold sm:text-sm">
      <div class="inline-flex items-center gap-2"><x-ui.icon name="badge-check" class="h-4 w-4" />Website resmi sekolah & portal PPDB online SMA Ibnu'Aqil.</div>
      <div class="inline-flex items-center gap-4 text-emerald-800/90">
        <a href="{{ route('site.pages.contact') }}" class="hover:text-emerald-950">Kontak Sekolah</a>
        <a href="{{ route('portal') }}" class="hover:text-emerald-950">Portal PPDB</a>
      </div>
    </div>
  </div>

  <header class="sticky top-0 z-50 border-b border-white/70 bg-white/85 backdrop-blur-xl">
    <div class="ui-container flex h-20 items-center justify-between gap-5">
      <a class="flex min-w-0 items-center gap-3" href="{{ route('site.home') }}">
        <img src="{{ asset('asset/logo.jpg') }}" alt="Logo SMA Ibnu'Aqil" class="h-12 w-12 rounded-2xl object-cover ring-1 ring-slate-200">
        <div class="min-w-0 leading-tight">
          <div class="truncate text-lg font-black tracking-tight text-slate-950">SMA Ibnu'Aqil</div>
          <div class="truncate text-xs font-semibold uppercase tracking-[.18em] text-slate-500">School Website & Admissions</div>
        </div>
      </a>

      <nav class="hidden items-center gap-1 xl:flex">
        <x-ui.button :href="route('site.home')" size="sm" :variant="request()->routeIs('site.home') ? 'secondary' : 'ghost'">Beranda</x-ui.button>
        <x-ui.button :href="route('site.pages.profile')" size="sm" :variant="request()->routeIs('site.pages.profile') ? 'secondary' : 'ghost'">Profil</x-ui.button>
        <x-ui.button :href="route('site.pages.vision')" size="sm" :variant="request()->routeIs('site.pages.vision') ? 'secondary' : 'ghost'">Visi Misi</x-ui.button>
        <x-ui.button :href="route('site.news.index')" size="sm" :variant="request()->routeIs('site.news.*') ? 'secondary' : 'ghost'">Berita</x-ui.button>
        <x-ui.button :href="route('site.gallery.index')" size="sm" :variant="request()->routeIs('site.gallery.*') ? 'secondary' : 'ghost'">Galeri</x-ui.button>
        <x-ui.button :href="route('site.facilities.index')" size="sm" :variant="request()->routeIs('site.facilities.*') ? 'secondary' : 'ghost'">Fasilitas</x-ui.button>
        <x-ui.button :href="route('site.pages.faq')" size="sm" :variant="request()->routeIs('site.pages.faq') ? 'secondary' : 'ghost'">FAQ PPDB</x-ui.button>
        <x-ui.button :href="route('site.pages.contact')" size="sm" :variant="request()->routeIs('site.pages.contact') ? 'secondary' : 'ghost'">Kontak</x-ui.button>
      </nav>

      <div class="hidden items-center gap-2 lg:flex">
        <x-ui.button :href="route('portal')" size="sm" icon="library">Daftar PPDB</x-ui.button>
      </div>

      <details class="relative lg:hidden">
        <summary class="inline-flex h-11 cursor-pointer list-none items-center gap-2 rounded-2xl border border-border bg-white px-4 text-sm font-extrabold shadow-sm">
          <x-ui.icon name="menu" class="h-4 w-4" /> Menu
        </summary>
        <div class="absolute right-0 mt-3 w-72 rounded-3xl border border-border bg-white p-3 shadow-panel">
          <div class="space-y-1">
            @foreach([
              ['site.home','Beranda'],['site.pages.profile','Profil'],['site.pages.vision','Visi Misi'],['site.news.index','Berita'],['site.gallery.index','Galeri'],['site.facilities.index','Fasilitas'],['site.pages.faq','FAQ PPDB'],['site.pages.contact','Kontak']
            ] as [$routeName,$label])
              <a class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 hover:text-slate-950" href="{{ route($routeName) }}">{{ $label }}</a>
            @endforeach
          </div>
          <x-ui.button :href="route('portal')" class="mt-3 w-full" icon="library">Masuk Portal PPDB</x-ui.button>
        </div>
      </details>
    </div>
  </header>

  <div class="ui-container pt-5"><x-flash /></div>
  <main>@yield('content')</main>

  <footer class="mt-20 border-t border-slate-200 bg-slate-950 text-slate-300">
    <div class="ui-container grid gap-10 py-12 md:grid-cols-[1.35fr_.85fr_.85fr]">
      <div>
        <div class="mb-4 flex items-center gap-3">
          <img src="{{ asset('asset/logo.jpg') }}" class="h-12 w-12 rounded-2xl object-cover ring-1 ring-white/10" alt="">
          <div>
            <div class="font-black text-white">SMA Ibnu'Aqil</div>
            <div class="text-sm text-slate-400">Pendidikan menengah yang berfokus pada akademik, karakter, dan kesiapan masa depan.</div>
          </div>
        </div>
        <p class="max-w-xl text-sm leading-7 text-slate-400">Website ini dirancang sebagai pusat informasi sekolah sekaligus portal PPDB modern agar proses komunikasi, promosi sekolah, dan pendaftaran calon siswa berjalan lebih rapi, cepat, dan profesional.</p>
      </div>
      <div>
        <div class="mb-3 text-sm font-black uppercase tracking-[.16em] text-slate-500">Navigasi</div>
        <div class="grid gap-2 text-sm">
          <a href="{{ route('site.pages.profile') }}" class="hover:text-white">Profil Sekolah</a>
          <a href="{{ route('site.pages.vision') }}" class="hover:text-white">Visi & Misi</a>
          <a href="{{ route('site.news.index') }}" class="hover:text-white">Berita</a>
          <a href="{{ route('site.gallery.index') }}" class="hover:text-white">Galeri</a>
          <a href="{{ route('site.facilities.index') }}" class="hover:text-white">Fasilitas</a>
        </div>
      </div>
      <div>
        <div class="mb-3 text-sm font-black uppercase tracking-[.16em] text-slate-500">Akses Cepat</div>
        <div class="grid gap-2 text-sm">
          <a href="{{ route('portal') }}" class="hover:text-white">Portal PPDB</a>
          <a href="{{ route('admin.auth.login.form') }}" class="hover:text-white">Admin Panel</a>
          <a href="{{ route('site.pages.faq') }}" class="hover:text-white">FAQ PPDB</a>
          <a href="{{ route('site.pages.contact') }}" class="hover:text-white">Kontak Sekolah</a>
        </div>
      </div>
    </div>
    <div class="border-t border-slate-800 py-4 text-center text-xs text-slate-500">© {{ date('Y') }} SMA Ibnu'Aqil. Seluruh hak cipta dilindungi.</div>
  </footer>

  <x-loading-overlay />
  <script src="{{ asset('ui/app.js') }}"></script>
  @stack('scripts')
</body>
</html>
