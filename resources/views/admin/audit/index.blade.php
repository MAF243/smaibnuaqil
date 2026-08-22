@extends('layouts.admin')

@php($pageTitle = 'Audit Log')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Audit Log</h1>
    <div class="text-muted-2">Jejak aktivitas penting admin dan sistem.</div>
  </div>
</div>

<div class="card card-soft p-3 mb-3">
  <form class="row g-2" method="get">
    <div class="col-md-5">
      <input class="form-control" type="text" name="q" value="{{ $q }}" placeholder="Cari action, summary, target...">
    </div>
    <div class="col-md-3">
      <select class="form-select" name="module">
        <option value="">Semua modul</option>
        @foreach($modules as $m)
          <option value="{{ $m }}" @selected($module === $m)>{{ ucfirst($m) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button class="btn btn-brand" type="submit">Filter</button>
      <a class="btn btn-soft" href="{{ route('admin.audit.index') }}">Reset</a>
    </div>
  </form>
</div>

<div class="card card-soft p-0 overflow-hidden">
  @if($items->isEmpty())
    <div class="p-4">
      <x-empty-state icon="shield-check" title="Belum ada audit log" description="Aktivitas admin akan tercatat di sini setelah fitur v3 berjalan." />
    </div>
  @else
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Waktu</th>
            <th>Modul</th>
            <th>Action</th>
            <th>Target</th>
            <th>Ringkasan</th>
            <th>Aktor</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $item)
            <tr>
              <td class="small text-nowrap">{{ optional($item->created_at)->format('d M Y H:i') }}</td>
              <td><span class="badge text-bg-dark border">{{ $item->module ?: '-' }}</span></td>
              <td class="small">{{ $item->action }}</td>
              <td class="small">{{ $item->target_type ? class_basename($item->target_type).' #'.$item->target_id : '-' }}</td>
              <td>{{ $item->summary ?: '-' }}</td>
              <td class="small">{{ $item->actor_account_id ?: '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $items->links() }}</div>
  @endif
</div>
@endsection
