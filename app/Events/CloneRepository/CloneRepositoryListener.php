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
            $step_name = $step->name;
            $status = Submission::$COMPLETED;
            $output = $process->getOutput();
            if (empty($output)) {
                $output = "Cloned repo {$event->repoUrl}";
            }
            $submission->updateOneResult($step_name, $status, $output);
        } else {
            Log::error("Failed to clone repo {$event->repoUrl}");
            $step_name = $step->name;
            $status = Submission::$FAILED;
            $output = $process->getErrorOutput();
            $submission->updateStatus($status);
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            $submission->updateOneResult($step_name, $status, $output);
        }
    }
}