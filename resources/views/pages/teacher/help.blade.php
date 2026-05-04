{{-- Teacher: help --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

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
    [
      'category' => 'classes',
      'category_label' => 'Lớp học',
      'question' => 'Làm thế nào để tạo lớp và mời học sinh tham gia?',
      'answer' => 'Vào Lớp của Tôi, chọn Tạo Lớp, nhập tên lớp và môn học. Sau khi tạo, dùng mã lớp hoặc liên kết mời trong trang chi tiết lớp để gửi cho học sinh.',
    ],
    [
      'category' => 'quizzes',
      'category_label' => 'Bài kiểm tra',
      'question' => 'Tôi cần chuẩn bị gì trước khi xuất bản bài kiểm tra?',
      'answer' => 'Kiểm tra tiêu đề, thời gian làm bài, thang điểm, câu hỏi bắt buộc và trạng thái lớp được giao. Nên dùng chế độ xem chi tiết bài kiểm tra để rà lại cấu hình trước khi xuất bản.',
    ],
    [
      'category' => 'questions',
      'category_label' => 'Ngân hàng câu hỏi',
      'question' => 'Có thể import câu hỏi hàng loạt không?',
      'answer' => 'Có. Vào Ngân hàng câu hỏi và dùng chức năng import file hoặc CSV. Hãy đảm bảo file đúng định dạng mẫu để hệ thống đọc được đáp án và điểm số.',
    ],
    [
      'category' => 'grading',
      'category_label' => 'Chấm điểm',
      'question' => 'Bài tự luận và bài tập được chấm ở đâu?',
      'answer' => 'Vào Chấm điểm để xem bài nộp cần xử lý. Mỗi bài nộp có thể nhập điểm, phản hồi và xem tệp đính kèm nếu học sinh đã nộp file.',
    ],
    [
      'category' => 'students',
      'category_label' => 'Học sinh',
      'question' => 'Học sinh không vào được lớp thì xử lý thế nào?',
      'answer' => 'Kiểm tra mã lớp còn đúng, lớp chưa bị lưu trữ và tài khoản học sinh đã đăng nhập đúng vai trò. Nếu vẫn lỗi, gửi yêu cầu hỗ trợ kèm email học sinh và mã lớp.',
    ],
    [
      'category' => 'analytics',
      'category_label' => 'Báo cáo',
      'question' => 'Tôi có thể xuất báo cáo học tập không?',
      'answer' => 'Có. Trang Phân tích có chức năng xuất dữ liệu để theo dõi kết quả lớp, lượt làm bài và tiến độ học sinh theo từng bài kiểm tra hoặc hoạt động.',
    ],
  ];

  $guides = [
    ['title' => 'Tạo lớp đầu tiên', 'description' => 'Thiết lập lớp, mã tham gia và danh sách học sinh.', 'route' => route('teacher.classes')],
    ['title' => 'Tạo bài kiểm tra', 'description' => 'Soạn đề, thêm câu hỏi và xuất bản cho lớp.', 'route' => route('teacher.quiz-create')],
    ['title' => 'Quản lý ngân hàng câu hỏi', 'description' => 'Tổ chức thư mục, import câu hỏi và dùng AI.', 'route' => route('teacher.questions')],
    ['title' => 'Chấm bài nộp', 'description' => 'Xem bài nộp, nhập điểm và gửi phản hồi.', 'route' => route('teacher.grading')],
  ];

  $troubleshooting = [
    'Ghi lại đường dẫn trang đang lỗi và thao tác vừa thực hiện.',
    'Chụp màn hình thông báo lỗi nếu có.',
    'Kiểm tra lại vai trò tài khoản hiện tại là Giáo viên.',
    'Thử tải lại trang sau khi lưu dữ liệu quan trọng.',
  ];
@endphp

@push('styles')
<style>
  .help-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.85fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  .help-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
  }
  .help-search-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 180px;
    gap: 0.75rem;
    margin-top: 1.25rem;
  }
  .help-metric {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.875rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--muted) 38%, transparent);
  }
  .help-metric strong {
    font-size: var(--text-2xl);
    line-height: 1;
  }
  .help-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--radius-md);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    background: color-mix(in srgb, var(--primary) 12%, transparent);
    flex-shrink: 0;
  }
  .help-guide {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    padding: 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    color: var(--foreground);
    text-decoration: none;
    background: var(--card);
    min-height: 7rem;
  }
  .help-guide:hover {
    border-color: var(--primary);
    box-shadow: var(--shadow-md);
    text-decoration: none;
  }
  .help-main-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(320px, 0.75fr);
    gap: 1.5rem;
    align-items: start;
  }
  .ticket-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.75rem;
    padding: 1rem 0;
    border-top: 1px solid var(--border);
  }
  .ticket-item:first-child {
    border-top: none;
    padding-top: 0;
  }
  .help-checklist {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .help-checklist li {
    display: flex;
    gap: 0.625rem;
    align-items: flex-start;
    font-size: var(--text-sm);
    color: var(--muted-foreground);
  }
  .help-checkmark {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--success) 14%, transparent);
    color: var(--success);
    flex-shrink: 0;
    margin-top: 1px;
  }
  .faq-meta {
    display: inline-flex;
    margin-top: 0.25rem;
    font-size: var(--text-xs);
    color: var(--primary);
  }
  @media (max-width: 1100px) {
    .help-hero,
    .help-main-grid {
      grid-template-columns: 1fr;
    }
  }
  @media (max-width: 640px) {
    .help-search-row,
    .ticket-item {
      grid-template-columns: 1fr;
    }
    .help-panel,
    .card-content,
    .card-header {
      padding: 1rem;
    }
  }
</style>
@endpush

@section('content')
  <div class="page-header">
    <div class="flex items-start justify-between gap-4 flex-wrap">
      <div>
        <h1>Trợ giúp và Hỗ trợ</h1>
        <p>Tra cứu hướng dẫn, xử lý sự cố nhanh và gửi yêu cầu hỗ trợ cho đội ngũ VietQuiz.</p>
      </div>
      <a href="#support-form" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Gửi yêu cầu
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;">
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;">
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <section class="help-hero">
    <div class="help-panel">
      <div class="flex items-start gap-4">
        <div class="help-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 2.5-3 4"/><path d="M12 17h.01"/></svg>
        </div>
        <div class="flex-1">
          <h2 style="font-size:var(--text-2xl);font-weight:700;margin:0 0 .375rem;">Bạn cần hỗ trợ phần nào?</h2>
          <p style="color:var(--muted-foreground);font-size:var(--text-sm);line-height:1.6;max-width:760px;">
            Tìm nhanh trong câu hỏi thường gặp hoặc chuyển thẳng đến khu vực gửi ticket. Các yêu cầu đã gửi sẽ được lưu trong lịch sử bên dưới.
          </p>
        </div>
      </div>
      <div class="help-search-row">
        <div class="search-input-wrapper">
          <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="search" class="input" id="help-search" placeholder="Tìm theo lớp học, bài kiểm tra, chấm điểm..." autocomplete="off">
        </div>
        <select class="input select" id="help-category">
          <option value="all">Tất cả chủ đề</option>
          <option value="classes">Lớp học</option>
          <option value="quizzes">Bài kiểm tra</option>
          <option value="questions">Ngân hàng câu hỏi</option>
          <option value="grading">Chấm điểm</option>
          <option value="students">Học sinh</option>
          <option value="analytics">Báo cáo</option>
        </select>
      </div>
    </div>

    <div class="help-panel" style="display:flex;flex-direction:column;gap:.75rem;">
      <div class="help-metric">
        <div>
          <div class="text-xs text-muted">Ticket mới</div>
          <strong>{{ $ticketStats['open'] ?? 0 }}</strong>
        </div>
        <span class="badge badge-info">Chờ tiếp nhận</span>
      </div>
      <div class="help-metric">
        <div>
          <div class="text-xs text-muted">Đang xử lý</div>
          <strong>{{ $ticketStats['in_progress'] ?? 0 }}</strong>
        </div>
        <span class="badge badge-warning">Theo dõi</span>
      </div>
      <div class="help-metric">
        <div>
          <div class="text-xs text-muted">Đã phản hồi</div>
          <strong>{{ ($ticketStats['resolved'] ?? 0) + ($ticketStats['closed'] ?? 0) }}</strong>
        </div>
        <span class="badge badge-success">Hoàn tất</span>
      </div>
    </div>
  </section>

  <div class="cards-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-bottom:1.5rem;">
    @foreach($guides as $guide)
      <a href="{{ $guide['route'] }}" class="help-guide hover-lift">
        <span class="help-icon" style="width:2.25rem;height:2.25rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </span>
        <span>
          <span style="display:block;font-weight:700;margin-bottom:.25rem;">{{ $guide['title'] }}</span>
          <span style="display:block;font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.5;">{{ $guide['description'] }}</span>
        </span>
      </a>
    @endforeach
  </div>

  <div class="help-main-grid">
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <div class="card">
        <div class="card-header">
          <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
              <h3 class="card-title">Câu hỏi thường gặp</h3>
              <p class="card-description">Lọc theo từ khóa hoặc chủ đề để tìm đúng hướng dẫn.</p>
            </div>
            <button type="button" class="btn btn-outline btn-sm" id="expand-faqs">Mở tất cả</button>
          </div>
        </div>
        <div class="card-content" id="faq-list">
          @foreach($faqs as $index => $faq)
            <div class="accordion-item {{ $index === 0 ? 'open' : '' }}" data-faq-item data-category="{{ $faq['category'] }}" data-search="{{ \Illuminate\Support\Str::lower($faq['question'].' '.$faq['answer'].' '.$faq['category_label']) }}">
              <button class="accordion-trigger" type="button">
                <span>
                  <span style="display:block;">{{ $faq['question'] }}</span>
                  <span class="faq-meta">{{ $faq['category_label'] }}</span>
                </span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
              <div class="accordion-content">{{ $faq['answer'] }}</div>
            </div>
          @endforeach
          <div class="empty-state" id="faq-empty" style="display:none;padding:2rem 1rem;">
            <div class="empty-state-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </div>
            <h3>Không tìm thấy hướng dẫn phù hợp</h3>
            <p>Thử từ khóa khác hoặc gửi yêu cầu hỗ trợ ở biểu mẫu bên cạnh.</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Trước khi gửi yêu cầu</h3>
          <p class="card-description">Các thông tin này giúp đội hỗ trợ xử lý nhanh và chính xác hơn.</p>
        </div>
        <div class="card-content">
          <ul class="help-checklist">
            @foreach($troubleshooting as $item)
              <li><span class="help-checkmark">✓</span><span>{{ $item }}</span></li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <div class="card" id="support-form">
        <div class="card-header">
          <h3 class="card-title">Gửi yêu cầu hỗ trợ</h3>
          <p class="card-description">Mô tả vấn đề cụ thể, hệ thống sẽ lưu ticket vào tài khoản của bạn.</p>
        </div>
        <div class="card-content">
          <form method="POST" action="{{ route('teacher.tickets.store') }}" style="display:flex;flex-direction:column;gap:0.875rem;">
            @csrf
            <div class="form-group">
              <label class="label label-required" for="ticket-category">Danh mục</label>
              <select name="category" id="ticket-category" class="input select @error('category') input-error @enderror" required>
                @foreach($categories as $value => $category)
                  <option value="{{ $value }}" @selected(old('category') === $value)>{{ $category['label'] }}</option>
                @endforeach
              </select>
              @error('category')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label class="label label-required" for="ticket-subject">Tiêu đề</label>
              <input type="text" name="subject" id="ticket-subject" class="input @error('subject') input-error @enderror" value="{{ old('subject') }}" placeholder="VD: Học sinh không thấy bài kiểm tra giữa kỳ" required maxlength="255">
              @error('subject')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label class="label label-required" for="ticket-description">Nội dung</label>
              <textarea name="description" id="ticket-description" class="input @error('description') input-error @enderror" placeholder="Mô tả thao tác đã làm, lỗi hiển thị, lớp/bài kiểm tra liên quan..." required maxlength="2000">{{ old('description') }}</textarea>
              <div class="form-hint"><span id="ticket-counter">0</span>/2000 ký tự</div>
              @error('description')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-full">Gửi yêu cầu hỗ trợ</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Kênh liên hệ</h3>
          <p class="card-description">Dùng ticket cho lỗi cần kiểm tra dữ liệu tài khoản.</p>
        </div>
        <div class="card-content" style="display:flex;flex-direction:column;gap:.75rem;">
          <div class="help-metric">
            <div>
              <div style="font-weight:700;">Email hỗ trợ</div>
              <div class="text-sm text-muted">support@vietquiz.edu.vn</div>
            </div>
            <span class="badge badge-info">24 giờ</span>
          </div>
          <div class="help-metric">
            <div>
              <div style="font-weight:700;">Điện thoại</div>
              <div class="text-sm text-muted">1900 1234</div>
            </div>
            <span class="badge badge-outline">9:00-17:00</span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Yêu cầu gần đây</h3>
          <p class="card-description">5 ticket mới nhất của tài khoản giáo viên.</p>
        </div>
        <div class="card-content">
          @forelse($tickets as $ticket)
            <div class="ticket-item">
              <div style="min-width:0;">
                <div class="flex items-center gap-2 flex-wrap" style="margin-bottom:.25rem;">
                  <span class="badge {{ $categories[$ticket->category]['badge'] ?? 'badge-default' }}">{{ $categories[$ticket->category]['label'] ?? $ticket->category }}</span>
                  <span class="badge {{ $statuses[$ticket->status]['badge'] ?? 'badge-default' }}">{{ $statuses[$ticket->status]['label'] ?? $ticket->status }}</span>
                  @if(($ticket->priority ?? 'normal') === 'vip')
                    <span class="badge badge-warning">Ưu tiên VIP</span>
                  @endif
                </div>
                <div style="font-weight:700;line-height:1.4;">{{ $ticket->subject }}</div>
                <div class="text-xs text-muted" style="margin-top:.25rem;">Gửi lúc {{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                @if($ticket->admin_response)
                  <div class="alert alert-info" style="margin-top:.75rem;padding:.625rem .75rem;">{{ $ticket->admin_response }}</div>
                @endif
              </div>
              <div class="text-xs text-muted">#{{ $ticket->id }}</div>
            </div>
          @empty
            <div class="empty-state" style="padding:2rem 1rem;">
              <div class="empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              </div>
              <h3>Chưa có yêu cầu nào</h3>
              <p>Các ticket bạn gửi sẽ xuất hiện tại đây để tiện theo dõi.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function() {
  var searchInput = document.getElementById('help-search');
  var categoryInput = document.getElementById('help-category');
  var faqItems = Array.prototype.slice.call(document.querySelectorAll('[data-faq-item]'));
  var faqEmpty = document.getElementById('faq-empty');
  var expandBtn = document.getElementById('expand-faqs');
  var description = document.getElementById('ticket-description');
  var counter = document.getElementById('ticket-counter');

  function normalize(value) {
    return (value || '').toString().toLowerCase().trim();
  }

  function filterFaqs() {
    var query = normalize(searchInput && searchInput.value);
    var category = categoryInput ? categoryInput.value : 'all';
    var visible = 0;

    faqItems.forEach(function(item) {
      var matchesCategory = category === 'all' || item.dataset.category === category;
      var matchesQuery = !query || normalize(item.dataset.search).indexOf(query) !== -1;
      var show = matchesCategory && matchesQuery;
      item.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    if (faqEmpty) {
      faqEmpty.style.display = visible ? 'none' : 'flex';
    }
  }

  function syncCounter() {
    if (!description || !counter) return;
    counter.textContent = description.value.length;
  }

  document.getElementById('faq-list')?.addEventListener('click', function(event) {
    var trigger = event.target.closest('.accordion-trigger');
    if (trigger) {
      trigger.closest('.accordion-item')?.classList.toggle('open');
    }
  });

  expandBtn?.addEventListener('click', function() {
    var shouldOpen = expandBtn.textContent.trim() === 'Mở tất cả';
    faqItems.forEach(function(item) {
      if (item.style.display !== 'none') {
        item.classList.toggle('open', shouldOpen);
      }
    });
    expandBtn.textContent = shouldOpen ? 'Thu gọn' : 'Mở tất cả';
  });

  searchInput?.addEventListener('input', filterFaqs);
  categoryInput?.addEventListener('change', filterFaqs);
  description?.addEventListener('input', syncCounter);
  syncCounter();

  @if(session('success'))
    if (window.toast) {
      window.toast(@json(session('success')), 'success');
    }
  @endif
})();
</script>
@endpush
