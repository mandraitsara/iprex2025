<?php
/**
------------------------------------------------------------------------
Tâche CRON de purge des token expirés
API de liaison vers l'application mobile
Copyright (C) 2018 Intersed

http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
*/
header('Access-Control-Allow-Origin: *');
ini_set('display_errors',1);

// Intégration des dépendances
require_once ('inc.config.php');
require_once ('AppAPI.class.php');

echo '<h1>Purge des token expirés</h1>';

// Instanciation de l'objet API
$api = new AppAPI();

// Intégration des dépendances GLPI
define('GLPI_ROOT', '/var/www/vhosts/intersed.fr/dev-support/');
include_once (GLPI_ROOT . "inc/autoload.function.php");
include_once (GLPI_ROOT . "inc/db.function.php");
include_once (GLPI_ROOT . "config/config.php");

$params = [];
$params['purge'] = true;

echo $api->delToken($params) ? '<p>Purge terminée.</p>' : '<p>Erreur !</p>';
