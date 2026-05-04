@extends('layouts.admin')

@section('title', 'Admin - Chi tiết người dùng')
@section('page-title', $user->name)
@section('page-description', 'Hồ sơ, vai trò, liên kết lớp học, khóa học, bài kiểm tra, bài nộp, điểm, thông báo và yêu cầu hỗ trợ.')

@section('actions')
  <a class="btn btn-outline btn-sm" href="{{ route('admin.users') }}">Quay lại</a>
@endsection

@section('content')
<section class="stats-grid stats-grid-4">
  @foreach([
    'Lớp tạo' => $user->created_classes_count,
    'Khóa tạo' => $user->created_courses_count,
    'Bài kiểm tra tạo' => $user->quizzes_count,
    'Bài nộp' => $user->submissions_count,
    'Điểm' => $user->grades_count,
    'Thông báo' => $user->notifications_count,
    'Yêu cầu hỗ trợ' => $user->tickets_count,
    'Lớp đang học' => $user->classes_count,
  ] as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ $label }}</div><div class="stat-card__value">{{ number_format($value) }}</div></div>
  @endforeach
</section>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Hồ sơ tài khoản</h3></div>
    <div class="card-content">
      <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="admin-form-grid" style="min-width:0;">
        @csrf @method('PATCH')
        <div class="form-group"><label class="label">Tên</label><input class="input" name="name" value="{{ old('name', $user->name) }}"></div>
        <div class="form-group"><label class="label">Email</label><input class="input" name="email" value="{{ old('email', $user->email) }}"></div>
        <div class="form-group"><label class="label">Vai trò</label><select class="input select" name="role">@foreach(['admin','teacher','student'] as $role)<option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ \App\Support\AdminLabels::role($role) }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">SĐT</label><input class="input" name="phone" value="{{ old('phone', $user->phone) }}"></div>
        <div class="form-group"><label class="label">Môn học</label><input class="input" name="subject" value="{{ old('subject', $user->subject) }}"></div>
        <div class="form-group"><label class="label">Mật khẩu mới</label><input class="input" name="password" type="password" autocomplete="new-password"></div>
        <button class="btn btn-primary" style="grid-column:1/-1;">Lưu hồ sơ</button>
      </form>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3 class="card-title">Trạng thái</h3></div>
    <div class="card-content">
      <div class="activity-item"><span class="badge badge-outline">Mã #{{ $user->id }}</span><div><div class="admin-row-title">{{ \App\Support\AdminLabels::role($user->role) }}</div><div class="admin-row-meta">Tạo lúc {{ $user->created_at?->format('d/m/Y H:i') }}</div></div></div>
      <div class="activity-item"><span class="badge {{ $user->trashed() ? 'badge-danger' : 'badge-success' }}">{{ $user->trashed() ? 'Đã khóa' : 'Hoạt động' }}</span><div class="admin-row-meta">{{ $user->trashed() ? 'Tài khoản đang ở thùng rác' : 'Có thể đăng nhập hệ thống thường' }}</div></div>
      <div class="activity-item"><span class="badge {{ $user->vipSubscription?->is_active ? 'badge-warning' : 'badge-outline' }}">VIP</span><div class="admin-row-meta">{{ $user->vipSubscription ? \App\Support\AdminLabels::vipPlan($user->vipSubscription->plan) : 'Chưa có gói VIP đang hoạt động' }}</div></div>
      <div class="admin-table-actions" style="justify-content:flex-start;margin-top:1rem;">
        @if($user->trashed())
          <form method="POST" action="{{ route('admin.users.restore', $user->id) }}">@csrf<button class="btn btn-outline-primary">Khôi phục</button></form>
        @else
          <form method="POST" action="{{ route('admin.users.delete', $user->id) }}" onsubmit="return confirm('Khóa tài khoản này?')">@csrf @method('DELETE')<button class="btn btn-destructive">Khóa tài khoản</button></form>
        @endif
      </div>
    </div>
  </section>
</div>

<div class="admin-grid-3">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Lớp học</h3></div>
    <div class="card-content">
      @forelse($user->createdClasses->merge($user->classes)->unique('id') as $class)
        <div class="activity-item"><span class="badge badge-outline">{{ \App\Support\AdminLabels::status($class->status ?? 'active') }}</span><div><a class="admin-row-title" href="{{ route('admin.classes.show', $class->id) }}">{{ $class->name }}</a><div class="admin-row-meta">{{ $class->code ?? 'Không có mã' }}</div></div></div>
      @empty <div class="empty-state">Chưa có lớp.</div> @endforelse
    </div>
  </section>
  <section class="card">
    <div class="card-header"><h3 class="card-title">Khóa học</h3></div>
    <div class="card-content">
      @forelse($user->createdCourses->merge($user->courses)->unique('id') as $course)
        <div class="activity-item"><span class="badge badge-outline">{{ \App\Support\AdminLabels::status($course->status ?? 'draft') }}</span><div><a class="admin-row-title" href="{{ route('admin.courses.show', $course->id) }}">{{ $course->name }}</a><div class="admin-row-meta">{{ $course->students->count() }} học sinh</div></div></div>
      @empty <div class="empty-state">Chưa có khóa học.</div> @endforelse
    </div>
  </section>
  <section class="card">
    <div class="card-header"><h3 class="card-title">Bài kiểm tra đã làm</h3></div>
    <div class="card-content">
      @forelse($attempts as $quiz)
        <div class="activity-item"><span class="badge badge-outline">{{ $quiz->pivot->score ?? 0 }} điểm</span><div><a class="admin-row-title" href="{{ route('admin.quizzes.show', $quiz->id) }}">{{ $quiz->title }}</a><div class="admin-row-meta">{{ $quiz->pivot->submitted_at ? \Illuminate\Support\Carbon::parse($quiz->pivot->submitted_at)->format('d/m/Y H:i') : 'Chưa nộp' }}</div></div></div>
      @empty <div class="empty-state">Chưa có lượt làm bài kiểm tra.</div> @endforelse
    </div>
  </section>
</div>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Bài nộp và điểm</h3></div>
    <div class="card-content">
      @forelse($user->submissions as $submission)
        <div class="activity-item"><span class="badge badge-info">Nộp</span><div><div class="admin-row-title">{{ $submission->assignment?->title ?? 'Bài tập đã xóa' }}</div><div class="admin-row-meta">{{ $submission->submitted_at?->format('d/m/Y H:i') }}</div></div></div>
      @empty <div class="empty-state">Chưa có bài nộp.</div> @endforelse
      @forelse($user->grades as $grade)
        <div class="activity-item"><span class="badge badge-success">{{ $grade->score }}</span><div><div class="admin-row-title">{{ \App\Support\AdminLabels::gradableType($grade->gradable_type) }} #{{ $grade->gradable_id }}</div><div class="admin-row-meta">{{ $grade->feedback }}</div></div></div>
      @empty @endforelse
    </div>
  </section>
  <section class="card">
    <div class="card-header"><h3 class="card-title">Thông báo và yêu cầu hỗ trợ</h3></div>
    <div class="card-content">
      @forelse($user->notifications as $notification)
        <div class="activity-item"><span class="badge {{ $notification->is_read ? 'badge-outline' : 'badge-warning' }}">{{ \App\Support\AdminLabels::notificationType($notification->type) }}</span><div><div class="admin-row-title">{{ $notification->title }}</div><div class="admin-row-meta">{{ \Illuminate\Support\Str::limit($notification->body, 90) }}</div></div></div>
      @empty <div class="empty-state">Chưa có thông báo.</div> @endforelse
      @forelse($user->tickets as $ticket)
        <div class="activity-item"><span class="badge badge-outline">{{ \App\Support\AdminLabels::status($ticket->status) }}</span><div><div class="admin-row-title">{{ $ticket->subject }}</div><div class="admin-row-meta">{{ \Illuminate\Support\Str::limit($ticket->description, 90) }}</div></div></div>
      @empty @endforelse
    </div>
  </section>
</div>
@endsection
