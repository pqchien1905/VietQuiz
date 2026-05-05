@extends('layouts.admin')

@section('title', 'Admin - Chi tiết bài kiểm tra')
@section('page-title', $quiz->title)
@section('page-description', 'Cấu hình bài kiểm tra, phạm vi giao bài, câu hỏi và lượt làm của học sinh.')

@php
  $statusOptions = ['draft', 'published', 'closed'];
  $typeOptions = ['exam' => 'Kiểm tra', 'practice' => 'Luyện tập'];
  $statusBadges = ['draft' => 'badge-warning', 'published' => 'badge-success', 'closed' => 'badge-danger'];
  $duration = $quiz->time_limit ?? $quiz->duration_minutes ?? 0;
  $totalPoints = $quiz->total_points ?? $quiz->questions->sum('points');
  $hasScope = $quiz->class_id || $quiz->course_id || ! empty($quiz->assigned_students);
  $hasSchedule = $quiz->start_at || $quiz->end_at;
@endphp

@push('styles')
<style>
  .quiz-detail-hero { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:center; padding:1.25rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:linear-gradient(135deg, color-mix(in srgb,var(--warning) 9%,var(--card)), var(--card)); box-shadow:var(--shadow-sm); }
  .quiz-detail-title h2 { margin:0; font-size:var(--text-xl); font-weight:900; }
  .quiz-detail-title p { margin:.25rem 0 0; color:var(--muted-foreground); }
  .quiz-detail-badges { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
  .quiz-config-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .quiz-config-grid .full { grid-column:1/-1; }
  .quiz-checks { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; }
  .quiz-check { padding:.75rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .quiz-check input { margin-right:.4rem; }
  .quiz-ops-list { display:grid; gap:.75rem; }
  .quiz-ops-item { display:flex; align-items:flex-start; gap:.65rem; padding:.75rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .quiz-ops-item strong { display:block; }
  .quiz-ops-item span { display:block; margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-sm); }
  .quiz-question-content { max-width:42rem; }
  @media (max-width:980px) { .quiz-detail-hero { grid-template-columns:1fr; } .quiz-detail-badges { justify-content:flex-start; } .quiz-config-grid,.quiz-checks { grid-template-columns:1fr; } .quiz-config-grid .full { grid-column:auto; } }
</style>
@endpush

@section('actions')
  <a class="btn btn-outline btn-sm" href="{{ route('admin.quizzes') }}">Quay lại</a>
@endsection

@section('content')
<section class="quiz-detail-hero">
  <div class="quiz-detail-title">
    <h2>{{ $quiz->title }}</h2>
    <p>{{ $quiz->teacher?->name ?? 'Không rõ giáo viên' }} · {{ $quiz->course?->name ?? 'Không gắn khóa học' }} · {{ $quiz->classModel?->name ?? 'Không gắn lớp' }}</p>
  </div>
  <div class="quiz-detail-badges">
    <span class="badge {{ $statusBadges[$quiz->status] ?? 'badge-outline' }}">{{ \App\Support\AdminLabels::status($quiz->status) }}</span>
    <span class="badge {{ ($quiz->quiz_type ?? 'exam') === 'practice' ? 'badge-info' : 'badge-warning' }}">{{ $typeOptions[$quiz->quiz_type ?? 'exam'] ?? 'Kiểm tra' }}</span>
    @if($quiz->trashed())<span class="badge badge-danger">Đã xóa</span>@endif
    @if($quiz->questions_count === 0)<span class="badge badge-warning">Chưa có câu hỏi</span>@endif
    @if(! $hasScope)<span class="badge badge-outline">Chưa gán phạm vi</span>@endif
    @if($quiz->start_at && $quiz->start_at->isFuture())<span class="badge badge-info">Chưa mở</span>@endif
    @if($quiz->end_at && $quiz->end_at->isPast())<span class="badge badge-danger">Quá hạn</span>@endif
  </div>
</section>

<section class="stats-grid stats-grid-4">
  @foreach(['Câu hỏi' => $quiz->questions_count, 'Lượt nộp' => $attemptSummary['submitted'], 'Chờ chấm' => $attemptSummary['ungraded'], 'Điểm TB' => round($attemptSummary['avg_score'] ?? 0, 1)] as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ $label }}</div><div class="stat-card__value">{{ number_format($value, is_float($value) ? 1 : 0) }}</div></div>
  @endforeach
</section>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Cấu hình bài kiểm tra</h3></div>
    <div class="card-content">
      <form method="POST" action="{{ route('admin.quizzes.update', $quiz->id) }}" class="quiz-config-grid">
        @csrf
        @method('PATCH')
        <div class="form-group full"><label class="label">Tiêu đề</label><input class="input" name="title" value="{{ old('title', $quiz->title) }}" required maxlength="255"></div>
        <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id" required>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $quiz->teacher_id) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Loại bài</label><select class="input select" name="quiz_type">@foreach($typeOptions as $value => $label)<option value="{{ $value }}" @selected(old('quiz_type', $quiz->quiz_type ?? 'exam') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Lớp</label><select class="input select" name="class_id"><option value="">Không gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id', $quiz->class_id) == $class->id)>{{ $class->name }} - {{ $class->code }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Không gắn khóa</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id', $quiz->course_id) == $course->id)>{{ $course->name }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Thời lượng phút</label><input class="input" name="duration_minutes" type="number" min="1" max="600" value="{{ old('duration_minutes', $quiz->duration_minutes ?? $duration) }}"></div>
        <div class="form-group"><label class="label">Tổng điểm</label><input class="input" name="total_points" type="number" min="1" max="10000" value="{{ old('total_points', $totalPoints) }}"></div>
        <div class="form-group"><label class="label">Điểm qua</label><input class="input" name="passing_score" type="number" min="0" max="100" value="{{ old('passing_score', $quiz->passing_score) }}"></div>
        <div class="form-group"><label class="label">Số lượt làm</label><input class="input" name="max_attempts" type="number" min="1" max="1" value="{{ old('max_attempts', $quiz->max_attempts ?? 1) }}"></div>
        <div class="form-group"><label class="label">Mở lúc</label><input class="input" name="start_at" type="datetime-local" value="{{ old('start_at', $quiz->start_at?->format('Y-m-d\TH:i')) }}"></div>
        <div class="form-group"><label class="label">Đóng lúc</label><input class="input" name="end_at" type="datetime-local" value="{{ old('end_at', $quiz->end_at?->format('Y-m-d\TH:i')) }}"></div>
        <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statusOptions as $status)<option value="{{ $status }}" @selected(old('status', $quiz->status) === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
        <div class="form-group full">
          <label class="label">Tùy chọn</label>
          <div class="quiz-checks">
            <label class="quiz-check"><input type="hidden" name="shuffle_questions" value="0"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $quiz->shuffle_questions))> Trộn câu hỏi</label>
            <label class="quiz-check"><input type="hidden" name="shuffle_answers" value="0"><input type="checkbox" name="shuffle_answers" value="1" @checked(old('shuffle_answers', $quiz->shuffle_answers))> Trộn đáp án</label>
            <label class="quiz-check"><input type="hidden" name="show_result" value="0"><input type="checkbox" name="show_result" value="1" @checked(old('show_result', $quiz->show_result))> Hiện kết quả</label>
            <label class="quiz-check"><input type="hidden" name="anti_cheat_enabled" value="0"><input type="checkbox" name="anti_cheat_enabled" value="1" @checked(old('anti_cheat_enabled', $quiz->anti_cheat_enabled))> Chống gian lận</label>
          </div>
        </div>
        <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="4" maxlength="2000">{{ old('description', $quiz->description) }}</textarea></div>
        <button class="btn btn-primary full">Lưu bài kiểm tra</button>
      </form>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3 class="card-title">Vận hành</h3></div>
    <div class="card-content quiz-ops-list">
      <div class="quiz-ops-item"><span class="badge {{ $quiz->questions_count > 0 ? 'badge-success' : 'badge-warning' }}">{{ $quiz->questions_count > 0 ? 'OK' : 'Thiếu' }}</span><div><strong>Ngân hàng câu hỏi</strong><span>{{ number_format($quiz->questions_count) }} câu hỏi, {{ number_format($totalPoints) }} điểm.</span></div></div>
      <div class="quiz-ops-item"><span class="badge {{ $hasScope ? 'badge-success' : 'badge-warning' }}">{{ $hasScope ? 'OK' : 'Thiếu' }}</span><div><strong>Phạm vi giao bài</strong><span>{{ $quiz->course?->name ?? $quiz->classModel?->name ?? 'Chưa gán khóa học, lớp hoặc danh sách học sinh cụ thể.' }}</span></div></div>
      <div class="quiz-ops-item"><span class="badge {{ $hasSchedule ? 'badge-info' : 'badge-outline' }}">{{ $hasSchedule ? 'Có lịch' : 'Mở ngay' }}</span><div><strong>Lịch làm bài</strong><span>{{ $quiz->start_at?->format('d/m/Y H:i') ?? 'Mở ngay' }} - {{ $quiz->end_at?->format('d/m/Y H:i') ?? 'Không hạn' }}</span></div></div>
      <div class="quiz-ops-item"><span class="badge {{ $attemptSummary['ungraded'] > 0 ? 'badge-warning' : 'badge-success' }}">{{ $attemptSummary['ungraded'] > 0 ? 'Chờ' : 'OK' }}</span><div><strong>Chấm điểm</strong><span>{{ number_format($attemptSummary['ungraded']) }} lượt nộp đang chờ xác nhận.</span></div></div>
      <a class="btn btn-outline" href="{{ route('admin.questions', ['q' => $quiz->title]) }}">Quản lý câu hỏi liên quan</a>
    </div>
  </section>
</div>

<section class="card">
  <div class="card-header"><h3 class="card-title">Câu hỏi</h3></div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Thứ tự</th><th>Nội dung</th><th>Loại</th><th>Đáp án</th><th>Điểm</th></tr></thead>
      <tbody>
        @forelse($quiz->questions as $question)
          <tr>
            <td>{{ $question->order }}</td>
            <td><div class="admin-row-title quiz-question-content">{{ \Illuminate\Support\Str::limit($question->content, 140) }}</div><div class="admin-row-meta">{{ $question->subject ?: 'Không môn học' }}</div></td>
            <td><span class="badge badge-outline">{{ \App\Support\AdminLabels::questionType($question->type) }}</span></td>
            <td>{{ \Illuminate\Support\Str::limit($question->correct_answer, 80) }}</td>
            <td>{{ number_format($question->points) }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-state">Bài kiểm tra chưa có câu hỏi.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Lượt làm</h3></div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Học sinh</th><th>Điểm</th><th>Chấm</th><th>Bắt đầu</th><th>Nộp lúc</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
        @forelse($attempts as $attempt)
          @php
            $attemptTotal = $attempt->total_points ?: $totalPoints;
            $percent = $attemptTotal ? round(($attempt->score ?? 0) / $attemptTotal * 100, 1) : 0;
          @endphp
          <tr>
            <td><div class="admin-row-title">{{ $attempt->student_name }}</div><div class="admin-row-meta">{{ $attempt->student_email }}</div></td>
            <td><span class="badge {{ $percent >= ($quiz->passing_score ?? 50) ? 'badge-success' : 'badge-danger' }}">{{ $attempt->score ?? 0 }}/{{ $attemptTotal }}</span><div class="admin-row-meta">{{ $percent }}%</div></td>
            <td><span class="badge {{ $attempt->is_graded ? 'badge-success' : 'badge-warning' }}">{{ $attempt->is_graded ? 'Đã chấm' : 'Chờ chấm' }}</span></td>
            <td>{{ $attempt->started_at ? \Illuminate\Support\Carbon::parse($attempt->started_at)->format('d/m/Y H:i') : 'Chưa bắt đầu' }}</td>
            <td>{{ $attempt->submitted_at ? \Illuminate\Support\Carbon::parse($attempt->submitted_at)->format('d/m/Y H:i') : 'Chưa nộp' }}</td>
            <td style="text-align:right;">
              <form method="POST" action="{{ route('admin.quizzes.attempts.reset', [$quiz->id, $attempt->user_id]) }}" data-confirm="Đặt lại lượt làm của {{ $attempt->student_name }}?" data-confirm-ok="Đặt lại">
                @csrf
                @method('DELETE')
                <button class="btn btn-destructive btn-sm">Đặt lại</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty-state">Chưa có lượt làm.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $attempts->links('components.pagination') }}</div>
</section>
@endsection
