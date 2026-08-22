@if ($paginator->hasPages())
  <nav aria-label="Pagination" class="d-flex align-items-center justify-content-between">
    <div class="text-muted-2 small">
      Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
    </div>

    <div class="d-flex gap-2">
      @if ($paginator->onFirstPage())
        <span class="btn btn-soft btn-sm disabled"><x-ui.icon name="arrow-left" class="h-4 w-4" /> Prev</span>
      @else
        <a class="btn btn-soft btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-ui.icon name="arrow-left" class="h-4 w-4" /> Prev</a>
      @endif

      @if ($paginator->hasMorePages())
        <a class="btn btn-soft btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Next <x-ui.icon name="arrow-right" class="h-4 w-4" /></a>
      @else
        <span class="btn btn-soft btn-sm disabled">Next <x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
      @endif
    </div>
  </nav>
@endif
