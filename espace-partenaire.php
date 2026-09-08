<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$moi = exiger_role('partenaire');
$pdo = connexion_bdd();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $dossier = candidature($pdo, (int)($_POST['candidature_id'] ?? 0));
    $decision = ($_POST['decision'] ?? '') === 'retenue' ? 'retenue' : 'non_retenue';

    if (!$dossier || (int)$dossier['id_partenaire'] !== (int)$moi['id']) {
        message('erreur', 'Cette candidature ne concerne aucun de vos besoins.');
    } else {
        changer_statut_candidature($pdo, (int)$dossier['id'], $decision);

        ajouter_notification(
            $pdo,
            (int)$dossier['id_etudiant'],
            $decision === 'retenue' ? 'Candidature retenue' : 'Candidature non retenue',
            'Votre candidature sur : ' . $dossier['titre'] . ' a reçu une réponse',
            'mes-candidatures.php'
        );

        message('succes', $decision === 'retenue'
            ? 'Candidature retenue. L\'étudiant en est informé et ses coordonnées restent affichées ci-dessous.'
            : 'Candidature écartée. L\'étudiant en est informé.');
    }

    rediriger(lien('espace-partenaire.php'));
}

$besoins = besoins_du_partenaire($pdo, (int)$moi['id']);
$candidatures = candidatures_du_partenaire($pdo, (int)$moi['id']);

$enAttente = 0;
foreach ($candidatures as $c) {
    if ($c['statut'] === 'en_attente') {
        $enAttente++;
    }
}

$titrePage = 'Mon espace';
require __DIR__ . '/includes/entete.php';
?>

<div class="titre-page">
  <h1><?= e($moi['nom_structure']) ?></h1>
  <p>Vos besoins déposés et les candidatures reçues.</p>
</div>

<?php if ($enAttente > 0): ?>
  <div class="message message-succes">
    <?= $enAttente ?> candidature<?= $enAttente > 1 ? 's' : '' ?> en attente de votre réponse.
  </div>
<?php endif; ?>

<section class="section">
  <div class="section-entete">
    <h2>Mes besoins</h2>
    <a class="bouton bouton-petit" href="<?= lien('deposer-besoin.php') ?>">Déposer un besoin</a>
  </div>

  <?php if (!$besoins): ?>
    <div class="vide">Vous n'avez encore déposé aucun besoin.</div>
  <?php else: ?>
    <div class="tableau">
      <table>
        <thead>
          <tr>
            <th>Besoin</th>
            <th>Déposé le</th>
            <th>État</th>
            <th>Candidatures</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($besoins as $b): ?>
            <tr>
              <td>
                <a href="<?= lien('besoin.php?id=' . (int)$b['id']) ?>"><?= e($b['titre']) ?></a>
                <div class="petit discret"><?= e($b['secteur']) ?></div>
              </td>
              <td><?= date_courte($b['date_depot']) ?></td>
              <td><span class="<?= classe_statut($b['statut']) ?>"><?= e(libelle_statut($b['statut'])) ?></span></td>
              <td>
                <?= (int)$b['nb_candidatures'] ?>
                <?php if ((int)$b['nb_attente'] > 0): ?>
                  <div class="petit discret"><?= (int)$b['nb_attente'] ?> sans réponse</div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="section">
  <div class="section-entete"><h2>Candidatures reçues</h2></div>

  <?php if (!$candidatures): ?>
    <div class="vide">Aucune candidature reçue pour l'instant.</div>
  <?php else: ?>
    <div class="tableau">
      <table>
        <thead>
          <tr>
            <th>Étudiant</th>
            <th>Sujet</th>
            <th>Motivation</th>
            <th>Reçue le</th>
            <th>Décision</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($candidatures as $c): ?>
            <tr>
              <td>
                <a href="<?= lien('etudiant.php?id=' . (int)$c['id_etudiant']) ?>"><?= e($c['prenom'] . ' ' . $c['nom']) ?></a>
                <div class="petit discret">
                  <?= e($c['sigle'] ?: '—') ?><?= $c['niveau_etudes'] ? ' · ' . e($c['niveau_etudes']) : '' ?>
                </div>
                <?php if ($c['statut'] === 'retenue'): ?>
                  <div class="petit"><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></div>
                  <?php if ($c['telephone']): ?><div class="petit discret"><?= e($c['telephone']) ?></div><?php endif; ?>
                <?php endif; ?>
              </td>
              <td><a href="<?= lien('besoin.php?id=' . (int)$c['id_besoin']) ?>"><?= e($c['titre']) ?></a></td>
              <td class="petit"><?= e(resume_court($c['motivation'], 150)) ?: '<span class="discret">—</span>' ?></td>
              <td><?= date_courte($c['date_candidature']) ?></td>
              <td>
                <?php if ($c['statut'] === 'en_attente'): ?>
                  <form method="post" class="ligne-boutons">
                    <?= champ_csrf() ?>
                    <input type="hidden" name="candidature_id" value="<?= (int)$c['id'] ?>">
                    <button type="submit" name="decision" value="retenue" class="bouton bouton-vert bouton-petit"
                            data-confirmer="Retenir <?= e($c['prenom'] . ' ' . $c['nom']) ?> sur ce sujet ?">Retenir</button>
                    <button type="submit" name="decision" value="non_retenue" class="bouton bouton-rouge bouton-petit"
                            data-confirmer="Écarter cette candidature ?">Écarter</button>
                  </form>
                <?php else: ?>
                  <span class="<?= classe_statut($c['statut']) ?>"><?= e(libelle_statut($c['statut'])) ?></span>
                  <div class="petit discret"><?= date_courte($c['date_decision']) ?></div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/pied.php'; ?>
