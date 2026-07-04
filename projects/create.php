<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$errors = [];
$data = ['title' => '', 'description' => '', 'goal_amount' => '', 'category' => '', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $data['title'] = trim($_POST['title'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['goal_amount'] = $_POST['goal_amount'] ?? '';
    $data['category'] = trim($_POST['category'] ?? '');
    $data['status'] = $_POST['status'] ?? 'active';

    if ($data['title'] === '') $errors[] = 'Le titre est obligatoire.';
    if ($data['description'] === '') $errors[] = 'La description est obligatoire.';
    if (!is_numeric($data['goal_amount']) || (float)$data['goal_amount'] <= 0) $errors[] = 'Objectif invalide.';
    if (!in_array($data['status'], ['active', 'inactive', 'completed'], true)) $errors[] = 'Statut invalide.';

    if (!$errors) {
        $imagePaths = handleMultipleImageUpload('images');
        $defaultIndex = isset($_POST['default_index']) ? (int)$_POST['default_index'] : 0;
        if (!isset($imagePaths[$defaultIndex])) {
            $defaultIndex = 0;
        }
        $coverImage = $imagePaths[$defaultIndex] ?? null;

        $videoPath = handleVideoUpload('video');

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO projects (title, description, goal_amount, raised_amount, image, video, category, status)
             VALUES (:title, :description, :goal_amount, 0, :image, :video, :category, :status)'
        );
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'],
            'goal_amount' => $data['goal_amount'],
            'image' => $coverImage,
            'video' => $videoPath,
            'category' => $data['category'],
            'status' => $data['status'],
        ]);
        $projectId = (int)$pdo->lastInsertId();

        if ($imagePaths) {
            $insertImg = $pdo->prepare(
                'INSERT INTO project_images (project_id, image_path, is_default) VALUES (:pid, :path, :is_default)'
            );
            foreach ($imagePaths as $i => $path) {
                $insertImg->execute([
                    'pid' => $projectId,
                    'path' => $path,
                    'is_default' => $i === $defaultIndex ? 1 : 0,
                ]);
            }
        }

        $pdo->commit();

        setFlash('success', 'Projet créé avec succès.');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Nouveau projet';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Nouveau projet</h3>
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

      <div class="mb-3">
        <label class="form-label">Titre *</label>
        <input type="text" name="title" class="form-control" required value="<?= e($data['title']) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Description *</label>
        <textarea name="description" class="form-control" rows="4" required><?= e($data['description']) ?></textarea>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Objectif ($) *</label>
          <input type="number" step="0.01" min="0" name="goal_amount" class="form-control" required value="<?= e($data['goal_amount']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Catégorie</label>
          <input type="text" name="category" class="form-control" value="<?= e($data['category']) ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Statut</label>
        <select name="status" class="form-select">
          <option value="active" <?= $data['status'] === 'active' ? 'selected' : '' ?>>Actif</option>
          <option value="inactive" <?= $data['status'] === 'inactive' ? 'selected' : '' ?>>Inactif</option>
          <option value="completed" <?= $data['status'] === 'completed' ? 'selected' : '' ?>>Terminé</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Images (plusieurs possibles)</label>
        <input type="file" name="images[]" id="imagesInput" class="form-control" accept="image/*" multiple>
        <small class="text-muted">Sélectionne une ou plusieurs images. Choisis ensuite celle par défaut ci-dessous.</small>
        <div id="imagePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
      </div>

      <div class="mb-3">
        <label class="form-label">Vidéo (optionnelle, courte)</label>
        <input type="file" name="video" class="form-control" accept="video/mp4,video/webm,video/quicktime">
        <small class="text-muted">Formats acceptés : mp4, webm, mov — 30 Mo max.</small>
      </div>

      <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg"></i> Enregistrer</button>
    </form>
  </div>
</div>

<script>
const input = document.getElementById('imagesInput');
const preview = document.getElementById('imagePreview');

input.addEventListener('change', () => {
  preview.innerHTML = '';
  const files = Array.from(input.files);

  files.forEach((file, index) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const wrapper = document.createElement('label');
      wrapper.className = 'text-center';
      wrapper.style.cursor = 'pointer';
      wrapper.innerHTML = `
        <div class="border rounded p-1 ${index === 0 ? 'border-danger border-2' : ''}" data-wrapper>
          <img src="${e.target.result}" style="width:80px;height:80px;object-fit:cover;border-radius:.3rem;">
          <div class="form-check form-check-inline mt-1 d-block">
            <input class="form-check-input" type="radio" name="default_index" value="${index}" ${index === 0 ? 'checked' : ''}>
            <small>Défaut</small>
          </div>
        </div>
      `;
      preview.appendChild(wrapper);
    };
    reader.readAsDataURL(file);
  });
});

preview?.addEventListener('change', (e) => {
  if (e.target.name === 'default_index') {
    document.querySelectorAll('[data-wrapper]').forEach(el => el.classList.remove('border-danger', 'border-2'));
    e.target.closest('[data-wrapper]').classList.add('border-danger', 'border-2');
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>