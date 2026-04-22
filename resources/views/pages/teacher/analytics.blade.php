{{-- Teacher: analytics --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.chart-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1.5rem}
    .chart-title{font-weight:600;font-size:var(--text-base);margin-bottom:1rem}
    .bar-chart{display:flex;align-items:flex-end;gap:.5rem;height:180px;padding-top:.5rem}
    .bar-col{display:flex;flex-direction:column;align-items:center;flex:1;gap:.25rem;height:100%}
    .bar-fill{width:100%;border-radius:var(--radius-sm) var(--radius-sm) 0 0;transition:height .6s ease;min-width:1.5rem}
    .bar-label{font-size:var(--text-xs);color:var(--muted-foreground);text-align:center;white-space:nowrap}
    .bar-val{font-size:var(--text-xs);font-weight:700}
    .donut-row{display:flex;gap:1rem;align-items:center}
    .donut-legend{display:flex;flex-direction:column;gap:.5rem}
    .leg-item{display:flex;align-items:center;gap:.5rem;font-size:var(--text-sm)}
    .leg-dot{width:.625rem;height:.625rem;border-radius:50%;flex-shrink:0}
    .rank-row{display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid var(--border)}
    .rank-row:last-child{border-bottom:none}
    .rank-num{width:1.5rem;text-align:center;font-weight:700;font-size:var(--text-sm)}
    .rank-ava{width:2rem;height:2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:var(--text-xs);font-weight:700;flex-shrink:0}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div><h1>Phân tích</h1><p style="color:var(--muted-foreground);">Tổng hợp dữ liệu và hiệu suất giảng dạy</p></div>
          <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <select class="input select" id="period" style="max-width:160px;font-size:var(--text-sm);"><option>Tuần này</option><option selected>Tháng này</option><option>Quý này</option><option>Năm nay</option></select>
            <button class="btn btn-outline btn-sm gap-2" onclick="exportCSV()">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Xuất báo cáo
            </button>
          </div>
        </div>
      </div>
      <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB</div><div class="stat-card__value">7.4</div><div class="stat-card__trend up">↑ +0.3 vs tháng trước</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tỉ lệ hoàn thành</div><div class="stat-card__value" style="color:var(--success);">86%</div><div class="stat-card__trend up">↑ +5%</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài kiểm tra</div><div class="stat-card__value" style="color:var(--primary);">12</div><div class="stat-card__trend">Tháng này</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Học sinh hoạt động</div><div class="stat-card__value" style="color:var(--info);">94</div><div class="stat-card__trend up">↑ +8</div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;" class="stagger-children">
        <div class="chart-card">
          <h3 class="chart-title">Điểm trung bình theo lớp</h3>
          <div class="bar-chart" id="bar1"></div>
        </div>
        <div class="chart-card">
          <h3 class="chart-title">Phân bố xếp loại</h3>
          <div class="donut-row" id="donut1"></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;" class="stagger-children">
        <div class="chart-card">
          <h3 class="chart-title">Điểm TB theo môn</h3>
          <div class="bar-chart" id="bar2"></div>
        </div>
        <div class="chart-card">
          <h3 class="chart-title">Top học sinh xuất sắc</h3>
          <div id="top-students"></div>
        </div>
      </div>

      <div class="chart-card stagger-children" style="margin-bottom:1.25rem;">
        <h3 class="chart-title">Xu hướng điểm TB theo tuần (6 tuần gần nhất)</h3>
        <div class="bar-chart" id="bar3" style="height:140px;"></div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
var classBars=[{label:'10A',val:7.8,color:'#3b82f6'},{label:'11B',val:7.2,color:'#8b5cf6'},{label:'9C',val:5.8,color:'#f97316'},{label:'10B',val:7.4,color:'#06b6d4'},{label:'12A',val:8.1,color:'#22c55e'}];
var subjectBars=[{label:'Toán',val:7.6,color:'#3b82f6'},{label:'Vật lý',val:7.0,color:'#8b5cf6'},{label:'Hóa học',val:6.8,color:'#f97316'},{label:'Sinh học',val:7.9,color:'#22c55e'},{label:'Ngữ văn',val:7.2,color:'#ec4899'}];
var weekBars=[{label:'T1',val:7.0,color:'#3b82f6'},{label:'T2',val:7.1,color:'#3b82f6'},{label:'T3',val:6.8,color:'#3b82f6'},{label:'T4',val:7.3,color:'#3b82f6'},{label:'T5',val:7.5,color:'#3b82f6'},{label:'T6',val:7.4,color:'#3b82f6'}];
var donutData=[{label:'Giỏi (≥8)',pct:35,color:'#22c55e'},{label:'Khá (6-7.9)',pct:40,color:'#3b82f6'},{label:'TB (5-5.9)',pct:18,color:'#f97316'},{label:'Yếu (<5)',pct:7,color:'#ef4444'}];
var topStudents=[{name:'Ngô Thị Ngọc',cls:'10A',avg:9.3,color:'#f97316'},{name:'Lê Hoàng Cường',cls:'11B',avg:9.1,color:'#3b82f6'},{name:'Cao Thị Vân',cls:'12A',avg:9.0,color:'#22c55e'},{name:'Lý Thị Quỳnh',cls:'10A',avg:8.7,color:'#8b5cf6'},{name:'Nguyễn Văn An',cls:'10A',avg:8.5,color:'#06b6d4'}];

function drawBars(id,data,maxVal){
  var mx=maxVal||Math.max.apply(null,data.map(function(d){return d.val;}))+1;
  var c=document.getElementById(id);
  c.innerHTML=data.map(function(d){
    var h=Math.round((d.val/mx)*100);
    return '<div class="bar-col"><div class="bar-val" style="color:'+d.color+';">'+d.val.toFixed(1)+'</div><div style="flex:1;display:flex;align-items:flex-end;width:100%;"><div class="bar-fill" style="height:'+h+'%;background:'+d.color+';opacity:0.85;"></div></div><div class="bar-label">'+d.label+'</div></div>';
  }).join('');
}

function drawDonut(id,data){
  var total=data.reduce(function(a,b){return a+b.pct;},0);
  var svg='<svg width="140" height="140" viewBox="0 0 140 140">';
  var cum=0;
  data.forEach(function(d){
    var r=55,cx=70,cy=70,circ=2*Math.PI*r;
    var dash=(d.pct/total)*circ;
    var gap=circ-dash;
    var off=-cum*circ/total+circ*0.25;
    svg+='<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="none" stroke="'+d.color+'" stroke-width="18" stroke-dasharray="'+dash+' '+gap+'" stroke-dashoffset="'+off+'" />';
    cum+=d.pct;
  });
  svg+='<text x="70" y="70" text-anchor="middle" dominant-baseline="central" style="font-size:18px;font-weight:700;fill:var(--foreground);">'+total+'%</text>';
  svg+='</svg>';
  var legend='<div class="donut-legend">'+data.map(function(d){return '<div class="leg-item"><div class="leg-dot" style="background:'+d.color+';"></div><span>'+d.label+' <strong>'+d.pct+'%</strong></span></div>';}).join('')+'</div>';
  document.getElementById(id).innerHTML=svg+legend;
}

function drawTop(id,data){
  var medals=['🥇','🥈','🥉','4','5'];
  function ini(n){return n.split(' ').filter(Boolean).map(function(w){return w[0];}).slice(-2).join('').toUpperCase();}
  document.getElementById(id).innerHTML=data.map(function(s,i){
    return '<div class="rank-row"><div class="rank-num">'+(i<3?medals[i]:(i+1))+'</div><div class="rank-ava" style="background:'+s.color+'22;color:'+s.color+';">'+ini(s.name)+'</div><div style="flex:1;"><div style="font-weight:500;font-size:var(--text-sm);">'+s.name+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Lớp '+s.cls+'</div></div><span style="font-weight:700;color:var(--success);">'+s.avg.toFixed(1)+'</span></div>';
  }).join('');
}

drawBars('bar1',classBars,10);
drawBars('bar2',subjectBars,10);
drawBars('bar3',weekBars,10);
drawDonut('donut1',donutData);
drawTop('top-students',topStudents);

window.exportCSV=function(){
  var tc=document.getElementById('toast-container');
  if(!tc)return;
  var e=document.createElement('div');
  e.className='toast toast-success';
  e.innerHTML='<span>✅</span><span>Đã xuất báo cáo phân tích thành công!</span>';
  tc.appendChild(e);
  setTimeout(function(){e.classList.add('show');},10);
  setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);
};
})();
</script>
@endpush
