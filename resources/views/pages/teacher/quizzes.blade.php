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
</style>
@endpush

@section('content')
  <!-- Page Header -->
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Bài kiểm tra &amp; Kỳ thi</h1>
        <p style="color:var(--muted-foreground);margin-top:0.25rem;">Tạo, quản lý và phân tích bài kiểm tra và kỳ thi của bạn</p>
      </div>
      <a href="{{ route('teacher.quiz-create') }}" class="btn btn-primary gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tạo Kỳ thi Mới
      </a>
    </div>
  </div>

  <!-- Flash -->
  @if(session('success'))
  <div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
  @endif

  <!-- Stats -->
  @php
    $publishedCount = $quizzes->where('status', 'published')->count();
    $draftCount     = $quizzes->where('status', 'draft')->count();
    $archivedCount  = $quizzes->where('status', 'archived')->count();
  @endphp
  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Tổng số Kỳ thi</div>
      <div class="stat-card__value">{{ $quizzes->count() }}</div>
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

  <!-- Quizzes Table -->
  <div class="card stagger-children">
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead>
          <tr>
            <th>Tên Bài thi</th>
            <th>Câu hỏi</th>
            <th>Lượt thi</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($quizzes as $quiz)
          <tr>
            <td>
              <div class="quiz-title-cell">
                <div class="quiz-icon" style="background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                  <div style="font-weight:600;">{{ $quiz->title }}</div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">
                    {{ $quiz->course ? $quiz->course->title : '—' }}
                    @if($quiz->duration_minutes) • {{ $quiz->duration_minutes }} phút @endif
                  </div>
                </div>
              </div>
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
              <div style="display:flex;gap:.375rem;">
                <a href="{{ route('teacher.quiz-detail', $quiz) }}" class="btn btn-ghost btn-sm" title="Xem chi tiết">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
                <form method="POST" action="{{ route('teacher.quizzes.destroy', $quiz) }}" onsubmit="return confirm('Xóa bài thi {{ $quiz->title }}?')">
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
            <td colspan="6" style="text-align:center;padding:3rem;color:var(--muted-foreground);">
              <div style="font-size:2.5rem;margin-bottom:.5rem;">📝</div>
              <p style="font-weight:500;">Chưa có bài kiểm tra nào</p>
              <p style="font-size:var(--text-sm);">Tạo bài thi đầu tiên để bắt đầu!</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
