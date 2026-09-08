<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$moi = exiger_role('etudiant');
$pdo = connexion_bdd();
$candidatures = candidatures_de_etudiant($pdo, (int)$moi['id']);

$titrePage = 'Mes candidatures';
require __DIR__ . '/includes/entete.php';
?>

<div class="titre-page">
  <h1>Mes candidatures</h1>
  <p>Suivez ici les réponses des partenaires aux sujets sur lesquels vous vous êtes positionné.</p>
</div>

<?php if (!$candidatures): ?>
  <div class="vide">
    Vous n'avez encore candidaté sur aucun besoin.
    <a href="<?= lien('besoins.php') ?>">Parcourir les besoins publiés</a>.
  </div>
<?php else: ?>
  <div class="tableau">
    <table>
      <thead>
        <tr>
          <th>Sujet</th>
          <th>Partenaire</th>
          <th>Envoyée le</th>
          <th>Réponse</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($candidatures as $c): ?>
          <tr>
            <td>
              <a href="<?= lien('besoin.php?id=' . (int)$c['id_besoin']) ?>"><?= e($c['titre']) ?></a>
              <div class="petit discret"><?= e($c['secteur']) ?></div>
            </td>
            <td><?= e($c['nom_structure']) ?></td>
            <td><?= date_courte($c['date_candidature']) ?></td>
            <td>
              <span class="<?= classe_statut($c['statut']) ?>"><?= e(libelle_statut($c['statut'])) ?></span>
              <?php if ($c['date_decision']): ?>
                <div class="petit discret">le <?= date_courte($c['date_decision']) ?></div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/pied.php'; ?>
