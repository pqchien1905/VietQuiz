{{-- Student: settings --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $activeTab = in_array($activeTab ?? 'profile', ['profile', 'notifications', 'security', 'appearance', 'account'], true)
    ? $activeTab
    : 'profile';
  $avatarUrl = $user->avatar ? asset('storage/' . $user->avatar) : null;
  $initials = collect(explode(' ', $user->name))->filter()->map(fn($word) => mb_substr($word, 0, 1))->take(2)->implode('') ?: 'HS';
  $isVip = $user->vipSubscriptionForAudience('student')->first()?->is_active;
@endphp

@push('styles')
<style>
  .settings-layout{display:grid;grid-template-columns:240px minmax(0,1fr);gap:1.5rem;align-items:start}
  .settings-nav{display:flex;flex-direction:column;gap:.25rem;padding:.75rem}
  .settings-tab{justify-content:flex-start;width:100%;border:0;background:transparent;text-align:left}
  .settings-panel{display:none}
  .settings-panel.active{display:block}
  .settings-avatar{width:5rem;height:5rem;border-radius:999px;display:flex;align-items:center;justify-content:center;background:var(--primary);color:var(--primary-foreground);font-weight:800;font-size:var(--text-xl);overflow:hidden;flex-shrink:0}
  .settings-avatar img{width:100%;height:100%;object-fit:cover}
  .settings-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
  .setting-toggle{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 0;border-bottom:1px solid var(--border)}
  .setting-toggle:last-child{border-bottom:0}
  .theme-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}
  .theme-option{border:2px solid var(--border);border-radius:var(--radius-md);padding:1rem;text-align:center;cursor:pointer;transition:border-color var(--transition-fast),background var(--transition-fast)}
  .theme-option.active{border-color:var(--primary);background:color-mix(in srgb,var(--primary) 6%,transparent)}
  .account-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem;border:1px solid var(--border);border-radius:var(--radius-md)}
  .form-error{color:var(--destructive);font-size:var(--text-xs);margin-top:.35rem}
  @media (max-width:900px){.settings-layout{grid-template-columns:1fr}.settings-nav{flex-direction:row;flex-wrap:wrap}.settings-tab{width:auto}.settings-grid-2,.theme-options{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div>
      <h1>Cài đặt</h1>
      <p style="color:var(--muted-foreground);">Quản lý tài khoản, bảo mật, thông báo và trải nghiệm học tập của bạn.</p>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;"><span>{{ session('success') }}</span></div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ $errors->first() }}</span></div>
  @endif

  <div class="settings-layout stagger-children" id="settings-root" data-active-tab="{{ $activeTab }}">
    <aside class="card settings-nav" aria-label="Cài đặt tài khoản">
      <div style="padding:.5rem .75rem;font-size:var(--text-xs);font-weight:700;color:var(--muted-foreground);text-transform:uppercase;letter-spacing:.07em;">Tài khoản</div>
      <button type="button" class="nav-item settings-tab" data-settings-tab="profile"><span>👤</span><span>Hồ sơ</span></button>
      <button type="button" class="nav-item settings-tab" data-settings-tab="notifications"><span>🔔</span><span>Thông báo</span></button>
      <button type="button" class="nav-item settings-tab" data-settings-tab="security"><span>🔒</span><span>Bảo mật</span></button>
      <button type="button" class="nav-item settings-tab" data-settings-tab="appearance"><span>🎨</span><span>Giao diện</span></button>
      <button type="button" class="nav-item settings-tab" data-settings-tab="account"><span>⚙️</span><span>Tài khoản</span></button>
    </aside>

    <div>
      <section class="card settings-panel" id="settings-panel-profile">
        <form method="POST" action="{{ route('student.settings.profile') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="settings_tab" value="profile">
          <div class="card-header">
            <h3 class="card-title">Hồ sơ cá nhân</h3>
            <p class="card-description">Thông tin này được dùng trong lớp học, bài nộp và phần trao đổi với giáo viên.</p>
          </div>
          <div class="card-content" style="display:flex;flex-direction:column;gap:1.25rem;">
            <div style="display:flex;align-items:center;gap:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border);flex-wrap:wrap;">
              <div class="settings-avatar" id="settings-avatar-preview">
                @if($avatarUrl)
                  <img src="{{ $avatarUrl }}" alt="{{ $user->name }}">
                @else
                  {{ $initials }}
                @endif
              </div>
              <div>
                <div style="font-weight:700;margin-bottom:.25rem;">Ảnh đại diện</div>
                <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.75rem;">JPG, PNG hoặc WebP, tối đa 2MB.</div>
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

            <div class="settings-grid-2">
              <div class="form-group">
                <label class="label label-required" for="name">Họ và tên</label>
                <input id="name" name="name" type="text" class="input @error('name') input-error @enderror" value="{{ old('name', $user->name) }}" required maxlength="255">
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label class="label" for="phone">Số điện thoại</label>
                <input id="phone" name="phone" type="tel" class="input @error('phone') input-error @enderror" value="{{ old('phone', $user->phone) }}" maxlength="20" placeholder="090 123 4567">
                @error('phone')<div class="form-error">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="form-group">
              <label class="label label-required" for="email">Email</label>
              <input id="email" name="email" type="email" class="input @error('email') input-error @enderror" value="{{ old('email', $user->email) }}" required maxlength="255">
              @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div style="padding:1rem;background:var(--muted);border-radius:var(--radius-md);font-size:var(--text-sm);color:var(--muted-foreground);">
              Vai trò tài khoản: <strong style="color:var(--foreground);">Học sinh</strong>. Ngày tạo tài khoản: {{ $user->created_at?->format('d/m/Y') }}.
            </div>
          </div>
          <div class="card-footer"><button class="btn btn-primary" type="submit">Lưu hồ sơ</button></div>
        </form>
      </section>

      <section class="card settings-panel" id="settings-panel-notifications">
        <form method="POST" action="{{ route('student.settings.notifications') }}">
          @csrf
          <input type="hidden" name="settings_tab" value="notifications">
          <div class="card-header">
            <h3 class="card-title">Cài đặt thông báo</h3>
            <p class="card-description">Chọn loại thông báo bạn muốn ưu tiên nhận trong phiên làm việc này.</p>
          </div>
          <div class="card-content">
            @foreach([
              'notif_quiz' => ['Bài kiểm tra mới', 'Khi giáo viên giao hoặc cập nhật quiz.'],
              'notif_assignment' => ['Bài tập mới', 'Khi giáo viên giao bài tập hoặc nhắc hạn nộp.'],
              'notif_grade' => ['Điểm số và nhận xét', 'Khi giáo viên công bố điểm hoặc nhận xét bài làm.'],
              'notif_email' => ['Email thông báo', 'Nhận bản sao thông báo quan trọng qua email.'],
              'notif_push' => ['Thông báo trình duyệt', 'Cho phép hiển thị thông báo đẩy trên thiết bị này.'],
            ] as $key => [$label, $desc])
              <div class="setting-toggle">
                <div>
                  <div style="font-weight:700;font-size:var(--text-sm);">{{ $label }}</div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $desc }}</div>
                </div>
                <label class="switch">
                  <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $notificationSettings[$key] ?? false))>
                  <span class="switch-slider"></span>
                </label>
              </div>
            @endforeach
          </div>
          <div class="card-footer"><button class="btn btn-primary" type="submit">Lưu thông báo</button></div>
        </form>
      </section>

      <section class="card settings-panel" id="settings-panel-security">
        <form method="POST" action="{{ route('student.settings.password') }}">
          @csrf
          <input type="hidden" name="settings_tab" value="security">
          <div class="card-header">
            <h3 class="card-title">Bảo mật tài khoản</h3>
            <p class="card-description">Đổi mật khẩu và kiểm tra phiên đăng nhập hiện tại.</p>
          </div>
          <div class="card-content" style="display:flex;flex-direction:column;gap:1.25rem;">
            <div class="form-group">
              <label class="label label-required" for="current_password">Mật khẩu hiện tại</label>
              <input id="current_password" name="current_password" type="password" class="input @error('current_password') input-error @enderror" autocomplete="current-password" required>
              @error('current_password')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="settings-grid-2">
              <div class="form-group">
                <label class="label label-required" for="password">Mật khẩu mới</label>
                <input id="password" name="password" type="password" class="input @error('password') input-error @enderror" autocomplete="new-password" required>
                @error('password')<div class="form-error">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label class="label label-required" for="password_confirmation">Xác nhận mật khẩu</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
              </div>
            </div>

            <div style="padding:1rem;background:var(--muted);border-radius:var(--radius-md);">
              <div style="font-weight:700;margin-bottom:.75rem;font-size:var(--text-sm);">Phiên đăng nhập hiện tại</div>
              <div class="account-row" style="background:var(--card);">
                <div>
                  <div style="font-size:var(--text-sm);font-weight:700;">{{ \Illuminate\Support\Str::limit($currentSession['device'], 70) }}</div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">IP {{ $currentSession['ip'] }} · {{ $currentSession['last_active'] }}</div>
                </div>
                <span class="badge badge-success">Thiết bị này</span>
              </div>
            </div>
          </div>
          <div class="card-footer"><button class="btn btn-primary" type="submit">Đổi mật khẩu</button></div>
        </form>
      </section>

      <section class="card settings-panel" id="settings-panel-appearance">
        <div class="card-header">
          <h3 class="card-title">Giao diện</h3>
          <p class="card-description">Tùy chỉnh giao diện trên trình duyệt hiện tại.</p>
        </div>
        <div class="card-content" style="display:flex;flex-direction:column;gap:1.5rem;">
          <div>
            <div style="font-weight:700;margin-bottom:.875rem;font-size:var(--text-sm);">Chủ đề giao diện</div>
            <div class="theme-options">
              <button type="button" class="theme-option" data-theme-option="light"><div style="font-size:1.5rem;margin-bottom:.25rem;">☀️</div><div style="font-size:var(--text-sm);font-weight:700;">Sáng</div></button>
              <button type="button" class="theme-option" data-theme-option="dark"><div style="font-size:1.5rem;margin-bottom:.25rem;">🌙</div><div style="font-size:var(--text-sm);font-weight:700;">Tối</div></button>
              <button type="button" class="theme-option" data-theme-option="system"><div style="font-size:1.5rem;margin-bottom:.25rem;">💻</div><div style="font-size:var(--text-sm);font-weight:700;">Theo hệ thống</div></button>
            </div>
          </div>
          <div class="form-group">
            <label class="label">Ngôn ngữ</label>
            <select class="input select" disabled>
              <option selected>Tiếng Việt</option>
            </select>
            <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.35rem;">Dự án hiện đang tối ưu nội dung tiếng Việt.</div>
          </div>
          <div class="setting-toggle" style="padding:1rem;background:var(--muted);border-radius:var(--radius-md);border-bottom:0;">
            <div>
              <div style="font-weight:700;font-size:var(--text-sm);">Hiệu ứng chuyển động</div>
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Lưu trên trình duyệt hiện tại.</div>
            </div>
            <label class="switch">
              <input type="checkbox" id="motion-toggle" checked>
              <span class="switch-slider"></span>
            </label>
          </div>
        </div>
      </section>

      <section class="card settings-panel" id="settings-panel-account">
        <div class="card-header">
          <h3 class="card-title">Tài khoản</h3>
          <p class="card-description">Dữ liệu học tập, gói hiện tại và vùng nguy hiểm.</p>
        </div>
        <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="account-row">
            <div>
              <div style="font-weight:700;">Xuất dữ liệu học tập</div>
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Tải JSON gồm hồ sơ, lớp, khóa học, quiz, bài nộp và điểm số.</div>
            </div>
            <a href="{{ route('student.settings.export') }}" class="btn btn-outline btn-sm">Tải JSON</a>
          </div>

          <div class="account-row">
            <div>
              <div style="font-weight:700;">Gói hiện tại</div>
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $isVip ? 'Bỏ quảng cáo khi học đang hoạt động.' : 'Miễn phí — có thể hiển thị quảng cáo khi học.' }}</div>
            </div>
            <a href="{{ route('student.vip') }}" class="btn btn-primary btn-sm">{{ $isVip ? 'Quản lý gói' : 'Bỏ quảng cáo' }}</a>
          </div>

          <div style="padding:1.25rem;background:color-mix(in srgb,var(--destructive) 5%,transparent);border:1px solid color-mix(in srgb,var(--destructive) 20%,transparent);border-radius:var(--radius-md);">
            <div style="font-weight:700;color:var(--destructive);margin-bottom:.5rem;">Vùng nguy hiểm</div>
            <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.875rem;">Xóa tài khoản sẽ đăng xuất bạn và đưa tài khoản vào trạng thái đã xóa. Cần nhập mật khẩu hiện tại để xác nhận.</p>
            <form method="POST" action="{{ route('profile.destroy') }}" data-confirm="Xóa tài khoản của bạn? Hành động này rất nghiêm trọng." data-confirm-ok="Xóa tài khoản" style="display:flex;gap:.75rem;align-items:flex-start;flex-wrap:wrap;">
              @csrf
              @method('DELETE')
              <input type="password" name="password" class="input @error('password', 'userDeletion') input-error @enderror" placeholder="Mật khẩu hiện tại" style="max-width:260px;" required>
              <button class="btn btn-destructive btn-sm" type="submit">Xóa tài khoản</button>
              @error('password', 'userDeletion')<div class="form-error" style="width:100%;">{{ $message }}</div>@enderror
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
  var root = document.getElementById('settings-root');
  var activeTab = root ? root.dataset.activeTab : 'profile';
  var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-settings-tab]'));
  var panels = Array.prototype.slice.call(document.querySelectorAll('.settings-panel'));

  function showTab(tab) {
    tabs.forEach(function(button) {
      button.classList.toggle('active', button.dataset.settingsTab === tab);
    });
    panels.forEach(function(panel) {
      panel.classList.toggle('active', panel.id === 'settings-panel-' + tab);
    });
  }

  tabs.forEach(function(button) {
    button.addEventListener('click', function() {
      showTab(button.dataset.settingsTab);
    });
  });

  showTab(activeTab);

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

  function applyTheme(theme) {
    var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', isDark);
    localStorage.setItem('vietquiz-theme', theme);
    document.querySelectorAll('[data-theme-option]').forEach(function(button) {
      button.classList.toggle('active', button.dataset.themeOption === theme);
    });
  }

  var savedTheme = localStorage.getItem('vietquiz-theme') || 'system';
  document.querySelectorAll('[data-theme-option]').forEach(function(button) {
    button.addEventListener('click', function() {
      applyTheme(button.dataset.themeOption);
    });
  });
  applyTheme(savedTheme);

  var motionToggle = document.getElementById('motion-toggle');
  var motion = localStorage.getItem('vietquiz-motion');
  if (motionToggle) {
    motionToggle.checked = motion !== 'reduced';
    motionToggle.addEventListener('change', function() {
      localStorage.setItem('vietquiz-motion', motionToggle.checked ? 'full' : 'reduced');
      document.body.classList.toggle('reduce-motion', !motionToggle.checked);
    });
  }
})();
</script>
@endpush
