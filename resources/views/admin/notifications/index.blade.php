@extends('layouts.admin')

@php($pageTitle = 'Notifikasi Admin')

@section('content')
<div class="admin-card p-4">
  @if($items->isEmpty())
    <x-empty-state icon="bell" title="Belum ada notifikasi" description="Notifikasi admin akan tampil di sini." />
  @else
    <div class="d-grid gap-2">
      @foreach($items as $item)
        <div class="border rounded-3 p-3" style="border-color:#1f2a44 !important;background:{{ $item->is_read ? '#0b1220' : 'rgba(96,165,250,.08)' }};">
          <div class="d-flex justify-content-between gap-2">
            <div class="fw-semibold">{{ $item->title }}</div>
            <div class="small admin-muted">{{ optional($item->created_at)->format('d M Y H:i') }}</div>
          </div>
          @if($item->message)<div class="small mt-1">{{ $item->message }}</div>@endif
          <div class="mt-2 d-flex gap-2">
            @if($item->link)<a class="btn btn-soft btn-sm" href="{{ $item->link }}">Buka</a>@endif
            @unless($item->is_read)
              <form method="post" action="{{ route('admin.notifications.read', $item->id) }}">@csrf<button class="btn btn-ghost btn-sm">Tandai dibaca</button></form>
            @endunless
          </div>
        </div>
      @endforeach
    </div>
    <div class="mt-3">{{ $items->links('pagination.simple') }}</div>
  @endif
</div>
@endsection
