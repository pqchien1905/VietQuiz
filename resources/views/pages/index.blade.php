{{-- Landing / Homepage --}}
@extends('layouts.landing')

@php
  $dashboardUrl = null;
  if (auth()->check()) {
    $dashboardUrl = auth()->user()->isTeacher() ? route('teacher.dashboard') : route('student.dashboard');
  }

  $features = [
    [
      'title' => 'Tạo đề thi nhanh chóng',
      'description' => 'Soạn đề với đa dạng loại câu hỏi: trắc nghiệm, đúng/sai, tự luận, điền khuyết. Hỗ trợ import từ Word/Excel.',
      'icon' => '<path d="M9 11l2 2 4-4"/><path d="M21 12a9 9 0 1 1-9-9"/><path d="M17 3h4v4"/>',
    ],
    [
      'title' => 'Chấm điểm tự động',
      'description' => 'Trắc nghiệm được chấm ngay sau khi nộp. Tự luận hỗ trợ AI gợi ý điểm số để giảm tải cho giáo viên.',
      'icon' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 10h8M8 14h5"/><path d="M16 17l2 2 3-4"/>',
    ],
    [
      'title' => 'Phân tích chi tiết',
      'description' => 'Dashboard trực quan với biểu đồ điểm, tỷ lệ hoàn thành, học sinh xuất sắc và cảnh báo học sinh yếu kém.',
      'icon' => '<path d="M4 19V5"/><path d="M4 19h16"/><rect x="7" y="11" width="3" height="5"/><rect x="12" y="7" width="3" height="9"/><rect x="17" y="9" width="3" height="7"/>',
    ],
    [
      'title' => 'Ngân hàng câu hỏi',
      'description' => 'Lưu trữ và tái sử dụng câu hỏi thông minh. Phân loại theo môn, chủ đề, độ khó. Tìm kiếm tức thời.',
      'icon' => '<path d="M3 21h18"/><path d="M5 21V8l7-4 7 4v13"/><path d="M9 21v-6h6v6"/><path d="M9 10h.01M15 10h.01"/>',
    ],
    [
      'title' => 'Quản lý lớp học',
      'description' => 'Tạo lớp, mời học sinh bằng mã, theo dõi điểm danh và quản lý toàn bộ hoạt động học tập.',
      'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    ],
    [
      'title' => 'Học mọi nơi',
      'description' => 'Tối ưu cho mobile và tablet. Học sinh làm bài trên điện thoại, giáo viên chấm bài mọi lúc mọi nơi.',
      'icon' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/><path d="M10 6h4"/>',
    ],
  ];

  $testimonials = [
    ['name' => 'Nguyễn Thị Lan', 'role' => 'Giáo viên Toán - THPT Chu Văn An, HN', 'initials' => 'NL', 'quote' => 'VietQuiz giúp tôi tiết kiệm 5 tiếng mỗi tuần. Việc chấm trắc nghiệm tự động là tuyệt vời nhất!'],
    ['name' => 'Trần Minh Tuấn', 'role' => 'Giảng viên CNTT - ĐH Bách Khoa HCM', 'initials' => 'TT', 'quote' => 'Tính năng phân tích học sinh giúp tôi nhanh chóng xác định những em cần hỗ trợ thêm. Rất hữu ích!'],
    ['name' => 'Lê Thu Hà', 'role' => 'Giáo viên Anh văn - THCS Nguyễn Du, ĐN', 'initials' => 'LH', 'quote' => 'Học sinh tôi chủ động học hơn khi dùng nền tảng này. Các em thích tính năng xem kết quả ngay lập tức.'],
  ];

  $plans = [
    [
      'name' => 'Miễn phí',
      'price' => '0đ',
      'period' => '/ mãi mãi',
      'button' => 'Bắt đầu ngay',
      'url' => route('register'),
      'featured' => false,
      'features' => ['Tối đa 3 lớp học', '50 câu hỏi/đề', 'Chấm điểm tự động', 'Báo cáo cơ bản'],
    ],
    [
      'name' => 'Giáo viên Pro',
      'price' => '199K',
      'period' => '/ tháng',
      'button' => 'Dùng thử 7 ngày miễn phí',
      'url' => route('register'),
      'featured' => true,
      'features' => ['Không giới hạn lớp học', 'Không giới hạn câu hỏi', 'AI gợi ý điểm tự luận', 'Phân tích nâng cao', 'Xuất báo cáo PDF', 'Hỗ trợ ưu tiên 24/7'],
    ],
    [
      'name' => 'Doanh nghiệp',
      'price' => 'Liên hệ',
      'period' => 'Giá riêng cho trường/tổ chức',
      'button' => 'Liên hệ ngay',
      'url' => route('login'),
      'featured' => false,
      'features' => ['Tất cả tính năng Pro', 'Quản lý toàn trường', 'SSO / LDAP integration', 'API access', 'Hỗ trợ onboarding riêng', 'SLA 99.9% uptime'],
    ],
  ];
@endphp

@section('body')
<style>
  .landing-page {
    background: var(--background);
    color: var(--foreground);
  }
  .landing-shell {
    max-width: 1320px;
    margin: 0 auto;
    padding: 0 2rem;
  }
  .landing-topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    border-bottom: 1px solid var(--border);
    background: color-mix(in srgb, var(--card) 94%, transparent);
    backdrop-filter: blur(14px);
  }
  .landing-topbar__inner {
    height: 4.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }
  .landing-brand {
    display: inline-flex;
    align-items: center;
    gap: .625rem;
    color: var(--foreground);
    text-decoration: none;
    font-weight: 900;
  }
  .landing-brand:hover {
    color: var(--foreground);
  }
  .landing-brand__mark {
    width: 2rem;
    height: 2rem;
    display: grid;
    place-items: center;
    border-radius: .55rem;
    background: var(--primary);
    color: #fff;
  }
  .landing-brand__mark svg,
  .landing-icon svg,
  .landing-check svg {
    width: 1rem;
    height: 1rem;
  }
  .landing-nav-actions {
    display: flex;
    align-items: center;
    gap: .35rem;
  }
  .landing-nav-actions .landing-nav-link {
    color: var(--foreground);
    font-size: var(--text-sm);
    font-weight: 700;
    padding: .45rem .75rem;
    border-radius: var(--radius-md);
    text-decoration: none;
  }
  .landing-nav-actions .landing-nav-link:hover {
    background: var(--muted);
    color: var(--primary);
  }
  .landing-theme-toggle {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 60;
    width: 2.25rem;
    height: 2.25rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--card);
    box-shadow: var(--shadow-md);
  }
  .landing-hero {
    min-height: 43rem;
    display: flex;
    align-items: center;
    text-align: center;
    background:
      linear-gradient(160deg, #eff6ff 0%, #ffffff 44%, #fff7ed 100%);
  }
  .dark .landing-hero {
    background: linear-gradient(160deg, #1e2235 0%, #1a1d2e 52%, #241f2b 100%);
  }
  .landing-hero__inner {
    max-width: 900px;
    margin: 0 auto;
    padding: 5.25rem 0 4.25rem;
  }
  .landing-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 1.9rem;
    padding: .35rem .85rem;
    border: 1px solid color-mix(in srgb, var(--primary) 28%, transparent);
    border-radius: 999px;
    background: color-mix(in srgb, var(--primary) 9%, transparent);
    color: var(--primary);
    font-size: var(--text-xs);
    font-weight: 800;
  }
  .landing-hero h1 {
    margin: 1.15rem 0 1rem;
    font-size: clamp(2.9rem, 5.4vw, 5.05rem);
    line-height: 1.02;
    font-weight: 950;
    letter-spacing: 0;
  }
  .landing-gradient-text {
    background: linear-gradient(135deg, #3b82f6 0%, #7c6ed6 45%, #f97316 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
  .landing-hero p {
    max-width: 680px;
    margin: 0 auto 2rem;
    color: var(--muted-foreground);
    font-size: var(--text-lg);
    line-height: 1.75;
  }
  .landing-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    flex-wrap: wrap;
    margin-top: 2.25rem;
  }
  .landing-stat strong {
    display: block;
    color: var(--primary);
    font-size: clamp(1.5rem, 3vw, 2rem);
    line-height: 1;
    font-weight: 950;
  }
  .landing-stat span {
    display: block;
    margin-top: .35rem;
    color: var(--muted-foreground);
    font-size: var(--text-xs);
  }
  .landing-section {
    padding: 5.25rem 0;
  }
  .landing-section--muted {
    background: var(--muted);
  }
  .landing-section__header {
    max-width: 920px;
    margin: 0 auto 3rem;
    text-align: center;
  }
  .landing-section__header h2 {
    margin: .85rem 0 .65rem;
    font-size: clamp(1.9rem, 2.7vw, 2.75rem);
    font-weight: 950;
    letter-spacing: 0;
  }
  .landing-section__header p {
    color: var(--muted-foreground);
    font-size: var(--text-base);
  }
  .landing-feature-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.35rem;
    max-width: 1080px;
    margin: 0 auto;
  }
  .landing-feature-card,
  .landing-testimonial,
  .landing-plan {
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--card);
    box-shadow: var(--shadow-sm);
  }
  .landing-feature-card {
    min-height: 13rem;
    padding: 1.45rem;
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast), transform var(--transition-fast);
  }
  .landing-feature-card:hover {
    transform: translateY(-3px);
    border-color: var(--primary);
    box-shadow: var(--shadow-lg);
  }
  .landing-icon {
    width: 2.5rem;
    height: 2.5rem;
    display: grid;
    place-items: center;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--primary) 12%, transparent);
    color: var(--primary);
    margin-bottom: 1rem;
  }
  .landing-feature-card h3 {
    margin-bottom: .5rem;
    font-size: 1.05rem;
    font-weight: 900;
    letter-spacing: 0;
  }
  .landing-feature-card p {
    color: var(--muted-foreground);
    font-size: var(--text-sm);
    line-height: 1.65;
  }
  .landing-testimonials {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.5rem;
    max-width: 1080px;
    margin: 0 auto;
  }
  .landing-testimonial {
    padding: 1.5rem;
  }
  .landing-stars {
    color: var(--foreground);
    font-size: var(--text-sm);
    letter-spacing: .08em;
    margin-bottom: .85rem;
  }
  .landing-testimonial blockquote {
    color: var(--foreground);
    font-size: var(--text-sm);
    line-height: 1.65;
    margin-bottom: 1.15rem;
  }
  .landing-person {
    display: flex;
    align-items: center;
    gap: .75rem;
  }
  .landing-person__avatar {
    width: 2.35rem;
    height: 2.35rem;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background: var(--primary);
    color: #fff;
    font-size: var(--text-xs);
    font-weight: 900;
  }
  .landing-person strong {
    display: block;
    font-size: var(--text-sm);
    line-height: 1.25;
  }
  .landing-person span {
    display: block;
    color: var(--muted-foreground);
    font-size: var(--text-xs);
  }
  .landing-pricing {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.5rem;
    max-width: 920px;
    margin: 0 auto;
    align-items: stretch;
  }
  .landing-plan {
    position: relative;
    padding: 2rem;
    text-align: center;
  }
  .landing-plan--featured {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 16%, transparent), var(--shadow-md);
    transform: translateY(-.25rem);
  }
  .landing-plan__tag {
    position: absolute;
    top: -1rem;
    left: 50%;
    transform: translateX(-50%);
    padding: .25rem .75rem;
    border-radius: 999px;
    background: var(--primary);
    color: var(--primary-foreground);
    font-size: .68rem;
    font-weight: 900;
    white-space: nowrap;
  }
  .landing-plan h3 {
    font-size: var(--text-lg);
    font-weight: 900;
    margin-bottom: 1rem;
  }
  .landing-plan__price {
    color: var(--foreground);
    font-size: 2.85rem;
    font-weight: 950;
    line-height: 1;
    margin-bottom: .65rem;
  }
  .landing-plan--featured .landing-plan__price,
  .landing-plan--featured h3 {
    color: var(--primary);
  }
  .landing-plan__period {
    min-height: 2.1rem;
    color: var(--muted-foreground);
    font-size: var(--text-xs);
    margin-bottom: 1.1rem;
  }
  .landing-plan .btn {
    width: 100%;
    margin-bottom: 1.2rem;
  }
  .landing-plan__features {
    display: flex;
    flex-direction: column;
    gap: .7rem;
    text-align: left;
  }
  .landing-check {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    font-size: var(--text-sm);
    color: var(--foreground);
    line-height: 1.4;
  }
  .landing-check svg {
    color: var(--success);
    margin-top: .1rem;
    flex-shrink: 0;
  }
  .landing-cta {
    padding: 5rem 1.5rem;
    text-align: center;
    color: #fff;
    background: linear-gradient(135deg, #3b82f6, #2f9cf4);
  }
  .landing-cta h2 {
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 950;
    margin-bottom: 1rem;
  }
  .landing-cta p {
    max-width: 600px;
    margin: 0 auto 1.75rem;
    color: rgb(255 255 255 / .88);
  }
  .landing-cta .btn {
    background: #fff;
    color: var(--primary);
    border-color: #fff;
    font-weight: 900;
  }
  .landing-footer {
    padding: 3.4rem 0 2.2rem;
    background: #111;
    color: #fff;
  }
  .landing-footer__grid {
    display: grid;
    grid-template-columns: minmax(280px, 2fr) repeat(3, minmax(140px, 1fr));
    gap: 2rem;
    padding-bottom: 2.2rem;
    border-bottom: 1px solid rgb(255 255 255 / .1);
  }
  .landing-footer p,
  .landing-footer a,
  .landing-footer__copy {
    color: rgb(255 255 255 / .72);
    font-size: var(--text-sm);
  }
  .landing-footer a {
    display: block;
    text-decoration: none;
    margin-top: .5rem;
  }
  .landing-footer a:hover {
    color: #fff;
  }
  .landing-footer h3 {
    color: #fff;
    font-size: var(--text-base);
    margin-bottom: .75rem;
  }
  .landing-footer__copy {
    padding-top: 1.4rem;
    text-align: center;
  }
  @media (max-width: 980px) {
    .landing-feature-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
      max-width: 760px;
    }
    .landing-testimonials,
    .landing-pricing {
      grid-template-columns: 1fr;
      max-width: 640px;
    }
    .landing-plan--featured {
      transform: none;
    }
  }
  @media (max-width: 760px) {
    .landing-shell {
      padding: 0 1rem;
    }
    .landing-nav-actions .landing-nav-link {
      display: none;
    }
    .landing-topbar__inner {
      height: auto;
      min-height: 4rem;
      padding: .75rem 0;
      align-items: flex-start;
    }
    .landing-nav-actions {
      flex-wrap: wrap;
      justify-content: flex-end;
    }
    .landing-hero {
      min-height: auto;
    }
    .landing-hero__inner {
      padding: 4rem 0 3.5rem;
    }
    .landing-hero h1 {
      font-size: clamp(2.25rem, 12vw, 3.5rem);
    }
    .landing-feature-grid,
    .landing-footer__grid {
      grid-template-columns: 1fr;
    }
    .landing-section {
      padding: 3.6rem 0;
    }
  }
</style>

<div class="landing-page">

  <header class="landing-topbar">
    <div class="landing-shell landing-topbar__inner">
      <a href="{{ route('home') }}" class="landing-brand" aria-label="VietQuiz">
        <span class="landing-brand__mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v14H6.5A2.5 2.5 0 0 0 4 19.5z"/>
            <path d="M4 5.5v14A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M9 7h6M9 11h4"/>
          </svg>
        </span>
        <span>VietQuiz</span>
      </a>

      <nav class="landing-nav-actions" aria-label="Điều hướng trang giới thiệu">
        <a href="#features" class="landing-nav-link">Tính năng</a>
        <a href="#pricing" class="landing-nav-link">Bảng giá</a>
        <a href="#testimonials" class="landing-nav-link">Đánh giá</a>
        @auth
          <a href="{{ $dashboardUrl }}" class="btn btn-primary btn-sm">Vào Dashboard</a>
          <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Đăng xuất</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Đăng nhập</a>
          <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Đăng ký</a>
        @endauth
      </nav>
    </div>
  </header>

  <main>
    <section class="landing-hero">
      <div class="landing-shell">
        <div class="landing-hero__inner animate-fade-in-up">
          <span class="landing-pill">Hơn 400,000+ Giáo viên & Học sinh tin dùng</span>
          <h1>
            Nâng cao chất lượng
            <span class="landing-gradient-text">Kiểm tra & Đánh giá</span>
            cùng công nghệ AI
          </h1>
          <p>
            Tạo đề kiểm tra nhanh chóng, quản lý ngân hàng câu hỏi thông minh,
            chấm điểm tự động và theo dõi tiến độ học sinh với nền tảng giáo dục toàn diện hàng đầu Việt Nam.
          </p>
          <a href="{{ $dashboardUrl ?? route('register') }}" class="btn btn-primary btn-lg">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.3">
              <rect x="3" y="3" width="7" height="7"/>
              <rect x="14" y="3" width="7" height="7"/>
              <rect x="14" y="14" width="7" height="7"/>
              <rect x="3" y="14" width="7" height="7"/>
            </svg>
            {{ auth()->check() ? 'Vào Dashboard' : 'Dùng thử miễn phí' }}
          </a>

          <div class="landing-stats" aria-label="Số liệu VietQuiz">
            <div class="landing-stat"><strong>400K+</strong><span>Người dùng</span></div>
            <div class="landing-stat"><strong>50K+</strong><span>Giáo viên</span></div>
            <div class="landing-stat"><strong>2M+</strong><span>Bài kiểm tra</span></div>
            <div class="landing-stat"><strong>98%</strong><span>Hài lòng</span></div>
          </div>
        </div>
      </div>
    </section>

    <section class="landing-section" id="features">
      <div class="landing-shell">
        <div class="landing-section__header">
          <span class="landing-pill">Tính năng</span>
          <h2>Mọi thứ bạn cần để dạy và học hiệu quả</h2>
          <p>Nền tảng toàn diện từ tạo đề, chấm điểm đến phân tích, tiết kiệm thời gian và nâng cao kết quả.</p>
        </div>

        <div class="landing-feature-grid">
          @foreach($features as $feature)
            <article class="landing-feature-card">
              <div class="landing-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $feature['icon'] !!}</svg>
              </div>
              <h3>{{ $feature['title'] }}</h3>
              <p>{{ $feature['description'] }}</p>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="landing-section landing-section--muted" id="testimonials">
      <div class="landing-shell">
        <div class="landing-section__header">
          <span class="landing-pill">Đánh giá</span>
          <h2>Được tin dùng bởi hàng ngàn giáo viên</h2>
        </div>

        <div class="landing-testimonials">
          @foreach($testimonials as $testimonial)
            <article class="landing-testimonial">
              <div class="landing-stars" aria-label="5 sao">★★★★★</div>
              <blockquote>“{{ $testimonial['quote'] }}”</blockquote>
              <div class="landing-person">
                <span class="landing-person__avatar">{{ $testimonial['initials'] }}</span>
                <span>
                  <strong>{{ $testimonial['name'] }}</strong>
                  <span>{{ $testimonial['role'] }}</span>
                </span>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="landing-section" id="pricing">
      <div class="landing-shell">
        <div class="landing-section__header">
          <span class="landing-pill">Bảng giá</span>
          <h2>Minh bạch, không ẩn phí</h2>
          <p>Bắt đầu miễn phí, nâng cấp khi cần.</p>
        </div>

        <div class="landing-pricing">
          @foreach($plans as $plan)
            <article class="landing-plan {{ $plan['featured'] ? 'landing-plan--featured' : '' }}">
              @if($plan['featured'])
                <span class="landing-plan__tag">Phổ biến nhất</span>
              @endif
              <h3>{{ $plan['name'] }}</h3>
              <div class="landing-plan__price">{{ $plan['price'] }}</div>
              <div class="landing-plan__period">{{ $plan['period'] }}</div>
              <a href="{{ $plan['url'] }}" class="btn {{ $plan['featured'] ? 'btn-primary' : 'btn-outline' }}">{{ $plan['button'] }}</a>
              <div class="landing-plan__features">
                @foreach($plan['features'] as $item)
                  <div class="landing-check">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 6 9 17l-5-5"/>
                    </svg>
                    <span>{{ $item }}</span>
                  </div>
                @endforeach
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="landing-cta">
      <h2>Sẵn sàng bắt đầu?</h2>
      <p>Tham gia cùng 400,000+ giáo viên đang sử dụng VietQuiz mỗi ngày. Miễn phí, không cần thẻ tín dụng.</p>
      <a href="{{ $dashboardUrl ?? route('register') }}" class="btn btn-lg">{{ auth()->check() ? 'Vào Dashboard' : 'Đăng ký miễn phí ngay →' }}</a>
      <div style="margin-top:1rem;font-size:var(--text-sm);color:rgb(255 255 255 / .78);">Không cần thẻ tín dụng • Hủy bất cứ lúc nào</div>
    </section>
  </main>

  <footer class="landing-footer">
    <div class="landing-shell">
      <div class="landing-footer__grid">
        <div>
          <a href="{{ route('home') }}" class="landing-brand" style="color:#fff;margin-bottom:1rem;">
            <span class="landing-brand__mark">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v14H6.5A2.5 2.5 0 0 0 4 19.5z"/>
                <path d="M4 5.5v14A2.5 2.5 0 0 1 6.5 17H20"/>
              </svg>
            </span>
            <span>VietQuiz</span>
          </a>
          <p>Nền tảng kiểm tra đánh giá toàn diện hàng đầu Việt Nam.</p>
        </div>
        <div>
          <h3>Sản phẩm</h3>
          <a href="#features">Tính năng</a>
          <a href="#pricing">Bảng giá</a>
          <a href="{{ route('login') }}">API</a>
        </div>
        <div>
          <h3>Hỗ trợ</h3>
          <a href="#features">Tài liệu</a>
          <a href="#testimonials">FAQ</a>
          <a href="{{ route('login') }}">Liên hệ</a>
        </div>
        <div>
          <h3>Pháp lý</h3>
          <a href="{{ route('login') }}">Điều khoản</a>
          <a href="{{ route('login') }}">Bảo mật</a>
        </div>
      </div>
      <div class="landing-footer__copy">© 2026 VietQuiz. Nền tảng Kiểm tra Đánh giá Toàn diện - Made in Vietnam</div>
    </div>
  </footer>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('a[href^="#"]').forEach(function(link) {
    link.addEventListener('click', function(event) {
      var target = document.querySelector(link.getAttribute('href'));
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
</script>
@endpush
