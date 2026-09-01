<?php
/**
 * UK-Connect — Publication / modification d'un projet (étudiant)
 * Règle de gestion (fiche technique, module 1) : un étudiant ne peut
 * modifier que ses propres projets (contrôle par id_etudiant).
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$u = require_role('etudiant');
csrf_verifier();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$projet = null;

// --- Mode modification : le projet doit appartenir à l'étudiant connecté ---
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM projets_etudiants WHERE id = ? AND id_etudiant = ?');
    $st->execute([$id, $u['id']]);
    $projet = $st->fetch();
    if (!$projet) {
        flash('erreur', 'Projet introuvable ou ne vous appartenant pas.');
        redirect(url('projet-publier.php'));
    }
}

$erreurs = [];
$v = [
    'titre_projet'       => $projet['titre_projet'] ?? '',
    'departement'        => $projet['departement'] ?? '',
    'resume_executif'    => $projet['resume_executif'] ?? '',
    'technologies_outils'=> $projet['technologies_outils'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) {
        $v[$k] = trim($_POST[$k] ?? '');
    }
    try {
        if ($v['titre_projet'] === '' || $v['resume_executif'] === '') {
            throw new InvalidArgumentException('Le titre et le résumé exécutif sont obligatoires.');
        }
        $pdf = televerser_pdf('fichier_pdf');  // peut renvoyer une exception claire

        if ($projet) { // ---------- mise à jour
            $sql = 'UPDATE projets_etudiants SET titre_projet=?, departement=?, resume_executif=?,
                    technologies_outils=?' . ($pdf ? ', fichier_resume_pdf=?' : '') . ' WHERE id=? AND id_etudiant=?';
            $params = [$v['titre_projet'], $v['departement'] ?: null, $v['resume_executif'],
                       $v['technologies_outils'] ?: null];
            if ($pdf) $params[] = $pdf;
            $params[] = $projet['id'];
            $params[] = $u['id'];
            $pdo->prepare($sql)->execute($params);
            flash('succes', 'Projet mis à jour. La date de modification a été enregistrée automatiquement.');
        } else { // ---------- création
            $pdo->prepare(
                'INSERT INTO projets_etudiants (id_etudiant, titre_projet, departement, resume_executif,
                                                technologies_outils, fichier_resume_pdf)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$u['id'], $v['titre_projet'], $v['departement'] ?: null, $v['resume_executif'],
                        $v['technologies_outils'] ?: null, $pdf]);
            flash('succes', 'Votre projet est publié : il apparaît désormais dans la vitrine publique. ✓');
        }
        redirect(url('projet-voir.php?id=' . ($projet ? $projet['id'] : $pdo->lastInsertId())));
    } catch (InvalidArgumentException $e) {
        $erreurs[] = $e->getMessage();
    } catch (RuntimeException $e) {
        $erreurs[] = $e->getMessage();
    }
}

$titre = $projet ? 'Modifier mon projet' : 'Publier un projet';
require __DIR__ . '/includes/header.php';
?>

<div class="panneau panneau-moyenne">
  <div class="titre-panneau">
    <h1><?= e($titre) ?></h1>
    <p>Un résumé clair et professionnel augmente vos chances d'être repéré par les recruteurs.</p>
  </div>

  <?php foreach ($erreurs as $err): ?>
    <div class="alerte alerte-rouge"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="champ">
      <label for="titre_projet">Titre du projet / mémoire *</label>
      <input type="text" id="titre_projet" name="titre_projet" required maxlength="200"
             value="<?= e($v['titre_projet']) ?>">
    </div>
    <div class="champ">
      <label for="departement">Département / domaine</label>
      <input type="text" id="departement" name="departement" maxlength="150"
             placeholder="Ex. : Génie logiciel, Sciences de gestion…"
             value="<?= e($v['departement']) ?>">
    </div>
    <div class="champ">
      <label for="resume_executif">Résumé exécutif *</label>
      <textarea id="resume_executif" name="resume_executif" required
                placeholder="Problème traité, méthodologie, résultats obtenus, recommandations…"><?= e($v['resume_executif']) ?></textarea>
    </div>
    <div class="champ">
      <label for="technologies_outils">Technologies & outils utilisés</label>
      <input type="text" id="technologies_outils" name="technologies_outils" maxlength="255"
             placeholder="Ex. : Android (Kotlin), SPSS, QGIS…"
             value="<?= e($v['technologies_outils']) ?>">
    </div>
    <div class="champ">
      <label for="fichier_pdf">Résumé en PDF (optionnel, 5 Mo max.)</label>
      <input type="file" id="fichier_pdf" name="fichier_pdf" accept=".pdf,application/pdf">
      <?php if ($projet && $projet['fichier_resume_pdf']): ?>
        <div class="aide">Un PDF est déjà joint : <?= e($projet['fichier_resume_pdf']) ?> — chargez un nouveau fichier pour le remplacer.</div>
      <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-bleu"><?= $projet ? 'Enregistrer les modifications' : 'Publier mon projet' ?></button>
    <a class="btn btn-outline" href="<?= url($projet ? 'projet-voir.php?id=' . $projet['id'] : 'projets.php') ?>">Annuler</a>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
