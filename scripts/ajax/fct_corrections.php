<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax ADMIN CORRECTIONS
------------------------------------------------------*/
//ini_set('display_errors',1);

// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$logsManager    = new LogManager($cnx);    // LOGS système
$froidManager   = new FroidManager($cnx);

// Construction et appel des fonctions "mode"
$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}



/* ------------------------------------------
MODE - Charge le contenu d'une étape de vue
-------------------------------------------*/
function modeRecherche() {

	global $cnx, $froidManager, $mode;

	$filtre_lot     = isset($_REQUEST['filtre_lot'])       ? trim(strtolower(strip_tags($_REQUEST['filtre_lot'])))        : '';
	$filtre_froid   = isset($_REQUEST['filtre_froid'])     ? trim(strtolower(strip_tags($_REQUEST['filtre_froid'])))      : '';
	$filtre_produit = isset($_REQUEST['filtre_produit'])   ? trim(strtolower(strip_tags($_REQUEST['filtre_produit'])))    : '';
	$filtre_palette = isset($_REQUEST['filtre_palette'])   ? intval(preg_replace("/[^0-9]/", "", $_REQUEST['filtre_palette'])) : 0;
	$filtre_date    = isset($_REQUEST['filtre_date'])      ? trim(strip_tags($_REQUEST['filtre_date']))                : '';

	$params = [];
	if ($filtre_lot     != '') { $params['lot']     = $filtre_lot;      }
	if ($filtre_froid   != '') { $params['froid']   = $filtre_froid;    }
	if ($filtre_produit != '') { $params['produit'] = $filtre_produit;  }
	if ($filtre_palette  >  0) { $params['palette'] = $filtre_palette;  }
	if ($filtre_date    != '') { $params['date']    = Outils::dateFrToSql($filtre_date); }

	// Préparation pagination (Ajax)
	$nbResultPpage      = 15;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode='.$mode;
	$filtresPagination .= $filtre_lot       != '' ? '&filtre_lot='    . $filtre_lot     : '';
	$filtresPagination .= $filtre_froid     != '' ? '&filtre_froid='  . $filtre_froid   : '';
	$filtresPagination .= $filtre_produit   != '' ? '&filtre_produit='. $filtre_produit : '';
	$filtresPagination .= $filtre_palette    > 0  ? '&filtre_palette='. $filtre_palette : '';
	$filtresPagination .= $filtre_date      != '' ? '&filtre_date='   . $filtre_date    : '';
	$start              = ($page-1) * $nbResultPpage;

	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;

	$liste_froids = $froidManager->getFroidsHistoriqueRecherche($params);
    
    if(empty($filtre_lot)){?>
        <div class="alert alert-secondary mt-3 text-center">
              <span class="fa-stack fa-2x mt-5">
                   <i class="fas fa-list-ul fa-stack-1x"></i>
                   <i class="fas fa-ban fa-stack-2x" style="color:Tomato"></i>
               </span>
            <h3 class="gris-7 mt-3 mb-5">Aucun résultat…</h3>
        </div>
        <?php
	    exit;
    }    

	// Aucun résultat
	if (!$liste_froids || empty($liste_froids)) { ?>

        <div class="alert alert-secondary mt-3 text-center">
              <span class="fa-stack fa-2x mt-5">
                   <i class="fas fa-list-ul fa-stack-1x"></i>
                   <i class="fas fa-ban fa-stack-2x" style="color:Tomato"></i>
               </span>
            <h3 class="gris-7 mt-3 mb-5">Aucun résultat…</h3>
        </div>

        <?php
	    exit;
    } // FIN aucun résultat

    // Résultats trouvés...

	// Construction de la pagination...
	$nbResults  = $froidManager->getNb_results();
	$pagination = new Pagination($page);

	$pagination->setUrl($filtresPagination);
	$pagination->setNb_results($nbResults);
	$pagination->setAjax_function(true);
	$pagination->setNb_results_page($nbResultPpage);

    ?>
    <div class="row">
        <div class="col-12">
            <table class="table admin mt-2 table-v-middle">
                <thead>
                <tr>
                    <th>Code</th>
                    <th>Produit</th>
                    <th>Lot</th>
                    <th>Traitement</th>
                    <th>Opération</th>
                    <th class="text-center">Palette</th>
                    <th class="text-center">Nb colis/blocs</th>
                    <th class="text-right pr-5">Poids total</th>
                    <th class="text-center">Entrée</th>
                    <th class="text-center">T°</th>
                    <th class="text-center">Sortie</th>
                    <th class="text-center">T°</th>
                    <th class="t-actions w-court-admin-cell text-center">Corriger</th>
                </tr>
                </thead>
                <?php
				$couleursLots   = ['#8746ce', '#0e8c62', '#367dc4', '#da5a1b', '#e32b62',  '#0e8f9d', '#3945e4', '#a92689', '#e24c4c', '#245a8e'];
				$coul           = 0;
				$lotId          = -1;

				foreach ($liste_froids as $pdtFroid) {

					if ($lotId !=  $pdtFroid->getId_lot().$pdtFroid->getQuantieme()) { $coul++; $lotId = $pdtFroid->getId_lot().$pdtFroid->getQuantieme(); }
					$date_entree = $pdtFroid->getFroid() instanceof Froid && $pdtFroid->getFroid()->getDate_entree() != ''
                        ? Outils::getDate_verbose($pdtFroid->getFroid()->getDate_entree(), false, ' - ', false) : '&mdash;';
					$date_sortie = $pdtFroid->getFroid() instanceof Froid && $pdtFroid->getFroid()->getDate_sortie() != ''
						? Outils::getDate_verbose($pdtFroid->getFroid()->getDate_sortie(), false, ' - ', false) : '&mdash;';
					$temp_debut = $pdtFroid->getFroid() instanceof Froid && floatval($pdtFroid->getFroid()->getTemp_debut()) != 0.0
						? $pdtFroid->getFroid()->getTemp_debut() . ' °C' : '&mdash;';
					$temp_fin = $pdtFroid->getFroid() instanceof Froid && floatval($pdtFroid->getFroid()->getTemp_fin()) != 0.0
						? $pdtFroid->getFroid()->getTemp_fin() . ' °C' : '&mdash;';

				    ?>

                    <tr>
                        <td><code class="gris-5 text-12"><?php echo $pdtFroid->getProduit()->getCode(). '/' . $pdtFroid->getId_lot_pdt_froid() ;?></code></td>
                        <td class="text-20"><?php echo $pdtFroid->getProduit()->getNom();?></td>
                        <td><span class="badge badge-secondary text-18" style="background-color: <?php
							echo $couleursLots[$coul]; ?>"><?php
                                  echo $pdtFroid->getNumlot();
                                ?></span></td>
                        <td><?php echo $pdtFroid->getNom_traitement(); ?></td>
                        <td><?php echo $pdtFroid->getCode_traitement(); ?></td>
                        <td class="text-center"><?php echo $pdtFroid->getNumero_palette() > 0 ? $pdtFroid->getNumero_palette() : '&mdash;'; ?></td>
                        <td class="text-center"><?php echo $pdtFroid->getNb_colis(); ?></td>
                        <td class="text-center"><?php echo $pdtFroid->getPoids(); ?> kg</td>
                        <td class="text-center"><?php echo $date_entree; ?></td>
                        <td class="text-center"><?php echo $temp_debut; ?></td>
                        <td class="text-center"><?php echo $date_sortie; ?></td>
                        <td class="text-center"><?php echo $temp_fin; ?></td>
                        <td class="t-actions w-court-admin-cell text-center">
                            <button type="button" class="btn btn-sm btn-secondary btnCorrection" data-id-lot-pdt-froid="<?php
							echo $pdtFroid->getId_lot_pdt_froid(); ?>"><i class="fa fa-edit"></i> </button>
                        </td>
                    </tr>

                <?php
				} // FIN boucle sur les froidProduits
                ?>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <?php

	// Pagination (aJax)
	if (isset($pagination)) {
		// Pagination bas de page, verbose...
		$pagination->setVerbose_pagination(1);
		$pagination->setVerbose_position('right');
		$pagination->setNature_resultats('produit');
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);

		echo ($pagination->getPaginationHtml());
	} // FIN test pagination

    exit;

} // FIN mode

/* ---------------------------------------------
MODE - Charge le contenu de la modale correction
----------------------------------------------*/
function modeContenuModaleCorrection() {

    global $cnx, $froidManager, $utilisateur;

    $id_lot_pdt_froid = isset($_REQUEST['id_lot_pdt_froid']) ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
    if ($id_lot_pdt_froid == 0) { exit("ERREUR - Identification du ProduitFroid impossible ! Code erreur : UGV6N9C8"); }

	$froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
    if (!$froidProduit instanceof FroidProduit) { exit("ERREUR - Instanciation de l'objet ProduitFroid impossible ! Code erreur : JFZRHY9B - " . $id_lot_pdt_froid); }

	$lotManger = new LotManager($cnx);
    $pdtManger = new ProduitManager($cnx);
    $palettesManager = new PalettesManager($cnx);

    $hache = substr(strtolower($froidProduit->getCode_traitement()),0,3) == 'hac';

    ?>
    <h4 class="mb-3">Correction <?php
        echo $hache ? 'du ' : 'de la ';
        echo strtolower($froidProduit->getNom_traitement()) . ' ' . $froidProduit->getCode_traitement();  ?></h4>
    <?php
    if ($utilisateur->isDev()) { ?>
        <p class="text-12"><i class="fa fa-user-secret mr-1 gris-9"></i><code>id_lot_pdt_froid :</code> <kbd><?php echo $id_lot_pdt_froid; ?></kbd></p>
    <?php }
    ?>


        <input type="hidden" name="mode" value="saveModifications"/>
        <input type="hidden" id="nbCompos" value="<?php echo $palettesManager->getNbComposByIdLotPdtFroid($id_lot_pdt_froid); ?>"/>
        <input type="hidden" name="id_lot_pdt_froid" id="correctionIdLotPdtFroid" value="<?php echo $froidProduit->getId_lot_pdt_froid(); ?>"/>
        <input type="hidden" name="id_froid_pour_ajout_pdt" id="correctionIdFroid" value="<?php echo $froidProduit->getId_froid(); ?>"/>
        <div class="alert alert-secondary">
            <div class="text-left text-14 gris-5 texte-fin mb-2"><i class="fa fa-dolly mr-1"></i>Modification du produit :</div>
        <div class="row">

            <div class="col-3">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text text-14">Lot</span>
                    </div>
                    <input type="text" class="form-control text-center numlot" id="numlot" placeholder="" name="numlot" value="<?php echo $froidProduit->getNumlot();?>"/>
                    <input type="hidden" class="form-control text-center" id="id_lot" placeholder="" name="id_lot" value="<?php echo $froidProduit->getId_lot();?>"/>
                </div>
            </div>
            <div class="<?php echo $hache ? 'd-none': 'col-3'; ?>">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text text-14">Quantième</span>
                    </div>
                    <input type="text" class="form-control text-center" placeholder="" name="quantieme" value="<?php echo $froidProduit->getQuantieme();?>"/>
                </div>
            </div>
            <div class="col-<?php echo $hache ? '9': '6'; ?>">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text text-14">Produit</span>
                    </div>
                    <select class="selectpicker show-tick form-control" name="id_pdt" data-live-search="true">
						<?php
						$params = ['vue' => intval($froidProduit->getFroid()->getId_type())];
						$pdtsliste = $pdtManger->getListeProduits($params);
						foreach ($pdtsliste as $pdtl) { ?>

                            <option value="<?php echo $pdtl->getId(); ?>" <?php
                            echo $pdtl->getId() == $froidProduit->getId_pdt() ? 'selected' : '';
                            ?>><?php echo $pdtl->getNom(); ?></option>

						<?php } // FIIN boucle sur les lots en cours ou cloturées il y a moins d'une semaine
						?>
                    </select>
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text text-14">Poids</span>
                    </div>
                    <input type="text" class="form-control text-right" placeholder="Poids" name="poids" value="<?php echo $froidProduit->getPoids();?>" data-old="<?php echo $froidProduit->getPoids();?>"/>
                    <div class="input-group-append">
                        <span class="input-group-text text-14">kg</span>
                    </div>
                </div>
            </div>
            <div class="<?php echo $hache ? 'd-none': 'col-3'; ?>">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text text-14">Qté</span>
                    </div>
                    <input type="text" class="form-control text-right" placeholder="Poids" name="nb_colis" value="<?php echo $froidProduit->getNb_colis();?>" data-old="<?php echo $froidProduit->getNb_colis();?>"/>
                    <div class="input-group-append">
                        <span class="input-group-text text-14"><?php echo strtolower(substr($froidProduit->getCode_traitement(),3,1)) == 'v' ? 'blocs' : 'colis';?></span>
                    </div>
                </div>
            </div>
            <div class="<?php echo $hache ? 'd-none': 'col-2'; ?>">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text text-14">Palette</span>
                </div>
                <select class="selectpicker form-control" name="palette">
                    <option value="0">- Aucune -</option>
                    <?php
                    foreach ($palettesManager->getListePalettes(['id_lot' => $froidProduit->getId_lot()]) as $p) {
                        if ($p->getId_client() == 0 && $p->getId() != $froidProduit->getId_palette()) { continue; }
                        ?>
                        <option value="<?php echo $p->getId(); ?>" data-subtext="<?php echo $p->getNom_client();?>" <?php
                        echo $p->getId() == $froidProduit->getId_palette() ? 'selected' : '';
                        ?>><?php echo $p->getNumero(); ?></option>
					<?php }
                    ?>
                </select>

                <!--<input type="text" class="form-control text-center" placeholder="-" name="palette" value="<?php /*echo $froidProduit->getId_palette() > 0 ? $froidProduit->getNumero_palette() : '';*/?>" data-old="<?php /*echo $froidProduit->getId_palette();*/?>"/>-->
            </div>
        </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger mr-2 btnSupprProduitFroid form-control" data-id-lot="<?php echo $froidProduit->getId_lot(); ?>" data-id-pdt="<?php echo $froidProduit->getId_pdt(); ?>">
                    <i class="fa fa-trash-alt margin-right-10"></i>Supprimer
                </button>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-info mr-2 btnAddProduitFroid form-control" data-id-pdt="<?php echo $froidProduit->getId_pdt(); ?>">
                    <i class="fa fa-plus-square margin-right-10"></i>Ajouter
                </button>
            </div>
        </div>
        </div>
        <div class="alert alert-secondary">
            <div class="text-left text-14 gris-5 texte-fin mb-2"><i class="fa fa-snowflake mr-1"></i>Modifications impactant l'ensemble du traitement :</div>
            <div class="row">
                <div class="col-3 mb-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-14">Statut</span>
                        </div>
                        <select class="selectpicker show-tick form-control" name="froid_statut" <?php echo $froidProduit->getFroid()->getStatut() == 0 ? 'disabled' : '';?>>
                                <option value="0" <?php echo $froidProduit->getFroid()->getStatut() == 0 ? 'selected' : ''; ?>>En production</option>
                                <option value="2" <?php echo $froidProduit->getFroid()->getStatut() == 2 ? 'selected' : ''; ?>>Finalisée</option>
                        </select>
                    </div>
                </div>
                <div class="col-3 mb-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-14">Conformité</span>
                        </div>
                        <select class="selectpicker show-tick form-control" name="froid_conformite">
                            <option value="-1" <?php echo $froidProduit->getFroid()->getConformite() == -1 ? 'selected' : ''; ?>>N/A</option>
                            <option data-divider="true"></option>
                            <option value="1" <?php echo $froidProduit->getFroid()->getConformite() == 1 ? 'selected' : ''; ?>>Oui</option>
                            <option value="0" <?php echo $froidProduit->getFroid()->getConformite() == 0 ? 'selected' : ''; ?>>Non</option>

                        </select>
                    </div>
                </div>
                <div class="col-3 mb-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-14">T° début</span>
                        </div>
                        <input type="text" class="form-control text-right" placeholder="0" name="temp_debut" value="<?php echo $froidProduit->getFroid()->getTemp_debut();?>"/>
                        <div class="input-group-append">
                            <span class="input-group-text text-14">°C</span>
                        </div>
                    </div>
                </div>
                <div class="col-3 mb-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-14">T° fin</span>
                        </div>


                        <?php
						// Récupération des températures mini et maxi autorisées pour le traitement (gestion du commentaire obligatoire si modif vers hors norme)
						$configManager      = new ConfigManager($cnx);
						$codeClef           = substr($froidProduit->getFroid()->getCode(),0,3);
						$clefConfigMin      = 'tmp_'.$codeClef.'_min';
						$clefConfigMax      = 'tmp_'.$codeClef.'_max';

						$config_tmp_min     = $configManager->getConfig($clefConfigMin);
						$config_tmp_max     = $configManager->getConfig($clefConfigMax);

						$tmp_min            = $config_tmp_min instanceof Config ?  intval($config_tmp_min->getValeur()) : -50;
						$tmp_max            = $config_tmp_max instanceof Config ?  intval($config_tmp_max->getValeur()) : 50;
                        ?>

                        <input type="text" class="form-control text-right" placeholder="0" name="temp_fin" value="<?php echo $froidProduit->getFroid()->getTemp_fin();?>" data-temp-min="<?php echo $tmp_min; ?>" data-temp-max="<?php echo $tmp_max; ?>"/>
                        <div class="input-group-append">
                            <span class="input-group-text text-14">°C</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-14">Date début</span>
                        </div>
                        <input type="text" class="datepicker form-control" placeholder="JJ/MM/AAAA" name="date_entree_jour" value="<?php echo Outils::dateSqlToFr($froidProduit->getFroid()->getDate_entree());?>" />
                    </div>
                </div>
                <div class="col-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-14">Heure début</span>
                        </div>
                        <input type="text" class="form-control text-right" placeholder="00:00" name="date_entree_heure" value="<?php echo Outils::getHeureMinutesFromDateTime($froidProduit->getFroid()->getDate_entree());?>"/>
                    </div>
                </div>
                <div class="col-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-14">Date fin</span>
                        </div>
                        <input type="text" class="datepicker form-control" placeholder="JJ/MM/AAAA" name="date_sortie_jour" value="<?php echo Outils::dateSqlToFr($froidProduit->getFroid()->getDate_sortie());?>" />
                    </div>
                </div>
                <div class="col-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text text-14">Heure fin</span>
                        </div>
                        <input type="text" class="form-control text-right" placeholder="00:00" name="date_sortie_heure" value="<?php echo Outils::getHeureMinutesFromDateTime($froidProduit->getFroid()->getDate_sortie());?>"/>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-secondary">
            <div class="text-left text-14 gris-5 texte-fin mb-2"><i class="fa fa-comment-dots mr-1"></i>Ajouter un commentaire a propos du traitement :</div>
            <div class="row">
                <div class="col-12">
                    <textarea class="form-control" name="froid_commentaire" placeholder="Nouveau commentaire..."></textarea>
                </div>
            </div>
        </div>

    <?php
    exit;

} // FIN mode

/* ---------------------------------------------
MODE - Supprime un produit froid
----------------------------------------------*/
function modeSupprimeProduitFroid() {

	global $cnx, $froidManager, $utilisateur;

	$id_lot_pdt_froid = isset($_REQUEST['id_lot_pdt_froid']) ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
	if ($id_lot_pdt_froid == 0) { exit("ERREUR - Identification du ProduitFroid impossible ! Code erreur : UGV6N9C8"); }

	$froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
	if (!$froidProduit instanceof FroidProduit) { exit("ERREUR - Instanciation de l'objet ProduitFroid impossible ! Code erreur : VYFJUXM0 - " . $id_lot_pdt_froid); }

	if ($froidManager->supprFroidProduit($froidProduit)) {

	    // On historise la modification
		$modifsManager = new ModificationManager($cnx);
		$modif = new Modification([]);
		$modif->setId_lot_pdt_froid($id_lot_pdt_froid);
		$modif->setId_froid($froidProduit->getFroid());
		$modif->setChamp('id_lot_pdt_froid');
		$modif->setValeur_old($id_lot_pdt_froid);
		$modif->setValeur_new(0);
		$modif->setDate(date('Y-m-d H:i:s'));
		$modif->setUser_id($utilisateur->getId());
		$modifsManager->saveModification($modif);

    }
    exit;

} // FIN mode


/* ---------------------------------------------
MODE - Ajotue un produit froid (BO correction)
----------------------------------------------*/
function modeAddProduitFroid() {

	global $cnx, $froidManager, $utilisateur;

	$id_pdt     = isset($_REQUEST['id_pdt']) ?   intval($_REQUEST['id_pdt'])        : 0;
	$id_lot     = isset($_REQUEST['id_lot'])    ? intval($_REQUEST['id_lot'])       : 0;
	$id_froid   = isset($_REQUEST['id_froid'])  ? intval($_REQUEST['id_froid'])     : 0;
	$quantieme  = isset($_REQUEST['quantieme']) ? intval($_REQUEST['quantieme'])    : 0;
	$poids      = isset($_REQUEST['poids'])     ? floatval($_REQUEST['poids'])      : 0;
	$nb_colis   = isset($_REQUEST['nb_colis'])  ? intval($_REQUEST['nb_colis'])     : 0;

	if ($id_pdt == 0 || $id_lot == 0 || $id_froid ==0) { exit('données invalides !'); }

	// On vérifie qu'il n'existe pas déjà
    $froidPdtDeja = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
	if ($froidPdtDeja instanceof FroidProduit) { exit('Existe déjà'); }

    $froidProduit = new FroidProduit([]);
	$froidProduit->setId_froid($id_froid);
	$froidProduit->setId_lot($id_lot);
	$froidProduit->setId_pdt($id_pdt);
	$froidProduit->setQuantieme($quantieme);
	$froidProduit->setPoids($poids);
	$froidProduit->setNb_colis($nb_colis);
	$froidProduit->setDate_add(date('Y-m-d H:i:s'));
	$froidProduit->setUser_add($utilisateur->getId());
	$new_id_lot_pdt_froid = $froidManager->saveFroidProduit($froidProduit);
	if (intval($new_id_lot_pdt_froid) > 0) {

		// On historise la modification
		$modifsManager = new ModificationManager($cnx);
		$modif = new Modification([]);
		$modif->setId_lot_pdt_froid($new_id_lot_pdt_froid);
		$modif->setId_froid($id_froid);
		$modif->setChamp('id_lot_pdt_froid');
		$modif->setValeur_old(0);
		$modif->setValeur_new($new_id_lot_pdt_froid);
		$modif->setDate(date('Y-m-d H:i:s'));
		$modif->setUser_id($utilisateur->getId());
		$modifsManager->saveModification($modif);

    }

	exit;

} // FIN mode


/* ---------------------------------------------
MODE - Correction d'un produit Froid (BO)
----------------------------------------------*/
function modeSaveModifications() {

    global $cnx, $froidManager, $utilisateur;

    // Récupération des variables
    $id_lot_pdt_froid   = isset($_REQUEST['id_lot_pdt_froid'])  ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
    $id_lot             = isset($_REQUEST['id_lot'])            ? intval($_REQUEST['id_lot'])           : 0;
    $id_pdt             = isset($_REQUEST['id_pdt'])            ? intval($_REQUEST['id_pdt'])           : 0;
	$quantieme          = isset($_REQUEST['quantieme'])         ? trim($_REQUEST['quantieme'])          : '';
	$poids              = isset($_REQUEST['poids'])             ? floatval($_REQUEST['poids'])          : 0.0;
	$nb_colis           = isset($_REQUEST['nb_colis'])          ? intval($_REQUEST['nb_colis'])         : 0;
	$statut             = isset($_REQUEST['froid_statut'])      ? intval($_REQUEST['froid_statut'])     : -2;
	$temp_debut         = isset($_REQUEST['temp_debut'])        ? floatval($_REQUEST['temp_debut'])     : 999;
	$temp_fin           = isset($_REQUEST['temp_fin'])          ? floatval($_REQUEST['temp_fin'])       : 999;
	$conformite         = isset($_REQUEST['froid_conformite'])  ? intval($_REQUEST['froid_conformite']) : -2;
	$date_entree_jour   = isset($_REQUEST['date_entree_jour'])  ? trim($_REQUEST['date_entree_jour'])   : '';
	$date_entree_heure  = isset($_REQUEST['date_entree_heure']) ? trim($_REQUEST['date_entree_heure'])  : '';
	$date_sortie_jour   = isset($_REQUEST['date_sortie_jour'])  ? trim($_REQUEST['date_sortie_jour'])   : '';
	$date_sortie_heure  = isset($_REQUEST['date_sortie_heure']) ? trim($_REQUEST['date_sortie_heure'])  : '';
	$froid_commentaire  = isset($_REQUEST['froid_commentaire']) ? trim(nl2br($_REQUEST['froid_commentaire']))  : '';
	$palette            = isset($_REQUEST['palette'])           ? intval(preg_replace("/[^0-9]/", "", $_REQUEST['palette'])) : 0;
    $numlot             = isset($_REQUEST['numlot']) ? trim($_REQUEST['numlot'])  : '';    
	// Reconstruction des dates
    $date_entree = Outils::dateFrToSql($date_entree_jour) . ' ' . $date_entree_heure . ':00';
	$date_sortie = Outils::dateFrToSql($date_sortie_jour) . ' ' . $date_sortie_heure . ':00';    

    // Instanciation de l'objet de référence
    $froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
    if (!$froidProduit instanceof FroidProduit) { exit('Instanciation FroidProduit échouéee'); }

    // On clone l'objet avant modifs pour l'historisation des anciennes valeurs
    $oldObjet = clone $froidProduit;

    $froid = $froidProduit->getFroid();
    if (!$froid instanceof Froid) { exit('Instanciation Froid échouée'); }

	// On clone l'objet avant modifs pour l'historisation des anciennes valeurs
	$oldObjetFroid = clone $froid;

	$palettesManager = new PalettesManager($cnx);

    // Si on modifie la palette, le poids ou le nb de colis, on impacte aussi la table palette_compositions...
    if ($poids != $froidProduit->getPoids() || $nb_colis != $froidProduit->getNb_colis() || $palette != $froidProduit->getId_palette()) {
		$palettesManager->updPaletteQteCompoByIdPdtFroid($id_lot_pdt_froid, $poids, $nb_colis, $palette);

    }

	$changePdt = false;
    // Hydratation des valeurs modifiées - table froid_produits
    if  ($id_lot    > 0   && $id_lot    != $froidProduit->getId_lot())      { $froidProduit->setId_lot($id_lot);        }
    if  ($id_pdt    > 0   && $id_pdt    != $froidProduit->getId_pdt())      { $froidProduit->setId_pdt($id_pdt); $changePdt = true; }
    if  ($quantieme != '' && $quantieme != $froidProduit->getQuantieme())   { $froidProduit->setQuantieme($quantieme);  }
    if  ($poids     > 0   && $poids     != $froidProduit->getPoids())       { $froidProduit->setPoids($poids);          }
    if  ($nb_colis  > 0   && $nb_colis  != $froidProduit->getNb_colis())    { $froidProduit->setNb_colis($nb_colis);    }
    if  ($palette  > 0   && $palette  != $froidProduit->getId_palette())    { $froidProduit->setId_palette($palette);   }
    if($numlot !='' && $numlot != $froidProduit->getNumlot()){ $froidProduit->setNumlot($numlot);    }

    // Hydratation des valeurs modifiées - table froid
    if  ($statut          > -2   && $statut           != $froid->getStatut())      { $froid->setStatut($statut);  }
    if  ($temp_debut      < 999 && $temp_debut        != $froid->getTemp_debut())  { $froid->setTemp_debut($temp_debut);  }
    if  ($temp_fin        < 999 && $temp_fin          != $froid->getTemp_fin())    { $froid->setTemp_fin($temp_fin);      }
    if  ($conformite      > -2  && $conformite        != $froid->getConformite())  { $froid->setConformite($conformite);  }
    if ($date_entree_jour != '' && $date_entree_heure != '' && substr($date_entree, 0,-3) != substr($froid->getDate_entree(),0,-3)) { $froid-> setDate_entree($date_entree); }
    if ($date_sortie_jour != '' && $date_sortie_heure != '' && substr($date_sortie, 0,-3) != substr($froid->getDate_sortie(),0,-3)) { $froid-> setDate_sortie($date_sortie); }    

    $modifManager = new ModificationManager($cnx);

    // Si modifications de la table froid_produuis
    if (!empty($froidProduit->attributs)) {

        // Si enregistrement ok, on historise les changements
		if ($froidManager->saveFroidProduit($froidProduit)) {

		    foreach ($froidProduit->attributs as $attribut) {

				$getter	= 'get'.ucfirst(strtolower($attribut));

				$modif = new Modification([]);
				$modif->setUser_id($utilisateur->getId());
				$modif->setDate(date('Y-m-d H:i:s'));
				$modif->setId_lot_pdt_froid($id_lot_pdt_froid);
				$modif->setChamp($attribut);
				$modif->setValeur_old($oldObjet->$getter());
				$modif->setValeur_new($froidProduit->$getter());

				$modifManager->saveModification($modif);

            } // FIN boucle modifications

        } // FIN test enregistrement ok pour historisation
    } // FIN modiifications table froid_produits

	// Si modifications de la table froid
	if (!empty($froid->attributs)) {

		// Si enregistrement ok, on historise les changements
		if ($froidManager->saveFroid($froid)) {



			foreach ($froid->attributs as $attribut) {

				$getter	= 'get'.ucfirst(strtolower($attribut));

				$modif = new Modification([]);
				$modif->setUser_id($utilisateur->getId());
				$modif->setDate(date('Y-m-d H:i:s'));
				$modif->setId_froid($froid->getId());
				$modif->setChamp($attribut);
				$modif->setValeur_old($oldObjetFroid->$getter());
				$modif->setValeur_new($froid->$getter());

				$modifManager->saveModification($modif);

			} // FIN boucle modifications

		} // FIN test enregistrement ok pour historisation
	} // FIN modiifications table froid_produits

	if ($changePdt) {
		$palettesManager->updatePdtCompo($id_lot_pdt_froid, $id_pdt);
		$logManager = new LogManager($cnx);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Changement de produit manuel sur id_lot_pdt_froid #'.$id_lot_pdt_froid.' et sur les compos correspondantes');
		$logManager->saveLog($log);
	}

    // Enregistrement du commentaire additionnel

	// Vérification des données
	if (strlen(trim($froid_commentaire)) == 0 || !isset($utilisateur) ||  !$utilisateur instanceof User) { exit; }

	$com = new Commentaire([]);
	$dateCom = date('Y-m-d H:i:s');
	$com->setDate($dateCom);
	$com->setId_user($utilisateur->getId());
	$com->setId_lot($id_lot);
	$com->setId_froid($froid->getId());
	$com->setCommentaire($froid_commentaire);
	$comManager = new CommentairesManager($cnx);

	$comManager->saveCommentaire($com);

    exit;

} // FIN mode


function modeCheckNumLotExiste()
{
    global $lotManger, $cnx, $id_lot;
    
    $lotsManager = new LotManager($cnx);
    $numlot = isset($_REQUEST['numlot']) ? trim($_REQUEST['numlot']) : '';
    
    if ($numlot == '') {
        exit;
    }

    // Si les 3 derniers caractèrs sont des chiffres (quantièmes), on les supprime
    if (preg_match('/^[0-9]*$/', (substr($numlot, -3)))) {
        $numlot = substr($numlot, 0, -3);
    }    
    echo $lotsManager->checkLotExiste($numlot) ? $id_lot : 0;

    exit;

} // FIN mode