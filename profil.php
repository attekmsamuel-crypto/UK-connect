<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$moi = exiger_connexion();
$pdo = connexion_bdd();
$facultes = liste_facultes($pdo);
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profil') {
        $photo = $moi['photo'];
        $banniere = $moi['banniere'];

        try {
            $nouvellePhoto = televerser_image('photo', 'avatars', 'photo');
            if ($nouvellePhoto) {
                $photo = $nouvellePhoto;
            }
            $nouvelleBanniere = televerser_image('banniere', 'bannieres', 'banniere');
            if ($nouvelleBanniere) {
                $banniere = $nouvelleBanniere;
            }
        } catch (RuntimeException $erreur) {
            $erreurs[] = $erreur->getMessage();
        }

        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');

        if ($nom === '' || $prenom === '') {
            $erreurs[] = 'Le nom et le prénom sont obligatoires.';
        }
        if ($moi['role'] === 'partenaire' && trim($_POST['nom_structure'] ?? '') === '') {
            $erreurs[] = 'Le nom de la structure est obligatoire.';
        }

        if (!$erreurs) {
            mettre_a_jour_profil($pdo, (int)$moi['id'], [
                'nom'           => $nom,
                'prenom'        => $prenom,
                'telephone'     => trim($_POST['telephone'] ?? '') ?: null,
                'bio'           => trim($_POST['bio'] ?? '') ?: null,
                'niveau_etudes' => trim($_POST['niveau_etudes'] ?? '') ?: null,
                'nom_structure' => $moi['role'] === 'partenaire' ? trim($_POST['nom_structure'] ?? '') : $moi['nom_structure'],
                'id_faculte'    => $moi['role'] === 'etudiant' && (int)($_POST['id_faculte'] ?? 0) > 0
                                    ? (int)$_POST['id_faculte'] : ($moi['role'] === 'etudiant' ? $moi['id_faculte'] : null),
                'photo'         => $photo,
                'banniere'      => $banniere,
            ]);
            message('succes', 'Votre profil est à jour.');
            rediriger(lien('profil.php'));
        }
    }

    if ($action === 'mot_de_passe') {
        $actuel = $_POST['actuel'] ?? '';
        $nouveau = $_POST['nouveau'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        if (!password_verify($actuel, $moi['mot_de_passe'])) {
            $erreurs[] = 'Le mot de passe actuel est incorrect.';
        } elseif (strlen($nouveau) < 8) {
            $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($nouveau !== $confirmation) {
            $erreurs[] = 'Les deux nouveaux mots de passe ne sont pas identiques.';
        } else {
            changer_mot_de_passe($pdo, (int)$moi['id'], $nouveau);
            message('succes', 'Votre mot de passe a été changé.');
            rediriger(lien('profil.php'));
        }
    }

    if ($action === 'formation' && $moi['role'] === 'etudiant') {
        ajouter_formation($pdo, [
            'id_etudiant'   => (int)$moi['id'],
            'diplome'       => trim($_POST['diplome'] ?? ''),
            'etablissement' => trim($_POST['etablissement'] ?? ''),
            'ville'         => trim($_POST['ville'] ?? '') ?: null,
            'annee_debut'   => (int)($_POST['annee_debut'] ?? 0) ?: null,
            'annee_fin'     => (int)($_POST['annee_fin'] ?? 0) ?: null,
            'mention'       => trim($_POST['mention'] ?? '') ?: null,
            'description'   => trim($_POST['description'] ?? '') ?: null,
        ]);
        message('succes', 'Formation ajoutée à votre profil.');
        rediriger(lien('profil.php#cv'));
    }

    if ($action === 'experience' && $moi['role'] === 'etudiant') {
        ajouter_experience($pdo, [
            'id_etudiant'  => (int)$moi['id'],
            'poste'        => trim($_POST['poste'] ?? ''),
            'organisation' => trim($_POST['organisation'] ?? ''),
            'lieu'         => trim($_POST['lieu'] ?? '') ?: null,
            'periode'      => trim($_POST['periode'] ?? '') ?: null,
            'description'  => trim($_POST['description'] ?? '') ?: null,
        ]);
        message('succes', 'Expérience ajoutée à votre profil.');
        rediriger(lien('profil.php#cv'));
    }

    if ($action === 'competence' && $moi['role'] === 'etudiant') {
        $nomCompetence = trim($_POST['competence'] ?? '');
        if ($nomCompetence !== '') {
            ajouter_competence($pdo, (int)$moi['id'], $nomCompetence, trim($_POST['categorie'] ?? 'Outil'));
        }
        rediriger(lien('profil.php#cv'));
    }

    if ($action === 'supprimer_cv' && $moi['role'] === 'etudiant') {
        supprimer_ligne_cv($pdo, $_POST['table'] ?? '', (int)($_POST['id'] ?? 0), (int)$moi['id']);
        rediriger(lien('profil.php#cv'));
    }

    if ($action === 'supprimer_projet' && $moi['role'] === 'etudiant') {
        supprimer_projet($pdo, (int)($_POST['id'] ?? 0), (int)$moi['id']);
        message('succes', 'Le projet a été retiré du portfolio.');
        rediriger(lien('profil.php#projets'));
    }
}

$formations = $moi['role'] === 'etudiant' ? formations_de($pdo, (int)$moi['id']) : [];
$experiences = $moi['role'] === 'etudiant' ? experiences_de($pdo, (int)$moi['id']) : [];
$competences = $moi['role'] === 'etudiant' ? competences_de($pdo, (int)$moi['id']) : [];
$mesProjets = $moi['role'] === 'etudiant' ? projets_de_etudiant($pdo, (int)$moi['id']) : [];

$titrePage = 'Mon profil';
require __DIR__ . '/includes/entete.php';
?>

<div class="titre-page">
  <h1>Mon profil</h1>
  <p>
    <?php if ($moi['role'] === 'etudiant'): ?>
      Ces informations composent la page que consultent les partenaires —
      <a href="<?= lien('etudiant.php?id=' . (int)$moi['id']) ?>">voir mon profil public</a>.
    <?php else: ?>
      Les coordonnées affichées aux étudiants sur vos besoins publiés.
    <?php endif; ?>
  </p>
</div>

<?php if ($erreurs): ?>
  <div class="message message-erreur">
    <?= icone('croix', 19) ?>
    <div>
      <?php foreach ($erreurs as $erreur): ?><div><?= e($erreur) ?></div><?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="detail">
  <div>
    <div class="panneau">
      <h2>Informations</h2>
      <form method="post" enctype="multipart/form-data">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="profil">

        <div class="deux-colonnes">
          <div class="champ">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?= e($moi['prenom']) ?>" required>
          </div>
          <div class="champ">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" value="<?= e($moi['nom']) ?>" required>
          </div>
        </div>

        <div class="deux-colonnes">
          <div class="champ">
            <label>Adresse email</label>
            <input type="text" value="<?= e($moi['email']) ?>" disabled>
            <div class="aide">L'adresse de connexion ne peut pas être modifiée ici.</div>
          </div>
          <div class="champ">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone" value="<?= e($moi['telephone']) ?>">
          </div>
        </div>

        <?php if ($moi['role'] === 'etudiant'): ?>
          <div class="deux-colonnes">
            <div class="champ">
              <label for="id_faculte">Faculté ou institut</label>
              <select id="id_faculte" name="id_faculte">
                <?php foreach ($facultes as $faculte): ?>
                  <option value="<?= (int)$faculte['id'] ?>" <?= (int)$moi['id_faculte'] === (int)$faculte['id'] ? 'selected' : '' ?>>
                    <?= e($faculte['sigle'] . ' — ' . $faculte['nom_faculte']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="champ">
              <label for="niveau_etudes">Niveau d'études</label>
              <input type="text" id="niveau_etudes" name="niveau_etudes" value="<?= e($moi['niveau_etudes']) ?>"
                     placeholder="Licence 3 — Génie logiciel">
            </div>
          </div>
        <?php elseif ($moi['role'] === 'partenaire'): ?>
          <div class="champ">
            <label for="nom_structure">Nom de la structure</label>
            <input type="text" id="nom_structure" name="nom_structure" value="<?= e($moi['nom_structure']) ?>" required>
          </div>
        <?php endif; ?>

        <div class="champ">
          <label for="bio">Présentation</label>
          <textarea id="bio" name="bio" style="min-height:100px;"><?= e($moi['bio']) ?></textarea>
          <div class="aide">Quelques lignes sur votre parcours ou votre structure.</div>
        </div>

        <?php if ($moi['role'] === 'etudiant'): ?>
          <div class="deux-colonnes">
            <div class="champ">
              <label for="photo">Photo de profil</label>
              <input type="file" id="photo" name="photo" accept="image/*">
            </div>
            <div class="champ">
              <label for="banniere">Image de couverture</label>
              <input type="file" id="banniere" name="banniere" accept="image/*">
            </div>
          </div>
        <?php endif; ?>

        <button type="submit" class="bouton">Enregistrer</button>
      </form>
    </div>

    <?php if ($moi['role'] === 'etudiant'): ?>
      <div class="panneau" id="projets">
        <h2>Mes projets publiés</h2>
        <?php if (!$mesProjets): ?>
          <p class="discret">Vous n'avez encore rien publié.
            <a href="<?= lien('publier-projet.php') ?>">Publier un premier projet</a>.</p>
        <?php else: ?>
          <div class="tableau">
            <table>
              <tbody>
                <?php foreach ($mesProjets as $p): ?>
                  <tr>
                    <td>
                      <a href="<?= lien('projet.php?id=' . (int)$p['id']) ?>"><?= e($p['titre']) ?></a>
                      <div class="petit discret"><?= date_courte($p['date_publication']) ?></div>
                    </td>
                    <td style="width:1%;">
                      <form method="post">
                        <?= champ_csrf() ?>
                        <input type="hidden" name="action" value="supprimer_projet">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="bouton bouton-rouge bouton-petit"
                                data-confirmer="Retirer ce projet du portfolio ?">Retirer</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="panneau" id="cv">
        <h2>Expériences</h2>
        <?php foreach ($experiences as $x): ?>
          <div class="cv-ligne">
            <b><?= e($x['poste']) ?></b>
            <span><?= e($x['organisation']) ?><?= $x['periode'] ? ' · ' . e($x['periode']) : '' ?></span>
            <form method="post" style="margin-top:6px;">
              <?= champ_csrf() ?>
              <input type="hidden" name="action" value="supprimer_cv">
              <input type="hidden" name="table" value="experiences">
              <input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
              <button type="submit" class="bouton bouton-rouge bouton-petit" data-confirmer="Supprimer cette expérience ?">Supprimer</button>
            </form>
          </div>
        <?php endforeach; ?>

        <form method="post" style="margin-top:18px;">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" value="experience">
          <div class="deux-colonnes">
            <div class="champ">
              <label for="poste">Poste ou stage</label>
              <input type="text" id="poste" name="poste" required>
            </div>
            <div class="champ">
              <label for="organisation">Organisation</label>
              <input type="text" id="organisation" name="organisation" required>
            </div>
          </div>
          <div class="deux-colonnes">
            <div class="champ">
              <label for="lieu">Lieu</label>
              <input type="text" id="lieu" name="lieu">
            </div>
            <div class="champ">
              <label for="periode">Période</label>
              <input type="text" id="periode" name="periode" placeholder="juin — août 2025">
            </div>
          </div>
          <div class="champ">
            <label for="description-exp">Missions réalisées</label>
            <textarea id="description-exp" name="description" style="min-height:80px;"></textarea>
          </div>
          <button type="submit" class="bouton bouton-secondaire">Ajouter cette expérience</button>
        </form>
      </div>

      <div class="panneau">
        <h2>Formation</h2>
        <?php foreach ($formations as $f): ?>
          <div class="cv-ligne">
            <b><?= e($f['diplome']) ?></b>
            <span><?= e($f['etablissement']) ?><?= $f['annee_debut'] ? ' · ' . (int)$f['annee_debut'] . ' — ' . ($f['annee_fin'] ? (int)$f['annee_fin'] : 'en cours') : '' ?></span>
            <form method="post" style="margin-top:6px;">
              <?= champ_csrf() ?>
              <input type="hidden" name="action" value="supprimer_cv">
              <input type="hidden" name="table" value="formations">
              <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
              <button type="submit" class="bouton bouton-rouge bouton-petit" data-confirmer="Supprimer cette formation ?">Supprimer</button>
            </form>
          </div>
        <?php endforeach; ?>

        <form method="post" style="margin-top:18px;">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" value="formation">
          <div class="deux-colonnes">
            <div class="champ">
              <label for="diplome">Diplôme</label>
              <input type="text" id="diplome" name="diplome" required>
            </div>
            <div class="champ">
              <label for="etablissement">Établissement</label>
              <input type="text" id="etablissement" name="etablissement" required>
            </div>
          </div>
          <div class="deux-colonnes">
            <div class="champ">
              <label for="annee_debut">Année de début</label>
              <input type="number" id="annee_debut" name="annee_debut" min="1980" max="2100">
            </div>
            <div class="champ">
              <label for="annee_fin">Année de fin</label>
              <input type="number" id="annee_fin" name="annee_fin" min="1980" max="2100">
              <div class="aide">Laisser vide si la formation est en cours.</div>
            </div>
          </div>
          <div class="deux-colonnes">
            <div class="champ">
              <label for="ville">Ville</label>
              <input type="text" id="ville" name="ville">
            </div>
            <div class="champ">
              <label for="mention">Mention</label>
              <input type="text" id="mention" name="mention">
            </div>
          </div>
          <button type="submit" class="bouton bouton-secondaire">Ajouter cette formation</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="panneau">
      <h2>Mot de passe</h2>
      <form method="post">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="mot_de_passe">
        <div class="champ">
          <label for="actuel">Mot de passe actuel</label>
          <input type="password" id="actuel" name="actuel" required>
        </div>
        <div class="deux-colonnes">
          <div class="champ">
            <label for="nouveau">Nouveau mot de passe</label>
            <input type="password" id="nouveau" name="nouveau" required>
          </div>
          <div class="champ">
            <label for="confirmation">Confirmer</label>
            <input type="password" id="confirmation" name="confirmation" required>
          </div>
        </div>
        <button type="submit" class="bouton bouton-secondaire">Changer le mot de passe</button>
      </form>
    </div>
  </div>

  <aside>
    <div class="encadre">
      <h4>Mon compte</h4>
      <dl>
        <dt>Type de compte</dt>
        <dd><?= e(ucfirst($moi['role'])) ?></dd>
        <dt>Inscrit depuis</dt>
        <dd><?= date_courte($moi['date_inscription']) ?></dd>
        <?php if ($moi['role'] === 'etudiant'): ?>
          <dt>Projets publiés</dt>
          <dd><?= count($mesProjets) ?></dd>
        <?php endif; ?>
      </dl>
    </div>

    <?php if ($moi['role'] === 'etudiant'): ?>
      <div class="encadre" style="margin-top:18px;">
        <h4>Compétences</h4>
        <?php if ($competences): ?>
          <ul class="puces" style="margin-bottom:14px;">
            <?php foreach ($competences as $c): ?>
              <li>
                <?= e($c['nom']) ?>
                <form method="post" style="display:inline;">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="supprimer_cv">
                  <input type="hidden" name="table" value="competences">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button type="submit" title="Retirer"
                          style="border:none;background:none;cursor:pointer;color:#9d3b30;padding:0 0 0 4px;">×</button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="petit discret">Aucune compétence renseignée.</p>
        <?php endif; ?>

        <form method="post">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" value="competence">
          <div class="champ">
            <label for="competence">Ajouter</label>
            <input type="text" id="competence" name="competence" maxlength="60" placeholder="PHP, enquête de terrain…">
          </div>
          <div class="champ">
            <label for="categorie">Catégorie</label>
            <select id="categorie" name="categorie">
              <option>Outil</option>
              <option>Langage</option>
              <option>Métier</option>
              <option>Langue</option>
              <option>Transversale</option>
            </select>
          </div>
          <button type="submit" class="bouton bouton-secondaire bouton-petit">Ajouter</button>
        </form>
      </div>
    <?php endif; ?>
  </aside>
</div>

<?php require __DIR__ . '/includes/pied.php'; ?>
