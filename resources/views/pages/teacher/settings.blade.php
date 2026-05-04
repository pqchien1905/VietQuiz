{{-- Teacher: settings --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $activeTab = old('settings_tab', session('settings_tab', 'profile'));
  $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
  $initials = collect(explode(' ', trim($user->name)))
    ->filter()
    ->map(fn ($word) => mb_substr($word, 0, 1))
    ->take(2)
    ->implode('');

  $notificationOptions = [
    'notif_email' => ['Email', 'Nhận tóm tắt hoạt động lớp học và cảnh báo quan trọng qua email.'],
    'notif_push' => ['Thông báo trong ứng dụng', 'Hiển thị thông báo mới ở khu vực chuông thông báo.'],
    'notif_submission' => ['Bài nộp mới', 'Báo khi học sinh nộp bài tập hoặc hoàn thành bài kiểm tra.'],
    'notif_deadline' => ['Sắp đến hạn', 'Nhắc các mốc hạn bài tập, bài kiểm tra và lớp học.'],
  ];
@endphp

@push('styles')
<style>
  .settings-layout { display: grid; grid-template-columns: 230px minmax(0, 1fr); gap: 1.5rem; align-items: start; }
  .settings-tabs { padding: .75rem; display: flex; flex-direction: column; gap: .25rem; position: sticky; top: 1rem; }
  .settings-tab { width: 100%; justify-content: flex-start; border: 0; background: transparent; }
  .settings-tab.active { background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--primary); }
  .settings-panel { display: none; }
  .settings-panel.active { display: block; }
  .settings-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
  .settings-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid var(--border); }
  .settings-row:last-child { border-bottom: 0; }
  .settings-avatar { width: 5rem; height: 5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--primary); color: var(--primary-foreground); font-weight: 700; font-size: var(--text-xl); overflow: hidden; flex-shrink: 0; }
  .settings-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .theme-choice { border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem; text-align: left; background: var(--background); color: var(--foreground); cursor: pointer; transition: border-color var(--transition-fast), background-color var(--transition-fast); }
  .theme-choice.active { border-color: var(--primary); background: color-mix(in srgb, var(--primary) 8%, transparent); }
  .danger-box { padding: 1.25rem; border: 1px solid color-mix(in srgb, var(--destructive) 35%, var(--border)); border-radius: var(--radius-md); background: color-mix(in srgb, var(--destructive) 6%, var(--card)); }

  @media (max-width: 900px) {
    .settings-layout { grid-template-columns: 1fr; }
    .settings-tabs { position: static; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 640px) {
    .settings-grid { grid-template-columns: 1fr; }
    .settings-tabs { grid-template-columns: 1fr; }
    .settings-row { align-items: flex-start; }
    .card-footer { justify-content: stretch; }
    .card-footer .btn { width: 100%; }
  }
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div>
      <h1>Cài đặt</h1>
      <p style="color:var(--muted-foreground);">Quản lý hồ sơ giáo viên, thông báo, bảo mật và tùy chọn hiển thị.</p>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;"><span>{{ session('success') }}</span></div>
  @endif

  @if($errors->any() && !$errors->userDeletion->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ $errors->first() }}</span></div>
  @endif

  <div class="settings-layout stagger-children" data-initial-tab="{{ $activeTab }}">
    <nav class="card settings-tabs" aria-label="Cài đặt giáo viên">
      <button type="button" class="btn btn-ghost settings-tab" data-settings-tab="profile">Hồ sơ</button>
      <button type="button" class="btn btn-ghost settings-tab" data-settings-tab="notifications">Thông báo</button>
      <button type="button" class="btn btn-ghost settings-tab" data-settings-tab="security">Bảo mật</button>
      <button type="button" class="btn btn-ghost settings-tab" data-settings-tab="appearance">Giao diện</button>
      <button type="button" class="btn btn-ghost settings-tab" data-settings-tab="account">Tài khoản</button>
    </nav>

    <div style="min-width:0;">
      <section class="card settings-panel" id="settings-panel-profile">
        <form method="POST" action="{{ route('teacher.settings.profile') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="settings_tab" value="profile">
          <div class="card-header">
            <h2 class="card-title">Hồ sơ giáo viên</h2>
            <p class="card-description">Thông tin này được hiển thị trong hồ sơ và các khu vực lớp học.</p>
          </div>
          <div class="card-content" style="display:flex;flex-direction:column;gap:1.25rem;">
            <div style="display:flex;align-items:center;gap:1rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border);">
              <div class="settings-avatar" id="settings-avatar-preview">
                @if($avatarUrl)
                  <img src="{{ $avatarUrl }}" alt="Ảnh đại diện của {{ $user->name }}">
                @else
                  {{ $initials ?: 'GV' }}
                @endif
              </div>
              <div style="min-width:0;">
                <div style="font-weight:600;margin-bottom:.25rem;">Ảnh đại diện</div>
                <div class="form-hint" style="margin-bottom:.75rem;">JPG, PNG hoặc WEBP, tối đa 2MB.</div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                  <label class="btn btn-outline btn-sm" for="avatar-input">Chọn ảnh</label>
                  <input id="avatar-input" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" style="display:none;">
                  @if($user->avatar)
                    <button type="submit" class="btn btn-ghost btn-sm" name="remove_avatar" value="1">Xóa ảnh</button>
                  @endif
                </div>
                @error('avatar')<div class="form-error">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="settings-grid">
              <div class="form-group">
                <label class="label label-required" for="name">Họ và tên</label>
                <input id="name" name="name" type="text" class="input @error('name') input-error @enderror" value="{{ old('name', $user->name) }}" required maxlength="255">
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label class="label label-required" for="email">Email</label>
                <input id="email" name="email" type="email" class="input @error('email') input-error @enderror" value="{{ old('email', $user->email) }}" required maxlength="255">
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label class="label" for="phone">Số điện thoại</label>
                <input id="phone" name="phone" type="tel" class="input @error('phone') input-error @enderror" value="{{ old('phone', $user->phone) }}" maxlength="20" placeholder="090 123 4567">
                @error('phone')<div class="form-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label class="label" for="subject">Môn phụ trách</label>
                <input id="subject" name="subject" type="text" class="input @error('subject') input-error @enderror" value="{{ old('subject', $user->subject) }}" maxlength="100" placeholder="Toán học, Tin học...">
                @error('subject')<div class="form-error">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="card-footer" style="justify-content:flex-end;">
            <button type="submit" class="btn btn-primary">Lưu hồ sơ</button>
          </div>
        </form>
      </section>

      <section class="card settings-panel" id="settings-panel-notifications">
        <form method="POST" action="{{ route('teacher.settings.notifications') }}">
          @csrf
          <input type="hidden" name="settings_tab" value="notifications">
          <div class="card-header">
            <h2 class="card-title">Thông báo</h2>
            <p class="card-description">Chọn những sự kiện bạn muốn theo dõi khi quản lý lớp học.</p>
          </div>
          <div class="card-content">
            @foreach($notificationOptions as $name => [$label, $description])
              <div class="settings-row">
                <div>
                  <div style="font-weight:600;">{{ $label }}</div>
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $description }}</div>
                </div>
                <label class="switch" aria-label="{{ $label }}">
                  <input type="checkbox" name="{{ $name }}" value="1" @checked(session($name, true))>
                  <span class="switch-slider"></span>
                </label>
              </div>
            @endforeach
          </div>
          <div class="card-footer" style="justify-content:flex-end;">
            <button type="submit" class="btn btn-primary">Lưu thông báo</button>
          </div>
        </form>
      </section>

      <section class="card settings-panel" id="settings-panel-security">
        <form method="POST" action="{{ route('teacher.settings.password') }}">
          @csrf
          <input type="hidden" name="settings_tab" value="security">
          <div class="card-header">
            <h2 class="card-title">Bảo mật</h2>
            <p class="card-description">Đổi mật khẩu định kỳ để bảo vệ dữ liệu lớp học và bài kiểm tra.</p>
          </div>
          <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
            <div class="form-group">
              <label class="label label-required" for="current_password">Mật khẩu hiện tại</label>
              <input id="current_password" name="current_password" type="password" class="input @error('current_password') input-error @enderror" autocomplete="current-password" required>
              @error('current_password')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="settings-grid">
              <div class="form-group">
                <label class="label label-required" for="password">Mật khẩu mới</label>
                <input id="password" name="password" type="password" class="input @error('password') input-error @enderror" autocomplete="new-password" required>
                @error('password')<div class="form-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label class="label label-required" for="password_confirmation">Xác nhận mật khẩu mới</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
              </div>
            </div>
          </div>
          <div class="card-footer" style="justify-content:flex-end;">
            <button type="submit" class="btn btn-primary">Cập nhật mật khẩu</button>
          </div>
        </form>
      </section>

      <section class="card settings-panel" id="settings-panel-appearance">
        <div class="card-header">
          <h2 class="card-title">Giao diện</h2>
          <p class="card-description">Các tùy chọn này được lưu trên trình duyệt hiện tại.</p>
        </div>
        <div class="card-content" style="display:flex;flex-direction:column;gap:1.25rem;">
          <div>
            <div style="font-weight:600;margin-bottom:.75rem;">Chủ đề</div>
            <div class="settings-grid" id="theme-options">
              <button type="button" class="theme-choice" data-theme="light">
                <div style="font-weight:700;">Sáng</div>
                <div class="form-hint">Nền sáng, phù hợp khi làm việc ban ngày.</div>
              </button>
              <button type="button" class="theme-choice" data-theme="dark">
                <div style="font-weight:700;">Tối</div>
                <div class="form-hint">Giảm độ chói trong môi trường thiếu sáng.</div>
              </button>
              <button type="button" class="theme-choice" data-theme="system">
                <div style="font-weight:700;">Theo hệ thống</div>
                <div class="form-hint">Tự đổi theo cài đặt của thiết bị.</div>
              </button>
            </div>
          </div>
          <div class="settings-grid">
            <div class="form-group">
              <label class="label" for="settings-date-format">Định dạng ngày</label>
              <select id="settings-date-format" class="input select" data-pref-key="vietquiz-date-format">
                <option value="dd/mm/yyyy">DD/MM/YYYY</option>
                <option value="yyyy-mm-dd">YYYY-MM-DD</option>
                <option value="mm/dd/yyyy">MM/DD/YYYY</option>
              </select>
            </div>
            <div class="form-group">
              <label class="label" for="settings-page-size">Số mục mỗi trang</label>
              <select id="settings-page-size" class="input select" data-pref-key="vietquiz-page-size">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>
          </div>
          <div class="settings-row" style="border:1px solid var(--border);border-radius:var(--radius-md);padding:1rem;">
            <div>
              <div style="font-weight:600;">Giảm hiệu ứng chuyển động</div>
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Hạn chế animation khi bạn cần giao diện ổn định hơn.</div>
            </div>
            <label class="switch" aria-label="Giảm hiệu ứng chuyển động">
              <input type="checkbox" id="reduce-motion-toggle">
              <span class="switch-slider"></span>
            </label>
          </div>
        </div>
        <div class="card-footer" style="justify-content:flex-end;">
          <button type="button" class="btn btn-primary" id="save-appearance-btn">Lưu giao diện</button>
        </div>
      </section>

      <section class="card settings-panel" id="settings-panel-account">
        <div class="card-header">
          <h2 class="card-title">Tài khoản</h2>
          <p class="card-description">Quản lý trạng thái tài khoản giáo viên và các thao tác nhạy cảm.</p>
        </div>
        <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="settings-row" style="border:1px solid var(--border);border-radius:var(--radius-md);padding:1rem;">
            <div>
              <div style="font-weight:600;">Chuyển sang màn học sinh</div>
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Dùng màn học sinh trên chính tài khoản hiện tại để làm bài hoặc xem trải nghiệm học sinh.</div>
            </div>
            <a class="btn btn-outline btn-sm" href="{{ URL::signedRoute('switch.to.student') }}">Chuyển vai trò</a>
          </div>

          <div class="danger-box">
            <div style="font-weight:700;color:var(--destructive);margin-bottom:.5rem;">Xóa tài khoản</div>
            <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1rem;">
              Tài khoản sẽ bị đưa vào trạng thái đã xóa và bạn sẽ đăng xuất ngay sau khi xác nhận mật khẩu.
            </p>
            <form method="POST" action="{{ route('profile.destroy') }}" id="delete-account-form" style="display:flex;gap:.75rem;align-items:flex-start;flex-wrap:wrap;">
              @csrf
              @method('DELETE')
              <input type="hidden" name="settings_tab" value="account">
              <div class="form-group" style="flex:1 1 16rem;">
                <label class="label label-required" for="delete-password">Mật khẩu xác nhận</label>
                <input id="delete-password" name="password" type="password" class="input {{ $errors->userDeletion->has('password') ? 'input-error' : '' }}" autocomplete="current-password">
                @if($errors->userDeletion->has('password'))
                  <div class="form-error">{{ $errors->userDeletion->first('password') }}</div>
                @endif
              </div>
              <button type="submit" class="btn btn-destructive" style="margin-top:1.65rem;">Xóa tài khoản</button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </div>

  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var root = document.querySelector('.settings-layout');
  var initialTab = root ? root.getAttribute('data-initial-tab') : 'profile';

  function showTab(tab) {
    document.querySelectorAll('.settings-panel').forEach(function(panel) {
      panel.classList.toggle('active', panel.id === 'settings-panel-' + tab);
    });
    document.querySelectorAll('[data-settings-tab]').forEach(function(button) {
      button.classList.toggle('active', button.getAttribute('data-settings-tab') === tab);
    });
  }

  document.querySelectorAll('[data-settings-tab]').forEach(function(button) {
    button.addEventListener('click', function() {
      showTab(button.getAttribute('data-settings-tab'));
    });
  });
  showTab(initialTab || 'profile');

  var avatarInput = document.getElementById('avatar-input');
  avatarInput && avatarInput.addEventListener('change', function() {
    var file = avatarInput.files && avatarInput.files[0];
    var preview = document.getElementById('settings-avatar-preview');
    if (!file || !preview) return;

    var reader = new FileReader();
    reader.onload = function(event) {
      preview.innerHTML = '<img src="' + event.target.result + '" alt="Ảnh đại diện mới">';
    };
    reader.readAsDataURL(file);
  });

  function applyThemePreference(theme) {
    var resolved = theme === 'system'
      ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : theme;
    document.documentElement.classList.toggle('dark', resolved === 'dark');
    document.querySelectorAll('.theme-choice').forEach(function(choice) {
      choice.classList.toggle('active', choice.getAttribute('data-theme') === theme);
    });
  }

  var savedTheme = localStorage.getItem('vietquiz-theme') || 'system';
  applyThemePreference(savedTheme);
  document.querySelectorAll('.theme-choice').forEach(function(choice) {
    choice.addEventListener('click', function() {
      var theme = choice.getAttribute('data-theme');
      localStorage.setItem('vietquiz-theme', theme);
      applyThemePreference(theme);
    });
  });

  document.querySelectorAll('[data-pref-key]').forEach(function(input) {
    var saved = localStorage.getItem(input.getAttribute('data-pref-key'));
    if (saved) input.value = saved;
  });

  var reduceMotion = document.getElementById('reduce-motion-toggle');
  if (reduceMotion) {
    reduceMotion.checked = localStorage.getItem('vietquiz-reduce-motion') === '1';
    reduceMotion.addEventListener('change', function() {
      localStorage.setItem('vietquiz-reduce-motion', reduceMotion.checked ? '1' : '0');
    });
  }

  var saveAppearance = document.getElementById('save-appearance-btn');
  saveAppearance && saveAppearance.addEventListener('click', function() {
    document.querySelectorAll('[data-pref-key]').forEach(function(input) {
      localStorage.setItem(input.getAttribute('data-pref-key'), input.value);
    });
    toast('Đã lưu cài đặt giao diện.', 'success');
  });

  var deleteForm = document.getElementById('delete-account-form');
  deleteForm && deleteForm.addEventListener('submit', function(event) {
    if (!window.confirm('Bạn chắc chắn muốn xóa tài khoản?')) {
      event.preventDefault();
    }
  });

  @if(session('success'))
    toast(@json(session('success')), 'success');
  @endif

  function toast(message, type) {
    var container = document.getElementById('toast-container');
    if (!container) return;

    var el = document.createElement('div');
    el.className = 'toast toast-' + (type || 'success');
    el.innerHTML = '<div class="toast-content"><div class="toast-title">' + message + '</div></div>';
    container.appendChild(el);
    setTimeout(function() {
      el.classList.add('removing');
      setTimeout(function() { el.remove(); }, 260);
    }, 3000);
  }
})();
</script>
@endpush
