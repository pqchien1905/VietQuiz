@extends('layouts.admin')

@section('title', 'Admin - Chi tiết bài tập')
@section('page-title', $assignment->title)
@section('page-description', 'Theo dõi phạm vi giao bài, bài nộp, điểm và học sinh chưa nộp.')

@section('actions')
  <a class="btn btn-outline btn-sm" href="{{ route('admin.assignments') }}">Quay lại</a>
@endsection

@section('content')
<section class="stats-grid stats-grid-4">
  @foreach(['Bài nộp' => $assignment->submissions_count, 'Chưa nộp' => $missingStudents->count(), 'Tổng điểm' => $assignment->total_points ?? 0, 'Ngày còn lại' => $assignment->due_at ? max(0, now()->diffInDays($assignment->due_at, false)) : 0] as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ $label }}</div><div class="stat-card__value">{{ number_format($value) }}</div></div>
  @endforeach
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Thông tin bài tập</h3></div>
  <div class="card-content">
    <div class="admin-grid-3">
      <div><div class="label">Giáo viên</div><div class="admin-row-title">{{ $assignment->teacher?->name ?? 'Không rõ' }}</div></div>
      <div><div class="label">Lớp</div><div class="admin-row-title">{{ $assignment->class?->name ?? 'Không gắn lớp' }}</div></div>
      <div><div class="label">Khóa học</div><div class="admin-row-title">{{ $assignment->course?->name ?? 'Không gắn khóa' }}</div></div>
    </div>
    <p style="margin-top:1rem;color:var(--muted-foreground);">{{ $assignment->description ?: 'Không có mô tả.' }}</p>
  </div>
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Bài nộp</h3></div>
  <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Học sinh</th><th>Nộp lúc</th><th>Nội dung</th><th>Điểm</th></tr></thead><tbody>
    @forelse($assignment->submissions as $submission)
      <tr><td><a class="admin-row-title" href="{{ route('admin.users.show', $submission->student_id) }}">{{ $submission->student?->name ?? 'Không rõ' }}</a><div class="admin-row-meta">{{ $submission->student?->email }}</div></td><td>{{ $submission->submitted_at?->format('d/m/Y H:i') }}</td><td>{{ \Illuminate\Support\Str::limit($submission->content ?: $submission->attachment, 120) }}</td><td>@forelse($submission->grades as $grade)<span class="badge badge-success">{{ $grade->score }}</span>@empty<span class="badge badge-warning">Chưa chấm</span>@endforelse</td></tr>
    @empty <tr><td colspan="4" class="empty-state">Chưa có bài nộp.</td></tr> @endforelse
  </tbody></table></div>
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Học sinh chưa nộp</h3></div>
  <div class="card-content">
    @forelse($missingStudents as $student)
      <div class="activity-item"><span class="badge badge-warning">Chưa nộp</span><div><a class="admin-row-title" href="{{ route('admin.users.show', $student->id) }}">{{ $student->name }}</a><div class="admin-row-meta">{{ $student->email }}</div></div></div>
    @empty <div class="empty-state">Không còn học sinh thiếu bài.</div> @endforelse
  </div>
</section>
@endsection
