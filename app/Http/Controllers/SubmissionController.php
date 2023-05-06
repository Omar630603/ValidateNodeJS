<?php

namespace App\Http\Controllers;

use App\Events\AddEnvFile\AddEnvFileEvent;
use App\Events\CloneRepository\CloneRepositoryEvent;
use App\Events\CopyTestsFolder\CopyTestsFolderEvent;
use App\Events\DeleteTempDirectory\DeleteTempDirectoryEvent;
use App\Events\ExamineFolderStructure\ExamineFolderStructureEvent;
use App\Events\NpmInstall\NpmInstallEvent;
use App\Events\NpmRunStart\NpmRunStartEvent;
use App\Events\ReplacePackageJson\ReplacePackageJsonEvent;
use App\Events\UnzipZipFiles\UnzipZipFilesEvent;
use App\Models\ExecutionStep;
use App\Models\Project;
use App\Models\Submission;
use App\Models\SubmissionHistory;
use App\Models\TemporaryFile;
use Illuminate\Http\Request;
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

    public function process(Request $request, $submission_id)
    {
        $submission = Submission::find($submission_id);
        if ($submission) {
            $completion_percentage = round($submission->getTotalCompletedSteps() / $submission->getTotalSteps() * 100);
            if ($submission->status === Submission::$PENDING) {
                $submission->initializeResults();
                $submission->updateStatus(Submission::$PROCESSING);
                $currentStep = $submission->getCurrentExecutionStep();
                return response()->json([
                    'message' => 'Submission is processing',
                    'status' => $submission->status,
                    'results' => $submission->results,
                    'next_step' => $currentStep,
                    'completion_percentage' => $completion_percentage,
                ], 200);
            } else if ($submission->status === Submission::$COMPLETED) {
                return response()->json([
                    'message' => 'Submission has completed',
                    'status' => $submission->status,
                    'results' => $submission->results,
                    'completion_percentage' => $completion_percentage,
                ], 200);
            } else if ($submission->status === Submission::$FAILED) {
                return response()->json([
                    'message' => 'Submission has failed',
                    'status' => $submission->status,
                    'results' => $submission->results,
                    'completion_percentage' => $completion_percentage,
                ], 200);
            } else if ($submission->status === Submission::$PROCESSING) {
                $step = $submission->getCurrentExecutionStep();
                if ($step) {
                    if ($submission->results->{$step->executionStep->name}->status == Submission::$PENDING) {
                        if ($step->executionStep->name === ExecutionStep::$CLONE_REPOSITORY) {
                            $repoUrl = $submission->path;
                            $this->lunchCloneRepositoryEvent($submission, $repoUrl, $this->getTempDir($submission), $step);
                        } else if ($step->executionStep->name === ExecutionStep::$UNZIP_ZIP_FILES) {
                            $zipFileDir = $submission->getMedia('submissions')->first()->getPath();
                            $this->lunchUnzipZipFilesEvent($submission, $zipFileDir, $this->getTempDir($submission), $step);
                        } else if ($step->executionStep->name === ExecutionStep::$EXAMINE_FOLDER_STRUCTURE) {
                            $this->lunchExamineFolderStructureEvent($submission, $this->getTempDir($submission), $step);
                        } else if ($step->executionStep->name === ExecutionStep::$ADD_ENV_FILE) {
                            $envFile = $submission->project->getMedia('project_files')->where('file_name', '.env')->first()->getPath();
                            $this->lunchAddEnvFileEvent($submission, $envFile, $this->getTempDir($submission), $step);
                        } else if ($step->executionStep->name === ExecutionStep::$REPLACE_PACKAGE_JSON) {
                            $packageJson = $submission->project->getMedia('project_files')->where('file_name', 'package.json')->first()->getPath();
                            $this->lunchReplacePackageJsonEvent($submission, $packageJson, $this->getTempDir($submission), $step);
                        } else if ($step->executionStep->name === ExecutionStep::$COPY_TESTS_FOLDER) {
                            $testsDir = [
                                'testsDirApi' => $submission->project->getMedia('project_tests_api'),
                                'testsDirWeb' => $submission->project->getMedia('project_tests_web'),
                                'testsDirImage' => $submission->project->getMedia('project_tests_images'),
                            ];
                            $this->lunchCopyTestsFolderEvent($submission, $testsDir, $this->getTempDir($submission), $step);
                        } else if ($step->executionStep->name === ExecutionStep::$NPM_INSTALL) {
                            $this->lunchNpmInstallEvent($submission, $this->getTempDir($submission), $step);
                        } else if ($step->executionStep->name === ExecutionStep::$NPM_RUN_START) {
                            $this->lunchNpmRunStartEvent($submission, $this->getTempDir($submission), $step);
                        }
                        // else if ($step->executionStep->name === ExecutionStep::$NPM_RUN_TESTS) {
                        //     $this->lunchNpmRunTestsEvent();}
                        else if ($step->executionStep->name === ExecutionStep::$DELETE_TEMP_DIRECTORY) {
                            $this->lunchDeleteTempDirectoryEvent($submission, $this->getTempDir($submission), $step);
                        }
                        return response()->json([
                            'message' => 'Step ' . $step->executionStep->name . ' is ' . $submission->results->{$step->executionStep->name}->status,
                            'status' => $submission->status,
                            'results' => $submission->results,
                            'next_step' => $submission->getNextExecutionStep($request->step_id),
                            'completion_percentage' => $completion_percentage,
                        ], 200);
                    } else {
                        return response()->json([
                            'message' => 'Step ' . $step->executionStep->name . ' is ' . $submission->results->{$step->executionStep->name}->status,
                            'status' => $submission->status,
                            'results' => $submission->results,
                            'next_step' => $submission->getNextExecutionStep($request->step_id),
                            'completion_percentage' => $completion_percentage,
                        ], 200);
                    }
                }
                return response()->json([
                    'message' => 'Submission is processing meanwhile there is no step to execute',
                    'status' => $submission->status,
                    'results' => $submission->results,
                    'next_step' => null,
                    'completion_percentage' => $completion_percentage,
                ], 200);
            }
        }
        return response()->json([
            'message' => 'Submission not found',
        ], 404);
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
            $this->lunchDeleteTempDirectoryEvent($submission, $this->getTempDir($submission), null, $commands);
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

    private function lunchCloneRepositoryEvent($submission, $repoUrl, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ["{{repoUrl}}" => $repoUrl, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new CloneRepositoryEvent($submission, $repoUrl, $tempDir, $commands));
    }

    private function lunchUnzipZipFilesEvent($submission, $zipFileDir, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{zipFileDir}}' => $zipFileDir, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new UnzipZipFilesEvent($submission, $zipFileDir, $tempDir, $commands));
    }

    private function lunchExamineFolderStructureEvent($submission, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new ExamineFolderStructureEvent($submission, $tempDir, $commands));
    }

    private function lunchAddEnvFileEvent($submission, $envFile, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{envFile}}' => $envFile, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new AddEnvFileEvent($submission, $envFile, $tempDir, $commands));
    }

    private function lunchReplacePackageJsonEvent($submission, $packageJson, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{packageJson}}' => $packageJson, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new ReplacePackageJsonEvent($submission, $packageJson, $tempDir, $commands));
    }

    private function lunchCopyTestsFolderEvent($submission, $testsDir, $tempDir, $step)
    {
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
        event(new CopyTestsFolderEvent($submission, $testsDir, $tempDir, $commandsArray));
    }

    private function lunchNpmInstallEvent($submission, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{options}}' => " "];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new NpmInstallEvent($submission, $tempDir, $commands));
    }

    private function lunchNpmRunStartEvent($submission, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        event(new NpmRunStartEvent($submission, $tempDir, $commands));
    }

    //     private function lunchNpmRunTestsEvent()
    //     {
    //         event(new NpmRunTestsEvent());
    //     }

    private function lunchDeleteTempDirectoryEvent($submission, $tempDir, $step, $commands = null)
    {
        if ($commands == null) $commands = [$step->executionStep->commands];
        event(new DeleteTempDirectoryEvent($submission, $tempDir, $commands));
    }
}
