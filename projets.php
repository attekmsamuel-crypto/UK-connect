<?php
/**
 * UK-Connect — Vitrine publique des projets étudiants (portfolio)
 * Recherche par mot-clé : index FULLTEXT de la base v2 (correctif n°5).
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$titre = 'Projets étudiants';
$pdo = db();

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    // Recherche plein-texte sur le titre et le résumé
    $st = $pdo->prepare(
        'SELECT p.*, CONCAT(u.prenom, " ", u.nom) AS auteur, f.sigle AS faculte_sigle
         FROM projets_etudiants p
         JOIN utilisateurs u  ON u.id = p.id_etudiant AND u.actif = 1
         LEFT JOIN facultes f ON f.id = u.id_faculte
         WHERE MATCH(p.titre_projet, p.resume_executif) AGAINST(? IN NATURAL LANGUAGE MODE)
         ORDER BY p.date_publication DESC
         LIMIT 30'
    );
    $st->execute([$q]);
} else {
    $st = $pdo->query(
        'SELECT p.*, CONCAT(u.prenom, " ", u.nom) AS auteur, f.sigle AS faculte_sigle
         FROM projets_etudiants p
         JOIN utilisateurs u  ON u.id = p.id_etudiant AND u.actif = 1
         LEFT JOIN facultes f ON f.id = u.id_faculte
         ORDER BY p.date_publication DESC
         LIMIT 30'
    );
}
$projets = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page-titre">
  <h1><i class="fa-solid fa-folder-open"></i> Projets &amp; mémoires des étudiants</h1>
  <p>La vitrine académique de l'Université de Kara : des travaux vérifiables, publiés par leurs auteurs.</p>
</div>

<form method="get" class="champ" style="max-width:520px;">
  <input type="search" name="q" placeholder="Rechercher un projet, une technologie, un domaine…"
         value="<?= e($q) ?>">
</form>

<?php if (!$projets): ?>
  <p class="muted">
    <?= $q !== '' ? 'Aucun projet ne correspond à votre recherche.' : 'Aucun projet publié pour le moment.' ?>
  </p>
<?php else: ?>
<div class="grille-cartes">
  <?php foreach ($projets as $p): ?>
    <div class="carte">
      <div class="etiquettes">
        <?php if ($p['departement']): ?><span class="etiquette"><?= e($p['departement']) ?></span><?php endif; ?>
        <?php if ($p['faculte_sigle']): ?><span class="etiquette etiquette-or"><?= e($p['faculte_sigle']) ?></span><?php endif; ?>
      </div>
      <h3><a href="<?= url('projet-voir.php?id=' . (int)$p['id']) ?>"><?= e($p['titre_projet']) ?></a></h3>
      <p><?= e(extrait($p['resume_executif'])) ?></p>
      <span class="auteur">Par <a href="<?= url('etudiant.php?id=' . (int)$p['id_etudiant']) ?>"><b><?= e($p['auteur']) ?></b></a> · <?= date_fr($p['date_publication']) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
