<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Génération de l'en-tête de page
------------------------------------------------------*/
if (!defined(__CBO_ROOT_URL__)) {
	require_once 'scripts/php/config.php';
}

// Variables header
$doctype		= isset($doctype)		? $doctype						: '<!DOCTYPE html>';  
$charset		= isset($charset)		? $charset						: 'utf-8';
$title			= isset($title)			? $title . ' - ' . $_SESSION['nom_site']	        : $_SESSION['nom_site'];
$include_code	= isset($include_code)  ? $include_code					: '';
$script_js		= isset($script_js)		? $script_js					: '';
$onload			= isset($onload)		? ' onload="' . $onload . '"'	: '';
$classBody		= isset($classBody)		? $classBody        			: '';

// Paramètres par défault non modifiables toujours chargés
if(!isset($css)){$css= array();}
if(!isset($js))	{$js = array();}

// Styles et JS des vendors :
	
$css_commons[] = 'vendor/jquery-ui/jquery-ui.min.css';
$css_commons[] = 'vendor/jquery-ui/jquery-ui.structure.min.css';
$css_commons[] = 'vendor/jquery-ui/jquery-ui.theme.min.css';
$css_commons[] = 'vendor/fontawesome/css/all.css';
$css_commons[] = 'vendor/bootstrap/css/bootstrap.min.css';
$css_commons[] = 'vendor/selectpicker/css/bootstrap-select.min.css';
$css_commons[] = 'vendor/icheck/skins/square/blue.css';
$css_commons[] = 'vendor/icheck/skins/flat/blue.css';
$css_commons[] = 'vendor/icheck/skins/flat/red.css';
$css_commons[] = 'vendor/icheck/skins/flat/green.css';
$css_commons[] = 'vendor/togglemaster/css/bootstrap-toggle.min.css';


$js_commons	[] = 'vendor/jquery/jquery-3.3.1.min.js';
$js_commons	[] = 'vendor/jquery-ui/jquery-ui.min.js';

$js_commons [] = 'vendor/bootstrap/js/bootstrap.bundle.min.js';
$js_commons [] = 'vendor/selectpicker/js/bootstrap-select.min.js';
$js_commons [] = 'vendor/icheck/icheck.min.js';
$js_commons [] = 'vendor/togglemaster/js/bootstrap4-toggle.js';



// Styles et JS communs au site :

$js_commons [] = 'scripts/js/commons.js';
$js_commons [] = 'scripts/js/main.js';

$css_commons[] = 'css/main.css';
$css_commons[] = 'css/responsive.css';


// On les ajoute avant le CSS de la page en cours en respectant l'ordre :
$css = array_merge($css_commons, $css);
$js	 = array_merge($js_commons,  $js);
	

// Page CSS associée si existante et pas déjà intégrée
$cssPage = 'css/'. str_replace('.php', '.css', basename($_SERVER['PHP_SELF']));

if (file_exists(__CBO_ROOT_PATH__.'/'.$cssPage) && !in_array($cssPage, $css)) {
	$css[]		= $cssPage;
	$css_page	= true; // Infos Debug Devtool
}

// Page JS associée si existante et pas déjà intégrée
$jsPage = 'scripts/js/'. str_replace('.php', '.js', basename($_SERVER['PHP_SELF']));
if (file_exists(__CBO_ROOT_PATH__.'/'.$jsPage) && !in_array($jsPage, $js)) {
	$js[]		= $jsPage;
	$js_page	= true; // Infos Debug Devtool
}

if (preg_match('#dev.iprex#',__CBO_ROOT_URL__)) {

	$title = '[DEV] '.$title;

} else if (preg_match('#iprex.intersed.info#',__CBO_ROOT_URL__)) {

	$title = '[PREPROD] '.$title;

}

//--------------------------------------------
//	Construction de la page HTML
//--------------------------------------------
echo $doctype; ?>
<html>
	<head>
		<meta charset="<?php echo $charset; ?>"/>
		<meta name="generator" content="CBO">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=device-width">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<!--[if IE]><link rel="shortcut icon" href="<?php echo __CBO_ROOT_URL__; ?>img/favicon.ico"><![endif]-->
		<link rel="icon" href="<?php echo __CBO_ROOT_URL__; ?>img/favicon.png">
		<link rel="apple-touch-icon" href="<?php echo __CBO_ROOT_URL__; ?>img/favicon.png">
		<link rel="apple-touch-icon" sizes="76x76"	 href="<?php echo __CBO_ROOT_URL__; ?>img/favicon.png">
		<link rel="apple-touch-icon" sizes="120x120" href="<?php echo __CBO_ROOT_URL__; ?>img/favicon.png">
		<link rel="apple-touch-icon" sizes="152x152" href="<?php echo __CBO_ROOT_URL__; ?>img/favicon.png">

        <meta name="robots" content="noindex, nofollow">

		<title><?php echo $title; ?></title>
			
		<?php
		//--------------------------------------------
		// Boucle CSS
		//--------------------------------------------
		if (isset($css)) {
			foreach ($css as $link)	{ ?>
		        <link rel="stylesheet" type="text/css" href="<?php echo __CBO_ROOT_URL__.$link.'?'.date('md'); ?>" media="screen, print" />
			<?php }
		} // FIN boucle CSS

		//--------------------------------------------
		// Boucle JS
		//--------------------------------------------
		if (isset($js))	{
			foreach ($js as $link) { ?>
                <script type="text/javascript" src="<?php echo __CBO_ROOT_URL__.$link.'?'.date('mdH'); ?>"></script>
			<?php }
		} // FIN appels JS
        ?>

		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body<?php echo $classBody != '' ? ' class="'.$classBody.'"' :  '';  echo $onload; ?>>
    <div id="body2fs">

    <?php include('header-content.php');