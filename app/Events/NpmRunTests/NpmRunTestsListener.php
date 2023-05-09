<?php

namespace App\Events\NpmRunTests;

use App\Events\NpmRunTests\NpmRunTestsEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class NpmRunTestsListener implements ShouldQueue
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
    public function handle(NpmRunTestsEvent $event): void
    {
        $submission = $event->submission;
        Log::info("NPM running tests in folder {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "NPM running tests");
        try {
            // processing
            $pass_all = false;
            $commands = $event->command;
            foreach ($commands as  $command) {
                $command_string = implode(" ", $command);
                Log::info("Running {$command_string} in folder {$event->tempDir}");
                $this->updateSubmissionTestsResultsStatus($command_string, $submission, Submission::$PROCESSING, "Running");
                $process = new Process($command, $event->tempDir, null, null, null);
                $process->run();
                if ($process->isSuccessful()) {
                    $pass_all = true;
                    Log::info("{$command_string} in folder {$event->tempDir}");
                    $this->updateSubmissionTestsResultsStatus($command_string, $submission, Submission::$COMPLETED, $process->getOutput());
                } else {
                    $pass_all = false;
                    Log::error("Failed to NPM run test {$command_string}"   . $process->getErrorOutput());
                    $this->updateSubmissionTestsResultsStatus($command_string, $submission, Submission::$FAILED, $process->getErrorOutput());
                }
            }
            if ($pass_all) {
                Log::info("NPM ran tests in folder {$event->tempDir}");
                $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "NPM tested");
            } else {
                Log::info("NPM failed to run tests in folder {$event->tempDir}");
                $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to run NPM tests");
            }
        } catch (\Throwable $th) {
            Log::error("Failed to NPM run tests in folder {$event->tempDir} " . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to NPM running tests");
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
        }
    }

    private function updateSubmissionTestsResultsStatus($testName, Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$NPM_RUN_TESTS;
        $submission->updateOneTestResult($stepName, $testName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$NPM_RUN_TESTS;
        $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
