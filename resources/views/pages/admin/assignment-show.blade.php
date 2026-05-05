@extends('layouts.admin')

@section('title', 'Admin - Chi tiết bài tập')
@section('page-title', $assignment->title)
@section('page-description', 'Theo dõi cấu hình, phạm vi giao bài, bài nộp, chấm điểm và học sinh chưa nộp.')

@php
  $typeOptions = ['file' => 'Tệp đính kèm', 'text' => 'Văn bản', 'online' => 'Làm trực tuyến'];
  $typeBadges = ['file' => 'badge-info', 'text' => 'badge-outline', 'online' => 'badge-success'];
  $isOverdue = $assignment->due_at && $assignment->due_at->isPast();
  $ungradedCount = max(0, $assignment->submissions_count - $gradedSubmissions);
@endphp

@push('styles')
<style>
  .assignment-detail-hero { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:center; padding:1.25rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:linear-gradient(135deg, color-mix(in srgb,var(--info) 8%,var(--card)), var(--card)); box-shadow:var(--shadow-sm); }
  .assignment-detail-title h2 { margin:0; font-size:var(--text-xl); font-weight:900; }
  .assignment-detail-title p { margin:.25rem 0 0; color:var(--muted-foreground); }
  .assignment-detail-badges { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
  .assignment-config-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .assignment-config-grid .full { grid-column:1/-1; }
  .assignment-ops-list { display:grid; gap:.75rem; }
  .assignment-ops-item { display:flex; align-items:flex-start; gap:.65rem; padding:.75rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .assignment-ops-item strong { display:block; }
  .assignment-ops-item span { display:block; margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-sm); }
  @media (max-width:900px) { .assignment-detail-hero { grid-template-columns:1fr; } .assignment-detail-badges { justify-content:flex-start; } .assignment-config-grid { grid-template-columns:1fr; } .assignment-config-grid .full { grid-column:auto; } }
</style>
@endpush

@section('actions')
  <a class="btn btn-outline btn-sm" href="{{ route('admin.assignments') }}">Quay lại</a>
@endsection

@section('content')
<section class="assignment-detail-hero">
  <div class="assignment-detail-title">
    <h2>{{ $assignment->title }}</h2>
    <p>{{ $assignment->teacher?->name ?? 'Không rõ giáo viên' }} · {{ $assignment->class?->name ?? 'Không gắn lớp' }} · {{ $assignment->course?->name ?? 'Không gắn khóa học' }}</p>
  </div>
  <div class="assignment-detail-badges">
    <span class="badge {{ $typeBadges[$assignment->type] ?? 'badge-outline' }}">{{ $typeOptions[$assignment->type] ?? \App\Support\AdminLabels::assignmentType($assignment->type) }}</span>
    @if($assignment->trashed())<span class="badge badge-danger">Đã xóa</span>@endif
    @if($isOverdue)<span class="badge badge-danger">Quá hạn</span>@else<span class="badge badge-success">Đang mở</span>@endif
    @if(! $assignment->class_id && ! $assignment->course_id)<span class="badge badge-warning">Chưa gán phạm vi</span>@endif
    @if($ungradedCount > 0)<span class="badge badge-warning">Chờ chấm</span>@endif
  </div>
</section>

<section class="stats-grid stats-grid-4">
  @foreach(['Bài nộp' => $assignment->submissions_count, 'Chưa nộp' => $missingStudents->count(), 'Đã chấm' => $gradedSubmissions, 'Mục tiêu' => $targetCount] as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ $label }}</div><div class="stat-card__value">{{ number_format($value) }}</div></div>
  @endforeach
</section>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Cấu hình bài tập</h3></div>
    <div class="card-content">
      <form method="POST" action="{{ route('admin.assignments.update', $assignment->id) }}" class="assignment-config-grid">
        @csrf
        @method('PATCH')
        <div class="form-group full"><label class="label">Tiêu đề</label><input class="input" name="title" value="{{ old('title', $assignment->title) }}" required maxlength="255"></div>
        <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id" required>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $assignment->teacher_id) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Loại nộp</label><select class="input select" name="type">@foreach($typeOptions as $value => $label)<option value="{{ $value }}" @selected(old('type', $assignment->type) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Lớp</label><select class="input select" name="class_id"><option value="">Không gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id', $assignment->class_id) == $class->id)>{{ $class->name }} - {{ $class->code }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Không gắn khóa</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id', $assignment->course_id) == $course->id)>{{ $course->name }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Hạn nộp</label><input class="input" name="due_at" type="datetime-local" value="{{ old('due_at', $assignment->due_at?->format('Y-m-d\TH:i')) }}"></div>
        <div class="form-group"><label class="label">Tổng điểm</label><input class="input" name="total_points" type="number" min="1" max="10000" value="{{ old('total_points', $assignment->total_points ?? 100) }}"></div>
        <div class="form-group full"><label class="label">Tệp đính kèm/đường dẫn</label><input class="input" name="attachment" value="{{ old('attachment', $assignment->attachment) }}" maxlength="255"></div>
        <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="4" maxlength="2000">{{ old('description', $assignment->description) }}</textarea></div>
        <button class="btn btn-primary full">Lưu bài tập</button>
      </form>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3 class="card-title">Vận hành</h3></div>
    <div class="card-content assignment-ops-list">
      <div class="assignment-ops-item"><span class="badge {{ ($assignment->class_id || $assignment->course_id) ? 'badge-success' : 'badge-warning' }}">{{ ($assignment->class_id || $assignment->course_id) ? 'OK' : 'Thiếu' }}</span><div><strong>Phạm vi giao bài</strong><span>{{ $assignment->class?->name ?? $assignment->course?->name ?? 'Chưa gắn lớp hoặc khóa học.' }}</span></div></div>
      <div class="assignment-ops-item"><span class="badge {{ $isOverdue ? 'badge-danger' : 'badge-success' }}">{{ $isOverdue ? 'Quá hạn' : 'Đang mở' }}</span><div><strong>Hạn nộp</strong><span>{{ $assignment->due_at?->format('d/m/Y H:i') ?? 'Không giới hạn thời gian nộp.' }}</span></div></div>
      <div class="assignment-ops-item"><span class="badge {{ $ungradedCount > 0 ? 'badge-warning' : 'badge-success' }}">{{ $ungradedCount > 0 ? 'Chờ' : 'OK' }}</span><div><strong>Chấm điểm</strong><span>{{ number_format($ungradedCount) }} bài nộp chưa có điểm.</span></div></div>
      <div class="assignment-ops-item"><span class="badge {{ $missingStudents->count() > 0 ? 'badge-warning' : 'badge-success' }}">{{ $missingStudents->count() > 0 ? 'Thiếu' : 'OK' }}</span><div><strong>Nộp bài</strong><span>{{ number_format($missingStudents->count()) }} học sinh trong phạm vi chưa nộp.</span></div></div>
      <a class="btn btn-outline" href="{{ route('admin.submissions', ['q' => $assignment->title]) }}">Xem toàn bộ bài nộp liên quan</a>
    </div>
  </section>
</div>

<section class="card">
  <div class="card-header"><h3 class="card-title">Bài nộp</h3></div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Học sinh</th><th>Nộp lúc</th><th>Nội dung</th><th>Điểm</th></tr></thead>
      <tbody>
        @forelse($assignment->submissions as $submission)
          <tr>
            <td><a class="admin-row-title" href="{{ route('admin.users.show', $submission->student_id) }}">{{ $submission->student?->name ?? 'Không rõ' }}</a><div class="admin-row-meta">{{ $submission->student?->email }}</div></td>
            <td>{{ $submission->submitted_at?->format('d/m/Y H:i') }}</td>
            <td>{{ \Illuminate\Support\Str::limit($submission->content ?: $submission->attachment, 140) }}</td>
            <td>@forelse($submission->grades as $grade)<span class="badge badge-success">{{ $grade->score }}</span><div class="admin-row-meta">{{ $grade->grader?->name }}</div>@empty<span class="badge badge-warning">Chưa chấm</span>@endforelse</td>
          </tr>
        @empty
          <tr><td colspan="4" class="empty-state">Chưa có bài nộp.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Học sinh chưa nộp</h3></div>
  <div class="card-content">
    @forelse($missingStudents as $student)
      <div class="activity-item"><span class="badge badge-warning">Chưa nộp</span><div><a class="admin-row-title" href="{{ route('admin.users.show', $student->id) }}">{{ $student->name }}</a><div class="admin-row-meta">{{ $student->email }}</div></div></div>
    @empty
      <div class="empty-state">Không còn học sinh thiếu bài.</div>
    @endforelse
  </div>
</section>
@endsection
