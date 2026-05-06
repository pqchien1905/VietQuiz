@php
  $isEdit = $mode === 'edit' && $quiz;
  $formKey = $isEdit ? 'edit-'.$quiz->id : 'create';
  $action = $isEdit ? route('admin.quizzes.update', $quiz->id) : route('admin.quizzes.store');
  $selectedType = old('_form') === $formKey ? old('quiz_type', 'exam') : ($quiz->quiz_type ?? 'exam');
  $selectedStatus = old('_form') === $formKey ? old('status', 'draft') : ($quiz->status ?? 'draft');
  $dateValue = function ($field) use ($quiz, $formKey) {
      if (old('_form') === $formKey) return old($field);
      return $quiz?->{$field}?->format('Y-m-d\TH:i');
  };
@endphp

<div class="modal-overlay" id="{{ $modalId }}">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" style="max-width:54rem;">
    <form method="POST" action="{{ $action }}">
      @csrf
      @if($isEdit)
        @method('PATCH')
      @endif
      <input type="hidden" name="_form" value="{{ $formKey }}">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="{{ $modalId }}-title">{{ $isEdit ? 'Sửa bài kiểm tra' : 'Thêm bài kiểm tra' }}</h2>
          <p class="modal-desc">{{ $isEdit ? ($quiz->teacher?->name ?? 'Chưa rõ giáo viên') : 'Tạo quiz khung, sau đó quản lý câu hỏi ở ngân hàng câu hỏi.' }}</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminQuizModal('{{ $modalId }}')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        <div class="quiz-modal-grid">
          <div class="form-group full">
            <label class="label">Tiêu đề</label>
            <input class="input" name="title" value="{{ old('_form') === $formKey ? old('title') : ($quiz->title ?? '') }}" required maxlength="255">
          </div>
          <div class="form-group">
            <label class="label">Giáo viên</label>
            <select class="input select" name="teacher_id" required @disabled($teachers->isEmpty())>
              <option value="">{{ $teachers->isEmpty() ? 'Chưa có giáo viên' : 'Chọn giáo viên' }}</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected((old('_form') === $formKey ? old('teacher_id') : ($quiz->teacher_id ?? null)) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>
              @endforeach
            </select>
            @if($teachers->isEmpty())
              <div class="quiz-empty-teacher">
                <span>Cần tạo ít nhất một tài khoản giáo viên trước khi tạo quiz.</span>
                <a class="btn btn-outline btn-sm" href="{{ route('admin.users', ['create' => 'teacher']) }}">Tạo giáo viên</a>
              </div>
            @endif
          </div>
          <div class="form-group">
            <label class="label">Loại bài</label>
            <select class="input select" name="quiz_type">
              @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group"><label class="label">Lớp</label><select class="input select" name="class_id"><option value="">Không gắn lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((old('_form') === $formKey ? old('class_id') : ($quiz->class_id ?? null)) == $class->id)>{{ $class->name }} - {{ $class->code }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Khóa học</label><select class="input select" name="course_id"><option value="">Không gắn khóa</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((old('_form') === $formKey ? old('course_id') : ($quiz->course_id ?? null)) == $course->id)>{{ $course->name }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Thời lượng phút</label><input class="input" name="duration_minutes" type="number" min="1" max="600" value="{{ old('_form') === $formKey ? old('duration_minutes') : ($quiz->duration_minutes ?? 60) }}"></div>
          <div class="form-group"><label class="label">Tổng điểm</label><input class="input" name="total_points" type="number" min="1" max="10000" value="{{ old('_form') === $formKey ? old('total_points') : ($quiz->total_points ?? 100) }}"></div>
          <div class="form-group"><label class="label">Điểm qua</label><input class="input" name="passing_score" type="number" min="0" max="100" value="{{ old('_form') === $formKey ? old('passing_score') : ($quiz->passing_score ?? 50) }}"></div>
          <div class="form-group"><label class="label">Số lượt làm</label><input class="input" name="max_attempts" type="number" min="1" max="1" value="{{ old('_form') === $formKey ? old('max_attempts') : ($quiz->max_attempts ?? 1) }}"></div>
          <div class="form-group"><label class="label">Mở lúc</label><input class="input" name="start_at" type="datetime-local" value="{{ $dateValue('start_at') }}"></div>
          <div class="form-group"><label class="label">Đóng lúc</label><input class="input" name="end_at" type="datetime-local" value="{{ $dateValue('end_at') }}"></div>
          <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="status">@foreach($statusOptions as $status)<option value="{{ $status }}" @selected($selectedStatus === $status)>{{ \App\Support\AdminLabels::status($status) }}</option>@endforeach</select></div>
          <div class="form-group">
            <label class="label">Tùy chọn</label>
            <div class="quiz-checks">
              <label class="quiz-check"><input type="hidden" name="shuffle_questions" value="0"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('_form') === $formKey ? old('shuffle_questions') : ($quiz->shuffle_questions ?? false))> Trộn câu hỏi</label>
              <label class="quiz-check"><input type="hidden" name="shuffle_answers" value="0"><input type="checkbox" name="shuffle_answers" value="1" @checked(old('_form') === $formKey ? old('shuffle_answers') : ($quiz->shuffle_answers ?? false))> Trộn đáp án</label>
              <label class="quiz-check"><input type="hidden" name="show_result" value="0"><input type="checkbox" name="show_result" value="1" @checked(old('_form') === $formKey ? old('show_result', true) : ($quiz->show_result ?? true))> Hiện kết quả</label>
              <label class="quiz-check"><input type="hidden" name="anti_cheat_enabled" value="0"><input type="checkbox" name="anti_cheat_enabled" value="1" @checked(old('_form') === $formKey ? old('anti_cheat_enabled') : ($quiz->anti_cheat_enabled ?? false))> Chống gian lận</label>
              <label class="quiz-check"><input type="hidden" name="public_to_all_students" value="0"><input type="checkbox" name="public_to_all_students" value="1" @checked(old('_form') === $formKey ? old('public_to_all_students') : ($quiz->public_to_all_students ?? false))> Công khai cho tất cả học sinh</label>
            </div>
          </div>
          <div class="form-group full"><label class="label">Mô tả</label><textarea class="input" name="description" rows="3" maxlength="2000">{{ old('_form') === $formKey ? old('description') : ($quiz->description ?? '') }}</textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminQuizModal('{{ $modalId }}')">Hủy</button>
        <button class="btn btn-primary" type="submit" @disabled($teachers->isEmpty())>{{ $isEdit ? 'Lưu thay đổi' : 'Tạo bài kiểm tra' }}</button>
      </div>
    </form>
  </div>
</div>
