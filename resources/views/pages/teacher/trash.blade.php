{{-- Teacher: trash --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $activeType = $type ?? 'all';
  $items = $trashedItems ?? collect();
  $allCount = $counts['all'] ?? $items->count();
  $badges = [
    'class' => 'badge-info',
    'course' => 'badge-success',
    'quiz' => 'badge-primary',
    'assignment' => 'badge-warning',
    'question' => 'badge-default',
  ];
@endphp

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Thùng rác</h1>
        <p style="color:var(--muted-foreground);">Quản lý các lớp học, khóa học, đề thi, bài tập và câu hỏi đã xóa.</p>
      </div>

      <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <form method="POST" action="{{ route('teacher.trash.restore-all') }}" data-confirm="Khôi phục tất cả mục trong thùng rác?" data-confirm-ok="Khôi phục">
          @csrf
          <button class="btn btn-outline btn-sm" type="submit" @disabled($allCount === 0)>Khôi phục tất cả</button>
        </form>
        <form method="POST" action="{{ route('teacher.trash.force-delete-all') }}" data-confirm="Xóa vĩnh viễn tất cả mục trong thùng rác? Hành động này không thể hoàn tác." data-confirm-ok="Xóa vĩnh viễn">
          @csrf
          @method('DELETE')
          <button class="btn btn-destructive btn-sm" type="submit" @disabled($allCount === 0)>Xóa vĩnh viễn tất cả</button>
        </form>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;"><span>{{ session('success') }}</span></div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ $errors->first() }}</span></div>
  @endif

  <div class="alert alert-warning stagger-children" style="margin-bottom:1.25rem;">
    <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <span style="font-size:var(--text-sm);">Các mục đã xóa sẽ được giữ trong thùng rác <strong>30 ngày</strong>. Sau thời hạn này, bạn nên xóa vĩnh viễn hoặc khôi phục các mục còn cần dùng.</span>
  </div>

  <div class="tabs-list stagger-children" style="margin-bottom:1.25rem;max-width:680px;">
    <a class="tab-trigger {{ $activeType === 'all' ? 'active' : '' }}" href="{{ route('teacher.trash') }}" style="text-decoration:none;">Tất cả ({{ $counts['all'] ?? 0 }})</a>
    @foreach($typeLabels as $trashType => $label)
      <a class="tab-trigger {{ $activeType === $trashType ? 'active' : '' }}" href="{{ route('teacher.trash', ['type' => $trashType]) }}" style="text-decoration:none;">
        {{ $label }} ({{ $counts[$trashType] ?? 0 }})
      </a>
    @endforeach
  </div>

  <form method="POST" action="{{ route('teacher.trash.restore-selected') }}" id="bulk-form">
    @csrf

    <div class="card stagger-children">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:1rem 1rem 0;">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);">
          Đang hiển thị <strong style="color:var(--foreground);">{{ $items->count() }}</strong> / {{ $allCount }} mục
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
          <button class="btn btn-outline btn-sm" type="submit" id="restore-selected-btn" disabled data-confirm="Khôi phục các mục đã chọn?" data-confirm-ok="Khôi phục">Khôi phục đã chọn</button>
          <button class="btn btn-destructive btn-sm" type="submit" id="delete-selected-btn" disabled formmethod="POST" formaction="{{ route('teacher.trash.force-delete-selected') }}" data-method="delete-selected" data-confirm="Xóa vĩnh viễn các mục đã chọn? Hành động này không thể hoàn tác." data-confirm-ok="Xóa vĩnh viễn">Xóa đã chọn</button>
        </div>
      </div>

      @if($items->isNotEmpty())
        <div class="table-wrapper" style="border:none;border-radius:0;margin-top:0.75rem;">
          <table>
            <thead>
              <tr>
                <th style="width:2.5rem;"><input type="checkbox" id="select-all" aria-label="Chọn tất cả" /></th>
                <th>Tên mục</th>
                <th>Loại</th>
                <th>Ngày xóa</th>
                <th>Đã xóa</th>
                <th>Còn lại</th>
                <th style="width:13rem;"></th>
              </tr>
            </thead>
            <tbody>
              @foreach($items as $item)
                <tr>
                  <td>
                    <input type="checkbox" class="item-check" name="items[]" value="{{ $item->key }}" aria-label="Chọn {{ $item->name }}" />
                  </td>
                  <td style="font-weight:500;font-size:var(--text-sm);max-width:24rem;">
                    {{ $item->name }}
                  </td>
                  <td>
                    <span class="badge {{ $badges[$item->type] ?? 'badge-default' }}">{{ $item->type_label }}</span>
                  </td>
                  <td style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $item->deleted_at_label }}</td>
                  <td style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $item->age_days }} ngày trước</td>
                  <td>
                    <span style="font-size:var(--text-sm);font-weight:600;color:{{ $item->is_expiring ? 'var(--destructive)' : 'var(--muted-foreground)' }};">
                      {{ $item->days_left }} ngày
                    </span>
                  </td>
                  <td>
                    <div style="display:flex;gap:.25rem;justify-content:flex-end;">
                      <button class="btn btn-ghost btn-sm" type="submit" form="restore-{{ $item->type }}-{{ $item->id }}">Khôi phục</button>
                      <button class="btn btn-ghost btn-sm" style="color:var(--destructive);" type="submit" form="delete-{{ $item->type }}-{{ $item->id }}">Xóa</button>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="empty-state" style="padding:3rem;">
          <div style="font-size:3rem;">Thùng rác</div>
          <h3>Không có mục nào</h3>
          <p>{{ $activeType === 'all' ? 'Thùng rác của bạn đang trống.' : 'Không có mục nào thuộc loại đang lọc.' }}</p>
        </div>
      @endif
    </div>
  </form>

  @foreach($items as $item)
    <form method="POST" action="{{ route('teacher.trash.restore', [$item->type, $item->id]) }}" id="restore-{{ $item->type }}-{{ $item->id }}" data-confirm="Khôi phục &quot;{{ $item->name }}&quot;?" data-confirm-ok="Khôi phục">
      @csrf
    </form>

    <form method="POST" action="{{ route('teacher.trash.force-delete', [$item->type, $item->id]) }}" id="delete-{{ $item->type }}-{{ $item->id }}" data-confirm="Xóa vĩnh viễn &quot;{{ $item->name }}&quot;? Hành động này không thể hoàn tác." data-confirm-ok="Xóa vĩnh viễn">
      @csrf
      @method('DELETE')
    </form>
  @endforeach

  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var checks = Array.prototype.slice.call(document.querySelectorAll('.item-check'));
  var selectAll = document.getElementById('select-all');
  var restoreBtn = document.getElementById('restore-selected-btn');
  var deleteBtn = document.getElementById('delete-selected-btn');
  var bulkForm = document.getElementById('bulk-form');

  function updateBulkState() {
    var checked = checks.filter(function(check) { return check.checked; });
    var hasChecked = checked.length > 0;
    if (restoreBtn) restoreBtn.disabled = !hasChecked;
    if (deleteBtn) deleteBtn.disabled = !hasChecked;
    if (selectAll) {
      selectAll.checked = checks.length > 0 && checked.length === checks.length;
      selectAll.indeterminate = checked.length > 0 && checked.length < checks.length;
    }
  }

  if (selectAll) {
    selectAll.addEventListener('change', function() {
      checks.forEach(function(check) { check.checked = selectAll.checked; });
      updateBulkState();
    });
  }

  checks.forEach(function(check) {
    check.addEventListener('change', updateBulkState);
  });

  if (bulkForm) {
    bulkForm.addEventListener('submit', function(event) {
      var submitter = event.submitter;
      var methodInput = bulkForm.querySelector('input[name="_method"]');
      if (submitter) {
        bulkForm.dataset.confirm = submitter.dataset.confirm || '';
        bulkForm.dataset.confirmOk = submitter.dataset.confirmOk || 'OK';
        bulkForm.action = submitter.formAction || bulkForm.action;
      }
      if (submitter && submitter.dataset.method === 'delete-selected') {
        if (!methodInput) {
          methodInput = document.createElement('input');
          methodInput.type = 'hidden';
          methodInput.name = '_method';
          bulkForm.appendChild(methodInput);
        }
        methodInput.value = 'DELETE';
      } else if (methodInput) {
        methodInput.remove();
      }
    });
  }

  updateBulkState();
})();
</script>
@endpush
