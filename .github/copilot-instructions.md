# Copilot Instructions

This file provides guidance to GitHub Copilot when working with code in this repository.

## Project Goal

**Secret-drop** is a zero-knowledge secret/file sharing application. The server NEVER sees plaintext secrets.


### Core Principles (non-negotiable)

- **Client-side encryption**: Browser generates key and encrypts using WebCrypto API
- **Server stores only**: ciphertext + iv + salt + metadata (never the key, never plaintext)
- **Key transmission**: Via URL fragment (`#...`) which is never sent to server
- **Algorithms**: AES-256-GCM for encryption, PBKDF2 for optional passphrase derivation
- **Encoding**: Base64URL for ciphertext, iv, salt

### Features

- Create encrypted secret (text or file) with expiration, single-use option, max views
- Share via link with key in fragment: `https://host/s/{token}#{key_material}`
- Read page fetches ciphertext, JS decrypts locally using key from `location.hash`
- Admin access without accounts: magic link email flow
- Revocation, expiration extension, read tracking (first_read_at, read_count)
- Email sending with responsive templates (DKIM via infra)
- Optional: passphrase protection, split mode (link + key separate), PGP for email

### Data Model

- `secrets`: token, type (text|file), cipher_meta (JSON), ciphertext/file_path, max_views, read_count, expire_at, revoked_at, creator_email_hash, admin_token
- `magic_links`: secret_id, email, token_hash, expire_at, used_at

### Security Constraints

- XSS is critical (would compromise secrets) → strict CSP already in place
- Tokens must be >= 128 bits, non-guessable
- Never log: full URLs, tokens, admin_tokens, ciphertext
- Rate limiting on creation and admin access

## Development Commands

```bash
# Initial setup (install deps, generate key, migrate, build assets)
composer setup

# Development server with concurrent processes (Laravel, queue, pail logs, Vite)
composer dev

# Run tests
composer test

# Run a single test
php artisan test --filter=TestClassName
php artisan test tests/Feature/ExampleTest.php

# Build frontend assets
npm run build
npm run dev
```

## Architecture

### Stack

- Laravel 13 with PHP 8.2+
- Alpine.js 3.15 (CSP build) + Tailwind CSS 4.2 + Vite
- MySQL or SQLite database

### Security Layer

This project has a security-first architecture with custom middleware and logging:

**Middleware** (`app/Http/Middleware/`):

- `ForceHttps` - Redirects to HTTPS and forces scheme in production
- `SecurityHeaders` - Adds CSP, HSTS, X-Frame-Options, and other security headers

**CSP Nonce System**:

- Nonce generated as singleton in `AppServiceProvider`
- Helper function `csp_nonce()` in `app/helpers.php`
- Blade directive `@nonce` for inline scripts/styles
- Vite configured to use CSP nonce automatically

**Log Sanitization** (`app/Logging/SanitizeProcessor.php`):

- Custom Monolog processor that redacts sensitive data (passwords, tokens, API keys, SSN, etc.)
- Applied to all logging channels via `config/logging.php`

### Key Files

- `app/helpers.php` - Global helper functions (autoloaded via composer)
- `app/Providers/AppServiceProvider.php` - CSP nonce registration and Blade/Vite configuration
