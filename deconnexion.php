<?php
/**
 * UK-Connect — Déconnexion
 */
require_once __DIR__ . '/includes/auth.php';
session_init();
logout();
session_start();
flash('succes', 'Vous êtes déconnecté. À bientôt sur UK-Connect !');
redirect(url('index.php'));
