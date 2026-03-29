@extends('layouts.app')

@section('title', __('messages.how_it_works_meta_title'))
@section('description', __('messages.how_it_works_meta_description'))

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
            "name": "{{ __('messages.how_it_works_title') }}",
            "item": "{{ localized_route('how-it-works') }}"
        }
    ]
}
</script>
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "HowTo",
    "name": "{{ __('messages.how_it_works_title') }}",
    "description": "{{ __('messages.how_it_works_intro') }}",
    "step": [
        {
            "@@type": "HowToStep",
            "position": 1,
            "name": "{{ __('messages.how_step1_title') }}",
            "text": "{{ __('messages.how_step1_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 2,
            "name": "{{ __('messages.how_step2_title') }}",
            "text": "{{ __('messages.how_step2_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 3,
            "name": "{{ __('messages.how_step3_title') }}",
            "text": "{{ __('messages.how_step3_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 4,
            "name": "{{ __('messages.how_step4_title') }}",
            "text": "{{ __('messages.how_step4_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 5,
            "name": "{{ __('messages.how_step5_title') }}",
            "text": "{{ __('messages.how_step5_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 6,
            "name": "{{ __('messages.how_step6_title') }}",
            "text": "{{ __('messages.how_step6_desc') }}"
        }
    ]
}
</script>
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "{{ __('messages.how_it_works_title') }}",
    "description": "{{ __('messages.how_it_works_meta_description') }}",
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
            <x-page-header :title="__('messages.how_it_works_title')" />

            {{-- Introduction --}}
            <p class="text-gray-600 dark:text-slate-400 mb-10 text-lg">
                {{ __('messages.how_it_works_intro') }}
            </p>

            {{-- Visual Encryption Diagram --}}
            <div class="mb-12">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    {{ __('messages.how_it_works_diagram_title') }}
                </h2>

                @php
                    $steps = [
                        ['key' => 'how_step1', 'iconName' => 'icon.pencil'],
                        ['key' => 'how_step2', 'iconName' => 'icon.lock-closed'],
                        ['key' => 'how_step3', 'iconName' => 'icon.server'],
                        ['key' => 'how_step4', 'iconName' => 'icon.link'],
                        ['key' => 'how_step5', 'iconName' => 'icon.shield-check'],
                        ['key' => 'how_step6', 'iconName' => 'icon.trash'],
                    ];
                @endphp
                <ol class="space-y-4 list-none">
                    @foreach($steps as $i => $step)
                        @if($i > 0)
                        <div class="flex justify-center" aria-hidden="true">
                            <x-icon.arrow-down class="w-5 h-5 text-gray-300 dark:text-slate-600" />
                        </div>
                        @endif
                        <div class="flex items-start gap-4">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-500/20 text-violet-600 dark:text-violet-400 font-bold shrink-0">
                                {{ $i + 1 }}
                            </div>
                            <div class="flex-1 pt-1">
                                <h3 class="font-medium text-gray-900 dark:text-white mb-1">{{ __("messages.{$step['key']}_title") }}</h3>
                                <p class="text-gray-600 dark:text-slate-400 text-sm">{{ __("messages.{$step['key']}_desc") }}</p>
                            </div>
                            <div class="hidden sm:flex items-center justify-center w-12 h-12 rounded-xl bg-violet-50 dark:bg-violet-500/10 shrink-0" aria-hidden="true">
                                <x-dynamic-component :component="$step['iconName']" class="w-6 h-6 text-violet-500 dark:text-violet-400" />
                            </div>
                        </div>
                    @endforeach
                </ol>
            </div>

            {{-- Secure by design --}}
            <div class="mb-12">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                    {{ __('messages.secure_by_design_title') }}
                </h2>
                <p class="text-gray-600 dark:text-slate-400 mb-6">
                    {{ __('messages.secure_by_design_intro') }}
                </p>

                <div class="grid md:grid-cols-2 gap-4">
                    <x-feature-card :title="__('messages.sbd_zk_title')">{{ __('messages.sbd_zk_desc') }}</x-feature-card>
                    <x-feature-card :title="__('messages.sbd_ml_title')">{{ __('messages.sbd_ml_desc') }}</x-feature-card>
                    <x-feature-card :title="__('messages.sbd_fragment_title')">{{ __('messages.sbd_fragment_desc') }}</x-feature-card>
                    <x-feature-card :title="__('messages.sbd_destroy_title')">{{ __('messages.sbd_destroy_desc') }}</x-feature-card>
                    <x-feature-card :title="__('messages.sbd_hosted_title')">{{ __('messages.sbd_hosted_desc') }}</x-feature-card>
                    <x-feature-card :title="__('messages.sbd_notrack_title')">{{ __('messages.sbd_notrack_desc') }}</x-feature-card>
                </div>
            </div>

            {{-- CTA --}}
            <div class="text-center">
                <x-btn-primary :href="route('home')">
                    {{ __('messages.how_cta') }}
                </x-btn-primary>
                <div class="mt-6">
                    <a href="{{ localized_route('use-cases') }}" class="text-sm text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors">
                        {{ __('messages.how_see_use_cases') }} &rarr;
                    </a>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
