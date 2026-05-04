@extends('layouts.admin')

@section('title', 'Admin - VIP & thanh toán')
@section('page-title', 'VIP & thanh toán')
@section('page-description', 'Quản lý gói VIP, trạng thái thuê bao và giao dịch thanh toán.')

@php
  $subscriptionBadges = ['active' => 'badge-success', 'expired' => 'badge-warning', 'cancelled' => 'badge-danger'];
  $paymentBadges = ['pending' => 'badge-warning', 'paid' => 'badge-success', 'failed' => 'badge-danger', 'cancelled' => 'badge-outline'];
@endphp

@section('content')
<section class="admin-grid-2">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Thuê bao VIP</h2>
        <p class="card-description">Cập nhật gói và trạng thái quyền lợi.</p>
      </div>
    </div>
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead><tr><th>Người dùng</th><th>Gói</th><th>Hiệu lực</th><th></th></tr></thead>
        <tbody>
        @forelse($subscriptions as $subscription)
          <tr>
            <td>
              <div class="admin-row-title">{{ $subscription->user?->name ?? 'Không rõ' }}</div>
              <div class="admin-row-meta">{{ $subscription->user?->email }}</div>
            </td>
            <td><span class="badge badge-warning">{{ \App\Support\AdminLabels::vipPlan($subscription->plan) }}</span></td>
            <td>
              <span class="badge {{ $subscriptionBadges[$subscription->status] ?? 'badge-outline' }}">{{ \App\Support\AdminLabels::status($subscription->status) }}</span>
              <div class="admin-row-meta">
                {{ $subscription->started_at?->format('d/m/Y') }} - {{ $subscription->expires_at?->format('d/m/Y') ?? 'trọn đời' }}
              </div>
            </td>
            <td>
              <form method="POST" action="{{ route('admin.vip.subscriptions.update', $subscription->id) }}" class="admin-inline-form">
                @csrf @method('PATCH')
                <select name="plan" class="input select" style="width:auto;">
                  @foreach(['monthly','yearly','lifetime'] as $plan)<option value="{{ $plan }}" @selected($subscription->plan === $plan)>{{ \App\Support\AdminLabels::vipPlan($plan) }}</option>@endforeach
                </select>
                <select name="status" class="input select" style="width:auto;">
                  @foreach(['active','expired','cancelled'] as $status)<option value="{{ $status }}" @selected($subscription->status === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach
                </select>
                <button class="btn btn-primary btn-sm">Lưu</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="empty-state">Chưa có thuê bao VIP.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $subscriptions->links('components.pagination') }}</div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Thanh toán</h2>
        <p class="card-description">Theo dõi giao dịch và đối soát trạng thái.</p>
      </div>
    </div>
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead><tr><th>Giao dịch</th><th>Số tiền</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        @forelse($payments as $payment)
          <tr>
            <td>
              <div class="admin-row-title">{{ $payment->txn_ref }}</div>
              <div class="admin-row-meta">{{ $payment->user?->name ?? 'Không rõ' }} · {{ \App\Support\AdminLabels::vipPlan($payment->plan) }}</div>
            </td>
            <td>{{ number_format($payment->amount) }}đ</td>
            <td>
              <span class="badge {{ $paymentBadges[$payment->status] ?? 'badge-outline' }}">{{ \App\Support\AdminLabels::status($payment->status) }}</span>
              <div class="admin-row-meta">{{ $payment->paid_at?->format('d/m/Y H:i') ?? 'Chưa thanh toán' }}</div>
            </td>
            <td>
              <form method="POST" action="{{ route('admin.vip.payments.update', $payment->id) }}" class="admin-inline-form">
                @csrf @method('PATCH')
                <select name="status" class="input select" style="width:auto;">
                  @foreach(['pending','paid','failed','cancelled'] as $status)<option value="{{ $status }}" @selected($payment->status === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach
                </select>
                <button class="btn btn-primary btn-sm">Lưu</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="empty-state">Chưa có giao dịch.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $payments->links('components.pagination') }}</div>
  </div>
</section>
@endsection
