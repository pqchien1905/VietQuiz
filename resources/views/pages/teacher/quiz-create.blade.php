{{-- Teacher: quiz-create --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.question-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--card);
    margin-bottom: 1rem;
    overflow: hidden;
    transition: box-shadow var(--transition-fast);
}
.question-card:hover { box-shadow: var(--shadow-md); }
.question-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: var(--muted);
    border-bottom: 1px solid var(--border);
}
.question-card-body { padding: 1.25rem; }
.option-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: border-color var(--transition-fast), background-color var(--transition-fast);
}
.option-input.selected {
    border-color: var(--success);
    background: color-mix(in srgb, var(--success) 8%, transparent);
}
.option-input input[type="radio"],
.option-input input[type="checkbox"] { accent-color: var(--success); }
.question-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: var(--text-xs);
    font-weight: 500;
    background: color-mix(in srgb, var(--primary) 10%, transparent);
    color: var(--primary);
}
</style>
@endpush

@section('content')
<!-- Breadcrumb -->
<nav class="breadcrumb">
    <a href="{{ route('teacher.quizzes') }}">Bài kiểm tra</a>
    <span class="breadcrumb-sep">›</span>
    <span class="active" id="breadcrumb-label">Tạo Kỳ thi Mới</span>
</nav>

<!-- Page header -->
<div class="page-header">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 id="page-title">Tạo Kỳ thi Mới</h1>
            <p style="color:var(--muted-foreground);margin-top:0.25rem;">Điền thông tin và thêm câu hỏi cho bài kiểm tra</p>
        </div>
        <div style="display:flex;gap:0.5rem;">
            <a href="{{ route('teacher.quizzes') }}" class="btn btn-outline">Hủy</a>
            <button type="button" class="btn btn-outline" id="save-draft-btn">Lưu Nháp</button>
            <button type="button" class="btn btn-primary" id="publish-btn">Xuất bản</button>
        </div>
    </div>
</div>

<form id="quiz-form" action="{{ route('teacher.quizzes.store') }}" method="POST">
    @csrf

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" id="create-grid">
        <!-- Left: Questions -->
        <div>
            <!-- Basic info -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Thông tin cơ bản</h3>
                </div>
                <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
                    <div class="form-group">
                        <label class="label label-required" for="quiz-title">Tiêu đề bài thi</label>
                        <input type="text" id="quiz-title" name="title" class="input @error('title') input-error @enderror"
                            placeholder="VD: Kiểm tra Cuối kì Toán lớp 10"
                            value="{{ old('title') }}" required />
                        @error('title')
                        <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="label" for="quiz-desc">Mô tả</label>
                        <textarea id="quiz-desc" name="description" class="input @error('description') input-error @enderror"
                            style="min-height:5rem;" placeholder="Mô tả về bài kiểm tra...">{{ old('description') }}</textarea>
                        @error('description')
                        <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="label" for="quiz-class">Lớp học</label>
                            <select id="quiz-class" name="class_id" class="input select @error('class_id') input-error @enderror">
                                <option value="">Chọn lớp học</option>
                                @foreach($courses as $course)
                                    @if($course->classModel)
                                        <option value="{{ $course->classModel->id }}" {{ old('class_id') == $course->classModel->id ? 'selected' : '' }}>
                                            {{ $course->classModel->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('class_id')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="label" for="quiz-course">Khóa học</label>
                            <select id="quiz-course" name="course_id" class="input select @error('course_id') input-error @enderror">
                                <option value="">Chọn khóa học (tùy chọn)</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="label" for="quiz-duration">Thời gian (phút)</label>
                            <input type="number" id="quiz-duration" name="time_limit"
                                class="input @error('time_limit') input-error @enderror"
                                value="{{ old('time_limit', 60) }}" min="1" />
                            @error('time_limit')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="label" for="quiz-passing">Điểm đạt (%)</label>
                            <input type="number" id="quiz-passing" name="passing_score"
                                class="input @error('passing_score') input-error @enderror"
                                value="{{ old('passing_score', 50) }}" min="0" max="100" />
                            @error('passing_score')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions section -->
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="card-title">Câu hỏi</h3>
                            <p class="card-description"><span id="question-count">0</span> câu hỏi đã thêm</p>
                        </div>
                        <div style="display:flex;gap:0.5rem;">
                            <button type="button" class="btn btn-outline btn-sm gap-1" id="add-mc-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Trắc nghiệm
                            </button>
                            <button type="button" class="btn btn-outline btn-sm gap-1" id="add-tf-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Đúng/Sai
                            </button>
                            <button type="button" class="btn btn-outline btn-sm gap-1" id="add-sa-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Tự luận
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    <div id="questions-list">
                        <!-- Empty state -->
                        <div class="empty-state" id="questions-empty">
                            <div class="empty-state-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 10.3c.2-.4.5-.8.9-1a2.1 2.1 0 0 1 2.6.4c.3.4.5.8.5 1.3 0 1.3-2 2-2 2"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </div>
                            <h3>Chưa có câu hỏi nào</h3>
                            <p>Nhấp nút bên trên để thêm câu hỏi</p>
                        </div>
                    </div>

                    <!-- Hidden: questions JSON for old() data -->
                    <input type="hidden" id="questions-json" name="questions_json" value="{{ old('questions_json', '[]') }}" />

                    @error('questions')
                    <span class="error-message" style="margin-top:0.5rem;display:block;">{{ $message }}</span>
                    @enderror
                    @error('questions.*.content')
                    <span class="error-message" style="margin-top:0.5rem;display:block;">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Right: Settings -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">
            <!-- Quiz settings -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cài đặt</h3>
                </div>
                <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
                    <div class="flex items-center justify-between">
                        <div>
                            <div style="font-weight:500;font-size:var(--text-sm);">Xáo trộn câu hỏi</div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Thứ tự ngẫu nhiên cho mỗi học sinh</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="shuffle-questions" name="is_shuffle" value="1" checked />
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div style="font-weight:500;font-size:var(--text-sm);">Hiện đáp án sau khi nộp</div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Học sinh xem đáp án đúng</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="show-answers" name="show_result" value="1" checked />
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div style="font-weight:500;font-size:var(--text-sm);">Xuất bản ngay</div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Học sinh có thể làm được luôn</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="publish-immediate" name="is_published" value="1" />
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="label" for="max-attempts">Số lần làm tối đa</label>
                        <input type="number" id="max-attempts" name="max_attempts"
                            class="input" value="{{ old('max_attempts', 1) }}" min="1" />
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="card" style="background: linear-gradient(135deg, color-mix(in srgb,var(--primary) 8%,transparent), color-mix(in srgb,var(--info) 8%,transparent)); border-color: color-mix(in srgb,var(--primary) 20%,transparent);">
                <div class="card-content">
                    <h4 style="font-size:var(--text-sm);margin-bottom:0.75rem;">Tổng quan</h4>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;font-size:var(--text-sm);">
                        <div class="flex items-center justify-between">
                            <span style="color:var(--muted-foreground);">Số câu hỏi</span>
                            <span id="summary-questions" style="font-weight:600;">0</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span style="color:var(--muted-foreground);">Thời gian</span>
                            <span style="font-weight:600;" id="summary-duration">{{ old('time_limit', 60) }} phút</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span style="color:var(--muted-foreground);">Điểm đạt</span>
                            <span style="font-weight:600;" id="summary-passing">{{ old('passing_score', 50) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validation errors summary -->
            @if($errors->any())
            <div class="card" style="border-color: var(--destructive);">
                <div class="card-content">
                    <h4 style="font-size:var(--text-sm);color:var(--destructive);margin-bottom:0.5rem;">Vui lòng sửa các lỗi sau:</h4>
                    <ul style="font-size:var(--text-xs);color:var(--destructive);padding-left:1rem;">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// ─────────────────────────────────────────────
// Quiz Create JavaScript — manages dynamic questions
// ─────────────────────────────────────────────
(function() {
    'use strict';

    // ── State ──────────────────────────────────
    let questions = [];
    let editingIndex = null;

    // ── Helpers ────────────────────────────────
    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    function getTypeLabel(type) {
        const map = {
            'multiple_choice': 'Trắc nghiệm',
            'true_false': 'Đúng/Sai',
            'short_answer': 'Tự luận',
        };
        return map[type] || type;
    }

    function getTypeIcon(type) {
        const map = {
            'multiple_choice': '◉',
            'true_false': '✓',
            'short_answer': '✎',
        };
        return map[type] || '?';
    }

    function updateSummary() {
        $('#summary-questions').textContent = questions.length;
        const dur = $('#quiz-duration').value || 60;
        $('#summary-duration').textContent = dur + ' phút';
        const ps = $('#quiz-passing').value || 50;
        $('#summary-passing').textContent = ps + '%';
    }

    function updateEmptyState() {
        const empty = $('#questions-empty');
        if (empty) {
            empty.style.display = questions.length === 0 ? 'flex' : 'none';
        }
        $('#question-count').textContent = questions.length;
    }

    // ── Render one question card ───────────────
    function renderQuestionCard(q, idx) {
        const isMc = q.type === 'multiple_choice';
        const isTf = q.type === 'true_false';
        const isSa = q.type === 'short_answer';
        const options = q.options || [];
        const correctIdx = isMc ? parseInt(q.correct_answer) : q.correct_answer;

        let optionsHtml = '';
        if (isMc && options.length) {
            const labels = ['A', 'B', 'C', 'D', 'E', 'F'];
            optionsHtml = `
                <div style="margin-top:0.75rem;display:flex;flex-direction:column;gap:0.5rem;">
                    ${options.map((opt, oi) => `
                        <div class="option-input ${parseInt(correctIdx) === oi ? 'selected' : ''}">
                            <span style="font-weight:600;font-size:var(--text-xs);color:var(--muted-foreground);width:1.25rem;">${labels[oi] || oi+1}</span>
                            <span style="flex:1;">${opt}</span>
                            ${parseInt(correctIdx) === oi ? '<span style="color:var(--success);font-size:var(--text-xs);font-weight:600;">✓ Đúng</span>' : ''}
                        </div>
                    `).join('')}
                </div>`;
        } else if (isTf) {
            const isTrue = correctIdx === 'true' || correctIdx === '1';
            optionsHtml = `
                <div style="margin-top:0.75rem;display:flex;gap:1rem;">
                    <div class="option-input ${isTrue ? 'selected' : ''}" style="flex:1;justify-content:center;font-weight:600;">
                        ✓ True - Đúng
                    </div>
                    <div class="option-input ${!isTrue ? 'selected' : ''}" style="flex:1;justify-content:center;font-weight:600;">
                        ✗ False - Sai
                    </div>
                </div>`;
        } else if (isSa) {
            optionsHtml = `
                <div style="margin-top:0.75rem;padding:0.75rem;background:var(--muted);border-radius:var(--radius-md);">
                    <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:0.25rem;">Đáp án đúng:</div>
                    <div style="font-weight:500;">${q.correct_answer || '(chưa nhập)'}</div>
                </div>`;
        }

        return `
        <div class="question-card" data-index="${idx}">
            <div class="question-card-header">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <span style="font-weight:700;font-size:var(--text-sm);color:var(--muted-foreground);width:1.5rem;text-align:center;">${idx + 1}</span>
                    <span class="question-type-badge">${getTypeIcon(q.type)} ${getTypeLabel(q.type)}</span>
                    ${q.points && q.points > 1 ? `<span class="question-type-badge" style="background:color-mix(in srgb,var(--warning) 10%,transparent);color:var(--warning);">${q.points} điểm</span>` : ''}
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="window._qc.edit(${idx})">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" style="color:var(--destructive);" onclick="window._qc.remove(${idx})">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                </div>
            </div>
            <div class="question-card-body">
                <div style="font-weight:500;margin-bottom:0.5rem;">${q.content || '(chưa có nội dung)'}</div>
                ${optionsHtml}
                ${q.explanation ? `<div style="margin-top:0.5rem;font-size:var(--text-xs);color:var(--muted-foreground);"><strong>Giải thích:</strong> ${q.explanation}</div>` : ''}
            </div>
        </div>`;
    }

    // ── Render all questions ───────────────────
    function renderQuestions() {
        const list = $('#questions-list');
        const empty = $('#questions-empty');

        // Remove existing cards (keep empty state)
        $$('.question-card').forEach(el => el.remove());

        if (questions.length === 0) {
            if (empty) empty.style.display = 'flex';
            return;
        }

        if (empty) empty.style.display = 'none';

        const frag = document.createDocumentFragment();
        questions.forEach((q, idx) => {
            const div = document.createElement('div');
            div.innerHTML = renderQuestionCard(q, idx).trim();
            frag.appendChild(frag.ownerDocument ? frag.ownerDocument.createElement('div') : div, ...div.childNodes);
        });

        // Re-append each card properly
        questions.forEach((q, idx) => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = renderQuestionCard(q, idx);
            list.appendChild(wrapper.firstElementChild);
        });

        updateEmptyState();
        updateSummary();
    }

    // ── Add question ───────────────────────────
    function addQuestion(type, prefilled = {}) {
        const defaultQ = {
            type,
            content: '',
            options: type === 'multiple_choice' ? ['', '', '', ''] : [],
            correct_answer: type === 'true_false' ? 'true' : (type === 'multiple_choice' ? '0' : ''),
            points: 1,
            explanation: '',
        };
        const q = { ...defaultQ, ...prefilled };
        questions.push(q);
        renderQuestions();
        // Auto-open editor for the new question
        editingIndex = questions.length - 1;
        openEditor(editingIndex);
    }

    // ── Update / Remove ───────────────────────
    function updateQuestion(idx, data) {
        if (questions[idx]) {
            questions[idx] = { ...questions[idx], ...data };
            renderQuestions();
        }
    }

    function removeQuestion(idx) {
        if (!confirm(`Xóa câu hỏi ${idx + 1}?`)) return;
        questions.splice(idx, 1);
        renderQuestions();
        updateSummary();
    }

    // ── Question Editor Modal ──────────────────
    function openEditor(idx) {
        editingIndex = idx;
        const q = questions[idx];
        if (!q) return;

        const isMc = q.type === 'multiple_choice';
        const isTf = q.type === 'true_false';

        let optionsEditor = '';
        if (isMc) {
            const labels = ['A', 'B', 'C', 'D'];
            const opts = q.options && q.options.length ? q.options : ['', '', '', ''];
            const correct = parseInt(q.correct_answer) || 0;
            optionsEditor = `
                <div class="form-group">
                    <label class="label label-required">Các đáp án</label>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;">
                        ${labels.map((lbl, oi) => `
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <input type="radio" name="correct_option_edit" value="${oi}" ${correct === oi ? 'checked' : ''} style="accent-color:var(--success);" />
                                <span style="font-weight:600;width:1.5rem;color:var(--muted-foreground);">${lbl}.</span>
                                <input type="text" class="input" id="edit-opt-${oi}"
                                    value="${opts[oi] || ''}"
                                    placeholder="Nhập đáp án ${lbl}..."
                                    style="flex:1;" />
                            </div>
                        `).join('')}
                    </div>
                    <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">
                        Chọn radio bên cạnh đáp án đúng
                    </div>
                </div>`;
        } else if (isTf) {
            optionsEditor = `
                <div class="form-group">
                    <label class="label label-required">Đáp án đúng</label>
                    <div style="display:flex;gap:1rem;">
                        <label class="option-input" style="flex:1;justify-content:center;cursor:pointer;">
                            <input type="radio" name="correct_tf_edit" value="true" ${q.correct_answer === 'true' ? 'checked' : ''} style="accent-color:var(--success);" />
                            <span style="font-weight:600;">✓ True — Đúng</span>
                        </label>
                        <label class="option-input" style="flex:1;justify-content:center;cursor:pointer;">
                            <input type="radio" name="correct_tf_edit" value="false" ${q.correct_answer === 'false' ? 'checked' : ''} style="accent-color:var(--success);" />
                            <span style="font-weight:600;">✗ False — Sai</span>
                        </label>
                    </div>
                </div>`;
        }

        const modalHtml = `
        <div id="question-editor-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;">
            <div id="question-editor-modal" style="background:var(--card);border-radius:var(--radius-xl);width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-xl);">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);">
                    <h3 style="font-size:var(--text-lg);font-weight:700;">Sửa câu hỏi ${idx + 1}</h3>
                    <button type="button" onclick="window._qc.closeEditor()" style="background:none;border:none;cursor:pointer;padding:0.25rem;color:var(--muted-foreground);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
                    <div class="form-group">
                        <label class="label label-required" for="edit-content">Nội dung câu hỏi</label>
                        <textarea id="edit-content" class="input @error('content') input-error @enderror"
                            style="min-height:5rem;"
                            placeholder="Nhập nội dung câu hỏi...">${q.content || ''}</textarea>
                    </div>

                    ${optionsEditor}

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="label" for="edit-points">Điểm số</label>
                            <input type="number" id="edit-points" class="input" value="${q.points || 1}" min="1" max="100" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label" for="edit-explanation">Giải thích / Phản hồi (tùy chọn)</label>
                        <textarea id="edit-explanation" class="input" style="min-height:3rem;"
                            placeholder="Giải thích đáp án đúng...">${q.explanation || ''}</textarea>
                    </div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-outline" onclick="window._qc.closeEditor()">Hủy</button>
                    <button type="button" class="btn btn-primary" id="save-edit-btn">Lưu câu hỏi</button>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Auto-focus content
        setTimeout(() => {
            const c = $('#edit-content');
            if (c) c.focus();
        }, 100);

        // Save handler
        $('#save-edit-btn').onclick = function() {
            const content = $('#edit-content').value.trim();
            const points = parseInt($('#edit-points').value) || 1;
            const explanation = $('#edit-explanation').value.trim();

            if (!content) {
                alert('Vui lòng nhập nội dung câu hỏi!');
                return;
            }

            let correct_answer = q.correct_answer;
            if (isMc) {
                const radios = document.getElementsByName('correct_option_edit');
                for (const r of radios) {
                    if (r.checked) { correct_answer = r.value; break; }
                }
                const newOpts = ['A','B','C','D'].map((_, oi) => {
                    const inp = $(`#edit-opt-${oi}`);
                    return inp ? inp.value.trim() : '';
                });
                updateQuestion(editingIndex, { content, options: newOpts, correct_answer, points, explanation });
            } else if (isTf) {
                const tfRadios = document.getElementsByName('correct_tf_edit');
                for (const r of tfRadios) {
                    if (r.checked) { correct_answer = r.value; break; }
                }
                updateQuestion(editingIndex, { content, correct_answer, points, explanation });
            } else {
                const saInput = $('#edit-sa-answer');
                correct_answer = saInput ? saInput.value.trim() : '';
                updateQuestion(editingIndex, { content, correct_answer, points, explanation });
            }

            closeEditor();
        };

        // Close on overlay click
        $('#question-editor-overlay').onclick = function(e) {
            if (e.target === this) closeEditor();
        };
    }

    function closeEditor() {
        const overlay = $('#question-editor-overlay');
        if (overlay) overlay.remove();
        editingIndex = null;
    }

    // ── Build hidden inputs for form ───────────
    function buildHiddenInputs() {
        // Remove old hidden inputs
        $$('.question-hidden-input').forEach(el => el.remove());

        questions.forEach((q, idx) => {
            const createHidden = (name, val) => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = name;
                inp.value = val !== null && val !== undefined ? val : '';
                inp.className = 'question-hidden-input';
                $('#quiz-form').appendChild(inp);
            };

            createHidden(`questions[${idx}][content]`, q.content);
            createHidden(`questions[${idx}][type]`, q.type);
            createHidden(`questions[${idx}][correct_answer]`, q.correct_answer);
            createHidden(`questions[${idx}][points]`, q.points || 1);
            createHidden(`questions[${idx}][explanation]`, q.explanation || '');

            if (q.options && Array.isArray(q.options)) {
                q.options.forEach((opt, oi) => {
                    createHidden(`questions[${idx}][options][${oi}]`, opt);
                });
            }
        });
    }

    // ── Validate before submit ─────────────────
    function validateForm() {
        const title = $('#quiz-title').value.trim();
        if (!title) {
            alert('Vui lòng nhập tiêu đề bài thi!');
            $('#quiz-title').focus();
            return false;
        }
        if (questions.length === 0) {
            alert('Vui lòng thêm ít nhất 1 câu hỏi!');
            return false;
        }
        for (let i = 0; i < questions.length; i++) {
            const q = questions[i];
            if (!q.content || !q.content.trim()) {
                alert(`Câu hỏi ${i + 1}: Vui lòng nhập nội dung câu hỏi!`);
                openEditor(i);
                return false;
            }
            if (!q.correct_answer && q.correct_answer !== '0' && q.correct_answer !== 0 && q.correct_answer !== 'true' && q.correct_answer !== 'false') {
                alert(`Câu hỏi ${i + 1}: Vui lòng chọn đáp án đúng!`);
                openEditor(i);
                return false;
            }
            if (q.type === 'multiple_choice') {
                const opts = q.options || [];
                const filled = opts.filter(o => o && o.trim()).length;
                if (filled < 2) {
                    alert(`Câu hỏi ${i + 1}: Cần ít nhất 2 đáp án cho câu trắc nghiệm!`);
                    openEditor(i);
                    return false;
                }
            }
        }
        return true;
    }

    // ── Init ───────────────────────────────────
    function init() {
        // Load old() data if available
        try {
            const oldJson = $('#questions-json').value;
            if (oldJson && oldJson !== '[]') {
                const loaded = JSON.parse(oldJson);
                if (Array.isArray(loaded) && loaded.length > 0) {
                    questions = loaded;
                }
            }
        } catch(e) {}

        renderQuestions();
        updateSummary();

        // Add question buttons
        $('#add-mc-btn').onclick = () => addQuestion('multiple_choice');
        $('#add-tf-btn').onclick = () => addQuestion('true_false');
        $('#add-sa-btn').onclick = () => addQuestion('short_answer');

        // Summary updates
        $('#quiz-duration').oninput = updateSummary;
        $('#quiz-passing').oninput = updateSummary;

        // Publish button
        $('#publish-btn').onclick = function() {
            if (!validateForm()) return;
            buildHiddenInputs();
            // Auto-check publish
            const pubCheck = $('#publish-immediate');
            if (pubCheck) pubCheck.checked = true;
            $('#quiz-form').submit();
        };

        // Save draft button
        $('#save-draft-btn').onclick = function() {
            if (!validateForm()) return;
            buildHiddenInputs();
            // Ensure not published
            const pubCheck = $('#publish-immediate');
            if (pubCheck) pubCheck.checked = false;
            $('#quiz-form').submit();
        };

        // Close editor on Escape
        document.onkeydown = function(e) {
            if (e.key === 'Escape') closeEditor();
        };
    }

    // Expose API globally
    window._qc = {
        add: addQuestion,
        edit: openEditor,
        remove: removeQuestion,
        closeEditor,
        updateQuestion,
        getQuestions: () => questions,
    };

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
