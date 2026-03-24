<?php

namespace Database\Factories;

use App\Models\MagicLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MagicLink>
 */
class MagicLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_hash' => hash('sha256', fake()->unique()->safeEmail()),
            'token_hash' => hash('sha256', Str::random(32)),
            'expire_at' => now()->addMinutes(5),
        ];
    }

    public function forEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email_hash' => MagicLink::hashEmail($email),
        ]);
    }

    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => hash('sha256', $token),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => now()->subMinute(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
        ]);
    }

    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_at' => now()->addMinutes(5),
            'used_at' => null,
        ]);
    }
}
