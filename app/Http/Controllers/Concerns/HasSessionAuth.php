<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HasSessionAuth
{
    /**
     * Get the authenticated session value, or null if expired/missing.
     * Controllers must define SESSION_KEY and SESSION_EXPIRES_KEY constants.
     */
    private function getSessionAuth(Request $request): mixed
    {
        $value = $request->session()->get(static::SESSION_KEY);

        if (! $value) {
            return null;
        }

        $expiresAt = $request->session()->get(static::SESSION_EXPIRES_KEY);

        if ($expiresAt && $expiresAt < now()->timestamp) {
            $request->session()->forget(static::SESSION_KEY);
            $request->session()->forget(static::SESSION_EXPIRES_KEY);

            return null;
        }

        return $value;
    }

    private function renewSessionExpiry(Request $request): void
    {
        if ($request->session()->has(static::SESSION_EXPIRES_KEY)) {
            $request->session()->put(static::SESSION_EXPIRES_KEY, now()->addMinutes(config('secrets.session_ttl'))->timestamp);
        }
    }
}
