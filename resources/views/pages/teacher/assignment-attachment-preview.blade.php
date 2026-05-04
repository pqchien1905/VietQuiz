{{-- Teacher: assignment attachment preview --}}
@extends('layouts.dashboard', ['role' => 'teacher'])

@push('styles')
<style>
.preview-shell{display:grid;grid-template-columns:minmax(0,1fr) 18rem;gap:1rem;align-items:start}
.preview-frame{border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);overflow:hidden;min-height:70vh}
.preview-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:1rem;border-bottom:1px solid var(--border);background:var(--muted)}
.preview-body{padding:1rem}
.preview-iframe{width:100%;height:72vh;border:0;background:#fff}
.preview-image{display:block;max-width:100%;max-height:72vh;margin:0 auto;border-radius:var(--radius-md)}
.preview-text{white-space:pre-wrap;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;font-size:var(--text-sm);line-height:1.7;background:var(--muted);border-radius:var(--radius-md);padding:1rem;overflow:auto;max-height:72vh}
.sheet-wrap{overflow:auto;max-height:72vh;border:1px solid var(--border);border-radius:var(--radius-md)}
.sheet-table{width:100%;border-collapse:collapse;font-size:var(--text-sm)}
.sheet-table td{border:1px solid var(--border);padding:.5rem;vertical-align:top;min-width:8rem}
.preview-side{display:flex;flex-direction:column;gap:1rem}
@media(max-width:980px){.preview-shell{grid-template-columns:1fr}.preview-iframe{height:65vh}}
</style>
@endpush

@section('content')
<nav class="breadcrumb">
  <a href="{{ route('teacher.assignments') }}">Bài tập</a>
  <span class="breadcrumb-sep">›</span>
  <span class="active">Xem tài liệu</span>
</nav>

<div class="page-header">
  <div class="flex items-center justify-between flex-wrap gap-4">
    <div>
      <h1>{{ $assignment->title }}</h1>
      <p style="color:var(--muted-foreground);margin-top:.25rem;">{{ $filename }}</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <a class="btn btn-outline" href="{{ route('teacher.assignments') }}">Quay lại</a>
      <a class="btn btn-primary" href="{{ route('teacher.assignments.attachment.download', $assignment) }}">Tải xuống</a>
    </div>
  </div>
</div>

<div class="preview-shell">
  <section class="preview-frame">
    <div class="preview-toolbar">
      <div>
        <div style="font-weight:800;">Bản xem trước</div>
        <div style="font-size:var(--text-xs);color:var(--muted-foreground);margin-top:.2rem;">{{ strtoupper($extension ?: 'file') }} · {{ $mime }}</div>
      </div>
      @if($convertedPdfAvailable)
        <a class="btn btn-outline btn-sm" href="{{ route('teacher.assignments.attachment.converted', $assignment) }}" target="_blank">Mở PDF</a>
      @elseif(in_array($previewType, ['pdf', 'image', 'text'], true))
        <a class="btn btn-outline btn-sm" href="{{ route('teacher.assignments.attachment.inline', $assignment) }}" target="_blank">Mở tab mới</a>
      @endif
    </div>
    <div class="preview-body">
      @if($convertedPdfAvailable)
        <iframe class="preview-iframe" src="{{ route('teacher.assignments.attachment.converted', $assignment) }}"></iframe>
      @elseif($previewType === 'pdf')
        <iframe class="preview-iframe" src="{{ route('teacher.assignments.attachment.inline', $assignment) }}"></iframe>
      @elseif($previewType === 'image')
        <img class="preview-image" src="{{ route('teacher.assignments.attachment.inline', $assignment) }}" alt="{{ $filename }}">
      @elseif($previewType === 'text')
        <iframe class="preview-iframe" src="{{ route('teacher.assignments.attachment.inline', $assignment) }}"></iframe>
      @elseif(in_array($previewType, ['word', 'presentation'], true))
        @if($previewText)
          <div class="alert alert-warning" style="margin-bottom:1rem;">
            Máy chủ chưa cấu hình LibreOffice nên đây chỉ là bản trích nội dung, không giữ nguyên bố cục file gốc.
          </div>
          <pre class="preview-text">{{ $previewText }}</pre>
        @else
          <div class="empty-state">
            <h3>Chưa thể xem đúng bố cục</h3>
            <p>Để xem Word/PowerPoint giống file gốc, máy chủ cần cài LibreOffice hoặc cấu hình biến LIBREOFFICE_PATH. Hiện tại bạn có thể tải file gốc để mở bằng ứng dụng Office.</p>
            <a class="btn btn-primary" href="{{ route('teacher.assignments.attachment.download', $assignment) }}">Tải file gốc</a>
          </div>
        @endif
      @elseif($previewType === 'spreadsheet')
        @if(count($spreadsheetRows))
          <div class="alert alert-warning" style="margin-bottom:1rem;">
            Máy chủ chưa cấu hình LibreOffice nên đây chỉ là bản xem bảng dữ liệu, không giữ nguyên định dạng Excel.
          </div>
          <div class="sheet-wrap">
            <table class="sheet-table">
              <tbody>
                @foreach($spreadsheetRows as $row)
                  <tr>
                    @foreach($row as $cell)
                      <td>{{ $cell }}</td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="empty-state">
            <h3>Chưa thể xem đúng bảng tính</h3>
            <p>Để xem Excel giống file gốc, máy chủ cần cài LibreOffice hoặc cấu hình biến LIBREOFFICE_PATH. Hiện tại bạn có thể tải file gốc.</p>
            <a class="btn btn-primary" href="{{ route('teacher.assignments.attachment.download', $assignment) }}">Tải file gốc</a>
          </div>
        @endif
      @else
        <div class="empty-state">
          <h3>Chưa hỗ trợ xem trước định dạng này</h3>
          <p>Bạn vẫn có thể tải file gốc để kiểm tra nội dung.</p>
          <a class="btn btn-primary" href="{{ route('teacher.assignments.attachment.download', $assignment) }}">Tải file gốc</a>
        </div>
      @endif
    </div>
  </section>

  <aside class="preview-side">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Thông tin tài liệu</h3></div>
      <div class="card-content" style="display:flex;flex-direction:column;gap:.75rem;font-size:var(--text-sm);">
        <div style="display:flex;justify-content:space-between;gap:1rem;"><span style="color:var(--muted-foreground);">Tên file</span><span style="font-weight:700;text-align:right;word-break:break-word;">{{ $filename }}</span></div>
        <div style="display:flex;justify-content:space-between;gap:1rem;"><span style="color:var(--muted-foreground);">Loại</span><span style="font-weight:700;">{{ strtoupper($extension ?: 'FILE') }}</span></div>
        <div style="display:flex;justify-content:space-between;gap:1rem;"><span style="color:var(--muted-foreground);">Bài tập</span><span style="font-weight:700;text-align:right;">{{ $assignment->title }}</span></div>
        <div style="display:flex;justify-content:space-between;gap:1rem;"><span style="color:var(--muted-foreground);">Hạn nộp</span><span style="font-weight:700;">{{ $assignment->due_at ? $assignment->due_at->format('d/m/Y H:i') : 'Không giới hạn' }}</span></div>
      </div>
    </div>

    <div class="alert alert-info">
      PDF, ảnh và file text được hiển thị trực tiếp. Word, Excel và PowerPoint sẽ giống file gốc khi máy chủ có LibreOffice để chuyển sang PDF.
    </div>
  </aside>
</div>
@endsection
