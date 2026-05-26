{{-- Teacher: assignments --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.assignment-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1.25rem;transition:box-shadow var(--transition-fast),border-color var(--transition-fast)}
.assignment-card:hover{box-shadow:var(--shadow-md)}
.assignment-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.assignment-title{font-size:var(--text-base);font-weight:700;margin:0}
.assignment-meta{display:flex;gap:1rem;flex-wrap:wrap;font-size:var(--text-sm);color:var(--muted-foreground);margin-top:.5rem}
.assignment-actions{display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end}
.assignment-progress{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem}
.filter-card .card-content{display:flex;flex-direction:column;gap:1rem}
.filter-row{display:grid;grid-template-columns:minmax(16rem,1.5fr) repeat(3,minmax(9rem,1fr)) auto auto;gap:.75rem;align-items:end}
.filter-field{display:flex;flex-direction:column;gap:.375rem;min-width:0}
.filter-field label{font-size:var(--text-xs);color:var(--muted-foreground);font-weight:700}
.status-tabs{display:flex;gap:.5rem;flex-wrap:wrap}
.status-tab{display:inline-flex;align-items:center;gap:.375rem;padding:.5rem .75rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);color:var(--foreground);font-size:var(--text-sm);text-decoration:none}
.status-tab.active{border-color:var(--primary);background:color-mix(in srgb,var(--primary) 10%,transparent);color:var(--primary);font-weight:700}
.drop-input{border:2px dashed var(--border);border-radius:var(--radius-md);padding:1rem;background:var(--muted)}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:1100px){.filter-row{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.filter-row,.assignment-progress,.form-grid-2{grid-template-columns:1fr}.assignment-actions,.filter-row .btn{width:100%;justify-content:center}}
</style>
@endpush

@section('content')
@php
  $filters = $filters ?? ['q' => '', 'status' => 'all', 'class_id' => null, 'course_id' => null, 'type' => null];
  $assignmentPageItems = method_exists($assignments, 'getCollection') ? $assignments->getCollection() : collect($assignments);
  $statusCounts = $statusCounts ?? ['all' => $assignments->count(), 'active' => 0, 'grading' => 0, 'overdue' => 0, 'completed' => 0];
  $summary = $summary ?? ['total_submissions' => $assignmentPageItems->sum('submissions_count'), 'graded_submissions' => $assignmentPageItems->sum('graded_submissions_count'), 'expected_submissions' => 0];
  $totalSubmissions = $summary['total_submissions'];
  $gradedSubmissions = $summary['graded_submissions'];
  $expectedSubmissions = $summary['expected_submissions'];
  $hasFilters = collect($filters)->filter(fn($value, $key) => $key !== 'status' ? $value !== null && $value !== '' : $value !== 'all')->isNotEmpty();
  $typeLabels = ['file' => 'Nộp file', 'text' => 'Trả lời văn bản', 'online' => 'Làm trực tuyến'];
  $typeBadges = ['file' => 'badge-info', 'text' => 'badge-default', 'online' => 'badge-success'];
  $statusLabels = ['active' => 'Đang hoạt động', 'grading' => 'Đang chấm', 'overdue' => 'Quá hạn', 'completed' => 'Hoàn thành'];
  $statusBadges = ['active' => 'badge-success', 'grading' => 'badge-warning', 'overdue' => 'badge-danger', 'completed' => 'badge-info'];
@endphp

<div class="page-header stagger-children">
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1>Bài tập</h1>
      <p style="color:var(--muted-foreground);margin-top:.25rem;">Tạo, giao và theo dõi tiến độ nộp bài của học sinh.</p>
    </div>
    <button class="btn btn-primary gap-2" type="button" onclick="openAssignmentModal()">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tạo bài tập
    </button>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger" style="margin-bottom:1rem;">{{ session('error') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
@endif

<div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
  <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đang hoạt động</div><div class="stat-card__value" style="color:var(--success);">{{ $statusCounts['active'] }}</div></div>
  <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Cần chấm</div><div class="stat-card__value" style="color:var(--warning);">{{ max(0, $totalSubmissions - $gradedSubmissions) }}</div></div>
  <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Hoàn thành</div><div class="stat-card__value">{{ $statusCounts['completed'] }}</div></div>
  <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài nộp</div><div class="stat-card__value">{{ $totalSubmissions }}{{ $expectedSubmissions ? ' / ' . $expectedSubmissions : '' }}</div></div>
</div>

<div class="card filter-card" style="margin-bottom:1rem;">
  <div class="card-content">
    <div class="status-tabs">
      @foreach(['all' => 'Tất cả', 'active' => 'Đang hoạt động', 'grading' => 'Đang chấm', 'overdue' => 'Quá hạn', 'completed' => 'Hoàn thành'] as $key => $label)
        @php
          $query = array_filter(array_merge($filters, ['status' => $key]), fn($value) => $value !== null && $value !== '');
        @endphp
        <a class="status-tab {{ ($filters['status'] ?? 'all') === $key ? 'active' : '' }}" href="{{ route('teacher.assignments', $query) }}">
          {{ $label }} <span class="badge badge-default">{{ $statusCounts[$key] }}</span>
        </a>
      @endforeach
    </div>

    <form method="GET" action="{{ route('teacher.assignments') }}" class="filter-row">
      <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">
      <div class="filter-field">
        <label for="assignment-search">Tìm kiếm</label>
        <input type="search" class="input" id="assignment-search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên bài, mô tả, lớp, khóa học...">
      </div>
      <div class="filter-field">
        <label for="assignment-class">Lớp học</label>
        <select class="input select" id="assignment-class" name="class_id">
          <option value="">Tất cả lớp</option>
          @foreach($classes as $class)
            <option value="{{ $class->id }}" @selected((string)($filters['class_id'] ?? '') === (string)$class->id)>{{ $class->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-field">
        <label for="assignment-course">Khóa học</label>
        <select class="input select" id="assignment-course" name="course_id">
          <option value="">Tất cả khóa học</option>
          @foreach($courses as $course)
            <option value="{{ $course->id }}" @selected((string)($filters['course_id'] ?? '') === (string)$course->id)>{{ $course->name ?? $course->title }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-field">
        <label for="assignment-type">Hình thức</label>
        <select class="input select" id="assignment-type" name="type">
          <option value="">Tất cả</option>
          @foreach($typeLabels as $value => $label)
            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Lọc</button>
      @if($hasFilters)
        <a class="btn btn-outline" href="{{ route('teacher.assignments') }}">Xóa lọc</a>
      @endif
    </form>
  </div>
</div>

<div class="stagger-children" style="display:flex;flex-direction:column;gap:.875rem;">
  @forelse($assignments as $assignment)
    @php
      $expected = (int) ($assignment->class?->students_count ?? 0);
      $submitted = (int) ($assignment->submissions_count ?? 0);
      $graded = (int) ($assignment->graded_submissions_count ?? 0);
      $submittedPct = $expected > 0 ? min(100, round($submitted / $expected * 100)) : 0;
      $gradedPct = $submitted > 0 ? min(100, round($graded / $submitted * 100)) : 0;
      $status = $assignment->computed_status;
      $dueText = $assignment->due_at ? $assignment->due_at->format('d/m/Y H:i') : 'Không giới hạn';
      $courseName = $assignment->course?->name ?? $assignment->course?->title;
    @endphp
    <div class="assignment-card">
      <div class="assignment-head">
        <div style="min-width:0;flex:1;">
          <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
            <h3 class="assignment-title">{{ $assignment->title }}</h3>
            <span class="badge {{ $statusBadges[$status] ?? 'badge-default' }}">{{ $statusLabels[$status] ?? $status }}</span>
            <span class="badge {{ $typeBadges[$assignment->type] ?? 'badge-default' }}">{{ $typeLabels[$assignment->type] ?? $assignment->type }}</span>
          </div>
          <div class="assignment-meta">
            <span>📚 {{ $assignment->class?->name ?? 'Chưa gắn lớp' }}</span>
            @if($courseName)<span>📖 {{ $courseName }}</span>@endif
            <span style="{{ $status === 'overdue' ? 'color:var(--destructive);font-weight:700;' : '' }}">📅 {{ $dueText }}</span>
            <span>⭐ {{ $assignment->total_points }} điểm</span>
            @if($assignment->attachment)
              <a href="{{ route('teacher.assignments.attachment.preview', $assignment) }}" target="_blank" style="color:var(--primary);text-decoration:none;">Xem tài liệu</a>
            @endif
          </div>
          @if($assignment->description)
            <p style="font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.6;margin:.75rem 0 0;">{{ \Illuminate\Support\Str::limit($assignment->description, 220) }}</p>
          @endif
        </div>
        <div class="assignment-actions">
          <a href="{{ route('teacher.assignments.show', $assignment) }}" class="btn btn-outline btn-sm">Xem chi tiết</a>
          <a href="{{ route('teacher.assignments.grading-board', $assignment) }}" class="btn {{ $submitted > $graded ? 'btn-primary' : 'btn-outline' }} btn-sm">
            {{ $submitted > $graded ? 'Chấm bài' : 'Xem bài nộp' }}
          </a>
          <button class="btn btn-ghost btn-sm" type="button" onclick="openEditAssignment({{ $assignment->id }})">Sửa</button>
          <form method="POST" action="{{ route('teacher.assignments.destroy', $assignment) }}" data-confirm="Xóa bài tập {{ $assignment->title }}?">
            @csrf
            @method('DELETE')
            <button class="btn btn-ghost btn-sm" style="color:var(--destructive);" type="submit">Xóa</button>
          </form>
        </div>
      </div>
      <div class="assignment-progress">
        <div>
          <div style="display:flex;justify-content:space-between;font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;"><span>Đã nộp</span><span>{{ $submitted }}/{{ $expected ?: '—' }}{{ $expected ? " ({$submittedPct}%)" : '' }}</span></div>
          <div class="progress" style="height:.4rem;"><div class="progress-bar" style="width:{{ $submittedPct }}%;background:{{ $submittedPct >= 80 ? 'var(--success)' : 'var(--info)' }};"></div></div>
        </div>
        <div>
          <div style="display:flex;justify-content:space-between;font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;"><span>Đã chấm</span><span>{{ $graded }}/{{ $submitted }}{{ $submitted ? " ({$gradedPct}%)" : '' }}</span></div>
          <div class="progress" style="height:.4rem;"><div class="progress-bar" style="width:{{ $gradedPct }}%;background:{{ $gradedPct >= 100 ? 'var(--success)' : 'var(--warning)' }};"></div></div>
        </div>
      </div>

    </div>
  @empty
    <div class="empty-state">
      <div style="font-size:3rem;">📋</div>
      <h3>Không có bài tập</h3>
      <p>{{ $hasFilters ? 'Không tìm thấy bài tập phù hợp với bộ lọc.' : 'Tạo bài tập đầu tiên để giao cho học sinh.' }}</p>
      @if(!$hasFilters)
        <button class="btn btn-primary" type="button" onclick="openAssignmentModal()">Tạo bài tập</button>
      @endif
    </div>
  @endforelse
</div>

{{ $assignments->links('components.pagination') }}

<div class="modal-overlay" id="assignment-modal">
  <div class="modal" style="max-width:42rem;">
    <div class="modal-header">
      <div><h3 class="modal-title">Tạo bài tập</h3><p class="modal-desc">Giao bài tập cho một lớp học và tùy chọn gắn với khóa học.</p></div>
      <button class="modal-close" type="button" onclick="closeModal('assignment-modal')">×</button>
    </div>
    <form method="POST" action="{{ route('teacher.assignments.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
        @include('pages.teacher.partials.assignment-form-fields', ['assignment' => null, 'classes' => $classes, 'courses' => $courses, 'typeLabels' => $typeLabels])
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeModal('assignment-modal')">Hủy</button>
        <button class="btn btn-primary" type="submit">Tạo bài tập</button>
      </div>
    </form>
  </div>
</div>

@foreach($assignments as $assignment)
<div class="modal-overlay" id="edit-assignment-{{ $assignment->id }}">
  <div class="modal" style="max-width:42rem;">
    <div class="modal-header">
      <div><h3 class="modal-title">Sửa bài tập</h3><p class="modal-desc">{{ $assignment->title }}</p></div>
      <button class="modal-close" type="button" onclick="closeModal('edit-assignment-{{ $assignment->id }}')">×</button>
    </div>
    <form method="POST" action="{{ route('teacher.assignments.update', $assignment) }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
        @include('pages.teacher.partials.assignment-form-fields', ['assignment' => $assignment, 'classes' => $classes, 'courses' => $courses, 'typeLabels' => $typeLabels])
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeModal('edit-assignment-{{ $assignment->id }}')">Hủy</button>
        <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
      </div>
    </form>
  </div>
</div>
@endforeach

<div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function() {
  'use strict';

  window.openModal = function(id) {
    document.getElementById(id)?.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  window.closeModal = function(id) {
    document.getElementById(id)?.classList.remove('open');
    document.body.style.overflow = '';
  };

  window.openAssignmentModal = function() {
    openModal('assignment-modal');
  };

  window.openEditAssignment = function(id) {
    openModal('edit-assignment-' + id);
  };

  document.querySelectorAll('.modal-overlay').forEach(function(modal) {
    modal.addEventListener('click', function(event) {
      if (event.target === modal) closeModal(modal.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.modal-overlay.open').forEach(function(modal) {
      closeModal(modal.id);
    });
  });
})();
</script>
@endpush
