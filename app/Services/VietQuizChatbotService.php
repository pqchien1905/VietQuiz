<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class VietQuizChatbotService
{
    /**
     * @param array<int, array{role?:string, content?:string}> $history
     * @return array{reply:string, actions:array<int, array{label:string, url:string}>, topic:string, source:string}|null
     */
    public function answer(User $user, string $role, string $message, array $history = []): ?array
    {
        if (!config('services.openai.chatbot.enabled') || blank(config('services.openai.key'))) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withToken((string) config('services.openai.key'))
                ->timeout((int) config('services.openai.chatbot.timeout', 20))
                ->post((string) config('services.openai.chatbot.url'), [
                    'model' => (string) config('services.openai.chatbot.model', 'gpt-4.1-mini'),
                    'instructions' => $this->instructions($role),
                    'input' => $this->input($user, $role, $message, $history),
                    'max_output_tokens' => 650,
                    'temperature' => 0.25,
                ]);

            if ($response->failed()) {
                report(new \RuntimeException('VietQuiz chatbot AI HTTP ' . $response->status()));

                return null;
            }

            $content = $this->extractOutputText($response->json());
            if ($content === '') {
                return null;
            }

            $decoded = json_decode($this->extractJson($content), true);
            if (!is_array($decoded)) {
                return null;
            }

            $reply = trim((string) ($decoded['reply'] ?? ''));
            if ($reply === '') {
                return null;
            }

            return [
                'reply' => Str::limit($reply, 1200, '...'),
                'actions' => $this->resolveActions((array) ($decoded['actions'] ?? []), $role),
                'topic' => Str::limit((string) ($decoded['topic'] ?? 'ai'), 40, ''),
                'source' => 'ai',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function instructions(string $role): string
    {
        $roleLabel = $role === 'teacher' ? 'giáo viên' : 'học sinh';
        $actionKeys = implode(', ', array_keys($this->actionMap($role)));

        return <<<PROMPT
Bạn là trợ lý AI của hệ thống VietQuiz dành cho {$roleLabel}.
Nhiệm vụ:
- Trả lời bằng tiếng Việt tự nhiên, ngắn gọn, đúng ngữ cảnh hệ thống quản lý quiz/lớp/khóa học/bài tập.
- Ưu tiên hướng dẫn thao tác trong VietQuiz, không bịa tính năng chưa có.
- Nếu câu hỏi liên quan dữ liệu cá nhân, điểm, lỗi tài khoản hoặc thanh toán mà cần kiểm tra backend, hướng dẫn người dùng gửi ticket.
- Không đưa lời giải trực tiếp cho bài kiểm tra đang làm; chỉ hướng dẫn cách sử dụng hệ thống và cách học trung thực.
- Không tự tạo URL. Chỉ chọn action key trong danh sách whitelist.

Trả về JSON hợp lệ, không markdown:
{
  "topic": "short_topic",
  "reply": "câu trả lời",
  "actions": ["action_key_1", "action_key_2"]
}

Action keys hợp lệ: {$actionKeys}
PROMPT;
    }

    /**
     * @param array<int, array{role?:string, content?:string}> $history
     * @return array<int, array{role:string, content:string}>
     */
    private function input(User $user, string $role, string $message, array $history): array
    {
        $context = [
            'role' => 'user',
            'content' => sprintf(
                "Ngữ cảnh tài khoản:\n- Vai trò hiện tại: %s\n- Tên hiển thị: %s\n- VIP: %s\n- Trang hiện tại: %s\n\nCâu hỏi mới: %s",
                $role === 'teacher' ? 'Giáo viên' : 'Học sinh',
                $user->name,
                $user->isVip() ? 'Có' : 'Không',
                request()->headers->get('referer', 'không rõ'),
                $message
            ),
        ];

        $items = collect($history)
            ->take(-6)
            ->map(function ($item) {
                $role = ($item['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
                $content = trim((string) ($item['content'] ?? ''));

                return $content === ''
                    ? null
                    : ['role' => $role, 'content' => Str::limit($content, 600, '...')];
            })
            ->filter()
            ->values()
            ->all();

        $items[] = $context;

        return $items;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function extractOutputText(array $body): string
    {
        $direct = data_get($body, 'output_text');
        if (is_string($direct) && trim($direct) !== '') {
            return trim($direct);
        }

        $text = '';
        foreach ((array) ($body['output'] ?? []) as $item) {
            foreach ((array) data_get($item, 'content', []) as $content) {
                $part = data_get($content, 'text');
                if (is_string($part)) {
                    $text .= $part;
                }
            }
        }

        return trim($text);
    }

    private function extractJson(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        return $start !== false && $end !== false && $end > $start
            ? substr($content, $start, $end - $start + 1)
            : $content;
    }

    /**
     * @param array<int, mixed> $actionKeys
     * @return array<int, array{label:string, url:string}>
     */
    private function resolveActions(array $actionKeys, string $role): array
    {
        $map = $this->actionMap($role);

        return collect($actionKeys)
            ->filter(fn ($key) => is_string($key) && isset($map[$key]))
            ->unique()
            ->take(3)
            ->map(fn ($key) => $map[$key])
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{label:string, url:string}>
     */
    private function actionMap(string $role): array
    {
        if ($role === 'teacher') {
            return [
                'dashboard' => ['label' => 'Bảng điều khiển', 'url' => route('teacher.dashboard')],
                'classes' => ['label' => 'Lớp của tôi', 'url' => route('teacher.classes')],
                'students' => ['label' => 'Học sinh', 'url' => route('teacher.students')],
                'courses' => ['label' => 'Khóa học', 'url' => route('teacher.courses')],
                'quizzes' => ['label' => 'Bài kiểm tra', 'url' => route('teacher.quizzes')],
                'quiz_create' => ['label' => 'Tạo bài kiểm tra', 'url' => route('teacher.quiz-create')],
                'questions' => ['label' => 'Ngân hàng câu hỏi', 'url' => route('teacher.questions')],
                'assignments' => ['label' => 'Bài tập', 'url' => route('teacher.assignments')],
                'grading' => ['label' => 'Bài tập', 'url' => route('teacher.assignments')],
                'analytics' => ['label' => 'Phân tích', 'url' => route('teacher.analytics')],
                'notifications' => ['label' => 'Thông báo', 'url' => route('teacher.notifications')],
                'settings' => ['label' => 'Cài đặt', 'url' => route('teacher.settings')],
                'profile' => ['label' => 'Hồ sơ', 'url' => route('teacher.profile')],
                'vip' => ['label' => 'Trang VIP', 'url' => route('teacher.vip')],
                'help' => ['label' => 'Gửi ticket', 'url' => route('teacher.help')],
            ];
        }

        return [
            'dashboard' => ['label' => 'Bảng điều khiển', 'url' => route('student.dashboard')],
            'classes' => ['label' => 'Lớp học', 'url' => route('student.classes')],
            'join_class' => ['label' => 'Tham gia lớp', 'url' => route('student.join-class')],
            'courses' => ['label' => 'Khóa học', 'url' => route('student.courses')],
            'quizzes' => ['label' => 'Bài kiểm tra', 'url' => route('student.quizzes')],
            'assignments' => ['label' => 'Bài tập', 'url' => route('student.assignments')],
            'grades' => ['label' => 'Điểm số', 'url' => route('student.grades')],
            'notifications' => ['label' => 'Thông báo', 'url' => route('student.notifications')],
            'settings' => ['label' => 'Cài đặt', 'url' => route('student.settings')],
            'profile' => ['label' => 'Hồ sơ', 'url' => route('student.profile')],
            'vip' => ['label' => 'Trang VIP', 'url' => route('student.vip')],
            'help' => ['label' => 'Gửi ticket', 'url' => route('student.help')],
        ];
    }
}
