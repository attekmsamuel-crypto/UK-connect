<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$pdo = connexion_bdd();
$moi = utilisateur_actuel();
$id = (int)($_GET['id'] ?? 0);
$etudiant = utilisateur_par_id($pdo, $id);

if (!$etudiant || $etudiant['role'] !== 'etudiant' || (int)$etudiant['actif'] !== 1) {
    http_response_code(404);
    $titrePage = 'Profil introuvable';
    require __DIR__ . '/includes/entete.php';
    echo '<div class="vide">Ce profil n\'existe pas. <a href="' . lien('portfolio.php') . '">Revenir au portfolio</a>.</div>';
    require __DIR__ . '/includes/pied.php';
    exit;
}

$projets = projets_de_etudiant($pdo, $id);
$formations = formations_de($pdo, $id);
$experiences = experiences_de($pdo, $id);
$competences = competences_de($pdo, $id);
$cestMoi = $moi && (int)$moi['id'] === $id;

$titrePage = $etudiant['prenom'] . ' ' . $etudiant['nom'];
require __DIR__ . '/includes/entete.php';
?>

<div class="couverture">
  <?php if ($etudiant['banniere']): ?>
    <img src="<?= lien('assets/uploads/bannieres/' . rawurlencode($etudiant['banniere'])) ?>" alt="">
  <?php endif; ?>
</div>

<div class="carte-profil">
  <div class="profil-tete">
    <?php if ($etudiant['photo']): ?>
      <img class="avatar" src="<?= lien('assets/uploads/avatars/' . rawurlencode($etudiant['photo'])) ?>" alt="">
    <?php else: ?>
      <span class="avatar"><?= e(initiales($etudiant['prenom'], $etudiant['nom'])) ?></span>
    <?php endif; ?>
    <div class="profil-identite">
      <h1><?= e($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></h1>
      <p class="discret" style="margin:0;">
        <?= e($etudiant['niveau_etudes'] ?: 'Étudiant') ?>
        <?php if ($etudiant['nom_faculte']): ?> · <?= e($etudiant['nom_faculte']) ?><?php endif; ?>
      </p>
    </div>
  </div>

  <?php if ($etudiant['bio']): ?>
    <p class="profil-resume"><?= e($etudiant['bio']) ?></p>
  <?php endif; ?>

  <?php if ($cestMoi): ?>
    <div class="ligne-boutons">
      <a class="bouton bouton-secondaire bouton-petit" href="<?= lien('profil.php') ?>">Modifier mon profil</a>
      <a class="bouton bouton-secondaire bouton-petit" href="<?= lien('publier-projet.php') ?>">Publier un projet</a>
    </div>
  <?php elseif ($moi && $moi['role'] !== 'etudiant'): ?>
    <div class="ligne-boutons">
      <a class="bouton bouton-petit" href="mailto:<?= e($etudiant['email']) ?>">Contacter par email</a>
      <?php if ($etudiant['telephone']): ?><span class="petit discret"><?= e($etudiant['telephone']) ?></span><?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<div class="detail">
  <div>
    <section class="section">
      <div class="section-entete"><h2>Projets publiés</h2></div>
      <?php if (!$projets): ?>
        <div class="vide"><?= $cestMoi ? 'Vous n\'avez publié aucun projet pour le moment.' : 'Aucun projet publié pour le moment.' ?></div>
      <?php else: ?>
        <div class="grille">
          <?php foreach ($projets as $p): ?>
            <article class="fiche ton-<?= e(ton_secteur($p['departement'])) ?>">
              <?php if ($p['departement']): ?>
                <div class="etiquettes"><span class="etiquette"><?= e($p['departement']) ?></span></div>
              <?php endif; ?>
              <h3><a href="<?= lien('projet.php?id=' . (int)$p['id']) ?>"><?= e($p['titre']) ?></a></h3>
              <p><?= e(resume_court($p['resume'], 170)) ?></p>
              <div class="fiche-bas"><?= date_courte($p['date_publication']) ?></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($experiences): ?>
      <section class="section cv-bloc">
        <div class="section-entete"><h2>Expériences</h2></div>
        <?php foreach ($experiences as $x): ?>
          <div class="cv-ligne">
            <b><?= e($x['poste']) ?></b>
            <span><?= e($x['organisation']) ?><?= $x['lieu'] ? ' — ' . e($x['lieu']) : '' ?><?= $x['periode'] ? ' · ' . e($x['periode']) : '' ?></span>
            <?php if ($x['description']): ?><p><?= e($x['description']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

    <?php if ($formations): ?>
      <section class="section cv-bloc">
        <div class="section-entete"><h2>Formation</h2></div>
        <?php foreach ($formations as $f): ?>
          <div class="cv-ligne">
            <b><?= e($f['diplome']) ?></b>
            <span>
              <?= e($f['etablissement']) ?><?= $f['ville'] ? ' — ' . e($f['ville']) : '' ?>
              <?php if ($f['annee_debut']): ?>
                · <?= (int)$f['annee_debut'] ?> — <?= $f['annee_fin'] ? (int)$f['annee_fin'] : 'en cours' ?>
              <?php endif; ?>
              <?= $f['mention'] ? ' · mention ' . e($f['mention']) : '' ?>
            </span>
            <?php if ($f['description']): ?><p><?= e($f['description']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </div>

  <aside>
    <div class="encadre">
      <h4>En bref</h4>
      <dl>
        <dt>Faculté</dt>
        <dd><?= e($etudiant['nom_faculte'] ?: '—') ?></dd>
        <dt>Niveau</dt>
        <dd><?= e($etudiant['niveau_etudes'] ?: '—') ?></dd>
        <dt>Projets publiés</dt>
        <dd><?= count($projets) ?></dd>
        <dt>Inscrit depuis</dt>
        <dd><?= date_courte($etudiant['date_inscription']) ?></dd>
      </dl>
    </div>

    <?php if ($competences): ?>
      <div class="encadre" style="margin-top:18px;">
        <h4>Compétences</h4>
        <ul class="puces">
          <?php foreach ($competences as $c): ?>
            <li><?= e($c['nom']) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </aside>
</div>

<?php require __DIR__ . '/includes/pied.php'; ?>
