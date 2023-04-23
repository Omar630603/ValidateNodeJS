<?php

namespace App\Http\Controllers;

use App\Events\CloneRepo\CloneRepoEvent;
use App\Models\Project;
use App\Models\Submission;
use App\Models\TemporaryFile;
use Illuminate\Http\Request;

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
            return view('submissions.show', compact('submission'));
        }
        return redirect()->route('submissions');
    }

    public function process(Request $request, $submission_id)
    {
        $submission = Submission::find($submission_id);
        if ($submission) {
            if ($submission->isGithubUrl()) {
                $repoUrl = $submission->path;
                $tempDir = storage_path('app/public/tmp/submissions/repo/' . $request->user()->id . '/' . $submission->id);

                event(new CloneRepoEvent($submission->id, $repoUrl, $tempDir));

                // Return a response to the user indicating that the cloning process has started
                return response()->json([
                    'message' => 'Cloning process has started',
                ], 200);
            } else {
                // Extract the zip file
                // Dispatch the next event (e.g. RunTestsEvent)
            }
        }
    }

    private function is_dir_empty($dir)
    {
        if (!is_readable($dir)) return null;
        return (count(scandir($dir)) == 2);
    }
}
