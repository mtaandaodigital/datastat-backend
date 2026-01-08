<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $table = 'course_event';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public function schedules()
    {
        return $this->hasMany(\App\Models\Schedule::class, 'course_id', 'id');
    }

    protected $fillable = [
        'title',
        'category',
        'start_date', // UNIX timestamp (string/int)
        'cut_of_date',
        'no_of_days',
        'introduction',
        'body',
        'course_status',
        'location',
        'software',
        'image_path',
        'submitted_time',
        'schedule_status',
        'fee_kes',
        'fee_usd',
        'fee_euro',
        'fee_pound',
        'meta_description',
        'meta_keywords',
        'start',
        'end',
        'id'
    ];

    protected $casts = [
        // Note: start_date, cut_of_date, end are VARCHAR fields, not dates
        'submitted_time' => 'datetime',
        'no_of_days' => 'integer',
        'schedule_status' => 'boolean',
        'fee_kes' => 'decimal:2',
        'fee_usd' => 'decimal:2',
        'fee_euro' => 'decimal:2',
        'fee_pound' => 'decimal:2',
        'start' => 'date', // This is the actual DATE field in database
    ];

    // Pricing helper
    public function getFormattedPriceAttribute(): string
    {
        $prices = [];
        if (!empty($this->fee_kes)) {
            $prices[] = 'KES ' . number_format((float) $this->fee_kes, 2);
        }
        if (!empty($this->fee_usd)) {
            $prices[] = '$' . number_format((float) $this->fee_usd, 2);
        }
        if (!empty($this->fee_euro)) {
            $prices[] = '€' . number_format((float) $this->fee_euro, 2);
        }
        if (!empty($this->fee_pound)) {
            $prices[] = '£' . number_format((float) $this->fee_pound, 2);
        }
        return implode(' | ', $prices);
    }

    public function isActive(): bool
    {
        return $this->schedule_status == 1;
    }

    public function scopeActive($query)
    {
        return $query->where('schedule_status', 1);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('introduction', 'LIKE', "%{$search}%")
                    ->orWhere('body', 'LIKE', "%{$search}%");
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path) {
            // Check if it's a full path or just filename
            if (str_starts_with($this->image_path, 'http')) {
                return $this->image_path;
            }

            $filename = basename($this->image_path);

            // New public path for course images
            return 'https://datastatresearch.org/images/courses/' . $filename;
        }

        // Default image path in the new images/courses directory
        return 'https://datastatresearch.org/images/courses/default-course.jpg';
    }

    public function getCourseImagesAttribute(): ?string
    {
        return $this->getImageUrlAttribute();
    }
    
    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }

    // Generate filename based on course title
    public function generateImageFilename(string $originalExtension): string
    {
        $slug = \Illuminate\Support\Str::slug($this->title);
        $suffix = '-by-datastat-training-institute';
        return $slug . $suffix . '.' . $originalExtension;
    }

    // Mutator to handle image path storage
    public function setImagePathAttribute($value)
    {
        // If value is null and we're updating (not creating a new record),
        // keep the existing image path
        if (is_null($value) && $this->exists && !empty($this->attributes['image_path'])) {
            // Do nothing, keep the existing value
            return;
        }
        
        if (is_array($value) && !empty($value)) {
            // If it's an array (from file upload), get the first item and extract filename
            $filename = $value[0];
            $this->attributes['image_path'] = basename($filename);
        } elseif (is_string($value) && !empty($value)) {
            // If it's a string, extract just the filename
            $this->attributes['image_path'] = basename($value);
        } else {
            // Only set to null if explicitly provided as empty
            $this->attributes['image_path'] = null;
        }
    }

    public function getDurationTextAttribute(): string
    {
        if ($this->no_of_days == 1) {
            return '1 Day';
        }

        return $this->no_of_days . ' Days';
    }

    // Get available course categories
    public static function getCategories(): array
    {
        return [
            'Information Technology' => 'Information Technology',
            'Project Management' => 'Project Management',
            'Finance & Accounting' => 'Finance & Accounting',
            'Human Resources' => 'Human Resources',
            'Marketing & Sales' => 'Marketing & Sales',
            'Operations Management' => 'Operations Management',
            'Leadership & Management' => 'Leadership & Management',
            'Data Analysis' => 'Data Analysis',
            'Quality Management' => 'Quality Management',
            'Other' => 'Other',
        ];
    }

    // Get course status options
    public static function getCourseStatuses(): array
    {
        return [
            'Top' => 'Top',
            'Featured' => 'Featured',
            'Latest' => 'Latest',
        ];
    }

    // Additional relationships for Phase 2
    public function registrants()
    {
        return $this->hasMany(Registrant::class, 'course_id', 'id');
    }

    // Mock trainer relationship since no assignment table exists
    public function trainers()
    {
        return $this->belongsToMany(Trainer::class, 'trainer_assignments', 'course_id', 'trainer_id');
    }

    // Additional scopes and methods
    public function scopeWithRegistrants(Builder $query): Builder
    {
        return $query->withCount('registrants');
    }

    public function getRegistrationCountAttribute(): int
    {
        return $this->registrants()->count();
    }

    public function getPaidRegistrationsCountAttribute(): int
    {
        return $this->registrants()->paid()->count();
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->registrants()->paid()->sum('total_amount') ?? 0;
    }

    public function getCompletionRateAttribute(): float
    {
        $total = $this->registrants()->count();
        if ($total === 0) return 0;

        $completed = $this->registrants()->completed()->count();
        return round(($completed / $total) * 100, 1);
    }

    public function isFullyBooked(int $maxCapacity = 30): bool
    {
        return $this->registrants()->paid()->count() >= $maxCapacity;
    }
}