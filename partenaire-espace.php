<?php
/**
 * UK-Connect — Espace partenaire
 * • Mes sujets déposés + statut de validation (module 2)
 * • Candidatures reçues + décision « retenue / non retenue » (module 3)
 * • Notification à l'écran : compteur de candidatures en attente
 *   (critère d'acceptation : « le partenaire est notifié »).
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$u = require_role('partenaire');
$pdo = db();
csrf_verifier();

// ---------- Décision sur une candidature (retenue / non retenue) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'], $_POST['candidature_id'])) {
    $candId = (int)$_POST['candidature_id'];
    $nouveau = $_POST['decision'] === 'retenue' ? STCAND_RETENUE : STCAND_NON_RETENUE;

    // Sécurité : la candidature doit concerner un sujet DU partenaire connecté
    $st = $pdo->prepare(
        'SELECT c.id FROM candidatures c
         JOIN besoins_sujets s ON s.id = c.id_sujet
         WHERE c.id = ? AND s.id_partenaire = ?'
    );
    $st->execute([$candId, $u['id']]);
    if ($st->fetch()) {
        $pdo->prepare(
            'UPDATE candidatures SET statut_id = ?, date_decision = NOW() WHERE id = ?'
        )->execute([$nouveau, $candId]);
        flash('succes', 'Décision enregistrée. L\'étudiant retenu est joignable via son contact.');
    } else {
        flash('erreur', 'Candidature introuvable parmi vos sujets.');
    }
    redirect(url('partenaire-espace.php'));
}

// ---------- Mes sujets ----------
$st = $pdo->prepare(
    'SELECT s.id, s.titre_sujet, s.date_creation, sv.libelle AS statut, sv.code AS statut_code,
            (SELECT COUNT(*) FROM candidatures c WHERE c.id_sujet = s.id)                       AS total,
            (SELECT COUNT(*) FROM candidatures c WHERE c.id_sujet = s.id AND c.statut_id = ' . STCAND_EN_ATTENTE . ') AS en_attente
     FROM besoins_sujets s
     JOIN statuts_validation sv ON sv.id = s.statut_id
     WHERE s.id_partenaire = ?
     ORDER BY s.date_creation DESC'
);
$st->execute([$u['id']]);
$mesSujets = $st->fetchAll();

// ---------- Candidatures reçues sur mes sujets ----------
$st = $pdo->prepare(
    'SELECT c.id, c.date_candidature, c.date_decision, c.statut_id,
            sc.libelle AS statut_libelle, s.titre_sujet,
            e.id AS etudiant_id, CONCAT(e.prenom, " ", e.nom) AS etudiant,
            e.email, e.telephone, f.sigle AS faculte_sigle
     FROM candidatures c
     JOIN statuts_candidature sc ON sc.id = c.statut_id
     JOIN besoins_sujets s       ON s.id  = c.id_sujet
     JOIN utilisateurs e         ON e.id  = c.id_etudiant
     LEFT JOIN facultes f        ON f.id  = e.id_faculte
     WHERE s.id_partenaire = ?
     ORDER BY (c.statut_id = ' . STCAND_EN_ATTENTE . ') DESC, c.date_candidature DESC'
);
$st->execute([$u['id']]);
$candidatures = $st->fetchAll();

$notif = array_sum(array_column($mesSujets, 'en_attente'));

$titre = 'Mon espace partenaire';
require __DIR__ . '/includes/header.php';
?>

<div class="page-titre">
  <h1>Mon espace — <?= e($u['nom_structure']) ?></h1>
  <p>Suivez vos sujets déposés et choisissez les étudiants que vous retenez.</p>
</div>

<?php if ($notif > 0): ?>
  <div class="alerte alerte-verte">
    <i class="fa-solid fa-bell"></i> Vous avez <b><?= $notif ?> candidature<?= $notif > 1 ? 's' : '' ?> en attente</b> de décision —
    consultez le tableau ci-dessous.
  </div>
<?php endif; ?>

<section class="section">
  <div class="titre-section">
    <h2>Mes sujets déposés</h2>
    <a class="btn btn-bleu btn-petit" href="<?= url('sujet-deposer.php') ?>">+ Déposer un besoin</a>
  </div>
  <?php if (!$mesSujets): ?>
    <div class="panneau"><p>Vous n'avez encore déposé aucun besoin.
      <a href="<?= url('sujet-deposer.php') ?>"><b>Déposer ma première problématique</b></a>.</p></div>
  <?php else: ?>
  <div class="tableau-englobant">
    <table>
      <thead><tr><th>Sujet</th><th>Déposé le</th><th>Statut</th><th>Candidatures</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($mesSujets as $s): ?>
        <tr>
          <td><b><?= e($s['titre_sujet']) ?></b></td>
          <td><?= date_fr($s['date_creation']) ?></td>
          <td><span class="statut statut-<?= e($s['statut_code']) ?>"><?= e($s['statut']) ?></span></td>
          <td>
            <?= (int)$s['total'] ?>
            <?php if ((int)$s['en_attente'] > 0): ?>
              <span class="compteur-notif"><?= (int)$s['en_attente'] ?> en attente</span>
            <?php endif; ?>
          </td>
          <td><a href="<?= url('sujet-voir.php?id=' . (int)$s['id']) ?>">Voir</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<section class="section">
  <div class="titre-section"><h2>Candidatures reçues</h2></div>
  <?php if (!$candidatures): ?>
    <div class="panneau"><p class="muted">Aucune candidature reçue pour l'instant.</p></div>
  <?php else: ?>
  <div class="tableau-englobant">
    <table>
      <thead><tr><th>Étudiant</th><th>Sujet</th><th>Faculté</th><th>Contact</th><th>Candidature</th><th>Décision</th></tr></thead>
      <tbody>
        <?php foreach ($candidatures as $c): ?>
        <tr>
          <td>
            <a href="<?= url('etudiant.php?id=' . (int)$c['etudiant_id']) ?>" title="Voir le portfolio de l'étudiant">
              <b><?= e($c['etudiant']) ?></b></a>
            <br><a class="muted" href="<?= url('etudiant.php?id=' . (int)$c['etudiant_id']) ?>">
              <i class="fa-solid fa-folder-open"></i> Voir son portfolio</a>
          </td>
          <td><?= e($c['titre_sujet']) ?></td>
          <td><?= e($c['faculte_sigle'] ?: '—') ?></td>
          <td>
            <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>
            <?php if ($c['telephone']): ?><br><span class="muted"><?= e($c['telephone']) ?></span><?php endif; ?>
          </td>
          <td><?= date_fr($c['date_candidature']) ?></td>
          <td>
            <?php if ((int)$c['statut_id'] === STCAND_EN_ATTENTE): ?>
              <form method="post" class="actions-bas">
                <?= csrf_field() ?>
                <input type="hidden" name="candidature_id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-vert btn-petit" name="decision" value="retenue"
                        data-confirm="Retenir <?= e($c['etudiant']) ?> ?"><i class="fa-solid fa-check"></i> Retenir</button>
                <button class="btn btn-rouge btn-petit" name="decision" value="non_retenue"
                        data-confirm="Ne pas retenir <?= e($c['etudiant']) ?> ?"><i class="fa-solid fa-xmark"></i> Écarter</button>
              </form>
            <?php else: ?>
              <span class="statut <?= $c['statut_id'] == STCAND_RETENUE ? 'statut-valide' : 'statut-rejete' ?>">
                <?= e($c['statut_libelle']) ?>
              </span>
              <br><span class="muted"><?= date_fr($c['date_decision']) ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
