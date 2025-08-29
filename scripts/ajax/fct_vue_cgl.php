<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax VUE CONGELATION
------------------------------------------------------*/
ini_set('display_errors',1);

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

	global $cnx, $utilisateur;

	$etape  = isset($_REQUEST['etape'])     ? intval($_REQUEST['etape'])            : 0;
	$na     = '<span class="badge badge-warning badge-pill text-14">Non renseigné</span>';


	/** ----------------------------------------
	 * Etape        : 0
	 * Description  : Point d'entrée
	 *  ----------------------------------- */

	if ($etape == 0) { ?>

        <div class="row mt-5">
            <div class="col text-center">
                <i class="fa fa-snowflake gris-9 fa-6x"></i>
            </div>
        </div>

        <div class="row mt-5">

            <div class="col-5 offset-1">
                <button type="button" class="btn btn-info btn-lg form-control btnNouvelleCgl">
                    <i class="fa fa-plus text-50 mb-3 mt-3"></i>
                    <h3 class="mb-3">Nouvelle congélation&hellip;</h3>
                </button>
            </div>

            <div class="col-5">
                <button type="button" class="btn btn-secondary btn-lg form-control btnCglsEnCours">
                    <i class="fa fa-stopwatch text-50 mb-3 mt-3"></i>
                    <h3 class="mb-3">Congélations en cours&hellip;</h3>
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

	    // Si on a des produits en attente, on affiche
        $froidsManager      = new FroidManager($cnx);
		$vue_code           = 'cgl,srgh';
		$pdts_attente       = $froidsManager->getProduitsFroidsEnAttente($vue_code);

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Produits en attente : ';
		}


        // Si on en a (des produits en attente)
        if (!empty($pdts_attente)) { ?>

            <div class="row">
                <div class="col mt-3">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Produits en attente :</h4>
                </div>
            </div>
            <?php
			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= count($pdts_attente);
			}

            // Boucle sur les produits en attente
            foreach ($pdts_attente as $pdt_attente) {

				if ($utilisateur->isDev()) {
					$_SESSION['infodevvue'].= '<br>- FroidProduit ['.$pdt_attente->getId_lot_pdt_froid().']';
					$_SESSION['infodevvue'].= ' | Palette ['.$pdt_attente->getId_palette().']';
					$_SESSION['infodevvue'].= ' | Compo ['.$pdt_attente->getId_compo().']';
				}

                ?>
                
                <div class="card bg-secondary text-white mb-3 carte-pdt-attente d-inline-block mr-3" style="max-width: 20rem" data-id-lot-pdt-froid="<?php echo $pdt_attente->getId_lot_pdt_froid(); ?>">

                            <div class="card-header text-22"><?php echo $pdt_attente->getProduit()->getNom(); ?></div>
                            <div class="card-body">
                                <table>
                                    <tr>
                                        <td class="vmiddle">Lot</td>
                                        <th class="text-18"><?php echo $pdt_attente->getNumlot().$pdt_attente->getQuantieme();?></th>
                                    </tr>
                                    <tr>
                                        <td class="vmiddle">Mis en attente</td>
                                        <th><?php echo Outils::getDate_verbose($pdt_attente->getDate_maj(), false, ' à ', false); ?></th>
                                    </tr>
                                    <tr>
                                        <td class="vmiddle">Nb de colis</td>
                                        <th><?php echo $pdt_attente->getNb_colis(); ?></th>
                                    </tr>
                                    <tr>
                                        <td class="vmiddle">Poids total (Kgs)</td>
                                        <th><?php echo number_format($pdt_attente->getPoids(),3, '.', ' '); ?></th>
                                    </tr>
                                </table>
                            </div>
                </div>


            <?php } // FIN boucle sur les produits en attente
            ?>

        <div class="clearfix"></div>
        <?php

        } // FIN test produits en attente
        else if ($utilisateur->isDev()) {
            $_SESSION['infodevvue'].= ' Aucun';
		}

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
			$_SESSION['infodevvue'].= '<br>Lots sur la vue ATL : ';
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
        } else if ($utilisateur->isDev()) {
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
                            <th><?php echo $lotvue->getNom_espece($na);?></th>
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

        // Liste des especes de produits actives correspondant à la composition du lot
        $produitEspeceManager = new ProduitEspecesManager($cnx);

		$espece = $produitEspeceManager->getProduitEspece($lot->getId_espece());
		modeShowProduitsFamille($espece->getId(), $id_lot, false);

		exit;

	} // FIN ETAPE


	/** ----------------------------------------
	 * Etape        : 3
	 * Description  : Nb de colis / Poids pdt
     * Paramètres   : Produit / Lot / Froid (facultatif)
	 *  ----------------------------------- */

	if ($etape == 3) {

	    // Récupération des variables
	    $couplePdtLot = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
		if ($couplePdtLot == '') { erreurLot(); exit; }

        // Array des données passées en paramètre (add)
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



                        // Affichage du nom du produit
						echo $pdt->getNom();

						?>

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
                        <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i><?php echo $pdt->getVrac() == 1 ? 'Poids' : 'Nombre de colis'; ?> en congélation :</h4>
                    </div>
                    <div class="col text-right">
						<?php

						// On récupère le nombre de colis du produit pour cette op de froid s'il a déjà été renseigné (modification depuis l'étape 10)
						$nbColisOld     = '';
						$Quantite     = '';

						// On contrôle l'ID froid et récupère l'objet FroidProduit
						if ($id_froid > 0) {

						    if (isset($froidProduit) && $froidProduit instanceof FroidProduit) {
								$froidPdt = $froidProduit;

                            } else {
								$froidPdt = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
                            }

							// Si on a déjà un lot/produit avec des données, on récupère l'ancienne valeur du nombre de colis
							if ($froidPdt instanceof FroidProduit) {
								$nbColisOld = $froidPdt->getNb_colis();
								$Quantite = $froidPdt->getQuantite();
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
                    <input type="hidden" id="quantitePaletteHisto" value="<?php echo  isset($froidPdt) && $froidPdt instanceof FroidProduit ? $froidPdt->getQuantite() : 0; ?>"/>
                    <input type="hidden" id="vrac" value="<?php echo $pdt->getVrac(); ?>>"/>
                    <input type="hidden" id="skipCreateCompoSave" value="0"/>
                    <input type="hidden" id="forceSaveProduit" value=""/>

                    <!-- Nombre de colis -->
                    <div class="col-4 <?php echo $pdt->getVrac() == 1 ? 'd-none' : '';?>">
                        <div class="input-group">
                            <input type="text" class="form-control text-100 text-center inputNbColis" name="nb_colis" placeholder="0" value="<?php echo $nbColisOld; ?>">
                            <input type="hidden" name="nb_colis_old" value="<?php echo $nbColisOld; ?>">
                            <span class="input-group-append">
                                  <span class="input-group-text text-26"> colis</span>
                            </span>
                        </div>
                    </div>

					  <!-- Nombre de quantite -->
					  <div class="col-4 <?php echo $pdt->getVrac() == 1 ? 'd-none' : '';?>">
                        <div class="input-group">
                            <input type="text" class="form-control text-100 text-center inputQuantite" name="quantite" placeholder="0" value="<?php echo $Quantite; ?>">
                            <input type="hidden" name="quantite_old" value="<?php echo $Quantite; ?>">
                            <span class="input-group-append">
                                  <span class="input-group-text text-26"> pieces</span>
                            </span>
                        </div>
                    </div>

                    <!-- Poids -->
                    <div class="col-4">
                        <div class="input-group">
                             <span class="input-group-prepend">
                                  <span class="input-group-text text-38 gris-9"><i class="fa fa-weight"></i></span>
                            </span>
                            <input type="text" class="form-control text-100 text-center inputPoidsPdt" name="poids_pdt" placeholder="0" value="<?php
                            if (isset($froidPdt) && $froidPdt instanceof FroidProduit) { echo $froidPdt->getPoids() > 0 ? $froidPdt->getPoids() : ''; } ?>" data-poids-defaut="<?php echo $pdt->getPoids(); ?>">
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

                <!-- Quantieme : sélection celui qui est en ID au dessus est la valeur historique avant modif -->
                <input type="hidden" name="quantieme"  value="<?php

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
                                ?>secondary text-40 padding-20-40 form-control choix-quantieme"><?php echo $quantieme; ?></button>

                                </div><?php
                            } // FIN boucle sur les quantièmes

                            ?>
                        </div>
					<?php
					} // FIN aucun ou plusieurs quantième pour le lot
            ?>
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
                    'mixte'      => $pdt->isMixte(),
                    'id_client'  => $pdt->getId_client(),
                    'hors_frais' => true
				];
				$palettes = $palettesManager->getListePalettes($params);

				if (empty($palettes)) { ?>

                    <div class="col-2 aucunePaletteEnCours">
                        <div class="alert alert-secondary text-center padding-50 w-100 text-18 gris-9">
                            Aucune palette<br>en cours...
                        </div>
                    </div>

				<?php
					if ($utilisateur->isDev()) {
						$_SESSION['infodevvue'].= '<br>Aucune palette en cours trouvée pour ce produit/client ';
						$_SESSION['infodevvue'].= $pdt->isMixte() ? ' (mixte) ' : '';
					}

				}

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

						$logManager = new LogManager($cnx);
						$log = new Log([]);
						$log->setLog_type('info');
						$log->setLog_texte('Statut à 1 de la palettte #'.$palette->getId() . ' (contrôle auto sur capacité atteinte lors de l\'affichage)');
						$logManager->saveLog($log);

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
										<?php echo $restantPoids < 0 ? '+ '.number_format($restantPoids * -1,0,'.', ' ') : number_format($restantPoids,0,'.', ' '); ?> <span class="texte-fin text-14">kgs</span>
                                    </div>
                                    <div class="col-6 text-center text-18">
										<?php echo $restantColis < 0 ? '+ ' . $restantColis * -1 : $restantColis; ?> <span class="texte-fin text-14">colis</span>
                                    </div>

								<?php } else { ?>
                                    <div class="col-12 text-center text-16">Capacité restante :</div>
                                    <div class="col-6 text-center text-18">
										<?php echo number_format($restantPoids,0,'.', ' '); ?> <span class="texte-fin text-14">kgs</span>
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
                    <?php }

					?>

                </div>

            </div> <!-- FIN conteneur ROW palettes -->

        </div> <!-- FIN row contenu -->

		<?php
		exit;

	} // FIN ETAPE


	/** ----------------------------------------
	 * Etape        : 4
	 * Description  : Température début cgl
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

            <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Température en début de congélation :</h4>

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
            $cgl_consignes_debut = $configManager->getConfig('cgl_consignes_debut');
            if ($cgl_consignes_debut instanceof Config) {
                if (strlen(trim($cgl_consignes_debut->getValeur())) > 0) { ?>

                    <div class="row">
                        <div class="col mt-3">
                            <div class="alert alert-secondary">
                                <h5>Rappel des consignes</h5>
                                <div>
                                    <?php echo $cgl_consignes_debut->getValeur(); ?>
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
	 * Description  : Départ congélation
	 *  ----------------------------------- */

	if ($etape == 5) {

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Départ congélation';
		}

		?>

        <div class="row mt-5 align-content-center">
            <div class="col text-center">
                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Entrée en tunnel :</h4>
                <button type="button" class="btn btn-lg btn-info text-30 padding-50 btnDebutCgl text-center"><i class="fa fa-stopwatch mr-2 text-50"></i><br>Débuter la congélation</button>
            </div>
        </div>

    <?php

    } // FIN ETAPE


	/** ---------------------------------------------------
	 * Etape        : 6
	 * Description  : Sorti de Tunel + Température fin CGL
     * Paramètres   : Froid
	 *  ------------------------------------------------- */

	if ($etape == 6) {

	   // Récupération des variables
        $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
        if ($id_froid == 0) { erreurLot(); exit; }
		$froidManager = new FroidManager($cnx);
        $froid = $froidManager->getFroid($id_froid);
        if (!$froid instanceof Froid) { erreurLot(); exit; }

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Froid ['.$id_froid.']';
		}

		// Enregistrement de la fin de congélation
        if ($froid->getDate_sortie() == '' || $froid->getDate_sortie() == '0000-00-00 00:00:00' || $froid->getDate_sortie() == null) {
            $froid->setDate_sortie(date('Y-m-d H:i:s'));
            $froid->setId_user_maj($utilisateur->getId());
            $froid->setId_user_fin($utilisateur->getId());
            $froidManager->saveFroid($froid);

			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= '<br>date_sortie, id_user_maj et id_user_fin enregistrés';
			}

			$logManager = new LogManager($cnx);
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte('Enregistrement fin de congélation OP_FROID #'.$id_froid);
			$logManager->saveLog($log);

        } // FIN test date de sortie non enregistrée
	    ?>

        <!-- Saisie de la température de fin de congélation -->
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

                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Température en fin de congélation :</h4>

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
                $config_tmp_cgl_min = $configManager->getConfig('tmp_cgl_min');
                $config_tmp_cgl_max = $configManager->getConfig('tmp_cgl_max');
                if ($config_tmp_cgl_min instanceof Config && $config_tmp_cgl_max instanceof Config) { ?>

                <!-- Affichage des consignes (si les températures sont bien configurées) -->
                <div class="row">
                    <div class="col mt-3">
                        <div class="alert alert-secondary temp-controles" data-temp-controle-min="<?php echo intval($config_tmp_cgl_min->getValeur()); ?>" data-temp-controle-max="<?php echo intval($config_tmp_cgl_max->getValeur()); ?>">
                            <h5>Rappel des consignes</h5>
                            <span id="consignesTemp">Température cible en fin de congélation entre <?php echo $config_tmp_cgl_min->getValeur(); ?>°C et <?php echo $config_tmp_cgl_max->getValeur(); ?>°C</span>
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
	 * Description  : Bloquage suite alerte T° de fin hors norme
     * Paramètres   : Froid
	 *  ----------------------------------------------------- */

	if ($etape == 7) {

		// Récupération des variables
		$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
		if ($id_froid == 0) { erreurLot(); exit; }
		$froidManager = new FroidManager($cnx);
		$froid = $froidManager->getFroid($id_froid);
		if (!$froid instanceof Froid) { erreurLot(); exit; }
        ?>

        <!-- Bloc alerte traitement bloqué -->
		<div class="alert alert-danger mt-2 text-center padding-top-50 padding-bottom-50">
            <i class="fa fa-exclamation-triangle fa-5x mb-2"></i>
            <h3>Température en fin de congélation non valide !</h3>
            <p>Le process de production a été suspendu.<br>Veuillez contacter un administrateur&hellip;</p>
            <button type="button" class="btn btn-secondary btn-lg btnRetourEtape0"><i class="fa fa-undo mr-2"></i> Menu congélation</button>
        </div>

        <?php

		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Froid ['.$id_froid.']';
		}
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
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3 mb-3"></i>Emballages disponibles en Congéalation  :</h4>

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
                    <button type="button" class="btn btn-danger btn-lg form-control padding-25 text-30 btnConformiteCgl" data-conformite="0"><i class="fa fa-times fa-lg mr-3"></i>Non Conforme</button>
                </div>

                <div class="col-3">
                    <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnConformiteCgl" data-conformite="1"><i class="fa fa-check fa-lg mr-3"></i>Conforme</button>
                </div>
            </div> <!-- FIN row boutons -->

        </div> <!-- FIN alerte -->
		<?php
		if ($utilisateur->isDev()) {
			$_SESSION['infodevvue'] = 'Froid [' . $id_froid . ']';
		}

		exit;

	} // FIN ETAPE


	/** ----------------------------------------
	 * Etape        : 10 (ex-102)
	 * Description  : Produits CGL en cours
     * Paramètres   : Froid
	 *  ----------------------------------- */

	if ($etape == 10) {
		modeShowListeProduitsFroid();
        exit;

	} // FIN ETAPE


    /** ----------------------------------------
	 * Etape        : 11
	 * Description  : Reprise pdt en attente
	 * Paramètres   : Id_lot_pdt_froid
	 *  ----------------------------------- */

	if ($etape == 11) {

		// Récupération des variables
		$id_lot_pdt_froid = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		if ($id_lot_pdt_froid == 0) { erreurLot(); exit; }

		$froidManager = new FroidManager($cnx);
		$froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
		if(!$froidProduit instanceof FroidProduit) { erreurLot(); exit; }

		?>
        <div class="row mt-3 align-content-center">
            <div class="col">

                <div class="alert alert-dark">
                    <div class="float-left text-left nopadding">
                        <span class="gris-7 text-26">
                            <i class="fa fa-history fa-lg mr-2 float-left mt-2"></i>
                            Reprise en attente
                        </span>
                    </div>

                    <h3 class="mb-0 text-40 text-center produit-add-upd" data-id-produit="<?php echo $froidProduit->getProduit()->getId(); ?>">
                        <?php echo $froidProduit->getProduit()->getNom(); ?>

                        <!-- Bouton RETOUR adaptatif -->
                        <button type="button" class="btn btn-secondary btn-lg float-right btnRetourProduits"><i class="fa fa-undo mr-2"></i>Annuler</button>

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

                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark padding-15 btn-large" data-valeur="T">
                                <i class="fa fa-tachometer-alt"></i>
                                <span class="nomargin text-20">100%</span>
                            </button></div>
                        <div class="col-8"><button type="button" class="form-control mb-2 btn btn-success padding-15 btn-large btnValiderCode" data-valeur="V" data-id-lot-pdt-froid="<?php
                            echo $id_lot_pdt_froid; ?>"><i class="fa fa-check"></i></button>
                        </div>
                    </div>

                </div> <!-- FIN alerte -->

            </div> <!-- FIN bloc gauche -->

            <!-- Bloc droite : Champs -->
            <div class="col-8">
                <div class="row">
                    <div class="col">
                        <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i><?php echo $froidProduit->getProduit()->getVrac() == 1 ? 'Poids' : 'Nombre de colis'; ?> en congélation :</h4>
                    </div>
                </div>

                <div class="row">
                    <!-- Valeurs d'origine pour les palettes -->
                    <input type="hidden" id="inputIdProduit" value="<?php echo $froidProduit->getProduit()->getId(); ?>"/>
                    <input type="hidden" id="inputIdLotPdtFroid" value="<?php echo $id_lot_pdt_froid; ?>"/>
                    <input type="hidden" id="inputIdCompo" value="<?php echo $froidProduit->getId_compo() ?>" data-id-histo="<?php echo $froidProduit->getId_compo() ?>"/>
                    <input type="hidden" id="inputIdPalette" value="<?php echo $froidProduit->getId_palette() ?>"/>
                    <input type="hidden" id="inputIdQuantieme" value="<?php echo $froidProduit->getQuantieme(); ?>"/>
                    <input type="hidden" id="poidsPaletteHisto" value="<?php echo $froidProduit->getPoids(); ?>"/>
                    <input type="hidden" id="nbColisPaletteHisto" value="<?php echo $froidProduit->getNb_colis(); ?>"/>
                    <input type="hidden" id="skipCreateCompoSave" value="0"/>
                    <input type="hidden" id="forceSaveProduit" value=""/>
                    <input type="hidden" id="vrac" value="<?php echo $froidProduit->getProduit()->getVrac(); ?>>"/>

                 <!-- Nombre de colis -->
                    <div class="col-4 <?php echo $froidProduit->getProduit()->getVrac() == 1 ? 'd-none' : '';?>">
                        <div class="input-group">
                            <input type="text" class="form-control text-100 text-center inputNbColis" name="nb_colis" placeholder="0" value="<?php echo $froidProduit->getNb_colis(); ?>">
                            <input type="hidden" name="nb_colis_old" value="<?php echo $froidProduit->getNb_colis(); ?>">
                            <span class="input-group-append">
                                  <span class="input-group-text text-26"> colis</span>
                            </span>
                        </div>
                    </div>

				  <!-- Nombre de pièces -->
				  <div class="col-4 <?php echo $froidProduit->getProduit()->getVrac() == 1 ? 'd-none' : '';?>">
                        <div class="input-group">
                            <input type="text" class="form-control text-100 text-center inputQuantite" name="quantite" placeholder="0" value="<?php echo $froidProduit->getQuantite(); ?>">
                            <input type="hidden" name="quantite_old" value="<?php echo $froidProduit->getQuantite(); ?>">
                            <span class="input-group-append">
                                  <span class="input-group-text text-26"> pieces</span>
                            </span>
                        </div>
                    </div>

                     <!-- Poids -->
                    <div class="col-4">
                        <div class="input-group">
                             <span class="input-group-prepend">
                                  <span class="input-group-text text-38 gris-9"><i class="fa fa-weight"></i></span>
                            </span>
                            <input type="text" class="form-control text-100 text-center inputPoidsPdt" name="poids_pdt" placeholder="0" value="<?php echo $froidProduit->getPoids(); ?>" data-poids-defaut="<?php echo $froidProduit->getProduit()->getPoids(); ?>">
                            <input type="hidden" name="poids_pdt_old" value="<?php echo $froidProduit->getPoids(); ?>">
                            <span class="input-group-append">
                                  <span class="input-group-text text-26"> kg</span>
                            </span>
                        </div>
                    </div>

                </div> <!-- FIN row -->

                <div class="row">
                    <div class="col-12 mt-3 gris-5">
                        <i class="fa fa-info-circle mr-1 gris-9"></i>Quantité disponible en attente : <?php echo $froidProduit->getNb_colis(); ?> colis (<?php echo $froidProduit->getPoids(); ?> kg)
                    </div>
                    <div class="col-12 mt-3">
                        <div class="alert alert-secondary padding-20">
                            Lot <span class="badge badge-dark text-16"><?php echo $froidProduit->getNumlot().$froidProduit->getQuantieme(); ?></span>
                            <?php
                            if ($froidProduit->getId_palette() > 0) { ?>
                                <span class="ml-2">Palette </span> <span class="badge badge-info text-16"><?php echo $froidProduit->getNumero_palette(); ?></span>
                            <?php } else { ?>
                                <span class="ml-5"><i class="fa fa-exclamation-triangle mr-1"></i> Quantité cumulée issue de plusieurs palettes : précisez la palette pour la quantité reprise&hellip;</span>
                            <?php } ?>

                        </div>
                    </div>
                </div>

            </div> <!-- FIN bloc droit -->

            <?php
            // Si l'Id_palette ou l'id_compo est à zéro, on demande de repréciser la palette...
            if ($froidProduit->getId_palette() == 0 || $froidProduit->getId_compo() == 0) { ?>


                <div class="col-12 ">
					<?php if ($froidProduit->getProduit()->isMixte()) { ?>
                        <p class="nomargin text-14 gris-7"><i class="fa fa-random mr-1 gris-9"></i>Palettes Mixtes :</p>
					<?php } ?>
                    <div class="row" id="listePalettesPdt">

						<?php
						$palettesManager = new PalettesManager($cnx);

						// On récupère toutes les palettes qui sont en production (statut = 0) pour le produit en cours
						$params = [
							'statut' => 0,
							'vides' => false,
							'id_produit' => $froidProduit->getProduit()->getId(),
                            'mixte' => $froidProduit->getProduit()->isMixte(),
							'id_client'  => $froidProduit->getProduit()->getId_client(),
							'hors_frais' => true
						];
						$palettes = $palettesManager->getListePalettes($params);
						if (empty($palettes)) { ?>

                            <div class="col-2 aucunePaletteEnCours">
                                <div class="alert alert-secondary text-center padding-50 w-100 text-18 gris-9">
                                    Aucune palette<br>en cours...
                                </div>
                            </div>

						<?php }

						$palettePdt = isset($froidPdt) && $froidPdt instanceof FroidProduit ? $froidPdt->getId_palette() : 0;

						foreach ($palettes as $palette) {

						$poidsPalette = $palettesManager->getPoidsTotalPalette($palette);
						$colisPalette = $palettesManager->getNbColisTotalPalette($palette);
						$capacitePoids = $palettesManager->getCapacitePalettePoids($palette);
						$capaciteColis = $palettesManager->getCapacitePaletteColis($palette);

						$restantPoids = floatval($capacitePoids - $poidsPalette);
						$restantColis = intval($capaciteColis - $colisPalette);

						?>

                        <div class="col-2 mb-1">
                            <div class="card bg-dark text-white pointeur carte-palette <?php echo $palette->getId() == $palettePdt ? 'palette-selectionnee' : ''; ?>"
                                 id="cartepaletteid<?php echo $palette->getId(); ?>"
                                 data-id-palette="<?php echo $palette->getId(); ?>"
                                 data-id-client="0"
                                 data-numero-palette="<?php echo $palette->getNumero(); ?>"
                                 data-id-poids-restant="<?php echo $restantPoids; ?>"
                                 data-id-colis-restant="<?php echo $restantColis; ?>">

								<?php if ($utilisateur->isDev()) { ?>
                                    <div class="dev-id-info dev-info-left"><i class="fa fa-user-secret"></i>
                                        <p class="texte-fin"><?php echo $palette->getId(); ?></p></div>
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
											foreach ($palettesManager->getClientsPalette($palette) as $clt_palette) { ?>
                                                <div class="text-14 texte-fin"><?php echo $clt_palette; ?></div>
											<?php } // FIN boucle clients de la palette
											?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body padding-10">
                                    <div class="row text-14"
                                    ">
                                    <div class="col text-center">
										<?php echo number_format($poidsPalette, 3, '.', ' '); ?> kgs
                                    </div>
                                    <div class="col text-center">
										<?php echo $colisPalette; ?> colis
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer padding-10 bg-<?php echo $restantPoids <= 0 || $restantColis <= 0 ? 'warning text-dark' : 'secondary'; ?>">
                                <div class="row">
									<?php if ($restantPoids <= 0 || $restantColis <= 0) { ?>
                                        <div class="col-12 text-center text-16">Capacité atteinte !</div>
                                        <div class="col-6 text-center text-18">
											<?php echo $restantPoids < 0 ? '+ ' . number_format($restantPoids * -1, 0) : number_format($restantPoids, 0); ?>
                                            <span class="texte-fin text-14">kgs</span>
                                        </div>
                                        <div class="col-6 text-center text-18">
											<?php echo $restantColis < 0 ? '+ ' . $restantColis * -1 : $restantColis; ?>
                                            <span class="texte-fin text-14">colis</span>
                                        </div>

									<?php } else { ?>
                                        <div class="col-12 text-center text-16">Capacité restante :</div>
                                        <div class="col-6 text-center text-18">
											<?php echo number_format($restantPoids, 0); ?> <span
                                                    class="texte-fin text-14">kgs</span>
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
                        <button type="button"
                                class="btn btn-dark form-control text-center text-20 padding-15 btnNouvellePalette">
                            <i class="fa fa-plus-square fa-lg"></i>
                            <p class="mb-0 mt-1 text-16">Nouvelle palette</p>
                        </button>
						<?php
						// On affiche le bouton "Voir complètes" s'il y en a...
						$paramsCompletes = [
							'statuts'     => '1,2',
							'vides'      => false,
							'id_produit' => $froidProduit->getId_pdt(),
							'mixte' => $froidProduit->getProduit()->isMixte(),
							'id_client' => $froidProduit->getProduit()->getId_client(),
							'hors_frais' => true
						];
						$palettesCompletes = $palettesManager->getListePalettes($paramsCompletes);
						if (!empty($palettesCompletes)) { ?>
                            <br>
                            <button type="button" class="btn btn-dark form-control text-center text-20 padding-15 btnPalettesCompletes mt-2">
                                <i class="fa fa-ellipsis-h fa-lg"></i>
                                <p class="mb-0 mt-1 text-16">Voir complètes</p>
                            </button>
						<?php }
						?>
                    </div>

                </div> <!-- FIN col-12 palettes -->
                </div> <!-- FIN conteneur ROW palettes -->


			<?php
            // Sinon, la palette est forcée à l'existante, mais pour le petit js qui snif snif la classe de palette sélectionnée, on lui donne comme ça il est content.... gentil le js, gentil...
			} else { ?>

                <span class="palette-selectionnee palette-selectionnee-masquee" id="cartepaletteid<?php echo $froidProduit->getId_palette(); ?>" data-id-poids-restant="<?php

                // Pour le poids restant de la palette, plutot que d'instancier la palette et de faire les calculs, on prends le max du produit en attente, puisque de toute façon on ne vas pas dépasser ce qu'il y a déjà dans la                        palette ici, elle est unique et déjà remplie...
                echo $froidProduit->getPoids() + 1;?>" data-id-client="0"></span>

            <?php
            } // FIN test palette associée
            ?>

        </div> <!-- FIN row contenu -->

        <?php
		if ($utilisateur->isDev()) {

			$_SESSION['infodevvue'] = 'FroidProduit [' . $id_lot_pdt_froid. '] <br>Produit ['.$froidProduit->getProduit()->getId().']';
			$_SESSION['infodevvue'].= $froidProduit->getProduit()->isMixte() ? ' (mixte)' : '';
			$_SESSION['infodevvue'].= '<br>Compo ['.$froidProduit->getId_compo().']<br>Palette ['.$froidProduit->getId_palette().']';
			if ($froidProduit->getId_palette() == 0 || $froidProduit->getId_compo() == 0) {
				$nPal = isset($palettes) ? count($palettes) : 0;
				$nPalS = $nPal > 1 ? 's' : '';
				if ($nPal == 0) { $nPal = 'Aucune'; }
			    $_SESSION['infodevvue'].= '<br>ID compo ou palette à zéro : préciser la palette obligatoire.';
				$_SESSION['infodevvue'].= '<br>Client ['.$froidProduit->getProduit()->getId_client().']<br>'.$nPal.' autre'.$nPalS.' palette'.$nPalS.' compatible'.$nPalS.' trouvée'.$nPalS.'';
			}
		}

		exit;

	} // FIN ETAPE

	/** ----------------------------------------
	 * Etape        : 100
     * Condition    : CGL non bloquée
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
                    <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnConfirmClotureCgl">
                        <i class="fa fa-flag-checkered fa-2x mr-3"></i><div>Clôturer la congélation</div>
                    </button>
                </div>
            </div>

        </div> <!-- FIN alerte -->

    <?php
    if ($utilisateur->isDev()) {
		$_SESSION['infodevvue'] = ' Validation de clôture';
	}

	exit;

	} // FIN ETAPE


	/** ----------------------------------------
	 * Etape        : 101
	 * Description  : Liste des congélations non clôturées
	 *  ----------------------------------- */

	if ($etape == 101) {

		// On récupère les congélations non clôturées
		$froidManager = new FroidManager($cnx);
		$params = [
			'type_code'     => 'cgl',   // Type de Froid
			'statuts'       => '0,1',   // En cours ou bloqué
			'lots_objets'   => true,    // On récupère les objets Lot
			'nb_pdts'       => true     // On récupère le nombre de produits
		];
		$listeCglEnCours = $froidManager->getFroidsListe($params);

		// Si aucune congélation en cours
		if (empty($listeCglEnCours)) {

		    if ($utilisateur->isDev()) {
		        $_SESSION['infodevvue'] = '<i class="fa fa-ban text-danger"></i> Aucun objet trouvé [type CGL] [statut 0|1] ';
			}
		    ?>

            <!-- Aucune congélation dans le pipe -->
            <div class="row mt-2 align-content-center">
                <div class="col text-center">
                    <div class="alert alert-secondary">

                       <span class="fa-stack fa-2x mt-5">
                           <i class="fas fa-snowflake fa-stack-1x"></i>
                           <i class="fas fa-ban fa-stack-2x" style="color:Tomato"></i>
                       </span>

                        <h3 class="gris-7 mt-3 mb-5">Aucune congélation en cours&hellip;</h3>

                        <button type="button" class="mb-5 btn btn-info btn-lg text-28 padding-top-15 padding-bottom-15 padding-left-50 padding-right-50 btnRetourEtape0">
                            <i class="fa fa-undo text-22 mr-1"></i> Retour
                        </button>

                    </div> <!-- FIN alerte -->
                </div> <!-- FIN col -->
            </div> <!-- FIN row conteneur -->

			<?php

			// On ne vas pas plus loin...
			exit;

		} // FIN test aucune CGL non clôturée
		?>

        <!-- Congélations non clôturées : En-tête -->
        <div class="row">
            <div class="col mt-3">
                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Congélations en cours :</h4>
            </div>
        </div>

		<?php

        if ($utilisateur->isDev()) {
            $s = count($listeCglEnCours) > 1 ? 's' : '';
			$_SESSION['infodevvue'] = count($listeCglEnCours) . ' objet'.$s.' trouvé'.$s.' [type CGL] [statut 0|1] ';
		}
		// Boucle sur les CGL en cours
        $i = 0;
		foreach ($listeCglEnCours as $froid) {

			if ($utilisateur->isDev()) {
				$_SESSION['infodevvue'].= '<br>Froid ['.$froid->getId().']';
			}

		    if ($i % 4 == 0) { ?><div class="clearfix"></div><?php } $i++; // Gestion du retour à la ligne tous les 4 blocs
		    ?>

            <!-- Carte de l'OP de froid -->
            <div class="card text-white bg-info mb-3 carte-cgl d-inline-block mr-3" style="max-width: 20rem;" data-id-froid="<?php echo $froid->getId(); ?>">

                <!-- Header de la carte : identifiant de l'OP -->
                <div class="card-header text-36"><?php echo 'CGL'.sprintf("%04d", $froid->getId()); ?></div>

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
                                <td class="vmiddle nowrap">Colis :</td>
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
							// Si la congélation a commencée... (test sur présence d'une heure de début)
							if ($froid->isEnCours()) { ?>

                                <!-- Température de début -->
                                <tr>
                                    <td class="nowrap">Temp. début :</td>
                                    <th class="text-right text-18">
										<?php
										// Echapement T° début inconnue ?!
										if ($froid->getTemp_debut() == '') { echo $na; }
										else {
											$tempDebutCgl       = number_format($froid->getTemp_debut(),3, '.', '');
											$tempDebutCglArray  = explode('.', $tempDebutCgl);
											echo $tempDebutCglArray[0] . '.<span class="text-16">'.$tempDebutCglArray[1].'</span>'; ?> &deg;C
											<?php
										} // FIN test température de début renseignée ?>
                                    </th>
                                </tr>

                                <!-- Heure d'entrée dans le tunel -->
                                <tr>
                                    <td class="nowrap vmiddle">Entrée tunnel :</td>
                                    <th class="text-right text-16"><?php
										echo Outils::getDate_verbose($froid->getDate_entree(), false, ' - '); ?>
                                    </th>
                                </tr>

								<?php
							} // FIN congélation commencée ?>

                            <tr>
								<?php
								// Si la congélation commencée est terminée (test sur présence d'une heure de sortie de tunel)
								if ($froid->isSortie()) { ?>

                                    <!-- Heure de sortie -->
                                    <td class="nowrap vmiddle">Sortie tunnel :</td>
                                    <th class="text-right text-16"><?php
										echo Outils::getDate_verbose($froid->getDate_sortie(), false, ' - '); ?>
                                    </th>

									<?php
									// Sinon, on affiche la sortie estimée
								} else if ($froid->isEnCours()) { ?>

                                    <!-- Heure de sortie estimée -->
                                    <td class="nowrap vmiddle">Sortie estimée :</td>
                                    <th class="text-right text-16"><?php

										$configManager = new ConfigManager($cnx);
										$config_duree_cgl_max  = $configManager->getConfig('duree_cgl_max');
										$dureeCglMax = $config_duree_cgl_max instanceof Config ? intval($config_duree_cgl_max->getValeur()) : 19;

										// Calcul : date entrée + 19 heures
										$sortieEstime = date('Y-m-d H:i',strtotime('+'.$dureeCglMax.' hour',strtotime($froid->getDate_entree())));
										echo Outils::getDate_verbose($sortieEstime, false, ' - '); ?></th>
									<?php
								} // FIN test congélation sortie ou non ?>
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
					$footerCardTxt  = 'En tunel';
					$footerCardBg   = 'bg-success';

				} else if ($froid->isSortie()) {

					$footerCardFa   = 'check-square';
					$footerCardTxt  = 'Sortie du tunnel';
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

    exit;

} // FIN mode


/* ------------------------------------------
MODE - Charge le ticket
-------------------------------------------*/
function modeChargeTicketLot() {

	global
        $cnx, $utilisateur;

	// Récupération des variables
	$etape          = isset($_REQUEST['etape'])     ? intval($_REQUEST['etape'])    : 0;
	$id_froid       = isset($_REQUEST['id_froid'])  ? intval($_REQUEST['id_froid']) : 0;
	$identifiant    = isset($_REQUEST['id'])        ? $_REQUEST['id']               : '';

	$err = '<span class="badge danger badge-pill text-14">ERREUR !</span>';

	if ($utilisateur->isDev()) { ?>
        <kbd class="w-100 btnConsoleDev"><i class="fa fa-user-secret"></i> Console Dev</kbd>
	<?php }

	/** ----------------------------------------
	 * DEV - On affiche l'étape pour débug
	 *  ----------------------------------- */
/*	if ($utilisateur->isDev()) { */?><!--
        <div class="dev-etape-vue"><i class="fa fa-user-secret fa-lg mr-1"></i>Etape <kbd><?php /*echo $etape;*/?></kbd></div>
	--><?php /*} // FIN test DEV*/



	/** ----------------------------------------
	 * TICKET
	 * Etape        : 101
	 * Description  : Sélection CGL
	 *  ----------------------------------- */

	if ($etape == 101 ) { ?>

        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot="0">
            &mdash;
        </div>

        <p class="mt-2 text-center">Sélectionnez une congélation&hellip;</p>

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

        // Si c'est une nouvelle congélation et pas un retour à la section des produits, on propose de revenir à l'écran d'accueil
        if ($id_froid == 0) { ?>

            <!-- Bouton retour accueil froid -->
            <button type="button" class="btn btn-danger btn-lg form-control btnRetourEtape0 text-left mt-1"><i class="fa fa-undo fa-lg vmiddle mr-2"></i>Annuler</button>

        <?php
        } // FIN test nouvelle congélation

    } // FIN ETAPE 1

	/** ----------------------------------------------------
	 * TICKET
	 * Etape        : 51 (Statut propre au ticket)
	 * Description  : Affiche "en cours" après entrée tunel
     *                avant le numéro de CGL dans le ticket
	 *  ------------------------------------------------- */

	if ($etape == 51) { ?>

        <p id="justFreezed"><i class="fa fa-clock mr-1"></i> Congélation en cours&hellip;</p>

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
	 * Description  : Affichage des infos CLG
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

		    // Affichage "en cours" sur liste des produits d'une CGL déjà lancée
			if ($etape == 10 && $froid->isEnCours() && !$froid->isSortie()) { ?>

                <p><i class="fa fa-clock mr-1"></i> Congélation en cours&hellip;</p>

			<?php } // FIN affichage en cours sur liste des produits d'une CGL déjà lancée

			// Affichage "terminée" sur liste des produits d'une CGL sortie du tunel
			if ($etape == 10 && $froid->isEnCours() && $froid->isSortie()) { ?>

                <p><i class="fa fa-check-square mr-1"></i> Congélation terminée</p>

			<?php } // FIN affichage en cours sur liste des produits d'une CGL sortie de tunel
		    ?>

            <!-- Affichage du numéro de traitement -->
            <div class="alert bg-info text-24 text-center mb-1 <?php echo $etape < 4 ? 'mt-4' : ''; ?>">
                <i class="fa fa-snowflake text-20"></i>
				<?php echo 'CGL'.sprintf("%04d", $froid->getId()); ?>
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

						$lotManager = new LotManager($cnx);
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

                <!-- Nombre de colis -->
                <tr>
                    <td class="vmiddle nowrap">Colis :</td>
                    <th class="text-right"><span class="badge badge-info text-20"><?php echo $froidManager->getNbColisFroid($froid); ?></span></th>
                </tr>

                <!-- Poids total -->
                <tr>
                    <td class="vmiddle nowrap">Poids total :</td>
                    <th class="text-right text-18"><?php

                        // Formatage CSS des décimales

    					$poidsTotalFroid = number_format($froidManager->getPoidsFroid($froid),3, '.', '');
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
								$tempDebutCgl       = number_format($froid->getTemp_debut(),3, '.', '');
								$tempDebutCglArray  = explode('.', $tempDebutCgl);
								echo $tempDebutCglArray[0] . '.<span class="text-16">'.$tempDebutCglArray[1].'</span>'; ?> &deg;
							</th>
                    </tr>

				<?php } // FIN ETAPE


				/** -----------------------------------------------------
				 * TICKET (Froid)
				 * Etape        : 51 (Début CGL) | 10 (Liste Pdts)
				 * Description  : Heure entrée tunnel et sortie estimée
				 *  -------------------------------------------------- */

				// Si la CGL viens d'être lancée (51) ou si on est sur la liste des produits (10)
                if ($etape == 51 || $etape == 10) {

                    // Si la congélation est démarrée mais pas encore sortie du tunel...
                    if ($froid->isEnCours() && !$froid->isSortie()) { ?>

                        <!-- Heure entrée en tunel -->
                        <tr>
                            <td class="nowrap vmiddle">Entrée tunnel :</td>
                            <th class="text-right text-16">
								<?php echo Outils::getDate_verbose($froid->getDate_entree(), false, ' - '); ?>
                            </th>
                        </tr>

                        <!-- Heure ESTIMEE de sortie (19h) -->
                        <tr>
                            <td class="nowrap vmiddle">Sortie estimée :</td>
                            <th class="text-right text-16">
								<?php

								$configManager = new ConfigManager($cnx);
								$config_duree_cgl_max  = $configManager->getConfig('duree_cgl_max');
								$dureeCglMax = $config_duree_cgl_max instanceof Config ? intval($config_duree_cgl_max->getValeur()) : 19;


								$sortieEstime = date('Y-m-d H:i', strtotime('+'.$dureeCglMax.' hour', strtotime($froid->getDate_entree())));
								echo Outils::getDate_verbose($sortieEstime, false, ' - '); ?>
                            </th>
                        </tr>

						<?php
					} // FIN test date entrée

                } // FIN ETAPE


				/** -------------------------------------------------------------
				 * TICKET (Froid)
				 * Etape        : > 5 | [!51] | 10->Sortie
				 * Description  : Heures entrée et sortie tunel + temps effectif
				 *  ---------------------------------------------------------- */

				// Après le départ de la CGL (5), hors 51 (pas juste après le départ), pas depuis la liste des produits, sauf si date sortie.
				if (($etape > 5 && $etape != 51 && $froid->isSortie())) {
				//if (($etape > 5 && $etape != 51 && $etape != 10 || ($etape == 10 && ($froid->isSortie())))) { ?>

                    <!-- Heure entrée tunel -->
                    <tr>
                        <td class="nowrap vmiddle">Entrée tunnel :</td>
                        <th class="text-right text-16"><?php echo Outils::getDate_verbose($froid->getDate_entree(), false, ' - '); ?></th>
                    </tr>

                    <!-- Heure sortie tunel -->
                    <tr>
                        <td class="nowrap vmiddle">Sortie tunnel :</td>
                        <th class="text-right text-16"><?php echo Outils::getDate_verbose($froid->getDate_sortie(), false, ' - '); ?></th>
                    </tr>

                    <!-- Temps effectif de la congélation -->
                    <tr>
                        <td class="vmiddle">Temps de congélation :</td>
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


				/** -------------------------------------------------------
				 * TICKET (Froid)
				 * Etape        : 7
				 * Description  :  Affiche la T° de fin en ROUGE (blocage)
				 *  ---------------------------------------------------- */

				// Etape blocage ou à tout moment si la CGL est bloquée avec une température de fin non nulle
				if ($etape == 7 || ($froid->getStatut() == 1 && $froid->getTemp_fin() != '') ) { ?>

                    <!-- Affichage en ROUGE de la température de fin en blocage -->
                    <tr>
                        <td class="nowrap vmiddle">Temp. fin :</td>
                        <th class="text-right">
                            <i class="fa fa-exclamation-triangle mr-2 text-warning fa-lg"></i>
                            <span class="badge badge-danger text-20">
							<?php
							if ($froid->getTemp_fin() == '') { echo $na; } else {

								// Formatage CSS des décimales
								$tempDebutCgl       = number_format($froid->getTemp_fin(),3, '.', '');
								$tempDebutCglArray  = explode('.', $tempDebutCgl);
								echo $tempDebutCglArray[0] . '.<span class="text-16">'.$tempDebutCglArray[1].'</span>'; ?> &deg;C
							<?php } ?>
                            </span>
                        </th>
                    </tr>

				<?php } // FIN ETAPE


				/** ----------------------------------------
				 * TICKET (Froid)
				 * Etape        : > 7
				 * Description  : Affiche la T° de fin
				 *  ----------------------------------- */

				// A partir de l'étape 8 (après avoir saisi la T° de fin), mais pas juste après départ CGL
				if ($etape > 7 && $etape != 51) {

				    // On exclu le cas d'une reprise de CGL non encore démarrée
				    if ($etape == 10 && $froid->getTemp_fin() == '') {} else {  ?>

                        <!-- T° de fin -->
                        <tr>
                            <td class="nowrap vmiddle">Temp. fin :</td>
                            <th class="text-right text-18">
								<?php
								if ($froid->getTemp_fin() == '') { echo $na; } else {

									// Formatage CSS des décimales
									$tempDebutCgl       = number_format($froid->getTemp_fin(),3, '.', '');
									$tempDebutCglArray  = explode('.', $tempDebutCgl);
									echo $tempDebutCglArray[0] . '.<span class="text-16">'.$tempDebutCglArray[1].'</span>'; ?> &deg;C
								<?php } ?>
                            </th>
                        </tr>
                    <?php
				    } // FIN Exclusion reprise CGL non encore démarrée

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
			 * Etape        : -
			 * Description  : Message clg bloquée
			 *  ----------------------------------- */

			// Congélation bloquée
			if ($froid->getStatut() == 1) { ?>

                 <div class="alert alert-danger mt-2 text-center">
                    <i class="fa fa-exclamation-triangle text-30"></i>
                    <h4 class="text-15">Congélation bloquée !</h4>
                    <p class="text-14">La validation d'un administrateur est requise pour débloquer la production.</p>
                </div>

            <?php } // FIN ETAPE


            /** ----------------------------------------
			 * TICKET (Froid)
			 * Etapes       : 5 | 8 | 9 | 100 | 10*
			 * Description  : Bouton retour étape zéro
			 * Condition    : On viens de l'étape 0 ou du changement de rouleau
			 *                (identifiant = 1)
			 *  ----------------------------------- */

            // Départ CGL (5), Emballages (8), Conformité (9), Clôture (100), Liste produits (10) si depuis étape 0 (identifiant 1)
			if ($etape == 5 || $etape == 8 || $etape == 9 || $etape == 100 || ($etape == 10 && $identifiant == 1)) { ?>

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

			// Départ Congélation prêt...
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
			 * Condition    : Congélation non commencée (date début) + pas bloquée ni terminée (statut 0)
			 *  ----------------------------------------------------------------------------------------- */

			if ($etape == 10 && (!$froid->isEnCours()) && $froid->getStatut() == 0) { ?>

                <!-- Bouton retour sélection CGLs en cours -->
                <button type="button" class="btn btn-secondary btn-lg form-control btnRetourSelectionPdts text-left margin-bottom-10 text-18 padding-20-10">
                    <i class="fa fa-fw fa-lg fa-plus-square vmiddle mr-2"></i>Ajouter des produits&hellip;
                </button>

                <!-- Bouton début de congélation : une fois tout étiqueté (masqué par défeut) -->
                <button type="button" class="btn btn-success btn-lg form-control btnFinEtiquetage text-center text-24 margin-top-25">
                    <i class="fa fa-check vmiddle text-40 vmiddle mb-2 mt-2"></i><p class="nomargin">Prêt à congeler</p>
                </button>

			<?php } // FIN ETAPE

			/** --------------------------------------------------------------------------------------------
			 * Calcul de la prochaine étape en fonction des données présentes
			-------------------------------------------------------------------------------------------- */

			// Par défaut -> Fin de congélation, saisir de la T° de sortie
            $nextEtape = 6;

            // Si on a une T° de SORTIE -> Emballages
            if ($froid->getTemp_fin() != 0.0 && $froid->getTemp_fin() != '' && $froid->getTemp_fin() != null) {
				$nextEtape = 8;
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


			/** --------------------------------------------------------------------------------------------
			 * TICKET (Froid)
			 * Etape        : 51 | 10*6
			 * Description  : Bouton fin CGL
             * Conditions   : Dès le début de CGL (51) ou depuis la liste des produits si en CGL cours
             *                CGL non bloquée ni terminée
			 *  -------------------------------------------------------------------------------------------- */

			if (($etape == 51 || ($etape == 10 && $nextEtape == 6)) && $froid->getStatut() == 0) { ?>

                <button type="button" class="btn btn-success btn-lg form-control btnFinCgl text-center text-24" data-toggle="modal" data-target="#modalConfirmFinFroid">
                    <i class="fa fa-sign-out-alt fa-lg vmiddle mb-2"></i><p class="nomargin">Sortie du tunnel</p>
                </button>

			<?php } // FIN ETAPE


			/** --------------------------------------------------------------------------------------------
			 * TICKET (Froid) - ELSE
			 * Etape        : 8 | 10*
             * Description  : Bouton Continuer (adaptatif)
             * Condition    : Prochaine étape pas sélection lot ou depuis la gestion des emballages
			 *  -------------------------------------------------------------------------------------------- */

			else if (($etape ==  10 && $nextEtape > 1) || $etape ==  8) { ?>

                <button type="button" class="btn btn-success btn-lg form-control btnContinuerCgl text-center text-24" data-etape-suivante="<?php echo $nextEtape; ?>">
                    <i class="fa fa-play fa-lg vmiddle mb-2"></i><p class="nomargin">Continuer</p>
                </button>

			<?php } // FIN ETAPE

			} // FIN test objet froid instancié

	} // FIN affichage froid

} // FIN mode


/* ------------------------------------------
MODE - Affiche les produits d'une famille
-------------------------------------------*/
function modeShowProduitsFamille($id_famille = 0, $id_lot = 0, $exit = true) {

	global $utilisateur, $cnx;

	if ($id_famille == 0) { $id_famille = isset($_REQUEST['id_famille']) ? intval($_REQUEST['id_famille']) : 0; }
	if ($id_lot     == 0) { $id_lot     = isset($_REQUEST['id_lot'])     ? intval($_REQUEST['id_lot'])     : 0; }
	if ($id_famille == 0 || $id_lot == 0) { exit; }

	$produitsManager    = new ProduitManager($cnx);
	$familleManager     = new ProduitEspecesManager($cnx);
	$froidManager       = new FroidManager($cnx);
	$famille = $familleManager->getProduitEspece($id_famille);

	if (!$famille instanceof ProduitEspece) { exit; }

	// On récup les ID pdt de toute l'op de froid en cours, pour matcher ceux qui y sont déjà
    $id_froid       = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	$idPdtsFroidLot = $id_froid > 0 ? $froidManager->getIdPdtsFroidLot($id_froid, $id_lot) : [];

	// Préparation pagination (Ajax)
	$nbResultPpage      = 15;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode=showProduitsFamille&id_famille='.$id_famille.'&id_lot='.$id_lot;
	$start              = ($page-1) * $nbResultPpage;

	if ($utilisateur->isDev()) {
		$_SESSION['infodevvue'].= 'Lot ['.$id_lot.']';
		$_SESSION['infodevvue'].= '<br>Froid ['.$id_froid.']';
		$_SESSION['infodevvue'].= '<br>ProduitEspece ['.$id_famille.']';
	}

	$params = [
		'id_famille'        => $id_famille,
		'start'             => $start,
		'nb_result_page'    => $nbResultPpage,
        'id_espece'         => $id_famille
	];

	$froidManager = new FroidManager($cnx);
	$froidType = $froidManager->getFroidTypeByCode('cgl');
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
                            <p><i>Famille <?php echo $famille->getNom();?> en congélation</i></p>
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
						// Si on a moins de 3 blocs de libres à droite, on va a la ligne
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


/* ------------------------------------------
MODE - Affiche la selection par code barre
~~~~~~ Déprécié ~~~~~~
-------------------------------------------*/
function modeShowSelectPdtByCode() {

	global $mode, $cnx;

	$id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
	if ($id_lot == 0) { exit; }

	?>
    <div class="row mt-5 align-content-center">
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
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger btn-large" data-valeur="C"><i class="fa fa-backspace"></i></button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="0">0</button></div>
                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-success btn-large btnValiderCode" data-valeur="V" data-id-lot="<?php echo $id_lot; ?>"><i class="fa fa-check"></i></button></div>
                </div>
            </div>
        </div>
        <div class="col-7">
            <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Scannez ou entrez un code produit :</h4>
            <div class="alert alert-secondary">
                <div class="row">
                    <div class="col">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-barcode fa-3x"></i></span>
                            </div>
                            <input type="text" class="form-control text-38 inputCodeBarre" placeholder="" value=""/>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <button type="button" class="btn btn-lg btn-secondary form-control btnCodeRetour" data-lot-id="<?php echo $id_lot; ?>">Retour</button>
                    </div>
                    <div class="col">
                        <button type="button" class="btn btn-lg btn-success form-control btnValiderCodeSaisi">Valider</button>
                    </div>
                </div>
            </div>
            <div class="d-none alert alert-danger codePdtInvalide">
                <i class="fa fa-exclamation-circle fa-3x float-left mr-3"></i> <strong>Aucun produit trouvé !</strong><p>Celui-ci est peut-être désactivé, vérifiez le code ou contactez un administrateur...</p>
            </div>
        </div>
    </div>
	<?php
	exit;
} // FIN mode


/* ------------------------------------------
MODE - Vérifie un code barre produit
~~~~~~ Déprécié ~~~~~~
-------------------------------------------*/

function modeCheckProduitByCode() {

	global $cnx;

	$code = isset($_REQUEST['code']) ? trim(preg_replace("/[^a-zA-Z0-9]/", '', $_REQUEST['code'])) : '';
	if ($code == '') { echo '-1'; exit; }

	$produitsManager = new ProduitManager($cnx);
	$pdts = $produitsManager->searchProduitsByCode($code);

	// Si aucun produit trouvé
	if (!$pdts || !is_array($pdts) || empty($pdts)) { echo '-1'; exit; }

	// Si plusieurs produits trouvés
	if (count($pdts) > 1) { echo '-1'; exit; }

	// Si un seul produit trouvé mais pas instancié
	if (!isset($pdts[0]) || !$pdts[0] instanceof Produit) { echo '0'; exit; }

	// Retour ID produit
	echo $pdts[0]->getId();

	exit;

} // FIN mode


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
		$quantite          = isset($_REQUEST['quantite'])         ? intval($_REQUEST['quantite'])      : 0;
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
		$quantite          = isset($params['quantite'])         ? intval($params['quantite'])      : 0;		
		$quantieme      = isset($params['quantieme'])       ? trim($params['quantieme'])        : '';
		$id_palette     = isset($params['id_palette'])      ? intval($params['id_palette'])     : 0;
		$id_compo       = isset($params['id_compo'])        ? intval($params['id_compo'])       : 0;
		$new_palette    = isset($params['new_palette'])     ? boolval($params['new_palette'])   : false;
    }





	// Instanciation des managers
    $froidManager    = new FroidManager($cnx);
	$lotsManager     = new LotManager($cnx);
	$palettesManager = new PalettesManager($cnx);

    // Gestion des erreurs
    if ($id_pdt == 0 || $id_lot == 0 || $nb_colis < 1 || $poids < 0.1 || $id_palette == 0) { exit('-1'); }

	$lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) { exit('-2'); }

	$lot->setDate_maj(date('Y-m-d H:i:s'));

    // v1.1, on commence par créer l'OP de froid si elle n'existe pas
	if ($id_froid == 0) {

		$typeCgl = $froidManager->getFroidTypeByCode('cgl');
		if (!$typeCgl || $typeCgl == 0) { exit('-3'); }

		$froid = new Froid([]);
		$froid->setId_type($typeCgl);
		$froid->setId_user_maj($utilisateur->getId());
		$id_froid = $froidManager->saveFroid($froid);
		if (!$id_froid || $id_froid == 0) { exit('-4'); }

		// Puisqu'on viens de créer l'op de froid, le lot concerné est officiellement dans la vue congélation
		$lotsManager->addLotVue($lot, 'cgl');       // Affecte le lot à la vue Congélation

		// Log
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("[CGL] Création du traitement OP froid ID " . $id_froid . " à l'ajout du premier produit.") ;
		$logsManager->saveLog($log);

	} // FIN test id froid

    if ($id_froid == 0) { exit('-5'); }

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
			$compo->setQuantite($quantite);

			$palettesManager->savePaletteComposition($compo);
			$logManager = new LogManager($cnx);
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte('Retrait du flag supprimé sur compo #'.$id_compo .' (Une compo a été créé dynamiquement à l\'étape 3) et purge des compos supprimées pour le produit_froid #'.$id_lot_pdt_froid);
			$logManager->saveLog($log);
			$palettesManager->purgeComposSupprimeesByFroidProduit($id_lot_pdt_froid);
        }
    }


	// Une fois le produit froid créé et la compo intégrée, on calcul le total pour mettre à jour le froidproduit
	$pdtLotFroid->setPoids($palettesManager->getPoidsTotalFroidProduit($id_lot_pdt_froid));
	$pdtLotFroid->setNb_colis($palettesManager->getNbColisTotalFroidProduit($id_lot_pdt_froid));	
	$pdtLotFroid->setQuantite($quantite);	
	$froidManager->saveFroidProduit($pdtLotFroid);

	$logVerbe = $update ? "Modification" : "Ajout";

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[CGL] ".$logVerbe." du produit ID " . $id_pdt . " sur le lot ID " . $id_lot . " à l'OP froid ID " . $id_froid . " quantième " . $quantieme . ", nb_colis = ". $nb_colis." poids = " . $poids) ;
	$logsManager->saveLog($log);

	// Retourne l'id de l'OP de froid pour maj CallBack aJax
	echo $id_froid;

	exit;

} // FIN mode


/* ---------------------------------------------------------
MODE - Enregistre la température de début de cgl
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
    echo 1;

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[CGL] Saisie de la température en début de traitement à " . $temp_debut . "°C pour l'OP Froid ID " . $id_froid) ;
	$logsManager->saveLog($log);

    exit;

} // FIN mode


/* ---------------------------------------------------------
MODE - Enregistre la température de fin de cgl
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
	$log->setLog_texte("[CGL] Saisie de la température de fin de traitement à " . $temp_fin . "°C pour l'OP Froid ID " . $id_froid) ;
	$logsManager->saveLog($log);

	// Gestion des alertes
	$configManager      = new ConfigManager($cnx);
	$config_tmp_cgl_min = $configManager->getConfig('tmp_cgl_min');
	$config_tmp_cgl_max = $configManager->getConfig('tmp_cgl_max');
	$config_alerte2_actif = $configManager->getConfig('alerte2_actif');

	// Si l'alerte n'est pas active ou s'il manque une température de référence, on ne va pas plus loin
	if (!$config_tmp_cgl_min instanceof Config || !$config_tmp_cgl_max instanceof Config  || !$config_alerte2_actif instanceof Config ) { exit('1'); }
	if (intval($config_alerte2_actif->getValeur()) == 0) { exit('1'); }

	// Test alerte
	if ($temp_fin < floatval($config_tmp_cgl_min->getValeur()) || $temp_fin > floatval($config_tmp_cgl_max->getValeur())) {

	  $alerteManager = new AlerteManager($cnx);
	  $id_froid_type = intval($froidManager->getFroidTypeByCode('cgl'));

	  // Comme la traçabilité se fait au niveau du lot on crée autant d'alertes que de lots dans la congélations
      foreach ($froid->getLots() as $lotcgl) {
		  $alerte = new Alerte([]);
		  $alerte->setId_lot($lotcgl->getId());
		  $alerte->setType(2);
		  $alerte->setId_froid($id_froid);
		  $alerte->setId_froid_type($id_froid_type);
		  $alerte->setNumlot($lotcgl->getNumlot());
		  $alerte->setDate(date('Y-m-d H:i:s'));
		  $alerte->setId_user($utilisateur->getId());
		  $alerte->setNom_user($utilisateur->getNomComplet());
		  $alerte->setValeur($temp_fin);
		  if ($alerteManager->saveAlerte($alerte)) {
			  $alerteManager->envoiMailAlerte($alerte);
          }


      }

        // En cas d'alerte ici, on bloque le workflow !
        $froid->setStatut(1);
        $froidManager->saveFroid($froid);
        echo '42';
        exit;

    } // FIN test alerte

	echo 1;



	exit;

} // FIN mode


/* ---------------------------------------------------------
MODE - Enregistre la début de congélation
----------------------------------------------------------*/
function modeSaveDebutCgl() {

	global $cnx, $utilisateur, $logsManager;

	$id_froid   = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;

	if ($id_froid == 0) { echo '-1'; exit; }

	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { echo '-2'; exit; }

	$froid->setDate_entree(date('Y-m-d H:i:s'));
	$froid->setId_user_debut($utilisateur->getId());
	$froid->setId_user_maj($utilisateur->getId());
	if (!$froidManager->saveFroid($froid)) { echo '-3'; exit; }

	// On évite les produits non étiquetés (DEBUG)
    $froidManager->etiquetteTout($froid);

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[CGL] Début du traitement de l'OP Froid ID " . $id_froid) ;
	$logsManager->saveLog($log);

	echo 1;

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
	$log->setLog_texte("[CGL] ".$logVerbe." produit ID_LOT_PDT_FROID " . $id_lot_pdt_froid) ;
	$logsManager->saveLog($log);

    exit;

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
    $quantite           = isset($_REQUEST['quantite'])             ? intval($_REQUEST['quantite'])          : 0;
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
		$log->setLog_texte("[CGL] Suppression du produit ID_LOT_PDT_FROID " . $id_lot_pdt_froid . " du traitement OP Froid " . $froidProduit->getId_froid()) ;
		$logsManager->saveLog($log);

		exit;

    } // FIN test supprime pdt froid

    // Si le quantième ou la palette est différent, c'est que l'on crée un nouveau produit
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
			'quantite' => $quantite,
            'new_palette'   => $id_palette != $froidProduit->getId_palette()
        ];
        modeAddPdtFroid($params);

        exit;
    }

	// On enregistre le poids et le nb colis dans la table froid
	if (!$froidManager->savePoidsColisLotProduit($froidProduit, $nb_colis, $poids,$quantite ,$id_palette)) { exit('-1'); }

	// Si on viens juste de rajouter sans changer de palette, sans le moindre clic sur un autre palette, il faut réactiver la compo ! (car l'id_compo est l'ancienne)
    // On réactive donc la dernière compo de cet id_lot_pdt_froid


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
			$log->setLog_texte("[CGL] suppression des compos en trop (modif poids en mode total) pour l'ID_LOT_PDT_FROID " . $id_lot_pdt_froid) ;
			$logsManager->saveLog($log);

		}
	}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[CGL] Modification du produit depuis récap traitement, ID_LOT_PDT_FROID " . $id_lot_pdt_froid) ;
	$logsManager->saveLog($log);

    exit;

} // FIN mode


/* ---------------------------------------------------------
MODE - Contenu de la modale de confirmation de fin de CGL
----------------------------------------------------------*/
function modeModalConfirmFinFroid() {

    global $cnx;

    echo '<i class="fa fa-sign-out-alt mr-1"></i>Sortie du tunnel';
    echo '^'; // Séparateur titre/body

    $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
    if ($id_froid == 0) { erreurLot(); exit; }

    $froidManager = new FroidManager($cnx);
    $froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { erreurLot(); exit; }
    ?>

    <div class="text-28 mb-3">
        Confirmer la fin de congélation ?
    </div>

    <?php
	$dateEntree     = new DateTime($froid->getDate_entree());
	$dateNow        = new DateTime();
	$interval       = $dateEntree->diff($dateNow);

	$jours  = (intval($interval->format('%d')));
	$heures = (intval($interval->format('%h')));
	$min    = (intval($interval->format('%i')));
    if ($jours > 1) { $heures = $heures + 48; }
    else if ($jours > 0) { $heures = $heures + 24; }
    ?>

    <div class="mt-2">
        <span class="gris-5"><i class="fa fa-stopwatch mr-1"></i> Temps écoulé :</span>
        <div><span class="badge badge-info text-30"><?php echo  $heures . ' h ' . sprintf('%02d', $min)  . ' min.'; ?></span></div>
    </div>

    <?php
    exit;

} // FIN mode


/* ---------------------------------------------------------
MODE - Conformité de la congélation (enregistrement)
----------------------------------------------------------*/
function modeConformiteCgl() {

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
	$log->setLog_texte("[CGL] ".$logConformite." du traitement OP froid produit " . $id_froid) ;
	$logsManager->saveLog($log);

    exit;

} // FIN mode


/* ---------------------------------------------------------
FONCTION - Liste (pagination/ajax) des emballages CGL
----------------------------------------------------------*/

function modeListeCartesEmballage() {

	global $cnx, $utilisateur;

	$consommablesManager = new ConsommablesManager($cnx);
	$vuesManager = new VueManager($cnx);

	// Préparation pagination (Ajax)
	$nbResultPpage      = 16;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode=listeCartesEmballage';
	$start              = ($page-1) * $nbResultPpage;

	$params = [
		'id_vue'            => $vuesManager->getVueByCode('cgl')->getId(),
		'get_emb'           => true,
		'has_encours'       => true,
		'start'             => $start,
		'nb_result_page'    => $nbResultPpage,
        'lot_nonsuppr'      => true
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

	if ($utilisateur->isDev()) {

	    $nEmb = isset($famillesListe) ? count($famillesListe) : 0;
	    $nEmbS = $nEmb > 1 ? 's' : '';
	    if ($nEmb == 0) { $nEmb = 'Aucun'; }

		$_SESSION['infodevvue'] = $nEmb . ' famille'.$nEmbS.' de consommable'.$nEmbS.' trouvée'.$nEmbS;
	}

	// Pagination (aJax)
	if (isset($pagination)) {

		// Si on a moins de 3 blocs de libres à droite, on va a la ligne
		$nbCasse = [4,5,10,11,16,17];

		if (in_array($nbPdtOnThePage,$nbCasse)) { ?>
            <div class="clearfix"></div>
		<?php }

		$pagination->setNature_resultats('produit');
		echo ($pagination->getPaginationBlocs());

	} // FIN test pagination

} // FIN mode


/* ---------------------------------------------------------
MODE -  Mise à jour du stock + liaison Froid
----------------------------------------------------------*/
function modeUpdConsommablesHistoFroid() {

	global $cnx;

	// Récupération des variables
	$id_froid   = isset($_REQUEST['id_froid'])  ? intval($_REQUEST['id_froid']) : 0;
	$id_emb     = isset($_REQUEST['id_emb'])    ? intval($_REQUEST['id_emb'])   : 0;
	$sens       = isset($_REQUEST['sens'])      ? intval($_REQUEST['sens'])     : 0;

	// Sécurisation des données
	if ($sens != -1 && $sens != 1) { exit; }
	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { exit; }
	$consommablesManager = new ConsommablesManager($cnx);
	$emb = $consommablesManager->getConsommable($id_emb);
	if (!$emb instanceof Consommable) { exit; }

	// Mise à jour du stock sur l'emballage par défaut
	$vueManager = new VueManager($cnx);
	$idVue = $vueManager->getVueByCode('cgl')->getId();
	$newStock = $sens > 0 ? $emb->getStock() - 1 : $emb->getStock() + 1;
	if (!$consommablesManager->saveConsommablesHisto($emb, $newStock, $idVue)) { exit; }

	// On retourne en callback le noveau stock
	echo $newStock;

	// Association de l'emballage au lot
	$params = [
		'id_emb'    => $id_emb,
		'id_froid'  => $id_froid,
		'qte_upd'   => $sens
	];

	$consommablesManager->addUpdEmballageProd($params);

	// Mise à jour du ticket par autre retour callback
	echo '|'; // Séparateur
	showEmballageProd($id_froid);

	exit;

} // FIN mode

/* ---------------------------------------------------------
MODE -  Fonction déportée pour mise à jour
        du détail stock emballage de la CGL
----------------------------------------------------------*/

function setEmballageProd($id_froid) {

	global
        $cnx,
        $consommablesManager,
		$logsManager;

	if (!$consommablesManager instanceof ConsommablesManager) {
		$consommablesManager = new ConsommablesManager($cnx);
	}

	// On récupère les emballages pour l'op de froid
	$emballagesFroid = $consommablesManager->getListeEmballages(['id_froid' => $id_froid]);

	// Si il n'y en a aucun encore, on associe tous les emballages « en cours » dont la famille est associée à la vue de froid concernée.
	if (empty($emballagesFroid)) {

		$vueManager = new VueManager($cnx);
		$froidManager = new FroidManager($cnx);
		$vueCgl = $vueManager->getVueByCode('cgl');
		if (!$vueCgl instanceof Vue) { ?><div class="alert alert-danger">Identification de la vue impossible.<br>Code Erreur : <code>FCIZEKQJ</code></div><?php  exit; }

		$froid = $froidManager->getFroid($id_froid);
		if (!$froid instanceof Froid) { ?><div class="alert alert-danger">Identification du traitement impossible.<br>Code Erreur : <code>HTLWYNCC</code></div><?php  exit; }

		// Si on arrive à intégrer
		if ($consommablesManager->setEmballagesVue($vueCgl, $froid)) {

			// Log
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte("[CGL] Association des emballages au traitement " . $id_froid) ;
			$logsManager->saveLog($log);

		}

	} // FIN test aucun emballage

	return true;

} // FIN fonction


/* ---------------------------------------------------------
MODE -  Clôture du traitement
----------------------------------------------------------*/
function modeClotureCgl() {

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
	$vueRcp = $vuesManager->getVueByCode('cgl');
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
	$log->setLog_texte("[CGL] Clôture du traitement " . $id_froid) ;
	$logsManager->saveLog($log);

	exit;

} // FIN mode


/* ---------------------------------------------------------
MODE -  Modale edit T° fin de CGL bloquée (admin BO)
----------------------------------------------------------*/
function modeModalUpdTempFin() {

    global $cnx;

	// Récupération des variables
	$id_froid   = isset($_REQUEST['id_froid'])  ? intval($_REQUEST['id_froid']) : 0;
	if ($id_froid == 0) { exit('-1'); }
	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) { exit('-1'); }

	// Récupération des températures mini et maxi autorisées pour le traitement (gestion des alertes et du blocage)
	$configManager      = new ConfigManager($cnx);
	$config_tmp_cgl_min = $configManager->getConfig('tmp_cgl_min');
	$config_tmp_cgl_max = $configManager->getConfig('tmp_cgl_max');
    ?>

    <div class="col-12">
        <h4>Congélation <?php echo 'CGL'.sprintf("%04d", $froid->getId()); ?></h4>
        Température de fin enregistrée : <span class="badge badge-danger text-20"><?php echo number_format($froid->getTemp_fin(),3); ?>°C</span>
    </div>
    <div class="row">
        <div class="col mt-3">
            <div class="alert alert-secondary text-16">
                <div class="col-6 offset-3 mb-2">
                    Modification :
                    <div class="input-group">
                        <input type="text" class="form-control text-24 text-right newTempFroid" placeholder="00.000" value="<?php
                        echo number_format($froid->getTemp_fin(),3); ?>" data-ok-min="<?php
                        echo $config_tmp_cgl_min instanceof Config ?  $config_tmp_cgl_min->getValeur() : -1000; ?>" data-ok-max="<?php
                        echo $config_tmp_cgl_max instanceof Config ?  $config_tmp_cgl_max->getValeur() : 100; ?>" data-old-temp="<?php
                        echo $froid->getTemp_fin();
                        ?>"/>
                        <div class="input-group-append">
                            <span class="input-group-text">°C</span>
                        </div>
                        <div class="invalid-feedback">
                           Température hors norme !<br>Veuillez corriger...
                        </div>
                    </div>
                </div>
                <?php

                if ($config_tmp_cgl_min instanceof Config && $config_tmp_cgl_max instanceof Config) { ?>

                    <!-- Affichage des consignes (si les températures sont bien configurées) -->
                    <i class="fa fa-info-circle mr-1 gris-9"></i>
                    Rappel des consignes : entre <?php
                    echo $config_tmp_cgl_min->getValeur(); ?>°C et <?php echo $config_tmp_cgl_max->getValeur(); ?>°C
                </div>
            </div>
        </div>

		<?php
	}
    exit;

} // FIN mode

/* ---------------------------------------------------------
MODE -  Modif T° fin de CGL bloquée (modale admin BO)
----------------------------------------------------------*/
function modeUpdTempFin() {

	global $cnx, $logsManager;

	// Récupération des variables
	$id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
	if ($id_froid == 0) { exit; }
	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);
	if (!$froid instanceof Froid) {	exit; }

	$temp_fin = isset($_REQUEST['temp_fin']) ? floatval($_REQUEST['temp_fin']) : 0.0;
	$froid->setTemp_fin($temp_fin);

	$froidManager->saveFroid($froid);

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("[CGL] Modification de la température de fin de traitement à " . $temp_fin . "°C sur l'OP " . $id_froid) ;
	$logsManager->saveLog($log);

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

    <table class="table admin table-front-tri mt-2 table-v-middle " data-id-froid="<?php echo $id_froid; ?>">
        <thead>
        <tr>
            <th>Code</th>
            <th class="position-relative padding-left-50 tri-produits" data-sens="<?php echo $sens; ?>" data-tri-actif="<?php echo $colonne == 'pdt' ? 1 : 0; ?>"><i class="fa fa-sort-alpha-<?php echo $sens == 'asc' ? 'down' : 'up'; ?> text-30 <?php echo $colonne == 'pdt' ? 'text-info-light' : 'gris-9'; ?> position-absolute abs-left-15 abs-top-10 "></i> Produit</th>
            <th class="position-relative padding-left-50 tri-lots" data-sens="<?php echo $sens; ?>" data-tri-actif="<?php echo $colonne == 'lot' ? 1 : 0; ?>"><i class="fa fa-sort-numeric-<?php echo $sens == 'asc' ? 'down' : 'up'; ?> text-30 <?php echo $colonne == 'lot' ? 'text-info-light' : 'gris-9'; ?> position-absolute abs-left-15 abs-top-10"></i> Lot</th>
            <th class="text-center pr-4">Palette</th>
            <th class="text-center pr-4">Nb de colis</th>
            <th class="text-center pr-4">Quantite</th>
            <th class="text-right pr-5">Poids total</th>
            <th class="text-center">Etiquetage</th>
            <th class="text-center">En attente</th>
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
                <td class="text-center text-20"><?php echo $pdtFroid->getQuantite();?></td>
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
                <td class="text-center">
                    <button type="button" class="btn btn-warning btnAttenteProduit" data-toggle="modal" data-target="#modalConfirmAttenteProduit" data-id-lot-pdt-froid="<?php echo $pdtFroid->getId_lot_pdt_froid(); ?>" <?php

					// Si le traitement a déjà commencé, on désactive le bouton
					echo $froid->isEnCours() ? 'disabled' : '';
					?>>
                        <i class="fas fa-lg fa-history"></i>
                    </button>
                </td>
            </tr>


		<?php } // FIN boucle produits
		?>

        </tbody>
    </table>
	<?php

} // FIN mode



/* -------------------------------------------------
MODE - Enregistre en commentaire de température HS
--------------------------------------------------*/
function modeSaveCommentaireTempHs() {

	global $cnx, $utilisateur;

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


	exit;

} // FIN mode

/* -------------------------------------------------
MODE - Reprise d'un produit en attente
--------------------------------------------------*/
function modeReprisePdtAttente() {

	global $cnx, $utilisateur, $logsManager;

	$id_lot_pdt_froid   = isset($_REQUEST['id_lot_pdt_froid'])  ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
	$id_palette         = isset($_REQUEST['id_palette'])        ? intval($_REQUEST['id_palette'])       : 0;
	$id_compo           = isset($_REQUEST['id_compo'])          ? intval($_REQUEST['id_compo'])         : 0;
	$id_froid           = isset($_REQUEST['id_froid'])          ? intval($_REQUEST['id_froid'])         : 0;
	$nb_colis           = isset($_REQUEST['nb_colis'])          ? intval($_REQUEST['nb_colis'])         : 0;
	$poids              = isset($_REQUEST['poids'])             ? floatval($_REQUEST['poids'])          : 0.0;

    if ($id_lot_pdt_froid == 0 || $id_palette == 0 || $id_compo == 0 || $nb_colis == 0 || $poids == 0.0) { exit('TG870AL7'); }

	$froidManager    = new FroidManager($cnx);
	$palettesManager = new PalettesManager($cnx);

	$creationFroid = false;

	// On récupère le FroidProduit
	$pdtFroid = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
	if (!$pdtFroid instanceof FroidProduit) { exit('JFH2LFFD'); }

	// Si l'opération de froid n'existe pas encore, on la crée...
	if ($id_froid == 0) {

		$typeCgl = $froidManager->getFroidTypeByCode('cgl');
		if (!$typeCgl || $typeCgl == 0) { exit('0XQZFTRB'); }

		$froid = new Froid([]);
		$froid->setId_type($typeCgl);
		$froid->setId_user_maj($utilisateur->getId());
		$id_froid = $froidManager->saveFroid($froid);
		if (!$id_froid || $id_froid == 0) { exit('R3SFJW5J'); }

		$creationFroid = true;  // Pour retour ajax en cas de création d'un nouveau traitement.

		// Puisqu'on viens de créer l'op de froid, le lot concerné est officiellement dans la vue congélation
		$lotsManager = new LotManager($cnx);

		// On récupère le lot depuis le produit froid
        $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
		$lot = $lotsManager->getLot($pdtFroid->getId_lot());
		if (!$lot instanceof Lot) { exit('SGLTT8WF'); }
		$lotsManager->addLotVue($lot, 'cgl');       // Affecte le lot à la vue Congélation

		// Log
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("[CGL] Création du traitement OP froid ID " . $id_froid . " à l'ajout du premier produit (reprise produit en attente).") ;
		$logsManager->saveLog($log);

    } // FIN création OP de froid.

    // Reprise complète
    if ($poids == $pdtFroid->getPoids() && $nb_colis == $pdtFroid->getNb_colis()) {

		// On peut avoir déjà un FroidProduit repris identique, dans ce cas il faut cumuler les quantités..
        
		// On recherche un FroidProduit pas en attente qui aurait les memes lot, pdt, froid, quantième et palette...
		$pdtFroidAcompleter = $froidManager->getFroidProduitObjetIdem($pdtFroid->getId_lot(), $pdtFroid->getId_pdt(), $id_froid, $pdtFroid->getQuantieme(), $id_palette);

		// Si on le trouve...
		if ($pdtFroidAcompleter instanceof FroidProduit) {

			// on viens rajouter les quantitiés à celui-ci
			$pdtFroidAcompleter->setPoids($pdtFroidAcompleter->getPoids() + $poids);
			$pdtFroidAcompleter->setNb_colis($pdtFroidAcompleter->getNb_colis() + $nb_colis);
			if (!$froidManager->saveFroidProduit($pdtFroidAcompleter)) { exit('13M6M3P7'); }

            // Il n'y donc plus rien dans le produit en attente, on le supprime
			if (!$froidManager->supprPdtfroid($pdtFroid)) { exit('2DG51NJH'); }

			// On crée maintenant la nouvelle compo pour le produit complété

            // On récupère le premier client de la palette
            $id_client = $palettesManager->getClientCompoByIdLotPdtFroid($pdtFroidAcompleter->getId_lot_pdt_froid());
			if (intval($id_client) == 0) {
			    // Si on en a pas (c'est possible ici, en cas d'ancienne palette multiple), on tente de le récupérer via l'ID palette
				$id_client = $palettesManager->getClientCompoByPalette($pdtFroidAcompleter->getId_palette());
				if (intval($id_client) == 0) { exit('H55M618U'); }
			}

			// Ici on va crer la compo en reprendant les quantitiés du produit cumulé, mais si on avait déjà une compo pour cet id_lot_pdt_froid, il faut le supprimer d'abord !
			$palettesManager->supprCompositionFromIdLotPdtFroid($pdtFroidAcompleter->getId_lot_pdt_froid());
			$log = new Log([]);
			$log->setLog_type('warning');
			$log->setLog_texte("Suppression de la composition pour le PDT_FROID #" . $pdtFroidAcompleter->getId_lot_pdt_froid() . " (quantitiés du produit cumulé) ") ;
			$logsManager->saveLog($log);



            // Création de la compo du produit cumulé
			$newCompo = new PaletteComposition([]);
			$newCompo->setSupprime(0);
			$newCompo->setPoids($pdtFroidAcompleter->getPoids());
			$newCompo->setNb_colis($pdtFroidAcompleter->getNb_colis());
			$newCompo->setId_lot_pdt_froid($pdtFroidAcompleter->getId_lot_pdt_froid());
			$newCompo->setId_palette($pdtFroidAcompleter->getId_palette());
			$newCompo->setId_produit($pdtFroidAcompleter->getId_pdt());
			$newCompo->setId_client($id_client);
			$newCompo->setDate(date('Y-m-d H:i:s'));
			$newCompo->setId_user($utilisateur->getId());

			if (!$palettesManager->savePaletteComposition($newCompo)) { exit('FMISXXZB'); }

			// On met à jour le statut de la palette si complète
			$palettesManager->updStatutPalette($pdtFroidAcompleter->getId_palette());

		// Sinon, ben on crée le nouveau produit
		} else {

			// On retire le statut "attente" et on affecte au nouveau traitement
			$pdtFroid->setAttente(0);
			$pdtFroid->setId_froid($id_froid);
			$pdtFroid->setId_palette($id_palette);
			if (!$froidManager->saveFroidProduit($pdtFroid)) { exit('CLKNPL4Z'); }

        } // FIN test produit à compléter ou mise à jour


	    // Reprise partielle
    } else {

        // On recherche un FroidProduit pas en attente qui aurait les memes lot, pdt, froid, quantième et palette...
        $pdtFroidAcompleter = $froidManager->getFroidProduitObjetIdem($pdtFroid->getId_lot(), $pdtFroid->getId_pdt(), $id_froid, $pdtFroid->getQuantieme(), $id_palette);

		// Si on le trouve...
        if ($pdtFroidAcompleter instanceof FroidProduit) {

    		// on viens rajouter les quantitiés à celui-ci
			$pdtFroidAcompleter->setPoids($pdtFroidAcompleter->getPoids() + $poids);
			$pdtFroidAcompleter->setNb_colis($pdtFroidAcompleter->getNb_colis() + $nb_colis);
			if (!$froidManager->saveFroidProduit($pdtFroidAcompleter)) { exit('46YV8NP4'); }

			// On se base ensuite sur son id_lot_pdt_froid pour la suite (compos...)
			$new_id_lot_pdt_froid = $pdtFroidAcompleter->getId_lot_pdt_froid();

		// Sinon, ben on crée le nouveau produit
        } else {

			// On crée un autre FroidProduit avec les quantités choisies
			$newPdtFroid = new FroidProduit([]);
			$newPdtFroid->setId_froid($id_froid);
			$newPdtFroid->setAttente(0);
			$newPdtFroid->setId_pdt($pdtFroid->getId_pdt());
			$newPdtFroid->setId_palette($id_palette);
			$newPdtFroid->setId_lot($pdtFroid->getId_lot());
			$newPdtFroid->setPoids($poids);
			$newPdtFroid->setNb_colis($nb_colis);
			$newPdtFroid->setUser_add($utilisateur->getId());
			$newPdtFroid->setEtiquetage($pdtFroid->getEtiquetage());
			$newPdtFroid->setDate_add(date('Y-m-d h:i:s'));
			$newPdtFroid->setQuantieme($pdtFroid->getQuantieme());
			$new_id_lot_pdt_froid = $froidManager->saveFroidProduit($newPdtFroid);
            
        } // FIN test produit à compléter ou création

		if (!$new_id_lot_pdt_froid || intval($new_id_lot_pdt_froid) < 1) { exit('ZCSFUUPH'); }

		// Puis on retire ces quantités du FroidProduit initial
		$pdtFroid->setPoids($pdtFroid->getPoids() - $poids);
		$pdtFroid->setNb_colis($pdtFroid->getNb_colis() - $nb_colis);
		$pdtFroid->setUser_maj($utilisateur->getId());;
		$pdtFroid->setDate_maj(date('Y-m-d H:i:s'));
		if (!$froidManager->saveFroidProduit($pdtFroid)) { exit('EVDLLFPP'); }

		// Mise à jour de ou des compos du FroidProduit initial
        if ($pdtFroid->getId_compo() > 0) {

            // On récupère la composition du produit en attente
            $oldCompo = $palettesManager->getComposition($pdtFroid->getId_compo());
            if ($oldCompo instanceof PaletteComposition) {

                // On met à jour les quantités de la composition
                $oldCompo->setNb_colis($pdtFroid->getNb_colis());
                $oldCompo->setPoids($pdtFroid->getPoids());
                $oldCompo->setSupprime(0);
				if (!$palettesManager->savePaletteComposition($oldCompo)) { exit('GVIBSOPM'); }


                // On peut avoir plusieurs compo pour l'ancien produit sur lequel il en reste
                // On a mis à jour une des compos, ok... mais il faut alors supprimer toutes les autres compos liées à l'ancien id_lot_pdt_froid
				$palettesManager->supprComposFroidProduitExcept($id_lot_pdt_froid, $pdtFroid->getId_compo());
				$palettesManager->razPoidsColisCompoByPdtFroid($id_lot_pdt_froid);

				// On crée maintenant la compo du nouveau produit splité
                $newCompo = new PaletteComposition([]);
				$newCompo->setSupprime(0);
				$newCompo->setPoids($poids);
				$newCompo->setNb_colis($nb_colis);
				$newCompo->setId_lot_pdt_froid($new_id_lot_pdt_froid);
				$newCompo->setId_palette($oldCompo->getId_palette());
				$newCompo->setId_produit($oldCompo->getId_produit());
				$newCompo->setId_client($oldCompo->getId_client());
				$newCompo->setDate(date('Y-m-d H:i:s'));
				$newCompo->setId_user($utilisateur->getId());

				if (!$palettesManager->savePaletteComposition($newCompo)) { exit('FMISXXZB'); }

				// On met à jour le statut de la palette si complète
				$palettesManager->updStatutPalette($oldCompo->getId_palette());

            } // FIN test instanciation objet PaletteComposition

        } // FIN test composition du produit initial


    } // FIN test reprise complète ou partielle

    // Dans tous les cas... et si pas d'erreur...

    // ...on retirer le flag "supprimé" de la compo
    $compo = $palettesManager->getComposition($id_compo);

    // Si la composition n'as pas été supprimée pendant la création d'une compo partielle...
    if ($compo instanceof PaletteComposition) {
		$compo->setSupprime(0);

		// Si on a créé un autre produit froid dan une reprise partielle, on met à jour l'id_lot_pdt_froid dans la compo
		if (isset($new_id_lot_pdt_froid)) {
			$compo->setId_lot_pdt_froid($new_id_lot_pdt_froid);
		}

		if (!$palettesManager->savePaletteComposition($compo)) {
			exit('TAIMWTHR');
		}
    }

	// ...puis on purge les compos temporaires / supprimées
	$palettesManager->purgeComposSupprimeesByFroidProduit($id_lot_pdt_froid);

	echo $creationFroid ? $id_froid : '1';
	exit;

} // FIN mode