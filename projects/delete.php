<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT image, video FROM projects WHERE id = :id');
$stmt->execute(['id' => $id]);
$project = $stmt->fetch();

if ($project) {
    $imgStmt = $pdo->prepare('SELECT image_path FROM project_images WHERE project_id = :id');
    $imgStmt->execute(['id' => $id]);
    $galleryPaths = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    $del = $pdo->prepare('DELETE FROM projects WHERE id = :id');
    $del->execute(['id' => $id]);

    $allImagePaths = array_unique(array_filter(array_merge([$project['image']], $galleryPaths)));
    foreach ($allImagePaths as $imgPath) {
        $path = dirname(__DIR__, 2) . $imgPath;
        if (is_file($path)) @unlink($path);
    }

    if ($project['video']) {
        $vpath = dirname(__DIR__, 2) . $project['video'];
        if (is_file($vpath)) @unlink($vpath);
    }

    setFlash('success', 'Projet supprimé (images, vidéo et dons associés ont aussi été supprimés).');
} else {
    setFlash('danger', 'Projet introuvable.');
}

header('Location: index.php');
exit;