{{-- Teacher: assignment grading board --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.board-layout{display:grid;grid-template-columns:minmax(19rem,24rem) minmax(0,1fr);gap:1rem;align-items:start}
.board-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1rem}
.meta-list{display:flex;flex-direction:column;gap:.5rem;font-size:var(--text-sm);color:var(--muted-foreground)}
.stats-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:.75rem}
.mini-stat{border:1px solid var(--border);border-radius:var(--radius-md);padding:.75rem;background:var(--background)}
.mini-stat .label{font-size:var(--text-xs);color:var(--muted-foreground)}
.mini-stat .value{font-size:var(--text-lg);font-weight:800}
.filter-form{display:grid;grid-template-columns:minmax(13rem,1.4fr) repeat(3,minmax(8.5rem,1fr)) auto;gap:.6rem;align-items:end}
.filter-field{display:flex;flex-direction:column;gap:.3rem}
.filter-field label{font-size:var(--text-xs);font-weight:700;color:var(--muted-foreground)}
.student-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(15rem,1fr));gap:.75rem}
.student-item{display:block;border:1px solid var(--border);border-radius:var(--radius-md);padding:.75rem;background:var(--background);text-decoration:none;color:inherit}
.student-item:hover{border-color:var(--primary)}
.student-item.active{border-color:var(--primary);box-shadow:0 0 0 1px color-mix(in srgb,var(--primary) 45%,transparent)}
.student-top{display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem}
.student-name{font-weight:700;line-height:1.35}
.student-email{font-size:var(--text-xs);color:var(--muted-foreground)}
.student-time{font-size:var(--text-xs);margin-top:.35rem;color:var(--muted-foreground)}
.assignment-content{white-space:pre-wrap;line-height:1.6;max-height:18rem;overflow:auto;font-size:var(--text-sm)}
@media(max-width:1280px){.board-layout{grid-template-columns:1fr}.stats-row{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:820px){.filter-form{grid-template-columns:1fr}.stats-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
  $allRoster = $allRoster ?? collect($roster ?? []);
  $rosterCollection = collect($roster ?? []);
  $submittedCount = $allRoster->filter(fn ($row) => $row->submission !== null)->count();
  $gradedCount = $allRoster->filter(fn ($row) => $row->grade !== null)->count();
  $pendingCount = max(0, $submittedCount - $gradedCount);
  $notSubmittedCount = max(0, $allRoster->count() - $submittedCount);
  $filters = $filters ?? ['q' => '', 'submission_status' => 'all', 'grading_status' => 'all', 'sort' => 'pending_first'];
@endphp

<div class="page-header">
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1>Chấm bài tập</h1>
      <p style="color:var(--muted-foreground);margin-top:.25rem;">{{ $assignment->title }}</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <a href="{{ route('teacher.assignments.show', $assignment) }}" class="btn btn-outline">Xem chi tiết bài tập</a>
      <a href="{{ route('teacher.assignments') }}" class="btn btn-ghost">Quay lại bài tập</a>
    </div>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
@endif

<div class="board-layout">
  <aside class="board-card">
    <h3 style="margin:0 0 .75rem 0;">Đề bài</h3>
    <div class="meta-list">
      <div>Lớp: <strong style="color:var(--foreground);">{{ $assignment->class?->name ?? 'Chưa gắn lớp' }}</strong></div>
      @if($assignment->course?->name)<div>Khóa học: <strong style="color:var(--foreground);">{{ $assignment->course->name }}</strong></div>@endif
      <div>Điểm tối đa: <strong style="color:var(--foreground);">{{ $assignment->total_points }}</strong></div>
      <div>Hạn nộp: <strong style="color:var(--foreground);">{{ $assignment->due_at ? $assignment->due_at->format('d/m/Y H:i') : 'Không giới hạn' }}</strong></div>
    </div>
    <hr style="border:none;border-top:1px solid var(--border);margin:1rem 0;">
    <div style="font-weight:700;margin-bottom:.5rem;">Nội dung giao bài</div>
    <div class="assignment-content">{{ $assignment->description ?: 'Không có mô tả.' }}</div>
    @if($assignment->attachment)
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;">
        <a class="btn btn-outline btn-sm" target="_blank" href="{{ route('teacher.assignments.attachment.preview', $assignment) }}">Xem tài liệu</a>
        <a class="btn btn-ghost btn-sm" href="{{ route('teacher.assignments.attachment.download', $assignment) }}">Tải tài liệu</a>
      </div>
    @endif
  </aside>

  <section class="board-card">
    <div class="stats-row">
      <div class="mini-stat"><div class="label">Tổng học sinh</div><div class="value">{{ $allRoster->count() }}</div></div>
      <div class="mini-stat"><div class="label">Đã nộp</div><div class="value">{{ $submittedCount }}</div></div>
      <div class="mini-stat"><div class="label">Chờ chấm</div><div class="value">{{ $pendingCount }}</div></div>
      <div class="mini-stat"><div class="label">Chưa nộp</div><div class="value">{{ $notSubmittedCount }}</div></div>
    </div>

    <form method="GET" class="filter-form" action="{{ route('teacher.assignments.grading-board', $assignment) }}" style="margin-bottom:.85rem;">
      <div class="filter-field">
        <label for="filter-q">Tìm học sinh</label>
        <input class="input" id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên hoặc email">
      </div>
      <div class="filter-field">
        <label for="filter-submission">Trạng thái nộp</label>
        <select class="input select" id="filter-submission" name="submission_status">
          <option value="all" @selected(($filters['submission_status'] ?? 'all') === 'all')>Tất cả</option>
          <option value="submitted" @selected(($filters['submission_status'] ?? '') === 'submitted')>Đã nộp</option>
          <option value="not_submitted" @selected(($filters['submission_status'] ?? '') === 'not_submitted')>Chưa nộp</option>
        </select>
      </div>
      <div class="filter-field">
        <label for="filter-grading">Trạng thái chấm</label>
        <select class="input select" id="filter-grading" name="grading_status">
          <option value="all" @selected(($filters['grading_status'] ?? 'all') === 'all')>Tất cả</option>
          <option value="pending" @selected(($filters['grading_status'] ?? '') === 'pending')>Chờ chấm</option>
          <option value="graded" @selected(($filters['grading_status'] ?? '') === 'graded')>Đã chấm</option>
        </select>
      </div>
      <div class="filter-field">
        <label for="filter-sort">Sắp xếp</label>
        <select class="input select" id="filter-sort" name="sort">
          <option value="pending_first" @selected(($filters['sort'] ?? 'pending_first') === 'pending_first')>Ưu tiên chờ chấm</option>
          <option value="submitted_newest" @selected(($filters['sort'] ?? '') === 'submitted_newest')>Nộp mới nhất</option>
          <option value="submitted_oldest" @selected(($filters['sort'] ?? '') === 'submitted_oldest')>Nộp cũ nhất</option>
          <option value="name_asc" @selected(($filters['sort'] ?? '') === 'name_asc')>Tên A → Z</option>
          <option value="name_desc" @selected(($filters['sort'] ?? '') === 'name_desc')>Tên Z → A</option>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Lọc</button>
    </form>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.75rem;">
      <h3 style="margin:0;">Danh sách nộp bài</h3>
      <span class="badge badge-default">Hiển thị {{ $rosterCollection->count() }} / {{ $allRoster->count() }}</span>
    </div>

    <div class="student-list">
      @forelse($roster as $row)
        @php
          $submission = $row->submission;
          $isActive = $selectedStudent && $selectedStudent->id === $row->student->id;
        @endphp
        <a class="student-item {{ $isActive ? 'active' : '' }}" href="{{ $submission ? route('teacher.assignments.grading-submission', ['assignment' => $assignment->id, 'submission' => $submission->id]) : route('teacher.assignments.grading-board', ['assignment' => $assignment->id, 'student_id' => $row->student->id]) }}">
          <div class="student-top">
            <div>
              <div class="student-name">{{ $row->student?->name ?? 'Học sinh' }}</div>
              <div class="student-email">{{ $row->student?->email }}</div>
            </div>
            <span class="badge {{ $submission ? (($row->grade ? 'badge-success' : 'badge-warning')) : 'badge-danger' }}">
              {{ $submission ? ($row->grade ? 'Đã chấm' : 'Chờ chấm') : 'Chưa nộp' }}
            </span>
          </div>
          <div class="student-time">{{ $submission?->submitted_at?->format('d/m/Y H:i') ?? 'Chưa có bài nộp' }}</div>
        </a>
      @empty
        <div class="empty-state" style="grid-column:1 / -1;">
          <h3>Không có dữ liệu phù hợp</h3>
          <p>Hãy đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>
      @endforelse
    </div>
  </section>
</div>
@endsection

