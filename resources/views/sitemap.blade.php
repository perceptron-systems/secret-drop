{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($locales as $locale)
  <url>
    <loc>{{ route('home', ['locale' => $locale]) }}</loc>
    <lastmod>{{ $lastmod }}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
@foreach($locales as $altLocale)
    <xhtml:link rel="alternate" hreflang="{{ $altLocale }}" href="{{ route('home', ['locale' => $altLocale]) }}" />
@endforeach
    <xhtml:link rel="alternate" hreflang="x-default" href="{{ route('home', ['locale' => App\Support\LocaleConfig::DEFAULT_LOCALE]) }}" />
    <image:image>
      <image:loc>{{ url('/og-image.png') }}</image:loc>
      <image:title>{{ config('app.name') }}</image:title>
    </image:image>
  </url>
@endforeach
@foreach($pages as $page)
@foreach($locales as $locale)
  <url>
    <loc>{{ localized_route($page, $locale) }}</loc>
    <lastmod>{{ $lastmod }}</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
@foreach($locales as $altLocale)
    <xhtml:link rel="alternate" hreflang="{{ $altLocale }}" href="{{ localized_route($page, $altLocale) }}" />
@endforeach
    <xhtml:link rel="alternate" hreflang="x-default" href="{{ localized_route($page, App\Support\LocaleConfig::DEFAULT_LOCALE) }}" />
  </url>
@endforeach
@endforeach
</urlset>
