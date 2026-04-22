{{-- Student: grades --}}
@extends('layouts.dashboard', ['role' => 'student'])

@section('content')
  <div class="page-header stagger-children">
        <h1>Điểm số</h1>
        <p style="color:var(--muted-foreground);">Theo dõi kết quả học tập trên tất cả các khóa học</p>
      </div>

      <!-- Stats -->
      <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Điểm TB Tổng thể</div><div class="stat-card__value" style="color:var(--success);">82.5%</div><div class="stat-card__trend up">↑ B+</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Đã chấm điểm</div><div class="stat-card__value">18</div><div class="stat-card__label">bài đã chấm</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Chờ chấm điểm</div><div class="stat-card__value" style="color:var(--warning);">3</div><div class="stat-card__label">bài chờ</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Chưa nộp</div><div class="stat-card__value" style="color:var(--destructive);">2</div><div class="stat-card__label">bài chưa nộp</div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;" class="stagger-children">
        <!-- Course performance chart -->
        <div class="card">
          <div class="card-header"><h3 class="card-title">Hiệu suất Khóa học</h3><p class="card-description">Điểm trung bình của bạn trong mỗi khóa học</p></div>
          <div class="card-content"><canvas id="courseChart" height="200"></canvas></div>
        </div>
        <!-- Trend -->
        <div class="card">
          <div class="card-header"><h3 class="card-title">Xu hướng Điểm số</h3><p class="card-description">Điểm trung bình theo tuần</p></div>
          <div class="card-content"><canvas id="trendChart" height="200"></canvas></div>
        </div>
      </div>

      <!-- Grades table -->
      <div class="card stagger-children">
        <div class="card-header">
          <div class="flex items-center justify-between flex-wrap gap-4">
            <div><h3 class="card-title">Tất cả Điểm số</h3><p class="card-description">Xem chi tiết tất cả điểm số của bạn</p></div>
            <div style="display:flex;gap:0.5rem;">
              <select class="input select" style="width:auto;" id="filter-course">
                <option value="all">Tất cả Khóa học</option>
                <option>Phát triển Web</option>
                <option>Cấu trúc Dữ liệu</option>
                <option>Thiết kế CSDL</option>
              </select>
              <select class="input select" style="width:auto;" id="filter-type">
                <option value="all">Tất cả Loại</option>
                <option value="quiz">Bài thi</option>
                <option value="assignment">Bài tập</option>
              </select>
            </div>
          </div>
        </div>
        <div class="table-wrapper" style="border:none;border-radius:0;">
          <table>
            <thead>
              <tr>
                <th>Tên bài</th>
                <th>Khóa học</th>
                <th>Loại</th>
                <th>Điểm</th>
                <th>Trọng số</th>
                <th>Kết quả</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody id="grades-table"></tbody>
          </table>
        </div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var GRADES=[
    {name:'Thi Giữa kỳ',course:'Phát triển Web',type:'quiz',score:88,max:100,weight:'30%',status:'graded'},
    {name:'Thư viện Component React',course:'Phát triển Web',type:'assignment',score:92,max:100,weight:'20%',status:'graded'},
    {name:'Trắc nghiệm HTML/CSS',course:'Phát triển Web',type:'quiz',score:78,max:100,weight:'10%',status:'graded'},
    {name:'Cây Tìm kiếm Nhị phân',course:'Cấu trúc Dữ liệu',type:'assignment',score:null,max:100,weight:'25%',status:'pending'},
    {name:'Trắc nghiệm Thuật toán Sắp xếp',course:'Cấu trúc Dữ liệu',type:'quiz',score:85,max:100,weight:'15%',status:'graded'},
    {name:'Thiết kế Sơ đồ CSDL',course:'Thiết kế CSDL',type:'assignment',score:null,max:100,weight:'30%',status:'not_submitted'},
    {name:'Trắc nghiệm Truy vấn SQL',course:'Thiết kế CSDL',type:'quiz',score:72,max:100,weight:'20%',status:'graded'},
    {name:'Thi Cuối kỳ',course:'Phát triển Web',type:'quiz',score:90,max:100,weight:'40%',status:'graded'}
  ];
  var STATUS_MAP={graded:'<span class="badge badge-success">Đã chấm</span>',pending:'<span class="badge badge-warning">Chờ chấm</span>',not_submitted:'<span class="badge badge-danger">Chưa nộp</span>'};
  function getGrade(pct){if(pct>=90)return{g:'A',c:'var(--success)'};if(pct>=80)return{g:'B',c:'var(--info)'};if(pct>=65)return{g:'C',c:'var(--warning)'};return{g:'F',c:'var(--destructive)'};}
  document.getElementById('grades-table').innerHTML=GRADES.map(function(g){
    var pct=g.score!==null?Math.round((g.score/g.max)*100):null;
    var grade=pct!==null?getGrade(pct):null;
    var typeBadge=g.type==='quiz'?'badge-primary':'badge-outline';
    var typeLabel=g.type==='quiz'?'Bài thi':'Bài tập';
    var gradeHtml=grade?'<span class="grade-circle grade-'+grade.g.toLowerCase()+'" style="display:inline-flex;width:2rem;height:2rem;font-size:var(--text-sm);">'+grade.g+'</span>':'—';
    return '<tr><td style="font-weight:500;">'+g.name+'</td><td style="font-size:var(--text-sm);color:var(--muted-foreground);">'+g.course+'</td><td><span class="badge '+typeBadge+'">'+typeLabel+'</span></td><td><span style="font-weight:600;">'+(pct!==null?pct+'%':'—')+'</span></td><td style="font-size:var(--text-sm);">'+g.weight+'</td><td>'+gradeHtml+'</td><td>'+STATUS_MAP[g.status]+'</td></tr>';
  }).join('');
  if(typeof Chart!=='undefined'){
    new Chart(document.getElementById('courseChart'),{type:'bar',data:{labels:['Phát triển Web','Cấu trúc DL','Thiết kế CSDL','Mạng MT','KT Phần mềm'],datasets:[{label:'Điểm TB',data:[87,82,75,88,79],backgroundColor:['#3b82f6','#f97316','#22c55e','#a855f7','#06b6d4'],borderRadius:4}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,max:100}}}});
    new Chart(document.getElementById('trendChart'),{type:'line',data:{labels:['Tuần 1','Tuần 2','Tuần 3','Tuần 4','Tuần 5','Tuần 6'],datasets:[{label:'Điểm TB của bạn',data:[75,79,82,78,85,88],borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,0.08)',fill:true,tension:0.4,pointRadius:5},{label:'TB lớp',data:[70,72,70,74,73,75],borderColor:'#f97316',backgroundColor:'transparent',tension:0.4,pointRadius:4,borderDash:[4,4]}]},options:{responsive:true,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:false,min:50,max:100}}}});
  }
})();
</script>
@endpush
