<?php

namespace App\Events\NpmInstall;

use App\Events\NpmInstall\NpmInstallEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class NpmInstallListener implements ShouldQueue
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
            $node_modulesFolderPath = public_path() . '/' . 'assets/projects/' . $submission->project->title . '/node_modules'; // specify the folder name to check
            if (is_dir($node_modulesFolderPath)) {
                Process::fromShellCommandline('cp -r ' . $node_modulesFolderPath . ' ' . $event->tempDir, null, null, null, null)->run();
            }

            $process = new Process($event->command, $event->tempDir, null, null, 120);
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
        if ($status != Submission::$PROCESSING) $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
