<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------------
INSTALLATION DU FRAMEWORK
(Inexistance du fichier config.params.php ou BDD inaccessible)
-------------------------------------------------------------*/


/*******************************************************************
 * DESACTIVATION DE L'INSTALLER
*******************************************************************/
header('Location: index.php');
/*******************************************************************
 * FIN DESACTIVATION DE L'INSTALLER
 *******************************************************************/

$sousrep = '../../';
$css_commons[] = $sousrep.'vendor/jquery-ui/jquery-ui.min.css';
$css_commons[] = $sousrep.'vendor/jquery-ui/jquery-ui.structure.min.css';
$css_commons[] = $sousrep.'vendor/jquery-ui/jquery-ui.theme.min.css';
$css_commons[] = $sousrep.'vendor/fontawesome/css/all.css';
$css_commons[] = $sousrep.'vendor/bootstrap/css/bootstrap.min.css';
$css_commons[] = $sousrep.'vendor/selectpicker/css/bootstrap-select.min.css';
$css_commons[] = $sousrep.'vendor/icheck/skins/square/blue.css';
$css_commons[] = $sousrep.'vendor/icheck/skins/flat/blue.css';
$css_commons[] = $sousrep.'vendor/bootstrap-switch/bootstrap-switch.min.css';
$css_commons[] = $sousrep.'css/main.css';
$css_commons[] = $sousrep.'css/responsive.css';
$css_commons[] = $sousrep.'css/rougetemp.css';
$css_commons[] = $sousrep.'css/install.css';

$js_commons	[] = $sousrep.'vendor/jquery/jquery-3.3.1.min.js';
$js_commons	[] = $sousrep.'vendor/jquery-ui/jquery-ui.min.js';
$js_commons [] = $sousrep.'vendor/bootstrap/js/bootstrap.bundle.min.js';
$js_commons [] = $sousrep.'vendor/selectpicker/js/bootstrap-select.min.js';
$js_commons [] = $sousrep.'vendor/icheck/icheck.min.js';
$js_commons [] = $sousrep.'vendor/bootstrap-switch/bootstrap-switch.min.js';
$js_commons [] = $sousrep.'scripts/js/commons.js';
$js_commons [] = $sousrep.'scripts/js/main.js';
$js_commons [] = $sousrep.'scripts/js/install.js';

@include_once( 'config.params.php' );

// On teste si le formulaire a été envoyé
if (isset($_POST['conf_nomsite'])) {

    /*****************************************
     * Génération du fichier config.params.php
     *****************************************/

    // Si le fichier existe déjà, on le renome en .old et on supprime les anciens old
    if (file_exists('config.params.php')) {
		$mask = "*.old";
        array_map( "unlink", glob( $mask ) );
		rename("config.params.php", "config.params-".date('YmdHis').".old");
    }

	$conf_nomsite = isset($_REQUEST['conf_nomsite']) ? trim($_REQUEST['conf_nomsite']) : false;
	$conf_domaine = isset($_REQUEST['conf_domaine']) ? trim($_REQUEST['conf_domaine']) : false;
	$localhost = isset($_REQUEST['localhost']) ? trim($_REQUEST['localhost']) : false;
	$conf_subdom = isset($_REQUEST['conf_subdom']) ? trim($_REQUEST['conf_subdom']) : false;
	$conf_ssl = isset($_REQUEST['conf_ssl']) ? 'true' : 'false';
	$mode_debug = isset($_REQUEST['mode_debug']) ? 'true' : 'false';
	$conf_smtp = isset($_REQUEST['conf_smtp']) ? 'true' : 'false';
	$conf_email = isset($_REQUEST['conf_email']) ? trim(strtolower($_REQUEST['conf_email'])) : false;
	$conf_smtp_host = isset($_REQUEST['conf_smtp_host']) ? trim(strtolower($_REQUEST['conf_smtp_host'])) : '';
	$conf_smtp_user = isset($_REQUEST['conf_smtp_user']) ? trim($_REQUEST['conf_smtp_user']) : '';
	$conf_smtp_pw = isset($_REQUEST['conf_smtp_pw']) ? $_REQUEST['conf_smtp_pw'] : '';
	$conf_smtp_port = isset($_REQUEST['conf_smtp_port']) && intval($_REQUEST['conf_smtp_port']) > 0 ? intval($_REQUEST['conf_smtp_port']) : "''";
	$conf_bdd_local_host = isset($_REQUEST['conf_bdd_local_host']) ? trim($_REQUEST['conf_bdd_local_host']) : '';
	$conf_bdd_local_port = isset($_REQUEST['conf_bdd_local_port']) && intval($_REQUEST['conf_bdd_local_port']) > 0 ? intval($_REQUEST['conf_bdd_local_port']) : "''";
	$conf_bdd_local_bdd = isset($_REQUEST['conf_bdd_local_bdd']) ? trim($_REQUEST['conf_bdd_local_bdd']) : '';
	$conf_bdd_local_user = isset($_REQUEST['conf_bdd_local_user']) ? trim($_REQUEST['conf_bdd_local_user']) : '';
	$conf_bdd_local_pw = isset($_REQUEST['conf_bdd_local_pw']) ? $_REQUEST['conf_bdd_local_pw'] : '';
	$conf_bdd_prod_host = isset($_REQUEST['conf_bdd_prod_host']) ? trim($_REQUEST['conf_bdd_prod_host']) : '';
	$conf_bdd_prod_port = isset($_REQUEST['conf_bdd_prod_port']) && intval($_REQUEST['conf_bdd_prod_port']) > 0 ? intval($_REQUEST['conf_bdd_prod_port']) : "''";
	$conf_bdd_prod_bdd = isset($_REQUEST['conf_bdd_prod_bdd']) ? trim($_REQUEST['conf_bdd_prod_bdd']) : '';
	$conf_bdd_prod_user = isset($_REQUEST['conf_bdd_prod_user']) ? trim($_REQUEST['conf_bdd_prod_user']) : '';
	$conf_bdd_prod_pw = isset($_REQUEST['conf_bdd_prod_pw']) ? $_REQUEST['conf_bdd_prod_pw'] : '';
	$conf_bdd_myadmin = isset($_REQUEST['conf_bdd_myadmin']) ? trim($_REQUEST['conf_bdd_myadmin']) : '';

	$erreur = false;

    // Gestion des erreurs
    if (!$conf_nomsite || !$conf_domaine || !$localhost || !$conf_subdom || !$conf_email 
        || !$conf_bdd_local_host || !$conf_bdd_local_bdd || !$conf_bdd_local_user || !$conf_bdd_local_pw
        || !$conf_bdd_prod_host || !$conf_bdd_prod_bdd || !$conf_bdd_prod_user || !$conf_bdd_prod_pw
    ) {
        $erreur = true;
    }


    // SI pas d'erreur
    if (!$erreur) {



    // On génère le nouveau fichier PHP
	$fp = fopen('config.params.php','w');
	$contenu = '<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Configuration générale des paramètres
Générée le '.date("d/m/Y à H:i:s").'
------------------------------------------------------*/

/**  --------------------------------------------
	 Paramètres généraux : SSL, URLs, Nom
---------------------------------------------  */
	
	$conf_nomsite 			= \''.$conf_nomsite.'\';
	$conf_domaine 			= \''.$conf_domaine.'\';
	$localhost 				= \''.$localhost   .'\';
	$conf_subdom			= \''.$conf_subdom .'\';
	$conf_ssl 				= '.  $conf_ssl    .';
	$mode_debug				= '.  $mode_debug  .';
	
/**  --------------------------------------------
	 Paramètres E-mail/SMTP
---------------------------------------------  */

	$conf_email				= \''.$conf_email       .'\';
	$conf_smtp				= '.  $conf_smtp        .';
	$conf_smtp_host			= \''.$conf_smtp_host   .'\';
	$conf_smtp_user 		= \''.$conf_smtp_user   .'\';
	$conf_smtp_pw 			= \''.$conf_smtp_pw     .'\';
	$conf_smtp_port 		= '.$conf_smtp_port   .';

/**  --------------------------------------------
	 Paramètres connexion BDD locale / production
---------------------------------------------  */

	$conf_bdd_local_host	= \''.$conf_bdd_local_host.'\';
	$conf_bdd_local_port	= '.$conf_bdd_local_port.';
	$conf_bdd_local_bdd		= \''.$conf_bdd_local_bdd.'\';
	$conf_bdd_local_user	= \''.$conf_bdd_local_user.'\';
	$conf_bdd_local_pw		= \''.$conf_bdd_local_pw.'\';

	$conf_bdd_prod_host		= \''.$conf_bdd_prod_host.'\';
	$conf_bdd_prod_port		= '.$conf_bdd_prod_port.';
	$conf_bdd_prod_bdd		= \''.$conf_bdd_prod_bdd.'\';
	$conf_bdd_prod_user		= \''.$conf_bdd_prod_user.'\';
	$conf_bdd_prod_pw		= \''.$conf_bdd_prod_pw.'\';

	$conf_bdd_myadmin		= \''.$conf_bdd_myadmin.'\';

/**  --------------------------------------------
	 FIN des paramètres
---------------------------------------------  */';



	fwrite($fp,$contenu);
	fclose($fp);

	header('Location: ../../index.php');

	} // FIN test pas d'erreurs



} // FIN test formulaire envoyé

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"/>
	<meta name="generator" content="CBO">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=device-width">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<!--[if IE]><link rel="shortcut icon" href="img/favicon.ico"><![endif]-->
	<link rel="icon" href="img/favicon.png">
	<link rel="apple-touch-icon" href="img/favicon.png">
	<link rel="apple-touch-icon" sizes="76x76"	 href="img/favicon.png">
	<link rel="apple-touch-icon" sizes="120x120" href="img/favicon.png">
	<link rel="apple-touch-icon" sizes="152x152" href="img/favicon.png">

	<title>Installation - CBO Framework</title>
	<?php
	foreach ($css_commons as $link)	{ ?>
		<link rel="stylesheet" type="text/css" href="<?php echo $link.'?'.date('md'); ?>" media="screen, print" />
	<?php }
	foreach ($js_commons as $link) { ?>
		<script type="text/javascript" src="<?php echo $link.'?'.date('md'); ?>"></script>
	<?php } ?>
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body class="install">

        <header class="container-fluid row">
            <div class="col-md-6 text-right margin-top-25">
                <img src="../../img/favicon.png">
            </div>
            <div class="col-md-6 text-left ">
                <h1 class="display-1 gris-3">CBO</h1>
                <h3 class="margin-top--35 margin-left-5 gris-9">Framework</h3>
                <p class="small gris-5 padding-left-10 margin-top--15">v.2.4.3</p>
            </div>
        </header>

        <div class="container">

            <div class="alert alert-secondary">

                <h2 class="text-center mb-3"><i class="fas fa-cogs mr-2"></i>INSTALLATION</h2>
                <?php
                $source = isset($_REQUEST['s']) ? trim(strtolower($_REQUEST['s'])) : '';
                $sources = array('pi' => "Définition des paramètres du framework", 'pe' => "Paramètres du framework absents ou corrompus", 'c' => 'Echec de connexion à la base de donnée');
                if (array_key_exists($source, $sources)) {
                $messageSource = $sources[$source]; ?>
                <div class="alert alert-danger text-center"><?php echo $messageSource; ?></div>
                <?php
                }
                if (isset($erreur) && $erreur) { ?>
                    <div class="alert alert-danger text-center">Les données comportent des erreurs, le fichier de configuration n'a pas été généré !</div>
                <?php } ?>

                <form action="install.php" method="post">

                    <h4 class="jumbotron">Paramètres généraux</h4>

                    <div class="container">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <small class="text-muted">
                                    Nom du site ou de l'application web
                                </small>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-tag mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_nomsite)) {
                                        echo strlen($conf_nomsite) > 0 ?  'is-valid' : 'is-invalid';
                                    }  ?>" placeholder="Nom du site" value="<?php echo isset($conf_nomsite) ? $conf_nomsite : ''; ?>" name="conf_nomsite">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container">
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <small class="text-muted">
                                    Domaine de développement
                                </small>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fa-fw fas fa-laptop-code mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($localhost)) {
										echo preg_match("#^[a-z0-9./]*$#", $localhost) ?  'is-valid' : 'is-invalid';
									} ?>" placeholder="localhost" value="<?php echo isset($localhost) ? $localhost : ''; ?>" name="localhost">
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <small class="text-muted">
                                    Domaine de production
                                </small>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-globe-americas mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_domaine)) {
                                        echo preg_match("#^[a-z0-9./]*$#", $conf_domaine) ?  'is-valid' : 'is-invalid';
                                    } ?>" placeholder="domaine.com" value="<?php echo isset($conf_domaine) ? $conf_domaine : ''; ?>" name="conf_domaine">
                                </div>
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">
                                    Sous-dossier du projet (facultatif)
                                </small>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-folder-open mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_subdom)) {
                                        echo preg_match("#^[a-z0-9/]*$#", $conf_subdom) ?  'is-valid' : 'is-invalid';
                                    } ?>" placeholder="sous-dossier" value="<?php echo isset($conf_subdom) ? $conf_subdom : ''; ?>" name="conf_subdom">
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <small class="text-muted">
                                    Certificat SSL
                                </small>
                                <div class="input-group">
                                    <input type="checkbox" class="switchCheckbox" name="conf_ssl"
                                        <?php if (isset($conf_ssl)) { echo $conf_ssl ?  'checked' : ''; }  else { echo 'checked'; } ?>
                                        data-on-text = "HTTPS"
                                        data-off-text = "HTTP"
                                        data-on-color = "success"
                                        data-off-color = "warning" />
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <small class="text-muted">
                                    Mode DEBUG
                                </small>
                                <div class="input-group">
                                    <input type="checkbox" class="switchCheckbox" name="mode_debug"
                                        <?php if (isset($mode_debug)) { echo $mode_debug ?  'checked' : ''; }  else { echo 'checked'; } ?>
                                        data-on-text = "Activé"
                                        data-off-text = "Désactivé"
                                        data-on-color = "info"
                                        data-off-color = "danger" />
                                </div>
                            </div>

                        </div>
                    </div>

                    <h4 class="mt-3 jumbotron">E-mail/SMTP</h4>

                    <div class="container">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">
                                    Adresse e-mail de référence
                                </small>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-envelope mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_email)) {
                                        echo preg_match("#^[a-z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$#", $conf_email) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_email) ? $conf_email : ''; ?>" name="conf_email">
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <small class="text-muted">
                                    SMTP
                                </small>
                                <div class="input-group">
                                    <input type="checkbox" class="switchCheckbox" id="switchSmtp" name="conf_smtp"
                                        <?php if (isset($conf_smtp)) { echo $conf_smtp  ?  'checked' : ''; } ?>
                                        data-on-text = "Activé"
                                        data-off-text = "Désactivé"
                                        data-on-color = "success"
                                        data-off-color = "info" />
                                </div>
                            </div>

                            <div class="col-md-3 mb-3 margin-top-25 text-right">
                                <button type="button" class="btn btn-secondary btnEnvoiMailTest"><i class="fas fa-paper-plane mr-2"></i>Envoyer un mail de test</button>
                                <div id="retourTestmail" class="small mt-1"></div>
                            </div>
                        </div>

                        <div class="collapse <?php echo isset($conf_smtp) && $conf_smtp == true ? 'show' : ''; ?>" id="collapseSmtp">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">
                                        Serveur SMTP
                                    </small>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text gris-9"><i class="fas fa-fw fa-server mr-1"></i></span>
                                        </div>
                                        <input type="text" class="form-control <?php if (isset($conf_smtp_host) && isset($conf_smtp) && $conf_smtp) {
                                            echo preg_match("#^[a-z0-9./]*$#", $conf_smtp_host) ?  'is-valid' : 'is-invalid';
                                            } ?>" placeholder="smtp.domaine.com" value="<?php echo isset($conf_smtp_host) ? $conf_smtp_host : ''; ?>" name="conf_smtp_host">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">
                                        Port SMTP
                                    </small>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text gris-9"><i class="fas fa-fw fa-door-open mr-1"></i></span>
                                        </div>
                                        <input type="text" class="form-control <?php if (isset($conf_smtp_port) && isset($conf_smtp) && $conf_smtp) {
                                            echo preg_match("#^[0-9]{2,5}$#", $conf_smtp_port) ?  'is-valid' : 'is-invalid';
                                            } ?>" placeholder="N° de port" value="<?php echo isset($conf_smtp_port) ? $conf_smtp_port : ''; ?>" name="conf_smtp_port">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">
                                        Identifiant SMTP
                                    </small>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text gris-9"><i class="fas fa-fw fa-user mr-1"></i></span>
                                        </div>
                                        <input type="text" class="form-control <?php if (isset($conf_smtp_user) && isset($conf_smtp) && $conf_smtp) {
                                            echo strlen($conf_smtp_user) > 0 ?  'is-valid' : 'is-invalid';
                                            } ?>" placeholder="exemple@domaine.com" value="<?php echo isset($conf_smtp_user) ? $conf_smtp_user : ''; ?>" name="conf_smtp_user">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">
                                        Mot de passe SMTP
                                    </small>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text gris-9"><i class="fas fa-fw fa-key mr-1"></i></span>
                                        </div>
                                        <input type="password" class="form-control <?php if (isset($conf_smtp_pw) && isset($conf_smtp) && $conf_smtp) {
                                            echo strlen($conf_smtp_pw) > 0 ?  'is-valid' : 'is-invalid';
                                            } ?>" placeholder="Mot de passe" value="<?php echo isset($conf_smtp_pw) ? $conf_smtp_pw : ''; ?>" name="conf_smtp_pw">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-3 jumbotron">Base de donnée</h4>

                    <div class="alert alert-info text-12">
                        <i class="fa fa-info-circle fa-lg mr-1"></i>
                        Si le contenu de l'en-tête <em>Host</em> correspond au domaine de développement, la base de développement est prise en compte, dans le cas contraire la base de production est appelée.
                    </div>

                    <div class="container">
                        <div class="row">

                            <div class="col-md-6 mb-3 border-right-gris">
                                <h5 class="text-center">Développement</h5>

                                <small class="text-muted">
                                    Hôte du serveur MySQL/MariaDB
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-server mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_local_host)) {
                                        echo preg_match("#^[a-z0-9./]*$#", $conf_bdd_local_host) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_local_host) ? $conf_bdd_local_host : ''; ?>" name="conf_bdd_local_host">
                                </div>

                                <small class="text-muted">
                                    Port
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-door-open mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_local_port)) {
                                        echo preg_match("#^[0-9]*$#", $conf_bdd_local_port) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_local_port) ? $conf_bdd_local_port : ''; ?>" name="conf_bdd_local_port">
                                </div>

                                <small class="text-muted">
                                    Nom de la base de donnée
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-database mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_local_bdd)) {
                                        echo preg_match("#^[a-zA-Z0-9-_]*$#", $conf_bdd_local_bdd) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_local_bdd) ? $conf_bdd_local_bdd : ''; ?>" name="conf_bdd_local_bdd">
                                </div>

                                <small class="text-muted">
                                    Utilisateur
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-user mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_local_user)) {
                                        echo preg_match("#^[a-zA-Z0-9-_]*$#", $conf_bdd_local_user) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_local_user) ? $conf_bdd_local_user : ''; ?>" name="conf_bdd_local_user">
                                </div>

                                <small class="text-muted">
                                    Mot de passe
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-key mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_local_pw)) {
                                        echo strlen($conf_bdd_local_pw) > 0 ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_local_pw) ? $conf_bdd_local_pw : ''; ?>" name="conf_bdd_local_pw">
                                </div>

                                <button type="button" class="btn btn-secondary btn-block btn-test-bdd bdd-dev"><i class="fas fa-handshake mr-1"></i> Tester la connexion</button>
                                <div id="testCnxBdd_local" class="text-center mt-1">&nbsp;</div>

                            </div>

                            <div class="col-md-6 mb-3">
                                <h5 class="text-center">Production</h5>

                                <small class="text-muted">
                                    Hôte du serveur MySQL/MariaDB
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-server mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_prod_host)) {
                                        echo preg_match("#^[a-z0-9./]*$#", $conf_bdd_prod_host) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_prod_host) ? $conf_bdd_prod_host : ''; ?>" name="conf_bdd_prod_host">
                                </div>

                                <small class="text-muted">
                                    Port
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-door-open mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_prod_port)) {
                                        echo preg_match("#^[0-9]*$#", $conf_bdd_prod_port) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_prod_port) ? $conf_bdd_prod_port : ''; ?>" name="conf_bdd_prod_port">
                                </div>

                                <small class="text-muted">
                                    Nom de la base de donnée
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-database mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_prod_bdd)) {
                                        echo preg_match("#^[a-zA-Z0-9-_]*$#", $conf_bdd_prod_bdd) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_prod_bdd) ? $conf_bdd_prod_bdd : ''; ?>" name="conf_bdd_prod_bdd">
                                </div>

                                <small class="text-muted">
                                    Utilisateur
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-user mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_prod_user)) {
                                        echo preg_match("#^[a-zA-Z0-9-_]*$#", $conf_bdd_prod_user) ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_prod_user) ? $conf_bdd_prod_user : ''; ?>" name="conf_bdd_prod_user">
                                </div>

                                <small class="text-muted">
                                    Mot de passe
                                </small>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-key mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control <?php if (isset($conf_bdd_prod_pw)) {
                                        echo strlen($conf_bdd_prod_pw) > 0 ?  'is-valid' : 'is-invalid';
                                        } ?>" placeholder="exemple@email.com" value="<?php echo isset($conf_bdd_prod_pw) ? $conf_bdd_prod_pw : ''; ?>" name="conf_bdd_prod_pw">
                                </div>

                                <button type="button" class="btn btn-secondary btn-block btn-test-bdd bdd-prod"><i class="fas fa-handshake mr-1"></i> Tester la connexion</button>
                                <div id="testCnxBdd_prod" class="text-center mt-1">&nbsp;</div>

                            </div>

                            <div class="col-md-12 mb-3">

                                <small class="text-muted">
                                    URL PhpMyAdmin pour accès rapide en mode Débug (facultatif)
                                </small>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text gris-9"><i class="fas fa-fw fa-link mr-1"></i></span>
                                    </div>
                                    <input type="text" class="form-control" placeholder="https://www.exemple.com/phpMyAdmin/" value="<?php
                                        echo isset($conf_bdd_myadmin) ? $conf_bdd_myadmin : ''; ?>" name="conf_bdd_myadmin">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btnTestPhpMyAdmin" type="button"><i class="fas fa-external-link-alt"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-3 jumbotron"><button type="submit" class="btn btn-info btn-block btn-lg"><i class="fas fa-save mr-1"></i> Enregistrer</button></h4>

                </form>
            </div>
        </div>
	</body>
</html>