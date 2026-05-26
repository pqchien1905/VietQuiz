{{-- Student: quizzes --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $tabUrl = function (string $status) use ($filters) {
    $params = ['status' => $status];
    if (($filters['q'] ?? '') !== '') {
      $params['q'] = $filters['q'];
    }
    if (!empty($filters['course_id'])) {
      $params['course_id'] = $filters['course_id'];
    }
    if (($filters['type'] ?? 'all') !== 'all') {
      $params['type'] = $filters['type'];
    }

    return route('student.quizzes', $params);
  };

  $hasActiveFilters = ($filters['q'] ?? '') !== ''
    || !empty($filters['course_id'])
    || ($filters['type'] ?? 'all') !== 'all';

  $tabs = [
    'available' => ['label' => 'Cần làm', 'count' => $available->count(), 'tone' => 'primary'],
    'scheduled' => ['label' => 'Chưa mở', 'count' => $scheduled->count(), 'tone' => 'info'],
    'completed' => ['label' => 'Đã làm', 'count' => $completed->count(), 'tone' => 'success'],
    'missed' => ['label' => 'Quá hạn', 'count' => $missed->count(), 'tone' => 'danger'],
  ];

  $activeItems = match ($activeTab) {
    'scheduled' => $scheduled,
    'completed' => $completed,
    'missed' => $missed,
    default => $available,
  };

  $activeTitle = $tabs[$activeTab]['label'] ?? 'Cần làm';
  $avgScore = $summary['avg_score'] !== null ? round($summary['avg_score']) : null;
@endphp

@push('styles')
<style>
  .quiz-page { display:grid; gap:1.25rem; min-width:0; }
  .quiz-hero {
    position:relative;
    overflow:hidden;
    border:1px solid var(--border);
    border-radius:var(--radius-2xl);
    padding:1.4rem;
    background:
      radial-gradient(circle at 12% 8%, color-mix(in srgb,var(--primary) 22%,transparent), transparent 22rem),
      linear-gradient(135deg, color-mix(in srgb,var(--primary) 9%,var(--card)), var(--card) 52%, color-mix(in srgb,var(--info) 8%,var(--card)));
    box-shadow:var(--shadow-sm);
  }
  .quiz-hero::after {
    content:"";
    position:absolute;
    width:18rem;
    height:18rem;
    right:-7rem;
    top:-8rem;
    border-radius:999px;
    background:color-mix(in srgb,var(--primary) 14%,transparent);
    pointer-events:none;
  }
  .quiz-hero-inner { position:relative; z-index:1; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:end; }
  .quiz-eyebrow { display:inline-flex; align-items:center; gap:.45rem; width:max-content; padding:.35rem .65rem; border-radius:999px; background:color-mix(in srgb,var(--primary) 12%,transparent); color:var(--primary); font-size:var(--text-xs); font-weight:900; margin-bottom:.75rem; }
  .quiz-hero h1 { margin:0; font-size:clamp(1.8rem,3vw,2.6rem); letter-spacing:-.05em; line-height:1.05; }
  .quiz-hero p { margin:.55rem 0 0; color:var(--muted-foreground); max-width:46rem; font-size:var(--text-sm); }
  .quiz-hero-action { align-self:center; }
  .quiz-summary-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.85rem; }
  .quiz-stat {
    position:relative;
    overflow:hidden;
    min-height:8.1rem;
    border:1px solid var(--border);
    border-radius:var(--radius-xl);
    padding:1rem;
    background:var(--card);
    box-shadow:var(--shadow-sm);
  }
  .quiz-stat::after { content:""; position:absolute; width:5.5rem; height:5.5rem; right:-2.8rem; top:-2.8rem; border-radius:999px; background:color-mix(in srgb,var(--stat-color,var(--primary)) 13%,transparent); }
  .quiz-stat-label { color:var(--muted-foreground); font-size:var(--text-xs); font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
  .quiz-stat-value { margin-top:.65rem; font-size:2rem; line-height:1; font-weight:950; color:var(--stat-color,var(--foreground)); letter-spacing:-.04em; }
  .quiz-stat-sub { margin-top:.45rem; color:var(--muted-foreground); font-size:var(--text-xs); }
  .quiz-filter-card { border:1px solid var(--border); border-radius:var(--radius-xl); padding:1rem; background:color-mix(in srgb,var(--card) 88%,var(--background)); box-shadow:var(--shadow-sm); }
  .quiz-filter-form { display:grid; grid-template-columns:minmax(220px,1fr) minmax(150px,14rem) minmax(140px,13rem) auto auto; gap:.75rem; align-items:center; }
  .quiz-filter-form .search-input-wrapper { min-width:0; }
  .quiz-filter-form .btn { height:2.5rem; }
  .quiz-tabs { display:flex; gap:.45rem; padding:.35rem; background:var(--muted); border-radius:var(--radius-lg); overflow-x:auto; }
  .quiz-tab { flex:0 0 auto; min-width:9.5rem; display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.65rem .9rem; border:1px solid transparent; border-radius:var(--radius-md); background:transparent; color:var(--muted-foreground); font-size:var(--text-sm); font-weight:900; cursor:pointer; text-decoration:none; white-space:nowrap; }
  .quiz-tab:hover { background:color-mix(in srgb,var(--card) 78%,transparent); color:var(--foreground); text-decoration:none; }
  .quiz-tab.active { background:var(--card); color:var(--foreground); border-color:var(--border); box-shadow:var(--shadow-sm); }
  .quiz-section-head { display:flex; align-items:end; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .quiz-section-head h2 { margin:0; font-size:var(--text-xl); letter-spacing:-.03em; }
  .quiz-section-head p { margin:.2rem 0 0; color:var(--muted-foreground); font-size:var(--text-sm); }
  .quiz-list-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:1rem; }
  .quiz-card {
    position:relative;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    min-height:100%;
    border:1px solid var(--border);
    border-radius:var(--radius-xl);
    background:var(--card);
    box-shadow:var(--shadow-sm);
    transition:transform var(--transition-fast), box-shadow var(--transition-fast), border-color var(--transition-fast);
  }
  .quiz-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-lg); border-color:color-mix(in srgb,var(--primary) 35%,var(--border)); }
  .quiz-card::before { content:""; position:absolute; inset:0 0 auto; height:.35rem; background:linear-gradient(90deg,var(--quiz-tone,var(--primary)),color-mix(in srgb,var(--quiz-tone,var(--primary)) 40%,transparent)); }
  .quiz-card-body { padding:1.1rem; flex:1; display:grid; gap:.95rem; }
  .quiz-card-top { display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:.85rem; align-items:start; }
  .quiz-icon { width:2.85rem; height:2.85rem; border-radius:1rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:color-mix(in srgb,var(--quiz-tone,var(--primary)) 12%,transparent); color:var(--quiz-tone,var(--primary)); }
  .quiz-title { margin:0; font-size:var(--text-lg); font-weight:950; line-height:1.28; letter-spacing:-.03em; }
  .quiz-meta { display:flex; align-items:center; gap:.45rem .7rem; flex-wrap:wrap; color:var(--muted-foreground); font-size:var(--text-xs); margin-top:.35rem; }
  .quiz-meta-item { display:inline-flex; align-items:center; gap:.3rem; min-width:0; }
  .quiz-desc { color:var(--muted-foreground); font-size:var(--text-sm); line-height:1.6; margin:0; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
  .quiz-due { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.75rem; border-radius:var(--radius-lg); background:color-mix(in srgb,var(--quiz-tone,var(--primary)) 8%,transparent); color:var(--quiz-tone,var(--primary)); font-size:var(--text-sm); font-weight:800; }
  .quiz-facts { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.55rem; }
  .quiz-fact { border:1px solid var(--border); border-radius:var(--radius-lg); padding:.7rem .55rem; text-align:center; background:color-mix(in srgb,var(--muted) 28%,transparent); min-width:0; }
  .quiz-fact strong { display:block; font-size:var(--text-base); line-height:1.05; font-weight:950; color:var(--foreground); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .quiz-fact span { display:block; color:var(--muted-foreground); font-size:var(--text-xs); margin-top:.25rem; }
  .quiz-card-footer { padding:.9rem 1.1rem 1.1rem; border-top:1px solid var(--border); background:color-mix(in srgb,var(--muted) 22%,transparent); }
  .quiz-empty { border:1px dashed var(--border); border-radius:var(--radius-xl); background:color-mix(in srgb,var(--muted) 25%,transparent); padding:3rem 1.25rem; text-align:center; display:grid; justify-items:center; gap:.75rem; }
  .quiz-empty-icon { width:4rem; height:4rem; border-radius:1.35rem; display:grid; place-items:center; background:color-mix(in srgb,var(--primary) 11%,transparent); color:var(--primary); }
  .quiz-empty h3 { margin:0; font-size:var(--text-xl); }
  .quiz-empty p { margin:0; max-width:32rem; color:var(--muted-foreground); font-size:var(--text-sm); }
  .quiz-completed-list { display:grid; gap:.8rem; }
  .quiz-result-card { display:grid; grid-template-columns:minmax(0,1fr) auto auto; gap:1rem; align-items:center; border:1px solid var(--border); border-radius:var(--radius-xl); padding:1rem; background:var(--card); box-shadow:var(--shadow-sm); }
  .quiz-score-ring { width:4.2rem; height:4.2rem; border-radius:999px; display:grid; place-items:center; background:conic-gradient(var(--score-color) calc(var(--score-pct) * 1%), color-mix(in srgb,var(--muted) 70%,transparent) 0); }
  .quiz-score-ring span { width:3.15rem; height:3.15rem; border-radius:999px; display:grid; place-items:center; background:var(--card); font-weight:950; font-size:var(--text-sm); color:var(--score-color); }
  @media (max-width:1180px) {
    .quiz-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .quiz-filter-form { grid-template-columns:1fr 1fr; }
    .quiz-filter-form .search-input-wrapper { grid-column:1 / -1; }
  }
  @media (max-width:760px) {
    .quiz-hero-inner { grid-template-columns:1fr; }
    .quiz-hero-action { justify-self:start; }
    .quiz-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .quiz-list-grid { grid-template-columns:1fr; }
    .quiz-result-card { grid-template-columns:1fr; }
    .quiz-filter-form { grid-template-columns:1fr; }
  }
  @media (max-width:520px) {
    .quiz-summary-grid { grid-template-columns:1fr; }
    .quiz-card-top { grid-template-columns:auto minmax(0,1fr); }
    .quiz-card-top > .badge { grid-column:1 / -1; width:max-content; }
    .quiz-facts { grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
</style>
@endpush

@section('content')
  @if(session('info') || session('success') || session('warning') || session('error'))
    <div style="margin-bottom:1rem;">
      @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
      @elseif(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @elseif(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
      @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif
    </div>
  @endif

  <div class="quiz-page">
    <section class="quiz-hero">
      <div class="quiz-hero-inner">
        <div>
          <div class="quiz-eyebrow">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11h6"/><path d="M9 15h6"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
            Trung tâm bài kiểm tra
          </div>
          <h1>Bài kiểm tra của tôi</h1>
          <p>Theo dõi bài cần làm, bài đang chờ mở, kết quả đã hoàn thành và các hạn kiểm tra trong khóa học của bạn.</p>
        </div>
        <a href="{{ route('student.courses') }}" class="btn btn-outline gap-2 quiz-hero-action">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m5 10 7 4 7-4"/><path d="m5 15 7 4 7-4"/></svg>
          Xem khóa học
        </a>
      </div>
    </section>

    <section class="quiz-summary-grid">
      <div class="quiz-stat" style="--stat-color:var(--foreground);">
        <div class="quiz-stat-label">Tổng bài giao</div>
        <div class="quiz-stat-value">{{ number_format($summary['total']) }}</div>
        <div class="quiz-stat-sub">Trong các lớp/khóa đang học</div>
      </div>
      <div class="quiz-stat" style="--stat-color:var(--primary);">
        <div class="quiz-stat-label">Cần làm</div>
        <div class="quiz-stat-value">{{ number_format($summary['available']) }}</div>
        <div class="quiz-stat-sub">Đang mở hoặc đang làm dở</div>
      </div>
      <div class="quiz-stat" style="--stat-color:var(--info);">
        <div class="quiz-stat-label">Chưa mở</div>
        <div class="quiz-stat-value">{{ number_format($summary['scheduled']) }}</div>
        <div class="quiz-stat-sub">Đã giao, chờ đến giờ</div>
      </div>
      <div class="quiz-stat" style="--stat-color:var(--success);">
        <div class="quiz-stat-label">Đã hoàn thành</div>
        <div class="quiz-stat-value">{{ number_format($summary['completed']) }}</div>
        <div class="quiz-stat-sub">Điểm TB: {{ $avgScore !== null ? $avgScore . '%' : 'chưa có' }}</div>
      </div>
      <div class="quiz-stat" style="--stat-color:{{ $summary['missed'] > 0 ? 'var(--destructive)' : 'var(--muted-foreground)' }};">
        <div class="quiz-stat-label">Quá hạn</div>
        <div class="quiz-stat-value">{{ number_format($summary['missed']) }}</div>
        <div class="quiz-stat-sub">Cần theo dõi hạn làm bài</div>
      </div>
    </section>

    <section class="quiz-filter-card">
      <form class="quiz-filter-form" method="GET" action="{{ route('student.quizzes') }}">
        <input type="hidden" name="status" value="{{ $activeTab }}">
        <div class="search-input-wrapper">
          <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input class="input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm bài kiểm tra, giáo viên, khóa học..." />
        </div>
        <select class="input select" name="course_id">
          <option value="">Tất cả khóa học</option>
          @foreach($courses as $course)
            <option value="{{ $course->id }}" @selected((string) $filters['course_id'] === (string) $course->id)>{{ $course->name }}</option>
          @endforeach
        </select>
        <select class="input select" name="type">
          <option value="all" @selected($filters['type'] === 'all')>Tất cả loại bài</option>
          <option value="exam" @selected($filters['type'] === 'exam')>Kiểm tra</option>
          <option value="practice" @selected($filters['type'] === 'practice')>Luyện tập</option>
        </select>
        <button class="btn btn-primary" type="submit">Lọc</button>
        @if($hasActiveFilters)
          <a class="btn btn-ghost" href="{{ route('student.quizzes', ['status' => $activeTab]) }}">Xóa lọc</a>
        @else
          <span class="text-sm text-muted" style="white-space:nowrap;">{{ $summary['total'] }} bài</span>
        @endif
      </form>
    </section>

    <nav class="quiz-tabs" role="tablist" aria-label="Trạng thái bài kiểm tra">
      @foreach($tabs as $key => $tab)
        <a class="quiz-tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ $tabUrl($key) }}" role="tab" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}">
          {{ $tab['label'] }}
          <span class="badge badge-{{ $tab['tone'] }}">{{ $tab['count'] }}</span>
        </a>
      @endforeach
    </nav>

    <section class="quiz-section-head">
      <div>
        <h2>{{ $activeTitle }}</h2>
        <p>{{ $activeItems->count() }} bài phù hợp với bộ lọc hiện tại.</p>
      </div>
    </section>

    @if($activeItems->isEmpty())
      <section class="quiz-empty">
        <div class="quiz-empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <h3>Không có bài trong mục này</h3>
        <p>Thử đổi bộ lọc, chọn khóa học khác hoặc quay lại sau khi giáo viên giao thêm bài.</p>
      </section>
    @elseif($activeTab === 'completed')
      <section class="quiz-completed-list">
        @foreach($activeItems as $quiz)
          @php
            $pct = $quiz->score_pct ?? 0;
            $passed = $pct >= ($quiz->passing_score ?? 50);
            $scoreColor = $pct >= 85 ? 'var(--success)' : ($pct >= 60 ? 'var(--info)' : 'var(--destructive)');
          @endphp
          <article class="quiz-result-card" style="--score-color:{{ $scoreColor }};--score-pct:{{ max(0, min(100, $pct)) }};">
            <div style="min-width:0;">
              <h3 class="quiz-title">{{ $quiz->title }}</h3>
              <div class="quiz-meta">
                <span>{{ $quiz->context_name }}</span>
                <span>{{ ($quiz->quiz_type ?? 'exam') === 'practice' ? 'Luyện tập' : 'Kiểm tra' }}</span>
                <span>{{ $quiz->submitted_at_display?->format('d/m/Y H:i') ?? 'Chưa có thời gian nộp' }}</span>
              </div>
              <div class="quiz-meta">
                <span>{{ $quiz->questions_count }} câu</span>
                <span>{{ $quiz->duration_label }}</span>
                @if(!empty($quiz->is_unlimited_attempts))
                  <span>Không giới hạn lượt làm</span>
                @else
                  <span>{{ $quiz->submitted_attempts ?? 0 }}/{{ $quiz->max_attempts_display ?? 1 }} lượt đã dùng</span>
                @endif
              </div>
            </div>
            <div class="quiz-score-ring">
              <span>{{ $quiz->score_pct !== null ? $quiz->score_pct . '%' : '--' }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;justify-content:flex-end;flex-wrap:wrap;">
              <span class="badge {{ $passed ? 'badge-success' : 'badge-danger' }}">{{ $passed ? 'Đạt' : 'Chưa đạt' }}</span>
              <a href="{{ route('student.quiz-result', $quiz) }}" class="btn btn-outline btn-sm">Xem kết quả</a>
            </div>
          </article>
        @endforeach
      </section>
    @else
      <section class="quiz-list-grid">
        @foreach($activeItems as $quiz)
          @php
            $isPractice = ($quiz->quiz_type ?? 'exam') === 'practice';
            $isScheduled = $quiz->learning_status === 'scheduled';
            $isInProgress = $quiz->learning_status === 'in_progress';
            $isMissed = $quiz->learning_status === 'missed';
            $toneColor = $isMissed ? 'var(--destructive)' : ($isScheduled ? 'var(--info)' : ($isPractice ? 'var(--info)' : 'var(--warning)'));
            $badgeClass = $isPractice ? 'badge-info' : 'badge-warning';
          @endphp
          <article class="quiz-card" style="--quiz-tone:{{ $toneColor }};">
            <div class="quiz-card-body">
              <div class="quiz-card-top">
                <div class="quiz-icon">
                  @if($isMissed)
                    <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 7v6"/><path d="M12 17h.01"/></svg>
                  @elseif($isScheduled)
                    <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  @elseif($isPractice)
                    <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11h6"/><path d="M9 15h6"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                  @endif
                </div>
                <div style="min-width:0;">
                  <h3 class="quiz-title">{{ $quiz->title }}</h3>
                  <div class="quiz-meta">
                    <span class="quiz-meta-item">{{ $quiz->context_name }}</span>
                    <span class="quiz-meta-item">{{ $quiz->teacher?->name ?? 'Chưa có giáo viên' }}</span>
                  </div>
                </div>
                <span class="badge {{ $badgeClass }}">{{ $isPractice ? 'Luyện tập' : 'Kiểm tra' }}</span>
              </div>

              <div class="quiz-due">
                <span>{{ $quiz->due_state['label'] }}</span>
                @if($isInProgress)
                  <span class="badge badge-primary">Đang làm dở</span>
                @elseif($isScheduled)
                  <span class="badge badge-info">Chờ mở</span>
                @elseif($isMissed)
                  <span class="badge badge-danger">Quá hạn</span>
                @endif
              </div>

              @if($quiz->description)
                <p class="quiz-desc">{{ $quiz->description }}</p>
              @else
                <p class="quiz-desc">Giáo viên chưa thêm mô tả cho bài này. Hãy kiểm tra số câu, thời lượng và lượt làm trước khi bắt đầu.</p>
              @endif

              <div class="quiz-facts">
                <div class="quiz-fact">
                  <strong>{{ $quiz->questions_count }}</strong>
                  <span>Câu hỏi</span>
                </div>
                <div class="quiz-fact">
                  <strong>{{ $quiz->duration_label }}</strong>
                  <span>Thời lượng</span>
                </div>
                <div class="quiz-fact">
                  <strong>{{ $quiz->passing_score ?? 50 }}%</strong>
                  <span>Điểm đạt</span>
                </div>
                <div class="quiz-fact">
                  <strong>
                    @if(!empty($quiz->is_unlimited_attempts))
                      Vô hạn
                    @else
                      {{ $quiz->remaining_attempts ?? 0 }}/{{ $quiz->max_attempts_display ?? 1 }}
                    @endif
                  </strong>
                  <span>Lượt còn</span>
                </div>
              </div>
            </div>
            <div class="quiz-card-footer">
              @if($isMissed)
                <button class="btn btn-outline btn-sm w-full" type="button" disabled>Đã quá hạn làm bài</button>
              @elseif($isScheduled)
                <button class="btn btn-outline btn-sm w-full" type="button" disabled>Chưa đến giờ mở bài</button>
              @else
                <a href="{{ route('student.quiz-take', $quiz) }}" class="btn btn-primary btn-sm w-full">{{ $isInProgress ? 'Tiếp tục làm bài' : 'Bắt đầu làm bài' }}</a>
              @endif
            </div>
          </article>
        @endforeach
      </section>
    @endif
  </div>
@endsection
