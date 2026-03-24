<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $token
 * @property string $admin_token_hash
 * @property string $type
 * @property array<string, mixed> $cipher_meta
 * @property string|null $ciphertext
 * @property string|null $file_path
 * @property int|null $max_views
 * @property int $read_count
 * @property Carbon|null $first_read_at
 * @property Carbon|null $last_read_at
 * @property Carbon|null $expire_at
 * @property Carbon|null $revoked_at
 * @property string|null $creator_email_hash
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Secret extends Model
{
    /** @use HasFactory<\Database\Factories\SecretFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'token',
        'admin_token_hash',
        'type',
        'cipher_meta',
        'ciphertext',
        'file_path',
        'max_views',
        'read_count',
        'expire_at',
        'revoked_at',
        'creator_email_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cipher_meta' => 'array',
            'first_read_at' => 'datetime',
            'last_read_at' => 'datetime',
            'expire_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expire_at !== null && $this->expire_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasReachedMaxViews(): bool
    {
        return $this->max_views !== null && $this->read_count >= $this->max_views;
    }

    public function isAccessible(): bool
    {
        return ! $this->isExpired()
            && ! $this->isRevoked()
            && ! $this->hasReachedMaxViews();
    }

    public function incrementReadCount(): void
    {
        $now = now();

        $this->read_count++;
        $this->last_read_at = $now;

        if ($this->first_read_at === null) {
            $this->first_read_at = $now;
        }

        $this->save();
    }

    public function shouldBeDestroyed(): bool
    {
        return $this->hasReachedMaxViews();
    }

    public function destroyContent(): void
    {
        if ($this->type === 'text') {
            $this->ciphertext = null;
        } else {
            $this->file_path = null;
        }

        $this->save();
    }

    public function hasCreatorEmail(): bool
    {
        return $this->creator_email_hash !== null;
    }

    public function verifyCreatorEmail(string $email): bool
    {
        if ($this->creator_email_hash === null) {
            return false;
        }

        return hash_equals(
            $this->creator_email_hash,
            hash('sha256', strtolower(trim($email)))
        );
    }
}
