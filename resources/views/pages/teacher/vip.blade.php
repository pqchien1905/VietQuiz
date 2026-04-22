{{-- Teacher: vip --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.vip-hero { background:linear-gradient(135deg,#7c3aed,#2563eb); padding:3rem 1.5rem; text-align:center; color:#fff; border-radius:var(--radius-xl); margin-bottom:1.5rem; position:relative; overflow:hidden; }
    .vip-hero::before { content:''; position:absolute; inset:0; background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Ccircle cx='30' cy='30' r='29'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
    .plan-card { border:2px solid var(--border); border-radius:var(--radius-xl); padding:2rem; transition:all var(--transition-fast); cursor:pointer; position:relative; }
    .plan-card:hover { border-color:var(--primary); box-shadow:var(--shadow-lg); transform:translateY(-4px); }
    .plan-card.selected { border-color:var(--primary); box-shadow:0 0 0 4px color-mix(in srgb,var(--primary) 10%,transparent); }
    .plan-card.popular { border-color:var(--primary); }
    .feature-row { display:flex; align-items:center; gap:0.5rem; padding:0.5rem 0; border-bottom:1px solid var(--border); font-size:var(--text-sm); }
    .feature-row:last-child { border-bottom:none; }
    .check { color:var(--success); flex-shrink:0; }
    .cross { color:var(--destructive); flex-shrink:0; opacity:.5; }
</style>
@endpush

@section('content')
  <!-- Hero -->
      <div class="vip-hero stagger-children">
        <div style="position:relative;">
          <div style="font-size:3rem;margin-bottom:0.75rem;">💎</div>
          <h1 style="color:#fff;font-size:var(--text-4xl);margin-bottom:0.75rem;">Nâng cấp lên VietQuiz Pro</h1>
          <p style="color:rgba(255,255,255,.85);font-size:var(--text-lg);max-width:560px;margin:0 auto 1.5rem;">Mở khóa toàn bộ tính năng cao cấp để giảng dạy và học tập hiệu quả hơn. Không giới hạn, không quảng cáo.</p>
          <div style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,.15);border-radius:9999px;padding:0.375rem 1rem;font-size:var(--text-sm);font-weight:500;color:#fff;">
            ⏰ Ưu đãi đặc biệt: Giảm 30% — còn 3 ngày
          </div>
        </div>
      </div>

      <!-- Billing toggle -->
      <div style="display:flex;align-items:center;justify-content:center;gap:1rem;margin-bottom:1.5rem;" class="stagger-children">
        <span style="font-size:var(--text-sm);">Hàng tháng</span>
        <label class="switch">
          <input type="checkbox" id="billing-toggle" onchange="toggleBilling(this)" />
          <span class="switch-slider"></span>
        </label>
        <span style="font-size:var(--text-sm);">Hàng năm <span class="badge badge-success">Tiết kiệm 30%</span></span>
      </div>

      <!-- Plans -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:2rem;max-width:900px;margin-left:auto;margin-right:auto;" class="stagger-children">
        <!-- Free -->
        <div class="plan-card" onclick="selectPlan('free')">
          <div style="font-size:var(--text-lg);font-weight:700;margin-bottom:0.5rem;">Miễn phí</div>
          <div style="font-size:2.5rem;font-weight:800;margin-bottom:0.25rem;">0₫</div>
          <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1.5rem;">Mãi mãi</div>
          <button class="btn btn-outline w-full" disabled style="opacity:.6;cursor:default;">Gói hiện tại</button>
          <div style="margin-top:1.25rem;" id="feat-free"></div>
        </div>

        <!-- Pro -->
        <div class="plan-card popular selected" id="plan-pro" onclick="selectPlan('pro')">
          <div style="position:absolute;top:-1rem;left:50%;transform:translateX(-50%);"><span class="badge badge-solid-primary" style="padding:.375rem 1rem;">⭐ Phổ biến nhất</span></div>
          <div style="font-size:var(--text-lg);font-weight:700;margin-bottom:0.5rem;color:var(--primary);">Pro</div>
          <div style="font-size:2.5rem;font-weight:800;margin-bottom:0.25rem;color:var(--primary);" id="pro-price">199K₫</div>
          <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1.5rem;" id="pro-period">/ tháng</div>
          <button class="btn btn-primary w-full" onclick="checkout('pro')">Đăng ký ngay</button>
          <div style="margin-top:1.25rem;" id="feat-pro"></div>
        </div>

        <!-- Enterprise -->
        <div class="plan-card" onclick="selectPlan('enterprise')">
          <div style="font-size:var(--text-lg);font-weight:700;margin-bottom:0.5rem;">Doanh nghiệp</div>
          <div style="font-size:2rem;font-weight:800;margin-bottom:0.25rem;">Liên hệ</div>
          <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1.5rem;">Giá riêng cho tổ chức</div>
          <button class="btn btn-outline w-full" onclick="contactSales()">Liên hệ Tư vấn</button>
          <div style="margin-top:1.25rem;" id="feat-ent"></div>
        </div>
      </div>

      <!-- FAQ -->
      <div class="card stagger-children" style="max-width:700px;margin:0 auto;">
        <div class="card-header"><h3 class="card-title">Câu hỏi thường gặp</h3></div>
        <div class="card-content" style="padding-top:0;" id="vip-faq"></div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
// Feature rows
function fr(arr,bold){return arr.map(function(r){return '<div class="feature-row"><span>'+r[0]+'</span><span'+(bold?' style="font-weight:500;"':'')+'>'+r[1]+'</span></div>';}).join('');}
document.getElementById('feat-free').innerHTML=fr([['✅','Tối đa 3 lớp học'],['✅','50 câu hỏi/đề'],['✅','Chấm điểm tự động'],['❌','AI gợi ý điểm'],['❌','Phân tích nâng cao'],['❌','Xuất báo cáo PDF']]);
document.getElementById('feat-pro').innerHTML=fr([['✅','Không giới hạn lớp'],['✅','Không giới hạn câu hỏi'],['✅','AI gợi ý điểm tự luận'],['✅','Phân tích nâng cao'],['✅','Xuất báo cáo PDF'],['✅','Hỗ trợ ưu tiên 24/7']],true);
document.getElementById('feat-ent').innerHTML=fr([['✅','Tất cả tính năng Pro'],['✅','Quản lý toàn trường'],['✅','SSO / LDAP'],['✅','API access'],['✅','Onboarding riêng'],['✅','SLA 99.9% uptime']]);
// FAQ
var VF=[{q:'Có thể hủy đăng ký bất cứ lúc nào không?',a:'Có, bạn có thể hủy bất cứ lúc nào. Sau khi hủy, bạn vẫn sử dụng được đến hết chu kỳ đã thanh toán.'},{q:'Thanh toán những phương thức nào?',a:'Hỗ trợ thanh toán qua VNPay, Momo, thẻ ngân hàng nội địa, Visa/Mastercard và chuyển khoản ngân hàng.'},{q:'Dữ liệu của tôi có an toàn khi dùng bản miễn phí không?',a:'Dữ liệu của bạn luôn được bảo mật với mã hóa AES-256, bất kể gói nào bạn sử dụng.'},{q:'Có ưu đãi cho trường học hoặc tổ chức giáo dục không?',a:'Có, liên hệ với chúng tôi để được tư vấn gói Enterprise với mức giá ưu đãi đặc biệt cho tổ chức.'}];
document.getElementById('vip-faq').innerHTML=VF.map(function(f){return '<div class="accordion-item"><button class="accordion-trigger" onclick="this.closest(\'.accordion-item\').classList.toggle(\'.open\')"><span>'+f.q+'</span><svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg></button><div class="accordion-content">'+f.a+'</div></div>';}).join('');
// Fix accordion click
document.getElementById('vip-faq').addEventListener('click',function(e){var t=e.target.closest('.accordion-trigger');if(t)t.closest('.accordion-item').classList.toggle('open');});

var yearly=false;
window.toggleBilling=function(cb){yearly=cb.checked;document.getElementById('pro-price').textContent=yearly?'139K₫':'199K₫';document.getElementById('pro-period').textContent=yearly?'/ tháng (thanh toán năm)':'/ tháng';};
window.selectPlan=function(plan){document.querySelectorAll('.plan-card').forEach(function(c){c.classList.remove('selected');});if(plan==='pro')document.getElementById('plan-pro').classList.add('selected');};
window.checkout=function(){toast('Đang chuyển đến trang thanh toán...');setTimeout(function(){toast('Nâng cấp thành công! Chào mừng lên Pro 🎉');},2000);};
window.contactSales=function(){toast('Đội ngũ tư vấn sẽ liên hệ với bạn trong 1 ngày làm việc.');};
function toast(m){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-success';e.innerHTML='<span>✅</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
})();
</script>
@endpush
