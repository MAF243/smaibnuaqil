@extends('layouts.admin')
@php($pageTitle = 'Data Pendaftar PPDB')
@section('content')
<div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
  @foreach([
    'draft' => ['Draft', $counts['draft'] ?? 0],
    'submitted' => ['Submitted', $counts['submitted'] ?? 0],
    'under_review' => ['Under Review', $counts['under_review'] ?? 0],
    'interview' => ['Interview', $counts['interview'] ?? 0],
    'accepted' => ['Accepted', $counts['accepted'] ?? 0],
    'rejected' => ['Rejected', $counts['rejected'] ?? 0],
    'completed' => ['Completed', $counts['completed'] ?? 0],
    'incomplete' => ['Incomplete', $counts['incomplete'] ?? 0],
  ] as $key => [$label, $value])
    <div class="admin-kpi"><div class="admin-kpi-label">{{ $label }}</div><div class="mt-3 flex items-center justify-between gap-3"><div class="text-3xl font-black tracking-tight text-white">{{ $value }}</div><x-status-badge :status="$key" /></div></div>
  @endforeach
</div>

<div class="admin-surface p-6 mb-6">
  <div class="mb-5 max-w-3xl"><div class="text-xl font-black tracking-tight text-white">Filter dan pencarian pendaftar</div><p class="mt-1 text-sm text-slate-400">Gunakan filter untuk memudahkan panitia memprioritaskan berkas yang perlu ditinjau lebih dulu.</p></div>
  <form method="get" class="grid gap-4 lg:grid-cols-[1.2fr_.8fr_.7fr_auto]">
    <div>
      <label class="mb-2 block text-sm font-bold text-slate-300">Cari pendaftar</label>
      <input type="text" class="ui-input bg-slate-950 text-white border-slate-700" name="q" value="{{ $q }}" placeholder="Nama, nomor ponsel, atau nomor pendaftaran">
    </div>
    <div>
      <label class="mb-2 block text-sm font-bold text-slate-300">Status</label>
      <select class="ui-select bg-slate-950 text-white border-slate-700" name="status">
        <option value="">Semua status</option>
        @foreach(['draft','submitted','under_review','interview','accepted','rejected','completed','incomplete'] as $st)
          <option value="{{ $st }}" @selected($status === $st)>{{ \App\Support\StudentWorkflow::label($st) }}</option>
        @endforeach
      </select>
    </div>
    <div class="flex items-end">
      <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/55 px-4 py-3 text-sm font-bold text-slate-300">
        <input class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-emerald-500" type="checkbox" name="missing_docs" value="1" {{ $missingDocs ? 'checked' : '' }}>
        Berkas belum lengkap
      </label>
    </div>
    <div class="flex items-end gap-2">
      <x-ui.button type="submit" icon="search">Terapkan</x-ui.button>
      <x-ui.button :href="route('admin.students.index')" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800">Reset</x-ui.button>
      <x-ui.button :href="route('admin.students.export.csv', ['status' => $status, 'missing_docs' => $missingDocs ? 1 : null])" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800" icon="download">CSV</x-ui.button>
    </div>
  </form>
</div>

<div class="admin-surface p-6">
  @if($students->count() === 0)
    <x-empty-state icon="users" title="Belum ada pendaftar yang sesuai" description="Ketika data pendaftaran masuk, daftar akan tampil di tabel ini." />
  @else
    <div class="overflow-x-auto"><table class="ui-table"><thead><tr><th>No Pendaftaran</th><th>Nama Siswa</th><th>Status</th><th>Dokumen</th><th>Submitted</th><th></th></tr></thead><tbody>@foreach($students as $s)<tr><td><div class="font-black text-slate-950">{{ $s->registration_number ?: '#'.$s->id }}</div><div class="text-xs text-slate-500">ID internal {{ $s->id }}</div></td><td><div class="font-black text-slate-950">{{ $s->name }}</div><div class="text-sm text-slate-500">{{ $s->phone }} • Ayah: {{ $s->father_name ?? '-' }}</div></td><td><x-status-badge :status="$s->status_normalized" /></td><td>@if($s->is_documents_complete)<x-status-badge status="valid">Lengkap</x-status-badge>@else<x-status-badge status="incomplete">{{ count($s->missing_documents) }} kurang</x-status-badge>@endif</td><td>{{ optional($s->submitted_at ?: $s->created_at)->format('d-m-Y H:i') }}</td><td><x-ui.button :href="route('admin.students.show', $s->id)" size="sm" variant="secondary" icon="eye">Detail</x-ui.button></td></tr>@endforeach</tbody></table></div>
    <div class="mt-4">{{ $students->links('pagination.simple') }}</div>
  @endif
</div>
@endsection
