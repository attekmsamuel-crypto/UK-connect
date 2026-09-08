<?php

function liste_facultes(PDO $pdo): array
{
    // TODO 1 : la liste des facultés
    // Renvoie toutes les lignes de la table facultes (id, nom_faculte, sigle),
    // triées par sigle du A au Z.
    // Il n'y a rien à filtrer ici : $pdo->query() suffit, inutile de préparer
    // la requête. Récupère ensuite toutes les lignes d'un coup avec fetchAll().
    // Tant que ce n'est pas fait : le menu déroulant « Faculté ou institut » de
    // la page d'inscription reste vide, et les filtres par faculté des pages
    // Besoins et Portfolio ne proposent rien.

    return [];
}

function secteurs_disponibles(PDO $pdo): array
{
    $requete = $pdo->query(
        'SELECT DISTINCT secteur FROM besoins WHERE statut = "valide" ORDER BY secteur'
    );

    return $requete->fetchAll(PDO::FETCH_COLUMN);
}

function chiffres_accueil(PDO $pdo): array
{
    // TODO 2 : les quatre compteurs de la page d'accueil
    // Compte quatre choses : le nombre de projets publiés, le nombre de besoins
    // dont le statut vaut 'valide', le nombre de partenaires actifs et le nombre
    // d'étudiants actifs (colonne actif à 1).
    // Quatre petites requêtes SELECT COUNT(*), chacune lue avec fetchColumn().
    // Renvoie un tableau avec exactement ces quatre clés, c'est ce que la page
    // d'accueil va lire : projets, besoins, partenaires, etudiants.
    // Tant que ce n'est pas fait : les quatre encadrés de l'accueil affichent 0.

    return ['projets' => 0, 'besoins' => 0, 'partenaires' => 0, 'etudiants' => 0];
}

function derniers_besoins(PDO $pdo, int $limite): array
{
    // TODO 3 : les derniers besoins publiés
    // Renvoie les $limite besoins les plus récents dont le statut vaut 'valide',
    // du plus récent au plus ancien (ORDER BY date_depot DESC).
    // Il faut une jointure sur utilisateurs pour récupérer nom_structure, et une
    // jointure LEFT sur facultes pour le sigle : un besoin peut très bien ne pas
    // être rattaché à une faculté, et une jointure normale ferait alors
    // disparaître la ligne.
    // Pour la limite, colle la valeur dans la requête après l'avoir forcée en
    // nombre entier :  ... ORDER BY b.date_depot DESC LIMIT ' . (int)$limite
    // Tant que ce n'est pas fait : le bloc « Derniers besoins publiés » de
    // l'accueil affiche « Aucun besoin n'est publié pour le moment ».

    return [];
}

function derniers_projets(PDO $pdo, int $limite): array
{
    // TODO 4 : les derniers projets déposés
    // Même principe que la fonction précédente, mais sur la table projets.
    // Joins utilisateurs pour l'auteur et LEFT JOIN facultes pour son sigle.
    // Ne garde que les projets dont l'auteur a un compte actif, et trie par
    // date_publication décroissante.
    // La page a besoin de l'identifiant de l'auteur sous le nom id_etudiant pour
    // fabriquer le lien vers sa fiche : pense à l'alias u.id AS id_etudiant.
    // Tant que ce n'est pas fait : le bloc « Derniers projets déposés » de
    // l'accueil reste vide.

    return [];
}

function utilisateur_par_email(PDO $pdo, string $email): ?array
{
    // TODO 5 : retrouver un compte à partir de son adresse email
    // Cherche dans utilisateurs la ligne dont la colonne email correspond à
    // $email et renvoie-la. Renvoie null si aucun compte ne correspond.
    // L'email vient d'un formulaire : requête préparée obligatoire, donc
    // prepare(), puis execute() avec la valeur, puis fetch().
    // Cette fonction sert à la connexion. C'est connecter(), dans
    // includes/auth.php, qui compare ensuite le mot de passe saisi au mot de
    // passe haché de la ligne, avec password_verify().
    // Tant que ce n'est pas fait : personne ne peut se connecter, la page de
    // connexion répond toujours « Adresse email ou mot de passe incorrect ».

    return null;
}

function utilisateur_par_id(PDO $pdo, int $id): ?array
{
    $requete = $pdo->prepare(
        'SELECT u.*, f.sigle, f.nom_faculte
         FROM utilisateurs u
         LEFT JOIN facultes f ON f.id = u.id_faculte
         WHERE u.id = ?'
    );
    $requete->execute([$id]);
    $utilisateur = $requete->fetch();

    return $utilisateur ?: null;
}

function creer_utilisateur(PDO $pdo, array $donnees): int
{
    // TODO 6 : enregistrer un nouveau compte
    // Insère une ligne dans utilisateurs avec les huit valeurs reçues dans le
    // tableau $donnees : nom, prenom, email, telephone, mot_de_passe, role,
    // id_faculte, nom_structure.
    // Le mot de passe arrive en clair et ne doit jamais partir tel quel en base :
    // passe-le par password_hash($donnees['mot_de_passe'], PASSWORD_DEFAULT).
    // Renvoie l'identifiant de la ligne créée, avec $pdo->lastInsertId().
    // Tant que ce n'est pas fait : le formulaire d'inscription répond
    // « Le compte n'a pas pu être enregistré ».

    return 0;
}

function mettre_a_jour_profil(PDO $pdo, int $id, array $donnees): void
{
    $requete = $pdo->prepare(
        'UPDATE utilisateurs
         SET nom = ?, prenom = ?, telephone = ?, bio = ?, niveau_etudes = ?,
             nom_structure = ?, id_faculte = ?, photo = ?, banniere = ?
         WHERE id = ?'
    );
    $requete->execute([
        $donnees['nom'],
        $donnees['prenom'],
        $donnees['telephone'],
        $donnees['bio'],
        $donnees['niveau_etudes'],
        $donnees['nom_structure'],
        $donnees['id_faculte'],
        $donnees['photo'],
        $donnees['banniere'],
        $id,
    ]);
}

function changer_mot_de_passe(PDO $pdo, int $id, string $motDePasse): void
{
    $requete = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
    $requete->execute([password_hash($motDePasse, PASSWORD_DEFAULT), $id]);
}

function chercher_besoins(PDO $pdo, string $mot, string $secteur, int $idFaculte): array
{
    // TODO 7 : la liste des besoins, avec la recherche et les filtres
    // Renvoie les besoins publiés (statut 'valide'), du plus récent au plus
    // ancien, avec le nom de la structure et le sigle de la faculté, exactement
    // comme dans la fonction 3.
    // Les trois filtres sont facultatifs et se combinent : $mot est cherché dans
    // le titre ou dans la description (avec LIKE et des % autour du mot),
    // $secteur est comparé à l'identique, et $idFaculte ne compte que s'il est
    // supérieur à 0.
    // Le plus simple : pars d'un tableau $conditions qui contient déjà
    // b.statut = "valide" et d'un tableau $valeurs vide ; pour chaque filtre
    // rempli, ajoute une condition dans le premier et sa ou ses valeurs dans le
    // second ; assemble ensuite le WHERE avec implode(' AND ', $conditions),
    // puis prepare() et execute($valeurs).
    // Attention : quand tu compares $mot à deux colonnes, il faut l'ajouter deux
    // fois dans $valeurs, une valeur par point d'interrogation.
    // Tant que ce n'est pas fait : la page Besoins annonce toujours
    // « Aucun besoin ne correspond à cette recherche ».

    return [];
}

function besoin(PDO $pdo, int $id): ?array
{
    // TODO 8 : la fiche complète d'un besoin
    // Renvoie toutes les colonnes du besoin demandé, plus les informations du
    // partenaire qui l'a déposé (nom_structure, email, telephone, bio, et son
    // identifiant sous l'alias id_partenaire) ainsi que le sigle et le nom de la
    // faculté concernée.
    // Une seule ligne est attendue : fetch(), et null si l'identifiant reçu ne
    // correspond à aucun besoin.
    // Tant que ce n'est pas fait : cliquer sur un besoin affiche « Ce besoin
    // n'existe pas ou n'est plus disponible », et côté administration les boutons
    // Publier et Rejeter répondent « Ce besoin est introuvable ».

    return null;
}

function chercher_projets(PDO $pdo, string $mot, int $idFaculte): array
{
    // TODO 9 : la liste du portfolio
    // Même travail qu'en 7, cette fois sur les projets : ne garde que les projets
    // dont l'auteur est actif, trie par date_publication décroissante, et renvoie
    // pour chacun son auteur (id_etudiant, prenom, nom) et le sigle de sa faculté.
    // Deux filtres seulement : $mot, cherché dans le titre, dans le résumé et
    // dans le domaine d'étude — donc trois valeurs à ajouter — et $idFaculte, qui
    // porte sur la faculté de l'auteur (u.id_faculte).
    // Tant que ce n'est pas fait : la page Portfolio reste vide, y compris quand
    // aucun filtre n'est saisi.

    return [];
}

function projet(PDO $pdo, int $id): ?array
{
    // TODO 10 : la fiche complète d'un projet
    // Renvoie les colonnes du projet demandé et celles de son auteur :
    // id_etudiant, prenom, nom, email, telephone, niveau_etudes, photo, ainsi que
    // le sigle et le nom de sa faculté.
    // Une seule ligne : fetch(), et null si le projet n'existe pas.
    // Tant que ce n'est pas fait : cliquer sur un projet du portfolio affiche
    // « Ce projet n'existe pas ».

    return null;
}

function ajouter_besoin(PDO $pdo, array $donnees): int
{
    // TODO 11 : enregistrer un besoin déposé par un partenaire
    // Insère dans besoins les cinq valeurs reçues dans $donnees : id_partenaire,
    // titre, description, secteur, id_faculte.
    // Ne touche pas à la colonne statut : la base la met d'elle-même à
    // 'en_attente', c'est l'administration qui tranchera ensuite.
    // Renvoie l'identifiant créé avec lastInsertId().
    // Tant que ce n'est pas fait : le formulaire « Déposer un besoin » répond
    // « Le besoin n'a pas pu être enregistré » et rien n'arrive dans la file de
    // modération.

    return 0;
}

function ajouter_projet(PDO $pdo, array $donnees): int
{
    // TODO 12 : publier un projet étudiant
    // Insère dans projets : id_etudiant, titre, departement, resume,
    // technologies, fichier_pdf.
    // Le fichier PDF a déjà été déposé sur le serveur avant l'appel : tu ne
    // reçois ici que son nom, ou null si l'étudiant n'a rien joint.
    // Renvoie l'identifiant créé : la page s'en sert pour rediriger vers la fiche
    // du projet tout juste publié.
    // Tant que ce n'est pas fait : le formulaire répond « Le projet n'a pas pu
    // être publié ».

    return 0;
}

function a_deja_postule(PDO $pdo, int $idBesoin, int $idEtudiant): bool
{
    $requete = $pdo->prepare(
        'SELECT COUNT(*) FROM candidatures WHERE id_besoin = ? AND id_etudiant = ?'
    );
    $requete->execute([$idBesoin, $idEtudiant]);

    return $requete->fetchColumn() > 0;
}

function ajouter_candidature(PDO $pdo, int $idBesoin, int $idEtudiant, string $motivation): int
{
    // TODO 13 : enregistrer une candidature
    // Insère dans candidatures les trois valeurs reçues : id_besoin, id_etudiant,
    // motivation. Renvoie l'identifiant créé.
    // Le statut n'est pas à renseigner, la base le met à 'en_attente'.
    // Inutile de vérifier ici que l'étudiant n'a pas déjà postulé : la page
    // appelle a_deja_postule() juste avant toi.
    // Tant que ce n'est pas fait : le bouton « Envoyer ma candidature » répond
    // « La candidature n'a pas pu être enregistrée », le partenaire n'est pas
    // prévenu et la page Mes candidatures reste vide.

    return 0;
}

function candidatures_de_etudiant(PDO $pdo, int $idEtudiant): array
{
    $requete = $pdo->prepare(
        'SELECT c.id, c.statut, c.motivation, c.date_candidature, c.date_decision,
                b.id AS id_besoin, b.titre, b.secteur, u.nom_structure
         FROM candidatures c
         JOIN besoins b ON b.id = c.id_besoin
         JOIN utilisateurs u ON u.id = b.id_partenaire
         WHERE c.id_etudiant = ?
         ORDER BY c.date_candidature DESC'
    );
    $requete->execute([$idEtudiant]);

    return $requete->fetchAll();
}

function candidatures_du_partenaire(PDO $pdo, int $idPartenaire): array
{
    // TODO 14 : les candidatures reçues par un partenaire
    // Renvoie toutes les candidatures déposées sur les besoins de ce partenaire.
    // Pars de candidatures, joins besoins pour retrouver id_partenaire et le
    // titre du besoin, puis joins utilisateurs pour l'étudiant (son identifiant
    // sous l'alias id_etudiant, prenom, nom, email, telephone, niveau_etudes) et
    // ajoute un LEFT JOIN sur facultes pour son sigle.
    // Fais remonter en tête celles qui attendent encore une réponse. Une façon
    // simple de l'écrire en SQL :
    //   ORDER BY (c.statut = "en_attente") DESC, c.date_candidature DESC
    // Tant que ce n'est pas fait : l'espace partenaire affiche « Aucune
    // candidature reçue pour l'instant » même quand des étudiants ont postulé.

    return [];
}

function changer_statut_candidature(PDO $pdo, int $id, string $statut): void
{
    // TODO 15 : la décision du partenaire
    // Mets à jour la candidature demandée : la colonne statut prend la valeur
    // reçue ('retenue' ou 'non_retenue') et date_decision prend la date et
    // l'heure du moment, avec la fonction SQL NOW().
    // Cette fonction ne renvoie rien, elle se contente de modifier la ligne.
    // Le contrôle « cette candidature porte bien sur un besoin de ce partenaire »
    // est déjà fait par la page avant l'appel.
    // Tant que ce n'est pas fait : les boutons Retenir et Écarter affichent bien
    // le message de confirmation, mais la ligne reste « En attente » et les
    // coordonnées de l'étudiant retenu ne s'affichent jamais.
}

function candidature(PDO $pdo, int $id): ?array
{
    $requete = $pdo->prepare(
        'SELECT c.*, b.titre, b.id_partenaire
         FROM candidatures c
         JOIN besoins b ON b.id = c.id_besoin
         WHERE c.id = ?'
    );
    $requete->execute([$id]);
    $ligne = $requete->fetch();

    return $ligne ?: null;
}

function besoins_du_partenaire(PDO $pdo, int $idPartenaire): array
{
    $requete = $pdo->prepare(
        'SELECT b.id, b.titre, b.secteur, b.statut, b.date_depot,
                (SELECT COUNT(*) FROM candidatures c WHERE c.id_besoin = b.id) AS nb_candidatures,
                (SELECT COUNT(*) FROM candidatures c WHERE c.id_besoin = b.id AND c.statut = "en_attente") AS nb_attente
         FROM besoins b
         WHERE b.id_partenaire = ?
         ORDER BY b.date_depot DESC'
    );
    $requete->execute([$idPartenaire]);

    return $requete->fetchAll();
}

function besoins_en_attente(PDO $pdo): array
{
    $requete = $pdo->query(
        'SELECT b.*, u.nom_structure, f.sigle
         FROM besoins b
         JOIN utilisateurs u ON u.id = b.id_partenaire
         LEFT JOIN facultes f ON f.id = b.id_faculte
         WHERE b.statut = "en_attente"
         ORDER BY b.date_depot ASC'
    );

    return $requete->fetchAll();
}

function moderer_besoin(PDO $pdo, int $id, string $statut, int $idAdmin): void
{
    $requete = $pdo->prepare(
        'UPDATE besoins SET statut = ?, id_moderateur = ?, date_decision = NOW() WHERE id = ?'
    );
    $requete->execute([$statut, $idAdmin, $id]);
}

function chiffres_admin(PDO $pdo): array
{
    $ligne = $pdo->query(
        'SELECT
            (SELECT COUNT(*) FROM utilisateurs WHERE role = "etudiant")   AS etudiants,
            (SELECT COUNT(*) FROM utilisateurs WHERE role = "partenaire") AS partenaires,
            (SELECT COUNT(*) FROM besoins WHERE statut = "en_attente")    AS a_moderer,
            (SELECT COUNT(*) FROM besoins WHERE statut = "valide")        AS publies,
            (SELECT COUNT(*) FROM projets)                                AS projets,
            (SELECT COUNT(*) FROM candidatures)                           AS candidatures'
    )->fetch();

    return $ligne;
}

function liste_utilisateurs(PDO $pdo, string $role): array
{
    $conditions = [];
    $valeurs = [];

    if ($role !== '') {
        $conditions[] = 'u.role = ?';
        $valeurs[] = $role;
    }

    $sql = 'SELECT u.id, u.nom, u.prenom, u.email, u.role, u.actif,
                   u.nom_structure, u.date_inscription, f.sigle
            FROM utilisateurs u
            LEFT JOIN facultes f ON f.id = u.id_faculte';
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY u.date_inscription DESC';

    $requete = $pdo->prepare($sql);
    $requete->execute($valeurs);

    return $requete->fetchAll();
}

function changer_activation_utilisateur(PDO $pdo, int $id, int $actif): void
{
    $requete = $pdo->prepare('UPDATE utilisateurs SET actif = ? WHERE id = ?');
    $requete->execute([$actif, $id]);
}

function dernieres_candidatures(PDO $pdo, int $limite): array
{
    $requete = $pdo->query(
        'SELECT c.date_candidature, c.statut, b.titre,
                u.id AS id_etudiant, u.prenom, u.nom
         FROM candidatures c
         JOIN besoins b ON b.id = c.id_besoin
         JOIN utilisateurs u ON u.id = c.id_etudiant
         ORDER BY c.date_candidature DESC
         LIMIT ' . (int)$limite
    );

    return $requete->fetchAll();
}

function ajouter_notification(PDO $pdo, int $idUtilisateur, string $titre, string $texte, string $lien): void
{
    $requete = $pdo->prepare(
        'INSERT INTO notifications (id_utilisateur, titre, message, lien) VALUES (?, ?, ?, ?)'
    );
    $requete->execute([$idUtilisateur, $titre, $texte, $lien]);
}

function notifications_de(PDO $pdo, int $idUtilisateur): array
{
    $requete = $pdo->prepare(
        'SELECT * FROM notifications WHERE id_utilisateur = ? ORDER BY date_creation DESC LIMIT 40'
    );
    $requete->execute([$idUtilisateur]);

    return $requete->fetchAll();
}

function compter_notifications_non_lues(PDO $pdo, int $idUtilisateur): int
{
    $requete = $pdo->prepare(
        'SELECT COUNT(*) FROM notifications WHERE id_utilisateur = ? AND lu = 0'
    );
    $requete->execute([$idUtilisateur]);

    return (int)$requete->fetchColumn();
}

function marquer_notifications_lues(PDO $pdo, int $idUtilisateur): void
{
    $requete = $pdo->prepare('UPDATE notifications SET lu = 1 WHERE id_utilisateur = ?');
    $requete->execute([$idUtilisateur]);
}

function projets_de_etudiant(PDO $pdo, int $idEtudiant): array
{
    $requete = $pdo->prepare(
        'SELECT * FROM projets WHERE id_etudiant = ? ORDER BY date_publication DESC'
    );
    $requete->execute([$idEtudiant]);

    return $requete->fetchAll();
}

function supprimer_projet(PDO $pdo, int $id, int $idEtudiant): void
{
    $requete = $pdo->prepare('DELETE FROM projets WHERE id = ? AND id_etudiant = ?');
    $requete->execute([$id, $idEtudiant]);
}

function formations_de(PDO $pdo, int $idEtudiant): array
{
    $requete = $pdo->prepare(
        'SELECT * FROM formations WHERE id_etudiant = ?
         ORDER BY annee_fin IS NULL DESC, annee_fin DESC, annee_debut DESC'
    );
    $requete->execute([$idEtudiant]);

    return $requete->fetchAll();
}

function ajouter_formation(PDO $pdo, array $donnees): void
{
    $requete = $pdo->prepare(
        'INSERT INTO formations (id_etudiant, diplome, etablissement, ville, annee_debut, annee_fin, mention, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $requete->execute([
        $donnees['id_etudiant'],
        $donnees['diplome'],
        $donnees['etablissement'],
        $donnees['ville'],
        $donnees['annee_debut'],
        $donnees['annee_fin'],
        $donnees['mention'],
        $donnees['description'],
    ]);
}

function experiences_de(PDO $pdo, int $idEtudiant): array
{
    $requete = $pdo->prepare('SELECT * FROM experiences WHERE id_etudiant = ? ORDER BY id DESC');
    $requete->execute([$idEtudiant]);

    return $requete->fetchAll();
}

function ajouter_experience(PDO $pdo, array $donnees): void
{
    $requete = $pdo->prepare(
        'INSERT INTO experiences (id_etudiant, poste, organisation, lieu, periode, description)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $requete->execute([
        $donnees['id_etudiant'],
        $donnees['poste'],
        $donnees['organisation'],
        $donnees['lieu'],
        $donnees['periode'],
        $donnees['description'],
    ]);
}

function competences_de(PDO $pdo, int $idEtudiant): array
{
    $requete = $pdo->prepare(
        'SELECT * FROM competences WHERE id_etudiant = ? ORDER BY categorie, nom'
    );
    $requete->execute([$idEtudiant]);

    return $requete->fetchAll();
}

function ajouter_competence(PDO $pdo, int $idEtudiant, string $nom, string $categorie): void
{
    $requete = $pdo->prepare(
        'INSERT IGNORE INTO competences (id_etudiant, nom, categorie) VALUES (?, ?, ?)'
    );
    $requete->execute([$idEtudiant, $nom, $categorie]);
}

function supprimer_ligne_cv(PDO $pdo, string $table, int $id, int $idEtudiant): void
{
    if (!in_array($table, ['formations', 'experiences', 'competences'], true)) {
        return;
    }

    $requete = $pdo->prepare('DELETE FROM ' . $table . ' WHERE id = ? AND id_etudiant = ?');
    $requete->execute([$id, $idEtudiant]);
}
