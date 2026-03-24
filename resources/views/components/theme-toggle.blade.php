<label
    x-data="themeToggle"
    class="group relative w-18 h-8 rounded-xl cursor-pointer overflow-hidden select-none
           bg-linear-to-b from-sky-400 to-blue-600 dark:from-[#0f1b3a] dark:to-[#050b17]
           shadow-[inset_0_2px_6px_rgba(255,255,255,0.42),inset_0_-6px_12px_rgba(0,0,0,0.25),0_8px_14px_rgba(0,0,0,0.18)]
           ring-1 ring-transparent dark:ring-white/10
           transition-[background,box-shadow] duration-700 motion-reduce:transition-none"
>
    <input
        type="checkbox"
        role="switch"
        class="sr-only"
        :checked="dark"
        @change="toggle()"
        :aria-label="ariaLabel()"
        data-label-light="{{ __('messages.a11y_switch_light') }}"
        data-label-dark="{{ __('messages.a11y_switch_dark') }}"
    />

    {{-- Stars (night) --}}
    <div class="absolute inset-0 opacity-0 dark:opacity-100 transition-opacity duration-700 motion-reduce:transition-none" aria-hidden="true">
        <span class="star star--xs motion-reduce:!animate-none" style="top:20%;left:16%;animation-delay:.15s"></span>
        <span class="star star--xs motion-reduce:!animate-none" style="top:55%;left:38%;animation-delay:.55s"></span>
        <span class="star motion-reduce:!animate-none" style="top:22%;left:62%;animation-delay:.9s"></span>
        <span class="star star--xs motion-reduce:!animate-none" style="top:50%;left:80%;animation-delay:1.2s"></span>
    </div>

    {{-- Cloud (day) - main --}}
    <div class="absolute bottom-1.5 left-8.5 w-3.5 h-1.5 rounded-full bg-white
                shadow-[-6px_-4px_0_4px_white,4px_-6px_0_4px_white,14px_-2px_0_4px_white,22px_0_0_2px_white,4px_2px_4px_rgba(0,0,0,0.06)]
                transition-all duration-700 motion-reduce:transition-none
                dark:opacity-0 dark:-translate-x-2 dark:blur-[1px]" aria-hidden="true"></div>

    {{-- Cloud (day) - small --}}
    <div class="absolute bottom-4.5 left-14 w-2 h-1 rounded-full bg-white/90
                shadow-[-4px_-2px_0_2px_rgba(255,255,255,0.9),3px_-4px_0_2px_rgba(255,255,255,0.9),8px_0_0_1px_rgba(255,255,255,0.9),2px_1px_3px_rgba(0,0,0,0.04)]
                transition-all duration-700 delay-75 motion-reduce:transition-none
                dark:opacity-0 dark:-translate-x-2 dark:blur-[1px]" aria-hidden="true"></div>

    {{-- Glow overlay --}}
    <div class="absolute inset-0 opacity-100 transition-opacity duration-700 motion-reduce:transition-none
                bg-[radial-gradient(72px_48px_at_30%_40%,rgba(255,255,255,0.35),transparent_60%)]
                dark:opacity-70 dark:bg-[radial-gradient(88px_60px_at_70%_35%,rgba(255,255,255,0.16),transparent_60%)]" aria-hidden="true"></div>

    {{-- Thumb wrapper --}}
    <div class="absolute top-1 left-1 w-6 h-6 rounded-full transition-transform duration-700 ease-[cubic-bezier(.2,.9,.2,1)] motion-reduce:transition-none
                dark:translate-x-10" aria-hidden="true">
        {{-- Sun --}}
        <div class="absolute inset-0 rounded-full
                    bg-[radial-gradient(circle_at_30%_30%,#fff7b1,#ffc933_60%,#f4a000)]
                    shadow-[0_6px_12px_rgba(0,0,0,0.25),inset_0_2px_4px_rgba(255,255,255,0.5),inset_0_-6px_10px_rgba(0,0,0,0.12)]
                    transition-opacity duration-500 motion-reduce:transition-none dark:opacity-0"></div>

        {{-- Moon --}}
        <div class="absolute inset-0 rounded-full opacity-0 dark:opacity-100 transition-opacity duration-500 motion-reduce:transition-none
                    bg-[radial-gradient(circle_at_30%_30%,#ffffff,#d9dde8_62%,#a9b2c7)]
                    shadow-[0_6px_14px_rgba(0,0,0,0.40),inset_-6px_-6px_0_rgba(0,0,0,0.10),inset_6px_6px_0_rgba(255,255,255,0.15)]">
            <span class="absolute top-1.5 left-2.5 w-1 h-1 rounded-full bg-slate-300/70 shadow-[inset_2px_2px_0_rgba(255,255,255,0.30)]"></span>
            <span class="absolute top-3.5 left-4 w-1.5 h-1.5 rounded-full bg-slate-400/50 shadow-[inset_2px_2px_0_rgba(255,255,255,0.25)]"></span>
        </div>
    </div>
</label>
