<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }} - {{ __('messages.app_description') }}@endif</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="theme-color" content="#8b5cf6">

    <meta name="msapplication-TileColor" content="#8b5cf6">

    {{-- Preload critical fonts --}}
    <link rel="preload" href="/fonts/instrument-sans-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/instrument-sans-600.woff2" as="font" type="font/woff2" crossorigin>
    @if(app()->getLocale() === 'ar')
    <link rel="preload" href="/fonts/noto-sans-arabic-400.woff2" as="font" type="font/woff2" crossorigin>
    @endif

    <meta name="description" content="@yield('description', __('messages.app_description'))">

    {{-- SEO: noindex for sensitive pages --}}
    @if(View::hasSection('noindex') || request()->is('s/*') || request()->routeIs('admin.*', 'superadmin.*'))
    <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- Canonical URL, Open Graph, Twitter Card - disabled for sensitive pages --}}
    @unless(request()->is('s/*') || request()->routeIs('admin.*', 'superadmin.*'))
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Alternate languages --}}
    {!! hreflang_tags() !!}

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }}@endif">
    <meta property="og:description" content="@yield('description', __('messages.app_description'))">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ __('messages.app_description') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@perceptron_sys">
    <meta name="twitter:creator" content="@perceptron_sys">
    <meta name="twitter:title" content="@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }}@endif">
    <meta name="twitter:description" content="@yield('description', __('messages.app_description'))">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">
    @endunless

    {{-- Schema.org JSON-LD (only on homepage) --}}
    @if(request()->routeIs('home'))
    <script type="application/ld+json" nonce="@nonce">
    {
        "@@context": "https://schema.org",
        "@@type": "WebApplication",
        "name": "{{ config('app.name') }}",
        "description": "{{ __('messages.app_description') }}",
        "url": "{{ url('/') }}",
        "applicationCategory": "SecurityApplication",
        "operatingSystem": "Any",
        "browserRequirements": "Requires JavaScript, Web Crypto API",
        "inLanguage": {!! json_encode(\App\Support\LocaleConfig::SUPPORTED_LOCALES) !!},
        "image": "{{ asset('icon-512.png') }}",
        "isAccessibleForFree": true,
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR"
        },
        "featureList": [
            "{{ __('messages.feature_encryption') }}",
            "{{ __('messages.feature_zero_knowledge') }}",
            "{{ __('messages.feature_auto_destroy') }}",
            "{{ __('messages.feature_expiration') }}"
        ],
        "author": {
            "@@type": "Person",
            "name": "{{ config('legal.editor_name') }}"
        }
    }
    </script>
    <script type="application/ld+json" nonce="@nonce">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "{{ config('legal.organization_name', config('app.name')) }}",
        "url": "{{ url('/') }}",
        "logo": {
            "@@type": "ImageObject",
            "url": "{{ asset('icon-512.png') }}",
            "width": 512,
            "height": 512
        },
        "description": "{{ __('messages.legal_about_text') }}"@if(config('legal.contact_email')),
        "email": "{{ config('legal.contact_email') }}"@endif,
        "knowsAbout": ["zero-knowledge encryption", "end-to-end encryption", "secure file sharing", "password sharing"],
        "founder": {
            "@@type": "Person",
            "name": "{{ config('legal.editor_name') }}"
        },
        @if(collect(config('legal.social'))->filter()->isNotEmpty())
        "sameAs": {!! json_encode(collect(config('legal.social'))->filter()->values()) !!},
        @endif
        "owns": {
            "@@type": "WebApplication",
            "name": "{{ config('app.name') }}",
            "url": "{{ url('/') }}"
        }
    }
    </script>
    <script type="application/ld+json" nonce="@nonce">
    {
        "@@context": "https://schema.org",
        "@@type": "Person",
        "name": "{{ config('legal.editor_name') }}",
        "url": "https://www.orsal.fr",
        "jobTitle": "Software Engineer",
        "knowsAbout": ["web application security", "zero-knowledge encryption", "Laravel development", "end-to-end encryption"],
        @if(collect(config('legal.social'))->filter()->isNotEmpty())
        "sameAs": {!! json_encode(collect(config('legal.social'))->filter()->values()) !!},
        @endif
        "owns": {
            "@@type": "WebApplication",
            "name": "{{ config('app.name') }}",
            "url": "{{ url('/') }}"
        }
    }
    </script>
    @endif

    @stack('schema')

    @php
        $jsTranslations = array_intersect_key(__('messages'), array_flip([
            'btn_encrypting',
            'btn_encrypting_upload',
            'captcha_invalid',
            'crypto_clipboard_failed',
            'crypto_creation_error',
            'crypto_decryption_error',
            'crypto_decryption_failed',
            'crypto_enter_secret',
            'crypto_file_download_failed',
            'crypto_fragment_invalid',
            'crypto_not_supported',
            'crypto_passphrase_incorrect',
            'crypto_passphrase_required',
            'crypto_select_file',
            'decrypting_file',
            'decrypting_message',
            'encrypted_end_to_end_file',
            'encrypted_end_to_end_message',
            'error_connection',
            'error_loading',
            'file_too_large',
            'qr_generation_failed',
            'secret_expired',
            'secret_file',
            'secret_max_views',
            'secret_message',
            'secret_not_exist',
            'secret_revoked',
            'secret_unavailable_generic',
            'unit_bytes',
            'unit_kilobytes',
            'unit_megabytes',
            'a11y_show_passphrase',
            'a11y_hide_passphrase',
            'admin_error_connection',
            'admin_error_not_found',
            'admin_error_already_revoked',
            'admin_error_already_consumed',
            'admin_error_revoked',
            'admin_error_revoke',
            'admin_error_extend',
        ]));
    @endphp
    <style nonce="@nonce">[x-cloak]{display:none!important}html{background:#e5e7eb}html.dark{background:#0f172a}</style>
    <script nonce="@nonce">
        {{-- Only expose translations needed by JS --}}
        window.translations = @json($jsTranslations);
    </script>
    <script nonce="@nonce">
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'light') {
                document.documentElement.classList.remove('dark');
            }
            if (!document.cookie.includes('tz_offset=')) {
                document.cookie = 'tz_offset=' + new Date().getTimezoneOffset() + ';path=/;max-age=86400;SameSite=Lax';
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased min-h-dvh flex flex-col bg-linear-to-br from-gray-50 via-gray-200 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:start-2 focus:z-[200] focus:px-4 focus:py-2 focus:bg-violet-600 focus:text-white focus:rounded-xl focus:text-sm focus:font-medium">
        {{ __('messages.a11y_skip_to_content') }}
    </a>

    <main id="main-content" class="relative z-10 flex-1 flex flex-col">
        @yield('content')
    </main>

    <footer class="relative z-10 py-4 sm:py-6 text-sm text-gray-500 dark:text-slate-400 transition-colors border-t border-transparent" style="border-image: linear-gradient(90deg, transparent, rgba(139,92,246,0.2), transparent) 1;">
        {{-- Desktop: links top, copyright + github + switchers bottom --}}
        <div class="hidden sm:flex flex-col items-center gap-2 px-4">
            <nav aria-label="{{ __('messages.a11y_footer_nav') }}" class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
                <a href="{{ route('home') }}" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">
                    {{ config('app.name') }}
                </a>
                <span aria-hidden="true">·</span>
                <a href="{{ localized_route('how-it-works') }}" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">
                    {{ __('messages.footer_how_it_works') }}
                </a>
                <span aria-hidden="true">·</span>
                <a href="{{ localized_route('use-cases') }}" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">
                    {{ __('messages.footer_use_cases') }}
                </a>
                <span aria-hidden="true">·</span>
                <a href="{{ localized_route('faq') }}" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">
                    FAQ
                </a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('admin.index') }}" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">
                    {{ __('messages.footer_manage') }}
                </a>
                <span aria-hidden="true">·</span>
                <a href="{{ localized_route('legal') }}" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">
                    {{ __('messages.footer_legal') }}
                </a>
            </nav>
            <div class="flex items-center gap-2">
                <span>&copy; 2026 <a href="https://www.orsal.fr" target="_blank" rel="noopener" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">Guillaume Orsal</a></span>
                <a href="https://github.com/perceptron-systems/secret-drop" target="_blank" rel="noopener" aria-label="GitHub" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">
                    <x-icon.github class="w-4 h-4" />
                </a>
                <span aria-hidden="true">·</span>
                <x-language-switcher />
                <x-theme-toggle />
            </div>
        </div>

        {{-- Mobile: icons top, copyright bottom --}}
        <div class="sm:hidden flex flex-col items-center gap-3">
            <div class="flex items-center gap-3">
                <x-footer-menu />
                <x-language-switcher />
                <x-theme-toggle />
            </div>
            <div class="flex items-center gap-1.5">
                <span>&copy; 2026 <a href="https://www.orsal.fr" target="_blank" rel="noopener" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">Guillaume Orsal</a></span>
                <a href="https://github.com/perceptron-systems/secret-drop" target="_blank" rel="noopener" aria-label="GitHub" class="hover:text-gray-700 dark:hover:text-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:rounded transition-colors">
                    <x-icon.github class="w-4 h-4" />
                </a>
            </div>
        </div>
    </footer>
</body>
</html>
