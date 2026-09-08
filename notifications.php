<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$moi = exiger_connexion();
$pdo = connexion_bdd();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();
    marquer_notifications_lues($pdo, (int)$moi['id']);
    message('succes', 'Toutes vos notifications sont marquées comme lues.');
    rediriger(lien('notifications.php'));
}

$notifications = notifications_de($pdo, (int)$moi['id']);
$nonLues = compter_notifications_non_lues($pdo, (int)$moi['id']);

$titrePage = 'Notifications';
require __DIR__ . '/includes/entete.php';
?>

<div class="titre-page">
  <h1>Notifications</h1>
  <p><?= $nonLues > 0 ? $nonLues . ' notification' . ($nonLues > 1 ? 's' : '') . ' non lue' . ($nonLues > 1 ? 's' : '') : 'Tout est à jour.' ?></p>
</div>

<?php if ($nonLues > 0): ?>
  <form method="post" style="margin-bottom:18px;">
    <?= champ_csrf() ?>
    <button type="submit" class="bouton bouton-secondaire bouton-petit">Tout marquer comme lu</button>
  </form>
<?php endif; ?>

<?php if (!$notifications): ?>
  <div class="vide">Vous n'avez encore reçu aucune notification.</div>
<?php else: ?>
  <div class="liste-notifications">
    <?php foreach ($notifications as $n): ?>
      <div class="notification<?= (int)$n['lu'] === 0 ? ' non-lue' : '' ?>">
        <div>
          <b><?= e($n['titre']) ?></b>
          <p><?= e($n['message']) ?></p>
          <?php if ($n['lien']): ?>
            <p class="petit"><a href="<?= lien($n['lien']) ?>">Ouvrir la page concernée</a></p>
          <?php endif; ?>
        </div>
        <span class="date"><?= date_courte($n['date_creation']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/pied.php'; ?>
