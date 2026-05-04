@extends('layouts.admin')

@section('title', 'Admin - Khuyến mãi')
@section('page-title', 'Khuyến mãi')
@section('page-description', 'Quản lý mã giảm giá, thời hạn, giới hạn sử dụng và trạng thái khuyến mãi.')

@section('content')
<section class="stats-grid stats-grid-4">
  @foreach($summary as $label => $value)
    <div class="stat-card">
      <div class="stat-card__label">{{ \App\Support\AdminLabels::summary($label) }}</div>
      <div class="stat-card__value">{{ number_format($value) }}</div>
    </div>
  @endforeach
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Tạo khuyến mãi</h3></div>
  <div class="card-content">
    <form method="POST" action="{{ route('admin.promotions.store') }}" class="admin-form-grid" style="min-width:0;">
      @csrf
      <div class="form-group"><label class="label">Mã</label><input class="input" name="code" value="{{ old('code') }}" placeholder="WELCOME20" required></div>
      <div class="form-group"><label class="label">Tên</label><input class="input" name="name" value="{{ old('name') }}" placeholder="Chào mừng học viên mới" required></div>
      <div class="form-group"><label class="label">Loại giảm</label><select class="input select" name="discount_type"><option value="percentage">Phần trăm</option><option value="fixed">Số tiền cố định</option></select></div>
      <div class="form-group"><label class="label">Giá trị</label><input class="input" name="discount_value" type="number" step="0.01" min="0" value="{{ old('discount_value', 10) }}" required></div>
      <div class="form-group"><label class="label">Giới hạn lượt dùng</label><input class="input" name="usage_limit" type="number" min="1" value="{{ old('usage_limit') }}"></div>
      <div class="form-group"><label class="label">Đã dùng</label><input class="input" name="used_count" type="number" min="0" value="{{ old('used_count', 0) }}"></div>
      <div class="form-group"><label class="label">Bắt đầu</label><input class="input" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}"></div>
      <div class="form-group"><label class="label">Kết thúc</label><input class="input" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}"></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="active">{{ \App\Support\AdminLabels::status('active') }}</option><option value="inactive">{{ \App\Support\AdminLabels::status('inactive') }}</option></select></div>
      <div class="form-group" style="grid-column:1/-1;"><label class="label">Mô tả</label><textarea class="input" name="description" rows="3">{{ old('description') }}</textarea></div>
      <button class="btn btn-primary" style="grid-column:1/-1;">Tạo khuyến mãi</button>
    </form>
  </div>
</section>

<section class="card">
  <div class="card-header">
    <form method="GET" class="admin-toolbar">
      <div class="form-group" style="min-width:260px;flex:1;"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Mã hoặc tên khuyến mãi"></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="">Tất cả</option><option value="active" @selected(request('status') === 'active')>{{ \App\Support\AdminLabels::status('active') }}</option><option value="inactive" @selected(request('status') === 'inactive')>{{ \App\Support\AdminLabels::status('inactive') }}</option></select></div>
      <div class="form-group"><label class="label">Dữ liệu</label><select class="input select" name="state"><option value="">Đang dùng</option><option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.promotions') }}">Đặt lại</a>
    </form>
  </div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Khuyến mãi</th><th>Hiệu lực</th><th>Sử dụng</th><th>Sửa nhanh</th><th></th></tr></thead>
      <tbody>
      @forelse($promotions as $promotion)
        <tr style="{{ $promotion->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
          <td>
            <div class="admin-row-title">{{ $promotion->code }}</div>
            <div class="admin-row-meta"><span>{{ $promotion->name }}</span><span>{{ $promotion->discount_type === 'percentage' ? $promotion->discount_value.'%' : number_format((float) $promotion->discount_value).'đ' }}</span></div>
          </td>
          <td>
            <span class="badge {{ $promotion->isActive() ? 'badge-success' : 'badge-outline' }}">{{ \App\Support\AdminLabels::status($promotion->status) }}</span>
            <div class="admin-row-meta">
              <span>{{ $promotion->starts_at?->format('d/m/Y H:i') ?? 'Không giới hạn bắt đầu' }}</span>
              <span>{{ $promotion->ends_at?->format('d/m/Y H:i') ?? 'Không giới hạn kết thúc' }}</span>
            </div>
          </td>
          <td>{{ number_format($promotion->used_count) }} / {{ $promotion->usage_limit ? number_format($promotion->usage_limit) : '∞' }}</td>
          <td>
            <form method="POST" action="{{ route('admin.promotions.update', $promotion->id) }}" class="admin-form-grid">
              @csrf @method('PATCH')
              <input class="input" name="code" value="{{ $promotion->code }}">
              <input class="input" name="name" value="{{ $promotion->name }}">
              <select class="input select" name="discount_type"><option value="percentage" @selected($promotion->discount_type === 'percentage')>{{ \App\Support\AdminLabels::discountType('percentage') }}</option><option value="fixed" @selected($promotion->discount_type === 'fixed')>{{ \App\Support\AdminLabels::discountType('fixed') }}</option></select>
              <input class="input" name="discount_value" type="number" step="0.01" value="{{ $promotion->discount_value }}">
              <input class="input" name="usage_limit" type="number" value="{{ $promotion->usage_limit }}">
              <input class="input" name="used_count" type="number" value="{{ $promotion->used_count }}">
              <input class="input" name="starts_at" type="datetime-local" value="{{ $promotion->starts_at?->format('Y-m-d\TH:i') }}">
              <input class="input" name="ends_at" type="datetime-local" value="{{ $promotion->ends_at?->format('Y-m-d\TH:i') }}">
              <select class="input select" name="status"><option value="active" @selected($promotion->status === 'active')>{{ \App\Support\AdminLabels::status('active') }}</option><option value="inactive" @selected($promotion->status === 'inactive')>{{ \App\Support\AdminLabels::status('inactive') }}</option></select>
              <textarea class="input" name="description" rows="2" style="grid-column:1/-1;">{{ $promotion->description }}</textarea>
              <button class="btn btn-primary btn-sm" style="grid-column:1/-1;">Lưu</button>
            </form>
          </td>
          <td>
            <div class="admin-table-actions">
              @if($promotion->trashed())
                <form method="POST" action="{{ route('admin.promotions.restore', $promotion->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>
              @else
                <form method="POST" action="{{ route('admin.promotions.delete', $promotion->id) }}" onsubmit="return confirm('Xóa khuyến mãi này?')">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Xóa</button></form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Chưa có khuyến mãi.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $promotions->links('components.pagination') }}</div>
</section>
@endsection
