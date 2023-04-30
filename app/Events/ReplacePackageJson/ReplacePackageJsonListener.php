<?php

namespace App\Events\ReplacePackageJson;

use App\Events\ReplacePackageJson\ReplacePackageJsonEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ReplacePackageJsonListener
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
        Log::info("Replacing package.json to {$event->tempDir}");
        $submission = Submission::find($event->submissionId);
        $step = ExecutionStep::where('name', ExecutionStep::$REPLACE_PACKAGE_JSON)->first();
        $step_name = $step->name;
        $status = Submission::$PROCESSING;
        $output = "Replacing package.json";
        $submission->updateOneResult($step_name, $status, $output);
        try {
            // processing
            $process = new Process($event->command);
            $process->run();
            if ($process->isSuccessful()) {
                Log::info("Replaced package.json to {$event->tempDir}");
                $status = Submission::$COMPLETED;
                $output = "Replaced";
                $submission->updateOneResult($step_name, $status, $output);
            } else {
                Log::error("Failed to replace package.json to {$event->tempDir}");
                $status = Submission::$FAILED;
                $output = $process->getErrorOutput();
                $submission->updateStatus($status);
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
                $submission->updateOneResult($step_name, $status, $output);
            }
        } catch (\Throwable $th) {
            // failed
            Log::error("Failed to replace package.json to {$event->tempDir}");
            $status = Submission::$FAILED;
            $output = $th->getMessage();
            $submission->updateStatus($status);
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            $submission->updateOneResult($step_name, $status, $output);
        }
    }
}
