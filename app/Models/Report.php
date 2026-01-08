<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'parameters',
        'generated_by',
        'generated_at',
        'file_path',
        'file_size',
        'status',
        'error_message',
        'scheduled',
        'schedule_frequency',
        'next_run_at',
        'recipients',
    ];

    protected $casts = [
        'parameters' => 'array',
        'recipients' => 'array',
        'generated_at' => 'datetime',
        'next_run_at' => 'datetime',
        'scheduled' => 'boolean',
        'file_size' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by', 'usermanagementid');
    }

    // Scopes
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('scheduled', true);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('generated_at', '>=', now()->subDays($days));
    }

    // Static methods for options
    public static function getTypes(): array
    {
        return [
            'course_registrations' => 'Course Registrations Report',
            'financial_summary' => 'Financial Summary Report',
            'trainer_performance' => 'Trainer Performance Report',
            'lead_conversion' => 'Lead Conversion Report',
            'attendance_report' => 'Attendance Report',
            'completion_rates' => 'Course Completion Rates',
            'revenue_analysis' => 'Revenue Analysis',
            'student_feedback' => 'Student Feedback Report',
            'course_popularity' => 'Course Popularity Report',
            'payment_status' => 'Payment Status Report',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];
    }

    public static function getScheduleFrequencies(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];
    }

    // Helper methods
    public function markAsCompleted(string $filePath, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'generated_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}