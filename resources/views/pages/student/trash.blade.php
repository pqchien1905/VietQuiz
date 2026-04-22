{{-- Student: trash --}}
@extends('layouts.dashboard', ['role' => 'student'])

@section('content')
  <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1>Thùng rác</h1>
            <p style="color:var(--muted-foreground);">Các mục đã xóa sẽ bị xóa vĩnh viễn sau 30 ngày</p>
          </div>
          <div style="display:flex;gap:0.5rem;">
            <button class="btn btn-outline btn-sm" id="btn-restore-all">Khôi phục tất cả</button>
            <button class="btn btn-destructive btn-sm" id="btn-delete-all">Xóa vĩnh viễn tất cả</button>
          </div>
        </div>
      </div>

      <div class="alert alert-warning stagger-children" style="margin-bottom:1.25rem;">
        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span style="font-size:var(--text-sm);">Các mục đã xóa trong thùng rác sẽ bị xóa vĩnh viễn sau <strong>30 ngày</strong>. Hãy khôi phục những gì bạn cần trước đó.</span>
      </div>

      <div class="tabs-list stagger-children" id="filter-tabs" style="margin-bottom:1.25rem;max-width:400px;">
        <button class="tab-trigger active" data-filter="all">Tất cả (5)</button>
        <button class="tab-trigger" data-filter="quiz">Đề thi</button>
        <button class="tab-trigger" data-filter="assignment">Bài tập</button>
        <button class="tab-trigger" data-filter="question">Câu hỏi</button>
      </div>

      <div class="card stagger-children">
        <div class="table-wrapper" style="border:none;border-radius:0;">
          <table>
            <thead>
              <tr>
                <th style="width:2.5rem;"><input type="checkbox" id="select-all" /></th>
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
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var TYPE_BADGE={quiz:'badge-primary',assignment:'badge-warning',question:'badge-success'};
  var TYPE_LABEL={quiz:'Đề thi',assignment:'Bài tập',question:'Câu hỏi'};
  var ITEMS=[
    {id:1,name:'Bài kiểm tra Cấu trúc Dữ liệu',type:'quiz',deleted:'28/03/2026',by:'Học sinh Demo',daysLeft:27},
    {id:2,name:'Bài tập Hóa học Chương 5',type:'assignment',deleted:'25/03/2026',by:'Học sinh Demo',daysLeft:24},
    {id:3,name:'Câu hỏi: Phương trình bậc 3',type:'question',deleted:'22/03/2026',by:'Học sinh Demo',daysLeft:21},
    {id:4,name:'Bài kiểm tra Vật lý Điện học',type:'quiz',deleted:'20/03/2026',by:'Học sinh Demo',daysLeft:19},
    {id:5,name:'Bài tập Sinh học Chương 2',type:'assignment',deleted:'15/03/2026',by:'Học sinh Demo',daysLeft:14}
  ];
  var filtered=ITEMS.slice();
  function render(data){
    var tbody=document.getElementById('trash-table');
    var empty=document.getElementById('trash-empty');
    if(!data.length){tbody.innerHTML='';empty.style.display='';return;}
    empty.style.display='none';
    tbody.innerHTML=data.map(function(i){
      var urgent=i.daysLeft<=7;
      var urgentColor=urgent?'var(--destructive)':'var(--muted-foreground)';
      return '<tr><td><input type="checkbox" class="item-check" value="'+i.id+'" /></td><td style="font-weight:500;font-size:var(--text-sm);">'+i.name+'</td><td><span class="badge '+TYPE_BADGE[i.type]+'">'+TYPE_LABEL[i.type]+'</span></td><td style="font-size:var(--text-sm);color:var(--muted-foreground);">'+i.deleted+'</td><td style="font-size:var(--text-sm);color:var(--muted-foreground);">'+i.by+'</td><td><span style="font-size:var(--text-sm);font-weight:600;color:'+urgentColor+';">'+i.daysLeft+' ngày</span></td><td><div style="display:flex;gap:0.25rem;"><button class="btn btn-ghost btn-sm" data-action="restore" data-id="'+i.id+'">Khôi phục</button><button class="btn btn-ghost btn-sm" style="color:var(--destructive);" data-action="delete" data-id="'+i.id+'">Xóa</button></div></td></tr>';
    }).join('');
  }
  document.getElementById('filter-tabs').addEventListener('click',function(e){
    var btn=e.target.closest('.tab-trigger');if(!btn)return;
    document.querySelectorAll('#filter-tabs .tab-trigger').forEach(function(b){b.classList.remove('active');});
    btn.classList.add('active');
    var type=btn.dataset.filter;
    filtered=type==='all'?ITEMS:ITEMS.filter(function(i){return i.type===type;});
    render(filtered);
  });
  document.getElementById('select-all').addEventListener('change',function(cb){
    document.querySelectorAll('.item-check').forEach(function(c){c.checked=cb.target.checked;});
  });
  document.getElementById('trash-table').addEventListener('click',function(e){
    var btn=e.target.closest('[data-action]');if(!btn)return;
    var id=parseInt(btn.dataset.id);
    if(btn.dataset.action==='restore'){
      ITEMS=ITEMS.filter(function(i){return i.id!==id;});filtered=filtered.filter(function(i){return i.id!==id;});render(filtered);updateTabCounts();toast('Đã khôi phục mục thành công');
    }else if(btn.dataset.action==='delete'){
      if(!confirm('Xóa vĩnh viễn mục này? Không thể hoàn tác!'))return;
      ITEMS=ITEMS.filter(function(i){return i.id!==id;});filtered=filtered.filter(function(i){return i.id!==id;});render(filtered);updateTabCounts();toast('Đã xóa vĩnh viễn');
    }
  });
  document.getElementById('btn-restore-all').addEventListener('click',function(){
    if(!ITEMS.length)return;ITEMS=[];filtered=[];render([]);updateTabCounts();toast('Đã khôi phục tất cả mục');
  });
  document.getElementById('btn-delete-all').addEventListener('click',function(){
    if(!ITEMS.length)return;if(!confirm('Xóa vĩnh viễn TẤT CẢ mục trong thùng rác? Không thể hoàn tác!'))return;
    ITEMS=[];filtered=[];render([]);updateTabCounts();toast('Đã xóa vĩnh viễn tất cả mục');
  });
  function updateTabCounts(){
    var tabs=document.querySelectorAll('#filter-tabs .tab-trigger');
    tabs[0].textContent='Tất cả ('+ITEMS.length+')';
    tabs[1].textContent='Đề thi ('+ITEMS.filter(function(i){return i.type==='quiz';}).length+')';
    tabs[2].textContent='Bài tập ('+ITEMS.filter(function(i){return i.type==='assignment';}).length+')';
    tabs[3].textContent='Câu hỏi ('+ITEMS.filter(function(i){return i.type==='question';}).length+')';
  }
  function toast(m){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-success';e.innerHTML='<span>✅</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
  render(ITEMS);
})();
</script>
@endpush
