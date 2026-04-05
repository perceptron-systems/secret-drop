<?php

namespace App\Http\Middleware;

use App\Services\CaptchaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/** Rate-limits requests per IP and requires a math captcha once the threshold is exceeded. */
class ThrottleWithCaptcha
{
    private const CACHE_PREFIX = 'throttle:';

    public function __construct(
        private CaptchaService $captcha,
    ) {
    }

    /**
     * @param  string  $maxAttempts  Maximum attempts before requiring captcha
     * @param  string  $decayMinutes  Time window in minutes
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '1'): Response
    {
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        $identifier = $this->getIdentifier($request);
        $cacheKey = self::CACHE_PREFIX.$identifier.':'.$request->path();

        $attempts = (int) Cache::get($cacheKey, 0);

        // If under limit, increment and proceed
        if ($attempts < $maxAttempts) {
            Cache::put($cacheKey, $attempts + 1, now()->addMinutes($decayMinutes));

            return $next($request);
        }

        // Rate limit exceeded - check for valid captcha
        $captchaToken = $request->input('captcha_token') ?? $request->header('X-Captcha-Token');
        $captchaAnswer = $request->input('captcha_answer') ?? $request->header('X-Captcha-Answer');

        if ($captchaToken && $captchaAnswer !== null) {
            if ($this->captcha->verify($captchaToken, $captchaAnswer, $identifier)) {
                // Valid captcha - reset counter and proceed
                Cache::forget($cacheKey);

                return $next($request);
            }
        }

        // Generate new captcha challenge
        $captchaData = $this->captcha->generate($identifier);

        $retryAfter = $this->getRetryAfter($decayMinutes);

        return $this->buildCaptchaResponse($captchaData, $retryAfter, $request);
    }

    private function getIdentifier(Request $request): string
    {
        return hash('sha256', $request->ip());
    }

    private function getRetryAfter(int $decayMinutes): int
    {
        return $decayMinutes * 60;
    }

    /**
     * @param  array{token: string, challenge: string}  $captchaData
     */
    private function buildCaptchaResponse(array $captchaData, int $retryAfter, Request $request): Response
    {
        $data = [
            'error' => 'rate_limit_exceeded',
            'message' => __('messages.rate_limit_exceeded'),
            'captcha_required' => true,
            'captcha_token' => $captchaData['token'],
            'captcha_challenge' => $captchaData['challenge'],
            'retry_after' => $retryAfter,
        ];

        if ($request->expectsJson()) {
            return response()->json($data, 429)
                ->header('Retry-After', (string) $retryAfter)
                ->header('X-Captcha-Required', 'true');
        }

        // For form submissions, redirect back with captcha data in session
        return redirect()
            ->back()
            ->withInput()
            ->with('captcha_required', true)
            ->with('captcha_token', $captchaData['token'])
            ->with('captcha_challenge', $captchaData['challenge'])
            ->withErrors(['captcha' => __('messages.rate_limit_exceeded')]);
    }
}
