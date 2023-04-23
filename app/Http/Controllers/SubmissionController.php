<?php

namespace App\Http\Controllers;

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

    private function is_dir_empty($dir)
    {
        if (!is_readable($dir)) return null;
        return (count(scandir($dir)) == 2);
    }
}
