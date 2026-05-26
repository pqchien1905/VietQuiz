<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadedFileStorage
{
    public static function storeWithOriginalName(UploadedFile $file, string $directory): string
    {
        $filename = self::safeOriginalName($file);
        $path = trim($directory, '/').'/'.(string) Str::uuid();

        return $file->storeAs($path, $filename);
    }

    private static function safeOriginalName(UploadedFile $file): string
    {
        $original = trim($file->getClientOriginalName());

        if ($original === '') {
            $extension = $file->getClientOriginalExtension();

            return 'attachment'.($extension !== '' ? '.'.$extension : '');
        }

        $filename = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/u', '_', $original) ?? $original;
        $filename = trim($filename, " .\t\n\r\0\x0B");

        if ($filename === '') {
            $extension = $file->getClientOriginalExtension();

            return 'attachment'.($extension !== '' ? '.'.$extension : '');
        }

        return Str::limit($filename, 180, '');
    }
}
