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
        Log::info("Cloning repo {$event->repoUrl} into {$event->tempDir}");
        $submission = Submission::find($event->submissionId);
        $step = ExecutionStep::where('name', ExecutionStep::$CLONE_REPOSITORY)->first();
        $step_name = $step->name;
        $status = Submission::$PROCESSING;
        $output = "Cloning repo {$event->repoUrl}";
        $submission->updateOneResult($step_name, $status, $output);
        try {
            // processing
            $process = new Process($event->command);
            $process->run();
            if ($process->isSuccessful()) {
                // completed
                Log::info("Cloned repo {$event->repoUrl} into {$event->tempDir}");
                $status = Submission::$COMPLETED;
                $output = $process->getOutput();
                if (empty($output)) {
                    $output = "Cloned repo {$event->repoUrl}";
                }
                $submission->updateOneResult($step_name, $status, $output);
            } else {
                // failed
                Log::error("Failed to clone repo {$event->repoUrl}");
                $status = Submission::$FAILED;
                $output = $process->getErrorOutput();
                $submission->updateStatus($status);
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
                $submission->updateOneResult($step_name, $status, $output);
            }
        } catch (\Throwable $th) {
            // failed
            Log::error("Failed to clone repo {$event->repoUrl}");
            $status = Submission::$FAILED;
            $output = $th->getMessage();
            $submission->updateStatus($status);
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            $submission->updateOneResult($step_name, $status, $output);
        }
    }
}
