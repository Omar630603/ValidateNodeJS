<?php

namespace App\Http\Controllers;

use App\Events\CloneRepository\CloneRepositoryEvent;
use App\Models\ExecutionStep;
use App\Models\Project;
use App\Models\ProjectExecutionStep;
use App\Models\Submission;
use App\Models\TemporaryFile;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        // $step = Project::find(1)->projectExecutionSteps()->where('order', 1)->first();
        // $commands = $step->executionStep->commands;
        // $step_variables = $step->variables;
        // $repoUrl = 'ok';
        // $tempDir = 'ok';
        // $values = ["{{repoUrl}}" => $repoUrl, '{{tempDir}}' => $tempDir];
        // // change the command to the actual values in array_replace function
        // // foreach ($step_variables as $variableValue) {
        // //     foreach ($commands as $commandKey => $commandValue) {
        // //         if ($commandValue === $variableValue) {
        // //             $commands[$commandKey] = $values[$variableValue];
        // //         }
        // //     }
        // // }
        // $commands = array_reduce($step_variables, function ($commands, $variableValue) use ($values) {
        //     return array_map(function ($command) use ($variableValue, $values) {
        //         return $command === $variableValue ? $values[$variableValue] : $command;
        //     }, $commands);
        // }, $step->executionStep->commands);


        // dd($commands, $step_variables);
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
            // the first stage of the submission change the status and initialize the results
            if ($submission->status === Submission::$PENDING) {
                $submission->initializeResults();
                $submission->updateStatus(Submission::$PROCESSING);
                $currentStep = $submission->getCurrentExecutionStep();
                return response()->json([
                    'message' => 'Submission is processing',
                    'status' => $submission->status,
                    'results' => $submission->results,
                    'next_step' => $currentStep,
                ], 200);
            } else if ($submission->status === Submission::$COMPLETED) {
                return response()->json([
                    'message' => 'Submission is completed',
                    'status' => $submission->status,
                    'results' => $submission->results,
                ], 200);
            } else if ($submission->status === Submission::$FAILED) {
                return response()->json([
                    'message' => 'Submission is failed',
                    'status' => $submission->status,
                    'results' => $submission->results,
                ], 500);
            } else if ($submission->status === Submission::$PROCESSING) {
                $step = $submission->getCurrentExecutionStep($request->step_id);
                if ($step) {
                    if ($step->executionStep->name === ExecutionStep::$CLONE_REPOSITORY && $submission->results->{$step->executionStep->name}->status == Submission::$PENDING) {
                        $repoUrl = $submission->path;
                        $tempDir = storage_path('app/public/tmp/submissions/' . $submission->user_id . '/' . $submission->project->title . '/' . $submission->id);
                        $this->lunchCloneRepositoryEvent($submission, $repoUrl, $tempDir, $step);
                        // } else if ($step->executionStep->name === ExecutionStep::$UNZIP_ZIP_FILES) {
                        //     $this->lunchUnzipZipFilesEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$REMOVE_ZIP_FILES) {
                        //     $this->lunchRemoveZipFilesEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$EXAMINE_FOLDER_STRUCTURE) {
                        //     $this->lunchExamineFolderStructureEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$ADD_ENV_FILE) {
                        //     $this->lunchAddEnvFileEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$REPLACE_PACKAGE_JSON) {
                        //     $this->lunchReplacePackageJsonEvent();
                        // } else if ($step->executionStep->name === ExecutionStep::$COPY_TESTS_FOLDER) {
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
                        ], 200);
                    } else {
                        return response()->json([
                            'message' => 'Step ' . $step->executionStep->name . ' is ' . $submission->results->{$step->executionStep->name}->status,
                            'status' => $submission->status,
                            'results' => $submission->results,
                            'next_step' => $step,
                        ], 200);
                    }
                }
                return response()->json([
                    'message' => 'Step not found',
                    'status' => $submission->status,
                    'results' => $submission->results,
                ], 404);
            }
        }
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
        // change the command to the actual values in array_replace function
        $commands = $this->replaceCommandArraysWithValues($step_variables, $values, $step);
        event(new CloneRepositoryEvent($submission->id, $repoUrl, $tempDir, $commands));
    }

    //     private function lunchUnzipZipFilesEvent()
    //     {
    //         event(new UnzipZipFilesEvent());
    //     }

    //     private function lunchRemoveZipFilesEvent()
    //     {
    //         event(new RemoveZipFilesEvent());
    //     }

    //     private function lunchExamineFolderStructureEvent()
    //     {
    //         event(new ExamineFolderStructureEvent());
    //     }

    //     private function lunchAddEnvFileEvent()
    //     {
    //         event(new AddEnvFileEvent());
    //     }

    //     private function lunchReplacePackageJsonEvent()
    //     {
    //         event(new ReplacePackageJsonEvent());
    //     }

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
