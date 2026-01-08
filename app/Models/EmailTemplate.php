<?php

namespace App\Models;

use App\Enums\EmailTemplateType;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'type',
        'subject',
        'body_html',
        'is_active',
    ];

    protected $casts = [
        'type' => EmailTemplateType::class,
        'is_active' => 'boolean',
    ];

    /**
     * Scope to retrieve active templates only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get template by type.
     */
    public static function getByType(EmailTemplateType $type)
    {
        return self::where('type', $type->value)->active()->first();
    }
}
