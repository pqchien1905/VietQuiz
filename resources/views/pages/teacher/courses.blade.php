{{-- Teacher: courses --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.course-card {
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      background: var(--card);
      overflow: hidden;
      transition: all var(--transition-fast);
      display: flex;
      flex-direction: column;
    }
    .course-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
    .course-banner {
      height: 5rem;
      display: flex;
      align-items: center;
      padding: 1rem;
      border-radius: var(--radius-xl) var(--radius-xl) 0 0;
    }
    .course-banner-icon { font-size: 2rem; }
    .course-card-body { padding: 1rem 1.25rem; flex: 1; }
    .course-stat-row {
      display: grid; grid-template-columns: repeat(3,1fr);
      gap: .5rem; margin: .75rem 0; text-align: center;
    }
    .course-stat-val { font-weight: 700; }
    .course-stat-lbl { font-size: var(--text-xs); color: var(--muted-foreground); margin-top: .125rem; }
    .course-card-footer {
      padding: .875rem 1.25rem;
      border-top: 1px solid var(--border);
      display: flex; gap: .5rem; align-items: center;
      margin-top: -1px;
      position: relative;
    }
    .color-dot {
      width: 1.75rem; height: 1.75rem; border-radius: 50%;
      border: 2.5px solid transparent; cursor: pointer;
      transition: transform .15s, border-color .15s;
    }
    .color-dot:hover { transform: scale(1.15); }
    .color-dot.selected { border-color: var(--foreground); transform: scale(1.15); }
    .empty-grid {
      grid-column: 1/-1; padding: 3rem;
      text-align: center; color: var(--muted-foreground);
    }
</style>
@endpush

@section('content')
  <!-- Page header -->
      <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1>Khóa học</h1>
            <p style="color:var(--muted-foreground);">Quản lý nội dung và tài liệu giảng dạy</p>
          </div>
          <button class="btn btn-primary gap-2" onclick="openCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tạo Khóa học
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng Khóa học</div><div class="stat-card__value" id="stat-total">6</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đang hoạt động</div><div class="stat-card__value" style="color:var(--success);" id="stat-active">4</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng học sinh</div><div class="stat-card__value" id="stat-students">296</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tài liệu</div><div class="stat-card__value" id="stat-materials">42</div></div>
      </div>

      <!-- Toolbar -->
      <div class="toolbar stagger-children" style="margin-bottom:1.25rem;">
        <div class="toolbar-left" style="flex:1;gap:.75rem;">
          <div class="search-input-wrapper" style="max-width:280px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" class="input" id="search-box" placeholder="Tìm khóa học..." oninput="renderCards()" style="font-size:var(--text-sm);" />
          </div>
          <select class="input select" id="filter-subject" onchange="renderCards()" style="max-width:160px;font-size:var(--text-sm);">
            <option value="">Tất cả môn</option>
            <option>Toán</option><option>Vật lý</option><option>Hóa học</option>
            <option>Sinh học</option><option>Lịch sử</option><option>Địa lý</option>
          </select>
          <select class="input select" id="filter-status" onchange="renderCards()" style="max-width:170px;font-size:var(--text-sm);">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang hoạt động</option>
            <option value="completed">Hoàn thành</option>
            <option value="draft">Nháp</option>
          </select>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
          <span id="result-count" style="font-size:var(--text-sm);color:var(--muted-foreground);white-space:nowrap;"></span>
          <button class="icon-btn" id="view-grid" title="Dạng lưới" onclick="setView('grid')" style="background:var(--muted);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          </button>
          <button class="icon-btn" id="view-list" title="Dạng danh sách" onclick="setView('list')">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          </button>
        </div>
      </div>

      <!-- Cards grid -->
      <div id="courses-grid" class="stagger-children" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;"></div>

<div class="modal-overlay" id="course-modal">
  <div class="modal" style="max-width:36rem;">
    <div class="modal-header">
      <div><h3 class="modal-title" id="modal-title">Tạo Khóa học mới</h3><p class="modal-desc" id="modal-desc">Tổ chức nội dung giảng dạy theo chủ đề</p></div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem;">
      <input type="hidden" id="edit-id" value="" />
      <div class="form-group"><label class="label label-required">Tên Khóa học</label><input type="text" class="input" placeholder="VD: Toán Đại số 10" id="f-name" /></div>
      <div class="form-group"><label class="label">Mô tả</label><textarea class="input" id="f-desc" style="min-height:4rem;" placeholder="Mô tả nội dung khóa học..."></textarea></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label class="label label-required">Môn học</label>
          <select class="input select" id="f-subject"><option value="">-- Chọn --</option><option>Toán</option><option>Vật lý</option><option>Hóa học</option><option>Sinh học</option><option>Lịch sử</option><option>Địa lý</option></select>
        </div>
        <div class="form-group">
          <label class="label">Màu sắc</label>
          <div id="color-picker" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.25rem;"></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <div id="modal-error" style="display:none;flex:1;color:var(--destructive);font-size:var(--text-sm);padding:0.375rem 0;">
        <span id="modal-error-text"></span>
      </div>
      <button class="btn btn-outline" onclick="closeModal()">Hủy</button>
      <button class="btn btn-primary" id="modal-submit-btn" onclick="submitModal()">Tạo Khóa học</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="delete-modal">
  <div class="modal" style="max-width:26rem;">
    <div class="modal-body" style="text-align:center;padding:2rem;">
      <div style="font-size:3rem;margin-bottom:.75rem;">🗑️</div>
      <h3 style="font-size:var(--text-xl);font-weight:700;margin-bottom:.5rem;">Xóa khóa học?</h3>
      <p style="color:var(--muted-foreground);font-size:var(--text-sm);margin-bottom:1.5rem;" id="delete-msg">Khóa học sẽ được chuyển vào thùng rác.</p>
      <div style="display:flex;gap:.75rem;justify-content:center;">
        <button class="btn btn-outline" onclick="closeDeleteModal()">Hủy</button>
        <button class="btn btn-destructive" id="delete-confirm-btn">Xóa</button>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function () {
  var COLORS = ['#3b82f6','#f97316','#22c55e','#a855f7','#ef4444','#06b6d4'];
  var SUBJECT_ICON = {Toán:'📐','Vật lý':'⚡','Hóa học':'🧪','Sinh học':'🌿','Địa lý':'🌍','Lịch sử':'📜'};
  var STATUS_BADGE = { active:'badge-success', completed:'badge-info', draft:'badge-outline' };
  var STATUS_LABEL = { active:'Đang hoạt động', completed:'Hoàn thành', draft:'Nháp' };

  var COURSES = [
    { id:1, name:'Toán Đại số 10',     subject:'Toán',    color:'#3b82f6', students:32, lessons:24, materials:8,  progress:75, status:'active',    updated:'Hôm nay' },
    { id:2, name:'Vật lý Điện học 11',  subject:'Vật lý',  color:'#f97316', students:28, lessons:18, materials:6,  progress:60, status:'active',    updated:'Hôm qua' },
    { id:3, name:'Hóa học Hữu cơ 9',   subject:'Hóa học', color:'#22c55e', students:30, lessons:20, materials:10, progress:90, status:'active',    updated:'3 ngày trước' },
    { id:4, name:'Sinh học Tế bào 10',  subject:'Sinh học',color:'#a855f7', students:35, lessons:15, materials:5,  progress:40, status:'active',    updated:'Tuần trước' },
    { id:5, name:'Toán Giải tích 12',   subject:'Toán',    color:'#ef4444', students:88, lessons:30, materials:12, progress:100,status:'completed', updated:'2 tuần trước' },
    { id:6, name:'Địa lý Kinh tế',     subject:'Địa lý',  color:'#06b6d4', students:83, lessons:12, materials:1,  progress:20, status:'draft',     updated:'1 tháng trước' }
  ];

  var currentView = 'grid';
  var deleteTargetId = null;
  var selectedColor = COLORS[0];
  var nextId = 7;

  /* ---- Color picker ---- */
  function buildColorPicker() {
    var cp = document.getElementById('color-picker');
    cp.innerHTML = COLORS.map(function(c) {
      return '<button type="button" class="color-dot' + (c === selectedColor ? ' selected' : '') + '" style="background:' + c + ';" data-color="' + c + '" onclick="pickColor(this,\'' + c + '\')"></button>';
    }).join('');
  }
  window.pickColor = function(btn, color) {
    selectedColor = color;
    document.querySelectorAll('.color-dot').forEach(function(b){ b.classList.remove('selected'); });
    btn.classList.add('selected');
  };

  /* ---- Render ---- */
  window.renderCards = function () {
    var q      = (document.getElementById('search-box').value || '').toLowerCase();
    var subj   = document.getElementById('filter-subject').value;
    var status = document.getElementById('filter-status').value;
    var grid   = document.getElementById('courses-grid');

    var list = COURSES.filter(function(c) {
      return (!q || c.name.toLowerCase().indexOf(q) !== -1 || c.subject.toLowerCase().indexOf(q) !== -1)
          && (!subj || c.subject === subj)
          && (!status || c.status === status);
    });

    document.getElementById('result-count').textContent = list.length + ' khóa học';

    if (!list.length) {
      grid.innerHTML = '<div class="empty-grid"><div style="font-size:3rem;margin-bottom:.75rem;">📚</div><h3 style="font-size:var(--text-xl);font-weight:600;margin-bottom:.375rem;color:var(--foreground);">Không tìm thấy khóa học</h3><p>Thử thay đổi bộ lọc hoặc tạo khóa học mới</p></div>';
      return;
    }
    if (currentView === 'list') { renderListView(list, grid); } else { renderGridView(list, grid); }
  };

  function renderGridView(list, grid) {
    grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;';
    grid.innerHTML = list.map(function(c) {
      var icon = SUBJECT_ICON[c.subject] || '📖';
      return '<div class="course-card">'
        + '<div class="course-banner" style="background:linear-gradient(135deg,' + c.color + ',' + c.color + 'cc);">'
        + '<div class="course-banner-icon">' + icon + '</div></div>'
        + '<div class="course-card-body">'
        + '<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.5rem;">'
        +   '<div><h3 style="font-size:var(--text-base);font-weight:700;margin-bottom:.25rem;">' + c.name + '</h3>'
        +   '<span class="badge badge-outline">' + c.subject + '</span></div>'
        +   '<span class="badge ' + STATUS_BADGE[c.status] + '">' + STATUS_LABEL[c.status] + '</span>'
        + '</div>'
        + '<div class="course-stat-row">'
        +   '<div><div class="course-stat-val">' + c.students + '</div><div class="course-stat-lbl">HS</div></div>'
        +   '<div><div class="course-stat-val">' + c.lessons + '</div><div class="course-stat-lbl">Bài học</div></div>'
        +   '<div><div class="course-stat-val">' + c.materials + '</div><div class="course-stat-lbl">Tài liệu</div></div>'
        + '</div>'
        + '<div style="margin-top:.75rem;">'
        +   '<div style="display:flex;justify-content:space-between;font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;"><span>Tiến độ</span><span>' + c.progress + '%</span></div>'
        +   '<div class="progress"><div class="progress-bar" style="width:' + c.progress + '%;background:' + c.color + ';"></div></div>'
        + '</div>'
        + '<div style="margin-top:.75rem;font-size:var(--text-xs);color:var(--muted-foreground);">Cập nhật: ' + c.updated + '</div>'
        + '</div>'
        + '<div class="course-card-footer" onclick="event.stopPropagation()">'
        +   '<button class="btn btn-outline btn-sm" onclick="openEditModal(' + c.id + ')">Quản lý</button>'
        +   '<button class="btn btn-ghost btn-sm" onclick="addLesson(' + c.id + ')">+ Bài học</button>'
        +   '<button class="btn btn-ghost btn-sm" style="margin-left:auto;color:var(--destructive);" onclick="openDeleteModal(' + c.id + ')">Xóa</button>'
        + '</div></div>';
    }).join('');
  }

  function renderListView(list, grid) {
    grid.style.cssText = 'display:block;';
    var rows = list.map(function(c) {
      var icon = SUBJECT_ICON[c.subject] || '📖';
      return '<tr>'
        + '<td><div style="display:flex;align-items:center;gap:.75rem;">'
        +   '<div style="width:2.25rem;height:2.25rem;border-radius:var(--radius-md);background:' + c.color + ';display:flex;align-items:center;justify-content:center;font-size:1rem;">' + icon + '</div>'
        +   '<div><div style="font-weight:600;">' + c.name + '</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">' + c.subject + '</div></div>'
        + '</div></td>'
        + '<td><span class="badge ' + STATUS_BADGE[c.status] + '">' + STATUS_LABEL[c.status] + '</span></td>'
        + '<td style="font-size:var(--text-sm);">' + c.students + '</td>'
        + '<td style="font-size:var(--text-sm);">' + c.lessons + '</td>'
        + '<td><div class="progress" style="width:5rem;display:inline-flex;vertical-align:middle;margin-right:.5rem;"><div class="progress-bar" style="width:' + c.progress + '%;background:' + c.color + ';"></div></div><span style="font-size:var(--text-sm);">' + c.progress + '%</span></td>'
        + '<td><div style="display:flex;gap:.375rem;">'
        +   '<button class="btn btn-ghost btn-sm" onclick="openEditModal(' + c.id + ')">Sửa</button>'
        +   '<button class="btn btn-ghost btn-sm" style="color:var(--destructive);" onclick="openDeleteModal(' + c.id + ')">Xóa</button>'
        + '</div></td></tr>';
    }).join('');
    grid.innerHTML = '<div class="card"><div class="table-wrapper" style="border:none;border-radius:0;">'
      + '<table><thead><tr><th>Khóa học</th><th>Trạng thái</th><th>HS</th><th>Bài học</th><th>Tiến độ</th><th></th></tr></thead>'
      + '<tbody>' + rows + '</tbody></table></div></div>';
  }

  /* ---- View toggle ---- */
  window.setView = function(v) {
    currentView = v;
    document.getElementById('view-grid').style.background = v === 'grid' ? 'var(--muted)' : '';
    document.getElementById('view-list').style.background = v === 'list' ? 'var(--muted)' : '';
    renderCards();
  };

  /* ---- Stats ---- */
  function updateStats() {
    document.getElementById('stat-total').textContent = COURSES.length;
    document.getElementById('stat-active').textContent = COURSES.filter(function(c){ return c.status === 'active'; }).length;
    document.getElementById('stat-students').textContent = COURSES.reduce(function(s,c){ return s + c.students; }, 0);
    document.getElementById('stat-materials').textContent = COURSES.reduce(function(s,c){ return s + c.materials; }, 0);
  }

  /* ---- Create modal ---- */
  window.openCreateModal = function() {
    document.getElementById('edit-id').value = '';
    document.getElementById('modal-title').textContent = 'Tạo Khóa học mới';
    document.getElementById('modal-desc').textContent = 'Tổ chức nội dung giảng dạy theo chủ đề';
    document.getElementById('modal-submit-btn').textContent = 'Tạo Khóa học';
    ['f-name','f-desc'].forEach(function(id){ document.getElementById(id).value = ''; });
    document.getElementById('f-subject').value = '';
    selectedColor = COLORS[0]; buildColorPicker();
    hideModalError();
    document.getElementById('course-modal').classList.add('open');
    setTimeout(function(){ document.getElementById('f-name').focus(); }, 100);
  };

  /* ---- Edit modal ---- */
  window.openEditModal = function(id) {
    var c = COURSES.find(function(x){ return x.id === id; });
    if (!c) return;
    document.getElementById('edit-id').value = id;
    document.getElementById('modal-title').textContent = 'Chỉnh sửa: ' + c.name;
    document.getElementById('modal-desc').textContent = 'Cập nhật thông tin khóa học';
    document.getElementById('modal-submit-btn').textContent = 'Lưu thay đổi';
    document.getElementById('f-name').value = c.name;
    document.getElementById('f-desc').value = '';
    document.getElementById('f-subject').value = c.subject;
    selectedColor = c.color; buildColorPicker();
    hideModalError();
    document.getElementById('course-modal').classList.add('open');
  };

  window.closeModal = function() { document.getElementById('course-modal').classList.remove('open'); };

  window.submitModal = function() {
    var name = document.getElementById('f-name').value.trim();
    var subject = document.getElementById('f-subject').value;
    var editId = parseInt(document.getElementById('edit-id').value) || 0;
    if (!name) { showModalError('Vui lòng nhập tên khóa học.'); return; }
    if (!subject) { showModalError('Vui lòng chọn môn học.'); return; }

    var btn = document.getElementById('modal-submit-btn');
    btn.disabled = true; btn.textContent = 'Đang xử lý...';

    setTimeout(function() {
      btn.disabled = false;
      if (editId) {
        var c = COURSES.find(function(x){ return x.id === editId; });
        if (c) { c.name = name; c.subject = subject; c.color = selectedColor; }
        showToast('Đã cập nhật khóa học "' + name + '"', 'success');
        btn.textContent = 'Lưu thay đổi';
      } else {
        COURSES.push({ id: nextId++, name: name, subject: subject, color: selectedColor, students: 0, lessons: 0, materials: 0, progress: 0, status: 'draft', updated: 'Vừa tạo' });
        showToast('Đã tạo khóa học "' + name + '" thành công!', 'success');
        btn.textContent = 'Tạo Khóa học';
      }
      closeModal(); renderCards(); updateStats();
    }, 800);
  };

  /* ---- Delete modal ---- */
  window.openDeleteModal = function(id) {
    deleteTargetId = id;
    var c = COURSES.find(function(x){ return x.id === id; });
    if (c) document.getElementById('delete-msg').textContent = 'Khóa học "' + c.name + '" sẽ được chuyển vào thùng rác.';
    document.getElementById('delete-modal').classList.add('open');
    document.getElementById('delete-confirm-btn').onclick = function() { confirmDelete(); };
  };
  window.closeDeleteModal = function() { document.getElementById('delete-modal').classList.remove('open'); deleteTargetId = null; };

  function confirmDelete() {
    if (!deleteTargetId) return;
    var c = COURSES.find(function(x){ return x.id === deleteTargetId; });
    COURSES = COURSES.filter(function(x){ return x.id !== deleteTargetId; });
    closeDeleteModal(); renderCards(); updateStats();
    if (c) showToast('Đã xóa khóa học "' + c.name + '"', 'success');
  }

  window.addLesson = function(id) { showToast('Thêm bài học vào khóa học #' + id, 'success'); };

  /* ---- Helpers ---- */
  function showModalError(msg) { document.getElementById('modal-error-text').textContent = msg; document.getElementById('modal-error').style.display = ''; }
  function hideModalError() { document.getElementById('modal-error').style.display = 'none'; }

  function showToast(msg, type) {
    var tc = document.getElementById('toast-container'); if (!tc) return;
    var t = document.createElement('div');
    t.className = 'toast toast-' + (type || 'success');
    t.innerHTML = '<span>' + (type === 'success' ? '✅' : 'ℹ️') + '</span><span>' + msg + '</span>';
    tc.appendChild(t);
    setTimeout(function(){ t.classList.add('show'); }, 10);
    setTimeout(function(){ t.classList.remove('show'); setTimeout(function(){ t.remove(); }, 300); }, 3000);
  }

  /* Close modals on overlay click */
  document.getElementById('course-modal').addEventListener('click', function(e){ if (e.target === this) closeModal(); });
  document.getElementById('delete-modal').addEventListener('click', function(e){ if (e.target === this) closeDeleteModal(); });

  /* Init */
  buildColorPicker();
  renderCards();
  updateStats();
})();
</script>
@endpush
