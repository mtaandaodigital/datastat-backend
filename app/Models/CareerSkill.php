<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerSkill extends Model
{
    protected $fillable = [
        'career_id',
        'skill',
    ];

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }

    // Helper method to get related courses
    public function getCourses()
    {
        return CourseEvent::where('software', $this->skill)->get();
    }
}