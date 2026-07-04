# Hazina Funding — Panneau Admin (PHP PDO + MySQL + Bootstrap 5)

Interface d'administration CRUD complète pour gérer les **projets**, **dons** et **utilisateurs** de la plateforme Hazina Funding.

## 📁 Structure

```
admin/
├── config/
│   └── database.php        → connexion PDO (identifiants à modifier)
├── includes/
│   ├── functions.php       → helpers (auth, CSRF, upload, formatage)
│   ├── header.php          → layout (navbar + sidebar)
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── uploads/            → images uploadées (projets, photos users)
├── projects/
│   ├── index.php  (liste + recherche + pagination)
│   ├── create.php
│   ├── edit.php
│   └── delete.php
├── donations/
│   ├── index.php  (liste + filtres statut/recherche)
│   ├── view.php   (détail + changement de statut)
│   └── delete.php
├── users/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
├── login.php
├── logout.php
├── index.php                → tableau de bord (stats)
└── database.sql             → ton script SQL fourni
```

## ⚙️ Installation

1. **Base de données** : importe `database.sql` dans phpMyAdmin (ou `mysql -u root -p < database.sql`). La base `hazinafunding` sera créée avec les tables `donations`, `projects`, `users`.

2. **Configuration** : ouvre `config/database.php` et adapte si besoin :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hazinafunding');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Déploiement** : place le dossier `admin/` dans ton serveur (XAMPP/WAMP/Laragon → `htdocs/`, ou tout hébergement PHP 8+).

4. **Droits d'écriture** : vérifie que `assets/uploads/` est accessible en écriture par le serveur web (pour les uploads d'images).

5. **Connexion** : va sur `admin/login.php`.
   - Compte déjà présent dans ton dump : `admin` / `admin@hazina.com`
   - ⚠️ Le mot de passe est déjà hashé (bcrypt) dans le dump et **je ne le connais pas** — si tu ne le connais pas non plus, régénère un hash et mets-le à jour en base :
     ```php
     php -r "echo password_hash('ton_nouveau_mdp', PASSWORD_BCRYPT);"
     ```
     Puis :
     ```sql
     UPDATE users SET password = 'LE_HASH_GENERE' WHERE username = 'admin';
     ```

## ✨ Fonctionnalités

- **Authentification** admin sécurisée (bcrypt + protection CSRF + session)
- **Projets** : création/édition/suppression avec upload d'image, barre de progression, filtres
- **Dons** : liste filtrable (statut, recherche), vue détaillée, changement de statut avec **recalcul automatique** du montant collecté du projet, suppression
- **Utilisateurs** : CRUD complet avec gestion des rôles (user/admin), upload de photo, mot de passe optionnel à la modification
- **Sécurité** : requêtes 100% préparées (PDO), échappement HTML systématique (`htmlspecialchars`), jeton CSRF sur tous les formulaires POST, `.htaccess` bloquant l'exécution de PHP dans `uploads/`
- **Responsive** : Bootstrap 5, sidebar rétractable sur mobile

## 🔒 Notes sécurité avant mise en production

- Change immédiatement le mot de passe admin par défaut
- Force HTTPS (cookies de session sécurisés)
- Limite la taille/type des uploads (déjà fait : 5 Mo max, jpg/jpeg/png/gif/webp)
- Sauvegarde régulièrement la base de données
