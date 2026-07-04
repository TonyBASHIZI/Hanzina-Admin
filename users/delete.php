<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);

if ($id === (int)$_SESSION['admin_id']) {
    setFlash('danger', 'Tu ne peux pas supprimer ton propre compte.');
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT photo FROM users WHERE id = :id');
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if ($user) {
    $del = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $del->execute(['id' => $id]);

    if ($user['photo']) {
        $path = __DIR__ . '/../' . ltrim($user['photo'], '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }

    setFlash('success', 'Utilisateur supprimé (les dons liés sont conservés, sans compte associé).');
} else {
    setFlash('danger', 'Utilisateur introuvable.');
}

header('Location: index.php');
exit;
