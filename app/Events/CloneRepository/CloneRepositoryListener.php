<?php

namespace App\Events\CloneRepository;

use App\Events\CloneRepository\CloneRepositoryEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CloneRepositoryListener
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
        $submission = Submission::find($event->submissionId);
        Log::info("Cloning repo {$event->repoUrl} into {$event->tempDir}");

        $process = new Process($event->command);
        $process->run();
        $step = ExecutionStep::where('name', ExecutionStep::$CLONE_REPOSITORY)->first();
        if ($process->isSuccessful()) {
            Log::info("Cloned repo {$event->repoUrl} into {$event->tempDir}");
            // Update the submission status to "cloned"
            $step_name =   $step->name;
            $status = Submission::$COMPLETED;
            $output = $process->getOutput();
            $submission->updateOneResult($step_name, $status, $output);
        } else {
            Log::error("Failed to clone repo {$event->repoUrl}");
            // Update the submission status to "clone_failed"
            $step_name =   $step->name;
            $status = Submission::$FAILED;
            $output = $process->getErrorOutput();
            $submission->updateOneResult($step_name, $status, $output);
        }
    }
}
