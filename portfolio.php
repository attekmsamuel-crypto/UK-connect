<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$pdo = connexion_bdd();

$mot = trim($_GET['q'] ?? '');
$idFaculte = (int)($_GET['faculte'] ?? 0);

$projets = chercher_projets($pdo, $mot, $idFaculte);
$facultes = liste_facultes($pdo);

$titrePage = 'Portfolio';
require __DIR__ . '/includes/entete.php';
?>

<div class="titre-page">
  <span class="surtitre">Travaux publiés</span>
  <h1>Portfolio des étudiants</h1>
  <p>Mémoires, projets de fin d'études et travaux personnels publiés par les étudiants de l'université.</p>
</div>

<div class="filtres">
  <form method="get">
    <div class="champ champ-recherche" style="grid-column: span 2;">
      <label for="q">Rechercher</label>
      <?= icone('recherche', 17) ?>
      <input type="text" id="q" name="q" value="<?= e($mot) ?>" placeholder="titre, résumé ou domaine d'étude">
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
  <?= count($projets) ?> projet<?= count($projets) > 1 ? 's' : '' ?>
  <?php if ($mot !== '' || $idFaculte > 0): ?>
    correspondant à votre recherche — <a href="<?= lien('portfolio.php') ?>">tout afficher</a>
  <?php endif; ?>
</p>

<?php if (!$projets): ?>
  <div class="vide">Aucun projet ne correspond à cette recherche.</div>
<?php else: ?>
  <div class="grille">
    <?php foreach ($projets as $p): ?>
      <article class="fiche ton-<?= e(ton_secteur($p['departement'])) ?>">
        <div class="etiquettes">
          <?php if ($p['departement']): ?><span class="etiquette"><?= e($p['departement']) ?></span><?php endif; ?>
          <?php if ($p['sigle']): ?><span class="etiquette etiquette-faculte"><?= e($p['sigle']) ?></span><?php endif; ?>
        </div>
        <h3><a href="<?= lien('projet.php?id=' . (int)$p['id']) ?>"><?= e($p['titre']) ?></a></h3>
        <p><?= e(resume_court($p['resume'], 200)) ?></p>
        <div class="fiche-bas">
          <span class="jeton"><?= e(initiales($p['prenom'], $p['nom'])) ?></span>
          <span>
            <a href="<?= lien('etudiant.php?id=' . (int)$p['id_etudiant']) ?>"><?= e($p['prenom'] . ' ' . $p['nom']) ?></a>
            · <?= date_courte($p['date_publication']) ?>
          </span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/pied.php'; ?>
