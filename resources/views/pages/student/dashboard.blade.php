{{-- Student Dashboard --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .student-dashboard { display: flex; flex-direction: column; gap: 1.5rem; }
  .student-hero { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:center; padding:1.25rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:linear-gradient(135deg, color-mix(in srgb, var(--primary) 10%, var(--card)), var(--card)); box-shadow:var(--shadow-sm); }
  .student-hero h1 { margin:0; font-size:clamp(1.5rem,3vw,2rem); line-height:1.2; }
  .student-hero p { margin:.35rem 0 0; color:var(--muted-foreground); }
  .dashboard-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(320px,.85fr); gap:1.5rem; align-items:start; }
  .stack { display:flex; flex-direction:column; gap:1rem; }
  .item-row { display:flex; gap:.875rem; align-items:flex-start; padding:.875rem 0; border-top:1px solid var(--border); }
  .item-row:first-child { border-top:0; padding-top:0; }
  .item-row:last-child { padding-bottom:0; }
  .item-icon { width:2.5rem; height:2.5rem; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .item-main { flex:1; min-width:0; }
  .item-title { font-weight:700; font-size:var(--text-sm); line-height:1.4; color:var(--foreground); }
  .item-meta { margin-top:.25rem; color:var(--muted-foreground); font-size:var(--text-xs); display:flex; flex-wrap:wrap; gap:.5rem; }
  .item-actions { display:flex; align-items:center; gap:.5rem; flex-shrink:0; }
  .progress-summary { display:grid; grid-template-columns:auto minmax(0,1fr); gap:1rem; align-items:center; }
  .progress-ring { width:5.25rem; height:5.25rem; border-radius:9999px; display:grid; place-items:center; background:conic-gradient(var(--primary) calc(var(--progress) * 1%), color-mix(in srgb, var(--muted) 80%, transparent) 0); position:relative; }
  .progress-ring::after { content:""; position:absolute; inset:.5rem; border-radius:inherit; background:var(--card); }
  .progress-ring strong { position:relative; z-index:1; font-size:var(--text-lg); }
  .mini-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
  .mini-card { border:1px solid var(--border); border-radius:var(--radius-md); padding:.875rem; background:var(--card); }
  .mini-card h4 { margin:0 0 .25rem; font-size:var(--text-sm); font-weight:700; }
  .mini-card p { margin:0; font-size:var(--text-xs); color:var(--muted-foreground); }
  .empty-block { text-align:center; padding:2rem 1rem; color:var(--muted-foreground); }
  .empty-block svg { margin:0 auto .75rem; color:var(--muted-foreground); }
  @media (max-width: 1024px) {
    .dashboard-grid, .student-hero { grid-template-columns:1fr; }
    .student-hero .hero-actions { justify-content:flex-start; }
  }
  @media (max-width: 640px) {
    .item-row { flex-wrap:wrap; }
    .item-actions { width:100%; justify-content:flex-start; padding-left:3.375rem; }
    .mini-grid { grid-template-columns:1fr; }
  }
</style>
@endpush

@section('content')
@php
  $avgGradeText = $avgGrade !== null ? number_format($avgGrade, 1) . '%' : 'Chưa có';
  $todayTextRaw = now()->translatedFormat('l, d/m/Y');
  $todayText = mb_strtoupper(mb_substr($todayTextRaw, 0, 1, 'UTF-8'), 'UTF-8')
    . mb_substr($todayTextRaw, 1, mb_strlen($todayTextRaw, 'UTF-8'), 'UTF-8');
  $formatDue = function ($date) {
      if (!$date) return 'Không giới hạn';
      if ($date->isToday()) return 'Hôm nay, ' . $date->format('H:i');
      if ($date->isTomorrow()) return 'Ngày mai, ' . $date->format('H:i');
      return $date->format('d/m/Y H:i');
  };
  $scopeName = fn ($item) => $item->classModel?->name ?? $item->class?->name ?? $item->course?->name ?? 'Chưa gắn lớp/khóa';
@endphp

<div class="student-dashboard">
  <section class="student-hero stagger-children">
    <div>
      <div class="badge badge-primary" style="margin-bottom:.75rem;">{{ $todayText }}</div>
      <h1>Chào mừng trở lại, {{ $user->name }}</h1>
      <p>Theo dõi bài cần làm, hạn nộp và tiến độ học tập của bạn trong một màn hình.</p>
    </div>
    <div class="hero-actions" style="display:flex;gap:.625rem;flex-wrap:wrap;justify-content:flex-end;">
      <a href="{{ route('student.join-class') }}" class="btn btn-outline">Tham gia lớp</a>
      <a href="{{ route('student.quizzes') }}" class="btn btn-primary">Làm bài kiểm tra</a>
    </div>
  </section>

  <div class="stats-grid stats-grid-4 stagger-children">
    <div class="stat-card"><div class="stat-card__value">{{ $courseCount }}</div><div class="stat-card__label">khóa đang theo học</div></div>
    <div class="stat-card"><div class="stat-card__value" style="color:var(--warning);">{{ $pendingQuizCount + $pendingAssignmentCount }}</div><div class="stat-card__label">{{ $pendingQuizCount }} kiểm tra, {{ $pendingAssignmentCount }} bài tập</div></div>
    <div class="stat-card"><div class="stat-card__value">{{ $classCount }}</div><div class="stat-card__label">lớp đã tham gia</div></div>
    <div class="stat-card"><div class="stat-card__value" style="color:var(--success);">{{ $avgGradeText }}</div><div class="stat-card__label">từ bài kiểm tra đã chấm</div></div>
  </div>

  <div class="dashboard-grid">
    <div class="stack">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Bài kiểm tra cần làm</h3></div>
        <div class="card-content">
          @forelse($upcomingQuizzes as $quiz)
            @php $isUrgent = $quiz->end_at && $quiz->end_at->isFuture() && $quiz->end_at->diffInHours(now()) <= 24; @endphp
            <div class="item-row">
              <div class="item-main">
                <div class="item-title">{{ $quiz->title }}</div>
                <div class="item-meta"><span>{{ $scopeName($quiz) }}</span><span>{{ $quiz->questions_count }} câu</span><span>Hạn: {{ $formatDue($quiz->end_at) }}</span></div>
              </div>
              <div class="item-actions">@if($isUrgent)<span class="badge badge-danger">Gấp</span>@endif <a href="{{ route('student.quiz-take', $quiz) }}" class="btn btn-primary btn-sm">Làm bài</a></div>
            </div>
          @empty
            <div class="empty-block"><p>Không có bài kiểm tra nào cần làm.</p></div>
          @endforelse
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Bài tập sắp đến hạn</h3></div>
        <div class="card-content">
          @forelse($dueSoonAssignments as $assignment)
            @php $isUrgent = $assignment->due_at && $assignment->due_at->isFuture() && $assignment->due_at->diffInHours(now()) <= 24; @endphp
            <div class="item-row">
              <div class="item-main">
                <div class="item-title">{{ $assignment->title }}</div>
                <div class="item-meta"><span>{{ $scopeName($assignment) }}</span><span>{{ $assignment->total_points ?? 100 }} điểm</span><span>Hạn: {{ $formatDue($assignment->due_at) }}</span></div>
              </div>
              <div class="item-actions">@if($isUrgent)<span class="badge badge-danger">Gấp</span>@endif <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-outline btn-sm">Nộp bài</a></div>
            </div>
          @empty
            <div class="empty-block"><p>Không có bài tập nào đang chờ nộp.</p></div>
          @endforelse
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Lớp và khóa học của bạn</h3></div>
        <div class="card-content">
          <div class="mini-grid">
            @forelse($recentCourses as $course)
              <a href="{{ route('student.course-detail', $course) }}" class="mini-card" style="text-decoration:none;color:inherit;"><h4>{{ $course->name }}</h4><p>{{ $course->teacher?->name ?? 'Chưa có giáo viên' }} · {{ $course->quizzes_count }} kiểm tra · {{ $course->assignments_count }} bài tập</p></a>
            @empty
              <div class="mini-card"><h4>Chưa có khóa học</h4><p>Tham gia lớp hoặc chờ giáo viên thêm bạn vào khóa học.</p></div>
            @endforelse
            @forelse($recentClasses as $class)
              <a href="{{ route('student.classes.show', $class) }}" class="mini-card" style="text-decoration:none;color:inherit;"><h4>{{ $class->name }}</h4><p>{{ $class->teacher?->name ?? 'Chưa có giáo viên' }} · {{ $class->courses_count }} khóa · {{ $class->quizzes_count }} kiểm tra · {{ $class->assignments_count }} bài tập</p></a>
            @empty
              <a href="{{ route('student.join-class') }}" class="mini-card" style="text-decoration:none;color:inherit;"><h4>Tham gia lớp</h4><p>Nhập mã lớp do giáo viên cung cấp để bắt đầu học.</p></a>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <aside class="stack">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Tiến độ học tập</h3></div>
        <div class="card-content"><div class="progress-summary"><div class="progress-ring" style="--progress: {{ $completionPercent }};"><strong>{{ $completionPercent }}%</strong></div><div><div style="font-weight:700;">{{ $completedLearningItems }}/{{ $totalLearningItems }} mục đã hoàn thành</div></div></div></div>
      </div>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Hoạt động gần đây</h3></div>
        <div class="card-content">
          @forelse($recentAttempts as $attempt)
            @php $pct = $attempt->pivot->total_points > 0 ? round(($attempt->pivot->score / $attempt->pivot->total_points) * 100) : null; @endphp
            <div class="item-row"><div class="item-main"><div class="item-title">{{ $attempt->title }}</div><div class="item-meta"><span>{{ $attempt->pivot->submitted_at ? \Carbon\Carbon::parse($attempt->pivot->submitted_at)->diffForHumans() : 'Vừa xong' }}</span><span>{{ $scopeName($attempt) }}</span></div></div><div class="item-actions"><span style="font-weight:800;">{{ $pct !== null ? $pct . '%' : 'Chờ chấm' }}</span></div></div>
          @empty
            <div class="empty-block"><p>Chưa có hoạt động nào.</p></div>
          @endforelse
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Thông báo mới</h3></div>
        <div class="card-content">
          @forelse($recentNotifications as $notification)
            <div class="item-row"><div class="item-main"><div class="item-title">{{ $notification->title }}</div><div class="item-meta"><span>{{ str($notification->body)->limit(70) }}</span><span>{{ $notification->created_at->diffForHumans() }}</span></div></div>@unless($notification->is_read)<span class="badge badge-primary">Mới</span>@endunless</div>
          @empty
            <div class="empty-block"><p>Chưa có thông báo mới.</p></div>
          @endforelse
        </div>
      </div>
    </aside>
  </div>
</div>
@endsection

