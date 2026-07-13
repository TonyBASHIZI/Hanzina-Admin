<?php
/**
 * Connexion à la base de données via PDO
 * Modifie les constantes ci-dessous selon ton environnement.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'hazinafunding');
define('DB_USER', 'root');
define('DB_PASS', 'Friend2024');
define('DB_CHARSET', 'utf8mb4');

// Chemin absolu vers le dossier uploads (utilisé par les modules projects/users)
define('UPLOAD_DIR', dirname(__DIR__, 2) . '/uploads/');
define('UPLOAD_URL', '/uploads/');

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Erreur de connexion à la base de données : ' . $e->getMessage());
        }
    }

    return $pdo;
}
