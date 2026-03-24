export function t(key) {
    return window.translations?.[key] || key;
}

export function formatFileSize(bytes) {
    if (!bytes && bytes !== 0) {
        return '';
    }
    if (bytes < 1024) {
        return bytes + ' ' + t('unit_bytes');
    }
    if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(1) + ' ' + t('unit_kilobytes');
    }

    return (bytes / (1024 * 1024)).toFixed(1) + ' ' + t('unit_megabytes');
}

export function fetchHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    };
}

export function buildCipherMeta(encrypted) {
    const meta = {
        alg: 'AES-256-GCM',
        iv: encrypted.iv,
        version: encrypted.version,
        has_passphrase: !!encrypted.salt
    };

    if (encrypted.salt) {
        meta.salt = encrypted.salt;
        meta.iv2 = encrypted.iv2;
        meta.kdf = 'PBKDF2-SHA256-600k';
    }

    return meta;
}

export async function copyText(text) {
    await navigator.clipboard.writeText(text);
}
