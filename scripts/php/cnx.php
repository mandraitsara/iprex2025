<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Connextion BDD
------------------------------------------------------*/
if (isset($_SESSION['DEVTEST']) && $_SESSION['DEVTEST'] == true) {

	$PARAM_hote			= $conf_bdd_local_host;
	$PARAM_port			= $conf_bdd_local_port;
	$PARAM_nom_bd		= $conf_bdd_local_bdd;
	$PARAM_utilisateur	= $conf_bdd_local_user;
	$PARAM_mot_passe	= $conf_bdd_local_pw;
} else {
	$PARAM_hote			= $conf_bdd_prod_host;
	$PARAM_port			= $conf_bdd_prod_port;
	$PARAM_nom_bd		= $conf_bdd_prod_bdd;
	$PARAM_utilisateur	= $conf_bdd_prod_user;
	$PARAM_mot_passe	= $conf_bdd_prod_pw;
}

try {

	$connexionString = 'mysql:host='.$PARAM_hote.';';
	$connexionString.= $PARAM_port > 0 ? 'port='.$PARAM_port.';' : '';
	$connexionString.= 'dbname='.$PARAM_nom_bd;

	$cnx = new PDO($connexionString, $PARAM_utilisateur, $PARAM_mot_passe, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
	$cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
} catch (Exception $e) {

	echo '<pre>';var_dump($e);echo '</pre>';exit;
   header('Location: ./scripts/php/install.php?s=c');
   exit;
}