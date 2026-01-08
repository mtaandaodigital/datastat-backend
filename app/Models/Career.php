<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Career extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'image_path',
        'meta_description',
        'meta_keywords',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($career) {
            if (!$career->slug) {
                $career->slug = Str::slug($career->title);
            }
        });
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CareerSkill::class);
    }

    // Helper method to get all related courses through skills
    public function getCourses()
    {
        $skills = $this->skills->pluck('skill')->toArray();
        return CourseEvent::whereIn('software', $skills)->get();
    }
}