import { t, formatFileSize, copyText } from '../utils.js';

export default () => ({
        token: null,
        isLoading: true,
        loadError: null,

        // Data from API
        type: null,
        cipherMeta: null,
        willBeDestroyed: false,

        // Text mode
        ciphertext: null,
        plaintext: '',

        // File mode
        filename: null,
        mime: null,
        size: null,

        // Common
        passphrase: '',
        isDecrypting: false,
        decrypted: false,
        error: null,
        copied: false,
        needsPassphrase: false,
        needsManualKey: false,
        manualKey: '',
        keyMaterial: null,
        version: 1,
        awaitingConfirmation: false,

        async init() {
            this.token = this.$el.dataset.token;
            await this.loadSecret();

            if (!this.loadError && !this.needsPassphrase && !this.needsManualKey && !this.error) {
                if (this.willBeDestroyed) {
                    this.awaitingConfirmation = true;
                } else {
                    this.decrypt();
                }
            }
        },

        confirmAndDecrypt() {
            this.awaitingConfirmation = false;
            this.decrypt();
        },

        async loadSecret() {
            try {
                const response = await fetch(`/api/secrets/${this.token}`);
                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 404) {
                        const hasKey = !!location.hash && location.hash.length > 1;
                        this.loadError = {
                            type: hasKey ? 'unavailable' : 'not_found',
                            message: hasKey ? t('secret_unavailable_generic') : t('secret_not_exist'),
                        };
                    } else {
                        this.loadError = {
                            type: 'error',
                            message: t('error_loading'),
                        };
                    }

                    return;
                }

                this.type = data.type;
                this.cipherMeta = data.cipher_meta;
                this.willBeDestroyed = data.will_be_destroyed;

                if (data.type === 'text') {
                    this.ciphertext = data.ciphertext;
                }
                // For files, metadata (filename, mime, size) is encrypted in the payload

                this.parseFragment();
            } catch (e) {
                this.loadError = {
                    type: 'error',
                    message: t('error_connection'),
                };
            } finally {
                this.isLoading = false;
            }
        },

        getUnavailableMessage(reason) {
            switch (reason) {
                case 'expired':
                    return t('secret_expired');
                case 'revoked':
                    return t('secret_revoked');
                case 'already_read':
                    return t('secret_max_views');
                case 'max_views':
                    return t('secret_max_views');
                default:
                    return t('secret_unavailable_generic');
            }
        },

        parseFragment() {
            const fragment = window.location.hash.substring(1);

            if (!fragment) {
                this.needsManualKey = true;
                return;
            }

            this.applyKeyFragment(fragment);
        },

        applyKeyFragment(fragment) {
            try {
                const parsed = window.SecretCrypto.parseKeyFragment(fragment);
                this.keyMaterial = parsed.keyMaterial;
                this.version = parsed.version;
                this.needsManualKey = false;
                // Passphrase requirement comes from server metadata, not fragment
                this.needsPassphrase = this.cipherMeta?.has_passphrase || false;
            } catch (e) {
                this.error = e.message || t('crypto_fragment_invalid');
            }
        },

        submitManualKey() {
            if (!this.manualKey.trim()) {
                return;
            }

            this.error = null;
            this.applyKeyFragment(this.manualKey.trim());

            if (!this.error && !this.needsPassphrase) {
                if (this.willBeDestroyed) {
                    this.awaitingConfirmation = true;
                } else {
                    this.decrypt();
                }
            }
        },

        async decrypt() {
            if (this.error && !this.needsPassphrase) {
                return;
            }

            this.error = null;
            this.isDecrypting = true;

            try {
                if (!window.SecretCrypto?.isCryptoAvailable()) {
                    throw new Error(t('crypto_not_supported'));
                }

                const salt = this.cipherMeta.salt || null;
                const passphrase = this.needsPassphrase ? this.passphrase : null;

                if (this.needsPassphrase && !passphrase?.trim()) {
                    throw new Error(t('crypto_passphrase_required'));
                }

                if (this.type === 'text') {
                    await this.decryptText(salt, passphrase);
                } else {
                    await this.decryptFile(salt, passphrase);
                }

                this.decrypted = true;
                await this.confirmRead();

                // Focus management for a11y
                this.$nextTick(() => {
                    const content = this.$el.querySelector('pre, [x-show="decrypted && type === \'file\'"]');
                    if (content) {
                        content.setAttribute('tabindex', '-1');
                        content.focus();
                    }
                });
            } catch (e) {
                if (e.name === 'OperationError') {
                    this.error = this.needsPassphrase
                        ? t('crypto_passphrase_incorrect')
                        : t('crypto_decryption_failed');
                } else {
                    this.error = e.message || t('crypto_decryption_error');
                }

                // Focus management for a11y - focus back to input on error
                this.$nextTick(() => {
                    if (this.needsPassphrase) {
                        this.$el.querySelector('#passphrase')?.focus();
                    } else if (this.needsManualKey) {
                        this.$el.querySelector('#manualKey')?.focus();
                    }
                });
            } finally {
                this.isDecrypting = false;
            }
        },

        async decryptText(salt, passphrase) {
            const iv2 = this.cipherMeta.iv2 || null;
            this.plaintext = await window.SecretCrypto.decryptSecret(
                this.ciphertext,
                this.cipherMeta.iv,
                this.keyMaterial,
                salt,
                iv2,
                passphrase,
                this.version
            );
        },

        async decryptFile(salt, passphrase) {
            const response = await fetch(`/s/${this.token}/download`);
            if (!response.ok) {
                throw new Error(t('crypto_file_download_failed'));
            }

            const encryptedData = await response.arrayBuffer();
            const iv2 = this.cipherMeta.iv2 || null;

            const result = await window.SecretCrypto.decryptFile(
                encryptedData,
                this.cipherMeta.iv,
                this.keyMaterial,
                salt,
                iv2,
                passphrase,
                this.version
            );

            // Update component state with decrypted metadata
            this.filename = result.filename;
            this.mime = result.mime;
            this.size = result.size;

            const blob = new Blob([result.data], { type: result.mime });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = result.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            URL.revokeObjectURL(url);
        },

        formatFileSize,

        async copyToClipboard() {
            try {
                await copyText(this.plaintext);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (e) {
                this.error = t('crypto_clipboard_failed');
            }
        },

        async confirmRead() {
            try {
                await fetch(`/api/secrets/${this.token}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
            } catch {
                // Network error — silent, secret was already decrypted
            }
        },

        secretTypeTitle() {
            return this.type === 'text' ? t('secret_message') : t('secret_file');
        },

        encryptedDescription() {
            return this.type === 'text' ? t('encrypted_end_to_end_message') : t('encrypted_end_to_end_file');
        },

        decryptingText() {
            return this.type === 'text' ? t('decrypting_message') : t('decrypting_file');
        },

        reload() {
            window.location.reload();
        },

        errorMessage() {
            return this.error || (this.loadError ? this.loadError.message : '');
        },

        isNotFound() {
            return this.loadError && this.loadError.type === 'not_found';
        },

        isUnavailable() {
            return this.loadError && this.loadError.type === 'unavailable';
        },

        isGenericError() {
            return this.loadError && this.loadError.type === 'error';
        },

        loadErrorMessage() {
            return this.loadError ? this.loadError.message : '';
        },


        clearRetryError() {
            this.error = null;
            if (this.needsManualKey) {
                this.manualKey = '';
            }
        }
});
