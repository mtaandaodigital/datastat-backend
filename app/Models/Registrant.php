<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use App\Notifications\UpcomingRegistrationReminder;

class Registrant extends Model
{
    use HasFactory;

    protected $table = 'registrants';
    protected $primaryKey = 'registrants_id';
    public $timestamps = false; // Your table doesn't have created_at/updated_at

    protected $fillable = [
        'firstname',
        'secondname',
        'surname',
        'registrant_title',
        'academic_qualification',
        'organization',
        'department',
        'country',
        'phone',
        'personal_email',
        'official_email',
        'address',
        'mode_of_payment',
        'accommodation',
        'airport_pickup',
        'supervisor',
        'supervisor_telephone',
        'supervisor_email',
        'expectations',
        'additional_area',
        'comment',
        'how_you_heard',
        'learning_model',
        'title_course',
        'course_id',
        'invoice_no',
        'registrant_no',
        'total_amount',
        'registered_time',
        'schedule_id',
    ];

    protected $casts = [
        // Remove automatic decimal casting to handle invalid values gracefully
        // 'total_amount' => 'decimal:2',
    ];

    // Accessors for compatibility with Phase 2 features
    public function getRegistrantIdAttribute()
    {
        return $this->registrants_id;
    }

    public function getLastnameAttribute()
    {
        return $this->surname;
    }

    public function getEmailAttribute()
    {
        return $this->personal_email;
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstname . ' ' . $this->secondname . ' ' . $this->surname);
    }

    public function getRegistrationDateAttribute()
    {
        return $this->registered_time;
    }

    // Mock payment status based on existing data
    public function getPaymentStatusAttribute(): string
    {
        // Handle invalid decimal values gracefully
        $amount = $this->getTotalAmountAsDecimal();
        if ($amount && $amount > 0) {
            return 'Paid';
        }
        return 'Pending';
    }

    public function getPaymentMethodAttribute(): string
    {
        return $this->mode_of_payment ?? 'Not Specified';
    }

    public function getFinalAmountAttribute()
    {
        return $this->getTotalAmountAsDecimal();
    }

    public function getAmountPaidAttribute()
    {
        return $this->getTotalAmountAsDecimal();
    }

    /**
     * Safely convert total_amount to decimal, handling invalid values
     */
    public function getTotalAmountAsDecimal(): ?float
    {
        if (is_null($this->total_amount) || $this->total_amount === '') {
            return null;
        }

        // Clean the value and attempt conversion
        $cleanValue = trim((string) $this->total_amount);
        
        // Remove any non-numeric characters except decimal point and minus sign
        $cleanValue = preg_replace('/[^0-9.-]/', '', $cleanValue);
        
        // Check if it's a valid numeric value
        if (is_numeric($cleanValue)) {
            return round((float) $cleanValue, 2);
        }
        
        return null;
    }

    // Mock attendance and completion status
    public function getAttendanceStatusAttribute(): string
    {
        return 'Present'; // Default for existing registrants
    }

    public function getCompletionStatusAttribute(): string
    {
        return 'Completed'; // Default for existing registrants
    }

    public function getCertificateIssuedAttribute(): bool
    {
        return true; // Default for existing registrants
    }

    public function getRatingAttribute(): ?float
    {
        return 4.5; // Default rating
    }

    public function getRegistrationSourceAttribute(): string
    {
        return $this->how_you_heard ?? 'Website';
    }

    public function getSpecialRequirementsAttribute(): ?string
    {
        return $this->comment;
    }

    public function getPositionAttribute(): ?string
    {
        return $this->registrant_title;
    }

    // Relationships
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'personal_email', 'email');
    }

    // Scopes
    public function scopePaid(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->where('total_amount', '>', 0)
              ->whereNotNull('total_amount')
              ->where('total_amount', '!=', '')
              ->whereRaw('total_amount REGEXP \'^[0-9]+(\.[0-9]+)?$\'');
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNull('total_amount')
              ->orWhere('total_amount', '<=', 0)
              ->orWhere('total_amount', '=', '')
              ->orWhereRaw('total_amount NOT REGEXP \'^[0-9]+(\.[0-9]+)?$\'');
        });
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->where('total_amount', '>', 0)
              ->whereNotNull('total_amount')
              ->where('total_amount', '!=', '')
              ->whereRaw('total_amount REGEXP \'^[0-9]+(\.[0-9]+)?$\'');
        });
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('registered_time', '>=', now()->subDays($days)->format('Y-m-d H:i:s'));
    }

    public function scopeByPaymentMethod(Builder $query, string $method): Builder
    {
        return $query->where('mode_of_payment', $method);
    }

    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    public function scopeByOrganization(Builder $query, string $organization): Builder
    {
        return $query->where('organization', 'LIKE', "%{$organization}%");
    }

    // Static methods
    public static function getPaymentStatuses(): array
    {
        return [
            'Paid' => 'Paid',
            'Pending' => 'Pending',
            'Failed' => 'Failed',
            'Refunded' => 'Refunded',
        ];
    }

    public static function getPaymentMethods(): array
    {
        return [
            'Bank Transfer' => 'Bank Transfer',
            'Credit Card' => 'Credit Card',
            'Mobile Money' => 'Mobile Money',
            'Cash' => 'Cash',
            'Cheque' => 'Cheque',
            'Other' => 'Other',
        ];
    }

    public static function getAttendanceStatuses(): array
    {
        return [
            'Present' => 'Present',
            'Absent' => 'Absent',
            'Partial' => 'Partial',
            'Excused' => 'Excused',
        ];
    }

    public static function getCompletionStatuses(): array
    {
        return [
            'Completed' => 'Completed',
            'In Progress' => 'In Progress',
            'Dropped Out' => 'Dropped Out',
            'Failed' => 'Failed',
        ];
    }

    public static function getRegistrationSources(): array
    {
        return [
            'Website' => 'Website',
            'Email Campaign' => 'Email Campaign',
            'Social Media' => 'Social Media',
            'Referral' => 'Referral',
            'Phone Call' => 'Phone Call',
            'Walk-in' => 'Walk-in',
            'Partner' => 'Partner',
            'Other' => 'Other',
        ];
    }

    // Helper methods
    public function isPaid(): bool
    {
        $amount = $this->getTotalAmountAsDecimal();
        return $amount && $amount > 0;
    }

    public function isCompleted(): bool
    {
        return $this->isPaid(); // Assume paid registrants completed
    }

    public function hasCertificate(): bool
    {
        return $this->isCompleted();
    }

    public function needsAccommodation(): bool
    {
        return strtolower($this->accommodation) === 'yes';
    }

    public function needsAirportPickup(): bool
    {
        return strtolower($this->airport_pickup) === 'yes';
    }

    public function getReminderEmail(): ?string
    {
        return $this->official_email ?: $this->personal_email;
    }

    // Action methods for Filament Resource
    public function markAsPaid(float $amount, string $method, ?string $reference = null): void
    {
        $this->update([
            'total_amount' => $amount,
            'mode_of_payment' => $method,
        ]);
    }

    public function markAsPresent(): void
    {
        // Since we don't have an attendance field in the database,
        // this is a placeholder method for the UI action
        // You might want to add an attendance field to the database
    }

    public function markAsCompleted(): void
    {
        // Since we don't have a completion field in the database,
        // this is a placeholder method for the UI action
        // You might want to add a completion field to the database
    }

    public function sendReminder(?string $customMessage = null): void
    {
        $email = $this->getReminderEmail();

        if (!$email) {
            return;
        }

        Notification::route('mail', $email)
            ->notify(new UpcomingRegistrationReminder($this, $customMessage));
    }
}