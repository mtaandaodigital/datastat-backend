<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'course_schedule';
    protected $primaryKey = 'schedule_id';
    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'start',
        'end',
        'start_data',
        'location',
        'course_fee_usd',
        'course_fee_ksh',
    ];
    
    // Do not cast to date to avoid automatic time serialization
    protected $casts = [
        // Keeping empty; start/end are stored as plain strings (Y-m-d)
    ];

    // Normalize date inputs to Y-m-d when setting attributes
    public function setStartAttribute($value)
    {
        $this->attributes['start'] = $this->normalizeDateToYmd($value);
    }

    public function setEndAttribute($value)
    {
        $this->attributes['end'] = $this->normalizeDateToYmd($value);
    }

    // Always return Y-m-d strings when accessing
    public function getStartAttribute($value)
    {
        return $this->normalizeDateToYmd($value);
    }

    public function getEndAttribute($value)
    {
        return $this->normalizeDateToYmd($value);
    }

    protected function normalizeDateToYmd($value): string
    {
        try {
            if ($value instanceof \DateTimeInterface) {
                return \Carbon\Carbon::instance($value)->format('Y-m-d');
            }
            if (is_numeric($value)) {
                return \Carbon\Carbon::createFromTimestamp((int) $value)->format('Y-m-d');
            }
            if (is_string($value) && $value !== '') {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            // fall through and return as string
        }
        return (string) $value;
    }

    protected static function booted()
    {
        static::saving(function (self $schedule) {
            if (empty($schedule->start_data) && !empty($schedule->start)) {
                $timestamp = is_string($schedule->start)
                    ? strtotime($schedule->start)
                    : strtotime((string) $schedule->start);
                $schedule->start_data = (string) ($timestamp ?: time());
            }
        });
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
}

