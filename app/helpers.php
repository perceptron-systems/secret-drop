<?php

use App\Support\LocaleConfig;

if (! function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        return app('csp-nonce');
    }
}

if (! function_exists('localized_route')) {
    function localized_route(string $page, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $slug = LocaleConfig::translatedSlug($page, $locale);

        return route('page.show', ['locale' => $locale, 'pageSlug' => $slug]);
    }
}

if (! function_exists('hreflang_tags')) {
    function hreflang_tags(): string
    {
        $route = request()->route();

        if (! $route) {
            return '';
        }

        $routeName = $route->getName();
        $tags = '';

        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $url = match ($routeName) {
                'home' => route('home', ['locale' => $locale]),
                'page.show' => hreflang_page_url($locale),
                default => null,
            };

            if ($url) {
                $tags .= "<link rel=\"alternate\" hreflang=\"{$locale}\" href=\"{$url}\">\n";
            }
        }

        if ($tags !== '') {
            $defaultUrl = match ($routeName) {
                'home' => route('home', ['locale' => LocaleConfig::DEFAULT_LOCALE]),
                'page.show' => hreflang_page_url(LocaleConfig::DEFAULT_LOCALE),
                default => null,
            };

            if ($defaultUrl) {
                $tags .= "<link rel=\"alternate\" hreflang=\"x-default\" href=\"{$defaultUrl}\">\n";
            }
        }

        return $tags;
    }
}

if (! function_exists('locale_switcher_urls')) {
    /**
     * @return array<string, string>
     */
    function locale_switcher_urls(): array
    {
        $route = request()->route();
        $routeName = $route?->getName();

        $urls = [];

        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $urls[$locale] = match (true) {
                $routeName === 'home' => route('home', ['locale' => $locale]),
                $routeName === 'page.show' => hreflang_page_url($locale) ?? route('home', ['locale' => $locale]),
                $route && array_key_exists('locale', $route->parameters()) => route($routeName, array_merge($route->parameters(), ['locale' => $locale])),
                default => route('home', ['locale' => $locale]),
            };
        }

        return $urls;
    }
}

if (! function_exists('hreflang_page_url')) {
    function hreflang_page_url(string $locale): ?string
    {
        $currentSlug = request()->route('pageSlug');
        $currentLocale = app()->getLocale();
        $page = LocaleConfig::findRouteBySlug($currentSlug, $currentLocale);

        return $page ? localized_route($page, $locale) : null;
    }
}
