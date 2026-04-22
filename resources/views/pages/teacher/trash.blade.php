{{-- Teacher: trash --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@section('content')
  <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1>Thùng rác</h1>
            <p style="color:var(--muted-foreground);">Các mục đã xóa sẽ bị xóa vĩnh viễn sau 30 ngày</p>
          </div>
          <div style="display:flex;gap:0.5rem;">
            <button class="btn btn-outline btn-sm" onclick="restoreAll()">Khôi phục tất cả</button>
            <button class="btn btn-destructive btn-sm" onclick="deleteAll()">Xóa vĩnh viễn tất cả</button>
          </div>
        </div>
      </div>

      <div class="alert alert-warning stagger-children" style="margin-bottom:1.25rem;">
        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span style="font-size:var(--text-sm);">Các mục đã xóa trong thùng rác sẽ bị xóa vĩnh viễn sau <strong>30 ngày</strong>. Hãy khôi phục những gì bạn cần trước đó.</span>
      </div>

      <div class="tabs-list stagger-children" style="margin-bottom:1.25rem;max-width:400px;">
        <button class="tab-trigger active" onclick="filterTrash('all',this)">Tất cả (7)</button>
        <button class="tab-trigger" onclick="filterTrash('quiz',this)">Đề thi</button>
        <button class="tab-trigger" onclick="filterTrash('assignment',this)">Bài tập</button>
        <button class="tab-trigger" onclick="filterTrash('question',this)">Câu hỏi</button>
      </div>

      <div class="card stagger-children">
        <div class="table-wrapper" style="border:none;border-radius:0;">
          <table>
            <thead>
              <tr>
                <th><input type="checkbox" id="select-all" onchange="toggleAll(this)" /></th>
                <th>Tên mục</th>
                <th>Loại</th>
                <th>Ngày xóa</th>
                <th>Xóa bởi</th>
                <th>Còn lại</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="trash-table"></tbody>
          </table>
        </div>
        <div id="trash-empty" class="empty-state" style="display:none;padding:3rem;">
          <div style="font-size:3rem;">🗑️</div>
          <h3>Thùng rác trống</h3>
          <p>Không có mục nào trong thùng rác</p>
        </div>
      </div>

<div class="modal-overlay" id="del-modal"><div class="modal" style="max-width:28rem;"><div class="modal-header"><div><h3 class="modal-title">Xác nhận</h3></div><button class="modal-close" onclick="closeDel()">✕</button></div><div class="modal-body"><p id="del-msg" style="color:var(--muted-foreground);"></p></div><div class="modal-footer"><button class="btn btn-outline" onclick="closeDel()">Hủy</button><button class="btn btn-destructive" id="del-confirm">Xóa</button></div></div></div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
var TBADGE={quiz:'badge-primary',assignment:'badge-warning',question:'badge-success'};
var TLABEL={quiz:'Đề thi',assignment:'Bài tập',question:'Câu hỏi'};
var ITEMS=[
  {id:1,name:'Kiểm tra Giữa kỳ Toán 10A',type:'quiz',deleted:'28/03/2026',by:'Giáo viên Demo',daysLeft:27},
  {id:2,name:'Bài tập Hóa học Chương 5',type:'assignment',deleted:'25/03/2026',by:'Giáo viên Demo',daysLeft:24},
  {id:3,name:'Câu hỏi: Phương trình bậc 3',type:'question',deleted:'22/03/2026',by:'Giáo viên Demo',daysLeft:21},
  {id:4,name:'Kiểm tra Cuối kỳ Vật lý',type:'quiz',deleted:'20/03/2026',by:'Giáo viên Demo',daysLeft:19},
  {id:5,name:'Bài tập Sinh học Chương 2',type:'assignment',deleted:'15/03/2026',by:'Giáo viên Demo',daysLeft:14},
  {id:6,name:'Câu hỏi: Định luật Newton',type:'question',deleted:'10/03/2026',by:'Giáo viên Demo',daysLeft:9},
  {id:7,name:'Kiểm tra trắc nghiệm Lịch sử',type:'quiz',deleted:'05/03/2026',by:'Giáo viên Demo',daysLeft:4}
];
var curFilter='all';
function render(){
  var data=curFilter==='all'?ITEMS:ITEMS.filter(function(i){return i.type===curFilter;});
  var tb=document.getElementById('trash-table'),em=document.getElementById('trash-empty');
  if(!data.length){tb.innerHTML='';em.style.display='';return;}
  em.style.display='none';
  tb.innerHTML=data.map(function(i){
    var u=i.daysLeft<=7;
    return '<tr>'
      +'<td><input type="checkbox" class="item-check" value="'+i.id+'" /></td>'
      +'<td style="font-weight:500;font-size:var(--text-sm);">'+i.name+'</td>'
      +'<td><span class="badge '+TBADGE[i.type]+'">'+TLABEL[i.type]+'</span></td>'
      +'<td style="font-size:var(--text-sm);color:var(--muted-foreground);">'+i.deleted+'</td>'
      +'<td style="font-size:var(--text-sm);color:var(--muted-foreground);">'+i.by+'</td>'
      +'<td><span style="font-size:var(--text-sm);font-weight:600;color:'+(u?'var(--destructive)':'var(--muted-foreground)')+';">'+i.daysLeft+' ngày</span></td>'
      +'<td><div style="display:flex;gap:.25rem;"><button class="btn btn-ghost btn-sm" onclick="restoreItem('+i.id+')">Khôi phục</button><button class="btn btn-ghost btn-sm" style="color:var(--destructive);" onclick="confirmDel('+i.id+')">Xóa</button></div></td>'
      +'</tr>';
  }).join('');
}
window.filterTrash=function(type,el){document.querySelectorAll('.tab-trigger').forEach(function(b){b.classList.remove('active');});el.classList.add('active');curFilter=type;render();};
window.toggleAll=function(cb){document.querySelectorAll('.item-check').forEach(function(c){c.checked=cb.checked;});};
window.restoreItem=function(id){ITEMS=ITEMS.filter(function(i){return i.id!==id;});render();toast('Đã khôi phục mục thành công');};
window.confirmDel=function(id){var it=ITEMS.find(function(i){return i.id===id;});if(!it)return;document.getElementById('del-msg').textContent='Xóa vĩnh viễn "'+it.name+'"? Không thể hoàn tác!';document.getElementById('del-confirm').onclick=function(){ITEMS=ITEMS.filter(function(i){return i.id!==id;});closeDel();render();toast('Đã xóa vĩnh viễn');};document.getElementById('del-modal').classList.add('open');};
window.restoreAll=function(){if(!ITEMS.length)return;ITEMS=[];render();toast('Đã khôi phục tất cả mục');};
window.deleteAll=function(){if(!ITEMS.length)return;document.getElementById('del-msg').textContent='Xóa vĩnh viễn TẤT CẢ '+ITEMS.length+' mục? Không thể hoàn tác!';document.getElementById('del-confirm').onclick=function(){ITEMS=[];closeDel();render();toast('Đã xóa vĩnh viễn tất cả');};document.getElementById('del-modal').classList.add('open');};
window.closeDel=function(){document.getElementById('del-modal').classList.remove('open');};
document.getElementById('del-modal').addEventListener('click',function(e){if(e.target===this)closeDel();});
function toast(m){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-success';e.innerHTML='<span>✅</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
render();
})();
</script>
@endpush
