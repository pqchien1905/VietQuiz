<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

class DocumentTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'docx' => $this->extractDocx($file->getRealPath()),
            'pdf' => $this->extractPdf($file->getRealPath()),
            'doc' => throw new RuntimeException('File .doc cu khong duoc ho tro truc tiep. Vui long luu lai thanh .docx hoac PDF.'),
            default => throw new RuntimeException('Dinh dang file khong duoc ho tro. Hay dung PDF hoac DOCX.'),
        };
    }

    private function extractDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Khong mo duoc file Word.');
        }

        $parts = [
            'word/document.xml',
            'word/header1.xml',
            'word/header2.xml',
            'word/header3.xml',
            'word/footer1.xml',
            'word/footer2.xml',
            'word/footer3.xml',
        ];

        $text = '';
        foreach ($parts as $part) {
            $xml = $zip->getFromName($part);
            if ($xml === false) {
                continue;
            }
            $text .= "\n" . $this->textFromDocxXml($xml);
        }

        $zip->close();

        return $this->cleanText($text);
    }

    private function textFromDocxXml(string $xml): string
    {
        $xml = preg_replace('/<w:tab\/>/', ' ', $xml) ?? $xml;
        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:tr>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:tc>/', "\t", $xml) ?? $xml;
        $text = strip_tags($xml);

        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function extractPdf(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false || $content === '') {
            throw new RuntimeException('Khong doc duoc file PDF.');
        }

        $streams = $this->extractPdfStreams($content);
        $text = '';

        foreach ($streams as $stream) {
            $text .= "\n" . $this->textFromPdfStream($stream);
        }

        $text = $this->cleanText($text);
        if ($text === '') {
            throw new RuntimeException('Khong trich xuat duoc chu tu PDF. Neu file la anh scan, can OCR truoc khi import.');
        }

        return $text;
    }

    /**
     * @return array<int, string>
     */
    private function extractPdfStreams(string $content): array
    {
        preg_match_all('/<<(.*?)>>\s*stream\r?\n(.*?)\r?\nendstream/s', $content, $matches, PREG_SET_ORDER);

        $streams = [];
        foreach ($matches as $match) {
            $dictionary = $match[1] ?? '';
            $stream = $match[2] ?? '';

            if (str_contains($dictionary, '/FlateDecode')) {
                $inflated = @gzuncompress($stream);
                if ($inflated === false) {
                    $inflated = @gzdecode($stream);
                }
                if ($inflated === false) {
                    $inflated = @gzinflate($stream);
                }
                if ($inflated !== false) {
                    $stream = $inflated;
                }
            }

            $streams[] = $stream;
        }

        if ($streams === []) {
            $streams[] = $content;
        }

        return $streams;
    }

    private function textFromPdfStream(string $stream): string
    {
        $text = '';

        preg_match_all('/\[(.*?)\]\s*TJ/s', $stream, $arrayMatches);
        foreach ($arrayMatches[1] ?? [] as $arrayText) {
            $text .= ' ' . $this->decodePdfStringGroup($arrayText);
        }

        preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*Tj/s', $stream, $stringMatches);
        foreach ($stringMatches[0] ?? [] as $operatorText) {
            if (preg_match('/\(((?:\\\\.|[^\\\\)])*)\)\s*Tj/s', $operatorText, $match)) {
                $text .= ' ' . $this->decodePdfString($match[1]);
            }
        }

        preg_match_all('/<([0-9A-Fa-f\s]+)>\s*Tj/s', $stream, $hexMatches);
        foreach ($hexMatches[1] ?? [] as $hex) {
            $text .= ' ' . $this->decodePdfHexString($hex);
        }

        return $text;
    }

    private function decodePdfStringGroup(string $group): string
    {
        $text = '';
        preg_match_all('/\(((?:\\\\.|[^\\\\)])*)\)|<([0-9A-Fa-f\s]+)>/', $group, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (($match[1] ?? '') !== '') {
                $text .= $this->decodePdfString($match[1]);
            } elseif (($match[2] ?? '') !== '') {
                $text .= $this->decodePdfHexString($match[2]);
            }
        }

        return $text;
    }

    private function decodePdfString(string $value): string
    {
        $value = preg_replace_callback('/\\\\([nrtbf\\\\()])/', function (array $match): string {
            return match ($match[1]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'b' => "\b",
                'f' => "\f",
                default => $match[1],
            };
        }, $value) ?? $value;

        $value = preg_replace_callback('/\\\\([0-7]{1,3})/', fn (array $match): string => chr(octdec($match[1])), $value) ?? $value;

        return $value;
    }

    private function decodePdfHexString(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex) ?? '';
        if ($hex === '') {
            return '';
        }
        if (strlen($hex) % 2 === 1) {
            $hex .= '0';
        }

        $binary = @hex2bin($hex);
        if ($binary === false) {
            return '';
        }

        if (str_starts_with($binary, "\xFE\xFF")) {
            $converted = @mb_convert_encoding(substr($binary, 2), 'UTF-8', 'UTF-16BE');
            return is_string($converted) ? $converted : '';
        }

        return $binary;
    }

    private function cleanText(string $text): string
    {
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\R{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
