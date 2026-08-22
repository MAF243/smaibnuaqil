@extends('layouts.app')
@php($title = 'PPDB - Upload Berkas')
@section('content')
  @include('ppdb._stepper', ['current' => 3])
  @php
    $docs = [
      ['field' => 'kk_file', 'label' => 'Kartu Keluarga', 'hint' => 'Wajib • PDF/JPG/PNG • maks. 5MB'],
      ['field' => 'akta_lahir', 'label' => 'Akta Lahir', 'hint' => 'Wajib • PDF/JPG/PNG • maks. 5MB'],
      ['field' => 'nilai_rapor', 'label' => 'Nilai Rapor', 'hint' => 'Wajib • PDF/JPG/PNG • maks. 5MB'],
      ['field' => 'ktp_father', 'label' => 'KTP Ayah', 'hint' => 'Wajib • PDF/JPG/PNG • maks. 5MB'],
      ['field' => 'ktp_mother', 'label' => 'KTP Ibu', 'hint' => 'Wajib • PDF/JPG/PNG • maks. 5MB'],
      ['field' => 'ktp_guardian', 'label' => 'KTP Wali', 'hint' => 'Opsional • PDF/JPG/PNG • maks. 5MB'],
      ['field' => 'kk_guardian', 'label' => 'KK Wali', 'hint' => 'Opsional • PDF/JPG/PNG • maks. 5MB'],
    ];
  @endphp
  <div class="grid gap-6 lg:grid-cols-[1.05fr_.75fr]">
    <div class="ui-card p-6 md:p-8">
      <div class="mb-6 max-w-3xl">
        <div class="ui-kicker"><x-ui.icon name="file-text" class="h-4 w-4" />Step 3</div>
        <h1 class="mt-4 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">Unggah Berkas Pendukung</h1>
        <p class="mt-2 text-sm leading-7 text-slate-600">Unggah dokumen wajib dengan file yang jelas dan terbaca. Jika ada berkas opsional, Anda bisa melengkapinya sekarang atau nanti.</p>
      </div>
      <form method="post" action="{{ route('ppdb.step3.store') }}" enctype="multipart/form-data" data-loading="Mengunggah berkas…" class="space-y-5">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
          @foreach($docs as $d)
            @php($field = $d['field'])
            @php($existing = $student->{$field} ?? null)
            <div class="rounded-[1.25rem] border border-border bg-slate-50 p-4">
              <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon name="file-text" class="h-5 w-5" /></div>
                <div class="min-w-0 flex-1">
                  <div class="text-sm font-black tracking-tight text-slate-950">{{ $d['label'] }}</div>
                  <div class="mt-1 text-xs leading-5 text-slate-500">{{ $d['hint'] }}</div>
                  <div class="mt-4"><input class="ui-input" type="file" name="{{ $field }}" accept=".pdf,.jpg,.jpeg,.png"></div>
                  <x-field-error name="{{ $field }}" />
                  <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    @if($existing)
                      <x-status-badge status="valid" />
                      <a class="ui-btn ui-btn-secondary ui-btn-sm" href="{{ route('ppdb.download', ['studentId' => $student->id, 'column' => $field]) }}"><x-ui.icon name="download" class="h-4 w-4" />Unduh</a>
                    @else
                      <x-status-badge status="incomplete">Belum ada</x-status-badge>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
          <x-ui.button :href="route('ppdb.step2')" variant="secondary" icon="arrow-left">Kembali ke Step 2</x-ui.button>
          <x-ui.button type="submit" icon="upload-cloud">Simpan & Lanjut ke Konfirmasi</x-ui.button>
        </div>
      </form>
    </div>
    <div class="space-y-5">
      <x-ui.card title="Tips upload berkas" description="Agar dokumen mudah diverifikasi oleh panitia.">
        <ul class="space-y-3 text-sm leading-7 text-slate-600">
          <li>• Gunakan hasil scan atau foto yang tidak buram dan tidak terpotong.</li>
          <li>• Pastikan seluruh teks pada dokumen masih dapat dibaca dengan jelas.</li>
          <li>• Jika ukuran file terlalu besar, kompres terlebih dahulu sebelum diunggah.</li>
        </ul>
      </x-ui.card>
      <x-ui.card title="Setelah upload" description="Langkah berikutnya adalah memeriksa ulang data sebelum mengirim pendaftaran secara resmi.">
        <div class="text-sm leading-7 text-slate-600">Setelah semua dokumen diperiksa, Anda akan masuk ke halaman konfirmasi untuk meninjau data siswa, orang tua/wali, serta kelengkapan berkas sebelum submit akhir.</div>
      </x-ui.card>
    </div>
  </div>
@endsection
