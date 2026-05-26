# Fly.io secrets for VietQuiz

Copy, edit the placeholder values, then run these commands from the project root.

```powershell
fly secrets set `
  APP_KEY="base64:CqcOUXVDQtKxk9r0/GsCh9TSTq5+LtVO055sl5sKYHs=" `
  APP_URL="https://vietquiz.fly.dev" `
  DB_CONNECTION="pgsql" `
  DB_HOST="db.vxkgihkvihuyuqnxvukc.supabase.co" `
  DB_PORT="5432" `
  DB_DATABASE="postgres" `
  DB_USERNAME="postgres" `
  DB_PASSWORD="QuangChien#1905" `
  DB_SSLMODE="require" `
  MAIL_MAILER="smtp" `
  MAIL_SCHEME="null" `
  MAIL_HOST="smtp.gmail.com" `
  MAIL_PORT="587" `
  MAIL_USERNAME="phuongdan010@gmail.com" `
  MAIL_PASSWORD="nsnf rfop hmmy powb" `
  MAIL_FROM_ADDRESS="phuongdan010@gmail.com" `
  MAIL_FROM_NAME="VietQuiz" `
  AI_QUESTION_API_KEY="PASTE_AI_KEY" `
  AI_QUESTION_API_URL="https://api.openai.com/v1/chat/completions" `
  AI_QUESTION_MODEL="gpt-4o-mini" `
  AI_QUESTION_API_ADAPTER="chat_completions" `
  AI_QUESTION_TIMEOUT="45" `
  VNPAY_TMN_CODE="K0IW5148" `
  VNPAY_HASH_SECRET="XABSD4DM1KP73OLUE7JFVKVWBBS387EI" `
  VNPAY_PAYMENT_URL="https://sandbox.vnpayment.vn/paymentv2/vpcpay.html" `
  VNPAY_VERSION="2.1.0"
```

Do not use `http://localhost:20128/v1/messages` on Fly.io. On Fly, `localhost`
means the VietQuiz container itself. If you want to keep using a `/v1/messages`
proxy, deploy that proxy to a public HTTPS URL first, then set:

```powershell
fly secrets set `
  AI_QUESTION_API_URL="https://your-ai-proxy.example.com/v1/messages" `
  AI_QUESTION_API_ADAPTER="anthropic_messages"
```


Optional Supabase pooler config, recommended if direct database networking is unstable. Replace the host, port, and username with the exact "Connection pooling" values from Supabase Dashboard:

```powershell
fly secrets set DB_HOST="aws-0-YOUR-REGION.pooler.supabase.com"
fly secrets set DB_PORT="6543"
fly secrets set DB_USERNAME="postgres.vxkgihkvihuyuqnxvukc"
fly secrets set DB_PASSWORD="PASTE_SUPABASE_DATABASE_PASSWORD"
```

Deploy:

```powershell
fly deploy
```

Startup behavior:

- The container always runs `php artisan migrate --force`.
- The container runs `php artisan db:seed --force` only when `AUTO_DB_SEED=true` and the `users` table is empty.
- To force reseeding on the next boot, run `fly secrets set AUTO_DB_SEED_FORCE=true`, deploy or restart once, then set it back to `false`.
