@extends('layouts.admin')

@section('title', 'Admin - Điểm số')
@section('page-title', 'Điểm số')
@section('page-description', 'Tra cứu và điều chỉnh điểm, phản hồi chấm bài trong toàn hệ thống.')

@section('content')
<section class="card">
  <div class="card-header"><form method="GET" class="admin-toolbar"><div class="form-group" style="min-width:280px;flex:1;"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Tên hoặc email học sinh"></div><button class="btn btn-primary">Tìm</button><a class="btn btn-outline" href="{{ route('admin.grades') }}">Đặt lại</a></form></div>
  <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Học sinh</th><th>Nguồn điểm</th><th>Người chấm</th><th>Cập nhật điểm</th></tr></thead><tbody>
    @forelse($grades as $grade)
      <tr>
        <td><a class="admin-row-title" href="{{ route('admin.users.show', $grade->student_id) }}">{{ $grade->student?->name ?? 'Không rõ' }}</a><div class="admin-row-meta">{{ $grade->student?->email }}</div></td>
        <td><div class="admin-row-title">{{ \App\Support\AdminLabels::gradableType($grade->gradable_type) }} #{{ $grade->gradable_id }}</div><div class="admin-row-meta">{{ $grade->gradable?->title ?? $grade->gradable?->name ?? 'Nguồn đã xóa' }}</div></td>
        <td>{{ $grade->grader?->name ?? 'Không rõ' }}<div class="admin-row-meta">{{ $grade->graded_at?->format('d/m/Y H:i') }}</div></td>
        <td><form method="POST" action="{{ route('admin.grades.update', $grade->id) }}" class="admin-form-grid">@csrf @method('PATCH')<input class="input" name="score" type="number" step="0.01" value="{{ $grade->score }}"><textarea class="input" name="feedback" rows="2">{{ $grade->feedback }}</textarea><button class="btn btn-primary btn-sm" style="grid-column:1/-1;">Lưu điểm</button></form></td>
      </tr>
    @empty <tr><td colspan="4" class="empty-state">Không có điểm.</td></tr> @endforelse
  </tbody></table></div>
  <div class="card-footer">{{ $grades->links('components.pagination') }}</div>
</section>
@endsection
