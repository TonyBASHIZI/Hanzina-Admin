<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$errors = [];
$data = ['username' => '', 'mail' => '', 'telephone' => '', 'adresse' => '', 'role' => 'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $data['username'] = trim($_POST['username'] ?? '');
    $data['mail'] = trim($_POST['mail'] ?? '');
    $data['telephone'] = trim($_POST['telephone'] ?? '');
    $data['adresse'] = trim($_POST['adresse'] ?? '');
    $data['role'] = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';

    if ($data['username'] === '') $errors[] = 'Le nom d\'utilisateur est obligatoire.';
    if (!filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
    if ($data['telephone'] === '') $errors[] = 'Le téléphone est obligatoire.';
    if ($data['adresse'] === '') $errors[] = 'L\'adresse est obligatoire.';
    if (strlen($password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    if (!in_array($data['role'], ['user', 'admin'], true)) $errors[] = 'Rôle invalide.';

    if (!$errors) {
        // Vérifie unicité username / email
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u OR mail = :m');
        $check->execute(['u' => $data['username'], 'm' => $data['mail']]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'Ce nom d\'utilisateur ou cet email est déjà utilisé.';
        }
    }

    if (!$errors) {
        $photoPath = handleImageUpload('photo');

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, mail, telephone, adresse, password, photo, role)
             VALUES (:username, :mail, :telephone, :adresse, :password, :photo, :role)'
        );
        $stmt->execute([
            'username' => $data['username'],
            'mail' => $data['mail'],
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'],
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'photo' => $photoPath,
            'role' => $data['role'],
        ]);

        setFlash('success', 'Utilisateur créé avec succès.');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Nouvel utilisateur';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Nouvel utilisateur</h3>
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Nom d'utilisateur *</label>
          <input type="text" name="username" class="form-control" required value="<?= e($data['username']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Email *</label>
          <input type="email" name="mail" class="form-control" required value="<?= e($data['mail']) ?>">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Téléphone *</label>
          <input type="text" name="telephone" class="form-control" required value="<?= e($data['telephone']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Rôle</label>
          <select name="role" class="form-select">
            <option value="user" <?= $data['role'] === 'user' ? 'selected' : '' ?>>Utilisateur</option>
            <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Adresse *</label>
        <input type="text" name="adresse" class="form-control" required value="<?= e($data['adresse']) ?>">
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Mot de passe *</label>
          <input type="password" name="password" class="form-control" required minlength="6">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Photo</label>
          <input type="file" name="photo" class="form-control" accept="image/*">
        </div>
      </div>

      <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg"></i> Enregistrer</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
