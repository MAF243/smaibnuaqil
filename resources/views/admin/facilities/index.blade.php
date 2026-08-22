@extends('layouts.admin')
@php($pageTitle = 'Kelola Fasilitas')
@section('content')
<div class="admin-surface p-6 mb-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xl font-black tracking-tight text-white">Fasilitas Sekolah</div>
      <p class="mt-1 text-sm text-slate-400">Urutan tampil mengikuti <strong>display order</strong>. Semakin kecil angkanya, semakin tinggi posisi fasilitas di website.</p>
    </div>
    <x-ui.button :href="route('admin.facilities.create')" icon="plus">Tambah Fasilitas</x-ui.button>
  </div>
</div>
<div class="admin-surface p-6">
  @if($items->count() === 0)
    <x-empty-state icon="building" title="Belum ada fasilitas" description="Tambahkan fasilitas sekolah agar pengunjung website mendapatkan gambaran sarana belajar yang lebih meyakinkan." action="Tambah Fasilitas" :href="route('admin.facilities.create')" />
  @else
    <div class="overflow-x-auto"><table class="ui-table"><thead><tr><th>ID</th><th>Nama Fasilitas</th><th>Key</th><th>Order</th><th>Status</th><th></th></tr></thead><tbody>@foreach($items as $item)<tr><td>{{ $item->id }}</td><td><div class="font-black text-slate-950">{{ $item->name }}</div><div class="mt-1 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags($item->short_description ?? ''), 110) }}</div></td><td><code class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $item->facility_key }}</code></td><td>{{ $item->display_order }}</td><td><x-status-badge :status="$item->is_published ? 'published' : 'draft'" /></td><td><div class="flex flex-wrap gap-2"><x-ui.button :href="route('admin.facilities.edit', $item->id)" size="sm" variant="secondary" icon="edit">Edit</x-ui.button><form method="post" action="{{ route('admin.facilities.destroy', $item->id) }}" data-loading="Menghapus…">@csrf @method('DELETE')<x-ui.button type="submit" size="sm" variant="danger" icon="trash" data-confirm="Hapus fasilitas ini?">Hapus</x-ui.button></form></div></td></tr>@endforeach</tbody></table></div>
    <div class="mt-4">{{ $items->links('pagination.simple') }}</div>
  @endif
</div>
@endsection
