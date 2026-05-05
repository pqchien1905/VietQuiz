@extends('layouts.admin')

@section('title', 'Admin - Điểm số')
@section('page-title', 'Điểm số')
@section('page-description', 'Kiểm soát điểm quiz và bài tập, phản hồi chấm bài, người chấm và các điểm cần rà soát.')

@php
  $summaryCards = [
    ['label' => 'Tổng điểm', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.grades')],
    ['label' => 'Điểm quiz', 'value' => $summary['quiz'], 'tone' => 'var(--info)', 'href' => route('admin.grades', ['type' => 'quiz'])],
    ['label' => 'Điểm bài tập', 'value' => $summary['assignment'], 'tone' => 'var(--success)', 'href' => route('admin.grades', ['type' => 'assignment'])],
    ['label' => 'Điểm TB', 'value' => $summary['avg'], 'tone' => 'var(--warning)', 'href' => route('admin.grades')],
    ['label' => 'Thiếu phản hồi', 'value' => $summary['missing_feedback'], 'tone' => 'var(--destructive)', 'href' => route('admin.grades', ['quality' => 'missing_feedback'])],
  ];
@endphp

@push('styles')
<style>
  .grades-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .grades-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .grades-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .grades-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .admin-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .admin-summary-grid .stat-card { min-height:7.25rem; }
  .grade-filter-grid { display:grid; grid-template-columns:minmax(260px,1fr) repeat(6,minmax(135px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .grade-cell { min-width:16rem; }
  .grade-source { min-width:18rem; }
  .grade-tags { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.45rem; }
  .grade-score { min-width:9rem; }
  .grade-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:11rem; }
  .grade-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .grade-modal-grid .full { grid-column:1/-1; }
  @media (max-width:1280px) { .grade-filter-grid { grid-template-columns:1fr 1fr 1fr; } .admin-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
  @media (max-width:820px) { .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .grade-modal-grid { grid-template-columns:1fr; } .grade-modal-grid .full { grid-column:auto; } }
  @media (max-width:620px) { .admin-summary-grid,.grade-filter-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<section class="stats-grid admin-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ is_float($card['value']) ? number_format($card['value'], 1) : number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header grades-header">
    <div class="grades-title">
      <h3>Bảng điểm hệ thống</h3>
      <p>Hiển thị {{ $grades->firstItem() ?? 0 }}-{{ $grades->lastItem() ?? 0 }} trên {{ number_format($grades->total()) }} kết quả.</p>
    </div>
    <a class="btn btn-outline" href="{{ route('admin.submissions', ['status' => 'ungraded']) }}">Bài nộp chưa chấm</a>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="grade-filter-grid">
      <div class="form-group"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Học sinh, email, nguồn điểm, phản hồi"></div>
      <div class="form-group"><label class="label">Học sinh</label><select class="input select" name="student_id"><option value="">Tất cả</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>{{ $student->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Người chấm</label><select class="input select" name="grader_id"><option value="">Tất cả</option>@foreach($graders as $grader)<option value="{{ $grader->id }}" @selected((string) request('grader_id') === (string) $grader->id)>{{ $grader->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Nguồn</label><select class="input select" name="type"><option value="">Tất cả</option><option value="quiz" @selected(request('type') === 'quiz')>Quiz</option><option value="assignment" @selected(request('type') === 'assignment')>Bài tập</option></select></div>
      <div class="form-group"><label class="label">Khoảng điểm</label><select class="input select" name="band"><option value="">Tất cả</option><option value="excellent" @selected(request('band') === 'excellent')>Từ 80</option><option value="pass" @selected(request('band') === 'pass')>50-79</option><option value="low" @selected(request('band') === 'low')>Dưới 50</option></select></div>
      <div class="form-group"><label class="label">Chất lượng</label><select class="input select" name="quality"><option value="">Tất cả</option><option value="missing_feedback" @selected(request('quality') === 'missing_feedback')>Thiếu phản hồi</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sort"><option value="">Mới chấm</option><option value="score_desc" @selected(request('sort') === 'score_desc')>Điểm cao</option><option value="score_asc" @selected(request('sort') === 'score_asc')>Điểm thấp</option><option value="student" @selected(request('sort') === 'student')>Học sinh A-Z</option><option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.grades') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Học sinh</th><th>Nguồn điểm</th><th>Điểm</th><th>Người chấm</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($grades as $grade)
        @php
          $isQuiz = $grade->gradable_type === \App\Models\Quiz::class;
          $maxScore = $isQuiz ? ($grade->gradable?->total_points ?: 100) : ($grade->gradable?->assignment?->total_points ?: 100);
          $percent = $maxScore ? round(((float) $grade->score / (float) $maxScore) * 100, 1) : 0;
          $sourceTitle = $isQuiz ? ($grade->gradable?->title ?? 'Quiz đã xóa') : ($grade->gradable?->assignment?->title ?? 'Bài nộp đã xóa');
        @endphp
        <tr>
          <td>
            <div class="grade-cell">
              <a class="admin-row-title" href="{{ route('admin.users.show', $grade->student_id) }}">{{ $grade->student?->name ?? 'Không rõ' }}</a>
              <div class="admin-row-meta">{{ $grade->student?->email }}</div>
              <div class="grade-tags">
                <span class="badge {{ $isQuiz ? 'badge-info' : 'badge-success' }}">{{ $isQuiz ? 'Quiz' : 'Bài tập' }}</span>
                @if(empty($grade->feedback))<span class="badge badge-warning">Thiếu phản hồi</span>@endif
              </div>
            </div>
          </td>
          <td>
            <div class="grade-source">
              <div class="admin-row-title">{{ $sourceTitle }}</div>
              <div class="admin-row-meta">{{ \App\Support\AdminLabels::gradableType($grade->gradable_type) }} #{{ $grade->gradable_id }}</div>
              @if(! $isQuiz && $grade->gradable)
                <div class="admin-row-meta">Nộp lúc {{ $grade->gradable->submitted_at?->format('d/m/Y H:i') }}</div>
              @endif
            </div>
          </td>
          <td>
            <div class="grade-score">
              <span class="badge {{ $percent >= 80 ? 'badge-success' : ($percent >= 50 ? 'badge-warning' : 'badge-danger') }}">{{ $grade->score }}/{{ $maxScore }}</span>
              <div class="admin-row-meta">{{ $percent }}%</div>
              <div class="admin-row-meta">{{ \Illuminate\Support\Str::limit($grade->feedback ?: 'Không phản hồi', 80) }}</div>
            </div>
          </td>
          <td>{{ $grade->grader?->name ?? 'Admin' }}<div class="admin-row-meta">{{ $grade->graded_at?->format('d/m/Y H:i') }}</div></td>
          <td>
            <div class="grade-actions">
              <button class="btn btn-primary btn-sm" type="button" onclick="openAdminGradeModal('edit-grade-{{ $grade->id }}')">Sửa</button>
              <form method="POST" action="{{ route('admin.grades.delete', $grade->id) }}" data-confirm="Xóa điểm của {{ $grade->student?->name ?? 'học sinh này' }}?" data-confirm-ok="Xóa điểm">
                @csrf
                @method('DELETE')
                <button class="btn btn-destructive btn-sm">Xóa</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có điểm phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $grades->links('components.pagination') }}</div>
</section>

@foreach($grades as $grade)
  @php
    $isQuiz = $grade->gradable_type === \App\Models\Quiz::class;
    $maxScore = $isQuiz ? ($grade->gradable?->total_points ?: 100) : ($grade->gradable?->assignment?->total_points ?: 100);
    $sourceTitle = $isQuiz ? ($grade->gradable?->title ?? 'Quiz đã xóa') : ($grade->gradable?->assignment?->title ?? 'Bài nộp đã xóa');
  @endphp
  <div class="modal-overlay" id="edit-grade-{{ $grade->id }}">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-grade-title-{{ $grade->id }}" style="max-width:38rem;">
      <form method="POST" action="{{ route('admin.grades.update', $grade->id) }}">
        @csrf
        @method('PATCH')
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="edit-grade-title-{{ $grade->id }}">Sửa điểm</h2>
            <p class="modal-desc">{{ $grade->student?->name ?? 'Không rõ học sinh' }} · {{ $sourceTitle }}</p>
          </div>
          <button class="modal-close" type="button" onclick="closeAdminGradeModal('edit-grade-{{ $grade->id }}')" aria-label="Đóng">×</button>
        </div>
        <div class="modal-body">
          <div class="grade-modal-grid">
            <div class="form-group"><label class="label">Điểm</label><input class="input" name="score" type="number" min="0" max="{{ $maxScore }}" step="1" value="{{ old('score', $grade->score) }}" required></div>
            <div class="form-group"><label class="label">Tối đa</label><input class="input" value="{{ $maxScore }}" disabled></div>
            <div class="form-group full"><label class="label">Phản hồi</label><textarea class="input" name="feedback" rows="4" maxlength="3000">{{ old('feedback', $grade->feedback) }}</textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeAdminGradeModal('edit-grade-{{ $grade->id }}')">Hủy</button>
          <button class="btn btn-primary">Lưu điểm</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@push('scripts')
<script>
  function openAdminGradeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminGradeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminGradeModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminGradeModal(overlay.id);
      });
    }
  });
</script>
@endpush
@endsection
