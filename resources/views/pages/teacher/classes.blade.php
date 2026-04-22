{{-- Teacher: classes --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.class-card {
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      background: var(--card);
      overflow: hidden;
      transition: all var(--transition-fast);
      display: flex;
      flex-direction: column;
    }
    .class-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
    .class-card-header {
      padding: 1.25rem 1.25rem 1rem;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: .75rem;
    }
    .class-icon {
      width: 3rem; height: 3rem;
      border-radius: var(--radius-md);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1.25rem; font-weight: 700;
      flex-shrink: 0;
    }
    .class-card-body { padding: 0 1.25rem 1rem; flex: 1; }
    .class-stat-row {
      display: grid; grid-template-columns: repeat(3,1fr);
      gap: .5rem; margin-top: .875rem;
      border-top: 1px solid var(--border); padding-top: .875rem;
    }
    .class-stat { text-align: center; }
    .class-stat-val { font-size: var(--text-lg); font-weight: 700; }
    .class-stat-lbl { font-size: var(--text-xs); color: var(--muted-foreground); margin-top: .125rem; }
    .class-card-footer {
      padding: .875rem 1.25rem;
      border-top: 1px solid var(--border);
      display: flex; gap: .5rem; align-items: center;
    }
    .class-code-badge {
      font-family: monospace;
      font-size: var(--text-xs);
      background: var(--muted);
      border: 1px solid var(--border);
      padding: .2rem .6rem;
      border-radius: var(--radius-sm);
      color: var(--muted-foreground);
      cursor: pointer;
      transition: background var(--transition-fast);
      margin-left: auto;
    }
    .class-code-badge:hover { background: var(--border); color: var(--foreground); }
    .empty-grid {
      grid-column: 1/-1;
      padding: 3rem;
      text-align: center;
      color: var(--muted-foreground);
    }
</style>
@endpush

@section('content')
  <!-- Page header -->
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Lớp của Tôi</h1>
        <p style="color:var(--muted-foreground);">Quản lý lớp học, theo dõi tiến độ và hiệu suất học sinh</p>
      </div>
      <button class="btn btn-primary gap-2" onclick="openModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tạo Lớp mới
      </button>
    </div>
  </div>

  <!-- Flash Messages -->
  @if(session('success'))
  <div class="alert alert-success" style="margin-bottom:1rem;">
    <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    <span>{{ session('success') }}</span>
  </div>
  @endif

  @if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:1rem;">
    <ul style="margin:0;padding-left:1rem;">
      @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <!-- Stats -->
  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng số lớp</div>
      <div class="stat-card__value">{{ $classes->count() }}</div>
      <div class="stat-card__label">lớp đang hoạt động</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng học sinh</div>
      <div class="stat-card__value">{{ $classes->sum('students_count') }}</div>
      <div class="stat-card__label">đang tham gia</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài thi đã giao</div>
      <div class="stat-card__value">{{ $classes->sum(fn($c) => $c->assignments->count()) }}</div>
      <div class="stat-card__label">trong học kỳ này</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Khóa học</div>
      <div class="stat-card__value">{{ $classes->sum(fn($c) => $c->courses->count()) }}</div>
      <div class="stat-card__label">đang mở</div>
    </div>
  </div>

  <!-- Cards grid -->
  <div class="stagger-children" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;">
    @php
      $colors = ['#3b82f6','#f97316','#22c55e','#a855f7','#ef4444','#06b6d4','#eab308','#ec4899'];
    @endphp

    @forelse($classes as $i => $class)
    <div class="class-card">
      <div class="class-card-header">
        <div style="display:flex;align-items:center;gap:.75rem;">
          <div class="class-icon" style="background:{{ $colors[$i % count($colors)] }};">
            {{ mb_substr($class->name, 0, 2) }}
          </div>
          <div>
            <h3 style="font-size:var(--text-base);font-weight:700;">{{ $class->name }}</h3>
            <span class="badge badge-default" style="margin-top:.25rem;">{{ $class->subject ?? 'Chưa phân loại' }}</span>
          </div>
        </div>
        <div class="dropdown" style="position:relative;">
          <button class="icon-btn" onclick="this.nextElementSibling.classList.toggle('open')" style="width:2rem;height:2rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
          </button>
          <div class="dropdown-menu" role="menu" style="right:0;min-width:140px;">
            <a href="{{ route('teacher.class-detail', $class) }}" class="dropdown-item">Chi tiết</a>
            <div class="dropdown-separator"></div>
            <form method="POST" action="{{ route('teacher.classes.destroy', $class) }}" onsubmit="return confirm('Xóa lớp {{ $class->name }}?')">
              @csrf @method('DELETE')
              <button type="submit" class="dropdown-item danger" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">Xóa</button>
            </form>
          </div>
        </div>
      </div>
      <div class="class-card-body">
        <p style="font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.5;">{{ Str::limit($class->description, 80) ?: 'Chưa có mô tả' }}</p>
        <div class="class-stat-row">
          <div class="class-stat">
            <div class="class-stat-val">{{ $class->students_count }}</div>
            <div class="class-stat-lbl">Học sinh</div>
          </div>
          <div class="class-stat">
            <div class="class-stat-val">{{ $class->assignments_count ?? $class->assignments()->count() }}</div>
            <div class="class-stat-lbl">Bài thi</div>
          </div>
          <div class="class-stat">
            <div class="class-stat-val">{{ $class->grade_level ?? '—' }}</div>
            <div class="class-stat-lbl">Khối</div>
          </div>
        </div>
      </div>
      <div class="class-card-footer">
        <a href="{{ route('teacher.class-detail', $class) }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;">Xem lớp</a>
        <span class="class-code-badge" title="Mã lớp: {{ $class->code }}" onclick="navigator.clipboard.writeText('{{ $class->code }}');this.textContent='Đã sao!'">{{ $class->code }}</span>
      </div>
    </div>
    @empty
    <div class="empty-grid">
      <div style="font-size:3rem;margin-bottom:.75rem;">🏫</div>
      <h3 style="font-size:var(--text-xl);font-weight:600;margin-bottom:.375rem;color:var(--foreground);">Chưa có lớp học nào</h3>
      <p>Tạo lớp mới để bắt đầu quản lý học sinh</p>
    </div>
    @endforelse
  </div>

  <!-- Create Class Modal -->
  <div class="modal-overlay" id="class-modal">
    <div class="modal" style="max-width:34rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Tạo Lớp Mới</h3>
          <p class="modal-desc">Điền thông tin để tạo lớp học</p>
        </div>
        <button class="modal-close" onclick="closeModal()">✕</button>
      </div>
      <form method="POST" action="{{ route('teacher.classes.store') }}">
        @csrf
        <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group" style="grid-column:1/-1;">
              <label class="label label-required">Tên Lớp</label>
              <input type="text" name="name" class="input" placeholder="VD: Lớp 12A1" required />
            </div>
            <div class="form-group">
              <label class="label">Môn học</label>
              <select name="subject" class="input select">
                <option value="">-- Chọn môn --</option>
                <option>Toán học</option><option>Vật lý</option><option>Hóa học</option>
                <option>Sinh học</option><option>Ngữ văn</option><option>Lịch sử</option>
                <option>Địa lý</option><option>Tiếng Anh</option><option>Tin học</option>
              </select>
            </div>
            <div class="form-group">
              <label class="label">Khối lớp</label>
              <input type="text" name="grade_level" class="input" placeholder="VD: 10, 11, 12" />
            </div>
            <div class="form-group" style="grid-column:1/-1;">
              <label class="label">Mô tả lớp học</label>
              <textarea name="description" class="input" style="min-height:4rem;" placeholder="Mô tả ngắn về lớp học..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal()">Hủy</button>
          <button type="submit" class="btn btn-primary">Tạo Lớp</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  window.openModal = function() {
    document.getElementById('class-modal').classList.add('open');
  };
  window.closeModal = function() {
    document.getElementById('class-modal').classList.remove('open');
  };

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    document.querySelectorAll('.dropdown-menu.open').forEach(function(m) {
      if (!m.parentElement.contains(e.target)) m.classList.remove('open');
    });
  });
</script>
@endpush
