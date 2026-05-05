@extends('layouts.admin')

@section('title', 'Admin - Bài tập')
@section('page-title', 'Bài tập')
@section('page-description', 'Quản trị bài tập theo phạm vi giao bài, hạn nộp, loại nộp, bài nộp và trạng thái chấm điểm.')

@php
  $typeOptions = ['file' => 'Tệp đính kèm', 'text' => 'Văn bản', 'online' => 'Làm trực tuyến'];
  $typeBadges = ['file' => 'badge-info', 'text' => 'badge-outline', 'online' => 'badge-success'];
  $summaryCards = [
    ['label' => 'Tổng bài tập', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.assignments', ['state' => 'all'])],
    ['label' => 'Đang mở', 'value' => $summary['open'], 'tone' => 'var(--success)', 'href' => route('admin.assignments', ['scope' => 'open'])],
    ['label' => 'Quá hạn', 'value' => $summary['overdue'], 'tone' => 'var(--destructive)', 'href' => route('admin.assignments', ['scope' => 'overdue'])],
    ['label' => 'Bài nộp', 'value' => $summary['submissions'], 'tone' => 'var(--info)', 'href' => route('admin.assignments', ['sort' => 'submissions'])],
    ['label' => 'Đã xóa', 'value' => $summary['deleted'], 'tone' => 'var(--destructive)', 'href' => route('admin.assignments', ['state' => 'deleted'])],
  ];
  $operationCards = [
    ['label' => 'Chờ chấm', 'value' => $summary['ungraded'], 'desc' => 'Bài nộp chưa có điểm/nhận xét.', 'href' => route('admin.assignments', ['scope' => 'grading'])],
    ['label' => 'Chưa có bài nộp', 'value' => $summary['no_submissions'], 'desc' => 'Bài tập chưa nhận được lượt nộp nào.', 'href' => route('admin.assignments', ['scope' => 'no_submissions'])],
    ['label' => 'Chưa gán phạm vi', 'value' => $summary['unassigned'], 'desc' => 'Bài tập chưa gắn lớp hoặc khóa học.', 'href' => route('admin.assignments', ['scope' => 'unassigned'])],
  ];
@endphp

@push('styles')
<style>
  .assignments-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .assignments-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .assignments-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .assignments-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .admin-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .admin-summary-grid .stat-card { min-height:7.25rem; }
  .assignment-ops-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
  .assignment-ops-card { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); color:inherit; text-decoration:none; box-shadow:var(--shadow-sm); }
  .assignment-ops-card strong { display:block; font-size:var(--text-xl); line-height:1; margin-top:.35rem; color:var(--warning); }
  .assignment-ops-card span { display:block; color:var(--muted-foreground); font-size:var(--text-sm); margin-top:.35rem; }
  .assignment-filter-grid { display:grid; grid-template-columns:minmax(260px,1fr) repeat(7,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .assignment-cell { min-width:18rem; }
  .assignment-tags { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.45rem; }
  .assignment-metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; min-width:15rem; }
  .assignment-metric { padding:.55rem .65rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .assignment-metric strong { display:block; line-height:1.1; }
  .assignment-metric span { display:block; margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-xs); white-space:nowrap; }
  .assignment-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:12rem; }
  .assignment-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .assignment-modal-grid .full { grid-column:1/-1; }
  .assignment-empty-teacher { margin-top:.5rem; padding:.75rem; border:1px solid color-mix(in srgb,var(--warning) 35%,var(--border)); border-radius:var(--radius-md); background:color-mix(in srgb,var(--warning) 10%,var(--card)); color:var(--muted-foreground); font-size:var(--text-sm); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
  @media (max-width:1380px) { .assignment-filter-grid { grid-template-columns:1fr 1fr 1fr; } }
  @media (max-width:1100px) { .admin-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .assignment-ops-grid { grid-template-columns:1fr; } }
  @media (max-width:820px) { .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .assignment-modal-grid { grid-template-columns:1fr; } .assignment-modal-grid .full { grid-column:auto; } .assignment-metrics { grid-template-columns:1fr 1fr; min-width:0; } }
  @media (max-width:620px) { .admin-summary-grid,.assignment-filter-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('actions')
  <button class="btn btn-primary btn-sm" type="button" onclick="openAdminAssignmentModal('create-assignment-modal')">Thêm bài tập</button>
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

<section class="assignment-ops-grid">
  @foreach($operationCards as $card)
    <a class="assignment-ops-card" href="{{ $card['href'] }}">
      <div>
        <div class="stat-card__label">{{ $card['label'] }}</div>
        <strong>{{ number_format($card['value']) }}</strong>
        <span>{{ $card['desc'] }}</span>
      </div>
      <span class="badge badge-warning">Cần xử lý</span>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header assignments-header">
    <div class="assignments-title">
      <h3>Danh sách bài tập</h3>
      <p>Hiển thị {{ $assignments->firstItem() ?? 0 }}-{{ $assignments->lastItem() ?? 0 }} trên {{ number_format($assignments->total()) }} kết quả.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openAdminAssignmentModal('create-assignment-modal')">Thêm bài tập</button>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="assignment-filter-grid">
      <div class="form-group"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Tên hoặc mô tả bài tập"></div>
      <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id"><option value="">Tất cả</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Lớp</label><select class="input select" name="class_id"><option value="">Tất cả</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Tất cả</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Loại</label><select class="input select" name="type"><option value="">Tất cả</option>@foreach($typeOptions as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Vận hành</label><select class="input select" name="scope"><option value="">Tất cả</option><option value="open" @selected(request('scope') === 'open')>Đang mở</option><option value="overdue" @selected(request('scope') === 'overdue')>Quá hạn</option><option value="grading" @selected(request('scope') === 'grading')>Chờ chấm</option><option value="no_submissions" @selected(request('scope') === 'no_submissions')>Chưa có bài nộp</option><option value="unassigned" @selected(request('scope') === 'unassigned')>Chưa gán phạm vi</option></select></div>
      <div class="form-group"><label class="label">Dữ liệu</label><select class="input select" name="state"><option value="active" @selected(request('state', 'active') === 'active')>Đang dùng</option><option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option><option value="all" @selected(request('state') === 'all')>Tất cả</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sort"><option value="">Mới nhất</option><option value="due" @selected(request('sort') === 'due')>Hạn gần nhất</option><option value="submissions" @selected(request('sort') === 'submissions')>Nhiều bài nộp</option><option value="title" @selected(request('sort') === 'title')>Tên A-Z</option><option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.assignments') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Bài tập</th><th>Phạm vi</th><th>Hạn nộp</th><th>Số liệu</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($assignments as $assignment)
        @php
          $expected = ($assignment->class?->students_count ?? 0) + ($assignment->course?->students_count ?? 0);
          $isOverdue = $assignment->due_at && $assignment->due_at->isPast();
        @endphp
        <tr style="{{ $assignment->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
          <td>
            <div class="assignment-cell">
              <a class="admin-row-title" href="{{ route('admin.assignments.show', $assignment->id) }}">{{ $assignment->title }}</a>
              <div class="admin-row-meta">{{ $assignment->teacher?->name ?? 'Không rõ giáo viên' }}</div>
              <div class="assignment-tags">
                <span class="badge {{ $typeBadges[$assignment->type] ?? 'badge-outline' }}">{{ $typeOptions[$assignment->type] ?? \App\Support\AdminLabels::assignmentType($assignment->type) }}</span>
                @if($assignment->attachment)<span class="badge badge-outline">Có tệp</span>@endif
                @if(! $assignment->class_id && ! $assignment->course_id)<span class="badge badge-warning">Chưa gán phạm vi</span>@endif
                @if($assignment->submissions_count === 0)<span class="badge badge-outline">Chưa có bài nộp</span>@endif
                @if($assignment->graded_submissions_count < $assignment->submissions_count)<span class="badge badge-warning">Chờ chấm</span>@endif
                @if($assignment->trashed())<span class="badge badge-danger">Đã xóa</span>@endif
              </div>
            </div>
          </td>
          <td>
            <div class="admin-row-title">{{ $assignment->class?->name ?? 'Không gắn lớp' }}</div>
            <div class="admin-row-meta">{{ $assignment->course?->name ?? 'Không gắn khóa học' }}</div>
          </td>
          <td>
            @if($assignment->due_at)
              <span class="badge {{ $isOverdue ? 'badge-danger' : 'badge-success' }}">{{ $assignment->due_at->format('d/m/Y H:i') }}</span>
            @else
              <span class="badge badge-outline">Không hạn</span>
            @endif
          </td>
          <td>
            <div class="assignment-metrics">
              <div class="assignment-metric"><strong>{{ number_format($assignment->submissions_count) }}</strong><span>Bài nộp</span></div>
              <div class="assignment-metric"><strong>{{ number_format($assignment->graded_submissions_count) }}</strong><span>Đã chấm</span></div>
              <div class="assignment-metric"><strong>{{ number_format($expected) }}</strong><span>Dự kiến</span></div>
            </div>
          </td>
          <td>
            <div class="assignment-actions">
              <a class="btn btn-outline btn-sm" href="{{ route('admin.assignments.show', $assignment->id) }}">Chi tiết</a>
              <button class="btn btn-primary btn-sm" type="button" onclick="openAdminAssignmentModal('edit-assignment-{{ $assignment->id }}')">Sửa</button>
              @if($assignment->trashed())
                <form method="POST" action="{{ route('admin.assignments.restore', $assignment->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>
              @else
                <form method="POST" action="{{ route('admin.assignments.delete', $assignment->id) }}" data-confirm="Đưa bài tập {{ $assignment->title }} vào thùng rác?" data-confirm-ok="Xóa bài tập">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Xóa</button></form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có bài tập phù hợp với bộ lọc.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $assignments->links('components.pagination') }}</div>
</section>

@include('pages.admin.partials.assignment-form-modal', [
  'modalId' => 'create-assignment-modal',
  'mode' => 'create',
  'assignment' => null,
  'teachers' => $teachers,
  'classes' => $classes,
  'courses' => $courses,
  'typeOptions' => $typeOptions,
])

@foreach($assignments as $assignment)
  @include('pages.admin.partials.assignment-form-modal', [
    'modalId' => 'edit-assignment-'.$assignment->id,
    'mode' => 'edit',
    'assignment' => $assignment,
    'teachers' => $teachers,
    'classes' => $classes,
    'courses' => $courses,
    'typeOptions' => $typeOptions,
  ])
@endforeach

@push('scripts')
<script>
  function openAdminAssignmentModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminAssignmentModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminAssignmentModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminAssignmentModal(overlay.id);
      });
    }
  });

  const oldForm = @json(old('_form'));
  if (oldForm === 'create') {
    openAdminAssignmentModal('create-assignment-modal');
  } else if (oldForm && oldForm.startsWith('edit-')) {
    openAdminAssignmentModal('edit-assignment-' + oldForm.replace('edit-', ''));
  }
</script>
@endpush
@endsection
