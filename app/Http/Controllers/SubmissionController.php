<?php

namespace App\Http\Controllers;

use App\Events\AddEnvFile\AddEnvFileEvent;
use App\Events\CloneRepository\CloneRepositoryEvent;
use App\Events\ExamineFolderStructure\ExamineFolderStructureEvent;
use App\Events\ReplacePackageJson\ReplacePackageJsonEvent;
use App\Events\UnzipZipFiles\UnzipZipFilesEvent;
use App\Models\ExecutionStep;
use App\Models\Project;
use App\Models\ProjectExecutionStep;
use App\Models\Submission;
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
                            $tempDir = $this->getTempDir($submission);
                            $this->lunchCloneRepositoryEvent($submission, $repoUrl, $tempDir, $step);
                        } else if ($step->executionStep->name === ExecutionStep::$UNZIP_ZIP_FILES) {
                            $zipFileDir = $submission->getMedia('submissions')->first()->getPath();
                            $tempDir = $this->getTempDir($submission);
                            $this->lunchUnzipZipFilesEvent($submission, $zipFileDir, $tempDir, $step);
                        } else if ($step->executionStep->name === ExecutionStep::$EXAMINE_FOLDER_STRUCTURE) {
                            $tempDir = $this->getTempDir($submission);
                            $this->lunchExamineFolderStructureEvent($submission, $tempDir, $step);
                        } else if ($step->executionStep->name === ExecutionStep::$ADD_ENV_FILE) {
                            // "{{envFile}}", "{{tempDir}}"
                            $tempDir = $this->getTempDir($submission);
                            $envFile = $submission->project->getMedia('project_files')->where('file_name', '.env')->first()->getPath();
                            $this->lunchAddEnvFileEvent($submission, $tempDir, $envFile, $step);
                        } else if ($step->executionStep->name === ExecutionStep::$REPLACE_PACKAGE_JSON) {
                            $tempDir = $this->getTempDir($submission);
                            $packageJson = $submission->project->getMedia('project_files')->where('file_name', 'package.json')->first()->getPath();
                            $this->lunchReplacePackageJsonEvent($submission, $tempDir, $packageJson, $step);
                        }
                        // else if ($step->executionStep->name === ExecutionStep::$COPY_TESTS_FOLDER) {
                        //     $this->lunchCopyTestsFolderEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$NPM_INSTALL) {
                        //     $this->lunchNpmInstallEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$NPM_RUN_BUILD) {
                        //     $this->lunchNpmRunBuildEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$NPM_RUN_TESTS) {
                        //     $this->lunchNpmRunTestsEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$DELETE_TEMP_DIRECTORY) {
                        //     $this->lunchDeleteTempDirectoryEvent();
                    }
                    if ($submission->results->{$step->executionStep->name}->status == Submission::$COMPLETED) {
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
                            'next_step' => $step,
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
    }

    public function refresh(Request $request, $submission_id)
    {
        $submission = Submission::find($submission_id);
        if ($submission) {
            $submission->updateStatus(Submission::$PENDING);
            $submission->initializeResults();
            Process::fromShellCommandline('rm -rf ' . $this->getTempDir($submission))->run();
            return response()->json([
                'message' => 'Submission has been refreshed',
                'status' => $submission->status,
                'results' => $submission->results,
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
        event(new CloneRepositoryEvent($submission->id, $repoUrl, $tempDir, $commands));
    }

    private function lunchUnzipZipFilesEvent($submission, $zipFileDir, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{zipFileDir}}' => $zipFileDir, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new UnzipZipFilesEvent($submission->id, $zipFileDir, $tempDir, $commands));
    }

    private function lunchExamineFolderStructureEvent($submission, $tempDir, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new ExamineFolderStructureEvent($submission->id, $tempDir, $commands));
    }

    private function lunchAddEnvFileEvent($submission, $tempDir, $envFile, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{envFile}}' => $envFile, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new AddEnvFileEvent($submission->id, $envFile, $tempDir, $commands));
    }

    private function lunchReplacePackageJsonEvent($submission, $tempDir, $packageJson, $step)
    {
        $commands = $step->executionStep->commands;
        $step_variables = $step->variables;
        $values = ['{{packageJson}}' => $packageJson, '{{tempDir}}' => $tempDir];
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new ReplacePackageJsonEvent($submission->id, $packageJson, $tempDir, $commands));
    }

    //     private function lunchCopyTestsFolderEvent()
    //     {
    //         event(new CopyTestsFolderEvent());
    //     }

    //     private function lunchNpmInstallEvent()
    //     {
    //         event(new NpmInstallEvent());
    //     }

    //     private function lunchNpmRunBuildEvent()
    //     {
    //         event(new NpmRunBuildEvent());
    //     }

    //     private function lunchNpmRunTestsEvent()
    //     {
    //         event(new NpmRunTestsEvent());
    //     }

    //     private function lunchDeleteTempDirectoryEvent()
    //     {
    //         event(new DeleteTempDirectoryEvent());
    //     }
}
