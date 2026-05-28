{{-- Student: assignments --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .assignments-page { display:flex; flex-direction:column; gap:1rem; }
  .assignments-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:.25rem; }
  .assignments-header h1 { margin:0; font-size:var(--text-3xl); font-weight:800; letter-spacing:0; }
  .assignments-header p { margin:.3rem 0 0; color:var(--muted-foreground); font-size:var(--text-sm); line-height:1.55; }
  .assignments-summary-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:1rem; }
  .assignments-summary-grid .stat-card { min-height:6.35rem; padding:1.25rem 1.35rem; }
  .assignments-summary-grid .stat-card__value { font-size:1.9rem; font-weight:800; }
  .assignments-toolbar { display:grid; grid-template-columns:minmax(18rem,1fr) minmax(10rem,13rem) minmax(10rem,13rem) minmax(9rem,11rem) auto auto; gap:.6rem; align-items:center; }
  .assignments-toolbar .input { min-height:2.5rem; }
  .assignments-result-count { color:var(--muted-foreground); font-size:var(--text-sm); white-space:nowrap; }
  .assignments-tabs { display:flex; align-items:center; gap:.3rem; width:max-content; max-width:100%; padding:.3rem; border-radius:var(--radius-lg); background:color-mix(in srgb,var(--muted) 72%,transparent); overflow-x:auto; }
  .assignments-tab { display:inline-flex; align-items:center; justify-content:center; gap:.45rem; min-height:2.2rem; padding:.4rem .8rem; border:1px solid transparent; border-radius:var(--radius-md); color:var(--muted-foreground); font-size:var(--text-sm); font-weight:700; text-decoration:none; white-space:nowrap; }
  .assignments-tab:hover { color:var(--foreground); background:color-mix(in srgb,var(--background) 65%,transparent); text-decoration:none; }
  .assignments-tab.active { color:var(--foreground); background:var(--background); border-color:var(--border); box-shadow:var(--shadow-sm); }
  .assignment-list { display:flex; flex-direction:column; gap:.8rem; }
  .assignment-row { border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); overflow:hidden; box-shadow:var(--shadow-sm); transition:border-color var(--transition-fast), box-shadow var(--transition-fast), transform var(--transition-fast); }
  .assignment-row:hover { border-color:color-mix(in srgb,var(--primary) 28%,var(--border)); box-shadow:var(--shadow-md); transform:translateY(-1px); }
  .assignment-row-main { display:grid; grid-template-columns:2.6rem minmax(0,1fr) auto; gap:1rem; align-items:start; padding:1rem 1.15rem; }
  .assignment-icon { width:2.55rem; height:2.55rem; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; color:var(--primary); background:color-mix(in srgb,var(--primary) 12%,transparent); }
  .assignment-icon svg { width:1.1rem; height:1.1rem; }
  .assignment-heading { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; min-width:0; }
  .assignment-heading h2 { margin:0; font-size:var(--text-base); font-weight:800; line-height:1.35; color:var(--foreground); }
  .assignment-scope-row { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-top:.55rem; }
  .assignment-scope-chip { display:inline-flex; align-items:center; gap:.45rem; max-width:100%; min-height:1.95rem; padding:.3rem .65rem; border:1px solid var(--border); border-radius:var(--radius-md); background:color-mix(in srgb,var(--muted) 48%,transparent); color:var(--foreground); font-size:var(--text-xs); font-weight:700; line-height:1.35; }
  .assignment-scope-chip svg { width:.95rem; height:.95rem; flex-shrink:0; }
  .assignment-scope-chip strong { color:var(--muted-foreground); font-weight:700; }
  .assignment-scope-chip span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .assignment-scope-chip.class-scope { background:color-mix(in srgb,var(--primary) 10%,var(--card)); border-color:color-mix(in srgb,var(--primary) 22%,var(--border)); color:var(--primary); }
  .assignment-scope-chip.course-scope { background:color-mix(in srgb,var(--success) 10%,var(--card)); border-color:color-mix(in srgb,var(--success) 24%,var(--border)); color:var(--success); }
  .assignment-meta { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; margin-top:.3rem; color:var(--muted-foreground); font-size:var(--text-xs); line-height:1.5; }
  .assignment-description { margin:.7rem 0 0; color:var(--muted-foreground); font-size:var(--text-sm); line-height:1.6; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .assignment-submission, .assignment-score { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; margin-top:.75rem; color:var(--muted-foreground); font-size:var(--text-sm); }
  .assignment-score strong { font-size:var(--text-base); font-weight:800; white-space:nowrap; }
  .assignment-score .progress { width:9.5rem; max-width:38vw; height:.42rem; }
  .assignment-actions { display:flex; justify-content:flex-end; align-items:flex-start; min-width:7rem; }
  .assignment-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.75rem 1.15rem; border-top:1px solid var(--border); background:color-mix(in srgb,var(--muted) 28%,transparent); color:var(--muted-foreground); font-size:var(--text-xs); }
  .assignment-footer a { color:var(--foreground); font-weight:700; text-decoration:none; }
  .assignment-footer a:hover { color:var(--primary); }
  .assignment-empty { padding:3rem 1.5rem; text-align:center; }
  .assignment-empty .empty-state-icon { margin:0 auto 1rem; }
  @media (max-width:1180px) {
    .assignments-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .assignments-toolbar { grid-template-columns:minmax(16rem,1fr) repeat(3,minmax(9rem,1fr)) auto; }
    .assignments-result-count { grid-column:1/-1; }
  }
  @media (max-width:820px) {
    .assignments-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .assignments-toolbar { grid-template-columns:1fr; }
    .assignment-row-main { grid-template-columns:2.4rem minmax(0,1fr); }
    .assignment-actions { grid-column:2; justify-content:flex-start; min-width:0; }
    .assignment-footer { align-items:flex-start; flex-direction:column; }
  }
  @media (max-width:540px) {
    .assignments-summary-grid { grid-template-columns:1fr; }
    .assignments-header h1 { font-size:var(--text-2xl); }
    .assignment-row-main { padding:.95rem; gap:.75rem; }
    .assignment-footer { padding:.75rem .95rem; }
  }
</style>
@endpush

@section('content')
  <div class="assignments-page">
    @if(session('success') || session('info') || session('warning') || $errors->any())
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @elseif(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
      @elseif(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
      @elseif($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
      @endif
    @endif

    <div class="assignments-header">
      <div>
        <h1>Bài tập</h1>
        <p>Quản lý bài tập được giao, hạn nộp, bài đã nộp và kết quả chấm điểm.</p>
      </div>
      <a href="{{ route('student.courses') }}" class="btn btn-outline gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m5 10 7 4 7-4"/><path d="m5 15 7 4 7-4"/></svg>
        Khóa học
      </a>
    </div>

    <div class="assignments-summary-grid">
      <div class="stat-card">
        <div class="stat-card__value">{{ number_format($summary['total']) }}</div>
        <div class="stat-card__label">Tổng bài tập</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__value" style="color:var(--warning);">{{ number_format($summary['pending']) }}</div>
        <div class="stat-card__label">Chưa nộp</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__value" style="color:var(--info);">{{ number_format($summary['submitted']) }}</div>
        <div class="stat-card__label">Chờ chấm</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__value" style="color:var(--success);">{{ number_format($summary['graded']) }}</div>
        <div class="stat-card__label">Đã chấm</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__value" style="color:{{ $summary['overdue'] > 0 ? 'var(--destructive)' : 'var(--muted-foreground)' }};">{{ number_format($summary['overdue']) }}</div>
        <div class="stat-card__label">Quá hạn</div>
      </div>
    </div>

    <form class="assignments-toolbar" method="GET" action="{{ route('student.assignments') }}">
      <div class="search-input-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm bài tập, giáo viên, lớp, khóa học...">
      </div>
      <select class="input select" name="course_id" aria-label="Lọc theo khóa học">
        <option value="">Tất cả khóa học</option>
        @foreach($courses as $course)
          <option value="{{ $course->id }}" @selected((string) $filters['course_id'] === (string) $course->id)>{{ $course->name }}</option>
        @endforeach
      </select>
      <select class="input select" name="class_id" aria-label="Lọc theo lớp">
        <option value="">Tất cả lớp</option>
        @foreach($classes as $class)
          <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
        @endforeach
      </select>
      <select class="input select" name="type" aria-label="Lọc theo loại bài tập">
        <option value="all" @selected($filters['type'] === 'all')>Tất cả loại</option>
        <option value="file" @selected($filters['type'] === 'file')>Nộp file</option>
        <option value="text" @selected($filters['type'] === 'text')>Văn bản</option>
        <option value="online" @selected($filters['type'] === 'online')>Trực tuyến</option>
      </select>
      <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
      <span class="assignments-result-count">{{ number_format($assignments->total()) }} kết quả</span>
      @if($filters['q'] !== '' || $filters['course_id'] || $filters['class_id'] || $filters['type'] !== 'all' || $filters['status'] !== 'all')
        <a class="btn btn-ghost btn-sm" href="{{ route('student.assignments') }}">Xóa lọc</a>
      @endif
    </form>

    @php
      $statusQuery = fn ($status) => route('student.assignments', array_filter([
        'q' => $filters['q'] ?: null,
        'course_id' => $filters['course_id'],
        'class_id' => $filters['class_id'],
        'type' => $filters['type'] !== 'all' ? $filters['type'] : null,
        'status' => $status !== 'all' ? $status : null,
      ], fn ($value) => $value !== null && $value !== ''));
    @endphp

    <nav class="assignments-tabs" aria-label="Trạng thái bài tập">
      <a class="assignments-tab {{ $filters['status'] === 'all' ? 'active' : '' }}" href="{{ $statusQuery('all') }}">Tất cả <span class="badge badge-primary">{{ $summary['total'] }}</span></a>
      <a class="assignments-tab {{ $filters['status'] === 'pending' ? 'active' : '' }}" href="{{ $statusQuery('pending') }}">Chưa nộp <span class="badge badge-warning">{{ $summary['pending'] }}</span></a>
      <a class="assignments-tab {{ $filters['status'] === 'submitted' ? 'active' : '' }}" href="{{ $statusQuery('submitted') }}">Chờ chấm <span class="badge badge-info">{{ $summary['submitted'] }}</span></a>
      <a class="assignments-tab {{ $filters['status'] === 'graded' ? 'active' : '' }}" href="{{ $statusQuery('graded') }}">Đã chấm <span class="badge badge-success">{{ $summary['graded'] }}</span></a>
      <a class="assignments-tab {{ $filters['status'] === 'overdue' ? 'active' : '' }}" href="{{ $statusQuery('overdue') }}">Quá hạn <span class="badge badge-danger">{{ $summary['overdue'] }}</span></a>
    </nav>

    @if($assignments->count() === 0)
      <div class="assignment-empty card">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
        </div>
        <h3>Không có bài tập phù hợp</h3>
        <p>Điều chỉnh bộ lọc hoặc chờ giáo viên giao bài tập mới.</p>
      </div>
    @else
      <div class="assignment-list">
        @foreach($assignments as $assignment)
          @php
            $typeLabels = ['file' => 'Nộp file', 'text' => 'Văn bản', 'online' => 'Trực tuyến'];
            $typeLabel = $typeLabels[$assignment->type] ?? 'Bài tập';
            $statusLabel = match($assignment->status) {
                'graded' => 'Đã chấm',
                'submitted' => 'Chờ chấm',
                'overdue' => 'Quá hạn',
                default => 'Chưa nộp',
            };
            $statusClass = match($assignment->status) {
                'graded' => 'badge-success',
                'submitted' => 'badge-info',
                'overdue' => 'badge-danger',
                default => 'badge-warning',
            };
            $toneColor = match($assignment->due_tone) {
                'danger' => 'var(--destructive)',
                'warning' => 'var(--warning)',
                default => 'var(--muted-foreground)',
            };
            $scoreColor = $assignment->score_pct === null
                ? 'var(--muted-foreground)'
                : ($assignment->score_pct >= 85 ? 'var(--success)' : ($assignment->score_pct >= 60 ? 'var(--info)' : 'var(--destructive)'));
            $scorePct = $assignment->score_pct !== null ? rtrim(rtrim(number_format($assignment->score_pct, 1), '0'), '.') : null;
          @endphp

          <article class="assignment-row">
            <div class="assignment-row-main">
              <div class="assignment-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
              </div>

              <div style="min-width:0;">
                <div class="assignment-heading">
                  <h2>{{ $assignment->title }}</h2>
                  <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                  <span class="badge badge-outline">{{ $typeLabel }}</span>
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
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                      <strong>Phạm vi</strong>
                      <span>{{ $assignment->scope_name }}</span>
                    </span>
                  @endif
                </div>
                <div class="assignment-meta">
                  <span>GV: {{ $assignment->teacher?->name ?? 'Chưa có giáo viên' }}</span>
                  <span>{{ number_format($assignment->total_points ?? 100) }} điểm</span>
                  <span style="color:{{ $toneColor }};">{{ $assignment->due_label }}</span>
                </div>

                @if($assignment->description)
                  <p class="assignment-description">{{ $assignment->description }}</p>
                @endif

                @if($assignment->score_pct !== null)
                  <div class="assignment-score">
                    <strong style="color:{{ $scoreColor }};">{{ $assignment->grade->score }}/{{ $assignment->total_points }} điểm ({{ $scorePct }}%)</strong>
                    <div class="progress" aria-hidden="true">
                      <div class="progress-bar" style="width:{{ min(100, $assignment->score_pct) }}%;background:{{ $scoreColor }};"></div>
                    </div>
                    @if($assignment->grade?->feedback)
                      <span>{{ str($assignment->grade->feedback)->limit(95) }}</span>
                    @endif
                  </div>
                @elseif($assignment->submission)
                  <div class="assignment-submission">
                    <span>Đã nộp lúc {{ $assignment->submission->submitted_at?->format('d/m/Y H:i') ?? '--' }}</span>
                    @if($assignment->submission->attachment)
                      <span class="badge badge-default">Có file đính kèm</span>
                    @endif
                  </div>
                @endif
              </div>

              <div class="assignment-actions">
                @if($assignment->status === 'pending')
                  <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-primary btn-sm">Nộp bài</a>
                @elseif($assignment->status === 'overdue')
                  <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-outline btn-sm">Xem yêu cầu</a>
                @else
                  <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-outline btn-sm">{{ $assignment->status === 'graded' ? 'Xem kết quả' : 'Xem bài nộp' }}</a>
                @endif
              </div>
            </div>

            <div class="assignment-footer">
              <div>
                Ngày giao: {{ $assignment->created_at->format('d/m/Y') }}
                @if($assignment->submission?->submitted_at)
                  · Nộp: {{ $assignment->submission->submitted_at->format('d/m/Y H:i') }}
                @endif
              </div>
              @if($assignment->attachment)
                <a href="{{ route('student.assignment-detail', $assignment) }}#attachment-preview">Tài liệu đính kèm</a>
              @endif
            </div>
          </article>
        @endforeach
      </div>

      {{ $assignments->links('components.pagination') }}
    @endif
  </div>
@endsection
