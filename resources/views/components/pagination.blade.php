@if($paginator->hasPages())
  <nav class="pagination-wrap" role="navigation" aria-label="Phân trang" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);">
      Hiển thị {{ number_format($paginator->firstItem()) }}-{{ number_format($paginator->lastItem()) }} trong {{ number_format($paginator->total()) }} kết quả
    </div>
    <div style="display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;">
      @if($paginator->onFirstPage())
        <span class="btn btn-outline btn-sm" style="opacity:.5;pointer-events:none;">Trước</span>
      @else
        <a class="btn btn-outline btn-sm" href="{{ $paginator->previousPageUrl() }}">Trước</a>
      @endif

      @foreach($elements as $element)
        @if(is_string($element))
          <span class="btn btn-ghost btn-sm" style="pointer-events:none;">{{ $element }}</span>
        @endif

        @if(is_array($element))
          @foreach($element as $page => $url)
            @if($page == $paginator->currentPage())
              <span class="btn btn-primary btn-sm" aria-current="page">{{ $page }}</span>
            @else
              <a class="btn btn-outline btn-sm" href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach
        @endif
      @endforeach

      @if($paginator->hasMorePages())
        <a class="btn btn-outline btn-sm" href="{{ $paginator->nextPageUrl() }}">Sau</a>
      @else
        <span class="btn btn-outline btn-sm" style="opacity:.5;pointer-events:none;">Sau</span>
      @endif
    </div>
  </nav>
@elseif($paginator->total() > 0)
  <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:1rem;">
    Hiển thị {{ number_format($paginator->total()) }} kết quả
  </div>
@endif
