<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax Validations
------------------------------------------------------*/
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$logsManager = new LogManager($cnx);
$lotsManager = new LotManager($cnx);
$validationsManager = new ValidationManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------
MODE - Modal détails validation
------------------------------------*/
function modeModalValidationDetails() {

	global
        $cnx,
        $validationsManager;

    // Gestion du retour d'erreur
    function erreurRecup() { ?>
        <div class="alert alert-danger text-center">
            <i class="fa fa-exclamation-triangle fa-lg mb-1"></i>
            <p>Erreur lors de la récupération des données !</p>
        </div>
        <?php exit;
    } // FIN retour en cas d'erreur

    $validation_id     = isset($_REQUEST['validation_id']) ? intval($_REQUEST['validation_id']) : 0;



    if ($validation_id == 0) { erreurRecup(); }

    $validation = $validationsManager->getValidation($validation_id);
    if (!$validation instanceof Validation) { erreurRecup(); }

	$froidManager = new FroidManager($cnx);



    ?>
        <div class="row">
            <div class="col-4">
                <div class="alert alert-secondary text-center">
                    <h2><?php

                       foreach ($validation->getLots() as $lotval) { ?>
                            <div><?php
								echo $validation->getType() != 4 ? $lotval->getNumlot() : 'NEGOCE ID'.$lotval->getID();
                             ?></div>
                        <?php } ?>
                    </h2>
                    <span class="badge badge-info text-20 form-control"><?php echo $validation->getVue()->getNom() ?></span>
                </div>

                <div class="alert alert-<?php
				if          ($validation->getResultat() == 1) { echo 'success';
				} else if   ($validation->getResultat() == 0) { echo 'danger';
				} else      { echo 'warning'; };?> text-center">
                    <h3><i class="fa fa-<?php echo $validation->getResultat() == 1 ? 'check' : 'exclamation-triangle'; ?> mr-2"></i><?php echo  $validation->getResultat_verbose(); ?></h3>
                </div>
            </div>
            <div class="col-8">
                <div class="row">
                    <div class="col-12 col-xl-8">
                        <span class="texte-fin text-12 mr-1">Date et heure du contrôle :</span>

						<?php echo $validation->getDate() != '' ? ucfirst(Outils::getDate_verbose($validation->getDate())) : '&mdash;'; ?>
                    </div>
                    <div class="col-12 col-xl-4 text-right">
                        <span class="texte-fin text-12 mr-1">Code du traitement :</span>
						<kbd class="bg-secondary"><?php

                            switch ($validation->getType()) {
                                case 1: echo 'RCP'.sprintf("%04d", $validation->getId_liaison());break;
                                case 2:
                                    $froid = $froidManager->getFroid($validation->getId_liaison());
                                    if ($froid instanceof Froid) {
                                        echo strtoupper($froid->getCode()).sprintf("%04d", $validation->getId_liaison());
                                    }
                                    break;
                                case 3:
                                    echo 'L'. sprintf("%03d", $validation->getId_liaison());
                                    break;
								case 4:
									echo 'NEG'. sprintf("%03d", $validation->getId_liaison());
									break;
                            }

                            ?></kbd>
                    </div>
                </div>
                <table class="table w-100 table-border text-14 mt-3 table-v-middle table-padding-4-8">
                    <?php
					/** ***************************
					 * Réception
					 *************************** */
					if ($validation->getType() == 1 && $validation->getReception() instanceof LotReception) {

						$lotRecepetion = $validation->getLots()[0];
					    ?>

                        <tr>
                            <th class="nowrap">Poids :</th>
                            <td class="text-center"><?php
                                if (isset($validation->getLots()[0]) && $validation->getLots()[0] instanceof Lot) {

									echo $lotRecepetion->getPoids_reception() != ''
										? '<code class="text-dark text-20">'.number_format($lotRecepetion->getPoids_reception(),3,'.', '') . '</code> Kgs'
										: '-';
                                } else {
                                    echo '-';
                                }
                                ?></td>
                            <th class="nowrap">Etat visuel :</th>
                            <td class="text-center text-white bg-<?php echo $validation->getReception()->getEtat_visuel() == 1 ? 'success' : 'danger'; ?>">
                                <?php echo $validation->getReception()->getEtat_visuel_verbose(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="nowrap">Composition :</th>
                            <td class="text-center"><?php echo $lotRecepetion->getComposition_verbose(); ?></td>
                            <th class="nowrap">Température<?php echo $lotRecepetion->getComposition() == 1 ? 's' : ''; ?> :</th>
                                <?php
								if ( $lotRecepetion->getReception() instanceof LotReception) {
									// Abats (une seule température
									if ($lotRecepetion->getComposition() == 2) { ?>

                                        <td class="text-center"><code class="text-dark text-20"><?php echo $lotRecepetion->getReception()->getTemp(); ?></code><span class="gris-7 text-14">°C</span></td>

                                    <?php }	else { ?>
                                        <td>
                                            <ul class="no-margin">
                                                <li>
                                                    <span class="badge badge-info badge-pill badge-dmf text-14">D</span><code class="text-dark text-20"><?php
                                                        echo $lotRecepetion->getReception()->getTemp_d(); ?></code><span class="gris-7 text-14">°C</span>
                                                </li>
                                                <li>
                                                    <span class="badge badge-info badge-pill badge-dmf text-14">M</span><code class="text-dark text-20"><?php
                                                        echo $lotRecepetion->getReception()->getTemp_m(); ?></code><span class="gris-7 text-14">°C</span>
                                                </li>
                                                <li>
                                                    <span class="badge badge-info badge-pill badge-dmf text-14">F</span><code class="text-dark text-20"><?php
                                                        echo $lotRecepetion->getReception()->getTemp_f(); ?></code><span class="gris-7 text-14">°C</span>
                                                </li>
                                            </ul>
                                        </td>
                                        <?php
									} // FIN test type composition
								} // Fin test objet Réception
								?>
                        </tr>
                        <tr>
                            <th class="nowrap">Crochets :</th>
                            <td class="text-center">


                                    <?php if ($lotRecepetion->getReception()->getCrochets_recus() == 0 && $lotRecepetion->getReception()->getCrochets_rendus() == 0) { echo "&mdash;";
					                } else { ?>
                                        <span class="badge badge-secondary mr-1 text-16 badge-pill texte-fin">
                                        <?php $ecart = $lotRecepetion->getReception()->getCrochets_recus() - $lotRecepetion->getReception()->getCrochets_rendus();
					                echo $ecart > 0 ? '+' : '';	echo $ecart;
					                ?></span>

					                <?php } ?>
								    <?php if ($lotRecepetion->getReception()->getCrochets_recus() > 0 || $lotRecepetion->getReception()->getCrochets_rendus() > 0) { ?>
                                    <i class="fa fa-download fa-fw gris-9"></i><?php echo $lotRecepetion->getReception()->getCrochets_recus(); ?>
                                    <i class="fa fa-upload fa-fw gris-9 ml-2"></i><?php echo $lotRecepetion->getReception()->getCrochets_rendus();
								} ?>
                            </td>
                            <th class="nowrap">Transporteur :</th>
                            <td class="text-center"><?php echo $lotRecepetion->getReception()->getNom_transporteur() == '' ? "&mdash;" : $lotRecepetion->getReception()->getNom_transporteur(); ?></td>

                        </tr>
                     <?php

					$controleurNom = $lotRecepetion->getReception()->getUser_nom();

                    /** ***************************
                     * Froid
                     *************************** */
                    } else if ($validation->getType() == 2) {

						$produitsManager = new ProduitManager($cnx);
						$froid = $froidManager->getFroid($validation->getId_liaison());

					    ?>
                        <tr>
                            <th class="nowrap">Machine :</th>
                            <td class="text-center" colspan="3"><?php
								switch(trim(strtolower($validation->getVue()->getCode()))) {
									case 'cgl':
										echo 'Tunnel de congélation';
										break;
									case 'srgv':
										echo 'Surgélateur vertical';
										break;
									case 'srgh':
										echo 'Surgélateur horizontal';
										break;
									case 'hac':
										echo 'Hachoir';
										break;
									default:
										echo 'N/A';
								}
                                ?></td>
                            <th class="nowrap">Produits :</th>
                            <td class="text-center text-22"><?php echo $produitsManager->getNbProduitsFroid($validation->getId_liaison()); ?></td>
                        </tr>
                        <tr>
                            <th class="nowrap">Entrée :</th>
                            <td class="text-center"><?php echo Outils::getDate_verbose($froid->getDate_entree(), false, '<br>'); ?></td>
                            <th class="nowrap">Sortie :</th>
                            <td class="text-center"><?php echo Outils::getDate_verbose($froid->getDate_sortie(), false, '<br>'); ?></td>
                            <th class="nowrap">Durée (H) :</th>
                            <td class="text-center"><?php echo $froid->getTempsFroid(); ?></td>
                        </tr>
                        <tr>
                            <th class="nowrap">Temp. début :</th>
                            <td class="text-center"><?php echo number_format($froid->getTemp_debut(),1,'.', ''); ?>°C</td>
                            <th class="nowrap">Temp. fin :</th>
                            <td class="text-center"><?php echo number_format($froid->getTemp_fin(),1,'.', ''); ?>°C</td>
                            <th class="nowrap">Delta T° :</th>
                            <td class="text-center"><?php echo number_format($froid->getTemp_fin() - $froid->getTemp_debut(),1,'.', ''); ?>°C</td>
                        </tr>

    			    	<?php
						$userManager = new UserManager($cnx);
                        $controleurNom = $userManager->getUser($froid->getId_visa_controleur())->getNomComplet();

                    /** ***************************
                     * Loma
                     *************************** */
					} else if ($validation->getType() == 3 && $validation->getLoma() instanceof Loma) {


					    ?>

                        <tr>
                            <th class="nowrap">Produit testé :</th>
                            <td class="text-center"><?php echo $validation->getLoma()->getNom_produit(); ?></td>
                            <th class="nowrap">Résultat :</th>

                            <td class="text-white nowrap bg-<?php echo $validation->getLoma()->getTest_pdt() == 0 ? 'success' : 'danger'; ?>">
                                <i class="fa fa-<?php echo $validation->getLoma()->getTest_pdt() == 0 ? 'check' : 'times'; ?> mr-2 fa-fw"></i><span class="texte-fin">OK</span>
                            </td>
                        </tr>


						<?php

						$controleurNom = $validation->getLoma()->getNom_user();

                    /** ***************************
                     * Réception Négoce
                     *************************** */
					} else if ($validation->getType() == 4 && $validation->getLot_negoce() instanceof LotNegoce) {

					    ?>
                        <tr>
                            <th class="nowrap">Température :</th>                        
                            <td class="text-center"><code class="text-dark text-20"><?php echo $validation->getLot_negoce()->getTemp(); ?></code><span class="gris-7 text-14">°C</span></td>
                        </tr>
                        <?php
						$controleur_id = $validation->getLot_negoce()->getId_user_maj();
						$userManager = new UserManager($cnx);
						$controleur = $userManager->getUser($controleur_id);
						$controleurNom = $controleur->getNomComplet();


                    /** ***************************
                     * Erreur quelque part :(
                     *************************** */
					} else {
						$controleurNom = 'N/A';
					    ?>
                        <tr>
                            <td class="bg-danger">Erreur de récupération des données...</td>
                        </tr>
					<?php
					} // FIN test type de retour
                    ?>
                </table>

<?php

if ($validation->getType() == 1 && $validation->getReception() instanceof LotReception) {


    ?>
    <input type="checkbox" class="togglemaster" data-toggle="toggle" id="updConformite"
		<?php echo $validation->getReception()->getConformite() == 1 ? 'checked' : ''; ?>
           data-on="Conforme"
           data-off="Non conforme"
           data-onstyle="success"
           data-offstyle="danger"
    />

    <?php



}

// Cycle de nuit / we pour froid
if ($validation->getType() == 2 && $froid->getNuit() > 0) {

    $dt = new DateTime($froid->getDate_entree());
    $cycleWe = intval($dt->format('w')) == 5;
    $typeCycle = $cycleWe ? 'week-end' : 'nuit';
    ?>

    <div class="row">
        <div class="col-5">
            <span class="badge badge-info text-20"><i class="fa fa-moon mr-1"></i> Cycle de <?php echo $typeCycle; ?></span>
        </div>
        <div class="col-7 text-right">
			<?php
			// Si on est en cycle de we, il faut valider le cycle de we
			if ($cycleWe) { ?>

                <p>
                    <label>Courbe de température :</label>
                    <input type="checkbox" class="togglemaster switch-courbe-temp"
                           checked
                           data-toggle      = "toggle"
                           data-on          = "Correcte"
                           data-off         = "Anormale"
                           data-onstyle     = "success"
                           data-offstyle    = "danger"
                           data-id-val      = "<?php echo $validation->getId(); ?>"/>
                </p>

			<?php } // FIN test cycle de we
			?>

        </div>
    </div>





<?php } // FIN test type froid pour gestion des cycles de nuit/we


$observations = '';
if ($validation->getType() == 1 && $validation->getReception() instanceof LotReception) {
    $observations = $validation->getReception()->getObservations();
} else if ($validation->getType() == 3 && $validation->getLoma() instanceof Loma) {
    $observations = $validation->getLoma()->getCommentaire();
}

if ($observations != '') { ?>
 <h4 class="text-16 mb-1">Observations :</h4>
                <p class="alert alert-secondary text-14"><?php echo $observations;?></p>
    <?php }
?>


            </div>
        </div>

	<?php
	echo '^'; // Séparateur Body / Footer ?>


    <div class="float-left padding-top-5"><i class="fa fa-user gris-c fa-sm mr-1"></i>
            <span class="texte-fin text-12">Opérateur :</span>
            <span class="text-14 gris-3"><?php echo $controleurNom; ?></span>
    </div>
    <div class="text-right">
        <button type="button" class="btn btn-success btnValiderFromModale" data-id-validation="<?php echo $validation->getId(); ?>"><i class="fa fa-check mr-2"></i>Valider</button>
    </div>



    <?php

	exit;

	/** ***************************
	 * Contrôle LOMA
	 *************************** */

	if ($validation->getType() == 'l') {

        if (!$lot->getDetailsLoma() instanceof DetailsLoma)  { erreurRecup(); } // Si pas instanciation de l'objet du test
        if ($lot->getDetailsLoma()->getTest_resultat() == 0) { erreurRecup(); } // Si pas d'info sur le résultat du test
        ?>

    <div class="row">
        <div class="col-5">
            <div class="alert alert-secondary text-center">
                <h2><?php echo $lot->getNumlot(); ?></h2>
                <span class="badge badge-info text-20"><?php echo $types_val[$type_val]; ?></span>
            </div>

            <div class="alert alert-<?php echo $lot->getDetailsLoma()->getTest_resultat() == 1 ? 'success' : 'danger';?> text-center">
                <h3><i class="fa fa-<?php echo $lot->getDetailsLoma()->getTest_resultat() == 1 ? 'check' : 'exclamation-triangle'; ?> mr-2"></i><?php echo $lot->getDetailsLoma()->getTest_resultat() == 1 ? 'DÉTECTÉ' : 'NON DÉTECTÉ'; ?></h3>
            </div>
        </div>
        <div class="col-7">

            <table class="table w-100 table-border text-14">
                <tr>
                    <th colspan="2">Référence du contrôle :</th>
                    <td colspan="3"><code>LOMA-ID/<?php echo $lot->getDetailsLoma()->getId(); ?></code></td>
                </tr>
                <tr>
                    <th colspan="2">Date et heure du test :</th>
                    <td colspan="3"><?php echo $lot->getDetailsLoma()->getDate_test() != '' ? ucfirst(Outils::getDate_verbose($lot->getDetailsLoma()->getDate_test())) : '&mdash;'; ?></td>
                </tr>
                <tr>
                    <th colspan="2">Client :</th>
                    <td colspan="3"><?php echo $lot->getDetailsLoma()->getNom_client(); ?></td>
                </tr>
                <tr>
                    <th colspan="2">Produit :</th>
                    <td colspan="3"><?php echo $lot->getDetailsLoma()->getNom_produit(); ?></td>
                </tr>
                <tr>
                    <th class="v-middle">Condionnement <i class="fa fa-caret-right v-middle gris-c pl-2"></i></th>
                    <th class="text-center v-middle">Début :</th>
                    <td class="text-center text-18 v-middle"><?php echo Outils::getHeureOnly($lot->getDetailsLoma()->getCond_debut()); ?></td>
                    <th class="text-center v-middle">Fin :</th>
                    <td class="text-center text-18 v-middle"><?php echo Outils::getHeureOnly($lot->getDetailsLoma()->getCond_fin()); ?></td>
                </tr>
                <tr>
                    <th colspan="2">Responsable du contrôle :</th>
                    <td colspan="3"><?php echo $lot->getDetailsLoma()->getNom_controleur(); ?></td>
                </tr>
            </table>
            </div>
        </div>
        <?php
        // Si commentaire...
        if ($lot->getDetailsLoma()->getCommentaire() != '') { ?>

            <div class="row">
                <div class="col">
                    <div class="alert alert-secondary mb-0">
                        <?php echo nl2br($lot->getDetailsLoma()->getCommentaire()); ?>
                    </div>
                </div>
            </div>
        <?php } // FIN commentaire Loma

		echo '^'; // Séparateur Body / Footer ?>

        <button type="button" class="btn btn-success btnValiderFromModale" data-id-table="<?php echo $lot->getDetailsLoma()->getId(); ?>" data-type-val="<?php echo $type_val; ?>"><i class="fa fa-check mr-2"></i>Valider</button>

		<?php


    /** ***************************
     * Surgélation / Congélation
	 *************************** */

    } else if ($type_val == 's' || $type_val == 'c') {

		$lot = $lotsManager->getDetailsFroidLot($lot);

		if (!$lot->getDetailsFroid() instanceof DetailsFroid)  { erreurRecup(); } // Si pas instanciation de l'objet du traitement froid
		if ($lot->getDetailsFroid()->getConforme() == 0) { erreurRecup(); }       // Si pas d'info sur la conformité

        ?>

        <div class="row">
            <div class="col-5">
                <div class="alert alert-secondary text-center">
                    <h2><?php echo $lot->getNumlot(); ?></h2>
                    <span class="badge badge-info text-20"><?php echo $types_val[$type_val]; ?></span>
                </div>

                <div class="alert alert-<?php echo $lot->getDetailsFroid()->getConforme() == 1 ? 'success' : 'danger';?> text-center">
                    <h3><i class="fa fa-<?php echo $lot->getDetailsFroid()->getConforme() == 1 ? 'check' : 'exclamation-triangle'; ?> mr-2"></i><?php echo $lot->getDetailsFroid()->getConforme() == 1 ? '' : 'NON'; ?> CONFORME</h3>
                </div>
            </div>
            <div class="col-7">
                <table class="table w-100 table-border text-14">
                    <tr>
                        <th colspan="2">Référence du traitement :</th>
                        <td colspan="3"><code>FROID-ID/<?php echo $lot->getDetailsFroid()->getId(); ?></code></td>
                    </tr>
                    <tr>
                        <th colspan="2">Date et heure du contrôle :</th>
                        <td colspan="3"><?php echo $lot->getDetailsFroid()->getDate_controle() != '' ? ucfirst(Outils::getDate_verbose($lot->getDetailsFroid()->getDate_controle())) : '&mdash;'; ?></td>
                    </tr>
                    <tr>
                        <th colspan="2">Entrée <?php echo $type_val == 's' ? 'dans le surgelateur' : 'en tunel'; ?> :</th>
                        <td colspan="3"><?php echo $lot->getDetailsFroid()->getDate_entree() != '' ? ucfirst(Outils::getDate_verbose($lot->getDetailsFroid()->getDate_entree())) : '&mdash;'; ?></td>
                    </tr>
                    <tr>
                        <th colspan="2">Sortie du <?php echo $type_val == 's' ? 'surgelateur' : 'tunel'; ?> :</th>
                        <td colspan="3"><?php echo $lot->getDetailsFroid()->getDate_sortie() != '' ? ucfirst(Outils::getDate_verbose($lot->getDetailsFroid()->getDate_sortie())) : '&mdash;'; ?></td>
                    </tr>
                    <tr>
                        <th class="v-middle">Température <i class="fa fa-caret-right v-middle gris-c pl-2"></i></th>
                        <th class="text-center v-middle">Début :</th>
                        <td class="text-center text-18 v-middle"><?php echo $lot->getDetailsFroid()->getTemp_debut() != '' ? $lot->getDetailsFroid()->getTemp_debut() . '&deg;C' : '&mdash;'; ?></td>
                        <th class="text-center v-middle">Fin :</th>
                        <td class="text-center text-18 v-middle"><?php echo $lot->getDetailsFroid()->getTemp_fin() != '' ? $lot->getDetailsFroid()->getTemp_fin() . '&deg;C' : '&mdash;'; ?></td>
                    </tr>
                    <tr>
                        <th colspan="2" class="v-middle">Temps de <?php echo $type_val == 's' ? 'surgelation' : 'congélation'; ?> :</th>
                        <td class="text-18 v-middle text-center"><?php echo $lot->getDetailsFroid()->getTempsFroid(); ?></td>
                        <th class="v-middle text-center bg-<?php
                        $minutesConsigne = $type_val == 's' ? 3 * 60 : 19 * 60;
                        $tempsFroidMinutes = intval($lot->getDetailsFroid()->getTempsFroid(true));
                        echo $tempsFroidMinutes > $minutesConsigne + 5 || $tempsFroidMinutes < $minutesConsigne - 5 ? 'danger' : 'success';
                        ?>" colspan="2">Consigne : <?php echo $type_val == 's' ? '3' : '19'; ?>H</th>
                    </tr>
                    <tr>
                        <th colspan="2">Responsable du contrôle :</th>
                        <td colspan="3"><?php echo $lot->getDetailsFroid()->getNom_controleur(); ?></td>
                    </tr>
                </table>
            </div>
        </div>

    <?php echo '^'; // Séparateur Body / Footer ?>

        <button type="button" class="btn btn-success btnValiderFromModale" data-id-table="<?php echo $lot->getDetailsFroid()->getId(); ?>" data-type-val="<?php echo $type_val; ?>"><i class="fa fa-check mr-2"></i>Valider</button>

    <?php
	}  // FIN test type de contrôle

	exit;
} // FIN mode

/* ------------------------------------
MODE - Valide un lot (admin)
------------------------------------*/
function modeValidationLot() {

	global
        $logsManager,
		$cnx,
		$utilisateur,
		$validationsManager;

    $id_validation = isset($_REQUEST['id_validation']) ? intval($_REQUEST['id_validation']) : 0;
    $conformite = isset($_REQUEST['conformite']) ? intval($_REQUEST['conformite']) : -1;

    if ($id_validation == 0) { echo '-1'; exit; }

    if (!isset($utilisateur))    { echo '-1'; exit; }
    if (!$utilisateur->isAdmin()) { echo '-1'; exit; }

	$validation = $validationsManager->getValidation($id_validation);
    if (!$validation instanceof Validation) { echo '-1'; exit; }

    if ($conformite > -1) {

		if ($validation->getType() == 1 && $validation->getReception() instanceof LotReception) {


			if ($validation->getReception()->getConformite() != $conformite) {
				$validation->getReception()->setConformite($conformite);
                $lotReceptionManager = new LotReceptionManager($cnx);
				$lotReceptionManager->saveLotReception($validation->getReception());

				$log = new Log([]);
				$log->setLog_type('info');
				$log->setLog_texte('Changement de la conformité de la réception lors de la validation du lot, ID validation #' . $id_validation) ;
				$logsManager->saveLog($log);

            }
		}
    }



    $validation->setValidation_id_user($utilisateur->getId());
    $validation->setValidation_date(date('Y-m-d- H:i:s'));
	$validationsManager->saveValidation($validation);


    exit;
}  // FIN mode


/* ------------------------------------
MODE - Valide tous les lots d'un coup
------------------------------------*/
function modeValidationLotsTous() {

	global
        $logsManager,
		$utilisateur,
		$validationsManager;

	if (!$validationsManager->validerTout($utilisateur)) { exit; }


	// Log de la validation par le responsable
	$log = new Log([]);
	$log->setLog_type('success');
	$log->setLog_texte('Validation rapide de tous les lots par user ID #'.$utilisateur->getId()) ;
	$logsManager->saveLog($log);

	exit;
}  // FIN mode

/* ----------------------------------------
MODE - Affiche la liste des lots à valider
----------------------------------------*/
function modeShowListeValidations() {

    global
    	$validationsManager,
		$utilisateur;

	$nbAvalider = $validationsManager->getNbAValider();

    // Si aucun lot a valider
	if ($nbAvalider == 0) { ?>

        <div class="alert alert-info text-center">
            <i class="far fa-check-circle fa-4x mt-2 mb-3"></i> <p class="text-18">Vous n'avez aucun lot à valider pour le moment&hellip;</p>
        </div>

		<?php
    // Si on a bien des lots a valider...
	} else {

		$lots_avalider = $validationsManager->getListeValidations(); ?>

        <div class="alert alert-danger d-lg-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un appareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-lg-table">
            <thead>
            <tr>
                <?php if ($utilisateur->isDev()) { ?>
                    <th>ID</th>
                <?php } ?>
                <th>Numéros de lots</th>
                <th>Vue à valider</th>
                <th>Etape</th>
                <th class="d-none d-xl-table-cell">Date & heure du contrôle</th>
                <th class="d-none d-xl-table-cell">Contrôleur</th>
                <th>Résultat</th>
                <th class="t-actions w-court-admin-cell">Détails</th>
                <th class="t-actions w-court-admin-cell">Valider</th>
            </tr>
            </thead>
            <tbody>

			<?php
			foreach ($lots_avalider as $val) {

			    $vueLot =  $val->getVue();
			    ?>
                <tr>
					<?php if ($utilisateur->isDev()) { ?>
                        <td><span class="badge badge-warning badge-pill text-14"><?php echo $val->getId(); ?></span></td>
					<?php } ?>
                    <td><?php
                        foreach ($val->getLots() as $lotval) { ?>
                            <span class="badge badge-secondary text-18"><?php
                                echo $val->getType() != 4 ? $lotval->getNumlot() : 'NEGOCE ID'.$lotval->getID(); ?></span>
                        <?php }
                       ?></td>

                    <td class="w-court-admin-cell"><?php echo $vueLot instanceOf Vue ? '<span class="badge badge-info form-control text-14">'.$vueLot->getNom().'</span>' : '—'; ?></td>
                    <td class="w-court-admin-cell">
                        <span class="badge badge-<?php
                            echo $val->getType() == 3 ? 'warning' : 'secondary';
                            ?> text-14" data-toggle="tooltip" data-placement="right" title="<?php
                            echo $val->getType() == 3 ? 'Contrôle LOMA' : 'Fin de traitement';
                            ?>"> <i class="fa fa-<?php
                            echo $val->getType() == 3 ? 'clipboard-check' : 'flag-checkered';
                            ?> fa-fw"></i></span>
                    </td>
                    <td class=" gris-5 d-none d-xl-table-cell"><?php echo $val->getDate() != '' ?  ucfirst(Outils::getDate_verbose($val->getDate(), false)) : '—'; ?></td>
                    <td><?php echo  $val->getNom_controleur(); ?></td>
                    <td><?php
						$fa         = $val->getResultat() == 1 ? 'check' :  'exclamation-triangle';
						if          ($val->getResultat() == 1) { $colorPill = 'success';
						} else if   ($val->getResultat() == 0) { $colorPill = 'danger';
						} else      { $colorPill = 'warning'; };

						echo '<span class="badge text-14 form-control badge-'.$colorPill.'"><i class="mr-1 fa fa-fw fa-'.$fa.'"></i>';
                        echo $val->getResultat_verbose();
						echo '</span>';
						?></td>

                   <td class="t-actions w-mini-admin-cell"><button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#modalValidation" data-validation-id="<?php
						echo $val->getId(); ?>"><i class="fa fa-ellipsis-h"></i> </button>
                    </td>
                    <td class="t-actions w-mini-admin-cell"><button type="button" class="btn  btn-success btnValider" data-id-validation="<?php
						echo $val->getId(); ?>"><i class="fas fa-check"></i> </button>
                    </td>
                </tr>
			<?php } // FIN boucle
			?>
            </tbody>
        </table>

        <?php

	} // FIN test nombre de lots à valider

    exit;

} // FIN mode

/* -----------------------------------------------------------------------
MODE - Mise à jour du badge compteur de lots à valider (validation ajax)
------------------------------------------------------------------------*/
function modeUpdateBadgeCompteurMenu() {

	global
	    $cnx;

	$validationsManager = new ValidationManager($cnx);
	$nbAvalider         = $validationsManager->getNbAValider(); ?>

	<span class="badge-a-valider badge badge-pill badge-<?php echo $nbAvalider > 0 ? 'warning' : 'success'; ?>"><?php echo $nbAvalider; ?></span>

    <?php
    exit;

} // FIN mode

/* -----------------------------------------------------------------------
MODE - Enregistremetn de la courbe de température pour les cycles de we
------------------------------------------------------------------------*/
function modeCourbeTemp() {

    global $cnx;

    $id_validation  = isset($_REQUEST['id_validation']) ? intval($_REQUEST['id_validation']) : 0;
    $courbe_temp    = isset($_REQUEST['courbe_temp'])   ? intval($_REQUEST['courbe_temp'])   : -1;

    if ($id_validation == 0 || $courbe_temp < 0 || $courbe_temp > 1) { exit('1'); }

	$validationsManager = new ValidationManager($cnx);

    $val = $validationsManager->getValidation($id_validation);
    if (!$val instanceof Validation) { exit('2'); }

    $val->setCourbe_temp($courbe_temp);
    echo $validationsManager->saveValidation($val) ? '3' : '4';
    exit;

} // FIN mode


// MODE - Affiche la liste des lots à valider

function modeShowListeValidationsNegoce() {

    global
    	$validationsManager,
		$utilisateur;

	$nbAvalider = $validationsManager->getNbNegoceAValider();

    // Si aucun lot a valider
	if ($nbAvalider == 0) { ?>

        <div class="alert alert-info text-center">
            <i class="far fa-check-circle fa-4x mt-2 mb-3"></i> <p class="text-18">Vous n'avez aucun lot à valider pour le moment&hellip;</p>
        </div>

		<?php
    // Si on a bien des lots a valider...
	} else {

		$lots_avalider = $validationsManager->getListeValidationsNegoce(); ?>

        <div class="alert alert-danger d-lg-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un appareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-lg-table">
            <thead>
            <tr>
                <?php if ($utilisateur->isDev()) { ?>
                    <th>ID</th>
                <?php } ?>
                <th>Numéros de lots</th>
                <th>Vue à valider</th>
                <th>Etape</th>
                <th class="d-none d-xl-table-cell">Date & heure du contrôle</th>
                <th class="d-none d-xl-table-cell">Contrôleur</th>
                <th>Résultat</th>
                <th class="t-actions w-court-admin-cell">Détails</th>
                <th class="t-actions w-court-admin-cell">Valider</th>
            </tr>
            </thead>
            <tbody>

			<?php
			foreach ($lots_avalider as $val) {
                $vueLot =  $val->getVue();
			    ?>
                <tr>
					<?php if ($utilisateur->isDev()) { ?>
                        <td><span class="badge badge-warning badge-pill text-14"><?php echo $val->getId(); ?></span></td>
					<?php } ?>
                    <td><?php
                        foreach ($val->getLots() as $lotval) { ?>
                            <span class="badge badge-secondary text-18"><?php
                                echo $val->getType() != 4 ? $lotval->getNumlot() : $lotval->getNum_bl(); ?></span>
                        <?php }
                       ?></td>

                    <td class="w-court-admin-cell"><?php echo $vueLot instanceOf Vue ? '<span class="badge badge-info form-control text-14">'.$vueLot->getNom().'</span>' : '—'; ?></td>
                    <td class="w-court-admin-cell">
                        <span class="badge badge-<?php
                            echo $val->getType() == 3 ? 'warning' : 'secondary';
                            ?> text-14" data-toggle="tooltip" data-placement="right" title="<?php
                            echo $val->getType() == 3 ? 'Contrôle LOMA' : 'Fin de traitement';
                            ?>"> <i class="fa fa-<?php
                            echo $val->getType() == 3 ? 'clipboard-check' : 'flag-checkered';
                            ?> fa-fw"></i></span>
                    </td>
                    <td class=" gris-5 d-none d-xl-table-cell"><?php echo $val->getDate() != '' ?  ucfirst(Outils::getDate_verbose($val->getDate(), false)) : '—'; ?></td>
                    <td><?php echo  $val->getNom_controleur(); ?></td>
                    <td><?php
						$fa         = $val->getResultat() == 1 ? 'check' :  'exclamation-triangle';
						if          ($val->getResultat() == 1) { $colorPill = 'success';
						} else if   ($val->getResultat() == 0) { $colorPill = 'danger';
						} else      { $colorPill = 'warning'; };

						echo '<span class="badge text-14 form-control badge-'.$colorPill.'"><i class="mr-1 fa fa-fw fa-'.$fa.'"></i>';
                        echo $val->getResultat_verbose();
						echo '</span>';
						?></td>

                   <td class="t-actions w-mini-admin-cell"><button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#modalValidation" data-validation-id="<?php
						echo $val->getId(); ?>"><i class="fa fa-ellipsis-h"></i> </button>
                    </td>
                    <td class="t-actions w-mini-admin-cell"><button type="button" class="btn  btn-success btnValider" data-id-validation="<?php
						echo $val->getId(); ?>"><i class="fas fa-check"></i> </button>
                    </td>
                </tr>
			<?php } // FIN boucle
			?>
            </tbody>
        </table>

        <?php

	} // FIN test nombre de lots à valider

    exit;




} // FIN mode


function modeModalValidationNegoceDetails() {
	global
        $cnx,
        $validationsManager;

    // Gestion du retour d'erreur
    function erreurRecup() { ?>
        <div class="alert alert-danger text-center">
            <i class="fa fa-exclamation-triangle fa-lg mb-1"></i>
            <p>Erreur lors de la récupération des données !</p>
        </div>
        <?php exit;
    } // FIN retour en cas d'erreur

    $validation_id     = isset($_REQUEST['validation_id']) ? intval($_REQUEST['validation_id']) : 0;



    if ($validation_id == 0) { erreurRecup(); }

    $validation = $validationsManager->getValidationNegoce($validation_id);
    if (!$validation instanceof Validation) { erreurRecup(); }
	$froidManager = new FroidManager($cnx); ?>
        <div class="row">
            <div class="col-4">
                <div class="alert alert-secondary text-center">
                    <h2><?php
                       foreach ((array)$validation->getLots() as $lotval) {
                        ?>
                            <div><?php                            
								echo $validation->getType() != 4 ? $lotval->getNumlot() : 'NEGOCE ID'.$lotval->getID();
                             ?></div>
                        <?php } ?>
                    </h2>
                    <span class="badge badge-info text-20 form-control"><?php echo $validation->getVue()->getNom() ?></span>
                </div>

                <div class="alert alert-<?php
				if          ($validation->getResultat() == 1) { echo 'success';
				} else if   ($validation->getResultat() == 0) { echo 'danger';
				} else      { echo 'warning'; };?> text-center">
                    <h3><i class="fa fa-<?php echo $validation->getResultat() == 1 ? 'check' : 'exclamation-triangle'; ?> mr-2"></i><?php echo  $validation->getResultat_verbose(); ?></h3>
                </div>
            </div>
            <div class="col-8">
                <div class="row">
                    <div class="col-12 col-xl-8">
                        <span class="texte-fin text-12 mr-1">Date et heure du contrôle :</span>
                        <?php echo $validation->getDate() != '' ? ucfirst(Outils::getDate_verbose($validation->getDate())) : '&mdash;'; ?>
                    </div>
                    <div class="col-12 col-xl-4 text-right">
                        <span class="texte-fin text-12 mr-1">Code du traitement :</span>
						<kbd class="bg-secondary"><?php

                            switch ($validation->getType()) {
                                case 1: echo 'RCP'.sprintf("%04d", $validation->getId_liaison());break;
                                case 2:
                                    $froid = $froidManager->getFroid($validation->getId_liaison());
                                    if ($froid instanceof Froid) {
                                        echo strtoupper($froid->getCode()).sprintf("%04d", $validation->getId_liaison());
                                    }
                                    break;
                                case 3:
                                    echo 'L'. sprintf("%03d", $validation->getId_liaison());
                                    break;
								case 4:
									echo 'NEG'. sprintf("%03d", $validation->getId_liaison_negoce());
									break;
                            }

                            ?></kbd>
                    </div>
                </div>
                <table class="table w-100 table-border text-14 mt-3 table-v-middle table-padding-4-8">
                    <?php
					/** ***************************
					 * Réception
					 *************************** */
					if ($validation->getType() == 1 && $validation->getReception() instanceof LotReception) {

						$lotRecepetion = $validation->getLots()[0];
					    ?>
                        <tr>
                            <th class="nowrap">Poids :</th>
                            <td class="text-center"><?php
                                if (isset($validation->getLots()[0]) && $validation->getLots()[0] instanceof Lot) {

									echo $lotRecepetion->getPoids_reception() != ''
										? '<code class="text-dark text-20">'.number_format($lotRecepetion->getPoids_reception(),3,'.', '') . '</code> Kgs'
										: '-';
                                } else {
                                    echo '-';
                                }
                                ?></td>
                            <th class="nowrap">Etat visuel :</th>
                            <td class="text-center text-white bg-<?php echo $validation->getReception()->getEtat_visuel() == 1 ? 'success' : 'danger'; ?>">
                                <?php echo $validation->getReception()->getEtat_visuel_verbose(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="nowrap">Composition :</th>
                            <td class="text-center"><?php echo $lotRecepetion->getComposition_verbose(); ?></td>
                            <th class="nowrap">Température<?php echo $lotRecepetion->getComposition() == 1 ? 's' : ''; ?> :</th>
                                <?php
								if ( $lotRecepetion->getReception() instanceof LotReception) {
									// Abats (une seule température
									if ($lotRecepetion->getComposition() == 2) { ?>

                                        <td class="text-center"><code class="text-dark text-20"><?php echo $lotRecepetion->getReception()->getTemp(); ?></code><span class="gris-7 text-14">°C</span></td>

                                    <?php }	else { ?>
                                        <td>
                                            <ul class="no-margin">
                                                <li>
                                                    <span class="badge badge-info badge-pill badge-dmf text-14">D</span><code class="text-dark text-20"><?php
                                                        echo $lotRecepetion->getReception()->getTemp_d(); ?></code><span class="gris-7 text-14">°C</span>
                                                </li>
                                                <li>
                                                    <span class="badge badge-info badge-pill badge-dmf text-14">M</span><code class="text-dark text-20"><?php
                                                        echo $lotRecepetion->getReception()->getTemp_m(); ?></code><span class="gris-7 text-14">°C</span>
                                                </li>
                                                <li>
                                                    <span class="badge badge-info badge-pill badge-dmf text-14">F</span><code class="text-dark text-20"><?php
                                                        echo $lotRecepetion->getReception()->getTemp_f(); ?></code><span class="gris-7 text-14">°C</span>
                                                </li>
                                            </ul>
                                        </td>
                                        <?php
									} // FIN test type composition
								} // Fin test objet Réception
								?>
                        </tr>
                        <tr>
                            <th class="nowrap">Crochets :</th>
                            <td class="text-center">


                                    <?php if ($lotRecepetion->getReception()->getCrochets_recus() == 0 && $lotRecepetion->getReception()->getCrochets_rendus() == 0) { echo "&mdash;";
					                } else { ?>
                                        <span class="badge badge-secondary mr-1 text-16 badge-pill texte-fin">
                                        <?php $ecart = $lotRecepetion->getReception()->getCrochets_recus() - $lotRecepetion->getReception()->getCrochets_rendus();
					                echo $ecart > 0 ? '+' : '';	echo $ecart;
					                ?></span>

					                <?php } ?>
								    <?php if ($lotRecepetion->getReception()->getCrochets_recus() > 0 || $lotRecepetion->getReception()->getCrochets_rendus() > 0) { ?>
                                    <i class="fa fa-download fa-fw gris-9"></i><?php echo $lotRecepetion->getReception()->getCrochets_recus(); ?>
                                    <i class="fa fa-upload fa-fw gris-9 ml-2"></i><?php echo $lotRecepetion->getReception()->getCrochets_rendus();
								} ?>
                            </td>
                            <th class="nowrap">Transporteur :</th>
                            <td class="text-center"><?php echo $lotRecepetion->getReception()->getNom_transporteur() == '' ? "&mdash;" : $lotRecepetion->getReception()->getNom_transporteur(); ?></td>

                        </tr>
                     <?php

					$controleurNom = $lotRecepetion->getReception()->getUser_nom();

                    /** ***************************
                     * Froid
                     *************************** */
                    } else if ($validation->getType() == 2) {

						$produitsManager = new ProduitManager($cnx);
						$froid = $froidManager->getFroid($validation->getId_liaison());

					    ?>
                        <tr>
                            <th class="nowrap">Machine :</th>
                            <td class="text-center" colspan="3"><?php
								switch(trim(strtolower($validation->getVue()->getCode()))) {
									case 'cgl':
										echo 'Tunnel de congélation';
										break;
									case 'srgv':
										echo 'Surgélateur vertical';
										break;
									case 'srgh':
										echo 'Surgélateur horizontal';
										break;
									case 'hac':
										echo 'Hachoir';
										break;
									default:
										echo 'N/A';
								}
                                ?></td>
                            <th class="nowrap">Produits :</th>
                            <td class="text-center text-22"><?php echo $produitsManager->getNbProduitsFroid($validation->getId_liaison()); ?></td>
                        </tr>
                        <tr>
                            <th class="nowrap">Entrée :</th>
                            <td class="text-center"><?php echo Outils::getDate_verbose($froid->getDate_entree(), false, '<br>'); ?></td>
                            <th class="nowrap">Sortie :</th>
                            <td class="text-center"><?php echo Outils::getDate_verbose($froid->getDate_sortie(), false, '<br>'); ?></td>
                            <th class="nowrap">Durée (H) :</th>
                            <td class="text-center"><?php echo $froid->getTempsFroid(); ?></td>
                        </tr>
                        <tr>
                            <th class="nowrap">Temp. début :</th>
                            <td class="text-center"><?php echo number_format($froid->getTemp_debut(),1,'.', ''); ?>°C</td>
                            <th class="nowrap">Temp. fin :</th>
                            <td class="text-center"><?php echo number_format($froid->getTemp_fin(),1,'.', ''); ?>°C</td>
                            <th class="nowrap">Delta T° :</th>
                            <td class="text-center"><?php echo number_format($froid->getTemp_fin() - $froid->getTemp_debut(),1,'.', ''); ?>°C</td>
                        </tr>

    			    	<?php
						$userManager = new UserManager($cnx);
                        $controleurNom = $userManager->getUser($froid->getId_visa_controleur())->getNomComplet();

                    /** ***************************
                     * Loma
                     *************************** */
					} else if ($validation->getType() == 3 && $validation->getLoma() instanceof Loma) {


					    ?>

                        <tr>
                            <th class="nowrap">Produit testé :</th>
                            <td class="text-center"><?php echo $validation->getLoma()->getNom_produit(); ?></td>
                            <th class="nowrap">Résultat :</th>

                            <td class="text-white nowrap bg-<?php echo $validation->getLoma()->getTest_pdt() == 0 ? 'success' : 'danger'; ?>">
                                <i class="fa fa-<?php echo $validation->getLoma()->getTest_pdt() == 0 ? 'check' : 'times'; ?> mr-2 fa-fw"></i><span class="texte-fin">OK</span>
                            </td>
                        </tr>


						<?php

						$controleurNom = $validation->getLoma()->getNom_user();

                    /** ***************************
                     * Réception Négoce
                     *************************** */
					} else if ($validation->getType() == 4 && $validation->getLot_negoce() instanceof LotNegoce) {
                        ?>   <tr>
                        <th class="nowrap">Température :</th>
                        
                        <td class="text-center"><code class="text-dark text-20"><?php echo $validation->getLot_negoce()->getTemp(); ?></code><span class="gris-7 text-14">°C</span></td>
                        </tr>                        
                        <?php

						$controleur_id = $validation->getLot_negoce()->getId_user_maj();
						$userManager = new UserManager($cnx);
						$controleur = $userManager->getUser($controleur_id);
						$controleurNom = $controleur->getNomComplet();


                    /** ***************************
                     * Erreur quelque part :(
                     *************************** */
					} else {
						$controleurNom = 'N/A';
					    ?>
                        <tr>
                            <td class="bg-danger">Erreur de récupération des données...</td>
                        </tr>
					<?php
					} // FIN test type de retour
                    ?>
                </table>

<?php

if ($validation->getType() == 1 && $validation->getReception() instanceof LotReception) {


    ?>
    <input type="checkbox" class="togglemaster" data-toggle="toggle" id="updConformite"
		<?php echo $validation->getReception()->getConformite() == 1 ? 'checked' : ''; ?>
           data-on="Conforme"
           data-off="Non conforme"
           data-onstyle="success"
           data-offstyle="danger"
    />

    <?php



}

// Cycle de nuit / we pour froid
if ($validation->getType() == 2 && $froid->getNuit() > 0) {

    $dt = new DateTime($froid->getDate_entree());
    $cycleWe = intval($dt->format('w')) == 5;
    $typeCycle = $cycleWe ? 'week-end' : 'nuit';
    ?>

    <div class="row">
        <div class="col-5">
            <span class="badge badge-info text-20"><i class="fa fa-moon mr-1"></i> Cycle de <?php echo $typeCycle; ?></span>
        </div>
        <div class="col-7 text-right">
			<?php
			// Si on est en cycle de we, il faut valider le cycle de we
			if ($cycleWe) { ?>

                <p>
                    <label>Courbe de température :</label>
                    <input type="checkbox" class="togglemaster switch-courbe-temp"
                           checked
                           data-toggle      = "toggle"
                           data-on          = "Correcte"
                           data-off         = "Anormale"
                           data-onstyle     = "success"
                           data-offstyle    = "danger"
                           data-id-val      = "<?php echo $validation->getId(); ?>"/>
                </p>

			<?php } // FIN test cycle de we
			?>

        </div>
    </div>





<?php } // FIN test type froid pour gestion des cycles de nuit/we


$observations = '';
if ($validation->getType() == 1 && $validation->getReception() instanceof LotReception) {
    $observations = $validation->getReception()->getObservations();
} else if ($validation->getType() == 3 && $validation->getLoma() instanceof Loma) {
    $observations = $validation->getLoma()->getCommentaire();
}

if ($observations != '') { ?>
 <h4 class="text-16 mb-1">Observations :</h4>
                <p class="alert alert-secondary text-14"><?php echo $observations;?></p>
    <?php }
?>


            </div>
        </div>

	<?php
	echo '^'; // Séparateur Body / Footer ?>


    <div class="float-left padding-top-5"><i class="fa fa-user gris-c fa-sm mr-1"></i>
            <span class="texte-fin text-12">Opérateur :</span>
            <span class="text-14 gris-3"><?php echo $controleurNom; ?></span>
    </div>
    <div class="text-right">
        <button type="button" class="btn btn-success btnValiderFromModale" data-id-validation="<?php echo $validation->getId(); ?>"><i class="fa fa-check mr-2"></i>Valider</button>
    </div>



    <?php

	exit;

	/** ***************************
	 * Contrôle LOMA
	 *************************** */

	if ($validation->getType() == 'l') {

        if (!$lot->getDetailsLoma() instanceof DetailsLoma)  { erreurRecup(); } // Si pas instanciation de l'objet du test
        if ($lot->getDetailsLoma()->getTest_resultat() == 0) { erreurRecup(); } // Si pas d'info sur le résultat du test
        ?>

    <div class="row">
        <div class="col-5">
            <div class="alert alert-secondary text-center">
                <h2><?php echo $lot->getNumlot(); ?></h2>
                <span class="badge badge-info text-20"><?php echo $types_val[$type_val]; ?></span>
            </div>

            <div class="alert alert-<?php echo $lot->getDetailsLoma()->getTest_resultat() == 1 ? 'success' : 'danger';?> text-center">
                <h3><i class="fa fa-<?php echo $lot->getDetailsLoma()->getTest_resultat() == 1 ? 'check' : 'exclamation-triangle'; ?> mr-2"></i><?php echo $lot->getDetailsLoma()->getTest_resultat() == 1 ? 'DÉTECTÉ' : 'NON DÉTECTÉ'; ?></h3>
            </div>
        </div>
        <div class="col-7">

            <table class="table w-100 table-border text-14">
                <tr>
                    <th colspan="2">Référence du contrôle :</th>
                    <td colspan="3"><code>LOMA-ID/<?php echo $lot->getDetailsLoma()->getId(); ?></code></td>
                </tr>
                <tr>
                    <th colspan="2">Date et heure du test :</th>
                    <td colspan="3"><?php echo $lot->getDetailsLoma()->getDate_test() != '' ? ucfirst(Outils::getDate_verbose($lot->getDetailsLoma()->getDate_test())) : '&mdash;'; ?></td>
                </tr>
                <tr>
                    <th colspan="2">Client :</th>
                    <td colspan="3"><?php echo $lot->getDetailsLoma()->getNom_client(); ?></td>
                </tr>
                <tr>
                    <th colspan="2">Produit :</th>
                    <td colspan="3"><?php echo $lot->getDetailsLoma()->getNom_produit(); ?></td>
                </tr>
                <tr>
                    <th class="v-middle">Condionnement <i class="fa fa-caret-right v-middle gris-c pl-2"></i></th>
                    <th class="text-center v-middle">Début :</th>
                    <td class="text-center text-18 v-middle"><?php echo Outils::getHeureOnly($lot->getDetailsLoma()->getCond_debut()); ?></td>
                    <th class="text-center v-middle">Fin :</th>
                    <td class="text-center text-18 v-middle"><?php echo Outils::getHeureOnly($lot->getDetailsLoma()->getCond_fin()); ?></td>
                </tr>
                <tr>
                    <th colspan="2">Responsable du contrôle :</th>
                    <td colspan="3"><?php echo $lot->getDetailsLoma()->getNom_controleur(); ?></td>
                </tr>
            </table>
            </div>
        </div>
        <?php
        // Si commentaire...
        if ($lot->getDetailsLoma()->getCommentaire() != '') { ?>

            <div class="row">
                <div class="col">
                    <div class="alert alert-secondary mb-0">
                        <?php echo nl2br($lot->getDetailsLoma()->getCommentaire()); ?>
                    </div>
                </div>
            </div>
        <?php } // FIN commentaire Loma

		echo '^'; // Séparateur Body / Footer ?>

        <button type="button" class="btn btn-success btnValiderFromModale" data-id-table="<?php echo $lot->getDetailsLoma()->getId(); ?>" data-type-val="<?php echo $type_val; ?>"><i class="fa fa-check mr-2"></i>Valider</button>

		<?php


    /** ***************************
     * Surgélation / Congélation
	 *************************** */

    } else if ($type_val == 's' || $type_val == 'c') {

		$lot = $lotsManager->getDetailsFroidLot($lot);

		if (!$lot->getDetailsFroid() instanceof DetailsFroid)  { erreurRecup(); } // Si pas instanciation de l'objet du traitement froid
		if ($lot->getDetailsFroid()->getConforme() == 0) { erreurRecup(); }       // Si pas d'info sur la conformité

        ?>

        <div class="row">
            <div class="col-5">
                <div class="alert alert-secondary text-center">
                    <h2><?php echo $lot->getNumlot(); ?></h2>
                    <span class="badge badge-info text-20"><?php echo $types_val[$type_val]; ?></span>
                </div>

                <div class="alert alert-<?php echo $lot->getDetailsFroid()->getConforme() == 1 ? 'success' : 'danger';?> text-center">
                    <h3><i class="fa fa-<?php echo $lot->getDetailsFroid()->getConforme() == 1 ? 'check' : 'exclamation-triangle'; ?> mr-2"></i><?php echo $lot->getDetailsFroid()->getConforme() == 1 ? '' : 'NON'; ?> CONFORME</h3>
                </div>
            </div>
            <div class="col-7">
                <table class="table w-100 table-border text-14">
                    <tr>
                        <th colspan="2">Référence du traitement :</th>
                        <td colspan="3"><code>FROID-ID/<?php echo $lot->getDetailsFroid()->getId(); ?></code></td>
                    </tr>
                    <tr>
                        <th colspan="2">Date et heure du contrôle :</th>
                        <td colspan="3"><?php echo $lot->getDetailsFroid()->getDate_controle() != '' ? ucfirst(Outils::getDate_verbose($lot->getDetailsFroid()->getDate_controle())) : '&mdash;'; ?></td>
                    </tr>
                    <tr>
                        <th colspan="2">Entrée <?php echo $type_val == 's' ? 'dans le surgelateur' : 'en tunel'; ?> :</th>
                        <td colspan="3"><?php echo $lot->getDetailsFroid()->getDate_entree() != '' ? ucfirst(Outils::getDate_verbose($lot->getDetailsFroid()->getDate_entree())) : '&mdash;'; ?></td>
                    </tr>
                    <tr>
                        <th colspan="2">Sortie du <?php echo $type_val == 's' ? 'surgelateur' : 'tunel'; ?> :</th>
                        <td colspan="3"><?php echo $lot->getDetailsFroid()->getDate_sortie() != '' ? ucfirst(Outils::getDate_verbose($lot->getDetailsFroid()->getDate_sortie())) : '&mdash;'; ?></td>
                    </tr>
                    <tr>
                        <th class="v-middle">Température <i class="fa fa-caret-right v-middle gris-c pl-2"></i></th>
                        <th class="text-center v-middle">Début :</th>
                        <td class="text-center text-18 v-middle"><?php echo $lot->getDetailsFroid()->getTemp_debut() != '' ? $lot->getDetailsFroid()->getTemp_debut() . '&deg;C' : '&mdash;'; ?></td>
                        <th class="text-center v-middle">Fin :</th>
                        <td class="text-center text-18 v-middle"><?php echo $lot->getDetailsFroid()->getTemp_fin() != '' ? $lot->getDetailsFroid()->getTemp_fin() . '&deg;C' : '&mdash;'; ?></td>
                    </tr>
                    <tr>
                        <th colspan="2" class="v-middle">Temps de <?php echo $type_val == 's' ? 'surgelation' : 'congélation'; ?> :</th>
                        <td class="text-18 v-middle text-center"><?php echo $lot->getDetailsFroid()->getTempsFroid(); ?></td>
                        <th class="v-middle text-center bg-<?php
                        $minutesConsigne = $type_val == 's' ? 3 * 60 : 19 * 60;
                        $tempsFroidMinutes = intval($lot->getDetailsFroid()->getTempsFroid(true));
                        echo $tempsFroidMinutes > $minutesConsigne + 5 || $tempsFroidMinutes < $minutesConsigne - 5 ? 'danger' : 'success';
                        ?>" colspan="2">Consigne : <?php echo $type_val == 's' ? '3' : '19'; ?>H</th>
                    </tr>
                    <tr>
                        <th colspan="2">Responsable du contrôle :</th>
                        <td colspan="3"><?php echo $lot->getDetailsFroid()->getNom_controleur(); ?></td>
                    </tr>
                </table>
            </div>
        </div>

    <?php echo '^'; // Séparateur Body / Footer ?>

        <button type="button" class="btn btn-success btnValiderFromModale" data-id-table="<?php echo $lot->getDetailsFroid()->getId(); ?>" data-type-val="<?php echo $type_val; ?>"><i class="fa fa-check mr-2"></i>Valider</button>

    <?php
	}  // FIN test type de contrôle

	exit;
} // FIN mode

function modeValidationLotNegoce() {

	global
        $logsManager,
		$cnx,
		$utilisateur,
		$validationsManager;

    $id_validation = isset($_REQUEST['id_validation']) ? intval($_REQUEST['id_validation']) : 0;
    $conformite = isset($_REQUEST['conformite']) ? intval($_REQUEST['conformite']) : -1;

    if ($id_validation == 0) { echo '-1'; exit; }

    if (!isset($utilisateur))    { echo '-1'; exit; }
    if (!$utilisateur->isAdmin()) { echo '-1'; exit; }

	$validation = $validationsManager->getValidationNegoce($id_validation);
    if (!$validation instanceof Validation) { echo '-1'; exit; }

    if ($conformite > -1) {

		if ($validation->getType() == 1 && $validation->getReception() instanceof LotReception) {


			if ($validation->getReception()->getConformite() != $conformite) {
				$validation->getReception()->setConformite($conformite);
                $lotReceptionManager = new LotReceptionManager($cnx);
				$lotReceptionManager->saveLotReception($validation->getReception());

				$log = new Log([]);
				$log->setLog_type('info');
				$log->setLog_texte('Changement de la conformité de la réception lors de la validation du lot, ID validation #' . $id_validation) ;
				$logsManager->saveLog($log);

            }
		}
    }



    $validation->setValidation_id_user($utilisateur->getId());
    $validation->setValidation_date(date('Y-m-d- H:i:s'));
	$validationsManager->saveValidation($validation);

    exit;
}  // FIN mode

function modevalidationLotsNegoceTous() {

	global
        $logsManager,
		$utilisateur,
		$validationsManager;

	if (!$validationsManager->validerToutNegoce($utilisateur)) { exit; }
    
	// Log de la validation par le responsable
	$log = new Log([]);
	$log->setLog_type('success');
	$log->setLog_texte('Validation rapide de tous les lots par user ID #'.$utilisateur->getId()) ;
	$logsManager->saveLog($log);

	exit;
}  // FIN mode