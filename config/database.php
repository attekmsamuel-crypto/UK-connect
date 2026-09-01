<?php
/**
 * ============================================================
 *  UK-Connect — Configuration & connexion à la base (PDO)
 *  Conforme à la fiche technique : /config → connexion BDD
 * ------------------------------------------------------------
 *  En local (XAMPP) : utilisateur root, mot de passe vide.
 *  En production : créer un compte dédié (voir README, section
 *  « Mise en production ») — jamais root.
 * ============================================================
 */

// --- Paramètres de connexion (à adapter en production) ------
define('DB_HOST', 'localhost');
define('DB_NAME', 'ukconnect');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP par défaut : vide

// --- Paramètres de l'application ----------------------------
define('APP_NAME', 'UK-Connect');
define('BASE_URL', '/uk-connect');                       // dossier dans htdocs
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');   // PDF des projets
define('UPLOAD_MAX_OCTETS', 5 * 1024 * 1024);            // 5 Mo max

/*
 * IDs des référentiels — fixes car pré-chargés par le script
 * database/ukconnect_v2.sql (section 6). Cela évite de faire une
 * requête pour lire un code à chaque page : simple et lisible.
 */
const ROLE_ETUDIANT       = 1;
const ROLE_PARTENAIRE     = 2;
const ROLE_ADMIN          = 3;

const STVAL_EN_ATTENTE    = 1;   // sujet : en attente de modération
const STVAL_VALIDE        = 2;   // sujet : validé (public)
const STVAL_REJETE        = 3;   // sujet : rejeté

const STCAND_EN_ATTENTE   = 1;   // candidature : en cours d'examen
const STCAND_RETENUE      = 2;   // candidature : retenue
const STCAND_NON_RETENUE  = 3;   // candidature : non retenue

/**
 * Retourne la connexion PDO (une seule instance partagée).
 * Requêtes préparées systématiques = protection anti-injection SQL.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Message volontairement générique : ne jamais exposer les détails techniques
            die('Impossible de se connecter à la base de données. 
                 Vérifiez que MySQL est démarré dans XAMPP et que la base « ukconnect » 
                 est importée (voir README.md).');
        }
    }
    return $pdo;
}
