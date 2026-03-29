<div
    x-data="{ expanded: false, extending: false, revoking: false }"
    data-secret-id="{{ $secret->id }}"
    class="card-accent bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-xl overflow-hidden transition-colors"
>
    {{-- Secret header --}}
    <button type="button" class="w-full p-5 flex items-center justify-between cursor-pointer text-left" @click="expanded = !expanded" :aria-expanded="expanded" aria-label="{{ __('messages.a11y_expand_secret') }}">
        <div class="flex items-center gap-4">
            {{-- Type icon --}}
            <div class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0 {{ $secret->type === 'text' ? 'bg-violet-100 dark:bg-violet-500/10' : 'bg-indigo-100 dark:bg-indigo-500/10' }}">
                @if($secret->type === 'text')
                    <x-icon.document class="w-5 h-5 text-violet-600 dark:text-violet-300" />
                @else
                    <x-icon.file class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                @endif
            </div>

            <div>
                <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ $secret->type === 'text' ? __('messages.type_text') : __('messages.type_file') }}
                    </span>
                    {{-- Status badge --}}
                    <span data-poll-badge>
                        @if($secret->isRevoked())
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                {{ __('messages.admin_status_revoked') }}
                            </span>
                        @elseif($secret->isExpired())
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                {{ __('messages.admin_status_expired') }}
                            </span>
                        @elseif($secret->hasReachedMaxViews())
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300">
                                {{ __('messages.admin_status_consumed') }}
                            </span>
                        @else
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                {{ __('messages.admin_status_active') }}
                            </span>
                        @endif
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-slate-400">
                    {{ __('messages.admin_created') }}: <span data-utc="{{ $secret->created_at->toIso8601String() }}"></span>
                </p>
            </div>
        </div>

        <x-icon.chevron-down class="w-5 h-5 text-gray-400 transition-transform" x-bind:class="{ 'rotate-180': expanded }" />
    </button>

    {{-- Expanded content --}}
    <div x-show="expanded" x-collapse class="border-t border-gray-200 dark:border-slate-700/50">
        <div class="p-5 space-y-4">
            {{-- Info grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="p-3 bg-gray-50 dark:bg-slate-900/50 rounded-xl">
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.admin_expires') }}</p>
                    <p data-poll-expire class="text-sm font-medium text-gray-900 dark:text-white"><span data-utc="{{ $secret->expire_at->toIso8601String() }}"></span></p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-900/50 rounded-xl">
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.admin_read_count') }}</p>
                    <p data-poll-reads class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $secret->read_count }}
                        @if($secret->max_views)
                            <span class="text-gray-400 dark:text-slate-500">/ {{ $secret->max_views }}</span>
                        @endif
                    </p>
                </div>
                <div data-poll-first-read class="p-3 bg-gray-50 dark:bg-slate-900/50 rounded-xl {{ $secret->first_read_at ? '' : 'hidden' }}">
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.admin_first_read') }}</p>
                    <p data-poll-first-read-value class="text-sm font-medium text-gray-900 dark:text-white"><span data-utc="{{ $secret->first_read_at?->toIso8601String() }}"></span></p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-900/50 rounded-xl">
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.admin_mode') }}</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $secret->max_views ? trans_choice('messages.admin_limited_views', $secret->max_views, ['count' => $secret->max_views]) : __('messages.admin_unlimited') }}
                    </p>
                </div>
            </div>

            {{-- Actions --}}
            @if(!$secret->isRevoked())
                <div data-poll-actions class="flex flex-col sm:flex-row gap-3 pt-2">
                    {{-- Extend --}}
                    <div class="flex items-center gap-2">
                        <select
                            data-extend-select
                            aria-label="{{ __('messages.a11y_extend_days') }}"
                            class="px-3 py-2 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
                        >
                            <option value="1">+1 {{ __('messages.admin_hour') }}</option>
                            <option value="24">+1 {{ __('messages.admin_day') }}</option>
                            <option value="168">+7 {{ __('messages.admin_days') }}</option>
                            <option value="720">+30 {{ __('messages.admin_days') }}</option>
                        </select>
                        <button
                            data-secret-id="{{ $secret->id }}"
                            @click="extend($el)"
                            :disabled="extending"
                            class="relative px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-xl disabled:opacity-50 transition cursor-pointer"
                        >
                            <span :class="extending && 'invisible'">{{ __('messages.admin_extend') }}</span>
                            <span x-show="extending" class="absolute inset-0 flex items-center justify-center">
                                <x-spinner />
                            </span>
                        </button>
                    </div>

                    {{-- Revoke --}}
                    @if($secret->isAccessible())
                        <button
                            data-secret-id="{{ $secret->id }}"
                            @click="openRevokeModal($el)"
                            :disabled="revoking"
                            class="relative px-4 py-2 text-red-600 dark:text-red-300 border border-red-300 dark:border-red-500/30 hover:bg-red-50 dark:hover:bg-red-500/10 text-sm font-medium rounded-xl disabled:opacity-50 transition cursor-pointer"
                        >
                            <span :class="revoking && 'invisible'">{{ __('messages.admin_revoke') }}</span>
                            <span x-show="revoking" class="absolute inset-0 flex items-center justify-center">
                                <x-spinner />
                            </span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
