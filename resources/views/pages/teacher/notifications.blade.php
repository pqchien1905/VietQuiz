{{-- Teacher: notifications --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.notif-item{display:flex;align-items:flex-start;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid var(--border);transition:background var(--transition-fast);cursor:pointer}
    .notif-item:last-child{border-bottom:none}
    .notif-item:hover{background:var(--muted)}
    .notif-item.unread{background:color-mix(in srgb,var(--primary) 4%,transparent)}
    .notif-item.unread:hover{background:color-mix(in srgb,var(--primary) 8%,transparent)}
    .notif-dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--primary);flex-shrink:0;margin-top:.5rem}
    .notif-icon{width:2.5rem;height:2.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.125rem}
    .tab-bar{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:1.25rem}
    .tab-btn{padding:.625rem 1.25rem;font-size:var(--text-sm);font-weight:500;background:none;border:none;border-bottom:2px solid transparent;color:var(--muted-foreground);cursor:pointer;transition:all var(--transition-fast);display:flex;align-items:center;gap:.375rem}
    .tab-btn:hover{color:var(--foreground)}
    .tab-btn.active{color:var(--primary);border-bottom-color:var(--primary)}
    .tab-count{font-size:var(--text-xs);background:var(--muted);color:var(--muted-foreground);padding:.1rem .5rem;border-radius:var(--radius-full);font-weight:600}
    .tab-btn.active .tab-count{background:var(--primary);color:var(--primary-foreground)}
    .notif-actions{display:flex;gap:.25rem;opacity:0;transition:opacity var(--transition-fast);flex-shrink:0}
    .notif-item:hover .notif-actions{opacity:1}
    .notif-act-btn{background:none;border:none;cursor:pointer;padding:.25rem;border-radius:var(--radius-sm);color:var(--muted-foreground);font-size:var(--text-sm);transition:all var(--transition-fast)}
    .notif-act-btn:hover{background:var(--muted);color:var(--foreground)}
    .notif-act-btn.del:hover{color:var(--destructive)}
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
            <button class="btn btn-outline btn-sm" onclick="markAllRead()">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.25rem;"><polyline points="20 6 9 17 4 12"/></svg>
              Đánh dấu tất cả đã đọc
            </button>
            <button class="btn btn-ghost btn-sm" style="color:var(--destructive);" onclick="confirmClearAll()">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.25rem;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
              Xóa tất cả
            </button>
          </div>
        </div>
      </div>

      <div class="tab-bar stagger-children" id="tab-bar">
        <button class="tab-btn active" data-tab="all">Tất cả <span class="tab-count" id="tc-all">10</span></button>
        <button class="tab-btn" data-tab="unread">Chưa đọc <span class="tab-count" id="tc-unread">5</span></button>
        <button class="tab-btn" data-tab="submission">Bài nộp <span class="tab-count" id="tc-sub">3</span></button>
        <button class="tab-btn" data-tab="quiz">Bài thi <span class="tab-count" id="tc-quiz">2</span></button>
        <button class="tab-btn" data-tab="system">Hệ thống <span class="tab-count" id="tc-sys">5</span></button>
      </div>

      <div class="card stagger-children">
        <div id="notifs-list"></div>
        <div id="notifs-empty" style="display:none;padding:3rem;text-align:center;">
          <div style="font-size:3rem;margin-bottom:.75rem;">🔔</div>
          <h3 style="font-weight:600;color:var(--foreground);">Không có thông báo</h3>
          <p style="color:var(--muted-foreground);margin-top:.5rem;">Bạn đã đọc hết thông báo rồi!</p>
        </div>
      </div>

<div class="modal-overlay" id="del-modal">
  <div class="modal" style="max-width:28rem;">
    <div class="modal-header">
      <div><h3 class="modal-title">Xóa thông báo</h3></div>
      <button class="modal-close" onclick="closeDel()">✕</button>
    </div>
    <div class="modal-body">
      <p id="del-msg" style="color:var(--muted-foreground);"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeDel()">Hủy</button>
      <button class="btn btn-destructive" id="del-confirm">Xóa</button>
    </div>
  </div>
</div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
var NOTIFS=[
  {id:1,type:'submission',icon:'📤',iconBg:'color-mix(in srgb,var(--primary) 12%,transparent)',title:'Nguyễn Minh Anh đã nộp bài',body:'Bài tập Hàm số Chương 3 đã được nộp và chờ chấm điểm.',time:'5 phút trước',unread:true,link:'grading.html'},
  {id:2,type:'submission',icon:'📤',iconBg:'color-mix(in srgb,var(--primary) 12%,transparent)',title:'Trần Thị Bích đã nộp bài',body:'Báo cáo Thực nghiệm Sinh học đã được nộp đúng hạn.',time:'32 phút trước',unread:true,link:'grading.html'},
  {id:3,type:'quiz',icon:'📝',iconBg:'color-mix(in srgb,var(--success) 12%,transparent)',title:'30 học sinh hoàn thành bài thi',body:'Bài kiểm tra "React Hooks" lớp 10A đã có kết quả.',time:'1 giờ trước',unread:true,link:'analytics.html'},
  {id:4,type:'system',icon:'🔔',iconBg:'color-mix(in srgb,var(--warning) 12%,transparent)',title:'Nhắc nhở: Sắp đến hạn chấm điểm',body:'Bài tập "Hóa học Hữu cơ" cần được chấm trước 20/04/2026.',time:'3 giờ trước',unread:true,link:'grading.html'},
  {id:5,type:'system',icon:'⭐',iconBg:'color-mix(in srgb,var(--warning) 12%,transparent)',title:'Học sinh xuất sắc tuần này',body:'Lưu Thị Uyên đạt điểm cao nhất lớp với 96% trung bình.',time:'Hôm qua',unread:true,link:'students.html'},
  {id:6,type:'submission',icon:'📤',iconBg:'color-mix(in srgb,var(--primary) 12%,transparent)',title:'8 học sinh nộp trễ hạn',body:'Bài tập "Vật lý Điện học" có 8 học sinh nộp muộn.',time:'Hôm qua',unread:false,link:'assignments.html'},
  {id:7,type:'system',icon:'✅',iconBg:'color-mix(in srgb,var(--success) 12%,transparent)',title:'Đề thi đã được duyệt',body:'Đề kiểm tra giữa kỳ môn Toán đã được phê duyệt bởi hiệu trưởng.',time:'2 ngày trước',unread:false,link:'quizzes.html'},
  {id:8,type:'system',icon:'🎉',iconBg:'color-mix(in srgb,var(--accent) 12%,transparent)',title:'Tài khoản nâng lên Pro',body:'Chúc mừng! Tài khoản của bạn đã được nâng cấp lên gói Pro.',time:'3 ngày trước',unread:false,link:'vip.html'},
  {id:9,type:'quiz',icon:'📊',iconBg:'color-mix(in srgb,var(--info) 12%,transparent)',title:'Báo cáo tuần sẵn sàng',body:'Báo cáo phân tích học tập tuần từ 20-27/03 đã sẵn sàng.',time:'4 ngày trước',unread:false,link:'analytics.html'},
  {id:10,type:'system',icon:'🔧',iconBg:'color-mix(in srgb,var(--muted-foreground) 12%,transparent)',title:'Bảo trì hệ thống',body:'VietQuiz sẽ bảo trì từ 02:00 - 04:00 ngày 05/04/2026.',time:'5 ngày trước',unread:false,link:null}
];

var curTab='all';

document.getElementById('tab-bar').addEventListener('click',function(e){
  var b=e.target.closest('.tab-btn');if(!b)return;
  document.querySelectorAll('.tab-btn').forEach(function(x){x.classList.remove('active');});
  b.classList.add('active');curTab=b.getAttribute('data-tab');render();
});

function getFiltered(){
  if(curTab==='all')return NOTIFS.slice();
  if(curTab==='unread')return NOTIFS.filter(function(n){return n.unread;});
  return NOTIFS.filter(function(n){return n.type===curTab;});
}

function render(){
  var data=getFiltered();
  var list=document.getElementById('notifs-list');
  var empty=document.getElementById('notifs-empty');
  if(!data.length){list.innerHTML='';empty.style.display='';return;}
  empty.style.display='none';
  list.innerHTML=data.map(function(n){
    return '<div class="notif-item'+(n.unread?' unread':'')+'" id="notif-'+n.id+'">'
      +'<div class="notif-icon" style="background:'+n.iconBg+';">'+n.icon+'</div>'
      +'<div style="flex:1;" onclick="readNotif('+n.id+',\''+(n.link||'')+'\')">'
      +'<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;">'
      +'<span style="font-weight:'+(n.unread?'600':'500')+';font-size:var(--text-sm);">'+n.title+'</span>'
      +'<span style="font-size:var(--text-xs);color:var(--muted-foreground);white-space:nowrap;">'+n.time+'</span>'
      +'</div>'
      +'<p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:.125rem;line-height:1.5;">'+n.body+'</p>'
      +'</div>'
      +'<div class="notif-actions">'
      +(n.unread?'<button class="notif-act-btn" onclick="toggleRead('+n.id+')" title="Đánh dấu đã đọc"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></button>':'<button class="notif-act-btn" onclick="toggleRead('+n.id+')" title="Đánh dấu chưa đọc"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg></button>')
      +'<button class="notif-act-btn del" onclick="confirmDelOne('+n.id+')" title="Xóa"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>'
      +'</div>'
      +(n.unread?'<div class="notif-dot"></div>':'')
      +'</div>';
  }).join('');
}

function updCounts(){
  document.getElementById('tc-all').textContent=NOTIFS.length;
  document.getElementById('tc-unread').textContent=NOTIFS.filter(function(n){return n.unread;}).length;
  document.getElementById('tc-sub').textContent=NOTIFS.filter(function(n){return n.type==='submission';}).length;
  document.getElementById('tc-quiz').textContent=NOTIFS.filter(function(n){return n.type==='quiz';}).length;
  document.getElementById('tc-sys').textContent=NOTIFS.filter(function(n){return n.type==='system';}).length;
}

window.readNotif=function(id,link){
  var n=NOTIFS.find(function(x){return x.id===id;});
  if(n)n.unread=false;
  updCounts();render();
  if(link)window.location.href=link;
};

window.toggleRead=function(id){
  var n=NOTIFS.find(function(x){return x.id===id;});
  if(n){n.unread=!n.unread;updCounts();render();toast(n.unread?'Đánh dấu chưa đọc':'Đánh dấu đã đọc');}
};

window.markAllRead=function(){
  NOTIFS.forEach(function(n){n.unread=false;});
  updCounts();render();toast('Đã đánh dấu tất cả đã đọc');
};

window.confirmDelOne=function(id){
  var n=NOTIFS.find(function(x){return x.id===id;});if(!n)return;
  document.getElementById('del-msg').textContent='Bạn có chắc muốn xóa thông báo "'+n.title+'"?';
  document.getElementById('del-confirm').onclick=function(){
    var i=NOTIFS.findIndex(function(x){return x.id===id;});
    if(i!==-1)NOTIFS.splice(i,1);
    closeDel();updCounts();render();toast('Đã xóa thông báo');
  };
  document.getElementById('del-modal').classList.add('open');
};

window.confirmClearAll=function(){
  if(!NOTIFS.length){toast('Không có thông báo nào','err');return;}
  document.getElementById('del-msg').textContent='Bạn có chắc muốn xóa tất cả '+NOTIFS.length+' thông báo? Hành động này không thể hoàn tác.';
  document.getElementById('del-confirm').onclick=function(){
    NOTIFS.length=0;closeDel();updCounts();render();toast('Đã xóa tất cả thông báo');
  };
  document.getElementById('del-modal').classList.add('open');
};
window.closeDel=function(){document.getElementById('del-modal').classList.remove('open');};
document.getElementById('del-modal').addEventListener('click',function(e){if(e.target===this)closeDel();});

function toast(m,t){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-'+(t==='err'?'error':'success');e.innerHTML='<span>'+(t==='err'?'❌':'✅')+'</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}

updCounts();render();
})();
</script>
@endpush
