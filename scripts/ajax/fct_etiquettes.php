<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax PRODUITS
------------------------------------------------------*/

use Ayeo\Barcode;
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$produitsManager = new ProduitManager($cnx);
$logsManager     = new LogManager($cnx);
$especesManager  = new ProduitEspecesManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}


/* -----------------------------------------------------
MODE - Impression des étiquettes
------------------------------------------------------*/
function modeImprimerEtiquette() {

	global $cnx, $especesManager;

	$id_fam = isset($_REQUEST['id_fam']) ? intval($_REQUEST['id_fam']) : 0;
	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
	if ($id_lot == 0 && $id_fam == 0) { exit; };

	$copies = isset($_REQUEST['copies']) ? intval($_REQUEST['copies']) : 0;
	if ($copies < 1 ) { exit; };

	$lotManager = new LotManager($cnx);
	$lot = $lotManager->getLot($id_lot);
	if (!$lot instanceof Lot && $id_fam == 0) { exit; }

    //ajout porte variable isset, pour definir s'il est pas existé 27.09.2024
	$nom_court = isset($_REQUEST['nom_court']) ? trim(strtoupper($_REQUEST['nom_court'])) : ''; 

   	if ($nom_court == '' && $id_fam == 0) { exit; }

	// Vue pour le templating de l'étiquette. Par défaut : atelier
	$vue = isset($_REQUEST['vue']) ? trim(strtolower($_REQUEST['vue'])) : 'atl';

	// Template d'étiquette pour la vue Scellés Colis
	if ($vue == 'sco') {

		etiquetteScellesColis($nom_court, $lot, $copies);

	// Template d'étiquette pour la vue  Atelier
	} else if ($vue == 'atl') {

		etiquetteAtelier($nom_court, $lot, $copies, $copies);

    // Template d'étiquette pour les vues Consommables ou Emballages
	} else if ($vue == 'con' || $vue == 'emb') {

	    etiquetteConsommable($id_fam, $copies);

	} // FIN test template de vue

	exit;

} // FIN mode


/* -----------------------------------------------------
FONCTION DEPORTEE - Génération étiquette - Atelier
------------------------------------------------------*/
function etiquetteAtelier($nom_court, Lot $lot, $copies = 1) {

	global $cnx, $especesManager;

	require_once('../../vendor/gs1/vendor/autoload.php');

	$configManager = new ConfigManager($cnx);
	$config_numagr = $configManager->getConfig('numagr');
	$numAgr = $config_numagr->getValeur();

	$numlotQuantieme =  $lot->getComposition() == 2
		? $lot->getNumlot()
		: $lot->getNumlot().Outils::getJourAnByDate(date('Y-m-d'));

	$fichierCodeBarre = 'cb-'.$numlotQuantieme.'.png';
	unlink(__CBO_UPLOADS_PATH__.'etiquettes/'.$fichierCodeBarre);

	$prefixe_code = '(01)00000000000000(10)';

	$builder = new Barcode\Builder();
	$builder->setBarcodeType('gs1-128');
	$builder->setFilename('../../uploads/etiquettes/'.$fichierCodeBarre);
	$builder->setImageFormat('png');
	$builder->setWidth(1000);
	$builder->setHeight(300);
	$builder->setFontSize(15);
	$builder->setBackgroundColor(255, 255, 255);
	$builder->setPaintColor(0, 0, 0);
	$builder->saveImage($prefixe_code.$numlotQuantieme);

	$espece = $especesManager->getEspeceByNomCourt($nom_court);

	$abattoirManager = new AbattoirManager($cnx);
	if (!$lot->getId_abattoir() || intval($lot->getId_abattoir()) == 0) { exit; }
	$abattoir = $abattoirManager->getAbattoir($lot->getId_abattoir());
	if (!$abattoir instanceof Abattoir) { exit; }

	// DLC = date de réception + 8j
	$drco =  new DateTime($lot->getDate_reception());
	$drco->add(new DateInterval('P8D'));
	$dlc = $drco->format('d/m/Y');

    ?>
    <html>
        <head>
            <style type="text/css" media="print">
                @page {
                    size: auto;   /* auto is the initial value */
                    margin: 0;  /* this affects the margin in the printer settings */
                }

                * {
                    font-family: Calibri, "Trebuchet MS", sans-serif;
                }

                h1 {
                    text-align: center;
                    font-size: 88pt;
                }

                h2 {
                    text-align: center;
                    font-size: 40pt;
                }

                h3 {
                    text-align: center;
                    font-size: 80pt;
                }

                body {
                    padding-left: 0.5cm;
                }

                .page {
                    padding-top: 0.5cm;
                    page-break-after: always
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                table td {
                    text-align: center;
                }

                ul, li {
                    list-style-type: none;
                    margin: 0;
                    padding: 0;
                    padding-left:12pt;
                    margin-top:18pt;
                }

                li {
                    padding-bottom: 12px;
                    font-weight: bold;
                    font-size: 52pt;
                }

                .text-center {
                    text-align: center;
                }

            </style>

        </head>
        <body>

        <?php

        // Boucle sur le nombre de copies
	    for ($i = 1; $i <= $copies; $i++) { ?>

            <div class="page">

                <h1><?php echo $nom_court; ?></h1>
                <h2>JOUR DE DÉCOUPE : <?php echo date('d/m/Y'); ?></h2>

                <table>
                    <tr>
                        <td width="40%" class="text-center">
                            <img class="agrement" style="" src="<?php echo __CBO_IMG_URL__ . 'agrement.jpg'; ?>"/>
                        </td>
                        <td width="60%">
                            <img class="codebarre" src="<?php echo __CBO_UPLOADS_URL__ . 'etiquettes/' . $fichierCodeBarre.'?'.date('s'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td width="40%"></td>
                        <td width="60%">
                            <h3><?php echo $numlotQuantieme; ?></h3>
                        </td>
                    </tr>
                </table>
                <ul>
                    <li>Catégorie : <?php echo $espece; ?></li>
                    <li>Abattoir : <?php echo $abattoir->getNom(); ?></li>
                    <li>Agrément abattoir : <?php echo $abattoir->getNumagr(); ?></li>
                    <li>Agrément atelier découpe : <?php echo $numAgr; ?></li>
                    <li>Origine : <?php echo strtoupper($lot->getNom_origine()); ?></li>
                    <li>Date réception : <?php echo Outils::dateSqlToFr($lot->getDate_reception()); ?></li>
                    <li>DLC : <?php echo $dlc; ?></li>
                </ul>
            </div>

          <!--  <div class="page">
                <table>
                    <tr>
                        <td width="70%">
                            <h1><?php /*echo $nom_court; */?></h1>
                            <h2>JOUR DE DÉCOUPE : <?php /*echo date('d/m/Y'); */?></h2>
                        </td>
                        <td width="30%" class="text-center">
                            <img class="agrement" style="" src="<?php /*echo __CBO_IMG_URL__ . 'agrement.jpg'; */?>"/>
                        </td>
                    </tr>
                </table>
                <div style="margin:0px;padding:0px;">
                    <img style="width:100%" class="codebarre" src="<?php /*echo __CBO_UPLOADS_URL__ . 'etiquettes/' . $fichierCodeBarre.'?'.date('s'); */?>"/>
                </div>
                <h3 style="margin:0px;padding:0px;"><?php /*echo $numlotQuantieme; */?></h3>
                <ul style="margin-top:80px;">
                    <li>Catégorie : <?php /*echo $espece; */?></li>
                    <li>Abattoir : <?php /*echo $abattoir->getNom(); */?></li>
                    <li>Agrément abattoir : <?php /*echo $abattoir->getNumagr(); */?></li>
                    <li>Agrément atelier découpe : <?php /*echo $numAgr; */?></li>
                    <li>Origine : <?php /*echo strtoupper($lot->getNom_origine()); */?></li>
                    <li>Date réception : <?php /*echo Outils::dateSqlToFr($lot->getDate_reception()); */?></li>
                    <li>DLC : <?php /*echo $dlc; */?></li>
                </ul>
            </div>-->
			<?php
		    } // FIN boucle nombre de copies
            ?>
        </body>
    </html>
    <?php

	exit;

} // FIN fonction template Etiquette Atelier


/* -----------------------------------------------------
FONCTION DEPORTEE - Génération étiquette - Atelier
------------------------------------------------------*/
function etiquetteScellesColis($nom_court, Lot $lot, $copies = 1) {
	global $cnx, $especesManager;

	$fichierCodeBarre = 'cb-'.$lot->getNumlot().'.png';

	$prefixe_code = '010000000000000010';

	$bc = new pi_barcode();
	$bc->setCode($prefixe_code.$lot->getNumlot());
	$bc->setType('C128');
	$bc->setSize(200, 1500, 10);
	$bc->setText('AUTO');
	$bc->hideCodeType();
	$bc->writeBarcodeFile(__CBO_UPLOADS_PATH__.'etiquettes/'.$fichierCodeBarre);


	$numlotQuantieme =  $lot->getComposition() == 2
		? $lot->getNumlot()
		: $lot->getNumlot().Outils::getJourAnByDate(date('Y-m-d'));


	?>
    <html>
        <head>
            <style type="text/css" media="print">
                @page {
                    size: auto;   /* auto is the initial value */
                    margin: 0;  /* this affects the margin in the printer settings */
                }
                * {
                    font-family: Calibri, "Trebuchet MS", sans-serif;
                }
                body {
                    /*padding-left: 0.5cm;*/
                    padding-left: 2.4cm;
                    text-align: center;
                    width: 50%;
                }

                .page {
                    padding-top: 0.5cm;
                    page-break-after: always
                }

                .mt08 { margin-top: .8cm; }
                .mt15 { margin-top: 1.5cm; }

                .nomcourt {
                    font-size: 90pt;
                    font-weight:bold;
                }

                .agrement {
                    margin-top; 2cm;
                }

                .codebarre {
                    visibility:hidden;
                }

            </style>

        </head>
        <body>

        <?php
        // Boucle sur le nombre de copies
	    for ($i = 1; $i <= $copies; $i++) {
		?>

            <div class="page">

                <div class="mt08">
                    <img class="codebarre" src="<?php echo __CBO_UPLOADS_URL__.'etiquettes/'.$fichierCodeBarre; ?>" />
                </div>

                <div class="nomcourt">STORE AT -18°C</div>

                <div class="mt15"><img class="agrement" src="<?php echo __CBO_IMG_URL__.'agrement.jpg'; ?>" /></div>

                <div class="mt08 nomcourt"><?php echo $nom_court; ?></div>

                <div class="mt08 nomcourt">
                    Batch number :<br>
		            <?php echo $numlotQuantieme;?>
                </div>


            </div>
		<?php
	    } // FIN boucle nombre de pages
	    ?>
        </body>
    </html>
	<?php

	exit;

} // FIN fonction template Etiquette Atelier


/* -----------------------------------------------------
FONCTION DEPORTEE - Génération étiquette - Consommables
------------------------------------------------------*/
function etiquetteConsommable($id_fam, $copies = 1) {

	global $cnx;   
	$consommablesManager = new ConsommablesManager($cnx);
	$fam = $consommablesManager->getConsommablesFamille($id_fam);
	if (!$fam instanceof ConsommablesFamille) { exit; }

	?>
	<html>
	<head>
		<style type="text/css" media="print">
			@page {
                size: auto;   /* auto is the initial value */
                margin: 0;  /* this affects the margin in the printer settings */
	            margin-top: 0.5cm;
			}

            @page :first {
                margin-top: 0;
            }
			* {
				font-family: Calibri, "Trebuchet MS", sans-serif;
			}
			body {
				text-align: center;
			}

			.page {
                padding: 0; 
                /*padding:10cm;*/
                margin: 0;
				page-break-after: always; 
                height: 15cm;
                max-height: 15cm;
                min-height: 15cm;
                /*width: 10cm;
                max-width: 10cm;
                min-width: 10cm;*/
                 width: 25cm;
                 max-width: 25cm;
                 min-width: 25cm;
			}


			.conteneur {
/*				writing-mode: vertical-rl;*/
                margin:0.5cm;
				height: 14cm;
				max-height: 14cm;
				min-height: 14cm;
                
                /*width: 9cm;
                max-width: 9cm;
                min-width: 9cm;
				text-align: center;*/
				border: 2px solid #000;
                font-weight:bold;
                page-break-inside: avoid;
                font-size: 52pt;
			}

            strong {
                display: inline-block;
                <?php
                $nbCaracs = strlen($fam->getNom());
                if ($nbCaracs < 15) {
                    echo 'margin-right: 3.5cm;';
                } else if ($nbCaracs < 30) {
                    echo 'margin-right: 2cm;';
                }
                ?>
            }
		</style>

	</head>
	<body>

	<?php
	// Boucle sur le nombre de copies
	for ($i = 1; $i <= $copies; $i++) {
		?>

		<div class="page">
			<div class="conteneur">
				<strong><?php echo strtoupper($fam->getNom()); ?></strong>
			</div>
		</div>
		<?php
	} // FIN boucle nombre de pages
	?>
	</body>
	</html>
	<?php

	exit;


} // FIN fonction template Etiquette Consommable