@extends('layouts.app')

@section('content')
  <div class="card">
    <h1 style="margin:0 0 8px">Selamat datang</h1>
    <p style="margin:0 0 16px">Portal PPDB SMA Ibnu Aqil (versi Laravel). Silakan login/daftar untuk mulai mengisi formulir.</p>

    <div class="row">
      <div class="col">
        <div class="card" style="border-style:dashed">
          <h3 style="margin-top:0">Untuk Calon Siswa</h3>
          <p style="margin-top:0">Isi pendaftaran bertahap (Step 1–3), upload berkas, lalu submit.</p>
          <a class="btn btn-primary" href="{{ route('student.dashboard') }}">Buka Dashboard</a>
        </div>
      </div>
      <div class="col">
        <div class="card" style="border-style:dashed">
          <h3 style="margin-top:0">Untuk Admin / Panitia</h3>
          <p style="margin-top:0">Kelola data pendaftar: lihat detail, verifikasi, reject, download berkas.</p>
          <a class="btn" href="{{ route('admin.auth.login.form') }}">Login Admin</a>
        </div>
      </div>
    </div>
  </div>
@endsection
