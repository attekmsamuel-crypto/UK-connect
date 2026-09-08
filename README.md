# UK-Connect

Plateforme de mise en relation entre les étudiants de l'Université de Kara et les
entreprises, collectivités et associations de la région.

Un partenaire dépose un besoin, l'université le relit et le publie, un étudiant
candidate, le partenaire retient un candidat. En parallèle, les étudiants publient
leurs mémoires et projets dans un portfolio consultable par les partenaires.

## Installation

1. Copier le dossier dans `C:\xampp\htdocs\`.
2. Démarrer Apache et MySQL depuis le panneau XAMPP.
3. Importer `base/ukconnect.sql` dans phpMyAdmin (menu **Importer**).
   Le script crée la base `ukconnect` et son jeu de données de démonstration.
4. Ouvrir <http://localhost/uk-connect-etudiant/>.

## Le travail à faire

Quinze fonctions ont été retirées du projet. Elles se trouvent toutes dans le même
fichier :

```
includes/requetes.php
```

Chacune est remplacée par un commentaire `TODO` numéroté qui dit ce que la fonction
doit renvoyer et ce qu'elle remet en marche dans l'application. Traite-les dans
l'ordre des numéros, de 1 à 15.

Tant qu'une fonction n'est pas écrite, ce qui en dépend ne répond pas : un tableau
reste vide, un bouton n'a pas d'effet, une donnée ne s'affiche pas. C'est normal,
et l'application ne plante pas pour autant.

**Ne modifie aucun autre fichier.** Tout le reste est déjà en place : la base de
données, les pages, la mise en page, la gestion des sessions et des rôles, l'envoi
des fichiers et les notifications.

Le document *UK-Connect — Les fonctions à écrire* décrit, pour chaque numéro, ce
que débloque la fonction et comment vérifier qu'elle marche.

## Comptes de démonstration

Mot de passe commun : `UkConnect2026`

| Profil        | Adresse                          |
|---------------|----------------------------------|
| Administration| admin@ukconnect.tg               |
| Partenaire    | contact@agrotogo.tg              |
| Partenaire    | sg@mairie-kara.tg                |
| Étudiant      | yao.salami@etu-univkara.tg       |
| Étudiant      | abra.koumi@etu-univkara.tg       |

## Organisation des fichiers

```
config/config.php        connexion à la base et constantes de l'application
includes/fonctions.php   fonctions utilitaires (affichage, dates, envoi de fichiers)
includes/requetes.php    toutes les requêtes SQL — c'est ici que tu travailles
includes/auth.php        session, connexion, contrôle des rôles
includes/entete.php      en-tête et menu communs
includes/pied.php        pied de page commun
assets/                  feuille de style, script, images et fichiers envoyés
base/ukconnect.sql       script de création de la base
admin/                   pages réservées à l'administration
```

Les pages n'écrivent jamais de SQL directement : elles appellent les fonctions de
`includes/requetes.php`.

## Environnement

PHP 8 et MySQL/MariaDB, sans framework ni bibliothèque externe. Aucune ressource
n'est chargée depuis Internet : le site fonctionne hors connexion.
