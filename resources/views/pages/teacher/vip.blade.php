{{-- Teacher: vip --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $active = $subscription && $subscription->is_active;
  $planNames = ['monthly' => 'Pro tháng', 'yearly' => 'Pro năm', 'lifetime' => 'Pro trọn đời'];
  $statusNames = ['pending' => 'Đang chờ', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'cancelled' => 'Đã hủy'];
  $statusClasses = ['pending' => 'vip-status-pending', 'paid' => 'vip-status-paid', 'failed' => 'vip-status-failed', 'cancelled' => 'vip-status-cancelled'];
@endphp

@push('styles')
<style>
  .vip-page { display:flex; flex-direction:column; gap:1.5rem; }
  .vip-hero { display:grid; grid-template-columns:minmax(0,1.4fr) minmax(280px,.6fr); gap:1.5rem; align-items:stretch; padding:2rem; border:1px solid var(--border); border-radius:var(--radius-xl); background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 14%,var(--card)),var(--card)); }
  .vip-kicker { display:inline-flex; align-items:center; gap:.5rem; width:max-content; padding:.35rem .75rem; border-radius:999px; background:color-mix(in srgb,var(--success) 12%,transparent); color:var(--success); font-size:var(--text-sm); font-weight:700; }
  .vip-hero h1 { margin:.85rem 0 .75rem; font-size:clamp(1.75rem,3vw,2.6rem); line-height:1.08; letter-spacing:0; }
  .vip-hero p { max-width:680px; color:var(--muted-foreground); font-size:var(--text-base); line-height:1.7; }
  .vip-summary { border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); padding:1.25rem; display:flex; flex-direction:column; gap:.75rem; }
  .vip-summary-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; font-size:var(--text-sm); }
  .vip-summary-row strong { font-size:var(--text-base); }
  .vip-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
  .vip-plan { position:relative; display:flex; flex-direction:column; gap:1rem; padding:1.25rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); transition:border-color var(--transition-fast), box-shadow var(--transition-fast), transform var(--transition-fast); }
  .vip-plan:hover { border-color:var(--primary); box-shadow:var(--shadow-md); transform:translateY(-2px); }
  .vip-plan.featured { border-color:var(--primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 10%,transparent); }
  .vip-plan-badge { position:absolute; top:-.75rem; left:1rem; padding:.25rem .65rem; border-radius:999px; background:var(--primary); color:var(--primary-foreground); font-size:.72rem; font-weight:800; }
  .vip-plan-title { font-size:var(--text-lg); font-weight:800; }
  .vip-price { display:flex; align-items:flex-end; gap:.35rem; }
  .vip-price strong { font-size:2rem; line-height:1; }
  .vip-price span { color:var(--muted-foreground); font-size:var(--text-sm); }
  .vip-features { display:flex; flex-direction:column; gap:.55rem; margin:0; padding:0; list-style:none; color:var(--muted-foreground); font-size:var(--text-sm); }
  .vip-features li { display:flex; gap:.5rem; align-items:flex-start; }
  .vip-form { display:flex; flex-direction:column; gap:.75rem; margin-top:auto; }
  .vip-form select { width:100%; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--background); color:var(--foreground); padding:.65rem .75rem; font-size:var(--text-sm); }
  .vip-section-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(320px,.45fr); gap:1rem; align-items:start; }
  .vip-alert { padding:1rem; border-radius:var(--radius-lg); border:1px solid var(--border); background:var(--card); }
  .vip-alert-success { border-color:color-mix(in srgb,var(--success) 35%,var(--border)); background:color-mix(in srgb,var(--success) 8%,var(--card)); color:var(--success); }
  .vip-alert-error { border-color:color-mix(in srgb,var(--destructive) 35%,var(--border)); background:color-mix(in srgb,var(--destructive) 8%,var(--card)); color:var(--destructive); }
  .vip-status { display:inline-flex; align-items:center; padding:.28rem .6rem; border-radius:999px; font-size:.75rem; font-weight:800; }
  .vip-status-pending { color:#a16207; background:#fef3c7; }
  .vip-status-paid { color:#047857; background:#d1fae5; }
  .vip-status-failed, .vip-status-cancelled { color:#b91c1c; background:#fee2e2; }
  .vip-info-list { display:flex; flex-direction:column; gap:.75rem; margin:0; padding:0; list-style:none; font-size:var(--text-sm); }
  .vip-info-list li { display:flex; justify-content:space-between; gap:1rem; border-bottom:1px solid var(--border); padding-bottom:.75rem; }
  .vip-info-list li:last-child { border-bottom:0; padding-bottom:0; }
  .vip-copy { word-break:break-all; color:var(--muted-foreground); text-align:right; }
  @media (max-width: 1024px) {
    .vip-hero, .vip-section-grid { grid-template-columns:1fr; }
    .vip-grid { grid-template-columns:1fr; }
  }
</style>
@endpush

@section('content')
<div class="vip-page">
  @if(session('success'))
    <div class="vip-alert vip-alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="vip-alert vip-alert-error">{{ session('error') }}</div>
  @endif

  @if($errors->any())
    <div class="vip-alert vip-alert-error">{{ $errors->first() }}</div>
  @endif

  <section class="vip-hero">
    <div>
      <div class="vip-kicker">Thanh toán qua VNPay sandbox</div>
      <h1>Nâng cấp VietQuiz Pro cho giáo viên</h1>
      <p>Mở khóa tạo đề không giới hạn, AI hỗ trợ câu hỏi và chấm tự luận, phân tích lớp học nâng cao, xuất báo cáo và ưu tiên hỗ trợ khi vận hành lớp.</p>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.25rem;">
        <a href="#vip-plans" class="btn btn-primary">Chọn gói</a>
        <a href="#vnpay-info" class="btn btn-outline">Thông tin VNPay</a>
      </div>
    </div>

    <aside class="vip-summary">
      <div class="vip-summary-row">
        <span>Trạng thái</span>
        <strong>{{ $active ? 'Đang dùng Pro' : 'Gói miễn phí' }}</strong>
      </div>
      <div class="vip-summary-row">
        <span>Gói hiện tại</span>
        <strong>{{ $active ? ($planNames[$subscription->plan] ?? 'Pro') : 'Free' }}</strong>
      </div>
      <div class="vip-summary-row">
        <span>Hiệu lực đến</span>
        <strong>
          @if(!$active)
            -
          @elseif($subscription->plan === 'lifetime' || !$subscription->expires_at)
            Trọn đời
          @else
            {{ $subscription->expires_at->format('d/m/Y') }}
          @endif
        </strong>
      </div>
      @if($active)
        <form method="POST" action="{{ route('teacher.vip.cancel') }}">
          @csrf
          <button class="btn btn-outline w-full" type="submit">Hủy gia hạn</button>
        </form>
      @endif
    </aside>
  </section>

  <section id="vip-plans" class="vip-grid">
    <article class="vip-plan">
      <div class="vip-plan-title">Pro tháng</div>
      <div class="vip-price"><strong>{{ number_format($plans['monthly']['amount']) }}đ</strong><span>/ tháng</span></div>
      <ul class="vip-features">
        <li><span>✓</span><span>Không giới hạn lớp học và ngân hàng câu hỏi</span></li>
        <li><span>✓</span><span>AI gợi ý câu hỏi theo chủ đề</span></li>
        <li><span>✓</span><span>Xuất báo cáo PDF/Excel</span></li>
      </ul>
      <form class="vip-form" method="POST" action="{{ route('teacher.vip.subscribe') }}">
        @csrf
        <input type="hidden" name="plan" value="monthly">
        @include('pages.teacher.partials.vip-bank-select')
        <button class="btn btn-primary w-full" type="submit">Thanh toán VNPay</button>
      </form>
    </article>

    <article class="vip-plan featured">
      <div class="vip-plan-badge">Tiết kiệm 30%</div>
      <div class="vip-plan-title">Pro năm</div>
      <div class="vip-price"><strong>{{ number_format($plans['yearly']['amount']) }}đ</strong><span>/ năm</span></div>
      <ul class="vip-features">
        <li><span>✓</span><span>Tất cả quyền lợi Pro tháng</span></li>
        <li><span>✓</span><span>Ưu tiên hỗ trợ trong ngày làm việc</span></li>
        <li><span>✓</span><span>Phù hợp giáo viên dùng thường xuyên</span></li>
      </ul>
      <form class="vip-form" method="POST" action="{{ route('teacher.vip.subscribe') }}">
        @csrf
        <input type="hidden" name="plan" value="yearly">
        @include('pages.teacher.partials.vip-bank-select')
        <button class="btn btn-primary w-full" type="submit">Thanh toán VNPay</button>
      </form>
    </article>

    <article class="vip-plan">
      <div class="vip-plan-title">Pro trọn đời</div>
      <div class="vip-price"><strong>{{ number_format($plans['lifetime']['amount']) }}đ</strong><span>/ một lần</span></div>
      <ul class="vip-features">
        <li><span>✓</span><span>Không cần gia hạn định kỳ</span></li>
        <li><span>✓</span><span>Nhận mọi cập nhật Pro tương lai</span></li>
        <li><span>✓</span><span>Phù hợp tài khoản cá nhân lâu dài</span></li>
      </ul>
      <form class="vip-form" method="POST" action="{{ route('teacher.vip.subscribe') }}">
        @csrf
        <input type="hidden" name="plan" value="lifetime">
        @include('pages.teacher.partials.vip-bank-select')
        <button class="btn btn-primary w-full" type="submit">Thanh toán VNPay</button>
      </form>
    </article>
  </section>

  <section class="vip-section-grid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Giao dịch gần nhất</h3>
      </div>
      <div class="card-content">
        @if($latestPayment)
          <ul class="vip-info-list">
            <li><span>Mã đơn</span><span class="vip-copy">{{ $latestPayment->txn_ref }}</span></li>
            <li><span>Gói</span><strong>{{ $planNames[$latestPayment->plan] ?? $latestPayment->plan }}</strong></li>
            <li><span>Số tiền</span><strong>{{ number_format($latestPayment->amount) }}đ</strong></li>
            <li><span>Trạng thái</span><span class="vip-status {{ $statusClasses[$latestPayment->status] ?? '' }}">{{ $statusNames[$latestPayment->status] ?? $latestPayment->status }}</span></li>
            <li><span>Thời gian</span><strong>{{ $latestPayment->created_at->format('d/m/Y H:i') }}</strong></li>
          </ul>
        @else
          <p style="margin:0;color:var(--muted-foreground);">Chưa có giao dịch VIP nào.</p>
        @endif
      </div>
    </div>

    <div class="card" id="vnpay-info">
      <div class="card-header">
        <h3 class="card-title">Thông tin kiểm thử</h3>
      </div>
      <div class="card-content">
        <ul class="vip-info-list">
          <li><span>IPN URL</span><span class="vip-copy">{{ $ipnUrl }}</span></li>
          <li><span>Ngân hàng</span><strong>NCB</strong></li>
          <li><span>Số thẻ</span><span class="vip-copy">9704198526191432198</span></li>
          <li><span>Chủ thẻ</span><strong>NGUYEN VAN A</strong></li>
          <li><span>Ngày phát hành</span><strong>07/15</strong></li>
          <li><span>OTP</span><strong>123456</strong></li>
        </ul>
      </div>
    </div>
  </section>
</div>
@endsection
