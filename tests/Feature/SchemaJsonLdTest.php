<?php

namespace Tests\Feature;

use Tests\TestCase;

class SchemaJsonLdTest extends TestCase
{
    public function testHomepageHasSingleGraphWithAllSchemaTypes(): void
    {
        $schemas = $this->extractSchemas('/en');

        $graphBlock = collect($schemas)->first(fn ($s) => isset($s->{'@graph'}));
        $this->assertNotNull($graphBlock, 'Homepage should have a @graph block');

        $types = collect($graphBlock->{'@graph'})->pluck('@type')->all();
        $this->assertContains('WebSite', $types);
        $this->assertContains('Organization', $types);
        $this->assertContains('Person', $types);
        $this->assertContains('WebApplication', $types);
    }

    public function testHomepageSchemasHaveIds(): void
    {
        $schemas = $this->extractSchemas('/en');
        $graphBlock = collect($schemas)->first(fn ($s) => isset($s->{'@graph'}));

        foreach ($graphBlock->{'@graph'} as $item) {
            $this->assertNotEmpty(
                $item->{'@id'} ?? null,
                "Schema @type={$item->{'@type'}} should have @id",
            );
        }
    }

    public function testOrganizationHasFoundingDateAndContactPoint(): void
    {
        $schemas = $this->extractSchemas('/en');
        $graphBlock = collect($schemas)->first(fn ($s) => isset($s->{'@graph'}));
        $org = collect($graphBlock->{'@graph'})->first(fn ($s) => $s->{'@type'} === 'Organization');

        $this->assertNotEmpty($org->foundingDate ?? null, 'Organization should have foundingDate');
        $this->assertNotEmpty($org->contactPoint ?? null, 'Organization should have contactPoint');
    }

    public function testPersonHasWorksFor(): void
    {
        $schemas = $this->extractSchemas('/en');
        $graphBlock = collect($schemas)->first(fn ($s) => isset($s->{'@graph'}));
        $person = collect($graphBlock->{'@graph'})->first(fn ($s) => $s->{'@type'} === 'Person');

        $this->assertNotEmpty($person->worksFor ?? null, 'Person should have worksFor');
    }

    public function testFaqAnswersContainNoHtml(): void
    {
        $schemas = $this->extractSchemas('/en/faq');
        $faq = collect($schemas)->first(fn ($s) => ($s->{'@type'} ?? null) === 'FAQPage');

        $this->assertNotNull($faq, 'FAQ page should have FAQPage schema');

        foreach ($faq->mainEntity as $question) {
            $text = $question->acceptedAnswer->text ?? '';
            $this->assertStringNotContainsString('<a', $text, "FAQ answer contains raw HTML: {$question->name}");
            $this->assertStringNotContainsString('&lt;', $text, "FAQ answer contains escaped HTML: {$question->name}");
        }
    }

    public function testInnerPagesHaveBreadcrumbList(): void
    {
        $pages = ['/en/how-it-works', '/en/use-cases', '/en/faq'];

        foreach ($pages as $page) {
            $schemas = $this->extractSchemas($page);
            $breadcrumb = collect($schemas)->first(fn ($s) => ($s->{'@type'} ?? null) === 'BreadcrumbList');
            $this->assertNotNull($breadcrumb, "{$page} should have BreadcrumbList schema");
        }
    }

    public function testInnerPagesHaveSpeakable(): void
    {
        $pages = ['/en/how-it-works', '/en/use-cases', '/en/faq'];

        foreach ($pages as $page) {
            $schemas = $this->extractSchemas($page);
            $webPage = collect($schemas)->first(fn ($s) => ($s->{'@type'} ?? null) === 'WebPage');
            $this->assertNotNull($webPage, "{$page} should have WebPage schema");
            $this->assertNotEmpty($webPage->speakable ?? null, "{$page} WebPage should have speakable");
        }
    }

    /**
     * @return array<int, object>
     */
    private function extractSchemas(string $uri): array
    {
        $response = $this->get($uri);
        $html = $response->getContent();

        preg_match_all('/<script type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches);

        $schemas = [];
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json);
            $this->assertNotNull($decoded, "Invalid JSON-LD found on {$uri}");
            $schemas[] = $decoded;
        }

        return $schemas;
    }
}
