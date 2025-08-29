<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Configuration générale des paramètres
Générée le 12/11/2018 à 17:24:05
------------------------------------------------------*/

/**  --------------------------------------------
	 Paramètres généraux : SSL, URLs, Nom
---------------------------------------------  */
	
	$conf_nomsite 			= 'iPrex';
	$conf_version 			= '2.8 build DEV';
	$conf_domaine 			= 'localhost';
	$localhost 				= 'localhost';
	$conf_subdom			= '/';
	$conf_ssl 				= false;
	$mode_debug				= false;

/**  --------------------------------------------
	 Paramètres E-mail/SMTP
---------------------------------------------  */

	$conf_email				= '';
	$conf_smtp				= false;

	// $conf_smtp_host		= 'profilexport-fr.mail.protection.outlook.com';
	// $conf_smtp_port 		= '25';

	$conf_smtp_host			= 'smtp.office365.com';
	$conf_smtp_port 		= '587';

	$conf_smtp_user 		= '';
	$conf_smtp_pw 			= '';

/**  --------------------------------------------
	 Paramètres connexion BDD locale / production
---------------------------------------------  */

	// $conf_bdd_local_host	= 'dc37588-004.privatesql';
	// $conf_bdd_local_port	= 35140;
	// $conf_bdd_local_bdd		= 'iprexjca';
	// $conf_bdd_local_user	= 'iprexjca';
	// $conf_bdd_local_pw		= 'Jr1dn85jPUldLWeoQDIo';

	// $conf_bdd_prod_host		= 'dc37588-004.privatesql';
	// $conf_bdd_prod_port		= 35140;
	// $conf_bdd_prod_bdd		= 'iprexjca';
	// $conf_bdd_prod_user		= 'iprexjca';
	// $conf_bdd_prod_pw		= 'Jr1dn85jPUldLWeoQDIo';

	$conf_bdd_local_host	= 'localhost';
	$conf_bdd_local_port	= 3306;
	$conf_bdd_local_bdd		= 'iprex';
	$conf_bdd_local_user	= 'root';
	$conf_bdd_local_pw		= 'root';

	$conf_bdd_prod_host		= 'localhost';
	$conf_bdd_prod_port		= 3306;
	$conf_bdd_prod_bdd		= 'iprex';
	$conf_bdd_prod_user		= 'root';
	$conf_bdd_prod_pw		= 'root';

	$conf_bdd_myadmin		= 'https://localhost/phpmyadmin';

/**  --------------------------------------------
	 FIN des paramètres
---------------------------------------------  */