{{-- Teacher: analytics --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
    $periodLabels = ['week' => 'Tuần này', 'month' => 'Tháng này', 'quarter' => 'Quý này', 'year' => 'Năm nay', 'custom' => 'Tùy chọn'];
    $dateQuery = $period === 'custom' ? ['start_date' => $range['start_date'], 'end_date' => $range['end_date']] : [];
    $exportQuery = array_filter(array_merge(['period' => $period, 'class_id' => $classId], $dateQuery));
    $maxClassScore = max(100, $scoreByClass->max(fn ($item) => $item['avg_score'] ?? 0));
    $maxWeeklyScore = max(100, collect($weeklyTrend)->max('val') ?? 0);
    $maxActivity = max(1, collect($activityTrend)->max('count') ?? 0);
@endphp

@push('styles')
<style>
.analytics-header { display:grid; grid-template-columns:minmax(0, 1fr) minmax(24rem, 42rem); gap:1.5rem; align-items:start; }
.analytics-heading h1 { margin-bottom:.35rem; }
.analytics-toolbar { display:grid; gap:.5rem; justify-self:end; width:100%; max-width:42rem; }
.analytics-filter { display:grid; grid-template-columns:1fr; gap:.5rem; }
.analytics-filter-row { display:grid; grid-template-columns:minmax(0, 1fr) auto auto; gap:.5rem; align-items:center; }
.date-filter-row { display:grid; grid-template-columns:1fr 1fr auto; gap:.5rem; align-items:end; }
.date-field { display:grid; gap:.25rem; }
.date-field label { color:var(--muted-foreground); font-size:var(--text-xs); font-weight:700; }
.period-tabs { display:flex; gap:.375rem; padding:.25rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--muted); }
.period-tab { border:0; border-radius:calc(var(--radius-md) - 2px); padding:.45rem .8rem; background:transparent; color:var(--muted-foreground); font-size:var(--text-sm); font-weight:600; cursor:pointer; }
.period-tab.active { background:var(--card); color:var(--foreground); box-shadow:var(--shadow-sm); }
.analytics-select { width:100%; min-width:0; }
.analytics-grid { display:grid; gap:1.25rem; }
.grid-2 { grid-template-columns:repeat(2, minmax(0, 1fr)); }
.grid-3 { grid-template-columns:minmax(0, 2.4fr) minmax(18rem, .9fr); }
.panel { border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); padding:1.25rem; box-shadow:0 1px 2px rgba(15,23,42,.03); }
.panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
.panel-title { font-size:var(--text-base); font-weight:700; margin:0; }
.panel-note { color:var(--muted-foreground); font-size:var(--text-xs); margin-top:.2rem; }
.metric-meta { color:var(--muted-foreground); font-size:var(--text-xs); margin-top:.35rem; }
.analytics-stat { min-height:7.25rem; }
.analytics-stat .stat-card__value { line-height:1.05; }
.muted-value { color:var(--muted-foreground) !important; font-size:var(--text-3xl); }
.metric-good { color:var(--success); }
.metric-warn { color:#f59e0b; }
.metric-bad { color:var(--destructive); }
.bar-chart { display:flex; align-items:flex-end; gap:.65rem; height:220px; padding-top:.5rem; }
.bar-col { flex:1; min-width:2.25rem; height:100%; display:flex; flex-direction:column; align-items:center; gap:.35rem; }
.bar-track { flex:1; width:100%; display:flex; align-items:flex-end; justify-content:center; border-bottom:1px solid var(--border); }
.bar-fill { width:100%; max-width:3.25rem; min-height:.25rem; border-radius:var(--radius-sm) var(--radius-sm) 0 0; background:linear-gradient(to top, var(--primary), color-mix(in srgb, var(--primary) 65%, var(--info))); }
.bar-value { font-size:var(--text-xs); font-weight:700; color:var(--primary); min-height:1rem; }
.bar-label { max-width:100%; color:var(--muted-foreground); font-size:var(--text-xs); text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.donut-wrap { display:flex; gap:1.25rem; align-items:center; flex-wrap:wrap; }
.donut-box { position:relative; width:148px; height:148px; flex:0 0 auto; }
.donut-center { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none; }
.donut-number { font-size:var(--text-2xl); font-weight:800; }
.legend { flex:1; min-width:14rem; display:grid; gap:.6rem; }
.legend-row { display:grid; grid-template-columns:auto 1fr auto auto; gap:.5rem; align-items:center; font-size:var(--text-sm); }
.legend-dot { width:.65rem; height:.65rem; border-radius:999px; }
.legend-count { color:var(--muted-foreground); font-size:var(--text-xs); }
.trend-bars { display:flex; align-items:flex-end; gap:.6rem; height:170px; }
.trend-col { flex:1; min-width:2rem; height:100%; display:flex; flex-direction:column; align-items:center; gap:.3rem; }
.trend-fill { width:100%; max-width:3rem; min-height:.25rem; border-radius:var(--radius-sm) var(--radius-sm) 0 0; background:var(--primary); opacity:.82; }
.activity-fill { background:#0f766e; }
.trend-value { min-height:1rem; font-size:var(--text-xs); font-weight:700; color:var(--primary); }
.trend-label { font-size:var(--text-xs); color:var(--muted-foreground); text-align:center; }
.rank-list, .activity-list { display:grid; gap:.15rem; }
.rank-row, .activity-row { display:flex; align-items:center; gap:.75rem; padding:.7rem 0; border-top:1px solid var(--border); }
.rank-row:first-child, .activity-row:first-child { border-top:0; }
.rank-pos { width:1.75rem; text-align:center; font-weight:800; color:var(--muted-foreground); }
.avatar-chip { width:2.25rem; height:2.25rem; border-radius:999px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; font-size:var(--text-xs); font-weight:800; background:color-mix(in srgb, var(--primary) 12%, transparent); color:var(--primary); }
.row-main { flex:1; min-width:0; }
.row-title { font-size:var(--text-sm); font-weight:650; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.row-sub { color:var(--muted-foreground); font-size:var(--text-xs); margin-top:.15rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.score-pill { border-radius:999px; padding:.2rem .55rem; font-size:var(--text-xs); font-weight:800; background:color-mix(in srgb, var(--primary) 10%, transparent); color:var(--primary); }
.status-pill { border-radius:999px; padding:.2rem .55rem; font-size:var(--text-xs); font-weight:700; background:var(--muted); color:var(--muted-foreground); white-space:nowrap; }
.status-pill.ok { background:color-mix(in srgb, var(--success) 12%, transparent); color:var(--success); }
.status-pill.warn { background:color-mix(in srgb, #f59e0b 16%, transparent); color:#b45309; }
.table-wrap { overflow-x:auto; }
.analytics-table { width:100%; border-collapse:collapse; }
.analytics-table th, .analytics-table td { padding:.8rem .75rem; border-top:1px solid var(--border); text-align:left; font-size:var(--text-sm); white-space:nowrap; }
.analytics-table th { color:var(--muted-foreground); font-size:var(--text-xs); text-transform:uppercase; letter-spacing:0; font-weight:800; }
.analytics-table td.num, .analytics-table th.num { text-align:right; }
.progress-line { height:.45rem; min-width:7rem; border-radius:999px; background:var(--muted); overflow:hidden; }
.progress-line span { display:block; height:100%; border-radius:inherit; background:var(--primary); }
.empty-state { min-height:7rem; display:flex; align-items:center; justify-content:center; text-align:center; padding:1.25rem; color:var(--muted-foreground); font-size:var(--text-sm); line-height:1.55; border:1px dashed var(--border); border-radius:var(--radius-md); background:color-mix(in srgb, var(--muted) 35%, transparent); }
.chart-empty { min-height:7.75rem; }
@media (max-width: 1024px) {
    .analytics-header { grid-template-columns:1fr; }
    .analytics-toolbar { justify-self:stretch; max-width:none; }
    .grid-2, .grid-3 { grid-template-columns:1fr; }
}
@media (max-width: 640px) {
    .panel { padding:1rem; }
    .analytics-filter-row, .date-filter-row { grid-template-columns:1fr; }
    .analytics-filter .btn, .analytics-select, .period-tabs { width:100%; }
    .period-tabs { display:grid; grid-template-columns:repeat(2, 1fr); }
    .donut-box { margin:auto; }
    .bar-chart, .trend-bars { gap:.35rem; }
}
</style>
@endpush

@section('content')
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1rem;">{{ session('error') }}</div>
@endif

<div class="page-header">
    <div class="analytics-header">
        <div class="analytics-heading">
            <h1>Phân tích</h1>
            <p style="color:var(--muted-foreground);">
                Dữ liệu từ {{ $range['start']->format('d/m/Y') }} đến {{ $range['end']->format('d/m/Y') }}
                @if($classId)
                    cho {{ optional($classes->firstWhere('id', $classId))->name }}
                @endif
            </p>
        </div>
        <div class="analytics-toolbar">
            <form method="GET" class="analytics-filter">
                <div class="period-tabs" role="tablist" aria-label="Chọn kỳ thống kê">
                    @foreach($periodLabels as $key => $label)
                        <a href="{{ route('teacher.analytics', array_filter(array_merge(['period' => $key, 'class_id' => $classId], $key === 'custom' ? ['start_date' => $range['start_date'], 'end_date' => $range['end_date']] : []))) }}"
                           class="period-tab {{ $period === $key ? 'active' : '' }}"
                           style="text-align:center;text-decoration:none;">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                <input type="hidden" name="period" value="{{ $period }}">
                @if($period === 'custom')
                    <div class="date-filter-row">
                        <div class="date-field">
                            <label for="analytics-start-date">Từ ngày</label>
                            <input id="analytics-start-date" type="date" name="start_date" class="input" value="{{ $range['start_date'] }}">
                        </div>
                        <div class="date-field">
                            <label for="analytics-end-date">Đến ngày</label>
                            <input id="analytics-end-date" type="date" name="end_date" class="input" value="{{ $range['end_date'] }}">
                        </div>
                        <button type="submit" name="period" value="custom" class="btn btn-primary btn-sm">Lọc ngày</button>
                    </div>
                @endif
                <div class="analytics-filter-row">
                <select name="class_id" class="input analytics-select" onchange="this.form.submit()" aria-label="Lọc theo lớp">
                    <option value="">Tất cả lớp</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
                    @if($classId)
                        <a class="btn btn-outline btn-sm" href="{{ route('teacher.analytics', ['period' => $period]) }}">Xóa lọc</a>
                    @endif
                    <a href="{{ route('teacher.analytics.export', $exportQuery) }}" class="btn btn-outline btn-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Xuất Excel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card analytics-stat">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm trung bình</div>
        <div class="stat-card__value {{ $summary['avg_score'] === null ? 'muted-value' : '' }}">{{ $summary['avg_score'] !== null ? number_format($summary['avg_score'], 1) . '%' : '—' }}</div>
        <div class="metric-meta">{{ $summary['total_graded'] }} bài đã có điểm trong kỳ</div>
    </div>
    <div class="stat-card analytics-stat">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tỷ lệ hoàn thành</div>
        <div class="stat-card__value {{ $summary['completion_rate'] === null ? 'muted-value' : '' }}" style="{{ $summary['completion_rate'] !== null ? 'color:var(--success);' : '' }}">{{ $summary['completion_rate'] !== null ? number_format($summary['completion_rate'], 1) . '%' : '—' }}</div>
        <div class="metric-meta">{{ $summary['submitted'] }} lượt nộp trong kỳ</div>
    </div>
    <div class="stat-card analytics-stat">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Chờ chấm</div>
        <div class="stat-card__value" style="color:#f59e0b;">{{ $summary['pending_grading'] }}</div>
        <div class="metric-meta">{{ $summary['quiz_submissions'] }} quiz, {{ $summary['assignment_submissions'] }} bài tập</div>
    </div>
    <div class="stat-card analytics-stat">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Quy mô lớp học</div>
        <div class="stat-card__value" style="color:var(--info);">{{ $summary['student_count'] }}</div>
        <div class="metric-meta">{{ $summary['class_count'] }} lớp, {{ $summary['quiz_count'] }} quiz, {{ $summary['assignment_count'] }} bài tập</div>
    </div>
</div>

<div class="analytics-grid grid-2 stagger-children" style="margin-bottom:1.25rem;">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Điểm trung bình theo lớp</h2>
                <div class="panel-note">Tính theo phần trăm điểm tối đa của từng bài.</div>
            </div>
        </div>
        @if($scoreByClass->whereNotNull('avg_score')->isNotEmpty())
            <div class="bar-chart">
                @foreach($scoreByClass as $item)
                    <div class="bar-col">
                        <div class="bar-value">{{ $item['avg_score'] !== null ? number_format($item['avg_score'], 1) . '%' : '' }}</div>
                        <div class="bar-track">
                            <div class="bar-fill" style="height:{{ $item['avg_score'] !== null ? max(4, round(($item['avg_score'] / $maxClassScore) * 100)) : 0 }}%;"></div>
                        </div>
                        <div class="bar-label" title="{{ $item['class_name'] }}">{{ $item['class_name'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Chưa có điểm trong kỳ đã chọn.</div>
        @endif
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Phân bố kết quả</h2>
                <div class="panel-note">Nhóm theo tỷ lệ điểm đạt được.</div>
            </div>
        </div>
        @if(collect($distribution)->sum('count') > 0)
            <div class="donut-wrap">
                <div class="donut-box">
                    <svg width="148" height="148" viewBox="0 0 148 148" id="analytics-donut" aria-hidden="true"></svg>
                    <div class="donut-center">
                        <div class="donut-number">{{ collect($distribution)->sum('count') }}</div>
                        <div class="panel-note">bài có điểm</div>
                    </div>
                </div>
                <div class="legend">
                    @foreach($distribution as $item)
                        <div class="legend-row">
                            <span class="legend-dot" style="background:{{ $item['color'] }}"></span>
                            <span>{{ $item['label'] }}</span>
                            <span class="legend-count">{{ $item['count'] }} bài</span>
                            <strong>{{ $item['pct'] }}%</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="empty-state">Chưa có dữ liệu phân bố điểm.</div>
        @endif
    </section>
</div>

<div class="analytics-grid grid-2 stagger-children" style="margin-bottom:1.25rem;">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Xu hướng điểm 6 tuần</h2>
                <div class="panel-note">Điểm trung bình theo tuần.</div>
            </div>
        </div>
        @if(collect($weeklyTrend)->sum('count') > 0)
            <div class="trend-bars">
                @foreach($weeklyTrend as $item)
                    <div class="trend-col" title="{{ $item['count'] }} bài có điểm">
                        <div class="trend-value">{{ $item['val'] > 0 ? number_format($item['val'], 0) . '%' : '' }}</div>
                        <div style="flex:1;width:100%;display:flex;align-items:flex-end;justify-content:center;">
                            <div class="trend-fill" style="height:{{ max(4, round(($item['val'] / $maxWeeklyScore) * 100)) }}%;"></div>
                        </div>
                        <div class="trend-label">{{ $item['label'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Chưa đủ dữ liệu để hiển thị xu hướng điểm.</div>
        @endif
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Phân tích theo ngày</h2>
                <div class="panel-note">Số lượt nộp theo từng ngày trong khoảng đã chọn.</div>
            </div>
        </div>
        @if(collect($activityTrend)->sum('count') > 0)
            <div class="trend-bars">
                @foreach($activityTrend as $item)
                    <div class="trend-col" title="{{ $item['date'] }}">
                        <div class="trend-value" style="color:#0f766e;">{{ $item['count'] ?: '' }}</div>
                        <div style="flex:1;width:100%;display:flex;align-items:flex-end;justify-content:center;">
                            <div class="trend-fill activity-fill" style="height:{{ max(4, round(($item['count'] / $maxActivity) * 100)) }}%;"></div>
                        </div>
                        <div class="trend-label">{{ $item['label'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Chưa có lượt nộp trong khoảng ngày đã chọn.</div>
        @endif
    </section>
</div>

<div class="analytics-grid grid-3 stagger-children" style="margin-bottom:1.25rem;">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Chi tiết theo lớp</h2>
                <div class="panel-note">Theo dữ liệu trong kỳ đang chọn.</div>
            </div>
        </div>
        @if($scoreByClass->isNotEmpty())
            <div class="table-wrap">
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>Lớp</th>
                            <th class="num">Học sinh</th>
                            <th class="num">Lượt nộp</th>
                            <th class="num">Đã chấm</th>
                            <th class="num">Điểm TB</th>
                            <th>Tỷ lệ hoàn thành</th>
                            <th class="num">Xuất sắc</th>
                            <th class="num">Cần hỗ trợ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($scoreByClass as $item)
                            <tr>
                                <td style="font-weight:700;">{{ $item['class_name'] }}</td>
                                <td class="num">{{ $item['student_count'] }}</td>
                                <td class="num">{{ $item['submitted_count'] }}</td>
                                <td class="num">{{ $item['graded_count'] }}</td>
                                <td class="num">{{ $item['avg_score'] !== null ? number_format($item['avg_score'], 1) . '%' : '—' }}</td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.6rem;">
                                        <div class="progress-line"><span style="width:{{ $item['completion_rate'] ?? 0 }}%;"></span></div>
                                        <strong>{{ $item['completion_rate'] !== null ? number_format($item['completion_rate'], 0) . '%' : '—' }}</strong>
                                    </div>
                                </td>
                                <td class="num metric-good">{{ $item['excellent_rate'] }}%</td>
                                <td class="num metric-bad">{{ $item['weak_rate'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">Bạn chưa có lớp học nào để thống kê.</div>
        @endif
    </section>

    <aside class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Top học sinh</h2>
                <div class="panel-note">Xếp theo điểm trung bình.</div>
            </div>
        </div>
        @if($topStudents->isNotEmpty())
            <div class="rank-list">
                @foreach($topStudents as $index => $student)
                    @php
                        $initials = collect(explode(' ', $student['name']))->filter()->map(fn ($word) => mb_substr($word, 0, 1))->take(-2)->implode('');
                    @endphp
                    <div class="rank-row">
                        <div class="rank-pos">{{ $index + 1 }}</div>
                        <div class="avatar-chip">{{ $initials }}</div>
                        <div class="row-main">
                            <div class="row-title">{{ $student['name'] }}</div>
                            <div class="row-sub">{{ $student['submitted'] }} bài có điểm</div>
                        </div>
                        <div class="score-pill">{{ number_format($student['avg'], 1) }}%</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Chưa có học sinh nào được xếp hạng.</div>
        @endif
    </aside>
</div>

<div class="analytics-grid grid-2 stagger-children">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Học sinh cần hỗ trợ</h2>
                <div class="panel-note">Điểm trung bình dưới 50% hoặc có nhiều bài thấp.</div>
            </div>
        </div>
        @if($atRiskStudents->isNotEmpty())
            <div class="rank-list">
                @foreach($atRiskStudents as $student)
                    <div class="rank-row">
                        <div class="avatar-chip" style="background:color-mix(in srgb, var(--destructive) 12%, transparent);color:var(--destructive);">
                            {{ collect(explode(' ', $student['name']))->filter()->map(fn ($word) => mb_substr($word, 0, 1))->take(-2)->implode('') }}
                        </div>
                        <div class="row-main">
                            <div class="row-title">{{ $student['name'] }}</div>
                            <div class="row-sub">{{ $student['email'] }} · {{ $student['low_count'] }} bài dưới 50%</div>
                        </div>
                        <div class="score-pill" style="background:color-mix(in srgb, var(--destructive) 10%, transparent);color:var(--destructive);">
                            {{ number_format($student['avg'], 1) }}%
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Không có học sinh nào trong nhóm cần hỗ trợ ở kỳ này.</div>
        @endif
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Hoạt động gần đây</h2>
                <div class="panel-note">Các lượt nộp mới nhất trong kỳ.</div>
            </div>
        </div>
        @if($recentActivities->isNotEmpty())
            <div class="activity-list">
                @foreach($recentActivities as $activity)
                    <div class="activity-row">
                        <div class="avatar-chip" style="background:{{ $activity->type === 'quiz' ? 'color-mix(in srgb, var(--primary) 12%, transparent)' : 'color-mix(in srgb, #0f766e 12%, transparent)' }};color:{{ $activity->type === 'quiz' ? 'var(--primary)' : '#0f766e' }};">
                            {{ $activity->type === 'quiz' ? 'Q' : 'BT' }}
                        </div>
                        <div class="row-main">
                            <div class="row-title">{{ $activity->student_name }} nộp {{ $activity->item_title }}</div>
                            <div class="row-sub">
                                {{ $activity->class_name }}
                                @if($activity->course_name) · {{ $activity->course_name }} @endif
                                · {{ $activity->submitted_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        @if($activity->percentage !== null)
                            <span class="status-pill ok">{{ number_format($activity->percentage, 1) }}%</span>
                        @else
                            <span class="status-pill warn">Chờ chấm</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">Chưa có hoạt động trong kỳ đã chọn.</div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const data = @json($distribution);
    const total = data.reduce((sum, item) => sum + item.count, 0);
    const svg = document.getElementById('analytics-donut');
    if (!svg || total === 0) return;

    const radius = 56;
    const center = 74;
    const circumference = 2 * Math.PI * radius;
    let offset = 0;
    let output = `<circle cx="${center}" cy="${center}" r="${radius}" fill="none" stroke="var(--muted)" stroke-width="18" />`;

    data.forEach(function(item) {
        if (item.count <= 0) return;
        const dash = (item.count / total) * circumference;
        output += `<circle cx="${center}" cy="${center}" r="${radius}" fill="none" stroke="${item.color}" stroke-width="18" stroke-linecap="round" stroke-dasharray="${dash} ${circumference - dash}" stroke-dashoffset="${circumference * .25 - offset}" />`;
        offset += dash;
    });

    svg.innerHTML = output;
})();
</script>
@endpush
