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

class NpmRunStart implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $submission;
    public $tempDir;
    public $command;
    /**
     * Create a new job instance.
     */
    public function __construct($submission, $tempDir, $command)
    {
        $this->submission = $submission;
        $this->tempDir = $tempDir;
        $this->command = $command;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $submission = $this->submission;
        $tempDir = $this->tempDir;
        $command = $this->command;


        Log::info("NPM run start is processing in folder {$this->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "NPM run start is processing");
        // Change port number in .env file
        $port = $this->getAvailablePort();
        if (!$port) {
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to find an available port for the project");
            return;
        }
        // Change port number in .env file
        $envPath = "$tempDir/.env";
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/PORT=\d+/', "PORT=$port", $envContent);
            file_put_contents($envPath, $envContent);
        }

        // Run NPM start command
        $output = "";
        try {
            // processing
            $process = new Process($command, $tempDir, null, null, null);
            $process->start();
            $process_pid = $process->getPid();

            $fail = true;
            $timeout = 60; // in seconds
            $startTime = time();
            while (time() - $startTime < $timeout) {
                $output = $process->getOutput();
                if (strpos($output, "Server started on port $port") !== false) {
                    log::info("NPM run start is completed in folder {$tempDir} the application is running on port $port");
                    $this->updateSubmissionStatus($submission, Submission::$COMPLETED, $output);
                    $fail = false;
                    $submission->updatePort($port);
                    $process->wait();
                    break;
                }
                usleep(100000); // wait for 100ms before checking the output again
            }
            // timeout reached, kill the process and update the submission status
            if ($fail) {
                $process->stop();
                Log::error("Failed to NPM run start in folder {$tempDir} due to timeout " . $process->getErrorOutput());
                $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to start application on port $port due to timeout");
                Process::fromShellCommandline("npx kill-port $port")->run();
                Process::fromShellCommandline('kill ' . $process_pid)->run();
                throw new \Exception($process->getErrorOutput());
            }
        } catch (\Throwable $th) {
            Log::error("Failed to NPM run start in folder {$tempDir}" . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to start application on port $port");
            Process::fromShellCommandline("npx kill-port $port")->run();
        }
    }

    /**
     * Get an available port number.
     *
     * @return int|null
     */
    private function getAvailablePort(): ?int
    {
        $minPort = 9000;
        $maxPort = 9999;
        for ($port = $minPort; $port <= $maxPort; $port++) {
            $fp = @fsockopen('localhost', $port, $errno, $errstr, 1);
            if (!$fp) {
                return $port;
            } else {
                fclose($fp);
            }
        }
        return null;
    }

    /**
     * Update the submission status and result of a specific step.
     *
     * @param Submission $submission
     * @param string $status
     * @param string $output
     * @return void
     */
    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$NPM_RUN_START;
        if ($status != Submission::$PROCESSING) $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
