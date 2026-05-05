@extends('layouts.admin')

@section('title', 'Admin - Tổng quan')
@section('page-title', 'Tổng quan hệ thống')
@section('page-description', 'Theo dõi dữ liệu vận hành, học tập, hỗ trợ và VIP trong một màn hình.')

@php
  $mainStats = [
    ['label' => 'Người dùng', 'value' => $stats['users'], 'detail' => $stats['teachers'].' giáo viên · '.$stats['students'].' học sinh', 'tone' => 'var(--primary)', 'href' => route('admin.users'), 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>'],
    ['label' => 'Lớp / khóa', 'value' => $stats['classes'], 'detail' => $stats['courses'].' khóa học', 'tone' => 'var(--info)', 'href' => route('admin.classes'), 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>'],
    ['label' => 'Bài kiểm tra', 'value' => $stats['quizzes'], 'detail' => $attemptCount.' lượt làm', 'tone' => 'var(--success)', 'href' => route('admin.quizzes'), 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>'],
    ['label' => 'Bài tập', 'value' => $stats['assignments'], 'detail' => $stats['submissions'].' bài nộp', 'tone' => 'var(--warning)', 'href' => route('admin.assignments'), 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>'],
    ['label' => 'Hỗ trợ đang mở', 'value' => $stats['tickets'], 'detail' => 'Cần xử lý', 'tone' => 'var(--destructive)', 'href' => route('admin.tickets', ['status' => 'open']), 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'],
    ['label' => 'VIP hoạt động', 'value' => $stats['vip'], 'detail' => 'Đang hiệu lực', 'tone' => '#eab308', 'href' => route('admin.vip'), 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'],
    ['label' => 'Điểm trung bình', 'value' => $avgScore !== null ? round($avgScore, 1) : '—', 'detail' => 'Tất cả điểm', 'tone' => 'var(--accent)', 'href' => route('admin.grades'), 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>'],
  ];
@endphp

@push('styles')
<style>
  .admin-dashboard-stats {
    display:grid;
    grid-template-columns:repeat(12,minmax(0,1fr));
    gap:1rem;
  }
  .admin-dashboard-stat {
    --stat-tone:var(--primary);
    grid-column:span 3;
    min-height:9rem;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:1rem;
    padding:1.25rem;
    border:1px solid color-mix(in srgb,var(--stat-tone) 24%,var(--border));
    border-radius:var(--radius-lg);
    background:
      linear-gradient(135deg,color-mix(in srgb,var(--stat-tone) 8%,transparent),transparent 48%),
      var(--card);
    color:inherit;
    text-decoration:none;
    box-shadow:var(--shadow-sm);
    transition:transform var(--transition-fast), box-shadow var(--transition-fast), border-color var(--transition-fast);
    overflow:hidden;
    position:relative;
  }
  .admin-dashboard-stat:nth-child(n+5) { grid-column:span 4; }
  .admin-dashboard-stat:hover {
    transform:translateY(-2px);
    box-shadow:var(--shadow-md);
    border-color:var(--stat-tone);
  }
  .admin-dashboard-stat__body { min-width:0; display:flex; flex-direction:column; gap:.45rem; }
  .admin-dashboard-stat__label {
    color:var(--muted-foreground);
    font-size:var(--text-sm);
    font-weight:750;
  }
  .admin-dashboard-stat__value {
    color:var(--stat-tone);
    font-size:clamp(2rem,3vw,2.55rem);
    font-weight:950;
    line-height:.9;
    letter-spacing:0;
  }
  .admin-dashboard-stat__detail {
    color:var(--muted-foreground);
    font-size:var(--text-sm);
    font-weight:650;
    overflow-wrap:anywhere;
  }
  .admin-dashboard-stat__icon {
    width:2.75rem;
    height:2.75rem;
    flex:0 0 auto;
    display:grid;
    place-items:center;
    border-radius:var(--radius-md);
    background:color-mix(in srgb,var(--stat-tone) 13%,transparent);
    color:var(--stat-tone);
  }
  .admin-dashboard-stat__icon svg { width:1.35rem; height:1.35rem; }
  @media (max-width:1200px) {
    .admin-dashboard-stat,
    .admin-dashboard-stat:nth-child(n+5) { grid-column:span 6; }
  }
  @media (max-width:680px) {
    .admin-dashboard-stat,
    .admin-dashboard-stat:nth-child(n+5) { grid-column:1 / -1; min-height:0; }
  }
</style>
@endpush

@section('content')
<section class="admin-hero">
  <div>
    <h2>Quản trị VietQuiz</h2>
    <p>Kiểm soát người dùng, lớp học, bài kiểm tra, bài tập, yêu cầu hỗ trợ, VIP và trạng thái hệ thống.</p>
  </div>
  <div class="admin-table-actions">
    <a href="{{ route('admin.tickets') }}" class="btn btn-outline">Xử lý hỗ trợ</a>
    <a href="{{ route('admin.users') }}" class="btn btn-primary">Quản lý người dùng</a>
  </div>
</section>

<section class="admin-dashboard-stats stagger-children">
  @foreach($mainStats as $card)
    <a href="{{ $card['href'] }}" class="admin-dashboard-stat" style="--stat-tone:{{ $card['tone'] }}">
      <span class="admin-dashboard-stat__body">
        <span class="admin-dashboard-stat__label">{{ $card['label'] }}</span>
        <span class="admin-dashboard-stat__value">{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</span>
        <span class="admin-dashboard-stat__detail">{{ $card['detail'] }}</span>
      </span>
      <span class="admin-dashboard-stat__icon">{!! $card['icon'] !!}</span>
    </a>
  @endforeach
</section>

<section class="card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Nhịp vận hành</h3>
      <p class="card-description">Số liệu tăng trưởng lấy trực tiếp từ dữ liệu hệ thống.</p>
    </div>
  </div>
  <div class="table-wrapper" style="border:none;border-radius:0;">
    <table>
      <thead>
        <tr><th>Khoảng thời gian</th><th>Người dùng</th><th>Quiz mới</th><th>Bài nộp</th><th>Ticket</th><th>Doanh thu VIP</th></tr>
      </thead>
      <tbody>
        <tr>
          <td><span class="badge badge-outline">Hôm nay</span></td>
          <td>{{ number_format($growth['today']['users']) }}</td>
          <td>{{ number_format($growth['today']['quizzes']) }}</td>
          <td>{{ number_format($growth['today']['submissions']) }}</td>
          <td>{{ number_format($growth['today']['tickets']) }}</td>
          <td>—</td>
        </tr>
        <tr>
          <td><span class="badge badge-outline">7 ngày</span></td>
          <td>{{ number_format($growth['seven_days']['users']) }}</td>
          <td>{{ number_format($growth['seven_days']['quizzes']) }}</td>
          <td>{{ number_format($growth['seven_days']['submissions']) }}</td>
          <td>{{ number_format($growth['seven_days']['tickets']) }}</td>
          <td>{{ number_format($growth['seven_days']['revenue']) }}đ</td>
        </tr>
        <tr>
          <td><span class="badge badge-outline">30 ngày</span></td>
          <td>{{ number_format($growth['thirty_days']['users']) }}</td>
          <td>{{ number_format($growth['thirty_days']['quizzes']) }}</td>
          <td>{{ number_format($growth['thirty_days']['submissions']) }}</td>
          <td>{{ number_format($growth['thirty_days']['tickets']) }}</td>
          <td>{{ number_format($growth['thirty_days']['revenue']) }}đ</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<div class="admin-grid-2">
  <section class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Cần xử lý</h3>
        <p class="card-description">Ticket mở lâu và giao dịch VIP chưa hoàn tất.</p>
      </div>
      <a class="btn btn-outline btn-sm" href="{{ route('admin.tickets') }}">Mở hỗ trợ</a>
    </div>
    <div class="card-content">
      @forelse($staleTickets as $ticket)
        <div class="activity-item">
          <span class="badge {{ $ticket->priority === 'vip' ? 'badge-warning' : 'badge-info' }}">{{ $ticket->priority === 'vip' ? 'VIP' : \App\Support\AdminLabels::status($ticket->status) }}</span>
          <div style="min-width:0;flex:1;">
            <div class="admin-row-title">{{ $ticket->subject }}</div>
            <div class="admin-row-meta">
              <span>{{ $ticket->user?->email ?? 'Người dùng đã xóa' }}</span>
              <span>{{ $ticket->created_at?->diffForHumans() }}</span>
            </div>
          </div>
        </div>
      @empty
        <div class="empty-state">Không có ticket cần xử lý.</div>
      @endforelse

      @forelse($paymentIssues as $payment)
        <div class="activity-item">
          <span class="badge {{ $payment->status === 'failed' ? 'badge-danger' : 'badge-warning' }}">{{ \App\Support\AdminLabels::status($payment->status) }}</span>
          <div style="min-width:0;flex:1;">
            <div class="admin-row-title">{{ $payment->txn_ref }}</div>
            <div class="admin-row-meta">
              <span>{{ $payment->user?->email ?? 'Người dùng đã xóa' }}</span>
              <span>{{ number_format($payment->amount) }}đ</span>
            </div>
          </div>
          <a class="btn btn-outline btn-sm" href="{{ route('admin.vip') }}">Đối soát</a>
        </div>
      @empty
      @endforelse
    </div>
  </section>

  <section class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Mốc học tập</h3>
        <p class="card-description">Quiz sắp đóng và bài tập đã quá hạn.</p>
      </div>
      <a class="btn btn-outline btn-sm" href="{{ route('admin.quizzes') }}">Mở quiz</a>
    </div>
    <div class="card-content">
      @forelse($closingQuizzes as $quiz)
        <div class="activity-item">
          <span class="badge badge-warning">{{ $quiz->end_at?->format('d/m H:i') }}</span>
          <div style="min-width:0;flex:1;">
            <a class="admin-row-title" href="{{ route('admin.quizzes.show', $quiz->id) }}">{{ $quiz->title }}</a>
            <div class="admin-row-meta">
              <span>{{ $quiz->teacher?->name ?? 'Không rõ giáo viên' }}</span>
              <span>{{ $quiz->questions_count }} câu</span>
            </div>
          </div>
        </div>
      @empty
        <div class="empty-state">Không có quiz sắp đóng trong 7 ngày.</div>
      @endforelse

      @forelse($overdueAssignments as $assignment)
        <div class="activity-item">
          <span class="badge badge-danger">Quá hạn</span>
          <div style="min-width:0;flex:1;">
            <a class="admin-row-title" href="{{ route('admin.assignments.show', $assignment->id) }}">{{ $assignment->title }}</a>
            <div class="admin-row-meta">
              <span>{{ $assignment->class?->name ?? $assignment->course?->name ?? 'Không phạm vi' }}</span>
              <span>{{ $assignment->submissions_count }} bài nộp</span>
            </div>
          </div>
        </div>
      @empty
      @endforelse
    </div>
  </section>
</div>

<div class="admin-grid-3">
  <section class="card">
    <div class="card-header"><h3 class="card-title">Người dùng mới</h3></div>
    <div class="card-content">
      @forelse($recentUsers as $user)
        <div class="activity-item">
          <div class="avatar avatar-sm" style="background:var(--primary);color:#fff;">{{ mb_substr($user->name,0,1) }}</div>
          <div style="min-width:0;">
            <a class="admin-row-title" href="{{ route('admin.users.show', $user->id) }}">{{ $user->name }}</a>
            <div class="admin-row-meta"><span>{{ $user->email }}</span><span>{{ \App\Support\AdminLabels::role($user->role) }}</span></div>
          </div>
        </div>
      @empty
        <div class="empty-state">Chưa có người dùng.</div>
      @endforelse
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3 class="card-title">Yêu cầu hỗ trợ mới</h3></div>
    <div class="card-content">
      @forelse($recentTickets as $ticket)
        <div class="activity-item">
          <span class="badge {{ in_array($ticket->status, ['open','in_progress']) ? 'badge-warning' : 'badge-success' }}">{{ \App\Support\AdminLabels::status($ticket->status) }}</span>
          <div style="min-width:0;">
            <div class="admin-row-title">{{ $ticket->subject }}</div>
            <div class="admin-row-meta"><span>{{ $ticket->user?->email ?? 'Người dùng đã xóa' }}</span></div>
          </div>
        </div>
      @empty
        <div class="empty-state">Không có yêu cầu hỗ trợ.</div>
      @endforelse
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3 class="card-title">Sức khỏe cấu hình</h3></div>
    <div class="card-content">
      @foreach($systemChecks as $check)
        <div class="activity-item">
          <span class="badge {{ $check['ok'] ? 'badge-success' : 'badge-warning' }}">{{ $check['ok'] ? 'OK' : 'Cần cấu hình' }}</span>
          <div style="min-width:0;">
            <div class="admin-row-title">{{ $check['label'] }}</div>
            <div class="admin-row-meta">{{ $check['detail'] }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </section>
</div>
@endsection
