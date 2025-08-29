<?php
/**
------------------------------------------------------------------------
PAGE - ADMIN - Lots - Tracabilité

Copyright (C) 2018 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */
require_once 'scripts/php/config.php';
require_once 'scripts/php/check_admin.php';

$h1     = 'Recherche de traçabilité !!';
$h1fa   = 'fa-fw fa-map-marked';

include('includes/header.php');

$lotsManager            = new LotManager($cnx);
$abattoirsManager       = new AbattoirManager($cnx);
$paysManager            = new PaysManager($cnx);
$consommablesManager    = new ConsommablesManager($cnx);
$produitsManager        = new ProduitManager($cnx);
$tiersManager           = new TiersManager($cnx);



// Filtres
$regexDateFr        =  '#^(([1-2][0-9]|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9][0-9]{2})$#';

$filtre_numlot      = isset($_REQUEST['filtre_numlot'])     ? trim(strtoupper($_REQUEST['filtre_numlot']))      : '';
$filtre_emballage   = isset($_REQUEST['filtre_emballage'])  ? trim(strtoupper($_REQUEST['filtre_emballage']))   : '';
$filtre_palette     = isset($_REQUEST['filtre_palette'])    ?  intval(preg_replace("/[^0-9]/", "", $_REQUEST['filtre_palette'])) : 0;

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

if(isset($_REQUEST['filtre_emballage'])){
    echo 'envoyé';
}

// Filtres pour pagination et redirections formulaires
$filtres = '';
$filtres.= $filtre_numlot != '' ? '?filtre_numlot='.$filtre_numlot : '';
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

$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
$pagination = new Pagination($page);
$pagination->setNb_results_page(20);
$pagination->setNb_avant(2);
$pagination->setNb_apres(2);
 
$params['start']            = $pagination->getStart();
$params['nb_result_page']   = $pagination->getNb_results_page();
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
 
$pagination->setUrl($filtresPagination);


$pagination->setNb_results(!empty($liste_resultats) ? $lotsManager->getNb_results() : 0);
?>
<div class="container-fluid">
    <div class="alert alert-danger d-md-none">
        <i class="fa fa-exclamation-circle text-28 mr-1 v-middle"></i>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cette page&hellip;
    </div>
    <div class="row d-none d-md-flex">
        <div class="col">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="filtres" class="row">
                <input type="hidden" name="mode" value="telecharger" />
                <input type="hidden" name="page" value="1" />
                <div class="col-12 col-xl-10">
                    <div class="row">
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-box gris-9"></i></span>
                                </div>
                                <input type="text" class="form-control" placeholder="Numéro de lot" name="filtre_numlot" value="<?php echo $filtre_numlot; ?>">
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-box-open gris-9"></i></span>
                                </div>
                                <input type="text" class="form-control" placeholder="Code emballage" name="filtre_emballage" value="<?php echo $filtre_emballage; ?>">
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text gris-9">Du&hellip;</span>
                                </div>
                                <input type="text" class=" datepicker form-control" placeholder="Date début" name="filtre_debut" value="<?php echo $filtre_debut; ?>">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt gris-9"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text gris-9" >&hellip;au</span>
                                </div>
                                <input type="text" class=" datepicker form-control" placeholder="Date fin" name="filtre_fin" value="<?php echo $filtre_fin; ?>">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt gris-9"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-pallet gris-9"></i></span>
                                </div>
                                <input type="text" class="form-control" placeholder="N° de palette" name="filtre_palette" value="<?php echo $filtre_palette > 0 ? $filtre_palette : ''; ?>">
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <select class="selectpicker show-tick form-control" title="Abattoirs" name="filtre_abattoirs[]" multiple data-selected-text-format="count > 1" data-actions-box="true">
                                    <?php  foreach ($abattoirsManager->getListeAbattoirs(['show_inactifs' => true]) as $abattoir) { ?>
                                        <option value="<?php echo $abattoir->getId();?>" <?php
                                        echo in_array($abattoir->getId(), $filtre_abattoirs_array) ? 'selected' : '';
                                        echo $abattoir->getActif() == 0 ? ' class="option-desactive" data-subtext="Désactivé"' : '';
                                        ?>><?php echo $abattoir->getNom();?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <select class="selectpicker show-tick form-control" title="Fournisseurs" name="filtre_fournisseurs[]" multiple data-selected-text-format="count > 1" data-actions-box="true">
									<?php foreach ($tiersManager->getListeFournisseurs() as $frs) { ?>
                                        <option value="<?php echo $frs->getId();?>" <?php
										echo in_array($frs->getId(), $filtre_frs_array) ? 'selected' : '';
										?>><?php echo $frs->getNom();?></option>
									<?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <select class="selectpicker show-tick form-control" title="Origines" name="filtre_pays[]" multiple data-selected-text-format="count > 1" data-actions-box="true">
                                    <?php foreach ($paysManager->getListePays(true) as $pays) { ?>
                                        <option value="<?php echo $pays->getId();?>" <?php
                                        echo in_array($pays->getId(), $filtre_pays_array) ? 'selected' : '';
                                        ?>><?php echo $pays->getNom();?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                               <select class="selectpicker show-tick form-control" title="Produits" name="filtre_produits[]" multiple data-selected-text-format="count > 1" data-actions-box="true" data-live-search="true">
									<?php foreach ($produitsManager->getListeProduits(['show_inactifs' => true]) as $produit) { ?>
                                        <option value="<?php echo $produit->getId();?>" <?php
										echo in_array($produit->getId(), $filtre_produits_array) ? 'selected' : '';
										echo $produit->getActif() == 0 ? ' class="option-desactive" data-subtext="Désactivé"' : '';
										?>><?php echo $produit->getNom();?></option>
									<?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                    <div class="col-6 col-lg">
                            <div class="input-group mb-3">
                                <select class="selectpicker show-tick form-control" title="clients" name="filtre_clients[]" multiple data-selected-text-format="count > 1" data-actions-box="true">
                                    <?php  foreach ($tiersManager->getListeClients(['show_inactifs' => true]) as $abattoir) { ?>
                                        <option value="<?php echo $abattoir->getId();?>" <?php
                                        echo in_array($abattoir->getId(), $filtre_abattoirs_array) ? 'selected' : '';
                                        echo $abattoir->getActif() == 0 ? ' class="option-desactive" data-subtext="Désactivé"' : '';
                                        ?>><?php echo $abattoir->getNom();?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-2 text-right">
                    <div class="row">
                        <div class="col mb-3">
                            <input type="submit" class="btn btn-info form-control" value="Rechercher">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <a href="<?php echo $_SERVER['PHP_SELF'];?>" class="btn btn-secondary form-control"><i class="fa fa-backspace mr-1 fa-fw"></i> Réinitialiser</a>
                        </div>
                        <div class="col pl-0 telecharger-1800">
                            <button type="button" class="btn btn-success form-control btnTelecharger"><i class="fa fa-file-download mr-1 fa-fw"></i>Télécharger</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>   
    </div>
    <div class="row">
    <div class="col-12 col-xl-10" id="listeResultats" data-statut=""><i class="fa fa-spin fa-spinner fa-2x"></i></div>
    <?php

include('includes/modales/modal_lot_info.php'); 
include('includes/modales/modal_lot_docs.php');
include('includes/footer.php');