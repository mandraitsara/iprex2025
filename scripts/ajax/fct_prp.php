<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax PRP
------------------------------------------------------*/
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$prpManager = new PrpOpsManager($cnx);
$logsManager = new LogManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------
MODE - Liste PRP (admin)
------------------------------------*/
function modeShowListePrp() {

    global
	    $cnx,
	    $utilisateur,
		$prpManager,
        $mode;

    // On vérifie qu'on est bien loggé
    if (!isset($_SESSION['logged_user'])) { exit;}

    $date_du = isset($_REQUEST['date_du']) && $_REQUEST['date_du'] != '' ? Outils::dateFrToSql($_REQUEST['date_du']) :  '';
    $date_au = isset($_REQUEST['date_au']) && $_REQUEST['date_au'] != '' ? Outils::dateFrToSql($_REQUEST['date_au']) :  '';
    if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
    if (!Outils::verifDateSql($date_au)) { $date_au = ''; }

    $id_trans = isset($_REQUEST['id_trans']) ? intval($_REQUEST['id_trans']) : 0;
    $id_client = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
    $validation = isset($_REQUEST['validation']) ? intval($_REQUEST['validation']) : 0;
    $num_bl = isset($_REQUEST['num_bl']) ? trim($_REQUEST['num_bl']) : '';


	// Préparation pagination (Ajax)
	$nbResultPpage      = 20;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode='.$mode;
	$start              = ($page-1) * $nbResultPpage;

	$params = [
	        'date_du' => $date_du,
	        'date_au' => $date_au,
	        'id_trans' => $id_trans,
	        'id_client' => $id_client,
	        'validation' => $validation,
	        'num_bl' => $num_bl,
            'start' => $start,
            'nb_result_page' => $nbResultPpage
    ];

	$filtresPagination.= '&date_du='.$date_du;
	$filtresPagination.= '&date_au='.$date_au;
	$filtresPagination.= '&id_trans='.$id_trans;
	$filtresPagination.= '&id_client='.$id_client;
	$filtresPagination.= '&validation='.$validation;
	$filtresPagination.= '&num_bl='.$num_bl;

    $listePrp = $prpManager->getListePrpOps($params);

    if (empty($listePrp)) { ?>
        <div class="alert alert-warning padding-50 text-center">Aucun contrôle PRP trouvé...</div>
    <?php exit; }

	// Liste non vide, construction de la pagination...
	$nbResults  = $prpManager->getNb_results();
	$pagination = new Pagination($page);

	$pagination->setUrl($filtresPagination);
	$pagination->setNb_results($nbResults);
	$pagination->setAjax_function(true);
	$pagination->setNb_results_page($nbResultPpage);

	?>
    <table class="table admin table-v-middle">
        <thead>
        <tr>
            <th <?php echo $utilisateur->isDev() ? '' : 'd-none'; ?>>ID</th>
            <th>Date</th>
            <th>Transporteur</th>
            <th>Client</th>
            <th>BL</th>
            <th>Opérateur</th>
            <th>Palettes</th>
            <th>Crochets</th>
            <th>Conformité</th>
            <th>Validateur</th>
            <th class="t-actions">Détails</th>
            <th class="t-actions">Document</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($listePrp as $prp) {


            ?>
            <tr>
                <td <?php echo $utilisateur->isDev() ? '' : 'd-none'; ?>><code><?php echo $prp->getId(); ?></code></td>
                <td><?php echo Outils::dateSqlToFr($prp->getDate()); ?></td>
                <td><?php echo $prp->getNom_transporteur(); ?></td>
                <td><?php echo $prp->getNoms_clients(); ?></td>
                <td><?php echo $prp->getNums_bls(); ?></td>
                <td><?php echo $prp->getNom_user(); ?></td>
                <td class="nowrap">
                    <span class="badge badge-secondary mr-1 text-16 badge-pill texte-fin">
                    <?php if ($prp->getPalettes_recues() == 0 && $prp->getPalettes_rendues() == 0) { echo "&mdash;";
					} else { $ecart = $prp->getPalettes_recues() - $prp->getPalettes_rendues();	echo $ecart > 0 ? '+' : '';	echo $ecart;
					} ?></span>
                    <?php if ($prp->getPalettes_recues() > 0 || $prp->getPalettes_rendues() > 0) { ?>
                        <i class="fa fa-download fa-fw gris-9"></i><?php echo $prp->getPalettes_recues(); ?>
                        <i class="fa fa-upload fa-fw gris-9 ml-2"></i><?php echo $prp->getPalettes_rendues();
                    }
                    if ($prp->getPalettes_poids() > 0) { ?>
                        <i class="fa fa-weight fa-fw gris-9 ml-2"></i>
                    <?php echo number_format($prp->getPalettes_poids(),3,'.', '') . ' Kg';
                    } ?>
                </td>
                <td>
                    <span class="badge badge-secondary mr-1 text-16 badge-pill texte-fin">
                    <?php if ($prp->getCrochets_recus() == 0 && $prp->getCrochets_rendus() == 0) { echo "&mdash;";
					} else { $ecart = $prp->getCrochets_recus() - $prp->getCrochets_rendus();	echo $ecart > 0 ? '+' : '';	echo $ecart;
					} ?></span>
					<?php if ($prp->getCrochets_recus() > 0 || $prp->getCrochets_rendus() > 0) { ?>
                        <i class="fa fa-download fa-fw gris-9"></i><?php echo $prp->getCrochets_recus(); ?>
                        <i class="fa fa-upload fa-fw gris-9 ml-2"></i><?php echo $prp->getCrochets_rendus();
					} ?>
                </td>
                <td>
                    <i class="fa fa-square mr-1 text-<?php echo $prp->getCmd_conforme() == 1 ? 'success' : 'danger'; ?>"></i>
                    <i class="fa fa-square mr-1 text-<?php echo $prp->getT_surface_conforme() == 1 ? 'success' : 'danger'; ?>"></i>
                    <i class="fa fa-square mr-1 text-<?php echo $prp->getT_camion_conforme() == 1 ? 'success' : 'danger'; ?>"></i>
                    <i class="fa fa-square mr-1 text-<?php echo $prp->getEmballage_conforme() == 1 ? 'success' : 'danger'; ?>"></i>
                </td>
                <td id="blocValide<?php echo $prp->getId();?>"><?php
                    if ($prp->getId_validateur() > 0) {
                        ?><i class="fa fa-check text-success mr-1"></i><?php echo $prp->getNom_validateur();
					} else { ?>
                        <button type="button" class="btn btn-success btn-sm btnValiderPrp form-control text-center"><i class="fa fa-check mr-1"></i> Valider</button>
					<?php }
                    ?></td>
                <td class="t-actions text-center"><button type="button" class="btn btn-sm btn-secondary btnDetailsPrp"><i class="fa fa-ellipsis-h"></i></button></td>
                <td class="t-actions text-center"><button type="button" class="btn btn-sm btn-dark btnPdfPrp"><i class="fa fa-file-pdf"></i></button></td>
            </tr>
		<?php }
        ?>
        </tbody>
    </table>
  <?php

	// Pagination (aJax)
	if (isset($pagination)) {
		// Pagination bas de page, verbose...
		$pagination->setVerbose_pagination(1);
		$pagination->setVerbose_position('right');
		$pagination->setNature_resultats('suivi');
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);

		echo ($pagination->getPaginationHtml());
	} // FIN test pagination

	exit;
} // FIN mode


// Modale détail
function modeShowModaleDetailsPrp() {

    global $prpManager, $cnx;
    $id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
    if ($id_prp == 0) { exit("Identification du PRP échouée !"); }

    $prp = $prpManager->getPrpOp($id_prp);
    if (!$prp instanceof PrpOp) { exit("Instanciation du PRP échouée !"); }
	$tiersManager = new TiersManager($cnx);
	$blManager = new BlManager($cnx);
    ?>
    <input type="hidden" name="mode" value="updPrp"/>
    <input type="hidden" name="id_prp" value="<?php echo $id_prp; ?>"/>

    <div class="row">
        <div class="col-4">
            <div class="alert alert-secondary text-14">
                    <div class="input-group mb-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Date</span>
                        </div>
                        <input type="text" class="form-control datepicker" placeholder="Date" name="date" value="<?php echo Outils::dateSqlToFr($prp->getDate()); ?>">
                    </div>
                    <div class="input-group mb-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Transporteur<span>
                        </div>
                        <select class="selectpicker form-control show-tick" name="id_trans" title="Transporteur">
							<?php
							foreach ($tiersManager->getListeTransporteurs() as $transp) { ?>
                                <option value="<?php echo $transp->getId(); ?>" <?php echo $transp->getId() == $prp->getId_transporteur() ? 'selected' : ''; ?>><?php echo $transp->getNom(); ?></option>
							<?php }
							?>
                        </select>
                    </div>
                    <!--<div class="input-group mb-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Client</span>
                        </div>
                        <select class="selectpicker form-control show-tick" name="id_client" title="Client" data-live-search="true" data-size="12" data-live-search-placeholder="Rechercher...">
							<?php
/*							foreach ($tiersManager->getListeClients() as $clt) { */?>
                                <option value="<?php /*echo $clt->getId(); */?>" <?php /*echo $clt->getId() == $prp->getId_client() ? 'selected' : ''; */?>><?php /*echo $clt->getNom(); */?></option>
							<?php /*}
							*/?>
                        </select>
                    </div>-->
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">BL</span>
                        </div>
                        <select class="selectpicker form-control show-tick" multiple name="ids_bls[]" id="modalPrpSelectClt" title="Sélectionnez" data-selected-text-format="count > 2">
							<?php



							foreach ($blManager->getListeBl(['ids_clients' => $prp->getIds_clients()]) as $bl) { ?>
                                <option value="<?php echo $bl->getId(); ?>" <?php echo in_array($bl->getId(), $prp->getIds_bls()) ? 'selected' : ''; ?> data-subtext="<?php echo $bl->getNom_client();?>"><?php echo $bl->getNum_bl(); ?></option>
							<?php }
							?>
                        </select>
                    </div>
            </div>

            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Validation administrateur</span>
                </div>
                <input type="checkbox"
                       name="validation"
                       class="togglemaster"
					<?php echo $prp->getId_validateur() > 0 ? 'checked' : ''; ?>
                       data-toggle="toggle"
                       data-on="Oui"
                       data-off="Non"
                       data-onstyle="success"
                       data-offstyle="secondary"
                />
            </div>

        </div>
        <div class="col-4">
          <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Conformité commande</span>
                </div>
              <input type="checkbox"
                     name="cmd_conforme"
                     class="togglemaster"
                     <?php echo $prp->getCmd_conforme() == 1 ? 'checked' : ''; ?>
                     data-toggle="toggle"
                     data-on="Oui"
                     data-off="Non"
                     data-onstyle="success"
                     data-offstyle="danger"
              />
          </div>
        <div class="input-group mt-1">
            <div class="input-group-prepend">
                <span class="input-group-text mw-220 text-14">T° en surface entre 2 colis</span>
            </div>
           <input type="number" class="form-control text-center" name="t_surface" placeholder="0.00" value="<?php echo number_format($prp->getT_surface(),2,'.',''); ?>" step="0.01"/>
            <div class="input-group-append">
                <span class="input-group-text">°C</span>
            </div>
        </div>
            <div class="input-group mt-1">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Conformité T° en surface</span>
                </div>
                <input type="checkbox"
                       name="t_surface_conforme"
                       class="togglemaster"
					<?php echo $prp->getT_surface_conforme() == 1 ? 'checked' : ''; ?>
                       data-toggle="toggle"
                       data-on="Oui"
                       data-off="Non"
                       data-onstyle="success"
                       data-offstyle="danger"
                />
            </div>

            <div class="input-group mt-1">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">T° caisse camion av/chargement</span>
                </div>
                <input type="number" class="form-control text-center" name="t_camion" placeholder="0.00" value="<?php echo number_format($prp->getT_camion(),2,'.',''); ?>" step="0.01"/>
                <div class="input-group-append">
                    <span class="input-group-text">°C</span>
                </div>
            </div>
            <div class="input-group mt-1">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Conformité T° caisse camion</span>
                </div>
                <input type="checkbox"
                       name="t_camion_conforme"
                       class="togglemaster"
					<?php echo $prp->getT_camion_conforme() == 1 ? 'checked' : ''; ?>
                       data-toggle="toggle"
                       data-on="Oui"
                       data-off="Non"
                       data-onstyle="success"
                       data-offstyle="danger"
                />
            </div>
            <div class="input-group mt-1">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Conformité emballage palette</span>
                </div>
                <input type="checkbox"
                       name="emballage_conforme"
                       class="togglemaster"
					<?php echo $prp->getEmballage_conforme() == 1 ? 'checked' : ''; ?>
                       data-toggle="toggle"
                       data-on="Oui"
                       data-off="Non"
                       data-onstyle="success"
                       data-offstyle="danger"
                />
            </div>




        </div>
        <div class="col-4">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Poids des palettes</span>
                </div>
                <input type="number" class="form-control text-center" name="poids" placeholder="0.000" value="<?php echo number_format($prp->getPalettes_poids(),3,'.',''); ?>" step="0.001" min="0"/>
                <div class="input-group-append">
                    <span class="input-group-text">Kg</span>
                </div>
            </div>
            <div class="input-group mt-1">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Palettes reçues</span>
                </div>
                <input type="number" class="form-control text-center" name="palettes_recues" placeholder="0" value="<?php echo $prp->getPalettes_recues(); ?>" step="1" min="0"/>
            </div>
            <div class="input-group mt-1">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Palettes rendues</span>
                </div>
                <input type="number" class="form-control text-center" name="palettes_rendues" placeholder="0" value="<?php echo $prp->getPalettes_rendues(); ?>" step="1" min="0"/>
            </div>
            <div class="input-group mt-1">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Crochets reçus</span>
                </div>
                <input type="number" class="form-control text-center" name="crochets_recus" placeholder="0" value="<?php echo $prp->getCrochets_recus(); ?>" step="1" min="0"/>
            </div>
            <div class="input-group mt-1">
                <div class="input-group-prepend">
                    <span class="input-group-text mw-220 text-14">Crochets rendus</span>
                </div>
                <input type="number" class="form-control text-center" name="crochets_rendus" placeholder="0" value="<?php echo $prp->getCrochets_rendus(); ?>" step="1" min="0"/>
            </div>

            <?php
            if (file_exists(__CBO_UPLOADS_PATH__.'signatures/exp/'.$id_prp.'.png')) { ?>
                <span class="gris-5 text-13">Signature du transporteur :</span>
                <img src="<?php echo __CBO_UPLOADS_URL__.'/signatures/exp/'.$id_prp.'.png'; ?>" class="img-signature"/>
			<?php }

            ?>


        </div>
    </div>
    <?php
    exit;
} // FIN modale

// Maj de la liste des BL lors du changement de client (modale)
function modeSelectBlsByClient() {

    global $cnx;
    $id_clt = isset($_REQUEST['id_clt']) ? intval($_REQUEST['id_clt']) : 0;
    if ($id_clt == 0) { exit('-1'); }
    $blManager = new BlManager($cnx);

    $bls = $blManager->getListeBl(['id_client' =>$id_clt]);
    if (empty($bls)) { ?>
        <option value="0">Aucun BL pour ce client !</option>
        <?php exit;
    }

	foreach ($bls as $bl) { ?>
        <option value="<?php echo $bl->getId(); ?>"><?php echo $bl->getNum_bl(); ?></option>
	<?php }
	exit;

} // FIN mode

// Valide un PRP
function modeValidePrp() {
    global $prpManager, $cnx, $utilisateur;

    $logManager = new LogManager($cnx);

    $id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
    if ($id_prp == 0) { exit('ERREUR ID0'); }

    $prp = $prpManager->getPrpOp($id_prp);
    if (!$prp instanceof PrpOp) { exit('ERREUR OBJ_PRP'); }

    $prp->setId_validateur($utilisateur->getId());
    $prp->setDate_visa(date('Y-m-d H-i-s'));

    if (!$prpManager->savePrpOp($prp)) { exit('ERREUR SAVEPRP'); }

    $log = new Log([]);
    $log->setLog_texte("Validation du PRP #".$id_prp);
    $log->setLog_type('success');
    $logManager->saveLog($log);
    ?>
    <i class="fa fa-check text-success mr-1"></i>
    <?php
    echo $utilisateur->getNomComplet();
    exit;

} // FIN mode

// Modif PRP amdin
function modeUpdPrp() {

	global $prpManager, $cnx, $utilisateur;

	$logManager = new LogManager($cnx);

	$id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
	if ($id_prp == 0) { exit('ERREUR ID0'); }

	$prp = $prpManager->getPrpOp($id_prp);
	if (!$prp instanceof PrpOp) { exit('ERREUR OBJ_PRP'); }

	$date = isset($_REQUEST['date']) ? Outils::dateFrToSql($_REQUEST['date']) : '';


	$id_trans = isset($_REQUEST['id_trans']) ? intval($_REQUEST['id_trans']) : 0;
	$ids_bls = isset($_REQUEST['ids_bls']) && is_array($_REQUEST['ids_bls']) ? $_REQUEST['ids_bls'] : [];

	$validation = isset($_REQUEST['validation']);

	$cmd_conforme = isset($_REQUEST['cmd_conforme']) ? 1 : 0;
	$t_surface_conforme = isset($_REQUEST['t_surface_conforme']) ? 1 : 0;
	$t_camion_conforme = isset($_REQUEST['t_camion_conforme']) ? 1 : 0;
	$emballage_conforme = isset($_REQUEST['emballage_conforme']) ? 1 : 0;
	$t_surface = isset($_REQUEST['t_surface']) ? floatval($_REQUEST['t_surface']) : false;
	$t_camion = isset($_REQUEST['t_camion']) ? floatval($_REQUEST['t_camion']) : false;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : false;
	$palettes_recues = isset($_REQUEST['palettes_recues']) ? intval($_REQUEST['palettes_recues']) : false;
	$palettes_rendues = isset($_REQUEST['palettes_rendues']) ? intval($_REQUEST['palettes_rendues']) : false;
	$crochets_recus = isset($_REQUEST['crochets_recus']) ? intval($_REQUEST['crochets_recus']) : false;
	$crochets_rendus = isset($_REQUEST['crochets_rendus']) ? intval($_REQUEST['crochets_rendus']) : false;

	// On identifie l'écart de palettes/crochets pour impact ou non sur le transporteur
    $ecart_palettes_recues = $prp->getPalettes_recues() - $palettes_recues;
    $ecart_palettes_rendues = $prp->getPalettes_rendues() - $palettes_rendues;
    $ecart_solde_palettes = $ecart_palettes_recues - $ecart_palettes_rendues;
    
	$ecart_crochets_recus = $prp->getCrochets_recus() - $crochets_recus;
	$ecart_crochets_rendus = $prp->getCrochets_rendus() - $crochets_rendus;
	$ecart_solde_crochets = $ecart_crochets_recus - $ecart_crochets_rendus;

    if (Outils::verifDateSql($date)) { $prp->setDate($date); }
    if ($id_trans > 0) { $prp->setId_transporteur($id_trans);}
    if ($validation && $prp->getId_validateur() == 0) { $prp->setId_validateur($utilisateur->getId()); $prp->setDate_visa(date('Y-m-d H!:i:s'));}
    if (!$validation && $prp->getId_validateur() > 0) { $prp->setId_validateur(0); $prp->setDate_visa(""); }
    if ($t_surface !== false) { $prp->setT_surface($t_surface);}
    if ($t_camion !== false) { $prp->setT_camion($t_camion);}
    if ($poids !== false) { $prp->setPalettes_poids($poids);}
    if ($palettes_recues !== false) { $prp->setPalettes_recues($palettes_recues);}
    if ($palettes_rendues !== false) { $prp->setPalettes_rendues($palettes_rendues);}
    if ($crochets_recus !== false) { $prp->setCrochets_recus($crochets_recus);}
    if ($crochets_rendus !== false) { $prp->setCrochets_rendus($crochets_rendus);}
    $prp->setCmd_conforme($cmd_conforme);
    $prp->setT_surface_conforme($t_surface_conforme);
    $prp->setT_camion_conforme($t_camion_conforme);
    $prp->setEmballage_conforme($emballage_conforme);


    if ($prpManager->savePrpOp($prp)) {

        $prpManager->savePrpOpBls($prp, $ids_bls);

		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("Modification du PRP #".$id_prp);
		$logManager->saveLog($log);

		// Mise à jour du transporteur
        $tiersManager = new TiersManager($cnx);
        $transporteur = $tiersManager->getTiers($prp->getId_transporteur());
        if (!$transporteur instanceof Tiers) { exit; }
		$solde_palettes = $transporteur->getSolde_palettes();
		$solde_palettes+= $ecart_solde_palettes;

		$solde_crochets = $transporteur->getSolde_crochets();
		$solde_crochets+= $ecart_solde_crochets;

		$transporteur->setSolde_palettes($solde_palettes);
		$transporteur->setSolde_crochets($solde_crochets);
		$tiersManager->saveTiers($transporteur);

    } // FIN save ok


    exit;

} // FIN mode

// Supprime un PRP
function modeSupprPrp() {

	global $prpManager, $cnx, $utilisateur;

	$logManager = new LogManager($cnx);

	$id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
	if ($id_prp == 0) { exit('ERREUR ID0'); }

	$prp = $prpManager->getPrpOp($id_prp);
	if (!$prp instanceof PrpOp) { exit('ERREUR OBJ_PRP'); }

	if ($prpManager->supprPrpOp($prp)) {
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte("Supprression totale du PRP #".$id_prp .'(Bl#'.$prp->setId_bl().')');
		$logManager->saveLog($log);
		
		// On met à jour la quantité de crochets/palettes du transporteur
        $tiersManager = new TiersManager($cnx);
        $transporteur = $tiersManager->getTiers($prp->getId_transporteur());
        if (!$transporteur instanceof Tiers) { exit; }
        $solde_palettes = $transporteur->getSolde_palettes();
		$solde_palettes+=$prp->getPalettes_recues();
		$solde_palettes-=$prp->getPalettes_rendues();
		$solde_crochets = $transporteur->getSolde_crochets();
		$solde_crochets+=$prp->getCrochets_recus();
		$solde_crochets-=$prp->getCrochets_rendus();
		$transporteur->setSolde_crochets($solde_crochets);
		$transporteur->setSolde_palettes($solde_palettes);
		$tiersManager->saveTiers($transporteur);
    }
	exit;

} // FIN mode

// Génère le PDF d'un PRP OP
function modeGenerePdf() {

    global $prpManager;

	$id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
	if ($id_prp == 0) { exit('ERREUR ID0'); }

	$prp = $prpManager->getPrpOp($id_prp);
	if (!$prp instanceof PrpOp) { exit('ERREUR OBJ_PRP'); }

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = genereContenuPdf($prp);
	$content .= ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/prpop-*.pdf') as $fichier) {
		unlink($fichier);
	}


	try {
		$nom_fichier = 'prpop-'.sprintf("%04d", $id_prp).'-'.date('is').'.pdf';
		$html2pdf = new HTML2PDF('L', 'A4', 'fr', false, 'ISO-8859-15');
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($content));;
		$savefilepath = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
		echo __CBO_TEMP_URL__.$nom_fichier;
	}
	catch(HTML2PDF_exception $e) {
		exit;
	}

	exit;

} // FIN mode

// Contenu PDF PRP (1 PRP)
/*function genereContenuPdfUnique($prp) {

    global $prpManager;

	// HEAD
	$contenu = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style type="text/css"> * { margin:0; padding: 0; } .header { border-bottom: 2px solid #ccc; } .header img.logo { width: 200px; } .text-right { text-align: right; } .text-center { text-align: center; } .table { border-collapse: collapse; } .table-donnees th { font-size: 11px; } .table-liste th { font-size: 9px; background-color: #d5d5d5; padding:3px; } .table-liste td { font-size: 9px; padding-top: 3px; padding-bottom: 3px; border-bottom: 1px solid #ccc;} .table-liste td.no-bb { border-bottom: none; padding-bottom: 0px; } .table-liste tr.soustotal td { background-color: #d5d5d5; } .titre { background-color: teal; color: #fff; padding: 3px; text-align: center; font-weight: normal; font-size: 14px; } .w100 { width: 100%; } .w75 { width: 75%; } .w65 { width: 65%; } .w45 { width: 45%; } .w55 { width: 55%; } .w70 { width: 70%; } .w80 { width: 80%; } .w50 { width: 50%; } .w40 { width: 40%; } .w25 { width: 25%; } .w33 { width: 33%; } .w34 { width: 34%; } .w20 { width: 20%; } .w30 { width: 30%; } .w15 { width: 15%; } .w35 { width: 35%; }  .w30 { width: 30%; } .w5 { width: 5%; } .w10 { width: 10%; } .w15 { width: 15%; } .text-6 { font-size: 6px; } .text-7 { font-size: 7px; } .text-8 { font-size: 8px; } .text-9 { font-size: 9px; } .text-10 { font-size: 10px; } .text-11 { font-size: 11px; } .text-12 { font-size: 12px; } .text-14 { font-size: 14px; } .text-16 { font-size: 16px; } .text-18 { font-size: 18px; } .text-20 { font-size: 20px; } .gris-3 { color:#333; } .gris-5 { color:#555; } .gris-7 { color:#777; } .gris-9 { color:#999; } .gris-c { color:#ccc; } .gris-d { color:#d5d5d5; } .gris-e { color:#e5e5e5; } .mt-0 { margin-top: 0px; } .mt-2 { margin-top: 2px; } .mt-5 { margin-top: 5px; } .mt-10 { margin-top: 10px; } .mt-15 { margin-top: 15px; } .mt-20 { margin-top: 20px; } .mt-25 { margin-top: 25px; } .mt-50 { margin-top: 50px; } .mb-0 { margin-bottom: 0px; } .mb-2 { margin-bottom: 2px; } .mb-5 { margin-bottom: 5px; } .mb-10 { margin-bottom: 10px; } .mb-15 { margin-bottom: 15px; } .mb-20 { margin-bottom: 20px; } .mb-25 { margin-bottom: 25px; } .mb-50 { margin-bottom: 50px; } .mr-0 { margin-right: 0px; } .mr-2 { margin-right: 2px; } .mr-5 { margin-right: 5px; } .mr-10 { margin-right: 10px; } .mr-15 { margin-right: 15px; } .mr-20 { margin-right: 20px; } .mr-25 { margin-right: 25px; } .mr-50 { margin-right: 50px; } .ml-0 { margin-left: 0px; } .ml-2 { margin-left: 2px; } .ml-5 { margin-left: 5px; } .ml-10 { margin-left: 10px; } .ml-15 { margin-left: 15px; } .ml-20 { margin-left: 20px; } .ml-25 { margin-left: 25px; } .ml-50 { margin-left: 50px; } .pt-0 { padding-top: 0px; } .pt-2 { padding-top: 2px; } .pt-5 { padding-top: 5px; } .pt-10 { padding-top: 10px; } .pt-15 { padding-top: 15px; } .pt-20 { padding-top: 20px; } .pt-25 { padding-top: 25px; } .pt-50 { padding-top: 50px; } .pb-0 { padding-bottom: 0px; } .pb-2 { padding-bottom: 2px; } .pb-5 { padding-bottom: 5px; } .pb-10 { padding-bottom: 10px; } .pb-15 { padding-bottom: 15px; } .pb-20 { padding-bottom: 20px; } .pb-25 { padding-bottom: 25px; } .pb-50 { padding-bottom: 50px; } .pr-0 { padding-right: 0px; } .pr-2 { padding-right: 2px; } .pr-5 { padding-right: 5px; } .pr-10 { padding-right: 10px; } .pr-15 { padding-right: 15px; } .pr-20 { padding-right: 20px; } .pr-25 { padding-right: 25px; } .pr-50 { padding-right: 50px; } .pl-0 { padding-left: 0px; } .pl-2 { padding-left: 2px; } .pl-5 { padding-left: 5px; } .pl-10 { padding-left: 10px; } .pl-15 { padding-left: 15px; } .pl-20 { padding-left: 20px; } .pl-25 { padding-left: 25px; } .pl-50 { padding-left: 50px; } .text-danger { color: #d9534f; } .vtop { vertical-align: top; } .br-1 { border-right: 1px solid #999; } </style> </head><body>';


	$validateur = $prp->getNom_validateur() != '' ? $prp->getNom_validateur() : '-';
	$validation = $prp->getDate_visa() != '' ? Outils::dateSqlToFr($prp->getDate_visa()) : '';
	$conf_cmd = $prp->getCmd_conforme() == 1 ? 'Oui' : 'Non';
	$conf_ts = $prp->getT_surface_conforme() == 1 ? 'Oui' : 'Non';
	$conf_tc = $prp->getT_camion_conforme() == 1 ? 'Oui' : 'Non';
	$conf_ep = $prp->getEmballage_conforme() == 1 ? 'Oui' : 'Non';

	$contenu.= '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Feuille de surveillance PRP OP</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>
               <table class="table w100 mt-10">
                   <tr>
                        <td class="w30 vtop">
                            <table class="w100 br-1">
                                <tr>
                                    <td class="w35 text-10 pt-5">Date</td>
                                    <td class="w65 text-13 pt-5 pr-5">'.Outils::dateSqlToFr($prp->getDate()).'</td>
                                </tr>
                                <tr>
                                    <td class="w35 text-10 pt-5">Opérateur</td>
                                    <td class="w65 text-11 pt-5 pr-5">'.$prp->getNom_user().'</td>
                                </tr>
                                <tr>
                                    <td class="w35 text-10 pt-5">Transporteur</td>
                                    <td class="w65 text-11 pt-5 pr-5">'.$prp->getNom_transporteur().'</td>
                                </tr>
                                <tr>
                                    <td class="w35 text-10 pt-5">Client</td>
                                    <td class="w65 text-11 pt-5 pr-5">'.$prp->getNom_client().'</td>
                                </tr>
                                <tr>
                                    <td class="w35 text-10 pt-5">BL</td>
                                    <td class="w65 text-11 pt-5 pr-5">'.$prp->getNum_Bl().'</td>
                                </tr>
                                <tr>
                                    <td class="w35 text-10 pt-5">Visa</td>
                                    <td class="w65 text-11 pt-5 pr-5">'.$validateur.'<br>Le '.$validation.'</td>
                                </tr>
                            </table>
                        </td>
                        <td class="w70 vtop pl-20">
                             <table class="w100">
                                <tr>
                                    <td class="w45 pt-5">Conformité commande</td>
                                    <td class="w55 pt-5 pr-5"><b>'.$conf_cmd.'</b></td>
                                </tr>
                                <tr>
                                    <td class="w45 pt-5">T° en surface entre 2 colis</td>
                                    <td class="w55 pt-5 pr-5"><b>'.number_format($prp->getT_surface(),2,'.', '').' °C</b></td>
                                </tr>
                                 <tr>
                                    <td class="w45 pt-5">Conformité T° en surface</td>
                                    <td class="w55 pt-5 pr-5"><b>'.$conf_ts.'</b></td>
                                </tr>
                                 <tr>
                                    <td class="w45 pt-5">T° caisse camion av/chargement</td>
                                    <td class="w55 pt-5 pr-5"><b>'.number_format($prp->getT_camion(),2,'.', '').' °C</b></td>
                                </tr>
                                 <tr>
                                    <td class="w45 pt-5">Conformité T° caisse camion</td>
                                    <td class="w55 pt-5 pr-5"><b>'.$conf_tc.'</b></td>
                                </tr>
                                 <tr>
                                    <td class="w45 pt-5">Conformité emballage palette</td>
                                    <td class="w55 pt-5 pr-5"><b>'.$conf_ep.'</b></td>
                                </tr>
                                
                                 <tr>
                                    <td class="w45 pt-5">Poids des palettes</td>
                                    <td class="w55 pt-5 pr-5"><b>'.number_format($prp->getPalettes_poids(),3,'.', '').' Kg</b></td>
                                </tr>
                                <tr>
                                    <td class="w45 pt-5">Palettes reçues</td>
                                    <td class="w55 pt-5 pr-5"><b>'.(int)$prp->getPalettes_recues().'</b></td>
                                </tr>
                                <tr>
                                    <td class="w45 pt-5">Palettes rendues</td>
                                    <td class="w55 pt-5 pr-5"><b>'.(int)$prp->getPalettes_rendues().'</b></td>
                                </tr>
                                <tr>
                                    <td class="w45 pt-5">Crochets reçus</td>
                                    <td class="w55 pt-5 pr-5"><b>'.(int)$prp->getCrochets_recus().'</b></td>
                                </tr>
                                <tr>
                                    <td class="w45 pt-5">Crochets rendus</td>
                                    <td class="w55 pt-5 pr-5"><b>'.(int)$prp->getCrochets_rendus().'</b></td>
                                </tr>
                              </table>
                        </td>

                   </tr>
               </table>';




	$contenu.= '</body></html>';
	return $contenu;

} // FIN genere PDF
*/

// Contenu PDF PRP (Semaine)
function genereContenuPdf($prp) {

	global $prpManager;

	// HEAD
	$contenu = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style type="text/css"> * { margin:0; padding: 0; } .header { border-bottom: 2px solid #ccc; } .header img.logo { width: 200px; } .text-right { text-align: right; } .text-center { text-align: center; } .table { border-collapse: collapse; } .table-donnees th { font-size: 11px; } .table-liste th { font-size: 9px; background-color: #d5d5d5; padding:3px; } .table-liste td { font-size: 9px; padding-top: 3px; padding-bottom: 3px; border-bottom: 1px solid #ccc;} .table-liste td.no-bb { border-bottom: none; padding-bottom: 0px; } .table-liste tr.soustotal td { background-color: #d5d5d5; } .titre { background-color: teal; color: #fff; padding: 3px; text-align: center; font-weight: normal; font-size: 14px; } .w100 { width: 100%; } .w75 { width: 75%; } .w65 { width: 65%; } .w45 { width: 45%; } .w55 { width: 55%; } .w70 { width: 70%; } .w80 { width: 80%; } .w50 { width: 50%; } .w40 { width: 40%; } .w25 { width: 25%; } .w33 { width: 33%; } .w34 { width: 34%; } .w20 { width: 20%; } .w30 { width: 30%; } .w15 { width: 15%; } .w35 { width: 35%; }  .w30 { width: 30%; } .w5 { width: 5%; } .w10 { width: 10%; } .w15 { width: 15%; } .text-6 { font-size: 6px; } .text-7 { font-size: 7px; } .text-8 { font-size: 8px; } .text-9 { font-size: 9px; } .text-10 { font-size: 10px; } .text-11 { font-size: 11px; } .text-12 { font-size: 12px; } .text-14 { font-size: 14px; } .text-16 { font-size: 16px; } .text-18 { font-size: 18px; } .text-20 { font-size: 20px; } .gris-3 { color:#333; } .gris-5 { color:#555; } .gris-7 { color:#777; } .gris-9 { color:#999; } .gris-c { color:#ccc; } .gris-d { color:#d5d5d5; } .gris-e { color:#e5e5e5; } .mt-0 { margin-top: 0px; } .mt-2 { margin-top: 2px; } .mt-5 { margin-top: 5px; } .mt-10 { margin-top: 10px; } .mt-15 { margin-top: 15px; } .mt-20 { margin-top: 20px; } .mt-25 { margin-top: 25px; } .mt-50 { margin-top: 50px; } .mb-0 { margin-bottom: 0px; } .mb-2 { margin-bottom: 2px; } .mb-5 { margin-bottom: 5px; } .mb-10 { margin-bottom: 10px; } .mb-15 { margin-bottom: 15px; } .mb-20 { margin-bottom: 20px; } .mb-25 { margin-bottom: 25px; } .mb-50 { margin-bottom: 50px; } .mr-0 { margin-right: 0px; } .mr-2 { margin-right: 2px; } .mr-5 { margin-right: 5px; } .mr-10 { margin-right: 10px; } .mr-15 { margin-right: 15px; } .mr-20 { margin-right: 20px; } .mr-25 { margin-right: 25px; } .mr-50 { margin-right: 50px; } .ml-0 { margin-left: 0px; } .ml-2 { margin-left: 2px; } .ml-5 { margin-left: 5px; } .ml-10 { margin-left: 10px; } .ml-15 { margin-left: 15px; } .ml-20 { margin-left: 20px; } .ml-25 { margin-left: 25px; } .ml-50 { margin-left: 50px; } .pt-0 { padding-top: 0px; } .pt-2 { padding-top: 2px; } .pt-5 { padding-top: 5px; } .pt-10 { padding-top: 10px; } .pt-15 { padding-top: 15px; } .pt-20 { padding-top: 20px; } .pt-25 { padding-top: 25px; } .pt-50 { padding-top: 50px; } .pb-0 { padding-bottom: 0px; } .pb-2 { padding-bottom: 2px; } .pb-5 { padding-bottom: 5px; } .pb-10 { padding-bottom: 10px; } .pb-15 { padding-bottom: 15px; } .pb-20 { padding-bottom: 20px; } .pb-25 { padding-bottom: 25px; } .pb-50 { padding-bottom: 50px; } .pr-0 { padding-right: 0px; } .pr-2 { padding-right: 2px; } .pr-5 { padding-right: 5px; } .pr-10 { padding-right: 10px; } .pr-15 { padding-right: 15px; } .pr-20 { padding-right: 20px; } .pr-25 { padding-right: 25px; } .pr-50 { padding-right: 50px; } .pl-0 { padding-left: 0px; } .pl-2 { padding-left: 2px; } .pl-5 { padding-left: 5px; } .pl-10 { padding-left: 10px; } .pl-15 { padding-left: 15px; } .pl-20 { padding-left: 20px; } .pl-25 { padding-left: 25px; } .pl-50 { padding-left: 50px; } .text-danger { color: #d9534f; } .vtop { vertical-align: top; } .br-1 { border-right: 1px solid #999; } .table-prp td {word-break:break-all;vertical-align: top; border:1px solid #777; padding:2px; font-size:10px;} tr.t-header td { text-align: center; background-color: #ddd; } .img-signature {max-width: 30px; } </style> </head><body>';


	// On récupère tous les PRP de la semaine...

	$sem_du = date('Y-m-d',strtotime('this week', strtotime($prp->getDate())));;
	$sem_au = date('Y-m-d',strtotime('next sunday', strtotime($prp->getDate())));;

    $liste = $prpManager->getListePrpOps(['date_du' => $sem_du, 'date_au' => $sem_au]);

	$contenu.= '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Feuille de surveillance PRP OP</span><br><span class="text-12">Semaine du '.Outils::dateSqlToFr($sem_du).' au '.Outils::dateSqlToFr($sem_au).'</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>
               <table class="table table-prp w100 mt-10">
                    <tr class="t-header">
                        <td rowspan="2" class="w5">Date</td>
                        <td rowspan="2" class="w10">Client<br>(Tranporteur)</td>
                        <td rowspan="2" class="w5">Conformité commande</td>
                        <td colspan="2" class="w10">Contrôle température produit</td>
                        <td colspan="2" class="w10">Contrôle température camion</td>
                        <td rowspan="2" class="w5">Palette filmée commande</td>
                        <td rowspan="2" class="w5">Poids palettes</td>
                        <td rowspan="2" class="w5">Palettes reçues</td>
                        <td rowspan="2" class="w5">Palettes rendues</td>
                        <td rowspan="2" class="w5">Crochets reçus</td>
                        <td rowspan="2" class="w5">Crochets rendus</td>
                        <td rowspan="2" class="w10">Responsable du contrôle</td>
                        <td rowspan="2" class="w10">Signature du transporteur</td>
                        <td rowspan="2" class="w10">Visa</td>
                    </tr>
                    <tr class="t-header">
                        <td class="w5">T° en surface entre 2 colis (emballe)</td>
                        <td>T° conforme</td>
                        <td class="w5">T° de la caisse du camion avant chargement</td>
                        <td>T° conforme</td>
                    </tr>';

	foreach ($liste as $p) {

		$validateur = $p->getNom_validateur() != '' ? $p->getNom_validateur() : '-';
		$validation = $p->getDate_visa() != '' ? Outils::dateSqlToFr($p->getDate_visa()) : '';
		$conf_cmd = $p->getCmd_conforme() == 1 ? 'Oui' : 'Non';
		$conf_ts = $p->getT_surface_conforme() == 1 ? 'Oui' : 'Non';
		$conf_tc = $p->getT_camion_conforme() == 1 ? 'Oui' : 'Non';
		$conf_ep = $p->getEmballage_conforme() == 1 ? 'Oui' : 'Non';
		$signature = '';
		if (file_exists(__CBO_UPLOADS_PATH__.'signatures/exp/'.$p->getId().'.png')) {
			$signature = '<img src="'. __CBO_UPLOADS_URL__.'/signatures/exp/'.$p->getId().'.png" style="width:100px;"/>';
		}


		$contenu.= '<tr>
                        <td class="w5">'.Outils::dateSqlToFr($p->getDate()).'</td>
                        <td class="w10">'.$p->getNoms_clients().'<br>('.$p->getNom_transporteur().')</td>
                        <td class="w5 text-center">'.$conf_cmd.'</td>
                        <td class="w5 text-center">'.number_format($p->getT_surface(),2,'.', '').'°C</td>
                        <td class="w5 text-center">'.$conf_ts.'</td>
                        <td class="w5 text-center">'.number_format($p->getT_camion(),2,'.', '').'°C</td>
                        <td class="w5 text-center">'.$conf_tc.'</td>
                        <td class="w5 text-center">'.$conf_ep.'</td>
                        <td class="w5 text-center">'.number_format($p->getPalettes_poids(),3, '.', '').' Kg</td>
                        <td class="w5 text-center">'.$p->getPalettes_recues().'</td>
                        <td class="w5 text-center">'.$p->getPalettes_rendues().'</td>
                        <td class="w5 text-center">'.$p->getCrochets_recus().'</td>
                        <td class="w5 text-center">'.$p->getCrochets_rendus().'</td>
                        <td class="w10 text-center">'.$p->getNom_user().'<br>'.Outils::dateSqlToFr($p->getDate_add()).'</td>
                        <td class="w10 text-center">'.$signature.'</td>
                        <td class="w10 text-center">'.$validateur.'<br>'.$validation.'</td>
                    </tr>';

    } // FIN boucle


	$contenu.= '</table>';




	$contenu.= '</body></html>';
	return $contenu;

} // FIN genere PDF