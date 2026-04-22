{{-- Teacher: class-detail --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@section('content')
  <!-- Breadcrumb -->
        <div class="breadcrumb stagger-children">
          <a href="{{ route('teacher.classes') }}">Lớp học</a>
          <span class="breadcrumb-sep">›</span>
          <span class="active">Lớp 10A — Toán Đại số</span>
        </div>

        <!-- Header -->
        <div
          style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;"
          class="stagger-children">
          <div style="display:flex;align-items:center;gap:1rem;">
            <div
              style="width:4rem;height:4rem;border-radius:var(--radius-lg);background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:1.75rem;">
              📐</div>
            <div>
              <h1 style="font-size:var(--text-2xl);">Lớp 10A — Toán Đại số</h1>
              <div style="display:flex;gap:0.5rem;margin-top:0.25rem;flex-wrap:wrap;">
                <span class="badge badge-primary">32 học sinh</span>
                <span class="badge badge-success">Đang hoạt động</span>
                <span class="badge badge-outline">Mã: VQ-10A</span>
              </div>
            </div>
          </div>
          <div style="display:flex;gap:0.5rem;">
            <button class="btn btn-outline gap-2" onclick="copyCode()">📋 Sao chép mã lớp</button>
            <button class="btn btn-primary gap-2" onclick="addQuiz()">+ Giao bài kiểm tra</button>
          </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
          <div class="stat-card">
            <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Học sinh</div>
            <div class="stat-card__value">32</div>
          </div>
          <div class="stat-card">
            <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm TB lớp</div>
            <div class="stat-card__value" style="color:var(--success);">81.3%</div>
          </div>
          <div class="stat-card">
            <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Bài thi đã giao
            </div>
            <div class="stat-card__value">8</div>
          </div>
          <div class="stat-card">
            <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Tỷ lệ hoàn thành
            </div>
            <div class="stat-card__value" style="color:var(--info);">94%</div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="nav-tabs stagger-children" style="margin-bottom:1.25rem;">
          <button class="nav-tab active" onclick="showTab('students',this)">Học sinh</button>
          <button class="nav-tab" onclick="showTab('quizzes',this)">Bài kiểm tra</button>
          <button class="nav-tab" onclick="showTab('progress',this)">Tiến độ</button>
          <button class="nav-tab" onclick="showTab('settings',this)">Cài đặt lớp</button>
        </div>

        <!-- Students tab -->
        <div id="tab-students" class="stagger-children">
          <div class="card">
            <div class="card-header">
              <div class="flex items-center justify-between flex-wrap gap-3">
                <h3 class="card-title">Danh sách Học sinh</h3>
                <div style="display:flex;gap:0.5rem;">
                  <div class="search-input-wrapper" style="max-width:240px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor"
                      stroke-width="2" viewBox="0 0 24 24" class="search-icon">
                      <circle cx="11" cy="11" r="8" />
                      <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="search" class="input" placeholder="Tìm học sinh..." style="font-size:var(--text-sm);"
                      id="stu-search" oninput="filterStudents()" />
                  </div>
                  <button class="btn btn-outline btn-sm" onclick="removeStudent()">Xóa khỏi lớp</button>
                </div>
              </div>
            </div>
            <div class="table-wrapper" style="border:none;border-radius:0;">
              <table>
                <thead>
                  <tr>
                    <th>Học sinh</th>
                    <th>Điểm TB</th>
                    <th>Hoàn thành</th>
                    <th>Lần cuối</th>
                    <th>Xếp loại</th>
                  </tr>
                </thead>
                <tbody id="stu-table"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Quizzes tab -->
        <div id="tab-quizzes" style="display:none;" class="stagger-children">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Bài kiểm tra đã giao</h3>
            </div>
            <div class="table-wrapper" style="border:none;border-radius:0;">
              <table>
                <thead>
                  <tr>
                    <th>Đề thi</th>
                    <th>Ngày giao</th>
                    <th>Hạn làm</th>
                    <th>Đã làm</th>
                    <th>Điểm TB</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="quiz-table"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Progress tab -->
        <div id="tab-progress" style="display:none;" class="stagger-children">
          <div class="stats-grid stats-grid-4" style="margin-bottom:1.5rem;">
            <div class="stat-card">
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm cao nhất
              </div>
              <div class="stat-card__value" style="color:var(--success);">95.2%</div>
            </div>
            <div class="stat-card">
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Điểm thấp nhất
              </div>
              <div class="stat-card__value" style="color:var(--destructive);">42.3%</div>
            </div>
            <div class="stat-card">
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Giỏi (≥90%)</div>
              <div class="stat-card__value" style="color:var(--success);">8</div>
            </div>
            <div class="stat-card">
              <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:.5rem;">Cần cải thiện
                (&lt;60%)</div>
              <div class="stat-card__value" style="color:var(--warning);">4</div>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Phân phối điểm</h3>
              </div>
              <div class="card-content"><canvas id="distChart" height="220"></canvas></div>
            </div>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Xu hướng điểm TB</h3>
              </div>
              <div class="card-content"><canvas id="trendChart" height="220"></canvas></div>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1.5rem;">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">🏆 Top 5 Học sinh</h3>
              </div>
              <div class="card-content" id="top-students" style="display:flex;flex-direction:column;gap:.75rem;"></div>
            </div>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">⚠️ Cần hỗ trợ</h3>
              </div>
              <div class="card-content" id="weak-students" style="display:flex;flex-direction:column;gap:.75rem;"></div>
            </div>
          </div>
        </div>

        <!-- Settings tab -->
        <div id="tab-settings" style="display:none;" class="stagger-children">
          <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
              <h3 class="card-title">Thông tin Lớp học</h3>
            </div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1.25rem;">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group"><label class="label">Tên lớp</label><input type="text" class="input"
                    value="Lớp 10A — Toán Đại số" /></div>
                <div class="form-group"><label class="label">Mã lớp</label><input type="text" class="input"
                    value="VQ-10A" readonly style="background:var(--muted);cursor:not-allowed;" /></div>
              </div>
              <div class="form-group"><label class="label">Mô tả</label><textarea class="input"
                  style="min-height:4rem;">Lớp Toán đại số dành cho học sinh khối 10. Chương trình theo chuẩn Bộ GD-ĐT 2025.</textarea>
              </div>
              <div class="form-group"><label class="label">Môn học</label>
                <select class="input select">
                  <option selected>Toán Đại số</option>
                  <option>Toán Hình học</option>
                  <option>Vật lý</option>
                  <option>Hóa học</option>
                  <option>Ngữ văn</option>
                  <option>Tiếng Anh</option>
                </select>
              </div>
              <div style="display:flex;justify-content:flex-end;"><button class="btn btn-primary"
                  onclick="toastSuccess('Đã lưu cài đặt lớp học')">💾 Lưu thay đổi</button></div>
            </div>
          </div>
          <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
              <h3 class="card-title">Tùy chọn</h3>
            </div>
            <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
              <div
                style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;border-bottom:1px solid var(--border);">
                <div>
                  <div style="font-weight:500;">Trạng thái lớp</div>
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Lớp đang hoạt động sẽ hiển thị
                    cho học sinh</div>
                </div>
                <label class="switch"><input type="checkbox" checked /><span class="switch-slider"></span></label>
              </div>
              <div
                style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;border-bottom:1px solid var(--border);">
                <div>
                  <div style="font-weight:500;">Cho phép tham gia bằng mã</div>
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Học sinh có thể tự tham gia lớp
                    bằng mã VQ-10A</div>
                </div>
                <label class="switch"><input type="checkbox" checked /><span class="switch-slider"></span></label>
              </div>
              <div
                style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;border-bottom:1px solid var(--border);">
                <div>
                  <div style="font-weight:500;">Thông báo kết quả</div>
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Gửi email thông báo khi học sinh
                    hoàn thành bài kiểm tra</div>
                </div>
                <label class="switch"><input type="checkbox" checked /><span class="switch-slider"></span></label>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;">
                <div>
                  <div style="font-weight:500;">Hiển thị đáp án sau nộp bài</div>
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Học sinh sẽ thấy đáp án đúng sau
                    khi hoàn thành bài thi</div>
                </div>
                <label class="switch"><input type="checkbox" /><span class="switch-slider"></span></label>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header">
              <h3 class="card-title" style="color:var(--destructive);">Vùng nguy hiểm</h3>
            </div>
            <div class="card-content">
              <div
                style="display:flex;justify-content:space-between;align-items:center;padding:1rem;background:color-mix(in srgb,var(--destructive) 5%,transparent);border-radius:var(--radius-md);border:1px solid color-mix(in srgb,var(--destructive) 20%,transparent);">
                <div>
                  <div style="font-weight:500;color:var(--destructive);">Giải tán lớp học</div>
                  <div style="font-size:var(--text-sm);color:var(--muted-foreground);">Xóa lớp và toàn bộ dữ liệu liên
                    quan. Hành động này không thể hoàn tác.</div>
                </div>
                <button class="btn btn-destructive btn-sm" onclick="dissolveClass()">Giải tán</button>
              </div>
            </div>
          </div>
        </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function () {
      /* ---- Toast helper (inline – no module import needed) ---- */
      function showToast(msg, type) {
        var tc = document.getElementById('toast-container'); if (!tc) return;
        var e = document.createElement('div');
        e.className = 'toast toast-' + type;
        e.innerHTML = '<span>' + (type === 'error' ? '❌' : type === 'info' ? 'ℹ️' : '✅') + '</span><span>' + msg + '</span>';
        tc.appendChild(e);
        setTimeout(function () { e.classList.add('show'); }, 10);
        setTimeout(function () { e.classList.remove('show'); setTimeout(function () { e.remove(); }, 300); }, 3000);
      }
      window.toastSuccess = function (m) { showToast(m, 'success'); };
      window.toastInfo = function (m) { showToast(m, 'info'); };
      var toastSuccess = window.toastSuccess;
      var toastInfo = window.toastInfo;

      /* ---- Data ---- */
      var STUDENTS = [
        { n: 'Nguyễn Minh Anh', avg: 95.2, done: 8, last: 'Hôm nay', g: 'A' },
        { n: 'Trần Thị Bích', avg: 88.4, done: 8, last: 'Hôm qua', g: 'B' },
        { n: 'Lê Văn Chiến', avg: 91.5, done: 7, last: 'Hôm qua', g: 'A' },
        { n: 'Ngô Quang Hùng', avg: 82.1, done: 8, last: '2 ngày trước', g: 'B' },
        { n: 'Mai Thị Lan', avg: 71.8, done: 8, last: 'Hôm nay', g: 'C' },
        { n: 'Đỗ Thị Nga', avg: 42.3, done: 4, last: '2 tuần trước', g: 'F' },
        { n: 'Chu Thị Thảo', avg: 69.4, done: 8, last: '3 ngày trước', g: 'D' },
        { n: 'Phạm Đức Toàn', avg: 93.8, done: 8, last: 'Hôm nay', g: 'A' },
        { n: 'Hoàng Thu Hà', avg: 86.5, done: 8, last: 'Hôm qua', g: 'B' },
        { n: 'Vũ Đình Khoa', avg: 78.3, done: 7, last: '3 ngày trước', g: 'C' },
        { n: 'Đặng Minh Tuấn', avg: 90.1, done: 8, last: 'Hôm nay', g: 'A' },
        { n: 'Bùi Thị Hương', avg: 84.7, done: 8, last: 'Hôm qua', g: 'B' },
        { n: 'Lý Hoàng Nam', avg: 67.2, done: 6, last: '5 ngày trước', g: 'D' },
        { n: 'Trịnh Quốc Bảo', avg: 75.9, done: 8, last: '2 ngày trước', g: 'C' },
        { n: 'Nguyễn Thị Dung', avg: 89.3, done: 8, last: 'Hôm nay', g: 'B' },
        { n: 'Phan Văn Đạt', avg: 55.6, done: 5, last: '1 tuần trước', g: 'F' },
        { n: 'Cao Thị Yến', avg: 92.4, done: 8, last: 'Hôm nay', g: 'A' },
        { n: 'Đinh Bá Phúc', avg: 80.8, done: 8, last: 'Hôm qua', g: 'B' },
        { n: 'Hồ Thanh Sơn', avg: 73.1, done: 7, last: '4 ngày trước', g: 'C' },
        { n: 'Tô Minh Quân', avg: 87.6, done: 8, last: 'Hôm nay', g: 'B' },
        { n: 'Dương Thị Linh', avg: 94.0, done: 8, last: 'Hôm nay', g: 'A' },
        { n: 'Lê Thị Phương', avg: 76.4, done: 8, last: '2 ngày trước', g: 'C' },
        { n: 'Nguyễn Hải Đăng', avg: 81.9, done: 8, last: 'Hôm qua', g: 'B' },
        { n: 'Trần Văn Kiên', avg: 63.5, done: 6, last: '6 ngày trước', g: 'D' },
        { n: 'Phạm Thị Mai', avg: 88.0, done: 8, last: 'Hôm nay', g: 'B' },
        { n: 'Vũ Hoàng Long', avg: 79.3, done: 7, last: '3 ngày trước', g: 'C' },
        { n: 'Đỗ Thanh Tùng', avg: 85.2, done: 8, last: 'Hôm qua', g: 'B' },
        { n: 'Mai Văn Hùng', avg: 70.5, done: 8, last: '4 ngày trước', g: 'C' },
        { n: 'Nguyễn Thị Hồng', avg: 91.7, done: 8, last: 'Hôm nay', g: 'A' },
        { n: 'Trần Đức Mạnh', avg: 77.8, done: 7, last: '2 ngày trước', g: 'C' },
        { n: 'Lê Thị Thanh', avg: 83.6, done: 8, last: 'Hôm qua', g: 'B' },
        { n: 'Hoàng Văn Trí', avg: 58.1, done: 5, last: '1 tuần trước', g: 'F' },
      ];
      var QUIZZES = [
        { name: 'KT Chương 1 - Hàm số', date: '01/03/2025', due: '10/03/2025', done: 32, total: 32, avg: 81, status: 'done' },
        { name: 'KT Giữa kỳ', date: '15/03/2025', due: '20/03/2025', done: 30, total: 32, avg: 78, status: 'done' },
        { name: 'Trắc nghiệm Phương trình', date: '25/03/2025', due: '01/04/2025', done: 28, total: 32, avg: 84, status: 'done' },
        { name: 'KT Chương 3 - Bất đẳng thức', date: '01/04/2025', due: '10/04/2025', done: 25, total: 32, avg: 76, status: 'active' },
        { name: 'Ôn tập cuối kỳ 1', date: '05/04/2025', due: '12/04/2025', done: 18, total: 32, avg: 0, status: 'active' },
        { name: 'KT Chương 4 - Hệ phương trình', date: '08/04/2025', due: '15/04/2025', done: 8, total: 32, avg: 0, status: 'active' },
        { name: 'Đề thi cuối kỳ 1', date: '12/04/2025', due: '20/04/2025', done: 0, total: 32, avg: 0, status: 'scheduled' },
        { name: 'KT Chương 5 - Đạo hàm', date: '20/04/2025', due: '30/04/2025', done: 0, total: 32, avg: 0, status: 'scheduled' },
      ];

      /* ---- Render students ---- */
      function avgColor(a) { return a >= 90 ? 'var(--success)' : a >= 70 ? 'var(--info)' : a >= 50 ? 'var(--warning)' : 'var(--destructive)'; }
      var COLORS = ['#3b82f6', '#ef4444', '#22c55e', '#f97316', '#a855f7', '#06b6d4', '#ec4899', '#eab308', '#14b8a6', '#f43f5e'];
      function renderStudents(list) {
        var tb = document.getElementById('stu-table');
        if (!list.length) { tb.innerHTML = '<tr><td colspan="5"><div style="padding:3rem;text-align:center;color:var(--muted-foreground)"><div style="font-size:3rem;margin-bottom:.75rem">👩‍🎓</div><h3 style="font-weight:600;color:var(--foreground)">Không tìm thấy học sinh</h3></div></td></tr>'; return; }
        tb.innerHTML = list.map(function (s, i) {
          var ini = s.n.split(' ').pop()[0];
          var c = COLORS[i % COLORS.length];
          return '<tr>'
            + '<td><div style="display:flex;align-items:center;gap:.75rem"><div class="avatar avatar-sm" style="background:' + c + '22;color:' + c + '">' + ini + '</div><span style="font-weight:500">' + s.n + '</span></div></td>'
            + '<td><span style="font-weight:600;color:' + avgColor(s.avg) + '">' + s.avg + '%</span></td>'
            + '<td><div style="display:flex;align-items:center;gap:.5rem"><div class="progress" style="width:4rem;height:.375rem;"><div class="progress-bar" style="width:' + (s.done / 8 * 100) + '%;background:' + (s.done >= 7 ? 'var(--success)' : 'var(--warning)') + ';"></div></div><span style="font-size:var(--text-sm)">' + s.done + '/8</span></div></td>'
            + '<td style="font-size:var(--text-sm);color:var(--muted-foreground)">' + s.last + '</td>'
            + '<td><div class="grade-circle grade-' + s.g.toLowerCase() + '" style="width:1.75rem;height:1.75rem;font-size:var(--text-xs)">' + s.g + '</div></td>'
            + '</tr>';
        }).join('');
      }
      window.filterStudents = function () {
        var q = (document.getElementById('stu-search').value || '').toLowerCase();
        renderStudents(STUDENTS.filter(function (s) { return !q || s.n.toLowerCase().indexOf(q) !== -1; }));
      };
      renderStudents(STUDENTS);

      /* ---- Render quizzes ---- */
      (function () {
        var tb = document.getElementById('quiz-table');
        function statusBadge(st) {
          if (st === 'done') return '<span class="badge badge-success">Hoàn thành</span>';
          if (st === 'active') return '<span class="badge badge-info badge-dot">Đang diễn ra</span>';
          return '<span class="badge badge-outline">Lên lịch</span>';
        }
        tb.innerHTML = QUIZZES.map(function (q) {
          var pct = Math.round(q.done / q.total * 100);
          var avgTxt = q.avg > 0 ? '<span style="font-weight:600;color:var(--success)">' + q.avg + '%</span>' : '<span style="color:var(--muted-foreground)">—</span>';
          return '<tr>'
            + '<td><div style="display:flex;align-items:center;gap:.5rem"><span style="font-weight:500">' + q.name + '</span>' + statusBadge(q.status) + '</div></td>'
            + '<td style="font-size:var(--text-sm);color:var(--muted-foreground)">' + q.date + '</td>'
            + '<td style="font-size:var(--text-sm)">' + q.due + '</td>'
            + '<td><div style="display:flex;align-items:center;gap:.5rem"><div class="progress" style="width:4rem;height:.375rem;"><div class="progress-bar" style="width:' + pct + '%;background:' + (pct >= 80 ? 'var(--success)' : pct > 0 ? 'var(--info)' : 'var(--muted-foreground)') + ';"></div></div><span style="font-size:var(--text-sm)">' + q.done + '/' + q.total + '</span></div></td>'
            + '<td>' + avgTxt + '</td>'
            + '<td><button class="btn btn-ghost btn-sm">Xem chi tiết</button></td>'
            + '</tr>';
        }).join('');
      })();

      /* ---- Progress: Top & Weak students ---- */
      (function () {
        var sorted = STUDENTS.slice().sort(function (a, b) { return b.avg - a.avg; });
        var top5 = sorted.slice(0, 5);
        var weak = sorted.filter(function (s) { return s.avg < 60; });
        var medals = ['🥇', '🥈', '🥉', '4', '5'];
        document.getElementById('top-students').innerHTML = top5.map(function (s, i) {
          return '<div style="display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;border-radius:var(--radius-md);background:' + (i === 0 ? 'color-mix(in srgb,#eab308 8%,transparent)' : 'transparent') + ';">'
            + '<span style="font-size:' + (i < 3 ? '1.25rem' : 'var(--text-sm)') + ';width:1.75rem;text-align:center;' + (i >= 3 ? 'color:var(--muted-foreground);font-weight:600;' : '') + '">' + medals[i] + '</span>'
            + '<span style="flex:1;font-weight:500;">' + s.n + '</span>'
            + '<span style="font-weight:700;color:var(--success);">' + s.avg + '%</span>'
            + '</div>';
        }).join('');
        document.getElementById('weak-students').innerHTML = weak.length ? weak.map(function (s) {
          return '<div style="display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;border-radius:var(--radius-md);background:color-mix(in srgb,var(--destructive) 5%,transparent);">'
            + '<span style="font-size:1.25rem;">⚠️</span>'
            + '<span style="flex:1;font-weight:500;">' + s.n + '</span>'
            + '<span style="font-weight:700;color:var(--destructive);">' + s.avg + '%</span>'
            + '</div>';
        }).join('') : '<div style="padding:1.5rem;text-align:center;color:var(--muted-foreground);"><div style="font-size:2rem;margin-bottom:.5rem;">🎉</div><div>Tất cả học sinh đều đạt yêu cầu!</div></div>';
      })();

      /* ---- Tabs + Charts ---- */
      var chartsDone = false;
      window.showTab = function (tab, el) {
        ['students', 'quizzes', 'progress', 'settings'].forEach(function (t) {
          document.getElementById('tab-' + t).style.display = t === tab ? '' : 'none';
        });
        document.querySelectorAll('.nav-tab').forEach(function (b) { b.classList.remove('active'); });
        el.classList.add('active');
        if (tab === 'progress' && !chartsDone) {
          chartsDone = true;
          new Chart(document.getElementById('distChart'), { type: 'bar', data: { labels: ['0-49', '50-59', '60-69', '70-79', '80-89', '90-100'], datasets: [{ label: 'Số học sinh', data: [1, 3, 3, 8, 10, 7], backgroundColor: ['#ef4444', '#f97316', '#eab308', '#06b6d4', '#3b82f6', '#22c55e'], borderRadius: 6 }] }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 2 } } } } });
          new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: ['KT1', 'KT2', 'KT3', 'KT4', 'KT5', 'KT6', 'KT7', 'KT8'], datasets: [{ label: 'Điểm TB', data: [72, 74, 76, 73, 78, 81, 80, 83], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)', fill: true, tension: .4, pointRadius: 5, pointBackgroundColor: '#3b82f6' }, { label: 'Điểm cao nhất', data: [92, 95, 93, 91, 95, 96, 94, 95], borderColor: '#22c55e', backgroundColor: 'transparent', borderDash: [5, 5], tension: .4, pointRadius: 3, pointBackgroundColor: '#22c55e' }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { min: 50, max: 100 } } } });
        }
      };
      window.copyCode = function () { navigator.clipboard && navigator.clipboard.writeText('VQ-10A'); toastSuccess('Đã sao chép mã lớp VQ-10A'); };
      window.addQuiz = function () { toastInfo('Mở trang giao bài kiểm tra'); };
      window.removeStudent = function () { toastInfo('Chọn học sinh để xóa khỏi lớp'); };
      window.dissolveClass = function () { if (confirm('Giải tán lớp học này?')) window.location.href = 'classes.html'; };
    })();
</script>
@endpush
