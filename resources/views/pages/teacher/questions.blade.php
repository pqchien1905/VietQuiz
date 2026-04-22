{{-- Teacher: questions --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Ngân hàng Câu hỏi</h1>
        <p style="color:var(--muted-foreground);">Quản lý và tổ chức tất cả câu hỏi của bạn</p>
      </div>
      <div style="display:flex;gap:.5rem;">
        <button class="btn btn-outline gap-2" onclick="document.getElementById('import-modal').classList.add('open')">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Import CSV
        </button>
        <button class="btn btn-primary gap-2" onclick="document.getElementById('add-modal').classList.add('open')">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Thêm Câu hỏi
        </button>
      </div>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
  @endif

  <!-- Stats -->
  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng câu hỏi</div>
      <div class="stat-card__value">{{ $questions->total() }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Trắc nghiệm</div>
      <div class="stat-card__value">{{ $questions->where('type', 'multiple_choice')->count() }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đúng/Sai</div>
      <div class="stat-card__value">{{ $questions->where('type', 'true_false')->count() }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tự luận</div>
      <div class="stat-card__value">{{ $questions->where('type', 'short_answer')->count() }}</div>
    </div>
  </div>

  <!-- Questions Table -->
  <div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead>
          <tr>
            <th style="width:40%">Nội dung</th>
            <th>Loại</th>
            <th>Bài thi</th>
            <th>Điểm</th>
            <th>Ngày tạo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($questions as $question)
          <tr>
            <td>
              <div style="font-weight:500;">{{ Str::limit($question->content, 80) }}</div>
              @if($question->explanation)
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;">💡 {{ Str::limit($question->explanation, 50) }}</div>
              @endif
            </td>
            <td>
              @if($question->type === 'multiple_choice')
                <span class="badge badge-info">Trắc nghiệm</span>
              @elseif($question->type === 'true_false')
                <span class="badge badge-warning">Đúng/Sai</span>
              @else
                <span class="badge badge-default">Tự luận</span>
              @endif
            </td>
            <td style="font-size:var(--text-sm);">{{ $question->quiz->title ?? '—' }}</td>
            <td><span class="badge badge-outline">{{ $question->points ?? 1 }} đ</span></td>
            <td style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $question->created_at->format('d/m/Y') }}</td>
            <td>
              <form method="POST" action="{{ route('teacher.questions.destroy', $question) }}" onsubmit="return confirm('Xóa câu hỏi này?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--destructive);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
              </form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" style="text-align:center;padding:3rem;color:var(--muted-foreground);">
              <p style="font-size:2rem;margin-bottom:.5rem;">📚</p>
              <p>Chưa có câu hỏi nào. Thêm câu hỏi hoặc import từ CSV!</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($questions->hasPages())
    <div style="padding:1rem;display:flex;justify-content:center;">
      {{ $questions->links() }}
    </div>
    @endif
  </div>

  <!-- Add Question Modal -->
  <div class="modal-overlay" id="add-modal">
    <div class="modal" style="max-width:36rem;">
      <div class="modal-header">
        <h3 class="modal-title">Thêm Câu hỏi</h3>
        <button class="modal-close" onclick="document.getElementById('add-modal').classList.remove('open')">✕</button>
      </div>
      <form method="POST" action="{{ route('teacher.questions.store') }}">
        @csrf
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-group">
            <label class="label label-required">Bài thi</label>
            <select name="quiz_id" class="input select" required>
              <option value="">-- Chọn bài thi --</option>
              @foreach($quizzes as $quiz)
              <option value="{{ $quiz->id }}">{{ $quiz->title }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="label label-required">Loại câu hỏi</label>
            <select name="type" class="input select" required>
              <option value="multiple_choice">Trắc nghiệm</option>
              <option value="true_false">Đúng/Sai</option>
              <option value="short_answer">Tự luận</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label label-required">Nội dung câu hỏi</label>
            <textarea name="content" class="input" style="min-height:4rem;" required></textarea>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="label label-required">Đáp án đúng</label>
              <input type="text" name="answer" class="input" required />
            </div>
            <div class="form-group">
              <label class="label">Điểm</label>
              <input type="number" name="points" class="input" value="1" min="1" />
            </div>
          </div>
          <div class="form-group">
            <label class="label">Giải thích</label>
            <textarea name="explanation" class="input" style="min-height:3rem;"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="document.getElementById('add-modal').classList.remove('open')">Hủy</button>
          <button type="submit" class="btn btn-primary">Thêm</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Import CSV Modal -->
  <div class="modal-overlay" id="import-modal">
    <div class="modal" style="max-width:30rem;">
      <div class="modal-header">
        <h3 class="modal-title">Import CSV</h3>
        <button class="modal-close" onclick="document.getElementById('import-modal').classList.remove('open')">✕</button>
      </div>
      <form method="POST" action="{{ route('teacher.questions.import-csv') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-group">
            <label class="label label-required">Bài thi</label>
            <select name="quiz_id" class="input select" required>
              <option value="">-- Chọn bài thi --</option>
              @foreach($quizzes as $quiz)
              <option value="{{ $quiz->id }}">{{ $quiz->title }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="label label-required">File CSV</label>
            <input type="file" name="csv_file" class="input" accept=".csv,.txt" required />
            <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;">Format: type, content, options (phân cách bởi |), answer, points, explanation</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="document.getElementById('import-modal').classList.remove('open')">Hủy</button>
          <button type="submit" class="btn btn-primary">Import</button>
        </div>
      </form>
    </div>
  </div>
@endsection
