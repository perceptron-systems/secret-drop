@php
    $currentRoute = request()->route()?->getName();
    $currentSlug = request()->route()?->parameter('pageSlug');

    $menuItems = [
        ['route' => route('home'), 'active' => $currentRoute === 'home', 'label' => __('messages.btn_create_new'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => localized_route('how-it-works'), 'active' => $currentRoute === 'page.show' && $currentSlug === trans('routes.how-it-works'), 'label' => __('messages.footer_how_it_works'), 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['route' => localized_route('use-cases'), 'active' => $currentRoute === 'page.show' && $currentSlug === trans('routes.use-cases'), 'label' => __('messages.footer_use_cases'), 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ['route' => route('admin.index'), 'active' => str_starts_with($currentRoute ?? '', 'admin.'), 'label' => __('messages.footer_manage'), 'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
        ['route' => localized_route('legal'), 'active' => $currentRoute === 'page.show' && $currentSlug === trans('routes.legal'), 'label' => __('messages.footer_legal'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ];
@endphp

<div x-data="footerMenu" class="relative">
    {{-- Hamburger button --}}
    <button
        @click="toggle()"
        type="button"
        class="flex items-center justify-center w-8 h-8 rounded-xl cursor-pointer select-none
               bg-linear-to-b from-violet-500 to-indigo-600 dark:from-violet-600 dark:to-indigo-800
               shadow-[0_4px_14px_rgba(0,0,0,0.18)] transition-shadow duration-300
               hover:shadow-[0_6px_20px_rgba(0,0,0,0.25)]
               focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
        :aria-expanded="open"
        aria-haspopup="menu"
        aria-label="{{ __('messages.a11y_footer_nav') }}"
    >
        {{-- Hamburger icon --}}
        <div class="flex flex-col gap-1" aria-hidden="true">
            <span class="w-4 h-0.5 rounded-full bg-white/90"></span>
            <span class="w-4 h-0.5 rounded-full bg-white/90"></span>
            <span class="w-4 h-0.5 rounded-full bg-white/90"></span>
        </div>
    </button>

    <x-modal-overlay show="open" close="close()" role="menu" aria-modal="true" aria-label="{{ __('messages.a11y_footer_nav') }}">
        <div
            @click.stop
            class="fixed bottom-16 start-4 w-56
                   bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl rounded-xl shadow-2xl
                   border border-gray-200 dark:border-slate-700/50
                   overflow-hidden"
        >
            <div class="flex flex-col py-1">
                @foreach($menuItems as $item)
                    <a
                        href="{{ $item['route'] }}"
                        role="menuitem"
                        @if($item['active']) aria-current="page" @endif
                        class="flex items-center gap-3 px-4 py-2 text-sm transition-colors focus:outline-none
                            {{ $item['active']
                                ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-300 font-medium border-s-2 border-violet-500'
                                : 'text-slate-700 dark:text-slate-300 border-s-2 border-transparent' }}"
                    >
                        <svg class="w-4 h-4 shrink-0 {{ $item['active'] ? 'text-violet-600 dark:text-violet-400' : 'text-violet-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </x-modal-overlay>
</div>
