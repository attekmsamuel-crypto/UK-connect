<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$moi = exiger_role('etudiant');
$pdo = connexion_bdd();

$saisie = ['titre' => '', 'departement' => '', 'technologies' => '', 'resume' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    foreach (array_keys($saisie) as $champ) {
        $saisie[$champ] = trim($_POST[$champ] ?? '');
    }

    if (mb_strlen($saisie['titre']) < 10) {
        $erreurs[] = 'Donnez un titre d\'au moins 10 caractères.';
    }
    if (mb_strlen($saisie['resume']) < 120) {
        $erreurs[] = 'Le résumé doit faire au moins 120 caractères : le sujet, la méthode, les résultats.';
    }

    $fichier = null;
    if (!$erreurs) {
        try {
            $fichier = televerser_pdf('fichier_pdf');
        } catch (RuntimeException $erreur) {
            $erreurs[] = $erreur->getMessage();
        }
    }

    if (!$erreurs) {
        $id = ajouter_projet($pdo, [
            'id_etudiant'  => (int)$moi['id'],
            'titre'        => $saisie['titre'],
            'departement'  => $saisie['departement'] !== '' ? $saisie['departement'] : null,
            'resume'       => $saisie['resume'],
            'technologies' => $saisie['technologies'] !== '' ? $saisie['technologies'] : null,
            'fichier_pdf'  => $fichier,
        ]);

        if ($id > 0) {
            message('succes', 'Votre projet est publié dans le portfolio.');
            rediriger(lien('projet.php?id=' . $id));
        }

        $erreurs[] = 'Le projet n\'a pas pu être publié.';
    }
}

$titrePage = 'Publier un projet';
require __DIR__ . '/includes/entete.php';
?>

<div class="formulaire-large" style="margin:0 auto;">
  <div class="titre-page">
    <h1>Publier un projet</h1>
    <p>Mémoire, projet de fin d'études, travail personnel : ce que vous publiez ici reste consultable par les partenaires.</p>
  </div>

  <?php if ($erreurs): ?>
    <div class="message message-erreur">
      <?= icone('croix', 19) ?>
      <div>
        <?php foreach ($erreurs as $erreur): ?><div><?= e($erreur) ?></div><?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="panneau">
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= champ_csrf() ?>

      <div class="champ">
        <label for="titre">Titre du projet</label>
        <input type="text" id="titre" name="titre" value="<?= e($saisie['titre']) ?>" maxlength="200" required>
      </div>

      <div class="deux-colonnes">
        <div class="champ">
          <label for="departement">Domaine d'étude</label>
          <input type="text" id="departement" name="departement" value="<?= e($saisie['departement']) ?>" maxlength="150"
                 placeholder="Génie logiciel, Sciences de gestion, Droit public…">
        </div>
        <div class="champ">
          <label for="technologies">Outils et méthodes</label>
          <input type="text" id="technologies" name="technologies" value="<?= e($saisie['technologies']) ?>" maxlength="255"
                 placeholder="PHP, MySQL, enquête de terrain, QGIS…">
        </div>
      </div>

      <div class="champ">
        <label for="resume">Résumé</label>
        <textarea id="resume" name="resume" required
                  placeholder="Le problème traité, la méthode suivie, les résultats obtenus."><?= e($saisie['resume']) ?></textarea>
        <div class="aide">C'est ce texte que liront les partenaires : soyez concret sur les résultats.</div>
      </div>

      <div class="champ">
        <label for="fichier_pdf">Document PDF (facultatif)</label>
        <input type="file" id="fichier_pdf" name="fichier_pdf" accept="application/pdf">
        <div class="aide">Résumé étendu ou mémoire complet, 5 Mo au maximum.</div>
      </div>

      <div class="ligne-boutons">
        <button type="submit" class="bouton">Publier</button>
        <a class="bouton bouton-secondaire" href="<?= lien('etudiant.php?id=' . (int)$moi['id']) ?>">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/pied.php'; ?>
