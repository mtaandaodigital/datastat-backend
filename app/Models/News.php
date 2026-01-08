<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'category',
        'introduction',
        'body',
        'photo',
        'submitted_time',
        'time',
        'submited_by',
    ];

    // Automatically set submitted_by when creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($news) {
            if (empty($news->submited_by)) {
                $news->submited_by = auth()->id() ?? 0;
            }
            if (empty($news->submitted_time)) {
                $news->submitted_time = now()->toDateTimeString();
            }
        });
    }

    // Scopes
    public function scopePublished($query)
    {
        // Legacy DB does not have is_published/published_at
        // Use time column (timestamp) as proxy for recent/published content
        return $query->whereNotNull('time');
    }

    public function scopeFeatured($query)
    {
        // Legacy DB has no is_featured flag
        return $query;
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

    // Accessors (use only legacy `photo` column; no Media Library)
    public function getFeaturedImageUrlAttribute(): string
    {
        if (!empty($this->photo)) {
            // Keep legacy path convention under public/uploads
            return asset('uploads/' . ltrim($this->photo, '/'));
        }

        return asset('images/default-news.jpg');
    }

    public function getCardImageUrlAttribute(): string
    {
        if (!empty($this->photo)) {
            return asset('uploads/' . ltrim($this->photo, '/'));
        }

        return asset('images/default-news.jpg');
    }

    public function getThumbImageUrlAttribute(): string
    {
        if (!empty($this->photo)) {
            return asset('uploads/' . ltrim($this->photo, '/'));
        }

        return asset('images/default-news.jpg');
    }

    // Get available news categories
    public static function getCategories(): array
    {
        return [
            'Company News' => 'Company News',
            'Industry Updates' => 'Industry Updates',
            'Training Announcements' => 'Training Announcements',
            'Success Stories' => 'Success Stories',
            'Technology Trends' => 'Technology Trends',
            'Event Coverage' => 'Event Coverage',
            'Press Releases' => 'Press Releases',
            'General' => 'General',
        ];
    }

    // URL generation
    public function getUrlAttribute(): string
    {
        return route('news.show', $this->id);
    }

    public function getAdminUrlAttribute(): string
    {
        return route('filament.admin.resources.news.view', $this);
    }
}