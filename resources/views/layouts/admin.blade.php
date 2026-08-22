<!doctype html>
<html lang="id" class="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'Admin - SMA Ibnu Aqil' }}</title>
  <x-ui.assets />
  @stack('head')
</head>
<body class="admin-body min-h-screen text-slate-100 antialiased">
  <div class="flex min-h-screen">
    <aside class="hidden w-80 shrink-0 border-r border-slate-800/80 bg-slate-950/85 p-5 lg:block">
      <div class="mb-8 flex items-center gap-4 rounded-3xl border border-slate-800 bg-slate-900/65 p-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-300"><x-ui.icon name="shield-check" class="h-6 w-6" /></div>
        <div class="min-w-0">
          <div class="truncate text-lg font-black tracking-tight text-white">Panel Pengelolaan</div>
          <div class="truncate text-xs font-semibold uppercase tracking-[.15em] text-slate-400">{{ session('admin_username') }} • {{ session('admin_role') }}</div>
        </div>
      </div>
      @php($navGroups = [
        'Ringkasan' => [
          ['route'=>'admin.dashboard','icon'=>'layout-dashboard','label'=>'Dashboard'],
          ['route'=>'admin.notifications.index','icon'=>'bell','label'=>'Notifikasi'],
        ],
        'PPDB' => [
          ['route'=>'admin.students.index','icon'=>'users','label'=>'Data Pendaftar'],
          ['route'=>'admin.students.export.csv','icon'=>'file-spreadsheet','label'=>'Export CSV'],
        ],
        'Website' => [
          ['route'=>'admin.news.index','icon'=>'newspaper','label'=>'Berita'],
          ['route'=>'admin.gallery.index','icon'=>'images','label'=>'Galeri'],
          ['route'=>'admin.facilities.index','icon'=>'building-2','label'=>'Fasilitas'],
          ['route'=>'admin.media.index','icon'=>'folder-open','label'=>'Media Library'],
          ['route'=>'admin.pages.index','icon'=>'file-text','label'=>'Halaman Website'],
        ],
        'Pengaturan' => [
          ['route'=>'admin.settings.hero','icon'=>'panel-top','label'=>'Hero Website'],
          ['route'=>'admin.settings.map','icon'=>'map-pin','label'=>'Peta & Lokasi'],
        ],
      ])
      @foreach($navGroups as $group => $items)
        <section class="mb-6">
          <div class="mb-2 px-3 text-[11px] font-black uppercase tracking-[.2em] text-slate-500">{{ $group }}</div>
          <div class="space-y-1.5">
            @foreach($items as $item)
              <a class="admin-navlink {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><x-ui.icon :name="$item['icon']" class="h-4 w-4" /><span>{{ $item['label'] }}</span></a>
            @endforeach
            @if($group === 'Ringkasan' && session('admin_role') === 'superadmin')
              <a class="admin-navlink {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" href="{{ route('admin.audit.index') }}"><x-ui.icon name="shield" class="h-4 w-4" /><span>Audit Log</span></a>
            @endif
          </div>
        </section>
      @endforeach
      <div class="mt-8 space-y-2 border-t border-slate-800 pt-5">
        <a class="admin-navlink" href="{{ route('site.home') }}" target="_blank"><x-ui.icon name="globe-2" class="h-4 w-4" /><span>Lihat Website</span></a>
        <form method="post" action="{{ route('admin.auth.logout') }}" data-loading="Logout…">@csrf<button class="admin-navlink w-full" type="submit" data-confirm="Logout dari panel admin?"><x-ui.icon name="log-out" class="h-4 w-4" /><span>Logout</span></button></form>
      </div>
    </aside>
    <main class="min-w-0 flex-1">
      <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/75 backdrop-blur-xl">
        <div class="flex h-20 items-center justify-between gap-4 px-4 lg:px-8">
          <div>
            <div class="text-2xl font-black tracking-tight text-white">{{ $pageTitle ?? 'Dashboard' }}</div>
            <div class="hidden text-sm text-slate-400 md:block">Kelola PPDB, konten website, media, serta aktivitas sistem dari satu panel yang lebih tertata.</div>
          </div>
          <div class="flex items-center gap-2">
            <x-ui.badge :status="session('admin_role') ?: 'admin'" class="border-slate-700 bg-slate-900 text-slate-200" />
            <x-ui.button :href="route('admin.students.index')" size="sm" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" icon="users">Pendaftar</x-ui.button>
          </div>
        </div>
      </header>
      <div class="p-4 lg:p-8">
        <x-flash />
        @yield('content')
      </div>
    </main>
  </div>
  <x-loading-overlay />
  <script src="{{ asset('ui/app.js') }}"></script>
  @stack('scripts')
</body>
</html>
