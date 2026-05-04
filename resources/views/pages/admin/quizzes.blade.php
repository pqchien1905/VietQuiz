@extends('layouts.admin')

@section('title', 'Admin - Bài kiểm tra')
@section('page-title', 'Bài kiểm tra')
@section('page-description', 'Kiểm soát trạng thái bài kiểm tra, ngân hàng câu hỏi, lớp học và khóa học liên quan.')

@php
  $statusBadges = ['draft' => 'badge-warning', 'published' => 'badge-success', 'closed' => 'badge-danger'];
@endphp

@section('content')
<section class="card">
  <div class="card-header">
    <form method="GET" class="admin-toolbar">
      <div class="form-group" style="min-width:280px;flex:1;">
        <label class="label">Tìm kiếm</label>
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Tên bài kiểm tra">
      </div>
      <div class="form-group">
        <label class="label">Trạng thái</label>
        <select class="input select" name="status">
          <option value="">Tất cả</option>
          @foreach(['draft','published','closed'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.quizzes') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead>
        <tr><th>Bài kiểm tra</th><th>Phạm vi</th><th>Cấu hình</th><th>Trạng thái</th><th></th></tr>
      </thead>
      <tbody>
      @forelse($quizzes as $quiz)
        <tr style="{{ $quiz->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
          <td>
            <div class="admin-row-title">{{ $quiz->title }}</div>
            <div class="admin-row-meta">
              <span>Mã #{{ $quiz->id }}</span>
              <span>{{ $quiz->teacher?->name ?? 'Không rõ giáo viên' }}</span>
            </div>
          </td>
          <td>
            <div>{{ $quiz->course?->name ?? 'Không gắn khóa học' }}</div>
            <div class="admin-row-meta">{{ $quiz->classModel?->name ?? 'Không gắn lớp' }}</div>
          </td>
          <td style="font-size:var(--text-xs);color:var(--muted-foreground);">
            {{ $quiz->questions_count }} câu · {{ $quiz->duration_minutes ?? $quiz->time_limit ?? 0 }} phút · {{ $quiz->total_points }} điểm
          </td>
          <td>
            <span class="badge {{ $statusBadges[$quiz->status] ?? 'badge-outline' }}">{{ \App\Support\AdminLabels::status($quiz->status) }}</span>
            <form method="POST" action="{{ route('admin.quizzes.update', $quiz->id) }}" class="admin-inline-form" style="margin-top:.5rem;">
              @csrf @method('PATCH')
              <select name="status" class="input select" style="width:auto;">
                @foreach(['draft','published','closed'] as $status)
                  <option value="{{ $status }}" @selected($quiz->status === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>
                @endforeach
              </select>
              <button class="btn btn-primary btn-sm">Lưu</button>
            </form>
          </td>
          <td>
            <div class="admin-table-actions">
              <a class="btn btn-outline btn-sm" href="{{ route('admin.quizzes.show', $quiz->id) }}">Chi tiết</a>
              @if($quiz->trashed())
                <form method="POST" action="{{ route('admin.quizzes.restore', $quiz->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>
              @else
                <form method="POST" action="{{ route('admin.quizzes.delete', $quiz->id) }}" onsubmit="return confirm('Xóa bài kiểm tra này?')">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Xóa</button></form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có bài kiểm tra phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $quizzes->links('components.pagination') }}</div>
</section>
@endsection
