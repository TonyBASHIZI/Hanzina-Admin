<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Vérifie que l'utilisateur est connecté ET admin, sinon redirige vers login */
function requireAdmin(): void
{
    if (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
        header('Location: ' . basePath() . 'login.php');
        exit;
    }
}

/** Calcule le chemin relatif vers la racine admin depuis n'importe quel sous-dossier */
function basePath(): string
{
    // Si on est dans un sous-dossier (projects/, donations/, users/), remonter d'un niveau
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $inSubfolder = preg_match('#/(projects|donations|users)/#', $script);
    return $inSubfolder ? '../' : '';
}

/** Ajoute un message flash affiché une seule fois */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Récupère et efface le message flash */
function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Échappement rapide pour l'affichage HTML */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Formate un montant en devise */
function money(float $amount): string
{
    return number_format($amount, 2, ',', ' ') . ' $';
}

/** Formate une date lisible */
function formatDate(string $date): string
{
    $dt = new DateTime($date);
    return $dt->format('d/m/Y H:i');
}

/** Génère / vérifie un jeton CSRF simple */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('Jeton de sécurité invalide (CSRF). Recharge la page et réessaie.');
    }
}

/** Upload sécurisé d'une image, retourne le nom de fichier généré ou null */
function handleImageUpload(string $inputName): ?string
{
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', "Erreur lors de l'upload du fichier.");
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        setFlash('danger', 'Format d\'image non autorisé (jpg, jpeg, png, gif, webp uniquement).');
        return null;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        setFlash('danger', 'L\'image dépasse la taille maximale de 5 Mo.');
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        setFlash('danger', 'Impossible d\'enregistrer le fichier uploadé.');
        return null;
    }
    return UPLOAD_URL . $filename;
}
/** Upload de plusieurs images (galerie), retourne un tableau de chemins */
function handleMultipleImageUpload(string $inputName): array
{
    $paths = [];

    if (empty($_FILES[$inputName]) || empty($_FILES[$inputName]['name'][0])) {
        return $paths;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $files = $_FILES[$inputName];
    $count = count($files['name']);

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            setFlash('danger', "Une image a été ignorée (format non autorisé) : " . e($files['name'][$i]));
            continue;
        }

        if ($files['size'][$i] > 5 * 1024 * 1024) {
            setFlash('danger', "Une image a été ignorée (taille > 5 Mo) : " . e($files['name'][$i]));
            continue;
        }

        $filename = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = UPLOAD_DIR . $filename;

        if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
            $paths[] = UPLOAD_URL . $filename;
        }
    }

    return $paths;
}

/** Upload d'une vidéo courte, retourne le chemin ou null */
function handleVideoUpload(string $inputName): ?string
{
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', "Erreur lors de l'upload de la vidéo.");
        return null;
    }

    $allowed = ['mp4', 'webm', 'mov'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        setFlash('danger', 'Format vidéo non autorisé (mp4, webm, mov uniquement).');
        return null;
    }

    if ($file['size'] > 30 * 1024 * 1024) {
        setFlash('danger', 'La vidéo dépasse la taille maximale de 30 Mo.');
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = 'vid_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        setFlash('danger', 'Impossible d\'enregistrer la vidéo uploadée.');
        return null;
    }

    return UPLOAD_URL . $filename;
}
