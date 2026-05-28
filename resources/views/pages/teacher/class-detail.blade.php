{{-- Teacher: class-detail --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $students = $studentGrades ?? collect();
  $assignments = ($class->assignments ?? collect())->sortByDesc('created_at')->values();
  $courses = ($class->courses ?? collect())->sortBy('name')->values();
  $pendingStudents = $pendingStudents ?? collect();
  $classAvgDisplay = $classAvg !== null ? round($classAvg, 1).'%' : '—';
  $status = $class->status ?? 'active';
  $joinLink = url('/student/join/' . strtolower($class->code));
  $submittedQuizCount = $quizzes->sum('submitted_count');
  $assignmentSubmissionCount = $assignments->sum(fn ($assignment) => $assignment->submissions?->count() ?? 0);
  $gradedStudents = $students->filter(fn ($student) => $student->avg_pct !== null)->count();
  $gradeBuckets = [
      'excellent' => ['label' => 'Giỏi', 'range' => '>= 90%', 'value' => $dist['excellent'] ?? 0, 'color' => 'var(--success)'],
      'good' => ['label' => 'Khá', 'range' => '70-89%', 'value' => $dist['good'] ?? 0, 'color' => '#0891b2'],
      'average' => ['label' => 'Trung bình', 'range' => '50-69%', 'value' => $dist['average'] ?? 0, 'color' => 'var(--warning)'],
      'weak' => ['label' => 'Cần hỗ trợ', 'range' => '< 50%', 'value' => $dist['weak'] ?? 0, 'color' => 'var(--destructive)'],
  ];
  $bucketMax = max(1, collect($gradeBuckets)->max('value'));
  $gradeColor = function (?float $score) {
      if ($score === null) return 'var(--muted-foreground)';
      if ($score >= 80) return 'var(--success)';
      if ($score >= 60) return '#0891b2';
      if ($score >= 40) return 'var(--warning)';
      return 'var(--destructive)';
  };
@endphp

@push('styles')
<style>
  .class-detail-page{display:flex;flex-direction:column;gap:1rem}
  .class-breadcrumb{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;font-size:var(--text-sm);color:var(--muted-foreground)}
  .class-breadcrumb a{color:var(--primary);font-weight:700;text-decoration:none}
  .class-hero{border:1px solid var(--border);border-radius:var(--radius-xl);background:var(--card);overflow:hidden}
  .class-hero__banner{padding:1.1rem 1.25rem;background:linear-gradient(135deg,var(--primary),#4f46e5);color:#fff;display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap}
  .class-identity{display:flex;gap:.9rem;align-items:flex-start;min-width:0}
  .class-avatar{width:3.25rem;height:3.25rem;border-radius:var(--radius-lg);background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:1.35rem;font-weight:900;flex-shrink:0}
  .class-title{font-size:var(--text-2xl);font-weight:900;line-height:1.16;margin:0 0 .45rem;word-break:break-word}
  .class-meta{display:flex;gap:.4rem;flex-wrap:wrap}
  .class-meta .badge{background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.28)}
  .class-actions{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
  .class-actions .btn-outline{background:rgba(255,255,255,.08);color:#fff;border-color:rgba(255,255,255,.35)}
  .class-hero__body{padding:1rem 1.25rem;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:1rem;align-items:center}
  .class-description{margin:0;color:var(--muted-foreground);line-height:1.6}
  .join-box{display:flex;align-items:center;gap:.5rem;background:var(--muted);border:1px solid var(--border);border-radius:var(--radius-md);padding:.55rem .65rem;min-width:min(100%,24rem)}
  .join-box code{font-size:var(--text-sm);font-weight:800;color:var(--foreground)}
  .kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}
  .kpi-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:.85rem .95rem}
  .kpi-label{font-size:var(--text-xs);font-weight:800;text-transform:uppercase;color:var(--muted-foreground);letter-spacing:0}
  .kpi-value{font-size:var(--text-2xl);font-weight:900;margin-top:.35rem;line-height:1}
  .kpi-sub{font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.35rem}
  .tabs-card{border:1px solid var(--border);border-radius:var(--radius-xl);background:var(--card);overflow:hidden}
  .class-tabs{display:flex;gap:.15rem;overflow-x:auto;border-bottom:1px solid var(--border);padding:0 .45rem;background:color-mix(in srgb,var(--muted) 45%,transparent)}
  .class-tab{border:0;background:transparent;color:var(--muted-foreground);font-weight:800;font-size:var(--text-sm);padding:.9rem .85rem;display:flex;align-items:center;gap:.45rem;white-space:nowrap;cursor:pointer;border-bottom:2px solid transparent}
  .class-tab:hover{color:var(--foreground)}
  .class-tab.active{color:var(--primary);border-bottom-color:var(--primary);background:var(--card)}
  .tab-count{font-size:var(--text-xs);font-weight:800;border:1px solid var(--border);border-radius:999px;padding:.08rem .45rem;background:var(--background);color:var(--muted-foreground)}
  .class-panel{display:none;padding:1rem}
  .class-panel.active{display:block}
  .panel-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.9rem}
  .panel-title{font-size:var(--text-lg);font-weight:900;margin:0}
  .panel-sub{font-size:var(--text-sm);color:var(--muted-foreground);margin:.2rem 0 0}
  .panel-actions{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
  .search-field{position:relative;min-width:230px}
  .search-field svg{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted-foreground);pointer-events:none}
  .search-field input{padding-left:2.35rem!important}
  .student-list,.content-list{display:flex;flex-direction:column;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
  .student-row,.content-row{display:grid;grid-template-columns:minmax(0,1.25fr) auto auto auto;gap:.75rem;align-items:center;padding:.8rem .9rem;border-bottom:1px solid var(--border);background:var(--card)}
  .student-row:last-child,.content-row:last-child{border-bottom:0}
  .student-main{display:flex;align-items:center;gap:.7rem;min-width:0}
  .student-avatar{width:2.25rem;height:2.25rem;border-radius:999px;background:var(--muted);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:var(--text-xs);flex-shrink:0}
  .student-name,.content-title{font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .row-meta{font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.15rem}
  .score-pill{font-weight:900;min-width:4rem;text-align:right}
  .request-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.75rem;margin-bottom:1rem}
  .request-card{border:1px solid color-mix(in srgb,var(--warning) 32%,var(--border));background:color-mix(in srgb,var(--warning) 7%,var(--card));border-radius:var(--radius-lg);padding:.85rem}
  .request-card__actions{display:flex;gap:.45rem;margin-top:.65rem;flex-wrap:wrap}
  .analytics-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,.75fr);gap:1rem}
  .mini-card{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);padding:1rem}
  .rank-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.55rem 0;border-bottom:1px solid var(--border)}
  .rank-row:last-child{border-bottom:0}
  .dist-row{display:grid;grid-template-columns:6.5rem minmax(0,1fr) 2rem;gap:.6rem;align-items:center;margin:.65rem 0}
  .dist-label{font-size:var(--text-xs);font-weight:800;color:var(--muted-foreground)}
  .dist-bar{height:.55rem;border-radius:999px;background:var(--muted);overflow:hidden}
  .dist-fill{height:100%;border-radius:999px}
  .settings-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,.75fr);gap:1rem}
  .settings-form{display:flex;flex-direction:column;gap:.85rem}
  .form-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}
  .danger-box{border:1px solid color-mix(in srgb,var(--destructive) 24%,var(--border));background:color-mix(in srgb,var(--destructive) 5%,transparent);border-radius:var(--radius-lg);padding:1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
  .empty-state{text-align:center;padding:2.75rem 1rem;color:var(--muted-foreground);border:1px dashed var(--border);border-radius:var(--radius-lg);background:color-mix(in srgb,var(--muted) 35%,transparent)}
  .modal-tabs{display:flex;gap:.5rem;border-bottom:1px solid var(--border);padding-bottom:.75rem;margin-bottom:1rem;flex-wrap:wrap}
  .modal-tab{border:0;background:transparent;border-radius:var(--radius-md);padding:.5rem .75rem;color:var(--muted-foreground);font-weight:800;cursor:pointer}
  .modal-tab.active{background:var(--primary);color:var(--primary-foreground)}
  .modal-panel{display:none}
  .modal-panel.active{display:block}
  .invite-link-box{display:flex;align-items:center;gap:.5rem;background:var(--muted);border-radius:var(--radius-md);padding:.7rem;font-size:var(--text-sm);word-break:break-all}
  .invite-link-box span{flex:1}
  @media(max-width:1050px){.kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.analytics-grid,.settings-grid{grid-template-columns:1fr}.class-hero__body{grid-template-columns:1fr}.student-row,.content-row{grid-template-columns:1fr}.score-pill{text-align:left}.join-box{width:100%}}
  @media(max-width:640px){.class-hero__banner{align-items:stretch}.class-actions,.panel-actions{width:100%}.class-actions .btn,.panel-actions .btn,.panel-actions form{flex:1}.form-grid-2{grid-template-columns:1fr}.kpi-grid{grid-template-columns:1fr}.search-field{width:100%}.search-field input{width:100%}}
</style>
@endpush

@section('content')
<div class="class-detail-page">
  <nav class="class-breadcrumb">
    <a href="{{ route('teacher.classes') }}">Lớp của tôi</a>
    <span>/</span>
    <span>{{ $class->name }}</span>
  </nav>

  @foreach(['success' => 'alert-success', 'warning' => 'alert-warning', 'error' => 'alert-danger', 'info' => 'alert-info'] as $flash => $className)
    @if(session($flash))
      <div class="alert {{ $className }}"><span>{{ session($flash) }}</span></div>
    @endif
  @endforeach
  @if($errors->any())
    <div class="alert alert-danger"><span>{{ $errors->first() }}</span></div>
  @endif

  <section class="class-hero">
    <div class="class-hero__banner">
      <div class="class-identity">
        <div class="class-avatar">{{ mb_substr($class->name, 0, 1) }}</div>
        <div>
          <h1 class="class-title">{{ $class->name }}</h1>
          <div class="class-meta">
            <span class="badge">{{ $status === 'archived' ? 'Đã lưu trữ' : 'Đang hoạt động' }}</span>
            @if($class->subject)<span class="badge">{{ $class->subject }}</span>@endif
            @if($class->grade_level)<span class="badge">Khối {{ $class->grade_level }}</span>@endif
            <span class="badge">Mã lớp: {{ $class->code }}</span>
          </div>
        </div>
      </div>
      <div class="class-actions">
        <button class="btn btn-outline btn-sm" type="button" onclick="copyText(@js($class->code), 'Đã sao chép mã lớp')">Sao chép mã</button>
        <button class="btn btn-outline btn-sm" type="button" onclick="openInviteModal('link')">Chia sẻ lớp</button>
        <button class="btn btn-outline btn-sm" type="button" onclick="openNotifyModal()">Gửi thông báo</button>
        <a class="btn btn-primary btn-sm" href="{{ route('teacher.quiz-create', ['class_id' => $class->id]) }}">Giao bài kiểm tra</a>
      </div>
    </div>
    <div class="class-hero__body">
      <p class="class-description">{{ $class->description ?: 'Chưa có mô tả cho lớp học này.' }}</p>
      <div class="join-box">
        <span style="font-size:var(--text-xs);color:var(--muted-foreground);font-weight:800;">Link mời</span>
        <code>{{ $class->code }}</code>
        <button class="btn btn-outline btn-sm" type="button" onclick="copyText(@js($joinLink), 'Đã sao chép link mời')">Copy link</button>
      </div>
    </div>
  </section>

  <div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Học sinh</div><div class="kpi-value">{{ $studentCount }}</div><div class="kpi-sub">{{ $pendingStudents->count() }} yêu cầu chờ duyệt</div></div>
    <div class="kpi-card"><div class="kpi-label">Điểm trung bình</div><div class="kpi-value" style="color:{{ $gradeColor($classAvg) }}">{{ $classAvgDisplay }}</div><div class="kpi-sub">{{ $gradedStudents }} học sinh có điểm</div></div>
    <div class="kpi-card"><div class="kpi-label">Bài kiểm tra</div><div class="kpi-value">{{ $quizzes->count() }}</div><div class="kpi-sub">{{ $submittedQuizCount }} lượt nộp</div></div>
    <div class="kpi-card"><div class="kpi-label">Bài tập</div><div class="kpi-value">{{ $assignments->count() }}</div><div class="kpi-sub">{{ $assignmentSubmissionCount }} bài nộp · {{ $completionRate }}% hoàn thành</div></div>
  </div>

  <section class="tabs-card">
    <div class="class-tabs">
      <button class="class-tab active" type="button" data-tab="students">Học sinh <span class="tab-count">{{ $studentCount }}</span></button>
      <button class="class-tab" type="button" data-tab="content">Nội dung <span class="tab-count">{{ $quizzes->count() + $assignments->count() }}</span></button>
      <button class="class-tab" type="button" data-tab="analytics">Phân tích</button>
      <button class="class-tab" type="button" data-tab="settings">Cài đặt</button>
    </div>

    <div class="class-panel active" id="panel-students">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Danh sách học sinh</h2>
          <p class="panel-sub">Quản lý học sinh đã tham gia và các yêu cầu chờ duyệt.</p>
        </div>
        <div class="panel-actions">
          <div class="search-field">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="student-search" class="input" type="search" placeholder="Tìm học sinh...">
          </div>
          <button class="btn btn-primary btn-sm" type="button" onclick="openInviteModal('email')">Thêm học sinh</button>
          <a class="btn btn-outline btn-sm" href="{{ route('teacher.classes.export', $class) }}">Xuất Excel</a>
        </div>
      </div>

      @if($pendingStudents->isNotEmpty())
        <div class="request-list">
          @foreach($pendingStudents as $pendingStudent)
            <div class="request-card">
              <div class="student-main">
                <div class="student-avatar">{{ mb_substr($pendingStudent->name, 0, 1) }}</div>
                <div style="min-width:0">
                  <div class="student-name">{{ $pendingStudent->name }}</div>
                  <div class="row-meta">{{ $pendingStudent->email }} · {{ $pendingStudent->pivot->requested_at ? \Illuminate\Support\Carbon::parse($pendingStudent->pivot->requested_at)->diffForHumans() : 'Đang chờ' }}</div>
                </div>
              </div>
              <div class="request-card__actions">
                <form method="POST" action="{{ route('teacher.classes.join-requests.approve', [$class, $pendingStudent->id]) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Duyệt</button></form>
                <form method="POST" action="{{ route('teacher.classes.join-requests.reject', [$class, $pendingStudent->id]) }}" data-confirm="Từ chối yêu cầu tham gia lớp của {{ $pendingStudent->name }}?">@csrf @method('DELETE')<button class="btn btn-outline btn-sm" type="submit">Từ chối</button></form>
              </div>
            </div>
          @endforeach
        </div>
      @endif

      @if($students->isNotEmpty())
        <div class="student-list">
          @foreach($students as $index => $student)
            <div class="student-row student-search-row" data-search="{{ mb_strtolower($student->name.' '.$student->email) }}">
              <div class="student-main">
                <div class="student-avatar">{{ mb_substr($student->name, 0, 1) }}</div>
                <div style="min-width:0">
                  <div class="student-name">{{ $student->name }}</div>
                  <div class="row-meta">{{ $student->email }}</div>
                </div>
              </div>
              <div><span class="badge badge-outline">{{ $student->completed_count }} bài đã nộp</span></div>
              <div class="score-pill" style="color:{{ $gradeColor($student->avg_pct) }}">{{ $student->avg_pct !== null ? $student->avg_pct.'%' : '—' }}</div>
              <form method="POST" action="{{ route('teacher.classes.remove-student', [$class, $student->id]) }}" data-confirm="Xóa {{ $student->name }} khỏi lớp?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" style="color:var(--destructive)" type="submit">Gỡ</button></form>
            </div>
          @endforeach
        </div>
      @else
        <div class="empty-state">
          <h3>Chưa có học sinh</h3>
          <p>Mời học sinh bằng email, link tham gia hoặc import danh sách để bắt đầu quản lý lớp.</p>
          <button class="btn btn-primary" type="button" onclick="openInviteModal('email')">Thêm học sinh</button>
        </div>
      @endif
    </div>

    <div class="class-panel" id="panel-content">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Nội dung của lớp</h2>
          <p class="panel-sub">Bài kiểm tra, bài tập và khóa học đang gắn với lớp này.</p>
        </div>
        <div class="panel-actions">
          <a class="btn btn-primary btn-sm" href="{{ route('teacher.quiz-create', ['class_id' => $class->id]) }}">Tạo bài kiểm tra</a>
          <button class="btn btn-outline btn-sm" type="button" onclick="openAssignmentModal()">Tạo bài tập</button>
        </div>
      </div>

      <div class="content-list" style="margin-bottom:1rem">
        @forelse($quizzes as $quiz)
          <div class="content-row">
            <div>
              <div class="content-title">{{ $quiz->title }}</div>
              <div class="row-meta">Bài kiểm tra · {{ $quiz->questions_count }} câu · {{ $quiz->submitted_count }} lượt nộp</div>
            </div>
            <span class="badge {{ $quiz->status === 'published' ? 'badge-success' : 'badge-outline' }}">{{ $quiz->status === 'published' ? 'Đã xuất bản' : 'Nháp' }}</span>
            <div class="score-pill" style="color:{{ $gradeColor($quiz->avg_score) }}">{{ $quiz->avg_score !== null ? $quiz->avg_score.'%' : '—' }}</div>
            <a class="btn btn-outline btn-sm" href="{{ route('teacher.quiz-detail', $quiz) }}">Xem</a>
          </div>
        @empty
          <div class="empty-state" style="border:0;border-radius:0">Chưa có bài kiểm tra trong lớp này.</div>
        @endforelse
      </div>

      <div class="content-list" style="margin-bottom:1rem">
        @forelse($assignments as $assignment)
          <div class="content-row">
            <div>
              <div class="content-title">{{ $assignment->title }}</div>
              <div class="row-meta">Bài tập · {{ $assignment->total_points ?? 100 }} điểm · {{ $assignment->due_at ? 'Hạn '.$assignment->due_at->format('d/m/Y H:i') : 'Không hạn nộp' }}</div>
            </div>
            <span class="badge badge-outline">{{ $assignment->type ?? 'file' }}</span>
            <div class="score-pill">{{ $assignment->submissions?->count() ?? 0 }} nộp</div>
            <a class="btn btn-outline btn-sm" href="{{ route('teacher.assignments.show', $assignment) }}">Xem</a>
          </div>
        @empty
          <div class="empty-state" style="border:0;border-radius:0">Chưa có bài tập trong lớp này.</div>
        @endforelse
      </div>

      @if($courses->isNotEmpty())
        <div class="content-list">
          @foreach($courses as $course)
            <div class="content-row">
              <div>
                <div class="content-title">{{ $course->name }}</div>
                <div class="row-meta">Khóa học · {{ $course->status === 'published' ? 'Đã xuất bản' : 'Nháp' }}</div>
              </div>
              <span class="badge badge-default">Khóa học</span>
              <span></span>
              <a class="btn btn-outline btn-sm" href="{{ route('teacher.courses.show', $course) }}">Mở</a>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <div class="class-panel" id="panel-analytics">
      <div class="analytics-grid">
        <div class="mini-card">
          <h2 class="panel-title" style="margin-bottom:.8rem">Phân phối điểm</h2>
          @foreach($gradeBuckets as $bucket)
            @php $width = round(($bucket['value'] / $bucketMax) * 100); @endphp
            <div class="dist-row">
              <div class="dist-label">{{ $bucket['label'] }}<div class="row-meta">{{ $bucket['range'] }}</div></div>
              <div class="dist-bar"><div class="dist-fill" style="width:{{ $width }}%;background:{{ $bucket['color'] }}"></div></div>
              <strong>{{ $bucket['value'] }}</strong>
            </div>
          @endforeach
        </div>
        <div class="mini-card">
          <h2 class="panel-title" style="margin-bottom:.8rem">Học sinh nổi bật</h2>
          @forelse($topStudents as $student)
            <div class="rank-row"><span>{{ $student->name }}</span><strong style="color:{{ $gradeColor($student->avg_pct) }}">{{ $student->avg_pct }}%</strong></div>
          @empty
            <p class="panel-sub">Chưa có dữ liệu điểm.</p>
          @endforelse
        </div>
        <div class="mini-card">
          <h2 class="panel-title" style="margin-bottom:.8rem">Cần hỗ trợ</h2>
          @forelse($weakStudents as $student)
            <div class="rank-row"><span>{{ $student->name }}</span><strong style="color:var(--destructive)">{{ $student->avg_pct }}%</strong></div>
          @empty
            <p class="panel-sub">Không có học sinh dưới ngưỡng 60%.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div class="class-panel" id="panel-settings">
      <div class="settings-grid">
        <form class="mini-card settings-form" method="POST" action="{{ route('teacher.classes.update', $class) }}">
          @csrf
          @method('PUT')
          <h2 class="panel-title">Thông tin lớp</h2>
          <div class="form-group"><label class="label label-required">Tên lớp</label><input class="input" name="name" value="{{ old('name', $class->name) }}" required></div>
          <div class="form-group"><label class="label">Mô tả</label><textarea class="input" name="description" style="min-height:5rem;resize:vertical;">{{ old('description', $class->description) }}</textarea></div>
          <div class="form-grid-2">
            <div class="form-group"><label class="label">Môn học</label><input class="input" name="subject" value="{{ old('subject', $class->subject) }}"></div>
            <div class="form-group"><label class="label">Khối lớp</label><input class="input" name="grade_level" value="{{ old('grade_level', $class->grade_level) }}"></div>
          </div>
          <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary" type="submit">Lưu thay đổi</button></div>
        </form>
        <div class="mini-card">
          <h2 class="panel-title" style="margin-bottom:.8rem">Trạng thái lớp</h2>
          @if($status === 'archived')
            <p class="panel-sub">Lớp đang được lưu trữ. Học sinh sẽ không thấy lớp như lớp đang hoạt động.</p>
            <form method="POST" action="{{ route('teacher.classes.restore', $class) }}" style="margin-top:.9rem">@csrf<button class="btn btn-outline" type="submit">Khôi phục lớp</button></form>
          @else
            <p class="panel-sub">Lớp đang hoạt động và học sinh có thể tham gia bằng mã/link mời.</p>
            <form method="POST" action="{{ route('teacher.classes.archive', $class) }}" data-confirm="Lưu trữ lớp này?" style="margin-top:.9rem">@csrf<button class="btn btn-outline" type="submit">Lưu trữ lớp</button></form>
          @endif
          <div class="danger-box" style="margin-top:1rem">
            <div><strong style="color:var(--destructive)">Xóa lớp</strong><div class="row-meta">Đưa lớp vào thùng rác. Có thể khôi phục trong trang thùng rác nếu cần.</div></div>
            <form method="POST" action="{{ route('teacher.classes.destroy', $class) }}" data-confirm="Xóa lớp {{ $class->name }}?">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm" type="submit">Xóa</button></form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal-overlay" id="invite-modal">
  <div class="modal" style="max-width:38rem">
    <div class="modal-header">
      <div><h3 class="modal-title">Thêm học sinh</h3><p class="modal-desc">Mời học sinh vào lớp {{ $class->name }}.</p></div>
      <button class="modal-close" type="button" onclick="closeModal('invite-modal')">×</button>
    </div>
    <div class="modal-body">
      <div class="modal-tabs">
        <button class="modal-tab active" type="button" data-invite-tab="email">Email</button>
        <button class="modal-tab" type="button" data-invite-tab="link">Link tham gia</button>
        <button class="modal-tab" type="button" data-invite-tab="file">Import file</button>
      </div>
      <div class="modal-panel active" id="invite-email-panel">
        <form method="POST" action="{{ route('teacher.students.invite-email') }}">
          @csrf
          <input type="hidden" name="class_id" value="{{ $class->id }}">
          <div class="form-group"><label class="label label-required">Email học sinh</label><textarea class="input" name="emails_raw" rows="7" placeholder="mỗi email một dòng hoặc phân cách bằng dấu phẩy" required>{{ old('emails_raw') }}</textarea></div>
          <div class="modal-footer" style="padding-left:0;padding-right:0"><button class="btn btn-outline" type="button" onclick="closeModal('invite-modal')">Hủy</button><button class="btn btn-primary" type="submit">Thêm vào lớp</button></div>
        </form>
      </div>
      <div class="modal-panel" id="invite-link-panel">
        <label class="label">Link tham gia lớp</label>
        <div class="invite-link-box"><span id="join-link">{{ $joinLink }}</span><button class="btn btn-outline btn-sm" type="button" onclick="copyText(@js($joinLink), 'Đã sao chép link mời')">Sao chép</button></div>
        <p class="panel-sub" style="margin-top:.65rem">Học sinh đăng nhập tài khoản học sinh rồi mở link này để gửi yêu cầu tham gia hoặc vào lớp.</p>
        <form method="POST" action="{{ route('teacher.students.invite-link', $class) }}" style="margin-top:.9rem">@csrf<button class="btn btn-outline btn-sm" type="submit">Tạo mã mới</button></form>
      </div>
      <div class="modal-panel" id="invite-file-panel">
        <a class="btn btn-ghost btn-sm" href="{{ route('teacher.classes.template', $class) }}" style="margin-bottom:.8rem">Tải file mẫu Excel</a>
        <form method="POST" action="{{ route('teacher.classes.import', $class) }}" enctype="multipart/form-data">
          @csrf
          <div class="form-group"><label class="label label-required">File danh sách</label><input class="input" type="file" name="students_file" accept=".xlsx,.csv,.txt" required></div>
          <div class="modal-footer" style="padding-left:0;padding-right:0"><button class="btn btn-outline" type="button" onclick="closeModal('invite-modal')">Hủy</button><button class="btn btn-primary" type="submit">Import</button></div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="notify-modal">
  <div class="modal" style="max-width:36rem">
    <div class="modal-header">
      <div><h3 class="modal-title">Gửi thông báo lớp</h3><p class="modal-desc">Thông báo sẽ gửi đến {{ $studentCount }} học sinh trong lớp.</p></div>
      <button class="modal-close" type="button" onclick="closeModal('notify-modal')">×</button>
    </div>
    <form method="POST" action="{{ route('teacher.classes.notify', $class) }}">
      @csrf
      <div class="modal-body">
        <div class="form-group"><label class="label label-required">Tiêu đề</label><input class="input" name="title" required maxlength="255"></div>
        <div class="form-group"><label class="label label-required">Nội dung</label><textarea class="input" name="body" rows="5" required maxlength="500"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('notify-modal')">Hủy</button><button class="btn btn-primary" type="submit">Gửi</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="assignment-modal">
  <div class="modal" style="max-width:38rem">
    <div class="modal-header">
      <div><h3 class="modal-title">Tạo bài tập nhanh</h3><p class="modal-desc">Bài tập sẽ được gắn với lớp {{ $class->name }}.</p></div>
      <button class="modal-close" type="button" onclick="closeModal('assignment-modal')">×</button>
    </div>
    <form method="POST" action="{{ route('teacher.assignments.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="class_id" value="{{ $class->id }}">
      <div class="modal-body">
        <div class="form-group"><label class="label label-required">Tiêu đề</label><input class="input" name="title" required maxlength="255"></div>
        <div class="form-group"><label class="label">Mô tả</label><textarea class="input" name="description" rows="4" maxlength="2000"></textarea></div>
        <div class="form-grid-2">
          <div class="form-group"><label class="label">Hình thức nộp</label><select class="input select" name="type"><option value="file">Nộp file</option><option value="text">Nhập văn bản</option><option value="essay">Tự luận</option><option value="project">Dự án</option><option value="practice">Thực hành</option></select></div>
          <div class="form-group"><label class="label">Điểm tối đa</label><input class="input" type="number" name="total_points" min="1" max="10000" value="100"></div>
        </div>
        <div class="form-grid-2">
          <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Không gắn khóa học</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->name }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Hạn nộp</label><input class="input" type="datetime-local" name="due_at" min="{{ now()->format('Y-m-d\TH:i') }}"></div>
        </div>
        <div class="form-group"><label class="label">Tài liệu đính kèm</label><input class="input" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.png,.jpg,.jpeg,.webp,.txt"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('assignment-modal')">Hủy</button><button class="btn btn-primary" type="submit">Tạo bài tập</button></div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
  'use strict';

  function setActiveTab(tab) {
    document.querySelectorAll('.class-tab').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
    document.querySelectorAll('.class-panel').forEach(panel => panel.classList.toggle('active', panel.id === 'panel-' + tab));
  }

  document.querySelectorAll('.class-tab').forEach(btn => {
    btn.addEventListener('click', () => setActiveTab(btn.dataset.tab));
  });

  window.openModal = function(id) {
    document.getElementById(id)?.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  window.closeModal = function(id) {
    document.getElementById(id)?.classList.remove('open');
    document.body.style.overflow = '';
  };

  window.openNotifyModal = function() { openModal('notify-modal'); };
  window.openAssignmentModal = function() { openModal('assignment-modal'); };

  function setInviteTab(tab) {
    document.querySelectorAll('[data-invite-tab]').forEach(btn => btn.classList.toggle('active', btn.dataset.inviteTab === tab));
    ['email', 'link', 'file'].forEach(name => document.getElementById('invite-' + name + '-panel')?.classList.toggle('active', name === tab));
  }

  window.openInviteModal = function(tab) {
    setInviteTab(tab || 'email');
    openModal('invite-modal');
  };

  document.querySelectorAll('[data-invite-tab]').forEach(btn => {
    btn.addEventListener('click', () => setInviteTab(btn.dataset.inviteTab));
  });

  window.copyText = function(text, message) {
    const done = () => {
      if (typeof window.showAppAlert === 'function') {
        window.showAppAlert(message || 'Đã sao chép');
      }
    };
    if (navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(done);
      return;
    }
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
    done();
  };

  document.getElementById('student-search')?.addEventListener('input', function() {
    const query = (this.value || '').trim().toLowerCase();
    document.querySelectorAll('.student-search-row').forEach(row => {
      row.style.display = !query || (row.dataset.search || '').includes(query) ? '' : 'none';
    });
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', event => {
      if (event.target === overlay) closeModal(overlay.id);
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.modal-overlay.open').forEach(overlay => overlay.classList.remove('open'));
    document.body.style.overflow = '';
  });
})();
</script>
@endpush
