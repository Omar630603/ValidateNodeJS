<?php

namespace App\Events\DeleteTempDirectory;

use App\Events\DeleteTempDirectory\DeleteTempDirectoryEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeleteTempDirectoryListener
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
    public function handle(DeleteTempDirectoryEvent $event): void
    {
        $submission = $event->submission;
        Log::info("Deleting folder {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "Deleting folder");
        try {
            // processing
            foreach ($event->command as $key => $value) {
                $process = new Process($value, null, null, null, null);
                $process->run();
                if ($process->isSuccessful()) {
                    Log::info('Command ' . implode(" ", $value) . ' is successful');
                } else {
                    Log::error("Failed to delete folder {$event->tempDir} "   . $process->getErrorOutput());
                    // $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to delete folder");
                }
            }
            // completed
            Log::info("Deleted folder {$event->tempDir}");
            $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "Deleted folder");
        } catch (\Throwable $th) {
            Log::error("Failed to delete folder {$event->tempDir} " . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to delete folder");
        }
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$DELETE_TEMP_DIRECTORY;
        $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
