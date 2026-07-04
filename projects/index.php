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
    $where = 'WHERE title LIKE :q1 OR category LIKE :q2';
    $params['q1'] = "%$search%";
    $params['q2'] = "%$search%";
}

$total = $pdo->prepare("SELECT COUNT(*) FROM projects $where");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM projects $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

$pageTitle = 'Projets';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h3 class="mb-0">Projets</h3>
  <a href="create.php" class="btn btn-danger"><i class="bi bi-plus-lg"></i> Nouveau projet</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-8">
        <input type="text" name="q" class="form-control" placeholder="Rechercher par titre ou catégorie..." value="<?= e($search) ?>">
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
          <th>Image</th>
          <th>Titre</th>
          <th>Catégorie</th>
          <th>Objectif</th>
          <th>Collecté</th>
          <th>Progression</th>
          <th>Statut</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($projects as $p):
        $pct = $p['goal_amount'] > 0 ? min(100, round($p['raised_amount'] / $p['goal_amount'] * 100)) : 0;
        $imgSrc = $p['image'] ? '../' . ltrim($p['image'], '/') : null;
      ?>
        <tr>
          <td>
            <?php if ($imgSrc): ?>
              <img src="<?= e($imgSrc) ?>" class="thumb" alt="" onerror="this.onerror=null;this.src='https://placehold.co/48x48?text=%20';">
            <?php else: ?>
              <div class="thumb bg-light d-flex align-items-center justify-content-center"><i class="bi bi-image text-muted"></i></div>
            <?php endif; ?>
            <?php if (!empty($p['video'])): ?>
              <i class="bi bi-camera-video-fill text-danger ms-1" title="Vidéo disponible"></i>
            <?php endif; ?>
          </td>
          <td class="fw-semibold"><?= e($p['title']) ?></td>
          <td><?= e($p['category']) ?></td>
          <td><?= money((float)$p['goal_amount']) ?></td>
          <td><?= money((float)$p['raised_amount']) ?></td>
          <td style="width:140px;">
            <div class="progress"><div class="progress-bar bg-danger" style="width: <?= $pct ?>%"></div></div>
            <small class="text-muted"><?= $pct ?>%</small>
          </td>
          <td>
            <span class="badge text-bg-<?= $p['status'] === 'active' ? 'success' : ($p['status'] === 'completed' ? 'primary' : 'secondary') ?>">
              <?= e($p['status']) ?>
            </span>
          </td>
          <td class="text-end">
            <a href="edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <a href="delete.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Supprimer ce projet ainsi que tous ses dons, images et vidéo ?');">
               <i class="bi bi-trash"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$projects): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Aucun projet trouvé</td></tr>
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