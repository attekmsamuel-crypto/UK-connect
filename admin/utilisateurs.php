<?php
/**
 * UK-Connect — Gestion des comptes (admin)
 * Désactivation / réactivation (suppression douce — base v2).
 * Un administrateur ne peut pas désactiver son propre compte.
 */
require_once __DIR__ . '/../includes/auth.php';
session_init();
$u = require_role('admin');
$pdo = db();
csrf_verifier();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $userId = (int)$_POST['user_id'];
    if ($userId === (int)$u['id']) {
        flash('erreur', 'Vous ne pouvez pas désactiver votre propre compte administrateur.');
    } else {
        $pdo->prepare('UPDATE utilisateurs SET actif = ? WHERE id = ?')
            ->execute([$_POST['action'] === 'activer' ? 1 : 0, $userId]);
        flash('succes', 'Compte ' . ($_POST['action'] === 'activer' ? 'réactivé' : 'désactivé') . '.');
    }
    redirect(url('admin/utilisateurs.php'));
}

$comptes = $pdo->query(
    'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.nom_structure, u.actif,
            u.date_creation, r.libelle AS role, f.sigle AS faculte_sigle,
            (SELECT COUNT(*) FROM projets_etudiants p WHERE p.id_etudiant = u.id) AS nb_projets,
            (SELECT COUNT(*) FROM besoins_sujets b WHERE b.id_partenaire = u.id)  AS nb_sujets
     FROM utilisateurs u
     JOIN roles r ON r.id = u.role_id
     LEFT JOIN facultes f ON f.id = u.id_faculte
     ORDER BY u.date_creation DESC'
)->fetchAll();

$titre = 'Gestion des comptes';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-titre">
  <h1>Gestion des comptes utilisateurs</h1>
  <p>Désactiver un compte le rend inutilisable sans effacer l'historique (suppression douce).</p>
</div>

<div class="tableau-englobant">
  <table>
    <thead>
      <tr><th>Utilisateur</th><th>Rôle</th><th>Rattachement</th><th>Contact</th><th>Activité</th><th>État</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($comptes as $c): ?>
      <tr>
        <td><b><?= e($c['prenom'] . ' ' . $c['nom']) ?></b><br>
            <span class="muted">Inscrit le <?= date_fr($c['date_creation']) ?></span></td>
        <td><span class="etiquette"><?= e($c['role']) ?></span></td>
        <td>
          <?= $c['faculte_sigle'] ? e($c['faculte_sigle']) : e($c['nom_structure'] ?: '—') ?>
        </td>
        <td><?= e($c['email']) ?><?php if ($c['telephone']): ?><br><span class="muted"><?= e($c['telephone']) ?></span><?php endif; ?></td>
        <td><?= (int)$c['nb_projets'] ?> projet(s)<br><?= (int)$c['nb_sujets'] ?> sujet(s)</td>
        <td>
          <?php if ($c['actif']): ?>
            <span class="statut statut-valide">Actif</span>
          <?php else: ?>
            <span class="statut statut-rejete">Désactivé</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ((int)$c['id'] !== (int)$u['id']): ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>">
            <?php if ($c['actif']): ?>
              <button class="btn btn-rouge btn-petit" name="action" value="desactiver"
                      data-confirm="Désactiver le compte de <?= e($c['prenom']) ?> ?">Désactiver</button>
            <?php else: ?>
              <button class="btn btn-vert btn-petit" name="action" value="activer">Réactiver</button>
            <?php endif; ?>
          </form>
          <?php else: ?>
          <span class="muted">Vous</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
