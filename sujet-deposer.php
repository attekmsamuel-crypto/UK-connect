<?php
/**
 * UK-Connect — Dépôt d'un besoin / problème (partenaire)
 * Règle de gestion (fiche technique, module 2) : tout sujet déposé
 * passe par le statut « en_attente » avant validation admin.
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$u = require_role('partenaire');
csrf_verifier();
$pdo = db();

$facultes = $pdo->query('SELECT id, sigle, nom_faculte FROM facultes ORDER BY sigle')->fetchAll();
$erreurs  = [];
$v = ['titre_sujet' => '', 'description_probleme' => '', 'secteur_filiere' => '', 'id_faculte' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) {
        $v[$k] = trim($_POST[$k] ?? '');
    }
    try {
        if ($v['titre_sujet'] === '' || $v['description_probleme'] === '' || $v['secteur_filiere'] === '') {
            throw new InvalidArgumentException('Titre, description et secteur sont obligatoires.');
        }
        $pdo->prepare(
            'INSERT INTO besoins_sujets (id_partenaire, titre_sujet, description_probleme,
                                         secteur_filiere, id_faculte, statut_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $u['id'], $v['titre_sujet'], $v['description_probleme'],
            $v['secteur_filiere'], $v['id_faculte'] !== '' ? (int)$v['id_faculte'] : null,
            STVAL_EN_ATTENTE,   // ← modération obligatoire avant publication
        ]);
        flash('succes', 'Votre besoin a été déposé. Il sera visible des étudiants après validation par l\'administration. ✓');
        redirect(url('partenaire-espace.php'));
    } catch (InvalidArgumentException $e) {
        $erreurs[] = $e->getMessage();
    }
}

$titre = 'Déposer un besoin';
require __DIR__ . '/includes/header.php';
?>

<div class="panneau panneau-moyenne">
  <div class="titre-panneau">
    <h1>Déposer une problématique</h1>
    <p>Décrivez un besoin réel de votre structure : il deviendra un sujet d'étude pour les étudiants de l'UK.</p>
  </div>

  <?php foreach ($erreurs as $err): ?>
    <div class="alerte alerte-rouge"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post">
    <?= csrf_field() ?>
    <div class="champ">
      <label for="titre_sujet">Titre de la problématique *</label>
      <input type="text" id="titre_sujet" name="titre_sujet" required maxlength="200"
             placeholder="Ex. : Cartographie de la gestion des déchets dans la commune de Kara"
             value="<?= e($v['titre_sujet']) ?>">
    </div>
    <div class="champ">
      <label for="description_probleme">Description détaillée du besoin *</label>
      <textarea id="description_probleme" name="description_probleme" required
                placeholder="Contexte, problème concret, résultat attendu de l'étude…"><?= e($v['description_probleme']) ?></textarea>
    </div>
    <div class="deux-colonnes">
      <div class="champ">
        <label for="secteur_filiere">Secteur / filière concerné *</label>
        <input type="text" id="secteur_filiere" name="secteur_filiere" required maxlength="100"
               placeholder="Ex. : Agriculture / AgriTech, Gestion, Santé…"
               value="<?= e($v['secteur_filiere']) ?>">
      </div>
      <div class="champ">
        <label for="id_faculte">Faculté suggérée (optionnel)</label>
        <select id="id_faculte" name="id_faculte">
          <option value="">— L'administration confirmera —</option>
          <?php foreach ($facultes as $f): ?>
            <option value="<?= (int)$f['id'] ?>"
              <?= (string)$v['id_faculte'] === (string)$f['id'] ? 'selected' : '' ?>>
              <?= e($f['sigle']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-bleu">Déposer le besoin</button>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
