{{-- Teacher: class-detail --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
    .student-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 0.75rem; border-bottom: 1px solid var(--border); transition: background var(--transition-fast); }
    .student-row:last-child { border-bottom: none; }
    .student-row:hover { background: var(--muted); border-radius: var(--radius-sm); }
    .tab-bar-custom { display: flex; gap: 0; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem; }
    .tab-btn-c { padding: 0.75rem 1.25rem; border: none; background: none; border-bottom: 2px solid transparent; color: var(--muted-foreground); cursor: pointer; font-weight: 600; font-size: var(--text-sm); transition: all var(--transition-fast); }
    .tab-btn-c:hover { color: var(--foreground); }
    .tab-btn-c.active { color: var(--primary); border-bottom-color: var(--primary); }
    .content-panel { display: none; }
    .content-panel.active { display: block; }
    .search-wrap { position: relative; flex: 1; max-width: 280px; }
    .search-wrap svg { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--muted-foreground); pointer-events: none; }
    .search-wrap input { padding-left: 2.25rem; }
</style>
@endpush

@section('content')
<?php
    $gradeColors = [
        'A' => 'var(--success)',
        'B' => 'var(--info)',
        'C' => 'var(--warning)',
        'D' => 'var(--warning)',
        'F' => 'var(--destructive)',
    ];
    $studentColors = ['#3b82f6','#ef4444','#22c55e','#f97316','#a855f7','#06b6d4','#ec4899','#eab308'];
?>

<!-- Breadcrumb -->
<div class="breadcrumb stagger-children">
    <a href="{{ route('teacher.classes') }}">Lớp học</a>
    <span class="breadcrumb-sep">›</span>
    <span class="active">{{ $class->name }}</span>
</div>

<!-- Header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;" class="stagger-children">
    <div style="display:flex;align-items:center;gap:1rem;">
        <div style="width:4rem;height:4rem;border-radius:var(--radius-lg);background:linear-gradient(135deg,var(--primary),#6366f1);display:flex;align-items:center;justify-content:center;font-size:1.75rem;color:#fff;font-weight:700;">
            {{ substr($class->name, 0, 1) }}
        </div>
        <div>
            <h1 style="font-size:var(--text-2xl);">{{ $class->name }}</h1>
            <div style="display:flex;gap:0.5rem;margin-top:0.25rem;flex-wrap:wrap;">
                <span class="badge badge-primary">{{ $studentCount }} học sinh</span>
                @if($class->subject)
                <span class="badge badge-outline">{{ $class->subject }}</span>
                @endif
                <span class="badge badge-outline">Mã: {{ $class->code }}</span>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:0.5rem;">
        <button class="btn btn-outline gap-2" onclick="copyCode()">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Sao chép mã
        </button>
        <a href="{{ route('teacher.quiz-create') }}" class="btn btn-primary gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Giao bài kiểm tra
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Học sinh</div>
        <div class="stat-card__value">{{ $studentCount }}</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB lớp</div>
        <div class="stat-card__value" style="color:var(--success);">{{ $classAvg ? round($classAvg, 1) . '%' : '—' }}</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài thi đã giao</div>
        <div class="stat-card__value">{{ $quizzes->count() }}</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tỷ lệ hoàn thành</div>
        <div class="stat-card__value" style="color:var(--info);">{{ $completionRate }}%</div>
    </div>
</div>

<!-- Tabs -->
<div class="tab-bar-custom stagger-children">
    <button class="tab-btn-c active" data-tab="students">Học sinh ({{ $studentCount }})</button>
    <button class="tab-btn-c" data-tab="quizzes">Bài kiểm tra ({{ $quizzes->count() }})</button>
    <button class="tab-btn-c" data-tab="progress">Tiến độ</button>
    <button class="tab-btn-c" data-tab="settings">Cài đặt lớp</button>
</div>

<!-- Tab: Students -->
<div class="content-panel active" id="panel-students">
    <div class="card">
        <div class="card-header">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                <h3 class="card-title">Danh sách Học sinh</h3>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <div class="search-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="search" class="input" placeholder="Tìm học sinh..." id="stu-search" style="font-size:var(--text-sm);" />
                    </div>
                </div>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Học sinh</th>
                        <th style="text-align:right;">Điểm TB</th>
                        <th style="text-align:center;">Đã làm</th>
                        <th>Xếp loại</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="stu-tbody">
                    @forelse($studentGrades as $idx => $student)
                    <?php $c = $studentColors[$idx % count($studentColors)]; ?>
                    <tr class="stu-row" data-name="{{ strtolower($student->name) }}">
                        <td>
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <div class="avatar" style="width:2.25rem;height:2.25rem;background:color-mix(in srgb, {{ $c }} 15%, transparent);color:{{ $c }};display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:var(--text-xs);font-weight:700;flex-shrink:0;">
                                    {{ collect(explode(' ', $student->name))->filter()->map(fn($w) => $w[0])->slice(-2)->implode('') }}
                                </div>
                                <span style="font-weight:500;">{{ $student->name }}</span>
                            </div>
                        </td>
                        <td style="text-align:right;">
                            @if($student->avg_pct !== null)
                            <span style="font-weight:700;color:{{ $gradeColors[$student->grade_letter] Đ 'inherit' }};">{{ $student->avg_pct }}%</span>
                            @else
                            <span style="color:var(--muted-foreground);">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <span style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $student->completed_count }}</span>
                        </td>
                        <td>
                            @if($student->avg_pct !== null)
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:1.75rem;height:1.75rem;border-radius:50%;font-size:var(--text-xs);font-weight:700;background:color-mix(in srgb, {{ $gradeColors[$student->grade_letter] Đ 'gray' }} 15%, transparent);color:{{ $gradeColors[$student->grade_letter] Đ 'gray' }};">{{ $student->grade_letter }}</span>
                            @else
                            <span style="color:var(--muted-foreground);">—</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('teacher.classes.remove-student', [$class, $student->id]) }}" onsubmit="return confirm('Xóa {{ $student->name }} khỏi lớp?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--destructive);" title="Xóa khỏi lớp">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--muted-foreground);">Chưa có học sinh nào trong lớp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Quizzes -->
<div class="content-panel" id="panel-quizzes">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Bài kiểm tra đã giao</h3>
        </div>
        @if($quizzes->isEmpty())
        <div class="card-content" style="text-align:center;padding:2rem;color:var(--muted-foreground);">
            Chưa có bài kiểm tra nào được giao cho lớp này.
        </div>
        @else
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Đề thi</th>
                        <th>Ngày tạo</th>
                        <th style="text-align:right;">Đã làm</th>
                        <th style="text-align:right;">Điểm TB</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quizzes as $quiz)
                    <tr>
                        <td><span style="font-weight:600;">{{ $quiz->title }}</span></td>
                        <td style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $quiz->created_at->format('d/m/Y') }}</td>
                        <td style="text-align:right;">{{ $quiz->submitted_count Đ 0 }}</td>
                        <td style="text-align:right;">
                            @if($quiz->avg_score !== null)
                            <span style="font-weight:700;color:var(--success);">{{ $quiz->avg_score }}%</span>
                            @else
                            <span style="color:var(--muted-foreground);">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('teacher.quiz-detail', $quiz) }}" class="btn btn-ghost btn-sm">Xem chi tiết</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<!-- Tab: Progress -->
<div class="content-panel" id="panel-progress">
    <!-- Mini stats -->
    <?php
        $maxScore = $studentGrades->filter(fn($s) => $s->avg_pct !== null)->max('avg_pct');
        $minScore = $studentGrades->filter(fn($s) => $s->avg_pct !== null)->min('avg_pct');
    ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">
        <div class="stat-card">
            <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm cao nhất</div>
            <div class="stat-card__value" style="color:var(--success);">{{ $maxScore ? $maxScore . '%' : '—' }}</div>
        </div>
        <div class="stat-card">
            <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm thấp nhất</div>
            <div class="stat-card__value" style="color:var(--destructive);">{{ $minScore ? $minScore . '%' : '—' }}</div>
        </div>
        <div class="stat-card">
            <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Giỏi (≥90%)</div>
            <div class="stat-card__value" style="color:var(--success);">{{ $dist['excellent'] }}</div>
        </div>
        <div class="stat-card">
            <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Cần cải thiện (&lt;60%)</div>
            <div class="stat-card__value" style="color:var(--warning);">{{ $dist['weak'] + $dist['average'] }}</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
        <!-- Top students -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top 5 Học sinh</h3>
            </div>
            <div class="card-content">
                @if($topStudents->isEmpty())
                <div style="text-align:center;padding:1.5rem;color:var(--muted-foreground);">Chưa có dữ liệu</div>
                @else
                @foreach($topStudents as $i => $student)
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid var(--border);">
                    @if($i === 0) <span style="font-size:1.1rem;">🥇</span>
                    @elseif($i === 1) <span style="font-size:1.1rem;">🥈</span>
                    @elseif($i === 2) <span style="font-size:1.1rem;">🥉</span>
                    @else <span style="width:1.5rem;text-align:center;font-weight:600;color:var(--muted-foreground);">{{ $i + 1 }}</span> @endif
                    <div style="flex:1;font-weight:500;font-size:var(--text-sm);">{{ $student->name }}</div>
                    <span style="font-weight:700;color:var(--success);">{{ $student->avg_pct }}%</span>
                </div>
                @endforeach
                @endif
            </div>
        </div>

        <!-- Need support -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cần hỗ trợ (&lt;60%)</h3>
            </div>
            <div class="card-content">
                @if($weakStudents->isEmpty())
                <div style="text-align:center;padding:1.5rem;color:var(--muted-foreground);">🎉 Tất cả học sinh đều đạt yêu cầu!</div>
                @else
                @foreach($weakStudents as $student)
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid var(--border);">
                    <span style="font-size:1.1rem;">⚠️</span>
                    <div style="flex:1;font-weight:500;font-size:var(--text-sm);">{{ $student->name }}</div>
                    <span style="font-weight:700;color:var(--destructive);">{{ $student->avg_pct }}%</span>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Tab: Settings -->
<div class="content-panel" id="panel-settings">
    <form method="POST" action="{{ route('teacher.classes.update', $class) }}">
        @csrf @method('PUT')
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h3 class="card-title">Thông tin Lớp học</h3>
            </div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="label label-required">Tên lớp</label>
                        <input type="text" name="name" class="input @error('name') input-error @enderror" value="{{ old('name', $class->name) }}" required />
                        @error('name') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="label">Mã lớp</label>
                        <input type="text" class="input" value="{{ $class->code }}" readonly style="background:var(--muted);cursor:not-allowed;" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Mô tả</label>
                    <textarea name="description" class="input @error('description') input-error @enderror" style="min-height:4rem;">{{ old('description', $class->description) }}</textarea>
                    @error('description') <span class="error-message">{{ $message }}</span> @enderror
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="label">Môn học</label>
                        <input type="text" name="subject" class="input" value="{{ old('subject', $class->subject) }}" placeholder="VD: Toán, Vật lý..." />
                    </div>
                    <div class="form-group">
                        <label class="label">Khối lớp</label>
                        <input type="text" name="grade_level" class="input" value="{{ old('grade_level', $class->grade_level) }}" placeholder="VD: 10, 11, 12..." />
                    </div>
                </div>
                <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 8 8"/></svg>
                        Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Danger zone -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="color:var(--destructive);">Vùng nguy hiểm</h3>
        </div>
        <div class="card-content">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem;background:color-mix(in srgb,var(--destructive) 5%,transparent);border-radius:var(--radius-md);border:1px solid color-mix(in srgb,var(--destructive) 20%,transparent);">
                <div>
                    <div style="font-weight:500;color:var(--destructive);">Xóa lớp học</div>
                    <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Xóa lớp và toàn bộ dữ liệu liên quan. Hành động này không thể hoàn tác.</div>
                </div>
                <form method="POST" action="{{ route('teacher.classes.destroy', $class) }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa lớp này? Hành động không thể hoàn tác!')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-destructive btn-sm">Xóa lớp</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // Tab switching
    document.querySelectorAll('.tab-btn-c').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn-c').forEach(function(b) { b.classList.remove('active'); });
            document.querySelectorAll('.content-panel').forEach(function(p) { p.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById('panel-' + btn.getAttribute('data-tab')).classList.add('active');
        });
    });

    // Search filter
    var searchInput = document.getElementById('stu-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = (this.value || '').toLowerCase();
            document.querySelectorAll('.stu-row').forEach(function(row) {
                var name = row.getAttribute('data-name') || '';
                row.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }

    // Copy code
    window.copyCode = function() {
        var code = {{ Js::from($class->code) }};
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(function() {
                showToast('Đã sao chép mã lớp: ' + code);
            });
        }
    };

    function showToast(msg) {
        var tc = document.getElementById('toast-container');
        if (!tc) return;
        var e = document.createElement('div');
        e.className = 'toast toast-success';
        e.innerHTML = '<span>✅</span><span>' + msg + '</span>';
        tc.appendChild(e);
        requestAnimationFrame(function() { e.classList.add('show'); });
        setTimeout(function() {
            e.classList.remove('show');
            setTimeout(function() { e.remove(); }, 300);
        }, 3000);
    }
})();
</script>
@endpush
