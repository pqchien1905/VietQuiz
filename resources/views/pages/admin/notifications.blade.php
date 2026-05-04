@extends('layouts.admin')

@section('title', 'Admin - Thông báo')
@section('page-title', 'Thông báo')
@section('page-description', 'Gửi thông báo hệ thống và quản lý lịch sử thông báo của người dùng.')

@section('content')
<section class="card">
  <div class="card-header"><h3 class="card-title">Gửi thông báo</h3></div>
  <div class="card-content">
    <form method="POST" action="{{ route('admin.notifications.store') }}" class="admin-form-grid" style="min-width:0;">
      @csrf
      <div class="form-group"><label class="label">Đối tượng</label><select class="input select" name="target"><option value="all">Tất cả</option><option value="teacher">Giáo viên</option><option value="student">Học sinh</option><option value="user">Một người dùng</option></select></div>
      <div class="form-group"><label class="label">Người dùng</label><select class="input select" name="user_id"><option value="">Chọn khi gửi một người</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }} ({{ \App\Support\AdminLabels::role($user->role) }})</option>@endforeach</select></div>
      <div class="form-group" style="grid-column:1/-1;"><label class="label">Tiêu đề</label><input class="input" name="title" required></div>
      <div class="form-group" style="grid-column:1/-1;"><label class="label">Nội dung</label><textarea class="input" name="body" rows="3"></textarea></div>
      <button class="btn btn-primary" style="grid-column:1/-1;">Gửi thông báo</button>
    </form>
  </div>
</section>

<section class="card">
  <div class="card-header"><form method="GET" class="admin-toolbar"><div class="form-group" style="min-width:260px;flex:1;"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Tiêu đề, nội dung"></div><div class="form-group"><label class="label">Loại</label><input class="input" name="type" value="{{ request('type') }}" placeholder="yêu cầu hỗ trợ"></div><div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="state"><option value="">Tất cả</option><option value="unread" @selected(request('state') === 'unread')>Chưa đọc</option><option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option></select></div><button class="btn btn-primary">Lọc</button><a class="btn btn-outline" href="{{ route('admin.notifications') }}">Đặt lại</a></form></div>
  <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Thông báo</th><th>Người nhận</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr></thead><tbody>
    @forelse($notifications as $notification)
      <tr style="{{ $notification->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}"><td><div class="admin-row-title">{{ $notification->title }}</div><div class="admin-row-meta">{{ \Illuminate\Support\Str::limit($notification->body, 120) }}</div></td><td>{{ $notification->user?->name ?? 'Người dùng đã xóa' }}<div class="admin-row-meta">{{ $notification->user?->email }}</div></td><td><span class="badge {{ $notification->is_read ? 'badge-outline' : 'badge-warning' }}">{{ $notification->is_read ? 'Đã đọc' : 'Chưa đọc' }}</span><div class="admin-row-meta">{{ \App\Support\AdminLabels::notificationType($notification->type) }}</div></td><td>{{ $notification->created_at?->format('d/m/Y H:i') }}</td><td><div class="admin-table-actions">@if($notification->trashed())<form method="POST" action="{{ route('admin.notifications.restore', $notification->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>@else<form method="POST" action="{{ route('admin.notifications.delete', $notification->id) }}" onsubmit="return confirm('Xóa thông báo này?')">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Xóa</button></form>@endif</div></td></tr>
    @empty <tr><td colspan="5" class="empty-state">Không có thông báo.</td></tr> @endforelse
  </tbody></table></div>
  <div class="card-footer">{{ $notifications->links('components.pagination') }}</div>
</section>
@endsection
