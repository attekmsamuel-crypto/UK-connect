<?php
/**
 * ============================================================
 *  UK-Connect — Fonctions utilitaires réutilisables
 *  (/includes → fonctions PHP réutilisables : sécurité, etc.)
 * ============================================================
 */

/* Polyfills PHP 8 → PHP 7.x : str_contains / str_starts_with / str_ends_with.
   Évite « Call to undefined function » sur les XAMPP livrés avec PHP 7. */
if (!function_exists('str_contains')) {
    function str_contains($meule, $aiguille) {
        return $aiguille === '' || strpos((string)$meule, $aiguille) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($meule, $aiguille) {
        return strncmp((string)$meule, (string)$aiguille, strlen((string)$aiguille)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($meule, $aiguille) {
        $a = (string)$aiguille; $l = strlen($a);
        return $l === 0 || substr((string)$meule, -$l) === $a;
    }
}

/* Polyfill : si l'extension mbstring n'est pas active sur l'hébergement,
   on retombe sur les fonctions standard (suffisantes pour l'UTF-8 usuel). */
if (!function_exists('mb_strlen')) {
    function mb_strlen($s) { return strlen($s); }
    function mb_substr($s, $debut, $longueur = null) {
        return $longueur === null ? substr($s, $debut) : substr($s, $debut, $longueur);
    }
    function mb_strrpos($s, $aiguille) { return strrpos($s, $aiguille); }
    function mb_strtoupper($s) { return strtoupper($s); }
    function mb_strtolower($s) { return strtolower($s); }
}

/**
 * Échappe une chaîne pour l'affichage HTML — protection anti-XSS.
 * Wrapper du fameux htmlspecialchars($v, ENT_QUOTES, 'UTF-8') :
 * une seule définition, les mêmes options partout, un appel court « e() »
 * sur les 330 sorties du projet. (Même principe que le helper e() de Laravel.)
 */
function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Construit une URL interne à partir de BASE_URL. */
function url(string $chemin = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($chemin, '/');
}

/** Redirection immédiate puis arrêt du script. */
function redirect(string $vers): void
{
    header('Location: ' . $vers);
    exit;
}

/* ---------------- Messages flash (affichés une seule fois) ---------------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_afficher(): string
{
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $classe = $f['type'] === 'erreur' ? 'alerte alerte-rouge' : 'alerte alerte-verte';
    return '<div class="' . $classe . '">' . e($f['message']) . '</div>';
}

/* ---------------- Protection CSRF (jeton anti-falsification) ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verifier(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
            http_response_code(403);
            die('Requête invalide (jeton de sécurité CSRF manquant ou expiré). 
                 Retournez en arrière et réessayez.');
        }
    }
}

/* ---------------- Formatage ---------------- */

function date_fr(?string $ts): string
{
    if (!$ts) return '—';
    $mois = [1=>'janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
    $t = strtotime($ts);
    return date('j', $t) . ' ' . $mois[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function extrait(?string $texte, int $max = 180): string
{
    $texte = trim((string)$texte);
    if (mb_strlen($texte) <= $max) return $texte;
    $coupe = mb_substr($texte, 0, $max);
    return mb_substr($coupe, 0, mb_strrpos($coupe, ' ')) . '…';
}

/* ---------------- Téléversement d'images (avatar, bannière…) ---------------- */

/**
 * Enregistre une image (JPG/PNG/WebP) dans assets/uploads/<sousDossier>/
 * et retourne le nom du fichier stocké.
 */
function televerser_image(string $champ, string $sousDossier = 'avatars',
                          string $prefixe = 'image', int $maxMo = 2): ?string
{
    if (empty($_FILES[$champ]['name']) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$champ]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur lors du téléversement de l\'image.');
    }
    if ($_FILES[$champ]['size'] > $maxMo * 1024 * 1024) {
        throw new RuntimeException("Image trop volumineuse ($maxMo Mo maximum).");
    }
    $extension = strtolower(pathinfo($_FILES[$champ]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Formats acceptés : JPG, PNG ou WebP.');
    }
    $dossier = UPLOAD_DIR . $sousDossier . '/';
    if (!is_dir($dossier)) {
        mkdir($dossier, 0777, true);
    }
    $nom = $prefixe . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
    if (!move_uploaded_file($_FILES[$champ]['tmp_name'], $dossier . $nom)) {
        throw new RuntimeException('Impossible d\'enregistrer l\'image sur le serveur.');
    }
    return $nom;
}

/* ---------------- Téléversement du PDF d'un projet ---------------- */

/**
 * Enregistre un PDF téléversé et retourne le nom du fichier stocké,
 * ou null si aucun fichier / erreur. Le nom est unique et sécurisé
 * (extension .pdf imposée, caractères dangereux supprimés).
 */
function televerser_pdf(string $champ): ?string
{
    if (empty($_FILES[$champ]['name']) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$champ]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur lors du téléversement du fichier.');
    }
    if ($_FILES[$champ]['size'] > UPLOAD_MAX_OCTETS) {
        throw new RuntimeException('Fichier trop volumineux (5 Mo maximum).');
    }
    $extension = strtolower(pathinfo($_FILES[$champ]['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        throw new RuntimeException('Seuls les fichiers PDF sont acceptés.');
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES[$champ]['name'], PATHINFO_FILENAME));
    $nom  = date('Ymd_His') . '_' . uniqid() . '_' . substr($base, 0, 40) . '.pdf';
    if (!move_uploaded_file($_FILES[$champ]['tmp_name'], UPLOAD_DIR . $nom)) {
        throw new RuntimeException('Impossible d\'enregistrer le fichier sur le serveur.');
    }
    return $nom;
}
