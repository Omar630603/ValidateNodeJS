<?php

namespace App\Events\ExamineFolderStructure;

use App\Events\ExamineFolderStructure\ExamineFolderStructureEvent;
use App\Models\ExecutionStep;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ExamineFolderStructureListener
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
    public function handle(ExamineFolderStructureEvent $event): void
    {
        $submission = $event->submission;
        Log::info("Examining folder structure from {$event->tempDir}");
        $this->updateSubmissionStatus($submission, Submission::$PROCESSING, "Examining folder structure");
        try {
            // processing
            $process = new Process($event->command);
            $process->run();
            if ($process->isSuccessful()) {
                // completed
                $projectStructure = $submission->project->defaultFileStructure;
                $defaultStructure = $projectStructure->structure;
                $excludedFolders = $projectStructure->excluded;
                $replacementFolders = $projectStructure->replacements;

                $submissionStructure = $this->getDirectoryStructure($event->tempDir, $excludedFolders, $replacementFolders);

                $diff = $this->compare_file_structures($defaultStructure, $submissionStructure);
                $missingFiles = [];
                foreach ($diff as $key => $value) {
                    if (gettype($key) == 'integer') {
                        if (!in_array($value, $excludedFolders)) array_push($missingFiles, $value);
                    } else {
                        if (!in_array($key, $excludedFolders)) array_push($missingFiles, [$key => $value]);
                    }
                }

                Log::info("Finished examining folder structure from {$event->tempDir}");
                if (empty($missingFiles)) {
                    $this->updateSubmissionStatus($submission, Submission::$COMPLETED, "Finished examining folder structure from successfully");
                } else {
                    Log::error("Failed to examine folder structure from {$event->tempDir} " . json_encode($missingFiles) . " are missing");
                    $this->updateSubmissionStatus($submission, Submission::$FAILED, "Submitted project is missing the following files " . json_encode($missingFiles));
                    Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
                }
            } else {
                Log::error("Failed to examine folder structure from {$event->tempDir} " . $process->getErrorOutput());
                $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to examine folder structure");
                Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
            }
        } catch (\Throwable $th) {
            Log::error("Failed to examine folder structure from {$event->tempDir}" . $th->getMessage());
            $this->updateSubmissionStatus($submission, Submission::$FAILED, "Failed to examine folder structure");
            Process::fromShellCommandline("rm -rf {$event->tempDir}")->run();
        }
    }

    private function getDirectoryStructure($dirPath, $excludedFolders, $replacementFolders)
    {
        $structure = [];
        $files = glob($dirPath . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                $dirName = basename($file);
                if (!in_array($dirName, $excludedFolders)) {
                    if (isset($replacementFolders[$dirName])) {
                        $dirName = $replacementFolders[$dirName];
                    }
                    $structure[$dirName] = $this->getDirectoryStructure($file, $excludedFolders, $replacementFolders);
                }
            } else {
                $structure[basename($file)] = '';
            }
        }
        return $structure;
    }

    private function compare_file_structures($defaultStructure, $submittedStructure)
    {
        $diff = [];
        foreach ($defaultStructure as $key => $value) {
            if (is_array($value)) {
                if (!isset($submittedStructure[$key])) {
                    $diff[$key] = $value;
                } else {
                    $new_diff = $this->compare_file_structures($value, $submittedStructure[$key]);
                    if (!empty($new_diff)) {
                        $diff[$key] = $new_diff;
                    }
                }
            } else if (!array_key_exists($key, $submittedStructure) || $submittedStructure[$key] !== $value) {
                $diff[] = $key;
            }
        }
        return $diff;
    }

    private function updateSubmissionStatus(Submission $submission, string $status, string $output): void
    {
        $stepName = ExecutionStep::$EXAMINE_FOLDER_STRUCTURE;
        if ($status != Submission::$PROCESSING) $submission->updateOneResult($stepName, $status, $output);
        if ($status != Submission::$COMPLETED) $submission->updateStatus($status);
    }
}
