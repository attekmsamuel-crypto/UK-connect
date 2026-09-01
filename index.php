<?php
/**
 * UK-Connect — Page d'accueil publique
 * Vitrine : derniers sujets validés + derniers projets publiés.
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
$titre = 'Accueil';
$pdo = db();

// Statistiques de la plateforme (4 compteurs simples)
$stats = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM projets_etudiants)                    AS projets,
            (SELECT COUNT(*) FROM besoins_sujets WHERE statut_id = ' . STVAL_VALIDE . ')  AS sujets,
            (SELECT COUNT(*) FROM utilisateurs WHERE role_id = ' . ROLE_PARTENAIRE . ')   AS partenaires,
            (SELECT COUNT(*) FROM facultes)                             AS facultes'
)->fetch();

// Derniers sujets validés (via la vue v2 : uniquement des sujets modérés)
$derniersSujets = $pdo->query(
    'SELECT * FROM v_sujets_publics ORDER BY date_creation DESC LIMIT 6'
)->fetchAll();

// Derniers projets publiés (comptes étudiants actifs uniquement)
$derniersProjets = $pdo->query(
    'SELECT * FROM v_projets_portfolio LIMIT 6'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="hero-badge"><i class="fa-solid fa-graduation-cap"></i> Université de Kara — Togo</div>
  <h1>Portail d'innovation académique<br>et de connexion <em>professionnelle</em></h1>
  <p>
    UK-Connect relie les mémoires et projets de nos étudiants aux besoins réels des ministères,
    collectivités, entreprises et ONG — une passerelle entre la recherche et l'économie togolaise.
  </p>
  <div class="hero-actions">
    <?php if (!est_connecte()): ?>
      <a class="btn btn-or" href="<?= url('inscription.php') ?>"><i class="fa-solid fa-user-plus"></i> Rejoindre la plateforme</a>
      <a class="btn btn-transparent" href="<?= url('projets.php') ?>"><i class="fa-solid fa-eye"></i> Voir le portfolio</a>
    <?php elseif (est_etudiant()): ?>
      <a class="btn btn-or" href="<?= url('projet-publier.php') ?>"><i class="fa-solid fa-upload"></i> Publier mon projet</a>
      <a class="btn btn-transparent" href="<?= url('sujets.php') ?>"><i class="fa-solid fa-magnifying-glass"></i> Trouver un sujet de mémoire</a>
    <?php elseif (est_partenaire()): ?>
      <a class="btn btn-or" href="<?= url('sujet-deposer.php') ?>"><i class="fa-solid fa-plus"></i> Déposer une problématique</a>
      <a class="btn btn-transparent" href="<?= url('projets.php') ?>"><i class="fa-solid fa-users"></i> Découvrir les talents</a>
    <?php else: ?>
      <a class="btn btn-or" href="<?= url('admin/index.php') ?>"><i class="fa-solid fa-gauge-high"></i> Tableau de bord</a>
    <?php endif; ?>
  </div>
</section>

<div class="stats">
  <div class="stat"><i class="fa-solid fa-folder-open stat-icone"></i><b><?= (int)$stats['projets'] ?></b><span>Projets publiés</span></div>
  <div class="stat"><i class="fa-solid fa-lightbulb stat-icone"></i><b><?= (int)$stats['sujets'] ?></b><span>Sujets d'étude validés</span></div>
  <div class="stat"><i class="fa-solid fa-handshake stat-icone"></i><b><?= (int)$stats['partenaires'] ?></b><span>Partenaires</span></div>
  <div class="stat"><i class="fa-solid fa-building-columns stat-icone"></i><b><?= (int)$stats['facultes'] ?></b><span>Facultés &amp; instituts</span></div>
</div>

<section class="section">
  <div class="titre-section">
    <h2>Derniers sujets d'étude</h2>
    <a href="<?= url('sujets.php') ?>">Tout voir <i class="fa-solid fa-arrow-right"></i></a>
  </div>
  <?php if (!$derniersSujets): ?>
    <p class="muted">Aucun sujet validé pour le moment.</p>
  <?php else: ?>
  <div class="grille-cartes">
    <?php foreach ($derniersSujets as $s): ?>
      <div class="carte">
        <div class="etiquettes">
          <span class="etiquette"><?= e($s['secteur_filiere']) ?></span>
          <?php if ($s['faculte_sigle']): ?><span class="etiquette etiquette-or"><?= e($s['faculte_sigle']) ?></span><?php endif; ?>
        </div>
        <h3><a href="<?= url('sujet-voir.php?id=' . (int)$s['id']) ?>"><?= e($s['titre_sujet']) ?></a></h3>
        <p><?= e(extrait($s['description_probleme'])) ?></p>
        <span class="date">Déposé par <b><?= e($s['partenaire']) ?></b> · <?= date_fr($s['date_creation']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<section class="section">
  <div class="titre-section">
    <h2>Derniers projets étudiants</h2>
    <a href="<?= url('projets.php') ?>">Tout voir <i class="fa-solid fa-arrow-right"></i></a>
  </div>
  <?php if (!$derniersProjets): ?>
    <p class="muted">Aucun projet publié pour le moment.</p>
  <?php else: ?>
  <div class="grille-cartes">
    <?php foreach ($derniersProjets as $p): ?>
      <div class="carte">
        <div class="etiquettes">
          <?php if ($p['departement']): ?><span class="etiquette"><?= e($p['departement']) ?></span><?php endif; ?>
          <?php if ($p['faculte_sigle']): ?><span class="etiquette etiquette-or"><?= e($p['faculte_sigle']) ?></span><?php endif; ?>
        </div>
        <h3><a href="<?= url('projet-voir.php?id=' . (int)$p['id']) ?>"><?= e($p['titre_projet']) ?></a></h3>
        <p><?= e(extrait($p['resume_executif'])) ?></p>
        <span class="auteur">Par <a href="<?= url('etudiant.php?id=' . (int)$p['auteur_id']) ?>"><b><?= e($p['auteur']) ?></b></a> · <?= date_fr($p['date_publication']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<section class="section">
  <div class="titre-section"><h2>Comment ça marche ?</h2></div>
  <div class="grille-cartes">
    <div class="carte"><div class="etape-num"><i class="fa-solid fa-file-signature"></i></div><h3>Un partenaire dépose une problématique</h3><p class="muted">Mairie, entreprise ou ONG décrit un besoin réel de son territoire.</p></div>
    <div class="carte"><div class="etape-num"><i class="fa-solid fa-user-shield"></i></div><h3>L'administration valide</h3><p class="muted">Un modérateur vérifie la pertinence académique du sujet avant publication.</p></div>
    <div class="carte"><div class="etape-num"><i class="fa-solid fa-paper-plane"></i></div><h3>L'étudiant candidate</h3><p class="muted">Il choisit le sujet comme mémoire ou projet de fin d'études.</p></div>
    <div class="carte"><div class="etape-num"><i class="fa-solid fa-handshake"></i></div><h3>Le partenaire retient</h3><p class="muted">Il sélectionne le candidat retenu et prend contact.</p></div>
    <div class="carte"><div class="etape-num"><i class="fa-solid fa-award"></i></div><h3>Le travail est publié</h3><p class="muted">Le résumé exécutif rejoint la vitrine : un portfolio vérifiable pour l'insertion professionnelle.</p></div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
