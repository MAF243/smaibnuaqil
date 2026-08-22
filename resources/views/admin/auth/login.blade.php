<!doctype html>
<html lang="id" class="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Login Admin</title>
  <x-ui.assets />
</head>
<body class="admin-body min-h-screen text-slate-100 antialiased">
  <div class="flex min-h-screen items-center justify-center p-4">
    <div class="grid w-full max-w-6xl gap-6 lg:grid-cols-[.95fr_.85fr]">
      <div class="admin-surface p-7 md:p-9">
        <div class="mb-7 flex items-center gap-4">
          <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-300"><x-ui.icon name="shield-check" class="h-7 w-7" /></div>
          <div>
            <h1 class="text-3xl font-black tracking-tight text-white">Login Admin</h1>
            <p class="mt-1 text-sm leading-6 text-slate-400">Masuk untuk mengelola PPDB, konten website, media, dan aktivitas sistem.</p>
          </div>
        </div>

        @if ($errors->any())
          <x-alert type="danger" title="Login gagal" class="mb-5">
            <ul class="list-disc space-y-1 pl-5">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </x-alert>
        @endif

        <form method="post" action="{{ route('admin.auth.login') }}" data-loading="Login…" class="space-y-4">
          @csrf
          <x-ui.field label="Username Admin" name="username" :value="old('username')" required autofocus class="bg-slate-950 text-white border-slate-700" />
          <x-ui.field label="Password" name="password" type="password" required class="bg-slate-950 text-white border-slate-700" />
          <div class="grid gap-3 pt-2">
            <x-ui.button type="submit" icon="log-in">Masuk ke Dashboard</x-ui.button>
            <x-ui.button :href="route('site.home')" variant="outline" class="border-slate-700 bg-slate-950 text-slate-100 hover:bg-slate-900" icon="globe-2">Kembali ke Website</x-ui.button>
          </div>
        </form>
      </div>
      <div class="admin-surface p-7 md:p-9">
        <div class="ui-kicker bg-emerald-500/10 text-emerald-300 border-emerald-500/20"><x-ui.icon name="badge-check" class="h-4 w-4" />Admin Workspace</div>
        <h2 class="mt-5 text-3xl font-black tracking-tight text-white">Panel kerja yang dirancang lebih rapi dan profesional</h2>
        <p class="mt-4 text-sm leading-7 text-slate-300">Panel admin difokuskan untuk membantu panitia dan tim pengelola website memonitor pendaftar, memvalidasi dokumen, mengatur konten publik, dan menjaga kualitas informasi sekolah.</p>
        <div class="mt-6 grid gap-3">
          <div class="rounded-2xl border border-slate-800 bg-slate-950/55 p-4 text-sm leading-7 text-slate-300">• Ringkasan jumlah pendaftar dan perkembangan status lebih mudah dibaca.</div>
          <div class="rounded-2xl border border-slate-800 bg-slate-950/55 p-4 text-sm leading-7 text-slate-300">• Konten berita, galeri, fasilitas, dan media tersusun dalam modul yang lebih jelas.</div>
          <div class="rounded-2xl border border-slate-800 bg-slate-950/55 p-4 text-sm leading-7 text-slate-300">• Audit log membantu tim melacak aktivitas perubahan data secara lebih aman.</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
