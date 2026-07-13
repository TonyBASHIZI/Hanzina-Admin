<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'Utilisateur introuvable.');
    header('Location: index.php');
    exit;
}

$errors = [];
$data = $user;

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
    if ($password !== '' && strlen($password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    if (!in_array($data['role'], ['user', 'admin'], true)) $errors[] = 'Rôle invalide.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (username = :u OR mail = :m) AND id != :id');
        $check->execute(['u' => $data['username'], 'm' => $data['mail'], 'id' => $id]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'Ce nom d\'utilisateur ou cet email est déjà utilisé par un autre compte.';
        }
    }

    if (!$errors) {
        $photoPath = $user['photo'];
        $newPhoto = handleImageUpload('photo');
        if ($newPhoto) {
            $photoPath = $newPhoto;
        }

        if ($password !== '') {
            $stmt = $pdo->prepare(
                'UPDATE users SET username=:username, mail=:mail, telephone=:telephone, adresse=:adresse,
                 password=:password, photo=:photo, role=:role WHERE id=:id'
            );
            $stmt->execute([
                'username' => $data['username'],
                'mail' => $data['mail'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'],
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'photo' => $photoPath,
                'role' => $data['role'],
                'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET username=:username, mail=:mail, telephone=:telephone, adresse=:adresse,
                 photo=:photo, role=:role WHERE id=:id'
            );
            $stmt->execute([
                'username' => $data['username'],
                'mail' => $data['mail'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'],
                'photo' => $photoPath,
                'role' => $data['role'],
                'id' => $id,
            ]);
        }

        // Met à jour la session si l'admin modifie son propre compte
        if ($id === (int)$_SESSION['admin_id']) {
            $_SESSION['admin_username'] = $data['username'];
            $_SESSION['admin_role'] = $data['role'];
        }

        setFlash('success', 'Utilisateur mis à jour avec succès.');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Modifier utilisateur';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Modifier l'utilisateur</h3>
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
          <select name="role" class="form-select" <?= (int)$user['id'] === (int)$_SESSION['admin_id'] ? 'disabled' : '' ?>>
            <option value="user" <?= $data['role'] === 'user' ? 'selected' : '' ?>>Utilisateur</option>
            <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
          </select>
          <?php if ((int)$user['id'] === (int)$_SESSION['admin_id']): ?>
            <input type="hidden" name="role" value="<?= e($data['role']) ?>">
            <small class="text-muted">Tu ne peux pas changer ton propre rôle.</small>
          <?php endif; ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Adresse *</label>
        <input type="text" name="adresse" class="form-control" required value="<?= e($data['adresse']) ?>">
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Nouveau mot de passe</label>
          <input type="password" name="password" class="form-control" minlength="6" placeholder="Laisser vide pour ne pas changer">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Photo</label>
          <?php if ($user['photo']): ?>
            <div class="mb-2">
          <img src="<?= e($user['photo']) ?>" class="thumb" style="width:60px;height:60px;" onerror="this.src='https://placehold.co/60x60?text=%20'">            </div>
          <?php endif; ?>
          <input type="file" name="photo" class="form-control" accept="image/*">
        </div>
      </div>

      <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg"></i> Mettre à jour</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
