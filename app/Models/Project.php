<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'tech_stack',
        'github_url',
        'image',
    ];

    protected $casts = [
        'tech_stack' => 'array',
    ];

    public function getTechStackAttribute($value): array
    {
        return json_decode($value, true);
    }

    public function setTechStackAttribute($value): void
    {
        $this->attributes['tech_stack'] = json_encode($value);
    }
}
