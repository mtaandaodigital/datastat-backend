<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Trainer extends Model
{
    use HasFactory;

    protected $table = 'trainers';
    protected $primaryKey = 'trainer_id';
    public $timestamps = false; // Your table doesn't have created_at/updated_at

    protected $fillable = [
        'firstname',
        'secondname',
        'surname',
        'trainer_title',
        'phone',
        'email',
        'academic_qualification',
        'specialization',
        'experience',
        'subject',
        'cv_path',
        'registered_time',
    ];

    // Accessors for compatibility with Phase 2 features
    public function getIdAttribute()
    {
        return $this->trainer_id;
    }

    public function getLastnameAttribute()
    {
        return $this->surname;
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstname . ' ' . $this->secondname . ' ' . $this->surname);
    }

    public function getBioAttribute(): ?string
    {
        return $this->academic_qualification . ' - ' . $this->specialization;
    }

    public function getSpecializationsStringAttribute(): string
    {
        return $this->specialization ?? 'General Training';
    }

    public function getExperienceYearsAttribute(): ?int
    {
        // Try to extract years from experience field
        if ($this->experience) {
            preg_match('/(\d+)/', $this->experience, $matches);
            return isset($matches[1]) ? (int)$matches[1] : null;
        }
        return null;
    }

    // Mock attributes for Phase 2 features
    public function getHourlyRateAttribute(): ?float
    {
        return 50.00; // Default hourly rate
    }

    public function getDailyRateAttribute(): ?float
    {
        return 400.00; // Default daily rate
    }

    public function getAvailabilityStatusAttribute(): string
    {
        return 'Available'; // Default status
    }

    public function getRatingAttribute(): ?float
    {
        return 4.5; // Default rating
    }

    public function getTotalCoursesToughtAttribute(): int
    {
        return 0; // Will be calculated from assignments
    }

    public function getTotalStudentsTrainedAttribute(): int
    {
        return 0; // Will be calculated from course registrations
    }

    public function getLanguagesStringAttribute(): string
    {
        return 'English'; // Default language
    }

    public function getCityAttribute(): ?string
    {
        return null; // Not in your current schema
    }

    public function getCountryAttribute(): ?string
    {
        return null; // Not in your current schema
    }

    public function getLinkedinProfileAttribute(): ?string
    {
        return null; // Not in your current schema
    }

    public function getWebsiteAttribute(): ?string
    {
        return null; // Not in your current schema
    }

    public function getIsActiveAttribute(): bool
    {
        return true; // Default to active
    }

    public function getCreatedAtAttribute()
    {
        return $this->registered_time;
    }

    public function getProfilePhotoAttribute(): ?string
    {
        return null; // No profile photos in current schema
    }

    // Relationships (mock since no assignment table exists)
    public function courses()
    {
        // This would need a trainer_assignments table to work properly
        return $this->belongsToMany(Course::class, 'trainer_assignments', 'trainer_id', 'course_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query; // All trainers are considered active
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query; // All trainers are considered available
    }

    public function scopeBySpecialization(Builder $query, string $specialization): Builder
    {
        return $query->where('specialization', 'LIKE', "%{$specialization}%");
    }

    public function scopeByExperience(Builder $query, int $minYears): Builder
    {
        return $query->where('experience', 'LIKE', "%{$minYears}%");
    }

    // Static methods
    public static function getAvailabilityStatuses(): array
    {
        return [
            'Available' => 'Available',
            'Busy' => 'Busy',
            'Unavailable' => 'Unavailable',
            'On Leave' => 'On Leave',
        ];
    }

    public static function getSpecializations(): array
    {
        return self::distinct('specialization')
                  ->whereNotNull('specialization')
                  ->where('specialization', '!=', '')
                  ->pluck('specialization', 'specialization')
                  ->toArray();
    }

    public static function getExperienceLevels(): array
    {
        return [
            'Junior (1-3 years)' => 'Junior (1-3 years)',
            'Mid-level (4-7 years)' => 'Mid-level (4-7 years)',
            'Senior (8-15 years)' => 'Senior (8-15 years)',
            'Expert (15+ years)' => 'Expert (15+ years)',
        ];
    }

    // Helper methods
    public function isAvailable(): bool
    {
        return true; // Default to available
    }

    public function hasCV(): bool
    {
        return !empty($this->cv_path);
    }

    public function getCVUrl(): ?string
    {
        if ($this->cv_path) {
            return asset('uploads/' . $this->cv_path);
        }
        return null;
    }

    public function getExperienceLevel(): string
    {
        $years = $this->experience_years;
        if (!$years) return 'Not Specified';
        
        if ($years <= 3) return 'Junior';
        if ($years <= 7) return 'Mid-level';
        if ($years <= 15) return 'Senior';
        return 'Expert';
    }
}