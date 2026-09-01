<?php
/**
 * UK-Connect — En-tête commun de toutes les pages (mise en page).
 * Adapte la barre de navigation au rôle de l'utilisateur connecté.
 * Identité visuelle alignée sur le site officiel univkara.tg :
 * barre supérieure bleue avec boutons verts, en-tête blanc épuré,
 * titres serif (Playfair Display) + texte Jost, accents or.
 */
require_once __DIR__ . '/auth.php';
session_init();
$u = current_user();
$titre = $titre ?? APP_NAME;

// Badge de notifications non lues (remplies automatiquement par les triggers SQL)
$nbNotifs = 0;
if ($u) {
    $stN = db()->prepare('SELECT COUNT(*) FROM notifications WHERE id_utilisateur = ? AND lu = 0');
    $stN->execute([$u['id']]);
    $nbNotifs = (int)$stN->fetchColumn();
}

// Entrée de menu active (comme sur univkara.tg : l'entrée courante est verte)
$chemin = $_SERVER['SCRIPT_NAME'] ?? '';
$navActif = function (string $suffixe) use ($chemin): string {
    if ($suffixe === '/index.php' && str_ends_with($chemin, 'admin/index.php')) return '';
    return str_ends_with($chemin, $suffixe) ? ' nav-actif' : '';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titre) ?> — <?= APP_NAME ?></title>
<link rel="icon" type="image/png" href="<?= url('assets/images/favicon.png') ?>">
<!-- Typographies du modèle univkara.tg : Playfair Display (serif des titres,
     équivalent libre du « LeBeauneNew » du site, sous licence payante)
     + Jost (texte, police exacte du site officiel) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800;900&display=swap" rel="stylesheet">
<!-- Font Awesome 6 — icônes -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<!-- ======= Barre supérieure institutionnelle (modèle univkara.tg) ======= -->
<div class="topbar">
  <div class="conteneur topbar-interieur">
    <a class="topbar-btn" href="<?= url('inscription.php') ?>">Inscription</a>
    <div class="topbar-droite">
      <a class="topbar-lien topbar-tel" href="tel:+22893851180">
        <i class="fa-solid fa-phone"></i> +228 93 85 11 80 / 97 90 34 91</a>
      <a class="topbar-lien topbar-aide" href="<?= url('verifier-installation.php') ?>">Centre d'aide</a>
      <span class="topbar-sociaux">
        <a href="https://x.com/KaraUniversite" target="_blank" rel="noopener" aria-label="X (Twitter)" title="X (Twitter)"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="https://www.linkedin.com/school/universit%C3%A9-de-kara" target="_blank" rel="noopener" aria-label="LinkedIn" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        <a href="https://www.tiktok.com/@univkara_togo" target="_blank" rel="noopener" aria-label="TikTok" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
        <a href="https://www.youtube.com/@universitedekaratv7082" target="_blank" rel="noopener" aria-label="YouTube" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
      </span>
      <a class="topbar-btn" href="mailto:admin@ukconnect.tg">Contactez-nous</a>
    </div>
  </div>
</div>

<header class="barre">
  <div class="conteneur barre-interieur">
    <a class="logo" href="<?= url('index.php') ?>">
      <img src="<?= url('assets/images/logo-uk.png') ?>" alt="Université de Kara" class="logo-img">
      <span class="logo-texte">UK<strong>-Connect</strong>
        <small>Portail d'innovation académique</small>
      </span>
    </a>

    <button class="burger" id="burger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>

    <nav class="nav" id="nav">
      <a href="<?= url('index.php') ?>" class="<?= $navActif('/index.php') ?>">Accueil</a>
      <a href="<?= url('projets.php') ?>" class="<?= $navActif('/projets.php') ?>">Portfolio</a>
      <a href="<?= url('sujets.php') ?>" class="<?= $navActif('/sujets.php') ?>">Besoins</a>

      <?php if (est_admin()): ?>
        <a href="<?= url('admin/index.php') ?>" class="nav-admin<?= $navActif('admin/index.php') ?>"><i class="fa-solid fa-user-shield"></i> Administration</a>
      <?php elseif (est_partenaire()): ?>
        <a href="<?= url('sujet-deposer.php') ?>" class="<?= $navActif('/sujet-deposer.php') ?>"><i class="fa-solid fa-plus"></i> Déposer un besoin</a>
        <a href="<?= url('partenaire-espace.php') ?>" class="<?= $navActif('/partenaire-espace.php') ?>"><i class="fa-solid fa-briefcase"></i> Mon espace</a>
      <?php elseif (est_etudiant()): ?>
        <a href="<?= url('projet-publier.php') ?>" class="<?= $navActif('/projet-publier.php') ?>"><i class="fa-solid fa-upload"></i> Publier un projet</a>
        <a href="<?= url('mes-candidatures.php') ?>" class="<?= $navActif('/mes-candidatures.php') ?>"><i class="fa-solid fa-paper-plane"></i> Mes candidatures</a>
      <?php endif; ?>

      <?php if ($u): ?>
        <a href="<?= url('notifications.php') ?>" title="Mes notifications" class="nav-cloche<?= $navActif('/notifications.php') ?>">
          <i class="fa-solid fa-bell"></i><span class="nav-libelle"> Notifications</span>
          <?php if ($nbNotifs > 0): ?><span class="compteur-notif"><?= $nbNotifs ?></span><?php endif; ?>
        </a>

        <?php
        // Étudiant : le nom ouvre son PROFIL PUBLIC (portfolio avec bannière).
        // Partenaire / admin : le nom ouvre la page d'édition de son profil.
        $lienProfilNom = $u['role_code'] === 'etudiant'
            ? url('etudiant.php?id=' . (int)$u['id'])
            : url('profil.php');
        ?>
        <a class="nav-profil" href="<?= $lienProfilNom ?>"
           title="<?= $u['role_code'] === 'etudiant' ? 'Voir mon profil public (portfolio)' : 'Mon profil' ?>">
          <?php if (!empty($u['photo_profil'])): ?>
            <img class="nav-avatar" src="<?= url('assets/uploads/avatars/' . rawurlencode($u['photo_profil'])) ?>"
                 alt="Ma photo de profil">
          <?php else: ?>
            <span class="nav-avatar nav-avatar-initiales">
              <?= e(mb_strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1))) ?>
            </span>
          <?php endif; ?>
          <span class="nav-libelle"><?= e($u['prenom']) ?></span>
        </a>

        <?php if ($u['role_code'] === 'etudiant'): ?>
          <!-- le nom pointe vers le profil public ; cette entrée ouvre l'éditeur de CV -->
          <a href="<?= url('profil.php') ?>" class="<?= $navActif('/profil.php') ?>"><i class="fa-solid fa-user-gear"></i> Mon profil</a>
        <?php endif; ?>
        <a class="nav-ico" href="<?= url('deconnexion.php') ?>"
           title="Se déconnecter"><i class="fa-solid fa-right-from-bracket"></i></a>
      <?php else: ?>
        <a class="btn btn-outline btn-petit" href="<?= url('connexion.php') ?>">
          <i class="fa-solid fa-right-to-bracket"></i> Connexion</a>
        <a class="btn btn-vert btn-petit" href="<?= url('inscription.php') ?>">
          <i class="fa-solid fa-user-plus"></i> S'inscrire</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="conteneur principal">
<?= flash_afficher() ?>
