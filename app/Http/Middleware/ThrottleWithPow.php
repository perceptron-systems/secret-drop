<?php

namespace App\Http\Middleware;

use App\Services\ProofOfWorkService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/** Rate-limits requests per IP and requires a proof-of-work once the threshold is exceeded. */
class ThrottleWithPow
{
    private const CACHE_PREFIX = 'throttle:';

    public function __construct(
        private ProofOfWorkService $pow,
    ) {
    }

    /**
     * @param  string  $maxAttempts  Maximum attempts before requiring PoW
     * @param  string  $decayMinutes  Time window in minutes
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '1'): Response
    {
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        $identifier = $this->getIdentifier($request);
        $cacheKey = self::CACHE_PREFIX.$identifier.':'.($request->route()?->getName() ?? $request->path());
        $ttl = now()->addMinutes($decayMinutes);

        // First hit within the window: set counter to 1 with TTL.
        // Subsequent hits increment without renewing TTL, so the decay window stays fixed.
        if (Cache::add($cacheKey, 1, $ttl)) {
            return $next($request);
        }

        $attempts = Cache::increment($cacheKey);

        // Key may have expired between add() and increment(); re-seed if so.
        if ($attempts === false) {
            Cache::put($cacheKey, 1, $ttl);

            return $next($request);
        }

        if ($attempts <= $maxAttempts) {
            return $next($request);
        }

        // Rate limit exceeded — check for valid proof-of-work
        $powToken = $request->input('pow_token') ?? $request->header('X-Pow-Token');
        $powNonce = $request->input('pow_nonce') ?? $request->header('X-Pow-Nonce');

        if ($powToken && $powNonce !== null) {
            if ($this->pow->verify($powToken, $powNonce, $identifier)) {
                return $next($request);
            }
        }

        // Generate new PoW challenge
        $powData = $this->pow->generate($identifier);

        $retryAfter = $decayMinutes * 60;

        return $this->buildPowResponse($powData, $retryAfter, $request);
    }

    private function getIdentifier(Request $request): string
    {
        return hash('sha256', $request->ip());
    }

    /**
     * @param  array{token: string, challenge: string, difficulty: int}  $powData
     */
    private function buildPowResponse(array $powData, int $retryAfter, Request $request): Response
    {
        $data = [
            'error' => 'rate_limit_exceeded',
            'message' => __('messages.rate_limit_exceeded'),
            'pow_required' => true,
            'pow_token' => $powData['token'],
            'pow_challenge' => $powData['challenge'],
            'pow_difficulty' => $powData['difficulty'],
            'retry_after' => $retryAfter,
        ];

        if ($request->expectsJson()) {
            return response()->json($data, 429)
                ->header('Retry-After', (string) $retryAfter)
                ->header('X-Pow-Required', 'true');
        }

        // For form submissions, redirect back with PoW data in session
        return redirect()
            ->back()
            ->withInput()
            ->with('pow_required', true)
            ->with('pow_token', $powData['token'])
            ->with('pow_challenge', $powData['challenge'])
            ->with('pow_difficulty', $powData['difficulty'])
            ->withErrors(['pow' => __('messages.rate_limit_exceeded')]);
    }
}
