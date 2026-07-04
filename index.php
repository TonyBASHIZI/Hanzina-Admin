<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();
$pdo = getPDO();

$nbProjects  = $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
$nbDonations = $pdo->query('SELECT COUNT(*) FROM donations')->fetchColumn();
$nbUsers     = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalRaised = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status = 'completed'")->fetchColumn();

$recentDonations = $pdo->query(
    'SELECT d.*, p.title AS project_title
     FROM donations d
     JOIN projects p ON p.id = d.project_id
     ORDER BY d.created_at DESC
     LIMIT 5'
)->fetchAll();

$projects = $pdo->query('SELECT * FROM projects ORDER BY created_at DESC LIMIT 5')->fetchAll();

$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Tableau de bord</h3>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card stat-card bg-brand1">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="small">Projets</div>
          <div class="fs-3 fw-bold"><?= (int)$nbProjects ?></div>
        </div>
        <i class="bi bi-kanban"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card bg-brand2">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="small">Dons</div>
          <div class="fs-3 fw-bold"><?= (int)$nbDonations ?></div>
        </div>
        <i class="bi bi-cash-coin"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card bg-brand3">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="small">Total collecté</div>
          <div class="fs-4 fw-bold"><?= money((float)$totalRaised) ?></div>
        </div>
        <i class="bi bi-graph-up-arrow"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card bg-brand4">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="small">Utilisateurs</div>
          <div class="fs-3 fw-bold"><?= (int)$nbUsers ?></div>
        </div>
        <i class="bi bi-people"></i>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        Derniers projets
        <a href="projects/index.php" class="btn btn-sm btn-outline-danger">Voir tout</a>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead>
            <tr><th>Projet</th><th>Objectif</th><th>Collecté</th><th>Statut</th></tr>
          </thead>
          <tbody>
          <?php foreach ($projects as $p): ?>
            <tr>
              <td><?= e($p['title']) ?></td>
              <td><?= money((float)$p['goal_amount']) ?></td>
              <td><?= money((float)$p['raised_amount']) ?></td>
              <td>
                <span class="badge text-bg-<?= $p['status'] === 'active' ? 'success' : ($p['status'] === 'completed' ? 'primary' : 'secondary') ?>">
                  <?= e($p['status']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$projects): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">Aucun projet</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        Derniers dons
        <a href="donations/index.php" class="btn btn-sm btn-outline-danger">Voir tout</a>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($recentDonations as $d): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?= e($d['donor_name'] ?: 'Anonymous') ?></div>
              <small class="text-muted"><?= e($d['project_title']) ?></small>
            </div>
            <span class="badge text-bg-success rounded-pill"><?= money((float)$d['amount']) ?></span>
          </li>
        <?php endforeach; ?>
        <?php if (!$recentDonations): ?>
          <li class="list-group-item text-center text-muted py-3">Aucun don</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
