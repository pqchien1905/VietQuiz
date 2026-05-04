{{-- Teacher: questions --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $typeLabels = [
      'multiple_choice' => 'Trắc nghiệm',
      'true_false' => 'Đúng/Sai',
      'short_answer' => 'Tự luận',
  ];
  $currentFolderId = $selectedFolder?->id;
@endphp

@push('styles')
<style>
  .question-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem}
  .question-filter-left{display:flex;align-items:center;gap:.75rem;flex:1;flex-wrap:wrap}
  .question-search{position:relative;min-width:260px;flex:1;max-width:420px}
  .question-search svg{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted-foreground)}
  .question-search input{padding-left:2.5rem!important;width:100%}
  .bank-section{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1rem;margin-bottom:1rem}
  .bank-section__head{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
  .bank-section__title{font-size:var(--text-base);font-weight:700;margin:0}
  .bank-section__meta{font-size:var(--text-sm);color:var(--muted-foreground);margin:.2rem 0 0}
  .folder-filter{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap}
  .folder-filter .question-search{max-width:360px}
  .folder-strip{display:flex;gap:.5rem;overflow:auto;padding:.25rem 0 1rem;margin-bottom:.5rem}
  .folder-chip{display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .75rem;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--foreground);text-decoration:none;font-size:var(--text-sm);white-space:nowrap}
  .folder-chip.is-active{border-color:var(--primary);background:color-mix(in srgb,var(--primary) 10%,var(--card));color:var(--primary);font-weight:700}
  .folder-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
  .folder-card{display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:.5rem;padding:1rem;border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--background);color:var(--foreground);transition:border-color .15s ease,transform .15s ease,box-shadow .15s ease;min-height:6.25rem}
  .folder-card:hover{border-color:var(--primary);transform:translateY(-1px);box-shadow:var(--shadow-sm)}
  .folder-card__link{display:grid;grid-template-columns:2.75rem minmax(0,1fr);align-items:flex-start;gap:.9rem;color:var(--foreground);text-decoration:none;min-width:0}
  .folder-card__actions{display:flex;align-items:flex-start;gap:.25rem;margin:0}
  .folder-card__actions form{margin:0}
  .folder-card__actions .btn,.folder-card > .btn{width:2rem;height:2rem;padding:0;display:grid;place-items:center}
  .folder-card__icon{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--primary) 10%,var(--card));color:var(--primary);flex:0 0 auto}
  .folder-card__body{display:flex;flex-direction:column;gap:.35rem;min-width:0}
  .folder-card__name{display:block;font-weight:700;line-height:1.35;overflow-wrap:anywhere}
  .folder-card__meta{font-size:var(--text-sm);color:var(--muted-foreground)}
  .folder-card__footer{display:none}
  .bulk-bar{display:none;align-items:center;justify-content:space-between;gap:1rem;border:1px solid var(--border);border-radius:var(--radius-lg);padding:.75rem 1rem;margin-bottom:1rem;background:var(--card)}
  .bulk-bar.show{display:flex}
  .question-checkbox{width:1rem;height:1rem;accent-color:var(--primary)}
  .ai-modal-overlay.open{display:flex}
  .ai-modal{width:100%;max-width:46rem;border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);color:var(--card-foreground);box-shadow:var(--shadow-xl);overflow:hidden}
  .ai-modal__header,.ai-modal__footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem}
  .ai-modal__header{border-bottom:1px solid var(--border)}
  .ai-modal__footer{border-top:1px solid var(--border);justify-content:flex-end}
  .ai-modal__body{padding:1.25rem;display:flex;flex-direction:column;gap:1rem;max-height:70vh;overflow:auto}
  .ai-modal__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
  .ai-alert{display:none;border-radius:var(--radius-md);padding:.75rem 1rem;font-size:var(--text-sm)}
  .ai-alert.error{display:block;border:1px solid color-mix(in srgb,var(--destructive) 35%,var(--border));background:color-mix(in srgb,var(--destructive) 10%,var(--card));color:var(--destructive)}
  .ai-alert.success{display:block;border:1px solid color-mix(in srgb,var(--success) 35%,var(--border));background:color-mix(in srgb,var(--success) 10%,var(--card));color:var(--success)}
  @media(max-width:760px){
    .question-filter-left,.question-toolbar{align-items:stretch}
    .folder-grid{grid-template-columns:1fr}
    .question-search,.question-filter-left .input,.folder-filter .question-search{max-width:none;width:100%}
    .ai-modal__grid{grid-template-columns:1fr}
  }
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Ngân hàng Câu hỏi</h1>
        <p style="color:var(--muted-foreground);">Tổ chức câu hỏi theo thư mục để dùng nhanh khi tạo bài tập hoặc bài kiểm tra.</p>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        @if(!$isFolderOpen)
        <button class="btn btn-outline gap-2" type="button" onclick="openQuestionModal('folder-modal')">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16"/><path d="M4 4h6l2 3h8v11H4z"/></svg>
          Tạo thư mục
        </button>
        @endif
        @if($isFolderOpen)
        <button class="btn btn-outline gap-2" type="button" onclick="openQuestionModal('ai-modal')">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.6 4.6L18 9.2l-4.4 1.6L12 15.4l-1.6-4.6L6 9.2l4.4-1.6L12 3z"/><path d="M19 14l.8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14z"/></svg>
          Tạo bằng AI
        </button>
        <button class="btn btn-outline gap-2" type="button" onclick="openQuestionModal('import-modal')">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
          Import file
        </button>
        <button class="btn btn-primary gap-2" type="button" onclick="openQuestionModal('add-modal')">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Thêm câu hỏi
        </button>
        @endif
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1rem;">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
  @endif

  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.25rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">{{ $isFolderOpen ? 'Câu hỏi trong thư mục' : 'Tổng câu hỏi' }}</div>
      <div class="stat-card__value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Trắc nghiệm</div>
      <div class="stat-card__value">{{ $stats['multiple_choice'] }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đúng/Sai</div>
      <div class="stat-card__value">{{ $stats['true_false'] }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">{{ $isFolderOpen ? 'Tự luận' : 'Thư mục' }}</div>
      <div class="stat-card__value">{{ $isFolderOpen ? $stats['short_answer'] : $folders->count() }}</div>
    </div>
  </div>

  <div class="folder-strip">
    <a class="folder-chip {{ ($filters['folder_id'] ?? '') === '' ? 'is-active' : '' }}" href="{{ route('teacher.questions', request()->except('folder_id')) }}">Tất cả</a>
    @foreach($folders as $folder)
      <a class="folder-chip {{ (string) ($filters['folder_id'] ?? '') === (string) $folder->id ? 'is-active' : '' }}" href="{{ route('teacher.questions', array_merge(request()->except('page', 'folder_q'), ['folder_id' => $folder->id])) }}">
        {{ $folder->name }}
        <span style="color:var(--muted-foreground);font-weight:600;">{{ $folder->questions_count }}</span>
      </a>
    @endforeach
  </div>

  @if(!$isFolderOpen)
    <section class="bank-section">
      <div class="bank-section__head">
        <div>
          <h2 class="bank-section__title">Thư mục câu hỏi</h2>
          <p class="bank-section__meta">{{ $folderCards->count() }} / {{ $folders->count() }} thư mục đang hiển thị</p>
        </div>
        <form class="folder-filter" method="GET" action="{{ route('teacher.questions') }}">
          <div class="question-search">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="search" class="input" name="folder_q" value="{{ $filters['folder_q'] }}" placeholder="Tìm thư mục..." />
          </div>
          <button class="btn btn-outline btn-sm" type="submit">Lọc</button>
          @if($filters['folder_q'] !== '')
            <a class="btn btn-ghost btn-sm" href="{{ route('teacher.questions') }}">Xóa lọc</a>
          @endif
        </form>
      </div>
      @if($folderCards->isEmpty())
        <div style="text-align:center;padding:3rem;color:var(--muted-foreground);">
          <p style="margin:0 0 .5rem;font-weight:700;color:var(--foreground);">{{ $folders->isEmpty() ? 'Chưa có thư mục' : 'Không tìm thấy thư mục phù hợp' }}</p>
          <p style="margin:0 0 1rem;">{{ $folders->isEmpty() ? 'Tạo thư mục trước, sau đó bấm vào thư mục để thêm câu hỏi.' : 'Thử đổi từ khóa tìm kiếm hoặc xóa lọc để xem lại tất cả thư mục.' }}</p>
          <button class="btn btn-primary" type="button" onclick="openQuestionModal('folder-modal')">Tạo thư mục</button>
        </div>
      @else
        <div class="folder-grid">
          @foreach($folderCards as $folder)
            <div class="folder-card">
              <a class="folder-card__link" href="{{ route('teacher.questions', array_merge(request()->except('page', 'folder_q'), ['folder_id' => $folder->id])) }}">
              <span class="folder-card__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16"/><path d="M4 4h6l2 3h8v11H4z"/></svg>
              </span>
              <span class="folder-card__body">
                <span class="folder-card__name">{{ $folder->name }}</span>
                <span class="folder-card__meta">{{ $folder->questions_count }} câu hỏi</span>
                <span class="folder-card__footer">
                  <span class="folder-stat-pill">Mở thư mục</span>
                </span>
              </span>
              </a>
              <button type="button" class="btn btn-ghost btn-sm" aria-label="Sửa thư mục" onclick="openEditFolderModal({{ $folder->id }}, @js($folder->name))">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              </button>
              <form class="folder-card__actions" method="POST" action="{{ route('teacher.questions.folders.destroy', $folder) }}" data-confirm="Xóa thư mục này? Tất cả câu hỏi trong thư mục cũng sẽ bị xóa.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--destructive);" aria-label="Xóa thư mục">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                </button>
              </form>
            </div>
          @endforeach
        </div>
      @endif
    </section>
  @else
  <div class="bank-section__head">
    <div>
      <h2 class="bank-section__title">{{ $selectedFolder->name }}</h2>
      <p class="bank-section__meta">Bộ lọc chỉ áp dụng cho câu hỏi trong thư mục này.</p>
    </div>
    <a class="btn btn-outline btn-sm" href="{{ route('teacher.questions') }}">Quay lại thư mục</a>
  </div>
  <form class="toolbar question-toolbar" method="GET" action="{{ route('teacher.questions') }}">
    <input type="hidden" name="folder_id" value="{{ $selectedFolder->id }}" />
    <div class="question-filter-left">
      <div class="question-search">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="search" class="input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm nội dung, đáp án, giải thích, bài thi, thư mục..." />
      </div>
      <select class="input select" name="type" style="max-width:190px;">
        <option value="">Tất cả loại</option>
        @foreach($typeLabels as $value => $label)
          <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <select class="input select" name="quiz_id" style="max-width:240px;">
        <option value="">Tất cả bài thi</option>
        @foreach($quizzes as $quiz)
          <option value="{{ $quiz->id }}" @selected((string) $filters['quiz_id'] === (string) $quiz->id)>{{ $quiz->title }}</option>
        @endforeach
      </select>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center;">
      <button class="btn btn-outline btn-sm" type="submit">Lọc</button>
      @if(request()->hasAny(['q', 'type', 'quiz_id']))
        <a class="btn btn-ghost btn-sm" href="{{ route('teacher.questions', ['folder_id' => $selectedFolder->id]) }}">Xóa lọc</a>
      @endif
    </div>
  </form>

  <form method="POST" action="{{ route('teacher.questions.bulk-destroy') }}" id="bulk-delete-form" data-confirm="Xóa các câu hỏi đã chọn?">
    @csrf
    @method('DELETE')
    <div class="bulk-bar" id="bulk-bar">
      <span id="bulk-count" style="font-size:var(--text-sm);font-weight:700;">0 câu hỏi đã chọn</span>
      <div id="bulk-inputs"></div>
      <button type="submit" class="btn btn-destructive btn-sm">Xóa hàng loạt</button>
    </div>
  </form>

  <div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead>
          <tr>
            <th style="width:2.5rem;"><input type="checkbox" class="question-checkbox" id="select-all-questions" onchange="toggleAllQuestions(this.checked)" /></th>
            <th style="width:44%">Nội dung</th>
            <th>Thư mục</th>
            <th>Loại</th>
            <th>Bài thi</th>
            <th>Ngày tạo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($questions as $question)
            <tr>
              <td><input type="checkbox" class="question-checkbox question-select" value="{{ $question->id }}" onchange="updateBulkSelection()" /></td>
              <td>
                <button type="button" class="btn btn-ghost btn-sm" aria-label="Sửa câu hỏi" onclick="openEditQuestionModal(@js([
                  'id' => $question->id,
                  'folder_id' => $question->folder_id,
                  'content' => $question->content,
                  'type' => $question->type,
                  'options' => $question->options ?? [],
                  'correct_answer' => $question->correct_answer,
                  'explanation' => $question->explanation,
                ]))">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </button>
                <div style="font-weight:600;">{{ Str::limit($question->content, 95) }}</div>
                @if($question->explanation)
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;">{{ Str::limit($question->explanation, 70) }}</div>
                @endif
              </td>
              <td style="font-size:var(--text-sm);">{{ $question->folder?->name ?? 'Chưa phân loại' }}</td>
              <td><span class="badge badge-outline">{{ $typeLabels[$question->type] ?? 'Câu hỏi' }}</span></td>
              <td style="font-size:var(--text-sm);">{{ $question->quiz->title ?? 'Ngân hàng' }}</td>
              <td style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $question->created_at->format('d/m/Y') }}</td>
              <td>
                <form method="POST" action="{{ route('teacher.questions.destroy', $question) }}" data-confirm="Xóa câu hỏi này?">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--destructive);" aria-label="Xóa câu hỏi">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="text-align:center;padding:3rem;color:var(--muted-foreground);">
                <p style="margin:0 0 .5rem;font-weight:700;color:var(--foreground);">Chưa có câu hỏi phù hợp</p>
                <p style="margin:0;">Tạo thư mục trước, sau đó thêm thủ công, tạo bằng AI hoặc import từ Excel, PDF, Word, hình ảnh.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($questions->hasPages())
      <div style="padding:1rem;display:flex;justify-content:center;">
        {{ $questions->links('components.pagination') }}
      </div>
    @endif
  </div>

  @endif

  <div class="modal-overlay" id="folder-modal">
    <div class="modal" style="max-width:30rem;">
      <div class="modal-header">
        <h3 class="modal-title">Tạo thư mục câu hỏi</h3>
        <button class="modal-close" type="button" onclick="closeQuestionModal('folder-modal')">×</button>
      </div>
      <form method="POST" action="{{ route('teacher.questions.folders.store') }}">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="label label-required">Tên thư mục</label>
            <input type="text" name="name" class="input" placeholder="VD: Toán 10 - Chương 1" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeQuestionModal('folder-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Tạo thư mục</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="edit-folder-modal">
    <div class="modal" style="max-width:30rem;">
      <div class="modal-header">
        <h3 class="modal-title">Sửa thư mục</h3>
        <button class="modal-close" type="button" onclick="closeQuestionModal('edit-folder-modal')">×</button>
      </div>
      <form method="POST" id="edit-folder-form">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="form-group">
            <label class="label label-required">Tên thư mục</label>
            <input type="text" name="name" id="edit-folder-name" class="input" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeQuestionModal('edit-folder-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Lưu</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="edit-question-modal">
    <div id="question-editor-modal" style="background:var(--card);border-radius:var(--radius-xl);width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-xl);">
      <form method="POST" id="edit-question-form">
        @csrf
        @method('PUT')
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);">
          <h3 style="font-size:var(--text-lg);font-weight:700;margin:0;">Sửa câu hỏi</h3>
          <button type="button" onclick="closeQuestionModal('edit-question-modal')" style="background:none;border:none;cursor:pointer;padding:0.25rem;color:var(--muted-foreground);" aria-label="Đóng">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
          <input type="hidden" name="folder_id" id="edit-question-folder-id" value="{{ $currentFolderId }}" />
          <div class="form-group">
            <label class="label label-required" for="edit-question-type">Loại câu hỏi</label>
            <select name="type" id="edit-question-type" class="input select" required>
              <option value="multiple_choice">Trắc nghiệm</option>
              <option value="true_false">Đúng/Sai</option>
              <option value="short_answer">Tự luận</option>
            </select>
          </div>
          <div class="form-group">
            <label class="label label-required" for="edit-question-content">Nội dung câu hỏi</label>
            <textarea name="content" id="edit-question-content" class="input" style="min-height:5rem;" placeholder="Nhập nội dung câu hỏi..." required></textarea>
          </div>
          <div id="edit-question-answer-editor"></div>
          <input type="hidden" name="correct_answer" id="edit-question-correct-answer" />
          <div class="form-group">
            <label class="label" for="edit-question-explanation">Giải thích / Phản hồi (tùy chọn)</label>
            <textarea name="explanation" id="edit-question-explanation" class="input" style="min-height:3rem;" placeholder="Giải thích đáp án đúng..."></textarea>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid var(--border);">
          <button type="button" class="btn btn-outline" onclick="closeQuestionModal('edit-question-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Lưu câu hỏi</button>
        </div>
      </form>
    </div>
  </div>

  @include('pages.teacher.partials.question-bank-modals', ['folders' => $folders, 'quizzes' => $quizzes, 'currentFolderId' => $currentFolderId])
@endsection

@push('scripts')
<script>
  function openQuestionModal(id) {
    document.getElementById(id)?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeQuestionModal(id) {
    document.getElementById(id)?.classList.remove('open');
    document.body.style.overflow = '';
  }

  function openEditFolderModal(folderId, folderName) {
    const form = document.getElementById('edit-folder-form');
    const name = document.getElementById('edit-folder-name');
    if (form) form.action = '/teacher/questions/folders/' + folderId;
    if (name) name.value = folderName || '';
    openQuestionModal('edit-folder-modal');
  }

  function openEditQuestionModal(question) {
    const form = document.getElementById('edit-question-form');
    if (form) form.action = '/teacher/questions/' + question.id;
    window.currentEditQuestion = question;

    const setValue = (id, value) => {
      const input = document.getElementById(id);
      if (input) input.value = value || '';
    };

    setValue('edit-question-folder-id', question.folder_id);
    setValue('edit-question-type', question.type);
    setValue('edit-question-content', question.content);
    setValue('edit-question-correct-answer', question.correct_answer);
    setValue('edit-question-explanation', question.explanation);
    renderEditQuestionAnswerEditor(question);

    openQuestionModal('edit-question-modal');
  }

  function renderEditQuestionAnswerEditor(question = window.currentEditQuestion || {}) {
    const type = document.getElementById('edit-question-type')?.value || question.type || 'multiple_choice';
    const container = document.getElementById('edit-question-answer-editor');
    const answer = document.getElementById('edit-question-correct-answer');
    if (!container || !answer) return;

    if (type === 'multiple_choice') {
      const options = Array.isArray(question.options) && question.type === 'multiple_choice'
        ? question.options
        : ['', '', '', ''];
      const correct = Number.parseInt(question.correct_answer || '0', 10) || 0;
      answer.value = String(correct);
      container.innerHTML = `
        <div class="form-group">
          <label class="label label-required">Các đáp án</label>
          <div style="display:flex;flex-direction:column;gap:0.5rem;">
            ${['A', 'B', 'C', 'D'].map((letter, index) => `
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <input type="radio" name="correct_option_edit_bank" value="${index}" ${correct === index ? 'checked' : ''} style="accent-color:var(--success);">
                <span style="font-weight:600;width:1.5rem;color:var(--muted-foreground);">${letter}.</span>
                <input type="text" name="options[]" class="input" value="${escapeHtml(options[index] || '')}" placeholder="Nhập đáp án ${letter}..." style="flex:1;" />
              </div>
            `).join('')}
          </div>
          <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">Chọn radio bên cạnh đáp án đúng</div>
        </div>`;
      return;
    }

    if (type === 'true_false') {
      const correct = question.type === 'true_false' ? (question.correct_answer || 'true') : 'true';
      answer.value = correct;
      container.innerHTML = `
        <div class="form-group">
          <label class="label label-required">Đáp án đúng</label>
          <div style="display:flex;gap:1rem;">
            <label class="option-input" style="flex:1;justify-content:center;cursor:pointer;">
              <input type="radio" name="correct_tf_edit_bank" value="true" ${correct !== 'false' ? 'checked' : ''} style="accent-color:var(--success);" />
              <span style="font-weight:600;">✓ True — Đúng</span>
            </label>
            <label class="option-input" style="flex:1;justify-content:center;cursor:pointer;">
              <input type="radio" name="correct_tf_edit_bank" value="false" ${correct === 'false' ? 'checked' : ''} style="accent-color:var(--success);" />
              <span style="font-weight:600;">✗ False — Sai</span>
            </label>
          </div>
        </div>`;
      return;
    }

    const correct = question.type === 'short_answer' ? (question.correct_answer || '') : '';
    answer.value = correct;
    container.innerHTML = `
      <div class="form-group">
        <label class="label label-required" for="edit-sa-answer-bank">Đáp án đúng / ý chính</label>
        <textarea id="edit-sa-answer-bank" class="input" style="min-height:3rem;" placeholder="Nhập đáp án hoặc ý chính để chấm...">${escapeHtml(correct)}</textarea>
      </div>`;
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function renderAddQuestionAnswerEditor() {
    const type = document.getElementById('add-question-type')?.value || 'multiple_choice';
    const container = document.getElementById('add-question-answer-editor');
    const answer = document.getElementById('add-question-correct-answer');
    if (!container || !answer) return;

    if (type === 'multiple_choice') {
      answer.value = '0';
      container.innerHTML = `
        <div class="form-group">
          <label class="label label-required">Các đáp án</label>
          <div style="display:flex;flex-direction:column;gap:0.5rem;">
            ${['A', 'B', 'C', 'D'].map((letter, index) => `
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <input type="radio" name="correct_option_add" value="${index}" ${index === 0 ? 'checked' : ''} style="accent-color:var(--success);">
                <span style="font-weight:600;width:1.5rem;color:var(--muted-foreground);">${letter}.</span>
                <input type="text" name="options[]" class="input" placeholder="Nhập đáp án ${letter}..." style="flex:1;" />
              </div>
            `).join('')}
          </div>
          <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">Chọn radio bên cạnh đáp án đúng</div>
        </div>`;
      return;
    }

    if (type === 'true_false') {
      answer.value = 'true';
      container.innerHTML = `
        <div class="form-group">
          <label class="label label-required">Đáp án đúng</label>
          <div style="display:flex;gap:1rem;">
            <label class="option-input" style="flex:1;justify-content:center;cursor:pointer;">
              <input type="radio" name="correct_tf_add" value="true" checked style="accent-color:var(--success);" />
              <span style="font-weight:600;">✓ True — Đúng</span>
            </label>
            <label class="option-input" style="flex:1;justify-content:center;cursor:pointer;">
              <input type="radio" name="correct_tf_add" value="false" style="accent-color:var(--success);" />
              <span style="font-weight:600;">✗ False — Sai</span>
            </label>
          </div>
        </div>`;
      return;
    }

    answer.value = '';
    container.innerHTML = `
      <div class="form-group">
        <label class="label label-required" for="add-sa-answer">Đáp án đúng / ý chính</label>
        <textarea id="add-sa-answer" class="input" style="min-height:3rem;" placeholder="Nhập đáp án hoặc ý chính để chấm..."></textarea>
      </div>`;
  }

  function selectedQuestionIds() {
    return Array.from(document.querySelectorAll('.question-select:checked')).map(input => input.value);
  }

  function updateBulkSelection() {
    const ids = selectedQuestionIds();
    const bar = document.getElementById('bulk-bar');
    const count = document.getElementById('bulk-count');
    const inputs = document.getElementById('bulk-inputs');
    const selectAll = document.getElementById('select-all-questions');
    const all = Array.from(document.querySelectorAll('.question-select'));

    bar?.classList.toggle('show', ids.length > 0);
    if (count) count.textContent = ids.length + ' câu hỏi đã chọn';
    if (inputs) {
      inputs.innerHTML = ids.map(id => '<input type="hidden" name="question_ids[]" value="' + id + '">').join('');
    }
    if (selectAll) {
      selectAll.checked = all.length > 0 && ids.length === all.length;
      selectAll.indeterminate = ids.length > 0 && ids.length < all.length;
    }
  }

  function toggleAllQuestions(checked) {
    document.querySelectorAll('.question-select').forEach(input => input.checked = checked);
    updateBulkSelection();
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeQuestionModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.modal-overlay.open').forEach(overlay => overlay.classList.remove('open'));
    document.body.style.overflow = '';
  });

  document.getElementById('add-question-form')?.addEventListener('submit', function() {
    const type = document.getElementById('add-question-type')?.value;
    const answer = document.getElementById('add-question-correct-answer');
    if (!answer) return;

    if (type === 'multiple_choice') {
      answer.value = document.querySelector('input[name="correct_option_add"]:checked')?.value || '0';
    } else if (type === 'true_false') {
      answer.value = document.querySelector('input[name="correct_tf_add"]:checked')?.value || 'true';
    } else if (type === 'short_answer') {
      answer.value = document.getElementById('add-sa-answer')?.value.trim() || 'Giáo viên chấm theo ý chính.';
    }
  });

  document.getElementById('add-question-type')?.addEventListener('change', renderAddQuestionAnswerEditor);
  renderAddQuestionAnswerEditor();

  document.getElementById('edit-question-type')?.addEventListener('change', function() {
    const base = {
      ...(window.currentEditQuestion || {}),
      type: this.value,
      content: document.getElementById('edit-question-content')?.value || '',
      explanation: document.getElementById('edit-question-explanation')?.value || '',
    };
    renderEditQuestionAnswerEditor(base);
  });

  document.getElementById('edit-question-form')?.addEventListener('submit', function() {
    const type = document.getElementById('edit-question-type')?.value;
    const answer = document.getElementById('edit-question-correct-answer');
    if (!answer) return;

    if (type === 'multiple_choice') {
      answer.value = document.querySelector('input[name="correct_option_edit_bank"]:checked')?.value || '0';
    } else if (type === 'true_false') {
      answer.value = document.querySelector('input[name="correct_tf_edit_bank"]:checked')?.value || 'true';
    } else if (type === 'short_answer') {
      answer.value = document.getElementById('edit-sa-answer-bank')?.value.trim() || 'Giáo viên chấm theo ý chính.';
    }
  });
</script>
@endpush
