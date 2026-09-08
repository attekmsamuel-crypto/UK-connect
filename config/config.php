<?php

define('DB_HOTE', 'localhost');
define('DB_NOM', 'ukconnect');
define('DB_UTILISATEUR', 'root');
define('DB_MOT_DE_PASSE', '');

define('NOM_SITE', 'UK-Connect');
define('RACINE', dirname(__DIR__));
define('DOSSIER_UPLOAD', RACINE . '/assets/uploads/');
define('TAILLE_MAX_PDF', 5 * 1024 * 1024);
define('TAILLE_MAX_IMAGE', 2 * 1024 * 1024);

$racineWeb = str_replace('\\', '/', RACINE);
$documentRoot = str_replace('\\', '/', rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
$base = '';
if ($documentRoot !== '' && strpos($racineWeb, $documentRoot) === 0) {
    $base = substr($racineWeb, strlen($documentRoot));
}
define('BASE_URL', rtrim($base, '/'));

function connexion_bdd(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOTE . ';dbname=' . DB_NOM . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_UTILISATEUR, DB_MOT_DE_PASSE, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $erreur) {
            exit('Connexion à la base impossible. Vérifiez que MySQL est démarré dans XAMPP et que la base « ' . DB_NOM . ' » a été importée.');
        }
    }

    return $pdo;
}
