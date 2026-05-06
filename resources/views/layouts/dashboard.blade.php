{{-- Dashboard layout for teacher/student pages --}}
@extends('layouts.app')

@php
  $role = $role ?? auth()->user()->role ?? 'teacher';
  $user = auth()->user();
  $placeholder = $role === 'teacher'
    ? 'Tìm kiếm bài kiểm tra, bài tập, học sinh...'
    : 'Tìm kiếm khóa học, bài kiểm tra...';
  $unreadCount = $user ? $user->notifications()->where('is_read', false)->count() : 0;
  $initials = $user ? collect(explode(' ', $user->name))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') : 'U';
  $isVip = $user && $user->vipSubscription && $user->vipSubscription->is_active;
  $avatarUrl = null;
  if ($user && $user->avatar) {
    $avatarUrl = \Illuminate\Support\Str::startsWith($user->avatar, ['http://', 'https://'])
      ? $user->avatar
      : asset('storage/' . ltrim($user->avatar, '/'));
  }
  $planLabels = $role === 'student'
    ? ['monthly' => 'Bỏ quảng cáo', 'yearly' => 'Bỏ quảng cáo', 'lifetime' => 'Bỏ quảng cáo']
    : ['monthly' => 'Pro tháng', 'yearly' => 'Pro năm', 'lifetime' => 'Pro trọn đời', 'pro' => 'Pro', 'enterprise' => 'Enterprise'];
  $roleLabel = $role === 'teacher' ? 'Giáo viên' : 'Học sinh';
  $accountSubtitle = $isVip
    ? ($planLabels[$user->vipSubscription->plan] ?? 'VIP')
    : ($role === 'student' ? 'Gói miễn phí' : 'Tài khoản thường');
  $vipDetailUrl = route("$role.vip") . ($role === 'teacher' ? '#vip-plans' : '#vip-plan');
  $vipExpiresLabel = null;
  if ($isVip) {
    $vipExpiresLabel = ($user->vipSubscription->plan === 'lifetime' || !$user->vipSubscription->expires_at)
      ? 'Trọn đời'
      : $user->vipSubscription->expires_at->format('d/m/Y');
  }

  $currentPage = request()->path();
  $currentPage = last(explode('/', $currentPage)) ?: 'dashboard';
  if (!$currentPage) {
    $currentPage = 'dashboard';
  }

  $teacherPages = [
    'dashboard' => 'Bảng điều khiển',
    'classes' => 'Lớp của Tôi',
    'courses' => 'Khóa học',
    'quizzes' => 'Bài kiểm tra',
    'questions' => 'Ngân hàng câu hỏi',
    'assignments' => 'Bài tập',
    'grading' => 'Chấm điểm',
    'students' => 'Học sinh',
    'analytics' => 'Phân tích',
  ];
  $studentPages = [
    'dashboard' => 'Bảng điều khiển',
    'classes' => 'Lớp học',
    'courses' => 'Khóa học',
    'quizzes' => 'Bài kiểm tra',
    'assignments' => 'Bài tập',
    'grades' => 'Điểm số',
    'join-class' => 'Tham gia lớp',
  ];
  $pages = $role === 'teacher' ? $teacherPages : $studentPages;

  $navIcons = [
    'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    'classes' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'courses' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
    'quizzes' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 10.3c.2-.4.5-.8.9-1a2.1 2.1 0 0 1 2.6.4c.3.4.5.8.5 1.3 0 1.3-2 2-2 2"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'questions' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/></svg>',
    'assignments' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>',
    'grading' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
    'students' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'analytics' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    'grades' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    'join-class' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>',
  ];

  $bellIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
  $trashIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
  $logoutIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
  $gradIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>';

  $hasTeacherAccount = $user && ($user->role === 'teacher' || $user->canSwitchRole());
  $hasStudentAccount = $user && ($user->role === 'student' || $user->canSwitchRole());
@endphp

@push('styles')
<style>
  .activity-bar { display: flex; flex-direction: column; align-items: center; gap: 0.25rem; flex: 1; }
  .activity-bar-inner { width: 100%; max-width: 2rem; border-radius: 4px 4px 0 0; background: linear-gradient(to top, var(--primary), color-mix(in srgb, var(--primary) 60%, var(--info))); transition: height var(--transition-slow); }
  .activity-bar-label { font-size: 0.65rem; color: var(--muted-foreground); }
  .chart-bars { display: flex; align-items: flex-end; gap: 0.375rem; height: 100px; padding-bottom: 0.25rem; }
  .quick-action { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--card); cursor: pointer; transition: all var(--transition-fast); text-decoration: none; color: var(--foreground); }
  .quick-action:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); border-color: var(--primary); }
  .quick-action-icon { width: 2.5rem; height: 2.5rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .task-item { display:flex; align-items:flex-start; gap:0.75rem; padding:0.875rem 0; border-top:1px solid var(--border); position:relative; }
  .task-item::before { content:''; position:absolute; left:-1.5rem; right:-1.5rem; top:0; border-top:1px solid var(--border); }
  .task-item:first-child::before { display:none; }
  .task-icon { width:2.25rem; height:2.25rem; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .activity-item { display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0; border-top:1px solid var(--border); position:relative; }
  .activity-item::before { content:''; position:absolute; left:-1.5rem; right:-1.5rem; top:0; border-top:1px solid var(--border); }
  .activity-item:first-child::before { display:none; }
  .user-menu-btn { min-height:2.75rem; max-width:18rem; border:1px solid transparent; }
  .user-menu-btn:hover, .user-menu-btn[aria-expanded="true"] { border-color:var(--border); background:var(--muted); }
  .user-menu-avatar-wrap { position:relative; flex-shrink:0; }
  .user-menu-avatar-wrap .avatar { overflow:hidden; }
  .user-menu-avatar-wrap .avatar img { width:100%; height:100%; object-fit:cover; }
  .user-online-dot { position:absolute; right:-1px; bottom:-1px; width:.7rem; height:.7rem; border-radius:999px; background:var(--success); border:2px solid var(--card); }
  .user-menu-meta { min-width:0; display:flex; flex-direction:column; align-items:flex-start; }
  .user-menu-name { max-width:9.5rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .user-menu-role-row { display:flex; align-items:center; gap:.375rem; min-width:0; }
  .user-vip-pill { font-size:.6rem; font-weight:800; padding:.1rem .4rem; border-radius:9999px; background:linear-gradient(135deg,#f59e0b,#eab308); color:#000; line-height:1.4; white-space:nowrap; }
  .user-menu-chevron { transition:transform var(--transition-fast); flex-shrink:0; }
  .user-menu-btn[aria-expanded="true"] .user-menu-chevron { transform:rotate(180deg); }
  .user-dropdown-menu { min-width:18.5rem; padding:.5rem; }
  .user-dropdown-header { display:flex; gap:.75rem; align-items:center; padding:.75rem; border-radius:var(--radius-md); background:color-mix(in srgb,var(--muted) 60%,transparent); margin-bottom:.35rem; }
  .user-dropdown-avatar { width:2.75rem; height:2.75rem; border-radius:999px; display:flex; align-items:center; justify-content:center; overflow:hidden; background:var(--primary); color:var(--primary-foreground); font-weight:800; flex-shrink:0; }
  .user-dropdown-avatar img { width:100%; height:100%; object-fit:cover; }
  .user-dropdown-email { font-size:var(--text-xs); color:var(--muted-foreground); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:12.25rem; }
  @media (max-width:640px) {
    .user-menu-meta { display:none; }
    .user-menu-btn { padding-right:.35rem; }
    .user-dropdown-menu { min-width:17rem; }
  }
</style>
@endpush

@push('scripts')
<script>
  function openTeacherRegModal() {
    var modal = document.getElementById('teacher-reg-modal');
    if (modal) {
      modal.style.display = 'flex';
      var menu = document.getElementById('user-menu');
      if (menu) menu.classList.remove('open');
    }
  }
  function closeTeacherRegModal() {
    var modal = document.getElementById('teacher-reg-modal');
    if (modal) modal.style.display = 'none';
  }
  function openRemoveTeacherModal() {
    var modal = document.getElementById('remove-teacher-modal');
    if (modal) {
      modal.style.display = 'flex';
      var menu = document.getElementById('user-menu');
      if (menu) menu.classList.remove('open');
    }
  }
  function closeRemoveTeacherModal() {
    var modal = document.getElementById('remove-teacher-modal');
    if (modal) modal.style.display = 'none';
  }

  const themeBtn = document.getElementById('theme-toggle-btn');
  const darkIcon = document.getElementById('theme-icon-dark');
  const lightIcon = document.getElementById('theme-icon-light');

  function applyTheme(theme) {
    const resolved = theme === 'system'
      ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : theme;
    document.documentElement.classList.toggle('dark', resolved === 'dark');
    if (darkIcon && lightIcon) {
      darkIcon.style.display = resolved === 'dark' ? 'none' : 'block';
      lightIcon.style.display = resolved === 'dark' ? 'block' : 'none';
    }
  }

  function getSavedTheme() {
    return localStorage.getItem('vietquiz-theme') || 'system';
  }

  (function() {
    const saved = getSavedTheme();
    applyTheme(saved);
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (getSavedTheme() === 'system') applyTheme('system');
    });
  })();

  themeBtn?.addEventListener('click', () => {
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem('vietquiz-theme', next);
    applyTheme(next);
  });

  window.toggleMobileSidebar = function() {
    document.getElementById('main-sidebar')?.classList.toggle('mobile-open');
    document.getElementById('mobile-overlay')?.classList.toggle('open');
  };
  document.getElementById('mobile-overlay')?.addEventListener('click', () => {
    document.getElementById('main-sidebar')?.classList.remove('mobile-open');
    document.getElementById('mobile-overlay')?.classList.remove('open');
  });

  const trigger = document.getElementById('user-menu-trigger');
  const menu = document.getElementById('user-menu');
  trigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    menu?.classList.toggle('open');
    trigger.setAttribute('aria-expanded', menu?.classList.contains('open') ? 'true' : 'false');
  });
  menu?.addEventListener('click', (e) => {
    e.stopPropagation();
  });
  document.addEventListener('click', () => {
    menu?.classList.remove('open');
    trigger?.setAttribute('aria-expanded', 'false');
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      menu?.classList.remove('open');
      trigger?.setAttribute('aria-expanded', 'false');
      trigger?.focus();
    }
  });

  document.getElementById('teacher-reg-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeTeacherRegModal();
  });
  document.getElementById('remove-teacher-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRemoveTeacherModal();
  });

  document.body.classList.add('page-enter');
</script>
@endpush

@section('body')
<div class="app-shell">
  <aside class="sidebar" id="main-sidebar">
    <a href="{{ route('home') }}" class="sidebar-logo" aria-label="VietQuiz Home">
      <div class="sidebar-logo-icon">{!! $gradIcon !!}</div>
      <div class="sidebar-logo-text">
        <h1>VietQuiz</h1>
        <p>{{ $role === 'teacher' ? 'Cổng Giáo viên' : 'Cổng Học sinh' }}</p>
      </div>
    </a>

    <nav class="sidebar-nav" aria-label="Điều hướng chính">
      @foreach ($pages as $page => $label)
        @php
          $isActivePage = request()->routeIs("$role.$page") || request()->routeIs("$role.$page.*") || $currentPage === $page || ($currentPage === 'dashboard' && $page === 'dashboard');
          if ($role === 'teacher' && $page === 'classes' && request()->routeIs('teacher.class-detail')) {
            $isActivePage = true;
          }
        @endphp
        <a href="{{ route("$role.$page") }}"
           class="nav-item {{ $isActivePage ? 'active' : '' }}"
           data-page="{{ $page }}">
          {!! $navIcons[$page] ?? '' !!}
          <span>{{ $label }}</span>
        </a>
      @endforeach
    </nav>

    <div class="sidebar-bottom">
      <a href="{{ route("$role.trash") }}" class="nav-item {{ $currentPage === 'trash' ? 'active' : '' }}">
        {!! $trashIcon !!}<span>Thùng rác</span>
      </a>
    </div>
  </aside>
  <div class="mobile-overlay" id="mobile-overlay"></div>

  <div class="main-container">
    <header class="header" id="main-header">
      <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Mở menu" onclick="toggleMobileSidebar()">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>

      <div class="header-search">
        <div style="position:relative;">
          <svg style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted-foreground);pointer-events:none;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input type="search" class="input" placeholder="{{ $placeholder }}" style="padding-left:2.5rem" aria-label="Tìm kiếm" />
        </div>
      </div>

      <div class="header-actions">
        <button class="icon-btn" id="theme-toggle-btn" title="Chuyển chế độ">
          <svg id="theme-icon-dark" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
          </svg>
          <svg id="theme-icon-light" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
            <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
          </svg>
        </button>

        <a href="{{ route("$role.notifications") }}" class="icon-btn notification-btn" style="position:relative;text-decoration:none;color:inherit;" aria-label="Thông báo">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          @if($unreadCount > 0)
          <span class="badge-dot-indicator">{{ $unreadCount }}</span>
          @endif
        </a>

        <div class="dropdown" id="user-dropdown">
          <button class="user-menu-btn" id="user-menu-trigger" aria-haspopup="true" aria-expanded="false">
            <div class="user-menu-avatar-wrap">
              <div class="avatar avatar-md" style="background:var(--primary);color:var(--primary-foreground);font-size:var(--text-sm);font-weight:600;">
                @if($avatarUrl)
                  <img src="{{ $avatarUrl }}" alt="{{ $user->name ?? 'Người dùng' }}">
                @else
                  {{ $initials }}
                @endif
              </div>
              <span class="user-online-dot" aria-hidden="true"></span>
            </div>
            <div class="user-menu-meta">
              <span class="user-menu-name">{{ $user->name ?? 'Người dùng' }}</span>
              <div class="user-menu-role-row">
                <span class="user-menu-role">{{ $roleLabel }}</span>
                @if($isVip)
                  <span class="user-vip-pill" title="VIP {{ $planLabels[$user->vipSubscription->plan] ?? '' }}">
                    ⭐ {{ $planLabels[$user->vipSubscription->plan] ?? 'VIP' }}
                  </span>
                @endif
              </div>
            </div>
            <svg class="user-menu-chevron" style="color:var(--muted-foreground);margin-left:.25rem" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>

          <div class="dropdown-menu user-dropdown-menu" id="user-menu" role="menu" aria-labelledby="user-menu-trigger">
            <div class="user-dropdown-header">
              <div class="user-dropdown-avatar">
                @if($avatarUrl)
                  <img src="{{ $avatarUrl }}" alt="{{ $user->name ?? 'Người dùng' }}">
                @else
                  {{ $initials }}
                @endif
              </div>
              <div style="min-width:0;">
                <div style="font-weight:800;font-size:var(--text-sm);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user->name ?? 'Người dùng' }}</div>
                <div class="user-dropdown-email">{{ $user->email ?? '' }}</div>
                <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.35rem;">
                  <span class="badge badge-outline">{{ $roleLabel }}</span>
                  <span class="badge {{ $isVip ? 'badge-warning' : 'badge-default' }}">{{ $accountSubtitle }}</span>
                </div>
              </div>
            </div>
            @if($isVip)
              <a href="{{ $vipDetailUrl }}" class="dropdown-item" style="color:#eab308;" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/></svg>
                <span style="display:flex;flex-direction:column;gap:.1rem;min-width:0;">
                  <span>Quản lý / gia hạn gói</span>
                  <span style="font-size:var(--text-xs);color:var(--muted-foreground);">Hiệu lực đến: {{ $vipExpiresLabel }}</span>
                </span>
              </a>
            @else
              <a href="{{ route("$role.vip") }}" class="dropdown-item" style="color:#eab308;" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/></svg>
                Nâng cấp VIP
              </a>
            @endif
            <div class="dropdown-separator"></div>
            <a href="{{ route("$role.profile") }}" class="dropdown-item" role="menuitem">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Hồ sơ
            </a>
            <a href="{{ route("$role.settings") }}" class="dropdown-item" role="menuitem">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Cài đặt
            </a>
            <a href="{{ route("$role.help") }}" class="dropdown-item" role="menuitem">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              Hỗ trợ
            </a>
            @if($user && $user->role === 'student')
              <div class="dropdown-separator"></div>
              @if($hasTeacherAccount)
                <a href="{{ URL::signedRoute('switch.to.teacher') }}" class="dropdown-item" role="menuitem">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                  Qua màn Giáo viên
                </a>
              @else
                <a href="#" onclick="event.preventDefault();openTeacherRegModal();" class="dropdown-item" role="menuitem">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                  Đăng ký làm Giáo viên
                </a>
              @endif
            @elseif($user && $user->role === 'teacher')
              <div class="dropdown-separator"></div>
              <a href="{{ $hasStudentAccount ? URL::signedRoute('switch.to.student') : route('register.as.student') }}" class="dropdown-item" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3l4 4-4 4"/><path d="M20 7H4"/><path d="M8 21l-4-4 4-4"/><path d="M4 17h16"/></svg>
                Qua màn Học sinh
              </a>
              <a href="#" onclick="event.preventDefault();openRemoveTeacherModal();" class="dropdown-item danger" role="menuitem" style="color:#dc2626;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="22" y1="11" x2="16" y2="11"/><line x1="5" y1="11" x2="2" y2="11"/></svg>
                Bỏ quyền làm giáo viên
              </a>
            @endif
            <div class="dropdown-separator"></div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
              @csrf
              <button type="submit" class="dropdown-item danger" role="menuitem" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
                {!! $logoutIcon !!}
                Đăng xuất
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <main class="main-content" id="main-content">
      @yield('content')
    </main>
  </div>
</div>

<x-chatbot-widget :role="$role" />

<!-- Modal: Register as Teacher -->
<div id="teacher-reg-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--card);border-radius:var(--radius-lg);padding:1.5rem;max-width:400px;width:90%;box-shadow:var(--shadow-lg);">
    <div style="text-align:center;margin-bottom:1.5rem;">
      <div style="width:3rem;height:3rem;border-radius:50%;background:color-mix(in srgb,var(--primary) 12%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--primary);">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
      </div>
      <h3 style="font-size:var(--text-xl);font-weight:700;margin-bottom:0.5rem;">Đăng ký làm Giáo viên</h3>
      <p style="color:var(--muted-foreground);font-size:var(--text-sm);">
        Bạn có muốn bật màn giáo viên trên tài khoản hiện tại để quản lý lớp học và tạo bài kiểm tra?
      </p>
    </div>
    <div style="display:flex;gap:0.75rem;">
      <button onclick="closeTeacherRegModal()" class="btn btn-secondary" style="flex:1;">Hủy</button>
      <a href="{{ route('register.as.teacher') }}" class="btn btn-primary" style="flex:1;text-align:center;text-decoration:none;">Xác nhận</a>
    </div>
  </div>
</div>

<!-- Modal: Remove Teacher Role -->
<div id="remove-teacher-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--card);border-radius:var(--radius-lg);padding:1.5rem;max-width:400px;width:90%;box-shadow:var(--shadow-lg);">
    <div style="text-align:center;margin-bottom:1.5rem;">
      <div style="width:3rem;height:3rem;border-radius:50%;background:color-mix(in srgb,#dc2626 12%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:#dc2626;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="22" y1="11" x2="16" y2="11"/><line x1="5" y1="11" x2="2" y2="11"/></svg>
      </div>
      <h3 style="font-size:var(--text-xl);font-weight:700;margin-bottom:0.5rem;">Bỏ quyền làm giáo viên</h3>
      <p style="color:var(--muted-foreground);font-size:var(--text-sm);">
        Bạn có chắc muốn bỏ quyền giáo viên? Tài khoản của bạn sẽ trở thành tài khoản học sinh và bạn sẽ không còn có thể tạo lớp học hoặc bài kiểm tra.
      </p>
    </div>
    <div style="display:flex;gap:0.75rem;">
      <button onclick="closeRemoveTeacherModal()" class="btn btn-secondary" style="flex:1;">Hủy</button>
      <a href="{{ route('remove.teacher.role') }}" class="btn btn-danger" style="flex:1;text-align:center;text-decoration:none;background:#dc2626;color:#fff;border-color:#dc2626;">Xác nhận</a>
    </div>
  </div>
</div>
@endsection
