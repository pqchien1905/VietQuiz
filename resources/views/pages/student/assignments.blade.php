{{-- Student: assignments --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .assignment-summary-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:1rem;margin-bottom:1.25rem}
  .assignment-filter .search-input-wrapper{min-width:17rem}
  .assignment-tabs{display:flex;gap:.25rem;padding:.25rem;background:var(--muted);border-radius:var(--radius-md);margin-bottom:1.25rem;max-width:46rem;overflow-x:auto}
  .assignment-tab{display:inline-flex;align-items:center;justify-content:center;gap:.375rem;padding:.5rem .75rem;border-radius:var(--radius-sm);border:1px solid transparent;background:transparent;color:var(--muted-foreground);font-size:var(--text-sm);font-weight:700;text-decoration:none;white-space:nowrap}
  .assignment-tab:hover{background:color-mix(in srgb,var(--background) 70%,transparent);color:var(--foreground);text-decoration:none}
  .assignment-tab.active{background:var(--background);color:var(--foreground);border-color:var(--border);box-shadow:var(--shadow-sm)}
  .assignment-row{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);overflow:hidden;transition:box-shadow var(--transition-fast),transform var(--transition-fast),border-color var(--transition-fast)}
  .assignment-row:hover{box-shadow:var(--shadow-md);transform:translateY(-1px);border-color:color-mix(in srgb,var(--primary) 25%,var(--border))}
  .assignment-row-content{display:flex;gap:1rem;align-items:flex-start;padding:1rem 1.25rem}
  .assignment-icon{width:2.75rem;height:2.75rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .assignment-title{font-size:var(--text-base);font-weight:800;line-height:1.35;margin:0}
  .assignment-meta{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.35rem}
  .assignment-description{font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.55;margin:.75rem 0 0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
  .assignment-score{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-top:.875rem}
  .assignment-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;margin-left:auto}
  .assignment-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.875rem 1.25rem;border-top:1px solid var(--border);background:color-mix(in srgb,var(--muted) 25%,transparent)}
  @media (max-width:1100px){.assignment-summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
  @media (max-width:820px){.assignment-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.assignment-filter .search-input-wrapper{min-width:0}.assignment-row-content{flex-wrap:wrap}.assignment-actions{width:100%;justify-content:flex-start;padding-left:3.75rem}.assignment-footer{align-items:flex-start;flex-direction:column}}
  @media (max-width:520px){.assignment-summary-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
  @if(session('success') || session('info') || session('warning') || $errors->any())
    <div style="margin-bottom:1rem;">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @elseif(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
      @elseif(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
      @elseif($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
      @endif
    </div>
  @endif

  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Bài tập</h1>
        <p>Quản lý bài tập được giao, hạn nộp, bài đã nộp và kết quả chấm điểm.</p>
      </div>
      <a href="{{ route('student.courses') }}" class="btn btn-outline gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m5 10 7 4 7-4"/><path d="m5 15 7 4 7-4"/></svg>
        Khóa học
      </a>
    </div>
  </div>

  <div class="assignment-summary-grid stagger-children">
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

  <form class="toolbar assignment-filter stagger-children" method="GET" action="{{ route('student.assignments') }}">
    <div class="toolbar-left">
      <div class="search-input-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm bài tập, giáo viên, lớp, khóa học..." />
      </div>
      <select class="input select" name="course_id" style="max-width:13rem;">
        <option value="">Tất cả khóa học</option>
        @foreach($courses as $course)
          <option value="{{ $course->id }}" @selected((string) $filters['course_id'] === (string) $course->id)>{{ $course->name }}</option>
        @endforeach
      </select>
      <select class="input select" name="class_id" style="max-width:13rem;">
        <option value="">Tất cả lớp</option>
        @foreach($classes as $class)
          <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
        @endforeach
      </select>
      <select class="input select" name="type" style="max-width:12rem;">
        <option value="all" @selected($filters['type'] === 'all')>Tất cả loại</option>
        <option value="file" @selected($filters['type'] === 'file')>Nộp file</option>
        <option value="text" @selected($filters['type'] === 'text')>Trả lời văn bản</option>
        <option value="online" @selected($filters['type'] === 'online')>Làm trực tuyến</option>
      </select>
    </div>
    <div class="toolbar-right">
      <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
      @if($filters['q'] !== '' || $filters['course_id'] || $filters['class_id'] || $filters['type'] !== 'all' || $filters['status'] !== 'all')
        <a class="btn btn-ghost btn-sm" href="{{ route('student.assignments') }}">Xóa lọc</a>
      @endif
      <span class="text-sm text-muted">{{ $assignments->total() }} kết quả</span>
    </div>
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

  <div class="assignment-tabs">
    <a class="assignment-tab {{ $filters['status'] === 'all' ? 'active' : '' }}" href="{{ $statusQuery('all') }}">Tất cả <span class="badge badge-primary">{{ $summary['total'] }}</span></a>
    <a class="assignment-tab {{ $filters['status'] === 'pending' ? 'active' : '' }}" href="{{ $statusQuery('pending') }}">Chưa nộp <span class="badge badge-warning">{{ $summary['pending'] }}</span></a>
    <a class="assignment-tab {{ $filters['status'] === 'submitted' ? 'active' : '' }}" href="{{ $statusQuery('submitted') }}">Chờ chấm <span class="badge badge-info">{{ $summary['submitted'] }}</span></a>
    <a class="assignment-tab {{ $filters['status'] === 'graded' ? 'active' : '' }}" href="{{ $statusQuery('graded') }}">Đã chấm <span class="badge badge-success">{{ $summary['graded'] }}</span></a>
    <a class="assignment-tab {{ $filters['status'] === 'overdue' ? 'active' : '' }}" href="{{ $statusQuery('overdue') }}">Quá hạn <span class="badge badge-danger">{{ $summary['overdue'] }}</span></a>
  </div>

  @if($assignments->count() === 0)
    <div class="empty-state card">
      <div class="empty-state-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
      </div>
      <h3>Không có bài tập phù hợp</h3>
      <p>Điều chỉnh bộ lọc hoặc chờ giáo viên giao bài tập mới.</p>
    </div>
  @else
    <div class="stagger-children" style="display:flex;flex-direction:column;gap:.875rem;">
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
          $scoreColor = $assignment->score_pct === null ? 'var(--muted-foreground)' : ($assignment->score_pct >= 85 ? 'var(--success)' : ($assignment->score_pct >= 60 ? 'var(--info)' : 'var(--destructive)'));
        @endphp

        <article class="assignment-row">
          <div class="assignment-row-content">
            <div class="assignment-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
            </div>

            <div style="flex:1;min-width:0;">
              <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                <h2 class="assignment-title">{{ $assignment->title }}</h2>
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                <span class="badge badge-outline">{{ $typeLabel }}</span>
              </div>
              <div class="assignment-meta">
                <span>{{ $assignment->scope_name }}</span>
                <span>GV: {{ $assignment->teacher?->name ?? 'Chưa có giáo viên' }}</span>
                <span>{{ $assignment->total_points ?? 100 }} điểm</span>
                <span style="color:{{ $toneColor }};">{{ $assignment->due_label }}</span>
              </div>

              @if($assignment->description)
                <p class="assignment-description">{{ $assignment->description }}</p>
              @endif

              @if($assignment->score_pct !== null)
                <div class="assignment-score">
                  <span style="font-weight:800;color:{{ $scoreColor }};">{{ $assignment->grade->score }}/{{ $assignment->total_points }} điểm ({{ $assignment->score_pct }}%)</span>
                  <div class="progress" style="width:150px;max-width:100%;">
                    <div class="progress-bar" style="width:{{ min(100, $assignment->score_pct) }}%;background:{{ $scoreColor }};"></div>
                  </div>
                  @if($assignment->grade?->feedback)
                    <span class="text-sm text-muted">{{ str($assignment->grade->feedback)->limit(90) }}</span>
                  @endif
                </div>
              @elseif($assignment->submission)
                <div class="assignment-score">
                  <span class="text-sm text-muted">Đã nộp lúc {{ $assignment->submission->submitted_at?->format('d/m/Y H:i') ?? '--' }}</span>
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
            <div class="text-xs text-muted">
              Ngày giao: {{ $assignment->created_at->format('d/m/Y') }}
              @if($assignment->submission?->submitted_at)
                · Nộp: {{ $assignment->submission->submitted_at->format('d/m/Y H:i') }}
              @endif
            </div>
            @if($assignment->attachment)
              <a href="{{ Storage::url($assignment->attachment) }}" target="_blank" class="btn btn-ghost btn-sm">Tài liệu đính kèm</a>
            @endif
          </div>
        </article>
      @endforeach
    </div>

    <div style="margin-top:1.5rem;">
      {{ $assignments->links('components.pagination') }}
    </div>
  @endif
@endsection
