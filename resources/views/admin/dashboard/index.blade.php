@extends('layouts.admin')
@php($pageTitle = 'Dashboard Admin')
@section('content')
<div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
  @foreach([
    ['Total Pendaftar',$summary['total'],'users','Semua data yang masuk ke sistem.'],
    ['Submitted Hari Ini',$summary['submitted_today'],'send','Pendaftaran baru yang dikirim hari ini.'],
    ['Review + Interview',$summary['under_review'] + $summary['interview'],'clipboard-check','Data yang masih aktif diproses.'],
    ['Accepted / Rejected',$summary['accepted'].' / '.$summary['rejected'],'badge-check','Ringkasan hasil seleksi saat ini.'],
  ] as $card)
    <div class="admin-kpi">
      <div class="flex items-center justify-between gap-4"><div class="admin-kpi-label">{{ $card[0] }}</div><div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/12 text-emerald-300"><x-ui.icon name="{{ $card[2] }}" class="h-5 w-5" /></div></div>
      <div class="admin-kpi-value">{{ $card[1] }}</div>
      <div class="mt-2 text-xs font-semibold leading-6 text-slate-400">{{ $card[3] }}</div>
    </div>
  @endforeach
</div>

<div class="grid gap-6 lg:grid-cols-[1.4fr_.8fr]">
  <div class="admin-surface p-6">
    <div class="mb-5 flex items-center justify-between gap-3"><div><div class="text-xl font-black tracking-tight text-white">Pendaftaran 7 Hari Terakhir</div><p class="mt-1 text-sm text-slate-400">Grafik ini membantu membaca tren pendaftar secara cepat.</p></div></div>
    <div class="flex h-64 items-end gap-3">
      @php($max = max(1, $weeklyChart->max('value')))
      @foreach($weeklyChart as $bar)
        <div class="flex flex-1 flex-col items-center gap-2">
          <div class="w-full rounded-t-2xl bg-gradient-to-t from-emerald-600 to-emerald-300" style="height: {{ max(14, ($bar['value'] / $max) * 210) }}px"></div>
          <div class="text-xs font-bold text-slate-300">{{ $bar['label'] }}</div>
          <div class="text-xs text-slate-500">{{ $bar['value'] }}</div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="admin-surface p-6">
    <div class="mb-5 text-xl font-black tracking-tight text-white">Ringkasan Status</div>
    <div class="space-y-2">
      @foreach(['under_review'=>'Under Review','interview'=>'Interview','accepted'=>'Accepted','rejected'=>'Rejected','completed'=>'Completed'] as $key=>$label)
        <div class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-950/55 p-3">
          <span class="font-bold text-slate-300">{{ $label }}</span>
          <strong class="text-white">{{ $summary[$key] }}</strong>
        </div>
      @endforeach
    </div>
  </div>

  <div class="admin-surface p-6">
    <div class="mb-5 flex items-center justify-between gap-3"><div><div class="text-xl font-black tracking-tight text-white">Pendaftar Terbaru</div><p class="mt-1 text-sm text-slate-400">Gunakan daftar ini untuk memulai verifikasi harian.</p></div><x-ui.button :href="route('admin.students.index')" size="sm" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800">Lihat Semua</x-ui.button></div>
    @if($recentStudents->isEmpty())
      <x-empty-state icon="users" title="Belum ada pendaftar" description="Ketika data masuk, daftar pendaftar terbaru akan tampil di sini." />
    @else
      <div class="overflow-x-auto"><table class="ui-table"><thead><tr><th>No Pendaftaran</th><th>Nama</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>@foreach($recentStudents as $s)<tr><td>{{ $s->registration_number ?: '#'.$s->id }}</td><td><a href="{{ route('admin.students.show', $s->id) }}" class="font-bold text-slate-900 hover:text-emerald-700">{{ $s->name }}</a></td><td><x-status-badge :status="$s->status_normalized" /></td><td>{{ optional($s->submitted_at ?: $s->created_at)->format('d M Y') }}</td></tr>@endforeach</tbody></table></div>
    @endif
  </div>

  <div class="admin-surface p-6">
    <div class="mb-5 flex items-center justify-between gap-3"><div><div class="text-xl font-black tracking-tight text-white">Notifikasi Admin</div><p class="mt-1 text-sm text-slate-400">Ringkasan kejadian yang perlu diperhatikan panitia.</p></div><x-ui.button :href="route('admin.notifications.index')" size="sm" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800">Lihat Semua</x-ui.button></div>
    @forelse($notifications as $item)
      <div class="mb-3 rounded-2xl border border-slate-800 bg-slate-950/55 p-4">
        <div class="flex justify-between gap-3"><div class="font-black text-white">{{ $item->title }}</div><div class="text-xs text-slate-500">{{ optional($item->created_at)->format('d M H:i') }}</div></div>
        @if($item->message)<p class="mt-2 text-sm leading-7 text-slate-300">{{ $item->message }}</p>@endif
      </div>
    @empty
      <x-empty-state icon="bell" title="Belum ada notifikasi" description="Notifikasi admin akan muncul setelah ada aktivitas penting di sistem." />
    @endforelse
  </div>

  <div class="admin-surface p-6 lg:col-span-2">
    <div class="mb-5 text-xl font-black tracking-tight text-white">Audit Log Terakhir</div>
    @if($recentActivities->isEmpty())
      <x-empty-state icon="activity" title="Belum ada log aktivitas" description="Aktivitas admin akan tercatat otomatis setelah ada perubahan data." />
    @else
      <div class="overflow-x-auto"><table class="ui-table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th></tr></thead><tbody>@foreach($recentActivities as $log)<tr><td>{{ optional($log->created_at)->format('d M Y H:i') }}</td><td>{{ $log->action }}</td><td>{{ $log->description ?: '-' }}</td></tr>@endforeach</tbody></table></div>
    @endif
  </div>
</div>
@endsection
