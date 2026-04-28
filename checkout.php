<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/payments.php';
require_once __DIR__ . '/api/bootstrap.php';

ensureSessionStarted();

$currentUser = authCurrentUser();
$cart = getSessionCart();
$catalog = fetchProductsCatalog();
$normalizedCart = normalizeCartItems($cart, $catalog);

$errorMessage = '';
$successMessage = '';

function formatCheckoutAmount(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $errorMessage = 'Jeton de securite invalide.';
    } elseif ($currentUser === null) {
        $errorMessage = 'Vous devez etre connecte pour passer commande.';
    } elseif (empty($normalizedCart['items'])) {
        $errorMessage = 'Votre panier est vide.';
    } else {
        $paymentMethod = $_POST['payment_method'] ?? '';
        $phone = trim($_POST['phone'] ?? '');

        if (!in_array($paymentMethod, ['mtn_mobile_money', 'airtel_money'], true)) {
            $errorMessage = 'Mode de paiement invalide.';
        } elseif (!validateCongolesePhone($phone)) {
            $errorMessage = 'Numero invalide. Format attendu : +242 06 123 4567 pour MTN ou +242 05 123 4567 pour Airtel.';
        } else {
            try {
                $pdo = getDatabaseConnection();
                $paymentResult = processMobileMoneyPayment($paymentMethod, $phone, $normalizedCart['total']);

                if (!$paymentResult['success']) {
                    $errorMessage = 'Echec du paiement : ' . $paymentResult['message'];
                } else {
                    $orderTotal = $normalizedCart['total'];

                    $stmt = $pdo->prepare('INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, ?)');
                    $stmt->execute([$currentUser['id'], $orderTotal, 'paid']);
                    $orderId = (int) $pdo->lastInsertId();

                    $stmt = $pdo->prepare(
                        'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );

                    foreach ($normalizedCart['items'] as $item) {
                        $stmt->execute([
                            $orderId,
                            $item['id'],
                            $item['name'],
                            $item['priceValue'],
                            $item['quantity'],
                            $item['lineTotal'],
                        ]);
                    }

                    saveSessionCart([]);
                    $normalizedCart = normalizeCartItems([], $catalog);

                    $smsSent = sendSmsConfirmationMessage(
                        $phone,
                        $currentUser['full_name'],
                        $orderId,
                        $orderTotal,
                        $paymentResult['transaction_id'] ?? null,
                        $paymentMethod
                    );

                    $successMessage = 'Commande payee avec succes. Reference : #' . $orderId . '. Transaction : ' . htmlspecialchars($paymentResult['transaction_id'] ?? 'N/A');
                    if ($smsSent) {
                        $successMessage .= ' Un SMS de confirmation a ete envoye au numero ' . htmlspecialchars($phone) . '.';
                    } else {
                        $successMessage .= ' Le paiement est confirme, mais le SMS de confirmation n a pas pu etre envoye.';
                    }
                }
            } catch (Throwable $exception) {
                $errorMessage = 'Erreur lors de la commande : ' . $exception->getMessage();
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriCongoMarket | Checkout</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body class="bg-stone-50 text-gray-800">
  <header class="sticky top-0 z-40 border-b border-orange-100 bg-white/90 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
      <a href="index.html" class="text-2xl font-bold text-orange-600">AgriCongoMarket</a>
      <nav class="hidden items-center gap-8 md:flex">
        <a href="index.html" class="font-medium text-gray-700 hover:text-orange-600">Accueil</a>
        <a href="produits.html" class="font-medium text-gray-700 hover:text-orange-600">Produits</a>
        <a href="contact.html" class="font-medium text-gray-700 hover:text-orange-600">Contact</a>
        <a href="dashboard.php" class="font-medium text-gray-700 hover:text-orange-600">Dashboard</a>
      </nav>
      <div class="hidden items-center gap-4 md:flex">
        <?php if ($currentUser): ?>
          <span class="rounded-full bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700"><?= htmlspecialchars($currentUser['full_name']) ?></span>
          <a href="deconnexion.php" class="rounded-lg bg-gray-900 px-4 py-2 text-white hover:bg-black">Deconnexion</a>
        <?php else: ?>
          <a href="connexion.php" class="font-medium text-orange-700 hover:text-orange-600">Connexion</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-4xl px-6 py-16">
    <h1 class="mb-8 text-3xl font-bold">Finaliser votre commande</h1>

    <?php if ($errorMessage !== ''): ?>
      <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
      <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-700"><?= htmlspecialchars($successMessage) ?></div>
      <a href="produits.html" class="rounded-lg bg-orange-600 px-6 py-3 text-white hover:bg-orange-700">Continuer vos achats</a>
    <?php elseif (empty($normalizedCart['items'])): ?>
      <p>Votre panier est vide. <a href="produits.html" class="text-orange-600">Voir les produits</a></p>
    <?php elseif (!$currentUser): ?>
      <p>Vous devez <a href="connexion.php" class="text-orange-600">vous connecter</a> pour passer commande.</p>
    <?php else: ?>
      <div class="grid gap-8 lg:grid-cols-2">
        <div>
          <h2 class="mb-4 text-xl font-semibold">Recapitulatif</h2>
          <div class="space-y-4">
            <?php foreach ($normalizedCart['items'] as $item): ?>
              <div class="flex items-center gap-4 rounded-lg bg-white p-4 shadow">
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-16 w-16 rounded object-cover">
                <div class="flex-1">
                  <h3 class="font-semibold"><?= htmlspecialchars($item['name']) ?></h3>
                  <p class="text-sm text-gray-600">Quantite : <?= $item['quantity'] ?> - Prix : <?= htmlspecialchars($item['price']) ?></p>
                  <p class="text-sm font-semibold">Sous-total : <?= htmlspecialchars(formatCheckoutAmount((float) $item['lineTotal'])) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-6 rounded-lg bg-gray-100 p-4">
            <p class="text-lg font-semibold">Total : <?= htmlspecialchars(formatCheckoutAmount((float) $normalizedCart['total'])) ?></p>
          </div>
        </div>

        <div>
          <h2 class="mb-4 text-xl font-semibold">Paiement Mobile Money</h2>
          <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <p class="text-sm text-gray-600">Commande pour : <?= htmlspecialchars($currentUser['full_name']) ?> (<?= htmlspecialchars($currentUser['email']) ?>)</p>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">Mode de paiement</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="radio" name="payment_method" value="mtn_mobile_money" required class="mr-2">
                  <span>MTN Mobile Money</span>
                </label>
                <label class="flex items-center">
                  <input type="radio" name="payment_method" value="airtel_money" required class="mr-2">
                  <span>Airtel Money</span>
                </label>
              </div>
            </div>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-gray-700">Numero de telephone</span>
              <input name="phone" type="tel" required placeholder="Ex: +242 06 123 4567 ou +242 05 123 4567" class="w-full rounded-2xl border border-gray-200 bg-stone-50 px-4 py-3 outline-none focus:border-orange-400 focus:bg-white">
              <p class="mt-1 text-xs text-gray-500">Utilisez un numero Congo-Brazzaville valide pour recevoir la confirmation.</p>
            </label>

            <button type="submit" class="w-full rounded-lg bg-orange-600 px-6 py-3 text-white hover:bg-orange-700">Confirmer et payer</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
