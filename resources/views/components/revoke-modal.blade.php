<x-modal-overlay show="showRevokeModal" close="closeRevokeModal()" role="dialog" aria-modal="true" aria-labelledby="revoke-modal-title" aria-describedby="revoke-modal-description">
    <div class="flex items-center justify-center min-h-full p-4">
        <div @click.stop>
        <x-card class="max-w-md w-full p-8 text-center">
            <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-red-500/0 to-rose-600 mb-4" style="--accent-rgb: 239, 68, 68" aria-hidden="true">
                <x-icon.warning class="w-7 h-7 text-white" />
            </div>
            <h3 id="revoke-modal-title" class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_revoke') }}</h3>
            <p id="revoke-modal-description" class="text-gray-600 dark:text-slate-400 mb-6">{{ __('messages.admin_revoke_confirm') }}</p>
            <div class="flex justify-center gap-3">
                <button
                    @click="closeRevokeModal()"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-xl transition cursor-pointer"
                >
                    {{ __('messages.btn_cancel') }}
                </button>
                <button
                    @click="confirmRevoke()"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-500 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 cursor-pointer"
                >
                    {{ __('messages.admin_revoke') }}
                </button>
            </div>
        </x-card>
        </div>
    </div>
</x-modal-overlay>
