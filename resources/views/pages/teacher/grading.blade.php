{{-- Teacher: grading --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.grading-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:1rem}
.grade-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);overflow:hidden}
.grade-card__head{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:1rem 1.25rem;border-bottom:1px solid var(--border);background:var(--muted)}
.grade-card__body{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(20rem,.7fr);gap:1rem;padding:1.25rem}
.submission-panel{border:1px solid var(--border);border-radius:var(--radius-md);padding:1rem;background:var(--background)}
.submission-text{white-space:pre-wrap;line-height:1.65;font-size:var(--text-sm);max-height:18rem;overflow:auto}
.grade-form{border:1px solid var(--border);border-radius:var(--radius-md);padding:1rem;background:var(--card);display:flex;flex-direction:column;gap:.875rem}
.filter-card .card-content{display:flex;flex-direction:column;gap:1rem}
.filter-row{display:grid;grid-template-columns:minmax(16rem,1fr) repeat(2,minmax(9rem,12rem)) auto auto;gap:.75rem;align-items:end}
.filter-field{display:flex;flex-direction:column;gap:.375rem}
.filter-field label{font-size:var(--text-xs);font-weight:700;color:var(--muted-foreground)}
.status-tabs{display:flex;gap:.5rem;flex-wrap:wrap}
.status-tab{display:inline-flex;align-items:center;gap:.375rem;padding:.5rem .75rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);color:var(--foreground);font-size:var(--text-sm);text-decoration:none}
.status-tab.active{border-color:var(--primary);background:color-mix(in srgb,var(--primary) 10%,transparent);color:var(--primary);font-weight:700}
.student-chipline{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap}
.avatar-sm{width:2.5rem;height:2.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--primary) 14%,transparent);color:var(--primary);font-weight:800;font-size:var(--text-sm);flex-shrink:0}
@media(max-width:980px){.grade-card__body,.filter-row{grid-template-columns:1fr}.filter-row .btn{width:100%;justify-content:center}}
</style>
@endpush

@section('content')
@php
  $filters = $filters ?? ['status' => 'pending', 'type' => 'all', 'q' => ''];
  $summary = $summary ?? ['pending' => 0, 'graded' => 0, 'quiz' => 0, 'assignment' => 0, 'graded_today' => 0, 'avg_pct' => null];
  $statusLabels = ['pending' => 'Chờ chấm', 'graded' => 'Đã chấm', 'all' => 'Tất cả'];
  $typeLabels = ['quiz' => 'Bài kiểm tra', 'assignment' => 'Bài tập'];
  $hasFilters = ($filters['status'] ?? 'pending') !== 'pending' || ($filters['type'] ?? 'all') !== 'all' || ($filters['q'] ?? '') !== '';
  $initials = fn($name) => collect(explode(' ', trim($name)))->filter()->take(-2)->map(fn($part) => mb_substr($part, 0, 1))->implode('');
@endphp

<div class="page-header">
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1>Chấm điểm</h1>
      <p style="color:var(--muted-foreground);margin-top:.25rem;">Xem bài nộp, nhập điểm và phản hồi cho học sinh.</p>
    </div>
    <a href="{{ route('teacher.grading.export') }}" class="btn btn-outline gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Xuất Excel
    </a>
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

<div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
  <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Chờ chấm</div><div class="stat-card__value" style="color:var(--warning);">{{ $summary['pending'] }}</div></div>
  <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đã chấm hôm nay</div><div class="stat-card__value" style="color:var(--success);">{{ $summary['graded_today'] }}</div></div>
  <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB</div><div class="stat-card__value">{{ $summary['avg_pct'] !== null ? $summary['avg_pct'] . '%' : '—' }}</div></div>
  <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng bài nộp</div><div class="stat-card__value">{{ ($summary['pending'] ?? 0) + ($summary['graded'] ?? 0) }}</div></div>
</div>

<div class="card filter-card" style="margin-bottom:1rem;">
  <div class="card-content">
    <div class="status-tabs">
      @foreach(['pending' => 'Chờ chấm', 'graded' => 'Đã chấm', 'all' => 'Tất cả'] as $status => $label)
        @php
          $count = $status === 'all' ? (($summary['pending'] ?? 0) + ($summary['graded'] ?? 0)) : ($summary[$status] ?? 0);
          $query = array_filter(array_merge($filters, ['status' => $status]), fn($value) => $value !== null && $value !== '');
        @endphp
        <a href="{{ route('teacher.grading', $query) }}" class="status-tab {{ ($filters['status'] ?? 'pending') === $status ? 'active' : '' }}">
          {{ $label }} <span class="badge badge-default">{{ $count }}</span>
        </a>
      @endforeach
    </div>

    <form method="GET" action="{{ route('teacher.grading') }}" class="filter-row">
      <input type="hidden" name="status" value="{{ $filters['status'] ?? 'pending' }}">
      <div class="filter-field">
        <label for="grading-search">Tìm kiếm</label>
        <input id="grading-search" class="input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên học sinh, bài, lớp, email...">
      </div>
      <div class="filter-field">
        <label for="grading-type">Loại</label>
        <select id="grading-type" class="input select" name="type">
          <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>Tất cả</option>
          <option value="quiz" @selected(($filters['type'] ?? '') === 'quiz')>Bài kiểm tra</option>
          <option value="assignment" @selected(($filters['type'] ?? '') === 'assignment')>Bài tập</option>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Lọc</button>
      @if($hasFilters)
        <a class="btn btn-outline" href="{{ route('teacher.grading') }}">Xóa lọc</a>
      @endif
    </form>
  </div>
</div>

<div class="grading-grid">
  @forelse($items as $item)
    @php
      $isAssignment = $item->type === 'assignment';
      $scoreClass = $item->percentage === null ? 'badge-warning' : ($item->percentage >= 80 ? 'badge-success' : ($item->percentage >= 50 ? 'badge-warning' : 'badge-danger'));
    @endphp
    <article class="grade-card">
      <div class="grade-card__head">
        <div class="student-chipline">
          <div class="avatar-sm">{{ $initials($item->student_name) }}</div>
          <div>
            <div style="font-weight:800;font-size:var(--text-base);">{{ $item->student_name }}</div>
            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $item->student_email ?? 'Không có email' }}</div>
          </div>
          <span class="badge {{ $isAssignment ? 'badge-info' : 'badge-default' }}">{{ $typeLabels[$item->type] }}</span>
          <span class="badge {{ $item->is_graded ? 'badge-success' : 'badge-warning' }}">{{ $item->is_graded ? 'Đã chấm' : 'Chờ chấm' }}</span>
        </div>
        <div style="text-align:right;">
          <div style="font-weight:800;">{{ $item->item_title }}</div>
          <div style="font-size:var(--text-xs);color:var(--muted-foreground);">
            {{ $item->class_name ?? 'Không gắn lớp' }}
            @if($item->course_name) · {{ $item->course_name }} @endif
            · Nộp {{ $item->submitted_at ? $item->submitted_at->format('d/m/Y H:i') : '—' }}
          </div>
        </div>
      </div>

      <div class="grade-card__body">
        <div class="submission-panel">
          <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:.75rem;">
            <h3 style="font-size:var(--text-base);font-weight:800;margin:0;">Bài nộp</h3>
            @if($item->score !== null)
              <span class="badge {{ $scoreClass }}">{{ $item->score }}/{{ $item->max_score }} điểm{{ $item->percentage !== null ? ' · ' . $item->percentage . '%' : '' }}</span>
            @endif
          </div>

          @if($item->content)
            <div class="submission-text">{{ $item->content }}</div>
          @else
            <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin:0;">Không có nội dung văn bản.</p>
          @endif

          @if($isAssignment && $item->attachment)
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;">
              <a class="btn btn-outline btn-sm" target="_blank" href="{{ route('teacher.grading.submissions.attachment.inline', $item->gradable_id) }}">Xem file nộp</a>
              <a class="btn btn-ghost btn-sm" href="{{ route('teacher.grading.submissions.attachment.download', $item->gradable_id) }}">Tải file</a>
            </div>
          @endif
        </div>

        <form class="grade-form" method="POST" action="{{ route('teacher.grading.store') }}">
          @csrf
          <input type="hidden" name="gradable_type" value="{{ $item->gradable_type }}">
          <input type="hidden" name="gradable_id" value="{{ $item->gradable_id }}">
          <input type="hidden" name="student_id" value="{{ $item->student_id }}">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
            <div class="form-group">
              <label class="label label-required">Điểm</label>
              <input class="input" type="number" name="score" min="0" max="{{ $item->max_score }}" step="1" value="{{ old('score', $item->score) }}" required style="font-weight:800;text-align:center;">
            </div>
            <div class="form-group">
              <label class="label">Điểm tối đa</label>
              <input class="input" value="{{ $item->max_score }}" readonly style="background:var(--muted);font-weight:800;text-align:center;">
            </div>
          </div>
          <div class="form-group">
            <label class="label">Nhận xét</label>
            <textarea class="input" name="feedback" rows="5" maxlength="3000" placeholder="Góp ý, lỗi cần sửa, điểm mạnh...">{{ old('feedback', $item->feedback) }}</textarea>
          </div>
          @if($item->graded_at)
            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Chấm lần cuối: {{ $item->graded_at->format('d/m/Y H:i') }}</div>
          @endif
          <button class="btn btn-primary" type="submit">{{ $item->is_graded ? 'Cập nhật điểm' : 'Lưu điểm' }}</button>
        </form>
      </div>
    </article>
  @empty
    <div class="empty-state">
      <div style="font-size:3rem;">{{ ($filters['status'] ?? 'pending') === 'pending' ? '📋' : '✅' }}</div>
      <h3>{{ $hasFilters ? 'Không tìm thấy bài nộp' : 'Không có bài cần chấm' }}</h3>
      <p>{{ $hasFilters ? 'Thử thay đổi bộ lọc hoặc từ khóa.' : 'Khi học sinh nộp bài, danh sách sẽ hiển thị ở đây.' }}</p>
    </div>
  @endforelse
</div>

{{ $items->links('components.pagination') }}

<div id="toast-container"></div>
@endsection
