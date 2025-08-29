<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Configuration générale et appels moteur
------------------------------------------------------*/

// Début temporisation et mémoire
$timestamp_debut = microtime(true);
$initialMem		 = memory_get_usage();
// On augmente la durée de vie de la session à 24H, sinon c'est 24 minutes par défaut (1440) !
ini_set("session.gc_maxlifetime","14400");

// Gestion de la restriction d'accès distant :
// Un cookie est nécéssaire pour accedér a l'intranet
// Si il n'existe pas, on redirige vers une page de connexion à l'application elle-même


//$skipAccess = true; // ON désactive le cookie d'accès au poste (à remettre si on passe par un hébergement ouvert)
//
//if ((!isset($_COOKIE['IPREXACCESS']) || (int)$_COOKIE['IPREXACCESS'] !== 1) && (!isset($skipAccess) || $skipAccess == false)) {
//	$cible = file_exists('access.php') ? 'access.php' : '../../access.php';
//	header('Location: '.$cible);
//	exit;
//}
// //
// $ipsDev = ['90.66.213.96', '82.64.52.153', '37.70.224.58', '86.248.32.145', '90.65.240.171', '86.248.18.95', '86.202.69.242'];
//var_dump($_SERVER['REMOTE_ADDR']);
//if ($_SERVER['HTTP_HOST'] != 'iprex.profilexport.local' && !in_array($_SERVER['REMOTE_ADDR'], $ipsDev)) {
//	header('Location: http://dev.iprex.pofilexport.ovh/');
//	exit;
//}



// Intégration des paramètres
try {
	if (! @include_once( 'config.params.php' )) {
		header('Location: ./scripts/php/install.php?s=pi');
		exit;
	}
}
catch(Exception $e) {
	header('Location: ./scripts/php/install.php?s=pe');
	exit;
}

// Ouverture de la session
session_name("PHPSESSID_IPREX");
session_start();


// Purge des appels de classes pour le mode Debug
$_SESSION['classcalls'] = [];

// Option de suspention du mode debug
if (isset($_GET['debugmode'])) {
	if ($_GET['debugmode'] == "on" || $_GET['debugmode'] == "1" || $_GET['debugmode'] == "true") {
		$_COOKIE['cbodebugoff'] = 0;
	}
}
if (isset($_COOKIE['cbodebugoff']) && $_COOKIE['cbodebugoff'] == 1) {
	$mode_debug = false;
}



// Définition des variables de sessions à l'ouverture
if (!isset($_SESSION['cbofsessdate']) || $_SESSION['cbofsessdate'] != date('Ymd') || !isset($_SESSION['url_site']) || !isset($_SESSION['sub_domain'])) {
		$protocole = 'http';
		$protocole.= $conf_ssl ? 's' : '';
		$protocole.= '://';
		$_SESSION['sub_domain']				= '/'.$conf_subdom;
		$_SESSION['url_site']				= $protocole.$conf_domaine.$conf_subdom .'/';
		$_SESSION['usesmtp']				= $conf_smtp;
		$_SESSION['nom_site']				= $conf_nomsite;
		$_SESSION['cbofv']					= '2.4.3';
		$_SESSION['cbofurl']				= 'cbo.cirdec.fr';
		$_SESSION['cbofurlreal']			= 'https://www.cirdec.fr/cbo/';
		$_SESSION['cbofyear']				= '2013';
		$_SESSION['cbofbsv']				= '4.0.0';
		$_SESSION['cbofjqueryv']			= '3.3.1';
		$_SESSION['cbofjqueryuiv']			= '1.12';
		$_SESSION['fontawesomeiconesurl']	= 'https://fontawesome.com/icons?d=gallery&m=free';
		$_SESSION['smtp_server'] 			= $conf_smtp_host;
		$_SESSION['cbofsessdate']			= date('Ymd');
		if ($conf_smtp) {
			$_SESSION['smtp_username'] 		= $conf_smtp_user;
			$_SESSION['smtp_password'] 		= $conf_smtp_pw;
			$_SESSION['smtp_port'] 			= $conf_smtp_port;
		}
}

$_SESSION['iprexv']	= $conf_version;


// Définition des variables globales
if (!defined('__CBO_LOGSQL_PATH__')) {
	define('__CBO_ROOT_URL__', 			$_SESSION['url_site']);
	define('__CBO_IMG_URL__', 			__CBO_ROOT_URL__ . 'img/');
	define('__CBO_CLASS_URL__', 		__CBO_ROOT_URL__ . 'class/');
	define('__CBO_SCRIPTS_JS_URL__', 	__CBO_ROOT_URL__ . 'scripts/js/');
	define('__CBO_SCRIPTS_PHP_URL__', 	__CBO_ROOT_URL__ . 'scripts/php/');
	define('__CBO_SCRIPTS_AJAX_URL__', 	__CBO_ROOT_URL__ . 'scripts/ajax/');
	define('__CBO_SCRIPTS_CRON_URL__', 	__CBO_ROOT_URL__ . 'scripts/cron/');
	define('__CBO_TEMP_URL__', 			__CBO_ROOT_URL__ . 'temp/');
	define('__CBO_UPLOADS_URL__', 		__CBO_ROOT_URL__ . 'uploads/');

	define('__CBO_ROOT_PATH__', 		$_SERVER['DOCUMENT_ROOT'] . $_SESSION['sub_domain']);
	define('__CBO_UPLOADS_PATH__', 		__CBO_ROOT_PATH__ . '/uploads/');
	define('__CBO_LOGSQL_PATH__', 		__CBO_ROOT_PATH__ . '/logsql/');
	define('__CBO_CSS_URL__', 			__CBO_ROOT_PATH__ . 'css/');
}

define('__CBO_PAGE__', str_replace('/','',str_replace('.php','',preg_replace('#^(.+[\\\/])*([^\\\/]+)$#', '$2', $_SERVER['PHP_SELF']))));
// Purge des requettes PDO en session pour le mode Debug si on a changé de page
if (substr(__CBO_PAGE__,0,4) != 'fct_') {	$_SESSION['pdoq'] = []; }

// Définition de la variable du chemin pour Smarty
define('SMARTY_DIR', 'vendor/smarty/');

// Limitation pages Debug
/*if (isset($onlyDebug) && $onlyDebug && !$mode_debug) {
	header('Location: '.__CBO_ROOT_URL__);
}*/ // Surchargé par la gestion du profil Developpeur

// Appel des connecteur PDO et d'autoload des classes
require_once 'cnx.php';
require_once 'charger_class.php';

// Mode maintenance ?
$configManager = new ConfigManager($cnx);
$config_maintenance  = $configManager->getConfig('maintenance');
// Si pas configuré encore...
if (!$config_maintenance instanceof Config) {
	$config_maintenance = new Config([]);
	$config_maintenance->setClef('maintenance');
	$config_maintenance->setDescription("Mode maintenance");
	$config_maintenance->setValeur(0);
	$configManager->saveConfig($config_maintenance);
} // Fin première configuration

// GesCom activée ?
$gescom_active = $configManager->getConfig('gescom_active');
// Si pas configuré encore...
if (!$gescom_active instanceof Config) {
	$gescom_active = new Config([]);
	$gescom_active->setClef('gescom_active');
	$gescom_active->setDescription("Activation de la Gescom (désactiver pour maintenance)");
	$gescom_active->setValeur(0);
	$configManager->saveConfig($gescom_active);
} // Fin première configuration

$modeMaintenance = intval($config_maintenance->getValeur()) == 1;
$gescom = intval($gescom_active->getValeur()) == 1;

// PROFIL EXPORT : intégration de la vérif d'authentification
if (!isset($skipAuth) || $skipAuth == false) {
	require_once 'check_auth.php';
} else if (isset($cnxBot) && $cnxBot == true) {
	$userManager = new UserManager($cnx);
	$userBot = $userManager->getUserBot();
	if (!$userBot instanceof User) {
		$userBot = new User([]);
	}
	$_SESSION['logged_user'] = serialize($userBot);
}
require_once 'fonctions.php';
//require_once 'crontab.php'; // Décommenter pour gérer les taches cron à la navigation
