<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use ZipArchive;

class QuestionFileImporter
{
    public function __construct(private readonly DocumentTextExtractor $textExtractor)
    {
    }

    /**
     * @return array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}>
     */
    public function import(UploadedFile $file, ?int $limit = null): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $questions = match ($extension) {
            'xlsx', 'xls' => $this->importSpreadsheet($file->getRealPath()),
            'docx' => $this->importDocx($file->getRealPath()),
            'pdf' => $this->importText($this->textExtractor->extract($file)),
            'doc' => throw new RuntimeException('File .doc cũ không được hỗ trợ trực tiếp. Vui lòng lưu lại thành .docx hoặc PDF.'),
            default => throw new RuntimeException('Định dạng file không được hỗ trợ. Hãy dùng Excel, PDF hoặc DOCX.'),
        };

        if ($limit !== null) {
            $questions = array_values(array_slice($questions, 0, max(1, $limit)));
        }

        if ($questions === []) {
            throw new RuntimeException('Chưa nhận diện được câu hỏi từ file. Hãy dùng mẫu: mỗi câu gồm nội dung + A/B/C/D, đáp án đúng bôi đỏ hoặc có bảng đáp án.');
        }

        return $questions;
    }

    /**
     * @return array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}>
     */
    private function importSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        if ($rows === []) {
            return [];
        }

        $firstRow = array_shift($rows) ?? [];
        $headers = $this->spreadsheetHeaders($firstRow);
        $hasHeader = in_array('content', $headers, true) || in_array('question', $headers, true);
        if (!$hasHeader) {
            array_unshift($rows, $firstRow);
            $headers = [
                'A' => 'type',
                'B' => 'content',
                'C' => 'option_a',
                'D' => 'option_b',
                'E' => 'option_c',
                'F' => 'option_d',
                'G' => 'correct_answer',
                'H' => 'points',
                'I' => 'explanation',
            ];
        }

        $questions = [];
        foreach ($rows as $row) {
            $data = [];
            foreach ($headers as $column => $key) {
                if ($key !== '') {
                    $data[$key] = trim((string) ($row[$column] ?? ''));
                }
            }

            $content = $data['content'] ?? $data['question'] ?? $data['noi_dung'] ?? '';
            if ($content === '') {
                continue;
            }

            $type = $this->normalizeType($data['type'] ?? $data['loai'] ?? 'multiple_choice');
            $options = $this->spreadsheetOptions($data);
            if ($options === [] && isset($data['options'])) {
                $options = array_values(array_filter(array_map('trim', preg_split('/[|;\n]/', $data['options']) ?: [])));
            }

            if ($type === 'multiple_choice' && count($options) < 2) {
                $type = 'short_answer';
            }

            $correctAnswer = $data['correct_answer'] ?? $data['answer'] ?? $data['dap_an'] ?? $data['correct'] ?? '';
            $correctAnswer = $this->normalizeSpreadsheetAnswer($correctAnswer, $type);

            if ($type === 'multiple_choice') {
                while (count($options) < 4) {
                    $options[] = '';
                }
            } else {
                $options = [];
            }

            $questions[] = [
                'type' => $type,
                'content' => $content,
                'options' => array_slice($options, 0, 6),
                'correct_answer' => $correctAnswer,
                'points' => max(1, (int) ($data['points'] ?? $data['diem'] ?? 1)),
                'explanation' => $data['explanation'] ?? $data['giai_thich'] ?? '',
            ];
        }

        return $questions;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private function spreadsheetHeaders(array $row): array
    {
        $headers = [];
        foreach ($row as $column => $value) {
            $key = mb_strtolower(trim((string) $value), 'UTF-8');
            $key = str_replace([' ', '-', '.'], '_', $key);
            $key = strtr($key, [
                'cau_hoi' => 'content',
                'nội_dung' => 'content',
                'noi_dung' => 'content',
                'loại' => 'type',
                'loai' => 'type',
                'đáp_án' => 'correct_answer',
                'dap_an' => 'correct_answer',
                'điểm' => 'points',
                'diem' => 'points',
                'giải_thích' => 'explanation',
                'giai_thich' => 'explanation',
            ]);
            $headers[$column] = $key;
        }

        return $headers;
    }

    /**
     * @param array<string, string> $data
     * @return array<int, string>
     */
    private function spreadsheetOptions(array $data): array
    {
        $options = [];
        foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $letter) {
            $value = $data['option_' . $letter] ?? $data[$letter] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $options[] = trim($value);
            }
        }

        return $options;
    }

    private function normalizeType(string $type): string
    {
        $normalized = mb_strtolower(trim($type), 'UTF-8');

        return match ($normalized) {
            'true_false', 'true/false', 'đúng/sai', 'dung/sai', 'đúng sai', 'dung sai' => 'true_false',
            'short_answer', 'tự luận', 'tu luan', 'tự_luận', 'tu_luan' => 'short_answer',
            default => 'multiple_choice',
        };
    }

    private function normalizeSpreadsheetAnswer(string $answer, string $type): string
    {
        $answer = trim($answer);
        if ($type === 'multiple_choice' && preg_match('/^[A-F]$/i', $answer)) {
            return (string) $this->answerLetterToIndex($answer);
        }

        if ($type === 'true_false') {
            $boolean = $this->parseBooleanAnswer($answer);
            return $boolean === false ? 'false' : 'true';
        }

        return $answer !== '' ? $answer : ($type === 'multiple_choice' ? '0' : 'Giáo viên chấm theo ý chính.');
    }

    /**
     * @return array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}>
     */
    private function importDocx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Không mở được file Word.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!is_string($xml) || trim($xml) === '') {
            throw new RuntimeException('Không đọc được nội dung file Word.');
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            throw new RuntimeException('File Word không hợp lệ.');
        }

        $xpath = new DOMXPath($dom);
        $body = $xpath->query('//*[local-name()="body"]')->item(0);
        if (!$body instanceof DOMElement) {
            throw new RuntimeException('File Word không có nội dung.');
        }

        $questionParagraphs = [];
        $answerKey = [];
        $explanationText = '';
        $section = 'questions';

        foreach ($body->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $name = $child->localName;
            if ($name === 'p') {
                $text = $this->cleanInlineText($this->nodeText($child));
                if ($text === '') {
                    continue;
                }

                if ($this->isAnswerHeading($text)) {
                    $section = 'answers';
                    continue;
                }
                if ($this->isExplanationHeading($text)) {
                    $section = 'explanations';
                    $remaining = trim((string) preg_replace('/^\s*lời\s*giải\s*[:\-]?\s*/iu', '', $text));
                    if ($remaining !== '') {
                        $explanationText .= "\n" . $remaining;
                    }
                    continue;
                }

                if ($section === 'questions') {
                    $questionParagraphs[] = [
                        'text' => $text,
                        'red' => $this->nodeHasRedText($xpath, $child),
                        'num_id' => $this->paragraphNumId($xpath, $child),
                    ];
                } elseif ($section === 'answers') {
                    $answerKey += $this->parseAnswerKeyText($text);
                } else {
                    $explanationText .= "\n" . $this->nodeText($child);
                }

                continue;
            }

            if ($name === 'tbl') {
                $table = $this->tableRows($xpath, $child);
                if ($section === 'answers' || $this->looksLikeAnswerTable($table)) {
                    $answerKey += $this->parseAnswerTable($table);
                }
            }
        }

        $questions = $this->questionsFromParagraphs($questionParagraphs, $answerKey);
        $this->applyExplanations($questions, $this->parseExplanations($explanationText));

        return $questions;
    }

    /**
     * @return array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}>
     */
    private function importText(string $text): array
    {
        $answerKey = $this->parseAnswerKeyText($text);
        $explanations = $this->parseExplanations($text);

        $questionText = preg_split('/\b(?:Bảng\s+đáp\s+án|Lời\s+giải)\b/iu', $text)[0] ?? $text;
        $lines = array_values(array_filter(array_map(
            fn (string $line): string => $this->cleanInlineText($line),
            preg_split('/\R/', $questionText) ?: []
        ), fn (string $line): bool => $line !== ''));

        $paragraphs = array_map(fn (string $line): array => ['text' => $line, 'red' => false], $lines);
        $questions = $this->questionsFromParagraphs($paragraphs, $answerKey);
        $this->applyExplanations($questions, $explanations);

        return $questions;
    }

    /**
     * @param array<int, array{text:string,red:bool,num_id?:string}> $paragraphs
     * @param array<int, string> $answerKey
     * @return array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}>
     */
    private function questionsFromParagraphs(array $paragraphs, array $answerKey): array
    {
        $questions = [];
        $current = null;
        $questionNumId = $this->detectQuestionNumId($paragraphs);

        foreach ($paragraphs as $paragraph) {
            $text = $paragraph['text'];
            if ($this->isQuestionTypeHeading($text)) {
                continue;
            }

            $isQuestionStart = $this->isQuestionStart($text)
                || ($questionNumId !== null
                    && ($paragraph['num_id'] ?? null) === $questionNumId
                    && (is_array($current) ? ($current['content'] ?? '') !== '' : true));

            if ($isQuestionStart) {
                if (is_array($current)) {
                    $questions[] = $this->finalizeQuestion($current, count($questions) + 1, $answerKey);
                }
                $current = [
                    'content' => $this->stripQuestionNumber($text),
                    'options' => [],
                    'red_options' => [],
                    'answer_lines' => [],
                ];
                continue;
            }

            if (!is_array($current)) {
                $current = ['content' => $text, 'options' => [], 'red_options' => [], 'answer_lines' => []];
                continue;
            }

            $option = $this->stripOptionLabel($text);
            if ($this->looksLikeOption($text, count($current['options']), $questionNumId, $paragraph['num_id'] ?? null)) {
                $index = count($current['options']);
                $current['options'][] = $option;
                if ($paragraph['red']) {
                    $current['red_options'][] = $index;
                }
            } elseif (count($current['options']) === 0 && $this->looksLikeAnswerLine($current['content'], $text)) {
                $current['answer_lines'][] = $text;
            } elseif (count($current['options']) === 0) {
                $current['content'] = trim($current['content'] . ' ' . $text);
            } else {
                $current['options'][count($current['options']) - 1] = trim(end($current['options']) . ' ' . $text);
            }
        }

        if (is_array($current)) {
            $questions[] = $this->finalizeQuestion($current, count($questions) + 1, $answerKey);
        }

        return array_values(array_filter($questions));
    }

    /**
     * @param array{content:string,options:array<int,string>,red_options:array<int,int>,answer_lines?:array<int,string>} $question
     * @param array<int, string> $answerKey
     * @return array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}|null
     */
    private function finalizeQuestion(array $question, int $number, array $answerKey): ?array
    {
        $options = array_values(array_filter(array_map(
            fn (string $option): string => $this->cleanInlineText($option),
            $question['options']
        ), fn (string $option): bool => $option !== ''));

        if (count($options) < 2) {
            $answer = $this->cleanInlineText(implode("\n", $question['answer_lines'] ?? []));
            if ($answer === '') {
                $answer = 'Giáo viên chấm theo ý chính.';
            }

            $booleanAnswer = $this->parseBooleanAnswer($answer);
            if ($booleanAnswer !== null) {
                return [
                    'type' => 'true_false',
                    'content' => $this->cleanInlineText($question['content']),
                    'options' => [],
                    'correct_answer' => $booleanAnswer ? 'true' : 'false',
                    'points' => 1,
                    'explanation' => '',
                ];
            }

            return [
                'type' => 'short_answer',
                'content' => $this->cleanInlineText($question['content']),
                'options' => [],
                'correct_answer' => $answer,
                'points' => 1,
                'explanation' => '',
            ];
        }

        $correct = $question['red_options'][0] ?? null;
        if ($correct === null && isset($answerKey[$number])) {
            $correct = $this->answerLetterToIndex($answerKey[$number]);
        }
        if ($correct === null || $correct < 0 || $correct >= count($options)) {
            $correct = 0;
        }

        while (count($options) < 4) {
            $options[] = '';
        }

        return [
            'type' => 'multiple_choice',
            'content' => $this->cleanInlineText($question['content']),
            'options' => array_slice($options, 0, 6),
            'correct_answer' => (string) $correct,
            'points' => 1,
            'explanation' => '',
        ];
    }

    private function nodeText(DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 't') {
                $text .= $child->textContent;
            } elseif ($child instanceof DOMElement && $child->localName === 'tab') {
                $text .= ' ';
            } elseif ($child instanceof DOMElement && $child->localName === 'br') {
                $text .= "\n";
            } else {
                $text .= $this->nodeText($child);
            }
        }

        return $text;
    }

    private function nodeHasRedText(DOMXPath $xpath, DOMElement $node): bool
    {
        foreach ($xpath->query('.//*[local-name()="color"]', $node) as $color) {
            if (!$color instanceof DOMElement) {
                continue;
            }
            $value = strtoupper($color->getAttribute('w:val') ?: $color->getAttribute('val'));
            if (in_array($value, ['FF0000', 'F00', 'RED'], true)) {
                return true;
            }
        }

        return false;
    }

    private function paragraphNumId(DOMXPath $xpath, DOMElement $paragraph): ?string
    {
        $numId = $xpath->query('./*[local-name()="pPr"]/*[local-name()="numPr"]/*[local-name()="numId"]', $paragraph)->item(0);
        if (!$numId instanceof DOMElement) {
            return null;
        }

        return $numId->getAttribute('w:val') ?: $numId->getAttribute('val') ?: null;
    }

    /**
     * @param array<int, array{text:string,red:bool,num_id?:string}> $paragraphs
     */
    private function detectQuestionNumId(array $paragraphs): ?string
    {
        foreach ($paragraphs as $paragraph) {
            if (($paragraph['num_id'] ?? null) !== null) {
                return $paragraph['num_id'];
            }
        }

        return null;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function tableRows(DOMXPath $xpath, DOMElement $table): array
    {
        $rows = [];
        foreach ($xpath->query('./*[local-name()="tr"]', $table) as $tr) {
            if (!$tr instanceof DOMElement) {
                continue;
            }
            $row = [];
            foreach ($xpath->query('./*[local-name()="tc"]', $tr) as $tc) {
                $row[] = $this->cleanInlineText($this->nodeText($tc));
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function looksLikeAnswerTable(array $rows): bool
    {
        return count($rows) >= 2
            && count(array_filter($rows[0], fn (string $cell): bool => preg_match('/^\d+$/', $cell) === 1)) >= 2
            && count(array_filter($rows[1], fn (string $cell): bool => preg_match('/^[A-F]$/i', $cell) === 1)) >= 2;
    }

    /**
     * @param array<int, array<int, string>> $rows
     * @return array<int, string>
     */
    private function parseAnswerTable(array $rows): array
    {
        $answers = [];
        for ($row = 0; $row + 1 < count($rows); $row += 2) {
            foreach ($rows[$row] as $index => $number) {
                $answer = $rows[$row + 1][$index] ?? '';
                if (preg_match('/^\d+$/', $number) && preg_match('/^[A-F]$/i', $answer)) {
                    $answers[(int) $number] = strtoupper($answer);
                }
            }
        }

        return $answers;
    }

    /**
     * @return array<int, string>
     */
    private function parseAnswerKeyText(string $text): array
    {
        $answers = [];
        if (preg_match_all('/(?:Câu\s*)?(\d+)\s*[:\.\-\)]?\s*([A-F])\b/iu', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $answers[(int) $match[1]] = strtoupper($match[2]);
            }
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
        for ($i = 0; $i + 1 < count($lines); $i++) {
            $numbers = preg_split('/\s+/', $lines[$i]) ?: [];
            $letters = preg_split('/\s+/', $lines[$i + 1]) ?: [];
            if (count($numbers) >= 2 && count($numbers) === count($letters)) {
                foreach ($numbers as $index => $number) {
                    $letter = $letters[$index] ?? '';
                    if (preg_match('/^\d+$/', $number) && preg_match('/^[A-F]$/i', $letter)) {
                        $answers[(int) $number] = strtoupper($letter);
                    }
                }
            }
        }

        return $answers;
    }

    /**
     * @return array<int, string>
     */
    private function parseExplanations(string $text): array
    {
        $explanations = [];
        if (preg_match_all('/Câu\s*(\d+)\s*[:\.\-\)]\s*(.*?)(?=\s*Câu\s*\d+\s*[:\.\-\)]|$)/ius', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $explanation = $this->cleanInlineText($match[2]);
                if ($explanation !== '' && !preg_match('/^[A-F]$/i', $explanation)) {
                    $explanations[(int) $match[1]] = $explanation;
                }
            }
        }

        return $explanations;
    }

    /**
     * @param array<int, array{type:string,content:string,options:array<int,string>,correct_answer:string,points:int,explanation:string}> $questions
     * @param array<int, string> $explanations
     */
    private function applyExplanations(array &$questions, array $explanations): void
    {
        foreach ($questions as $index => &$question) {
            $number = $index + 1;
            if (isset($explanations[$number])) {
                $question['explanation'] = $explanations[$number];
            }
        }
    }

    private function isQuestionStart(string $text): bool
    {
        return preg_match('/^\s*(?:Câu\s*)?\d+\s*[\.\)]\s+/iu', $text) === 1;
    }

    private function isQuestionTypeHeading(string $text): bool
    {
        return preg_match('/^\s*(tự\s*luận|tu\s*luan|đúng\s*\/\s*sai|dung\s*\/\s*sai|đúng\s*sai|dung\s*sai)\s*$/iu', $text) === 1;
    }

    private function stripQuestionNumber(string $text): string
    {
        return $this->cleanInlineText((string) preg_replace('/^\s*(?:Câu\s*)?\d+\s*[\.\)]\s*/iu', '', $text));
    }

    private function looksLikeOption(string $text, int $optionCount, ?string $questionNumId = null, ?string $paragraphNumId = null): bool
    {
        if (preg_match('/^\s*[A-F]\s*[\.\)]\s+/iu', $text) === 1) {
            return true;
        }

        if ($questionNumId !== null && $paragraphNumId !== null) {
            return $paragraphNumId !== $questionNumId && $optionCount < 6;
        }

        return false;
    }

    private function looksLikeAnswerLine(string $content, string $text): bool
    {
        return $this->parseBooleanAnswer($text) !== null
            || preg_match('/[\?:]\s*$/u', trim($content)) === 1;
    }

    private function parseBooleanAnswer(string $answer): ?bool
    {
        $normalized = mb_strtolower(trim($answer), 'UTF-8');
        $normalized = preg_replace('/[\.!。]+$/u', '', $normalized) ?? $normalized;

        return match ($normalized) {
            'đúng', 'dung', 'true', 't', 'yes', 'co', 'có' => true,
            'sai', 'false', 'f', 'no', 'khong', 'không' => false,
            default => null,
        };
    }

    private function stripOptionLabel(string $text): string
    {
        return $this->cleanInlineText((string) preg_replace('/^\s*[A-F]\s*[\.\)]\s*/iu', '', $text));
    }

    private function answerLetterToIndex(string $letter): ?int
    {
        $index = ord(strtoupper($letter)[0]) - ord('A');
        return $index >= 0 && $index <= 5 ? $index : null;
    }

    private function isAnswerHeading(string $text): bool
    {
        return preg_match('/^\s*bảng\s+đáp\s+án\s*$/iu', $text) === 1;
    }

    private function isExplanationHeading(string $text): bool
    {
        return preg_match('/^\s*lời\s*giải\b/iu', $text) === 1;
    }

    private function cleanInlineText(string $text): string
    {
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s*\R\s*/u', "\n", $text) ?? $text;

        return trim($text);
    }
}
