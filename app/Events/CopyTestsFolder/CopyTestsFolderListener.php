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
        Log::info("Copying tests folder to {$event->tempDir}");
        $submission = $event->submission;
        $step = ExecutionStep::where('name', ExecutionStep::$COPY_TESTS_FOLDER)->first();
        $step_name = $step->name;
        $status = Submission::$PROCESSING;
        $output = "Copying tests folder to {$event->tempDir}";
        $submission->updateOneResult($step_name, $status, $output);

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
                    $status = Submission::$FAILED;
                    $output = $process->getErrorOutput();
                    $submission->updateStatus($status);
                    Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
                    $submission->updateOneResult($step_name, $status, $output);
                    throw new \Exception($process->getErrorOutput());
                }
            }
            // completed
            $status = Submission::$COMPLETED;
            $output = "Copied";
            $submission->updateOneResult($step_name, $status, $output);
        } catch (\Throwable $th) {
            Log::error("Failed to copying tests folder to {$event->tempDir}");
            $status = Submission::$FAILED;
            $output = $th->getMessage();
            $submission->updateStatus($status);
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            $submission->updateOneResult($step_name, $status, $output);
        }
    }
}
