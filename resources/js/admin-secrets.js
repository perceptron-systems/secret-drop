import { fetchHeaders } from './utils.js';

export default () => ({
        extendDays: '7',
        showRevokeModal: false,
        pendingRevokeId: null,
        pendingRevokeEl: null,
        errorMessage: '',

        init() {
            this.$watch('showRevokeModal', (value) => {
                if (value) {
                    this.$nextTick(() => {
                        this.$refs.confirmRevokeBtn.focus();
                    });
                }
            });
        },

        buildUrl(template, secretId) {
            return this.$el.dataset[template].replace('__ID__', secretId);
        },

        openRevokeModal(buttonEl) {
            this.pendingRevokeId = buttonEl.dataset.secretId;
            this.pendingRevokeEl = buttonEl;
            this.showRevokeModal = true;
        },

        closeRevokeModal() {
            this.showRevokeModal = false;
            const triggerEl = this.pendingRevokeEl;
            this.pendingRevokeId = null;
            this.pendingRevokeEl = null;

            if (triggerEl) {
                this.$nextTick(() => triggerEl.focus());
            }
        },

        async confirmRevoke() {
            if (this.pendingRevokeId && this.pendingRevokeEl) {
                this.showRevokeModal = false;
                await this.revoke(this.pendingRevokeId, this.pendingRevokeEl);
            }
        },

        showError(message) {
            this.errorMessage = message;

            setTimeout(() => {
                this.errorMessage = '';
            }, 5000);
        },

        async extend(buttonEl) {
            const secretId = buttonEl.dataset.secretId;
            const card = buttonEl.closest('[x-data]');
            const data = Alpine.$data(card);
            data.extending = true;

            try {
                const response = await fetch(this.buildUrl('extendUrl', secretId), {
                    method: 'POST',
                    headers: fetchHeaders(),
                    body: JSON.stringify({ days: parseInt(this.extendDays) }),
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    const result = await response.json();
                    this.showError(result.error || 'Error extending secret');
                    buttonEl.focus();
                }
            } catch {
                this.showError('Connection error');
                buttonEl.focus();
            } finally {
                data.extending = false;
            }
        },

        async revoke(secretId, buttonEl) {
            const card = buttonEl.closest('[x-data]');
            const data = Alpine.$data(card);
            data.revoking = true;

            try {
                const response = await fetch(this.buildUrl('revokeUrl', secretId), {
                    method: 'POST',
                    headers: fetchHeaders(),
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    const result = await response.json();
                    this.showError(result.error || 'Error revoking secret');
                    buttonEl.focus();
                }
            } catch {
                this.showError('Connection error');
                buttonEl.focus();
            } finally {
                data.revoking = false;
            }
        },
});
