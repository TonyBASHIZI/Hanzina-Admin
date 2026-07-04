<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE username LIKE :q OR mail LIKE :q';
    $params['q'] = "%$search%";
}

$total = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

$pageTitle = 'Utilisateurs';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h3 class="mb-0">Utilisateurs</h3>
  <a href="create.php" class="btn btn-danger"><i class="bi bi-plus-lg"></i> Nouvel utilisateur</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-8">
        <input type="text" name="q" class="form-control" placeholder="Rechercher par nom ou email..." value="<?= e($search) ?>">
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100"><i class="bi bi-search"></i> Filtrer</button>
      </div>
      <?php if ($search !== ''): ?>
      <div class="col-md-2">
        <a href="index.php" class="btn btn-outline-danger w-100">Réinitialiser</a>
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
          <th>Utilisateur</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Rôle</th>
          <th>Inscrit le</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td class="fw-semibold"><?= e($u['username']) ?></td>
          <td><?= e($u['mail']) ?></td>
          <td><?= e($u['telephone']) ?></td>
          <td>
            <span class="badge text-bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>"><?= e($u['role']) ?></span>
          </td>
          <td><?= formatDate($u['created_at']) ?></td>
          <td class="text-end">
            <a href="edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php if ((int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
            <a href="delete.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Supprimer cet utilisateur ?');"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$users): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Aucun utilisateur trouvé</td></tr>
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
        <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
