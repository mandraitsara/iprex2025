<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax VUE EMBALLAGES
------------------------------------------------------*/
ini_set('display_errors',1);

// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

$fonctionNom = 'mode'.ucfirst($mode);

if (function_exists($fonctionNom)) {
	$fonctionNom();
}


/* ------------------------------------------
MODE - Charge le contenu d'une étape de vue
-------------------------------------------*/
function modeChargeEtapeVue() {

	global $cnx, $utilisateur;

	$etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;

	/** ----------------------------------------
	 * DEV - On affiche l'étape pour débug
	 *  ----------------------------------- */
	if ($utilisateur->isDev()) { ?>
        <div class="dev-etape-vue"><i class="fa fa-user-secret fa-lg mr-1"></i>Etape <kbd><?php echo $etape;?></kbd></div>
	<?php } // FIN test DEV


	/** ----------------------------------------
	 * Etape        : 1
	 * Description  : Liste des emballages
	 *  ----------------------------------- */
	if ($etape == 1) {
		?>

        <div class="row mt-3 align-content-center">
            <div class="col text-center">
                <div class="alert alert-secondary">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Emballages disponibles :</h4>
                    <div class="row" id="containerListeEmballages">
						<?php
						// Fonction déportée pour pagination Ajax
						modeListeCartesEmballage();
						?>
                    </div>
                </div>
            </div>
        </div>

		<?php

		exit;
	} // FIN ETAPE



} // FIN charge Etape

function modeListeCartesEmballage() {

	global $cnx;

	$consommablesManager  = new ConsommablesManager($cnx);
	$vuesManager        = new VueManager($cnx);

	// Préparation pagination (Ajax)
	$nbResultPpage      = 10; // 16 pour 3 lignes
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode=listeCartesEmballage';
	$start              = ($page-1) * $nbResultPpage;

	$params = [
		'get_emb'           => true,
		'has_encours'       => true,
		'id_type'           => 1,
		'start'             => $start,
		'nb_result_page'    => $nbResultPpage
	];

	$famillesListe = $consommablesManager->getListeConsommablesFamilles($params);
	$nbResults  = $consommablesManager->getNb_results();
	$pagination = new Pagination($page);

	$pagination->setUrl($filtresPagination);
	$pagination->setNb_results($nbResults);
	$pagination->setAjax_function(true);
	$pagination->setNb_results_page($nbResultPpage);
	$pagination->setNb_apres(2);
	$pagination->setNb_avant(2);

	$nbPdtOnThePage = count($famillesListe);

	foreach ($famillesListe as $fam) { ?>

        <div class="col-2 mb-3">
            <div class="card bg-secondary text-white carte-emb" data-id-fam="<?php
                echo $fam->getId();?>" data-id-emb-encours="<?php
                echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : 0; ?>">

                <div class="card-header">
					<?php echo $fam->getCode(); 
						
					
					?>
                </div>

                <div class="card-body iprex">
                    <h5 class="card-title mb-0 <?php echo strlen($fam->getNom()) > 30 ? 'text-16' : 'text-18'; ?>"><?php echo $fam->getNom(); ?></h5>

                    <div class="badge badge-dark text-16 d-block margin-top-15 texte-fin">
                        <?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getNumlot_frs() : '?'; ?>
					    <span class="text-12 margin-top-5 texte-fin d-block"><?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getNom_frs() : ''; ?></span>

                        <hr class="mb-1 mt-1">
                        <div class="margin-5 texte-fin text-14">
                        <i class="fa fa-exclamation-triangle text-warning mr-2 alerte-zero-stock <?php
						if ($fam->getEmb_encours() instanceof Consommable) {
							echo $fam->getEmb_encours()->getStock_actuel() > 0 ? 'd-none' : '';
						} ?>"></i>
                        Stock restant : <span class="stock-restant"><?php echo $fam->getEmb_encours() instanceof Consommable ?  $fam->getEmb_encours()->getStock_actuel() : ''; ?></span>
                        </div>
                    </div>




                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-danger padding-20-10 border-light form-control btn-emb-defectueux" data-id-emb="<?php
							echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : '';?>"><i class="fa fa-exclamation-triangle fa-lg"></i></button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-dark padding-20 border-light form-control btn-emb-print" data-id-fam="<?php echo $fam->getId();?>"><i class="fa fa-print fa-lg"></i></button>
                        </div>
                        <div class="col mt-2">
                            <button type="button" class="btn btn-info padding-20 border-light form-control btn-emb-change" data-id-old-emb="<?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : ''; ?>"><i class="fa fa-retweet fa-lg"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

	<?php } // FIN boucle sur les emballages

	// Pagination (aJax)
	if (isset($pagination)) {

		// Si on a moins de 3 blocs de libres à droite, on va a la ligne
		$nbCasse = [4,5,10,11,16,17];
		if (in_array($nbPdtOnThePage,$nbCasse)) { ?>
            <div class="clearfix"></div>
		<?php }

		$pagination->setNature_resultats('emballage');
		$pagination->setCardFooterButtonClasses('btn btn-secondary padding-20-10 border-light form-control');
		echo ($pagination->getPaginationBlocs());

	} // FIN test pagination

} // FIN mode


function modeChargeTicketLot() {

	global $cnx, $utilisateur;

	$etape          = isset($_REQUEST['etape'])     ? intval($_REQUEST['etape'])    : 0;
	$identifiant    = isset($_REQUEST['id'])        ? $_REQUEST['id']               : '';

	$na  = '<span class="badge badge-warning badge-pill text-14">Non renseigné !</span>';
	$err = '<span class="badge danger badge-pill text-14">ERREUR !</span>';

	if ($etape > 1) {
		$consommablesManager = new ConsommablesManager($cnx);
	}

		// ETAPE 1 - Emballages
	if ($etape == 1) { 
		
		?>
        <h4 class="mb-3">Emballages défectueux</h4>
        <table id="ticketLotInfoStock" class="table-suivi-stock-emb"><?php showEmballageProd(); ?></table>
        <h4 class="mb-3 mt-3">Changements de rouleaux</h4>
        <table id="ticketLotInfoStockD" class="table-suivi-stock-emb"><?php showEmballageChang(); ?></table>
	<?php

	} // FIN ETAPE


} // FIN mode


// Fonction déportée pour mise à jour du détail des rouleaux d'emballage : defectueux
function showEmballageProd() {

	global $cnx, $consommablesManager;

	if (!$consommablesManager instanceof ConsommablesManager) {
		$consommablesManager = new ConsommablesManager($cnx);
	}


	$defectueux = $consommablesManager->getEmballagesDefectueuxJour();

	if (!$defectueux || empty($defectueux)) { ?>
        <tr>
            <td colspan="2">Aucun défectueux aujourd'hui</td>
        </tr>
	<?php }

	foreach ($defectueux as $donnees) {

		?>
        <tr>
            <td><?php echo $donnees['nom']; ?></td>
            <td class="text-right hook-badge-emb-ticket">
                <span class="badge badge-danger badge-pill text-16"><?php echo $donnees['qte']; ?></span>
            </td>
        </tr>
		<?php
	} // FIN boucle

    return true;

} // FIN fonction


// Fonction déportée pour mise à jour du détail des rouleaux d'emballage : changements
function showEmballageChang() {

	global $cnx, $consommablesManager;

	// On récupère les changements de rouleaux du jour pour ce lot
	$changements = $consommablesManager->getEmballagesChangementRouleauJour();

	if (!$changements || empty($changements)) { ?>
        <tr>
            <td colspan="2">Aucun changement aujourd'hui</td>
        </tr>
	<?php }

	foreach ($changements as $donnees) {

		?>
        <tr>
            <td><?php echo $donnees['nom']; ?></td>
            <td class="text-right hook-badge-emb-ticket">
                <span class="badge badge-info badge-pill text-16"><?php echo $donnees['nb']; ?></span>
            </td>
            <!--<td colspan="2"><?php // echo $donnees['nom']; ?>
				<br>
                <i class="fa fa-retweet"></i><span class="text-info ml-1 mr-1"><?php // echo $donnees['precedent'] ?></span> <i class="fa fa-long-arrow-alt-right"></i><span class="text-info ml-1 mr-1"><?php  // echo $donnees['actuel'] ?></span>
            </td>-->
        </tr>
		<?php

	} // FIN boucle

	return true;


} // FIN fonction

