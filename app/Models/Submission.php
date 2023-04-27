<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Submission extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    static $types = ['file', 'url'];
    static $statues =  ['pending', 'processing', 'completed', 'failed'];
    static $FILE = 'file';
    static $URL = 'url';
    static $PENDING = 'pending';
    static $PROCESSING = 'processing';
    static $COMPLETED = 'completed';
    static $FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'project_id',
        'type',
        'path',
        'status',
        'results',
    ];

    protected $casts = [
        'results' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function getResultsAttribute($value)
    {
        return json_decode($value);
    }

    public function setResultsAttribute($value)
    {
        $this->attributes['results'] = json_encode($value);
    }

    public function getFileAttribute()
    {
        return $this->getFirstMediaUrl('public_submissions_files');
    }

    public function isGithubUrl()
    {
        return $this->type == self::$URL;
    }

    public function getExecutionSteps()
    {
        if ($this->isGithubUrl()) {
            $steps = $this->project->projectExecutionSteps->filter(function ($step) {
                return $step->executionStep->name != 'Unzip ZIP Files' && $step->executionStep->name != 'Remove ZIP Files';
            });
        } else {
            $steps = $this->project->projectExecutionSteps->filter(function ($step) {
                return $step->executionStep->name != 'Clone Repository';
            });
        }
        // order the steps by their order
        $steps = $steps->sortBy('order');
        return $steps;
    }

    public function initializeResults()
    {
        $results = [];
        $steps = $this->getExecutionSteps();
        foreach ($steps as $step) {
            $results[$step->executionStep->name] = [
                'stepID' => $step->id,
                'status' => self::$PENDING,
                'output' => '',
            ];
        }
        $this->updateResults($results);
    }

    public function updateStatus($status)
    {
        $this->status = $status;
        $this->save();
    }

    public function updateOneResult($step_name, $status, $output)
    {
        $results = $this->results;
        $results->$step_name->status = $status;
        $results->$step_name->output = $output;
        $this->updateResults($results);
    }

    public function updateResults($results)
    {
        $this->results = $results;
        $this->save();
    }

    public function getCurrentExecutionStep($step_id = null)
    {
        $steps = $this->getExecutionSteps();
        if ($step_id) {
            $current_step = $steps->first(function ($step) use ($step_id) {
                return $step->id == $step_id;
            });
            return $current_step;
        } else {
            $results = $this->results;
            $current_step = null;
            if (!$results) {
                return $current_step;
            }
            foreach ($steps as $step) {
                if ($results->{$step->executionStep->name}?->status == self::$PROCESSING || $results->{$step->executionStep->name}?->status == self::$PENDING) {
                    $current_step = $step;
                    break;
                }
            }
            return $current_step;
        }
    }

    public function getNextExecutionStep($step_id)
    {
        $current_step = $this->getCurrentExecutionStep($step_id);
        // get the next step with the bigger order number
        $next_step = $this->getExecutionSteps()->first(function ($step) use ($current_step) {
            return $step->order > $current_step->order;
        });
        return $next_step;
    }
}