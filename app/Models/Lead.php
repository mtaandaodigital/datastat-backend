<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'interest',
        'source',
        'status',
        'notes',
        'funnel_version',
    ];

    protected $casts = [
        // No special casts needed for current DB schema
    ];

    // Relationships
    // (None currently; schema has no assigned_to FK)

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
    }

    public function scopeRecentlyCreated($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'Converted');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['New', 'Contacted', 'Qualified']);
    }

    // Accessors & Mutators
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'New' => 'primary',
            'Contacted' => 'info',
            'Qualified' => 'warning',
            'Converted' => 'success',
            'Lost' => 'danger',
            default => 'gray',
        };
    }

    public function getSourceColorAttribute(): string
    {
        return match ($this->source) {
            'Landing Page' => 'success',
            'Website' => 'primary',
            'Referral' => 'warning',
            'Social Media' => 'info',
            'Email Campaign' => 'purple',
            'Other' => 'gray',
            default => 'gray',
        };
    }

    public function getDaysSinceCreatedAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    // Removed contacted/converted accessors - columns not present in DB

    // Methods
    public function markAsContacted(): void
    {
        $this->update([
            'status' => 'Contacted',
        ]);
    }

    public function markAsConverted(): void
    {
        $this->update([
            'status' => 'Converted',
        ]);
    }

    public function addNote(string $note): void
    {
        $existingNotes = $this->notes ? $this->notes . "\n\n" : '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $user = auth()->user()->full_name ?? 'System';
        
        $this->update([
            'notes' => $existingNotes . "[{$timestamp}] {$user}: {$note}"
        ]);
    }

    // Static methods for statistics
    public static function getConversionRate(): float
    {
        $total = static::count();
        if ($total === 0) return 0;
        
        $converted = static::converted()->count();
        return round(($converted / $total) * 100, 2);
    }

    public static function getSourceStats(): array
    {
        return static::selectRaw('source, COUNT(*) as count')
                    ->groupBy('source')
                    ->pluck('count', 'source')
                    ->toArray();
    }

    public static function getStatusStats(): array
    {
        return static::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();
    }

    public static function getMonthlyStats(): array
    {
        return static::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subMonths(12))
                    ->groupBy('month')
                    ->orderBy('month')
                    ->pluck('count', 'month')
                    ->toArray();
    }

    // Available options
    public static function getStatuses(): array
    {
        return [
            'New' => 'New',
            'Contacted' => 'Contacted',
            'Qualified' => 'Qualified',
            'Converted' => 'Converted',
            'Lost' => 'Lost',
        ];
    }

    public static function getSources(): array
    {
        return [
            'Landing Page' => 'Landing Page',
            'Website' => 'Website',
            'Referral' => 'Referral',
            'Social Media' => 'Social Media',
            'Email Campaign' => 'Email Campaign',
            'Other' => 'Other',
        ];
    }
}