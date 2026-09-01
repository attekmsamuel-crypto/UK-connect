<?php
/**
 * UK-Connect — Profil public d'un étudiant (mini-portfolio)
 * C'est la page « connexion professionnelle » : en cliquant sur le nom
 * d'un candidat, le partenaire / recruteur voit sa bannière, sa photo,
 * sa faculté, sa bio et tous ses projets publiés — pour décider en connaissance de cause.
 *
 * Public : tout le monde voit le profil et le portfolio.
 * Confidentialité : email / téléphone réservés aux partenaires et admins connectés.
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare(
    'SELECT u.*, r.code AS role_code, f.sigle AS faculte_sigle, f.nom_faculte
     FROM utilisateurs u
     JOIN roles r        ON r.id  = u.role_id
     LEFT JOIN facultes f ON f.id = u.id_faculte
     WHERE u.id = ? AND u.actif = 1 AND r.code = \'etudiant\'');
$st->execute([$id]);
$etu = $st->fetch();
if (!$etu) {
    flash('erreur', 'Profil étudiant introuvable.');
    redirect(url('projets.php'));
}

// Portfolio : tous les projets publiés par cet étudiant
$st = $pdo->prepare(
    'SELECT * FROM projets_etudiants WHERE id_etudiant = ? ORDER BY date_publication DESC'
);
$st->execute([$id]);
$projets = $st->fetchAll();

// CV (v2.2) : formations, expériences et outils maîtrisés.
// Mode dégradé : si la base n'a pas été mise à jour (v2.2), le profil
// public s'affiche quand même, simplement sans les sections du CV.
$formations = $experiences = $competences = [];
$cvDispo = true;
try {
    $st = $pdo->prepare('SELECT * FROM profil_formations WHERE id_etudiant = ? ORDER BY annee_debut DESC, id DESC');
    $st->execute([$id]);
    $formations = $st->fetchAll();
    $st = $pdo->prepare('SELECT * FROM profil_experiences WHERE id_etudiant = ? ORDER BY id DESC');
    $st->execute([$id]);
    $experiences = $st->fetchAll();
    $st = $pdo->prepare('SELECT * FROM profil_competences WHERE id_etudiant = ? ORDER BY categorie, nom');
    $st->execute([$id]);
    $competences = $st->fetchAll();
} catch (PDOException $e) {
    $cvDispo = false; // tables profil_* absentes → ré-importer database/ukconnect_v2.sql
}

// C'est mon propre profil ?
$estMoi = est_connecte() && (int)current_user()['id'] === (int)$etu['id'];
// Coordonnées visibles : partenaires, admins, et le propriétaire du profil
$contactVisible = est_partenaire() || est_admin() || $estMoi;

$titre = $etu['prenom'] . ' ' . $etu['nom'];
require __DIR__ . '/includes/header.php';

$initiales = mb_strtoupper(mb_substr($etu['prenom'], 0, 1) . mb_substr($etu['nom'], 0, 1));

$iconesCompetences = [
    'Langage' => 'fa-code', 'Outil' => 'fa-screwdriver-wrench', 'Langue' => 'fa-language',
    'Compétence métier' => 'fa-briefcase', 'Compétence transversale' => 'fa-people-group',
];
?>

<div class="banniere-profil <?= $etu['banniere_profil'] ? 'avec-image' : '' ?>"
     <?= $etu['banniere_profil']
         ? 'style="background-image:url(' . url('assets/uploads/bannieres/' . rawurlencode($etu['banniere_profil'])) . ')"'
         : '' ?>></div>

<div class="profil-tete">
  <?php if ($etu['photo_profil']): ?>
    <img class="avatar-rond" src="<?= url('assets/uploads/avatars/' . rawurlencode($etu['photo_profil'])) ?>"
         alt="Photo de <?= e($titre) ?>">
  <?php else: ?>
    <div class="avatar-rond"><?= e($initiales) ?></div>
  <?php endif; ?>

  <h1><?= e($titre) ?></h1>
  <?php if ($estMoi): ?>
    <p class="muted" style="margin-top:4px;">
      <i class="fa-solid fa-circle-info" style="color:var(--vert);"></i>
      Voici votre CV public — c'est exactement cette page que voient les recruteurs.
      <a href="<?= url('profil.php') ?>"><b>Le compléter</b></a>
    </p>
  <?php endif; ?>
  <div class="profil-badges">
    <span class="etiquette"><i class="fa-solid fa-graduation-cap"></i> Étudiant·e</span>
    <?php if ($etu['niveau_etudes']): ?>
      <span class="etiquette etiquette-vert"><i class="fa-solid fa-layer-group"></i> <?= e($etu['niveau_etudes']) ?></span>
    <?php endif; ?>
    <?php if ($etu['faculte_sigle']): ?>
      <span class="etiquette etiquette-or"><?= e($etu['faculte_sigle']) ?> — <?= e($etu['faculte_nom']) ?></span>
    <?php endif; ?>
    <span class="etiquette etiquette-vert"><?= count($projets) ?> projet<?= count($projets) > 1 ? 's' : '' ?> publié<?= count($projets) > 1 ? 's' : '' ?></span>
  </div>
</div>

<div style="max-width: 980px; margin: 26px auto;">
  <div class="cv-grille">

    <!-- ======= Colonne latérale : contact + outils ======= -->
    <aside style="display:flex; flex-direction:column; gap:16px;">
      <div class="panneau">
        <h2 class="cv-titre-panneau"><i class="fa-solid fa-address-card"></i> Contact</h2>
        <?php if ($contactVisible): ?>
          <p style="font-size:14px;"><i class="fa-solid fa-envelope" style="color:var(--vert);"></i>
             <a href="mailto:<?= e($etu['email']) ?>"><b><?= e($etu['email']) ?></b></a></p>
          <?php if ($etu['telephone']): ?>
            <p style="margin-top:6px; font-size:14px;"><i class="fa-solid fa-phone" style="color:var(--vert);"></i>
               <b><?= e($etu['telephone']) ?></b></p>
          <?php endif; ?>
          <?php if ($estMoi): ?>
            <p class="muted" style="margin-top:10px; font-size:12.8px;">
              Vos coordonnées sont visibles des partenaires connectés — c'est ce qui permet la mise en relation.</p>
          <?php else: ?>
            <p class="muted" style="margin-top:10px; font-size:12.8px;">
              Vous êtes partenaire : contactez <?= e($etu['prenom']) ?> directement pour discuter de votre sujet.</p>
          <?php endif; ?>
        <?php else: ?>
          <p class="muted" style="font-size:13.5px;">
            <i class="fa-solid fa-lock"></i>
            Les coordonnées de <?= e($etu['prenom']) ?> sont réservées aux partenaires connectés
            (mise en relation professionnelle).
            <?php if (!est_connecte()): ?>
              <a href="<?= url('connexion.php') ?>"><b>Se connecter en tant que partenaire</b></a>.
            <?php endif; ?></p>
        <?php endif; ?>
      </div>

      <div class="panneau">
        <h2 class="cv-titre-panneau"><i class="fa-solid fa-screwdriver-wrench"></i> Outils &amp; compétences</h2>
        <?php if (!$competences): ?>
          <p class="muted" style="font-size:13.5px;">
            Aucun outil listé.
            <?php if ($estMoi): ?><a href="<?= url('profil.php') ?>"><b>Les ajouter</b></a><?php endif; ?></p>
        <?php else: ?>
          <div class="liste-competences">
            <?php foreach ($competences as $c): ?>
              <span class="puce"><i class="fa-solid <?= $iconesCompetences[$c['categorie']] ?? 'fa-check' ?>"></i> <?= e($c['nom']) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>

    <!-- ======= Colonne principale : présentation, expériences, formations ======= -->
    <div style="display:flex; flex-direction:column; gap:16px;">

      <div class="panneau">
        <h2 class="cv-titre-panneau"><i class="fa-solid fa-user"></i> Présentation</h2>
        <?php if ($etu['bio']): ?>
          <p style="white-space:pre-line;"><?= e($etu['bio']) ?></p>
        <?php else: ?>
          <p class="muted">Pas encore de présentation.
            <?php if ($estMoi): ?><a href="<?= url('profil.php') ?>"><b>En rédiger une</b></a><?php endif; ?></p>
        <?php endif; ?>
      </div>

      <div class="panneau">
        <h2 class="cv-titre-panneau"><i class="fa-solid fa-briefcase"></i> Expériences &amp; stages</h2>
        <?php if (!$experiences): ?>
          <p class="muted">Aucune expérience renseignée.
            <?php if ($estMoi): ?><a href="<?= url('profil.php') ?>"><b>L'enrichir</b></a><?php endif; ?></p>
        <?php else: ?>
          <div class="cv-tl">
            <?php foreach ($experiences as $x): ?>
              <div class="cv-tl-item">
                <b><?= e($x['poste']) ?></b>
                <?php if ($x['en_cours']): ?><span class="etiquette etiquette-vert">Aujourd'hui</span><?php endif; ?>
                <div class="muted" style="font-size:13px;"><?= e($x['organisation']) ?><?= $x['lieu'] ? ' · ' . e($x['lieu']) : '' ?></div>
                <?php if ($x['periode']): ?><span class="periode"><?= e($x['periode']) ?></span><?php endif; ?>
                <?php if ($x['description']): ?><p style="font-size:13.5px; margin-top:5px;"><?= e($x['description']) ?></p><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="panneau">
        <h2 class="cv-titre-panneau"><i class="fa-solid fa-scroll"></i> Formations</h2>
        <?php if (!$formations): ?>
          <p class="muted">Aucune formation renseignée.
            <?php if ($estMoi): ?><a href="<?= url('profil.php') ?>"><b>L'ajouter</b></a><?php endif; ?></p>
        <?php else: ?>
          <div class="cv-tl">
            <?php foreach ($formations as $f): ?>
              <div class="cv-tl-item">
                <b><?= e($f['diplome']) ?></b>
                <?php if ($f['mention']): ?> <span class="etiquette etiquette-or"><?= e($f['mention']) ?></span><?php endif; ?>
                <div class="muted" style="font-size:13px;"><?= e($f['etablissement']) ?><?= $f['ville'] ? ' · ' . e($f['ville']) : '' ?></div>
                <?php if ($f['annee_debut'] || $f['annee_fin']): ?>
                  <span class="periode"><?= $f['annee_debut'] ? (int)$f['annee_debut'] : '?' ?> — <?= $f['annee_fin'] ? (int)$f['annee_fin'] : 'en cours' ?></span>
                <?php endif; ?>
                <?php if ($f['description']): ?><p style="font-size:13.5px; margin-top:5px;"><?= e($f['description']) ?></p><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="titre-section" style="margin-top:28px;">
    <h2><i class="fa-solid fa-folder-open"></i> Portfolio — travaux publiés</h2>
  </div>
  <?php if (!$projets): ?>
    <div class="panneau"><p class="muted"><?= e($etu['prenom']) ?> n'a pas encore publié de projet dans la vitrine.</p></div>
  <?php else: ?>
  <div class="grille-cartes">
    <?php foreach ($projets as $p): ?>
      <div class="carte">
        <div class="etiquettes">
          <?php if ($p['departement']): ?><span class="etiquette"><?= e($p['departement']) ?></span><?php endif; ?>
          <span class="etiquette etiquette-gris"><?= date_fr($p['date_publication']) ?></span>
        </div>
        <h3><a href="<?= url('projet-voir.php?id=' . (int)$p['id']) ?>"><?= e($p['titre_projet']) ?></a></h3>
        <p><?= e(extrait($p['resume_executif'])) ?></p>
        <?php if ($p['technologies_outils']): ?>
          <span class="etiquette etiquette-or"><i class="fa-solid fa-screwdriver-wrench"></i> <?= e($p['technologies_outils']) ?></span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
