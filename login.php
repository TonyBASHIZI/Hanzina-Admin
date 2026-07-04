<?php
require_once __DIR__ . '/includes/functions.php';

// Si déjà connecté, redirige vers le dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Merci de renseigner tous les champs.';
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = :id1 OR mail = :id2) AND role = "admin" LIMIT 1');
        $stmt->execute(['id1' => $identifier, 'id2' => $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            header('Location: index.php');
            exit;
        }

        $error = 'Identifiants incorrects ou compte non administrateur.';
    }
}

$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — Hazina Funding Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
  <div class="card login-card shadow p-4">
    <div class="text-center mb-3">
      <i class="bi bi-heart-fill text-danger fs-1"></i>
      <h4 class="mt-2 mb-0">Hazina Funding</h4>
      <small class="text-muted">Panneau d'administration</small>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
      <div class="mb-3">
        <label class="form-label">Nom d'utilisateur ou email</label>
        <input type="text" name="identifier" class="form-control" required autofocus value="<?= e($_POST['identifier'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Mot de passe</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-danger w-100">
        <i class="bi bi-box-arrow-in-right"></i> Se connecter
      </button>
    </form>
  </div>
</div>
</body>
</html>
