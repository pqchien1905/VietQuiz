{{-- Student: help --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $categories = [
    'technical' => ['label' => 'Kỹ thuật', 'badge' => 'badge-info'],
    'account' => ['label' => 'Tài khoản', 'badge' => 'badge-primary'],
    'quiz' => ['label' => 'Bài kiểm tra', 'badge' => 'badge-warning'],
    'grades' => ['label' => 'Điểm số', 'badge' => 'badge-success'],
    'other' => ['label' => 'Khác', 'badge' => 'badge-default'],
  ];
  $statuses = [
    'open' => ['label' => 'Mới gửi', 'badge' => 'badge-info'],
    'in_progress' => ['label' => 'Đang xử lý', 'badge' => 'badge-warning'],
    'resolved' => ['label' => 'Đã phản hồi', 'badge' => 'badge-success'],
    'closed' => ['label' => 'Đã đóng', 'badge' => 'badge-default'],
  ];
  $faqs = [
    ['id' => 'faq-quiz', 'topic' => 'Bài kiểm tra', 'question' => 'Làm thế nào để bắt đầu làm bài kiểm tra?', 'answer' => 'Vào Bài kiểm tra, chọn bài đang mở rồi nhấn Làm bài. Nếu bạn đã bắt đầu nhưng chưa nộp, hệ thống sẽ cho phép tiếp tục bài làm còn dang dở.'],
    ['id' => 'faq-submit-quiz', 'topic' => 'Bài kiểm tra', 'question' => 'Bài kiểm tra có tự nộp khi hết giờ không?', 'answer' => 'Có. Khi hết thời gian, bài làm sẽ được gửi với các câu trả lời hiện có. Bạn nên kiểm tra kết nối mạng và chủ động nộp trước khi hết giờ.'],
    ['id' => 'faq-assignment', 'topic' => 'Bài tập', 'question' => 'Tôi nộp bài tập ở đâu?', 'answer' => 'Vào Bài tập, mở bài được giao, nhập nội dung hoặc đính kèm file rồi nhấn Nộp bài. Nếu giáo viên chưa chấm và bài chưa quá hạn, bạn có thể cập nhật lại bài nộp.'],
    ['id' => 'faq-join', 'topic' => 'Lớp học', 'question' => 'Làm sao để tham gia lớp học mới?', 'answer' => 'Mở Tham gia lớp, nhập mã lớp do giáo viên cung cấp hoặc mở link mời dạng /student/join/ma-lop. Sau khi tham gia, các khóa học thuộc lớp sẽ tự đồng bộ.'],
    ['id' => 'faq-grades', 'topic' => 'Điểm số', 'question' => 'Khi nào điểm được hiển thị?', 'answer' => 'Quiz trắc nghiệm thường có điểm ngay sau khi nộp. Bài tập hoặc bài cần giáo viên chấm sẽ nằm ở trạng thái chờ chấm trong trang Điểm số.'],
    ['id' => 'faq-trash', 'topic' => 'Tài khoản', 'question' => 'Tôi lỡ xóa thông báo thì khôi phục thế nào?', 'answer' => 'Vào Thùng rác để khôi phục thông báo đã xóa. Các mục trong thùng rác có thể được khôi phục hoặc xóa vĩnh viễn.'],
  ];
  $guides = [
    ['icon' => '📝', 'title' => 'Làm bài kiểm tra', 'description' => 'Xem danh sách quiz, tiếp tục bài đang làm và mở kết quả.', 'route' => route('student.quizzes'), 'faq' => 'faq-quiz'],
    ['icon' => '📎', 'title' => 'Nộp bài tập', 'description' => 'Theo dõi hạn nộp, gửi file hoặc nội dung bài làm.', 'route' => route('student.assignments'), 'faq' => 'faq-assignment'],
    ['icon' => '🎓', 'title' => 'Tham gia lớp', 'description' => 'Dùng mã lớp hoặc link mời từ giáo viên.', 'route' => route('student.join-class'), 'faq' => 'faq-join'],
    ['icon' => '🏅', 'title' => 'Xem điểm số', 'description' => 'Theo dõi điểm đã chấm, chờ chấm và bài chưa nộp.', 'route' => route('student.grades'), 'faq' => 'faq-grades'],
  ];
@endphp

@push('styles')
<style>
  .help-hero{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(280px,.85fr);gap:1rem;margin-bottom:1.5rem}
  .help-panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;box-shadow:var(--shadow-sm)}
  .help-search-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.75rem;margin-top:1.25rem}
  .help-metric{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.875rem;border:1px solid var(--border);border-radius:var(--radius-md);background:color-mix(in srgb,var(--muted) 38%,transparent)}
  .help-metric strong{font-size:var(--text-2xl);line-height:1}
  .help-guides{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1rem;margin-bottom:1.5rem}
  .help-guide{display:flex;align-items:flex-start;gap:.875rem;padding:1rem;border:1px solid var(--border);border-radius:var(--radius-md);color:var(--foreground);text-decoration:none;background:var(--card);min-height:7rem}
  .help-guide:hover{border-color:var(--primary);box-shadow:var(--shadow-md);text-decoration:none}
  .help-icon{width:2.5rem;height:2.5rem;border-radius:var(--radius-md);display:inline-flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--primary) 12%,transparent);flex-shrink:0;font-size:1.2rem}
  .help-main-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:1.5rem;align-items:start}
  .ticket-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.75rem;padding:1rem 0;border-top:1px solid var(--border)}
  .ticket-item:first-child{border-top:none;padding-top:0}
  .contact-row{display:flex;align-items:center;gap:.875rem;padding:.875rem;border:1px solid var(--border);border-radius:var(--radius-md);color:inherit;text-decoration:none}
  .contact-row:hover{border-color:var(--primary);box-shadow:var(--shadow-sm)}
  @media (max-width:900px){.help-hero,.help-main-grid{grid-template-columns:1fr}.help-search-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div>
      <h1>Trung tâm trợ giúp</h1>
      <p style="color:var(--muted-foreground);">Tìm hướng dẫn nhanh hoặc gửi yêu cầu hỗ trợ cho đội ngũ VietQuiz.</p>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;"><span>{{ session('success') }}</span></div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ $errors->first() }}</span></div>
  @endif

  <section class="help-hero stagger-children">
    <div class="help-panel" style="background:linear-gradient(135deg,var(--primary),color-mix(in srgb,var(--primary) 70%,var(--info)));color:#fff;">
      <div style="font-size:var(--text-2xl);font-weight:800;margin-bottom:.5rem;">Bạn cần hỗ trợ điều gì?</div>
      <div style="color:rgba(255,255,255,.84);font-size:var(--text-sm);max-width:640px;">Tìm nhanh trong FAQ hoặc gửi ticket nếu bạn gặp lỗi khi làm quiz, nộp bài, xem điểm hoặc tham gia lớp.</div>
      <div class="help-search-row">
        <input type="search" class="input" placeholder="Tìm trong câu hỏi thường gặp..." id="help-search" style="background:#fff;color:#111;border:0;">
        <button class="btn btn-outline" type="button" id="clear-help-search" style="background:#fff;">Xóa tìm kiếm</button>
      </div>
    </div>

    <div class="help-panel" style="display:flex;flex-direction:column;gap:.75rem;">
      <div class="help-metric"><span>Ticket mới</span><strong>{{ $ticketStats['open'] ?? 0 }}</strong></div>
      <div class="help-metric"><span>Đang xử lý</span><strong>{{ $ticketStats['in_progress'] ?? 0 }}</strong></div>
      <div class="help-metric"><span>Đã phản hồi</span><strong>{{ ($ticketStats['resolved'] ?? 0) + ($ticketStats['closed'] ?? 0) }}</strong></div>
    </div>
  </section>

  <div class="help-guides stagger-children">
    @foreach($guides as $guide)
      <a href="{{ $guide['route'] }}" class="help-guide" data-faq-target="{{ $guide['faq'] }}">
        <div class="help-icon">{{ $guide['icon'] }}</div>
        <div>
          <div style="font-weight:800;margin-bottom:.25rem;">{{ $guide['title'] }}</div>
          <div style="font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.5;">{{ $guide['description'] }}</div>
        </div>
      </a>
    @endforeach
  </div>

  <div class="help-main-grid stagger-children">
    <section>
      <h2 style="font-size:var(--text-xl);font-weight:800;margin-bottom:1rem;">Câu hỏi thường gặp</h2>
      <div class="card" id="faq-container">
        <div class="card-content" style="padding:0;">
          @foreach($faqs as $faq)
            <div class="accordion-item" id="{{ $faq['id'] }}" data-faq-item>
              <button class="accordion-trigger" type="button" onclick="this.closest('.accordion-item').classList.toggle('open')">
                <span>
                  <span class="badge badge-outline" style="margin-right:.5rem;">{{ $faq['topic'] }}</span>
                  {{ $faq['question'] }}
                </span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
              <div class="accordion-content">{{ $faq['answer'] }}</div>
            </div>
          @endforeach
          <div id="faq-empty" style="display:none;padding:2rem;text-align:center;color:var(--muted-foreground);">Không tìm thấy câu hỏi phù hợp.</div>
        </div>
      </div>
    </section>

    <aside style="display:flex;flex-direction:column;gap:1rem;">
      <section class="card">
        <div class="card-header">
          <h3 class="card-title">Gửi yêu cầu hỗ trợ</h3>
          <p class="card-description">Mô tả rõ vấn đề, đường dẫn trang và thao tác vừa thực hiện để được xử lý nhanh hơn.</p>
        </div>
        <div class="card-content">
          <form method="POST" action="{{ route('student.help.ticket') }}" style="display:flex;flex-direction:column;gap:.875rem;">
            @csrf
            <div class="form-group">
              <label class="label label-required" for="subject">Tiêu đề</label>
              <input id="subject" type="text" name="subject" class="input @error('subject') input-error @enderror" placeholder="Ví dụ: Không nộp được bài tập" value="{{ old('subject') }}" required maxlength="255">
              @error('subject')<div style="color:var(--destructive);font-size:var(--text-xs);margin-top:.35rem;">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label class="label label-required" for="category">Loại vấn đề</label>
              <select id="category" name="category" class="input select @error('category') input-error @enderror" required>
                <option value="quiz" @selected(old('category') === 'quiz')>Bài kiểm tra</option>
                <option value="technical" @selected(old('category') === 'technical')>Lỗi kỹ thuật</option>
                <option value="account" @selected(old('category') === 'account')>Tài khoản / đăng nhập</option>
                <option value="grades" @selected(old('category') === 'grades')>Điểm số</option>
                <option value="other" @selected(old('category') === 'other')>Vấn đề khác</option>
              </select>
              @error('category')<div style="color:var(--destructive);font-size:var(--text-xs);margin-top:.35rem;">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label class="label label-required" for="description">Mô tả vấn đề</label>
              <textarea id="description" name="description" class="input @error('description') input-error @enderror" style="min-height:7rem;" placeholder="Nêu rõ bạn đang ở trang nào, thao tác nào bị lỗi, thông báo lỗi nếu có..." required maxlength="2000">{{ old('description') }}</textarea>
              @error('description')<div style="color:var(--destructive);font-size:var(--text-xs);margin-top:.35rem;">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Gửi yêu cầu hỗ trợ</button>
          </form>
        </div>
      </section>

      <section class="card">
        <div class="card-header">
          <h3 class="card-title">Liên hệ nhanh</h3>
        </div>
        <div class="card-content" style="display:flex;flex-direction:column;gap:.75rem;">
          <a class="contact-row" href="mailto:support@vietquiz.vn">
            <div class="help-icon">✉️</div>
            <div><div style="font-weight:700;">Email hỗ trợ</div><div style="font-size:var(--text-sm);color:var(--muted-foreground);">support@vietquiz.vn</div></div>
          </a>
          <a class="contact-row" href="{{ route('student.notifications') }}">
            <div class="help-icon">🔔</div>
            <div><div style="font-weight:700;">Theo dõi phản hồi</div><div style="font-size:var(--text-sm);color:var(--muted-foreground);">Kiểm tra thông báo sau khi gửi ticket.</div></div>
          </a>
          <a class="contact-row" href="{{ route('student.settings') }}">
            <div class="help-icon">⚙️</div>
            <div><div style="font-weight:700;">Cài đặt tài khoản</div><div style="font-size:var(--text-sm);color:var(--muted-foreground);">Cập nhật email, mật khẩu và thông báo.</div></div>
          </a>
        </div>
      </section>

      <section class="card">
        <div class="card-header">
          <h3 class="card-title">Yêu cầu gần đây</h3>
          <p class="card-description">5 ticket mới nhất của bạn.</p>
        </div>
        <div class="card-content" style="padding-top:0;">
          @forelse($tickets as $ticket)
            @php
              $category = $categories[$ticket->category] ?? $categories['other'];
              $status = $statuses[$ticket->status] ?? $statuses['open'];
            @endphp
            <div class="ticket-item">
              <div style="min-width:0;">
                <div style="font-weight:800;font-size:var(--text-sm);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ticket->subject }}</div>
                <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;">{{ $ticket->created_at?->format('d/m/Y H:i') }}</div>
                @if($ticket->admin_response)
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.35rem;line-height:1.5;">Phản hồi: {{ \Illuminate\Support\Str::limit($ticket->admin_response, 90) }}</div>
                @endif
              </div>
              <div style="display:flex;flex-direction:column;gap:.35rem;align-items:flex-end;">
                <span class="badge {{ $category['badge'] }}">{{ $category['label'] }}</span>
                <span class="badge {{ $status['badge'] }}">{{ $status['label'] }}</span>
                @if(($ticket->priority ?? 'normal') === 'vip')
                  <span class="badge badge-warning">VIP</span>
                @endif
              </div>
            </div>
          @empty
            <div style="padding:1rem 0;color:var(--muted-foreground);font-size:var(--text-sm);">Bạn chưa gửi yêu cầu hỗ trợ nào.</div>
          @endforelse
        </div>
      </section>
    </aside>
  </div>

  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var search = document.getElementById('help-search');
  var clear = document.getElementById('clear-help-search');
  var items = Array.prototype.slice.call(document.querySelectorAll('[data-faq-item]'));
  var empty = document.getElementById('faq-empty');

  function filterFaq() {
    var query = (search && search.value || '').toLowerCase().trim();
    var visible = 0;
    items.forEach(function(item) {
      var match = !query || item.textContent.toLowerCase().indexOf(query) !== -1;
      item.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    if (empty) empty.style.display = visible ? 'none' : '';
  }

  search && search.addEventListener('input', filterFaq);
  clear && clear.addEventListener('click', function() {
    if (search) search.value = '';
    filterFaq();
  });

  document.querySelectorAll('[data-faq-target]').forEach(function(link) {
    link.addEventListener('click', function(event) {
      var target = document.getElementById(link.dataset.faqTarget);
      if (!target) return;
      event.preventDefault();
      target.classList.add('open');
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });
})();
</script>
@endpush
