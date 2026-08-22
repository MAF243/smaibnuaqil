@extends('layouts.admin')

@php($pageTitle = 'Edit Halaman')

@section('content')
<div class="admin-card p-4">
  <form method="post" action="{{ route('admin.pages.update', $item->id) }}">
    @csrf
    @method('PUT')
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label admin-muted">Judul</label>
        <input class="form-control admin-input" name="title" value="{{ old('title', $item->title) }}">
        <x-field-error name="title" />
      </div>
      <div class="col-md-4">
        <label class="form-label admin-muted">Page Key</label>
        <input class="form-control admin-input" value="{{ $item->page_key }}" disabled>
      </div>
      <div class="col-12">
        <label class="form-label admin-muted">Excerpt</label>
        <textarea class="form-control admin-input" name="excerpt" rows="3">{{ old('excerpt', $item->excerpt) }}</textarea>
      </div>
      <div class="col-12">
        <label class="form-label admin-muted">Konten</label>
        <textarea class="form-control admin-input" name="content" rows="16">{{ old('content', $item->content) }}</textarea>
      </div>
      <div class="col-12 d-flex align-items-center gap-2">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" {{ old('is_published', $item->is_published) ? 'checked' : '' }}>
          <label class="form-check-label" for="is_published">Publish halaman</label>
        </div>
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-brand" type="submit">Simpan</button>
        <a class="btn btn-soft" href="{{ route('admin.pages.index') }}">Kembali</a>
      </div>
    </div>
  </form>
</div>
@endsection
