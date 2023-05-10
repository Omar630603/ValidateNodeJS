<?php

namespace App\Events\CopyTestsFolder;

use App\Events\CopyTestsFolder\CopyTestsFolderEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CopyTestsFolderListener
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
    public function handle(CopyTestsFolderEvent $event): void
    {
        $submission = $event->submission;
        Log::info("Copying tests folder to {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "Copying tests folder");
        try {
            // processing
            mkdir($event->tempDir . '/tests', 0777, true);
            mkdir($event->tempDir . '/tests/api', 0777, true);
            mkdir($event->tempDir . '/tests/web', 0777, true);
            mkdir($event->tempDir . '/tests/web/images', 0777, true);
            foreach ($event->command as $key => $value) {
                $process = new Process($value);
                $process->run();
                if ($process->isSuccessful()) {
                    Log::info("Copied tests {$value[2]} folder to {$value[3]}");
                } else {
                    Log::error("Failed to copying tests {$value[2]} folder to {$value[3]}");
                    $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to copying tests folder");
                    Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
                    throw new \Exception($process->getErrorOutput());
                }
            }
            // completed
            Log::info("Copied tests folder to {$event->tempDir}");
            $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "Copied tests folder");
        } catch (\Throwable $th) {
            Log::error("Failed to copying tests folder to {$event->tempDir} " . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to copying tests folder");
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
        }
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$COPY_TESTS_FOLDER;
        if ($status != Submission::$PROCESSING) $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
