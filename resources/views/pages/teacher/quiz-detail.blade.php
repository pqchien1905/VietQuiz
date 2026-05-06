{{-- Teacher: quiz-detail --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.breadcrumb-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: var(--text-sm);
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.breadcrumb-detail a { color: var(--primary); text-decoration: none; font-weight: 500; }
.breadcrumb-detail a:hover { text-decoration: underline; }
.breadcrumb-sep { color: var(--muted-foreground); }
.breadcrumb-current { color: var(--muted-foreground); }
.grade-a { background: var(--success); color: #fff; }
.grade-b { background: var(--info); color: #fff; }
.grade-c { background: var(--warning); color: #000; }
.grade-d { background: #f97316; color: #fff; }
.grade-f { background: var(--destructive); color: #fff; }

/* Share link */
.share-link-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--muted);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 0.375rem 0.5rem 0.375rem 0.75rem;
    max-width: 360px;
    flex-wrap: wrap;
}
.share-link-box input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: var(--text-sm);
    color: var(--foreground);
    min-width: 0;
    padding: 0;
    outline: none;
}
.copy-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: var(--primary);
    color: var(--primary-foreground);
    border: none;
    border-radius: var(--radius-sm);
    font-size: var(--text-sm);
    font-weight: 500;
    cursor: pointer;
    transition: opacity var(--transition-fast);
    white-space: nowrap;
}
.copy-btn:hover { opacity: 0.85; }
.copy-btn.copied {
    background: var(--success);
}

/* Question management */
.questions-section { margin-top: 1.5rem; }
.questions-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.questions-header h3 { font-size: var(--text-lg); font-weight: 700; margin: 0; }
.question-item {
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--card);
    margin-bottom: 0.75rem;
    overflow: hidden;
    transition: box-shadow var(--transition-fast);
}
.question-item:hover { box-shadow: var(--shadow-sm); }
.question-item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    background: var(--muted);
    border-bottom: 1px solid var(--border);
}
.question-item-num {
    font-weight: 700;
    font-size: var(--text-sm);
    color: var(--muted-foreground);
    min-width: 2rem;
}
.question-type-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: var(--text-xs);
    font-weight: 500;
    background: color-mix(in srgb, var(--primary) 10%, transparent);
    color: var(--primary);
}
.question-item-actions { display: flex; gap: 0.375rem; align-items: center; }
.question-item-body { padding: 0.875rem 1rem; }
.question-item-content { font-weight: 500; margin-bottom: 0.5rem; }
.question-item-answer {
    font-size: var(--text-sm);
    padding: 0.375rem 0.625rem;
    background: color-mix(in srgb, var(--success) 10%, transparent);
    color: var(--success);
    border-radius: var(--radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-weight: 500;
}
.question-item-wrong {
    background: color-mix(in srgb, var(--destructive) 10%, transparent);
    color: var(--destructive);
}
.option-list-item {
    padding: 0.375rem 0;
    font-size: var(--text-sm);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.option-list-item .opt-label {
    font-weight: 600;
    color: var(--muted-foreground);
    font-size: var(--text-xs);
    width: 1.25rem;
}
.option-list-item.is-correct { color: var(--success); font-weight: 500; }

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.modal-box {
    background: var(--card);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-xl);
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
}
.modal-header h3 { font-size: var(--text-lg); font-weight: 700; margin: 0; }
.modal-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border);
}
.option-radio-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: border-color var(--transition-fast), background-color var(--transition-fast);
}
.option-radio-item:hover { border-color: var(--primary); background: color-mix(in srgb, var(--primary) 5%, transparent); }
.option-radio-item.selected { border-color: var(--success); background: color-mix(in srgb, var(--success) 8%, transparent); }
.option-radio-item input { accent-color: var(--success); }
.option-radio-item .opt-letter { font-weight: 700; color: var(--muted-foreground); width: 1.5rem; font-size: var(--text-sm); }
.option-radio-item.selected .opt-letter { color: var(--success); }
.option-radio-item.flex-1 { flex: 1; }
.tf-choice {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    cursor: pointer;
    flex: 1;
    transition: all var(--transition-fast);
}
.tf-choice:hover { border-color: var(--primary); }
.tf-choice.selected-true { border-color: var(--success); background: color-mix(in srgb, var(--success) 10%, transparent); }
.tf-choice.selected-false { border-color: var(--destructive); background: color-mix(in srgb, var(--destructive) 10%, transparent); }
.tf-choice input { accent-color: var(--success); }
.quiz-detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.quiz-main-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.detail-list {
    display: grid;
    gap: 0.875rem;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.875rem;
    border-bottom: 1px solid var(--border);
    font-size: var(--text-sm);
}
.detail-row:last-child { border-bottom: none; padding-bottom: 0; }
.detail-label { color: var(--muted-foreground); }
.detail-value { font-weight: 600; text-align: right; }
.attempt-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border);
    font-size: var(--text-sm);
}
.attempt-row:last-child { border-bottom: none; }
.attempt-student { min-width: 0; }
.attempt-name { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.attempt-meta { color: var(--muted-foreground); font-size: var(--text-xs); margin-top: 0.125rem; }
.empty-icon {
    width: 2.5rem;
    height: 2.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-md);
    color: var(--muted-foreground);
    background: var(--muted);
    margin-bottom: 0.75rem;
}
@media (max-width: 1024px) {
    .quiz-detail-grid,
    .quiz-main-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .attempt-row { grid-template-columns: 1fr; gap: 0.35rem; }
    .detail-row { flex-direction: column; gap: 0.25rem; }
    .detail-value { text-align: left; }
}
</style>
@endpush

@section('content')
<?php
  $quiz = $quiz ?? null;
  $avgScore = $avgScore ?? 0;
  $totalStudents = $quiz->classModel?->students()?->count() ?? 0;
  $submittedCount = $quiz->attempts?->count() ?? 0;
  $unsubmittedCount = max(0, $totalStudents - $submittedCount);
  $attempts = $quiz->attempts ?? collect();
  $submittedAttempts = $attempts->filter(fn($attempt) => $attempt->pivot->submitted_at !== null)->values();
  $submittedCount = $submittedAttempts->count();
  $inProgressCount = max(0, $attempts->count() - $submittedCount);
  $unsubmittedCount = max(0, $totalStudents - $submittedCount - $inProgressCount);
  $questions = $quiz->questions ?? collect();
  $totalPoints = round($questions->sum(fn($question) => (float) ($question->points ?? 0)), 2);
  $passCount = $submittedAttempts->filter(fn($attempt) => $attempt->pivot->total_points > 0
    && round(($attempt->pivot->score / $attempt->pivot->total_points) * 100) >= ($quiz->passing_score ?? 50)
  )->count();
  $passRate = $submittedCount > 0 ? round(($passCount / $submittedCount) * 100) : 0;
  $targetLabel = $quiz->classModel?->name
    ?? ($quiz->course?->title
    ?? (!empty($quiz->assigned_students) ? count($quiz->assigned_students) . ' học sinh được chọn' : ($quiz->public_to_all_students ? 'Mọi người' : 'Chưa chọn phạm vi')));
  $formatDateTime = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : 'Chưa đặt';
  $violationSummary = $violationSummary ?? collect();
  $violationTotal = $violationSummary->sum('total');
  $violationLabels = [
    'tab_hidden' => 'Đổi tab',
    'focus_lost' => 'Mất focus',
    'fullscreen_exit' => 'Thoát fullscreen',
    'copy' => 'Sao chép',
    'cut' => 'Cắt',
    'paste' => 'Dán',
    'context_menu' => 'Chuột phải',
    'blocked_shortcut' => 'Phím tắt bị chặn',
    'devtools_detected' => 'DevTools',
  ];
  $normalizeOptions = function ($raw) {
    if (is_array($raw)) return $raw;
    if (!$raw) return [];
    $decoded = json_decode($raw, true);
    if (is_string($decoded)) {
      $decoded = json_decode($decoded, true);
    }
    return is_array($decoded) ? $decoded : [];
  };

  function gradeLetter($pct) {
    if ($pct >= 90) return 'A';
    if ($pct >= 80) return 'B';
    if ($pct >= 70) return 'C';
    if ($pct >= 60) return 'D';
    return 'F';
  }
  function gradeClass($pct) {
    if ($pct >= 90) return 'grade-a';
    if ($pct >= 80) return 'grade-b';
    if ($pct >= 70) return 'grade-c';
    if ($pct >= 60) return 'grade-d';
    return 'grade-f';
  }
  function gradeColor($pct) {
    if ($pct >= 90) return 'var(--success)';
    if ($pct >= 70) return 'var(--info)';
    if ($pct >= 50) return 'var(--warning)';
    return 'var(--destructive)';
  }
  function timeSpent($start, $end) {
    if (!$start || !$end) return '—';
    $s = \Carbon\Carbon::parse($start);
    $e = \Carbon\Carbon::parse($end);
    $m = (int) $s->diffInMinutes($e);
    return $m . ' phút';
  }
?>

<!-- Breadcrumb -->
<nav class="breadcrumb-detail stagger-children">
  <a href="{{ route('teacher.quizzes') }}">Bài kiểm tra</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-current">{{ $quiz->title ?? 'Chi tiết' }}</span>
</nav>

<!-- Header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;" class="stagger-children">
  <div>
    <h1 style="font-size:var(--text-2xl);font-weight:800;">{{ $quiz->title }}</h1>
    <div style="display:flex;gap:.5rem;margin-top:.5rem;flex-wrap:wrap;">
      @if($quiz->status === 'published')
        <span class="badge badge-success">Hoạt động</span>
      @elseif($quiz->status === 'draft')
        <span class="badge badge-warning">Nháp</span>
      @else
        <span class="badge badge-outline">Lưu trữ</span>
      @endif
      @if($quiz->quiz_type === 'practice')
        <span class="badge" style="background:color-mix(in srgb,var(--info) 12%,transparent);color:var(--info);">Luyện tập</span>
      @else
        <span class="badge" style="background:color-mix(in srgb,var(--warning) 12%,transparent);color:var(--warning);">Kiểm tra</span>
      @endif
      <span class="badge badge-default">{{ $questions->count() }} câu hỏi</span>
      @if($quiz->time_limit)
        <span class="badge badge-default">{{ $quiz->time_limit }} phút</span>
      @endif
      @if($quiz->classModel)
        <span class="badge badge-default">{{ $quiz->classModel->name }}</span>
      @else
        <span class="badge badge-default">Mọi người</span>
      @endif
    </div>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="{{ route('teacher.quizzes') }}" class="btn btn-outline btn-sm gap-1">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Quay lại
    </a>
    @if($quiz->status === 'published')
    <form method="POST" action="{{ route('teacher.quizzes.unpublish', $quiz) }}">
      @csrf
      <button type="submit" class="btn btn-outline btn-sm">Gỡ xuất bản</button>
    </form>
    @else
    <form method="POST" action="{{ route('teacher.quizzes.publish', $quiz) }}">
      @csrf
      <button type="submit" class="btn btn-primary btn-sm gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Xuất bản
      </button>
    </form>
    @endif
  </div>
</div>

<!-- Share link -->
<div style="margin-bottom:1.5rem;" class="stagger-children">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:0.5rem;">
    <h3 style="font-size:var(--text-base);font-weight:600;color:var(--muted-foreground);display:flex;align-items:center;gap:0.375rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
      Chia sẻ liên kết
    </h3>
  </div>
  <div class="share-link-box">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted-foreground);flex-shrink:0;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
    <input type="text" id="share-link-input" value="{{ route('student.quiz-take', $quiz) }}" readonly />
    <button class="copy-btn" id="copy-link-btn" onclick="copyShareLink()">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" id="copy-icon"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
      <span id="copy-label">Sao chép</span>
    </button>
  </div>
  <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.375rem;">
    Gửi liên kết này cho học sinh. Họ sẽ được chuyển thẳng đến bài kiểm tra.
  </p>
</div>

<!-- Stats -->
<div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng lượt làm</div>
    <div class="stat-card__value">{{ $submittedCount }}</div>
    @if($totalStudents > 0)
    <div class="stat-card__label">/ {{ $totalStudents }} học sinh</div>
    @endif
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB</div>
    <div class="stat-card__value" style="color:{{ gradeColor($avgScore) }};">{{ $avgScore ? $avgScore . '%' : '—' }}</div>
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm cao nhất</div>
    @php
      $maxScore = $submittedAttempts->count() > 0 ? $submittedAttempts->max(fn($a) => $a->pivot->total_points > 0 ? round(($a->pivot->score / $a->pivot->total_points) * 100) : 0) : 0;
    @endphp
    <div class="stat-card__value" style="color:{{ gradeColor($maxScore) }};">{{ $maxScore ? $maxScore . '%' : '—' }}</div>
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Chưa làm</div>
    <div class="stat-card__value" style="color:{{ $unsubmittedCount > 0 ? 'var(--warning)' : 'var(--success)' }};">{{ $unsubmittedCount }}</div>
  </div>
</div>

<!-- Quiz Info -->
<div class="quiz-detail-grid stagger-children">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Thông tin bài kiểm tra</h3>
      <p class="card-description">Cấu hình đang áp dụng cho học sinh</p>
    </div>
    <div class="card-content">
      <div class="detail-list">
        <div class="detail-row">
          <span class="detail-label">Mô tả</span>
          <span class="detail-value">{{ $quiz->description ?: 'Chưa có mô tả' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Đối tượng</span>
          <span class="detail-value">{{ $targetLabel }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Thư mục</span>
          <span class="detail-value">{{ $quiz->folder?->name ?? 'Chưa phân loại' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Thang điểm</span>
          <span class="detail-value">{{ $totalPoints > 0 ? $totalPoints : 10 }} điểm</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Điểm đạt</span>
          <span class="detail-value">{{ $quiz->passing_score ?? 50 }}%</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Số lần làm</span>
          <span class="detail-value">{{ $quiz->max_attempts ?? 1 }} lần</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Trộn câu hỏi</span>
          <span class="detail-value">{{ $quiz->is_shuffle || $quiz->shuffle_questions ? 'Bật' : 'Tắt' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Chống gian lận</span>
          <span class="detail-value">{{ $quiz->anti_cheat_enabled ? 'Bật' : 'Tắt' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Mở bài</span>
          <span class="detail-value">{{ $formatDateTime($quiz->start_at) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Hạn nộp</span>
          <span class="detail-value">{{ $formatDateTime($quiz->end_at) }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Báo cáo chống gian lận</h3>
      <p class="card-description">{{ $violationTotal }} vi phạm từ {{ $violationSummary->count() }} học sinh</p>
    </div>
    <div class="card-content">
      @if($violationSummary->count() > 0)
        @foreach($violationSummary->take(8) as $item)
          <div class="attempt-row">
            <div class="attempt-student">
              <div class="attempt-name">{{ $item['student']?->name ?? 'Học sinh không xác định' }}</div>
              <div class="attempt-meta">
                {{ $item['student']?->email ?? 'Không có email' }} · Gần nhất {{ $formatDateTime($item['latest_at']) }}
              </div>
              <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.35rem;">
                @foreach($item['events']->take(4) as $event => $count)
                  <span class="badge badge-warning">{{ $violationLabels[$event] ?? $event }}: {{ $count }}</span>
                @endforeach
              </div>
            </div>
            <span class="badge badge-danger">{{ $item['total'] }} vi phạm</span>
          </div>
        @endforeach
      @else
        <div style="text-align:center;padding:2rem;color:var(--muted-foreground);">
          <span class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
          </span>
          <p style="font-size:var(--text-sm);">Chưa ghi nhận vi phạm chống gian lận.</p>
        </div>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Lượt nộp gần đây</h3>
      <p class="card-description">{{ $passRate }}% đạt yêu cầu, {{ $inProgressCount }} đang làm</p>
    </div>
    <div class="card-content">
      @if($submittedAttempts->count() > 0)
        @foreach($submittedAttempts->take(8) as $attempt)
          @php
            $pct = $attempt->pivot->total_points > 0 ? round(($attempt->pivot->score / $attempt->pivot->total_points) * 100) : 0;
          @endphp
          <div class="attempt-row">
            <div class="attempt-student">
              <div class="attempt-name">{{ $attempt->name }}</div>
              <div class="attempt-meta">{{ $attempt->email }} · Nộp lúc {{ $formatDateTime($attempt->pivot->submitted_at) }}</div>
            </div>
            <span class="badge {{ $pct >= ($quiz->passing_score ?? 50) ? 'badge-success' : 'badge-danger' }}">{{ $pct >= ($quiz->passing_score ?? 50) ? 'Đạt' : 'Chưa đạt' }}</span>
            <span style="font-weight:700;color:{{ gradeColor($pct) }};">{{ $pct }}%</span>
            <span style="color:var(--muted-foreground);font-size:var(--text-xs);">{{ timeSpent($attempt->pivot->started_at, $attempt->pivot->submitted_at) }}</span>
          </div>
        @endforeach
      @else
        <div style="text-align:center;padding:2rem;color:var(--muted-foreground);">
          <span class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          </span>
          <p style="font-size:var(--text-sm);">Chưa có học sinh nào nộp bài.</p>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Main Grid -->
<div class="quiz-main-grid stagger-children" id="attempts">
  <!-- Left: Question Analysis -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Phân tích câu hỏi</h3>
      <p class="card-description">Tỷ lệ trả lời đúng thấp nhất</p>
    </div>
    <div class="card-content" style="padding-top:0;">
      @if($questions->count() > 0)
        @php
          $questionStats = [];
          $allAnswers = [];
          foreach ($attempts as $attempt) {
            if ($attempt->pivot->submitted_at === null) continue;
            $ad = json_decode($attempt->pivot->answers ?? '{}', true) ?: [];
            foreach ($questions as $q) {
              $ua = $ad[$q->id] ?? null;
              if (!isset($questionStats[$q->id])) {
                $questionStats[$q->id] = ['content' => \Illuminate\Support\Str::limit($q->content, 45), 'correct' => 0, 'total' => 0];
              }
              $questionStats[$q->id]['total']++;
              if ($ua !== null && $q->isCorrect($ua)) {
                $questionStats[$q->id]['correct']++;
              }
            }
          }
          $hardest = collect($questionStats)
            ->filter(fn($s) => $s['total'] > 0)
            ->sortBy(fn($s) => $s['total'] > 0 ? ($s['correct'] / $s['total']) : 1)
            ->take(5);
        @endphp
        @if($hardest->count() > 0)
          @foreach($hardest as $qid => $stat)
            @php $rate = $stat['total'] > 0 ? round($stat['correct'] / $stat['total'] * 100) : 0; @endphp
            <div style="margin-bottom:.875rem;">
              <div style="display:flex;justify-content:space-between;font-size:var(--text-sm);margin-bottom:.25rem;">
                <span style="font-weight:500;">{{ $stat['content'] }}</span>
                <span style="font-weight:700;color:{{ $rate < 50 ? 'var(--destructive)' : ($rate < 70 ? 'var(--warning)' : 'var(--success)') }};">{{ $rate }}%</span>
              </div>
              <div class="progress progress-sm">
                <div class="progress-bar {{ $rate < 50 ? 'danger' : ($rate < 70 ? 'warning' : 'success') }}" style="width:{{ $rate }}%;"></div>
              </div>
            </div>
          @endforeach
        @else
          <div style="text-align:center;padding:2rem;color:var(--muted-foreground);">
            <p style="font-size:var(--text-sm);">Chưa có học sinh nào làm bài.</p>
          </div>
        @endif
      @else
        <div style="text-align:center;padding:2rem;color:var(--muted-foreground);">
          <p style="font-size:var(--text-sm);">Bài kiểm tra chưa có câu hỏi.</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Right: Score Distribution -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Phân phối điểm số</h3>
      <p class="card-description">{{ $submittedCount }} lượt đã nộp</p>
    </div>
    <div class="card-content">
      @if($submittedAttempts->count() > 0)
        @php
          $scores = $submittedAttempts->map(fn($a) => $a->pivot->total_points > 0
            ? round(($a->pivot->score / $a->pivot->total_points) * 100)
            : 0)->sort()->values();
          $buckets = ['0-20%'=>0,'21-40%'=>0,'41-60%'=>0,'61-80%'=>0,'81-100%'=>0];
          foreach($scores as $s) {
            if ($s <= 20) $buckets['0-20%']++;
            elseif ($s <= 40) $buckets['21-40%']++;
            elseif ($s <= 60) $buckets['41-60%']++;
            elseif ($s <= 80) $buckets['61-80%']++;
            else $buckets['81-100%']++;
          }
          $maxBucket = max($buckets) ?: 1;
        @endphp
        @foreach($buckets as $range => $count)
          @php
            $barColor = $range === '0-20%' ? 'var(--destructive)' : ($range === '21-40%' ? '#f97316' : ($range === '41-60%' ? 'var(--warning)' : ($range === '61-80%' ? 'var(--info)' : 'var(--success)')));
          @endphp
          <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;font-size:var(--text-sm);">
            <span style="width:3.5rem;font-size:var(--text-xs);color:var(--muted-foreground);">{{ $range }}</span>
            <div style="flex:1;height:1.25rem;background:var(--muted);border-radius:var(--radius-sm);overflow:hidden;">
              <div style="height:100%;width:{{ $count > 0 ? round($count / $maxBucket * 100) : 0 }}%;background:{{ $barColor }};transition:width 0.5s ease;border-radius:var(--radius-sm);"></div>
            </div>
            <span style="width:2rem;text-align:right;font-weight:700;color:var(--muted-foreground);">{{ $count }}</span>
          </div>
        @endforeach
      @else
        <div style="text-align:center;padding:2rem;color:var(--muted-foreground);">
          <span class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          </span>
          <p style="font-size:var(--text-sm);">Chưa có dữ liệu điểm.</p>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Question Management -->
<div class="questions-section stagger-children">
  <div class="questions-header">
    <div>
      <h3>Câu hỏi <span style="color:var(--muted-foreground);font-weight:400;font-size:var(--text-base);">({{ $questions->count() }})</span></h3>
      <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">Thêm, chỉnh sửa hoặc xóa câu hỏi</p>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
      <button type="button" class="btn btn-outline btn-sm gap-1" onclick="openQuestionModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Thêm câu hỏi
      </button>
    </div>
  </div>

  @if($questions->count() === 0)
    <div class="card" style="text-align:center;padding:3rem;color:var(--muted-foreground);">
      <span class="empty-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
      </span>
      <p style="font-size:var(--text-lg);font-weight:600;margin-bottom:0.375rem;">Chưa có câu hỏi nào</p>
      <p style="font-size:var(--text-sm);">Nhấn "Thêm câu hỏi" để bắt đầu.</p>
    </div>
  @else
    <div id="questions-list">
      @foreach($questions as $q)
        @php
          $qNum = $loop->index + 1;
          $typeLabel = $q->type === 'multiple_choice' ? 'Trắc nghiệm' : ($q->type === 'true_false' ? 'Đúng/Sai' : 'Tự luận');
          $typeIcon = $q->type === 'multiple_choice' ? '◉' : ($q->type === 'true_false' ? '✓' : '✎');
          $opts = $normalizeOptions($q->options);
          $correctIdx = $q->correct_answer;
          $labels = ['A','B','C','D','E','F'];
        @endphp
        <div class="question-item" id="q-item-{{ $q->id }}" data-type="{{ $q->type }}" data-correct-answer="{{ $q->correct_answer }}">
          <div class="question-item-header">
            <div style="display:flex;align-items:center;gap:0.75rem;">
              <span class="question-item-num">{{ $qNum }}</span>
              <span class="question-type-tag">{{ $typeIcon }} {{ $typeLabel }}</span>
            </div>
            <div class="question-item-actions">
              <button type="button" class="btn btn-ghost btn-sm" onclick="editQuestion({{ $q->id }})" title="Sửa">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <form method="POST" action="{{ route('teacher.questions.destroy', $q) }}" data-confirm="Xóa câu hỏi này?" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--destructive);" title="Xóa">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
              </form>
            </div>
          </div>
          <div class="question-item-body">
            <div class="question-item-content">{{ $q->content }}</div>

            @if($q->type === 'multiple_choice' && count($opts))
              <div style="display:flex;flex-direction:column;gap:0.25rem;margin-top:0.5rem;">
                @foreach($opts as $oi => $opt)
                  @php $isCorrect = (string)$oi === (string)$correctIdx; @endphp
                  <div class="option-list-item {{ $isCorrect ? 'is-correct' : '' }}">
                    <span class="opt-label">{{ $labels[$oi] ?? ($oi+1) }}.</span>
                    <span style="flex:1;">{{ $opt }}</span>
                    @if($isCorrect)
                      <span style="color:var(--success);font-size:var(--text-xs);font-weight:700;">✓ Đúng</span>
                    @endif
                  </div>
                @endforeach
              </div>
            @elseif($q->type === 'true_false')
              <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                <div class="question-item-answer {{ (string)$correctIdx !== 'true' && (string)$correctIdx !== '1' ? 'question-item-wrong' : '' }}" data-answer="true" style="{{ (string)$correctIdx !== 'true' && (string)$correctIdx !== '1' ? 'opacity:0.4;' : '' }}">
                  ✓ True — Đúng
                </div>
                <div class="question-item-answer {{ (string)$correctIdx === 'true' || (string)$correctIdx === '1' ? 'question-item-wrong' : '' }}" data-answer="false" style="{{ (string)$correctIdx === 'true' || (string)$correctIdx === '1' ? 'opacity:0.4;' : '' }}">
                  ✗ False — Sai
                </div>
              </div>
            @else
              <div style="margin-top:0.5rem;font-size:var(--text-sm);">
                <span style="color:var(--muted-foreground);">Đáp án: </span>
                <span style="font-weight:500;">{{ $q->correct_answer }}</span>
              </div>
            @endif

            @if($q->explanation)
              <div class="explanation-text" style="margin-top:0.5rem;font-size:var(--text-xs);color:var(--muted-foreground);">
                <strong>Giải thích:</strong> {{ $q->explanation }}
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

<!-- Question Editor Modal -->
<div id="question-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:var(--card);border-radius:var(--radius-xl);width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-xl);">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);">
      <h3 style="font-size:var(--text-lg);font-weight:700;margin:0;" id="qmodal-title">Thêm câu hỏi</h3>
      <button onclick="closeQuestionModal()" style="background:none;border:none;cursor:pointer;padding:0.25rem;color:var(--muted-foreground);">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form id="question-form" method="POST" action="{{ route('teacher.questions.store') }}">
      @csrf
      <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
        <input type="hidden" id="q-edit-id" name="edit_id" value="" />
        <input type="hidden" id="q-quiz-id" name="quiz_id" value="{{ $quiz->id }}" />

        <div class="form-group">
          <label class="label label-required">Loại câu hỏi</label>
          <div style="display:flex;gap:0.5rem;flex-wrap:wrap;" id="q-type-selector">
            <button type="button" class="btn btn-outline btn-sm" data-type="multiple_choice" onclick="setQuestionType('multiple_choice')">◉ Trắc nghiệm</button>
            <button type="button" class="btn btn-outline btn-sm" data-type="true_false" onclick="setQuestionType('true_false')">✓ Đúng/Sai</button>
            <button type="button" class="btn btn-outline btn-sm" data-type="short_answer" onclick="setQuestionType('short_answer')">✎ Tự luận</button>
          </div>
          <input type="hidden" id="q-type-input" name="type" value="multiple_choice" />
        </div>

        <div class="form-group">
          <label class="label label-required" for="q-content">Nội dung câu hỏi</label>
          <textarea id="q-content" name="content" class="input @error('content') input-error @enderror"
            style="min-height:5rem;" placeholder="Nhập nội dung câu hỏi..."></textarea>
        </div>

        <!-- Multiple choice options -->
        <div id="q-mc-options" class="form-group">
          <label class="label label-required">Các đáp án</label>
          <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:0.5rem;">Chọn radio bên cạnh đáp án đúng</div>
          <div style="display:flex;flex-direction:column;gap:0.5rem;" id="q-options-list">
            @for($i = 0; $i < 4; $i++)
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <input type="radio" name="correct_option" value="{{ $i }}" id="q-opt-radio-{{ $i }}" style="accent-color:var(--success);" />
                <span style="font-weight:700;color:var(--muted-foreground);width:1.5rem;font-size:var(--text-sm);">{{ chr(65+$i) }}.</span>
                <input type="text" id="q-opt-{{ $i }}" name="options[]"
                  class="input @error('options.'.$i) input-error @enderror"
                  placeholder="Nhập đáp án {{ chr(65+$i) }}..." />
              </div>
            @endfor
          </div>
        </div>

        <!-- True/False options -->
        <div id="q-tf-options" class="form-group" style="display:none;">
          <label class="label label-required">Đáp án đúng</label>
          <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <label class="tf-choice" id="q-tf-true-label" style="flex:1;justify-content:center;">
              <input type="radio" name="tf_correct" value="true" id="q-tf-true" style="accent-color:var(--success);" />
              <span style="font-weight:600;">✓ True — Đúng</span>
            </label>
            <label class="tf-choice" id="q-tf-false-label" style="flex:1;justify-content:center;">
              <input type="radio" name="tf_correct" value="false" id="q-tf-false" style="accent-color:var(--success);" />
              <span style="font-weight:600;">✗ False — Sai</span>
            </label>
          </div>
        </div>

        <!-- Short answer -->
        <div id="q-sa-options" class="form-group" style="display:none;">
          <label class="label label-required" for="q-sa-answer">Đáp án đúng</label>
          <input type="text" id="q-sa-answer" name="sa_answer" class="input" placeholder="Nhập đáp án đúng..." />
        </div>

        <div class="form-group">
          <label class="label" for="q-explanation">Giải thích / Phản hồi (tùy chọn)</label>
          <textarea id="q-explanation" name="explanation" class="input" style="min-height:3rem;"
            placeholder="Giải thích đáp án đúng..."></textarea>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid var(--border);">
        <button type="button" class="btn btn-outline" onclick="closeQuestionModal()">Hủy</button>
        <button type="button" class="btn btn-primary" id="q-save-btn" onclick="saveQuestion()">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Lưu câu hỏi
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
// ─────────────────────────────────────────────
// Copy share link
// ─────────────────────────────────────────────
function copyShareLink() {
    const input = document.getElementById('share-link-input');
    if (!input) return;
    const link = input.value;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link).then(() => {
            showCopySuccess();
        }).catch(() => {
            fallbackCopy(link);
        });
    } else {
        fallbackCopy(link);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try { document.execCommand('copy'); showCopySuccess(); } catch(e) { notifyUser('Sao chép thất bại. Hãy copy thủ công.'); }
    document.body.removeChild(textarea);
}

function showCopySuccess() {
    const btn = document.getElementById('copy-link-btn');
    const label = document.getElementById('copy-label');
    if (!btn) return;
    btn.classList.add('copied');
    if (label) label.textContent = 'Đã sao chép!';
    setTimeout(() => {
        btn.classList.remove('copied');
        if (label) label.textContent = 'Sao chép';
    }, 2000);
}

// ─────────────────────────────────────────────
// Question management
// ─────────────────────────────────────────────
let currentQuestionType = 'multiple_choice';
let editingQuestionId = null;

function notifyUser(message) {
    if (typeof window.showAppAlert === 'function') {
        window.showAppAlert(message);
        return;
    }
    alert(message);
}

// Question type selector
function setQuestionType(type) {
    currentQuestionType = type;
    document.getElementById('q-type-input').value = type;

    // Update button styles
    document.querySelectorAll('#q-type-selector button').forEach(btn => {
        btn.classList.toggle('btn-primary', btn.dataset.type === type);
        btn.classList.toggle('btn-outline', btn.dataset.type !== type);
    });

    // Show/hide option groups
    document.getElementById('q-mc-options').style.display = type === 'multiple_choice' ? '' : 'none';
    document.getElementById('q-tf-options').style.display = type === 'true_false' ? '' : 'none';
    document.getElementById('q-sa-options').style.display = type === 'short_answer' ? '' : 'none';
}

function openQuestionModal() {
    editingQuestionId = null;
    document.getElementById('q-edit-id').value = '';
    document.getElementById('q-content').value = '';
    document.getElementById('q-explanation').value = '';
    document.getElementById('q-sa-answer').value = '';
    document.getElementById('qmodal-title').textContent = 'Thêm câu hỏi';

    // Reset form action to store
    document.getElementById('question-form').action = '{{ route('teacher.questions.store') }}';

    // Reset options
    for (let i = 0; i < 4; i++) {
        const inp = document.getElementById('q-opt-' + i);
        if (inp) inp.value = '';
    }
    document.querySelectorAll('input[name="correct_option"]').forEach(r => r.checked = false);
    document.querySelectorAll('input[name="tf_correct"]').forEach(r => r.checked = false);

    // Reset type to multiple_choice
    setQuestionType('multiple_choice');

    document.getElementById('question-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('q-content')?.focus(), 100);
}

function editQuestion(questionId) {
    const item = document.getElementById('q-item-' + questionId);
    if (!item) return;

    const content = item.querySelector('.question-item-content')?.textContent?.trim() || '';
    const explanation = item.querySelector('.question-item-body .explanation-text')?.textContent?.replace('Giải thích:', '').trim() || '';

    // Determine type from the DOM
    let type = item.dataset.type || 'multiple_choice';
    const typeTag = item.querySelector('.question-type-tag');
    if (typeTag) {
        const tagText = typeTag.textContent;
        if (tagText.includes('Đúng/Sai')) type = 'true_false';
        else if (tagText.includes('Tự luận')) type = 'short_answer';
        else type = 'multiple_choice';
    }

    editingQuestionId = questionId;
    document.getElementById('q-edit-id').value = questionId;
    document.getElementById('q-content').value = content;
    document.getElementById('q-explanation').value = explanation;
    document.getElementById('qmodal-title').textContent = 'Sửa câu hỏi';

    // Update form action to update route
    document.getElementById('question-form').action = '/teacher/questions/' + questionId;

    // Set type
    setQuestionType(type);

    // Parse options from DOM
    if (type === 'multiple_choice') {
        const optItems = item.querySelectorAll('.option-list-item');
        for (let i = 0; i < 4; i++) {
            const inp = document.getElementById('q-opt-' + i);
            if (inp && optItems[i]) {
                inp.value = optItems[i].querySelector('span:nth-child(2)')?.textContent?.trim() || '';
            }
        }
        // Find correct option
        const correctItem = item.querySelector('.option-list-item.is-correct');
        if (correctItem) {
            const allItems = Array.from(item.querySelectorAll('.option-list-item'));
            const correctIdx = allItems.indexOf(correctItem);
            const radio = document.getElementById('q-opt-radio-' + correctIdx);
            if (radio) radio.checked = true;
        }
    } else if (type === 'true_false') {
        const correct = item.dataset.correctAnswer === '1' ? 'true' : item.dataset.correctAnswer;
        const radio = document.querySelector('input[name="tf_correct"][value="' + correct + '"]');
        if (radio) radio.checked = true;
    } else {
        const saDiv = item.querySelector('.question-item-body .question-item-content + div');
        if (saDiv && !saDiv.classList.contains('option-list-item') && !saDiv.classList.contains('question-item-answer')) {
            const saText = saDiv.textContent?.replace('Đáp án:', '').trim() || '';
            document.getElementById('q-sa-answer').value = saText;
        }
    }

    document.getElementById('question-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('q-content')?.focus(), 100);
}

function closeQuestionModal() {
    document.getElementById('question-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function saveQuestion() {
    const content = document.getElementById('q-content').value.trim();
    if (!content) {
        notifyUser('Vui lòng nhập nội dung câu hỏi!');
        return;
    }

    const type = currentQuestionType;
    let correct_answer = '';

    if (type === 'multiple_choice') {
        const radios = document.querySelectorAll('input[name="correct_option"]');
        let selectedIdx = -1;
        radios.forEach((r, i) => { if (r.checked) selectedIdx = i; });
        if (selectedIdx === -1) {
            notifyUser('Vui lòng chọn đáp án đúng!');
            return;
        }
        const options = [];
        for (let i = 0; i < 4; i++) {
            const inp = document.getElementById('q-opt-' + i);
            options.push(inp ? inp.value.trim() : '');
        }
        // Remove empty options at the end
        while (options.length > 0 && options[options.length - 1] === '') options.pop();
        if (options.length < 2) {
            notifyUser('Cần ít nhất 2 đáp án!');
            return;
        }
        correct_answer = String(selectedIdx);
        // Build form data for MC
        submitQuestionForm(content, type, correct_answer, options);
    } else if (type === 'true_false') {
        const tfRadios = document.querySelectorAll('input[name="tf_correct"]');
        tfRadios.forEach(r => { if (r.checked) correct_answer = r.value; });
        if (!correct_answer) {
            notifyUser('Vui lòng chọn đáp án đúng!');
            return;
        }
        submitQuestionForm(content, type, correct_answer, null);
    } else {
        correct_answer = document.getElementById('q-sa-answer').value.trim();
        if (!correct_answer) {
            notifyUser('Vui lòng nhập đáp án đúng!');
            return;
        }
        submitQuestionForm(content, type, correct_answer, null);
    }
}

function submitQuestionForm(content, type, correct_answer, options) {
    const editId = document.getElementById('q-edit-id').value;
    const isEditing = !!editId;
    const url = isEditing ? '/teacher/questions/' + editId : '{{ route('teacher.questions.store') }}';

    const formData = new FormData();
    if (isEditing) formData.append('_method', 'PUT');
    formData.append('_token', '{{ csrf_token() }}');
    if (!isEditing) formData.append('quiz_id', document.getElementById('q-quiz-id').value);
    formData.append('content', content);
    formData.append('type', type);
    formData.append('correct_answer', correct_answer);
    const explanation = document.getElementById('q-explanation').value.trim();
    formData.append('explanation', explanation);

    if (options && Array.isArray(options)) {
        options.forEach((opt) => formData.append('options[]', opt));
    }

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    }).then(response => {
        window.location.reload();
    }).catch(() => {
        window.location.reload();
    });
}

// Close modal on overlay click
document.getElementById('question-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeQuestionModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeQuestionModal();
});

// Init type selector
setQuestionType('multiple_choice');
</script>
@endpush
