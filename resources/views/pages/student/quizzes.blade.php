{{-- Student: quizzes --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
  .quiz-summary-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:1rem;margin-bottom:1.25rem}
  .quiz-tabs{display:flex;gap:.25rem;padding:.25rem;background:var(--muted);border-radius:var(--radius-md);margin-bottom:1.25rem;max-width:44rem}
  .quiz-tab{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:.375rem;padding:.5rem .75rem;border:1px solid transparent;border-radius:var(--radius-sm);background:transparent;color:var(--muted-foreground);font-size:var(--text-sm);font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap}
  .quiz-tab:hover{background:color-mix(in srgb,var(--background) 70%,transparent);color:var(--foreground);text-decoration:none}
  .quiz-tab.active{background:var(--background);color:var(--foreground);border-color:var(--border);box-shadow:var(--shadow-sm)}
  .quiz-card{position:relative;display:flex;flex-direction:column;min-height:100%}
  .quiz-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.875rem}
  .quiz-icon{width:2.5rem;height:2.5rem;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .quiz-title{font-size:var(--text-base);font-weight:800;line-height:1.35;margin:0 0 .25rem}
  .quiz-meta{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;font-size:var(--text-xs);color:var(--muted-foreground)}
  .quiz-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem;margin:1rem 0}
  .quiz-fact{border:1px solid var(--border);border-radius:var(--radius-md);padding:.625rem .5rem;text-align:center;background:color-mix(in srgb,var(--muted) 35%,transparent)}
  .quiz-fact strong{display:block;font-size:var(--text-base);line-height:1;color:var(--foreground)}
  .quiz-fact span{display:block;font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem}
  .quiz-description{font-size:var(--text-sm);color:var(--muted-foreground);line-height:1.55;margin:.75rem 0 0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
  .quiz-filter-form .search-input-wrapper{min-width:17rem}
  .quiz-panel{display:none}
  .quiz-panel.active{display:block}
  .score-pill{display:inline-flex;align-items:center;gap:.375rem;font-weight:800}
  @media (max-width:1100px){.quiz-summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
  @media (max-width:900px){.quiz-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.quiz-tabs{max-width:none;overflow-x:auto}.quiz-tab{min-width:9rem}.quiz-filter-form .search-input-wrapper{min-width:0}}
  @media (max-width:520px){.quiz-summary-grid{grid-template-columns:1fr}.quiz-facts{grid-template-columns:1fr}.quiz-card-top{align-items:flex-start}.quiz-meta{gap:.5rem}}
</style>
@endpush

@section('content')
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
  @endphp

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

  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Bài kiểm tra của tôi</h1>
        <p>Theo dõi bài cần làm, bài đã hoàn thành và các hạn kiểm tra trong khóa học.</p>
      </div>
      <a href="{{ route('student.courses') }}" class="btn btn-outline gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m5 10 7 4 7-4"/><path d="m5 15 7 4 7-4"/></svg>
        Khóa học
      </a>
    </div>
  </div>

  <div class="quiz-summary-grid stagger-children">
    <div class="stat-card">
      <div class="stat-card__value">{{ number_format($summary['total']) }}</div>
      <div class="stat-card__label">Tổng bài giao</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--primary);">{{ number_format($summary['available']) }}</div>
      <div class="stat-card__label">Cần làm</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--info);">{{ number_format($summary['scheduled']) }}</div>
      <div class="stat-card__label">Chưa mở</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:var(--success);">{{ number_format($summary['completed']) }}</div>
      <div class="stat-card__label">Đã hoàn thành</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value" style="color:{{ $summary['missed'] > 0 ? 'var(--destructive)' : 'var(--muted-foreground)' }};">{{ number_format($summary['missed']) }}</div>
      <div class="stat-card__label">Quá hạn</div>
    </div>
  </div>

  <form class="toolbar quiz-filter-form stagger-children" method="GET" action="{{ route('student.quizzes') }}">
    <input type="hidden" name="status" value="{{ $activeTab }}">
    <div class="toolbar-left">
      <div class="search-input-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm bài kiểm tra, giáo viên, khóa học..." />
      </div>
      <select class="input select" name="course_id" style="max-width:13rem;">
        <option value="">Tất cả khóa học</option>
        @foreach($courses as $course)
          <option value="{{ $course->id }}" @selected((string) $filters['course_id'] === (string) $course->id)>{{ $course->name }}</option>
        @endforeach
      </select>
      <select class="input select" name="type" style="max-width:12rem;">
        <option value="all" @selected($filters['type'] === 'all')>Tất cả loại bài</option>
        <option value="exam" @selected($filters['type'] === 'exam')>Kiểm tra</option>
        <option value="practice" @selected($filters['type'] === 'practice')>Luyện tập</option>
      </select>
    </div>
    <div class="toolbar-right">
      <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
      @if($hasActiveFilters)
        <a class="btn btn-ghost btn-sm" href="{{ route('student.quizzes', ['status' => $activeTab]) }}">Xóa lọc</a>
      @endif
      <span class="text-sm text-muted">{{ $summary['total'] }} bài</span>
    </div>
  </form>

  <div class="quiz-tabs" role="tablist" aria-label="Trạng thái bài kiểm tra">
    <a class="quiz-tab {{ $activeTab === 'available' ? 'active' : '' }}" href="{{ $tabUrl('available') }}" role="tab" aria-selected="{{ $activeTab === 'available' ? 'true' : 'false' }}">
      Cần làm <span class="badge badge-primary">{{ $available->count() }}</span>
    </a>
    <a class="quiz-tab {{ $activeTab === 'scheduled' ? 'active' : '' }}" href="{{ $tabUrl('scheduled') }}" role="tab" aria-selected="{{ $activeTab === 'scheduled' ? 'true' : 'false' }}">
      Chưa mở <span class="badge badge-info">{{ $scheduled->count() }}</span>
    </a>
    <a class="quiz-tab {{ $activeTab === 'completed' ? 'active' : '' }}" href="{{ $tabUrl('completed') }}" role="tab" aria-selected="{{ $activeTab === 'completed' ? 'true' : 'false' }}">
      Đã làm <span class="badge badge-success">{{ $completed->count() }}</span>
    </a>
    <a class="quiz-tab {{ $activeTab === 'missed' ? 'active' : '' }}" href="{{ $tabUrl('missed') }}" role="tab" aria-selected="{{ $activeTab === 'missed' ? 'true' : 'false' }}">
      Quá hạn <span class="badge badge-danger">{{ $missed->count() }}</span>
    </a>
  </div>

  <div class="quiz-panel {{ $activeTab === 'available' ? 'active' : '' }}" id="quiz-panel-available">
    @if($available->isEmpty())
      <div class="empty-state card">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <h3>Không có bài cần làm</h3>
        <p>Các bài kiểm tra đang mở hoặc bài đang làm dở sẽ xuất hiện tại đây.</p>
      </div>
    @else
      <div class="cards-grid stagger-children">
        @foreach($available as $quiz)
          @php
            $isPractice = ($quiz->quiz_type ?? 'exam') === 'practice';
            $isScheduled = $quiz->learning_status === 'scheduled';
            $isInProgress = $quiz->learning_status === 'in_progress';
            $toneColor = $isPractice ? 'var(--info)' : 'var(--warning)';
            $dueTone = $quiz->due_state['tone'] ?? 'muted';
            $dueColor = match($dueTone) {
                'danger' => 'var(--destructive)',
                'warning' => 'var(--warning)',
                'info' => 'var(--info)',
                default => 'var(--muted-foreground)',
            };
          @endphp
          <article class="card hover-lift quiz-card">
            <div class="card-content" style="flex:1;">
              <div class="quiz-card-top">
                <div style="display:flex;align-items:flex-start;gap:.875rem;min-width:0;">
                  <div class="quiz-icon" style="background:color-mix(in srgb,{{ $toneColor }} 13%,transparent);color:{{ $toneColor }};">
                    @if($isPractice)
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    @else
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11h6"/><path d="M9 15h6"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                    @endif
                  </div>
                  <div style="min-width:0;">
                    <h2 class="quiz-title">{{ $quiz->title }}</h2>
                    <div class="quiz-meta">
                      <span>{{ $quiz->context_name }}</span>
                      <span>{{ $quiz->teacher?->name ?? 'Chưa có giáo viên' }}</span>
                    </div>
                  </div>
                </div>
                <span class="badge {{ $isPractice ? 'badge-info' : 'badge-warning' }}">{{ $isPractice ? 'Luyện tập' : 'Kiểm tra' }}</span>
              </div>

              <div class="quiz-meta" style="color:{{ $dueColor }};">
                <span>{{ $quiz->due_state['label'] }}</span>
                @if($isInProgress)
                  <span class="badge badge-primary">Đang làm dở</span>
                @elseif($isScheduled)
                  <span class="badge badge-outline">Chưa mở</span>
                @endif
              </div>

              @if($quiz->description)
                <p class="quiz-description">{{ $quiz->description }}</p>
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
              </div>
            </div>
            <div class="card-footer">
              @if($isScheduled)
                <button class="btn btn-outline btn-sm w-full" type="button" disabled>Chưa đến giờ mở bài</button>
              @else
                <a href="{{ route('student.quiz-take', $quiz) }}" class="btn btn-primary btn-sm w-full">{{ $isInProgress ? 'Tiếp tục làm bài' : 'Bắt đầu làm bài' }}</a>
              @endif
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>

  <div class="quiz-panel {{ $activeTab === 'scheduled' ? 'active' : '' }}" id="quiz-panel-scheduled">
    @if($scheduled->isEmpty())
      <div class="empty-state card">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <h3>Không có bài chưa mở</h3>
        <p>Các bài kiểm tra đã được giao nhưng chưa đến giờ làm sẽ hiển thị tại đây.</p>
      </div>
    @else
      <div class="cards-grid stagger-children">
        @foreach($scheduled as $quiz)
          @php
            $isPractice = ($quiz->quiz_type ?? 'exam') === 'practice';
            $toneColor = $isPractice ? 'var(--info)' : 'var(--warning)';
          @endphp
          <article class="card hover-lift quiz-card">
            <div class="card-content" style="flex:1;">
              <div class="quiz-card-top">
                <div style="display:flex;align-items:flex-start;gap:.875rem;min-width:0;">
                  <div class="quiz-icon" style="background:color-mix(in srgb,{{ $toneColor }} 13%,transparent);color:{{ $toneColor }};">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  </div>
                  <div style="min-width:0;">
                    <h2 class="quiz-title">{{ $quiz->title }}</h2>
                    <div class="quiz-meta">
                      <span>{{ $quiz->context_name }}</span>
                      <span>{{ $quiz->teacher?->name ?? 'Chưa có giáo viên' }}</span>
                    </div>
                  </div>
                </div>
                <span class="badge {{ $isPractice ? 'badge-info' : 'badge-warning' }}">{{ $isPractice ? 'Luyện tập' : 'Kiểm tra' }}</span>
              </div>

              <div class="quiz-meta" style="color:var(--info);">
                <span>{{ $quiz->due_state['label'] }}</span>
                <span class="badge badge-outline">Chưa mở</span>
              </div>

              @if($quiz->description)
                <p class="quiz-description">{{ $quiz->description }}</p>
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
              </div>
            </div>
            <div class="card-footer">
              <button class="btn btn-outline btn-sm w-full" type="button" disabled>Chưa đến giờ mở bài</button>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>

  <div class="quiz-panel {{ $activeTab === 'completed' ? 'active' : '' }}" id="quiz-panel-completed">
    @if($completed->isEmpty())
      <div class="empty-state card">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <h3>Chưa có bài hoàn thành</h3>
        <p>Sau khi nộp bài, kết quả và lịch sử làm bài sẽ hiển thị ở đây.</p>
      </div>
    @else
      <div class="card">
        <div class="table-wrapper" style="border:none;border-radius:0;">
          <div class="table-scroll">
            <table>
              <thead>
                <tr>
                  <th>Bài kiểm tra</th>
                  <th>Khóa học/Lớp</th>
                  <th>Ngày nộp</th>
                  <th>Điểm</th>
                  <th>Trạng thái</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach($completed as $quiz)
                  @php
                    $pct = $quiz->score_pct ?? 0;
                    $passed = $pct >= ($quiz->passing_score ?? 50);
                    $scoreColor = $pct >= 85 ? 'var(--success)' : ($pct >= 60 ? 'var(--info)' : 'var(--destructive)');
                  @endphp
                  <tr>
                    <td>
                      <div style="font-weight:700;">{{ $quiz->title }}</div>
                      <div class="text-xs text-muted">{{ ($quiz->quiz_type ?? 'exam') === 'practice' ? 'Luyện tập' : 'Kiểm tra' }} · {{ $quiz->questions_count }} câu · {{ $quiz->duration_label }}</div>
                    </td>
                    <td class="text-sm text-muted">{{ $quiz->context_name }}</td>
                    <td class="text-sm text-muted">{{ $quiz->submitted_at_display?->format('d/m/Y H:i') ?? '--' }}</td>
                    <td>
                      <span class="score-pill" style="color:{{ $scoreColor }};">
                        {{ $quiz->score_pct !== null ? $quiz->score_pct . '%' : '--' }}
                      </span>
                      @if($quiz->score_value !== null && $quiz->score_max > 0)
                        <div class="text-xs text-muted">{{ $quiz->score_value }}/{{ $quiz->score_max }} điểm</div>
                      @endif
                    </td>
                    <td>
                      <span class="badge {{ $passed ? 'badge-success' : 'badge-danger' }}">{{ $passed ? 'Đạt' : 'Chưa đạt' }}</span>
                    </td>
                    <td>
                      <a href="{{ route('student.quiz-result', $quiz) }}" class="btn btn-outline btn-sm">Xem kết quả</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif
  </div>

  <div class="quiz-panel {{ $activeTab === 'missed' ? 'active' : '' }}" id="quiz-panel-missed">
    @if($missed->isEmpty())
      <div class="empty-state card">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <h3>Không có bài quá hạn</h3>
        <p>Bạn chưa bỏ lỡ bài kiểm tra nào trong bộ lọc hiện tại.</p>
      </div>
    @else
      <div class="stagger-children" style="display:flex;flex-direction:column;gap:.875rem;">
        @foreach($missed as $quiz)
          <article class="card" style="border-color:color-mix(in srgb,var(--destructive) 45%,var(--border));">
            <div class="card-content" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
              <div class="quiz-icon" style="background:color-mix(in srgb,var(--destructive) 12%,transparent);color:var(--destructive);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 7v6"/><path d="M12 17h.01"/></svg>
              </div>
              <div style="flex:1;min-width:16rem;">
                <div style="font-weight:800;">{{ $quiz->title }}</div>
                <div class="text-sm text-muted">{{ $quiz->context_name }} · {{ $quiz->questions_count }} câu · {{ $quiz->duration_label }}</div>
                <div class="text-sm" style="color:var(--destructive);margin-top:.25rem;">{{ $quiz->due_state['label'] }}</div>
              </div>
              <span class="badge badge-danger">Không thể làm bài</span>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>

  <div id="toast-container"></div>
@endsection
