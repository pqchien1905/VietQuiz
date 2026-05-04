@extends('layouts.admin')

@section('title', 'Admin - Ngân hàng câu hỏi')
@section('page-title', 'Ngân hàng câu hỏi')
@section('page-description', 'Quản lý câu hỏi độc lập, câu hỏi trong bài kiểm tra, đáp án, điểm và khôi phục câu hỏi đã xóa.')

@php
  $questionTypes = ['multiple_choice', 'true_false', 'short_answer'];
@endphp

@section('content')
<section class="stats-grid stats-grid-4">
  @foreach($summary as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ \App\Support\AdminLabels::summary($label) }}</div><div class="stat-card__value">{{ number_format($value) }}</div></div>
  @endforeach
</section>

<section class="card">
  <div class="card-header"><h3 class="card-title">Tạo câu hỏi</h3></div>
  <div class="card-content">
    <form method="POST" action="{{ route('admin.questions.store') }}" class="admin-form-grid" style="min-width:0;">
      @csrf
      <div class="form-group"><label class="label">Giáo viên</label><select class="input select" name="teacher_id">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }} - {{ $teacher->email }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Bài kiểm tra</label><select class="input select" name="quiz_id"><option value="">Câu hỏi ngân hàng</option>@foreach($quizzes as $quiz)<option value="{{ $quiz->id }}">{{ $quiz->title }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Thư mục</label><select class="input select" name="folder_id"><option value="">Không thư mục</option>@foreach($folders as $folder)<option value="{{ $folder->id }}">{{ $folder->name }} - {{ $folder->teacher?->name }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Loại</label><select class="input select" name="type">@foreach($questionTypes as $type)<option value="{{ $type }}">{{ \App\Support\AdminLabels::questionType($type) }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Môn học</label><input class="input" name="subject"></div>
      <div class="form-group"><label class="label">Điểm</label><input class="input" name="points" type="number" value="1"></div>
      <div class="form-group" style="grid-column:1/-1;"><label class="label">Nội dung</label><textarea class="input" name="content" rows="3" required></textarea></div>
      <div class="form-group" style="grid-column:1/-1;"><label class="label">Lựa chọn</label><div class="admin-form-grid" style="min-width:0;">@for($i=0;$i<4;$i++)<input class="input" name="options[]" placeholder="Lựa chọn {{ $i + 1 }}">@endfor</div></div>
      <div class="form-group"><label class="label">Đáp án đúng</label><input class="input" name="correct_answer" required></div>
      <div class="form-group"><label class="label">Thứ tự</label><input class="input" name="order" type="number" value="0"></div>
      <div class="form-group" style="grid-column:1/-1;"><label class="label">Giải thích</label><textarea class="input" name="explanation" rows="2"></textarea></div>
      <button class="btn btn-primary" style="grid-column:1/-1;">Tạo câu hỏi</button>
    </form>
  </div>
</section>

<section class="card">
  <div class="card-header">
    <form method="GET" class="admin-toolbar">
      <div class="form-group" style="min-width:260px;flex:1;"><label class="label">Tìm kiếm</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Nội dung, môn học"></div>
      <div class="form-group"><label class="label">Loại</label><select class="input select" name="type"><option value="">Tất cả</option>@foreach($questionTypes as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ \App\Support\AdminLabels::questionType($type) }}</option>@endforeach</select></div>
      <div class="form-group"><label class="label">Phạm vi</label><select class="input select" name="scope"><option value="">Tất cả</option><option value="bank" @selected(request('scope') === 'bank')>Ngân hàng</option><option value="quiz" @selected(request('scope') === 'quiz')>Trong bài kiểm tra</option></select></div>
      <div class="form-group"><label class="label">Trạng thái</label><select class="input select" name="state"><option value="">Đang dùng</option><option value="deleted" @selected(request('state') === 'deleted')>Đã xóa</option></select></div>
      <button class="btn btn-primary">Lọc</button><a class="btn btn-outline" href="{{ route('admin.questions') }}">Đặt lại</a>
    </form>
  </div>
  <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Câu hỏi</th><th>Nguồn</th><th>Đáp án</th><th>Sửa nhanh</th><th></th></tr></thead><tbody>
    @forelse($questions as $question)
      <tr style="{{ $question->trashed() ? 'background:color-mix(in srgb,var(--destructive) 8%,transparent);' : '' }}">
        <td><div class="admin-row-title">{{ \Illuminate\Support\Str::limit($question->content, 100) }}</div><div class="admin-row-meta"><span>{{ \App\Support\AdminLabels::questionType($question->type) }}</span><span>{{ $question->subject ?? 'Không môn' }}</span><span>{{ $question->points }} điểm</span></div></td>
        <td><div>{{ $question->teacher?->name ?? 'Không rõ' }}</div><div class="admin-row-meta">{{ $question->quiz?->title ?? $question->folder?->name ?? 'Ngân hàng chung' }}</div></td>
        <td>{{ \Illuminate\Support\Str::limit($question->correct_answer, 80) }}</td>
        <td>
          <form method="POST" action="{{ route('admin.questions.update', $question->id) }}" class="admin-form-grid">
            @csrf @method('PATCH')
            <input type="hidden" name="teacher_id" value="{{ $question->teacher_id }}">
            <input type="hidden" name="quiz_id" value="{{ $question->quiz_id }}">
            <input type="hidden" name="folder_id" value="{{ $question->folder_id }}">
            <input type="hidden" name="subject" value="{{ $question->subject }}">
            <input type="hidden" name="type" value="{{ $question->type }}">
            @foreach(($question->options ?? []) as $option)<input type="hidden" name="options[]" value="{{ $option }}">@endforeach
            <textarea class="input" name="content" rows="2" style="grid-column:1/-1;">{{ $question->content }}</textarea>
            <input class="input" name="correct_answer" value="{{ $question->correct_answer }}">
            <input class="input" name="points" type="number" value="{{ $question->points }}">
            <textarea class="input" name="explanation" rows="2" style="grid-column:1/-1;">{{ $question->explanation }}</textarea>
            <input type="hidden" name="order" value="{{ $question->order }}">
            <button class="btn btn-primary btn-sm" style="grid-column:1/-1;">Lưu</button>
          </form>
        </td>
        <td><div class="admin-table-actions">@if($question->trashed())<form method="POST" action="{{ route('admin.questions.restore', $question->id) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form>@else<form method="POST" action="{{ route('admin.questions.delete', $question->id) }}" onsubmit="return confirm('Xóa câu hỏi này?')">@csrf @method('DELETE')<button class="btn btn-destructive btn-sm">Xóa</button></form>@endif</div></td>
      </tr>
    @empty <tr><td colspan="5" class="empty-state">Không có câu hỏi phù hợp.</td></tr> @endforelse
  </tbody></table></div>
  <div class="card-footer">{{ $questions->links('components.pagination') }}</div>
</section>
@endsection
