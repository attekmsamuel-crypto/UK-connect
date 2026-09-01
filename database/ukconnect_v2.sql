-- ============================================================================
--  UK-CONNECT — BASE DE DONNÉES « ukconnect » — VERSION 2.2
-- ============================================================================
--  Plateforme de mise en relation Étudiants ↔ Partenaires — Université de Kara
--
--  Cible ............ : MySQL 8.0+ / InnoDB (validé sur MariaDB 11.8)
--  Jeu de caractères  : utf8mb4 / utf8mb4_unicode_ci (i18n complète)
--  Auteur ............ : Audit & refonte — architecture de données
--  Date .............. : 2026-08-28
--
--  ÉVOLUTIONS v1 → v2 (les 7 correctifs de l'audit) :
--    1. Anti-doublon métier      : UNIQUE (id_sujet, id_etudiant) sur candidatures
--    1b. Notifications internes  : table notifications + 3 triggers auto (v2.1)
--    2. Modération tracée        : valide_par + date_validation sur besoins_sujets
--    3. ON DELETE explicites     : RESTRICT / CASCADE / SET NULL par relation
--                                  + suppression douce (utilisateurs.actif)
--    4. ENUM remplacés           : tables de référence roles, statuts_validation,
--                                  statuts_candidature (extensibles sans ALTER)
--    5. Index de recherche       : index composites + FULLTEXT (titres, résumés)
--    6. Règles métier en base    : contraintes CHECK + triggers (rôle ↔ faculté,
--                                  partenaire ↔ structure, modération par un admin)
--    7. Exploitation             : date_modification auto, compte applicatif à
--                                  privilèges minimaux, plan de sauvegarde documenté
--    8. CV étudiant (v2.2)       : profil_formations / profil_experiences /
--                                  profil_competences + utilisateurs.niveau_etudes
--
--  NOTE FACULTÉS : les 9 entités officielles de l'Université de Kara (affiche
--  « Offres de formation 2026-2027 ») sont pré-chargées : 5 facultés (FLESH,
--  FASEG, FDSP, FAST, FSS), 2 instituts (ISPAU, ISMA), 1 école (EPK) et
--  1 centre d'excellence (CEPRODUC).
-- ============================================================================


-- ============================================================================
-- SECTION 1 — CRÉATION DE LA BASE
-- ============================================================================
DROP DATABASE IF EXISTS ukconnect;
CREATE DATABASE ukconnect
  DEFAULT CHARSET = utf8mb4
  COLLATE         = utf8mb4_unicode_ci;
USE ukconnect;


-- ============================================================================
-- SECTION 2 — TABLES DE RÉFÉRENCE (remplacent les ENUM v1)
-- ----------------------------------------------------------------------------
--  Pourquoi : un ENUM impose un ALTER TABLE (verrou) pour ajouter une valeur,
--  n'est pas internationalisable et se compare par index interne fragile.
--  Une table de référence est extensible en production, documentée (libellé
--  lisible) et requêtable (SELECT * FROM roles).
-- ============================================================================
CREATE TABLE roles (
    id       TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du rôle',
    code     VARCHAR(20)      NOT NULL                COMMENT 'Code technique (utilisé dans le code PHP)',
    libelle  VARCHAR(60)      NOT NULL                COMMENT 'Libellé lisible affiché dans l''UI',
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rôles applicatifs (remplace ENUM type_role)';


CREATE TABLE statuts_validation (
    id       TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code     VARCHAR(20)      NOT NULL COMMENT 'Code technique (en_attente | valide | rejete)',
    libelle  VARCHAR(60)      NOT NULL COMMENT 'Libellé affiché',
    PRIMARY KEY (id),
    UNIQUE KEY uq_statuts_validation_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Statuts de modération des sujets (remplace ENUM statut_validation)';


CREATE TABLE statuts_candidature (
    id       TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code     VARCHAR(20)      NOT NULL COMMENT 'Code technique (en_attente | retenue | non_retenue)',
    libelle  VARCHAR(60)      NOT NULL COMMENT 'Libellé affiché',
    PRIMARY KEY (id),
    UNIQUE KEY uq_statuts_candidature_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Statuts de suivi des candidatures (remplace ENUM statut)';


-- ============================================================================
-- SECTION 3 — TABLES MÉTIER
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 3.1 facultes — référentiel des facultés / instituts
-- ----------------------------------------------------------------------------
CREATE TABLE facultes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la faculté',
    nom_faculte VARCHAR(150) NOT NULL                COMMENT 'Intitulé complet de la faculté / institut',
    sigle       VARCHAR(20)  NOT NULL                COMMENT 'Sigle officiel (FLESH, FASEG…)',
    PRIMARY KEY (id),
    UNIQUE KEY uq_facultes_sigle (sigle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Référentiel des facultés et instituts de l''Université de Kara';


-- ----------------------------------------------------------------------------
-- 3.2 utilisateurs — comptes étudiants / partenaires / administrateurs
-- ----------------------------------------------------------------------------
CREATE TABLE utilisateurs (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique du compte',
    nom               VARCHAR(100)  NOT NULL                COMMENT 'Nom de famille',
    prenom            VARCHAR(100)  NOT NULL                COMMENT 'Prénom',
    email             VARCHAR(150)  NOT NULL                COMMENT 'Identifiant de connexion (unique)',
    telephone         VARCHAR(20)   NULL                    COMMENT 'Contact visible aux partenaires',
    mot_de_passe      VARCHAR(255)  NOT NULL                COMMENT 'Hachage password_hash (bcrypt/argon2) — JAMAIS en clair',
    role_id           TINYINT UNSIGNED NOT NULL             COMMENT 'Rôle du compte → roles(id)',
    id_faculte        INT UNSIGNED  NULL                    COMMENT 'Faculté de rattachement (étudiants)',
    nom_structure     VARCHAR(150)  NULL                    COMMENT 'Raison sociale (partenaires uniquement)',
    niveau_etudes     VARCHAR(60)   NULL                    COMMENT 'Niveau d''études actuel (Licence 3, Master 1, Ingénieur…)',
    photo_profil      VARCHAR(255)  NULL                    COMMENT 'Nom du fichier avatar',
    banniere_profil   VARCHAR(255)  NULL                    COMMENT 'Nom du fichier bannière de couverture',
    bio               TEXT          NULL                    COMMENT 'Présentation libre du profil',
    actif             BOOLEAN       NOT NULL DEFAULT TRUE   COMMENT 'Suppression douce : FALSE = compte désactivé',
    date_creation     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Création du compte',
    date_modification TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP COMMENT 'Dernière mise à jour (auto)',
    PRIMARY KEY (id),
    UNIQUE KEY uq_utilisateurs_email (email),
    KEY idx_utilisateurs_role (role_id),
    CONSTRAINT fk_utilisateurs_role     FOREIGN KEY (role_id)   REFERENCES roles(id)
                                        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_utilisateurs_faculte  FOREIGN KEY (id_faculte) REFERENCES facultes(id)
                                        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_utilisateurs_email   CHECK (email LIKE '%_@_%._%'),
    CONSTRAINT chk_utilisateurs_mdp     CHECK (CHAR_LENGTH(mot_de_passe) >= 20)
                                        -- un hachage réel (bcrypt = 60 car., argon2 > 90)
                                        -- ne fait jamais moins de 20 caractères
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Comptes utilisateurs : étudiants, partenaires, administrateurs';


-- ----------------------------------------------------------------------------
-- 3.3 besoins_sujets — problématiques déposées par les partenaires
-- ----------------------------------------------------------------------------
CREATE TABLE besoins_sujets (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du sujet',
    id_partenaire        INT UNSIGNED NOT NULL                COMMENT 'Partenaire déposant → utilisateurs(id)',
    titre_sujet          VARCHAR(200) NOT NULL                COMMENT 'Titre de la problématique',
    description_probleme TEXT         NOT NULL                COMMENT 'Description détaillée du besoin',
    secteur_filiere      VARCHAR(100) NOT NULL                COMMENT 'Secteur / filière concerné',
    id_faculte           INT UNSIGNED NULL                    COMMENT 'Faculté suggérée / confirmée par l''admin',
    statut_id            TINYINT UNSIGNED NOT NULL DEFAULT 1  COMMENT 'Modération → statuts_validation(id) (1 = en_attente)',
    valide_par           INT UNSIGNED NULL                    COMMENT 'Admin ayant tranché → utilisateurs(id)',
    date_validation      DATETIME     NULL                    COMMENT 'Horodatage de la décision de modération',
    date_creation        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Dépôt du sujet',
    date_modification    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP COMMENT 'Dernière mise à jour (auto)',
    PRIMARY KEY (id),
    KEY idx_besoins_partenaire (id_partenaire),
    KEY idx_besoins_statut     (statut_id, id),      -- flux de modération + listing public trié
    KEY idx_besoins_secteur    (secteur_filiere),
    KEY idx_besoins_faculte    (id_faculte),
    CONSTRAINT fk_besoins_partenaire  FOREIGN KEY (id_partenaire)    REFERENCES utilisateurs(id)
                                      ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_besoins_faculte     FOREIGN KEY (id_faculte)       REFERENCES facultes(id)
                                      ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_besoins_statut      FOREIGN KEY (statut_id)        REFERENCES statuts_validation(id)
                                      ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_besoins_valide_par  FOREIGN KEY (valide_par)       REFERENCES utilisateurs(id)
                                      ON DELETE SET NULL ON UPDATE CASCADE
    -- NB : la règle « décision signée ET datée » (chk) est portée par les
    -- triggers trg_besoins_bi/bu (section 4.2), car certains moteurs
    -- (MariaDB) interdisent les CHECK référençant une colonne porteuse de FK.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Besoins / sujets déposés par les partenaires, avec modération admin';


-- ----------------------------------------------------------------------------
-- 3.4 projets_etudiants — portfolio des mémoires et projets publiés
-- ----------------------------------------------------------------------------
CREATE TABLE projets_etudiants (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du projet',
    id_etudiant         INT UNSIGNED NOT NULL                COMMENT 'Auteur → utilisateurs(id)',
    titre_projet        VARCHAR(200) NOT NULL                COMMENT 'Titre du mémoire / projet',
    departement         VARCHAR(150) NULL                    COMMENT 'Domaine d''étude précis',
    resume_executif     TEXT         NOT NULL                COMMENT 'Résumé complet du projet',
    technologies_outils VARCHAR(255) NULL                    COMMENT 'Outils / méthodes utilisés',
    fichier_resume_pdf  VARCHAR(255) NULL                    COMMENT 'Nom du fichier PDF joint',
    date_publication    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Mise en ligne',
    date_modification   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP COMMENT 'Dernière mise à jour (auto)',
    PRIMARY KEY (id),
    KEY idx_projets_etudiant_date (id_etudiant, date_publication),  -- portfolio par auteur, tri chronologique
    FULLTEXT KEY ft_projets_recherche (titre_projet, resume_executif) -- recherche plein-texte
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mémoires et projets publiés par les étudiants dans le portfolio';


-- ----------------------------------------------------------------------------
-- 3.5 candidatures — table de liaison N-N étudiants ↔ sujets
-- ----------------------------------------------------------------------------
CREATE TABLE candidatures (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la candidature',
    id_sujet         INT UNSIGNED NOT NULL                COMMENT 'Sujet visé → besoins_sujets(id)',
    id_etudiant      INT UNSIGNED NOT NULL                COMMENT 'Étudiant candidat → utilisateurs(id)',
    statut_id        TINYINT UNSIGNED NOT NULL DEFAULT 1  COMMENT 'Suivi → statuts_candidature(id) (1 = en_attente)',
    date_candidature TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de dépôt',
    date_decision    DATETIME     NULL                    COMMENT 'Horodatage de la décision (retenue / non_retenue)',
    PRIMARY KEY (id),
    UNIQUE KEY uq_candidature_unique (id_sujet, id_etudiant),
    -- ☝ correctif n°1 : UN SEUL dossier par étudiant et par sujet — contrainte
    --   métier qui existait seulement dans le code applicatif en v1.
    KEY idx_candidatures_sujet    (id_sujet, statut_id),  -- sélection d'un candidat par un partenaire
    KEY idx_candidatures_etudiant (id_etudiant, date_candidature), -- historique étudiant
    CONSTRAINT fk_candidatures_sujet    FOREIGN KEY (id_sujet)    REFERENCES besoins_sujets(id)
                                        ON DELETE CASCADE ON UPDATE CASCADE,
                                        -- supprimer un sujet entraîne ses candidatures
    CONSTRAINT fk_candidatures_etudiant FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id)
                                        ON DELETE RESTRICT ON UPDATE CASCADE,
                                        -- on n'efface pas un étudiant ayant un historique
    CONSTRAINT fk_candidatures_statut   FOREIGN KEY (statut_id)   REFERENCES statuts_candidature(id)
                                        ON DELETE RESTRICT ON UPDATE CASCADE
    -- NB : la règle « décision horodatée » (chk) est portée par les triggers
    -- trg_candidatures_bi/bu (section 4.3) pour la même raison de portabilité.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Liaison N-N : candidature d''un étudiant à un sujet déposé';


-- ----------------------------------------------------------------------------
-- 3.6 notifications — messages internes générés automatiquement par triggers
-- ----------------------------------------------------------------------------
CREATE TABLE notifications (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la notification',
    id_utilisateur  INT UNSIGNED NOT NULL                COMMENT 'Destinataire → utilisateurs(id)',
    titre           VARCHAR(150) NOT NULL                COMMENT 'Titre court (ex. : Nouvelle candidature)',
    message         VARCHAR(255) NOT NULL                COMMENT 'Texte du message',
    lien            VARCHAR(255) NULL                    COMMENT 'Page à ouvrir en cliquant (relative)',
    lu              BOOLEAN      NOT NULL DEFAULT FALSE  COMMENT 'FALSE = non lue (badge du menu)',
    date_creation   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Horodatage',
    PRIMARY KEY (id),
    KEY idx_notifications_dest (id_utilisateur, lu, date_creation),
    CONSTRAINT fk_notifications_dest FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
                                     ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notifications internes : candidatures, décisions, modération';


-- ----------------------------------------------------------------------------
-- 3.7 profil_formations — CV : parcours de formation de l'étudiant (v2.2)
-- ----------------------------------------------------------------------------
CREATE TABLE profil_formations (
    id            INT UNSIGNED      NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la formation',
    id_etudiant   INT UNSIGNED      NOT NULL                COMMENT 'Étudiant concerné → utilisateurs(id)',
    diplome       VARCHAR(150)      NOT NULL                COMMENT 'Intitulé du diplôme ou de la formation',
    etablissement VARCHAR(150)      NOT NULL                COMMENT 'École, faculté ou université',
    ville         VARCHAR(100)      NULL                    COMMENT 'Ville de l''établissement',
    annee_debut   SMALLINT UNSIGNED NULL                    COMMENT 'Première année (ex. 2023)',
    annee_fin     SMALLINT UNSIGNED NULL                    COMMENT 'Dernière année (NULL = en cours)',
    mention       VARCHAR(60)       NULL                    COMMENT 'Mention obtenue ou prévue (Bien, Excellent…)',
    description   TEXT              NULL                    COMMENT 'Détails : parcours, spécialisation, cours clés',
    date_ajout    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Ajout au CV',
    PRIMARY KEY (id),
    KEY idx_formations_etudiant (id_etudiant),
    CONSTRAINT fk_formations_etudiant FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id)
                                        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_formations_annees CHECK (annee_fin IS NULL OR annee_debut IS NULL OR annee_fin >= annee_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='CV étudiant — formations et diplômes';


-- ----------------------------------------------------------------------------
-- 3.8 profil_experiences — CV : stages et expériences professionnelles (v2.2)
-- ----------------------------------------------------------------------------
CREATE TABLE profil_experiences (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de l''expérience',
    id_etudiant   INT UNSIGNED NOT NULL                COMMENT 'Étudiant concerné → utilisateurs(id)',
    poste         VARCHAR(150) NOT NULL                COMMENT 'Intitulé du poste ou du stage',
    organisation  VARCHAR(150) NOT NULL                COMMENT 'Entreprise, ONG, ministère, association…',
    lieu          VARCHAR(100) NULL                    COMMENT 'Ville / région',
    periode       VARCHAR(60)  NULL                    COMMENT 'Période lisible (ex. « juin — août 2025 »)',
    en_cours      BOOLEAN      NOT NULL DEFAULT FALSE  COMMENT 'TRUE = expérience toujours en cours',
    description   TEXT         NULL                    COMMENT 'Missions, réalisations, résultats mesurables',
    date_ajout    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Ajout au CV',
    PRIMARY KEY (id),
    KEY idx_experiences_etudiant (id_etudiant),
    CONSTRAINT fk_experiences_etudiant FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id)
                                        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='CV étudiant — stages et expériences de terrain';


-- ----------------------------------------------------------------------------
-- 3.9 profil_competences — CV : outils, langages et compétences maîtrisés (v2.2)
-- ----------------------------------------------------------------------------
CREATE TABLE profil_competences (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la compétence',
    id_etudiant INT UNSIGNED NOT NULL                COMMENT 'Étudiant concerné → utilisateurs(id)',
    nom         VARCHAR(60)  NOT NULL                COMMENT 'Outil, langage, langue ou soft skill',
    categorie   VARCHAR(40)  NOT NULL DEFAULT 'Outil' COMMENT 'Langage / Outil / Langue / Compétence métier / Compétence transversale',
    date_ajout  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Ajout au CV',
    PRIMARY KEY (id),
    UNIQUE KEY uq_competence (id_etudiant, nom)      COMMENT 'Anti-doublon : un même outil une seule fois par étudiant',
    CONSTRAINT fk_competences_etudiant FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id)
                                        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='CV étudiant — outils et compétences maîtrisés';


-- ============================================================================
-- SECTION 4 — TRIGGERS (règles métier non exprimables par CHECK)
-- ----------------------------------------------------------------------------
--  Une CHECK ne peut pas interroger une autre table : les règles croisant
--  utilisateurs ↔ roles sont donc portées par des triggers BEFORE INSERT/UPDATE.
-- ============================================================================

DELIMITER $$

-- 4.1 Règles de cohérence des comptes --------------------------------------
--   • un ÉTUDIANT doit être rattaché à une faculté ;
--   • un PARTENAIRE doit renseigner sa structure ;
--   • un ADMIN ne peut pas être « validé par » lui-même hors modération (cf 4.2).
CREATE TRIGGER trg_utilisateurs_bi
BEFORE INSERT ON utilisateurs
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(20);
    SELECT code INTO v_role FROM roles WHERE id = NEW.role_id;

    IF v_role = 'etudiant' AND NEW.id_faculte IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '[UK-Connect] Un étudiant doit obligatoirement être rattaché à une faculté.';
    END IF;

    IF v_role = 'partenaire' AND NEW.nom_structure IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '[UK-Connect] Un partenaire doit obligatoirement renseigner nom_structure.';
    END IF;
END$$

CREATE TRIGGER trg_utilisateurs_bu
BEFORE UPDATE ON utilisateurs
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(20);
    SELECT code INTO v_role FROM roles WHERE id = NEW.role_id;

    IF v_role = 'etudiant' AND NEW.id_faculte IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '[UK-Connect] Un étudiant doit obligatoirement être rattaché à une faculté.';
    END IF;

    IF v_role = 'partenaire' AND NEW.nom_structure IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '[UK-Connect] Un partenaire doit obligatoirement renseigner nom_structure.';
    END IF;
END$$

-- 4.2 La modération ne peut être effectuée que par un ADMIN -----------------
--   • valide_par doit détenir le rôle « admin » ;
--   • date_validation est posée automatiquement dès qu'un sujet est tranché ;
--   • une décision est toujours signée ET datée, jamais l'une sans l'autre.
CREATE TRIGGER trg_besoins_bi
BEFORE INSERT ON besoins_sujets
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(20);
    IF NEW.valide_par IS NOT NULL THEN
        SELECT code INTO v_role FROM utilisateurs u JOIN roles r ON r.id = u.role_id
        WHERE u.id = NEW.valide_par;
        IF v_role <> 'admin' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = '[UK-Connect] Seul un administrateur peut valider ou rejeter un sujet.';
        END IF;
        SET NEW.date_validation = COALESCE(NEW.date_validation, NOW());
    END IF;
    -- une décision est toujours signée ET datée, jamais l'une sans l'autre
    IF (NEW.valide_par IS NULL) <> (NEW.date_validation IS NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '[UK-Connect] Une décision de validation doit être à la fois signée et datée.';
    END IF;
END$$

CREATE TRIGGER trg_besoins_bu
BEFORE UPDATE ON besoins_sujets
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(20);
    IF NEW.valide_par IS NOT NULL THEN
        SELECT code INTO v_role FROM utilisateurs u JOIN roles r ON r.id = u.role_id
        WHERE u.id = NEW.valide_par;
        IF v_role <> 'admin' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = '[UK-Connect] Seul un administrateur peut valider ou rejeter un sujet.';
        END IF;
        SET NEW.date_validation = COALESCE(NEW.date_validation, NOW());
    END IF;
    IF (NEW.valide_par IS NULL) <> (NEW.date_validation IS NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '[UK-Connect] Une décision de validation doit être à la fois signée et datée.';
    END IF;
END$$

-- 4.3 Toute décision de candidature doit être horodatée ----------------------
CREATE TRIGGER trg_candidatures_bi
BEFORE INSERT ON candidatures
FOR EACH ROW
BEGIN
    IF NEW.statut_id <> 1 AND NEW.date_decision IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '[UK-Connect] Toute décision (retenue / non retenue) doit être horodatée.';
    END IF;
END$$

CREATE TRIGGER trg_candidatures_bu
BEFORE UPDATE ON candidatures
FOR EACH ROW
BEGIN
    IF NEW.statut_id <> 1 AND NEW.date_decision IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '[UK-Connect] Toute décision (retenue / non retenue) doit être horodatée.';
    END IF;
END$$

-- 4.4 Notifications automatiques (le partenaire est notifié d'une candidature,
--     l'étudiant est notifié d'une décision, le partenaire de la modération) ---
CREATE TRIGGER trg_candidatures_notif_ai
AFTER INSERT ON candidatures
FOR EACH ROW
BEGIN
    DECLARE v_partenaire INT UNSIGNED;
    SELECT id_partenaire INTO v_partenaire FROM besoins_sujets WHERE id = NEW.id_sujet;
    INSERT INTO notifications (id_utilisateur, titre, message, lien)
    VALUES (v_partenaire, 'Nouvelle candidature',
            CONCAT('Un étudiant vient de candidater au sujet n° ', NEW.id_sujet,
                   '. Consultez votre espace pour statuer.'),
            'partenaire-espace.php');
END$$

CREATE TRIGGER trg_candidatures_notif_au
AFTER UPDATE ON candidatures
FOR EACH ROW
BEGIN
    IF OLD.statut_id <> NEW.statut_id THEN
        INSERT INTO notifications (id_utilisateur, titre, message, lien)
        VALUES (NEW.id_etudiant,
                IF(NEW.statut_id = 2, 'Candidature retenue 🎉', 'Décision sur votre candidature'),
                CONCAT('Le partenaire a statué sur votre candidature au sujet n° ',
                       NEW.id_sujet, '.'),
                'mes-candidatures.php');
    END IF;
END$$

CREATE TRIGGER trg_besoins_notif_au
AFTER UPDATE ON besoins_sujets
FOR EACH ROW
BEGIN
    IF OLD.statut_id <> NEW.statut_id THEN
        INSERT INTO notifications (id_utilisateur, titre, message, lien)
        VALUES (NEW.id_partenaire,
                IF(NEW.statut_id = 2, 'Sujet validé ✓', 'Sujet non retenu'),
                IF(NEW.statut_id = 2,
                   CONCAT('Votre sujet n° ', NEW.id, ' est validé et désormais visible publiquement.'),
                   CONCAT('Votre sujet n° ', NEW.id, ' n''a pas été retenu par la modération.')),
                'partenaire-espace.php');
    END IF;
END$$

DELIMITER ;


-- ============================================================================
-- SECTION 5 — VUES APPLICATIVES
-- ----------------------------------------------------------------------------
--  La règle « un sujet en attente n'est jamais visible publiquement » (v1 :
--  clause WHERE dispersée dans le PHP) est désormais encapsulée dans la vue.
-- ============================================================================

-- 5.1 Sujets publics (validés, partenaires actifs uniquement)
CREATE VIEW v_sujets_publics AS
SELECT
    b.id,
    b.titre_sujet,
    b.description_probleme,
    b.secteur_filiere,
    b.date_creation,
    u.nom_structure        AS partenaire,
    u.photo_profil         AS partenaire_photo,
    f.sigle                AS faculte_sigle,
    f.nom_faculte          AS faculte_nom
FROM besoins_sujets b
JOIN statuts_validation sv ON sv.id = b.statut_id
JOIN utilisateurs u        ON u.id = b.id_partenaire AND u.actif = TRUE
LEFT JOIN facultes f       ON f.id = b.id_faculte
WHERE sv.code = 'valide';

-- 5.2 Candidatures détaillées (back-office partenaire / admin)
CREATE VIEW v_candidatures_detail AS
SELECT
    c.id,
    c.date_candidature,
    c.date_decision,
    sc.code                AS statut,
    s.id                   AS sujet_id,
    s.titre_sujet,
    e.id                   AS etudiant_id,
    CONCAT(e.prenom, ' ', e.nom) AS etudiant,
    e.email                AS etudiant_email,
    e.telephone            AS etudiant_telephone,
    f.sigle                AS etudiant_faculte
FROM candidatures c
JOIN statuts_candidature sc ON sc.id = c.statut_id
JOIN besoins_sujets s       ON s.id  = c.id_sujet
JOIN utilisateurs e         ON e.id  = c.id_etudiant
LEFT JOIN facultes f        ON f.id  = e.id_faculte;

-- 5.3 Portfolio public des projets étudiants
CREATE VIEW v_projets_portfolio AS
SELECT
    p.id,
    p.titre_projet,
    p.departement,
    p.resume_executif,
    p.technologies_outils,
    p.fichier_resume_pdf,
    p.date_publication,
    CONCAT(e.prenom, ' ', e.nom) AS auteur,
    e.id                         AS auteur_id,
    f.sigle                      AS faculte_sigle
FROM projets_etudiants p
JOIN utilisateurs e ON e.id = p.id_etudiant AND e.actif = TRUE
LEFT JOIN facultes f ON f.id = e.id_faculte
ORDER BY p.date_publication DESC;


-- ============================================================================
-- SECTION 6 — DONNÉES DE RÉFÉRENCE
-- ============================================================================

-- 6.1 Rôles
INSERT INTO roles (id, code, libelle) VALUES
  (1, 'etudiant',  'Étudiant'),
  (2, 'partenaire','Partenaire entreprise / organisation'),
  (3, 'admin',     'Administrateur plateforme');

-- 6.2 Statuts
INSERT INTO statuts_validation (id, code, libelle) VALUES
  (1, 'en_attente', 'En attente de modération'),
  (2, 'valide',     'Validé — visible publiquement'),
  (3, 'rejete',     'Rejeté par l''administration');

INSERT INTO statuts_candidature (id, code, libelle) VALUES
  (1, 'en_attente',  'Candidature en cours d''examen'),
  (2, 'retenue',     'Candidature retenue'),
  (3, 'non_retenue', 'Candidature non retenue');

-- 6.3 Facultés, instituts, écoles et centres — entités OFFICIELLES de l'Université de Kara
--    (source : affiche officielle « Offres de formation 2026-2027 » — 9 entités :
--     5 facultés + 2 instituts + 1 école + 1 centre d'excellence)
INSERT INTO facultes (id, nom_faculte, sigle) VALUES
  (1, 'Faculté des Lettres et Sciences Humaines',                                          'FLESH'),
  (2, 'Faculté des Sciences Économiques et de Gestion',                                    'FASEG'),
  (3, 'Faculté de Droit et des Sciences Politiques',                                       'FDSP'),
  (4, 'Faculté des Sciences et Techniques',                                                'FAST'),
  (5, 'Faculté des Sciences de la Santé',                                                  'FSS'),
  (6, 'Institut de Formation en Sciences Pédagogiques et Administration Universitaire',    'ISPAU'),
  (7, 'Institut Supérieur des Métiers Agricoles',                                          'ISMA'),
  (8, 'École Polytechnique de Kara — Campus de Kara',                                      'EPK'),
  (9, 'Centre d''Excellence en Production Durable des Cultures',                           'CEPRODUC');


-- ============================================================================
-- SECTION 7 — JEU DE DONNÉES DE DÉMONSTRATION
-- ----------------------------------------------------------------------------
--  Mot de passe de TOUS les comptes de démo : UkConnect@2026
--  (hachage bcrypt cost 12 — équivalent exact de password_hash() de PHP)
--  ⚠ À supprimer en production (section commentée « PROD »).
-- ============================================================================
SET @mdp = '$2y$12$gXUfIF4VYAJHFrtzWQVJE.h3lVfU6cRN6k3p3fcY3V2gWubD3cSYC';

-- 7.1 Comptes ----------------------------------------------------------------
INSERT INTO utilisateurs (id, nom, prenom, email, telephone, mot_de_passe, role_id, id_faculte, nom_structure, bio) VALUES
  (1, 'TCHAKOURA', 'Aïcha',   'admin@ukconnect.tg',        '+228 90 00 00 01', @mdp, 3, NULL,   NULL,
   'Administration de la plateforme UK-Connect — Direction des études.'),
  (2, 'ABLOGAN',   'Kossi',   'contact@agrotogo.tg',       '+228 90 11 22 33', @mdp, 2, NULL,   'Agro-Togo SARL',
   'Coopérative spécialisée dans la transformation et la commercialisation de produits agricoles dans la région de la Kara.'),
  (3, 'DJOSSOU',   'Farida',  'espoinjeunesse@ong.tg',     '+228 91 44 55 66', @mdp, 2, NULL,   'ONG Espoir Jeunesse',
   'ONG d''accompagnement à l''entrepreneuriat des jeunes de la préfecture de la Kozah.'),
  (4, 'SALAMI',    'Yao',     'yao.salami@etu-univkara.tg','+228 92 10 20 30', @mdp, 1, 4,      NULL,
   'Étudiant en Génie Logiciel, passionné de développement d''applications mobiles.'),
  (5, 'KOUMI',     'Abra',    'abra.koumi@etu-univkara.tg','+228 92 40 50 60', @mdp, 1, 3,      NULL,
   'Étudiante en Gestion, intéressée par la finance des PME africaines.'),
  (6, 'BANDJAO',   'Léa',     'lea.bandjao@etu-univkara.tg','+228 93 70 80 90', @mdp, 1, 5,     NULL,
   'Étudiante en Sciences de la Santé, nutrition communautaire.');

-- 7.2 Sujets (les 4 états du cycle de modération sont représentés) ----------
INSERT INTO besoins_sujets (id, id_partenaire, titre_sujet, description_probleme, secteur_filiere, id_faculte, statut_id, valide_par, date_validation, date_creation) VALUES
  (1, 2, 'Application mobile de suivi des exploitants agricoles de la Kara',
   'Agro-Togo SARL travaille avec plus de 400 exploitants dont la production, les surfaces et les rendements sont suivis sur papier. Nous souhaitons une application mobile (Android, hors-ligne d''abord) permettant de collecter les données d''exploitation, de suivre les campagnes et de générer des rapports automatiques pour le conseil agricole.',
   'Agriculture / AgriTech', 4, 2, 1, '2026-08-20 10:30:00', '2026-08-18 09:15:00'),
  (2, 3, 'Plateforme de gestion des adhérents et des cotisations d''une ONG',
   'L''ONG Espoir Jeunesse gère environ 1 200 adhérents avec des fichiers Excel dispersés. Nous cherchons une application web de gestion des adhérents, du recouvrement des cotisations et de l''édition de reçus, avec un tableau de bord pour le bureau.',
   'Gestion / Social', 3, 2, 1, '2026-08-22 14:00:00', '2026-08-21 11:45:00'),
  (3, 2, 'Système de traçabilité des intrants agricoles par QR code',
   'Nous souhaitons tracer les intrants (semences, engrais) distribués aux exploitants jusqu''aux récoltes, via étiquetage QR code et scan mobile.',
   'Agriculture / Logistique', 4, 1, NULL, NULL, '2026-08-26 16:20:00'),
  (4, 3, 'Application de covoiturage inter-campus',
   'Idée de mise en relation des étudiants pour des trajets partagés Lomé–Kara en période de vacances universitaires.',
   'Transport', NULL, 3, 1, '2026-08-25 09:00:00', '2026-08-24 13:10:00');

-- 7.3 Projets étudiants -------------------------------------------------------
INSERT INTO projets_etudiants (id, id_etudiant, titre_projet, departement, resume_executif, technologies_outils, fichier_resume_pdf, date_publication) VALUES
  (1, 4, 'AgriSuin — application de suivi des pépinières villageoises',
   'Génie Logiciel',
   'Application Android de suivi de croissance des pépinières (photos horodatées, mesures, alertes d''arrosage) développée comme projet de licence. Testée sur 12 pépinières de la préfecture de la Kozah, elle a réduit de 23 % la mortalité des plants la première saison.',
   'Android (Kotlin), Firebase, GPS', 'agrisuin_resume.pdf', '2026-07-10 10:00:00'),
  (2, 5, 'Analyse de la gestion financière des PME du marché central de Kara',
   'Sciences de Gestion',
   'Mémoire de licence analysant les pratiques comptables de 40 PME du marché central de Kara. Met en évidence le manque de séparation des caisses et propose un modèle simplifié de registre numérique adapté aux commerçants non bancarisés.',
   'Enquête terrain, SPSS, Excel', 'pme_kara_resume.pdf', '2026-07-25 15:30:00'),
  (3, 6, 'Cartographie nutritionnelle des cantines scolaires de la Kara',
   'Nutrition publique',
   'Projet d''initiative étudiante cartographiant l''offre nutritionnelle de 18 cantines scolaires et proposant des menus équilibrés à base de produits locaux (soja, moringa, igname) pour un coût inférieur à 150 FCFA/repas.',
   'Enquête, QGIS, planification alimentaire', 'cantine_resume.pdf', '2026-08-05 08:45:00');

-- 7.4 Candidatures (tous les statuts représentés) ------------------------------
INSERT INTO candidatures (id, id_sujet, id_etudiant, statut_id, date_candidature, date_decision) VALUES
  (1, 1, 4, 2, '2026-08-19 08:20:00', '2026-08-23 17:00:00'),  -- Yao → sujet AgriTech : retenue
  (2, 1, 5, 3, '2026-08-19 12:05:00', '2026-08-23 17:05:00'),  -- Abra → sujet AgriTech : non retenue
  (3, 2, 5, 2, '2026-08-22 09:40:00', '2026-08-27 10:15:00'),  -- Abra → sujet ONG : retenue
  (4, 2, 6, 1, '2026-08-23 14:25:00', NULL);                   -- Léa → sujet ONG : en attente

-- Bannières de démonstration (fichiers inclus dans assets/uploads/bannieres/)
UPDATE utilisateurs SET banniere_profil = 'banniere_demo_agritech.png', photo_profil = 'avatar_demo_yao.png' WHERE id = 4;
UPDATE utilisateurs SET banniere_profil = 'banniere_demo_gestion.png'  WHERE id = 5;
UPDATE utilisateurs SET banniere_profil = 'banniere_demo_sante.png'    WHERE id = 6;

-- 7.5 CV de démonstration (v2.2) : niveau d'études, parcours, stages et outils
UPDATE utilisateurs SET niveau_etudes = 'Licence 3 — Génie Logiciel'      WHERE id = 4;
UPDATE utilisateurs SET niveau_etudes = 'Master 1 — Gestion des Projets'  WHERE id = 5;
UPDATE utilisateurs SET niveau_etudes = 'Licence 3 — Sciences Infirmières' WHERE id = 6;

INSERT INTO profil_formations (id_etudiant, diplome, etablissement, ville, annee_debut, annee_fin, mention, description) VALUES
(4, 'Licence en Génie Logiciel', 'FAST — Université de Kara', 'Kara', 2023, 2026, 'Bien',
 'Parcours développement logiciel : PHP/MySQL, génie logiciel, applications mobiles.'),
(4, 'Baccalauréat série D', 'Lycée Municipal de Kara', 'Kara', 2022, 2023, NULL,
 'Baccalauréat scientifique, spécialisation mathématiques.'),
(5, 'Master en Gestion des Projets', 'FDSP — Université de Kara', 'Kara', 2024, NULL, NULL,
 'Options : suivi-évaluation, gestion de projets communautaires.'),
(6, 'Licence en Sciences Infirmières', 'FSS — Université de Kara', 'Kara', 2022, 2025, 'Très bien',
 'Stages cliniques au CHR de Kara, santé communautaire en milieu rural.');

INSERT INTO profil_experiences (id_etudiant, poste, organisation, lieu, periode, en_cours, description) VALUES
(4, 'Stagiaire développeur web', 'Agro-Togo SARL', 'Lomé', 'juin — août 2025', FALSE,
 'Conception d''un tableau de bord de suivi des commandes (PHP/MySQL) ; formation des équipes au back-office.'),
(4, 'Bénévole numérique', 'Espoir Jeunesse ONG', 'Kara', '2024 — aujourd''hui', TRUE,
 'Ateliers d''initiation à l''informatique pour 40 jeunes ; maintenance du parc informatique.'),
(5, 'Assistant suivi-évaluation (stage)', 'Plan International Togo', 'Sokodé', 'févr. — juil. 2025', FALSE,
 'Collecte terrain (KoboToolbox), analyse des indicateurs, rédaction de rapports trimestriels.'),
(6, 'Stagiaire infirmière', 'CHR de Kara', 'Kara', 'nov. 2024 — févr. 2025', FALSE,
 'Soins généraux et urgences ; campagne de sensibilisation santé maternelle (300 personnes touchées).');

INSERT INTO profil_competences (id_etudiant, nom, categorie) VALUES
(4, 'PHP', 'Langage'), (4, 'MySQL', 'Outil'), (4, 'JavaScript', 'Langage'),
(4, 'HTML & CSS', 'Langage'), (4, 'Git / GitHub', 'Outil'), (4, 'Python', 'Langage'),
(4, 'Pack MS Office', 'Outil'), (4, 'Travail en équipe', 'Compétence transversale'),
(5, 'Gestion de projet', 'Compétence métier'), (5, 'KoboToolbox', 'Outil'),
(5, 'MS Project', 'Outil'), (5, 'Rédaction de rapports', 'Compétence transversale'),
(5, 'Anglais professionnel', 'Langue'),
(6, 'Soins d''urgence', 'Compétence métier'), (6, 'Santé communautaire', 'Compétence métier'),
(6, 'Écoute active', 'Compétence transversale');

-- 7.5 Démonstration des protections (décommentez pour tester — chaque bloc DOIT échouer)
--  a) doublon de candidature        → violerait uq_candidature_unique
-- INSERT INTO candidatures (id_sujet, id_etudiant, statut_id) VALUES (1, 4, 1);
--  b) étudiant sans faculté         → déclencheur trg_utilisateurs_bi
-- INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, id_faculte)
--        VALUES ('X', 'Y', 'x.y@etu-univkara.tg', '$2y$12$aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 1, NULL);
--  c) validation par un non-admin   → déclencheur trg_besoins_bu
-- UPDATE besoins_sujets SET valide_par = 4 WHERE id = 3;


-- ============================================================================
-- SECTION 8 — CONTRÔLE QUALITÉ (à exécuter après l'installation)
-- ============================================================================
SELECT 'roles'                AS table_, COUNT(*) AS nb FROM roles
UNION ALL SELECT 'statuts_validation',  COUNT(*) FROM statuts_validation
UNION ALL SELECT 'statuts_candidature', COUNT(*) FROM statuts_candidature
UNION ALL SELECT 'facultes',            COUNT(*) FROM facultes
UNION ALL SELECT 'utilisateurs',        COUNT(*) FROM utilisateurs
UNION ALL SELECT 'besoins_sujets',      COUNT(*) FROM besoins_sujets
UNION ALL SELECT 'projets_etudiants',   COUNT(*) FROM projets_etudiants
UNION ALL SELECT 'candidatures',        COUNT(*) FROM candidatures;

SELECT * FROM v_sujets_publics;         -- doit renvoyer uniquement les sujets 1 et 2
SELECT * FROM v_candidatures_detail;    -- 4 candidatures avec leur libellé de statut


-- ============================================================================
-- SECTION 9 — COMPTE APPLICATIF & EXPLOITATION (PRODUCTION)
-- ----------------------------------------------------------------------------
--  Principe du moindre privilège : l'application PHP ne se connecte JAMAIS
--  en root. Activez et adaptez le bloc ci-dessous au déploiement.
-- ============================================================================
-- CREATE USER 'ukconnect_app'@'localhost' IDENTIFIED BY '◆_MOT_DE_PASSE_FORT_◆';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON ukconnect.* TO 'ukconnect_app'@'localhost';
-- FLUSH PRIVILEGES;
--
--  SAUVEGARDES (cron quotidien + journalisation binaire activée) :
--    0 2 * * * mysqldump -u root -p'◆◆◆' --single-transaction --routines \
--              --triggers ukconnect | gzip > /var/backups/ukconnect_$(date +\%F).sql.gz
--
--  RESTAURATION :
--    gunzip < ukconnect_2026-08-28.sql.gz | mysql -u root -p ukconnect
--
--  REMARQUE COLLATION : utf8mb4_unicode_ci est compatible MySQL 8 ET MariaDB.
--  Sur un serveur MySQL 8 exclusivement, vous pouvez préférer utf8mb4_0900_ai_ci.
-- ============================================================================
