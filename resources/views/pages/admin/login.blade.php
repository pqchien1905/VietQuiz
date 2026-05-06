@extends('layouts.app')

@push('styles')
<style>
  .admin-login-wrap { min-height:100vh; display:grid; place-items:center; background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 16%,var(--background)),var(--background)); padding:1.5rem; }
  .admin-login-card { width:min(440px,100%); }
  .admin-login-logo { width:3rem; height:3rem; border-radius:var(--radius-lg); background:var(--primary); color:var(--primary-foreground); display:grid; place-items:center; font-weight:900; margin-bottom:1rem; }
</style>
@endpush

@section('body')
<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div class="admin-login-logo">VQ</div>
    <div class="card">
      <div class="card-header">
        <h1 class="card-title">Đăng nhập quản trị</h1>
        <p class="card-description">Truy cập bảng điều khiển quản trị tại `/admin`.</p>
      </div>
      <div class="card-content">
        @if($errors->any())
          <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ $errors->first() }}</span></div>
        @endif
        <form method="POST" action="{{ route('admin.login') }}" style="display:flex;flex-direction:column;gap:1rem;">
          @csrf
          <div class="form-group">
            <label class="label" for="username">Email admin</label>
            <input id="username" name="username" type="email" class="input" value="{{ old('username') }}" required autofocus>
          </div>
          <div class="form-group">
            <label class="label" for="password">Mật khẩu</label>
            <input id="password" name="password" type="password" class="input" required>
          </div>
          <button class="btn btn-primary btn-lg" type="submit">Vào trang quản trị</button>
          <p style="color:var(--muted-foreground);font-size:var(--text-xs);margin:0;">Tài khoản admin được tạo trong database. Không sử dụng mật khẩu mặc định khi triển khai.</p>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
