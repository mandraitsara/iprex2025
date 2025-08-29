<?php
/**
------------------------------------------------------------------------
SCRIPT PHP - Purge des logs

Copyright (C) 2018 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */

$params['nb_results_p_page'] = 1000000000;
$liste_logs = $logsManager->getLogs($params);

if (!empty($liste_logs)) {

	// On prépare le fichier de log
	$fichier = date('YmdHis').".log";
	$logfile = fopen("scripts/logs/".$fichier, "w");


	$logline = "ID\tDate\tType\tDescription\tUser\tIP\tNom\n-------------------------------------------\n";
	fwrite($logfile, $logline);

	foreach ($liste_logs as $log) {

		$logline = "#" . $log->getId();
		$logline .= "\t" . $log->getLog_datetime();
		$logline .= "\t" . $log->getLog_type();
		$logline .= "\t" . $log->getLog_texte();
		$logline .= "\t" . $log->getLog_user_id();
		$logline .= "\t" . $log->getLog_ip();
		$logline .= "\t" . $log->getNom_user();
		$logline .= "\n";
		fwrite($logfile, $logline);

	} // FIN boucle logs
	$logline = "-------------------------------------------\n##### Généré le " . date('d/m/Y à H:i:s') . " #####\n";
	fwrite($logfile, $logline);
	fclose($logfile);

	$logok = file_exists('scripts/logs/' . $fichier);

	if ($logok) {

		$logsManager->purgeLogs();

	} // FIN test let's go purging...


	?>
	<div class="col">
		<div class="alert alert-<?php echo $logok ? 'success' : 'danger'; ?>">
			<i class="fa fa-<?php echo $logok ? 'check' : 'exclamation-triangle'; ?> fa-lg "></i> <?php echo $logok ? 'Journal des logs purgé et archivé.' : 'ERREUR dans la génération du journal des logs ! Purge abandonnée.'; ?>
		</div>
	</div>
	<?php
} // FIN test logs pas vide
