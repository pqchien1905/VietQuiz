{{-- Admin layout aligned with VietQuiz dashboard design system --}}
@extends('layouts.app')

@php
  $adminNavGroups = [
    ['label' => null, 'items' => [
      ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Tổng quan', 'icon' => 'dashboard'],
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
      ['route' => 'admin.system', 'match' => 'admin.system', 'label' => 'Hệ thống', 'icon' => 'system'],
    ]],
  ];
  $icons = [
    'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
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
    'system' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82"/></svg>',
  ];
@endphp

@push('styles')
<style>
  .admin-shell { display:flex; height:100vh; overflow:hidden; background:var(--background); }
  .admin-header-title { min-width:0; }
  .admin-header-title h1 { font-size:var(--text-lg); font-weight:800; margin:0; line-height:1.2; }
  .admin-header-title p { color:var(--muted-foreground); font-size:var(--text-xs); margin:.15rem 0 0; }
  .admin-content { display:flex; flex-direction:column; gap:1.5rem; }
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
  @media (max-width:1100px) { .admin-grid-2,.admin-grid-3 { grid-template-columns:1fr; } }
  @media (max-width:720px) { .admin-hero { flex-direction:column; align-items:flex-start; } .admin-form-grid { grid-template-columns:1fr; min-width:0; } }
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
    <header class="header">
      <div class="admin-header-title">
        <h1>@yield('page-title', 'Admin')</h1>
        <p>@yield('page-description', 'Quản trị toàn bộ hệ thống VietQuiz')</p>
      </div>
      <div class="header-actions">
        @yield('actions')
        <a href="{{ route('home') }}" class="btn btn-outline btn-sm">Về ứng dụng</a>
      </div>
    </header>

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
