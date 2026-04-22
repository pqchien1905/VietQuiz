{{-- Student: courses --}}
@extends('layouts.dashboard', ['role' => 'student'])

@section('content')
  <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1>Khóa học của tôi</h1>
            <p style="color:var(--muted-foreground);">Các lớp học đang tham gia trong học kỳ này</p>
          </div>
          <a href="{{ route('student.join-class') }}" class="btn btn-primary gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tham gia lớp mới
          </a>
        </div>
      </div>

      <div class="cards-grid stagger-children" id="courses-grid"></div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var COURSES=[
    {id:1,name:'Phát triển Web',teacher:'GV. Nguyễn Văn An',color:'#3b82f6',icon:'💻',students:32,progress:72,avg:88.5,quizzes:5,assignments:3,nextDue:'Ngày mai',status:'active'},
    {id:2,name:'Cấu trúc Dữ liệu',teacher:'GV. Trần Thị Mai',color:'#f97316',icon:'🌳',students:28,progress:55,avg:82.1,quizzes:4,assignments:2,nextDue:'05/04',status:'active'},
    {id:3,name:'Thiết kế CSDL',teacher:'GV. Lê Minh Tuấn',color:'#22c55e',icon:'🗄️',students:27,progress:40,avg:75.4,quizzes:3,assignments:4,nextDue:'08/04',status:'active'},
    {id:4,name:'Mạng Máy tính',teacher:'GV. Phạm Quốc Hùng',color:'#a855f7',icon:'🌐',students:35,progress:68,avg:79.0,quizzes:3,assignments:1,nextDue:'10/04',status:'active'},
    {id:5,name:'Lập trình Java',teacher:'GV. Hoàng Văn Bình',color:'#ef4444',icon:'☕',students:30,progress:90,avg:91.3,quizzes:6,assignments:3,nextDue:'Đã hoàn thành',status:'completed'},
    {id:6,name:'Kỹ thuật Phần mềm',teacher:'GV. Đỗ Thị Lan',color:'#06b6d4',icon:'⚙️',students:25,progress:25,avg:0,quizzes:1,assignments:0,nextDue:'Mới bắt đầu',status:'active'}
  ];
  document.getElementById('courses-grid').innerHTML=COURSES.map(function(c){
    var completed=c.status==='completed'?'<span class="badge badge-solid-primary" style="margin-left:auto;background:rgba(255,255,255,.2);color:#fff;">✅ Hoàn thành</span>':'';
    var avgColor=c.avg>0?'var(--success)':'var(--muted-foreground)';
    var avgText=c.avg>0?c.avg+'%':'—';
    var dueColor=c.nextDue==='Ngày mai'?'var(--destructive)':'var(--muted-foreground)';
    var progClass=c.progress>=90?' success':'';
    return '<div class="card hover-lift" style="cursor:pointer;"><div style="height:4.5rem;background:linear-gradient(135deg,'+c.color+','+c.color+'cc);border-radius:var(--radius-lg) var(--radius-lg) 0 0;display:flex;align-items:center;padding:1rem;gap:.75rem;"><div style="font-size:1.75rem;">'+c.icon+'</div><div style="color:#fff;"><div style="font-weight:700;font-size:var(--text-base);">'+c.name+'</div><div style="font-size:var(--text-xs);opacity:.85;">'+c.teacher+'</div></div>'+completed+'</div><div class="card-content"><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;text-align:center;margin-bottom:.875rem;"><div><div style="font-weight:700;">'+c.quizzes+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Bài thi</div></div><div><div style="font-weight:700;">'+c.assignments+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Bài tập</div></div><div><div style="font-weight:700;color:'+avgColor+';">'+avgText+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">Điểm TB</div></div></div><div><div style="display:flex;justify-content:space-between;font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;"><span>Tiến độ khóa học</span><span>'+c.progress+'%</span></div><div class="progress"><div class="progress-bar'+progClass+'" style="width:'+c.progress+'%;background:'+c.color+';"></div></div></div><div style="margin-top:.75rem;font-size:var(--text-xs);color:'+dueColor+';">📅 Hạn tiếp theo: '+c.nextDue+'</div></div><div class="card-footer" style="gap:.5rem;"><a href="{{ route('student.quizzes') }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;">Bài kiểm tra</a><a href="{{ route('student.assignments') }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;">Bài tập</a></div></div>';
  }).join('');
})();
</script>
@endpush
