<?php

namespace App\Events\ReplacePackageJson;

use App\Events\ReplacePackageJson\ReplacePackageJsonEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ReplacePackageJsonListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ReplacePackageJsonEvent $event): void
    {
        $submission = $event->submission;
        Log::info("Replacing package.json to {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "Replacing package.json");
        try {
            // processing
            $process = new Process($event->command);
            $process->run();
            if ($process->isSuccessful()) {
                // completed
                Log::info("Replaced package.json to {$event->tempDir}");
                $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "Replaced package.json");
            } else {
                // failed
                Log::error("Failed to replace package.json to {$event->tempDir} " . $process->getErrorOutput());
                $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to replace package.json");
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            }
        } catch (\Throwable $th) {
            // failed
            Log::error("Failed to replace package.json to {$event->tempDir} " . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to replace package.json");
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
        }
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$REPLACE_PACKAGE_JSON;
        $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
