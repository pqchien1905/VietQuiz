{{-- Student: course-detail --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
.course-hero {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    padding: 2rem 1.5rem 2.5rem;
    color: #fff;
}
.course-progress-ring { position: relative; width: 5.5rem; height: 5.5rem; }
.course-progress-ring svg { transform: rotate(-90deg); }
.course-progress-ring .pct {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: var(--text-base); font-weight: 800;
}
.nav-tabs-custom {
    display: flex; gap: 0;
    border-bottom: 1px solid var(--border);
    margin-bottom: 1.5rem;
}
.nav-tab-custom {
    padding: 0.625rem 1.25rem;
    border: none; background: none;
    border-bottom: 2px solid transparent;
    color: var(--muted-foreground);
    cursor: pointer; font-weight: 600;
    font-size: var(--text-sm);
    transition: all var(--transition-fast);
}
.nav-tab-custom:hover { color: var(--foreground); }
.nav-tab-custom.active { color: var(--primary); border-bottom-color: var(--primary); }
.content-tab { display: none; }
.content-tab.active { display: block; }
</style>
@endpush

@section('content')
<?php
    $courseColor = $course->color ?? '#3b82f6';
    $totalQuizzes = $course->quizzes->count();
    $totalAssignments = $course->assignments->count();
    $totalItems = $totalQuizzes + $totalAssignments;
    $completedItems = count($completedQuizIds) + count($submittedAssignmentIds);
    $circumference = 2 * 3.14159 * 36;
    $ringOffset = $circumference * (1 - $completionPct / 100);
?>

<!-- Hero -->
<div class="course-hero">
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="margin-bottom: 1rem;">
            <a href="{{ route('student.courses') }}"
               style="color: rgba(255,255,255,.7); font-size: var(--text-sm); text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                Khóa học
            </a>
        </div>
        <div style="display: flex; align-items: flex-start; gap: 1.5rem; flex-wrap: wrap; justify-content: space-between;">
            <div>
                <h1 style="color: #fff; font-size: var(--text-2xl); margin-bottom: 0.375rem;">{{ $course->name }}</h1>
                <p style="color: rgba(255,255,255,.75); font-size: var(--text-sm);">
                    GV: {{ $course->teacher->name ?? 'N/A' }}
                    @if($course->classModel)
                    &middot; {{ $course->classModel->name }}
                    @endif
                </p>
                @if($course->description)
                <p style="color: rgba(255,255,255,.6); font-size: var(--text-sm); margin-top: 0.5rem; max-width: 500px;">{{ $course->description }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Content -->
<div style="max-width: 900px; margin: 0 auto; padding: 0 1.5rem 2rem;">

    <!-- Stats + Progress -->
    <div style="display: grid; grid-template-columns: 1fr auto; gap: 1.5rem; align-items: center; margin-top: -1.5rem; margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem;">
            <div class="stat-card" style="text-align: center;">
                <div class="stat-card__value" style="color: var(--primary);">{{ $totalQuizzes }}</div>
                <div class="stat-card__label">Bài kiểm tra</div>
            </div>
            <div class="stat-card" style="text-align: center;">
                <div class="stat-card__value" style="color: var(--info);">{{ $totalAssignments }}</div>
                <div class="stat-card__label">Bài tập</div>
            </div>
            <div class="stat-card" style="text-align: center;">
                <div class="stat-card__value" style="color: var(--success);">{{ $completedItems }}/{{ $totalItems }}</div>
                <div class="stat-card__label">Đã hoàn thành</div>
            </div>
            <div class="stat-card" style="text-align: center;">
                <div class="stat-card__value">{{ $avgGrade !== null ? $avgGrade . '%' : 'N/A' }}</div>
                <div class="stat-card__label">Điểm TB</div>
            </div>
        </div>

        @if($totalItems > 0)
        <div style="text-align: center; flex-shrink: 0;">
            <div class="course-progress-ring">
                <svg width="88" height="88" viewBox="0 0 88 88">
                    <circle cx="44" cy="44" r="36" fill="none" stroke="rgba(255,255,255,.2)" stroke-width="7"/>
                    <circle cx="44" cy="44" r="36" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round"
                        stroke-dasharray="{{ $circumference }}"
                        stroke-dashoffset="{{ $ringOffset }}"/>
                </svg>
                <div class="pct" style="color: #fff;">{{ $completionPct }}%</div>
            </div>
            <div style="font-size: var(--text-xs); color: rgba(255,255,255,.6); margin-top: 0.25rem;">Hoàn thành</div>
        </div>
        @endif
    </div>

    <!-- Tabs -->
    <div class="nav-tabs-custom">
        <button class="nav-tab-custom active" data-tab="content">Nội dung</button>
        <button class="nav-tab-custom" data-tab="quizzes">Bài kiểm tra ({{ $totalQuizzes }})</button>
        <button class="nav-tab-custom" data-tab="assignments">Bài tập ({{ $totalAssignments }})</button>
        <button class="nav-tab-custom" data-tab="grades">Điểm số</button>
    </div>

    <!-- Tab: Content (combined) -->
    <div class="content-tab active" id="tab-content">
        @if($totalItems === 0)
        <div style="text-align: center; padding: 3rem; color: var(--muted-foreground);">
            <div style="font-size: 3rem; margin-bottom: 0.75rem;">📚</div>
            <h3 style="font-size: var(--text-xl); font-weight: 600; color: var(--foreground); margin-bottom: 0.25rem;">Chưa có nội dung</h3>
            <p>Khóa học này chưa có bài kiểm tra hay bài tập nào.</p>
        </div>
        @else

            @foreach($course->quizzes as $quiz)
            <?php $done = in_array($quiz->id, $completedQuizIds); ?>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--card); margin-bottom: 0.75rem; transition: box-shadow var(--transition-fast);"
                 onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='none'">
                <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: {{ $done ? 'color-mix(in srgb, var(--success) 12%, transparent)' : 'color-mix(in srgb, var(--primary) 12%, transparent)' }}; color: {{ $done ? 'var(--success)' : 'var(--primary)' }};">
                    @if($done)
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    @endif
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; font-size: var(--text-base); margin-bottom: 0.125rem;">{{ $quiz->title }}</div>
                    <div style="font-size: var(--text-xs); color: var(--muted-foreground);">
                        {{ $quiz->questions->count() }} câu
                        @if($quiz->time_limit) &middot; {{ $quiz->time_limit }} phút @endif
                        &middot; Đạt: {{ $quiz->passing_score }}%
                    </div>
                </div>
                @if($done)
                <span class="badge badge-success">Đã làm</span>
                <a href="{{ route('student.quiz-result', $quiz) }}" class="btn btn-outline btn-sm">Xem lại</a>
                @elseif($quiz->status === 'published')
                <a href="{{ route('student.quiz-take', $quiz) }}" class="btn btn-primary btn-sm">Làm bài</a>
                @else
                <span class="badge badge-outline">Chưa mở</span>
                @endif
            </div>
            @endforeach

            @foreach($course->assignments as $assignment)
            <?php
                $done = in_array($assignment->id, $submittedAssignmentIds);
                $isOverdue = $assignment->due_at && $assignment->due_at->isPast();
                $daysLeft = $assignment->due_at ? $assignment->due_at->diffInDays(now()) : null;
            ?>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--card); margin-bottom: 0.75rem; transition: box-shadow var(--transition-fast);"
                 onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='none'">
                <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: color-mix(in srgb, var(--warning) 12%, transparent); color: var(--warning);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; font-size: var(--text-base); margin-bottom: 0.125rem;">{{ $assignment->title }}</div>
                    <div style="font-size: var(--text-xs); color: var(--muted-foreground);">
                        {{ $assignment->total_points }} điểm
                        @if($assignment->due_at)
                        &middot; Hạn: {{ $assignment->due_at->format('d/m/Y H:i') }}
                        @if($isOverdue) &middot; <strong style="color: var(--destructive);">Đã quá hạn</strong>
                        @elseif($daysLeft !== null && $daysLeft <= 2) &middot; <strong style="color: var(--warning);">Còn {{ $daysLeft }} ngày</strong>
                        @endif
                        @endif
                    </div>
                </div>
                @if($done)
                <span class="badge badge-success">Đã nộp</span>
                <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-outline btn-sm">Xem</a>
                @elseif($isOverdue)
                <span class="badge badge-destructive">Quá hạn</span>
                @else
                <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-primary btn-sm">Nộp bài</a>
                @endif
            </div>
            @endforeach

        @endif
    </div>

    <!-- Tab: Quizzes -->
    <div class="content-tab" id="tab-quizzes">
        @if($course->quizzes->isEmpty())
        <div style="text-align: center; padding: 3rem; color: var(--muted-foreground);">
            <div style="font-size: 3rem; margin-bottom: 0.75rem;">📝</div>
            <h3 style="font-size: var(--text-xl); font-weight: 600; color: var(--foreground);">Chưa có bài kiểm tra</h3>
        </div>
        @else
        @foreach($course->quizzes as $quiz)
        <?php $done = in_array($quiz->id, $completedQuizIds); ?>
        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--card); margin-bottom: 0.75rem;">
            <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius-md); background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 0.25rem;">{{ $quiz->title }}</div>
                <div style="font-size: var(--text-xs); color: var(--muted-foreground);">
                    {{ $quiz->questions->count() }} câu
                    @if($quiz->time_limit) &middot; {{ $quiz->time_limit }} phút @endif
                    &middot; Đạt: {{ $quiz->passing_score }}%
                </div>
            </div>
            @if($done)
            <div style="text-align: right;">
                <span class="badge badge-success" style="display: block; margin-bottom: 0.25rem;">Đã làm</span>
                <a href="{{ route('student.quiz-result', $quiz) }}" class="btn btn-ghost btn-sm">Xem kết quả</a>
            </div>
            @elseif($quiz->status === 'published')
            <a href="{{ route('student.quiz-take', $quiz) }}" class="btn btn-primary btn-sm">Làm bài</a>
            @else
            <span class="badge badge-outline">Chưa mở</span>
            @endif
        </div>
        @endforeach
        @endif
    </div>

    <!-- Tab: Assignments -->
    <div class="content-tab" id="tab-assignments">
        @if($course->assignments->isEmpty())
        <div style="text-align: center; padding: 3rem; color: var(--muted-foreground);">
            <div style="font-size: 3rem; margin-bottom: 0.75rem;">📋</div>
            <h3 style="font-size: var(--text-xl); font-weight: 600; color: var(--foreground);">Chưa có bài tập</h3>
        </div>
        @else
        @foreach($course->assignments as $assignment)
        <?php
            $done = in_array($assignment->id, $submittedAssignmentIds);
            $isOverdue = $assignment->due_at && $assignment->due_at->isPast();
        ?>
        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--card); margin-bottom: 0.75rem;">
            <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius-md); background: color-mix(in srgb, var(--warning) 12%, transparent); color: var(--warning); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 0.25rem;">{{ $assignment->title }}</div>
                <div style="font-size: var(--text-xs); color: var(--muted-foreground);">
                    {{ $assignment->total_points }} điểm
                    @if($assignment->due_at) &middot; Hạn: {{ $assignment->due_at->format('d/m/Y H:i') }} @endif
                </div>
            </div>
            @if($done)
            <span class="badge badge-success">Đã nộp</span>
            <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-outline btn-sm">Xem</a>
            @elseif($isOverdue)
            <span class="badge badge-destructive">Quá hạn</span>
            @else
            <a href="{{ route('student.assignment-detail', $assignment) }}" class="btn btn-primary btn-sm">Nộp bài</a>
            @endif
        </div>
        @endforeach
        @endif
    </div>

    <!-- Tab: Grades -->
    <div class="content-tab" id="tab-grades">
        @if($avgGrade === null)
        <div style="text-align: center; padding: 3rem; color: var(--muted-foreground);">
            <div style="font-size: 3rem; margin-bottom: 0.75rem;">📊</div>
            <h3 style="font-size: var(--text-xl); font-weight: 600; color: var(--foreground);">Chưa có điểm nào</h3>
            <p>Hoàn thành bài kiểm tra và bài tập để xem điểm.</p>
        </div>
        @else
        <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 4rem; font-weight: 800; color: var(--success);">{{ $avgGrade }}%</div>
            <div style="font-size: var(--text-lg); color: var(--muted-foreground); margin-top: 0.5rem;">Điểm trung bình của bạn trong khóa học này</div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    document.querySelectorAll('.nav-tab-custom').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.nav-tab-custom').forEach(function(b) { b.classList.remove('active'); });
            document.querySelectorAll('.content-tab').forEach(function(t) { t.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById('tab-' + btn.getAttribute('data-tab')).classList.add('active');
        });
    });
})();
</script>
@endpush
