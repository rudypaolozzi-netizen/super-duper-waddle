# Planning Sequoia - Application de gestion collaborative

Application web de planning pour la gestion d'équipe, développée pour Sequoia Communication Corporate.

## 📋 Fonctionnalités

### Gestion des tâches
- Attribution de temps par personne et par dossier
- Visualisation sur 1 ou 3 semaines
- Validation des heures par membre
- Commentaires sur chaque tâche
- Alerte visuelle au-delà de 7h/jour

### Gestion des dossiers
- Création de dossiers avec couleurs personnalisées
- Recherche rapide de dossiers
- Suppression avec double validation

### Gestion d'équipe
- Ajout/suppression de membres
- Authentification sécurisée

### Exports
- Export par membre (heures validées/non validées)
- Export par dossier
- Export automatique hebdomadaire par email

### Interface
- Auto-refresh toutes les 30 secondes
- Design responsive basé sur la charte Sequoia
- Favicon personnalisé

## 🚀 Installation

### Prérequis
- Serveur web (Apache/Nginx)
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Accès CRON (pour l'export automatique)

### Étape 1 : Télécharger les fichiers
Placer les fichiers suivants dans votre répertoire web :
- `index.html`
- `config.php`
- `api.php`
- `weekly_export.php` cron

### Étape 2 : Configuration de la base de données
Éditer `config.php` avec vos identifiants :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nombddtest');
define('DB_USER', 'testlogin');
define('DB_PASS', 'testpwd');
```

### Étape 3 : Premier accès
1. Accéder à `index.html` dans votre navigateur
2. La base de données se crée automatiquement
3. Utiliser les identifiants par défaut :
   - **Identifiant** : `admin`
   - **Mot de passe** : `admin123`

### Étape 4 : Configuration de l'export automatique
Ajouter une tâche CRON pour l'export hebdomadaire :

```bash
# Ouvrir le crontab
crontab -e

# Ajouter cette ligne (export tous les vendredis à 20h)
0 20 * * 5 /usr/bin/php /chemin/vers/weekly_export.php
```

**Important** : Modifier l'adresse email dans `weekly_export.php` si nécessaire :
```php
$to = 'licences@sequoia.fr';
```

## 🎨 Charte graphique

### Couleurs
- **Bleu Sequoia** : #015871 (PANTONE 19-4340)
- **Beige clair** : #dcd494
- **Rose pâle** : #f0c8bf
- **Beige chaud** : #e3d9ce

### Typographies
- **Titres** : Titillium Web
- **Corps** : Bricolage Grotesque

## 👥 Utilisation

### Connexion
- Se connecter avec un compte utilisateur
- Les nouveaux membres ont le mot de passe par défaut : `sequoia123`

### Créer un dossier
1. Cliquer sur "+ Créer un dossier"
2. Saisir le nom
3. Choisir une couleur (avec le sélecteur ou en saisissant le code hex)
4. Valider

### Ajouter des heures
1. Cliquer sur une case du planning (jour + membre)
2. Sélectionner le dossier
3. Saisir les heures (par incréments de 0.25h)
4. Ajouter un commentaire (optionnel)
5. Enregistrer

### Modifier/Supprimer une tâche
1. Cliquer sur un bloc de tâche existant
2. Modifier les informations
3. Enregistrer ou supprimer

### Exporter les données
1. Aller dans la section "Exports"
2. Choisir l'export désiré :
   - **Par membre** : récapitulatif par personne
   - **Par dossier** : récapitulatif par projet
3. Un fichier CSV est téléchargé

## 🔐 Sécurité

### Recommandations
- Changer le mot de passe admin dès la première connexion
- Utiliser des mots de passe forts pour tous les comptes
- Placer `config.php` en dehors du répertoire web si possible
- Activer HTTPS sur votre serveur

### Permissions fichiers
```bash
chmod 644 index.html
chmod 600 config.php
chmod 644 api.php
chmod 700 weekly_export.php
```

## 📊 Structure de la base de données

### Table `users`
- `id` : Identifiant unique
- `username` : Nom d'utilisateur (unique)
- `password` : Mot de passe hashé
- `name` : Nom complet
- `created_at` : Date de création

### Table `folders`
- `id` : Identifiant unique
- `name` : Nom du dossier
- `color` : Couleur (format hex #RRGGBB)
- `created_at` : Date de création

### Table `tasks`
- `id` : Identifiant unique
- `user_id` : Référence vers l'utilisateur
- `folder_id` : Référence vers le dossier
- `date` : Date de la tâche
- `hours` : Nombre d'heures (décimal)
- `comment` : Commentaire optionnel
- `validated` : Statut de validation (booléen)
- `created_at` : Date de création

## 🛠️ Dépannage

### La base de données ne se crée pas
- Vérifier les identifiants dans `config.php`
- S'assurer que l'utilisateur MySQL a les droits CREATE TABLE
- Consulter les logs d'erreur PHP

### L'authentification ne fonctionne pas
- Vérifier que les sessions PHP sont activées
- Contrôler les permissions du dossier de sessions
- Tester avec les identifiants par défaut (admin/admin123)

### Les emails ne partent pas
- Vérifier la configuration SMTP du serveur
- Tester la fonction `mail()` de PHP
- Consulter les logs du serveur mail
- Alternative : utiliser PHPMailer pour un envoi SMTP authentifié

### Le planning ne s'affiche pas
- Ouvrir la console JavaScript du navigateur
- Vérifier que `api.php` est accessible
- S'assurer que le serveur web supporte les requêtes POST

## 🔄 Mise à jour

Pour mettre à jour l'application :
1. Sauvegarder la base de données
2. Remplacer les fichiers PHP et HTML
3. Tester sur un environnement de développement
4. Déployer en production

## 📝 Changelog

### Version 1.0 (Octobre 2025)
- Interface de planning avec vue 1 ou 3 semaines
- Gestion des utilisateurs et dossiers
- Attribution et validation des heures
- Export CSV par membre et par dossier
- Export automatique hebdomadaire par email
- Auto-refresh toutes les 30 secondes
- Design basé sur la charte graphique Sequoia

## 🤝 Support

Pour toute question ou assistance :
- Email : licences@sequoia.fr
- Vérifier les logs d'erreur PHP
- Consulter la documentation MySQL

## 📄 Licence

Application propriétaire développée pour Sequoia Communication Corporate.
Tous droits réservés © 2025 Sequoia.

## 🎯 Fonctionnalités avancées (optionnelles)

### Notifications par email
Modifier `api.php` pour ajouter des notifications :
- Quand des heures dépassent 7h/jour
- Quand une tâche est validée
- Rappel de validation en fin de semaine

### Rapports personnalisés
Créer de nouveaux types d'exports dans `api.php` :
- Export mensuel
- Statistiques par département
- Graphiques de charge de travail

### Droits d'accès
Ajouter un système de rôles :
- Admin : tous les droits
- Manager : gestion d'équipe
- Membre : validation de ses propres heures

## 💡 Conseils d'utilisation

### Bonnes pratiques
- Valider les heures chaque vendredi
- Utiliser des noms de dossiers clairs et cohérents
- Ajouter des commentaires pour les tâches complexes
- Exporter régulièrement pour garder un historique

### Organisation recommandée
- Créer des dossiers par client ou par projet
- Utiliser des couleurs cohérentes (par type de projet)
- Former l'équipe à l'utilisation de l'outil
- Définir un processus de validation des heures

### Maintenance
- Sauvegarder la base de données chaque semaine
- Nettoyer les anciennes données (> 1 an) si nécessaire
- Vérifier les logs d'erreur régulièrement
- Tester l'export automatique après modification