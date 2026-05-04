@extends('layouts.admin')

@section('title', 'Admin - Chi tiết khóa học')
@section('page-title', $course->name)
@section('page-description', 'Quản lý cấu hình, ghi danh, lớp nguồn, quiz và bài tập của khóa học.')

@php
  $statusOptions = ['draft', 'published'];
  $classStudentsCount = $course->classModel?->students?->count() ?? 0;
  $hasContent = $course->quizzes_count + $course->assignments_count > 0;
@endphp

@push('styles')
<style>
  .course-detail-hero { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:center; padding:1.25rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:linear-gradient(135deg, color-mix(in srgb,var(--primary) 8%,var(--card)), var(--card)); box-shadow:var(--shadow-sm); }
  .course-detail-title { display:flex; align-items:center; gap:.85rem; min-width:0; }
  .course-detail-mark { width:3.25rem; height:3.25rem; border-radius:.85rem; display:grid; place-items:center; color:#fff; font-weight:900; font-size:var(--text-lg); flex:0 0 auto; }
  .course-detail-title h2 { margin:0; font-size:var(--text-xl); font-weight:900; }
  .course-detail-title p { margin:.25rem 0 0; color:var(--muted-foreground); }
  .course-detail-badges { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
  .course-config-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .course-config-grid .full { grid-column:1/-1; }
  .course-enroll-actions { display:flex; gap:.75rem; flex-wrap:wrap; align-items:flex-end; }
  .course-enroll-actions .form-group { flex:1 1 18rem; }
  .admin-courses-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .admin-courses-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .admin-courses-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .admin-courses-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .course-alert { padding:.75rem; border:1px solid color-mix(in srgb,var(--warning) 35%,var(--border)); border-radius:var(--radius-md); background:color-mix(in srgb,var(--warning) 10%,var(--card)); color:var(--muted-foreground); font-size:var(--text-sm); }
  .course-checklist { display:grid; gap:.75rem; }
  .course-check { display:flex; align-items:flex-start; gap:.65rem; padding:.75rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .course-check strong { display:block; }
  .course-check span { display:block; margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-sm); }
  .course-content-list { display:grid; gap:.75rem; }
  @media (max-width:900px) { .course-detail-hero { grid-template-columns:1fr; } .course-detail-badges { justify-content:flex-start; } .course-config-grid { grid-template-columns:1fr; } .course-config-grid .full { grid-column:auto; } }
</style>
@endpush

@section('actions')
  <a class="btn btn-outline btn-sm" href="{{ route('admin.courses') }}">Quay lại</a>
@endsection

@section('content')
<section class="course-detail-hero">
  <div class="course-detail-title">
    <div class="course-detail-mark" style="background:{{ $course->color ?: 'var(--primary)' }}">{{ $course->icon ?: mb_strtoupper(mb_substr($course->name, 0, 1)) }}</div>
    <div style="min-width:0;">
      <h2>{{ $course->name }}</h2>
      <p>{{ $course->teacher?->name ?? 'Không rõ giáo viên' }} · {{ $course->classModel?->name ?? 'Không gắn lớp' }}</p>
    </div>
  </div>
  <div class="course-detail-badges">
    <span class="badge {{ $course->status === 'published' ? 'badge-success' : 'badge-warning' }}">{{ \App\Support\AdminLabels::status($course->status ?? 'draft') }}</span>
    @if($course->trashed())<span class="badge badge-danger">Đã xóa</span>@endif
    @if(! $course->class_id)<span class="badge badge-warning">Chưa gắn lớp</span>@endif
    @if($course->students_count === 0)<span class="badge badge-outline">Chưa có học sinh</span>@endif
    @if(! $hasContent)<span class="badge badge-outline">Chưa có nội dung</span>@endif
  </div>
</section>

<section class="stats-grid stats-grid-4">
  @foreach(['Học sinh' => $course->students_count, 'Bài kiểm tra' => $course->quizzes_count, 'Bài tập' => $course->assignments_count, 'Học sinh lớp' => $classStudentsCount] as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ $label }}</div><div class="stat-card__value">{{ number_format($value) }}</div></div>
  @endforeach
</section>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Cấu hình khóa học</h3></div>
    <div class="card-content">
      <form method="POST" action="{{ route('admin.courses.update', $course->id) }}" class="course-config-grid">
        @csrf
        @method('PATCH')
        <div class="form-group"><label class="label">Tên khóa học</label><input class="input" name="name" value="{{ old('name', $course->name) }}" required maxlength="255"></div>
        <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id" required>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $course->teacher_id) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Lớp liên kết</label><select class="input select" name="class_id"><option value="">Không gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id', $course->class_id) == $class->id)>{{ $class->name }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statusOptions as $status)<option value="{{ $status }}" @selected(old('status', $course->status ?? 'draft') === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Màu</label><input class="input" name="color" value="{{ old('color', $course->color) }}" maxlength="20"></div>
        <div class="form-group"><label class="label">Ký hiệu</label><input class="input" name="icon" value="{{ old('icon', $course->icon) }}" maxlength="10"></div>
        <div class="form-group full"><label class="label">Ảnh bìa</label><input class="input" name="cover_image" value="{{ old('cover_image', $course->cover_image) }}" maxlength="255"></div>
        <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="4" maxlength="2000">{{ old('description', $course->description) }}</textarea></div>
        <button class="btn btn-primary full">Lưu khóa học</button>
      </form>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3 class="card-title">Vận hành</h3></div>
    <div class="card-content course-checklist">
      <div class="course-check">
        <span class="badge {{ $course->class_id ? 'badge-success' : 'badge-warning' }}">{{ $course->class_id ? 'OK' : 'Thiếu' }}</span>
        <div><strong>Lớp nguồn</strong><span>{{ $course->classModel?->name ?? 'Chưa gắn lớp nên không thể đồng bộ học sinh tự động.' }}</span></div>
      </div>
      <div class="course-check">
        <span class="badge {{ $course->students_count > 0 ? 'badge-success' : 'badge-warning' }}">{{ $course->students_count > 0 ? 'OK' : 'Thiếu' }}</span>
        <div><strong>Ghi danh</strong><span>{{ number_format($course->students_count) }} học sinh đang tham gia khóa học.</span></div>
      </div>
      <div class="course-check">
        <span class="badge {{ $hasContent ? 'badge-success' : 'badge-warning' }}">{{ $hasContent ? 'OK' : 'Thiếu' }}</span>
        <div><strong>Nội dung học tập</strong><span>{{ number_format($course->quizzes_count) }} quiz và {{ number_format($course->assignments_count) }} bài tập.</span></div>
      </div>
      @if(! $course->classModel)
        <div class="course-alert">Gắn lớp liên kết nếu muốn đồng bộ danh sách học sinh từ lớp vào khóa học.</div>
      @endif
    </div>
  </section>
</div>

<section class="card">
  <div class="card-header admin-courses-header">
    <div class="admin-courses-title">
      <h3>Ghi danh học sinh</h3>
      <p>Thêm từng học sinh hoặc đồng bộ toàn bộ học sinh từ lớp liên kết.</p>
    </div>
  </div>
  <div class="card-content" style="border-top:1px solid var(--border);">
    <div class="course-enroll-actions">
      <form method="POST" action="{{ route('admin.courses.students.add', $course->id) }}" class="course-enroll-actions" style="flex:1 1 auto;">
        @csrf
        <div class="form-group">
          <label class="label">Học sinh chưa ghi danh</label>
          <select class="input select" name="student_id" @disabled($availableStudents->isEmpty())>
            @forelse($availableStudents as $student)
              <option value="{{ $student->id }}">{{ $student->name }} - {{ $student->email }}</option>
            @empty
              <option value="">Không còn học sinh phù hợp</option>
            @endforelse
          </select>
        </div>
        <button class="btn btn-primary" @disabled($availableStudents->isEmpty())>Thêm học sinh</button>
      </form>
      <form method="POST" action="{{ route('admin.courses.students.sync', $course->id) }}">
        @csrf
        <button class="btn btn-outline" @disabled(! $course->classModel)>Đồng bộ từ lớp</button>
      </form>
    </div>
    <div class="admin-row-meta" style="margin-top:.75rem;">Lớp liên kết: {{ $course->classModel?->name ?? 'Chưa gắn lớp' }} · {{ number_format($classStudentsCount) }} học sinh trong lớp</div>
  </div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Học sinh</th><th>Email</th><th>Ngày ghi danh</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
        @forelse($course->students as $student)
          <tr>
            <td><a class="admin-row-title" href="{{ route('admin.users.show', $student->id) }}">{{ $student->name }}</a></td>
            <td>{{ $student->email }}</td>
            <td>{{ $student->pivot->enrolled_at ? \Illuminate\Support\Carbon::parse($student->pivot->enrolled_at)->format('d/m/Y H:i') : 'Không rõ' }}</td>
            <td style="text-align:right;">
              <form method="POST" action="{{ route('admin.courses.students.remove', [$course->id, $student->id]) }}" data-confirm="Gỡ học sinh {{ $student->name }} khỏi khóa học?" data-confirm-ok="Gỡ học sinh">
                @csrf
                @method('DELETE')
                <button class="btn btn-destructive btn-sm">Gỡ</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="empty-state">Chưa có học sinh ghi danh.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Bài kiểm tra</h3></div>
    <div class="card-content course-content-list">
      @forelse($course->quizzes as $quiz)
        <div class="activity-item">
          <span class="badge badge-outline">{{ \App\Support\AdminLabels::status($quiz->status) }}</span>
          <div>
            <a class="admin-row-title" href="{{ route('admin.quizzes.show', $quiz->id) }}">{{ $quiz->title }}</a>
            <div class="admin-row-meta">{{ $quiz->questions->count() }} câu hỏi</div>
          </div>
        </div>
      @empty
        <div class="empty-state">Chưa có bài kiểm tra trong khóa học.</div>
      @endforelse
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3 class="card-title">Bài tập</h3></div>
    <div class="card-content course-content-list">
      @forelse($course->assignments as $assignment)
        <div class="activity-item">
          <span class="badge badge-outline">{{ \App\Support\AdminLabels::assignmentType($assignment->type) }}</span>
          <div>
            <a class="admin-row-title" href="{{ route('admin.assignments.show', $assignment->id) }}">{{ $assignment->title }}</a>
            <div class="admin-row-meta">{{ $assignment->submissions->count() }} bài nộp</div>
          </div>
        </div>
      @empty
        <div class="empty-state">Chưa có bài tập trong khóa học.</div>
      @endforelse
    </div>
  </section>
</div>
@endsection
