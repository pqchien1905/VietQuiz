{{-- Teacher: assignment grading single submission --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.grading-page{display:flex;flex-direction:column;gap:1rem}
.grading-hero{border:1px solid var(--border);border-radius:1.5rem;background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 13%,var(--card)) 0%,var(--card) 52%,color-mix(in srgb,var(--warning) 10%,var(--card)) 100%);padding:1.2rem;box-shadow:var(--shadow-sm)}
.grading-hero__top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.grading-title{display:flex;align-items:flex-start;gap:.9rem}
.grading-icon{width:3rem;height:3rem;border-radius:1rem;background:var(--primary);color:#fff;display:grid;place-items:center;box-shadow:0 14px 30px color-mix(in srgb,var(--primary) 30%,transparent);flex-shrink:0}
.grading-title h1{margin:0;font-size:clamp(1.6rem,2vw,2.35rem);letter-spacing:-.04em}
.grading-title p{margin:.25rem 0 0;color:var(--muted-foreground)}
.grading-actions{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
.grading-meta-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-top:1rem}
.grading-meta{border:1px solid color-mix(in srgb,var(--border) 80%,transparent);border-radius:1rem;background:color-mix(in srgb,var(--background) 78%,transparent);padding:.8rem}
.grading-meta__label{font-size:var(--text-xs);font-weight:700;color:var(--muted-foreground);text-transform:uppercase;letter-spacing:.04em}
.grading-meta__value{margin-top:.25rem;font-weight:800;line-height:1.25}
.grading-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(23rem,28rem);gap:1rem;align-items:start}
.grading-card{border:1px solid var(--border);border-radius:1.25rem;background:var(--card);box-shadow:var(--shadow-sm);overflow:hidden}
.grading-card__head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:1rem 1.1rem;border-bottom:1px solid var(--border);background:color-mix(in srgb,var(--muted) 70%,transparent)}
.grading-card__body{padding:1.1rem}
.student-strip{display:flex;align-items:center;gap:.85rem}
.student-avatar{width:3rem;height:3rem;border-radius:999px;background:linear-gradient(135deg,var(--primary),color-mix(in srgb,var(--primary) 55%,#22c55e));color:#fff;display:grid;place-items:center;font-weight:900;letter-spacing:.02em}
.student-name{font-weight:900;font-size:var(--text-lg);line-height:1.2}
.student-sub{font-size:var(--text-sm);color:var(--muted-foreground);margin-top:.15rem}
.submission-section{display:flex;flex-direction:column;gap:1rem}
.section-label{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.5rem;font-weight:900}
.answer-box{border:1px solid var(--border);border-radius:1rem;background:var(--background);padding:1rem;min-height:11rem;white-space:pre-wrap;line-height:1.7;font-size:var(--text-sm)}
.empty-answer{border:1px dashed var(--border);border-radius:1rem;background:var(--muted);padding:1rem;color:var(--muted-foreground);font-size:var(--text-sm)}
.file-card{display:flex;align-items:center;justify-content:space-between;gap:1rem;border:1px solid var(--border);border-radius:1rem;background:linear-gradient(135deg,var(--background),color-mix(in srgb,var(--primary) 5%,var(--background)));padding:1rem}
.file-info{display:flex;align-items:center;gap:.85rem;min-width:0}
.file-icon{width:2.75rem;height:2.75rem;border-radius:.9rem;background:color-mix(in srgb,var(--primary) 14%,transparent);color:var(--primary);display:grid;place-items:center;flex-shrink:0}
.file-name{font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:38rem}
.file-meta{font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.15rem}
.file-actions{display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end}
.grading-panel{position:sticky;top:1rem;max-height:calc(100vh - 2rem);overflow:auto;padding-bottom:3.5rem}
.score-ring{display:grid;grid-template-columns:auto 1fr;gap:.9rem;align-items:center;border:1px solid var(--border);border-radius:1rem;background:var(--background);padding:.85rem;margin-bottom:1rem}
.score-orb{width:4.2rem;height:4.2rem;border-radius:999px;background:conic-gradient(var(--primary) calc(var(--score-pct,0)*1%),color-mix(in srgb,var(--border) 70%,transparent) 0);display:grid;place-items:center}
.score-orb span{width:3.15rem;height:3.15rem;border-radius:999px;background:var(--card);display:grid;place-items:center;font-weight:900;font-size:var(--text-sm)}
.score-text{font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.55}
.score-text strong{color:var(--foreground)}
.grade-form{display:flex;flex-direction:column;gap:.85rem}
.score-input-wrap{display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:center}
.score-max{border:1px solid var(--border);background:var(--muted);border-radius:var(--radius-md);padding:.72rem .8rem;font-weight:800;color:var(--muted-foreground)}
.feedback-hint{display:flex;justify-content:space-between;gap:.75rem;font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem}
.ai-grade-box{border:1px solid color-mix(in srgb,var(--primary) 24%,var(--border));border-radius:1rem;background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 10%,var(--card)),var(--background));padding:.9rem;margin-bottom:1rem}
.ai-grade-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;margin-bottom:.65rem}
.ai-grade-title{font-weight:900}
.ai-grade-sub{font-size:var(--text-xs);color:var(--muted-foreground);line-height:1.5;margin-top:.15rem}
.ai-grade-actions{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-top:.75rem}
.ai-grade-actions .btn{width:100%;justify-content:center}
.ai-grade-status{display:none;border-radius:.8rem;padding:.65rem .75rem;font-size:var(--text-sm);line-height:1.5;margin-top:.75rem}
.ai-grade-status.is-info{display:block;background:color-mix(in srgb,var(--primary) 9%,transparent);color:var(--primary);border:1px solid color-mix(in srgb,var(--primary) 20%,transparent)}
.ai-grade-status.is-success{display:block;background:color-mix(in srgb,var(--success) 10%,transparent);color:var(--success);border:1px solid color-mix(in srgb,var(--success) 24%,transparent)}
.ai-grade-status.is-error{display:block;background:color-mix(in srgb,var(--destructive) 10%,transparent);color:var(--destructive);border:1px solid color-mix(in srgb,var(--destructive) 24%,transparent)}
.submit-row{display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:center}
.submit-row .btn-primary{min-width:12rem}
@media(max-width:1280px){.grading-layout{grid-template-columns:1fr}.grading-panel{position:static;max-height:none;overflow:visible;padding-bottom:0}.grading-meta-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.file-name{max-width:28rem}}
@media(max-width:760px){.grading-hero{padding:1rem}.grading-title{flex-direction:column}.grading-meta-grid{grid-template-columns:1fr}.file-card{align-items:flex-start;flex-direction:column}.file-actions{width:100%;justify-content:flex-start}.score-input-wrap{grid-template-columns:1fr}.score-max{width:max-content}.grading-card__head{padding:1rem}.grading-card__body{padding:1rem}}
</style>
@endpush

@section('content')
@php
  $score = $grade?->score;
  $maxScore = (int) ($assignment->total_points ?: 100);
  $scorePercent = $score !== null && $maxScore > 0 ? min(100, round(((float) $score / $maxScore) * 100)) : 0;
  $studentName = $submission->student?->name ?? 'Học sinh';
  $studentInitials = collect(explode(' ', trim($studentName)))
    ->filter()
    ->take(2)
    ->map(fn ($part) => mb_substr($part, 0, 1))
    ->implode('');
  $submittedAt = $submission->submitted_at?->format('d/m/Y H:i') ?? 'Chưa rõ';
  $fileName = $submission->attachment ? basename($submission->attachment) : null;
  $fileExtension = $fileName ? strtoupper(pathinfo($fileName, PATHINFO_EXTENSION) ?: 'FILE') : null;
@endphp

<div class="grading-page">
  <div class="grading-hero">
    <div class="grading-hero__top">
      <div class="grading-title">
        <div class="grading-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <div>
          <h1>Chấm bài nộp</h1>
          <p>{{ $assignment->title }}</p>
        </div>
      </div>
      <div class="grading-actions">
        <a href="{{ route('teacher.assignments.grading-board', $assignment) }}" class="btn btn-outline">Quay lại màn chấm tổng</a>
        <a href="{{ route('teacher.assignments.show', $assignment) }}" class="btn btn-ghost">Chi tiết bài tập</a>
      </div>
    </div>

    <div class="grading-meta-grid">
      <div class="grading-meta">
        <div class="grading-meta__label">Học sinh</div>
        <div class="grading-meta__value">{{ $studentName }}</div>
      </div>
      <div class="grading-meta">
        <div class="grading-meta__label">Nộp lúc</div>
        <div class="grading-meta__value">{{ $submittedAt }}</div>
      </div>
      <div class="grading-meta">
        <div class="grading-meta__label">Điểm tối đa</div>
        <div class="grading-meta__value">{{ $maxScore }} điểm</div>
      </div>
      <div class="grading-meta">
        <div class="grading-meta__label">Trạng thái</div>
        <div class="grading-meta__value">
          <span class="badge {{ $grade ? 'badge-success' : 'badge-warning' }}">{{ $grade ? 'Đã chấm' : 'Chờ chấm' }}</span>
        </div>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="grading-layout">
    <section class="grading-card">
      <div class="grading-card__head">
        <div class="student-strip">
          <div class="student-avatar">{{ $studentInitials ?: 'HS' }}</div>
          <div>
            <div class="student-name">{{ $studentName }}</div>
            <div class="student-sub">{{ $submission->student?->email ?? 'Không có email' }} · Nộp {{ $submittedAt }}</div>
          </div>
        </div>
        <span class="badge {{ $submission->attachment ? 'badge-info' : 'badge-default' }}">
          {{ $submission->attachment ? 'Có file đính kèm' : 'Chỉ có nội dung' }}
        </span>
      </div>

      <div class="grading-card__body">
        <div class="submission-section">
          <div>
            <div class="section-label">
              <span>Nội dung bài nộp</span>
              @if($submission->content)
                <span class="badge badge-default">{{ mb_strlen($submission->content) }} ký tự</span>
              @endif
            </div>

            @if($submission->content)
              <div class="answer-box">{{ $submission->content }}</div>
            @else
              <div class="empty-answer">Học sinh không nhập nội dung văn bản. Kiểm tra file đính kèm nếu có.</div>
            @endif
          </div>

          <div>
            <div class="section-label">
              <span>File bài nộp</span>
            </div>

            @if($submission->attachment)
              <div class="file-card">
                <div class="file-info">
                  <div class="file-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                  </div>
                  <div style="min-width:0;">
                    <div class="file-name">{{ $fileName }}</div>
                    <div class="file-meta">{{ $fileExtension }} · Mở trong tab mới để xem chi tiết trước khi chấm</div>
                  </div>
                </div>
                <div class="file-actions">
                  <a class="btn btn-primary btn-sm" target="_blank" href="{{ route('teacher.grading.submissions.attachment.inline', $submission->id) }}">Xem file</a>
                  <a class="btn btn-outline btn-sm" href="{{ route('teacher.grading.submissions.attachment.download', $submission->id) }}">Tải xuống</a>
                </div>
              </div>
            @else
              <div class="empty-answer">Không có file đính kèm.</div>
            @endif
          </div>
        </div>
      </div>
    </section>

    <aside class="grading-card grading-panel">
      <div class="grading-card__head">
        <div>
          <div style="font-weight:900;font-size:var(--text-lg);">Chấm điểm</div>
          <div class="student-sub">Điểm sẽ được gửi thông báo cho học sinh sau khi lưu.</div>
        </div>
      </div>

      <div class="grading-card__body">
        <div class="score-ring" style="--score-pct:{{ $scorePercent }}">
          <div class="score-orb"><span>{{ $score ?? '—' }}/{{ $maxScore }}</span></div>
          <div class="score-text">
            @if($grade)
              <strong>{{ $scorePercent }}%</strong> · Chấm lần cuối {{ $grade->graded_at?->format('d/m/Y H:i') ?? 'chưa rõ' }}.
            @else
              <strong>Chưa có điểm</strong>. Nhập điểm và nhận xét để hoàn tất bài nộp này.
            @endif
          </div>
        </div>

        <div class="ai-grade-box" data-ai-grade>
          <div class="ai-grade-head">
            <div>
              <div class="ai-grade-title">Chấm bằng AI</div>
              <div class="ai-grade-sub">AI tự đọc đề bài, nội dung nộp và file đính kèm để điền nháp điểm + nhận xét.</div>
            </div>
            <span class="badge badge-info">Pro</span>
          </div>
          <div class="ai-grade-actions">
            <button id="ai-grade-btn" class="btn btn-primary btn-sm" type="button">Chấm bằng AI</button>
            <span class="ai-grade-sub">Không tự động lưu điểm. Giáo viên kiểm tra rồi bấm lưu.</span>
          </div>
          <div id="ai-grade-status" class="ai-grade-status" role="status" aria-live="polite"></div>
        </div>

        <form id="grade-form" class="grade-form" method="POST" action="{{ route('teacher.grading.store') }}">
          @csrf
          <input type="hidden" name="gradable_type" value="assignment">
          <input type="hidden" name="gradable_id" value="{{ $submission->id }}">
          <input type="hidden" name="student_id" value="{{ $submission->student_id }}">

          <div class="form-group">
            <label class="label label-required" for="grade-score">Điểm</label>
            <div class="score-input-wrap">
              <input id="grade-score" class="input" type="number" name="score" min="0" max="{{ $maxScore }}" step="1" value="{{ old('score', $score) }}" placeholder="0 - {{ $maxScore }}" required autofocus>
              <div class="score-max">/ {{ $maxScore }}</div>
            </div>
            @error('score')
              <p style="color:var(--destructive);font-size:var(--text-xs);margin:.3rem 0 0;">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group">
            <label class="label" for="grade-feedback">Nhận xét</label>
            <textarea id="grade-feedback" class="input" name="feedback" rows="6" maxlength="3000" placeholder="Ghi rõ điểm mạnh, lỗi cần sửa và gợi ý cải thiện...">{{ old('feedback', $grade?->feedback) }}</textarea>
            <div class="feedback-hint">
              <span>Nên viết nhận xét cụ thể để học sinh biết cần sửa gì.</span>
              <span>Tối đa 3000 ký tự</span>
            </div>
            @error('feedback')
              <p style="color:var(--destructive);font-size:var(--text-xs);margin:.3rem 0 0;">{{ $message }}</p>
            @enderror
          </div>
          <div class="submit-row">
            <button class="btn btn-primary" type="submit">{{ $grade ? 'Cập nhật điểm' : 'Lưu điểm' }}</button>
            <a class="btn btn-ghost" href="{{ route('teacher.assignments.grading-board', $assignment) }}">Để sau</a>
          </div>
        </form>
      </div>
    </aside>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const button = document.getElementById('ai-grade-btn');
  const status = document.getElementById('ai-grade-status');
  const scoreInput = document.getElementById('grade-score');
  const feedbackInput = document.getElementById('grade-feedback');

  if (!button || !status || !scoreInput || !feedbackInput) {
    return;
  }

  const setStatus = (type, message) => {
    status.className = 'ai-grade-status is-' + type;
    status.textContent = message;
  };

  const errorMessageFrom = (payload) => {
    if (payload && payload.message) {
      return payload.message;
    }
    if (payload && payload.errors) {
      const firstField = Object.values(payload.errors)[0];
      if (Array.isArray(firstField) && firstField[0]) {
        return firstField[0];
      }
    }
    return 'Không tạo được gợi ý AI. Vui lòng thử lại.';
  };

  button.addEventListener('click', async () => {
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Đang chấm...';
    setStatus('info', 'AI đang đọc bài nộp và tạo gợi ý. Không rời trang trong lúc xử lý.');

    try {
      const response = await fetch(@json(route('teacher.assignments.grading-submission.ai-grade', [$assignment, $submission])), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': @json(csrf_token()),
        },
        body: JSON.stringify({}),
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload.success) {
        throw new Error(errorMessageFrom(payload));
      }

      scoreInput.value = payload.score;
      feedbackInput.value = payload.feedback || '';

      const warnings = Array.isArray(payload.warnings) && payload.warnings.length
        ? ' Lưu ý: ' + payload.warnings.join(' ')
        : '';
      const summary = payload.summary ? ' ' + payload.summary : '';
      setStatus('success', 'Đã điền gợi ý AI vào form. Hãy kiểm tra lại trước khi lưu.' + summary + warnings);
    } catch (error) {
      setStatus('error', error.message || 'Không tạo được gợi ý AI. Vui lòng thử lại.');
    } finally {
      button.disabled = false;
      button.textContent = originalText;
    }
  });
});
</script>
@endpush

