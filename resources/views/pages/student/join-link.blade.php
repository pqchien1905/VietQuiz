@extends('layouts.app')

@section('body')
<div class="auth-page" style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;">
  <div class="card" style="max-width:34rem;width:100%;">
    <div class="card-header" style="text-align:center;">
      <div style="width:4rem;height:4rem;border-radius:999px;background:color-mix(in srgb,var(--primary) 12%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--primary);">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="8.5" cy="7" r="4"/>
          <line x1="20" y1="8" x2="20" y2="14"/>
          <line x1="23" y1="11" x2="17" y2="11"/>
        </svg>
      </div>
      <h1 class="card-title" style="font-size:var(--text-2xl);">Tham gia lớp học</h1>
      <p class="card-description">
        @if($class)
          Link mời này dành cho lớp <strong>{{ $class->name }}</strong>.
        @else
          Không tìm thấy lớp với mã <strong>{{ $code }}</strong>.
        @endif
      </p>
    </div>

    <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
      @if(session('warning'))
        <div class="alert alert-warning">
          <span>{{ session('warning') }}</span>
        </div>
      @endif

      @if($class)
        <div style="display:grid;gap:.75rem;padding:1rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--muted);">
          <div style="display:flex;justify-content:space-between;gap:1rem;">
            <span style="color:var(--muted-foreground);font-size:var(--text-sm);">Lớp</span>
            <strong>{{ $class->name }}</strong>
          </div>
          <div style="display:flex;justify-content:space-between;gap:1rem;">
            <span style="color:var(--muted-foreground);font-size:var(--text-sm);">Giáo viên</span>
            <strong>{{ $class->teacher->name ?? 'Giáo viên' }}</strong>
          </div>
          <div style="display:flex;justify-content:space-between;gap:1rem;">
            <span style="color:var(--muted-foreground);font-size:var(--text-sm);">Mã lớp</span>
            <strong>{{ $class->code }}</strong>
          </div>
        </div>
      @endif

      <div class="alert alert-info">
        <span>Bạn đang ở màn giáo viên. Hãy chuyển sang màn học sinh để tham gia lớp bằng chính tài khoản hiện tại.</span>
      </div>
    </div>

    <div class="card-footer" style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center;">
      @auth
        @if(auth()->user()->role === 'teacher')
          <a href="{{ URL::signedRoute('switch.to.student', ['intended' => request()->fullUrl()]) }}" class="btn btn-primary">Chuyển sang màn học sinh</a>
          <a href="{{ route('teacher.dashboard') }}" class="btn btn-outline">Về trang giáo viên</a>
        @else
          <a href="{{ route('login') }}" class="btn btn-primary">Đăng nhập tài khoản học sinh</a>
        @endif
      @endauth
    </div>
  </div>
</div>
@endsection
