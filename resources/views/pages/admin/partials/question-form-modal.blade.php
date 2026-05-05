@php
  $isEdit = $mode === 'edit' && $question;
  $formKey = $isEdit ? 'edit-question-'.$question->id : 'create-question';
  $action = $isEdit ? route('admin.questions.update', $question->id) : route('admin.questions.store');
  $selectedType = old('_form') === $formKey ? old('type', 'multiple_choice') : ($question->type ?? 'multiple_choice');
  $options = old('_form') === $formKey ? old('options', []) : ($question->options ?? []);
  $options = array_pad(array_values($options), 4, '');
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
          <h2 class="modal-title" id="{{ $modalId }}-title">{{ $isEdit ? 'Sửa câu hỏi' : 'Thêm câu hỏi' }}</h2>
          <p class="modal-desc">Câu hỏi có thể nằm trong ngân hàng hoặc gắn trực tiếp vào một quiz.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminQuestionModal('{{ $modalId }}')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        <div class="question-modal-grid">
          <div class="form-group">
            <label class="label">Giáo viên</label>
            <select class="input select" name="teacher_id" required @disabled($teachers->isEmpty())>
              <option value="">{{ $teachers->isEmpty() ? 'Chưa có giáo viên' : 'Chọn giáo viên' }}</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected((old('_form') === $formKey ? old('teacher_id') : ($question->teacher_id ?? null)) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>
              @endforeach
            </select>
            @if($teachers->isEmpty())
              <div class="question-empty-teacher">
                <span>Cần tạo ít nhất một giáo viên trước khi tạo câu hỏi.</span>
                <a class="btn btn-outline btn-sm" href="{{ route('admin.users', ['create' => 'teacher']) }}">Tạo giáo viên</a>
              </div>
            @endif
          </div>
          <div class="form-group"><label class="label">Loại câu hỏi</label><select class="input select" name="type">@foreach($questionTypes as $type)<option value="{{ $type }}" @selected($selectedType === $type)>{{ \App\Support\AdminLabels::questionType($type) }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Quiz</label><select class="input select" name="quiz_id"><option value="">Câu hỏi ngân hàng</option>@foreach($quizzes as $quiz)<option value="{{ $quiz->id }}" @selected((old('_form') === $formKey ? old('quiz_id') : ($question->quiz_id ?? null)) == $quiz->id)>{{ $quiz->title }} - {{ $quiz->teacher?->name }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Thư mục</label><select class="input select" name="folder_id"><option value="">Không thư mục</option>@foreach($folders as $folder)<option value="{{ $folder->id }}" @selected((old('_form') === $formKey ? old('folder_id') : ($question->folder_id ?? null)) == $folder->id)>{{ $folder->name }} - {{ $folder->teacher?->name }}</option>@endforeach</select></div>
          <div class="form-group"><label class="label">Môn học</label><input class="input" name="subject" value="{{ old('_form') === $formKey ? old('subject') : ($question->subject ?? '') }}" maxlength="255"></div>
          <div class="form-group"><label class="label">Điểm</label><input class="input" name="points" type="number" min="1" max="1000" value="{{ old('_form') === $formKey ? old('points') : ($question->points ?? 1) }}"></div>
          <div class="form-group full"><label class="label">Nội dung</label><textarea class="input" name="content" rows="4" required maxlength="5000">{{ old('_form') === $formKey ? old('content') : ($question->content ?? '') }}</textarea></div>
          <div class="form-group full">
            <label class="label">Lựa chọn trắc nghiệm</label>
            <div class="question-options-grid">
              @for($i = 0; $i < 4; $i++)
                <input class="input" name="options[]" value="{{ $options[$i] ?? '' }}" placeholder="Lựa chọn {{ $i + 1 }}">
              @endfor
            </div>
          </div>
          <div class="form-group"><label class="label">Đáp án đúng</label><input class="input" name="correct_answer" value="{{ old('_form') === $formKey ? old('correct_answer') : ($question->correct_answer ?? '') }}" required maxlength="2000"></div>
          <div class="form-group"><label class="label">Thứ tự trong quiz</label><input class="input" name="order" type="number" min="0" max="10000" value="{{ old('_form') === $formKey ? old('order') : ($question->order ?? 0) }}"></div>
          <div class="form-group full"><label class="label">Giải thích</label><textarea class="input" name="explanation" rows="3" maxlength="3000">{{ old('_form') === $formKey ? old('explanation') : ($question->explanation ?? '') }}</textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminQuestionModal('{{ $modalId }}')">Hủy</button>
        <button class="btn btn-primary" @disabled($teachers->isEmpty())>{{ $isEdit ? 'Lưu thay đổi' : 'Tạo câu hỏi' }}</button>
      </div>
    </form>
  </div>
</div>
