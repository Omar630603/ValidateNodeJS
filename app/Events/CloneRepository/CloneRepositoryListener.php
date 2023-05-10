<?php

namespace App\Events\CloneRepository;

use App\Events\CloneRepository\CloneRepositoryEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CloneRepositoryListener implements ShouldQueue
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
    public function handle(CloneRepositoryEvent $event): void
    {
        $submission = $event->submission;
        Log::info("Cloning repo {$event->repoUrl} into {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "Cloning repo {$event->repoUrl}");
        try {
            // processing
            $process = new Process($event->command);
            $process->run();
            if ($process->isSuccessful()) {
                // completed
                Log::info("Cloned repo {$event->repoUrl} into {$event->tempDir}");
                $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "Cloned repo {$event->repoUrl}");
            } else {
                // failed
                Log::error("Failed to clone repo {$event->repoUrl} " . $process->getErrorOutput());
                $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to clone repo {$event->repoUrl}");
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            }
        } catch (\Throwable $th) {
            // failed
            Log::error("Failed to clone repo {$event->repoUrl} " . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to clone repo {$event->repoUrl}");
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
        }
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$CLONE_REPOSITORY;
        if ($status != Submission::$PROCESSING) $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
