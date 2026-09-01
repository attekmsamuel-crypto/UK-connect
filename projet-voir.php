<?php
/**
 * UK-Connect — Détail d'un projet étudiant
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare(
    'SELECT p.*, CONCAT(u.prenom, " ", u.nom) AS auteur, u.id AS auteur_id,
            u.bio AS auteur_bio, u.telephone AS auteur_tel, f.sigle AS faculte_sigle
     FROM projets_etudiants p
     JOIN utilisateurs u  ON u.id = p.id_etudiant
     LEFT JOIN facultes f ON f.id = u.id_faculte
     WHERE p.id = ?'
);
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    flash('erreur', 'Projet introuvable.');
    redirect(url('projets.php'));
}
$titre = $p['titre_projet'];
require __DIR__ . '/includes/header.php';
?>

<div class="page-titre">
  <div class="etiquettes" style="margin-bottom:8px;">
    <?php if ($p['departement']): ?><span class="etiquette"><?= e($p['departement']) ?></span><?php endif; ?>
    <?php if ($p['faculte_sigle']): ?><span class="etiquette etiquette-or"><?= e($p['faculte_sigle']) ?></span><?php endif; ?>
  </div>
  <h1><?= e($p['titre_projet']) ?></h1>
  <p>Publié par <b><?= e($p['auteur']) ?></b> le <?= date_fr($p['date_publication']) ?></p>
</div>

<div class="panneau" style="margin-bottom:18px;">
  <h2 style="font-size:17px; margin-bottom:10px; color:var(--bleu);">Résumé exécutif</h2>
  <p style="white-space: pre-line;"><?= e($p['resume_executif']) ?></p>

  <?php if ($p['technologies_outils']): ?>
    <h2 style="font-size:17px; margin:18px 0 8px; color:var(--bleu);">Technologies & outils</h2>
    <p><?= e($p['technologies_outils']) ?></p>
  <?php endif; ?>

  <?php if ($p['fichier_resume_pdf']): ?>
    <p style="margin-top:18px;">
      <a class="btn btn-outline btn-petit" target="_blank"
         href="<?= url('assets/uploads/' . rawurlencode($p['fichier_resume_pdf'])) ?>">
        <i class="fa-solid fa-file-pdf"></i> Télécharger le résumé (PDF)
      </a>
    </p>
  <?php endif; ?>
</div>

<div class="panneau">
  <h2 style="font-size:17px; margin-bottom:8px; color:var(--bleu);">À propos de l'auteur</h2>
  <p><a href="<?= url('etudiant.php?id=' . (int)$p['auteur_id']) ?>"><b><?= e($p['auteur']) ?></b></a>
     <?php if ($p['faculte_sigle']): ?> — <?= e($p['faculte_sigle']) ?><?php endif; ?></p>
  <a class="btn btn-bleu btn-petit" style="margin-top:10px;"
     href="<?= url('etudiant.php?id=' . (int)$p['auteur_id']) ?>">
     <i class="fa-solid fa-folder-open"></i> Voir le portfolio de l'auteur</a>
  <?php if ($p['auteur_bio']): ?><p class="muted" style="margin-top:6px;"><?= e($p['auteur_bio']) ?></p><?php endif; ?>
  <?php
    // Le téléphone n'est visible que des partenaires connectés (cf. fiche v1 :
    // « contact téléphonique visible aux partenaires »)
    if (est_partenaire() && $p['auteur_tel']): ?>
    <p style="margin-top:8px;"><b><i class="fa-solid fa-phone"></i> Contact :</b> <?= e($p['auteur_tel']) ?></p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
