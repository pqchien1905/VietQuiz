{{-- Teacher: help --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@section('content')
  <div class="page-header stagger-children">
        <h1>Trợ giúp và Hỗ trợ</h1>
        <p style="color:var(--muted-foreground);">Tìm câu trả lời cho các câu hỏi thường gặp hoặc liên hệ với đội ngũ hỗ trợ</p>
      </div>

      <!-- Quick links -->
      <div class="stats-grid stats-grid-3 stagger-children" style="margin-bottom:1.5rem;">
        <div class="stat-card" style="cursor:pointer;" onclick="">
          <div style="width:3rem;height:3rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;font-size:1.5rem;">📖</div>
          <div style="font-weight:600;">Sổ tay Hướng dẫn</div>
          <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Hướng dẫn đầy đủ cách sử dụng VietQuiz</div>
        </div>
        <div class="stat-card" style="cursor:pointer;">
          <div style="width:3rem;height:3rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--accent) 12%,transparent);color:var(--accent);display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;font-size:1.5rem;">🎬</div>
          <div style="font-weight:600;">Video Hướng dẫn</div>
          <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Xem video hướng dẫn từng bước</div>
        </div>
        <div class="stat-card" style="cursor:pointer;">
          <div style="width:3rem;height:3rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--success) 12%,transparent);color:var(--success);display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;font-size:1.5rem;">📚</div>
          <div style="font-weight:600;">Tài liệu</div>
          <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Tài liệu kỹ thuật chi tiết</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;" class="stagger-children">
        <!-- FAQ -->
        <div class="card">
          <div class="card-header">
            <div class="flex items-center justify-between">
              <h3 class="card-title">Câu hỏi Thường gặp</h3>
              <div class="search-input-wrapper" style="max-width:200px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" class="input" style="font-size:var(--text-sm);" placeholder="Tìm câu hỏi..." id="faq-search" />
              </div>
            </div>
          </div>
          <div class="card-content" id="faq-list" style="padding-top:0;"></div>
        </div>

        <!-- Contact & Ticket -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">
          <div class="card">
            <div class="card-header"><h3 class="card-title">Liên hệ Hỗ trợ</h3></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:0.75rem;">
              <div class="flex items-center gap-3" style="gap:0.75rem;padding:0.875rem;background:var(--muted);border-radius:var(--radius-md);">
                <div style="font-size:1.5rem;">📧</div>
                <div>
                  <div style="font-weight:600;">Hỗ trợ qua Email</div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Phản hồi trong vòng 24 giờ</div>
                  <div style="font-size:var(--text-sm);color:var(--primary);margin-top:0.25rem;">support@vietquiz.edu.vn</div>
                </div>
              </div>
              <div class="flex items-center gap-3" style="gap:0.75rem;padding:0.875rem;background:var(--muted);border-radius:var(--radius-md);">
                <div style="font-size:1.5rem;">📞</div>
                <div>
                  <div style="font-weight:600;">Hỗ trợ qua Điện thoại</div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Thứ Hai - Thứ Sáu, 9:00 - 17:00</div>
                  <div style="font-size:var(--text-sm);color:var(--primary);margin-top:0.25rem;">1900 1234</div>
                </div>
              </div>
              <button class="btn btn-primary gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Bắt đầu Chat Trực tiếp
              </button>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><h3 class="card-title">Gửi Yêu cầu Hỗ trợ</h3><p class="card-description">Mô tả vấn đề và chúng tôi sẽ phản hồi sớm</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:0.875rem;">
              <div class="form-group"><label class="label">Danh mục</label><select class="input select"><option>Vấn đề Kỹ thuật</option><option>Tài khoản & Cài đặt</option><option>Bài kiểm tra</option><option>Điểm số</option><option>Khác</option></select></div>
              <div class="form-group"><label class="label">Nội dung</label><textarea class="input" style="min-height:5rem;" placeholder="Mô tả chi tiết vấn đề của bạn..."></textarea></div>
              <button class="btn btn-primary" onclick="submitTicket()">Gửi Yêu cầu</button>
            </div>
          </div>
        </div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
var FAQS=[
  {cat:'Bắt đầu',q:'Làm thế nào để tạo lớp học mới?',a:'Truy cập trang "Lớp học" và nhấp "Tạo Lớp". Điền tên, môn học và lịch học. Học sinh có thể tham gia bằng mã lớp.'},
  {cat:'Bài kiểm tra',q:'Làm thế nào để tạo bài kiểm tra?',a:'Vào "Bài kiểm tra" → "Tạo Kỳ thi Mới". Điền thông tin, thêm câu hỏi (trắc nghiệm, đúng/sai, tự luận) và xuất bản.'},
  {cat:'Bài kiểm tra',q:'Có thể xáo trộn câu hỏi không?',a:'Có, khi tạo bài thi, bật tùy chọn "Xáo trộn câu hỏi" để mỗi học sinh nhận đề theo thứ tự khác nhau.'},
  {cat:'Chấm điểm',q:'Làm thế nào để chấm bài tự luận?',a:'Vào "Chấm điểm", chọn bài tập cần chấm, xem bài nộp của từng học sinh và nhập điểm kèm phản hồi.'},
  {cat:'Phân tích',q:'Có thể xuất báo cáo không?',a:'Có, trang "Phân tích" có nút "Xuất Báo cáo". Báo cáo CSV bao gồm điểm, thống kê và tiến độ học sinh.'},
  {cat:'Kỹ thuật',q:'Học sinh không vào được bài thi phải làm gì?',a:'Kiểm tra: (1) Bài thi đã xuất bản chưa, (2) Chưa hết hạn, (3) Học sinh đúng lớp. Liên hệ hỗ trợ nếu vẫn lỗi.'}
];
function renderFAQ(data){
  document.getElementById('faq-list').innerHTML=data.map(function(f){
    return '<div class="accordion-item" style="border-radius:var(--radius-md);"><button class="accordion-trigger" onclick="toggleFAQ(this)"><div><div style="font-weight:500;font-size:var(--text-sm);">'+f.q+'</div><div style="font-size:var(--text-xs);color:var(--primary);">'+f.cat+'</div></div><svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button><div class="accordion-content">'+f.a+'</div></div>';
  }).join('');
}
window.toggleFAQ=function(btn){btn.closest('.accordion-item').classList.toggle('open');};
document.getElementById('faq-search').addEventListener('input',function(e){
  var q=e.target.value.toLowerCase();
  renderFAQ(q?FAQS.filter(function(f){return f.q.toLowerCase().indexOf(q)!==-1||f.a.toLowerCase().indexOf(q)!==-1;}):FAQS);
});
window.submitTicket=function(){toast('Đã gửi yêu cầu hỗ trợ! Chúng tôi sẽ phản hồi trong 24 giờ.');};
function toast(m){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-success';e.innerHTML='<span>✅</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
renderFAQ(FAQS);
})();
</script>
@endpush
