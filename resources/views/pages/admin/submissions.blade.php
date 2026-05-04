@extends('layouts.admin')

@section('title', 'Admin - Bài nộp')
@section('page-title', 'Bài nộp')
@section('page-description', 'Tra cứu bài nộp theo học sinh, bài tập, lớp và khóa học.')

@section('content')
<section class="card">
  <div class="card-header"><form method="GET" class="admin-toolbar"><div class="form-group" style="min-width:280px;flex:1;"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Tên học sinh, email, bài tập"></div><button class="btn btn-primary">Tìm</button><a class="btn btn-outline" href="{{ route('admin.submissions') }}">Đặt lại</a></form></div>
  <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Học sinh</th><th>Bài tập</th><th>Phạm vi</th><th>Nộp lúc</th><th>Điểm</th></tr></thead><tbody>
    @forelse($submissions as $submission)
      <tr><td><a class="admin-row-title" href="{{ route('admin.users.show', $submission->student_id) }}">{{ $submission->student?->name ?? 'Không rõ' }}</a><div class="admin-row-meta">{{ $submission->student?->email }}</div></td><td><a class="admin-row-title" href="{{ route('admin.assignments.show', $submission->assignment_id) }}">{{ $submission->assignment?->title ?? 'Bài tập đã xóa' }}</a><div class="admin-row-meta">{{ \Illuminate\Support\Str::limit($submission->content ?: $submission->attachment, 90) }}</div></td><td>{{ $submission->assignment?->class?->name ?? 'Không lớp' }}<div class="admin-row-meta">{{ $submission->assignment?->course?->name ?? 'Không khóa' }}</div></td><td>{{ $submission->submitted_at?->format('d/m/Y H:i') }}</td><td>@forelse($submission->grades as $grade)<span class="badge badge-success">{{ $grade->score }}</span>@empty<span class="badge badge-warning">Chưa chấm</span>@endforelse</td></tr>
    @empty <tr><td colspan="5" class="empty-state">Không có bài nộp.</td></tr> @endforelse
  </tbody></table></div>
  <div class="card-footer">{{ $submissions->links('components.pagination') }}</div>
</section>
@endsection
