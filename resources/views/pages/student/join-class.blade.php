{{-- Student: join-class --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $initialCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', old('code', $prefillCode ?? '')));
@endphp

@push('styles')
<style>
  .join-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);gap:1.5rem;align-items:start}
  .code-input{display:grid;grid-template-columns:repeat(6,minmax(2.25rem,3rem));gap:.5rem;justify-content:center;margin:1.25rem 0}
  .code-digit{height:3.4rem;border:2px solid var(--border);border-radius:var(--radius-md);font-size:var(--text-2xl);font-weight:700;text-align:center;background:var(--background);color:var(--foreground);text-transform:uppercase}
  .code-digit:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 15%,transparent)}
  .join-class-row{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:.875rem;align-items:center;padding:1rem 0;border-top:1px solid var(--border)}
  .join-class-row:first-child{border-top:none;padding-top:0}
  .join-dot{width:2.6rem;height:2.6rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0}
  .join-meta{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.45rem}
  .copy-field{display:flex;gap:.5rem;align-items:center;background:var(--muted);border:1px solid var(--border);border-radius:var(--radius-md);padding:.5rem}
  .copy-field code{flex:1;font-size:var(--text-sm);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  @media (max-width: 980px){.join-grid{grid-template-columns:1fr}.code-input{grid-template-columns:repeat(6,minmax(2rem,1fr))}}
</style>
@endpush

@section('content')
<div class="page-header stagger-children">
  <div>
    <h1>Tham gia lớp</h1>
    <p style="color:var(--muted-foreground);">Nhập mã lớp hoặc mở link mời từ giáo viên để bắt đầu học trong lớp mới.</p>
  </div>
  <a href="{{ route('student.classes') }}" class="btn btn-outline">Danh sách lớp</a>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:1rem;"><span>{{ session('success') }}</span></div>
@endif
@if(session('info'))
  <div class="alert alert-info" style="margin-bottom:1rem;"><span>{{ session('info') }}</span></div>
@endif
@if(session('warning'))
  <div class="alert alert-warning" style="margin-bottom:1rem;"><span>{{ session('warning') }}</span></div>
@endif

<div class="stats-grid stats-grid-3 stagger-children" style="margin-bottom:1.5rem;">
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Lớp đang học</div>
    <div class="stat-card__value">{{ $summary['enrolled'] }}</div>
    <div class="stat-card__label">lớp đã tham gia</div>
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Khóa học</div>
    <div class="stat-card__value">{{ $summary['courses'] }}</div>
    <div class="stat-card__label">tự động đồng bộ khi vào lớp</div>
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Nội dung được giao</div>
    <div class="stat-card__value">{{ $summary['pending_items'] }}</div>
    <div class="stat-card__label">quiz và bài tập trong lớp</div>
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Yêu cầu chờ duyệt</div>
    <div class="stat-card__value">{{ $summary['pending_requests'] ?? 0 }}</div>
    <div class="stat-card__label">có thể hủy trực tiếp tại trang này</div>
  </div>
</div>

<div class="join-grid stagger-children">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Nhập mã lớp</h3>
      <p class="card-description">Mã lớp thường gồm 6 ký tự. Bạn có thể dán cả mã, hệ thống sẽ tự chuẩn hóa chữ hoa và bỏ dấu cách.</p>
    </div>
    <div class="card-content">
      <form method="POST" action="{{ route('student.join-class.submit') }}" id="join-form">
        @csrf

        <div class="code-input" aria-label="Nhập từng ký tự mã lớp">
          @for($i = 0; $i < 6; $i++)
            <input
              type="text"
              class="code-digit"
              maxlength="1"
              inputmode="text"
              autocomplete="off"
              value="{{ $initialCode[$i] ?? '' }}"
              data-code-digit
              aria-label="Ký tự {{ $i + 1 }}"
            >
          @endfor
        </div>

        <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.75rem;align-items:start;max-width:460px;margin:0 auto 1rem;">
          <div>
            <label for="code" style="display:block;font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.35rem;">Mã lớp</label>
            <input
              type="text"
              class="input"
              id="code"
              name="code"
              value="{{ $initialCode }}"
              placeholder="VD: ABC123"
              maxlength="20"
              style="text-transform:uppercase;letter-spacing:.08em;font-weight:700;"
              required
            >
            @error('code')
              <p style="color:var(--destructive);font-size:var(--text-sm);margin-top:.5rem;">{{ $message }}</p>
            @enderror
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:1.65rem;">Tham gia</button>
        </div>

      </form>

      <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border);">
        <h4 style="font-size:var(--text-sm);font-weight:700;margin-bottom:.5rem;">Cách tham gia bằng link</h4>
        <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.75rem;">
          Nếu giáo viên gửi link dạng <strong>/student/join/ma-lop</strong>, bạn chỉ cần mở link đó khi đã đăng nhập tài khoản học sinh.
        </p>
        <div class="copy-field">
          <code>{{ url('/student/join/' . strtolower($initialCode ?: 'ABC123')) }}</code>
          <button type="button" class="btn btn-outline btn-sm" id="copy-example-link">Sao chép</button>
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:1rem;">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Yêu cầu đang chờ duyệt</h3>
        <p class="card-description">Danh sách lớp bạn đã gửi yêu cầu tham gia và chưa được phê duyệt.</p>
      </div>
      <div class="card-content" style="padding-top:0;">
        @forelse(($pendingEnrollments ?? []) as $pending)
          <div class="join-class-row">
            <div class="join-dot" style="background:{{ $pending['color'] }};">{{ mb_substr($pending['name'], 0, 1) }}</div>
            <div style="min-width:0;">
              <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $pending['name'] }}</div>
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);">
                {{ $pending['teacher'] }} · {{ $pending['requested_at'] ? 'Gửi lúc ' . $pending['requested_at'] : 'Đang chờ duyệt' }}
              </div>
              <div class="join-meta">
                <span class="badge badge-outline">Mã {{ $pending['code'] }}</span>
                <span class="badge badge-outline">{{ $pending['source'] }}</span>
              </div>
            </div>
            <form method="POST" action="{{ route('student.join-class.cancel', $pending['id']) }}" onsubmit="return confirm('Bạn muốn hủy yêu cầu tham gia lớp {{ addslashes($pending['name']) }}?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline btn-sm">Hủy yêu cầu</button>
            </form>
          </div>
        @empty
          <div style="padding:1rem 0;color:var(--muted-foreground);font-size:var(--text-sm);">Bạn không có yêu cầu nào đang chờ duyệt.</div>
        @endforelse
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Lớp đang tham gia</h3>
        <p class="card-description">Những lớp bạn đã ghi danh thành công.</p>
      </div>
      <div class="card-content" style="padding-top:0;">
        @forelse($enrolledClasses as $class)
          <a href="{{ $class['url'] }}" class="join-class-row" style="text-decoration:none;color:inherit;">
            <div class="join-dot" style="background:{{ $class['color'] }};">{{ mb_substr($class['name'], 0, 1) }}</div>
            <div style="min-width:0;">
              <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $class['name'] }}</div>
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $class['teacher'] }} · {{ $class['joined_at'] ? 'Tham gia ' . $class['joined_at'] : 'Đã tham gia' }}</div>
              <div class="join-meta">
                <span class="badge badge-outline">Mã {{ $class['code'] }}</span>
                <span class="badge badge-outline">{{ $class['courses'] }} khóa học</span>
              </div>
            </div>
            <span class="badge badge-success">Đang học</span>
          </a>
        @empty
          <div style="padding:1rem 0;color:var(--muted-foreground);font-size:var(--text-sm);">Bạn chưa tham gia lớp nào.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function(){
  const form = document.getElementById('join-form');
  const codeInput = document.getElementById('code');
  const digits = Array.from(document.querySelectorAll('[data-code-digit]'));

  function normalize(value) {
    return String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 20);
  }

  function syncDigitsFromCode() {
    const code = normalize(codeInput.value);
    codeInput.value = code;
    digits.forEach(function(input, index) {
      input.value = code[index] || '';
    });
  }

  function syncCodeFromDigits() {
    codeInput.value = normalize(digits.map(input => input.value).join(''));
  }


  codeInput?.addEventListener('input', syncDigitsFromCode);
  digits.forEach(function(input, index) {
    input.addEventListener('input', function() {
      input.value = normalize(input.value).slice(0, 1);
      if (input.value && digits[index + 1]) {
        digits[index + 1].focus();
      }
      syncCodeFromDigits();
    });
    input.addEventListener('keydown', function(event) {
      if (event.key === 'Backspace' && !input.value && digits[index - 1]) {
        digits[index - 1].focus();
      }
    });
    input.addEventListener('paste', function(event) {
      event.preventDefault();
      codeInput.value = normalize(event.clipboardData.getData('text'));
      syncDigitsFromCode();
    });
  });


  form?.addEventListener('submit', function() {
    codeInput.value = normalize(codeInput.value);
  });

  document.getElementById('copy-example-link')?.addEventListener('click', function() {
    const link = '{{ url('/student/join') }}/' + normalize(codeInput.value || 'ABC123').toLowerCase();
    copyText(link);
  });

  function copyText(text) {
    const done = function() { toast('Đã sao chép link mời.', 'success'); };
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(done).catch(function() { fallbackCopy(text, done); });
      return;
    }
    fallbackCopy(text, done);
  }

  function fallbackCopy(text, done) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
    done();
  }

  function toast(message, type) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const item = document.createElement('div');
    item.className = 'toast toast-' + (type === 'error' ? 'error' : 'success');
    item.textContent = message;
    container.appendChild(item);
    setTimeout(function() { item.classList.add('show'); }, 10);
    setTimeout(function() {
      item.classList.remove('show');
      setTimeout(function() { item.remove(); }, 250);
    }, 2600);
  }

  syncDigitsFromCode();
})();
</script>
@endpush


