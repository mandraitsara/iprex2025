<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax LOTS
------------------------------------------------------*/
ini_set('display_errors',0);
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$logsManager = new LogManager($cnx);
$lotsManager = new LotManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* --------------------------------------
MODE - Retourne la liste des lots (aJax)
---------------------------------------*/
function modeShowListeLots() {

	global
	$utilisateur,
	$cnx;
    $lotsManager            = new LotManager($cnx);
    
    // $abattoirsManager       = new AbattoirManager($cnx);
    // $paysManager            = new PaysManager($cnx);
    // $consommablesManager    = new ConsommablesManager($cnx);
    // $produitsManager        = new ProduitManager($cnx);
    // $tiersManager           = new TiersManager($cnx);

    // Filtres

   
    $regexDateFr        =  '#^(([1-2][0-9]|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9][0-9]{2})$#';

    $filtre_numlot      = isset($_REQUEST['filtre_numlot'])     ? trim(strtoupper($_REQUEST['filtre_numlot']))      : '';
    $filtre_emballage   = isset($_REQUEST['filtre_emballage'])  ? trim(strtoupper($_REQUEST['filtre_emballage']))   : '';
    $filtre_palette     = isset($_REQUEST['filtre_palette'])    ?  intval(preg_replace("/[^0-9]/", "", $_REQUEST['filtre_palette'])) : 0;
    $mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
    $filtre_debut       = isset($_REQUEST['filtre_debut'])      && preg_match($regexDateFr, $_REQUEST['filtre_debut'])  ? $_REQUEST['filtre_debut']  : '';
    $filtre_fin         = isset($_REQUEST['filtre_fin'])        && preg_match($regexDateFr, $_REQUEST['filtre_fin'])    ? $_REQUEST['filtre_fin']    : '';

    $filtre_abattoirs_array = [];
    $filtre_frs_array       = [];
    $filtre_pays_array      = [];
    $filtre_clients_array   = [];
    $filtre_produits_array  = [];

    $filtre_abattoirs       = '';
    $filtre_frs             = '';
    $filtre_pays            = '';
    $filtre_clients         = '';
    $filtre_produits        = '';

if (isset($_REQUEST['filtre_abattoirs'])) {
	$filtre_abattoirs_array = is_array($_REQUEST['filtre_abattoirs']) ? $_REQUEST['filtre_abattoirs'] : explode(',', $_REQUEST['filtre_abattoirs']);
	$filtre_abattoirs       = implode(',', $filtre_abattoirs_array);
}
if (isset($_REQUEST['filtre_fournisseurs'])) {
	$filtre_frs_array = is_array($_REQUEST['filtre_fournisseurs']) ? $_REQUEST['filtre_fournisseurs'] : explode(',', $_REQUEST['filtre_fournisseurs']);
	$filtre_frs       = implode(',', $filtre_frs_array);
}
if (isset($_REQUEST['filtre_pays'])) {
	$filtre_pays_array = is_array($_REQUEST['filtre_pays']) ? $_REQUEST['filtre_pays'] : explode(',', $_REQUEST['filtre_pays']);
	$filtre_pays       = implode(',', $filtre_pays_array);
}
if (isset($_REQUEST['filtre_clients'])) {
	$filtre_clients_array = is_array($_REQUEST['filtre_clients']) ? $_REQUEST['filtre_clients'] : explode(',', $_REQUEST['filtre_clients']);
	$filtre_clients       = implode(',', $filtre_clients_array);
}
if (isset($_REQUEST['filtre_produits'])) {
	$filtre_produits_array = is_array($_REQUEST['filtre_produits']) ? $_REQUEST['filtre_produits'] : explode(',', $_REQUEST['filtre_produits']);
	$filtre_produits       = implode(',', $filtre_produits_array);
}

// Filtres pour pagination et redirections formulaires
$filtres = '';
$filtres.= '?mode='.$mode;
// $filtres.= $filtre_numlot != '' ? '?filtre_numlot='.$filtre_numlot : '';
$filtres.= isset($_REQUEST['filtre_numlot'])	    ? ($filtres != '' ? '&' : '?').'filtre_numlot='    .$filtre_numlot    : '';
$filtres.= isset($_REQUEST['filtre_emballage'])	    ? ($filtres != '' ? '&' : '?').'filtre_emballage='    .$filtre_emballage    : '';
$filtres.= isset($_REQUEST['filtre_fournisseurs']) 	? ($filtres != '' ? '&' : '?').'filtre_fournisseurs=' .$filtre_frs          : '';
$filtres.= isset($_REQUEST['filtre_abattoirs']) 	? ($filtres != '' ? '&' : '?').'filtre_abattoirs='    .$filtre_abattoirs    : '';
$filtres.= isset($_REQUEST['filtre_pays'])	        ? ($filtres != '' ? '&' : '?').'filtre_pays='         .$filtre_pays         : '';
$filtres.= isset($_REQUEST['filtre_clients'])	    ? ($filtres != '' ? '&' : '?').'filtre_clients='      .$filtre_clients      : '';
$filtres.= isset($_REQUEST['filtre_produits'])	    ? ($filtres != '' ? '&' : '?').'filtre_produits='     .$filtre_produits     : '';
$filtres.= isset($_REQUEST['filtre_debut'])		    ? ($filtres != '' ? '&' : '?').'filtre_debut='        .$filtre_debut        : '';
$filtres.= isset($_REQUEST['filtre_fin'])		    ? ($filtres != '' ? '&' : '?').'filtre_fin='          .$filtre_fin          : '';
$filtres.= isset($_REQUEST['filtre_palette'])		? ($filtres != '' ? '&' : '?').'filtre_palette='      .$filtre_palette      : '';

$filtresPagination = $filtres;

	// Préparation pagination (Ajax)
	$nbResultPpage      = 20;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$start              = ($page-1) * $nbResultPpage;

	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;
	//$params['order'] 			= $statut == 1 ? 'date_maj' : 'date_out';
	$params['order'] 			= 'id';
	// $params['recherche'] 			= $recherche;
 
    $params['start']            = $start;
    $params['nb_result_page']   = $nbResultPpage;
    $params['numlot']           = $filtre_numlot;
    $params['emballage']        = $filtre_emballage;
    $params['origines']         = $filtre_pays;
    $params['abattoirs']        = $filtre_abattoirs;
    $params['frs']              = $filtre_frs;
    $params['clients']          = $filtre_clients;
    $params['produits']         = $filtre_produits;
    $params['palette']          = $filtre_palette;
    $params['date_debut']       = $filtre_debut != '' ? Outils::dateFrToSql($filtre_debut)  : '';
    $params['date_fin']         = $filtre_fin   != '' ? Outils::dateFrToSql($filtre_fin)    : '';

    // var_dump($params);
    
    $liste_resultats = $lotsManager->getListeLotsTracabiliteByVue($params);   
    
    
            // test si aucun
            if (empty($liste_resultats)) {
				?>
                <div class="alert alert-warning astuces">
                    <i class="fa fa-info-circle fa-lg mr-1"></i>Aucun lot correspondant aux critères de recherche
                    sélectionnés&hellip;
                    <p class="mt-2 pl-2 text-14">Astuces de recherche:</p>
                    <ul class="text-14 pl-4">
                        <li>Sélectionnez plusieurs abattoirs, origines, clients ou produits.</li>
                        <li>Saisissez juste une partie du numéro de lot ou du code emballage recherché.</li>
                        <li>Bornez les dates pour rechercher sur l'ensemble des opérations (réception, abattage, sortie&hellip;).</li>
                    </ul>
                </div>
				<?php
			} else {
                // Liste non vide, construction de la pagination...
                $nbResults  = $lotsManager->getNb_results();
                $pagination = new Pagination($page);
                $pagination->setUrl($filtresPagination);
                $pagination->setNb_results($nbResults);
                $pagination->setAjax_function(true);
                $pagination->setNb_results_page($nbResultPpage);
				?>
                <table class="admin w-100">
                    <thead>
                    <tr>
                        <th>Lot</th>
                        <th class="d-none d-lg-table-cell">Réception</th>
                        <th class="d-none d-lg-table-cell">Pays</th>
                        <th>Abattoir</th>
                        <th>Fournisseur</th>
                        <th>Etat</th>
                        <th class="t-actions w-mini-admin-cell">Documents</th>
                        <th class="t-actions w-mini-admin-cell">Détails</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php

                    
                    foreach ($liste_resultats as $res) {                         
                        ?>
                        <tr>
                            <td class="text-20"><?php echo $res->getNumlot(); ?></td>
                            <td class="d-none d-lg-table-cell"><?php echo Outils::getDate_only_verbose($res->getDate_reception(), true, false); ?></td>
                            <td class="d-none d-lg-table-cell"><?php echo $res->getNom_origine(); ?></td>
                            <td><?php echo $res->getNom_abattoir(); ?></td>
                            <td><?php echo $res->getNom_fournisseur(); ?></td>                            
                            <td><span class="text-14 badge badge-<?php
                            if (!$res->isEnCours()) {
                                echo 'danger';
                            } else {
                                echo 'info';
                            }
                                ?>"><?php
                            echo !$res->isEnCours()
                                ? 'Sorti le ' . Outils::getDate_only_verbose($res->getDate_out(),true, false)
                                : 'En production';
                                    ?></span></td>
                            <td class="t-actions w-mini-admin-cell">
                                <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalLotDocs" data-lot-id="<?php
								echo $res->getId(); ?>"><i class="fas fa-copy"></i>
                                </button>
                            </td>
                            <td class="t-actions w-mini-admin-cell">
                                <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalLotInfo" data-lot-id="<?php
								echo $res->getId(); ?>"><i class="fa fa-ellipsis-h"></i> </button>
                            </td>
                        </tr>
                    <?php } // FIN boucle résultats
					?>
                    </tbody>
                </table>
				<?php
                // Pagination (aJax)
				if (isset($pagination)) {
					$pagination->setVerbose_pagination(1);
					$pagination->setVerbose_position('right');
					$pagination->setNature_resultats('lot');
            		$pagination->setNb_apres(2);
		            $pagination->setNb_avant(2);
		            echo ($pagination->getPaginationHtml());
				}
			} // FIN résultats
            ?>
        </div>
        </div>     
   <?php

}
