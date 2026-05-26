<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class AssignmentAiGrader
{
    public function __construct(private readonly DocumentTextExtractor $documentTextExtractor)
    {
    }

    /**
     * @return array{score:int,feedback:string,summary:string,warnings:array<int,string>}
     */
    public function grade(Assignment $assignment, Submission $submission, ?string $rubric = null): array
    {
        $maxScore = max(1, (int) ($assignment->total_points ?: 100));
        [$attachmentText, $warnings] = $this->extractSubmissionAttachmentText($submission);
        $prompt = $this->userPrompt($assignment, $submission, $maxScore, $rubric, $attachmentText);

        $apiKey = config('services.ai_questions.key');
        $apiUrl = config('services.ai_questions.url');
        $model = config('services.ai_questions.model');
        $adapter = config('services.ai_questions.adapter', 'chat_completions');
        $timeout = max((int) config('services.ai_questions.timeout', 45), 30);

        if (! is_string($apiUrl) || trim($apiUrl) === '') {
            throw new RuntimeException('Chua cau hinh dia chi AI API.');
        }

        if ($adapter === 'anthropic_messages') {
            $body = $this->postJsonWithNativeStream($apiUrl, [
                'model' => $model,
                'max_tokens' => 1800,
                'temperature' => 0.2,
                'system' => $this->systemPrompt(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ], is_string($apiKey) ? $apiKey : null, $timeout);
            $content = $this->extractAnthropicMessageText($body);
        } else {
            $request = Http::acceptJson()->timeout($timeout);
            if ($apiKey) {
                $request = $request->withToken($apiKey);
            }

            $response = $request->post($apiUrl, [
                'model' => $model,
                'temperature' => 0.2,
                'max_tokens' => 1800,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->failed()) {
                throw new RuntimeException('AI API loi: HTTP '.$response->status());
            }

            $content = data_get($response->json(), 'choices.0.message.content');
        }

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI khong tra ve noi dung hop le.');
        }

        $decoded = json_decode($this->extractJson($content), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Không đọc được JSON từ AI.');
        }

        return $this->normalizeSuggestion($decoded, $maxScore, $warnings);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Ban la tro ly cham bai tap cho giao vien VietQuiz.
Chi tra ve JSON hop le, khong dung markdown.
Không tự lưu điểm. Điểm chỉ là gợi ý để giáo viên duyệt.
Cham cong bang dua tren de bai, noi dung hoc sinh nop va tieu chi bo sung neu co.
Neu thong tin bai nop khong du de cham chinh xac, hay cho diem than trong va noi ro ly do trong feedback.
Cau truc JSON bat buoc:
{
  "score": 0,
  "feedback": "Nhan xet ngan gon bang tieng Viet, neu ro diem manh, loi can sua va goi y cai thien.",
  "summary": "Tom tat ly do cham diem trong 1-2 cau."
}
PROMPT;
    }

    private function userPrompt(
        Assignment $assignment,
        Submission $submission,
        int $maxScore,
        ?string $rubric,
        ?string $attachmentText
    ): string {
        $content = trim((string) $submission->content);
        $content = $content !== '' ? $content : '[Học sinh không nhập nội dung văn bản.]';
        $rubric = trim((string) $rubric);

        return trim(sprintf(
            "Hay goi y diem tu 0 den %d.\n\n".
            "DE BAI\n".
            "Tieu de: %s\n".
            "Mo ta: %s\n".
            "Loai bai: %s\n".
            "Diem toi da: %d\n\n".
            "TIEU CHI BO SUNG CUA GIAO VIEN\n%s\n\n".
            "NOI DUNG HOC SINH NOP\n%s\n\n".
            "NOI DUNG TRICH XUAT TU FILE DINH KEM\n%s",
            $maxScore,
            (string) $assignment->title,
            trim((string) $assignment->description) !== '' ? trim((string) $assignment->description) : '[Không có mô tả.]',
            (string) $assignment->type,
            $maxScore,
            $rubric !== '' ? $rubric : '[Không có tiêu chí bổ sung.]',
            mb_substr($content, 0, 9000),
            $attachmentText ?: '[Không có file đính kèm hoặc không trích xuất được nội dung file.]'
        ));
    }

    /**
     * @return array{0:?string,1:array<int,string>}
     */
    private function extractSubmissionAttachmentText(Submission $submission): array
    {
        if (! $submission->attachment || ! Storage::exists($submission->attachment)) {
            return [null, []];
        }

        $path = Storage::path($submission->attachment);
        $extension = strtolower(pathinfo($submission->attachment, PATHINFO_EXTENSION));
        $filename = basename($submission->attachment);

        try {
            $text = match ($extension) {
                'pdf', 'docx' => $this->extractDocumentText($path, $filename),
                'txt', 'md', 'csv' => $this->extractPlainText($path),
                'xls', 'xlsx' => $this->extractSpreadsheetText($path),
                default => null,
            };
        } catch (Throwable $exception) {
            return [null, ['Không trích xuất được nội dung file đính kèm: '.$exception->getMessage()]];
        }

        $text = trim((string) $text);
        if ($text === '') {
            return [null, ['File dinh kem khong co noi dung van ban co the doc tu dong.']];
        }

        return [mb_substr("Ten file: {$filename}\n\n".$text, 0, 12000), []];
    }

    private function extractDocumentText(string $path, string $filename): string
    {
        $file = new UploadedFile(
            $path,
            $filename,
            @mime_content_type($path) ?: null,
            null,
            true
        );

        return $this->documentTextExtractor->extract($file);
    }

    private function extractPlainText(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Không đọc được file văn bản.');
        }

        return trim($content);
    }

    private function extractSpreadsheetText(string $path): string
    {
        $reader = IOFactory::createReaderForFile($path);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $spreadsheet = $reader->load($path);
        $lines = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $lines[] = 'Sheet: '.$sheet->getTitle();

            $highestRow = min((int) $sheet->getHighestDataRow(), 80);
            $highestColumn = min(Coordinate::columnIndexFromString($sheet->getHighestDataColumn()), 20);

            for ($row = 1; $row <= $highestRow; $row++) {
                $values = [];
                for ($column = 1; $column <= $highestColumn; $column++) {
                    $cell = Coordinate::stringFromColumnIndex($column).$row;
                    $values[] = trim((string) $sheet->getCell($cell)->getFormattedValue());
                }

                $values = array_values(array_filter($values, fn ($value) => $value !== ''));

                if ($values !== []) {
                    $lines[] = implode(' | ', $values);
                }

                if (mb_strlen(implode("\n", $lines)) > 11000) {
                    break 2;
                }
            }
        }

        $spreadsheet->disconnectWorksheets();

        return trim(implode("\n", $lines));
    }

    /**
     * @param array<string,mixed> $decoded
     * @param array<int,string> $warnings
     * @return array{score:int,feedback:string,summary:string,warnings:array<int,string>}
     */
    private function normalizeSuggestion(array $decoded, int $maxScore, array $warnings): array
    {
        $rawScore = $decoded['score'] ?? null;
        if (! is_numeric($rawScore)) {
            throw new RuntimeException('AI khong tra ve diem hop le.');
        }

        $score = (int) round((float) $rawScore);
        $score = max(0, min($maxScore, $score));

        $feedback = trim((string) ($decoded['feedback'] ?? ''));
        if ($feedback === '') {
            $feedback = trim((string) ($decoded['summary'] ?? 'AI da tao goi y diem, giao vien can kiem tra lai truoc khi luu.'));
        }

        $summary = trim((string) ($decoded['summary'] ?? ''));

        return [
            'score' => $score,
            'feedback' => mb_substr($feedback, 0, 3000),
            'summary' => mb_substr($summary, 0, 1000),
            'warnings' => $warnings,
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
     * @param array<string, mixed> $payload
     */
    private function postJsonWithNativeStream(string $url, array $payload, ?string $apiKey, int $timeout): string
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: text/event-stream, application/json',
        ];

        if ($apiKey) {
            $headers[] = 'Authorization: Bearer '.$apiKey;
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

        if (! preg_match('/\s2\d\d\s/', $statusLine)) {
            throw new RuntimeException('AI API loi: '.($statusLine ?: 'khong ro trang thai'));
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
            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = trim(substr($line, 5));
            if ($payload === '' || $payload === '[DONE]') {
                continue;
            }

            $event = json_decode($payload, true);
            if (! is_array($event)) {
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
}
