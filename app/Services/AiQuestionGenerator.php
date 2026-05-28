<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiQuestionGenerator
{
    /**
     * @param array{topic:string,type:string,count:int,difficulty:string,grade:?string,extra_context:?string} $input
     * @return array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}>
     */
    public function generate(array $input): array
    {
        $apiKey = config('services.ai_questions.key');
        $apiUrl = config('services.ai_questions.url');
        $model = config('services.ai_questions.model');
        $adapter = config('services.ai_questions.adapter', 'chat_completions');
        $timeout = max((int) config('services.ai_questions.timeout', 45), min(240, 30 + ((int) $input['count'] * 2)));
        $maxTokens = min(16000, max(3000, (int) $input['count'] * 220));

        if ($adapter === 'anthropic_messages') {
            $body = $this->postJsonWithNativeStream($apiUrl, [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => 0.35,
                'system' => $this->systemPrompt(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->anthropicUserContent($input),
                    ],
                ],
            ], $apiKey, $timeout);
            $content = $this->extractAnthropicMessageText($body);
        } else {
            $request = Http::acceptJson()->timeout($timeout);
            if ($apiKey) {
                $request = $request->withToken($apiKey);
            }

            $response = $request->post($apiUrl, [
                'model' => $model,
                'temperature' => 0.35,
                'max_tokens' => $maxTokens,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->chatUserContent($input),
                    ],
                ],
            ]);

            if ($response->failed()) {
                throw new RuntimeException('AI API lỗi: HTTP ' . $response->status());
            }

            $content = data_get($response->json(), 'choices.0.message.content');
        }
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI không trả về nội dung hợp lệ.');
        }

        $decoded = json_decode($this->extractJson($content), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Không đọc được JSON từ AI.');
        }

        $questions = $decoded['questions'] ?? $decoded;
        if (!is_array($questions)) {
            throw new RuntimeException('AI không trả về danh sách câu hỏi.');
        }

        return $this->normalizeQuestions($questions, (int) $input['count']);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Bạn là trợ lý tạo câu hỏi trắc nghiệm tiếng Việt cho giáo viên.
Chỉ trả về JSON hợp lệ, không dùng markdown.
Cấu trúc JSON bắt buộc:
{
  "questions": [
    {
      "type": "multiple_choice" | "true_false" | "short_answer",
      "content": "nội dung câu hỏi bằng tiếng Việt",
      "options": ["A", "B", "C", "D"],
      "correct_answer": "0",
      "explanation": "giải thích ngắn gọn bằng tiếng Việt"
    }
  ]
}
Quy tắc:
- Với "multiple_choice": bắt buộc có đúng 4 lựa chọn trong "options"; "correct_answer" phải là chỉ số dạng chuỗi theo thứ tự từ 0: "0", "1", "2", hoặc "3".
- Với "true_false": "options" phải là []; "correct_answer" chỉ được là "true" hoặc "false".
- Với "short_answer": "options" phải là []; "correct_answer" phải là đáp án ngắn gọn hoặc ý chính để chấm.
- Nội dung phải rõ ràng, phù hợp lứa tuổi học sinh, đúng kiến thức và dùng tiếng Việt tự nhiên.
PROMPT;
    }

    /**
     * @param array{topic:string,type:string,count:int,difficulty:string,grade:?string,extra_context:?string} $input
     */
    private function userPrompt(array $input): string
    {
        $typeLabel = match ($input['type']) {
            'multiple_choice' => 'chỉ câu hỏi trắc nghiệm 4 lựa chọn',
            'true_false' => 'chỉ câu hỏi đúng/sai',
            'short_answer' => 'chỉ câu hỏi tự luận ngắn',
            default => 'kết hợp trắc nghiệm, đúng/sai và tự luận',
        };

        return sprintf(
            "Tạo %d câu hỏi bằng tiếng Việt.\nChủ đề: %s\nLoại câu hỏi: %s\nMức độ: %s\nKhối/lớp: %s\nYêu cầu bổ sung: %s",
            (int) $input['count'],
            $input['topic'],
            $typeLabel,
            $input['difficulty'],
            $input['grade'] ?: 'không chỉ định',
            $input['extra_context'] ?: 'không có'
        );
    }

    /**
     * @param array{topic:string,type:string,count:int,difficulty:string,grade:?string,extra_context:?string,image_data_url?:string} $input
     * @return string|array<int, array<string, mixed>>
     */
    private function chatUserContent(array $input): string|array
    {
        $prompt = $this->userPrompt($input);
        $imageDataUrl = $input['image_data_url'] ?? null;
        if (!is_string($imageDataUrl) || $imageDataUrl === '') {
            return $prompt;
        }

        return [
            ['type' => 'text', 'text' => $prompt],
            ['type' => 'image_url', 'image_url' => ['url' => $imageDataUrl]],
        ];
    }

    /**
     * @param array{topic:string,type:string,count:int,difficulty:string,grade:?string,extra_context:?string,image_data_url?:string} $input
     * @return string|array<int, array<string, mixed>>
     */
    private function anthropicUserContent(array $input): string|array
    {
        $prompt = $this->userPrompt($input);
        $imageDataUrl = $input['image_data_url'] ?? null;
        if (!is_string($imageDataUrl) || $imageDataUrl === '') {
            return $prompt;
        }

        if (!preg_match('/^data:([^;]+);base64,(.+)$/', $imageDataUrl, $matches)) {
            return $prompt;
        }

        return [
            ['type' => 'text', 'text' => $prompt],
            [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $matches[1],
                    'data' => $matches[2],
                ],
            ],
        ];
    }

    private function extractJson(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false && $end > $start) {
            return substr($content, $start, $end - $start + 1);
        }

        return $content;
    }

    /**
     * 9Router's /v1/messages endpoint returns server-sent events. Native streams
     * keep the raw body intact in environments where the Laravel HTTP client
     * may not buffer SSE content.
     *
     * @param array<string, mixed> $payload
     */
    private function postJsonWithNativeStream(string $url, array $payload, ?string $apiKey, int $timeout): string
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: text/event-stream, application/json',
        ];

        if ($apiKey) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'ignore_errors' => true,
                'timeout' => $timeout,
            ],
        ]);

        $body = file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';

        if ($body === false) {
            throw new RuntimeException('Không kết nối được AI API.');
        }

        if (!preg_match('/\s2\d\d\s/', $statusLine)) {
            throw new RuntimeException('AI API lỗi: ' . ($statusLine ?: 'không rõ trạng thái'));
        }

        return $body;
    }

    private function extractAnthropicMessageText(string $body): string
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $blocks = $decoded['content'] ?? [];
            if (is_array($blocks)) {
                return collect($blocks)
                    ->map(fn ($block) => is_array($block) ? ($block['text'] ?? '') : '')
                    ->implode('');
            }
        }

        $text = '';
        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = trim($line);
            if (!str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = trim(substr($line, 5));
            if ($payload === '' || $payload === '[DONE]') {
                continue;
            }

            $event = json_decode($payload, true);
            if (!is_array($event)) {
                continue;
            }

            $deltaText = data_get($event, 'delta.text');
            if (is_string($deltaText)) {
                $text .= $deltaText;
                continue;
            }

            $blockText = data_get($event, 'content_block.text');
            if (is_string($blockText)) {
                $text .= $blockText;
            }
        }

        return $text;
    }

    /**
     * @param array<int, mixed> $questions
     * @return array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}>
     */
    private function normalizeQuestions(array $questions, int $limit): array
    {
        $normalized = [];
        $allowedTypes = ['multiple_choice', 'true_false', 'short_answer'];

        foreach ($questions as $question) {
            if (!is_array($question)) {
                continue;
            }

            $type = (string) ($question['type'] ?? '');
            if (!in_array($type, $allowedTypes, true)) {
                continue;
            }

            $content = trim((string) ($question['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $options = [];
            $correctAnswer = (string) ($question['correct_answer'] ?? '');

            if ($type === 'multiple_choice') {
                $options = array_values(array_map(
                    fn ($option) => trim((string) $option),
                    array_slice((array) ($question['options'] ?? []), 0, 4)
                ));
                $options = array_pad(array_filter($options, fn ($option) => $option !== ''), 4, '');
                if (count(array_filter($options, fn ($option) => $option !== '')) < 2) {
                    continue;
                }
                if (!in_array($correctAnswer, ['0', '1', '2', '3'], true)) {
                    $correctAnswer = '0';
                }
            } elseif ($type === 'true_false') {
                $options = [];
                $correctAnswer = strtolower($correctAnswer) === 'false' ? 'false' : 'true';
            } else {
                $options = [];
                $correctAnswer = trim($correctAnswer);
                if ($correctAnswer === '') {
                    $correctAnswer = 'Câu trả lời cần được giáo viên chấm theo ý chính.';
                }
            }

            $normalized[] = [
                'type' => $type,
                'content' => $content,
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'points' => 1,
                'explanation' => trim((string) ($question['explanation'] ?? '')),
            ];

            if (count($normalized) >= $limit) {
                break;
            }
        }

        if ($normalized === []) {
            throw new RuntimeException('AI chưa tạo được câu hỏi hợp lệ. Hãy thử mô tả chủ đề cụ thể hơn.');
        }

        return $normalized;
    }
}
