@extends('layouts.admin')

@section('title', 'Admin - Chi tiết bài kiểm tra')
@section('page-title', $quiz->title)
@section('page-description', 'Cấu hình bài kiểm tra, danh sách câu hỏi và lượt làm của học sinh.')

@section('actions')
  <a class="btn btn-outline btn-sm" href="{{ route('admin.quizzes') }}">Quay lại</a>
@endsection

@section('content')
<section class="stats-grid stats-grid-4">
  @foreach(['Câu hỏi' => $quiz->questions_count, 'Thời lượng' => $quiz->duration_minutes ?? 0, 'Điểm qua' => $quiz->passing_score ?? 0, 'Tổng điểm' => $quiz->total_points ?? $quiz->questions->sum('points')] as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ $label }}</div><div class="stat-card__value">{{ number_format($value) }}</div></div>
  @endforeach
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Cấu hình bài kiểm tra</h3></div>
  <div class="card-content">
    <form method="POST" action="{{ route('admin.quizzes.update', $quiz->id) }}" class="admin-form-grid" style="min-width:0;">
      @csrf @method('PATCH')
      <div class="form-group"><label class="label">Tiêu đề</label><input class="input" name="title" value="{{ old('title', $quiz->title) }}"></div>
      <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $quiz->teacher_id) == $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Lớp</label><select class="input select" name="class_id"><option value="">Không gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id', $quiz->class_id) == $class->id)>{{ $class->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Không gắn khóa</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id', $quiz->course_id) == $course->id)>{{ $course->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Thời lượng phút</label><input class="input" name="duration_minutes" type="number" value="{{ old('duration_minutes', $quiz->duration_minutes) }}"></div>
      <div class="form-group"><label class="label">Điểm qua</label><input class="input" name="passing_score" type="number" value="{{ old('passing_score', $quiz->passing_score) }}"></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach(['draft','published','closed'] as $status)<option value="{{ $status }}" @selected($quiz->status === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
      <button class="btn btn-primary" style="grid-column:1/-1;">Lưu bài kiểm tra</button>
    </form>
  </div>
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Câu hỏi</h3><a class="btn btn-outline btn-sm" href="{{ route('admin.questions', ['q' => $quiz->title]) }}">Quản lý ngân hàng</a></div>
  <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Thứ tự</th><th>Nội dung</th><th>Loại</th><th>Đáp án</th><th>Điểm</th></tr></thead><tbody>
    @forelse($quiz->questions as $question)
      <tr><td>{{ $question->order }}</td><td><div class="admin-row-title">{{ \Illuminate\Support\Str::limit($question->content, 120) }}</div><div class="admin-row-meta">{{ $question->subject }}</div></td><td><span class="badge badge-outline">{{ \App\Support\AdminLabels::questionType($question->type) }}</span></td><td>{{ \Illuminate\Support\Str::limit($question->correct_answer, 80) }}</td><td>{{ $question->points }}</td></tr>
    @empty <tr><td colspan="5" class="empty-state">Bài kiểm tra chưa có câu hỏi.</td></tr> @endforelse
  </tbody></table></div>
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Lượt làm</h3></div>
  <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Học sinh</th><th>Điểm</th><th>Trạng thái</th><th>Nộp lúc</th><th></th></tr></thead><tbody>
    @forelse($attempts as $attempt)
      <tr><td><div class="admin-row-title">{{ $attempt->student_name }}</div><div class="admin-row-meta">{{ $attempt->student_email }}</div></td><td>{{ $attempt->score ?? 0 }}</td><td><span class="badge badge-outline">{{ \App\Support\AdminLabels::status($attempt->status ?? 'submitted') }}</span></td><td>{{ $attempt->submitted_at ? \Illuminate\Support\Carbon::parse($attempt->submitted_at)->format('d/m/Y H:i') : 'Chưa nộp' }}</td><td><form method="POST" action="{{ route('admin.quizzes.attempts.reset', [$quiz->id, $attempt->user_id]) }}" onsubmit="return confirm('Đặt lại lượt làm này?')">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Đặt lại</button></form></td></tr>
    @empty <tr><td colspan="5" class="empty-state">Chưa có lượt làm.</td></tr> @endforelse
  </tbody></table></div>
  <div class="card-footer">{{ $attempts->links('components.pagination') }}</div>
</section>
@endsection
