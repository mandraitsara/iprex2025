<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax VUE SURGELATION VERTICALE
------------------------------------------------------*/
//ini_set('display_errors',1);

// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$logsManager = new LogManager($cnx);    // LOGS système
$lotsManager = new LotManager($cnx);    // LOTS

// Construction et appel des fonctions "mode"
$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------------
FONCTION - Message d'erreur standard
-------------------------------------------*/
function erreurLot() {
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
function modeChargeEtapeVue() {

	global $cnx, $utilisateur, $logsManager;

	$etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;
	$na = '<span class="badge badge-warning badge-pill text-14">Non renseigné</span>';



	/** ----------------------------------------
	 * Etape        : 0
	 * Description  : Point d'entrée
	 *  ----------------------------------- */

	if ($etape == 0) { ?>

        <div class="row mt-5">
            <div class="col text-center">
                <i class="fa fa-ruler-vertical gris-9 fa-6x"></i>
            </div>
        </div>

        <div class="row mt-5">

            <div class="col-5 offset-1">
                <button type="button" class="btn btn-info btn-lg form-control btnNouvelleSrg">
                    <i class="fa fa-plus text-50 mb-3 mt-3"></i>
                    <h3 class="mb-3">Nouvelle surgélation verticale&hellip;</h3>
                </button>
            </div>

            <div class="col-5">
                <button type="button" class="btn btn-secondary btn-lg form-control btnSrgsEnCours">
                    <i class="fa fa-stopwatch text-50 mb-3 mt-3"></i>
                    <h3 class="mb-3">Surgélations verticales en cours&hellip;</h3>
                </button>
            </div>

        </div>
		<?php

	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 1
	 * Description  : Sélection du lot
	 *  ----------------------------------- */

	if ($etape == 1) {
		?>


        <div class="row">
            <div class="col mt-3">
                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Sélectionnez le lot concerné :</h4>
            </div>
        </div>

		<?php

		// On récupère la liste des lots de la vue Atelier (car tant qu'ils sont en atelier, ils sont dispo en OPs de Froid)
		$lotsManager = new LotManager($cnx);
		$listeLot    = $lotsManager->getListeLotsByVue('atl'); // On charge les lots dispo en atelier car ils y restent dès que la réception est terminée

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = '<br>Lots sur la vue ATL : ';
		}

		// Si aucun lot en atelier...
		if (empty($listeLot)) {



			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= 'Aucun';
			}
		    ?>

            <div class="col alert alert-secondary mt-3 padding-50">
                <h2 class="mb-0 text-secondary text-center"><i class="fa fa-exclamation-circle fa-2x mb-3"></i>
                    <p>Aucun lot disponible&hellip;</p>
                </h2>
            </div>

			<?php
		}  else if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'].= count($listeLot);
		} // FIN test aucun lot atelier

		// Boucle sur les lots en atelier -  Affichage des cartes LOT
		foreach ($listeLot as $lotvue) {

			// Récupération des quantièmes du lot
			$quantiemes = $lotsManager->getLotQuantiemes($lotvue);

			// Si on qu'un seul quantième, on le concatène avec le numéro du lot
			if (count($quantiemes) == 1 && trim(strtolower($quantiemes[0])) != 'a') {
				$lotvue->setNumlot($lotvue->getNumlot() . $quantiemes[0]);
			}


			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= '<br>- Lot ['.$lotvue->getId().'] ' . $lotvue->getNumlot();
			}

		    ?>

            <div class="card text-white mb-3 carte-lot d-inline-block mr-3" style="max-width: 20rem;background-color: <?php echo $lotvue->getCouleur(); ?>" data-id-lot="<?php echo $lotvue->getId(); ?>" data-etape-suivante="2">

                <div class="card-header text-36"><?php echo $lotvue->getNumlot(); ?></div>
                <div class="card-body">

                    <table>
                        <tr>
                            <td class="vmiddle">Espèce</td>
                            <th><?php echo $lotvue->getNom_espece($na); ?></th>
                        </tr>
                        <tr>
                            <td class="vmiddle">Composition</td>
                            <th><?php echo $lotvue->getComposition_viande_verbose() != '' ? ' '.strtoupper($lotvue->getComposition_viande_verbose()) : ''; ?></th>
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
                            <th><?php
								echo $lotvue->getDate_reception() != '' && $lotvue->getDate_reception() != '0000-00-00'
									?  Outils::getDate_only_verbose($lotvue->getDate_reception(), true, false)
									: $na;
								?></th>
                        </tr>
                        <tr>
                            <td>Poids</td>
                            <th><?php
								echo $lotvue->getPoids_reception() > 0
									? number_format($lotvue->getPoids_reception(),3, '.', ' ') . ' kg'
									: $na;
								?></th>
                        </tr>
						<?php
						// Si on a plusieurs quantièmes, on les liste ici pour info
						if (count($quantiemes) > 1) { ?>

                            <tr>
                                <td>Quantièmes</td>
                                <th><?php
									foreach ($quantiemes as $quantieme) { ?>
                                        <span class="mr-2"><?php echo $quantieme; ?></span>
										<?php
									} // FIN boucle quantièmes
									?></th>
                            </tr>

							<?php
							// Si il n'y en a qu'un, on l'affiche quand même ici
						} else if (!empty($quantiemes)) { ?>

                            <tr>
                                <td>Quantième</td>
                                <th><?php echo $quantiemes[0]; ?></th>
                            </tr>

							<?php
						} // FIN test plusieurs quantiemes
						?>
                    </table>

                </div> <!-- FIN body carte -->

            </div> <!-- FIN carte -->

			<?php
		} // FIN boucle sur les lots en atelier
		exit;

	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 2
	 * Description  : Sélection des produits
	 * Paramètres   : Lot
	 *  ----------------------------------- */

	if ($etape == 2) {

		// Vérification des variables - Lot requis
		$id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if ($id_lot == 0) { erreurLot(); exit; }
		$lotsManager = new LotManager($cnx);
		$lot =  $lotsManager->getLot($id_lot);
		if (!$lot instanceof Lot) { erreurLot(); exit; }

		// Liste des familles de produits actives correspondant à la composition du lot
		$produitEspeceManager = new ProduitEspecesManager($cnx);


		$espece = $produitEspeceManager->getProduitEspece($lot->getId_espece());
		modeShowProduitsFamille($espece->getId(), $id_lot, false);


		exit;

	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 3
	 * Description  : Nb de blocs / Poids pdt
	 * Paramètres   : Produit / Lot / Froid (facultatif)
	 *  ----------------------------------- */

	if ($etape == 3) {

		// Récupération des variables
		$couplePdtLot = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
		if ($couplePdtLot == '') { erreurLot(); exit; }

		// Array des données passées en paramètre
		$couplePdtLotArray = explode('|', $couplePdtLot);

		$froidManager = new FroidManager($cnx);

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = ' ';
		}

		// Si on a bien un array, c'est qu'on est en ajout.
		if (isset($couplePdtLotArray[2]))  {

			// Formatage des variables
			$id_pdt     = intval($couplePdtLotArray[0]);
			$id_lot     = intval($couplePdtLotArray[1]);
			$id_froid   = intval($couplePdtLotArray[2]);
			if ($id_pdt == 0 || $id_lot == 0) { erreurLot(); exit; }

			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= 'Mode AJOUT (passage d\'un array pdt/lot/froid)';
				$_SESSION['infodevvue'].= '<br>Lot ['.$id_lot.']';
				$_SESSION['infodevvue'].= '<br>Produit ['.$id_pdt.']';
			}

			// On peut être en ajout d'un produit déjà dans le traitement, ce qui revient à un update
            // On essaye alors de récupérer les données depuis les variables fournies
			$froidProduit = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
			if ($froidProduit instanceof FroidProduit) {

				if ($utilisateur->isDev()) {
					$_SESSION['infodevvue'].= ' (déjà en traitement : FroidProduit trouvé avec le lot/pdt/froid)';
					$_SESSION['infodevvue'].= '<br>FroidProduit ['.$froidProduit->getId_lot_pdt_froid().']';
					$_SESSION['infodevvue'].= '<br>Palette ['.$froidProduit->getId_palette().']';
					$_SESSION['infodevvue'].= '<br>Compo ['.$froidProduit->getId_compo().']';
					$_SESSION['infodevvue'].= '<br>Quantième ['.$froidProduit->getQuantieme().']';
				}

				$inputIdPalette     = $froidProduit->getId_palette();
				$inputIdCompo       = $froidProduit->getId_compo();
				$inputQuantieme     = $froidProduit->getQuantieme();
            }


    	// Si update, on pas un array de params mais l'id_lot_pdt_froid
		} else {

			$id_lot_pdt_froid = intval($couplePdtLot);
			if ($id_lot_pdt_froid == 0) { erreurLot(); exit; }
			$froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
			if (!$froidProduit instanceof FroidProduit) { erreurLot(); exit; }

			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= 'Mode UPDATE (passage d\'un id_lot_pdt_froid)';
				$_SESSION['infodevvue'].= '<br>FroidProduit ['.$id_lot_pdt_froid.']';
				$_SESSION['infodevvue'].= '<br>Produit ['.$froidProduit->getId_pdt().']';
				$_SESSION['infodevvue'].= '<br>Lot ['.$froidProduit->getId_lot().']';
				$_SESSION['infodevvue'].= '<br>Froid ['.$froidProduit->getId_froid().']';
				$_SESSION['infodevvue'].= '<br>Palette ['.$froidProduit->getId_palette().']';
				$_SESSION['infodevvue'].= '<br>Compo ['.$froidProduit->getId_compo().']';
				$_SESSION['infodevvue'].= '<br>Quantième ['.$froidProduit->getQuantieme().']';
			}

			$id_pdt             = $froidProduit->getId_pdt();
			$id_lot             = $froidProduit->getId_lot();
			$id_froid           = $froidProduit->getId_froid();
			$inputIdPalette     = $froidProduit->getId_palette();
			$inputIdCompo       = $froidProduit->getId_compo();
			$inputQuantieme     = $froidProduit->getQuantieme();

		} // FIN test paramètre id_lot / id_pdt pour ajout ou id_lot_pdt_froid pour upd

		// Vérification du Lot
		$lotsManager = new LotManager($cnx);
		$lot = $lotsManager->getLot($id_lot);
		if (!$lot instanceof Lot){ erreurLot(); exit; }

		// Vérification du produit
		$pdtManager = new ProduitManager($cnx);
		$pdt = $pdtManager->getProduit($id_pdt);
		if (!$pdt instanceof Produit){ erreurLot(); exit; }

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'].= '<br>Produit mixte : ';
			$_SESSION['infodevvue'].= $pdt->isMixte() ? 'OUI' : 'NON';
		}

		// On retourne le ou les quantièmes du lot s'il y en a...
		$quantiemes = $lotsManager->getLotQuantiemes($lot);
		?>

        <div class="row mt-3 align-content-center">
            <div class="col">

                <div class="alert alert-dark">
                    <h3 class="mb-0 text-40 text-center produit-add-upd" data-id-produit="<?php echo $pdt->getId(); ?>">
					<?php
                        // En mode DEV, on affiche les IDs utiles...
                        if ($utilisateur->isDev()) { ?>
                            <div class="float-left margin-top--5 line-height-15 text-left alert alert-secondary padding-7" style="padding-top: 0 !important;">
                            <span class="text-12 gris-7">
                                <i class="fa fa-user-secret mr-1"></i>
                                <span class="texte-fin">
                                    id_produit <kbd class="opacity-06"><?php echo $pdt->getId(); ?></kbd>
                                    id_lot <kbd class="opacity-06"><?php echo isset($id_lot) ? $id_lot : '-'; ?></kbd>
                                    id_froid <kbd class="opacity-06"><?php echo isset($id_froid) ? $id_froid : '-';; ?></kbd>
                                    id_lot_pdt_froid <kbd class="opacity-06"><?php echo isset($id_lot_pdt_froid) ? $id_lot_pdt_froid : '-';; ?></kbd><br>
                                    id_palette <kbd class="opacity-06"><?php echo isset($inputIdPalette) ? $inputIdPalette : '-';; ?></kbd>
                                    id_compo <kbd class="opacity-06"><?php echo isset($inputIdCompo) ? $inputIdCompo : '-';; ?></kbd>
                                    quantieme <kbd class="opacity-06"><?php echo isset($inputQuantieme) ? $inputQuantieme : '-';; ?></kbd>
                                    pdt mixte <kbd class="opacity-06"><?php echo $pdt->isMixte() ? 'Oui' : 'Non';; ?></kbd>
                                </span>
                            </span>
                            </div>
                            	<?php
                        // Sinon, on affiche l'EAN13
                        } else {
							// On teste la présence du code barre généré en PNG, sinon on le crée
							if (!file_exists(__CBO_ROOT_PATH__.'/img/barcodes/ean13/'.$pdt->getEan13().'.png')) {
								$bc = new pi_barcode();
								$bc->setCode($pdt->getEan13());
								$bc->setType('EAN');
								$bc->setSize(30, 150, 10);
								$bc->setText('AUTO');
								$bc->hideCodeType();
								$bc->setColors('#123456', '#F9F9F9');
								$bc->writeBarcodeFile(__CBO_ROOT_PATH__.'/img/barcodes/ean13/'.$pdt->getEan13().'.png');
							}
							echo '<span class="codebarimg"><img src="'.__CBO_ROOT_URL__.'img/barcodes/ean13/'.$pdt->getEan13().'.png"/></span>';

                        } // FIN test mode DEV

                        // Nom du produit
						echo '<span id="nomProduitPourLoma">'.$pdt->getNom().'</span>';?>

                        <!-- Bouton RETOUR adaptatif -->
                        <button type="button" class="btn btn-danger btn-lg float-right btnRetourProduits"><i class="fa fa-undo mr-2"></i>Retour</button>

                    </h3>
                </div> <!-- FIN alerte -->

            </div> <!-- FIN col -->
        </div> <!-- FIN row -->

        <!-- Row contenu -->
        <div class="row mt-2 align-content-center">


            <!-- bloc gauche : Pavé numérique -->
            <div class="col-4 text-center">
                <div class="alert alert-secondary">

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

                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark padding-15 btn-large <?php
							echo $pdt->getVrac() == 1 ? 'd-none' : '';?>" data-valeur=">"><i class="fa fa-weight"></i></button></div>
                        <div class="col-<?php echo $pdt->getVrac() == 1 ? '12' : '8';?>"><button type="button" class="form-control mb-2 btn btn-success padding-15 btn-large btnValiderCode" data-valeur="V" data-id-pdt="<?php
							echo $id_pdt; ?>" data-id-lot="<?php echo $id_lot; ?>"><i class="fa fa-check"></i></button>
                        </div>
                    </div>

                </div> <!-- FIN alerte -->

            </div> <!-- FIN bloc gauche -->

            <!-- Bloc droite : Champs -->
              <div class="col-8">
                  <div class="row">
                    <div class="col">
                        <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i><?php echo $pdt->getVrac() == 1 ? 'Poids' : 'Nombre de blocs'; ?> en surgélation :</h4>
                    </div>
                    <div class="col text-right">

                    <?php

						// On récupère le nombre de colis du produit pour cette op de froid s'il a déjà été renseigné (modification depuis l'étape 10)
						$nbColisOld     = '';

						// On contrôle l'ID froid et récupère l'objet FroidProduit
						if ($id_froid > 0) {

							if (isset($froidProduit) && $froidProduit instanceof FroidProduit) {
								$froidPdt = $froidProduit;
							}

							else {
								$froidPdt         = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
							}

							// Si on a déjà un lot/produit avec des données, on récupère l'ancienne valeur du nombre de colis
							if ($froidPdt instanceof FroidProduit) {
								$nbColisOld = $froidPdt->getNb_colis();
							}

						} // FIN test ID froid


						// Test mode update pour intégration switch méthode d'ajout
						if (isset($froidPdt) && $froidPdt instanceof FroidProduit) { ?>

                            <div class="row">
                                <div class="col mb-3">
                                    <span class="mr-1">Méthode de mise à jour :</span>
                                    <input type="checkbox" checked
                                           class="togglemaster methode-maj"
                                           data-toggle="toggle"
                                           data-on="Ajout"
                                           data-off="Total"
                                           data-onstyle="secondary"
                                           data-offstyle="info"
                                           data-size="large"
                                    />
                                </div>
                            </div>


						<?php } // FIN test mode update pour intégration switch méthode d'ajout
						?>
                    </div>
                  </div>



                <div class="row">

                    <!-- Valeurs d'origine pour le mode upd -->
                    <input type="hidden" id="inputIdProduit" value="<?php echo $pdt->getId(); ?>"/>
                    <input type="hidden" id="inputIdLotPdtFroid" value="<?php echo isset($froidPdt) && $froidPdt instanceof FroidProduit ? $froidPdt->getId_lot_pdt_froid() : 0 ; ?>"/>
                    <input type="hidden" id="inputIdCompo" value="<?php echo isset($inputIdCompo) ? $inputIdCompo : 0; ?>" data-id-histo="<?php echo isset($inputIdCompo) ? $inputIdCompo : 0; ?>"/>
                    <input type="hidden" id="inputIdPalette" value="<?php echo isset($inputIdPalette) ? $inputIdPalette : 0; ?>"/>
                    <input type="hidden" id="inputIdQuantieme" value="<?php echo isset($inputQuantieme) ? $inputQuantieme : 0; ?>"/>
                    <input type="hidden" id="poidsPaletteHisto" value="<?php echo  isset($froidPdt) && $froidPdt instanceof FroidProduit ? $froidPdt->getPoids() : 0; ?>"/>
                    <input type="hidden" id="nbColisPaletteHisto" value="<?php echo  isset($froidPdt) && $froidPdt instanceof FroidProduit ? $froidPdt->getNb_colis() : 0; ?>"/>
                    <input type="hidden" id="vrac" value="<?php echo $pdt->getVrac(); ?>>"/>
                    <input type="hidden" id="skipCreateCompoSave" value="0"/>
                    <input type="hidden" id="forceSaveProduit" value=""/>

                    <!-- Nombre de blocs -->
                    <div class="col-5 <?php echo $pdt->getVrac() == 1 ? 'd-none' : '';?>">
                        <div class="input-group">
                            <input type="text" class="form-control text-100 text-center inputNbColis" name="nb_colis" placeholder="0" value="<?php echo $nbColisOld; ?>">
                            <input type="hidden" name="nb_colis_old" value="<?php echo $nbColisOld; ?>">
                            <span class="input-group-append">
                                  <span class="input-group-text text-26"> blocs</span>
                            </span>
                        </div>
                    </div>

                    <!-- Poids -->
                    <div class="col-7">
                        <div class="input-group">
                             <span class="input-group-prepend">
                                  <span class="input-group-text text-38 gris-9"><i class="fa fa-weight"></i></span>
                            </span>
                            <input type="text" class="form-control text-100 text-center inputPoidsPdt" name="poids_pdt" placeholder="0" value="<?php if (isset($froidPdt) && $froidPdt instanceof FroidProduit) { echo $froidPdt->getPoids() > 0 ? $froidPdt->getPoids() : ''; } ?>" data-poids-defaut="<?php echo $pdt->getPoids(); ?>">
                            <input type="hidden" name="poids_pdt_old" value="<?php if (isset($froidPdt) && $froidPdt instanceof FroidProduit) { echo $froidPdt->getPoids() > 0 ? $froidPdt->getPoids() : ''; } ?>">
                            <span class="input-group-append">
                                  <span class="input-group-text text-26"> kg</span>
                            </span>
                        </div>
                    </div>

                </div> <!-- FIN row -->

             <!-- Affichage du poids par défaut du produit pour information -->
                <div class="row">
                    <div class="col mt-3">
                        <div class="alert alert-default">
                            <div class="row">
                                <div class="col">
                                    <i class="fa fa-info-circle fa-2x vmiddle mr-2 gris-9"></i>
                                    <span class="text-14 vmiddle">Poids par défaut du produit : <strong class="text-18"><?php echo $pdt->getPoids(); ?></strong> kg.</span>
                                    <span class="infoMajAjout alert alert-warning nomargin float-right text-14">
                                        <span class="doresetdeja"></span>
                                        <span class="estimationtotal"></span>
                                    </span>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>


                <!-- Quantieme -->
                <input type="hidden" name="quantieme" value="<?php

				// Pas de quantième pour les lots abats
				if ($lot->getComposition() == 2) {
					echo 'A';
					// Lot viande
				} else {
					// On récupère le quantième du produit si on est en update, sinon celui du lot en cours s'il n'y en a qu'un seul d'associé.

					// Si le lotpdt n'existe pas encore, on tente de récupérer le quantième du lot
					if (!isset($froidPdt)) { $froidPdt = false; }
					if (!$froidPdt instanceof FroidProduit) {

						$quantiemeLot = $lotsManager->getLotQuantiemes($lot);
						echo count($quantiemeLot) == 1 ? $quantiemeLot[0] : '';

						// Si on a bien un lot produit
					} else {
						$quantiemePdt = isset($froidPdt) && $froidPdt instanceof FroidProduit ? $froidPdt->getQuantieme() : '';
						if ($quantiemePdt != '') {
							echo $quantiemePdt;
						} else {
							echo count($quantiemes) == 1 ? $quantiemes[0] : '';
						}
					} // FIN test lot produit instancié
				} // FIN test composition pour quantième FIN test composition pour quantième
                 ?>"/>

					<?php
					// Si aucun ou plusieurs quantièmes pôur le lot...
					if ((count($quantiemes) > 1 || empty($quantiemes)) && $lot->getComposition() != 2) {

						$texteQuantiemeTitre = intval($nbColisOld) == 0 ? "Sélectionnez le quantième :" : "Quantième :";


						?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i><?php echo $texteQuantiemeTitre; ?></h4>
                            </div>
                            <div class="row cbo-slide-h117">
								<?php

								// Si aucun quantième, on gènère celui du jour, de la veille et de l'avant-veille
								if (empty($quantiemes)) {

									$aujourdHui = date('Y-m-d');
									$veille = date('Y-m-d', strtotime($aujourdHui . ' - 1 DAY'));
									$avantVeille = date('Y-m-d', strtotime($veille . ' - 1 DAY'));
									$quantiemes = [
										Outils::getJourAnByDate($aujourdHui),
										Outils::getJourAnByDate($veille),
										Outils::getJourAnByDate($avantVeille)
									];
								}

								// Boucle sur les quantièmes
								foreach ($quantiemes as $quantieme) {


									?>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-<?php
										echo isset($quantiemePdt) && $quantieme != $quantiemePdt ? 'outline-' : '';
										?>secondary text-40 padding-20 form-control choix-quantieme"><?php echo $quantieme; ?></button>

                                    </div>
									<?php
								} // FIN boucle sur les quantièmes


								?>


                        </div>

					<?php } // FIN aucun ou plusieurs quantième pour le lot
				?>
                        </div>
            </div> <!-- FIN bloc droit -->

            <?php
			// -------------------------------------------------------------------
			// Palettes
			// -------------------------------------------------------------------
			?>
            <div class="col-12 ">
                <?php if ($pdt->isMixte()) { ?>
                    <p class="nomargin text-14 gris-7"><i class="fa fa-random mr-1 gris-9"></i>Palettes Mixtes :</p>
                <?php } ?>
                <div class="row" id="listePalettesPdt">
				<?php
				$palettesManager = new PalettesManager($cnx);

				// On récupère toutes les palettes qui sont en production (statut = 0) pour le produit en cours
				$params = [
					'statut'     => 0,
					'vides'      => false,
					'id_produit' => $id_pdt,
                    'mixte' => $pdt->isMixte(),
					'id_client'  => $pdt->getId_client(),
					'hors_frais' => true
				];
				$palettes = $palettesManager->getListePalettes($params);
				if (empty($palettes)) {

					if ($utilisateur->isDev()) {
						$_SESSION['infodevvue'].= '<br>Aucune palette en cours trouvée pour ce produit/client ';
						$_SESSION['infodevvue'].= $pdt->isMixte() ? ' (mixte) ' : '';
					}
				    ?>

                    <div class="col-2 aucunePaletteEnCours">
                        <div class="alert alert-secondary text-center padding-50 w-100 text-18 gris-9">
                            Aucune palette<br>en cours...
                        </div>
                    </div>

				<?php }

				$palettePdt = isset($froidPdt) && $froidPdt instanceof FroidProduit ? $froidPdt->getId_palette() : 0;

				if ($utilisateur->isDev()) {
					$nPal = isset($palettes) ? count($palettes) : 0;
					$nPalS = $nPal > 1 ? 's' : '';
					$_SESSION['infodevvue'].= '<br>'.$nPal.' palette'.$nPalS.' trouvée'.$nPalS.' :';
				}

				foreach ($palettes as $palette) {

					$poidsPalette  = $palettesManager->getPoidsTotalPalette($palette);
					$colisPalette  = $palettesManager->getNbColisTotalPalette($palette);
					$capacitePoids = $palettesManager->getCapacitePalettePoids($palette);
					$capaciteColis = $palettesManager->getCapacitePaletteColis($palette);

					$restantPoids  = floatval($capacitePoids - $poidsPalette);
					$restantColis  = intval($capaciteColis - $colisPalette);


				if ($utilisateur->isDev()) {
					$_SESSION['infodevvue'].= '<br>Palette ['.$palette->getId().'] (Statut '.$palette->getStatut().') Poids : '.$poidsPalette.' / '.$capacitePoids.' | Colis : '.$colisPalette.' / ' . $capaciteColis;
				}

				// Si la capacité est atteinte, on n'aurais pas du l'avoir ici : il faut mettre le statut à jour
				if ($restantPoids <= 0 || $restantColis <= 0) {
					$palette->setStatut(1);
					$palettesManager->savePalette($palette);


					if ($utilisateur->isDev()) {
						$_SESSION['infodevvue'].= '<br><i class="fa fa-info-circle text-danger mr-1"></i> Mise à jour capacité atteinte en BDD';
					}

					// Log
					$log = new Log([]);
					$log->setLog_type('info');
					$log->setLog_texte("[SRGV] Mise à jour du statut palette complète #".$palette->getId()." durant affichage liste") ;
					$logsManager->saveLog($log);

					continue;
				} // FIN palette complète avec mauvais statut

					?>

                    <div class="col-2 mb-1">
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

                            <div class="card-footer padding-10 bg-<?php echo $restantPoids <= 0 || $restantColis <= 0 ? 'warning text-dark' : 'secondary'; ?>">
                                <div class="row">
								<?php if ($restantPoids <= 0 || $restantColis <= 0) { ?>
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
						<?php
						// Bouton clôturer palette pour les responsables
						if ($utilisateur->isResp()) { ?>

                            <div class="card-footer padding-15 bg-warning text-dark text-center btnCloturePalette">
                                <i class="fa fa-box mr-1"></i> Clôturer
                            </div>

							<?php
						} // FIN bouton clôturer palette pour les responsables
						?>
                        </div>
                    </div>
					<?php

				} // FIN boucle sur les palettes en préparation

				?>

                <div class="col-2 padding-left-50 padding-right-50">
                    <button type="button" class="btn btn-dark form-control text-center text-20 padding-15 btnNouvellePalette">
                        <i class="fa fa-plus-square fa-lg"></i>
                        <p class="mb-0 mt-1 text-16">Nouvelle palette</p>
                    </button>
					<?php
					// On affiche le bouton "Voir complètes" s'il y en a...
					$paramsCompletes = [
						'statuts'     => '1,2',
						'vides'      => false,
						'id_produit' => $id_pdt,
						'mixte' => $pdt->isMixte(),
                        'id_client' => $pdt->getId_client(),
						'hors_frais' => true
					];
					$palettesCompletes = $palettesManager->getListePalettes($paramsCompletes);
					if (!empty($palettesCompletes)) { ?>
                        <br>
                        <button type="button" class="btn btn-dark form-control text-center text-20 padding-15 btnPalettesCompletes mt-2">
                            <i class="fa fa-ellipsis-h fa-lg"></i>
                            <p class="mb-0 mt-1 text-16">Voir complètes</p>
                        </button>
					<?php } ?>
                </div>

            </div> <!-- FIN conteneur ROW palettes -->

        </div> <!-- FIN row contenu -->

		<?php
		exit;

	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 4
	 * Description  : Température début srg
	 * Paramètres   : Froid
	 *  ----------------------------------- */

	if ($etape == 4) {

		// Récupération des variables
		$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
		if ($id_froid == 0) { echo 'ERREUR, identifaction du froid impossible'; exit; }

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Froid ['.$id_froid.']';
		}
		?>

        <div class="row mt-5 align-content-center">

            <!-- Bloc gauche : Pavé numérique -->
            <div class="col-5 text-center">

                <div class="alert alert-secondary">

                    <div class="input-group clavier">

                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="1">1</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="2">2</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="3">3</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="4">4</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="5">5</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="6">6</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="7">7</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="8">8</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="9">9</button></div>

                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur=".">.</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="0">0</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur="+">+/-</i></button></div>

                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger btn-large" data-valeur="C"><i class="fa fa-backspace"></i></button></div>
                        <div class="col-8"><button type="button" class="form-control mb-2 btn btn-success btn-large btnValiderTempDebut" data-valeur="V" data-id-froid="<?php
							echo $id_froid; ?>"><i class="fa fa-check"></i></button>
                        </div>

                    </div> <!-- FIN clavier -->
                </div> <!-- FIN alerte -->
            </div> <!-- FIN bloc gauche -->

            <!-- Bloc droit : champ et consignes -->
            <div class="col-7">

                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Température en début de surgélation :</h4>

                <!-- Champ T° -->
                <div class="row">
                    <div class="col-8">
                        <div class="input-group">
                        <span class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-thermometer-half text-50 gris-9 ml-2 mr-2"></i></span>
                        </span>
                            <input type="text" class="form-control text-100 text-center inputTempDebut" name="temp_debut" placeholder="0" value="">
                            <span class="input-group-append">
                              <span class="input-group-text text-36"> &deg;C</span>
                        </span>
                        </div>
                    </div>
                </div>

                <!-- Message d'erreur - masqué par défaut -->
                <div class="d-none alert alert-danger tempInvalide mt-2">
                    <i class="fa fa-exclamation-circle fa-3x float-left mr-3"></i> <strong>ATTENTION !</strong><p>Température invalide&hellip;</p>
                </div>

                <!-- Consignes -->
				<?php
				$configManager = new ConfigManager($cnx);
				$srgv_consignes_debut = $configManager->getConfig('srgv_consignes_debut');
				if ($srgv_consignes_debut instanceof Config) {
					if (strlen(trim($srgv_consignes_debut->getValeur())) > 0) { ?>

                        <div class="row">
                            <div class="col mt-3">
                                <div class="alert alert-secondary">
                                    <h5>Rappel des consignes</h5>
                                    <div>
										<?php echo $srgv_consignes_debut->getValeur(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

					<?php } // FIN test consigne non vide
				} // FIN test instanciation de la configuration
				?>

            </div> <!-- FIN bloc droit -->

        </div> <!-- FIN row conteneur -->

		<?php
	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 5
	 * Description  : Départ surgélation
	 *  ----------------------------------- */

	if ($etape == 5) {

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Départ congélation';
		}

	    ?>

        <div class="row mt-5 align-content-center">
            <div class="col text-center">
                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Entrée en surgélateur :</h4>
                <button type="button" class="btn btn-lg btn-info text-30 padding-50 btnDebutSrg text-center"><i class="fa fa-stopwatch mr-2 text-50"></i><br>Débuter la surgélation</button>
            </div>
        </div>


		<?php
		// A partir de 10h du matin (G = heure de 0 à 23)
		if (intval(date('G')) > 9) { ?>

            <div class="row mt-5 align-content-center">
                <div class="col text-center">
                    <h4><span class="mr-5">Cycle de <?php echo date('w') == 5 ? 'week-end' : 'nuit'; ?> :</span>


                        <input type="checkbox"
                               class="togglemaster-nuit"
							<?php echo intval(date('G')) > 11 ? 'checked' : ''; // On précoche à partir de midi ?>
                               data-toggle="toggle"
                               data-on="<i class='fas fa-moon mr-2'></i>Oui"
                               data-off="<i class='fas fa-sun mr-2'></i>Non"
                               data-onstyle="primary"
                               data-offstyle="secondary"
                               data-size="large"
                    </h4>
                </div>
            </div>

			<?php
		} // FIN test heure pou cycle de nuit


	} // FIN ETAPE

	/** ---------------------------------------------------------
	 * Etape        : 6
	 * Description  : Sorti de surgélateur + Température fin SRG
	 * Paramètres   : Froid
	 *  ------------------------------------------------------- */

	if ($etape == 6) {

		// Récupération des variables
		$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
		if ($id_froid == 0) { erreurLot(); exit; }
		$froidManager = new FroidManager($cnx);
		$froid = $froidManager->getFroid($id_froid, true);
		if (!$froid instanceof Froid) { erreurLot(); exit; }

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Froid ['.$id_froid.']';
		}

		// Enregistrement de la fin de surgélation
		if ($froid->getDate_sortie() == '' || $froid->getDate_sortie() == '0000-00-00 00:00:00' || $froid->getDate_sortie() == null) {
			$froid->setDate_sortie(date('Y-m-d H:i:s'));
			$froid->setId_user_maj($utilisateur->getId());
			$froid->setId_user_fin($utilisateur->getId());
			$froidManager->saveFroid($froid);

			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= '<br>date_sortie, id_user_maj et id_user_fin enregistrés';
			}

			// Log
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte("[SRGV] Enregistrement fin de surgélation froid#".$id_froid) ;
			$logsManager->saveLog($log);

			// Alerte de temps de surgélation
			$configManager = new ConfigManager($cnx);
			$config_duree_srgv_max  = $configManager->getConfig('duree_srgv_max');

			// On vérifie que l'alerte est activée
			$activationAlerte = $configManager->getConfig('alerte3_actif');

			// Si l'alerte est active et qu'on est pas en cycle de nuit
			if ($activationAlerte instanceof Config && intval($activationAlerte->getValeur()) == 1 && $froid->getNuit() == 0) {

                if ($config_duree_srgv_max instanceof Config) {

                    $dureeMax = floatval($config_duree_srgv_max->getValeur()); // En float pour comparaison avec la diff
                    if ($dureeMax > 0.0) {

                        // On calcule la durée effective
                        $hdeb = strtotime($froid->getDate_entree());
                        $hfin = strtotime($froid->getDate_sortie());
                        $diff = round(abs($hfin - $hdeb) / 3600,2); // On converti la différence en heures avec 2 décimales pour les minutes

                        // Si elle est supérieur à la durée max configurée : alerte
                        if ($diff > $dureeMax) {

                            $alerteManager = new AlerteManager($cnx);
                            $id_froid_type = intval($froidManager->getFroidTypeByCode('srgv'));

                            // Comme la traçabilité se fait au niveau du lot on crée autant d'alertes que de lots dans la surgélation
                            foreach ($froid->getLots() as $lotsrg) {
                                $alerte = new Alerte([]);
                                $alerte->setId_lot($lotsrg->getId());
                                $alerte->setType(3);            // Type 3 = Durée surgélation
                                $alerte->setId_froid($id_froid);
                                $alerte->setId_froid_type($id_froid_type);
                                $alerte->setNumlot($lotsrg->getNumlot());
                                $alerte->setDate(date('Y-m-d H:i:s'));
                                $alerte->setId_user($utilisateur->getId());
                                $alerte->setNom_user($utilisateur->getNomComplet());
                                $alerte->setValeur(round($diff * 60));
                                if ($alerteManager->saveAlerte($alerte)) {
									$alerteManager->envoiMailAlerte($alerte);
                                }
                            }

                        } // FIN test délais max dépassé
                    } // FIN test durée Max > 0
                } // FIN Config définie
			} // FIN test alerte active / cycle de nuit
		} // FIN test date de sortie non enregistrée
		?>

        <!-- Saisie de la température de fin de surgélation -->
        <div class="row mt-5 align-content-center">

            <!-- bloc gauche : Pavé numérique -->
            <div class="col-5 text-center">

                <div class="alert alert-secondary">

                    <div class="input-group clavier">

                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="1">1</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="2">2</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="3">3</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="4">4</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="5">5</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="6">6</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="7">7</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="8">8</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="9">9</button></div>

                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur=".">.</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="0">0</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur="+">+/-</i></button></div>

                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger btn-large" data-valeur="C"><i class="fa fa-backspace"></i></button></div>
                        <div class="col-8"><button type="button" class="form-control mb-2 btn btn-success btn-large btnValiderTempFin" data-valeur="V" data-id-froid="<?php
							echo $id_froid; ?>"><i class="fa fa-check"></i></button>
                        </div>

                    </div> <!-- FIN clavier -->
                </div> <!-- FIN alerte -->
            </div> <!-- FIN bloc gauche -->

            <!-- Bloc droit : champ et consignes -->
            <div class="col-7">

                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Température en fin de surgélation :</h4>

                <!-- Champ T° -->
                <div class="row">
                    <div class="col-8">
                        <div class="input-group">
                              <span class="input-group-prepend">
                                  <span class="input-group-text"><i class="fa fa-thermometer-half text-50 gris-9 ml-2 mr-2 ifa-tempfin"></i></span>
                            </span>
                            <input type="text" class="form-control text-100 text-center inputTempFin" name="temp_fin" placeholder="0" value="-">
                            <span class="input-group-append">
                                  <span class="input-group-text text-36"> &deg;C</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Message d'erreur - masqué par défaut -->
                <div class="d-none alert alert-danger tempInvalide mt-2">
                    <i class="fa fa-exclamation-circle fa-3x float-left mr-3"></i> <strong>ATTENTION !</strong><p>Température invalide&hellip;</p>
                </div>

				<?php
				// Récupération des températures mini et maxi autorisées pour le traitement (gestion des alertes et du blocage)
				$configManager      = new ConfigManager($cnx);
				$config_tmp_srg_min = $configManager->getConfig('tmp_srg_min');
				$config_tmp_srg_max = $configManager->getConfig('tmp_srg_max');
				if ($config_tmp_srg_min instanceof Config && $config_tmp_srg_max instanceof Config) { ?>

                    <!-- Affichage des consignes (si les températures sont bien configurées) -->
                    <div class="row">
                        <div class="col mt-3">
                            <div class="alert alert-secondary temp-controles" data-temp-controle-min="<?php echo intval($config_tmp_srg_min->getValeur()); ?>" data-temp-controle-max="<?php echo intval($config_tmp_srg_max->getValeur()); ?>">
                                <h5>Rappel des consignes</h5>
                                <ul>
                                    <li id="consignesTemp">Température cible en fin de surgélation entre <?php echo $config_tmp_srg_min->getValeur(); ?>°C et <?php echo $config_tmp_srg_max->getValeur(); ?>°C</li>
                                    <li>Si la température en fin de surgélation est supérieure à <?php echo $config_tmp_srg_max->getValeur(); ?>°C, poursuivre la surgélation (sauf en cas de panne)</li>
                                    <li>En cas de panne, prévenir le responsable</li>
                                </ul>

                            </div>
                        </div>
                    </div>

					<?php
				} // FIN test températures d'alertes configurées ?>

            </div> <!-- FIN bloc droit -->
        </div> <!-- FIN row conteneur -->

		<?php
		exit;

	} // FIN ETAPE

	/** --------------------------------------------------------
	 * Etape        : 7
	 * Description  : Contrôle LOMA sur les produits concernés
	 * Paramètres   : Froid
	 *  ----------------------------------------------------- */

	if ($etape == 7) {

		// Récupération des variables
		$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
		if ($id_froid == 0) { erreurLot(); exit; }
		$froidManager = new FroidManager($cnx);
		$froid = $froidManager->getFroid($id_froid);
		if (!$froid instanceof Froid) { erreurLot(); exit; }

		$id_lot_pdt_froid = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Froid ['.$id_froid.']';
		}

		// Aucun produit sélectioné : liste des produits
		if ($id_lot_pdt_froid == 0) {

			// On récupère les produits d'une op de froid (en param) ayant un loma à 1 et pour lesquels les tests n'ont pas été faits
			$listeFroidProduitsLoma = $froidManager->getLomaAfaireFromFroid($froid);



			// Si il y en a, on affiche la liste des produits sous forme de cartes
			if (!empty($listeFroidProduitsLoma)) { ?>

                <div class="row mt-3 align-content-center">

                    <div class="col text-center">

                        <div class="alert alert-secondary">
                            <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Produits à contrôler :</h4>
                            <div class="row justify-content-md-center">

								<?php
								foreach ($listeFroidProduitsLoma as $froidProduit) {

									$pdt = $froidProduit->getProduit();
									if (!$pdt instanceof Produit) {
										continue;
									}

									if (strlen($pdt->getNom()) > 46) {
										$sizeTxt = 'text-16';
									} else if (strlen($pdt->getNom()) > 38) {
										$sizeTxt = 'text-18';
									} else if (strlen($pdt->getNom()) > 30) {
										$sizeTxt = 'text-20';
									} else {
										$sizeTxt = '';
									}
									?>

                                    <div class="col-2 mb-3">
                                        <div class="card bg-warning pointeur carte-pdt carte-pdt-loma"
                                             data-id-lot-pdt-froid="<?php echo $froidProduit->getId_lot_pdt_froid(); ?>">

                                            <div class="card-header">A contrôler</div>
                                            <div class="card-body">
                                                <h4 class="card-title mb-0 <?php echo $sizeTxt; ?>"><?php echo $pdt->getNom(); ?></h4>
                                            </div>
                                            <div class="card-footer text-12">Lot <span
                                                        class="badge badge-secondary text-16"><?php echo $froidProduit->getNumlot(); ?></span>
                                            </div>
                                        </div>
                                    </div>

									<?php
								} // FIN boucle sur les familles de produits actives

								?>
                            </div>
                        </div>
                    </div>
                </div>
				<?php

				// Sinon on dis ok et bouton next...
			} else {

				?>
                <form id="controleLoma">
                    <input type="hidden" name="id_froid" value="<?php echo $froid->getId(); ?>"/>
                    <input type="hidden" name="mode" value="saveLomaApres"/>

                    <div class="row">
                        <div class="col text-center header-loma">
                            <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3 mt-3"></i>Surveillance du contrôle de détection métallique LOMA :</h4>
                            <div class="badge badge-dark text-24 badge-pill mt-3">Passage des tests après produits</div>
                        </div>
                    </div>
                    <div class="row mt-3 masque-clavier-virtuel">

                        <div class="col-6 offset-3 tests-plaquettes">

                            <div class="alert alert-secondary row">


                                <div class="col-4 text-center loma-test-btns">
                                    <h4>Test non ferreux</h4>
                                    <p>Taille : 5.5mm</p>
                                    <button type="button" class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light" data-test="nfe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                    <button type="button" class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light" data-test="nfe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                                </div>

                                <div class="col-4 text-center loma-test-btns">
                                    <h4>Test inox</h4>
                                    <p>Taille : 5.5mm</p>
                                    <button type="button" class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light" data-test="inox" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                    <button type="button" class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light" data-test="inox" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                                </div>


                                <div class="col-4 text-center loma-test-btns">
                                    <h4>Test ferreux</h4>
                                    <p>Taille : 3.0mm</p>
                                    <button type="button" class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light" data-test="fe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                    <button type="button" class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light" data-test="fe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                                </div>

                                <div class="col-12 text-center">
                                    <p class="small pt-2 gris-7">Les tests doivent sonner pour être validés.</p>
                                </div>

                            </div>

                        </div>



                    </div>
                    <div class="resultats-tests d-none">
                        <input type="hidden" name="resultest_nfe"  value="-1" />
                        <input type="hidden" name="resultest_inox" value="-1" />
                        <input type="hidden" name="resultest_fe"   value="-1" />
                    </div>
                </form>
				<?php

			} // FIN test lot pdt reçu

			// Si un produit a été choisi
		} else {


			$froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
			if (!$froidProduit instanceof FroidProduit) { ?>
                <div class="alert alert-danger"><h4>ERREUR !</h4><p>Instanciation du produit/froid impossible...</p><p><code>Code erreur : X4QZ3O2Q</code></p></div>
				<?php exit; }

			// Formulaire contrôle LOMA
			?>
            <form id="controleLoma" class="hidden">
                <input type="hidden" name="id_lot_pdt_froid" value="<?php echo $id_lot_pdt_froid; ?>"/>
                <input type="hidden" name="id_froid" value="<?php echo $id_froid; ?>"/>
                <input type="hidden" name="mode" value="saveLoma"/>
                <div class="row">
                    <div class="col text-center">
                        <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3 mt-3"></i>Surveillance du contrôle de détection métallique LOMA :</h4>
                        <div class="badge badge-dark text-24 badge-pill mt-3"><?php echo $froidProduit->getProduit()->getNom(); ?></div>
                    </div>
                </div>
                <div class="row mt-3 masque-clavier-virtuel">
                    <div class="col-5"></div>
                    <div class="col-2 ml-4 alert alert-dark row">
                        <div class="col-12 text-center loma-test-btns">

                            <h4>Test produit</h4>
                            <p>Détection corps étranger</p>
                            <button type="button" class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light" data-test="pdt" data-resultat="1"><i class="fa fa-exclamation-triangle fa-lg"></i></button>
                            <button type="button" class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light" data-test="pdt" data-resultat="0"><i class="fa fa-check fa-lg"></i></button>

                        </div>
                        <div class="col-12 text-center">
                            <p class="small pt-2 gris-7">Ne doit pas sonner pour être validé.</p>
                        </div>
                    </div>

                </div>
                <div class="row mt-3 loma-commentaires">
                    <div class="col-6 offset-3">
                        <div class="alert alert-secondary">
                            <div class="row">
                                <div class="col-9">
                                    <label>Commentaires :</label>
                                    <textarea name="commentaires" class="form-control" id="champ_clavier"></textarea>
                                </div>
                                <div class="col-3">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-lg btn-info padding-20-10 form-control btn-valid-loma"><i class="fa fa-check fa-lg mb-2"></i><br/>Terminé</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="resultats-tests d-none">
                    <input type="hidden" name="resultest_pdt"  value="-1" />
                </div>
            </form>
			<?php


		} // FIN test produits loma

		exit;




	} // FIN ETAPE


	/** ----------------------------------------
	 * Etape        : 8
	 * Description  : Emballages de la vue
	 *  ----------------------------------- */

	if ($etape == 8) { ?>

        <div class="row mt-3 align-content-center">
            <div class="col text-center">

                <div class="alert alert-secondary">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3 mb-3"></i>Emballages disponibles en Surgélation Verticale  :</h4>

                    <!-- Conteneur des cartes d'emballages -->
                    <div class="row" id="containerListeEmballages">
						<?php
						// Fonction déportée pour pagination Ajax
						modeListeCartesEmballage();
						?>
                    </div> <!-- FIN conteneur cartes -->
                </div> <!-- FIN alerte -->
            </div> <!-- FIN col -->
        </div> <!-- FIN row -->

		<?php
		exit;

	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 9
	 * Description  : Conformité
	 * Paramètres   : Froid
	 *  ----------------------------------- */

	if ($etape == 9) {

		// Récupération des variables
		$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
		if ($id_froid == 0) { erreurLot(); exit; }
		$froidManager = new FroidManager($cnx);
		$froid = $froidManager->getFroid($id_froid);
		if (!$froid instanceof Froid) { erreurLot(); exit; }
		?>

        <div class="alert alert-secondary mt-3 padding-50">

            <div class="col-6 offset-3 mb-3">
                <h4><i class="fa fa-clipboard-check fa-2x ml-2 mr-3 vmiddle gris-9"></i><span class="vmiddle">Validation finale du contrôleur :</span></h4>
            </div>


            <div class="row align-content-center">
                <div class="col-3 offset-3">
                    <button type="button" class="btn btn-danger btn-lg form-control padding-25 text-30 btnConformiteSrg" data-conformite="0"><i class="fa fa-times fa-lg mr-3"></i>Non Conforme</button>
                </div>

                <div class="col-3">
                    <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnConformiteSrg" data-conformite="1"><i class="fa fa-check fa-lg mr-3"></i>Conforme</button>
                </div>
            </div> <!-- FIN row boutons -->

        </div> <!-- FIN alerte -->
		<?php

		exit;

	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 10
	 * Description  : Produits SRG en cours
	 * Paramètres   : Froid
	 *  ----------------------------------- */

	if ($etape == 10) {

		modeShowListeProduitsFroid();
		exit;

	} // FIN ETAPE

	/** -----------------------------------------------------
	 * Etape        : 11
	 * Description  : Contrôle LOMA (Tests AVANT)
	 * Paramètres   : Froid
	 *  ----------------------------------------------------- */

	if ($etape == 11) {

		// Récupération des variables
		$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
		if ($id_froid == 0) { erreurLot(); exit; }
		$froidManager = new FroidManager($cnx);
		$froid = $froidManager->getFroid($id_froid);
		if (!$froid instanceof Froid) { erreurLot(); exit; }

		?>
        <form id="controleLoma">
            <input type="hidden" name="id_froid" value="<?php echo $froid->getId(); ?>"/>
            <input type="hidden" name="mode" value="saveLomaAvant"/>

            <div class="row">
                <div class="col text-center header-loma">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3 mt-3"></i>Surveillance du contrôle de détection métallique LOMA :</h4>
                    <div class="badge badge-dark text-24 badge-pill mt-3">Passage des tests avant produits</div>
                </div>
            </div>
            <div class="row mt-3 masque-clavier-virtuel">

                    <div class="col-6 offset-3 tests-plaquettes">

                        <div class="alert alert-secondary row">


                            <div class="col-4 text-center loma-test-btns">
                                <h4>Test non ferreux</h4>
                                <p>Taille : 5.5mm</p>
                                <button type="button" class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light" data-test="nfe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                <button type="button" class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light" data-test="nfe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                            </div>

                            <div class="col-4 text-center loma-test-btns">
                                <h4>Test inox</h4>
                                <p>Taille : 5.5mm</p>
                                <button type="button" class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light" data-test="inox" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                <button type="button" class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light" data-test="inox" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                            </div>


                            <div class="col-4 text-center loma-test-btns">
                                <h4>Test ferreux</h4>
                                <p>Taille : 3.0mm</p>
                                <button type="button" class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light" data-test="fe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                <button type="button" class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light" data-test="fe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                            </div>

                            <div class="col-12 text-center">
                                <p class="small pt-2 gris-7">Les tests doivent sonner pour être validés.</p>
                            </div>

                        </div>

                    </div>



            </div>

            <div class="resultats-tests d-none">
                <input type="hidden" name="resultest_nfe"  value="-1" />
                <input type="hidden" name="resultest_inox" value="-1" />
                <input type="hidden" name="resultest_fe"   value="-1" />
            </div>
        </form>
		<?php

		exit;

	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 100
	 * Description  : Fin du traitement
	 *  ----------------------------------- */

	if ($etape == 100) {
		?>

        <div class="alert alert-secondary mt-3 padding-50">

            <div class="col-6 offset-3 mb-3">
                <h4 class="text-center"><span class="vmiddle">Fin de traitement</span></h4>
            </div>

            <div class="row align-content-center">
                <div class="col-4 offset-4 text-center">
                    <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnConfirmClotureSrg">
                        <i class="fa fa-flag-checkered fa-2x mr-3"></i><div>Clôturer la surgélation</div>
                    </button>
                </div>
            </div>

        </div> <!-- FIN alerte -->

		<?php

		exit;

	} // FIN ETAPE


	/** ----------------------------------------
	 * Etape        : 101
	 * Description  : Liste des surgélation non clôturées
	 *  ----------------------------------- */

	if ($etape == 101) {

		// On récupère les surgélations non clôturées
		$froidManager = new FroidManager($cnx);
		$params = [
			'type_code'     => 'srgv',   // Type de Froid
			'statuts'       => '0,1',   // En cours ou bloqué
			'lots_objets'   => true,    // On récupère les objets Lot
			'nb_pdts'       => true     // On récupère le nombre de produits
		];
		$listeSrgEnCours = $froidManager->getFroidsListe($params);

		// Si aucune surgélation en cours
		if (empty($listeSrgEnCours)) {


			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'] = '<i class="fa fa-ban text-danger"></i> Aucun objet trouvé [type SRGV] [statut 0|1] ';
			}


			?>

            <!-- Aucune surgélation dans le pipe -->
            <div class="row mt-2 align-content-center">
                <div class="col text-center">
                    <div class="alert alert-secondary">

                       <span class="fa-stack fa-2x mt-5">
                           <i class="fas fa-snowflake fa-stack-1x"></i>
                           <i class="fas fa-ban fa-stack-2x" style="color:Tomato"></i>
                       </span>

                        <h3 class="gris-7 mt-3 mb-5">Aucune surgélation en cours&hellip;</h3>

                        <button type="button" class="mb-5 btn btn-info btn-lg text-28 padding-top-15 padding-bottom-15 padding-left-50 padding-right-50 btnRetourEtape0">
                            <i class="fa fa-undo text-22 mr-1"></i> Retour
                        </button>

                    </div> <!-- FIN alerte -->
                </div> <!-- FIN col -->
            </div> <!-- FIN row conteneur -->

			<?php

			// On ne vas pas plus loin...
			exit;

		} // FIN test aucune SRG non clôturée
		?>

        <!-- Surgélations non clôturées : En-tête -->
        <div class="row">
            <div class="col mt-3">
                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Surgélations verticales en cours :</h4>
            </div>
        </div>

		<?php

        if ($utilisateur->isDev()) {
			$s = count($listeSrgEnCours) > 1 ? 's' : '';
			$_SESSION['infodevvue'] = count($listeSrgEnCours) . ' objet'.$s.' trouvé'.$s.' [type SRGV] [statut 0|1] ';
		}

		// Boucle sur les SRG en cours.
		$i = 0;
		foreach ($listeSrgEnCours as $froid) {

			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= '<br>Froid ['.$froid->getId().']';
			}

			if ($i % 4 == 0) { ?><div class="clearfix"></div><?php } $i++; // Gestion du retour à la ligne tous les 4 blocs
            ?>

            <!-- Carte de l'OP de froid -->
            <div class="card text-white bg-info mb-3 carte-srg d-inline-block mr-3" style="max-width: 20rem;" data-id-froid="<?php echo $froid->getId(); ?>">

                <!-- Header de la carte : identifiant de l'OP -->
                <div class="card-header text-36"><?php echo 'SRGV'.sprintf("%04d", $froid->getId()); ?></div>

                <!-- Corps de la carte : détails -->
                <div class="card-body">

					<?php
					// Si le traitement n'a plus de produit, donc plus de lot... on propose de le supprimer
					if (empty($froid->getLots())) { ?>

                        <div class="alert alert-warning mb-3">
                            <span class="fa-stack fa-lg mr-2">
                               <i class="fas fa-box fa-stack-1x"></i>
                               <i class="fas fa-ban fa-stack-2x" style="color:Tomato"></i>
                           </span>
                            Aucun produit
                        </div>
                        <button type="button" class="btn btn-danger btn-large padding-20-40 form-control btnSupprimerFroidVide" data-id-froid="<?php echo $froid->getId(); ?>"><i class="fa fa-trash-alt fa-lg mr-2"></i>Supprimer</button>

						<?php
						// Des produits sont associés au traitement...
					} else { ?>
                    <table>
                        <tr>
                            <td>Lots :</td>
                            <th class="text-right"><?php

								// On gère l'espacement du premier pour les lignes de séparation
								$premier = true;

								// boucle sur les lots
								foreach ($froid->getLots() as $froidLot) {

									// Récupération des quantièmes en production du lot
									$quantiemes = $froidManager->getQuantiemesLotFroid($froid->getId(), $froidLot);

									// On concatène les quantièmes de chaque lot
									foreach ($quantiemes as $quantieme) { ?>

                                        <span class="badge badge-secondary text-16 w-100 <?php echo !$premier ? 'mt-1' : ''; ?>"><?php echo $froidLot->getNumlot(). $quantieme; ?></span>

										<?php $premier  = false;
									} // FIN boucle quantièmes

									// Si on a aucun quantième, on affiche simplement le lot...
									if (empty($quantiemes)) { ?>

                                        <span class="badge badge-secondary text-16 w-100 <?php echo !$premier ? 'mt-1' : ''; ?>"><?php echo $froidLot->getNumlot(); ?></span>

										<?php
									} // FIN test aucun quantième

									$premier = false;
								} // FIN boucle sur les lots
								?>
                        </tr>

                        <tr>
                            <td class="vmiddle nowrap">Produits :</td>
                            <th class="text-right"><span class="badge badge-info text-18"><?php echo $froid->getNb_produits(); ?></span></th>
                        </tr>

                        <tr>
                            <td class="vmiddle nowrap">Blocs :</td>
                            <th class="text-right"><span class="badge badge-info text-18"><?php echo $froidManager->getNbColisFroid($froid); ?></span></th>
                        </tr>

                        <tr>
                            <td class="vmiddle nowrap">Poids total :</td>
                            <th class="text-right text-18"><?php
								// Formatage CSS des décimales du poids
								$poidsTotalFroid        = number_format($froidManager->getPoidsFroid($froid),3, '.', '');
								$poidsTotalFroidArray   = explode('.', $poidsTotalFroid);
								echo $poidsTotalFroidArray[0] . '.<span class="text-16">'.$poidsTotalFroidArray[1].'</span>'; ?> kg</th>
                        </tr>

						<?php
						// Si la surgélation a commencée... (test sur présence d'une heure de début)
						if ($froid->isEnCours()) { ?>

                            <!-- Température de début -->
                            <tr>
                                <td class="nowrap">Temp. début :</td>
                                <th class="text-right text-18">
									<?php
									// Echapement T° début inconnue ?!
									if ($froid->getTemp_debut() == '') { echo $na; }
									else {
										$tempDebutSrg       = number_format($froid->getTemp_debut(),3, '.', '');
										$tempDebutSrgArray  = explode('.', $tempDebutSrg);
										echo $tempDebutSrgArray[0] . '.<span class="text-16">'.$tempDebutSrgArray[1].'</span>'; ?> &deg;C
										<?php
									} // FIN test température de début renseignée ?>
                                </th>
                            </tr>

                            <!-- Heure d'entrée dans le tunel -->
                            <tr>
                                <td class="nowrap vmiddle">Entrée surgélateur :</td>
                                <th class="text-right text-16"><?php
									echo Outils::getDate_verbose($froid->getDate_entree(), false, ' - '); ?>
                                </th>
                            </tr>

							<?php
						} // FIN surgélation commencée ?>

                        <tr>
							<?php
							// Si la surgélation commencée est terminée (test sur présence d'une heure de sortie)
							if ($froid->isSortie()) { ?>

                                <!-- Heure de sortie -->
                                <td class="nowrap vmiddle">Sortie surgélateur :</td>
                                <th class="text-right text-16"><?php
									echo Outils::getDate_verbose($froid->getDate_sortie(), false, ' - '); ?>
                                </th>

								<?php
								// Sinon, on affiche la sortie estimée
							} else if ($froid->isEnCours()) { ?>

                                <!-- Heure de sortie estimée -->
                                <td class="nowrap vmiddle">Sortie estimée :</td>
                                <th class="text-right text-16">		<?php



								// Si on est en cycle de nuit
								if ($froid->getNuit() == 1) { ?>
                                    Cycle de nuit
								<?php } else {
									// Calcul : date entrée + 3 heures

									$sortieEstime = date('Y-m-d H:i',strtotime('+3 hour',strtotime($froid->getDate_entree())));
									echo Outils::getDate_verbose($sortieEstime, false, ' - '); ?></th>
									<?php
								} // FIN test cyle de nuit
								?>
                                </th>
								<?php
							} // FIN test surgélation sortie ou non ?>
                        </tr>

                    </table>

			<?php
		} // FIN test produits associés au traitement
		?>

                </div> <!-- FIN body carte -->

				<?php
				// Variables footer de la carte
				if ($froid->getStatut() == 1) {

					$footerCardFa   = 'pause';
					$footerCardTxt  = 'Bloquée';
					$footerCardBg   = 'bg-danger';

				} else if ($froid->isEnCours() && !$froid->isSortie()) {

					$footerCardFa   = 'clock';
					$footerCardTxt  = 'En surgélateur';
					$footerCardBg   = 'bg-success';

				} else if ($froid->isSortie()) {

					$footerCardFa   = 'check-square';
					$footerCardTxt  = 'Sortie du surgélateur';
					$footerCardBg   = 'bg-primary';

				} else {

					$footerCardFa   = 'clipboard-list';
					$footerCardTxt  = 'En préparation';
					$footerCardBg   = '';
				} // FIN variables foooter carte
				?>

                <!-- Footer de la carte : état du traitement -->
                <div class="card-footer <?php echo $footerCardBg; ?>">
                    <i class="fa fa-lg fa-<?php echo $footerCardFa; ?> mr-2"></i><?php echo $footerCardTxt; ?>
                </div>

            </div> <!-- FIN carte -->
			<?php

		} // FIN boucle sur les lots en atelier

		exit;

	} // FIN ETAPE

} // FIN MODE


/* ------------------------------------------
MODE - Charge le ticket
-------------------------------------------*/
function modeChargeTicketLot() {

	global
	$cnx, $utilisateur;

	// Récupération des variables
	$etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;
	$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	$identifiant = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';

	$err = '<span class="badge danger badge-pill text-14">ERREUR !</span>';

	if ($utilisateur->isDev()) { ?>
        <kbd class="w-100 btnConsoleDev"><i class="fa fa-user-secret"></i> Console Dev</kbd>
	<?php }


	/** ----------------------------------------
	 * TICKET
	 * Etape        : 101
	 * Description  : Sélection SRG
	 *  ----------------------------------- */

	if ($etape == 101 ) { ?>

        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot="0">
            &mdash;
        </div>

        <p class="mt-2 text-center">Sélectionnez une surgélation&hellip;</p>

        <!-- Bouton retour accueil froid -->
        <button type="button" class="btn btn-info btn-lg form-control btnRetourEtape0 text-left mt-1"><i class="fa fa-undo fa-lg vmiddle mr-2"></i>Retour</button>

		<?php
		exit; // On ne vas pas plus loin



	} // FIN ETAPE 101



	/** ----------------------------------------
	 * TICKET
	 * Etape        : 1
	 * Description  : Sélection du lot
	 *  ----------------------------------- */

	if ($etape == 1) { ?>

        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot="0">
            &mdash;
        </div>
        <input id="lotid_photo" type="hidden" value="0">
        <p class="mt-2 text-center">Sélectionnez un lot&hellip;</p>
		<?php

		// Si c'est une nouvelle surgélation et pas un retour à la section des produits, on propose de revenir à l'écran d'accueil
		if ($id_froid == 0) { ?>

            <!-- Bouton retour accueil froid -->
            <button type="button" class="btn btn-danger btn-lg form-control btnRetourEtape0 text-left mt-1"><i class="fa fa-undo fa-lg vmiddle mr-2"></i>Annuler</button>

			<?php
		} // FIN test nouvelle surgélation

	} // FIN ETAPE 1

	/** ----------------------------------------------------
	 * TICKET
	 * Etape        : 51 (Statut propre au ticket)
	 * Description  : Affiche "en cours" après entrée surg.
	 *                avant le numéro de SRGV dans le ticket
	 *  ------------------------------------------------- */

	if ($etape == 51) { ?>

        <p id="justFreezed"><i class="fa fa-clock mr-1"></i> Surgélation en cours&hellip;</p>

	<?php } // FIN Etape


	/** ----------------------------------------
	 * TICKET
	 * Etape        : 2
	 * Description  : Sélection des pdts du lot
	 * Paramètre    : Lot
	 *  ----------------------------------- */

	if ($etape == 2) {

		// Récupération du lot
		$lotManager = new LotManager($cnx);
		$lot = $lotManager->getLot($identifiant);
		if ($identifiant == 0 || !$lot instanceof Lot) { echo $err; exit; }

		// Récupération des quantièmes du lot
		$quantiemes = $lotManager->getLotQuantiemes($lot);

		// Si on qu'un seul quantième, on le concatène avec le numéro du lot
		if (count($quantiemes) == 1 && trim(strtolower($quantiemes[0])) != 'a') {
			$lot->setNumlot($lot->getNumlot() . $quantiemes[0]);
		}
		?>

        <!-- Affichage du numéro de lot sélectionné -->
        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot=" <?php echo $lot->getId(); ?>">
			<?php echo $lot->getNumlot(); ?>
        </div>

		<?php
		// Si on a plusieurs quantièmes, on les liste ici pour info
		if (count($quantiemes) > 1) { ?>

            <table class="mb-0">
                <tr>
                    <td>Quantièmes de production :</td>
                    <th class="text-right">
						<?php
						foreach ($quantiemes as $quantieme) { ?>
                            <span class="badge badge-secondary text-14"><?php echo $quantieme; ?></span>
						<?php } // FIN boucle sur les quantièmes
						?>
                    </th>
                </tr>
            </table>


		<?php } // FIN plusieurs quantièmes
		?>

        <!-- Balise contenant l'ID du lot en cours dans le ticket, pour communication JS -->
        <input id="lotid_photo" type="hidden" value="<?php echo $lot->getId(); ?>">

        <!-- Bouton changement de vue -->
        <button type="button" class="btn btn-secondary btn-lg form-control btnChangeLot text-left mt-1"><i class="fa fa-backspace fa-lg vmiddle mr-2"></i>Sélectionner un autre lot</button>
		<?php

	} // FIN ETAPE

	/** ----------------------------------------
	 * TICKET
	 * Etapes       : Toutes (Etape mère)
	 * Description  : Affichage des infos SRG
	 *  ------------------------------------- */

	// Si on a un ID Froid, on peut accedder aux étapes filles suivantes...
	if ($id_froid > 0) {

		// Récupération des variables
		$froidManager = new FroidManager($cnx);
		$froid = $froidManager->getFroid($id_froid, true);

		// Valeur par défaut du N/A
		$na = '<span class="badge badge-warning badge-pill text-14">Non renseigné</span>';

		// On vérifie que l'objet Froid est bien instancié.
		if ($froid instanceof Froid) {

            // On assigne les emballages de la vue au traitement
			setEmballageProd($froid->getId());

			// Affichage "en cours" sur liste des produits d'une SRG déjà lancée
			if ($etape == 10 && $froid->isEnCours() && !$froid->isSortie()) { ?>

                <p><i class="fa fa-clock mr-1"></i> Surgélation en cours&hellip;</p>

			<?php } // FIN affichage en cours sur liste des produits d'une SRG déjà lancée

			// Affichage "terminée" sur liste des produits d'une SRG sortie du tunel
			if ($etape == 10 && $froid->isEnCours() && $froid->isSortie()) { ?>

                <p><i class="fa fa-check-square mr-1"></i> Surgélation terminée</p>

			<?php } // FIN affichage en cours sur liste des produits d'une SRG sortie
			?>

            <!-- Affichage du numéro de traitement -->
            <div class="alert bg-info text-24 text-center mb-1 <?php echo $etape < 4 ? 'mt-4' : ''; ?>">
                <i class="fa fa-ruler-vertical text-20"></i>
				<?php echo 'SRGV'.sprintf("%04d", $froid->getId()); ?>
            </div>


            <!-- Tableau récapitulatif des données -->
            <table class="mb-0">

                <!-- Liste des lots concernés par l'OP de froid -->
                <tr>
                    <td>Lots :</td>
                    <th class="text-right"><?php

                        // Gestion du style sur le premier élement (séparateur CSS)
						$premier = true;

                        // On stocke les ID des lots pour la communication avec l'API de Photo
						$lots_ids = '';

			
						// Boucle sur les lots de l'opération de froid...
						foreach ($froid->getLots() as $froidLot) {

							// Récupération des quantièmes en production du lot
							$quantiemes = $froidManager->getQuantiemesLotFroid($froid->getId(), $froidLot);

							// On concatène les quantièmes de chaque lot
							foreach ($quantiemes as $quantieme) { ?>

                                <span class="badge badge-secondary text-16 w-100 <?php echo !$premier ? 'mt-1' : ''; ?>"><?php echo $froidLot->getNumlot(). $quantieme; ?></span>

								<?php $premier  = false;
							} // FIN boucle quantièmes

							// Si on a aucun quantième, on affiche simplement le lot...
							if (empty($quantiemes)) { ?>

                                <span class="badge badge-secondary text-16 w-100 <?php echo !$premier ? 'mt-1' : ''; ?>"><?php echo $froidLot->getNumlot(); ?></span>

								<?php
							} // FIN test aucun quantième

							$premier  = false;
							$lots_ids.= $froidLot->getId().',';

						} // FIN boucle sur les lots
						?>

                        <!-- Balise d'identifcation pour liaison avec l'API photo -->
                        <input id="lotid_photo" type="hidden" value="<?php echo $lots_ids != '' ? substr($lots_ids,0,-1) : 0; ?>">
                    </th>
                </tr>

                <!-- Nombre de produits -->
                <tr>
                    <td class="vmiddle nowrap">Produits :</td>
                    <th class="text-right"><span class="badge badge-info text-20"><?php echo $froid->getNb_produits(); ?></span></th>
                </tr>

                <!-- Nombre de blocs -->
                <tr>
                    <td class="vmiddle nowrap">Blocs :</td>
                    <th class="text-right"><span class="badge badge-info text-20"><?php echo $froidManager->getNbColisFroid($froid); ?></span></th>
                </tr>

                <!-- Poids total -->
                <tr>
                    <td class="vmiddle nowrap">Poids total :</td>
                    <th class="text-right text-18"><?php
						$poidsTotalFroid =  number_format($froidManager->getPoidsFroid($froid), 3, '.', '');


                        // Formatage CSS des décimales
    					//$poidsTotalFroid = number_format($froidManager->getPoidsFroidFromCompos($froid),3, '.', '');
						$poidsTotalFroidArray = explode('.', $poidsTotalFroid);
						echo $poidsTotalFroidArray[0] . '.<span class="text-16">'.$poidsTotalFroidArray[1].'</span>'; ?> kg
                    </th>
                </tr>
                <?php
	            /** ----------------------------------------
				 * TICKET (Froid)
				 * Etape        : > 4
				 * Description  : Aff. température de début
				 *  ----------------------------------- */

				// On vérifie qu'une température de début est bien enregistrée
				if ($etape > 4 && $froid->getTemp_debut() != '') { ?>

                    <tr>
                        <td class="nowrap">Temp. début :</td>
                        <th class="text-right text-18">
							<?php
							    // Formatage CSS des décimales
								$tempDebutSrg       = number_format($froid->getTemp_debut(),3, '.', '');
								$tempDebutSrgArray  = explode('.', $tempDebutSrg);
								echo $tempDebutSrgArray[0] . '.<span class="text-16">'.$tempDebutSrgArray[1].'</span>'; ?> &deg;
							</th>
                    </tr>

				<?php } // FIN ETAPE

					/** -----------------------------------------------------
				 * TICKET (Froid)
				 * Etape        : 51 (Début SRG) | 10 (Liste Pdts)
				 * Description  : Heure entrée surg. et sortie estimée
				 *  -------------------------------------------------- */

				// Si la SRG viens d'être lancée (51) ou si on est sur la liste des produits (10)
                if ($etape == 51 || $etape == 10) {

                    // Si la surgélation est démarrée mais pas encore sortie du surgélateur...
                    if ($froid->isEnCours() && !$froid->isSortie()) { ?>

                        <!-- Heure entrée en surgélateur -->
                        <tr>
                            <td class="nowrap vmiddle">Entrée surgélateur :</td>
                            <th class="text-right text-16">
								<?php echo Outils::getDate_verbose($froid->getDate_entree(), false, ' - '); ?>
                            </th>
                        </tr>

                        <!-- Heure ESTIMEE de sortie (3h) -->
                        <tr>
                            <td class="nowrap vmiddle">Sortie estimée :</td>
                            <th class="text-right text-16">		<?php



								// Si on est en cycle de nuit
								if ($froid->getNuit() == 1) { ?>
                                    Cycle de nuit
								<?php } else {
								// Calcul : date entrée + 3 heures

								$sortieEstime = date('Y-m-d H:i',strtotime('+3 hour',strtotime($froid->getDate_entree())));
								echo Outils::getDate_verbose($sortieEstime, false, ' - '); ?></th>
							<?php
							} // FIN test cyle de nuit
							?>
                            </th>
                        </tr>

						<?php
					} // FIN test date entrée

                } // FIN ETAPE


				/** -------------------------------------------------------------
				 * TICKET (Froid)
				 * Etape        : > 5 | [!51] | 10->Sortie
				 * Description  : Heures entrée et sortie surg. + temps effectif
				 *  ---------------------------------------------------------- */

				// Après le départ de la SRG (5), hors 51 (pas juste après le départ), pas depuis la liste des produits, sauf si date sortie.
				if (($etape > 5 && $etape != 51 && $froid->isSortie())) { ?>

                    <!-- Heure entrée surgélateur  -->
                    <tr>
                        <td class="nowrap vmiddle">Entrée surgélateur :</td>
                        <th class="text-right text-16"><?php echo Outils::getDate_verbose($froid->getDate_entree(), false, ' - '); ?></th>
                    </tr>

                    <!-- Heure sortie tunel -->
                    <tr>
                        <td class="nowrap vmiddle">Sortie surgélateur :</td>
                        <th class="text-right text-16"><?php echo Outils::getDate_verbose($froid->getDate_sortie(), false, ' - '); ?></th>
                    </tr>

                    <!-- Temps effectif de la surgélation -->
                    <tr>
                        <td class="vmiddle">Temps surgélation :</td>
                        <th class="text-right text-16">

                            <?php
                            // Calcul de l'intervale
							$dateEntree     = new DateTime($froid->getDate_entree());
							$dateNow        = new DateTime($froid->getDate_sortie());
							$interval       = $dateEntree->diff($dateNow);

							$jours  = (intval($interval->format('%d')));
							$heures = (intval($interval->format('%h')));
							$min    = (intval($interval->format('%i')));

							if ($jours > 0) { $heures = $heures + 24; }
							echo  $heures . ' h ' . $min . ' min.'; ?>
                        </th>
                    </tr>

				<?php } // FIN ETAPE



				/** ----------------------------------------
				 * TICKET (Froid)
				 * Etape        : > 7
				 * Description  : Affiche la T° de fin
				 *  ----------------------------------- */

				// A partir de l'étape 8 (après avoir saisi la T° de fin), mais pas juste après départ SRG
				if ($etape > 7 && $etape != 51) {

				    // On exclu le cas d'une reprise de SRG non encore démarrée
				    if ($etape == 10 && $froid->getTemp_fin() == '') {} else {  ?>

                        <!-- T° de fin -->
                        <tr>
                            <td class="nowrap vmiddle">Temp. fin :</td>
                            <th class="text-right text-18">
								<?php
								if ($froid->getTemp_fin() == '') { echo $na; } else {

									// Formatage CSS des décimales
									$tempDebutSrg       = number_format($froid->getTemp_fin(),3, '.', '');
									$tempDebutSrgArray  = explode('.', $tempDebutSrg);
									echo $tempDebutSrgArray[0] . '.<span class="text-16">'.$tempDebutSrgArray[1].'</span>'; ?> &deg;C
								<?php } ?>
                            </th>
                        </tr>
                    <?php
				    } // FIN Exclusion reprise SRG non encore démarrée

				} // FIN ETAPE

				/** ----------------------------------------
				 * TICKET (Froid)
				 * Etape        : -
				 * Description  : Affiche la conformité
				 *  ----------------------------------- */

				// Si la conformité est renseignée (! -1)
				if ($froid->getConformite() > -1) { ?>

                    <!-- Conformité -->
                    <tr>
                        <td class="nowrap vmiddle">Conformité :</td>
                        <th class="text-right text-18">
							<?php
							if ($froid->getConformite() == 0) { ?>
                                <span class="badge badge-danger text-16">Non conforme</span>
							<?php } else { ?>Conforme<?php } ?>
                        </th>
                    </tr>

					<?php
				} // FIN ETAPE
				?>
            </table>

			<?php

			/** ----------------------------------------
			 * TICKET (Froid)
			 * Etapes       : 5 | 8 | 9 | 100 | 10*
			 * Description  : Bouton retour étape zéro
			 * Condition    : On viens de l'étape 0 ou du changement de rouleau
			 *                (identifiant = 1)
			 *  ----------------------------------- */

			// Départ SRG (5), Emballages (8), Conformité (9), Clôture (100), Liste produits (10) si depuis étape 0 (identifiant 1)
			if ($etape == 5 || $etape == 8 || $etape == 9 || $etape == 7 ||$etape == 100 || ($etape == 10 && $identifiant == 1)) { ?>

                <!-- Bouton retour étape zéro -->
                <button type="button" class="btn btn-secondary btn-lg form-control btnRetourEtape0 text-left margin-bottom-10 text-18">
                    <i class="fa fa-fw fa-lg fa-undo vmiddle mr-2"></i>Retour
                </button>

			<?php } // FIN ETAPE

			/** ----------------------------------------
			 * TICKET (Froid)
			 * Etape        : 5
			 * Description  : Bouton retour T° début
			 *  ----------------------------------- */

			// Départ surgélation prêt...
			if ($etape == 5) { ?>
                <button type="button" class="btn btn-secondary btn-lg form-control btnModifTempDebut text-left margin-bottom-10 text-18 padding-20-10">
                    <i class="fa fa-fw fa-thermometer-three-quarters fa-lg vmiddle mr-2"></i>Modifier la température&hellip;
                </button>

			<?php } // FIN ETAPE


			// SI on des incidents...

			if (isset($froid)) {

				$incidentsManager = new IncidentsManager($cnx);

				// On récup les id_lots ou il y a un incident pour ce traitement
				$id_lots = $incidentsManager->getLotsIncidentsByFroid($froid);

				foreach ($id_lots as $id_lot) {

					?>
                    <button type="button" class="btn btn-warning btn-lg form-control btnCommentaires text-left mb-3" data-toggle="modal" data-target="#modalCommentairesFront" data-id-lot="<?php echo $id_lot; ?>">
                        <i class="fa fa-info-circle fa-lg fa-fw vmiddle mr-2"></i>Infos incidents
						<?php if (count($id_lots) > 1) {
							$lotI = $lotManager->getLot($id_lot);
							if ($lotI instanceof Lot) { ?>
                                <p class="texte-fin small nomargin">Lot <?php echo $lotI->getNumlot(); ?></p>
							<?php }
							?>

						<?php } ?>
                    </button>
				<?php }
			} // FIN test froid



			?>

            <!-- Bouton changement de rouleau -->
            <button type="button" class="btn btn-secondary btn-lg form-control text-left margin-bottom-10 text-18 padding-20-10"
                    data-toggle="modal" data-target="#modalNouvelEmballage" >
                <i class="fa fa-fw fa-retweet fa-lg fa-fw vmiddle mr-2"></i>
                Changer de rouleau&hellip;
            </button>

            <!-- -Suivi retour de changement de rouleau -->
            <input type="hidden" id="etapeEnCours" value="<?php echo $etape; ?>"/>

			<?php
			/** --------------------------------------------------------------------------------------------
			 * TICKET (Froid)
			 * Etape        : 2 | 1*
			 * Description  : Bouton fin sélection produits
			 * Condition    : Depuis la selection des cartes de produits ou bien des lots si on a déjà
			 *                au moins un produit dans le traitement
			 *  ------------------------------------------------------------------------------------------ */

			if ($etape == 2 || ($etape == 1 && $froid->getNb_produits() > 0)) { ?>

                <!-- Bouton sélection pdt terminée -->
                <button type="button" class="btn btn-success btn-lg form-control btnFinSelectionPdts text-center text-24 btnPdtsSelectionnes">
                    <i class="fa fa-clipboard-check text-40 vmiddle mb-2 mt-2"></i><p class="nomargin">Sélection terminée</p>
                </button>

			<?php } // FIN ETAPE

			/** --------------------------------------------------------------------------------------------
			 * TICKET (Froid)
			 * Etape        : 10
			 * Description  : Bouton retour sélection produits
			 * Condition    : Surgélation non commencée (date début) + pas bloquée ni terminée (statut 0)
			 *  ----------------------------------------------------------------------------------------- */

			if ($etape == 10 && (!$froid->isEnCours()) && $froid->getStatut() == 0) { ?>

                <!-- Bouton retour sélection SRGs en cours -->
                <button type="button" class="btn btn-secondary btn-lg form-control btnRetourSelectionPdts text-left margin-bottom-10 text-18 padding-20-10">
                    <i class="fa fa-fw fa-lg fa-plus-square vmiddle mr-2"></i>Ajouter des produits&hellip;
                </button>

                <!-- Bouton début de surgelation : une fois tout étiqueté (masqué par défeut) -->
                <button type="button" class="btn btn-success btn-lg form-control btnFinEtiquetage text-center text-24 margin-top-25">
                    <i class="fa fa-check vmiddle text-40 vmiddle mb-2 mt-2"></i><p class="nomargin">Prêt à surgeler</p>
                </button>

			<?php } // FIN ETAPE

			/** --------------------------------------------------------------------------------------------
			 * Calcul de la prochaine étape en fonction des données présentes
			-------------------------------------------------------------------------------------------- */

			// Par défaut -> Fin de surgélation, saisir de la T° de sortie
			$nextEtape = 6;

			// Si on a une T° de SORTIE -> Emballages
			if ($froid->getTemp_fin() != 0.0 && $froid->getTemp_fin() != '' && $froid->getTemp_fin() != null) {
				$nextEtape = 7;
			}

			// Si on a PAS de T° de DEBUT -> Formulaire se saisie de T° de début
			if ($froid->getTemp_debut() == 0.0 || $froid->getTemp_debut() == '' || $froid->getTemp_debut() == null) {
				$nextEtape = 4;
			}

			// Si on a PAS de date d'ENTREE -> Sélection des lots & produits
			if (!$froid->isEnCours()) {
				$nextEtape = 1;
			}

			// Si on est aux emballages -> Confirmité
			if ($etape == 8) {
				$nextEtape = 9;
			}

			// Si le loma est ok mais que la prochaine étape est le loma, on passe au 8
			if ($nextEtape == 7 && $froidManager->isLomaTermine($froid)) {
				$nextEtape = 8;
			}


			/** --------------------------------------------------------------------------------------------
			 * TICKET (Froid)
			 * Etape        : 51 | 10*6
			 * Description  : Bouton fin SRG
			 * Conditions   : Dès le début de SRG (51) ou depuis la liste des produits si SRG en cours
			 *                SRG non bloquée ni terminée
			 *  -------------------------------------------------------------------------------------------- */

			if (($etape == 51 || ($etape == 10 && $nextEtape == 6)) && $froid->getStatut() == 0) { ?>

                <button type="button" class="btn btn-success btn-lg form-control btnFinSrg text-center text-24" data-toggle="modal" data-target="#modalConfirmFinFroid">
                    <i class="fa fa-sign-out-alt fa-lg vmiddle mb-2"></i><p class="nomargin">Sortie du surgélateur</p>
                </button>

			<?php } // FIN ETAPE


			/** --------------------------------------------------------------------------------------------
			 * TICKET (Froid) - ELSE
			 * Etape        : 8 | 10*
			 * Description  : Bouton Continuer (adaptatif)
			 * Condition    : Prochaine étape pas sélection lot ou depuis la gestion des emballages
			 *  -------------------------------------------------------------------------------------------- */

			else if (($etape ==  10 && $nextEtape > 1) || $etape ==  8) { ?>

                <button type="button" class="btn btn-success btn-lg form-control btnContinuerSrg text-center text-24" data-etape-suivante="<?php echo $nextEtape; ?>">
                    <i class="fa fa-play fa-lg vmiddle mb-2"></i><p class="nomargin">Continuer</p>
                </button>

			<?php } // FIN ETAPE



		} // FIN test objet froid instancié

	} // FIN affichage froid

} // FIN MODE

/* ------------------------------------------
MODE - Affiche les produits d'une espèce
-------------------------------------------*/
function modeShowProduitsFamille($id_espece = 0, $id_lot = 0, $exit = true) {

	global $utilisateur, $cnx;

	if ($id_espece  == 0) { $id_espece = isset($_REQUEST['id_espece']) ? intval($_REQUEST['id_espece']) : 0; }
	if ($id_lot     == 0) { $id_lot     = isset($_REQUEST['id_lot'])     ? intval($_REQUEST['id_lot'])     : 0; }
	if ($id_espece  == 0 || $id_lot == 0) { exit; }

	$produitsManager    = new ProduitManager($cnx);
	$familleManager     = new ProduitEspecesManager($cnx);
	$froidManager       = new FroidManager($cnx);
	$famille = $familleManager->getProduitEspece($id_espece);

	if (!$famille instanceof ProduitEspece) { exit; }

	// On récup les ID pdt de toute l'op de froid en cours, pour matcher ceux qui y sont déjà
	$id_froid       = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	$idPdtsFroidLot = $id_froid > 0 ? $froidManager->getIdPdtsFroidLot($id_froid, $id_lot) : [];

	// Préparation pagination (Ajax)
	$nbResultPpage      = 15;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode=showProduitsFamille&id_famille='.$id_espece.'&id_lot='.$id_lot;
	$start              = ($page-1) * $nbResultPpage;


	if ($utilisateur->isDev()) {
		$_SESSION['infodevvue'].= 'Lot ['.$id_lot.']';
		$_SESSION['infodevvue'].= '<br>Froid ['.$id_froid.']';
		$_SESSION['infodevvue'].= '<br>ProduitEspece ['.$id_espece.']';
	}


	$params = [
		'id_espece'        => $id_espece,
		'start'             => $start,
		'nb_result_page'    => $nbResultPpage
	];

	$froidManager = new FroidManager($cnx);
	$froidType = $froidManager->getFroidTypeByCode('srgv');
	if (intval($froidType) > 0) {
		$params['vue'] = $froidType;
	}

	$liste_produits = $produitsManager->getListeProduits($params);

	$nbResults  = $produitsManager->getNb_results();
	$pagination = new Pagination($page);

	$pagination->setUrl($filtresPagination);
	$pagination->setNb_results($nbResults);
	$pagination->setAjax_function(true);
	$pagination->setNb_results_page($nbResultPpage);
	$pagination->setNb_apres(2);
	$pagination->setNb_avant(2);

	if ($utilisateur->isDev()) {
		$nPdt = isset($liste_produits) ? count($liste_produits) : 0;
		$nPdtS = $nPdt > 1 ? 's' : '';
		$nPdtDp= ' :';
		if ($nPdt == 0) { $nPdt = 'Aucun'; $nPdtDp = '';}
		$_SESSION['infodevvue'].= '<br>'.$nPdt.' produit'.$nPdtS.' trouvé'.$nPdtS.$nPdtDp;
	}
	?>

    <div class="row mt-3 align-content-center">

        <div class="col text-center">

            <div class="alert alert-secondary">
                <h4 class="<?php echo empty($liste_produits) ? 'd-none' : ''; ?>"><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Ajouter un produit  :</h4>
                <div class="row">
					<?php
					$nbPdtOnThePage = count($liste_produits);

					if (empty($liste_produits)) { ?>

                        <div class="col-12 padding-50">

                            <i class="fa fa-exclamation-circle fa-3x gris-9 mb-3"></i>
                            <h5>Aucun produit disponible !</h5>
                            <p><i>Famille <?php echo $famille->getNom();?> en surgélation verticale</i></p>
                            <p>Contactez un administrateur...</p>
                        </div>

                    <?php }

					foreach ($liste_produits as $pdt) {

						if ($utilisateur->isDev()) {
							$_SESSION['infodevvue'].= '<br>Produit ['.$pdt->getId().']';
							$_SESSION['infodevvue'].= in_array($pdt->getId(), $idPdtsFroidLot) ? ' (déjà dans le traitement)' : '';
						}

						if (strlen($pdt->getNom()) > 46) {		    $sizeTxt = 'text-16';
						} else if (strlen($pdt->getNom()) > 38) {	$sizeTxt = 'text-18';
						} else if (strlen($pdt->getNom()) > 30) {   $sizeTxt = 'text-20';
						} else {        					        $sizeTxt = ''; }
						?>

                        <div class="col-2 mb-3">
                            <div class="card bg-info text-white pointeur carte-pdt <?php
							echo in_array($pdt->getId(), $idPdtsFroidLot) ? 'pdtDejaFroid' : ''; ?>" data-pdt-id="<?php echo $pdt->getId();?>" data-lot-id="<?php echo $id_lot;?>">

								<?php if (in_array($pdt->getId(), $idPdtsFroidLot)) { ?>
                                    <span class="iconDejaFroid badge badge-dark padding-5"><i class="fa fa-sign-in-alt fa-rotate-90 text-18"></i></span>
								<?php } ?>
                                <div class="card-header"><?php echo $famille->getNom(); ?></div>
                                <div class="card-body">
                                    <h4 class="card-title mb-0 <?php echo $sizeTxt; ?>"><?php echo $pdt->getNom();?></h4>
                                </div>
                                <div class="card-footer"><?php echo $pdt->getCode(); ?></div>
                            </div>
                        </div>

						<?php
					} // FIN boucle sur les familles de produits actives

					// Pagination (aJax)
					if (isset($pagination)) {
						// Si on a moins de 3 colis de libres à droite, on va a la ligne
						$nbCasse = [4,5,10,11,16,17];
						if (in_array($nbPdtOnThePage,$nbCasse)) { ?>
                            <div class="clearfix"></div>
						<?php }

						$pagination->setNature_resultats('produit');
						echo ($pagination->getPaginationBlocs());

					} // FIN test pagination
					?>
                </div>
            </div>
        </div>
    </div>

	<?php
	if ($exit) { exit; }

} // FIN mode

/* ---------------------------------------------------------
MODE -  Fonction déportée pour mise à jour
	du détail stock emballage de la SRGV
----------------------------------------------------------*/

function setEmballageProd($id_froid) {

	global $cnx, $consommablesManager, $logsManager;

	if (!$consommablesManager instanceof ConsommablesManager) {
		$consommablesManager = new ConsommablesManager($cnx);
	}

	// On récupère les emballages pour l'op de froid
	$emballagesFroid = $consommablesManager->getListeEmballages(['id_froid' => $id_froid]);

	// Si il n'y en a aucun encore, on associe tous les emballages « en cours » dont la famille est associée à la vue de froid concernée.
	if (empty($emballagesFroid)) {

		$vueManager = new VueManager($cnx);
		$froidManager = new FroidManager($cnx);
		$vueSrg = $vueManager->getVueByCode('srgv');
		if (!$vueSrg instanceof Vue) { ?><div class="alert alert-danger">Identification de la vue impossible.<br>Code Erreur : <code>SGQCHCUP</code></div><?php  exit; }

		$froid = $froidManager->getFroid($id_froid);
		if (!$froid instanceof Froid) { ?><div class="alert alert-danger">Identification du traitement impossible.<br>Code Erreur : <code>RTWAIYWX</code></div><?php  exit; }

		// Si on arrive à intégrer, on remet à jour la liste...
		if ($consommablesManager->setEmballagesVue($vueSrg, $froid)) {
			$consommablesManager->getListeEmballagesTicket(['id_froid' => $id_froid]);


			// Log
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte("[SRGV] Association des emballages au traitement " . $id_froid) ;
			$logsManager->saveLog($log);
		}

	} // FIN test aucun emballage

	return true;

} // FIN fonction

/* ------------------------------------------
MODE - Ajoute un produit à l'OP de froid
-------------------------------------------*/
function modeAddPdtFroid($params = []) {


	global $utilisateur, $cnx, $logsManager;

	if (!$utilisateur instanceof User) { exit('-1'); }

	// Récupération des variables
	if (!$params || empty($params)) {
		$id_pdt         = isset($_REQUEST['id_pdt'])        ? intval($_REQUEST['id_pdt'])       : 0;
		$id_lot         = isset($_REQUEST['id_lot'])        ? intval($_REQUEST['id_lot'])       : 0;
		$id_froid       = isset($_REQUEST['id_froid'])      ? intval($_REQUEST['id_froid'])     : 0;
		$nb_colis       = isset($_REQUEST['nb_colis'])      ? intval($_REQUEST['nb_colis'])     : 0;
		$nb_colis_add   = isset($_REQUEST['nb_colis_add'])  ? intval($_REQUEST['nb_colis_add']) : $nb_colis;
		$poids          = isset($_REQUEST['poids'])         ? floatval($_REQUEST['poids'])      : 0.0;
		$poids_add      = isset($_REQUEST['poids_add'])     ? floatval($_REQUEST['poids_add'])  : $poids;
		$quantieme      = isset($_REQUEST['quantieme'])     ? trim($_REQUEST['quantieme'])      : '';
		$id_palette     = isset($_REQUEST['id_palette'])    ? intval($_REQUEST['id_palette'])   : 0;
		$id_compo       = isset($_REQUEST['id_compo'])      ? intval($_REQUEST['id_compo'])     : 0;
		$new_palette    = isset($_REQUEST['new_palette'])   ? boolval($_REQUEST['new_palette']) : false;
	} else {
		$id_pdt         = isset($params['id_pdt'])          ? intval($params['id_pdt'])         : 0;
		$id_lot         = isset($params['id_lot'])          ? intval($params['id_lot'])         : 0;
		$id_froid       = isset($params['id_froid'])        ? intval($params['id_froid'])       : 0;
		$nb_colis       = isset($params['nb_colis'])        ? intval($params['nb_colis'])       : 0;
		$nb_colis_add   = isset($_REQUEST['nb_colis_add'])  ? intval($_REQUEST['nb_colis_add']) : $nb_colis;
		$poids          = isset($params['poids'])           ? floatval($params['poids'])        : 0.0;
		$poids_add      = isset($_REQUEST['poids_add'])     ? floatval($_REQUEST['poids_add'])  :$poids;
		$quantieme      = isset($params['quantieme'])       ? trim($params['quantieme'])        : '';
		$id_palette     = isset($params['id_palette'])      ? intval($params['id_palette'])     : 0;
		$id_compo       = isset($params['id_compo'])        ? intval($params['id_compo'])       : 0;
		$new_palette    = isset($params['new_palette'])     ? boolval($params['new_palette'])   : false;
	}

	// Instanciation des managers
	$froidManager   = new FroidManager($cnx);
	$lotsManager    = new LotManager($cnx);
	$palettesManager = new PalettesManager($cnx);

	// Gestion des erreurs
	if ($id_pdt == 0 || $id_lot == 0 || $nb_colis < 1 || $poids < 0.1  || $id_palette == 0) { exit('-1'); }


	$lot = $lotsManager->getLot($id_lot);
	if (!$lot instanceof Lot) { exit('-1'); }

	$lot->setDate_maj(date('Y-m-d H:i:s'));


	// v1.1, on commence par créer l'OP de froid si elle n'existe pas
	if ($id_froid == 0) {

		$typeCgl = $froidManager->getFroidTypeByCode('srgv');
		if (!$typeCgl || $typeCgl == 0) { exit('-3'); }

		$froid = new Froid([]);
		$froid->setId_type($typeCgl);
		$froid->setId_user_maj($utilisateur->getId());
		$id_froid = $froidManager->saveFroid($froid);
		if (!$id_froid || $id_froid == 0) { exit('-4'); }

		// Puisqu'on viens de créer l'op de froid, le lot concerné est officiellement dans la vue surgélation verticale
		$lotsManager->addLotVue($lot, 'srgv');       // Affecte le lot à la vue surgélation verticale

		// Log
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("[SRGV] Création du traitement OP froid ID " . $id_froid . " à l'ajout du premier produit.") ;
		$logsManager->saveLog($log);

	} // FIN test id froid

	if ($id_froid == 0) { exit('-5'); }

	// On tente de récupérer l'objet pdt/lot/froid

	$update = true;

	// On tente de récupérer l'objet pdt/lot/froid/quantieme/palette
	$pdtLotFroid = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid, $quantieme, $id_palette);
	if (!$pdtLotFroid instanceof FroidProduit) {

		$update = false;
		// v1.1 On crée le pdt/lot/froid si on a pas pu le récupérer
		$pdtLotFroid = new FroidProduit([]);
		$pdtLotFroid->setId_lot($id_lot);
		$pdtLotFroid->setId_pdt($id_pdt);
		$pdtLotFroid->setId_froid($id_froid);
	}

	$pdtLotFroid->setId_palette($id_palette);
	if (strtolower($quantieme) != 'a' && $lot->getComposition() != 2 && $quantieme != '<br') {

		$pdtLotFroid->setQuantieme($quantieme);
		$lotsManager->addQuantiemeIfNotExist($id_lot, $quantieme);
	}

	// On met à jour la date et l'user pour histo
	if ($update) {

		$pdtLotFroid->setDate_maj(date('Y-m-d H:i:s'));
		$pdtLotFroid->setUser_maj($utilisateur->getId());

	} else {

		$pdtLotFroid->setDate_add(date('Y-m-d H:i:s'));
		$pdtLotFroid->setUser_add($utilisateur->getId());
	}

	$id_pdtfroid = $froidManager->saveFroidProduit($pdtLotFroid);

	$lotsManager->saveLot($lot);

	// On enregistre l'id_lot_pdt_froid dans la table des compositions de palettes
	$id_lot_pdt_froid = $pdtLotFroid->getId_lot_pdt_froid() > 0 ? $pdtLotFroid->getId_lot_pdt_froid() : $id_pdtfroid;

	// Si on change de palette, l'id_pdt_froid de la compo est le nouveau
	if ($new_palette) { $id_lot_pdt_froid = $id_pdtfroid; }

	// Une compo a été créé dynamiquement à l'étape 3

	if ($id_lot_pdt_froid > 0) {

		$palettesManager = new PalettesManager($cnx);
		$compo = $palettesManager->getComposition($id_compo);
		if ($compo instanceof PaletteComposition) {

			$compo->setId_lot_pdt_froid($id_lot_pdt_froid);
			$compo->setSupprime(0);                       // On retire le flag "supprimé" qui empèche de faire n'importe quoi si on s'amuse a cliquer sur plein de palettes dans l'édition


			// on met à jour le poids et le nb de colis de la compo car il a pu être modifié apres la selection de la palette
			$compo->setPoids($poids);
			$compo->setNb_colis($nb_colis);

			$palettesManager->savePaletteComposition($compo);
			$palettesManager->purgeComposSupprimeesByFroidProduit($id_lot_pdt_froid);
		}
	}


	// Une fois le produit froid créé et la compo intégrée, on calcul le total pour mettre à jour le froidproduit
	$pdtLotFroid->setPoids($palettesManager->getPoidsTotalFroidProduit($id_lot_pdt_froid));
	$pdtLotFroid->setNb_colis($palettesManager->getNbColisTotalFroidProduit($id_lot_pdt_froid));
	$froidManager->saveFroidProduit($pdtLotFroid);

	$logVerbe = $update ? "Modification" : "Ajout";

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] ".$logVerbe." du produit ID " . $id_pdt . " sur le lot ID " . $id_lot . " à l'OP froid ID " . $id_froid . " quantième " . $quantieme . ", nb_colis = ". $nb_colis." poids = " . $poids) ;
	$logsManager->saveLog($log);

	// Retourne l'id de l'OP de froid pour maj CallBack aJax
	echo $id_froid;

	exit;

} // FIN mode

/* ---------------------------------------------------
MODE - Attribue un contrôle LOMA nécessaire au produit
----------------------------------------------------*/
function modeControleLomaPdt() {

	global $cnx, $logsManager;

	// Vérification des variables
	$id_pdt     = isset($_REQUEST['id_pdt'])    ? intval($_REQUEST['id_pdt'])   : 0;
	$id_lot     = isset($_REQUEST['id_lot'])    ? intval($_REQUEST['id_lot'])   : 0;
	$id_froid   = isset($_REQUEST['id_froid'])  ? intval($_REQUEST['id_froid']) : 0;
	if ($id_pdt == 0 || $id_lot == 0 || $id_froid == 0) { echo '-1'; exit; }

	// On récupère l'Id_lot_pdt
	$froidManager = new FroidManager($cnx);
	$froidProduit = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
	if (!$froidProduit instanceof FroidProduit) { exit; }

	$froidManager->setProduitLoma($froidProduit);

	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Définition du contrôle LOMA requis sur le produit ID_LOT_PDT_FROID " . $froidProduit->getId_lot_pdt_froid()) ;
	$logsManager->saveLog($log);

    // Si on a pas encore fait le controle test avant, on retourne le code "2", sinon "1"
    $froid = $froidManager->getFroid($id_froid);
    if (!$froid instanceof Froid) { exit('-2');}

    echo $froid->getTest_avant_fe() < 0 ? '2' : '1'; // SI pas fait, il est à -1 (valeur défaut BDD)

	exit;

} // FIN mode


/* ---------------------------------------------------------
FONCTION - Liste (pagination/ajax) des emballages SRG
----------------------------------------------------------*/

function modeListeCartesEmballage() {

	global $cnx;

	$consommablesManager = new ConsommablesManager($cnx);
	$vuesManager = new VueManager($cnx);

	// Préparation pagination (Ajax)
	$nbResultPpage      = 16;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode=listeCartesEmballage';
	$start              = ($page-1) * $nbResultPpage;

	$params = [
		'id_vue'            => $vuesManager->getVueByCode('srgv')->getId(),
		'get_emb'           => true,
		'has_encours'       => true,
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
			echo $fam->getId();?>" data-id-emb-encours="<?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : 0; ?>">

                <div class="card-header">
					<?php echo $fam->getCode(); ?>
                </div>

                <div class="card-body">

                    <h5 class="card-title mb-0"><?php echo $fam->getNom(); ?></h5>

                    <span class="badge badge-dark text-16 d-block margin-top-15"><?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getNumlot_frs() : '?'; ?>
					<span class="text-12 margin-top-5 texte-fin d-block"><?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getNom_frs() : ''; ?></span></span>

                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-danger padding-20-10 border-light form-control btn-emb-defectueux" data-id-emb="<?php
							echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : '';?>"><i class="fa fa-exclamation-triangle fa-lg"></i></button>
                        </div>

                        <div class="col">
                            <button type="button" class="btn btn-info padding-20 border-light form-control btn-emb-change" data-id-old-emb="<?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : ''; ?>"><i class="fa fa-retweet fa-lg"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

	<?php } // FIN boucle sur les emballages

	// Pagination (aJax)
	if (isset($pagination)) {

		// Si on a moins de 3 colis de libres à droite, on va a la ligne
		$nbCasse = [4,5,10,11,16,17];

		if (in_array($nbPdtOnThePage,$nbCasse)) { ?>
            <div class="clearfix"></div>
		<?php }

		$pagination->setNature_resultats('produit');
		echo ($pagination->getPaginationBlocs());

	} // FIN test pagination

} // FIN mode

/* ---------------------------------------------------------
MODE - Modifie le nb de colis / poids d'un produit
----------------------------------------------------------*/
function modeUpdPdtLotPoids() {

	global $cnx, $utilisateur, $logsManager;

	$id_lot_pdt_froid   = isset($_REQUEST['id_lot_pdt_froid'])  ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
	$nb_colis           = isset($_REQUEST['nb_colis'])          ? intval($_REQUEST['nb_colis'])         : 0;
	$nb_colis_add       = isset($_REQUEST['nb_colis_add'])      ? intval($_REQUEST['nb_colis_add'])     : 0;
	$poids              = isset($_REQUEST['poids'])             ? floatval($_REQUEST['poids'])          : 0.0;
	$poids_add          = isset($_REQUEST['poids_add'])         ? floatval($_REQUEST['poids_add'])      : 0.0;
	$quantieme          = isset($_REQUEST['quantieme'])         ? trim($_REQUEST['quantieme'])          : '';
	$id_palette         = isset($_REQUEST['id_palette'])        ? intval($_REQUEST['id_palette'])       : 0;
	$id_compo           = isset($_REQUEST['id_compo'])          ? intval($_REQUEST['id_compo'])         : 0;
	$ajout              = isset($_REQUEST['ajout'])             ? intval($_REQUEST['ajout'])            : -1;



	$froidManager = new FroidManager($cnx);
	$froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
	if (!$froidProduit instanceof FroidProduit) { exit; }

	// Si l'id palette est différent de l'id palette correspondant à l'id lot pdt froid, c'est qu'on change de palette, il faut donc prendre le poids_add et le nb_colis_add
	if ($froidProduit->getId_palette() != $id_palette) {
		$poids = $poids_add;
		$nb_colis = $nb_colis_add;
    }

	// Si nb_colis à 0 alors on supprime le produit de l'op de froid
	if ($nb_colis == 0) {

		if (!$froidManager->supprPdtfroid($froidProduit)) { exit('-1'); }

		// On le retire de la palette à laquelle il était associé
		$palettesManager = new PalettesManager($cnx);
		$palettesManager->supprComposition($froidProduit);

		// Log
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("[SRGV] Suppression du produit ID_LOT_PDT_FROID " . $id_lot_pdt_froid . " du traitement OP Froid " . $froidProduit->getId_froid()) ;
		$logsManager->saveLog($log);

		exit;

	} // FIN test supprime pdt froid

	// SI le quantième est différent, c'est que l'on crée un nouveau produit
	if (($quantieme != 'A' && $quantieme != $froidProduit->getQuantieme())
		|| $id_palette != $froidProduit->getId_palette()) {

		$params = [
			'id_pdt'        => $froidProduit->getId_pdt(),
			'id_lot'        => $froidProduit->getId_lot(),
			'id_froid'      => $froidProduit->getId_froid(),
			'id_palette'    => $id_palette,
			'nb_colis'      => $nb_colis,
			'poids'         => $poids_add, // Ici le poids est celui rajouté
			'nb_colis_add'  => $nb_colis_add,
			'poids_add'     => $poids_add,
			'quantieme'     => $quantieme,
			'id_compo'      => $id_compo,
			'new_palette'   => $id_palette != $froidProduit->getId_palette()
		];


		modeAddPdtFroid($params);
		exit;
	}

	// On enregistre le poids et le nb colis dans la table froid
	if (!$froidManager->savePoidsColisLotProduit($froidProduit, $nb_colis, $poids, $id_palette)) { exit('-1'); }

	$froidProduit->setDate_maj(date('Y-m-d H:i:s'));
	$froidProduit->setUser_maj($utilisateur->getId());
	$id_pdtfroid = $froidManager->saveFroidProduit($froidProduit);

	// On enregistre l'id_lot_pdt_froid dans la table des compositions de palettes
	$id_lot_pdt_froid = $froidProduit->getId_lot_pdt_froid() > 0 ? $froidProduit->getId_lot_pdt_froid() : $id_pdtfroid;
	if ($id_lot_pdt_froid > 0) {
		$palettesManager = new PalettesManager($cnx);
		$compo = $palettesManager->getComposition($id_compo);
		if ($compo instanceof PaletteComposition) {
			$compo->setId_lot_pdt_froid($id_lot_pdt_froid);
			$compo->setSupprime(0);                     // On retire le flag "supprimé" qui empèche de faire n'importe quoi si on s'amuse a cliquer sur plein de palettes dans l'édition
			$palettesManager->savePaletteComposition($compo);
			$palettesManager->purgeComposSupprimeesByFroidProduit($id_lot_pdt_froid);
		}
	}

	// Si le poids add = poids alors c'est qu'on est en mode "total", il faut donc supprimer toutes les anciennes compos car la nouvelle vas remplacer les précédentes
	//if ($poids_add == $poids && $froidProduit->getId_palette() == $id_palette) {
	if ($ajout != 1 && $froidProduit->getId_palette() == $id_palette) {

		// On supprime toutes les compos pour cet id_lot_pdt_froid excepté la dernière créée
		$query_id_last = 'SELECT `id` FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = '.$id_lot_pdt_froid.' ORDER BY `id` DESC LIMIT 0,1';
		$query = $cnx->prepare($query_id_last);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		$id_last = $donnees && isset($donnees['id']) ? intval($donnees['id']) : 0;
		if ($id_last > 0) {
			$query_del = 'DELETE FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = '.$id_lot_pdt_froid.' AND `id` < ' . $id_last;
			$query2 = $cnx->prepare($query_del);
			$query2->execute();

			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte("[SRGV] suppression des compos en trop (modif poids en mode total) pour l'ID_LOT_PDT_FROID " . $id_lot_pdt_froid) ;
			$logsManager->saveLog($log);

		}
	}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Modification du produit depuis récap traitement, ID_LOT_PDT_FROID " . $id_lot_pdt_froid) ;
	$logsManager->saveLog($log);

	exit;

} // FIN mode

/* ---------------------------------------------------------
MODE - Etiquetage produit lot
----------------------------------------------------------*/
function modeEtiquetageProduitLot() {

	global $cnx, $logsManager;

	$id_lot_pdt_froid = isset($_REQUEST['id_lot_pdt_froid']) ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
	$etiquetage = isset($_REQUEST['etiquetage']) ? intval($_REQUEST['etiquetage']) : 0;
	if (($etiquetage != 0 && $etiquetage != 1) || $id_lot_pdt_froid == 0)  { exit('-1'); }

	$froidManager = new FroidManager($cnx);
	if (!$froidManager->saveEtiquetagePdtFroid($id_lot_pdt_froid, $etiquetage)) { exit('-3'); }

	$logVerbe = $etiquetage == 1 ? "Etiquetage" : "Retrait de l'étiquetage";

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] ".$logVerbe." produit ID_LOT_PDT_FROID " . $id_lot_pdt_froid) ;
	$logsManager->saveLog($log);

	exit;

} // FIN mode

/* ---------------------------------------------------------
MODE - Enregistre la température de début de surgélation
----------------------------------------------------------*/
function modeSaveTempDebut() {

	global $cnx, $utilisateur, $logsManager;

	$id_froid   = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	$temp_debut = isset($_REQUEST['temp_debut']) ? floatval($_REQUEST['temp_debut']) : 0.0;

	if ($id_froid == 0) { echo '-1'; exit; }

	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { echo '-2'; exit; }

	$froid->setTemp_debut($temp_debut);
	$froid->setId_user_maj($utilisateur->getId());
	if (!$froidManager->saveFroid($froid)) { echo '-3'; exit; }

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Saisie de la température en début de traitement à " . $temp_debut . "°C pour l'OP Froid ID " . $id_froid) ;
	$logsManager->saveLog($log);

	echo 1;
	exit;

} // FIN mode

/* ---------------------------------------------------------
MODE - Enregistre la température de fin de surgélation
----------------------------------------------------------*/
function modeSaveTempFin() {

	global $cnx, $utilisateur, $logsManager;

	$id_froid   = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	$temp_fin = isset($_REQUEST['temp_fin']) ? floatval($_REQUEST['temp_fin']) : 0.0;

	if ($id_froid == 0) { echo '-1'; exit; }

	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid, true);
	if (!$froid instanceof Froid) { echo '-2'; exit; }

	$froid->setTemp_fin($temp_fin);
	$froid->setId_user_maj($utilisateur->getId());

	if (!$froidManager->saveFroid($froid)) { echo '-3'; exit; }

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Saisie de la température de fin de traitement à " . $temp_fin . "°C pour l'OP Froid ID " . $id_froid) ;
	$logsManager->saveLog($log);

	// Gestion des alertes
	$configManager      = new ConfigManager($cnx);
	$config_tmp_srg_min = $configManager->getConfig('tmp_srg_min');
	$config_tmp_srg_max = $configManager->getConfig('tmp_srg_max');
	$config_alerte2_actif = $configManager->getConfig('alerte2_actif');

	// Si l'alerte n'est pas active ou s'il manque une température de référence, on ne va pas plus loin
	if (!$config_tmp_srg_min instanceof Config || !$config_tmp_srg_max instanceof Config  || !$config_alerte2_actif instanceof Config ) { exit('1'); }
	if (intval($config_alerte2_actif->getValeur()) == 0) { exit('1'); }

	// Test alerte
	if ($temp_fin < floatval($config_tmp_srg_min->getValeur()) || $temp_fin > floatval($config_tmp_srg_max->getValeur())) {

		$alerteManager = new AlerteManager($cnx);
		$id_froid_type = intval($froidManager->getFroidTypeByCode('srgv'));

		// Comme la traçabilité se fait au niveau du lot on crée autant d'alertes que de lots dans la surgélation
		foreach ($froid->getLots() as $lotsrg) {
			$alerte = new Alerte([]);
			$alerte->setId_lot($lotsrg->getId());
			$alerte->setType(2);
			$alerte->setId_froid($id_froid);
			$alerte->setId_froid_type($id_froid_type);
			$alerte->setNumlot($lotsrg->getNumlot());
			$alerte->setDate(date('Y-m-d H:i:s'));
			$alerte->setId_user($utilisateur->getId());
			$alerte->setNom_user($utilisateur->getNomComplet());
			$alerte->setValeur($temp_fin);
			if ($alerteManager->saveAlerte($alerte)) {
				$alerteManager->envoiMailAlerte($alerte);
            }

		}
	} // FIN test alerte

    $nbPdtsLoma = $froidManager->getNbProduitsLoma($froid);
	$codeRetour = $nbPdtsLoma > 0 && $froid->getTest_avant_fe() < 0 ? '2' : '1'; // SI pas fait, getTest_avant_fe renvoie -1 (valeur défaut BDD)

    echo $codeRetour;
	exit;

} // FIN mode


/* ---------------------------------------------------------
MODE - Enregistre la début de surgélation
----------------------------------------------------------*/
function modeSaveDebutSrg() {

	global $cnx, $utilisateur, $logsManager;

	$id_froid   = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	$nuit       = isset($_REQUEST['nuit'])     ? intval($_REQUEST['nuit'])     : 0;


	if ($id_froid == 0) { echo '-1'; exit; }

	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { echo '-2'; exit; }

	$froid->setDate_entree(date('Y-m-d H:i:s'));
	$froid->setId_user_debut($utilisateur->getId());
	$froid->setId_user_maj($utilisateur->getId());
	$froid->setNuit($nuit);
	if (!$froidManager->saveFroid($froid)) { echo '-3'; exit; }

	// On évite les produits non étiquetés (DEBUG)
	$froidManager->etiquetteTout($froid);

	$texteNuit = $nuit ? "en cycle de nuit" : "";

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Début du traitement ".$texteNuit." de l'OP Froid ID " . $id_froid) ;
	$logsManager->saveLog($log);

	echo 1;

	exit;

} // FIN mode

/* ---------------------------------------------------------
MODE - Contenu de la modale de confirmation de fin de SRG
----------------------------------------------------------*/
function modeModalConfirmFinFroid() {

	global $cnx;

	echo '<i class="fa fa-sign-out-alt mr-1"></i>Sortie du surgélateur';
	echo '^'; // Séparateur titre/body

	$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	if ($id_froid == 0) { erreurLot(); exit; }

	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { erreurLot(); exit; }
	?>

    <div class="text-28 mb-3">
        Confirmer la fin de surgélation ?
    </div>

	<?php
	$dateEntree     = new DateTime($froid->getDate_entree());
	$dateNow        = new DateTime();
	$interval       = $dateEntree->diff($dateNow);

	$jours  = (intval($interval->format('%d')));
	$heures = (intval($interval->format('%h')));
	$min    = (intval($interval->format('%i')));
	if ($jours > 0) { $heures = $heures + 24; }
	?>

    <div class="mt-2">
        <span class="gris-5"><i class="fa fa-stopwatch mr-1"></i> Temps écoulé :</span>
        <div><span class="badge badge-info text-30"><?php echo  $heures . ' h ' . sprintf('%02d', $min)  . ' min.'; ?></span></div>
    </div>

	<?php
	exit;

} // FIN mode

/* ---------------------------------------------------------
MODE - Conformité de la surgélation (enregistrement)
----------------------------------------------------------*/
function modeConformiteSrg() {

	global $cnx, $utilisateur, $logsManager;

	$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	if ($id_froid == 0) { exit('-1'); }

	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { exit('-2'); }

	$conformite = isset($_REQUEST['conformite']) ? intval($_REQUEST['conformite']) : -1;
	if ($conformite < 0 || $conformite > 1) { exit('-3'); }

	$logConformite = $conformite == 1 ? "Conformité" : "Non conformité";

	$froid->setConformite($conformite);
	$froid->setId_user_maj($utilisateur->getId());
	$froidManager = new FroidManager($cnx);
	if (!$froidManager->saveFroid($froid)) { exit('-4'); }

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] ".$logConformite." du traitement OP froid produit " . $id_froid) ;
	$logsManager->saveLog($log);

	exit;

} // FIN mode

/* ---------------------------------------------------------
MODE -  Clôture du traitement
----------------------------------------------------------*/
function modeClotureSrg() {

	global $cnx, $utilisateur, $logsManager;

	// Récupération des variables
	$id_froid   = isset($_REQUEST['id_froid'])  ? intval($_REQUEST['id_froid']) : 0;
	if ($id_froid == 0) { exit('-1'); }

	// Sécurisation des données
	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid, true);
	if (!$froid instanceof Froid) { exit('-2'); }
	if (!$utilisateur instanceof User) { exit('-3'); }

	$froid->setId_visa_controleur($utilisateur->getId());
	$froid->setDate_controle(date('Y-m-d H:i:s'));
	$froid->setStatut(2);

	if (!$froidManager->saveFroid($froid)) { exit('-4'); }

	$vuesManager = new VueManager($cnx);
	$vueRcp = $vuesManager->getVueByCode('srgv');
	if (!$vueRcp instanceof Vue) { exit; }

	// Validation admin
	$validation = new Validation([]);
	$validation->setType(2); // 2 = Froid
	$validation->setId_vue($vueRcp->getId());
	$validation->setId_liaison($id_froid);
	$validationManager = new ValidationManager($cnx);
	if ($validationManager->saveValidation($validation)) {
		$validationManager->addValidationLot($validation, $froid->getLots());
	}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Clôture du traitement " . $id_froid) ;
	$logsManager->saveLog($log);

	exit;

} // FIN mode


/* ---------------------------------------------------------
MODE -  Enregistre un contrôle Loma sur un produit
----------------------------------------------------------*/
function modeSaveLoma() {

	global $cnx, $utilisateur, $logsManager;

	// Récupération des variables
	$id_lot_pdt_froid   = isset($_REQUEST['id_lot_pdt_froid'])  ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
	if ($id_lot_pdt_froid == 0) { exit('CKAEIUYJ'); }

	$resultest_pdt  = isset($_REQUEST['resultest_pdt'])     ? intval($_REQUEST['resultest_pdt'])    : -1;   // 0 = OK

	$controleOk = $resultest_pdt == 1;

	$commentaires = isset($_REQUEST['commentaires']) ? trim(strip_tags(htmlspecialchars($_REQUEST['commentaires']))) : '';

	// Ici on est ok... on procède
	$lomaManager = new LomaManager($cnx);

	// On tente de récupérer si un loma existe déjà pour ce produit dans cette op pour MAJ
	$loma = $lomaManager->getLomaByIdLotPdtFroid($id_lot_pdt_froid);
	if (!$loma instanceof Loma) {
		$loma = new Loma([]);
		$loma->setId_lot_pdt_froid($id_lot_pdt_froid);
    }
	$loma->setTest_pdt($resultest_pdt);
	$loma->setCommentaire($commentaires);
	$loma->setDate_test(date('Y-m-d H:i:s'));
	$loma->setId_user_visa($utilisateur->getId());

	$ressave = $lomaManager->saveLoma($loma);
	if (is_numeric($ressave)) { $loma->setId($ressave); }

	// SI le loma est bien enregistré en base, on enregistre la demande de validation admin + gestion alerte
	if ($ressave) {

		// Log
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("[SRGV] Contrôle LOMA sur produit ID_LOT_PDT_FROID " . $id_lot_pdt_froid) ;
		$logsManager->saveLog($log);

		// Validation admin
		$vueManager     = new VueManager($cnx);
		$froidManager   = new FroidManager($cnx);

		// On récupère l'objet de la Vue
		$vue = $vueManager->getVueByCode('srgv');
		if (!$vue instanceof Vue) { exit('539O96ZR'); }
		if (intval($loma->getId()) == 0) { exit('KY8210CL'); }

		// On récupère le lot depuis le lotpdtfroid
		$lotpdtFroid = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
		if (!$lotpdtFroid instanceof FroidProduit) { exit('D5ZTPV2L'); }
		$id_lot = $lotpdtFroid->getId_lot();


		$validation     = new Validation([]);
		$validation->setType(3); // 3 = Loma
		$validation->setId_vue($vue->getId());
		$validation->setId_liaison($loma->getId());
		$validationManager = new ValidationManager($cnx);
		if ($validationManager->saveValidation($validation)) {
			$validationManager->addValidationLot($validation, $id_lot);
		}

		// On vérifie que l'alerte est activée
		$configManager    = new ConfigManager($cnx);
		$activationAlerte = $configManager->getConfig('alerte4_actif');
		if ($activationAlerte instanceof Config && intval($activationAlerte->getValeur()) == 1) {

			// Si le test est pas glop...
			if (!$controleOk) {

				$texte = $resultest_pdt == 1 ? 'Contrôle LOMA positif sur produit' : 'Test non détecté';

				$alerteManager = new AlerteManager($cnx);
				$froidManager  = new FroidManager($cnx);
				$id_froid_type = intval($froidManager->getFroidTypeByCode('srgv'));
				$alerte = new Alerte([]);
				$alerte->setId_lot($id_lot);
				$alerte->setType(4);            // Type 3 = Loma
				$alerte->setId_froid($lotpdtFroid->getId_froid());
				$alerte->setId_froid_type($id_froid_type);
				$alerte->setNumlot($lotpdtFroid->getNumlot());
				$alerte->setDate(date('Y-m-d H:i:s'));
				$alerte->setId_user($utilisateur->getId());
				$alerte->setNom_user($utilisateur->getNomComplet());
				$alerte->setValeur($texte);
				if ($alerteManager->saveAlerte($alerte)) {
					$alerteManager->envoiMailAlerte($alerte);
                }

			} // FIN test en alerte
		} // FIN test alerte loma active

	} // FIN test LOMA bien enregistré

	echo $ressave ? '' : 'WFJZHB5I';

	exit;

} // FIN mode


/* ---------------------------------------------------------
MODE -  Charge la liste des produits du traitement
----------------------------------------------------------*/
function modeShowListeProduitsFroid() {

	global $cnx, $utilisateur;

	$colonne    = isset($_REQUEST['colonne'])   ? trim(strtolower($_REQUEST['colonne']))    : 'pdt';
	$sens       = isset($_REQUEST['sens'])      ? trim(strtolower($_REQUEST['sens']))       : 'asc';

	// Récupération des variables
	$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	if ($id_froid == 0) { erreurLot(); exit; }
	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { erreurLot(); exit; }

	// On récupèère les produits de la congélation
	$pdtsFroid = $froidManager->getFroidProduits($froid, $colonne, $sens);

	// Aucun produit
	if (empty($pdtsFroid)) {

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = '<i class="fa fa-ban text-danger"></i> Aucun objet trouvé [Froid #'.$id_froid.']';
		}


		?>

        <div class="alert alert-secondary mt-3 text-center">
              <span class="fa-stack fa-2x mt-5">
                   <i class="fas fa-box fa-stack-1x"></i>
                   <i class="fas fa-ban fa-stack-2x" style="color:Tomato"></i>
               </span>
            <h3 class="gris-7 mt-3 mb-5">Aucun produit…</h3>
        </div>

		<?php exit;
	} // FIN test aucun produit
	?>

    <table class="table admin table-front-tri mt-2 table-v-middle" data-id-froid="<?php echo $id_froid; ?>">
        <thead>
        <tr>
            <th>Code</th>
            <th class="position-relative padding-left-50 tri-produits" data-sens="<?php echo $sens; ?>" data-tri-actif="<?php echo $colonne == 'pdt' ? 1 : 0; ?>"><i class="fa fa-sort-alpha-<?php echo $sens == 'asc' ? 'down' : 'up'; ?> text-30 <?php echo $colonne == 'pdt' ? 'text-info-light' : 'gris-9'; ?> position-absolute abs-left-15 abs-top-10 "></i> Produit</th>
            <th class="position-relative padding-left-50 tri-lots" data-sens="<?php echo $sens; ?>" data-tri-actif="<?php echo $colonne == 'lot' ? 1 : 0; ?>"><i class="fa fa-sort-numeric-<?php echo $sens == 'asc' ? 'down' : 'up'; ?> text-30 <?php echo $colonne == 'lot' ? 'text-info-light' : 'gris-9'; ?> position-absolute abs-left-15 abs-top-10"></i> Lot</th>
            <th class="text-center pr-4">Palette en cours</th>
            <th class="text-center pr-4">Nb de blocs</th>
            <th class="text-right pr-5">Poids total</th>
            <th class="text-center pr-5">Etiquetage</th>
            <!--<th class="text-center">En attente</th>-->
        </tr>
        </thead>

        <tbody>
		<?php

		if ($utilisateur->isDev()) {
			$s = count($pdtsFroid) > 1 ? 's' : '';
			$_SESSION['infodevvue'] = count($pdtsFroid) . ' objet'.$s.' trouvé'.$s.' [Froid #'.$id_froid.']';
		}

		// Boucle sur les produits de la congélation
		foreach ($pdtsFroid as $pdtFroid) {

			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= '<br>FroidProduit [' . $pdtFroid->getId_lot_pdt_froid().']';
				$_SESSION['infodevvue'].= ' - Froid ['. $id_froid.'] Pdt ['.$pdtFroid->getProduit()->getId().'] Lot [' . $pdtFroid->getId_lot().']';
				$_SESSION['infodevvue'].= ' Quantième ['.$pdtFroid->getQuantieme().']';
				$_SESSION['infodevvue'].= ' Palette ['.$pdtFroid->getId_palette().']';
				$_SESSION['infodevvue'].= ' Colis ['.$pdtFroid->getNb_colis().']';
				$_SESSION['infodevvue'].= ' Poids ['.$pdtFroid->getPoids().']';
			}

		    ?>

            <tr>
                <td><code class="gris-5 text-12"><?php echo $pdtFroid->getProduit()->getCode(). '/' . $pdtFroid->getId_lot_pdt_froid();?></code></td>
                <td class="text-20 nomProduitLigne"><?php echo $pdtFroid->getProduit()->getNom();?></td>
                <td><span class="badge badge-secondary text-18" style="background-color: <?php
					// On calcule un code hexa pour la couleur basé sur l'ID froid, et on le rend plus foncé de 20% pour être sûr qu'il soit un minimum visible...
					$hexaLot = Outils::genereHexaCouleur($pdtFroid->getNumlot()); echo $hexaLot ;
					?>;color:<?php echo Outils::isCouleurHexaClaire($hexaLot) ? '#000' : '#fff'; ?>"><?php echo $pdtFroid->getNumlot() . $pdtFroid->getQuantieme();?></span>
                </td>
                <td class="text-center text-20"><?php echo $pdtFroid->getNumero_palette() > 0 ? $pdtFroid->getNumero_palette() : '&mdash;';?></td>
                <td class="text-center text-20"><?php echo $pdtFroid->getNb_colis();?></td>
                <td class="text-right text-16"><?php
					echo number_format($pdtFroid->getPoids(),3,'.','');?> kg

                    <!-- bouton Changer le poids -->
                    <button type="button" class="btn ml-1 btnChangePoidsPdt btn-info" data-id-lot-pdt-froid="<?php echo $pdtFroid->getId_lot_pdt_froid(); ?>"><i class="fa fa-edit"></i>
                    </button>
                </td>
                <td class="text-center">

                    <!-- Switch Etiquetage -->
                    <input type="checkbox" class="togglemaster check-etiquetage<?php

					// Si la congélation a déjà commencée, on désactive le selecteur (CSS)
					echo $froid->isEnCours() ? ' disabled' : ''; ?>"

                           data-toggle              = "toggle"
                           data-on                  = "Oui"
                           data-off                 = "Non"
                           data-onstyle             = "success"
                           data-offstyle            = "secondary"
                           data-id-lot-pdt-froid    = "<?php echo $pdtFroid->getId_lot_pdt_froid(); ?>"

						<?php
						// Statut coché
						echo $pdtFroid->getEtiquetage() == 1 ? 'checked' : ''; ?>

						<?php
						// Si la congélation a déjà commencée, on désactive le selecteur (ACTION JS)
						echo $froid->isEnCours() ? 'disabled' : ''; ?>/>
                </td>
                <!--<td class="text-center">
                    <button type="button" class="btn btn-warning btnAttenteProduit" data-toggle="modal" data-target="#modalConfirmAttenteProduit" data-id-lot-pdt-froid="<?php // echo $pdtFroid->getId_lot_pdt_froid(); ?>">
                        <i class="fas fa-lg fa-history"></i>
                    </button>
                </td>-->
            </tr>

			<?php

		} // FIN boucle produits
		?>

        </tbody>
    </table>
	<?php

} // FIN mode


/* -------------------------------------------------
MODE - Enregistre en commentaire de température HS
--------------------------------------------------*/
function modeSaveCommentaireTempHs() {

	global $cnx, $utilisateur, $logsManager;

	// Récupération des données
	$id_lot       = 0; // VUE froid, pas d'ID lot
	$id_froid     = isset($_REQUEST['id_froid'])      ? intval($_REQUEST['id_froid'])             : 0;
	$commentaire  = isset($_REQUEST['commentaires'])  ? trim(nl2br($_REQUEST['commentaires']))    : '';

	// Vérification des données
	if ($id_froid + $id_lot == 0 || strlen($commentaire) == 0 || !isset($utilisateur) ||  !$utilisateur instanceof User) { exit('-1'); }

	$com = new Commentaire([]);
	$dateCom = date('Y-m-d H:i:s');
	$com->setDate($dateCom);
	$com->setId_user($utilisateur->getId());
	$com->setId_lot($id_lot);
	$com->setId_froid($id_froid);
	$com->setCommentaire($commentaire);
	$comManager = new CommentairesManager($cnx);

	$comManager->saveCommentaire($com);

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Enregistrement commentaire température HS sur lot #".$id_lot." et froid#".$id_froid) ;
	$logsManager->saveLog($log);


	exit;

} // FIN mode

/* -------------------------------------------------
MODE - Enregistre le controle loma (tests avant)
--------------------------------------------------*/
function modeSaveLomaAvant() {

    global $cnx, $logsManager;

    $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
    if ($id_froid == 0) { exit('ERR_IDFROID0'); }

    $froidManager = new FroidManager($cnx);
    $froid = $froidManager->getFroid($id_froid);
    if (!$froid instanceof Froid) { exit('ERR_OBJFROID_'.$id_froid);}

	$resultest_nfe  = isset($_REQUEST['resultest_nfe'])     ? intval($_REQUEST['resultest_nfe'])        : -1;  // 1 = OK
	$resultest_fe   = isset($_REQUEST['resultest_fe'])      ? intval($_REQUEST['resultest_fe'])         : -1;  // 1 = OK
	$resultest_inox = isset($_REQUEST['resultest_inox'])    ? intval($_REQUEST['resultest_inox'])       : -1;  // 1 = OK

	$froid->setTest_avant_fe($resultest_fe);
	$froid->setTest_avant_nfe($resultest_nfe);
	$froid->setTest_avant_inox($resultest_inox);
	if (!$froidManager->saveFroid($froid)) { exit('ERR_SAVEFROID_TESTSAPRES_'.$id_froid);}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Enregistrement LOMA AVANT sur froid#".$id_froid) ;
	$logsManager->saveLog($log);

    echo '1';
	exit;

} // FIN mode

// Enregistre le loma après
function modeSaveLomaApres() {

	global $cnx, $logsManager;

	$froidManager = new FroidManager($cnx);

	// Récupération des variables
	$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;

	$froid = $froidManager->getFroid($id_froid);

	if (!$froid instanceof Froid) { exit('ERR_OBJFROID_'.$id_froid);}

	$resultest_nfe  = isset($_REQUEST['resultest_nfe'])     ? intval($_REQUEST['resultest_nfe'])        : -1;  // 1 = OK
	$resultest_fe   = isset($_REQUEST['resultest_fe'])      ? intval($_REQUEST['resultest_fe'])         : -1;  // 1 = OK
	$resultest_inox = isset($_REQUEST['resultest_inox'])    ? intval($_REQUEST['resultest_inox'])       : -1;  // 1 = OK

	$froid->setTest_apres_fe($resultest_fe);
	$froid->setTest_apres_nfe($resultest_nfe);
	$froid->setTest_apres_inox($resultest_inox);
	if (!$froidManager->saveFroid($froid)) { exit('ERR_SAVEFROID_TESTSAPRES_'.$id_froid);}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[SRGV] Enregistrement LOMA APRES sur froid#".$id_froid) ;
	$logsManager->saveLog($log);

	echo '1';
	exit;


} // FIN mode