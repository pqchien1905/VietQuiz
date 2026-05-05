@extends('layouts.admin')

@section('title', 'Admin - Bài kiểm tra')
@section('page-title', 'Bài kiểm tra')
@section('page-description', 'Quản trị quiz theo trạng thái xuất bản, phạm vi giao bài, câu hỏi, lịch mở và lượt làm.')

@php
  $statusOptions = ['draft', 'published', 'closed'];
  $typeOptions = ['exam' => 'Kiểm tra', 'practice' => 'Luyện tập'];
  $statusBadges = ['draft' => 'badge-warning', 'published' => 'badge-success', 'closed' => 'badge-danger'];
  $summaryCards = [
    ['label' => 'Tổng quiz', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.quizzes', ['state' => 'all'])],
    ['label' => 'Đã xuất bản', 'value' => $summary['published'], 'tone' => 'var(--success)', 'href' => route('admin.quizzes', ['status' => 'published'])],
    ['label' => 'Bản nháp', 'value' => $summary['draft'], 'tone' => 'var(--warning)', 'href' => route('admin.quizzes', ['status' => 'draft'])],
    ['label' => 'Đã đóng', 'value' => $summary['closed'], 'tone' => 'var(--destructive)', 'href' => route('admin.quizzes', ['status' => 'closed'])],
    ['label' => 'Lượt nộp', 'value' => $summary['attempts'], 'tone' => 'var(--info)', 'href' => route('admin.quizzes', ['sort' => 'attempts'])],
  ];
  $operationCards = [
    ['label' => 'Chưa có câu hỏi', 'value' => $summary['no_questions'], 'desc' => 'Không nên xuất bản khi chưa có câu hỏi.', 'href' => route('admin.quizzes', ['scope' => 'no_questions'])],
    ['label' => 'Chờ chấm', 'value' => $summary['ungraded'], 'desc' => 'Lượt nộp chưa được xác nhận điểm.', 'href' => route('admin.quizzes', ['scope' => 'ungraded'])],
    ['label' => 'Chưa mở lịch', 'value' => $summary['scheduled'], 'desc' => 'Quiz đã xuất bản nhưng start_at ở tương lai.', 'href' => route('admin.quizzes', ['scope' => 'scheduled'])],
  ];
@endphp

@push('styles')
<style>
  .admin-quizzes-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .admin-quizzes-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .admin-quizzes-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .admin-quizzes-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .admin-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .admin-summary-grid .stat-card { min-height:7.25rem; }
  .quiz-ops-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
  .quiz-ops-card { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); color:inherit; text-decoration:none; box-shadow:var(--shadow-sm); }
  .quiz-ops-card strong { display:block; font-size:var(--text-xl); line-height:1; margin-top:.35rem; color:var(--warning); }
  .quiz-ops-card span { display:block; color:var(--muted-foreground); font-size:var(--text-sm); margin-top:.35rem; }
  .quiz-filter-grid { display:grid; grid-template-columns:minmax(260px,1fr) repeat(7,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .quiz-cell { min-width:18rem; }
  .quiz-name-row { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .quiz-state-tags { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.45rem; }
  .quiz-metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; min-width:15rem; }
  .quiz-metric { padding:.55rem .65rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .quiz-metric strong { display:block; line-height:1.1; }
  .quiz-metric span { display:block; margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-xs); white-space:nowrap; }
  .quiz-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:12rem; }
  .quiz-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .quiz-modal-grid .full { grid-column:1/-1; }
  .quiz-checks { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
  .quiz-check { padding:.75rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .quiz-empty-teacher { margin-top:.5rem; padding:.75rem; border:1px solid color-mix(in srgb,var(--warning) 35%,var(--border)); border-radius:var(--radius-md); background:color-mix(in srgb,var(--warning) 10%,var(--card)); color:var(--muted-foreground); font-size:var(--text-sm); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
  @media (max-width:1380px) { .quiz-filter-grid { grid-template-columns:1fr 1fr 1fr; } }
  @media (max-width:1100px) { .admin-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .quiz-ops-grid,.quiz-checks { grid-template-columns:1fr; } }
  @media (max-width:820px) { .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .quiz-modal-grid { grid-template-columns:1fr; } .quiz-modal-grid .full { grid-column:auto; } .quiz-metrics { grid-template-columns:1fr 1fr; min-width:0; } }
  @media (max-width:620px) { .admin-summary-grid,.quiz-filter-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('actions')
  <button class="btn btn-primary btn-sm" type="button" onclick="openAdminQuizModal('create-quiz-modal')">Thêm bài kiểm tra</button>
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

<section class="quiz-ops-grid">
  @foreach($operationCards as $card)
    <a class="quiz-ops-card" href="{{ $card['href'] }}">
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
  <div class="card-header admin-quizzes-header">
    <div class="admin-quizzes-title">
      <h3>Danh sách bài kiểm tra</h3>
      <p>Hiển thị {{ $quizzes->firstItem() ?? 0 }}-{{ $quizzes->lastItem() ?? 0 }} trên {{ number_format($quizzes->total()) }} kết quả.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openAdminQuizModal('create-quiz-modal')">Thêm bài kiểm tra</button>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="quiz-filter-grid">
      <div class="form-group"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Tiêu đề hoặc mô tả"></div>
      <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id"><option value="">Tất cả</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Lớp</label><select class="input select" name="class_id"><option value="">Tất cả</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Tất cả</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Loại</label><select class="input select" name="quiz_type"><option value="">Tất cả</option>@foreach($typeOptions as $value => $label)<option value="{{ $value }}" @selected(request('quiz_type') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="">Tất cả</option>@foreach($statusOptions as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Dữ liệu</label><select class="input select" name="state"><option value="active" @selected(request('state', 'active') === 'active')>Đang dùng</option><option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option><option value="all" @selected(request('state') === 'all')>Tất cả</option></select></div>
      <div class="form-group"><label class="label">Vận hành</label><select class="input select" name="scope"><option value="">Tất cả</option><option value="no_questions" @selected(request('scope') === 'no_questions')>Chưa có câu hỏi</option><option value="unassigned" @selected(request('scope') === 'unassigned')>Chưa gán phạm vi</option><option value="scheduled" @selected(request('scope') === 'scheduled')>Chưa mở lịch</option><option value="expired" @selected(request('scope') === 'expired')>Quá hạn</option><option value="ungraded" @selected(request('scope') === 'ungraded')>Chờ chấm</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sort"><option value="">Mới nhất</option><option value="attempts" @selected(request('sort') === 'attempts')>Nhiều lượt nộp</option><option value="questions" @selected(request('sort') === 'questions')>Nhiều câu hỏi</option><option value="title" @selected(request('sort') === 'title')>Tên A-Z</option><option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.quizzes') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Bài kiểm tra</th><th>Phạm vi</th><th>Số liệu</th><th>Trạng thái</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($quizzes as $quiz)
        @php
          $duration = $quiz->time_limit ?? $quiz->duration_minutes ?? 0;
          $hasSchedule = $quiz->start_at || $quiz->end_at;
        @endphp
        <tr style="{{ $quiz->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
          <td>
            <div class="quiz-cell">
              <div class="quiz-name-row">
                <a class="admin-row-title" href="{{ route('admin.quizzes.show', $quiz->id) }}">{{ $quiz->title }}</a>
                <span class="badge {{ ($quiz->quiz_type ?? 'exam') === 'practice' ? 'badge-info' : 'badge-warning' }}">{{ $typeOptions[$quiz->quiz_type ?? 'exam'] ?? 'Kiểm tra' }}</span>
              </div>
              <div class="admin-row-meta">Mã #{{ $quiz->id }} · {{ $quiz->teacher?->name ?? 'Không rõ giáo viên' }}</div>
              <div class="quiz-state-tags">
                @if($quiz->questions_count === 0)<span class="badge badge-warning">Chưa có câu hỏi</span>@endif
                @if(! $quiz->class_id && ! $quiz->course_id && empty($quiz->assigned_students))<span class="badge badge-outline">Chưa gán phạm vi</span>@endif
                @if($quiz->start_at && $quiz->start_at->isFuture())<span class="badge badge-info">Chưa mở</span>@endif
                @if($quiz->end_at && $quiz->end_at->isPast())<span class="badge badge-danger">Quá hạn</span>@endif
              </div>
            </div>
          </td>
          <td>
            <div class="admin-row-title">{{ $quiz->course?->name ?? 'Không gắn khóa học' }}</div>
            <div class="admin-row-meta">{{ $quiz->classModel?->name ?? 'Không gắn lớp' }}</div>
            @if($hasSchedule)
              <div class="admin-row-meta">{{ $quiz->start_at?->format('d/m/Y H:i') ?? 'Mở ngay' }} - {{ $quiz->end_at?->format('d/m/Y H:i') ?? 'Không hạn' }}</div>
            @endif
          </td>
          <td>
            <div class="quiz-metrics">
              <div class="quiz-metric"><strong>{{ number_format($quiz->questions_count) }}</strong><span>Câu hỏi</span></div>
              <div class="quiz-metric"><strong>{{ number_format($quiz->submitted_attempts_count) }}</strong><span>Lượt nộp</span></div>
              <div class="quiz-metric"><strong>{{ number_format($duration) }}</strong><span>Phút</span></div>
            </div>
          </td>
          <td>
            <span class="badge {{ $statusBadges[$quiz->status] ?? 'badge-outline' }}">{{ \App\Support\AdminLabels::status($quiz->status) }}</span>
            @if($quiz->trashed())<span class="badge badge-danger" style="margin-left:.35rem;">Đã xóa</span>@endif
          </td>
          <td>
            <div class="quiz-actions">
              <a class="btn btn-outline btn-sm" href="{{ route('admin.quizzes.show', $quiz->id) }}">Chi tiết</a>
              <button class="btn btn-primary btn-sm" type="button" onclick="openAdminQuizModal('edit-quiz-{{ $quiz->id }}')">Sửa</button>
              @if($quiz->trashed())
                <form method="POST" action="{{ route('admin.quizzes.restore', $quiz->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>
              @else
                <form method="POST" action="{{ route('admin.quizzes.delete', $quiz->id) }}" data-confirm="Đưa bài kiểm tra {{ $quiz->title }} vào thùng rác?" data-confirm-ok="Xóa bài kiểm tra">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Xóa</button></form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có bài kiểm tra phù hợp với bộ lọc.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $quizzes->links('components.pagination') }}</div>
</section>

@include('pages.admin.partials.quiz-form-modal', [
  'modalId' => 'create-quiz-modal',
  'mode' => 'create',
  'quiz' => null,
  'teachers' => $teachers,
  'classes' => $classes,
  'courses' => $courses,
  'statusOptions' => $statusOptions,
  'typeOptions' => $typeOptions,
])

@foreach($quizzes as $quiz)
  @include('pages.admin.partials.quiz-form-modal', [
    'modalId' => 'edit-quiz-'.$quiz->id,
    'mode' => 'edit',
    'quiz' => $quiz,
    'teachers' => $teachers,
    'classes' => $classes,
    'courses' => $courses,
    'statusOptions' => $statusOptions,
    'typeOptions' => $typeOptions,
  ])
@endforeach

@push('scripts')
<script>
  function openAdminQuizModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminQuizModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminQuizModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminQuizModal(overlay.id);
      });
    }
  });

  const oldForm = @json(old('_form'));
  if (oldForm === 'create') {
    openAdminQuizModal('create-quiz-modal');
  } else if (oldForm && oldForm.startsWith('edit-')) {
    openAdminQuizModal('edit-quiz-' + oldForm.replace('edit-', ''));
  }
</script>
@endpush
@endsection
