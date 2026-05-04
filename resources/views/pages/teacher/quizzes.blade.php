{{-- Teacher: quizzes --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
    .quiz-title-cell { display:flex; align-items:center; gap:.75rem; }
    .quiz-icon {
        width:2.25rem; height:2.25rem; border-radius:var(--radius-md);
        display:flex; align-items:center; justify-content:center;
        font-size:1rem; flex-shrink:0;
    }
    .quiz-row { cursor:pointer; transition:background .15s ease; }
    .quiz-row:hover { background:color-mix(in srgb,var(--primary) 6%,transparent); }
    .folder-toolbar {
        display:flex; align-items:center; justify-content:space-between;
        flex-wrap:wrap; gap:1rem; margin-bottom:1rem;
    }
    .folder-list { display:flex; flex-wrap:wrap; gap:.5rem; }
    .folder-chip {
        display:inline-flex; align-items:center; gap:.375rem;
        padding:.5rem .75rem; border:1px solid var(--border);
        border-radius:var(--radius-md); color:var(--foreground);
        background:var(--card); font-size:var(--text-sm); text-decoration:none;
    }
    .folder-chip.active {
        border-color:var(--primary);
        background:color-mix(in srgb,var(--primary) 10%,transparent);
        color:var(--primary);
        font-weight:600;
    }
    .folder-create-btn {
        background:var(--primary);
        border-color:var(--primary);
        color:var(--primary-foreground);
    }
    .folder-create-btn:hover {
        background:color-mix(in srgb, var(--primary) 88%, black);
        border-color:color-mix(in srgb, var(--primary) 88%, black);
        color:var(--primary-foreground);
    }
    .folder-select { min-width:10rem; height:2.25rem; padding:.25rem .5rem; font-size:var(--text-sm); }
    .folder-modal {
        display:none;
        position:fixed;
        inset:0;
        z-index:9999;
        align-items:center;
        justify-content:center;
        padding:1rem;
        background:rgba(0,0,0,.5);
    }
    .folder-modal.open { display:flex; }
    .folder-modal__box {
        width:100%;
        max-width:28rem;
        border:1px solid var(--border);
        border-radius:var(--radius-lg);
        background:var(--card);
        color:var(--card-foreground);
        box-shadow:var(--shadow-xl);
    }
    .folder-modal__header,
    .folder-modal__footer {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:1rem;
        padding:1rem 1.25rem;
    }
    .folder-modal__header { border-bottom:1px solid var(--border); }
    .folder-modal__footer { border-top:1px solid var(--border); justify-content:flex-end; }
    .folder-modal__body { padding:1.25rem; }
    .bulk-actions {
        display:none;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
        padding:.875rem 1rem;
        border-bottom:1px solid var(--border);
        background:var(--muted);
    }
    .bulk-actions.open { display:flex; }
    .bulk-actions__summary {
        display:flex;
        align-items:center;
        gap:.5rem;
        font-size:var(--text-sm);
        color:var(--muted-foreground);
        font-weight:600;
    }
    .quiz-select-cell { width:2.75rem; text-align:center; }
    .quiz-select-cell input { width:1rem; height:1rem; accent-color:var(--primary); }
    .quiz-filter-card .card-content { display:flex; flex-direction:column; gap:1rem; }
    .filter-row {
        display:grid;
        grid-template-columns:minmax(16rem, 1.5fr) repeat(3, minmax(9rem, 1fr)) auto auto;
        gap:.75rem;
        align-items:end;
    }
    .filter-field { display:flex; flex-direction:column; gap:.375rem; min-width:0; }
    .filter-field label { font-size:var(--text-xs); color:var(--muted-foreground); font-weight:600; }
    .filter-search { position:relative; }
    .filter-search svg {
        position:absolute; left:.75rem; top:50%; transform:translateY(-50%);
        color:var(--muted-foreground); pointer-events:none;
    }
    .filter-search input { padding-left:2.35rem; }
    @media (max-width: 1100px) {
        .filter-row { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .folder-toolbar { align-items:stretch; }
        .folder-list, .folder-create-btn { width:100%; }
        .filter-row { grid-template-columns:1fr; }
        .filter-row .btn { width:100%; justify-content:center; }
        .bulk-actions { flex-direction:column; align-items:stretch; }
    }
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Bài kiểm tra &amp; Kỳ thi</h1>
        <p style="color:var(--muted-foreground);margin-top:0.25rem;">Tạo, quản lý và phân loại bài kiểm tra của bạn theo thư mục.</p>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;">
      <a href="{{ route('teacher.quiz-create', ['from_bank' => 1]) }}" class="btn btn-outline gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>
        Tạo từ ngân hàng
      </a>
      <a href="{{ route('teacher.quiz-create') }}" class="btn btn-primary gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tạo bài kiểm tra
      </a>
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

  @php
    $activeFolder = $activeFolder ?? 'all';
    $filters = $filters ?? ['q' => '', 'status' => null, 'type' => null, 'course_id' => null];
    $folderQuery = array_filter($filters, fn($value) => $value !== null && $value !== '');
    $hasFilters = count($folderQuery) > 0 || ($activeFolder !== null && $activeFolder !== 'all');
  @endphp
  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Tổng số bài</div>
      <div class="stat-card__value">{{ $quizzes->total() }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Đang hoạt động</div>
      <div class="stat-card__value" style="color:var(--success);">{{ $publishedCount }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Nháp</div>
      <div class="stat-card__value" style="color:var(--warning);">{{ $draftCount }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Đã lưu trữ</div>
      <div class="stat-card__value" style="color:var(--muted-foreground);">{{ $archivedCount }}</div>
    </div>
  </div>

  <div class="card quiz-filter-card" style="margin-bottom:1rem;">
    <div class="card-content">
      <div class="folder-toolbar">
        <div class="folder-list">
          <a class="folder-chip {{ $activeFolder === null || $activeFolder === 'all' ? 'active' : '' }}" href="{{ route('teacher.quizzes', $folderQuery) }}">
            Tất cả
          </a>
          <a class="folder-chip {{ $activeFolder === 'none' ? 'active' : '' }}" href="{{ route('teacher.quizzes', array_merge($folderQuery, ['folder' => 'none'])) }}">
            Chưa phân loại
          </a>
          @foreach($folders as $folder)
            <a class="folder-chip {{ (string) $activeFolder === (string) $folder->id ? 'active' : '' }}" href="{{ route('teacher.quizzes', array_merge($folderQuery, ['folder' => $folder->id])) }}">
              {{ $folder->name }}
              <span class="badge badge-default">{{ $folder->quizzes_count }}</span>
            </a>
          @endforeach
        </div>

        <button type="button" class="btn folder-create-btn" onclick="openFolderModal()">
          Tạo thư mục
        </button>
      </div>

      <form method="GET" action="{{ route('teacher.quizzes') }}" class="filter-row">
        @if($activeFolder !== null && $activeFolder !== 'all')
          <input type="hidden" name="folder" value="{{ $activeFolder }}">
        @endif

        <div class="filter-field">
          <label for="quiz-search">Tìm kiếm</label>
          <div class="filter-search">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" id="quiz-search" name="q" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Tên bài, mô tả, khóa học...">
          </div>
        </div>

        <div class="filter-field">
          <label for="quiz-status-filter">Trạng thái</label>
          <select id="quiz-status-filter" name="status" class="input select">
            <option value="">Tất cả</option>
            <option value="published" @selected(($filters['status'] ?? '') === 'published')>Hoạt động</option>
            <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Nháp</option>
            <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Đã đóng</option>
          </select>
        </div>

        <div class="filter-field">
          <label for="quiz-type-filter">Loại bài</label>
          <select id="quiz-type-filter" name="type" class="input select">
            <option value="">Tất cả</option>
            <option value="exam" @selected(($filters['type'] ?? '') === 'exam')>Kiểm tra</option>
            <option value="practice" @selected(($filters['type'] ?? '') === 'practice')>Luyện tập</option>
          </select>
        </div>

        <div class="filter-field">
          <label for="quiz-course-filter">Khóa học</label>
          <select id="quiz-course-filter" name="course_id" class="input select">
            <option value="">Tất cả</option>
            @foreach($courses as $course)
              <option value="{{ $course->id }}" @selected((string) ($filters['course_id'] ?? '') === (string) $course->id)>
                {{ $course->name ?? $course->title }}
              </option>
            @endforeach
          </select>
        </div>

        <button type="submit" class="btn btn-primary">Lọc</button>
        @if($hasFilters)
          <a href="{{ route('teacher.quizzes') }}" class="btn btn-outline">Xóa lọc</a>
        @endif
      </form>
    </div>
  </div>

  <div class="card stagger-children">
    <form id="bulk-delete-form" method="POST" action="{{ route('teacher.quizzes.bulk-destroy') }}" data-confirm="Xóa các bài kiểm tra đã chọn?">
      @csrf
      @method('DELETE')
    </form>
    <div class="bulk-actions" id="bulk-actions">
      <div class="bulk-actions__summary">
        <span id="selected-quiz-count">0 bài đã chọn</span>
      </div>
      <button type="submit" form="bulk-delete-form" class="btn btn-destructive btn-sm" id="bulk-delete-btn" disabled>
        Xóa đã chọn
      </button>
    </div>
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead>
          <tr>
            <th class="quiz-select-cell">
              <input type="checkbox" id="select-all-quizzes" aria-label="Chọn tất cả bài kiểm tra">
            </th>
            <th>Tên bài thi</th>
            <th>Thư mục</th>
            <th>Câu hỏi</th>
            <th>Lượt thi</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($quizzes as $quiz)
          <tr class="quiz-row" data-href="{{ route('teacher.quiz-detail', $quiz) }}" onclick="handleQuizRowClick(event, this)">
            <td class="quiz-select-cell" data-row-action>
              <input type="checkbox" name="quiz_ids[]" value="{{ $quiz->id }}" form="bulk-delete-form" class="quiz-bulk-checkbox" aria-label="Chọn {{ $quiz->title }}">
            </td>
            <td>
              <div class="quiz-title-cell">
                <div class="quiz-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                  <div style="font-weight:600;">{{ $quiz->title }}</div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">
                    {{ $quiz->course ? ($quiz->course->title ?? $quiz->course->name) : 'Không gắn khóa học' }}
                    @if($quiz->duration_minutes || $quiz->time_limit) • {{ $quiz->duration_minutes ?? $quiz->time_limit }} phút @endif
                  </div>
                </div>
              </div>
            </td>
            <td>
              <form method="POST" action="{{ route('teacher.quizzes.move-folder', $quiz) }}" data-row-action>
                @csrf
                @method('PATCH')
                <select name="folder_id" class="input select folder-select" onchange="this.form.submit()">
                  <option value="">Chưa phân loại</option>
                  @foreach($folders as $folder)
                    <option value="{{ $folder->id }}" @selected($quiz->folder_id === $folder->id)>{{ $folder->name }}</option>
                  @endforeach
                </select>
              </form>
            </td>
            <td><span class="badge badge-default">{{ $quiz->questions_count }} câu</span></td>
            <td>{{ $quiz->attempts_count }} lượt</td>
            <td>
              @if($quiz->status === 'published')
                <span class="badge badge-success">Hoạt động</span>
              @elseif($quiz->status === 'draft')
                <span class="badge badge-warning">Nháp</span>
              @else
                <span class="badge badge-outline">Lưu trữ</span>
              @endif
            </td>
            <td style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $quiz->created_at->format('d/m/Y') }}</td>
            <td>
              <div style="display:flex;gap:.375rem;" data-row-action>
                <a href="{{ route('teacher.quiz-detail', $quiz) }}" class="btn btn-ghost btn-sm" title="Xem chi tiết">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
                <a href="{{ route('teacher.quizzes.edit', $quiz) }}" class="btn btn-ghost btn-sm" title="Chỉnh sửa">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </a>
                <form method="POST" action="{{ route('teacher.quizzes.destroy', $quiz) }}" data-confirm="Xóa bài thi {{ $quiz->title }}?">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--destructive);" title="Xóa">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" style="text-align:center;padding:3rem;color:var(--muted-foreground);">
              <p style="font-weight:500;">Chưa có bài kiểm tra nào</p>
              <p style="font-size:var(--text-sm);">Tạo bài thi đầu tiên để bắt đầu.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{ $quizzes->links('components.pagination') }}

  <div class="folder-modal" id="folder-modal" aria-hidden="true">
    <div class="folder-modal__box" role="dialog" aria-modal="true" aria-labelledby="folder-modal-title">
      <form method="POST" action="{{ route('teacher.quiz-folders.store') }}">
        @csrf
        <div class="folder-modal__header">
          <div>
            <h3 id="folder-modal-title" style="font-size:var(--text-lg);font-weight:700;margin:0;">Tạo thư mục</h3>
            <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:.25rem;">Nhóm các bài kiểm tra theo chủ đề hoặc lớp học.</p>
          </div>
          <button type="button" class="btn btn-ghost btn-sm" onclick="closeFolderModal()" aria-label="Đóng">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </button>
        </div>
        <div class="folder-modal__body">
          <div class="form-group">
            <label class="label label-required" for="folder-name-input">Tên thư mục</label>
            <input type="text" id="folder-name-input" name="name" class="input" maxlength="100" placeholder="Ví dụ: Lớp 12A1, Laravel, Ôn tập..." required>
          </div>
        </div>
        <div class="folder-modal__footer">
          <button type="button" class="btn btn-outline" onclick="closeFolderModal()">Hủy</button>
          <button type="submit" class="btn btn-primary">Tạo thư mục</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
<script>
function handleQuizRowClick(event, row) {
    if (event.target.closest('a, button, input, select, textarea, form, label, [data-row-action]')) {
        return;
    }

    window.location.href = row.dataset.href;
}

function openFolderModal() {
    const modal = document.getElementById('folder-modal');
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('folder-name-input')?.focus(), 50);
}

function closeFolderModal() {
    const modal = document.getElementById('folder-modal');
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.getElementById('folder-modal')?.addEventListener('click', function(event) {
    if (event.target === this) closeFolderModal();
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') closeFolderModal();
});

const selectAllQuizzes = document.getElementById('select-all-quizzes');
const quizBulkCheckboxes = Array.from(document.querySelectorAll('.quiz-bulk-checkbox'));
const bulkActions = document.getElementById('bulk-actions');
const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
const selectedQuizCount = document.getElementById('selected-quiz-count');

function updateBulkDeleteState() {
    const selectedCount = quizBulkCheckboxes.filter(checkbox => checkbox.checked).length;
    if (bulkActions) bulkActions.classList.toggle('open', selectedCount > 0);
    if (bulkDeleteBtn) bulkDeleteBtn.disabled = selectedCount === 0;
    if (selectedQuizCount) selectedQuizCount.textContent = selectedCount + ' bài đã chọn';

    if (selectAllQuizzes) {
        selectAllQuizzes.checked = selectedCount > 0 && selectedCount === quizBulkCheckboxes.length;
        selectAllQuizzes.indeterminate = selectedCount > 0 && selectedCount < quizBulkCheckboxes.length;
    }
}

selectAllQuizzes?.addEventListener('change', function() {
    quizBulkCheckboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkDeleteState();
});

quizBulkCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkDeleteState);
});

updateBulkDeleteState();
</script>
@endpush
