# Guide de configuration et test des emails

Ce document explique comment configurer et tester l'envoi d'emails dans l'application.

## Configuration des emails

L'application utilise Symfony Mailer pour l'envoi d'emails. La configuration se fait dans le fichier `.env` :

```
# Configuration actuelle: null://null (les emails ne sont pas réellement envoyés)
# Pour envoyer des emails réels, utilisez l'une des configurations suivantes:
# SMTP: MAILER_DSN=smtp://user:pass@smtp.example.com:port
# Gmail: MAILER_DSN=gmail://username:password@default
# Sendmail: MAILER_DSN=sendmail://default
# Pour le développement, vous pouvez utiliser Mailpit: MAILER_DSN=smtp://localhost:1025
MAILER_DSN=null://null
```

### Options de configuration

1. **Configuration actuelle (null://null)** : Les emails ne sont pas réellement envoyés, mais l'application simule l'envoi.
2. **SMTP** : Utilisez un serveur SMTP standard.
3. **Gmail** : Utilisez Gmail comme service d'envoi d'emails.
4. **Sendmail** : Utilisez le service Sendmail local.
5. **Mailpit** : Solution de développement qui capture les emails localement.

## Tester l'envoi d'emails

### Utilisation de la commande de test

L'application fournit une commande Symfony pour tester l'envoi d'emails :

```bash
php bin/console app:test-email
```

Cette commande :
- Affiche la configuration actuelle du mailer
- Tente d'envoyer un email d'approbation
- Tente d'envoyer un email de rejet
- Affiche les résultats détaillés

### Vérifier les logs

Les logs d'envoi d'emails sont visibles dans les fichiers de log du serveur. Vous pouvez également voir les messages dans la console lors de l'approbation ou du rejet d'un utilisateur.

Les logs incluent :
- Le statut de l'envoi (succès ou échec)
- Le destinataire
- La date et l'heure
- Les détails de l'erreur en cas d'échec

## Configuration pour le développement avec Mailpit

Pour le développement, nous recommandons d'utiliser Mailpit qui permet de capturer et visualiser les emails sans les envoyer réellement :

1. Installez Mailpit (via Docker ou directement)
2. Modifiez votre fichier `.env` :
   ```
   MAILER_DSN=smtp://localhost:1025
   ```
3. Lancez Mailpit
4. Accédez à l'interface web de Mailpit (généralement sur http://localhost:8025)
5. Testez l'envoi d'emails

## Emails envoyés par l'application

L'application envoie deux types d'emails :

1. **Email d'approbation** : Envoyé lorsqu'un administrateur approuve un utilisateur.
   - Contient un lien vers la page de connexion
   - Informe l'utilisateur que son compte a été approuvé

2. **Email de rejet** : Envoyé lorsqu'un administrateur rejette un utilisateur.
   - Contient la raison du rejet (si spécifiée)
   - Contient un lien pour s'inscrire à nouveau
