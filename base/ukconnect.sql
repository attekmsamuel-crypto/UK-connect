-- UK-Connect — base de données
-- Université de Kara
--
-- Import : phpMyAdmin > Importer, ou en ligne de commande
--          mysql -u root --default-character-set=utf8mb4 < ukconnect.sql
--
-- Le script supprime la base existante avant de la recréer.

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS ukconnect;
CREATE DATABASE ukconnect DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ukconnect;


-- --------------------------------------------------------
-- Facultés, instituts et écoles de l'université
-- --------------------------------------------------------
CREATE TABLE facultes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_faculte VARCHAR(150) NOT NULL,
    sigle       VARCHAR(20)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_facultes_sigle (sigle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Comptes : étudiants, partenaires, administrateurs
-- --------------------------------------------------------
CREATE TABLE utilisateurs (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom              VARCHAR(100) NOT NULL,
    prenom           VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL,
    telephone        VARCHAR(30)  DEFAULT NULL,
    mot_de_passe     VARCHAR(255) NOT NULL,
    role             ENUM('etudiant','partenaire','admin') NOT NULL DEFAULT 'etudiant',
    id_faculte       INT UNSIGNED DEFAULT NULL,
    nom_structure    VARCHAR(150) DEFAULT NULL,
    niveau_etudes    VARCHAR(60)  DEFAULT NULL,
    photo            VARCHAR(255) DEFAULT NULL,
    banniere         VARCHAR(255) DEFAULT NULL,
    bio              TEXT         DEFAULT NULL,
    actif            TINYINT(1)   NOT NULL DEFAULT 1,
    date_inscription DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_utilisateurs_email (email),
    KEY idx_utilisateurs_role (role),
    CONSTRAINT fk_utilisateurs_faculte FOREIGN KEY (id_faculte)
        REFERENCES facultes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Besoins déposés par les partenaires
-- Un besoin n'est visible du public qu'une fois validé par un administrateur.
-- --------------------------------------------------------
CREATE TABLE besoins (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_partenaire INT UNSIGNED NOT NULL,
    titre         VARCHAR(200) NOT NULL,
    description   TEXT         NOT NULL,
    secteur       VARCHAR(100) NOT NULL,
    id_faculte    INT UNSIGNED DEFAULT NULL,
    statut        ENUM('en_attente','valide','rejete') NOT NULL DEFAULT 'en_attente',
    id_moderateur INT UNSIGNED DEFAULT NULL,
    date_depot    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_decision DATETIME     DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_besoins_statut (statut),
    KEY idx_besoins_partenaire (id_partenaire),
    CONSTRAINT fk_besoins_partenaire FOREIGN KEY (id_partenaire)
        REFERENCES utilisateurs (id) ON DELETE CASCADE,
    CONSTRAINT fk_besoins_faculte FOREIGN KEY (id_faculte)
        REFERENCES facultes (id) ON DELETE SET NULL,
    CONSTRAINT fk_besoins_moderateur FOREIGN KEY (id_moderateur)
        REFERENCES utilisateurs (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Projets et mémoires publiés par les étudiants
-- --------------------------------------------------------
CREATE TABLE projets (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_etudiant      INT UNSIGNED NOT NULL,
    titre            VARCHAR(200) NOT NULL,
    departement      VARCHAR(150) DEFAULT NULL,
    resume           TEXT         NOT NULL,
    technologies     VARCHAR(255) DEFAULT NULL,
    fichier_pdf      VARCHAR(255) DEFAULT NULL,
    date_publication DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_projets_etudiant (id_etudiant),
    CONSTRAINT fk_projets_etudiant FOREIGN KEY (id_etudiant)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Candidatures : un étudiant se positionne sur un besoin
-- Un seul dossier par étudiant et par besoin.
-- --------------------------------------------------------
CREATE TABLE candidatures (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_besoin        INT UNSIGNED NOT NULL,
    id_etudiant      INT UNSIGNED NOT NULL,
    motivation       TEXT         DEFAULT NULL,
    statut           ENUM('en_attente','retenue','non_retenue') NOT NULL DEFAULT 'en_attente',
    date_candidature DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_decision    DATETIME     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_candidature (id_besoin, id_etudiant),
    KEY idx_candidatures_etudiant (id_etudiant),
    CONSTRAINT fk_candidatures_besoin FOREIGN KEY (id_besoin)
        REFERENCES besoins (id) ON DELETE CASCADE,
    CONSTRAINT fk_candidatures_etudiant FOREIGN KEY (id_etudiant)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Notifications internes
-- --------------------------------------------------------
CREATE TABLE notifications (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_utilisateur INT UNSIGNED NOT NULL,
    titre          VARCHAR(150) NOT NULL,
    message        VARCHAR(255) NOT NULL,
    lien           VARCHAR(255) DEFAULT NULL,
    lu             TINYINT(1)   NOT NULL DEFAULT 0,
    date_creation  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_destinataire (id_utilisateur, lu),
    CONSTRAINT fk_notifications_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Le CV de l'étudiant : formations, expériences, compétences
-- --------------------------------------------------------
CREATE TABLE formations (
    id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    id_etudiant   INT UNSIGNED      NOT NULL,
    diplome       VARCHAR(150)      NOT NULL,
    etablissement VARCHAR(150)      NOT NULL,
    ville         VARCHAR(100)      DEFAULT NULL,
    annee_debut   SMALLINT UNSIGNED DEFAULT NULL,
    annee_fin     SMALLINT UNSIGNED DEFAULT NULL,
    mention       VARCHAR(60)       DEFAULT NULL,
    description   TEXT              DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_formations_etudiant (id_etudiant),
    CONSTRAINT fk_formations_etudiant FOREIGN KEY (id_etudiant)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE experiences (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_etudiant  INT UNSIGNED NOT NULL,
    poste        VARCHAR(150) NOT NULL,
    organisation VARCHAR(150) NOT NULL,
    lieu         VARCHAR(100) DEFAULT NULL,
    periode      VARCHAR(60)  DEFAULT NULL,
    description  TEXT         DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_experiences_etudiant (id_etudiant),
    CONSTRAINT fk_experiences_etudiant FOREIGN KEY (id_etudiant)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competences (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_etudiant INT UNSIGNED NOT NULL,
    nom         VARCHAR(60)  NOT NULL,
    categorie   VARCHAR(40)  NOT NULL DEFAULT 'Outil',
    PRIMARY KEY (id),
    UNIQUE KEY uk_competence (id_etudiant, nom),
    CONSTRAINT fk_competences_etudiant FOREIGN KEY (id_etudiant)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================================
-- Données
-- ========================================================

-- Les neuf entités de l'Université de Kara
INSERT INTO facultes (id, nom_faculte, sigle) VALUES
(1, 'Faculté des Lettres et Sciences Humaines', 'FLESH'),
(2, 'Faculté des Sciences Économiques et de Gestion', 'FASEG'),
(3, 'Faculté de Droit et des Sciences Politiques', 'FDSP'),
(4, 'Faculté des Sciences et Techniques', 'FAST'),
(5, 'Faculté des Sciences de la Santé', 'FSS'),
(6, 'Institut de Formation en Sciences Pédagogiques et Administration Universitaire', 'ISPAU'),
(7, 'Institut Supérieur des Métiers Agricoles', 'ISMA'),
(8, 'École Polytechnique de Kara', 'EPK'),
(9, 'Centre d''Excellence en Production Durable des Cultures', 'CEPRODUC');


-- Comptes de démonstration. Mot de passe commun : UkConnect2026
SET @mdp = '$2y$10$PafZqjLDbrw5TJEPc0LX/OI/qF3itAdScz5OCfknIUqX3jrTL0Aea';

INSERT INTO utilisateurs (id, nom, prenom, email, telephone, mot_de_passe, role, id_faculte, nom_structure, niveau_etudes, bio) VALUES
(1, 'TCHAKOURA', 'Aïcha', 'admin@ukconnect.tg', '+228 90 00 00 01', @mdp, 'admin', NULL, NULL, NULL,
 'Direction des études — administration de la plateforme.'),

(2, 'ABLOGAN', 'Kossi', 'contact@agrotogo.tg', '+228 90 11 22 33', @mdp, 'partenaire', NULL, 'Agro-Togo SARL', NULL,
 'Coopérative de transformation et de commercialisation de produits agricoles dans la région de la Kara.'),
(3, 'DJOSSOU', 'Farida', 'contact@espoirjeunesse.tg', '+228 91 44 55 66', @mdp, 'partenaire', NULL, 'ONG Espoir Jeunesse', NULL,
 'Accompagnement à l''entrepreneuriat des jeunes de la préfecture de la Kozah.'),
(4, 'AMOUZOU', 'Edem', 'sg@mairie-kara.tg', '+228 90 55 66 77', @mdp, 'partenaire', NULL, 'Mairie de Kara', NULL,
 'Services techniques de la commune de Kara.'),

(5, 'SALAMI', 'Yao', 'yao.salami@etu-univkara.tg', '+228 92 10 20 30', @mdp, 'etudiant', 4, NULL, 'Licence 3 — Génie logiciel',
 'Étudiant en génie logiciel. Je développe surtout des applications mobiles et des outils de collecte de données de terrain.'),
(6, 'KOUMI', 'Abra', 'abra.koumi@etu-univkara.tg', '+228 92 40 50 60', @mdp, 'etudiant', 2, NULL, 'Master 1 — Gestion de projets',
 'Étudiante en gestion, intéressée par le financement des petites entreprises et le suivi-évaluation de projets.'),
(7, 'BANDJAO', 'Léa', 'lea.bandjao@etu-univkara.tg', '+228 93 70 80 90', @mdp, 'etudiant', 5, NULL, 'Licence 3 — Sciences infirmières',
 'Étudiante en sciences de la santé, orientée nutrition communautaire et santé maternelle.'),
(8, 'PIDABIYA', 'Komlan', 'komlan.pidabiya@etu-univkara.tg', '+228 91 22 33 44', @mdp, 'etudiant', 3, NULL, 'Master 2 — Droit public',
 'Étudiant en droit public, mémoire en cours sur la décentralisation et les finances locales.');


-- Dates d'inscription des comptes de démonstration
UPDATE utilisateurs SET date_inscription = '2026-05-12 09:00:00' WHERE id = 1;
UPDATE utilisateurs SET date_inscription = '2026-06-24 11:20:00' WHERE id = 2;
UPDATE utilisateurs SET date_inscription = '2026-07-03 15:40:00' WHERE id = 3;
UPDATE utilisateurs SET date_inscription = '2026-07-19 08:55:00' WHERE id = 4;
UPDATE utilisateurs SET date_inscription = '2026-02-17 10:05:00' WHERE id = 5;
UPDATE utilisateurs SET date_inscription = '2026-03-08 16:30:00' WHERE id = 6;
UPDATE utilisateurs SET date_inscription = '2026-04-21 09:45:00' WHERE id = 7;
UPDATE utilisateurs SET date_inscription = '2026-06-05 14:10:00' WHERE id = 8;

INSERT INTO besoins (id, id_partenaire, titre, description, secteur, id_faculte, statut, id_moderateur, date_depot, date_decision) VALUES
(1, 2, 'Application mobile de suivi des exploitants agricoles',
 'Nous travaillons avec plus de 400 exploitants dont la production, les surfaces cultivées et les rendements sont encore suivis sur papier. Nous cherchons une application mobile Android, utilisable sans connexion permanente, qui permette de saisir les données d''exploitation sur le terrain, de suivre les campagnes et de sortir des rapports pour le conseil agricole.\n\nLe travail attendu comprend l''analyse des besoins avec nos animateurs, la conception de la base de données et une première version fonctionnelle testée sur deux cantons.',
 'Agriculture', 4, 'valide', 1, '2026-08-18 09:15:00', '2026-08-20 10:30:00'),

(2, 3, 'Gestion des adhérents et des cotisations d''une ONG',
 'L''ONG suit environ 1 200 adhérents avec plusieurs fichiers Excel qui ne sont jamais à jour en même temps. Nous souhaitons une application web pour gérer les adhérents, enregistrer les cotisations, éditer les reçus et disposer d''un tableau de bord pour le bureau.\n\nUne attention particulière est demandée sur la reprise des données existantes et sur la formation des deux secrétaires qui utiliseront l''outil au quotidien.',
 'Gestion', 2, 'valide', 1, '2026-08-21 11:45:00', '2026-08-22 14:00:00'),

(3, 4, 'Étude sur la gestion des déchets ménagers du centre-ville',
 'La commune souhaite disposer d''un état des lieux sérieux de la collecte des déchets ménagers dans les quartiers du centre : volumes, circuits, points de regroupement sauvages, coût réel du ramassage.\n\nLe travail peut prendre la forme d''un mémoire, avec enquête de terrain et cartographie des points noirs, et doit déboucher sur des recommandations chiffrées pour le prochain budget communal.',
 'Environnement', 1, 'valide', 1, '2026-08-23 08:00:00', '2026-08-24 09:30:00'),

(4, 2, 'Traçabilité des intrants agricoles par code QR',
 'Nous voudrions suivre les semences et les engrais distribués aux exploitants jusqu''à la récolte, par étiquetage et lecture de codes QR sur téléphone. L''objectif est de savoir précisément ce qui a été distribué, à qui, et avec quel résultat.',
 'Agriculture', 4, 'en_attente', NULL, '2026-08-26 16:20:00', NULL),

(5, 4, 'Numérisation des actes d''état civil de la commune',
 'Les registres d''état civil sont uniquement sur papier. Nous cherchons une solution de numérisation et d''indexation qui permette de retrouver un acte en quelques secondes, avec un contrôle strict des accès.',
 'Administration', 3, 'en_attente', NULL, '2026-08-29 10:05:00', NULL),

(6, 3, 'Application de covoiturage entre campus',
 'Idée de mise en relation des étudiants pour partager les trajets Lomé — Kara pendant les vacances universitaires.',
 'Transport', NULL, 'rejete', 1, '2026-08-24 13:10:00', '2026-08-25 09:00:00');


INSERT INTO projets (id, id_etudiant, titre, departement, resume, technologies, fichier_pdf, date_publication) VALUES
(1, 5, 'AgriSuivi — application de suivi des pépinières villageoises',
 'Génie logiciel',
 'Application Android de suivi de croissance des pépinières : photos horodatées, relevés de mesures, alertes d''arrosage. Développée comme projet de licence et testée sur douze pépinières de la préfecture de la Kozah pendant une saison complète.\n\nLe suivi régulier permis par l''application a fait reculer la mortalité des plants de 23 % sur les sites tests par rapport aux sites témoins. L''outil est aujourd''hui repris par deux groupements de producteurs.',
 'Android, Kotlin, SQLite, GPS', NULL, '2026-07-10 10:00:00'),

(2, 6, 'La gestion financière des petites entreprises du marché central de Kara',
 'Sciences de gestion',
 'Mémoire de licence portant sur les pratiques comptables de quarante commerçantes et commerçants du marché central de Kara.\n\nL''enquête montre que la caisse du commerce et la caisse du ménage ne sont pratiquement jamais séparées, ce qui rend impossible tout calcul de rentabilité. Le mémoire propose un modèle de registre simplifié, testé sur huit commerces pendant trois mois, adapté aux personnes non bancarisées et peu familières de l''écrit.',
 'Enquête de terrain, questionnaire, Excel', NULL, '2026-07-25 15:30:00'),

(3, 7, 'Cartographie nutritionnelle des cantines scolaires de la Kara',
 'Nutrition publique',
 'Projet d''initiative étudiante ayant recensé l''offre alimentaire de dix-huit cantines scolaires de la région.\n\nLe travail propose des menus équilibrés construits à partir de produits locaux — soja, moringa, igname — pour un coût de revient inférieur à 150 FCFA par repas. Trois écoles ont adopté les menus proposés à la rentrée suivante.',
 'Enquête, QGIS, planification alimentaire', NULL, '2026-08-05 08:45:00'),

(4, 8, 'Les finances locales des communes issues de la décentralisation',
 'Droit public',
 'Mémoire de master analysant les ressources propres de quatre communes de la région de la Kara depuis la réforme de la décentralisation.\n\nL''étude met en évidence l''écart entre les compétences transférées et les moyens effectivement mobilisés, et documente les marges de manœuvre fiscales réellement utilisables par les communes.',
 'Analyse documentaire, entretiens, données budgétaires', NULL, '2026-08-12 14:20:00'),

(5, 5, 'Plateforme de gestion des stages de la FAST',
 'Génie logiciel',
 'Projet de groupe réalisé en deuxième année : une application web permettant aux étudiants de déposer leurs conventions de stage, aux enseignants de les valider et au secrétariat de suivre l''ensemble des dossiers.\n\nL''application a servi de support au traitement de 90 conventions lors de la campagne de stages 2025.',
 'PHP, MySQL, Bootstrap', NULL, '2026-08-20 11:00:00');


INSERT INTO candidatures (id, id_besoin, id_etudiant, motivation, statut, date_candidature, date_decision) VALUES
(1, 1, 5, 'J''ai déjà développé une application de suivi de pépinières qui fonctionne hors connexion, avec synchronisation différée. Le problème décrit ici est très proche et je connais le terrain de la Kozah.', 'retenue', '2026-08-19 08:20:00', '2026-08-23 17:00:00'),
(2, 1, 6, 'Je souhaite travailler sur la partie analyse des besoins et sur les indicateurs de suivi des campagnes.', 'non_retenue', '2026-08-19 12:05:00', '2026-08-23 17:05:00'),
(3, 2, 6, 'Mon mémoire porte sur le suivi-évaluation dans les organisations associatives. Reprendre des données existantes et former les utilisatrices fait partie de ce que je sais faire.', 'retenue', '2026-08-22 09:40:00', '2026-08-27 10:15:00'),
(4, 2, 7, 'Je m''intéresse à la gestion des adhérents dans les structures de santé communautaire et je voudrais élargir à une ONG.', 'en_attente', '2026-08-23 14:25:00', NULL),
(5, 3, 8, 'Sujet directement lié à mon mémoire sur les finances locales : le coût réel du ramassage des déchets est l''un des postes que j''étudie.', 'en_attente', '2026-08-25 09:10:00', NULL);


INSERT INTO notifications (id_utilisateur, titre, message, lien, lu, date_creation) VALUES
(2, 'Nouvelle candidature', 'Un étudiant a postulé sur : Application mobile de suivi des exploitants agricoles', 'espace-partenaire.php', 1, '2026-08-19 08:20:00'),
(5, 'Candidature retenue', 'Votre candidature sur : Application mobile de suivi des exploitants agricoles a été retenue', 'mes-candidatures.php', 0, '2026-08-23 17:00:00'),
(6, 'Candidature retenue', 'Votre candidature sur : Gestion des adhérents et des cotisations d''une ONG a été retenue', 'mes-candidatures.php', 0, '2026-08-27 10:15:00'),
(3, 'Nouvelle candidature', 'Un étudiant a postulé sur : Gestion des adhérents et des cotisations d''une ONG', 'espace-partenaire.php', 0, '2026-08-23 14:25:00'),
(4, 'Besoin publié', 'Votre besoin : Étude sur la gestion des déchets ménagers du centre-ville est en ligne', 'espace-partenaire.php', 0, '2026-08-24 09:30:00');


INSERT INTO formations (id_etudiant, diplome, etablissement, ville, annee_debut, annee_fin, mention, description) VALUES
(5, 'Licence en génie logiciel', 'FAST — Université de Kara', 'Kara', 2023, 2026, 'Bien', 'Développement logiciel, bases de données, applications mobiles.'),
(5, 'Baccalauréat série D', 'Lycée municipal de Kara', 'Kara', 2022, 2023, NULL, NULL),
(6, 'Master en gestion de projets', 'FASEG — Université de Kara', 'Kara', 2024, NULL, NULL, 'Suivi-évaluation et gestion de projets communautaires.'),
(6, 'Licence en sciences de gestion', 'FASEG — Université de Kara', 'Kara', 2021, 2024, 'Assez bien', NULL),
(7, 'Licence en sciences infirmières', 'FSS — Université de Kara', 'Kara', 2022, 2025, 'Très bien', 'Stages cliniques au CHR de Kara, santé communautaire en milieu rural.'),
(8, 'Master en droit public', 'FDSP — Université de Kara', 'Kara', 2024, NULL, NULL, 'Collectivités territoriales et finances publiques.');

INSERT INTO experiences (id_etudiant, poste, organisation, lieu, periode, description) VALUES
(5, 'Stagiaire développeur web', 'Agro-Togo SARL', 'Lomé', 'juin — août 2025', 'Tableau de bord de suivi des commandes en PHP et MySQL, puis formation des équipes au back-office.'),
(5, 'Bénévole', 'ONG Espoir Jeunesse', 'Kara', 'depuis 2024', 'Ateliers d''initiation à l''informatique pour une quarantaine de jeunes, maintenance du parc informatique.'),
(6, 'Assistante suivi-évaluation (stage)', 'Plan International Togo', 'Sokodé', 'février — juillet 2025', 'Collecte de données sur tablette, analyse des indicateurs, rédaction des rapports trimestriels.'),
(7, 'Stagiaire infirmière', 'CHR de Kara', 'Kara', 'novembre 2024 — février 2025', 'Soins généraux et urgences, campagne de sensibilisation à la santé maternelle.'),
(8, 'Stagiaire', 'Mairie de Kara — service des affaires juridiques', 'Kara', 'juillet — septembre 2025', 'Rédaction d''actes administratifs et préparation des délibérations du conseil municipal.');

INSERT INTO competences (id_etudiant, nom, categorie) VALUES
(5, 'PHP', 'Langage'), (5, 'MySQL', 'Outil'), (5, 'JavaScript', 'Langage'),
(5, 'HTML et CSS', 'Langage'), (5, 'Kotlin', 'Langage'), (5, 'Git', 'Outil'),
(6, 'Gestion de projet', 'Métier'), (6, 'Suivi-évaluation', 'Métier'),
(6, 'Excel', 'Outil'), (6, 'Anglais professionnel', 'Langue'),
(7, 'Soins d''urgence', 'Métier'), (7, 'Santé communautaire', 'Métier'), (7, 'Écoute active', 'Transversale'),
(8, 'Rédaction juridique', 'Métier'), (8, 'Analyse budgétaire', 'Métier');
