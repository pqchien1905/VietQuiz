@extends('layouts.admin')

@section('title', 'Admin - Hỗ trợ')
@section('page-title', 'Hỗ trợ')
@section('page-description', 'Tiếp nhận, phản hồi và đổi trạng thái yêu cầu hỗ trợ của người dùng.')

@php
  $statusBadges = ['open' => 'badge-info', 'in_progress' => 'badge-warning', 'resolved' => 'badge-success', 'closed' => 'badge-outline'];
  $statusLabels = ['open' => 'Mới gửi', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã xử lý', 'closed' => 'Đã đóng'];
@endphp

@section('content')
<section class="stats-grid stats-grid-4">
  @foreach($summary as $status => $count)
    <div class="stat-card">
      <div class="stat-card__label">{{ $statusLabels[$status] ?? $status }}</div>
      <div class="stat-card__value">{{ number_format($count) }}</div>
    </div>
  @endforeach
</section>

<section class="card">
  <div class="card-header">
    <form method="GET" class="admin-toolbar">
      <div class="form-group" style="min-width:280px;flex:1;">
        <label class="label">Tìm kiếm</label>
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Tiêu đề hoặc nội dung yêu cầu">
      </div>
      <div class="form-group">
        <label class="label">Trạng thái</label>
        <select class="input select" name="status">
          <option value="">Tất cả</option>
          @foreach($statusLabels as $status => $label)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.tickets') }}">Đặt lại</a>
    </form>
  </div>

  <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
    @forelse($tickets as $ticket)
      <article class="card" style="box-shadow:none;">
        <div class="card-header">
          <div>
            <div class="admin-row-title">{{ $ticket->subject }}</div>
            <div class="admin-row-meta">
              <span>{{ $ticket->user?->name ?? 'Không rõ người gửi' }}</span>
              <span>{{ $ticket->user?->email }}</span>
              <span>{{ $ticket->created_at?->format('d/m/Y H:i') }}</span>
            </div>
          </div>
          <div class="admin-table-actions">
            <span class="badge {{ $statusBadges[$ticket->status] ?? 'badge-outline' }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
            <span class="badge {{ $ticket->priority === 'vip' ? 'badge-warning' : 'badge-outline' }}">{{ $ticket->priority === 'vip' ? 'Ưu tiên VIP' : 'Bình thường' }}</span>
          </div>
        </div>
        <div class="card-content">
          <p style="margin:0 0 1rem;color:var(--muted-foreground);">{{ $ticket->description }}</p>
          @if($ticket->admin_response)
            <div class="alert alert-info"><span>{{ $ticket->admin_response }}</span></div>
          @endif
          <form method="POST" action="{{ route('admin.tickets.respond', $ticket->id) }}" class="admin-grid-2" style="align-items:start;">
            @csrf @method('PATCH')
            <div class="form-group">
              <label class="label">Phản hồi admin</label>
              <textarea name="admin_response" class="input" rows="5" maxlength="3000" placeholder="Nhập nội dung phản hồi...">{{ old('admin_response', $ticket->admin_response) }}</textarea>
            </div>
            <div class="form-group">
              <label class="label">Trạng thái</label>
              <select name="status" class="input select">
                @foreach($statusLabels as $status => $label)
                  <option value="{{ $status }}" @selected($ticket->status === $status)>{{ $label }}</option>
                @endforeach
              </select>
              <button class="btn btn-primary" style="margin-top:.75rem;">Cập nhật yêu cầu</button>
            </div>
          </form>
        </div>
      </article>
    @empty
      <div class="empty-state">Không có yêu cầu hỗ trợ phù hợp.</div>
    @endforelse
  </div>
  <div class="card-footer">{{ $tickets->links('components.pagination') }}</div>
</section>
@endsection
