<?php

require_once __DIR__ . '/includes/auth.php';

deconnecter();
demarrer_session();
message('succes', 'Vous êtes déconnecté.');
rediriger(lien('index.php'));
