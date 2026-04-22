{{-- Teacher Dashboard --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

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
        <h1>Chào mừng trở lại, {{ $user->name }} 👋</h1>
        <p style="color: var(--muted-foreground); margin-top: 0.25rem;">Đây là tổng quan về các hoạt động giảng dạy của bạn.</p>
      </div>
      <a href="{{ route('teacher.quiz-create') }}" class="btn btn-primary gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tạo Bài thi
      </a>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="stats-grid stats-grid-4 stagger-children" id="stats-grid">
    <div class="stat-card">
      <div class="flex items-center justify-between" style="margin-bottom: 0.75rem;">
        <span style="font-size: var(--text-sm); font-weight: 500; color: var(--muted-foreground);">Tổng số Lớp</span>
        <div class="stat-card__icon" style="background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--primary);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $classCount }}</div>
      <div class="stat-card__label">lớp học</div>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between" style="margin-bottom: 0.75rem;">
        <span style="font-size: var(--text-sm); font-weight: 500; color: var(--muted-foreground);">Tổng số Bài thi</span>
        <div class="stat-card__icon" style="background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $quizCount }}</div>
      <div class="stat-card__label">bài kiểm tra</div>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between" style="margin-bottom: 0.75rem;">
        <span style="font-size: var(--text-sm); font-weight: 500; color: var(--muted-foreground);">Tổng Học sinh</span>
        <div class="stat-card__icon" style="background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $studentCount }}</div>
      <div class="stat-card__label">học sinh</div>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between" style="margin-bottom: 0.75rem;">
        <span style="font-size: var(--text-sm); font-weight: 500; color: var(--muted-foreground);">Câu hỏi</span>
        <div class="stat-card__icon" style="background: color-mix(in srgb, var(--info) 12%, transparent); color: var(--info);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/></svg>
        </div>
      </div>
      <div class="stat-card__value">{{ $questionCount }}</div>
      <div class="stat-card__label">ngân hàng câu hỏi</div>
    </div>
  </div>

  <!-- Main Grid -->
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
    <!-- Upcoming Assignments -->
    <div class="stagger-children">
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="card-title">Nhiệm vụ Sắp tới</h3>
              <p class="card-description">Bài tập và bài kiểm tra sắp đến hạn</p>
            </div>
            <a href="{{ route('teacher.assignments') }}" class="btn btn-ghost btn-sm" style="color:var(--primary);">Xem tất cả</a>
          </div>
        </div>
        <div class="card-content">
          @forelse($recentAssignments as $assignment)
          <div class="task-item">
            <div class="task-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:var(--text-sm);">{{ $assignment->title }}</div>
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Hạn: {{ $assignment->due_at->format('d/m/Y') }}</div>
            </div>
            @if($assignment->due_at->diffInDays(now()) <= 2)
            <span class="badge badge-destructive">Gấp</span>
            @else
            <span class="badge badge-warning">{{ $assignment->due_at->diffInDays(now()) }} ngày</span>
            @endif
          </div>
          @empty
          <div style="text-align:center;padding:2rem;color:var(--muted-foreground);">
            <p>Chưa có nhiệm vụ nào sắp tới.</p>
          </div>
          @endforelse
        </div>
      </div>

      <!-- Weekly Activity -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Xu hướng Hoạt động Hàng tuần</h3>
          <p class="card-description">Số bài kiểm tra được làm trong 7 ngày qua</p>
        </div>
        <div class="card-content">
          <div style="display:flex;align-items:flex-end;justify-content:space-between;height:140px;gap:0.5rem;padding:0.5rem 0;">
            @foreach($weekDays as $i => $day)
            @php $maxVal = max(1, max($activityData)); $pct = ($activityData[$i] / $maxVal) * 100; @endphp
            <div class="activity-bar">
              <div class="activity-bar-inner" style="height:{{ max(5, $pct) }}%;"></div>
              <span class="activity-bar-label">{{ $day }}</span>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="stagger-children">
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header"><h3 class="card-title">Truy cập Nhanh</h3></div>
        <div class="card-content" style="display:flex;flex-direction:column;gap:0.75rem;">
          <a href="{{ route('teacher.quiz-create') }}" class="quick-action">
            <div class="quick-action-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
            <div><div style="font-weight:600;font-size:var(--text-sm);">Tạo Bài kiểm tra</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Tạo đề thi mới</div></div>
          </a>
          <a href="{{ route('teacher.classes') }}" class="quick-action">
            <div class="quick-action-icon" style="background:color-mix(in srgb,var(--success) 12%,transparent);color:var(--success);">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div><div style="font-weight:600;font-size:var(--text-sm);">Quản lý Lớp học</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Xem và quản lý lớp</div></div>
          </a>
          <a href="{{ route('teacher.grading') }}" class="quick-action">
            <div class="quick-action-icon" style="background:color-mix(in srgb,var(--warning) 12%,transparent);color:var(--warning);">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <div><div style="font-weight:600;font-size:var(--text-sm);">Chấm điểm</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $ungradedCount }} bài chờ chấm</div></div>
          </a>
          <a href="{{ route('teacher.analytics') }}" class="quick-action">
            <div class="quick-action-icon" style="background:color-mix(in srgb,var(--info) 12%,transparent);color:var(--info);">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </div>
            <div><div style="font-weight:600;font-size:var(--text-sm);">Xem Phân tích</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Thống kê chi tiết</div></div>
          </a>
        </div>
      </div>

      <!-- Recent Notifications -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Thông báo Gần đây</h3>
        </div>
        <div class="card-content">
          @forelse($recentNotifications as $notification)
          <div class="activity-item">
            <div style="width:2rem;height:2rem;border-radius:50%;background:color-mix(in srgb,var(--info) 12%,transparent);display:flex;align-items:center;justify-content:center;color:var(--info);flex-shrink:0;">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:var(--text-sm);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $notification->title }}</div>
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $notification->created_at->diffForHumans() }}</div>
            </div>
          </div>
          @empty
          <p style="color:var(--muted-foreground);font-size:var(--text-sm);text-align:center;">Chưa có thông báo.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
@endsection
