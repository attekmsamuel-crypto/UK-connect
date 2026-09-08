<?php

require_once __DIR__ . '/fonctions.php';
require_once __DIR__ . '/requetes.php';

function demarrer_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function utilisateur_actuel(): ?array
{
    static $utilisateur = null;
    static $charge = false;

    if ($charge) {
        return $utilisateur;
    }

    $charge = true;
    demarrer_session();

    if (empty($_SESSION['utilisateur_id'])) {
        return null;
    }

    $utilisateur = utilisateur_par_id(connexion_bdd(), (int)$_SESSION['utilisateur_id']);

    if (!$utilisateur || (int)$utilisateur['actif'] !== 1) {
        unset($_SESSION['utilisateur_id']);
        $utilisateur = null;
    }

    return $utilisateur;
}

function est_connecte(): bool
{
    return utilisateur_actuel() !== null;
}

function a_le_role(string $role): bool
{
    $utilisateur = utilisateur_actuel();

    return $utilisateur !== null && $utilisateur['role'] === $role;
}

function est_etudiant(): bool
{
    return a_le_role('etudiant');
}

function est_partenaire(): bool
{
    return a_le_role('partenaire');
}

function est_admin(): bool
{
    return a_le_role('admin');
}

function exiger_connexion(): array
{
    $utilisateur = utilisateur_actuel();

    if ($utilisateur === null) {
        message('erreur', 'Connectez-vous pour accéder à cette page.');
        rediriger(lien('connexion.php'));
    }

    return $utilisateur;
}

function exiger_role(string $role): array
{
    $utilisateur = exiger_connexion();

    if ($utilisateur['role'] !== $role) {
        message('erreur', 'Cette page ne vous est pas accessible.');
        rediriger(lien('index.php'));
    }

    return $utilisateur;
}

function connecter(PDO $pdo, string $email, string $motDePasse): bool
{
    $utilisateur = utilisateur_par_email($pdo, $email);

    if (!$utilisateur || !password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
        return false;
    }

    if ((int)$utilisateur['actif'] !== 1) {
        message('erreur', 'Ce compte a été désactivé. Contactez l\'administration de la plateforme.');

        return false;
    }

    demarrer_session();
    session_regenerate_id(true);
    $_SESSION['utilisateur_id'] = (int)$utilisateur['id'];
    unset($_SESSION['csrf']);

    return true;
}

function deconnecter(): void
{
    demarrer_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

function accueil_du_role(array $utilisateur): string
{
    if ($utilisateur['role'] === 'admin') {
        return lien('admin/index.php');
    }
    if ($utilisateur['role'] === 'partenaire') {
        return lien('espace-partenaire.php');
    }

    return lien('etudiant.php?id=' . (int)$utilisateur['id']);
}
