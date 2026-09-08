<?php

require_once __DIR__ . '/../config/config.php';

function e($valeur): string
{
    return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8');
}

function lien(string $chemin = ''): string
{
    return BASE_URL . '/' . ltrim($chemin, '/');
}

function rediriger(string $vers): void
{
    header('Location: ' . $vers);
    exit;
}

function message(string $type, string $texte): void
{
    $_SESSION['message'] = ['type' => $type, 'texte' => $texte];
}

function afficher_message(): string
{
    if (empty($_SESSION['message'])) {
        return '';
    }

    $m = $_SESSION['message'];
    unset($_SESSION['message']);
    $erreur = $m['type'] === 'erreur';
    $classe = $erreur ? 'message message-erreur' : 'message message-succes';

    return '<div class="' . $classe . '">' . icone($erreur ? 'croix' : 'valider', 19)
         . '<div>' . e($m['texte']) . '</div></div>';
}

function jeton_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function champ_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . jeton_csrf() . '">';
}

function verifier_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $recu = $_POST['csrf'] ?? '';
    if (!is_string($recu) || !hash_equals($_SESSION['csrf'] ?? '', $recu)) {
        http_response_code(403);
        exit('Formulaire expiré. Revenez en arrière et recommencez.');
    }
}

function date_courte(?string $date): string
{
    if (empty($date)) {
        return '—';
    }

    $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
             'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $t = strtotime($date);

    return (int)date('j', $t) . ' ' . $mois[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function resume_court(?string $texte, int $maximum = 180): string
{
    $texte = trim(preg_replace('/\s+/', ' ', (string)$texte));
    if (mb_strlen($texte) <= $maximum) {
        return $texte;
    }

    $coupe = mb_substr($texte, 0, $maximum);
    $espace = mb_strrpos($coupe, ' ');

    return ($espace ? mb_substr($coupe, 0, $espace) : $coupe) . '…';
}

function fleche(string $sens = 'droite'): string
{
    $trace = $sens === 'gauche'
        ? 'M13.5 8h-11m4.5 4.5L2.5 8 7 3.5'
        : 'M2.5 8h11M9 3.5 13.5 8 9 12.5';

    return '<svg class="fleche" width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">'
         . '<path d="' . $trace . '" stroke="currentColor" stroke-width="1.6" '
         . 'stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function icone(string $nom, int $taille = 18): string
{
    $traces = [
        'fleche'        => '<path d="M4 12h15M13 6l6 6-6 6"/>',
        'fleche-gauche' => '<path d="M20 12H5M11 6l-6 6 6 6"/>',
        'recherche'     => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>',
        'cloche'        => '<path d="M18.5 9a6.5 6.5 0 1 0-13 0c0 5.2-2 6.5-2 6.5h17s-2-1.3-2-6.5"/><path d="M13.8 19.5a2.1 2.1 0 0 1-3.6 0"/>',
        'personne'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.1 3.6-6.4 8-6.4s8 2.3 8 6.4"/>',
        'immeuble'      => '<path d="M3 21h18M5 21V6l7-3 7 3v15"/><path d="M10 10h1.5M10 14h1.5M14 10h1.5M14 14h1.5"/>',
        'dossier'       => '<path d="M3 7.5A2 2 0 0 1 5 5.5h3.6l2 2.2H19a2 2 0 0 1 2 2v7.8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'document'      => '<path d="M14 3H7.5A2 2 0 0 0 5.5 5v14a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'valider'       => '<path d="M20 6.5L9.3 17.2 4 11.9"/>',
        'croix'         => '<path d="M18 6L6 18M6 6l12 12"/>',
        'envoyer'       => '<path d="M21.5 2.5L2.8 10.2l7.4 3.1 3.1 7.4z"/><path d="M21.5 2.5L10.2 13.3"/>',
        'lieu'          => '<path d="M12 21.5s7.2-6.4 7.2-11.3a7.2 7.2 0 1 0-14.4 0C4.8 15.1 12 21.5 12 21.5z"/><circle cx="12" cy="10" r="2.6"/>',
        'mail'          => '<rect x="3" y="5" width="18" height="14" rx="2.2"/><path d="M3.6 6.6L12 13l8.4-6.4"/>',
        'telephone'     => '<path d="M5.2 3h3.6l1.9 4.7-2.4 1.5a12.4 12.4 0 0 0 6.5 6.5l1.5-2.4 4.7 1.9v3.6a2 2 0 0 1-2.2 2A17.4 17.4 0 0 1 3.2 5.2 2 2 0 0 1 5.2 3z"/>',
        'etoile'        => '<path d="M12 3.4l2.7 5.7 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
        'menu'          => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'sortie'        => '<path d="M15 4h3.5A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5H15"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
        'reglages'      => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v2.4M12 18.8v2.4M4.5 7.5l2 1.2M17.5 15.3l2 1.2M4.5 16.5l2-1.2M17.5 8.7l2-1.2"/>',
    ];

    if (!isset($traces[$nom])) {
        return '';
    }

    return '<svg class="ico" width="' . $taille . '" height="' . $taille . '" viewBox="0 0 24 24" '
         . 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" '
         . 'stroke-linejoin="round" aria-hidden="true">' . $traces[$nom] . '</svg>';
}

function ton_secteur(?string $secteur): string
{
    $connus = [
        'agriculture'    => 'olive',
        'environnement'  => 'sarcelle',
        'gestion'        => 'azur',
        'administration' => 'prune',
        'transport'      => 'brique',
        'santé'          => 'grenat',
        'social'         => 'prune',
        'éducation'      => 'azur',
        'numérique'      => 'sarcelle',
    ];

    $cle = mb_strtolower(trim((string)$secteur));
    if (isset($connus[$cle])) {
        return $connus[$cle];
    }

    $palette = ['olive', 'sarcelle', 'azur', 'prune', 'brique', 'grenat'];

    return $palette[abs(crc32($cle)) % count($palette)];
}

function initiales(string $prenom, string $nom): string
{
    return mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
}

function libelle_statut(string $code): string
{
    $libelles = [
        'en_attente'  => 'En attente',
        'valide'      => 'Publié',
        'rejete'      => 'Rejeté',
        'retenue'     => 'Retenue',
        'non_retenue' => 'Non retenue',
    ];

    return $libelles[$code] ?? $code;
}

function classe_statut(string $code): string
{
    $classes = [
        'en_attente'  => 'etat etat-attente',
        'valide'      => 'etat etat-ok',
        'retenue'     => 'etat etat-ok',
        'rejete'      => 'etat etat-non',
        'non_retenue' => 'etat etat-non',
    ];

    return $classes[$code] ?? 'etat';
}

function televerser_pdf(string $champ): ?string
{
    if (empty($_FILES[$champ]['name']) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$champ]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le fichier n\'a pas pu être envoyé.');
    }
    if ($_FILES[$champ]['size'] > TAILLE_MAX_PDF) {
        throw new RuntimeException('Fichier trop lourd : 5 Mo au maximum.');
    }
    if (strtolower(pathinfo($_FILES[$champ]['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        throw new RuntimeException('Seuls les fichiers PDF sont acceptés.');
    }

    $dossier = DOSSIER_UPLOAD . 'projets/';
    if (!is_dir($dossier)) {
        mkdir($dossier, 0777, true);
    }

    $origine = pathinfo($_FILES[$champ]['name'], PATHINFO_FILENAME);
    $nom = date('Ymd_His') . '_' . substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', $origine), 0, 40) . '.pdf';

    if (!move_uploaded_file($_FILES[$champ]['tmp_name'], $dossier . $nom)) {
        throw new RuntimeException('Enregistrement du fichier impossible sur le serveur.');
    }

    return $nom;
}

function televerser_image(string $champ, string $sousDossier, string $prefixe): ?string
{
    if (empty($_FILES[$champ]['name']) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$champ]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('L\'image n\'a pas pu être envoyée.');
    }
    if ($_FILES[$champ]['size'] > TAILLE_MAX_IMAGE) {
        throw new RuntimeException('Image trop lourde : 2 Mo au maximum.');
    }

    $extension = strtolower(pathinfo($_FILES[$champ]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Formats acceptés : JPG, PNG ou WebP.');
    }

    $dossier = DOSSIER_UPLOAD . $sousDossier . '/';
    if (!is_dir($dossier)) {
        mkdir($dossier, 0777, true);
    }

    $nom = $prefixe . '_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $extension;

    if (!move_uploaded_file($_FILES[$champ]['tmp_name'], $dossier . $nom)) {
        throw new RuntimeException('Enregistrement de l\'image impossible sur le serveur.');
    }

    return $nom;
}
