{{-- Teacher: grading --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.grade-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1.25rem;transition:all var(--transition-fast)}
    .grade-card:hover{box-shadow:var(--shadow-md)}
    .grade-input{width:4.5rem;text-align:center;font-weight:700;font-size:var(--text-base)}
    .grade-input:focus{border-color:var(--primary);box-shadow:0 0 0 2px color-mix(in srgb,var(--primary) 25%,transparent)}
    .student-row{display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;border-bottom:1px solid var(--border);transition:background var(--transition-fast)}
    .student-row:hover{background:var(--muted)}
    .student-row:last-child{border-bottom:none}
    .avatar-sm{width:2rem;height:2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:var(--text-xs);font-weight:700;flex-shrink:0}
    .tab-bar{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:1.25rem}
    .tab-btn{padding:.625rem 1.25rem;font-size:var(--text-sm);font-weight:500;background:none;border:none;border-bottom:2px solid transparent;color:var(--muted-foreground);cursor:pointer;transition:all var(--transition-fast);display:flex;align-items:center;gap:.375rem}
    .tab-btn:hover{color:var(--foreground)}
    .tab-btn.active{color:var(--primary);border-bottom-color:var(--primary)}
    .tab-count{font-size:var(--text-xs);background:var(--muted);color:var(--muted-foreground);padding:.1rem .5rem;border-radius:var(--radius-full);font-weight:600}
    .tab-btn.active .tab-count{background:var(--primary);color:var(--primary-foreground)}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1>Chấm điểm</h1>
            <p style="color:var(--muted-foreground);">Chấm điểm bài kiểm tra và bài tập của học sinh</p>
          </div>
        </div>
      </div>
      <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Chờ chấm</div><div class="stat-card__value" style="color:var(--warning);" id="st-pend">18</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đã chấm hôm nay</div><div class="stat-card__value" style="color:var(--success);" id="st-today">12</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB chung</div><div class="stat-card__value" id="st-avg">7.4</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng đã chấm</div><div class="stat-card__value" id="st-total">156</div></div>
      </div>
      <div class="tab-bar stagger-children" id="tab-bar">
        <button class="tab-btn active" data-tab="pending">Chờ chấm <span class="tab-count" id="tc-p">18</span></button>
        <button class="tab-btn" data-tab="graded">Đã chấm <span class="tab-count" id="tc-g">12</span></button>
      </div>
      <div class="toolbar stagger-children">
        <div class="toolbar-left">
          <div class="search-input-wrapper" style="max-width:300px;flex:1;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" class="input" placeholder="Tìm học sinh..." id="g-search" oninput="applyFilters()" style="font-size:var(--text-sm);" />
          </div>
          <select class="input select" id="g-asgn" onchange="applyFilters()" style="max-width:200px;font-size:var(--text-sm);"><option value="">Tất cả bài</option><option>Bài tập Hàm số</option><option>Thực hành Điện học</option><option>Kiểm tra Cuối kì Toán</option><option>Sóng cơ học</option></select>
        </div>
        <div class="toolbar-right"><span style="font-size:var(--text-sm);color:var(--muted-foreground);" id="g-cnt">30 bài nộp</span></div>
      </div>
      <div id="glist" class="stagger-children" style="margin-top:1rem;"></div>

<div class="modal-overlay" id="grade-modal">
  <div class="modal" style="max-width:32rem;">
    <div class="modal-header">
      <div><h3 class="modal-title" id="gm-title">Chấm điểm</h3><p class="modal-desc" id="gm-sub"></p></div>
      <button class="modal-close" onclick="closeGradeModal()">✕</button>
    </div>
    <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
      <input type="hidden" id="gm-id" />
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="label">Điểm</label><input type="number" class="input grade-input" id="gm-score" min="0" max="10" step="0.1" style="width:100%;text-align:left;" /></div>
        <div class="form-group"><label class="label">Điểm tối đa</label><input type="text" class="input" id="gm-max" readonly style="background:var(--muted);" /></div>
      </div>
      <div class="form-group"><label class="label">Nhận xét</label><textarea class="input" style="min-height:4rem;" id="gm-comment" placeholder="Nhận xét cho học sinh..."></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeGradeModal()">Hủy</button>
      <button class="btn btn-primary" onclick="saveGrade()">Lưu điểm</button>
    </div>
  </div>
</div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
var colors=['#3b82f6','#ef4444','#22c55e','#f97316','#a855f7','#06b6d4','#ec4899','#eab308'];
var SUBS=[
  {id:1,student:'Nguyễn Văn An',assignment:'Bài tập Hàm số',cls:'10A',submitted:'14/04/2026',score:null,max:10,status:'pending'},
  {id:2,student:'Trần Thị Bình',assignment:'Bài tập Hàm số',cls:'10A',submitted:'14/04/2026',score:null,max:10,status:'pending'},
  {id:3,student:'Lê Hoàng Cường',assignment:'Thực hành Điện học',cls:'11B',submitted:'13/04/2026',score:null,max:10,status:'pending'},
  {id:4,student:'Phạm Minh Đức',assignment:'Bài tập Hàm số',cls:'10A',submitted:'15/04/2026',score:null,max:10,status:'pending'},
  {id:5,student:'Hoàng Thu Hà',assignment:'Kiểm tra Cuối kì Toán',cls:'10A',submitted:'12/04/2026',score:8.5,max:10,status:'graded'},
  {id:6,student:'Vũ Đình Khoa',assignment:'Kiểm tra Cuối kì Toán',cls:'10A',submitted:'12/04/2026',score:7.0,max:10,status:'graded'},
  {id:7,student:'Đỗ Thị Lan',assignment:'Thực hành Điện học',cls:'11B',submitted:'13/04/2026',score:null,max:10,status:'pending'},
  {id:8,student:'Bùi Văn Mạnh',assignment:'Sóng cơ học',cls:'11B',submitted:'11/04/2026',score:6.5,max:10,status:'graded'},
  {id:9,student:'Ngô Thị Ngọc',assignment:'Bài tập Hàm số',cls:'10A',submitted:'14/04/2026',score:null,max:10,status:'pending'},
  {id:10,student:'Trịnh Quốc Phong',assignment:'Sóng cơ học',cls:'11B',submitted:'11/04/2026',score:null,max:10,status:'pending'},
  {id:11,student:'Lý Thị Quỳnh',assignment:'Kiểm tra Cuối kì Toán',cls:'10A',submitted:'12/04/2026',score:9.0,max:10,status:'graded'},
  {id:12,student:'Đinh Văn Sơn',assignment:'Thực hành Điện học',cls:'11B',submitted:'13/04/2026',score:null,max:10,status:'pending'},
  {id:13,student:'Phan Thị Trang',assignment:'Bài tập Hàm số',cls:'10A',submitted:'15/04/2026',score:null,max:10,status:'pending'},
  {id:14,student:'Mai Xuân Uy',assignment:'Kiểm tra Cuối kì Toán',cls:'10A',submitted:'12/04/2026',score:7.5,max:10,status:'graded'},
  {id:15,student:'Cao Thị Vân',assignment:'Sóng cơ học',cls:'11B',submitted:'11/04/2026',score:null,max:10,status:'pending'},
  {id:16,student:'Hồ Anh Tuấn',assignment:'Thực hành Điện học',cls:'11B',submitted:'13/04/2026',score:8.0,max:10,status:'graded'},
  {id:17,student:'Tô Minh Yến',assignment:'Bài tập Hàm số',cls:'10A',submitted:'15/04/2026',score:null,max:10,status:'pending'},
  {id:18,student:'Dương Bảo Long',assignment:'Sóng cơ học',cls:'11B',submitted:'11/04/2026',score:null,max:10,status:'pending'},
  {id:19,student:'Lưu Thị Hương',assignment:'Kiểm tra Cuối kì Toán',cls:'10A',submitted:'12/04/2026',score:6.0,max:10,status:'graded'},
  {id:20,student:'Châu Đức Huy',assignment:'Thực hành Điện học',cls:'11B',submitted:'13/04/2026',score:null,max:10,status:'pending'},
  {id:21,student:'Nguyễn Hải Yến',assignment:'Bài tập Hàm số',cls:'10A',submitted:'14/04/2026',score:null,max:10,status:'pending'},
  {id:22,student:'Trần Minh Quân',assignment:'Kiểm tra Cuối kì Toán',cls:'10A',submitted:'12/04/2026',score:8.0,max:10,status:'graded'},
  {id:23,student:'Lê Văn Tâm',assignment:'Sóng cơ học',cls:'11B',submitted:'11/04/2026',score:null,max:10,status:'pending'},
  {id:24,student:'Phạm Thị Oanh',assignment:'Thực hành Điện học',cls:'11B',submitted:'13/04/2026',score:null,max:10,status:'pending'},
  {id:25,student:'Hoàng Đức Thịnh',assignment:'Bài tập Hàm số',cls:'10A',submitted:'15/04/2026',score:null,max:10,status:'pending'},
  {id:26,student:'Vũ Thị Kim',assignment:'Kiểm tra Cuối kì Toán',cls:'10A',submitted:'12/04/2026',score:9.5,max:10,status:'graded'},
  {id:27,student:'Đỗ Hoàng Nam',assignment:'Sóng cơ học',cls:'11B',submitted:'11/04/2026',score:7.0,max:10,status:'graded'},
  {id:28,student:'Bùi Thanh Hải',assignment:'Thực hành Điện học',cls:'11B',submitted:'13/04/2026',score:null,max:10,status:'pending'},
  {id:29,student:'Ngô Văn Phúc',assignment:'Bài tập Hàm số',cls:'10A',submitted:'14/04/2026',score:null,max:10,status:'pending'},
  {id:30,student:'Trịnh Thị Mai',assignment:'Kiểm tra Cuối kì Toán',cls:'10A',submitted:'12/04/2026',score:8.5,max:10,status:'graded'}
];
var curTab='pending';

function getIni(n){return n.split(' ').filter(Boolean).map(function(w){return w[0];}).slice(-2).join('').toUpperCase();}

document.getElementById('tab-bar').addEventListener('click',function(e){
  var b=e.target.closest('.tab-btn');if(!b)return;
  document.querySelectorAll('.tab-btn').forEach(function(x){x.classList.remove('active');});
  b.classList.add('active');curTab=b.getAttribute('data-tab');applyFilters();
});

function render(data){
  var c=document.getElementById('glist');
  document.getElementById('g-cnt').textContent=data.length+' bài nộp';
  if(!data.length){c.innerHTML='<div style="padding:3rem;text-align:center;color:var(--muted-foreground);"><div style="font-size:3rem;margin-bottom:.75rem;">✅</div><h3 style="font-size:var(--text-xl);font-weight:600;color:var(--foreground);">Không có bài nào</h3><p>Đã chấm hết hoặc thay đổi bộ lọc</p></div>';return;}
  var grouped={};
  data.forEach(function(s){if(!grouped[s.assignment])grouped[s.assignment]=[];grouped[s.assignment].push(s);});
  var html='';
  Object.keys(grouped).forEach(function(asgn){
    var items=grouped[asgn];
    var pendCnt=items.filter(function(s){return!s.score&&s.score!==0;}).length;
    html+='<div class="grade-card" style="margin-bottom:.75rem;">'
      +'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">'
      +'<div><h3 style="font-weight:600;font-size:var(--text-base);">'+asgn+'</h3>'
      +'<span style="font-size:var(--text-sm);color:var(--muted-foreground);">'+items.length+' bài nộp'+(pendCnt?' · '+pendCnt+' chờ chấm':'')+'</span></div>'
      +'</div>'
      +'<div style="border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;">';
    items.forEach(function(s,i){
      var ci=colors[i%colors.length];
      var scoreHTML=s.score!==null?'<span style="font-weight:700;color:'+(s.score>=7?'var(--success)':s.score>=5?'var(--warning)':'var(--destructive)')+';">'+s.score.toFixed(1)+'/'+s.max+'</span>':'<span class="badge badge-warning" style="font-size:var(--text-xs);">Chờ chấm</span>';
      var btnHTML=s.score!==null?'<button class="btn btn-ghost btn-sm" onclick="openGrade('+s.id+')">Sửa điểm</button>':'<button class="btn btn-primary btn-sm" onclick="openGrade('+s.id+')">Chấm điểm</button>';
      html+='<div class="student-row">'
        +'<div class="avatar-sm" style="background:'+ci+'22;color:'+ci+';">'+getIni(s.student)+'</div>'
        +'<div style="flex:1;"><div style="font-weight:500;">'+s.student+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Lớp '+s.cls+' · Nộp: '+s.submitted+'</div></div>'
        +'<div style="min-width:5rem;text-align:right;">'+scoreHTML+'</div>'
        +'<div>'+btnHTML+'</div>'
        +'</div>';
    });
    html+='</div></div>';
  });
  c.innerHTML=html;
}

window.applyFilters=function(){
  var s=(document.getElementById('g-search').value||'').toLowerCase();
  var a=document.getElementById('g-asgn').value;
  render(SUBS.filter(function(x){
    var mt=curTab==='pending'?(x.score===null):(x.score!==null);
    return mt&&(!s||x.student.toLowerCase().indexOf(s)!==-1)&&(!a||x.assignment===a);
  }));
};

window.openGrade=function(id){
  var s=SUBS.find(function(x){return x.id===id;});if(!s)return;
  document.getElementById('gm-id').value=id;
  document.getElementById('gm-title').textContent='Chấm điểm — '+s.student;
  document.getElementById('gm-sub').textContent=s.assignment+' · Lớp '+s.cls;
  document.getElementById('gm-score').value=s.score!==null?s.score:'';
  document.getElementById('gm-max').value=s.max+' điểm';
  document.getElementById('gm-comment').value='';
  document.getElementById('grade-modal').classList.add('open');
  setTimeout(function(){document.getElementById('gm-score').focus();},100);
};
window.closeGradeModal=function(){document.getElementById('grade-modal').classList.remove('open');};
window.saveGrade=function(){
  var id=parseInt(document.getElementById('gm-id').value);
  var sc=parseFloat(document.getElementById('gm-score').value);
  if(isNaN(sc)||sc<0||sc>10){toast('Điểm phải từ 0-10','err');return;}
  var s=SUBS.find(function(x){return x.id===id;});
  if(s){s.score=sc;s.status='graded';}
  closeGradeModal();updSt();applyFilters();toast('Đã lưu điểm '+sc.toFixed(1));
};

function updSt(){
  var pend=SUBS.filter(function(s){return s.score===null;}).length;
  var graded=SUBS.filter(function(s){return s.score!==null;}).length;
  var scores=SUBS.filter(function(s){return s.score!==null;}).map(function(s){return s.score;});
  var avg=scores.length?(scores.reduce(function(a,b){return a+b;},0)/scores.length).toFixed(1):'—';
  document.getElementById('st-pend').textContent=pend;
  document.getElementById('st-today').textContent=graded;
  document.getElementById('st-avg').textContent=avg;
  document.getElementById('st-total').textContent=graded;
  document.getElementById('tc-p').textContent=pend;
  document.getElementById('tc-g').textContent=graded;
}

function toast(m,t){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-'+(t==='err'?'error':'success');e.innerHTML='<span>'+(t==='err'?'❌':'✅')+'</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}

document.getElementById('grade-modal').addEventListener('click',function(e){if(e.target===this)closeGradeModal();});
updSt();applyFilters();
})();
</script>
@endpush
