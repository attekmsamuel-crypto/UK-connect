<?php
/**
 * UK-Connect — Connexion
 */
require_once __DIR__ . '/includes/auth.php';
session_init();

if (est_connecte()) {
    redirect(url('index.php'));
}
csrf_verifier(); // après la session, avant tout traitement POST

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';
    if ($email !== '' && $mdp !== '' && login($email, $mdp)) {
        flash('succes', 'Bienvenue, vous êtes connecté.');
        redirect(url('index.php'));
    }
    // sinon : le message d'erreur flash est déjà positionné par login()
}

$titre = 'Connexion';
require __DIR__ . '/includes/header.php';
?>

<div class="panneau panneau-etroite">
  <div class="titre-panneau">
    <h1>Connexion</h1>
    <p>Accédez à votre espace étudiant, partenaire ou administrateur.</p>
  </div>

  <form method="post">
    <?= csrf_field() ?>
    <div class="champ">
      <label for="email">Adresse email</label>
      <input type="email" id="email" name="email" required autofocus
             value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div class="champ">
      <label for="mot_de_passe">Mot de passe</label>
      <input type="password" id="mot_de_passe" name="mot_de_passe" required>
    </div>
    <button type="submit" class="btn btn-bleu btn-bloc">Se connecter</button>
  </form>

  <p style="margin-top:16px; text-align:center;" class="muted">
    Pas encore de compte ? <a href="<?= url('inscription.php') ?>"><b>Créer un compte</b></a>
  </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
