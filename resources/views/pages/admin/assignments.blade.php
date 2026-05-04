@extends('layouts.admin')

@section('title', 'Admin - Bài tập')
@section('page-title', 'Bài tập')
@section('page-description', 'Theo dõi bài tập, hạn nộp, hình thức nộp và số lượt nộp bài.')

@php
  $typeBadges = ['file' => 'badge-info', 'text' => 'badge-outline', 'online' => 'badge-success'];
@endphp

@section('content')
<section class="card">
  <div class="card-header">
    <form method="GET" class="admin-toolbar">
      <div class="form-group" style="min-width:280px;flex:1;">
        <label class="label">Tìm kiếm</label>
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Tên bài tập">
      </div>
      <button class="btn btn-primary">Tìm</button>
      <a class="btn btn-outline" href="{{ route('admin.assignments') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Bài tập</th><th>Phạm vi</th><th>Hạn nộp</th><th>Số liệu</th><th></th></tr></thead>
      <tbody>
      @forelse($assignments as $assignment)
        <tr style="{{ $assignment->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
          <td>
            <div class="admin-row-title">{{ $assignment->title }}</div>
            <div class="admin-row-meta">
              <span>{{ $assignment->teacher?->name ?? 'Không rõ giáo viên' }}</span>
              <span class="badge {{ $typeBadges[$assignment->type] ?? 'badge-outline' }}">{{ \App\Support\AdminLabels::assignmentType($assignment->type) }}</span>
            </div>
          </td>
          <td>
            <div>{{ $assignment->class?->name ?? 'Không gắn lớp' }}</div>
            <div class="admin-row-meta">{{ $assignment->course?->name ?? 'Không gắn khóa học' }}</div>
          </td>
          <td>
            @if($assignment->due_at)
              <span class="badge {{ $assignment->due_at->isPast() ? 'badge-danger' : 'badge-success' }}">{{ $assignment->due_at->format('d/m/Y H:i') }}</span>
            @else
              <span class="badge badge-outline">Không hạn</span>
            @endif
          </td>
          <td style="font-size:var(--text-xs);color:var(--muted-foreground);">
            {{ $assignment->submissions_count }} lượt nộp · {{ $assignment->total_points }} điểm
          </td>
          <td>
            <div class="admin-table-actions">
              <a class="btn btn-outline btn-sm" href="{{ route('admin.assignments.show', $assignment->id) }}">Chi tiết</a>
              @if($assignment->trashed())
                <form method="POST" action="{{ route('admin.assignments.restore', $assignment->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>
              @else
                <form method="POST" action="{{ route('admin.assignments.delete', $assignment->id) }}" onsubmit="return confirm('Xóa bài tập này?')">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Xóa</button></form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có bài tập phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $assignments->links('components.pagination') }}</div>
</section>
@endsection
