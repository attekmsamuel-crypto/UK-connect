</main>

<footer class="pied">
  <div class="zone pied-colonnes">
    <div>
      <div class="pied-marque">
        <img src="<?= lien('assets/images/logo-uk.png') ?>" alt="">
        <strong>UK-Connect</strong>
      </div>
      <p>
        La plateforme met en relation les travaux des étudiants de l'Université de Kara
        avec les besoins des entreprises, des collectivités et des associations de la région.
      </p>
    </div>
    <div>
      <h4>Découvrir</h4>
      <ul>
        <li><a href="<?= lien('besoins.php') ?>">Besoins publiés</a></li>
        <li><a href="<?= lien('portfolio.php') ?>">Portfolio</a></li>
        <li><a href="<?= lien('index.php') ?>">Accueil</a></li>
      </ul>
    </div>
    <div>
      <h4>Participer</h4>
      <ul>
        <li><a href="<?= lien('inscription.php') ?>">Créer un compte</a></li>
        <li><a href="<?= lien('connexion.php') ?>">Se connecter</a></li>
        <li><a href="<?= lien('deposer-besoin.php') ?>">Déposer un besoin</a></li>
      </ul>
    </div>
    <div>
      <h4>Nous joindre</h4>
      <ul>
        <li><?= icone('lieu', 15) ?> Université de Kara, BP 404</li>
        <li><?= icone('immeuble', 15) ?> Kara, Togo</li>
        <li><a href="mailto:contact@ukconnect.tg"><?= icone('mail', 15) ?> contact@ukconnect.tg</a></li>
        <li><a href="tel:+22893851180"><?= icone('telephone', 15) ?> +228 93 85 11 80</a></li>
      </ul>
    </div>
  </div>
  <div class="zone pied-bas">
    <span>© <?= date('Y') ?> Université de Kara</span>
    <span>Projet réalisé dans le cadre de la formation en développement web</span>
  </div>
</footer>

<script src="<?= lien('assets/js/script.js') ?>"></script>
</body>
</html>
