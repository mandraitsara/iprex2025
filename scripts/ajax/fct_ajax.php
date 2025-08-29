<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Contrôleur Ajax du FrameWork
------------------------------------------------------*/

/**
 *****************************************
 *  /!\ NE PAS EDITER CE FICHIER COEUR  *
 ****************************************
 */

require_once '../php/config.php';

$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------
MODE - EXEMPLE ToolKit
------------------------------------*/
function modeModeAjax() {

	echo 'ok~'; ?>

	    <div class="alert alert-success"><i class="fa fa-check"></i> Retour ajax fonctionnel.</div>

	<?php
    exit;

} // FIN mode



/* ------------------------------------
 	DEBUG BAR - Mode debug à false
 ------------------------------------*/
function modeCloseDebugBar() {

	setcookie("cbodebugoff", 1, time()+3600,  $_SESSION['sub_domain']);
	exit;

} // FIN mode

/* ------------------------------------
 SQL Console
------------------------------------*/
function modeSql() {

    global
        $cnx;

	$query = isset($_REQUEST['query']) ? $_REQUEST['query'] : '';
    if ($query == '') { exit; }

    try {
		$stmt = @$cnx->prepare($query);
		$res = @$stmt->execute();
		if (!$res) {
			echo $stmt->errorInfo()[2]."\n";
			exit;
        }

		$result = $stmt->fetchAll();
		print_r($result);

    } catch(Exception $e) {
		echo "Erreur SQL !";
    }

    exit;
} // FIN mode

/* --------------------------------------------------
SQL Console : détail des requêtes en session (ajax)
---------------------------------------------------*/
function modeDetailsRequetesPdoDebug() {

    foreach ($_SESSION['pdoq'] as $manager => $queries) { ?>

        <p><span><?php echo $manager; ?></span><?php
        foreach ($queries as $query) {

            if (!is_array($query)) { ?> <code><?php echo $query; ?></code> <?php  } else {
                $q = isset($query['q']) ? trim($query['q']) : '';
                $v = isset($query['v']) ? trim($query['v']) : '';
                ?><code><?php echo $q; ?><span class="bindvalues-dbx"><?php echo $v; ?></span></code> <?php
            }
            ?>

        <?php }
        ?></p>

    <?php } // FIN boucle
    exit;

} // FIN MODE

/* --------------------------------------------------
SQL Console : RAZ détail des requêtes en session (ajax)
---------------------------------------------------*/
function modeCleanDetailsRequetesPdoDebug() {

	$_SESSION['pdoq'] = [];
	exit;

} // FIN MODE


function modeGetListeLogs() {


    global $cnx, $mode;

	$logsManager = new LogManager($cnx);

	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
	$pagination = new Pagination($page);
	$pagination->setNb_results_page(20);
	$pagination->setNb_avant(3);
	$pagination->setNb_apres(3);

	$nbResultPpage_defaut = 25;
	$nbResultPpage     = isset($_REQUEST['nb_result_p_page']) ? intval($_REQUEST['nb_result_p_page'])   : 25;
	if ($nbResultPpage == 0) { $nbResultPpage = $nbResultPpage_defaut; }
	$start              = ($page-1) * $nbResultPpage;

	$params['start'] = $start;
	$params['nb_results_p_page'] = $nbResultPpage;



// Filtres
	$regexDateFr =  '#^(([1-2][0-9]|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9][0-9]{2})$#';

	$filtre_user    = isset($_REQUEST['filtre_user'])   ? intval($_REQUEST['filtre_user'])           : 0;
	$filtre_type    = isset($_REQUEST['filtre_type'])   ? trim(strtolower($_REQUEST['filtre_type'])) : '';
	$filtre_desc    = isset($_REQUEST['filtre_desc'])   ? trim(strip_tags($_REQUEST['filtre_desc'])) : '';
	$filtre_ip      = isset($_REQUEST['filtre_ip'])     ? trim(strip_tags($_REQUEST['filtre_ip']))   : '';
	$filtre_debut   = isset($_REQUEST['filtre_debut'])  && preg_match($regexDateFr, $_REQUEST['filtre_debut'])  ? $_REQUEST['filtre_debut']  : '';
	$filtre_fin     = isset($_REQUEST['filtre_fin'])    && preg_match($regexDateFr, $_REQUEST['filtre_fin'])    ? $_REQUEST['filtre_fin']    : '';

	if ($filtre_user   > 0 ) { $params['user']  = $filtre_user;  }
	if ($filtre_type  != '') { $params['type']  = $filtre_type;  }
	if ($filtre_desc  != '') { $params['desc']  = $filtre_desc;  }
	if ($filtre_ip    != '') { $params['ip']    = $filtre_ip;    }
	if ($filtre_debut != '') { $params['debut'] = $filtre_debut; }
	if ($filtre_fin   != '') { $params['fin']   = $filtre_fin;   }


// Filtres pour pagination et redirections formulaires
	$filtres = '?mode='.$mode;
	$filtres.= $filtre_user > 0 ? '&filtre_user='.$filtre_user : '';
	$filtres.= isset($_REQUEST['filtre_type'])		? ($filtres != '' ? '&' : '?').'filtre_type=' .$filtre_type : '';
	$filtres.= isset($_REQUEST['filtre_desc'])		? ($filtres != '' ? '&' : '?').'filtre_desc=' .$filtre_desc : '';
	$filtres.= isset($_REQUEST['filtre_ip'])		? ($filtres != '' ? '&' : '?').'filtre_ip=' .$filtre_ip : '';
	$filtres.= isset($_REQUEST['filtre_debut'])		? ($filtres != '' ? '&' : '?').'filtre_debut=' .$filtre_debut : '';
	$filtres.= isset($_REQUEST['filtre_fin'])		? ($filtres != '' ? '&' : '?').'filtre_fin=' .$filtre_fin : '';

	$filtresPagination = $filtres;

	$pagination->setAjax_function(true);
	$pagination->setUrl($filtresPagination);

	$listeLogs = $logsManager->getLogs($params);

	$pagination->setNb_results(!empty($listeLogs) ? $logsManager->getNb_results() : 0);


    ?>
    <p class="texte-fin gris-7 nomargin"><i class="fa fa-clock mr-1 fa-sm"></i> Mis à jour à <?php echo date('H:i');?></p>
        <?php
	// Pas de logs
	if (empty($listeLogs)) { ?>


        <div class="alert alert-secondary">
            Aucun log.
        </div>

		<?php
		// Des logs ont été trouvés
	} else {
		?>




        <table class="admin w-100">
            <thead>
            <tr>
                <th class="text-center">ID</th>
                <th>Date/heure !!</th>
                <th>Type</th>
                <th>Description</th>
                <th>Utilisateur</th>
                <th>IP</th>
            </tr>
            </thead>
            <tbody>
			<?php

			foreach ($listeLogs as $log) { ?>

                <tr>
                    <td class="text-center"><span
                                class="badge badge-secondary badge-pill texte-fin"><?php echo $log->getId(); ?></span>
                    </td>
                    <td class="nowrap texte-fin text-12"><?php echo ucfirst(Outils::getDate_verbose($log->getLog_datetime(), false)); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $log->getLog_type(); ?> badge-pill texte-fin text-12"><?php echo Outils::getAlertesTypes()[$log->getLog_type()]; ?></span>
                    </td>
                    <td class="texte-fin text-12"><?php echo str_replace(',', ', ', $log->getLog_texte()); ?></td>
                    <td class="texte-fin text-12"><?php echo $log->getLog_user_id() > 0 ? $log->getNom_user() : '&mdash;'; ?></td>
                    <td><kbd><?php echo $log->getLog_ip(); ?></kbd></td>
                </tr>

			<?php } ?>
            </tbody>
        </table>
		<?php

		if (isset($pagination)) {
			// Pagination bas de page, verbose...
			$pagination->setVerbose_pagination(1);
			$pagination->setVerbose_position('right');
			$pagination->setNature_resultats('log');

			echo $pagination->getPaginationHtml();
		}

	} // FIN test résultats
    exit;

} // FIN mode


function modeShowLogSqlFile() {

    $chemin = isset($_REQUEST['chemin']) ? str_replace('//', '/', trim($_REQUEST['chemin'])) : '';
    if ($chemin == '') { ?>
        <div class="alert alert-danger"><i class="fa fa-frown fa-3x m-3"></i><<br>Chemin non défini !</div>
    <?php exit; }
	if (!file_exists($chemin)) { ?>
        <div class="alert alert-danger"><i class="fa fa-frown fa-3x m-3"></i><<br>Fichier introuvable !</div>
	<?php exit; }

	$handle = fopen($chemin, 'r');
    if (!$handle) { ?>
        <div class="alert alert-danger"><i class="fa fa-frown fa-3x m-3"></i><<br>Impossible d'ouvrir le fichier !</div>
	<?php exit; } ?>

    <table class="table table-admin table-blfact">


    <?php

	while (!feof($handle)) {

		$buffer = fgets($handle);
		$bufferCols = explode(';', $buffer);
        if (!isset($bufferCols[1])) { continue; }

        echo '<tr><td class="texte-fin text-12">'. $bufferCols[0].'</td><td class="text-left">'.str_replace(',', ', ', $bufferCols[1]).'</td></tr>';
	}
	fclose($handle);
    ?>
    </table>
    <?php



} // FIN mode