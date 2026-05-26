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
.mode-tab {
    display: flex;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    overflow: hidden;
}
.mode-tab-btn {
    flex: 1;
    padding: 0.625rem 1rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--muted-foreground);
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.mode-tab-btn.active {
    background: var(--primary);
    color: #fff;
}
.mode-tab-btn:not(.active):hover {
    background: var(--muted);
}
.anti-cheat-toggle {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--warning) 7%, transparent);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 0.75rem;
    padding: 0.875rem 1rem;
}
.anti-cheat-toggle.is-disabled {
    background: var(--muted);
    opacity: 0.65;
}
.anti-cheat-toggle__title {
    font-size: var(--text-sm);
    font-weight: 700;
    margin-bottom: 0.125rem;
}
.anti-cheat-toggle__desc {
    color: var(--muted-foreground);
    font-size: var(--text-xs);
    line-height: 1.5;
}
.switch {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    flex-shrink: 0;
}
.switch input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.switch span {
    width: 2.75rem;
    height: 1.5rem;
    border-radius: 999px;
    background: var(--muted-foreground);
    position: relative;
    transition: background-color var(--transition-fast);
}
.switch span::after {
    content: "";
    width: 1.125rem;
    height: 1.125rem;
    border-radius: 999px;
    background: #fff;
    position: absolute;
    left: 0.1875rem;
    top: 0.1875rem;
    transition: transform var(--transition-fast);
    box-shadow: var(--shadow-sm);
}
.switch input:checked + span {
    background: var(--warning);
}
.switch input:checked + span::after {
    transform: translateX(1.25rem);
}
.switch input:disabled + span {
    cursor: not-allowed;
    opacity: 0.65;
}
.assign-tab {
    display: flex;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    overflow: hidden;
    margin-bottom: 0.75rem;
}
.assign-tab-btn {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: var(--text-xs);
    font-weight: 500;
    color: var(--muted-foreground);
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    text-align: center;
}
.assign-tab-btn.active {
    background: color-mix(in srgb, var(--primary) 10%, transparent);
    color: var(--primary);
    font-weight: 600;
}
.assign-tab-btn:not(.active):hover {
    background: var(--muted);
}
.assign-tab-btn + .assign-tab-btn {
    border-left: 1px solid var(--border);
}
.student-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    background: var(--muted);
    border: 1px solid var(--border);
    border-radius: 9999px;
    font-size: var(--text-xs);
    font-weight: 500;
    margin: 0.25rem;
    cursor: default;
}
.student-chip .chip-remove {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--muted-foreground);
    padding: 0;
    line-height: 1;
    font-size: 14px;
    display: flex;
    align-items: center;
}
.student-chip .chip-remove:hover { color: var(--destructive); }
.student-search-wrap {
    position: relative;
}
.student-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    z-index: 50;
    max-height: 220px;
    overflow-y: auto;
    display: none;
}
.student-search-results.show { display: block; }
.student-search-item {
    padding: 0.625rem 0.875rem;
    cursor: pointer;
    font-size: var(--text-sm);
    display: flex;
    align-items: center;
    gap: 0.625rem;
    border-bottom: 1px solid var(--border);
}
.student-search-item:last-child { border-bottom: none; }
.student-search-item:hover { background: var(--muted); }
.student-search-item.already-added { opacity: 0.4; pointer-events: none; }
.student-avatar-sm {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}
.ai-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.ai-modal-overlay.open {
    display: flex;
}
.ai-modal {
    background: var(--card);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
    max-height: 90vh;
    max-width: 620px;
    overflow-y: auto;
    width: 100%;
}
.ai-modal__header,
.ai-modal__footer {
    align-items: center;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
}
.ai-modal__footer {
    border-bottom: 0;
    border-top: 1px solid var(--border);
    justify-content: flex-end;
    gap: 0.75rem;
}
.ai-modal__body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
}
.ai-modal__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}
.ai-alert {
    border-radius: var(--radius-md);
    display: none;
    font-size: var(--text-sm);
    line-height: 1.5;
    padding: 0.75rem 0.875rem;
}
.ai-alert.error {
    background: color-mix(in srgb, var(--destructive) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--destructive) 25%, transparent);
    color: var(--destructive);
}
.ai-alert.success {
    background: color-mix(in srgb, var(--success) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--success) 25%, transparent);
    color: var(--success);
}
.question-actions-menu {
    min-width: 13rem;
}
.question-actions-menu .dropdown-item {
    font-weight: 600;
}
.bank-picker-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(10rem, 14rem) minmax(8rem, 11rem);
    gap: 0.75rem;
}
.bank-question-list {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    max-height: 48vh;
    overflow: auto;
}
.bank-question-item {
    align-items: flex-start;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    display: grid;
    gap: 0.75rem;
    grid-template-columns: auto minmax(0, 1fr);
    padding: 0.875rem 1rem;
}
.bank-question-item:last-child {
    border-bottom: 0;
}
.bank-question-item:hover {
    background: var(--muted);
}
.bank-question-item input {
    margin-top: 0.2rem;
    width: 1rem;
    height: 1rem;
    accent-color: var(--primary);
}
.bank-question-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.45rem;
}
@media (max-width: 700px) {
    .ai-modal__grid {
        grid-template-columns: 1fr;
    }
    .bank-picker-toolbar {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')
@php
    $isEdit = isset($quiz) && $quiz?->exists;
    $formAction = $isEdit ? route('teacher.quizzes.update', $quiz) : route('teacher.quizzes.store');
    $pageTitle = $isEdit ? 'Chỉnh sửa bài kiểm tra' : 'Tạo Kỳ thi Mới';
    $pageDescription = $isEdit ? 'Cập nhật thông tin và câu hỏi cho bài kiểm tra' : 'Điền thông tin và thêm câu hỏi cho bài kiểm tra';
    $submitLabel = $isEdit ? 'Cập nhật & xuất bản' : 'Xuất bản';
    $draftLabel = $isEdit ? 'Lưu nháp' : 'Lưu Nháp';
    $assignmentTypeValue = old('assignment_type', $isEdit
        ? (!empty($quiz->assigned_students) ? 'specific' : ($quiz->public_to_all_students ? 'everyone' : 'class'))
        : 'class');
    $quizTypeValue = old('quiz_type', $isEdit ? ($quiz->quiz_type ?? 'exam') : 'exam');
    $unlimitedAttemptsValue = (bool) old('unlimited_attempts', $isEdit ? is_null($quiz->max_attempts) : false);
    $antiCheatValue = old('anti_cheat_enabled', $isEdit ? (string) (int) $quiz->anti_cheat_enabled : '1');
    $deadlineValue = old('end_at', $isEdit && $quiz->end_at ? $quiz->end_at->format('Y-m-d\TH:i') : '');
    $initialQuestions = old('questions_json');
    if (!$initialQuestions) {
        $initialQuestions = $isEdit
            ? $quiz->questions->map(function ($question) {
                $options = $question->options;
                if (is_string($options)) {
                    $decoded = json_decode($options, true);
                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded, true);
                    }
                    $options = is_array($decoded) ? $decoded : [];
                }

                return [
                    'type' => $question->type,
                    'content' => $question->content,
                    'options' => array_values($options ?: []),
                    'correct_answer' => (string) $question->correct_answer,
                    'points' => (float) ($question->points ?? 1),
                    'explanation' => $question->explanation ?? '',
                ];
            })->values()->toJson()
            : '[]';
    }
    $selectedStudentIds = collect(old('assigned_students', $isEdit ? ($quiz->assigned_students ?? []) : []))
        ->map(fn($id) => (int) $id)
        ->filter()
        ->values();
    $initialSelectedStudents = $classes
        ->flatMap(fn($class) => $class->students)
        ->whereIn('id', $selectedStudentIds)
        ->unique('id')
        ->map(fn($student) => ['id' => $student->id, 'name' => $student->name, 'email' => $student->email ?? ''])
        ->values();
    $questionFolders = $questionFolders ?? collect();
    $bankQuestions = ($bankQuestions ?? collect())->map(function ($question) {
        $options = $question->options;
        if (is_string($options)) {
            $decoded = json_decode($options, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $options = is_array($decoded) ? $decoded : [];
        }

        return [
            'id' => $question->id,
            'type' => $question->type,
            'content' => $question->content,
            'options' => array_values($options ?: []),
            'correct_answer' => (string) $question->correct_answer,
            'points' => 1,
            'explanation' => $question->explanation ?? '',
            'folder_id' => $question->folder_id,
            'folder_name' => $question->folder?->name ?? 'Chưa phân loại',
            'quiz_title' => $question->quiz?->title,
        ];
    })->values();
@endphp

@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1rem;">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
@endif

<!-- Breadcrumb -->
<nav class="breadcrumb">
    <a href="{{ route('teacher.quizzes') }}">Bài kiểm tra</a>
    <span class="breadcrumb-sep">›</span>
    <span class="active" id="breadcrumb-label">{{ $pageTitle }}</span>
</nav>

<!-- Page header -->
<div class="page-header">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 id="page-title">{{ $pageTitle }}</h1>
            <p style="color:var(--muted-foreground);margin-top:0.25rem;">{{ $pageDescription }}</p>
        </div>
        <div style="display:flex;gap:0.5rem;">
            <a href="{{ route('teacher.quizzes') }}" class="btn btn-outline">Hủy</a>
            <button type="button" class="btn btn-outline" id="save-draft-btn">{{ $draftLabel }}</button>
            <button type="button" class="btn btn-primary" id="publish-btn">{{ $submitLabel }}</button>
        </div>
    </div>
</div>

<form id="quiz-form" action="{{ $formAction }}" method="POST">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

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
                            value="{{ old('title', $isEdit ? $quiz->title : '') }}" required />
                        @error('title')
                        <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="label" for="quiz-desc">Mô tả</label>
                        <textarea id="quiz-desc" name="description" class="input @error('description') input-error @enderror"
                            style="min-height:5rem;" placeholder="Mô tả về bài kiểm tra...">{{ old('description', $isEdit ? $quiz->description : '') }}</textarea>
                        @error('description')
                        <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Quiz type -->
                    <div class="form-group">
                        <label class="label">Loại bài kiểm tra</label>
                        <div class="mode-tab" id="quiz-type-tab">
                            <button type="button" class="mode-tab-btn active" data-value="exam" onclick="setQuizType('exam')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                Kiểm tra
                            </button>
                            <button type="button" class="mode-tab-btn" data-value="practice" onclick="setQuizType('practice')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Luyện tập
                            </button>
                        </div>
                        <input type="hidden" name="quiz_type" id="quiz_type_input" value="{{ $quizTypeValue }}" />
                        <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;" id="quiz-type-desc">
                            <strong>Kiểm tra:</strong> Bật giám sát chống gian lận: chặn DevTools, click phải, sao chép/dán, yêu cầu toàn màn hình và cảnh báo khi học sinh rời màn hình làm bài.
                        </div>
                        <div class="anti-cheat-toggle" id="anti-cheat-section">
                            <div>
                                <div class="anti-cheat-toggle__title">Chống gian lận</div>
                                <div class="anti-cheat-toggle__desc" id="anti-cheat-desc">Bật giám sát toàn màn hình, cảnh báo rời tab và chặn phím tắt DevTools cho bài kiểm tra.</div>
                            </div>
                            <input type="hidden" name="anti_cheat_enabled" id="anti_cheat_hidden" value="{{ $antiCheatValue }}">
                            <label class="switch" for="anti_cheat_enabled" aria-label="Bật tắt chống gian lận">
                                <input type="checkbox" id="anti_cheat_enabled" value="1" @checked((bool) $antiCheatValue) onchange="toggleAntiCheat(this.checked)">
                                <span aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="label" for="quiz-class">Lớp học</label>
                            <select id="quiz-class" name="class_id" class="input select @error('class_id') input-error @enderror" onchange="onClassChange(this.value)">
                                <option value="">Chọn lớp học (tùy chọn)</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('class_id', $isEdit ? $quiz->class_id : null) == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
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
                                    <option value="{{ $course->id }}" {{ old('course_id', $isEdit ? $quiz->course_id : null) == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="label" for="quiz-folder">Thư mục</label>
                        <select id="quiz-folder" name="folder_id" class="input select @error('folder_id') input-error @enderror">
                            <option value="">Không chọn thư mục</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}" {{ old('folder_id', $isEdit ? $quiz->folder_id : null) == $folder->id ? 'selected' : '' }}>
                                    {{ $folder->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('folder_id')
                        <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="label" for="quiz-duration">Thời gian (phút)</label>
                            <input type="number" id="quiz-duration" name="time_limit"
                                class="input @error('time_limit') input-error @enderror"
                                value="{{ old('time_limit', $isEdit ? ($quiz->time_limit ?? $quiz->duration_minutes ?? 60) : 60) }}" min="1" />
                            @error('time_limit')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="label" for="quiz-passing">Điểm đạt (%)</label>
                            <input type="number" id="quiz-passing" name="passing_score"
                                class="input @error('passing_score') input-error @enderror"
                                value="{{ old('passing_score', $isEdit ? ($quiz->passing_score ?? 50) : 50) }}" min="0" max="100" />
                            @error('passing_score')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="label" for="quiz-deadline">Hạn làm bài</label>
                        <input type="datetime-local" id="quiz-deadline" name="end_at"
                            class="input @error('end_at') input-error @enderror"
                            value="{{ $deadlineValue }}" />
                        <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">
                            Để trống nếu không giới hạn hạn làm bài.
                        </div>
                        @error('end_at')
                        <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Assignment type -->
                    <div class="form-group">
                        <label class="label">Giao cho</label>
                        <div class="assign-tab" id="assign-type-tab">
                            <button type="button" class="assign-tab-btn active" data-value="class" onclick="setAssignType('class')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Theo lớp/khóa học
                            </button>
                            <button type="button" class="assign-tab-btn" data-value="everyone" onclick="setAssignType('everyone')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Công khai
                            </button>
                            <button type="button" class="assign-tab-btn" data-value="specific" onclick="setAssignType('specific')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Học sinh cụ thể
                            </button>
                        </div>
                        <input type="hidden" name="assignment_type" id="assignment_type_input" value="{{ $assignmentTypeValue }}" />
                        <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.35rem;">
                            Chọn lớp, khóa học, danh sách học sinh hoặc bật công khai rõ ràng trước khi xuất bản.
                        </div>

                        <!-- Student picker (shown when specific is selected) -->
                        <div id="student-picker-section" style="display:none;">
                            <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-bottom:0.5rem;" id="selected-students-chips">
                            </div>
                            <div class="student-search-wrap">
                                <input type="text" id="student-search" class="input" placeholder="Tìm kiếm học sinh..."
                                    autocomplete="off" oninput="searchStudents(this.value)" />
                                <div class="student-search-results" id="student-search-results">
                                </div>
                            </div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">
                                Nhấn Enter hoặc click để thêm học sinh
                            </div>
                        </div>

                        <!-- Hidden inputs for selected student IDs -->
                        <div id="student-ids-container"></div>
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
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;justify-content:flex-end;">
                            <button type="button" class="btn btn-outline btn-sm gap-1" id="bank-picker-btn" onclick="openBankPickerModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>
                                Ngân hàng
                            </button>
                            <button type="button" class="btn btn-primary btn-sm gap-1" id="ai-generate-btn" onclick="openAiQuestionModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z"/><path d="M19 14l.8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14z"/><path d="M5 14l.8 2.2L8 17l-2.2.8L5 20l-.8-2.2L2 17l2.2-.8L5 14z"/></svg>
                                Tạo bằng AI
                            </button>
                            <button type="button" class="btn btn-outline btn-sm gap-1" id="import-file-btn" onclick="openImportFileModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Import File
                            </button>
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline btn-sm gap-1" id="create-question-btn" onclick="toggleQuestionCreateMenu(event)" aria-haspopup="true" aria-expanded="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Tạo câu hỏi
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div class="dropdown-menu question-actions-menu" id="question-create-menu" role="menu">
                                    <button type="button" class="dropdown-item" id="add-mc-btn" role="menuitem">
                                        <span>◉</span>
                                        Trắc nghiệm
                                    </button>
                                    <button type="button" class="dropdown-item" id="add-tf-btn" role="menuitem">
                                        <span>✓</span>
                                        Đúng/Sai
                                    </button>
                                    <button type="button" class="dropdown-item" id="add-sa-btn" role="menuitem">
                                        <span>✎</span>
                                        Tự luận
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    <div id="bulk-question-actions" style="display:none;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:1rem;padding:0.75rem 1rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--muted);">
                        <label style="display:flex;align-items:center;gap:0.5rem;font-size:var(--text-sm);font-weight:600;cursor:pointer;">
                            <input type="checkbox" id="select-all-questions" style="accent-color:var(--primary);" onchange="toggleSelectAllQuestions(this.checked)" />
                            Chọn tất cả
                        </label>
                        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;justify-content:flex-end;">
                            <span id="selected-questions-count" style="font-size:var(--text-sm);color:var(--muted-foreground);">0 câu đã chọn</span>
                            <button type="button" class="btn btn-destructive btn-sm" id="bulk-delete-questions-btn" onclick="removeSelectedQuestions()" disabled>
                                Xóa câu đã chọn
                            </button>
                        </div>
                    </div>
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
                    <input type="hidden" id="questions-json" name="questions_json" value="{{ $initialQuestions }}" />

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
                            <input type="checkbox" id="shuffle-questions" name="is_shuffle" value="1" @checked((bool) old('is_shuffle', $isEdit ? $quiz->is_shuffle : true)) />
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div style="font-weight:500;font-size:var(--text-sm);">Hiện đáp án sau khi nộp</div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Học sinh xem đáp án đúng</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="show-answers" name="show_result" value="1" @checked((bool) old('show_result', $isEdit ? $quiz->show_result : true)) />
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div style="font-weight:500;font-size:var(--text-sm);">Xuất bản ngay</div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Học sinh có thể làm được luôn</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="publish-immediate" name="is_published" value="1" @checked((bool) old('is_published', $isEdit ? $quiz->status === 'published' : false)) />
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="label" for="max-attempts">Số lần làm tối đa</label>
                        <label style="display:flex;align-items:center;gap:.5rem;margin:.4rem 0 .6rem;font-size:var(--text-sm);">
                            <input type="checkbox" id="unlimited-attempts" name="unlimited_attempts" value="1" @checked($unlimitedAttemptsValue)>
                            Không giới hạn số lần kiểm tra
                        </label>
                        <input type="number" id="max-attempts" name="max_attempts"
                            class="input @error('max_attempts') input-error @enderror"
                            value="{{ old('max_attempts', $isEdit ? ($quiz->max_attempts ?? 1) : 1) }}"
                            min="1" max="20" />
                        <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">
                            Mỗi học sinh có thể nộp lại đến số lượt đã cấu hình.
                        </div>
                        @error('max_attempts')
                        <span class="error-message">{{ $message }}</span>
                        @enderror
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

<div class="ai-modal-overlay" id="bank-picker-modal">
    <div class="ai-modal" style="max-width:820px;">
        <div class="ai-modal__header">
            <div>
                <h3 style="font-size:var(--text-lg);font-weight:800;margin:0;">Chọn câu hỏi từ ngân hàng</h3>
                <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">Các câu được chọn sẽ được sao chép vào bài kiểm tra hiện tại để chỉnh sửa trước khi lưu.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeBankPickerModal()">Đóng</button>
        </div>
        <div class="ai-modal__body">
            <div class="bank-picker-toolbar">
                <input type="search" class="input" id="bank-question-search" placeholder="Tìm nội dung câu hỏi, đáp án..." oninput="renderBankQuestions()" />
                <select class="input select" id="bank-folder-filter" onchange="renderBankQuestions()">
                    <option value="">Tất cả thư mục</option>
                    <option value="none">Chưa phân loại</option>
                    @foreach($questionFolders as $folder)
                        <option value="{{ $folder->id }}">{{ $folder->name }} ({{ $folder->questions_count }})</option>
                    @endforeach
                </select>
                <select class="input select" id="bank-type-filter" onchange="renderBankQuestions()">
                    <option value="">Tất cả loại</option>
                    <option value="multiple_choice">Trắc nghiệm</option>
                    <option value="true_false">Đúng/Sai</option>
                    <option value="short_answer">Tự luận</option>
                </select>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:0.5rem;font-size:var(--text-sm);font-weight:600;cursor:pointer;">
                    <input type="checkbox" id="bank-select-visible" style="accent-color:var(--primary);" onchange="toggleVisibleBankQuestions(this.checked)" />
                    Chọn các câu đang hiển thị
                </label>
                <span id="bank-picker-count" style="font-size:var(--text-sm);color:var(--muted-foreground);">0 câu được chọn</span>
            </div>
            <div class="bank-question-list" id="bank-question-list"></div>
        </div>
        <div class="ai-modal__footer">
            <button type="button" class="btn btn-outline" onclick="closeBankPickerModal()">Hủy</button>
            <button type="button" class="btn btn-primary" id="bank-add-selected-btn" onclick="addSelectedBankQuestions()">Thêm vào bài kiểm tra</button>
        </div>
    </div>
</div>

<div class="ai-modal-overlay" id="ai-question-modal">
    <div class="ai-modal">
        <div class="ai-modal__header">
            <div>
                <h3 style="font-size:var(--text-lg);font-weight:800;margin:0;">Tạo câu hỏi bằng AI</h3>
                <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">AI sẽ thêm câu hỏi vào danh sách hiện tại, bạn vẫn có thể chỉnh sửa trước khi lưu.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeAiQuestionModal()">Đóng</button>
        </div>
        <div class="ai-modal__body">
            <div class="ai-alert error" id="ai-question-error"></div>
            <div class="ai-alert success" id="ai-question-success"></div>
            <div class="form-group">
                <label class="label" for="ai-topic">Chủ đề / mục tiêu</label>
                <input type="text" class="input" id="ai-topic" placeholder="VD: Hàm số bậc hai, chiến dịch Điện Biên Phủ, quang hợp..." />
            </div>
            <div class="form-group">
                <label class="label" for="ai-source-file">Tài liệu nguồn (tùy chọn)</label>
                <input type="file" class="input" id="ai-source-file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,image/png,image/jpeg,image/webp" />
                <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">
                    AI có thể đọc DOCX, PDF có lớp chữ và ảnh PNG/JPG/WEBP để tạo câu hỏi. Nếu chọn file, chủ đề có thể để trống.
                </div>
            </div>
            <div class="ai-modal__grid">
                <div class="form-group">
                    <label class="label" for="ai-count">Số câu</label>
                    <input type="number" class="input" id="ai-count" min="1" max="100" value="5" />
                </div>
                <div class="form-group">
                    <label class="label" for="ai-type">Loại câu hỏi</label>
                    <select class="input select" id="ai-type">
                        <option value="mixed">Kết hợp</option>
                        <option value="multiple_choice">Trắc nghiệm</option>
                        <option value="true_false">Đúng/Sai</option>
                        <option value="short_answer">Tự luận</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="ai-difficulty">Mức độ</label>
                    <select class="input select" id="ai-difficulty">
                        <option value="medium">Trung bình</option>
                        <option value="easy">Dễ</option>
                        <option value="hard">Khó</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="label" for="ai-grade">Khối/lớp</label>
                <input type="text" class="input" id="ai-grade" placeholder="VD: Lớp 10, THCS, sinh viên năm 1..." />
            </div>
            <div class="form-group">
                <label class="label" for="ai-extra-context">Yêu cầu bổ sung</label>
                <textarea class="input" id="ai-extra-context" style="min-height:5rem;resize:vertical;" placeholder="VD: Bám sát SGK, có giải thích ngắn, tránh câu hỏi mẹo..."></textarea>
            </div>
        </div>
        <div class="ai-modal__footer">
            <button type="button" class="btn btn-outline" onclick="closeAiQuestionModal()">Hủy</button>
            <button type="button" class="btn btn-primary" id="ai-submit-btn" onclick="generateAiQuestions()">Tạo câu hỏi</button>
        </div>
    </div>
</div>

<div class="ai-modal-overlay" id="ai-loading-modal">
    <div class="ai-modal" style="max-width:420px;">
        <div class="ai-modal__header">
            <div>
                <h3 style="font-size:var(--text-lg);font-weight:800;margin:0;">Đang tạo câu hỏi</h3>
                <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;" id="ai-loading-message">AI đang xử lý yêu cầu của bạn...</p>
            </div>
        </div>
        <div class="ai-modal__body">
            <div style="height:0.75rem;background:var(--muted);border-radius:999px;overflow:hidden;">
                <div id="ai-loading-bar" style="height:100%;width:0%;background:var(--primary);border-radius:999px;transition:width 0.35s ease;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:var(--text-sm);font-weight:700;">
                <span id="ai-loading-status">Chuẩn bị gửi yêu cầu</span>
                <span id="ai-loading-percent">0%</span>
            </div>
        </div>
    </div>
</div>

<div class="ai-modal-overlay" id="import-file-modal">
    <div class="ai-modal">
        <div class="ai-modal__header">
            <div>
                <h3 style="font-size:var(--text-lg);font-weight:800;margin:0;">Import File tạo đề</h3>
                <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">Tải đề từ Word/PDF, hệ thống nhận đáp án tô đỏ hoặc bảng đáp án rồi đưa vào danh sách để chỉnh sửa.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeImportFileModal()">Đóng</button>
        </div>
        <div class="ai-modal__body">
            <div class="ai-alert error" id="import-file-error"></div>
            <div class="ai-alert success" id="import-file-success"></div>
            <div class="form-group">
                <label class="label label-required" for="import-source-file">File đề</label>
                <input type="file" class="input" id="import-source-file" accept=".pdf,.doc,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword" />
                <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">DOCX đọc được đáp án tô đỏ và bảng đáp án. PDF cần có lớp chữ; PDF scan cần OCR trước.</div>
            </div>
        </div>
        <div class="ai-modal__footer">
            <button type="button" class="btn btn-outline" onclick="closeImportFileModal()">Hủy</button>
            <button type="button" class="btn btn-primary" id="import-submit-btn" onclick="importQuestionsFromFile()">Import File</button>
        </div>
    </div>
</div>
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
    let selectedQuestionIndexes = new Set();
    let selectedStudents = [];  // {id, name, email}
    let allStudents = [];       // flat list of all students from classes
    let currentAssignType = 'class';
    let currentQuizType = 'exam';

    // ── Helpers ────────────────────────────────
    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }
    const AI_QUESTIONS_URL = @json(route('teacher.quizzes.generate-ai-questions'));
    const IMPORT_QUESTIONS_URL = @json(route('teacher.quizzes.import-questions-file'));
    const INITIAL_ASSIGN_TYPE = @json($assignmentTypeValue);
    const INITIAL_QUIZ_TYPE = @json($quizTypeValue);
    const INITIAL_SELECTED_STUDENTS = @json($initialSelectedStudents);
    const BANK_QUESTIONS = @json($bankQuestions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    const OPEN_BANK_PICKER_ON_LOAD = @json(request()->boolean('from_bank'));
    let selectedBankQuestionIds = new Set();
    let visibleBankQuestionIds = [];

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

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

    function updateBulkQuestionActions() {
        selectedQuestionIndexes = new Set([...selectedQuestionIndexes].filter(idx => idx >= 0 && idx < questions.length));
        const toolbar = $('#bulk-question-actions');
        const selectedCount = selectedQuestionIndexes.size;
        if (toolbar) toolbar.style.display = questions.length > 0 ? 'flex' : 'none';

        const countEl = $('#selected-questions-count');
        if (countEl) countEl.textContent = selectedCount + ' câu đã chọn';

        const deleteBtn = $('#bulk-delete-questions-btn');
        if (deleteBtn) deleteBtn.disabled = selectedCount === 0;

        const selectAll = $('#select-all-questions');
        if (selectAll) {
            selectAll.checked = questions.length > 0 && selectedCount === questions.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < questions.length;
        }
    }

    function toggleQuestionSelection(idx, checked) {
        if (checked) {
            selectedQuestionIndexes.add(idx);
        } else {
            selectedQuestionIndexes.delete(idx);
        }
        updateBulkQuestionActions();
    }

    function toggleSelectAllQuestions(checked) {
        selectedQuestionIndexes = checked
            ? new Set(questions.map((_, idx) => idx))
            : new Set();
        renderQuestions();
    }

    // ── Quiz Type ──────────────────────────────
    function setQuizType(type) {
        currentQuizType = type;
        $$('.mode-tab-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.value === type));
        $('#quiz_type_input').value = type;
        const desc = $('#quiz-type-desc');
        if (desc) {
            if (type === 'exam') {
                desc.innerHTML = '<strong>Kiểm tra:</strong> Bật giám sát chống gian lận: chặn DevTools, click phải, sao chép/dán, yêu cầu toàn màn hình và cảnh báo khi học sinh rời màn hình làm bài.';
            } else {
                desc.innerHTML = '<strong>Luyện tập:</strong> Không bật giám sát chống gian lận; học sinh có thể luyện bài linh hoạt và xem kết quả sau khi nộp.';
            }
        }
        syncAntiCheatControl(type);
    }

    function syncAntiCheatControl(type) {
        const section = $('#anti-cheat-section');
        const checkbox = $('#anti_cheat_enabled');
        const hidden = $('#anti_cheat_hidden');
        const antiCheatDesc = $('#anti-cheat-desc');
        if (!section || !checkbox || !hidden) return;

        const isExam = type === 'exam';
        section.classList.toggle('is-disabled', !isExam);
        checkbox.disabled = !isExam;

        if (isExam) {
            hidden.value = checkbox.checked ? '1' : '0';
            if (antiCheatDesc) {
                antiCheatDesc.textContent = checkbox.checked
                    ? 'Đang bật: học sinh sẽ bị giám sát toàn màn hình, cảnh báo rời tab và chặn phím tắt DevTools.'
                    : 'Đang tắt: bài kiểm tra không áp dụng giám sát chống gian lận.';
            }
        } else {
            hidden.value = '0';
            if (antiCheatDesc) {
                antiCheatDesc.textContent = 'Luyện tập không sử dụng chống gian lận.';
            }
        }
    }

    function toggleAntiCheat(enabled) {
        const hidden = $('#anti_cheat_hidden');
        if (hidden) hidden.value = enabled ? '1' : '0';
        syncAntiCheatControl(currentQuizType);
    }

    function syncUnlimitedAttemptsControl() {
        const unlimited = $('#unlimited-attempts');
        const maxAttempts = $('#max-attempts');
        if (!unlimited || !maxAttempts) return;

        maxAttempts.disabled = unlimited.checked;
        if (unlimited.checked) {
            maxAttempts.dataset.prevValue = maxAttempts.value || '1';
            maxAttempts.value = '';
            maxAttempts.placeholder = 'Không giới hạn';
        } else {
            if (!maxAttempts.value) {
                maxAttempts.value = maxAttempts.dataset.prevValue || '1';
            }
            maxAttempts.placeholder = '';
        }
    }

    // ── AI Question Generation ─────────────────
    function openAiQuestionModal() {
        const modal = $('#ai-question-modal');
        if (!modal) return;
        setAiAlert('', '');
        const title = $('#quiz-title')?.value?.trim();
        if (title && !$('#ai-topic').value.trim()) {
            $('#ai-topic').value = title;
        }
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => $('#ai-topic')?.focus(), 100);
    }

    function closeAiQuestionModal() {
        $('#ai-question-modal')?.classList.remove('open');
        document.body.style.overflow = '';
    }

    function syncAiTopicFromFile() {
        const topicInput = $('#ai-topic');
        const sourceFile = $('#ai-source-file')?.files?.[0] || null;
        if (topicInput && sourceFile && !topicInput.value.trim()) {
            topicInput.value = sourceFile.name.replace(/\.[^.]+$/, '');
        }
    }

    function openImportFileModal() {
        const modal = $('#import-file-modal');
        if (!modal) return;
        setImportFileAlert('', '');
        const input = $('#import-source-file');
        if (input) input.value = '';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => $('#import-source-file')?.focus(), 100);
    }

    function closeImportFileModal() {
        $('#import-file-modal')?.classList.remove('open');
        document.body.style.overflow = '';
    }

    function toggleQuestionCreateMenu(event) {
        event?.stopPropagation();
        const menu = $('#question-create-menu');
        const button = $('#create-question-btn');
        if (!menu || !button) return;
        const isOpen = menu.classList.toggle('open');
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function closeQuestionCreateMenu() {
        $('#question-create-menu')?.classList.remove('open');
        $('#create-question-btn')?.setAttribute('aria-expanded', 'false');
    }

    function openBankPickerModal() {
        const modal = $('#bank-picker-modal');
        if (!modal) return;
        renderBankQuestions();
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => $('#bank-question-search')?.focus(), 100);
    }

    function closeBankPickerModal() {
        $('#bank-picker-modal')?.classList.remove('open');
        document.body.style.overflow = '';
    }

    function bankQuestionMatchesFilters(question) {
        const search = normalizeQuestionKey($('#bank-question-search')?.value || '');
        const folder = $('#bank-folder-filter')?.value || '';
        const type = $('#bank-type-filter')?.value || '';

        if (type && question.type !== type) return false;
        if (folder === 'none' && question.folder_id) return false;
        if (folder && folder !== 'none' && String(question.folder_id || '') !== folder) return false;

        if (!search) return true;
        const haystack = normalizeQuestionKey([
            question.content,
            question.correct_answer,
            question.explanation,
            question.folder_name,
            question.quiz_title,
            ...(question.options || []),
        ].join(' '));

        return haystack.includes(search);
    }

    function renderBankQuestions() {
        const list = $('#bank-question-list');
        if (!list) return;

        const filtered = BANK_QUESTIONS.filter(bankQuestionMatchesFilters);
        visibleBankQuestionIds = filtered.map(question => Number(question.id));

        if (!BANK_QUESTIONS.length) {
            list.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--muted-foreground);">Ngân hàng câu hỏi chưa có câu hỏi nào.</div>';
        } else if (!filtered.length) {
            list.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--muted-foreground);">Không tìm thấy câu hỏi phù hợp.</div>';
        } else {
            list.innerHTML = filtered.map(question => {
                const checked = selectedBankQuestionIds.has(Number(question.id)) ? 'checked' : '';
                const source = question.quiz_title ? `Từ bài: ${escapeHtml(question.quiz_title)}` : 'Ngân hàng';
                return `
                    <label class="bank-question-item">
                        <input type="checkbox" value="${question.id}" ${checked} onchange="toggleBankQuestion(${question.id}, this.checked)" />
                        <div>
                            <div style="font-weight:600;line-height:1.45;">${escapeHtml(question.content)}</div>
                            <div class="bank-question-meta">
                                <span class="badge badge-default">${escapeHtml(getTypeLabel(question.type))}</span>
                                <span class="badge badge-outline">${escapeHtml(question.folder_name || 'Chưa phân loại')}</span>
                                <span class="badge badge-outline">${source}</span>
                            </div>
                        </div>
                    </label>`;
            }).join('');
        }

        syncBankPickerState();
    }

    function syncBankPickerState() {
        const count = selectedBankQuestionIds.size;
        const countEl = $('#bank-picker-count');
        const addBtn = $('#bank-add-selected-btn');
        const selectVisible = $('#bank-select-visible');

        if (countEl) countEl.textContent = count + ' câu được chọn';
        if (addBtn) addBtn.disabled = count === 0;
        if (selectVisible) {
            const visibleSelected = visibleBankQuestionIds.filter(id => selectedBankQuestionIds.has(id)).length;
            selectVisible.checked = visibleBankQuestionIds.length > 0 && visibleSelected === visibleBankQuestionIds.length;
            selectVisible.indeterminate = visibleSelected > 0 && visibleSelected < visibleBankQuestionIds.length;
        }
    }

    function toggleBankQuestion(id, checked) {
        const numericId = Number(id);
        if (checked) {
            selectedBankQuestionIds.add(numericId);
        } else {
            selectedBankQuestionIds.delete(numericId);
        }
        syncBankPickerState();
    }

    function toggleVisibleBankQuestions(checked) {
        visibleBankQuestionIds.forEach(id => {
            if (checked) {
                selectedBankQuestionIds.add(id);
            } else {
                selectedBankQuestionIds.delete(id);
            }
        });
        renderBankQuestions();
    }

    function cloneBankQuestion(question) {
        const normalized = normalizeAiQuestion(question);
        return normalized ? { ...normalized, options: [...(normalized.options || [])] } : null;
    }

    async function addSelectedBankQuestions() {
        const selected = BANK_QUESTIONS
            .filter(question => selectedBankQuestionIds.has(Number(question.id)))
            .map(cloneBankQuestion)
            .filter(Boolean);

        if (!selected.length) {
            window.showAppAlert('Vui lòng chọn ít nhất 1 câu hỏi từ ngân hàng.');
            return;
        }

        const { fresh, duplicates } = findImportDuplicates(selected);
        let added = fresh.length;
        let replaced = 0;

        if (duplicates.length) {
            const action = await askDuplicateImportAction(duplicates, fresh.length);
            if (action === 'cancel') return;

            if (action === 'replace') {
                questions.push(...fresh);
                duplicates.forEach(({ question, existingIndex, repeatedInFile }) => {
                    if (existingIndex !== undefined && !repeatedInFile) {
                        questions[existingIndex] = question;
                        replaced++;
                    } else {
                        questions.push(question);
                        added++;
                    }
                });
            } else if (action === 'append') {
                questions.push(...fresh, ...duplicates.map(item => item.question));
                added += duplicates.length;
            } else {
                questions.push(...fresh);
            }
        } else {
            questions.push(...fresh);
        }

        selectedBankQuestionIds.clear();
        renderQuestions();
        updateSummary();
        closeBankPickerModal();

        const message = replaced > 0
            ? `Đã thêm ${added} câu và ghi đè ${replaced} câu từ ngân hàng.`
            : `Đã thêm ${added} câu hỏi từ ngân hàng.`;
        window.showAppAlert(message);
    }

    function setAiAlert(type, message) {
        const error = $('#ai-question-error');
        const success = $('#ai-question-success');
        if (error) {
            error.style.display = type === 'error' && message ? 'block' : 'none';
            error.textContent = type === 'error' ? message : '';
        }
        if (success) {
            success.style.display = type === 'success' && message ? 'block' : 'none';
            success.textContent = type === 'success' ? message : '';
        }
    }

    function setImportFileAlert(type, message) {
        const error = $('#import-file-error');
        const success = $('#import-file-success');
        if (error) {
            error.style.display = type === 'error' && message ? 'block' : 'none';
            error.textContent = type === 'error' ? message : '';
        }
        if (success) {
            success.style.display = type === 'success' && message ? 'block' : 'none';
            success.textContent = type === 'success' ? message : '';
        }
    }

    let aiLoadingTimer = null;
    let aiLoadingPercent = 0;

    function setAiLoadingProgress(percent, status = '') {
        aiLoadingPercent = Math.max(0, Math.min(100, Math.round(percent)));
        const bar = $('#ai-loading-bar');
        const percentEl = $('#ai-loading-percent');
        const statusEl = $('#ai-loading-status');
        if (bar) bar.style.width = aiLoadingPercent + '%';
        if (percentEl) percentEl.textContent = aiLoadingPercent + '%';
        if (statusEl && status) statusEl.textContent = status;
    }

    function openAiLoadingModal(count) {
        const modal = $('#ai-loading-modal');
        if (!modal) return;
        clearInterval(aiLoadingTimer);
        setAiLoadingProgress(5, `Đang gửi yêu cầu tạo ${count} câu...`);
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        const estimatedSeconds = Math.max(12, Math.min(90, count * 2));
        const step = Math.max(1, 90 / estimatedSeconds);
        aiLoadingTimer = setInterval(() => {
            if (aiLoadingPercent >= 95) {
                clearInterval(aiLoadingTimer);
                return;
            }
            const next = Math.min(95, aiLoadingPercent + step);
            const status = next < 35
                ? 'AI đang phân tích yêu cầu...'
                : (next < 75 ? 'AI đang tạo nội dung câu hỏi...' : 'Đang chuẩn hóa đáp án và lời giải...');
            setAiLoadingProgress(next, status);
        }, 1000);
    }

    function closeAiLoadingModal(success = false) {
        clearInterval(aiLoadingTimer);
        aiLoadingTimer = null;
        if (success) {
            setAiLoadingProgress(100, 'Hoàn tất');
        }
        setTimeout(() => {
            $('#ai-loading-modal')?.classList.remove('open');
            document.body.style.overflow = '';
        }, success ? 450 : 0);
    }

    function extractApiErrorMessage(data, fallback) {
        if (data?.errors && typeof data.errors === 'object') {
            const first = Object.values(data.errors).flat()[0];
            if (first) return first;
        }
        return data?.message || fallback;
    }

    function normalizeAiQuestion(question) {
        const type = question.type;
        const normalized = {
            type,
            content: String(question.content || '').trim(),
            options: Array.isArray(question.options) ? question.options.map(option => String(option || '').trim()) : [],
            correct_answer: String(question.correct_answer ?? '').trim(),
            points: 1,
            explanation: String(question.explanation || '').trim(),
        };

        if (!normalized.content || !['multiple_choice', 'true_false', 'short_answer'].includes(type)) {
            return null;
        }

        if (type === 'multiple_choice') {
            normalized.options = normalized.options.slice(0, 4);
            while (normalized.options.length < 4) normalized.options.push('');
            if (!['0', '1', '2', '3'].includes(normalized.correct_answer)) normalized.correct_answer = '0';
        } else if (type === 'true_false') {
            normalized.options = [];
            normalized.correct_answer = normalized.correct_answer === 'false' ? 'false' : 'true';
        } else {
            normalized.options = [];
            if (!normalized.correct_answer) normalized.correct_answer = 'Giáo viên chấm theo ý chính.';
        }

        return normalized;
    }

    function normalizeQuestionKey(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .replace(/[“”"'.:;!?()[\]{}\-–—]+/g, '')
            .trim();
    }

    function questionSignature(question, includeAnswer = false) {
        const answer = includeAnswer ? '|' + normalizeQuestionKey(question.correct_answer || '') : '';
        return normalizeQuestionKey(question.content) + answer;
    }

    function findImportDuplicates(imported) {
        const existingByContent = new Map();
        const existingByFull = new Map();
        questions.forEach((question, index) => {
            existingByContent.set(questionSignature(question), index);
            existingByFull.set(questionSignature(question, true), index);
        });

        const incomingContent = new Set();
        const incomingFull = new Set();
        const duplicates = [];
        const fresh = [];

        imported.forEach((question, importIndex) => {
            const contentKey = questionSignature(question);
            const fullKey = questionSignature(question, true);
            const existingIndex = existingByFull.has(fullKey)
                ? existingByFull.get(fullKey)
                : existingByContent.get(contentKey);
            const duplicateType = existingByFull.has(fullKey) ? 'full' : (existingByContent.has(contentKey) ? 'content' : null);
            const repeatedInFile = incomingFull.has(fullKey) || incomingContent.has(contentKey);

            incomingContent.add(contentKey);
            incomingFull.add(fullKey);

            if (existingIndex !== undefined || repeatedInFile) {
                duplicates.push({ question, importIndex, existingIndex, duplicateType, repeatedInFile });
            } else {
                fresh.push(question);
            }
        });

        return { fresh, duplicates };
    }

    function askDuplicateImportAction(duplicates, freshCount) {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;padding:1rem;';
        overlay.innerHTML = `
            <div style="background:var(--card);border-radius:var(--radius-xl);width:100%;max-width:560px;box-shadow:var(--shadow-xl);overflow:hidden;">
                <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);">
                    <h3 style="font-size:var(--text-lg);font-weight:800;margin:0;">Phát hiện câu hỏi trùng</h3>
                    <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.35rem;">
                        Có ${duplicates.length} câu trùng trong danh sách hiện tại hoặc trong file import. ${freshCount} câu mới sẽ được thêm.
                    </p>
                </div>
                <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:0.75rem;">
                    <button type="button" class="btn btn-outline" data-action="skip" style="justify-content:flex-start;">
                        Bỏ qua câu trùng, chỉ thêm câu mới
                    </button>
                    <button type="button" class="btn btn-outline" data-action="replace" style="justify-content:flex-start;">
                        Ghi đè câu đã tồn tại bằng bản import
                    </button>
                    <button type="button" class="btn btn-outline" data-action="append" style="justify-content:flex-start;">
                        Vẫn thêm tất cả câu trùng như câu mới
                    </button>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" data-action="cancel">Hủy</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        return new Promise(resolve => {
            overlay.addEventListener('click', event => {
                if (event.target === overlay) {
                    resolve('cancel');
                    overlay.remove();
                    document.body.style.overflow = '';
                    return;
                }
                const button = event.target.closest('[data-action]');
                if (!button) return;
                resolve(button.dataset.action);
                overlay.remove();
                document.body.style.overflow = '';
            });
        });
    }

    async function generateAiQuestions() {
        const topic = $('#ai-topic')?.value.trim();
        const sourceFile = $('#ai-source-file')?.files?.[0] || null;
        const count = parseInt($('#ai-count')?.value, 10) || 5;
        const type = $('#ai-type')?.value || 'mixed';
        const difficulty = $('#ai-difficulty')?.value || 'medium';
        const grade = $('#ai-grade')?.value.trim() || '';
        const extraContext = $('#ai-extra-context')?.value.trim() || '';
        const submitBtn = $('#ai-submit-btn');

        if (!topic && !sourceFile) {
            setAiAlert('error', 'Vui lòng nhập chủ đề hoặc chọn tài liệu nguồn để tạo câu hỏi.');
            $('#ai-topic')?.focus();
            return;
        }
        if (count < 1 || count > 100) {
            setAiAlert('error', 'Số câu phải từ 1 đến 100. Vui lòng không nhập quá 100 câu.');
            $('#ai-count')?.focus();
            return;
        }

        setAiAlert('', '');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang tạo...';
        }
        openAiLoadingModal(count);

        try {
            const formData = new FormData();
            if (topic) {
                formData.append('topic', topic);
            }
            formData.append('count', count);
            formData.append('type', type);
            formData.append('difficulty', difficulty);
            formData.append('grade', grade);
            formData.append('extra_context', extraContext);
            if (sourceFile) {
                formData.append('source_file', sourceFile);
            }

            const response = await fetch(AI_QUESTIONS_URL, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(extractApiErrorMessage(data, 'Không thể tạo câu hỏi bằng AI.'));
            }

            const generated = (data.questions || []).map(normalizeAiQuestion).filter(Boolean);
            if (!generated.length) {
                throw new Error('AI chưa trả về câu hỏi hợp lệ.');
            }

            questions.push(...generated);
            renderQuestions();
            updateSummary();
            closeAiLoadingModal(true);
            setAiAlert('success', 'Đã thêm ' + generated.length + ' câu hỏi vào bài kiểm tra.');
            setTimeout(closeAiQuestionModal, 800);
        } catch (error) {
            closeAiLoadingModal(false);
            setAiAlert('error', error.message || 'Có lỗi khi gọi AI.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Tạo câu hỏi';
            }
        }
    }

    async function importQuestionsFromFile() {
        const sourceFile = $('#import-source-file')?.files?.[0] || null;
        const submitBtn = $('#import-submit-btn');

        if (!sourceFile) {
            setImportFileAlert('error', 'Vui lòng chọn file DOCX hoặc PDF để import.');
            $('#import-source-file')?.focus();
            return;
        }

        setImportFileAlert('', '');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang import...';
        }

        try {
            const formData = new FormData();
            formData.append('source_file', sourceFile);

            const response = await fetch(IMPORT_QUESTIONS_URL, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Không thể import câu hỏi từ file.');
            }

            const imported = (data.questions || []).map(normalizeAiQuestion).filter(Boolean);
            if (!imported.length) {
                throw new Error('File chưa tạo được câu hỏi hợp lệ.');
            }

            const { fresh, duplicates } = findImportDuplicates(imported);
            let addedCount = fresh.length;
            let replacedCount = 0;

            if (duplicates.length) {
                const action = await askDuplicateImportAction(duplicates, fresh.length);
                if (action === 'cancel') {
                    return;
                }

                if (action === 'replace') {
                    questions.push(...fresh);
                    duplicates.forEach(({ question, existingIndex, repeatedInFile }) => {
                        if (existingIndex !== undefined && !repeatedInFile) {
                            questions[existingIndex] = question;
                            replacedCount++;
                        } else {
                            questions.push(question);
                            addedCount++;
                        }
                    });
                } else if (action === 'append') {
                    questions.push(...fresh, ...duplicates.map(item => item.question));
                    addedCount += duplicates.length;
                } else {
                    questions.push(...fresh);
                }
            } else {
                questions.push(...fresh);
            }

            renderQuestions();
            updateSummary();
            const message = replacedCount > 0
                ? `Đã thêm ${addedCount} câu và ghi đè ${replacedCount} câu trùng.`
                : `Đã import ${addedCount} câu hỏi vào bài kiểm tra.`;
            setImportFileAlert('success', message);
            setTimeout(closeImportFileModal, 900);
        } catch (error) {
            setImportFileAlert('error', error.message || 'Có lỗi khi import file.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Import File';
            }
        }
    }

    // ── Assignment Type ───────────────────────
    function setAssignType(type) {
        currentAssignType = type;
        $$('.assign-tab-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.value === type));
        $('#assignment_type_input').value = type;

        const picker = $('#student-picker-section');
        if (picker) {
            picker.style.display = type === 'specific' ? 'block' : 'none';
        }
        if (type === 'everyone') {
            const classSelect = $('#quiz-class');
            if (classSelect) classSelect.value = '';
        }
        // Update class selector visibility
        const classRow = $('#quiz-class').closest('.form-group');
        if (classRow) {
            if (type === 'class' || type === 'specific') {
                classRow.style.display = '';
            } else {
                classRow.style.display = 'none';
            }
        }
    }

    function onClassChange(classId) {
        // Reload students for the new class
        loadStudentsForClass(classId);
        // Clear selected students when class changes
        selectedStudents = [];
        renderStudentChips();
    }

    // ── Student Picker ─────────────────────────
    function loadStudentsForClass(classId) {
        allStudents = [];
        @foreach($classes as $class)
            if (!classId || classId === '{{ $class->id }}') {
                @foreach($class->students as $student)
                    allStudents.push({ id: {{ $student->id }}, name: '{{ $student->name }}', email: '{{ $student->email ?? '' }}', classId: '{{ $class->id }}' });
                @endforeach
            }
        @endforeach
    }

    function searchStudents(query) {
        const results = $('#student-search-results');
        if (!query.trim()) {
            results.classList.remove('show');
            return;
        }
        const q = query.toLowerCase();
        const filtered = allStudents.filter(s =>
            !selectedStudents.find(ss => ss.id === s.id) &&
            (s.name.toLowerCase().includes(q) || (s.email && s.email.toLowerCase().includes(q)))
        );

        if (filtered.length === 0) {
            results.innerHTML = '<div style="padding:0.75rem;font-size:var(--text-sm);color:var(--muted-foreground);">Không tìm thấy học sinh</div>';
        } else {
            results.innerHTML = filtered.map(s => `
                <div class="student-search-item" onclick="addStudent(${s.id}, '${s.name.replace(/'/g, "\\'")}', '${(s.email || '').replace(/'/g, "\\'")}')">
                    <div class="student-avatar-sm">${s.name.charAt(0).toUpperCase()}</div>
                    <div>
                        <div style="font-weight:500;">${s.name}</div>
                        <div style="font-size:var(--text-xs);color:var(--muted-foreground);">${s.email || ''}</div>
                    </div>
                </div>
            `).join('');
        }
        results.classList.add('show');
    }

    function addStudent(id, name, email) {
        if (selectedStudents.find(s => s.id === id)) return;
        selectedStudents.push({ id, name, email });
        renderStudentChips();
        renderStudentHiddenInputs();
        $('#student-search').value = '';
        $('#student-search-results').classList.remove('show');
    }

    function removeStudent(id) {
        selectedStudents = selectedStudents.filter(s => s.id !== id);
        renderStudentChips();
        renderStudentHiddenInputs();
    }

    function renderStudentChips() {
        const container = $('#selected-students-chips');
        if (!container) return;
        if (selectedStudents.length === 0) {
            container.innerHTML = '<div style="font-size:var(--text-xs);color:var(--muted-foreground);padding:0.25rem 0;">Chưa chọn học sinh nào</div>';
            return;
        }
        container.innerHTML = selectedStudents.map(s => `
            <div class="student-chip">
                <div class="student-avatar-sm" style="width:20px;height:20px;font-size:10px;">${s.name.charAt(0).toUpperCase()}</div>
                <span>${s.name}</span>
                <button type="button" class="chip-remove" onclick="removeStudent(${s.id})" title="Xóa">&times;</button>
            </div>
        `).join('');
    }

    function renderStudentHiddenInputs() {
        const container = $('#student-ids-container');
        if (!container) return;
        container.innerHTML = selectedStudents.map(s =>
            `<input type="hidden" name="assigned_students[]" value="${s.id}" />`
        ).join('');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // ── Render one question card ───────────────
    function renderQuestionCard(q, idx) {
        const isMc = q.type === 'multiple_choice';
        const isTf = q.type === 'true_false';
        const isSa = q.type === 'short_answer';
        const options = q.options || [];
        const correctIdx = isMc ? parseInt(q.correct_answer) : q.correct_answer;
        const isSelected = selectedQuestionIndexes.has(idx);
        const safeContent = escapeHtml(q.content || '(chưa có nội dung)');
        const safeExplanation = escapeHtml(q.explanation || '');
        const safeShortAnswer = escapeHtml(q.correct_answer || '(chưa nhập)');

        let optionsHtml = '';
        if (isMc && options.length) {
            const labels = ['A', 'B', 'C', 'D', 'E', 'F'];
            optionsHtml = `
                <div style="margin-top:0.75rem;display:flex;flex-direction:column;gap:0.5rem;">
                    ${options.map((opt, oi) => `
                        <div class="option-input ${parseInt(correctIdx) === oi ? 'selected' : ''}">
                            <span style="font-weight:600;font-size:var(--text-xs);color:var(--muted-foreground);width:1.25rem;">${labels[oi] || oi+1}</span>
                            <span style="flex:1;">${escapeHtml(opt)}</span>
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
                    <div style="font-weight:500;">${safeShortAnswer}</div>
                </div>`;
        }

        return `
        <div class="question-card" data-index="${idx}">
            <div class="question-card-header">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="window._qc.toggleSelect(${idx}, this.checked)" aria-label="Chọn câu hỏi ${idx + 1}" style="accent-color:var(--primary);width:1rem;height:1rem;" />
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
                <div style="font-weight:500;margin-bottom:0.5rem;">${safeContent}</div>
                ${optionsHtml}
                ${q.explanation ? `<div style="margin-top:0.5rem;font-size:var(--text-xs);color:var(--muted-foreground);"><strong>Giải thích:</strong> ${safeExplanation}</div>` : ''}
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
            selectedQuestionIndexes.clear();
            updateBulkQuestionActions();
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
        updateBulkQuestionActions();
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

    async function removeQuestion(idx) {
        if (!await window.showAppConfirm(`Xóa câu hỏi ${idx + 1}?`)) return;
        questions.splice(idx, 1);
        selectedQuestionIndexes.delete(idx);
        selectedQuestionIndexes = new Set([...selectedQuestionIndexes].map(i => i > idx ? i - 1 : i));
        renderQuestions();
        updateSummary();
    }

    async function removeSelectedQuestions() {
        const indexes = [...selectedQuestionIndexes].filter(idx => idx >= 0 && idx < questions.length).sort((a, b) => b - a);
        if (!indexes.length) return;

        if (!await window.showAppConfirm(`Xóa ${indexes.length} câu hỏi đã chọn?`)) return;
        indexes.forEach(idx => questions.splice(idx, 1));
        selectedQuestionIndexes.clear();
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
                                    value="${escapeHtml(opts[oi] || '')}"
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
        } else {
            optionsEditor = `
                <div class="form-group">
                    <label class="label label-required" for="edit-sa-answer">Đáp án đúng / ý chính</label>
                    <textarea id="edit-sa-answer" class="input" style="min-height:3rem;"
                        placeholder="Nhập đáp án hoặc ý chính để chấm...">${escapeHtml(q.correct_answer || '')}</textarea>
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
                        <label class="label label-required" for="edit-type">Loại câu hỏi</label>
                        <select id="edit-type" class="input select">
                            <option value="multiple_choice" ${q.type === 'multiple_choice' ? 'selected' : ''}>Trắc nghiệm</option>
                            <option value="true_false" ${q.type === 'true_false' ? 'selected' : ''}>Đúng/Sai</option>
                            <option value="short_answer" ${q.type === 'short_answer' ? 'selected' : ''}>Tự luận</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="label label-required" for="edit-content">Nội dung câu hỏi</label>
                        <textarea id="edit-content" class="input @error('content') input-error @enderror"
                            style="min-height:5rem;"
                            placeholder="Nhập nội dung câu hỏi...">${escapeHtml(q.content || '')}</textarea>
                    </div>

                    ${optionsEditor}

                    <div class="form-group">
                        <label class="label" for="edit-explanation">Giải thích / Phản hồi (tùy chọn)</label>
                        <textarea id="edit-explanation" class="input" style="min-height:3rem;"
                            placeholder="Giải thích đáp án đúng...">${escapeHtml(q.explanation || '')}</textarea>
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

        $('#edit-type').onchange = function() {
            const nextType = this.value;
            const content = $('#edit-content')?.value.trim() || q.content || '';
            const explanation = $('#edit-explanation')?.value.trim() || q.explanation || '';
            const nextQuestion = {
                type: nextType,
                content,
                points: 1,
                explanation,
                options: nextType === 'multiple_choice' ? ['', '', '', ''] : [],
                correct_answer: nextType === 'true_false'
                    ? 'true'
                    : (nextType === 'multiple_choice' ? '0' : (q.correct_answer && q.type === 'short_answer' ? q.correct_answer : 'Giáo viên chấm theo ý chính.')),
            };
            updateQuestion(editingIndex, nextQuestion);
            closeEditor();
            openEditor(editingIndex);
        };

        // Save handler
        $('#save-edit-btn').onclick = function() {
            const content = $('#edit-content').value.trim();
            const explanation = $('#edit-explanation').value.trim();
            const points = 1; // Always 1 point per question
            const selectedType = $('#edit-type')?.value || q.type;

            if (!content) {
                window.showAppAlert('Vui lòng nhập nội dung câu hỏi!');
                return;
            }

            let correct_answer = q.correct_answer;
            if (selectedType === 'multiple_choice') {
                const radios = document.getElementsByName('correct_option_edit');
                for (const r of radios) {
                    if (r.checked) { correct_answer = r.value; break; }
                }
                const newOpts = ['A','B','C','D'].map((_, oi) => {
                    const inp = $(`#edit-opt-${oi}`);
                    return inp ? inp.value.trim() : '';
                });
                updateQuestion(editingIndex, { type: selectedType, content, options: newOpts, correct_answer, points, explanation });
            } else if (selectedType === 'true_false') {
                const tfRadios = document.getElementsByName('correct_tf_edit');
                for (const r of tfRadios) {
                    if (r.checked) { correct_answer = r.value; break; }
                }
                updateQuestion(editingIndex, { type: selectedType, content, options: [], correct_answer, points, explanation });
            } else {
                const saInput = $('#edit-sa-answer');
                correct_answer = saInput ? saInput.value.trim() : '';
                if (!correct_answer) {
                    correct_answer = 'Giáo viên chấm theo ý chính.';
                }
                updateQuestion(editingIndex, { type: selectedType, content, options: [], correct_answer, points, explanation });
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

        const questionsJson = $('#questions-json');
        if (questionsJson) {
            questionsJson.value = JSON.stringify(questions);
        }
    }

    function parseQuestionsJson(raw) {
        if (!raw || raw === '[]') return [];

        try {
            return JSON.parse(raw);
        } catch (error) {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = raw;
            const decoded = textarea.value;
            return decoded && decoded !== raw ? JSON.parse(decoded) : [];
        }
    }

    // ── Validate before submit ─────────────────
    function validateForm() {
        const title = $('#quiz-title').value.trim();
        if (!title) {
            window.showAppAlert('Vui lòng nhập tiêu đề bài thi!');
            $('#quiz-title').focus();
            return false;
        }
        if (questions.length === 0) {
            window.showAppAlert('Vui lòng thêm ít nhất 1 câu hỏi!');
            return false;
        }
        for (let i = 0; i < questions.length; i++) {
            const q = questions[i];
            if (!q.content || !q.content.trim()) {
                window.showAppAlert(`Câu hỏi ${i + 1}: Vui lòng nhập nội dung câu hỏi!`);
                openEditor(i);
                return false;
            }
            if (!q.correct_answer && q.correct_answer !== '0' && q.correct_answer !== 0 && q.correct_answer !== 'true' && q.correct_answer !== 'false') {
                window.showAppAlert(`Câu hỏi ${i + 1}: Vui lòng chọn đáp án đúng!`);
                openEditor(i);
                return false;
            }
            if (q.type === 'multiple_choice') {
                const opts = q.options || [];
                const filled = opts.filter(o => o && o.trim()).length;
                if (filled < 2) {
                    window.showAppAlert(`Câu hỏi ${i + 1}: Cần ít nhất 2 đáp án cho câu trắc nghiệm!`);
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
            const loaded = parseQuestionsJson(oldJson);
            if (Array.isArray(loaded) && loaded.length > 0) {
                questions = loaded;
            }
        } catch(e) {}

        renderQuestions();
        updateSummary();

        // Add question buttons
        $('#add-mc-btn').onclick = () => { closeQuestionCreateMenu(); addQuestion('multiple_choice'); };
        $('#add-tf-btn').onclick = () => { closeQuestionCreateMenu(); addQuestion('true_false'); };
        $('#add-sa-btn').onclick = () => { closeQuestionCreateMenu(); addQuestion('short_answer'); };

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

        // Student search: close results on outside click
        document.addEventListener('click', function(e) {
            const results = $('#student-search-results');
            const search = $('#student-search');
            if (results && search && !results.contains(e.target) && e.target !== search) {
                results.classList.remove('show');
            }
            const menu = $('#question-create-menu');
            const button = $('#create-question-btn');
            if (menu && button && !menu.contains(e.target) && !button.contains(e.target)) {
                closeQuestionCreateMenu();
            }
        });

        $('#ai-question-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closeAiQuestionModal();
        });
        $('#ai-source-file')?.addEventListener('change', syncAiTopicFromFile);
        $('#import-file-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closeImportFileModal();
        });
        $('#bank-picker-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closeBankPickerModal();
        });

        selectedStudents = Array.isArray(INITIAL_SELECTED_STUDENTS)
            ? INITIAL_SELECTED_STUDENTS.map(s => ({ id: Number(s.id), name: s.name || '', email: s.email || '' }))
            : [];

        // Initial student load
        const initialClassId = $('#quiz-class').value;
        loadStudentsForClass(initialClassId);

        // If specific students were pre-selected (e.g. from old() data), render them
        renderStudentChips();
        renderStudentHiddenInputs();

        // Initial assign type setup
        const assignType = INITIAL_ASSIGN_TYPE || 'class';
        if (assignType === 'everyone') {
            setAssignType('everyone');
        } else if (assignType === 'specific') {
            setAssignType('specific');
        } else {
            setAssignType('class');
        }

        const quizType = INITIAL_QUIZ_TYPE || 'exam';
        setQuizType(quizType);
        syncUnlimitedAttemptsControl();
        $('#unlimited-attempts')?.addEventListener('change', syncUnlimitedAttemptsControl);

        if (OPEN_BANK_PICKER_ON_LOAD) {
            openBankPickerModal();
        }
    }

    // Expose API globally
    window._qc = {
        add: addQuestion,
        edit: openEditor,
        remove: removeQuestion,
        closeEditor,
        updateQuestion,
        toggleSelect: toggleQuestionSelection,
        removeSelected: removeSelectedQuestions,
        getQuestions: () => questions,
    };
    // Expose student picker globally
    window._addStudent = addStudent;
    window._removeStudent = removeStudent;
    window.addStudent = addStudent;
    window.removeStudent = removeStudent;
    window.onClassChange = onClassChange;
    window.setQuizType = setQuizType;
    window.toggleAntiCheat = toggleAntiCheat;
    window.setAssignType = setAssignType;
    window.searchStudents = searchStudents;
    window.openAiQuestionModal = openAiQuestionModal;
    window.closeAiQuestionModal = closeAiQuestionModal;
    window.generateAiQuestions = generateAiQuestions;
    window.toggleSelectAllQuestions = toggleSelectAllQuestions;
    window.removeSelectedQuestions = removeSelectedQuestions;
    window.openImportFileModal = openImportFileModal;
    window.closeImportFileModal = closeImportFileModal;
    window.importQuestionsFromFile = importQuestionsFromFile;
    window.toggleQuestionCreateMenu = toggleQuestionCreateMenu;
    window.openBankPickerModal = openBankPickerModal;
    window.closeBankPickerModal = closeBankPickerModal;
    window.renderBankQuestions = renderBankQuestions;
    window.toggleBankQuestion = toggleBankQuestion;
    window.toggleVisibleBankQuestions = toggleVisibleBankQuestions;
    window.addSelectedBankQuestions = addSelectedBankQuestions;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
