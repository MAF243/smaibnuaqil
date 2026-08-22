@extends('layouts.admin')

@php($pageTitle = 'Halaman Website')

@section('content')
<div class="admin-card p-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle mb-0" style="--bs-table-bg:transparent;--bs-table-border-color:#1f2a44">
      <thead><tr><th>Key</th><th>Judul</th><th>Status</th><th style="width:120px">Aksi</th></tr></thead>
      <tbody>
        @foreach($items as $item)
          <tr>
            <td><code>{{ $item->page_key }}</code></td>
            <td>
              <div class="fw-semibold">{{ $item->title }}</div>
              <div class="small admin-muted">{{ $item->excerpt }}</div>
            </td>
            <td><x-status-badge :status="$item->is_published ? 'published' : 'incomplete'" /></td>
            <td><a class="btn btn-soft btn-sm" href="{{ route('admin.pages.edit', $item->id) }}">Edit</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
