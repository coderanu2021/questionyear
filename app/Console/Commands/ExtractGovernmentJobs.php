<?php

namespace App\Console\Commands;

use App\Models\RawNotice;
use App\Services\JobSources\JobNoticeExtractor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

#[Signature('jobs:extract {--retry-failed : Reset failed extraction attempts and retry them}')]
#[Description('Extract structured job details from pending official raw notices')]
class ExtractGovernmentJobs extends Command
{
    private const MAX_ATTEMPTS = 3;

    public function handle(JobNoticeExtractor $extractor): int
    {
        if ($this->option('retry-failed')) {
            RawNotice::query()->where('extraction_status', 'failed')->update([
                'extraction_status' => 'pending',
                'extraction_attempts' => 0,
                'last_extraction_error' => null,
            ]);
        }

        $summary = ['processed' => 0, 'created' => 0, 'auto_published' => 0, 'failed' => 0];
        $rawNotices = RawNotice::query()
            ->with('source')
            ->where('extraction_status', 'pending')
            ->where('extraction_attempts', '<', self::MAX_ATTEMPTS)
            ->whereHas('source', fn (Builder $query): Builder => $query->where('active', true))
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($rawNotices as $rawNotice) {
            try {
                $result = $extractor->extract($rawNotice);
                $summary['processed']++;
                $summary['created'] += $result['created'] ? 1 : 0;
                $summary['auto_published'] += $result['auto_published'] ? 1 : 0;
            } catch (Throwable $exception) {
                $summary['failed']++;
                $rawNotice->forceFill([
                    'extraction_status' => 'failed',
                    'extraction_attempts' => $rawNotice->extraction_attempts + 1,
                    'last_extraction_error' => Str::limit($exception->getMessage(), 2000, ''),
                ])->save();

                Log::warning('Government job notice extraction failed.', [
                    'raw_notice_id' => $rawNotice->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        $this->info("Processed {$summary['processed']} raw notices; created {$summary['created']} pending or published notifications; auto-published {$summary['auto_published']}; failed {$summary['failed']}.");

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
