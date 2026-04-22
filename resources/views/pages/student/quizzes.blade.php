{{-- Student: quizzes --}}
@extends('layouts.dashboard', ['role' => 'student'])

@section('content')
  <div class="page-header stagger-children">
        <h1>Bài kiểm tra của tôi</h1>
        <p style="color:var(--muted-foreground);">Danh sách bài kiểm tra đã giao và lịch sử làm bài</p>
      </div>

      <!-- Tabs -->
      <div class="tabs-list stagger-children" style="margin-bottom:1.25rem;max-width:420px;">
        <button class="tab-trigger active" onclick="switchTab('upcoming',this)">Sắp tới</button>
        <button class="tab-trigger" onclick="switchTab('completed',this)">Đã hoàn thành</button>
        <button class="tab-trigger" onclick="switchTab('missed',this)">Bỏ lỡ</button>
      </div>

      <!-- Upcoming -->
      <div id="tab-upcoming" class="stagger-children">
        <div class="cards-grid" id="upcoming-grid"></div>
      </div>

      <!-- Completed -->
      <div id="tab-completed" style="display:none;" class="stagger-children">
        <div class="card">
          <div class="table-wrapper" style="border:none;border-radius:0;">
            <table>
              <thead><tr><th>Bài kiểm tra</th><th>Lớp</th><th>Ngày làm</th><th>Điểm</th><th>Xếp loại</th><th></th></tr></thead>
              <tbody id="completed-table"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Missed -->
      <div id="tab-missed" style="display:none;" class="stagger-children">
        <div id="missed-container"></div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var UPCOMING=[
    {id:1,title:'Kiểm tra Cấu trúc Dữ liệu 3',course:'Cấu trúc Dữ liệu',questions:20,duration:45,due:'Hôm nay, 23:59',daysLeft:0,urgent:true},
    {id:2,title:'Trắc nghiệm HTML/CSS Nâng cao',course:'Phát triển Web',questions:15,duration:30,due:'Ngày mai, 17:00',daysLeft:1,urgent:false},
    {id:3,title:'KT Giữa kỳ Thiết kế CSDL',course:'Thiết kế CSDL',questions:30,duration:60,due:'05/04/2026',daysLeft:4,urgent:false},
    {id:4,title:'Phân tích Thuật toán Sắp xếp',course:'Cấu trúc Dữ liệu',questions:25,duration:50,due:'08/04/2026',daysLeft:7,urgent:false},
    {id:5,title:'KT Cuối kỳ Mạng Máy tính',course:'Mạng Máy tính',questions:40,duration:90,due:'15/04/2026',daysLeft:14,urgent:false}
  ];
  var COMPLETED=[
    {title:'Trắc nghiệm React Hooks',course:'Phát triển Web',date:'28/03/2026',score:88,max:100,grade:'B'},
    {title:'KT Cơ bản OOP Java',course:'Lập trình Java',date:'25/03/2026',score:92,max:100,grade:'A'},
    {title:'Trắc nghiệm SQL Nâng cao',course:'Thiết kế CSDL',date:'20/03/2026',score:72,max:100,grade:'C'},
    {title:'KT Thuật toán Tìm kiếm',course:'Cấu trúc Dữ liệu',date:'15/03/2026',score:85,max:100,grade:'B'},
    {title:'Kiểm tra Mạng TCP/IP',course:'Mạng Máy tính',date:'10/03/2026',score:78,max:100,grade:'C'},
    {title:'Trắc nghiệm HTML Cơ bản',course:'Phát triển Web',date:'05/03/2026',score:95,max:100,grade:'A'}
  ];
  var MISSED=[
    {title:'KT Hệ điều hành',course:'Hệ điều hành',due:'01/03/2026',penaltyNote:'Liên hệ giáo viên để được phép làm muộn'},
    {title:'Trắc nghiệm Mạng LAN',course:'Mạng Máy tính',due:'28/02/2026',penaltyNote:'Hạn đã qua — không thể làm bù'}
  ];
  document.getElementById('upcoming-grid').innerHTML=UPCOMING.map(function(q){
    var badgeCls=q.urgent?'badge-danger':'badge-primary';
    var badgeTxt=q.urgent?'🔥 Hôm nay':'📝 Sắp tới';
    var dueColor=q.urgent?'var(--destructive)':'var(--muted-foreground)';
    return '<div class="card hover-lift"><div class="card-content"><div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.75rem;"><span class="badge '+badgeCls+'">'+badgeTxt+'</span><span style="font-size:var(--text-xs);color:var(--muted-foreground);">'+q.questions+' câu · '+q.duration+' phút</span></div><h3 style="font-size:var(--text-base);font-weight:700;margin-bottom:.25rem;line-height:1.4;">'+q.title+'</h3><p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1rem;">'+q.course+'</p><div style="display:flex;align-items:center;justify-content:space-between;"><span style="font-size:var(--text-xs);color:'+dueColor+';">⏰ '+q.due+'</span><a href="{{ route('student.quiz-take') }}" class="btn btn-primary btn-sm">Làm bài</a></div></div></div>';
  }).join('');
  document.getElementById('completed-table').innerHTML=COMPLETED.map(function(q){
    var pct=Math.round((q.score/q.max)*100);
    var c=pct>=90?'var(--success)':pct>=70?'var(--info)':pct>=50?'var(--warning)':'var(--destructive)';
    return '<tr><td style="font-weight:500;">'+q.title+'</td><td style="font-size:var(--text-sm);color:var(--muted-foreground);">'+q.course+'</td><td style="font-size:var(--text-sm);color:var(--muted-foreground);">'+q.date+'</td><td><span style="font-weight:700;color:'+c+';">'+pct+'%</span></td><td><div class="grade-circle grade-'+q.grade.toLowerCase()+'" style="width:1.75rem;height:1.75rem;font-size:var(--text-xs);">'+q.grade+'</div></td><td><a href="{{ route('student.quiz-result') }}" class="btn btn-ghost btn-sm">Xem kết quả</a></td></tr>';
  }).join('');
  document.getElementById('missed-container').innerHTML=MISSED.length
    ? MISSED.map(function(m){return '<div class="card" style="border-color:var(--destructive);margin-bottom:.75rem;"><div class="card-content"><div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;"><div style="font-size:1.5rem;">⚠️</div><div style="flex:1;"><div style="font-weight:600;">'+m.title+'</div><div style="font-size:var(--text-sm);color:var(--muted-foreground);">'+m.course+' · Hạn: '+m.due+'</div><div style="font-size:var(--text-sm);color:var(--destructive);margin-top:.25rem;">'+m.penaltyNote+'</div></div></div></div></div>';}).join('')
    : '<div class="empty-state"><div style="font-size:3rem;">🎉</div><h3>Không bỏ lỡ bài nào</h3><p>Bạn đã hoàn thành tất cả bài kiểm tra!</p></div>';
  window.switchTab=function(tab,el){
    ['upcoming','completed','missed'].forEach(function(t){document.getElementById('tab-'+t).style.display=t===tab?'':'none';});
    document.querySelectorAll('.tab-trigger').forEach(function(b){b.classList.remove('active');});
    el.classList.add('active');
  };
})();
</script>
@endpush
