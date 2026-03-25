<?php

namespace Tests\Feature;

use App\Support\LocaleConfig;
use Tests\TestCase;

class LocalizedRoutingTest extends TestCase
{
    /** Vérifie la redirection racine vers la locale détectée. */
    public function testRootRedirectsToDetectedLocale(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr')
            ->get('/');

        $response->assertRedirect();
        $this->assertStringEndsWith('/fr/', $response->headers->get('Location'));
    }

    /** Vérifie la redirection racine vers la locale anglaise. */
    public function testRootRedirectsToEnglishLocale(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->get('/');

        $response->assertRedirect();
        $this->assertStringEndsWith('/en/', $response->headers->get('Location'));
    }

    /** Vérifie le fallback racine vers le français sans header. */
    public function testRootDefaultsToFrenchWithoutHeader(): void
    {
        $response = $this->withHeader('Accept-Language', '')
            ->get('/');

        $response->assertRedirect();
        $this->assertStringEndsWith('/fr/', $response->headers->get('Location'));
    }

    /** Vérifie que la page d'accueil se charge avec une locale. */
    public function testHomePageRendersWithLocale(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
    }

    /** Vérifie que la page d'accueil se charge pour toutes les locales. */
    public function testHomePageRendersForAllLocales(): void
    {
        foreach (LocaleConfig::SUPPORTED_LOCALES as $locale) {
            $response = $this->get("/{$locale}");

            $response->assertOk();
        }
    }

    /** Vérifie le rendu avec un slug français. */
    public function testLocalizedPageRendersWithFrenchSlug(): void
    {
        $response = $this->get('/fr/comment-ca-marche');

        $response->assertOk();
    }

    /** Vérifie le rendu avec un slug anglais. */
    public function testLocalizedPageRendersWithEnglishSlug(): void
    {
        $response = $this->get('/en/how-it-works');

        $response->assertOk();
    }

    /** Vérifie le rendu avec un slug allemand. */
    public function testLocalizedPageRendersWithGermanSlug(): void
    {
        $response = $this->get('/de/so-funktioniert-es');

        $response->assertOk();
    }

    /** Vérifie le rendu des cas d'usage avec slug traduit. */
    public function testUseCasesRendersWithTranslatedSlug(): void
    {
        $response = $this->get('/fr/cas-d-usage');

        $response->assertOk();
    }

    /** Vérifie le rendu des mentions légales avec slug traduit. */
    public function testLegalRendersWithTranslatedSlug(): void
    {
        $response = $this->get('/fr/mentions-legales');

        $response->assertOk();
    }

    /** Vérifie qu'un slug invalide retourne 404. */
    public function testInvalidSlugReturns404(): void
    {
        $response = $this->get('/fr/nonexistent-page');

        $response->assertNotFound();
    }

    /** Vérifie qu'une locale invalide retourne 404. */
    public function testInvalidLocaleDoesNotMatchRoute(): void
    {
        $response = $this->get('/xx/how-it-works');

        $response->assertNotFound();
    }

    /** Vérifie la redirection 301 de l'ancien URL how-it-works. */
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

    /** Vérifie la redirection 301 de l'ancien URL use-cases. */
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

    /** Vérifie la redirection 301 de l'ancien URL legal. */
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

    /** Vérifie la redirection 301 quand le slug ne correspond pas à la locale. */
    public function testWrongSlugForLocaleRedirects301(): void
    {
        $response = $this->get('/fr/how-it-works');

        $response->assertStatus(301);
        $this->assertStringContainsString(
            '/fr/comment-ca-marche',
            $response->headers->get('Location')
        );
    }

    /** Vérifie que la route admin reste accessible. */
    public function testAdminRouteStillAccessible(): void
    {
        $response = $this->get('/fr/admin');

        $response->assertOk();
    }

    /** Vérifie la redirection /admin vers l'admin localisé. */
    public function testNonLocalizedAdminRedirectsToLocalizedAdmin(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr')
            ->get('/admin');

        $response->assertRedirect(route('admin.index', ['locale' => 'fr']));
    }

    /** Vérifie la redirection /superadmin vers le superadmin localisé. */
    public function testNonLocalizedSuperadminRedirectsToLocalizedSuperadmin(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->get('/superadmin');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'en']));
    }

    /** Vérifie que route() génère une URL localisée. */
    public function testRouteHelperGeneratesLocalizedUrl(): void
    {
        app()->setLocale('fr');
        \Illuminate\Support\Facades\URL::defaults(['locale' => 'fr']);

        $url = route('home');

        $this->assertStringEndsWith('/fr', $url);
    }

    /** Vérifie que localized_route() génère le bon slug. */
    public function testLocalizedRouteHelperGeneratesCorrectSlug(): void
    {
        app()->setLocale('fr');

        $url = localized_route('how-it-works');

        $this->assertStringContainsString('/fr/comment-ca-marche', $url);
    }

    /** Vérifie localized_route() avec une locale explicite. */
    public function testLocalizedRouteHelperWithExplicitLocale(): void
    {
        app()->setLocale('fr');

        $url = localized_route('how-it-works', 'de');

        $this->assertStringContainsString('/de/so-funktioniert-es', $url);
    }

    /** Vérifie que le sitemap contient toutes les variantes de locale. */
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

    /** Vérifie les balises hreflang sur la page d'accueil. */
    public function testHreflangTagsOnHomePage(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('hreflang="fr"', $content);
        $this->assertStringContainsString('hreflang="en"', $content);
        $this->assertStringContainsString('hreflang="x-default"', $content);
    }

    /** Vérifie les balises hreflang sur une page localisée. */
    public function testHreflangTagsOnLocalizedPage(): void
    {
        $response = $this->get('/fr/comment-ca-marche');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('hreflang="en"', $content);
        $this->assertStringContainsString('/en/how-it-works', $content);
        $this->assertStringContainsString('/de/so-funktioniert-es', $content);
    }

    /** Vérifie que le header Content-Language correspond à la locale. */
    public function testContentLanguageHeaderMatchesLocale(): void
    {
        $response = $this->get('/de');

        $response->assertOk();
        $response->assertHeader('Content-Language', 'de');
    }

    /** Vérifie que le japonais utilise les slugs anglais. */
    public function testJapaneseUsesEnglishSlugs(): void
    {
        $response = $this->get('/ja/how-it-works');

        $response->assertOk();
    }

    /** Vérifie la présence du sélecteur de langue sur la page d'accueil. */
    public function testLanguageSwitcherPresentOnHomePage(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();
        $response->assertSee('x-data="languageSwitcher"', false);
    }

    /** Vérifie que le sélecteur de langue contient toutes les URLs. */
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

    /** Vérifie que le sélecteur pointe vers les pages traduites. */
    public function testLanguageSwitcherOnLocalizedPagePointsToTranslatedPages(): void
    {
        $response = $this->get('/fr/comment-ca-marche');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('/en/how-it-works', $content);
        $this->assertStringContainsString('/de/so-funktioniert-es', $content);
    }

    /** Vérifie que locale_switcher_urls() retourne toutes les locales. */
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

    /** Vérifie que toutes les pages traduisibles fonctionnent pour toutes les locales. */
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
