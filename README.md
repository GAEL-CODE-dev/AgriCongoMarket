# AgriCongoMarket

Plateforme e-commerce pour produits agricoles locaux au Congo, permettant aux producteurs de vendre directement aux consommateurs.

## Fonctionnalités

- **Catalogue de produits** : Affichage des produits par catégories (fruits/légumes, produits laitiers, viandes, épicerie).
- **Panier intelligent** : Ajout, modification et suppression de produits en temps réel.
- **Authentification** : Inscription et connexion des utilisateurs (acheteurs et vendeurs).
- **Commandes** : Finalisation des achats avec création de commandes en base de données.
- **Paiements** : Support pour MTN Mobile Money et Airtel Money (simulation pour l'instant).
- **Contact** : Formulaire de contact avec enregistrement des messages.
- **Sécurité** : Protection CSRF, validation des entrées, mots de passe sécurisés.

## Technologies utilisées

- **Frontend** : HTML, CSS (Tailwind CSS), JavaScript
- **Backend** : PHP 8+ avec PDO pour MySQL
- **Base de données** : MySQL
- **Serveur** : Apache/Nginx (via XAMPP en local)

## Installation

### Prérequis
- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache recommandé)
- Node.js (pour les dépendances frontend, optionnel)

### Étapes

1. **Cloner ou télécharger le projet** dans le répertoire de votre serveur web (ex. : `htdocs` pour XAMPP).

2. **Configurer la base de données** :
   - Créer une base de données MySQL nommée `agricongo`.
   - Importer le fichier `schema.sql` :
     ```bash
     mysql -u root -p agricongo < schema.sql
     ```
   - Ou utiliser phpMyAdmin pour importer le fichier.

3. **Configurer les variables d'environnement** :
   - Créer un fichier `.env` à la racine ou définir les variables dans votre serveur :
     ```
     AGRI_DB_HOST=127.0.0.1
     AGRI_DB_PORT=3306
     AGRI_DB_NAME=agricongo
     AGRI_DB_USER=root
     AGRI_DB_PASSWORD=votre_mot_de_passe
     ```

4. **Installer les dépendances frontend** (optionnel) :
   ```bash
   npm install
   npm run build  # Si configuré
   ```

5. **Démarrer le serveur** :
   - Avec XAMPP : Démarrer Apache et MySQL.
   - Accéder à `http://localhost/AgriCongo/` (ajuster le chemin selon votre configuration).

### Tests
- Ouvrir `index.html` dans un navigateur.
- Tester l'inscription/connexion.
- Ajouter des produits au panier et finaliser une commande.

#### Comptes de test
- **Vendeur** : vendeur@example.com / password123 (accès au dashboard)
- **Acheteur** : acheteur@example.com / password123

## Structure du projet

```
AgriCongo/
├── index.html          # Page d'accueil
├── connexion.php       # Authentification
├── produits.html       # Catalogue et panier
├── checkout.php        # Finalisation des commandes
├── contact.html        # Formulaire de contact
├── api/                # Endpoints API
│   ├── auth.php
│   ├── cart.php
│   ├── contact.php
│   └── products.php
├── includes/           # Fonctions utilitaires
│   └── auth.php
├── img/                # Images des produits
├── database.php        # Configuration DB
├── schema.sql          # Schéma de la base
├── style.css           # Styles personnalisés
├── produits.js         # Logique frontend
└── README.md           # Ce fichier
```

## API Endpoints

- `GET/POST /api/products.php` : Catalogue des produits
- `GET/POST /api/cart.php` : Gestion du panier
- `POST /api/auth.php` : Authentification JSON
- `POST /api/contact.php` : Envoi de messages

## Sécurité

- Utilisation de prepared statements pour éviter les injections SQL.
- Hachage des mots de passe avec `password_hash()`.
- Protection CSRF sur les formulaires.
- Validation des entrées côté serveur.

## Paiements

Le système supporte les paiements via MTN Mobile Money et Airtel Money. Actuellement, c'est une simulation (90% de succès pour les tests).

Pour une intégration réelle :
- S'inscrire auprès de MTN/Airtel pour obtenir des clés API.
- Remplacer `simulateMobileMoneyPayment` par des appels API réels.
- Gérer les webhooks pour confirmer les paiements.

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

## Contact

Pour des questions, utiliser le formulaire de contact du site ou ouvrir une issue sur GitHub.