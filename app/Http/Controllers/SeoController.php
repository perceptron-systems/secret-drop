<?php

namespace App\Http\Controllers;

use App\Support\LocaleConfig;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $sitemap = url('/sitemap.xml');
        $disallowAdmin = collect(LocaleConfig::SUPPORTED_LOCALES)
            ->map(fn (string $locale) => "Disallow: /{$locale}/admin/")
            ->implode("\n");

        $content = <<<TXT
            User-agent: *
            Disallow: /s/
            {$disallowAdmin}
            Disallow: /api/
            Disallow: /contact

            User-agent: GPTBot
            Allow: /
            Disallow: /s/
            Disallow: /api/

            User-agent: ChatGPT-User
            Allow: /

            User-agent: Google-Extended
            Allow: /

            User-agent: anthropic-ai
            Allow: /

            User-agent: PerplexityBot
            Allow: /

            User-agent: ClaudeBot
            Allow: /

            User-agent: OAI-SearchBot
            Allow: /

            User-agent: bingbot
            Allow: /

            User-agent: GoogleOther
            Allow: /

            User-agent: Applebot-Extended
            Allow: /

            User-agent: Amazonbot
            Allow: /

            User-agent: FacebookBot
            Allow: /

            User-agent: Bytespider
            Disallow: /

            User-agent: CCBot
            Disallow: /

            Sitemap: {$sitemap}
            TXT;

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        $locales = LocaleConfig::SUPPORTED_LOCALES;
        $pages = LocaleConfig::translatablePages();
        $lastmod = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return response(
            view('sitemap', compact('locales', 'pages', 'lastmod'))->render(),
            200,
            ['Content-Type' => 'application/xml']
        );
    }

    public function sitemapStylesheet(): Response
    {
        return response(
            file_get_contents(resource_path('sitemap.xsl')),
            200,
            ['Content-Type' => 'text/xsl']
        );
    }

}
