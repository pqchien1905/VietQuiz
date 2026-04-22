{{-- Reset Password --}}
@extends('layouts.auth')
@section('title', 'Đặt lại Mật khẩu — VietQuiz')

@push('scripts')
<script>
  window.checkStrength = (val) => {
    const bar = document.getElementById('strength-bar');
    const lbl = document.getElementById('strength-label');
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const config = [
      { pct:'0%', bg:'var(--border)', text:'' },
      { pct:'25%', bg:'var(--destructive)', text:'Rất yếu' },
      { pct:'50%', bg:'var(--warning)', text:'Trung bình' },
      { pct:'75%', bg:'var(--info)', text:'Khá mạnh' },
      { pct:'100%', bg:'var(--success)', text:'Mạnh' },
    ][score];
    if (bar) { bar.style.width = config.pct; bar.style.background = config.bg; }
    if (lbl) lbl.textContent = config.text;
  };

  window.togglePw = (id) => {
    const el = document.getElementById(id);
    if (el) el.type = el.type === 'password' ? 'text' : 'password';
  };
</script>
@endpush

@section('auth-content')
  <div class="auth-logo">
    <div class="auth-logo-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    </div>
    <h1>Tạo mật khẩu mới</h1>
    <p>Mật khẩu phải có ít nhất 8 ký tự</p>
  </div>

  <div class="card shadow-xl" style="border-width:2px;">
    <div class="card-content">
      @error('email')
      <div class="alert alert-danger" style="margin-bottom:1rem;">
        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ $message }}</span>
      </div>
      @enderror

      <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="label label-required" for="email">Email</label>
          <input type="email" id="email" name="email" class="input @error('email') input-error @enderror" value="{{ old('email', $request->email) }}" required autocomplete="email" readonly />
        </div>

        <div class="form-group" style="margin-bottom:1rem;">
          <label class="label label-required" for="password">Mật khẩu mới</label>
          <div class="input-with-icon">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="password" name="password" class="input has-icon @error('password') input-error @enderror" placeholder="Mật khẩu mới" oninput="checkStrength(this.value)" required />
            <button type="button" class="input-suffix-btn" onclick="togglePw('password')">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div style="margin-top:.5rem;">
            <div class="progress progress-sm"><div class="progress-bar" id="strength-bar" style="width:0%;transition:width .3s,background .3s;"></div></div>
            <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;" id="strength-label"></div>
          </div>
          @error('password')
          <p class="form-error" style="display:flex;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>{{ $message }}</span></p>
          @enderror
        </div>

        <div class="form-group" style="margin-bottom:1.5rem;">
          <label class="label label-required" for="password_confirmation">Xác nhận mật khẩu</label>
          <div class="input-with-icon">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="password_confirmation" name="password_confirmation" class="input has-icon" placeholder="Nhập lại mật khẩu" required />
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg">Đặt lại mật khẩu</button>
      </form>
    </div>
  </div>
@endsection
