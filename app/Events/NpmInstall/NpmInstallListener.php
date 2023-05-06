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
        $submission = $event->submission;
        Log::info("NPM installing in folder {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "NPM installing");
        try {
            // processing
            $process = new Process($event->command, $event->tempDir, null, null, null);
            $process->run();
            if ($process->isSuccessful()) {
                Log::info("NPM installed in folder {$event->tempDir}");
                $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "NPM installed");
            } else {
                Log::error("Failed to NPM install in folder {$event->tempDir} "   . $process->getErrorOutput());
                $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to NPM install");
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
                throw new \Exception($process->getErrorOutput());
            }
        } catch (\Throwable $th) {
            Log::error("Failed to NPM install in folder {$event->tempDir}" . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to NPM install");
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
        }
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$NPM_INSTALL;
        $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
