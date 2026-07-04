<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(d.donor_name LIKE :q OR d.donor_email LIKE :q OR p.title LIKE :q)';
    $params['q'] = "%$search%";
}
if ($status !== '' && in_array($status, ['pending', 'completed', 'cancelled'], true)) {
    $conditions[] = 'd.status = :status';
    $params['status'] = $status;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countSql = "SELECT COUNT(*) FROM donations d JOIN projects p ON p.id = d.project_id $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$sql = "SELECT d.*, p.title AS project_title
        FROM donations d
        JOIN projects p ON p.id = d.project_id
        $where
        ORDER BY d.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$donations = $stmt->fetchAll();

$pageTitle = 'Dons';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h3 class="mb-0">Dons</h3>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-6">
        <input type="text" name="q" class="form-control" placeholder="Rechercher par donateur, email ou projet..." value="<?= e($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">Tous les statuts</option>
          <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>En attente</option>
          <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Complété</option>
          <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100"><i class="bi bi-search"></i> Filtrer</button>
      </div>
      <?php if ($search !== '' || $status !== ''): ?>
      <div class="col-md-1">
        <a href="index.php" class="btn btn-outline-danger w-100" title="Réinitialiser"><i class="bi bi-x-lg"></i></a>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>Donateur</th>
          <th>Projet</th>
          <th>Montant</th>
          <th>Statut</th>
          <th>Date</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($donations as $d): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($d['donor_name'] ?: 'Anonymous') ?></div>
            <small class="text-muted"><?= e($d['donor_email']) ?></small>
          </td>
          <td><?= e($d['project_title']) ?></td>
          <td class="fw-semibold text-success"><?= money((float)$d['amount']) ?></td>
          <td>
            <?php
              $badge = ['completed' => 'success', 'pending' => 'warning', 'cancelled' => 'secondary'][$d['status']] ?? 'secondary';
            ?>
            <span class="badge text-bg-<?= $badge ?>"><?= e($d['status']) ?></span>
          </td>
          <td><?= formatDate($d['created_at']) ?></td>
          <td class="text-end">
            <a href="view.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <a href="delete.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Supprimer ce don ?');"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$donations): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Aucun don trouvé</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
