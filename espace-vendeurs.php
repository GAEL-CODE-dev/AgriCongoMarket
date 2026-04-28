<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/auth.php';

ensureSessionStarted();

$currentUser = authCurrentUser();
if ($currentUser === null || $currentUser['role'] !== 'seller') {
    header('Location: connexion.php');
    exit;
}

$pdo = getDatabaseConnection();
$producerStmt = $pdo->prepare('SELECT * FROM producers WHERE user_id = ? LIMIT 1');
$producerStmt->execute([$currentUser['id']]);
$producer = $producerStmt->fetch() ?: null;

$products = [];
if ($producer !== null) {
    $productsStmt = $pdo->prepare(
        'SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.producer_id = ?
         ORDER BY p.created_at DESC'
    );
    $productsStmt->execute([$producer['id']]);
    $products = $productsStmt->fetchAll();
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriCongoMarket | Espace vendeurs</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-lg">
          <i class="fas fa-store text-xl"></i>
        </div>
        <div>
          <p class="text-sm uppercase tracking-[0.28em] text-orange-500">Espace vendeur</p>
          <h1 class="text-xl font-semibold">Bonjour <?= htmlspecialchars($currentUser['full_name']) ?></h1>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <a href="dashboard.php" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Retour dashboard</a>
        <a href="deconnexion.php" class="rounded-full bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600">Deconnexion</a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-6xl px-6 py-10">
    <?php if ($producer === null): ?>
      <div class="rounded-3xl border border-amber-200 bg-amber-50 p-8 text-amber-900 shadow-sm">
        <h2 class="text-2xl font-bold">Profil vendeur incomplet</h2>
        <p class="mt-3 text-slate-700">Votre compte vendeur existe, mais votre profil de producteur n est pas encore configure. Contactez l administrateur pour activer votre espace.</p>
      </div>
    <?php else: ?>
      <section class="mb-10 grid gap-6 md:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl">
          <p class="text-sm uppercase tracking-[0.28em] text-orange-500">Publication rapide</p>
          <h2 class="mt-4 text-3xl font-bold text-slate-900">Publiez vos produits ici</h2>
          <p class="mt-4 text-slate-600">Les produits publies seront accessibles aux clients sur la page catalogue.</p>

          <form id="sellerProductForm" class="mt-8 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="grid gap-4 md:grid-cols-2">
              <label class="block">
                <span class="text-sm font-semibold text-slate-700">Nom du produit</span>
                <input name="name" type="text" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200" placeholder="Tomates de Brazzaville">
              </label>
              <label class="block">
                <span class="text-sm font-semibold text-slate-700">Prix en FCFA</span>
                <input name="price_value" type="number" min="1" step="1" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200" placeholder="1500">
              </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <label class="block">
                <span class="text-sm font-semibold text-slate-700">Categorie</span>
                <select name="category" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200">
                  <option value="fruits-legumes">Fruits et legumes</option>
                  <option value="produits-laitiers">Produits frais et oeufs</option>
                  <option value="viandes">Viandes et volailles</option>
                  <option value="epicerie">Epicerie et miel</option>
                </select>
              </label>
              <label class="block">
                <span class="text-sm font-semibold text-slate-700">Unite</span>
                <input name="unit" type="text" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200" placeholder="kg, litre, piece">
              </label>
            </div>

            <label class="block">
              <span class="text-sm font-semibold text-slate-700">Stock disponible</span>
              <input name="stock_quantity" type="number" min="0" step="1" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200" placeholder="50">
            </label>

            <label class="block">
              <span class="text-sm font-semibold text-slate-700">Description</span>
              <textarea name="description" rows="4" required class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200" placeholder="Description breve"></textarea>
            </label>

            <label class="block">
              <span class="text-sm font-semibold text-slate-700">Image</span>
              <input name="image" type="text" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200" placeholder="img/produits.png ou https://...">
            </label>

            <label class="block">
              <span class="text-sm font-semibold text-slate-700">Style visuel</span>
              <select name="bg_gradient" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200">
                <option value="from-green-50 to-emerald-50">Vert frais</option>
                <option value="from-red-50 to-orange-50">Rouge lumineux</option>
                <option value="from-yellow-50 to-amber-50">Miel doux</option>
                <option value="from-orange-100 to-yellow-50">Orange marche</option>
              </select>
            </label>

            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-orange-600">
              <i class="fas fa-plus mr-2"></i>Publier mon produit
            </button>
          </form>

          <div id="sellerFormMessage" class="mt-4 hidden rounded-2xl px-4 py-3 text-sm"></div>
        </div>

        <aside class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl">
          <div class="mb-5 flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-50 text-green-700">
              <i class="fas fa-tag text-xl"></i>
            </div>
            <div>
              <p class="text-sm uppercase tracking-[0.24em] text-green-500">Statut du vendeur</p>
              <h2 class="text-2xl font-semibold text-slate-900">Produits publies</h2>
            </div>
          </div>
          <p class="text-sm text-slate-600">Vos produits publies s affichent ici et deviennent visibles sur la page client.</p>
          <div class="mt-6 space-y-4">
            <div class="rounded-3xl bg-slate-50 p-4">
              <p class="text-sm text-slate-500">Total produits</p>
              <p class="mt-2 text-3xl font-bold text-slate-900"><?= count($products) ?></p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-4">
              <p class="text-sm text-slate-500">Nom de la ferme</p>
              <p class="mt-2 font-semibold text-slate-900"><?= htmlspecialchars($producer['farm_name']) ?></p>
            </div>
          </div>
        </aside>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl">
        <div class="mb-6 flex items-center justify-between gap-4">
          <div>
            <p class="text-sm uppercase tracking-[0.28em] text-orange-500">Mes produits</p>
            <h2 class="mt-3 text-2xl font-bold text-slate-900">Catalogue vendeur</h2>
          </div>
          <a href="produits.html" class="inline-flex items-center gap-2 rounded-full bg-green-50 px-4 py-2 text-sm font-semibold text-green-700 transition hover:bg-green-100">
            <i class="fas fa-eye"></i>Voir cote client
          </a>
        </div>

        <?php if (empty($products)): ?>
          <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-12 text-center text-slate-600">
            <i class="fas fa-box-open text-4xl text-slate-400"></i>
            <p class="mt-4 text-lg font-semibold">Aucun produit publie</p>
            <p class="mt-2">Publiez votre premier produit avec le formulaire ci-dessus.</p>
          </div>
        <?php else: ?>
          <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($products as $product): ?>
              <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
                <div class="mb-4 h-44 overflow-hidden rounded-3xl bg-white">
                  <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover">
                </div>
                <div class="space-y-3">
                  <p class="text-xs uppercase tracking-[0.24em] text-slate-500"><?= htmlspecialchars((string) $product['category_name']) ?></p>
                  <h3 class="text-xl font-semibold text-slate-900"><?= htmlspecialchars($product['name']) ?></h3>
                  <p class="text-sm leading-6 text-slate-600"><?= htmlspecialchars($product['description']) ?></p>
                  <div class="mt-4 flex items-center justify-between gap-3">
                    <span class="font-semibold text-slate-900"><?= htmlspecialchars($product['price_label']) ?></span>
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700"><?= $product['is_active'] ? 'Actif' : 'Inactif' ?></span>
                  </div>
                  <p class="text-sm text-slate-500">Stock: <?= (int) $product['stock_quantity'] ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>

  <script>
    const sellerForm = document.getElementById('sellerProductForm');
    const sellerFormMessage = document.getElementById('sellerFormMessage');

    if (sellerForm) {
      sellerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        sellerFormMessage.classList.add('hidden');

        const formData = new FormData(sellerForm);
        const payload = {
          name: formData.get('name'),
          description: formData.get('description'),
          category: formData.get('category'),
          price_value: parseFloat(formData.get('price_value')) || 0,
          unit: formData.get('unit'),
          image: formData.get('image') || 'img/produits.png',
          bg_gradient: formData.get('bg_gradient') || 'from-green-50 to-emerald-50',
          stock_quantity: parseInt(formData.get('stock_quantity'), 10) || 0
        };

        try {
          const response = await fetch('api/products.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': formData.get('csrf_token')
            },
            body: JSON.stringify(payload)
          });
          const data = await response.json().catch(() => ({}));

          if (!response.ok) {
            throw new Error(data.error || 'Erreur lors de la publication.');
          }

          sellerFormMessage.textContent = data.message || 'Produit publie avec succes.';
          sellerFormMessage.className = 'mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700';
          sellerFormMessage.classList.remove('hidden');
          sellerForm.reset();
          setTimeout(() => window.location.reload(), 1000);
        } catch (error) {
          sellerFormMessage.textContent = error.message || 'Erreur lors de la publication.';
          sellerFormMessage.className = 'mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700';
          sellerFormMessage.classList.remove('hidden');
        }
      });
    }
  </script>
</body>
</html>
