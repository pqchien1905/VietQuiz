@extends('layouts.admin')

@section('title', 'Admin - Ngân hàng câu hỏi')
@section('page-title', 'Ngân hàng câu hỏi')
@section('page-description', 'Quản lý câu hỏi độc lập, câu hỏi gắn quiz, thư mục, đáp án, điểm và chất lượng nội dung.')

@php
  $questionTypes = ['multiple_choice', 'true_false', 'short_answer'];
  $summaryCards = [
    ['label' => 'Tổng câu hỏi', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.questions', ['state' => 'all'])],
    ['label' => 'Ngân hàng', 'value' => $summary['bank'], 'tone' => 'var(--info)', 'href' => route('admin.questions', ['scope' => 'bank'])],
    ['label' => 'Trong quiz', 'value' => $summary['quiz'], 'tone' => 'var(--success)', 'href' => route('admin.questions', ['scope' => 'quiz'])],
    ['label' => 'Thư mục', 'value' => $summary['folders'], 'tone' => 'var(--warning)', 'href' => route('admin.questions')],
    ['label' => 'Đã xóa', 'value' => $summary['deleted'], 'tone' => 'var(--destructive)', 'href' => route('admin.questions', ['state' => 'deleted'])],
  ];
  $qualityCards = [
    ['label' => 'Thiếu giải thích', 'value' => $summary['missing_explanation'], 'desc' => 'Nên bổ sung giải thích để học sinh tự học sau khi làm bài.', 'href' => route('admin.questions', ['quality' => 'missing_explanation'])],
    ['label' => 'Thiếu lựa chọn', 'value' => $summary['missing_options'], 'desc' => 'Câu trắc nghiệm cần ít nhất 2 lựa chọn hợp lệ.', 'href' => route('admin.questions', ['quality' => 'missing_options'])],
    ['label' => 'Chưa phân loại', 'value' => $summary['bank'] - $folders->sum('questions_count'), 'desc' => 'Câu ngân hàng chưa nằm trong thư mục.', 'href' => route('admin.questions', ['scope' => 'uncategorized'])],
  ];
@endphp

@push('styles')
<style>
  .questions-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .questions-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .questions-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .questions-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .admin-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .admin-summary-grid .stat-card { min-height:7.25rem; }
  .question-quality-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
  .question-quality-card { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); color:inherit; text-decoration:none; box-shadow:var(--shadow-sm); }
  .question-quality-card strong { display:block; font-size:var(--text-xl); line-height:1; margin-top:.35rem; color:var(--warning); }
  .question-quality-card span { display:block; color:var(--muted-foreground); font-size:var(--text-sm); margin-top:.35rem; }
  .question-filter-grid { display:grid; grid-template-columns:minmax(260px,1fr) repeat(7,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .question-cell { min-width:22rem; }
  .question-meta { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.45rem; }
  .question-source { min-width:12rem; }
  .question-answer { max-width:20rem; }
  .question-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:10rem; }
  .question-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .question-modal-grid .full { grid-column:1/-1; }
  .question-options-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
  .folder-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
  .folder-card { padding:1rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); box-shadow:var(--shadow-sm); }
  .folder-card strong { display:block; font-size:var(--text-base); }
  .folder-card span { display:block; color:var(--muted-foreground); font-size:var(--text-sm); margin-top:.25rem; }
  .folder-actions { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.85rem; }
  .question-empty-teacher { margin-top:.5rem; padding:.75rem; border:1px solid color-mix(in srgb,var(--warning) 35%,var(--border)); border-radius:var(--radius-md); background:color-mix(in srgb,var(--warning) 10%,var(--card)); color:var(--muted-foreground); font-size:var(--text-sm); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
  @media (max-width:1380px) { .question-filter-grid { grid-template-columns:1fr 1fr 1fr; } .folder-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:1100px) { .admin-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .question-quality-grid { grid-template-columns:1fr; } }
  @media (max-width:820px) { .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .question-modal-grid,.question-options-grid { grid-template-columns:1fr; } .question-modal-grid .full { grid-column:auto; } }
  @media (max-width:620px) { .admin-summary-grid,.question-filter-grid,.folder-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('actions')
  <button class="btn btn-outline btn-sm" type="button" onclick="openAdminQuestionModal('create-folder-modal')">Thêm thư mục</button>
  <button class="btn btn-primary btn-sm" type="button" onclick="openAdminQuestionModal('create-question-modal')">Thêm câu hỏi</button>
@endsection

@section('content')
<section class="stats-grid admin-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="question-quality-grid">
  @foreach($qualityCards as $card)
    <a class="question-quality-card" href="{{ $card['href'] }}">
      <div>
        <div class="stat-card__label">{{ $card['label'] }}</div>
        <strong>{{ number_format(max(0, $card['value'])) }}</strong>
        <span>{{ $card['desc'] }}</span>
      </div>
      <span class="badge badge-warning">Cần xử lý</span>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header questions-header">
    <div class="questions-title">
      <h3>Thư mục câu hỏi</h3>
      <p>Nhóm câu hỏi ngân hàng theo giáo viên để tái sử dụng khi tạo quiz.</p>
    </div>
    <button class="btn btn-outline" type="button" onclick="openAdminQuestionModal('create-folder-modal')">Thêm thư mục</button>
  </div>
  <div class="card-content" style="border-top:1px solid var(--border);">
    <div class="folder-grid">
      @forelse($folders->take(8) as $folder)
        <div class="folder-card">
          <strong>{{ $folder->name }}</strong>
          <span>{{ $folder->teacher?->name ?? 'Không rõ giáo viên' }} · {{ number_format($folder->questions_count) }} câu hỏi</span>
          <div class="folder-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('admin.questions', ['folder_id' => $folder->id]) }}">Xem</a>
            <button class="btn btn-primary btn-sm" type="button" onclick="openAdminQuestionModal('edit-folder-{{ $folder->id }}')">Sửa</button>
            <form method="POST" action="{{ route('admin.questions.folders.delete', $folder->id) }}" data-confirm="Xóa thư mục {{ $folder->name }}? Chỉ xóa được khi thư mục không còn câu hỏi." data-confirm-ok="Xóa thư mục">
              @csrf
              @method('DELETE')
              <button class="btn btn-destructive btn-sm" @disabled($folder->questions_count > 0)>Xóa</button>
            </form>
          </div>
        </div>
      @empty
        <div class="empty-state">Chưa có thư mục câu hỏi.</div>
      @endforelse
    </div>
  </div>
</section>

<section class="card">
  <div class="card-header questions-header">
    <div class="questions-title">
      <h3>Danh sách câu hỏi</h3>
      <p>Hiển thị {{ $questions->firstItem() ?? 0 }}-{{ $questions->lastItem() ?? 0 }} trên {{ number_format($questions->total()) }} kết quả.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openAdminQuestionModal('create-question-modal')">Thêm câu hỏi</button>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="question-filter-grid">
      <div class="form-group"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Nội dung, môn học, đáp án"></div>
      <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id"><option value="">Tất cả</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Quiz</label><select class="input select" name="quiz_id"><option value="">Tất cả</option>@foreach($quizzes as $quiz)<option value="{{ $quiz->id }}" @selected((string) request('quiz_id') === (string) $quiz->id)>{{ $quiz->title }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Thư mục</label><select class="input select" name="folder_id"><option value="">Tất cả</option>@foreach($folders as $folder)<option value="{{ $folder->id }}" @selected((string) request('folder_id') === (string) $folder->id)>{{ $folder->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Loại</label><select class="input select" name="type"><option value="">Tất cả</option>@foreach($questionTypes as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ \App\Support\AdminLabels::questionType($type) }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Phạm vi</label><select class="input select" name="scope"><option value="">Tất cả</option><option value="bank" @selected(request('scope') === 'bank')>Ngân hàng</option><option value="quiz" @selected(request('scope') === 'quiz')>Trong quiz</option><option value="uncategorized" @selected(request('scope') === 'uncategorized')>Chưa phân loại</option></select></div>
      <div class="form-group"><label class="label">Chất lượng</label><select class="input select" name="quality"><option value="">Tất cả</option><option value="missing_explanation" @selected(request('quality') === 'missing_explanation')>Thiếu giải thích</option><option value="missing_options" @selected(request('quality') === 'missing_options')>Thiếu lựa chọn</option><option value="zero_points" @selected(request('quality') === 'zero_points')>Không có điểm</option></select></div>
      <div class="form-group"><label class="label">Dữ liệu</label><select class="input select" name="state"><option value="active" @selected(request('state', 'active') === 'active')>Đang dùng</option><option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option><option value="all" @selected(request('state') === 'all')>Tất cả</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sort"><option value="">Mới nhất</option><option value="points" @selected(request('sort') === 'points')>Điểm cao</option><option value="content" @selected(request('sort') === 'content')>Nội dung A-Z</option><option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.questions') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Câu hỏi</th><th>Nguồn</th><th>Đáp án</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($questions as $question)
        <tr style="{{ $question->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
          <td>
            <div class="question-cell">
              <div class="admin-row-title">{{ \Illuminate\Support\Str::limit($question->content, 130) }}</div>
              <div class="question-meta">
                <span class="badge badge-outline">{{ \App\Support\AdminLabels::questionType($question->type) }}</span>
                <span class="badge badge-outline">{{ $question->subject ?: 'Không môn' }}</span>
                <span class="badge badge-info">{{ number_format($question->points) }} điểm</span>
                @if($question->trashed())<span class="badge badge-danger">Đã xóa</span>@endif
                @if(empty($question->explanation))<span class="badge badge-warning">Thiếu giải thích</span>@endif
                @if($question->type === 'multiple_choice' && count($question->getOptionsArray()) < 2)<span class="badge badge-danger">Thiếu lựa chọn</span>@endif
              </div>
            </div>
          </td>
          <td>
            <div class="question-source">
              <div class="admin-row-title">{{ $question->teacher?->name ?? 'Không rõ giáo viên' }}</div>
              <div class="admin-row-meta">{{ $question->quiz?->title ?? $question->folder?->name ?? 'Ngân hàng chung' }}</div>
            </div>
          </td>
          <td><div class="question-answer">{{ \Illuminate\Support\Str::limit($question->correct_answer, 100) }}</div></td>
          <td>
            <div class="question-actions">
              <button class="btn btn-primary btn-sm" type="button" onclick="openAdminQuestionModal('edit-question-{{ $question->id }}')">Sửa</button>
              @if($question->trashed())
                <form method="POST" action="{{ route('admin.questions.restore', $question->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>
              @else
                <form method="POST" action="{{ route('admin.questions.delete', $question->id) }}" data-confirm="Đưa câu hỏi này vào thùng rác?" data-confirm-ok="Xóa câu hỏi">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Xóa</button></form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="empty-state">Không có câu hỏi phù hợp với bộ lọc.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $questions->links('components.pagination') }}</div>
</section>

@include('pages.admin.partials.question-folder-modal', ['modalId' => 'create-folder-modal', 'mode' => 'create', 'folder' => null, 'teachers' => $teachers])
@foreach($folders as $folder)
  @include('pages.admin.partials.question-folder-modal', ['modalId' => 'edit-folder-'.$folder->id, 'mode' => 'edit', 'folder' => $folder, 'teachers' => $teachers])
@endforeach

@include('pages.admin.partials.question-form-modal', [
  'modalId' => 'create-question-modal',
  'mode' => 'create',
  'question' => null,
  'teachers' => $teachers,
  'quizzes' => $quizzes,
  'folders' => $folders,
  'questionTypes' => $questionTypes,
])

@foreach($questions as $question)
  @include('pages.admin.partials.question-form-modal', [
    'modalId' => 'edit-question-'.$question->id,
    'mode' => 'edit',
    'question' => $question,
    'teachers' => $teachers,
    'quizzes' => $quizzes,
    'folders' => $folders,
    'questionTypes' => $questionTypes,
  ])
@endforeach

@push('scripts')
<script>
  function openAdminQuestionModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminQuestionModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminQuestionModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminQuestionModal(overlay.id);
      });
    }
  });

  const oldForm = @json(old('_form'));
  if (oldForm === 'create-question') {
    openAdminQuestionModal('create-question-modal');
  } else if (oldForm === 'create-folder') {
    openAdminQuestionModal('create-folder-modal');
  } else if (oldForm && oldForm.startsWith('edit-question-')) {
    openAdminQuestionModal(oldForm);
  } else if (oldForm && oldForm.startsWith('edit-folder-')) {
    openAdminQuestionModal(oldForm);
  }
</script>
@endpush
@endsection
