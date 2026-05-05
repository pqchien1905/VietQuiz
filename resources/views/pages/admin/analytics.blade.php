@extends('layouts.admin')

@section('title', 'Admin - Thống kê')
@section('page-title', 'Thống kê hệ thống')
@section('page-description', 'Theo dõi dữ liệu người dùng, học tập, vận hành, VIP và xuất báo cáo Excel/PDF.')

@php
  $cards = [
    ['label' => 'Người dùng', 'value' => $overview['users'], 'detail' => '+'.number_format($periodStats['users']).' trong kỳ', 'tone' => 'var(--primary)'],
    ['label' => 'Giáo viên', 'value' => $overview['teachers'], 'detail' => number_format($overview['students']).' học sinh', 'tone' => 'var(--info)'],
    ['label' => 'Quiz', 'value' => $overview['quizzes'], 'detail' => '+'.number_format($periodStats['quizzes']).' trong kỳ', 'tone' => 'var(--success)'],
    ['label' => 'Bài nộp', 'value' => $overview['submissions'], 'detail' => '+'.number_format($periodStats['submissions']).' trong kỳ', 'tone' => 'var(--warning)'],
    ['label' => 'Doanh thu VIP', 'value' => number_format($overview['revenue']).'đ', 'detail' => number_format($periodStats['revenue']).'đ trong kỳ', 'tone' => '#eab308'],
    ['label' => 'Điểm TB', 'value' => $overview['avg_score'] ?: '—', 'detail' => number_format($overview['grades']).' điểm đã ghi', 'tone' => 'var(--accent)'],
  ];
@endphp

@push('styles')
<style>
  .analytics-filter { display:flex; align-items:end; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .analytics-filter form { display:flex; align-items:end; gap:.75rem; flex-wrap:wrap; }
  .analytics-filter .form-group { min-width:11rem; }
  .analytics-chart-grid { display:grid; grid-template-columns:2fr 1fr; gap:1rem; }
  .analytics-chart-card canvas { width:100% !important; max-height:320px; }
  .analytics-mini-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
  .analytics-metric { padding:1rem; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--card); min-width:0; }
  .analytics-metric__label { color:var(--muted-foreground); font-size:var(--text-xs); font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
  .analytics-metric__value { margin-top:.4rem; font-size:1.45rem; font-weight:900; line-height:1.1; }
  .analytics-metric__detail { margin-top:.3rem; color:var(--muted-foreground); font-size:var(--text-xs); }
  .analytics-table-title { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  @media (max-width:1180px) { .analytics-chart-grid,.analytics-mini-grid { grid-template-columns:1fr 1fr; } }
  @media (max-width:720px) { .analytics-chart-grid,.analytics-mini-grid { grid-template-columns:1fr; } .analytics-filter form,.analytics-filter .form-group,.analytics-filter .btn { width:100%; } }
  @media print {
    @page { size:A4 portrait; margin:10mm; }
    html, body { background:#fff !important; color:#111 !important; }
    body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .sidebar,
    .admin-header,
    .mobile-overlay,
    .analytics-filter form,
    script { display:none !important; }
    .admin-shell,
    .main-container,
    .main-content { display:block !important; height:auto !important; min-height:0 !important; overflow:visible !important; width:100% !important; background:#fff !important; }
    .main-content { padding:0 !important; }
    .admin-content { display:block !important; max-width:none !important; width:100% !important; }
    .admin-content > * { margin-bottom:10px !important; }
    .card,
    .stat-card,
    .analytics-metric { break-inside:avoid; page-break-inside:avoid; box-shadow:none !important; border:1px solid #d1d5db !important; background:#fff !important; }
    .stats-grid { display:grid !important; grid-template-columns:repeat(3,1fr) !important; gap:8px !important; }
    .analytics-chart-grid,
    .admin-grid-2,
    .analytics-mini-grid { display:grid !important; grid-template-columns:1fr !important; gap:8px !important; }
    .card-header,
    .card-content { padding:10px !important; }
    .stat-card,
    .analytics-metric { padding:10px !important; min-height:0 !important; }
    .card-title { font-size:14px !important; color:#111 !important; }
    .card-description,
    .stat-card__label,
    .analytics-metric__label,
    .analytics-metric__detail,
    .admin-row-meta { color:#4b5563 !important; }
    .stat-card__value,
    .analytics-metric__value { font-size:22px !important; }
    .analytics-chart-card canvas { max-height:190px !important; }
    .table-wrapper { overflow:visible !important; border:none !important; }
    table { width:100% !important; min-width:0 !important; border-collapse:collapse !important; font-size:11px !important; }
    th, td { border:1px solid #e5e7eb !important; padding:5px 6px !important; color:#111 !important; }
    a { color:#111 !important; text-decoration:none !important; }
  }
</style>
@endpush

@section('actions')
  <a class="btn btn-primary btn-sm" href="{{ route('admin.analytics.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Xuất Excel</a>
  <button class="btn btn-outline btn-sm" type="button" id="admin-analytics-print-pdf">Xuất PDF</button>
@endsection

@section('content')
<section class="card">
  <div class="card-content analytics-filter">
    <div>
      <h3 class="card-title">Khoảng thống kê</h3>
      <p class="card-description">{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</p>
    </div>
    <form method="GET" action="{{ route('admin.analytics') }}">
      <div class="form-group">
        <label class="label">Từ ngày</label>
        <input class="input" type="date" name="from" value="{{ $from->toDateString() }}">
      </div>
      <div class="form-group">
        <label class="label">Đến ngày</label>
        <input class="input" type="date" name="to" value="{{ $to->toDateString() }}">
      </div>
      <button class="btn btn-primary" type="submit">Lọc</button>
      <a class="btn btn-outline" href="{{ route('admin.analytics') }}">30 ngày</a>
    </form>
  </div>
</section>

<section class="stats-grid stats-grid-3">
  @foreach($cards as $card)
    <div class="stat-card">
      <div class="stat-card__label">{{ $card['label'] }}</div>
      <div class="stat-card__value" style="color:{{ $card['tone'] }}">{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</div>
      <div class="stat-card__label">{{ $card['detail'] }}</div>
    </div>
  @endforeach
</section>

<div class="analytics-chart-grid">
  <section class="card analytics-chart-card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Xu hướng 12 tháng</h3>
        <p class="card-description">Người dùng mới, quiz mới, bài nộp và doanh thu VIP.</p>
      </div>
    </div>
    <div class="card-content"><canvas id="adminMonthlyChart" height="140"></canvas></div>
  </section>

  <section class="card analytics-chart-card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Cơ cấu người dùng</h3>
        <p class="card-description">Phân bố theo vai trò.</p>
      </div>
    </div>
    <div class="card-content"><canvas id="adminRoleChart" height="220"></canvas></div>
  </section>
</div>

<div class="analytics-chart-grid">
  <section class="card analytics-chart-card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Trạng thái học tập</h3>
        <p class="card-description">Quiz theo trạng thái và dữ liệu chấm điểm.</p>
      </div>
    </div>
    <div class="card-content"><canvas id="adminLearningChart" height="140"></canvas></div>
  </section>

  <section class="card analytics-chart-card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Hỗ trợ</h3>
        <p class="card-description">Ticket theo trạng thái.</p>
      </div>
    </div>
    <div class="card-content"><canvas id="adminTicketChart" height="220"></canvas></div>
  </section>
</div>

<section class="analytics-mini-grid">
  <div class="analytics-metric">
    <div class="analytics-metric__label">Lượt làm quiz</div>
    <div class="analytics-metric__value">{{ number_format($learning['quiz_attempts']) }}</div>
    <div class="analytics-metric__detail">{{ number_format($learning['submitted_attempts']) }} lượt đã nộp</div>
  </div>
  <div class="analytics-metric">
    <div class="analytics-metric__label">Chưa chấm</div>
    <div class="analytics-metric__value">{{ number_format($learning['ungraded_attempts'] + $learning['ungraded_submissions']) }}</div>
    <div class="analytics-metric__detail">Quiz và bài tập cần xử lý</div>
  </div>
  <div class="analytics-metric">
    <div class="analytics-metric__label">Bài tập quá hạn</div>
    <div class="analytics-metric__value">{{ number_format($learning['overdue_assignments']) }}</div>
    <div class="analytics-metric__detail">Tính đến hiện tại</div>
  </div>
  <div class="analytics-metric">
    <div class="analytics-metric__label">VIP đang hoạt động</div>
    <div class="analytics-metric__value">{{ number_format($overview['vip']) }}</div>
    <div class="analytics-metric__detail">Tổng đăng ký active</div>
  </div>
</section>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header analytics-table-title">
      <div>
        <h3 class="card-title">Giáo viên nổi bật</h3>
        <p class="card-description">Sắp theo số quiz và bài tập.</p>
      </div>
    </div>
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead><tr><th>Giáo viên</th><th>Lớp</th><th>Khóa</th><th>Quiz</th><th>Bài tập</th></tr></thead>
        <tbody>
          @forelse($topTeachers as $teacher)
            <tr>
              <td><div class="admin-row-title">{{ $teacher->name }}</div><div class="admin-row-meta">{{ $teacher->email }}</div></td>
              <td>{{ number_format($teacher->created_classes_count) }}</td>
              <td>{{ number_format($teacher->created_courses_count) }}</td>
              <td>{{ number_format($teacher->quizzes_count) }}</td>
              <td>{{ number_format($teacher->assignments_count) }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="empty-state">Chưa có dữ liệu giáo viên.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="card">
    <div class="card-header analytics-table-title">
      <div>
        <h3 class="card-title">Khóa học nhiều học viên</h3>
        <p class="card-description">Top khóa theo số học viên ghi danh.</p>
      </div>
    </div>
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead><tr><th>Khóa học</th><th>Giáo viên</th><th>Học viên</th><th>Quiz</th><th>Bài tập</th></tr></thead>
        <tbody>
          @forelse($topCourses as $course)
            <tr>
              <td><a class="admin-row-title" href="{{ route('admin.courses.show', $course->id) }}">{{ $course->name }}</a></td>
              <td>{{ $course->teacher?->name ?? '—' }}</td>
              <td>{{ number_format($course->students_count) }}</td>
              <td>{{ number_format($course->quizzes_count) }}</td>
              <td>{{ number_format($course->assignments_count) }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="empty-state">Chưa có dữ liệu khóa học.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Thanh toán VIP</h3>
        <p class="card-description">Tổng hợp theo trạng thái giao dịch.</p>
      </div>
    </div>
    <div class="table-wrapper" style="border:none;border-radius:0;">
      <table>
        <thead><tr><th>Trạng thái</th><th>Số lượng</th><th>Tổng tiền</th></tr></thead>
        <tbody>
          @forelse($paymentStatus as $row)
            <tr><td><span class="badge badge-outline">{{ $row['status'] }}</span></td><td>{{ number_format($row['count']) }}</td><td>{{ number_format($row['amount']) }}đ</td></tr>
          @empty
            <tr><td colspan="3" class="empty-state">Chưa có giao dịch VIP.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Hoạt động mới nhất</h3>
        <p class="card-description">Mốc cập nhật gần nhất theo nhóm dữ liệu.</p>
      </div>
    </div>
    <div class="card-content">
      @foreach($recentActivity as $activity)
        <div class="activity-item">
          <span class="badge badge-info">{{ $activity['label'] }}</span>
          <div class="admin-row-title">{{ $activity['value'] }}</div>
        </div>
      @endforeach
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  const monthly = @json($monthly);
  const roles = @json($roleDistribution);
  const quizStatus = @json($quizStatus);
  const ticketStatus = @json($ticketStatus);
  const learning = @json($learning);
  const css = getComputedStyle(document.documentElement);
  const palette = [
    css.getPropertyValue('--primary').trim() || '#3b82f6',
    css.getPropertyValue('--success').trim() || '#22c55e',
    css.getPropertyValue('--warning').trim() || '#f59e0b',
    css.getPropertyValue('--destructive').trim() || '#ef4444',
    css.getPropertyValue('--info').trim() || '#06b6d4',
    '#eab308',
  ];
  const grid = css.getPropertyValue('--border').trim() || '#e5e7eb';
  const text = css.getPropertyValue('--muted-foreground').trim() || '#6b7280';

  function baseOptions(extra = {}) {
    return {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { labels: { color: text, usePointStyle: true } } },
      scales: {
        x: { ticks: { color: text }, grid: { color: grid } },
        y: { ticks: { color: text }, grid: { color: grid } },
      },
      ...extra,
    };
  }

  new Chart(document.getElementById('adminMonthlyChart'), {
    type: 'line',
    data: {
      labels: monthly.map(row => row.label),
      datasets: [
        { label: 'Người dùng', data: monthly.map(row => row.users), borderColor: palette[0], backgroundColor: palette[0] + '22', tension: .35, fill: true },
        { label: 'Quiz', data: monthly.map(row => row.quizzes), borderColor: palette[1], backgroundColor: palette[1] + '22', tension: .35 },
        { label: 'Bài nộp', data: monthly.map(row => row.submissions), borderColor: palette[2], backgroundColor: palette[2] + '22', tension: .35 },
      ],
    },
    options: baseOptions(),
  });

  new Chart(document.getElementById('adminRoleChart'), {
    type: 'doughnut',
    data: { labels: roles.map(row => row.label), datasets: [{ data: roles.map(row => row.value), backgroundColor: palette.map(color => color + 'cc') }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: text, usePointStyle: true } } } },
  });

  new Chart(document.getElementById('adminLearningChart'), {
    type: 'bar',
    data: {
      labels: ['Đã xuất bản', 'Nháp', 'Đã đóng', 'Đã nộp', 'Chưa chấm', 'Quá hạn'],
      datasets: [{ label: 'Số lượng', data: [
        learning.published_quizzes,
        learning.draft_quizzes,
        learning.closed_quizzes,
        learning.submitted_attempts,
        learning.ungraded_attempts + learning.ungraded_submissions,
        learning.overdue_assignments,
      ], backgroundColor: palette.map(color => color + 'cc'), borderRadius: 4 }],
    },
    options: baseOptions({ plugins: { legend: { display: false } } }),
  });

  new Chart(document.getElementById('adminTicketChart'), {
    type: 'doughnut',
    data: { labels: ticketStatus.map(row => row.label), datasets: [{ data: ticketStatus.map(row => row.value), backgroundColor: palette.map(color => color + 'cc') }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: text, usePointStyle: true } } } },
  });

  document.getElementById('admin-analytics-print-pdf')?.addEventListener('click', function() {
    const source = document.querySelector('.admin-content');
    if (!source) return;

    const clone = source.cloneNode(true);
    const sourceCanvases = source.querySelectorAll('canvas');
    const clonedCanvases = clone.querySelectorAll('canvas');

    clonedCanvases.forEach(function(canvas, index) {
      const original = sourceCanvases[index];
      if (!original) return;

      const image = document.createElement('img');
      image.src = original.toDataURL('image/png');
      image.alt = canvas.id || 'Biểu đồ thống kê';
      image.className = 'print-chart-image';
      canvas.replaceWith(image);
    });

    clone.querySelectorAll('script').forEach(function(script) {
      script.remove();
    });

    const printWindow = window.open('', 'vietquiz-admin-analytics-print');
    if (!printWindow) {
      window.print();
      return;
    }

    printWindow.document.open();
    printWindow.document.write(`
      <!doctype html>
      <html lang="vi">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>VietQuiz - Thống kê hệ thống</title>
        <style>
          @page { size: A4 portrait; margin: 10mm; }
          * { box-sizing: border-box; }
          body {
            margin: 0;
            background: #fff;
            color: #111827;
            font-family: "Be Vietnam Pro", Arial, sans-serif;
            font-size: 12px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          .admin-content { display: flex; flex-direction: column; gap: 10px; width: 100%; }
          .card,
          .stat-card,
          .analytics-metric {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
            overflow: hidden;
          }
          .card-header,
          .card-content { padding: 10px; }
          .card-title { margin: 0; font-size: 14px; font-weight: 800; color: #111827; }
          .card-description { margin: 4px 0 0; color: #6b7280; }
          .analytics-filter { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
          .analytics-filter form { display: none; }
          .stats-grid,
          .analytics-mini-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
          }
          .analytics-chart-grid,
          .admin-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
          }
          .stat-card,
          .analytics-metric { padding: 10px; }
          .stat-card__label,
          .analytics-metric__label,
          .analytics-metric__detail,
          .admin-row-meta { color: #6b7280; font-size: 11px; }
          .stat-card__value,
          .analytics-metric__value { margin-top: 4px; font-size: 22px; font-weight: 900; line-height: 1.1; }
          .print-chart-image {
            display: block;
            width: 100%;
            max-height: 230px;
            object-fit: contain;
          }
          .table-wrapper { overflow: visible; border: 0 !important; }
          table { width: 100%; border-collapse: collapse; font-size: 11px; }
          th, td { border: 1px solid #e5e7eb; padding: 5px 6px; text-align: left; vertical-align: top; }
          th { background: #f3f4f6; font-weight: 800; }
          a { color: #111827; text-decoration: none; }
          .badge {
            display: inline-flex;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            padding: 2px 8px;
            color: #111827;
            background: #fff;
          }
          .activity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            border-top: 1px solid #e5e7eb;
          }
          .activity-item:first-child { border-top: 0; }
        </style>
      </head>
      <body>${clone.outerHTML}</body>
      </html>
    `);
    printWindow.document.close();

    printWindow.onload = function() {
      printWindow.focus();
      printWindow.print();
    };
  });
</script>
@endpush
