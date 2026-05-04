{{-- Student: class-detail --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .class-detail-hero{border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:var(--card);margin-bottom:1.5rem}
  .class-detail-cover{padding:1.5rem;color:#fff;display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}
  .class-detail-icon{width:4rem;height:4rem;border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);font-size:1.75rem;font-weight:800;line-height:1;flex-shrink:0;overflow:hidden}
  .class-detail-icon svg{width:1.8rem;height:1.8rem;display:block}
  .class-detail-icon span{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis}
  .class-detail-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;padding:1rem;background:var(--card)}
  .class-tabs{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:1rem;overflow-x:auto}
  .class-tab{padding:.75rem 1rem;border:none;border-bottom:2px solid transparent;background:transparent;color:var(--muted-foreground);font-weight:700;font-size:var(--text-sm);cursor:pointer;white-space:nowrap}
  .class-tab.active{color:var(--primary);border-bottom-color:var(--primary)}
  .class-panel{display:none}
  .class-panel.active{display:block}
  .class-row{display:flex;align-items:center;gap:1rem;padding:1rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);margin-bottom:.75rem}
  .class-row-icon{width:2.5rem;height:2.5rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .student-chip{display:flex;align-items:center;gap:.75rem;padding:.75rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card)}
  .student-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.75rem}
  @media (max-width:800px){.class-detail-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.class-row{align-items:flex-start;flex-wrap:wrap}.class-row .row-actions{width:100%;padding-left:3.5rem}}
  @media (max-width:520px){.class-detail-stats{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
  $classColor = $class->color ?: '#2563eb';
  $formatDue = function ($date) {
      if (!$date) return 'Không giới hạn';
      if ($date->isToday()) return 'Hôm nay, ' . $date->format('H:i');
      if ($date->isTomorrow()) return 'Ngày mai, ' . $date->format('H:i');
      return $date->format('d/m/Y H:i');
  };
@endphp

<div class="breadcrumb">
  <a href="{{ route('student.classes') }}">Lớp học</a>
  <span class="breadcrumb-sep">/</span>
  <span class="active">{{ $class->name }}</span>
</div>

<section class="class-detail-hero">
  <div class="class-detail-cover" style="background:linear-gradient(135deg,{{ $classColor }},color-mix(in srgb,{{ $classColor }} 72%,#111827));">
    <div style="display:flex;align-items:flex-start;gap:1rem;min-width:0;">
      <div class="class-detail-icon">
        <x-display-icon :icon="$class->icon" :label="$class->name" />
      </div>
      <div style="min-width:0;">
        <div style="margin-bottom:.5rem;">
          <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.28);">{{ $class->status === 'archived' ? 'Đã lưu trữ' : 'Đang học' }}</span>
          @if($class->subject)
            <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.28);">{{ $class->subject }}</span>
          @endif
        </div>
        <h1 style="color:#fff;margin:0 0 .375rem;font-size:var(--text-3xl);">{{ $class->name }}</h1>
        <p style="color:rgba(255,255,255,.84);margin:0;">{{ $class->teacher?->name ?? 'Chưa có giáo viên' }} · Mã lớp {{ $class->code }}</p>
        @if($class->description)
          <p style="color:rgba(255,255,255,.78);margin:.75rem 0 0;max-width:46rem;line-height:1.6;">{{ $class->description }}</p>
        @endif
      </div>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <a href="{{ route('student.join-class') }}" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.35);">Tham gia lớp khác</a>
      <form method="POST" action="{{ route('student.classes.leave', $class) }}" data-confirm="Bạn chắc chắn muốn rời lớp này?">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline btn-sm" type="submit" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.35);">Rời lớp</button>
      </form>
    </div>
  </div>

  <div class="class-detail-stats">
    <div class="stat-card" style="box-shadow:none;">
      <div class="stat-card__value">{{ $class->learning_courses_count }}</div>
      <div class="stat-card__label">Khóa học</div>
    </div>
    <div class="stat-card" style="box-shadow:none;">
      <div class="stat-card__value">{{ $class->learning_quizzes_count }}</div>
      <div class="stat-card__label">Bài kiểm tra</div>
    </div>
    <div class="stat-card" style="box-shadow:none;">
      <div class="stat-card__value">{{ $class->learning_assignments_count }}</div>
      <div class="stat-card__label">Bài tập</div>
    </div>
    <div class="stat-card" style="box-shadow:none;">
      <div class="stat-card__value" style="color:var(--success);">{{ $class->progress_pct }}%</div>
      <div class="stat-card__label">Tiến độ</div>
    </div>
  </div>
</section>

<div class="class-tabs" role="tablist">
  <button class="class-tab active" type="button" data-tab="courses">Khóa học ({{ $class->courses->count() }})</button>
  <button class="class-tab" type="button" data-tab="quizzes">Bài kiểm tra ({{ $quizzes->count() }})</button>
  <button class="class-tab" type="button" data-tab="assignments">Bài tập ({{ $assignments->count() }})</button>
  <button class="class-tab" type="button" data-tab="students">Bạn cùng lớp ({{ $class->students->count() }})</button>
</div>

<div class="class-panel active" id="class-panel-courses">
  @forelse($class->courses as $course)
    <div class="class-row">
      <div class="class-row-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:800;">{{ $course->name }}</div>
        <div class="text-sm text-muted">{{ $course->quizzes_count }} bài kiểm tra · {{ $course->assignments_count }} bài tập</div>
      </div>
      <div class="row-actions">
        <a href="{{ route('student.courses.show', $course) }}" class="btn btn-primary btn-sm">Mở khóa học</a>
      </div>
    </div>
  @empty
    <div class="empty-state card"><h3>Chưa có khóa học</h3><p>Giáo viên chưa thêm khóa học nào vào lớp này.</p></div>
  @endforelse
</div>

<div class="class-panel" id="class-panel-quizzes">
  @forelse($quizzes as $quiz)
    <div class="class-row">
      <div class="class-row-icon" style="background:color-mix(in srgb,var(--warning) 14%,transparent);color:var(--warning);">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><path d="M14 2v6h6"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:800;">{{ $quiz->title }}</div>
        <div class="text-sm text-muted">{{ $quiz->course?->name ?? $quiz->classModel?->name ?? 'Lớp' }} · {{ $quiz->questions_count }} câu · Hạn: {{ $formatDue($quiz->end_at) }}</div>
      </div>
      <div class="row-actions" style="display:flex;gap:.5rem;align-items:center;">
        @if($quiz->is_completed)
          <span class="badge badge-success">Đã làm</span>
          <a href="{{ route('student.quiz-result', $quiz) }}" class="btn btn-outline btn-sm">Kết quả</a>
        @elseif($quiz->is_available)
          <a href="{{ route('student.quiz-take', $quiz) }}" class="btn btn-primary btn-sm">Làm bài</a>
        @else
          <span class="badge badge-outline">Chưa mở/quá hạn</span>
        @endif
      </div>
    </div>
  @empty
    <div class="empty-state card"><h3>Chưa có bài kiểm tra</h3><p>Khi giáo viên giao bài, bạn sẽ thấy ở đây.</p></div>
  @endforelse
</div>

<div class="class-panel" id="class-panel-assignments">
  @forelse($assignments as $assignment)
    <div class="class-row">
      <div class="class-row-icon" style="background:color-mix(in srgb,var(--info) 12%,transparent);color:var(--info);">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:800;">{{ $assignment->title }}</div>
        <div class="text-sm text-muted">{{ $assignment->course?->name ?? $assignment->class?->name ?? 'Lớp' }} · {{ $assignment->total_points ?? 100 }} điểm · Hạn: {{ $formatDue($assignment->due_at) }}</div>
      </div>
      <div class="row-actions" style="display:flex;gap:.5rem;align-items:center;">
        @if($assignment->is_submitted)
          <span class="badge badge-success">Đã nộp</span>
          <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-outline btn-sm">Xem</a>
        @elseif($assignment->is_available)
          <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-primary btn-sm">Nộp bài</a>
        @else
          <span class="badge badge-danger">Quá hạn</span>
        @endif
      </div>
    </div>
  @empty
    <div class="empty-state card"><h3>Chưa có bài tập</h3><p>Khi giáo viên giao bài tập, bạn sẽ thấy ở đây.</p></div>
  @endforelse
</div>

<div class="class-panel" id="class-panel-students">
  <div class="student-grid">
    @foreach($class->students as $student)
      <div class="student-chip">
        <div class="avatar avatar-md">{{ collect(explode(' ', $student->name))->map(fn($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}</div>
        <div style="min-width:0;">
          <div style="font-weight:700;">{{ $student->name }}</div>
          <div class="text-xs text-muted">{{ $student->email }}</div>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
  const panels = {
    courses: document.getElementById('class-panel-courses'),
    quizzes: document.getElementById('class-panel-quizzes'),
    assignments: document.getElementById('class-panel-assignments'),
    students: document.getElementById('class-panel-students')
  };

  document.querySelectorAll('.class-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      const target = tab.dataset.tab;
      document.querySelectorAll('.class-tab').forEach(function(item) { item.classList.remove('active'); });
      Object.values(panels).forEach(function(panel) { panel.classList.remove('active'); });
      tab.classList.add('active');
      panels[target].classList.add('active');
    });
  });
})();
</script>
@endpush
