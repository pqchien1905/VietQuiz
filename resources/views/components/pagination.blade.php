@php
  $pageName = $paginator->getPageName();
  $currentPerPage = (int) request()->query('per_page', $paginator->perPage());
  $perPageOptions = [10, 20, 50, 100];
  if (!in_array($currentPerPage, $perPageOptions, true)) {
      $currentPerPage = $paginator->perPage();
  }
@endphp

@if($paginator->total() > 0)
  <nav class="pagination-wrap" role="navigation" aria-label="Phân trang" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
    <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);">
        Hiển thị {{ number_format($paginator->firstItem() ?? 0) }}-{{ number_format($paginator->lastItem() ?? 0) }} trong {{ number_format($paginator->total()) }} kết quả
      </div>

      <form method="GET" action="{{ request()->url() }}" style="display:flex;align-items:center;gap:.4rem;">
        @foreach(request()->query() as $key => $value)
          @continue($key === 'per_page' || $key === $pageName)
          @if(is_array($value))
            @foreach($value as $subValue)
              <input type="hidden" name="{{ $key }}[]" value="{{ $subValue }}">
            @endforeach
          @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endif
        @endforeach
        <label for="per_page" style="font-size:var(--text-sm);color:var(--muted-foreground);">Số hàng/trang</label>
        <select id="per_page" name="per_page" class="input select" style="width:auto;min-width:84px;" onchange="this.form.submit()">
          @foreach($perPageOptions as $option)
            <option value="{{ $option }}" @selected($currentPerPage === $option)>{{ $option }}</option>
          @endforeach
        </select>
      </form>
    </div>

    @if($paginator->hasPages())
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
    @endif
  </nav>
@endif

