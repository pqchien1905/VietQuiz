@extends('layouts.admin')

@section('title', 'Admin - VIP & thanh toán')
@section('page-title', 'VIP & thanh toán')
@section('page-description', 'Đối soát doanh thu, cấp quyền VIP, xử lý giao dịch VNPay và kiểm soát thời hạn sử dụng.')

@php
  $subscriptionBadges = ['active' => 'badge-success', 'expired' => 'badge-warning', 'cancelled' => 'badge-danger'];
  $paymentBadges = ['pending' => 'badge-warning', 'paid' => 'badge-success', 'failed' => 'badge-danger', 'cancelled' => 'badge-outline'];
  $plans = ['monthly' => 'Hàng tháng', 'yearly' => 'Hàng năm', 'lifetime' => 'Trọn đời'];
  $audienceLabels = ['teacher' => 'Giáo viên', 'student' => 'Học sinh'];
  $statuses = ['active' => 'Hoạt động', 'expired' => 'Hết hạn', 'cancelled' => 'Đã hủy'];
  $paymentStatuses = ['pending' => 'Chờ xử lý', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'cancelled' => 'Đã hủy'];
  $summaryCards = [
    ['label' => 'VIP hoạt động', 'value' => number_format($summary['active']), 'tone' => 'var(--success)', 'href' => route('admin.vip', ['sub_status' => 'active'])],
    ['label' => 'Sắp hết hạn', 'value' => number_format($summary['expiring']), 'tone' => 'var(--warning)', 'href' => route('admin.vip', ['sub_scope' => 'expiring'])],
    ['label' => 'Cần rà soát', 'value' => number_format($summary['overdue'] + $summary['pending']), 'tone' => 'var(--destructive)', 'href' => route('admin.vip', ['pay_status' => 'pending'])],
    ['label' => 'Doanh thu 30 ngày', 'value' => number_format($summary['revenue_30d']).'đ', 'tone' => 'var(--primary)', 'href' => route('admin.vip', ['pay_scope' => 'recent_paid'])],
    ['label' => 'Tổng doanh thu', 'value' => number_format($summary['revenue_total']).'đ', 'tone' => 'var(--info)', 'href' => route('admin.vip', ['pay_status' => 'paid'])],
  ];
@endphp

@push('styles')
<style>
  .vip-summary-grid { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .vip-summary-grid .stat-card { min-height:7.25rem; }
  .vip-ops-grid { display:grid; grid-template-columns:minmax(22rem,.75fr) minmax(0,1.25fr); gap:1rem; align-items:start; }
  .vip-filter-grid { display:grid; grid-template-columns:minmax(220px,1fr) repeat(4,minmax(130px,auto)) auto auto; gap:.75rem; align-items:end; width:100%; }
  .vip-row-main { min-width:15rem; }
  .vip-row-meta { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.45rem; }
  .vip-money { font-weight:800; white-space:nowrap; }
  .vip-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; min-width:8rem; }
  .vip-modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .vip-modal-grid .full { grid-column:1/-1; }
  .vip-plan-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
  .vip-plan-card { border:1px solid var(--border); border-radius:8px; padding:1rem; display:flex; flex-direction:column; gap:.75rem; background:var(--card); }
  .vip-plan-card__top { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
  .vip-plan-card__price { font-size:1.35rem; font-weight:900; color:var(--primary); }
  @media (max-width:1320px) { .vip-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .vip-filter-grid { grid-template-columns:1fr 1fr 1fr; } .vip-ops-grid { grid-template-columns:1fr; } }
  @media (max-width:1320px) { .vip-plan-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:760px) { .vip-summary-grid,.vip-filter-grid,.vip-modal-grid,.vip-plan-grid { grid-template-columns:1fr; } .vip-modal-grid .full { grid-column:auto; } }
</style>
@endpush

@section('content')
<section class="stats-grid vip-summary-grid">
  @foreach($summaryCards as $card)
    <a href="{{ $card['href'] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ $card['value'] }}</div>
    </a>
  @endforeach
</section>

<section class="vip-ops-grid">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Cấp VIP thủ công</h2>
        <p class="card-description">Dùng cho đối soát chuyển khoản ngoài VNPay, tài khoản thử nghiệm hoặc hỗ trợ khách hàng.</p>
      </div>
    </div>
    <div class="card-content">
      <form method="POST" action="{{ route('admin.vip.subscriptions.store') }}" class="admin-form-grid">
        @csrf
        <div class="form-group" style="grid-column:1/-1;">
          <label class="label">Người dùng</label>
          <select class="input select" name="user_id" required>
            <option value="">Chọn tài khoản</option>
            @foreach($eligibleUsers as $user)
              <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }} - {{ \App\Support\AdminLabels::role($user->role) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group"><label class="label">Gói</label><select class="input select" name="plan">@foreach($plans as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="form-group"><label class="label">Bắt đầu</label><input class="input" type="datetime-local" name="started_at" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
        <div class="form-group"><label class="label">Hết hạn</label><input class="input" type="datetime-local" name="expires_at" placeholder="Tự tính theo gói"></div>
        <button class="btn btn-primary" style="grid-column:1/-1;">Cấp hoặc cập nhật VIP</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Tín hiệu đối soát</h2>
        <p class="card-description">Các chỉ số giúp ưu tiên xử lý quyền lợi và thanh toán.</p>
      </div>
    </div>
    <div class="activity-list">
      <div class="activity-item"><span class="badge badge-success">{{ number_format($summary['paid']) }}</span><div><div class="admin-row-title">Giao dịch đã thanh toán</div><div class="admin-row-meta">Đã ghi nhận doanh thu và có thể kích hoạt VIP.</div></div></div>
      <div class="activity-item"><span class="badge badge-warning">{{ number_format($summary['pending']) }}</span><div><div class="admin-row-title">Giao dịch đang chờ</div><div class="admin-row-meta">Cần kiểm tra VNPay hoặc phản hồi của người dùng.</div></div></div>
      <div class="activity-item"><span class="badge badge-danger">{{ number_format($summary['failed']) }}</span><div><div class="admin-row-title">Giao dịch lỗi hoặc đã hủy</div><div class="admin-row-meta">Không kích hoạt quyền lợi nếu chưa có xác nhận thanh toán.</div></div></div>
    </div>
  </div>
</section>

<section class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">Giá gói VIP</h2>
      <p class="card-description">Cập nhật giá hiển thị ở trang mua VIP của học sinh và giáo viên.</p>
    </div>
  </div>
  <div class="card-content">
    <div class="vip-plan-grid">
      @foreach($vipPlans as $vipPlan)
        <form method="POST" action="{{ route('admin.vip.plans.update', $vipPlan->id) }}" class="vip-plan-card">
          @csrf
          @method('PATCH')
          <div class="vip-plan-card__top">
            <div>
              <div class="admin-row-title">{{ $audienceLabels[$vipPlan->audience] ?? $vipPlan->audience }}</div>
              <div class="admin-row-meta">{{ $plans[$vipPlan->plan] ?? $vipPlan->plan }}</div>
            </div>
            <span class="badge {{ $vipPlan->status === 'active' ? 'badge-success' : 'badge-outline' }}">{{ $vipPlan->status === 'active' ? 'Đang bán' : 'Tạm ẩn' }}</span>
          </div>
          <div class="vip-plan-card__price">{{ number_format($vipPlan->amount) }}đ</div>
          <div class="form-group"><label class="label">Tên gói</label><input class="input" name="label" value="{{ $vipPlan->label }}" required></div>
          <div class="form-group"><label class="label">Giá bán</label><input class="input" name="amount" type="number" min="0" value="{{ $vipPlan->amount }}" required></div>
          <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="active" @selected($vipPlan->status === 'active')>Đang bán</option><option value="inactive" @selected($vipPlan->status === 'inactive')>Tạm ẩn</option></select></div>
          <input type="hidden" name="sort_order" value="{{ $vipPlan->sort_order }}">
          <button class="btn btn-primary btn-sm">Lưu giá</button>
        </form>
      @endforeach
    </div>
  </div>
</section>

<section class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">Khuyến mãi cho VIP</h2>
      <p class="card-description">Tạo và sửa mã giảm giá gắn với tất cả gói VIP hoặc một chu kỳ cụ thể.</p>
    </div>
    <a class="btn btn-outline" href="{{ route('admin.promotions') }}">Tất cả khuyến mãi</a>
  </div>
  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="POST" action="{{ route('admin.promotions.store') }}" class="admin-form-grid">
      @csrf
      <div class="form-group"><label class="label">Mã</label><input class="input" name="code" placeholder="VIP20" required></div>
      <div class="form-group"><label class="label">Tên</label><input class="input" name="name" placeholder="Ưu đãi VIP" required></div>
      <div class="form-group"><label class="label">Áp dụng</label><select class="input select" name="vip_plan"><option value="all">Tất cả gói VIP</option>@foreach($plans as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Loại giảm</label><select class="input select" name="discount_type"><option value="percentage">Phần trăm</option><option value="fixed">Số tiền cố định</option></select></div>
      <div class="form-group"><label class="label">Giá trị</label><input class="input" name="discount_value" type="number" min="0" step="0.01" value="10" required></div>
      <div class="form-group"><label class="label">Giới hạn lượt dùng</label><input class="input" name="usage_limit" type="number" min="1"></div>
      <div class="form-group"><label class="label">Đã dùng</label><input class="input" name="used_count" type="number" min="0" value="0"></div>
      <div class="form-group"><label class="label">Bắt đầu</label><input class="input" name="starts_at" type="datetime-local"></div>
      <div class="form-group"><label class="label">Kết thúc</label><input class="input" name="ends_at" type="datetime-local"></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="active">Hoạt động</option><option value="inactive">Tạm dừng</option></select></div>
      <div class="form-group" style="grid-column:1/-1;"><label class="label">Mô tả</label><textarea class="input" name="description" rows="2"></textarea></div>
      <button class="btn btn-primary" style="grid-column:1/-1;">Tạo khuyến mãi VIP</button>
    </form>
  </div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Mã</th><th>Áp dụng</th><th>Giảm giá</th><th>Hiệu lực</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($vipPromotions as $promotion)
        <tr>
          <td><div class="admin-row-title">{{ $promotion->code }}</div><div class="admin-row-meta">{{ $promotion->name }}</div></td>
          <td><span class="badge badge-warning">{{ $promotion->vip_plan === 'all' ? 'Tất cả VIP' : ($plans[$promotion->vip_plan] ?? $promotion->vip_plan) }}</span></td>
          <td>{{ $promotion->discount_type === 'percentage' ? rtrim(rtrim($promotion->discount_value, '0'), '.').'%' : number_format((float) $promotion->discount_value).'đ' }}</td>
          <td><span class="badge {{ $promotion->isActive() ? 'badge-success' : 'badge-outline' }}">{{ \App\Support\AdminLabels::status($promotion->status) }}</span><div class="admin-row-meta">{{ $promotion->ends_at?->format('d/m/Y H:i') ?? 'Không giới hạn' }}</div></td>
          <td><div class="vip-actions"><button class="btn btn-primary btn-sm" type="button" onclick="openAdminVipModal('edit-promotion-{{ $promotion->id }}')">Sửa</button></div></td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Chưa có khuyến mãi VIP.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">Thuê bao VIP</h2>
      <p class="card-description">Hiển thị {{ $subscriptions->firstItem() ?? 0 }}-{{ $subscriptions->lastItem() ?? 0 }} trên {{ number_format($subscriptions->total()) }} thuê bao.</p>
    </div>
  </div>
  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="vip-filter-grid">
      <div class="form-group"><label class="label">Tìm thuê bao</label><input class="input" name="sub_q" value="{{ request('sub_q') }}" placeholder="Tên hoặc email"></div>
      <div class="form-group"><label class="label">Vai trò</label><select class="input select" name="sub_role"><option value="">Tất cả</option><option value="teacher" @selected(request('sub_role') === 'teacher')>Giáo viên</option><option value="student" @selected(request('sub_role') === 'student')>Học sinh</option></select></div>
      <div class="form-group"><label class="label">Gói</label><select class="input select" name="sub_plan"><option value="">Tất cả</option>@foreach($plans as $value => $label)<option value="{{ $value }}" @selected(request('sub_plan') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="sub_status"><option value="">Tất cả</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('sub_status') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Nhóm</label><select class="input select" name="sub_scope"><option value="">Tất cả</option><option value="expiring" @selected(request('sub_scope') === 'expiring')>Sắp hết hạn</option><option value="overdue" @selected(request('sub_scope') === 'overdue')>Quá hạn vẫn active</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="sub_sort"><option value="">Mới cập nhật</option><option value="expires" @selected(request('sub_sort') === 'expires')>Gần hết hạn</option><option value="user" @selected(request('sub_sort') === 'user')>Người dùng A-Z</option><option value="oldest" @selected(request('sub_sort') === 'oldest')>Cũ nhất</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.vip') }}">Đặt lại</a>
    </form>
  </div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Người dùng</th><th>Gói</th><th>Hiệu lực</th><th>Trạng thái</th><th style="text-align:right;">Thao tác</th></tr></thead>
      <tbody>
      @forelse($subscriptions as $subscription)
        <tr>
          <td><div class="vip-row-main"><a class="admin-row-title" href="{{ route('admin.users.show', $subscription->user_id) }}">{{ $subscription->user?->name ?? 'Không rõ' }}</a><div class="admin-row-meta">{{ $subscription->user?->email }}</div></div></td>
          <td><span class="badge badge-warning">{{ $plans[$subscription->plan] ?? $subscription->plan }}</span><div class="admin-row-meta">{{ \App\Support\AdminLabels::role($subscription->user?->role) }}</div></td>
          <td>{{ $subscription->started_at?->format('d/m/Y H:i') }}<div class="admin-row-meta">{{ $subscription->expires_at?->format('d/m/Y H:i') ?? 'Không hết hạn' }}</div></td>
          <td><span class="badge {{ $subscriptionBadges[$subscription->status] ?? 'badge-outline' }}">{{ $statuses[$subscription->status] ?? $subscription->status }}</span><div class="admin-row-meta">{{ $subscription->is_active ? 'Đang có hiệu lực' : 'Không còn hiệu lực' }}</div></td>
          <td><div class="vip-actions"><button class="btn btn-primary btn-sm" type="button" onclick="openAdminVipModal('edit-subscription-{{ $subscription->id }}')">Sửa</button></div></td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Chưa có thuê bao phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $subscriptions->links('components.pagination') }}</div>
</section>

<section class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">Giao dịch thanh toán</h2>
      <p class="card-description">Hiển thị {{ $payments->firstItem() ?? 0 }}-{{ $payments->lastItem() ?? 0 }} trên {{ number_format($payments->total()) }} giao dịch.</p>
    </div>
  </div>
  <div class="card-content" style="border-top:1px solid var(--border);">
    <form method="GET" class="vip-filter-grid">
      <div class="form-group"><label class="label">Tìm giao dịch</label><input class="input" name="pay_q" value="{{ request('pay_q') }}" placeholder="Mã giao dịch, người dùng, mã VNPay"></div>
      <div class="form-group"><label class="label">Vai trò</label><select class="input select" name="pay_role"><option value="">Tất cả</option><option value="teacher" @selected(request('pay_role') === 'teacher')>Giáo viên</option><option value="student" @selected(request('pay_role') === 'student')>Học sinh</option></select></div>
      <div class="form-group"><label class="label">Gói</label><select class="input select" name="pay_plan"><option value="">Tất cả</option>@foreach($plans as $value => $label)<option value="{{ $value }}" @selected(request('pay_plan') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="pay_status"><option value="">Tất cả</option>@foreach($paymentStatuses as $value => $label)<option value="{{ $value }}" @selected(request('pay_status') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Nhóm</label><select class="input select" name="pay_scope"><option value="">Tất cả</option><option value="needs_reconcile" @selected(request('pay_scope') === 'needs_reconcile')>Đã trả chưa gắn VIP</option><option value="recent_paid" @selected(request('pay_scope') === 'recent_paid')>Đã trả 7 ngày</option></select></div>
      <div class="form-group"><label class="label">Sắp xếp</label><select class="input select" name="pay_sort"><option value="">Mới nhất</option><option value="amount" @selected(request('pay_sort') === 'amount')>Số tiền cao</option><option value="paid_at" @selected(request('pay_sort') === 'paid_at')>Mới thanh toán</option><option value="oldest" @selected(request('pay_sort') === 'oldest')>Cũ nhất</option></select></div>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.vip') }}">Đặt lại</a>
    </form>
  </div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead><tr><th>Giao dịch</th><th>Người dùng</th><th>Số tiền</th><th>Trạng thái</th><th style="text-align:right;">Đối soát</th></tr></thead>
      <tbody>
      @forelse($payments as $payment)
        <tr>
          <td><div class="vip-row-main"><div class="admin-row-title">{{ $payment->txn_ref }}</div><div class="vip-row-meta">@if($payment->vnp_transaction_no)<span class="badge badge-outline">VNPay {{ $payment->vnp_transaction_no }}</span>@endif @if($payment->vnp_bank_code)<span class="badge badge-outline">{{ $payment->vnp_bank_code }}</span>@endif</div></div></td>
          <td><a class="admin-row-title" href="{{ route('admin.users.show', $payment->user_id) }}">{{ $payment->user?->name ?? 'Không rõ' }}</a><div class="admin-row-meta">{{ $payment->user?->email }} · {{ $plans[$payment->plan] ?? $payment->plan }}</div></td>
          <td><span class="vip-money">{{ number_format($payment->amount) }}đ</span><div class="admin-row-meta">{{ $payment->created_at?->format('d/m/Y H:i') }}</div></td>
          <td><span class="badge {{ $paymentBadges[$payment->status] ?? 'badge-outline' }}">{{ $paymentStatuses[$payment->status] ?? $payment->status }}</span><div class="admin-row-meta">{{ $payment->paid_at?->format('d/m/Y H:i') ?? 'Chưa thanh toán' }}</div></td>
          <td>
            <form method="POST" action="{{ route('admin.vip.payments.update', $payment->id) }}" class="admin-inline-form" style="justify-content:flex-end;">
              @csrf
              @method('PATCH')
              <select class="input select" name="status" style="width:auto;">@foreach($paymentStatuses as $value => $label)<option value="{{ $value }}" @selected($payment->status === $value)>{{ $label }}</option>@endforeach</select>
              <button class="btn btn-primary btn-sm">Lưu</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Chưa có giao dịch phù hợp.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $payments->links('components.pagination') }}</div>
</section>

@foreach($subscriptions as $subscription)
  <div class="modal-overlay" id="edit-subscription-{{ $subscription->id }}">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-subscription-title-{{ $subscription->id }}" style="max-width:38rem;">
      <form method="POST" action="{{ route('admin.vip.subscriptions.update', $subscription->id) }}">
        @csrf
        @method('PATCH')
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="edit-subscription-title-{{ $subscription->id }}">Sửa thuê bao VIP</h2>
            <p class="modal-desc">{{ $subscription->user?->name ?? 'Không rõ người dùng' }} · {{ $subscription->user?->email }}</p>
          </div>
          <button class="modal-close" type="button" onclick="closeAdminVipModal('edit-subscription-{{ $subscription->id }}')" aria-label="Đóng">×</button>
        </div>
        <div class="modal-body">
          <div class="vip-modal-grid">
            <div class="form-group"><label class="label">Gói</label><select class="input select" name="plan">@foreach($plans as $value => $label)<option value="{{ $value }}" @selected($subscription->plan === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected($subscription->status === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Bắt đầu</label><input class="input" type="datetime-local" name="started_at" value="{{ old('started_at', $subscription->started_at?->format('Y-m-d\TH:i')) }}" required></div>
            <div class="form-group"><label class="label">Hết hạn</label><input class="input" type="datetime-local" name="expires_at" value="{{ old('expires_at', $subscription->expires_at?->format('Y-m-d\TH:i')) }}"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeAdminVipModal('edit-subscription-{{ $subscription->id }}')">Hủy</button>
          <button class="btn btn-primary">Lưu thuê bao</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@foreach($vipPromotions as $promotion)
  <div class="modal-overlay" id="edit-promotion-{{ $promotion->id }}">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-promotion-title-{{ $promotion->id }}" style="max-width:42rem;">
      <form method="POST" action="{{ route('admin.promotions.update', $promotion->id) }}">
        @csrf
        @method('PATCH')
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="edit-promotion-title-{{ $promotion->id }}">Sửa khuyến mãi VIP</h2>
            <p class="modal-desc">{{ $promotion->code }} · {{ $promotion->name }}</p>
          </div>
          <button class="modal-close" type="button" onclick="closeAdminVipModal('edit-promotion-{{ $promotion->id }}')" aria-label="Đóng">×</button>
        </div>
        <div class="modal-body">
          <div class="vip-modal-grid">
            <div class="form-group"><label class="label">Mã</label><input class="input" name="code" value="{{ $promotion->code }}" required></div>
            <div class="form-group"><label class="label">Tên</label><input class="input" name="name" value="{{ $promotion->name }}" required></div>
            <div class="form-group"><label class="label">Áp dụng</label><select class="input select" name="vip_plan"><option value="all" @selected($promotion->vip_plan === 'all')>Tất cả gói VIP</option>@foreach($plans as $value => $label)<option value="{{ $value }}" @selected($promotion->vip_plan === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="label">Loại giảm</label><select class="input select" name="discount_type"><option value="percentage" @selected($promotion->discount_type === 'percentage')>Phần trăm</option><option value="fixed" @selected($promotion->discount_type === 'fixed')>Số tiền cố định</option></select></div>
            <div class="form-group"><label class="label">Giá trị</label><input class="input" name="discount_value" type="number" min="0" step="0.01" value="{{ $promotion->discount_value }}" required></div>
            <div class="form-group"><label class="label">Giới hạn lượt dùng</label><input class="input" name="usage_limit" type="number" min="1" value="{{ $promotion->usage_limit }}"></div>
            <div class="form-group"><label class="label">Đã dùng</label><input class="input" name="used_count" type="number" min="0" value="{{ $promotion->used_count }}"></div>
            <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status"><option value="active" @selected($promotion->status === 'active')>Hoạt động</option><option value="inactive" @selected($promotion->status === 'inactive')>Tạm dừng</option></select></div>
            <div class="form-group"><label class="label">Bắt đầu</label><input class="input" name="starts_at" type="datetime-local" value="{{ $promotion->starts_at?->format('Y-m-d\TH:i') }}"></div>
            <div class="form-group"><label class="label">Kết thúc</label><input class="input" name="ends_at" type="datetime-local" value="{{ $promotion->ends_at?->format('Y-m-d\TH:i') }}"></div>
            <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="3">{{ $promotion->description }}</textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" type="button" onclick="closeAdminVipModal('edit-promotion-{{ $promotion->id }}')">Hủy</button>
          <button class="btn btn-primary">Lưu khuyến mãi</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@push('scripts')
<script>
  function openAdminVipModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAdminVipModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(event) {
      if (event.target === overlay) closeAdminVipModal(overlay.id);
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(function(overlay) {
        closeAdminVipModal(overlay.id);
      });
    }
  });
</script>
@endpush
@endsection
