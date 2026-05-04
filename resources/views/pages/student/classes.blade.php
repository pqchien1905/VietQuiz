{{-- Student: classes --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .student-class-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1.25rem}
  .student-class-card{display:flex;flex-direction:column;min-height:100%;position:relative}
  .student-class-cover{height:5.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;color:#fff}
  .student-class-icon{width:2.75rem;height:2.75rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);font-size:1.25rem;font-weight:800;line-height:1;flex-shrink:0;overflow:hidden}
  .student-class-icon svg{width:1.35rem;height:1.35rem;display:block}
  .student-class-icon span{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis}
  .student-class-title{font-size:var(--text-base);font-weight:800;line-height:1.25;color:#fff;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
  .student-class-meta{font-size:var(--text-xs);color:rgba(255,255,255,.84);margin-top:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .student-class-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem;margin:1rem 0}
  .student-class-stat{border:1px solid var(--border);border-radius:var(--radius-md);padding:.625rem .5rem;text-align:center;background:color-mix(in srgb,var(--muted) 35%,transparent)}
  .student-class-stat strong{display:block;font-size:var(--text-lg);line-height:1;color:var(--foreground)}
  .student-class-stat span{display:block;font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem}
  .student-class-tabs{display:flex;gap:.25rem;padding:.25rem;background:var(--muted);border-radius:var(--radius-md);margin-bottom:1rem;max-width:34rem}
  .student-class-tab{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:.375rem;padding:.5rem .75rem;border:1px solid transparent;border-radius:var(--radius-sm);background:transparent;color:var(--muted-foreground);font-size:var(--text-sm);font-weight:700;text-decoration:none;white-space:nowrap}
  .student-class-tab:hover{background:color-mix(in srgb,var(--background) 70%,transparent);color:var(--foreground);text-decoration:none}
  .student-class-tab.active{background:var(--background);color:var(--foreground);border-color:var(--border);box-shadow:var(--shadow-sm)}
  .student-class-alert{display:flex;align-items:center;gap:.45rem;margin-top:.75rem;font-size:var(--text-xs);font-weight:700;color:var(--warning)}
  .student-classes-filter .search-input-wrapper{min-width:17rem}
  .class-card-link{position:absolute;inset:0;z-index:1}
  .class-card-actions,.class-card-form{position:relative;z-index:2}
  @media (max-width:900px){.student-class-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.student-classes-filter .search-input-wrapper{min-width:0}.student-class-tabs{max-width:none;overflow-x:auto}.student-class-tab{min-width:8.5rem}}
  @media (max-width:560px){.student-class-summary{grid-template-columns:1fr}.student-class-stats{grid-template-columns:1fr}.student-class-cover{height:auto;min-height:5.25rem}}
</style>
@endpush

@section('content')
  @php
    $statusUrl = function (string $status) use ($filters) {
      $params = ['status' => $status];
      if (($filters['q'] ?? '') !== '') {
        $params['q'] = $filters['q'];
      }
      if (($filters['subject'] ?? '') !== '') {
        $params['subject'] = $filters['subject'];
      }

      return route('student.classes', $params);
    };
    $hasActiveFilters = ($filters['q'] ?? '') !== ''
      || ($filters['subject'] ?? '') !== ''
      || ($filters['status'] ?? 'all') !== 'all';
  @endphp

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
        <h1>Lớp học của tôi</h1>
        <p>Danh sách lớp đã tham gia, tiến độ học tập và nội dung được giao trong từng lớp.</p>
      </div>
      <a href="{{ route('student.join-class') }}" class="btn btn-primary gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tham gia lớp
      </a>
    </div>
  </div>

  <div class="student-class-summary stagger-children">
    <div class="stat-card">
      <div class="stat-card__value">{{ number_format($summary['total']) }}</div>
      <div class="stat-card__label">Lớp đã tham gia</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--primary);">{{ number_format($summary['courses']) }}</div>
      <div class="stat-card__label">Khóa học trong lớp</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--warning);">{{ number_format($summary['pending_items']) }}</div>
      <div class="stat-card__label">Việc cần làm</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--success);">{{ $summary['avg_progress'] }}%</div>
      <div class="stat-card__label">Tiến độ trung bình</div>
    </div>
  </div>

  <div class="student-class-tabs" role="navigation" aria-label="Trạng thái lớp">
    <a class="student-class-tab {{ $filters['status'] === 'all' ? 'active' : '' }}" href="{{ $statusUrl('all') }}">
      Tất cả <span class="badge badge-primary">{{ $summary['total'] }}</span>
    </a>
    <a class="student-class-tab {{ $filters['status'] === 'active' ? 'active' : '' }}" href="{{ $statusUrl('active') }}">
      Đang học <span class="badge badge-success">{{ $summary['active'] }}</span>
    </a>
    <a class="student-class-tab {{ $filters['status'] === 'archived' ? 'active' : '' }}" href="{{ $statusUrl('archived') }}">
      Lưu trữ <span class="badge badge-outline">{{ $summary['archived'] }}</span>
    </a>
  </div>

  <form class="toolbar student-classes-filter stagger-children" method="GET" action="{{ route('student.classes') }}">
    <div class="toolbar-left">
      <div class="search-input-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm lớp, mã lớp, giáo viên, môn học..." />
      </div>
      <select class="input select" name="subject" style="max-width:13rem;">
        <option value="">Tất cả môn học</option>
        @foreach($subjects as $subject)
          <option value="{{ $subject }}" @selected($filters['subject'] === $subject)>{{ $subject }}</option>
        @endforeach
      </select>
      <select class="input select" name="status" style="max-width:13rem;">
        <option value="all" @selected($filters['status'] === 'all')>Tất cả trạng thái</option>
        <option value="active" @selected($filters['status'] === 'active')>Đang hoạt động</option>
        <option value="archived" @selected($filters['status'] === 'archived')>Đã lưu trữ</option>
      </select>
    </div>
    <div class="toolbar-right">
      <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
      @if($hasActiveFilters)
        <a class="btn btn-ghost btn-sm" href="{{ route('student.classes') }}">Xóa lọc</a>
      @endif
      <span class="text-sm text-muted">{{ $classes->total() }} lớp</span>
    </div>
  </form>

  @if($classes->count() === 0)
    <div class="empty-state card" style="margin-top:1.25rem;">
      <div class="empty-state-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      </div>
      <h3>Chưa có lớp phù hợp</h3>
      <p>Nhập mã lớp từ giáo viên hoặc điều chỉnh bộ lọc để xem lớp đã tham gia.</p>
      <a href="{{ route('student.join-class') }}" class="btn btn-primary">Tham gia lớp</a>
    </div>
  @else
    <div class="cards-grid stagger-children" style="margin-top:1.25rem;">
      @foreach($classes as $class)
        @php
          $classColor = $class->display_color ?? '#2563eb';
          $statusLabel = $class->activity_status_label ?? ($class->status === 'archived' ? 'Đã lưu trữ' : 'Đang học');
          $statusClass = $class->activity_status_badge ?? ($class->status === 'archived' ? 'badge-outline' : 'badge-success');
          $progressClass = $class->progress_pct >= 100 ? 'success' : ($class->progress_pct < 35 ? 'warning' : '');
        @endphp
        <article class="card hover-lift student-class-card">
          <a class="class-card-link" href="{{ route('student.classes.show', $class) }}" aria-label="Mở lớp {{ $class->name }}"></a>
          <div class="student-class-cover" style="background:linear-gradient(135deg,{{ $classColor }},color-mix(in srgb,{{ $classColor }} 74%,#111827));">
            <div style="display:flex;align-items:center;gap:.875rem;min-width:0;">
              <div class="student-class-icon">
                <x-display-icon :icon="$class->icon" :label="$class->name" />
              </div>
              <div style="min-width:0;">
                <div class="student-class-title">{{ $class->name }}</div>
                <div class="student-class-meta">{{ $class->teacher?->name ?? 'Chưa có giáo viên' }}@if($class->subject) · {{ $class->subject }}@endif</div>
              </div>
            </div>
            <span class="badge {{ $statusClass }}" style="background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.28);">{{ $statusLabel }}</span>
          </div>

          <div class="card-content" style="flex:1;">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="badge badge-outline">Mã: {{ $class->code }}</span>
              @if($class->grade_level)
                <span class="badge badge-default">Khối {{ $class->grade_level }}</span>
              @endif
              <span class="badge badge-default">{{ $class->students_count }} học sinh</span>
            </div>

            @if($class->has_pending_items)
              <div class="student-class-alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                {{ $class->pending_items_count }} việc cần hoàn thành
              </div>
            @else
              <div class="student-class-alert" style="color:var(--success);">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                Không còn việc đang chờ
              </div>
            @endif

            @if($class->description)
              <p class="text-sm text-muted" style="line-height:1.55;margin:1rem 0 0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $class->description }}</p>
            @endif

            <div class="student-class-stats">
              <div class="student-class-stat">
                <strong>{{ $class->learning_courses_count }}</strong>
                <span>Khóa học</span>
              </div>
              <div class="student-class-stat">
                <strong>{{ $class->learning_quizzes_count }}</strong>
                <span>Kiểm tra</span>
              </div>
              <div class="student-class-stat">
                <strong>{{ $class->learning_assignments_count }}</strong>
                <span>Bài tập</span>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between text-xs text-muted" style="margin-bottom:.35rem;">
                <span>Tiến độ lớp</span>
                <span>{{ $class->completed_items_count }}/{{ $class->total_items_count }} mục · {{ $class->progress_pct }}%</span>
              </div>
              <div class="progress">
                <div class="progress-bar {{ $progressClass }}" style="width:{{ $class->progress_pct }}%;@if(!$progressClass) background:{{ $classColor }}; @endif"></div>
              </div>
            </div>
          </div>

          <div class="card-footer class-card-actions">
            <a href="{{ route('student.classes.show', $class) }}" class="btn btn-primary btn-sm" style="flex:1;">Chi tiết</a>
            <a href="{{ route('student.courses', ['class_id' => $class->id]) }}" class="btn btn-outline btn-sm" style="flex:1;">Khóa học</a>
            <form class="class-card-form" method="POST" action="{{ route('student.classes.leave', $class) }}" data-confirm="Bạn chắc chắn muốn rời lớp {{ $class->name }}? Bạn sẽ bị gỡ khỏi các khóa học thuộc lớp này." data-confirm-ok="Rời lớp" style="margin:0;">
              @csrf
              @method('DELETE')
              <button class="btn btn-ghost btn-sm" type="submit" style="color:var(--destructive);">Rời lớp</button>
            </form>
          </div>
        </article>
      @endforeach
    </div>

    <div style="margin-top:1.5rem;">
      {{ $classes->links('components.pagination') }}
    </div>
  @endif
@endsection
