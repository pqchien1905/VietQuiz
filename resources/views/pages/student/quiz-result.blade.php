{{-- Student: quiz-result --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
.result-hero {
    background: linear-gradient(135deg, #eff6ff, #fff7ed);
    padding: 2.5rem 1.5rem;
    text-align: center;
}
.dark .result-hero { background: linear-gradient(135deg, #1e2235, #1a1d2e); }
.score-circle {
    width: 140px; height: 140px;
    border-radius: 50%;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    margin: 0 auto 1.25rem;
    font-weight: 700;
}
.stat-mini { text-align: center; }
.stat-mini-val { font-size: var(--text-2xl); font-weight: 700; }
.stat-mini-label { font-size: var(--text-xs); color: var(--muted-foreground); }
.result-tabs { display: flex; gap: 0.25rem; padding: 0.25rem; background: var(--muted); border-radius: var(--radius-lg); margin-bottom: 1.5rem; }
.result-tab {
    flex: 1; padding: 0.625rem 1rem; border: none;
    border-radius: var(--radius-md); background: none;
    cursor: pointer; font-weight: 600; font-size: var(--text-sm);
    transition: all var(--transition-fast); color: var(--muted-foreground);
}
.result-tab.active { background: var(--card); color: var(--foreground); box-shadow: var(--shadow-sm); }
.result-detail-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--card);
    margin-bottom: 1rem;
    overflow: hidden;
}
.result-detail-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.25rem;
    background: var(--muted);
    border-bottom: 1px solid var(--border);
}
.result-detail-body { padding: 1.25rem; }
.result-correct { border-left: 4px solid var(--success); }
.result-wrong { border-left: 4px solid var(--destructive); }
.result-skipped { border-left: 4px solid var(--muted-foreground); }
.result-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: var(--text-xs); font-weight: 600; }
.result-badge-correct { background: color-mix(in srgb, var(--success) 15%, transparent); color: var(--success); }
.result-badge-wrong { background: color-mix(in srgb, var(--destructive) 15%, transparent); color: var(--destructive); }
.result-badge-skipped { background: var(--muted); color: var(--muted-foreground); }
.review-panel { display: none; }
.review-panel.active { display: block; }
.stats-panel { display: none; }
.stats-panel.active { display: block; }
@media print {
    .quiz-header, .result-tabs, .btn, .no-print { display: none !important; }
    .result-hero { background: white; padding: 1rem; }
}
</style>
@endpush

@section('content')
<?php
    $answersJson = $attempt->pivot->answers ?? '{}';
    $answersData = json_decode($answersJson, true) ?: [];
    $totalPoints = $attempt->pivot->total_points ?? 1;
    $earnedPoints = $attempt->pivot->score ?? 0;
    $percent = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
    $passed = $percent >= ($quiz->passing_score ?? 50);
    $gradeLabel = $percent >= 90 ? 'Xuất sắc' : ($percent >= 80 ? 'Giỏi' : ($percent >= 70 ? 'Khá' : ($percent >= 60 ? 'Trung bình' : 'Yếu')));
    $gradeColor = $passed ? 'var(--success)' : 'var(--destructive)';
    $bgColor = $passed ? 'color-mix(in srgb, var(--success) 12%, transparent)' : 'color-mix(in srgb, var(--destructive) 12%, transparent)';
    $timeSpent = '';
    if ($attempt->pivot->started_at && $attempt->pivot->submitted_at) {
        $start = \Carbon\Carbon::parse($attempt->pivot->started_at);
        $end = \Carbon\Carbon::parse($attempt->pivot->submitted_at);
        $diff = $start->diff($end);
        $timeSpent = $diff->i > 0 ? $diff->format('%i phút %s giây') : $diff->format('%s giây');
    }
?>

<!-- Result Hero -->
<div class="result-hero">
    <div class="score-circle animate-fade-in" style="background:{{ $bgColor }}; color:{{ $gradeColor }};">
        <span style="font-size:2.25rem;">{{ $percent }}%</span>
        <span style="font-size:var(--text-sm);font-weight:600;">{{ $gradeLabel }}</span>
    </div>

    <h1 style="font-size:var(--text-2xl);font-weight:800;margin-bottom:0.25rem;">
        {{ $passed ? 'Chúc mừng! Bạn đã đạt!' : 'Chưa đạt. Cố gắng lần sau!' }}
    </h1>
    <p style="color:var(--muted-foreground);margin-bottom:1rem;">{{ $quiz->title }}</p>

    <div style="display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <div class="stat-mini">
            <div class="stat-mini-val" style="color:var(--success);">
                {{ $earnedPoints }}
            </div>
            <div class="stat-mini-label">Điểm đạt</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-val" style="color:var(--muted-foreground);">{{ $totalPoints }}</div>
            <div class="stat-mini-label">Tổng điểm</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $percent }}%</div>
            <div class="stat-mini-label">Tỷ lệ</div>
        </div>
        @if($timeSpent)
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $timeSpent }}</div>
            <div class="stat-mini-label">Thời gian</div>
        </div>
        @endif
        <div class="stat-mini">
            <div class="stat-mini-val" style="color:{{ $quiz->passing_score > $percent ? 'var(--destructive)' : 'var(--success)' }};">
                {{ $quiz->passing_score }}%
            </div>
            <div class="stat-mini-label">Điểm đạt</div>
        </div>
    </div>

    <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:0.75rem;">
        <a href="{{ route('student.quizzes') }}" class="btn btn-outline gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Danh sách bài kiểm tra
        </a>
        <a href="{{ route('student.quiz-take', $quiz) }}" class="btn btn-outline gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/></svg>
            Làm lại
        </a>
        <button class="btn btn-primary gap-2" onclick="document.querySelector('.result-tabs .result-tab:last-child').click();">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Xem đáp án
        </button>
    </div>
</div>

<!-- Body -->
<div style="padding:1.5rem;max-width:800px;margin:0 auto;">
    <!-- Tabs -->
    <div class="result-tabs no-print">
        <button class="result-tab active" id="tab-stats" onclick="switchTab('stats')">Thống kê</button>
        <button class="result-tab" id="tab-review" onclick="switchTab('review')">Xem đáp án</button>
    </div>

    <!-- Stats Panel -->
    <div class="stats-panel active" id="panel-stats">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
            <?php
                $correct = 0; $wrong = 0; $skipped = 0;
                foreach($quiz->questions as $q) {
                    $userAns = $answersData[$q->id] ?? null;
                    if ($userAns === null || $userAns === '') {
                        $skipped++;
                    } elseif ($q->isCorrect($userAns)) {
                        $correct++;
                    } else {
                        $wrong++;
                    }
                }
            ?>
            <div class="card" style="text-align:center;">
                <div class="card-content" style="padding:1.5rem;">
                    <div style="font-size:var(--text-3xl);font-weight:800;color:var(--success);">{{ $correct }}</div>
                    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">Câu đúng</div>
                </div>
            </div>
            <div class="card" style="text-align:center;">
                <div class="card-content" style="padding:1.5rem;">
                    <div style="font-size:var(--text-3xl);font-weight:800;color:var(--destructive);">{{ $wrong }}</div>
                    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">Câu sai</div>
                </div>
            </div>
            <div class="card" style="text-align:center;">
                <div class="card-content" style="padding:1.5rem;">
                    <div style="font-size:var(--text-3xl);font-weight:800;color:var(--muted-foreground);">{{ $skipped }}</div>
                    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;">Bỏ qua</div>
                </div>
            </div>
        </div>

        <!-- Score breakdown bar -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Phân tích kết quả</h3>
            </div>
            <div class="card-content">
                <div style="display:flex;height:2rem;border-radius:var(--radius-md);overflow:hidden;background:var(--muted);margin-bottom:1rem;">
                    @if($correct + $wrong + $skipped > 0)
                    <div style="background:var(--success);width:{{ round($correct / ($correct + $wrong + $skipped) * 100) }}%;display:flex;align-items:center;justify-content:center;font-size:var(--text-xs);font-weight:700;color:#fff;">
                        {{ $correct > 0 ? round($correct / ($correct + $wrong + $skipped) * 100) . '%' : '' }}
                    </div>
                    <div style="background:var(--destructive);width:{{ round($wrong / ($correct + $wrong + $skipped) * 100) }}%;display:flex;align-items:center;justify-content:center;font-size:var(--text-xs);font-weight:700;color:#fff;">
                        {{ $wrong > 0 ? round($wrong / ($correct + $wrong + $skipped) * 100) . '%' : '' }}
                    </div>
                    <div style="background:var(--muted-foreground);width:{{ round($skipped / ($correct + $wrong + $skipped) * 100) }}%;display:flex;align-items:center;justify-content:center;font-size:var(--text-xs);font-weight:700;color:#fff;">
                        {{ $skipped > 0 ? round($skipped / ($correct + $wrong + $skipped) * 100) . '%' : '' }}
                    </div>
                    @endif
                </div>
                <div style="display:flex;gap:1.5rem;font-size:var(--text-sm);">
                    <div style="display:flex;align-items:center;gap:0.375rem;">
                        <div style="width:0.75rem;height:0.75rem;border-radius:50%;background:var(--success);"></div>
                        <span>Đúng ({{ $correct }})</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.375rem;">
                        <div style="width:0.75rem;height:0.75rem;border-radius:50%;background:var(--destructive);"></div>
                        <span>Sai ({{ $wrong }})</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.375rem;">
                        <div style="width:0.75rem;height:0.75rem;border-radius:50%;background:var(--muted-foreground);"></div>
                        <span>Bỏ qua ({{ $skipped }})</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Panel -->
    <div class="review-panel" id="panel-review">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-weight:700;font-size:var(--text-lg);">Chi tiết từng câu hỏi</h3>
            @if($quiz->show_result)
            <span style="font-size:var(--text-xs);color:var(--success);display:flex;align-items:center;gap:0.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Hiện đáp án đúng
            </span>
            @endif
        </div>

        @foreach($quiz->questions as $qi => $question)
        <?php
            $userAns = $answersData[$question->id] ?? null;
            $isCorrect = $userAns !== null && $userAns !== '' && $question->isCorrect($userAns);
            $isSkipped = $userAns === null || $userAns === '';
            $cardClass = $isSkipped ? 'result-skipped' : ($isCorrect ? 'result-correct' : 'result-wrong');
            $options = $question->options ?? [];
            $labels = ['A', 'B', 'C', 'D'];
        ?>
        <div class="result-detail-card {{ $cardClass }}">
            <div class="result-detail-header">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <span style="font-weight:700;font-size:var(--text-sm);color:var(--muted-foreground);">Câu {{ $qi + 1 }}</span>
                    @if($isCorrect)
                    <span class="result-badge result-badge-correct">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Đúng
                    </span>
                    @elseif($isSkipped)
                    <span class="result-badge result-badge-skipped">Bỏ qua</span>
                    @else
                    <span class="result-badge result-badge-wrong">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Sai
                    </span>
                    @endif
                    <span style="font-size:var(--text-xs);color:var(--muted-foreground);">{{ $question->points ?? 1 }} điểm</span>
                </div>
                @if(!$isCorrect && $quiz->show_result)
                <span class="result-badge result-badge-correct" style="font-size:var(--text-xs);">
                    Đáp án: {{ $question->type === 'multiple_choice' && is_numeric($question->correct_answer) ? $labels[(int)$question->correct_answer] . '. ' . ($options[(int)$question->correct_answer] ?? '') : ($question->correct_answer ?: '(chưa có)') }}
                </span>
                @endif
            </div>
            <div class="result-detail-body">
                <div style="font-weight:500;margin-bottom:0.875rem;">{{ $question->content }}</div>

                @if($question->type === 'multiple_choice' && count($options))
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    @foreach($options as $oi => $opt)
                    <?php
                        $isUserChoice = is_numeric($userAns) && (int)$userAns === $oi;
                        $isCorrectChoice = (int)$question->correct_answer === $oi;
                        $optClass = '';
                        if ($isCorrectChoice && $quiz->show_result) $optClass = 'background:color-mix(in srgb,var(--success) 12%,transparent);border-color:var(--success);';
                        if ($isUserChoice && !$isCorrectChoice) $optClass = 'background:color-mix(in srgb,var(--destructive) 12%,transparent);border-color:var(--destructive);';
                        if (!$isUserChoice && !$isCorrectChoice) $optClass = 'opacity:0.5;';
                    ?>
                    <div style="display:flex;align-items:center;gap:0.75rem;padding:0.625rem 0.875rem;border:1px solid var(--border);border-radius:var(--radius-md);font-size:var(--text-sm);{{ $optClass }}">
                        <span style="font-weight:700;width:1.5rem;color:var(--muted-foreground);">{{ $labels[$oi] }}.</span>
                        <span style="flex:1;">{{ $opt }}</span>
                        @if($isUserChoice)
                        <span style="font-weight:700;color:var(--destructive);">Bạn chọn</span>
                        @endif
                        @if($isCorrectChoice && $quiz->show_result)
                        <span style="font-weight:700;color:var(--success);">✓</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @elseif($question->type === 'true_false')
                <div style="display:flex;gap:1rem;">
                    <?php
                        $userIsTrue = $userAns === 'true';
                        $userIsFalse = $userAns === 'false';
                        $correctIsTrue = $question->correct_answer === 'true';
                    ?>
                    <div style="padding:0.625rem 1rem;border:1px solid var(--border);border-radius:var(--radius-md);font-size:var(--text-sm);{{ $userIsTrue && !$correctIsTrue ? 'border-color:var(--destructive);background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}{{ $correctIsTrue && $quiz->show_result ? 'border-color:var(--success);background:color-mix(in srgb,var(--success) 8%,transparent);' : '' }}">
                        ✓ True
                        @if($userIsTrue && !$correctIsTrue) — <strong style="color:var(--destructive)">Sai</strong> @endif
                        @if($correctIsTrue && $quiz->show_result) — <strong style="color:var(--success)">Đáp án</strong> @endif
                    </div>
                    <div style="padding:0.625rem 1rem;border:1px solid var(--border);border-radius:var(--radius-md);font-size:var(--text-sm);{{ $userIsFalse && $correctIsTrue ? 'border-color:var(--destructive);background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}{{ !$correctIsTrue && $quiz->show_result ? 'border-color:var(--success);background:color-mix(in srgb,var(--success) 8%,transparent);' : '' }}">
                        ✗ False
                        @if($userIsFalse && $correctIsTrue) — <strong style="color:var(--destructive)">Sai</strong> @endif
                        @if(!$correctIsTrue && $quiz->show_result) — <strong style="color:var(--success)">Đáp án</strong> @endif
                    </div>
                </div>
                @else
                <div style="padding:0.75rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--muted);">
                    <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:0.25rem;">Câu trả lời của bạn:</div>
                    <div style="font-weight:500;">{{ $userAns ?: '(không có)' }}</div>
                </div>
                @endif

                @if($question->explanation && $quiz->show_result)
                <div style="margin-top:0.75rem;padding:0.75rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--info) 8%,transparent);border:1px solid color-mix(in srgb,var(--info) 25%,transparent);">
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--info);margin-bottom:0.25rem;">💡 Giải thích</div>
                    <div style="font-size:var(--text-sm);">{{ $question->explanation }}</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach

        <div style="display:flex;justify-content:center;gap:0.75rem;margin-top:1.5rem;">
            <a href="{{ route('student.quizzes') }}" class="btn btn-outline">Danh sách bài kiểm tra</a>
            <a href="{{ route('student.quiz-take', $quiz) }}" class="btn btn-primary gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/></svg>
                Làm lại
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.result-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.stats-panel, .review-panel').forEach(p => p.classList.remove('active'));

    if (tab === 'stats') {
        document.getElementById('tab-stats').classList.add('active');
        document.getElementById('panel-stats').classList.add('active');
    } else {
        document.getElementById('tab-review').classList.add('active');
        document.getElementById('panel-review').classList.add('active');
    }
}
</script>
@endpush
