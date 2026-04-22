{{-- Student: course-detail --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
.course-header { background:linear-gradient(135deg,var(--course-color,#3b82f6),color-mix(in srgb,var(--course-color,#3b82f6) 60%,#000)); padding:2rem 1.5rem 3.5rem; color:#fff; }
    .course-progress-ring { position:relative; width:6rem; height:6rem; }
    .course-progress-ring svg { transform:rotate(-90deg); }
    .course-progress-ring .pct { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:var(--text-lg); font-weight:700; }
</style>
@endpush

@section('content')
  <!-- Course hero -->
      <div class="course-header" id="course-header">
        <div style="max-width:900px;margin:0 auto;">
          <!-- Breadcrumb -->
          <div style="margin-bottom:1rem;">
            <a href="{{ route('student.courses') }}" style="color:rgba(255,255,255,.7);font-size:var(--text-sm);text-decoration:none;display:inline-flex;align-items:center;gap:0.25rem;">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
              Khóa học
            </a>
          </div>
          <div style="display:flex;align-items:flex-start;gap:1.5rem;flex-wrap:wrap;">
            <div>
              <h1 style="color:#fff;font-size:var(--text-3xl);margin-bottom:0.5rem;" id="course-title">Toán 10 — Học kỳ II</h1>
              <p style="color:rgba(255,255,255,.8);font-size:var(--text-sm);" id="course-teacher">GV: Nguyễn Văn A • Lớp 10A</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div style="max-width:900px;margin:0 auto;padding:0 1.5rem 2rem;">

        <!-- Stats + progress -->
        <div style="display:grid;grid-template-columns:1fr auto;gap:1.5rem;align-items:center;margin-top:-2rem;margin-bottom:1.5rem;">
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;">
            <div class="stat-card" style="text-align:center;">
              <div class="stat-card__value" id="stat-quizzes">8</div>
              <div class="stat-card__label">Bài kiểm tra</div>
            </div>
            <div class="stat-card" style="text-align:center;">
              <div class="stat-card__value" id="stat-assignments">12</div>
              <div class="stat-card__label">Bài tập</div>
            </div>
            <div class="stat-card" style="text-align:center;">
              <div class="stat-card__value" id="stat-completed">6</div>
              <div class="stat-card__label">Đã hoàn thành</div>
            </div>
            <div class="stat-card" style="text-align:center;">
              <div class="stat-card__value" style="color:var(--success);" id="stat-avg">78%</div>
              <div class="stat-card__label">Điểm TB</div>
            </div>
          </div>
          <div style="text-align:center;flex-shrink:0;">
            <div class="course-progress-ring" id="progress-ring">
              <svg width="96" height="96" viewBox="0 0 96 96">
                <circle cx="48" cy="48" r="40" fill="none" stroke="rgba(255,255,255,.2)" stroke-width="8"/>
                <circle cx="48" cy="48" r="40" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round"
                  stroke-dasharray="251.2" stroke-dashoffset="87.92" id="progress-circle"/>
              </svg>
              <div class="pct" style="color:#fff;" id="progress-pct">65%</div>
            </div>
            <div style="font-size:var(--text-xs);color:rgba(255,255,255,.7);margin-top:0.25rem;">Hoàn thành</div>
          </div>
        </div>

        <!-- Nav tabs -->
        <div class="nav-tabs" style="margin-bottom:1.5rem;">
          <button class="nav-tab active" onclick="switchTab('content',this)">Nội dung</button>
          <button class="nav-tab" onclick="switchTab('quizzes',this)">Bài kiểm tra</button>
          <button class="nav-tab" onclick="switchTab('assignments',this)">Bài tập</button>
          <button class="nav-tab" onclick="switchTab('grades',this)">Điểm số</button>
        </div>

        <!-- Tab: Nội dung -->
        <div id="tab-content">
          <div id="syllabus-list"></div>
        </div>

        <!-- Tab: Bài kiểm tra -->
        <div id="tab-quizzes" style="display:none;">
          <div class="card" id="quiz-list"></div>
        </div>

        <!-- Tab: Bài tập -->
        <div id="tab-assignments" style="display:none;">
          <div class="card" id="assignment-list"></div>
        </div>

        <!-- Tab: Điểm số -->
        <div id="tab-grades" style="display:none;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
            <div class="card">
              <div class="card-header"><h3 class="card-title">Điểm theo Bài kiểm tra</h3></div>
              <div class="card-content" id="quiz-grades"></div>
            </div>
            <div class="card">
              <div class="card-header"><h3 class="card-title">Điểm theo Bài tập</h3></div>
              <div class="card-content" id="assignment-grades"></div>
            </div>
          </div>
        </div>

      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
// Inline sidebar + header — works on file:// without CORS issues
(function(){
var role='student';
var page=location.pathname.split('/').pop().replace(/^.*\//,'').split('?')[0]||'dashboard.html';
var I={dash:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',book:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',fq:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 10.3c.2-.4.5-.8.9-1a2.1 2.1 0 0 1 2.6.4c.3.4.5.8.5 1.3 0 1.3-2 2-2 2"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',clip:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="15" y2="16"/><line x1="9" y1="8" x2="11" y2="8"/></svg>',award:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',up:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>',bell:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',trash:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>',out:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',grad:'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>'};
var NAV=[{k:'Bảng điều khiển',h:'dashboard.html',i:'dash'},{k:'Khóa học',h:'courses.html',i:'book'},{k:'Bài kiểm tra',h:'quizzes.html',i:'fq'},{k:'Bài tập',h:'assignments.html',i:'clip'},{k:'Điểm số',h:'grades.html',i:'award'},{k:'Tham gia lớp',h:'join-class.html',i:'up'}];
var cn=document.cookie.match(/auth_name=([^;]+)/);
var un=cn?decodeURIComponent(cn[1]):'Học sinh Demo';
var initials=un.split(' ').filter(Boolean).map(function(w){return w[0];}).slice(-2).join('').toUpperCase();
var saved=localStorage.getItem('vietquiz-theme')||'system';
var dark=saved==='dark'||(saved==='system'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
if(dark)document.documentElement.classList.add('dark');
var navHTML=NAV.map(function(n){var a=page===n.h||page.startsWith(n.h.replace('.html',''));return'<a href="'+n.h+'" class="nav-item'+(a?' active':'')+'">'+I[n.i]+'<span>'+n.k+'</span></a>';}).join('');
var ss=document.getElementById('sidebar-slot');
if(ss){ss.outerHTML='<aside class="sidebar" id="main-sidebar"><a href="{{ route('home') }}" class="sidebar-logo"><div class="sidebar-logo-icon">'+I.grad+'</div><div class="sidebar-logo-text"><h1>VietQuiz</h1><p>Cổng Học sinh</p></div></a><nav class="sidebar-nav">'+navHTML+'</nav><div class="sidebar-bottom"><a href="{{ route('student.trash') }}" class="nav-item'+(page==='trash.html'?' active':'')+'">'+I.trash+'<span>Thùng rác</span></a></div></aside><div class="mobile-overlay" id="mobile-overlay"></div>';var ov=document.getElementById('mobile-overlay');if(ov)ov.onclick=function(){document.getElementById('main-sidebar').classList.remove('mobile-open');ov.classList.remove('open');};}
var sunSvg='<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
var moonSvg='<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
var crownSvg='<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/></svg>';
var userSvg='<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
var settingsSvg='<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';
var logoutSvg='<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
var isDark=document.documentElement.classList.contains('dark');
var hs=document.getElementById('header-slot');
if(hs){hs.outerHTML='<header class="header" id="main-header"><button class="mobile-menu-btn" id="mobmenubtn" aria-label="Open menu"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button><div class="header-search"><div style="position:relative"><svg style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted-foreground);pointer-events:none;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="search" class="input" placeholder="Tìm kiếm khóa học, bài kiểm tra..." style="padding-left:2.5rem" /></div></div><div class="header-actions"><button class="icon-btn" id="ttbtn" title="'+(isDark?'Light mode':'Dark mode')+'">'+(isDark?sunSvg:moonSvg)+'</button><a href="{{ route('student.notifications') }}" class="icon-btn notification-btn" style="position:relative;text-decoration:none;color:inherit">'+I.bell+'<span style="position:absolute;top:-2px;right:-2px;width:1.25rem;height:1.25rem;background:var(--destructive);color:#fff;border-radius:50%;font-size:.625rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid var(--card)">5</span></a><div class="dropdown"><button class="user-menu-btn" id="umtrigger"><div class="avatar avatar-md" style="background:var(--primary);color:var(--primary-foreground);font-size:var(--text-sm);font-weight:600">'+initials+'</div><div style="display:flex;flex-direction:column;align-items:flex-start"><span class="user-menu-name">'+un+'</span><span class="user-menu-role">Học sinh</span></div><svg style="color:var(--muted-foreground);margin-left:.25rem" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></button><div class="dropdown-menu" id="umenu"><div class="dropdown-label">Tài khoản của tôi</div><a href="{{ route('student.vip') }}" class="dropdown-item" style="color:#eab308">'+crownSvg+' Nâng VIP</a><div class="dropdown-separator"></div><a href="{{ route('student.profile') }}" class="dropdown-item">'+userSvg+' Hồ sơ</a><a href="{{ route('student.settings') }}" class="dropdown-item">'+settingsSvg+' Cài đặt</a><a href="{{ route('student.help') }}" class="dropdown-item"><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><circle cx=\"12\" cy=\"12\" r=\"10\"/><path d=\"M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3\"/><line x1=\"12\" y1=\"17\" x2=\"12.01\" y2=\"17\"/></svg> Hỗ trợ</a><div class="dropdown-separator"></div><button class="dropdown-item danger" id="hlbtn">'+logoutSvg+' Đăng xuất</button></div></div></div></header>';document.getElementById('mobmenubtn').onclick=function(){document.getElementById('main-sidebar').classList.toggle('mobile-open');document.getElementById('mobile-overlay').classList.toggle('open');};document.getElementById('hlbtn').onclick=function(){document.cookie='auth_role=;path=/;expires=Thu,01 Jan 1970 00:00:00 GMT';document.cookie='auth_name=;path=/;expires=Thu,01 Jan 1970 00:00:00 GMT';location.href='{{ route('login') }}';};document.getElementById('ttbtn').onclick=function(){var d=document.documentElement.classList.toggle('dark');localStorage.setItem('vietquiz-theme',d?'dark':'light');};var ut=document.getElementById('umtrigger'),um=document.getElementById('umenu');if(ut&&um){ut.onclick=function(e){e.stopPropagation();um.classList.toggle('open');};document.onclick=function(){um.classList.remove('open');};}}
document.body.classList.add('page-enter');
})();
</script>
@endpush
