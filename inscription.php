<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

if (est_connecte()) {
    rediriger(lien('index.php'));
}

$pdo = connexion_bdd();
$facultes = liste_facultes($pdo);

$saisie = [
    'role'          => 'etudiant',
    'prenom'        => '',
    'nom'           => '',
    'email'         => '',
    'telephone'     => '',
    'id_faculte'    => '',
    'nom_structure' => '',
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    foreach (array_keys($saisie) as $champ) {
        $saisie[$champ] = trim($_POST[$champ] ?? '');
    }
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (!in_array($saisie['role'], ['etudiant', 'partenaire'], true)) {
        $saisie['role'] = 'etudiant';
    }
    if ($saisie['prenom'] === '' || $saisie['nom'] === '') {
        $erreurs[] = 'Le nom et le prénom sont obligatoires.';
    }
    if (!filter_var($saisie['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L\'adresse email saisie n\'est pas valide.';
    }
    if (strlen($motDePasse) < 8) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    if ($motDePasse !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne sont pas identiques.';
    }
    if ($saisie['role'] === 'etudiant' && (int)$saisie['id_faculte'] <= 0) {
        $erreurs[] = 'Indiquez la faculté ou l\'institut auquel vous êtes rattaché.';
    }
    if ($saisie['role'] === 'partenaire' && $saisie['nom_structure'] === '') {
        $erreurs[] = 'Indiquez le nom de votre structure.';
    }

    if (!$erreurs) {
        $donnees = [
            'nom'           => $saisie['nom'],
            'prenom'        => $saisie['prenom'],
            'email'         => strtolower($saisie['email']),
            'telephone'     => $saisie['telephone'] !== '' ? $saisie['telephone'] : null,
            'mot_de_passe'  => $motDePasse,
            'role'          => $saisie['role'],
            'id_faculte'    => $saisie['role'] === 'etudiant' ? (int)$saisie['id_faculte'] : null,
            'nom_structure' => $saisie['role'] === 'partenaire' ? $saisie['nom_structure'] : null,
        ];

        try {
            $id = creer_utilisateur($pdo, $donnees);
        } catch (PDOException $erreur) {
            $id = 0;
            if ($erreur->getCode() === '23000') {
                $erreurs[] = 'Cette adresse email est déjà utilisée par un compte.';
            } else {
                $erreurs[] = 'L\'enregistrement a échoué. Réessayez dans un instant.';
            }
        }

        if ($id > 0) {
            message('succes', 'Votre compte est créé. Connectez-vous pour accéder à votre espace.');
            rediriger(lien('connexion.php'));
        }

        if (!$erreurs) {
            $erreurs[] = 'Le compte n\'a pas pu être enregistré.';
        }
    }
}

$titrePage = 'Créer un compte';
require __DIR__ . '/includes/entete.php';
?>

<div class="formulaire-large">
  <div class="titre-page">
    <span class="surtitre">Rejoindre la plateforme</span>
    <h1>Créer un compte</h1>
    <p>Un compte étudiant pour publier vos travaux et candidater sur les sujets ; un compte
       partenaire pour déposer un besoin et choisir qui le traitera.</p>
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

      <div class="choix-role">
        <label>
          <input type="radio" name="role" value="etudiant" <?= $saisie['role'] === 'etudiant' ? 'checked' : '' ?>>
          <b>Étudiant</b>
          <span>Publier mes projets et mémoires, candidater sur les besoins.</span>
        </label>
        <label>
          <input type="radio" name="role" value="partenaire" <?= $saisie['role'] === 'partenaire' ? 'checked' : '' ?>>
          <b>Partenaire</b>
          <span>Entreprise, mairie, ONG, service public. Déposer un besoin.</span>
        </label>
      </div>

      <div class="deux-colonnes">
        <div class="champ">
          <label for="prenom">Prénom</label>
          <input type="text" id="prenom" name="prenom" value="<?= e($saisie['prenom']) ?>" required>
        </div>
        <div class="champ">
          <label for="nom">Nom</label>
          <input type="text" id="nom" name="nom" value="<?= e($saisie['nom']) ?>" required>
        </div>
      </div>

      <div class="deux-colonnes">
        <div class="champ">
          <label for="email">Adresse email</label>
          <input type="email" id="email" name="email" value="<?= e($saisie['email']) ?>" required>
        </div>
        <div class="champ">
          <label for="telephone">Téléphone</label>
          <input type="tel" id="telephone" name="telephone" value="<?= e($saisie['telephone']) ?>">
        </div>
      </div>

      <div class="champ" id="bloc-etudiant">
        <label for="id_faculte">Faculté ou institut</label>
        <select id="id_faculte" name="id_faculte">
          <option value="">— choisir —</option>
          <?php foreach ($facultes as $faculte): ?>
            <option value="<?= (int)$faculte['id'] ?>" <?= (int)$saisie['id_faculte'] === (int)$faculte['id'] ? 'selected' : '' ?>>
              <?= e($faculte['sigle'] . ' — ' . $faculte['nom_faculte']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!$facultes): ?>
          <div class="aide">Aucune faculté n'est disponible pour le moment.</div>
        <?php endif; ?>
      </div>

      <div class="champ" id="bloc-partenaire" hidden>
        <label for="nom_structure">Nom de la structure</label>
        <input type="text" id="nom_structure" name="nom_structure" value="<?= e($saisie['nom_structure']) ?>"
               placeholder="Mairie de Kara, Agro-Togo SARL, ONG…">
      </div>

      <div class="deux-colonnes">
        <div class="champ">
          <label for="mot_de_passe">Mot de passe</label>
          <input type="password" id="mot_de_passe" name="mot_de_passe" required>
          <div class="aide">8 caractères au minimum.</div>
        </div>
        <div class="champ">
          <label for="confirmation">Confirmer le mot de passe</label>
          <input type="password" id="confirmation" name="confirmation" required>
        </div>
      </div>

      <div class="ligne-boutons">
        <button type="submit" class="bouton">Créer mon compte <?= icone('fleche', 17) ?></button>
        <a class="petit discret" href="<?= lien('connexion.php') ?>">J'ai déjà un compte</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/pied.php'; ?>
