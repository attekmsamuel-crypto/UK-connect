<?php

require_once __DIR__ . '/../includes/auth.php';

demarrer_session();

$moi = exiger_role('admin');
$pdo = connexion_bdd();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $id = (int)($_POST['utilisateur_id'] ?? 0);
    $actif = (int)($_POST['actif'] ?? 1);

    if ($id === (int)$moi['id']) {
        message('erreur', 'Vous ne pouvez pas désactiver votre propre compte.');
    } else {
        changer_activation_utilisateur($pdo, $id, $actif);
        message('succes', $actif === 1 ? 'Le compte est réactivé.' : 'Le compte est désactivé.');
    }

    $filtre = $_POST['role'] ?? '';
    rediriger(lien('admin/utilisateurs.php') . ($filtre !== '' ? '?role=' . urlencode($filtre) : ''));
}

$role = $_GET['role'] ?? '';
if (!in_array($role, ['etudiant', 'partenaire', 'admin'], true)) {
    $role = '';
}

$utilisateurs = liste_utilisateurs($pdo, $role);
$chiffres = chiffres_admin($pdo);

$titrePage = 'Comptes';
require __DIR__ . '/../includes/entete.php';
?>

<div class="titre-page">
  <h1>Comptes</h1>
  <p><?= (int)$chiffres['etudiants'] ?> étudiants et <?= (int)$chiffres['partenaires'] ?> partenaires inscrits sur la plateforme.</p>
</div>

<div class="onglets">
  <a href="<?= lien('admin/utilisateurs.php') ?>" class="<?= $role === '' ? 'actif' : '' ?>">Tous</a>
  <a href="<?= lien('admin/utilisateurs.php?role=etudiant') ?>" class="<?= $role === 'etudiant' ? 'actif' : '' ?>">Étudiants</a>
  <a href="<?= lien('admin/utilisateurs.php?role=partenaire') ?>" class="<?= $role === 'partenaire' ? 'actif' : '' ?>">Partenaires</a>
  <a href="<?= lien('admin/utilisateurs.php?role=admin') ?>" class="<?= $role === 'admin' ? 'actif' : '' ?>">Administration</a>
</div>

<?php if (!$utilisateurs): ?>
  <div class="vide">Aucun compte dans cette catégorie.</div>
<?php else: ?>
  <div class="tableau">
    <table>
      <thead>
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>Profil</th>
          <th>Inscrit le</th>
          <th>État</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilisateurs as $u): ?>
          <tr>
            <td>
              <?php if ($u['role'] === 'etudiant'): ?>
                <a href="<?= lien('etudiant.php?id=' . (int)$u['id']) ?>"><?= e($u['prenom'] . ' ' . $u['nom']) ?></a>
              <?php else: ?>
                <?= e($u['prenom'] . ' ' . $u['nom']) ?>
              <?php endif; ?>
              <?php if ($u['nom_structure']): ?>
                <div class="petit discret"><?= e($u['nom_structure']) ?></div>
              <?php endif; ?>
            </td>
            <td class="petit"><a href="mailto:<?= e($u['email']) ?>"><?= e($u['email']) ?></a></td>
            <td>
              <?= e(ucfirst($u['role'])) ?>
              <?php if ($u['sigle']): ?><div class="petit discret"><?= e($u['sigle']) ?></div><?php endif; ?>
            </td>
            <td><?= date_courte($u['date_inscription']) ?></td>
            <td>
              <?php if ((int)$u['actif'] === 1): ?>
                <span class="etat etat-ok">Actif</span>
              <?php else: ?>
                <span class="etat etat-non">Désactivé</span>
              <?php endif; ?>
            </td>
            <td style="width:1%;">
              <?php if ((int)$u['id'] !== (int)$moi['id']): ?>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="utilisateur_id" value="<?= (int)$u['id'] ?>">
                  <input type="hidden" name="role" value="<?= e($role) ?>">
                  <?php if ((int)$u['actif'] === 1): ?>
                    <input type="hidden" name="actif" value="0">
                    <button type="submit" class="bouton bouton-rouge bouton-petit"
                            data-confirmer="Désactiver le compte de <?= e($u['prenom'] . ' ' . $u['nom']) ?> ?">Désactiver</button>
                  <?php else: ?>
                    <input type="hidden" name="actif" value="1">
                    <button type="submit" class="bouton bouton-secondaire bouton-petit">Réactiver</button>
                  <?php endif; ?>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/pied.php'; ?>
