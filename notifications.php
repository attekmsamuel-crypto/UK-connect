<?php
/**
 * UK-Connect — Centre de notifications
 * Les notifications sont CRÉÉES AUTOMATIQUEMENT par les triggers de la base
 * (candidature reçue, décision, modération). Cette page les affiche, permet
 * de les marquer comme lues et redirige vers la page concernée.
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$u = require_login();
$pdo = db();
csrf_verifier();

// ---------- Marquer comme lue (une, puis redirection vers son lien) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lire_id'])) {
    $pdo->prepare('UPDATE notifications SET lu = 1 WHERE id = ? AND id_utilisateur = ?')
        ->execute([(int)$_POST['lire_id'], $u['id']]);
    $st = $pdo->prepare('SELECT lien FROM notifications WHERE id = ?');
    $st->execute([(int)$_POST['lire_id']]);
    $lien = $st->fetchColumn();
    redirect($lien ? url($lien) : url('notifications.php'));
}

// ---------- Tout marquer comme lu ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tout_lu'])) {
    $pdo->prepare('UPDATE notifications SET lu = 1 WHERE id_utilisateur = ?')
        ->execute([$u['id']]);
    flash('succes', 'Toutes vos notifications sont marquées comme lues.');
    redirect(url('notifications.php'));
}

// ---------- Liste ----------
$st = $pdo->prepare(
    'SELECT * FROM notifications WHERE id_utilisateur = ? ORDER BY lu ASC, date_creation DESC LIMIT 50'
);
$st->execute([$u['id']]);
$notifications = $st->fetchAll();
$nbNonLues = count(array_filter($notifications, fn($n) => !$n['lu']));

// Icône selon le type de notification (titre généré par les triggers)
function icone_notif(string $titre): string {
    if (str_contains($titre, 'retenue'))  return 'fa-circle-check';
    if (str_contains($titre, 'candidature')) return 'fa-paper-plane';
    if (str_contains($titre, 'validé'))   return 'fa-circle-check';
    return 'fa-bell';
}

$titre = 'Mes notifications';
require __DIR__ . '/includes/header.php';
?>

<div class="page-titre" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
  <div>
    <h1><i class="fa-solid fa-bell"></i> Mes notifications</h1>
    <p><?= $nbNonLues > 0 ? $nbNonLues . ' non lue(s)' : 'Vous êtes à jour ✓' ?></p>
  </div>
  <?php if ($nbNonLues > 0): ?>
  <form method="post">
    <?= csrf_field() ?>
    <button class="btn btn-outline btn-petit" name="tout_lu" value="1">
      <i class="fa-solid fa-check-double"></i> Tout marquer comme lu</button>
  </form>
  <?php endif; ?>
</div>

<?php if (!$notifications): ?>
  <div class="panneau"><p class="muted">Aucune notification pour le moment.
    Elles apparaîtront ici automatiquement (nouvelle candidature, décision du partenaire, validation de sujet…).</p></div>
<?php else: ?>
<div class="grille-cartes">
  <?php foreach ($notifications as $n): ?>
    <div class="carte" style="<?= $n['lu'] ? 'opacity:.62;' : 'border-left:4px solid var(--or);' ?>">
      <div class="etiquettes">
        <span class="etiquette"><i class="fa-solid <?= icone_notif($n['titre']) ?>"></i>
          <?= $n['lu'] ? 'Lue' : 'Nouvelle' ?></span>
        <span class="etiquette etiquette-gris"><?= date_fr($n['date_creation']) ?></span>
      </div>
      <h3><?= e($n['titre']) ?></h3>
      <p><?= e($n['message']) ?></p>
      <form method="post" class="actions-bas">
        <?= csrf_field() ?>
        <input type="hidden" name="lire_id" value="<?= (int)$n['id'] ?>">
        <button class="btn btn-bleu btn-petit">
          <?= $n['lien'] ? '<i class="fa-solid fa-arrow-right"></i> Consulter' : '<i class="fa-solid fa-check"></i> Marquer comme lue' ?>
        </button>
      </form>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
