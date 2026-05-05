@extends('layouts.admin')

@section('title', 'Admin - Tìm kiếm')
@section('page-title', 'Tìm kiếm')
@section('page-description', $queryText !== '' ? 'Kết quả phù hợp với "' . $queryText . '".' : 'Nhập từ khóa để tìm dữ liệu trong trang quản trị.')

@push('styles')
<style>
  .admin-search-page-form { display:flex; gap:.75rem; align-items:center; width:min(56rem,100%); }
  .admin-search-page-form .search-input-wrapper { flex:1; }
  .admin-search-summary { color:var(--muted-foreground); font-size:var(--text-sm); margin:0; }
  .admin-search-results { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .admin-search-card .card-header { align-items:center; justify-content:space-between; }
  .admin-search-list { display:flex; flex-direction:column; }
  .admin-search-item { display:flex; align-items:center; gap:.75rem; padding:.85rem 0; border-top:1px solid var(--border); text-decoration:none; color:inherit; }
  .admin-search-item:first-child { border-top:0; padding-top:0; }
  .admin-search-item:hover .admin-row-title { color:var(--primary); }
  .admin-search-mark { width:2.25rem; height:2.25rem; border-radius:999px; display:grid; place-items:center; flex:0 0 auto; background:color-mix(in srgb,var(--primary) 12%,var(--card)); color:var(--primary); font-weight:900; }
  @media (max-width:980px) { .admin-search-results { grid-template-columns:1fr; } }
  @media (max-width:640px) { .admin-search-page-form { flex-direction:column; align-items:stretch; } }
</style>
@endpush

@section('content')
<section class="card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Tìm kiếm toàn cục</h3>
      <p class="card-description">Tìm nhanh người dùng, lớp học, khóa học, bài kiểm tra, bài tập, câu hỏi, hỗ trợ và khuyến mãi.</p>
    </div>
  </div>
  <div class="card-content">
    <form method="GET" action="{{ route('admin.search') }}" class="admin-search-page-form" role="search">
      <div class="search-input-wrapper">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input class="input" type="search" name="q" value="{{ $queryText }}" placeholder="Nhập từ khóa cần tìm" autofocus>
      </div>
      <button class="btn btn-primary" type="submit">Tìm kiếm</button>
    </form>
  </div>
</section>

@if($queryText === '')
  <section class="card">
    <div class="empty-state">Nhập từ khóa ở thanh tìm kiếm để bắt đầu.</div>
  </section>
@elseif($total === 0)
  <section class="card">
    <div class="empty-state">Không tìm thấy kết quả phù hợp với "{{ $queryText }}".</div>
  </section>
@else
  <p class="admin-search-summary">Tìm thấy {{ number_format($total) }} kết quả nhanh. Mỗi nhóm hiển thị tối đa 6 mục.</p>

  <div class="admin-search-results">
    @foreach($groups as $group)
      <section class="card admin-search-card">
        <div class="card-header">
          <div>
            <h3 class="card-title">{{ $group['label'] }}</h3>
            <p class="card-description">{{ $group['count'] }} kết quả phù hợp</p>
          </div>
          <a class="btn btn-outline btn-sm" href="{{ $group['route'] }}">Xem tất cả</a>
        </div>
        <div class="card-content admin-search-list">
          @foreach($group['items'] as $item)
            <a class="admin-search-item" href="{{ $item['href'] }}">
              <span class="admin-search-mark">{{ mb_strtoupper(mb_substr($item['title'], 0, 1)) }}</span>
              <span style="min-width:0;flex:1;">
                <span class="admin-row-title">{{ $item['title'] }}</span>
                @if($item['description'])
                  <span class="admin-row-meta">{{ $item['description'] }}</span>
                @endif
              </span>
              @if($item['badge'])
                <span class="badge badge-outline">{{ \Illuminate\Support\Str::limit($item['badge'], 18) }}</span>
              @endif
            </a>
          @endforeach
        </div>
      </section>
    @endforeach
  </div>
@endif
@endsection
