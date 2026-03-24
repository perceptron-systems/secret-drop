# Configuration Email (DKIM/SPF/DMARC)

Ce document décrit la configuration des emails pour Secret Drop avec une authentification complète (DKIM, SPF, DMARC).

## Vue d'ensemble

L'authentification email repose sur 3 mécanismes complémentaires :

| Mécanisme | Rôle | Configuration |
|-----------|------|---------------|
| **SPF** | Autorise les serveurs à envoyer pour le domaine | DNS uniquement |
| **DKIM** | Signature cryptographique des emails | DNS + Application |
| **DMARC** | Politique en cas d'échec SPF/DKIM | DNS uniquement |

## Option 1 : Service mail externe (recommandé)

Si vous utilisez Mailgun, SES, Postmark ou Sendgrid, ces services gèrent automatiquement DKIM/SPF. Suivez leur documentation pour configurer les enregistrements DNS.

### Configuration Laravel

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.votredomaine.com
MAILGUN_SECRET=votre-api-key
```

## Option 2 : SMTP OVH

OVH peut signer les emails en DKIM si vous utilisez leur SMTP.

### Configuration Laravel

```env
MAIL_MAILER=smtp
MAIL_HOST=ssl0.ovh.net
MAIL_PORT=465
MAIL_USERNAME=votre-email@votredomaine.com
MAIL_PASSWORD=votre-mot-de-passe
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@votredomaine.com
MAIL_FROM_NAME="Secret Drop"
```

### Configuration DNS (OVH)

1. Connectez-vous au Manager OVH
2. Allez dans "Emails" > "Votre domaine" > "DKIM"
3. Activez le DKIM (OVH génère les clés automatiquement)

## Option 3 : DKIM signé par l'application

Si vous utilisez un SMTP qui ne signe pas en DKIM, Laravel peut le faire.

### 1. Générer une paire de clés DKIM

```bash
# Créer le dossier pour les clés
mkdir -p storage/dkim

# Générer la clé privée (2048 bits)
openssl genrsa -out storage/dkim/private.key 2048

# Extraire la clé publique
openssl rsa -in storage/dkim/private.key -pubout -out storage/dkim/public.key

# Protéger la clé privée
chmod 600 storage/dkim/private.key
```

### 2. Configuration .env

```env
MAIL_DKIM_DOMAIN=votredomaine.com
MAIL_DKIM_SELECTOR=secretdrop
MAIL_DKIM_PRIVATE_KEY_PATH=storage/dkim/private.key
```

### 3. Créer le Listener DKIM

Le fichier `app/Listeners/SignEmailWithDkim.php` signe automatiquement les emails sortants.

### 4. Configuration DNS

Ajoutez un enregistrement TXT pour le DKIM :

```
Nom: secretdrop._domainkey.votredomaine.com
Type: TXT
Valeur: v=DKIM1; k=rsa; p=VOTRE_CLE_PUBLIQUE_EN_BASE64
```

Pour obtenir la valeur de la clé publique :
```bash
cat storage/dkim/public.key | grep -v "PUBLIC KEY" | tr -d '\n'
```

## Configuration SPF

Ajoutez un enregistrement TXT à la racine du domaine :

```
Nom: @
Type: TXT
Valeur: v=spf1 include:mx.ovh.com -all
```

Adaptez selon votre provider :
- OVH : `include:mx.ovh.com`
- Mailgun : `include:mailgun.org`
- SES : `include:amazonses.com`
- Sendgrid : `include:sendgrid.net`

## Configuration DMARC

Ajoutez un enregistrement TXT :

```
Nom: _dmarc.votredomaine.com
Type: TXT
Valeur: v=DMARC1; p=quarantine; rua=mailto:dmarc@votredomaine.com; pct=100; adkim=s; aspf=s
```

### Options DMARC

| Option | Valeur | Description |
|--------|--------|-------------|
| `p` | `none` | Mode observation (rapports uniquement) |
| `p` | `quarantine` | Emails suspects en spam |
| `p` | `reject` | Emails suspects rejetés |
| `pct` | `100` | Pourcentage d'emails à vérifier |
| `adkim` | `s` | Alignement DKIM strict |
| `aspf` | `s` | Alignement SPF strict |

**Conseil** : Commencez par `p=none` pour observer, puis passez à `quarantine` puis `reject`.

## Vérification

### Tester l'envoi

```bash
php artisan tinker
>>> Mail::raw('Test DKIM', fn($m) => $m->to('check-auth@verifier.port25.com'));
```

Port25 vous enverra un rapport détaillé par email.

### Outils de vérification

- [MXToolbox](https://mxtoolbox.com/SuperTool.aspx) - Vérification SPF/DKIM/DMARC
- [Mail-tester](https://www.mail-tester.com/) - Score de délivrabilité
- [DKIM Validator](https://dkimvalidator.com/) - Test de signature DKIM

## Dépannage

### Email en spam

1. Vérifiez les enregistrements DNS avec MXToolbox
2. Vérifiez que l'adresse FROM correspond au domaine DKIM
3. Testez avec mail-tester.com

### Erreur "DKIM signature verification failed"

1. Vérifiez que la clé publique DNS correspond à la clé privée
2. Vérifiez le sélecteur DKIM dans le DNS
3. Attendez la propagation DNS (jusqu'à 24h)
