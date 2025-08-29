<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax PRODUITS
------------------------------------------------------*/

error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$produitsManager = new ProduitManager($cnx);
$logsManager     = new LogManager($cnx);
$especesManager  = new ProduitEspecesManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}



/* --------------------------------------
MODE - Modale Espèce Produit (admin)
--------------------------------------*/
function modeModalProduitEspece() {

	global
	$utilisateur,
	$especesManager;

	// On vérifie qu'on est bien loggé
	if (!isset($_SESSION['logged_user'])) { exit;}

	$espece_id     = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$espece        = $espece_id > 0 ? $especesManager->getProduitEspece($espece_id) : new ProduitEspece([]);

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) {
		$utilisateur = unserialize($_SESSION['logged_user']);
	}

	// Retour Titre
	echo '<i class="fa fa-horse"></i>';
	echo $espece_id > 0 ? $espece->getNom() : "Nouvelle espèce&hellip;";

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>

    <form class="container-fluid" id="formEspeceAddUpd">
        <input type="hidden" name="mode" value="saveEspece"/>
        <input type="hidden" name="espece_id" id="input_id" value="<?php echo $espece_id; ?>"/>
        <div class="row">
            <div class="col-7 input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">Libellé</span>
                </div>
                <input type="text" class="form-control" placeholder="Nom de l'espèce" name="nom" id="input_nom" value="<?php echo $espece->getNom(); ?>">
                <div class="invalid-feedback">Un nom est obligatoire.</div>
            </div>


            <div class="col-3 input-group mb-2 input-group" >
                <div class="input-group-prepend">
                    <span class="input-group-text">Couleur</span>
                </div>
                <input type="text" class="form-control" placeholder="Code couleur" name="couleur" id="input_couleur" value="<?php echo $espece->getCouleur(); ?>" />
            </div>



            <div class="col-2 mb-2 text-right">

                <input type="checkbox" class="togglemaster" data-toggle="toggle" name="activation"
					<?php echo $espece->isActif() ? 'checked' : ''; ?>
                       data-on="Activée"
                       data-off="Désactivée"
                       data-onstyle="success"
                       data-offstyle="danger"
                />
            </div>






        </div>
        <div class="row">
            <div class="col-12 mt-2">
                <div class="alert alert-danger">
                    <h2>ATTENTION</h2>
                    <p class="text-uppercase text-16 mb-0">Supprimer ou désactiver une espèce rendra la sélection des produits associés impossible !</p>
                    <p class="text-13">Veillez à toujours classer les produits en espèces distinctes <b>ABATS</b> et <b>VIANDES</b> pour le bon fonctionnement de l'application.</p>
                </div>
            </div>
        </div>

    </form>
    <div class="row mt-2">
        <div class="col doublon d-none">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle mr-1"></i> Ce nom d'espèce existe déjà !
            </div>
        </div>
    </div>



	<?php
	// Séparateur Body/Footer pour le callback aJax
	echo '^';

	// Retour boutons footer si produit existant (bouton supprimer)
	if ($espece_id > 0) {
		?>
        <button type="button" class="btn btn-danger btn-sm btnSupprimeEspece">
            <i class="fa fa-times fa-lg vmiddle mr-1"></i> Supprimer
        </button>
		<?php
	} // FIN test édition utilisateur existant
	exit;

} // FIN mode


/* --------------------------------------
MODE - Modale Espèce Catégorie (admin)
--------------------------------------*/
function modeModalProduitCategorie() {

	global $utilisateur, $cnx;

	$categoriesManager = new ProduitCategoriesManager($cnx);

	// On vérifie qu'on est bien loggé
	if (!isset($_SESSION['logged_user'])) { exit;}

	$categorie_id     = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$categorie        = $categorie_id > 0 ? $categoriesManager->getProduitCategorie($categorie_id) : new ProduitCategorie([]);

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) {
		$utilisateur = unserialize($_SESSION['logged_user']);
	}

	// Retour Titre
	echo '<i class="fa fa-folder-open"></i>';
	echo $categorie_id > 0 ? $categorie->getNom() : "Nouvelle catégorie de produits&hellip;";

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>

    <form class="container-fluid" id="formCategorieAddUpd">
        <input type="hidden" name="mode" value="saveCategorie"/>
        <input type="hidden" name="categorie_id" id="input_id" value="<?php echo $categorie_id; ?>"/>
        <div class="row">
            <div class="col-10 input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">Libellé</span>
                </div>
                <input type="text" class="form-control" placeholder="Nom de la catégorie" name="nom" id="input_nom" value="<?php echo $categorie->getNom(); ?>">
                <div class="invalid-feedback">Un nom est obligatoire.</div>
            </div>

            <div class="col-2 mb-2 text-right">

                <input type="checkbox" class="togglemaster" data-toggle="toggle" name="activation"
					<?php echo $categorie->isActif() ? 'checked' : ''; ?>
                       data-on="Activée"
                       data-off="Désactivée"
                       data-onstyle="success"
                       data-offstyle="danger"
                />
            </div>
        </div>
		<?php if ( $categorie_id > 0 ) { ?>
            <div class="row">
                <div class="col-12 mt-2">
                    <div class="alert alert-danger">
                        <h2>ATTENTION</h2>
                        <p class="text-uppercase text-16 mb-0">Supprimer ou désactiver une catégorie rendra la sélection des produits associés impossible pour l'étiquetage !</p>
                    </div>
                </div>
            </div>
		<?php } ?>
    </form>
    <div class="row mt-2">
        <div class="col doublon d-none">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle mr-1"></i> Ce nom de catégorie existe déjà !
            </div>
        </div>
    </div>



	<?php
	// Séparateur Body/Footer pour le callback aJax
	echo '^';

	// Retour boutons footer si produit existant (bouton supprimer)
	if ($categorie_id > 0) {
		?>
        <button type="button" class="btn btn-danger btn-sm btnSupprimeCategorie">
            <i class="fa fa-times fa-lg vmiddle mr-1"></i> Supprimer
        </button>
		<?php
	} // FIN test édition utilisateur existant
	exit;

} // FIN mode

/* ------------------------------------
MODE - Modale Produit (admin)
------------------------------------*/
function modeModalProduit() {

	global
	$cnx,
	$utilisateur,
	$produitsManager,
	$especesManager;

	$categoriesManager 	= new ProduitCategoriesManager($cnx);
	$languesManager 	= new LanguesManager($cnx);

	// On vérifie qu'on est bien loggé
	if (!isset($_SESSION['logged_user'])) { exit;}

	$produit_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$produit    = $produit_id > 0 ? $produitsManager->getProduit($produit_id) : new Produit([]);

	

	// Calcul des prochains EAN pour un nouveau produit
	if ($produit->getId() == 0) {
		$ean13 = $produitsManager->getNextEan13();
		$ean14 = '9' . substr($ean13,0,-1);
		$ean14 = $ean14.$produitsManager->getClefEan($ean14);
		$produit->setEan13($ean13);
		$produit->setEan14($ean14);
		$ean7poids = $produitsManager->getNextEan7();
		$produit->setEan7($ean7poids);
		$produit->setEan7_type(0);
		$ean7prix = $produitsManager->getNextEan7('prix');
	}

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) {
		$utilisateur = unserialize($_SESSION['logged_user']);
	}

	// Retour Titre
	echo '<i class="fa fa-dolly"></i>';
	echo $produit_id > 0 ? $produit->getNom() : "Nouveau produit&hellip;";

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>

    <form class="container-fluid" id="formProduitAddUpd">
        <input type="hidden" name="mode" value="saveProduit"/>
        <input type="hidden" name="produit_id" id="input_id" value="<?php echo $produit_id; ?>"/>

        <div class="row"><!-- Conteneur général du formulaire -->

            <div class="col-8"> <!-- Bloc de gauche -->
                <div class="row">
                    <div class="col-8">
					<?php
					$langues = $languesManager->getListeLangues(['actif' => 1]);
					foreach ($langues as $langue) { ?>



                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <img src="<?php echo $langue->getDrapeau(); ?>" class="mr-1"/> Libellé
                                </span>
                            </div>
                            <input type="text" class="form-control" placeholder="Nom du produit" name="noms[<?php echo $langue->getId(); ?>]"  value="<?php echo isset($produit->getNoms()[$langue->getId()]) ? $produit->getNoms()[$langue->getId()] : ''; ?>">
                        </div>


					<?php }
					?>
                    </div>

                    <div class="col-4">
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Code</span>
                            </div>
                            <input type="text" class="form-control" placeholder="Code interne" name="code" id="input_code" value="<?php echo $produit->getCode(); ?>">
                            <div class="invalid-feedback">Code unique obligatoire.</div>
                        </div>

                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Colis</span>
                            </div>
                            <input type="text" class="form-control" placeholder="Colis/palette" name="nb_colis" id="input_nb_colis" value="<?php echo $produit->getNb_colis(); ?>">
                        </div>
                    </div>

                </div>



                <div class="row mb-4">

                    <div class="col-8 mb-2 input-group ">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Nom court</span>
                        </div>
                        <input type="text" class="form-control" placeholder="Nom court" name="nom_court" maxlength="50" id="input_nom_court" value="<?php echo $produit->getNom_court(); ?>">
                    </div>




                    <div class="col-4 mb-2 input-group ">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Poids </span>
                        </div>
                        <input type="text" class="form-control" placeholder="Poids par défaut" name="poids" id="input_poids" value="<?php
							echo $produit->getVrai_poids();
						?>">
                        <div class="input-group-append">
                            <span class="input-group-text">Kg</span>
                        </div>
                    </div>

                </div>
                <div class="row mb-4">
                    <div class="col-2 mb-2 input-group">

                        <input type="checkbox" class="togglemaster" data-toggle="toggle" name="activation"
							<?php echo $produit->isActif() ? 'checked' : ''; ?>
                               data-on="Activé"
                               data-off="Désactivé"
                               data-onstyle="success"
                               data-offstyle="danger"
                        />
                    </div>
                    <div class="col-4 mb-2 input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-12">Palette suivante</span>
                        </div>
                        <input type="text" class="form-control text-center" readonly value="<?php echo $produit->getPalette_suiv(); ?>"/>
                        <div class="input-group-append">
                            <button class="btn btn-secondary <?php
							echo $produit->getPalette_suiv() == 1 ? 'disabled' : 'btnResetPaletteSuiv'; ?>" <?php echo $produit->getPalette_suiv() < 10 ? 'disabled' : ''; ?> type="button"><i class="fa fa-undo"></i></button>
                        </div>
                    </div>


                    <div class="col-3 mb-2 input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-12">Palette mixte</span>
                        </div>
                        <input type="checkbox" class="togglemaster form-control" data-toggle="toggle" name="mixte"
							<?php echo $produit->isMixte() ? 'checked' : ''; ?>
                               data-on="Oui"
                               data-off="Non"
                               data-onstyle="info"
                               data-offstyle="secondary"
                        />
                    </div>

                    <div class="col-3 mb-2 input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-12">DLC/DLUO</span>
                        </div>
                        <input type="text" class="form-control" placeholder="Jrs" name="nb_jours_dlc" id="input_nb_jours_dlc" value="<?php echo $produit->getNb_jours_dlc(); ?>">
                        <div class="input-group-append">
                            <span class="input-group-text text-12">j.</span>
                        </div>
                    </div>

                </div>
                <div class="row mb-2">

                    <div class="col-12 col-lg-6 input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Espèce</span>
                        </div>
                        <select class="selectpicker form-control show-tick" name="espece" id="input_espece" title="Sélectionnez l'espèce...">
							<?php
							foreach ($especesManager->getListeProduitEspeces(true) as $espece) { ?>

                                <option value="<?php echo $espece->getId();?>" <?php echo $produit->getId_espece() == $espece->getId() ? 'selected' : ''; ?>><?php echo $espece->getNom();?></option>

							<?php } // FIN boucle sur les espèces ?>
                        </select>
                        <input type="hidden" class="form-control" id="input_espece_feedback">
                        <div class="invalid-feedback">L'espèce est obligatoire.</div>


                    </div>

                    <div class="col-12 col-lg-6 input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Catégories</span>
                        </div>
                        <select class="selectpicker form-control show-tick" name="categories[]" id="input_categorie" title="Sélectionnez les catégories..." multiple data-selected-text-format="count > 1">
							<?php
							foreach ($categoriesManager->getListeProduitCategories() as $categorie) { ?>

                                <option value="<?php echo $categorie->getId();?>" <?php echo in_array($categorie->getId(), $produit->getCategories_ids())  ? 'selected' : ''; ?>><?php echo $categorie->getNom();?></option>

							<?php } // FIN boucle sur les catégories ?>
                        </select>
                    </div>

                </div>
                <div class="row mb-2">
                    <div class="col-12 col-lg-6 input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">EAN 13</span>
                        </div>
                        <input type="text" class="form-control" placeholder="Code EAN 13" name="ean13" id="input_ean13" value="<?php echo $produit->getEan13();
                        ?>" data-old="<?php echo $produit->getEan13(); ?>">
                        <div class="input-group-append">
                            <span class="input-group-text"><a href="http://www.gomaro.ch/lecheck.htm" target="_blank" class="gris-9"><i class="fa fa-key"></i></a></span>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">EAN 14</span>
                        </div>
                        <input type="text" class="form-control" placeholder="Code EAN 14" name="ean14" id="input_ean14" value="<?php echo $produit->getEan14();
                        ?>" data-old="<?php echo $produit->getEan14(); ?>">
                        <div class="input-group-append">
                            <span class="input-group-text"><a href="http://www.gomaro.ch/lecheck.htm" target="_blank" class="gris-9"><i class="fa fa-key"></i></a></span>
                        </div>

                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-6 input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">EAN 7</span>
                        </div>
                        <input type="text" class="form-control" placeholder="Code EAN 7" name="ean7" id="input_ean7" value="<?php echo $produit->getEan7(); ?>" data-ean7-poids="<?php
                        echo isset($ean7poids) ? $ean7poids : ''; ?>" data-ean7-prix="<?php
						echo isset($ean7prix) ? $ean7prix : ''; ?>" data-old="<?php echo $produit->getEan7(); ?>">
                    </div>
                    <div class="col-3 input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-12">Type EAN7</span>
                        </div>
                        <input type="checkbox" class="togglemaster form-control <?php echo isset($ean7poids) ? 'ean7type-npdt' : '';?>" data-toggle="toggle" name="ean7_type"
							<?php echo $produit->getEan7_type() == 1 ? 'checked' : ''; ?>
                               data-on="Prix"
                               data-off="Poids"
                               data-onstyle="secondary"
                               data-offstyle="secondary"
                        />
                    </div>

                    <div class="col-3 mb-2 input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-12">Vrac</span>
                        </div>
                        <input type="checkbox" class="togglemaster form-control" data-toggle="toggle" name="vrac"
							<?php echo $produit->isVrac() ? 'checked' : ''; ?>
                               data-on="Oui"
                               data-off="Non"
                               data-onstyle="info"
                               data-offstyle="secondary"
                        />
                    </div>
                </div>

                <div class="row mb-2">

                    <div class="col-6 input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Emballages</span>
                        </div>
                        <select class="selectpicker form-control show-tick" name="emballages[]" id="input_emballages" title="Sélectionnez les emballages..." multiple data-selected-text-format="count > 1" data-size="10" data-actions-box="true">
                            <?php
                            $consammablesManager = new ConsommablesManager($cnx);
							$liste_famsemb = $consammablesManager->getListeConsommablesFamilles(['id_type' => 1]); // Type 1 = emballages
							foreach ($liste_famsemb as $famemb) { ?>
                                <option value="<?php echo $famemb->getID(); ?>" <?php echo in_array($famemb->getId(), $produit->getIds_familles_emballages())  ? 'selected' : ''; ?>><?php echo $famemb->getNom(); ?></option>
							<?php }
                            ?>
                        </select>
                    </div>
                </div>

				<div class="row">

					<div class="col-12 mb-1 pt-2">
						<span class="gris-7"><i class="fa fa-eye mr-2 gris-9"></i> Vues associées :</span>
					</div>

					<div class="col-12">
						<?php
						$froidManager = new FroidManager($cnx);
						$froidTypes = $froidManager->getFroidTypes();
						foreach ($froidTypes as $froidtype) {

							if (strtolower($froidtype['nom']) == 'negoce') { continue; }
							?>

							<label class="mr-3 pointeur"><input type="checkbox" <?php

								echo is_array($produit->getFroids()) && in_array(intval($froidtype['id']), $produit->getFroids()) ? 'checked' : '';

								?> class="icheck" name="froids[]" value="<?php echo $froidtype['id']; ?>"> <?php echo $froidtype['nom'];?></label>&nbsp;

						<?php } // FIN boucle sur les froids
						?>

					</div>
				</div>
            </div>
            <div class="col-4"> <!-- Bloc de droite (Gescom) -->
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-secondary">
                            <div class="row mb-3">
                                <div class="col-lg-8 padding-top-5">
                                  Vendu à la pièce
                                </div>
                                <div class="col-lg-4 text-right">
                                    <input type="checkbox"
                                           class="togglemaster"
										<?php echo $produit->getVendu_piece() == 1 ? 'checked' : ''; ?>
                                           data-toggle="toggle"
                                           data-on="Oui"
                                           data-off="Non"
                                           data-onstyle="info"
                                           data-offstyle="secondary"
                                           name="vendu_piece"
                                    />
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-8 padding-top-5">
                                    Produit de gros <span class="texte-fin text-13">(Taxe Interbev)</span>
                                </div>
                                <div class="col-lg-4 text-right">
                                    <input type="checkbox"
                                           class="togglemaster"
										<?php echo $produit->getPdt_gros() == 1 ? 'checked' : ''; ?>
                                           data-toggle="toggle"
                                           data-on="Oui"
                                           data-off="Non"
                                           data-onstyle="info"
                                           data-offstyle="secondary"
                                           name="pdt_gros"
                                    />
                                </div>
                            </div>
							
							<div class="row mb-3">
                                <div class="col-lg-8 padding-top-5">
                                    Produit de negoce
                                </div>
                                <div class="col-lg-4 text-right">
                                    <input type="checkbox"
                                           class="togglemaster"
										<?php echo $produit->getVendu_negoce() == 1 ? 'checked' : ''; ?>
                                           data-toggle="toggle"
                                           data-on="Oui"
                                           data-off="Non"
                                           data-onstyle="info"
                                           data-offstyle="secondary"
                                           name="produit_negoce"
                                    />
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12 input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Nomenclature</span>
                                    </div>
                                    <input type="text" class="form-control" maxlength="8" name="nomenclature" placeholder="-" value="<?php echo $produit->getNomenclature() != '' ? $produit->getNomenclature() : ''; ?>" />
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12 input-group ">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Stock</span>
                                    </div>
                                    <select class="selectpicker form-control show-tick" name="id_client" title="Tous"  data-live-search="true" data-live-search-placeholder="Rechercher">
                                        <option value="0">&mdash; Tous &mdash;</option>
										<?php
										$tiersManager = new TiersManager($cnx);
										foreach ($tiersManager->getListeClients() as $clt) { ?>
                                            <option value="<?php echo $clt->getId();?>" <?php
											echo $produit->getId_client() > 0 && $clt->getId() == $produit->getId_client()  ? 'selected' : ''; ?>><?php echo $clt->getNom();?></option>
										<?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12 input-group ">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Taxe</span>
                                    </div>
                                    <select class="selectpicker form-control show-tick" name="id_taxe" title="Sélectionnez...">
                                        <?php
                                        $taxesManager = new TaxesManager($cnx);
                                        $taxes = $taxesManager->getListeTaxes();
                                        foreach ($taxes as $taxe) { ?>
                                            <option value="<?php echo $taxe->getId();?>" data-subtext="<?php echo $taxe->getTaux() ;?> %" <?php
												echo $produit->getId() > 0 && $taxe->getId() == $produit->getId_taxe()  ? 'selected' : ''; ?>><?php echo $taxe->getNom();?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12 input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Stats</span>
                                    </div>
                                    <select class="selectpicker form-control show-tick" name="stats" title="Sélectionnez...">
										<?php
										$stats = $produitsManager->getProduitStats();
										foreach ($stats as $stat) { ?>
                                            <option value="<?php echo $stat->getId();?>" <?php
											echo $produit->getId() > 0 && $stat->getId() == $produit->getStats()  ? 'selected' : ''; ?>><?php echo $stat->getNom();?></option>
										<?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12 input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Type d'emballage</span>
                                    </div>
                                    <select class="selectpicker form-control show-tick" name="id_pdt_emballage" title="Sélectionnez...">
										<?php
                                        $produitEmballageManager = new ProduitEmballageManager($cnx);
										$emballagesPdt = $produitEmballageManager->getListeProduitEmballages();
										foreach ($emballagesPdt as $embPdt) { ?>
                                            <option value="<?php echo $embPdt->getId();?>" <?php
											echo $produit->getId() > 0 && $embPdt->getId() == $produit->getId_pdt_emballage()  ? 'selected' : ''; ?>><?php echo $embPdt->getNom();?></option>
										<?php } ?>
                                    </select>
                                </div>
                            </div>



							<div class="row mb-3">
								<div class="col-12 input-group">
									<div class="input-group-prepend">
										<span class="input-group-text">PCB <span class="ml-1 texte-fin text-13">(Nombre de pièces par carton)</span></span>
									</div>
									<input type="text" class="form-control text-center" name="pcb" placeholder="-" value="<?php echo $produit->getPcb() > 0 ? $produit->getPcb() : ''; ?>" />
								</div>
							</div>
							<div class="row mb-3">
								<div class="col-12 input-group">
									<div class="input-group-prepend">
										<span class="input-group-text">Poids unitaire</span>
									</div>
									<input type="text" class="form-control text-right" name="poids_unitaire" placeholder="-" value="<?php echo $produit->getPoids_unitaire() > 0 ? $produit->getPoids_unitaire() : ''; ?>" />
									<div class="input-group-append">
										<span class="input-group-text">Kg</span>
									</div>
								</div>
								<div class="col-12 texte-fin text-12 gris-7 ml-1 mt-1">
									<i class="fa fa-info-circle mr-1"></i> Par exemple pour le steak haché.
								</div>
							</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>




    </form>
    <div class="row mt-2">
        <div class="col doublon d-none">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle mr-1"></i> Un produit existe déjà avec ces EAN !
            </div>
        </div>
    </div>

	<?php
	// Séparateur Body/Footer pour le callback aJax
	echo '^';

	// Retour boutons footer si produit existant (bouton supprimer)
	if ($produit_id > 0) {
		?>
        <button type="button" class="btn btn-danger btn-sm btnSupprimeProduit">
            <i class="fa fa-times fa-lg vmiddle mr-1"></i> Supprimer
        </button>
		<?php
	} // FIN test édition utilisateur existant
	exit;
} // FIN mode

/* -------------------------------------------------
MODE - Enregistre une espèce (add/upd)
--------------------------------------------------*/
function modeSaveEspece() {

	global
	$cnx,
	$especesManager;

	// Vérification des données
	$espece_id     = isset($_REQUEST['espece_id'])    ? intval($_REQUEST['espece_id'])        : 0;
	$nom           = isset($_REQUEST['nom'])          ? trim($_REQUEST['nom'])    : '';
	$couleur       = isset($_REQUEST['couleur'])      ? trim($_REQUEST['couleur'])    : '';
	$activation    = isset($_REQUEST['activation'])   ? 1 : 0;

	// Si pas de nom on ne vas pas plus loin...
	if ($nom == '') {
		echo '-1';
		exit;
	} // FIN test champs requis

	$nom = htmlspecialchars(str_replace('#et#', '&', $nom));

	// Instanciation de l'objet PRODUITESPECE (hydraté ou vide)

	$espece = $espece_id > 0 ? $especesManager->getProduitEspece($espece_id) : new ProduitEspece([]);

	// mise à jour des champs de base
	$espece->setNom($nom);
	$espece->setCouleur($couleur);
	$espece->setDate_maj(date('Y-m-d H:i:s'));
	$espece->setActif($activation);

	// Si création, on enregistre la date
	if ($espece_id == 0) {
		$espece->setDate_add(date('Y-m-d H:i:s'));
	}

	// Enregistrement et retour pour callBack ajax
	$retour = $especesManager->saveProduitEspeces($espece);

	// Logs
	$logsManager = new LogManager($cnx);
	$log = new Log([]);
	if ($retour) {
		$log->setLog_type('info');
		if ($espece_id == 0) {
			$log->setLog_texte("Création d'une nouvelle espèce : " . $nom);
		} else {
			$log->setLog_texte("Mise à jour des informations de l'espèce' #" . (int)$espece_id);
		}
	} else {
		$log->setLog_type('danger');
		if ($espece_id == 0) {
			$log->setLog_texte("ERREUR lors de la création de la nouvelle espèce : " . $nom);
		} else {
			$log->setLog_texte("ERREUR lors de la mise à jour des informations de l'espèce' #" . (int)$espece_id);
		}
	} // FIN test retour création/maj pour log

	$logsManager->saveLog($log);

	echo $retour !== false ? '1' : '0';
	exit;

} // FIN mode


/* -------------------------------------------------
MODE - Enregistre une catégorie (add/upd)
--------------------------------------------------*/
function modeSaveCategorie() {

	global $cnx;

	$categorieManager = new ProduitCategoriesManager($cnx);

	// Vérification des données
	$categorie_id   = isset($_REQUEST['categorie_id'])  ? intval($_REQUEST['categorie_id'])     : 0;
	$nom           = isset($_REQUEST['nom'])            ? trim($_REQUEST['nom'])    : '';
	$activation    = isset($_REQUEST['activation'])     ? 1 : 0;

	// Si pas de nom on ne vas pas plus loin...
	if ($nom == '') {
		echo '-1';
		exit;
	} // FIN test champs requis

	$nom = htmlspecialchars(str_replace('#et#', '&', $nom));

	// Instanciation de l'objet PRODUITECATEGORIE (hydraté ou vide)

	$categorie = $categorie_id > 0 ? $categorieManager->getProduitCategorie($categorie_id) : new ProduitCategorie([]);

	// mise à jour des champs de base
	$categorie->setNom($nom);
	$categorie->setDate_maj(date('Y-m-d H:i:s'));
	$categorie->setActif($activation);

	// Si création, on enregistre la date
	if ($categorie_id == 0) {
		$categorie->setDate_add(date('Y-m-d H:i:s'));
	}

	// Enregistrement et retour pour callBack ajax
	$retour = $categorieManager->saveProduitCategorie($categorie);

	// Logs
	$logsManager = new LogManager($cnx);
	$log = new Log([]);
	if ($retour) {
		$log->setLog_type('info');
		if ($categorie_id == 0) {
			$log->setLog_texte("Création d'une nouvelle catégorie de produits : " . $nom);
		} else {
			$log->setLog_texte("Mise à jour des informations de la catégorie de produits ' #" . (int)$categorie_id);
		}
	} else {
		$log->setLog_type('danger');
		if ($categorie_id == 0) {
			$log->setLog_texte("ERREUR lors de la création de la nouvelle catégorie de produits : " . $nom);
		} else {
			$log->setLog_texte("ERREUR lors de la mise à jour des informations de la catégorie de produits' #" . (int)$categorie_id);
		}
	} // FIN test retour création/maj pour log

	$logsManager->saveLog($log);

	echo $retour !== false ? '1' : '0';
	exit;

} // FIN mode


/* ------------------------------------
MODE - Enregistre un produit (add/upd)
------------------------------------*/
function modeSaveProduit() {

	global
	$cnx,
	$produitsManager;

	// Vérification des données
	$produit_id     = isset($_REQUEST['produit_id'])    ? intval($_REQUEST['produit_id'])       : 0;
	$nom_court      = isset($_REQUEST['nom_court'])     ? strtoupper(trim($_REQUEST['nom_court']))    : '';
	$code           = isset($_REQUEST['code'])          ? strtoupper(trim($_REQUEST['code']))   : '';
	$id_espece      = isset($_REQUEST['espece'])        ? intval($_REQUEST['espece'])           : 0;
	$ids_categories = isset($_REQUEST['categories'])  ? $_REQUEST['categories']               : [];
	$ids_emballages = isset($_REQUEST['emballages'])  ? $_REQUEST['emballages']               : [];
	$poids          = isset($_REQUEST['poids'])         ? floatval($_REQUEST['poids'])          : 0;
	$nb_colis       = isset($_REQUEST['nb_colis'])      ? intval($_REQUEST['nb_colis'])         : 0;
	$nb_jours_dlc   = isset($_REQUEST['nb_jours_dlc'])  ? intval($_REQUEST['nb_jours_dlc'])     : -1;
	$froids         = isset($_REQUEST['froids'])        ? $_REQUEST['froids']                   : [];
	$id_client      = isset($_REQUEST['id_client'])     ? intval($_REQUEST['id_client'])        : 0;
	$ean13          = isset($_REQUEST['ean13'])         ? preg_replace("/[^0-9]/", "",$_REQUEST['ean13'])  : '';
	$ean14          = isset($_REQUEST['ean14'])         ? preg_replace("/[^0-9]/", "",$_REQUEST['ean14'])  : '';
	$nomenclature   = isset($_REQUEST['nomenclature'])  ? preg_replace("/[^0-9]/", "",$_REQUEST['nomenclature'])  : '';
	$ean7           = isset($_REQUEST['ean7'])          ? trim($_REQUEST['ean7'])  : '';
	$ean7_type      = isset($_REQUEST['ean7_type']) 	? 1 : 0;
	$vrac           = isset($_REQUEST['vrac'])      	? 1 : 0;
	$vendu_piece    = isset($_REQUEST['vendu_piece'])  	? 1 : 0;
	$produit_negoce = isset($_REQUEST['produit_negoce']) ? 1 : 0 ;	
	// Gescom
	$pdt_gros 			= isset($_REQUEST['pdt_gros']) 			? 1 : 0;
	$id_taxe 			= isset($_REQUEST['id_taxe']) 			? intval($_REQUEST['id_taxe']) 						: 0;
	$stats 		= isset($_REQUEST['stats']) 		? intval($_REQUEST['stats']) 				: 0;
	$id_pdt_emballage 	= isset($_REQUEST['id_pdt_emballage']) 	? intval($_REQUEST['id_pdt_emballage']) 			: 0;
	$pcb 				= isset($_REQUEST['pcb']) 				? intval($_REQUEST['pcb']) 							: 1;
	$poids_unitaire 	= isset($_REQUEST['poids_unitaire']) 	? floatval($_REQUEST['poids_unitaire']) 			: 0;

	$activation     = isset($_REQUEST['activation'])   ? 1 : 0;
	$mixte          = isset($_REQUEST['mixte'])   ? 1 : 0;
	
	// Si pas de code ou de nom, on ne vas pas plus loin...
	if ($code == '') {
		echo 'code vide -1';
		exit;
	} // FIN test champs requis


	// On vérifie que les eans n'existe pas déjà
	if ($code !== '' && $produitsManager->checkExisteDeja($produit_id, $ean7, $ean13, $ean14)) {
		echo 'déjà existe -1';
		exit;
	} // FIN contrôle existe déjà

	// Instanciation de l'objet PRODUIT (hydraté ou vide)
	$produit = $produit_id > 0 ? $produitsManager->getProduit($produit_id) : new Produit([]);

	// mise à jour des champs de base
	$produit->setNom_court($nom_court);
	$produit->setCode($code);
	$produit->setId_espece($id_espece);
	$produit->setEan13($ean13);
	$produit->setEan14($ean14);
	$produit->setDate_maj(date('Y-m-d H:i:s'));
	$produit->setActif($activation);
	$produit->setPoids($poids);
	$produit->setNb_colis($nb_colis);
	$produit->setMixte($mixte);
	$produit->setEan7($ean7);
	$produit->setEan7_type($ean7_type);
	$produit->setVrac($vrac);
	$produit->setId_client($id_client);
	$produit->setVendu_piece($vendu_piece);
	$produit->setPdt_gros($pdt_gros);
	$produit->setId_taxe($id_taxe);
	$produit->setStats($stats);
	$produit->setId_pdt_emballage($id_pdt_emballage);
	$produit->setPcb($pcb);
	$produit->setPoids_unitaire($poids_unitaire);
	$produit->setNomenclature($nomenclature);
	$produit->setVendu_negoce($produit_negoce);

	if ($nb_jours_dlc > -1) {
		$produit->setNb_jours_dlc($nb_jours_dlc);

	}


	// Si création, on enregistre la date
	if ($produit_id == 0) {
		$produit->setDate_add(date('Y-m-d H:i:s'));
	}

	// Enregistrement et retour pour callBack ajax
	$retour = $produitsManager->saveProduit($produit);

	if (is_numeric($retour) && (int)$produit->getId() == 0) {
		$produit->setId($retour);
	}

	// On récupère toutes les langues actives pour récupérer les trads
	$languesManager = new LanguesManager($cnx);
	$langues = $languesManager->getListeLangues(['actif' => 1]);

	foreach ($langues as $langue) {

		if (!isset($_REQUEST['noms'][$langue->getId()])) { continue; }
		$trad = trim($_REQUEST['noms'][$langue->getId()]);

		$produitsManager->saveTradProduit($produit, $langue->getId(), $trad);

	}

	// On enregistre les catégories
	$categoriesManager = new ProduitCategoriesManager($cnx);
	$categoriesManager->liaisonProduitCategoriePdt($produit->getId(), $ids_categories);

	// Puis les familles d'emballages associées
    $produitsManager->liaisonProduitFamillesEmballages($produit->getId(), $ids_emballages);


	// Enregistrement des types de froids associés
	$produitsManager->saveFroidsProduit($produit, $froids);

	// Logs
	$logsManager = new LogManager($cnx);
	$log = new Log([]);
	if ($retour) {
		$log->setLog_type('info');
		if ($produit_id == 0) {
			$log->setLog_texte("Création d'un nouveu produit : " . $nom);
		} else {
			$log->setLog_texte("Mise à jour des informations du produit #" . (int)$produit_id);
		}
	} else {
		$log->setLog_type('danger');
		if ($produit_id == 0) {
			$log->setLog_texte("ERREUR lors de la création d'un nouveu produit : " . $nom);
		} else {
			$log->setLog_texte("ERREUR lors de la mise à jour des informations du produit #" . (int)$produit_id);
		}
	} // FIN test retour création/maj pour log

	$logsManager->saveLog($log);

	echo $retour !== false ? '1' : '0';
	exit;
} // FIN mode

/* ----------------------------------------------
MODE - Affiche la liste des espèces
-----------------------------------------------*/
function modeShowListeProduitsEspeces() {

	global
	$utilisateur,
	$especesManager;

	$listeEspeces = $especesManager->getListeProduitEspeces(true, false);

	// Si aucune espèce a afficher
	if (empty($listeEspeces)) { ?>

        <div class="alert alert-danger">
            <i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucune espèce !</strong>
        </div>

		<?php

		// Sinon, affichage de la liste des espèces
	} else { ?>

        <div class="alert alert-danger d-md-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-md-table">
            <thead>
            <tr>
				<?php
				// On affiche l'ID que si on est développeur
				if ($utilisateur->isDev()) { ?><th class="w-court-admin-cell d-none d-xl-table-cell">ID</th><?php } ?>

                <th class="w-mini-admin-cell text-center">Couleur</th>
                <th>Nom</th>
                <th>Nombre de produits</th>
                <th class="text-center w-court-admin-cell">Actif</th>
                <th class="t-actions w-court-admin-cell">Détails</th>
            </tr>
            </thead>
            <tbody>
			<?php
			// Boucle sur les espèces de produits
			foreach ($listeEspeces as $espece) {
				?>
                <tr>
					<?php
					// On affiche l'ID que si on est développeur
					if ($utilisateur->isDev()) { ?>
                        <td class="w-court-admin-cell d-none d-xl-table-cell"><span class="badge badge-pill badge-warning"><?php echo $espece->getId();?></span></td>
					<?php } ?>
                    <td class="text-center"><i class="fa fa-square fa-lg" style="color:<?php echo $espece->getCouleur(); ?>"></i></td>
                    <td class="text-18"><?php echo $espece->getNom();?></td>
                    <td><span class="badge badge-secondary badge-pill text-18"><?php echo $especesManager->getNbProduits($espece) ;?></span></td>
                    <td class="text-center w-court-admin-cell"><i class="fa fa-fw fa-lg fa-<?php echo $espece->isActif() ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i></td>
                    <td class="t-actions w-court-admin-cell"><button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalProduitEspece" data-espece-id="<?php
						echo $espece->getId(); ?>"><i class="fa fa-edit"></i> </button></td>
                </tr>
				<?php
			} // FIN boucle produits ?>
            </tbody>
        </table>
	<?php } // FIN test produits à afficher
	exit;

} // FIN mode

/* ----------------------------------------------
MODE - Affiche la liste des catégories
-----------------------------------------------*/
function modeShowListeProduitsCategories() {

	global $utilisateur, $cnx;

	$categoriesManager = new ProduitCategoriesManager($cnx);

	$listeCategories = $categoriesManager->getListeProduitCategories(true, false);

	// Si aucune categorie a afficher
	if (empty($listeCategories)) { ?>

        <div class="alert alert-danger">
            <i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucune catégorie de produit !</strong>
        </div>

		<?php

		// Sinon, affichage de la liste des catégories
	} else { ?>

        <div class="alert alert-danger d-md-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-md-table">
            <thead>
            <tr>
				<?php
				// On affiche l'ID que si on est développeur
				if ($utilisateur->isDev()) { ?><th class="w-court-admin-cell d-none d-xl-table-cell">ID</th><?php } ?>

                <th>Nom</th>
                <th>Nombre de produits</th>
                <th class="text-center w-court-admin-cell">Actif</th>
                <th class="t-actions w-court-admin-cell">Détails</th>
            </tr>
            </thead>
            <tbody>
			<?php
			// Boucle sur les catégories de produits
			foreach ($listeCategories as $categorie) {
				?>
                <tr>
					<?php
					// On affiche l'ID que si on est développeur
					if ($utilisateur->isDev()) { ?>
                        <td class="w-court-admin-cell d-none d-xl-table-cell"><span class="badge badge-pill badge-warning"><?php echo $categorie->getId();?></span></td>
					<?php } ?>
                    <td class="text-18"><?php echo $categorie->getNom();?></td>
                    <td><span class="badge badge-secondary badge-pill text-18"><?php echo $categoriesManager->getNbProduits($categorie) ;?></span></td>
                    <td class="text-center w-court-admin-cell"><i class="fa fa-fw fa-lg fa-<?php echo $categorie->isActif() ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i></td>
                    <td class="t-actions w-court-admin-cell"><button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalProduitCategorie" data-categorie-id="<?php
						echo $categorie->getId(); ?>"><i class="fa fa-edit"></i> </button></td>
                </tr>
				<?php
			} // FIN boucle catégories ?>
            </tbody>
        </table>
	<?php } // FIN test catégories à afficher
	exit;

} // FIN mode

/* ------------------------------------
MODE - Affiche la liste des produits
------------------------------------*/
function modeShowListeProduits() {

	global
        $cnx,
        $mode,
        $utilisateur,
        $produitsManager;

	// Préparation pagination (Ajax)
	$nbResultPpage      = 12;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode='.$mode;
	$start              = ($page-1) * $nbResultPpage;

	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;
	$params['show_inactifs']    = true;


	// Gestion des filtres (recherche)
	$filtre_nom         =  isset($_REQUEST['filtre_nom'])        ? trim($_REQUEST['filtre_nom'])            : '';
	$filtre_nom_court   =  isset($_REQUEST['filtre_nom_court'])  ? trim($_REQUEST['filtre_nom_court'])      : '';
	$filtre_vues        =  isset($_REQUEST['filtre_vues'])       ? intval($_REQUEST['filtre_vues'])         : 0;
	$filtre_espece      =  isset($_REQUEST['filtre_espece'])     ? intval($_REQUEST['filtre_espece'])       : 0;
	$filtre_categorie   =  isset($_REQUEST['filtre_categorie'])  ? intval($_REQUEST['filtre_categorie'])    : 0;
	$filtre_activation  =  isset($_REQUEST['filtre_actif'])      ? intval($_REQUEST['filtre_actif'])        : 0;

	$orderby_champ = isset($_REQUEST['orderby_champ']) ? $_REQUEST['orderby_champ'] : 'nom';
	$orderby_sens = isset($_REQUEST['orderby_sens']) ? $_REQUEST['orderby_sens'] : 'ASC';

	$params['orderby_champ']    = $orderby_champ;
	$params['orderby_sens']    = $orderby_sens;
	$filtresPagination.='&orderby_champ='.$orderby_champ;
	$filtresPagination.='&orderby_sens='.$orderby_sens;

	if ($filtre_activation == 1 || $filtre_activation == -1) { $params['actif'] = $filtre_activation; $filtresPagination.='&filtre_actif='.$filtre_activation;}
	if ($filtre_espece     > 0  ) { $params['id_espece']    = $filtre_espece;   $filtresPagination.='&filtre_espece='.$filtre_espece;           }
	if ($filtre_categorie  > 0  ) { $params['id_categorie'] = $filtre_categorie;$filtresPagination.='&filtre_categorie='.$filtre_categorie;     }
	if ($filtre_vues       != 0  ) { $params['vue']         = $filtre_vues;     $filtresPagination.='&filtre_vues='.$filtre_vues;               }
	if ($filtre_nom        != '') { $params['nom']          = $filtre_nom;      $filtresPagination.='&filtre_nom='.$filtre_nom;                 }
	if ($filtre_nom_court  != '') { $params['nom_court']    = $filtre_nom_court;$filtresPagination.='&filtre_nom_court='.$filtre_nom_court;     }

	// On ne gère pas la pagination si on a un filtre par catégorie
	if ($filtre_categorie > 0) {
		$page = 1;
		$nbResultPpage = 10000;
		$params['nb_result_page'] = $nbResultPpage;
		$params['start'] 			= $page;
	}

	$listeProduits = $produitsManager->getListeProduits($params);


	// Si aucun produit a afficher
	if (empty($listeProduits)) { ?>

        <div class="alert alert-danger">
            <i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucun produit !</strong>
        </div>

		<?php

		// Sinon, affichage de la liste des produits
	} else {

		// Liste non vide, construction de la pagination...
		$nbResults  = $produitsManager->getNb_results();
		$pagination = new Pagination($page);

		$pagination->setUrl($filtresPagination);
		$pagination->setNb_results($nbResults);
		$pagination->setAjax_function(true);
		$pagination->setNb_results_page($nbResultPpage);
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);
		?>

        <div class="alert alert-danger d-md-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-md-table">
            <thead>
            <tr>
				<?php
				// On affiche l'ID que si on est développeur
				if ($utilisateur->isDev()) { ?><th class="w-mini-admin-cell d-none d-xl-table-cell">ID</th><?php } ?>

                <th class="orderby pointeur w-mini-admin-cell d-none d-lg-table-cell" data-champ="p.`code`">Code <i class="ml-1 fa fa-sort gris-9"></i></th>
                <th class="orderby pointeur" data-champ="t.`nom`">Nom <i class="ml-1 fa fa-sort gris-9"></i></th>
                <th>Nom court</th>
                <th class="w-court-admin-cell">Espèce</th>
                <th class="w-court-admin-cell text-center">Catégories</th>
                <th class="text-center d-none d-xl-table-cell nowrap">Palette suiv.</th>
                <th class="text-center d-none d-xl-table-cell">Poids par défaut</th>
                <th class="text-center w-mini-admin-cell">Actif</th>
                <th class="t-actions w-mini-admin-cell">Détails</th>
            </tr>
            </thead>
            <tbody>
			<?php

			// Boucle sur les produits
			foreach ($listeProduits as $produit) {
				?>
                <tr>
					<?php
					// On affiche l'ID que si on est développeur
					if ($utilisateur->isDev()) { ?>
                        <td class="w-mini-admin-cell d-none d-xl-table-cell"><span class="badge badge-pill badge-warning"><?php echo $produit->getId();?></span></td>
					<?php } ?>
                    <td class="w-mini-admin-cell d-none d-lg-table-cell"><code class="text-14"><?php echo $produit->getCode();?></code></td>
                    <td class="text-18"><?php echo $produit->getNom();?></td>
                    <td><?php echo $produit->getNom_court();?></td>
                    <td><?php echo $produit->getNom_espece(); ?></td>
                    <td class="text-center"><?php

						if (empty($produit->getCategories())) { echo '&mdash;'; }
						$i = 0;
						foreach ($produit->getCategories() as $pdt_cates) {
							$i++;
							$mb1 = $i < count($produit->getCategories()) ? 'mb-1' : '';
							?>
                            <span class="badge badge-secondary text-14 form-control <?php echo $mb1; ?>"><?php echo $pdt_cates->getNom(); ?></span>
						<?php } ?></td>
                    <td class="text-center d-none d-xl-table-cell"><?php
                        echo $produit->getPalette_suiv();
					/*	if ($produit->getEan13() != '') {
							*/?><!--<code class="text-dark nowrap"><?php /*echo $produit->getEan13();*/?></code><?php
/*						} else { */?>
                            <span class="fa-stack fa-2x">
                                  <i class="fas fa-barcode fa-stack-1x"></i>
                                  <i class="fas fa-ban fa-stack-2x" style="color:Tomato"></i>
                                </span>
						--><?php /*}*/
						?></td>
                    <td class="text-center w-court-admin-cell"><?php
						if ($produit->getPoids() > 0) {
							echo $produit->getPoids();
						} else {
							$configManager = new ConfigManager($cnx);
							$config_poids_defaut = $configManager->getConfig('poids_defaut');
							if ($config_poids_defaut instanceof Config) {
								echo $config_poids_defaut->getValeur();
							}
						} ?> kg</td>
                    <td class="text-center w-mini-admin-cell"><i class="fa fa-fw fa-lg fa-<?php echo $produit->isActif() ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i></td>
                    <td class="t-actions w-mini-admin-cell">
                        <?php
                        if ($produit->getNom_court() == 'PDTWEB') { ?>
                            <button type="button" class="btn btn-sm btn-secondary disabled" disabled data-produit-id="<?php
							echo $produit->getId(); ?>"><i class="fa fa-ban"></i> </button>
						<?php } else { ?>
                            <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalProduit" data-produit-id="<?php
							echo $produit->getId(); ?>"><i class="fa fa-edit"></i> </button>
						<?php }
                        ?>

                    </td>
                </tr>
				<?php
			} // FIN boucle produits ?>
            </tbody>
        </table>
		<?php

		// Pagination (aJax)
		if (isset($pagination) && $filtre_categorie == 0) {
			// Pagination bas de page, verbose...
			$pagination->setVerbose_pagination(1);
			$pagination->setVerbose_position('right');
			$pagination->setNature_resultats('produit');
			$pagination->setNb_apres(2);
			$pagination->setNb_avant(2);

			echo ($pagination->getPaginationHtml());
		} // FIN test pagination

	} // FIN test produits à afficher
	exit;
} // FIN mode

/* ---------------------------------------
MODE - Supprime une espèce
---------------------------------------*/
function modeSupprProduitEspece() {

	global
	$especesManager,
	$logsManager;

	//  -> On ne supprime pas réellement pour les raisons de traçabilité, on passe le champ "supprime" à 1

	// On récupère l'ID de l'espèce, si il n'est pas clairement identifié, on ne va pas plus loin
	$id_espece = isset($_REQUEST['id_espece']) ? intval($_REQUEST['id_espece']) : 0;
	if ($id_espece == 0) { exit; }

	// Instanciation de l'objet ProduitEspece
	$espece = $especesManager->getProduitEspece($id_espece);

	// Si on a pas un objet ProduitEspece en retour, on ne va pas plus loin !
	if (!$espece instanceof ProduitEspece) { exit; }

	// On passe le statut à supprimé
	$espece->setSupprime(1);

	// On désactive
	$espece->setActif(0);

	// On enregistre la date de modification
	$espece->setDate_maj(date('Y-m-d H:i:s'));

	// Si la mise à jour s'est bien passé en BDD
	if ($especesManager->saveProduitEspeces($espece)) {

		// On retire le lien sur les produits liés :
		$nbRazPdt = intval($especesManager->razProduitsEspece($id_espece));
		$pluriel = $nbRazPdt > 1 ? 's' : '';

		// On sauvegarde dans les LOGS
		$log = new Log([]);
		$log->setLog_texte("Suppression d'une espèce (champ 'supprime' à 1) : ID #" . $id_espece . " [" . $nbRazPdt . " produit".$pluriel." affecté".$pluriel."]");
		$log->setLog_type('warning');
		$logsManager->saveLog($log);

	} // FIN test suppression OK pour Log
	exit;

} // FIN mode


/* ---------------------------------------
MODE - Supprime une catégorie de produits
---------------------------------------*/
function modeSupprProduitCategorie() {

	global $cnx, $logsManager;

	$categoriesManager = new ProduitCategoriesManager($cnx);

	//  -> On ne supprime pas réellement pour les raisons de traçabilité, on passe le champ "supprime" à 1

	// On récupère l'ID de la catégorie, si il n'est pas clairement identifié, on ne va pas plus loin
	$id_categorie = isset($_REQUEST['id_categorie']) ? intval($_REQUEST['id_categorie']) : 0;
	if ($id_categorie == 0) { exit; }

	// Instanciation de l'objet ProduitEspece
	$categorie = $categoriesManager->getProduitCategorie($id_categorie);

	// Si on a pas un objet ProduitCategorie en retour, on ne va pas plus loin !
	if (!$categorie instanceof ProduitCategorie) { exit; }

	// On passe le statut à supprimé
	$categorie->setSupprime(1);

	// On désactive
	$categorie->setActif(0);

	// On enregistre la date de modification
	$categorie->setDate_maj(date('Y-m-d H:i:s'));

	// Si la mise à jour s'est bien passé en BDD
	if ($categoriesManager->saveProduitCategorie($categorie)) {

		// On retire le lien avec les produits liés :
		$nbRazPdt = $categoriesManager->getNbProduits($id_categorie);
		$categoriesManager->razProduitsCategories($id_categorie);
		$pluriel = $nbRazPdt > 1 ? 's' : '';

		// On sauvegarde dans les LOGS
		$log = new Log([]);
		$log->setLog_texte("Suppression d'une catégorie de produit (champ 'supprime' à 1) : ID #" . $id_categorie . " [" . $nbRazPdt . " produit".$pluriel." affecté".$pluriel."]");
		$log->setLog_type('warning');
		$logsManager->saveLog($log);

	} // FIN test suppression OK pour Log
	exit;

} // FIN mode

/* ---------------------------------------
MODE - Supprime un produit
---------------------------------------*/
function modeSupprProduit() {

	global
	$produitsManager,
	$logsManager;

	//  -> On ne supprime pas réellement pour les raisons de traçabilité, on passe le champ "supprime" à 1

	// On récupère l'ID de l'produit, si il n'est pas clairement identifié, on ne va pas plus loin
	$id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit']) : 0;
	if ($id_produit == 0) { exit; }

	// Instanciation de l'objet Produit
	$produit = $produitsManager->getProduit($id_produit);

	// On passe le statut à supprimé
	$produit->setSupprime(1);

	// On désactive
	$produit->setActif(0);

	// On enregistre la date de modification
	$produit->setDate_maj(date('Y-m-d H:i:s'));

	// Si la mise à jour s'est bien passé en BDD
	if ($produitsManager->saveProduit($produit)) {

		// On sauvegarde dans les LOGS
		$log = new Log([]);
		$log->setLog_texte("Suppression d'un produit (champ 'supprime' à 1) : ID #" . $id_produit);
		$log->setLog_type('warning');
		$logsManager->saveLog($log);

	} // FIN test suppression OK pour Log
	exit;
} // FIN mode


/* -------------------------------------------------------------------
MODE - Vérifie si une espèce existe déjà (admin/modale)
--------------------------------------------------------------------*/
function modeCheckEspeceExisteDeja() {

	global
	$especesManager;

	$id    = isset($_REQUEST['id'])       ? (int)$_REQUEST['id']     : 0;
	$nom   = isset($_REQUEST['nom'])      ? trim($_REQUEST['nom'])   : '';

	echo $especesManager->checkExisteDeja($nom, $id) ? 1 : 0;

} // FIN mode

/* -----------------------------------------------------
MODE - Vérifie si un produit existe déjà (admin/modale)
------------------------------------------------------*/
function modeCheckExisteDeja() {

	global
	$produitsManager;

	$id     = isset($_REQUEST['id'])        ? (int)$_REQUEST['id']      : 0;
	$ean7   = isset($_REQUEST['ean7'])      ? trim($_REQUEST['ean7'])   : '';
	$ean13   = isset($_REQUEST['ean13'])      ? trim($_REQUEST['ean13'])   : '';
	$ean14   = isset($_REQUEST['ean14'])      ? trim($_REQUEST['ean14'])   : '';

	echo $produitsManager->checkExisteDeja($id, $ean7, $ean13, $ean14) ? 1 : 0;

} // FIN mode

/* -----------------------------------------------------
MODE - Liste des produits froid du jour (admin)
------------------------------------------------------*/
function modeListeProduitsFroidJour() {
	global $cnx, $utilisateur;
	$type           = isset($_REQUEST['type'])          ? trim(strtolower($_REQUEST['type']))               : '';
	$entree_debut   = isset($_REQUEST['entree_debut'])  ? Outils::dateFrToSql($_REQUEST['entree_debut'])    : '';
	$entree_fin     = isset($_REQUEST['entree_fin'])    ? Outils::dateFrToSql($_REQUEST['entree_fin'])      : '';
	$sortie_debut   = isset($_REQUEST['sortie_debut'])  ? Outils::dateFrToSql($_REQUEST['sortie_debut'])    : '';
	$sortie_fin     = isset($_REQUEST['sortie_fin'])    ? Outils::dateFrToSql($_REQUEST['sortie_fin'])      : '';
	$froidManager = new FroidManager($cnx);
	// On récupère la liste des blocages en cours
	$params = [
		'date_entree_debut'     => $entree_debut,
		'date_entree_fin'       => $entree_fin,
		'date_sortie_debut'     => $sortie_debut,
		'date_sortie_fin'       => $sortie_fin,
		'type'                  => $type
	];
	$listeFroidProduits = $froidManager->getProduitsFroidJour($params);
	// Si aucun produit correspondant au filtre
	if (empty($listeFroidProduits)) { ?>
        <div class="alert alert-secondary text-center padding-top-50 padding-bottom-50">
            <i class="fa fa-info-circle text-50 vmiddle mb-1"></i>
            <h3>Aucun produit correspondant aux critères demandés...</h3>
        </div>
		<?php
		// Des produits ont été trouvés
	} else { ?>
        <table class="admin w-100 d-none d-md-table">
            <thead>
            <tr>
				<?php
				// On affiche l'ID que si on est développeur
				if ($utilisateur->isDev()) { ?>
                    <th class="w-court-admin-cell d-none d-xl-table-cell"><kbd class="text-success">id_pdt</kbd></th>
                    <th class="w-court-admin-cell d-none d-xl-table-cell"><kbd class="text-success">id_lot_pdt_froid</kbd></th>
				<?php } ?>
                <th>Traitement</th>
                <th>Code</th>
                <th>Nom</th>
                <th>Lot</th>
                <th class="text-center">Palette</th>
                <th class="text-center">Nb <?php echo $type == 'srgv' ? 'blocs' : 'colis'; ?></th>
                <th class="text-right">Poids</th>
                <th>Conformité</th>
                <th class="text-center">Statut</th>
                <th class="text-center">T° début</th>
                <th class="text-center">T° fin</th>
                <th>Entrée</th>
                <th>Sortie</th>
            </tr>
            </thead>
            <tbody>
			<?php
			// Boucle sur les espèces
			$statuts = [
				0 => 'En cours',
				1 => 'Bloqué',
				2 => 'Terminé'
			];
			foreach ($listeFroidProduits as $pdtFroid) {

				$produit    = $pdtFroid->getProduit();
				$froid      = $pdtFroid->getFroid();

				// Debug perte de l'info en PROD !!!!?!???? O_o
				$tempFin =  $froid->getTemp_fin();

				if (!$produit instanceof Produit) { $produit = new Produit([]); }
				if (!$froid   instanceof Froid)   { $froid   = new Froid([]);   }
				?>
                <tr>
					<?php
					// On affiche l'ID que si on est développeur
					if ($utilisateur->isDev()) { ?>
                        <td class="w-court-admin-cell d-none d-xl-table-cell"><span class="badge-warning badge-pill text-12"><?php echo $pdtFroid->getId_pdt(); ?></span></td>
                        <td class="w-court-admin-cell d-none d-xl-table-cell"><span class="badge-warning badge-pill text-12"><?php echo $pdtFroid->getId_lot_pdt_froid(); ?></span></td>
					<?php }	?>
                    <td><?php echo strtoupper($type).sprintf("%04d", $pdtFroid->getId_froid()); ?></td>
                    <td><?php echo $produit->getCode();?></td>
                    <td><?php echo $produit->getNom();?></td>
                    <td><?php echo $pdtFroid->getNumlot() .  $pdtFroid->getQuantieme();?></td>
                    <td class="text-center"><?php echo $pdtFroid->getId_palette() > 0 ?  $pdtFroid->getId_palette() : '&mdash;';?></td>
                    <td class="text-center"><?php echo $pdtFroid->getNb_colis();?></td>
                    <td class="text-right"><?php echo number_format($pdtFroid->getPoids(),3, '.', '');?> kgs</td>
                    <td><?php
						if ($froid->getConformite() < 0) {
							echo '&mdash;';
						} else { ?>
                        <i class="fa fa-<?php echo $froid->getConformite() == 1 ? 'check' : 'times'; ?> fa-fw mr-1"></i><?php echo $froid->getConformite() == 1 ? 'Conforme' : 'Non conforme'; ?>
						<?php }
						?></td>
                    <td class="text-center"><?php echo $statuts[$froid->getStatut()]; ?></td>
                    <td class="text-center"><?php echo number_format($froid->getTemp_debut(),1,'.', ''); ?>°C</td>
                    <td class="text-center"><?php echo $tempFin != '' ? number_format($tempFin, 1,'.', '') : 'N/A'; ?>°C</td>
                    <td><?php echo Outils::getDate_verbose($froid->getDate_entree(), false, ' - '); ?></td>
                    <td><?php echo $froid->getDate_sortie() != '' ? Outils::getDate_verbose($froid->getDate_sortie(), false, ' - ') : '&mdash;'; ?></td>
                </tr>
			<?php } // FIN liste produits
			?>
            </tbody>
        </table>
		<?php

	} // FIN test produits trouvés
	exit;
} // FIN mode


/* -----------------------------------------------------
MODE - Liste des produits a nom court par catégorie
Pour édition des étiquettes vue ATL
------------------------------------------------------*/
function modeShowProduitsCourtsCategorie() {

	global $produitsManager, $cnx;

	$id_categorie = isset($_REQUEST['id_categorie']) ? intval($_REQUEST['id_categorie']) : 0;
	if ($id_categorie == 0)  { exit; }

	$liste_pdts =  $produitsManager->getProduitsNomsCourtsByCategorie($id_categorie);

	if (empty($liste_pdts)) { ?>

        <div class="col">
            <div class="alert alert-danger text-20">
                <i class="fa fa-exclamation-circle fa-lg mr-2"></i>Aucun produit dans cette catégorie...
            </div>
        </div>

		<?php exit;
	}

	?>
    <div class="col-12 text-center mb-2">
        <i class="fa fa-arrow-down gris-9 fa-lg"></i>
    </div>


	<?php
	// Boucle sur les catégories de produits
	foreach ($liste_pdts as $pdt) { ?>

        <div class="col-2 mb-3">
            <button type="button" class="btn btn-large btn-info form-control padding-20-40 text-20 text-uppercase btnNomCourt">
				<?php echo $pdt; ?>
            </button>
        </div>

		<?php
	} // FIN boucle sur les catégories
	?>


	<?php
	exit;
} // FIN mode





/* -----------------------------------------------------
MODE - Mise en attente produit d'un lot
------------------------------------------------------*/
function modeMiseEnAttenteLotPdtFroid() {

	global $cnx, $utilisateur;

	$froidProduitManager = new FroidManager($cnx);
    $logManager = new LogManager($cnx);
	$palettesManager = new PalettesManager($cnx);

	$idlotpdtfroid = isset($_REQUEST['idlotpdtfroid']) ? intval($_REQUEST['idlotpdtfroid']) : 0;
	if ($idlotpdtfroid == 0) { exit('3I1HMQB4'); }

	$froidProduit = $froidProduitManager->getFroidProduitObjetByIdLotPdtFroid($idlotpdtfroid);
	if (!$froidProduit instanceOf FroidProduit) { exit('MI8QY595'); }

	$texteLog = 'MISE EN ATTENTE id_lot_pdt_froid ' .$idlotpdtfroid . ' / id_pdt : ' .	$froidProduit->getId_pdt() . ' / id_lot :' . $froidProduit->getId_lot() . ' / id_palette : ' . $froidProduit->getId_palette() . ' / quantieme = ' . $froidProduit->getQuantieme();
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte($texteLog);
	$logManager->saveLog($log);

	$froidProduit->setDate_maj(date('Y-m-d H:i:s'));
	$froidProduit->setUser_maj($utilisateur->getId());
	$froidProduit->setAttente(1);



	if (!$froidProduitManager->saveFroidProduit($froidProduit)) { echo 'ARMMTVYP'; }

	// Il faut regrouper les produits en attente ayant le même lot et le même quantième...

    // On récupère tous les produits en attente
	$froidsManager      = new FroidManager($cnx);
	$vue_code           = 'cgl,srgh';
	$pdts_attente       = $froidsManager->getProduitsFroidsEnAttente($vue_code);

	$liste = [];

    // On construit un array pour regrouper les produits ayant le meme lot et quantième (grace aux clefs)
    foreach ($pdts_attente as $pdt_attente) {

        if (!isset(	$liste[$pdt_attente->getId_pdt().'-'.$pdt_attente->getId_lot().'-'.$pdt_attente->getQuantieme()])) {
			$liste[$pdt_attente->getId_pdt().'-'.$pdt_attente->getId_lot() .'-'. $pdt_attente->getQuantieme()] = [];
        }
		$liste[$pdt_attente->getId_pdt().'-'.$pdt_attente->getId_lot() .'-'. $pdt_attente->getQuantieme()][] = $pdt_attente->getId_lot_pdt_froid();

    } // FIN boucle sur les produits en attente



	// Puis pour chacun d'entre eux, on cumule le poids et le nb de colis
    foreach ($liste as $ids_lot_pdt_froid) {

        // On ne s'occupe pas des cas uniques.
        if (count($ids_lot_pdt_froid) < 2) { continue; }

		// On combine les données poids/nb de colis...
		// Si l'ID palette est différent, on le met à zéro -> dans ce cas il y aura nécéssité de le repréciser à la reprise
        $total_poids    = 0.0;
        $total_nb_colis = 0;
		$id_produit     = 0;
        $ids_palettes   = [];
        $ids_compos     = [];
        $ids_froid      = [];
        $etiquetages    = [];
		$lomas          = [];

        foreach ($ids_lot_pdt_froid as $id_lot_pdt_froid) {

            $pdtFroid = $froidProduitManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
            if (!$pdtFroid instanceof FroidProduit) { continue; }

			$total_poids    = $total_poids    + $pdtFroid->getPoids();
			$total_nb_colis = $total_nb_colis + $pdtFroid->getNb_colis();
			$ids_palettes[] = $pdtFroid->getId_palette();
			$ids_compos[]   = $pdtFroid->getId_compo();
			$ids_froid[]    = $pdtFroid->getId_froid();
			$etiquetages[]  = $pdtFroid->getEtiquetage();
			$lomas[]        = $pdtFroid->getLoma();
			$id_produit     = $pdtFroid->getProduit()->getId();


        } // FIN boucle $ids_lot_pdt_froid

        // On crée un nouveau ProduitFroid combiné et on supprime les autres...

        // On récupère les valeurs uniques ou bien on les définies à zéro.
        $id_palette = count(array_unique($ids_palettes)) == 1 ? $ids_palettes[0] : 0;
		$id_compo   = count(array_unique($ids_compos))   == 1 ? $ids_compos[0]   : 0;
		$id_froid   = count(array_unique($ids_froid))    == 1 ? $ids_froid[0]    : 0;
		$etiquetage = count(array_unique($etiquetages))  == 1 ? $etiquetages[0]  : 0;
		$loma       = count(array_unique($lomas))        == 1 ? $lomas[0]        : 0;

		// On doit avoir un id_froid pour ne pas avoir d'effet de bord sur les diverses requêtes historiques, on prend donc le premier...
        // (ben oui, on peut avoir une combinaisons de cgl et de srg...)
        if ($id_froid == 0 && isset($ids_froid[0])) { $id_froid = $ids_froid[0]; }


		// Instanciation et hydratation du produit combiné
        $pdtCombine = new FroidProduit([]);
		$pdtCombine->setId_palette($id_palette);
		$pdtCombine->setId_lot($pdtFroid->getId_lot());
		$pdtCombine->setPoids($total_poids);
		$pdtCombine->setNb_colis($total_nb_colis);
		$pdtCombine->setQuantieme($pdtFroid->getQuantieme());
		$pdtCombine->setUser_add($utilisateur->getId());
		$pdtCombine->setUser_maj($utilisateur->getId());
		$pdtCombine->setDate_maj(date('Y-m-d H:i:s'));
		$pdtCombine->setId_froid($id_froid);
		$pdtCombine->setAttente(1);
		$pdtCombine->setDate_add(date('Y-m-d H:i:s'));
		$pdtCombine->setEtiquetage($etiquetage);
		$pdtCombine->setId_pdt($id_produit);
		$pdtCombine->setLoma($loma);

		// Enregistrement du produitFroid combiné
		$id_lot_pdt_froid_combine = $froidProduitManager->saveFroidProduit($pdtCombine);

		// En cas d'échec de la création du produit combiné, on ne vas pas plus loin...
        if (!$id_lot_pdt_froid_combine) { exit; }

		// Si on a conservé la palette (tous viennent de la même), on peut créer la compo associée au pdt cumulé
		if ($id_palette > 0) {

		    // On récupère la compo du dernier produitFroid de la boucle
            $compoOld = $palettesManager->getComposition($id_compo);
            $id_client = $compoOld instanceof PaletteComposition ? $compoOld->getId_client() : 0;

			$compo = new PaletteComposition([]);
			$compo->setNb_colis($total_nb_colis);
			$compo->setPoids($total_poids);
			$compo->setId_lot_pdt_froid($id_lot_pdt_froid_combine);
			$compo->setId_user($utilisateur->getId());
			$compo->setId_palette($id_palette);
			$compo->setDate(date('Y-m-d H:i:s'));
			$compo->setSupprime(0);
			$compo->setId_client($id_client);
			$compo->setId_produit($id_produit);

			$palettesManager->savePaletteComposition($compo);



		} // FIN test palette conservée

		// On peut supprimer les anciens produits qu'on a combinés
        foreach ($ids_lot_pdt_froid as $id_lot_pdt_froid) {

            $froidProduitManager->supprPdtfroid($id_lot_pdt_froid);

        } // FIN boucle suppression des anciens produitsFroids



    } // FIN boucle sur les ID_lot_pdt_froids du même lot/quantième


    // CBO 30/08/2021 - Bug si on reprend des produits en attente ça cumulle le poids et les colis en compo
    // Seule solution trouvée : mettre à 0 les poids et colis de toutes les compos ayant cet id_lot_pdt_froid
	$palettesManager->razPoidsColisCompoByPdtFroid($froidProduit);

	exit;
} // FIN mode

/* -----------------------------------------------------
MODE - Affecte un produitFroid en attente
       à son nouveau traitement
------------------------------------------------------*/
function modeAffecteProduitFroidAttente() {

	global $utilisateur, $cnx;

	$froidManager       = new FroidManager($cnx);

	$id_lot_pdt_froid   = isset($_REQUEST['id_lot_pdt_froid'])  ? intval($_REQUEST['id_lot_pdt_froid'])     : 0;
	$id_froid           = isset($_REQUEST['id_froid'])          ? intval($_REQUEST['id_froid'])             : 0;
	$code_vue           = isset($_REQUEST['code_vue'])          ? trim(strtolower($_REQUEST['code_vue']))   : '';

	if ($id_lot_pdt_froid == 0) { exit('VB9I5JS3'); }
	if ($code_vue        == '') { exit('ZMT0ABRL'); }

	$froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
	if (!$froidProduit instanceof FroidProduit) { exit('F3D9BM6O');}

	// Si l'ID froid n'existe pas, on le crée...
	if ($id_froid == 0) {


		$typeCgl = $froidManager->getFroidTypeByCode($code_vue);
		if (!$typeCgl || $typeCgl == 0) { exit('0C9H9QP3'); }

		$froid = new Froid([]);
		$froid->setId_type($typeCgl);
		$froid->setId_user_maj($utilisateur->getId());
		$id_froid = $froidManager->saveFroid($froid);
		if (!$id_froid || $id_froid == 0) { exit('I34EKR7X'); }

		// Le lot à l'origine du produit est déjà associé à la vue froid en question, inutile donc de refaire l'associaiton ici

		// Log
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("[".strtoupper($code_vue)."] Création du traitement OP froid ID " . $id_froid . " à l'ajout du premier produit (attente).") ;
		$logsManager = new LogManager($cnx);
		$logsManager->saveLog($log);

	} // FIN création de l'OP de froid

	// Ici on a tout, on est bien... on peut enfin passer aux choses sérieuses...
	$froidProduit->setAttente(0);
	$froidProduit->setId_froid($id_froid);
	$froidProduit->setDate_maj(date('Y-m-d H:i:s'));
	$froidProduit->setUser_maj($utilisateur->getId());

	if (!$froidManager->saveFroidProduit($froidProduit)) { exit('SMXFL0V7'); }

	echo 'OK'.$id_froid;

	exit;

} // FIN mode

/* -----------------------------------------------------
MODE - Supprime une op de froid
------------------------------------------------------*/
function modeSupprimeFroid() {

	global $cnx, $utilisateur;

	$id_froid = isset($_REQUEST['id_froid']) ? $_REQUEST['id_froid'] : 0;
	if ($id_froid == 0) { exit; }

	$froidManager = new FroidManager($cnx);
	$froid = $froidManager->getFroid($id_froid);

	if (!$froid instanceof Froid) { exit; }

	$froid->setSupprime(1);
	$froid->setId_user_maj($utilisateur->getId());

	if ($froidManager->saveFroid($froid)) {
        $logManager = new LogManager($cnx);
        $log = new Log();
        $log->setLog_type('warning');
        $log->setLog_texte("Suppression du traitement froid #".$id_froid);
        $logManager->saveLog($log);
    }

	exit;

} // FIN mode



/* ------------------------------------------
MODE - Export en PDF
-------------------------------------------*/
function modeExportPdf() {

	global $tiersManager;

	$type = isset($_REQUEST['type']) ? trim(strtolower($_REQUEST['type'])) : 'produits';

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = genereContenuPdf($type);
	$content .= ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/iprexpdts-*.pdf') as $fichier) {
		unlink($fichier);
	}

	try {
		$nom_fichier = 'iprexpdts-'.date('is').'.pdf';
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
function genereContenuPdf($type = 'produits') {

	global $cnx, $produitsManager, $especesManager;

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
    .w65 { width: 65%; }
    .w50 { width: 50%; }
    .w45 { width: 45%; }
    .w40 { width: 40%; }
    .w25 { width: 25%; }
    .w35 { width: 35%; }
    .w33 { width: 33%; }
    .w34 { width: 34%; }
    .w30 { width: 30%; }
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

	$contenu.=  genereEntetePagePdf($type);

	// PAGE 1

	// GENERAL

	// Préparation des variables
	$na             = '<span class="gris-9 text-11"><i>Non renseigné</i></span>';
	$tiret          = '<span class="gris-9 text-11"><i>-</i></span>';

	// Préparation des variables

	$params = [ 'show_inactifs'  => true ];

	$liste = $type == 'produits' ?  $produitsManager->getListeProduits($params) : $especesManager->getListeProduitEspeces($params);

	// Génération du contenu HTML
	$contenu.= '<table class="table table-liste w100 mt-10">';

	// Aucun item
	if (empty($liste)) {

		$contenu.= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun '.$type.'</i></td></tr>';

		// Liste des items
	} else {

		$contenu.= $type == 'produits'
			? '<tr>
                    <th class="w5">Code</th>
                    <th class="w20">Nom</th>
                    <th class="w10">Nom court</th>
                    <th class="w15">Espèce</th>
                    <th class="w15">Catégories</th>
                    <th class="w10">EAN13</th>
                    <th class="w10 text-center">Poids par défaut (kgs)</th>
                    <th class="w10 text-center">Colisage</th>
                    <th class="w5 text-center">Actif</th>
                </tr>'
			:  '<tr>
                    <th class="w5">Couleur</th>
                    <th class="w65 pl-2">Nom</th>
                    <th class="w15 text-center">Nb de produits</th>
                    <th class="w15 text-center">Actif</th>
                </tr>';

		$configManager = new ConfigManager($cnx);
		$config_poids_defaut = $configManager->getConfig('poids_defaut');
		if ($config_poids_defaut instanceof Config) {
			$poidsDefautConfig =  $config_poids_defaut->getValeur();
		}

		$froidManager = new FroidManager($cnx);
		$froidTypes = $froidManager->getFroidTypes();

		foreach ($liste as $item) {


		    if ($type == 'produits') {
		        $cates = '';
				foreach ($item->getCategories() as $pdt_cates) {
					$cates .= $pdt_cates->getNom() . ', ';
				 }
				$cates = count($item->getCategories()) > 1 ? substr($cates,0,-2) : $cates;

				$poids = $item->getPoids() > 0 ? $item->getPoids() : $poidsDefautConfig;

				$nbColis =$item->getNb_colis() > 0  ? $item->getNb_colis() : $na;


            } // FIN produits

			$actif = $item->getActif() == 1 ? 'Oui' : 'Non';




			$contenu.= $type == 'produits'
				? '<tr>
                        <td class="w5">'.$item->getCode().'</td>
                        <td class="w20">'.$item->getNom().'</td>
                        <td class="w10">'.$item->getNom_court().'</td>
                        <td class="w15">'.$item->getNom_espece().'</td>
                        <td class="w15">'.$cates.'</td>
                        <td class="w10">'.$item->getEan13().'</td>
                        <td class="w10 text-center">'.number_format($poids,3,'.', ' ').'</td>
                        <td class="w10 text-center">'.$nbColis .'</td>
                        <td class="w5 text-center">'.$actif.'</td>
                   </tr>'
				: '<tr>
                        <td class="w5" style="background-color: '.$item->getCouleur().'"></td>
                        <td class="w65 pl-2">'.$item->getNom().'</td>
                        <td class="w15 text-center">'.$especesManager->getNbProduits($item).'</td>
                        <td class="w15 text-center">'.$actif.'</td>
                    </tr>';

		} // FIN boucle sur les items


	} // FIN test produits

	$contenu.= '</table>';

	$de = $type == 'produits' ? "de " : "d'";

	$contenu.= '<table class="table w100 mt-15"><tr><th class="w100 recap">Nombre '.$de.$type.' : '. count($liste) .'</th></tr></table>';
	// FOOTER
	$contenu.= '<table class="w100 gris-9">
                    <tr>
                        <td class="w50 text-8">Document édité le '.date('d/m/Y').' à '.date('H:i:s').'</td>
                        <td class="w50 text-right text-6">&copy; 2019 IPREX / INTERSED </td>
                    </tr>
                </table>
            </body>
        </html>';


	$contenu = str_replace('Œ', 'OE', $contenu);
	// RETOUR CONTENU
	return $contenu;

} // FIN fonction déportée


/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le header du PDF (logo...)
-----------------------------------------------------------------------------*/
function genereEntetePagePdf($type = 'produits') {

	global $cnx;

	$entete = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            Liste des '.$type.' au '.date("d/m/Y").'
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


/* -------------------------------------------------
MODE - Mise à jour du quantième sur un FroidProduit
--------------------------------------------------*/
function modeSaveQuantiemeLotPdtFroid() {

    global $cnx;

    $id_lot_pdt_froid = isset($_REQUEST['id_lot_pdt_froid']) ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
    $quantieme = isset($_REQUEST['quantieme']) ? intval($_REQUEST['quantieme']) : 0;

	$froidManager = new FroidManager($cnx);
	$froidPdt = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
	if (!$froidPdt instanceof FroidProduit) { exit; }

	$froidPdt->setQuantieme($quantieme);
	$froidManager->saveFroidProduit($froidPdt);

    exit;

} // FIN mode

/* -------------------------------------------------
MODE - Supprime un FroidProduit d'un traitement
--------------------------------------------------*/
function modeSupprIdLotPdtFroid() {

	global $cnx;

	$id_lot_pdt_froid = isset($_REQUEST['id_lot_pdt_froid']) ? intval($_REQUEST['id_lot_pdt_froid']) : 0;

	$froidManager = new FroidManager($cnx);
	$froidPdt = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
	if (!$froidPdt instanceof FroidProduit) { exit; }

	$froidManager->supprFroidProduit($froidPdt);

	exit;

} // FIN mode

/* -------------------------------------------------
MODE - Réinitalise la palette suivante à 1
--------------------------------------------------*/
function modeRazPaletteSuiv() {

    global $produitsManager, $logsManager;

    $id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit']) : 0;
    if ($id_produit == 0) { exit; }

    $produit = $produitsManager->getProduit($id_produit);
    if (!$produit instanceof Produit) { exit; }

    $produit->setPalette_suiv(1);
    if (!$produitsManager->saveProduit($produit)) { exit; }

    $log = new Log([]);
    $log->setLog_type('info');
    $log->setLog_texte("Réinitialisation du prochain numéro de palette à 1 pour le produit ID " . $id_produit);
	$logsManager->saveLog($log);

	echo '1';
	exit;

} // FIN mode


/* ----------------------------------------------
MODE - Affiche la liste des emballages pdt
-----------------------------------------------*/
function modeShowListeEmballagesProduit() {

	global $utilisateur, $cnx;

	$emballageProduitManager = new ProduitEmballageManager($cnx);

	$liste = $emballageProduitManager->getListeProduitEmballages();

	// Si rien a afficher
	if (empty($liste)) { ?>

        <div class="alert alert-danger">
            <i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucun emballage de produit !</strong>
        </div>

		<?php

	// Sinon, affichage de la liste
	} else { ?>

        <div class="alert alert-danger d-md-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-md-table">
            <thead>
            <tr>
				<?php
				// On affiche l'ID que si on est développeur
				if ($utilisateur->isDev()) { ?><th class="w-court-admin-cell d-none d-xl-table-cell">ID</th><?php } ?>

                <th>Nom</th>
                <th class="t-actions w-court-admin-cell">Modifier</th>
            </tr>
            </thead>
            <tbody>
			<?php
			// Boucle sur les emballages de produits
			foreach ($liste as $embpdt) {
				?>
                <tr>
					<?php
					// On affiche l'ID que si on est développeur
					if ($utilisateur->isDev()) { ?>
                        <td class="w-court-admin-cell d-none d-xl-table-cell"><span class="badge badge-pill badge-warning"><?php echo $embpdt->getId();?></span></td>
					<?php } ?>
                    <td class="text-18"><?php echo $embpdt->getNom();?></td>
                    <td class="t-actions w-court-admin-cell"><button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalProduitCategorie" data-id="<?php
						echo $embpdt->getId(); ?>"><i class="fa fa-edit"></i> </button></td>
                </tr>
				<?php
			} // FIN boucle  ?>
            </tbody>
        </table>
	<?php } // FIN test à afficher
	exit;

} // FIN mode




/* --------------------------------------
MODE - Modale Emballage produit (admin)
--------------------------------------*/
function modeModalEmballagesProduit() {

	global $utilisateur, $cnx;

	$emballageProduitManager = new ProduitEmballageManager($cnx);

	// On vérifie qu'on est bien loggé
	if (!isset($_SESSION['logged_user'])) { exit;}

	$id     = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$embPdt        = $id > 0 ? $emballageProduitManager->getProduitEmballage($id) : new ProduitEmballage([]);

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) {$utilisateur = unserialize($_SESSION['logged_user']);}

	// Retour Titre
	echo '<i class="fa fa-folder-open"></i>';
	echo $id > 0 ? $embPdt->getNom() : "Nouveau type d'emballage de produits&hellip;";

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>

    <form class="container-fluid" id="formEmballagePdtAddUpd">
        <input type="hidden" name="mode" value="saveEmballagePdt"/>
        <input type="hidden" name="id" id="input_id" value="<?php echo $id; ?>"/>
        <div class="row">
            <div class="col-12 input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">Libellé</span>
                </div>
                <input type="text" class="form-control" placeholder="Nom de l'emballage produit" name="nom" id="input_nom" value="<?php echo $embPdt->getNom(); ?>">
                <div class="invalid-feedback">Un nom est obligatoire.</div>
            </div>
        </div>
    </form>
    <div class="row mt-2">
        <div class="col doublon d-none">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle mr-1"></i> Ce nom existe déjà !
            </div>
        </div>
    </div>



	<?php
	// Séparateur Body/Footer pour le callback aJax
	echo '^';

	// Retour boutons footer si existant (bouton supprimer)
	if ($id > 0) {
		?>
        <button type="button" class="btn btn-danger btn-sm btnSupprimePdtEmb">
            <i class="fa fa-times fa-lg vmiddle mr-1"></i> Supprimer
        </button>
		<?php
	} // FIN test édition utilisateur existant
	exit;

} // FIN mode




/* -------------------------------------------------
MODE - Enregistre un emballage produit (add/upd)
--------------------------------------------------*/
function modeSaveEmballagePdt() {

	global $cnx;

	$emballageProduitManager = new ProduitEmballageManager($cnx);

	// Vérification des données
	$id   = isset($_REQUEST['id'])   ? intval($_REQUEST['id'])   : 0;
	$nom  = isset($_REQUEST['nom'])  ? trim($_REQUEST['nom'])    : '';

	// Si pas de nom on ne vas pas plus loin...
	if ($nom == '') {
		echo '-1';
		exit;
	} // FIN test champs requis

	$nom = htmlspecialchars(str_replace('#et#', '&', $nom));

	// Instanciation de l'objet PRODUITECATEGORIE (hydraté ou vide)

	$embPdt = $id > 0 ? $emballageProduitManager->getProduitEmballage($id) : new ProduitEmballage([]);

	// mise à jour des champs de base
	$embPdt->setNom($nom);

	// Enregistrement et retour pour callBack ajax
	$retour = $emballageProduitManager->saveProduitEmballage($embPdt);

	// Logs
	$logsManager = new LogManager($cnx);
	$log = new Log([]);
	if ($retour) {
		$log->setLog_type('info');
		if ($id == 0) {
			$log->setLog_texte("Création d'un nouvau type d'emballage de produits (ProductEmballage) : " . $nom);
		} else {
			$log->setLog_texte("Renommage de l'emballage de produits (ProductEmballage) ' #" . (int)$id);
		}
	} else {
		$log->setLog_type('danger');
		if ($id == 0) {
			$log->setLog_texte("ERREUR lors de la création d'un nouvau type d'emballage de produits (ProductEmballage) : " . $nom);
		} else {
			$log->setLog_texte("ERREUR lors du renommage de l'emballage de produits (ProductEmballage) ' #" . (int)$id);
		}
	} // FIN test retour création/maj pour log

	$logsManager->saveLog($log);

	echo $retour !== false ? '1' : '0';
	exit;

} // FIN mode



/* ---------------------------------------
MODE - Supprime un emballage produit
---------------------------------------*/
function modeSupprEmballageProduit() {

	global $cnx, $logsManager;

	$emballageProduitManager = new ProduitEmballageManager($cnx);

	//  -> On ne supprime pas réellement pour les raisons de traçabilité, on passe le champ "supprime" à 1

	// On récupère l'ID
	$id  = isset($_REQUEST['id_emb_pdt'])   ? intval($_REQUEST['id_emb_pdt'])   : 0;
	if ($id == 0) { exit; }

	// Instanciation de l'objet ProduitEmballage
	$pdtEmb = $emballageProduitManager->getProduitEmballage($id);

	// Si on a pas un objet ProduitEmballage en retour, on ne va pas plus loin !
	if (!$pdtEmb instanceof ProduitEmballage) { exit; }

	// On passe le statut à supprimé
	$pdtEmb->setSupprime(1);


	// Si la mise à jour s'est bien passé en BDD
	if ($emballageProduitManager->saveProduitEmballage($pdtEmb)) {

		// On retire le lien avec les produits liés :
		$emballageProduitManager->razProduitEmballage($id);

		// On sauvegarde dans les LOGS
		$log = new Log([]);
		$log->setLog_texte("Suppression d'un type d'emballages produit (champ 'supprime' à 1) : ID #" . $id);
		$log->setLog_type('warning');
		$logsManager->saveLog($log);

	} // FIN test suppression OK pour Log
	exit;

} // FIN mode


// Affiche la liste des EANS (admin)
function modeListeEans() {

	global $produitsManager;
	?>
    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <a class="nav-item nav-link active" id="nav13tab" data-toggle="tab" href="#nav13" role="tab" aria-controls="nav13" aria-selected="true">EAN13</a>
            <a class="nav-item nav-link" id="nav14tab" data-toggle="tab" href="#nav14" role="tab" aria-controls="nav14" aria-selected="false">EAN14</a>
            <a class="nav-item nav-link" id="nav7tab" data-toggle="tab" href="#nav7" role="tab" aria-controls="nav7" aria-selected="false">EAN7</a>
        </div>
    </nav>
    <div class="tab-content" id="nav-tabContent">

        <?php
        $eans = [13,14,7];
        foreach ($eans as $ean) {

            $methode = 'getEan'. $ean;

			$liste = $produitsManager->getListeProduitsEans($ean);
            ?>
            <div class="tab-pane fade <?php
            echo $ean == 13 ? 'show active' : '' ?>" id="nav<?php echo $ean;?>" role="tabpanel" aria-labelledby="nav<?php echo $ean;?>tab">
            <?php   if (empty($liste)) { echo 'Aucun EAN'.$ean.'...';} else { ?>
                <table class="table admin table-v-middle">
                    <thead>
                    <tr>
                        <th>EAN<?php echo $ean;?></th>
                        <th>Code</th>
                        <th>Produit</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ($liste as $produit) { ?>
                        <tr>
                            <td class="text-20"><?php echo $produit->$methode();?></td>
                            <td><?php echo $produit->getCode();?></td>
                            <td><?php echo $produit->getNom();?></td>
                        </tr>
					<?php } // FIN boucle sur les produits
					?>
                    </tbody>
                </table>

            <?php } ?>
            </div>
		<?php } // FIN boucle sur les types d'EAN

        ?>
    </div>
    <?php

	
    exit;
} // FIN mode

// Export PDF de la liste des EANS
function modeExportEansPdf() {

	global $produitsManager;

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style type="text/css">  
* { margin:0; padding: 0; }.header { border-bottom: 2px solid #ccc; }.header img.logo { width: 200px; }.text-right { text-align: right; }.text-center { text-align: center; }.table { border-collapse: collapse; }.table-donnees th { font-size: 11px; }.table-liste th { font-size: 9px; background-color: #d5d5d5; padding:3px; }.table-liste td { font-size: 9px; padding-top: 3px; padding-bottom: 3px; border-bottom: 1px solid #ccc;}.table-liste td.no-bb { border-bottom: none; padding-bottom: 0px; }.titre {   background-color: teal;   color: #fff;   padding: 3px;   text-align: center;   font-weight: normal;   font-size: 14px;}.recap {   background-color: #ccc;   padding: 3px;   text-align: center;   font-weight: normal;   font-size: 10px;}.w100 { width: 100%; }.w75 { width: 75%; }.w65 { width: 65%; }.w50 { width: 50%; }.w45 { width: 45%; }.w40 { width: 40%; }.w25 { width: 25%; }.w35 { width: 35%; }.w33 { width: 33%; }.w34 { width: 34%; }.w30 { width: 30%; }.w20 { width: 20%; }.w30 { width: 30%; }.w15 { width: 15%; }.w35 { width: 35%; }.w5 { width: 5%; }.w10 { width: 10%; }.w15 { width: 15%; }.text-6 { font-size: 6px; }.text-7 { font-size: 7px; }.text-8 { font-size: 8px; }.text-9 { font-size: 9px; }.text-10 { font-size: 10px; }.text-11 { font-size: 11px; }.text-12 { font-size: 12px; }.text-14 { font-size: 14px; }.text-16 { font-size: 16px; }.text-18 { font-size: 18px; }.text-20 { font-size: 20px; }.gris-3 { color:#333; }.gris-5 { color:#555; }.gris-7 { color:#777; }.gris-9 { color:#999; }.gris-c { color:#ccc; }.gris-d { color:#d5d5d5; }.gris-e { color:#e5e5e5; }.mt-0 { margin-top: 0px; }.mt-2 { margin-top: 2px; }.mt-5 { margin-top: 5px; }.mt-10 { margin-top: 10px; }.mt-15 { margin-top: 15px; }.mt-20 { margin-top: 20px; }.mt-25 { margin-top: 25px; }.mt-50 { margin-top: 50px; }.mb-0 { margin-bottom: 0px; }.mb-2 { margin-bottom: 2px; }.mb-5 { margin-bottom: 5px; }.mb-10 { margin-bottom: 10px; }.mb-15 { margin-bottom: 15px; }.mb-20 { margin-bottom: 20px; }.mb-25 { margin-bottom: 25px; }.mb-50 { margin-bottom: 50px; }.mr-0 { margin-right: 0px; }.mr-2 { margin-right: 2px; }.mr-5 { margin-right: 5px; }.mr-10 { margin-right: 10px; }.mr-15 { margin-right: 15px; }.mr-20 { margin-right: 20px; }.mr-25 { margin-right: 25px; }.mr-50 { margin-right: 50px; }.ml-0 { margin-left: 0px; }.ml-2 { margin-left: 2px; }.ml-5 { margin-left: 5px; }.ml-10 { margin-left: 10px; }.ml-15 { margin-left: 15px; }.ml-20 { margin-left: 20px; }.ml-25 { margin-left: 25px; }.ml-50 { margin-left: 50px; }.pt-0 { padding-top: 0px; }.pt-2 { padding-top: 2px; }.pt-5 { padding-top: 5px; }.pt-10 { padding-top: 10px; }.pt-15 { padding-top: 15px; }.pt-20 { padding-top: 20px; }.pt-25 { padding-top: 25px; }.pt-50 { padding-top: 50px; }.pb-0 { padding-bottom: 0px; }.pb-2 { padding-bottom: 2px; }.pb-5 { padding-bottom: 5px; }.pb-10 { padding-bottom: 10px; }.pb-15 { padding-bottom: 15px; }.pb-20 { padding-bottom: 20px; }.pb-25 { padding-bottom: 25px; }.pb-50 { padding-bottom: 50px; }.pr-0 { padding-right: 0px; }.pr-2 { padding-right: 2px; }.pr-5 { padding-right: 5px; }.pr-10 { padding-right: 10px; }.pr-15 { padding-right: 15px; }.pr-20 { padding-right: 20px; }.pr-25 { padding-right: 25px; }.pr-50 { padding-right: 50px; }.pl-0 { padding-left: 0px; }.pl-2 { padding-left: 2px; }.pl-5 { padding-left: 5px; }.pl-10 { padding-left: 10px; }.pl-15 { padding-left: 15px; }.pl-20 { padding-left: 20px; }.pl-25 { padding-left: 25px; }.pl-50 { padding-left: 50px; }.text-danger { color: #d9534f; }</style></head><body>';

	$content.=  genereEntetePagePdf('EAN');

	$eans = [13,14,7];
	foreach ($eans as $ean) {
		$methode = 'getEan' . $ean;

		$content.= '<h1>EAN '.$ean.'</h1>';
		$liste = $produitsManager->getListeProduitsEans($ean);
		$content.= '<table class="table table-liste w100 mt-10"><tr><th>EAN'. $ean.'</th><th>Code</th><th>Produit</th></tr>';
        foreach ($liste as $produit) {
            $content.= '<tr>
                            <td class="w30">'. $produit->$methode() .'</td>
                            <td class="w10">'. $produit->getCode() .'</td>
                            <td class="w60">'. $produit->getNom() .'</td>
                        </tr>';
        } // FIN boucle sur les produits
		$content.= '</table>';
	} // FIN boucle sur les types d'EAN
	$content .= ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/iprexeans-*.pdf') as $fichier) {
		unlink($fichier);
	}

	try {
		$nom_fichier = 'iprexeans-'.date('is').'.pdf';
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


// Mise à jour / suppression d'un poids palette
function modeUpdPoidsPalette() {

    global $cnx;
    $poidsPalettesManager = new PoidsPaletteManager($cnx);
	$logsManager = new LogManager($cnx);


    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0;
    $nom = isset($_REQUEST['nom']) ? trim($_REQUEST['nom']) : 0;
    if ($id == 0) { exit('-1'); }

    $poidsPalette = $poidsPalettesManager->getPoidsPalette($id);
    if (!$poidsPalette instanceof PoidsPalette) { exit('-2'); }

    $log = new Log([]);

    // Mise à jour
    if ($poids > 0 && $nom != '') {
        $poidsPalette->setPoids($poids);
        $poidsPalette->setNom($nom);
        $res = $poidsPalettesManager->savePoidsPalette($poidsPalette);
        $logTxt = $res ? 'M' : 'Echec de la m';
		$logTxt.='odification du nom/poids sur le PoidsPalette ID#'.$id;
		$logType = $res ? 'info' : 'danger';
		$log->setLog_texte($logTxt);
		$log->setLog_type($logType);
		$logsManager->saveLog($log);
		exit;
    }

    // Suppression
    $res = $poidsPalettesManager->deletePoidsPalette($poidsPalette);
	$logTxt = $res ? 'S' : 'Echec de la s';
	$logTxt.='uppression du poids palette ID#'.$id;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logsManager->saveLog($log);
	exit;


} // FIN mode

function modeActiveRacineEan13() {

	global $produitsManager, $cnx;
	$logsManager = new LogManager($cnx);

    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id == 0) { exit('-1'); }

	$res = $produitsManager->activeRacineEan13($id);
    if ($res) {
        echo '1';
        $log = new Log();
		$log->setLog_type('info');
		$log->setLog_texte('Changement de la racine par défaut pour les EAN 13 (ID#'.$id.')');
		$logsManager->saveLog($log);
    }
    exit;

} // FIN mode