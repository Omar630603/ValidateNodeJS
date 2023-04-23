<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\Datatables;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $projects = Project::skip(0)->take(3)->get();
        if ($request->ajax()) {
            $data = DB::table('projects')
                ->select('projects.id', 'projects.title', DB::raw('COUNT(submissions.id) as submission_count'))
                ->leftJoin('submissions', function ($join) use ($user) {
                    $join->on('projects.id', '=', 'submissions.project_id')
                        ->where('submissions.user_id', '=', $user->id);
                })
                ->groupBy('projects.id');


            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('title', function ($row) {
                    $title_button = '<a href="/submissions/project/' . $row->id . '" class="underline text-secondary">' . $row->title . '</a>';
                    return $title_button;
                })
                ->rawColumns(['title'])
                ->make(true);
        }
        return view('dashboard.index', compact('projects'));
    }
}
