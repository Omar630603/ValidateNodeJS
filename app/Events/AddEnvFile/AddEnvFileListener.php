<?php

namespace App\Events\AddEnvFile;

use App\Events\AddEnvFile\AddEnvFileEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AddEnvFileListener
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
        Log::info("Adding env file {$event->envFile} into {$event->tempDir}");
        $submission = Submission::find($event->submissionId);
        $step = ExecutionStep::where('name', ExecutionStep::$ADD_ENV_FILE)->first();
        $step_name = $step->name;
        $status = Submission::$PROCESSING;
        $output = "Adding env file {$event->envFile}";
        $submission->updateOneResult($step_name, $status, $output);
        try {
            // processing
            $process = new Process($event->command);
            $process->run();
            if ($process->isSuccessful()) {
                Log::info("Added env file {$event->envFile} into {$event->tempDir}");
                $status = Submission::$COMPLETED;
                $output = "Added env file";
                $submission->updateOneResult($step_name, $status, $output);
            } else {
                Log::error("Failed to add env file {$event->envFile}");
                $status = Submission::$FAILED;
                $output = $process->getErrorOutput();
                $submission->updateStatus($status);
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
                $submission->updateOneResult($step_name, $status, $output);
            }
        } catch (\Throwable $th) {
            // failed
            Log::error("Failed to add env file {$event->envFile}");
            $status = Submission::$FAILED;
            $output = $th->getMessage();
            $submission->updateStatus($status);
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            $submission->updateOneResult($step_name, $status, $output);
        }
    }
}
