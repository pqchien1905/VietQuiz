@extends('layouts.admin')

@section('title', 'Admin - Khuyến mãi')
@section('page-title', 'Khuyến mãi')
@section('page-description', 'Quản lý mã giảm giá, phạm vi áp dụng, thời hạn, giới hạn sử dụng và trạng thái vận hành.')

@php
  $discountTypes = ['percentage' => 'Phần trăm', 'fixed' => 'Số tiền cố định'];
  $statusLabels = ['active' => 'Hoạt động', 'inactive' => 'Tạm dừng'];
  $vipPlanLabels = ['all' => 'Tất cả gói VIP', 'monthly' => 'VIP tháng', 'yearly' => 'VIP năm', 'lifetime' => 'VIP trọn đời'];
  $summaryCards = [
    ['label' => 'Tổng mã', 'value' => $summary['total'], 'tone' => 'var(--primary)', 'href' => route('admin.promotions', ['state' => 'all'])],
    ['label' => 'Đang hiệu lực', 'value' => $summary['running'], 'tone' => 'var(--success)', 'href' => route('admin.promotions', ['scope' => 'running'])],
    ['label' => 'Sắp chạy', 'value' => $summary['scheduled'], 'tone' => 'var(--info)', 'href' => route('admin.promotions', ['scope' => 'scheduled'])],
    ['label' => 'Hết hạn', 'value' => $summary['expired'], 'tone' => 'var(--warning)', 'href' => route('admin.promotions', ['scope' => 'expired'])],
    ['label' => 'Dùng hết', 'value' => $summary['exhausted'], 'tone' => 'var(--destructive)', 'href' => route('admin.promotions', ['scope' => 'exhausted'])],
    ['label' => 'Mã VIP', 'value' => $summary['vip'], 'tone' => 'var(--warning)', 'href' => route('admin.promotions', ['scope' => 'vip'])],
  ];
@endphp

@push('styles')
<style>
  .promotion-summary-grid { grid-template-columns:repeat(6,minmax(0,1fr)); }
  .promotion-summary-grid .stat-card { min-height:7.25rem; }
  .promotion-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .promotion-title { display:flex; flex-direction:column; gap:.25rem; min-width:0; }
  .promotion-title h3 { margin:0; font-size:var(--text-lg); font-weight:800; }
  .promotion-title p { margin:0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .promotion-filter-grid { display:grid; grid-template-columns:minmax(240px,1fr) repeat(6,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .promotion-main { min-width:18rem; }
  .promotion-code { font-size:1rem; font-weight:900; letter-spacing:.04em; }
  .promotion-meta { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.45rem; }
  .promotion-value { min-width:9rem; }
  .promotion-actions { display:flex; gap:.5rem; justify-content:flex-end; flex-wrap:wrap; min-width:11rem; }
  .promotion-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .promotion-modal-grid .full { grid-column:1/-1; }
  @media (max-width:1380px) { .promotion-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .promotion-filter-grid { grid-template-columns:1fr 1fr 1fr; } }
  @media (max-width:760px) { .promotion-summary-grid,.promotion-filter-grid,.promotion-modal-grid { grid-template-columns:1fr; } .promotion-modal-grid .full { grid-column:auto; } }
</style>
@endpush

@section('content')
<section class="stats-grid promotion-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ number_format($card['value']) }}</div>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header promotion-header">
    <div class="promotion-title">
      <h3>Danh sách khuyến mãi</h3>
      <p>Hiển thị {{ $promotions->firstItem() ?? 0 }}-{{ $promotions->lastItem() ?? 0 }} trên {{ number_format($promotions->total()) }} mã.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openAdminPromotionModal('create-promotion-modal')">Tạo khuyến mãi</button>
  </div>

  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="promotion-filter-grid">
      <div class="form-group"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Mã, tên hoặc mô tả"></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="">Tất cả</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Loại giảm</label><select class="input select" name="discount_type"><option value="">Tất cả</option>@foreach($discountTypes as $value => $label)<option value="{{ $value }}" @selected(request('discount_type') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">VIP</label><select class="input select" name="vip_plan"><option value="">Tất cả</option>@foreach($vipPlanLabels as $value => $label)<option value="{{ $value }}" @selected(request('vip_plan') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Nhóm</label><select class="input select" name="scope"><option value="">Tất cả</option><option value="running" @selected(request('scope') === 'running')>Đang hiệu lực</option><option value="scheduled" @selected(request('scope') === 'scheduled')>Sắp chạy</option><option value="expired" @selected(request('scope') === 'expired')>Hết hạn</option><option value="exhausted" @selected(request('scope') === 'exhausted')>Dùng hết</option><option value="vip" @selected(request('scope') === 'vip')>Mã VIP</option><option value="general" @selected(request('scope') === 'general')>Mã thường</option></select></div>
      <div class="form-group"><label class="label">Dữ liệu</label><select class="input select" name="state"><option value="active" @selected(request('state', 'active') === 'active')>Đang dùng</option><option value="all" @selected(request('state') === 'all')>Tất cả</option><option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sort"><option value="">Mới nhất</option><option value="code" @selected(request('sort') === 'code')>Mã A-Z</option><option value="ending" @selected(request('sort') === 'ending')>Sắp hết hạn</option><option value="usage" @selected(request('sort') === 'usage')>Dùng nhiều</option><option value="value" @selected(request('sort') === 'value')>Giá trị cao</option><option value="oldest" @selected(request('sort') === 'oldest')>Cũ nhất</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.promotions') }}">Đặt lại</a>
    </form>
  </div>

  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Khuyến mãi</th><th>Áp dụng</th><th>Giá trị</th><th>Hiệu lực</th><th>Sử dụng</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($promotions as $promotion)
        @php
          $usagePercent = $promotion->usage_limit ? min(100, round(($promotion->used_count / max(1, $promotion->usage_limit)) * 100)) : null;
          $isDeleted = $promotion->trashed();
          $isActiveNow = ! $isDeleted && $promotion->isActive();
          $scopeLabel = $promotion->vip_plan ? ($vipPlanLabels[$promotion->vip_plan] ?? $promotion->vip_plan) : 'Toàn hệ thống';
          $discountLabel = $promotion->discount_type === 'percentage'
            ? rtrim(rtrim((string) $promotion->discount_value, '0'), '.').'%'
            : number_format((float) $promotion->discount_value).'đ';
        @endphp
        <tr style="{{ $isDeleted ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
          <td>
            <div class="promotion-main">
              <div class="promotion-code">{{ $promotion->code }}</div>
              <div class="admin-row-title">{{ $promotion->name }}</div>
              <div class="admin-row-meta">{{ \Illuminate\Support\Str::limit($promotion->description ?: 'Không có mô tả', 100) }}</div>
            </div>
          </td>
          <td>
            <span class="badge {{ $promotion->vip_plan ? 'badge-warning' : 'badge-info' }}">{{ $scopeLabel }}</span>
            <div class="promotion-meta">
              <span class="badge badge-outline">{{ $discountTypes[$promotion->discount_type] ?? $promotion->discount_type }}</span>
            </div>
          </td>
          <td><div class="promotion-value"><span class="badge {{ $promotion->discount_type === 'percentage' ? 'badge-success' : 'badge-info' }}">{{ $discountLabel }}</span></div></td>
          <td>
            <span class="badge {{ $isDeleted ? 'badge-danger' : ($isActiveNow ? 'badge-success' : 'badge-outline') }}">{{ $isDeleted ? 'Đã xóa' : ($isActiveNow ? 'Đang hiệu lực' : ($statusLabels[$promotion->status] ?? $promotion->status)) }}</span>
            <div class="admin-row-meta">{{ $promotion->starts_at?->format('d/m/Y H:i') ?? 'Không giới hạn bắt đầu' }}</div>
            <div class="admin-row-meta">{{ $promotion->ends_at?->format('d/m/Y H:i') ?? 'Không giới hạn kết thúc' }}</div>
          </td>
          <td>
            {{ number_format($promotion->used_count) }} / {{ $promotion->usage_limit ? number_format($promotion->usage_limit) : '∞' }}
            @if($usagePercent !== null)<div class="admin-row-meta">{{ $usagePercent }}% đã dùng</div>@endif
          </td>
          <td>
            <div class="promotion-actions">
              @if($isDeleted)
                <form method="POST" action="{{ route('admin.promotions.restore', $promotion->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>
              @else
                <button class="btn btn-primary btn-sm" type="button" onclick="openAdminPromotionModal('edit-promotion-{{ $promotion->id }}')">Sửa</button>
                <form method="POST" action="{{ route('admin.promotions.delete', $promotion->id) }}" data-confirm="Đưa mã {{ $promotion->code }} vào thùng rác?" data-confirm-ok="Xóa mã">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-destructive btn-sm">Xóa</button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty-state">Không có khuyến mãi phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $promotions->links('components.pagination') }}</div>
</section>

<div class="modal-overlay" id="create-promotion-modal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="create-promotion-title" style="max-width:44rem;">
    <form method="POST" action="{{ route('admin.promotions.store') }}">
      @csrf
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="create-promotion-title">Tạo khuyến mãi</h2>
          <p class="modal-desc">Thiết lập mã, phạm vi áp dụng, giới hạn và thời gian hiệu lực.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminPromotionModal('create-promotion-modal')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        @include('pages.admin.partials.promotion-form-fields', ['promotion' => null, 'discountTypes' => $discountTypes, 'statusLabels' => $statusLabels, 'vipPlanLabels' => $vipPlanLabels])
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminPromotionModal('create-promotion-modal')">Hủy</button>
        <button class="btn btn-primary">Tạo khuyến mãi</button>
      </div>
    </form>
  </div>
</div>

@foreach($promotions as $promotion)
  @unless($promotion->trashed())
    <div class="modal-overlay" id="edit-promotion-{{ $promotion->id }}">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-promotion-title-{{ $promotion->id }}" style="max-width:44rem;">
        <form method="POST" action="{{ route('admin.promotions.update', $promotion->id) }}">
          @csrf
          @method('PATCH')
          <div class="modal-header">
            <div>
              <h2 class="modal-title" id="edit-promotion-title-{{ $promotion->id }}">Sửa khuyến mãi</h2>
              <p class="modal-desc">{{ $promotion->code }} · {{ $promotion->name }}</p>
            </div>
            <button class="modal-close" type="button" onclick="closeAdminPromotionModal('edit-promotion-{{ $promotion->id }}')" aria-label="Đóng">×</button>
          </div>
          <div class="modal-body">
            @include('pages.admin.partials.promotion-form-fields', ['promotion' => $promotion, 'discountTypes' => $discountTypes, 'statusLabels' => $statusLabels, 'vipPlanLabels' => $vipPlanLabels])
          </div>
          <div class="modal-footer">
            <button class="btn btn-outline" type="button" onclick="closeAdminPromotionModal('edit-promotion-{{ $promotion->id }}')">Hủy</button>
            <button class="btn btn-primary">Lưu khuyến mãi</button>
          </div>
        </form>
      </div>
    </div>
  @endunless
@endforeach

@push('scripts')
<script>
  function openAdminPromotionModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminPromotionModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminPromotionModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminPromotionModal(overlay.id);
      });
    }
  });
</script>
@endpush
@endsection
