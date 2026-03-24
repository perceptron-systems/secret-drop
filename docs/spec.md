# Projet : Partage de secrets & fichiers chiffrés côté client (Blade + Alpine + WebCrypto)

## Objectif

Construire une web-app auto-hébergeable (Laravel) permettant de **chiffrer côté navigateur** une chaîne ou un fichier, de **stocker uniquement le ciphertext** côté serveur, puis de **générer un lien de partage**. L’app doit aussi permettre l’envoi du lien par email, le suivi de lecture, l’annulation, et une administration sans compte via magic link.

Le projet vise un modèle **zero-knowledge** : le serveur ne doit jamais recevoir le secret en clair.

---

## Stack & contraintes techniques

- Backend : **Laravel 12** (PHP 8.4), Blade
- Front : **Alpine.js** + JS vanilla
- Crypto : **Web Crypto API** (natif navigateur)
- Stockage : DB + stockage fichiers (local/S3 compatible)
- Envoi mail : Laravel Mail + responsive HTML (style “Litmus-friendly”), DKIM via infra (Postfix/SES/Mailgun/etc.)
- Sécurité : HTTPS obligatoire, CSP recommandée, logs sans données sensibles
- Aucun compte utilisateur / pas d’auth classique

---

## Principes cryptographiques (non négociables)

1. Le navigateur génère la **clé de chiffrement** et chiffre localement.
2. Le serveur reçoit et stocke **uniquement** :
    - ciphertext + iv + salt + métadonnées
    - jamais le secret en clair, jamais la clé
3. La clé doit être transmise au destinataire via :
    - **fragment URL** (`#...`) recommandé (non envoyé au serveur)
    - ou clé séparée “out-of-band” (optionnel)
4. Algorithmes :
    - **AES-256-GCM** pour chiffrer
    - **PBKDF2** ou **HKDF** pour dériver une clé si passphrase optionnelle
    - `crypto.getRandomValues()` pour l’aléa
5. Encodages :
    - exporter `ciphertext`, `iv`, `salt` en Base64URL (pas Base64 standard)
6. Pas de crypto “maison”. Pas de libs crypto douteuses si WebCrypto suffit.

---

## Fonctionnalités (MVP + évolutions)

### 1) Création d’un secret (texte)

- Form :
    - champ "secret" (textarea)
    - options :
        - `expire_at` (durée max) : ex. 1h, 1j, 7j, custom
        - `max_views` optionnel (ex. 1 pour usage unique, 3, illimité)
        - `passphrase` optionnelle (si utilisée, dérivation côté client)
    - bouton : "Générer le lien"
- Résultat :
    - lien partage : `https://host/s/<token>#<key_material>`
    - bouton “copier”
    - option “envoyer par mail”

### 2) Création d’un secret (fichier)

- Upload fichier (drag&drop + input)
- Chiffrement côté client AVANT upload (métadonnées fichier incluses dans le payload chiffré)
- Upload du ciphertext (blob) uniquement, aucune métadonnée en clair
- Télécharger côté destinataire après déchiffrement local (blob -> download)

### 3) Consultation / lecture

- Page `GET /s/{token}` :
    - récupère ciphertext + métadonnées depuis serveur
    - le JS lit la clé depuis `location.hash`
    - déchiffre localement
    - affiche :
        - secret texte (avec bouton copier)
        - ou fichier (bouton télécharger)
- Marquer “lu” côté serveur :
    - quand déchiffrement réussit, envoyer un `POST /s/{token}/read` (sans secret)
    - stocker `first_read_at`, `read_count`, `last_read_at`
- Si usage unique :
    - après premier `read` réussi, rendre le secret irréversible côté serveur :
        - supprimer ciphertext / ou marquer invalidé et supprimer blob
        - conserver seulement métadonnées (audit minimal)

### 4) Annulation / révocation

- Permettre d’annuler un secret avant expiration :
    - action “Revoke” via interface d’admin (magic link)
    - côté serveur : marque `revoked_at` + supprime ciphertext/blob
- Après révocation, page de lecture affiche “Secret indisponible”.

### 5) Administration sans compte (par URL + magic link email)

- Lors de la création, si l’utilisateur fournit un email “éditeur” (optionnel mais recommandé) :
    - stocker `creator_email` (hashé ou en clair selon besoin de renvoi magic link ; préférer en clair chiffré au repos si possible)
    - générer un “admin handle” non devinable : `admin_token`
- Interface admin accessible via :
    - URL : `GET /a/{admin_token}` (NE DOIT PAS donner accès direct sans vérif)
    - workflow :
        1. l’utilisateur saisit son email
        2. si email correspond au `creator_email`, envoyer un **magic link** à usage unique
        3. magic link contient un jeton court TTL (15 min)
        4. une fois validé : accès actions admin :
            - voir statut (lu/pas lu, dates, compteurs)
            - révoquer
            - prolonger expiration (si pas encore lu ou si non-unique)
            - renvoyer email au destinataire (si configuré)

### 6) Envoi email au destinataire depuis la plateforme

- Après création, possibilité d’envoyer un email au destinataire contenant le lien de partage
- Exigences :
    - template email responsive (Litmus-like) : table-based, inline CSS, fallback dark mode basique
    - DKIM : **à configurer via l’infra d’envoi**, documenter le setup (DNS DKIM, SPF, DMARC)
- Contenu email :
    - CTA “Ouvrir le secret”
    - info expiration / usage unique
    - avertissement : ne pas transférer le lien
    - option : inclure la clé séparément ? (par défaut non ; garder clé dans fragment URL)

---

## Idées pertinentes à ajouter (recommandées)

1. **Séparation lien / clé** :
    - mode “Split” : générer 2 éléments :
        - URL serveur : `https://host/s/<token>`
        - clé : `K...`
    - utile pour envoyer lien par mail et clé par SMS/Signal
2. **Rate limit & anti-abus** :
    - limiter créations par IP / par fenêtre temporelle
    - captcha optionnel (hCaptcha/Turnstile) en mode public
3. **CSP + SRI + build minimal** :
    - CSP stricte pour réduire XSS (XSS = compromission du secret côté client)
4. **Taille fichiers & streaming** :
    - chiffrement chunké pour gros fichiers (option avancée)
    - MVP : limiter ex. 50–200MB
5. **Journalisation minimale** :
    - logs sans IP complète (ou tronquée), sans tokens, sans secrets
6. **Suppression automatique** :
    - scheduler Laravel qui purge expirés + blobs orphelins
7. **Mode “burn after read” correct** :
    - ne marquer “lu” que si le client confirme un déchiffrement réussi
8. **Internationalisation FR/EN** (option)

---

## Modèle de données (proposition)

Table `secrets` :

- `id` (uuid)
- `token` (string unique, public)
- `type` enum: `text|file`
- `cipher_meta` json: `{alg, kdf, iv, salt, aad, version}`
- `ciphertext` longtext (pour text) OU `file_path` (pour blob chiffré)
- `max_views` int nullable (1 = usage unique)
- `read_count` int default 0
- `first_read_at`, `last_read_at` (nullable)
- `expire_at` datetime nullable
- `revoked_at` datetime nullable
- `creator_email_hash` string nullable (hash SHA256)
- `admin_token` string unique (non exposé publiquement)
- `created_at`, `updated_at`

Table `magic_links` :

- `id`
- `secret_id`
- `email`
- `token_hash`
- `expire_at`
- `used_at`
- `created_at`

---

## Endpoints (proposition)

Public :

- `GET /` : formulaire create
- `POST /api/secrets` : reçoit ciphertext + meta + options -> retourne `{token, admin_token?}`
- `GET /s/{token}` : page de lecture (ne reçoit pas la clé)
- `GET /api/secrets/{token}` : retourne ciphertext + meta + statut (si accessible)
- `POST /api/secrets/{token}/read` : incrémente read_count + timestamps si déchiffrement ok (preuve minimale)
- `POST /api/secrets/{token}/revoke-request` : demande accès admin (email) -> envoie magic link
  Admin :
- `GET /a/{admin_token}` : page admin (demande email si pas de session magic)
- `POST /api/admin/{admin_token}/magic` : vérifie email, émet magic link
- `GET /magic/{token}` : valide le magic link, ouvre session courte
- `POST /api/admin/{admin_token}/revoke` : révoque
- `POST /api/admin/{admin_token}/extend` : étend expiration
- `POST /api/admin/{admin_token}/resend` : renvoie email destinataire

Sessions admin :

- Cookie httpOnly + TTL court OU session Laravel standard, scope secret unique

---

## Frontend : flux précis (à implémenter)

### Création

1. L’utilisateur saisit texte OU sélectionne un fichier
2. JS :
    - génère clé AES-GCM 256
    - génère iv (12 bytes)
    - (optionnel) dérive clé depuis passphrase via PBKDF2 (salt + iterations)
    - chiffre le payload
3. JS envoie au serveur :
    - ciphertext (base64url) ou blob chiffré (multipart)
    - meta: iv, salt, algoVersion (filename/mime/size chiffrés dans le payload)
    - options: expire_at, max_views, emails
4. Serveur répond :
    - `token` public
    - `admin_token` (non-public)
5. JS construit URL finale :
    - `shareUrl = /s/{token}#<key_material>`
    - key_material = export JWK ou raw key encodée base64url + paramètres KDF

### Lecture

1. Page charge ciphertext via API
2. JS lit `location.hash`, reconstruit clé
3. Déchiffre localement
4. Si succès -> affiche + POST `/read`
5. Si échec -> afficher erreur (clé invalide / expiré / révoqué)

---

## Sécurité : points à traiter explicitement

- XSS = critique (peut voler secret avant chiffrement ou après déchiffrement)
    - CSP stricte, éviter `unsafe-inline` si possible
    - minimiser dépendances
- Tokens non devinables (>= 128 bits)
- Rate limiting sur création et accès admin
- Protection brute force sur magic links
- Ne jamais logguer :
    - URL complète (car fragment non loggué mais prudence)
    - tokens, admin_token, ciphertext
- Téléchargements : headers sécurisés, pas de mime sniffing

---

## Tests à produire

- Unit :
    - services token generation
    - expiration logic
    - revoke logic
    - magic link issuance/validation
- Feature :
    - create secret (text/file) -> fetch -> revoke -> expired
    - usage unique : 1er read ok, second read KO
    - admin magic link flow
- (Optionnel) E2E (Playwright/Cypress) :
    - chiffrement/déchiffrement JS (sanity)

---

## Livrables attendus

1. Repo Laravel prêt à déployer
2. Documentation :
    - config DKIM/SPF/DMARC (checklist)
    - variables d’env (mail, storage)
    - limites de taille fichiers
4. UI simple et propre (Blade + Alpine), copy-to-clipboard, états clairs

---

## Questions à trancher par défaut (choix recommandés)

- Default expiration : **7 jours**
- Default max_views : **null** (illimité), option "usage unique" = max_views=1
- Taille max fichier : **100MB** MVP
- Mode clé : **fragment URL** par défaut + option split
- Passphrase : optionnelle (désactivée par défaut)

---

## Critères d’acceptation

- Le serveur ne reçoit jamais le secret en clair (vérifiable via logs + code)
- Les liens fonctionnent avec clé dans fragment
- Usage unique / expiration / révocation opérationnels
- Fichiers chiffrés/déchiffrés localement et téléchargeables
- Envoi email responsive OK (rendu correct clients majeurs)
- DKIM documenté + compatible
- Administration sans compte via magic link email fonctionnelle
- Statut “lu” visible côté admin

---

## Notes d’implémentation WebCrypto (guidelines)

- AES-GCM iv: 12 bytes
- `additionalData` (AAD) : inclure token/version pour éviter mixups (option)
- Export clé : `crypto.subtle.exportKey("raw", key)` puis base64url
- Passphrase KDF :
    - PBKDF2 SHA-256
    - iterations: 200k (ajuster perf)
- Prévoir versioning (`crypto_version`) pour migrations futures

---

## Ce que l’agent doit produire en plus (bonus)

- Un mode “prévisualiser” secret déchiffré sans l’envoyer
- Un “QR code du lien” pour mobile
- Un bouton “générer clé séparée” (split mode)
- Une page “statut public minimal” (sans admin) : expiré / révoqué / dispo (sans révéler lu/non-lu si privacy)

---

## Instruction finale à l'agent

Livrer une implémentation propre, sobre, auditable, avec priorité à la sécurité (XSS/CSP) et à la simplicité (Blade/Alpine/WebCrypto). Toute fonctionnalité impliquant l'accès au secret en clair côté serveur est interdite.

---

## Checklist d'implémentation

### Infrastructure & Configuration

- [x]   1. Initialiser le projet Laravel 12 (PHP 8.4), configurer Blade, Alpine.js, stockage local/S3 et configuration mail de base.
- [x]   2. Mettre en place les contraintes de sécurité globales : HTTPS forcé, headers sécurisés, CSP stricte, configuration des logs sans données sensibles.
- [x]   3. Concevoir et migrer le schéma de base de données (secrets, magic_links) avec UUID, tokens non devinables et champs métier nécessaires.
- [x]   4. Implémenter les services backend de génération de tokens sécurisés (token public, admin_token, magic link token).

### Création de secrets

- [x]   5. Créer la page publique de création de secret (Blade + Alpine) avec formulaire texte, options (expiration, usage unique, max views, passphrase).
- [x]   6. Implémenter le chiffrement côté client pour les secrets texte via Web Crypto API (AES-256-GCM, Base64URL, versioning crypto).
- [x]   7. Implémenter l'API POST /api/secrets pour recevoir uniquement le ciphertext, les métadonnées et les options, sans jamais recevoir le secret en clair.
- [x]   8. Générer côté client l'URL de partage avec clé dans le fragment (/s/{token}#key_material) et proposer la copie.
- [x]   9. Ajouter la création de secret fichier : interface upload (drag & drop), chiffrement côté client avant upload, envoi du blob chiffré et métadonnées.
- [x]   10. Gérer le stockage serveur des fichiers chiffrés (local/S3), sans jamais manipuler de contenu déchiffré.

### Lecture de secrets

- [x]   11. Créer la page de lecture GET /s/{token} avec chargement des métadonnées et du ciphertext via API.
- [x]   12. Implémenter le déchiffrement côté client à partir du fragment URL, avec affichage texte ou téléchargement de fichier après succès.
- [x]   13. Implémenter l'API POST /api/secrets/{token}/read déclenchée uniquement après un déchiffrement réussi côté client.
- [x]   14. Gérer la logique serveur de lecture : incrément des compteurs, dates de lecture, vérification max_views et expiration.
- [x]   15. Implémenter le mode usage unique : suppression irréversible du ciphertext/blob après la première lecture validée.
- [x]   16. Gérer les états d'erreur de lecture : expiré, révoqué, usage unique consommé, clé invalide.
- [x]   17. Implémenter la révocation d'un secret côté serveur avec suppression du ciphertext/blob et affichage "secret indisponible".

### Administration

- [x]   18. Ajouter la possibilité de saisir un email créateur lors de la création et stocker l'email hashé.
- [x]   19. Implémenter la page admin avec workflow de vérification par email (magic link, pas d'accès direct).
- [x]   20. Implémenter l'émission de magic links à usage unique avec TTL court (5 min) et protection brute force.
- [x]   21. Implémenter la validation du magic link et l'ouverture d'une session admin courte et scoped.
- [x]   22. Créer l'interface admin permettant de voir le statut (lu, dates, compteurs), révoquer et prolonger l'expiration.

### Email

- [x]   23. ~~Implémenter l'envoi d'email au destinataire depuis la plateforme avec template responsive (table-based, inline CSS).~~ => Abandonné : contrevient au principe zero-knowledge.
- [x]   24. Documenter et configurer le support DKIM/SPF/DMARC côté infrastructure d'envoi mail.
- [x]   25. ~~Ajouter l'option d'envoi du lien avec mode "clé dans fragment" par défaut et avertissements de sécurité dans l'email.~~ => Abandonné : contrevient au principe zero-knowledge.

### Fonctionnalités avancées

- [x]   26. Implémenter le mode "split" lien / clé avec génération d'une clé séparée à transmettre out-of-band.
- [x]   27. Mettre en place le rate limiting sur la création de secrets et les accès admin, avec captcha.

### Maintenance & Sécurité

- [x]   28. Implémenter le scheduler Laravel pour la purge automatique des secrets expirés et des blobs orphelins.
- [x]   29. Ajouter la journalisation minimale conforme zero-knowledge (pas d'URL complète, pas de tokens, pas de ciphertext).
- [x]   30. Ajouter les headers sécurisés pour le téléchargement de fichiers (no sniffing, content-disposition sûr).

### Tests

- [x]   31. Implémenter les tests unitaires (tokens, expiration, révocation, magic links).
- [x]   32. Implémenter les tests fonctionnels (création, lecture, usage unique, expiration, admin flow).

### UX & Finitions

- [x]   33. Ajouter les bonus UX : QR code du lien, bouton "clé séparée" (preview local ignoré).
- [x]   34. Ajouter l'internationalisation FR/EN de l'interface.

### Documentation & Déploiement

- [x]   35. Préparer la documentation de déploiement (env, mail, storage, limites fichiers, sécurité).
- [ ]   36. Travailler le SEO / Référencement du site (titre, desc, schema.org, ...)
