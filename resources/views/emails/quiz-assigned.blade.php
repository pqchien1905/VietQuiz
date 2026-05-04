{{-- Email: Quiz Assigned --}}
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bài kiểm tra mới được giao</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:2rem 1rem;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#059669,#10b981);padding:2rem;text-align:center;">
              <div style="font-size:2.5rem;margin-bottom:0.5rem;">📝</div>
              <h1 style="color:#ffffff;font-size:1.5rem;font-weight:700;margin:0;">Bài kiểm tra mới</h1>
              <p style="color:rgba(255,255,255,0.85);margin:0.5rem 0 0;font-size:0.875rem;">VietQuiz</p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:2rem;">
              <p style="margin:0 0 1rem;font-size:1rem;color:#1f2937;">
                Xin chào <strong>{{ $studentName }}</strong>,
              </p>
              <p style="margin:0 0 1.5rem;font-size:1rem;color:#374151;line-height:1.6;">
                Giáo viên vừa giao một bài kiểm tra mới cho bạn.
              </p>

              <!-- Quiz info -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.5rem;">
                <tr>
                  <td style="padding:1.5rem;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:0.5rem 0;font-size:0.875rem;color:#6b7280;">Bài kiểm tra</td>
                        <td style="padding:0.5rem 0;font-size:0.875rem;font-weight:600;color:#1f2937;text-align:right;">{{ $quizTitle }}</td>
                      </tr>
                      <tr>
                        <td style="padding:0.5rem 0;font-size:0.875rem;color:#6b7280;border-top:1px solid #e5e7eb;">Lớp</td>
                        <td style="padding:0.5rem 0;font-size:0.875rem;font-weight:600;color:#1f2937;text-align:right;border-top:1px solid #e5e7eb;">{{ $className }}</td>
                      </tr>
                      @if($dueDate)
                      <tr>
                        <td style="padding:0.5rem 0;font-size:0.875rem;color:#6b7280;border-top:1px solid #e5e7eb;">Hạn nộp</td>
                        <td style="padding:0.5rem 0;font-size:0.875rem;font-weight:600;color:#dc2626;text-align:right;border-top:1px solid #e5e7eb;">{{ $dueDate }}</td>
                      </tr>
                      @endif
                    </table>
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ $quizUrl }}" style="display:inline-block;background:#059669;color:#ffffff;text-decoration:none;font-weight:600;padding:0.75rem 2rem;border-radius:8px;font-size:1rem;">
                      Làm bài ngay →
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:1.5rem;text-align:center;">
              <p style="margin:0;font-size:0.75rem;color:#9ca3af;">
                Email này được gửi tự động từ VietQuiz. Vui lòng không reply email này.
              </p>
              <p style="margin:0.5rem 0 0;font-size:0.75rem;color:#9ca3af;">
                © {{ date('Y') }} VietQuiz — Nền tảng kiểm tra trực tuyến
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
