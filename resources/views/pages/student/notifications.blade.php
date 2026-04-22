{{-- Student: notifications --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
.notif-item { display:flex; align-items:flex-start; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid var(--border); transition:background var(--transition-fast); cursor:pointer; }
    .notif-item:last-child { border-bottom:none; }
    .notif-item:hover { background:var(--muted); }
    .notif-item.unread { background:color-mix(in srgb,var(--primary) 4%,transparent); }
    .notif-item.unread:hover { background:color-mix(in srgb,var(--primary) 8%,transparent); }
    .notif-dot { width:0.5rem; height:0.5rem; border-radius:50%; background:var(--primary); flex-shrink:0; margin-top:0.5rem; }
    .notif-icon { width:2.5rem; height:2.5rem; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.125rem; }
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1>Thông báo</h1>
            <p style="color:var(--muted-foreground);">Tất cả thông báo và cập nhật của bạn</p>
          </div>
          <div style="display:flex;gap:0.5rem;">
            <button class="btn btn-outline btn-sm" onclick="markAllRead()">Đánh dấu tất cả đã đọc</button>
            <button class="btn btn-ghost btn-sm" style="color:var(--destructive);" onclick="clearAll()">Xóa tất cả</button>
          </div>
        </div>
      </div>

      <!-- Filter -->
      <div class="tabs-list stagger-children" style="margin-bottom:1.25rem;max-width:500px;">
        <button class="tab-trigger active" onclick="filterNotifs('all',this)">Tất cả</button>
        <button class="tab-trigger" onclick="filterNotifs('unread',this)">Chưa đọc <span class="badge badge-primary" style="margin-left:0.25rem;" id="unread-count">5</span></button>
        <button class="tab-trigger" onclick="filterNotifs('submission',this)">Bài nộp</button>
        <button class="tab-trigger" onclick="filterNotifs('system',this)">Hệ thống</button>
      </div>

      <div class="card stagger-children">
        <div id="notifs-list"></div>
        <div id="notifs-empty" class="empty-state" style="display:none;padding:3rem;">
          <div style="font-size:2.5rem;">🔔</div>
          <h3>Không có thông báo</h3>
          <p>Bạn đã đọc hết thông báo rồi!</p>
        </div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var NOTIFS=[
    {id:1,type:'submission',icon:'📤',iconBg:'color-mix(in srgb,var(--primary) 12%,transparent)',title:'Bài tập đã được chấm điểm',body:'Bài tập Hàm số Chương 3 đã được giáo viên chấm xong.',time:'5 phút trước',unread:true,link:'grades.html'},
    {id:2,type:'submission',icon:'📤',iconBg:'color-mix(in srgb,var(--primary) 12%,transparent)',title:'Nhắc nhở nộp bài tập',body:'Bài tập Hóa học Chương 5 còn 2 ngày đến hạn nộp.',time:'32 phút trước',unread:true,link:'assignments.html'},
    {id:3,type:'quiz',icon:'📝',iconBg:'color-mix(in srgb,var(--success) 12%,transparent)',title:'Kết quả bài kiểm tra sẵn sàng',body:'Bài kiểm tra "React Hooks" đã có kết quả. Xem điểm ngay!',time:'1 giờ trước',unread:true,link:'quiz-result.html?id=1'},
    {id:4,type:'system',icon:'🔔',iconBg:'color-mix(in srgb,var(--warning) 12%,transparent)',title:'Sắp có bài kiểm tra mới',body:'Giáo viên vừa tạo bài kiểm tra mới trong lớp 10A. Kiểm tra ngay!',time:'3 giờ trước',unread:true,link:'quizzes.html'},
    {id:5,type:'system',icon:'⭐',iconBg:'color-mix(in srgb,var(--warning) 12%,transparent)',title:'Xếp hạng cao trong lớp',body:'Bạn đạt vị trí thứ 3 trong bảng xếp hạng tuần này. Giỏi lắm!',time:'Hôm qua',unread:true,link:'grades.html'},
    {id:6,type:'submission',icon:'📤',iconBg:'color-mix(in srgb,var(--primary) 12%,transparent)',title:'Bài tập sắp đến hạn',body:'Bài tập "Vật lý Điện học" còn 1 ngày đến hạn nộp.',time:'Hôm qua',unread:false,link:'assignments.html'},
    {id:7,type:'system',icon:'✅',iconBg:'color-mix(in srgb,var(--success) 12%,transparent)',title:'Tham gia lớp thành công',body:'Bạn đã được thêm vào lớp 10A Toán thành công.',time:'2 ngày trước',unread:false,link:'courses.html'},
    {id:8,type:'system',icon:'🎉',iconBg:'color-mix(in srgb,var(--accent) 12%,transparent)',title:'Tài khoản nâng lên Pro',body:'Chúc mừng! Tài khoản của bạn đã được nâng cấp lên gói Pro.',time:'3 ngày trước',unread:false,link:'vip.html'},
    {id:9,type:'quiz',icon:'📊',iconBg:'color-mix(in srgb,var(--info) 12%,transparent)',title:'Báo cáo tuần sẵn sàng',body:'Báo cáo phân tích học tập tuần từ 20-27/03 đã sẵn sàng.',time:'4 ngày trước',unread:false,link:'grades.html'},
    {id:10,type:'system',icon:'🔧',iconBg:'color-mix(in srgb,var(--muted-foreground) 12%,transparent)',title:'Bảo trì hệ thống',body:'VietQuiz sẽ bảo trì từ 02:00 - 04:00 ngày 05/04/2026.',time:'5 ngày trước',unread:false,link:null}
  ];
  var currentFilter='all';
  function render(data){
    var list=document.getElementById('notifs-list');
    var empty=document.getElementById('notifs-empty');
    if(!data.length){list.innerHTML='';empty.style.display='';return;}
    empty.style.display='none';
    list.innerHTML=data.map(function(n){
      var dot=n.unread?'<div class="notif-dot"></div>':'';
      var fw=n.unread?'600':'500';
      return '<div class="notif-item '+(n.unread?'unread':'')+'" id="notif-'+n.id+'" onclick="readNotif('+n.id+',\''+(n.link||'')+'\')">'+'<div class="notif-icon" style="background:'+n.iconBg+';">'+n.icon+'</div>'+'<div style="flex:1;"><div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;"><span style="font-weight:'+fw+';font-size:var(--text-sm);">'+n.title+'</span><span style="font-size:var(--text-xs);color:var(--muted-foreground);white-space:nowrap;">'+n.time+'</span></div><p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.125rem;line-height:1.5;">'+n.body+'</p></div>'+dot+'</div>';
    }).join('');
  }
  window.filterNotifs=function(f,el){
    currentFilter=f;
    document.querySelectorAll('.tab-trigger').forEach(function(b){b.classList.remove('active');});
    el.classList.add('active');
    var data=f==='all'?NOTIFS:f==='unread'?NOTIFS.filter(function(n){return n.unread;}):NOTIFS.filter(function(n){return n.type===f;});
    render(data);
  };
  window.readNotif=function(id,link){
    var n=NOTIFS.filter(function(x){return x.id===id;})[0];
    if(n)n.unread=false;
    var el=document.getElementById('notif-'+id);
    if(el)el.classList.remove('unread');
    var dot=el?el.querySelector('.notif-dot'):null;
    if(dot)dot.remove();
    updateUnreadCount();
    if(link)window.location.href=link;
  };
  window.markAllRead=function(){
    NOTIFS.forEach(function(n){n.unread=false;});
    render(currentFilter==='all'?NOTIFS:NOTIFS.filter(function(n){return n.type===currentFilter;}));
    updateUnreadCount();
    toast('Đã đánh dấu tất cả đã đọc');
  };
  window.clearAll=function(){
    if(confirm('Xóa tất cả thông báo?')){NOTIFS.length=0;render([]);updateUnreadCount();toast('Đã xóa tất cả thông báo');}
  };
  function updateUnreadCount(){var cnt=NOTIFS.filter(function(n){return n.unread;}).length;var el=document.getElementById('unread-count');if(el)el.textContent=cnt;}
  function toast(m){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-success';e.innerHTML='<span>✅</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
  render(NOTIFS);
})();
</script>
@endpush
