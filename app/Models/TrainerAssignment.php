<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainer_id',
        'course_id',
        'role',
        'start_date',
        'end_date',
        'rate_agreed',
        'payment_status',
        'payment_amount',
        'payment_date',
        'notes',
        'evaluation_score',
        'evaluation_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'rate_agreed' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'evaluation_score' => 'decimal:1',
    ];

    // Relationships
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // Static methods for options
    public static function getRoles(): array
    {
        return [
            'Lead Trainer' => 'Lead Trainer',
            'Co-Trainer' => 'Co-Trainer',
            'Assistant Trainer' => 'Assistant Trainer',
            'Guest Speaker' => 'Guest Speaker',
            'Technical Support' => 'Technical Support',
        ];
    }

    public static function getPaymentStatuses(): array
    {
        return [
            'Pending' => 'Pending',
            'Paid' => 'Paid',
            'Partial' => 'Partial',
            'Overdue' => 'Overdue',
        ];
    }
}