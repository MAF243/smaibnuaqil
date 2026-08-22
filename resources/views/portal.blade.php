@extends('layouts.site')
@php($title = 'Portal PPDB')
@section('content')
<section class="ui-section">
  <div class="ui-container">
    <div class="grid items-start gap-8 lg:grid-cols-[1.15fr_.85fr]">
      <div class="ui-card p-7 md:p-10">
        <span class="ui-kicker"><x-ui.icon name="library" class="h-4 w-4" />Pendaftaran Peserta Didik Baru</span>
        <h1 class="ui-title mt-5 max-w-4xl">Mulai pendaftaran dengan alur yang lebih jelas, rapi, dan mudah dipantau.</h1>
        <p class="ui-lead mt-5 max-w-3xl">Portal PPDB SMA Ibnu Aqil membantu calon siswa dan orang tua menyelesaikan proses pendaftaran secara bertahap: membuat akun, mengisi data, mengunggah berkas, menerima nomor pendaftaran, hingga memantau status seleksi dan jadwal lanjutan.</p>
        <div class="mt-8 flex flex-wrap gap-3">
          @if(session('username'))
            <a class="ui-btn ui-btn-primary ui-btn-lg" href="{{ route('student.dashboard') }}"><x-ui.icon name="layout-dashboard" class="h-5 w-5" />Buka Dashboard</a>
            <a class="ui-btn ui-btn-secondary ui-btn-lg" href="{{ route('ppdb.step1') }}"><x-ui.icon name="edit" class="h-5 w-5" />Lanjutkan Formulir</a>
          @else
            <a class="ui-btn ui-btn-primary ui-btn-lg" href="{{ route('auth.register.form') }}"><x-ui.icon name="plus" class="h-5 w-5" />Buat Akun Pendaftar</a>
            <a class="ui-btn ui-btn-secondary ui-btn-lg" href="{{ route('auth.login.form') }}"><x-ui.icon name="log-in" class="h-5 w-5" />Login ke Portal</a>
          @endif
        </div>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          @foreach([
            ['01','Lengkapi Data','Isi data siswa dan orang tua/wali dengan informasi yang valid.'],
            ['02','Unggah Berkas','Kirim berkas wajib secara bertahap dengan ukuran yang sudah ditentukan.'],
            ['03','Dapatkan Nomor','Nomor pendaftaran dibuat otomatis setelah data dikirim.'],
            ['04','Pantau Proses','Lihat timeline status, notifikasi, jadwal tes, dan hasil seleksi.'],
          ] as $item)
            <div class="rounded-[1.25rem] border border-border bg-slate-50 p-4">
              <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-primary text-sm font-black text-white">{{ $item[0] }}</div>
              <div class="text-base font-black tracking-tight text-slate-950">{{ $item[1] }}</div>
              <div class="mt-2 text-sm leading-6 text-slate-600">{{ $item[2] }}</div>
            </div>
          @endforeach
        </div>
      </div>
      <div class="space-y-5">
        <div class="ui-card p-6">
          <div class="mb-4 flex items-start gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><x-ui.icon name="badge-check" class="h-6 w-6" /></div>
            <div>
              <h2 class="text-xl font-black tracking-tight text-slate-950">Kenapa portal ini lebih nyaman?</h2>
              <p class="mt-1 text-sm leading-6 text-slate-600">Karena proses pendaftaran tidak lagi tersebar. Semua ringkasan penting berada di satu dashboard.</p>
            </div>
          </div>
          <div class="space-y-3 text-sm leading-6 text-slate-600">
            <div class="ui-stat-chip"><x-ui.icon name="check-circle" class="h-4 w-4 text-emerald-600" />Cek kelengkapan berkas lebih cepat</div>
            <div class="ui-stat-chip"><x-ui.icon name="bell" class="h-4 w-4 text-emerald-600" />Dapat notifikasi saat status diperbarui</div>
            <div class="ui-stat-chip"><x-ui.icon name="calendar" class="h-4 w-4 text-emerald-600" />Jadwal tes dan interview lebih teratur</div>
          </div>
          <a class="ui-btn ui-btn-outline mt-5 w-full" href="{{ route('admin.auth.login.form') }}"><x-ui.icon name="shield" class="h-4 w-4" />Akses Panitia / Admin</a>
        </div>
        <div class="ui-card p-6">
          <div class="mb-3 flex items-center gap-2 text-base font-black tracking-tight text-slate-950"><x-ui.icon name="help-circle" class="h-5 w-5 text-primary" />Informasi singkat sebelum mendaftar</div>
          @if($faq)
            <p class="text-sm leading-7 text-slate-600">{{ $faq->excerpt }}</p>
            <x-ui.button :href="route('site.pages.faq')" variant="secondary" size="sm" class="mt-4">Baca FAQ PPDB</x-ui.button>
          @else
            <ul class="space-y-2 text-sm leading-7 text-slate-600">
              <li>• Siapkan dokumen dalam format PDF/JPG/PNG yang jelas dan terbaca.</li>
              <li>• Gunakan nomor ponsel aktif untuk komunikasi lanjutan dari panitia.</li>
              <li>• Pastikan setiap data sesuai dokumen resmi agar proses verifikasi lebih cepat.</li>
            </ul>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
