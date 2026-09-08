<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$pdo = connexion_bdd();
$moi = utilisateur_actuel();
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();
    $etudiant = exiger_role('etudiant');
    $motivation = trim($_POST['motivation'] ?? '');
    $fiche = besoin($pdo, $id);

    if (!$fiche || $fiche['statut'] !== 'valide') {
        message('erreur', 'Ce besoin n\'est plus ouvert aux candidatures.');
    } elseif (a_deja_postule($pdo, $id, (int)$etudiant['id'])) {
        message('erreur', 'Vous avez déjà candidaté sur ce besoin.');
    } else {
        $idCandidature = ajouter_candidature($pdo, $id, (int)$etudiant['id'], $motivation);

        if ($idCandidature > 0) {
            ajouter_notification(
                $pdo,
                (int)$fiche['id_partenaire'],
                'Nouvelle candidature',
                $etudiant['prenom'] . ' ' . $etudiant['nom'] . ' a postulé sur : ' . $fiche['titre'],
                'espace-partenaire.php'
            );
            message('succes', 'Votre candidature est enregistrée. Le partenaire en est informé.');
        } else {
            message('erreur', 'La candidature n\'a pas pu être enregistrée.');
        }
    }

    rediriger(lien('besoin.php?id=' . $id));
}

$fiche = besoin($pdo, $id);

if (!$fiche) {
    http_response_code(404);
    $titrePage = 'Besoin introuvable';
    require __DIR__ . '/includes/entete.php';
    echo '<div class="vide">Ce besoin n\'existe pas ou n\'est plus disponible. <a href="' . lien('besoins.php') . '">Revenir à la liste</a>.</div>';
    require __DIR__ . '/includes/pied.php';
    exit;
}

$visible = $fiche['statut'] === 'valide'
    || ($moi && ($moi['role'] === 'admin' || (int)$moi['id'] === (int)$fiche['id_partenaire']));

if (!$visible) {
    message('erreur', 'Ce besoin n\'est pas encore publié.');
    rediriger(lien('besoins.php'));
}

$dejaPostule = $moi && $moi['role'] === 'etudiant' && a_deja_postule($pdo, $id, (int)$moi['id']);

$titrePage = $fiche['titre'];
require __DIR__ . '/includes/entete.php';
?>

<a class="retour" href="<?= lien('besoins.php') ?>"><?= fleche('gauche') ?> Retour aux besoins</a>

<div class="titre-page ton-<?= e(ton_secteur($fiche['secteur'])) ?>">
  <div class="etiquettes">
    <span class="etiquette"><?= e($fiche['secteur']) ?></span>
    <?php if ($fiche['sigle']): ?><span class="etiquette etiquette-faculte"><?= e($fiche['sigle']) ?></span><?php endif; ?>
    <?php if ($fiche['statut'] !== 'valide'): ?>
      <span class="<?= classe_statut($fiche['statut']) ?>"><?= e(libelle_statut($fiche['statut'])) ?></span>
    <?php endif; ?>
  </div>
  <h1><?= e($fiche['titre']) ?></h1>
  <p>Déposé par <?= e($fiche['nom_structure']) ?> le <?= date_courte($fiche['date_depot']) ?></p>
</div>

<div class="detail">
  <div>
    <div class="panneau">
      <h2>La problématique</h2>
      <div class="texte-long"><?= e($fiche['description']) ?></div>
    </div>

    <?php if ($moi && $moi['role'] === 'etudiant'): ?>
      <div class="panneau">
        <h2>Candidater sur ce sujet</h2>
        <?php if ($dejaPostule): ?>
          <p class="discret">Vous avez déjà candidaté sur ce besoin. Suivez la réponse du partenaire depuis
            <a href="<?= lien('mes-candidatures.php') ?>">Mes candidatures</a>.</p>
        <?php elseif ($fiche['statut'] !== 'valide'): ?>
          <p class="discret">Ce besoin n'est pas ouvert aux candidatures.</p>
        <?php else: ?>
          <form method="post">
            <?= champ_csrf() ?>
            <div class="champ">
              <label for="motivation">Pourquoi ce sujet vous correspond</label>
              <textarea id="motivation" name="motivation"
                        placeholder="Votre parcours, vos travaux déjà réalisés, ce que vous comptez apporter…"></textarea>
              <div class="aide">Le partenaire lira ce texte à côté de votre profil et de vos projets publiés.</div>
            </div>
            <button type="submit" class="bouton">Envoyer ma candidature</button>
          </form>
        <?php endif; ?>
      </div>
    <?php elseif (!$moi): ?>
      <div class="panneau">
        <p style="margin:0;">
          <a href="<?= lien('connexion.php') ?>">Connectez-vous</a> avec un compte étudiant pour candidater sur ce sujet.
        </p>
      </div>
    <?php endif; ?>
  </div>

  <aside class="encadre">
    <h4>Le partenaire</h4>
    <dl>
      <dt>Structure</dt>
      <dd><?= e($fiche['nom_structure']) ?></dd>
      <?php if (!empty($fiche['bio'])): ?>
        <dt>Présentation</dt>
        <dd><?= e($fiche['bio']) ?></dd>
      <?php endif; ?>
      <dt>Secteur</dt>
      <dd><?= e($fiche['secteur']) ?></dd>
      <?php if ($fiche['nom_faculte']): ?>
        <dt>Faculté concernée</dt>
        <dd><?= e($fiche['nom_faculte']) ?></dd>
      <?php endif; ?>
      <?php if ($moi && in_array($moi['role'], ['admin', 'partenaire'], true)): ?>
        <dt>Contact</dt>
        <dd><a href="mailto:<?= e($fiche['email']) ?>"><?= e($fiche['email']) ?></a></dd>
      <?php endif; ?>
    </dl>
  </aside>
</div>

<?php require __DIR__ . '/includes/pied.php'; ?>
