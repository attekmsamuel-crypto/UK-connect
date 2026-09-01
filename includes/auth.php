<?php
/**
 * ============================================================
 *  UK-Connect — Authentification, sessions et contrôle des rôles
 *  (le contrôle des rôles est exigé à chaque action sensible
 *   par le cahier des charges, section « Contraintes techniques »)
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/** Redémarre la session si nécessaire (à appeler sur chaque page). */
function session_init(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Utilisateur connecté (tableau) ou null.
 * Une seule lecture en base par requête (cache statique).
 */
function current_user(): ?array
{
    static $cache = false;
    global $USER_COURANT;
    if ($cache !== false) return $USER_COURANT;
    $cache = true;
    $USER_COURANT = null;

    session_init();
    if (empty($_SESSION['user_id'])) return null;

    $st = db()->prepare(
        'SELECT u.*, r.code AS role_code, f.sigle AS faculte_sigle
         FROM utilisateurs u
         JOIN roles r        ON r.id = u.role_id
         LEFT JOIN facultes f ON f.id = u.id_faculte
         WHERE u.id = ? AND u.actif = 1'
    );
    $st->execute([$_SESSION['user_id']]);
    $u = $st->fetch();
    if (!$u) { // compte supprimé ou désactivé pendant la session
        unset($_SESSION['user_id']);
        return null;
    }
    $USER_COURANT = $u;
    return $u;
}

/** Raccourcis de rôle. */
function est_connecte(): bool  { return current_user() !== null; }
function est_etudiant(): bool  { $u = current_user(); return $u && $u['role_code'] === 'etudiant'; }
function est_partenaire(): bool{ $u = current_user(); return $u && $u['role_code'] === 'partenaire'; }
function est_admin(): bool     { $u = current_user(); return $u && $u['role_code'] === 'admin'; }

/** Interdit l'accès aux visiteurs non connectés. */
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('erreur', 'Veuillez vous connecter pour accéder à cette page.');
        redirect(url('connexion.php'));
    }
    return $u;
}

/** Interdit l'accès aux rôles non autorisés (contrôle à chaque action sensible). */
function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role_code'], $roles, true)) {
        http_response_code(403);
        flash('erreur', 'Accès refusé : vous n\'avez pas les droits nécessaires pour cette page.');
        redirect(url('index.php'));
    }
    return $u;
}

/**
 * Connexion : vérifie email + mot de passe haché (password_verify).
 * Ne révèle jamais lequel des deux est incorrect.
 */
function login(string $email, string $motDePasse): bool
{
    $st = db()->prepare(
        'SELECT u.id, u.mot_de_passe, u.actif, r.code AS role_code
         FROM utilisateurs u JOIN roles r ON r.id = u.role_id
         WHERE u.email = ?'
    );
    $st->execute([$email]);
    $u = $st->fetch();

    if (!$u || !password_verify($motDePasse, $u['mot_de_passe'])) {
        flash('erreur', 'Email ou mot de passe incorrect.');
        return false;
    }
    if (!$u['actif']) {
        flash('erreur', 'Ce compte est désactivé. Contactez l\'administration.');
        return false;
    }
    session_regenerate_id(true);       // anti-fixation de session
    $_SESSION['user_id'] = (int)$u['id'];
    unset($_SESSION['csrf']);          // nouveau jeton CSRF après connexion
    return true;
}

/** Déconnexion complète. */
function logout(): void
{
    session_init();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Inscription d'un compte étudiant ou partenaire.
 * La cohérence métier (étudiant ⇒ faculté, partenaire ⇒ structure) est
 * garantie par les triggers de la base ; on la vérifie aussi ici pour
 * un message d'erreur convivial.
 * Retourne l'id du nouveau compte, ou lance une exception (message clair).
 */
function register(array $d): int
{
    $email = trim(strtolower($d['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Adresse email invalide.');
    }
    if (strlen($d['mot_de_passe']) < 8) {
        throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
    }
    if ($d['mot_de_passe'] !== $d['confirmation']) {
        throw new InvalidArgumentException('Les deux mots de passe ne correspondent pas.');
    }

    $roleId = ($d['role'] === 'partenaire') ? ROLE_PARTENAIRE : ROLE_ETUDIANT;
    $faculte = ($roleId === ROLE_ETUDIANT) ? (int)$d['id_faculte'] : null;
    $structure = ($roleId === ROLE_PARTENAIRE) ? trim($d['nom_structure']) : null;

    if ($roleId === ROLE_ETUDIANT && !$faculte) {
        throw new InvalidArgumentException('Veuillez sélectionner votre faculté / institut.');
    }
    if ($roleId === ROLE_PARTENAIRE && ($structure === '' || $structure === null)) {
        throw new InvalidArgumentException('Veuillez indiquer le nom de votre structure (entreprise, ONG, collectivité…).');
    }

    $st = db()->prepare(
        'INSERT INTO utilisateurs (nom, prenom, email, telephone, mot_de_passe, role_id, id_faculte, nom_structure)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    try {
        $st->execute([
            trim($d['nom']), trim($d['prenom']), $email, trim($d['telephone'] ?? '') ?: null,
            password_hash($d['mot_de_passe'], PASSWORD_DEFAULT),  // hachage bcrypt — jamais en clair
            $roleId, $faculte, $structure,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') { // violation d'unicité (email déjà présent)
            throw new InvalidArgumentException('Cet email est déjà utilisé. Utilisez « Mot de passe oublié » ou un autre email.');
        }
        throw $e;
    }
    return (int)db()->lastInsertId();
}
