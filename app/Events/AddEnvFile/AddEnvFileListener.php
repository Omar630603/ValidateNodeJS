<?php

namespace App\Events\AddEnvFile;

use App\Events\AddEnvFile\AddEnvFileEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AddEnvFileListener implements ShouldQueue
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
    public function handle(AddEnvFileEvent $event): void
    {
        $submission = $event->submission;
        Log::info("Adding env file {$event->envFile} into {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "Adding env file");
        try {
            // processing
            $process = new Process($event->command);
            $process->run();
            if ($process->isSuccessful()) {
                Log::info("Added env file {$event->envFile} into {$event->tempDir}");
                $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "Added env file");
            } else {
                Log::error("Failed to add env file {$event->envFile} " . $process->getErrorOutput());
                $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to add env file");
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            }
        } catch (\Throwable $th) {
            // failed
            Log::error("Failed to add env file {$event->envFile} " . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to add env file");
        }
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$ADD_ENV_FILE;
        $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
