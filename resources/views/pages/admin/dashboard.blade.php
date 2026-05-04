@extends('layouts.admin')

@section('title', 'Admin - Tổng quan')
@section('page-title', 'Tổng quan hệ thống')
@section('page-description', 'Theo dõi dữ liệu vận hành, học tập, hỗ trợ và VIP trong một màn hình.')

@php
  $mainStats = [
    ['Người dùng', $stats['users'], $stats['teachers'].' giáo viên · '.$stats['students'].' học sinh', 'var(--primary)', route('admin.users')],
    ['Lớp / khóa', $stats['classes'], $stats['courses'].' khóa học', 'var(--info)', route('admin.classes')],
    ['Bài kiểm tra', $stats['quizzes'], $attemptCount.' lượt làm', 'var(--success)', route('admin.quizzes')],
    ['Bài tập', $stats['assignments'], $stats['submissions'].' bài nộp', 'var(--warning)', route('admin.assignments')],
    ['Hỗ trợ đang mở', $stats['tickets'], 'Cần xử lý', 'var(--destructive)', route('admin.tickets', ['status' => 'open'])],
    ['VIP hoạt động', $stats['vip'], 'Đang hiệu lực', '#eab308', route('admin.vip')],
    ['Điểm trung bình', $avgScore !== null ? round($avgScore, 1) : '—', 'Tất cả điểm', 'var(--accent)', route('admin.grades')],
    ['Phân hệ', 10, 'Đang quản trị', 'var(--primary)', route('admin.system')],
  ];
@endphp

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

<section class="stats-grid stats-grid-4 stagger-children">
  @foreach($mainStats as $card)
    <a href="{{ $card[4] }}" class="stat-card" style="text-decoration:none;color:inherit;">
      <div class="stat-card__label">{{ $card[0] }}</div>
      <div class="stat-card__value" style="color:{{ $card[3] }}">{{ is_numeric($card[1]) ? number_format($card[1]) : $card[1] }}</div>
      <div class="stat-card__label">{{ $card[2] }}</div>
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
