<?php
/**
------------------------------------------------------------------------
PAGE - Création de BL depuis le Front

Copyright (C) 2020 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2020 Intersed
@version   1.0
@since     2020 #confinement #télétravail #covid-19 #coronavirus

------------------------------------------------------------------------
*/
ini_set('display_errors',1);
require_once 'scripts/php/config.php';
global $iso_langue;
// On récupère les IDs de compos sélectionnées
$ids_compos_get     = isset($_REQUEST['c'])     ? base64_decode($_REQUEST['c'])             : '';
$id_lot_negoce      = isset($_REQUEST['ln'])    ? intval(base64_decode($_REQUEST['ln']))    : 0;
$id_bl              = isset($_REQUEST['idbl'])  ? intval($_REQUEST['idbl'])                 : 0;
$id_bl_admin        = isset($_REQUEST['i'])     ? intval(base64_decode($_REQUEST['i']))     : 0;

if ($id_bl == 0 && $id_bl_admin > 0) { $id_bl = $id_bl_admin; }

$ids_compos = explode(',', $ids_compos_get);

$bo         = isset($_REQUEST['bo']);

// Si gesCom désactivée et pas DEV, ou qu'on a pas d'ID de compos passées ni ID de BL
if ((!$gescom && !$utilisateur->isDev()) || (empty($ids_compos) && $id_bl == 0 &&  $id_lot_negoce == 0)) {
	header('Location: '.$_SERVER['HTTP_REFERER']);
	exit;
}

// Création du BL en base... d'après les ids_comps, s'il correspond déjà on retourne le BL déjà existant
$blManager      = new BlManager($cnx);
$paysManager    = new PaysManager($cnx);
$pays           = $paysManager->getListePays();


// Cas de création depuis lot de négoce
if ($id_bl == 0 && $id_lot_negoce > 0) {    
    $bl = $blManager->getOrCreateBlFromNegoce($id_lot_negoce);

    
    
} else {
	$bl = $id_bl > 0
		? $blManager->getBl($id_bl, true, true) // Compatibilité produits hors compos (négoce...)
		: $blManager->getOrCreateBlFromCompos($ids_compos);
        
}

$bl = $blManager->forcePalettesRegroupement($bl);

$css[] = 'css/rougetemp.css';

$typeH1 = $bl->isBt() ? 'transfert' : 'livraison';
$ifaH1  = $bl->isBt() ? 'contract'  : 'invoice';
$backH1 = $bl->isBt() ? 'bts'       : 'bls';

if ($bo) {
	$h1     = 'Edition du bon de '.$typeH1.' ' . $bl->getCode();
	$h1.= $bl->getStatut() == 1 ? ' (En attente)' : '';
	$h1.= '<a href="gc-'.$backH1.'.php" class="float-right btn btn-secondary btn-sm margin-right-5"><i class="fa fa-undo-alt gris-c mr-1"></i> Retour à la liste</a>';
	$h1fa   = 'fa-fw fa-file-'.$ifaH1;
	$classBody = 'bobl';
}

include('includes/header.php');

$tiersManager   = new TiersManager($cnx);
$languesManager = new LanguesManager($cnx);

$paramsClients = $bl->isBt() ? ['stk_type' => 2] : [];
$listeClients  = $tiersManager->getListeClients($paramsClients);
$transporteurs = $tiersManager->getListeTransporteurs();
$langues       = $languesManager->getListeLangues(['actif' => 1]);
$ids_langues = [];

foreach ($langues as $l) { $ids_langues[] = $l->getId(); }

// SI on est sur le Front...
if (!$bo) {
	// On a besoin des vues pour affichage du menu vues
	if (!isset($vueManager)) {
		$vueManager = new VueManager($cnx);
	}
	$vue_code = 'stk'; // On force l'affichage de la vue STK comme active
	$skipDocs = true;  // Pour ne pas afficher le bouton des documents (debbug)
	include('includes/_menu-vues.php');
	?>
    <h1 class="text-center">Bon de <?php echo $typeH1. ' '; echo $bl->getStatut() == 1 ? ' (En attente)' : ''; ?></h1><?php
} // FIN test front

$showQte = false;
foreach ($bl->getLignes() as $ligne) {
    
	if (!$ligne->getProduit() instanceof Produit) {
		$ligne->setProduit(new Produit([]));
	}
	if (intval($ligne->getProduit()->getVendu_piece()) == 1 || ($ligne->getQte() > 0 && $ligne->getNb_colis() == 0 && $ligne->getPoids() == 0)) {
		$showQte = true;
		break;
	}
}

$id_client_web  = $tiersManager->getId_client_web();
$web            = $id_client_web > 0 && $bl->getId_tiers_facturation() == $id_client_web;

if ($web) {
	$orderPrestashopManager = new OrdersPrestashopManager($cnx);
	$orderPrestashopManager->cleanIdOrderDetailsLignesBl();
}

$listeFrs = $tiersManager->getListeFournisseurs();
 ?>
<div class="container-fluid">
	<div class="row">
	    <div class="col-8">
			<table class="table table-blfact">
				<thead>
					<tr>
						<th>Code</th>
						<th>Désignation</th>
						<th class="text-center w-75px">Colis</th>
						<?php if ($showQte) { ?><th class="text-center w-150px">Qté</th><?php } ?>
						<th class="text-right padding-right-10 w-150px">Poids (Kg)</th>
						<th class="text-right padding-right-10 w-150px">Poids brut (Kg)</th>
                           <?php if (!$bl->isBt()) { ?>
						<th class="text-right padding-right-10 bl-chiffre-old w-150px">PU HT (€)</th>
						<th class="text-right padding-right-10 bl-chiffre-old w-150px">Total HT (€)</th>
                        <?php if ($web) { ?><th class="t-actions text-center w-50px">Web</th><?php } ?>
                        <?php } ?>
					</tr>
				</thead>
				<tbody>
                <?php
                $total_colis 	= 0;
                $total_colis_bl = 0;
                $id_palette 	= -1;
                $num_palette 	= 0;
                $id_poids_pal 	= 0;
                $total_poids 	= 0.0;
                $total_poids_bl = 0.0;
                $total_pu_bl 	= 0.0;
                $total_total_bl = 0.0;
                $total_palette_poids_palette_bl = 0.0;

                $produitsManager = new ProduitManager($cnx);
                $listeProduits = $produitsManager->getListeProduits();

                $poidsPalettesManager = new PoidsPaletteManager($cnx);
                $listePoidsPalettes = $poidsPalettesManager->getListePoidsPalettes(1); // 1 = Palettes et pas emballages/cartons

                $ids_produits = [];
                $id_pdt_web = $produitsManager->getIdProduitWeb();

                foreach ($bl->getLignes() as $ligne) {
                    $piece = false;
                    if ($ligne->getProduit() instanceof Produit) {                       
                        $piece = $ligne->getProduit()->isVendu_piece();

                        $ids_produits[(int)$ligne->getProduit()->getId()] = (int)$ligne->getProduit()->getId();
                    }
                    $id_pays = (int)$ligne->getId_pays() > 0 ? $ligne->getId_pays() : $blManager->getIdPaysFromLot($ligne->getId_lot());

                    // Si on a une unicité sur id_pdt + id_palette identique, on regroupe en cumulant toutes les valeurs (colis + poids)
                    if ($id_palette < 0) {
                        $id_palette 	= $ligne->getId_palette();
                        $num_palette 	= $ligne->getNumero_palette();
                        $id_poids_pal  = $ligne->getId_poids_palette();
                    }

                    $idpdt   = $ligne->getId_produit_bl() > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit();
                    $unicite =  'PAL'.$ligne->getId_palette().'PDT'.$idpdt.'LOT'.$ligne->getId_lot();

                    if ($ligne->getId_palette() != $id_palette && $id_palette > -1 && $ligne->getId_palette() > 0) {

                        $total_palette_poids_palette = $id_palette > 0 ? $poidsPalettesManager->getTotalPoidsPalette($id_palette) : $total_poids;
                        $total_poids_total = $total_poids + $total_palette_poids_palette;
                        $total_palette_poids_palette_bl+=$total_palette_poids_palette;

                        if ($id_palette > 0) { ?>
                            <tr class="total " data-id="<?php echo $id_palette; ?>">
                                <td></td>
                                <td class="text-right">
                                    <?php if (!$bl->isBt()) { ?>
                                    <select class="selectpicker float-left selectTypePoidsPalette" title="Type de palette">
                                        <?php  foreach ($listePoidsPalettes as $pp) { ?>
                                            <option value="<?php echo $pp->getId();?>" <?Php echo $pp->getId() == $id_poids_pal ? 'selected' : ''; ?>><?php echo $pp->getNom(); ?></option>
                                        <?php } ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-secondary float-left ml-1" data-toggle="modal" data-target="#modalEmballagesPalette" data-id-palette="<?php echo $id_palette; ?>">Emb.</button>

                                    <?php }
                                    echo 'Palette N°';
                                    ?>
                                    <input type="text" class="form-control numPalette w-50px text-center d-inline-block padding-2-10" value="<?php echo $num_palette; ?>"/>
                                </td>
                                <?php if ($showQte) { ?> <td></td><?php } ?>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="bl-chiffre-old"></td>
                                <td class="bl-chiffre-old"></td>
								<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
                                </tr>
                            <?php if (!$bl->isBt()) { ?>
                                <tr class="total totalPalette" data-id="<?php echo $id_palette; ?>">
                                    <td></td>
                                    <td class="text-right">Total poids net</td>
                                    <td class="text-center totalColis"><?php echo $total_colis > 0 ? $total_colis : '';?></td>
                                    <?php if ($showQte) { ?> <td></td><?php } ?>
                                    <td class="text-right totalPoids"><?php echo number_format($total_poids,3, '.', ' ');?></td>
                                    <td class="text-right"></td>
                                    <td class="bl-chiffre-old"></td>
                                    <td class="bl-chiffre-old"></td>

                                    <?php if ($web) { ?><td class="t-actions"></td><?php } ?>
                                </tr>
                                <tr class="total" data-id="<?php echo $id_palette; ?>">
                                    <td></td>
                                    <td class="text-right">Total poids emballages & palette</td>
                                    <td></td>
                                    <?php if ($showQte) { ?> <td></td><?php } ?>
                                    <td class="text-right totalPoids"><?php echo number_format($total_palette_poids_palette,3, '.', ' ');?></td>
                                    <td class="text-right"></td>
                                    <td class="bl-chiffre-old"></td>
                                    <td class="bl-chiffre-old"></td>
                                    <?php if ($web) { ?><td class="t-actions"></td><?php } ?>
                                </tr>
                                <tr class="total" data-id="<?php echo $id_palette; ?>">
                                    <td></td>
                                    <td class="text-right">Total poids brut</td>
                                    <td></td>
                                    <?php if ($showQte) { ?> <td></td><?php } ?>
                                    <td class="text-right totalPoids"><?php echo number_format($total_poids_total,3, '.', ' ');?></td>
                                    <td class="text-right"></td>
                                    <?php if (!$bl->isBt()) { ?>
                                        <td class="bl-chiffre-old"></td>
                                        <td class="bl-chiffre-old"></td>

                                    <?php } ?>
                                    <?php if ($web) { ?><td class="t-actions"></td><?php } ?>
                                </tr>
                                <?php
                            } // FIN test bl/bt
						} else { ?>
                            <tr class="total">
                                <td></td>
                                <td class="text-right">Total hors palettes</td>
                                <td class="text-center"><?php echo $total_colis > 0 ? $total_colis : ''; ?></td>
                                <?php if ($showQte) { ?> <td></td><?php } ?>
                                <td class="text-right totalPoids"><?php echo number_format($total_palette_poids_palette,3, '.', ' ');?></td>
                                <td class="text-right"></td>
                                <td class="bl-chiffre-old"></td>
                                <td class="bl-chiffre-old"></td>

                                <?php if ($web) { ?><td class="t-actions"></td><?php } ?>
                            </tr>
                        <?php } // FIN test palette 0 (hors stock) -  doit etre pris en compte dans la boucle mais non affiché
                        $num_palette 	= $ligne->getNumero_palette();
                        $id_palette 	= $ligne->getId_palette();
                        $id_poids_pal   = $ligne->getId_poids_palette();
                        $total_colis 	= 0;
                        $total_poids 	= 0.0;
                    }
                    $total_colis+= 		(int)$ligne->getNb_Colis();
                    $total_poids+= 		(float)$ligne->getPoids();
                    $total_colis_bl+= 	(int)$ligne->getNb_Colis();
                    $total_poids_bl+= 	(float)$ligne->getPoids();
                    $total_pu_bl+= 		(float)$ligne->getPu_ht();
                    $total_total_bl+= 	(float)$ligne->getTotal();
                    ?>
                    <tr data-id-ligne="<?php echo $ligne->getid(); ?>" class=" ligneBl lignePalette<?php echo $ligne->getId_palette();
                    ?> lignePdt<?php echo $ligne->getId_produit(); ?>" data-unicite="<?php echo $unicite; ?>" data-id-palette-ligne="<?php echo $ligne->getId_palette(); ?>">
                        <td class="codeProduit"><?php echo (int)$ligne->getCode() > 0 ? $ligne->getCode() : '-'; ?></td>
                        <td>
                            <?php
                            if ($web) {
                                $od = $orderPrestashopManager->getOrderLigneByIdLigneBl($ligne->getId());
                            } // FIN web
                            if ($ligne->getId_produit_bl() == 0 && $ligne->getId_produit() == 0 && $ligne->getDesignation() != '') {
                                echo $ligne->getDesignation();
                            } else if ($ligne->getId_produit() == $id_pdt_web) { ?>
                                <button type="button" class="btn btn-light text-left border text-uppercase form-control btnAddProduitBLForWeb" data-id-bll="<?php echo $ligne->getId(); ?>">
                                    Produit Web
                                </button>
                            <?php } else { ?>
                                <select class="selectpicker form-control show-tick selectProduitBl"  data-live-search="true" data-live-search-placeholder="Rechercher">
                                    <?php foreach ($listeProduits as $pdt) { ?>
                                        <option value="<?php echo $pdt->getId(); ?>"
                                            <?php
                                            if($pdt->getId() == $ligne->getId_produit_bl()) { echo 'selected'; }
                                            else if ($pdt->getId() == $ligne->getId_produit() && $ligne->getId_produit_bl() == 0) { echo 'selected'; };
                                            ?> data-subtext="<?php echo $pdt->getCode(); ?>"><?php echo $pdt->getNoms()[1]; ?></option>
                                    <?php }?>
                                </select>
                            <?php }
                            // Si une désignation a été forcée, on l'affiche
                            if ($ligne->getDesignation() != '') {
                                echo $ligne->getDesignation();
                            // Sinon, on prends le nom du produit
                            } else if ($ligne->getProduit() instanceof Produit) {
                                $noms = $ligne->getProduit()->getNoms();
                                // Boucle sur les langues actives
                                foreach ($ids_langues as $id_langue) { ?>
                                    <span class="nom_trads nom_trad_<?php echo $id_langue; ?>"><?php echo isset($noms[$id_langue]) ? $noms[$id_langue] : '<span class="text-danger">Traduction manquante !</span>'; ?></span>
                                    <?php
                                } // FIN Boucle sur les langues actives
                            } // FIN test désignation libre / nom du produit
                            // Si client web, on récup l'order_ligne si on l'a
                            if ($web && $od instanceof OrderDetailPrestashop) {
                              ?>
                                    <div class="bg-info text-white margin-top-2 padding-2-10 w-100pc texte-fin text-13">
                                        <i class="fa fa-globe mr-1"></i> <?php echo $od->getReference_order() . '<br>'.$od->getNom(); ?>
                                    </div>
                                <?php
                            } // FIN web
                            // Numéro de lot / origine
                            ?>
                            <div class="nomargin row <?php echo $ligne->getId_compo() == 0 ? 'd-none' : ''; ?>">

                                <div class="col-md-5 padding-0 mt-1">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text pl-2 pr-2 texte-fin text-12">Lot</span>
                                        </div>
                                        <input type="text" class="form-control w-150px text-13 d-inline-block padding-2-10 numlotLigne" value="<?php echo $ligne->getNumlot();?>" placeholder="N/A" />
                                    </div>
                                </div>
                                <div class="col-md-7 pr-0 mt-1">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text texte-fin text-12 pl-2 pr-2">Orig.</span>
                                        </div>
                                    <select class="form-control origLigne selectpicker lh-18 show-tick" data-style="text-13">
                                        <?php
                                        if ($id_pays == 0) {
                                            echo '<option value="0">N/A</option>';
                                        }
                                        foreach ($pays as $p) { ?>
                                            <option value="<?php echo $p->getId(); ?>" <?php echo $p->getId() == $id_pays ? 'selected' : ''; ?>><?php echo $p->getNom(); ?></option>
                                        <?php }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-md-12">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text texte-fin text-12 pl-2 pr-2">Fournisseur</span>
                                    </div>
                                    <select class="form-control idFrs selectpicker lh-18 show-tick" data-style="text-13" data-id-ligne="<?php echo $ligne->getId();?>">
                                        <?php
                                        foreach ($listeFrs as $frs) { ?>
                                            <option value="<?php echo $frs->getId();?>" <?php
                                            echo $ligne->getId_frs() == $frs->getId() ? 'selected' : '';
                                            echo (int)$ligne->getId_frs() == 0 && strtolower($frs->getNom()) == "profil export" ? 'selected' : '';
                                            ?>><?php echo $frs->getNom();?></option>
                                        <?php }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <?php if ($utilisateur->isDev()) { echo '<div class="bg-dark gris-e texte-fin text-11 padding-5 mt-1"><i class="fa fa-user-secret text-warning mr-1"></i> id_bl # '.$ligne->getId_bl().' &mdash; id_bl_ligne #'.$ligne->getId().' &mdash; id_compo #' . $ligne->getId_compo(). ' &mdash; ';
                            echo 'id_palette #'.$ligne->getId_palette(). ' &mdash; num_palette_force = ' . $ligne->getNum_palette(). ' &mdash; ';
                            echo $ligne->getProduit()->getVendu_piece() == 1 ? 'Vente à la pièce' : 'Vente au Kg';echo '</div>'; } ?>
                    </td>
                    <td class="text-center"><input type="text" class="form-control text-center nbColisLigne" placeholder="-" value="<?php echo $piece && !$web ? '' : $ligne->getNb_colis();?>"/></td>
                    <?php if ($showQte) { ?>
                        <td class="text-center"><input type="text" class="form-control text-center qteLigne <?php
                            echo $ligne->getProduit()->getVendu_piece() == 1 ? 'multiplicateurQte' : '';
                            ?>" value="<?php
                            echo $ligne->getQte() > 1 ? $ligne->getQte() : 1;?>" placeholder="-"/></td>
                    <?php } ?>
                    <td class="text-right"><input type="text" placeholder="0.000" class="form-control text-right poidsLigne <?php
                        echo $ligne->getProduit()->getVendu_piece() == 0 ? 'multiplicateurQte' : '';
                        ?>" value="<?php echo $ligne->getPoids() > 0 ? number_format($ligne->getPoids(),3, '.', '') : '';?>"/>
                    </td>
                    <td class="poids-brut text-right padding-top-10"><i class="fa fa-spin fa-spinner gris-c fa-sm"></i></td>
                    <?php if (!$bl->isBt()) { ?>
                    <td class="text-right bl-chiffre-old"><input type="text" class="form-control text-right puLigne <?php echo $ligne->getPu_ht() == 0 ? 'rougeTemp' : ''; ?>" placeholder="0.00" value="<?php echo $ligne->getPu_ht() > 0 ? number_format($ligne->getPu_ht(),2,'.', '') : '';?>" />
                    <input type="hidden" class="puLigneOld" value="<?php echo $ligne->getPu_ht(); ?>"/>
                    </td>
                    <td class="text-right bl-chiffre-old totalLigne"><?php echo number_format($ligne->getTotal(),2,'.', '');?></td>
                    <?php
                     }
					if ($web) { ?>
                        <td class="t-actions text-center">
                            <?php
                            $od = $orderPrestashopManager->getOrderLigneByIdLigneBl($ligne->getId());
                            if ($od instanceof OrderDetailPrestashop) { ?>
                                <button type="button" class="btn btn-danger btn-sm btnDesassocieWebLigne" data-id-od="<?php echo $od->getId(); ?>"><i class="fa fa-times"></i></button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-info btn-sm btnLigneWeb" data-id="<?php echo $ligne->getid(); ?>"><i class="fa fa-globe"></i></button>
                            <?php }
                            ?>
                        </td>
					<?php }
                       ?>
                </tr>
            <?php } // FIN boucle sur les lignes

    if ($id_palette > 0) {
        $total_palette_poids_palette = $poidsPalettesManager->getTotalPoidsPalette($id_palette);
        $total_poids_total = $total_poids + $total_palette_poids_palette;
        $total_palette_poids_palette_bl+=$total_palette_poids_palette;
    ?>
    <tr class="total <?php echo (int)$id_palette == 0 ? 'd-none' : ''; ?>" data-id="<?php echo $id_palette; ?>">
        <td></td>
        <td class="text-right">
			<?php if (!$bl->isBt()) { ?>
                <select class="selectpicker float-left selectTypePoidsPalette" title="Type de palette">
					<?php  foreach ($listePoidsPalettes as $pp) { ?>
                        <option value="<?php echo $pp->getId();?>" <?php echo $pp->getId() == $id_poids_pal ? 'selected' : ''; ?>><?php echo $pp->getNom(); ?></option>
					<?php } ?>
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary float-left ml-1" data-toggle="modal" data-target="#modalEmballagesPalette" data-id-palette="<?php echo $id_palette; ?>">Emb.</button>
			<?php }
            echo 'Palette N°';
            ?>
            <input type="text" class="form-control numPalette w-50px text-center d-inline-block padding-2-10" value="<?php echo $num_palette; ?>"/>
        </td>
        <td></td>
        <td></td>
        <td></td>
		<?php if ($showQte) { ?>
            <td></td>
		<?php } ?>
		<?php if (!$bl->isBt()) { ?>
            <td class="bl-chiffre-old"></td>
            <td class="bl-chiffre-old"></td>
		<?php } ?>
		<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
    </tr>
	<?php if (!$bl->isBt()) { ?>
        <tr class="total totalPalette" data-id="<?php echo $id_palette; ?>">
            <td></td>
            <td class="text-right">Total poids net</td>
            <td class="text-center totalColis"><?php echo $total_colis > 0 ? $total_colis : '';?></td>
			<?php if ($showQte) { ?>
                <td></td>
			<?php } ?>
            <td class="text-right totalPoids"><?php echo number_format($total_poids,3, '.', ' ');?></td>
            <td class="text-right"></td>
            <td class="bl-chiffre-old"></td>
            <td class="bl-chiffre-old"></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
        </tr>
        <tr class="total" data-id="<?php echo $id_palette; ?>">
            <td></td>
            <td class="text-right">Total poids emballages & palette</td>
            <td></td>
			<?php if ($showQte) { ?>
                <td></td>
			<?php } ?>
            <td class="text-right totalPoids"><?php echo number_format($total_palette_poids_palette,3, '.', ' ');?></td>
            <td class="text-right"></td>
            <td class="bl-chiffre-old"></td>
            <td class="bl-chiffre-old"></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
        </tr>
        <tr class="total" data-id="<?php echo $id_palette; ?>">
            <td></td>
            <td class="text-right">Total poids brut</td>
            <td></td>
			<?php if ($showQte) { ?>
                <td></td>
			<?php } ?>
            <td class="text-right totalPoids"><?php echo number_format($total_poids_total,3, '.', ' ');?></td>
            <td class="text-right"></td>
            <td class="bl-chiffre-old"></td>
            <td class="bl-chiffre-old"></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
        </tr>
        <?php } ?>
    <?php }	?>
            </tbody>
            <?php if (!$bl->isBt()) { ?>
            <tfoot>
            <tr class="ligneTotalBl">
                <td></td>
                <td class="text-right">Total de l'expédition net</td>
                <?php if ($showQte) { ?>
                    <td></td>
                <?php } ?>
                <td class="text-center totalColis"><?php echo $total_colis_bl > 0 ? $total_colis_bl : '';?></td>
                <td class="text-right totalPoids"><?php echo number_format($total_poids_bl,3, '.', ' ');?></td>
                <td></td>
                <td class="text-right bl-chiffre-old totalPu"></td>
                <td class="text-right bl-chiffre-old totalBl"><?php echo number_format($total_total_bl,2,'.', ' ');?></td>
                <?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            </tr>
            <tr class="">
                <td></td>
                <td class="text-right">Total poids emballages & palettes</td>
                <?php if ($showQte) { ?>
                    <td></td>
                <?php } ?>
                <td></td>
                <td class="text-right totalPoidsPalettes"><?php echo number_format($total_palette_poids_palette_bl,3, '.', ' ');?></td>
                <td></td>
                <td class="text-right bl-chiffre-old"></td>
                <td class="text-right bl-chiffre-old"></td>
                <?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            </tr>
            <tr class="">
                <td></td>
                <td class="text-right">Total de l'expédition brut</td>
                <?php if ($showQte) { ?>
                    <td></td>
                <?php } ?>
                <td></td>
                <td class="text-right totalPoidsTotal"><?php echo number_format($total_palette_poids_palette_bl + $total_poids_bl,3, '.', ' ');?></td>
                <td></td>
                <td class="text-right bl-chiffre-old"></td>
                <td class="text-right bl-chiffre-old"></td>

                <?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            </tr>
            </tfoot>
        <?php } ?>
        </table>
    </div>
	<div class="col-4">
        <form class="alert alert-secondary" id="formBl">
                <input type="hidden" name="mode" value="generePdfBl" />
                <input type="hidden" name="id" value="<?php echo $bl->getId(); ?>" />
                <input type="hidden" id="idClientWeb" value="<?php echo $id_client_web; ?>" />
                <input type="hidden" name="cc" value="0" />
                <input type="hidden" name="id_ctc" value="0" />
                <?php if (!$bl->isBt()) { ?>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Numéro de BL</span>
                    </div>
                    <div class="col-8">
                        <div class="float-right padding-5"><?php echo $bl->getChiffrage() == 0 ? 'Non chiffré' : 'Chiffré'; ?></div>
                        <span class="text-24 text-uppercase"><?php echo $bl->getCode(); ?></span>
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Numéro de commande</span>
                    </div>
                    <div class="col-8">
                        <input type="text" class="form-control text-24 text-uppercase"  placeholder="Numéro de commande" name="num_cmd" maxlength="32" id="num_cmd" value="<?php echo $bl->getNum_cmd(); ?>"/>
                    </div>
                </div>
                <?php } ?>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Date BL</span>
                    </div>
                    <div class="col-8">
                        <div class="input-group">
                            <input type="text" class="datepicker form-control text-20" placeholder="Sélectionnez..." id="date_bl" name="date_bl" value="<?php echo Outils::dateSqlToFr($bl->getDate()); ?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="far fa-calendar-alt gris-5 fa-lg"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Date livraison</span>
                    </div>
                    <div class="col-8">
                        <div class="input-group">
                            <input type="text" class="datepicker form-control" placeholder="Sélectionnez..." id="date_livraison" name="date_livraison" value="<?php echo Outils::dateSqlToFr($bl->getDate_livraison()); ?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="far fa-calendar-alt gris-5 fa-lg"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!$bl->isBt()) { ?>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Client facturé</span>
                    </div>
                    <div class="col-8">
                        <select class="selectpicker form-control show-tick" data-live-search="true" data-live-search-placeholder="Rechercher" name="id_t_fact">
                            <?php
                            foreach ($listeClients as $clt) { ?>
                                <option value="<?php echo $clt->getId(); ?>" <?php echo $clt->getId() == $bl->getId_tiers_facturation() ? 'selected' : ''; ?>><?php echo $clt->getNom(); ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7"><?php echo $bl->isBt() ? 'Depot' : 'Client'; ?> livré</span>
                    </div>
                    <div class="col-8">
                        <select class="selectpicker form-control show-tick" data-live-search="true" data-live-search-placeholder="Rechercher" name="id_t_livr" id="id_tiers_livraison">
                            <?php
                            foreach ($listeClients as $clt) { ?>
                                <option value="<?php echo $clt->getId(); ?>" <?php echo $clt->getId() == $bl->getId_tiers_livraison() ? 'selected' : ''; ?>><?php echo $clt->getNom(); ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Adresse</span>
                    </div>
                    <div class="col-8">
                        <select class="selectpicker form-control show-tick" data-live-search="true" data-live-search-placeholder="Rechercher" name="id_adresse" id="selectAdresses">
                        </select>
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Transporteur</span>
                    </div>
                    <div class="col-8">
                        <select class="selectpicker form-control show-tick" name="id_transp" <?php
                            echo count($transporteurs) > 1 ? 'title="Sélectionnez..."' : '' ?>>
                            <?php
                            foreach ($tiersManager->getListeTransporteurs() as $trans) { ?>
                                <option value="<?php echo $trans->getId(); ?>" <?php echo $trans->getId() == $bl->getId_tiers_transporteur() ? 'selected' : ''; ?>><?php echo $trans->getNom(); ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>
                </div>
                <?php if (!$bl->isBt()) { ?>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Langue</span>
                    </div>
                    <div class="col-8">
                        <?php
                        $id_client 	= $bl->getId_tiers_facturation();
                        $client 	= $tiersManager->getTiers($id_client);
                        if ($client instanceof Tiers) {
                            $iso_langue = strtolower($client->getLangue_iso());
                        }
                        ?>
                        <select class="selectpicker form-control show-tick" name="id_langue" id="id_langue">
                            <?php
                            foreach ($langues as $langue) { ?>
                                <option value="<?php echo $langue->getId(); ?>" <?php echo $iso_langue == strtolower($langue->getIso()) ? 'selected' : ''; ?>><?php echo $langue->getNom(); ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                <div class="row mb-1">
                    <div class="col-4 padding-top-10">
                        <span class="gris-7">Origine</span>
                    </div>
                    <div class="col-8">
                        <select class="form-control origBl selectpicker lh-18 show-tick" title="Changer pour toutes les lignes">
                            <?php
                            foreach ($pays as $p) { ?>
                                <option value="<?php echo $p->getId(); ?>"><?php echo $p->getNom(); ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <?php if (!$bl->isBt()) { ?>
                    <div class="col-8">
                        <div class="row mb-1">
                            <div class="col-6 padding-top-10">
                                <span class="gris-7">Regroupement</span>
                            </div>
                            <div class="col-6">
                                <select class="selectpicker form-control show-tick" name="regroupement" id="regroupement">
                                    <option value="0" <?php echo $bl->getRegroupement() == 0 ? 'selected' : ''; ?>>Non</option>
                                    <option value="1" <?php echo $bl->getRegroupement() == 1 ? 'selected' : ''; ?>>Oui</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="col-4 padding-top-10">
                    </div>
                    <?php } ?>
                    <input type="hidden" id="ids_produits" value="<?php echo implode(',', $ids_produits); ?>"/>
                    </div>
                    <div class="row justify-content-md-end mt-2">
                        <?php
                        $needSaveBtn = !in_array(Outils::getInfosClient()['nav'], ['Firefox', 'Chrome', 'Opera', 'Opera Next', 'Edge']);
                        $colXs = $needSaveBtn ? '6' : '4';
                        if ($bl->isBt()) { $colXs = 6; }
                        if ($needSaveBtn) { ?>
                            <div class="col-6">
                                <button type="button" class="btn btn-primary mb-2 form-control text-center btnSaveSafari">
                                    <i class="fa fa-fw fa-save mb-1"></i><br>Enregistrer modifs.
                                </button>
                            </div>
                        <?php } ?>

                        <?php if (!$bl->isBt()) { ?>
                        <div class="col-6 col-xl-<?php echo $colXs; ?>">
                            <button type="button" class="btn btn-warning mb-2 form-control text-center" id="btnAttenteBl">
                                <i class="fa fa-fw fa-hourglass-end mb-1"></i><br>Mettre en attente
                            </button>
                        </div>
                        <?php } ?>
                        <div class="col-6 col-xl-<?php echo $colXs; ?>">
                            <button type="button" class="btn btn-success mb-2 form-control text-center" id="btnGenereBl">
                                <i class="fa fa-fw fa-<?php echo $bl->isBt() ? 'check' : 'download'; ?> mb-1"></i><br><?php echo $bl->isBt() ? 'Regénérer le bon de transfert' : 'Générer le BL'; ?>
                            </button>
                        </div>
                        <?php if (!$bl->isBt()) { ?>
                        <div class="col-6 col-xl-<?php echo $colXs; ?>">
                            <button type="button" class="btn btn-info mb-2 form-control text-center" id="btnEnvoiBl" data-toggle="modal" data-target="#modalEnvoiMail">
                                <i class="fa fa-fw fa-paper-plane mb-1"></i><br>Générer et envoyer
                            </button>
                        </div>
                    <?php } ?>
                        <div class="col-6 col-xl-<?php echo $colXs; ?>">
                            <button type="button" class="btn btn-info form-control text-center btnBleuAjouterProduitBl">
                                <i class="fa fa-fw fa-plus-circle mb-1"></i><br>Ajouter un produit
                            </button>
                        </div>
                        <div class="col-6 col-xl-<?php echo $colXs; ?>">
                            <button type="button" class="btn btn-secondary form-control text-center" data-toggle="modal" data-target="#modalAddLigneBl">
                                <i class="fa fa-fw fa-plus-circle mb-1"></i><br>Ligne simple
                            </button>
                        </div>
                        <div class="col-6 col-xl-<?php echo $colXs; ?>">
                            <button type="button" class="btn btn-dark  form-control text-center" id="btnEtiquette">
                                <i class="fa fa-fw fa-print mb-1"></i><br>Imprim. étiquette
                            </button>
                            <iframe id="etiquetteFrame" name="imprimerEtiquette"></iframe>
                        </div>
                    </div>
					<a href="" target="_blank" download id="lienPdfBl" class="d-none"></a>
				</form>
			</div>
		</div>
	<div id="clavier_virtuel" class="clavier-stk"></div>
</div>
<iframe id="blFrame" name="imprimerBl"></iframe>
<?php
include('includes/modales/modal_emballages_palette.php');
include('includes/modales/modal_envoi_mail.php');
include('includes/modales/modal_add_ligne_bl.php');
include('includes/modales/modal_add_pdt_bl.php');
include('includes/modales/modal_info.php');
include_once('includes/footer.php');
