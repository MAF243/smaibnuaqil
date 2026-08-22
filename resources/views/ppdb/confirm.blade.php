@extends('layouts.app')
@php($title = 'PPDB - Konfirmasi Data')
@section('content')
  @include('ppdb._stepper', ['current' => 4])
  @php
    $requiredDocs = [
      'kk_file' => 'Kartu Keluarga',
      'akta_lahir' => 'Akta Lahir',
      'nilai_rapor' => 'Nilai Rapor',
      'ktp_father' => 'KTP Ayah',
      'ktp_mother' => 'KTP Ibu',
    ];
    $missing = [];
    foreach($requiredDocs as $k => $label){ if(empty($student->{$k})) $missing[] = $label; }
  @endphp
  <div class="ui-card p-6 md:p-8">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div>
        <div class="ui-kicker"><x-ui.icon name="check-circle" class="h-4 w-4" />Tahap Akhir</div>
        <h1 class="mt-4 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">Konfirmasi Data Pendaftaran</h1>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">Tinjau kembali seluruh data sebelum mengirim formulir. Setelah submit, nomor pendaftaran akan dipakai sebagai referensi utama untuk proses selanjutnya.</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-slate-500">Status saat ini</span>
        <x-status-badge :status="$student->status_normalized" />
      </div>
    </div>

    @if(count($missing) > 0)
      <x-alert type="warning" title="Masih ada berkas yang belum lengkap">Dokumen berikut belum tersedia: <strong>{{ implode(', ', $missing) }}</strong>. Anda tetap dapat submit, tetapi panitia kemungkinan akan meminta pelengkapan pada tahap verifikasi.</x-alert>
    @endif

    <div class="grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
      <x-ui.card title="Ringkasan Data Siswa" description="Pastikan biodata sudah sesuai dokumen resmi." padding="p-0">
        <div class="overflow-x-auto"><table class="ui-table"><tbody>
          <tr><th>Nama Lengkap</th><td>{{ $student->name }}</td></tr>
          <tr><th>Tempat, Tanggal Lahir</th><td>{{ $student->birthplace ?? '-' }}, {{ $student->dob?->format('d-m-Y') }}</td></tr>
          <tr><th>Nomor Ponsel</th><td>{{ $student->phone }}</td></tr>
          <tr><th>Alamat</th><td>{{ $student->address }}</td></tr>
          <tr><th>Jenis Kelamin</th><td>{{ $student->gender ?? '-' }}</td></tr>
          <tr><th>Agama</th><td>{{ $student->religion_child ?? '-' }}</td></tr>
          <tr><th>Motivasi</th><td>{{ $student->motivation ?? '-' }}</td></tr>
        </tbody></table></div>
      </x-ui.card>
      <x-ui.card title="Ringkasan Orang Tua / Wali" description="Data keluarga akan dipakai untuk keperluan verifikasi dan komunikasi." padding="p-0">
        <div class="overflow-x-auto"><table class="ui-table"><tbody>
          <tr><th>Ayah</th><td>{{ $student->father_name ?? '-' }} <span class="text-slate-500">({{ $student->father_phone ?? '-' }})</span></td></tr>
          <tr><th>Ibu</th><td>{{ $student->mother_name ?? '-' }} <span class="text-slate-500">({{ $student->mother_phone ?? '-' }})</span></td></tr>
          <tr><th>Wali</th><td>{{ $student->guardian_name ?? '-' }} <span class="text-slate-500">({{ $student->guardian_phone ?? '-' }})</span></td></tr>
        </tbody></table></div>
      </x-ui.card>
    </div>

    <x-ui.card title="Checklist Berkas" description="Berikut ringkasan kelengkapan dokumen yang telah diunggah." class="mt-5">
      <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
        @foreach($requiredDocs as $k => $label)
          <div class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-slate-50 p-3">
            <div class="inline-flex items-center gap-2 text-sm font-bold text-slate-900">
              <x-ui.icon name="{{ !empty($student->{$k}) ? 'check-circle' : 'circle-alert' }}" class="h-4 w-4 {{ !empty($student->{$k}) ? 'text-emerald-600' : 'text-amber-600' }}" />{{ $label }}
            </div>
            <x-status-badge :status="!empty($student->{$k}) ? 'valid' : 'incomplete'" />
          </div>
        @endforeach
      </div>
      <div class="mt-5 flex flex-wrap gap-3">
        <x-ui.button :href="route('ppdb.step1')" variant="secondary" icon="edit">Perbaiki Step 1</x-ui.button>
        <x-ui.button :href="route('ppdb.step2')" variant="secondary" icon="edit">Perbaiki Step 2</x-ui.button>
        <x-ui.button :href="route('ppdb.step3')" variant="secondary" icon="edit">Kelola Berkas</x-ui.button>
      </div>
    </x-ui.card>

    <div class="mt-6 flex flex-wrap gap-3">
      <x-ui.button :href="route('ppdb.step3')" variant="secondary" icon="arrow-left">Kembali ke Step 3</x-ui.button>
      <form method="post" action="{{ route('ppdb.submit') }}" data-loading="Mengirim pendaftaran…" class="m-0">@csrf<x-ui.button type="submit" icon="send" data-confirm="Yakin submit pendaftaran sekarang?">Submit Pendaftaran Sekarang</x-ui.button></form>
    </div>
  </div>
@endsection
