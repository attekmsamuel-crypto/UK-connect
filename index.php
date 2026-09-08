<?php

require_once __DIR__ . '/includes/auth.php';

demarrer_session();

$pdo = connexion_bdd();
$chiffres = chiffres_accueil($pdo);
$besoins = derniers_besoins($pdo, 6);
$projets = derniers_projets($pdo, 6);
$facultes = liste_facultes($pdo);

$vedette = $besoins ? $besoins[0] : null;

$titrePage = 'Accueil';
require __DIR__ . '/includes/entete.php';
?>

<section class="presentation">
  <div class="presentation-corps">
    <div>
      <span class="surtitre">Université de Kara</span>
      <h1>Les travaux de nos étudiants au service des <em>besoins du territoire</em></h1>
      <p>
        Les entreprises, les collectivités et les associations déposent ici les difficultés
        concrètes qu'elles rencontrent. L'université les publie, les étudiants s'en saisissent
        comme sujets de mémoire, et le travail achevé rejoint un portfolio public.
      </p>
      <div class="ligne-boutons">
        <?php if (!est_connecte()): ?>
          <a class="bouton" href="<?= lien('inscription.php') ?>">Créer un compte <?= icone('fleche', 17) ?></a>
          <a class="bouton bouton-fantome" href="<?= lien('besoins.php') ?>">Parcourir les besoins</a>
        <?php elseif (est_etudiant()): ?>
          <a class="bouton" href="<?= lien('besoins.php') ?>">Trouver un sujet <?= icone('fleche', 17) ?></a>
          <a class="bouton bouton-fantome" href="<?= lien('publier-projet.php') ?>">Publier un projet</a>
        <?php elseif (est_partenaire()): ?>
          <a class="bouton" href="<?= lien('deposer-besoin.php') ?>">Déposer un besoin <?= icone('fleche', 17) ?></a>
          <a class="bouton bouton-fantome" href="<?= lien('portfolio.php') ?>">Voir les profils étudiants</a>
        <?php else: ?>
          <a class="bouton" href="<?= lien('admin/index.php') ?>">Aller à la modération <?= icone('fleche', 17) ?></a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($vedette): ?>
      <aside class="apercu ton-<?= e(ton_secteur($vedette['secteur'])) ?>">
        <div class="apercu-tete"><span></span> Dernier besoin publié</div>
        <h3><a href="<?= lien('besoin.php?id=' . (int)$vedette['id']) ?>"><?= e($vedette['titre']) ?></a></h3>
        <p><?= e(resume_court($vedette['description'], 165)) ?></p>
        <div class="apercu-pied">
          <span class="jeton"><?= e(mb_strtoupper(mb_substr($vedette['nom_structure'], 0, 2))) ?></span>
          <span><?= e($vedette['nom_structure']) ?><br><?= date_courte($vedette['date_depot']) ?></span>
        </div>
      </aside>
    <?php endif; ?>
  </div>

  <div class="chiffres">
    <div class="chiffre"><b><?= (int)$chiffres['projets'] ?></b><span>projets publiés</span></div>
    <div class="chiffre"><b><?= (int)$chiffres['besoins'] ?></b><span>besoins en ligne</span></div>
    <div class="chiffre"><b><?= (int)$chiffres['partenaires'] ?></b><span>structures partenaires</span></div>
    <div class="chiffre"><b><?= (int)$chiffres['etudiants'] ?></b><span>étudiants inscrits</span></div>
  </div>
</section>

<section class="section">
  <div class="section-entete">
    <div>
      <span class="surtitre">Ce que cherchent les partenaires</span>
      <h2>Derniers besoins publiés</h2>
    </div>
    <a class="lien-suite" href="<?= lien('besoins.php') ?>">Voir tous les besoins <?= icone('fleche', 16) ?></a>
  </div>

  <?php if (!$besoins): ?>
    <div class="vide">Aucun besoin n'est publié pour le moment.</div>
  <?php else: ?>
    <div class="grille">
      <?php foreach ($besoins as $b): ?>
        <article class="fiche ton-<?= e(ton_secteur($b['secteur'])) ?>">
          <div class="etiquettes">
            <span class="etiquette"><?= e($b['secteur']) ?></span>
            <?php if ($b['sigle']): ?><span class="etiquette etiquette-faculte"><?= e($b['sigle']) ?></span><?php endif; ?>
          </div>
          <h3><a href="<?= lien('besoin.php?id=' . (int)$b['id']) ?>"><?= e($b['titre']) ?></a></h3>
          <p><?= e(resume_court($b['description'], 160)) ?></p>
          <div class="fiche-bas">
            <span class="jeton"><?= e(mb_strtoupper(mb_substr($b['nom_structure'], 0, 2))) ?></span>
            <span><?= e($b['nom_structure']) ?> · <?= date_courte($b['date_depot']) ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="section">
  <div class="section-entete">
    <div>
      <span class="surtitre">Ce que produisent nos étudiants</span>
      <h2>Derniers projets déposés</h2>
    </div>
    <a class="lien-suite" href="<?= lien('portfolio.php') ?>">Voir tout le portfolio <?= icone('fleche', 16) ?></a>
  </div>

  <?php if (!$projets): ?>
    <div class="vide">Aucun projet n'est encore publié.</div>
  <?php else: ?>
    <div class="grille">
      <?php foreach ($projets as $p): ?>
        <article class="fiche ton-<?= e(ton_secteur($p['departement'])) ?>">
          <div class="etiquettes">
            <?php if ($p['departement']): ?><span class="etiquette"><?= e($p['departement']) ?></span><?php endif; ?>
            <?php if ($p['sigle']): ?><span class="etiquette etiquette-faculte"><?= e($p['sigle']) ?></span><?php endif; ?>
          </div>
          <h3><a href="<?= lien('projet.php?id=' . (int)$p['id']) ?>"><?= e($p['titre']) ?></a></h3>
          <p><?= e(resume_court($p['resume'], 160)) ?></p>
          <div class="fiche-bas">
            <span class="jeton"><?= e(initiales($p['prenom'], $p['nom'])) ?></span>
            <span>
              <a href="<?= lien('etudiant.php?id=' . (int)$p['id_etudiant']) ?>"><?= e($p['prenom'] . ' ' . $p['nom']) ?></a>
              · <?= date_courte($p['date_publication']) ?>
            </span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if ($facultes): ?>
<section class="section">
  <div class="section-entete">
    <div>
      <span class="surtitre">Neuf entités, un seul portail</span>
      <h2>Les facultés, instituts et écoles de l'université</h2>
    </div>
  </div>
  <div class="ruban">
    <div class="ruban-piste">
      <?php for ($passage = 0; $passage < 2; $passage++): ?>
        <?php foreach ($facultes as $faculte): ?>
          <span class="ruban-element" <?= $passage === 1 ? 'aria-hidden="true"' : '' ?>>
            <b><?= e($faculte['sigle']) ?></b> <?= e($faculte['nom_faculte']) ?>
          </span>
        <?php endforeach; ?>
      <?php endfor; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/pied.php'; ?>
