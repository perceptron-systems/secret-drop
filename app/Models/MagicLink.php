<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $email_hash
 * @property string $token_hash
 * @property Carbon $expire_at
 * @property Carbon|null $used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MagicLink extends Model
{
    /** @use HasFactory<\Database\Factories\MagicLinkFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'email_hash',
        'token_hash',
        'expire_at',
        'used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expire_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expire_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }

    public function markAsUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('token_hash', hash('sha256', $token))->first();
    }

    public static function hashEmail(string $email): string
    {
        return hash_hmac(
            'sha256',
            strtolower(trim($email)),
            config('secrets.email_hash_pepper')
        );
    }
}
