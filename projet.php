<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$pdo = connexion_bdd();
$moi = utilisateur_actuel();
$id = (int)($_GET['id'] ?? 0);
$fiche = projet($pdo, $id);

if (!$fiche) {
    http_response_code(404);
    $titrePage = 'Projet introuvable';
    require __DIR__ . '/includes/entete.php';
    echo '<div class="vide">Ce projet n\'existe pas. <a href="' . lien('portfolio.php') . '">Revenir au portfolio</a>.</div>';
    require __DIR__ . '/includes/pied.php';
    exit;
}

$titrePage = $fiche['titre'];
require __DIR__ . '/includes/entete.php';
?>

<a class="retour" href="<?= lien('portfolio.php') ?>"><?= fleche('gauche') ?> Retour au portfolio</a>

<div class="titre-page ton-<?= e(ton_secteur($fiche['departement'])) ?>">
  <div class="etiquettes">
    <?php if ($fiche['departement']): ?><span class="etiquette"><?= e($fiche['departement']) ?></span><?php endif; ?>
    <?php if ($fiche['sigle']): ?><span class="etiquette etiquette-faculte"><?= e($fiche['sigle']) ?></span><?php endif; ?>
  </div>
  <h1><?= e($fiche['titre']) ?></h1>
  <p>
    Publié le <?= date_courte($fiche['date_publication']) ?> par
    <a href="<?= lien('etudiant.php?id=' . (int)$fiche['id_etudiant']) ?>"><?= e($fiche['prenom'] . ' ' . $fiche['nom']) ?></a>
  </p>
</div>

<div class="detail">
  <div class="panneau">
    <h2>Résumé</h2>
    <div class="texte-long"><?= e($fiche['resume']) ?></div>

    <?php if ($fiche['fichier_pdf']): ?>
      <p style="margin-top:20px;">
        <a class="bouton bouton-secondaire" target="_blank" rel="noopener"
           href="<?= lien('assets/uploads/projets/' . rawurlencode($fiche['fichier_pdf'])) ?>">Ouvrir le document PDF</a>
      </p>
    <?php endif; ?>
  </div>

  <aside class="encadre">
    <h4>L'auteur</h4>
    <dl>
      <dt>Nom</dt>
      <dd><a href="<?= lien('etudiant.php?id=' . (int)$fiche['id_etudiant']) ?>"><?= e($fiche['prenom'] . ' ' . $fiche['nom']) ?></a></dd>
      <?php if ($fiche['niveau_etudes']): ?>
        <dt>Niveau</dt>
        <dd><?= e($fiche['niveau_etudes']) ?></dd>
      <?php endif; ?>
      <?php if ($fiche['nom_faculte']): ?>
        <dt>Faculté</dt>
        <dd><?= e($fiche['nom_faculte']) ?></dd>
      <?php endif; ?>
      <?php if ($fiche['technologies']): ?>
        <dt>Outils et méthodes</dt>
        <dd><?= e($fiche['technologies']) ?></dd>
      <?php endif; ?>
      <?php if ($moi && in_array($moi['role'], ['partenaire', 'admin'], true)): ?>
        <dt>Contact</dt>
        <dd><a href="mailto:<?= e($fiche['email']) ?>"><?= e($fiche['email']) ?></a></dd>
        <?php if ($fiche['telephone']): ?><dd><?= e($fiche['telephone']) ?></dd><?php endif; ?>
      <?php endif; ?>
    </dl>
  </aside>
</div>

<?php require __DIR__ . '/includes/pied.php'; ?>
