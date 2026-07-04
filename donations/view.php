<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT d.*, p.title AS project_title, u.username
     FROM donations d
     JOIN projects p ON p.id = d.project_id
     LEFT JOIN users u ON u.id = d.user_id
     WHERE d.id = :id'
);
$stmt->execute(['id' => $id]);
$donation = $stmt->fetch();

if (!$donation) {
    setFlash('danger', 'Don introuvable.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $newStatus = $_POST['status'] ?? '';

    if (!in_array($newStatus, ['pending', 'completed', 'cancelled'], true)) {
        setFlash('danger', 'Statut invalide.');
    } else {
        $pdo->beginTransaction();

        $update = $pdo->prepare('UPDATE donations SET status = :status WHERE id = :id');
        $update->execute(['status' => $newStatus, 'id' => $id]);

        // Recalcule le montant collecté du projet en fonction des dons "completed"
        $recalc = $pdo->prepare(
            "UPDATE projects SET raised_amount = (
                SELECT COALESCE(SUM(amount), 0) FROM donations WHERE project_id = :pid AND status = 'completed'
             ) WHERE id = :pid"
        );
        $recalc->execute(['pid' => $donation['project_id']]);

        $pdo->commit();

        setFlash('success', 'Statut du don mis à jour.');
        header('Location: view.php?id=' . $id);
        exit;
    }
}

$pageTitle = 'Détail du don';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Détail du don #<?= (int)$donation['id'] ?></h3>
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Donateur</dt>
          <dd class="col-sm-8"><?= e($donation['donor_name'] ?: 'Anonymous') ?></dd>

          <dt class="col-sm-4">Email</dt>
          <dd class="col-sm-8"><?= e($donation['donor_email'] ?: '—') ?></dd>

          <dt class="col-sm-4">Compte lié</dt>
          <dd class="col-sm-8"><?= e($donation['username'] ?? '—') ?></dd>

          <dt class="col-sm-4">Projet</dt>
          <dd class="col-sm-8"><?= e($donation['project_title']) ?></dd>

          <dt class="col-sm-4">Montant</dt>
          <dd class="col-sm-8 fw-semibold text-success"><?= money((float)$donation['amount']) ?></dd>

          <dt class="col-sm-4">Message</dt>
          <dd class="col-sm-8"><?= e($donation['message']) ?: '—' ?></dd>

          <dt class="col-sm-4">Date</dt>
          <dd class="col-sm-8"><?= formatDate($donation['created_at']) ?></dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header bg-white fw-semibold">Statut du don</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
          <select name="status" class="form-select mb-3">
            <option value="pending" <?= $donation['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
            <option value="completed" <?= $donation['status'] === 'completed' ? 'selected' : '' ?>>Complété</option>
            <option value="cancelled" <?= $donation['status'] === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
          </select>
          <button type="submit" class="btn btn-danger w-100"><i class="bi bi-check-lg"></i> Mettre à jour</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
