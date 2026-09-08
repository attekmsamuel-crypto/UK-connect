<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$pdo = connexion_bdd();

$mot = trim($_GET['q'] ?? '');
$secteur = trim($_GET['secteur'] ?? '');
$idFaculte = (int)($_GET['faculte'] ?? 0);

$besoins = chercher_besoins($pdo, $mot, $secteur, $idFaculte);
$facultes = liste_facultes($pdo);
$secteurs = secteurs_disponibles($pdo);

$titrePage = 'Besoins publiés';
require __DIR__ . '/includes/entete.php';
?>

<div class="titre-page">
  <span class="surtitre">Problématiques du territoire</span>
  <h1>Besoins publiés</h1>
  <p>Les problématiques déposées par les partenaires et validées par l'université. Chacune peut devenir un sujet de mémoire ou de projet.</p>
</div>

<div class="filtres">
  <form method="get">
    <div class="champ champ-recherche">
      <label for="q">Rechercher</label>
      <?= icone('recherche', 17) ?>
      <input type="text" id="q" name="q" value="<?= e($mot) ?>" placeholder="mot-clé dans le titre ou la description">
    </div>
    <div class="champ">
      <label for="secteur">Secteur</label>
      <select id="secteur" name="secteur">
        <option value="">Tous</option>
        <?php foreach ($secteurs as $s): ?>
          <option value="<?= e($s) ?>" <?= $secteur === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="champ">
      <label for="faculte">Faculté</label>
      <select id="faculte" name="faculte">
        <option value="0">Toutes</option>
        <?php foreach ($facultes as $faculte): ?>
          <option value="<?= (int)$faculte['id'] ?>" <?= $idFaculte === (int)$faculte['id'] ? 'selected' : '' ?>><?= e($faculte['sigle']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="champ">
      <button type="submit" class="bouton">Filtrer <?= icone('fleche', 16) ?></button>
    </div>
  </form>
</div>

<p class="resultats">
  <?= count($besoins) ?> besoin<?= count($besoins) > 1 ? 's' : '' ?>
  <?php if ($mot !== '' || $secteur !== '' || $idFaculte > 0): ?>
    correspondant à votre recherche — <a href="<?= lien('besoins.php') ?>">tout afficher</a>
  <?php endif; ?>
</p>

<?php if (!$besoins): ?>
  <div class="vide">Aucun besoin ne correspond à cette recherche.</div>
<?php else: ?>
  <div class="grille">
    <?php foreach ($besoins as $b): ?>
      <article class="fiche ton-<?= e(ton_secteur($b['secteur'])) ?>">
        <div class="etiquettes">
          <span class="etiquette"><?= e($b['secteur']) ?></span>
          <?php if ($b['sigle']): ?><span class="etiquette etiquette-faculte"><?= e($b['sigle']) ?></span><?php endif; ?>
        </div>
        <h3><a href="<?= lien('besoin.php?id=' . (int)$b['id']) ?>"><?= e($b['titre']) ?></a></h3>
        <p><?= e(resume_court($b['description'], 200)) ?></p>
        <div class="fiche-bas">
          <span class="jeton"><?= e(mb_strtoupper(mb_substr($b['nom_structure'], 0, 2))) ?></span>
          <span><?= e($b['nom_structure']) ?> · <?= date_courte($b['date_depot']) ?></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/pied.php'; ?>
