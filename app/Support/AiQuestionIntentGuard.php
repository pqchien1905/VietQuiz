<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class AiQuestionIntentGuard
{
    /**
     * @param array{topic?:string,extra_context?:string} $input
     */
    public static function ensureMeaningfulRequest(array $input, bool $hasSourceFile): void
    {
        $topic = trim((string) ($input['topic'] ?? ''));

        // If user provides a source file, allow generation from file content.
        if ($hasSourceFile && $topic === '') {
            return;
        }

        if (self::isLowIntentText($topic)) {
            $hint = self::buildSpecificHint($topic);
            throw ValidationException::withMessages([
                'topic' => [
                    $hint,
                ],
            ]);
        }
    }

    private static function isLowIntentText(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return true;
        }

        if (!preg_match('/\p{L}/u', $text)) {
            return true;
        }

        if (mb_strlen($text) < 8) {
            return true;
        }

        $normalized = mb_strtolower($text);
        $compact = preg_replace('/\s+/u', '', $normalized) ?? $normalized;
        if ($compact !== '' && preg_match('/^(.)\1{4,}$/u', $compact)) {
            return true;
        }

        $tokens = preg_split('/[\s,.;:!?(){}\[\]"\']+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) < 3) {
            return true;
        }

        $noiseTokens = [
            'test', 'demo', 'abc', 'abcd', 'asdf', 'qwer', 'qwerty', '123', '1234',
            'ok', 'oke', 'helo', 'hello', 'hihi', 'haha', 'kkk',
            'linh', 'tinh', 'linhtinh', 'random',
            'khong', 'biet', 'gido', 'gi', 'do',
        ];

        $meaningful = array_values(array_filter($tokens, function (string $token) use ($noiseTokens): bool {
            if (preg_match('/^\d+$/', $token)) {
                return false;
            }
            return !in_array($token, $noiseTokens, true);
        }));

        return count($meaningful) < 2;
    }

    private static function buildSpecificHint(string $rawTopic): string
    {
        $topic = mb_strtolower(trim($rawTopic));
        if ($topic === '') {
            return 'Vui lòng nhập chủ đề cụ thể. Ví dụ: "10 câu trắc nghiệm về thẻ HTML cơ bản cho lớp 10, mức độ dễ".';
        }

        $catalog = [
            'html' => [
                'keywords' => ['html', 'css', 'javascript', 'js', 'dom', 'web', 'frontend', 'thẻ', 'the'],
                'example' => 'Ví dụ phù hợp: "8 câu trắc nghiệm về thẻ HTML heading và paragraph cho lớp 10, mức độ dễ".',
            ],
            'sql' => [
                'keywords' => ['sql', 'mysql', 'postgresql', 'database', 'cơ sở dữ liệu', 'co so du lieu', 'truy vấn', 'truy van', 'select', 'join'],
                'example' => 'Ví dụ phù hợp: "12 câu trắc nghiệm về câu lệnh SELECT, WHERE, ORDER BY trong SQL cho lớp 11".',
            ],
            'python' => [
                'keywords' => ['python', 'biến', 'bien', 'vòng lặp', 'vong lap', 'hàm', 'ham', 'list', 'tuple', 'dict'],
                'example' => 'Ví dụ phù hợp: "12 câu về vòng lặp for/while trong Python lớp 11, gồm 8 trắc nghiệm và 4 đúng/sai".',
            ],
            'cpp' => [
                'keywords' => ['c++', 'cpp', 'c/c++', 'con trỏ', 'con tro', 'mảng', 'mang', 'struct'],
                'example' => 'Ví dụ phù hợp: "10 câu trắc nghiệm về mảng một chiều và vòng lặp trong C++ cho lớp 11".',
            ],
            'java' => [
                'keywords' => ['java', 'oop', 'hướng đối tượng', 'huong doi tuong', 'class', 'object', 'kế thừa', 'ke thua'],
                'example' => 'Ví dụ phù hợp: "10 câu về class, object và kế thừa trong Java, mức độ trung bình".',
            ],
            'scratch' => [
                'keywords' => ['scratch', 'block coding', 'lập trình kéo thả', 'lap trinh keo tha'],
                'example' => 'Ví dụ phù hợp: "8 câu đúng/sai về khối lệnh điều khiển và sự kiện trong Scratch cho lớp 6".',
            ],
            'informatics' => [
                'keywords' => ['tin học 6', 'tin học 7', 'tin học 8', 'tin học 9', 'tin hoc 6', 'tin hoc 7', 'tin hoc 8', 'tin hoc 9', 'thuật toán', 'thuat toan'],
                'example' => 'Ví dụ phù hợp: "10 câu trắc nghiệm về thuật toán và sơ đồ khối cho Tin học 8".',
            ],
            'math' => [
                'keywords' => ['toán', 'toan', 'đại số', 'dai so', 'hình học', 'hinh hoc', 'phương trình', 'phuong trinh', 'logarit'],
                'example' => 'Ví dụ phù hợp: "10 câu trắc nghiệm về phương trình bậc hai lớp 9, mức độ trung bình".',
            ],
            'physics' => [
                'keywords' => ['vật lý', 'vat ly', 'điện', 'dien', 'cơ học', 'co hoc', 'ohm', 'dao động', 'dao dong'],
                'example' => 'Ví dụ phù hợp: "10 câu về định luật Ohm lớp 11, có lời giải ngắn cho từng câu".',
            ],
            'chemistry' => [
                'keywords' => ['hóa', 'hoa', 'hóa học', 'hoa hoc', 'mol', 'phản ứng', 'phan ung', 'axit', 'bazơ', 'bazo'],
                'example' => 'Ví dụ phù hợp: "15 câu trắc nghiệm về axit-bazơ lớp 10, mức độ từ dễ đến trung bình".',
            ],
            'biology' => [
                'keywords' => ['sinh', 'sinh học', 'sinh hoc', 'tế bào', 'te bao', 'di truyền', 'di truyen', 'enzyme'],
                'example' => 'Ví dụ phù hợp: "10 câu đúng/sai về cấu trúc tế bào sinh học lớp 10".',
            ],
            'history' => [
                'keywords' => ['lịch sử', 'lich su', 'khởi nghĩa', 'khoi nghia', 'chiến tranh', 'chien tranh', 'cách mạng', 'cach mang'],
                'example' => 'Ví dụ phù hợp: "10 câu trắc nghiệm về Cách mạng tháng Tám cho lớp 12".',
            ],
            'geography' => [
                'keywords' => ['địa lý', 'dia ly', 'khí hậu', 'khi hau', 'địa hình', 'dia hinh', 'dân số', 'dan so'],
                'example' => 'Ví dụ phù hợp: "12 câu trắc nghiệm về khí hậu Việt Nam lớp 8, mức độ dễ".',
            ],
            'literature' => [
                'keywords' => ['ngữ văn', 'ngu van', 'văn học', 'van hoc', 'thơ', 'tho', 'truyện', 'truyen', 'tác phẩm', 'tac pham'],
                'example' => 'Ví dụ phù hợp: "8 câu tự luận ngắn về tác phẩm Lão Hạc lớp 8, có đáp án ý chính".',
            ],
            'english' => [
                'keywords' => ['english', 'tiếng anh', 'tieng anh', 'grammar', 'vocabulary', 'tense', 'reading'],
                'example' => 'Ví dụ phù hợp: "15 câu trắc nghiệm về thì hiện tại hoàn thành lớp 9, kèm giải thích".',
            ],
            'primary_science' => [
                'keywords' => ['khoa học', 'khoa hoc', 'tự nhiên', 'tu nhien', 'thực vật', 'thuc vat', 'động vật', 'dong vat', 'lớp 4', 'lop 4', 'lớp 5', 'lop 5'],
                'example' => 'Ví dụ phù hợp: "8 câu trắc nghiệm về vòng đời cây xanh cho lớp 4, mức độ dễ".',
            ],
            'civics' => [
                'keywords' => ['gdcd', 'giáo dục công dân', 'giao duc cong dan', 'đạo đức', 'dao duc', 'pháp luật', 'phap luat'],
                'example' => 'Ví dụ phù hợp: "10 câu đúng/sai về quyền và nghĩa vụ công dân cho lớp 9".',
            ],
        ];

        foreach ($catalog as $item) {
            foreach ($item['keywords'] as $keyword) {
                if (str_contains($topic, $keyword)) {
                    return 'Nội dung chưa đủ cụ thể cho chủ đề bạn đang nhập. '
                        . $item['example']
                        . ' Hãy thêm: phạm vi kiến thức, cấp lớp, số lượng câu và mức độ khó.';
                }
            }
        }

        return 'Nội dung chưa đủ rõ để tạo câu hỏi. Hãy nhập theo mẫu: "Chủ đề + phạm vi kiến thức + cấp lớp + số câu + mức độ". '
            . 'Ví dụ: "10 câu trắc nghiệm về thẻ HTML cơ bản cho lớp 10, mức độ dễ".';
    }
}
