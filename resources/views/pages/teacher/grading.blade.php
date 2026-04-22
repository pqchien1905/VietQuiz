{{-- Teacher: grading --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.grade-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--card);
    padding: 1.25rem;
    transition: box-shadow var(--transition-fast);
}
.grade-card:hover { box-shadow: var(--shadow-md); }
.grade-input {
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    padding: 0.5rem 0.75rem;
    font-weight: 700;
    font-size: var(--text-base);
    width: 100%;
    text-align: center;
    transition: border-color var(--transition-fast);
}
.grade-input:focus { outline: none; border-color: var(--primary); }
.student-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 0.75rem;
    border-bottom: 1px solid var(--border);
    transition: background var(--transition-fast);
}
.student-row:last-child { border-bottom: none; }
.student-row:hover { background: var(--muted); border-radius: var(--radius-sm); }
.avatar-sm {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-xs);
    font-weight: 700;
    flex-shrink: 0;
}
.tab-bar {
    display: flex;
    gap: 0;
    border-bottom: 1px solid var(--border);
    margin-bottom: 1.25rem;
}
.tab-btn {
    padding: 0.75rem 1.25rem;
    font-size: var(--text-sm);
    font-weight: 600;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--muted-foreground);
    cursor: pointer;
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.tab-btn:hover { color: var(--foreground); }
.tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
.tab-count {
    font-size: var(--text-xs);
    background: var(--muted);
    color: var(--muted-foreground);
    padding: 0.1rem 0.5rem;
    border-radius: 9999px;
    font-weight: 700;
}
.tab-btn.active .tab-count { background: var(--primary); color: var(--primary-foreground); }
.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.toolbar-left { display: flex; gap: 0.75rem; flex: 1; flex-wrap: wrap; }
.toolbar-right { font-size: var(--text-sm); color: var(--muted-foreground); white-space: nowrap; }
.search-wrap {
    position: relative;
    flex: 1;
    max-width: 300px;
}
.search-wrap svg {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted-foreground);
    pointer-events: none;
}
.search-wrap input { padding-left: 2.25rem; }
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--muted-foreground);
}
.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 0.75rem;
}
.toolbar-right { display: flex; align-items: center; gap: 0.75rem; }
.grade-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.grade-modal {
    background: var(--card);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 440px;
    box-shadow: var(--shadow-xl);
    overflow: hidden;
}
.grade-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
}
.grade-modal-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.grade-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border);
}
.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: var(--text-xs);
    font-weight: 600;
}
.type-quiz { background: color-mix(in srgb, var(--primary) 10%, transparent); color: var(--primary); }
.type-assignment { background: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); }
</style>
@endpush

@section('content')
<?php
    $pendingList = collect($pendingGrades)->filter(fn($g) => !$g->is_graded)->values();
    $gradedList = collect($pendingGrades)->filter(fn($g) => $g->is_graded)->values();
    $pendingCount = $pendingList->count();
    $gradedCount = $gradedList->count();
    $allScores = collect($pendingGrades)->filter(fn($g) => $g->score !== null)->pluck('score');
    $avgScore = $allScores->count() > 0 ? round($allScores->avg(), 1) : null;
    $todayGraded = collect($pendingGrades)->filter(fn($g) =>
        $g->is_graded && $g->submitted_at && \Carbon\Carbon::parse($g->submitted_at)->isToday()
    )->count();

    $colors = ['#3b82f6', '#ef4444', '#22c55e', '#f97316', '#a855f7', '#06b6d4', '#ec4899', '#eab308'];
?>

<!-- Page Header -->
<div class="page-header">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1>Chấm điểm</h1>
            <p style="color:var(--muted-foreground);">Chấm điểm bài kiểm tra và bài tập của học sinh</p>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <a href="{{ route('teacher.grading.export') }}" class="btn btn-outline btn-sm gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Xuất CSV
            </a>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid stats-grid-4 stagger-children" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Chờ chấm</div>
        <div class="stat-card__value" style="color:var(--warning);">{{ $pendingCount }}</div>
        <div class="stat-card__trend">Bài nộp</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Đã chấm hôm nay</div>
        <div class="stat-card__value" style="color:var(--success);">{{ $todayGraded }}</div>
        <div class="stat-card__trend">Hôm nay</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Điểm TB chung</div>
        <div class="stat-card__value">{{ $avgScore ?? '—' }}</div>
        <div class="stat-card__trend">{{ $gradedCount }} bài đã chấm</div>
    </div>
    <div class="stat-card">
        <div style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:0.5rem;">Tổng đã chấm</div>
        <div class="stat-card__value">{{ $gradedCount }}</div>
        <div class="stat-card__trend">Tổng cộng</div>
    </div>
</div>

<!-- Tabs -->
<div class="tab-bar stagger-children">
    <button class="tab-btn active" data-tab="pending" id="tab-pending">
        Chờ chấm
        <span class="tab-count" id="tc-pending">{{ $pendingCount }}</span>
    </button>
    <button class="tab-btn" data-tab="graded" id="tab-graded">
        Đã chấm
        <span class="tab-count" id="tc-graded">{{ $gradedCount }}</span>
    </button>
</div>

<!-- Toolbar -->
<div class="toolbar stagger-children">
    <div class="toolbar-left">
        <div class="search-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" class="input" placeholder="Tìm học sinh..." id="search-input" style="font-size:var(--text-sm);" />
        </div>
    </div>
    <div class="toolbar-right">
        <span id="item-count">{{ $pendingCount }} bài nộp</span>
    </div>
</div>

<!-- List -->
<div id="grade-list" class="stagger-children"></div>

<!-- Grading Modal -->
<div id="grade-modal-overlay" style="display:none;">
    <div class="grade-modal" id="grade-modal">
        <div class="grade-modal-header">
            <div>
                <h3 style="font-size:var(--text-lg);font-weight:700;margin-bottom:0.125rem;" id="gm-title">Chấm điểm</h3>
                <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin:0;" id="gm-sub"></p>
            </div>
            <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;padding:0.25rem;color:var(--muted-foreground);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="grade-modal-body">
            <input type="hidden" id="gm-gradable-type" />
            <input type="hidden" id="gm-gradable-id" />
            <input type="hidden" id="gm-student-id" />
            <input type="hidden" id="gm-max-score" />

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="label label-required" for="gm-score">Điểm</label>
                    <input type="number" id="gm-score" class="grade-input"
                        min="0" step="0.1" placeholder="0.0" />
                </div>
                <div class="form-group">
                    <label class="label">Điểm tối đa</label>
                    <input type="text" id="gm-max-display" class="input" readonly
                        style="background:var(--muted);font-weight:600;" />
                </div>
            </div>

            <div class="form-group">
                <label class="label" for="gm-feedback">Nhận xét <span style="color:var(--muted-foreground);font-weight:400;">(tùy chọn)</span></label>
                <textarea id="gm-feedback" class="input" style="min-height:4rem;"
                    placeholder="Nhận xét cho học sinh..."></textarea>
            </div>

            <div id="gm-error" style="color:var(--destructive);font-size:var(--text-sm);display:none;"></div>
        </div>
        <div class="grade-modal-footer">
            <button class="btn btn-outline" onclick="closeModal()">Hủy</button>
            <button class="btn btn-primary" id="gm-save-btn" onclick="saveGrade()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Lưu điểm
            </button>
        </div>
    </div>
</div>

<div id="toast-container"></div>
@endsection

@push('scripts')
<script>
// ─────────────────────────────────────────────
// Grading JavaScript — real data from server
// ─────────────────────────────────────────────
(function() {
    'use strict';

    // ── Real data from server ─────────────────
    const PENDING_GRADES = @json($pendingGrades);

    const colors = ['#3b82f6','#ef4444','#22c55e','#f97316','#a855f7','#06b6d4','#ec4899','#eab308'];

    // ── State ────────────────────────────────
    let currentTab = 'pending';
    let searchQuery = '';

    // ── Helpers ─────────────────────────────
    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    function getInitials(name) {
        return name.split(' ').filter(Boolean)
            .map(w => w[0])
            .slice(-2)
            .join('')
            .toUpperCase();
    }

    function scoreColor(score, max) {
        const pct = max > 0 ? (score / max) * 100 : 0;
        if (pct >= 80) return 'var(--success)';
        if (pct >= 60) return 'var(--warning)';
        return 'var(--destructive)';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function showToast(message, type = 'success') {
        const tc = $('#toast-container');
        if (!tc) return;
        const e = document.createElement('div');
        e.className = `toast toast-${type === 'error' ? 'error' : 'success'}`;
        e.innerHTML = `<span>${type === 'error' ? '❌' : '✅'}</span><span>${message}</span>`;
        tc.appendChild(e);
        requestAnimationFrame(() => e.classList.add('show'));
        setTimeout(() => {
            e.classList.remove('show');
            setTimeout(() => e.remove(), 300);
        }, 3000);
    }

    // ── Filter ──────────────────────────────
    function getFiltered() {
        let list = currentTab === 'pending'
            ? PENDING_GRADES.filter(g => !g.is_graded)
            : PENDING_GRADES.filter(g => g.is_graded);

        if (searchQuery) {
            const q = searchQuery.toLowerCase();
            list = list.filter(g =>
                g.student_name.toLowerCase().includes(q) ||
                g.item_title.toLowerCase().includes(q)
            );
        }

        return list;
    }

    // ── Group by item ────────────────────────
    function groupByItem(list) {
        const groups = {};
        list.forEach(g => {
            const key = g.item_title + '||' + g.type;
            if (!groups[key]) groups[key] = [];
            groups[key].push(g);
        });
        return groups;
    }

    // ── Render ──────────────────────────────
    function renderList() {
        const list = getFiltered();
        const container = $('#grade-list');
        const countEl = $('#item-count');

        countEl.textContent = `${list.length} bài nộp`;

        if (list.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">${currentTab === 'pending' ? '📋' : '✅'}</div>
                    <h3 style="font-size:var(--text-xl);font-weight:600;color:var(--foreground);margin-bottom:0.25rem;">
                        ${currentTab === 'pending' ? 'Không có bài chờ chấm' : 'Chưa có bài nào được chấm'}
                    </h3>
                    <p>${searchQuery ? 'Thử thay đổi từ khóa tìm kiếm' : 'Tất cả bài đã được chấm hết rồi!'}</p>
                </div>`;
            return;
        }

        const groups = groupByItem(list);
        let html = '';

        Object.keys(groups).forEach(key => {
            const [title, type] = key.split('||');
            const items = groups[key];
            const pendingInGroup = items.filter(i => !i.is_graded).length;
            const typeClass = type === 'quiz' ? 'type-quiz' : 'type-assignment';
            const typeIcon = type === 'quiz' ? '📝' : '📄';

            html += `
            <div class="grade-card" style="margin-bottom:1rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <h3 style="font-weight:700;font-size:var(--text-base);">${title}</h3>
                        <span class="type-badge ${typeClass}">${typeIcon} ${type === 'quiz' ? 'Quiz' : 'Bài tập'}</span>
                    </div>
                    <span style="font-size:var(--text-sm);color:var(--muted-foreground);">
                        ${items.length} bài nộp
                        ${pendingInGroup > 0 ? ` · <strong style="color:var(--warning);">${pendingInGroup} chờ chấm</strong>` : ''}
                    </span>
                </div>
                <div style="border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;">
            `;

            items.forEach((g, idx) => {
                const ci = colors[idx % colors.length];
                const initials = getInitials(g.student_name);
                const maxScore = g.max_score || 10;
                const hasScore = g.score !== null && g.score !== undefined;
                const scoreHTML = hasScore
                    ? `<span style="font-weight:800;font-size:var(--text-base);color:${scoreColor(g.score, maxScore)};">${g.score}</span><span style="font-size:var(--text-sm);color:var(--muted-foreground);">/${maxScore}</span>`
                    : `<span class="badge badge-warning" style="font-size:var(--text-xs);">Chờ chấm</span>`;

                const btnHTML = hasScore
                    ? `<button class="btn btn-ghost btn-sm" onclick="openGradeModal('${g.type}', '${g.gradable_id}', ${g.student_id}, '${g.student_name}', '${title}', ${maxScore}, ${g.score}, '${g.type}')">Sửa</button>`
                    : `<button class="btn btn-primary btn-sm" onclick="openGradeModal('${g.type}', '${g.gradable_id}', ${g.student_id}, '${g.student_name}', '${title}', ${maxScore}, null, '${g.type}')">Chấm điểm</button>`;

                html += `
                    <div class="student-row">
                        <div class="avatar-sm" style="background:color-mix(in srgb, ${ci} 15%, transparent);color:${ci};">${initials}</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;font-size:var(--text-sm);">${g.student_name}</div>
                            <div style="font-size:var(--text-xs);color:var(--muted-foreground);">Nộp: ${formatDate(g.submitted_at)}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;min-width:7rem;justify-content:flex-end;">
                            <div style="display:flex;align-items:baseline;gap:0.25rem;">${scoreHTML}</div>
                            ${btnHTML}
                        </div>
                    </div>`;
            });

            html += `</div></div>`;
        });

        container.innerHTML = html;
    }

    // ── Tab switching ────────────────────────
    $$('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            $$('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTab = btn.getAttribute('data-tab');
            renderList();
        });
    });

    // ── Search ───────────────────────────────
    $('#search-input').addEventListener('input', (e) => {
        searchQuery = e.target.value.trim();
        renderList();
    });

    // ── Modal ───────────────────────────────
    window.openGradeModal = function(type, gradableId, studentId, studentName, itemTitle, maxScore, currentScore, submissionType) {
        const overlay = $('#grade-modal-overlay');
        overlay.style.display = 'flex';

        $('#gm-gradable-type').value = submissionType;
        $('#gm-gradable-id').value = gradableId;
        $('#gm-student-id').value = studentId;
        $('#gm-max-score').value = maxScore;

        $('#gm-title').textContent = `Chấm điểm — ${studentName}`;
        $('#gm-sub').textContent = `${itemTitle}`;
        $('#gm-max-display').value = `${maxScore} điểm`;
        $('#gm-score').value = currentScore !== null ? currentScore : '';
        $('#gm-feedback').value = '';
        $('#gm-error').style.display = 'none';

        setTimeout(() => $('#gm-score').focus(), 100);
    };

    window.closeModal = function() {
        $('#grade-modal-overlay').style.display = 'none';
    };

    // Close on overlay click
    $('#grade-modal-overlay').addEventListener('click', (e) => {
        if (e.target === $('#grade-modal-overlay')) closeModal();
    });

    // Close on Escape
    document.onkeydown = (e) => {
        if (e.key === 'Escape') closeModal();
    };

    // ── Save Grade ──────────────────────────
    window.saveGrade = async function() {
        const score = parseFloat($('#gm-score').value);
        const maxScore = parseFloat($('#gm-max-score').value);
        const feedback = $('#gm-feedback').value.trim();

        if (isNaN(score) || score < 0) {
            $('#gm-error').textContent = 'Vui lòng nhập điểm hợp lệ (>= 0)!';
            $('#gm-error').style.display = 'block';
            return;
        }
        if (score > maxScore) {
            $('#gm-error').textContent = `Điểm không được lớn hơn ${maxScore}!`;
            $('#gm-error').style.display = 'block';
            return;
        }

        $('#gm-error').style.display = 'none';
        const saveBtn = $('#gm-save-btn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span>Đang lưu...</span>';

        try {
            const formData = new FormData();
            formData.append('gradable_type', $('#gm-gradable-type').value);
            formData.append('gradable_id', $('#gm-gradable-id').value);
            formData.append('student_id', $('#gm-student-id').value);
            formData.append('score', score);
            formData.append('feedback', feedback);

            const response = await fetch('/teacher/grading', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                // Update local data
                const gradableId = $('#gm-gradable-id').value;
                const studentId = parseInt($('#gm-student-id').value);
                const idx = PENDING_GRADES.findIndex(g =>
                    g.gradable_id == gradableId && g.student_id == studentId
                );
                if (idx >= 0) {
                    PENDING_GRADES[idx].score = score;
                    PENDING_GRADES[idx].is_graded = true;
                }

                closeModal();
                showToast(`Đã lưu điểm ${score}!`);
                renderList();
                updateTabCounts();
            } else {
                $('#gm-error').textContent = data.message || 'Có lỗi xảy ra!';
                $('#gm-error').style.display = 'block';
            }
        } catch (err) {
            console.error(err);
            $('#gm-error').textContent = 'Không thể kết nối server!';
            $('#gm-error').style.display = 'block';
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Lưu điểm`;
        }
    };

    function updateTabCounts() {
        const pending = PENDING_GRADES.filter(g => !g.is_graded).length;
        const graded = PENDING_GRADES.filter(g => g.is_graded).length;
        $('#tc-pending').textContent = pending;
        $('#tc-graded').textContent = graded;
        $('#tc-pending').parentElement.classList.toggle('active', currentTab === 'pending');
        $('#tc-graded').parentElement.classList.toggle('active', currentTab === 'graded');
    }

    // ── Init ────────────────────────────────
    renderList();
})();
</script>
@endpush
