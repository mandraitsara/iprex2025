<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax VUE Nettoyage
------------------------------------------------------*/

// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';
// Instanciation des Managers
$logsManager = new LogManager($cnx);

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
	 * Etape        : 0
	 * Description  : Selection du module
	 *  ----------------------------------- */
	if ($etape == 0) { ?>

		<div class="row justify-content-md-center mt-5">
			<div class="col-2">
				<button type="button" class="btn btn-info btn-lg padding-50 form-control btnEtape" data-etape="1">
					<i class="fa fa-clipboard-check fa-2x mb-2 margin-left-15"></i>
					<i class="fa fa-caret-left position-relative" style="top:-5px; "></i>
					<div>Contrôle<br>avant production</div>
				</button>
			</div>

            <div class="col-2">
                <button type="button" class="btn btn-info btn-lg padding-50 form-control btnEtape" data-etape="3">
                    <i class="fa fa-caret-right" style="top:-5px; "></i>
                    <i class="fa fa-clipboard-check fa-2x mb-2 margin-right-15"></i>
                    <div>Contrôle<br>en fin de production</div>
                </button>
            </div>

		</div>


	<?php } // FIN étape

	/** ----------------------------------------
	 * Etape        : 1
	 * Description  : Contrôle AVANT PROD
	 *  ----------------------------------- */
	if ($etape == 1) {

		$pvisuManager =  new PvisuAvantManager($cnx);
        $pointsControlesManager = new PointsControleManager($cnx);
		$points = $pointsControlesManager->getListePointsControles();

		$bo = false;

		$id_avant = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_avant > 0) {
            // Admin - edite un avant
            $pvisu = $pvisuManager->getPvisuAvant($id_avant);
            $bo = true;
		} else {
			// On récupère les données enregistrées du jour s'il y en a
			$pvisu = $pvisuManager->getPvisuAvantJour('', false);
		}
		if (!$pvisu instanceof PvisuAvant) { $pvisu = new PvisuAvant([]); }

		$visuPoints = $pvisuManager->getListePvisuAvantPoints($pvisu, true);
        if (!is_array($visuPoints)) { $visuPoints = []; }

		if ($pvisu->getDate() == '') { $pvisu->setDate(date('Y-m-d')); }



		?>

        <form class="row justify-content-md-center" id="formEtape">
            <input type="hidden" name="mode" value="savePvisuAvant"/>
            <div class="col-md-10 col-xl-6">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td class="border-0 text-28 gris-9 pt-2"><?php echo Outils::getDate_only_verbose($pvisu->getDate(), true, false); ?></td>
                        <td class="border-0 text-center text-success pt-4 pb-2"><i class="fa fa-check mr-1"></i> Satisfaisant</td>
                        <td class="border-0 text-center nowrap text-danger pt-4 pb-2"><i class="fa fa-times mr-1"></i>Non-satisfaisant</td>
                    </tr>
                    </thead>
        <?php
		foreach ($points as $point) { ?>
           <tr class="<?php echo $point->getId_parent() == 0 ? 'bg-info text-white ' : ''; ?>">
                <td class="<?php echo $point->getId_parent() == 0 ? 'pl-3 text-28' : 'pl-5 text-18'; ?>">
                <?php echo $point->getNom(); ?>
                </td>
                <td class="text-center ichecktout">
                    <?php if ($point->getId_parent() == 0 && (int)$pvisu->getId_user_validation() == 0) { ?>
                        <button type="button" class="btn btn-success btn-sm border padding-5-10 margin-top-5 btnToutOk" data-id-parent="<?php
                        echo $point->getId();?>"><i class="fa fa-check mr-1"></i> Tout OK</button>
                    <?php } else if ($point->getId_parent() == 0 && $bo) { ?>

                    <?php } else if ($point->getId_parent() > 0) { ?>
                        <input type="radio" class="icheck icheck-pvisu icheck-vert parent-<?php
                        echo $point->getId_parent(); ?>" name="point[<?php echo $point->getId();?>]" value="1" <?php
                        echo isset($visuPoints[$point->getId()]) && (int)$visuPoints[$point->getId()] == 1 ? 'checked' : '';
                        echo (int)$pvisu->getId_user_validation() > 0 && !$bo ? ' disabled' : ''; ?>>
                    <?php } ?>

                </td>
               <td class="text-center ichecktout">
				   <?php if ($point->getId_parent() > 0) { ?>
                       <input type="radio" class="icheck icheck-pvisu icheck-rouge" name="point[<?php echo $point->getId();?>]" value="0" <?php
                       echo isset($visuPoints[$point->getId()]) && (int)$visuPoints[$point->getId()] == 0 ? 'checked' : '';
					   echo (int)$pvisu->getId_user_validation() > 0 && !$bo ? ' disabled' : ''; ?>>
				   <?php } ?>
               </td>

            </tr>
		<?php } // FIN boucle sur les points de contrôle
        ?>
                </table>
            </div>
            <div class="col-md-4 col-xl-6">
                <h2 class="gris-9 mt-4">Vérification propreté visuelle avant la production</h2>
                <div class="alert alert-secondary mt-4">
                    <ul>
                        <li><span class="text-success"><i class="fa fa-check mr-1"></i> Satisfaisant</span><span class="texte-fin gris-5"> : Pas d'anomalie constatée.</span></li>
                        <li><span class="text-danger"><i class="fa fa-times mr-1"></i> Non-satisfaisant</span><span class="texte-fin gris-5"> : nécéssité d'ouvrir une fiche de non-conformité (ENR59) et de mettre en œuvre une action corrective et/ou préventive. <em>Suivi réalisé lors de l'inspection suivante.</em></span></li>
                    </ul>
                </div>

                <div class="mb-2">
		<?php
		if ((int)$pvisu->getId_user_validation() == 0 || $bo) { ?>

                    <textarea name="commentaires" id="champ_clavier" class="form-control textarea-fixe" placeholder="Commentaires"><?php echo $pvisu->getCommentaires(); ?></textarea>
		<?php } else { echo $pvisu->getCommentaires(); } ?>
                </div>


                <?php
                if ((int)$pvisu->getId_user_validation() == 0) { ?>
                    <button type="button" class="btn btn-secondary btn-lg padding-20 btnRetourEtape0 mr-2">
                        <i class="fa fa-undo mr-1"></i> Annuler
                    </button>
                    <button type="button" class="btn btn-success btn-lg padding-20 btnSigner">
                        <i class="fa fa-signature mr-1"></i> Valider et signer
                    </button>
                <?php } else { ?>
                    <div class="alert alert-warning padding-50">Document validé par <?php echo $pvisu->getNom_validateur();

                    if ($bo) { ?>
                        <a href="<?php echo __CBO_ROOT_URL__.'admin-nettoyage.php'; ?>" class="btn btn-secondary float-right  margin-top--20">
                            <i class="fa fa-undo mr-1"></i> Retour
                        </a>
                        <button type="button" class="btn btn-success float-right btn margin-top--20 mr-1 btnSigner">
                            <i class="fa fa-save mr-1"></i> Enregistrer
                        </button>
					<?php } else { ?>
                        <button type="button" class="btn btn-secondary float-right btn-lg padding-20 btnRetourEtape0 mr-0 margin-top--20">
                            <i class="fa fa-undo mr-1"></i> Retour
                        </button>
					<?php } ?>

                    </div>
                <?php } ?>

            </div>
        </form>
        <?php

	 } // FIN étape

	/** ----------------------------------------
	 * Etape        : 2 (PENDANT )
	 * Description  : Selection du lot
	 *  ----------------------------------- */
	if ($etape == 2 ) { selectionLot($etape);  } // FIN étape



	/** ----------------------------------------
	 * Etape        : 21
	 * Description  : Contrôle PENDANT prod
	 *  ----------------------------------- */
	if ($etape == 21 ) {

	    $id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	    $id = substr($_REQUEST['id'], 0,2) == 'id' ? intval(str_replace('id', '', $_REQUEST['id'])) : 0;

		$pvisuManager =  new PvisuPendantManager($cnx);
		$pointsControlesManager = new PointsControleManager($cnx);
		$pVisuActionsManager = new PvisuActionsManager($cnx);
		$lotsManager = new LotManager($cnx);

		$points = $pointsControlesManager->getListePointsControles(false,1); // Doc 1 = Pendant
		$pVisuActions = $pVisuActionsManager->getListePvisuActions();

		$bo = $id > 0;



	    if ($id > 0) {

			$pvisu = $pvisuManager->getPvisuPendant($id);
			if (!$pvisu instanceof PvisuPendant) { $pvisu = new PvisuPendant([]); }
			$id_lot = $pvisu->getId_lot();

			$lot = $lotsManager->getLot($id_lot);
			if (!$lot instanceof Lot) { exit("Echec d'instanciation du lot (ID ".$id_lot.")"); }


		} else {
			if ($id_lot == 0) { exit("Echec d'identification du lot (ID 0)"); }

			$lot = $lotsManager->getLot($id_lot);
			if (!$lot instanceof Lot) { exit("Echec d'instanciation du lot (ID ".$id_lot.")"); }

			// On récupère les données enregistrées du jour s'il y en a
			$pvisu = $pvisuManager->getPvisuPendantJourByLot($lot);
			if (!$pvisu instanceof PvisuPendant) { $pvisu = new PvisuPendant([]); }
		}

		if ($pvisu->getDate() == '') { $pvisu->setDate(date('Y-m-d')); }
		$visuPoints = $pvisuManager->getListePvisuPendantPoints($pvisu, true);

		if (!is_array($visuPoints)) { $visuPoints = []; }

		$dNoneDroits = $utilisateur->isAdmin() ? '' : 'd-none';
		$urlRetour = $bo ? 'admin-nettoyage.php' : 'atelier/';
		?>
        <form class="row justify-content-md-center" id="formEtape">
            <input type="hidden" id="urlAtelier" value="<?php echo __CBO_ROOT_URL__.$urlRetour;?>"/>
            <input type="hidden" name="mode" value="savePvisuPendant"/>
            <input type="hidden" name="id_lot" value="<?php echo $id_lot; ?>"/>
            <input type="hidden" name="id" value="<?php echo $id; ?>"/>
            <div class="col-md-10 col-xl-7">

                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td class="border-0 text-28 gris-9 pt-3"><?php echo Outils::getDate_only_verbose($pvisu->getDate(), true, false); ?></td>
                        <td class="border-0 text-center text-success pt-4 pb-2"><i class="fa fa-check mr-1"></i>Oui</td>
                        <td class="border-0 text-center nowrap text-danger pt-4 pb-2"><i class="fa fa-times mr-1"></i>Non</td>
                        <td class="border-0 text-center text-info line-height-15 pt-4 pb-2 <?php echo $dNoneDroits; ?>">Action corrective<br>immédiate</td>
                        <td class="border-0 text-center text-info line-height-15 pt-4 pb-2 <?php echo $dNoneDroits; ?>">Ouverture<br>fiche N-C</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="bg-info text-white">
                        <td class="text-28">Incidents en cours de production</td>
                        <td class="text-center">
                            <?php if ((int)$pvisu->getId_user_validation() == 0 || $bo) { ?>
                            <button type="button" class="btn btn-success btn-sm border padding-5-10 margin-top-5 btnToutOk">
                                <i class="fa fa-check mr-1"></i> Tout OK</button>
                            <?php } ?>
                        </td>
                        <td></td>
                        <td class="<?php echo $dNoneDroits; ?>"></td>
                        <td class="<?php echo $dNoneDroits; ?>"></td>
                    </tr>
					<?php
					foreach ($points as $point) {

					    $etat = isset($visuPoints[$point->getId()]['etat']) ? $visuPoints[$point->getId()]['etat'] : -1;
					    $id_pvisu_action = isset($visuPoints[$point->getId()]['id_pvisu_action']) ? $visuPoints[$point->getId()]['id_pvisu_action'] : 0;
					    $fiche_nc = isset($visuPoints[$point->getId()]['fiche_nc']) ? $visuPoints[$point->getId()]['fiche_nc'] : 0;

					    ?>
                        <tr>
                            <td class="text-18">
								<?php echo $point->getNom(); ?>
                            </td>
                            <td class="text-center">

                                <input type="radio" class="icheck icheck-pvisu icheck-vert" name="point[<?php echo $point->getId();?>]" value="1" <?php
								echo $etat == 1 ? 'checked' : '';
								echo (int)$pvisu->getId_user_validation() > 0 && !$bo ? ' disabled' : '';  ?>>


                            </td>
                            <td class="text-center">

                                <input type="radio" class="icheck icheck-pvisu icheck-rouge" name="point[<?php echo $point->getId();?>]" value="0" <?php
								echo $etat == 0 ? 'checked' : '';
								echo (int)$pvisu->getId_user_validation() > 0 && !$bo ? ' disabled' : '';  ?>>


                            </td>
                            <td class="text-center <?php echo $dNoneDroits; ?>">

                                <!--<select class="selectpicker selectpicker-tactile" title="Aucune">-->
                                <select class="selectpicker" title="Aucune" name="actions[<?php echo $point->getId();?>]">
                                    <option value="0">Aucune</option>
                                    <option data-divider="true"></option>
									<?php
									foreach ($pVisuActions as $pVisuAction) { ?>
                                        <option value="<?php echo $pVisuAction->getId(); ?>" <?php
                                        echo $id_pvisu_action == $pVisuAction->getId() ? 'selected' : '';?>><?php
                                        echo $pVisuAction->getNom(); ?></option>
									<?php }
									?>
                                </select>

                            </td>
                            <td class="text-center <?php echo $dNoneDroits; ?>">
                                <input type="checkbox"
                                       class="togglemaster fiche-nc"
                                       name="fiche_nc[<?php echo $point->getId();?>]"
                                       value="1"
                                       data-toggle="toggle"
                                       data-on="Oui"
                                       data-off="Non"
                                       data-onstyle="danger"
                                       data-offstyle="secondary"
									    <?php echo $fiche_nc == 1 ? 'checked' : ''; ?>
                                />
                            </td>

                        </tr>
					<?php } // FIN boucle sur les points de contrôle
					?>
                    </tbody>
                </table>


            </div>
            <div class="col-md-4 col-xl-5">
                <h2 class="gris-9 mt-4">Vérification propreté visuelle pendant la production</h2>
                <div class="alert alert-secondary mt-4">
                    <span class="badge badge-secondary float-right text-36 ml-5"><span class="texte-fin text-24">Lot</span> <?php echo $lot->getNumlot(); ?></span>
                        <ul>
                            <li><span class="text-success"><i class="fa fa-check mr-1"></i> Satisfaisant</span><span class="texte-fin gris-5">
                                    : Pas d'anomalie constatée.</span></li>
                            <li><span class="text-danger"><i class="fa fa-times mr-1"></i> Non-satisfaisant</span><span class="texte-fin gris-5">
                                    : Nécéssité de mettre en œuvre une action corrective et/ou préventive.
                                    <em>Suivi réalisé lors de l'inspection suivante.</em></span></li>
                            <li>Au contrôle, si des carcasses sont en contact avec le sol, un parage sera fait sur la carcasse avant le désossage.</li>
                        </ul>
                </div>
                <div class="mb-2">
                    <?php if ((int)$pvisu->getId_user_validation() > 0 && !$bo) {  echo $pvisu->getCommentaires();  } else { ?>
                        <textarea name="commentaires" id="champ_clavier<?php echo $bo ? 'non' : ''; ?>" class="form-control textarea-fixe" placeholder="Commentaires"><?php echo $pvisu->getCommentaires(); ?></textarea>
					<?php } ?>

                </div>
                <div class="mt-3">
                    <?php if ($bo) { ?>

                        <a href="<?php echo __CBO_ROOT_URL__.'admin-nettoyage.php'?>" class="btn btn-secondary btn mr-2">
                            <i class="fa fa-undo mr-1"></i> Annuler
                        </a>
                        <button type="button" class="btn btn-success btn btnSigner">
                            <i class="fa fa-save mr-1"></i> Enregistrer
                        </button>

                    <?php } else { ?>


                        <?php
			               if ((int)$pvisu->getId_user_validation() > 0 && !$bo) { ?>
                               <div class="alert alert-warning padding-50">
                                   <a href="<?php echo __CBO_ROOT_URL__.'atelier/'?>" class="btn btn-secondary float-right btn-lg padding-20 margin-top--25">
                                       <i class="fa fa-undo mr-1"></i> Retour
                                   </a>
                                   Document validé par <?php echo $pvisu->getNom_validateur();?>
                               </div>
			                <?php } else { ?>
                                        <a href="<?php echo __CBO_ROOT_URL__.'atelier/'?>" class="btn btn-secondary btn-lg padding-20 mr-2">
                                            <i class="fa fa-undo mr-1"></i> Annuler
                                        </a>
                        <button type="button" class="btn btn-success btn-lg padding-20 btnSigner">
                            <i class="fa fa-signature mr-1"></i> Valider et signer
                        </button>

                    <?php }
			               } ?>

                </div>
            </div>
        </form>
        <?php
        exit;

	} // FIN étape

	/** ----------------------------------------
	 * Etape        : 3
	 * Description  : Contrôle FIN prod
	 *  ----------------------------------- */
	if ($etape == 3 ) {

		$pvisuManager =  new PvisuApresManager($cnx);
		$pointsControlesManager = new PointsControleManager($cnx);
		$pVisuActionsManager = new PvisuActionsManager($cnx);
		$points = $pointsControlesManager->getListePointsControles(false,2); // Doc 1 = Après
		$pVisuActions = $pVisuActionsManager->getListePvisuActions();

		$id = substr($_REQUEST['id'], 0,2) == 'id' ? intval(str_replace('id', '', $_REQUEST['id'])) : 0;
		$bo = $id > 0;




		if ($id > 0) {

			$pvisu = $pvisuManager->getPvisuApres($id);

		} else {

			// On récupère les données enregistrées du jour s'il y en a
			$pvisu = $pvisuManager->getPvisuApresJour('', false);
			if (!$pvisu instanceof PvisuApres) { $pvisu = new PvisuApres([]); }

		}


		if (!$pvisu instanceof PvisuApres) { $pvisu = new PvisuApres([]); }
		$visuPoints = $pvisuManager->getListePvisuApresPoints($pvisu, true);

		if (!is_array($visuPoints)) { $visuPoints = []; }

		$dNoneDroits = $utilisateur->isAdmin() ? '' : 'd-none';

		?>
        <form class="row justify-content-md-center" id="formEtape">
            <input type="hidden" name="mode" value="savePvisuApres"/>
            <input type="hidden" name="bo" value="<?php echo $bo ? '1' : '0'; ?>"/>

            <div class="col-md-10 col-xl-7">

                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td class="border-0 text-28 gris-9 pt-2"><?php echo Outils::getDate_only_verbose($pvisu->getDate(), true, false); ?></td>
                        <td class="border-0 text-center text-success pt-4 pb-2"><i class="fa fa-check mr-1"></i>Oui</td>
                        <td class="border-0 text-center nowrap text-danger pt-4 pb-2"><i class="fa fa-times mr-1"></i>Non</td>
                        <td class="border-0 text-center text-info line-height-15 pt-4 pb-2 <?php echo $dNoneDroits; ?>">Action corrective<br>immédiate</td>
                        <td class="border-0 text-center text-info line-height-15 pt-4 pb-2 <?php echo $dNoneDroits; ?>">Ouverture<br>fiche N-C</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="bg-info text-white">
                        <td class="text-28">Incidents en fin de production</td>
                        <td class="text-center">
		                <?php if ((int)$pvisu->getId_user_validation() == 0 || $bo) { ?>
                            <button type="button" class="btn btn-success btn-sm border padding-5-10 margin-top-5 btnToutOk">
                                <i class="fa fa-check mr-1"></i> Tout OK</button>
                        <?php } ?>
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
					<?php
					foreach ($points as $point) {


						$etat = isset($visuPoints[$point->getId()]['etat']) ? $visuPoints[$point->getId()]['etat'] : -1;
						$id_pvisu_action = isset($visuPoints[$point->getId()]['id_pvisu_action']) ? $visuPoints[$point->getId()]['id_pvisu_action'] : 0;
						$fiche_nc = isset($visuPoints[$point->getId()]['fiche_nc']) ? $visuPoints[$point->getId()]['fiche_nc'] : 0;

					    ?>
                        <tr>
                            <td class="text-18">
								<?php echo $point->getNom(); ?>
                            </td>
                            <td class="text-center">

                                <input type="radio" class="icheck icheck-pvisu icheck-vert" name="point[<?php echo $point->getId();?>]" value="1" <?php
								echo $etat == 1 ? 'checked' : '';
								echo (int)$pvisu->getId_user_validation() > 0 && !$bo ? ' disabled' : '';  ?>>


                            </td>
                            <td class="text-center">

                                <input type="radio" class="icheck icheck-pvisu icheck-rouge" name="point[<?php echo $point->getId();?>]" value="0" <?php
								echo $etat == 0 ? 'checked' : '';
								echo (int)$pvisu->getId_user_validation() > 0 && !$bo ? ' disabled' : '';  ?>>

                            </td>
                            <td class="text-center <?php echo $dNoneDroits; ?>">

                                <!--<select class="selectpicker selectpicker-tactile" title="Aucune">-->
                                <select class="selectpicker" title="Aucune" name="action[<?php echo $point->getId();?>]">
                                    <option value="0">Aucune</option>
                                    <option data-divider="true"></option>
									<?php
									foreach ($pVisuActions as $pVisuAction) { ?>
                                        <option value="<?php echo $pVisuAction->getId(); ?>" <?php
										echo $id_pvisu_action == $pVisuAction->getId() ? 'selected' : '';?>><?php
											echo $pVisuAction->getNom(); ?></option>
									<?php }
									?>
                                </select>

                            </td>
                            <td class="text-center <?php echo $dNoneDroits; ?>">
                                <input type="checkbox"
                                       class="togglemaster fiche-nc"
                                       name="fiche_nc[<?php echo $point->getId();?>]"
                                       value="1"
                                       data-toggle="toggle"
                                       data-on="Oui"
                                       data-off="Non"
                                       data-onstyle="danger"
                                       data-offstyle="secondary"
                                       <?php echo $fiche_nc == 1 ? 'checked' : ''; ?>
                                />
                            </td>

                        </tr>
					<?php } // FIN boucle sur les points de contrôle
					?>
                    </tbody>
                </table>


            </div>
            <div class="col-md-4 col-xl-5">
                <h2 class="gris-9 mt-4">Vérification propreté visuelle en fin de production</h2>
                <div class="alert alert-secondary mt-4">
                    <ul>
                        <li><span class="text-success"><i class="fa fa-check mr-1"></i> Satisfaisant</span><span class="texte-fin gris-5">
                                    : Pas d'anomalie constatée.</span></li>
                        <li><span class="text-danger"><i class="fa fa-times mr-1"></i> Non-satisfaisant</span><span class="texte-fin gris-5">
                                    : Nécéssité de mettre en œuvre une action corrective et/ou préventive.
                                    <em>Suivi réalisé lors de l'inspection suivante.</em></span></li>
                    </ul>
                </div>
                <div class="mb-2">
					<?php if ((int)$pvisu->getId_user_validation() > 0 && !$bo) {  echo $pvisu->getCommentaires();  } else { ?>
                        <textarea name="commentaires" id="champ_clavier<?php echo $bo ? 'non' : ''; ?>" class="form-control textarea-fixe" placeholder="Commentaires"><?php echo $pvisu->getCommentaires(); ?></textarea>
					<?php } ?>
                </div>
                <div class="mt-3">
					<?php if ($bo) { ?>

                        <a href="<?php echo __CBO_ROOT_URL__.'admin-nettoyage.php'?>" class="btn btn-secondary btn mr-2">
                            <i class="fa fa-undo mr-1"></i> Annuler
                        </a>
                        <button type="button" class="btn btn-success btn btnSigner">
                            <i class="fa fa-save mr-1"></i> Enregistrer
                        </button>

					<?php } else { ?>


						<?php
						if ((int)$pvisu->getId_user_validation() > 0 && !$bo) { ?>
                            <div class="alert alert-warning padding-50">
                                <a href="<?php echo __CBO_ROOT_URL__.'nettoyage/'?>" class="btn btn-secondary float-right btn-lg padding-20 margin-top--25">
                                    <i class="fa fa-undo mr-1"></i> Retour
                                </a>
                                Document validé par <?php echo $pvisu->getNom_validateur();?>
                            </div>
						<?php } else { ?>
                            <a href="<?php echo __CBO_ROOT_URL__.'nettoyage/'?>" class="btn btn-secondary btn-lg padding-20 mr-2">
                                <i class="fa fa-undo mr-1"></i> Annuler
                            </a>
                            <button type="button" class="btn btn-success btn-lg padding-20 btnSigner">
                                <i class="fa fa-signature mr-1"></i> Valider et signer
                            </button>

						<?php }
					} ?>




                </div>
            </div>
        </form>
		<?php
		exit;

	} // FIN étape

} // FIN mode




// Enregistre la vérif avant prod
function modeSavePvisuAvant() {

    global $cnx, $utilisateur;

    $pvisuAvantManager = new PvisuAvantManager($cnx);
	$pointsControleManager = new PointsControleManager($cnx);

    $pvisu = $pvisuAvantManager->getPvisuAvantJour();
    if (!($pvisu instanceof PvisuAvant)) { exit('-1'); }

    $points = isset($_REQUEST['point']) && is_array($_REQUEST['point']) ? $_REQUEST['point'] : [];
    $commentaires = isset($_REQUEST['commentaires']) ? strip_tags(trim($_REQUEST['commentaires'])) : '';

    // On vérifie qu'on a bien le bon nombre de points de controles cochés
    if (empty($points)) { exit('-2');}
	//$nbPoints = $pointsControleManager->getNbPointsControlesActifs();
	//if ($nbPoints != count($points)) { exit('-3'); }

    // On intègre les points de contrôles
	if (!$pvisuAvantManager->savePvisuAvantPoints($pvisu, $points)) { exit('-4'); }

	// On met à jour les commentaires et on signe
	$pvisu->setCommentaires($commentaires);
	$pvisu->setId_user($utilisateur->getId());

	echo $pvisuAvantManager->savePvisuAvant($pvisu) ? '1' : '0';

    exit;
} // FIN mode


function selectionLot($etape) {

    global $cnx;

	$na = '<span class="badge badge-warning badge-pill text-14">Non renseigné</span>';

    ?>
    <div class="row">
        <div class="col-10 mt-3">
            <h2 class="gris-9">Vérification propreté visuelle en <?php echo $etape == 2 ? 'cours' : 'fin'; ?> de production</h2>
            <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Sélectionnez le lot concerné :</h4>
        </div>
        <div class="col-2 mt-3 text-right">
            <button type="button" class="btn btn-secondary btn-lg padding-20 btnRetourEtape0 mr-2">
                <i class="fa fa-undo mr-1"></i> Retour
            </button>
        </div>
    </div>
    <?php
    $lotsManager = new LotManager($cnx);
    $listeLot    = $lotsManager->getListeLotsByVue('atl');
    // Si aucun lot en atelier
    if (empty($listeLot)) { ?>
        <div class="col alert alert-secondary mt-3 padding-50">
            <h2 class="mb-0 text-secondary text-center"><i class="fa fa-exclamation-circle fa-2x mb-3"></i>
                <p>Aucune lot disponible en atelier&hellip;</p>
            </h2>
        </div>
        <?php
    } // FIN test aucun lot atelier
    // Boucle sur les lots en atelier
    foreach ($listeLot as $lotvue) { ?>
        <div class="card text-white mb-3 carte-lot d-inline-block mr-3" style="max-width: 20rem;background-color: <?php echo $lotvue->getCouleur(); ?>" data-id-lot="<?php echo $lotvue->getId(); ?>" data-etape-suivante="<?php echo $etape.'1'; ?>">
            <div class="card-header text-36"><?php echo $lotvue->getNumlot(); ?></div>
            <div class="card-body">
                <table>
                    <tr>
                        <td>Espèce</td>
                        <th><?php echo $lotvue->getNom_espece($na); ?></th>
                    </tr>
                    <tr>
                        <td>Origine</td>
                        <th><?php echo $lotvue->getNom_origine() != '' ? $lotvue->getNom_origine() : $na; ?></th>
                    </tr>
                    <tr>
                        <td>Fournisseur</td>
                        <th><?php echo $lotvue->getNom_fournisseur() != '' ? $lotvue->getNom_fournisseur() : $na; ?></th>
                    </tr>
                    <tr>
                        <td>Abattoir</td>
                        <th><?php echo $lotvue->getNom_abattoir() != '' ? $lotvue->getNom_abattoir() : $na ; ?><br><span class="texte-fin"><?php echo $lotvue->getNumagr_abattoir();?></span></th>
                    </tr>
                    <tr>
                        <td>Réception</td>
                        <th><?php echo $lotvue->getDate_reception() != '' && $lotvue->getDate_reception() != '0000-00-00'  ?  Outils::getDate_only_verbose($lotvue->getDate_reception(), true, false) : $na; ?></th>
                    </tr>
                    <tr>
                        <td>Poids</td>
                        <th><?php echo $lotvue->getPoids_reception() > 0 ? number_format($lotvue->getPoids_reception(),3, '.', ' ') . ' kgs' : $na; ?></th>
                    </tr>
                </table>
            </div> <!-- FIN body carte -->
        </div> <!-- FIN carte -->
        <?php
    } // FIN boucle sur les lots en atelier

    exit;
} // FIN selection lot

// Save pvisu Apres
function modeSavePvisuApres() {

	global $cnx, $utilisateur;


	$pvisuApresManager = new PvisuApresManager($cnx);
	$pointsControleManager = new PointsControleManager($cnx);

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id > 0) {
		$pvisu = $pvisuApresManager->getPvisuApres($id);
		if (!$pvisu instanceof PvisuApres) { exit('-1'); }
	} else {
		$pvisu = $pvisuApresManager->getPvisuApresJour();
		if (!$pvisu instanceof PvisuApres) { exit('-2'); }
	}

	$commentaires   = isset($_REQUEST['commentaires']) ? strip_tags(trim($_REQUEST['commentaires'])) : '';

	$creation = false;

	// On crée le pvisu si besoin
	if ((int)$pvisu->getId() == 0) {

		$pvisu->setCommentaires($commentaires);
		$pvisu->setId_user($utilisateur->getId());
		$pvisu->setDate(date('Y-m-d'));

		$id_pvisu = $pvisuApresManager->savePvisuApres($pvisu);
		if ((int)$id_pvisu == 0) {
			exit('-5');
		}
		$pvisu->setId($id_pvisu);
		$creation = true;
	} else if ($commentaires != $pvisu->getCommentaires()) {
		// On met à jour le commentaire
		$pvisu->setCommentaires($commentaires);
		$pvisuApresManager->savePvisuApres($pvisu);

	} // FIn création pvisu

	$points         = isset($_REQUEST['point'])     && is_array($_REQUEST['point'])     ? $_REQUEST['point']    : [];
	$actions        = isset($_REQUEST['action'])    && is_array($_REQUEST['action'])    ? $_REQUEST['action']   : [] ;
	$fiches_nc      = isset($_REQUEST['fiche_nc'])  && is_array($_REQUEST['fiche_nc'])  ? $_REQUEST['fiche_nc'] : [] ;

	// On vérifie qu'on a bien le bon nombre de points de controles cochés
	if (empty($points)) { exit('-2');}
	$points_donnees = [];
	//$nbPoints = $pointsControleManager->getNbPointsControlesActifs(2);
	//if ($nbPoints != count($points)) { exit('-3'); }
	foreach ($points as $id_point => $etat) {

		$tmp = [];
		$tmp['etat'] = intval($etat);
		$tmp['id_pvisu_action'] = isset($actions[$id_point]) ? intval($actions[$id_point]) : 0;
		$tmp['fiche_nc'] = isset($fiches_nc[$id_point]) ? intval($fiches_nc[$id_point]) : 0;

		$points_donnees[$id_point] = $tmp;

	} // FIN boucle point



	// On intègre les points de contrôles
	if (!$pvisuApresManager->savePvisuApresPoints($pvisu, $points_donnees)) {
		// Si erreur on dé-signe le pvisu
		if ($creation) { $pvisuApresManager->savePvisuApres($pvisu); }
		exit('0');
	}

	exit('1');

} // FIN mode

// Save pvisu pendant
function modeSavePvisuPendant() {

    global $cnx, $utilisateur;

	$pvisuPendantManager = new PvisuPendantManager($cnx);
	$pointsControleManager = new PointsControleManager($cnx);
    $lotManager = new LotManager($cnx);

	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id > 0) {
		$pvisu = $pvisuPendantManager->getPvisuPendant($id);
    } else {
		$lot = $lotManager->getLot($id_lot);
		if (!$lot instanceof Lot) { exit('-4'); }
		$pvisu = $pvisuPendantManager->getPvisuPendantJourByLot($lot);
    }

	if (!$pvisu instanceof PvisuPendant) { exit('-1'); }

	$commentaires   = isset($_REQUEST['commentaires']) ? strip_tags(trim($_REQUEST['commentaires'])) : '';

    $creation = false;
	// On crée le pvisu si besoin
    if ((int)$pvisu->getId() == 0) {

		$pvisu->setCommentaires($commentaires);
		$pvisu->setId_user($utilisateur->getId());
		$pvisu->setId_lot($id_lot);
		$pvisu->setDate(date('Y-m-d'));
		$id_pvisu = $pvisuPendantManager->savePvisuPendant($pvisu);
		if ((int)$id_pvisu == 0) {
			exit('-5');
		}
		$pvisu->setId($id_pvisu);
		$creation = true;
	} else if ($commentaires != $pvisu->getCommentaires()) {
        // On met à jour le commentaire
		$pvisu->setCommentaires($commentaires);
		$pvisuPendantManager->savePvisuPendant($pvisu);

    } // FIn création pvisu

	$points         = isset($_REQUEST['point'])     && is_array($_REQUEST['point'])     ? $_REQUEST['point']    : [];
	$actions        = isset($_REQUEST['action'])    && is_array($_REQUEST['action'])    ? $_REQUEST['action']   : [] ;
	$fiches_nc      = isset($_REQUEST['fiche_nc'])  && is_array($_REQUEST['fiche_nc'])  ? $_REQUEST['fiche_nc'] : [] ;

	// On vérifie qu'on a bien le bon nombre de points de controles cochés
	if (empty($points)) { exit('-2');}
	$points_donnees = [];
	//$nbPoints = $pointsControleManager->getNbPointsControlesActifs(1);
	//if ($nbPoints != count($points)) { exit('-3'); }
	foreach ($points as $id_point => $etat) {

		$tmp = [];
		$tmp['etat'] = intval($etat);
		$tmp['id_pvisu_action'] = isset($actions[$id_point]) ? intval($actions[$id_point]) : 0;
		$tmp['fiche_nc'] = isset($fiches_nc[$id_point]) ? intval($fiches_nc[$id_point]) : 0;

		$points_donnees[$id_point] = $tmp;

    } // FIN boucle point


	// On intègre les points de contrôles
	if (!$pvisuPendantManager->savePvisuPendantPoints($pvisu, $points_donnees)) {
	    // Si erreur on dé-signe le pvisu
		if ($creation) { $pvisuPendantManager->supprPvisupendant($pvisu); }
	    exit('0');
	}

	exit('1');

} // FIN mode