{{-- Student: grades --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $statusLabels = [
    'all' => 'Tất cả',
    'graded' => 'Đã chấm',
    'pending' => 'Chờ chấm',
    'not_submitted' => 'Chưa nộp',
  ];
  $statusCounts = [
    'all' => $summary['total'],
    'graded' => $summary['graded'],
    'pending' => $summary['pending'],
    'not_submitted' => $summary['not_submitted'],
  ];
  $statusBadges = [
    'graded' => ['class' => 'badge-success', 'label' => 'Đã chấm'],
    'pending' => ['class' => 'badge-warning', 'label' => 'Chờ chấm'],
    'not_submitted' => ['class' => 'badge-danger', 'label' => 'Chưa nộp'],
  ];
  $letterClass = fn ($letter) => $letter ? 'grade-' . strtolower($letter) : '';
@endphp

@section('content')
  <div class="page-header stagger-children">
    <div>
      <h1>Điểm số</h1>
      <p style="color:var(--muted-foreground);">Theo dõi kết quả quiz, bài tập và các bài còn thiếu trong lớp học của bạn.</p>
    </div>
  </div>

  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Điểm TB tổng thể</div>
      <div class="stat-card__value" style="color:var(--success);">
        {{ $summary['avg_pct'] !== null ? number_format($summary['avg_pct'], 1) . '%' : '—' }}
      </div>
      <div class="stat-card__label">{{ $summary['letter'] ? 'Xếp loại ' . $summary['letter'] : 'Chưa có điểm đã chấm' }}</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Đã chấm điểm</div>
      <div class="stat-card__value">{{ $summary['graded'] }}</div>
      <div class="stat-card__label">{{ $summary['quiz'] }} quiz, {{ $summary['assignment'] }} bài tập</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Chờ chấm điểm</div>
      <div class="stat-card__value" style="color:var(--warning);">{{ $summary['pending'] }}</div>
      <div class="stat-card__label">bài đã nộp đang chờ giáo viên</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Chưa nộp</div>
      <div class="stat-card__value" style="color:var(--destructive);">{{ $summary['not_submitted'] }}</div>
      <div class="stat-card__label">
        {{ $summary['best_pct'] !== null ? 'Điểm cao nhất ' . number_format($summary['best_pct'], 1) . '%' : 'Cần hoàn thành bài được giao' }}
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:1.5rem;" class="stagger-children">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Hiệu suất theo lớp/khóa học</h3>
        <p class="card-description">Điểm trung bình các bài đã chấm, nhóm theo nơi giao bài.</p>
      </div>
      <div class="card-content">
        @if(count($courseChartData))
          <canvas id="courseChart" height="220"></canvas>
        @else
          <div style="padding:2rem;text-align:center;color:var(--muted-foreground);">Chưa có dữ liệu điểm để vẽ biểu đồ.</div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Xu hướng điểm số</h3>
        <p class="card-description">Trung bình điểm theo ngày chấm hoặc ngày nộp gần nhất.</p>
      </div>
      <div class="card-content">
        @if(count($trendChartData))
          <canvas id="trendChart" height="220"></canvas>
        @else
          <div style="padding:2rem;text-align:center;color:var(--muted-foreground);">Xu hướng sẽ xuất hiện sau khi có điểm đã chấm.</div>
        @endif
      </div>
    </div>
  </div>

  <div class="card stagger-children">
    <div class="card-header">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h3 class="card-title">Bảng điểm của tôi</h3>
          <p class="card-description">Tìm kiếm, lọc và mở nhanh bài làm để xem chi tiết.</p>
        </div>
        <a href="{{ route('student.assignments') }}" class="btn btn-outline btn-sm">Xem bài tập</a>
      </div>
    </div>

    <div class="card-content" style="padding-top:0;">
      <form method="GET" action="{{ route('student.grades') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem;align-items:end;margin-bottom:1rem;">
        <div>
          <label for="q" style="display:block;font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.35rem;">Tìm kiếm</label>
          <input id="q" name="q" value="{{ $filters['q'] }}" class="input" placeholder="Tên bài, khóa học, lớp...">
        </div>
        <div>
          <label for="course_id" style="display:block;font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.35rem;">Khóa học</label>
          <select id="course_id" name="course_id" class="input select">
            <option value="">Tất cả</option>
            @foreach($courses as $course)
              <option value="{{ $course->id }}" @selected($filters['course_id'] === $course->id)>{{ $course->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="class_id" style="display:block;font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.35rem;">Lớp</label>
          <select id="class_id" name="class_id" class="input select">
            <option value="">Tất cả</option>
            @foreach($classes as $class)
              <option value="{{ $class->id }}" @selected($filters['class_id'] === $class->id)>{{ $class->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="type" style="display:block;font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.35rem;">Loại</label>
          <select id="type" name="type" class="input select">
            <option value="all" @selected($filters['type'] === 'all')>Tất cả</option>
            <option value="quiz" @selected($filters['type'] === 'quiz')>Bài kiểm tra</option>
            <option value="assignment" @selected($filters['type'] === 'assignment')>Bài tập</option>
          </select>
        </div>
        <div>
          <label for="status" style="display:block;font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.35rem;">Trạng thái</label>
          <select id="status" name="status" class="input select">
            @foreach($statusLabels as $value => $label)
              <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div style="display:flex;gap:0.5rem;">
          <button type="submit" class="btn btn-primary">Lọc</button>
          <a href="{{ route('student.grades') }}" class="btn btn-ghost">Xóa</a>
        </div>
      </form>

      <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">
        @foreach($statusLabels as $value => $label)
          <a
            href="{{ route('student.grades', array_merge(request()->except(['page', 'status']), ['status' => $value])) }}"
            class="btn btn-sm {{ $filters['status'] === $value ? 'btn-primary' : 'btn-outline' }}"
          >
            {{ $label }} <span style="opacity:.75;">({{ $statusCounts[$value] }})</span>
          </a>
        @endforeach
      </div>
    </div>

    @if($grades->count())
      <div class="table-wrapper" style="border:none;border-radius:0;">
        <table>
          <thead>
            <tr>
              <th>Tên bài</th>
              <th>Lớp/khóa học</th>
              <th>Loại</th>
              <th>Điểm</th>
              <th>Kết quả</th>
              <th>Thời gian</th>
              <th>Trạng thái</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($grades as $grade)
              @php
                $badge = $statusBadges[$grade->status] ?? ['class' => 'badge-outline', 'label' => 'Không rõ'];
              @endphp
              <tr>
                <td>
                  <div style="font-weight:600;">{{ $grade->title }}</div>
                  @if($grade->feedback)
                    <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-top:0.25rem;max-width:28rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                      {{ $grade->feedback }}
                    </div>
                  @endif
                </td>
                <td>
                  <div style="font-weight:500;">{{ $grade->scope_name }}</div>
                  @if($grade->course_name && $grade->class_name)
                    <div style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $grade->class_name }}</div>
                  @endif
                </td>
                <td>
                  <span class="badge {{ $grade->type === 'quiz' ? 'badge-primary' : 'badge-outline' }}">{{ $grade->type_label }}</span>
                </td>
                <td>
                  @if($grade->score !== null)
                    <div style="font-weight:700;">{{ number_format($grade->score, 1) }}/{{ number_format($grade->max_score, 0) }}</div>
                    <div style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ number_format($grade->percentage, 1) }}%</div>
                  @else
                    <span style="color:var(--muted-foreground);">—</span>
                  @endif
                </td>
                <td>
                  @if($grade->letter)
                    <span class="grade-circle {{ $letterClass($grade->letter) }}" style="display:inline-flex;width:2rem;height:2rem;font-size:var(--text-sm);">
                      {{ $grade->letter }}
                    </span>
                  @else
                    <span style="color:var(--muted-foreground);">—</span>
                  @endif
                </td>
                <td style="font-size:var(--text-sm);color:var(--muted-foreground);">{{ $grade->date_label }}</td>
                <td><span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
                <td style="text-align:right;">
                  <a href="{{ $grade->url }}" class="btn btn-outline btn-sm">Chi tiết</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="card-content" style="border-top:1px solid var(--border);">
        {{ $grades->links() }}
      </div>
    @else
      <div class="card-content">
        <div style="padding:3rem 1rem;text-align:center;color:var(--muted-foreground);">
          <div style="font-weight:600;color:var(--foreground);margin-bottom:0.35rem;">Chưa có dòng điểm phù hợp</div>
          <div>Thử xóa bộ lọc hoặc hoàn thành bài kiểm tra, bài tập được giao để bảng điểm cập nhật.</div>
        </div>
      </div>
    @endif
  </div>
@endsection

@push('scripts')
<script>
(function(){
  const courseData = @json($courseChartData);
  const trendData = @json($trendChartData);

  if (typeof Chart === 'undefined') {
    return;
  }

  const courseCanvas = document.getElementById('courseChart');
  if (courseCanvas && courseData.length) {
    new Chart(courseCanvas, {
      type: 'bar',
      data: {
        labels: courseData.map(item => item.label),
        datasets: [{
          label: 'Điểm TB',
          data: courseData.map(item => item.average),
          backgroundColor: ['#2563eb', '#16a34a', '#f97316', '#7c3aed', '#0891b2', '#dc2626', '#4b5563', '#ca8a04'],
          borderRadius: 6
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              afterLabel: function(context) {
                const item = courseData[context.dataIndex];
                return item.count + ' mục đã chấm';
              }
            }
          }
        },
        scales: { x: { beginAtZero: true, max: 100 } }
      }
    });
  }

  const trendCanvas = document.getElementById('trendChart');
  if (trendCanvas && trendData.length) {
    new Chart(trendCanvas, {
      type: 'line',
      data: {
        labels: trendData.map(item => item.label),
        datasets: [{
          label: 'Điểm TB',
          data: trendData.map(item => item.average),
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37,99,235,0.08)',
          fill: true,
          tension: 0.35,
          pointRadius: 4
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100 } }
      }
    });
  }
})();
</script>
@endpush
