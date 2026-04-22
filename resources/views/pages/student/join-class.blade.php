{{-- Student: join-class --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
.code-input { display:flex; gap:.5rem; justify-content:center; margin:1.5rem 0; }
    .code-digit { width:3rem; height:3.5rem; border:2px solid var(--border); border-radius:var(--radius-md); font-size:var(--text-2xl); font-weight:700; text-align:center; background:var(--background); color:var(--foreground); transition:border-color var(--transition-fast); }
    .code-digit:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 15%,transparent); }
    .class-preview { border:2px solid var(--primary); border-radius:var(--radius-xl); padding:1.5rem; background:color-mix(in srgb,var(--primary) 4%,transparent); text-align:center; animation:fade-in-up .3s ease; }
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
        <h1>Tham gia Lớp học</h1>
        <p style="color:var(--muted-foreground);">Nhập mã lớp do giáo viên cung cấp để tham gia</p>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;" class="stagger-children">
        <!-- Join form -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Nhập mã lớp</h3>
            <p class="card-description">Mã lớp gồm 6 ký tự được giáo viên cấp</p>
          </div>
          <div class="card-content" style="text-align:center;">
            <div style="font-size:3rem;margin-bottom:.5rem;">🔑</div>
            <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1rem;">Nhập từng ký tự của mã lớp</p>

            <div class="code-input">
              <input type="text" class="code-digit" id="d0" maxlength="1" oninput="nextDigit(this,1)" onkeydown="prevDigit(event,0)" />
              <input type="text" class="code-digit" id="d1" maxlength="1" oninput="nextDigit(this,2)" onkeydown="prevDigit(event,1)" />
              <span style="display:flex;align-items:center;font-size:var(--text-xl);color:var(--muted-foreground);">-</span>
              <input type="text" class="code-digit" id="d2" maxlength="1" oninput="nextDigit(this,3)" onkeydown="prevDigit(event,2)" />
              <input type="text" class="code-digit" id="d3" maxlength="1" oninput="nextDigit(this,4)" onkeydown="prevDigit(event,3)" />
              <input type="text" class="code-digit" id="d4" maxlength="1" oninput="nextDigit(this,5)" onkeydown="prevDigit(event,4)" />
            </div>

            <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:1.25rem;">Hoặc nhập toàn bộ mã vào ô bên dưới</p>

            <div style="display:flex;gap:.5rem;max-width:320px;margin:0 auto;">
              <input type="text" class="input" id="full-code" placeholder="VD: VQ-10A" style="flex:1;text-transform:uppercase;letter-spacing:.1em;" oninput="this.value=this.value.toUpperCase()" />
              <button class="btn btn-primary" onclick="lookupCode()">Tìm</button>
            </div>

            <!-- Class preview -->
            <div class="class-preview" id="class-preview" style="display:none;margin-top:1.25rem;">
              <div style="font-size:2rem;margin-bottom:.5rem;" id="preview-icon">📐</div>
              <h3 style="font-size:var(--text-xl);font-weight:700;" id="preview-name">Lớp Toán Đại số</h3>
              <p style="font-size:var(--text-sm);color:var(--muted-foreground);" id="preview-teacher">GV. Nguyễn Văn An</p>
              <div style="display:flex;justify-content:center;gap:1.5rem;margin:1rem 0;font-size:var(--text-sm);">
                <div><span class="badge badge-primary" id="preview-students">32 học sinh</span></div>
                <div><span class="badge badge-outline" id="preview-subject">Toán học</span></div>
              </div>
              <button class="btn btn-primary w-full" onclick="joinClass()">Tham gia Lớp này</button>
            </div>
          </div>
        </div>

        <!-- Recent + enrolled -->
        <div style="display:flex;flex-direction:column;gap:1rem;">
          <div class="card">
            <div class="card-header"><h3 class="card-title">Gợi ý lớp học</h3><p class="card-description">Theo gợi ý từ trường học của bạn</p></div>
            <div class="card-content" style="padding-top:0;" id="suggestions-list"></div>
          </div>

          <div class="card">
            <div class="card-header"><h3 class="card-title">Lớp đang tham gia</h3></div>
            <div class="card-content" style="padding-top:0;" id="enrolled-list"></div>
          </div>
        </div>
      </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  var SUGGESTIONS=[{name:'Kỹ thuật Phần mềm',teacher:'GV. Đỗ Thị Lan',code:'VQ-KT1',color:'#06b6d4',icon:'⚙️',members:25},{name:'Hệ điều hành',teacher:'GV. Vũ Đình Trung',code:'VQ-HDH',color:'#f97316',icon:'💾',members:30},{name:'An toàn Thông tin',teacher:'GV. Lê Quốc Bảo',code:'VQ-ATTT',color:'#ef4444',icon:'🔒',members:28}];
  var ENROLLED=[{name:'Phát triển Web',code:'VQ-PW1',color:'#3b82f6'},{name:'Cấu trúc Dữ liệu',code:'VQ-CTD',color:'#f97316'},{name:'Thiết kế CSDL',code:'VQ-CSS',color:'#22c55e'},{name:'Mạng Máy tính',code:'VQ-MMT',color:'#a855f7'}];
  document.getElementById('suggestions-list').innerHTML=SUGGESTIONS.map(function(c){return '<div style="display:flex;align-items:center;gap:.75rem;padding:.875rem 0;border-top:1px solid var(--border);"><div style="width:2.5rem;height:2.5rem;border-radius:var(--radius-md);background:'+c.color+'20;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">'+c.icon+'</div><div style="flex:1;"><div style="font-weight:500;font-size:var(--text-sm);">'+c.name+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">'+c.teacher+' · '+c.members+' học sinh</div></div><button class="btn btn-outline btn-sm" onclick="quickJoin(\''+c.code+'\',\''+c.name+'\')"Tham gia</button></div>';}).join('');
  document.getElementById('enrolled-list').innerHTML=ENROLLED.map(function(c){return '<div style="display:flex;align-items:center;gap:.625rem;padding:.5rem 0;border-top:1px solid var(--border);"><div style="width:.5rem;height:.5rem;border-radius:50%;background:'+c.color+';flex-shrink:0;"></div><span style="flex:1;font-size:var(--text-sm);">'+c.name+'</span><span style="font-size:var(--text-xs);color:var(--muted-foreground);">'+c.code+'</span><svg style="color:var(--success);" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>';}).join('');
  var CLASS_DB={'VQ-10A':{name:'Lớp 10A — Toán Đại số',teacher:'GV. Nguyễn Văn An',icon:'📐',students:32,subject:'Toán học'},'VQ-PW1':{name:'Phát triển Web',teacher:'GV. Nguyễn Văn An',icon:'💻',students:32,subject:'Công nghệ'},'VQ-KT1':{name:'Kỹ thuật Phần mềm',teacher:'GV. Đỗ Thị Lan',icon:'⚙️',students:25,subject:'Công nghệ'},'VQ-HDH':{name:'Hệ điều hành',teacher:'GV. Vũ Đình Trung',icon:'💾',students:30,subject:'Công nghệ'}};
  window.nextDigit=function(el,nextIdx){if(el.value.length===1&&nextIdx<=4){var d=document.getElementById('d'+nextIdx);if(d)d.focus();}};
  window.prevDigit=function(e,idx){if(e.key==='Backspace'&&idx>0&&!e.target.value){var d=document.getElementById('d'+(idx-1));if(d)d.focus();}};
  window.lookupCode=function(){var code=document.getElementById('full-code').value.trim().toUpperCase();var cls=CLASS_DB[code];if(!cls){toast('Không tìm thấy lớp với mã: '+code,'error');return;}showPreview(cls);};
  function showPreview(cls){document.getElementById('preview-name').textContent=cls.name;document.getElementById('preview-teacher').textContent=cls.teacher;document.getElementById('preview-icon').textContent=cls.icon;document.getElementById('preview-students').textContent=cls.students+' học sinh';document.getElementById('preview-subject').textContent=cls.subject;document.getElementById('class-preview').style.display='';}
  window.joinClass=function(){toast('Đã tham gia lớp học thành công! Chuyển đến trang khóa học...','success');setTimeout(function(){window.location.href='{{ route('student.courses') }}';},1500);};
  window.quickJoin=function(code,name){var cls=CLASS_DB[code];if(cls){showPreview(cls);document.getElementById('full-code').value=code;}else toast('Đã gửi yêu cầu tham gia: '+name,'success');};
  function toast(m,t){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-'+(t||'success');e.innerHTML='<span>'+(t==='error'?'❌':'✅')+'</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);}
})();
</script>
@endpush
