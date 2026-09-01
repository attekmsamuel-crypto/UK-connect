<?php
/**
 * UK-Connect — Détail d'un sujet + candidature (module 3)
 * Un sujet non validé n'est JAMAIS visible ici (vue v_sujets_publics).
 * L'anti-doublon est garanti par la contrainte UNIQUE (id_sujet, id_etudiant).
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$pdo = db();
csrf_verifier();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM v_sujets_publics WHERE id = ?');
$st->execute([$id]);
$s = $st->fetch();
if (!$s) {
    flash('erreur', 'Sujet introuvable ou non validé.');
    redirect(url('sujets.php'));
}

// ---------- Traitement de la candidature (étudiant connecté) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidater'])) {
    $u = require_role('etudiant');
    try {
        $pdo->prepare(
            'INSERT INTO candidatures (id_sujet, id_etudiant, statut_id) VALUES (?, ?, ?)'
        )->execute([$id, $u['id'], STCAND_EN_ATTENTE]);
        flash('succes', 'Votre candidature a été envoyée. Le partenaire vous répondra depuis son espace. ✓');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            flash('erreur', 'Vous avez déjà candidaté à ce sujet — une seule candidature est autorisée.');
        } else {
            flash('erreur', 'Erreur technique : candidature non enregistrée.');
        }
    }
    redirect(url('sujet-voir.php?id=' . $id));
}

// Déjà candidaté ?
$dejaCandidat = false;
if (est_etudiant()) {
    $st = $pdo->prepare('SELECT 1 FROM candidatures WHERE id_sujet = ? AND id_etudiant = ?');
    $st->execute([$id, current_user()['id']]);
    $dejaCandidat = (bool)$st->fetch();
}

$titre = $s['titre_sujet'];
require __DIR__ . '/includes/header.php';
?>

<div class="page-titre">
  <div class="etiquettes" style="margin-bottom:8px;">
    <span class="etiquette"><?= e($s['secteur_filiere']) ?></span>
    <?php if ($s['faculte_sigle']): ?><span class="etiquette etiquette-or">Faculté suggérée : <?= e($s['faculte_sigle']) ?></span><?php endif; ?>
  </div>
  <h1><?= e($s['titre_sujet']) ?></h1>
  <p>Déposé par <b><?= e($s['partenaire']) ?></b> le <?= date_fr($s['date_creation']) ?></p>
</div>

<div class="panneau" style="margin-bottom:18px;">
  <h2 style="font-size:17px; margin-bottom:10px; color:var(--bleu);">Description du besoin</h2>
  <p style="white-space: pre-line;"><?= e($s['description_probleme']) ?></p>
</div>

<div class="panneau">
  <?php if (est_etudiant()): ?>
    <?php if ($dejaCandidat): ?>
      <p><i class="fa-solid fa-circle-check"></i> <b>Vous avez déjà candidaté à ce sujet.</b>
         Suivez la décision du partenaire dans
         <a href="<?= url('mes-candidatures.php') ?>">Mes candidatures</a>.</p>
    <?php else: ?>
      <h2 style="font-size:17px; margin-bottom:10px; color:var(--bleu);">Candidater à ce sujet</h2>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="candidater" value="1">
        <p class="muted" style="margin-bottom:12px;">
          Votre profil (nom, faculté, contact) sera transmis au partenaire avec votre candidature.
        </p>
        <button type="submit" class="btn btn-or">Envoyer ma candidature</button>
      </form>
    <?php endif; ?>

  <?php elseif (!est_connecte()): ?>
    <p><i class="fa-solid fa-right-to-bracket"></i> <a href="<?= url('connexion.php') ?>"><b>Connectez-vous</b></a> ou
       <a href="<?= url('inscription.php') ?>"><b>créez un compte étudiant</b></a> pour candidater à ce sujet.</p>

  <?php elseif (est_partenaire()): ?>
    <p class="muted">Espace partenaire : ce sujet est publié sur la vitrine. Les candidatures reçues se gèrent dans
       <a href="<?= url('partenaire-espace.php') ?>">votre espace</a>.</p>

  <?php else: ?>
    <p class="muted">Connecté en tant qu'administrateur : vous voyez la page telle qu'un visiteur la voit.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
