<?php
/**
------------------------------------------------------------------------
PAGE - Maintenance

Copyright (C) 2019 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2019 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */

// Ouverture de la session
require_once 'scripts/php/config.params.php';
session_name("PHPSESSID_IPREX");
session_start();
require_once 'scripts/php/cnx.php';
require_once 'scripts/php/charger_class.php';

// On vérifie que l'on est bien en mode maintenance !
// Mode maintenance ?
$configManager = new ConfigManager($cnx);
$config_maintenance  = $configManager->getConfig('maintenance');
// Si pas configuré encore...
if (!$config_maintenance instanceof Config) {
	$config_maintenance = new Config([]);
	$config_maintenance->setClef('maintenance');
	$config_maintenance->setDescription("Mode maintenance");
	$config_maintenance->setValeur(0);
} // Fin première configuration

$modeMaintenance = intval($config_maintenance->getValeur()) == 1;

// Si ce n'est pas le cas, on redirige vers l'accueil...
if (!$modeMaintenance) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <!--<meta name="apple-mobile-web-app-capable" content="yes"/>-->
        <meta name="generator" content="CBO">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=device-width">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <!--[if IE]><link rel="shortcut icon" href="http://intersed.info/iprex/img/favicon.ico"><![endif]-->
        <link rel="icon" href="http://intersed.info/iprex/img/favicon.png">
        <meta name="robots" content="noindex, nofollow">
        <title>iPrex - Maintenance</title>
        <link rel="stylesheet" type="text/css" href="http://intersed.info/iprex/css/maintenance.css" media="screen, print" />
        <link rel="stylesheet" type="text/css" href="http://intersed.info/iprex/vendor/fontawesome/css/all.css?<?php echo date('Ym');?>" media="screen, print" />
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <h1><i class="fa fa-cogs"></i><br/>Maintenance</h1>
        <h2><i class="fab fa-spin fa-codepen"></i> L'intranet Profil Export est en cours de maintenance&hellip;</h2>
        <h3>L'accès au service sera rétabli dans quelques instants.<br/>
        Merci de votre compréhension.</h3>
        <div class="retour">
            <a href="index.php">Réessayer</a>
        </div>
    </body>
</html>