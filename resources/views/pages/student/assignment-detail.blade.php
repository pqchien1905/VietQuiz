{{-- Student: assignment-detail --}}
@extends('layouts.dashboard', ['role' => 'student'])

@push('styles')
<style>
.file-drop-zone {
    border: 2px dashed var(--border);
    border-radius: var(--radius-lg);
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition-fast);
    background: var(--muted);
}
.file-drop-zone:hover, .file-drop-zone.drag-over {
    border-color: var(--primary);
    background: color-mix(in srgb, var(--primary) 5%, transparent);
}
.file-drop-zone input[type="file"] {
    display: none;
}
.uploaded-file-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--muted);
    border-radius: var(--radius-md);
    margin-top: 0.75rem;
}
.uploaded-file-item .file-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: color-mix(in srgb, var(--primary) 15%, transparent);
    color: var(--primary);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>
@endpush

@section('content')
  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="{{ route('student.assignments') }}">Bài tập</a>
    <span class="breadcrumb-sep">›</span>
    <span class="active">{{ $assignment->title }}</span>
  </div>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;">
      <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      {{ session('success') }}
    </div>
  @endif

  @php
    $dueDate = $assignment->due_at ? \Carbon\Carbon::parse($assignment->due_at) : null;
    $isPast = $dueDate && $dueDate->isPast();
    $isSubmitted = $submission !== null;
    $isGraded = $grade !== null;
    $timeLeft = $dueDate ? $dueDate->diffForHumans() : null;
    $score = $isGraded && $assignment->total_points > 0
      ? round(($grade->score / $assignment->total_points) * 100) : null;
    $typeIcons = ['essay' => '📝', 'code' => '💻', 'project' => '🚀', 'practice' => '🔬'];
    $typeLabels = ['essay' => 'Tự luận', 'code' => 'Lập trình', 'project' => 'Dự án', 'practice' => 'Thực hành'];
    $typeIcon = $typeIcons[$assignment->type] ?? '📋';
    $typeLabel = $typeLabels[$assignment->type] ?? 'Bài tập';
    $courseName = $assignment->class?->name ?? $assignment->course?->name ?? 'Không rõ lớp';
  @endphp

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
    <!-- Left: Instructions -->
    <div>
      <div class="card">
        <div class="card-header">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div>
              <h2 class="card-title">{{ $assignment->title }}</h2>
              <p class="card-description">
                {{ $typeIcon }} {{ $typeLabel }} · {{ $courseName }}
                @if($assignment->teacher)
                  · GV: {{ $assignment->teacher->name }}
                @endif
              </p>
            </div>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
              @if($isGraded)
                <span class="badge badge-success">Đã chấm</span>
              @elseif($isSubmitted)
                <span class="badge badge-info">Đã nộp</span>
              @elseif($isPast)
                <span class="badge badge-danger">Quá hạn</span>
              @else
                <span class="badge badge-warning">Chưa nộp</span>
              @endif
            </div>
          </div>
        </div>
        <div class="card-content">
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
            <div style="background:var(--muted);padding:0.875rem;border-radius:var(--radius-md);">
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:0.25rem;">Điểm</div>
              @if($isGraded)
                <div style="font-size:var(--text-2xl);font-weight:700;color:var(--success);">{{ $grade->score }}/{{ $assignment->total_points ?? 100 }}</div>
                <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.25rem;">{{ $score }}%</div>
              @else
                <div style="font-size:var(--text-2xl);font-weight:700;color:var(--muted-foreground);">—</div>
              @endif
            </div>
            <div style="background:var(--muted);padding:0.875rem;border-radius:var(--radius-md);">
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:0.25rem;">Hạn nộp</div>
              @if($dueDate)
                <div style="font-size:var(--text-base);font-weight:600;{{ $isPast ? 'color:var(--destructive);' : '' }}">
                  {{ $dueDate->format('d/m/Y H:i') }}
                </div>
                @if(!$isPast && $timeLeft)
                  <div style="font-size:var(--text-xs);color:var(--warning);margin-top:0.25rem;">{{ $timeLeft }}</div>
                @elseif($isPast)
                  <div style="font-size:var(--text-xs);color:var(--destructive);margin-top:0.25rem;">Đã quá hạn</div>
                @endif
              @else
                <div style="font-size:var(--text-base);font-weight:600;color:var(--muted-foreground);">Không giới hạn</div>
              @endif
            </div>
            <div style="background:var(--muted);padding:0.875rem;border-radius:var(--radius-md);">
              <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:0.25rem;">Nộp lúc</div>
              @if($submission?->submitted_at)
                <div style="font-size:var(--text-base);font-weight:600;">{{ \Carbon\Carbon::parse($submission->submitted_at)->format('d/m/Y H:i') }}</div>
              @else
                <div style="font-size:var(--text-base);font-weight:600;color:var(--muted-foreground);">—</div>
              @endif
            </div>
          </div>

          <h3 style="font-size:var(--text-lg);font-weight:600;margin-bottom:0.75rem;">Mô tả bài tập</h3>
          @if($assignment->description)
            <p style="font-size:var(--text-sm);line-height:1.7;color:var(--muted-foreground);margin-bottom:1.5rem;">
              {{ $assignment->description }}
            </p>
          @else
            <p style="font-size:var(--text-sm);color:var(--muted-foreground);margin-bottom:1.5rem;font-style:italic;">
              Không có mô tả
            </p>
          @endif

          @if($assignment->attachment)
            <h3 style="font-size:var(--text-lg);font-weight:600;margin-bottom:0.75rem;">Tài liệu đính kèm</h3>
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.875rem;background:var(--muted);border-radius:var(--radius-md);margin-bottom:1.5rem;">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <div style="flex:1;">
                <div style="font-weight:500;font-size:var(--text-sm);">{{ basename($assignment->attachment) }}</div>
              </div>
              <a href="{{ Storage::url($assignment->attachment) }}" target="_blank" class="btn btn-outline btn-sm">Tải về</a>
            </div>
          @endif

          <!-- Submission Form -->
          @if(!$isPast || $isSubmitted)
            <h3 style="font-size:var(--text-lg);font-weight:600;margin-bottom:0.75rem;">
              {{ $isSubmitted ? 'Cập nhật bài nộp' : 'Nộp bài' }}
            </h3>

            <form method="POST" action="{{ route('student.assignment.submit', $assignment) }}" enctype="multipart/form-data" id="submission-form">
              @csrf

              <!-- Text submission -->
              <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:var(--text-sm);font-weight:500;margin-bottom:0.5rem;">
                  Nội dung trả lời
                  <span style="font-weight:400;color:var(--muted-foreground);">(tùy chọn)</span>
                </label>
                <textarea name="content"
                  class="input"
                  rows="6"
                  placeholder="Nhập nội dung bài làm tại đây..."
                  style="width:100%;resize:vertical;font-family:inherit;"
                >{{ old('content', $submission?->content) }}</textarea>
                @error('content')
                  <p style="color:var(--destructive);font-size:var(--text-xs);margin-top:0.25rem;">{{ $message }}</p>
                @enderror
              </div>

              <!-- File upload -->
              <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:var(--text-sm);font-weight:500;margin-bottom:0.5rem;">
                  Đính kèm file
                  <span style="font-weight:400;color:var(--muted-foreground);">(PDF, DOCX, ZIP, ảnh - tối đa 10MB)</span>
                </label>

                @if($submission?->attachment)
                  <div class="uploaded-file-item">
                    <div class="file-icon">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                      <div style="font-weight:500;font-size:var(--text-sm);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ basename($submission->attachment) }}</div>
                    </div>
                    <a href="{{ Storage::url($submission->attachment) }}" target="_blank" class="btn btn-ghost btn-sm">Xem</a>
                  </div>
                  <p style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:0.5rem;">Tải file mới sẽ thay thế file cũ.</p>
                @endif

                <div class="file-drop-zone" id="file-drop-zone" onclick="document.getElementById('file-input').click()">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--muted-foreground)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 0.5rem;display:block;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  <div style="font-weight:500;margin-bottom:0.25rem;">Kéo thả file vào đây hoặc click để chọn</div>
                  <div style="font-size:var(--text-xs);color:var(--muted-foreground);">PDF, DOCX, ZIP, PNG, JPG (tối đa 10MB)</div>
                  <input type="file" name="attachment" id="file-input" accept=".pdf,.doc,.docx,.zip,.png,.jpg,.jpeg" onchange="handleFileSelect(this)">
                </div>

                <div id="file-preview" style="display:none;margin-top:0.75rem;"></div>
                @error('attachment')
                  <p style="color:var(--destructive);font-size:var(--text-xs);margin-top:0.25rem;">{{ $message }}</p>
                @enderror
              </div>

              <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                  {{ $isSubmitted ? '💾 Cập nhật bài nộp' : '📤 Nộp bài' }}
                </button>
                @if($isSubmitted)
                  <span style="font-size:var(--text-xs);color:var(--muted-foreground);">Bạn có thể nộp lại nhiều lần trước hạn.</span>
                @endif
              </div>
            </form>
          @else
            <div class="alert alert-warning">
              <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              Hạn nộp đã qua. Bạn không thể nộp bài được nữa.
            </div>
          @endif
        </div>

        <!-- Grading feedback -->
        @if($isGraded && $grade?->feedback)
          <div class="card-footer">
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              <span style="font-size:var(--text-sm);font-weight:500;color:var(--success);">
                Đã chấm bởi {{ $grade->grader?->name ?? 'Giáo viên' }}
                @if($grade->graded_at)
                  · {{ \Carbon\Carbon::parse($grade->graded_at)->format('d/m/Y') }}
                @endif
              </span>
            </div>
            @if($grade->feedback)
              <p style="font-size:var(--text-sm);line-height:1.6;margin-top:0.5rem;color:var(--muted-foreground);">
                {{ $grade->feedback }}
              </p>
            @endif
          </div>
        @endif
      </div>
    </div>

    <!-- Right: Info + Actions -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <!-- Quick info -->
      <div class="card">
        <div class="card-header"><h3 class="card-title" style="font-size:var(--text-base);">Thông tin</h3></div>
        <div class="card-content" style="display:flex;flex-direction:column;gap:0.75rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:var(--text-sm);">
            <span style="color:var(--muted-foreground);">Điểm tối đa</span>
            <span style="font-weight:600;">{{ $assignment->total_points ?? 100 }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:var(--text-sm);">
            <span style="color:var(--muted-foreground);">Loại</span>
            <span style="font-weight:600;">{{ $typeIcon }} {{ $typeLabel }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:var(--text-sm);">
            <span style="color:var(--muted-foreground);">Giáo viên</span>
            <span style="font-weight:600;">{{ $assignment->teacher?->name ?? '—' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:var(--text-sm);">
            <span style="color:var(--muted-foreground);">Ngày giao</span>
            <span style="font-weight:600;">{{ $assignment->created_at->format('d/m/Y') }}</span>
          </div>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="card">
        <div class="card-content" style="display:flex;flex-direction:column;gap:0.5rem;">
          <a href="{{ route('student.assignments') }}" class="btn btn-ghost btn-sm w-full" style="justify-content:center;">
            ← Quay lại danh sách
          </a>
        </div>
      </div>
    </div>
  </div>
  <div id="toast-container"></div>
@endsection

@push('scripts')
<script>
  // File drop zone drag-over effect
  const dropZone = document.getElementById('file-drop-zone');
  if (dropZone) {
    dropZone.addEventListener('dragover', function(e) {
      e.preventDefault();
      this.classList.add('drag-over');
    });
    dropZone.addEventListener('dragleave', function(e) {
      e.preventDefault();
      this.classList.remove('drag-over');
    });
    dropZone.addEventListener('drop', function(e) {
      e.preventDefault();
      this.classList.remove('drag-over');
      const input = document.getElementById('file-input');
      if (e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        handleFileSelect(input);
      }
    });
  }

  function handleFileSelect(input) {
    const file = input.files[0];
    const preview = document.getElementById('file-preview');
    if (!file) return;

    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
    const ext = file.name.split('.').pop().toUpperCase();
    const isImage = ['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext.toLowerCase());

    let html = `<div class="uploaded-file-item">
      <div class="file-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:500;font-size:var(--text-sm);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${file.name}</div>
        <div style="font-size:var(--text-xs);color:var(--muted-foreground);">${ext} · ${sizeMB} MB</div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="removeFile()">✕</button>
    </div>`;

    preview.innerHTML = html;
    preview.style.display = 'block';
  }

  function removeFile() {
    const input = document.getElementById('file-input');
    input.value = '';
    document.getElementById('file-preview').style.display = 'none';
  }
</script>
@endpush
