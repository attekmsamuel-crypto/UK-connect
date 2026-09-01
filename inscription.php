<?php
/**
 * UK-Connect — Inscription (étudiant ou partenaire)
 * Les administrateurs ne se créent pas par ce formulaire :
 * le compte admin est créé par l'installation (script SQL).
 */
require_once __DIR__ . '/includes/auth.php';
session_init();

if (est_connecte()) {
    redirect(url('index.php'));
}
csrf_verifier();

$facultes = db()->query('SELECT id, sigle, nom_faculte FROM facultes ORDER BY sigle')->fetchAll();

$erreurs = [];
$valeurs = [
    'role' => 'etudiant', 'nom' => '', 'prenom' => '', 'email' => '',
    'telephone' => '', 'id_faculte' => '', 'nom_structure' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($valeurs as $k => $defaut) {
        $valeurs[$k] = trim($_POST[$k] ?? $defaut);
    }
    try {
        if ($valeurs['nom'] === '' || $valeurs['prenom'] === '') {
            throw new InvalidArgumentException('Nom et prénom sont obligatoires.');
        }
        $id = register([
            'role'         => $valeurs['role'],
            'nom'          => $valeurs['nom'],
            'prenom'       => $valeurs['prenom'],
            'email'        => $valeurs['email'],
            'telephone'    => $valeurs['telephone'],
            'id_faculte'   => $valeurs['id_faculte'],
            'nom_structure'=> $valeurs['nom_structure'],
            'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
            'confirmation' => $_POST['confirmation'] ?? '',
        ]);
        // Connexion automatique après l'inscription
        $u = db()->query('SELECT email FROM utilisateurs WHERE id = ' . $id)->fetch();
        login($u['email'], $_POST['mot_de_passe']);
        flash('succes', 'Votre compte a été créé avec succès. Bienvenue sur UK-Connect !');
        redirect(url('index.php'));
    } catch (InvalidArgumentException $e) {
        $erreurs[] = $e->getMessage();
    } catch (RuntimeException $e) {
        $erreurs[] = $e->getMessage();
    } catch (PDOException $e) {
        $erreurs[] = 'Une erreur technique est survenue. Réessayez.';
    }
}

$titre = 'Inscription';
require __DIR__ . '/includes/header.php';
?>

<div class="panneau panneau-moyenne" style="margin: 20px auto;">
  <div class="titre-panneau">
    <h1>Créer un compte</h1>
    <p>Étudiant de l'UK ? Partenaire (entreprise, ONG, collectivité) ? Rejoignez la plateforme.</p>
  </div>

  <?php foreach ($erreurs as $err): ?>
    <div class="alerte alerte-rouge"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post">
    <?= csrf_field() ?>

    <div class="champ">
      <label>Je suis…</label>
      <div class="radios">
        <label><input type="radio" name="role" value="etudiant"
            <?= $valeurs['role'] !== 'partenaire' ? 'checked' : '' ?>> <i class="fa-solid fa-graduation-cap"></i> Un étudiant de l'UK</label>
        <label><input type="radio" name="role" value="partenaire"
            <?= $valeurs['role'] === 'partenaire' ? 'checked' : '' ?>> <i class="fa-solid fa-building"></i> Une structure partenaire</label>
      </div>
    </div>

    <div class="deux-colonnes">
      <div class="champ">
        <label for="nom">Nom *</label>
        <input type="text" id="nom" name="nom" required value="<?= e($valeurs['nom']) ?>">
      </div>
      <div class="champ">
        <label for="prenom">Prénom *</label>
        <input type="text" id="prenom" name="prenom" required value="<?= e($valeurs['prenom']) ?>">
      </div>
    </div>

    <div class="deux-colonnes">
      <div class="champ">
        <label for="email">Adresse email *</label>
        <input type="email" id="email" name="email" required value="<?= e($valeurs['email']) ?>">
      </div>
      <div class="champ">
        <label for="telephone">Téléphone</label>
        <input type="tel" id="telephone" name="telephone" placeholder="+228 …"
               value="<?= e($valeurs['telephone']) ?>">
      </div>
    </div>

    <!-- Bloc spécifique aux étudiants (trigger : faculté obligatoire) -->
    <div id="bloc-etudiant">
      <div class="champ">
        <label for="id_faculte">Faculté / Institut *</label>
        <select id="id_faculte" name="id_faculte">
          <option value="">— Sélectionnez —</option>
          <?php foreach ($facultes as $f): ?>
            <option value="<?= (int)$f['id'] ?>"
              <?= (string)$valeurs['id_faculte'] === (string)$f['id'] ? 'selected' : '' ?>>
              <?= e($f['sigle'] . ' — ' . $f['nom_faculte']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Bloc spécifique aux partenaires (trigger : structure obligatoire) -->
    <div id="bloc-partenaire">
      <div class="champ">
        <label for="nom_structure">Nom de la structure *</label>
        <input type="text" id="nom_structure" name="nom_structure"
               placeholder="Ex. : Mairie de Kara, Agro-Togo SARL, ONG Espoir Jeunesse…"
               value="<?= e($valeurs['nom_structure']) ?>">
        <div class="aide">Entreprise, ONG, collectivité territoriale, projet de développement…</div>
      </div>
    </div>

    <div class="deux-colonnes">
      <div class="champ">
        <label for="mot_de_passe">Mot de passe * <span class="aide">(8 caractères min.)</span></label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8">
      </div>
      <div class="champ">
        <label for="confirmation">Confirmer le mot de passe *</label>
        <input type="password" id="confirmation" name="confirmation" required minlength="8">
      </div>
    </div>

    <button type="submit" class="btn btn-bleu btn-bloc">Créer mon compte</button>
  </form>

  <p style="margin-top:16px; text-align:center;" class="muted">
    Déjà inscrit ? <a href="<?= url('connexion.php') ?>"><b>Se connecter</b></a>
  </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
