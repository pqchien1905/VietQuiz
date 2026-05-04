{{-- Teacher: courses --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $colors = ['#2563eb', '#ea580c', '#16a34a', '#7c3aed', '#dc2626', '#0891b2', '#ca8a04', '#db2777'];
  $statusLabels = [
      'draft' => 'Nháp',
      'published' => 'Đã xuất bản',
  ];
@endphp

@push('styles')
<style>
  .course-card{border:1px solid var(--border);border-radius:var(--radius-xl);background:var(--card);overflow:hidden;display:flex;flex-direction:column;transition:box-shadow var(--transition-fast),transform var(--transition-fast),border-color var(--transition-fast)}
  .course-card:hover{box-shadow:var(--shadow-lg);transform:translateY(-2px);border-color:color-mix(in srgb,var(--primary) 28%,var(--border))}
  .course-card.is-clickable{cursor:pointer}
  .course-card.is-clickable:focus{outline:2px solid var(--primary);outline-offset:3px}
  .course-banner{height:5.5rem;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;color:#fff}
  .course-banner__icon{width:3rem;height:3rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.16);font-size:1.35rem;font-weight:800;backdrop-filter:blur(4px)}
  .course-banner__status{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.25rem .625rem;background:rgba(255,255,255,.18);font-size:var(--text-xs);font-weight:700;color:#fff}
  .course-card__body{padding:1rem 1.25rem;flex:1}
  .course-title{font-size:var(--text-base);font-weight:800;line-height:1.35;margin:0 0 .375rem}
  .course-meta{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem}
  .course-description{font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.55;margin:0 0 1rem;min-height:2.75rem}
  .course-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;border-top:1px solid var(--border);padding-top:.875rem}
  .course-stat{text-align:center}
  .course-stat__value{font-size:var(--text-lg);font-weight:800}
  .course-stat__label{font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.125rem}
  .course-card__footer{padding:.875rem 1.25rem;border-top:1px solid var(--border);display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
  .course-empty{grid-column:1/-1;padding:3.5rem 1.5rem;text-align:center;color:var(--muted-foreground)}
  .course-empty__icon{width:4rem;height:4rem;border-radius:999px;background:var(--muted);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--muted-foreground)}
  .filter-form{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap}
  .filter-left{display:flex;align-items:center;gap:.75rem;flex:1;flex-wrap:wrap}
  .search-wrap{position:relative;min-width:260px;flex:1;max-width:380px}
  .search-wrap svg{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted-foreground);pointer-events:none}
  .search-wrap input{padding-left:2.5rem!important;width:100%}
  .color-picker{display:flex;gap:.5rem;flex-wrap:wrap}
  .color-dot{width:1.875rem;height:1.875rem;border-radius:999px;border:2px solid transparent;box-shadow:0 0 0 1px var(--border);cursor:pointer;transition:transform var(--transition-fast),border-color var(--transition-fast)}
  .color-dot:hover,.color-dot.is-selected{transform:scale(1.08);border-color:var(--foreground)}
  .quick-actions{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:1.25rem}
  .quick-action{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:.875rem 1rem;display:flex;align-items:center;gap:.75rem;text-decoration:none;color:var(--foreground);transition:box-shadow var(--transition-fast),transform var(--transition-fast),border-color var(--transition-fast);font:inherit;text-align:left;cursor:pointer;width:100%}
  .quick-action:hover{box-shadow:var(--shadow-md);transform:translateY(-1px);border-color:color-mix(in srgb,var(--primary) 28%,var(--border))}
  .quick-action__icon{width:2.25rem;height:2.25rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:var(--muted);color:var(--primary);flex-shrink:0}
  .quick-action__title{font-weight:700;font-size:var(--text-sm);line-height:1.25}
  .quick-action__sub{font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.125rem}
  .course-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem}
  .course-actions form{display:inline-flex}
  .course-note{margin-top:.875rem;border:1px dashed var(--border);border-radius:var(--radius-md);padding:.625rem .75rem;font-size:var(--text-xs);color:var(--muted-foreground);line-height:1.5;background:color-mix(in srgb,var(--muted) 55%,transparent)}
  .course-updated{font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.75rem;display:flex;gap:.35rem;align-items:center;flex-wrap:wrap}
  @media (max-width:700px){
    .quick-actions{grid-template-columns:1fr}
    .filter-left,.filter-form{align-items:stretch}
    .search-wrap,.filter-left .input{max-width:none;width:100%}
    .course-card__footer .btn{flex:1;justify-content:center}
  }
  @media (min-width:701px) and (max-width:1100px){.quick-actions{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Khóa học</h1>
        <p style="color:var(--muted-foreground);">Quản lý nội dung giảng dạy, bài kiểm tra và bài tập theo từng lớp.</p>
      </div>
      <button class="btn btn-primary gap-2" type="button" onclick="openCreateModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tạo khóa học
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;"><span>{{ session('success') }}</span></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ session('error') }}</span></div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ $errors->first() }}</span></div>
  @endif

  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng khóa học</div>
      <div class="stat-card__value">{{ $courses->total() }}</div>
      <div class="stat-card__label">{{ $publishedCount }} đã xuất bản</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bản nháp</div>
      <div class="stat-card__value" style="{{ $draftCount ? 'color:var(--warning)' : '' }}">{{ $draftCount }}</div>
      <div class="stat-card__label">cần hoàn thiện</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Học sinh</div>
      <div class="stat-card__value">{{ $totalStudents }}</div>
      <div class="stat-card__label">đang tham gia</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Nội dung</div>
      <div class="stat-card__value">{{ $totalMaterials }}</div>
      <div class="stat-card__label">bài kiểm tra và bài tập</div>
    </div>
  </div>

  <div class="quick-actions stagger-children">
    <button class="quick-action" type="button" onclick="openCreateModal()">
      <span class="quick-action__icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      </span>
      <span><span class="quick-action__title">Tạo khóa học</span> <br><span class="quick-action__sub">Thiết lập nội dung theo lớp</span></span>
    </button>
    <a class="quick-action" href="{{ route('teacher.quiz-create') }}">
      <span class="quick-action__icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      </span>
      <span><span class="quick-action__title">Tạo bài kiểm tra</span> <br><span class="quick-action__sub">Gắn với khóa học khi cần</span></span>
    </a>
    <a class="quick-action" href="{{ route('teacher.assignments') }}">
      <span class="quick-action__icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
      </span>
      <span><span class="quick-action__title">Quản lý bài tập</span> <br><span class="quick-action__sub">Theo dõi giao và nộp bài</span></span>
    </a>
    <a class="quick-action" href="{{ route('teacher.classes') }}">
      <span class="quick-action__icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </span>
      <span><span class="quick-action__title">Quản lý lớp</span> <br><span class="quick-action__sub">Thêm học sinh vào lớp</span></span>
    </a>
  </div>

  <form class="toolbar filter-form stagger-children" method="GET" action="{{ route('teacher.courses') }}">
    <div class="filter-left">
      <div class="search-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" class="input" name="search" value="{{ request('search') }}" placeholder="Tìm khóa học, lớp, môn học..." style="font-size:var(--text-sm);" />
      </div>
      <select class="input select" name="class_id" style="font-size:var(--text-sm);max-width:220px;">
        <option value="">Tất cả lớp</option>
        @foreach($classes as $class)
          <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->name }}</option>
        @endforeach
      </select>
      <select class="input select" name="status" style="font-size:var(--text-sm);max-width:180px;">
        <option value="">Tất cả trạng thái</option>
        <option value="published" @selected(request('status') === 'published')>Đã xuất bản</option>
        <option value="draft" @selected(request('status') === 'draft')>Nháp</option>
      </select>
    </div>
    <div class="toolbar-right">
      <button class="btn btn-outline btn-sm" type="submit">Lọc</button>
      @if(request()->hasAny(['search', 'class_id', 'status']))
        <a class="btn btn-ghost btn-sm" href="{{ route('teacher.courses') }}">Xóa lọc</a>
      @endif
      <span style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $courses->total() }} khóa học</span>
    </div>
  </form>

  <div class="stagger-children" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;margin-top:1.25rem;">
    @forelse($courses as $index => $course)
      @php
        $color = preg_match('/^#[0-9A-Fa-f]{6}$/', $course->color ?? '') ? $course->color : $colors[$index % count($colors)];
        $status = $course->status ?: 'draft';
        $statusLabel = $statusLabels[$status] ?? 'Nháp';
        $class = $course->classModel;
        $subject = $class?->subject;
        $latestActivity = collect([$course->quizzes_max_created_at, $course->assignments_max_created_at, $course->updated_at])->filter()->max();
      @endphp
      <article class="course-card is-clickable" role="link" tabindex="0" data-detail-url="{{ route('teacher.courses.show', $course) }}" onclick="openCourseDetail(event, this)" onkeydown="openCourseDetailFromKey(event, this)">
        <div class="course-banner" style="background:linear-gradient(135deg, {{ $color }}, color-mix(in srgb, {{ $color }} 78%, #111827));">
          <div class="course-banner__icon">{{ mb_substr($course->name, 0, 1) }}</div>
          <span class="course-banner__status">{{ $statusLabel }}</span>
        </div>

        <div class="course-card__body">
          <h2 class="course-title">{{ $course->name }}</h2>
          <div class="course-meta">
            @if($class)
              <span class="badge badge-default">{{ $class->name }}</span>
            @else
              <span class="badge badge-outline">Chưa gắn lớp</span>
            @endif
            @if($subject)
              <span class="badge badge-outline">{{ $subject }}</span>
            @endif
          </div>

          <p class="course-description">{{ \Illuminate\Support\Str::limit($course->description, 120) ?: 'Chưa có mô tả cho khóa học này.' }}</p>

          <div class="course-stats">
            <div class="course-stat">
              <div class="course-stat__value">{{ $course->students_count }}</div>
              <div class="course-stat__label">Học sinh</div>
            </div>
            <div class="course-stat">
              <div class="course-stat__value">{{ $course->quizzes_count }}</div>
              <div class="course-stat__label">Bài kiểm tra</div>
            </div>
            <div class="course-stat">
              <div class="course-stat__value">{{ $course->assignments_count }}</div>
              <div class="course-stat__label">Bài tập</div>
            </div>
          </div>

          <div class="course-updated">
            <span>Cập nhật gần nhất:</span>
            <strong>{{ $latestActivity ? \Illuminate\Support\Carbon::parse($latestActivity)->diffForHumans() : 'Chưa có hoạt động' }}</strong>
          </div>

          @if(!$class)
            <div class="course-note">Khóa học chưa gắn với lớp nên chưa thể đồng bộ học sinh hoặc tạo bài tập nhanh. Hãy chọn lớp trong phần chỉnh sửa.</div>
          @elseif($course->students_count === 0)
            <div class="course-note">Khóa học đã gắn với {{ $class->name }}. Dùng nút đồng bộ để đưa học sinh của lớp vào khóa học.</div>
          @endif
        </div>

        <div class="course-card__footer" onclick="event.stopPropagation()">
          <button class="btn btn-outline btn-sm" type="button" onclick="openEditModal({{ $course->id }})">Chỉnh sửa</button>
          <a class="btn btn-primary btn-sm" href="{{ route('teacher.quiz-create', ['course_id' => $course->id, 'class_id' => $course->class_id]) }}">Tạo bài kiểm tra</a>
          @if($class)
            <button class="btn btn-outline btn-sm" type="button" onclick="openAssignmentModal({{ $course->id }})">Tạo bài tập</button>
            <form method="POST" action="{{ route('teacher.courses.sync-students', $course) }}">
              @csrf
              <button class="btn btn-ghost btn-sm" type="submit">Đồng bộ học sinh</button>
            </form>
          @endif
          @if($status === 'published')
            <form method="POST" action="{{ route('teacher.courses.unpublish', $course) }}">
              @csrf
              <button class="btn btn-ghost btn-sm" type="submit">Đưa về nháp</button>
            </form>
          @else
            <form method="POST" action="{{ route('teacher.courses.publish', $course) }}">
              @csrf
              <button class="btn btn-ghost btn-sm" type="submit">Xuất bản</button>
            </form>
          @endif
          <form method="POST" action="{{ route('teacher.courses.duplicate', $course) }}">
            @csrf
            <button class="btn btn-ghost btn-sm" type="submit">Nhân bản</button>
          </form>
          <button class="btn btn-ghost btn-sm" type="button" style="margin-left:auto;color:var(--destructive);" onclick="openDeleteModal({{ $course->id }})">Xóa</button>
        </div>
      </article>
    @empty
      <div class="course-empty">
        <div class="course-empty__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>
        </div>
        <h3 style="font-size:var(--text-xl);font-weight:700;color:var(--foreground);margin-bottom:.375rem;">Chưa có khóa học phù hợp</h3>
        <p style="margin:0 0 1rem;">Tạo khóa học mới hoặc thay đổi bộ lọc để xem thêm kết quả.</p>
        <button class="btn btn-primary" type="button" onclick="openCreateModal()">Tạo khóa học</button>
      </div>
    @endforelse
  </div>

  {{ $courses->links('components.pagination') }}

  <div class="modal-overlay" id="create-modal">
    <div class="modal" style="max-width:38rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Tạo khóa học</h3>
          <p class="modal-desc">Điền thông tin để tổ chức nội dung giảng dạy cho một lớp.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeModal('create-modal')">×</button>
      </div>
      <form method="POST" action="{{ route('teacher.courses.store') }}">
        @csrf
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-group">
            <label class="label label-required" for="create-name">Tên khóa học</label>
            <input type="text" id="create-name" name="name" class="input" value="{{ old('name') }}" placeholder="VD: Toán Đại số 10" required />
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="label" for="create-class">Lớp học</label>
              <select id="create-class" name="class_id" class="input select">
                <option value="">Chưa gắn lớp</option>
                @foreach($classes as $class)
                  <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->name }}{{ $class->subject ? ' - ' . $class->subject : '' }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="label" for="create-status">Trạng thái</label>
              <select id="create-status" name="status" class="input select">
                <option value="draft" @selected(old('status', 'draft') === 'draft')>Nháp</option>
                <option value="published" @selected(old('status') === 'published')>Đã xuất bản</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="label" for="create-description">Mô tả</label>
            <textarea id="create-description" name="description" class="input" style="min-height:5.5rem;resize:vertical;" placeholder="Mục tiêu, nội dung hoặc ghi chú cho khóa học...">{{ old('description') }}</textarea>
          </div>
          <div class="form-group">
            <label class="label">Màu khóa học</label>
            <input type="hidden" name="color" id="create-color" value="{{ old('color', $colors[0]) }}">
            <div class="color-picker" data-target="create-color">
              @foreach($colors as $color)
                <button type="button" class="color-dot {{ old('color', $colors[0]) === $color ? 'is-selected' : '' }}" style="background:{{ $color }};" data-color="{{ $color }}" aria-label="Chọn màu {{ $color }}"></button>
              @endforeach
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('create-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Tạo khóa học</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="edit-modal">
    <div class="modal" style="max-width:38rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Chỉnh sửa khóa học</h3>
          <p class="modal-desc" id="edit-desc"></p>
        </div>
        <button class="modal-close" type="button" onclick="closeModal('edit-modal')">×</button>
      </div>
      <form method="POST" id="edit-form">
        @csrf
        @method('PUT')
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-group">
            <label class="label label-required" for="edit-name">Tên khóa học</label>
            <input type="text" id="edit-name" name="name" class="input" required />
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="label" for="edit-class">Lớp học</label>
              <select id="edit-class" name="class_id" class="input select">
                <option value="">Chưa gắn lớp</option>
                @foreach($classes as $class)
                  <option value="{{ $class->id }}">{{ $class->name }}{{ $class->subject ? ' - ' . $class->subject : '' }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="label" for="edit-status">Trạng thái</label>
              <select id="edit-status" name="status" class="input select">
                <option value="draft">Nháp</option>
                <option value="published">Đã xuất bản</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="label" for="edit-description">Mô tả</label>
            <textarea id="edit-description" name="description" class="input" style="min-height:5.5rem;resize:vertical;"></textarea>
          </div>
          <div class="form-group">
            <label class="label">Màu khóa học</label>
            <input type="hidden" name="color" id="edit-color" value="{{ $colors[0] }}">
            <div class="color-picker" data-target="edit-color">
              @foreach($colors as $color)
                <button type="button" class="color-dot" style="background:{{ $color }};" data-color="{{ $color }}" aria-label="Chọn màu {{ $color }}"></button>
              @endforeach
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('edit-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="assignment-modal">
    <div class="modal" style="max-width:38rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Tạo bài tập nhanh</h3>
          <p class="modal-desc" id="assignment-desc">Bài tập sẽ được gắn với khóa học và lớp đã chọn.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeModal('assignment-modal')">×</button>
      </div>
      <form method="POST" action="{{ route('teacher.assignments.store') }}">
        @csrf
        <input type="hidden" id="assignment-class-id" name="class_id">
        <input type="hidden" id="assignment-course-id" name="course_id">
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-group">
            <label class="label label-required" for="assignment-title">Tiêu đề bài tập</label>
            <input type="text" id="assignment-title" name="title" class="input" placeholder="VD: Bài tập ôn chương 1" required />
          </div>
          <div class="form-group">
            <label class="label" for="assignment-description">Mô tả</label>
            <textarea id="assignment-description" name="description" class="input" style="min-height:5rem;resize:vertical;" placeholder="Hướng dẫn làm bài, yêu cầu nộp bài..."></textarea>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="label" for="assignment-type">Loại bài tập</label>
              <select id="assignment-type" name="type" class="input select">
                <option value="essay">Tự luận</option>
                <option value="practice">Thực hành</option>
                <option value="project">Dự án</option>
                <option value="code">Lập trình</option>
              </select>
            </div>
            <div class="form-group">
              <label class="label" for="assignment-points">Điểm tối đa</label>
              <input type="number" id="assignment-points" name="total_points" class="input" min="1" max="10000" value="100" />
            </div>
          </div>
          <div class="form-group">
            <label class="label" for="assignment-due">Hạn nộp</label>
            <input type="datetime-local" id="assignment-due" name="due_at" class="input" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('assignment-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Tạo bài tập</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="delete-modal">
    <div class="modal" style="max-width:28rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Xóa khóa học?</h3>
          <p class="modal-desc" id="delete-desc"></p>
        </div>
        <button class="modal-close" type="button" onclick="closeModal('delete-modal')">×</button>
      </div>
      <div class="modal-body">
        <p style="font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.6;margin:0;">Khóa học sẽ được đưa vào thùng rác. Các nội dung liên quan vẫn có thể được khôi phục nếu hệ thống hỗ trợ khôi phục dữ liệu đã xóa.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('delete-modal')">Hủy</button>
        <form method="POST" id="delete-form" style="display:inline;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-destructive">Xóa khóa học</button>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
(function() {
  'use strict';

  const courses = @json($coursesData);
  const openModalFromServer = @json($openModal ?? null);
  const editCourseId = @json($editCourseId ?? null);
  const hasErrors = @json($errors->any());

  function findCourse(id) {
    return courses.find(item => Number(item.id) === Number(id));
  }

  window.openCourseDetail = function(event, card) {
    if (event.target.closest('a,button,form,input,select,textarea,label')) return;
    const url = card.dataset.detailUrl;
    if (url) window.location.href = url;
  };

  window.openCourseDetailFromKey = function(event, card) {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    event.preventDefault();
    const url = card.dataset.detailUrl;
    if (url) window.location.href = url;
  };

  function selectColor(targetId, color) {
    const input = document.getElementById(targetId);
    if (!input) return;
    input.value = color;

    document.querySelectorAll('.color-picker[data-target="' + targetId + '"] .color-dot').forEach(button => {
      button.classList.toggle('is-selected', button.dataset.color === color);
    });
  }

  window.openModal = function(id) {
    document.getElementById(id)?.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  window.closeModal = function(id) {
    document.getElementById(id)?.classList.remove('open');
    document.body.style.overflow = '';
  };

  window.openCreateModal = function() {
    openModal('create-modal');
    setTimeout(() => document.getElementById('create-name')?.focus(), 100);
  };

  window.openEditModal = function(id) {
    const item = findCourse(id);
    if (!item) return;

    document.getElementById('edit-desc').textContent = 'Cập nhật thông tin và trạng thái khóa học.';
    document.getElementById('edit-name').value = item.name || '';
    document.getElementById('edit-class').value = item.class_id || '';
    document.getElementById('edit-status').value = item.status || 'draft';
    document.getElementById('edit-description').value = item.description || '';
    document.getElementById('edit-form').action = item.update_url;
    selectColor('edit-color', item.color || '#2563eb');
    openModal('edit-modal');
  };

  window.openDeleteModal = function(id) {
    const item = findCourse(id);
    if (!item) return;

    document.getElementById('delete-desc').textContent = 'Khóa học: ' + item.name;
    document.getElementById('delete-form').action = item.delete_url;
    openModal('delete-modal');
  };

  window.openAssignmentModal = function(id) {
    const item = findCourse(id);
    if (!item || !item.class_id) return;

    document.getElementById('assignment-course-id').value = item.id;
    document.getElementById('assignment-class-id').value = item.class_id;
    document.getElementById('assignment-desc').textContent = 'Khóa học: ' + item.name + (item.class_name ? ' · Lớp: ' + item.class_name : '');
    document.getElementById('assignment-title').value = '';
    document.getElementById('assignment-description').value = '';
    document.getElementById('assignment-type').value = 'essay';
    document.getElementById('assignment-points').value = '100';
    document.getElementById('assignment-due').value = '';
    openModal('assignment-modal');
    setTimeout(() => document.getElementById('assignment-title')?.focus(), 100);
  };

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

  document.addEventListener('DOMContentLoaded', function() {
    if (openModalFromServer === 'edit-modal' && editCourseId) {
      openEditModal(editCourseId);
      return;
    }

    if (openModalFromServer === 'create-modal' || (hasErrors && !openModalFromServer)) {
      openCreateModal();
    }
  });
})();
</script>
@endpush
