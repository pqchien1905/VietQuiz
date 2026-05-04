{{-- Teacher: profile --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $initials = collect(explode(' ', trim($user->name ?? 'GV')))
    ->filter()
    ->map(fn ($word) => mb_substr($word, 0, 1))
    ->take(2)
    ->implode('') ?: 'GV';

  $avatarUrl = null;
  if (!empty($user->avatar)) {
    $avatarUrl = \Illuminate\Support\Str::startsWith($user->avatar, ['http://', 'https://'])
      ? $user->avatar
      : asset('storage/' . ltrim($user->avatar, '/'));
  }

  $profileIcons = [
    'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'target' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'book' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'flame' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 17c1.38 0 2-.5 2-2 0-1.2-.78-1.78-1.76-2.5C10.1 11.66 9 10.85 9 9c0-1 .5-2 1.5-3 0 2 1.5 3 3 4 1.64 1.09 3.5 2.33 3.5 5a5 5 0 1 1-10 0c0-1.15.4-2.2 1.06-3.03.06.96.2 1.78.44 2.53Z"/></svg>',
    'class' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'quiz' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 10.3c.2-.4.5-.8.9-1a2.1 2.1 0 0 1 2.6.4c.3.4.5.8.5 1.3 0 1.3-2 2-2 2"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'assignment' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>',
  ];
@endphp

@push('styles')
<style>
  .profile-shell { max-width: 1180px; margin: 0 auto; padding: 1.5rem; }
  .profile-hero { border-radius: var(--radius-lg); background: linear-gradient(135deg, #2563eb, #0f766e); color: #fff; padding: 2rem; display: grid; grid-template-columns: auto 1fr auto; gap: 1.5rem; align-items: center; box-shadow: var(--shadow-md); }
  .profile-avatar { width: 5.5rem; height: 5.5rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid rgba(255,255,255,.45); background: rgba(255,255,255,.18); color: #fff; font-size: var(--text-xl); font-weight: 800; }
  .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .profile-title { color: #fff; font-size: var(--text-3xl); line-height: 1.15; margin: 0; }
  .profile-subtitle { color: rgba(255,255,255,.82); font-size: var(--text-sm); margin-top: .35rem; }
  .profile-hero-stats { display: flex; gap: 1.25rem; flex-wrap: wrap; margin-top: 1rem; }
  .profile-hero-stat { min-width: 5.25rem; color: rgba(255,255,255,.88); font-size: var(--text-xs); }
  .profile-hero-stat strong { display: block; color: #fff; font-size: var(--text-xl); line-height: 1.1; }
  .profile-actions { display: flex; flex-direction: column; gap: .625rem; min-width: 10rem; }
  .profile-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(320px, .8fr); gap: 1.25rem; margin-top: 1.25rem; }
  .profile-stack { display: flex; flex-direction: column; gap: 1.25rem; }
  .metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .875rem; margin-top: 1.25rem; }
  .metric { border: 1px solid var(--border); background: var(--card); border-radius: var(--radius-md); padding: 1rem; min-height: 6.25rem; }
  .metric-label { color: var(--muted-foreground); font-size: var(--text-xs); font-weight: 700; text-transform: uppercase; }
  .metric-value { font-size: var(--text-2xl); font-weight: 800; margin-top: .5rem; }
  .metric-note { color: var(--muted-foreground); font-size: var(--text-xs); margin-top: .25rem; }
  .achievement-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem; }
  .achievement-badge { display: flex; gap: .75rem; align-items: flex-start; padding: .875rem; border-radius: var(--radius-md); background: var(--muted); border: 1px solid var(--border); }
  .achievement-badge.inactive { opacity: .62; }
  .achievement-icon { width: 2.25rem; height: 2.25rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex: 0 0 auto; color: var(--primary); background: color-mix(in srgb, var(--primary) 12%, transparent); }
  .profile-list { display: flex; flex-direction: column; gap: .75rem; }
  .profile-row { display: flex; align-items: center; justify-content: space-between; gap: .875rem; padding: .875rem; border: 1px solid var(--border); border-radius: var(--radius-md); color: inherit; text-decoration: none; }
  .profile-row:hover { border-color: var(--primary); box-shadow: var(--shadow-sm); }
  .row-main { min-width: 0; }
  .row-title { font-size: var(--text-sm); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .row-meta { color: var(--muted-foreground); font-size: var(--text-xs); margin-top: .2rem; }
  .row-icon { width: 2.25rem; height: 2.25rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex: 0 0 auto; background: color-mix(in srgb, var(--primary) 10%, transparent); color: var(--primary); }
  .contact-item { display: flex; align-items: center; gap: .75rem; padding: .75rem 0; border-top: 1px solid var(--border); }
  .contact-item:first-child { border-top: 0; padding-top: 0; }
  .activity-grid { display: grid; grid-template-columns: repeat(14, .875rem); gap: .25rem; }
  .activity-day { width: .875rem; height: .875rem; border-radius: 3px; background: var(--muted); }
  .activity-day.level-1 { background: color-mix(in srgb, #16a34a 30%, transparent); }
  .activity-day.level-2 { background: color-mix(in srgb, #16a34a 55%, transparent); }
  .activity-day.level-3 { background: color-mix(in srgb, #16a34a 78%, transparent); }
  .activity-day.level-4 { background: #16a34a; }
  .quick-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
  .empty-state { color: var(--muted-foreground); font-size: var(--text-sm); padding: 1rem; border: 1px dashed var(--border); border-radius: var(--radius-md); text-align: center; }
  @media (max-width: 980px) {
    .profile-hero { grid-template-columns: auto 1fr; }
    .profile-actions { grid-column: 1 / -1; flex-direction: row; flex-wrap: wrap; }
    .profile-grid { grid-template-columns: 1fr; }
    .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 640px) {
    .profile-shell { padding: 1rem; }
    .profile-hero { grid-template-columns: 1fr; text-align: left; padding: 1.25rem; }
    .profile-avatar { width: 4.5rem; height: 4.5rem; }
    .metric-grid, .quick-actions { grid-template-columns: 1fr; }
    .activity-grid { grid-template-columns: repeat(12, .875rem); }
  }
</style>
@endpush

@section('content')
  <div class="profile-shell">
    <section class="profile-hero">
      <div class="profile-avatar" aria-label="Ảnh đại diện">
        @if($avatarUrl)
          <img src="{{ $avatarUrl }}" alt="{{ $user->name }}">
        @else
          {{ $initials }}
        @endif
      </div>

      <div>
        <h1 class="profile-title">{{ $user->name }}</h1>
        <div class="profile-subtitle">
          Giáo viên{{ $user->subject ? ' • ' . $user->subject : '' }} • Tham gia từ {{ $memberSince }}
        </div>
        <div class="profile-hero-stats">
          <div class="profile-hero-stat"><strong>{{ number_format($studentCount) }}</strong>Học sinh</div>
          <div class="profile-hero-stat"><strong>{{ number_format($classCount) }}</strong>Lớp học</div>
          <div class="profile-hero-stat"><strong>{{ number_format($quizCount) }}</strong>Đề kiểm tra</div>
          <div class="profile-hero-stat"><strong>{{ $avgScore !== null ? $avgScore : '—' }}</strong>Điểm TB</div>
        </div>
      </div>

      <div class="profile-actions">
        <a href="{{ route('teacher.settings') }}" class="btn btn-secondary">Chỉnh sửa hồ sơ</a>
        <a href="{{ route('teacher.analytics') }}" class="btn btn-primary">Xem phân tích</a>
      </div>
    </section>

    <div class="metric-grid">
      <div class="metric">
        <div class="metric-label">Khóa học</div>
        <div class="metric-value">{{ number_format($courseCount) }}</div>
        <div class="metric-note">Nội dung đang quản lý</div>
      </div>
      <div class="metric">
        <div class="metric-label">Đề đã xuất bản</div>
        <div class="metric-value">{{ number_format($publishedQuizCount) }}</div>
        <div class="metric-note">Trong tổng {{ number_format($quizCount) }} đề</div>
      </div>
      <div class="metric">
        <div class="metric-label">Bài tập</div>
        <div class="metric-value">{{ number_format($assignmentCount) }}</div>
        <div class="metric-note">{{ number_format($submissionCount) }} bài nộp</div>
      </div>
      <div class="metric">
        <div class="metric-label">Lượt làm bài</div>
        <div class="metric-value">{{ number_format($quizAttemptCount) }}</div>
        <div class="metric-note">{{ number_format($questionCount) }} câu hỏi đã tạo</div>
      </div>
    </div>

    <div class="profile-grid">
      <div class="profile-stack">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Thành tích</h3>
            <p class="card-description">Các mốc được tính theo dữ liệu hiện tại của tài khoản</p>
          </div>
          <div class="card-content">
            <div class="achievement-grid">
              @foreach($achievements as $achievement)
                <div class="achievement-badge {{ $achievement['active'] ? '' : 'inactive' }}">
                  <div class="achievement-icon">{!! $profileIcons[$achievement['icon']] ?? '' !!}</div>
                  <div>
                    <div style="font-size:var(--text-sm);font-weight:800;">{{ $achievement['value'] }}</div>
                    <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.2rem;">{{ $achievement['label'] }}</div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Lớp học gần đây</h3>
            <p class="card-description">Tổng quan nhanh các lớp đang được quản lý</p>
          </div>
          <div class="card-content">
            @if($topClasses->isEmpty())
              <div class="empty-state">Chưa có lớp học. Tạo lớp đầu tiên để bắt đầu quản lý học sinh.</div>
            @else
              <div class="profile-list">
                @foreach($topClasses as $class)
                  <a href="{{ route('teacher.class-detail', $class) }}" class="profile-row">
                    <div class="row-icon">{!! $profileIcons['class'] !!}</div>
                    <div class="row-main">
                      <div class="row-title">{{ $class->name }}</div>
                      <div class="row-meta">{{ $class->students_count }} học sinh • {{ $class->quizzes_count }} đề • {{ $class->assignments_count }} bài tập</div>
                    </div>
                    <span class="badge badge-outline">{{ $class->status === 'archived' ? 'Lưu trữ' : 'Đang hoạt động' }}</span>
                  </a>
                @endforeach
              </div>
            @endif
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Hoạt động gần đây</h3>
            <p class="card-description">Lớp, đề kiểm tra và bài tập mới nhất</p>
          </div>
          <div class="card-content">
            @if($recentActivities->isEmpty())
              <div class="empty-state">Chưa có hoạt động nào để hiển thị.</div>
            @else
              <div class="profile-list">
                @foreach($recentActivities as $activity)
                  <a href="{{ $activity->url }}" class="profile-row">
                    <div class="row-icon">{!! $profileIcons[$activity->type] ?? $profileIcons['class'] !!}</div>
                    <div class="row-main">
                      <div class="row-title">{{ $activity->title }}</div>
                      <div class="row-meta">{{ $activity->meta }} • {{ $activity->created_at?->diffForHumans() }}</div>
                    </div>
                  </a>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>

      <aside class="profile-stack">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Thao tác nhanh</h3>
          </div>
          <div class="card-content">
            <div class="quick-actions">
              <a href="{{ route('teacher.classes') }}" class="btn btn-secondary">Tạo lớp</a>
              <a href="{{ route('teacher.quiz-create') }}" class="btn btn-secondary">Tạo đề</a>
              <a href="{{ route('teacher.assignments') }}" class="btn btn-secondary">Giao bài</a>
              <a href="{{ route('teacher.students') }}" class="btn btn-secondary">Học sinh</a>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Thông tin liên hệ</h3>
          </div>
          <div class="card-content">
            <div class="contact-item">
              <div class="row-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
              </div>
              <div>
                <div class="row-meta">Email</div>
                <a href="mailto:{{ $user->email }}" style="font-size:var(--text-sm);color:inherit;">{{ $user->email }}</a>
              </div>
            </div>
            <div class="contact-item">
              <div class="row-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.08 5.18 2 2 0 0 1 5.06 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.63 2.63a2 2 0 0 1-.45 2.11L9 10.91a16 16 0 0 0 4.09 4.09l1.45-1.24a2 2 0 0 1 2.11-.45c.85.3 1.73.51 2.63.63A2 2 0 0 1 22 16.92z"/></svg>
              </div>
              <div>
                <div class="row-meta">Điện thoại</div>
                @if($user->phone)
                  <a href="tel:{{ $user->phone }}" style="font-size:var(--text-sm);color:inherit;">{{ $user->phone }}</a>
                @else
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Chưa cập nhật</div>
                @endif
              </div>
            </div>
            <div class="contact-item">
              <div class="row-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
              </div>
              <div>
                <div class="row-meta">Chuyên môn</div>
                <div style="font-size:var(--text-sm);">{{ $user->subject ?: 'Chưa cập nhật' }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Hoạt động 12 tuần qua</h3>
          </div>
          <div class="card-content">
            <div class="activity-grid" aria-label="Hoạt động 12 tuần qua">
              @foreach($activityHeatmap as $day)
                <div class="activity-day level-{{ $day['level'] }}" title="{{ $day['date']->format('d/m/Y') }}: {{ $day['count'] }} hoạt động"></div>
              @endforeach
            </div>
            <div style="display:flex;align-items:center;gap:.45rem;margin-top:.875rem;font-size:var(--text-xs);color:var(--muted-foreground);">
              <span>Ít hơn</span>
              <span class="activity-day"></span>
              <span class="activity-day level-1"></span>
              <span class="activity-day level-2"></span>
              <span class="activity-day level-3"></span>
              <span class="activity-day level-4"></span>
              <span>Nhiều hơn</span>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Đề kiểm tra mới</h3>
          </div>
          <div class="card-content">
            @if($recentQuizzes->isEmpty())
              <div class="empty-state">Chưa có đề kiểm tra.</div>
            @else
              <div class="profile-list">
                @foreach($recentQuizzes as $quiz)
                  <a href="{{ route('teacher.quiz-detail', $quiz) }}" class="profile-row">
                    <div class="row-main">
                      <div class="row-title">{{ $quiz->title }}</div>
                      <div class="row-meta">{{ $quiz->questions_count }} câu hỏi • {{ $quiz->created_at?->format('d/m/Y') }}</div>
                    </div>
                    <span class="badge {{ $quiz->status === 'published' ? 'badge-default' : 'badge-outline' }}">{{ $quiz->status === 'published' ? 'Đã xuất bản' : 'Bản nháp' }}</span>
                  </a>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </aside>
    </div>
  </div>
@endsection
