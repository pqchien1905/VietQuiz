@php
  $isEdit = $mode === 'edit' && $folder;
  $formKey = $isEdit ? 'edit-folder-'.$folder->id : 'create-folder';
  $action = $isEdit ? route('admin.questions.folders.update', $folder->id) : route('admin.questions.folders.store');
@endphp

<div class="modal-overlay" id="{{ $modalId }}">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" style="max-width:34rem;">
    <form method="POST" action="{{ $action }}">
      @csrf
      @if($isEdit)
        @method('PATCH')
      @endif
      <input type="hidden" name="_form" value="{{ $formKey }}">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="{{ $modalId }}-title">{{ $isEdit ? 'Sửa thư mục' : 'Thêm thư mục' }}</h2>
          <p class="modal-desc">Thư mục giúp giáo viên tái sử dụng câu hỏi ngân hàng.</p>
        </div>
        <button class="modal-close" type="button" onclick="closeAdminQuestionModal('{{ $modalId }}')" aria-label="Đóng">×</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="label">Giáo viên</label>
          <select class="input select" name="teacher_id" required @disabled($teachers->isEmpty())>
            <option value="">{{ $teachers->isEmpty() ? 'Chưa có giáo viên' : 'Chọn giáo viên' }}</option>
            @foreach($teachers as $teacher)
              <option value="{{ $teacher->id }}" @selected((old('_form') === $formKey ? old('teacher_id') : ($folder->teacher_id ?? null)) == $teacher->id)>{{ $teacher->name }} - {{ $teacher->email }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" style="margin-top:1rem;">
          <label class="label">Tên thư mục</label>
          <input class="input" name="name" value="{{ old('_form') === $formKey ? old('name') : ($folder->name ?? '') }}" required maxlength="255">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" type="button" onclick="closeAdminQuestionModal('{{ $modalId }}')">Hủy</button>
        <button class="btn btn-primary" @disabled($teachers->isEmpty())>{{ $isEdit ? 'Lưu thay đổi' : 'Tạo thư mục' }}</button>
      </div>
    </form>
  </div>
</div>
