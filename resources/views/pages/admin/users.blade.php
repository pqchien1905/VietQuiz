@extends('layouts.admin')

@section('title', 'Admin - Người dùng')
@section('page-title', 'Người dùng')
@section('page-description', 'Quản lý tài khoản, vai trò, trạng thái, thông tin liên hệ và khôi phục người dùng.')

@php
  $roles = ['admin', 'teacher', 'student'];
  $summaryCards = [
    ['label' => 'Tổng cộng', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.users', ['state' => 'all'])],
    ['label' => 'Đang hoạt động', 'value' => $summary['active'], 'tone' => 'var(--success)', 'href' => route('admin.users')],
    ['label' => 'Đã khóa', 'value' => $summary['deleted'], 'tone' => 'var(--destructive)', 'href' => route('admin.users', ['state' => 'deleted'])],
    ['label' => 'Giáo viên', 'value' => $summary['teachers'], 'tone' => 'var(--info)', 'href' => route('admin.users', ['role' => 'teacher'])],
    ['label' => 'Học sinh', 'value' => $summary['students'], 'tone' => 'var(--warning)', 'href' => route('admin.users', ['role' => 'student'])],
  ];
@endphp

@push('styles')
<style>
  .admin-users-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .admin-users-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .admin-users-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .admin-users-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .user-cell { display:flex; align-items:center; gap:.75rem; min-width:16rem; }
  .user-avatar { width:2.5rem; height:2.5rem; border-radius:999px; display:grid; place-items:center; background:color-mix(in srgb,var(--primary) 14%,var(--card)); color:var(--primary); font-weight:800; flex:0 0 auto; }
  .user-name-row { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .user-status-stack { display:flex; gap:.35rem; flex-wrap:wrap; }
  .user-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:12rem; }
  .user-meta-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.35rem; color:var(--muted-foreground); font-size:var(--text-xs); }
  .user-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .user-modal-grid .full { grid-column:1/-1; }
  .user-filter-grid { display:grid; grid-template-columns:minmax(260px,1fr) repeat(4,minmax(140px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .admin-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .admin-summary-grid .stat-card { min-height:7.25rem; }
  @media (max-width:1280px) { .admin-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
  @media (max-width:1100px) { .user-filter-grid { grid-template-columns:1fr 1fr; } .user-modal-grid { grid-template-columns:1fr; } .user-modal-grid .full { grid-column:auto; } }
  @media (max-width:820px) { .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:720px) { .user-filter-grid { grid-template-columns:1fr; } .user-cell { min-width:0; } .user-meta-grid { grid-template-columns:1fr; } }
  @media (max-width:520px) { .admin-summary-grid { grid-template-columns:1fr; } }
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
  <div class="card-header admin-users-header">
    <div class="admin-users-title">
      <h3>Danh sách người dùng</h3>
      <p>Hiển thị {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} trên {{ number_format($users->total()) }} kết quả.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openAdminUserModal('create-user-modal')">Thêm người dùng</button>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="user-filter-grid">
      <div class="form-group">
        <label class="label">Tìm kiếm</label>
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Tên, email, số điện thoại">
      </div>
      <div class="form-group">
        <label class="label">Vai trò</label>
        <select class="input select" name="role">
          <option value="">Tất cả</option>
          @foreach($roles as $role)
            <option value="{{ $role }}" @selected(request('role') === $role)>{{ \App\Support\AdminLabels::role($role) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="label">Trạng thái</label>
        <select class="input select" name="state">
          <option value="active" @selected(request('state', 'active') === 'active')>Đang hoạt động</option>
          <option value="deleted" @selected(request('state') === 'deleted')>Đã khóa</option>
          <option value="all" @selected(request('state') === 'all')>Tất cả</option>
        </select>
      </div>
      <div class="form-group">
        <label class="label">VIP</label>
        <select class="input select" name="vip">
          <option value="">Tất cả</option>
          <option value="active" @selected(request('vip') === 'active')>Đang VIP</option>
        </select>
      </div>
      <div class="form-group">
        <label class="label">Sắp xếp</label>
        <select class="input select" name="sort">
          <option value="">Mới nhất</option>
          <option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option>
          <option value="name" @selected(request('sort') === 'name')>Tên A-Z</option>
        </select>
      </div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.users') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead>
        <tr>
          <th>Tài khoản</th>
          <th>Vai trò</th>
          <th>Liên kết</th>
          <th>Trạng thái</th>
          <th style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      @forelse($users as $user)
        <tr style="{{ $user->trashed() ? 'background:color-mix(in srgb,var(--destructive) 7%,transparent);' : '' }}">
          <td>
            <div class="user-cell">
              <div class="user-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
              <div style="min-width:0;">
                <div class="user-name-row">
                  <a class="admin-row-title" href="{{ route('admin.users.show', $user->id) }}">{{ $user->name }}</a>
                  @if($user->vipSubscription?->is_active)<span class="badge badge-warning">VIP</span>@endif
                </div>
                <div class="admin-row-meta">
                  <span>{{ $user->email }}</span>
                  <span>#{{ $user->id }}</span>
                </div>
              </div>
            </div>
          </td>
          <td>
            <span class="badge badge-outline">{{ \App\Support\AdminLabels::role($user->role) }}</span>
            @if($user->can_switch_role)
              <span class="badge badge-info">Đa vai trò</span>
            @endif
          </td>
          <td>
            <div class="user-meta-grid">
              <span>{{ $user->classes_count }} lớp</span>
              <span>{{ $user->courses_count }} khóa</span>
              <span>{{ $user->tickets_count }} hỗ trợ</span>
            </div>
          </td>
          <td>
            <div class="user-status-stack">
              <span class="badge {{ $user->trashed() ? 'badge-danger' : 'badge-success' }}">{{ $user->trashed() ? 'Đã khóa' : 'Hoạt động' }}</span>
              @if($user->phone)
                <span class="badge badge-outline">{{ $user->phone }}</span>
              @endif
              @if($user->subject)
                <span class="badge badge-outline">{{ $user->subject }}</span>
              @endif
            </div>
          </td>
          <td>
            <div class="user-actions">
              <a class="btn btn-outline btn-sm" href="{{ route('admin.users.show', $user->id) }}">Chi tiết</a>
              <button class="btn btn-primary btn-sm" type="button" onclick="openAdminUserModal('edit-user-{{ $user->id }}')">Sửa</button>
              @if($user->trashed())
                <form method="POST" action="{{ route('admin.users.restore', $user->id) }}">
                  @csrf
                  <button class="btn btn-outline-primary btn-sm">Khôi phục</button>
                </form>
              @else
                <form method="POST" action="{{ route('admin.users.delete', $user->id) }}" data-confirm="Khóa tài khoản {{ $user->name }}? Người dùng sẽ không thể đăng nhập cho đến khi được khôi phục." data-confirm-ok="Khóa tài khoản">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-destructive btn-sm">Khóa</button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có người dùng phù hợp với bộ lọc.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $users->links('components.pagination') }}</div>
</section>

<div class="modal-overlay" id="create-user-modal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="create-user-title" style="max-width:42rem;">
    <form method="POST" action="{{ route('admin.users.store') }}">
      @csrf
      <input type="hidden" name="_form" value="create">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="create-user-title">Thêm người dùng</h2>
          <p class="modal-desc">Tạo tài khoản mới và gán vai trò phù hợp.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminUserModal('create-user-modal')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        <div class="user-modal-grid">
          <div class="form-group"><label class="label">Họ tên</label><input class="input" name="name" value="{{ old('_form') === 'create' ? old('name') : '' }}" required maxlength="255"></div>
          <div class="form-group"><label class="label">Email</label><input class="input" name="email" type="email" value="{{ old('_form') === 'create' ? old('email') : '' }}" required maxlength="255"></div>
          <div class="form-group"><label class="label">Vai trò</label><select class="input select" name="role" required>@foreach($roles as $role)<option value="{{ $role }}" @selected((old('_form') === 'create' ? old('role') : request('create')) === $role)>{{ \App\Support\AdminLabels::role($role) }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Mật khẩu</label><input class="input" name="password" type="password" required minlength="8" placeholder="Tối thiểu 8 ký tự"></div>
          <div class="form-group"><label class="label">Số điện thoại</label><input class="input" name="phone" value="{{ old('_form') === 'create' ? old('phone') : '' }}" maxlength="20"></div>
          <div class="form-group"><label class="label">Môn học / chuyên môn</label><input class="input" name="subject" value="{{ old('_form') === 'create' ? old('subject') : '' }}" maxlength="255"></div>
          <label class="full" style="display:flex;align-items:center;gap:.5rem;color:var(--muted-foreground);font-size:var(--text-sm);"><input type="checkbox" name="can_switch_role" value="1" @checked(old('_form') === 'create' && old('can_switch_role'))> Cho phép chuyển giữa giáo viên và học sinh</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminUserModal('create-user-modal')">Hủy</button>
        <button class="btn btn-primary" type="submit">Tạo người dùng</button>
      </div>
    </form>
  </div>
</div>

@foreach($users as $user)
  <div class="modal-overlay" id="edit-user-{{ $user->id }}">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-user-title-{{ $user->id }}" style="max-width:42rem;">
      <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_form" value="edit-{{ $user->id }}">
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="edit-user-title-{{ $user->id }}">Sửa người dùng</h2>
            <p class="modal-desc">{{ $user->email }} · #{{ $user->id }}</p>
          </div>
          <button class="modal-close" type="button" onclick="closeAdminUserModal('edit-user-{{ $user->id }}')" aria-label="Đóng">×</button>
        </div>
        <div class="modal-body">
          <div class="user-modal-grid">
            <div class="form-group"><label class="label">Họ tên</label><input class="input" name="name" value="{{ old('_form') === 'edit-'.$user->id ? old('name') : $user->name }}" required maxlength="255"></div>
            <div class="form-group"><label class="label">Email</label><input class="input" name="email" type="email" value="{{ old('_form') === 'edit-'.$user->id ? old('email') : $user->email }}" required maxlength="255"></div>
            <div class="form-group"><label class="label">Vai trò</label><select class="input select" name="role" required>@foreach($roles as $role)<option value="{{ $role }}" @selected((old('_form') === 'edit-'.$user->id ? old('role') : $user->role) === $role)>{{ \App\Support\AdminLabels::role($role) }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Mật khẩu mới</label><input class="input" name="password" type="password" minlength="8" placeholder="Để trống nếu không đổi"></div>
            <div class="form-group"><label class="label">Số điện thoại</label><input class="input" name="phone" value="{{ old('_form') === 'edit-'.$user->id ? old('phone') : $user->phone }}" maxlength="20"></div>
            <div class="form-group"><label class="label">Môn học / chuyên môn</label><input class="input" name="subject" value="{{ old('_form') === 'edit-'.$user->id ? old('subject') : $user->subject }}" maxlength="255"></div>
            <label class="full" style="display:flex;align-items:center;gap:.5rem;color:var(--muted-foreground);font-size:var(--text-sm);"><input type="checkbox" name="can_switch_role" value="1" @checked(old('_form') === 'edit-'.$user->id ? old('can_switch_role') : $user->can_switch_role)> Cho phép chuyển giữa giáo viên và học sinh</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeAdminUserModal('edit-user-{{ $user->id }}')">Hủy</button>
          <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@push('scripts')
<script>
  function openAdminUserModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminUserModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminUserModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminUserModal(overlay.id);
      });
    }
  });

  const oldForm = @json(old('_form'));
  const createIntent = @json(request('create'));
  if (oldForm === 'create' || createIntent) {
    openAdminUserModal('create-user-modal');
  } else if (oldForm && oldForm.startsWith('edit-')) {
    openAdminUserModal('edit-user-' + oldForm.replace('edit-', ''));
  }
</script>
@endpush
@endsection
