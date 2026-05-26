@extends('layouts.app')

@push('styles')
<style>
  :root {
    --admin-ink: #0f172a;
    --admin-blue: #2563eb;
    --admin-cyan: #06b6d4;
    --admin-lime: #84cc16;
    --admin-glass: rgba(255, 255, 255, .78);
  }

  .admin-login-wrap {
    min-height:100vh;
    display:grid;
    place-items:center;
    padding:clamp(1.25rem, 4vw, 3rem);
    background:
      radial-gradient(circle at 18% 16%, rgba(37, 99, 235, .22), transparent 28rem),
      radial-gradient(circle at 82% 18%, rgba(6, 182, 212, .18), transparent 24rem),
      linear-gradient(145deg, #eef5ff 0%, #f8fbff 45%, #ecfeff 100%);
    overflow:hidden;
    position:relative;
    color:var(--admin-ink);
    font-family:"Be Vietnam Pro", "Segoe UI", sans-serif;
  }

  .admin-login-wrap::before,
  .admin-login-wrap::after {
    content:"";
    position:absolute;
    border-radius:999px;
    pointer-events:none;
  }

  .admin-login-wrap::before {
    width:32rem;
    height:32rem;
    left:-13rem;
    bottom:-13rem;
    background:linear-gradient(135deg, rgba(37, 99, 235, .22), rgba(132, 204, 22, .18));
    filter:blur(4px);
  }

  .admin-login-wrap::after {
    width:20rem;
    height:20rem;
    right:-7rem;
    top:8rem;
    background:rgba(6, 182, 212, .16);
    border:1px solid rgba(6, 182, 212, .2);
  }

  .admin-login-shell {
    width:min(1040px, 100%);
    display:grid;
    grid-template-columns:minmax(0, 1.08fr) minmax(360px, .92fr);
    gap:1rem;
    position:relative;
    z-index:1;
  }

  .admin-login-hero,
  .admin-login-panel {
    border:1px solid rgba(148, 163, 184, .28);
    background:var(--admin-glass);
    box-shadow:0 28px 80px rgba(15, 23, 42, .12);
    backdrop-filter:blur(18px);
  }

  .admin-login-hero {
    min-height:34rem;
    border-radius:2rem;
    padding:2rem;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    overflow:hidden;
    position:relative;
  }

  .admin-login-hero::before {
    content:"";
    position:absolute;
    inset:auto -8rem -10rem auto;
    width:26rem;
    height:26rem;
    border-radius:999px;
    background:linear-gradient(135deg, rgba(37, 99, 235, .18), rgba(6, 182, 212, .26));
  }

  .admin-brand-row {
    display:flex;
    align-items:center;
    gap:.9rem;
    position:relative;
  }

  .admin-login-logo {
    width:3.5rem;
    height:3.5rem;
    border-radius:1.15rem;
    background:linear-gradient(135deg, var(--admin-blue), #38bdf8);
    color:white;
    display:grid;
    place-items:center;
    font-weight:900;
    letter-spacing:-.05em;
    box-shadow:0 18px 36px rgba(37, 99, 235, .24);
  }

  .admin-brand-name {
    display:grid;
    gap:.1rem;
  }

  .admin-brand-name strong {
    font-size:1.05rem;
    line-height:1;
  }

  .admin-brand-name span {
    color:#64748b;
    font-size:.82rem;
    font-weight:700;
  }

  .admin-hero-copy {
    max-width:34rem;
    position:relative;
  }

  .admin-hero-copy .eyebrow {
    display:inline-flex;
    align-items:center;
    gap:.45rem;
    padding:.45rem .7rem;
    border-radius:999px;
    background:rgba(37, 99, 235, .1);
    color:#1d4ed8;
    font-size:.74rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.08em;
    margin-bottom:1rem;
  }

  .admin-hero-copy h1 {
    margin:0;
    font-size:clamp(2.35rem, 5vw, 4.35rem);
    line-height:.95;
    letter-spacing:-.07em;
    font-weight:950;
  }

  .admin-hero-copy p {
    margin:1.1rem 0 0;
    color:#475569;
    font-size:1rem;
    line-height:1.7;
    max-width:30rem;
  }

  .admin-login-metrics {
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:.75rem;
    position:relative;
  }

  .admin-login-metric {
    border:1px solid rgba(148, 163, 184, .28);
    border-radius:1.25rem;
    padding:1rem;
    background:rgba(255, 255, 255, .54);
  }

  .admin-login-metric strong {
    display:block;
    font-size:1.35rem;
    line-height:1;
    letter-spacing:-.04em;
  }

  .admin-login-metric span {
    display:block;
    margin-top:.4rem;
    color:#64748b;
    font-size:.78rem;
    font-weight:700;
  }

  .admin-login-panel {
    border-radius:2rem;
    padding:.7rem;
    align-self:center;
  }

  .admin-login-card {
    border-radius:1.55rem;
    background:rgba(255,255,255,.9);
    overflow:hidden;
    border:1px solid rgba(226, 232, 240, .9);
  }

  .admin-login-card-head {
    padding:2rem 2rem 1.25rem;
    border-bottom:1px solid rgba(226, 232, 240, .9);
  }

  .admin-login-card-head h2 {
    margin:0;
    font-size:1.65rem;
    line-height:1.15;
    letter-spacing:-.04em;
    font-weight:950;
  }

  .admin-login-card-head p {
    margin:.55rem 0 0;
    color:#64748b;
    line-height:1.55;
  }

  .admin-login-form {
    display:flex;
    flex-direction:column;
    gap:1rem;
    padding:1.5rem 2rem 2rem;
  }

  .admin-login-form .label {
    color:#1e293b;
    font-weight:850;
    margin-bottom:.42rem;
  }

  .admin-login-form .input {
    height:3.15rem;
    border-radius:1rem;
    border-color:#dbe4f0;
    background:#f8fbff;
    font-weight:700;
    transition:border-color .18s ease, box-shadow .18s ease, background .18s ease;
  }

  .admin-login-form .input:focus {
    border-color:rgba(37, 99, 235, .72);
    background:#fff;
    box-shadow:0 0 0 4px rgba(37, 99, 235, .12);
  }

  .admin-login-submit {
    height:3.15rem;
    border:0;
    border-radius:1rem;
    background:linear-gradient(135deg, #2563eb, #0ea5e9);
    color:white;
    font-size:1rem;
    font-weight:900;
    cursor:pointer;
    box-shadow:0 18px 34px rgba(37, 99, 235, .22);
    transition:transform .18s ease, box-shadow .18s ease, filter .18s ease;
  }

  .admin-login-submit:hover {
    transform:translateY(-1px);
    filter:saturate(1.08);
    box-shadow:0 22px 42px rgba(37, 99, 235, .28);
  }

  .admin-login-note {
    margin:0;
    padding:1rem;
    border-radius:1rem;
    background:linear-gradient(135deg, rgba(37, 99, 235, .08), rgba(6, 182, 212, .08));
    color:#526173;
    font-size:.84rem;
    line-height:1.55;
  }

  .admin-login-note strong {
    color:#1e293b;
  }

  .admin-login-alert {
    border-radius:1rem;
    border:1px solid rgba(220, 38, 38, .2);
    background:rgba(254, 226, 226, .72);
    color:#991b1b;
    padding:.9rem 1rem;
    font-weight:700;
    line-height:1.45;
  }

  @media (max-width: 900px) {
    .admin-login-wrap { align-items:start; overflow:auto; }
    .admin-login-shell { grid-template-columns:1fr; }
    .admin-login-hero { min-height:auto; gap:2rem; }
    .admin-login-panel { align-self:stretch; }
  }

  @media (max-width: 560px) {
    .admin-login-wrap { padding:1rem; }
    .admin-login-hero,
    .admin-login-panel { border-radius:1.35rem; }
    .admin-login-hero { padding:1.25rem; }
    .admin-login-metrics { grid-template-columns:1fr; }
    .admin-login-card-head,
    .admin-login-form { padding-left:1.25rem; padding-right:1.25rem; }
  }
</style>
@endpush

@section('body')
<div class="admin-login-wrap">
  <main class="admin-login-shell" aria-label="Đăng nhập quản trị VietQuiz">
    <section class="admin-login-hero">
      <div class="admin-brand-row">
        <div class="admin-login-logo">VQ</div>
        <div class="admin-brand-name">
          <strong>VietQuiz Admin</strong>
          <span>Trung tâm vận hành hệ thống</span>
        </div>
      </div>

      <div class="admin-hero-copy">
        <div class="eyebrow">Bảng điều khiển bảo mật</div>
        <h1>Quản trị gọn, nhanh và rõ ràng.</h1>
        <p>Theo dõi người dùng, lớp học, nội dung học tập và vận hành hệ thống từ một khu vực quản trị tập trung.</p>
      </div>

      <div class="admin-login-metrics" aria-hidden="true">
        <div class="admin-login-metric">
          <strong>24/7</strong>
          <span>Giám sát hệ thống</span>
        </div>
        <div class="admin-login-metric">
          <strong>RBAC</strong>
          <span>Phân quyền rõ ràng</span>
        </div>
        <div class="admin-login-metric">
          <strong>VQ</strong>
          <span>VietQuiz Console</span>
        </div>
      </div>
    </section>

    <section class="admin-login-panel">
      <div class="admin-login-card">
        <div class="admin-login-card-head">
          <h2>Đăng nhập quản trị</h2>
          <p>Dùng tài khoản admin để truy cập bảng điều khiển tại <strong>/admin</strong>.</p>
        </div>

        <form method="POST" action="{{ route('admin.login') }}" class="admin-login-form">
          @csrf

          @if($errors->any())
            <div class="admin-login-alert">{{ $errors->first() }}</div>
          @endif

          <div class="form-group">
            <label class="label" for="username">Email admin</label>
            <input id="username" name="username" type="email" class="input" value="{{ old('username') }}" placeholder="admin@vietquiz.vn" required autofocus>
          </div>

          <div class="form-group">
            <label class="label" for="password">Mật khẩu</label>
            <input id="password" name="password" type="password" class="input" placeholder="Nhập mật khẩu quản trị" required>
          </div>

          <button class="admin-login-submit" type="submit">Vào trang quản trị</button>

          <!-- <p class="admin-login-note">
            <strong>Lưu ý bảo mật:</strong> tài khoản admin được tạo trong database. Không sử dụng mật khẩu mặc định khi triển khai thật.
          </p> -->
        </form>
      </div>
    </section>
  </main>
</div>
@endsection
