{{-- Confirm Password --}}
@extends('layouts.auth')
@section('title', 'Xác nhận Mật khẩu — VietQuiz')

@section('auth-content')
<div class="auth-logo">
  <div class="auth-logo-icon">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
  </div>
  <h1>Xác nhận Mật khẩu</h1>
  <p>Vui lòng xác nhận mật khẩu trước khi tiếp tục.</p>
</div>
<div class="card shadow-xl" style="border-width:2px;">
  <div class="card-content">
    <form method="POST" action="{{ route('password.confirm') }}">
      @csrf
      <div class="form-group" style="margin-bottom:1rem;">
        <label class="label" for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" class="input @error('password') input-error @enderror" required autocomplete="current-password" />
        @error('password')
          <p class="form-error" style="display:flex;">{{ $message }}</p>
        @enderror
      </div>
      <button type="submit" class="btn btn-primary w-full">Xác nhận</button>
    </form>
  </div>
</div>
@endsection
