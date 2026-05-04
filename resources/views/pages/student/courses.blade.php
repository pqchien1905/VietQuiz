{{-- Student: courses --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .student-course-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
  .student-course-card{display:flex;flex-direction:column;min-height:100%;position:relative}
  .student-course-cover{height:5.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;color:#fff}
  .student-course-icon{width:2.75rem;height:2.75rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);font-size:1.35rem;font-weight:800;line-height:1;flex-shrink:0;overflow:hidden}
  .student-course-icon svg{width:1.35rem;height:1.35rem;display:block}
  .student-course-icon span{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis}
  .student-course-title{font-weight:800;font-size:var(--text-base);line-height:1.25;color:#fff;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
  .student-course-meta{font-size:var(--text-xs);color:rgba(255,255,255,.82);margin-top:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .student-course-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem;text-align:center;margin-bottom:1rem}
  .student-course-stat{border:1px solid var(--border);border-radius:var(--radius-md);padding:.625rem .5rem;background:color-mix(in srgb,var(--muted) 35%,transparent)}
  .student-course-stat strong{display:block;font-size:var(--text-lg);line-height:1;color:var(--foreground)}
  .student-course-stat span{display:block;font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem}
  .course-card-link{position:absolute;inset:0;z-index:1}
  .course-card-actions{position:relative;z-index:2}
  .filter-form .search-input-wrapper{min-width:16rem}
  @media (max-width:900px){.student-course-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media (max-width:640px){.student-course-summary{grid-template-columns:1fr}.filter-form .search-input-wrapper{min-width:0}.student-course-cover{height:auto;min-height:5.25rem}}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Khóa học của tôi</h1>
        <p>Theo dõi tiến độ, bài kiểm tra, bài tập và điểm số trong các khóa học đã tham gia.</p>
      </div>
      <a href="{{ route('student.join-class') }}" class="btn btn-primary gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tham gia lớp mới
      </a>
    </div>
  </div>

  <div class="student-course-summary stagger-children">
    <div class="stat-card">
      <div class="stat-card__value">{{ number_format($summary['total']) }}</div>
      <div class="stat-card__label">Khóa học</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--primary);">{{ number_format($summary['active']) }}</div>
      <div class="stat-card__label">Đang học</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--success);">{{ number_format($summary['completed']) }}</div>
      <div class="stat-card__label">Đã hoàn thành</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--warning);">{{ number_format($summary['pending_items']) }}</div>
      <div class="stat-card__label">Việc cần làm</div>
    </div>
  </div>

  <form class="toolbar filter-form stagger-children" method="GET" action="{{ route('student.courses') }}">
    <div class="toolbar-left">
      <div class="search-input-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm khóa học, giáo viên, lớp..." />
      </div>
      <select class="input select" name="status" style="max-width:12rem;">
        <option value="all" @selected($filters['status'] === 'all')>Tất cả trạng thái</option>
        <option value="active" @selected($filters['status'] === 'active')>Đang học</option>
        <option value="completed" @selected($filters['status'] === 'completed')>Đã hoàn thành</option>
        <option value="draft" @selected($filters['status'] === 'draft')>Chưa mở</option>
      </select>
      <select class="input select" name="class_id" style="max-width:12rem;">
        <option value="">Tất cả lớp</option>
        @foreach($classes as $class)
          <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="toolbar-right">
      <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
      @if($filters['q'] !== '' || $filters['status'] !== 'all' || $filters['class_id'])
        <a class="btn btn-ghost btn-sm" href="{{ route('student.courses') }}">Xóa lọc</a>
      @endif
      <span class="text-sm text-muted">{{ $courses->total() }} kết quả</span>
    </div>
  </form>

  @if($courses->count() === 0)
    <div class="empty-state card" style="margin-top:1.25rem;">
      <div class="empty-state-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m5 10 7 4 7-4"/><path d="m5 15 7 4 7-4"/></svg>
      </div>
      <h3>Chưa có khóa học phù hợp</h3>
      <p>Tham gia lớp bằng mã mời hoặc điều chỉnh bộ lọc để xem các khóa học của bạn.</p>
      <a href="{{ route('student.join-class') }}" class="btn btn-primary">Tham gia lớp</a>
    </div>
  @else
    <div class="cards-grid stagger-children" style="margin-top:1.25rem;">
      @foreach($courses as $course)
        @php
          $courseColor = $course->color ?: '#2563eb';
          $progressClass = $course->progress_pct >= 100 ? 'success' : ($course->progress_pct < 35 ? 'warning' : '');
          $statusLabel = match($course->learning_status) {
              'completed' => 'Hoàn thành',
              'draft' => 'Chưa mở',
              default => 'Đang học',
          };
          $statusClass = match($course->learning_status) {
              'completed' => 'badge-success',
              'draft' => 'badge-outline',
              default => 'badge-primary',
          };
          $nextDueColor = $course->next_due_at && $course->next_due_at->diffInHours(now()) <= 48 ? 'var(--destructive)' : 'var(--muted-foreground)';
        @endphp

        <article class="card hover-lift student-course-card">
          <a class="course-card-link" href="{{ route('student.courses.show', $course) }}" aria-label="Mở khóa học {{ $course->name }}"></a>

          <div class="student-course-cover" style="background:linear-gradient(135deg,{{ $courseColor }},color-mix(in srgb,{{ $courseColor }} 74%,#111827));">
            <div style="display:flex;align-items:center;gap:.875rem;min-width:0;">
              <div class="student-course-icon">
                <x-display-icon :icon="$course->icon" :label="$course->name" />
              </div>
              <div style="min-width:0;">
                <div class="student-course-title">{{ $course->name }}</div>
                <div class="student-course-meta">{{ $course->teacher?->name ?? 'Chưa có giáo viên' }}@if($course->classModel) · {{ $course->classModel->name }}@endif</div>
              </div>
            </div>
            <span class="badge {{ $statusClass }}" style="background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.28);">{{ $statusLabel }}</span>
          </div>

          <div class="card-content" style="flex:1;">
            @if($course->description)
              <p class="text-sm text-muted" style="line-height:1.55;margin-bottom:1rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $course->description }}</p>
            @endif

            <div class="student-course-stats">
              <div class="student-course-stat">
                <strong>{{ $course->published_quizzes_count }}</strong>
                <span>Bài kiểm tra</span>
              </div>
              <div class="student-course-stat">
                <strong>{{ $course->assignments_count }}</strong>
                <span>Bài tập</span>
              </div>
              <div class="student-course-stat">
                <strong style="color:{{ $course->avg_grade !== null ? 'var(--success)' : 'var(--muted-foreground)' }};">{{ $course->avg_grade !== null ? $course->avg_grade . '%' : '--' }}</strong>
                <span>Điểm TB</span>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between text-xs text-muted" style="margin-bottom:.35rem;">
                <span>Tiến độ khóa học</span>
                <span>{{ $course->completed_items }}/{{ $course->total_items }} mục · {{ $course->progress_pct }}%</span>
              </div>
              <div class="progress">
                <div class="progress-bar {{ $progressClass }}" style="width:{{ $course->progress_pct }}%;@if(!$progressClass) background:{{ $courseColor }}; @endif"></div>
              </div>
            </div>

            <div class="flex items-center justify-between gap-2" style="margin-top:1rem;">
              <span class="text-xs" style="color:{{ $nextDueColor }};">
                @if($course->next_due_at)
                  Hạn gần nhất: {{ $course->next_due_at->format('d/m/Y H:i') }}
                @else
                  Không có hạn đang chờ
                @endif
              </span>
              <span class="text-xs text-muted">{{ $course->students_count }} học viên</span>
            </div>
          </div>

          <div class="card-footer course-card-actions">
            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-primary btn-sm" style="flex:1;">Chi tiết</a>
            <a href="{{ route('student.quizzes', ['course_id' => $course->id]) }}" class="btn btn-outline btn-sm" style="flex:1;">Kiểm tra</a>
            <a href="{{ route('student.assignments', ['course_id' => $course->id]) }}" class="btn btn-outline btn-sm" style="flex:1;">Bài tập</a>
          </div>
        </article>
      @endforeach
    </div>

    <div style="margin-top:1.5rem;">
      {{ $courses->links('components.pagination') }}
    </div>
  @endif

  <div id="toast-container"></div>
@endsection
