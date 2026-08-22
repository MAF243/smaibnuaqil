@extends('layouts.admin')
@php($pageTitle = 'Kelola Berita')
@section('content')
<div class="admin-surface p-6 mb-6">
  <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
    <div>
      <div class="text-xl font-black tracking-tight text-white">Manajemen Berita Sekolah</div>
      <p class="mt-1 text-sm text-slate-400">Gunakan halaman ini untuk menjaga website tetap aktif dengan berita, pengumuman, dan publikasi kegiatan.</p>
    </div>
    <x-ui.button :href="route('admin.news.create')" icon="plus">Tambah Berita</x-ui.button>
  </div>
  <form method="get" action="{{ route('admin.news.index') }}" class="grid gap-4 lg:grid-cols-[1fr_auto]">
    <div>
      <label class="mb-2 block text-sm font-bold text-slate-300">Cari berita</label>
      <input type="text" name="q" value="{{ $q }}" class="ui-input bg-slate-950 text-white border-slate-700" placeholder="Cari judul atau isi berita...">
    </div>
    <div class="flex items-end gap-2"><x-ui.button type="submit" icon="search">Cari</x-ui.button><x-ui.button :href="route('admin.news.index')" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800">Reset</x-ui.button></div>
  </form>
</div>
<div class="admin-surface p-6">
  @if($items->count() === 0)
    <x-empty-state icon="newspaper" title="Belum ada berita" description="Mulailah dengan menambahkan satu berita utama agar website terlihat lebih aktif dan meyakinkan." action="Tambah Berita" :href="route('admin.news.create')" />
  @else
    <div class="overflow-x-auto"><table class="ui-table"><thead><tr><th>ID</th><th>Judul</th><th>Status</th><th>Tanggal</th><th></th></tr></thead><tbody>@foreach($items as $item)<tr><td>{{ $item->id }}</td><td><div class="font-black text-slate-950">{{ $item->title_normalized }}</div><div class="mt-1 text-sm text-slate-500">{{ $item->excerpt }}</div></td><td><x-status-badge :status="$item->is_published ? 'published' : 'draft'" /></td><td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td><td><div class="flex flex-wrap gap-2"><x-ui.button :href="route('admin.news.edit', $item->id)" size="sm" variant="secondary" icon="edit">Edit</x-ui.button><form method="post" action="{{ route('admin.news.destroy', $item->id) }}" data-loading="Menghapus…">@csrf @method('DELETE')<x-ui.button type="submit" size="sm" variant="danger" icon="trash" data-confirm="Hapus berita ini?">Hapus</x-ui.button></form></div></td></tr>@endforeach</tbody></table></div>
    <div class="mt-4">{{ $items->links('pagination.simple') }}</div>
  @endif
</div>
@endsection
