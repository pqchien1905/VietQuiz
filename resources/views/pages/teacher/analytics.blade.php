{{-- Teacher: analytics --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.chart-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--card);
    padding: 1.5rem;
}
.chart-title {
    font-weight: 600;
    font-size: var(--text-base);
    margin-bottom: 1rem;
}
.bar-chart {
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    height: 200px;
    padding-top: 0.5rem;
}
.bar-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    gap: 0.25rem;
    height: 100%;
    min-width: 2rem;
}
.bar-fill {
    width: 100%;
    border-radius: var(--radius-sm) var(--radius-sm) 0 0;
    transition: height 0.6s ease;
    min-width: 1.5rem;
    max-width: 4rem;
}
.bar-label {
    font-size: var(--text-xs);
    color: var(--muted-foreground);
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.bar-val {
    font-size: var(--text-xs);
    font-weight: 700;
}
.donut-row {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    flex-wrap: wrap;
}
.donut-legend {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
}
.leg-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: var(--text-sm);
}
.leg-dot {
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
    flex-shrink: 0;
}
.rank-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--border);
}
.rank-row:last-child { border-bottom: none; }
.rank-num {
    width: 1.5rem;
    text-align: center;
    font-weight: 700;
    font-size: var(--text-sm);
    color: var(--muted-foreground);
}
.rank-ava {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-xs);
    font-weight: 700;
    flex-shrink: 0;
}
.rank-score {
    font-weight: 700;
    min-width: 2.5rem;
    text-align: right;
}
.medal { font-size: 1.1rem; }
.line-chart {
    display: flex;
    align-items: flex-end;
    gap: 0.75rem;
    height: 140px;
    padding-top: 0.5rem;
}
.line-dot {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    height: 100%;
}
.line-point {
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
    background: var(--primary);
    flex-shrink: 0;
    margin-top: auto;
}
.line-bar {
    width: 100%;
    max-width: 3rem;
    border-radius: var(--radius-sm) var(--radius-sm) 0 0;
    background: var(--primary);
    opacity: 0.7;
    transition: height 0.6s ease;
}
.line-label {
    font-size: var(--text-xs);
    color: var(--muted-foreground);
    text-align: center;
}
.line-val {
    font-size: var(--text-xs);
    font-weight: 700;
    color: var(--primary);
}
.period-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.period-btn {
    padding: 0.375rem 0.875rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    background: var(--card);
    cursor: pointer;
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--muted-foreground);
    transition: all var(--transition-fast);
}
.period-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}
.period-btn.active {
    background: var(--primary);
    color: var(--primary-foreground);
    border-color: var(--primary);
}
.empty-chart {
    text-align: center;
    padding: 2rem;
    color: var(--muted-foreground);
    font-size: var(--text-sm);
}
</style>
@endpush

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1>Phân tích</h1>
            <p style="color:var(--muted-foreground);">Tổng hợp dữ liệu và hiệu suất giảng dạy</p>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
            <form method="GET" class="period-form" id="period-form">
                <button type="submit" name="period" value="week"
                    class="period-btn {{ $period === 'week' ? 'active' : '' }}">Tuần</button>
                <button type="submit" name="period" value="month"
                    class="period-btn {{ $period === 'month' ? 'active' : '' }}">Tháng</button>
                <button type="submit" name="period" value="quarter"
                    class="period-btn {{ $period === 'quarter' ? 'active' : '' }}">Quý</button>
                <button type="submit" name="period" value="year"
                    class="period-btn {{ $period === 'year' ? 'active' : '' }}">Năm</button>
            </form>
            <a href="{{ route('teacher.analytics.export') }}" class="btn btn-outline btn-sm gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Xuất CSV
            </a>
        </div>
    </div>
</div>

<!-- Stats Row -->
<div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Điểm TB</div>
        <div class="stat-card__value">{{ $avgScore ? number_format($avgScore, 1) : '—' }}</div>
        <div class="stat-card__trend">Giáo viên</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Số bài đã chấm</div>
        <div class="stat-card__value" style="color:var(--primary);">{{ $totalGraded }}</div>
        <div class="stat-card__trend">Trong kỳ</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Bài kiểm tra</div>
        <div class="stat-card__value" style="color:var(--primary);">{{ $quizCount }}</div>
        <div class="stat-card__trend">Tổng số</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Học sinh</div>
        <div class="stat-card__value" style="color:var(--info);">{{ $studentCount }}</div>
        <div class="stat-card__trend">{{ $classCount }} lớp</div>
    </div>
</div>

<!-- Charts Row 1 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;" class="stagger-children">
    <!-- Bar chart: Score by Class -->
    <div class="chart-card">
        <h3 class="chart-title">Điểm TB theo lớp</h3>
        @if($scoreByClass->isNotEmpty())
        <div class="bar-chart" id="bar-class">
            @foreach($scoreByClass as $item)
            <?php $maxVal = $scoreByClass->max('avg_score') ?: 10; ?>
            <div class="bar-col">
                <div class="bar-val" style="color:var(--primary);">{{ $item['avg_score'] }}</div>
                <div style="flex:1;display:flex;align-items:flex-end;width:100%;">
                    <div class="bar-fill" style="height:{{ max(4, round($item['avg_score'] / 10 * 100)) }}%;background:var(--primary);"></div>
                </div>
                <div class="bar-label" title="{{ $item['class_name'] }}">{{ $item['class_name'] }}</div>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-chart">Chưa có dữ liệu điểm theo lớp</div>
        @endif
    </div>

    <!-- Donut: Score Distribution -->
    <div class="chart-card">
        <h3 class="chart-title">Phân bố xếp loại</h3>
        @if(array_sum(array_column($distribution, 'pct')) > 0)
        <div class="donut-row" id="donut-dist">
            <div style="position:relative;">
                <svg width="140" height="140" viewBox="0 0 140 140" id="donut-svg"></svg>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                    <div style="font-size:var(--text-2xl);font-weight:800;">{{ array_sum(array_column($distribution, 'pct')) }}%</div>
                    <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Tổng</div>
                </div>
            </div>
            <div class="donut-legend">
                @foreach($distribution as $d)
                <div class="leg-item">
                    <div class="leg-dot" style="background:{{ $d['color'] }};"></div>
                    <span>{{ $d['label'] }}</span>
                    <strong>{{ $d['pct'] }}%</strong>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="empty-chart">Chưa có dữ liệu phân bố điểm</div>
        @endif
    </div>
</div>

<!-- Charts Row 2 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;" class="stagger-children">
    <!-- Line chart: Weekly Trend -->
    <div class="chart-card">
        <h3 class="chart-title">Xu hướng điểm TB (6 tuần gần nhất)</h3>
        @if(collect($weeklyTrend)->sum('val') > 0)
        <div class="line-chart" id="weekly-trend">
            @foreach($weeklyTrend as $w)
            <?php $maxV = collect($weeklyTrend)->max('val') ?: 10; ?>
            <div class="line-dot">
                <div class="line-val">{{ $w['val'] > 0 ? $w['val'] : '' }}</div>
                <div style="flex:1;display:flex;align-items:flex-end;width:100%;justify-content:center;">
                    <div class="line-bar" style="height:{{ max(4, round($w['val'] / 10 * 100)) }}%;"></div>
                </div>
                <div class="line-point"></div>
                <div class="line-label">{{ $w['label'] }}</div>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-chart">Chưa có dữ liệu xu hướng</div>
        @endif
    </div>

    <!-- Top Students -->
    <div class="chart-card">
        <h3 class="chart-title">Top học sinh xuất sắc</h3>
        @if($topStudents->isNotEmpty())
        <?php $topColors = ['#f97316', '#3b82f6', '#22c55e', '#a855f7', '#06b6d4']; ?>
        @foreach($topStudents as $i => $student)
        <?php $c = $topColors[$i] ?? '#6b7280'; ?>
        <div class="rank-row">
            <div class="rank-num">
                @if($i === 0) 🥇
                @elseif($i === 1) 🥈
                @elseif($i === 2) 🥉
                @else {{ $i + 1 }}
                @endif
            </div>
            <div class="rank-ava" style="background:color-mix(in srgb, {{ $c }} 15%, transparent);color:{{ $c }};">
                {{ collect(explode(' ', $student['name']))->filter()->map(fn($w) => $w[0])->slice(-2)->implode('') }}
            </div>
            <div style="flex:1;">
                <div style="font-weight:500;font-size:var(--text-sm);">{{ $student['name'] }}</div>
            </div>
            <div class="rank-score" style="color:{{ $c }};">{{ $student['avg'] }}</div>
        </div>
        @endforeach
        @else
        <div class="empty-chart">Chưa có học sinh nào được xếp hạng</div>
        @endif
    </div>
</div>

<!-- Summary Table -->
<div class="chart-card stagger-children" style="margin-bottom:1.25rem;">
    <h3 class="chart-title">Chi tiết theo lớp</h3>
    @if($scoreByClass->isNotEmpty())
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Lớp</th>
                    <th style="text-align:right;">Số bài chấm</th>
                    <th style="text-align:right;">Điểm TB</th>
                    <th style="text-align:right;">Tỷ lệ Giỏi</th>
                    <th style="text-align:right;">Tỷ lệ Yếu</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scoreByClass as $item)
                <?php
                    $clsGrades = \App\Models\Grade::whereHas('student.classes', fn($q) => $q->where('classes.id', $item['class_id']))
                        ->where('grader_id', auth()->id())
                        ->get();
                    $total = $clsGrades->count();
                    $excellent = $clsGrades->where('score', '>=', 8)->count();
                    $weak = $clsGrades->where('score', '<', 5)->count();
                ?>
                <tr>
                    <td style="font-weight:600;">{{ $item['class_name'] }}</td>
                    <td style="text-align:right;color:var(--muted-foreground);">{{ $item['count'] }}</td>
                    <td style="text-align:right;font-weight:700;">{{ $item['avg_score'] }}</td>
                    <td style="text-align:right;color:var(--success);">
                        {{ $total > 0 ? round($excellent / $total * 100) : 0 }}%
                    </td>
                    <td style="text-align:right;color:var(--destructive);">
                        {{ $total > 0 ? round($weak / $total * 100) : 0 }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-chart">Chưa có dữ liệu chi tiết theo lớp</div>
    @endif
</div>

@endsection

@push('scripts')
<script>
// Draw donut chart with real data
(function() {
    const data = @json($distribution);
    const total = data.reduce((a, b) => a + b.pct, 0);

    if (total === 0) return;

    const svg = document.getElementById('donut-svg');
    if (!svg) return;

    const r = 55, cx = 70, cy = 70, circ = 2 * Math.PI * r;
    let cum = 0;
    let svgContent = '';

    data.forEach(d => {
        const dash = (d.pct / total) * circ;
        const gap = circ - dash;
        const offset = -cum * circ / total + circ * 0.25;
        svgContent += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${d.color}" stroke-width="18" stroke-dasharray="${dash} ${gap}" stroke-dashoffset="${offset}" />`;
        cum += d.pct;
    });

    svg.innerHTML = svgContent;
})();
</script>
@endpush
