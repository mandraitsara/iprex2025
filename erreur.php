<?php
/**
------------------------------------------------------------------------
GESTION DES PAGES D'ERREUR

Copyright (C) 2019 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2019 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */
$erreurs 	= [401,403,404,500,502,503,504];
$erreur  	= isset($_REQUEST['e']) && in_array(intval($_REQUEST['e']), $erreurs) ? intval($_REQUEST['e']) : 404;
$texte 		= '';
switch($erreur) {
	case 401:
		$texte 		= "Vous n'êtes pas autorisé à atteindre cette page&hellip;";
		break;
	case 403:
		$texte 		= "Vous n'êtes pas autorisé à atteindre cette page&hellip;";
		break;
	case 404:
		$texte 		= "La page demandée est introuvable&hellip;";
		break;
	case 500:
		$texte 		= "Une erreur interne au serveur est survenue&hellip;";
		break;
	case 502:
		$texte 		= "Passerelle incorrecte (Bad Gateway)";
		break;
	case 503:
		$texte 		= "Le service est momentanément indisponible&hellip;";
		break;
	case 504:
		$texte 		= "Temps d'attente expiré (Gateway Timeout)";
		break;
}

?>
<!DOCTYPE html><html>
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
    <style type="text/css">
        body {
            background-color: #777;
            color: #fff;
            font-family: "Calibri Light", Calibri, "Trebuchet MS", Verdana, Sans-serif;
        }

        h1 {
            text-align: center;
            font-size: 10em;
            margin-bottom: 0px;
        }

        h2 {
            font-weight: normal;
            text-align: center;
            font-size: 2em;
            border-top: 1px solid #ccc;
            margin-top: 0;
        }

        .retour {
            text-align: center;
        }

        .retour a {
            display: inline-block;
            text-decoration: none;
            color: #fff;
            font-size: 1.2em;
            border:2px solid #fff;
            padding: 5px 10px;
            border-radius: 5px;
        }
    </style>

	<title>Oups !</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>
<h1><?php echo substr($erreur,0,1); ?><i class="far fa-frown-open"></i><?php echo substr($erreur,strlen($erreur)-1,1); ?></h1>
<h2><?php echo $texte; ?></h2>
<?php if ($erreur != 403) { ?>
<div class="retour">
	<a href="http://intersed.info/iprex/">Retour à l'accueil</a>
</div>
<?php } ?>
<body>
</body>
</html>

