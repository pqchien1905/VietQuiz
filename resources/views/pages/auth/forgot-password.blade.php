{{-- Forgot Password Page --}}
@extends('layouts.auth')

@push('scripts')
<script>
  // Theme and auth are handled by the main layout

  // Show step-newpass if hash #reset present
  if (window.location.hash === '#reset') {
    document.getElementById('step-email').style.display = 'none';
    document.getElementById('step-newpass').style.display = '';
  }

  let resendTimer = null;

  window.sendReset = async () => {
    const email = document.getElementById('reset-email').value.trim();
    const btn = document.getElementById('send-btn');
    const errEl = document.getElementById('email-error');
    errEl.style.display = 'none';
    if (!email) { showError(errEl, 'Vui lòng nhập địa chỉ email.'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError(errEl, 'Email không hợp lệ.'); return; }
    btn.disabled = true; btn.textContent = 'Đang gửi...';
    await delay(1500);
    document.getElementById('step-email').style.display = 'none';
    document.getElementById('step-sent').style.display = '';
    document.getElementById('sent-email-display').textContent = email;
    startResendCountdown();
  };

  window.resendReset = () => {
    const btn = document.getElementById('resend-btn');
    btn.disabled = true; btn.textContent = 'Đang gửi lại...';
    setTimeout(() => {
      toastSuccess('Đã gửi lại email đặt lại mật khẩu!');
      btn.textContent = 'Gửi lại email';
      startResendCountdown();
    }, 1500);
  };

  window.submitNewPass = async () => {
    const pass = document.getElementById('new-pass').value;
    const confirm = document.getElementById('confirm-pass').value;
    if (pass.length < 8) { toastError('Mật khẩu phải có ít nhất 8 ký tự.'); return; }
    if (pass !== confirm) { toastError('Mật khẩu xác nhận không khớp.'); return; }
    await delay(1200);
    document.getElementById('step-newpass').style.display = 'none';
    document.getElementById('step-success').style.display = '';
  };

  window.togglePw = (id) => {
    const el = document.getElementById(id);
    if (el) el.type = el.type === 'password' ? 'text' : 'password';
  };

  window.checkStrength = (val) => {
    const bar = document.getElementById('strength-bar');
    const lbl = document.getElementById('strength-label');
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const config = [
      { pct:'0%',   bg:'var(--border)', text:'' },
      { pct:'25%',  bg:'var(--destructive)', text:'Rất yếu' },
      { pct:'50%',  bg:'var(--warning)',    text:'Trung bình' },
      { pct:'75%',  bg:'var(--info)',        text:'Khá mạnh' },
      { pct:'100%', bg:'var(--success)',     text:'Mạnh' },
    ][score];
    if (bar) { bar.style.width = config.pct; bar.style.background = config.bg; }
    if (lbl) lbl.textContent = config.text;
  };

  function startResendCountdown() {
    let secs = 60;
    clearInterval(resendTimer);
    const el = document.getElementById('resend-countdown');
    const btn = document.getElementById('resend-btn');
    if (!btn) return;
    btn.disabled = true;
    if (el) el.textContent = `Có thể gửi lại sau ${secs}s`;
    resendTimer = setInterval(() => {
      secs--;
      if (secs <= 0) { clearInterval(resendTimer); btn.disabled = false; if (el) el.textContent = ''; }
      else if (el) el.textContent = `Có thể gửi lại sau ${secs}s`;
    }, 1000);
  }

  function showError(el, msg) { if (el) { el.textContent = msg; el.style.display = ''; } }
  function delay(ms) { return new Promise(r => setTimeout(r, ms)); }
</script>
@endpush

@section('auth-content')
  <div class="auth-logo">
    <div class="auth-logo-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
    </div>
    <h1 style="font-size:var(--text-2xl);font-weight:700;">VietQuiz</h1>
  </div>

  <!-- Step 1: Enter email -->
  <div class="card fade-in" id="step-email" @if(session('status')) style="display:none;" @endif>
    <div class="card-header" style="text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:.75rem;">🔐</div>
      <h2 class="card-title" style="font-size:var(--text-xl);">Quên mật khẩu?</h2>
      <p class="card-description">Nhập email đăng ký, chúng tôi sẽ gửi link đặt lại mật khẩu cho bạn.</p>
    </div>
    <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
      @error('email')
      <div class="alert alert-danger">
        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ $message }}</span>
      </div>
      @enderror
      <form method="POST" action="{{ route('password.email') }}" id="forgot-form">
        @csrf
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="label label-required">Địa chỉ Email</label>
          <div class="input-with-icon">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <input type="email" class="input has-icon @error('email') input-error @enderror" name="email" id="reset-email" placeholder="ten.ban@example.com" value="{{ old('email') }}" autocomplete="email" required />
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-full" id="send-btn">Gửi link đặt lại mật khẩu</button>
      </form>
      <div class="alert alert-info" style="font-size:var(--text-xs);">
        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Link đặt lại sẽ hết hạn sau <strong>60 phút</strong>.
      </div>
    </div>
    <div class="card-footer" style="justify-content:center;">
      <a href="{{ route('login') }}" class="btn btn-ghost btn-sm gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Quay lại Đăng nhập
      </a>
    </div>
  </div>

  <!-- Step 2: Email sent -->
  <div class="card fade-in" id="step-sent" @if(!session('status')) style="display:none;" @endif style="text-align:center;">
    <div class="card-content" style="padding:2rem;">
      <div style="width:5rem;height:5rem;border-radius:50%;background:color-mix(in srgb,var(--success) 12%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:2.5rem;">✉️</div>
      <h2 style="font-size:var(--text-xl);font-weight:700;margin-bottom:.5rem;">Kiểm tra Email của bạn</h2>
      <p style="color:var(--muted-foreground);margin-bottom:.5rem;font-size:var(--text-sm);">{{ session('status') ?? 'Chúng tôi đã gửi link đặt lại mật khẩu đến email của bạn.' }}</p>
      <form method="POST" action="{{ route('password.email') }}" style="margin-top:1.5rem;">
        @csrf
        <input type="hidden" name="email" value="{{ old('email') }}" />
        <button type="submit" class="btn btn-outline w-full" style="margin-bottom:.75rem;">Gửi lại email</button>
      </form>
      <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Quay lại Đăng nhập</a>
    </div>
  </div>

  <!-- Step 3: New password -->
  <div class="card fade-in" id="step-newpass" style="display:none;">
    <div class="card-header" style="text-align:center;">
      <div style="font-size:2rem;margin-bottom:.5rem;">🔒</div>
      <h2 class="card-title">Tạo mật khẩu mới</h2>
      <p class="card-description">Mật khẩu phải có ít nhất 8 ký tự.</p>
    </div>
    <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
      <div class="form-group">
        <label class="label label-required">Mật khẩu mới</label>
        <div class="input-with-icon">
          <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" class="input has-icon" id="new-pass" placeholder="Mật khẩu mới" oninput="checkStrength(this.value)" />
          <button type="button" class="input-suffix-btn" onclick="togglePw('new-pass')">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div style="margin-top:.5rem;">
          <div class="progress progress-sm"><div class="progress-bar" id="strength-bar" style="width:0%;transition:width .3s,background .3s;"></div></div>
          <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;" id="strength-label"></div>
        </div>
      </div>
      <div class="form-group">
        <label class="label label-required">Xác nhận mật khẩu</label>
        <div class="input-with-icon">
          <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" class="input has-icon" id="confirm-pass" placeholder="Nhập lại mật khẩu" />
        </div>
      </div>
      <button class="btn btn-primary w-full" type="button" onclick="submitNewPass()">Đặt lại mật khẩu</button>
    </div>
  </div>

  <!-- Step 4: Success -->
  <div class="card fade-in" id="step-success" style="display:none;text-align:center;">
    <div class="card-content" style="padding:2rem;">
      <div style="width:5rem;height:5rem;border-radius:50%;background:color-mix(in srgb,var(--success) 12%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" stroke="var(--success)" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <h2 style="font-size:var(--text-xl);font-weight:700;margin-bottom:.5rem;">Đặt lại thành công!</h2>
      <p style="color:var(--muted-foreground);margin-bottom:1.5rem;font-size:var(--text-sm);">Mật khẩu của bạn đã được cập nhật.</p>
      <a href="{{ route('login') }}" class="btn btn-primary w-full">Đăng nhập ngay</a>
    </div>
  </div>
@endsection
