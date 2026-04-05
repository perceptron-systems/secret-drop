<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class GeoController extends Controller
{
    public function llmsTxt(): Response
    {
        $base = url('/en');
        $github = config('legal.social.github', 'https://github.com/perceptron-systems/secret-drop');
        $website = config('legal.social.website', 'https://www.orsal.fr');
        $contactUrl = url('/contact');
        $editorName = config('legal.editor_name', 'Secret Drop');
        $fullUrl = url('/llms-full.txt');

        $content = <<<TXT
        # Secret Drop

        > Free, open-source zero-knowledge secret sharing app. End-to-end encrypted text and file sharing with self-destructing links — the server never sees your data.

        For full documentation, see: {$fullUrl}

        ## Docs

        - [Homepage]({$base}): Create encrypted, self-destructing links to share passwords, files, and sensitive text. No account required. Available in 11 languages.
        - [How It Works]({$base}/how-it-works): Technical explanation of the zero-knowledge encryption process using AES-256-GCM via Web Crypto API. Covers the 6-step encrypt-share-decrypt flow.
        - [Use Cases]({$base}/use-cases): Six practical scenarios for sharing passwords, API keys, confidential documents, SSH keys, Wi-Fi credentials, and personal data with recommended security settings.
        - [FAQ]({$base}/faq): Answers to common questions about encryption, privacy, GDPR compliance, file size limits (10 MB), open-source licensing (AGPL-3.0), and secret management.
        - [Legal Notice]({$base}/legal-notice): Operator identity, OVH Cloud hosting details (Roubaix, France), cookie policy, and GDPR data processing information.
        - [Source Code]({$github}): Full source code under AGPL-3.0. Built with Laravel, Alpine.js, and Tailwind CSS.

        ## Key Facts

        - Created by {$editorName}
        - Hosted in France on OVH Cloud servers (Roubaix)
        - Open source under GNU AGPL-3.0 license
        - Encryption: AES-256-GCM via Web Crypto API, PBKDF2 with SHA-256 and 600,000 iterations for passphrase derivation
        - Supports text (up to 50,000 characters) and files (up to 10 MB)
        - Available in 11 languages: English, French, German, Spanish, Italian, Portuguese, Dutch, Polish, Japanese, Korean, Arabic
        - No account required, no tracking, no third-party cookies
        - GDPR compliant by design — server stores only encrypted data, never plaintext or keys
        - Configurable expiration from 1 hour to 90 days, single-use or limited view options

        ## Contact

        - Creator website: {$website}
        - Contact form: {$contactUrl}
        - Source code: {$github}
        TXT;

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function llmsFullTxt(): Response
    {
        $base = url('/en');
        $github = config('legal.social.github', 'https://github.com/perceptron-systems/secret-drop');
        $website = config('legal.social.website', 'https://www.orsal.fr');
        $contactUrl = url('/contact');

        $siteUrl = url('');
        $content = file_get_contents(resource_path('llms-full.txt'));
        $content = str_replace(
            ['WEBSITE_URL', 'CONTACT_URL', 'GITHUB_URL', 'BASE_URL', 'SITE_URL', 'EDITOR_NAME'],
            [$website, $contactUrl, $github, $base, $siteUrl, config('legal.editor_name', 'Secret Drop')],
            $content,
        );

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function securityTxt(): Response
    {
        $email = config('legal.contact_email', config('mail.from.address'));
        $canonical = url('/.well-known/security.txt');
        $expires = now()->addYear()->utc()->format('Y-m-d\TH:i:s\Z');

        $content = <<<TXT
            Contact: mailto:{$email}
            Expires: {$expires}
            Preferred-Languages: fr, en
            Canonical: {$canonical}
            TXT;

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
