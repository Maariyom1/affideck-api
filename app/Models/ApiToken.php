<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'token_hash',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function ($activeQuery): void {
                $activeQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function revoke(): void
    {
        if ($this->revoked_at === null) {
            $this->forceFill(['revoked_at' => now()])->save();
        }
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function issuePair(User $user, string $name = 'affideck-web', ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $accessToken = 'atk_'.Str::random(48);
        $refreshToken = 'rtk_'.Str::random(64);

        $accessRecord = static::query()->create([
            'user_id' => $user->getKey(),
            'name' => $name,
            'type' => 'access',
            'token_hash' => static::hashToken($accessToken),
            'expires_at' => now()->addMinutes(15),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        static::query()->create([
            'user_id' => $user->getKey(),
            'name' => $name,
            'type' => 'refresh',
            'token_hash' => static::hashToken($refreshToken),
            'expires_at' => now()->addDays(30),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
            'access_token_id' => $accessRecord->getKey(),
        ];
    }

    public static function findByPlainText(string $token, string $type): ?self
    {
        return static::query()
            ->with('user')
            ->active()
            ->where('type', $type)
            ->where('token_hash', static::hashToken($token))
            ->first();
    }

    public static function rotateRefreshToken(string $refreshToken, ?string $ipAddress = null, ?string $userAgent = null): ?array
    {
        $currentRefreshToken = static::findByPlainText($refreshToken, 'refresh');

        if ($currentRefreshToken === null) {
            return null;
        }

        $currentRefreshToken->revoke();

        $user = $currentRefreshToken->user;

        if ($user === null) {
            return null;
        }

        return array_merge(
            static::issuePair($user, $currentRefreshToken->name, $ipAddress, $userAgent),
            ['user' => $user]
        );
    }
}