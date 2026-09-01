<?php
/**
 * UK-Connect — Mes candidatures (étudiant)
 * Suivi des décisions du partenaire avec horodatage (date_decision).
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$u = require_role('etudiant');
$pdo = db();

$st = $pdo->prepare(
    'SELECT c.id, c.date_candidature, c.date_decision, sc.code AS statut_code,
            sc.libelle AS statut_libelle, s.id AS sujet_id, s.titre_sujet,
            u2.nom_structure AS partenaire, f.sigle AS faculte_sigle
     FROM candidatures c
     JOIN statuts_candidature sc ON sc.id = c.statut_id
     JOIN besoins_sujets s       ON s.id  = c.id_sujet
     JOIN utilisateurs u2        ON u2.id = s.id_partenaire
     LEFT JOIN facultes f        ON f.id  = u2.id_faculte
     WHERE c.id_etudiant = ?
     ORDER BY c.date_candidature DESC'
);
$st->execute([$u['id']]);
$candidatures = $st->fetchAll();

function classe_statut(string $code): string {
    return match ($code) {
        'valide', 'retenue'      => 'statut-valide',
        'rejete', 'non_retenue'  => 'statut-rejete',
        default                  => 'statut-en-attente',
    };
}

$titre = 'Mes candidatures';
require __DIR__ . '/includes/header.php';
?>

<div class="page-titre">
  <h1>Mes candidatures</h1>
  <p>Historique complet de vos candidatures aux sujets déposés par les partenaires.</p>
</div>

<?php if (!$candidatures): ?>
  <div class="panneau">
    <p>Aucune candidature pour le moment. Parcourez la
       <a href="<?= url('sujets.php') ?>"><b>banque de sujets d'étude</b></a> pour trouver votre sujet de mémoire <i class="fa-solid fa-lightbulb"></i></p>
  </div>
<?php else: ?>
<div class="tableau-englobant">
  <table>
    <thead>
      <tr><th>Sujet</th><th>Partenaire</th><th>Déposée le</th><th>Statut</th><th>Décision</th></tr>
    </thead>
    <tbody>
      <?php foreach ($candidatures as $c): ?>
      <tr>
        <td><a href="<?= url('sujet-voir.php?id=' . (int)$c['sujet_id']) ?>"><b><?= e($c['titre_sujet']) ?></b></a></td>
        <td><?= e($c['partenaire']) ?></td>
        <td><?= date_fr($c['date_candidature']) ?></td>
        <td><span class="statut <?= classe_statut($c['statut_code']) ?>"><?= e($c['statut_libelle']) ?></span></td>
        <td><?= $c['date_decision'] ? date_fr($c['date_decision']) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
