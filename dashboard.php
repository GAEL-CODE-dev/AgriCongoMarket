<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/auth.php';

ensureSessionStarted();

$currentUser = authCurrentUser();
if ($currentUser === null) {
    header('Location: connexion.php');
    exit;
}

$pdo = getDatabaseConnection();
$isSeller = $currentUser['role'] === 'seller';
$isBuyer = $currentUser['role'] === 'buyer';
$producer = null;
$products = [];
$orders = [];
$stats = [
    'primary_amount' => 0,
    'primary_label' => $isSeller ? 'Chiffre d affaires' : 'Total depense',
    'order_count' => 0,
    'product_count' => 0,
];

function formatDashboardAmount(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

if ($isSeller) {
    $producerStmt = $pdo->prepare('SELECT * FROM producers WHERE user_id = ? LIMIT 1');
    $producerStmt->execute([$currentUser['id']]);
    $producer = $producerStmt->fetch() ?: null;

    if ($producer !== null) {
        $productsStmt = $pdo->prepare(
            'SELECT p.id, p.name, p.description, p.price_label, p.stock_quantity, p.is_active, p.image_path, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.producer_id = ?
             ORDER BY p.created_at DESC'
        );
        $productsStmt->execute([$producer['id']]);
        $products = $productsStmt->fetchAll();

        $ordersStmt = $pdo->prepare(
            'SELECT
                o.id,
                o.status,
                o.created_at,
                u.full_name AS buyer_name,
                SUM(oi.line_total) AS seller_total,
                GROUP_CONCAT(CONCAT(oi.product_name, " x", oi.quantity) SEPARATOR ", ") AS order_lines
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             JOIN products p ON p.id = oi.product_id
             LEFT JOIN users u ON u.id = o.user_id
             WHERE p.producer_id = ?
             GROUP BY o.id, o.status, o.created_at, u.full_name
             ORDER BY o.created_at DESC
             LIMIT 50'
        );
        $ordersStmt->execute([$producer['id']]);
        $orders = $ordersStmt->fetchAll();

        $statsStmt = $pdo->prepare(
            'SELECT
                COUNT(DISTINCT o.id) AS total_orders,
                COALESCE(SUM(oi.line_total), 0) AS total_sales,
                COUNT(DISTINCT p.id) AS total_products
             FROM products p
             LEFT JOIN order_items oi ON oi.product_id = p.id
             LEFT JOIN orders o ON o.id = oi.order_id AND o.status = "paid"
             WHERE p.producer_id = ?'
        );
        $statsStmt->execute([$producer['id']]);
        $rawStats = $statsStmt->fetch() ?: [];

        $stats = [
            'primary_amount' => (float) ($rawStats['total_sales'] ?? 0),
            'primary_label' => 'Chiffre d affaires',
            'order_count' => (int) ($rawStats['total_orders'] ?? 0),
            'product_count' => (int) ($rawStats['total_products'] ?? 0),
        ];
    }
} elseif ($isBuyer) {
    $ordersStmt = $pdo->prepare(
        'SELECT
            o.id,
            o.status,
            o.total_amount,
            o.created_at,
            GROUP_CONCAT(CONCAT(oi.product_name, " x", oi.quantity) SEPARATOR ", ") AS order_lines
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         WHERE o.user_id = ?
         GROUP BY o.id, o.status, o.total_amount, o.created_at
         ORDER BY o.created_at DESC
         LIMIT 50'
    );
    $ordersStmt->execute([$currentUser['id']]);
    $orders = $ordersStmt->fetchAll();

    $statsStmt = $pdo->prepare(
        'SELECT
            COUNT(id) AS total_orders,
            COALESCE(SUM(total_amount), 0) AS total_spent
         FROM orders
         WHERE user_id = ? AND status = "paid"'
    );
    $statsStmt->execute([$currentUser['id']]);
    $rawStats = $statsStmt->fetch() ?: [];

    $stats = [
        'primary_amount' => (float) ($rawStats['total_spent'] ?? 0),
        'primary_label' => 'Total depense',
        'order_count' => (int) ($rawStats['total_orders'] ?? 0),
        'product_count' => count($orders),
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriCongoMarket | Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body class="dashboard-page">
  <header class="dashboard-header">
    <div class="dashboard-shell dashboard-header__inner">
      <a href="index.html" class="dashboard-brand">
        <span class="dashboard-brand__mark"><i class="fas fa-leaf"></i></span>
        <span>
          <strong>AgriCongoMarket</strong>
          <small>tableau de bord</small>
        </span>
      </a>

      <nav class="dashboard-nav">
        <a href="produits.html">Produits</a>
        <a href="checkout.php">Paiement</a>
        <?php if ($isSeller): ?>
          <a href="espace-vendeurs.php">Espace vendeur</a>
        <?php endif; ?>
      </nav>

      <div class="dashboard-user">
        <div>
          <strong><?= htmlspecialchars($currentUser['full_name']) ?></strong>
          <small><?= htmlspecialchars($currentUser['email']) ?></small>
        </div>
        <a href="deconnexion.php" class="dashboard-logout">Deconnexion</a>
      </div>
    </div>
  </header>

  <main class="dashboard-shell dashboard-main">
    <section class="hero-card">
      <div>
        <p class="eyebrow"><?= $isSeller ? 'Compte vendeur' : 'Compte acheteur' ?></p>
        <h1>Bienvenue, <?= htmlspecialchars($currentUser['full_name']) ?></h1>
        <p class="hero-copy">
          <?php if ($isSeller): ?>
            Suivez vos produits, vos ventes et l activite de votre exploitation depuis un espace recentre sur les donnees utiles.
          <?php else: ?>
            Retrouvez vos commandes, vos paiements et votre historique d achat dans un espace plus simple a consulter.
          <?php endif; ?>
        </p>
      </div>
      <div class="hero-actions">
        <?php if ($isSeller): ?>
          <a href="espace-vendeurs.php" class="button-primary">Publier un produit</a>
        <?php else: ?>
          <a href="produits.html" class="button-primary">Continuer mes achats</a>
        <?php endif; ?>
        <a href="contact.html" class="button-secondary">Contacter le support</a>
      </div>
    </section>

    <section class="stats-grid">
      <article class="stat-card">
        <span class="stat-card__icon stat-card__icon--orange"><i class="fas fa-wallet"></i></span>
        <p class="stat-card__label"><?= htmlspecialchars($stats['primary_label']) ?></p>
        <strong class="stat-card__value"><?= htmlspecialchars(formatDashboardAmount((float) $stats['primary_amount'])) ?></strong>
      </article>
      <article class="stat-card">
        <span class="stat-card__icon stat-card__icon--green"><i class="fas fa-receipt"></i></span>
        <p class="stat-card__label">Commandes</p>
        <strong class="stat-card__value"><?= (int) $stats['order_count'] ?></strong>
      </article>
      <article class="stat-card">
        <span class="stat-card__icon stat-card__icon--slate"><i class="fas fa-box"></i></span>
        <p class="stat-card__label"><?= $isSeller ? 'Produits publies' : 'Commandes visibles' ?></p>
        <strong class="stat-card__value"><?= (int) $stats['product_count'] ?></strong>
      </article>
    </section>

    <section class="dashboard-tabs">
      <div class="tab-bar" role="tablist" aria-label="Sections du dashboard">
        <button class="tab-button is-active" data-tab-target="overview" role="tab" aria-selected="true">Vue d ensemble</button>
        <button class="tab-button" data-tab-target="orders" role="tab" aria-selected="false"><?= $isSeller ? 'Ventes' : 'Achats' ?></button>
      </div>

      <div class="tab-panel" data-tab-panel="overview">
        <div class="content-grid">
          <article class="panel-card">
            <div class="panel-card__header">
              <div>
                <p class="eyebrow">Profil</p>
                <h2><?= $isSeller ? 'Identite producteur' : 'Mon compte' ?></h2>
              </div>
            </div>

            <?php if ($isSeller && $producer !== null): ?>
              <dl class="info-list">
                <div>
                  <dt>Ferme</dt>
                  <dd><?= htmlspecialchars($producer['farm_name']) ?></dd>
                </div>
                <div>
                  <dt>Region</dt>
                  <dd><?= htmlspecialchars((string) $producer['region']) ?></dd>
                </div>
                <div>
                  <dt>Description</dt>
                  <dd><?= htmlspecialchars((string) $producer['description']) ?></dd>
                </div>
              </dl>
            <?php else: ?>
              <dl class="info-list">
                <div>
                  <dt>Nom</dt>
                  <dd><?= htmlspecialchars($currentUser['full_name']) ?></dd>
                </div>
                <div>
                  <dt>Email</dt>
                  <dd><?= htmlspecialchars($currentUser['email']) ?></dd>
                </div>
                <div>
                  <dt>Telephone</dt>
                  <dd><?= htmlspecialchars((string) ($currentUser['phone'] ?? 'Non renseigne')) ?></dd>
                </div>
              </dl>
            <?php endif; ?>
          </article>

          <article class="panel-card">
            <div class="panel-card__header">
              <div>
                <p class="eyebrow">Synthese</p>
                <h2><?= $isSeller ? 'Points d attention' : 'Activite recente' ?></h2>
              </div>
            </div>

            <?php if ($isSeller && $producer === null): ?>
              <div class="notice-card notice-card--warning">
                Votre compte vendeur existe, mais le profil producteur n est pas encore active. L API bloque maintenant toute publication tant que ce profil manque.
              </div>
            <?php elseif ($isSeller): ?>
              <ul class="summary-list">
                <li>Les publications produits passent maintenant par un endpoint protege par CSRF.</li>
                <li>Les prix et libelles sont harmonises en FCFA sur tout le projet.</li>
                <li>Le dashboard a ete simplifie pour ne garder que les donnees reellement reliees au backend.</li>
              </ul>
            <?php else: ?>
              <ul class="summary-list">
                <li>Vos commandes payees apparaissent avec leur total complet.</li>
                <li>Le compte conserve des informations plus fiables en session, y compris le telephone si disponible.</li>
                <li>Les flux panier et contact utilisent maintenant une protection CSRF cote API.</li>
              </ul>
            <?php endif; ?>
          </article>
        </div>

        <?php if ($isSeller): ?>
          <article class="panel-card panel-card--full">
            <div class="panel-card__header">
              <div>
                <p class="eyebrow">Catalogue</p>
                <h2>Produits publies</h2>
              </div>
              <a href="espace-vendeurs.php" class="button-secondary">Gerer les produits</a>
            </div>

            <?php if (empty($products)): ?>
              <div class="empty-state">Aucun produit publie pour le moment.</div>
            <?php else: ?>
              <div class="product-grid">
                <?php foreach ($products as $product): ?>
                  <article class="product-card">
                    <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="product-card__body">
                      <p class="product-card__category"><?= htmlspecialchars((string) $product['category_name']) ?></p>
                      <h3><?= htmlspecialchars($product['name']) ?></h3>
                      <p><?= htmlspecialchars($product['description']) ?></p>
                      <div class="product-card__meta">
                        <span><?= htmlspecialchars($product['price_label']) ?></span>
                        <span>Stock: <?= (int) $product['stock_quantity'] ?></span>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </article>
        <?php endif; ?>
      </div>

      <div class="tab-panel is-hidden" data-tab-panel="orders">
        <article class="panel-card panel-card--full">
          <div class="panel-card__header">
            <div>
              <p class="eyebrow"><?= $isSeller ? 'Ventes recentes' : 'Achats recents' ?></p>
              <h2><?= $isSeller ? 'Historique des ventes' : 'Historique des commandes' ?></h2>
            </div>
          </div>

          <?php if (empty($orders)): ?>
            <div class="empty-state"><?= $isSeller ? 'Aucune vente enregistree pour le moment.' : 'Aucun achat enregistre pour le moment.' ?></div>
          <?php else: ?>
            <div class="orders-list">
              <?php foreach ($orders as $order): ?>
                <article class="order-row">
                  <div>
                    <strong>Commande #<?= (int) $order['id'] ?></strong>
                    <small><?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))) ?></small>
                  </div>
                  <div>
                    <span class="status-chip status-chip--<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></span>
                  </div>
                  <div>
                    <p><?= htmlspecialchars((string) ($order['order_lines'] ?? 'Aucune ligne')) ?></p>
                    <?php if ($isSeller): ?>
                      <small>Acheteur: <?= htmlspecialchars((string) ($order['buyer_name'] ?? 'Client inconnu')) ?></small>
                    <?php endif; ?>
                  </div>
                  <div class="order-row__amount">
                    <?= htmlspecialchars(formatDashboardAmount((float) ($isSeller ? $order['seller_total'] : $order['total_amount']))) ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      </div>
    </section>
  </main>

  <script src="assets/dashboard.js"></script>
</body>
</html>
