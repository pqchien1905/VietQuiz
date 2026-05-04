{{-- Teacher: notifications --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@php
  $filterItems = [
    'all' => ['label' => 'Tất cả', 'count' => $totalCount],
    'unread' => ['label' => 'Chưa đọc', 'count' => $unreadCount],
    'read' => ['label' => 'Đã đọc', 'count' => $readCount],
    'assignment' => ['label' => 'Bài tập', 'count' => $categoryCounts['assignment'] ?? 0],
    'quiz' => ['label' => 'Bài kiểm tra', 'count' => $categoryCounts['quiz'] ?? 0],
    'grading' => ['label' => 'Chấm điểm', 'count' => $categoryCounts['grading'] ?? 0],
    'class' => ['label' => 'Lớp học', 'count' => $categoryCounts['class'] ?? 0],
    'system' => ['label' => 'Hệ thống', 'count' => $categoryCounts['system'] ?? 0],
  ];

  $typeMeta = function (string $type): array {
    if (str_contains($type, 'assignment')) return ['label' => 'Bài tập', 'icon' => '📌', 'tone' => 'primary'];
    if (str_contains($type, 'quiz')) return ['label' => 'Bài kiểm tra', 'icon' => '📝', 'tone' => 'success'];
    if (str_contains($type, 'grade') || str_contains($type, 'grading') || str_contains($type, 'submission')) return ['label' => 'Chấm điểm', 'icon' => '✅', 'tone' => 'info'];
    if (str_contains($type, 'class')) return ['label' => 'Lớp học', 'icon' => '👥', 'tone' => 'accent'];
    if (str_contains($type, 'reminder')) return ['label' => 'Nhắc nhở', 'icon' => '⏰', 'tone' => 'warning'];
    return ['label' => 'Hệ thống', 'icon' => '🔔', 'tone' => 'muted'];
  };

  $toneColor = function (string $tone): string {
    return match ($tone) {
      'primary' => 'var(--primary)',
      'success' => 'var(--success)',
      'info' => 'var(--info)',
      'accent' => 'var(--accent)',
      'warning' => 'var(--warning)',
      default => 'var(--muted-foreground)',
    };
  };
@endphp

@push('styles')
<style>
  .notifications-toolbar { display:grid; grid-template-columns: minmax(0,1fr) auto; gap:0.75rem; align-items:center; margin-bottom:1rem; }
  .notifications-actions { display:flex; gap:0.5rem; flex-wrap:wrap; justify-content:flex-end; }
  .notif-tabs { display:flex; gap:0.25rem; overflow:auto; padding-bottom:0.25rem; margin-bottom:1.25rem; border-bottom:1px solid var(--border); }
  .notif-tab { display:inline-flex; align-items:center; gap:0.5rem; padding:0.625rem 0.875rem; border-bottom:2px solid transparent; color:var(--muted-foreground); text-decoration:none; white-space:nowrap; font-size:var(--text-sm); font-weight:600; }
  .notif-tab:hover { color:var(--foreground); }
  .notif-tab.active { color:var(--primary); border-bottom-color:var(--primary); }
  .notif-count { min-width:1.5rem; height:1.5rem; padding:0 0.45rem; border-radius:999px; background:var(--muted); color:var(--muted-foreground); display:inline-flex; align-items:center; justify-content:center; font-size:var(--text-xs); font-weight:700; }
  .notif-tab.active .notif-count { background:var(--primary); color:var(--primary-foreground); }
  .notif-card { overflow:hidden; }
  .notif-item { display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid var(--border); background:var(--card); align-items:flex-start; }
  .notif-item:last-child { border-bottom:none; }
  .notif-item.unread { background:color-mix(in srgb,var(--primary) 5%,var(--card)); }
  .notif-icon { width:2.5rem; height:2.5rem; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.125rem; }
  .notif-open { width:100%; border:0; padding:0; background:transparent; color:inherit; text-align:left; cursor:pointer; }
  .notif-open:hover .notif-title { color:var(--primary); }
  .notif-title-row { display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem; }
  .notif-title { font-size:var(--text-sm); font-weight:600; color:var(--foreground); line-height:1.4; }
  .notif-body { margin-top:0.25rem; color:var(--muted-foreground); font-size:var(--text-sm); line-height:1.55; }
  .notif-meta { margin-top:0.625rem; display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center; color:var(--muted-foreground); font-size:var(--text-xs); }
  .notif-badge { display:inline-flex; align-items:center; border-radius:999px; padding:0.125rem 0.5rem; background:var(--muted); color:var(--muted-foreground); font-weight:700; }
  .notif-unread-dot { width:0.5rem; height:0.5rem; border-radius:999px; background:var(--primary); display:inline-block; }
  .notif-row-actions { display:flex; gap:0.25rem; align-items:center; opacity:0.75; }
  .notif-item:hover .notif-row-actions { opacity:1; }
  .notif-action-btn { width:2rem; height:2rem; display:inline-flex; align-items:center; justify-content:center; border:1px solid transparent; border-radius:var(--radius-sm); background:transparent; color:var(--muted-foreground); cursor:pointer; }
  .notif-action-btn:hover { background:var(--muted); color:var(--foreground); }
  .notif-action-btn.danger:hover { color:var(--destructive); }
  .notif-empty { padding:3.5rem 1.5rem; text-align:center; }
  .notif-empty-icon { width:4rem; height:4rem; border-radius:999px; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; background:var(--muted); color:var(--muted-foreground); font-size:1.75rem; }
  .notif-pagination { padding:1rem 1.25rem; border-top:1px solid var(--border); }
  @media (max-width: 768px) {
    .notifications-toolbar { grid-template-columns:1fr; }
    .notifications-actions { justify-content:flex-start; }
    .notif-item { grid-template-columns:auto minmax(0,1fr); }
    .notif-row-actions { grid-column:2; justify-content:flex-start; }
    .notif-title-row { display:block; }
  }
</style>
@endpush

@section('content')
  <div class="page-header stagger-children">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1>Thông báo</h1>
        <p style="color:var(--muted-foreground);">Theo dõi bài nộp, lớp học, bài kiểm tra và cập nhật hệ thống.</p>
      </div>
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        <div class="card" style="padding:0.75rem 1rem;min-width:8rem;">
          <div style="font-size:var(--text-xs);color:var(--muted-foreground);font-weight:600;">Tổng</div>
          <div style="font-size:var(--text-xl);font-weight:800;">{{ $totalCount }}</div>
        </div>
        <div class="card" style="padding:0.75rem 1rem;min-width:8rem;">
          <div style="font-size:var(--text-xs);color:var(--muted-foreground);font-weight:600;">Chưa đọc</div>
          <div style="font-size:var(--text-xl);font-weight:800;color:var(--primary);">{{ $unreadCount }}</div>
        </div>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
  @endif

  <div class="notifications-toolbar">
    <form method="GET" action="{{ route('teacher.notifications') }}">
      <input type="hidden" name="filter" value="{{ $currentFilter }}">
      <div style="position:relative;">
        <svg style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted-foreground);pointer-events:none;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input class="input" type="search" name="q" value="{{ $search }}" placeholder="Tìm theo tiêu đề, nội dung hoặc loại thông báo..." style="padding-left:2.5rem;">
      </div>
    </form>

    <div class="notifications-actions">
      <form method="POST" action="{{ route('teacher.notifications.mark-all-read') }}">
        @csrf
        <button class="btn btn-outline btn-sm" type="submit" @disabled($unreadCount === 0)>Đánh dấu tất cả đã đọc</button>
      </form>
      <form method="POST" action="{{ route('teacher.notifications.clear-all') }}" data-confirm="Xóa tất cả thông báo? Hành động này không thể hoàn tác.">
        @csrf
        @method('DELETE')
        <button class="btn btn-ghost btn-sm" type="submit" style="color:var(--destructive);" @disabled($totalCount === 0)>Xóa tất cả</button>
      </form>
    </div>
  </div>

  <nav class="notif-tabs" aria-label="Bộ lọc thông báo">
    @foreach($filterItems as $key => $item)
      <a class="notif-tab {{ $currentFilter === $key ? 'active' : '' }}"
         href="{{ route('teacher.notifications', array_filter(['filter' => $key, 'q' => $search ?: null])) }}">
        {{ $item['label'] }}
        <span class="notif-count">{{ $item['count'] }}</span>
      </a>
    @endforeach
  </nav>

  <div class="card notif-card stagger-children">
    @forelse($notifications as $notification)
      @php
        $meta = $typeMeta($notification->type);
        $color = $toneColor($meta['tone']);
      @endphp
      <article class="notif-item {{ $notification->is_read ? '' : 'unread' }}">
        <div class="notif-icon" style="background:color-mix(in srgb, {{ $color }} 13%, transparent); color:{{ $color }};">{{ $meta['icon'] }}</div>

        <form method="POST" action="{{ route('teacher.notifications.open', $notification) }}" style="min-width:0;">
          @csrf
          <button class="notif-open" type="submit">
            <div class="notif-title-row">
              <span class="notif-title">{{ $notification->title }}</span>
              <span style="font-size:var(--text-xs);color:var(--muted-foreground);white-space:nowrap;">{{ $notification->created_at?->diffForHumans() }}</span>
            </div>
            @if($notification->body)
              <p class="notif-body">{{ $notification->body }}</p>
            @endif
            <div class="notif-meta">
              <span class="notif-badge">{{ $meta['label'] }}</span>
              <span>{{ $notification->created_at?->format('d/m/Y H:i') }}</span>
              @unless($notification->is_read)
                <span class="notif-unread-dot" title="Chưa đọc"></span>
              @endunless
            </div>
          </button>
        </form>

        <div class="notif-row-actions">
          @if($notification->is_read)
            <form method="POST" action="{{ route('teacher.notifications.unread', $notification) }}">
              @csrf
              <button class="notif-action-btn" type="submit" title="Đánh dấu chưa đọc" aria-label="Đánh dấu chưa đọc">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
              </button>
            </form>
          @else
            <form method="POST" action="{{ route('teacher.notifications.read', $notification) }}">
              @csrf
              <button class="notif-action-btn" type="submit" title="Đánh dấu đã đọc" aria-label="Đánh dấu đã đọc">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </form>
          @endif
          <form method="POST" action="{{ route('teacher.notifications.destroy', $notification) }}" data-confirm="Xóa thông báo này?">
            @csrf
            @method('DELETE')
            <button class="notif-action-btn danger" type="submit" title="Xóa" aria-label="Xóa thông báo">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </form>
        </div>
      </article>
    @empty
      <div class="notif-empty">
        <div class="notif-empty-icon">🔔</div>
        <h3 style="font-weight:700;color:var(--foreground);">Không có thông báo</h3>
        <p style="color:var(--muted-foreground);margin-top:.5rem;">
          @if($search)
            Không tìm thấy thông báo phù hợp với từ khóa "{{ $search }}".
          @else
            Khi có bài nộp, cập nhật lớp học hoặc thông tin hệ thống, chúng sẽ xuất hiện tại đây.
          @endif
        </p>
      </div>
    @endforelse

    @if($notifications->hasPages())
      <div class="notif-pagination">
        {{ $notifications->links() }}
      </div>
    @endif
  </div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('form[data-confirm]').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      if (!confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });
</script>
@endpush
