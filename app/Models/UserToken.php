<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserToken extends Model
{
    use HasFactory;

    protected $table = 'user_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'expires',
    ];

    protected $casts = [
        'expires' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'usermanagementid');
    }

    public function isExpired(): bool
    {
        return $this->expires->isPast();
    }

    public function scopeValid($query)
    {
        return $query->where('expires', '>', now());
    }

    public static function cleanupExpired(): int
    {
        return static::where('expires', '<', now())->delete();
    }

    public static function createForUser(User $user, int $days = 30): self
    {
        static::where('user_id', $user->usermanagementid)->delete();

        $token = bin2hex(random_bytes(32));
        $expires = now()->addDays($days);

        return static::create([
            'user_id' => $user->usermanagementid,
            'token' => $token,
            'expires' => $expires,
        ]);
    }

    public static function findUserByToken(string $token): ?User
    {
        $userToken = static::valid()
            ->where('token', $token)
            ->with('user')
            ->first();

        return $userToken?->user;
    }
}