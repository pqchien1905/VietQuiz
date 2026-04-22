{{-- Verify Email --}}
@extends('layouts.auth')
@section('title', 'Xác thực Email — VietQuiz')

@section('auth-content')
<div class="auth-logo">
  <div class="auth-logo-icon">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
  </div>
  <h1>Xác thực Email</h1>
  <p>Vui lòng xác thực email trước khi tiếp tục.</p>
</div>
<div class="card shadow-xl" style="border-width:2px;">
  <div class="card-content">
    <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1rem;">
      Cảm ơn bạn đã đăng ký! Trước khi bắt đầu, vui lòng xác thực email bằng cách nhấn vào link trong email chúng tôi vừa gửi cho bạn.
    </p>
    @if (session('status') == 'verification-link-sent')
      <div class="alert alert-success" style="margin-bottom:1rem;">
        Link xác thực mới đã được gửi đến email của bạn.
      </div>
    @endif
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Gửi lại email xác thực</button>
      </form>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline">Đăng xuất</button>
      </form>
    </div>
  </div>
</div>
@endsection
