@extends('layouts.admin')

@section('title', 'Admin - Khóa học')
@section('page-title', 'Khóa học')
@section('page-description', 'Quản lý khóa học theo giáo viên, lớp liên kết, ghi danh học sinh, quiz và bài tập.')

@php
  $statusOptions = ['draft', 'published'];
  $summaryCards = [
    ['label' => 'Tổng khóa', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.courses', ['state' => 'all'])],
    ['label' => 'Đã xuất bản', 'value' => $summary['published'], 'tone' => 'var(--success)', 'href' => route('admin.courses', ['status' => 'published'])],
    ['label' => 'Bản nháp', 'value' => $summary['draft'], 'tone' => 'var(--warning)', 'href' => route('admin.courses', ['status' => 'draft'])],
    ['label' => 'Đã xóa', 'value' => $summary['deleted'], 'tone' => 'var(--destructive)', 'href' => route('admin.courses', ['state' => 'deleted'])],
    ['label' => 'Học sinh', 'value' => $summary['students'], 'tone' => 'var(--info)', 'href' => route('admin.courses')],
  ];
  $operationCards = [
    ['label' => 'Chưa gắn lớp', 'value' => $summary['unlinked'], 'desc' => 'Không thể đồng bộ học sinh tự động.', 'href' => route('admin.courses', ['scope' => 'unlinked'])],
    ['label' => 'Chưa có học sinh', 'value' => $summary['empty_students'], 'desc' => 'Cần ghi danh hoặc đồng bộ từ lớp.', 'href' => route('admin.courses', ['scope' => 'empty_students'])],
    ['label' => 'Chưa có nội dung', 'value' => $summary['empty_content'], 'desc' => 'Chưa có quiz hoặc bài tập để học.', 'href' => route('admin.courses', ['scope' => 'empty_content'])],
  ];
@endphp

@push('styles')
<style>
  .admin-courses-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .admin-courses-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .admin-courses-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .admin-courses-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .admin-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .admin-summary-grid .stat-card { min-height:7.25rem; }
  .course-ops-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
  .course-ops-card { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); color:inherit; text-decoration:none; box-shadow:var(--shadow-sm); }
  .course-ops-card strong { display:block; font-size:var(--text-xl); line-height:1; margin-top:.35rem; color:var(--warning); }
  .course-ops-card span { display:block; color:var(--muted-foreground); font-size:var(--text-sm); margin-top:.35rem; }
  .course-cell { display:flex; align-items:center; gap:.75rem; min-width:18rem; }
  .course-mark { width:2.75rem; height:2.75rem; border-radius:.75rem; display:grid; place-items:center; color:#fff; font-weight:900; flex:0 0 auto; box-shadow:var(--shadow-sm); }
  .course-name-row { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .course-state-tags { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.45rem; }
  .course-metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; min-width:15rem; }
  .course-metric { padding:.55rem .65rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
  .course-metric strong { display:block; font-size:var(--text-base); line-height:1.1; }
  .course-metric span { display:block; margin-top:.15rem; color:var(--muted-foreground); font-size:var(--text-xs); white-space:nowrap; }
  .course-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:13rem; }
  .course-filter-grid { display:grid; grid-template-columns:minmax(260px,1fr) repeat(6,minmax(135px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .course-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .course-modal-grid .full { grid-column:1/-1; }
  .course-empty-teacher { margin-top:.5rem; padding:.75rem; border:1px solid color-mix(in srgb,var(--warning) 35%,var(--border)); border-radius:var(--radius-md); background:color-mix(in srgb,var(--warning) 10%,var(--card)); color:var(--muted-foreground); font-size:var(--text-sm); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
  @media (max-width:1280px) { .admin-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
  @media (max-width:1200px) { .course-filter-grid { grid-template-columns:1fr 1fr; } .course-metrics { grid-template-columns:repeat(3,minmax(0,1fr)); min-width:13rem; } }
  @media (max-width:920px) { .course-ops-grid { grid-template-columns:1fr; } }
  @media (max-width:820px) { .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .course-metrics { grid-template-columns:1fr 1fr; } }
  @media (max-width:720px) { .course-filter-grid,.course-modal-grid { grid-template-columns:1fr; } .course-modal-grid .full { grid-column:auto; } .course-cell { min-width:0; } .course-metrics { min-width:0; } }
  @media (max-width:520px) { .admin-summary-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('actions')
  <button class="btn btn-primary btn-sm" type="button" onclick="openAdminCourseModal('create-course-modal')">Thêm khóa học</button>
@endsection

@section('content')
<section class="stats-grid admin-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="course-ops-grid">
  @foreach($operationCards as $card)
    <a class="course-ops-card" href="{{ $card['href'] }}">
      <div>
        <div class="stat-card__label">{{ $card['label'] }}</div>
        <strong>{{ number_format($card['value']) }}</strong>
        <span>{{ $card['desc'] }}</span>
      </div>
      <span class="badge badge-warning">Cần xử lý</span>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header admin-courses-header">
    <div class="admin-courses-title">
      <h3>Danh sách khóa học</h3>
      <p>Hiển thị {{ $courses->firstItem() ?? 0 }}-{{ $courses->lastItem() ?? 0 }} trên {{ number_format($courses->total()) }} kết quả.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openAdminCourseModal('create-course-modal')">Thêm khóa học</button>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="course-filter-grid">
      <div class="form-group">
        <label class="label">Tìm kiếm</label>
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Tên khóa học hoặc mô tả">
      </div>
      <div class="form-group">
        <label class="label">Giáo viên</label>
        <select class="input select" name="teacher_id">
          <option value="">Tất cả</option>
          @foreach($teachers as $teacher)
            <option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="label">Lớp</label>
        <select class="input select" name="class_id">
          <option value="">Tất cả</option>
          @foreach($classes as $class)
            <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="label">Trạng thái</label>
        <select class="input select" name="status">
          <option value="">Tất cả</option>
          @foreach($statusOptions as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="label">Dữ liệu</label>
        <select class="input select" name="state">
          <option value="active" @selected(request('state', 'active') === 'active')>Đang dùng</option>
          <option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option>
          <option value="all" @selected(request('state') === 'all')>Tất cả</option>
        </select>
      </div>
      <div class="form-group">
        <label class="label">Vận hành</label>
        <select class="input select" name="scope">
          <option value="">Tất cả</option>
          <option value="unlinked" @selected(request('scope') === 'unlinked')>Chưa gắn lớp</option>
          <option value="empty_students" @selected(request('scope') === 'empty_students')>Chưa có học sinh</option>
          <option value="empty_content" @selected(request('scope') === 'empty_content')>Chưa có nội dung</option>
        </select>
      </div>
      <div class="form-group">
        <label class="label">Sắp xếp</label>
        <select class="input select" name="sort">
          <option value="">Mới nhất</option>
          <option value="students" @selected(request('sort') === 'students')>Nhiều học sinh</option>
          <option value="content" @selected(request('sort') === 'content')>Nhiều nội dung</option>
          <option value="name" @selected(request('sort') === 'name')>Tên A-Z</option>
          <option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option>
        </select>
      </div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.courses') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead>
        <tr>
          <th>Khóa học</th>
          <th>Phụ trách</th>
          <th>Số liệu</th>
          <th>Trạng thái</th>
          <th style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      @forelse($courses as $course)
        <tr style="{{ $course->trashed() ? 'background:color-mix(in srgb,var(--destructive) 7%,transparent);' : '' }}">
          <td>
            <div class="course-cell">
              <div class="course-mark" style="background:{{ $course->color ?: 'var(--primary)' }}">{{ $course->icon ?: mb_strtoupper(mb_substr($course->name, 0, 1)) }}</div>
              <div style="min-width:0;">
                <div class="course-name-row">
                  <a class="admin-row-title" href="{{ route('admin.courses.show', $course->id) }}">{{ $course->name }}</a>
                </div>
                <div class="admin-row-meta">{{ \Illuminate\Support\Str::limit($course->description ?: 'Chưa có mô tả', 95) }}</div>
                <div class="course-state-tags">
                  @if(! $course->class_id)<span class="badge badge-warning">Chưa gắn lớp</span>@endif
                  @if($course->students_count === 0)<span class="badge badge-outline">Chưa có học sinh</span>@endif
                  @if($course->quizzes_count + $course->assignments_count === 0)<span class="badge badge-outline">Chưa có nội dung</span>@endif
                </div>
              </div>
            </div>
          </td>
          <td>
            <div class="admin-row-title">{{ $course->teacher?->name ?? 'Không rõ giáo viên' }}</div>
            <div class="admin-row-meta">{{ $course->classModel?->name ?? 'Không gắn lớp' }}</div>
          </td>
          <td>
            <div class="course-metrics">
              <div class="course-metric"><strong>{{ number_format($course->students_count) }}</strong><span>Học sinh</span></div>
              <div class="course-metric"><strong>{{ number_format($course->quizzes_count) }}</strong><span>Quiz</span></div>
              <div class="course-metric"><strong>{{ number_format($course->assignments_count) }}</strong><span>Bài tập</span></div>
            </div>
          </td>
          <td>
            <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
              <span class="badge {{ $course->status === 'published' ? 'badge-success' : 'badge-warning' }}">{{ \App\Support\AdminLabels::status($course->status ?? 'draft') }}</span>
              @if($course->trashed())
                <span class="badge badge-danger">Đã xóa</span>
              @endif
            </div>
          </td>
          <td>
            <div class="course-actions">
              <a class="btn btn-outline btn-sm" href="{{ route('admin.courses.show', $course->id) }}">Chi tiết</a>
              <button class="btn btn-primary btn-sm" type="button" onclick="openAdminCourseModal('edit-course-{{ $course->id }}')">Sửa</button>
              @if($course->trashed())
                <form method="POST" action="{{ route('admin.courses.restore', $course->id) }}">
                  @csrf
                  <button class="btn btn-outline-primary btn-sm">Khôi phục</button>
                </form>
              @else
                <form method="POST" action="{{ route('admin.courses.delete', $course->id) }}" data-confirm="Xóa khóa học {{ $course->name }} khỏi danh sách hoạt động? Dữ liệu liên quan vẫn được giữ để khôi phục." data-confirm-ok="Xóa khóa học">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-destructive btn-sm">Xóa</button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có khóa học phù hợp với bộ lọc.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $courses->links('components.pagination') }}</div>
</section>

<div class="modal-overlay" id="create-course-modal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="create-course-title" style="max-width:44rem;">
    <form method="POST" action="{{ route('admin.courses.store') }}">
      @csrf
      <input type="hidden" name="_form" value="create">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="create-course-title">Thêm khóa học</h2>
          <p class="modal-desc">Tạo khóa học, gắn giáo viên phụ trách và lớp nguồn nếu cần đồng bộ học sinh.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminCourseModal('create-course-modal')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        <div class="course-modal-grid">
          <div class="form-group"><label class="label">Tên khóa học</label><input class="input" name="name" value="{{ old('_form') === 'create' ? old('name') : '' }}" required maxlength="255"></div>
          <div class="form-group">
            <label class="label">Giáo viên</label>
            <select class="input select" name="teacher_id" required @disabled($teachers->isEmpty())>
              <option value="">{{ $teachers->isEmpty() ? 'Chưa có giáo viên' : 'Chọn giáo viên' }}</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(old('_form') === 'create' && (string) old('teacher_id') === (string) $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>
              @endforeach
            </select>
            @if($teachers->isEmpty())
              <div class="course-empty-teacher">
                <span>Cần tạo ít nhất một tài khoản giáo viên trước khi tạo khóa học.</span>
                <a class="btn btn-outline btn-sm" href="{{ route('admin.users', ['create' => 'teacher']) }}">Tạo giáo viên</a>
              </div>
            @endif
          </div>
          <div class="form-group"><label class="label">Lớp liên kết</label><select class="input select" name="class_id"><option value="">Không gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('_form') === 'create' && (string) old('class_id') === (string) $class->id)>{{ $class->name }} - {{ $class->code }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statusOptions as $status)<option value="{{ $status }}" @selected(old('_form') === 'create' && old('status', 'draft') === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Màu</label><input class="input" name="color" value="{{ old('_form') === 'create' ? old('color', '#3b82f6') : '#3b82f6' }}" maxlength="20"></div>
          <div class="form-group"><label class="label">Ký hiệu</label><input class="input" name="icon" value="{{ old('_form') === 'create' ? old('icon') : '' }}" maxlength="10" placeholder="VD: JS"></div>
          <div class="form-group full"><label class="label">Ảnh bìa</label><input class="input" name="cover_image" value="{{ old('_form') === 'create' ? old('cover_image') : '' }}" maxlength="255" placeholder="URL hoặc đường dẫn ảnh"></div>
          <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="3" maxlength="2000">{{ old('_form') === 'create' ? old('description') : '' }}</textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminCourseModal('create-course-modal')">Hủy</button>
        <button class="btn btn-primary" type="submit" @disabled($teachers->isEmpty())>Tạo khóa học</button>
      </div>
    </form>
  </div>
</div>

@foreach($courses as $course)
  <div class="modal-overlay" id="edit-course-{{ $course->id }}">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-course-title-{{ $course->id }}" style="max-width:44rem;">
      <form method="POST" action="{{ route('admin.courses.update', $course->id) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_form" value="edit-{{ $course->id }}">
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="edit-course-title-{{ $course->id }}">Sửa khóa học</h2>
            <p class="modal-desc">{{ $course->teacher?->name ?? 'Chưa rõ giáo viên' }} · {{ $course->classModel?->name ?? 'Không gắn lớp' }}</p>
          </div>
          <button class="modal-close" type="button" onclick="closeAdminCourseModal('edit-course-{{ $course->id }}')" aria-label="Đóng">×</button>
        </div>
        <div class="modal-body">
          <div class="course-modal-grid">
            <div class="form-group"><label class="label">Tên khóa học</label><input class="input" name="name" value="{{ old('_form') === 'edit-'.$course->id ? old('name') : $course->name }}" required maxlength="255"></div>
            <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id" required>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((old('_form') === 'edit-'.$course->id ? old('teacher_id') : $course->teacher_id) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Lớp liên kết</label><select class="input select" name="class_id"><option value="">Không gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((old('_form') === 'edit-'.$course->id ? old('class_id') : $course->class_id) == $class->id)>{{ $class->name }} - {{ $class->code }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statusOptions as $status)<option value="{{ $status }}" @selected((old('_form') === 'edit-'.$course->id ? old('status') : ($course->status ?? 'draft')) === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Màu</label><input class="input" name="color" value="{{ old('_form') === 'edit-'.$course->id ? old('color') : $course->color }}" maxlength="20"></div>
            <div class="form-group"><label class="label">Ký hiệu</label><input class="input" name="icon" value="{{ old('_form') === 'edit-'.$course->id ? old('icon') : $course->icon }}" maxlength="10"></div>
            <div class="form-group full"><label class="label">Ảnh bìa</label><input class="input" name="cover_image" value="{{ old('_form') === 'edit-'.$course->id ? old('cover_image') : $course->cover_image }}" maxlength="255"></div>
            <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="3" maxlength="2000">{{ old('_form') === 'edit-'.$course->id ? old('description') : $course->description }}</textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeAdminCourseModal('edit-course-{{ $course->id }}')">Hủy</button>
          <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@push('scripts')
<script>
  function openAdminCourseModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminCourseModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminCourseModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminCourseModal(overlay.id);
      });
    }
  });

  const oldForm = @json(old('_form'));
  if (oldForm === 'create') {
    openAdminCourseModal('create-course-modal');
  } else if (oldForm && oldForm.startsWith('edit-')) {
    openAdminCourseModal('edit-course-' + oldForm.replace('edit-', ''));
  }
</script>
@endpush
@endsection
