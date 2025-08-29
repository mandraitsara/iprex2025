<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax NEGOCE
------------------------------------------------------*/

// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

$lotsNegoceManager = new LotNegoceManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------------
FONCTION - Message d'erreur standard
-------------------------------------------*/
function erreur() {
	?>
	<div class="alert alert-danger text-center">
		<i class="fa fa-exclamation-triangle fa-3x mb-2"></i><br>
		<p>Erreur de récupération des données !</p>
	</div>
	<?php
	exit;
} // FIN fonctions


/* ------------------------------------------
MODE - Charge le contenu d'une étape de vue
-------------------------------------------*/
function    modeChargeEtapeVue() {

	global $cnx, $lotsNegoceManager, $utilisateur;

	$etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;


	/** ----------------------------------------
	 * DEV - On affiche l'étape pour débug
	 *  ----------------------------------- */
	if ($utilisateur->isDev()) { ?>
		<div class="dev-etape-vue"><i class="fa fa-user-secret fa-lg mr-1"></i>Etape <kbd><?php echo $etape;?></kbd></div>
	<?php } // FIN test DEV

	/** ----------------------------------------
	 * Etape        : 1
	 * Description  : Liste des produits
	 *  ----------------------------------- */

	if ($etape == 1) {

		// Liste des lots de négoce non expédiés mais reçus
		$liste = $lotsNegoceManager->getListeLotNegoces(['statut' => 1, 'receptionnes' => true, 'get_nb_traites' => true]);
	

		// Si aucun lot...
	    if(empty($liste)) { ?>

            <div class="row mt-3">
                <div class="col">
                    <div class="alert alert-secondary text-center padding-50">
                        <i class="fa fa-exclamation-circle gris-9 fa-3x mb-2"></i>
                        <p class="nomargin text-18 gris-7">Aucun lot de négoce réceptionné non expédié&hellip;</p>
                    </div>
                </div>
            </div>
	        <?php exit;
        } // FIN aucun lot
        ?>
        <div class="row mt-3">
            <div class="col">
        <?php

		$na = '<span class="badge badge-warning badge-pill text-14">Non renseigné !</span>';

        // Liste des lots (cartes)
        foreach ($liste as $lotneg) {			
            ?>

            <div class="card text-white mb-3 carte-lot d-inline-block mr-3" style="max-width: 20rem; background-color: <?php echo $lotneg->getCouleur(); ?>" data-id-lot="<?php
			echo $lotneg->getId(); ?>" >

                <div class="card-header text-36"><?php echo $lotneg->getNum_bl() != '' ? 'BL '.$lotneg->getNum_bl() : 'ID '.$lotneg->getID(); ?></div>
                <div class="card-body">
                    <table>                                         
                        <tr>
                            <td>Fournisseur</td>
                            <th><?php echo $lotneg->getNom_fournisseur() != '' ? $lotneg->getNom_fournisseur() : $na; ?><br>
							<?php echo $lotneg->getNumagr() != '' ? $lotneg->getNumagr() : $na; ?>
							</th>
                        </tr>
						
						<tr>
                            <td>Reception</td>
                            <th><?php echo $lotneg->getDate_reception() != '' ? Outils::dateSqlToFr($lotneg->getDate_reception()) : '&mdash;'; ?></th>
                        </tr>  

						<tr>
                            <td>Nombre Produits : </td>
                            <th>
							<?php echo  $lotsNegoceManager->getNbProduitsByLot($lotneg); ?>
							</th>
                        </tr>  

						<tr>
                            <td>Poids:</td>
                            <th><?php 
							$poids = $lotsNegoceManager->getPoidsProduitLotNegoce($lotneg->getId());						
							$poidsTotal     = number_format($poids,2, '.', '');
							$poidsTotalFroidArray   = explode('.', $poidsTotal);
							echo '<span class="text-16">'.$poidsTotalFroidArray[0].'.'.$poidsTotalFroidArray[1].'</span>'; ?> kg
							</th>
                        </tr>  

                    </table>
                </div> <!-- FIN body carte -->
            </div> <!-- FIN carte -->

            <?php

        } // FIN boucle sur les lots de négoce
        ?>

            </div>
        </div>

        <?php
        exit;

	} // FIN étape 1

	/** ----------------------------------------
	 * Etape        : 2
	 * Description  : Liste des produits du lot
	 *  ----------------------------------- */

	if ($etape == 2) {

	    $id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $lotNeg = $lotsNegoceManager->getLotNegoce($id_lot);
        if (!$lotNeg instanceof LotNegoce) { erreurLot();  }

		modeShowTableauProduits($lotNeg);


	} // FIN étape 2


	/** ----------------------------------------
	 * Etape        : 3
	 * Description  : Modif produit negoce
	 *  ----------------------------------- */

	if ($etape == 3) {
		$id_pdt = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		$pdtNeg = $lotsNegoceManager->getNegoceProduit($id_pdt);
		if (!$pdtNeg instanceof NegoceProduit) { erreurLot();  }

		$produitManager = new ProduitManager($cnx);
		$pdt = $produitManager->getProduit($pdtNeg->getId_pdt());
		if (!$pdt instanceof Produit) { erreurLot();  }		
		$palettesManager = new PalettesManager($cnx);
		?>
		<div class="row mt-3 align-content-center">
			<div class="col">
				<div class="alert alert-dark">
					<h3 class="mb-0 text-40 text-center" data-id-pdt-lot-negeoce="<?php echo $pdtNeg->getId_lot_pdt_negoce(); ?>">
						<?php echo $pdtNeg->getNom_produit(); ?>
					</h3>
				</div>
			</div>
		</div>


		<!-- Row contenu -->
		<div class="row mt-2 align-content-center">

		<!-- bloc gauche : Pavé numérique -->
		<div class="col-4 text-center">
			<div class="alert alert-secondary">

				<input type="hidden" id="champ" value="nb_cartons"/>
				<input type="hidden" id="idLotPdtNegeoce" value="<?php echo $pdtNeg->getId_lot_pdt_negoce(); ?>"/>
				<input type="hidden" id="idPalettePdtNegoce" value="<?php echo $pdtNeg->getId_palette(); ?>"/>
				<input type="hidden" id="idPaletteIdClient" value="0"/>
				<input type="hidden" id="inputIdProduit" value="<?php echo $pdtNeg->getId_pdt(); ?>"/>

				<div class="input-group clavier">
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="1">1</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="2">2</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="3">3</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="4">4</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="5">5</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="6">6</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="7">7</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="8">8</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="9">9</button></div>

					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark padding-15 btn-large" data-valeur=".">.</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="0">0</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger padding-15 btn-large" data-valeur="C"><i class="fa fa-backspace"></i></button></div>

					<div class="col-12"><button type="button" class="form-control mb-2 btn btn-success padding-15 btn-large btnValider" data-valeur="V" data-id-pdt-lot-negoce="<?php
						echo $pdtNeg->getId_lot_pdt_negoce(); ?>"><i class="fa fa-check"></i></button>
					</div>
				</div>

			</div> <!-- FIN alerte -->

		</div> <!-- FIN bloc gauche -->

		<!-- Bloc droite : Champs -->
		<div class="col-8">
			<div class="row">
				<div class="col">
					<h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Nombre de cartons et poids :</h4>
				</div>
				<div class="col text-right">
				</div>
			</div>

			<div class="row">
				<!-- Nombre de cartons -->
				<div class="col-5">
					<div class="input-group">
						<input type="text" class="form-control text-100 text-center inputNbCartons" name="nb_cartons" placeholder="0" value="<?php echo $pdtNeg->getNb_cartons(); ?>">
						<input type="hidden" name="nb_cartons_old" value="<?php echo $pdtNeg->getNb_cartons(); ?>">
						<span class="input-group-append">
                                  <span class="input-group-text text-26"> cartons</span>
                            </span>
					</div>
				</div>

				<!-- Poids -->
				<div class="col-7">
					<div class="input-group">
                             <span class="input-group-prepend">
                                  <span class="input-group-text text-38 gris-9"><i class="fa fa-weight"></i></span>
                            </span>
						<input type="text" class="form-control text-100 text-center inputPoids" name="poids" placeholder="0" value="<?php
						echo $pdtNeg->getPoids(); ?>">
						<input type="hidden" name="poids_pdt_old" value="<?php echo $pdtNeg->getPoids(); ?>">
						<span class="input-group-append">
                                  <span class="input-group-text text-26"> kg</span>
                            </span>
					</div>
				</div>

			</div> <!-- FIN row -->




		<?php
		// -------------------------------------------------------------------
		// Palettes
		// -------------------------------------------------------------------
		?>
		<div class="row mt-2">
			<div class="col-12">
				<?php if ($pdt->isMixte()) { ?>
					<p class="nomargin text-14 gris-7"><i class="fa fa-random mr-1 gris-9"></i>Palettes Mixtes :</p>
				<?php } ?>
				<div class="row" id="listePalettesPdt">
					<?php


					// On récupère toutes les palettes qui sont en production (statut = 0) pour le produit en cours
					$params = [
						'statut'     => 0,
						'vides'      => false,
						'id_produit' => $pdt->getId(),
						'mixte' => $pdt->isMixte(),
						'hors_frais' => true
					];
					$palettes = $palettesManager->getListePalettes($params);
					if (empty($palettes)) { ?>

						<div class="col-3">
							<div class="alert alert-secondary text-center padding-50 w-100 text-18 gris-9">
								Aucune palette<br>en cours...
							</div>
						</div>

					<?php }

					$palettePdt = isset($froidPdt) && $froidPdt instanceof FroidProduit ? $froidPdt->getId_palette() : 0;

					foreach ($palettes as $palette) {

					$poidsPalette  = $palettesManager->getPoidsTotalPalette($palette);
					$colisPalette  = $palettesManager->getNbColisTotalPalette($palette);
					$capacitePoids = $palettesManager->getCapacitePalettePoids($palette);
					$capaciteColis = $palettesManager->getCapacitePaletteColis($palette);

					$restantPoids  = floatval($capacitePoids - $poidsPalette);
					$restantColis  = intval($capaciteColis - $colisPalette);

					?>

					<div class="col-3 mb-1">
						<div class="card bg-dark text-white pointeur carte-palette <?php echo $palette->getId() == $palettePdt ? 'palette-selectionnee' : '';?>" id="cartepaletteid<?php echo $palette->getId(); ?>"
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

						<div class="card-footer padding-10 bg-<?php echo ($restantPoids <= 0 || $restantColis <= 0)  && $colisPalette > 0 ? 'warning text-dark' : 'secondary'; ?>">
							<div class="row">
								<?php if ($poidsPalette == 0 || $colisPalette == 0) { ?>
                                    <div class="col-12 text-center text-16">Palette vide</div>
                                    <div class="col-12 text-18">&nbsp;</div>
                                <?php } else if ($restantPoids <= 0 || $restantColis <= 0) { ?>
									<div class="col-12 text-center text-16">Capacité atteinte !</div>
									<div class="col-6 text-center text-18">
										<?php echo $restantPoids < 0 ? '+ '.number_format($restantPoids * -1,0) : number_format($restantPoids,0); ?> <span class="texte-fin text-14">kgs</span>
									</div>
									<div class="col-6 text-center text-18">
										<?php echo $restantColis < 0 ? '+ ' . $restantColis * -1 : $restantColis; ?> <span class="texte-fin text-14">colis</span>
									</div>

								<?php } else { ?>
									<div class="col-12 text-center text-16">Capacité restante :</div>
									<div class="col-6 text-center text-18">
										<?php echo number_format($restantPoids,0); ?> <span class="texte-fin text-14">kgs</span>
									</div>
									<div class="col-6 text-center text-18">
										<?php echo $restantColis; ?> <span class="texte-fin text-14">colis</span>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			<?php

			} // FIN boucle sur les palettes en préparation

			?>

				<div class="col-3 padding-left-50 padding-right-50">
					<button type="button" class="btn btn-dark form-control text-center text-20 padding-15 btnNouvellePalette">
						<i class="fa fa-plus-square fa-lg"></i>
						<p class="mb-0 mt-1 text-16">Nouvelle palette</p>
					</button>
					<br>
					<button type="button" class="btn btn-dark form-control text-center text-20 padding-15 btnPalettesCompletes mt-2">
						<i class="fa fa-ellipsis-h fa-lg"></i>
						<p class="mb-0 mt-1 text-16">Voir complètes</p>
					</button>
				</div>

			</div> <!-- FIN conteneur ROW palettes -->

			</div> <!-- FIN bloc droit -->

		</div> <!-- FIN row contenu -->

        </div>
        <?php
		

	
	} // FIN étape 2

	/** ----------------------------------------
	 * Etape        : 4
	 * Description  : Traitement partiel (éclatement)
	 *  ----------------------------------- */

	if ($etape == 4) {

		$id_pdt = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		$pdtNeg = $lotsNegoceManager->getNegoceProduit($id_pdt);
		if (!$pdtNeg instanceof NegoceProduit) { erreurLot();  }

		$produitManager = new ProduitManager($cnx);
		$pdt = $produitManager->getProduit($pdtNeg->getId_pdt());
		if (!$pdt instanceof Produit) { erreurLot();  }

		$palettesManager = new PalettesManager($cnx);

		?>
        <div class="row mt-3 align-content-center">
            <div class="col">
                <div class="alert alert-dark">
                    <h3 class="mb-0 text-40 text-center" data-id-pdt-lot-negeoce="<?php echo $pdtNeg->getId_lot_pdt_negoce(); ?>">
						<?php echo $pdtNeg->getNom_produit(); ?>
                    </h3>
                </div>
            </div>
        </div>


        <!-- Row contenu -->
        <div class="row mt-2 align-content-center">

        <!-- bloc gauche : Pavé numérique -->
        <div class="col-4 text-center">
            <div class="alert alert-secondary">

                <input type="hidden" id="champ" value="nb_cartons"/>
                <input type="hidden" id="idLotPdtNegeoce" value="<?php echo $pdtNeg->getId_lot_pdt_negoce(); ?>"/>
                <input type="hidden" id="inputIdProduit" value="<?php echo $pdtNeg->getId_pdt(); ?>"/>

                <div class="input-group clavier">
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="1">1</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="2">2</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="3">3</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="4">4</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="5">5</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="6">6</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="7">7</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="8">8</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="9">9</button></div>

                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark padding-15 btn-large" data-valeur=".">.</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary padding-15 btn-large" data-valeur="0">0</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger padding-15 btn-large" data-valeur="C"><i class="fa fa-backspace"></i></button></div>

                    <div class="col-12"><button type="button" class="form-control mb-2 btn btn-success padding-15 btn-large btnValider" data-valeur="V" data-id-pdt-lot-negoce="<?php
						echo $pdtNeg->getId_lot_pdt_negoce(); ?>"><i class="fa fa-check"></i></button>
                    </div>
                </div>

            </div> <!-- FIN alerte -->

        </div> <!-- FIN bloc gauche -->

        <!-- Bloc droite : Champs -->
        <div class="col-8">
            <div class="row">
                <div class="col">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Quantité traitée :</h4>
                </div>
                <div class="col text-right">
                </div>
            </div>
            <input type="hidden" name="id_lot_negoce" value="<?php echo $pdtNeg->getId_lot_negoce(); ?>"/>
            <div class="row">
                <!-- Nombre de cartons -->
                <div class="col-5">
                    <div class="input-group input-group-success">
                        <input type="text" class="form-control text-100 text-center inputNbCartons" name="nb_cartons" placeholder="0" value="<?php echo $pdtNeg->getNb_cartons(); ?>">
                        <input type="hidden" name="nb_cartons_old" value="<?php echo $pdtNeg->getNb_cartons(); ?>">
                        <span class="input-group-append">
                                  <span class="input-group-text text-26"> cartons</span>
                            </span>
                    </div>
                </div>

                <!-- Poids -->
                <div class="col-7">
                    <div class="input-group input-group-success">
                             <span class="input-group-prepend">
                                  <span class="input-group-text text-38 gris-9"><i class="fa fa-weight"></i></span>
                            </span>
                        <input type="text" class="form-control text-100 text-center inputPoids" name="poids" placeholder="0" value="<?php
						echo $pdtNeg->getPoids(); ?>">
                        <input type="hidden" name="poids_pdt_old" value="<?php echo $pdtNeg->getPoids(); ?>">
                        <span class="input-group-append">
                                  <span class="input-group-text text-26"> kg</span>
                            </span>
                    </div>
                </div>

            </div> <!-- FIN row -->
            <div class="row mt-3">
                <div class="col">
                    <div class="alert alert-secondary">
                        Quantité totale du produit : <?php echo $pdtNeg->getNb_cartons(); ?> carton<?php echo $pdtNeg->getNb_cartons() > 1 ? 's' : ''; ?> /
						<?php echo number_format($pdtNeg->getPoids(),3,'.',' '); ?> kg.
                    </div>
                </div>
            </div>


        </div> <!-- FIN row contenu -->

        </div>
        <?php
    } // FIN étape 4

	exit;

} // FIN mode




/* ------------------------------------------
MODE - Charge le ticket
-------------------------------------------*/
function modeChargeTicket() {

	global $cnx, $utilisateur, $lotsNegoceManager;

	// Récupération des variables
	$etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;
	$identifiant = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
	$id_lot_negoce = intval($identifiant);

	$err = '<span class="badge danger badge-pill text-14">ERREUR !</span>';

	/** ----------------------------------------
	 * TICKET
	 * Etape        : 1
	 * Contexte    : Liste des lots de négoce
	 *  ----------------------------------- */

	if ($etape == 1) {

        ?>
        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot="0">
            &mdash;
        </div>
        <p class="mt-2 text-center">Sélectionnez un lot de négoce&hellip;</p>
        <?php

	} // FIN ETAPE 1

    /** ----------------------------------------
	 * TICKET
	 * Etape        : 2
	 * Contexte    : Liste des produits
	 *  ----------------------------------- */

	if ($etape == 2) {

		$lotneg = $lotsNegoceManager->getLotNegoce($identifiant);	
		if (!$lotneg instanceof LotNegoce) { erreurLot(); }
        ?>
        <div class="alert alert-secondary text-30 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot="0">
           <?php echo $lotneg->getNum_bl(); ?>
        </div>

        <button type="button" class="btn btn-secondary btn-lg form-control btnRetourEtape1 text-left mb-4"><i class="fa fa-undo fa-lg vmiddle mr-3"></i>Autre lot de negoce&hellip;</button>
	    <?php

		$na = '<span class="badge badge-warning text-16 texte-fin">Non renseigné</span>';
		?>
		<table class="mb-0">			
			<tr>
				<td>Fournisseur :</td>
				<th><?php echo $lotneg->getNom_fournisseur() != '' ? $lotneg->getNom_fournisseur() : $na; ?></th>
			</tr>
			<tr>
				<td>Produits :</td>
				<th>		
					<?php echo  $lotsNegoceManager->getNbProduitsByLot($lotneg); ?>
				</th>
			</tr>
			<tr>
				<td>Poids Total :</td>
				<th>
				<?php
					$poids = $lotsNegoceManager->getPoidsProduitLotNegoce($id_lot_negoce);						
					$poidsTotal     = number_format($poids,3, '.', '');
					$poidsTotalFroidArray   = explode('.', $poidsTotal);
					echo '<span class="text-16">'.$poidsTotalFroidArray[0].'.'.$poidsTotalFroidArray[1].'</span>'; ?> kg</th>							
			
				
				</th>
			</tr>
			

		</table>

        <a href="<?php echo __CBO_ROOT_URL__?>bl-addupd.php?ln=<?php echo base64_encode($lotneg->getId());?>" class="btn btn-outline-warning btn-lg form-control text-left mt-3">
            <i class="fa fa-truck fa-fw mr-2"></i>
            Bon de livraison</a>
		<?php


	} // FIN ETAPE 2

	/** ----------------------------------------
	 * TICKET
	 * Etape        : 3
	 * Contexte    : Modif pdt négoce
	 *  ----------------------------------- */

	if ($etape == 3 || $etape == 4) {

		$pdtNeg = $lotsNegoceManager->getNegoceProduit($identifiant);
		if (!$pdtNeg instanceof NegoceProduit) { erreurLot(); }

		$lotneg = $lotsNegoceManager->getLotNegoce($pdtNeg->getId_lot_negoce());
		if (!$lotneg instanceof LotNegoce) { erreurLot(); }

		?>
        <div class="alert alert-secondary text-30 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot="<?php echo $lotneg->getId(); ?>">
			<?php echo $lotneg->getNum_bl(); ?>
        </div>
        <input type="hidden" value="<?php echo $pdtNeg->getId_pdt(); ?>" class="id_pdt_negoce"/>
        <button type="button" class="btn btn-secondary btn-lg form-control btnRetourEtape2 text-left mb-4" data-id-lot="<?php echo $pdtNeg->getId_lot_negoce(); ?>"><i class="fa fa-undo fa-lg vmiddle mr-3"></i>Retour lot&hellip;</button>

		<?php

	} // FIN étape 3





} // FIN charge ticket

function erreurLot() {
	?>
	<div class="alert alert-danger text-center">
		<i class="fa fa-exclamation-triangle fa-3x mb-2"></i><br>
		<p>Erreur de récupération du lot !</p>
	</div>
	<?php
	exit;
} // FIN fonctions

	// Fonction déportée pour liste des produits d'un lot
function modeShowTableauProduits(LotNegoce $lotNeg) {

	global $lotsNegoceManager, $utilisateur;

	 if (empty($lotNeg->getProduits())) { ?>

		 <div class="row">
			 <div class="col mt-3">
				 <div class="alert alert-warning text-center text-30 padding-50">
					 <i class="fa fa-exclamation-circle fa-lg mb-2"></i>
					 <p class="nomargin">Aucun produit dans ce lot</p>

				 </div>
			 </div>
		 </div>

	 <?php exit; }

	 ?>
<div class="row">
	<div class="col mt-3">

		<table class="table admin table-v-middle ">
			<thead>
			<tr>
				<th>N° de lot Negoce</th>
				<th>Produit</th>
				<th class="text-center">Nb de cartons</th>
				<th class="text-right padding-right-15">Poids (kg)</th>
				<th class="text-center">Quantite</th>
				<th class="text-center t-actions">Traité</th>
				<th class="text-center t-actions">Partiel</th>
				<th class="text-center t-actions">Modifier</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ($lotNeg->getProduits() as $pdtNeg) {				
				?>
				<tr>
					<td><?php echo $pdtNeg->getNum_lot();?></td>
					<td class="text-16"><?php echo $pdtNeg->getNom_produit(); ?></td>
					<td class="text-center text-20"><?php echo $pdtNeg->getNb_cartons(); ?></td>
					<td class="text-right text-20"><?php echo number_format($pdtNeg->getPoids(),3,',', ' '); ?></td>
					<td class="text-center text-20"><?php echo $pdtNeg->getQuantite() > 0 ? $pdtNeg->getQuantite() : '<span class="gris-9">&mdash;</span>'; ?></td>
					<td class="text-center t-actions">
						<input type="checkbox" class="togglemaster switchTraite"
							   data-toggle              = "toggle"
							   data-on                  = "Oui"
							   data-off                 = "Non"
							   data-onstyle             = "success"
							   data-offstyle            = "secondary"
							   data-id                  = "<?php echo $pdtNeg->getId_lot_pdt_negoce(); ?>"
							<?php
							// Statut coché
							echo $pdtNeg->getTraite() == 1  ? 'checked' : ''; ?>/>
					</td>
                    <td class="text-center t-actions">
                        <button type="button" class="btn btn-secondary btn-lg btnPartiel" data-id="<?php echo $pdtNeg->getId_lot_pdt_negoce();?>">
                            <i class="fa fa-columns"></i>
                        </button>
                    </td>
					<td class="text-center t-actions">
						<button type="button" class="btn btn-secondary btn-lg btnModierPdtNeg" data-id="<?php echo $pdtNeg->getId_lot_pdt_negoce();?>">
							<i class="fa fa-edit"></i>
						</button>
					</td>
				</tr>


			<?php
			} // FIN boucle produits
			?>
			</tbody>
		</table>
		<?php
	exit;


} // FIN fonction

// MODE - Switch l'état "Traité" d'un produit de négoce
function modeSwitchTraite() {

    global $lotsNegoceManager;

    $id_lot_pdt_negoce = isset($_REQUEST['id_lot_pdt_negoce']) ? intval($_REQUEST['id_lot_pdt_negoce']) : 0;
    $traite = isset($_REQUEST['traite']) ? intval($_REQUEST['traite']) : -1;

    if ($traite < 0 || $traite > 1) { exit; }
    if ($id_lot_pdt_negoce == 0) { exit; }

    $pdtNeg = $lotsNegoceManager->getNegoceProduit($id_lot_pdt_negoce);
    if (!$pdtNeg instanceof NegoceProduit) { exit; }

    $pdtNeg->setTraite($traite);
    echo $lotsNegoceManager->saveNegoceProduit($pdtNeg) ? 1 : 0;
    exit;

} // FIN mode


// Mise à jour des données produits negoce (nbcolis, poids, palette)...
function modeUpdPdtNegoce() {

	global $lotsNegoceManager,$cnx, $utilisateur;

	$id_lot_pdt_negoce = isset($_REQUEST['id_lot_pdt_negoce']) ? intval($_REQUEST['id_lot_pdt_negoce']) : 0;
	$nb_cartons = isset($_REQUEST['nb_cartons']) ? intval($_REQUEST['nb_cartons']) : 0;
	//$quantite = isset($_REQUEST['quantite']) ? intval($_REQUEST['quantite']) : 0;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0.0;
	$id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
	$id_client = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;	

	if ($id_lot_pdt_negoce == 0) { exit; }
	$pdtNeg = $lotsNegoceManager->getNegoceProduit($id_lot_pdt_negoce);
	if (!$pdtNeg instanceof NegoceProduit) { exit; }

	$pdtNeg->setNb_cartons($nb_cartons);
	$pdtNeg->setPoids($poids);
	//$pdtNeg->setQuantite($quantite);

	echo $lotsNegoceManager->saveNegoceProduit($pdtNeg) ? 1 : 0;

	// Création de la composition si elle n'existe pas... (on teste si on a un id_palette sinon ça sert à rien)
    if ($id_palette > 0) {

        $palettesManager = new PalettesManager($cnx);

        $compos = $palettesManager->getCompositionsNegoceProduit($id_lot_pdt_negoce);

        // Si On a déjà des compositions pour ce produit de négoce : on les supprime et on crée une nouvelle
        foreach ($compos as $compo) {
            $palettesManager->supprCompositionFromId($compo->getId());
			$log = new Log([]);
			$log->setLog_type('warning');
			$log->setLog_texte("Suppression de la composition  #" . $compo->getId() . " (déjà des compositions pour ce produit de négoce : on les supprime et on crée une nouvelle)") ;
			$logsManager = new LogManager($cnx);
			$logsManager->saveLog($log);
        }

        $id_client = $id_client == 0 ? $palettesManager->getClientCompoByPalette($id_palette) : $id_client;
        $id_produit = $lotsNegoceManager->getIdProduitByNegoceProduit($id_lot_pdt_negoce);

        $compo = new PaletteComposition([]);
        $compo->setId_palette($id_palette);
        $compo->setPoids($poids);
        $compo->setNb_colis($nb_cartons);
        $compo->setId_client($id_client);
        $compo->setId_produit($id_produit);
        $compo->setId_lot_pdt_froid(0);
        $compo->setId_lot_pdt_negoce($id_lot_pdt_negoce);
        $compo->setId_lot_regroupement(0);
        $compo->setDate(date('Y-m-d H:i:s'));
        $compo->setId_user($utilisateur->getId());
        $compo->setSupprime(0);

        $palettesManager->savePaletteComposition($compo);

		// On met à jour le statut de la palette si complète
		$palettesManager->updStatutPalette($id_palette);


	} // FIN test palette définie

    exit;

} // FIN mode

// Split un produit de négoce en deux et marque une partie comme traitée
function modeSplitPdtNegoceTraite() {

	global $lotsNegoceManager,$cnx, $utilisateur;

	$id_lot_pdt_negoce = isset($_REQUEST['id_lot_pdt_negoce']) ? intval($_REQUEST['id_lot_pdt_negoce']) : 0;
	$nb_cartons = isset($_REQUEST['nb_cartons']) ? intval($_REQUEST['nb_cartons']) : 0;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0.0;
    if ($poids == 0 || $nb_cartons == 0) { exit; }

	if ($id_lot_pdt_negoce == 0) { exit; }
	$pdtNeg = $lotsNegoceManager->getNegoceProduit($id_lot_pdt_negoce);
	if (!$pdtNeg instanceof NegoceProduit) { exit; }

	if ($poids >= $pdtNeg->getPoids() || $nb_cartons >= $pdtNeg->getNb_cartons()) { exit; }
	$poids_restant = $pdtNeg->getPoids() - $poids;
	$cartons_restant = $pdtNeg->getNb_cartons() - $nb_cartons;

	$pdtNeg->setNb_cartons($cartons_restant);
	$pdtNeg->setPoids($poids_restant);
	$pdtNeg->setTraite(0);
	if (!$lotsNegoceManager->saveNegoceProduit($pdtNeg)) { exit; }

	// On a traité l'original, on crée à présent un clone, en partie traité
    $pdtNeg2 = new NegoceProduit([]);
	$pdtNeg2->setTraite(1);
	$pdtNeg2->setPoids($poids);
	$pdtNeg2->setNb_cartons($nb_cartons);
	$pdtNeg2->setSupprime(0);
	$pdtNeg2->setDate_add(date('Y-m-d H:i:s'));
	$pdtNeg2->setId_palette($pdtNeg->getId_palette());
	$pdtNeg2->setId_lot_negoce($pdtNeg->getId_lot_negoce());
	$pdtNeg2->setId_pdt($pdtNeg->getId_pdt());
	$pdtNeg2->setNum_lot($pdtNeg->getNum_lot());
	$pdtNeg2->setDlc($pdtNeg->getDlc());
	$pdtNeg2->setUser_add($utilisateur->getId());

	$res = $lotsNegoceManager->saveNegoceProduit($pdtNeg2);
	echo intval($res) > 0 ? 1 : 0;
	$id = intval($res);
	$logTxt = $id > 0 ? 'Traitement partiel du produit de négoce #' . $id_lot_pdt_negoce . ' dupliqué sur ID '.$id : 'Echec lors du traitement partiel du produit de négoce #'.$id_lot_pdt_negoce;
    $logStyle = $id > 0 ? 'info' : 'danger';
    $log = new Log([]);
    $log->setLog_texte($logTxt);
    $log->setLog_type($logStyle);
    $logManager = new LogManager($cnx);
    $logManager->saveLog($log);
    exit;

} // FIN mode