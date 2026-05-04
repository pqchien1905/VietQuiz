{{-- Student: profile --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $avatarUrl = null;
  if (!empty($user->avatar)) {
    $avatarUrl = \Illuminate\Support\Str::startsWith($user->avatar, ['http://', 'https://'])
      ? $user->avatar
      : asset('storage/' . ltrim($user->avatar, '/'));
  }
  $initials = collect(explode(' ', $user->name))->filter()->map(fn($word) => mb_substr($word, 0, 1))->take(2)->implode('') ?: 'HS';
  $iconMap = [
    'class' => '🎓',
    'book' => '📚',
    'target' => '🎯',
    'assignment' => '📎',
    'star' => '⭐',
    'flame' => '🔥',
    'quiz' => '📝',
  ];
@endphp

@push('styles')
<style>
  .profile-shell{max-width:1180px;margin:0 auto;padding:1.5rem}
  .profile-hero{border-radius:var(--radius-lg);background:linear-gradient(135deg,#2563eb,#0f766e);color:#fff;padding:2rem;display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:1.5rem;align-items:center;box-shadow:var(--shadow-md)}
  .profile-avatar{width:5.5rem;height:5.5rem;border-radius:999px;display:flex;align-items:center;justify-content:center;overflow:hidden;border:4px solid rgba(255,255,255,.45);background:rgba(255,255,255,.18);color:#fff;font-size:var(--text-xl);font-weight:800}
  .profile-avatar img{width:100%;height:100%;object-fit:cover}
  .profile-title{color:#fff;font-size:var(--text-3xl);line-height:1.15;margin:0}
  .profile-subtitle{color:rgba(255,255,255,.84);font-size:var(--text-sm);margin-top:.35rem}
  .profile-hero-stats{display:flex;gap:1.25rem;flex-wrap:wrap;margin-top:1rem}
  .profile-hero-stat{min-width:5.25rem;color:rgba(255,255,255,.88);font-size:var(--text-xs)}
  .profile-hero-stat strong{display:block;color:#fff;font-size:var(--text-xl);line-height:1.1}
  .profile-actions{display:flex;flex-direction:column;gap:.625rem;min-width:10rem}
  .profile-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.85fr);gap:1.25rem;margin-top:1.25rem}
  .profile-stack{display:flex;flex-direction:column;gap:1.25rem}
  .achievement-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.75rem}
  .achievement-badge{display:flex;flex-direction:column;align-items:center;gap:.25rem;padding:.95rem;border-radius:var(--radius-md);background:var(--muted);border:1px solid var(--border);text-align:center;min-height:7rem}
  .achievement-badge.inactive{opacity:.55}
  .achievement-badge .icon{font-size:1.65rem}
  .achievement-badge .label{font-size:var(--text-xs);color:var(--muted-foreground)}
  .achievement-badge .value{font-size:var(--text-sm);font-weight:800}
  .activity-grid{display:flex;gap:.25rem;flex-wrap:wrap}
  .activity-day{width:.875rem;height:.875rem;border-radius:2px;background:var(--muted)}
  .activity-day.level-1{background:color-mix(in srgb,var(--success) 30%,transparent)}
  .activity-day.level-2{background:color-mix(in srgb,var(--success) 55%,transparent)}
  .activity-day.level-3{background:color-mix(in srgb,var(--success) 80%,transparent)}
  .activity-day.level-4{background:var(--success)}
  .profile-list{display:flex;flex-direction:column;gap:.75rem}
  .profile-row{display:flex;align-items:center;justify-content:space-between;gap:.875rem;padding:.875rem;border:1px solid var(--border);border-radius:var(--radius-md);color:inherit;text-decoration:none}
  .profile-row:hover{border-color:var(--primary);box-shadow:var(--shadow-sm)}
  .row-left{display:flex;align-items:center;gap:.75rem;min-width:0}
  .row-icon{width:2.4rem;height:2.4rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--primary) 12%,transparent);flex-shrink:0}
  .row-title{font-weight:700;font-size:var(--text-sm);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .row-meta{font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.15rem}
  .info-list{display:flex;flex-direction:column;gap:.85rem}
  .info-row{display:flex;gap:.75rem;align-items:flex-start}
  .info-icon{width:2rem;height:2rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:var(--muted);flex-shrink:0}
  @media (max-width:900px){.profile-hero{grid-template-columns:auto 1fr}.profile-actions{grid-column:1 / -1;flex-direction:row;flex-wrap:wrap}.profile-grid{grid-template-columns:1fr}}
  @media (max-width:640px){.profile-shell{padding:1rem}.profile-hero{grid-template-columns:1fr;padding:1.25rem}.profile-avatar{width:4.5rem;height:4.5rem}}
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
          Học sinh · {{ $classes->first()?->name ?? 'Chưa tham gia lớp' }} · Thành viên từ {{ $memberSince }}
        </div>
        <div class="profile-hero-stats">
          <div class="profile-hero-stat"><strong>{{ number_format($classCount) }}</strong>Lớp học</div>
          <div class="profile-hero-stat"><strong>{{ number_format($courseCount) }}</strong>Khóa học</div>
          <div class="profile-hero-stat"><strong>{{ number_format($quizCount) }}</strong>Quiz đã làm</div>
          <div class="profile-hero-stat"><strong>{{ $avgGrade !== null ? $avgGrade . '%' : '—' }}</strong>Điểm TB</div>
        </div>
      </div>

      <div class="profile-actions">
        <a href="{{ route('student.settings') }}" class="btn btn-primary">Chỉnh sửa hồ sơ</a>
        <a href="{{ route('student.grades') }}" class="btn btn-outline" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.35);">Xem điểm số</a>
      </div>
    </section>

    <div class="profile-grid">
      <div class="profile-stack">
        <section class="card">
          <div class="card-header">
            <h3 class="card-title">Thành tích học tập</h3>
            <p class="card-description">Các mốc được tính từ lớp, khóa học, bài nộp và điểm số thật.</p>
          </div>
          <div class="card-content">
            <div class="achievement-grid">
              @foreach($achievements as $achievement)
                <div class="achievement-badge {{ $achievement['active'] ? '' : 'inactive' }}">
                  <div class="icon">{{ $iconMap[$achievement['icon']] ?? '⭐' }}</div>
                  <div class="value">{{ $achievement['value'] }}</div>
                  <div class="label">{{ $achievement['label'] }}</div>
                </div>
              @endforeach
            </div>
          </div>
        </section>

        <section class="card">
          <div class="card-header">
            <h3 class="card-title">Hoạt động 12 tuần qua</h3>
            <p class="card-description">Tổng hợp ngày tham gia lớp, ghi danh khóa học, nộp quiz và nộp bài tập.</p>
          </div>
          <div class="card-content">
            <div class="activity-grid">
              @foreach($activityHeatmap as $day)
                <div class="activity-day level-{{ $day['level'] }}" title="{{ $day['date']->format('d/m/Y') }} · {{ $day['count'] }} hoạt động"></div>
              @endforeach
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;margin-top:.75rem;font-size:var(--text-xs);color:var(--muted-foreground);">
              <span>Ít hơn</span>
              <div class="activity-day"></div>
              <div class="activity-day level-1"></div>
              <div class="activity-day level-2"></div>
              <div class="activity-day level-3"></div>
              <div class="activity-day level-4"></div>
              <span>Nhiều hơn</span>
            </div>
          </div>
        </section>

        <section class="card">
          <div class="card-header">
            <h3 class="card-title">Hoạt động gần đây</h3>
            <p class="card-description">Các bài làm, bài nộp và lớp mới nhất của bạn.</p>
          </div>
          <div class="card-content">
            <div class="profile-list">
              @forelse($recentActivities as $activity)
                <a href="{{ $activity->url }}" class="profile-row">
                  <div class="row-left">
                    <div class="row-icon">{{ $iconMap[$activity->type] ?? '📌' }}</div>
                    <div style="min-width:0;">
                      <div class="row-title">{{ $activity->title }}</div>
                      <div class="row-meta">{{ $activity->meta }}</div>
                    </div>
                  </div>
                  <span style="font-size:var(--text-xs);color:var(--muted-foreground);white-space:nowrap;">{{ $activity->created_at?->diffForHumans() }}</span>
                </a>
              @empty
                <div style="color:var(--muted-foreground);font-size:var(--text-sm);">Chưa có hoạt động học tập gần đây.</div>
              @endforelse
            </div>
          </div>
        </section>
      </div>

      <aside class="profile-stack">
        <section class="card">
          <div class="card-header">
            <h3 class="card-title">Thông tin cá nhân</h3>
          </div>
          <div class="card-content">
            <div class="info-list">
              <div class="info-row">
                <div class="info-icon">📧</div>
                <div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Email</div>
                  <a href="mailto:{{ $user->email }}" style="font-size:var(--text-sm);color:inherit;">{{ $user->email }}</a>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon">📞</div>
                <div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Điện thoại</div>
                  @if($user->phone)
                    <a href="tel:{{ $user->phone }}" style="font-size:var(--text-sm);color:inherit;">{{ $user->phone }}</a>
                  @else
                    <span style="font-size:var(--text-sm);color:var(--muted-foreground);">Chưa cập nhật</span>
                  @endif
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon">🏫</div>
                <div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Lớp hiện tại</div>
                  <div style="font-size:var(--text-sm);">{{ $classes->first()?->name ?? 'Chưa tham gia lớp' }}</div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-icon">📅</div>
                <div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Ngày tạo tài khoản</div>
                  <div style="font-size:var(--text-sm);">{{ $user->created_at?->format('d/m/Y') }}</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="card">
          <div class="card-header">
            <h3 class="card-title">Tiến độ nhanh</h3>
          </div>
          <div class="card-content">
            <div class="stats-grid" style="grid-template-columns:repeat(2,1fr);gap:.75rem;">
              <div class="stat-card" style="padding:1rem;">
                <div class="stat-card__value">{{ $pendingQuizCount }}</div>
                <div class="stat-card__label">Quiz đang làm</div>
              </div>
              <div class="stat-card" style="padding:1rem;">
                <div class="stat-card__value">{{ $pendingAssignmentCount }}</div>
                <div class="stat-card__label">Bài tập chưa nộp</div>
              </div>
              <div class="stat-card" style="padding:1rem;">
                <div class="stat-card__value">{{ $bestGrade !== null ? $bestGrade . '%' : '—' }}</div>
                <div class="stat-card__label">Điểm cao nhất</div>
              </div>
              <div class="stat-card" style="padding:1rem;">
                <div class="stat-card__value">{{ $assignmentCount }}</div>
                <div class="stat-card__label">Bài đã nộp</div>
              </div>
            </div>
          </div>
        </section>

        <section class="card">
          <div class="card-header">
            <h3 class="card-title">Lớp đang học</h3>
          </div>
          <div class="card-content">
            <div class="profile-list">
              @forelse($classes as $class)
                <a href="{{ route('student.classes.show', $class) }}" class="profile-row">
                  <div class="row-left">
                    <div class="row-icon">🎓</div>
                    <div style="min-width:0;">
                      <div class="row-title">{{ $class->name }}</div>
                      <div class="row-meta">{{ $class->teacher?->name ?? 'Giáo viên' }} · {{ $class->courses_count }} khóa học</div>
                    </div>
                  </div>
                  <span class="badge badge-outline">{{ $class->students_count }} HS</span>
                </a>
              @empty
                <div style="color:var(--muted-foreground);font-size:var(--text-sm);">Bạn chưa tham gia lớp nào.</div>
                <a href="{{ route('student.join-class') }}" class="btn btn-primary btn-sm">Tham gia lớp</a>
              @endforelse
            </div>
          </div>
        </section>

        <section class="card">
          <div class="card-header">
            <h3 class="card-title">Khóa học gần đây</h3>
          </div>
          <div class="card-content">
            <div class="profile-list">
              @forelse($courses as $course)
                <a href="{{ route('student.courses.show', $course) }}" class="profile-row">
                  <div class="row-left">
                    <div class="row-icon">📚</div>
                    <div style="min-width:0;">
                      <div class="row-title">{{ $course->name }}</div>
                      <div class="row-meta">{{ $course->quizzes_count }} quiz · {{ $course->assignments_count }} bài tập</div>
                    </div>
                  </div>
                </a>
              @empty
                <div style="color:var(--muted-foreground);font-size:var(--text-sm);">Chưa có khóa học nào.</div>
              @endforelse
            </div>
          </div>
        </section>
      </aside>
    </div>
  </div>
@endsection
