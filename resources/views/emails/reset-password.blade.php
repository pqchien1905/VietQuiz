<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đặt lại mật khẩu VietQuiz</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#172033;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 32px rgba(15,23,42,0.10);">
          <tr>
            <td style="background:#0f766e;padding:32px 28px;text-align:center;">
              <div style="width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,0.16);margin:0 auto 14px;line-height:56px;font-size:28px;color:#ffffff;font-weight:700;">VQ</div>
              <h1 style="margin:0;color:#ffffff;font-size:24px;line-height:1.3;font-weight:800;">Đặt lại mật khẩu</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.86);font-size:14px;line-height:1.6;">VietQuiz đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px 28px;">
              <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#172033;">Xin chào,</p>
              <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#465467;">
                Vui lòng nhấn nút bên dưới để tạo mật khẩu mới. Liên kết này chỉ có hiệu lực trong <strong>{{ $expire }} phút</strong>.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:26px 0;">
                <tr>
                  <td align="center">
                    <a href="{{ $resetUrl }}" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;padding:14px 28px;border-radius:10px;">
                      Đặt lại mật khẩu
                    </a>
                  </td>
                </tr>
              </table>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ecfeff;border:1px solid #a5f3fc;border-radius:12px;margin:0 0 22px;">
                <tr>
                  <td style="padding:14px 16px;">
                    <p style="margin:0;color:#155e75;font-size:14px;line-height:1.6;">
                      Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Mật khẩu hiện tại của bạn vẫn được giữ nguyên.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#64748b;">Nếu nút không hoạt động, hãy sao chép và mở liên kết sau trong trình duyệt:</p>
              <p style="margin:0;word-break:break-all;font-size:12px;line-height:1.6;color:#0f766e;">{{ $resetUrl }}</p>
            </td>
          </tr>

          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:20px 28px;text-align:center;">
              <p style="margin:0;font-size:12px;line-height:1.6;color:#94a3b8;">Email này được gửi tự động từ VietQuiz. Vui lòng không trả lời email này.</p>
              <p style="margin:6px 0 0;font-size:12px;line-height:1.6;color:#94a3b8;">© {{ date('Y') }} VietQuiz</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
