<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

if (est_connecte()) {
    rediriger(accueil_du_role(utilisateur_actuel()));
}

$pdo = connexion_bdd();
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if (connecter($pdo, $email, $motDePasse)) {
        $connecte = utilisateur_par_id($pdo, (int)$_SESSION['utilisateur_id']);
        message('succes', 'Bonjour ' . $connecte['prenom'] . ', vous êtes connecté.');
        rediriger(accueil_du_role($connecte));
    }

    if (empty($_SESSION['message'])) {
        message('erreur', 'Adresse email ou mot de passe incorrect.');
    }
}

$titrePage = 'Connexion';
require __DIR__ . '/includes/entete.php';
?>

<div class="formulaire-etroit">
  <nav class="fil-ariane">
    <a href="<?= lien('index.php') ?>">Accueil</a><span>/</span>Connexion
  </nav>

  <div class="encart">
    <div class="encart-tete">
      <h1>Identification</h1>
    </div>

    <div class="encart-corps">
      <form method="post" novalidate>
        <?= champ_csrf() ?>

        <div class="champ">
          <label for="email">Adresse électronique</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
        </div>

        <div class="champ">
          <label for="mot_de_passe">Mot de passe</label>
          <input type="password" id="mot_de_passe" name="mot_de_passe" required>
        </div>

        <button type="submit" class="bouton">Se connecter</button>
      </form>

      <p class="petit discret" style="margin: 20px 0 0;">
        Les comptes étudiants et partenaires se créent librement :
        <a href="<?= lien('inscription.php') ?>">créer un compte</a>.
        Les comptes d'administration sont attribués par l'université.
      </p>
    </div>

    <div class="encart-note">
      <b>Comptes de démonstration</b> — mot de passe <code>UkConnect2026</code>
      <dl>
        <dt>Étudiant</dt><dd><code>yao.salami@etu-univkara.tg</code></dd>
        <dt>Partenaire</dt><dd><code>contact@agrotogo.tg</code></dd>
        <dt>Administration</dt><dd><code>admin@ukconnect.tg</code></dd>
      </dl>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/pied.php'; ?>
