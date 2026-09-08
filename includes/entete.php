<?php

require_once __DIR__ . '/auth.php';

demarrer_session();

$moi = utilisateur_actuel();
$titrePage = $titrePage ?? NOM_SITE;

$pageActuelle = trim(substr($_SERVER['SCRIPT_NAME'], strlen(BASE_URL)), '/');

$nbNotifications = 0;
if ($moi) {
    $nbNotifications = compter_notifications_non_lues(connexion_bdd(), (int)$moi['id']);
}

function onglet(string $page): string
{
    global $pageActuelle;

    return $pageActuelle === $page ? ' class="actif"' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titrePage) ?> — <?= NOM_SITE ?></title>
<meta name="description" content="Les travaux des étudiants de l'Université de Kara et les besoins des entreprises, collectivités et associations de la région.">
<link rel="icon" href="<?= lien('assets/images/favicon.png') ?>">
<link rel="stylesheet" href="<?= lien('assets/css/style.css') ?>?v=<?= filemtime(RACINE . '/assets/css/style.css') ?>">
</head>
<body>

<div class="bandeau">
  <div class="zone bandeau-contenu">
    <span>Université de Kara — Appel à projets web 2026</span>
    <span class="bandeau-droite">
      <a href="mailto:contact@ukconnect.tg"><?= icone('mail', 15) ?> contact@ukconnect.tg</a>
      <a href="tel:+22893851180"><?= icone('telephone', 15) ?> +228 93 85 11 80</a>
    </span>
  </div>
</div>

<header class="entete">
  <div class="zone entete-contenu">
    <a class="marque" href="<?= lien('index.php') ?>">
      <img src="<?= lien('assets/images/logo-uk.png') ?>" alt="Université de Kara">
      <span>
        <strong>UK-Connect</strong>
        <small>Étudiants et partenaires</small>
      </span>
    </a>

    <button class="bouton-menu" type="button" id="bouton-menu" aria-label="Afficher le menu">
      <?= icone('menu', 17) ?> Menu
    </button>

    <nav class="navigation" id="navigation">
      <a href="<?= lien('index.php') ?>"<?= onglet('index.php') ?>>Accueil</a>
      <a href="<?= lien('besoins.php') ?>"<?= onglet('besoins.php') ?>>Besoins</a>
      <a href="<?= lien('portfolio.php') ?>"<?= onglet('portfolio.php') ?>>Portfolio</a>

      <?php if ($moi && $moi['role'] === 'partenaire'): ?>
        <a href="<?= lien('deposer-besoin.php') ?>"<?= onglet('deposer-besoin.php') ?>>Déposer</a>
        <a href="<?= lien('espace-partenaire.php') ?>"<?= onglet('espace-partenaire.php') ?>>Mon espace</a>
      <?php elseif ($moi && $moi['role'] === 'etudiant'): ?>
        <a href="<?= lien('publier-projet.php') ?>"<?= onglet('publier-projet.php') ?>>Publier</a>
        <a href="<?= lien('mes-candidatures.php') ?>"<?= onglet('mes-candidatures.php') ?>>Mes candidatures</a>
      <?php elseif ($moi && $moi['role'] === 'admin'): ?>
        <a href="<?= lien('admin/index.php') ?>"<?= onglet('admin/index.php') ?>>Modération</a>
        <a href="<?= lien('admin/utilisateurs.php') ?>"<?= onglet('admin/utilisateurs.php') ?>>Comptes</a>
      <?php endif; ?>

      <?php if ($moi): ?>
        <a class="lien-notifications<?= $pageActuelle === 'notifications.php' ? ' actif' : '' ?>"
           href="<?= lien('notifications.php') ?>" title="Mes notifications" aria-label="Mes notifications">
          <?= icone('cloche') ?>
          <?php if ($nbNotifications > 0): ?><span class="pastille"><?= $nbNotifications ?></span><?php endif; ?>
        </a>
        <a class="lien-compte" href="<?= lien('profil.php') ?>">
          <span class="jeton"><?= e(initiales($moi['prenom'], $moi['nom'])) ?></span>
          <?= e($moi['prenom']) ?>
        </a>
        <a class="lien-sortie" href="<?= lien('deconnexion.php') ?>" title="Se déconnecter" aria-label="Se déconnecter">
          <?= icone('sortie') ?>
        </a>
      <?php else: ?>
        <a href="<?= lien('connexion.php') ?>"<?= onglet('connexion.php') ?>>Connexion</a>
        <a class="bouton bouton-petit" href="<?= lien('inscription.php') ?>">Créer un compte</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="zone contenu">
<?= afficher_message() ?>
