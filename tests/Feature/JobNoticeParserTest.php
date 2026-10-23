<?php

namespace Tests\Feature;

use App\Services\JobParsers\HtmlJobNoticeParser;
use App\Services\JobParsers\PdfJobNoticeParser;
use App\Services\JobParsers\RssJobNoticeParser;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class JobNoticeParserTest extends TestCase
{
    public function test_html_parser_extracts_notice_links_and_text_from_a_saved_fixture(): void
    {
        $content = file_get_contents(base_path('tests/Fixtures/government-jobs/ssc-notices.html'));
        $parser = new HtmlJobNoticeParser;
        $notices = $parser->parse('https://ssc.gov.in/noticeboard', $content);

        $this->assertCount(2, $notices);
        $this->assertSame('https://ssc.gov.in/NoticeBoard/notice.pdf', $notices[0]['url']);
        $this->assertSame('Notice of recruitment for selection posts', $notices[0]['title']);
        $this->assertStringContainsString('recruitment', strtolower($notices[0]['raw_text']));
        $this->assertSame('SSC Notices', $parser->extractTitle($content));
        $this->assertStringContainsString('Latest notices', $parser->extractText($content));
    }

    public function test_rss_parser_resolves_relative_links_and_summarizes_saved_fixture_text(): void
    {
        $content = file_get_contents(base_path('tests/Fixtures/government-jobs/notices.xml'));
        $notices = (new RssJobNoticeParser)->parse('https://ssc.gov.in/feeds/current.xml', $content);

        $this->assertCount(2, $notices);
        $this->assertSame('https://ssc.gov.in/recruitment/notice-1', $notices[0]['url']);
        $this->assertSame('Recruitment notification for assistant posts', $notices[0]['title']);
        $this->assertStringContainsString('official advertisement', $notices[0]['raw_text']);
        $this->assertSame('https://ssc.gov.in/notices/admit-card', $notices[1]['url']);
    }

    public function test_pdf_parser_extracts_text_from_a_saved_fixture_without_a_live_request(): void
    {
        $process = new Process(['pdftotext', '-v']);
        $process->run();
        if (! $process->isSuccessful()) {
            $this->markTestSkipped('pdftotext is not installed in this environment.');
        }

        $content = file_get_contents(base_path('tests/Fixtures/government-jobs/official-notice.pdf'));
        $notices = (new PdfJobNoticeParser)->parse('https://ssc.gov.in/official-notice.pdf', $content);

        $this->assertCount(1, $notices);
        $this->assertSame('https://ssc.gov.in/official-notice.pdf', $notices[0]['url']);
        $this->assertStringContainsString('Official Recruitment Notification', $notices[0]['raw_text']);
    }
}
