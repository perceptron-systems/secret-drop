<?php

namespace App\Http\Middleware;

use App\Support\LocaleConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/** Resolves the active locale from the URL prefix or Accept-Language header and sets it app-wide. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);
        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    private function detectLocale(Request $request): string
    {
        $firstSegment = $request->segment(1);

        if ($firstSegment && LocaleConfig::isSupported($firstSegment)) {
            return $firstSegment;
        }

        return $this->detectFromAcceptLanguage($request);
    }

    private function detectFromAcceptLanguage(Request $request): string
    {
        $acceptLanguage = $request->header('Accept-Language', '');

        if (empty($acceptLanguage)) {
            return LocaleConfig::DEFAULT_LOCALE;
        }

        $preferredLocales = $this->parseAcceptLanguage($acceptLanguage);

        foreach ($preferredLocales as $locale) {
            $shortLocale = substr($locale, 0, 2);

            if (LocaleConfig::isSupported($shortLocale)) {
                return $shortLocale;
            }
        }

        return LocaleConfig::DEFAULT_LOCALE;
    }

    /**
     * @return array<int, string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $locales = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);

            if (empty($part)) {
                continue;
            }

            $segments = explode(';', $part);
            $locale = trim($segments[0]);
            $quality = 1.0;

            if (isset($segments[1])) {
                $qPart = trim($segments[1]);

                if (str_starts_with($qPart, 'q=')) {
                    $quality = (float) substr($qPart, 2);
                }
            }

            $locales[$locale] = $quality;
        }

        arsort($locales);

        return array_keys($locales);
    }
}
