<?php

namespace App\Http\Controllers;

use Exception;
use ZipArchive;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::all();
        return view('projects.index', compact('projects'));
    }

    public function show(Request $request, $project_id)
    {
        $project = Project::find($project_id);
        if (!$project) {
            return redirect()->route('projects');
        }
        return view('projects.show', compact('project'));
    }

    public function showPDF(Request $request)
    {
        if ($request->ajax()) {
            if ($request->id) {
                $media = Media::find($request->id);
                if ($media) {
                    $path = $media->getUrl();
                    return response()->json($path, 200);
                }
                return response()->json(["message" => "media not found"], 404);
            }
            return response()->json(["message" => "no media was requested"], 400);
        }
    }

    public function download(Request $request, $project_id)
    {
        if ($request->ajax()) {
            $project = Project::find($project_id);
            if (!$project) {
                return response()->json(["message" => "project not found"], 404);
            }
            if ($request->type) {
                switch ($request->type) {
                    case 'guides':
                        // $zipMedia = $project->getMedia('project_zips')->where('file_name', 'guides.zip')->first();
                        // if ($zipMedia) {
                        //     return response()->json($zipMedia->getUrl(), 200);
                        // } else if (is_file(storage_path('app/public/assets/projects/' . $project->title . '/zips/guides.zip'))) {
                        //     return response()->json(asset('/projects/api-experiment/' . $project->title . '/zips/guides.zip'), 200);
                        // } else {
                        //     $guides = $project->getMedia('project_guides');
                        //     $tempDir = storage_path('app/public/assets/projects/' . $project->title . '/zips');
                        //     if (!is_dir($tempDir)) mkdir($tempDir);
                        //     foreach ($guides as $guide) {
                        //         $path = $guide->getPath();
                        //         $filename = $guide->file_name;
                        //         copy($path, $tempDir . '/' . $filename);
                        //     }
                        //     $zipPath = $tempDir . '/guides.zip';
                        //     $zip = new ZipArchive;
                        //     if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                        //         $files = Storage::files('public/assets/projects/' . $project->title . '/zips');
                        //         foreach ($files as $file) {
                        //             $zip->addFile(storage_path('app/' . $file), basename($file));
                        //         }
                        //         $zip->close();
                        //     } else {
                        //         throw new Exception('Failed to create zip archive');
                        //     }
                        //     $media = $project->addMedia($zipPath)->toMediaCollection('project_zips');
                        //     return response()->json($media->getUrl(), 200);
                        // }
                        break;
                    case 'supplements':
                        # code...
                        break;
                    case 'tests':
                        # code...
                        break;
                }
            }
            return response()->json(["message" => "no type was requested"], 400);
        }
    }
}
