<?php

namespace App\Jobs;

use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class NpmInstall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $submission;
    public $tempDir;
    public $command;
    public $no_copy;
    /**
     * Create a new job instance.
     */
    public function __construct($submission, $tempDir, $command, $no_copy)
    {
        $this->submission = $submission;
        $this->tempDir = $tempDir;
        $this->command = $command;
        $this->no_copy = $no_copy;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $submission = $this->submission;
        Log::info("NPM installing in folder {$this->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "NPM installing");
        try {
            // processing
            // check if the module is already exist within the assets folder
            $package_lock_json_path = public_path() . '/assets/projects/' . $submission->project->title . '/files/package-lock.json'; // specify the file name to check
            $node_modulesFolderPath = public_path() . '/assets/projects/' . $submission->project->title . '/node_modules'; // specify the folder name to check
            if ($this->no_copy) {
                $process = new Process($this->command, $this->tempDir, null, null, 120);
                $process->start();
                $process_pid = $process->getPid();
                $process->wait();
                if ($process->isSuccessful()) {
                    Log::info("NPM installed in folder {$this->tempDir}");
                    $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "NPM installed");
                } else {
                    Log::error("Failed to NPM install in folder {$this->tempDir} "   . $process->getErrorOutput());
                    $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to NPM install");
                    Process::fromShellCommandline('kill ' . $process_pid)->run();
                    Process::fromShellCommandline("rm -rf {$this->tempDir}")->run();
                    throw new \Exception($process->getErrorOutput());
                }
            } else {
                Process::fromShellCommandline('cp ' . $package_lock_json_path . ' ' . $this->tempDir, null, null, null, null)->run();
                Process::fromShellCommandline('cp -r ' . $node_modulesFolderPath . ' ' . $this->tempDir, null, null, null, null)->run();

                Log::info("NPM installed in folder {$this->tempDir} from assets folder");
                $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "NPM installed");
            }
        } catch (\Throwable $th) {
            Log::error("Failed to NPM install in folder {$this->tempDir}" . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to NPM install");
            Process::fromShellCommandline("rm -rf {$this->tempDir}")->run();
        }
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$NPM_INSTALL;
        if ($status != Submission::$PROCESSING) $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
