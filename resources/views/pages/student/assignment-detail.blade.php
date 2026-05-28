{{-- Student: assignment-detail --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .assignment-detail-page { display:flex; flex-direction:column; gap:1rem; }
  .assignment-breadcrumb { display:flex; align-items:center; gap:.45rem; color:var(--muted-foreground); font-size:var(--text-sm); }
  .assignment-breadcrumb a { color:var(--muted-foreground); text-decoration:none; font-weight:700; }
  .assignment-breadcrumb a:hover { color:var(--primary); }
  .assignment-detail-grid { display:grid; grid-template-columns:minmax(0,2fr) minmax(18rem,24rem); gap:1.25rem; align-items:start; }
  .assignment-hero { border:1px solid var(--border); border-radius:var(--radius-lg); background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 7%,var(--card)),var(--card)); box-shadow:var(--shadow-sm); overflow:hidden; }
  .assignment-hero-main { display:grid; grid-template-columns:3rem minmax(0,1fr) auto; gap:1rem; align-items:start; padding:1.25rem; }
  .assignment-hero-icon { width:3rem; height:3rem; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; color:var(--primary); background:color-mix(in srgb,var(--primary) 13%,transparent); }
  .assignment-hero-icon svg { width:1.35rem; height:1.35rem; }
  .assignment-title-row { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; min-width:0; }
  .assignment-title-row h1 { margin:0; font-size:var(--text-2xl); font-weight:850; line-height:1.25; letter-spacing:0; }
  .assignment-hero-meta { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; margin-top:.55rem; color:var(--muted-foreground); font-size:var(--text-sm); }
  .assignment-scope-row { display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; margin-top:.85rem; }
  .assignment-scope-chip { display:inline-flex; align-items:center; gap:.45rem; max-width:100%; min-height:2rem; padding:.32rem .7rem; border:1px solid var(--border); border-radius:var(--radius-md); background:color-mix(in srgb,var(--muted) 45%,transparent); color:var(--foreground); font-size:var(--text-xs); font-weight:800; line-height:1.35; }
  .assignment-scope-chip svg { width:1rem; height:1rem; flex-shrink:0; }
  .assignment-scope-chip strong { color:var(--muted-foreground); font-weight:800; }
  .assignment-scope-chip span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .assignment-scope-chip.class-scope { background:color-mix(in srgb,var(--primary) 10%,var(--card)); border-color:color-mix(in srgb,var(--primary) 24%,var(--border)); color:var(--primary); }
  .assignment-scope-chip.course-scope { background:color-mix(in srgb,var(--success) 10%,var(--card)); border-color:color-mix(in srgb,var(--success) 24%,var(--border)); color:var(--success); }
  .assignment-hero-actions { display:flex; gap:.5rem; flex-wrap:wrap; justify-content:flex-end; }
  .assignment-status-strip { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); border-top:1px solid var(--border); background:color-mix(in srgb,var(--muted) 26%,transparent); }
  .assignment-status-item { padding:1rem 1.25rem; border-right:1px solid var(--border); }
  .assignment-status-item:last-child { border-right:0; }
  .assignment-status-label { color:var(--muted-foreground); font-size:var(--text-xs); font-weight:700; margin-bottom:.3rem; }
  .assignment-status-value { color:var(--foreground); font-size:var(--text-base); font-weight:850; line-height:1.3; }
  .assignment-status-note { margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-xs); }
  .assignment-section-card { border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); box-shadow:var(--shadow-sm); overflow:hidden; }
  .assignment-section-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid var(--border); }
  .assignment-section-header h2, .assignment-section-header h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .assignment-section-body { padding:1.25rem; }
  .assignment-description { color:var(--muted-foreground); font-size:var(--text-sm); line-height:1.75; white-space:pre-line; }
  .assignment-empty-text { color:var(--muted-foreground); font-size:var(--text-sm); font-style:italic; }
  .attachment-card, .submission-file-card { display:flex; align-items:center; gap:.8rem; padding:.85rem; border:1px solid var(--border); border-radius:var(--radius-md); background:color-mix(in srgb,var(--muted) 32%,transparent); }
  .attachment-icon { width:2.5rem; height:2.5rem; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--primary); background:color-mix(in srgb,var(--primary) 13%,transparent); }
  .attachment-icon svg { width:1.1rem; height:1.1rem; }
  .attachment-name { font-size:var(--text-sm); font-weight:750; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .attachment-meta { margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-xs); }
  .attachment-actions { display:flex; gap:.45rem; flex-wrap:wrap; justify-content:flex-end; }
  .submission-form-grid { display:flex; flex-direction:column; gap:1rem; }
  .submission-label { display:flex; align-items:baseline; justify-content:space-between; gap:1rem; margin-bottom:.45rem; font-size:var(--text-sm); font-weight:750; }
  .submission-label span { color:var(--muted-foreground); font-size:var(--text-xs); font-weight:500; }
  .submission-textarea { min-height:10rem; resize:vertical; line-height:1.65; font-family:inherit; }
  input[type="file"].assignment-file-input { display:block; height:auto; min-height:3.75rem; padding:1rem; border:2px dashed var(--border); border-radius:var(--radius-md); background:color-mix(in srgb,var(--muted) 42%,transparent); line-height:1.5; }
  .submission-help { margin-top:.35rem; color:var(--muted-foreground); font-size:var(--text-xs); line-height:1.5; }
  .feedback-card { border-color:color-mix(in srgb,var(--success) 28%,var(--border)); background:color-mix(in srgb,var(--success) 6%,var(--card)); }
  .feedback-card .assignment-section-header { border-bottom-color:color-mix(in srgb,var(--success) 24%,var(--border)); }
  .feedback-content { color:var(--muted-foreground); font-size:var(--text-sm); line-height:1.7; white-space:pre-line; }
  .side-stack { display:flex; flex-direction:column; gap:1rem; position:sticky; top:5.25rem; }
  .summary-list { display:flex; flex-direction:column; gap:.8rem; }
  .summary-row { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; color:var(--text-sm); }
  .summary-row span:first-child { color:var(--muted-foreground); font-size:var(--text-sm); }
  .summary-row span:last-child { color:var(--foreground); font-size:var(--text-sm); font-weight:750; text-align:right; }
  .score-panel { padding:1rem; border-radius:var(--radius-md); background:color-mix(in srgb,var(--success) 9%,var(--card)); border:1px solid color-mix(in srgb,var(--success) 25%,var(--border)); }
  .score-panel strong { display:block; color:var(--success); font-size:1.75rem; line-height:1; }
  .score-panel span { display:block; margin-top:.35rem; color:var(--muted-foreground); font-size:var(--text-xs); }
  @media (max-width:1050px) {
    .assignment-detail-grid { grid-template-columns:1fr; }
    .side-stack { position:static; }
  }
  @media (max-width:760px) {
    .assignment-hero-main { grid-template-columns:2.75rem minmax(0,1fr); }
    .assignment-hero-actions { grid-column:2; justify-content:flex-start; }
    .assignment-status-strip { grid-template-columns:1fr; }
    .assignment-status-item { border-right:0; border-bottom:1px solid var(--border); }
    .assignment-status-item:last-child { border-bottom:0; }
    .attachment-card, .submission-file-card { align-items:flex-start; flex-wrap:wrap; }
    .attachment-actions { width:100%; justify-content:flex-start; padding-left:3.3rem; }
  }
  @media (max-width:520px) {
    .assignment-title-row h1 { font-size:var(--text-xl); }
    .assignment-hero-main, .assignment-section-body, .assignment-section-header { padding:1rem; }
  }
</style>
@endpush

@section('content')
  @php
    $dueDate = $assignment->due_at ? \Carbon\Carbon::parse($assignment->due_at) : null;
    $isPast = $dueDate && $dueDate->isPast();
    $isSubmitted = $submission !== null;
    $isGraded = $grade !== null;
    $canSubmit = ! $isPast || $isSubmitted;
    $score = $isGraded && ($assignment->total_points ?? 0) > 0
      ? round(((float) $grade->score / (float) $assignment->total_points) * 100)
      : null;
    $typeLabels = ['file' => 'Nộp file', 'text' => 'Văn bản', 'online' => 'Trực tuyến'];
    $typeLabel = $typeLabels[$assignment->type] ?? 'Bài tập';
    $statusLabel = $isGraded ? 'Đã chấm' : ($isSubmitted ? 'Đã nộp' : ($isPast ? 'Quá hạn' : 'Chưa nộp'));
    $statusClass = $isGraded ? 'badge-success' : ($isSubmitted ? 'badge-info' : ($isPast ? 'badge-danger' : 'badge-warning'));
    $dueTone = $isPast ? 'var(--destructive)' : ($dueDate && $dueDate->diffInHours(now()) <= 48 ? 'var(--warning)' : 'var(--foreground)');
  @endphp

  <div class="assignment-detail-page">
    <nav class="assignment-breadcrumb" aria-label="Breadcrumb">
      <a href="{{ route('student.assignments') }}">Bài tập</a>
      <span>/</span>
      <span>{{ $assignment->title }}</span>
    </nav>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
      <div class="alert alert-info">{{ session('info') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="assignment-detail-grid">
      <main style="display:flex;flex-direction:column;gap:1rem;">
        <section class="assignment-hero">
          <div class="assignment-hero-main">
            <div class="assignment-hero-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
            </div>
            <div style="min-width:0;">
              <div class="assignment-title-row">
                <h1>{{ $assignment->title }}</h1>
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                <span class="badge badge-outline">{{ $typeLabel }}</span>
              </div>
              <div class="assignment-hero-meta">
                <span>GV: {{ $assignment->teacher?->name ?? 'Chưa có giáo viên' }}</span>
                <span>{{ number_format($assignment->total_points ?? 100) }} điểm</span>
                <span style="color:{{ $dueTone }};">
                  @if($dueDate)
                    Hạn {{ $dueDate->format('d/m/Y H:i') }}
                  @else
                    Không giới hạn hạn nộp
                  @endif
                </span>
              </div>
              <div class="assignment-scope-row" aria-label="Phạm vi giao bài">
                @if($assignment->class)
                  <span class="assignment-scope-chip class-scope" title="Lớp học: {{ $assignment->class->name }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    <strong>Lớp học</strong>
                    <span>{{ $assignment->class->name }}</span>
                  </span>
                @endif
                @if($assignment->course)
                  <span class="assignment-scope-chip course-scope" title="Khóa học: {{ $assignment->course->name }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m5 10 7 4 7-4"/><path d="m5 15 7 4 7-4"/></svg>
                    <strong>Khóa học</strong>
                    <span>{{ $assignment->course->name }}</span>
                  </span>
                @endif
                @if(! $assignment->class && ! $assignment->course)
                  <span class="assignment-scope-chip">
                    <strong>Phạm vi</strong>
                    <span>Không rõ lớp hoặc khóa học</span>
                  </span>
                @endif
              </div>
            </div>
            <div class="assignment-hero-actions">
              <a href="{{ route('student.assignments') }}" class="btn btn-outline btn-sm">Quay lại</a>
              @if($assignment->attachment)
                <a href="#attachment-preview" class="btn btn-ghost btn-sm">Tài liệu</a>
              @endif
            </div>
          </div>
          <div class="assignment-status-strip">
            <div class="assignment-status-item">
              <div class="assignment-status-label">Điểm</div>
              @if($isGraded)
                <div class="assignment-status-value" style="color:var(--success);">{{ $grade->score }}/{{ $assignment->total_points ?? 100 }}</div>
                <div class="assignment-status-note">{{ $score }}%</div>
              @else
                <div class="assignment-status-value" style="color:var(--muted-foreground);">Chưa có</div>
                <div class="assignment-status-note">Giáo viên chưa công bố điểm.</div>
              @endif
            </div>
            <div class="assignment-status-item">
              <div class="assignment-status-label">Hạn nộp</div>
              @if($dueDate)
                <div class="assignment-status-value" style="color:{{ $dueTone }};">{{ $dueDate->format('d/m/Y H:i') }}</div>
                <div class="assignment-status-note">{{ $isPast ? 'Đã quá hạn' : $dueDate->diffForHumans() }}</div>
              @else
                <div class="assignment-status-value">Không giới hạn</div>
                <div class="assignment-status-note">Có thể nộp khi bài tập còn mở.</div>
              @endif
            </div>
            <div class="assignment-status-item">
              <div class="assignment-status-label">Bài nộp</div>
              @if($submission?->submitted_at)
                <div class="assignment-status-value">{{ \Carbon\Carbon::parse($submission->submitted_at)->format('d/m/Y H:i') }}</div>
                <div class="assignment-status-note">{{ $isGraded ? 'Đã được chấm' : 'Đang chờ chấm' }}</div>
              @else
                <div class="assignment-status-value" style="color:{{ $isPast ? 'var(--destructive)' : 'var(--warning)' }};">Chưa nộp</div>
                <div class="assignment-status-note">{{ $isPast ? 'Không thể nộp sau hạn.' : 'Hãy gửi bài trước hạn.' }}</div>
              @endif
            </div>
          </div>
        </section>

        <section class="assignment-section-card">
          <div class="assignment-section-header">
            <h2>Yêu cầu bài tập</h2>
          </div>
          <div class="assignment-section-body">
            @if($assignment->description)
              <div class="assignment-description">{{ $assignment->description }}</div>
            @else
              <div class="assignment-empty-text">Giáo viên chưa thêm mô tả hoặc hướng dẫn chi tiết.</div>
            @endif
          </div>
        </section>

        @if($assignment->attachment)
          <section class="assignment-section-card" id="attachment-preview">
            <div class="assignment-section-header">
              <h2>Tài liệu đính kèm</h2>
            </div>
            <div class="assignment-section-body">
              <div class="attachment-card">
                <div class="attachment-icon" aria-hidden="true">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                </div>
                <div style="flex:1;min-width:0;">
                  <div class="attachment-name">{{ $attachmentPreview['filename'] ?? basename($assignment->attachment) }}</div>
                  @if($attachmentPreview)
                    <div class="attachment-meta">{{ strtoupper($attachmentPreview['extension'] ?: 'FILE') }} · {{ $attachmentPreview['mime'] }}</div>
                  @endif
                </div>
                <div class="attachment-actions">
                  @if($attachmentPreview)
                    <a href="{{ $attachmentPreview['preview_url'] }}" target="_blank" class="btn btn-ghost btn-sm">Xem</a>
                    <a href="{{ $attachmentPreview['download_url'] }}" class="btn btn-outline btn-sm">Tải xuống</a>
                  @else
                    <a href="{{ Storage::url($assignment->attachment) }}" target="_blank" class="btn btn-outline btn-sm">Tải xuống</a>
                  @endif
                </div>
              </div>
            </div>
          </section>
        @endif

        <section class="assignment-section-card">
          <div class="assignment-section-header">
            <h2>{{ $isSubmitted ? 'Bài nộp của bạn' : 'Nộp bài' }}</h2>
            @if($isSubmitted)
              <span class="badge {{ $isGraded ? 'badge-success' : 'badge-info' }}">{{ $isGraded ? 'Đã chấm' : 'Đang chờ chấm' }}</span>
            @endif
          </div>
          <div class="assignment-section-body">
            @if($canSubmit)
              <form method="POST" action="{{ route('student.assignment.submit', $assignment) }}" enctype="multipart/form-data" id="submission-form" class="submission-form-grid">
                @csrf

                <div>
                  <label class="submission-label" for="submission-content">
                    Nội dung trả lời
                    <span>Tùy chọn</span>
                  </label>
                  <textarea id="submission-content" name="content" class="input submission-textarea" rows="7" placeholder="Nhập nội dung bài làm tại đây...">{{ old('content', $submission?->content) }}</textarea>
                  @error('content')
                    <p style="color:var(--destructive);font-size:var(--text-xs);margin-top:.25rem;">{{ $message }}</p>
                  @enderror
                </div>

                @if($submission?->attachment)
                  <div>
                    <label class="submission-label">
                      File đã nộp
                      <span>Tải file mới sẽ thay thế file cũ</span>
                    </label>
                    <div class="submission-file-card">
                      <div class="attachment-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                      </div>
                      <div style="flex:1;min-width:0;">
                        <div class="attachment-name">{{ $submissionAttachmentPreview['filename'] ?? basename($submission->attachment) }}</div>
                        @if($submissionAttachmentPreview)
                          <div class="attachment-meta">{{ strtoupper($submissionAttachmentPreview['extension'] ?: 'FILE') }} · {{ $submissionAttachmentPreview['mime'] }}</div>
                        @endif
                      </div>
                      <div class="attachment-actions">
                        @if($submissionAttachmentPreview)
                          <a href="{{ $submissionAttachmentPreview['preview_url'] }}" target="_blank" class="btn btn-ghost btn-sm">Xem</a>
                          <a href="{{ $submissionAttachmentPreview['download_url'] }}" class="btn btn-outline btn-sm">Tải xuống</a>
                        @endif
                      </div>
                    </div>
                  </div>
                @endif

                <div>
                  <label class="submission-label" for="file-input">
                    Đính kèm file
                    <span>Tối đa {{ $submissionAttachmentMaxLabel ?? '100MB' }}</span>
                  </label>
                  <input id="file-input" class="input assignment-file-input" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.png,.jpg,.jpeg,.gif,.webp">
                  <div class="submission-help">Hỗ trợ PDF, DOC, DOCX, XLS, XLSX, ZIP và ảnh. File mới sẽ thay thế file đã nộp trước đó.</div>
                  @error('attachment')
                    <p style="color:var(--destructive);font-size:var(--text-xs);margin-top:.25rem;">{{ $message }}</p>
                  @enderror
                </div>

                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                  <button type="submit" class="btn btn-primary">{{ $isSubmitted ? 'Cập nhật bài nộp' : 'Nộp bài' }}</button>
                  @if($isSubmitted && ! $isPast)
                    <span class="submission-help" style="margin-top:0;">Bạn có thể nộp lại nhiều lần trước hạn.</span>
                  @elseif($isSubmitted && $isPast)
                    <span class="submission-help" style="margin-top:0;">Bài đã quá hạn, hệ thống vẫn cho xem và cập nhật bài đã nộp.</span>
                  @endif
                </div>
              </form>
            @else
              <div class="alert alert-warning">
                Hạn nộp đã qua. Bạn không thể nộp bài mới cho bài tập này.
              </div>
            @endif
          </div>
        </section>

        @if($isGraded)
          <section class="assignment-section-card feedback-card">
            <div class="assignment-section-header">
              <h2>Kết quả chấm điểm</h2>
              <span class="badge badge-success">{{ $grade->score }}/{{ $assignment->total_points ?? 100 }} điểm</span>
            </div>
            <div class="assignment-section-body">
              <div class="feedback-content">
                @if($grade?->feedback)
                  {{ $grade->feedback }}
                @else
                  Giáo viên đã chấm điểm nhưng chưa thêm nhận xét.
                @endif
              </div>
              <div class="submission-help">
                Chấm bởi {{ $grade->grader?->name ?? 'Giáo viên' }}
                @if($grade->graded_at)
                  · {{ \Carbon\Carbon::parse($grade->graded_at)->format('d/m/Y H:i') }}
                @endif
              </div>
            </div>
          </section>
        @endif
      </main>

      <aside class="side-stack">
        @if($isGraded)
          <div class="score-panel">
            <strong>{{ $score }}%</strong>
            <span>{{ $grade->score }}/{{ $assignment->total_points ?? 100 }} điểm</span>
          </div>
        @endif

        <section class="assignment-section-card">
          <div class="assignment-section-header">
            <h3>Thông tin bài tập</h3>
          </div>
          <div class="assignment-section-body summary-list">
            <div class="summary-row">
              <span>Trạng thái</span>
              <span><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></span>
            </div>
            <div class="summary-row">
              <span>Hình thức</span>
              <span>{{ $typeLabel }}</span>
            </div>
            <div class="summary-row">
              <span>Điểm tối đa</span>
              <span>{{ number_format($assignment->total_points ?? 100) }}</span>
            </div>
            <div class="summary-row">
              <span>Giáo viên</span>
              <span>{{ $assignment->teacher?->name ?? 'Chưa có giáo viên' }}</span>
            </div>
            <div class="summary-row">
              <span>Lớp học</span>
              <span>{{ $assignment->class?->name ?? 'Không gắn lớp' }}</span>
            </div>
            <div class="summary-row">
              <span>Khóa học</span>
              <span>{{ $assignment->course?->name ?? 'Không gắn khóa học' }}</span>
            </div>
            <div class="summary-row">
              <span>Ngày giao</span>
              <span>{{ $assignment->created_at->format('d/m/Y') }}</span>
            </div>
            <div class="summary-row">
              <span>Hạn nộp</span>
              <span style="color:{{ $dueTone }};">{{ $dueDate ? $dueDate->format('d/m/Y H:i') : 'Không giới hạn' }}</span>
            </div>
          </div>
        </section>

        <section class="assignment-section-card">
          <div class="assignment-section-body" style="display:flex;flex-direction:column;gap:.55rem;">
            <a href="{{ route('student.assignments') }}" class="btn btn-outline btn-sm" style="justify-content:center;">Quay lại danh sách</a>
            @if($assignment->attachment && $attachmentPreview)
              <a href="{{ $attachmentPreview['download_url'] }}" class="btn btn-ghost btn-sm" style="justify-content:center;">Tải tài liệu</a>
            @endif
          </div>
        </section>
      </aside>
    </div>
  </div>
  <div id="toast-container"></div>
@endsection
