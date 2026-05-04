@extends('layouts.admin')

@section('title', 'Admin - Chi tiết lớp')
@section('page-title', $class->name)
@section('page-description', 'Quản lý giáo viên, học sinh, khóa học, bài kiểm tra và bài tập thuộc lớp.')

@section('actions')
  <a class="btn btn-outline btn-sm" href="{{ route('admin.classes') }}">Quay lại</a>
@endsection

@section('content')
<section class="stats-grid stats-grid-4">
  @foreach(['Học sinh' => $class->students_count, 'Khóa học' => $class->courses_count, 'Bài kiểm tra' => $class->quizzes_count, 'Bài tập' => $class->assignments_count] as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ $label }}</div><div class="stat-card__value">{{ number_format($value) }}</div></div>
  @endforeach
</section>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Cấu hình lớp</h3></div>
    <div class="card-content">
      <form method="POST" action="{{ route('admin.classes.update', $class->id) }}" class="admin-form-grid" style="min-width:0;">
        @csrf @method('PATCH')
        <div class="form-group"><label class="label">Tên lớp</label><input class="input" name="name" value="{{ old('name', $class->name) }}"></div>
        <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $class->teacher_id) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Môn học</label><input class="input" name="subject" value="{{ old('subject', $class->subject) }}"></div>
        <div class="form-group"><label class="label">Khối</label><input class="input" name="grade_level" value="{{ old('grade_level', $class->grade_level) }}"></div>
        <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="active" @selected(($class->status ?? 'active') === 'active')>{{ \App\Support\AdminLabels::status('active') }}</option><option value="archived" @selected($class->status === 'archived')>{{ \App\Support\AdminLabels::status('archived') }}</option></select></div>
        <button class="btn btn-primary" style="grid-column:1/-1;">Lưu lớp học</button>
      </form>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3 class="card-title">Thêm học sinh</h3></div>
    <div class="card-content">
      <form method="POST" action="{{ route('admin.classes.students.add', $class->id) }}" class="admin-inline-form">
        @csrf
        <select class="input select" name="student_id" style="min-width:260px;">
          @foreach($availableStudents as $student)<option value="{{ $student->id }}">{{ $student->name }} - {{ $student->email }}</option>@endforeach
        </select>
        <button class="btn btn-primary">Thêm</button>
      </form>
      <div class="admin-row-meta" style="margin-top:.75rem;">Mã lớp: {{ $class->code ?? 'Chưa có mã' }} · Giáo viên: {{ $class->teacher?->name ?? 'Không rõ' }}</div>
    </div>
  </section>
</div>

<section class="card">
  <div class="card-header"><h3 class="card-title">Danh sách học sinh</h3></div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table><thead><tr><th>Học sinh</th><th>Email</th><th>Ngày tham gia</th><th></th></tr></thead><tbody>
      @forelse($class->students as $student)
        <tr><td><a class="admin-row-title" href="{{ route('admin.users.show', $student->id) }}">{{ $student->name }}</a></td><td>{{ $student->email }}</td><td>{{ $student->pivot->joined_at ? \Illuminate\Support\Carbon::parse($student->pivot->joined_at)->format('d/m/Y H:i') : '' }}</td><td><form method="POST" action="{{ route('admin.classes.students.remove', [$class->id, $student->id]) }}" onsubmit="return confirm('Gỡ học sinh khỏi lớp?')">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Gỡ</button></form></td></tr>
      @empty <tr><td colspan="4" class="empty-state">Chưa có học sinh.</td></tr> @endforelse
    </tbody></table>
  </div>
</section>

<div class="admin-grid-3">
  @foreach([['Khóa học', $class->courses, 'admin.courses.show', 'name'], ['Bài kiểm tra', $class->quizzes, 'admin.quizzes.show', 'title'], ['Bài tập', $class->assignments, 'admin.assignments.show', 'title']] as [$title, $items, $route, $field])
    <section class="card"><div class="card-header"><h3 class="card-title">{{ $title }}</h3></div><div class="card-content">
      @forelse($items as $item)
        <div class="activity-item"><span class="badge badge-outline">{{ isset($item->status) ? \App\Support\AdminLabels::status($item->status) : \App\Support\AdminLabels::assignmentType($item->type ?? null) }}</span><div><a class="admin-row-title" href="{{ route($route, $item->id) }}">{{ $item->{$field} }}</a><div class="admin-row-meta">{{ $item->teacher?->name ?? 'Không rõ giáo viên' }}</div></div></div>
      @empty <div class="empty-state">Chưa có dữ liệu.</div> @endforelse
    </div></section>
  @endforeach
</div>
@endsection
