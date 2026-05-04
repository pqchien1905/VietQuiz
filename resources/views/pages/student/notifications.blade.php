{{-- Student: notifications --}}
@extends('layouts.dashboard', ['role' => 'student'])

@php
  $filters = [
    'all' => ['label' => 'Tất cả', 'count' => $totalCount],
    'unread' => ['label' => 'Chưa đọc', 'count' => $unreadCount],
    'read' => ['label' => 'Đã đọc', 'count' => $readCount],
    'assignment' => ['label' => 'Bài tập', 'count' => $categoryCounts['assignment'] ?? 0],
    'quiz' => ['label' => 'Bài kiểm tra', 'count' => $categoryCounts['quiz'] ?? 0],
    'grading' => ['label' => 'Điểm số', 'count' => $categoryCounts['grading'] ?? 0],
    'class' => ['label' => 'Lớp học', 'count' => $categoryCounts['class'] ?? 0],
    'system' => ['label' => 'Hệ thống', 'count' => $categoryCounts['system'] ?? 0],
  ];

  $categoryFor = function (string $type): string {
    return match (true) {
      str_contains($type, 'assignment') => 'assignment',
      str_contains($type, 'quiz') => 'quiz',
      str_contains($type, 'grade') || str_contains($type, 'grading') || str_contains($type, 'submission') => 'grading',
      str_contains($type, 'class') || str_contains($type, 'course') => 'class',
      default => 'system',
    };
  };

  $metaFor = function (string $type) use ($categoryFor): array {
    return match ($categoryFor($type)) {
      'assignment' => ['icon' => '📎', 'class' => 'notif-assignment', 'label' => 'Bài tập', 'badge' => 'badge-warning'],
      'quiz' => ['icon' => '📝', 'class' => 'notif-quiz', 'label' => 'Bài kiểm tra', 'badge' => 'badge-primary'],
      'grading' => ['icon' => '🏅', 'class' => 'notif-grade', 'label' => 'Điểm số', 'badge' => 'badge-success'],
      'class' => ['icon' => '🎓', 'class' => 'notif-class', 'label' => 'Lớp học', 'badge' => 'badge-info'],
      default => ['icon' => '🔔', 'class' => 'notif-system', 'label' => 'Hệ thống', 'badge' => 'badge-outline'],
    };
  };
@endphp

@push('styles')
<style>
  .notif-toolbar{display:grid;grid-template-columns:minmax(220px,1fr) auto;gap:.75rem;align-items:end;margin-bottom:1rem}
  .notif-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem}
  .notif-item{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid var(--border);transition:background var(--transition-fast)}
  .notif-item:last-child{border-bottom:none}
  .notif-item:hover{background:var(--muted)}
  .notif-item.unread{background:color-mix(in srgb,var(--primary) 4%,transparent)}
  .notif-icon{width:2.6rem;height:2.6rem;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem}
  .notif-assignment{background:color-mix(in srgb,var(--warning) 14%,transparent)}
  .notif-quiz{background:color-mix(in srgb,var(--primary) 14%,transparent)}
  .notif-grade{background:color-mix(in srgb,var(--success) 14%,transparent)}
  .notif-class{background:color-mix(in srgb,var(--info) 14%,transparent)}
  .notif-system{background:color-mix(in srgb,var(--muted-foreground) 12%,transparent)}
  .notif-actions{display:flex;gap:.35rem;align-items:center;justify-content:flex-end;flex-wrap:wrap}
  .notif-unread-dot{width:.55rem;height:.55rem;border-radius:50%;background:var(--primary);display:inline-block}
  @media (max-width: 760px){.notif-toolbar{grid-template-columns:1fr}.notif-item{grid-template-columns:auto minmax(0,1fr)}.notif-actions{grid-column:1 / -1;justify-content:flex-start;padding-left:3.6rem}}
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Thông báo</h1>
        <p style="color:var(--muted-foreground);">Theo dõi bài tập, bài kiểm tra, điểm số và cập nhật từ lớp học.</p>
      </div>
      <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <form method="POST" action="{{ route('student.notifications.mark-all-read') }}">
          @csrf
          <button class="btn btn-outline btn-sm" type="submit" @disabled($unreadCount === 0)>Đánh dấu tất cả đã đọc</button>
        </form>
        <form method="POST" action="{{ route('student.notifications.clear-all') }}" data-confirm="Xóa tất cả thông báo? Các thông báo sẽ được chuyển vào thùng rác." data-confirm-ok="Xóa">
          @csrf
          @method('DELETE')
          <button class="btn btn-ghost btn-sm" style="color:var(--destructive);" type="submit" @disabled($totalCount === 0)>Xóa tất cả</button>
        </form>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;"><span>{{ session('success') }}</span></div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;"><span>{{ $errors->first() }}</span></div>
  @endif

  <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tổng thông báo</div>
      <div class="stat-card__value">{{ $totalCount }}</div>
      <div class="stat-card__label">đang hiển thị trong hộp thư</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Chưa đọc</div>
      <div class="stat-card__value" style="color:var(--primary);">{{ $unreadCount }}</div>
      <div class="stat-card__label">cần xem</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài tập & quiz</div>
      <div class="stat-card__value">{{ ($categoryCounts['assignment'] ?? 0) + ($categoryCounts['quiz'] ?? 0) }}</div>
      <div class="stat-card__label">liên quan học tập</div>
    </div>
    <div class="stat-card">
      <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Đã đọc</div>
      <div class="stat-card__value">{{ $readCount }}</div>
      <div class="stat-card__label">đã xử lý</div>
    </div>
  </div>

  <form method="GET" action="{{ route('student.notifications') }}" class="notif-toolbar">
    <div>
      <label for="q" style="display:block;font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.35rem;">Tìm kiếm</label>
      <input id="q" name="q" class="input" value="{{ $search }}" placeholder="Tìm theo tiêu đề, nội dung hoặc loại thông báo...">
      <input type="hidden" name="filter" value="{{ $currentFilter }}">
    </div>
    <div style="display:flex;gap:.5rem;">
      <button class="btn btn-primary" type="submit">Tìm</button>
      <a href="{{ route('student.notifications', ['filter' => $currentFilter]) }}" class="btn btn-ghost">Xóa</a>
    </div>
  </form>

  <div class="notif-tabs stagger-children">
    @foreach($filters as $filterKey => $filter)
      <a
        href="{{ route('student.notifications', array_filter(['filter' => $filterKey, 'q' => $search ?: null])) }}"
        class="tab-trigger {{ $currentFilter === $filterKey ? 'active' : '' }}"
        style="text-decoration:none;"
      >
        {{ $filter['label'] }}
        <span class="badge {{ $filterKey === 'unread' && $filter['count'] > 0 ? 'badge-primary' : 'badge-outline' }}" style="margin-left:.25rem;">{{ $filter['count'] }}</span>
      </a>
    @endforeach
  </div>

  <div class="card stagger-children">
    @if($notifications->count())
      @foreach($notifications as $notification)
        @php
          $meta = $metaFor($notification->type);
          $isUnread = ! $notification->is_read;
        @endphp
        <div class="notif-item {{ $isUnread ? 'unread' : '' }}">
          <div class="notif-icon {{ $meta['class'] }}">{{ $meta['icon'] }}</div>
          <div style="min-width:0;">
            <div style="display:flex;align-items:flex-start;gap:.5rem;justify-content:space-between;">
              <div style="min-width:0;">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                  <h3 style="font-size:var(--text-base);font-weight:{{ $isUnread ? 700 : 600 }};margin:0;">{{ $notification->title }}</h3>
                  @if($isUnread)
                    <span class="notif-unread-dot" aria-label="Chưa đọc"></span>
                  @endif
                  <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                </div>
                <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin:.35rem 0 0;line-height:1.55;">{{ $notification->body }}</p>
              </div>
              <span style="font-size:var(--text-xs);color:var(--muted-foreground);white-space:nowrap;">{{ $notification->created_at?->diffForHumans() }}</span>
            </div>
          </div>
          <div class="notif-actions">
            <form method="POST" action="{{ route('student.notifications.open', $notification) }}">
              @csrf
              <button class="btn btn-primary btn-sm" type="submit">Mở</button>
            </form>
            @if($isUnread)
              <form method="POST" action="{{ route('student.notifications.read', $notification) }}">
                @csrf
                <button class="btn btn-outline btn-sm" type="submit">Đã đọc</button>
              </form>
            @else
              <form method="POST" action="{{ route('student.notifications.unread', $notification) }}">
                @csrf
                <button class="btn btn-outline btn-sm" type="submit">Chưa đọc</button>
              </form>
            @endif
            <form method="POST" action="{{ route('student.notifications.destroy', $notification) }}" data-confirm="Xóa thông báo này? Thông báo sẽ được chuyển vào thùng rác." data-confirm-ok="Xóa">
              @csrf
              @method('DELETE')
              <button class="btn btn-ghost btn-sm" style="color:var(--destructive);" type="submit">Xóa</button>
            </form>
          </div>
        </div>
      @endforeach

      <div style="padding:1rem;border-top:1px solid var(--border);">
        {{ $notifications->links() }}
      </div>
    @else
      <div class="empty-state" style="padding:3rem;">
        <div style="font-size:2.5rem;">Thông báo</div>
        <h3>Không có thông báo</h3>
        <p>{{ $search ? 'Không tìm thấy thông báo phù hợp với từ khóa.' : 'Bạn chưa có thông báo nào trong mục này.' }}</p>
        @if($search || $currentFilter !== 'all')
          <a href="{{ route('student.notifications') }}" class="btn btn-outline" style="margin-top:1rem;">Xem tất cả</a>
        @endif
      </div>
    @endif
  </div>

  <div style="margin-top:1rem;display:flex;justify-content:flex-end;">
    <a href="{{ route('student.trash') }}" class="btn btn-outline btn-sm">Mở thùng rác</a>
  </div>

  <div id="toast-container"></div>
@endsection
