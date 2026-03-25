@extends('layouts.app')

@section('title', __('messages.use_cases_title'))
@section('description', __('messages.use_cases_meta_description'))

@push('schema')
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "{{ config('app.name') }}",
            "item": "{{ route('home') }}"
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": "{{ __('messages.use_cases_title') }}",
            "item": "{{ localized_route('use-cases') }}"
        }
    ]
}
</script>
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "{{ __('messages.use_cases_title') }}",
    "description": "{{ __('messages.use_cases_meta_description') }}",
    "about": {
        "@@type": "WebApplication",
        "name": "{{ config('app.name') }}"
    },
    "isPartOf": {
        "@@type": "WebSite",
        "name": "{{ config('app.name') }}",
        "url": "{{ url('/') }}"
    }
}
</script>
@endpush

@section('content')
<div class="flex-1 py-12 px-4 pb-8 overflow-x-hidden transition-colors">
    <div class="max-w-4xl mx-auto">
        <x-card class="p-8 lg:p-12">
            <x-page-header :title="__('messages.use_cases_title')" />

            {{-- Introduction --}}
            <p class="text-gray-600 dark:text-slate-400 mb-10 text-lg">
                {{ __('messages.use_cases_intro') }}
            </p>

            {{-- Use cases grid --}}
            @php
                $useCases = [
                    ['key' => 'usecase1', 'iconName' => 'icon.key'],
                    ['key' => 'usecase2', 'iconName' => 'icon.document'],
                    ['key' => 'usecase3', 'iconName' => 'icon.code'],
                    ['key' => 'usecase4', 'iconName' => 'icon.terminal'],
                    ['key' => 'usecase5', 'iconName' => 'icon.wifi'],
                    ['key' => 'usecase6', 'iconName' => 'icon.user'],
                ];
            @endphp
            <div class="grid md:grid-cols-2 gap-4 mb-12">
                @foreach($useCases as $uc)
                <div class="p-5 bg-gray-50 dark:bg-slate-700/30 border border-gray-200 dark:border-slate-600/30 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/20 shrink-0" aria-hidden="true">
                            <x-dynamic-component :component="$uc['iconName']" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-900 dark:text-white mb-1">{{ __("messages.{$uc['key']}_title") }}</h2>
                            <p class="text-sm text-gray-600 dark:text-slate-400 mb-2">{{ __("messages.{$uc['key']}_desc") }}</p>
                            <p class="text-xs text-gray-600 dark:text-slate-400 italic">{{ __("messages.{$uc['key']}_example") }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- CTA --}}
            <div class="text-center">
                <x-btn-primary :href="route('home')">
                    {{ __('messages.use_cases_cta') }}
                </x-btn-primary>
                <div class="mt-6">
                    <a href="{{ localized_route('faq') }}" class="text-sm text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors">
                        {{ __('messages.faq_title') }} &rarr;
                    </a>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
