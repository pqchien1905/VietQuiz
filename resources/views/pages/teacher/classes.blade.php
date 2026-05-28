{{-- Teacher: classes --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $colors = ['#2563eb', '#ea580c', '#16a34a', '#7c3aed', '#dc2626', '#0891b2', '#ca8a04', '#db2777'];
  $subjectsList = ['Toán học','Vật lý','Hóa học','Sinh học','Ngữ văn','Lịch sử','Địa lý','Tiếng Anh','Tin học','GDCD','Công nghệ','Nghệ thuật'];
  $classesForJs = $classes->getCollection()->map(fn ($class) => [
      'id' => $class->id,
      'name' => $class->name,
      'subject' => $class->subject ?? '',
      'description' => $class->description ?? '',
      'grade_level' => $class->grade_level ?? '',
      'code' => $class->code,
      'update_url' => route('teacher.classes.update', $class),
      'notify_url' => route('teacher.classes.notify', $class),
      'delete_url' => route('teacher.classes.destroy', $class),
  ])->values();
@endphp

@push('styles')
<style>
  .class-card{border:1px solid var(--border);border-radius:var(--radius-xl);background:var(--card);display:flex;flex-direction:column;overflow:visible;transition:box-shadow var(--transition-fast),transform var(--transition-fast),border-color var(--transition-fast)}
  .class-card.is-clickable{cursor:pointer}
  .class-card.is-clickable:focus{outline:2px solid var(--primary);outline-offset:3px}
  .class-card:hover{box-shadow:var(--shadow-lg);transform:translateY(-2px);border-color:color-mix(in srgb,var(--primary) 28%,var(--border))}
  .class-card__header{padding:1.25rem 1.25rem 1rem;display:flex;align-items:flex-start;justify-content:space-between;gap:.875rem}
  .class-card__main{display:flex;align-items:flex-start;gap:.875rem;min-width:0}
  .class-icon{width:3rem;height:3rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;font-weight:800;flex-shrink:0}
  .class-card__title{font-size:var(--text-base);font-weight:800;line-height:1.3;margin:0 0 .25rem}
  .class-card__meta{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
  .class-status{display:inline-flex;align-items:center;gap:.25rem;font-size:var(--text-xs);font-weight:600;padding:.15rem .5rem;border-radius:999px}
  .class-status::before{content:'';width:.375rem;height:.375rem;border-radius:999px;background:currentColor}
  .class-status.active{background:color-mix(in srgb,var(--success) 14%,transparent);color:var(--success)}
  .class-status.archived{background:var(--muted);color:var(--muted-foreground)}
  .class-card__body{padding:0 1.25rem 1rem;flex:1}
  .class-description{font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.55;margin:0 0 .875rem;min-height:2.75rem}
  .class-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;border-top:1px solid var(--border);padding-top:.875rem}
  .class-stat{text-align:center}
  .class-stat__value{font-size:var(--text-lg);font-weight:800}
  .class-stat__label{font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.125rem}
  .class-card__footer{padding:.875rem 1.25rem;border-top:1px solid var(--border);display:flex;gap:.5rem;align-items:center}
  .class-code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:var(--text-xs);background:var(--muted);border:1px solid var(--border);padding:.2rem .6rem;border-radius:var(--radius-sm);color:var(--muted-foreground);cursor:pointer;transition:background var(--transition-fast);margin-left:auto}
  .class-code:hover{background:var(--border);color:var(--foreground)}
  .class-empty{grid-column:1/-1;padding:3.5rem 1.5rem;text-align:center;color:var(--muted-foreground)}
  .class-empty__icon{width:4rem;height:4rem;border-radius:999px;background:var(--muted);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--muted-foreground)}
  .filter-form{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap}
  .filter-left{display:flex;align-items:center;gap:.75rem;flex:1;flex-wrap:wrap}
  .search-wrap{position:relative;min-width:260px;flex:1;max-width:360px}
  .search-wrap svg{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted-foreground);pointer-events:none}
  .search-wrap input{padding-left:2.5rem!important;width:100%}
  @media (max-width:700px){
    .filter-left,.filter-form{align-items:stretch}
    .search-wrap,.filter-left .input{max-width:none;width:100%}
    .class-card__footer{flex-wrap:wrap}
  }
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Lớp của tôi</h1>
        <p style="color:var(--muted-foreground);">Quản lý lớp học, theo dõi học sinh và hiệu suất các bài kiểm tra.</p>
      </div>
      <button class="btn btn-primary gap-2" type="button" onclick="openCreateModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tạo lớp mới
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;"><span>{{ session('success') }}</span></div>
  @endif
  @if(session('warning'))
    <div class="alert alert-warning" style="margin-bottom:1rem;"><span>{{ session('warning') }}</span></div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ session('error') }}</span></div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ $errors->first() }}</span></div>
  @endif

  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng số lớp</div>
      <div class="stat-card__value">{{ $classes->total() }}</div>
      <div class="stat-card__label">{{ $activeCount }} đang hoạt động</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng học sinh</div>
      <div class="stat-card__value">{{ $totalStudents }}</div>
      <div class="stat-card__label">đang tham gia</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài thi đã giao</div>
      <div class="stat-card__value">{{ $totalQuizzes }}</div>
      <div class="stat-card__label">đã xuất bản</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB các lớp</div>
      <div class="stat-card__value" style="{{ $overallAvg ? 'color:var(--success)' : 'color:var(--muted-foreground)' }}">{{ $overallAvg ? $overallAvg . '%' : '—' }}</div>
      <div class="stat-card__label">{{ $archivedCount }} lớp lưu trữ</div>
    </div>
  </div>

  <form class="toolbar filter-form stagger-children" method="GET" action="{{ route('teacher.classes') }}">
    <div class="filter-left">
      <div class="search-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" class="input" name="search" value="{{ request('search') }}" placeholder="Tìm lớp, mã lớp, môn học..." style="font-size:var(--text-sm);" />
      </div>
      <select class="input select" name="subject" style="font-size:var(--text-sm);max-width:180px;">
        <option value="">Tất cả môn</option>
        @foreach($subjects as $subject)
          <option value="{{ $subject }}" @selected(request('subject') === $subject)>{{ $subject }}</option>
        @endforeach
      </select>
      <select class="input select" name="status" style="font-size:var(--text-sm);max-width:180px;">
        <option value="">Tất cả trạng thái</option>
        <option value="active" @selected(request('status') === 'active')>Hoạt động</option>
        <option value="archived" @selected(request('status') === 'archived')>Đã lưu trữ</option>
      </select>
    </div>
    <div class="toolbar-right">
      <button class="btn btn-outline btn-sm" type="submit">Lọc</button>
      @if(request()->hasAny(['search', 'subject', 'status']))
        <a class="btn btn-ghost btn-sm" href="{{ route('teacher.classes') }}">Xóa lọc</a>
      @endif
      <span style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $classes->total() }} lớp</span>
    </div>
  </form>

  <div class="stagger-children" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;margin-top:1.25rem;">
    @forelse($classes as $index => $class)
      @php
        $color = $colors[$index % count($colors)];
        $status = $class->status ?? 'active';
      @endphp
      <article class="class-card is-clickable" role="link" tabindex="0" data-detail-url="{{ route('teacher.class-detail', $class) }}" onclick="openClassDetail(event, this)" onkeydown="openClassDetailFromKey(event, this)">
        <div class="class-card__header">
          <div class="class-card__main">
            <div class="class-icon" style="background:{{ $color }};">{{ mb_substr($class->name, 0, 1) }}</div>
            <div style="min-width:0;">
              <h2 class="class-card__title">{{ $class->name }}</h2>
              <div class="class-card__meta">
                @if($class->subject)
                  <span class="badge badge-default">{{ $class->subject }}</span>
                @endif
                @if($class->grade_level)
                  <span class="badge badge-outline">Khối {{ $class->grade_level }}</span>
                @endif
                <span class="class-status {{ $status }}">{{ $status === 'active' ? 'Hoạt động' : 'Lưu trữ' }}</span>
              </div>
            </div>
          </div>

          <div class="dropdown" style="position:relative;z-index:20;" onclick="event.stopPropagation()">
            <button class="icon-btn" type="button" onclick="event.stopPropagation();toggleDropdown(this)" aria-label="Mở menu lớp" style="width:2rem;height:2rem;">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
            </button>
            <div class="dropdown-menu" role="menu" style="right:0;min-width:12rem;z-index:20;">
              <a class="dropdown-item" href="{{ route('teacher.class-detail', $class) }}">Xem lớp</a>
              <button class="dropdown-item" type="button" onclick="openEditModal({{ $class->id }})">Chỉnh sửa</button>
              @if($status === 'active')
                <button class="dropdown-item" type="button" onclick="openNotifyModal({{ $class->id }})">Gửi thông báo</button>
                <form method="POST" action="{{ route('teacher.classes.archive', $class) }}" style="display:contents;">
                  @csrf
                  <button class="dropdown-item" type="submit">Lưu trữ lớp</button>
                </form>
              @else
                <form method="POST" action="{{ route('teacher.classes.restore', $class) }}" style="display:contents;">
                  @csrf
                  <button class="dropdown-item" type="submit">Khôi phục lớp</button>
                </form>
              @endif
              <div class="dropdown-separator"></div>
              <button class="dropdown-item danger" type="button" onclick="openDeleteModal({{ $class->id }})">Xóa lớp</button>
            </div>
          </div>
        </div>

        <div class="class-card__body">
          <p class="class-description">{{ \Illuminate\Support\Str::limit($class->description, 110) ?: 'Chưa có mô tả cho lớp học này.' }}</p>
          <div class="class-stats">
            <div class="class-stat">
              <div class="class-stat__value">{{ $class->students_count }}</div>
              <div class="class-stat__label">Học sinh</div>
            </div>
            <div class="class-stat">
              <div class="class-stat__value">{{ $class->published_quizzes_count ?? 0 }}</div>
              <div class="class-stat__label">Bài thi</div>
            </div>
            <div class="class-stat">
              <div class="class-stat__value" style="{{ $class->avg_score ? 'color:var(--success)' : 'color:var(--muted-foreground)' }}">{{ $class->avg_score ? $class->avg_score . '%' : '—' }}</div>
              <div class="class-stat__label">Điểm TB</div>
            </div>
          </div>
        </div>

        <div class="class-card__footer" onclick="event.stopPropagation()">
          <a href="{{ route('teacher.class-detail', $class) }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;">Xem lớp</a>
          <a href="{{ route('teacher.quiz-create', ['class_id' => $class->id]) }}" class="btn btn-primary btn-sm" style="flex:1;justify-content:center;">Giao bài</a>
          <button class="class-code" type="button" data-code="{{ $class->code }}" onclick="copyClassCode(this)" title="Sao chép mã lớp">{{ $class->code }}</button>
        </div>
      </article>
    @empty
      <div class="class-empty">
        <div class="class-empty__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
        <h3 style="font-size:var(--text-xl);font-weight:700;color:var(--foreground);margin-bottom:.375rem;">Chưa có lớp học phù hợp</h3>
        <p style="margin:0 0 1rem;">Tạo lớp mới hoặc thay đổi bộ lọc để xem thêm kết quả.</p>
        <button class="btn btn-primary" type="button" onclick="openCreateModal()">Tạo lớp mới</button>
      </div>
    @endforelse
  </div>

  {{ $classes->links('components.pagination') }}

  <div class="modal-overlay" id="create-modal">
    <div class="modal" style="max-width:36rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Tạo lớp mới</h3>
          <p class="modal-desc">Điền thông tin cơ bản để tạo lớp học.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeModal('create-modal')">×</button>
      </div>
      <form method="POST" action="{{ route('teacher.classes.store') }}">
        @csrf
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-group">
            <label class="label label-required" for="create-name">Tên lớp</label>
            <input type="text" id="create-name" name="name" class="input @error('name') input-error @enderror" placeholder="VD: Lớp 10A1" value="{{ old('name') }}" required />
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="label" for="create-subject">Môn học</label>
              <select id="create-subject" name="subject" class="input select">
                <option value="">Chọn môn</option>
                @foreach($subjectsList as $subject)
                  <option value="{{ $subject }}" @selected(old('subject') === $subject)>{{ $subject }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="label" for="create-grade">Khối lớp</label>
              <input type="text" id="create-grade" name="grade_level" class="input" placeholder="VD: 10, 11, 12" value="{{ old('grade_level') }}" />
            </div>
          </div>
          <div class="form-group">
            <label class="label" for="create-description">Mô tả</label>
            <textarea id="create-description" name="description" class="input" style="min-height:5rem;resize:vertical;" placeholder="Mục tiêu, nội dung hoặc ghi chú cho lớp...">{{ old('description') }}</textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('create-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Tạo lớp</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="edit-modal">
    <div class="modal" style="max-width:36rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Chỉnh sửa lớp</h3>
          <p class="modal-desc" id="edit-desc"></p>
        </div>
        <button class="modal-close" type="button" onclick="closeModal('edit-modal')">×</button>
      </div>
      <form method="POST" id="edit-form">
        @csrf
        @method('PUT')
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-group">
            <label class="label label-required" for="edit-name">Tên lớp</label>
            <input type="text" id="edit-name" name="name" class="input" required />
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="label" for="edit-subject">Môn học</label>
              <select id="edit-subject" name="subject" class="input select">
                <option value="">Chọn môn</option>
                @foreach($subjectsList as $subject)
                  <option value="{{ $subject }}">{{ $subject }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="label" for="edit-grade">Khối lớp</label>
              <input type="text" id="edit-grade" name="grade_level" class="input" />
            </div>
          </div>
          <div class="form-group">
            <label class="label" for="edit-description">Mô tả</label>
            <textarea id="edit-description" name="description" class="input" style="min-height:5rem;resize:vertical;"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('edit-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="notify-modal">
    <div class="modal" style="max-width:36rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Gửi thông báo lớp</h3>
          <p class="modal-desc" id="notify-desc"></p>
        </div>
        <button class="modal-close" type="button" onclick="closeModal('notify-modal')">×</button>
      </div>
      <form method="POST" id="notify-form">
        @csrf
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-group">
            <label class="label label-required" for="notify-title">Tiêu đề</label>
            <input type="text" id="notify-title" name="title" class="input" placeholder="VD: Nhắc lịch làm bài kiểm tra" required />
          </div>
          <div class="form-group">
            <label class="label label-required" for="notify-body">Nội dung</label>
            <textarea id="notify-body" name="body" class="input" style="min-height:6rem;resize:vertical;" placeholder="Nhập nội dung thông báo..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('notify-modal')">Hủy</button>
          <button type="submit" class="btn btn-primary">Gửi thông báo</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="delete-modal">
    <div class="modal" style="max-width:28rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Xóa lớp?</h3>
          <p class="modal-desc" id="delete-desc"></p>
        </div>
        <button class="modal-close" type="button" onclick="closeModal('delete-modal')">×</button>
      </div>
      <div class="modal-body">
        <p style="font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.6;margin:0;">Lớp sẽ được đưa vào thùng rác. Bạn có thể khôi phục trong trang thùng rác nếu cần.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('delete-modal')">Hủy</button>
        <form method="POST" id="delete-form" style="display:inline;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-destructive">Xóa lớp</button>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
(function() {
  'use strict';

  const classes = @json($classesForJs);
  const openModalFromServer = @json($openModal ?? null);
  const editClassId = @json($editClassId ?? null);

  function findClass(id) {
    return classes.find(item => Number(item.id) === Number(id));
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
  };

  window.openEditModal = function(id) {
    const item = findClass(id);
    if (!item) return;
    document.getElementById('edit-desc').textContent = 'Mã lớp: ' + item.code;
    document.getElementById('edit-name').value = item.name || '';
    document.getElementById('edit-subject').value = item.subject || '';
    document.getElementById('edit-grade').value = item.grade_level || '';
    document.getElementById('edit-description').value = item.description || '';
    document.getElementById('edit-form').action = item.update_url;
    closeAllDropdowns();
    openModal('edit-modal');
  };

  window.openNotifyModal = function(id) {
    const item = findClass(id);
    if (!item) return;
    document.getElementById('notify-desc').textContent = 'Gửi đến tất cả học sinh trong lớp: ' + item.name;
    document.getElementById('notify-form').action = item.notify_url;
    closeAllDropdowns();
    openModal('notify-modal');
  };

  window.openDeleteModal = function(id) {
    const item = findClass(id);
    if (!item) return;
    document.getElementById('delete-desc').textContent = 'Lớp: ' + item.name;
    document.getElementById('delete-form').action = item.delete_url;
    closeAllDropdowns();
    openModal('delete-modal');
  };

  window.openClassDetail = function(event, card) {
    if (event.target.closest('a, button, form, input, select, textarea, .dropdown, .modal-overlay')) return;
    const url = card.dataset.detailUrl;
    if (url) window.location.href = url;
  };

  window.openClassDetailFromKey = function(event, card) {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    if (event.target.closest('a, button, form, input, select, textarea, .dropdown, .modal-overlay')) return;
    event.preventDefault();
    const url = card.dataset.detailUrl;
    if (url) window.location.href = url;
  };

  window.toggleDropdown = function(button) {
    const menu = button.nextElementSibling;
    const isOpen = menu?.classList.contains('open');
    closeAllDropdowns();
    if (menu && !isOpen) menu.classList.add('open');
  };

  window.closeAllDropdowns = function() {
    document.querySelectorAll('.dropdown-menu.open').forEach(menu => menu.classList.remove('open'));
  };

  window.copyClassCode = function(button) {
    const code = button.dataset.code || button.textContent.trim();
    const done = function() {
      const old = button.textContent;
      button.textContent = 'Đã sao!';
      setTimeout(() => button.textContent = old, 1400);
    };

    if (navigator.clipboard) {
      navigator.clipboard.writeText(code).then(done).catch(done);
      return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = code;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
    done();
  };

  document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) closeAllDropdowns();
  });

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.modal-overlay.open').forEach(overlay => overlay.classList.remove('open'));
    closeAllDropdowns();
    document.body.style.overflow = '';
  });

  document.addEventListener('DOMContentLoaded', function() {
    if (openModalFromServer === 'edit-modal' && editClassId) {
      openEditModal(editClassId);
    } else if (openModalFromServer === 'create-modal' || (@json($errors->any()) && !openModalFromServer)) {
      openCreateModal();
    }
  });
})();
</script>
@endpush
