<?php
/**
 * UK-Connect — Mon profil (v2.2 : CV interactif de l'étudiant)
 * • Identité : photo de profil + bannière de couverture
 * • Parcours : niveau d'études, faculté, bio
 * • CV       : formations, stages & expériences, outils maîtrisés (ajout / retrait)
 * • Sécurité : PDO préparé, jeton CSRF, vérification de propriété sur chaque action
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$u = require_login();
$pdo = db();
csrf_verifier();

$erreurs = [];
$estEtudiant = $u['role_code'] === 'etudiant';

// ---------- Formulaire : informations personnelles & parcours ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'infos') {
    try {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $tel = trim($_POST['telephone'] ?? '') ?: null;
        $bio = trim($_POST['bio'] ?? '') ?: null;
        if ($nom === '' || $prenom === '') {
            throw new InvalidArgumentException('Nom et prénom sont obligatoires.');
        }
        $sql = 'UPDATE utilisateurs SET nom = ?, prenom = ?, telephone = ?, bio = ?';
        $params = [$nom, $prenom, $tel, $bio];
        if ($estEtudiant) {
            $sql .= ', niveau_etudes = ?';
            $params[] = trim($_POST['niveau_etudes'] ?? '') ?: null;
            if (!empty($_POST['id_faculte'])) {
                $sql .= ', id_faculte = ?';
                $params[] = (int)$_POST['id_faculte'];
            }
        }
        $sql .= ' WHERE id = ?';
        $params[] = $u['id'];
        $pdo->prepare($sql)->execute($params);
        flash('succes', 'Profil mis à jour.');
        redirect(url('profil.php'));
    } catch (InvalidArgumentException $e) {
        $erreurs[] = $e->getMessage();
    }
}

// ---------- Formulaire : bannière de couverture ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'banniere') {
    try {
        $image = televerser_image('banniere', 'bannieres', 'banniere');
        if ($image) {
            $pdo->prepare('UPDATE utilisateurs SET banniere_profil = ? WHERE id = ?')
                ->execute([$image, $u['id']]);
            flash('succes', 'Bannière de couverture mise à jour.');
        }
        redirect(url('profil.php'));
    } catch (RuntimeException $e) {
        $erreurs[] = $e->getMessage();
    }
}

// ---------- Formulaire : photo de profil ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'photo') {
    try {
        $image = televerser_image('photo');
        if ($image) {
            $pdo->prepare('UPDATE utilisateurs SET photo_profil = ? WHERE id = ?')
                ->execute([$image, $u['id']]);
            flash('succes', 'Photo de profil mise à jour.');
        }
        redirect(url('profil.php'));
    } catch (RuntimeException $e) {
        $erreurs[] = $e->getMessage();
    }
}

// ---------- Formulaire : changement de mot de passe ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'mdp') {
    try {
        $actuel = $_POST['mdp_actuel'] ?? '';
        $nouveau = $_POST['mdp_nouveau'] ?? '';
        $confirm = $_POST['mdp_confirm'] ?? '';
        if (!password_verify($actuel, $u['mot_de_passe'])) {
            throw new InvalidArgumentException('Mot de passe actuel incorrect.');
        }
        if (strlen($nouveau) < 8) {
            throw new InvalidArgumentException('Le nouveau mot de passe doit contenir au moins 8 caractères.');
        }
        if ($nouveau !== $confirm) {
            throw new InvalidArgumentException('La confirmation ne correspond pas.');
        }
        $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')
            ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $u['id']]);
        flash('succes', 'Mot de passe modifié avec succès.');
        redirect(url('profil.php'));
    } catch (InvalidArgumentException $e) {
        $erreurs[] = $e->getMessage();
    }
}

// ================= CV étudiant (v2.2) =================

// ---------- Ajout : formation ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'formation_ajout') {
    try {
        $diplome = trim($_POST['diplome'] ?? '');
        $etab = trim($_POST['etablissement'] ?? '');
        if ($diplome === '' || $etab === '') {
            throw new InvalidArgumentException('Le diplôme et l\'établissement sont obligatoires.');
        }
        $pdo->prepare(
            'INSERT INTO profil_formations (id_etudiant, diplome, etablissement, ville, annee_debut, annee_fin, mention, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                $u['id'], $diplome, $etab,
                trim($_POST['ville'] ?? '') ?: null,
                (($_POST['annee_debut'] ?? '') !== '') ? (int)$_POST['annee_debut'] : null,
                (($_POST['annee_fin'] ?? '') !== '') ? (int)$_POST['annee_fin'] : null,
                trim($_POST['mention'] ?? '') ?: null,
                trim($_POST['description'] ?? '') ?: null,
            ]);
        flash('succes', 'Formation ajoutée à votre CV.');
        redirect(url('profil.php'));
    } catch (InvalidArgumentException $e) {
        $erreurs[] = $e->getMessage();
    }
}

// ---------- Retrait : formation ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'formation_suppr') {
    $pdo->prepare('DELETE FROM profil_formations WHERE id = ? AND id_etudiant = ?')
        ->execute([(int)$_POST['id'], $u['id']]);
    flash('succes', 'Formation retirée de votre CV.');
    redirect(url('profil.php'));
}

// ---------- Ajout : expérience / stage ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'experience_ajout') {
    try {
        $poste = trim($_POST['poste'] ?? '');
        $orga = trim($_POST['organisation'] ?? '');
        if ($poste === '' || $orga === '') {
            throw new InvalidArgumentException('Le poste et la structure sont obligatoires.');
        }
        $pdo->prepare(
            'INSERT INTO profil_experiences (id_etudiant, poste, organisation, lieu, periode, en_cours, description)
             VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                $u['id'], $poste, $orga,
                trim($_POST['lieu'] ?? '') ?: null,
                trim($_POST['periode'] ?? '') ?: null,
                isset($_POST['en_cours']) ? 1 : 0,
                trim($_POST['description'] ?? '') ?: null,
            ]);
        flash('succes', 'Expérience ajoutée à votre CV.');
        redirect(url('profil.php'));
    } catch (InvalidArgumentException $e) {
        $erreurs[] = $e->getMessage();
    }
}

// ---------- Retrait : expérience ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'experience_suppr') {
    $pdo->prepare('DELETE FROM profil_experiences WHERE id = ? AND id_etudiant = ?')
        ->execute([(int)$_POST['id'], $u['id']]);
    flash('succes', 'Expérience retirée de votre CV.');
    redirect(url('profil.php'));
}

// ---------- Ajout : outil / compétence ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'competence_ajout') {
    $nom = mb_substr(trim($_POST['nom'] ?? ''), 0, 60);
    $cat = in_array($_POST['categorie'] ?? '', ['Langage', 'Outil', 'Langue', 'Compétence métier', 'Compétence transversale'], true)
        ? $_POST['categorie'] : 'Outil';
    if ($nom !== '') {
        try {
            $pdo->prepare('INSERT INTO profil_competences (id_etudiant, nom, categorie) VALUES (?, ?, ?)')
                ->execute([$u['id'], $nom, $cat]);
            flash('succes', '« ' . $nom . ' » ajouté à vos outils.');
        } catch (PDOException $e) {
            flash('erreur', 'Cet outil figure déjà dans votre CV.');
        }
    }
    redirect(url('profil.php'));
}

// ---------- Retrait : outil / compétence ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'competence_suppr') {
    $pdo->prepare('DELETE FROM profil_competences WHERE id = ? AND id_etudiant = ?')
        ->execute([(int)$_POST['id'], $u['id']]);
    flash('succes', 'Compétence retirée.');
    redirect(url('profil.php'));
}

// ---------- Relecture des données ----------
$st = $pdo->prepare('SELECT u.*, f.sigle AS faculte_sigle FROM utilisateurs u
                     LEFT JOIN facultes f ON f.id = u.id_faculte WHERE u.id = ?');
$st->execute([$u['id']]);
$u = $st->fetch();
$facultes = $pdo->query('SELECT id, sigle, nom_faculte FROM facultes ORDER BY sigle')->fetchAll();

$formations = $experiences = $competences = [];
$cvDispo = true;
if ($estEtudiant) {
    try {
        $st = $pdo->prepare('SELECT * FROM profil_formations WHERE id_etudiant = ? ORDER BY annee_debut DESC, id DESC');
        $st->execute([$u['id']]);
        $formations = $st->fetchAll();
        $st = $pdo->prepare('SELECT * FROM profil_experiences WHERE id_etudiant = ? ORDER BY id DESC');
        $st->execute([$u['id']]);
        $experiences = $st->fetchAll();
        $st = $pdo->prepare('SELECT * FROM profil_competences WHERE id_etudiant = ? ORDER BY categorie, nom');
        $st->execute([$u['id']]);
        $competences = $st->fetchAll();
    } catch (PDOException $e) {
        $cvDispo = false;
    }
}

$iconesCompetences = [
    'Langage' => 'fa-code', 'Outil' => 'fa-screwdriver-wrench', 'Langue' => 'fa-language',
    'Compétence métier' => 'fa-briefcase', 'Compétence transversale' => 'fa-people-group',
];
$niveaux = ['Licence 1', 'Licence 2', 'Licence 3', 'Master 1', 'Master 2', 'Doctorat',
            'Diplôme d\'ingénieur', 'DUT / BTS', 'Autre'];

$titre = 'Mon profil';
require __DIR__ . '/includes/header.php';
?>

<div class="page-titre" style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap;">
  <div>
    <h1><i class="fa-solid fa-user"></i> Mon profil</h1>
    <p><?= $estEtudiant
        ? 'Votre CV en ligne : il s\'affiche automatiquement aux partenaires qui consultent vos candidatures.'
        : 'Gérez vos informations personnelles.' ?></p>
  </div>
  <?php if ($estEtudiant): ?>
    <a class="btn btn-vert" target="_blank" href="<?= url('etudiant.php?id=' . (int)$u['id']) ?>">
      <i class="fa-solid fa-eye"></i> Voir comme un recruteur</a>
  <?php endif; ?>
</div>

<?php foreach ($erreurs as $err): ?>
  <div class="alerte alerte-rouge"><?= e($err) ?></div>
<?php endforeach; ?>
<?php if (!$cvDispo): ?>
  <div class="alerte alerte-rouge">
    <i class="fa-solid fa-database"></i>
    <b>Base de données à mettre à jour :</b> les tables du CV (v2.2) sont absentes.
    Ré-importez <code>database/ukconnect_v2.sql</code> dans phpMyAdmin pour activer
    formations, expériences et outils.
  </div>
<?php endif; ?>

<?php if ($estEtudiant): ?>
<!-- ============ 1. CARTE D'IDENTITÉ : bannière + avatar ============ -->
<div class="panneau carte-identite">
  <div class="ci-banniere <?= $u['banniere_profil'] ? 'avec-image' : '' ?>"
       <?= $u['banniere_profil']
           ? 'style="background-image:url(' . url('assets/uploads/bannieres/' . rawurlencode($u['banniere_profil'])) . ')"'
           : '' ?>></div>
  <div class="ci-corps">
    <?php if ($u['photo_profil']): ?>
      <img class="ci-avatar" src="<?= url('assets/uploads/avatars/' . rawurlencode($u['photo_profil'])) ?>" alt="Ma photo">
    <?php else: ?>
      <div class="ci-avatar ci-avatar-initiales"><?= e(mb_strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1))) ?></div>
    <?php endif; ?>
    <div style="flex:1;">
      <h2 style="font-size:20px;"><?= e($u['prenom'] . ' ' . $u['nom']) ?></h2>
      <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:5px;">
        <?php if ($u['niveau_etudes']): ?><span class="etiquette etiquette-vert"><i class="fa-solid fa-layer-group"></i> <?= e($u['niveau_etudes']) ?></span><?php endif; ?>
        <?php if ($u['faculte_sigle']): ?><span class="etiquette etiquette-or"><?= e($u['faculte_sigle']) ?></span><?php endif; ?>
        <span class="etiquette"><?= count($formations) ?> formation<?= count($formations) > 1 ? 's' : '' ?></span>
        <span class="etiquette"><?= count($experiences) ?> expérience<?= count($experiences) > 1 ? 's' : '' ?></span>
        <span class="etiquette"><?= count($competences) ?> outil<?= count($competences) > 1 ? 's' : '' ?></span>
      </div>
    </div>
  </div>
  <div class="ci-formulaires">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="section" value="photo">
      <div class="champ"><label><i class="fa-solid fa-camera"></i> Photo de profil</label>
        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
        <div class="aide">JPG, PNG ou WebP — 2 Mo max. Si aucune photo : vos initiales s'affichent.</div></div>
      <button class="btn btn-bleu btn-petit">Changer ma photo</button>
    </form>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="section" value="banniere">
      <div class="champ"><label><i class="fa-solid fa-panorama"></i> Bannière de couverture</label>
        <input type="file" name="banniere" accept=".jpg,.jpeg,.png,.webp">
        <div class="aide">Grande image en haut de votre CV public — 2 Mo max.</div></div>
      <button class="btn btn-outline btn-petit">Changer ma bannière</button>
    </form>
  </div>
</div>

<div class="deux-colonnes" style="align-items:start; margin-top:18px;">
  <div>
    <!-- ============ 2. PARCOURS UNIVERSITAIRE ============ -->
    <div class="panneau" style="margin-bottom:18px;">
      <h2 class="cv-titre-panneau"><i class="fa-solid fa-graduation-cap"></i> Parcours universitaire &amp; présentation</h2>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="section" value="infos">
        <div class="deux-colonnes">
          <div class="champ"><label>Nom *</label>
            <input type="text" name="nom" required value="<?= e($u['nom']) ?>"></div>
          <div class="champ"><label>Prénom *</label>
            <input type="text" name="prenom" required value="<?= e($u['prenom']) ?>"></div>
        </div>
        <div class="deux-colonnes">
          <div class="champ"><label>Niveau d''études actuel</label>
            <select name="niveau_etudes">
              <option value="">— Choisir —</option>
              <?php foreach ($niveaux as $n): ?>
                <option value="<?= e($n) ?>" <?= $u['niveau_etudes'] === $n ? 'selected' : '' ?>><?= e($n) ?></option>
              <?php endforeach; ?>
              <?php if ($u['niveau_etudes'] && !in_array($u['niveau_etudes'], $niveaux, true)): ?>
                <option value="<?= e($u['niveau_etudes']) ?>" selected><?= e($u['niveau_etudes']) ?></option>
              <?php endif; ?>
            </select>
            <div class="aide">Ex. : Licence 3, Master 1… Affiché sur votre CV public.</div></div>
          <div class="champ"><label>Faculté / Institut</label>
            <select name="id_faculte">
              <?php foreach ($facultes as $f): ?>
                <option value="<?= (int)$f['id'] ?>" <?= (int)$u['id_faculte'] === (int)$f['id'] ? 'selected' : '' ?>>
                  <?= e($f['sigle'] . ' — ' . $f['nom_faculte']) ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
        <div class="champ"><label>Téléphone</label>
          <input type="tel" name="telephone" value="<?= e($u['telephone'] ?? '') ?>"></div>
        <div class="champ"><label>Bio / présentation</label>
          <textarea name="bio" placeholder="Quelques lignes qui vous présentent : votre projet professionnel, ce qui vous distingue…"><?= e($u['bio'] ?? '') ?></textarea></div>
        <button class="btn btn-bleu"><i class="fa-solid fa-floppy-disk"></i> Enregistrer mon parcours</button>
      </form>
    </div>

    <!-- ============ 4. MOT DE PASSE ============ -->
    <div class="panneau">
      <h2 class="cv-titre-panneau"><i class="fa-solid fa-lock"></i> Changer mon mot de passe</h2>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="section" value="mdp">
        <div class="champ"><label>Mot de passe actuel *</label>
          <input type="password" name="mdp_actuel" required></div>
        <div class="deux-colonnes">
          <div class="champ"><label>Nouveau *(8 car. min.)</label>
            <input type="password" name="mdp_nouveau" required minlength="8"></div>
          <div class="champ"><label>Confirmer *</label>
            <input type="password" name="mdp_confirm" required minlength="8"></div>
        </div>
        <button class="btn btn-outline"><i class="fa-solid fa-key"></i> Modifier le mot de passe</button>
      </form>
    </div>
  </div>

  <div>
    <!-- ============ 3.A MES FORMATIONS ============ -->
    <div class="panneau" style="margin-bottom:18px;">
      <h2 class="cv-titre-panneau"><i class="fa-solid fa-scroll"></i> Mes formations</h2>
      <?php if (!$formations): ?>
        <p class="muted" style="margin-bottom:10px;">Aucune formation pour l'instant — ajoutez votre parcours ci-dessous.</p>
      <?php else: ?>
        <?php foreach ($formations as $f): ?>
        <div class="ligne-cv">
          <div>
            <b><?= e($f['diplome']) ?></b>
            <?php if ($f['annee_debut'] || $f['annee_fin']): ?>
              <span class="periode"><?= (int)$f['annee_debut'] ?> — <?= $f['annee_fin'] ? (int)$f['annee_fin'] : 'en cours' ?></span>
            <?php endif; ?>
            <?php if ($f['mention']): ?> <span class="etiquette etiquette-or"><?= e($f['mention']) ?></span><?php endif; ?>
            <div class="muted" style="font-size:13px;"><?= e($f['etablissement']) ?><?= $f['ville'] ? ' · ' . e($f['ville']) : '' ?></div>
            <?php if ($f['description']): ?><p style="font-size:13.5px; margin-top:4px;"><?= e($f['description']) ?></p><?php endif; ?>
          </div>
          <form method="post" onsubmit="return confirm('Retirer cette formation de votre CV ?');">
            <?= csrf_field() ?><input type="hidden" name="section" value="formation_suppr">
            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
            <button class="btn-suppr" title="Retirer"><i class="fa-solid fa-xmark"></i></button>
          </form>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <form method="post" style="border-top:1px dashed var(--cadre); margin-top:12px; padding-top:14px;">
        <?= csrf_field() ?><input type="hidden" name="section" value="formation_ajout">
        <div class="deux-colonnes">
          <div class="champ"><label>Diplôme / formation *</label>
            <input type="text" name="diplome" required placeholder="Ex. : Licence en Génie Logiciel"></div>
          <div class="champ"><label>Établissement *</label>
            <input type="text" name="etablissement" required placeholder="Ex. : FAST — Université de Kara"></div>
        </div>
        <div class="grille-form">
          <div class="champ"><label>Ville</label><input type="text" name="ville" placeholder="Kara"></div>
          <div class="champ"><label>Année de début</label><input type="number" name="annee_debut" min="1990" max="2100" placeholder="2023"></div>
          <div class="champ"><label>Année de fin</label><input type="number" name="annee_fin" min="1990" max="2100" placeholder="2026 (vide = en cours)"></div>
          <div class="champ"><label>Mention</label><input type="text" name="mention" placeholder="Bien, Excellent…"></div>
        </div>
        <div class="champ"><label>Détails</label>
          <textarea name="description" style="min-height:70px;" placeholder="Spécialisation, cours clés, mémoire en cours…"></textarea></div>
        <button class="btn btn-vert btn-petit"><i class="fa-solid fa-plus"></i> Ajouter cette formation</button>
      </form>
    </div>

    <!-- ============ 3.B MES EXPÉRIENCES & STAGES ============ -->
    <div class="panneau" style="margin-bottom:18px;">
      <h2 class="cv-titre-panneau"><i class="fa-solid fa-briefcase"></i> Mes expériences &amp; stages</h2>
      <?php if (!$experiences): ?>
        <p class="muted" style="margin-bottom:10px;">Aucune expérience pour l'instant — stage, bénévolat, emploi : tout compte !</p>
      <?php else: ?>
        <?php foreach ($experiences as $x): ?>
        <div class="ligne-cv">
          <div>
            <b><?= e($x['poste']) ?></b>
            <?php if ($x['periode']): ?><span class="periode"><?= e($x['periode']) ?></span><?php endif; ?>
            <?php if ($x['en_cours']): ?><span class="etiquette etiquette-vert">Aujourd'hui</span><?php endif; ?>
            <div class="muted" style="font-size:13px;"><?= e($x['organisation']) ?><?= $x['lieu'] ? ' · ' . e($x['lieu']) : '' ?></div>
            <?php if ($x['description']): ?><p style="font-size:13.5px; margin-top:4px;"><?= e($x['description']) ?></p><?php endif; ?>
          </div>
          <form method="post" onsubmit="return confirm('Retirer cette expérience de votre CV ?');">
            <?= csrf_field() ?><input type="hidden" name="section" value="experience_suppr">
            <input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
            <button class="btn-suppr" title="Retirer"><i class="fa-solid fa-xmark"></i></button>
          </form>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <form method="post" style="border-top:1px dashed var(--cadre); margin-top:12px; padding-top:14px;">
        <?= csrf_field() ?><input type="hidden" name="section" value="experience_ajout">
        <div class="deux-colonnes">
          <div class="champ"><label>Poste / stage *</label>
            <input type="text" name="poste" required placeholder="Ex. : Stagiaire développeur web"></div>
          <div class="champ"><label>Structure *</label>
            <input type="text" name="organisation" required placeholder="Entreprise, ONG, ministère…"></div>
        </div>
        <div class="grille-form">
          <div class="champ"><label>Lieu</label><input type="text" name="lieu" placeholder="Lomé"></div>
          <div class="champ"><label>Période</label><input type="text" name="periode" placeholder="juin — août 2025"></div>
        </div>
        <div class="champ" style="margin-bottom:8px;">
          <label style="font-weight:600; display:inline-flex; align-items:center; gap:8px;">
            <input type="checkbox" name="en_cours" style="width:auto;"> Cette expérience est toujours en cours
          </label></div>
        <div class="champ"><label>Missions &amp; réalisations</label>
          <textarea name="description" style="min-height:70px;" placeholder="Ce que vous avez fait, les résultats obtenus…"></textarea></div>
        <button class="btn btn-vert btn-petit"><i class="fa-solid fa-plus"></i> Ajouter cette expérience</button>
      </form>
    </div>

    <!-- ============ 3.C MES OUTILS & COMPÉTENCES ============ -->
    <div class="panneau">
      <h2 class="cv-titre-panneau"><i class="fa-solid fa-screwdriver-wrench"></i> Mes outils &amp; compétences</h2>
      <?php if ($competences): ?>
        <div class="liste-competences" style="margin-bottom:14px;">
          <?php foreach ($competences as $c): ?>
            <span class="puce">
              <i class="fa-solid <?= $iconesCompetences[$c['categorie']] ?? 'fa-check' ?>"></i> <?= e($c['nom']) ?>
              <form method="post">
                <?= csrf_field() ?><input type="hidden" name="section" value="competence_suppr">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="puce-suppr" title="Retirer <?= e($c['nom']) ?>"><i class="fa-solid fa-xmark"></i></button>
              </form>
            </span>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="muted" style="margin-bottom:14px;">Listez ce que vous savez faire : langages, logiciels, langues, qualités métier…</p>
      <?php endif; ?>
      <form method="post" style="border-top:1px dashed var(--cadre); padding-top:14px;">
        <?= csrf_field() ?><input type="hidden" name="section" value="competence_ajout">
        <div class="champ"><label>Outil / compétence *</label>
          <input type="text" name="nom" required maxlength="60" placeholder="Ex. : PHP, Excel, KoboToolbox, Anglais…"></div>
        <div class="champ"><label>Catégorie</label>
          <select name="categorie">
            <option>Outil</option><option>Langage</option><option>Langue</option>
            <option>Compétence métier</option><option>Compétence transversale</option>
          </select></div>
        <button class="btn btn-vert btn-petit"><i class="fa-solid fa-plus"></i> Ajouter</button>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ======= Version partenaire / administrateur (formulaire simple) ======= -->
<div class="deux-colonnes" style="align-items:start;">
  <div class="panneau" style="text-align:center;">
    <?php if ($u['photo_profil']): ?>
      <img src="<?= url('assets/uploads/avatars/' . rawurlencode($u['photo_profil'])) ?>"
           alt="Ma photo" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid var(--or);">
    <?php else: ?>
      <div style="width:120px; height:120px; border-radius:50%; background:var(--bleu-clair); color:var(--bleu);
                  display:grid; place-items:center; font-size:40px; font-weight:800; margin:0 auto;">
        <?= e(mb_strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1))) ?>
      </div>
    <?php endif; ?>
    <h3 style="margin:12px 0 2px;"><?= e($u['prenom'] . ' ' . $u['nom']) ?></h3>
    <p class="muted"><span class="etiquette"><?= e($u['role_code']) ?></span>
      <?php if ($u['nom_structure']): ?><br><?= e($u['nom_structure']) ?><?php endif; ?></p>
  </div>
  <div>
    <div class="panneau" style="margin-bottom:18px;">
      <h2 class="cv-titre-panneau"><i class="fa-solid fa-pen"></i> Informations personnelles</h2>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="section" value="infos">
        <div class="deux-colonnes">
          <div class="champ"><label>Nom *</label><input type="text" name="nom" required value="<?= e($u['nom']) ?>"></div>
          <div class="champ"><label>Prénom *</label><input type="text" name="prenom" required value="<?= e($u['prenom']) ?>"></div>
        </div>
        <div class="deux-colonnes">
          <div class="champ"><label>Email (non modifiable)</label>
            <input type="email" value="<?= e($u['email']) ?>" disabled style="background:#f1f5f9;"></div>
          <div class="champ"><label>Téléphone</label>
            <input type="tel" name="telephone" value="<?= e($u['telephone'] ?? '') ?>"></div>
        </div>
        <div class="champ"><label>Bio / présentation</label>
          <textarea name="bio"><?= e($u['bio'] ?? '') ?></textarea></div>
        <button class="btn btn-bleu"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
      </form>
    </div>
    <div class="panneau">
      <h2 class="cv-titre-panneau"><i class="fa-solid fa-lock"></i> Changer mon mot de passe</h2>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="section" value="mdp">
        <div class="champ"><label>Mot de passe actuel *</label><input type="password" name="mdp_actuel" required></div>
        <div class="deux-colonnes">
          <div class="champ"><label>Nouveau *(8 car. min.)</label><input type="password" name="mdp_nouveau" required minlength="8"></div>
          <div class="champ"><label>Confirmer *</label><input type="password" name="mdp_confirm" required minlength="8"></div>
        </div>
        <button class="btn btn-outline"><i class="fa-solid fa-key"></i> Modifier le mot de passe</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
