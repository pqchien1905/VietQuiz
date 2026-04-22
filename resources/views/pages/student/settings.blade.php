{{-- Student: settings --}}
@extends('layouts.dashboard', ['role' => 'student'])

@section('content')
  <div class="page-header stagger-children">
        <h1>Cài đặt</h1>
        <p style="color:var(--muted-foreground);">Quản lý tài khoản và tùy chỉnh trải nghiệm của bạn</p>
      </div>

      <div style="display:grid;grid-template-columns:220px 1fr;gap:1.5rem;align-items:start;" class="stagger-children">
        <!-- Vertical tabs -->
        <nav style="display:flex;flex-direction:column;gap:.25rem;" class="card" style="padding:.75rem;">
          <div style="padding:.5rem .75rem;font-size:var(--text-xs);font-weight:600;color:var(--muted-foreground);text-transform:uppercase;letter-spacing:.07em;">Tài khoản</div>
          <div id="settings-tabs"></div>
        </nav>

        <!-- Panels -->
        <div>
          <!-- Profile -->
          <div id="panel-profile" class="card">
            <div class="card-header"><h3 class="card-title">Hồ sơ cá nhân</h3><p class="card-description">Thông tin hiển thị với giáo viên và bạn học</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1.25rem;">
              <!-- Avatar -->
              <div style="display:flex;align-items:center;gap:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border);">
                <div class="avatar avatar-xl" style="background:var(--primary);" id="settings-avatar">HS</div>
                <div>
                  <div style="font-weight:500;margin-bottom:.25rem;">Ảnh đại diện</div>
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.75rem;">JPG, PNG, GIF tối đa 5MB</div>
                  <div style="display:flex;gap:.5rem;">
                    <button class="btn btn-outline btn-sm">Tải ảnh lên</button>
                    <button class="btn btn-ghost btn-sm">Xóa ảnh</button>
                  </div>
                </div>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group"><label class="label label-required">Họ và tên</label><input type="text" class="input" id="s-name" /></div>
                <div class="form-group"><label class="label">Biệt danh</label><input type="text" class="input" placeholder="Tên hiển thị ngắn" /></div>
              </div>
              <div class="form-group"><label class="label label-required">Email</label><input type="email" class="input" id="s-email" /></div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group"><label class="label">Lớp học</label><input type="text" class="input" value="10A" /></div>
                <div class="form-group"><label class="label">Trường học</label><input type="text" class="input" value="THPT Example" /></div>
              </div>
              <div class="form-group"><label class="label">Giới thiệu bản thân</label><textarea class="input" style="min-height:4rem;" placeholder="Viết vài dòng về bản thân..."></textarea></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary" onclick="saveProfile()">Lưu thay đổi</button></div>
          </div>

          <!-- Notifications -->
          <div id="panel-notifs" class="card" style="display:none;">
            <div class="card-header"><h3 class="card-title">Cài đặt Thông báo</h3><p class="card-description">Chọn loại thông báo bạn muốn nhận</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:0;" id="notif-toggles"></div>
            <div class="card-footer"><button class="btn btn-primary" onclick="saveNotifs()">Lưu cài đặt</button></div>
          </div>

          <!-- Privacy / Security -->
          <div id="panel-privacy" class="card" style="display:none;">
            <div class="card-header"><h3 class="card-title">Bảo mật tài khoản</h3><p class="card-description">Quản lý mật khẩu và phiên đăng nhập</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1.25rem;">
              <div class="form-group"><label class="label">Mật khẩu hiện tại</label><input type="password" class="input" placeholder="Nhập mật khẩu hiện tại" /></div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group"><label class="label">Mật khẩu mới</label><input type="password" class="input" placeholder="Ít nhất 8 ký tự" /></div>
                <div class="form-group"><label class="label">Xác nhận mật khẩu</label><input type="password" class="input" placeholder="Nhập lại" /></div>
              </div>
              <div style="padding:1rem;background:var(--muted);border-radius:var(--radius-md);">
                <div style="font-weight:500;margin-bottom:.75rem;font-size:var(--text-sm);">Phiên đăng nhập</div>
                <div id="sessions-list"></div>
              </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary" onclick="savePassword()">Đổi mật khẩu</button></div>
          </div>

          <!-- Appearance -->
          <div id="panel-appear" class="card" style="display:none;">
            <div class="card-header"><h3 class="card-title">Giao diện</h3><p class="card-description">Tùy chỉnh màu sắc và ngôn ngữ</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1.5rem;">
              <div>
                <div style="font-weight:500;margin-bottom:.875rem;font-size:var(--text-sm);">Chủ đề giao diện</div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;" id="theme-options"></div>
              </div>
              <div class="form-group">
                <label class="label">Ngôn ngữ</label>
                <select class="input select"><option selected>Tiếng Việt</option><option>English</option></select>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem;background:var(--muted);border-radius:var(--radius-md);">
                <div><div style="font-weight:500;font-size:var(--text-sm);">Hiệu ứng chuyển động</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Tắt nếu bạn nhạy cảm với chuyển động</div></div>
                <label class="switch"><input type="checkbox" checked /><span class="switch-slider"></span></label>
              </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary" onclick="saveAppear()">Lưu thay đổi</button></div>
          </div>

          <!-- Account -->
          <div id="panel-account" class="card" style="display:none;">
            <div class="card-header"><h3 class="card-title">Tài khoản</h3><p class="card-description">Quản lý dữ liệu và tùy chọn tài khoản</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
              <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem;border:1px solid var(--border);border-radius:var(--radius-md);">
                <div><div style="font-weight:500;">Xuất dữ liệu</div><div style="font-size:var(--text-sm);color:var(--muted-foreground);">Tải về toàn bộ lịch sử bài thi và điểm số</div></div>
                <button class="btn btn-outline btn-sm" onclick="exportData()">Xuất CSV</button>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem;border:1px solid var(--border);border-radius:var(--radius-md);">
                <div><div style="font-weight:500;">Gói hiện tại</div><div style="font-size:var(--text-sm);color:var(--muted-foreground);">Miễn phí — tối đa 5 lớp học</div></div>
                <a href="{{ route('student.vip') }}" class="btn btn-primary btn-sm">Nâng cấp Pro</a>
              </div>
              <div style="padding:1.25rem;background:color-mix(in srgb,var(--destructive) 5%,transparent);border:1px solid color-mix(in srgb,var(--destructive) 20%,transparent);border-radius:var(--radius-md);">
                <div style="font-weight:500;color:var(--destructive);margin-bottom:.5rem;">Vùng nguy hiểm</div>
                <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.875rem;">Xóa tài khoản sẽ xóa vĩnh viễn toàn bộ dữ liệu. Không thể hoàn tác.</p>
                <button class="btn btn-destructive btn-sm" onclick="deleteAccount()">Xóa tài khoản</button>
              </div>
            </div>
          </div>
        </div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var TABS=[{id:'profile',icon:'👤',label:'Hồ sơ cá nhân'},{id:'notifs',icon:'🔔',label:'Thông báo'},{id:'privacy',icon:'🔒',label:'Bảo mật'},{id:'appear',icon:'🎨',label:'Giao diện'},{id:'account',icon:'⚙️',label:'Tài khoản'}];
  document.getElementById('settings-tabs').innerHTML=TABS.map(function(t,i){return '<button class="nav-item '+(i===0?'active':'')+'" id="stab-'+t.id+'" onclick="switchTab(\''+t.id+'\',this)"><span>'+t.icon+'</span><span>'+t.label+'</span></button>';}).join('');
  var NOTIF_OPTS=[{label:'Bài kiểm tra mới',desc:'Khi giáo viên giao bài kiểm tra',on:true},{label:'Bài tập mới',desc:'Khi giáo viên giao bài tập',on:true},{label:'Kết quả đã công bố',desc:'Khi điểm bài thi được cập nhật',on:true},{label:'Nhắc nhở hạn nộp',desc:'1 ngày trước hạn nộp bài',on:false},{label:'Email thông báo',desc:'Nhận thông báo qua email',on:true},{label:'Thông báo đẩy',desc:'Cho phép thông báo trên trình duyệt',on:false}];
  document.getElementById('notif-toggles').innerHTML=NOTIF_OPTS.map(function(n){return '<div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--border);"><div><div style="font-weight:500;font-size:var(--text-sm);">'+n.label+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">'+n.desc+'</div></div><label class="switch"><input type="checkbox" '+(n.on?'checked':'')+' /><span class="switch-slider"></span></label></div>';}).join('');
  var SESSIONS=[{device:'Chrome / Windows',loc:'Hà Nội',time:'Đang hoạt động',current:true},{device:'Safari / iPhone',loc:'Hà Nội',time:'2 ngày trước',current:false}];
  document.getElementById('sessions-list').innerHTML=SESSIONS.map(function(s){var action=s.current?'<span class="badge badge-success">Thiết bị này</span>':'<button class="btn btn-ghost btn-sm" style="color:var(--destructive);">Dăng xuất</button>';return '<div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-top:1px solid var(--border);"><div><div style="font-size:var(--text-sm);font-weight:500;">'+s.device+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">'+s.loc+' · '+s.time+'</div></div>'+action+'</div>';}).join('');
  var THEMES=[{id:'light',icon:'☀️',label:'Sáng'},{id:'dark',icon:'🌙',label:'Tối'},{id:'system',icon:'💻',label:'Theo hệ thống'}];
  document.getElementById('theme-options').innerHTML=THEMES.map(function(t){return '<label style="cursor:pointer;"><input type="radio" name="theme" value="'+t.id+'" style="display:none;" onchange="applyTheme(\''+t.id+'\')" /><div class="theme-option" id="topt-'+t.id+'" style="border:2px solid var(--border);border-radius:var(--radius-md);padding:1rem;text-align:center;transition:border-color var(--transition-fast);"><div style="font-size:1.5rem;margin-bottom:.25rem;">'+t.icon+'</div><div style="font-size:var(--text-sm);font-weight:500;">'+t.label+'</div></div></label>';}).join('');
  var cn=document.cookie.match(/auth_name=([^;]+)/);var name=cn?decodeURIComponent(cn[1]):'Học sinh Demo';
  var el=document.getElementById('s-name');if(el)el.value=name;
  var emailEl=document.getElementById('s-email');if(emailEl)emailEl.value='student@demo.com';
  var avatarEl=document.getElementById('settings-avatar');if(avatarEl)avatarEl.textContent=(name||'HS').split(' ').filter(Boolean).map(function(w){return w[0];}).slice(-2).join('').toUpperCase();
  var savedTheme=localStorage.getItem('vietquiz-theme')||'system';
  var topt=document.getElementById('topt-'+savedTheme);if(topt)topt.style.borderColor='var(--primary)';
  var radio=document.querySelector('input[name="theme"][value="'+savedTheme+'"]');if(radio)radio.checked=true;
  window.switchTab=function(id,el){['profile','notifs','privacy','appear','account'].forEach(function(t){document.getElementById('panel-'+t).style.display=t===id?'':'none';});document.querySelectorAll('[id^="stab-"]').forEach(function(b){b.classList.remove('active');});el.classList.add('active');};
  window.applyTheme=function(theme){var isDark=theme==='dark'||(theme==='system'&&window.matchMedia('(prefers-color-scheme:dark)').matches);if(isDark)document.documentElement.classList.add('dark');else document.documentElement.classList.remove('dark');localStorage.setItem('vietquiz-theme',theme);document.querySelectorAll('.theme-option').forEach(function(o){o.style.borderColor='var(--border)';});document.getElementById('topt-'+theme).style.borderColor='var(--primary)';};
  window.saveProfile=function(){toast('Đã lưu thông tin hồ sơ');};
  window.saveNotifs=function(){toast('Đã lưu cài đặt thông báo');};
  window.savePassword=function(){toast('Đã cập nhật mật khẩu thành công');};
  window.saveAppear=function(){toast('Đã lưu cài đặt giao diện');};
  window.exportData=function(){toast('Đang chuẩn bị xuất dữ liệu...');};
  window.deleteAccount=function(){if(confirm('Xóa tài khoản vĩnh viễn? Không thể hoàn tác!'))toast('Chức năng này cần xác nhận từ email.');};
  function toast(m){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-success';e.innerHTML='<span>✅</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
})();
</script>
@endpush
