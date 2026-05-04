@php
  $currentFolderId = $currentFolderId ?? null;
  $currentFolder = $currentFolderId ? $folders->firstWhere('id', (int) $currentFolderId) : null;
@endphp

<div class="modal-overlay" id="add-modal">
  <div id="question-editor-modal" style="background:var(--card);border-radius:var(--radius-xl);width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-xl);">
    <form method="POST" action="{{ route('teacher.questions.store') }}" id="add-question-form">
      @csrf
      <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);">
        <h3 style="font-size:var(--text-lg);font-weight:700;margin:0;">Thêm câu hỏi</h3>
        <button type="button" onclick="closeQuestionModal('add-modal')" style="background:none;border:none;cursor:pointer;padding:0.25rem;color:var(--muted-foreground);" aria-label="Đóng">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
        <div class="form-group">
          <label class="label label-required">Thư mục</label>
          <select name="folder_id" class="input select" required>
            <option value="">-- Chọn thư mục --</option>
            @foreach($folders as $folder)
              <option value="{{ $folder->id }}" @selected((string) old('folder_id', $currentFolderId) === (string) $folder->id)>{{ $folder->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="label">Gắn nhanh vào bài thi</label>
          <select name="quiz_id" class="input select">
            <option value="">Chỉ lưu trong ngân hàng</option>
            @foreach($quizzes as $quiz)
              <option value="{{ $quiz->id }}" @selected((string) old('quiz_id') === (string) $quiz->id)>{{ $quiz->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="label label-required" for="add-question-type">Loại câu hỏi</label>
          <select name="type" class="input select" id="add-question-type" required>
            <option value="multiple_choice">Trắc nghiệm</option>
            <option value="true_false">Đúng/Sai</option>
            <option value="short_answer">Tự luận</option>
          </select>
        </div>
        <div class="form-group">
          <label class="label label-required" for="add-question-content">Nội dung câu hỏi</label>
          <textarea id="add-question-content" name="content" class="input" style="min-height:5rem;" placeholder="Nhập nội dung câu hỏi..." required></textarea>
        </div>
        <div id="add-question-answer-editor"></div>
        <input type="hidden" name="correct_answer" id="add-question-correct-answer" value="0" />
        <div class="form-group">
          <label class="label">Giải thích / Phản hồi (tùy chọn)</label>
          <textarea name="explanation" class="input" style="min-height:3rem;" placeholder="Giải thích đáp án đúng..."></textarea>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid var(--border);">
        <button type="button" class="btn btn-outline" onclick="closeQuestionModal('add-modal')">Hủy</button>
        <button type="submit" class="btn btn-primary">Lưu câu hỏi</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay ai-modal-overlay" id="ai-modal">
  <div class="ai-modal">
    <form method="POST" action="{{ route('teacher.questions.generate-ai') }}" enctype="multipart/form-data">
      @csrf
      <div class="ai-modal__header">
        <div>
          <h3 style="font-size:var(--text-lg);font-weight:800;margin:0;">Tạo câu hỏi bằng AI</h3>
          <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">AI sẽ thêm câu hỏi trực tiếp vào thư mục hiện tại, bạn vẫn có thể chỉnh sửa sau khi tạo.</p>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="closeQuestionModal('ai-modal')">Đóng</button>
      </div>
      <div class="ai-modal__body">
        <div class="ai-alert error" id="ai-question-error" style="display:none;"></div>
        <div class="ai-alert success" id="ai-question-success" style="display:none;"></div>
        <div class="form-group">
          <label class="label label-required">Thư mục</label>
          <select name="folder_id" class="input select" required>
            <option value="">-- Chọn thư mục --</option>
            @foreach($folders as $folder)
              <option value="{{ $folder->id }}" @selected((string) old('folder_id', $currentFolderId) === (string) $folder->id)>{{ $folder->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="label">Gắn nhanh vào bài thi</label>
          <select name="quiz_id" class="input select">
            <option value="">Chỉ lưu trong ngân hàng</option>
            @foreach($quizzes as $quiz)
              <option value="{{ $quiz->id }}" @selected((string) old('quiz_id') === (string) $quiz->id)>{{ $quiz->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="label">Chủ đề</label>
          <input type="text" name="topic" class="input" placeholder="VD: Hàm số bậc hai, Laravel routing..." />
        </div>
        <div class="form-group">
          <label class="label">File nguồn cho AI</label>
          <input type="file" name="source_file" class="input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" />
          <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;">AI có thể đọc Word, PDF có lớp chữ và ảnh PNG/JPG/WEBP để tạo câu hỏi. Nếu chọn file, chủ đề có thể để trống.</p>
        </div>
        <div class="ai-modal__grid">
          <div class="form-group">
            <label class="label label-required">Số câu</label>
            <input type="number" name="count" class="input" min="1" max="100" value="10" required />
          </div>
          <div class="form-group">
            <label class="label label-required">Loại câu hỏi</label>
            <select name="type" class="input select" required>
              <option value="mixed">Kết hợp</option>
              <option value="multiple_choice">Trắc nghiệm</option>
              <option value="true_false">Đúng/Sai</option>
              <option value="short_answer">Tự luận ngắn</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label label-required">Độ khó</label>
            <select name="difficulty" class="input select" required>
              <option value="easy">Dễ</option>
              <option value="medium" selected>Trung bình</option>
              <option value="hard">Khó</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="label">Khối/lớp</label>
          <input type="text" name="grade" class="input" placeholder="VD: Lớp 10" />
        </div>
        <div class="form-group">
          <label class="label">Yêu cầu bổ sung</label>
          <textarea name="extra_context" class="input" style="min-height:5rem;resize:vertical;" placeholder="Ví dụ: bám sát SGK, có giải thích ngắn, tránh câu hỏi mẹo..."></textarea>
        </div>
      </div>
      <div class="ai-modal__footer">
        <button type="button" class="btn btn-outline" onclick="closeQuestionModal('ai-modal')">Hủy</button>
        <button type="submit" class="btn btn-primary" id="ai-submit-btn">Tạo câu hỏi</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay ai-modal-overlay" id="import-modal">
  <div class="ai-modal">
    <form method="POST" action="{{ route('teacher.questions.import-file') }}" enctype="multipart/form-data">
      @csrf
      <div class="ai-modal__header">
        <div>
          <h3 style="font-size:var(--text-lg);font-weight:800;margin:0;">Import File tạo câu hỏi</h3>
          <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">Tải đề từ Word/PDF/Excel, hệ thống nhận đáp án tô đỏ, bảng đáp án, lời giải, câu tự luận và đúng/sai rồi đưa vào thư mục hiện tại.</p>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="closeQuestionModal('import-modal')">Đóng</button>
      </div>
      <div class="ai-modal__body">
        <div class="ai-alert error" id="import-file-error" style="display:none;"></div>
        <div class="ai-alert success" id="import-file-success" style="display:none;"></div>
        <input type="hidden" name="type" value="mixed" />
        <input type="hidden" name="difficulty" value="medium" />
        <div class="form-group">
          <label class="label label-required">Thư mục</label>
          <select name="folder_id" class="input select" required>
            <option value="">-- Chọn thư mục --</option>
            @foreach($folders as $folder)
              <option value="{{ $folder->id }}" @selected((string) old('folder_id', $currentFolderId) === (string) $folder->id)>{{ $folder->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="label">Gắn nhanh vào bài thi</label>
          <select name="quiz_id" class="input select">
            <option value="">Chỉ lưu trong ngân hàng</option>
            @foreach($quizzes as $quiz)
              <option value="{{ $quiz->id }}" @selected((string) old('quiz_id') === (string) $quiz->id)>{{ $quiz->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="label label-required">File câu hỏi</label>
          <input type="file" name="source_file" class="input" accept=".xlsx,.xls,.pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" required />
          <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;">DOCX đọc được đáp án tô đỏ, bảng đáp án và lời giải. PDF cần có lớp chữ; PDF scan nên OCR trước. Excel hỗ trợ cột đáp án/lời giải. Ảnh sẽ được AI đọc nội dung.</p>
        </div>
      </div>
      <div class="ai-modal__footer">
        <button type="button" class="btn btn-outline" onclick="closeQuestionModal('import-modal')">Hủy</button>
        <button type="submit" class="btn btn-primary" id="import-submit-btn">Import File</button>
      </div>
    </form>
  </div>
</div>
