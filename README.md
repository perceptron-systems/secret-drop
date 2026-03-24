# Secret Drop

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)

Application open source de partage de secrets et fichiers chiffrés côté client. Le serveur ne voit jamais les données en clair.

## Principe Zero-Knowledge

- Le navigateur génère la clé et chiffre localement (AES-256-GCM)
- Le serveur stocke uniquement le ciphertext + métadonnées
- La clé est transmise via le fragment URL (`#...`) qui n'est jamais envoyé au serveur
- Passphrase optionnelle avec dérivation PBKDF2

## Fonctionnalités

- **Secrets texte** : messages chiffrés avec copie facile
- **Fichiers chiffrés** : upload/download avec chiffrement côté client (jusqu'à 10 Mo)
- **Usage unique** : destruction après première lecture
- **Expiration** : 1h, 1j, 7j, 30j ou 90j
- **Limite de lectures** : max_views configurable
- **Passphrase** : protection supplémentaire optionnelle
- **Mode split** : lien et clé séparés pour un partage plus sécurisé
- **QR code** : partage mobile du lien
- **Révocation** : annulation immédiate via le dashboard admin
- **Administration** : accès par magic link (sans compte), suivi des lectures, extension d'expiration
- **Super admin** : dashboard de statistiques avec graphiques, heatmaps, pageviews
- **11 langues** : fr, en, de, es, it, pt, nl, pl, ja, ko, ar
- **Dark mode** : thème clair/sombre

## Stack technique

- **Backend** : Laravel 13, PHP 8.2+
- **Frontend** : Alpine.js 3.15 (build CSP), Tailwind CSS 4.2, Vite
- **Crypto** : Web Crypto API (navigateur)
- **Base de données** : MySQL ou SQLite

## Installation

```bash
# Cloner le repo
git clone https://github.com/perceptron-systems/secret-drop.git
cd secret-drop

# Installation et configuration
composer setup

# Lancer en développement
composer dev
```

La commande `composer setup` exécute :
- Installation des dépendances PHP et npm
- Génération de la clé d'application
- Migration de la base de données
- Build des assets

## Commandes

### Développement

```bash
composer dev      # Serveur + queue + logs + Vite
composer test     # Tests PHPUnit
npm run build     # Build production
```

### Artisan

```bash
# Nettoyer les secrets expirés/révoqués/consommés
php artisan secrets:clean

# Supprimer les fichiers orphelins (sans secret correspondant)
php artisan secrets:clean-blobs

# Mode dry-run (affiche sans supprimer)
php artisan secrets:clean --dry-run
php artisan secrets:clean-blobs --dry-run
```

`secrets:clean` supprime :
- Les secrets expirés (`expire_at` dépassé)
- Les secrets révoqués (`revoked_at` défini)
- Les secrets ayant atteint leur limite de lectures
- Les secrets à usage unique déjà lus
- Les magic links expirés ou utilisés

`secrets:clean-blobs` supprime les fichiers stockés qui n'ont plus de secret correspondant en base.

## API

### Endpoints API

| Méthode | URL | Description |
|---------|-----|-------------|
| `POST` | `/api/secrets` | Créer un secret |
| `GET` | `/api/secrets/{token}` | Récupérer les métadonnées + ciphertext |
| `POST` | `/api/secrets/{token}/read` | Confirmer la lecture (après déchiffrement) |
| `POST` | `/api/secrets/{adminToken}/revoke` | Révoquer un secret |

### Routes web publiques

| Méthode | URL | Description |
|---------|-----|-------------|
| `GET` | `/` | Redirection vers `/{locale}` |
| `GET` | `/{locale}` | Page de création |
| `GET` | `/{locale}/{pageSlug}` | Pages statiques localisées |
| `GET` | `/s/{token}` | Page de lecture d'un secret |
| `GET` | `/s/{token}/download` | Télécharger un fichier chiffré |
| `GET` | `/contact` | Page de contact |

### Routes admin

| Méthode | URL | Description |
|---------|-----|-------------|
| `GET` | `/{locale}/admin` | Page de connexion admin |
| `POST` | `/{locale}/admin/request-access` | Demander un magic link |
| `GET` | `/{locale}/admin/verify/{token}` | Vérifier le magic link |
| `GET` | `/{locale}/admin/dashboard` | Dashboard admin |
| `POST` | `/{locale}/admin/secrets/{id}/revoke` | Révoquer un secret |
| `POST` | `/{locale}/admin/secrets/{id}/extend` | Étendre l'expiration |
| `POST` | `/{locale}/admin/logout` | Déconnexion admin |

### Routes super admin

| Méthode | URL | Description |
|---------|-----|-------------|
| `GET` | `/{locale}/superadmin` | Page de connexion super admin |
| `POST` | `/{locale}/superadmin/request-access` | Demander un magic link |
| `GET` | `/{locale}/superadmin/verify/{token}` | Vérifier le magic link |
| `GET` | `/{locale}/superadmin/dashboard` | Dashboard statistiques |
| `GET` | `/{locale}/superadmin/dashboard/poll` | Polling des données en temps réel |
| `POST` | `/{locale}/superadmin/logout` | Déconnexion super admin |

### Routes SEO & utilitaires

| Méthode | URL | Description |
|---------|-----|-------------|
| `GET` | `/robots.txt` | Fichier robots |
| `GET` | `/sitemap.xml` | Sitemap XML |
| `GET` | `/.well-known/security.txt` | Fichier security.txt |

## Sécurité

### Ce que le serveur ne voit jamais
- Le secret en clair
- La clé de chiffrement
- Le fragment URL

### Mesures de protection
- CSP stricte avec nonce (compatible Alpine.js CSP build)
- Headers de sécurité (HSTS, X-Frame-Options, etc.)
- Sanitization des logs (pas de tokens, pas de secrets)
- Tokens cryptographiquement sécurisés (128+ bits)
- Rate limiting progressif avec captcha

### Données stockées
- `ciphertext` : contenu chiffré (base64url)
- `cipher_meta` : iv, salt, version de l'algorithme
- Métadonnées : type, expiration, compteurs

## Configuration

Variables d'environnement principales :

```env
APP_URL=https://your-domain.com
APP_ENV=production

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=secretdrop
DB_USERNAME=secretdrop
DB_PASSWORD=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com

# Super Admin
SUPER_ADMIN_EMAIL=admin@example.com
```

### Configuration DKIM (optionnel)

Si votre serveur SMTP ne signe pas les emails en DKIM, l'application peut le faire :

```bash
# Générer la clé privée
mkdir -p storage/dkim
openssl genrsa -out storage/dkim/private.key 2048
chmod 600 storage/dkim/private.key

# Extraire la clé publique pour le DNS
openssl rsa -in storage/dkim/private.key -pubout -out storage/dkim/public.key
cat storage/dkim/public.key | grep -v "PUBLIC KEY" | tr -d '\n'
```

Puis configurer dans `.env` :
```env
MAIL_DKIM_DOMAIN=votredomaine.com
MAIL_DKIM_SELECTOR=secretdrop
MAIL_DKIM_PRIVATE_KEY_PATH=storage/dkim/private.key
```

Et ajouter l'enregistrement DNS TXT :
```
secretdrop._domainkey.votredomaine.com  TXT  "v=DKIM1; k=rsa; p=VOTRE_CLE_PUBLIQUE"
```

Pour plus de détails (SPF, DMARC, OVH), voir [docs/email-configuration.md](docs/email-configuration.md).

## Scheduler

Pour la purge automatique des données, ajouter au crontab :

```cron
* * * * * cd /path/to/secret-drop && php artisan schedule:run >> /dev/null 2>&1
```

Tâches planifiées (configurées dans `routes/console.php`) :

| Commande | Fréquence | Description |
|----------|-----------|-------------|
| `secrets:clean` | Toutes les heures | Supprime les secrets expirés, révoqués, consommés et les magic links |
| `secrets:clean-blobs` | Toutes les 6 heures | Supprime les fichiers orphelins (sans secret correspondant) |
| `session:gc` | Quotidien | Nettoyage des sessions expirées |

Les commandes `secrets:*` supportent l'option `--dry-run` pour prévisualiser les suppressions.

## Logs Apache (zero-knowledge)

Pour respecter le principe zero-knowledge, les logs Apache ne doivent pas contenir les tokens des URLs sensibles. Utilisez un format de log personnalisé :

```apache
# Dans /etc/apache2/conf-available/secret-drop-log.conf

# Format qui masque les tokens dans les URLs sensibles
LogFormat "%h %l %u %t \"%m %U\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" secretdrop

# %U = URI sans query string (le fragment # n'est jamais envoyé au serveur)
# Les tokens dans /s/{token} restent visibles, voir ci-dessous pour les masquer
```

Pour masquer complètement les tokens, utilisez `mod_rewrite` avec une variable d'environnement :

```apache
<VirtualHost *:443>
    ServerName secret-drop.example.com
    DocumentRoot /var/www/secret-drop/public

    # Masquer les tokens dans les logs
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^/s/[^/]+
    RewriteRule ^/s/(.*)$ - [E=SANITIZED_URI:/s/[TOKEN]]

    RewriteCond %{REQUEST_URI} ^/api/secrets/[^/]+
    RewriteRule ^/api/secrets/(.*)$ - [E=SANITIZED_URI:/api/secrets/[TOKEN]]

    RewriteCond %{REQUEST_URI} ^/[a-z]{2}/admin/verify/[^/]+
    RewriteRule ^/([a-z]{2})/admin/verify/(.*)$ - [E=SANITIZED_URI:/$1/admin/verify/[TOKEN]]

    RewriteCond %{REQUEST_URI} ^/[a-z]{2}/superadmin/verify/[^/]+
    RewriteRule ^/([a-z]{2})/superadmin/verify/(.*)$ - [E=SANITIZED_URI:/$1/superadmin/verify/[TOKEN]]

    # Format de log sécurisé
    LogFormat "%h %l %u %t \"%m %{SANITIZED_URI}e\" %>s %b" secretdrop_safe
    SetEnvIf Request_URI "." SANITIZED_URI=%{REQUEST_URI}

    CustomLog ${APACHE_LOG_DIR}/secret-drop-access.log secretdrop_safe
    ErrorLog ${APACHE_LOG_DIR}/secret-drop-error.log
</VirtualHost>
```

Note : Le fragment URL (`#...` contenant la clé de chiffrement) n'est **jamais** envoyé au serveur par le navigateur, donc il n'apparaît jamais dans les logs serveur.

## Tests

```bash
# Tous les tests
composer test

# Un test spécifique
php artisan test --filter=ShowSecretTest

# Avec couverture
php artisan test --coverage
```

## CI/CD

### Intégration Continue (CI)

Le projet utilise GitHub Actions pour l'intégration continue (`.github/workflows/ci.yml`) :

| Job | Description |
|-----|-------------|
| **Pint** | Vérification du style de code PHP |
| **Larastan** | Analyse statique (PHPStan niveau max) |
| **Tests** | Suite complète PHPUnit |

Les checks sont exécutés sur chaque push et pull request vers `main`.

### Déploiement Continu (CD)

Le workflow de déploiement (`.github/workflows/cd.yml`) se déclenche automatiquement après le succès du CI sur `main`, ou manuellement via `workflow_dispatch`.

Il build les assets dans GitHub Actions puis les transfère sur le serveur via SSH/SCP.

Secrets GitHub requis (Settings > Secrets and variables > Actions) :

| Secret | Description |
|--------|-------------|
| `SSH_HOST` | Adresse IP ou domaine du serveur |
| `SSH_USER` | Utilisateur SSH (ex: `deploy`) |
| `SSH_KEY` | Clé privée SSH (contenu de `~/.ssh/id_ed25519`) |
| `SSH_PORT` | Port SSH (si différent de 22) |
| `SSH_PATH` | Chemin du projet (ex: `/var/www/secret-drop`) |

## Licence

AGPL-3.0 — voir [LICENSE](LICENSE)
