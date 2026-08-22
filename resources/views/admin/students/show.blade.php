@extends('layouts.admin')
@php($pageTitle = 'Detail Pendaftar')
@section('content')
<div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <x-ui.button :href="route('admin.students.index')" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" icon="arrow-left">Kembali ke daftar</x-ui.button>
  <div class="flex items-center gap-3"><span class="text-sm font-semibold text-slate-400">{{ $student->registration_number ?: '#'.$student->id }}</span><x-status-badge :status="$student->status_normalized" /></div>
</div>
<div class="grid gap-6 lg:grid-cols-[1.05fr_.85fr]">
  <div class="space-y-6">
    <x-ui.card title="Data Siswa" description="Ringkasan biodata yang dikirim pendaftar.">
      <div class="overflow-x-auto"><table class="ui-table"><tbody>
        <tr><th>Nama Lengkap</th><td>{{ $student->name }}</td></tr>
        <tr><th>Tempat / Tanggal Lahir</th><td>{{ $student->birthplace ?? '-' }} / {{ $student->dob?->format('d-m-Y') }}</td></tr>
        <tr><th>No. HP</th><td>{{ $student->phone }}</td></tr>
        <tr><th>Alamat</th><td>{{ $student->address }}</td></tr>
        <tr><th>Status</th><td>{{ $student->status_label }}</td></tr>
        <tr><th>Tanggal Submit</th><td>{{ optional($student->submitted_at ?: $student->created_at)->format('d-m-Y H:i') }}</td></tr>
        <tr><th>Motivasi</th><td>{{ $student->motivation ?? '-' }}</td></tr>
      </tbody></table></div>
    </x-ui.card>

    <x-ui.card title="Data Orang Tua / Wali" description="Informasi keluarga untuk kebutuhan verifikasi dan komunikasi.">
      <div class="overflow-x-auto"><table class="ui-table"><tbody>
        <tr><th>Ayah</th><td>{{ $student->father_name ?? '-' }} ({{ $student->father_phone ?? '-' }})</td></tr>
        <tr><th>Ibu</th><td>{{ $student->mother_name ?? '-' }} ({{ $student->mother_phone ?? '-' }})</td></tr>
        <tr><th>Wali</th><td>{{ $student->guardian_name ?? '-' }} ({{ $student->guardian_phone ?? '-' }})</td></tr>
      </tbody></table></div>
    </x-ui.card>

    <x-ui.card title="Timeline Status" description="Riwayat perubahan status membantu admin dan pendaftar melihat alur proses dengan lebih jelas.">
      @if($student->statusHistories->isEmpty())
        <x-empty-state icon="clock" title="Belum ada timeline" description="Riwayat perubahan status akan muncul setelah admin atau sistem memperbarui status pendaftaran." />
      @else
        <div class="space-y-4">@foreach($student->statusHistories as $history)<div class="timeline-line pl-5"><div class="flex gap-4"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><x-ui.icon name="activity" class="h-5 w-5" /></div><div class="min-w-0 flex-1 pb-4"><div class="flex flex-wrap items-center justify-between gap-2"><div class="font-black text-slate-950">{{ \App\Support\StudentWorkflow::label($history->to_status) }}</div><div class="text-xs font-semibold text-slate-500">{{ optional($history->created_at)->format('d M Y H:i') }}</div></div><div class="mt-1 text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Aktor: {{ ucfirst($history->actor_type) }}</div>@if($history->note)<div class="mt-2 text-sm leading-7 text-slate-600">{{ $history->note }}</div>@endif</div></div></div>@endforeach</div>
      @endif
    </x-ui.card>
  </div>

  <div class="space-y-6">
    @php($docs = $student->required_document_map + ['ktp_guardian' => 'KTP Wali', 'kk_guardian' => 'KK Wali'])
    <x-ui.card title="Dokumen Pendaftar" description="Admin dapat memeriksa dan mengunduh berkas dari bagian ini.">
      <div class="space-y-3">@foreach($docs as $col => $label)@php($exists = !empty($student->{$col}))<div class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-slate-50 p-3"><div class="flex items-center gap-2"><x-ui.icon  :name="$exists ? 'check-circle' : 'circle-alert'" class="h-4 w-4 {{ $exists ? 'text-emerald-600' : 'text-amber-600' }}" /><div class="text-sm font-bold text-slate-950">{{ $label }}</div></div>@if($exists)<x-ui.button :href="route('admin.students.download', [$student->id, $col])" size="sm" variant="secondary" icon="download">Unduh</x-ui.button>@else<span class="text-xs font-semibold text-slate-500">Belum ada</span>@endif</div>@endforeach</div>
    </x-ui.card>

    <x-ui.card title="Update Status PPDB" description="Perubahan status akan terlihat di dashboard pendaftar dan tercatat pada timeline.">
      <form method="post" action="{{ route('admin.students.status.update', $student->id) }}" data-loading="Menyimpan status…" class="space-y-4">
        @csrf
        <label class="block"><span class="mb-2 block text-sm font-bold text-slate-700">Status baru</span><select class="ui-select" name="status">@foreach($availableStatuses as $st)<option value="{{ $st }}" @selected($student->status_normalized === $st)>{{ \App\Support\StudentWorkflow::label($st) }}</option>@endforeach</select></label>
        <x-ui.textarea label="Catatan Status / Alasan" name="note" :value="old('note', $student->last_status_note ?: $student->rejection_reason)" rows="3" hint="Catatan ini dapat membantu pendaftar memahami tindak lanjut yang diperlukan." />
        <x-ui.textarea label="Catatan Hasil Seleksi" name="result_note" :value="old('result_note', $student->result_note)" rows="3" />
        <x-ui.button type="submit" icon="check-circle">Simpan Perubahan Status</x-ui.button>
      </form>
    </x-ui.card>

    <x-ui.card title="Jadwal Interview / Tes" description="Tetapkan jadwal lanjutan apabila proses seleksi membutuhkannya.">
      <form method="post" action="{{ route('admin.students.interview.schedule', $student->id) }}" data-loading="Menyimpan jadwal…" class="space-y-4">
        @csrf
        <x-ui.field label="Tanggal & Jam" name="scheduled_at" type="datetime-local" :value="old('scheduled_at', optional($student->interview?->scheduled_at)->format('Y-m-d\TH:i'))" />
        <x-ui.field label="Lokasi" name="location" :value="old('location', $student->interview?->location)" />
        <x-ui.field label="Link Meeting" name="meeting_link" :value="old('meeting_link', $student->interview?->meeting_link)" />
        <x-ui.textarea label="Catatan" name="notes" :value="old('notes', $student->interview?->notes)" rows="3" />
        <x-ui.button type="submit" variant="secondary" icon="calendar">Simpan Jadwal</x-ui.button>
      </form>
      @if($student->interview)
        <div class="mt-5 border-t border-border pt-5">
          <form method="post" action="{{ route('admin.students.interview.attendance', $student->id) }}" class="space-y-4">
            @csrf
            <label class="block"><span class="mb-2 block text-sm font-bold text-slate-700">Status Kehadiran</span><select class="ui-select" name="attendance_status">@foreach(['scheduled' => 'Scheduled', 'present' => 'Present', 'absent' => 'Absent', 'rescheduled' => 'Rescheduled'] as $val => $label)<option value="{{ $val }}" @selected($student->interview->attendance_status === $val)>{{ $label }}</option>@endforeach</select></label>
            <x-ui.button type="submit" variant="secondary" icon="badge-check">Update Kehadiran</x-ui.button>
          </form>
        </div>
      @endif
    </x-ui.card>
  </div>
</div>
@endsection
