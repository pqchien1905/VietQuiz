@extends('layouts.admin')

@section('title', 'Admin - Hỗ trợ')
@section('page-title', 'Hỗ trợ')
@section('page-description', 'Điều phối hàng đợi hỗ trợ, ưu tiên ticket VIP, phản hồi người dùng và theo dõi các yêu cầu quá hạn.')

@php
  $statusBadges = ['open' => 'badge-info', 'in_progress' => 'badge-warning', 'resolved' => 'badge-success', 'closed' => 'badge-outline'];
  $statusLabels = ['open' => 'Mới gửi', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã xử lý', 'closed' => 'Đã đóng'];
  $priorityLabels = ['normal' => 'Bình thường', 'vip' => 'Ưu tiên VIP'];
  $summaryCards = [
    ['label' => 'Mới gửi', 'value' => $summary['open'], 'tone' => 'var(--info)', 'href' => route('admin.tickets', ['status' => 'open'])],
    ['label' => 'Đang xử lý', 'value' => $summary['in_progress'], 'tone' => 'var(--warning)', 'href' => route('admin.tickets', ['status' => 'in_progress'])],
    ['label' => 'Chưa phản hồi', 'value' => $summary['unanswered'], 'tone' => 'var(--destructive)', 'href' => route('admin.tickets', ['scope' => 'unanswered'])],
    ['label' => 'VIP đang mở', 'value' => $summary['vip'], 'tone' => 'var(--warning)', 'href' => route('admin.tickets', ['scope' => 'vip'])],
    ['label' => 'Quá hạn', 'value' => $summary['stale'], 'tone' => 'var(--destructive)', 'href' => route('admin.tickets', ['scope' => 'stale'])],
    ['label' => 'Hôm nay', 'value' => $summary['today'], 'tone' => 'var(--primary)', 'href' => route('admin.tickets')],
  ];
@endphp

@push('styles')
<style>
  .ticket-summary-grid { grid-template-columns:repeat(6,minmax(0,1fr)); }
  .ticket-summary-grid .stat-card { min-height:7.25rem; }
  .ticket-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .ticket-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .ticket-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .ticket-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .ticket-filter-grid { display:grid; grid-template-columns:minmax(240px,1fr) repeat(6,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .ticket-main { min-width:18rem; }
  .ticket-description { margin-top:.35rem; color:var(--muted-foreground); max-width:46rem; white-space:normal; }
  .ticket-tags { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.5rem; }
  .ticket-user { min-width:14rem; }
  .ticket-response { max-width:26rem; min-width:16rem; white-space:normal; color:var(--muted-foreground); }
  .ticket-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:9rem; }
  .ticket-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .ticket-modal-grid .full { grid-column:1/-1; }
  @media (max-width:1400px) { .ticket-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .ticket-filter-grid { grid-template-columns:1fr 1fr 1fr; } }
  @media (max-width:760px) { .ticket-summary-grid,.ticket-filter-grid,.ticket-modal-grid { grid-template-columns:1fr; } .ticket-modal-grid .full { grid-column:auto; } }
</style>
@endpush

@section('content')
<section class="stats-grid ticket-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header ticket-header">
    <div class="ticket-title">
      <h3>Hàng đợi hỗ trợ</h3>
      <p>Hiển thị {{ $tickets->firstItem() ?? 0 }}-{{ $tickets->lastItem() ?? 0 }} trên {{ number_format($tickets->total()) }} yêu cầu.</p>
    </div>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="ticket-filter-grid">
      <div class="form-group"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Tiêu đề, nội dung, người gửi"></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="">Tất cả</option>@foreach($statusLabels as $status => $label)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Danh mục</label><select class="input select" name="category"><option value="">Tất cả</option>@foreach($categories as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Ưu tiên</label><select class="input select" name="priority"><option value="">Tất cả</option>@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Vai trò</label><select class="input select" name="role"><option value="">Tất cả</option><option value="teacher" @selected(request('role') === 'teacher')>Giáo viên</option><option value="student" @selected(request('role') === 'student')>Học sinh</option></select></div>
      <div class="form-group"><label class="label">Nhóm</label><select class="input select" name="scope"><option value="">Tất cả</option><option value="active" @selected(request('scope') === 'active')>Đang mở</option><option value="unanswered" @selected(request('scope') === 'unanswered')>Chưa phản hồi</option><option value="answered" @selected(request('scope') === 'answered')>Đã phản hồi</option><option value="vip" @selected(request('scope') === 'vip')>VIP</option><option value="stale" @selected(request('scope') === 'stale')>Quá hạn</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sort"><option value="">Ưu tiên xử lý</option><option value="priority" @selected(request('sort') === 'priority')>VIP trước</option><option value="updated" @selected(request('sort') === 'updated')>Mới cập nhật</option><option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option><option value="sender" @selected(request('sort') === 'sender')>Người gửi A-Z</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.tickets') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Yêu cầu</th><th>Người gửi</th><th>Trạng thái</th><th>Phản hồi</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($tickets as $ticket)
        @php
          $isStale = in_array($ticket->status, ['open', 'in_progress'], true) && $ticket->updated_at?->lte(now()->subDays(2));
          $categoryLabel = $categories[$ticket->category] ?? $ticket->category;
        @endphp
        <tr>
          <td>
            <div class="ticket-main">
              <div class="admin-row-title">{{ $ticket->subject }}</div>
              <div class="ticket-description">{{ \Illuminate\Support\Str::limit($ticket->description, 170) }}</div>
              <div class="ticket-tags">
                <span class="badge badge-outline">{{ $categoryLabel }}</span>
                <span class="badge {{ $ticket->priority === 'vip' ? 'badge-warning' : 'badge-outline' }}">{{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</span>
                @if($isStale)<span class="badge badge-danger">Quá hạn</span>@endif
              </div>
            </div>
          </td>
          <td>
            <div class="ticket-user">
              <a class="admin-row-title" href="{{ route('admin.users.show', $ticket->user_id) }}">{{ $ticket->user?->name ?? 'Không rõ' }}</a>
              <div class="admin-row-meta">{{ $ticket->user?->email }}</div>
              <div class="admin-row-meta">{{ \App\Support\AdminLabels::role($ticket->user?->role) }}</div>
            </div>
          </td>
          <td>
            <span class="badge {{ $statusBadges[$ticket->status] ?? 'badge-outline' }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
            <div class="admin-row-meta">Tạo {{ $ticket->created_at?->format('d/m/Y H:i') }}</div>
            <div class="admin-row-meta">Cập nhật {{ $ticket->updated_at?->format('d/m/Y H:i') }}</div>
          </td>
          <td><div class="ticket-response">{{ \Illuminate\Support\Str::limit($ticket->admin_response ?: 'Chưa có phản hồi từ admin', 150) }}</div></td>
          <td><div class="ticket-actions"><button class="btn btn-primary btn-sm" type="button" onclick="openAdminTicketModal('ticket-{{ $ticket->id }}')">Xử lý</button></div></td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Không có yêu cầu hỗ trợ phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $tickets->links('components.pagination') }}</div>
</section>

@foreach($tickets as $ticket)
  <div class="modal-overlay" id="ticket-{{ $ticket->id }}">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ticket-title-{{ $ticket->id }}" style="max-width:48rem;">
      <form method="POST" action="{{ route('admin.tickets.respond', $ticket->id) }}">
        @csrf
        @method('PATCH')
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="ticket-title-{{ $ticket->id }}">Xử lý ticket #{{ $ticket->id }}</h2>
            <p class="modal-desc">{{ $ticket->user?->name ?? 'Không rõ người gửi' }} · {{ $ticket->subject }}</p>
          </div>
          <button class="modal-close" type="button" onclick="closeAdminTicketModal('ticket-{{ $ticket->id }}')" aria-label="Đóng">×</button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info"><span>{{ $ticket->description }}</span></div>
          <div class="ticket-modal-grid" style="margin-top:1rem;">
            <div class="form-group"><label class="label">Trạng thái</label><select name="status" class="input select">@foreach($statusLabels as $status => $label)<option value="{{ $status }}" @selected($ticket->status === $status)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Ưu tiên</label><select name="priority" class="input select">@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected($ticket->priority === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group full"><label class="label">Danh mục</label><select name="category" class="input select">@foreach($categories as $value => $label)<option value="{{ $value }}" @selected($ticket->category === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group full"><label class="label">Phản hồi admin</label><textarea name="admin_response" class="input" rows="6" maxlength="3000" placeholder="Nhập nội dung phản hồi...">{{ old('admin_response', $ticket->admin_response) }}</textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeAdminTicketModal('ticket-{{ $ticket->id }}')">Hủy</button>
          <button class="btn btn-primary">Cập nhật ticket</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@push('scripts')
<script>
  function openAdminTicketModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminTicketModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminTicketModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminTicketModal(overlay.id);
      });
    }
  });
</script>
@endpush
@endsection
