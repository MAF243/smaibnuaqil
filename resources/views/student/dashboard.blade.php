@extends('layouts.app')
@php($title = 'Dashboard Pendaftaran')
@section('content')
<div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
  <div>
    <div class="ui-kicker"><x-ui.icon name="layout-dashboard" class="h-4 w-4" />Dashboard Pendaftar</div>
    <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 md:text-4xl">Pantau proses PPDB Anda di satu tempat</h1>
    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">Dashboard ini merangkum status terbaru, nomor pendaftaran, kelengkapan berkas, timeline perubahan status, serta informasi jadwal dan hasil seleksi.</p>
  </div>
  <a class="ui-btn ui-btn-secondary" href="{{ route('portal') }}"><x-ui.icon name="arrow-left" class="h-4 w-4" />Kembali ke Portal</a>
</div>
@if(!$student)
  <div class="ui-card p-6"><x-empty-state icon="clipboard-check" title="Belum ada data pendaftaran" description="Mulai dari Step 1 untuk membuat data calon siswa. Sistem akan menyimpan progres Anda secara bertahap." action="Mulai Isi Formulir" :href="route('ppdb.step1')" /></div>
@else
  <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat-card label="Nomor Pendaftaran" :value="$student->registration_number ?: 'Belum dibuat'" icon="file-text" description="Nomor dibuat otomatis setelah submit akhir." />
    <x-ui.stat-card label="Status Saat Ini" :value="$student->status_label" icon="badge-check" description="Pantau perubahan status secara berkala." />
    <x-ui.stat-card label="Progress Form" :value="$student->progress_percent.'%'" icon="activity" description="Semakin lengkap data, semakin siap untuk diproses." />
    <x-ui.stat-card label="Kelengkapan Berkas" :value="$student->is_documents_complete ? 'Lengkap' : count($student->missing_documents).' kurang'" icon="file-text" description="Periksa dokumen wajib agar tidak tertunda." />
  </div>
  <div class="grid gap-6 lg:grid-cols-[1.3fr_.8fr]">
    <div class="space-y-6">
      <x-ui.card title="Timeline Status PPDB" description="Riwayat pembaruan status dari panitia akan ditampilkan di sini.">
        @if($student->statusHistories->isEmpty())
          <x-empty-state icon="clock" title="Belum ada riwayat status" description="Timeline akan muncul setelah ada perubahan status dari sistem atau panitia." />
        @else
          <div class="space-y-4">
            @foreach($student->statusHistories as $history)
              <div class="timeline-line pl-5">
                <div class="flex items-start gap-4">
                  <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><x-ui.icon name="check-circle" class="h-5 w-5" /></div>
                  <div class="min-w-0 flex-1 pb-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                      <div class="font-black text-slate-950">{{ \App\Support\StudentWorkflow::label($history->to_status) }}</div>
                      <div class="text-xs font-semibold text-slate-500">{{ optional($history->created_at)->format('d M Y H:i') }}</div>
                    </div>
                    <div class="mt-1 text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Aktor: {{ ucfirst($history->actor_type) }}</div>
                    @if($history->note)<p class="mt-2 text-sm leading-7 text-slate-600">{{ $history->note }}</p>@endif
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </x-ui.card>

      <x-ui.card title="Hasil Seleksi" description="Informasi hasil seleksi akan diperbarui pada bagian ini.">
        @if(in_array($student->status_normalized, ['accepted','rejected','completed']))
          <x-alert :type="$student->status_normalized === 'rejected' ? 'danger' : 'success'" :title="$student->status_normalized === 'rejected' ? 'Status Belum Lolos' : 'Hasil Seleksi Sudah Tersedia'">
            <div>Status saat ini: <strong>{{ $student->status_label }}</strong></div>
            @if($student->result_note)<div class="mt-2">Catatan panitia: {{ $student->result_note }}</div>@endif
            @if($student->rejection_reason)<div class="mt-2">Alasan: {{ $student->rejection_reason }}</div>@endif
          </x-alert>
        @else
          <x-alert type="info" title="Belum dipublikasikan">Hasil seleksi belum diumumkan. Silakan pantau dashboard ini secara berkala.</x-alert>
        @endif
      </x-ui.card>
    </div>

    <aside class="space-y-6">
      <x-ui.card title="Jadwal Interview / Tes" description="Jadwal yang ditetapkan panitia akan muncul di sini.">
        @if($student->interview)
          <div class="space-y-4 text-sm leading-7 text-slate-600">
            <div><div class="text-xs font-black uppercase tracking-[.16em] text-slate-400">Tanggal & Jam</div><div class="mt-1 font-semibold text-slate-900">{{ optional($student->interview->scheduled_at)->format('d M Y H:i') }}</div></div>
            <div><div class="text-xs font-black uppercase tracking-[.16em] text-slate-400">Lokasi</div><div class="mt-1 font-semibold text-slate-900">{{ $student->interview->location ?: '-' }}</div></div>
            @if($student->interview->meeting_link)
              <x-ui.button :href="$student->interview->meeting_link" target="_blank" variant="secondary" size="sm" icon="arrow-right">Buka Link Meeting</x-ui.button>
            @endif
          </div>
        @else
          <x-empty-state icon="calendar" title="Belum ada jadwal" description="Jadwal tes atau interview akan ditambahkan panitia apabila diperlukan." />
        @endif
      </x-ui.card>

      <x-ui.card title="Dokumen Wajib" description="Periksa kembali kelengkapan berkas yang sudah diunggah.">
        <div class="space-y-3">
          @foreach($student->required_document_map as $col => $label)
            @php($ok = !empty($student->{$col}))
            <div class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-slate-50 p-3">
              <div class="flex items-center gap-2 text-sm font-bold text-slate-900"><x-ui.icon  :name="$ok ? 'check-circle' : 'circle-alert'" class="h-4 w-4 {{ $ok ? 'text-emerald-600' : 'text-amber-600' }}" />{{ $label }}</div>
              @if($ok)
                <a class="ui-btn ui-btn-secondary ui-btn-sm" href="{{ route('ppdb.download', [$student->id, $col]) }}">Unduh</a>
              @else
                <span class="text-xs font-semibold text-slate-500">Belum ada</span>
              @endif
            </div>
          @endforeach
        </div>
      </x-ui.card>

      <x-ui.card title="Notifikasi" description="Pemberitahuan dari panitia dan sistem akan muncul di sini.">
        @if(($notifications ?? collect())->isEmpty())
          <x-empty-state icon="bell" title="Belum ada notifikasi" description="Notifikasi penting terkait status, berkas, dan jadwal akan tampil di dashboard ini." />
        @else
          <div class="space-y-3">
            @foreach($notifications as $item)
              <div class="rounded-2xl border border-border p-4 {{ $item->is_read ? 'bg-white' : 'bg-emerald-50/60' }}">
                <div class="flex justify-between gap-3">
                  <div class="font-black text-slate-950">{{ $item->title }}</div>
                  <div class="text-xs font-semibold text-slate-500">{{ optional($item->created_at)->format('d M H:i') }}</div>
                </div>
                @if($item->message)<p class="mt-2 text-sm leading-7 text-slate-600">{{ $item->message }}</p>@endif
              </div>
            @endforeach
          </div>
        @endif
      </x-ui.card>
    </aside>
  </div>
@endif
@endsection
