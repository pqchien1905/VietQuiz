{{-- Register Page --}}
@extends('layouts.auth')

@php($selectedRole = request('role', 'teacher'))

@push('scripts')
<script>
  // Theme is handled by the main layout

  let activeRole = '{{ $selectedRole }}';

  window.selectRole = (role) => {
    activeRole = role;
    document.getElementById('tab-teacher').classList.toggle('active', role === 'teacher');
    document.getElementById('tab-student').classList.toggle('active', role === 'student');
    document.getElementById('selected-role').value = role;
    document.querySelectorAll('.teacher-only').forEach(el => el.style.display = role === 'teacher' ? '' : 'none');
  };

  window.checkStrength = (pwd) => {
    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    const colors = ['', '#ef4444', '#f59e0b', '#22c55e', '#22c55e'];
    const labels = ['', 'Yếu', 'Trung bình', 'Tốt', 'Mạnh'];
    const bar = document.getElementById('pw-bar');
    const label = document.getElementById('pw-label');
    bar.style.width = score * 25 + '%';
    bar.style.background = colors[score];
    label.textContent = score ? 'Độ mạnh: ' + labels[score] : 'Độ mạnh mật khẩu';
    label.style.color = colors[score] || 'var(--muted-foreground)';
  };

  document.getElementById('register-form')?.addEventListener('submit', (e) => {
    const btn = document.getElementById('register-btn');
    btn.disabled = true;
    btn.classList.add('loading');
    btn.textContent = 'Đang tạo tài khoản...';
  });

  @if($selectedRole === 'student')
    selectRole('student');
  @endif
</script>
@endpush

@section('auth-content')
  <div style="text-align:center;margin-bottom:2rem;">
    <div style="width:4rem;height:4rem;border-radius:50%;background:color-mix(in srgb,var(--primary) 12%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--primary);">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
    </div>
    <h1 style="font-size:var(--text-3xl);font-weight:800;">Tạo tài khoản của bạn</h1>
    <p style="color:var(--muted-foreground);font-size:var(--text-sm);">Tham gia hàng nghìn nhà giáo dục và học sinh</p>
  </div>

  <div class="card shadow-xl" style="border-width:2px;">
    <div class="card-header">
      <h2 class="card-title" style="font-size:var(--text-xl);">Đăng ký</h2>
      <p class="card-description">Chọn vai trò và điền thông tin chi tiết</p>
    </div>
    <div class="card-content">
      <div class="form-group" style="margin-bottom:1.25rem;">
        <label class="label">Tôi là...</label>
        <div class="role-tabs">
          <button class="role-tab {{ $selectedRole !== 'student' ? 'active' : '' }}" id="tab-teacher" type="button" onclick="selectRole('teacher')">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            Giáo viên
          </button>
          <button class="role-tab {{ $selectedRole === 'student' ? 'active' : '' }}" id="tab-student" type="button" onclick="selectRole('student')">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Học sinh
          </button>
        </div>
        <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.5rem;text-align:center;">
          @if($selectedRole === 'student')
            Muốn đăng ký làm giáo viên?
            <a href="{{ route('register', ['role' => 'teacher']) }}" style="color:var(--primary);font-weight:600;">Đăng ký làm giáo viên</a>
          @else
            Muốn đăng ký làm học sinh?
            <a href="{{ route('register', ['role' => 'student']) }}" style="color:var(--primary);font-weight:600;">Đăng ký làm học sinh</a>
          @endif
        </p>
      </div>

      <div class="alert alert-danger" id="reg-error" style="display:none;margin-bottom:1rem;"></div>

      <form method="POST" action="{{ route('register') }}" id="register-form" novalidate>
        @csrf
        <input type="hidden" name="role" id="selected-role" value="{{ $selectedRole }}" />
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
          <div class="form-group">
            <label class="label label-required">Họ và Tên</label>
            <input type="text" id="name" name="name" class="input @error('name') input-error @enderror" placeholder="Nguyễn Văn A" value="{{ old('name') }}" autocomplete="name" required />
            @error('name')
            <p class="form-error" style="display:flex;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>{{ $message }}</span></p>
            @enderror
          </div>
          <div class="form-group">
            <label class="label">Số Điện thoại</label>
            <input type="tel" id="phone" name="phone" class="input" placeholder="090 123 4567" value="{{ old('phone') }}" />
          </div>
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="label label-required">Địa chỉ Email</label>
          <div class="input-wrapper">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <input type="email" id="email" name="email" class="input @error('email') input-error @enderror" placeholder="ten.ban@example.com" value="{{ old('email') }}" required />
          </div>
          @error('email')
          <p class="form-error" style="display:flex;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>{{ $message }}</span></p>
          @enderror
        </div>
        <div class="form-group teacher-only" style="margin-bottom:1rem; {{ $selectedRole === 'student' ? 'display:none;' : '' }}">
          <label class="label">Cơ quan / Trường học</label>
          <input type="text" id="institution" name="institution" class="input" placeholder="Tên Trường / Tổ chức" />
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
          <div class="form-group">
            <label class="label label-required">Mật khẩu</label>
            <div class="input-wrapper">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" id="password" name="password" class="input @error('password') input-error @enderror" placeholder="Tối thiểu 8 ký tự" oninput="checkStrength(this.value)" required minlength="8" />
            </div>
            <div class="pw-strength"><div class="pw-strength-bar" id="pw-bar" style="width:0%;"></div></div>
            <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;" id="pw-label">Độ mạnh mật khẩu</div>
            @error('password')
            <p class="form-error" style="display:flex;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>{{ $message }}</span></p>
            @enderror
          </div>
          <div class="form-group">
            <label class="label label-required">Xác nhận Mật khẩu</label>
            <div class="input-wrapper">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" id="confirm-password" name="password_confirmation" class="input" placeholder="Nhập lại mật khẩu" required />
            </div>
          </div>
        </div>
        <div class="checkbox-wrapper" style="margin-bottom:1.25rem;">
          <input type="checkbox" id="terms" name="terms" required />
          <label for="terms" style="font-size:var(--text-sm);font-weight:400;">Tôi đồng ý với <a href="#">Điều khoản Dịch vụ</a> và <a href="#">Chính sách Bảo mật</a></label>
        </div>
        @error('terms')
        <p class="form-error" style="display:flex;margin-bottom:1rem;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>{{ $message }}</span></p>
        @enderror
        <button type="submit" class="btn btn-primary w-full btn-lg" id="register-btn">Tạo tài khoản</button>
      </form>

      <p style="text-align:center;margin-top:1rem;font-size:var(--text-sm);color:var(--muted-foreground);">
        Đã có tài khoản? <a href="{{ route('login') }}" style="font-weight:600;color:var(--primary);">Đăng nhập</a>
      </p>
    </div>
  </div>

  <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-top:1.25rem;font-size:var(--text-xs);color:var(--muted-foreground);">
    🔒 Dữ liệu của bạn được bảo mật với mã hóa AES-256
  </div>
@endsection
