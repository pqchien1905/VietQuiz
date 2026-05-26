{{-- Teacher: course detail --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $colors = ['#2563eb', '#ea580c', '#16a34a', '#7c3aed', '#dc2626', '#0891b2', '#ca8a04', '#db2777'];
  $courseColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $course->color ?? '') ? $course->color : '#2563eb';
  $statusLabels = ['draft' => 'Nháp', 'published' => 'Đã xuất bản'];
  $statusLabel = $statusLabels[$course->status] ?? 'Nháp';
  $students = $course->students;
  $quizzes = $course->quizzes->sortByDesc('created_at');
  $assignments = $course->assignments->sortByDesc('created_at');
@endphp

@push('styles')
<style>
  .course-detail{display:flex;flex-direction:column;gap:1.25rem}
  .detail-hero{border:1px solid var(--border);border-radius:var(--radius-xl);overflow:hidden;background:var(--card)}
  .detail-hero__top{min-height:8.5rem;padding:1.25rem;background:linear-gradient(135deg, {{ $courseColor }}, color-mix(in srgb, {{ $courseColor }} 76%, #111827));color:#fff;display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;flex-wrap:wrap}
  .detail-hero__icon{width:3.5rem;height:3.5rem;border-radius:var(--radius-lg);background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800}
  .detail-hero__title{font-size:var(--text-2xl);font-weight:800;margin:.75rem 0 .375rem;line-height:1.2}
  .detail-hero__meta{display:flex;gap:.5rem;flex-wrap:wrap}
  .detail-hero__body{padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
  .detail-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
  .detail-actions form{display:inline-flex}
  .detail-tabs{display:flex;align-items:center;gap:.25rem;border-bottom:1px solid var(--border);overflow-x:auto}
  .detail-tab{border:0;background:transparent;padding:.875rem 1rem;color:var(--muted-foreground);font-size:var(--text-sm);font-weight:700;cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap}
  .detail-tab:hover{color:var(--foreground);background:var(--muted)}
  .detail-tab.active{color:var(--primary);border-bottom-color:var(--primary)}
  .detail-panel{display:none}
  .detail-panel.active{display:block}
  .detail-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.25rem}
  .content-list{display:flex;flex-direction:column;gap:.75rem}
  .content-row{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1rem;display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}
  .content-row__title{font-size:var(--text-base);font-weight:800;margin:0 0 .25rem}
  .content-row__meta{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;font-size:var(--text-xs);color:var(--muted-foreground)}
  .empty-panel{text-align:center;padding:3rem 1.25rem;color:var(--muted-foreground)}
  .empty-panel__icon{width:4rem;height:4rem;border-radius:999px;background:var(--muted);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--muted-foreground)}
  .student-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--border);padding:.75rem 0}
  .student-row:last-child{border-bottom:0}
  .student-avatar{width:2.25rem;height:2.25rem;border-radius:999px;background:var(--muted);display:flex;align-items:center;justify-content:center;font-size:var(--text-xs);font-weight:800;flex-shrink:0}
  .settings-section{border:1px solid var(--border);border-radius:var(--radius-xl);background:var(--card);overflow:hidden}
  .settings-section__header{padding:1rem 1.25rem;border-bottom:1px solid var(--border)}
  .settings-section__body{padding:1.25rem}
  .danger-box{border:1px solid color-mix(in srgb,var(--destructive) 22%,var(--border));background:color-mix(in srgb,var(--destructive) 5%,transparent);border-radius:var(--radius-lg);padding:1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
  .color-picker{display:flex;gap:.5rem;flex-wrap:wrap}
  .color-dot{width:1.875rem;height:1.875rem;border-radius:999px;border:2px solid transparent;box-shadow:0 0 0 1px var(--border);cursor:pointer}
  .color-dot.is-selected{border-color:var(--foreground)}
  @media(max-width:900px){.detail-grid{grid-template-columns:1fr}.detail-hero__body{align-items:stretch}.detail-actions .btn{justify-content:center}.detail-actions{width:100%}}
</style>
@endpush

@section('content')
<div class="course-detail">
  <nav style="display:flex;align-items:center;gap:.5rem;font-size:var(--text-sm);color:var(--muted-foreground);flex-wrap:wrap;">
    <a href="{{ route('teacher.courses') }}" style="color:var(--primary);text-decoration:none;font-weight:700;">Khóa học</a>
    <span>/</span>
    <span>{{ $course->name }}</span>
  </nav>

  @if(session('success'))
    <div class="alert alert-success"><span>{{ session('success') }}</span></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger"><span>{{ session('error') }}</span></div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger"><span>{{ $errors->first() }}</span></div>
  @endif

  <section class="detail-hero">
    <div class="detail-hero__top">
      <div>
        <div class="detail-hero__icon">{{ mb_substr($course->name, 0, 1) }}</div>
        <h1 class="detail-hero__title">{{ $course->name }}</h1>
        <div class="detail-hero__meta">
          <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;">{{ $statusLabel }}</span>
          @if($course->classModel)
            <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;">{{ $course->classModel->name }}</span>
          @else
            <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;">Chưa gắn lớp</span>
          @endif
          @if($course->classModel?->subject)
            <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;">{{ $course->classModel->subject }}</span>
          @endif
        </div>
      </div>
      <div class="detail-actions">
        <a class="btn btn-outline btn-sm" href="{{ route('teacher.courses') }}">Quay lại</a>
        <button class="btn btn-outline btn-sm" type="button" onclick="openEditModal()">Chỉnh sửa</button>
        @if($course->status === 'published')
          <form method="POST" action="{{ route('teacher.courses.unpublish', $course) }}">@csrf<button class="btn btn-outline btn-sm" type="submit">Đưa về nháp</button></form>
        @else
          <form method="POST" action="{{ route('teacher.courses.publish', $course) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Xuất bản</button></form>
        @endif
      </div>
    </div>
    <div class="detail-hero__body">
      <p style="margin:0;color:var(--muted-foreground);line-height:1.6;max-width:48rem;">{{ $course->description ?: 'Chưa có mô tả cho khóa học này.' }}</p>
      <div class="detail-actions">
        <a class="btn btn-primary btn-sm" href="{{ route('teacher.quiz-create', ['course_id' => $course->id, 'class_id' => $course->class_id]) }}">Tạo bài kiểm tra</a>
        @if($course->class_id)
          <button class="btn btn-outline btn-sm" type="button" onclick="openAssignmentModal()">Tạo bài tập</button>
          <form method="POST" action="{{ route('teacher.courses.sync-students', $course) }}">@csrf<button class="btn btn-ghost btn-sm" type="submit">Đồng bộ học sinh</button></form>
        @endif
      </div>
    </div>
  </section>

  <div class="stats-grid stats-grid-4">
    <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Học sinh</div><div class="stat-card__value">{{ $course->students_count }}</div><div class="stat-card__label">đang theo học</div></div>
    <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài kiểm tra</div><div class="stat-card__value">{{ $course->quizzes_count }}</div><div class="stat-card__label">{{ $publishedQuizzes }} đã xuất bản, {{ $draftQuizzes }} nháp</div></div>
    <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài tập</div><div class="stat-card__value">{{ $course->assignments_count }}</div><div class="stat-card__label">{{ $submittedAssignments }} bài nộp</div></div>
    <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB</div><div class="stat-card__value" style="{{ $avgScore !== null ? 'color:var(--success)' : 'color:var(--muted-foreground)' }}">{{ $avgScore !== null ? $avgScore . '%' : '—' }}</div><div class="stat-card__label">{{ $latestActivity ? 'cập nhật ' . \Illuminate\Support\Carbon::parse($latestActivity)->diffForHumans() : 'chưa có hoạt động' }}</div></div>
  </div>

  <div class="card">
    <div class="detail-tabs">
      <button class="detail-tab active" type="button" data-tab="overview">Tổng quan</button>
      <button class="detail-tab" type="button" data-tab="quizzes">Bài kiểm tra <span class="badge badge-outline">{{ $quizzes->count() }}</span></button>
      <button class="detail-tab" type="button" data-tab="assignments">Bài tập <span class="badge badge-outline">{{ $assignments->count() }}</span></button>
      <button class="detail-tab" type="button" data-tab="students">Học sinh <span class="badge badge-outline">{{ $students->count() }}</span></button>
      <button class="detail-tab" type="button" data-tab="settings">Cài đặt</button>
    </div>

    <div style="padding:1.25rem;">
      <div class="detail-panel active" id="panel-overview">
        <div class="detail-grid">
          <div class="content-list">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
              <h2 style="font-size:var(--text-lg);font-weight:800;margin:0;">Hoạt động gần đây</h2>
              <div class="detail-actions">
                <a class="btn btn-outline btn-sm" href="{{ route('teacher.quiz-create', ['course_id' => $course->id, 'class_id' => $course->class_id]) }}">Thêm bài kiểm tra</a>
                @if($course->class_id)<button class="btn btn-outline btn-sm" type="button" onclick="openAssignmentModal()">Thêm bài tập</button>@endif
              </div>
            </div>
            @forelse($quizzes->take(3) as $quiz)
              <div class="content-row">
                <div>
                  <h3 class="content-row__title">{{ $quiz->title }}</h3>
                  <div class="content-row__meta"><span>Bài kiểm tra</span><span>{{ $quiz->questions->count() }} câu hỏi</span><span>{{ $quiz->created_at?->diffForHumans() }}</span></div>
                </div>
                <a class="btn btn-outline btn-sm" href="{{ route('teacher.quiz-detail', $quiz) }}">Xem</a>
              </div>
            @empty
              <div class="empty-panel"><div class="empty-panel__icon">KT</div><h3>Chưa có bài kiểm tra</h3><p>Tạo bài kiểm tra đầu tiên cho khóa học này.</p></div>
            @endforelse
            @foreach($assignments->take(3) as $assignment)
              <div class="content-row">
                <div>
                  <h3 class="content-row__title">{{ $assignment->title }}</h3>
                  <div class="content-row__meta"><span>Bài tập</span><span>{{ $assignment->submissions->count() }} bài nộp</span><span>{{ $assignment->due_at ? 'Hạn ' . $assignment->due_at->format('d/m/Y H:i') : 'Không hạn nộp' }}</span></div>
                </div>
                <a class="btn btn-outline btn-sm" href="{{ route('teacher.assignments') }}">Quản lý</a>
              </div>
            @endforeach
          </div>
          <aside class="settings-section">
            <div class="settings-section__header"><h3 style="font-size:var(--text-base);font-weight:800;margin:0;">Thông tin khóa học</h3></div>
            <div class="settings-section__body" style="display:flex;flex-direction:column;gap:.75rem;">
              <div><div style="font-size:var(--text-xs);color:var(--muted-foreground);font-weight:700;">Lớp</div><div>{{ $course->classModel?->name ?? 'Chưa gắn lớp' }}</div></div>
              <div><div style="font-size:var(--text-xs);color:var(--muted-foreground);font-weight:700;">Môn học</div><div>{{ $course->classModel?->subject ?? 'Chưa có' }}</div></div>
              <div><div style="font-size:var(--text-xs);color:var(--muted-foreground);font-weight:700;">Ngày tạo</div><div>{{ $course->created_at?->format('d/m/Y H:i') }}</div></div>
              <div><div style="font-size:var(--text-xs);color:var(--muted-foreground);font-weight:700;">Cập nhật</div><div>{{ $course->updated_at?->format('d/m/Y H:i') }}</div></div>
            </div>
          </aside>
        </div>
      </div>

      <div class="detail-panel" id="panel-quizzes">
        <div class="content-list">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;"><h2 style="font-size:var(--text-lg);font-weight:800;margin:0;">Bài kiểm tra</h2><a class="btn btn-primary btn-sm" href="{{ route('teacher.quiz-create', ['course_id' => $course->id, 'class_id' => $course->class_id]) }}">Tạo bài kiểm tra</a></div>
          @forelse($quizzes as $quiz)
            <div class="content-row">
              <div>
                <h3 class="content-row__title">{{ $quiz->title }}</h3>
                <div class="content-row__meta"><span class="badge {{ $quiz->status === 'published' ? 'badge-success' : 'badge-outline' }}">{{ $quiz->status === 'published' ? 'Đã xuất bản' : 'Nháp' }}</span><span>{{ $quiz->questions->count() }} câu hỏi</span><span>{{ $quiz->attempts->count() }} lượt làm</span></div>
              </div>
              <div class="detail-actions">
                <a class="btn btn-outline btn-sm" href="{{ route('teacher.quiz-detail', $quiz) }}">Xem chi tiết</a>
                @if($quiz->status === 'published')
                  <form method="POST" action="{{ route('teacher.quizzes.unpublish', $quiz) }}">@csrf<button class="btn btn-ghost btn-sm" type="submit">Gỡ xuất bản</button></form>
                @else
                  <form method="POST" action="{{ route('teacher.quizzes.publish', $quiz) }}">@csrf<button class="btn btn-ghost btn-sm" type="submit">Xuất bản</button></form>
                @endif
              </div>
            </div>
          @empty
            <div class="empty-panel"><div class="empty-panel__icon">KT</div><h3>Chưa có bài kiểm tra</h3><p>Tạo bài kiểm tra để học sinh bắt đầu làm bài.</p></div>
          @endforelse
        </div>
      </div>

      <div class="detail-panel" id="panel-assignments">
        <div class="content-list">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;"><h2 style="font-size:var(--text-lg);font-weight:800;margin:0;">Bài tập</h2>@if($course->class_id)<button class="btn btn-primary btn-sm" type="button" onclick="openAssignmentModal()">Tạo bài tập</button>@endif</div>
          @forelse($assignments as $assignment)
            <div class="content-row">
              <div>
                <h3 class="content-row__title">{{ $assignment->title }}</h3>
                <div class="content-row__meta"><span>{{ $assignment->type ?? 'essay' }}</span><span>{{ $assignment->total_points ?? 100 }} điểm</span><span>{{ $assignment->submissions->count() }} bài nộp</span><span>{{ $assignment->due_at ? 'Hạn ' . $assignment->due_at->format('d/m/Y H:i') : 'Không hạn nộp' }}</span></div>
              </div>
              <div class="detail-actions">
                <a class="btn btn-outline btn-sm" href="{{ route('teacher.assignments') }}">Quản lý</a>
                <form method="POST" action="{{ route('teacher.assignments.destroy', $assignment) }}" data-confirm="Xóa bài tập này?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" style="color:var(--destructive);" type="submit">Xóa</button></form>
              </div>
            </div>
          @empty
            <div class="empty-panel"><div class="empty-panel__icon">BT</div><h3>Chưa có bài tập</h3><p>Tạo bài tập để giao nhiệm vụ ngoài bài kiểm tra.</p></div>
          @endforelse
        </div>
      </div>

      <div class="detail-panel" id="panel-students">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
          <h2 style="font-size:var(--text-lg);font-weight:800;margin:0;">Học sinh trong khóa học</h2>
          @if($course->class_id)<form method="POST" action="{{ route('teacher.courses.sync-students', $course) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Đồng bộ từ lớp</button></form>@endif
        </div>
        @forelse($students as $student)
          <div class="student-row">
            <div style="display:flex;align-items:center;gap:.75rem;min-width:0;">
              <div class="student-avatar">{{ mb_substr($student->name, 0, 1) }}</div>
              <div style="min-width:0;"><div style="font-weight:800;">{{ $student->name }}</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $student->email }}</div></div>
            </div>
            <form method="POST" action="{{ route('teacher.courses.remove-student', [$course, $student]) }}" data-confirm="Gỡ học sinh này khỏi khóa học?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" style="color:var(--destructive);" type="submit">Gỡ</button></form>
          </div>
        @empty
          <div class="empty-panel"><div class="empty-panel__icon">HS</div><h3>Chưa có học sinh</h3><p>{{ $course->class_id ? 'Đồng bộ học sinh từ lớp để bắt đầu.' : 'Gắn khóa học với một lớp trước khi đồng bộ học sinh.' }}</p></div>
        @endforelse
      </div>

      <div class="detail-panel" id="panel-settings">
        <div class="settings-section" style="margin-bottom:1rem;">
          <div class="settings-section__header"><h2 style="font-size:var(--text-lg);font-weight:800;margin:0;">Cài đặt khóa học</h2></div>
          <form method="POST" action="{{ route('teacher.courses.update', $course) }}">
            @csrf
            @method('PUT')
            <div class="settings-section__body" style="display:flex;flex-direction:column;gap:1rem;">
              <div class="form-group"><label class="label label-required">Tên khóa học</label><input class="input" name="name" value="{{ old('name', $course->name) }}" required></div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group"><label class="label">Lớp học</label><select class="input select" name="class_id"><option value="">Chưa gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) old('class_id', $course->class_id) === (string) $class->id)>{{ $class->name }}{{ $class->subject ? ' - ' . $class->subject : '' }}</option>@endforeach</select></div>
                <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="draft" @selected(old('status', $course->status) === 'draft')>Nháp</option><option value="published" @selected(old('status', $course->status) === 'published')>Đã xuất bản</option></select></div>
              </div>
              <div class="form-group"><label class="label">Mô tả</label><textarea class="input" name="description" style="min-height:5.5rem;resize:vertical;">{{ old('description', $course->description) }}</textarea></div>
              <div class="form-group"><label class="label">Màu khóa học</label><input type="hidden" name="color" id="settings-color" value="{{ old('color', $courseColor) }}"><div class="color-picker" data-target="settings-color">@foreach($colors as $color)<button type="button" class="color-dot {{ old('color', $courseColor) === $color ? 'is-selected' : '' }}" style="background:{{ $color }};" data-color="{{ $color }}"></button>@endforeach</div></div>
              <div style="display:flex;justify-content:flex-end;"><button class="btn btn-primary" type="submit">Lưu thay đổi</button></div>
            </div>
          </form>
        </div>
        <div class="danger-box">
          <div><div style="font-weight:800;color:var(--destructive);">Xóa khóa học</div><div style="font-size:var(--text-sm);color:var(--muted-foreground);">Khóa học sẽ được đưa vào thùng rác.</div></div>
          <button class="btn btn-destructive btn-sm" type="button" onclick="openDeleteModal()">Xóa khóa học</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="edit-modal">
  <div class="modal" style="max-width:38rem;">
    <div class="modal-header"><div><h3 class="modal-title">Chỉnh sửa khóa học</h3><p class="modal-desc">Cập nhật thông tin khóa học.</p></div><button class="modal-close" type="button" onclick="closeModal('edit-modal')">×</button></div>
    <form method="POST" action="{{ route('teacher.courses.update', $course) }}">
      @csrf @method('PUT')
      <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
        <div class="form-group"><label class="label label-required">Tên khóa học</label><input class="input" name="name" value="{{ old('name', $course->name) }}" required></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group"><label class="label">Lớp học</label><select class="input select" name="class_id"><option value="">Chưa gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) old('class_id', $course->class_id) === (string) $class->id)>{{ $class->name }}{{ $class->subject ? ' - ' . $class->subject : '' }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="draft" @selected(old('status', $course->status) === 'draft')>Nháp</option><option value="published" @selected(old('status', $course->status) === 'published')>Đã xuất bản</option></select></div>
        </div>
        <div class="form-group"><label class="label">Mô tả</label><textarea class="input" name="description" style="min-height:5.5rem;resize:vertical;">{{ old('description', $course->description) }}</textarea></div>
        <input type="hidden" name="color" id="edit-color" value="{{ old('color', $courseColor) }}">
      </div>
      <div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('edit-modal')">Hủy</button><button class="btn btn-primary" type="submit">Lưu thay đổi</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="assignment-modal">
  <div class="modal" style="max-width:38rem;">
    <div class="modal-header"><div><h3 class="modal-title">Tạo bài tập nhanh</h3><p class="modal-desc">Bài tập sẽ được gắn với khóa học "{{ $course->name }}".</p></div><button class="modal-close" type="button" onclick="closeModal('assignment-modal')">×</button></div>
    <form method="POST" action="{{ route('teacher.assignments.store') }}">
      @csrf
      <input type="hidden" name="class_id" value="{{ $course->class_id }}">
      <input type="hidden" name="course_id" value="{{ $course->id }}">
      <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
        <div class="form-group"><label class="label label-required">Tiêu đề bài tập</label><input class="input" name="title" placeholder="VD: Bài tập ôn chương 1" required></div>
        <div class="form-group"><label class="label">Mô tả</label><textarea class="input" name="description" style="min-height:5rem;resize:vertical;"></textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;"><div class="form-group"><label class="label">Loại bài tập</label><select class="input select" name="type"><option value="essay">Tự luận</option><option value="practice">Thực hành</option><option value="project">Dự án</option><option value="code">Lập trình</option></select></div><div class="form-group"><label class="label">Điểm tối đa</label><input class="input" type="number" name="total_points" min="1" max="10000" value="100"></div></div>
        <div class="form-group"><label class="label">Hạn nộp</label><input class="input" type="datetime-local" name="due_at" min="{{ now()->format('Y-m-d\TH:i') }}"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('assignment-modal')">Hủy</button><button class="btn btn-primary" type="submit">Tạo bài tập</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="delete-modal">
  <div class="modal" style="max-width:28rem;">
    <div class="modal-header"><div><h3 class="modal-title">Xóa khóa học?</h3><p class="modal-desc">{{ $course->name }}</p></div><button class="modal-close" type="button" onclick="closeModal('delete-modal')">×</button></div>
    <div class="modal-body"><p style="font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.6;margin:0;">Khóa học sẽ được đưa vào thùng rác. Bạn có thể khôi phục trong trang thùng rác nếu cần.</p></div>
    <div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('delete-modal')">Hủy</button><form method="POST" action="{{ route('teacher.courses.destroy', $course) }}">@csrf @method('DELETE')<button class="btn btn-destructive" type="submit">Xóa khóa học</button></form></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
  'use strict';

  window.openModal = function(id) {
    document.getElementById(id)?.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  window.closeModal = function(id) {
    document.getElementById(id)?.classList.remove('open');
    document.body.style.overflow = '';
  };

  window.openEditModal = function() { openModal('edit-modal'); };
  window.openAssignmentModal = function() { openModal('assignment-modal'); };
  window.openDeleteModal = function() { openModal('delete-modal'); };

  document.querySelectorAll('.detail-tab').forEach(function(button) {
    button.addEventListener('click', function() {
      document.querySelectorAll('.detail-tab').forEach(tab => tab.classList.remove('active'));
      document.querySelectorAll('.detail-panel').forEach(panel => panel.classList.remove('active'));
      button.classList.add('active');
      document.getElementById('panel-' + button.dataset.tab)?.classList.add('active');
    });
  });

  function selectColor(targetId, color) {
    const input = document.getElementById(targetId);
    if (!input) return;
    input.value = color;
    document.querySelectorAll('.color-picker[data-target="' + targetId + '"] .color-dot').forEach(button => {
      button.classList.toggle('is-selected', button.dataset.color === color);
    });
  }

  document.querySelectorAll('.color-picker').forEach(function(picker) {
    picker.addEventListener('click', function(event) {
      const button = event.target.closest('.color-dot');
      if (!button) return;
      selectColor(picker.dataset.target, button.dataset.color);
    });
  });

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.modal-overlay.open').forEach(overlay => overlay.classList.remove('open'));
    document.body.style.overflow = '';
  });
})();
</script>
@endpush
