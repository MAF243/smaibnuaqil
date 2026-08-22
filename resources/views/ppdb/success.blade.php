@extends('layouts.app')
@php($title = 'PPDB - Berhasil Dikirim')
@section('content')
<div class="mx-auto max-w-4xl">
  <div class="ui-card p-8 text-center md:p-12">
    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[1.75rem] bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><x-ui.icon name="badge-check" class="h-10 w-10" /></div>
    <h1 class="mt-6 text-3xl font-black tracking-tight text-slate-950 md:text-4xl">Pendaftaran berhasil dikirim</h1>
    <p class="mx-auto mt-3 max-w-2xl text-base leading-8 text-slate-600">Terima kasih. Data pendaftaran Anda telah masuk ke sistem dan akan diproses oleh panitia. Pantau perkembangan status, notifikasi, serta jadwal lanjutan melalui dashboard pendaftar.</p>
    <div class="mt-6 flex items-center justify-center gap-3 text-sm text-slate-500">
      <span>Status saat ini</span>
      <x-status-badge :status="$student->status_normalized" />
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-3 text-left">
      <div class="rounded-[1.25rem] border border-border bg-slate-50 p-5">
        <div class="text-sm font-black tracking-tight text-slate-950">Pantau status</div>
        <p class="mt-2 text-sm leading-7 text-slate-600">Perubahan status seperti under review, interview, atau accepted akan tampil di dashboard Anda.</p>
      </div>
      <div class="rounded-[1.25rem] border border-border bg-slate-50 p-5">
        <div class="text-sm font-black tracking-tight text-slate-950">Cek notifikasi</div>
        <p class="mt-2 text-sm leading-7 text-slate-600">Jika ada berkas kurang atau ada jadwal lanjutan, panitia akan mengirim pemberitahuan melalui sistem.</p>
      </div>
      <div class="rounded-[1.25rem] border border-border bg-slate-50 p-5">
        <div class="text-sm font-black tracking-tight text-slate-950">Siapkan komunikasi</div>
        <p class="mt-2 text-sm leading-7 text-slate-600">Pastikan nomor ponsel yang dicantumkan aktif agar informasi penting tidak terlewat.</p>
      </div>
    </div>

    <div class="mt-8 flex flex-wrap justify-center gap-3">
      <x-ui.button :href="route('student.dashboard')" icon="layout-dashboard">Buka Dashboard</x-ui.button>
      <x-ui.button :href="route('site.home')" variant="secondary" icon="globe-2">Kembali ke Website</x-ui.button>
    </div>
  </div>
</div>
@endsection
