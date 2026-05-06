@props(['role' => 'student'])

@php
  $isTeacher = $role === 'teacher';
  $endpoint = route($role . '.chatbot.message');
  $helpUrl = route($role . '.help');
  $quickPrompts = $isTeacher
    ? ['Tạo bài kiểm tra thế nào?', 'Mời học sinh vào lớp', 'Chấm điểm bài nộp', 'Nâng cấp VIP']
    : ['Cách làm bài kiểm tra', 'Nộp bài tập ở đâu?', 'Xem điểm số', 'Tham gia lớp bằng mã'];
@endphp

<section
  class="vq-chatbot"
  data-chatbot
  data-endpoint="{{ $endpoint }}"
  data-role="{{ $role }}"
  data-user-id="{{ auth()->id() }}"
  aria-label="Trợ lý VietQuiz"
>
  <div class="vq-chatbot__panel" data-chatbot-panel aria-hidden="true">
    <header class="vq-chatbot__header">
      <div class="vq-chatbot__avatar" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2a7 7 0 0 0-7 7v3a7 7 0 0 0 14 0V9a7 7 0 0 0-7-7Z"/>
          <path d="M8 11h.01M16 11h.01M9 16c1.8 1 4.2 1 6 0"/>
          <path d="M4 12H3a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h1M20 12h1a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-1"/>
        </svg>
      </div>
      <div class="vq-chatbot__title">
        <strong>Trợ lý VietQuiz</strong>
        <span>{{ $isTeacher ? 'Hỗ trợ giáo viên' : 'Hỗ trợ học sinh' }}</span>
      </div>
      <button class="vq-chatbot__icon-btn" type="button" data-chatbot-minimize aria-label="Thu nhỏ chatbot">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M5 12h14"/>
        </svg>
      </button>
    </header>

    <div class="vq-chatbot__body" data-chatbot-messages role="log" aria-live="polite"></div>

    <div class="vq-chatbot__quick" aria-label="Câu hỏi gợi ý">
      @foreach($quickPrompts as $prompt)
        <button type="button" data-chatbot-prompt="{{ $prompt }}">{{ $prompt }}</button>
      @endforeach
    </div>

    <form class="vq-chatbot__form" data-chatbot-form>
      <label class="sr-only" for="vq-chatbot-input-{{ $role }}">Nhập câu hỏi</label>
      <textarea
        id="vq-chatbot-input-{{ $role }}"
        data-chatbot-input
        rows="1"
        maxlength="1000"
        placeholder="Nhập câu hỏi của bạn..."
      ></textarea>
      <button type="submit" aria-label="Gửi câu hỏi">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="m22 2-7 20-4-9-9-4Z"/>
          <path d="M22 2 11 13"/>
        </svg>
      </button>
    </form>

    <footer class="vq-chatbot__footer">
      <span>Chatbot trả lời theo hướng dẫn trong hệ thống.</span>
      <a href="{{ $helpUrl }}">Gửi ticket</a>
    </footer>
  </div>

  <button class="vq-chatbot__launcher" type="button" data-chatbot-toggle aria-expanded="false" aria-label="Mở chatbot hỗ trợ">
    <span class="vq-chatbot__pulse" aria-hidden="true"></span>
    <svg class="vq-chatbot__launcher-chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/>
      <path d="M8 10h8M8 14h5"/>
    </svg>
    <svg class="vq-chatbot__launcher-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M18 6 6 18M6 6l12 12"/>
    </svg>
  </button>
</section>
