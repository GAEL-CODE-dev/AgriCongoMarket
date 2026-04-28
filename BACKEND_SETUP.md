# Backend AgriCongo

## Ce qui a été ajouté/amélioré

- `database.php` : Connexion PDO MySQL sécurisée
- `schema.sql` : Structure de base de données + catégories + produits de démo + tables orders/order_items + users de test
- `includes/auth.php` : Fonctions d'authentification avec CSRF et validation renforcée
- `includes/payments.php` : Simulation des paiements MTN Mobile Money et Airtel Money
- `api/products.php` : Catalogue produits
- `api/cart.php` : Panier en session
- `api/contact.php` : Enregistrement des messages
- `api/auth.php` : Login/register JSON
- `connexion.php` : Page de connexion/inscription avec protection CSRF et redirection vendeurs
- `checkout.php` : Finalisation des commandes avec choix de paiement
- `dashboard.php` : Dashboard vendeur avec stats, produits et commandes
- `deconnexion.php` : Fermeture de session
- `contact.js` : Envoi AJAX du formulaire contact
- `README.md` : Documentation complète
- `tests/AuthTest.php` : Tests unitaires basiques
- `Dockerfile` et `docker-compose.yml` : Configuration pour déploiement
- `composer.json` : Gestion des dépendances PHP
- `composer.json` : Gestion des dépendances PHP

## Installation

1. Créer une base MySQL puis importer `schema.sql`
2. Définir les variables d'environnement :
   - `AGRI_DB_HOST`
   - `AGRI_DB_PORT`
   - `AGRI_DB_NAME`
   - `AGRI_DB_USER`
   - `AGRI_DB_PASSWORD`
3. Servir le projet avec PHP ou Apache

## Exemple local

Si vous utilisez XAMPP ou WAMP, placez le projet dans `htdocs` ou `www`, puis :

- Importer `schema.sql` dans phpMyAdmin
- Utiliser par exemple :
  - `AGRI_DB_HOST=127.0.0.1`
  - `AGRI_DB_PORT=3306`
  - `AGRI_DB_NAME=agricongo`
  - `AGRI_DB_USER=root`
  - `AGRI_DB_PASSWORD=`

## Avec Docker

```bash
docker-compose up --build
```

Accéder à `http://localhost:8080`

## Comportement actuel

- Le catalogue produits fonctionne même sans base grâce à un fallback local
- Le panier fonctionne via session PHP si les endpoints sont disponibles
- Le formulaire contact enregistre en base si possible, sinon dans `storage/messages.log`
- L'authentification nécessite une base MySQL active
- Les commandes sont créées et stockées en base avec statut 'paid' après paiement simulé
- Paiements : Simulation de MTN Mobile Money et Airtel Money (90% de succès)- Dashboard vendeur : Accessible après connexion, affiche stats, produits et commandes récentes avec onglets
## Suite logique

- Ajouter un espace vendeur pour gérer les produits
- Ajouter un tableau de bord admin
- Intégrer un système de paiement
- Ajouter des notifications par email
- Optimiser les performances (cache, CDN)
