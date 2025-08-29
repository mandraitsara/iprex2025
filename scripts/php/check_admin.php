<?php
/**
------------------------------------------------------------------------
SCRIPT PHP - Check Admin

Copyright (C) 2018 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */
// On checke si l'admin s'est bien identifié avec son login/pw pour cette partie là
// Sur la page auth (code), on rajoute un lien pour se connecter directement avec login/pw
// Gestion par session
// On vérifie d'ailleurs que l'user a bien des droits d'admin au passage, sinon il aurais pas du arriver la, on exit avec un message pour debug eventuel

// Si c'est pas le cas, on renvoie à une connexion admin

$cnxAdmin = isset($_SESSION['cnxadmin']) ? $_SESSION['cnxadmin'] : false;

if (!$cnxAdmin) {
	header('Location: auth_admin.php');
}