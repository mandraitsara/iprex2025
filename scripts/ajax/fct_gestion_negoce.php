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

$na = '<i class="far fa-question-circle text-danger fa-lg"></i>';


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

	//$params['statut'] = $statut;
	//$params['get_nb_traites'] = true;


	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;
	$params['order'] 			= 'id';

	$listeLots = $lotsNegoceManager->getListeNegoceLots($params);    
	
	// Si aucun lot
	if (empty($listeLots)) { ?>

		<div class="alert alert-secondary text-center">
			<i class="far fa-clock mb-2 mt-2 fa-5x"></i> <p class="text-24 mb-0">Aucun lot&hellip;</p>
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
                <th>Numéro de lot</th>				
				<th>Fournisseur</th>
				<th>Date de réception</th>
				<th>Nom du produit</th>			              
				<th>DLC/DDM</th>			              
				<th>Nb de cartons</th>			              
				<th>Quantite</th>			              
				<?php if ($statut == 1) {?>						                             
				<th class="text-center nowrap">Poids réceptionné</th>				                
                <?php } ?>				
				<th class="t-actions w-mini-admin-cell text-center">Détails</th>
			</tr>
			</thead>
			<tbody>
			<?php

			$na = '<i class="far fa-question-circle text-danger fa-lg"></i>';
			$btnPoidsReception = '<button type="button" class="btn btn-sm btn-secondary btnPoidsReceptionLot margin-right-50"><i class="fa fa-weight"></i></button>';

			foreach ($listeLots as $lot) {?>
            
                <tr>
                <td class="text-22"><?php echo $lot->getNum_bl(); ?></td>
                <td class="text-22"><?php echo $lot->getNum_lot(); ?></td>
                <td><?php echo $lot->getFournisseur(); ?></td>
                <td><?php echo Outils::dateSqlToFr($lot->getDate_reception()) !=="" ? Outils::dateSqlToFr($lot->getDate_reception()) : '&mdash;'; ?></td>
                
                <td class="text-22"><?php echo $lot->getNom_produit(); ?></td>

                <td><?php echo Outils::dateSqlToFr($lot->getDlc()) !=="" ? Outils::dateSqlToFr($lot->getDlc()) : '&mdash;'; ?></td>
                <td><?php echo $lot->getNb_cartons() !=="" ? $lot->getNb_cartons() : '&mdash;'; ?></td>                
                <td><?php echo $lot->getQuantite() !=="" ? $lot->getQuantite() : '&mdash;'; ?></td> 
                <td>
                    <?php
                    $poidsReception = $lot->getPoids() > 0 ? number_format($lot->getPoids(),2,'.',' ') . ' Kg' : $na;
                    echo $poidsReception;
                    ?>
                </td>                
                
              <td class="t-actions w-mini-admin-cell"><button type="button" class="btn  btn-secondary" data-toggle="modal" data-target="#modalLotInfo" data-lot-id="<?php
 						echo $lot->getId_lot_pdt_negoce(); ?>"><i class="fa fa-ellipsis-h"></i> </button>
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


// Modif / supprime un produit négoce d'un lot de négoce (modale admin)
function modeUpdPdtNegoce() {

	global $cnx, $lotsNegoceManager, $logsManager;

	$id_pdt_negoce = isset($_REQUEST['id_pdt_negoce']) ? intval($_REQUEST['id_pdt_negoce']) : 0;
	$nb_cartons = isset($_REQUEST['nb_cartons']) ? intval($_REQUEST['nb_cartons']) : 0;
	$quantite = isset($_REQUEST['quantite']) ? intval($_REQUEST['quantite']) : 0;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0.0;	
	$num_lot = isset($_REQUEST['num_lot']) ? trim($_REQUEST['num_lot']) : '';
    $numero_palette =  isset($_REQUEST['numero_palette']) ? trim($_REQUEST['numero_palette']) : '';

	
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
        $palettesManager = new PalettesManager($cnx);
		$id_palette = $palettesManager->getLastIdPaletteByNumero($numero_palette);
        $pdtNegoce->setId_palette($id_palette);
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


function modeSupprPdtLotNegoce()
{
	global $cnx, $lotsNegoceManager;
	$id_lot_pdt_negoce = isset($_REQUEST['id_lot_pdt_negoce']) ? intval($_REQUEST['id_lot_pdt_negoce']) : 0;
	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;	

	if ($id_lot_pdt_negoce == 0 || $id_lot == 0) { exit("ERREUR - Identification du ProduitNegoce impossible ! Code erreur : UGV6N9C8"); }
	
	$lotsNegoceManager->supprLotProduitNegoce($id_lot_pdt_negoce);

	modeListeProduitsLotNegoce($id_lot);
	exit;
}

/* --------------------------------------
MODE - Modale détails info du lot
--------------------------------------*/
function modeModalLotInfo()
{

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
        <div id="general" class="tab-pane fade show active" role="tabpanel" data-id-lot="<?php echo $lot->getId_lot_pdt_negoce(); ?>">

            <div class="row">
                <div class="col-3 margin-top-10 ">
                    <div class="alert alert-dark text-center">
                        <h2 <?php echo strlen($lot->getNum_lot()) > 10 ? 'class="text-26"' : ''; ?>><?php echo $lot->getNum_lot(); ?></h2>                    

                    </div>
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
                        <th >Nom du produit</th>
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
			$poidsStock = $lotsNegoceManager->getPoidsRestantLotNegoce($id_lot_pdt_negoce);
			$total_poids_stk_exp = (floatval($poidsReceptionne) + floatval($poidsExpedie));


			$total_poids_stk_exp = $poidsStock + $poidsExpedie;
            $ecart = $poidsReceptionne > 0 ? (((floatval($poidsReceptionne)  - floatval($poidsExpedie)) * 100) / $poidsReceptionne) * 1 : 0;        

            $cssBadge = 'success';

            if ($ecart > 2.1 || $ecart < -2.1) {
                $cssBadge = 'danger';
            } else if ($ecart == 0) {
                $cssBadge = 'secondary';
            }


			$ecartReceptionne = 2;
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
                        <p class="texte-fin text-12 nomargin">Total poids stock + expédié</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo number_format($total_poids_stk_exp,3,'.', ' '); ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>    
					
					<div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Poids receptionné</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo number_format($poidsReceptionne,3,'.', ' '); ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>

					<div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Ecart receptionné</p>
                        <span class="badge badge-<?php echo $cssBadge; ?>"><code class="gris-e5 text-22"><?php echo
                                                                                                            $poidsReceptionne > 0 ? number_format($ecart, 1, '.', '') : '-'; ?></code><span class="texte-fin text-14"> %</span></span>
                    </div>

                </div>
            </div>

			<?php

			$produitsStockExp = $lotsNegoceManager->getProduitsNegoceProduitStock($id_lot_pdt_negoce);
			

			if(empty($produitsStockExp)){?>
				<div class="text-center padding-15 text-24 gris-7">
					<p>Aucun produit en stock.</p>
		  		</div>
			<?php } else{
				$colspan = $utilisateur->isDev() ? 9 : 8;
                $colDev = $utilisateur->isDev() ? '<th class="w-100px"><i class="fa fa-user-secret mr-1"></i>Compo</th>' : '';
				?>
				<table class="admin w-100 table-lot-stock-expedies">
                <thead>
                <tr><th colspan="<?php echo $colspan ;?>" class="text-center bg-primary">Expédié</th></tr>
                <tr>
                    <th class="text-right">Client</th>
                    <th class="text-right" >Produit</th>                    
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
						<td class="text-right"><?php echo $produitsStockExp->getNom_produit();?></td>
						<td class="text-right"><?php echo $produitsStockExp->getPoids() != '' ? number_format($produitsStockExp->getPoids(), 3, '.', ' ') . ' kg' : '-' ;?></td>
						<td class="text-right" ><?php echo Outils::dateSqlToFr($produitsStockExp->getDate_reception()); ?></td>						
						<td class="text-right" ><?php echo Outils::dateSqlToFr($produitsStockExp->getDlc()); ?></td>												
						<td></td>
						<td></td>
					</tr>
					<!-- fin pour l'information du produit -->


					<!-- information pour les clients -->
					<tr>						
						<td class="text-right w20"><?php echo $produitsStockExp->getnom_client() ;?></td>
						<td class="text-right w5 "><span>poids clients : </span></td>
						<td class="text-right"><?php echo $produitsStockExp->getPoids_clients() !='' ? number_format($produitsStockExp->getPoids_clients(), 3, '.', ' ') . ' kg' : '-' ; ;?></td>
						<td></td>
						<td></td>
						<td  class="text-left">
						<a class="btn btn-link d-block p-0 text-left" target="_blank" href="gc-bls.php?i=<?php echo base64_encode($produitsStockExp->getId_bl()); ?>" class="text-info texte-fin text-13 d-block">
							<?php echo $produitsStockExp->getNumero_bl(); ?></a>		
						</td>
						<td class="text-left">
						<a class="btn btn-link d-block p-0 text-left" target="_blank" href="gc-factures.php?i=<?php echo base64_encode($produitsStockExp->getId_facture()); ?>" class="text-info texte-fin text-13 d-block">
							<?php echo $produitsStockExp->getNum_facture(); ?></a>
						</td>
					</tr>

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


/* -----------------------------------------------------------------------
MODE/FONCTION INTERNE - Liste produit détail lot (call include + pagination)
-----------------------------------------------------------------------*/
function modeShowListeProduitsDetailsLot($id_lot = 0)
{

	global $cnx, $lotsNegoceManager,$mode,$na;
	

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

	$listeProduits = $lotsNegoceManager->getListeNegoceProduits($params);
	
   // Aucun Produit
    if (empty($listeProduits)) { ?>
        <div class="text-center padding-50 text-24 gris-7">
            <i class="fa fa-box-open fa-lg mr-1"></i>
            <p>Aucun produit&hellip;</p>
        </div>
    <?php
        // Liste des produits
    } else { ?>

        <table class="admin w-100 table-striped">
            <thead>
                <tr>
                    <th class="text-left">Désignation</th>
                    <th class="text-left">Numero lot </th>
					<th class="text-left">DLC/DDM</th>
                    <th class="text-left">Nb de cartons</th>
                    <th class="text-left">Poids</th>
                    <th class="text-left">Quantite</th>
                    <th class="text-left">Traité</th>
                    <th class="text-left">BL sortant</th>
				</tr>
            </thead>
            <tbody>
                <?php                

                foreach ($listeProduits as $pdtlot) {?>
					<tr>
						<td class="text-left" ><?php echo $pdtlot->getNom_produit(); ?></td>
						<td class="text-left" ><?php echo $pdtlot->getNum_lot(); ?></td>
						<td class="text-left" ><?php echo Outils::dateSqlToFr($pdtlot->getDlc()); ?></td>
						<td class="text-left" ><?php echo $pdtlot->getNb_cartons(); ?></td>
						<td class="text-left" ><?php echo $pdtlot->getPoids(); ?></td>
						<td class="text-left" ><?php echo $pdtlot->getQuantite(); ?></td>						
						<td class="text-left" ><i class="fa fa-fw fa-<?php echo $pdtlot->getTraite() == 1 ? 'check text-success' : 'hourglass-half gris-9'; ?> mr-1"></i> <?php echo $pdtlot->getTraite() == 1 ? 'Oui' : 'Non'; ?></td>					
                        <td class="text-center">
				
                        <?php
                        
                if ($pdtlot->getId_bl() == 0) { ?>
                    <i class="fa fa-times gris-9"></i>
				<?php } else { ?>					
					<a href="gc-bls.php?i=<?php echo base64_encode($pdtlot->getId_bl()); ?>" class="text-info texte-fin text-13 d-block"><?php echo $pdtlot->getNum_lot(); ?></a>
				<?php }
                ?>
              </td>
					</tr>
                <?php
                } // FIN hors stock
                ?>
            </tbody>
        </table>

		<table  class="table-striped">
			<tr>
				<th>Nombres produits traité : </th>
				<td>
					<?php 
						echo $lotsNegoceManager->getNbProduitsTraitesByLot($lot);
					?>
				</td>
			</tr>
			<tr>
				<th>Nombres produits non traité : </th>
				<td>
				<?php
					$produitsTotal = $lotsNegoceManager->getNbProduitsByLot($lot) ? intval($lotsNegoceManager->getNbProduitsByLot($lot)) : 0;
					$produitsTraite = $lotsNegoceManager->getNbProduitsTraitesByLot($lot) ? intval($lotsNegoceManager->getNbProduitsTraitesByLot($lot)) : 0;
					echo $prodtuisRestant = intval($produitsTotal) - intval($produitsTraite);
				?>
				</td>
			</tr>
			<tr>
				<th>Nombres produits Total : </th>
				<td>
					<?php 
					echo $produitsTotal = $lotsNegoceManager->getNbProduitsByLot($lot) ? intval($lotsNegoceManager->getNbProduitsByLot($lot)) : 0;
					?>
				</td>
			</tr>
			
		</table>        
    <?php


    } // FIN test produits


	

} // FIN fonction
