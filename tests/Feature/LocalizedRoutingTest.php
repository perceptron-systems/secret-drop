<?php

namespace Tests\Feature;

use App\Support\LocaleConfig;
use Tests\TestCase;

class LocalizedRoutingTest extends TestCase
{
    public function testRootRedirectsToDetectedLocale(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr')
            ->get('/');

        $response->assertRedirect();
        $this->assertStringEndsWith('/fr/', $response->headers->get('Location'));
    }

    public function testRootRedirectsToEnglishLocale(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->get('/');

        $response->assertRedirect();
        $this->assertStringEndsWith('/en/', $response->headers->get('Location'));
    }

    public function testRootDefaultsToFrenchWithoutHeader(): void
    {
        $response = $this->withHeader('Accept-Language', '')
            ->get('/');

        $response->assertRedirect();
        $this->assertStringEndsWith('/fr/', $response->headers->get('Location'));
    }

    public function testHomePageRendersWithLocale(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
    }

    public function testHomePageRendersForAllLocales(): void
    {
        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $response = $this->get("/{$locale}");

            $response->assertOk();
        }
    }

    public function testLocalizedPageRendersWithFrenchSlug(): void
    {
        $response = $this->get('/fr/comment-ca-marche');

        $response->assertOk();
    }

    public function testLocalizedPageRendersWithEnglishSlug(): void
    {
        $response = $this->get('/en/how-it-works');

        $response->assertOk();
    }

    public function testLocalizedPageRendersWithGermanSlug(): void
    {
        $response = $this->get('/de/so-funktioniert-es');

        $response->assertOk();
    }

    public function testUseCasesRendersWithTranslatedSlug(): void
    {
        $response = $this->get('/fr/cas-d-usage');

        $response->assertOk();
    }

    public function testLegalRendersWithTranslatedSlug(): void
    {
        $response = $this->get('/fr/mentions-legales');

        $response->assertOk();
    }

    public function testInvalidSlugReturns404(): void
    {
        $response = $this->get('/fr/nonexistent-page');

        $response->assertNotFound();
    }

    public function testInvalidLocaleDoesNotMatchRoute(): void
    {
        $response = $this->get('/xx/how-it-works');

        $response->assertNotFound();
    }

    public function testLegacyHowItWorksRedirects301(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr')
            ->get('/how-it-works');

        $response->assertStatus(301);
        $this->assertStringContainsString(
            '/fr/comment-ca-marche',
            $response->headers->get('Location')
        );
    }

    public function testLegacyUseCasesRedirects301(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->get('/use-cases');

        $response->assertStatus(301);
        $this->assertStringContainsString(
            '/en/use-cases',
            $response->headers->get('Location')
        );
    }

    public function testLegacyLegalRedirects301(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr')
            ->get('/legal');

        $response->assertStatus(301);
        $this->assertStringContainsString(
            '/fr/mentions-legales',
            $response->headers->get('Location')
        );
    }

    public function testWrongSlugForLocaleRedirects301(): void
    {
        $response = $this->get('/fr/how-it-works');

        $response->assertStatus(301);
        $this->assertStringContainsString(
            '/fr/comment-ca-marche',
            $response->headers->get('Location')
        );
    }

    public function testAdminRouteStillAccessible(): void
    {
        $response = $this->get('/fr/admin');

        $response->assertOk();
    }

    public function testNonLocalizedAdminRedirectsToLocalizedAdmin(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr')
            ->get('/admin');

        $response->assertRedirect(route('admin.index', ['locale' => 'fr']));
    }

    public function testNonLocalizedSuperadminRedirectsToLocalizedSuperadmin(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->get('/superadmin');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'en']));
    }

    public function testRouteHelperGeneratesLocalizedUrl(): void
    {
        app()->setLocale('fr');
        \Illuminate\Support\Facades\URL::defaults(['locale' => 'fr']);

        $url = route('home');

        $this->assertStringEndsWith('/fr', $url);
    }

    public function testLocalizedRouteHelperGeneratesCorrectSlug(): void
    {
        app()->setLocale('fr');

        $url = localized_route('how-it-works');

        $this->assertStringContainsString('/fr/comment-ca-marche', $url);
    }

    public function testLocalizedRouteHelperWithExplicitLocale(): void
    {
        app()->setLocale('fr');

        $url = localized_route('how-it-works', 'de');

        $this->assertStringContainsString('/de/so-funktioniert-es', $url);
    }

    public function testSitemapContainsAllLocaleVariants(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');

        $content = $response->getContent();

        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $this->assertStringContainsString("/{$locale}", $content);
        }

        $this->assertStringContainsString('xhtml:link', $content);
        $this->assertStringContainsString('hreflang', $content);
    }

    public function testHreflangTagsOnHomePage(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('hreflang="fr"', $content);
        $this->assertStringContainsString('hreflang="en"', $content);
        $this->assertStringContainsString('hreflang="x-default"', $content);
    }

    public function testHreflangTagsOnLocalizedPage(): void
    {
        $response = $this->get('/fr/comment-ca-marche');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('hreflang="en"', $content);
        $this->assertStringContainsString('/en/how-it-works', $content);
        $this->assertStringContainsString('/de/so-funktioniert-es', $content);
    }

    public function testContentLanguageHeaderMatchesLocale(): void
    {
        $response = $this->get('/de');

        $response->assertOk();
        $response->assertHeader('Content-Language', 'de');
    }

    public function testJapaneseUsesEnglishSlugs(): void
    {
        $response = $this->get('/ja/how-it-works');

        $response->assertOk();
    }

    public function testLanguageSwitcherPresentOnHomePage(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
        $response->assertSee('x-data="languageSwitcher"', false);
    }

    public function testLanguageSwitcherContainsAllLocaleUrls(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
        $content = $response->getContent();

        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $this->assertStringContainsString(
                "/{$locale}",
                $content,
                "Language switcher should contain URL for locale '{$locale}'"
            );
        }
    }

    public function testLanguageSwitcherOnLocalizedPagePointsToTranslatedPages(): void
    {
        $response = $this->get('/fr/comment-ca-marche');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('/en/how-it-works', $content);
        $this->assertStringContainsString('/de/so-funktioniert-es', $content);
    }

    public function testLocaleSwitcherUrlsHelperReturnsAllLocales(): void
    {
        $this->get('/fr');

        $urls = locale_switcher_urls();

        $this->assertCount(count(LocaleConfig::SUPPORTED_LOCALES), $urls);

        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $this->assertArrayHasKey($locale, $urls);
            $this->assertStringContainsString("/{$locale}", $urls[$locale]);
        }
    }

    public function testAllTranslatablePagesWorkForAllLocales(): void
    {
        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            foreach (LocaleConfig::translatablePages() as $page) {
                $slug = LocaleConfig::translatedSlug($page, $locale);
                $response = $this->get("/{$locale}/{$slug}");

                $response->assertOk(
                    "Page '{$page}' with slug '{$slug}' for locale '{$locale}' should return 200"
                );
            }
        }
    }
}
