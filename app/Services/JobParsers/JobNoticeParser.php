<?php

namespace App\Services\JobParsers;

interface JobNoticeParser
{
    /**
     * @return array<int, array{url: string, title: string, raw_text: string}>
     */
    public function parse(string $url, string $content): array;
}
