{{-- Teacher: settings --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@section('content')
  <div class="page-header stagger-children">
        <h1>Cài đặt</h1>
        <p style="color:var(--muted-foreground);">Quản lý tùy chọn tài khoản và cài đặt ứng dụng</p>
      </div>

      <div style="display:grid;grid-template-columns:200px 1fr;gap:1.5rem;" class="stagger-children">
        <!-- Tabs sidebar -->
        <div style="display:flex;flex-direction:column;gap:0.25rem;">
          <button class="nav-item active" onclick="showTab('profile',this)">Hồ sơ</button>
          <button class="nav-item" onclick="showTab('notifications',this)">Thông báo</button>
          <button class="nav-item" onclick="showTab('security',this)">Bảo mật</button>
          <button class="nav-item" onclick="showTab('appearance',this)">Giao diện</button>
          <button class="nav-item" onclick="showTab('account',this)">Tài khoản</button>
        </div>

        <!-- Content -->
        <div>
          <!-- Profile tab -->
          <div class="card" id="tab-profile">
            <div class="card-header"><h3 class="card-title">Hồ sơ</h3><p class="card-description">Cập nhật thông tin cá nhân và tiểu sử của bạn</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
              <div style="display:flex;align-items:center;gap:1rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
                <div class="avatar avatar-2xl">TD</div>
                <div>
                  <button class="btn btn-outline btn-sm">Tải Ảnh Mới</button>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">JPG, PNG tối đa 2MB</div>
                </div>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group"><label class="label">Họ và Tên</label><input type="text" class="input" value="Giáo viên Demo" /></div>
                <div class="form-group"><label class="label">Email</label><input type="email" class="input" value="teacher@demo.com" /></div>
                <div class="form-group"><label class="label">Số Điện thoại</label><input type="tel" class="input" value="090 123 4567" /></div>
                <div class="form-group"><label class="label">Cơ quan</label><input type="text" class="input" value="Trường THPT Example" /></div>
              </div>
              <div class="form-group"><label class="label">Tiểu sử</label><textarea class="input" style="min-height:5rem;">Giáo viên Toán với 10 năm kinh nghiệm...</textarea></div>
              <div style="display:flex;justify-content:flex-end;"><button class="btn btn-primary" onclick="saveProfile()">Lưu thay đổi</button></div>
            </div>
          </div>

          <!-- Notifications tab -->
          <div class="card" id="tab-notifications" style="display:none;">
            <div class="card-header"><h3 class="card-title">Thông báo</h3><p class="card-description">Kiểm soát thông báo nào bạn muốn nhận</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1.25rem;"></div>
          </div>

          <!-- Security tab -->
          <div class="card" id="tab-security" style="display:none;">
            <div class="card-header"><h3 class="card-title">Bảo mật</h3><p class="card-description">Quản lý mật khẩu và bảo mật tài khoản</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
              <div class="form-group"><label class="label">Mật khẩu Hiện tại</label><input type="password" class="input" placeholder="••••••••" /></div>
              <div class="form-group"><label class="label">Mật khẩu Mới</label><input type="password" class="input" placeholder="••••••••" /></div>
              <div class="form-group"><label class="label">Xác nhận Mật khẩu Mới</label><input type="password" class="input" placeholder="••••••••" /></div>
              <div style="display:flex;justify-content:flex-end;"><button class="btn btn-primary" onclick="savePassword()">Cập nhật Mật khẩu</button></div>
            </div>
          </div>

          <!-- Appearance tab -->
          <div class="card" id="tab-appearance" style="display:none;">
            <div class="card-header"><h3 class="card-title">Giao diện</h3><p class="card-description">Tùy chỉnh giao diện ứng dụng</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1.5rem;">
              <div>
                <div style="font-weight:500;margin-bottom:0.75rem;">Chủ đề</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;">
                  <button class="btn btn-outline" id="theme-light-btn" onclick="setThemeAndMark('light')" style="flex-direction:column;height:5rem;gap:0.25rem;">
                    ☀️<span>Sáng</span>
                  </button>
                  <button class="btn btn-outline" id="theme-dark-btn" onclick="setThemeAndMark('dark')" style="flex-direction:column;height:5rem;gap:0.25rem;">
                    🌙<span>Tối</span>
                  </button>
                  <button class="btn btn-outline" id="theme-sys-btn" onclick="setThemeAndMark('system')" style="flex-direction:column;height:5rem;gap:0.25rem;">
                    💻<span>Hệ thống</span>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label class="label">Định dạng Ngày</label>
                <select class="input select"><option>DD/MM/YYYY</option><option>MM/DD/YYYY</option><option>YYYY-MM-DD</option></select>
              </div>
              <div class="form-group">
                <label class="label">Số mục trên mỗi trang</label>
                <select class="input select"><option>10</option><option>25</option><option>50</option></select>
              </div>
            </div>
          </div>

          <!-- Account tab -->
          <div class="card" id="tab-account" style="display:none;">
            <div class="card-header"><h3 class="card-title">Tài khoản</h3></div>
            <div class="card-content">
              <div style="padding:1.5rem;border:2px dashed var(--destructive);border-radius:var(--radius-md);">
                <h4 style="color:var(--destructive);margin-bottom:0.5rem;">Khu vực Nguy hiểm</h4>
                <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1rem;">Xóa tài khoản của bạn và tất cả dữ liệu liên quan. Hành động này không thể hoàn tác.</p>
                <button class="btn btn-destructive btn-sm" onclick="confirmDelete()">Xóa Tài khoản</button>
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
var NOTIF_OPT=[
  {id:'email',label:'Thông báo Email',desc:'Nhận thông báo qua email'},
  {id:'push',label:'Thông báo đẩy',desc:'Nhận thông báo trong trình duyệt'},
  {id:'new-submission',label:'Bài nộp mới',desc:'Khi học sinh nộp bài kiểm tra'},
  {id:'deadline',label:'Sắp đến hạn',desc:'Nhắc nhở 24 giờ trước kỳ thi'},
  {id:'grade',label:'Điểm số',desc:'Khi bài của bạn được chấm điểm'}
];
// Render notification toggles
var nc=document.getElementById('tab-notifications');
if(nc){var nBody=nc.querySelector('.card-content');if(nBody){nBody.innerHTML=NOTIF_OPT.map(function(n){return '<div class="flex items-center justify-between"><div><div style="font-weight:500;">'+n.label+'</div><div style="font-size:var(--text-sm);color:var(--muted-foreground);">'+n.desc+'</div></div><label class="switch"><input type="checkbox" checked /><span class="switch-slider"></span></label></div>';}).join('')+'<div style="display:flex;justify-content:flex-end;margin-top:1rem;"><button class="btn btn-primary" onclick="saveNotifications()">Lưu thay đổi</button></div>';}}

// Highlight current theme
var saved=localStorage.getItem('vietquiz-theme')||'system';
['light','dark','system'].forEach(function(t){var b=document.getElementById('theme-'+t+'-btn');if(b&&t===saved){b.classList.add('btn-primary');b.classList.remove('btn-outline');}});

window.showTab=function(tab,el){
  ['profile','notifications','security','appearance','account'].forEach(function(t){var d=document.getElementById('tab-'+t);if(d)d.style.display='none';});
  var target=document.getElementById('tab-'+tab);if(target)target.style.display='';
  // Only toggle sidebar-style nav items within the settings tabs panel
  if(el){var sibs=el.parentElement.querySelectorAll('.nav-item');sibs.forEach(function(b){b.classList.remove('active');});el.classList.add('active');}
};

window.setThemeAndMark=function(theme){
  localStorage.setItem('vietquiz-theme',theme);
  if(theme==='dark')document.documentElement.classList.add('dark');
  else if(theme==='light')document.documentElement.classList.remove('dark');
  else{var sys=window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.classList.toggle('dark',sys);}
  ['light','dark','system'].forEach(function(t){var b=document.getElementById('theme-'+t+'-btn');if(b){b.classList.toggle('btn-primary',t===theme);b.classList.toggle('btn-outline',t!==theme);}});
  toast('Đã chuyển sang chủ đề '+(theme==='light'?'Sáng':theme==='dark'?'Tối':'Hệ thống'));
};

window.saveProfile=function(){toast('Đã lưu hồ sơ thành công!');};
window.saveNotifications=function(){toast('Đã lưu cài đặt thông báo!');};
window.savePassword=function(){toast('Đã cập nhật mật khẩu!');};
window.confirmDelete=function(){toast('Chức năng xóa tài khoản chưa khả dụng trong phiên bản demo.','err');};

function toast(m,t){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-'+(t==='err'?'error':'success');e.innerHTML='<span>'+(t==='err'?'❌':'✅')+'</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
})();
</script>
@endpush
