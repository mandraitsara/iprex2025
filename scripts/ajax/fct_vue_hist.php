<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax VUE HISTORIQUE
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

	$filtre_lot     = isset($_REQUEST['lot'])       ? trim(strtolower(strip_tags($_REQUEST['lot'])))        : '';
	$filtre_froid   = isset($_REQUEST['froid'])     ? trim(strtolower(strip_tags($_REQUEST['froid'])))      : '';
	$filtre_produit = isset($_REQUEST['produit'])   ? trim(strtolower(strip_tags($_REQUEST['produit'])))    : '';
	$filtre_date    = isset($_REQUEST['date'])      ? trim(strip_tags($_REQUEST['date']))                   : '';
	$filtre_palette = isset($_REQUEST['palette'])   ? intval(preg_replace("/[^0-9]/", "", $_REQUEST['palette'])) : 0;


	$colonne        = isset($_REQUEST['colonne'])   ? trim(strtolower($_REQUEST['colonne']))                : 'pdt';
	$sens           = isset($_REQUEST['sens'])      ? trim(strtolower($_REQUEST['sens']))                   : 'asc';

	$params = [];
	if ($filtre_lot     != '') { $params['lot']     = $filtre_lot;      }
	if ($filtre_froid   != '') { $params['froid']   = $filtre_froid;    }
	if ($filtre_produit != '') { $params['produit'] = $filtre_produit;  }
	if ($filtre_date    != '') { $params['date']    = $filtre_date;     }
	if ($filtre_palette  >  0) { $params['palette'] = $filtre_palette;  }

	// Préparation pagination (Ajax)
	$nbResultPpage      = 15;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode='.$mode;
	$filtresPagination .= $filtre_lot       != '' ? '&lot='    . $filtre_lot     : '';
	$filtresPagination .= $filtre_froid     != '' ? '&froid='  . $filtre_froid   : '';
	$filtresPagination .= $filtre_produit   != '' ? '&produit='. $filtre_produit : '';
	$filtresPagination .= $filtre_date      != '' ? '&date='   . $filtre_date    : '';
	$filtresPagination .= $filtre_palette   > 0   ? '&palette='. $filtre_palette : '';
	$start              = ($page-1) * $nbResultPpage;

	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;
	$params['colonne'] 	        = $colonne;
	$params['sens'] 	        = $sens;



	$liste_froids = $froidManager->getFroidsHistoriqueRecherche($params);

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
                    <th class="line-height-40">Code</th>
                    <th class="position-relative padding-left-50 tri-produits" data-sens="<?php echo $sens; ?>" data-tri-actif="<?php echo $colonne == 'pdt' ? 1 : 0; ?>"><i class="fa fa-sort-alpha-<?php echo $sens == 'asc' ? 'down' : 'up'; ?> text-30 <?php echo $colonne == 'pdt' ? 'text-info-light' : 'gris-9'; ?> position-absolute abs-left-15 abs-top-10 "></i> Produit</th>
                    <th class="position-relative padding-left-50 tri-lots" data-sens="<?php echo $sens; ?>" data-tri-actif="<?php echo $colonne == 'lot' ? 1 : 0; ?>"><i class="fa fa-sort-numeric-<?php echo $sens == 'asc' ? 'down' : 'up'; ?> text-30 <?php echo $colonne == 'lot' ? 'text-info-light' : 'gris-9'; ?> position-absolute abs-left-15 abs-top-10"></i> Lot</th>
                    <th>Traitement</th>
                    <th>Opération</th>
                    <th class="text-center">Palette</th>
                    <th class="text-center">Nb colis/blocs</th>
                    <th class="text-right pr-5">Poids total</th>
                    <th class="text-center">Entrée</th>
                    <th class="text-center">Sortie</th>
                    <th class="text-center">Etiquetage</th>
                </tr>
                </thead>
                <?php
				foreach ($liste_froids as $pdtFroid) {

					$date_entree = $pdtFroid->getFroid() instanceof Froid && $pdtFroid->getFroid()->getDate_entree() != ''
                        ? Outils::getDate_verbose($pdtFroid->getFroid()->getDate_entree(), false, ' - ', false) : '&mdash;';
					$date_sortie = $pdtFroid->getFroid() instanceof Froid && $pdtFroid->getFroid()->getDate_sortie() != ''
						? Outils::getDate_verbose($pdtFroid->getFroid()->getDate_sortie(), false, ' - ', false) : '&mdash;';

				    ?>
                    <tr>
                        <td><code class="gris-5 text-12"><?php echo $pdtFroid->getProduit()->getCode(). '/' . $pdtFroid->getId_lot_pdt_froid();?></code></td>
                        <td class="text-20"><?php echo $pdtFroid->getProduit()->getNom();?></td>
                        <td><span class="badge badge-secondary text-18" style="background-color: <?php
							// On calcule un code hexa pour la couleur basé sur l'ID froid, et on le rend plus foncé de 20% pour être sûr qu'il soit un minimum visible...
							$hexaLot = Outils::genereHexaCouleur($pdtFroid->getNumlot()); echo $hexaLot ;
							?>;color:<?php echo Outils::isCouleurHexaClaire($hexaLot) ? '#000' : '#fff'; ?>"><?php echo $pdtFroid->getNumlot() . $pdtFroid->getQuantieme();?></span></td>
                        <td><?php echo $pdtFroid->getNom_traitement(); ?></td>
                        <td><?php echo $pdtFroid->getCode_traitement(); ?></td>
                        <td class="text-center"><?php echo (int)$pdtFroid->getNumero_palette() > 0 ? $pdtFroid->getNumero_palette() : '&mdash;'; ?></td>
                        <td class="text-center"><?php echo $pdtFroid->getNb_colis(); ?></td>
                        <td class="text-center"><?php echo $pdtFroid->getPoids(); ?> kg</td>
                        <td class="text-center"><?php echo $date_entree; ?></td>
                        <td class="text-center"><?php echo $date_sortie; ?></td>
                        <td class="text-center"><?php echo $pdtFroid->getEtiquetage() == 1 ? '<i class="fa fa-check"></i>' : '&mdash;'; ?></td>
                    </tr>

                <?php
				} // FIN boucle sur les froidProduits
                ?>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-9">
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
            ?>
        </div>
        <div class="col-3">
            <?php
            // Totaux (v1.3)
			$totaux = $froidManager->getFroidsHistoriqueRechercheTotaux($params);
			if (is_array($totaux)) { ?>
                <p class="text-right">
                    <span class="badge badge-dark text-14 padding-5-10">
                        Total : <span class="text-20"><?php echo $totaux['nb_colis']; ?></span> colis/blocs  pour <span class="text-20"><?php echo number_format($totaux['poids'],3, ',', ' '); ?></span> kg.
                    </span>
                </p>
            <?php } // FIN test retour OK ?>


        </div>
    </div>
    <?php



    exit;

} // FIN mode