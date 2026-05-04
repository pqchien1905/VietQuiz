{{-- Teacher: class-detail --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
    /* ── Page Layout ── */
    .page-class-detail { display: flex; flex-direction: column; gap: 1.25rem; }

    /* ── Header Section ── */
    .cd-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .cd-header-left { display: flex; align-items: center; gap: 1rem; }
    .cd-class-icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: var(--radius-lg);
        background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
        color: #fff;
        font-size: 1.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgb(59 130 246 / 0.3);
    }
    .cd-class-info h1 {
        font-size: var(--text-xl);
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 0.375rem;
    }
    .cd-class-badges { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .cd-actions { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }

    /* ── Stats Row ── */
    .cd-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.875rem;
    }
    .cd-stat {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        padding: 1.125rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
        transition: box-shadow var(--transition-fast), transform var(--transition-fast);
    }
    .cd-stat:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
    .cd-stat-label { font-size: var(--text-xs); color: var(--muted-foreground); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    .cd-stat-value { font-size: var(--text-2xl); font-weight: 800; line-height: 1; }
    .cd-stat-sub { font-size: var(--text-xs); color: var(--muted-foreground); margin-top: 0.25rem; }

    /* ── Tab Navigation ── */
    .cd-tabs {
        display: flex;
        align-items: center;
        gap: 0;
        border-bottom: 1px solid var(--border);
        background: var(--card);
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        padding: 0 0.5rem;
        overflow: hidden;
    }
    .cd-tab {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.875rem 1.125rem;
        border: none;
        background: none;
        border-bottom: 2px solid transparent;
        color: var(--muted-foreground);
        cursor: pointer;
        font-size: var(--text-sm);
        font-weight: 600;
        transition: all var(--transition-fast);
        white-space: nowrap;
        position: relative;
    }
    .cd-tab:hover { color: var(--foreground); background: var(--muted); border-radius: var(--radius-md) var(--radius-md) 0 0; }
    .cd-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
    .cd-tab svg { width: 1rem; height: 1rem; flex-shrink: 0; }
    .cd-tab-badge {
        background: var(--muted);
        color: var(--muted-foreground);
        border-radius: 9999px;
        padding: 0.125rem 0.5rem;
        font-size: 0.7rem;
        font-weight: 700;
        transition: all var(--transition-fast);
    }
    .cd-tab.active .cd-tab-badge { background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--primary); }

    /* ── Tab Panels ── */
    .cd-panel { display: none; }
    .cd-panel.active { display: block; }

    /* ── Toolbar ── */
    .cd-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .cd-toolbar-left { display: flex; align-items: center; gap: 0.75rem; flex: 1; }
    .cd-toolbar-right { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }

    /* ── Search ── */
    .cd-search {
        position: relative;
        flex: 1;
        max-width: 280px;
    }
    .cd-search svg { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--muted-foreground); pointer-events: none; }
    .cd-search input { padding-left: 2.25rem; width: 100%; }

    /* ── Student Table ── */
    .cd-table-wrap { overflow-x: auto; border-radius: var(--radius-lg); }
    .cd-table { width: 100%; border-collapse: collapse; }
    .cd-table thead th {
        padding: 0.625rem 1rem;
        text-align: left;
        font-size: var(--text-xs);
        font-weight: 700;
        color: var(--muted-foreground);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: var(--muted);
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }
    .cd-table thead th:first-child { border-radius: var(--radius-md) 0 0 0; }
    .cd-table thead th:last-child { border-radius: 0 var(--radius-md) 0 0; }
    .cd-table tbody tr {
        border-bottom: 1px solid var(--border);
        transition: background var(--transition-fast);
    }
    .cd-table tbody tr:last-child { border-bottom: none; }
    .cd-table tbody tr:hover { background: var(--muted); }
    .cd-table tbody td { padding: 0.75rem 1rem; vertical-align: middle; }

    /* Student Avatar */
    .stu-avatar {
        width: 2.125rem;
        height: 2.125rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--text-xs);
        font-weight: 700;
        flex-shrink: 0;
    }

    /* Grade badge */
    .grade-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        font-size: var(--text-xs);
        font-weight: 800;
    }

    /* ── Progress Cards ── */
    .cd-progress-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
    .cd-list-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 0; border-bottom: 1px solid var(--border); }
    .cd-list-item:last-child { border-bottom: none; }
    .cd-list-rank { width: 1.75rem; font-size: var(--text-base); text-align: center; flex-shrink: 0; }
    .cd-list-info { flex: 1; min-width: 0; }
    .cd-list-name { font-size: var(--text-sm); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cd-list-sub { font-size: var(--text-xs); color: var(--muted-foreground); }

    /* ── Dist Bar ── */
    .dist-row { display: flex; align-items: center; gap: 0.625rem; padding: 0.375rem 0; }
    .dist-label { font-size: var(--text-xs); font-weight: 600; width: 5.5rem; flex-shrink: 0; color: var(--muted-foreground); }
    .dist-bar { flex: 1; height: 0.5rem; background: var(--muted); border-radius: 9999px; overflow: hidden; }
    .dist-fill { height: 100%; border-radius: 9999px; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
    .dist-count { font-size: var(--text-xs); font-weight: 700; width: 1.5rem; text-align: right; flex-shrink: 0; }

    /* ── Settings ── */
    .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .danger-card {
        background: color-mix(in srgb, var(--destructive) 3%, transparent);
        border: 1px solid color-mix(in srgb, var(--destructive) 15%, transparent);
        border-radius: var(--radius-xl);
        padding: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    /* ── Toast ── */
    #toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: 0.5rem; pointer-events: none; }
    .toast {
        display: flex; align-items: center; gap: 0.625rem;
        padding: 0.75rem 1.125rem;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        font-size: var(--text-sm);
        font-weight: 500;
        pointer-events: all;
        opacity: 0; transform: translateX(1rem);
        transition: all var(--transition);
    }
    .toast.show { opacity: 1; transform: translateX(0); }
    .toast-success { border-left: 3px solid var(--success); }
    .toast-warning { border-left: 3px solid var(--warning); }
    .toast-error { border-left: 3px solid var(--destructive); }

    /* ── Import / Add forms ── */
    .cd-inline-form {
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid var(--border);
        background: var(--muted);
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .cd-inline-form-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

    /* ── Add Student Modal ── */
    .asm-tabs { display: flex; gap: 0.5rem; border-bottom: 1px solid var(--border); margin-bottom: 1.25rem; padding-bottom: 0.75rem; flex-wrap: wrap; }
    .asm-tab {
        padding: 0.5rem 0.875rem;
        border: none;
        background: transparent;
        border-radius: var(--radius-md);
        color: var(--muted-foreground);
        cursor: pointer;
        font-size: var(--text-sm);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all var(--transition-fast);
    }
    .asm-tab:hover { color: var(--foreground); background: var(--muted); }
    .asm-tab.active { color: var(--primary-foreground); background: var(--primary); }
    .asm-panel { display: none; }
    .asm-panel.active { display: block; }
    .asm-file-hint { font-size: var(--text-xs); color: var(--muted-foreground); margin-bottom: 0.75rem; }
    .asm-file-hint code { background: var(--muted); padding: 0.125rem 0.375rem; border-radius: var(--radius-sm); font-size: var(--text-xs); }
    .asm-or { display: flex; align-items: center; gap: 0.75rem; margin: 0.75rem 0; color: var(--muted-foreground); font-size: var(--text-xs); }
    .asm-or::before, .asm-or::after { content: ''; flex: 1; height: 1px; background: var(--border); }
    .asm-link-box { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: var(--muted); border-radius: var(--radius-md); font-size: var(--text-sm); word-break: break-all; }
    .asm-link-box span { flex: 1; color: var(--foreground); }

    /* ── Empty state ── */
    .cd-empty { text-align: center; padding: 3rem 1.5rem; color: var(--muted-foreground); }
    .cd-empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }

    /* ── Responsive ── */
    @media (max-width: 900px) {
        .cd-stats { grid-template-columns: repeat(2, 1fr); }
        .cd-progress-grid { grid-template-columns: 1fr; }
        .settings-grid { grid-template-columns: 1fr; }
        .cd-tabs { overflow-x: auto; }
    }
    @media (max-width: 600px) {
        .cd-stats { grid-template-columns: repeat(2, 1fr); }
        .cd-header { flex-direction: column; }
        .cd-actions { width: 100%; }
        .cd-actions .btn { flex: 1; justify-content: center; }
    }
</style>
@endpush

@section('content')
<?php
    $gradeColors = [
        'A' => 'var(--success)',
        'B' => '#22d3ee',
        'C' => 'var(--warning)',
        'D' => '#fb923c',
        'F' => 'var(--destructive)',
    ];
    $gradeBgColors = [
        'A' => 'color-mix(in srgb, var(--success) 12%, transparent)',
        'B' => 'color-mix(in srgb, #22d3ee 12%, transparent)',
        'C' => 'color-mix(in srgb, var(--warning) 12%, transparent)',
        'D' => 'color-mix(in srgb, #fb923c 12%, transparent)',
        'F' => 'color-mix(in srgb, var(--destructive) 12%, transparent)',
    ];
    $avatarColors = ['#3b82f6','#ef4444','#22c55e','#f97316','#a855f7','#06b6d4','#ec4899','#eab308'];
    $classColor = $avatarColors[abs(crc32($class->name)) % count($avatarColors)];
    $studentColors = $avatarColors;

    // Distribution bar widths
    $totalWithScore = $dist['excellent'] + $dist['good'] + $dist['average'] + $dist['weak'];
    $distWidths = [];
    foreach (['excellent', 'good', 'average', 'weak'] as $key) {
        $distWidths[$key] = $totalWithScore > 0 ? round($dist[$key] / $totalWithScore * 100) : 0;
    }
    $distLabels = [
        'excellent' => ['Giỏi ≥90%', 'var(--success)'],
        'good'      => ['Khá 70-89%', '#22d3ee'],
        'average'   => ['TB 50-69%', 'var(--warning)'],
        'weak'      => ['Yếu <50%', 'var(--destructive)'],
    ];
?>

<div class="page-class-detail">

    <!-- ── Header ── -->
    <div class="cd-header stagger-children">
        <div class="cd-header-left">
            <div class="cd-class-icon">{{ mb_substr($class->name, 0, 1) }}</div>
            <div class="cd-class-info">
                <h1>{{ $class->name }}</h1>
                <div class="cd-class-badges">
                    <span class="badge badge-primary" style="font-size:var(--text-xs);">
                        {{ $studentCount }} học sinh
                    </span>
                    @if($class->subject)
                    <span class="badge badge-default" style="font-size:var(--text-xs);">{{ $class->subject }}</span>
                    @endif
                    <span class="badge badge-outline" style="font-size:var(--text-xs);">
                        Mã: <strong>{{ $class->code }}</strong>
                    </span>
                    @if($class->status === 'archived')
                    <span class="badge badge-destructive" style="font-size:var(--text-xs);">Đã lưu trữ</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="cd-actions">
            <button class="btn btn-outline btn-sm" onclick="copyCode()" style="gap:0.375rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Sao chép mã
            </button>
            <button class="btn btn-outline btn-sm" onclick="openAddStudentModal('asm-link')" style="gap:0.375rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                Chia sẻ lớp
            </button>
            <a href="{{ route('teacher.quiz-create', ['class_id' => $class->id]) }}" class="btn btn-primary btn-sm" style="gap:0.375rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Giao bài kiểm tra
            </a>
        </div>
    </div>

    <!-- ── Stats Row ── -->
    <div class="cd-stats stagger-children">
        <div class="cd-stat">
            <div class="cd-stat-label">Học sinh</div>
            <div class="cd-stat-value" style="color:var(--primary);">{{ $studentCount }}</div>
            <div class="cd-stat-sub">đã tham gia</div>
        </div>
        <div class="cd-stat">
            <div class="cd-stat-label">Điểm trung bình</div>
            <div class="cd-stat-value" style="{{ $classAvg ? 'color:var(--success)' : 'color:var(--muted-foreground)' }}">
                {{ $classAvg ? round($classAvg, 1) . '%' : '—' }}
            </div>
            <div class="cd-stat-sub">của lớp</div>
        </div>
        <div class="cd-stat">
            <div class="cd-stat-label">Bài thi đã giao</div>
            <div class="cd-stat-value" style="color:#a855f7;">{{ $quizzes->count() }}</div>
            <div class="cd-stat-sub">đã tạo</div>
        </div>
        <div class="cd-stat">
            <div class="cd-stat-label">Tỷ lệ hoàn thành</div>
            <div class="cd-stat-value" style="color:#06b6d4;">{{ $completionRate }}%</div>
            <div class="cd-stat-sub">bài đã nộp</div>
        </div>
    </div>

    <!-- ── Tabs ── -->
    <div class="cd-tabs stagger-children">
        <button class="cd-tab active" data-tab="students">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Học sinh
            <span class="cd-tab-badge">{{ $studentCount }}</span>
        </button>
        <button class="cd-tab" data-tab="quizzes">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Bài kiểm tra
            <span class="cd-tab-badge">{{ $quizzes->count() }}</span>
        </button>
        <button class="cd-tab" data-tab="progress">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Tiến độ
        </button>
        <button class="cd-tab" data-tab="settings">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
            Cài đặt
        </button>
    </div>

    <!-- ══════════════════════════════════════
         TAB: HỌC SINH
    ══════════════════════════════════════ -->
    <div class="cd-panel active" id="panel-students">
        <div class="card">
            <!-- Toolbar -->
            <div style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;border-bottom:1px solid var(--border);">
                <h3 style="font-size:var(--text-base);font-weight:700;">Danh sách học sinh</h3>
                <div style="display:flex;gap:.5rem;align-items:center;">
                    <div class="cd-search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="search" class="input" placeholder="Tìm học sinh..." id="stu-search" style="font-size:var(--text-sm);height:2rem;" />
                    </div>
                    <a href="{{ route('teacher.classes.export', $class) }}" class="btn btn-outline btn-sm" style="gap:0.375rem;" title="Xuất danh sách học sinh ra file CSV">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Xuất Excel
                    </a>
                    <button class="btn btn-outline btn-sm" onclick="toggleForm('add-notify-form')" style="gap:0.375rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        Gửi thông báo
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="openAddStudentModal()" style="gap:0.375rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Thêm HS
                    </button>
                </div>
            </div>

            <!-- Send Notification Form -->
            <div id="add-notify-form" class="cd-inline-form" style="display:none;">
                <p style="font-size:var(--text-sm);font-weight:600;margin:0;">Gửi thông báo cho {{ $studentCount }} học sinh trong lớp</p>
                <form method="POST" action="{{ route('teacher.classes.notify', $class) }}" class="cd-inline-form-row" style="margin:0;padding:0;border:none;background:none;flex-wrap:wrap;">
                    @csrf
                    <input type="text" name="title" class="input" placeholder="Tiêu đề thông báo..." required style="max-width:220px;font-size:var(--text-sm);height:2rem;" />
                    <input type="text" name="body" class="input" placeholder="Nội dung thông báo..." required style="flex:1;min-width:200px;font-size:var(--text-sm);height:2rem;" />
                    <button type="submit" class="btn btn-primary btn-sm">Gửi ngay</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="hideForm('add-notify-form')">Hủy</button>
                </form>
            </div>

            <!-- Table -->
            <div class="cd-table-wrap">
                <table class="cd-table">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            <th style="text-align:right;">Điểm TB</th>
                            <th style="text-align:center;">Đã làm</th>
                            <th style="text-align:center;">Xếp loại</th>
                            <th style="text-align:right;"></th>
                        </tr>
                    </thead>
                    <tbody id="stu-tbody">
                        @forelse($studentGrades as $idx => $student)
                        <?php
                            $c = $studentColors[$idx % count($studentColors)];
                            $initials = collect(explode(' ', $student->name))->filter()->map(fn($w) => $w[0])->slice(-2)->implode('');
                        ?>
                        <tr class="stu-row" data-name="{{ strtolower($student->name) }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:0.75rem;">
                                    <div class="stu-avatar" style="background:color-mix(in srgb, {{ $c }} 15%, transparent);color:{{ $c }};">
                                        {{ $initials }}
                                    </div>
                                    <span style="font-weight:600;font-size:var(--text-sm);">{{ $student->name }}</span>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                @if($student->avg_pct !== null)
                                <span style="font-weight:800;font-size:var(--text-base);color:{{ $gradeColors[$student->grade_letter] ?? 'inherit' }};">{{ $student->avg_pct }}%</span>
                                @else
                                <span style="color:var(--muted-foreground);">—</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <span class="cd-tab-badge" style="font-size:var(--text-xs);">{{ $student->completed_count }}</span>
                            </td>
                            <td style="text-align:center;">
                                @if($student->avg_pct !== null)
                                <span class="grade-badge" style="background:{{ $gradeBgColors[$student->grade_letter] ?? 'var(--muted)' }};color:{{ $gradeColors[$student->grade_letter] ?? 'gray' }};">
                                    {{ $student->grade_letter }}
                                </span>
                                @else
                                <span style="color:var(--muted-foreground);font-size:var(--text-sm);">—</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <form method="POST" action="{{ route('teacher.classes.remove-student', [$class, $student->id]) }}" data-confirm="Xóa {{ $student->name }} khỏi lớp?" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--destructive);width:2rem;height:2rem;padding:0;" title="Xóa khỏi lớp">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="cd-empty">
                                    <div class="cd-empty-icon">🎓</div>
                                    <p style="font-size:var(--text-sm);font-weight:600;margin-bottom:.25rem;color:var(--foreground);">Chưa có học sinh nào</p>
                                    <p style="font-size:var(--text-xs);">Thêm học sinh bằng email hoặc nhập file CSV để bắt đầu</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         TAB: BÀI KIỂM TRA
    ══════════════════════════════════════ -->
    <div class="cd-panel" id="panel-quizzes">
        <div class="card">
            <div style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);">
                <h3 style="font-size:var(--text-base);font-weight:700;">Bài kiểm tra đã giao</h3>
                <a href="{{ route('teacher.quiz-create', ['class_id' => $class->id]) }}" class="btn btn-primary btn-sm" style="gap:0.375rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tạo bài mới
                </a>
            </div>

            @if($quizzes->isEmpty())
            <div class="cd-empty">
                <div class="cd-empty-icon">📝</div>
                <p style="font-size:var(--text-sm);font-weight:600;margin-bottom:.25rem;color:var(--foreground);">Chưa có bài kiểm tra nào</p>
                <p style="font-size:var(--text-xs);">Tạo bài kiểm tra và giao cho lớp này</p>
            </div>
            @else
            <div class="cd-table-wrap">
                <table class="cd-table">
                    <thead>
                        <tr>
                            <th>Đề thi</th>
                            <th>Ngày tạo</th>
                            <th style="text-align:center;">Câu hỏi</th>
                            <th style="text-align:right;">Đã nộp</th>
                            <th style="text-align:right;">Điểm TB</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quizzes as $quiz)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <div style="width:2rem;height:2rem;border-radius:var(--radius-sm);background:color-mix(in srgb, var(--primary) 10%, transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    </div>
                                    <span style="font-weight:600;font-size:var(--text-sm);">{{ $quiz->title }}</span>
                                </div>
                            </td>
                            <td style="font-size:var(--text-sm);color:var(--muted-foreground);white-space:nowrap;">{{ $quiz->created_at->format('d/m/Y') }}</td>
                            <td style="text-align:center;font-size:var(--text-sm);">{{ $quiz->questions_count ?? '—' }}</td>
                            <td style="text-align:right;font-size:var(--text-sm);">{{ $quiz->submitted_count ?? 0 }}</td>
                            <td style="text-align:right;">
                                @if($quiz->avg_score !== null)
                                <span style="font-weight:800;font-size:var(--text-sm);color:var(--success);">{{ $quiz->avg_score }}%</span>
                                @else
                                <span style="color:var(--muted-foreground);font-size:var(--text-sm);">—</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <a href="{{ route('teacher.quiz-detail', $quiz) }}" class="btn btn-ghost btn-sm" style="font-size:var(--text-xs);">Xem</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <!-- ══════════════════════════════════════
         TAB: TIẾN ĐỘ
    ══════════════════════════════════════ -->
    <div class="cd-panel" id="panel-progress">
        <!-- Mini stats -->
        <?php
            $maxScore = $studentGrades->filter(fn($s) => $s->avg_pct !== null)->max('avg_pct');
            $minScore = $studentGrades->filter(fn($s) => $s->avg_pct !== null)->min('avg_pct');
        ?>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.875rem;margin-bottom:1.25rem;">
            <div class="cd-stat">
                <div class="cd-stat-label">Điểm cao nhất</div>
                <div class="cd-stat-value" style="color:var(--success);">{{ $maxScore ? $maxScore . '%' : '—' }}</div>
            </div>
            <div class="cd-stat">
                <div class="cd-stat-label">Điểm thấp nhất</div>
                <div class="cd-stat-value" style="color:var(--destructive);">{{ $minScore ? $minScore . '%' : '—' }}</div>
            </div>
            <div class="cd-stat">
                <div class="cd-stat-label">Giỏi (≥90%)</div>
                <div class="cd-stat-value" style="color:var(--success);">{{ $dist['excellent'] }}</div>
            </div>
            <div class="cd-stat">
                <div class="cd-stat-label">Cần cải thiện (&lt;60%)</div>
                <div class="cd-stat-value" style="color:var(--warning);">{{ $dist['weak'] + $dist['average'] }}</div>
            </div>
        </div>

        <!-- Two column layout -->
        <div class="cd-progress-grid">

            <!-- Left: Distribution + Top Students -->
            <div style="display:flex;flex-direction:column;gap:1.25rem;">
                <!-- Grade distribution -->
                <div class="card">
                    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);">
                        <h3 style="font-size:var(--text-sm);font-weight:700;">Phân bố điểm</h3>
                    </div>
                    <div style="padding:1rem 1.25rem;">
                        @foreach (['excellent', 'good', 'average', 'weak'] as $key)
                        <?php [$label, $color] = $distLabels[$key]; ?>
                        <div class="dist-row">
                            <div class="dist-label" style="color:{{ $color }};">{{ $label }}</div>
                            <div class="dist-bar">
                                <div class="dist-fill" style="width:{{ $distWidths[$key] }}%;background:{{ $color }};"></div>
                            </div>
                            <div class="dist-count" style="color:{{ $color }};">{{ $dist[$key] }}</div>
                        </div>
                        @endforeach
                        @if($totalWithScore === 0)
                        <p style="text-align:center;font-size:var(--text-sm);color:var(--muted-foreground);padding:.5rem 0;">Chưa có dữ liệu điểm</p>
                        @endif
                    </div>
                </div>

                <!-- Top Students -->
                <div class="card">
                    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.5rem;">
                        <span>🥇</span>
                        <h3 style="font-size:var(--text-sm);font-weight:700;">Top 5 Học sinh</h3>
                    </div>
                    <div style="padding:0.5rem 1.25rem;">
                        @if($topStudents->isEmpty())
                        <div style="text-align:center;padding:1.5rem;color:var(--muted-foreground);font-size:var(--text-sm);">Chưa có dữ liệu</div>
                        @else
                        @foreach($topStudents as $i => $student)
                        <div class="cd-list-item">
                            <div class="cd-list-rank">
                                @if($i === 0) 🥇
                                @elseif($i === 1) 🥈
                                @elseif($i === 2) 🥉
                                @else <span style="font-weight:700;color:var(--muted-foreground);font-size:var(--text-sm);">{{ $i + 1 }}</span>
                                @endif
                            </div>
                            <div class="cd-list-info">
                                <div class="cd-list-name">{{ $student->name }}</div>
                                <div class="cd-list-sub">{{ $student->completed_count }} bài đã làm</div>
                            </div>
                            <span style="font-weight:800;font-size:var(--text-base);color:var(--success);">{{ $student->avg_pct }}%</span>
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right: Weak Students + Summary -->
            <div style="display:flex;flex-direction:column;gap:1.25rem;">
                <!-- Need support -->
                <div class="card">
                    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.5rem;">
                        <span>⚠️</span>
                        <h3 style="font-size:var(--text-sm);font-weight:700;">Cần hỗ trợ (&lt;60%)</h3>
                    </div>
                    <div style="padding:0.5rem 1.25rem;">
                        @if($weakStudents->isEmpty())
                        <div style="text-align:center;padding:1.5rem;">
                            <div style="font-size:2rem;margin-bottom:.5rem;">🎉</div>
                            <p style="font-size:var(--text-sm);font-weight:600;color:var(--success);">Tất cả học sinh đều đạt yêu cầu!</p>
                        </div>
                        @else
                        @foreach($weakStudents as $student)
                        <div class="cd-list-item">
                            <div class="cd-list-rank">⚠️</div>
                            <div class="cd-list-info">
                                <div class="cd-list-name">{{ $student->name }}</div>
                                <div class="cd-list-sub">{{ $student->completed_count }} bài đã làm</div>
                            </div>
                            <span style="font-weight:800;font-size:var(--text-base);color:var(--destructive);">{{ $student->avg_pct }}%</span>
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>

                <!-- Class summary card -->
                <div class="card" style="background:linear-gradient(135deg, color-mix(in srgb, var(--primary) 6%, transparent), color-mix(in srgb, #6366f1 6%, transparent));border-color:color-mix(in srgb, var(--primary) 20%, transparent);">
                    <div style="padding:1.25rem;">
                        <h3 style="font-size:var(--text-sm);font-weight:700;margin-bottom:1rem;">Tổng quan lớp</h3>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;">Tổng HS</div>
                                <div style="font-size:var(--text-xl);font-weight:800;">{{ $studentCount }}</div>
                            </div>
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;">Điểm TB</div>
                                <div style="font-size:var(--text-xl);font-weight:800;color:var(--success);">{{ $classAvg ? round($classAvg, 1) . '%' : '—' }}</div>
                            </div>
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;">Bài thi</div>
                                <div style="font-size:var(--text-xl);font-weight:800;color:#a855f7;">{{ $quizzes->count() }}</div>
                            </div>
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.25rem;">Hoàn thành</div>
                                <div style="font-size:var(--text-xl);font-weight:800;color:#06b6d4;">{{ $completionRate }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         TAB: CÀI ĐẶT
    ══════════════════════════════════════ -->
    <div class="cd-panel" id="panel-settings">
        <form method="POST" action="{{ route('teacher.classes.update', $class) }}">
            @csrf @method('PUT')
            <div class="card" style="margin-bottom:1.25rem;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);">
                    <h3 style="font-size:var(--text-base);font-weight:700;">Thông tin lớp học</h3>
                </div>
                <div style="padding:1.25rem;display:flex;flex-direction:column;gap:1rem;">
                    <div class="settings-grid">
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
                        <textarea name="description" class="input @error('description') input-error @enderror" style="min-height:4rem;resize:vertical;">{{ old('description', $class->description) }}</textarea>
                        @error('description') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="settings-grid">
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
                        <button type="submit" class="btn btn-primary" style="gap:0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 8 8"/></svg>
                            Lưu thay đổi
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Archive / Restore -->
        <div class="card" style="margin-bottom:1.25rem;">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);">
                <h3 style="font-size:var(--text-base);font-weight:700;">Trạng thái lớp học</h3>
            </div>
            <div style="padding:1.25rem;">
                @if($class->status === 'archived')
                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:0.625rem;">
                        <span style="font-size:1.25rem;">📦</span>
                        <div>
                            <div style="font-weight:600;font-size:var(--text-sm);color:var(--muted-foreground);">Lớp đang được lưu trữ</div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Lớp không hiển thị với học sinh. Khôi phục để tiếp tục sử dụng.</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('teacher.classes.restore', $class) }}" style="flex-shrink:0;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" style="gap:0.375rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.1"/></svg>
                            Khôi phục lớp
                        </button>
                    </form>
                </div>
                @else
                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:0.625rem;">
                        <span style="font-size:1.25rem;">✅</span>
                        <div>
                            <div style="font-weight:600;font-size:var(--text-sm);">Lớp đang hoạt động</div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Lớp đang mở và học sinh có thể tham gia.</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('teacher.classes.archive', $class) }}" data-confirm="Lưu trữ lớp này? Lớp sẽ không hiển thị với học sinh nhưng dữ liệu được giữ nguyên." data-confirm-destructive="false" style="flex-shrink:0;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" style="gap:0.375rem;color:var(--muted-foreground);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                            Lưu trữ lớp
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);">
                <h3 style="font-size:var(--text-base);font-weight:700;color:var(--destructive);">Vùng nguy hiểm</h3>
            </div>
            <div style="padding:1.25rem;">
                <div class="danger-card">
                    <div>
                        <div style="font-weight:600;font-size:var(--text-sm);color:var(--destructive);margin-bottom:.25rem;">Xóa lớp học</div>
                        <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Xóa lớp và toàn bộ dữ liệu liên quan. Hành động này không thể hoàn tác.</div>
                    </div>
                    <form method="POST" action="{{ route('teacher.classes.destroy', $class) }}" data-confirm="Bạn chắc chắn muốn xóa lớp này? Hành động không thể hoàn tác!" style="flex-shrink:0;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-destructive btn-sm">Xóa lớp</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ── Modal: Thêm học sinh ── -->
<div class="modal-overlay" id="add-student-modal">
    <div class="modal" style="max-width:34rem;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Thêm học sinh</h3>
                <p class="modal-desc">Thêm học sinh vào lớp "{{ $class->name }}" bằng email, link tham gia hoặc file.</p>
            </div>
            <button class="modal-close" onclick="closeAddStudentModal()" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body" style="padding:1.25rem;">
            <!-- Tab buttons -->
            <div class="asm-tabs">
                <button class="asm-tab active" data-tab="asm-email" type="button" title="Chỉ thêm những tài khoản học sinh đã tồn tại trong hệ thống.">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Nhập email
                </button>
                <button class="asm-tab" data-tab="asm-link" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    Link tham gia
                </button>
                <button class="asm-tab" data-tab="asm-file" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="16" y2="16"/><line x1="12" y1="8" x2="12" y2="16"/></svg>
                    Import file
                </button>
            </div>

            <!-- Tab: Nhập email -->
            <div class="asm-panel active" id="panel-asm-email">
                <form method="POST" action="{{ route('teacher.students.invite-email') }}">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $class->id }}" />
                    <div class="form-group">
                        <label class="label label-required">Email học sinh</label>
                        <textarea name="emails_raw" class="input @error('emails') input-error @enderror @error('emails_raw') input-error @enderror" rows="7" placeholder="nguyenvana@example.com&#10;tranthib@example.com&#10;leminhc@example.com" required style="resize:vertical;font-family:var(--font-mono);font-size:var(--text-xs);">{{ old('emails_raw') }}</textarea>
                        @error('emails') <span class="error-message">{{ $message }}</span> @enderror
                        @error('emails_raw') <span class="error-message">{{ $message }}</span> @enderror
                        <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.5rem;">
                            Mỗi email một dòng. Hệ thống chỉ thêm các tài khoản đã đăng ký với vai trò học sinh.
                        </p>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem;">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="closeAddStudentModal()">Hủy</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Thêm vào lớp
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab: Link tham gia -->
            <div class="asm-panel" id="panel-asm-link">
                <div class="form-group">
                    <label class="label">Link tham gia lớp</label>
                    <div class="asm-link-box">
                        <span id="asm-join-link">{{ url('/student/join/' . strtolower($class->code)) }}</span>
                        <button type="button" class="btn btn-outline btn-sm" onclick="copyJoinLink()">Sao chép</button>
                    </div>
                    <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.5rem;">
                        Học sinh đăng nhập tài khoản học sinh rồi mở link này để tự tham gia lớp. Nếu đang dùng tài khoản giáo viên, hệ thống sẽ hiện hướng dẫn thay vì chuyển về dashboard.
                    </p>
                </div>
                <form method="POST" action="{{ route('teacher.students.invite-link', $class) }}" style="margin-top:1rem;">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">
                        Tạo mã mời mới
                    </button>
                </form>
            </div>

            <!-- Tab: Thêm nhanh bằng file Excel -->
            <div class="asm-panel" id="panel-asm-file">
                <p class="asm-file-hint">
                    Chỉ thêm những tài khoản <strong>học sinh đã tồn tại</strong> trong hệ thống.
                    File Excel cần có cột <strong>Email</strong> (hoặc <strong>Họ tên</strong>).
                </p>
                <a href="{{ route('teacher.classes.template', $class) }}" class="btn btn-ghost btn-sm" style="margin-bottom:.875rem;gap:.375rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Tải file mẫu Excel
                </a>
                <form method="POST" action="{{ route('teacher.classes.import', $class) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="label">Chọn file Excel (.xlsx)</label>
                        <input type="file" name="students_file" accept=".xlsx,.csv,.txt" class="input @error('students_file') input-error @enderror" required style="font-size:var(--text-sm);" />
                        @error('students_file') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem;">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="closeAddStudentModal()">Hủy</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Thêm vào lớp
                        </button>
                    </div>
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

    // ── Tab switching (page tabs)
    document.querySelectorAll('.cd-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cd-tab').forEach(function(b) { b.classList.remove('active'); });
            document.querySelectorAll('.cd-panel').forEach(function(p) { p.classList.remove('active'); });
            btn.classList.add('active');
            var panel = document.getElementById('panel-' + btn.getAttribute('data-tab'));
            if (panel) panel.classList.add('active');
        });
    });

    // ── Add Student Modal
    function activateAsmTab(tabId) {
        tabId = tabId || 'asm-email';
        document.querySelectorAll('.asm-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.asm-panel').forEach(function(p) { p.classList.remove('active'); });
        var tab = document.querySelector('.asm-tab[data-tab="' + tabId + '"]');
        var panel = document.getElementById('panel-' + tabId);
        if (tab) tab.classList.add('active');
        if (panel) panel.classList.add('active');
    }

    window.openAddStudentModal = function(tabId) {
        var modal = document.getElementById('add-student-modal');
        if (modal) {
            modal.classList.add('open');
            activateAsmTab(tabId || 'asm-email');
        }
    };
    window.closeAddStudentModal = function() {
        var modal = document.getElementById('add-student-modal');
        if (modal) modal.classList.remove('open');
    };

    // ASM tab switching
    document.querySelectorAll('.asm-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            activateAsmTab(btn.getAttribute('data-tab'));
        });
    });

    // Modal overlay close on click outside
    var asmModal = document.getElementById('add-student-modal');
    if (asmModal) {
        asmModal.addEventListener('click', function(e) {
            if (e.target === asmModal) closeAddStudentModal();
        });
    }

    // ── Search filter
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

    // ── Copy code
    window.copyCode = function() {
        var code = {{ Js::from($class->code) }};
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(function() {
                showToast('Đã sao chép mã lớp: ' + code);
            });
        }
    };

    // ── Copy join link
    window.copyJoinLink = function() {
        var link = {{ Js::from(url('/student/join/' . strtolower($class->code))) }};
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(function() {
                showToast('Đã sao chép link mời: ' + link);
            });
        } else {
            var input = document.createElement('textarea');
            input.value = link;
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showToast('Đã sao chép link mời: ' + link);
        }
    };

    // ── Toggle / hide forms
    window.toggleForm = function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.display = el.style.display === 'none' ? 'flex' : 'none';
    };
    window.hideForm = function(id) {
        var el = document.getElementById(id);
        if (el) el.style.display = 'none';
    };

    // ── Toast
    function showToast(msg, type) {
        type = type || 'success';
        var tc = document.getElementById('toast-container');
        if (!tc) return;
        var e = document.createElement('div');
        e.className = 'toast toast-' + type;
        e.innerHTML = '<span>' + (type === 'warning' ? '⚠️' : '✅') + '</span><span>' + msg + '</span>';
        tc.appendChild(e);
        requestAnimationFrame(function() { e.classList.add('show'); });
        setTimeout(function() {
            e.classList.remove('show');
            setTimeout(function() { e.remove(); }, 300);
        }, 4000);
    }

    // Auto-show flash messages as toasts
    @if(session('success'))
    showToast({{ Js::from(session('success')) }}, 'success');
    @endif
    @if(session('warning'))
    showToast({{ Js::from(session('warning')) }}, 'warning');
    @endif
    @if(session('error'))
    showToast({{ Js::from(session('error')) }}, 'error');
    @endif

    // ── Escape to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var asmModal = document.getElementById('add-student-modal');
            if (asmModal) asmModal.classList.remove('open');
            document.getElementById('add-notify-form') && (document.getElementById('add-notify-form').style.display = 'none');
        }
    });
})();
</script>
@endpush
