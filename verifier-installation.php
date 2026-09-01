<?php
/**
 * UK-Connect — Vérificateur d'installation (à exécuter UNE fois, puis à supprimer)
 * URL : http://localhost/uk-connect/verifier-installation.php
 * Contrôle : extension PDO, connexion, tables, vues, triggers, données de référence.
 */
require_once __DIR__ . '/config/database.php';

echo '<html><head><meta charset="UTF-8"><title>Vérification UK-Connect</title></head>
<body style="font-family:Segoe UI,Arial,sans-serif;background:#f1f5f9;padding:30px;">
<div style="max-width:720px;margin:auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
<h1 style="color:#1e40af;">🔧 Vérification de l\'installation UK-Connect</h1>';

$ok = true;
function ligne($ok, $texte, $detail = '') {
    echo '<p style="font-size:15px;margin:8px 0;">'
       . ($ok ? '<span style="color:#047857;font-weight:bold;">✓</span> '
             : '<span style="color:#be123c;font-weight:bold;">✗</span> ')
       . htmlspecialchars($texte)
       . ($detail !== '' ? ' — <span style="color:#64748b;">' . htmlspecialchars($detail) . '</span>' : '')
       . '</p>';
}

// 1. Extension PDO
ligne(extension_loaded('pdo_mysql'), 'Extension PHP pdo_mysql',
      extension_loaded('pdo_mysql') ? 'présente' : 'activez-la dans XAMPP (config PHP)');

// 2. Connexion au serveur MySQL
try {
    new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS);
    ligne(true, "Connexion au serveur MySQL ($DB_HOST)", 'identifiants corrects');
} catch (Exception $e) {
    ligne(false, "Connexion au serveur MySQL ($DB_HOST)", $e->getMessage());
    $ok = false;
}

// 3. Base et structure
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ligne(true, "Base « $DB_NAME » accessible");
} catch (Exception $e) {
    ligne(false, "Base « $DB_NAME » introuvable",
          'importez database/ukconnect_v2.sql via phpMyAdmin (onglet Importer)');
    echo '</div></body></html>';
    exit;
}

$attendu = ['utilisateurs','facultes','besoins_sujets','projets_etudiants','candidatures',
            'roles','statuts_validation','statuts_candidature'];
$manquantes = [];
foreach ($attendu as $t) {
    $n = $pdo->query("SELECT COUNT(*) FROM information_schema.tables
                      WHERE table_schema='" . DB_NAME . "' AND table_name='$t'")->fetchColumn();
    if ($n) ligne(true, "Table $t"); else { ligne(false, "Table $t absente"); $manquantes[] = $t; $ok = false; }
}

$vues = ['v_sujets_publics','v_candidatures_detail','v_projets_portfolio'];
foreach ($vues as $v) {
    try { $pdo->query("SELECT 1 FROM $v LIMIT 1"); ligne(true, "Vue $v"); }
    catch (Exception $e) { ligne(false, "Vue $v absente", 'import SQL incomplet ?'); $ok = false; }
}

$trg = $pdo->query("SELECT COUNT(*) FROM information_schema.triggers
                    WHERE trigger_schema='" . DB_NAME . "'")->fetchColumn();
ligne($trg >= 6, "Triggers de sécurité", "$trg / 6 attendus");

// 4. Colonne clé de la v2 (celle qui cause l'erreur du message d'origine)
$col = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema='" . DB_NAME . "'
                    AND table_name='besoins_sujets' AND column_name='statut_id'")->fetchColumn();
ligne($col, "Colonne besoins_sujets.statut_id (structure v2)",
      $col ? 'présente' : 'ABSENTE → vous êtes sur l\'ancienne structure v1');

// 5. Données de référence + démo
foreach (['facultes' => 9, 'roles' => 3, 'utilisateurs' => null] as $t => $min) {
    $n = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    ligne($min === null || $n >= $min, "Contenu de « $t »", "$n ligne(s)" . ($min !== null ? " (min. $min)" : ''));
}

echo $ok
   ? '<p style="background:#d1fae5;color:#065f46;padding:14px;border-radius:9px;font-weight:bold;">
        <i class="fa-solid fa-circle-check"></i> Tout est prêt ! Ouvrez http://localhost/uk-connect/<br>
        <small style="font-weight:normal;">Comptes de démo (mot de passe : UkConnect@2026) :
        admin@ukconnect.tg · contact@agrotogo.tg · yao.salami@etu-univkara.tg</small></p>'
   : '<p style="background:#ffe4e6;color:#9f1239;padding:14px;border-radius:9px;font-weight:bold;">
        <i class="fa-solid fa-triangle-exclamation"></i> Réglez les points ✗ ci-dessus, puis rechargez cette page.<br>
        <small style="font-weight:normal;">La plupart du temps : importer
        database/ukconnect_v2.sql dans phpMyAdmin (onglet « Importer »).</small></p>';

echo '<p style="color:#64748b;font-size:13px;margin-top:18px;">🔐 Sécurité :
      supprimez ce fichier (verifier-installation.php) après l\'installation.</p>';
echo '</div></body></html>';
