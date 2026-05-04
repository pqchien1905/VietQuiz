{{-- Student: join-class --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $initialCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', old('code', $prefillCode ?? '')));
  $availableJson = $availableClasses->keyBy('code')->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
@endphp

@push('styles')
<style>
  .join-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);gap:1.5rem;align-items:start}
  .code-input{display:grid;grid-template-columns:repeat(6,minmax(2.25rem,3rem));gap:.5rem;justify-content:center;margin:1.25rem 0}
  .code-digit{height:3.4rem;border:2px solid var(--border);border-radius:var(--radius-md);font-size:var(--text-2xl);font-weight:700;text-align:center;background:var(--background);color:var(--foreground);text-transform:uppercase}
  .code-digit:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 15%,transparent)}
  .class-preview{border:1px solid color-mix(in srgb,var(--primary) 45%,var(--border));border-radius:var(--radius-lg);padding:1rem;background:color-mix(in srgb,var(--primary) 5%,transparent);text-align:left;margin-top:1rem}
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

<div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Lớp đang học</div>
    <div class="stat-card__value">{{ $summary['enrolled'] }}</div>
    <div class="stat-card__label">lớp đã tham gia</div>
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Khóa học</div>
    <div class="stat-card__value">{{ $summary['courses'] }}</div>
    <div class="stat-card__label">tự đồng bộ khi vào lớp</div>
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Nội dung được giao</div>
    <div class="stat-card__value">{{ $summary['pending_items'] }}</div>
    <div class="stat-card__label">quiz và bài tập trong lớp</div>
  </div>
  <div class="stat-card">
    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Lớp có thể tham gia</div>
    <div class="stat-card__value">{{ $summary['available'] }}</div>
    <div class="stat-card__label">lớp đang hoạt động</div>
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

        <div class="class-preview" id="class-preview" hidden>
          <div style="display:flex;align-items:flex-start;gap:.875rem;">
            <div class="join-dot" id="preview-dot" style="background:var(--primary);">L</div>
            <div style="min-width:0;flex:1;">
              <h3 style="font-size:var(--text-lg);font-weight:700;margin:0;" id="preview-name">Tên lớp</h3>
              <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin:.2rem 0 0;" id="preview-teacher">Giáo viên</p>
              <div class="join-meta">
                <span class="badge badge-primary" id="preview-subject">Môn học</span>
                <span class="badge badge-outline" id="preview-students">0 học sinh</span>
                <span class="badge badge-outline" id="preview-courses">0 khóa học</span>
              </div>
              <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin:.65rem 0 0;" id="preview-description"></p>
            </div>
          </div>
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
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="card-title">Lớp có sẵn</h3>
            <p class="card-description">Các lớp đang hoạt động mà bạn chưa tham gia.</p>
          </div>
          <a href="{{ route('student.classes') }}" class="btn btn-ghost btn-sm">Đang học</a>
        </div>
      </div>
      <div class="card-content" style="padding-top:0;">
        @forelse($availableClasses as $class)
          <div class="join-class-row">
            <div class="join-dot" style="background:{{ $class['color'] }};">{{ mb_substr($class['name'], 0, 1) }}</div>
            <div style="min-width:0;">
              <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $class['name'] }}</div>
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $class['teacher'] }} · Mã {{ $class['code'] }}</div>
              <div class="join-meta">
                <span class="badge badge-primary">{{ $class['subject'] }}</span>
                <span class="badge badge-outline">{{ $class['students'] }} học sinh</span>
                <span class="badge badge-outline">{{ $class['courses'] }} khóa học</span>
              </div>
            </div>
            <div style="display:flex;gap:.5rem;align-items:center;">
              <button type="button" class="btn btn-outline btn-sm" data-preview-code="{{ $class['code'] }}">Xem</button>
              <form method="POST" action="{{ route('student.join-class.submit') }}">
                @csrf
                <input type="hidden" name="code" value="{{ $class['code'] }}">
                <button type="submit" class="btn btn-primary btn-sm">Tham gia</button>
              </form>
            </div>
          </div>
        @empty
          <div style="padding:2rem 1rem;text-align:center;color:var(--muted-foreground);">
            <div style="font-weight:600;color:var(--foreground);margin-bottom:.35rem;">Chưa có lớp mới phù hợp</div>
            <div>Hãy nhập mã lớp từ giáo viên hoặc quay lại khi lớp mới được mở.</div>
          </div>
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
  const classesByCode = {!! $availableJson ?: '{}' !!};
  const form = document.getElementById('join-form');
  const codeInput = document.getElementById('code');
  const digits = Array.from(document.querySelectorAll('[data-code-digit]'));
  const preview = document.getElementById('class-preview');

  function normalize(value) {
    return String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 20);
  }

  function syncDigitsFromCode() {
    const code = normalize(codeInput.value);
    codeInput.value = code;
    digits.forEach(function(input, index) {
      input.value = code[index] || '';
    });
    renderPreview(code);
  }

  function syncCodeFromDigits() {
    codeInput.value = normalize(digits.map(input => input.value).join(''));
    renderPreview(codeInput.value);
  }

  function renderPreview(code) {
    const cls = classesByCode[code];
    if (!cls) {
      preview.hidden = true;
      return;
    }

    document.getElementById('preview-dot').textContent = (cls.name || 'L').slice(0, 1);
    document.getElementById('preview-dot').style.background = cls.color || 'var(--primary)';
    document.getElementById('preview-name').textContent = cls.name || 'Tên lớp';
    document.getElementById('preview-teacher').textContent = cls.teacher || 'Giáo viên';
    document.getElementById('preview-subject').textContent = cls.subject || 'Chưa phân môn';
    document.getElementById('preview-students').textContent = (cls.students || 0) + ' học sinh';
    document.getElementById('preview-courses').textContent = (cls.courses || 0) + ' khóa học';
    document.getElementById('preview-description').textContent = cls.description || 'Sau khi tham gia, các khóa học thuộc lớp sẽ tự động xuất hiện trong tài khoản của bạn.';
    preview.hidden = false;
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

  document.querySelectorAll('[data-preview-code]').forEach(function(button) {
    button.addEventListener('click', function() {
      codeInput.value = normalize(button.dataset.previewCode);
      syncDigitsFromCode();
      form?.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
