<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'Portal PPDB - SMA Ibnu Aqil' }}</title>
  <x-ui.assets />
  @stack('head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased" style="background-image:radial-gradient(circle at top left, rgba(16,185,129,.08), transparent 24%), radial-gradient(circle at top right, rgba(59,130,246,.06), transparent 20%)">
  <header class="sticky top-0 z-50 border-b border-white/80 bg-white/90 backdrop-blur-xl">
    <div class="ui-container flex h-20 items-center justify-between gap-5">
      <a class="flex items-center gap-3" href="{{ route('portal') }}">
        <img src="{{ asset('asset/logo.jpg') }}" alt="Logo" class="h-12 w-12 rounded-2xl object-cover ring-1 ring-slate-200">
        <div>
          <div class="text-lg font-black tracking-tight">Portal PPDB</div>
          <div class="text-xs font-semibold uppercase tracking-[.16em] text-slate-500">SMA Ibnu Aqil</div>
        </div>
      </a>
      <div class="hidden items-center gap-2 md:flex">
        <x-ui.button :href="route('site.home')" target="_blank" size="sm" variant="outline" icon="globe-2">Website Sekolah</x-ui.button>
        @if(session('username'))
          <x-ui.button :href="route('student.dashboard')" size="sm" variant="secondary" icon="layout-dashboard">Dashboard</x-ui.button>
          <form method="post" action="{{ route('auth.logout') }}" data-loading="Mengeluarkan akun…">@csrf<x-ui.button type="submit" size="sm" variant="outline" icon="log-out">Logout</x-ui.button></form>
        @else
          <x-ui.button :href="route('auth.login.form')" size="sm" variant="outline">Login</x-ui.button>
          <x-ui.button :href="route('auth.register.form')" size="sm">Daftar Akun</x-ui.button>
        @endif
      </div>
    </div>
  </header>

  <main class="ui-container py-8 md:py-10">
    <x-flash />
    @yield('content')
  </main>

  <footer class="border-t border-slate-200 bg-white/70 py-6 text-center text-sm text-slate-500">
    Portal PPDB SMA Ibnu Aqil — proses pendaftaran lebih tertata, transparan, dan mudah dipantau.
  </footer>
  <x-loading-overlay />
  <script src="{{ asset('ui/app.js') }}"></script>
  @stack('scripts')
</body>
</html>
