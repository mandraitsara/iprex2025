<?php
/**
------------------------------------------------------------------------
SCRIPT PHP - Check authentification User

Copyright (C) 2018 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */

// On tente de récupérer l'utilisateur connecté en session
$utilisateur = isset($_SESSION['logged_user']) ? unserialize($_SESSION['logged_user']) : false;

// Commenté le 23/05/2024 par PPL
// if ((!isset($cnxBot) || $cnxBot == false) && $utilisateur instanceof User) {
// 	if (strtolower($utilisateur->getNom()) == 'bot') {

// 		header('Location: ' . __CBO_SCRIPTS_PHP_URL__.'deconnexion.php');
// 		exit;
// 	}
// }

// Si on est connecté, qu'on est pas Developpeur et que le mode maintenance est activé, alors on redirige vers la page de maintenance
if ($utilisateur instanceof User && !$utilisateur->isDev()) {
	if (isset($modeMaintenance) && $modeMaintenance == true) {
		header('Location: ' . __CBO_ROOT_URL__.'maintenance.php');
		exit;
	}
}

$cnxCookieOK = false;

// Si on est pas connecté ET qu'on est pas déjà sur la page d'autentification
if (!$utilisateur instanceof User && __CBO_PAGE__ != 'auth' && __CBO_PAGE__ != 'auth_admin') {

	// On teste si un cookie de connexion est toujours actif pour augmenter la durée de session à 24h...
	$user_cookie_id = isset($_COOKIE['IPREXCNXUSERID']) ? intval($_COOKIE['IPREXCNXUSERID']) : 0;

	if ($user_cookie_id > 0) {
		if (!isset($usersManager)){
			$usersManager = new UserManager($cnx);
		}

		$utilisateurCookie = $usersManager->getUser($user_cookie_id);
		if ($utilisateurCookie instanceof User) {
			$cnxCookieOK = true;
			$utilisateur = $utilisateurCookie;
			$_SESSION['logged_user'] = serialize($utilisateurCookie);

			// Si admin, on passe la variable en session
			if ($utilisateur->isAdmin()) {
				$_SESSION['cnxadmin'] = true;
			}
		}
	}



	// SI on est pas arrivé ici depuis un appel aJax ou un script PHP
	// (pour éviter les redirection au sein des modales ou de divs de retour)
	if (strpos($_SERVER['SCRIPT_NAME'], 'scripts') == false && !$cnxCookieOK) {

		// Alors on redirige vers la page de connexion par code (lien vers connexion admin en header)
		header('Location: ' . __CBO_ROOT_URL__.'auth.php?redirect='.__CBO_PAGE__);
		exit;

	} // FIN test pas depuis aJax

} // FIN test utilisateur et source