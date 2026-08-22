@extends('layouts.app')
@php($title = 'Daftar Akun PPDB')
@section('content')
<div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[.95fr_.85fr]">
  <div class="ui-card p-6 md:p-8">
    <div class="mb-6 flex items-center gap-3">
      <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon name="plus" class="h-6 w-6" /></div>
      <div>
        <h1 class="text-2xl font-black tracking-tight">Buat Akun Pendaftar</h1>
        <p class="mt-1 text-sm leading-6 text-slate-600">Akun ini digunakan untuk mengakses formulir PPDB, dashboard status, dan notifikasi dari sekolah.</p>
      </div>
    </div>
    <form method="post" action="{{ route('auth.register') }}" data-loading="Membuat akun…" class="space-y-4">
      @csrf
      <x-ui.field label="Username" name="username" :value="old('username')" hint="Gunakan username yang mudah diingat dan akan dipakai saat login." required />
      <div class="grid gap-4 md:grid-cols-2">
        <x-ui.field label="Password" name="password" type="password" hint="Gunakan kombinasi yang kuat dan aman." required />
        <x-ui.field label="Konfirmasi Password" name="password_confirmation" type="password" hint="Ketik ulang password untuk memastikan sesuai." required />
      </div>
      <div class="grid gap-3 pt-2">
        <x-ui.button type="submit" icon="check-circle">Buat Akun Sekarang</x-ui.button>
        <x-ui.button :href="route('auth.login.form')" variant="secondary" icon="log-in">Sudah punya akun? Login</x-ui.button>
      </div>
      <div class="pt-2 text-center text-sm"><a class="font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('portal') }}">Kembali ke portal PPDB</a></div>
    </form>
  </div>
  <div class="ui-card p-6 md:p-8">
    <div class="ui-kicker"><x-ui.icon name="check-circle" class="h-4 w-4" />Sebelum Membuat Akun</div>
    <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-950">Siapkan informasi berikut</h2>
    <ul class="mt-5 space-y-3 text-sm leading-7 text-slate-600">
      <li class="rounded-2xl border border-border bg-slate-50 p-4">• Data calon siswa yang sesuai dokumen resmi.</li>
      <li class="rounded-2xl border border-border bg-slate-50 p-4">• Nomor ponsel aktif untuk komunikasi dan tindak lanjut dari panitia.</li>
      <li class="rounded-2xl border border-border bg-slate-50 p-4">• Dokumen digital seperti KK, akta lahir, dan rapor dengan ukuran file sesuai ketentuan.</li>
    </ul>
  </div>
</div>
@endsection
