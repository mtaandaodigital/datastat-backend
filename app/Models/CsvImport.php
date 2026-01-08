<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsvImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_filename',
        'type',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'errors',
        'mapping',
        'user_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'mapping' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'usermanagementid');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) return 0;
        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->processed_rows === 0) return 0;
        return round(($this->successful_rows / $this->processed_rows) * 100, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'processing' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) return null;
        
        $endTime = $this->completed_at ?? now();
        $duration = $this->started_at->diffInSeconds($endTime);
        
        if ($duration < 60) {
            return $duration . ' seconds';
        } elseif ($duration < 3600) {
            return round($duration / 60, 1) . ' minutes';
        } else {
            return round($duration / 3600, 1) . ' hours';
        }
    }

    public function getFilePathAttribute(): string
    {
        return storage_path('app/csv-imports/' . $this->filename);
    }

    // Methods
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(array $errors = []): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'errors' => $errors,
        ]);
    }

    public function updateProgress(int $processed, int $successful, int $failed, array $errors = []): void
    {
        $this->update([
            'processed_rows' => $processed,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'errors' => $errors,
        ]);
    }

    public function addError(string $row, string $error): void
    {
        $errors = $this->errors ?? [];
        $errors[] = [
            'row' => $row,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ];
        
        $this->update(['errors' => $errors]);
    }

    // Static methods
    public static function getTypes(): array
    {
        return [
            'courses' => 'Courses',
            'users' => 'Users',
            'leads' => 'Leads',
            'news' => 'News',
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

    public static function getRecentStats(): array
    {
        $recent = static::recent()->get();
        
        return [
            'total' => $recent->count(),
            'completed' => $recent->where('status', 'completed')->count(),
            'failed' => $recent->where('status', 'failed')->count(),
            'processing' => $recent->where('status', 'processing')->count(),
            'success_rate' => $recent->count() > 0 
                ? round($recent->where('status', 'completed')->count() / $recent->count() * 100, 2)
                : 0,
        ];
    }
}