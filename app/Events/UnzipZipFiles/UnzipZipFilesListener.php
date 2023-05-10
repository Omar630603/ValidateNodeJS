<?php

namespace App\Events\UnzipZipFiles;

use App\Events\UnzipZipFiles\UnzipZipFilesEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class UnzipZipFilesListener implements ShouldQueue
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
    public function handle(UnzipZipFilesEvent $event): void
    {
        $submission = $event->submission;
        Log::info("Unzipping {$event->zipFileDir} into {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "Unzipping submitted folder");
        try {
            // processing
            $process = new Process($event->command);
            $process->run();
            if ($process->isSuccessful()) {
                Log::info("Unzipped {$event->zipFileDir} into {$event->tempDir}");
                $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "Unzipped submitted folder");
            } else {
                Log::error("Failed to unzip {$event->zipFileDir} " . $process->getErrorOutput());
                $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to unzip submitted folder");
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            }
        } catch (\Throwable $th) {
            // failed
            Log::error("Failed to unzip {$event->zipFileDir} " . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed tp unzip submitted folder");
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
        }
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$UNZIP_ZIP_FILES;
        if ($status != Submission::$PROCESSING) $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
