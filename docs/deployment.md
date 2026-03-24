# Guide de déploiement

Documentation complète pour déployer Secret Drop en production.

## Prérequis

### Serveur

| Composant | Version minimum | Recommandé |
|-----------|-----------------|------------|
| PHP | 8.2 | 8.4 |
| Composer | 2.x | 2.x |
| Node.js | 18.x | 20.x |
| SQLite | 3.x | 3.x |

### Extensions PHP requises

```
bcmath, ctype, curl, dom, fileinfo, json, mbstring,
openssl, pdo_sqlite, tokenizer, xml, zip
```

Vérification :
```bash
php -m | grep -E "^(bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pdo_sqlite|tokenizer|xml|zip)$"
```

## Installation

### 1. Récupérer le code

```bash
cd /var/www
git clone https://github.com/Gallyan/secret-drop.git
cd secret-drop
```

### 2. Configuration environnement

```bash
cp .env.example .env
```

Éditer `.env` avec les valeurs de production (voir section Variables d'environnement).

### 3. Installation des dépendances

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 4. Initialisation

```bash
# Générer la clé d'application
php artisan key:generate

# Créer la base de données
touch database/database.sqlite
php artisan migrate --force

# Cache de configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Permissions

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Variables d'environnement

### Application (obligatoires)

```env
APP_NAME="Secret Drop"
APP_ENV=production
APP_DEBUG=false
APP_KEY=                          # Généré par php artisan key:generate
APP_URL=https://secret-drop.example.com

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
```

### Base de données

```env
DB_CONNECTION=sqlite
# Le fichier database/database.sqlite est utilisé par défaut
```

### Session et cache

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Mail

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=votre-username
MAIL_PASSWORD=votre-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Voir [email-configuration.md](email-configuration.md) pour la configuration DKIM/SPF/DMARC.

### DKIM (optionnel)

Si votre SMTP ne signe pas automatiquement :

```env
MAIL_DKIM_DOMAIN=example.com
MAIL_DKIM_SELECTOR=secretdrop
MAIL_DKIM_PRIVATE_KEY_PATH=storage/dkim/private.key
```

### Stockage des fichiers chiffrés

#### Option 1 : Local (défaut)

```env
SECRETS_DISK_DRIVER=local
# Les fichiers sont stockés dans storage/app/secrets/
```

#### Option 2 : S3 / Compatible S3

```env
SECRETS_DISK_DRIVER=s3
SECRETS_AWS_ACCESS_KEY_ID=votre-access-key
SECRETS_AWS_SECRET_ACCESS_KEY=votre-secret-key
SECRETS_AWS_DEFAULT_REGION=eu-west-3
SECRETS_AWS_BUCKET=secret-drop-files
# SECRETS_AWS_ENDPOINT=https://s3.example.com  # Pour S3-compatible (MinIO, etc.)
```

### Super Admin

```env
SUPER_ADMIN_EMAIL=admin@example.com
```

Cet email peut accéder au tableau de bord des statistiques à `/superadmin`.

### Mentions légales

```env
LEGAL_EDITOR_NAME="Nom de l'éditeur"
LEGAL_HOSTING_NAME="OVH"
LEGAL_CONTACT_EMAIL=contact@example.com
```

## Limites fichiers

### Configuration PHP

Dans `php.ini` ou configuration du pool PHP-FPM :

```ini
; Taille maximale des fichiers uploadés (100 Mo recommandé)
upload_max_filesize = 100M
post_max_size = 100M

; Mémoire pour le chiffrement de gros fichiers
memory_limit = 256M

; Timeout pour les gros uploads
max_execution_time = 300
max_input_time = 300
```

### Configuration Nginx

```nginx
client_max_body_size 100M;
client_body_timeout 300s;
```

### Configuration Apache

```apache
LimitRequestBody 104857600
```

### Limite côté application

La limite de 100 Mo est définie dans le JavaScript côté client (`secret-form.js:109`).

## Configuration serveur web

### Nginx (recommandé)

```nginx
server {
    listen 443 ssl http2;
    server_name secret-drop.example.com;
    root /var/www/secret-drop/public;
    index index.php;

    # SSL
    ssl_certificate /etc/letsencrypt/live/secret-drop.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/secret-drop.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # Uploads
    client_max_body_size 100M;
    client_body_timeout 300s;

    # Logs sécurisés (masquage des tokens)
    # Voir section "Logs zero-knowledge"

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Bloquer l'accès aux fichiers sensibles
    location ~ /\.(?!well-known) {
        deny all;
    }

    location ~ ^/(storage|database|\.env) {
        deny all;
    }
}

# Redirection HTTP -> HTTPS
server {
    listen 80;
    server_name secret-drop.example.com;
    return 301 https://$server_name$request_uri;
}
```

### Apache

```apache
<VirtualHost *:443>
    ServerName secret-drop.example.com
    DocumentRoot /var/www/secret-drop/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/secret-drop.example.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/secret-drop.example.com/privkey.pem

    <Directory /var/www/secret-drop/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Limite upload
    LimitRequestBody 104857600

    # Logs sécurisés - voir README.md section "Logs Apache"
</VirtualHost>
```

## Logs zero-knowledge

Le serveur ne doit **jamais** logger les tokens des URLs sensibles.

### Nginx

```nginx
# Format de log avec masquage
map $request_uri $sanitized_uri {
    ~^/s/[^/]+(.*)$                    "/s/[TOKEN]$1";
    ~^/api/secrets/[^/]+(.*)$          "/api/secrets/[TOKEN]$1";
    ~^/admin/verify/[^/]+$             "/admin/verify/[TOKEN]";
    ~^/superadmin/verify/[^/]+$        "/superadmin/verify/[TOKEN]";
    default                            $request_uri;
}

log_format secretdrop '$remote_addr - $remote_user [$time_local] '
                      '"$request_method $sanitized_uri" $status $body_bytes_sent '
                      '"$http_referer" "$http_user_agent"';

access_log /var/log/nginx/secret-drop.log secretdrop;
```

### Apache

Voir la section "Logs Apache (zero-knowledge)" dans le README.md.

### Note importante

Le fragment URL (`#...` contenant la clé de chiffrement) n'est **jamais** envoyé au serveur par le navigateur - il reste entièrement côté client.

## Scheduler (tâches planifiées)

### Crontab

```cron
* * * * * cd /var/www/secret-drop && php artisan schedule:run >> /dev/null 2>&1
```

### Tâches automatiques

| Commande | Fréquence | Description |
|----------|-----------|-------------|
| `secrets:clean` | Toutes les heures | Supprime secrets expirés, révoqués, consommés |
| `secrets:clean-blobs` | Quotidien (3h) | Supprime fichiers orphelins |

### Queue worker (optionnel mais recommandé)

Pour l'envoi d'emails asynchrone :

```bash
# Systemd service: /etc/systemd/system/secret-drop-worker.service
[Unit]
Description=Secret Drop Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/secret-drop
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable secret-drop-worker
sudo systemctl start secret-drop-worker
```

## Sécurité

### Headers HTTP (automatiques)

L'application ajoute automatiquement :

| Header | Valeur |
|--------|--------|
| `Content-Security-Policy` | Stricte avec nonce |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` |
| `X-XSS-Protection` | `1; mode=block` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Strict-Transport-Security` | `max-age=31536000` (production) |
| `Permissions-Policy` | Caméra, micro, géoloc désactivés |

### Rate limiting

- Création de secrets : 10/minute par IP
- Accès admin (magic links) : 5/minute par IP
- Captcha automatique après dépassement

### Checklist de sécurité

- [ ] `APP_DEBUG=false` en production
- [ ] `APP_ENV=production`
- [ ] HTTPS obligatoire avec certificat valide
- [ ] Clé `APP_KEY` unique et secrète
- [ ] Permissions correctes sur `storage/` et `bootstrap/cache/`
- [ ] Fichier `.env` non accessible publiquement
- [ ] Logs configurés pour masquer les tokens
- [ ] Firewall configuré (ports 80, 443 uniquement)
- [ ] Backups réguliers de la base de données

## Monitoring

### Healthcheck

```bash
curl -f https://secret-drop.example.com/up || echo "DOWN"
```

### Supervision des fichiers

```bash
# Espace disque utilisé par les fichiers chiffrés
du -sh /var/www/secret-drop/storage/app/secrets/

# Nombre de secrets actifs
sqlite3 /var/www/secret-drop/database/database.sqlite \
  "SELECT COUNT(*) FROM secrets WHERE expire_at > datetime('now') AND revoked_at IS NULL;"
```

## Mise à jour

```bash
cd /var/www/secret-drop

# Maintenance
php artisan down

# Mise à jour
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force

# Rafraîchir les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Relancer
php artisan up
```

## Rollback

```bash
# Revenir à un commit précédent
git checkout <commit-hash>

# Ou utiliser les tags de version
git checkout v1.0.0

# Puis réinstaller
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
```

## Dépannage

### Erreur 500

```bash
# Vérifier les logs Laravel
tail -f storage/logs/laravel.log

# Vérifier les permissions
ls -la storage/
chown -R www-data:www-data storage bootstrap/cache
```

### Emails non envoyés

```bash
# Tester l'envoi
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com'));

# Vérifier la queue
php artisan queue:work --once
```

### Fichiers uploadés non trouvés

```bash
# Vérifier le disque configuré
php artisan tinker
>>> config('filesystems.disks.secrets')

# Vérifier l'existence du dossier
ls -la storage/app/secrets/
```

### Base de données corrompue

```bash
# Vérifier l'intégrité
sqlite3 database/database.sqlite "PRAGMA integrity_check;"

# Restaurer depuis backup si nécessaire
cp /backup/database.sqlite database/database.sqlite
```
