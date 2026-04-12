/**
 * Proof-of-Work solver using SHA-256.
 * Finds a nonce such that SHA-256(challenge || nonce) has N leading zero bits.
 */

const PARALLEL_BATCH = 256;
const YIELD_INTERVAL = 4096;
const DEFAULT_TIMEOUT = 30000;

/**
 * @param {string} challengeHex - Hex-encoded challenge from server
 * @param {number} difficulty - Required leading zero bits
 * @param {object} [options]
 * @param {number} [options.timeout=30000] - Timeout in ms
 * @param {function} [options.onProgress] - Called with current nonce count
 * @returns {Promise<string>} Hex-encoded nonce
 */
export async function solvePow(challengeHex, difficulty, options = {}) {
    const timeout = options.timeout || DEFAULT_TIMEOUT;
    const onProgress = options.onProgress || null;
    const startTime = Date.now();
    const challengeBytes = hexToBytes(challengeHex);

    let nonce = 0;

    while (true) {
        if (Date.now() - startTime > timeout) {
            throw new Error('pow_timeout');
        }

        // Fire parallel batch of SHA-256 digests
        const promises = [];
        for (let i = 0; i < PARALLEL_BATCH; i++) {
            const currentNonce = nonce + i;
            const nonceHex = currentNonce.toString(16).padStart(8, '0');
            const nonceBytes = hexToBytes(nonceHex);

            const data = new Uint8Array(challengeBytes.length + nonceBytes.length);
            data.set(challengeBytes, 0);
            data.set(nonceBytes, challengeBytes.length);

            promises.push(
                crypto.subtle.digest('SHA-256', data).then(function (hashBuffer) {
                    if (hasLeadingZeroBits(new Uint8Array(hashBuffer), difficulty)) {
                        return nonceHex;
                    }
                    return null;
                })
            );
        }

        const results = await Promise.all(promises);
        const winner = results.find(function (r) { return r !== null; });
        if (winner) {
            return winner;
        }

        nonce += PARALLEL_BATCH;

        // Yield to event loop periodically
        if (nonce % YIELD_INTERVAL === 0) {
            if (onProgress) {
                onProgress(nonce);
            }
            await new Promise(function (resolve) { setTimeout(resolve, 0); });
        }
    }
}

/**
 * @param {string} hex
 * @returns {Uint8Array}
 */
function hexToBytes(hex) {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substring(i, i + 2), 16);
    }
    return bytes;
}

/**
 * @param {Uint8Array} hash
 * @param {number} bits
 * @returns {boolean}
 */
function hasLeadingZeroBits(hash, bits) {
    var fullBytes = Math.floor(bits / 8);
    var remainingBits = bits % 8;

    for (var i = 0; i < fullBytes; i++) {
        if (hash[i] !== 0) {
            return false;
        }
    }

    if (remainingBits > 0) {
        var mask = 0xFF << (8 - remainingBits);
        if ((hash[fullBytes] & mask) !== 0) {
            return false;
        }
    }

    return true;
}
