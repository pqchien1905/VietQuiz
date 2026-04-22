{{-- Teacher: assignments --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.asgn-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1.25rem;transition:all var(--transition-fast)}
    .asgn-card:hover{box-shadow:var(--shadow-md)}
    .tab-bar{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:1.25rem}
    .tab-btn{padding:.625rem 1.25rem;font-size:var(--text-sm);font-weight:500;background:none;border:none;border-bottom:2px solid transparent;color:var(--muted-foreground);cursor:pointer;transition:all var(--transition-fast);display:flex;align-items:center;gap:.375rem}
    .tab-btn:hover{color:var(--foreground)}
    .tab-btn.active{color:var(--primary);border-bottom-color:var(--primary)}
    .tab-count{font-size:var(--text-xs);background:var(--muted);color:var(--muted-foreground);padding:.1rem .5rem;border-radius:var(--radius-full);font-weight:600}
    .tab-btn.active .tab-count{background:var(--primary);color:var(--primary-foreground)}
    .prog-row{display:flex;align-items:center;gap:.5rem;font-size:var(--text-sm)}
    .prog-row .progress{flex:1;height:.375rem}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1>Bài tập</h1>
            <p style="color:var(--muted-foreground);">Tạo và quản lý bài tập của học sinh</p>
          </div>
          <button class="btn btn-primary gap-2" onclick="openCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tạo Bài tập
          </button>
        </div>
      </div>
      <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đang hoạt động</div><div class="stat-card__value" style="color:var(--success);" id="st-act">3</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đang chấm</div><div class="stat-card__value" style="color:var(--warning);" id="st-grd">2</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Hoàn thành</div><div class="stat-card__value" id="st-cmp">2</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng Bài nộp</div><div class="stat-card__value" id="st-sub">0</div></div>
      </div>
      <div class="tab-bar stagger-children" id="tab-bar">
        <button class="tab-btn active" data-tab="all">Tất cả <span class="tab-count" id="tc-all">7</span></button>
        <button class="tab-btn" data-tab="active">Đang hoạt động <span class="tab-count" id="tc-act">3</span></button>
        <button class="tab-btn" data-tab="grading">Đang chấm <span class="tab-count" id="tc-grd">2</span></button>
        <button class="tab-btn" data-tab="completed">Hoàn thành <span class="tab-count" id="tc-cmp">2</span></button>
      </div>
      <div class="toolbar stagger-children">
        <div class="toolbar-left">
          <div class="search-input-wrapper" style="max-width:320px;flex:1;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" class="input" placeholder="Tìm bài tập..." id="a-search" oninput="applyFilters()" style="font-size:var(--text-sm);" />
          </div>
          <select class="input select" id="a-cls" onchange="applyFilters()" style="max-width:140px;font-size:var(--text-sm);"><option value="">Tất cả lớp</option><option>Lớp 10A</option><option>Lớp 11B</option><option>Lớp 9C</option><option>Lớp 10B</option></select>
        </div>
        <div class="toolbar-right"><span style="font-size:var(--text-sm);color:var(--muted-foreground);" id="a-cnt">7 bài tập</span></div>
      </div>
      <div id="alist" class="stagger-children" style="display:flex;flex-direction:column;gap:.75rem;margin-top:1rem;"></div>

<div class="modal-overlay" id="create-modal">
  <div class="modal" style="max-width:38rem;">
    <div class="modal-header">
      <div><h3 class="modal-title" id="cm-title">Tạo Bài tập mới</h3><p class="modal-desc">Tạo bài tập cho lớp học</p></div>
      <button class="modal-close" onclick="closeCreateModal()">✕</button>
    </div>
    <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
      <input type="hidden" id="ea-id" value="" />
      <div class="form-group"><label class="label label-required">Tiêu đề</label><input type="text" class="input" placeholder="VD: Bài tập Chương 3 — Hàm số" id="a-title" /></div>
      <div class="form-group"><label class="label">Mô tả</label><textarea class="input" style="min-height:4rem;" placeholder="Hướng dẫn chi tiết..." id="a-desc"></textarea></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="label">Lớp học</label><select class="input select" id="a-class"><option>Lớp 10A</option><option>Lớp 11B</option><option>Lớp 9C</option><option>Lớp 10B</option></select></div>
        <div class="form-group"><label class="label">Hạn nộp</label><input type="date" class="input" id="a-due" /></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="label">Điểm tối đa</label><input type="number" class="input" value="100" min="1" id="a-pts" /></div>
        <div class="form-group"><label class="label">Loại</label><select class="input select" id="a-type"><option value="essay">Viết/Essay</option><option value="code">Lập trình</option><option value="project">Dự án nhóm</option><option value="practice">Thực hành</option></select></div>
      </div>
      <div class="form-group"><label class="label">File đính kèm</label>
        <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:1.5rem;text-align:center;cursor:pointer;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
          <div style="color:var(--muted-foreground);font-size:var(--text-sm);">📎 Kéo thả hoặc nhấp để đính kèm</div>
        </div>
      </div>

<div class="modal-overlay" id="del-modal">
  <div class="modal" style="max-width:26rem;">
    <div class="modal-body" style="text-align:center;padding:2rem;">
      <div style="font-size:3rem;margin-bottom:.75rem;">🗑️</div>
      <h3 style="font-size:var(--text-xl);font-weight:700;margin-bottom:.5rem;">Xóa bài tập?</h3>
      <p style="color:var(--muted-foreground);font-size:var(--text-sm);margin-bottom:1.5rem;" id="del-msg">Bài tập sẽ bị xóa.</p>
      <div style="display:flex;gap:.75rem;justify-content:center;">
        <button class="btn btn-outline" onclick="closeDelModal()">Hủy</button>
        <button class="btn btn-destructive" id="del-btn">Xóa</button>
      </div>
    </div>
  </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
var SL={active:'Đang hoạt động',grading:'Đang chấm',completed:'Hoàn thành'};
var SB={active:'badge-success',grading:'badge-warning',completed:'badge-info'};
var TL={essay:'Viết',code:'Lập trình',project:'Dự án',practice:'Thực hành'};

var AS=[
  {id:1,title:'Bài tập Hàm số — Chương 3',cls:'Lớp 10A',due:'18/04/2026',submitted:28,total:32,graded:20,points:100,status:'active',type:'essay'},
  {id:2,title:'Thực hành Thí nghiệm Điện học',cls:'Lớp 11B',due:'15/04/2026',submitted:25,total:28,graded:25,points:50,status:'grading',type:'practice'},
  {id:3,title:'Bài tập Cân bằng hóa học',cls:'Lớp 9C',due:'10/04/2026',submitted:30,total:30,graded:30,points:100,status:'completed',type:'essay'},
  {id:4,title:'Báo cáo Thực nghiệm Sinh học',cls:'Lớp 10B',due:'20/04/2026',submitted:12,total:35,graded:0,points:150,status:'active',type:'project'},
  {id:5,title:'Phân tích sơ đồ Quang hợp',cls:'Lớp 10B',due:'25/04/2026',submitted:5,total:35,graded:0,points:50,status:'active',type:'essay'},
  {id:6,title:'Giải toán Tích phân',cls:'Lớp 10A',due:'08/04/2026',submitted:32,total:32,graded:32,points:100,status:'completed',type:'essay'},
  {id:7,title:'Bài tập Sóng cơ học',cls:'Lớp 11B',due:'12/04/2026',submitted:28,total:28,graded:15,points:75,status:'grading',type:'essay'}
];
var curTab='all',delId=null,nxId=8;

document.getElementById('tab-bar').addEventListener('click',function(e){
  var b=e.target.closest('.tab-btn');if(!b)return;
  document.querySelectorAll('.tab-btn').forEach(function(x){x.classList.remove('active');});
  b.classList.add('active');curTab=b.getAttribute('data-tab');applyFilters();
});

function render(data){
  var c=document.getElementById('alist');
  document.getElementById('a-cnt').textContent=data.length+' bài tập';
  if(!data.length){c.innerHTML='<div style="padding:3rem;text-align:center;color:var(--muted-foreground);"><div style="font-size:3rem;margin-bottom:.75rem;">📋</div><h3 style="font-size:var(--text-xl);font-weight:600;color:var(--foreground);">Không có bài tập</h3><p>Tạo bài tập đầu tiên</p></div>';return;}
  c.innerHTML=data.map(function(a){
    var pct=a.total?Math.round((a.submitted/a.total)*100):0;
    var gpct=a.submitted?Math.round((a.graded/a.submitted)*100):0;
    var gradBtn=a.status==='grading'||a.status==='active'?'<a href="grading.html?id='+a.id+'" class="btn btn-primary btn-sm">Chấm điểm</a>':'';
    return '<div class="asgn-card">'
      +'<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
      +'<div style="flex:1;">'
      +'<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap;">'
      +'<h3 style="font-size:var(--text-base);font-weight:600;">'+a.title+'</h3>'
      +'<span class="badge '+SB[a.status]+'">'+SL[a.status]+'</span>'
      +'<span class="badge badge-outline">'+TL[a.type]+'</span>'
      +'</div>'
      +'<div style="display:flex;gap:1rem;font-size:var(--text-sm);color:var(--muted-foreground);flex-wrap:wrap;">'
      +'<span>📚 '+a.cls+'</span><span>📅 Hạn: '+a.due+'</span><span>⭐ '+a.points+' điểm</span>'
      +'</div></div>'
      +'<div style="display:flex;gap:.5rem;flex-shrink:0;">'
      +gradBtn
      +'<button class="btn btn-outline btn-sm" onclick="toast(\'Mở bài nộp #'+a.id+'\')">Bài nộp ('+a.submitted+')</button>'
      +'<button class="btn btn-ghost btn-sm" onclick="editA('+a.id+')">Sửa</button>'
      +'<button class="btn btn-ghost btn-sm" style="color:var(--destructive);" onclick="openDelModal('+a.id+')"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>'
      +'</div></div>'
      +'<div style="margin-top:1rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">'
      +'<div><div style="display:flex;justify-content:space-between;font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;"><span>Đã nộp</span><span>'+a.submitted+'/'+a.total+' ('+pct+'%)</span></div><div class="progress" style="height:.375rem;"><div class="progress-bar" style="width:'+pct+'%;background:'+(pct>=80?'var(--success)':'var(--info)')+';"></div></div></div>'
      +'<div><div style="display:flex;justify-content:space-between;font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;"><span>Đã chấm</span><span>'+a.graded+'/'+a.submitted+' ('+(gpct||0)+'%)</span></div><div class="progress" style="height:.375rem;"><div class="progress-bar" style="width:'+gpct+'%;background:'+(gpct>=100?'var(--success)':'var(--warning)')+';"></div></div></div>'
      +'</div></div>';
  }).join('');
}

window.applyFilters=function(){
  var s=(document.getElementById('a-search').value||'').toLowerCase();
  var cls=document.getElementById('a-cls').value;
  render(AS.filter(function(a){
    return(!s||a.title.toLowerCase().indexOf(s)!==-1)&&(curTab==='all'||a.status===curTab)&&(!cls||a.cls===cls);
  }));
};

window.openCreateModal=function(){
  document.getElementById('ea-id').value='';document.getElementById('cm-title').textContent='Tạo Bài tập mới';
  document.getElementById('a-title').value='';document.getElementById('a-desc').value='';
  document.getElementById('pub-btn').textContent='Xuất bản';
  document.getElementById('create-modal').classList.add('open');
  setTimeout(function(){document.getElementById('a-title').focus();},100);
};
window.closeCreateModal=function(){document.getElementById('create-modal').classList.remove('open');};
window.editA=function(id){
  var a=AS.find(function(x){return x.id===id;});if(!a)return;
  document.getElementById('ea-id').value=id;document.getElementById('cm-title').textContent='Sửa bài tập';
  document.getElementById('a-title').value=a.title;document.getElementById('a-class').value=a.cls;
  document.getElementById('a-pts').value=a.points;document.getElementById('a-type').value=a.type;
  document.getElementById('pub-btn').textContent='Lưu thay đổi';
  document.getElementById('create-modal').classList.add('open');
};
window.saveDraft=function(){toast('Đã lưu nháp!');closeCreateModal();};
window.publishA=function(){
  var t=document.getElementById('a-title').value.trim();if(!t){toast('Nhập tiêu đề!','err');return;}
  var eid=parseInt(document.getElementById('ea-id').value)||0;
  if(eid){var a=AS.find(function(x){return x.id===eid;});if(a)a.title=t;toast('Đã cập nhật!');}
  else{AS.push({id:nxId++,title:t,cls:document.getElementById('a-class').value,due:'30/04/2026',submitted:0,total:32,graded:0,points:parseInt(document.getElementById('a-pts').value)||100,status:'active',type:document.getElementById('a-type').value});toast('Đã xuất bản!');}
  closeCreateModal();updSt();applyFilters();
};

window.openDelModal=function(id){delId=id;var a=AS.find(function(x){return x.id===id;});
  if(a)document.getElementById('del-msg').textContent='"'+a.title+'" sẽ bị xóa.';
  document.getElementById('del-modal').classList.add('open');
  document.getElementById('del-btn').onclick=function(){AS=AS.filter(function(x){return x.id!==delId;});closeDelModal();updSt();applyFilters();toast('Đã xóa!');};
};
window.closeDelModal=function(){document.getElementById('del-modal').classList.remove('open');delId=null;};

function updSt(){
  var act=AS.filter(function(a){return a.status==='active';}).length;
  var grd=AS.filter(function(a){return a.status==='grading';}).length;
  var cmp=AS.filter(function(a){return a.status==='completed';}).length;
  var sub=AS.reduce(function(s,a){return s+a.submitted;},0);
  document.getElementById('st-act').textContent=act;document.getElementById('st-grd').textContent=grd;
  document.getElementById('st-cmp').textContent=cmp;document.getElementById('st-sub').textContent=sub;
  document.getElementById('tc-all').textContent=AS.length;document.getElementById('tc-act').textContent=act;
  document.getElementById('tc-grd').textContent=grd;document.getElementById('tc-cmp').textContent=cmp;
}

window.toast=function(m,t){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-'+(t==='err'?'error':'success');e.innerHTML='<span>'+(t==='err'?'❌':'✅')+'</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);};

['create-modal','del-modal'].forEach(function(id){document.getElementById(id).addEventListener('click',function(e){if(e.target===this){if(id==='create-modal')closeCreateModal();else closeDelModal();}});});

updSt();applyFilters();
})();
</script>
@endpush
