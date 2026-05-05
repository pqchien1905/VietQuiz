@php
  $isEdit = $mode === 'edit' && $assignment;
  $formKey = $isEdit ? 'edit-'.$assignment->id : 'create';
  $action = $isEdit ? route('admin.assignments.update', $assignment->id) : route('admin.assignments.store');
  $selectedType = old('_form') === $formKey ? old('type', 'file') : ($assignment->type ?? 'file');
@endphp

<div class="modal-overlay" id="{{ $modalId }}">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" style="max-width:50rem;">
    <form method="POST" action="{{ $action }}">
      @csrf
      @if($isEdit)
        @method('PATCH')
      @endif
      <input type="hidden" name="_form" value="{{ $formKey }}">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="{{ $modalId }}-title">{{ $isEdit ? 'Sửa bài tập' : 'Thêm bài tập' }}</h2>
          <p class="modal-desc">Gắn bài tập với lớp hoặc khóa học để theo dõi bài nộp và chấm điểm.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminAssignmentModal('{{ $modalId }}')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        <div class="assignment-modal-grid">
          <div class="form-group full"><label class="label">Tiêu đề</label><input class="input" name="title" value="{{ old('_form') === $formKey ? old('title') : ($assignment->title ?? '') }}" required maxlength="255"></div>
          <div class="form-group">
            <label class="label">Giáo viên</label>
            <select class="input select" name="teacher_id" required @disabled($teachers->isEmpty())>
              <option value="">{{ $teachers->isEmpty() ? 'Chưa có giáo viên' : 'Chọn giáo viên' }}</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected((old('_form') === $formKey ? old('teacher_id') : ($assignment->teacher_id ?? null)) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>
              @endforeach
            </select>
            @if($teachers->isEmpty())
              <div class="assignment-empty-teacher">
                <span>Cần tạo ít nhất một giáo viên trước khi tạo bài tập.</span>
                <a class="btn btn-outline btn-sm" href="{{ route('admin.users', ['create' => 'teacher']) }}">Tạo giáo viên</a>
              </div>
            @endif
          </div>
          <div class="form-group"><label class="label">Loại nộp</label><select class="input select" name="type">@foreach($typeOptions as $value => $label)<option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Lớp</label><select class="input select" name="class_id"><option value="">Không gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((old('_form') === $formKey ? old('class_id') : ($assignment->class_id ?? null)) == $class->id)>{{ $class->name }} - {{ $class->code }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Không gắn khóa</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((old('_form') === $formKey ? old('course_id') : ($assignment->course_id ?? null)) == $course->id)>{{ $course->name }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Hạn nộp</label><input class="input" name="due_at" type="datetime-local" value="{{ old('_form') === $formKey ? old('due_at') : ($assignment?->due_at?->format('Y-m-d\TH:i')) }}"></div>
          <div class="form-group"><label class="label">Tổng điểm</label><input class="input" name="total_points" type="number" min="1" max="10000" value="{{ old('_form') === $formKey ? old('total_points') : ($assignment->total_points ?? 100) }}"></div>
          <div class="form-group full"><label class="label">Tệp đính kèm/đường dẫn</label><input class="input" name="attachment" value="{{ old('_form') === $formKey ? old('attachment') : ($assignment->attachment ?? '') }}" maxlength="255" placeholder="Đường dẫn tệp nếu đã có"></div>
          <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="4" maxlength="2000">{{ old('_form') === $formKey ? old('description') : ($assignment->description ?? '') }}</textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminAssignmentModal('{{ $modalId }}')">Hủy</button>
        <button class="btn btn-primary" @disabled($teachers->isEmpty())>{{ $isEdit ? 'Lưu thay đổi' : 'Tạo bài tập' }}</button>
      </div>
    </form>
  </div>
</div>
