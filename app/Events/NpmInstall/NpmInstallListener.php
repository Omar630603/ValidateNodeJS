<?php

namespace App\Events\NpmInstall;

use App\Events\NpmInstall\NpmInstallEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class NpmInstallListener
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
    public function handle(NpmInstallEvent $event): void
    {
        Log::info("NPM install in folder {$event->tempDir}");
        $submission = $event->submission;
        $step = ExecutionStep::where('name', ExecutionStep::$NPM_INSTALL)->first();
        $step_name = $step->name;
        $status = Submission::$PROCESSING;
        $output = "NPM install in folder {$event->tempDir}";
        $submission->updateOneResult($step_name, $status, $output);
        try {
            // processing
            $process = new Process($event->command, $event->tempDir, null, null, null);
            $process->run();
            if ($process->isSuccessful()) {
                Log::info("NPM installed in folder {$event->tempDir}");
                $status = Submission::$COMPLETED;
                $output = "NPM installed";
                $submission->updateOneResult($step_name, $status, $output);
            } else {
                Log::error("Failed to NPM install in folder {$event->tempDir}");
                $status = Submission::$FAILED;
                $output = $process->getErrorOutput();
                $submission->updateStatus($status);
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
                $submission->updateOneResult($step_name, $status, $output);
                throw new \Exception($process->getErrorOutput());
            }
        } catch (\Throwable $th) {
            Log::error("Failed to NPM install in folder {$event->tempDir}");
            $status = Submission::$FAILED;
            $output = $th->getMessage();
            $submission->updateStatus($status);
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            $submission->updateOneResult($step_name, $status, $output);
        }
    }
}
