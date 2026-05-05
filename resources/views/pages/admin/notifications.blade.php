@extends('layouts.admin')

@section('title', 'Admin - Thông báo')
@section('page-title', 'Thông báo')
@section('page-description', 'Gửi thông báo theo đối tượng, theo dõi trạng thái đọc và quản lý lịch sử thông báo toàn hệ thống.')

@php
  $typeLabels = [
    'admin_broadcast' => 'Thông báo hệ thống',
    'support_ticket' => 'Hỗ trợ',
    'quiz' => 'Bài kiểm tra',
    'assignment' => 'Bài tập',
    'grade' => 'Điểm số',
    'grading' => 'Chấm điểm',
    'class' => 'Lớp học',
    'course' => 'Khóa học',
    'reminder' => 'Nhắc nhở',
    'vip' => 'VIP',
    'system' => 'Hệ thống',
  ];
  $summaryCards = [
    ['label' => 'Tổng thông báo', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.notifications', ['state' => 'all'])],
    ['label' => 'Đang lưu', 'value' => $summary['active'], 'tone' => 'var(--info)', 'href' => route('admin.notifications')],
    ['label' => 'Chưa đọc', 'value' => $summary['unread'], 'tone' => 'var(--warning)', 'href' => route('admin.notifications', ['state' => 'unread'])],
    ['label' => 'Đã đọc', 'value' => $summary['read'], 'tone' => 'var(--success)', 'href' => route('admin.notifications', ['state' => 'read'])],
    ['label' => 'Gửi hôm nay', 'value' => $summary['today'], 'tone' => 'var(--primary)', 'href' => route('admin.notifications')],
    ['label' => 'Đã xóa', 'value' => $summary['deleted'], 'tone' => 'var(--destructive)', 'href' => route('admin.notifications', ['state' => 'deleted'])],
  ];
@endphp

@push('styles')
<style>
  .notification-summary-grid { grid-template-columns:repeat(6,minmax(0,1fr)); }
  .notification-summary-grid .stat-card { min-height:7.25rem; }
  .notification-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .notification-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .notification-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .notification-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .notification-filter-grid { display:grid; grid-template-columns:minmax(240px,1fr) repeat(6,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .notification-main { min-width:18rem; }
  .notification-body { margin-top:.35rem; color:var(--muted-foreground); max-width:42rem; white-space:normal; }
  .notification-tags { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.45rem; }
  .notification-recipient { min-width:14rem; }
  .notification-actions { display:flex; gap:.5rem; justify-content:flex-end; flex-wrap:wrap; min-width:13rem; }
  .notification-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .notification-modal-grid .full { grid-column:1/-1; }
  @media (max-width:1400px) { .notification-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .notification-filter-grid { grid-template-columns:1fr 1fr 1fr; } }
  @media (max-width:760px) { .notification-summary-grid,.notification-filter-grid,.notification-modal-grid { grid-template-columns:1fr; } .notification-modal-grid .full { grid-column:auto; } }
</style>
@endpush

@section('content')
<section class="stats-grid notification-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header notification-header">
    <div class="notification-title">
      <h3>Lịch sử thông báo</h3>
      <p>Hiển thị {{ $notifications->firstItem() ?? 0 }}-{{ $notifications->lastItem() ?? 0 }} trên {{ number_format($notifications->total()) }} thông báo.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openAdminNotificationModal('create-notification-modal')">Gửi thông báo</button>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="notification-filter-grid">
      <div class="form-group"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Tiêu đề, nội dung, người nhận"></div>
      <div class="form-group"><label class="label">Người nhận</label><select class="input select" name="user_id"><option value="">Tất cả</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Vai trò</label><select class="input select" name="role"><option value="">Tất cả</option><option value="teacher" @selected(request('role') === 'teacher')>Giáo viên</option><option value="student" @selected(request('role') === 'student')>Học sinh</option></select></div>
      <div class="form-group"><label class="label">Loại</label><select class="input select" name="type"><option value="">Tất cả</option>@foreach($types as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $typeLabels[$type] ?? $type }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="state"><option value="active" @selected(request('state', 'active') === 'active')>Đang lưu</option><option value="all" @selected(request('state') === 'all')>Tất cả</option><option value="unread" @selected(request('state') === 'unread')>Chưa đọc</option><option value="read" @selected(request('state') === 'read')>Đã đọc</option><option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option></select></div>
      <div class="form-group"><label class="label">Nhóm</label><select class="input select" name="scope"><option value="">Tất cả</option><option value="with_url" @selected(request('scope') === 'with_url')>Có liên kết</option><option value="system" @selected(request('scope') === 'system')>Hệ thống</option><option value="learning" @selected(request('scope') === 'learning')>Học tập</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sort"><option value="">Mới nhất</option><option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option><option value="recipient" @selected(request('sort') === 'recipient')>Người nhận A-Z</option><option value="type" @selected(request('sort') === 'type')>Theo loại</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.notifications') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Thông báo</th><th>Người nhận</th><th>Trạng thái</th><th>Thời gian</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($notifications as $notification)
        @php
          $url = data_get($notification->data, 'url');
          $target = data_get($notification->data, 'target');
        @endphp
        <tr style="{{ $notification->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
          <td>
            <div class="notification-main">
              <div class="admin-row-title">{{ $notification->title }}</div>
              <div class="notification-body">{{ \Illuminate\Support\Str::limit($notification->body ?: 'Không có nội dung', 150) }}</div>
              <div class="notification-tags">
                <span class="badge badge-outline">{{ $typeLabels[$notification->type] ?? $notification->type }}</span>
                @if($url)<span class="badge badge-info">Có liên kết</span>@endif
                @if($target)<span class="badge badge-outline">Target: {{ $target }}</span>@endif
              </div>
            </div>
          </td>
          <td>
            <div class="notification-recipient">
              <a class="admin-row-title" href="{{ route('admin.users.show', $notification->user_id) }}">{{ $notification->user?->name ?? 'Người dùng đã xóa' }}</a>
              <div class="admin-row-meta">{{ $notification->user?->email }}</div>
              <div class="admin-row-meta">{{ \App\Support\AdminLabels::role($notification->user?->role) }}</div>
            </div>
          </td>
          <td>
            <span class="badge {{ $notification->is_read ? 'badge-outline' : 'badge-warning' }}">{{ $notification->is_read ? 'Đã đọc' : 'Chưa đọc' }}</span>
            @if($notification->trashed())<div class="admin-row-meta">Đã xóa</div>@endif
          </td>
          <td>{{ $notification->created_at?->format('d/m/Y H:i') }}<div class="admin-row-meta">Cập nhật {{ $notification->updated_at?->format('d/m/Y H:i') }}</div></td>
          <td>
            <div class="notification-actions">
              @if($url)<a class="btn btn-outline btn-sm" href="{{ $url }}">Mở</a>@endif
              @if($notification->trashed())
                <form method="POST" action="{{ route('admin.notifications.restore', $notification->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>
              @else
                <form method="POST" action="{{ route('admin.notifications.read-state', $notification->id) }}">
                  @csrf
                  @method('PATCH')
                  <input type="hidden" name="is_read" value="{{ $notification->is_read ? 0 : 1 }}">
                  <button class="btn btn-outline btn-sm">{{ $notification->is_read ? 'Đánh dấu chưa đọc' : 'Đánh dấu đã đọc' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.notifications.delete', $notification->id) }}" data-confirm="Đưa thông báo này vào thùng rác?" data-confirm-ok="Xóa thông báo">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-destructive btn-sm">Xóa</button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có thông báo phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $notifications->links('components.pagination') }}</div>
</section>

<div class="modal-overlay" id="create-notification-modal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="create-notification-title" style="max-width:44rem;">
    <form method="POST" action="{{ route('admin.notifications.store') }}">
      @csrf
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="create-notification-title">Gửi thông báo</h2>
          <p class="modal-desc">Thông báo sẽ được tạo trực tiếp trong hộp thư người nhận.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminNotificationModal('create-notification-modal')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        <div class="notification-modal-grid">
          <div class="form-group"><label class="label">Đối tượng</label><select class="input select" name="target" required><option value="all">Tất cả</option><option value="teacher">Giáo viên</option><option value="student">Học sinh</option><option value="user">Một người dùng</option></select></div>
          <div class="form-group"><label class="label">Người dùng</label><select class="input select" name="user_id"><option value="">Chọn khi gửi một người</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Loại</label><input class="input" name="type" value="admin_broadcast" maxlength="80"></div>
          <div class="form-group"><label class="label">Liên kết khi mở</label><input class="input" name="url" placeholder="{{ route('admin.dashboard') }}"></div>
          <div class="form-group full"><label class="label">Tiêu đề</label><input class="input" name="title" required maxlength="255"></div>
          <div class="form-group full"><label class="label">Nội dung</label><textarea class="input" name="body" rows="5" maxlength="3000"></textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminNotificationModal('create-notification-modal')">Hủy</button>
        <button class="btn btn-primary">Gửi thông báo</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  function openAdminNotificationModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminNotificationModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminNotificationModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminNotificationModal(overlay.id);
      });
    }
  });
</script>
@endpush
@endsection
