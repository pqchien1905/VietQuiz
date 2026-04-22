{{-- Student: quiz-take --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
body { background: var(--background); }
.quiz-shell { min-height: 100vh; display: flex; flex-direction: column; }
.quiz-header {
    background: var(--card);
    border-bottom: 1px solid var(--border);
    padding: 0.875rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    position: sticky;
    top: 0;
    z-index: 10;
}
.timer-display {
    font-size: var(--text-xl);
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.timer-display.warning { color: var(--warning); }
.timer-display.danger {
    color: var(--destructive);
    animation: timer-pulse 1s ease-in-out infinite;
}
@keyframes timer-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.quiz-body {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 260px;
    gap: 1.5rem;
    padding: 1.5rem;
    max-width: 1100px;
    margin: 0 auto;
    width: 100%;
}
.question-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1.75rem;
}
.question-content {
    font-size: var(--text-lg);
    font-weight: 600;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}
.option-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 1rem 1.125rem;
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--card);
    cursor: pointer;
    transition: all var(--transition-fast);
    text-align: left;
    margin-bottom: 0.625rem;
    font-size: var(--text-base);
}
.option-btn:hover {
    border-color: var(--primary);
    background: color-mix(in srgb, var(--primary) 5%, transparent);
}
.option-btn.selected {
    border-color: var(--primary);
    background: color-mix(in srgb, var(--primary) 10%, transparent);
    color: var(--primary);
    font-weight: 500;
}
.option-letter {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: var(--muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-sm);
    font-weight: 700;
    flex-shrink: 0;
}
.option-btn.selected .option-letter {
    background: var(--primary);
    color: #fff;
}
.tf-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.tf-btn {
    padding: 1.25rem;
    border: 2px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--card);
    cursor: pointer;
    text-align: center;
    transition: all var(--transition-fast);
    font-weight: 600;
    font-size: var(--text-base);
}
.tf-btn:hover {
    border-color: var(--primary);
    background: color-mix(in srgb, var(--primary) 5%, transparent);
}
.tf-btn.selected {
    border-color: var(--primary);
    background: color-mix(in srgb, var(--primary) 10%, transparent);
    color: var(--primary);
}
.short-answer-input {
    width: 100%;
    min-height: 100px;
    padding: 0.875rem 1rem;
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--card);
    font-size: var(--text-base);
    font-family: inherit;
    resize: vertical;
    transition: border-color var(--transition-fast);
}
.short-answer-input:focus {
    outline: none;
    border-color: var(--primary);
}
.q-nav {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    position: sticky;
    top: 5.5rem;
    height: fit-content;
}
.q-nav-title {
    font-size: var(--text-sm);
    font-weight: 700;
    margin-bottom: 0.25rem;
}
.q-nav-sub {
    font-size: var(--text-xs);
    color: var(--muted-foreground);
    margin-bottom: 0.75rem;
}
.q-nav-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.375rem;
    margin-bottom: 1rem;
}
.q-nav-btn {
    width: 100%;
    aspect-ratio: 1;
    border-radius: var(--radius-sm);
    border: 2px solid var(--border);
    background: var(--muted);
    cursor: pointer;
    font-size: var(--text-xs);
    font-weight: 700;
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
}
.q-nav-btn:hover { border-color: var(--primary); }
.q-nav-btn.answered {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}
.q-nav-btn.current {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 25%, transparent);
}
.nav-actions {
    display: flex;
    gap: 0.5rem;
}
.nav-btn {
    flex: 1;
    padding: 0.625rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    background: var(--muted);
    cursor: pointer;
    font-size: var(--text-sm);
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    transition: all var(--transition-fast);
}
.nav-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
.nav-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.submit-sidebar-btn {
    width: 100%;
    padding: 0.75rem;
    background: var(--success);
    color: #fff;
    border: none;
    border-radius: var(--radius-md);
    font-weight: 700;
    font-size: var(--text-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
    margin-top: 0.5rem;
}
.submit-sidebar-btn:hover { filter: brightness(1.1); }
.submit-sidebar-btn:disabled { opacity: 0.5; cursor: not-allowed; filter: none; }
.progress-bar-wrap {
    height: 4px;
    background: var(--muted);
    border-radius: 2px;
    overflow: hidden;
    margin-top: 0.5rem;
}
.progress-bar-fill {
    height: 100%;
    background: var(--primary);
    border-radius: 2px;
    transition: width 0.3s ease;
}
.submit-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.submit-modal {
    background: var(--card);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 420px;
    padding: 2rem;
    box-shadow: var(--shadow-xl);
}
.submit-modal h3 {
    font-size: var(--text-xl);
    font-weight: 800;
    margin-bottom: 0.75rem;
}
.submit-modal p {
    color: var(--muted-foreground);
    margin-bottom: 1.5rem;
    font-size: var(--text-sm);
}
.submit-modal-actions {
    display: flex;
    gap: 0.75rem;
}
@media (max-width: 900px) {
    .quiz-body { grid-template-columns: 1fr; }
    .q-nav { position: static; }
}
</style>
@endpush

@section('content')
<div class="quiz-shell">
    <!-- Header -->
    <div class="quiz-header">
        <div style="display:flex;align-items:center;gap:1rem;">
            <a href="{{ route('student.quizzes') }}" class="btn btn-ghost btn-sm gap-1" style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Thoát
            </a>
            <div>
                <div style="font-weight:700;font-size:var(--text-base);">{{ $quiz->title }}</div>
                <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Câu <span id="current-num">1</span> / <span id="total-num">{{ $quiz->questions->count() }}</span></div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:1rem;">
            <div class="timer-display" id="timer-display">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span id="timer-text">--:--</span>
            </div>
            <button class="btn btn-primary btn-sm" id="submit-btn-top" onclick="showSubmitModal()">
                Nộp bài
            </button>
        </div>
    </div>

    <!-- Body -->
    <div class="quiz-body">
        <!-- Left: Question Panel -->
        <div class="question-panel" id="question-panel">
            <div id="question-inner">
                <!-- Filled by JavaScript -->
            </div>

            <!-- Navigation -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border);">
                <button class="btn btn-outline btn-sm gap-1" id="prev-btn" onclick="navigateQuestion(-1)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Câu trước
                </button>
                <button class="btn btn-outline btn-sm gap-1" id="next-btn" onclick="navigateQuestion(1)">
                    Câu sau
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>

        <!-- Right: Question Navigator -->
        <div class="q-nav">
            <div class="q-nav-title">Điều hướng</div>
            <div class="q-nav-sub">
                <span id="answered-count">0</span>/{{ $quiz->questions->count() }} đã trả lời
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="progress-bar" style="width:0%"></div>
            </div>
            <div class="q-nav-grid" id="q-nav-grid">
                <!-- Filled by JavaScript -->
            </div>
            <div class="nav-actions">
                <button class="nav-btn" id="nav-prev" onclick="navigateQuestion(-1)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button class="nav-btn" id="nav-next" onclick="navigateQuestion(1)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
            <button class="submit-sidebar-btn" id="submit-sidebar-btn" onclick="showSubmitModal()">
                Nộp bài ngay
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ─────────────────────────────────────────────
// Quiz Take JavaScript
// ─────────────────────────────────────────────
(function() {
    'use strict';

    // ── Data from server ────────────────────
    const QUIZ_DATA = @json([
        'id' => $quiz->id,
        'title' => $quiz->title,
        'time_limit' => $quiz->time_limit,
        'started_at' => $startedAt->toIso8601String(),
    ]);

    const QUESTIONS = @json($quiz->questions->values()->map(fn($q, $i) => [
        'idx' => $i,
        'id' => $q->id,
        'content' => $q->content,
        'type' => $q->type,
        'options' => $q->options ?? [],
        'correct_answer' => $q->correct_answer,
        'points' => $q->points ?? 1,
        'explanation' => $q->explanation,
    ]));

    const TOTAL = QUESTIONS.length;

    // ── State ────────────────────────────────
    let currentIdx = 0;
    let answers = {};        // { questionId: answer }
    let timerInterval = null;

    // ── Helpers ───────────────────────────────
    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    function getAnsweredCount() {
        return Object.keys(answers).filter(k => answers[k] !== '' && answers[k] !== null && answers[k] !== undefined).length;
    }

    // ── Timer ─────────────────────────────────
    function initTimer() {
        if (!QUIZ_DATA.time_limit) {
            $('#timer-display').style.display = 'none';
            return;
        }

        const startTime = new Date(QUIZ_DATA.started_at).getTime();
        const endTime = startTime + QUIZ_DATA.time_limit * 60 * 1000;

        function updateTimer() {
            const now = Date.now();
            const remaining = endTime - now;

            if (remaining <= 0) {
                clearInterval(timerInterval);
                $('#timer-text').textContent = '00:00';
                autoSubmit();
                return;
            }

            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            $('#timer-text').textContent =
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            const timerEl = $('#timer-display');
            timerEl.classList.remove('warning', 'danger');
            if (remaining < 60000) {
                timerEl.classList.add('danger');
            } else if (remaining < 300000) {
                timerEl.classList.add('warning');
            }
        }

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
    }

    // ── Render Question ───────────────────────
    function renderQuestion(idx) {
        const q = QUESTIONS[idx];
        if (!q) return;

        currentIdx = idx;
        $('#current-num').textContent = idx + 1;

        // Update nav buttons
        const prevBtn = $('#prev-btn');
        const nextBtn = $('#next-btn');
        if (prevBtn) {
            prevBtn.disabled = idx === 0;
        }
        if (nextBtn) {
            nextBtn.disabled = idx === TOTAL - 1;
        }

        // Update nav grid highlight
        $$('.q-nav-btn').forEach((btn, i) => {
            btn.classList.toggle('current', i === idx);
        });

        const inner = $('#question-inner');
        const labels = ['A', 'B', 'C', 'D', 'E', 'F'];
        const currentAnswer = answers[q.id] ?? '';

        let html = `
            <div class="question-content">${idx + 1}. ${q.content}</div>
            <div style="margin-bottom:0.5rem;">
                <span class="question-type-badge" style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.6rem;border-radius:9999px;font-size:var(--text-xs);font-weight:500;background:color-mix(in srgb,var(--primary) 10%,transparent);color:var(--primary);">
                    ${q.type === 'multiple_choice' ? '◉ Trắc nghiệm' : q.type === 'true_false' ? '✓ Đúng/Sai' : '✎ Tự luận'}
                </span>
                ${q.points > 1 ? `<span style="margin-left:0.5rem;font-size:var(--text-xs);color:var(--muted-foreground);">${q.points} điểm</span>` : ''}
            </div>
        `;

        if (q.type === 'multiple_choice') {
            const opts = q.options && q.options.length ? q.options : [];
            html += `<div style="display:flex;flex-direction:column;gap:0.625rem;">`;
            opts.forEach((opt, oi) => {
                const isSelected = String(currentAnswer) === String(oi);
                html += `
                    <button class="option-btn ${isSelected ? 'selected' : ''}"
                        onclick="selectOption(${q.id}, ${oi}, '${q.type}')"
                        data-opt-idx="${oi}">
                        <span class="option-letter">${labels[oi] || oi + 1}</span>
                        <span>${opt}</span>
                    </button>`;
            });
            html += `</div>`;

        } else if (q.type === 'true_false') {
            html += `
                <div class="tf-grid">
                    <button class="tf-btn ${currentAnswer === 'true' ? 'selected' : ''}"
                        onclick="selectOption(${q.id}, 'true', '${q.type}')">
                        ✓ True — Đúng
                    </button>
                    <button class="tf-btn ${currentAnswer === 'false' ? 'selected' : ''}"
                        onclick="selectOption(${q.id}, 'false', '${q.type}')">
                        ✗ False — Sai
                    </button>
                </div>`;

        } else {
            html += `
                <textarea class="short-answer-input"
                    id="sa-answer-${q.id}"
                    placeholder="Nhập câu trả lời của bạn..."
                    oninput="selectOption(${q.id}, this.value, '${q.type}')"
                >${currentAnswer || ''}</textarea>`;
        }

        inner.innerHTML = html;
    }

    // ── Render Navigation Grid ────────────────
    function renderNavGrid() {
        const grid = $('#q-nav-grid');
        let html = '';
        QUESTIONS.forEach((q, i) => {
            const isAnswered = answers[q.id] !== '' && answers[q.id] !== null && answers[q.id] !== undefined;
            const isCurrent = i === currentIdx;
            html += `<button class="q-nav-btn ${isAnswered ? 'answered' : ''} ${isCurrent ? 'current' : ''}"
                onclick="goToQuestion(${i})">${i + 1}</button>`;
        });
        grid.innerHTML = html;
        updateProgress();
    }

    // ── Update Progress ───────────────────────
    function updateProgress() {
        const count = getAnsweredCount();
        $('#answered-count').textContent = count;
        const pct = TOTAL > 0 ? Math.round((count / TOTAL) * 100) : 0;
        $('#progress-bar').style.width = pct + '%';

        const submitBtn = $('#submit-sidebar-btn');
        if (submitBtn) {
            submitBtn.disabled = count === 0;
        }
    }

    // ── Select Option ────────────────────────
    function selectOption(questionId, value, type) {
        answers[questionId] = value;

        if (type === 'multiple_choice') {
            $$('.option-btn').forEach(btn => btn.classList.remove('selected'));
            $$('.option-btn').forEach(btn => {
                if (parseInt(btn.dataset.optIdx) === parseInt(value)) {
                    btn.classList.add('selected');
                }
            });
        } else if (type === 'true_false') {
            $$('.tf-btn').forEach(btn => btn.classList.remove('selected'));
            $$('.tf-btn').forEach(btn => {
                if (btn.textContent.trim().startsWith(value === 'true' ? '✓' : '✗')) {
                    btn.classList.add('selected');
                }
            });
        }

        // Update nav
        const qIdx = QUESTIONS.findIndex(q => q.id === questionId);
        if (qIdx >= 0) {
            const btn = $$('.q-nav-btn')[qIdx];
            if (btn) btn.classList.add('answered');
        }

        updateProgress();
    }

    // ── Navigate ──────────────────────────────
    function navigateQuestion(dir) {
        const next = currentIdx + dir;
        if (next >= 0 && next < TOTAL) {
            goToQuestion(next);
        }
    }

    function goToQuestion(idx) {
        renderQuestion(idx);
        renderNavGrid();
    }

    // ── Submit Modal ─────────────────────────
    function showSubmitModal() {
        const answered = getAnsweredCount();
        const unanswered = TOTAL - answered;
        const unansweredText = unanswered > 0
            ? `<p style="color:var(--warning);font-weight:600;">Bạn còn <strong>${unanswered}</strong> câu chưa trả lời!</p>`
            : `<p style="color:var(--success);font-weight:600;">Bạn đã trả lời đủ tất cả câu hỏi.</p>`;

        const overlay = document.createElement('div');
        overlay.className = 'submit-modal-overlay';
        overlay.id = 'submit-modal-overlay';
        overlay.innerHTML = `
            <div class="submit-modal">
                <h3>Xác nhận nộp bài?</h3>
                <p>Bạn đã trả lời <strong>${answered}/${TOTAL}</strong> câu.</p>
                ${unansweredText}
                <p style="color:var(--muted-foreground);margin-top:0.5rem;">Hành động này không thể hoàn tác.</p>
                <div class="submit-modal-actions">
                    <button class="btn btn-outline" style="flex:1;" onclick="closeSubmitModal()">Chưa xong</button>
                    <button class="btn btn-primary" style="flex:1;" onclick="submitQuiz()">Nộp bài</button>
                </div>
            </div>`;
        overlay.onclick = (e) => { if (e.target === overlay) closeSubmitModal(); };
        document.body.appendChild(overlay);
    }

    function closeSubmitModal() {
        const m = $('#submit-modal-overlay');
        if (m) m.remove();
    }

    // ── Submit Quiz ───────────────────────────
    async function submitQuiz() {
        closeSubmitModal();
        clearInterval(timerInterval);

        // Build answers object keyed by question id
        const answersPayload = {};
        QUESTIONS.forEach(q => {
            answersPayload[q.id] = answers[q.id] ?? '';
        });

        const submitBtn = $('#submit-btn-top');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Đang nộp...'; }

        try {
            const response = await fetch(`/student/quiz-take/${QUIZ_DATA.id}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ answers: answersPayload }),
            });

            const data = await response.json();

            if (response.ok && data.success) {
                window.location.href = `/student/quiz-result/${QUIZ_DATA.id}`;
            } else {
                alert(data.error || 'Có lỗi xảy ra. Vui lòng thử lại.');
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Nộp bài'; }
                timerInterval = setInterval(() => {}, 1000); // Restart timer placeholder
            }
        } catch (err) {
            console.error('Submit error:', err);
            alert('Không thể kết nối server. Vui lòng kiểm tra kết nối mạng.');
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Nộp bài'; }
        }
    }

    function autoSubmit() {
        const answered = getAnsweredCount();
        if (answered > 0) {
            submitQuiz();
        } else {
            window.location.href = '/student/quizzes';
        }
    }

    // ── Keyboard shortcuts ───────────────────
    document.onkeydown = function(e) {
        if (e.key === 'ArrowLeft') navigateQuestion(-1);
        if (e.key === 'ArrowRight') navigateQuestion(1);
        if (e.key === 'Escape') closeSubmitModal();
    };

    // ── Warn before leaving ───────────────────
    window.onbeforeunload = function(e) {
        if (getAnsweredCount() > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    };

    // ── Init ─────────────────────────────────
    function init() {
        renderNavGrid();
        renderQuestion(0);
        initTimer();

        // Remove warning when form is submitted normally
        document.querySelector('form')?.addEventListener('submit', () => {
            window.onbeforeunload = null;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
