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
ini_set('display_errors',1); // Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$logsManager = new LogManager($cnx);
$lotsNegoceManager = new LotNegoceManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}



/* ------------------------------------
MODE - Enregistre un nouveau lot N
------------------------------------*/
function modeAddLotNegoce() {

	global
	$cnx,
	$lotsNegoceManager,
	$logsManager;
	
	$especesManager = new ProduitEspecesManager($cnx);

	$regexDateFr     =  '#^(([1-2]\d|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9]\d{2})$#';
	$num_bl     = isset($_REQUEST['num_bl'])  ?  trim(strtoupper($_REQUEST['num_bl'])) : '';
	$id_fournisseur  = isset($_REQUEST['id_fournisseur']) ? intval($_REQUEST['id_fournisseur'])  : 0;
	$date_reception  	 = isset($_REQUEST['date_reception']) && preg_match($regexDateFr, $_REQUEST['date_reception']) ? Outils::dateFrToSql($_REQUEST['date_reception']) : '';	
	
	$lotNegoce = new LotNegoce([]);
	$lotNegoce->setDate_add(date('Y-m-d H:i:s'));
	$lotNegoce->setVisible(1);
	$lotNegoce->setId_fournisseur($id_fournisseur);	
	$lotNegoce->setNum_bl($num_bl);    
	
	$numlot = $lotsNegoceManager->getLotNegoceByNumLot($num_bl);

	if($numlot == true) {		
		$numlots = substr($num_bl,0,0);
		$num_b = ($numlots ? intval($numlots) : 0) + 1;		
		$bl = $num_bl.intval($num_b);
		$lotNegoce->setNum_bl($bl);
	}

	if ($date_reception != '')    	{
		$lotNegoce->setDate_reception($date_reception);
		$lotNegoce->setDate_entree($date_reception);}
	
	$id_lot =  $lotsNegoceManager->saveLotNegoce($lotNegoce);

	//enregistrer la vue
	if (intval($id_lot) > 0) {
			$vuesManager = new VueManager($cnx);
            $vueReception = $vuesManager->getVueByCode('rcp_neg');
            $lotVue = new LotVue([]);
            $lotVue->setId_lot($id_lot);
            $lotVue->setId_vue($vueReception->getId());
            $lotVue->setDate_entree(date('Y-m-d H:i:s'));
            $lotVuesManager = new LotVueManager($cnx);
            $lotVuesManager->saveLotVue($lotVue);
	} else {
        erreur($numlot);
        exit;
    } // FIN test création lot

	// Si lot créé, on Log
	if (intval($id_lot) > 0) {
		$log = new Log([]);
		$log->setLog_type('success');
		$log->setLog_texte('Création du lot de négoce #' . $id_lot);
		$logsManager->saveLog($log);

		// Si erreur, on reviens...
	} else {
		exit('ERREUR');
	} // FIN test création lot

header('Location: ../../admin-lots-negoce.php');
	exit;

} // FIN mode


/* --------------------------------------
MODE - Retourne la liste des lots (aJax)
---------------------------------------*/
function modeShowListeLotsNegoce() {

	global
	$mode,
	$lotsNegoceManager,
	$utilisateur,
	$cnx;

	if (!isset($utilisateur) || !$utilisateur) { exit('Session expirée ! Reconnectez-vous pour continuer...'); }

	// Préparation pagination (Ajax)
	$nbResultPpage      = 20;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode='.$mode;
	$start              = ($page-1) * $nbResultPpage;

	$statut = isset($_REQUEST['statut']) ? intval($_REQUEST['statut']) : 1; // 1 = En cours | 0 = Terminé

	$params['statut'] = $statut;
	$params['get_nb_traites'] = true;


	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;
	$params['order'] 			= 'id';

	$listeLots = $lotsNegoceManager->getListeLotNegoces($params);
	
	// Si aucun lot
	if (empty($listeLots)) { ?>

		<div class="alert alert-secondary text-center">
			<i class="far fa-clock mb-2 mt-2 fa-5x"></i> <p class="text-24 mb-0">Aucun lot negoce&hellip;</p>
		</div>

		<?php

		// Des lots ont été trouvés...
	} else {
		// Liste non vide, construction de la pagination...
		$nbResults  = $lotsNegoceManager->getNb_results();
		$pagination = new Pagination($page);
		$pagination->setUrl($filtresPagination);
		$pagination->setNb_results($nbResults);
		$pagination->setAjax_function(true);
		$pagination->setNb_results_page($nbResultPpage);

		?>
		<div class="alert alert-danger d-lg-none text-center">
			<i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
		</div>

		<table class="admin w-100 d-none d-lg-table">
			<thead>
			<tr>
                <th class="w-mini-admin-cell d-none d-xl-table-cell">N° de BL</th>				
				<th>Fournisseur</th>
				<th>Date de reception</th>
				<th>Poids (Réception)</th>
				<?php if ($statut == 0) { ?>
				<th class="text-center">Sortie</th>
				<?php } ?>
                <th class="text-center">Incidents</th>
				<?php if ($statut == 1) {?>
				<th class="text-center nowrap">Nombre Produits</th>		
				<th>Vues actuelles</th>
				<th class="t-actions w-mini-admin-cell text-center">Visible</th>
				<th class="t-actions w-mini-admin-cell text-center">Modifier</th>
				<?php } ?>
				<th class="t-actions w-mini-admin-cell text-center">Documents</th>
				<th class="t-actions w-mini-admin-cell text-center">Produits</th>
				<th class="t-actions w-mini-admin-cell text-center">Details</th>
			</tr>
			</thead>
			<tbody>
			<?php

			$na = '<i class="far fa-question-circle text-danger fa-lg"></i>';
			$btnPoidsReception = '<button type="button" class="btn btn-sm btn-secondary btnPoidsReceptionLot margin-right-50"><i class="fa fa-weight"></i></button>';

			foreach ($listeLots as $lot) {				
				
				?>
				<tr data-id-lot="<?php echo $lot->getId();?>">
				<td class="w-mini-admin-cell d-none d-xl-table-cell nowrap"><?php echo $lot->getNum_bl() != '' ? $lot->getNum_bl() : '&mdash;';?></td>
				</td>				
					<td class="text-right nowrap">
							<?php echo $lot->getNom_fournisseur($na); ?>
					</td>					
					<td class="text-center nowrap">
					<?php echo $lot->getDate_reception() != '' ? Outils::dateSqlToFr($lot->getDate_reception()) : '&mdash;'; ?>
					</td>
					<?php if ($statut == 0) { ?>
						<td class="text-center nowrap"><?php echo $lot->getDate_out() != '' && $lot->getDate_out() != '0000-00-00 00:00:00' ? Outils::dateSqlToFr($lot->getDate_out()) : '&mdash;'; ?></td>
					<?php } ?>
					<td class="text-20 text-center"><?php echo $lotsNegoceManager->getPoidsLotNegoce($lot->getId()) > 0 ? number_format($lotsNegoceManager->getPoidsLotNegoce($lot->getId()), 3, '.', ' ') . ' <span class="texte-fin text-14">Kg</span>' : '&mdash;'; ?></td>
                    <td class="text-center">
						<?php
						$incidentsManager = new IncidentsManager($cnx);
						$params = ['id_lot_negoce' => $lot->getId()];
						$incidents = $incidentsManager->getIncidentsListe($params);
												
						if (empty($incidents)) {?>
                            <span class="gris-9">&mdash;</span>
						<?php }

						foreach ($incidents as $incident) { ?>

                            <span class="fa-stack ico-incident pointeur text-12"
                                  data-id-incident="<?php echo $incident->getId()?>"
                                  data-verbose="<?php echo $incident->getNom_type_incident(); ?>"
                                  data-date="Le <?php echo Outils::getDate_verbose($incident->getDate(), false, ' à '); ?>"
                                  data-user="<?php echo $incident->getNom_user(); ?>">
                              <i class="fas fa-bookmark fa-stack-2x text-danger"></i>
                              <i class="fas fa-exclamation-triangle fa-stack-1x fa-inverse margin-top--2"></i>
                            </span>

						<?php }

						// affichage des incidents s'il y en
						?>

                    </td>					
					<?php if ($statut == 1) { ?>
					<td class="text-center nowrap" ><?php echo  $lotsNegoceManager->getNbProduitsByLot($lot); ?></td>					
					<?php if ($statut == 1) { ?>
					<td class="w-court-admin-cell"><?php
					if (is_array($lot->getVues()) && !empty($lot->getVues())) {
						 $firstVue = true;
                         foreach ($lot->getVues() as $lotvue) {
							if ($lotvue->getVue() instanceof Vue) { 								

								?>
                                            <span class="badge badge-info form-control text-12 texte-fin <?php echo !$firstVue ? 'margin-top-5' : ''; ?>"><?php echo $lotvue->getVue()->getNom(); ?></span>
                                        <?php } else { ?>
                                            echo '&mdash;';
                                    <?php } // FN test instanciation objet Vue

                                                                    $firstVue = false;
                                                                } // FIN boucle vues

                                                            } else { ?>
                                    <span class="badge badge-danger form-control text-14">Terminé</span>
                                <?php } // FIN test vues
                                ?>
                            </td>
																<?php } ?>
					<td class="t-actions w-mini-admin-cell">
						<input type="checkbox" class="togglemaster switch-visibilite-lot"
							   data-toggle              = "toggle"
							   data-on                  = "Oui"
							   data-off                 = "Non"
							   data-onstyle             = "success"
							   data-offstyle            = "danger"
							   data-size                = "small"
							<?php
							// Statut coché
							echo $lot->isVisible()  ? 'checked' : ''; ?>/>
					</td>
					

						<td class="t-actions w-mini-admin-cell"><button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#modalLotEdit" data-lot-id="<?php
							echo $lot->getId(); ?>"><i class="fa fa-edit"></i> </button>
						</td>
					<?php } ?>
					<td class="t-actions w-mini-admin-cell"><button type="button" class="btn  btn-secondary" data-toggle="modal" data-target="#modalLotDocs" data-lot-id="<?php
						echo $lot->getId(); ?>"><i class="fas fa-copy"></i> </button>
					</td>
					<td class="t-actions w-mini-admin-cell"><button type="button" class="btn  btn-secondary" data-toggle="modal" data-target="#modalLotNegProduits" data-lot-id="<?php
						echo $lot->getId(); ?>"><i class="fa fa-dolly"></i> </button>
					</td>
					<td class="t-actions w-mini-admin-cell"> <button type="button" class="btn  btn-secondary" data-toggle="modal" data-target="#modalLotInfo" data-statut="<?php echo $statut; ?>" data-lot-id="<?php
 						echo $lot->getId(); ?>"><i class="fa fa-ellipsis-h"></i> </button>
                	</td>
				</tr>
				
			<?php } // FIN boucle lots
			?>
			</tbody>
			
		</table>
		<?php

		// Pagination (aJax)
		if (isset($pagination)) {
			// Pagination bas de page, verbose...
			$pagination->setVerbose_pagination(1);
			$pagination->setVerbose_position('right');
			$pagination->setNature_resultats('lot');
			$pagination->setNb_apres(2);
			$pagination->setNb_avant(2);

			echo ($pagination->getPaginationHtml());
		} // FIN test pagination



	} // FIN test résultats trouvés

	exit;

} // FIN mode

/* ----------------------------------------------------------------------------
MODE - Change la visibilité d'un lot (swith admin)
-----------------------------------------------------------------------------*/
function modeChangeVisibilite() {
	global $lotsNegoceManager;

	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
	$visible = isset($_REQUEST['visible']) ? intval($_REQUEST['visible']) : -1;
	if ($id_lot == 0 || $visible < 0 || $visible > 1) { exit; }

	$lot = $lotsNegoceManager->getLotNegoce($id_lot);
	if (!$lot instanceof LotNegoce) { exit; }

	$lot->setVisible($visible);
	echo $lotsNegoceManager->saveLotNegoce($lot) ? 1 : 0;
	exit;

} // FIN mode

/* --------------------------------------
MODE - Modale édition du lot
---------------------------------------*/
function modeModalLotEdit() {

	global $cnx, $lotsNegoceManager;

	$paysManager     = new PaysManager($cnx);


	$id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

	if ($id_lot == 0) { echo '-1'; exit; }

	$lot = $lotsNegoceManager->getLotNegoce($id_lot);


	if (!$lot instanceof LotNegoce) { echo '-1'; exit; }

	echo '<i class="fa fa-box"></i><span class="gris-7 ">Lot de négoce ID</span> ' . $lot->getNum_bl(). ' du ' .Outils::dateSqlToFr($lot->getDate_reception()).'^' ;
	

	?>
	<form class="row" id="formUpdLot">
		<input type="hidden" name="mode" value="updLot"/>
		<input type="hidden" name="id_lot" id="updLotIdLot" value="<?php echo $id_lot; ?>"/>
		<div class="col">
            <div class="row">
                <div class="col">
                    <div class="alert alert-dark">
                        <div class="row">
                            <div class="col-5">
                                <label class="pt-1 gris-5">
                                        <span class="fa-stack text-14 gris-9 mr-1">
                                          <i class="fas fa-circle fa-stack-2x"></i>
                                          <i class="fas fa-hashtag fa-stack-1x fa-inverse text-12 gris-e"></i>
                                        </span>Numéro de BL :</label>
                            </div>
                            <div class="col-7">
                                <input type="text" class="form-control text-20" placeholder="Numéro de BL" name="num_bl" value="<?php echo $lot->getNum_bl();?>"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			<div class="alert alert-dark">				
				<div class="row">
					<div class="col-5">
						<label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-industry fa-stack-1x fa-inverse text-14 gris-e"></i>
                            </span>Fournisseur :</label>
					</div>

					<div class="col-7">
						<select class="selectpicker show-tick form-control" name="id_fournisseur" title="Sélectionnez...">
							<?php
							$tiersManager = new TiersManager($cnx);
							foreach ($tiersManager->getListeFournisseurs([]) as $frs) { ?>
								<option value="<?php echo $frs->getId(); ?>" <?php echo $frs->getId() == $lot->getId_fournisseur() ? 'selected' : ''; ?>><?php echo $frs->getNom(); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>

				<div class="row">
                            <div class="col-5">
                                    <label class="pt-1 gris-5">
                                        <span class="fa-stack text-14 gris-9 mr-1">
                                          <i class="fas fa-circle fa-stack-2x"></i>
                                          <i class="far fa-calendar-alt fa-stack-1x fa-inverse text-14 gris-e"></i>
                                        </span>Date de reception</label>
                            </div>
                            <div class="col-7">
								<div class="input-group">
                                        <input type="text" class="datepicker form-control" placeholder="Sélectionnez..." id="updDateReception" value="<?php echo Outils::dateSqlToFr($lot->getDate_reception());?>"  name="date_reception"/>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="far fa-calendar-alt gris-5 fa-lg"></i></span>
                                        </div>
                                </div>
                            </div>
                </div>
                            <!--Fin--> 
				</div>
				
					

			</div>

		</div>
	</div>
	</form>
	<?php
	echo '^'; // Séparateur Body / Footer

	// Si le lot est pret c'est à dire qu'il a une date d'abattage, un abattoir et une originie, on ne peux plus le supprimer mais on peux le sortir du lot
	if (!empty($lot->getProduits())) { ?>
		<button type="button" class="btn btn-info btn-sm btnSortieLot mr-1"><i class="fa fa-sign-out-alt fa-lg vmiddle mr-1"></i> Sortie du lot</button>
		<button type="button" class="btn btn-success btn-sm btnSaveLot"><i class="fa fa-save fa-lg vmiddle mr-1"></i> Enregistrer</button>
		<?php
		// Sinon (il n'est pas encore en circuit) on peux supprimer mais pas le sortir du lot
	} else { ?>
		<button type="button" class="btn btn-success btn-sm btnSaveLot mr-1"><i class="fa fa-save fa-lg vmiddle mr-1"></i> Enregistrer</button>
		<button type="button" class="btn btn-danger btn-sm btnDelLot"><i class="fa fa-times fa-lg vmiddle mr-1"></i> Supprimer</button>
		<?php
	} // FIN test mises à jour	

	exit;
} // FIN mode

/* ------------------------------------
MODE - Enregistre les modifs d'un lot
------------------------------------*/
function modeUpdLot() {

	global
	$cnx,
	$logsManager,
	$lotsNegoceManager,
	$utilisateur;

	$num_bl          = isset($_REQUEST['num_bl'])          ? trim(strtoupper($_REQUEST['num_bl']))                : '';
	$date_entree  = isset($_REQUEST['date_entree'])  ? Outils::dateFrToSql($_REQUEST['date_entree'])     : '';
	$date_reception  = isset($_REQUEST['date_reception'])  ? Outils::dateFrToSql($_REQUEST['date_reception'])     : '';

	
	$id_fournisseur  = isset($_REQUEST['id_fournisseur'])  ? intval($_REQUEST['id_fournisseur'])                  : 0;
	$id_lot          = isset($_REQUEST['id_lot'])          ? intval($_REQUEST['id_lot'])                          : 0;

	// Si on a du mal à récupérer le lot, on retourne une erreur
	if ($id_lot == 0) { echo '-2'; exit; }
	$lot = $lotsNegoceManager->getLotNegoce($id_lot);
	if (!$lot instanceof LotNegoce) { echo '-2'; exit; }
	 $lot->setNum_bl($num_bl);
	 $lot->setDate_reception($date_reception);
	if ($lot->getDate_entree()           != $date_entree) { $lot->setDate_entree($date_entree);}
	if ($lot->getId_fournisseur()  != $id_fournisseur && $id_fournisseur > 0 )      { $lot->setId_fournisseur($id_fournisseur);        }	
	       
	//Fin
	// Si des modifications ont eue lieu, on enregistre...
	if (!empty($lot->attributs)) {
		if (!$lotsNegoceManager->saveLotNegoce($lot)) {
			echo '-3'; exit;

			// SI modif OK, on log...
		} else {

			$log = new Log([]);
			$log->setLog_type('info');

			$texteLog = 'Modification du lot de négoce #' . $id_lot . ' (';
			foreach ($lot->attributs as $attrib) {
				$texteLog.= $attrib.', ';
			}
			$texteLog = substr($texteLog,0,-2);
			$texteLog.= ')';

			$log->setLog_texte($texteLog);
			$logsManager->saveLog($log);

		} // FIN test modif

	} else { echo '-4'; }

	exit;

} // FIN mode

/* ------------------------------------
MODE - Suppression d'un lot
------------------------------------*/
function modeSupprimeLot() {

	global
	$logsManager,
	$lotsNegoceManager;

	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;

	// Si on a du mal à récupérer le lot, on retourne une erreur
	if ($id_lot == 0) { echo '-1'; exit; }
	$lot = $lotsNegoceManager->getLotNegoce($id_lot);
	if (!$lot instanceof LotNegoce) { echo '-1'; exit; }

	$lot->setSupprime(1);
	if (!$lotsNegoceManager->saveLotNegoce($lot)) {
		echo '-1';

		// SI suppression OK
	} else {

	    // Log
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte("Suppression du lot de negoce " . $lot->getNumlot());
		$logsManager->saveLog($log);

	} // FIN test modif

	exit;

} // FIN mode


/* ------------------------------------
MODE - Sortie d'un lot
------------------------------------*/
function modeSortieLot() {

	global
	$cnx,
	$logsManager,
	$lotsNegoceManager;

	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;

	// Si on a du mal à récupérer le lot, on retourne une erreur
	if ($id_lot == 0) { echo '-1'; exit; }
	$lot = $lotsNegoceManager->getLotNegoce($id_lot);
	if (!$lot instanceof LotNegoce) { echo '-1'; exit; }

	$lot->setDate_out(date('Y-m-d H:i:s'));
	if (!$lotsNegoceManager->saveLotNegoce($lot)) {
		echo '-1';

		// SI sortie OK, on supprime les vues et on log...
	} else {

		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("Sortie du lot de négoce " . $lot->getNum_bl());
		$logsManager->saveLog($log);

	} // FIN test modif

	exit;

} // FIN mode

/* ------------------------------------
MODE - Modale des produits (BO)
------------------------------------*/
function modeModalLotNegProduits() {

	global $cnx, $lotsNegoceManager;


	$id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

	// Si on a du mal à récupérer le lot, on retourne une erreur
	if ($id_lot == 0) { echo '-1'; exit; }
	$lot = $lotsNegoceManager->getLotNegoce($id_lot);

	
	if (!$lot instanceof LotNegoce) { echo '-1'; exit; }
	$produitsManager = new ProduitManager($cnx);

	echo '<i class="mr-1 fa fa-dolly gris-9 text-12"></i>Produits du lot de négoce: '.$lot->getNum_bl() .' du '.Outils::dateSqlToFr($lot->getDate_reception()).'^';
	?>

	<div class="alert alert-secondary">
		<p class="text-14 nomargin gris-9">
			<i class="mr-1 fa fa-plus gris-a"></i>Ajouter un produit :
		</p>
		<form class="row margin-top-5" id="formAddPdtNegoce">
            <input type="hidden" name="mode" value="addPdtLotNegoce">
            <input type="hidden" name="id_lot" value="<?php echo $id_lot; ?>">
			<div class="col">
			<p class="nomargin text-12 texte-fin gris-5">Produit negoce</p>
			<select name="id_pdt" class="form-control selectpicker" title=""  data-live-search="true" data-live-search-placeholder="Rechercher">
			<?php
			$listePdts = $produitsManager->getListeProduits([]);
					foreach ($listePdts as $pdt) {
						$vendu_negoce = $pdt->getVendu_negoce();
						if($vendu_negoce > 0){?>
							<option value="<?php echo $pdt->getId(); ?>" id="option_produit" data-qte-pcb="<?php echo $pdt->getPcb(); ?>"  data-subtext="<?php echo $pdt->getCode(); ?>"><?php echo $pdt->getNom(); ?></option>
						<?php						
						}						
					 }
			?>				
								
				</select>
			</div>
			
			<div class="col-2 padding-right-0">
                <p class="nomargin text-12 texte-fin gris-5">Numéro de lot</p>
                <div class="input-group">
                    <input type="text" class="form-control num_lot" placeholder="" value="" name="num_lot" />
                    <input hidden class="form-control" placeholder="" value="1" name="numero_palette" />
                </div>
            </div>

			<div class="col-2 padding-right-0">
                <p class="nomargin text-12 texte-fin gris-5">DLC/DDM</p>
                <div class="input-group">
                    <input id="dlc_ddm" type="text" class="datepicker form-control" placeholder="Sélectionnez..." name="dlc" />
                </div>
            </div>


			<div class="col-1 padding-right-0">
				<p class="nomargin text-12 texte-fin gris-5">Cartons</p>
				<div class="input-group">
					<input type="text" id="nb_cartons" class="form-control" placeholder="" name="cartons" />
				</div>
			</div>


			<div class="col-2 padding-right-0">
				<p class="nomargin text-12 texte-fin gris-5">Quantité</p>
				<div class="input-group">
					<input type="text" id="nb_pieces" class="form-control" placeholder="" name="quantite" />
				</div>
			</div>	
			



			<div class="col-2">
				<p class="nomargin text-12 texte-fin gris-5">Poids</p>
				<div class="input-group">
					<input type="text" class="form-control" placeholder="" name="poids" />
					<div class="input-group-append">
						<span class="input-group-text texte-fin text-12 padding-left-5 padding-right-5">kg</span>
					</div>
				</div>
			</div>
			
			<div class="col-1">
				<p class="nomargin text-12 texte-fin gris-5">&nbsp;</p>
				<button type="button" class="btn btn-info form-control btnAddPdtNegoce"><i class="fa fa-check"></i></button>
			</div>
					
		</form>
	
	</div>

	<table class="table admin table-v-middle">
		<thead>
		<tr>
			<th>Produit</th>
			<th class="w-150px">N° de lot</th>
			<th class="w-150px">DLC/DDM</th>
			<th class="text-center w-75px">Cartons</th>
			<th class="text-center w-150px">Poids <span class="texte-fin text-12">(kg)</span></th>
			<th class="text-center w-75px">Quantité</th>			
			<th class="text-center nowrap w-100px">BL sortant</th>			
			<th class="t-actions text-center w-75px">Modifier</th>
			<th class="text-center nowrap w-75px">Supprimer</th>
		</thead>
		<tbody id="listeProduitsLotNegoce" data-lot-id="<?php echo $id_lot; ?>">
			<tr><td colspan="7" class="padding-20 text-center gris-9"><i class="fa fa-spin fa-spinner"></i></td></tr>
		</tbody>
	</table>

	<?php
	exit;

} // FIN mode

/* ------------------------------------
MODE - Liste des produits
------------------------------------*/
function modeListeProduitsLotNegoce($id_lot = 0) {

	global $cnx, $lotsNegoceManager,$mode;

    if ($id_lot == 0) { $id_lot = isset($_REQUEST['lot_id']) ? intval($_REQUEST['lot_id']) : 0; }

    // Préparation pagination (Ajax)
	$nbResultPpage      = 15;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode='.$mode.'&lot_id='.$id_lot;
	$start              = ($page-1) * $nbResultPpage;

	// Si on a du mal à récupérer le lot, on retourne une erreur
	if ($id_lot == 0) { echo '<tr><td colspan="5">Erreur de récupération du lot !</td></tr>'; exit; }
	$lot = $lotsNegoceManager->getLotNegoce($id_lot);
	if (!$lot instanceof LotNegoce) {  echo '<tr><td colspan="5">Erreur d\'instanciation du lot !</td></tr>';  exit; }

	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;
	$params['id_lot'] 	        = $id_lot;

	$listeProduits = $lotsNegoceManager->getListeLotsNegoceProduits($params);
	
	if (empty($listeProduits)) { ?>
		<tr><td colspan="8" class="padding-20 text-center gris-9">Aucun produit&hellip;</td></tr>
		<?php
		exit;
	}

	// Liste non vide, construction de la pagination...
	$nbResults  = $lotsNegoceManager->getNb_results();
	$pagination = new Pagination($page);

	$pagination->setUrl($filtresPagination);
	$pagination->setNb_results($nbResults);
	$pagination->setAjax_function(true);
	$pagination->setNb_results_page($nbResultPpage);

	// Boucle sur les produits de négoce du lot
	foreach ($listeProduits as $pdtNegoce) {
				?>

        <tr>
            <td class="padding-left-0 now"><?php echo $pdtNegoce->getNom_produit(); ?></td>
            <td>
                <input type="text" maxlength="500" class="form-control text-center inputNum_lot" value="<?php echo $pdtNegoce->getNum_lot(); ?>"/>
            </td>
            <td>				
                <input  type="text" class="datepickers form-control inputDlc" value="<?php echo Outils::dateSqlToFr($pdtNegoce->getDlc()); ?>"/>
            </td>
            <td class="w-75px">
                <input type="text" maxlength="3" class="form-control text-center inputCartons" value="<?php echo $pdtNegoce->getNb_cartons(); ?>"/>
            </td>
            <td class="w-150px">
                <input type="text" maxlength="20" class="form-control text-center inputPoids" value="<?php echo $pdtNegoce->getPoids(); ?>"/>
            </td>
            <td class="w-75px">
                <input type="text" maxlength="3" class="form-control text-center inputQuantite" value="<?php echo $pdtNegoce->getQuantite(); ?>"/>
            </td>            
            <td class="text-center">
				
                <?php
                if ($pdtNegoce->getId_bl() == 0) { ?>
                    <i class="fa fa-times gris-9"></i>
				<?php } else { ?>
					
					<a href="gc-bls.php?i=<?php echo base64_encode($pdtNegoce->getId_bl()); ?>" class="text-info texte-fin text-13 d-block"><?php echo $pdtNegoce->getNumero_bl(); ?></a>
				<?php }
                ?>
              </td>
			<td class="w-75px text-center">
                <button type="button" class="btn btn-success btnSavePdtNegoce" data-id="<?php echo $pdtNegoce->getId_lot_pdt_negoce(); ?>"><i class="fa fa-fw fa-save"></i></button>                
            </td>

			<td class="w-75px text-center">
				<button type="button" class="btn btn-danger btnDeletePdtNegoce" data-id="<?php echo $pdtNegoce->getId_lot_pdt_negoce(); ?>"><i class="fa fa-fw fa-trash"></i></button>
			</td> 
		
		</tr>

    <?php
    } // FIN boucle sur les produits

	// Pagination (aJax)
	if (isset($pagination)) {
		// Pagination bas de page, verbose...
		$pagination->setVerbose_pagination(1);
		$pagination->setVerbose_position('right');
		$pagination->setNature_resultats('produit');
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);

		echo '<tr><td colspan="10" class="text-right">';
		echo $pagination->getPaginationHtml();
		echo '</td></tr>';
	} // FIN test pagination


} // FIN mode


function modeAddPdtLotNegoce() {

	global $cnx, $lotsNegoceManager, $utilisateur;

	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
	$id_pdt = isset($_REQUEST['id_pdt']) ? intval($_REQUEST['id_pdt']) : 0;
	$cartons = isset($_REQUEST['cartons']) ? intval($_REQUEST['cartons']) : 0;
	$numero_palette = isset($_REQUEST['numero_palette']) ? intval($_REQUEST['numero_palette']) : 0;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0.0;
	$qte = isset($_REQUEST['quantite']) ? intval($_REQUEST['quantite']) : '0';	
	$num_lot = isset($_REQUEST['num_lot']) ? trim($_REQUEST['num_lot']) : '';
	$dlc = isset($_REQUEST['dlc']) ? Outils::dateFrToSql($_REQUEST['dlc']) :  '';

    // On récupère l'id de la palette par son numéro (on prends le dernier en date)
    $palettesManager = new PalettesManager($cnx);
   // $id_palette = $palettesManager->getLastIdPaletteByNumero($numero_palette);

	$negoce_produit = new NegoceProduit([]);
	$negoce_produit->setPoids($poids);
	$negoce_produit->setQuantite($qte);
	$negoce_produit->setNumero_palette($negoce_produit);
	$negoce_produit->setSupprime(0);
	$negoce_produit->setDate_add(date('Y-m-d H:i:s'));
	$negoce_produit->setId_lot_negoce($id_lot);
	$negoce_produit->setId_pdt($id_pdt);
	$negoce_produit->setNb_cartons($cartons);
	$negoce_produit->setTraite(0);
	$negoce_produit->setUser_add($utilisateur->getId());
	$negoce_produit->setNum_lot($num_lot);
	$negoce_produit->setDlc($dlc);

	$lotsNegoceManager->saveNegoceProduit($negoce_produit);

	modeListeProduitsLotNegoce($id_lot);
	exit;
} // FIN mode


// Modif / supprime un produit négoce d'un lot de négoce (modale admin)
function modeUpdPdtNegoce() {

	global $cnx, $lotsNegoceManager, $logsManager;

	$id_pdt_negoce = isset($_REQUEST['id_pdt_negoce']) ? intval($_REQUEST['id_pdt_negoce']) : 0;
	$nb_cartons = isset($_REQUEST['nb_cartons']) ? intval($_REQUEST['nb_cartons']) : 0;
	$quantite = isset($_REQUEST['quantite']) ? intval($_REQUEST['quantite']) : 0;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0.0;	
	$num_lot = isset($_REQUEST['num_lot']) ? trim($_REQUEST['num_lot']) : '';
	$numero_palette = isset($_REQUEST['numero_palette']) ? intval($_REQUEST['numero_palette']) : 0;

	
	$dlc = isset($_REQUEST['dlc']) ? Outils::dateFrToSql($_REQUEST['dlc']) :  '';	

	$pdtNegoce = $lotsNegoceManager->getNegoceProduit($id_pdt_negoce);

	
	if (!$pdtNegoce instanceof NegoceProduit) { ?>

        <tr>
            <td colspan="5" class="text-center bg-danger padding-20 text-white">
                <i class="fa fa-exclamation-triangle fa-lg mb-2"></i>
                <p>Erreur lors de l'instanciation de l'objet NegoceProduit !<br><code>ID <?php echo $id_pdt_negoce; ?></code></p>
            </td>
        </tr>

    <?php exit; }

	$log = new Log([]);


	if ($nb_cartons == 0 || $poids < 0.01) {
		$pdtNegoce->setSupprime(1);

    } else {
		$pdtNegoce->setNb_cartons($nb_cartons);
		$pdtNegoce->setPoids($poids);
		$pdtNegoce->setNum_lot($num_lot);
		$pdtNegoce->setQuantite($quantite);
		$pdtNegoce->setDlc($dlc);		
		//$id_palette = $palettesManager->getLastIdPaletteByNumero($numero_palette);
		//$pdtNegoce->setId_palette($id_palette);
    }

	if ($lotsNegoceManager->saveNegoceProduit($pdtNegoce)) {
		$log->setLog_type('success');
		$log->setLog_texte('Modification du produit de négoce #' . $id_pdt_negoce);
    } else {
		$log->setLog_type('danger');
		$log->setLog_texte('Echec de la modification du produit de négoce #' . $id_pdt_negoce);
    }

	$logsManager->saveLog($log);

	modeListeProduitsLotNegoce($pdtNegoce->getId_lot_negoce());

    exit;

} // FIN mode

	//Nouvelle commande, le lot negoce soit identique de celle viande et abat
	function modeGenereNumLot()
{
	global $cnx;

    $regexDateFr =  '#^(([1-2]\d|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9]\d{2})$#';
    $date       = isset($_REQUEST['date']) && preg_match($regexDateFr, $_REQUEST['date']) ? Outils::dateFrToSql($_REQUEST['date']) : '';	
    $fournisseur       = isset($_REQUEST['fournisseur']) ? intval($_REQUEST['fournisseur']) : '';
	
    if ($date == "" || $fournisseur == "") {
        exit();
    }

    /*
     * Année sur 2 chiffres (19) (auto) 24
	 * id fournisseur 100
	 * ajout la seconde (0 à 60)
     * Jour de la semaine (A, B, C...) (47C)
	 * ajout 1 s'il y a un doublon (1)
	 * resultat final: Année + id fournisseur + seconde + jour de la semaine + 1 (24143547B ou 24143547B1)
     */
    $dateNegoce = new DateTime($date);	
    $num_bl = $dateNegoce->format('y');

    // id de fournisseur
    $num_bl .= $fournisseur;
    $datetime = new DateTime($date);

	$seconde = date('s');
	$num_bl .= $seconde;
	$num_bl .= $datetime->format('W');
    // Jour de la semaine (w)
    $jours = [0 => 'G', 1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F'];

    $num_bl .= $jours[$datetime->format('w')];

    echo $num_bl;

    exit;
} // FIN mode

/* ------------------------------------
MODE - Enregistre un nouveau lot
------------------------------------*/
function modeAddLot()
{

    global
        $cnx,
        $lotsManager,
        $logsManager;

    $especesManager = new ProduitEspecesManager($cnx);

    $regexDateFr     =  '#^(([1-2]\d|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9]\d{2})$#';

    $numlot          = isset($_REQUEST['numlot'])        ? trim(strtoupper($_REQUEST['numlot'])) : '';
    $id_abattoir     = isset($_REQUEST['id_abattoir'])   ? intval($_REQUEST['id_abattoir'])      : 0;
    $id_origine      = isset($_REQUEST['id_origine'])    ? intval($_REQUEST['id_origine'])       : 0;
    $id_fournisseur  = isset($_REQUEST['id_fournisseur']) ? intval($_REQUEST['id_fournisseur'])  : 0;
    $date_abattage   = isset($_REQUEST['date_abattage']) && preg_match($regexDateFr, $_REQUEST['date_abattage']) ? Outils::dateFrToSql($_REQUEST['date_abattage']) : '';

    $reception       = isset($_REQUEST['date_reception']) && preg_match($regexDateFr, $_REQUEST['date_reception']) ? Outils::dateFrToSql($_REQUEST['date_reception']) : '';
    $poids_abattoir     = isset($_REQUEST['poids_abattoir'])   ? floatval(str_replace(',', '.', $_REQUEST['poids_abattoir']))  : 0.0;
    $poids_reception = isset($_REQUEST['poids_reception'])  ? floatval(str_replace(',', '.', $_REQUEST['poids_reception'])) : 0.0;
    //$composition     = isset($_REQUEST['composition'])    ?  $_REQUEST['composition'] : 0;

	


    // Fonction de retour erreur
    function erreur($numlot)
    {
        header('Location: ../../admin-lots-add.php?e=' . $numlot);
        return false;
    }
    if ($numlot == '') {
        erreur();
    }


    // Si c'est un lot Abats et qu'on a pas le A, on le rajoute
    if (substr($composition, 0, 1) == 'A' && strtoupper(substr($numlot, -1)) != 'A') {
        $numlot = $numlot . 'A';

        // Si c'est pas un lot Abats et qu'on a un A, on l'enlève.
    } else if (substr($composition, 0, 1) != 'A' && strtoupper(substr($numlot, -1)) == 'A') {
        $numlot = substr($numlot, 0, -1);
    } // FIN contrôle du numéro de lot

    $lot = new Lot([]);


    $lot->setNumlot($numlot);
    $lot->setDate_add(date('Y-m-d H:i:s'));
    $lot->setVisible(1);
    $lot->setId_fournisseur($id_fournisseur);

    if ($composition != 'va') {
        $compo = substr($composition, 0, 1) == 'A' ? 2 : 1;
        $lot->setId_espece(str_replace('A', '', $composition));
        $lot->setComposition($compo);
    } else {
        $lot->setId_espece($especesManager->getIdEspeceViande());
        $lot->setComposition(1);
    }

    // Champs optionnels
    if ($id_origine > 0) {
        $lot->setId_origine($id_origine);
    }
    if ($id_abattoir > 0) {
        $lot->setId_abattoir($id_abattoir);
    }
    if ($date_abattage != '') {
        $lot->setDate_abattage($date_abattage);
    }

    if ($reception != '') {
        $lot->setDate_reception($reception);
    }
    if ($poids_abattoir > 0.0) {
        $lot->setPoids_abattoir($poids_abattoir);
    }
    if ($poids_reception > 0.0) {
        $lot->setPoids_reception($poids_reception);
    }

    $id_lot =  $lotsManager->saveLot($lot);
    // Si lot créé, on associe éventuellement la vue et on Log
    if (intval($id_lot) > 0) {

        // Si on dispose des infos nécessaires, on place la vue sur Reception
        if ($id_origine > 0 && $id_abattoir > 0 && $date_abattage != '') {

            $vuesManager = new VueManager($cnx);
            $vueReception = $vuesManager->getVueByCode('rcp');
            $lotVue = new LotVue([]);
            $lotVue->setId_lot($id_lot);
            $lotVue->setId_vue($vueReception->getId());
            $lotVue->setDate_entree(date('Y-m-d H:i:s'));
            $lotVuesManager = new LotVueManager($cnx);
            $lotVuesManager->saveLotVue($lotVue);
        }

        $log = new Log([]);
        $log->setLog_type('success');
        $log->setLog_texte('Création du lot ' . $numlot);
        $logsManager->saveLog($log);

        // Si erreur, on reviens...
    } else {
        erreur($numlot);
        exit;
    } // FIN test création lot

    // Si on a un double lot, on crée le second en "A" pour abats
    if ($composition == 'va') {
        $lot_abats = clone $lot;
        $lot_abats->setId(''); // Pour éviter un update de la méthode Save
        $lot_abats->setNumlot($lot->getNumlot() . 'A');
        $lot_abats->setComposition(2);
        $lot_abats->setId_espece($especesManager->getIdEspeceAbats());

        // On n'intègre pas le poids abattoir pour le lot Abats (demande client sur site du 25/06/2019)
        $lot_abats->setPoids_abattoir(0);

        // Si lot créé, on Log

        $id_lot_abats = $lotsManager->saveLot($lot_abats);
        if (intval($id_lot_abats) > 0) {

            // On associe aussi la vue pour le second lot créé
            if (!isset($vuesManager)) {
                $vuesManager = new VueManager($cnx);
            }
            if (!isset($vueReception)) {
                $vueReception = $vuesManager->getVueByCode('rcp');
            }
            $lotVueA = new LotVue([]);
            $lotVueA->setId_lot($id_lot_abats);
            $lotVueA->setId_vue($vueReception->getId());
            $lotVueA->setDate_entree(date('Y-m-d H:i:s'));
            if (!isset($lotVuesManager)) {
                $lotVuesManager = new LotVueManager($cnx);
            }
            $lotVuesManager->saveLotVue($lotVueA);


            $log = new Log([]);
            $log->setLog_type('success');
            $log->setLog_texte('Création du lot ' . $lot_abats->getNumlot());
            $logsManager->saveLog($log);

            // Si erreur, on reviens...
        } else {
            erreur($lot_abats->getNumlot());
            exit;
        } // FIN test création lot
    }

    // Récupération de l'abattoir pour Bizerba
    $abattoirManager = new AbattoirManager($cnx);
    $abattoir = $abattoirManager->getAbattoir($id_abattoir);

    // Si on a pas les données nécéssaires, on envoie pas vers Bizerba...
    if (!$abattoir instanceof Abattoir || $date_abattage == '' || trim($abattoir->getNumagr()) == '') {
        header('Location: ../../admin-lots.php?erbz');
        exit;
    }

    $bzok = envoiLotBizerba($lot, $abattoir);

    // Si on a un lot double, on envoie aussi le lot Abats
    if ($bzok && isset($lot_abats)) {
        $bzok = envoiLotBizerba($lot_abats, $abattoir, true);
    }


    $paramBz = !$bzok ? '?erbz' : '';
    header('Location: ../../admin-lots.php' . $paramBz);
    exit;
} // FIN mode

function modeSupprPdtLotNegoce()
{
	global $cnx, $lotsNegoceManager;
	$id_lot_pdt_negoce = isset($_REQUEST['id_lot_pdt_negoce']) ? intval($_REQUEST['id_lot_pdt_negoce']) : 0;	
	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;	

	if ($id_lot_pdt_negoce == 0) { exit("ERREUR - Identification du ProduitNegoce impossible ! Code erreur : UGV6N9C8"); }

	$lot = $lotsNegoceManager->getNegoceProduit($id_lot_pdt_negoce);
	if (!$lot instanceof NegoceProduit) { echo '-1'; exit; }

	$lot->setSupprime(1);

	if (!$lotsNegoceManager->saveNegoceProduit($lot)) {
		echo '-1';
	}

	modeListeProduitsLotNegoce($id_lot);
	
	exit;
}


function modeModalLotInfo(){

    global $cnx, $lotsNegoceManager, $utilisateur, $lotsManager;

    //$lotsManager->updateComposBlArchives();


    $produitsManager = new ProduitManager($cnx);
    $facturesManager = new FacturesManager($cnx);
    $produitsManager->cleanBlLignesSupprimees();

    $id_lot_pdt_negoce = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

	
    if ($id_lot_pdt_negoce == 0) {
        echo '-1';
        exit;
    }

    $lot = $lotsNegoceManager->getDetailsProduitsNegoce($id_lot_pdt_negoce);	
	
	
    if (!$lot instanceof NegoceProduit) {
        echo '-1';
        exit;
    }

   //$lotsNegoceManager->updatePoidsProduitsFromCompos($id_lot);   
    
    echo '<i class="fa fa-box"></i><span class="gris-7 ">Lot de négoce</span> ' . $lot->getNum_lot(). '/'. $lot->getNom_produit() ;

    echo '^'; // Séparateur Title / Body
    ?>

    <!-- NAVIGATION ONGLETS -->
    <ul class="nav nav-tabs margin-top--10" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" role="tab" href="#general" aria-selected="true"><i class="fa fa-sm fa-info-circle gris-b mr-2"></i>Général</a></li>        
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#pdts"><i class="fa fa-sm fa-barcode gris-b mr-2"></i>Produits</a></li>        
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#stk"><i class="fa fa-sm fa-layer-group gris-b mr-2"></i>Stock</a></li>                        
    </ul>
    <!-- FIN NAVIGATION ONGLETS -->

    <!-- CONTENEUR ONGLETS -->
    <div class="tab-content">
        <!-- ONGLET GENERAL -->
        <div id="general" class="tab-pane fade show active" role="tabpanel" data-id-lot="<?php echo $lot->getId_lot_pdt_negoce(); ?>" data-statut="<?php echo $lot->getStatus(); ?>">

            <div class="row">
                <div class="col-3 margin-top-10 ">
                    <div class="alert alert-dark text-center">
                        <h2 <?php echo strlen($lot->getNum_lot()) > 10 ? 'class="text-26"' : ''; ?>><?php echo $lot->getNum_lot(); ?></h2>                    
                    </div>
					<?php  if($lot->getStatus() == 1 ){
							?>
							<button type="button" class="btn btn-danger mb-3 form-control btn-reopenlot"><i class="fa fa-lock-open mr-2"></i> Ré-ouvrir&hellip;</button>
						<?php
					} else {?>
							<button type="button" class="btn btn-success btn-sm mb-3 form-control btnSortieLot mr-1"><i class="fa fa-sign-out-alt fa-lg vmiddle mr-1"></i> Sortie du lot</button>
					<?php } ?>					
                    <table class="admin w-100 d-none d-lg-table">
                        <thead>
                    <tr>    
                        <th  class="w-mini-admin-cell d-none d-xl-table-cell">Nom du produit</th>
                        <th>Nb de cartons</th>
                        <th>Poids</th>
                        <th>Quantite</th>
						<th>DLC/DDM</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?php echo $lot->getNom_produit() ;?></td>
                        <td><?php echo $lot->getNb_cartons() ;?></td>
                        <td><?php echo $lot->getPoids() ;?></td>
                        <td><?php echo $lot->getQuantite() ;?></td>
						<td><?php echo Outils::dateSqlToFr($lot->getDlc()) ;?></td>
                    </tr>
                    </tbody>
                    </table>
                 </div>
                <div class="col-9 margin-top-10 position-relative">

                    <table class="table table-border table-v-middle text-14 table-padding-4-8">
                        <tr>
                            <th class="nowrap">Fournisseur :</th>
                            <td class="text-center"><?php echo $lot->getFournisseur(); ?></td>
						</tr>
						<tr>                            
                            <th class="nowrap">Agrément :</th>
                            <td class="text-center"><?php echo $lot->getNumagr() != '' ? $lot->getNumagr() : '&mdash;'; ?></td>
                        </tr>					
						
						<tr>
                            <th class="nowrap">Date réception :</th>
                            <td class="text-center"><?php echo
                                                    $lot->getDate_reception() != '' && $lot->getDate_reception() != '0000-00-00'
                                                        ? Outils::getDate_only_verbose($lot->getDate_reception(), true, false) : '&mdash;';?></td>
                            
                        </tr>
                        <tr>                            
                            <th class="nowrap">N° BL :</th>
                            <td class="text-center"><?php echo $lot->getNum_bl() != '' ? $lot->getNum_bl() : '&mdash;'; ?></td>
                        </tr>
                    </table>                    
                   
                </div>

            </div>
        </div><!-- FIN ONGLET GENERAL -->
        

        <!-- ONGLET PRODUITS -->
        <div id="pdts" class="tab-pane fade" role="tabpanel">
    
        <table class="admin w-100 d-none d-lg-table">
                        <thead>
                    <tr>    
                        <th>Nom du produit</th>
                        <th>Nb de cartons</th>
                        <th>Poids</th>
                        <th>Quantite</th>
                        
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?php echo $lot->getNom_produit() ;?></td>
                        <td><?php echo $lot->getNb_cartons() ;?></td>
                        <td><?php echo $lot->getPoids() ;?></td>
                        <td><?php echo $lot->getQuantite() ;?></td>
                        
                    </tr>
                    </tbody>
                    </table>

        </div><!-- FIN ONGLET PRODUITS --> 

        <!-- ONGLET STOCK  -->
        <div id="stk" class="tab-pane fade" role="tabpanel">
			<?php			
			$poidsReceptionne = $lotsNegoceManager->getPoidsProduitLotNegoce($id_lot_pdt_negoce);
			
			$poidsExpedie = $lotsNegoceManager->getPoidsExpedie($id_lot_pdt_negoce);

			$poidsStock = $lotsNegoceManager->getPoidsRestantLotNegoce($id_lot_pdt_negoce) < 0 ? 0 : $lotsNegoceManager->getPoidsRestantLotNegoce($id_lot_pdt_negoce);				
			
			$poidsReceptionne = floatval($poidsReceptionne);
			$poidsExpedie = floatval($poidsExpedie);
						
			

			$ecart = $poidsReceptionne > 0 ? (($poidsStock / $poidsReceptionne) * 100) : 0;	

			
			$cssBadge = 'success';

            if ($ecart < 2.1 || $ecart < -2.1) {
                $cssBadge = 'success';
            } else if ($ecart == 0) {
                $cssBadge = 'secondary';
            } else if ($ecart > 50.1){
				$cssBadge = 'danger';
			}		
			
			?>
		<div class="alert alert-secondary">
                <div class="row">
                    <div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Total poids en stock</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo number_format($poidsStock,3,'.', ' '); ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>
                    <div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Total poids expédié</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo number_format($poidsExpedie,3,'.', ' '); ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>       
					<div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Poids receptionné</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo number_format($poidsReceptionne,3,'.', ' '); ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>

					<div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Ecart receptionné</p>
                        <span class="badge badge-<?php echo $cssBadge; ?>"><code class="gris-e5 text-22"><?php echo
                                                                                                            $poidsReceptionne > 0 ? number_format($ecart, 0, '.', '') : '-'; ?></code><span class="texte-fin text-14"> %</span></span>
                    </div>

                </div>
            </div>

			<?php

			global
			$BLigneManager;	
			$produitsStockExpedie = $BLigneManager->getProduitsNegoceProduitStock($id_lot_pdt_negoce);			
			
			if(empty($produitsStockExpedie)){?>
				<div class="text-center padding-15 text-24 gris-7">
					<p>Aucun produit n'a été expédié.</p>
		  		</div>
			<?php } else{
				$colspan = $utilisateur->isDev() ? 8 : 8;                
				?>
				<table class="admin w-100 table-lot-stock-expedies">
                <thead>
                <tr><th colspan="<?php echo $colspan ;?>" class="text-center bg-primary">Expédié</th></tr>
                <tr>					
                    <th class="text-right">Client</th>
                    <th class="text-right" >Produit</th>         
					<th class="text-right">Poids traitement</th>	        
                    <th class="text-right">Poids receptionné</th>									
                    <th class="text-right">Date</th>
                    <th class="text-right">DLC/DDM</th>
                    <th class="text-right">BL/BT</th>
                    <th class="text-right">Facture</th>
                </tr>
                </thead>
                <tbody>
					<?php 

						$orderPrestashopManager = new OrdersPrestashopManager($cnx);
						$tiersManager = new TiersManager($cnx);
						$id_client_web = $tiersManager->getId_client_web();?>					
					<!-- information pour le produit -->
					<tr>										
						<td ></td>						
						<td class="text-right"><?php echo $lot->getNom_produit();?></td>
						<td></td>
						<td class="text-right"><?php echo $lot->getPoids() != '' ? number_format($lot->getPoids(), 3, '.', ' ') . ' kg' : '-' ;?></td>
						<td class="text-right" ><?php echo Outils::dateSqlToFr($lot->getDate_reception()); ?></td>						
						<td class="text-right" ><?php echo Outils::dateSqlToFr($lot->getDlc()); ?></td>												
						<td></td>
						<td></td>
					</tr>				

					<?php 
							foreach ($produitsStockExpedie as $ligne) {								
								if (!$ligne instanceof BlLigne) {
									exit('un élément n\'est pas une instance de BlLigne');
								}
								?>
								<tr>														
								<td class="text-left"><?php echo $ligne->getLibelle();?></td>	
								<td></td>							
								<td class="text-right"><?php echo $ligne->getPoids() !='' ? number_format($ligne->getPoids(), 3, '.', ' ') . ' kg' : '-' ;?></td>
								<td></td>
								<td class="text-right"> <?php echo Outils::dateSqlToFr($ligne->getDate_add()) ;?></td>
								<td></td>
								
								<td  class="text-left">
								<a class="btn btn-link d-block p-0 text-left" target="_blank" href="gc-bls.php?i=<?php echo base64_encode($ligne->getId_bl()); ?>" class="text-info texte-fin text-13 d-block">
									<?php  ?><?php echo $ligne->getNum_bl() ;?></a>		
								</td>

								<td  class="text-left">
								<a class="btn btn-link d-block p-0 text-left" target="_blank" href="gc-factures.php?i=<?php echo base64_encode($ligne->getId_facture()); ?>" class="text-info texte-fin text-13 d-block">
									<?php  ?><?php echo $ligne->getNum_facture() ;?></a>		
								</td>
								
								</tr>
								<?php }
								


						?>						
				</tbody>
			</table>
			<?php
			}
			?>



        </div><!-- FIN ONGLET STOCK -->


    </div> <!-- FIN CONTENEUR ONGLETS -->

<?php
    exit;
} // FIN mode

