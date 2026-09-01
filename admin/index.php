<?php
/**
 * UK-Connect — Back-office administrateur : tableau de bord & modération
 * Fonctionnalité 4 du cahier des charges : validation ou rejet des sujets,
 * avec traçabilité complète (valide_par + date_validation — base v2).
 */
require_once __DIR__ . '/../includes/auth.php';
session_init();
$u = require_role('admin');
$pdo = db();
csrf_verifier();

// ---------- Validation / rejet d'un sujet ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sujet_id'], $_POST['action'])) {
    $sujetId = (int)$_POST['sujet_id'];
    $statut  = $_POST['action'] === 'valider' ? STVAL_VALIDE : STVAL_REJETE;

    // Le trigger trg_besoins_bu vérifie que valide_par est bien un ADMIN
    // et pose automatiquement date_validation.
    $pdo->prepare('UPDATE besoins_sujets SET statut_id = ?, valide_par = ? WHERE id = ?')
        ->execute([$statut, $u['id'], $sujetId]);
    flash('succes', $_POST['action'] === 'valider'
        ? 'Sujet validé : il est désormais visible dans la vitrine publique.'
        : 'Sujet rejeté : il restera invisible des étudiants.');
    redirect(url('admin/index.php'));
}

// ---------- Statistiques ----------
$stats = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM utilisateurs) AS comptes,
            (SELECT COUNT(*) FROM utilisateurs WHERE role_id = ' . ROLE_ETUDIANT . ')  AS etudiants,
            (SELECT COUNT(*) FROM utilisateurs WHERE role_id = ' . ROLE_PARTENAIRE . ') AS partenaires,
            (SELECT COUNT(*) FROM besoins_sujets WHERE statut_id = ' . STVAL_EN_ATTENTE . ') AS en_attente,
            (SELECT COUNT(*) FROM besoins_sujets WHERE statut_id = ' . STVAL_VALIDE . ')     AS valides,
            (SELECT COUNT(*) FROM projets_etudiants) AS projets,
            (SELECT COUNT(*) FROM candidatures)      AS candidatures'
)->fetch();

// ---------- Sujets en attente de modération ----------
$enAttente = $pdo->query(
    'SELECT s.*, u.nom_structure
     FROM besoins_sujets s
     JOIN utilisateurs u ON u.id = s.id_partenaire
     WHERE s.statut_id = ' . STVAL_EN_ATTENTE . '
     ORDER BY s.date_creation ASC'
)->fetchAll();

// ---------- Dernières candidatures ----------
$dernieresCand = $pdo->query(
    'SELECT c.date_candidature, s.titre_sujet, e.id AS etudiant_id, CONCAT(e.prenom, " ", e.nom) AS etudiant
     FROM candidatures c
     JOIN besoins_sujets s ON s.id = c.id_sujet
     JOIN utilisateurs e   ON e.id = c.id_etudiant
     ORDER BY c.date_candidature DESC LIMIT 6'
)->fetchAll();

$titre = 'Administration';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-titre">
  <h1>Administration UK-Connect</h1>
  <p>Modération des sujets et suivi général de la plateforme.</p>
</div>

<div class="stats">
  <div class="stat"><b><?= (int)$stats['comptes'] ?></b><span>Comptes</span></div>
  <div class="stat"><b><?= (int)$stats['etudiants'] ?></b><span>Étudiants</span></div>
  <div class="stat"><b><?= (int)$stats['partenaires'] ?></b><span>Partenaires</span></div>
  <div class="stat"><b><?= (int)$stats['en_attente'] ?></b><span>Sujets à modérer</span></div>
  <div class="stat"><b><?= (int)$stats['valides'] ?></b><span>Sujets validés</span></div>
  <div class="stat"><b><?= (int)$stats['projets'] ?></b><span>Projets</span></div>
  <div class="stat"><b><?= (int)$stats['candidatures'] ?></b><span>Candidatures</span></div>
</div>

<section class="section">
  <div class="titre-section">
    <h2><i class="fa-solid fa-gears"></i> Sujets en attente de validation</h2>
    <a href="<?= url('admin/utilisateurs.php') ?>">Gérer les comptes <i class="fa-solid fa-arrow-right"></i></a>
  </div>

  <?php if (!$enAttente): ?>
    <div class="panneau"><p class="muted">Aucun sujet en attente : la file de modération est vide. <i class="fa-solid fa-face-smile-beam"></i></p></div>
  <?php else: ?>
    <?php foreach ($enAttente as $s): ?>
    <div class="panneau" style="margin-bottom:14px;">
      <div class="etiquettes" style="margin-bottom:6px;">
        <span class="etiquette"><?= e($s['secteur_filiere']) ?></span>
        <span class="etiquette etiquette-gris">Déposé par <?= e($s['nom_structure']) ?> · <?= date_fr($s['date_creation']) ?></span>
      </div>
      <h3 style="margin-bottom:8px;"><?= e($s['titre_sujet']) ?></h3>
      <p style="white-space:pre-line; color:var(--gris); font-size:14.5px; margin-bottom:12px;">
        <?= e(extrait($s['description_probleme'], 400)) ?>
      </p>
      <form method="post" class="actions-bas">
        <?= csrf_field() ?>
        <input type="hidden" name="sujet_id" value="<?= (int)$s['id'] ?>">
        <button class="btn btn-vert btn-petit" name="action" value="valider"
                data-confirm="Valider ce sujet ? Il deviendra visible publiquement."><i class="fa-solid fa-check"></i> Valider</button>
        <button class="btn btn-rouge btn-petit" name="action" value="rejeter"
                data-confirm="Rejeter ce sujet ? Il ne sera jamais publié."><i class="fa-solid fa-xmark"></i> Rejeter</button>
      </form>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<section class="section">
  <div class="titre-section"><h2><i class="fa-solid fa-chart-line"></i> Activité récente</h2></div>
  <div class="tableau-englobant">
    <table>
      <thead><tr><th>Candidature déposée</th><th>Étudiant</th><th>Sujet</th></tr></thead>
      <tbody>
        <?php foreach ($dernieresCand as $c): ?>
        <tr>
          <td><?= date_fr($c['date_candidature']) ?></td>
          <td><a href="<?= url('../etudiant.php?id=' . (int)$c['etudiant_id']) ?>"><b><?= e($c['etudiant']) ?></b></a></td>
          <td><?= e($c['titre_sujet']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$dernieresCand): ?>
        <tr><td colspan="3" class="muted">Aucune candidature enregistrée.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
