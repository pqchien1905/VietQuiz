{{-- Header component --}}
@php
    $role = $role ?? auth()->user()->role ?? 'teacher';
    $user = auth()->user();
    $placeholder = $role === 'teacher'
        ? 'Tìm kiếm bài kiểm tra, bài tập, học sinh...'
        : 'Tìm kiếm khóa học, bài kiểm tra...';
    $unreadCount = $user ? $user->notifications()->where('is_read', false)->count() : 0;
    $initials = $user ? collect(explode(' ', $user->name))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') : 'U';
@endphp

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
        <div class="avatar avatar-md" style="background:var(--primary);color:var(--primary-foreground);font-size:var(--text-sm);font-weight:600;">{{ $initials }}</div>
        <div style="display:flex;flex-direction:column;align-items:flex-start;">
          <span class="user-menu-name">{{ $user->name ?? 'Người dùng' }}</span>
          <span class="user-menu-role">{{ $role === 'teacher' ? 'Giáo viên' : 'Học sinh' }}</span>
        </div>
        <svg style="color:var(--muted-foreground);margin-left:.25rem" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </button>

      <div class="dropdown-menu" id="user-menu" role="menu">
        <div class="dropdown-label">Tài khoản của tôi</div>
        <a href="{{ route("$role.vip") }}" class="dropdown-item" style="color:#eab308;" role="menuitem">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/></svg>
          Nâng VIP
        </a>
        <div class="dropdown-separator"></div>
        <a href="{{ route("$role.profile") }}" class="dropdown-item" role="menuitem">Hồ sơ</a>
        <a href="{{ route("$role.settings") }}" class="dropdown-item" role="menuitem">Cài đặt</a>
        <a href="{{ route("$role.help") }}" class="dropdown-item" role="menuitem">Hỗ trợ</a>
        <div class="dropdown-separator"></div>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
          @csrf
          <button type="submit" class="dropdown-item danger" role="menuitem" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">Đăng xuất</button>
        </form>
      </div>
    </div>
  </div>
</header>

@push('scripts')
<script>
  // Theme toggle
  const themeBtn = document.getElementById('theme-toggle-btn');
  const darkIcon = document.getElementById('theme-icon-dark');
  const lightIcon = document.getElementById('theme-icon-light');

  function applyTheme(theme) {
    const resolved = theme === 'system'
      ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : theme;
    document.documentElement.classList.toggle('dark', resolved === 'dark');
    darkIcon.style.display = resolved === 'dark' ? 'none' : 'block';
    lightIcon.style.display = resolved === 'dark' ? 'block' : 'none';
  }

  function getSavedTheme() {
    return localStorage.getItem('vietquiz-theme') || 'system';
  }

  // Init theme
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

  // Mobile sidebar
  window.toggleMobileSidebar = function() {
    document.getElementById('main-sidebar')?.classList.toggle('mobile-open');
    document.getElementById('mobile-overlay')?.classList.toggle('open');
  };
  document.getElementById('mobile-overlay')?.addEventListener('click', () => {
    document.getElementById('main-sidebar')?.classList.remove('mobile-open');
    document.getElementById('mobile-overlay')?.classList.remove('open');
  });

  // User dropdown
  const trigger = document.getElementById('user-menu-trigger');
  const menu = document.getElementById('user-menu');
  trigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    menu?.classList.toggle('open');
    trigger.setAttribute('aria-expanded', menu?.classList.contains('open') ? 'true' : 'false');
  });
  document.addEventListener('click', () => {
    menu?.classList.remove('open');
    trigger?.setAttribute('aria-expanded', 'false');
  });

  // Page enter animation
  document.body.classList.add('page-enter');
</script>
@endpush
