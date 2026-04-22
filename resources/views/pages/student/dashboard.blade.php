{{-- Student Dashboard --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .page-header { margin-bottom: 1.5rem; }
</style>
@endpush

@section('content')
  <!-- Page Header -->
  <div class="page-header stagger-children" id="page-header">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Chào mừng trở lại, {{ $user->name }}! 👋</h1>
        <p style="color: var(--muted-foreground); margin-top: 0.25rem;">Đây là tổng quan của bạn.</p>
      </div>
      <div style="display:flex;gap:0.5rem;">
        <a href="{{ route('student.join-class') }}" class="btn btn-outline gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
          Tham gia Lớp
        </a>
        <a href="{{ route('student.quizzes') }}" class="btn btn-primary gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
          Xem Bài kiểm tra
        </a>
      </div>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="stats-grid stats-grid-4 stagger-children">
    <div class="stat-card">
      <div class="flex items-center justify-between" style="margin-bottom: 0.75rem;">
        <span style="font-size: var(--text-sm); font-weight: 500; color: var(--muted-foreground);">Khóa học Đã đăng ký</span>
        <div class="stat-card__icon" style="background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--primary);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $courseCount }}</div>
      <div class="stat-card__label">đang học kỳ này</div>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between" style="margin-bottom: 0.75rem;">
        <span style="font-size: var(--text-sm); font-weight: 500; color: var(--muted-foreground);">Bài kiểm tra Cần làm</span>
        <div class="stat-card__icon" style="background: color-mix(in srgb, var(--destructive) 12%, transparent); color: var(--destructive);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
      </div>
      <div class="stat-card__value" style="color:var(--destructive);">{{ $pendingQuizCount }}</div>
      <div class="stat-card__label">đến hạn tuần này</div>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between" style="margin-bottom: 0.75rem;">
        <span style="font-size: var(--text-sm); font-weight: 500; color: var(--muted-foreground);">Lớp Tham gia</span>
        <div class="stat-card__icon" style="background: color-mix(in srgb, var(--warning) 12%, transparent); color: var(--warning);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $classCount }}</div>
      <div class="stat-card__label">lớp học</div>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between" style="margin-bottom: 0.75rem;">
        <span style="font-size: var(--text-sm); font-weight: 500; color: var(--muted-foreground);">Điểm Trung bình</span>
        <div class="stat-card__icon" style="background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
        </div>
      </div>
      <div class="stat-card__value" style="color:var(--success);">{{ $avgGrade ? number_format($avgGrade, 1) . '%' : 'N/A' }}</div>
      <div class="stat-card__label">điểm trung bình</div>
    </div>
  </div>

  <!-- Main Grid -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    <!-- Upcoming -->
    <div class="card">
      <div class="card-header">
        <div class="flex items-center justify-between">
          <h3 class="card-title">Nhiệm vụ Sắp tới</h3>
          <a href="{{ route('student.assignments') }}" class="btn btn-ghost btn-sm" style="color:var(--primary);">Xem tất cả</a>
        </div>
        <p class="card-description">Bài tập và bài kiểm tra đang chờ</p>
      </div>
      <div class="card-content">
        @forelse($dueSoonAssignments as $assignment)
        <div class="task-item">
          <div class="task-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:var(--text-sm);">{{ $assignment->title }}</div>
            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Hạn: {{ $assignment->due_at->format('d/m/Y H:i') }}</div>
          </div>
          @if($assignment->due_at->diffInDays(now()) <= 1)
          <span class="badge badge-destructive">Gấp</span>
          @else
          <span class="badge badge-warning">{{ $assignment->due_at->diffInDays(now()) }} ngày</span>
          @endif
        </div>
        @empty
        <div style="text-align:center;padding:2rem;color:var(--muted-foreground);">
          <p>🎉 Không có bài tập nào sắp tới!</p>
        </div>
        @endforelse
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
      <div class="card-header">
        <div class="flex items-center justify-between">
          <h3 class="card-title">Hoạt động Gần đây</h3>
          <a href="{{ route('student.grades') }}" class="btn btn-ghost btn-sm" style="color:var(--primary);">Xem tất cả</a>
        </div>
        <p class="card-description">Các bài nộp và hoàn thành gần đây của bạn</p>
      </div>
      <div class="card-content">
        @forelse($recentAttempts as $attempt)
        <div class="activity-item">
          <div style="width:2.25rem;height:2.25rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--success) 12%,transparent);display:flex;align-items:center;justify-content:center;color:var(--success);flex-shrink:0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:var(--text-sm);font-weight:500;">Hoàn thành bài kiểm tra</div>
            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">"{{ $attempt->title }}"</div>
          </div>
          <div style="text-align:right;">
            @if($attempt->pivot->is_graded)
            <div style="font-size:var(--text-sm);font-weight:600;">{{ $attempt->pivot->score }}/{{ $attempt->pivot->total_points }}</div>
            @else
            <span class="badge badge-warning" style="font-size:var(--text-xs);">Chờ chấm</span>
            @endif
            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $attempt->pivot->submitted_at ? \Carbon\Carbon::parse($attempt->pivot->submitted_at)->diffForHumans() : '' }}</div>
          </div>
        </div>
        @empty
        <div style="text-align:center;padding:2rem;color:var(--muted-foreground);">
          <p>Chưa có hoạt động nào. Bắt đầu làm bài kiểm tra!</p>
        </div>
        @endforelse
      </div>
    </div>
  </div>
@endsection
