{{-- Student: assignments --}}
@extends('layouts.dashboard', ['role' => 'student'])

@section('content')
  <div class="page-header stagger-children">
        <h1>Bài tập</h1>
        <p style="color:var(--muted-foreground);">Danh sách bài tập được giao từ các khóa học</p>
      </div>

      <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đến hạn tuần này</div><div class="stat-card__value" style="color:var(--destructive);">2</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đang làm</div><div class="stat-card__value" style="color:var(--warning);">3</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đã nộp</div><div class="stat-card__value" style="color:var(--success);">12</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB Bài tập</div><div class="stat-card__value">84.2%</div></div>
      </div>

      <!-- Filter -->
      <div class="toolbar stagger-children">
        <div class="toolbar-left">
          <div class="tabs-list" style="max-width:420px;">
            <button class="tab-trigger active" onclick="filterStatus('all',this)">Tất cả</button>
            <button class="tab-trigger" onclick="filterStatus('pending',this)">Chưa nộp</button>
            <button class="tab-trigger" onclick="filterStatus('submitted',this)">Đã nộp</button>
            <button class="tab-trigger" onclick="filterStatus('graded',this)">Đã chấm</button>
          </div>
        </div>
      </div>

      <div id="assignments-list" class="stagger-children" style="display:flex;flex-direction:column;gap:.875rem;"></div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var ITEMS=[
    {id:1,title:'Xây dựng Thư viện Component React',course:'Phát triển Web',due:'Ngày mai 17:00',points:100,status:'pending',score:null,feedback:null,type:'project',urgent:true},
    {id:2,title:'Thiết kế Sơ đồ ER hệ thống',course:'Thiết kế CSDL',due:'05/04/2026',points:80,status:'pending',score:null,feedback:null,type:'essay',urgent:false},
    {id:3,title:'Cài đặt Cây Tìm kiếm Nhị phân',course:'Cấu trúc Dữ liệu',due:'08/04/2026',points:100,status:'submitted',score:null,feedback:null,type:'code',urgent:false},
    {id:4,title:'Báo cáo so sánh thuật toán sắp xếp',course:'Cấu trúc Dữ liệu',due:'01/04/2026',points:50,status:'pending',score:null,feedback:null,type:'essay',urgent:true},
    {id:5,title:'Truy vấn SQL Nâng cao',course:'Thiết kế CSDL',due:'28/03/2026',points:60,status:'graded',score:48,feedback:'Truy vấn tốt, cần tối ưu index.',type:'essay',urgent:false},
    {id:6,title:'Trang web Portfolio cá nhân',course:'Phát triển Web',due:'25/03/2026',points:150,status:'graded',score:138,feedback:'Thiết kế đẹp, responsive tốt!',type:'project',urgent:false},
    {id:7,title:'Phân tích giao thức TCP/IP',course:'Mạng Máy tính',due:'20/03/2026',points:40,status:'graded',score:35,feedback:'Phân tích đúng, cần thêm ví dụ.',type:'essay',urgent:false}
  ];
  var TYPE_ICONS={project:'🚀',essay:'📝',code:'💻',practice:'🔬'};
  var STATUS_CONFIG={pending:{label:'Chưa nộp',badge:'badge-warning',action:'Nộp bài'},submitted:{label:'Đã nộp',badge:'badge-info',action:'Xem bài nộp'},graded:{label:'Đã chấm',badge:'badge-success',action:'Xem kết quả'}};
  function render(data){
    var el=document.getElementById('assignments-list');
    if(!data.length){el.innerHTML='<div class="empty-state"><div style="font-size:3rem;">📋</div><h3>Không có bài tập nào</h3></div>';return;}
    el.innerHTML=data.map(function(a){
      var s=STATUS_CONFIG[a.status];
      var pct=a.score!==null?Math.round((a.score/a.points)*100):null;
      var urgentBadge=a.urgent?'<span class="badge badge-danger">🔥 Gấp</span>':'';
      var scoreHtml='';
      if(a.score!==null){
        var sc=pct>=90?'var(--success)':pct>=70?'var(--info)':'var(--warning)';
        scoreHtml='<div style="margin-top:.75rem;display:flex;align-items:center;gap:.75rem;"><span style="font-weight:700;color:'+sc+';">'+a.score+'/'+a.points+' điểm ('+pct+'%)</span><div class="progress" style="width:120px;"><div class="progress-bar" style="width:'+pct+'%;background:'+sc+';"></div></div></div>';
        if(a.feedback)scoreHtml+='<p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:.375rem;font-style:italic;">"'+a.feedback+'"</p>';
      }
      var btnCls=a.status==='pending'?'btn-primary':'btn-outline';
      return '<div class="card"><div class="card-content" style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;"><div style="font-size:2rem;flex-shrink:0;">'+TYPE_ICONS[a.type]+'</div><div style="flex:1;"><div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.375rem;"><h3 style="font-size:var(--text-base);font-weight:600;">'+a.title+'</h3>'+urgentBadge+'<span class="badge '+s.badge+'">'+s.label+'</span></div><div style="font-size:var(--text-sm);color:var(--muted-foreground);display:flex;gap:1rem;flex-wrap:wrap;"><span>📚 '+a.course+'</span><span>📅 Hạn: '+a.due+'</span><span>⭐ '+a.points+' điểm</span></div>'+scoreHtml+'</div><button class="btn '+btnCls+' btn-sm" onclick="handleAction('+a.id+',\''+a.status+'\')" style="flex-shrink:0;">'+s.action+'</button></div></div>';
    }).join('');
  }
  window.filterStatus=function(status,el){
    document.querySelectorAll('.tab-trigger').forEach(function(b){b.classList.remove('active');});
    el.classList.add('active');
    render(status==='all'?ITEMS:ITEMS.filter(function(a){return a.status===status;}));
  };
  window.handleAction=function(id,status){
    toast(status==='pending'?'Mở trang nộp bài':status==='submitted'?'Xem bài đã nộp':'Xem kết quả chi tiết');
  };
  function toast(m){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-success';e.innerHTML='<span>✅</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
  render(ITEMS);
})();
</script>
@endpush
