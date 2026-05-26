{{-- Teacher Dashboard --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $maxActivity = max(1, max($activityData ?? [0]));
@endphp

@push('styles')
<style>
  .teacher-dashboard { display:flex; flex-direction:column; gap:1.5rem; }
  .dashboard-hero { border:1px solid var(--border); border-radius:var(--radius-xl); background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 10%,var(--card)),var(--card)); padding:1.5rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
  .dashboard-hero h1 { margin:0; font-size:clamp(1.5rem,2vw,2.25rem); line-height:1.2; font-weight:800; }
  .dashboard-hero p { margin:.5rem 0 0; color:var(--muted-foreground); max-width:56rem; }
  .hero-actions { display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; justify-content:flex-end; }
  .stat-card__top { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.75rem; }
  .stat-card__hint { display:flex; align-items:center; gap:.375rem; color:var(--muted-foreground); font-size:var(--text-xs); margin-top:.625rem; }
  .dashboard-grid { display:grid; grid-template-columns:minmax(0,2fr) minmax(320px,1fr); gap:1.5rem; align-items:start; }
  .dashboard-stack { display:flex; flex-direction:column; gap:1.5rem; min-width:0; }
  .section-title-row { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
  .section-title-row h3 { margin:0; }
  .list-row { display:flex; align-items:flex-start; gap:.875rem; padding:.875rem 0; border-top:1px solid var(--border); }
  .list-row:first-child { border-top:0; padding-top:0; }
  .row-icon { width:2.4rem; height:2.4rem; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .row-main { min-width:0; flex:1; }
  .row-title { font-weight:700; font-size:var(--text-sm); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .row-meta { margin-top:.25rem; display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; color:var(--muted-foreground); font-size:var(--text-xs); }
  .row-actions { display:flex; align-items:center; gap:.5rem; flex-shrink:0; }
  .quick-action-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
  .quick-action { min-height:5rem; }
  .class-strip { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.875rem; }
  .mini-card { border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); padding:1rem; min-width:0; }
  .mini-card:hover { border-color:color-mix(in srgb,var(--primary) 28%,var(--border)); box-shadow:var(--shadow-md); }
  .mini-card__title { font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .mini-card__meta { margin-top:.625rem; display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; color:var(--muted-foreground); font-size:var(--text-xs); }
  .mini-card__meta strong { display:block; color:var(--foreground); font-size:var(--text-base); }
  .activity-chart { display:flex; align-items:flex-end; justify-content:space-between; height:150px; gap:.5rem; padding:.5rem 0 0; }
  .activity-bar { display:flex; flex-direction:column; align-items:center; gap:.375rem; flex:1; height:100%; }
  .activity-bar-track { width:100%; max-width:2rem; flex:1; display:flex; align-items:flex-end; }
  .activity-bar-inner { width:100%; border-radius:6px 6px 0 0; background:linear-gradient(to top,var(--primary),color-mix(in srgb,var(--primary) 62%,var(--info))); min-height:.375rem; }
  .activity-bar-label { font-size:.7rem; color:var(--muted-foreground); }
  .progress-line { height:.5rem; border-radius:999px; background:var(--muted); overflow:hidden; margin-top:.75rem; }
  .progress-line span { display:block; height:100%; border-radius:999px; background:var(--primary); }
  .empty-state { text-align:center; padding:2rem 1rem; color:var(--muted-foreground); }
  .modal-backdrop { display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; background:rgba(15,23,42,.55); padding:1rem; }
  .modal-backdrop.open { display:flex; }
  .dashboard-modal { width:min(560px,100%); max-height:90vh; overflow:auto; background:var(--card); border:1px solid var(--border); border-radius:var(--radius-xl); box-shadow:var(--shadow-lg); }
  .dashboard-modal__header { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border); display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
  .dashboard-modal__body { padding:1.5rem; display:flex; flex-direction:column; gap:1rem; }
  .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
  .field { display:flex; flex-direction:column; gap:.375rem; }
  .field label { font-size:var(--text-sm); font-weight:600; }
  @media (max-width:1100px) {
    .dashboard-grid { grid-template-columns:1fr; }
    .class-strip { grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
  @media (max-width:720px) {
    .dashboard-hero { align-items:stretch; flex-direction:column; }
    .hero-actions { justify-content:flex-start; }
    .quick-action-grid, .class-strip, .form-grid { grid-template-columns:1fr; }
    .row-actions { display:none; }
  }
</style>
@endpush

@section('content')
<div class="teacher-dashboard">
  <section class="dashboard-hero stagger-children">
    <div>
      <h1>Chào mừng trở lại, {{ $user->name }}</h1>
      <p>Theo dõi lớp học, bài kiểm tra, bài tập và các bài nộp cần chấm trong một màn hình.</p>
    </div>
    <div class="hero-actions">
      <button class="btn btn-outline gap-2" type="button" data-open-modal="create-class-modal">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tạo lớp
      </button>
      <button class="btn btn-secondary gap-2" type="button" data-open-modal="create-assignment-modal">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
        Giao bài tập
      </button>
      <a href="{{ route('teacher.quiz-create') }}" class="btn btn-primary gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        Tạo bài thi
      </a>
    </div>
  </section>

  @if(session('success'))
    <div class="alert alert-success"><span>{{ session('success') }}</span></div>
  @endif
  @if(session('error') || (isset($errors) && $errors->any()))
    <div class="alert alert-danger"><span>{{ session('error') ?? $errors->first() }}</span></div>
  @endif

  <section class="stats-grid stats-grid-4 stagger-children">
    <div class="stat-card">
      <div class="stat-card__top">
        <span style="font-size:var(--text-sm);font-weight:600;color:var(--muted-foreground);">Lớp đang quản lý</span>
        <div class="stat-card__icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $classCount }}</div>
      <div class="stat-card__label">{{ $studentCount }} học sinh duy nhất</div>
    </div>

    <div class="stat-card">
      <div class="stat-card__top">
        <span style="font-size:var(--text-sm);font-weight:600;color:var(--muted-foreground);">Bài thi</span>
        <div class="stat-card__icon" style="background:color-mix(in srgb,var(--info) 12%,transparent);color:var(--info);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $quizCount }}</div>
      <div class="stat-card__label">{{ $publishedQuizCount }} đã xuất bản, {{ $draftQuizCount }} nháp</div>
    </div>

    <div class="stat-card">
      <div class="stat-card__top">
        <span style="font-size:var(--text-sm);font-weight:600;color:var(--muted-foreground);">Chờ chấm</span>
        <div class="stat-card__icon" style="background:color-mix(in srgb,var(--warning) 14%,transparent);color:var(--warning);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $pendingGradingCount }}</div>
      <div class="stat-card__label">{{ $ungradedCount }} bài thi, {{ $ungradedAssignmentCount }} bài tập</div>
    </div>

    <div class="stat-card">
      <div class="stat-card__top">
        <span style="font-size:var(--text-sm);font-weight:600;color:var(--muted-foreground);">Hiệu suất</span>
        <div class="stat-card__icon" style="background:color-mix(in srgb,var(--success) 12%,transparent);color:var(--success);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $averageScore !== null ? $averageScore . '%' : '—' }}</div>
      <div class="stat-card__label">{{ $completionRate !== null ? $completionRate . '% lượt làm đã nộp' : 'Chưa có lượt làm' }}</div>
      @if($completionRate !== null)
        <div class="progress-line"><span style="width:{{ min(100, $completionRate) }}%;"></span></div>
      @endif
    </div>
  </section>

  <section class="card">
    <div class="card-header">
      <div class="section-title-row">
        <div>
          <h3 class="card-title">Lớp học gần đây</h3>
          <p class="card-description">Mở nhanh lớp, giao bài hoặc xem số lượng học sinh.</p>
        </div>
        <a href="{{ route('teacher.classes') }}" class="btn btn-ghost btn-sm">Xem tất cả</a>
      </div>
    </div>
    <div class="card-content">
      @if($recentClasses->isNotEmpty())
        <div class="class-strip">
          @foreach($recentClasses as $class)
            <a class="mini-card" href="{{ route('teacher.class-detail', $class) }}" style="text-decoration:none;color:inherit;">
              <div class="mini-card__title">{{ $class->name }}</div>
              <div class="row-meta">
                <span class="badge {{ ($class->status ?? 'active') === 'active' ? 'badge-success' : 'badge-outline' }}">{{ ($class->status ?? 'active') === 'active' ? 'Hoạt động' : 'Lưu trữ' }}</span>
                <span>Mã {{ $class->code }}</span>
              </div>
              <div class="mini-card__meta">
                <span><strong>{{ $class->students_count }}</strong>Học sinh</span>
                <span><strong>{{ $class->quizzes_count }}</strong>Bài thi</span>
                <span><strong>{{ $class->assignments_count }}</strong>Bài tập</span>
              </div>
            </a>
          @endforeach
        </div>
      @else
        <div class="empty-state">Bạn chưa có lớp học nào. Hãy tạo lớp đầu tiên để mời học sinh tham gia.</div>
      @endif
    </div>
  </section>

  <div class="dashboard-grid">
    <div class="dashboard-stack">
      <section class="card">
        <div class="card-header">
          <div class="section-title-row">
            <div>
              <h3 class="card-title">Việc cần xử lý</h3>
              <p class="card-description">Bài tập sắp đến hạn, bài thi đang mở và bài nộp mới nhất.</p>
            </div>
            <a href="{{ route('teacher.assignments') }}" class="btn btn-outline btn-sm">Vào bài tập</a>
          </div>
        </div>
        <div class="card-content">
          @forelse($recentSubmissions as $submission)
            @php $grade = $submission->grades->first(); @endphp
            <div class="list-row">
              <div class="row-icon" style="background:color-mix(in srgb,var(--warning) 14%,transparent);color:var(--warning);">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              </div>
              <div class="row-main">
                <div class="row-title">{{ $submission->assignment?->title ?? 'Bài tập' }}</div>
                <div class="row-meta">
                  <span>{{ $submission->student?->name ?? 'Học sinh' }}</span>
                  <span>{{ optional($submission->submitted_at)->diffForHumans() }}</span>
                  <span class="badge {{ $grade ? 'badge-success' : 'badge-warning' }}">{{ $grade ? 'Đã chấm' : 'Chờ chấm' }}</span>
                </div>
              </div>
              <div class="row-actions">
                <a href="{{ route('teacher.assignments') }}" class="btn btn-ghost btn-sm">Mở</a>
              </div>
            </div>
          @empty
            <div class="empty-state">Chưa có bài nộp mới.</div>
          @endforelse
        </div>
      </section>

      <section class="card">
        <div class="card-header">
          <div class="section-title-row">
            <div>
              <h3 class="card-title">Bài kiểm tra gần đây</h3>
              <p class="card-description">Theo dõi trạng thái xuất bản, câu hỏi và lượt làm.</p>
            </div>
            <a href="{{ route('teacher.quizzes') }}" class="btn btn-ghost btn-sm">Quản lý bài thi</a>
          </div>
        </div>
        <div class="card-content">
          @forelse($recentQuizzes as $quiz)
            <div class="list-row">
              <div class="row-icon" style="background:color-mix(in srgb,var(--info) 12%,transparent);color:var(--info);">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
              </div>
              <div class="row-main">
                <div class="row-title">{{ $quiz->title }}</div>
                <div class="row-meta">
                  <span class="badge {{ $quiz->status === 'published' ? 'badge-success' : 'badge-outline' }}">{{ $quiz->status === 'published' ? 'Đã xuất bản' : 'Nháp' }}</span>
                  <span>{{ $quiz->questions_count }} câu hỏi</span>
                  <span>{{ $quiz->attempts_count }} lượt làm</span>
                  @if($quiz->classModel)<span>{{ $quiz->classModel->name }}</span>@endif
                </div>
              </div>
              <div class="row-actions">
                <a href="{{ route('teacher.quiz-detail', $quiz) }}" class="btn btn-outline btn-sm">Chi tiết</a>
              </div>
            </div>
          @empty
            <div class="empty-state">Chưa có bài kiểm tra. Tạo bài thi để bắt đầu giao cho lớp.</div>
          @endforelse
        </div>
      </section>

      <section class="card">
        <div class="card-header">
          <h3 class="card-title">Hoạt động 7 ngày qua</h3>
          <p class="card-description">Số lượt học sinh bắt đầu làm bài theo từng ngày.</p>
        </div>
        <div class="card-content">
          <div class="activity-chart">
            @foreach($weekDays as $i => $day)
              @php $pct = (($activityData[$i] ?? 0) / $maxActivity) * 100; @endphp
              <div class="activity-bar" title="{{ $activityData[$i] ?? 0 }} lượt">
                <div class="activity-bar-track"><div class="activity-bar-inner" style="height:{{ max(4, $pct) }}%;"></div></div>
                <span class="activity-bar-label">{{ $day }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </section>
    </div>

    <aside class="dashboard-stack">
      <section class="card">
        <div class="card-header"><h3 class="card-title">Truy cập nhanh</h3></div>
        <div class="card-content">
          <div class="quick-action-grid">
            <a href="{{ route('teacher.quiz-create') }}" class="quick-action">
              <div class="quick-action-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </div>
              <div><div style="font-weight:700;font-size:var(--text-sm);">Tạo bài thi</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Soạn đề mới</div></div>
            </a>
            <a href="{{ route('teacher.questions') }}" class="quick-action">
              <div class="quick-action-icon" style="background:color-mix(in srgb,var(--info) 12%,transparent);color:var(--info);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/></svg>
              </div>
              <div><div style="font-weight:700;font-size:var(--text-sm);">Ngân hàng câu hỏi</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $questionCount }} câu hỏi</div></div>
            </a>
            <a href="{{ route('teacher.students') }}" class="quick-action">
              <div class="quick-action-icon" style="background:color-mix(in srgb,var(--success) 12%,transparent);color:var(--success);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              </div>
              <div><div style="font-weight:700;font-size:var(--text-sm);">Học sinh</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $studentCount }} tài khoản</div></div>
            </a>
            <a href="{{ route('teacher.analytics') }}" class="quick-action">
              <div class="quick-action-icon" style="background:color-mix(in srgb,var(--warning) 12%,transparent);color:var(--warning);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              </div>
              <div><div style="font-weight:700;font-size:var(--text-sm);">Phân tích</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Báo cáo lớp học</div></div>
            </a>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="card-header">
          <div class="section-title-row">
            <h3 class="card-title">Sắp đến hạn</h3>
            <a href="{{ route('teacher.assignments') }}" class="btn btn-ghost btn-sm">Bài tập</a>
          </div>
        </div>
        <div class="card-content">
          @forelse($recentAssignments as $assignment)
            @php $daysLeft = $assignment->due_at ? now()->startOfDay()->diffInDays($assignment->due_at->copy()->startOfDay(), false) : null; @endphp
            <div class="list-row">
              <div class="row-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
              </div>
              <div class="row-main">
                <div class="row-title">{{ $assignment->title }}</div>
                <div class="row-meta">
                  <span>{{ optional($assignment->due_at)->format('d/m/Y H:i') }}</span>
                  <span>{{ $assignment->submissions_count }} bài nộp</span>
                </div>
              </div>
              <span class="badge {{ $daysLeft !== null && $daysLeft <= 2 ? 'badge-danger' : 'badge-warning' }}">{{ $daysLeft !== null ? ($daysLeft <= 0 ? 'Hôm nay' : $daysLeft . ' ngày') : 'Không hạn' }}</span>
            </div>
          @empty
            <div class="empty-state">Không có bài tập nào sắp đến hạn.</div>
          @endforelse
        </div>
      </section>

      <section class="card">
        <div class="card-header"><h3 class="card-title">Thông báo gần đây</h3></div>
        <div class="card-content">
          @forelse($recentNotifications as $notification)
            <div class="list-row">
              <div class="row-icon" style="background:color-mix(in srgb,var(--info) 12%,transparent);color:var(--info);">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
              </div>
              <div class="row-main">
                <div class="row-title">{{ $notification->title }}</div>
                <div class="row-meta"><span>{{ $notification->created_at->diffForHumans() }}</span></div>
              </div>
            </div>
          @empty
            <div class="empty-state">Chưa có thông báo mới.</div>
          @endforelse
        </div>
      </section>
    </aside>
  </div>
</div>

<div class="modal-backdrop" id="create-class-modal">
  <form class="dashboard-modal" method="POST" action="{{ route('teacher.classes.store') }}">
    @csrf
    <div class="dashboard-modal__header">
      <div>
        <h3 class="card-title">Tạo lớp mới</h3>
        <p class="card-description">Sau khi tạo, hệ thống sinh mã lớp để mời học sinh.</p>
      </div>
      <button class="icon-btn" type="button" data-close-modal aria-label="Đóng">×</button>
    </div>
    <div class="dashboard-modal__body">
      <div class="field"><label for="class-name">Tên lớp</label><input id="class-name" class="input" name="name" required maxlength="255" placeholder="Ví dụ: Toán 10A1"></div>
      <div class="form-grid">
        <div class="field"><label for="class-subject">Môn học</label><input id="class-subject" class="input" name="subject" maxlength="255" placeholder="Toán học"></div>
        <div class="field"><label for="class-grade">Khối</label><input id="class-grade" class="input" name="grade_level" maxlength="50" placeholder="10"></div>
      </div>
      <div class="field"><label for="class-description">Mô tả</label><textarea id="class-description" class="input" name="description" rows="4" maxlength="1000" placeholder="Ghi chú ngắn cho lớp học"></textarea></div>
      <div style="display:flex;justify-content:flex-end;gap:.75rem;">
        <button class="btn btn-secondary" type="button" data-close-modal>Hủy</button>
        <button class="btn btn-primary" type="submit">Tạo lớp</button>
      </div>
    </div>
  </form>
</div>

<div class="modal-backdrop" id="create-assignment-modal">
  <form class="dashboard-modal" method="POST" action="{{ route('teacher.assignments.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="dashboard-modal__header">
      <div>
        <h3 class="card-title">Giao bài tập nhanh</h3>
        <p class="card-description">Tạo bài tập cơ bản, có thể chỉnh chi tiết ở trang Bài tập.</p>
      </div>
      <button class="icon-btn" type="button" data-close-modal aria-label="Đóng">×</button>
    </div>
    <div class="dashboard-modal__body">
      <div class="field"><label for="assignment-title">Tiêu đề</label><input id="assignment-title" class="input" name="title" required maxlength="255" placeholder="Ví dụ: Bài tập chương 1"></div>
      <div class="form-grid">
        <div class="field">
          <label for="assignment-class">Lớp</label>
          <select id="assignment-class" class="input select" name="class_id" required>
            <option value="">Chọn lớp</option>
            @foreach($classesForForms as $class)
              <option value="{{ $class->id }}">{{ $class->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label for="assignment-course">Khóa học</label>
          <select id="assignment-course" class="input select" name="course_id">
            <option value="">Không gắn khóa học</option>
            @foreach($coursesForForms as $course)
              <option value="{{ $course->id }}">{{ $course->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div class="field"><label for="assignment-due">Hạn nộp</label><input id="assignment-due" class="input" type="datetime-local" name="due_at" min="{{ now()->format('Y-m-d\TH:i') }}"></div>
        <div class="field"><label for="assignment-points">Điểm tối đa</label><input id="assignment-points" class="input" type="number" name="total_points" min="1" max="10000" value="100"></div>
      </div>
      <input type="hidden" name="type" value="file">
      <div class="field"><label for="assignment-description">Mô tả</label><textarea id="assignment-description" class="input" name="description" rows="4" maxlength="2000" placeholder="Yêu cầu làm bài"></textarea></div>
      <div class="field"><label for="assignment-attachment">Tệp đính kèm</label><input id="assignment-attachment" class="input" type="file" name="attachment"></div>
      <div style="display:flex;justify-content:flex-end;gap:.75rem;">
        <button class="btn btn-secondary" type="button" data-close-modal>Hủy</button>
        <button class="btn btn-primary" type="submit" @disabled($classesForForms->isEmpty())>Giao bài</button>
      </div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-open-modal]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.getElementById(button.dataset.openModal)?.classList.add('open');
    });
  });

  document.querySelectorAll('[data-close-modal]').forEach(function (button) {
    button.addEventListener('click', function () {
      button.closest('.modal-backdrop')?.classList.remove('open');
    });
  });

  document.querySelectorAll('.modal-backdrop').forEach(function (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) modal.classList.remove('open');
    });
  });
</script>
@endpush
