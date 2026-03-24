@extends('layouts.app')

@section('title', __('messages.legal_title'))
@section('description', __('messages.legal_meta_description'))

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
            "name": "{{ __('messages.legal_title') }}"
        }
    ]
}
</script>
@endpush

@section('content')
<div class="flex-1 py-12 px-4 pb-8 overflow-x-hidden transition-colors">
    <div class="max-w-3xl mx-auto">
        <x-card class="p-8 lg:p-12">
            <x-page-header :title="__('messages.legal_title')" />

            <div class="prose prose-gray dark:prose-invert max-w-none">
                {{-- About --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_about_title') }}
                    </h2>
                    <p class="text-gray-600 dark:text-slate-400 mb-3">
                        {{ __('messages.legal_about_text') }}
                    </p>
                    <p class="text-gray-600 dark:text-slate-400">
                        {{ __('messages.legal_about_security') }}
                    </p>
                </section>

                {{-- Editor --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_editor_title') }}
                    </h2>
                    <div class="text-gray-600 dark:text-slate-400 space-y-1">
                        <p>{{ config('legal.editor_name') }}</p>
                        @if(config('legal.editor_address'))
                            <p>{{ config('legal.editor_address') }}</p>
                        @endif
                        @if(config('legal.editor_phone'))
                            <p>{{ __('messages.legal_editor_phone') }} {{ config('legal.editor_phone') }}</p>
                        @endif
                        @php
                            $editorEmail = config('legal.contact_email', config('mail.from.address'));
                            [$editorUser, $editorDomain] = explode('@', $editorEmail);
                        @endphp
                        <p>{{ __('messages.legal_editor_email') }} <span class="protected-email" dir="ltr" data-user="{{ $editorUser }}" data-domain="{{ $editorDomain }}">[e-mail]</span></p>
                    </div>
                </section>

                {{-- Hosting --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_hosting_title') }}
                    </h2>
                    <p class="text-gray-600 dark:text-slate-400 mb-2">
                        {{ __('messages.legal_hosting_text') }}
                    </p>
                    <div class="text-gray-600 dark:text-slate-400 space-y-1 rtl:text-right" dir="ltr">
                        <p>{{ config('legal.hosting.name') }}</p>
                        <p>{{ config('legal.hosting.address') }}</p>
                        <p>{{ __('messages.legal_hosting_phone') }} {{ config('legal.hosting.phone') }}</p>
                    </div>
                </section>

                {{-- Data Protection --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_data_title') }}
                    </h2>
                    <p class="text-gray-600 dark:text-slate-400 mb-4">
                        {{ __('messages.legal_data_text') }}
                    </p>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl">
                            <h3 class="font-medium text-emerald-800 dark:text-emerald-300 mb-2">
                                {{ __('messages.legal_data_stored') }}
                            </h3>
                            <ul class="text-sm text-emerald-700 dark:text-emerald-300 space-y-1">
                                <li class="flex items-start gap-2">
                                    <x-icon.check class="w-4 h-4 mt-0.5 shrink-0" />
                                    {{ __('messages.legal_data_item_ciphertext') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <x-icon.check class="w-4 h-4 mt-0.5 shrink-0" />
                                    {{ __('messages.legal_data_item_metadata') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <x-icon.check class="w-4 h-4 mt-0.5 shrink-0" />
                                    {{ __('messages.legal_data_item_email') }}
                                </li>
                            </ul>
                        </div>

                        <div class="p-4 bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/20 rounded-xl">
                            <h3 class="font-medium text-violet-800 dark:text-violet-400 mb-2">
                                {{ __('messages.legal_data_not_stored') }}
                            </h3>
                            <ul class="text-sm text-violet-700 dark:text-violet-300 space-y-1">
                                <li class="flex items-start gap-2">
                                    <x-icon.lock class="w-4 h-4 mt-0.5 shrink-0" />
                                    {{ __('messages.legal_data_not_item_plaintext') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <x-icon.lock class="w-4 h-4 mt-0.5 shrink-0" />
                                    {{ __('messages.legal_data_not_item_key') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <x-icon.lock class="w-4 h-4 mt-0.5 shrink-0" />
                                    {{ __('messages.legal_data_not_item_file_meta') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                {{-- Cookies --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_cookies_title') }}
                    </h2>
                    <p class="text-gray-600 dark:text-slate-400 mb-2">
                        {{ __('messages.legal_cookies_text') }}
                    </p>
                    <p class="text-gray-600 dark:text-slate-400">
                        {{ __('messages.legal_cookies_cnil') }}
                    </p>
                </section>

                {{-- Contact --}}
                <section>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_contact_title') }}
                    </h2>
                    @php
                        $email = config('legal.contact_email', config('mail.from.address'));
                        [$emailUser, $emailDomain] = explode('@', $email);
                    @endphp
                    <p class="text-gray-600 dark:text-slate-400">
                        {{ __('messages.legal_contact_prefix') }} <a href="{{ route('contact.email') }}" class="protected-email text-violet-600 dark:text-violet-400 hover:underline" dir="ltr" data-user="{{ $emailUser }}" data-domain="{{ $emailDomain }}">[e-mail]</a>.
                    </p>
                </section>
            </div>
        </x-card>
    </div>
</div>
@endsection
