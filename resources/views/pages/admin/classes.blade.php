@extends('layouts.admin')

@section('title', 'Admin - Lớp học')
@section('page-title', 'Lớp học')
@section('page-description', 'Quản lý lớp, giáo viên phụ trách, học sinh, khóa học, bài kiểm tra và bài tập.')

@php
  $summaryCards = [
    ['label' => 'Tổng lớp', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.classes', ['state' => 'all'])],
    ['label' => 'Đang hoạt động', 'value' => $summary['active'], 'tone' => 'var(--success)', 'href' => route('admin.classes')],
    ['label' => 'Đã lưu trữ', 'value' => $summary['archived'], 'tone' => 'var(--warning)', 'href' => route('admin.classes', ['status' => 'archived'])],
    ['label' => 'Đã xóa', 'value' => $summary['deleted'], 'tone' => 'var(--destructive)', 'href' => route('admin.classes', ['state' => 'deleted'])],
    ['label' => 'Học sinh', 'value' => $summary['students'], 'tone' => 'var(--info)', 'href' => route('admin.classes')],
  ];
  $statusOptions = ['active', 'archived'];
@endphp

@push('styles')
<style>
  .admin-classes-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .admin-classes-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .admin-classes-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .admin-classes-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .class-cell { display:flex; align-items:center; gap:.75rem; min-width:17rem; }
  .class-mark { width:2.75rem; height:2.75rem; border-radius:.75rem; display:grid; place-items:center; color:#fff; font-weight:900; flex:0 0 auto; box-shadow:var(--shadow-sm); }
  .class-name-row { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .class-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.5rem; min-width:18rem; }
  .class-metric { padding:.55rem .65rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .class-metric strong { display:block; font-size:var(--text-base); line-height:1.1; }
  .class-metric span { display:block; margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-xs); white-space:nowrap; }
  .class-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:13rem; }
  .class-filter-grid { display:grid; grid-template-columns:minmax(260px,1fr) repeat(5,minmax(135px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .class-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .class-modal-grid .full { grid-column:1/-1; }
  .class-empty-teacher { margin-top:.5rem; padding:.75rem; border:1px solid color-mix(in srgb,var(--warning) 35%,var(--border)); border-radius:var(--radius-md); background:color-mix(in srgb,var(--warning) 10%,var(--card)); color:var(--muted-foreground); font-size:var(--text-sm); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
  .admin-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .admin-summary-grid .stat-card { min-height:7.25rem; }
  @media (max-width:1280px) { .admin-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
  @media (max-width:1200px) { .class-filter-grid { grid-template-columns:1fr 1fr; } .class-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); min-width:14rem; } }
  @media (max-width:820px) { .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:720px) { .class-filter-grid,.class-modal-grid { grid-template-columns:1fr; } .class-modal-grid .full { grid-column:auto; } .class-cell { min-width:0; } .class-metrics { grid-template-columns:1fr 1fr; min-width:0; } }
  @media (max-width:520px) { .admin-summary-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('actions')
  <button class="btn btn-primary btn-sm" type="button" onclick="openAdminClassModal('create-class-modal')">Thêm lớp học</button>
@endsection

@section('content')
<section class="stats-grid admin-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header admin-classes-header">
    <div class="admin-classes-title">
      <h3>Danh sách lớp học</h3>
      <p>Hiển thị {{ $classes->firstItem() ?? 0 }}-{{ $classes->lastItem() ?? 0 }} trên {{ number_format($classes->total()) }} kết quả.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openAdminClassModal('create-class-modal')">Thêm lớp học</button>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="class-filter-grid">
      <div class="form-group">
        <label class="label">Tìm kiếm</label>
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Tên lớp, mã lớp, môn học, khối">
      </div>
      <div class="form-group">
        <label class="label">Giáo viên</label>
        <select class="input select" name="teacher_id">
          <option value="">Tất cả</option>
          @foreach($teachers as $teacher)
            <option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="label">Môn học</label>
        <select class="input select" name="subject">
          <option value="">Tất cả</option>
          @foreach($subjects as $subject)
            <option value="{{ $subject }}" @selected(request('subject') === $subject)>{{ $subject }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="label">Trạng thái</label>
        <select class="input select" name="status">
          <option value="">Tất cả</option>
          @foreach($statusOptions as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="label">Dữ liệu</label>
        <select class="input select" name="state">
          <option value="active" @selected(request('state', 'active') === 'active')>Đang dùng</option>
          <option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option>
          <option value="all" @selected(request('state') === 'all')>Tất cả</option>
        </select>
      </div>
      <div class="form-group">
        <label class="label">Sắp xếp</label>
        <select class="input select" name="sort">
          <option value="">Mới nhất</option>
          <option value="students" @selected(request('sort') === 'students')>Nhiều học sinh</option>
          <option value="name" @selected(request('sort') === 'name')>Tên A-Z</option>
          <option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option>
        </select>
      </div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.classes') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead>
        <tr>
          <th>Lớp học</th>
          <th>Giáo viên</th>
          <th>Số liệu</th>
          <th>Trạng thái</th>
          <th style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      @forelse($classes as $class)
        <tr style="{{ $class->trashed() ? 'background:color-mix(in srgb,var(--destructive) 7%,transparent);' : '' }}">
          <td>
            <div class="class-cell">
              <div class="class-mark" style="background:{{ $class->color ?: 'var(--primary)' }}">{{ $class->icon ?: mb_strtoupper(mb_substr($class->name, 0, 1)) }}</div>
              <div style="min-width:0;">
                <div class="class-name-row">
                  <a class="admin-row-title" href="{{ route('admin.classes.show', $class->id) }}">{{ $class->name }}</a>
                  <span class="badge badge-outline">{{ $class->code }}</span>
                </div>
                <div class="admin-row-meta">
                  <span>{{ $class->subject ?: 'Chưa có môn' }}</span>
                  <span>{{ $class->grade_level ?: 'Chưa có khối' }}</span>
                </div>
              </div>
            </div>
          </td>
          <td>
            <div class="admin-row-title">{{ $class->teacher?->name ?? 'Không rõ' }}</div>
            <div class="admin-row-meta">{{ $class->teacher?->email }}</div>
          </td>
          <td>
            <div class="class-metrics">
              <div class="class-metric"><strong>{{ number_format($class->students_count) }}</strong><span>Học sinh</span></div>
              <div class="class-metric"><strong>{{ number_format($class->courses_count) }}</strong><span>Khóa</span></div>
              <div class="class-metric"><strong>{{ number_format($class->quizzes_count) }}</strong><span>Quiz</span></div>
              <div class="class-metric"><strong>{{ number_format($class->assignments_count) }}</strong><span>Bài tập</span></div>
            </div>
          </td>
          <td>
            <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
              <span class="badge {{ $class->status === 'active' ? 'badge-success' : 'badge-warning' }}">{{ \App\Support\AdminLabels::status($class->status ?? 'active') }}</span>
              @if($class->trashed())
                <span class="badge badge-danger">Đã xóa</span>
              @endif
            </div>
          </td>
          <td>
            <div class="class-actions">
              <a class="btn btn-outline btn-sm" href="{{ route('admin.classes.show', $class->id) }}">Chi tiết</a>
              <button class="btn btn-primary btn-sm" type="button" onclick="openAdminClassModal('edit-class-{{ $class->id }}')">Sửa</button>
              @if($class->trashed())
                <form method="POST" action="{{ route('admin.classes.restore', $class->id) }}">
                  @csrf
                  <button class="btn btn-outline-primary btn-sm">Khôi phục</button>
                </form>
              @else
                <form method="POST" action="{{ route('admin.classes.delete', $class->id) }}" data-confirm="Đưa lớp {{ $class->name }} vào thùng rác? Dữ liệu liên quan vẫn được giữ để khôi phục." data-confirm-ok="Xóa lớp">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-destructive btn-sm">Xóa</button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có lớp học phù hợp với bộ lọc.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $classes->links('components.pagination') }}</div>
</section>

<div class="modal-overlay" id="create-class-modal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="create-class-title" style="max-width:44rem;">
    <form method="POST" action="{{ route('admin.classes.store') }}">
      @csrf
      <input type="hidden" name="_form" value="create">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="create-class-title">Thêm lớp học</h2>
          <p class="modal-desc">Tạo lớp và gán giáo viên phụ trách.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminClassModal('create-class-modal')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        <div class="class-modal-grid">
          <div class="form-group"><label class="label">Tên lớp</label><input class="input" name="name" value="{{ old('_form') === 'create' ? old('name') : '' }}" required maxlength="255"></div>
          <div class="form-group"><label class="label">Mã lớp</label><input class="input" name="code" value="{{ old('_form') === 'create' ? old('code') : '' }}" maxlength="50" placeholder="Tự tạo nếu bỏ trống"></div>
          <div class="form-group">
            <label class="label">Giáo viên</label>
            <select class="input select" name="teacher_id" required @disabled($teachers->isEmpty())>
              <option value="">{{ $teachers->isEmpty() ? 'Chưa có giáo viên' : 'Chọn giáo viên' }}</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(old('_form') === 'create' && (string) old('teacher_id') === (string) $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>
              @endforeach
            </select>
            @if($teachers->isEmpty())
              <div class="class-empty-teacher">
                <span>Cần tạo ít nhất một tài khoản giáo viên trước khi tạo lớp.</span>
                <a class="btn btn-outline btn-sm" href="{{ route('admin.users', ['create' => 'teacher']) }}">Tạo giáo viên</a>
              </div>
            @endif
          </div>
          <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statusOptions as $status)<option value="{{ $status }}" @selected(old('_form') === 'create' && old('status', 'active') === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Môn học</label><input class="input" name="subject" value="{{ old('_form') === 'create' ? old('subject') : '' }}" maxlength="255"></div>
          <div class="form-group"><label class="label">Khối / cấp độ</label><input class="input" name="grade_level" value="{{ old('_form') === 'create' ? old('grade_level') : '' }}" maxlength="50"></div>
          <div class="form-group"><label class="label">Màu</label><input class="input" name="color" value="{{ old('_form') === 'create' ? old('color', '#3b82f6') : '#3b82f6' }}" maxlength="20"></div>
          <div class="form-group"><label class="label">Ký hiệu</label><input class="input" name="icon" value="{{ old('_form') === 'create' ? old('icon') : '' }}" maxlength="10" placeholder="VD: 10A"></div>
          <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="3" maxlength="1000">{{ old('_form') === 'create' ? old('description') : '' }}</textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminClassModal('create-class-modal')">Hủy</button>
        <button class="btn btn-primary" type="submit" @disabled($teachers->isEmpty())>Tạo lớp học</button>
      </div>
    </form>
  </div>
</div>

@foreach($classes as $class)
  <div class="modal-overlay" id="edit-class-{{ $class->id }}">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-class-title-{{ $class->id }}" style="max-width:44rem;">
      <form method="POST" action="{{ route('admin.classes.update', $class->id) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_form" value="edit-{{ $class->id }}">
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="edit-class-title-{{ $class->id }}">Sửa lớp học</h2>
            <p class="modal-desc">{{ $class->code }} · {{ $class->teacher?->name ?? 'Chưa rõ giáo viên' }}</p>
          </div>
          <button class="modal-close" type="button" onclick="closeAdminClassModal('edit-class-{{ $class->id }}')" aria-label="Đóng">×</button>
        </div>
        <div class="modal-body">
          <div class="class-modal-grid">
            <div class="form-group"><label class="label">Tên lớp</label><input class="input" name="name" value="{{ old('_form') === 'edit-'.$class->id ? old('name') : $class->name }}" required maxlength="255"></div>
            <div class="form-group"><label class="label">Mã lớp</label><input class="input" name="code" value="{{ old('_form') === 'edit-'.$class->id ? old('code') : $class->code }}" required maxlength="50"></div>
            <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id" required>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((old('_form') === 'edit-'.$class->id ? old('teacher_id') : $class->teacher_id) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statusOptions as $status)<option value="{{ $status }}" @selected((old('_form') === 'edit-'.$class->id ? old('status') : ($class->status ?? 'active')) === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Môn học</label><input class="input" name="subject" value="{{ old('_form') === 'edit-'.$class->id ? old('subject') : $class->subject }}" maxlength="255"></div>
            <div class="form-group"><label class="label">Khối / cấp độ</label><input class="input" name="grade_level" value="{{ old('_form') === 'edit-'.$class->id ? old('grade_level') : $class->grade_level }}" maxlength="50"></div>
            <div class="form-group"><label class="label">Màu</label><input class="input" name="color" value="{{ old('_form') === 'edit-'.$class->id ? old('color') : $class->color }}" maxlength="20"></div>
            <div class="form-group"><label class="label">Ký hiệu</label><input class="input" name="icon" value="{{ old('_form') === 'edit-'.$class->id ? old('icon') : $class->icon }}" maxlength="10"></div>
            <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="3" maxlength="1000">{{ old('_form') === 'edit-'.$class->id ? old('description') : $class->description }}</textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeAdminClassModal('edit-class-{{ $class->id }}')">Hủy</button>
          <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@push('scripts')
<script>
  function openAdminClassModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminClassModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminClassModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminClassModal(overlay.id);
      });
    }
  });

  const oldForm = @json(old('_form'));
  if (oldForm === 'create') {
    openAdminClassModal('create-class-modal');
  } else if (oldForm && oldForm.startsWith('edit-')) {
    openAdminClassModal('edit-class-' + oldForm.replace('edit-', ''));
  }
</script>
@endpush
@endsection
