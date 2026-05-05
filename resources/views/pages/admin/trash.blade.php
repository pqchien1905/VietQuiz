@extends('layouts.admin')

@section('title', 'Admin - Thùng rác')
@section('page-title', 'Thùng rác')
@section('page-description', 'Kiểm soát dữ liệu đã xóa mềm, khôi phục nhanh hoặc xóa vĩnh viễn khi đã chắc chắn.')

@php
  $summaryCards = [
    ['label' => 'Tổng trong thùng rác', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.trash')],
    ['label' => 'Xóa hôm nay', 'value' => $summary['today'], 'tone' => 'var(--info)', 'href' => route('admin.trash', ['age' => 'today', 'type' => $type, 'q' => $queryText, 'sort' => $sort])],
    ['label' => 'Trong 7 ngày', 'value' => $summary['week'], 'tone' => 'var(--success)', 'href' => route('admin.trash', ['age' => 'week', 'type' => $type, 'q' => $queryText, 'sort' => $sort])],
    ['label' => 'Cần xử lý sớm', 'value' => $summary['expiring'], 'tone' => 'var(--warning)', 'href' => route('admin.trash', ['age' => 'old', 'type' => $type, 'q' => $queryText, 'sort' => $sort])],
    ['label' => 'Quá 30 ngày', 'value' => $summary['old'], 'tone' => 'var(--destructive)', 'href' => route('admin.trash', ['age' => 'old', 'type' => $type, 'q' => $queryText, 'sort' => $sort])],
  ];
  $activeTypeLabel = $type === 'all' ? 'Tất cả loại dữ liệu' : ($map[$type]['label'] ?? $type);
  $currentQuery = array_filter(['type' => $type !== 'all' ? $type : null, 'q' => $queryText ?: null, 'age' => $age !== 'all' ? $age : null, 'sort' => $sort !== 'latest' ? $sort : null]);
@endphp

@push('styles')
<style>
  .trash-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .trash-summary-grid .stat-card { min-height:7rem; }
  .trash-board { display:grid; grid-template-columns:17rem minmax(0,1fr); gap:1rem; align-items:start; }
  .trash-sidebar { border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); overflow:hidden; }
  .trash-sidebar__head { padding:1rem; border-bottom:1px solid var(--border); }
  .trash-sidebar__head h3 { margin:0; font-size:var(--text-base); font-weight:800; }
  .trash-sidebar__head p { margin:.25rem 0 0; color:var(--muted-foreground); font-size:var(--text-xs); }
  .trash-type-list { display:flex; flex-direction:column; padding:.5rem; }
  .trash-type-item { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.65rem .75rem; border-radius:var(--radius-md); color:inherit; text-decoration:none; }
  .trash-type-item:hover { background:var(--muted); }
  .trash-type-item.active { background:color-mix(in srgb,var(--primary) 12%,transparent); color:var(--primary); font-weight:800; }
  .trash-type-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .trash-workspace { min-width:0; }
  .trash-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .trash-header h3 { margin:0; font-size:var(--text-lg); font-weight:900; }
  .trash-header p { margin:.25rem 0 0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .trash-filter-grid { display:grid; grid-template-columns:minmax(240px,1fr) repeat(3,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .trash-actions-bar { display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; padding:1rem; border-top:1px solid var(--border); background:color-mix(in srgb,var(--muted) 45%,transparent); }
  .trash-actions-group { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .trash-list { display:flex; flex-direction:column; }
  .trash-list-head,
  .trash-list-row { display:grid; grid-template-columns:2.5rem minmax(16rem,1fr) 7.5rem 8.5rem 9.5rem minmax(12rem,auto); gap:1rem; align-items:center; }
  .trash-list-head { padding:.8rem 1.25rem; background:var(--muted); color:var(--muted-foreground); font-size:var(--text-xs); font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
  .trash-list-row { padding:1rem 1.25rem; border-top:1px solid var(--border); }
  .trash-list-row:hover { background:color-mix(in srgb,var(--muted) 42%,transparent); }
  .trash-list-cell { min-width:0; }
  .trash-check { display:flex; justify-content:center; }
  .trash-item-title { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .trash-item-title a, .trash-item-title span { font-weight:800; color:var(--foreground); text-decoration:none; }
  .trash-item-title a:hover { color:var(--primary); }
  .trash-item-meta { margin-top:.35rem; color:var(--muted-foreground); font-size:var(--text-xs); display:flex; flex-wrap:wrap; gap:.45rem .75rem; }
  .trash-retention { display:flex; flex-direction:column; gap:.35rem; min-width:8rem; }
  .trash-retention__bar { height:.45rem; border-radius:999px; background:var(--muted); overflow:hidden; }
  .trash-retention__fill { height:100%; border-radius:999px; background:var(--success); }
  .trash-retention.is-warning .trash-retention__fill { background:var(--warning); }
  .trash-retention.is-danger .trash-retention__fill { background:var(--destructive); }
  .trash-row-actions { display:flex; align-items:center; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
  .trash-danger-note { display:flex; gap:.75rem; align-items:flex-start; padding:1rem; border:1px solid color-mix(in srgb,var(--destructive) 24%,transparent); background:color-mix(in srgb,var(--destructive) 7%,transparent); border-radius:var(--radius-lg); color:var(--muted-foreground); font-size:var(--text-sm); }
  .trash-danger-note strong { color:var(--foreground); }
  @media (max-width:1280px) { .trash-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .trash-board { grid-template-columns:1fr; } .trash-type-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:1100px) {
    .trash-list-head { display:none; }
    .trash-list-row { grid-template-columns:2.5rem minmax(0,1fr); align-items:start; }
    .trash-list-cell[data-label]::before { content:attr(data-label); display:block; color:var(--muted-foreground); font-size:var(--text-xs); font-weight:800; text-transform:uppercase; margin-bottom:.25rem; }
    .trash-row-actions { justify-content:flex-start; }
  }
  @media (max-width:900px) { .trash-filter-grid { grid-template-columns:1fr 1fr; } .trash-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:640px) { .trash-filter-grid,.trash-type-list,.trash-summary-grid { grid-template-columns:1fr; } .trash-list-row { grid-template-columns:1fr; } .trash-check { justify-content:flex-start; } }
</style>
@endpush

@section('content')
<section class="stats-grid trash-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="trash-danger-note">
  <span class="badge badge-danger">Lưu ý</span>
  <div><strong>Xóa vĩnh viễn không thể hoàn tác.</strong> Hãy khôi phục các mục còn cần đối chiếu trước khi dọn thùng rác. Khôi phục chỉ đưa bản ghi trở lại trạng thái hoạt động, không tự sửa các quan hệ đã bị xóa riêng ở module khác.</div>
</section>

<section class="trash-board">
  <aside class="trash-sidebar">
    <div class="trash-sidebar__head">
      <h3>Loại dữ liệu</h3>
      <p>Chọn nhanh theo module đang có dữ liệu đã xóa.</p>
    </div>
    <div class="trash-type-list">
      <a class="trash-type-item {{ $type === 'all' ? 'active' : '' }}" href="{{ route('admin.trash', array_merge($currentQuery, ['type' => null])) }}">
        <span class="trash-type-name">Tất cả</span>
        <span class="badge badge-outline">{{ number_format($summary['total']) }}</span>
      </a>
      @foreach($map as $key => $config)
        <a class="trash-type-item {{ $type === $key ? 'active' : '' }}" href="{{ route('admin.trash', array_merge($currentQuery, ['type' => $key])) }}">
          <span class="trash-type-name">{{ $config['label'] }}</span>
          <span class="badge badge-outline">{{ number_format($typeCounts[$key] ?? 0) }}</span>
        </a>
      @endforeach
    </div>
  </aside>

  <div class="trash-workspace card">
    <div class="card-header trash-header">
      <div>
        <h3>{{ $activeTypeLabel }}</h3>
        <p>Đang hiển thị {{ number_format($items->count()) }} mục sau bộ lọc.</p>
      </div>
      <div class="trash-actions-group">
        <form method="POST" action="{{ route('admin.trash.restore-all') }}" data-confirm="Khôi phục toàn bộ mục đang chọn theo loại dữ liệu hiện tại?" data-confirm-ok="Khôi phục">
          @csrf
          <input type="hidden" name="type" value="{{ $type }}">
          <button class="btn btn-outline-primary btn-sm" @disabled($items->isEmpty())>Khôi phục {{ $type === 'all' ? 'tất cả' : 'loại này' }}</button>
        </form>
        <form method="POST" action="{{ route('admin.trash.force-delete-all') }}" data-confirm="Xóa vĩnh viễn {{ $type === 'all' ? 'toàn bộ thùng rác' : 'toàn bộ '.$activeTypeLabel }}? Hành động này không thể hoàn tác." data-confirm-ok="Xóa vĩnh viễn">
          @csrf
          @method('DELETE')
          <input type="hidden" name="type" value="{{ $type }}">
          <button class="btn btn-destructive btn-sm" @disabled($items->isEmpty())>Dọn {{ $type === 'all' ? 'thùng rác' : 'loại này' }}</button>
        </form>
      </div>
    </div>

    <div class="card-content" style="border-top:1px solid var(--border);">
      <form method="GET" class="trash-filter-grid">
        <div class="form-group">
          <label class="label">Tìm kiếm</label>
          <input class="input" name="q" value="{{ $queryText }}" placeholder="Tên, email, mã, nội dung, ID">
        </div>
        <div class="form-group">
          <label class="label">Loại</label>
          <select class="input select" name="type">
            <option value="all" @selected($type === 'all')>Tất cả</option>
            @foreach($map as $key => $config)
              <option value="{{ $key }}" @selected($type === $key)>{{ $config['label'] }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="label">Thời gian xóa</label>
          <select class="input select" name="age">
            <option value="all" @selected($age === 'all')>Tất cả</option>
            <option value="today" @selected($age === 'today')>Hôm nay</option>
            <option value="week" @selected($age === 'week')>7 ngày</option>
            <option value="month" @selected($age === 'month')>30 ngày</option>
            <option value="old" @selected($age === 'old')>Quá 30 ngày</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label">Sắp xếp</label>
          <select class="input select" name="sort">
            <option value="latest" @selected($sort === 'latest')>Mới xóa trước</option>
            <option value="oldest" @selected($sort === 'oldest')>Cũ nhất trước</option>
            <option value="type" @selected($sort === 'type')>Theo loại</option>
            <option value="name" @selected($sort === 'name')>Theo tên</option>
          </select>
        </div>
        <button class="btn btn-primary">Lọc</button>
        <a class="btn btn-outline" href="{{ route('admin.trash') }}">Đặt lại</a>
      </form>
    </div>

    <div class="trash-actions-bar">
      <div class="trash-actions-group">
        <label style="display:flex;align-items:center;gap:.5rem;font-size:var(--text-sm);color:var(--muted-foreground);">
          <input type="checkbox" data-trash-select-all>
          Chọn tất cả trên trang
        </label>
        <span class="badge badge-outline"><span data-trash-selected-count>0</span> đã chọn</span>
      </div>
      <div class="trash-actions-group">
        <form id="trash-bulk-form" method="POST" action="{{ route('admin.trash.restore-selected') }}">
          @csrf
          <button class="btn btn-outline-primary btn-sm" data-trash-bulk="restore" disabled>Khôi phục đã chọn</button>
          <button class="btn btn-destructive btn-sm" data-trash-bulk="delete" disabled>Xóa vĩnh viễn đã chọn</button>
        </form>
      </div>
    </div>

    <div class="trash-list">
      <div class="trash-list-head">
        <div></div>
        <div>Dữ liệu</div>
        <div>Loại</div>
        <div>Xóa lúc</div>
        <div>Thời hạn</div>
        <div style="text-align:right;">Thao tác</div>
      </div>
        @forelse($items as $item)
          @php
            $progress = min(100, max(0, ($item['age_days'] / 30) * 100));
            $retentionClass = $item['days_left'] === 0 ? 'is-danger' : ($item['days_left'] <= 7 ? 'is-warning' : '');
          @endphp
          <div class="trash-list-row">
            <div class="trash-check"><input type="checkbox" value="{{ $item['key'] }}" data-trash-item></div>
            <div class="trash-list-cell" data-label="Dữ liệu">
              <div class="trash-item-title">
                @if($item['detail_route'])
                  <a href="{{ $item['detail_route'] }}">{{ \Illuminate\Support\Str::limit($item['title'], 120) }}</a>
                @else
                  <span>{{ \Illuminate\Support\Str::limit($item['title'], 120) }}</span>
                @endif
                <span class="badge badge-outline">#{{ $item['id'] }}</span>
              </div>
              <div class="trash-item-meta">
                @if($item['owner'])<span>Liên quan: {{ $item['owner'] }}</span>@endif
                @if($item['description'])<span>{{ \Illuminate\Support\Str::limit($item['description'], 150) }}</span>@endif
              </div>
            </div>
            <div class="trash-list-cell" data-label="Loại"><span class="badge badge-outline">{{ $item['label'] }}</span></div>
            <div class="trash-list-cell" data-label="Xóa lúc">
              <div>{{ $item['deleted_at']?->format('d/m/Y H:i') }}</div>
              <div class="admin-row-meta">{{ $item['age_days'] }} ngày trước</div>
            </div>
            <div class="trash-list-cell" data-label="Thời hạn">
              <div class="trash-retention {{ $retentionClass }}">
                <span class="badge {{ $item['days_left'] === 0 ? 'badge-danger' : ($item['days_left'] <= 7 ? 'badge-warning' : 'badge-success') }}">
                  {{ $item['days_left'] === 0 ? 'Quá hạn' : 'Còn '.$item['days_left'].' ngày' }}
                </span>
                <div class="trash-retention__bar"><div class="trash-retention__fill" style="width:{{ $progress }}%"></div></div>
              </div>
            </div>
            <div class="trash-list-cell" data-label="Thao tác">
              <div class="trash-row-actions">
                <form method="POST" action="{{ route('admin.trash.restore', [$item['type'], $item['id']]) }}" data-confirm="Khôi phục {{ $item['label'] }} #{{ $item['id'] }}?" data-confirm-ok="Khôi phục">
                  @csrf
                  <button class="btn btn-outline-primary btn-sm">Khôi phục</button>
                </form>
                <form method="POST" action="{{ route('admin.trash.force-delete', [$item['type'], $item['id']]) }}" data-confirm="Xóa vĩnh viễn {{ $item['label'] }} #{{ $item['id'] }}? Hành động này không thể hoàn tác." data-confirm-ok="Xóa vĩnh viễn">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-destructive btn-sm">Xóa</button>
                </form>
              </div>
            </div>
          </div>
        @empty
          <div class="empty-state">
            <h3>Thùng rác trống</h3>
            <p>Không có dữ liệu đã xóa mềm phù hợp với bộ lọc hiện tại.</p>
          </div>
        @endforelse
    </div>
  </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const checkboxes = Array.from(document.querySelectorAll('[data-trash-item]'));
  const selectAll = document.querySelector('[data-trash-select-all]');
  const count = document.querySelector('[data-trash-selected-count]');
  const bulkForm = document.getElementById('trash-bulk-form');
  const bulkButtons = Array.from(document.querySelectorAll('[data-trash-bulk]'));

  const clearBulkInputs = () => bulkForm?.querySelectorAll('input[name="items[]"], input[name="_method"]').forEach((input) => input.remove());
  const selected = () => checkboxes.filter((box) => box.checked).map((box) => box.value);
  const sync = () => {
    const values = selected();
    if (count) count.textContent = values.length;
    bulkButtons.forEach((button) => button.disabled = values.length === 0);
    if (selectAll) {
      selectAll.checked = values.length > 0 && values.length === checkboxes.length;
      selectAll.indeterminate = values.length > 0 && values.length < checkboxes.length;
    }
  };

  selectAll?.addEventListener('change', () => {
    checkboxes.forEach((box) => box.checked = selectAll.checked);
    sync();
  });
  checkboxes.forEach((box) => box.addEventListener('change', sync));

  bulkButtons.forEach((button) => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();
      const values = selected();
      if (!values.length || !bulkForm) return;
      const isDelete = button.dataset.trashBulk === 'delete';
      const message = isDelete
        ? `Xóa vĩnh viễn ${values.length} mục đã chọn? Hành động này không thể hoàn tác.`
        : `Khôi phục ${values.length} mục đã chọn?`;
      const accepted = window.showAppConfirm
        ? await window.showAppConfirm(message, { title: 'Xác nhận', confirmText: isDelete ? 'Xóa vĩnh viễn' : 'Khôi phục', destructive: isDelete })
        : window.confirm(message);
      if (!accepted) return;

      clearBulkInputs();
      bulkForm.action = isDelete ? @json(route('admin.trash.force-delete-selected')) : @json(route('admin.trash.restore-selected'));
      if (isDelete) {
        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        bulkForm.appendChild(method);
      }
      values.forEach((value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'items[]';
        input.value = value;
        bulkForm.appendChild(input);
      });
      bulkForm.submit();
    });
  });

  sync();
});
</script>
@endpush
@endsection
