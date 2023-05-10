<?php

namespace App\Http\Controllers;

use App\Jobs\AddEnvFile;
use App\Jobs\CloneRepository;
use App\Jobs\CopyTestsFolder;
use App\Jobs\DeleteTempDirectory;
use App\Jobs\ExamineFolderStructure;
use App\Jobs\NpmInstall;
use App\Jobs\NpmRunStart;
use App\Jobs\NpmRunTests;
use App\Jobs\ReplacePackageJson;
use App\Jobs\UnzipZipFiles;
use App\Models\ExecutionStep;
use App\Models\Project;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use App\Models\TemporaryFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        return view('submissions.index');
    }

    public function upload(Request $request, $project_id)
    {
        if ($request->hasFile('folder_path')) {
            $project_title = Project::find($project_id)->title;

            $file = $request->file('folder_path');
            $file_name = $file->getClientOriginalName();
            $folder_path = 'public/tmp/submissions/' . $request->user()->id . '/' . $project_title;
            $file->storeAs($folder_path, $file_name);

            TemporaryFile::create([
                'folder_path' => $folder_path,
                'file_name' => $file_name,
            ]);

            return $folder_path;
        }
        return '';
    }

    public function submit(Request $request)
    {

        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'folder_path' => 'required_without:github_url',
                'github_url' => 'required_without:folder_path',
            ]);

            $submission = new Submission();
            $submission->user_id = $request->user()->id;
            $submission->project_id = $request->project_id;
            if ($request->has('folder_path')) {
                $submission->type = Submission::$FILE;
                $submission->path = $request->folder_path;

                $temporary_file = TemporaryFile::where('folder_path', $request->folder_path)->first();

                if ($temporary_file) {
                    $path = storage_path('app/' . $request->folder_path . '/' . $temporary_file->file_name);
                    $submission->addMedia($path)->toMediaCollection('submissions', 'public_submissions_files');
                    if ($this->is_dir_empty(storage_path('app/' . $request->folder_path))) {
                        rmdir(storage_path('app/' . $request->folder_path));
                    }
                    $temporary_file->delete();
                }
            } else {
                $submission->type = Submission::$URL;
                $submission->path = $request->github_url;
            }
            $submission->status = Submission::$PENDING;
            $submission->save();


            return response()->json([
                'message' => 'Submission created successfully',
                'submission' => $submission,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Submission failed',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function showAllSubmissionsBasedOnProject(Request $request, $project_id)
    {
        $project = Project::find($project_id);
        $submissions = Submission::where('project_id', $project_id)
            ->where('user_id', $request->user()->id)->get();
        if (!$project) {
            return redirect()->route('submissions');
        }
        return view('submissions.show', compact('project', 'submissions'));
    }

    public function show(Request $request, $submission_id)
    {
        $submission = Submission::find($submission_id);
        if ($submission) {
            $steps = $submission->getExecutionSteps();
            $currentStep = $submission->getCurrentExecutionStep();
            return view('submissions.show', compact('submission', 'steps', 'currentStep'));
        }
        return redirect()->route('submissions');
    }

    public function status(Request $request, $submission_id)
    {
        $submission = Submission::find($submission_id);
        if (!$submission) {
            return response()->json([
                'message' => 'Submission not found',
            ], 404);
        }
        return response()->json([
            'status' => $submission->status,
            'step' => $submission->getCurrentExecutionStep(),
        ], 200);
    }

    public function process(Request $request, $submission_id)
    {
        $submission = Submission::find($submission_id);
        if ($submission) {
            $completion_percentage = round($submission->getTotalCompletedSteps() / $submission->getTotalSteps() * 100);
            if ($submission->status === Submission::$PENDING) {
                $submission->initializeResults();
                $submission->updateStatus(Submission::$PROCESSING);
                $currentStep = $submission->getCurrentExecutionStep();
                return $this->returnSubmissionResponse("Submission is processing", $submission->status, $submission->results, $currentStep, $completion_percentage);
            } else if ($submission->status === Submission::$COMPLETED) {
                return $this->returnSubmissionResponse("Submission has completed", $submission->status, $submission->results, null, $completion_percentage);
            } else if ($submission->status === Submission::$FAILED) {
                return $this->returnSubmissionResponse("Submission has failed", $submission->status, $submission->results, null, $completion_percentage);
            } else if ($submission->status === Submission::$PROCESSING) {
                $step = $submission->getCurrentExecutionStep();
                if ($step) {
                    if ($submission->results->{$step->executionStep->name}->status == Submission::$PENDING) {
                        $submission->updateOneResult($step->executionStep->name, Submission::$PROCESSING, " ");
                        switch ($step->executionStep->name) {
                            case ExecutionStep::$CLONE_REPOSITORY:
                                $this->lunchCloneRepositoryJob($submission, $submission->path, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$UNZIP_ZIP_FILES:
                                $zipFileDir = $submission->getMedia('submissions')->first()->getPath();
                                $this->lunchUnzipZipFilesJob($submission, $zipFileDir, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$EXAMINE_FOLDER_STRUCTURE:
                                $this->lunchExamineFolderStructureJob($submission, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$ADD_ENV_FILE:
                                $envFile = $submission->project->getMedia('project_files')->where('file_name', '.env')->first()->getPath();
                                $this->lunchAddEnvFileJob($submission, $envFile, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$REPLACE_PACKAGE_JSON:
                                $packageJson = $submission->project->getMedia('project_files')->where('file_name', 'package.json')->first()->getPath();
                                $this->lunchReplacePackageJsonJob($submission, $packageJson, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$COPY_TESTS_FOLDER:
                                $this->lunchCopyTestsFolderJob($submission, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$NPM_INSTALL:
                                $this->lunchNpmInstallJob($submission, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$NPM_RUN_START:
                                $this->lunchNpmRunStartJob($submission, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$NPM_RUN_TESTS:
                                $this->lunchNpmRunTestsJob($submission, $this->getTempDir($submission), $step);
                                break;
                            case ExecutionStep::$DELETE_TEMP_DIRECTORY:
                                $this->lunchDeleteTempDirectoryJob($submission, $this->getTempDir($submission), $step);
                                break;
                            default:
                                break;
                        }
                    }
                    return $this->returnSubmissionResponse(
                        'Step ' . $step->executionStep->name . ' is ' . $submission->results->{$step->executionStep->name}->status,
                        $submission->status,
                        $submission->results,
                        $step,
                        $completion_percentage
                    );
                }
                return $this->returnSubmissionResponse(
                    'Submission is processing meanwhile there is no step to execute',
                    $submission->status,
                    $submission->results,
                    $step,
                    $completion_percentage
                );
            }
        }
        return response()->json([
            'message' => 'Submission not found',
        ], 404);
    }

    public function returnSubmissionResponse($message, $status, $results, $next_step = null, $completion_percentage)
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
            'results' => $results,
            'next_step' => $next_step,
            'completion_percentage' => $completion_percentage,
        ], 200);
    }

    public function refresh(Request $request, $submission_id)
    {
        $submission = Submission::find($submission_id);
        if ($submission and $submission->status === Submission::$FAILED) {
            $commands = [];
            if ($submission->port != null) {
                $commands = [
                    ['npx', 'kill-port', $submission->port],
                    ['rm', '-rf', $this->getTempDir($submission)],
                ];
            } else {
                $commands = [
                    ['rm', '-rf', $this->getTempDir($submission)],
                ];
            }
            // Delete temp directory
            foreach ($commands as $command) {
                $process = new Process($command, null, null, null, 120);
                $process->run();
                if ($process->isSuccessful()) {
                    Log::info('Command ' . implode(" ", $command) . ' is successful');
                } else {
                    Log::error('Command ' . implode(" ", $command) . ' has failed '   . $process->getErrorOutput());
                }
            }
            // Update submission status
            $submission->updateStatus(Submission::$PENDING);
            $current_attempt = $submission->attempts;
            $submission->initializeResults(true);
            // Create submission history
            $submission_history                 = new SubmissionHistory();
            $submission_history->submission_id  = $submission->id;
            $submission_history->user_id        = $submission->user_id;
            $submission_history->project_id     = $submission->project_id;
            $submission_history->type           = $submission->type;
            $submission_history->path           = $submission->path;
            $submission_history->status         = $submission->status;
            $submission_history->results        = $submission->results;
            $submission_history->attempts       = $current_attempt;
            $submission_history->port           = $submission->port;
            $submission_history->save();
            $submission->updatePort(null);
            // Return response
            return response()->json([
                'message' => 'Submission has been refreshed',
                'status' => $submission->status,
                'results' => $submission->results,
                'attempts' => $submission->attempts,
                'completion_percentage' => 0,
            ], 200);
        }
    }

    private function getTempDir($submission)
    {
        return storage_path('app/public/tmp/submissions/' . $submission->user_id . '/' . $submission->project->title . '/' . $submission->id);
    }

    private function is_dir_empty($dir)
    {
        if (!is_readable($dir)) return null;
        return (count(scandir($dir)) == 2);
    }

    private function replaceCommandArraysWithValues($step_variables, $values, $step)
    {
        return array_reduce($step_variables, function ($commands, $variableValue) use ($values) {
            return array_map(function ($command) use ($variableValue, $values) {
                return $command === $variableValue ? $values[$variableValue] : $command;
            }, $commands);
        }, $step->executionStep->commands);
    }

    private function lunchCloneRepositoryJob($submission, $repoUrl, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ["{{repoUrl}}" => $repoUrl, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        dispatch(new CloneRepository($submission, $repoUrl, $tempDir, $commands));
    }

    private function lunchUnzipZipFilesJob($submission, $zipFileDir, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{zipFileDir}}' => $zipFileDir, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        dispatch(new UnzipZipFiles($submission, $zipFileDir, $tempDir, $commands));
    }

    private function lunchExamineFolderStructureJob($submission, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        dispatch(new ExamineFolderStructure($submission, $tempDir, $commands));
    }

    private function lunchAddEnvFileJob($submission, $envFile, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{envFile}}' => $envFile, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        dispatch(new AddEnvFile($submission, $envFile, $tempDir, $commands));
    }

    private function lunchReplacePackageJsonJob($submission, $packageJson, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{packageJson}}' => $packageJson, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        dispatch(new ReplacePackageJson($submission, $packageJson, $tempDir, $commands));
    }

    private function lunchCopyTestsFolderJob($submission, $tempDir, $step)
    {
        $testsDir = [
            'testsDirApi' => $submission->project->getMedia('project_tests_api'),
            'testsDirWeb' => $submission->project->getMedia('project_tests_web'),
            'testsDirImage' => $submission->project->getMedia('project_tests_images'),
        ];
        // command 1: [1]cp [2]-r [3]{{testsDir}} [4]{{tempDir}}
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{testsDir}}' => $testsDir, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        $commandsArray = [];
        foreach ($testsDir['testsDirApi'] as $key => $value) {
            $commands[2] = $value->getPath();
            $commands[3] = $tempDir . '/tests/api';
            array_push($commandsArray, $commands);
        }
        foreach ($testsDir['testsDirWeb'] as $key => $value) {
            $commands[2] =  $value->getPath();
            $commands[3] = $tempDir . '/tests/web';
            array_push($commandsArray, $commands);
        }
        foreach ($testsDir['testsDirImage'] as $key => $value) {
            $commands[2] =  $value->getPath();
            $commands[3] = $tempDir . '/tests/web/images';
            array_push($commandsArray, $commands);
        }
        dispatch(new CopyTestsFolder($submission, $testsDir, $tempDir, $commandsArray));
    }

    private function lunchNpmInstallJob($submission, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{options}}' => " "];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        $package_lock_json_path = public_path() . '/assets/projects/' . $submission->project->title . '/files/package-lock.json'; // specify the file name to check
        $node_modulesFolderPath = public_path() . '/assets/projects/' . $submission->project->title . '/node_modules'; // specify the folder name to check
        $no_copy = !is_dir($node_modulesFolderPath) || !file_exists($package_lock_json_path);
        dispatch(new NpmInstall($submission, $tempDir, $commands, $no_copy));
    }

    private function lunchNpmRunStartJob($submission, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        dispatch(new NpmRunStart($submission, $tempDir, $commands));
    }

    private function lunchNpmRunTestsJob($submission, $tempDir, $step)
    {
        $commands = [];
        $tests = $submission->project->projectExecutionSteps->where('execution_step_id', $step->executionStep->id)->first()->variables;
        foreach ($tests as $testCommandValue) {
            $command = implode(" ", $step->executionStep->commands);
            $key = explode("=", $testCommandValue)[0];
            $value = explode("=", $testCommandValue)[1];
            $testName = str_replace($key, $value, $command);
            array_push($commands, explode(" ", $testName));
        }
        dispatch(new NpmRunTests($submission, $tempDir, $commands));
    }

    private function lunchDeleteTempDirectoryJob($submission, $tempDir, $step, $commands = null)
    {
        if ($commands == null) $commands = [$step->executionStep->commands];
        dispatch(new DeleteTempDirectory($submission, $tempDir, $commands));
    }
}
