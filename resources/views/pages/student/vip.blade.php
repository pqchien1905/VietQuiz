{{-- Student: vip --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $plan = $plans['monthly'];
  $isActive = $subscription?->is_active;
  $statusNames = ['pending' => 'Đang chờ', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'cancelled' => 'Đã hủy'];
  $statusClasses = ['pending' => 'vip-status-pending', 'paid' => 'vip-status-paid', 'failed' => 'vip-status-failed', 'cancelled' => 'vip-status-cancelled'];
@endphp

@push('styles')
<style>
  .vip-page{display:flex;flex-direction:column;gap:1.5rem}
  .vip-hero{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:1.25rem;align-items:stretch;padding:2rem;border:1px solid var(--border);border-radius:var(--radius-xl);background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 10%,var(--card)),var(--card))}
  .vip-kicker{display:inline-flex;align-items:center;gap:.45rem;width:max-content;padding:.35rem .75rem;border-radius:999px;background:color-mix(in srgb,var(--success) 12%,transparent);color:var(--success);font-size:var(--text-sm);font-weight:800}
  .vip-hero h1{margin:.85rem 0 .75rem;font-size:clamp(1.8rem,3vw,2.65rem);line-height:1.08;letter-spacing:0}
  .vip-hero p{max-width:720px;color:var(--muted-foreground);font-size:var(--text-base);line-height:1.7}
  .vip-summary{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1.25rem;display:flex;flex-direction:column;gap:.75rem}
  .vip-summary-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;font-size:var(--text-sm)}
  .vip-summary-row strong{font-size:var(--text-base)}
  .vip-plan{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,.45fr);gap:1rem;align-items:stretch}
  .vip-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1.25rem}
  .vip-offer{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 10%,transparent)}
  .vip-price{display:flex;align-items:flex-end;gap:.45rem;margin:.75rem 0}
  .vip-price strong{font-size:2.4rem;line-height:1;color:var(--primary)}
  .vip-price span{font-size:var(--text-sm);color:var(--muted-foreground);padding-bottom:.2rem}
  .vip-features{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem;margin:1rem 0 0;padding:0;list-style:none}
  .vip-features li{display:flex;align-items:flex-start;gap:.5rem;color:var(--muted-foreground);font-size:var(--text-sm)}
  .vip-check{color:var(--success);font-weight:900;line-height:1.35}
  .vip-form{display:flex;flex-direction:column;gap:.8rem;margin-top:1rem}
  .vip-form select{width:100%;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--background);color:var(--foreground);padding:.65rem .75rem;font-size:var(--text-sm)}
  .vip-alert{padding:1rem;border-radius:var(--radius-lg);border:1px solid var(--border);background:var(--card)}
  .vip-alert-success{border-color:color-mix(in srgb,var(--success) 35%,var(--border));background:color-mix(in srgb,var(--success) 8%,var(--card));color:var(--success)}
  .vip-alert-error{border-color:color-mix(in srgb,var(--destructive) 35%,var(--border));background:color-mix(in srgb,var(--destructive) 8%,var(--card));color:var(--destructive)}
  .vip-status{display:inline-flex;align-items:center;padding:.28rem .6rem;border-radius:999px;font-size:.75rem;font-weight:800}
  .vip-status-pending{color:#a16207;background:#fef3c7}
  .vip-status-paid{color:#047857;background:#d1fae5}
  .vip-status-failed,.vip-status-cancelled{color:#b91c1c;background:#fee2e2}
  .vip-info-list{display:flex;flex-direction:column;gap:.75rem;margin:0;padding:0;list-style:none;font-size:var(--text-sm)}
  .vip-info-list li{display:flex;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--border);padding-bottom:.75rem}
  .vip-info-list li:last-child{border-bottom:0;padding-bottom:0}
  .vip-copy{word-break:break-all;color:var(--muted-foreground);text-align:right}
  @media (max-width:900px){.vip-hero,.vip-plan{grid-template-columns:1fr}.vip-features{grid-template-columns:1fr}}
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
      <div class="vip-kicker">Bỏ quảng cáo khi học</div>
      <h1>Học liền mạch, không bị chen quảng cáo.</h1>
      <p>Gói này chỉ dành cho học sinh: bỏ quảng cáo trên màn học, màn làm quiz và màn xem bài tập. Không mở thêm tính năng giáo viên, không gói phức tạp, giá rẻ để tập trung học.</p>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem;">
        <a href="#vip-plan" class="btn btn-primary">Xem gói</a>
        <a href="{{ route('student.dashboard') }}" class="btn btn-outline">Về học tiếp</a>
      </div>
    </div>

    <aside class="vip-summary">
      <div class="vip-summary-row"><span>Trạng thái</span><strong>{{ $isActive ? 'Đã kích hoạt' : 'Chưa kích hoạt' }}</strong></div>
      <div class="vip-summary-row"><span>Gói hiện tại</span><strong>{{ $isActive ? 'Bỏ quảng cáo' : 'Miễn phí' }}</strong></div>
      <div class="vip-summary-row"><span>Hiệu lực đến</span><strong>{{ $subscription?->expires_at ? $subscription->expires_at->format('d/m/Y') : ($isActive ? 'Không giới hạn' : '—') }}</strong></div>
      @if($isActive)
        <form method="POST" action="{{ route('student.vip.cancel') }}" data-confirm="Hủy gói bỏ quảng cáo? Bạn vẫn dùng được đến hết chu kỳ đã thanh toán." data-confirm-ok="Hủy gói">
          @csrf
          <button class="btn btn-outline w-full" type="submit">Hủy gia hạn</button>
        </form>
      @endif
    </aside>
  </section>

  <section id="vip-plan" class="vip-plan">
    <article class="vip-card vip-offer">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div>
          <div class="vip-kicker">Gói duy nhất cho học sinh</div>
          <h2 style="font-size:var(--text-2xl);margin:.75rem 0 .25rem;">Bỏ quảng cáo khi học</h2>
          <p style="color:var(--muted-foreground);margin:0;">Một gói đơn giản để màn học sạch hơn và ít gián đoạn hơn.</p>
        </div>
        @if($isActive)
          <span class="badge badge-success">Đang sử dụng</span>
        @endif
      </div>

      <div class="vip-price">
        <strong>{{ number_format($plan['amount']) }}đ</strong>
        <span>/ tháng</span>
      </div>

      <ul class="vip-features">
        <li><span class="vip-check">✓</span><span>Không hiển thị quảng cáo trên màn học và làm bài.</span></li>
        <li><span class="vip-check">✓</span><span>Không chen quảng cáo giữa các câu hỏi quiz.</span></li>
        <li><span class="vip-check">✓</span><span>Giữ nguyên toàn bộ dữ liệu học tập hiện có.</span></li>
        <li><span class="vip-check">✓</span><span>Hủy gia hạn bất cứ lúc nào trong tài khoản.</span></li>
      </ul>

      @unless($isActive)
        <form class="vip-form" method="POST" action="{{ route('student.vip.subscribe') }}">
          @csrf
          <input type="hidden" name="plan" value="monthly">
          <label>
            <span style="display:block;margin-bottom:.35rem;font-size:var(--text-sm);font-weight:700;">Phương thức thanh toán</span>
            <select name="bank_code">
              <option value="">Chọn tại cổng VNPay</option>
              <option value="VNPAYQR">VNPay QR</option>
              <option value="VNBANK">Thẻ ATM / tài khoản nội địa</option>
              <option value="INTCARD">Thẻ quốc tế</option>
              <option value="NCB">NCB sandbox</option>
            </select>
          </label>
          <button class="btn btn-primary w-full" type="submit">Thanh toán qua VNPay</button>
        </form>
      @endunless
    </article>

    <aside class="vip-card">
      <h3 class="card-title" style="margin-bottom:.75rem;">Giao dịch gần nhất</h3>
      @if($latestPayment)
        <ul class="vip-info-list">
          <li><span>Mã đơn</span><span class="vip-copy">{{ $latestPayment->txn_ref }}</span></li>
          <li><span>Số tiền</span><strong>{{ number_format($latestPayment->amount) }}đ</strong></li>
          <li><span>Trạng thái</span><span class="vip-status {{ $statusClasses[$latestPayment->status] ?? '' }}">{{ $statusNames[$latestPayment->status] ?? $latestPayment->status }}</span></li>
          <li><span>Thời gian</span><span class="vip-copy">{{ $latestPayment->created_at?->format('d/m/Y H:i') }}</span></li>
        </ul>
      @else
        <p style="color:var(--muted-foreground);font-size:var(--text-sm);line-height:1.6;">Bạn chưa có giao dịch nào. Sau khi thanh toán, trạng thái giao dịch sẽ xuất hiện tại đây.</p>
      @endif
    </aside>
  </section>

  <section class="vip-card">
    <h3 class="card-title" style="margin-bottom:.75rem;">Câu hỏi thường gặp</h3>
    <div class="accordion-item open">
      <button class="accordion-trigger" type="button" onclick="this.closest('.accordion-item').classList.toggle('open')">
        <span>Gói này có mở tính năng giáo viên không?</span>
        <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="accordion-content">Không. Gói học sinh này chỉ bỏ quảng cáo khi học, không mở tính năng tạo lớp, tạo đề hoặc công cụ giáo viên.</div>
    </div>
    <div class="accordion-item">
      <button class="accordion-trigger" type="button" onclick="this.closest('.accordion-item').classList.toggle('open')">
        <span>Sau khi hủy thì quảng cáo quay lại khi nào?</span>
        <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="accordion-content">Bạn vẫn dùng được đến hết chu kỳ đã thanh toán. Sau ngày hết hạn, tài khoản trở về gói miễn phí.</div>
    </div>
    <div class="accordion-item">
      <button class="accordion-trigger" type="button" onclick="this.closest('.accordion-item').classList.toggle('open')">
        <span>Thanh toán qua đâu?</span>
        <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="accordion-content">Trang dùng luồng thanh toán VNPay hiện có của dự án. Nếu môi trường chưa cấu hình VNPay, hệ thống sẽ báo lỗi cấu hình thay vì tạo giao dịch lỗi.</div>
    </div>
  </section>

  <div id="toast-container"></div>
</div>
@endsection
