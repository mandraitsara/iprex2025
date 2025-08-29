<?php
/* ******************************************************************************
 *  TACHE CRON
 *  Clôture des lots 15 jours après la traça faite
 *
 *  (c) Cédric Bouillon 2020
 *  INTERSED
 ****************************************************************************** */
ini_set('display_errors' ,1);

require_once dirname( __FILE__ ).'/../php/config.cli.php';
require_once dirname( __FILE__ ).'/../../class/Cron.class.php';
require_once dirname( __FILE__ ).'/../../class/CronsManager.class.php';
require_once dirname( __FILE__ ).'/../../class/Log.class.php';
require_once dirname( __FILE__ ).'/../../class/LogManager.class.php';

$email_to	= 'ppactol@boostervente.com';
$email_from = 'contact@profilexport.fr';

$cronsManager = new CronsManager($cnx);
$cron = $cronsManager->getCronByFileName(basename(__FILE__));
if (!$cron instanceof Cron) { exit('Non déclaré en BSDD'); }
if (!$cron->isActif()) { exit('Inactif'); }
$cron->setExecution(date('Y-m-d H:i:s'));
$cronsManager->saveCron($cron);

/* ******************************************************************************
 *  PARAMETRES
 ****************************************************************************** */
$mail_admin = 'ppactol@boostervente.com';
$nom_cron	= 'Clôture des lots dont la traçabilité a été validé depuis 15 jours';
/* ******************************************************************************
 *  FIN PARAMETRES
 ****************************************************************************** */

$show_debug = isset($_REQUEST['show_debug']) || isset($_REQUEST['debug']);
if ($show_debug) { ?>

	<!DOCTYPE html><html>
	<head>
		<meta charset="utf-8"/>
		<meta name="robots" content="noindex, nofollow">
		<title>iPrex</title>
		<style type="text/css">
            body { font-family: "Courier New", Courier, monospace; margin: 0; padding: 10px; }
            body * { padding: 0;}

		</style>
	</head>
<body>
<h1>CRON iPrex</h1><hr>
<h2><?php echo $nom_cron; ?></h2><hr>
    <ul>
<?php }
$logsManager = new LogManager($cnx);


echo $show_debug ? '<li>Cloture des lots... ' : '';

$query_upd = 'UPDATE `pe_lots` SET `date_out` = NOW() WHERE `date_out` IS NULL AND `test_tracabilite` >= NOW() - INTERVAL 15 DAY ';
$query = $cnx->prepare($query_upd);
$res1 = $query->execute();
$nb = $res1 ? $query->rowCount() : 0;
$pluriel = $nb > 1 ? 's' : '';
if ($show_debug) { echo $res1 ? '[OK] ' . $nb . ' lot'.$pluriel.' clôturé'.$pluriel : '[ERREUR]'; echo '</li>'; }
else {
    if ($res1) {
		$log = new Log();
		$log->setLog_type('success');
		$log->setLog_texte("[CRON] - Clôture des lots automatique 15j apres traçabilité validée : OK");
		$logsManager->saveLog($log);
    } else {
		envoiMail([$email_to], $email_from, "ERREUR CRON IPREX", utf8_decode("[CRON] - Echec de la clôture des lots automatique 15j apres traçabilité validée !"));
    }
}

echo $show_debug ? '<li>Suppression des vues... ' : '';
$query_del = 'DELETE FROM `pe_lot_vues` WHERE `id_lot` IN (SELECT `id` FROM `pe_lots` WHERE `date_out` IS NOT NULL)';

$query2 = $cnx->prepare($query_del);
$res2 = $query2->execute();
$nb = $res2 ? $query2->rowCount() : 0;
$pluriel = $nb > 1 ? 's' : '';
if ($show_debug) { echo $res2 ? '[OK] ' . $nb . ' association'.$pluriel.' de vue supprimée'.$pluriel : '[ERREUR]'; echo '</li>'; }
else {
	if ($res2) {
		$log = new Log();
		$log->setLog_type('success');
		$log->setLog_texte('[CRON] - Suppression des associations de vues sur clôture des lots : ' . $nb . ' supprimée'.$pluriel);
		$logsManager->saveLog($log);
	} else {
		envoiMail([$email_to], $email_from, "ERREUR CRON IPREX", utf8_decode("[CRON] - Echec de la suppression des associations de vues sur clôture des lots automatique 15j apres traçabilité validée !"));
	}
}

if ($show_debug) { ?>
    </ul>
	</body>
	</html>
<?php }