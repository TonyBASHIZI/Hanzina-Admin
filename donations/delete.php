<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT project_id FROM donations WHERE id = :id');
$stmt->execute(['id' => $id]);
$donation = $stmt->fetch();

if ($donation) {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM donations WHERE id = :id');
    $del->execute(['id' => $id]);

    $recalc = $pdo->prepare(
        "UPDATE projects SET raised_amount = (
            SELECT COALESCE(SUM(amount), 0) FROM donations WHERE project_id = :pid AND status = 'completed'
         ) WHERE id = :pid"
    );
    $recalc->execute(['pid' => $donation['project_id']]);

    $pdo->commit();

    setFlash('success', 'Don supprimé et montant du projet recalculé.');
} else {
    setFlash('danger', 'Don introuvable.');
}

header('Location: index.php');
exit;
