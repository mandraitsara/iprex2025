<?php
$logsManager    = new LogManager($cnx);
$cronsManager   = new CronsManager($cnx);
$params         =  ['actives' => true];
$crons          = $cronsManager->getListeCrons($params);
foreach ($crons as $cron) {

	$mois 	= is_numeric($cron->getMois()) && $cron->getMois() > 0 ?  sprintf("%02d",$cron->getMois()) : date('m');
	$jour 	= is_numeric($cron->getJour_mois()) && $cron->getJour_mois() > 0 ?  sprintf("%02d",$cron->getJour_mois()) : date('d');
	$heure 	= is_numeric($cron->getHeure()) && $cron->getHeure() >= 0 ? sprintf("%02d",$cron->getHeure()) : date('H');
	$min 	= is_numeric($cron->getMinute()) && $cron->getMinute() >= 0 ? sprintf("%02d",$cron->getMinute()) : date('i');
	$dateTheorique = date('Y').'-'.$mois.'-'.$jour.' '.$heure.':'.$min;

	// La prochaine exécution n'est pas encore là...
	if ($dateTheorique >= date('Y-m-d H:i')) {
		continue;
	}

	// Si la dernière exécution date du meme jour que le théorique, on passe
	if ($cron->getExecutionWithoutSeconds() !== '' && substr($cron->getExecutionWithoutSeconds(),0,-6) == substr($dateTheorique,0,-6)) {
		continue;
	} else {
        $log = new Log();
		$retour = 'Exécution de la tâche CRON ' . $cron->getFichier() . " à la navigation... ";
		if (include_once __CBO_ROOT_PATH__.'/'.$cron->getChemin().$cron->getFichier().'.php') {
			$retour.= ' [Réussie]';
			$log->setLog_type('info');
		} else {
			$retour.= ' [Echouée !]';
			$log->setLog_type('danger');
		}
		$log->setLog_texte($retour);
		$logsManager->saveLog($log);
	}
}