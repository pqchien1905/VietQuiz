{{-- Teacher: profile --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.profile-hero { background:linear-gradient(135deg,var(--primary),color-mix(in srgb,var(--primary) 70%,var(--info))); padding:3rem 1.5rem 5rem; color:#fff; }
    .profile-card { margin-top:-4rem; position:relative; z-index:1; }
    .achievement-badge { display:flex;flex-direction:column;align-items:center;gap:0.25rem;padding:0.875rem;border-radius:var(--radius-md);background:var(--muted);border:1px solid var(--border);text-align:center; }
    .achievement-badge .icon { font-size:1.75rem; }
    .achievement-badge .label { font-size:var(--text-xs);color:var(--muted-foreground); }
    .achievement-badge .value { font-size:var(--text-sm);font-weight:700; }
    .activity-day { width:0.875rem;height:0.875rem;border-radius:2px;background:var(--muted);cursor:default; }
    .activity-day.level-1 { background:color-mix(in srgb,var(--success) 30%,transparent); }
    .activity-day.level-2 { background:color-mix(in srgb,var(--success) 55%,transparent); }
    .activity-day.level-3 { background:color-mix(in srgb,var(--success) 80%,transparent); }
    .activity-day.level-4 { background:var(--success); }
</style>
@endpush

@section('content')
  <!-- Hero banner -->
      <div class="profile-hero">
        <div style="max-width:900px;margin:0 auto;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
          <div class="avatar avatar-2xl" id="profile-avatar" style="border:4px solid rgba(255,255,255,0.5);">GV</div>
          <div style="flex:1;">
            <h1 style="color:#fff;font-size:var(--text-3xl);" id="profile-name">Giáo viên Demo</h1>
            <div style="color:rgba(255,255,255,.8);font-size:var(--text-sm);margin-top:0.25rem;" id="profile-role">Giáo viên • Trường THPT Example</div>
            <div style="display:flex;gap:1.5rem;margin-top:1rem;flex-wrap:wrap;">
              <div style="color:rgba(255,255,255,.9);font-size:var(--text-sm);"><span style="font-weight:700;font-size:var(--text-xl);">125</span><br/>Học sinh</div>
              <div style="color:rgba(255,255,255,.9);font-size:var(--text-sm);"><span style="font-weight:700;font-size:var(--text-xl);">18</span><br/>Đề thi</div>
              <div style="color:rgba(255,255,255,.9);font-size:var(--text-sm);"><span style="font-weight:700;font-size:var(--text-xl);">4</span><br/>Lớp học</div>
              <div style="color:rgba(255,255,255,.9);font-size:var(--text-sm);"><span style="font-weight:700;font-size:var(--text-xl);">2 năm</span><br/>Sử dụng</div>
            </div>
          </div>
          <div id="profile-edit-btn"></div>
        </div>
      </div>

      <div style="max-width:900px;margin:0 auto;padding:0 1.5rem 2rem;" class="profile-card">
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-content" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
              <div style="font-size:var(--text-sm);font-weight:500;">Chỉnh sửa thông tin hồ sơ</div>
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Cập nhật ảnh, tiểu sử và thông tin liên hệ</div>
            </div>
            <a href="{{ route('teacher.settings') }}" class="btn btn-primary btn-sm gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Chỉnh sửa
            </a>
          </div>
        </div>

        <!-- Achievements -->
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header"><h3 class="card-title">Thành tích</h3><p class="card-description">Huy hiệu và mốc quan trọng</p></div>
          <div class="card-content">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:0.75rem;" id="ach-grid"></div>
          </div>
        </div>

        <!-- Activity heatmap -->
        <div class="card" style="margin-bottom:1.5rem;">
          <div class="card-header"><h3 class="card-title">Hoạt động 12 tuần qua</h3></div>
          <div class="card-content">
            <div style="display:flex;gap:0.25rem;flex-wrap:wrap;" id="activity-grid"></div>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;font-size:var(--text-xs);color:var(--muted-foreground);">
              <span>Ít hơn</span>
              <div class="activity-day"></div>
              <div class="activity-day level-1"></div>
              <div class="activity-day level-2"></div>
              <div class="activity-day level-3"></div>
              <div class="activity-day level-4"></div>
              <span>Nhiều hơn</span>
            </div>
          </div>
        </div>

        <!-- About + Contact -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
          <div class="card">
            <div class="card-header"><h3 class="card-title">Giới thiệu</h3></div>
            <div class="card-content">
              <p style="font-size:var(--text-sm);line-height:1.7;color:var(--muted-foreground);">Giáo viên Toán với hơn 10 năm kinh nghiệm giảng dạy tại các trường THPT. Đam mê ứng dụng công nghệ trong giáo dục để nâng cao hiệu quả học tập của học sinh.</p>
              <div style="margin-top:1rem;display:flex;flex-direction:column;gap:0.5rem;">
                <div style="font-size:var(--text-sm);display:flex;gap:0.5rem;"><span>🎓</span><span>Đại học Sư phạm Hà Nội • Toán học</span></div>
                <div style="font-size:var(--text-sm);display:flex;gap:0.5rem;"><span>📍</span><span>Hà Nội, Việt Nam</span></div>
                <div style="font-size:var(--text-sm);display:flex;gap:0.5rem;"><span>📅</span><span>Tham gia từ tháng 3/2024</span></div>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><h3 class="card-title">Liên hệ</h3></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:0.75rem;">
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:2rem;height:2rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--primary) 12%,transparent);display:flex;align-items:center;justify-content:center;">📧</div>
                <div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Email</div><div style="font-size:var(--text-sm);" id="profile-email">teacher@demo.com</div></div>
              </div>
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:2rem;height:2rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--success) 12%,transparent);display:flex;align-items:center;justify-content:center;">📞</div>
                <div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Điện thoại</div><div style="font-size:var(--text-sm);">090 123 4567</div></div>
              </div>
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:2rem;height:2rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--warning) 12%,transparent);display:flex;align-items:center;justify-content:center;">🏫</div>
                <div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Trường</div><div style="font-size:var(--text-sm);">THPT Example, Hà Nội</div></div>
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
  var cn=document.cookie.match(/auth_name=([^;]+)/);var un=cn?decodeURIComponent(cn[1]):'Giáo viên Demo';
  var ini=un.split(' ').filter(Boolean).map(function(w){return w[0];}).slice(-2).join('').toUpperCase();
  var el=document.getElementById('profile-name');if(el)el.textContent=un;
  document.getElementById('profile-avatar').textContent=ini;
  // Achievements
  var ACH=[{icon:'🏆',label:'Giáo viên Xuất sắc',value:'Top 5%'},{icon:'🎯',label:'Đề thi Đầu tiên',value:'Tháng 3/2024'},{icon:'👥',label:'100+ Học sinh',value:'Đạt thành tích'},{icon:'⭐',label:'Đánh giá 5 sao',value:'18 lượt'},{icon:'📚',label:'Ngân hàng câu hỏi',value:'200+ câu'},{icon:'🔥',label:'Streak 30 ngày',value:'Liên tiếp'}];
  document.getElementById('ach-grid').innerHTML=ACH.map(function(a){return '<div class="achievement-badge"><div class="icon">'+a.icon+'</div><div class="value">'+a.value+'</div><div class="label">'+a.label+'</div></div>';}).join('');
  // Activity heatmap
  var grid=document.getElementById('activity-grid');
  var levels=[0,0,1,0,2,0,1,2,3,1,0,2,4,3,2,1,0,2,1,3,4,2,0,1,3,2,4,1,0,0,2,3,1,2,0,4,3,2,1,0,2,1,4,3,0,1,2,0,3,2,4,1,0,3,2,1,4,0,2,3,1,0,4,2,3,0,1,2,4,3,2,1,0,3,1,4,2,0,3,1,2,4,0,3];
  grid.innerHTML=levels.map(function(l){return '<div class="activity-day level-'+l+'"></div>';}).join('');
})();
</script>
@endpush
