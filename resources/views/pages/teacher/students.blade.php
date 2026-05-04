{{-- Teacher: students --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
  $studentsJson = json_encode($studentsData, $jsonFlags);
  $classesJson = json_encode($classes->map(fn ($class) => [
    'id' => $class->id,
    'name' => $class->name,
    'code' => $class->code,
    'join_url' => route('student.join.code', ['code' => strtolower($class->code)]),
    'regenerate_url' => route('teacher.students.invite-link', $class),
  ])->values(), $jsonFlags);

  $initials = function ($name) {
    return collect(explode(' ', trim($name)))
      ->filter()
      ->map(fn ($word) => mb_substr($word, 0, 1))
      ->take(-2)
      ->implode('');
  };

  $rankLabel = function ($avg) {
    if ($avg === null) return 'Chưa có điểm';
    if ($avg >= 8) return 'Giỏi';
    if ($avg >= 6) return 'Khá';
    if ($avg >= 5) return 'Trung bình';
    return 'Yếu';
  };

  $rankBadge = function ($avg) {
    if ($avg === null) return 'badge-default';
    if ($avg >= 8) return 'badge-success';
    if ($avg >= 6) return 'badge-info';
    return 'badge-warning';
  };

  $scoreColor = function ($avg) {
    if ($avg === null) return 'var(--muted-foreground)';
    if ($avg >= 8) return 'var(--success)';
    if ($avg >= 6) return 'var(--info)';
    return 'var(--warning)';
  };
@endphp

@push('styles')
<style>
  .students-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1.5rem}
  .student-avatar{width:2.5rem;height:2.5rem;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:var(--text-sm);font-weight:700;flex-shrink:0;text-transform:uppercase}
  .student-name-cell{display:flex;align-items:center;gap:.75rem;min-width:15rem}
  .student-class-list{display:flex;align-items:center;gap:.375rem;flex-wrap:wrap}
  .student-actions{display:flex;align-items:center;justify-content:flex-end;gap:.375rem}
  .student-action-btn{width:2rem;height:2rem;padding:0}
  .students-empty{padding:3rem 1.5rem;text-align:center;color:var(--muted-foreground)}
  .students-empty-icon{width:4rem;height:4rem;border-radius:999px;background:var(--muted);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--muted-foreground)}
  .invite-tabs{display:flex;gap:.5rem;border-bottom:1px solid var(--border);padding-bottom:.75rem;margin-bottom:1rem}
  .invite-tab{border:none;background:transparent;color:var(--muted-foreground);padding:.5rem .875rem;border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:600;cursor:pointer}
  .invite-tab.active{background:var(--primary);color:var(--primary-foreground)}
  .invite-link-box{display:flex;align-items:center;gap:.5rem;padding:.75rem;background:var(--muted);border-radius:var(--radius-md);font-size:var(--text-sm);word-break:break-all}
  .invite-link-box span{flex:1;color:var(--foreground)}
  .detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
  .detail-stat{padding:.875rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--muted)}
  .detail-stat-label{font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem}
  .detail-score-list{display:flex;flex-direction:column;gap:.625rem;margin-top:.75rem}
  .detail-score-item{display:flex;justify-content:space-between;gap:1rem;padding:.75rem;border:1px solid var(--border);border-radius:var(--radius-md)}
  .student-toolbar{align-items:stretch}
  .student-toolbar .toolbar-left{flex-wrap:wrap}
  @media (max-width: 1100px){.students-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media (max-width: 720px){
    .students-summary,.detail-grid{grid-template-columns:1fr}
    .student-actions{justify-content:flex-start}
    .student-toolbar .toolbar-left,.student-toolbar .toolbar-right{width:100%;align-items:stretch}
    .student-toolbar .input,.student-toolbar .select,.student-toolbar .search-input-wrapper{max-width:none!important;width:100%}
  }
</style>
@endpush

@section('content')
  @foreach(['success' => 'alert-success', 'warning' => 'alert-warning', 'error' => 'alert-danger', 'info' => 'alert-info'] as $key => $className)
    @if(session($key))
      <div class="alert {{ $className }}" style="margin-bottom:1rem;">
        <span>{{ session($key) }}</span>
      </div>
    @endif
  @endforeach

  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;">
      <span>{{ $errors->first() }}</span>
    </div>
  @endif

  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Học sinh</h1>
        <p>Quản lý, theo dõi tiến độ và mời học sinh vào các lớp của bạn.</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <a class="btn btn-outline gap-2" href="{{ route('teacher.students.export', request()->query()) }}">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Xuất Excel
        </a>
        <button class="btn btn-primary gap-2" type="button" onclick="openInviteModal()" @disabled($classes->isEmpty()) title="{{ $classes->isEmpty() ? 'Tạo lớp trước khi mời học sinh' : '' }}">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Mời học sinh
        </button>
      </div>
    </div>
  </div>

  <div class="students-summary stagger-children">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng học sinh</div>
      <div class="stat-card__value">{{ $stats['total'] }}</div>
      <div class="stat-card__label">trong tất cả lớp</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Giỏi</div>
      <div class="stat-card__value" style="color:var(--success);">{{ $stats['good'] }}</div>
      <div class="stat-card__label">điểm TB từ 8.0</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Khá</div>
      <div class="stat-card__value" style="color:var(--info);">{{ $stats['ok'] }}</div>
      <div class="stat-card__label">điểm TB 6.0 đến 7.9</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Cần hỗ trợ</div>
      <div class="stat-card__value" style="color:var(--warning);">{{ $stats['weak'] }}</div>
      <div class="stat-card__label">chưa có điểm hoặc dưới 6.0</div>
    </div>
  </div>

  <form class="toolbar student-toolbar stagger-children" method="GET" action="{{ route('teacher.students') }}">
    <div class="toolbar-left">
      <div class="search-input-wrapper" style="max-width:320px;flex:1;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon" aria-hidden="true">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="search" class="input" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên hoặc email..." style="font-size:var(--text-sm);" />
      </div>

      <select class="input select" name="class_id" style="max-width:190px;font-size:var(--text-sm);">
        <option value="">Tất cả lớp</option>
        @foreach($classes as $class)
          <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->name }}</option>
        @endforeach
      </select>

      <select class="input select" name="perf" style="max-width:180px;font-size:var(--text-sm);">
        <option value="">Tất cả xếp loại</option>
        <option value="good" @selected(request('perf') === 'good')>Giỏi</option>
        <option value="ok" @selected(request('perf') === 'ok')>Khá</option>
        <option value="weak" @selected(request('perf') === 'weak')>Cần hỗ trợ</option>
      </select>
    </div>
    <div class="toolbar-right">
      <button class="btn btn-outline btn-sm" type="submit">Lọc</button>
      @if(request()->hasAny(['search', 'class_id', 'perf']))
        <a class="btn btn-ghost btn-sm" href="{{ route('teacher.students') }}">Xóa lọc</a>
      @endif
      <span style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $allStudents->total() }} kết quả</span>
    </div>
  </form>

  <div class="card stagger-children" style="margin-top:1rem;">
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead>
          <tr>
            <th>Học sinh</th>
            <th>Lớp</th>
            <th>Điểm TB</th>
            <th>Bài nộp</th>
            <th>Hoàn thành</th>
            <th>Xếp loại</th>
            <th style="width:7.5rem;text-align:right;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @forelse($allStudents as $student)
            @php
              $percent = $student->total_assignments > 0
                ? min(100, (int) round(($student->submissions_count / $student->total_assignments) * 100))
                : 0;
              $firstClass = $student->classes->first();
              $avatarColor = ['#2563eb', '#dc2626', '#16a34a', '#ea580c', '#0891b2', '#4f46e5'][$loop->index % 6];
            @endphp
            <tr>
              <td>
                <div class="student-name-cell">
                  <div class="student-avatar" style="background:{{ $avatarColor }}22;color:{{ $avatarColor }};">{{ $initials($student->name) }}</div>
                  <div>
                    <div style="font-weight:700;">{{ $student->name }}</div>
                    <div style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $student->email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="student-class-list">
                  @forelse($student->classes as $class)
                    <span class="badge badge-default">{{ $class->name }}</span>
                  @empty
                    <span style="color:var(--muted-foreground);">Không có lớp</span>
                  @endforelse
                </div>
              </td>
              <td>
                <span style="font-weight:800;color:{{ $scoreColor($student->avg_score) }};">
                  {{ $student->avg_score !== null ? number_format($student->avg_score, 1) : 'N/A' }}
                </span>
              </td>
              <td style="font-size:var(--text-sm);">{{ $student->submissions_count }} / {{ $student->total_assignments }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:.5rem;">
                  <div class="progress" style="width:5.5rem;height:.375rem;">
                    <div class="progress-bar" style="width:{{ $percent }}%;background:{{ $percent >= 80 ? 'var(--success)' : ($percent >= 50 ? 'var(--info)' : 'var(--warning)') }};"></div>
                  </div>
                  <span style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $percent }}%</span>
                </div>
              </td>
              <td><span class="badge {{ $rankBadge($student->avg_score) }}">{{ $rankLabel($student->avg_score) }}</span></td>
              <td>
                <div class="student-actions">
                  <button class="btn btn-ghost btn-sm" type="button" onclick="openDetailModal({{ $student->id }})">Chi tiết</button>
                  @if($firstClass)
                    <button class="btn btn-ghost btn-sm student-action-btn" type="button" onclick="openRemoveModal({{ $student->id }})" aria-label="Gỡ học sinh khỏi lớp">
                      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                      </svg>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7">
                <div class="students-empty">
                  <div class="students-empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                  </div>
                  <h3 style="font-size:var(--text-lg);font-weight:700;color:var(--foreground);margin-bottom:.375rem;">Chưa có học sinh phù hợp</h3>
                  <p style="font-size:var(--text-sm);margin:0;">Mời học sinh vào lớp hoặc thay đổi bộ lọc để xem thêm kết quả.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{ $allStudents->links('components.pagination') }}

  <div class="modal-overlay" id="invite-modal">
    <div class="modal" style="max-width:34rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Mời học sinh</h3>
          <p class="modal-desc">Thêm học sinh đã có tài khoản hoặc chia sẻ mã tham gia lớp.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeInviteModal()">×</button>
      </div>
      <div class="modal-body">
        <div class="invite-tabs">
          <button class="invite-tab active" id="invite-tab-email" type="button" onclick="switchInviteTab('email')">Mời qua email</button>
          <button class="invite-tab" id="invite-tab-link" type="button" onclick="switchInviteTab('link')">Link tham gia</button>
        </div>

        <div id="invite-email-panel">
          <form method="POST" action="{{ route('teacher.students.invite-email') }}" id="invite-email-form">
            @csrf
            <div class="form-group">
              <label class="label label-required" for="invite-class">Lớp</label>
              <select class="input select" id="invite-class" name="class_id" required>
                <option value="">Chọn lớp...</option>
                @foreach($classes as $class)
                  <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group" style="margin-top:1rem;">
              <label class="label label-required" for="invite-emails">Email học sinh</label>
              <textarea class="input" id="invite-emails" name="emails_raw" rows="6" placeholder="Nhập mỗi email trên một dòng hoặc phân cách bằng dấu phẩy..." required>{{ old('emails_raw') }}</textarea>
              <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.5rem;">Chỉ tài khoản có vai trò học sinh mới được thêm vào lớp.</p>
            </div>
          </form>
        </div>

        <div id="invite-link-panel" style="display:none;">
          <div class="form-group">
            <label class="label label-required" for="invite-link-class">Lớp</label>
            <select class="input select" id="invite-link-class" onchange="updateInviteLink()">
              <option value="">Chọn lớp...</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group" style="margin-top:1rem;">
            <label class="label">Mã và link tham gia</label>
            <div class="invite-link-box">
              <span id="invite-link-preview">Chọn lớp để xem link tham gia</span>
              <button class="btn btn-outline btn-sm" type="button" onclick="copyInviteLink()">Sao chép</button>
            </div>
            <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.5rem;" id="invite-code-preview"></p>
          </div>
          <form method="POST" action="#" id="regenerate-link-form" style="margin-top:1rem;" data-confirm="Mã cũ sẽ không còn dùng được. Bạn muốn tạo mã mới?" data-confirm-ok="Tạo mã mới" data-confirm-destructive="false">
            @csrf
            <button class="btn btn-outline btn-sm" type="submit" disabled id="regenerate-link-btn">Tạo mã mới</button>
          </form>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeInviteModal()">Hủy</button>
        <button class="btn btn-primary" type="submit" form="invite-email-form" id="invite-submit-btn">Thêm vào lớp</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="remove-modal">
    <div class="modal" style="max-width:28rem;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Gỡ học sinh khỏi lớp</h3>
          <p class="modal-desc">Tài khoản học sinh vẫn được giữ, chỉ gỡ khỏi lớp đã chọn.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeRemoveModal()">×</button>
      </div>
      <form method="POST" action="{{ route('teacher.students.remove') }}" id="remove-student-form" data-confirm="Bạn chắc chắn muốn gỡ học sinh này khỏi lớp?" data-confirm-ok="Gỡ khỏi lớp" data-confirm-destructive="true">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="student_id" id="remove-student-id">
          <div style="font-weight:700;margin-bottom:.75rem;" id="remove-student-name"></div>
          <div class="form-group">
            <label class="label label-required" for="remove-class-id">Chọn lớp cần gỡ</label>
            <select class="input select" name="class_id" id="remove-class-id" required></select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeRemoveModal()">Hủy</button>
          <button class="btn btn-destructive" type="submit">Gỡ khỏi lớp</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="detail-modal">
    <div class="modal" style="max-width:36rem;">
      <div class="modal-header">
        <div style="display:flex;align-items:center;gap:.875rem;">
          <div class="student-avatar" id="detail-avatar" style="width:3rem;height:3rem;"></div>
          <div>
            <h3 class="modal-title" id="detail-name"></h3>
            <p class="modal-desc" id="detail-email"></p>
          </div>
        </div>
        <button class="modal-close" type="button" onclick="closeDetailModal()">×</button>
      </div>
      <div class="modal-body" id="detail-body"></div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeDetailModal()">Đóng</button>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
(function() {
  'use strict';

  const students = {!! $studentsJson !!};
  const classes = {!! $classesJson !!};
  const colors = ['#2563eb', '#dc2626', '#16a34a', '#ea580c', '#0891b2', '#4f46e5'];

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(char) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]);
    });
  }

  function initials(name) {
    return String(name || '').trim().split(/\s+/).filter(Boolean).map(word => word.charAt(0)).slice(-2).join('').toUpperCase();
  }

  function scoreColor(avg) {
    if (avg === null || avg === undefined) return 'var(--muted-foreground)';
    if (avg >= 8) return 'var(--success)';
    if (avg >= 6) return 'var(--info)';
    return 'var(--warning)';
  }

  function badgeClass(avg) {
    if (avg === null || avg === undefined) return 'badge-default';
    if (avg >= 8) return 'badge-success';
    if (avg >= 6) return 'badge-info';
    return 'badge-warning';
  }

  function rankLabel(avg) {
    if (avg === null || avg === undefined) return 'Chưa có điểm';
    if (avg >= 8) return 'Giỏi';
    if (avg >= 6) return 'Khá';
    if (avg >= 5) return 'Trung bình';
    return 'Yếu';
  }

  function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  window.openInviteModal = function() {
    switchInviteTab('email');
    openModal('invite-modal');
  };

  window.closeInviteModal = function() {
    closeModal('invite-modal');
  };

  window.switchInviteTab = function(tab) {
    document.getElementById('invite-tab-email')?.classList.toggle('active', tab === 'email');
    document.getElementById('invite-tab-link')?.classList.toggle('active', tab === 'link');
    document.getElementById('invite-email-panel').style.display = tab === 'email' ? '' : 'none';
    document.getElementById('invite-link-panel').style.display = tab === 'link' ? '' : 'none';
    document.getElementById('invite-submit-btn').style.display = tab === 'email' ? '' : 'none';
    if (tab === 'link') updateInviteLink();
  };

  window.updateInviteLink = function() {
    const selectedId = Number(document.getElementById('invite-link-class')?.value || 0);
    const selected = classes.find(item => Number(item.id) === selectedId);
    const preview = document.getElementById('invite-link-preview');
    const codePreview = document.getElementById('invite-code-preview');
    const form = document.getElementById('regenerate-link-form');
    const button = document.getElementById('regenerate-link-btn');

    if (!selected) {
      preview.textContent = 'Chọn lớp để xem link tham gia';
      codePreview.textContent = '';
      form.action = '#';
      button.disabled = true;
      return;
    }

    preview.textContent = selected.join_url;
    codePreview.textContent = 'Mã lớp: ' + selected.code;
    form.action = selected.regenerate_url;
    button.disabled = false;
  };

  window.copyInviteLink = function() {
    const text = document.getElementById('invite-link-preview')?.textContent || '';
    if (!text || text.startsWith('Chọn lớp')) return;
    navigator.clipboard?.writeText(text).then(function() {
      if (window.showAppAlert) showAppAlert('Đã sao chép link tham gia.');
    }).catch(function() {
      const textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.opacity = '0';
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      textarea.remove();
    });
  };

  window.openRemoveModal = function(studentId) {
    const student = students.find(item => Number(item.id) === Number(studentId));
    if (!student || !student.classes.length) return;

    document.getElementById('remove-student-id').value = student.id;
    document.getElementById('remove-student-name').textContent = student.name;
    document.getElementById('remove-class-id').innerHTML = student.classes.map(function(item) {
      return `<option value="${escapeHtml(item.id)}">${escapeHtml(item.name)}</option>`;
    }).join('');
    openModal('remove-modal');
  };

  window.closeRemoveModal = function() {
    closeModal('remove-modal');
  };

  window.openDetailModal = function(studentId) {
    const student = students.find(item => Number(item.id) === Number(studentId));
    if (!student) return;

    const index = students.findIndex(item => Number(item.id) === Number(studentId));
    const color = colors[Math.max(index, 0) % colors.length];
    const percent = student.total_assignments > 0 ? Math.min(100, Math.round((student.submitted / student.total_assignments) * 100)) : 0;
    const classBadges = student.classes.length
      ? student.classes.map(item => `<span class="badge badge-default">${escapeHtml(item.name)}</span>`).join(' ')
      : '<span style="color:var(--muted-foreground);">Không có lớp</span>';
    const grades = student.grades && student.grades.length
      ? student.grades.map(function(item) {
          const score = item.score === null || item.score === undefined ? 'N/A' : Number(item.score).toFixed(1);
          return `<div class="detail-score-item">
            <div>
              <div style="font-weight:600;">${escapeHtml(item.title)}</div>
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);">${escapeHtml(item.date)}</div>
            </div>
            <strong style="color:${scoreColor(item.score)};">${score}</strong>
          </div>`;
        }).join('')
      : '<p style="color:var(--muted-foreground);font-size:var(--text-sm);margin:.75rem 0 0;">Chưa có bài nộp nào.</p>';

    const avatar = document.getElementById('detail-avatar');
    avatar.style.background = `${color}22`;
    avatar.style.color = color;
    avatar.textContent = initials(student.name);
    document.getElementById('detail-name').textContent = student.name;
    document.getElementById('detail-email').textContent = student.email || '';
    document.getElementById('detail-body').innerHTML = `
      <div class="detail-grid">
        <div class="detail-stat">
          <div class="detail-stat-label">Lớp đang học</div>
          <div class="student-class-list">${classBadges}</div>
        </div>
        <div class="detail-stat">
          <div class="detail-stat-label">Điểm trung bình</div>
          <div style="font-size:var(--text-xl);font-weight:800;color:${scoreColor(student.avg)};">${student.avg === null ? 'N/A' : Number(student.avg).toFixed(1)}</div>
        </div>
        <div class="detail-stat">
          <div class="detail-stat-label">Bài đã nộp</div>
          <div style="font-weight:700;">${escapeHtml(student.submitted)} / ${escapeHtml(student.total_assignments)}</div>
        </div>
        <div class="detail-stat">
          <div class="detail-stat-label">Hoàn thành</div>
          <div style="display:flex;align-items:center;gap:.5rem;">
            <div class="progress" style="height:.375rem;flex:1;"><div class="progress-bar" style="width:${percent}%;"></div></div>
            <strong>${percent}%</strong>
          </div>
        </div>
        <div class="detail-stat">
          <div class="detail-stat-label">Xếp loại</div>
          <span class="badge ${badgeClass(student.avg)}">${rankLabel(student.avg)}</span>
        </div>
        <div class="detail-stat">
          <div class="detail-stat-label">Ngày tham gia</div>
          <div style="font-weight:700;">${student.joined_at ? escapeHtml(student.joined_at) : 'N/A'}</div>
        </div>
      </div>
      <div style="margin-top:1.25rem;">
        <div style="font-size:var(--text-sm);font-weight:700;">Kết quả gần đây</div>
        <div class="detail-score-list">${grades}</div>
      </div>`;

    openModal('detail-modal');
  };

  window.closeDetailModal = function() {
    closeModal('detail-modal');
  };

  ['invite-modal', 'remove-modal', 'detail-modal'].forEach(function(id) {
    document.getElementById(id)?.addEventListener('click', function(event) {
      if (event.target === this) closeModal(id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    ['invite-modal', 'remove-modal', 'detail-modal'].forEach(closeModal);
  });
})();
</script>
@endpush
