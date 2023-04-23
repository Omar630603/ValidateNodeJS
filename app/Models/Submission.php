<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Submission extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    static $tyeps = ['file', 'url'];
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
}
