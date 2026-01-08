<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEvent extends Model
{
    protected $table = 'course_event';

    protected $fillable = [
        'title',
        'category',
        'start_date',
        'start',
        'end',
        'cut_of_date',
        'no_of_days',
        'introduction',
        'body',
        'course_status',
        'schedule_status',
        'location',
        'software',
        'fee_kes',
        'fee_euro',
        'fee_usd',
        'fee_pound',
        'image_path',
        'submitted_time',
        'clean_introduction',
        'clean_body',
        'meta_description',
        'meta_keywords'
    ];

    protected $casts = [
        'start' => 'date',
        'submitted_time' => 'datetime',
        'schedule_status' => 'integer'
    ];

    public function careerSkills()
    {
        return $this->hasMany(CareerSkill::class, 'skill', 'software');
    }
}