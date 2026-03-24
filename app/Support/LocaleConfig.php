<?php

namespace App\Support;

class LocaleConfig
{
    /** @var array<int, string> */
    public const SUPPORTED_LOCALES = ['en', 'fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ja', 'ko', 'ar'];

    public const DEFAULT_LOCALE = 'fr';

    /** @var array<string, string> */
    public const NATIVE_NAMES = [
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'es' => 'Español',
        'it' => 'Italiano',
        'pt' => 'Português',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'ja' => '日本語',
        'ko' => '한국어',
        'ar' => 'العربية',
    ];

    /** @var array<string, string> */
    public const FLAGS = [
        'en' => '🇬🇧',
        'fr' => '🇫🇷',
        'de' => '🇩🇪',
        'es' => '🇪🇸',
        'it' => '🇮🇹',
        'pt' => '🇵🇹',
        'nl' => '🇳🇱',
        'pl' => '🇵🇱',
        'ja' => '🇯🇵',
        'ko' => '🇰🇷',
        'ar' => '🇸🇦',
    ];

    /** @var array<int, string> */
    private const TRANSLATABLE_PAGES = ['how-it-works', 'use-cases', 'legal'];

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES, true);
    }

    /**
     * @return array<int, string>
     */
    public static function translatablePages(): array
    {
        return self::TRANSLATABLE_PAGES;
    }

    public static function findRouteBySlug(string $slug, string $locale): ?string
    {
        foreach (self::TRANSLATABLE_PAGES as $routeName) {
            $translatedSlug = trans("routes.{$routeName}", [], $locale);

            if ($translatedSlug === $slug) {
                return $routeName;
            }
        }

        return null;
    }

    public static function findRouteBySlugAnyLocale(string $slug): ?string
    {
        foreach (self::TRANSLATABLE_PAGES as $routeName) {
            foreach (self::SUPPORTED_LOCALES as $locale) {
                if (trans("routes.{$routeName}", [], $locale) === $slug) {
                    return $routeName;
                }
            }
        }

        return null;
    }

    public static function translatedSlug(string $routeName, string $locale): string
    {
        return trans("routes.{$routeName}", [], $locale);
    }

    public static function localePattern(): string
    {
        return implode('|', self::SUPPORTED_LOCALES);
    }
}
