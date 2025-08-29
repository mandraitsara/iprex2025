<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax PALETTES
------------------------------------------------------*/
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
$skipAuth   = isset($_REQUEST['skipAuth']);

// Intégration de la configuration du FrameWork et des autorisations

require_once '../php/config.php';

// Instanciation des Managers
$palettesManager = new PalettesManager($cnx);
$logsManager     = new LogManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------
MODE - Affiche la liste des palettes
------------------------------------*/
function modeShowListePalettes() {

	global
	$mode,
	$utilisateur,
	$palettesManager;




	$nbResultPpage      = 1000000;

	$page               = 1;
	$filtresPagination  = '?mode='.$mode;
	$start              = ($page-1) * $nbResultPpage;


	$filtre_debut   = isset($_REQUEST['filtre_debut'])  	? Outils::dateFrToSql($_REQUEST['filtre_debut'])    : '';
	$filtre_fin     = isset($_REQUEST['filtre_fin'])    	? Outils::dateFrToSql($_REQUEST['filtre_fin'])      : '';
	$filtre_numero	= isset($_REQUEST['filtre_numero']) 	? intval($_REQUEST['filtre_numero']) 				: 0;
	$filtre_clt 	= isset($_REQUEST['filtre_client']) 	? intval($_REQUEST['filtre_client']) 				: 0;
	$filtre_statut 	= isset($_REQUEST['filtre_statut']) && $_REQUEST['filtre_statut'] != '' ? intval($_REQUEST['filtre_statut']) : -1;

	$params = [];

	$params['vides'] 			= false;
	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;

	if ($filtre_numero > 0)	 { $params['numero'] 	= $filtre_numero; 	}
	if ($filtre_clt > 0) 	 { $params['id_client'] = $filtre_clt; 		}
	if ($filtre_statut > -1) { $params['statut']	= $filtre_statut; 	}
	if ($filtre_debut != '') { $params['debut']		= $filtre_debut; 	}
	if ($filtre_fin != '')   { $params['fin']		= $filtre_fin; 		}

	$listePalettes = $palettesManager->getListePalettes($params);

	// Si aucune palette a afficher
	if (empty($listePalettes)) { ?>

		<div class="alert alert-danger">
			<i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucune palette correspondant aux critères de recherche...</strong>
		</div>

		<?php
		// Sinon, affichage de la liste des palettes
	} else {

		// Liste non vide, construction de la pagination...
		$nbResults  = $palettesManager->getNb_results();
		$pagination = new Pagination($page);

		$pagination->setUrl($filtresPagination);
		$pagination->setNb_results($nbResults);
		$pagination->setAjax_function(true);
		$pagination->setNb_results_page($nbResultPpage);
		$pagination->setNb_apres(2);
		$pagination->getNb_avant(2);
		?>

		<div class="alert alert-danger d-md-none text-center">
			<i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
		</div>

		<table class="admin w-100 d-none d-md-table">
			<thead>
			<tr>
				<?php if ($utilisateur->isDev()) { ?> <th>ID</th> <?php } ?>
				<th>N° palette</th>
				<th>Poids</th>
				<th>Nb de colis</th>
				<th>Capacité maximale</th>
				<th>Disponible</th>
				<th>Création</th>
				<th>Opérateur</th>
				<th>Clients</th>
				<th>Produits</th>
				<th>Statut</th>
				<th class="t-actions w-court-admin-cell">Détails</th>
			</tr>
			</thead>
			<tbody>
			<?php
			// Boucle sur les palettes
			foreach ($listePalettes as $palette) {
				?>
				<tr>
				    <?php if ($utilisateur->isDev()) { ?> <td><code class="nowrap"><?php echo $palette->getId(); ?></td></code><?php } ?>
					<td><span class="badge badge-pill badge-secondary text-20"><?php echo $palette->getNumero();?></span></td>
                    <?php
                    // Si la palette est vide
                    if (empty($palette->getComposition())) { ?>

                        <td colspan="8">
                            <span class="badge badge-danger badge-pill text-14">Palette vide...</span>
                        </td>

                    <?php
                    // C'est bon y'a des trucs et des machins dans le bouzin... (ou peut-être même un bidule, ça m'étonnerais pas !!)
                    } else { ?>


		            <td class="nowrap"><?php $poidsPalette = $palettesManager->getPoidsTotalPalette($palette);  echo number_format($poidsPalette,3,'.', ' '); ?> kgs</td>
					<td><?php echo $palettesManager->getNbColisTotalPalette($palette); ?></td>
					<td class="nowrap"><?php
						$capacitePoids =  $palettesManager->getCapacitePalettePoids($palette);
						echo number_format($capacitePoids, 3 , '.', '');
						?> kgs</td>
					<td class="nowrap"><?php echo number_format($capacitePoids - $poidsPalette,3,'.', ' '); ?> kgs</td>
					<td><?php echo Outils::getDate_verbose($palette->getDate(), false, ' ', false, true);?></td>
					<td><?php echo $palette->getNom_user() == 'bot bot' ? 'SCAN' : $palette->getNom_user(); ?></td>
					<td><?php
						// On récupère la liste des clients de la palette (composition)
						$clients_palette = $palettesManager->getClientsPalette($palette);

						// Si aucun client
						if (!is_array($clients_palette) || empty($clients_palette)) { ?><span class="gris-9">Aucun</span><?php

						// Clients
						} else {

							// Un seul client, on affiche le nom, simplement
							if (count($clients_palette) == 1) {

								// On renvoie le premier élement du tableau (la clef est l'ID, on ne peut donc pas faire [0] : mais current() est un pointeur placé par défaut au premier élement)
								echo current($clients_palette);

							// Plusieurs client, on affiche une liste
							} else {
								$i = 0;
								foreach ($clients_palette as $clt_pal) { $i++; ?>
									<div class="badge badge-secondary text-16 form-control <?php echo $i == count($clients_palette) ? 'mb-0' : ''; ?>"><?php echo $clt_pal; ?></div>
									<?php
								} // FIN boucle clients de la palette
							} // FIN test plusieurs clients (mixte)
						} // FIN test clients de la palette
						?></td>
					<td><?php
						// On récupère la liste des produits de la palette (composition)
						$produits_palette = $palettesManager->getProduitsPalette($palette);

						// Si aucun produit
						if (!is_array($produits_palette) || empty($produits_palette)) { ?><span class="gris-9">Aucun</span><?php

						// Produits
						} else {

							// Un seul produit, on affiche le nom, simplement
							if (count($produits_palette) == 1) {

								// On renvoie le premier élement du tableau (la clef est l'ID, on ne peut donc pas faire [0] : mais current() est un pointeur placé par défaut au premier élement)
								echo current($produits_palette);

								// Plusieurs produits, on affiche une liste
							} else {
								$i = 0;
								foreach ($produits_palette as $pdt_pal) { $i++; ?>
									<div class="badge badge-info text-16 form-control <?php echo $i == count($produits_palette) ? 'mb-0' : ''; ?>"><?php echo $pdt_pal; ?></div>
									<?php
								} // FIN boucle produits de la palette
							} // FIN test plusieurs produits (mixte)
						} // FIN test produits de la palette
						?></td>
						<?php
					} // FIN test palette vide
					?>


					<td><?php echo $palette->getStatut_verbose(); ?></td>

					<td class="t-actions w-court-admin-cell"><button type="button" class="btn btn-sm btn-secondary btnEditPalette" data-id-palette="<?php
						echo $palette->getId(); ?>"><i class="fa fa-edit"></i> </button></td>
				</tr>
				<?php
			} // FIN boucle palettes ?>
			</tbody>
		</table>
		<?php
		// Pagination (aJax)
		if (isset($pagination)) {
			// Pagination bas de page, verbose...
			$pagination->setVerbose_pagination(1);
			$pagination->setVerbose_position('right');
			$pagination->setNature_resultats('palette');
			$pagination->setNb_apres(2);
			$pagination->setNb_avant(2);

			echo ($pagination->getPaginationHtml());
		} // FIN test pagination


	} // FIN test palettes à afficher
	exit;
} // FIN mode


/* ------------------------------------
MODE - Modale Palette (admin)
------------------------------------*/
function modeModalPalette() {

	global
	$cnx,
	$utilisateur,
	$palettesManager;


	$palette_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$palette    = $palette_id > 0 ? $palettesManager->getPalette($palette_id) : false;

	if (!$palette instanceof Palette) { exit; }

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) {
		$utilisateur = unserialize($_SESSION['logged_user']);
	}

	// Retour Titre
	echo '<i class="fa fa-pallet"></i> Palette N°'.$palette->getNumero();

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>

	<form class="container-fluid" id="formPaletteUpd">
		<input type="hidden" name="mode" value="savePalette"/>
		<input type="hidden" name="id_palette" value="<?php echo $palette_id; ?>"/>
		<div class="row">

            <!-- INFOS -->
			<div class="col-7 mb-3">
				Palette N°<span class="badge badge-pill badge-secondary text-20 ml-2 mr-2"><?php echo $palette->getNumero();?></span>
                <span class="gris-7 text-12 texte-fin">Numéro d'identification unique : #<?php echo $palette_id; ?></span>
			</div>
            <!-- FIN INFOS -->

            <!-- Statut -->
            <div class="col-5 input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">Statut</span>
                </div>
                <select class="selectpicker form-control show-tick" name="statut" id="changeStatutPalette" title="Statut" data-id-palette="<?php echo $palette_id; ?>">
                    <?php
                    foreach (Palette::STATUTS as $statut_id => $statut_verbose) { ?>
                        <option value="<?php echo $statut_id; ?>" <?php echo $statut_id == $palette->getStatut() ? 'selected' : ''; ?>><?php echo $statut_verbose; ?></option>
                    <?php }
                    ?>
                </select>
            </div>
            <!-- FIN Statut -->

		</div>

        <div class="row">
            <div class="col">

                <?php
                // Palette vide
                if (empty($palette->getComposition())) {


                    $palette->setSupprime(1);
                    $palettesManager->savePalette($palette);

                    ?>

                    <div class="alert alert-secondary text-center mt-3 text-20 padding-50">
                        <p>La palette est vide...</p>
                    </div>

                <?php
                // palette non vide, on affiche la composition
				} else { ?>

                    <table class="admin w-100">
                        <tr>
                            <th>Client</th>
                            <th>Produit</th>
                            <th>Poids</th>
                            <th>Nb colis</th>
                            <th class="t-actions w-mini-admin-cell text-center">Enregistrer</th>
                            <th class="t-actions w-mini-admin-cell text-center">Supprimer</th>
                        </tr>
                        <tbody>
						<?php
						// On boucle sur les compositions pour une modif en ligne
						foreach ($palette->getComposition() as $compo) { ?>
                            <tr data-id-palette-composition="<?php echo $compo->getId(); ?>">

                                <!-- Client -->
                                <td class="pl-0">
                                    <select class="selectpicker form-control show-tick" name="id_client" title="Client" data-live-search="true" data-live-search-placeholder="Rechercher">
										<?php
										$tiersManager = new TiersManager($cnx);
										$listeClients = $tiersManager->getListeClients([]);
										if (is_array($listeClients)) {

											foreach ($listeClients as $clt) { ?>
                                                <option value="<?php echo $clt->getId(); ?>" <?php echo $clt->getId() == $compo->getId_client() ? 'selected' : ''; ?>><?php echo $clt->getNom(); ?></option>
											<?php } // FIN boucle client
										} // FIN test liste client ok
										?>
                                    </select>
                                </td>

                                <!-- Produit -->
                                <td class="pl-0">
                                    <select class="selectpicker form-control show-tick" name="id_produit" title="Produit" data-live-search="true" data-live-search-placeholder="Rechercher">
										<?php
										$produitManager = new ProduitManager($cnx);
										$listeProduits = $produitManager->getListeProduits([]);
										if (is_array($listeProduits)) {

											foreach ($listeProduits as $pdt) { ?>
                                                <option value="<?php echo $pdt->getId(); ?>" <?php echo $pdt->getId() == $compo->getId_produit() ? 'selected' : ''; ?>><?php echo $pdt->getNom(); ?></option>
											<?php } // FIN boucle client
										} // FIN test liste client ok
										?>
                                    </select>
                                </td>

                                <!-- Poids -->
                                <td class="pl-0 w-court-admin-cell">
                                    <div class="input-group">
                                        <input type="text" class="form-control text-right" placeholder="Poids" name="poids" value="<?php echo $compo->getPoids()?>">
                                        <div class="input-group-append">
                                            <span class="input-group-text">kgs</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Colis -->
                                <td class="pl-0 w-court-admin-cell">
                                    <div class="input-group">
                                        <input type="text" class="form-control text-right" placeholder="Poids" name="nb_colis" value="<?php echo $compo->getNb_colis()?>">
                                        <div class="input-group-append">
                                            <span class="input-group-text">colis</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="t-actions">
                                    <button type="button" class="btn btn-success btnSaveCompo"><i class="fa fa-check"></i></button>
                                </td>
                                <td class="t-actions">
                                    <button type="button" class="btn btn-danger btnSupprCompo"><i class="fa fa-trash-alt"></i></button>
                                </td>

                            </tr>

						<?php } // FIN boucle compositions
						?>
                        </tbody>
                    </table>

                <?php
                } // FIN test composition palette

                ?>



            </div>
        </div>

	</form>

	<?php

	exit;
} // FIN mode





/* ------------------------------------
MODE - Change le statut d'une palette
------------------------------------*/
function modeChangeStatutPalette() {

    global $palettesManager, $logsManager;

    $id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette'])  : 0;
    $statut     = isset($_REQUEST['statut'])     ? intval($_REQUEST['statut'])      : 0;

    if ($id_palette == 0) { exit; }

    $palette = $palettesManager->getPalette($id_palette);

    if (!$palette instanceof Palette) { exit; }

    $palette->setStatut($statut);
    if (!$palettesManager->savePalette($palette)) { exit; }

	$log = new Log([]);
    $log->setLog_type('info');
    $log->setLog_texte("Modification du statut de la palette " . $id_palette . " à ". $statut);
	$logsManager->saveLog($log);

	echo '1'; // Retour positif CallBack Ajax
	exit;


} // FIN mode

/* ---------------------------------------------------------------
MODE - Modifie une ligne de  composition de palette (BackOffice)
----------------------------------------------------------------*/
function modeModifComposition() {

	global $palettesManager, $logsManager;

	$id_compo   = isset($_REQUEST['id_compo'])   ? intval($_REQUEST['id_compo'])    : 0;
	$id_client  = isset($_REQUEST['id_client'])  ? intval($_REQUEST['id_client'])   : 0;
	$id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit'])  : 0;
	$nb_colis   = isset($_REQUEST['nb_colis'])   ? intval($_REQUEST['nb_colis'])    : 0;
	$poids      = isset($_REQUEST['poids'])      ? floatval($_REQUEST['poids'])     : 0;

    if ($id_compo == 0 || $id_client == 0 || $id_produit == 0 || $nb_colis == 0 || $poids == 0) { exit; }

	$compo = $palettesManager->getComposition($id_compo);
	if (!$compo instanceof PaletteComposition) { exit; }

	$compo->setId_client($id_client);
	$compo->setId_produit($id_produit);
	$compo->setNb_colis($nb_colis);
	$compo->setPoids($poids);

	if (!$palettesManager->savePaletteComposition($compo)) { exit; }

	// On met à jour le statut de la palette si complète
	$palettesManager->updStatutPalette($compo->getId_palette());

	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Modification de la composition de palette " . $id_compo);
	$logsManager->saveLog($log);

	echo '1'; // Retour positif CallBack Ajax
    exit;

} // FIN mode


/* ---------------------------------------------------------------
MODE - Supprime une ligne de  composition de palette (BackOffice)
----------------------------------------------------------------*/
function modeSupprimeComposition() {

	global $palettesManager, $logsManager;

	$id_compo   = isset($_REQUEST['id_compo'])   ? intval($_REQUEST['id_compo'])    : 0;

	if ($id_compo == 0) { exit; }

	$compo = $palettesManager->getComposition($id_compo);
	if (!$compo instanceof PaletteComposition) { exit; }

	$compo->setSupprime(1);
	if (!$palettesManager->savePaletteComposition($compo)) { exit; }

	// On met à jour le statut de la palette si complète
	$palettesManager->updStatutPalette($compo->getId_palette());

	$log = new Log([]);
	$log->setLog_type('warning');
	$log->setLog_texte("Suppression (flag à 1) de la composition de palette " . $id_compo);
	$logsManager->saveLog($log);

	echo '1'; // Retour positif CallBack Ajax
	exit;

} // FIN mode

/* ---------------------------------------------------------------
MODE - Supprime une palette (BackOffice)
----------------------------------------------------------------*/
function modeSupprimePaletteVide() {

	global $palettesManager, $logsManager;

	$id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette'])  : 0;
	if ($id_palette == 0) { exit; }

	$palette = $palettesManager->getPalette($id_palette);

	if (!$palette instanceof Palette) { exit; }

	$palette->setSupprime(1);
	if (!$palettesManager->savePalette($palette)) { exit; }

	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Suppression (flag à 1) de la palette " . $id_palette);
	$logsManager->saveLog($log);

	echo '1'; // Retour positif CallBack Ajax
	exit;


} // FIN mode

/* ---------------------------------------
MODE - Modale selection palette (Front)
---------------------------------------*/
function modeSelectPaletteFront() {

	global $palettesManager, $cnx;

	// On récupère toutes les palettes qui sont en production (statut = 0) pour le produit en cours
	$params = ['statut' => 0, 'vides' => false, 'hors_frais' => true];

	$id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit']) : 0;
	if ($id_produit > 0) { $params['id_produit'] = $id_produit; }

    $palettes = $palettesManager->getListePalettes($params);
    ?>

    <div class="alert alert-danger confirmPaletteOverCapacite">
        <i class="fa fa-exclamation-circle fa-2x mb-2"></i>
        <h3 class="text-uppercase">Dépassement de capacité</h3>
        <p class="badge badge-warning text-24 alerteAvantDepassement">La palette
            <span class="infoConfirmPaletteNumero"><i class="fa fa-spin fa-spinner"></i></span>
            ne peut contenir plus que <span class="infoConfirmPalettePoids"><i class="fa fa-spin fa-spinner"></i></span> kgs.
        </p>
        <p>Confirmer le dépassement de <span class="infoConfirmPaletteOverPoids"><i class="fa fa-spin fa-spinner"></i></span> kgs ?</p>
        <button type="button" class="btn btn-danger btn-large padding-20-40 text-30 mr-3 btnConfirmPaletteOverAnnuler"><i class="fa fa-undo mr-2"></i>Annuler</button>
        <button type="button" class="btn btn-success btn-large padding-20-40 text-30 btnConfirmPaletteOverContinuer"><i class="fa fa-check mr-2"></i>Confirmer</button>
        <input type="hidden" class="idPaletteConfirmOverCapacite" value=""/>
    </div>


    <div class="row">
        <div class="col-10">
            <div class="row justify-content-center">

    <?php


    // Si pas ou plus de palettes
    if (empty($palettes)) { ?>

        <div class="alert alert-secondary text-center padding-50 margin-top-35 w-50">
            Aucune palette en cours...
        </div>

    <?php }

    // Boucle sur les palettes en préparation
    foreach ($palettes as $palette) {

		$poidsPalette  = $palettesManager->getPoidsTotalPalette($palette);
		$colisPalette  = $palettesManager->getNbColisTotalPalette($palette);
		$capacitePoids = $palettesManager->getCapacitePalettePoids($palette);
		$capaciteColis = $palettesManager->getCapacitePaletteColis($palette);


		$restantPoids  = floatval($capacitePoids - $poidsPalette);
		$restantColis  = intval($capaciteColis - $colisPalette);




		?>

        <div class="col-3 mb-3">
            <div class="card bg-dark text-white pointeur carte-palette"
                    data-id-palette="<?php echo $palette->getId();?>"
                    data-numero-palette="<?php echo $palette->getNumero();?>"
                    data-id-poids-restant="<?php echo $restantPoids; ?>"
                    data-id-colis-restant="<?php echo $restantColis; ?>">
                <div class="card-header text-center"><p class="mb-0 text-14 text-uppercase">Palette</p><span class="badge badge-info text-30"><?php echo $palette->getNumero(); ?></span></div>
                <div class="card-body padding-10">
                    <div class=""><?php
                        foreach($palettesManager->getClientsPalette($palette) as $clt_palette) { ?>
                            <div class="text-14 texte-fin"><?php echo $clt_palette; ?></div>
                        <?php } // FIN boucle clients de la palette
                        ?>
                    </div>
                    <div class="bg-secondary padding-5"><?php
						foreach($palettesManager->getProduitsPalette($palette) as $pdt_palette) { ?>
                            <div class="text-14 texte-fin"><?php echo $pdt_palette; ?></div>
						<?php } // FIN boucle produits de la palette
						?>
                    </div>
                    <div class="text-14">
                        <span class="mr-3"><?php echo number_format($poidsPalette,3,'.', ' '); ?> kgs</span>
                        <span><?php echo $colisPalette;?> colis</span>
                    </div>
                </div>
                <div class="card-footer padding-10 bg-secondary">

                    <?php if ($restantPoids <= 0 || $restantColis <= 0) { ?>

                        <p class="text-16 mb-0 text-warning">Capacité atteinte !</p>
                        <span class="mr-3 <?php echo $restantPoids < 0 ? 'text-warning' : ''?>"><?php

                            echo $restantPoids < 0 ? '+ '.number_format($restantPoids * -1,0) : number_format($restantPoids,0); ?> <span class="texte-fin text-14">kgs</span></span>
                        <span <?php echo $restantPoids < 0 ? 'class="text-warning"' : ''?>><?php echo $restantColis < 0 ? '+ ' . $restantColis * -1 : $restantColis; ?> <span class="texte-fin text-14">colis</span></span>

                    <?php } else { ?>
                        <p class="text-16 mb-0">Capacité restante :</p>
                        <span class="mr-3"><?php echo number_format($restantPoids,0); ?> <span class="texte-fin text-14">kgs</span></span>
                        <span><?php echo $restantColis; ?> <span class="texte-fin text-14">colis</span></span>
                    <?php } ?>

                </div>
            </div>
        </div>
        <?php

    } // FIN boucle sur les palettes en préparation
    ?>

           </div> <!-- FIN row -->
        </div> <!-- FIN col-10 -->

        <div class="col-2">
            <button type="button" class="btn btn-large btn-success form-control text-center text-20 pt-3 pb-3 btnNouvellePalette">
                <i class="fa fa-plus-square fa-2x"></i>
                <p class="mb-0 mt-2">Nouvelle palette</p>
            </button>
            <button type="button" class="btn btn-large btn-secondary form-control text-center text-20 pt-3 pb-3 mt-3 btnModifPaletteFront">
                <i class="fa fa-edit fa-2x"></i>
                <p class="mb-0 mt-2">Correction...</p>
            </button>
        </div>
    </div><!-- FIN row -->
    <?php
	exit;

} // FIN mode


/* -----------------------------------------------------
MODE - Affecte une nouvelle compo a une palette (Front)
------------------------------------------------------*/
function modeAffecteCompoPalette () {

	global $palettesManager, $utilisateur, $cnx;

	$id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette'])  : 0;
	if ($id_palette == 0) { exit('PK74ILIA'); }



	$palette = $palettesManager->getPalette($id_palette);
	if (!$palette instanceof Palette) { exit('G4G4RZCH'); }

	$id_produit         = isset($_REQUEST['id_produit'])        ? intval($_REQUEST['id_produit'])       : 0;
	$old_compo          = isset($_REQUEST['old_compo'])         ? intval($_REQUEST['old_compo'])        : 0;
	$id_client          = isset($_REQUEST['id_client'])         ? intval($_REQUEST['id_client'])        : 0;
	$nb_colis           = isset($_REQUEST['nb_colis'])          ? intval($_REQUEST['nb_colis'])         : 0;
	$poids              = isset($_REQUEST['poids'])             ? floatval($_REQUEST['poids'])          : 0;
	$quantite            = isset($_REQUEST['quantite'])             ? intval($_REQUEST['quantite']) : 0;
	$id_lot_pdt_froid   = isset($_REQUEST['id_lot_pdt_froid'])  ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
	$supprime           = isset($_REQUEST['supprime'])          ? intval($_REQUEST['supprime'])         : 1;

	if ($id_produit == 0 || $nb_colis == 0 || $poids == 0) { exit('DW7X24PN'); }

	// On utilise le premier client de la palette (sauf si passé en paramètre)
	if ($id_client == 0) {
	    foreach ($palettesManager->getClientsPalette($palette) as $id => $nom) { $id_client = $id; }
	}


	 // Si l'id client n'est toujours pas défini, et qu'on a une ancienne compo à abandonner, on le récupère de celle-ci
	if ($id_client == 0 && $old_compo > 0) {
	   $old_compo_obj = $palettesManager->getComposition($old_compo);
	   if ($old_compo_obj instanceof PaletteComposition) {
	       $id_client = $old_compo_obj->getId_client();
	   }
	}


	$compo = new PaletteComposition([]);
	$compo->setPoids($poids);	
	$compo->setNb_colis($nb_colis);
	$compo->setId_produit($id_produit);
	$compo->setId_client($id_client);
    $compo->setQuantite($quantite);
	$compo->setDate(date('Y-m-d H:i:s'));
	$compo->setId_user($utilisateur->getId());
	$compo->setId_palette($id_palette);
	$compo->setId_lot_pdt_froid($id_lot_pdt_froid);
	$compo->setSupprime($supprime);                     // On la définie comme supprimée par défaut en cas de mumuse sur la fiche si on clique sur plein de palettes, on viendra alors retirer ce flag à l'enregistrement



	$id_compo = $palettesManager->savePaletteComposition($compo);
	if (!$id_compo || intval($id_compo) == 0) { exit('GDG1IEN4'); }

	// Si la capacité de la palette est atteinte, on passe le statut à "complète" (1)
	$palette = $palettesManager->getPalette($id_palette); // On recharge pour avoir le poids du produit qu'on viens d'ajouter
	$capacite = intval($palettesManager->getCapacitePalettePoids($palette));
	$total = intval($palettesManager->getPoidsTotalPalette($palette));

	if ($total >= $capacite) {
		$palette->setStatut(1);
		$palettesManager->savePalette($palette);
    }

	// Enfin on supprime l'ancienne composition éventuelle (changement de palette, clic sur une palette étape 3)
	if ($old_compo > 0) {
	    $palettesManager->supprCompositionFromId($old_compo);
        $log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte("Suppression de la composition  #" . $old_compo . " suite changement de palette (clic sur palette étape 3) ") ;
		$logsManager = new LogManager($cnx);
		$logsManager->saveLog($log);

	}

	echo 'ok'.$id_palette.'|'.$id_compo;
	exit;

} // FIN mode


/* -----------------------------------------------------
MODE - Modale nouvelle palette (Front)
------------------------------------------------------*/
function modeModaleNouvellePalette() {

    global $cnx, $palettesManager;

    $produitManager = new ProduitManager($cnx);
	$tiersManager   = new TiersManager($cnx);

    $id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit']) : 0;
    if ($id_produit == 0) { exit("Erreur d'identification du produit !") ;}
    $produit = $produitManager->getProduit($id_produit);
    if (!$produit instanceof Produit) { exit("Erreur d'instanciation du produit !"); }


    $tous = isset($_REQUEST['tous']);
    ?>
    <div class="row">
        <div class="col-2">


            <?php if ($produit->isMixte()) { ?>

            <div class="mb-1 text-left text-16 text-center text-14">
                <i class="fa fa-info-circle gris-9 mr-1"></i>Produit « mixte »
            </div>

            <div class="alert alert-warning text-14 text-left">
                Ce produit est conditionnable sur palettes mixtes, le prochain numéro de palette dépend du client sélectionné.<br/><br/>Celui-ci est indiqué sous son nom&hellip;
            </div>

            <?php }  else { ?>
                <p class="mb-0 text-14 text-uppercase gris-5">Palette</p>
                <span class="badge badge-dark text-40" id="numeroNextPalette"><?php echo $produit->getPalette_suiv(); ?></span>
            <?php } ?>



        </div> <!-- FIN col gauche -->
        <div class="col-10">

            <div class="mb-1 text-left text-16">
                <i class="fa fa-address-card gris-9 mr-1"></i>Sélectionnez le client :
            </div>

            <div class="alert alert-secondary row">

                <?php
                // On récupère les clients visibles pour les palettes

                // Si le produit est exclusif à un client...
                if ($produit->getId_client() > 0) {
                    $client = $tiersManager->getTiers($produit->getId_client());
                    $liste_clients = [$client];
                } else {
                    $params = [];
                    // Si on a pas demandé à tous les voir, on filtre par visibilité
                    if (!$tous) { $params = ['visibilite_palettes' => 1]; }
                    $liste_clients  = $tiersManager->getListeClients($params);
                }

                if (empty($liste_clients)) { ?>

                    <div class="alert alert-danger">
                        <h3>Aucun client visible !</h3>
                        <p>Contactez un administrateur...</p>
                    </div>

                <?php } // FIN aucun client visible ! O_o


                // Boucle sur les clients
                foreach ($liste_clients as $clt) {

					if (strlen($clt->getNom()) > 28) {
						$taille_texte = 12;
                    } else if (strlen($clt->getNom()) > 22) {
						$taille_texte = 14;
					} else { $taille_texte = 16; }
                    ?>
                    <div class="col-3">
                        <button type="button" class="btn btn-secondary form-control mb-2 padding-20 btnClientNewPalette text-uppercase text-<?php echo $taille_texte;?>" data-id-client="<?php echo $clt->getId(); ?>" data-palette-suiv="<?php echo $clt->getPalette_suiv(); ?>">
							<?php echo $clt->getNom();

							   if ($produit->isMixte()) { ?>
							   	<p class="nomargin"><span class="badge badge-info text-16"><?php echo $clt->getPalette_suiv(); ?></span></p>
							   	<?php }
							?>
                        </button>
                    </div>

                <?php } // FIN boucle clients

                // Si on ne les affiche pas tous, on le propose
                if (!$tous && $produit->getId_client() == 0) {
                ?>
                <div class="col-3">
                    <button type="button" class="btn btn-dark form-control mb-2 padding-20 btnVoirTousClient text-uppercase">
						<i class="fa fa-plus-square fa-lg <?php echo $produit->isMixte() ? '' : 'mr-3' ;?>"></i><?php echo $produit->isMixte() ? '<br/>' : '' ;?>Voir plus...
                    </button>
                </div>
                <?php
                } // FIN affichage pas tous ?>

            </div> <!-- FIN alert -->

        </div> <!-- FIN col droite -->
    </div> <!-- FIN row -->

    <?php
    exit;

} // FIN mode



/* -----------------------------------------------------
MODE - Création nouvelle palette + 1ere compo
------------------------------------------------------*/
function modeCreationNouvellePalette() {

	global $cnx, $palettesManager, $utilisateur;

	$id_client      = isset($_REQUEST['id_client'])  ? intval($_REQUEST['id_client'])   : 0;
	$id_produit     = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit'])  : 0;    
	$nb_colis       = isset($_REQUEST['nb_colis'])   ? intval($_REQUEST['nb_colis'])    : 0;
	$poids          = isset($_REQUEST['poids'])      ? floatval($_REQUEST['poids'])     : 0.0;
	$quantite          = isset($_REQUEST['quantite'])      ? floatval($_REQUEST['quantite'])     : 0;
	$num_palette    = isset($_REQUEST['num_palette']) ? intval($_REQUEST['num_palette'])  : 0;
	$old_compo      = isset($_REQUEST['old_compo']) ? intval($_REQUEST['old_compo'])  : 0;
	$compoSupprime  = isset($_REQUEST['compo_supprime']) ? intval($_REQUEST['compo_supprime'])  : 0;
	$compoFrais     = isset($_REQUEST['frais']) ? intval($_REQUEST['frais'])  : 0;

	// Gestion des erreurs
    if ($id_client == 0 || $id_produit == 0 || $nb_colis == 0 || $num_palette == 0) {
        $codes = '-clt#'.$id_client.'-pdt#'.$id_produit.'-nbc='.$nb_colis.'-poids='.$poids.'-$nump='.$num_palette.'-quantite='.$quantite ;
        exit('FNH7PN1I'.$codes);
    }

    $palette = new Palette([]);
    $palette->setId_user($utilisateur->getId());
	$palette->setDate(date('Y-m-d H:i:s'));
	$palette->setStatut(0);
	$palette->setSupprime(0);
	$palette->setNumero($num_palette);
	if ($compoFrais == 1) {
	    $palette->setScan_frais(1);
	}

	// On enregistre la palette - gestion des erreurs
    $id_palette = $palettesManager->savePalette($palette);
	if (!$id_palette || intval($id_palette) == 0) { exit('V214TXSU'); }

	$palette->setId($id_palette);;

	// La palette est bien créé, on incrémente le numéro de la future palette en config
	$next_palette = $num_palette + 1;

	$produitManager = new ProduitManager($cnx);
    $produit = $produitManager->getProduit($id_produit);

	if (!$produit instanceof Produit) { exit('05N8DMHQ'); }

	// Si le numéro de palette est associé au client (produit mixte)
	if ($produit->isMixte()) {
        $tiersManager = new TiersManager($cnx);
        $client = $tiersManager->getTiers($id_client);
        if (!$client instanceof Tiers) { exit('2A0FRZYR/0'); }
        $client->setPalette_suiv($next_palette);
        if (!$tiersManager->saveTiers($client)) { exit('2A0FRZYR/1'); }

    // Si le numéro de palette est associé au produit
	} else {
	    $produit->setPalette_suiv($next_palette);
	    if (!$produitManager->saveProduit($produit)) { exit('2A0FRZYR/2'); }
	}

    // On enregistre la composition
    $compo = new PaletteComposition([]);
	$compo->setSupprime($compoSupprime);
	$compo->setDate(date('Y-m-d H:i:s'));
	$compo->setId_user($utilisateur->getId());
	$compo->setId_palette($id_palette);
	$compo->setId_produit($id_produit);
	$compo->setId_client($id_client);
	$compo->setNb_colis($nb_colis);
	$compo->setPoids($poids);

	$id_compo = $palettesManager->savePaletteComposition($compo);

	if (!$id_compo || intval($id_compo) == 0) { exit('LYKIDAEG'); }

	// Si le produit était associée à une précédente composition, on le retire...
	if ($old_compo > 0) {
	    $palettesManager->supprCompositionFromId($old_compo);
	    $log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte("Suppression de la composition  #" . $old_compo . " (produit associé à une précédente composition lors de la création d'une nouvelle palette) ") ;
		$logsManager = new LogManager($cnx);
		$logsManager->saveLog($log);
	}

    echo 'ok'.$id_palette.'|'.$id_compo; // Retour positif ajax

    // A présent on retourne le HTML pour la nouvelle carte
    echo '|'; // Séparateur callback ajax



    $capacitePoids = $produit instanceof Produit ? $produit->getPoids() * $produit->getNb_colis() : 0;
    $capaciteColis = $produit instanceof Produit ? $produit->getNb_colis() : 0;

    $colCss = $compoFrais == 1 ? '3' : '2';
    ?>
    <div class="col-<?php echo $colCss; ?> mb-2">
        <div class="card bg-dark text-white pointeur carte-palette carte-palette-nouvelle" id="cartepaletteid<?php echo $palette->getId(); ?>"
             data-id-palette="<?php echo $palette->getId();?>"
             data-id-client="<?php echo $id_client;?>"
             data-numero-palette="<?php echo $palette->getNumero();?>"
             data-id-poids-restant="999999"
             data-id-colis-restant="999999">

			<?php if ($utilisateur->isDev()) { ?>
                <div class="dev-id-info dev-info-left"><i class="fa fa-user-secret"></i><p class="texte-fin"><?php echo $palette->getId();?></p></div>
			<?php } ?>

            <div class="card-header">
                <div class="row">
                    <div class="col-5 text-center">
                        <span class="badge badge-info text-11 w-100">
                            <span class="text-uppercase texte-fin">Palette</span>
                            <div class="text-30"><?php echo $palette->getNumero(); ?></div>
                        </span>
                    </div>
                    <div class="col-7 text-center">

                            <div class="text-14 texte-fin text-uppercase"><?php

                                $tiersManager = new TiersManager($cnx);
                                $client = $tiersManager->getTiers($id_client);
                                if ($client instanceof Tiers) {
                                    echo $client->getNom();
                                }
                                ?></div>

                    </div>
                </div>
            </div>

            <div class="card-body padding-10">
                <div class="row text-14">
                <div class="col text-center">0.000 kgs</div>
                <div class="col text-center">0 colis</div>
            </div>
        </div>

        <div class="card-footer padding-10 bg-secondary">
            <div class="row">
                <div class="col-12 text-center text-16 text-capitalize">Capacité restante :</div>
                <div class="col-6 text-center text-18"><?php echo number_format($capacitePoids,0); ?> <span class="texte-fin text-14">kgs</span></div>
                 <div class="col-6 text-center text-18"><?php echo $capaciteColis; ?> <span class="texte-fin text-14">colis</span></div>
            </div>
        </div>
    </div>
    <?php

    exit;

} // FIN mode

/* -----------------------------------------------------
MODE - Création nouvelle palette + 1ere compo (NEGOCE)
------------------------------------------------------*/
function modeCreationNouvellePalettePdtNegoce() {

	global $cnx, $palettesManager, $utilisateur;

	$id_client          = isset($_REQUEST['id_client'])  ? intval($_REQUEST['id_client'])   : 0;
	$id_lot_pdt_negoce  = isset($_REQUEST['id_lot_pdt_negoce']) ? intval($_REQUEST['id_lot_pdt_negoce'])  : 0;
	$num_palette        = isset($_REQUEST['num_palette']) ? intval($_REQUEST['num_palette'])  : 0;

	// Gestion des erreurs
	if ($id_client == 0 || $id_lot_pdt_negoce == 0  || $num_palette == 0) { exit('FNH7PN1I'); }

	// Récupération du prochain numéro

	$lotsNegoceManager = new LotNegoceManager($cnx);
	$pdtNegoce = $lotsNegoceManager->getNegoceProduit($id_lot_pdt_negoce);
	if (!$pdtNegoce instanceof NegoceProduit) { exit('05N8DMHQ/0'); }

	$produitManager = new ProduitManager($cnx);
	$produit = $produitManager->getProduit($pdtNegoce->getId_pdt());
	if (!$produit instanceof Produit) { exit('05N8DMHQ/1'); }


	// Si le numéro de palette est associé au client (produit mixte)
	if ($produit->isMixte()) {
        $tiersManager = new TiersManager($cnx);
        $client = $tiersManager->getTiers($id_client);
        $numero = $client->getPalette_suiv();

    // Si le numéro de palette est associé au produit
	} else {
	    $numero = $produit->getPalette_suiv();
	}

	$palette = new Palette([]);
	$palette->setId_user($utilisateur->getId());
	$palette->setDate(date('Y-m-d H:i:s'));
	$palette->setStatut(0);
	$palette->setSupprime(0);
	$palette->setNumero($num_palette);

	// On enregistre la palette - gestion des erreurs
	$id_palette = $palettesManager->savePalette($palette);
	if (!$id_palette || intval($id_palette) == 0) { exit('V214TXSU'); }

	$palette->setId($id_palette);;

	// La palette est bien créé, on incrémente le numéro de la future palette

	$next_palette = $num_palette + 1;

	// Si le numéro de palette est associé au client (produit mixte)
	if ($produit->isMixte()) {
        if (!isset($tiersManager)) { $tiersManager = new TiersManager($cnx); }
	    if (!isset($client)) {  $client = $tiersManager->getTiers($id_client); }
        if (!$client instanceof Tiers) { exit('2A0FRZYR/0/1'); }

        $client->setPalette_suiv($next_palette);
        if (!$tiersManager->saveTiers($client)) { exit('2A0FRZYR/1/1'); }

    // Si le numéro de palette est associé au produit
	} else {
	    $produit->setPalette_suiv($next_palette);
	    if (!$produitManager->saveProduit($produit)) { exit('2A0FRZYR/2/1'); }
	}


	$lotsNegoceManager = new LotNegoceManager($cnx);
	$id_produit = $lotsNegoceManager->getIdProduitByNegoceProduit($id_lot_pdt_negoce);

	// On enregistre la composition
	$compo = new PaletteComposition([]);
	$compo->setSupprime(0);
	$compo->setDate(date('Y-m-d H:i:s'));
	$compo->setId_user($utilisateur->getId());
	$compo->setId_palette($id_palette);
	$compo->setId_produit($id_produit);
	$compo->setId_client($id_client);
	$compo->setId_lot_pdt_negoce($id_lot_pdt_negoce);

	$id_compo = $palettesManager->savePaletteComposition($compo);
	if (!$id_compo || intval($id_compo) == 0) { exit('LYKIDAEG'); }

	echo 'ok'.$id_palette.'|'.$id_client; // Retour positif ajax

	// A présent on retourne le HTML pour la nouvelle carte
	echo '|'; // Séparateur callback ajax

	$produitManager = new ProduitManager($cnx);
	$produit = $produitManager->getProduit($id_produit);
	$capacitePoids = $produit instanceof Produit ? $produit->getPoids() * $produit->getNb_colis() : 0;
	$capaciteColis = $produit instanceof Produit ? $produit->getNb_colis() : 0;

	?>
    <div class="col-3 mb-2">
        <div class="card bg-dark text-white pointeur carte-palette carte-palette-nouvelle" id="cartepaletteid<?php echo $palette->getId(); ?>"
             data-id-palette="<?php echo $palette->getId();?>"
             data-id-client="<?php echo $id_client;?>"
             data-numero-palette="<?php echo $palette->getNumero();?>"
             data-id-poids-restant="999999"
             data-id-colis-restant="999999">

			<?php if ($utilisateur->isDev()) { ?>
                <div class="dev-id-info dev-info-left"><i class="fa fa-user-secret"></i><p class="texte-fin"><?php echo $palette->getId();?></p></div>
			<?php } ?>

            <div class="card-header">
                <div class="row">
                    <div class="col-5 text-center">
                        <span class="badge badge-info text-11 w-100">
                            <span class="text-uppercase texte-fin">Palette</span>
                            <div class="text-30"><?php echo $palette->getNumero(); ?></div>
                        </span>
                    </div>
                    <div class="col-7 text-center">

                            <div class="text-14 texte-fin text-uppercase"><?php

                                $tiersManager = new TiersManager($cnx);
                                $client = $tiersManager->getTiers($id_client);
                                if ($client instanceof Tiers) {
                                    echo $client->getNom();
                                }
                                ?></div>

                    </div>
                </div>
            </div>

            <div class="card-body padding-10">
                <div class="row text-14">
                <div class="col text-center">0.000 kgs</div>
                <div class="col text-center">0 colis</div>
            </div>
        </div>

        <div class="card-footer padding-10 bg-secondary">
            <div class="row">
                <div class="col-12 text-center text-16 text-capitalize">Capacité restante :</div>
                <div class="col-6 text-center text-18"><?php echo number_format($capacitePoids,0); ?> <span class="texte-fin text-14">kgs</span></div>
                 <div class="col-6 text-center text-18"><?php echo $capaciteColis; ?> <span class="texte-fin text-14">colis</span></div>
            </div>
        </div>
    </div>
    <?php
    exit;
} // FIN mode

/* -----------------------------------------------------
MODE - Correction palettes (Front)
------------------------------------------------------*/
function modeModaleCorrectionsPalettes() {

    global $cnx, $utilisateur, $palettesManager;

    $id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;

    // Si pas de palette, étape 1 : sélection d'une palette
    if ($id_palette == 0) {

		// On récupère toutes les palettes qui sont en production (statut = 0)
		$params = ['statut' => 0, 'vides' => false, 'hors_frais' => true];
		$palettes = $palettesManager->getListePalettes($params);

        ?>

        <div class="row justify-content-md-center">
            <div class="col-12">
                <p>Choix de la palette à corriger :</p>
            </div>
            <?php

		// Boucle sur les palettes en préparation
		foreach ($palettes as $palette) { ?>

            <div class="col-2 mb-3">
                <button type="button" class="btn btn-large btn-dark w-100 padding-20-40 btnChoixPaletteCorrection" data-id-palette="<?php echo $palette->getId(); ?>">
                    <p class="nomargin text-uppercase text-white texte-fin">Palette</p>
                    <span class="badge badge-info text-30"><?php echo $palette->getNumero();?></span>
                </button>

            </div>

		<?php } // FIN boucle palettes
		?>
        </div>

    <?php
    // Si palette en paramètre, étape 2 : affichage du contenu et des options
    } else {

        // Instanciation de l'objet et gestion des erreurs
        $palette = $palettesManager->getPalette($id_palette);
        if (!$palette instanceof Palette) { ?>
            <strong>Identification de la palette impossible !</strong><br>Code erreur : QU5LI04V<code></code>
        <?php exit; } ?>
        <div class="row">
            <div class="col-2 vmiddle">
                Palette<span class="badge badge-info vmiddle text-30 ml-2"><?php echo $palette->getNumero(); ?></span>
            </div>
            <div class="col-5 mt-2">
                    <?php
                    $clients = $palettesManager->getClientsPalette($palette);
                    if (empty($clients)) { echo 'Aucun'; }
                    foreach ($clients as $clt) {
                        echo ' <span class="badge badge-dark text-20 ml-2">'. $clt . '</span>';
                    }
                    ?>
            </div>
            <div class="col-5 text-right">
                <button type="button" class="btn btn-info btn-large text-20 padding-20-40 btnChangerClientPalette" data-id-palette="<?php echo $palette->getId(); ?>"><i class="fa fa-retweet mr-2"></i>Changer client&hellip;</button>
                <button type="button" class="btn btn-secondary btn-large text-20 padding-20-40 ml-2 btnRetourPalettesCorrection"><i class="fa fa-arrow-left mr-2"></i>Retour</button>
            </div>
        </div>

        <?php
		// Si vide...
		if (empty($palette->getComposition())) { ?>
            <div class="alert alert-warning text-20 text-center mt-3">Palette vide...</div>

		<?php
		exit;
		} // FIN test palette vide
        ?>

        <div class="row">
            <div class="col-12">
                <table class="admin w-100 mt-2">
                    <tr>
                        <th class="text-left">Produit</th>
                        <th>Poids (kgs)</th>
                        <th>Nb de colis</th>
                        <th>Supprimer</th>
                        <th>Déplacer</th>
                    </tr>
                    <tbody>
                    <?php
                    // Boucle sur les compositions de la palette
                    foreach ($palette->getComposition() as $compo) { ?>

                        <tr data-id-compo="<?php echo $compo->getId(); ?>">
                            <td class="text-left text-18"><?php echo $compo->getNom_produit(); ?></td>
                            <td class="text-20"><?php echo number_format($compo->getPoids(),3,'.', ' ');?></td>
                            <td class="text-20"><?php echo $compo->getNb_colis();?></td>
                            <td>
                                <button type="button" class="btn btn-large btn-danger pl-3 pr-3 pt-2 pb-2 btnSupprimerCompo">
                                    <i class="fa fa-trash-alt fa-lg"></i>
                                </button>
                            </td>
                            <td>
                                <button type="button" class="btn btn-large btn-info pl-3 pr-3 pt-2 pb-2 btnDeplacerCompo">
                                    <i class="fa fa-exchange-alt fa-lg"></i>
                                </button>
                            </td>
                        </tr>

                    <?php } // FIN boucle compositions
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php

    } // FIN test palette identifiée (étape)

    exit;

} // FIN mode

/* -----------------------------------------------------
MODE - Change client palette - modale (front)
------------------------------------------------------*/
function modeModaleChangeClientPalette() {

	global $cnx, $utilisateur, $palettesManager;

	$id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
	if ($id_palette == 0) {   ?> <strong>Identification de la palette impossible !</strong><br>Code erreur : BMG566DT<code></code> <?php exit; }

	?>
    <div class="row justify-content-md-center">
        <div class="col-12">
            <p>Nouveau client :</p>
            <div class="row">

                <?php
                $tiersManager =  new TiersManager($cnx);
                $params = ['visibilite_palettes' => 1];
                $liste_clients  = $tiersManager->getListeClients($params);
                if (empty($liste_clients)) { ?>

                    <div class="alert alert-danger">
                        <h3>Aucun client visible !</h3>
                        <p>Contactez un administrateur...</p>
                    </div>

                <?php } // FIN aucun client visible ! O_o

                // Boucle sur les clients
                foreach ($liste_clients as $clt) {

					if (strlen($clt->getNom()) > 28) {
						$taille_texte = 12;
                    } else if (strlen($clt->getNom()) > 22) {
						$taille_texte = 14;
					} else { $taille_texte = 16; }
                    ?>
                    <div class="col-3">
                        <button type="button" class="btn btn-dark form-control mb-2 padding-20 btnClientPalette text-uppercase text-<?php echo $taille_texte;?>" data-id-client="<?php
                            echo $clt->getId(); ?>" data-id-palette="<?php
                            echo $id_palette; ?>">
							<?php echo $clt->getNom(); ?>
                        </button>
                    </div>

                <?php } // FIN boucle clients ?>


            </div>
        </div>
    </div>
    <?php

	exit;

} // FIN mode

/* -----------------------------------------------------
MODE - Change client palette - action (Front)
------------------------------------------------------*/
function modeChangeClientPalette() {

	global $cnx, $utilisateur, $palettesManager;

	$id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
	if ($id_palette == 0) {   ?> <strong>Identification de la palette impossible !</strong><br>Code erreur : PE7XX5IP<code></code> <?php exit; }

	$id_client  = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
	if ($id_palette == 0) {   ?> <strong>Identification de la palette impossible !</strong><br>Code erreur : 8ARYI7I4<code></code> <?php exit; }

	$palette = $palettesManager->getPalette($id_palette);
	if (!$palette instanceof Palette)  { ?> <strong>Instanciation de la palette impossible !</strong><br>Code erreur : KZ1PGXDQ<code></code> <?php exit; }

	echo $palettesManager->setClientPalette($palette, $id_client) ? 1 : '<strong>ERREUR de la mise à jour des données !</strong><br>Code erreur : 8DZR9NRB<code></code>';
	exit;


} // FIN mode

/* -----------------------------------------------------
MODE - Supprime une composition palette (Front)
------------------------------------------------------*/
function modeModaleCorrectionsPaletteSupprCompo() {

	global $cnx, $palettesManager;

	$id_compo = isset($_REQUEST['id_compo']) ? intval($_REQUEST['id_compo']) : 0;
	if ($id_compo == 0) { exit; }

	$compo = $palettesManager->getComposition($id_compo);
	if (!$compo instanceof PaletteComposition) { exit; }

	$compo->setSupprime(1);
	if (!$palettesManager->savePaletteComposition($compo)) { exit; }

	// On met à jour le statut de la palette si complète
	$palettesManager->updStatutPalette($compo->getId_palette());

	echo '1'; // Retour positif

	// Log
	$logManager = new LogManager($cnx);
	$log = new Log([]);
	$log->setLog_type('warning');
	$log->setLog_texte("Suppression (flag) de la composition ID ".$id_compo." de sa palette (Front)");
	$logManager->saveLog($log);

	exit;

} // FIN mode


/* -----------------------------------------------------
MODE - Supprime une composition palette (Front)
------------------------------------------------------*/
function modeDeplaceCompositionPalette() {

	global $cnx, $palettesManager;

	$id_compo = isset($_REQUEST['id_compo']) ? intval($_REQUEST['id_compo']) : 0;
	if ($id_compo == 0) { exit; }

	$compo = $palettesManager->getComposition($id_compo);
	if (!$compo instanceof PaletteComposition) { exit; }

	// SI on a sélectionné une palette (étape 2)
	$id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
	if ($id_palette > 0) {

	    $oldPalette = $compo->getId_palette();

		$compo->setId_palette($id_palette);
		$palettesManager->savePaletteComposition($compo);

		// On met à jour le statut de la palette si complète
		$palettesManager->updStatutPalette($id_palette);

		// Log
		$logManager = new LogManager($cnx);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte("Déplacement de la composition #".$id_compo." de la palette #".$oldPalette." vers la palette #".$id_palette." (Front)");
		$logManager->saveLog($log);

	    exit;
    } // FIN étape 2

    // On récupère toutes les palettes qui sont en production (statut = 0)
    $params = ['statut' => 0, 'vides' => false, 'hors_frais' => true];
    $palettes = $palettesManager->getListePalettes($params);

    ?>

    <div class="row justify-content-md-center">
        <div class="col-12">
            <p>Choix de la palette de destination :</p>
        </div>
        <?php

        // Boucle sur les palettes en préparation
        foreach ($palettes as $palette) {

            // Si c'est la même palette, on passe...
            if ($palette->getId() == $compo->getId_palette()) { continue; }
            ?>

            <div class="col-2 mb-3">
                <button type="button" class="btn btn-large btn-dark w-100 padding-20-40 btnChoixPaletteDeplacerCompo" data-id-palette="<?php echo $palette->getId(); ?>" data-id-compo="<?php echo $id_compo; ?>">
                    <p class="nomargin text-uppercase text-white texte-fin">Palette</p>
                    <span class="badge badge-info text-30"><?php echo $palette->getNumero();?></span>
                </button>

            </div>

        <?php } // FIN boucle palettes
        ?>
    </div>
    <?php
    exit;

} // FIN mode

/* ------------------------------------------
MODE - Export en PDF
-------------------------------------------*/
function modeExportPdf() {

	global $tiersManager;

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = genereContenuPdf();
	$content .= ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/iprexplt-*.pdf') as $fichier) {
		unlink($fichier);
	}

	try {
		$nom_fichier = 'iprexplt-'.date('is').'.pdf';
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15');
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

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML pour le PDF
-----------------------------------------------------------------------------*/
function genereContenuPdf() {

	global $cnx, $palettesManager;

	// HEAD
	$contenu = '<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style type="text/css">
  
    * { margin:0; padding: 0; }
  
    .header { border-bottom: 2px solid #ccc; }
    .header img.logo { width: 200px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .table { border-collapse: collapse; }
    .table-donnees th { font-size: 11px; }
    .table-liste th { font-size: 9px; background-color: #d5d5d5; padding:3px; }
    .table-liste td { font-size: 9px; padding-top: 3px; padding-bottom: 3px; border-bottom: 1px solid #ccc;}
    .table-liste td.no-bb { border-bottom: none; padding-bottom: 0px; }
    .titre {
       background-color: teal;
       color: #fff;
       padding: 3px;
       text-align: center;
       font-weight: normal;
       font-size: 14px;
    }
    .recap {
       background-color: #ccc;
       padding: 3px;
       text-align: center;
       font-weight: normal;
       font-size: 10px;
    }
    
    .w100 { width: 100%; }
    .w75 { width: 75%; }
    .w50 { width: 50%; }
    .w40 { width: 40%; }
    .w25 { width: 25%; }
    .w33 { width: 33%; }
    .w34 { width: 34%; }
    .w20 { width: 20%; }
    .w30 { width: 30%; }
    .w15 { width: 15%; }
    .w35 { width: 35%; }
    .w5 { width: 5%; }
    .w10 { width: 10%; }
    .w15 { width: 15%; }
    
    .text-6 { font-size: 6px; }
    .text-7 { font-size: 7px; }
    .text-8 { font-size: 8px; }
    .text-9 { font-size: 9px; }
    .text-10 { font-size: 10px; }
    .text-11 { font-size: 11px; }
    .text-12 { font-size: 12px; }
    .text-14 { font-size: 14px; }
    .text-16 { font-size: 16px; }
    .text-18 { font-size: 18px; }
    .text-20 { font-size: 20px; }
    
    .gris-3 { color:#333; }
    .gris-5 { color:#555; }
    .gris-7 { color:#777; }
    .gris-9 { color:#999; }
    .gris-c { color:#ccc; }
    .gris-d { color:#d5d5d5; }
    .gris-e { color:#e5e5e5; }
    
    .mt-0 { margin-top: 0px; }
    .mt-2 { margin-top: 2px; }
    .mt-5 { margin-top: 5px; }
    .mt-10 { margin-top: 10px; }
    .mt-15 { margin-top: 15px; }
    .mt-20 { margin-top: 20px; }
    .mt-25 { margin-top: 25px; }
    .mt-50 { margin-top: 50px; }
    
    .mb-0 { margin-bottom: 0px; }
    .mb-2 { margin-bottom: 2px; }
    .mb-5 { margin-bottom: 5px; }
    .mb-10 { margin-bottom: 10px; }
    .mb-15 { margin-bottom: 15px; }
    .mb-20 { margin-bottom: 20px; }
    .mb-25 { margin-bottom: 25px; }
    .mb-50 { margin-bottom: 50px; }
    
    .mr-0 { margin-right: 0px; }
    .mr-2 { margin-right: 2px; }
    .mr-5 { margin-right: 5px; }
    .mr-10 { margin-right: 10px; }
    .mr-15 { margin-right: 15px; }
    .mr-20 { margin-right: 20px; }
    .mr-25 { margin-right: 25px; }
    .mr-50 { margin-right: 50px; }
    
    .ml-0 { margin-left: 0px; }
    .ml-2 { margin-left: 2px; }
    .ml-5 { margin-left: 5px; }
    .ml-10 { margin-left: 10px; }
    .ml-15 { margin-left: 15px; }
    .ml-20 { margin-left: 20px; }
    .ml-25 { margin-left: 25px; }
    .ml-50 { margin-left: 50px; }
    
    .pt-0 { padding-top: 0px; }
    .pt-2 { padding-top: 2px; }
    .pt-5 { padding-top: 5px; }
    .pt-10 { padding-top: 10px; }
    .pt-15 { padding-top: 15px; }
    .pt-20 { padding-top: 20px; }
    .pt-25 { padding-top: 25px; }
    .pt-50 { padding-top: 50px; }
    
    .pb-0 { padding-bottom: 0px; }
    .pb-2 { padding-bottom: 2px; }
    .pb-5 { padding-bottom: 5px; }
    .pb-10 { padding-bottom: 10px; }
    .pb-15 { padding-bottom: 15px; }
    .pb-20 { padding-bottom: 20px; }
    .pb-25 { padding-bottom: 25px; }
    .pb-50 { padding-bottom: 50px; }
    
    .pr-0 { padding-right: 0px; }
    .pr-2 { padding-right: 2px; }
    .pr-5 { padding-right: 5px; }
    .pr-10 { padding-right: 10px; }
    .pr-15 { padding-right: 15px; }
    .pr-20 { padding-right: 20px; }
    .pr-25 { padding-right: 25px; }
    .pr-50 { padding-right: 50px; }
    
    .pl-0 { padding-left: 0px; }
    .pl-2 { padding-left: 2px; }
    .pl-5 { padding-left: 5px; }
    .pl-10 { padding-left: 10px; }
    .pl-15 { padding-left: 15px; }
    .pl-20 { padding-left: 20px; }
    .pl-25 { padding-left: 25px; }
    .pl-50 { padding-left: 50px; }
    
    .text-danger { color: #d9534f; }
    
  </style> 
</head>
<body>';

	$contenu.=  genereEntetePagePdf();

	// PAGE 1

	// GENERAL

	// Préparation des variables
	$na             = '<span class="gris-9 text-11"><i>Non renseigné</i></span>';
	$tiret          = '<span class="gris-9 text-11"><i>-</i></span>';

	// Préparation des variables
	$params = [];
	$liste = $palettesManager->getListePalettes($params);


	// Génération du contenu HTML
	$contenu.= '<table class="table table-liste w100 mt-10">';

	// Aucun frs
	if (empty($liste)) {

		$contenu.= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucune palette</i></td></tr>';

		// Liste des Frs
	} else {

		$contenu.= '<tr>
                        <th class="w10 text-center">N° palette</th>
                        <th class="w10 text-right">Poids (kgs)</th>
                        <th class="w10 text-center">Nb de colis</th>
                        <th class="w10 text-right">Capacité maximale</th>
                        <th class="w10 text-right pr-15">Disponible (kgs)</th>
                        <th class="w20">Client</th>
                        <th class="w20">Produits</th>
                        <th class="w10">Statut</th>
                    </tr>';

		foreach ($liste as $item) {

			$contenu.= '<tr>
                            <td class="w10 text-center">'.$item->getNumero().'</td>';


		   if (empty($item->getComposition())) {
			   $contenu.= '<td colspan="7">Palette vide...</td></tr>';
			   continue;
           }
			$poidsPalette =$palettesManager->getPoidsTotalPalette($item);
			$capacitePoids = $palettesManager->getCapacitePalettePoids($item);


			$clients_palette = $palettesManager->getClientsPalette($item);

			$clients = '';

			// Si aucun client
			if (!is_array($clients_palette) || empty($clients_palette)) {
				// Clients
			} else {

				// Un seul client, on affiche le nom, simplement
				if (count($clients_palette) == 1) {

					// On renvoie le premier élement du tableau (la clef est l'ID, on ne peut donc pas faire [0] : mais current() est un pointeur placé par défaut au premier élement)
					$clients = current($clients_palette);

					// Plusieurs client, on affiche une liste
				} else {
					$i = 0;
					foreach ($clients_palette as $clt_pal) { $i++;
						$clients.= $clt_pal . ', ';

					} // FIN boucle clients de la palette
				} // FIN test plusieurs clients (mixte)
			} // FIN test clients de la palette

            if (count($clients_palette) > 1) {
                $clients = substr($clients,0,-2);
            }

			// On récupère la liste des produits de la palette (composition)
			$produits_palette = $palettesManager->getProduitsPalette($item);

            $pdts = '';

			// Si aucun produit
			if (!is_array($produits_palette) || empty($produits_palette)) {

				// Produits
			} else {

				// Un seul produit, on affiche le nom, simplement
				if (count($produits_palette) == 1) {

					// On renvoie le premier élement du tableau (la clef est l'ID, on ne peut donc pas faire [0] : mais current() est un pointeur placé par défaut au premier élement)
					$pdts = current($produits_palette);

					// Plusieurs produits, on affiche une liste
				} else {
					$i = 0;
					foreach ($produits_palette as $pdt_pal) { $i++;
						$pdts.= $pdt_pal . ', ';
					} // FIN boucle produits de la palette
				} // FIN test plusieurs produits (mixte)
			} // FIN test produits de la palette

			if (count($produits_palette) > 1) {
				$pdts = substr($pdts,0,-2);
			}


			$contenu.= '
                            <td class="w10 text-right">'.number_format($poidsPalette,3,'.', ' ').'</td>
                            <td class="w10 text-center">'.  $palettesManager->getNbColisTotalPalette($item).'</td>
                            <td class="w10 text-right">'.number_format($capacitePoids, 3 , '.', '').'</td>
                            <td class="w10 text-right pr-15">'.number_format($capacitePoids - $poidsPalette,3,'.', ' ').'</td>
                            <td class="w20">'.$clients.'</td>
                            <td class="w20">'.$pdts.'</td>
                            <td class="w10">'.$item->getStatut_verbose().'</td>
                        </tr>';

		} // FIN boucle sur les produits


	} // FIN test produits

	$contenu.= '</table>';

	$contenu.= '<table class="table w100 mt-15"><tr><th class="w100 recap">Nombre de palettes : '. count($liste) .'</th></tr></table>';
	// FOOTER
	$contenu.= '<table class="w100 gris-9">
                    <tr>
                        <td class="w50 text-8">Document édité le '.date('d/m/Y').' à '.date('H:i:s').'</td>
                        <td class="w50 text-right text-6">&copy; 2019 IPREX / INTERSED </td>
                    </tr>
                </table>
            </body>
        </html>';



	// RETOUR CONTENU
	return $contenu;

} // FIN fonction déportée

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le header du PDF (logo...)
-----------------------------------------------------------------------------*/
function genereEntetePagePdf() {

	global $cnx;

	$entete = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            Liste des palettes au '.date("d/m/Y").'
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-12 gris-7">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>';

	return $entete;

} // FIN fonction déportée

/* ----------------------------------------------------------------------------
MODE affiche les palettes complètes (Front)
-----------------------------------------------------------------------------*/
function modeListePalettesCompletes() {

    global $cnx, $utilisateur, $palettesManager;

    $id_pdt = isset($_REQUEST['id_pdt']) ? intval($_REQUEST['id_pdt']) : 0;
    if ($id_pdt == 0) { exit; }

    $negoce = isset($_REQUEST['negoce']);
    $frais  = isset($_REQUEST['frais']);
    $col = $negoce || $frais ? 3 : 2;

    // Si le produit est "mixte", on filtre sur les palettes mixtes
    $pdtManager = new ProduitManager($cnx);

	$pdt = $pdtManager->getProduit($id_pdt);
	if (!$pdt instanceof Produit) { exit; }

    echo 'ok~'; // Pour Append

    $params = [
        'vides'      => false,
        'statuts'      => '1,2',         // 1 = complètes / 2 = Clôturée
        'id_produit' => $id_pdt,
        'mixte' => $pdt->isMixte(),
        'hors_frais' => true
    ];
    $palettes = $palettesManager->getListePalettes($params);
    if (empty($palettes)) { ?>

        <div class="col-<?php echo $col; ?>">
            <div class="alert alert-secondary text-center padding-50 w-100 text-18 gris-9">
                Aucune palette<br><?php echo $pdt->isMixte() ? 'mixte complète' : 'complète pour ce produit' ; ?>...
            </div>
        </div>

    <?php }

    foreach ($palettes as $palette) {

        $poidsPalette  = $palettesManager->getPoidsTotalPalette($palette);
        $colisPalette  = $palettesManager->getNbColisTotalPalette($palette);
        $capacitePoids = $palettesManager->getCapacitePalettePoids($palette);
        $capaciteColis = $palettesManager->getCapacitePaletteColis($palette);

        $restantPoids  = floatval($capacitePoids - $poidsPalette);
        $restantColis  = intval($capaciteColis - $colisPalette);

        ?>

        <div class="col-<?php echo $col; ?> mb-2">
            <div class="card bg-danger text-white pointeur carte-palette carte-palette-complete" id="cartepaletteid<?php echo $palette->getId(); ?>"
                 data-id-palette="<?php echo $palette->getId();?>"
                 data-id-client="0"
                 data-numero-palette="<?php echo $palette->getNumero();?>"
                 data-id-poids-restant="<?php echo $restantPoids; ?>"
                 data-id-colis-restant="<?php echo $restantColis; ?>">

                <?php if ($utilisateur->isDev()) { ?>
                    <div class="dev-id-info dev-info-left"><i class="fa fa-user-secret"></i><p class="texte-fin"><?php echo $palette->getId();?></p></div>
                <?php } ?>

                <div class="card-header">
                    <div class="row">
                        <div class="col-5 text-center">
                                    <span class="badge badge-info text-11 w-100">
                                        <span class="text-uppercase texte-fin">Palette</span>
                                        <div class="text-30"><?php echo $palette->getNumero(); ?></div>
                                    </span>
                        </div>
                        <div class="col-7 text-center">
                            <?php
                            foreach($palettesManager->getClientsPalette($palette) as $clt_palette) { ?>
                                <div class="text-14 texte-fin"><?php echo $clt_palette; ?></div>
                            <?php } // FIN boucle clients de la palette
                            ?>
                        </div>
                    </div>
                </div>

                <div class="card-body padding-10">
                    <div class="row text-14"">
                        <div class="col text-center">
                            <?php echo number_format($poidsPalette,3,'.', ' '); ?> kgs
                        </div>
                        <div class="col text-center">
                            <?php echo $colisPalette;?> colis
                        </div>
                    </div>
                </div>

                <div class="card-footer padding-10 bg-danger">
                    <div class="row">
                            <div class="col-12 text-center text-16"><?php echo $palette->getStatut() == 1 ? 'Capacité atteinte' : 'Palette clôturée'; ?></div>
                            <div class="col-6 text-center text-18">
                                <?php echo $restantPoids < 0 ? '+ '.number_format($restantPoids * -1,0) : number_format($restantPoids,0); ?> <span class="texte-fin text-14">kgs</span>
                            </div>
                            <div class="col-6 text-center text-18">
                                <?php echo $restantColis < 0 ? '+ ' . $restantColis * -1 : $restantColis; ?> <span class="texte-fin text-14">colis</span>
                            </div>

                    </div>
                </div>
            </div>
        </div>
        <?php

    } // FIN boucle sur les palettes en préparation

} // FIN mode

/* ----------------------------------------------------------------------------
MODE Néttoie les composition de palettes abandonnées
    Une composition est crée avant l'enregistrement du produit,
    Si l'opérateur quitte la page avant d'enregistrer un nouveau produit au traitement
    on peut avoir des compos sans id_lot_pdt_froid associé... ici on les nettoie.
-----------------------------------------------------------------------------*/
function modeCleanCompoPalettesVides() {

    global $palettesManager, $utilisateur;

    $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;

    // Attention un autre opérateur peut être en train de modifier un produit sur un autre poste et donc avoir des compo vides temporaire de façon tout à fait légitime !...
    // Pour ça, on ne vire que celles créées par l'utilisateur actuel

    // Si on réussi a supprimer les compos, on supprime ensuite les palettes vides
    if ($palettesManager->cleanCompoPalettesVides($utilisateur)) {
         $palettesManager->cleanPalettesVides($utilisateur);
    }

    exit;

} // FIN mode

// Clôture une palette (front)
function modeCloturePalette() {

      global $palettesManager, $logsManager;

      $id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
      if ($id_palette == 0) { exit; }

      $palette = $palettesManager->getPalette($id_palette);
      if (!$palette instanceof Palette) { exit; }

      $palette->setStatut(2); // 2 = Clôturée (Anciennement "Expédié")
      $palettesManager->savePalette($palette);

      $log = new Log([]);
      $log->setLog_type('info');
      $log->setLog_texte("Clôture de la palette ID " . $id_palette );
	  $logsManager->saveLog($log);

      exit;

} // FIN mode


// Enregistre le type de poids palette (BL)
function modeSetPoidsPalette() {

    global $palettesManager;

      $id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
      if ($id_palette == 0) { exit('-1'); }

      $id_type_pp = isset($_REQUEST['id_type_pp']) ? intval($_REQUEST['id_type_pp']) : 0;
      if ($id_type_pp == 0) { exit('-2'); }

      $palette = $palettesManager->getPalette($id_palette);
      if (!$palette instanceof Palette) { exit('-3'); }

      $palette->setId_poids_palette($id_type_pp);
      echo $palettesManager->savePalette($palette) ? 1 : 0;
      exit;



} // FIN mode

// Affiche la modale pour sélection des emballages associés à une palette (BL)
function modeShowModaleEmballagesPalette() {

    global $cnx;

    $id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
    if ($id_palette == 0) { exit('-1'); }

    $poidsPaletteManager = new PoidsPaletteManager($cnx);

    $palettePoidsPalette = $poidsPaletteManager->getPalettePoidsPaletteByPalette($id_palette);

    ?>
    <input type="hidden" name="mode" value="saveEmballagesPalette"/>
    <input type="hidden" name="id_palette" value="<?php echo $id_palette; ?>"/>
    <?php

    $listePoids = $poidsPaletteManager->getListePoidsPalettes(0); // 0 pour n'avoir que les emballages et pas les poids de palettes en elles-mêmes
    foreach ($listePoids as $pp) {

        $qte = isset($palettePoidsPalette[$pp->getId()]) ? intval($palettePoidsPalette[$pp->getId()]) : 0;
        ?>

        <div class="row mb-1">
            <div class="col-3">
                <select class="selectpicker form-control" title="Aucun" data-size="8" name="nb[<?php echo $pp->getId();?>]">
                <option value="0">Aucun</option>
                 <option data-divider="true"></option>
                    <?php
                    for ($i = 1; $i < 100; $i ++) { ?>
                        <option value="<?php echo $i; ?>" <?php
                        echo $qte == $i ? 'selected' : '';
                        ?>><?php echo $i; ?></option>
                    <?php }
                    ?>
                </select>
            </div>
            <div class="col padding-top-5">
                <?php echo $pp->getNom(); ?>
            </div>
        </div>

    <?php }
    exit;

} // FIN mode

// Enregistre les emballages d'une palette pour le calcul du poids (BL)
function modeSaveEmballagesPalette() {

    global $palettesManager, $cnx;

    $id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
    if ($id_palette == 0) { exit('-1'); }

    $nb = isset($_REQUEST['nb']) ? $_REQUEST['nb'] : '';

    if (empty($nb) || !is_array($nb)) { exit('-2'); }

    echo $palettesManager->savePoidsPalette($id_palette, $nb) ? 1 : 0;
    exit;

} // FIN mode

// Retourne le poids de la palette (emballage/colis/palette), hors produits
function modeGetPoidsPalettePoids() {

    global $cnx;

    $id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
    if ($id_palette == 0) { exit('-1'); }

    $poidsPalettesManager = new PoidsPaletteManager($cnx);

    echo $poidsPalettesManager->getTotalPoidsPalette($id_palette);
    exit;

} // FIN mode


/* -----------------------------------------------------
MODE - Création nouvelle palette vide (FRAIS)
------------------------------------------------------*/
function modeCreationNouvellePaletteFrais() {

	global $cnx, $palettesManager, $utilisateur;

	$id_client          = isset($_REQUEST['id_client'])  ? intval($_REQUEST['id_client'])   : 0;

	// Gestion des erreurs
	if ($id_client == 0) { exit('FNH7PN1I2'); }

	// Récupération du prochain numéro
    $tiersManager = new TiersManager($cnx);
	$client = $tiersManager->getTiers($id_client);
	$num_palette = $client->getPalette_suiv();

    if (!$utilisateur instanceof User) {
        $userManager = new UserManager($cnx);
        $utilisateur = $userManager->getUserBot();
    }


	$palette = new Palette([]);
	$palette->setId_user($utilisateur->getId());
	$palette->setDate(date('Y-m-d H:i:s'));
	$palette->setStatut(0);
	$palette->setScan_frais(1);
	$palette->setSupprime(0);
	$palette->setNumero($num_palette);
	$palette->setId_client($id_client);

	// On enregistre la palette - gestion des erreurs
	$id_palette = $palettesManager->savePalette($palette);
	if (!$id_palette || intval($id_palette) == 0) { exit('V214TXSU2'); }

	$palette->setId($id_palette);;

	// La palette est bien créé, on incrémente le numéro de la future palette

	$next_palette = $num_palette + 1;

	// Si le numéro de palette est associé au client (produit mixte)
    $client->setPalette_suiv($next_palette);
    if (!$tiersManager->saveTiers($client)) { exit('2A0FRZYR/1/1'); }
	exit;
} // FIN mode


function modeCheckNumeroPaletteExiste() {
    global $palettesManager;

    $num_palette = isset($_REQUEST['palette']) ? intval($_REQUEST['palette']) : 0;
    $id_client = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
    if ($num_palette == 0) { exit; }
    
    echo $palettesManager->checkNumeroPaletteExiste($num_palette, $id_client);
    exit;

} // FIN mode