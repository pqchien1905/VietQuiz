{{-- Teacher: students --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.stu-avatar{width:2.5rem;height:2.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:var(--text-sm);font-weight:700;flex-shrink:0}
    .perf-dot{width:.5rem;height:.5rem;border-radius:50%;display:inline-block;margin-right:.25rem}
    .inv-tag{display:inline-flex;align-items:center;gap:.25rem;padding:.25rem .625rem;background:var(--primary);color:var(--primary-foreground);border-radius:var(--radius-full);font-size:var(--text-xs);font-weight:500}
    .inv-tag button{background:none;border:none;color:inherit;cursor:pointer;padding:0;font-size:.875rem;line-height:1;opacity:.7}
    .inv-tag button:hover{opacity:1}
    .inv-link-box{display:flex;align-items:center;gap:.5rem;padding:.75rem;background:var(--muted);border-radius:var(--radius-md);font-size:var(--text-sm);font-family:monospace;word-break:break-all}
    .inv-link-box span{flex:1;color:var(--muted-foreground)}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div><h1>Học sinh</h1><p style="color:var(--muted-foreground);">Quản lý và theo dõi học sinh của bạn</p></div>
          <button class="btn btn-primary gap-2" onclick="openInvModal()"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Mời học sinh</button>
        </div>
      </div>
      <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng học sinh</div><div class="stat-card__value" id="st-t">0</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Giỏi (≥8)</div><div class="stat-card__value" style="color:var(--success);" id="st-g">0</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Khá (6-7.9)</div><div class="stat-card__value" style="color:var(--info);" id="st-k">0</div></div>
        <div class="stat-card"><div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">TB & Yếu (<6)</div><div class="stat-card__value" style="color:var(--warning);" id="st-y">0</div></div>
      </div>
      <div class="toolbar stagger-children">
        <div class="toolbar-left">
          <div class="search-input-wrapper" style="max-width:300px;flex:1;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="search" class="input" placeholder="Tìm học sinh..." id="s-search" oninput="applyFilters()" style="font-size:var(--text-sm);" /></div>
          <select class="input select" id="s-cls" onchange="applyFilters()" style="max-width:140px;font-size:var(--text-sm);"><option value="">Tất cả lớp</option><option>10A</option><option>11B</option><option>9C</option><option>10B</option><option>12A</option></select>
          <select class="input select" id="s-perf" onchange="applyFilters()" style="max-width:140px;font-size:var(--text-sm);"><option value="">Tất cả</option><option value="good">Giỏi</option><option value="ok">Khá</option><option value="weak">TB & Yếu</option></select>
        </div>
        <div class="toolbar-right"><span style="font-size:var(--text-sm);color:var(--muted-foreground);" id="s-cnt">0 học sinh</span></div>
      </div>
      <div class="card stagger-children" style="margin-top:1rem;">
        <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Học sinh</th><th>Lớp</th><th>Điểm TB</th><th>Bài nộp</th><th>Tỉ lệ hoàn thành</th><th>Xếp loại</th><th></th></tr></thead><tbody id="s-table"></tbody></table></div>
      </div>

<div class="modal-overlay" id="inv-modal">
  <div class="modal" style="max-width:34rem;">
    <div class="modal-header">
      <div><h3 class="modal-title">Mời học sinh</h3><p class="modal-desc">Mời qua email hoặc chia sẻ link</p></div>
      <button class="modal-close" onclick="closeInvModal()">✕</button>
    </div>
    <div class="modal-body" style="display:flex;flex-direction:column;gap:1.25rem;">
      <div style="display:flex;gap:.75rem;border-bottom:1px solid var(--border);padding-bottom:.75rem;">
        <button class="tab-btn active" id="inv-tab-email" onclick="switchInvTab('email')" style="border:none;padding:.5rem 1rem;">📧 Mời qua Email</button>
        <button class="tab-btn" id="inv-tab-link" onclick="switchInvTab('link')" style="border:none;padding:.5rem 1rem;">🔗 Chia sẻ Link</button>
      </div>
      <div id="inv-email-panel">
        <div class="form-group"><label class="label">Lớp</label><select class="input select" id="inv-cls"><option>10A</option><option>11B</option><option>9C</option><option>10B</option><option>12A</option></select></div>
        <div class="form-group" style="margin-top:.75rem;"><label class="label label-required">Email học sinh</label>
          <div style="display:flex;gap:.5rem;"><input type="email" class="input" placeholder="Nhập email..." id="inv-email" style="flex:1;" /><button class="btn btn-outline btn-sm" onclick="addEmail()">Thêm</button></div>
        </div>
        <div id="inv-tags" style="display:flex;flex-wrap:wrap;gap:.375rem;margin-top:.75rem;"></div>
        <div id="inv-err" style="color:var(--destructive);font-size:var(--text-sm);margin-top:.375rem;display:none;"></div>
      </div>
      <div id="inv-link-panel" style="display:none;">
        <div class="form-group"><label class="label">Lớp</label><select class="input select" id="inv-link-cls"><option>10A</option><option>11B</option><option>9C</option><option>10B</option><option>12A</option></select></div>
        <div style="margin-top:.75rem;"><label class="label">Link mời</label>
          <div class="inv-link-box"><span id="inv-link-url">https://vietquiz.edu.vn/join/10A-abc123</span><button class="btn btn-outline btn-sm" onclick="copyLink()">📋 Sao chép</button></div>
        </div>
        <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.5rem;">Link có hiệu lực 7 ngày. Học sinh có link sẽ tự động tham gia lớp.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeInvModal()">Hủy</button>
      <button class="btn btn-primary" id="inv-send-btn" onclick="sendInvites()"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.375rem;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Gửi lời mời</button>
    </div>
  </div>
</div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
var colors=['#3b82f6','#ef4444','#22c55e','#f97316','#a855f7','#06b6d4','#ec4899','#eab308','#14b8a6','#f43f5e'];
var STU=[
  {id:1,name:'Nguyễn Văn An',cls:'10A',avg:8.5,submitted:12,total:14,email:'an.nv@email.com'},
  {id:2,name:'Trần Thị Bình',cls:'10A',avg:7.2,submitted:13,total:14,email:'binh.tt@email.com'},
  {id:3,name:'Lê Hoàng Cường',cls:'11B',avg:9.1,submitted:10,total:10,email:'cuong.lh@email.com'},
  {id:4,name:'Phạm Minh Đức',cls:'10A',avg:6.8,submitted:11,total:14,email:'duc.pm@email.com'},
  {id:5,name:'Hoàng Thu Hà',cls:'10A',avg:8.0,submitted:14,total:14,email:'ha.ht@email.com'},
  {id:6,name:'Vũ Đình Khoa',cls:'11B',avg:5.5,submitted:7,total:10,email:'khoa.vd@email.com'},
  {id:7,name:'Đỗ Thị Lan',cls:'11B',avg:7.8,submitted:9,total:10,email:'lan.dt@email.com'},
  {id:8,name:'Bùi Văn Mạnh',cls:'9C',avg:4.2,submitted:5,total:8,email:'manh.bv@email.com'},
  {id:9,name:'Ngô Thị Ngọc',cls:'10A',avg:9.3,submitted:14,total:14,email:'ngoc.nt@email.com'},
  {id:10,name:'Trịnh Quốc Phong',cls:'11B',avg:6.0,submitted:8,total:10,email:'phong.tq@email.com'},
  {id:11,name:'Lý Thị Quỳnh',cls:'10A',avg:8.7,submitted:13,total:14,email:'quynh.lt@email.com'},
  {id:12,name:'Đinh Văn Sơn',cls:'9C',avg:7.5,submitted:7,total:8,email:'son.dv@email.com'},
  {id:13,name:'Phan Thị Trang',cls:'10B',avg:6.3,submitted:9,total:12,email:'trang.pt@email.com'},
  {id:14,name:'Mai Xuân Uy',cls:'10B',avg:8.2,submitted:12,total:12,email:'uy.mx@email.com'},
  {id:15,name:'Cao Thị Vân',cls:'12A',avg:9.0,submitted:6,total:6,email:'van.ct@email.com'},
  {id:16,name:'Hồ Anh Tuấn',cls:'12A',avg:7.1,submitted:5,total:6,email:'tuan.ha@email.com'},
  {id:17,name:'Tô Minh Yến',cls:'9C',avg:5.8,submitted:6,total:8,email:'yen.tm@email.com'},
  {id:18,name:'Dương Bảo Long',cls:'10B',avg:7.9,submitted:11,total:12,email:'long.db@email.com'}
];

function getIni(n){return n.split(' ').filter(Boolean).map(function(w){return w[0];}).slice(-2).join('').toUpperCase();}
function perf(a){return a>=8?'good':a>=6?'ok':'weak';}
function perfLabel(a){return a>=8?'Giỏi':a>=6?'Khá':a>=5?'Trung bình':'Yếu';}
function perfBadge(a){return a>=8?'badge-success':a>=6?'badge-info':'badge-warning';}
function perfColor(a){return a>=8?'var(--success)':a>=6?'var(--info)':'var(--warning)';}

function render(data){
  var tb=document.getElementById('s-table');
  document.getElementById('s-cnt').textContent=data.length+' học sinh';
  if(!data.length){tb.innerHTML='<tr><td colspan="7"><div style="padding:3rem;text-align:center;color:var(--muted-foreground);"><div style="font-size:3rem;margin-bottom:.75rem;">👩‍🎓</div><h3 style="font-weight:600;color:var(--foreground);">Không tìm thấy</h3></div></td></tr>';return;}
  tb.innerHTML=data.map(function(s,i){
    var ci=colors[i%colors.length];
    var pct=s.total?Math.round((s.submitted/s.total)*100):0;
    return '<tr>'
      +'<td><div style="display:flex;align-items:center;gap:.75rem;"><div class="stu-avatar" style="background:'+ci+'22;color:'+ci+';">'+getIni(s.name)+'</div><div><div style="font-weight:600;">'+s.name+'</div><div style="font-size:var(--text-xs);color:var(--muted-foreground);">'+s.email+'</div></div></div></td>'
      +'<td><span class="badge badge-default">Lớp '+s.cls+'</span></td>'
      +'<td><span style="font-weight:700;color:'+perfColor(s.avg)+';">'+s.avg.toFixed(1)+'</span></td>'
      +'<td style="font-size:var(--text-sm);">'+s.submitted+'/'+s.total+'</td>'
      +'<td><div style="display:flex;align-items:center;gap:.5rem;"><div class="progress" style="width:5rem;height:.375rem;"><div class="progress-bar" style="width:'+pct+'%;background:'+(pct>=80?'var(--success)':'var(--warning)')+';"></div></div><span style="font-size:var(--text-xs);color:var(--muted-foreground);">'+pct+'%</span></div></td>'
      +'<td><span class="badge '+perfBadge(s.avg)+'">'+perfLabel(s.avg)+'</span></td>'
      +'<td><button class="btn btn-ghost btn-sm" onclick="toast(\'Xem chi tiết '+s.name+'\')">Chi tiết</button></td>'
      +'</tr>';
  }).join('');
}

window.applyFilters=function(){
  var s=(document.getElementById('s-search').value||'').toLowerCase();
  var c=document.getElementById('s-cls').value;
  var p=document.getElementById('s-perf').value;
  render(STU.filter(function(x){
    return(!s||x.name.toLowerCase().indexOf(s)!==-1)&&(!c||x.cls===c)&&(!p||perf(x.avg)===p);
  }));
};

function updSt(){
  document.getElementById('st-t').textContent=STU.length;
  document.getElementById('st-g').textContent=STU.filter(function(s){return s.avg>=8;}).length;
  document.getElementById('st-k').textContent=STU.filter(function(s){return s.avg>=6&&s.avg<8;}).length;
  document.getElementById('st-y').textContent=STU.filter(function(s){return s.avg<6;}).length;
}

window.toast=function(m,t){var tc=document.getElementById('toast-container');if(!tc)return;var e=document.createElement('div');e.className='toast toast-'+(t==='err'?'error':'success');e.innerHTML='<span>'+(t==='err'?'❌':'✅')+'</span><span>'+m+'</span>';tc.appendChild(e);setTimeout(function(){e.classList.add('show');},10);setTimeout(function(){e.classList.remove('show');setTimeout(function(){e.remove();},300);},3000);};

var invEmails=[];
window.openInvModal=function(){invEmails=[];renderTags();hideErr();document.getElementById('inv-email').value='';document.getElementById('inv-modal').classList.add('open');setTimeout(function(){document.getElementById('inv-email').focus();},100);};
window.closeInvModal=function(){document.getElementById('inv-modal').classList.remove('open');};
window.switchInvTab=function(tab){document.getElementById('inv-tab-email').classList.toggle('active',tab==='email');document.getElementById('inv-tab-link').classList.toggle('active',tab==='link');document.getElementById('inv-email-panel').style.display=tab==='email'?'':'none';document.getElementById('inv-link-panel').style.display=tab==='link'?'':'none';document.getElementById('inv-send-btn').style.display=tab==='email'?'':'none';if(tab==='link')updLink();};
function updLink(){var c=document.getElementById('inv-link-cls').value;var code=c+'-'+Math.random().toString(36).substr(2,6);document.getElementById('inv-link-url').textContent='https://vietquiz.edu.vn/join/'+code;}
window.copyLink=function(){var t=document.getElementById('inv-link-url').textContent;navigator.clipboard.writeText(t).then(function(){toast('Đã sao chép link!');}).catch(function(){toast('Không thể sao chép','err');});};
window.addEmail=function(){var inp=document.getElementById('inv-email'),v=inp.value.trim();hideErr();
  if(!v){showErr('Nhập email');return;}
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)){showErr('Email không hợp lệ');return;}
  if(invEmails.indexOf(v)!==-1){showErr('Email đã thêm');return;}
  invEmails.push(v);inp.value='';inp.focus();renderTags();
};
window.removeEmail=function(i){invEmails.splice(i,1);renderTags();};
function renderTags(){document.getElementById('inv-tags').innerHTML=invEmails.map(function(e,i){return '<div class="inv-tag"><span>'+e+'</span><button onclick="removeEmail('+i+')">✕</button></div>';}).join('')+(invEmails.length?'<span style="font-size:var(--text-xs);color:var(--muted-foreground);align-self:center;">'+invEmails.length+' email</span>':'');}
function showErr(m){var e=document.getElementById('inv-err');e.textContent=m;e.style.display='';}
function hideErr(){document.getElementById('inv-err').style.display='none';}
window.sendInvites=function(){if(!invEmails.length){showErr('Thêm ít nhất 1 email');return;}
  var cls=document.getElementById('inv-cls').value;
  var btn=document.getElementById('inv-send-btn');btn.disabled=true;btn.innerHTML='<span style="margin-right:.375rem;">⏳</span>Đang gửi...';
  setTimeout(function(){btn.disabled=false;btn.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.375rem;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Gửi lời mời';
    toast('Đã gửi '+invEmails.length+' lời mời vào lớp '+cls+'!');closeInvModal();
  },1200);
};
document.getElementById('inv-modal').addEventListener('click',function(e){if(e.target===this)closeInvModal();});
document.getElementById('inv-email').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();addEmail();}});
document.getElementById('inv-link-cls').addEventListener('change',updLink);

updSt();applyFilters();
})();
</script>
@endpush
