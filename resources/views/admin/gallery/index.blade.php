@extends('layouts.admin')
@php($pageTitle = 'Kelola Galeri')
@section('content')
<div class="admin-surface p-6 mb-6">
  <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
    <div>
      <div class="text-xl font-black tracking-tight text-white">Galeri Kegiatan Sekolah</div>
      <p class="mt-1 text-sm text-slate-400">Kelola dokumentasi visual agar website terlihat aktif, aktual, dan meyakinkan bagi orang tua maupun calon siswa.</p>
    </div>
    <x-ui.button :href="route('admin.gallery.create')" icon="plus">Tambah Foto</x-ui.button>
  </div>
  <form method="get" action="{{ route('admin.gallery.index') }}" class="grid gap-4 lg:grid-cols-[1fr_auto]">
    <div>
      <label class="mb-2 block text-sm font-bold text-slate-300">Cari galeri</label>
      <input type="text" name="q" value="{{ $q }}" class="ui-input bg-slate-950 text-white border-slate-700" placeholder="Judul atau deskripsi foto...">
    </div>
    <div class="flex items-end gap-2"><x-ui.button type="submit" icon="search">Cari</x-ui.button><x-ui.button :href="route('admin.gallery.index')" variant="outline" class="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800">Reset</x-ui.button></div>
  </form>
</div>
<div class="admin-surface p-6">
  @if($items->count() === 0)
    <x-empty-state icon="images" title="Galeri masih kosong" description="Upload dokumentasi kegiatan agar website terlihat lebih hidup dan profesional." action="Tambah Foto" :href="route('admin.gallery.create')" />
  @else
    <div class="overflow-x-auto"><table class="ui-table"><thead><tr><th>ID</th><th>Preview</th><th>Informasi</th><th>Status</th><th>Urutan</th><th>Tanggal</th><th></th></tr></thead><tbody>@foreach($items as $item)<tr><td>{{ $item->id }}</td><td>@if($item->image_path)<img src="{{ asset($item->image_path) }}" alt="" class="h-16 w-24 rounded-xl border border-slate-200 object-cover">@else<span class="text-slate-400">—</span>@endif</td><td><div class="font-black text-slate-950">{{ $item->title ?: 'Tanpa judul' }}</div><div class="mt-1 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags($item->description ?? ''), 110) }}</div></td><td><x-status-badge :status="$item->is_published ? 'published' : 'draft'" /></td><td>{{ $item->sort_order ?? 0 }}</td><td>{{ optional($item->uploaded_at)->format('d/m/Y H:i') }}</td><td><div class="flex flex-wrap gap-2"><x-ui.button :href="route('admin.gallery.edit', $item->id)" size="sm" variant="secondary" icon="edit">Edit</x-ui.button><form method="post" action="{{ route('admin.gallery.destroy', $item->id) }}" data-loading="Menghapus…">@csrf @method('DELETE')<x-ui.button type="submit" size="sm" variant="danger" icon="trash" data-confirm="Hapus foto ini?">Hapus</x-ui.button></form></div></td></tr>@endforeach</tbody></table></div>
    <div class="mt-4">{{ $items->links('pagination.simple') }}</div>
  @endif
</div>
@endsection
