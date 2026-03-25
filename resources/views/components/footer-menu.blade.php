@php
    $currentRoute = request()->route()?->getName();
    $currentSlug = request()->route()?->parameter('pageSlug');

    $menuItems = [
        ['route' => route('home'), 'active' => $currentRoute === 'home', 'label' => __('messages.btn_create_new'), 'iconName' => 'icon.home'],
        ['route' => localized_route('how-it-works'), 'active' => $currentRoute === 'page.show' && $currentSlug === trans('routes.how-it-works'), 'label' => __('messages.footer_how_it_works'), 'iconName' => 'icon.info'],
        ['route' => localized_route('use-cases'), 'active' => $currentRoute === 'page.show' && $currentSlug === trans('routes.use-cases'), 'label' => __('messages.footer_use_cases'), 'iconName' => 'icon.briefcase'],
        ['route' => localized_route('faq'), 'active' => $currentRoute === 'page.show' && $currentSlug === trans('routes.faq'), 'label' => 'FAQ', 'iconName' => 'icon.question-mark-circle'],
        ['route' => route('admin.index'), 'active' => str_starts_with($currentRoute ?? '', 'admin.'), 'label' => __('messages.footer_manage'), 'iconName' => 'icon.key'],
        ['route' => localized_route('legal'), 'active' => $currentRoute === 'page.show' && $currentSlug === trans('routes.legal'), 'label' => __('messages.footer_legal'), 'iconName' => 'icon.document'],
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
                        <x-dynamic-component :component="$item['iconName']" class="w-4 h-4 shrink-0 {{ $item['active'] ? 'text-violet-600 dark:text-violet-400' : 'text-violet-500' }}" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </x-modal-overlay>
</div>
