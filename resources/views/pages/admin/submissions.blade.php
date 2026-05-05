@extends('layouts.admin')

@section('title', 'Admin - Bài nộp')
@section('page-title', 'Bài nộp')
@section('page-description', 'Tra cứu, phân loại và xử lý bài nộp theo học sinh, bài tập, lớp, khóa học, hạn nộp và trạng thái chấm.')

@php
  $summaryCards = [
    ['label' => 'Tổng bài nộp', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.submissions')],
    ['label' => 'Đã chấm', 'value' => $summary['graded'], 'tone' => 'var(--success)', 'href' => route('admin.submissions', ['status' => 'graded'])],
    ['label' => 'Chưa chấm', 'value' => $summary['ungraded'], 'tone' => 'var(--warning)', 'href' => route('admin.submissions', ['status' => 'ungraded'])],
    ['label' => 'Nộp trễ', 'value' => $summary['late'], 'tone' => 'var(--destructive)', 'href' => route('admin.submissions', ['scope' => 'late'])],
    ['label' => 'Có tệp', 'value' => $summary['attachments'], 'tone' => 'var(--info)', 'href' => route('admin.submissions', ['scope' => 'attachment'])],
  ];
@endphp

@push('styles')
<style>
  .submissions-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .submissions-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .submissions-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .submissions-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .admin-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .admin-summary-grid .stat-card { min-height:7.25rem; }
  .submission-filter-grid { display:grid; grid-template-columns:minmax(260px,1fr) repeat(7,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .submission-cell { min-width:16rem; }
  .submission-tags { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.45rem; }
  .submission-preview { max-width:24rem; color:var(--muted-foreground); font-size:var(--text-sm); }
  .submission-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:12rem; }
  .submission-grade-box { min-width:10rem; }
  .submission-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .submission-modal-grid .full { grid-column:1/-1; }
  @media (max-width:1380px) { .submission-filter-grid { grid-template-columns:1fr 1fr 1fr; } }
  @media (max-width:1100px) { .admin-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
  @media (max-width:820px) { .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .submission-modal-grid { grid-template-columns:1fr; } .submission-modal-grid .full { grid-column:auto; } }
  @media (max-width:620px) { .admin-summary-grid,.submission-filter-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<section class="stats-grid admin-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header submissions-header">
    <div class="submissions-title">
      <h3>Danh sách bài nộp</h3>
      <p>Hiển thị {{ $submissions->firstItem() ?? 0 }}-{{ $submissions->lastItem() ?? 0 }} trên {{ number_format($submissions->total()) }} kết quả.</p>
    </div>
    <a class="btn btn-outline" href="{{ route('admin.grades') }}">Xem điểm số</a>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="submission-filter-grid">
      <div class="form-group"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Học sinh, email, bài tập, nội dung"></div>
      <div class="form-group"><label class="label">Học sinh</label><select class="input select" name="student_id"><option value="">Tất cả</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>{{ $student->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Bài tập</label><select class="input select" name="assignment_id"><option value="">Tất cả</option>@foreach($assignments as $assignment)<option value="{{ $assignment->id }}" @selected((string) request('assignment_id') === (string) $assignment->id)>{{ $assignment->title }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id"><option value="">Tất cả</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Lớp</label><select class="input select" name="class_id"><option value="">Tất cả</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Tất cả</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Chấm điểm</label><select class="input select" name="status"><option value="">Tất cả</option><option value="graded" @selected(request('status') === 'graded')>Đã chấm</option><option value="ungraded" @selected(request('status') === 'ungraded')>Chưa chấm</option></select></div>
      <div class="form-group"><label class="label">Loại</label><select class="input select" name="scope"><option value="">Tất cả</option><option value="late" @selected(request('scope') === 'late')>Nộp trễ</option><option value="on_time" @selected(request('scope') === 'on_time')>Đúng hạn</option><option value="attachment" @selected(request('scope') === 'attachment')>Có tệp</option><option value="text" @selected(request('scope') === 'text')>Có nội dung</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sort"><option value="">Mới nộp</option><option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option><option value="student" @selected(request('sort') === 'student')>Học sinh A-Z</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.submissions') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Học sinh</th><th>Bài tập</th><th>Phạm vi</th><th>Nộp lúc</th><th>Điểm</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($submissions as $submission)
        @php
          $assignment = $submission->assignment;
          $grade = $submission->grades->first();
          $isLate = $assignment?->due_at && $submission->submitted_at && $submission->submitted_at->gt($assignment->due_at);
          $maxScore = $assignment?->total_points ?: 100;
        @endphp
        <tr>
          <td>
            <div class="submission-cell">
              <a class="admin-row-title" href="{{ route('admin.users.show', $submission->student_id) }}">{{ $submission->student?->name ?? 'Không rõ' }}</a>
              <div class="admin-row-meta">{{ $submission->student?->email }}</div>
              <div class="submission-tags">
                @if($grade)<span class="badge badge-success">Đã chấm</span>@else<span class="badge badge-warning">Chưa chấm</span>@endif
                @if($isLate)<span class="badge badge-danger">Nộp trễ</span>@endif
                @if($submission->attachment)<span class="badge badge-info">Có tệp</span>@endif
              </div>
            </div>
          </td>
          <td>
            <a class="admin-row-title" href="{{ route('admin.assignments.show', $submission->assignment_id) }}">{{ $assignment?->title ?? 'Bài tập đã xóa' }}</a>
            <div class="submission-preview">{{ \Illuminate\Support\Str::limit($submission->content ?: $submission->attachment ?: 'Không có nội dung xem trước', 110) }}</div>
          </td>
          <td>
            {{ $assignment?->class?->name ?? 'Không lớp' }}
            <div class="admin-row-meta">{{ $assignment?->course?->name ?? 'Không khóa' }}</div>
            <div class="admin-row-meta">{{ $assignment?->teacher?->name ?? 'Không rõ giáo viên' }}</div>
          </td>
          <td>
            {{ $submission->submitted_at?->format('d/m/Y H:i') }}
            <div class="admin-row-meta">Hạn: {{ $assignment?->due_at?->format('d/m/Y H:i') ?? 'Không hạn' }}</div>
          </td>
          <td>
            <div class="submission-grade-box">
              @if($grade)
                <span class="badge badge-success">{{ $grade->score }}/{{ $maxScore }}</span>
                <div class="admin-row-meta">{{ \Illuminate\Support\Str::limit($grade->feedback ?: 'Không phản hồi', 70) }}</div>
              @else
                <span class="badge badge-warning">Chưa chấm</span>
              @endif
            </div>
          </td>
          <td>
            <div class="submission-actions">
              <button class="btn btn-primary btn-sm" type="button" onclick="openAdminSubmissionModal('grade-submission-{{ $submission->id }}')">{{ $grade ? 'Sửa điểm' : 'Chấm' }}</button>
              <form method="POST" action="{{ route('admin.submissions.delete', $submission->id) }}" data-confirm="Xóa bài nộp của {{ $submission->student?->name ?? 'học sinh này' }}?" data-confirm-ok="Xóa bài nộp">
                @csrf
                @method('DELETE')
                <button class="btn btn-destructive btn-sm">Xóa</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty-state">Không có bài nộp phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $submissions->links('components.pagination') }}</div>
</section>

@foreach($submissions as $submission)
  @php
    $grade = $submission->grades->first();
    $maxScore = $submission->assignment?->total_points ?: 100;
  @endphp
  <div class="modal-overlay" id="grade-submission-{{ $submission->id }}">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="grade-submission-title-{{ $submission->id }}" style="max-width:38rem;">
      <form method="POST" action="{{ route('admin.submissions.grade', $submission->id) }}">
        @csrf
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="grade-submission-title-{{ $submission->id }}">Chấm bài nộp</h2>
            <p class="modal-desc">{{ $submission->student?->name ?? 'Không rõ học sinh' }} · {{ $submission->assignment?->title ?? 'Bài tập đã xóa' }}</p>
          </div>
          <button class="modal-close" type="button" onclick="closeAdminSubmissionModal('grade-submission-{{ $submission->id }}')" aria-label="Đóng">×</button>
        </div>
        <div class="modal-body">
          <div class="submission-modal-grid">
            <div class="form-group"><label class="label">Điểm</label><input class="input" name="score" type="number" min="0" max="{{ $maxScore }}" value="{{ old('score', $grade?->score) }}" required></div>
            <div class="form-group"><label class="label">Tối đa</label><input class="input" value="{{ $maxScore }}" disabled></div>
            <div class="form-group full"><label class="label">Phản hồi</label><textarea class="input" name="feedback" rows="4" maxlength="3000">{{ old('feedback', $grade?->feedback) }}</textarea></div>
            <div class="form-group full"><label class="label">Nội dung nộp</label><div class="empty-state" style="text-align:left;">{{ \Illuminate\Support\Str::limit($submission->content ?: $submission->attachment ?: 'Không có nội dung xem trước', 500) }}</div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeAdminSubmissionModal('grade-submission-{{ $submission->id }}')">Hủy</button>
          <button class="btn btn-primary">Lưu điểm</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@push('scripts')
<script>
  function openAdminSubmissionModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminSubmissionModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminSubmissionModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminSubmissionModal(overlay.id);
      });
    }
  });
</script>
@endpush
@endsection
