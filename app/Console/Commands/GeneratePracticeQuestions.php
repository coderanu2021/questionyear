<?php

namespace App\Console\Commands;

use App\PracticeQuestionGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('practice:generate {period? : daily, weekly, or monthly; omit to generate all}')]
#[Description('Prepare persisted practice sets using saved questions and Gemini')]
class GeneratePracticeQuestions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PracticeQuestionGenerator $generator): int
    {
        $period = $this->argument('period');
        if ($period !== null && ! isset(PracticeQuestionGenerator::COUNTS[$period])) {
            $this->error('Choose daily, weekly, or monthly.');

            return self::FAILURE;
        }
        $failed = false;
        foreach ($period ? [$period] : array_keys(PracticeQuestionGenerator::COUNTS) as $name) {
            try {
                $generator->generate($name);
                $this->info(ucfirst($name).' practice set is ready.');
            } catch (Throwable $exception) {
                $failed = true;
                $this->error($name.': '.$exception->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
