{{-- Admin layout aligned with VietQuiz dashboard design system --}}
@extends('layouts.app')

@php
  $adminNavGroups = [
    ['label' => null, 'items' => [
      ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Tổng quan', 'icon' => 'dashboard'],
      ['route' => 'admin.analytics', 'match' => 'admin.analytics*', 'label' => 'Thống kê', 'icon' => 'analytics'],
      ['route' => 'admin.users', 'match' => 'admin.users*', 'label' => 'Người dùng', 'icon' => 'users'],
      ['route' => 'admin.classes', 'match' => 'admin.classes*', 'label' => 'Lớp học', 'icon' => 'classes'],
      ['route' => 'admin.courses', 'match' => 'admin.courses*', 'label' => 'Khóa học', 'icon' => 'courses'],
      ['route' => 'admin.quizzes', 'match' => 'admin.quizzes*', 'label' => 'Bài kiểm tra', 'icon' => 'quizzes'],
      ['route' => 'admin.questions', 'match' => 'admin.questions*', 'label' => 'Ngân hàng câu hỏi', 'icon' => 'questions'],
    ]],
    ['label' => 'Quản lý học tập', 'items' => [
      ['route' => 'admin.assignments', 'match' => 'admin.assignments*', 'label' => 'Bài tập', 'icon' => 'assignments'],
      ['route' => 'admin.submissions', 'match' => 'admin.submissions*', 'label' => 'Bài nộp', 'icon' => 'submissions'],
      ['route' => 'admin.grades', 'match' => 'admin.grades*', 'label' => 'Điểm số', 'icon' => 'grades'],
    ]],
    ['label' => 'Doanh thu', 'items' => [
      ['route' => 'admin.vip', 'match' => 'admin.vip*', 'label' => 'VIP & thanh toán', 'icon' => 'vip'],
      ['route' => 'admin.promotions', 'match' => 'admin.promotions*', 'label' => 'Khuyến mãi', 'icon' => 'promotions'],
    ]],
    ['label' => 'Vận hành', 'items' => [
      ['route' => 'admin.notifications', 'match' => 'admin.notifications*', 'label' => 'Thông báo', 'icon' => 'notifications'],
      ['route' => 'admin.tickets', 'match' => 'admin.tickets*', 'label' => 'Hỗ trợ', 'icon' => 'tickets'],
      ['route' => 'admin.trash', 'match' => 'admin.trash*', 'label' => 'Thùng rác', 'icon' => 'trash'],
    ]],
  ];
  $icons = [
    'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    'analytics' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>',
    'classes' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'courses' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
    'quizzes' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>',
    'questions' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.8 1c-.4.8-1.2 1.3-1.9 1.8-.7.5-1 1-1 2.2"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'assignments' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>',
    'submissions' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
    'grades' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
    'notifications' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
    'tickets' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'vip' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    'promotions' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
    'trash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>',
  ];
@endphp

@push('styles')
<style>
  .admin-shell { display:flex; height:100vh; overflow:hidden; background:var(--background); }
  .admin-shell .main-container { min-width:0; }
  .admin-shell .main-content { min-width:0; }
  .admin-header { min-height:var(--header-height); height:auto; padding:.75rem 1.5rem; align-items:center; background:color-mix(in srgb,var(--card) 92%,var(--background)); backdrop-filter:blur(10px); }
  .admin-header-main { display:flex; align-items:center; gap:.75rem; min-width:0; flex:1; }
  .admin-mobile-menu { flex:0 0 auto; }
  .admin-header-title { min-width:0; display:flex; flex-direction:column; gap:.2rem; }
  .admin-header-kicker { display:flex; align-items:center; gap:.4rem; color:var(--muted-foreground); font-size:.68rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
  .admin-header-kicker svg { width:.85rem; height:.85rem; color:var(--primary); }
  .admin-header-title h1 { font-size:clamp(1.05rem,1.4vw,1.35rem); font-weight:900; margin:0; line-height:1.15; letter-spacing:0; }
  .admin-header-title p { color:var(--muted-foreground); font-size:var(--text-xs); margin:0; max-width:56rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .admin-header-search { flex:0 1 24rem; min-width:16rem; margin-left:auto; }
  .admin-header-search .search-input-wrapper { display:flex; align-items:center; }
  .admin-header-search .input { height:2.25rem; border-radius:999px; padding-right:2.25rem; }
  .admin-search-submit { position:absolute; right:.25rem; top:50%; transform:translateY(-50%); width:1.75rem; height:1.75rem; border:0; border-radius:999px; display:grid; place-items:center; background:transparent; color:var(--muted-foreground); cursor:pointer; transition:background-color var(--transition-fast), color var(--transition-fast); }
  .admin-search-submit:hover { background:var(--muted); color:var(--foreground); }
  .admin-search-submit svg { width:.95rem; height:.95rem; }
  .admin-header-actions { display:flex; align-items:center; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; flex-shrink:0; }
  .admin-session-pill { display:flex; align-items:center; gap:.45rem; height:2rem; padding:0 .75rem; border:1px solid var(--border); border-radius:999px; background:var(--card); color:var(--muted-foreground); font-size:var(--text-xs); font-weight:700; }
  .admin-session-dot { width:.5rem; height:.5rem; border-radius:999px; background:var(--success); box-shadow:0 0 0 3px color-mix(in srgb,var(--success) 16%,transparent); }
  .admin-header-form { margin:0; }
  .admin-content { display:flex; flex-direction:column; gap:1.5rem; min-width:0; max-width:100%; }
  .admin-content > * { min-width:0; }
  .admin-hero { border:1px solid var(--border); border-radius:var(--radius-xl); background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 10%,var(--card)),var(--card)); padding:1.5rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
  .admin-hero h2 { margin:0; font-size:clamp(1.4rem,2vw,2rem); font-weight:900; line-height:1.2; }
  .admin-hero p { margin:.45rem 0 0; color:var(--muted-foreground); max-width:58rem; }
  .admin-toolbar { display:flex; align-items:end; gap:.75rem; flex-wrap:wrap; }
  .admin-table-actions { display:flex; align-items:center; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
  .admin-inline-form { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
  .admin-grid-2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .admin-grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
  .admin-row-title { font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .admin-row-meta { color:var(--muted-foreground); font-size:var(--text-xs); margin-top:.25rem; display:flex; gap:.5rem; flex-wrap:wrap; }
  .admin-login-wrap { min-height:100vh; display:grid; place-items:center; background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 16%,var(--background)),var(--background)); padding:1.5rem; }
  .admin-login-card { width:min(440px,100%); }
  .admin-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.5rem; min-width:420px; }
  .admin-nav-group { margin-top:.65rem; }
  .admin-nav-group:first-child { margin-top:0; }
  .admin-nav-group__label { padding:.5rem .75rem .25rem; color:var(--muted-foreground); font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
  .activity-item { display:flex; align-items:center; gap:.75rem; padding:.75rem 0; border-top:1px solid var(--border); }
  .activity-item:first-child { border-top:0; padding-top:0; }
  .empty-state { text-align:center; padding:2rem 1rem; color:var(--muted-foreground); }
  .admin-content .card { min-width:0; }
  .admin-content .card-header { gap:1rem; }
  .admin-content .card-header > * { min-width:0; }
  .admin-content .stats-grid { margin-bottom:0; }
  .admin-content :is(.user-filter-grid,.class-filter-grid,.course-filter-grid,.quiz-filter-grid,.question-filter-grid,.assignment-filter-grid,.submission-filter-grid,.grade-filter-grid,.promotion-filter-grid,.vip-filter-grid,.notification-filter-grid,.ticket-filter-grid,.trash-filter-grid) {
    display:grid !important;
    grid-template-columns:repeat(auto-fit,minmax(8rem,1fr)) !important;
    align-items:end;
    gap:.75rem;
    width:100%;
  }
  .admin-content :is(.user-filter-grid,.class-filter-grid,.course-filter-grid,.quiz-filter-grid,.question-filter-grid,.assignment-filter-grid,.submission-filter-grid,.grade-filter-grid,.promotion-filter-grid,.vip-filter-grid,.notification-filter-grid,.ticket-filter-grid,.trash-filter-grid) > .form-group:first-child {
    grid-column:span 2;
  }
  .admin-content :is(.user-filter-grid,.class-filter-grid,.course-filter-grid,.quiz-filter-grid,.question-filter-grid,.assignment-filter-grid,.submission-filter-grid,.grade-filter-grid,.promotion-filter-grid,.vip-filter-grid,.notification-filter-grid,.ticket-filter-grid,.trash-filter-grid) .form-group {
    min-width:0;
  }
  .admin-content :is(.user-filter-grid,.class-filter-grid,.course-filter-grid,.quiz-filter-grid,.question-filter-grid,.assignment-filter-grid,.submission-filter-grid,.grade-filter-grid,.promotion-filter-grid,.vip-filter-grid,.notification-filter-grid,.ticket-filter-grid,.trash-filter-grid) :is(.input,.select,button,.btn) {
    width:100%;
    min-width:0;
  }
  .admin-content .table-wrapper {
    overflow-x:auto !important;
    overflow-y:hidden;
    max-width:100%;
    -webkit-overflow-scrolling:touch;
  }
  .admin-content .table-wrapper table {
    min-width:980px;
  }
  .admin-content td,
  .admin-content th {
    overflow-wrap:anywhere;
  }
  .admin-content .admin-table-actions,
  .admin-content :is(.user-actions,.class-actions,.course-actions,.quiz-actions,.assignment-actions,.question-actions,.promotion-actions,.notification-actions,.ticket-actions) {
    min-width:max-content;
  }
  .admin-content .btn { white-space:nowrap; }
  @media (max-width:1100px) { .admin-grid-2,.admin-grid-3 { grid-template-columns:1fr; } }
  @media (max-width:900px) {
    .admin-header { align-items:flex-start; flex-direction:column; }
    .admin-header-main,.admin-header-search,.admin-header-actions { width:100%; }
    .admin-header-search { margin-left:0; flex-basis:auto; min-width:0; }
    .admin-header-actions { justify-content:flex-start; }
    .admin-header-title p { white-space:normal; }
  }
  @media (max-width:720px) {
    .admin-hero { flex-direction:column; align-items:flex-start; }
    .admin-form-grid { grid-template-columns:1fr; min-width:0; }
    .admin-content :is(.user-filter-grid,.class-filter-grid,.course-filter-grid,.quiz-filter-grid,.question-filter-grid,.assignment-filter-grid,.submission-filter-grid,.grade-filter-grid,.promotion-filter-grid,.vip-filter-grid,.notification-filter-grid,.ticket-filter-grid,.trash-filter-grid) {
      grid-template-columns:1fr !important;
    }
    .admin-content :is(.user-filter-grid,.class-filter-grid,.course-filter-grid,.quiz-filter-grid,.question-filter-grid,.assignment-filter-grid,.submission-filter-grid,.grade-filter-grid,.promotion-filter-grid,.vip-filter-grid,.notification-filter-grid,.ticket-filter-grid,.trash-filter-grid) > .form-group:first-child {
      grid-column:auto;
    }
  }
  @media (max-width:520px) {
    .admin-header { padding:.75rem 1rem; }
    .admin-header-actions > .btn,
    .admin-header-actions > form,
    .admin-header-actions > form .btn { width:100%; }
    .admin-header-search .input { height:2.5rem; }
    .admin-session-pill { width:100%; justify-content:center; }
  }
</style>
@endpush

@section('body')
<div class="admin-shell">
  <aside class="sidebar" id="main-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
      <div class="sidebar-logo-icon">{!! $icons['dashboard'] !!}</div>
      <div class="sidebar-logo-text">
        <h1>VietQuiz</h1>
        <p>Bảng quản trị</p>
      </div>
    </a>

    <nav class="sidebar-nav" aria-label="Điều hướng admin">
      @foreach($adminNavGroups as $group)
        <div class="admin-nav-group">
          @if($group['label'])
            <div class="admin-nav-group__label">{{ $group['label'] }}</div>
          @endif
          @foreach($group['items'] as $item)
            <a href="{{ route($item['route']) }}" class="nav-item {{ request()->routeIs($item['match']) ? 'active' : '' }}">
              {!! $icons[$item['icon']] !!}
              <span>{{ $item['label'] }}</span>
            </a>
          @endforeach
        </div>
      @endforeach
    </nav>

    <div class="sidebar-bottom">
      <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button class="nav-item" type="submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          <span>Đăng xuất</span>
        </button>
      </form>
    </div>
  </aside>

  <div class="main-container">
    <header class="header admin-header">
      <div class="admin-header-main">
        <button class="mobile-menu-btn admin-mobile-menu" id="admin-mobile-menu-btn" type="button" aria-label="Mở menu quản trị" onclick="document.getElementById('main-sidebar')?.classList.add('mobile-open');document.getElementById('admin-mobile-overlay')?.classList.add('open');">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
        </button>
        <div class="admin-header-title">
          <div class="admin-header-kicker">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Admin Console</span>
          </div>
        <h1>@yield('page-title', 'Admin')</h1>
        <p>@yield('page-description', 'Quản trị toàn bộ hệ thống VietQuiz')</p>
        </div>
      </div>
      <form method="GET" action="{{ route('admin.search') }}" class="header-search admin-header-search" role="search">
        <div class="search-input-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input
            class="input"
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Tìm người dùng, lớp, khóa học..."
            aria-label="Tìm kiếm trong admin"
            autocomplete="off"
          >
          <button class="admin-search-submit" type="submit" aria-label="Tìm kiếm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          </button>
        </div>
      </form>
      <div class="header-actions admin-header-actions">
        @yield('actions')
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline btn-sm">Tổng quan</a>
        <a href="{{ route('home') }}" class="btn btn-outline btn-sm">Về ứng dụng</a>
        <span class="admin-session-pill"><span class="admin-session-dot"></span>Phiên admin</span>
        <form method="POST" action="{{ route('admin.logout') }}" class="admin-header-form">
          @csrf
          <button class="btn btn-destructive btn-sm" type="submit">Đăng xuất</button>
        </form>
      </div>
    </header>
    <div class="mobile-overlay" id="admin-mobile-overlay" onclick="document.getElementById('main-sidebar')?.classList.remove('mobile-open');this.classList.remove('open');"></div>

    <main class="main-content">
      <div class="admin-content">
        @if(session('success'))
          <div class="alert alert-success"><span>{{ session('success') }}</span></div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger"><span>{{ $errors->first() }}</span></div>
        @endif
        @yield('content')
      </div>
    </main>
  </div>
</div>
@endsection
