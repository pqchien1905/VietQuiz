{{-- Student: help --}}
@extends('layouts.dashboard', ['role' => 'student'])

@section('content')
  <div class="page-header stagger-children">
        <h1>Trung tâm Trợ giúp</h1>
        <p style="color:var(--muted-foreground);">Tìm câu trả lời nhanh chóng hoặc liên hệ hỗ trợ</p>
      </div>

      <!-- Search -->
      <div class="card stagger-children" style="margin-bottom:1.5rem;background:linear-gradient(135deg,var(--primary),color-mix(in srgb,var(--primary) 70%,var(--info)));padding:2rem;text-align:center;">
        <div style="color:#fff;font-size:var(--text-2xl);font-weight:700;margin-bottom:.5rem;">Chúng tôi có thể giúp gì cho bạn?</div>
        <div style="color:rgba(255,255,255,.8);margin-bottom:1.25rem;font-size:var(--text-sm);">Tìm kiếm câu trả lời trong hàng trăm bài hướng dẫn</div>
        <div style="max-width:500px;margin:0 auto;display:flex;gap:.5rem;">
          <div class="search-input-wrapper" style="flex:1;background:#fff;border-radius:var(--radius-md);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="search-icon" style="color:var(--muted-foreground);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" class="input has-icon" placeholder="VD: làm thế nào để nộp bài..." style="background:transparent;border:none;box-shadow:none;" id="help-search" oninput="filterFaq(this.value)" />
          </div>
          <button class="btn btn-outline" style="background:#fff;flex-shrink:0;">Tìm kiếm</button>
        </div>
      </div>

      <!-- Quick cards -->
      <div class="cards-grid stagger-children" style="margin-bottom:1.5rem;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));" id="quick-cards"></div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;" class="stagger-children">
        <!-- FAQ -->
        <div>
          <h2 style="font-size:var(--text-xl);font-weight:700;margin-bottom:1rem;">Câu hỏi thường gặp</h2>
          <div class="card" id="faq-container">
            <div class="card-content" style="padding:0;" id="faq-list"></div>
          </div>
        </div>

        <!-- Contact + Ticket -->
        <div style="display:flex;flex-direction:column;gap:1rem;">
          <!-- Contact cards -->
          <div class="card">
            <div class="card-header"><h3 class="card-title">Liên hệ Hỗ trợ</h3></div>
            <div class="card-content" style="padding-top:0;display:flex;flex-direction:column;gap:.75rem;" id="contact-list"></div>
          </div>

          <!-- Submit ticket -->
          <div class="card">
            <div class="card-header"><h3 class="card-title">Gửi yêu cầu hỗ trợ</h3><p class="card-description">Mô tả vấn đề bạn gặp phải, chúng tôi sẽ phản hồi trong 24h</p></div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:.875rem;">
              <div class="form-group">
                <label class="label">Loại vấn đề</label>
                <select class="input select"><option>Không làm được bài kiểm tra</option><option>Lỗi đăng nhập</option><option>Không xem được điểm</option><option>Vấn đề khác</option></select>
              </div>
              <div class="form-group"><label class="label label-required">Mô tả vấn đề</label><textarea class="input" style="min-height:5rem;" placeholder="Mô tả chi tiết vấn đề bạn gặp phải và các bước bạn đã thử..." id="ticket-desc"></textarea></div>
              <div class="form-group">
                <label class="label">Ảnh chụp màn hình (tùy chọn)</label>
                <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:1.25rem;text-align:center;cursor:pointer;" class="hover-lift">
                  <div style="font-size:1.5rem;margin-bottom:.25rem;">📎</div>
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Kéo thả hoặc nhấp để đính kèm</div>
                </div>
              </div>
            </div>
            <div class="card-footer">
              <button class="btn btn-primary w-full" id="ticket-btn" onclick="submitTicket()">Gửi yêu cầu hỗ trợ</button>
            </div>
          </div>
        </div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var CARDS=[{icon:'\ud83d\udcdd',title:'L\u00e0m b\u00e0i ki\u1ec3m tra',desc:'H\u01b0\u1edbng d\u1eabn l\u00e0m b\u00e0i',faq:'faq-quiz'},{icon:'\ud83d\udce4',title:'N\u1ed9p b\u00e0i t\u1eadp',desc:'C\u00e1ch n\u1ed9p v\u00e0 xem \u0111i\u1ec3m',faq:'faq-assign'},{icon:'\ud83d\udd11',title:'Tham gia l\u1edbp',desc:'S\u1eed d\u1ee5ng m\u00e3 l\u1edbp h\u1ecdc',faq:'faq-join'},{icon:'\ud83d\udcca',title:'Xem \u0111i\u1ec3m s\u1ed1',desc:'Tra c\u1ee9u b\u1ea3ng \u0111i\u1ec3m',faq:'faq-grades'}];
  document.getElementById('quick-cards').innerHTML=CARDS.map(function(c){return '<a href="#'+c.faq+'" class="card hover-lift" style="text-decoration:none;cursor:pointer;" onclick="scrollToFaq(event,\''+c.faq+'\')">'+'<div class="card-content" style="text-align:center;padding:1.25rem .75rem;">'+'<div style="font-size:2rem;margin-bottom:.5rem;">'+c.icon+'</div>'+'<div style="font-weight:600;font-size:var(--text-sm);margin-bottom:.25rem;">'+c.title+'</div>'+'<div style="font-size:var(--text-xs);color:var(--muted-foreground);">'+c.desc+'</div>'+'</div></a>';}).join('');

  var FAQS=[{id:'faq-quiz',q:'L\u00e0m th\u1ebf n\u00e0o \u0111\u1ec3 b\u1eaft \u0111\u1ea7u l\u00e0m b\u00e0i ki\u1ec3m tra?',a:'V\u00e0o m\u1ee5c "B\u00e0i ki\u1ec3m tra" \u2192 ch\u1ecdn b\u00e0i thi \u2192 nh\u1ea5n "L\u00e0m b\u00e0i". \u0110\u1ea3m b\u1ea3o c\u00f3 k\u1ebft n\u1ed1i internet \u1ed5n \u0111\u1ecbnh trong su\u1ed1t qu\u00e1 tr\u00ecnh l\u00e0m b\u00e0i. B\u00e0i s\u1ebd t\u1ef1 n\u1ed9p khi h\u1ebft gi\u1edd.'},{id:'faq-assign',q:'C\u00e1ch n\u1ed9p b\u00e0i t\u1eadp nh\u01b0 th\u1ebf n\u00e0o?',a:'V\u00e0o "B\u00e0i t\u1eadp" \u2192 ch\u1ecdn b\u00e0i c\u1ea7n n\u1ed9p \u2192 nh\u1ea5n "N\u1ed9p b\u00e0i" \u2192 t\u1ea3i file ho\u1eb7c nh\u1eadp c\u00e2u tr\u1ea3 l\u1eddi \u2192 x\u00e1c nh\u1eadn. B\u1ea1n c\u00f3 th\u1ec3 n\u1ed9p l\u1ea1i tr\u01b0\u1edbc h\u1ea1n ch\u00f3t n\u1ebfu c\u1ea7n.'},{id:'faq-join',q:'L\u00e0m sao \u0111\u1ec3 tham gia l\u1edbp h\u1ecdc m\u1edbi?',a:'V\u00e0o m\u1ee5c "Tham gia l\u1edbp" \u2192 nh\u1eadp m\u00e3 l\u1edbp do gi\u00e1o vi\u00ean cung c\u1ea5p (VD: VQ-10A) \u2192 nh\u1ea5n "Tham gia". M\u00e3 l\u1edbp th\u01b0\u1eddng \u0111\u01b0\u1ee3c chia s\u1ebb qua nh\u00f3m l\u1edbp.'},{id:'faq-grades',q:'Khi n\u00e0o \u0111i\u1ec3m b\u00e0i ki\u1ec3m tra \u0111\u01b0\u1ee3c c\u1eadp nh\u1eadt?',a:'B\u00e0i tr\u1eafc nghi\u1ec7m: \u0111i\u1ec3m hi\u1ec3n th\u1ecb ngay sau khi n\u1ed9p. B\u00e0i t\u1ef1 lu\u1eadn ho\u1eb7c b\u00e0i t\u1eadp: ch\u1edd gi\u00e1o vi\u00ean ch\u1ea5m, th\u01b0\u1eddng trong v\u00f2ng 3-5 ng\u00e0y l\u00e0m vi\u1ec7c.'},{id:'faq-timer',q:'\u0110i\u1ec1u g\u00ec x\u1ea3y ra khi h\u1ebft gi\u1edd l\u00e0m b\u00e0i?',a:'H\u1ec7 th\u1ed1ng t\u1ef1 \u0111\u1ed9ng n\u1ed9p b\u00e0i v\u1edbi nh\u1eefng c\u00e2u \u0111\u00e3 tr\u1ea3 l\u1eddi. C\u00e2u ch\u01b0a tr\u1ea3 l\u1eddi s\u1ebd b\u1ecb t\u00ednh l\u00e0 sai. C\u00f3 c\u1ea3nh b\u00e1o 5 ph\u00fat v\u00e0 1 ph\u00fat tr\u01b0\u1edbc khi h\u1ebft gi\u1edd.'},{id:'faq-save',q:'B\u00e0i l\u00e0m c\u00f3 \u0111\u01b0\u1ee3c t\u1ef1 l\u01b0u kh\u00f4ng?',a:'C\u00f3, b\u00e0i l\u00e0m \u0111\u01b0\u1ee3c t\u1ef1 l\u01b0u m\u1ed7i 30 gi\u00e2y v\u00e0o b\u1ed9 nh\u1edb t\u1ea1m. N\u1ebfu m\u1ea5t k\u1ebft n\u1ed1i v\u00e0 \u0111\u0103ng nh\u1eadp l\u1ea1i, ti\u1ebfn \u0111\u1ed9 s\u1ebd \u0111\u01b0\u1ee3c ph\u1ee5c h\u1ed3i t\u1eeb l\u1ea7n l\u01b0u cu\u1ed1i c\u00f9ng.'}];
  document.getElementById('faq-list').innerHTML=FAQS.map(function(f){return '<div class="accordion-item" id="'+f.id+'"><button class="accordion-trigger" onclick="this.closest(\'.accordion-item\').classList.toggle(\'open\')"><span>'+f.q+'</span><svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg></button><div class="accordion-content">'+f.a+'</div></div>';}).join('');

  var CONTACTS=[{icon:'\ud83d\udcac',title:'Chat tr\u1ef1c ti\u1ebfp',desc:'Ph\u1ea3n h\u1ed3i trong v\u00e0i ph\u00fat',action:'B\u1eaft \u0111\u1ea7u chat',color:'var(--primary)'},{icon:'\u2709\ufe0f',title:'Email h\u1ed7 tr\u1ee3',desc:'support@vietquiz.vn',action:'G\u1eedi email',color:'var(--success)'},{icon:'\ud83d\udcda',title:'Video h\u01b0\u1edbng d\u1eabn',desc:'Th\u01b0 vi\u1ec7n video chi ti\u1ebft',action:'Xem video',color:'var(--info)'}];
  document.getElementById('contact-list').innerHTML=CONTACTS.map(function(c){return '<div style="display:flex;align-items:center;gap:.875rem;padding:.875rem;border:1px solid var(--border);border-radius:var(--radius-md);transition:all var(--transition-fast);" class="hover-lift"><div style="width:2.75rem;height:2.75rem;border-radius:var(--radius-md);background:color-mix(in srgb,'+c.color+' 12%,transparent);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">'+c.icon+'</div><div style="flex:1;"><div style="font-weight:600;font-size:var(--text-sm);">'+c.title+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">'+c.desc+'</div></div><button class="btn btn-ghost btn-sm" style="color:'+c.color+';" onclick="contactAction(\''+c.title+'\')">' +c.action+' \u2192</button></div>';}).join('');

  window.filterFaq=function(query){var q=query.toLowerCase();document.querySelectorAll('.accordion-item').forEach(function(item){var text=item.textContent.toLowerCase();item.style.display=(!q||text.indexOf(q)!==-1)?'':'none';});};
  window.scrollToFaq=function(e,id){e.preventDefault();var el=document.getElementById(id);if(el){el.classList.add('open');el.scrollIntoView({behavior:'smooth',block:'center'});}};
  window.contactAction=function(title){toast('\u0110ang k\u1ebft n\u1ed1i v\u1edbi '+title+'...','info');};
  window.submitTicket=function(){var desc=document.getElementById('ticket-desc').value.trim();if(!desc){toast('Vui l\u00f2ng m\u00f4 t\u1ea3 v\u1ea5n \u0111\u1ec1 c\u1ee7a b\u1ea1n.','info');return;}var btn=document.getElementById('ticket-btn');btn.disabled=true;btn.textContent='\u0110ang g\u1eedi...';setTimeout(function(){toast('\u0110\u00e3 g\u1eedi y\u00eau c\u1ea7u h\u1ed7 tr\u1ee3! Ch\u00fang t\u00f4i s\u1ebd ph\u1ea3n h\u1ed3i trong 24h.','success');btn.disabled=false;btn.textContent='G\u1eedi y\u00eau c\u1ea7u h\u1ed7 tr\u1ee3';document.getElementById('ticket-desc').value='';},1500);};
  function toast(m,t){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-'+(t||'success');var icon=t==='info'?'\u2139\ufe0f':'\u2705';e.innerHTML='<span>'+icon+'</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
})();
</script>
@endpush
