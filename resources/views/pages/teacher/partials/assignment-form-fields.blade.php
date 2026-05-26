@php
  $assignment = $assignment ?? null;
  $selectedClassId = old('class_id', $assignment?->class_id);
  $selectedCourseId = old('course_id', $assignment?->course_id);
  $selectedType = old('type', $assignment?->type ?? 'file');
  $dueValue = old('due_at', $assignment?->due_at ? $assignment->due_at->format('Y-m-d\TH:i') : '');
  $minDueAt = now()->format('Y-m-d\TH:i');
@endphp

<div class="form-group">
  <label class="label label-required" for="assignment-title-{{ $assignment?->id ?? 'new' }}">Tiêu đề</label>
  <input id="assignment-title-{{ $assignment?->id ?? 'new' }}" class="input" name="title" value="{{ old('title', $assignment?->title) }}" maxlength="255" placeholder="VD: Báo cáo thực hành chương 3" required>
</div>

<div class="form-group">
  <label class="label" for="assignment-description-{{ $assignment?->id ?? 'new' }}">Mô tả / hướng dẫn</label>
  <textarea id="assignment-description-{{ $assignment?->id ?? 'new' }}" class="input" name="description" maxlength="2000" style="min-height:5.5rem;resize:vertical;" placeholder="Yêu cầu, tiêu chí chấm, định dạng nộp bài...">{{ old('description', $assignment?->description) }}</textarea>
</div>

<div class="form-grid-2">
  <div class="form-group">
    <label class="label label-required" for="assignment-class-{{ $assignment?->id ?? 'new' }}">Lớp học</label>
    <select id="assignment-class-{{ $assignment?->id ?? 'new' }}" class="input select" name="class_id" required>
      <option value="">Chọn lớp học</option>
      @foreach($classes as $class)
        <option value="{{ $class->id }}" @selected((string) $selectedClassId === (string) $class->id)>{{ $class->name }}{{ $class->subject ? ' - ' . $class->subject : '' }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group">
    <label class="label" for="assignment-course-{{ $assignment?->id ?? 'new' }}">Khóa học</label>
    <select id="assignment-course-{{ $assignment?->id ?? 'new' }}" class="input select" name="course_id">
      <option value="">Không gắn khóa học</option>
      @foreach($courses as $course)
        <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->name ?? $course->title }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="form-grid-2">
  <div class="form-group">
    <label class="label" for="assignment-due-{{ $assignment?->id ?? 'new' }}">Hạn nộp</label>
    <input id="assignment-due-{{ $assignment?->id ?? 'new' }}" class="input" type="datetime-local" name="due_at" value="{{ $dueValue }}" min="{{ $minDueAt }}">
  </div>
  <div class="form-group">
    <label class="label" for="assignment-points-{{ $assignment?->id ?? 'new' }}">Điểm tối đa</label>
    <input id="assignment-points-{{ $assignment?->id ?? 'new' }}" class="input" type="number" name="total_points" min="1" max="10000" value="{{ old('total_points', $assignment?->total_points ?? 100) }}">
  </div>
</div>

<div class="form-grid-2">
  <div class="form-group">
    <label class="label" for="assignment-type-{{ $assignment?->id ?? 'new' }}">Hình thức nộp</label>
    <select id="assignment-type-{{ $assignment?->id ?? 'new' }}" class="input select" name="type">
      @foreach($typeLabels as $value => $label)
        <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group">
    <label class="label" for="assignment-attachment-{{ $assignment?->id ?? 'new' }}">Tài liệu đính kèm</label>
    <input id="assignment-attachment-{{ $assignment?->id ?? 'new' }}" class="input drop-input" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.png,.jpg,.jpeg,.webp,.txt">
    <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.25rem;">Tối đa 100MB. Hỗ trợ tài liệu, ảnh và file nén.</div>
  </div>
</div>

@if($assignment?->attachment)
  <input type="hidden" name="remove_attachment" id="remove-attachment-{{ $assignment->id }}" value="0">
  <div id="current-attachment-{{ $assignment->id }}" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.55rem .65rem;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--background);font-size:var(--text-sm);">
    <div style="min-width:0;">
      <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-bottom:.15rem;">Tài liệu hiện tại</div>
      <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ basename($assignment->attachment) }}</div>
    </div>
    <button
      type="button"
      class="btn btn-ghost btn-sm"
      style="color:var(--destructive);font-weight:700;padding:.2rem .45rem;line-height:1;"
      onclick="(function(){ document.getElementById('remove-attachment-{{ $assignment->id }}').value='1'; var box=document.getElementById('current-attachment-{{ $assignment->id }}'); if (box) box.style.display='none'; })();"
      aria-label="Xóa tài liệu hiện tại"
      title="Xóa tài liệu hiện tại"
    >×</button>
  </div>
@endif
