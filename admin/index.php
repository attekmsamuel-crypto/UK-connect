<?php

require_once __DIR__ . '/../includes/auth.php';

demarrer_session();

$moi = exiger_role('admin');
$pdo = connexion_bdd();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $id = (int)($_POST['besoin_id'] ?? 0);
    $decision = ($_POST['decision'] ?? '') === 'valide' ? 'valide' : 'rejete';
    $fiche = besoin($pdo, $id);

    if (!$fiche) {
        message('erreur', 'Ce besoin est introuvable.');
    } else {
        moderer_besoin($pdo, $id, $decision, (int)$moi['id']);

        ajouter_notification(
            $pdo,
            (int)$fiche['id_partenaire'],
            $decision === 'valide' ? 'Besoin publié' : 'Besoin non retenu',
            $decision === 'valide'
                ? 'Votre besoin : ' . $fiche['titre'] . ' est désormais visible des étudiants'
                : 'Votre besoin : ' . $fiche['titre'] . ' n\'a pas été retenu',
            'espace-partenaire.php'
        );

        message('succes', $decision === 'valide'
            ? 'Besoin publié. Il apparaît maintenant dans la liste publique.'
            : 'Besoin rejeté. Il ne sera pas visible des étudiants.');
    }

    rediriger(lien('admin/index.php'));
}

$chiffres = chiffres_admin($pdo);
$aModerer = besoins_en_attente($pdo);
$activite = dernieres_candidatures($pdo, 8);

$titrePage = 'Modération';
require __DIR__ . '/../includes/entete.php';
?>

<div class="titre-page">
  <h1>Administration</h1>
  <p>Relecture des besoins déposés par les partenaires et suivi général de la plateforme.</p>
</div>

<div class="chiffres chiffres-clairs">
  <div class="chiffre"><b><?= (int)$chiffres['a_moderer'] ?></b><span>besoins à relire</span></div>
  <div class="chiffre"><b><?= (int)$chiffres['publies'] ?></b><span>besoins publiés</span></div>
  <div class="chiffre"><b><?= (int)$chiffres['projets'] ?></b><span>projets déposés</span></div>
  <div class="chiffre"><b><?= (int)$chiffres['candidatures'] ?></b><span>candidatures</span></div>
</div>

<section class="section">
  <div class="section-entete">
    <h2>Besoins en attente de relecture</h2>
    <a href="<?= lien('admin/utilisateurs.php') ?>">Gérer les comptes</a>
  </div>

  <?php if (!$aModerer): ?>
    <div class="vide">Aucun besoin en attente. La file de modération est vide.</div>
  <?php else: ?>
    <?php foreach ($aModerer as $b): ?>
      <div class="panneau ton-<?= e(ton_secteur($b['secteur'])) ?>" style="margin-bottom:16px;">
        <div class="etiquettes">
          <span class="etiquette"><?= e($b['secteur']) ?></span>
          <?php if ($b['sigle']): ?><span class="etiquette etiquette-faculte"><?= e($b['sigle']) ?></span><?php endif; ?>
          <span class="etiquette etiquette-neutre"><?= icone('immeuble', 14) ?> <?= e($b['nom_structure']) ?></span>
        </div>
        <h3><a href="<?= lien('besoin.php?id=' . (int)$b['id']) ?>"><?= e($b['titre']) ?></a></h3>
        <p class="petit discret">Déposé le <?= date_courte($b['date_depot']) ?></p>
        <p><?= e(resume_court($b['description'], 420)) ?></p>

        <form method="post" class="ligne-boutons">
          <?= champ_csrf() ?>
          <input type="hidden" name="besoin_id" value="<?= (int)$b['id'] ?>">
          <button type="submit" name="decision" value="valide" class="bouton bouton-vert bouton-petit"
                  data-confirmer="Publier ce besoin ? Il deviendra visible de tous les étudiants.">Publier</button>
          <button type="submit" name="decision" value="rejete" class="bouton bouton-rouge bouton-petit"
                  data-confirmer="Rejeter ce besoin ?">Rejeter</button>
          <a class="petit" href="<?= lien('besoin.php?id=' . (int)$b['id']) ?>">Lire en entier</a>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<section class="section">
  <div class="section-entete"><h2>Dernières candidatures</h2></div>
  <?php if (!$activite): ?>
    <div class="vide">Aucune candidature enregistrée.</div>
  <?php else: ?>
    <div class="tableau">
      <table>
        <thead>
          <tr><th>Date</th><th>Étudiant</th><th>Sujet</th><th>État</th></tr>
        </thead>
        <tbody>
          <?php foreach ($activite as $c): ?>
            <tr>
              <td><?= date_courte($c['date_candidature']) ?></td>
              <td><a href="<?= lien('etudiant.php?id=' . (int)$c['id_etudiant']) ?>"><?= e($c['prenom'] . ' ' . $c['nom']) ?></a></td>
              <td><?= e($c['titre']) ?></td>
              <td><span class="<?= classe_statut($c['statut']) ?>"><?= e(libelle_statut($c['statut'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/pied.php'; ?>
