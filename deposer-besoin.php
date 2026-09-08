<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$moi = exiger_role('partenaire');
$pdo = connexion_bdd();
$facultes = liste_facultes($pdo);

$saisie = ['titre' => '', 'secteur' => '', 'id_faculte' => '', 'description' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    foreach (array_keys($saisie) as $champ) {
        $saisie[$champ] = trim($_POST[$champ] ?? '');
    }

    if (mb_strlen($saisie['titre']) < 10) {
        $erreurs[] = 'Donnez un titre d\'au moins 10 caractères.';
    }
    if ($saisie['secteur'] === '') {
        $erreurs[] = 'Indiquez le secteur concerné.';
    }
    if (mb_strlen($saisie['description']) < 80) {
        $erreurs[] = 'Décrivez le besoin en 80 caractères au minimum : contexte, difficulté rencontrée, résultat attendu.';
    }

    if (!$erreurs) {
        $id = ajouter_besoin($pdo, [
            'id_partenaire' => (int)$moi['id'],
            'titre'         => $saisie['titre'],
            'description'   => $saisie['description'],
            'secteur'       => $saisie['secteur'],
            'id_faculte'    => (int)$saisie['id_faculte'] > 0 ? (int)$saisie['id_faculte'] : null,
        ]);

        if ($id > 0) {
            message('succes', 'Votre besoin est enregistré. Il sera visible des étudiants dès sa validation par l\'université.');
            rediriger(lien('espace-partenaire.php'));
        }

        $erreurs[] = 'Le besoin n\'a pas pu être enregistré.';
    }
}

$titrePage = 'Déposer un besoin';
require __DIR__ . '/includes/entete.php';
?>

<div class="formulaire-large" style="margin:0 auto;">
  <div class="titre-page">
    <h1>Déposer un besoin</h1>
    <p>Décrivez une difficulté concrète de votre structure. Après relecture par l'université, elle sera proposée aux étudiants.</p>
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
    <form method="post" novalidate>
      <?= champ_csrf() ?>

      <div class="champ">
        <label for="titre">Titre du besoin</label>
        <input type="text" id="titre" name="titre" value="<?= e($saisie['titre']) ?>" maxlength="200" required
               placeholder="Suivi des adhérents d'une association de producteurs">
      </div>

      <div class="deux-colonnes">
        <div class="champ">
          <label for="secteur">Secteur</label>
          <input type="text" id="secteur" name="secteur" value="<?= e($saisie['secteur']) ?>" maxlength="100" required
                 placeholder="Agriculture, Santé, Gestion, Environnement…">
        </div>
        <div class="champ">
          <label for="id_faculte">Faculté suggérée</label>
          <select id="id_faculte" name="id_faculte">
            <option value="0">Je ne sais pas</option>
            <?php foreach ($facultes as $faculte): ?>
              <option value="<?= (int)$faculte['id'] ?>" <?= (int)$saisie['id_faculte'] === (int)$faculte['id'] ? 'selected' : '' ?>>
                <?= e($faculte['sigle'] . ' — ' . $faculte['nom_faculte']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="aide">L'université confirmera ou corrigera ce rattachement.</div>
        </div>
      </div>

      <div class="champ">
        <label for="description">Description</label>
        <textarea id="description" name="description" required
                  placeholder="Le contexte, ce qui pose problème aujourd'hui, ce que vous attendez du travail de l'étudiant."><?= e($saisie['description']) ?></textarea>
        <div class="aide">Plus la description est précise, plus les candidatures reçues seront pertinentes.</div>
      </div>

      <div class="ligne-boutons">
        <button type="submit" class="bouton">Envoyer pour validation</button>
        <a class="bouton bouton-secondaire" href="<?= lien('espace-partenaire.php') ?>">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/pied.php'; ?>
