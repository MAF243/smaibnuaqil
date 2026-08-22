@extends('layouts.app')
@php($title = 'Login Portal PPDB')
@section('content')
<div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[.95fr_.85fr]">
  <div class="ui-card p-6 md:p-8">
    <div class="mb-6 flex items-center gap-3">
      <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon name="log-in" class="h-6 w-6" /></div>
      <div>
        <h1 class="text-2xl font-black tracking-tight">Masuk ke Portal PPDB</h1>
        <p class="mt-1 text-sm leading-6 text-slate-600">Gunakan akun pendaftar untuk melanjutkan formulir, memeriksa status, dan melihat notifikasi dari panitia.</p>
      </div>
    </div>
    <form method="post" action="{{ route('auth.login') }}" data-loading="Memproses login…" class="space-y-4">
      @csrf
      <x-ui.field label="Username" name="username" :value="old('username')" placeholder="Masukkan username pendaftar" required autofocus />
      <x-ui.field label="Password" name="password" type="password" placeholder="Masukkan password" required />
      <div class="grid gap-3 pt-2">
        <x-ui.button type="submit" icon="log-in">Masuk ke Dashboard</x-ui.button>
        <x-ui.button :href="route('auth.register.form')" variant="secondary" icon="plus">Belum punya akun? Daftar dulu</x-ui.button>
      </div>
      <div class="pt-2 text-center text-sm"><a class="font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('portal') }}">Kembali ke portal PPDB</a></div>
    </form>
  </div>
  <div class="ui-card p-6 md:p-8">
    <div class="ui-kicker"><x-ui.icon name="shield-check" class="h-4 w-4" />Aman & Tertata</div>
    <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-950">Apa yang bisa Anda lakukan setelah login?</h2>
    <div class="mt-5 space-y-4 text-sm leading-7 text-slate-600">
      <div class="rounded-2xl border border-border bg-slate-50 p-4"><strong class="text-slate-950">Lanjutkan formulir</strong><br>Data yang sudah disimpan dapat dilanjutkan tanpa mengulang dari awal.</div>
      <div class="rounded-2xl border border-border bg-slate-50 p-4"><strong class="text-slate-950">Pantau status pendaftaran</strong><br>Lihat perubahan status, jadwal interview, dan informasi hasil seleksi.</div>
      <div class="rounded-2xl border border-border bg-slate-50 p-4"><strong class="text-slate-950">Kelola berkas</strong><br>Periksa dokumen yang sudah diunggah dan lengkapi jika ada kekurangan.</div>
    </div>
  </div>
</div>
@endsection
