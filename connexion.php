<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/auth.php';

ensureSessionStarted();

$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = ($_POST['mode'] ?? 'login') === 'register' ? 'register' : 'login';

    // Vérifier le token CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $errorMessage = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        try {
            if (!isDatabaseAvailable()) {
                throw new RuntimeException('La base de données n\'est pas disponible. Importez d\'abord schema.sql dans MySQL.');
            }

            $pdo = getDatabaseConnection();

            if ($mode === 'register') {
                $user = authRegisterUser($pdo, $_POST);
                authLoginUser($user);
                $successMessage = 'Compte créé avec succès. Vous êtes maintenant connecté.';
                // Redirection après inscription
                header('Location: dashboard.php');
                exit;
            } else {
                $user = authAttemptLogin($pdo, (string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
                $successMessage = 'Connexion réussie.';
                // Redirection après connexion
                header('Location: dashboard.php');
                exit;
            }
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }
    }
}

$currentUser = authCurrentUser();
$csrfToken = generateCsrfToken();

function pageMessageClass(bool $isError): string
{
    return $isError
        ? 'border-red-200 bg-red-50 text-red-700'
        : 'border-green-200 bg-green-50 text-green-700';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriCongoMarket | Connexion</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="productions.css">
</head>
<body class="bg-stone-50 text-gray-800">
  <header class="sticky top-0 z-40 border-b border-orange-100 bg-white/90 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
      <a href="index.html" class="text-2xl font-bold text-orange-600">AgriCongoMarket</a>
      <nav class="hidden items-center gap-8 md:flex">
        <a href="index.html" class="font-medium text-gray-700 hover:text-orange-600">Accueil</a>
        <a href="production.html" class="font-medium text-gray-700 hover:text-orange-600">Production</a>
        <a href="produits.html" class="font-medium text-gray-700 hover:text-orange-600">Produits</a>
        <a href="contact.html" class="font-medium text-gray-700 hover:text-orange-600">Contact</a>
        <?php if ($currentUser !== null && $currentUser['role'] === 'seller'): ?>
          <a href="dashboard.php" class="font-medium text-orange-700 hover:text-orange-600">Mon Dashboard</a>
        <?php else: ?>
          <a href="dashboard.php" class="font-medium text-gray-700 hover:text-orange-600">Dashboard</a>
        <?php endif; ?>
      </nav>
      <div class="hidden items-center gap-4 md:flex">
        <?php if ($currentUser !== null): ?>
          <span class="rounded-full bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700"><?= htmlspecialchars($currentUser['full_name']) ?></span>
          <a href="deconnexion.php" class="rounded-lg bg-gray-900 px-4 py-2 text-white hover:bg-black">Deconnexion</a>
        <?php else: ?>
          <a href="contact.html" class="font-medium text-orange-700 hover:text-orange-600">Besoin d aide ?</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-6xl px-6 py-16">
    <section class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
      <div class="rounded-[32px] bg-[linear-gradient(135deg,#111827,#14532d)] p-8 text-white shadow-2xl">
        <p class="text-sm uppercase tracking-[0.28em] text-orange-200">Espace compte</p>
        <h1 class="mt-4 text-4xl font-extrabold">Connectez-vous ou creez un compte vendeur/acheteur.</h1>
        <p class="mt-5 text-base leading-8 text-gray-200">Le backend est maintenant pret a gerer les utilisateurs. Une fois la base importee, tu peux creer des comptes et preparer les prochaines briques du projet.</p>

        <div class="mt-8 grid gap-4">
          <div class="rounded-3xl bg-white/10 p-5">
            <h2 class="font-bold">Acheteur</h2>
            <p class="mt-2 text-sm text-gray-200">Se connecter pour suivre ses commandes et son panier quand tu ajouteras cette partie.</p>
          </div>
          <div class="rounded-3xl bg-white/10 p-5">
            <h2 class="font-bold">Vendeur</h2>
            <p class="mt-2 text-sm text-gray-200">Creer un compte pour publier des produits depuis un futur espace vendeur.</p>
          </div>
        </div>
      </div>

      <div class="rounded-[32px] bg-white p-8 shadow-lg ring-1 ring-black/5">
        <div class="mb-6 flex flex-wrap gap-3">
          <a href="?mode=login" class="rounded-full px-4 py-2 text-sm font-semibold <?= $mode === 'login' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700' ?>">Connexion</a>
          <a href="?mode=register" class="rounded-full px-4 py-2 text-sm font-semibold <?= $mode === 'register' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700' ?>">Inscription</a>
        </div>

        <?php if ($errorMessage !== ''): ?>
          <div class="mb-6 rounded-2xl border px-4 py-3 <?= pageMessageClass(true) ?>"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if ($successMessage !== ''): ?>
          <div class="mb-6 rounded-2xl border px-4 py-3 <?= pageMessageClass(false) ?>"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($currentUser !== null): ?>
          <div class="rounded-3xl border border-green-200 bg-green-50 p-6">
            <h2 class="text-2xl font-bold text-gray-900">Vous etes connecte.</h2>
            <p class="mt-3 text-gray-600">Compte actif : <strong><?= htmlspecialchars($currentUser['email']) ?></strong> (<?= htmlspecialchars($currentUser['role']) ?>)</p>
            <div class="mt-5 flex gap-3">
              <?php if ($currentUser['role'] === 'seller'): ?>
                <a href="dashboard.php" class="rounded-2xl bg-orange-500 px-5 py-3 font-semibold text-white hover:bg-orange-600">Accéder à mon dashboard</a>
              <?php else: ?>
                <a href="produits.html" class="rounded-2xl bg-orange-500 px-5 py-3 font-semibold text-white hover:bg-orange-600">Voir les produits</a>
              <?php endif; ?>
              <a href="deconnexion.php" class="rounded-2xl border border-gray-200 px-5 py-3 font-semibold text-gray-700 hover:bg-gray-50">Se deconnecter</a>
            </div>
          </div>
        <?php else: ?>
          <form method="post" class="space-y-5">
            <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <?php if ($mode === 'register'): ?>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-gray-700">Nom complet</span>
                <input name="full_name" type="text" required class="w-full rounded-2xl border border-gray-200 bg-stone-50 px-4 py-3 outline-none focus:border-orange-400 focus:bg-white">
              </label>

              <div class="grid gap-5 md:grid-cols-2">
                <label class="block">
                  <span class="mb-2 block text-sm font-semibold text-gray-700">Telephone</span>
                  <input name="phone" type="tel" class="w-full rounded-2xl border border-gray-200 bg-stone-50 px-4 py-3 outline-none focus:border-orange-400 focus:bg-white">
                </label>
                <label class="block">
                  <span class="mb-2 block text-sm font-semibold text-gray-700">Profil</span>
                  <select name="role" class="w-full rounded-2xl border border-gray-200 bg-stone-50 px-4 py-3 outline-none focus:border-orange-400 focus:bg-white">
                    <option value="buyer">Acheteur</option>
                    <option value="seller">Vendeur</option>
                  </select>
                </label>
              </div>
            <?php endif; ?>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-gray-700">Email</span>
              <input name="email" type="email" required class="w-full rounded-2xl border border-gray-200 bg-stone-50 px-4 py-3 outline-none focus:border-orange-400 focus:bg-white">
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-gray-700">Mot de passe</span>
              <input name="password" type="password" required class="w-full rounded-2xl border border-gray-200 bg-stone-50 px-4 py-3 outline-none focus:border-orange-400 focus:bg-white">
            </label>

            <div class="flex flex-wrap gap-3">
              <button type="submit" class="rounded-2xl bg-orange-500 px-6 py-3 font-semibold text-white hover:bg-orange-600">
                <?= $mode === 'register' ? 'Creer mon compte' : 'Se connecter' ?>
              </button>
              <a href="contact.html" class="rounded-2xl border border-gray-200 px-6 py-3 font-semibold text-gray-700 hover:bg-gray-50">Besoin d aide</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>
