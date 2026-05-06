{{-- Student: quiz-take --}}
@extends('layouts.app')

@push('styles')
<style>
body { background: color-mix(in srgb, var(--muted) 45%, var(--background)); }
.quiz-shell { min-height: 100vh; display: flex; flex-direction: column; width: 100%; }
.quiz-header {
    background: var(--card);
    border-bottom: 1px solid var(--border);
    padding: 1rem 1.875rem;
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
@keyframes slideInRight {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes slideOutRight {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(20px); }
}
.quiz-body {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 260px;
    gap: 1.5rem;
    padding: 1.875rem;
    max-width: 1320px;
    margin: 0 auto;
    width: 100%;
}
.question-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1.75rem;
    min-height: calc(100vh - 12rem);
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
.submit-sidebar-btn.is-loading,
.btn.is-loading {
    opacity: 0.75;
    cursor: wait;
}
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
.autosave-status {
    min-height: 1rem;
    margin-top: 0.45rem;
    font-size: var(--text-xs);
    color: var(--muted-foreground);
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
.exit-modal { max-width: 460px; }
.exit-modal-warning {
    background: color-mix(in srgb, var(--warning) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--warning) 28%, transparent);
    border-radius: var(--radius-md);
    color: color-mix(in srgb, var(--warning) 78%, black);
    font-size: var(--text-sm);
    line-height: 1.55;
    margin-bottom: 1.25rem;
    padding: 0.875rem 1rem;
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
.exam-guard-banner {
    background: color-mix(in srgb, var(--warning) 9%, transparent);
    border: 1px solid color-mix(in srgb, var(--warning) 28%, transparent);
    border-radius: var(--radius-md);
    color: var(--warning);
    font-size: var(--text-xs);
    font-weight: 600;
    line-height: 1.5;
    margin-top: 0.875rem;
    padding: 0.75rem 0.875rem;
}
@media (max-width: 900px) {
    .quiz-body { grid-template-columns: 1fr; }
    .q-nav { position: static; }
}
@media (max-width: 640px) {
    .quiz-header { align-items:flex-start; flex-direction:column; padding:1rem; }
    .quiz-header > div { width:100%; justify-content:space-between; flex-wrap:wrap; }
    .quiz-body { padding:1rem; }
    .submit-modal-actions { flex-direction:column; }
}
</style>
@endpush

@section('body')
<div class="quiz-shell">
    @if(session('info') || session('success') || session('warning'))
        <div style="padding:1rem 1.5rem 0;">
            @if(session('info'))
                <div class="alert alert-info" style="margin:0;">{{ session('info') }}</div>
            @elseif(session('success'))
                <div class="alert alert-success" style="margin:0;">{{ session('success') }}</div>
            @elseif(session('warning'))
                <div class="alert alert-warning" style="margin:0;">{{ session('warning') }}</div>
            @endif
        </div>
    @endif
    <!-- Header -->
    <div class="quiz-header">
        <div style="display:flex;align-items:center;gap:1rem;">
            <button type="button" class="btn btn-ghost btn-sm gap-1" onclick="showExitModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Thoát
            </button>
            <div>
                <div style="font-weight:700;font-size:var(--text-base);">{{ $quiz->title }}</div>
                <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Câu <span id="current-num">1</span> / <span id="total-num">{{ $quiz->questions->count() }}</span></div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:1rem;">
            @if($quiz->quiz_type === 'practice')
            <div style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;background:color-mix(in srgb,var(--info) 10%,transparent);color:var(--info);border-radius:var(--radius-md);font-size:var(--text-xs);font-weight:600;border:1px solid color-mix(in srgb,var(--info) 30%,transparent);">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Luyện tập
            </div>
            @else
            <div style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;background:color-mix(in srgb,var(--warning) 10%,transparent);color:var(--warning);border-radius:var(--radius-md);font-size:var(--text-xs);font-weight:600;border:1px solid color-mix(in srgb,var(--warning) 30%,transparent);">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Kiểm tra
            </div>
            @endif
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
            <div class="autosave-status" id="autosave-status" aria-live="polite"></div>
            @if($quiz->quiz_type === 'exam' && ($quiz->anti_cheat_enabled ?? true))
            <div class="exam-guard-banner" id="exam-guard-banner">
                Bài kiểm tra đang bật giám sát: không mở DevTools, không rời màn hình làm bài và không thoát toàn màn hình.
            </div>
            @endif
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
@php
    $quizPayload = [
        'id' => $quiz->id,
        'user_id' => auth()->id(),
        'title' => $quiz->title,
        'time_limit' => $quiz->time_limit,
        'started_at' => $startedAt->toIso8601String(),
        'quiz_type' => $quiz->quiz_type ?? 'exam',
        'anti_cheat_enabled' => (bool) ($quiz->anti_cheat_enabled ?? true),
        'submit_url' => route('student.quiz-take.submit', $quiz),
        'violation_url' => route('student.quiz-take.violations', $quiz),
        'result_url' => route('student.quiz-result', $quiz),
        'list_url' => route('student.quizzes'),
        'max_violations' => max(1, (int) config('vietquiz.anti_cheat.max_violations', 3)),
    ];

    $questionPayload = $quiz->questions->values()->map(fn($q, $i) => [
        'idx' => $i,
        'id' => $q->id,
        'content' => $q->content,
        'type' => $q->type,
        'options' => $q->shuffled_options ?? $q->options ?? [],
        'points' => $q->points ?? 1,
    ]);
@endphp
<script>
// ─────────────────────────────────────────────
// Quiz Take JavaScript
// ─────────────────────────────────────────────
(function() {
    'use strict';

    // ── Data from server ────────────────────
    const QUIZ_DATA = @json($quizPayload);

    const QUESTIONS = @json($questionPayload);

    const TOTAL = QUESTIONS.length;
    const IS_EXAM = QUIZ_DATA.quiz_type === 'exam';
    const IS_GUARDED_EXAM = IS_EXAM && QUIZ_DATA.anti_cheat_enabled;
    const AUTOSAVE_KEY = [
        'vietquiz',
        'quiz-draft',
        QUIZ_DATA.user_id,
        QUIZ_DATA.id,
        QUIZ_DATA.started_at,
    ].join(':');

    // ── State ────────────────────────────────
    let currentIdx = 0;
    let answers = {};
    let timerInterval = null;
    let isSubmitting = false;
    let autosaveStatusTimer = null;

    // ── Helpers ───────────────────────────────
    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function notifyUser(message) {
        if (typeof window.showAppAlert === 'function') {
            window.showAppAlert(message);
            return;
        }
        alert(message);
    }

    function getAnsweredCount() {
        return Object.keys(answers).filter(k => answers[k] !== '' && answers[k] !== null && answers[k] !== undefined).length;
    }

    function setAutosaveStatus(message) {
        const el = $('#autosave-status');
        if (!el) return;

        el.textContent = message;
        if (autosaveStatusTimer) clearTimeout(autosaveStatusTimer);
        autosaveStatusTimer = setTimeout(() => {
            el.textContent = '';
        }, 2500);
    }

    function loadAutosavedAnswers() {
        try {
            const raw = localStorage.getItem(AUTOSAVE_KEY);
            if (!raw) return;

            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed.answers !== 'object' || Array.isArray(parsed.answers)) return;

            const validQuestionIds = new Set(QUESTIONS.map(q => String(q.id)));
            answers = Object.fromEntries(
                Object.entries(parsed.answers).filter(([questionId]) => validQuestionIds.has(String(questionId)))
            );

            if (getAnsweredCount() > 0) {
                setAutosaveStatus('Đã khôi phục câu trả lời nháp.');
            }
        } catch (error) {
            console.warn('Autosave restore failed:', error);
        }
    }

    function saveAnswersDraft() {
        try {
            localStorage.setItem(AUTOSAVE_KEY, JSON.stringify({
                answers,
                saved_at: new Date().toISOString(),
            }));
            setAutosaveStatus('Đã lưu nháp.');
        } catch (error) {
            console.warn('Autosave failed:', error);
            setAutosaveStatus('Không thể lưu nháp trên trình duyệt này.');
        }
    }

    function clearAnswersDraft() {
        try {
            localStorage.removeItem(AUTOSAVE_KEY);
        } catch (error) {
            console.warn('Autosave cleanup failed:', error);
        }
    }

    // ── Timer ─────────────────────────────────
    function initTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

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
                submitQuiz({ force: true });
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
        if (!q) {
            $('#question-inner').innerHTML = '<div style="text-align:center;padding:2rem;color:var(--muted-foreground);">Bài kiểm tra chưa có câu hỏi khả dụng.</div>';
            return;
        }
        currentIdx = idx;
        $('#current-num').textContent = idx + 1;

        const prevBtn = $('#prev-btn');
        const nextBtn = $('#next-btn');
        if (prevBtn) prevBtn.disabled = idx === 0;
        if (nextBtn) nextBtn.disabled = idx === TOTAL - 1;

        $$('.q-nav-btn').forEach((btn, i) => btn.classList.toggle('current', i === idx));

        const inner = $('#question-inner');
        const labels = ['A', 'B', 'C', 'D', 'E', 'F'];
        const currentAnswer = answers[q.id] ?? '';

        let html = `
            <div class="question-content">${idx + 1}. ${escapeHtml(q.content)}</div>
            <div style="margin-bottom:0.5rem;">
                <span class="question-type-badge" style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.6rem;border-radius:9999px;font-size:var(--text-xs);font-weight:500;background:color-mix(in srgb,var(--primary) 10%,transparent);color:var(--primary);">
                    ${q.type === 'multiple_choice' ? '◉ Trắc nghiệm' : q.type === 'true_false' ? '✓ Đúng/Sai' : '✎ Tự luận'}
                </span>
                ${q.points > 1 ? `<span style="margin-left:0.5rem;font-size:var(--text-xs);color:var(--muted-foreground);">${q.points} điểm</span>` : ''}
            </div>`;

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
                        <span>${escapeHtml(opt)}</span>
                    </button>`;
            });
            html += `</div>`;

        } else if (q.type === 'true_false') {
            const trueLabel = q.options && q.options[0] ? q.options[0] : 'Đúng';
            const falseLabel = q.options && q.options[1] ? q.options[1] : 'Sai';
            html += `<div class="tf-grid">
                <button class="tf-btn ${currentAnswer === 'true' ? 'selected' : ''}"
                    onclick="selectOption(${q.id}, 'true', '${q.type}')">
                    ✓ ${escapeHtml(trueLabel)}
                </button>
                <button class="tf-btn ${currentAnswer === 'false' ? 'selected' : ''}"
                    onclick="selectOption(${q.id}, 'false', '${q.type}')">
                    ✗ ${escapeHtml(falseLabel)}
                </button>
            </div>`;

        } else {
            html += `
                <textarea class="short-answer-input"
                    id="sa-answer-${q.id}"
                    placeholder="Nhập câu trả lời của bạn..."
                    oninput="selectOption(${q.id}, this.value, '${q.type}')"
                >${escapeHtml(currentAnswer || '')}</textarea>`;
        }

        html += `<div id="per-question-feedback"></div>`;
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
        if (submitBtn) submitBtn.disabled = false;
    }

    // ── Select Option ────────────────────────
    function selectOption(questionId, value, type) {
        answers[questionId] = value;

        if (type === 'multiple_choice') {
            $$('.option-btn').forEach(btn => {
                btn.classList.toggle('selected', parseInt(btn.dataset.optIdx) === parseInt(value));
            });
        } else if (type === 'true_false') {
            $$('.tf-btn').forEach(btn => {
                const text = btn.textContent.trim();
                btn.classList.toggle('selected', (value === 'true' && text.startsWith('✓')) || (value === 'false' && text.startsWith('✗')));
            });
        }

        const qIdx = QUESTIONS.findIndex(q => q.id === questionId);
        if (qIdx >= 0) {
            const btn = $$('.q-nav-btn')[qIdx];
            if (btn) btn.classList.add('answered');
        }

        renderNavGrid();
        updateProgress();
        saveAnswersDraft();
    }

    // ── Navigate ──────────────────────────────
    function navigateQuestion(dir) {
        const next = currentIdx + dir;
        if (next >= 0 && next < TOTAL) goToQuestion(next);
    }

    function goToQuestion(idx) { renderQuestion(idx); renderNavGrid(); }

    // ── Submit Modal ─────────────────────────
    function showSubmitModal() {
        const answered = getAnsweredCount();
        const unanswered = TOTAL - answered;
        const modeText = IS_EXAM ? 'Kiểm tra' : 'Luyện tập';
        const unansweredText = unanswered > 0
            ? `<p style="color:var(--warning);font-weight:600;">Bạn còn <strong>${unanswered}</strong> câu chưa trả lời!</p>`
            : `<p style="color:var(--success);font-weight:600;">Bạn đã trả lời đủ tất cả câu hỏi.</p>`;

        const overlay = document.createElement('div');
        overlay.className = 'submit-modal-overlay';
        overlay.id = 'submit-modal-overlay';
        overlay.innerHTML = `
            <div class="submit-modal">
                <h3>Xác nhận nộp bài ${modeText}?</h3>
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

    function showExitModal() {
        const answered = getAnsweredCount();
        const unanswered = TOTAL - answered;
        const overlay = document.createElement('div');
        overlay.className = 'submit-modal-overlay';
        overlay.id = 'exit-modal-overlay';
        overlay.innerHTML = `
            <div class="submit-modal exit-modal">
                <h3>Bạn muốn thoát khỏi bài kiểm tra?</h3>
                <p>Bạn đang làm bài <strong>${escapeHtml(QUIZ_DATA.title)}</strong> và đã trả lời <strong>${answered}/${TOTAL}</strong> câu.</p>
                <div class="exit-modal-warning">
                    Nếu thoát, hệ thống sẽ nộp bài với các câu trả lời hiện tại rồi đưa bạn ra khỏi màn hình làm bài. Hành động này không thể hoàn tác.
                    ${unanswered > 0 ? `<br><strong>Bạn còn ${unanswered} câu chưa trả lời.</strong>` : ''}
                </div>
                <div class="submit-modal-actions">
                    <button class="btn btn-outline" style="flex:1;" onclick="closeExitModal()">Tiếp tục ở lại làm bài</button>
                    <button class="btn btn-primary" style="flex:1;" id="exit-submit-btn">Nộp bài và thoát</button>
                </div>
            </div>`;
        overlay.onclick = (e) => { if (e.target === overlay) closeExitModal(); };
        document.body.appendChild(overlay);
        document.getElementById('exit-submit-btn')?.addEventListener('click', () => {
            submitQuiz({ redirectUrl: QUIZ_DATA.list_url });
        });
    }

    function closeExitModal() {
        const m = $('#exit-modal-overlay');
        if (m) m.remove();
    }

    // ── Submit Quiz ───────────────────────────
    async function submitQuiz(options = {}) {
        if (isSubmitting) return;
        isSubmitting = true;
        closeSubmitModal();
        closeExitModal();
        clearInterval(timerInterval);
        window.onbeforeunload = null;

        // Build answers object keyed by question id
        const answersPayload = {};
        QUESTIONS.forEach(q => {
            answersPayload[q.id] = answers[q.id] ?? '';
        });

        const submitBtn = $('#submit-btn-top');
        const sidebarBtn = $('#submit-sidebar-btn');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('is-loading'); submitBtn.textContent = 'Đang nộp...'; }
        if (sidebarBtn) { sidebarBtn.disabled = true; sidebarBtn.classList.add('is-loading'); sidebarBtn.textContent = 'Đang nộp...'; }

        try {
            const response = await fetch(QUIZ_DATA.submit_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ answers: answersPayload }),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                clearAnswersDraft();
                window.location.href = options.redirectUrl || data.redirect_url || QUIZ_DATA.result_url;
            } else {
                throw new Error(data.error || 'Có lỗi xảy ra. Vui lòng thử lại.');
            }
        } catch (err) {
            console.error('Submit error:', err);
            if (!options.force) {
                notifyUser(err.message || 'Không thể kết nối server. Vui lòng kiểm tra kết nối mạng.');
            }
            isSubmitting = false;
            window.onbeforeunload = beforeUnloadHandler;
            if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('is-loading'); submitBtn.textContent = 'Nộp bài'; }
            if (sidebarBtn) { sidebarBtn.disabled = false; sidebarBtn.classList.remove('is-loading'); sidebarBtn.textContent = 'Nộp bài ngay'; }
            initTimer();
        }
    }

    // ── Keyboard shortcuts ───────────────────
    document.onkeydown = function(e) {
        if (e.key === 'ArrowLeft') navigateQuestion(-1);
        if (e.key === 'ArrowRight') navigateQuestion(1);
        if (e.key === 'Escape') {
            closeSubmitModal();
            closeExitModal();
        }
    };

    // ── Warn before leaving ───────────────────
    function beforeUnloadHandler(e) {
        if (getAnsweredCount() > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    }
    window.onbeforeunload = beforeUnloadHandler;

    // ── Anti-Cheat Measures (exam mode only) ──
    let violationCount = 0;
    let lastFocusWarningAt = 0;
    let lastDevtoolsWarningAt = 0;
    let isAutoSubmitting = false;
    let finalWarningShown = false;
    const MAX_VIOLATIONS = QUIZ_DATA.max_violations || 3;
    const DEVTOOLS_THRESHOLD = 160;

    async function logAntiCheatViolation(eventType, metadata = {}) {
        if (!IS_GUARDED_EXAM || !QUIZ_DATA.violation_url) return null;

        try {
            const response = await fetch(QUIZ_DATA.violation_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    event_type: eventType,
                    metadata: {
                        ...metadata,
                        url: window.location.href,
                        user_agent: navigator.userAgent,
                        client_time: new Date().toISOString(),
                    },
                }),
            });

            return await response.json().catch(() => null);
        } catch (error) {
            console.warn('Anti-cheat log failed:', error);
            return null;
        }
    }

    function showAntiCheatWarning(msg, countViolation = true, eventType = 'focus_lost', metadata = {}) {
        if (!IS_GUARDED_EXAM) return;
        if (countViolation) violationCount++;

        const existing = document.getElementById('anticheat-toast');
        if (existing) existing.remove();

        const warningText = countViolation
            ? msg + ' (' + violationCount + '/' + MAX_VIOLATIONS + ' lần)'
            : msg;
        const toast = document.createElement('div');
        toast.id = 'anticheat-toast';
        toast.style.cssText = 'position:fixed;top:5rem;right:1rem;z-index:99999;background:var(--destructive);color:#fff;padding:0.875rem 1.25rem;border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:500;box-shadow:var(--shadow-lg);max-width:300px;animation:slideInRight 0.3s ease';
        toast.textContent = warningText;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.animation = 'slideOutRight 0.3s ease forwards'; setTimeout(() => toast.remove(), 300); }, 4000);

        if (countViolation) {
            logAntiCheatViolation(eventType, metadata).then(data => {
                if (!data) return;
                if (Number.isInteger(data.violation_count)) {
                    violationCount = data.violation_count;
                }
                if ((data.should_auto_submit || data.should_redirect) && !isAutoSubmitting) {
                    isAutoSubmitting = true;
                    notifyUser(data.time_expired
                        ? 'Thời gian làm bài đã kết thúc. Hệ thống đã đóng lượt làm bài.'
                        : 'Bạn đã vi phạm quá số lần cho phép. Hệ thống đã đóng lượt làm bài.');
                    window.onbeforeunload = null;
                    setTimeout(() => {
                        window.location.href = data.redirect_url || QUIZ_DATA.result_url;
                    }, 700);
                }
            });
        }

        if (violationCount >= MAX_VIOLATIONS && !finalWarningShown) {
            finalWarningShown = true;
            const finalToast = document.createElement('div');
            finalToast.id = 'anticheat-final-toast';
            finalToast.style.cssText = 'position:fixed;top:9rem;right:1rem;z-index:99999;background:var(--destructive);color:#fff;padding:0.875rem 1.25rem;border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:700;box-shadow:var(--shadow-lg);max-width:320px;animation:slideInRight 0.3s ease';
            finalToast.textContent = 'Bạn đã vi phạm quá số lần cho phép. Hệ thống sẽ tự động nộp bài.';
            document.body.appendChild(finalToast);
        }
    }

    // Fullscreen prompt on start
    function promptFullscreen() {
        if (!IS_GUARDED_EXAM) return;

        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:99999;display:flex;align-items:center;justify-content:center;padding:1rem;';
        overlay.innerHTML = '<div style="background:var(--card);border-radius:var(--radius-xl);padding:2rem;max-width:430px;text-align:center;box-shadow:var(--shadow-xl);"><h3 style="font-size:var(--text-xl);font-weight:800;margin-bottom:0.75rem;">Bật chế độ toàn màn hình</h3><p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1.5rem;">Bài kiểm tra yêu cầu toàn màn hình. Nếu rời màn hình, thoát fullscreen hoặc mở công cụ nhà phát triển, hệ thống sẽ cảnh báo và ghi nhận vi phạm.</p><div style="display:flex;gap:0.75rem;justify-content:center;"><button id="fs-accept" class="btn btn-primary">Bắt đầu làm bài</button></div></div>';
        document.body.appendChild(overlay);
        document.getElementById('fs-accept').onclick = () => {
            try {
                const el = document.documentElement;
                const request = el.requestFullscreen || el.webkitRequestFullscreen;
                const result = request ? request.call(el) : null;
                if (result && typeof result.catch === 'function') {
                    result.catch(() => {});
                }
            } catch (e) {}
            overlay.remove();
        };
    }

    function isFullscreen() {
        return document.fullscreenElement || document.webkitFullscreenElement;
    }

    function registerExamGuard() {
        if (!IS_GUARDED_EXAM) return;

        ['copy', 'cut', 'paste'].forEach(eventName => {
            document.addEventListener(eventName, event => {
                event.preventDefault();
                showAntiCheatWarning('Không được sao chép, cắt hoặc dán nội dung trong bài kiểm tra.', true, eventName, {
                    target: event.target?.tagName || null,
                });
            });
        });

        document.addEventListener('contextmenu', event => {
            event.preventDefault();
            showAntiCheatWarning('Không được click chuột phải trong bài kiểm tra.', true, 'context_menu');
        });

        document.addEventListener('keydown', event => {
            const key = String(event.key).toLowerCase();
            const blocked =
                event.key === 'F12'
                || (event.ctrlKey && event.shiftKey && ['i', 'j', 'c'].includes(key))
                || (event.ctrlKey && ['u', 's', 'p'].includes(key))
                || (event.metaKey && event.altKey && ['i', 'j', 'c'].includes(key));

            if (!blocked) return;
            event.preventDefault();
            event.stopPropagation();
            showAntiCheatWarning('Không được mở DevTools hoặc dùng phím tắt hệ thống trong bài kiểm tra.', true, 'blocked_shortcut', {
                key: event.key,
                ctrl: event.ctrlKey,
                shift: event.shiftKey,
                alt: event.altKey,
                meta: event.metaKey,
            });
        }, true);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) return;
            showAntiCheatWarning('Bạn đã rời khỏi tab làm bài. Hành vi này đang được ghi nhận.', true, 'tab_hidden');
        });

        window.addEventListener('blur', () => {
            const now = Date.now();
            if (now - lastFocusWarningAt < 1200) return;
            lastFocusWarningAt = now;
            showAntiCheatWarning('Cửa sổ làm bài không còn được focus. Hành vi này đang được ghi nhận.', true, 'focus_lost');
        });

        document.addEventListener('fullscreenchange', () => {
            if (isFullscreen()) return;
            showAntiCheatWarning('Bạn đã thoát chế độ toàn màn hình trong bài kiểm tra.', true, 'fullscreen_exit');
        });

        document.addEventListener('webkitfullscreenchange', () => {
            if (isFullscreen()) return;
            showAntiCheatWarning('Bạn đã thoát chế độ toàn màn hình trong bài kiểm tra.', true, 'fullscreen_exit');
        });

        window.setInterval(() => {
            const widthGap = window.outerWidth - window.innerWidth;
            const heightGap = window.outerHeight - window.innerHeight;
            if (widthGap < DEVTOOLS_THRESHOLD && heightGap < DEVTOOLS_THRESHOLD) return;

            const now = Date.now();
            if (now - lastDevtoolsWarningAt < 5000) return;
            lastDevtoolsWarningAt = now;
            showAntiCheatWarning('Hệ thống phát hiện cửa sổ DevTools hoặc vùng hiển thị bất thường.', true, 'devtools_detected', {
                width_gap: widthGap,
                height_gap: heightGap,
            });
        }, 1500);
    }

    // ── Init ─────────────────────────────────
    function init() {
        loadAutosavedAnswers();
        renderNavGrid();
        renderQuestion(0);
        initTimer();
        registerExamGuard();
        promptFullscreen();

        // Remove warning when form is submitted normally
        document.querySelector('form')?.addEventListener('submit', () => {
            window.onbeforeunload = null;
        });
    }

    Object.assign(window, {
        closeSubmitModal,
        closeExitModal,
        goToQuestion,
        navigateQuestion,
        selectOption,
        showExitModal,
        showSubmitModal,
        submitQuiz,
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
