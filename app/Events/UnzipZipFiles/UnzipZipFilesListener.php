<?php

namespace App\Events\UnzipZipFiles;

use App\Events\UnzipZipFiles\UnzipZipFilesEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class UnzipZipFilesListener
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
    public function handle(UnzipZipFilesEvent $event): void
    {
        $submission = Submission::find($event->submissionId);
        Log::info("Unzipping {$event->zipFileDir} into {$event->tempDir}");

        $process = new Process($event->command);
        $process->run();
        $step = ExecutionStep::where('name', ExecutionStep::$UNZIP_ZIP_FILES)->first();
        if ($process->isSuccessful()) {
            Log::info("Unzipped {$event->zipFileDir} into {$event->tempDir}");
            $step_name = $step->name;
            $status = Submission::$COMPLETED;
            $output = "Unzipped";
            Process::fromShellCommandline("rm -rf {$event->zipFileDir}")->run();
            $submission->updateOneResult($step_name, $status, $output);
        } else {
            Log::error("Failed to unzip {$event->zipFileDir}");
            $step_name = $step->name;
            $status = Submission::$FAILED;
            $output = $process->getErrorOutput();
            $submission->updateStatus($status);
            Process::fromShellCommandline("rm -rf {$event->zipFileDir}")->run();
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            $submission->updateOneResult($step_name, $status, $output);
        }
    }
}
