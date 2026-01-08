<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usermanagement';
    protected $primaryKey = 'usermanagementid';
    public $timestamps = false;

    protected $fillable = [
        'firstname',
        'secondname',
        'surname',
        'telephone',
        'email',
        'password',
        'location',
        'login_active_status',
        'accesslevel',
        'activation_status',
        'addtime',
        'user_level',
        'remember_token',
        'created_userid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'login_active_status' => 'boolean',
        'accesslevel' => 'integer',
        'user_level' => 'integer',
        'activation_status' => 'integer',
        'addtime' => 'date',
    ];

    // Accessors for compatibility
    public function getNameAttribute(): string
    {
        return trim($this->firstname . ' ' . $this->secondname . ' ' . $this->surname);
    }

    public function getLastnameAttribute(): string
    {
        return $this->surname;
    }

    public function getPhoneAttribute(): string
    {
        return $this->telephone;
    }

    public function getOrganizationAttribute(): string
    {
        return $this->location; // Using location as organization
    }

    public function getCreatedAtAttribute()
    {
        return $this->addtime;
    }

    public function getUpdatedAtAttribute()
    {
        return $this->addtime;
    }

    // Filament authentication
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function isActive(): bool
    {
        // Check if user is active and activated
        // login_active_status: 1 = active, 0 = inactive
        // activation_status: 1 = activated, 0 = not activated
        return $this->login_active_status == 1 && $this->activation_status == 1;
    }

    public function isAdmin(): bool
    {
        return $this->accesslevel == 1;
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_level == 1;
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstname . ' ' . $this->surname);
    }

    public function userTokens(): HasMany
    {
        return $this->hasMany(UserToken::class, 'user_id', 'usermanagementid');
    }


    public function validatePassword(string $password): bool
    {
        // Handle null passwords
        if (is_null($this->password)) {
            return false;
        }
        
        // Since you want to keep using MD5, validate against MD5
        return $this->password === md5($password);
    }
    


    // Return the primary key column name used for session identification
    public function getAuthIdentifierName()
    {
        return 'usermanagementid';
    }

    public function getAuthIdentifier()
    {
        return $this->usermanagementid;
    }
}