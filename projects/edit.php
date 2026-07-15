<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
$stmt->execute(['id' => $id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Projet introuvable.');
    header('Location: index.php');
    exit;
}

$errors = [];
$data = $project;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $data['title'] = trim($_POST['title'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['goal_amount'] = $_POST['goal_amount'] ?? '';
    $data['raised_amount'] = $_POST['raised_amount'] ?? '';
    $data['category'] = trim($_POST['category'] ?? '');
    $data['status'] = $_POST['status'] ?? 'active';

    if ($data['title'] === '') $errors[] = 'Le titre est obligatoire.';
    if ($data['description'] === '') $errors[] = 'La description est obligatoire.';
    if (!is_numeric($data['goal_amount']) || (float)$data['goal_amount'] <= 0) $errors[] = 'Objectif invalide.';
    if (!is_numeric($data['raised_amount']) || (float)$data['raised_amount'] < 0) $errors[] = 'Montant collecté invalide.';
    if (!in_array($data['status'], ['active', 'inactive', 'completed'], true)) $errors[] = 'Statut invalide.';

    if (!$errors) {
        $pdo->beginTransaction();

        $toDelete = $_POST['delete_images'] ?? [];
        if ($toDelete) {
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $sel = $pdo->prepare("SELECT id, image_path FROM project_images WHERE id IN ($placeholders) AND project_id = ?");
            $sel->execute([...$toDelete, $id]);
            $rows = $sel->fetchAll();

            $del = $pdo->prepare("DELETE FROM project_images WHERE id IN ($placeholders) AND project_id = ?");
            $del->execute([...$toDelete, $id]);

            foreach ($rows as $row) {
                $path = dirname(__DIR__, 2) . $row['image_path'];
                if (is_file($path)) @unlink($path);
            }
        }

        $newImages = handleMultipleImageUpload('images');
        if ($newImages) {
            $insertImg = $pdo->prepare('INSERT INTO project_images (project_id, image_path, is_default) VALUES (:pid, :path, 0)');
            foreach ($newImages as $path) {
                $insertImg->execute(['pid' => $id, 'path' => $path]);
            }
        }

        $defaultChoice = $_POST['default_choice'] ?? '';
        $coverImage = $project['image'];

        if (str_starts_with($defaultChoice, 'existing:')) {
            $imgId = (int)substr($defaultChoice, 9);
            $find = $pdo->prepare('SELECT image_path FROM project_images WHERE id = :iid AND project_id = :pid');
            $find->execute(['iid' => $imgId, 'pid' => $id]);
            $found = $find->fetchColumn();
            if ($found) $coverImage = $found;
        } elseif (str_starts_with($defaultChoice, 'new:')) {
            $idx = (int)substr($defaultChoice, 4);
            if (isset($newImages[$idx])) $coverImage = $newImages[$idx];
        }

        $pdo->prepare('UPDATE project_images SET is_default = 0 WHERE project_id = :pid')->execute(['pid' => $id]);
        $pdo->prepare('UPDATE project_images SET is_default = 1 WHERE project_id = :pid AND image_path = :path')
            ->execute(['pid' => $id, 'path' => $coverImage]);

        $videoPath = $project['video'];
        if (!empty($_POST['remove_video'])) {
            if ($videoPath) {
                $vpath = dirname(__DIR__, 2) . $videoPath;
                if (is_file($vpath)) @unlink($vpath);
            }
            $videoPath = null;
        }
        $newVideo = handleVideoUpload('video');
        if ($newVideo) {
            if ($videoPath) {
                $vpath = dirname(__DIR__, 2) . $videoPath;
                if (is_file($vpath)) @unlink($vpath);
            }
            $videoPath = $newVideo;
        }

        $upd = $pdo->prepare(
            'UPDATE projects SET title=:title, description=:description, goal_amount=:goal_amount,
             raised_amount=:raised_amount, image=:image, video=:video, category=:category, status=:status
             WHERE id=:id'
        );
        $upd->execute([
            'title' => $data['title'],
            'description' => $data['description'],
            'goal_amount' => $data['goal_amount'],
            'raised_amount' => $data['raised_amount'],
            'image' => $coverImage,
            'video' => $videoPath,
            'category' => $data['category'],
            'status' => $data['status'],
            'id' => $id,
        ]);

        $pdo->commit();

        setFlash('success', 'Projet mis à jour avec succès.');
        header('Location: index.php');
        exit;
    }
}

$gallery = $pdo->prepare('SELECT * FROM project_images WHERE project_id = :pid ORDER BY is_default DESC, created_at ASC');
$gallery->execute(['pid' => $id]);
$galleryImages = $gallery->fetchAll();

$pageTitle = 'Modifier projet';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Modifier le projet</h3>
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
          <label class="form-label">Montant collecté ($)</label>
          <input type="number" step="0.01" min="0" name="raised_amount" class="form-control" value="<?= e($data['raised_amount']) ?>">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Catégorie</label>
          <select name="category" class="form-select">
              <option value="">— Choisir une catégorie —</option>
              <?php foreach (['Éducation', 'Santé', 'Urgence humanitaire', 'Alimentation', 'Eau & Assainissement', 'Logement', 'Infrastructures & Routes', 'Orphelinat', 'Personnes déplacées & Réfugiés', 'Personnes âgées', 'Personnes handicapées', 'Autonomisation des femmes', 'Agriculture', 'Environnement', 'Sport & Culture', 'Technologie & Innovation', 'Autre'] as $cat): ?>
                  <option value="<?= e($cat) ?>" <?= $data['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
              <?php endforeach; ?>
          </select>
      </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Statut</label>
          <select name="status" class="form-select">
            <option value="active" <?= $data['status'] === 'active' ? 'selected' : '' ?>>Actif</option>
            <option value="inactive" <?= $data['status'] === 'inactive' ? 'selected' : '' ?>>Inactif</option>
            <option value="completed" <?= $data['status'] === 'completed' ? 'selected' : '' ?>>Terminé</option>
          </select>
        </div>
      </div>

      <hr>

      <div class="mb-3">
        <label class="form-label fw-semibold">Galerie d'images</label>
        <?php if ($galleryImages): ?>
          <div class="d-flex flex-wrap gap-3 mb-3">
            <?php foreach ($galleryImages as $img): ?>
              <div class="text-center border rounded p-2 <?= $img['is_default'] ? 'border-danger border-2' : '' ?>">
                <img src="<?= e($img['image_path']) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:.3rem;" onerror="this.src='https://placehold.co/90x90?text=%20'">
                <div class="form-check form-check-inline mt-1 d-block">
                  <input class="form-check-input" type="radio" name="default_choice" value="existing:<?= (int)$img['id'] ?>" <?= $img['is_default'] ? 'checked' : '' ?>>
                  <small>Défaut</small>
                </div>
                <div class="form-check form-check-inline d-block">
                  <input class="form-check-input" type="checkbox" name="delete_images[]" value="<?= (int)$img['id'] ?>" id="del<?= (int)$img['id'] ?>">
                  <label class="form-check-label text-danger small" for="del<?= (int)$img['id'] ?>">Supprimer</label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted">Aucune image pour le moment.</p>
        <?php endif; ?>

        <label class="form-label">Ajouter de nouvelles images</label>
        <input type="file" name="images[]" id="imagesInput" class="form-control" accept="image/*" multiple>
        <small class="text-muted">Les nouvelles images pourront aussi être choisies comme image par défaut ci-dessous.</small>
        <div id="imagePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
      </div>

      <hr>

      <div class="mb-3">
        <label class="form-label fw-semibold">Vidéo</label><br>
        <?php if ($project['video']): ?>
          <video src="<?= e($project['video']) ?>" controls style="max-width:280px;" class="d-block mb-2 rounded"></video>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="remove_video" value="1" id="removeVideo">
            <label class="form-check-label text-danger" for="removeVideo">Supprimer la vidéo actuelle</label>
          </div>
        <?php else: ?>
          <p class="text-muted">Aucune vidéo pour le moment.</p>
        <?php endif; ?>
        <input type="file" name="video" class="form-control" accept="video/mp4,video/webm,video/quicktime">
        <small class="text-muted">Formats acceptés : mp4, webm, mov — 30 Mo max. Remplace automatiquement la vidéo actuelle si tu en uploades une nouvelle.</small>
      </div>

      <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg"></i> Mettre à jour</button>
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
        <div class="border rounded p-1" data-wrapper>
          <img src="${e.target.result}" style="width:80px;height:80px;object-fit:cover;border-radius:.3rem;">
          <div class="form-check form-check-inline mt-1 d-block">
            <input class="form-check-input" type="radio" name="default_choice" value="new:${index}">
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
  if (e.target.name === 'default_choice') {
    document.querySelectorAll('[data-wrapper]').forEach(el => el.classList.remove('border-danger', 'border-2'));
    if (e.target.closest('[data-wrapper]')) {
      e.target.closest('[data-wrapper]').classList.add('border-danger', 'border-2');
    }
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>