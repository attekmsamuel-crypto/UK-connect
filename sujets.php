<?php
/**
 * UK-Connect — Banque de sujets d'étude (public)
 * Ne montrent QUE les sujets validés : la règle de modération est
 * encapsulée dans la vue v_sujets_publics (base v2).
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$titre = 'Sujets d\'étude';
$pdo = db();

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $st = $pdo->prepare(
        'SELECT * FROM v_sujets_publics
         WHERE secteur_filiere LIKE ? OR titre_sujet LIKE ? OR faculte_sigle LIKE ?
         ORDER BY date_creation DESC LIMIT 30'
    );
    $st->execute(["%$q%", "%$q%", "%$q%"]);
} else {
    $st = $pdo->query('SELECT * FROM v_sujets_publics ORDER BY date_creation DESC LIMIT 30');
}
$sujets = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page-titre">
  <h1><i class="fa-solid fa-lightbulb"></i> Sujets d'étude proposés par les partenaires</h1>
  <p>Des problématiques réelles soumises par les collectivités, entreprises et ONG — validées par l'administration.</p>
</div>

<form method="get" class="champ" style="max-width:520px;">
  <input type="search" name="q" placeholder="Rechercher par secteur, mot-clé, faculté…" value="<?= e($q) ?>">
</form>

<?php if (!$sujets): ?>
  <p class="muted"><?= $q !== '' ? 'Aucun sujet ne correspond à votre recherche.' : 'Aucun sujet validé pour le moment.' ?></p>
<?php else: ?>
<div class="grille-cartes">
  <?php foreach ($sujets as $s): ?>
    <div class="carte">
      <div class="etiquettes">
        <span class="etiquette"><?= e($s['secteur_filiere']) ?></span>
        <?php if ($s['faculte_sigle']): ?><span class="etiquette etiquette-or"><?= e($s['faculte_sigle']) ?></span><?php endif; ?>
      </div>
      <h3><a href="<?= url('sujet-voir.php?id=' . (int)$s['id']) ?>"><?= e($s['titre_sujet']) ?></a></h3>
      <p><?= e(extrait($s['description_probleme'])) ?></p>
      <span class="date">Déposé par <b><?= e($s['partenaire']) ?></b> · <?= date_fr($s['date_creation']) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
