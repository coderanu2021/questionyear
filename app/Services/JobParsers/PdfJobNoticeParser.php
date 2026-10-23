<?php

namespace App\Services\JobParsers;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PdfJobNoticeParser implements JobNoticeParser
{
    public function parse(string $url, string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'questionyear-notice-');
        if ($path === false) {
            throw new \RuntimeException('Unable to create a temporary file for the official PDF notice.');
        }

        try {
            file_put_contents($path, $content);
            $process = new Process(['pdftotext', '-layout', $path, '-']);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('pdftotext could not read the official PDF notice.');
            }

            $text = trim(preg_replace('/\s+/u', ' ', $process->getOutput()) ?? '');
            if ($text === '') {
                return [];
            }

            return [[
                'url' => $url,
                'title' => Str::limit(Str::before($text, '. '), 500, ''),
                'raw_text' => Str::limit($text, 25000, ''),
            ]];
        } finally {
            @unlink($path);
        }
    }
}
