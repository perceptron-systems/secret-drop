# Secret Drop

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)

Zero-knowledge secret sharing — end-to-end encrypted, self-destructing, open source.

**[Live demo](https://secret.orsal.fr)** · **[How it works](https://secret.orsal.fr/en/how-it-works)**

## Screenshot

![Secret Drop — Create a secret](screenshots/home.png)

## Why

Passwords sent over email or Slack remain readable by every intermediary — indefinitely. Secret Drop encrypts everything in the browser before it ever leaves your screen. The server only ever sees noise.

## How it works

1. You type a secret (text or file) in your browser
2. Your browser generates a key and encrypts locally (AES-256-GCM via Web Crypto API)
3. Only the ciphertext reaches the server — it has no way to decrypt it
4. The decryption key stays in the URL fragment (`#`), which is never sent to the server
5. The recipient opens the link and their browser decrypts locally
6. Once read, the secret is permanently destroyed

## Features

- **Zero-knowledge** — the server cannot read your data; not a policy, a mathematical impossibility
- **End-to-end encryption** — AES-256-GCM with browser-generated keys
- **Self-destructing** — configurable expiration and read limits
- **File sharing up to 10 MB** — encrypted before upload
- **No account, no password** — magic link authentication with nothing to steal
- **11 languages** — fr, en, de, es, it, pt, nl, pl, ja, ko, ar
- **Admin dashboard** — revoke or extend secrets via magic link
- **Stats dashboard** — pageviews, bots, devices, heatmaps, referrers
- **Strict CSP** — nonce-based, compatible with Alpine.js CSP build
- **AGPL-3.0** — fully auditable source code

## Stack

Laravel 13 · PHP 8.2+ · Alpine.js 3 (CSP build) · Tailwind CSS 4 · Vite · MySQL/SQLite

## Quick start

```bash
git clone https://github.com/perceptron-systems/secret-drop.git
cd secret-drop
composer setup    # install deps, generate key, migrate, build
composer dev      # start dev server
```

## Tests

```bash
composer test                          # full suite (370+ tests)
php artisan test --filter=SecretTest   # run a single test
```

## Security

| What the server stores | What it never sees |
|------------------------|--------------------|
| Ciphertext (AES-256-GCM) | Plaintext content |
| IV, salt, metadata | Encryption key |
| Hashed email (SHA-256) | URL fragment (`#key`) |

Additional measures: strict CSP with nonce, HSTS, log sanitization (tokens and secrets are never logged), rate limiting with SHA-256 proof-of-work, honeypot, daily per-IP limits, file storage quota, CORS restriction, DKIM email signing.

## Deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
```

Add the Laravel scheduler to crontab (required for automatic cleanup of expired secrets):
```cron
* * * * * cd /path/to/secret-drop && php artisan schedule:run >> /dev/null 2>&1
```

See `.env.production.example` for all available configuration options.

## CI/CD

GitHub Actions: Pint (code style) → Larastan (static analysis) → PHPUnit → deploy via SSH.

## License

AGPL-3.0 — see [LICENSE](LICENSE)

---

Built by [Perceptron Systems](https://github.com/perceptron-systems) · [Guillaume Orsal](https://www.orsal.fr) · © 2026
