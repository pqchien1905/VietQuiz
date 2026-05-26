{{-- Email: Grade Published --}}
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diem bai lam da duoc cong bo</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  @php
      $label = ($itemType ?? 'quiz') === 'assignment' ? 'bai tap' : 'bai kiem tra';
  @endphp
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:2rem 1rem;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
          <tr>
            <td style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:2rem;text-align:center;">
              <h1 style="color:#ffffff;font-size:1.5rem;font-weight:700;margin:0;">Diem bai lam</h1>
              <p style="color:rgba(255,255,255,0.85);margin:0.5rem 0 0;font-size:0.875rem;">VietQuiz</p>
            </td>
          </tr>
          <tr>
            <td style="padding:2rem;">
              <p style="margin:0 0 1rem;font-size:1rem;color:#1f2937;">
                Xin chao <strong>{{ $studentName }}</strong>,
              </p>
              <p style="margin:0 0 1.5rem;font-size:1rem;color:#374151;line-height:1.6;">
                Ket qua {{ $label }} <strong>{{ $quizTitle }}</strong> da duoc cong bo.
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f9ff;border-radius:12px;border:1px solid #e0f2fe;margin-bottom:1.5rem;">
                <tr>
                  <td style="padding:1.5rem;text-align:center;">
                    <div style="font-size:3rem;font-weight:800;color:#1d4ed8;">{{ $pct }}%</div>
                    <div style="font-size:1rem;color:#374151;margin-top:0.25rem;">
                      {{ $score }} / {{ $totalPoints }} diem
                    </div>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 1.5rem;font-size:0.875rem;color:#6b7280;">
                Hay dang nhap VietQuiz de xem chi tiet ket qua.
              </p>
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ config('app.url') }}/student/grades" style="display:inline-block;background:#6366f1;color:#ffffff;text-decoration:none;font-weight:600;padding:0.75rem 2rem;border-radius:8px;font-size:1rem;">
                      Xem ket qua
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:1.5rem;text-align:center;">
              <p style="margin:0;font-size:0.75rem;color:#9ca3af;">
                Email này được gửi tự động từ VietQuiz. Vui lòng không reply email này.
              </p>
              <p style="margin:0.5rem 0 0;font-size:0.75rem;color:#9ca3af;">
                Copyright {{ date('Y') }} VietQuiz
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
