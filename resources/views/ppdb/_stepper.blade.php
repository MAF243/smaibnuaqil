@php
  $current = $current ?? ($step ?? 1);
  $steps = [1=>['label'=>'Data Siswa','desc'=>'Informasi dasar calon siswa','icon'=>'id-card'],2=>['label'=>'Orang Tua / Wali','desc'=>'Data keluarga & wali','icon'=>'users'],3=>['label'=>'Berkas','desc'=>'Unggah dokumen pendukung','icon'=>'paperclip'],4=>['label'=>'Konfirmasi','desc'=>'Periksa lalu kirim formulir','icon'=>'check-circle']];
@endphp
<div class="ui-card mb-6 p-5 md:p-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="ui-kicker"><x-ui.icon name="file-text" class="h-4 w-4" />Formulir PPDB Online</div>
      <div class="mt-3 text-2xl font-black tracking-tight text-slate-950">Lengkapi proses pendaftaran secara bertahap</div>
      <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">Setiap tahap dapat disimpan terlebih dahulu. Anda bisa kembali lagi ke dashboard untuk melanjutkan tanpa harus mengulang dari awal.</p>
    </div>
    <a href="{{ route('student.dashboard') }}" class="ui-btn ui-btn-secondary ui-btn-sm"><x-ui.icon name="layout-dashboard" class="h-4 w-4" />Dashboard</a>
  </div>
  <div class="mt-6 grid gap-3 md:grid-cols-4">
    @foreach($steps as $i => $st)
      @php($state = $i === $current ? 'active' : ($i < $current ? 'done' : 'todo'))
      <div class="rounded-[1.25rem] border p-4 {{ $state==='active' ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : ($state==='done' ? 'border-emerald-200 bg-emerald-50/70 text-emerald-800' : 'border-border bg-slate-50 text-slate-500') }}">
        <div class="flex items-start gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $state==='todo' ? 'bg-white ring-1 ring-slate-200' : 'bg-primary text-white' }}">
            @if($state==='done')<x-ui.icon name="check" class="h-4 w-4" />@else<x-ui.icon  :name="$st['icon']" class="h-4 w-4" />@endif
          </div>
          <div>
            <div class="text-[11px] font-black uppercase tracking-[.16em]">Step {{ $i }}</div>
            <div class="mt-1 text-sm font-black">{{ $st['label'] }}</div>
            <div class="mt-1 text-xs leading-5 opacity-80">{{ $st['desc'] }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
