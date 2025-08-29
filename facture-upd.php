<?php
/**
------------------------------------------------------------------------
PAGE - Modif de facture/avoir

Copyright (C) 2020 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2020 Intersed
@version   1.0
@since     2020

------------------------------------------------------------------------
*/

ini_set('display_errors',1);
require_once 'scripts/php/config.php';

$facturesManager = new FacturesManager($cnx);

$id_facture = isset($_REQUEST['f'])         ? intval(base64_decode($_REQUEST['f']))  : 0;
$filters    = isset($_REQUEST['filters'])   ? $_REQUEST['filters']                   : '';
$facture    = $id_facture > 0               ? $facturesManager->getFacture($id_facture) : false;

if (!$facture instanceof Facture) {
	header('Location: '.$_SERVER['HTTP_REFERER']);
    exit;
}
if ($facture->getDate_compta() != '') {
	header('Location: '.$_SERVER['HTTP_REFERER']);
	exit;
}

$avoir = $facture->getMontant_ht() < 0;
$h1Txt = 'Edition de ';
$h1Txt.= $avoir ? 'l\'avoir ' : 'la facture ';
$h1Txt.= $facture->getNum_facture();

$css[] = 'css/rougetemp.css';
$h1     = $h1Txt;
$h1fa   = 'fa-fw fa-file-invoice-dollar';

include('includes/header.php');

$tiersManager = new TiersManager($cnx);
$client = $tiersManager->getTiers($facture->getId_tiers_facturation());
if (!$client instanceof Tiers) {
	$client = $tiersManager->getTiers($facture->getId_tiers_livraison());
}
if (!$client instanceof Tiers) { $client = new Tiers([]); } // Gestion des erreurs
$traductionsManager = new TraductionsManager($cnx);
$id_langue = $client->getId_langue();
?>
<form id="formUpdFacture">
    <input type="hidden" name="filters" value="<?php echo $filters; ?>"/>
    <div class="container-fluid">
        <div class="row">
            <input type="hidden" name="mode" value="updFacture" />
            <input type="hidden" name="id_facture" value="<?php echo $id_facture; ?>" />
            <div class="col-10">
                <table id="htmlLigne" class="d-none">
                    <tr class="ligne">
                        <td class="<?php echo $utilisateur->isDev() ? 't-actions' : 'd-none'; ?>"><code>-</code></td>
                        <td class="pl-0">
                            <textarea placeholder="Désignation" name="new_line_designation[]" class="form-control"></textarea>
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control text-right puht" name="new_line_pu_ht[]" placeholder="0" value="">
                                <span class="input-group-append">
                                    <span class="input-group-text">€</span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control text-right qte" name="new_line_qte[]" placeholder="0" value="">
                            </div>
                        </td>
                        <td class="t-actions total text-right padding-top-10 gris-5 text-16">&mdash; €</td>
                        <td class="t-actions">
                            <button type="button" class="btnSupprLigne btn btn-sm btn-danger">
                                <i class="fa fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </table>
                <table class="table admin rs" id="lignesUpdFacture">
                    <thead>
                        <tr>
                            <th class="<?php echo $utilisateur->isDev() ? 't-actions' : 'd-none'; ?>">ID</th>
                            <th>Désignation</th>
                            <th class="text-right padding-right-50 w-15pc nowrap">Prix unitaire</th>
                            <th class="text-right padding-right-50 w-15pc">Quantité</th>
                            <th class="text-right padding-right-50 w-15pc">Poids</th>
                            <th class="w-10pc t-actions text-right nowrap" <?php
                            echo !$utilisateur->isDev() && !$utilisateur->isAdmin() ? 'colspan="2"' : '';
                            ?>>Total HT</th>
                            <th class="w-10pc t-actions text-right nowrap <?php
                            echo !$utilisateur->isDev() && !$utilisateur->isAdmin() ? 'd-none' : '';
                            ?>">Prix d'achat</th>
                            <th class="w-5pc t-actions text-center">Supprimer</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $listeFrs = $tiersManager->getListeFournisseurs();
                    foreach ($facture->getLignes() as $ligne) {
                        $designation = '';
                        if ($ligne->getDesignation() != '') {
                            $designation = $ligne->getDesignation();
                        }
                        else {
                            if ($ligne->getProduit() instanceof Produit && $ligne->getProduit()->getId() > 0) {
                                $noms = $ligne->getProduit()->getNoms();
                                $designation = isset($noms[$id_langue]) ? $noms[$id_langue] : 'Traduction manquante !';
                            }
                            if ($avoir && (int)$ligne->getId_facture_avoir() > 0) {
                                $id_facture_avoir = $ligne->getId_facture_avoir();
                                $factureAvoir = $facturesManager->getFacture($id_facture_avoir, false);
                                $designation.= $designation != '' ? '<br>' : '';
                                $designation.= $traductionsManager->getTrad('avoirsurfact') . '' . $factureAvoir->getNum_facture();
                            } else if ($avoir) {
                                $designation.= $designation != '' ? '<br>' : '';
                                $designation.= $traductionsManager->getTrad('avoir');
                            } // FIN test désignation libre / nom du produit
                            if ((int)$ligne->getId_produit() > 0) {
                                $designation.=  $ligne->getNumlot() != '' ? '<br>'.$traductionsManager->getTrad('lot', $id_langue) . ' : ' .  $ligne->getNumlot() : '';
                                $designation.=  $ligne->getOrigine() != '' ? '<br>'.$traductionsManager->getTrad('origine', $id_langue) . ' : ' . $ligne->getOrigine() : '';
                                $designation.= $ligne->getProduit()->getEan13() != '' ? '<br>EAN : ' . $ligne->getProduit()->getEan13() : '';
                            }
                        }
                        $qte = $ligne->getProduit()->getVendu_piece() == 1 || $ligne->getProduit()->getID() == 0 ? $ligne->getQte() : number_format($ligne->getPoids() ,3,'.', ' ');
                        $unite =  $ligne->getProduit()->getVendu_piece() == 1 || $ligne->getProduit()->getID() == 0  ? 'pièces' : 'Kg';
                        $total = $ligne->getTotal();                        
                        ?>
                        <tr class="ligne lignePdt<?php echo $ligne->getId_produit();?> ligneFrs<?php echo $ligne->getId_frs(); ?>" data-piece="<?php echo $ligne->getProduit()->getVendu_piece(); ?>">
                            <td rowspan="2" class="<?php echo $utilisateur->isDev() ? 't-actions' : 'd-none'; ?>"><code><?php echo $ligne->getId(); ?></code></td>
                            <td rowspan="2" class="pl-0">
                                <textarea placeholder="Désignation" name="designation[<?php echo $ligne->getId(); ?>]" class="form-control text-13"><?php
                                    echo Outils::br2nl($designation);
                                    ?></textarea>
                            </td>
                            <td rowspan="2">
                                <div class="input-group">
                                    <input type="text" class="form-control text-right puht" name="pu_ht[<?php echo $ligne->getId(); ?>]" placeholder="0" value="<?php echo  number_format($ligne->getPu_ht() ,2,'.', ''); ?>">
                                    <span class="input-group-append">
                                        <span class="input-group-text">€</span>
                                    </span>
                                </div>
                            </td>
                            <td rowspan="2">
                                <div class="input-group">
                                    <input type="text" class="form-control text-right qte" name="qte[<?php echo $ligne->getId(); ?>]" placeholder="0" value="<?php echo $ligne->getQte(); ?>">
                                    <span class="input-group-append">
                                        <span class="input-group-text">Pièces</span>
                                    </span>
                                </div>
                            </td>
                            <td rowspan="2">
                                <div class="input-group">
                                    <input type="text" class="form-control text-right poids" name="poids[<?php echo $ligne->getId(); ?>]" placeholder="0" value="<?php echo number_format($ligne->getPoids() ,3,'.', '');; ?>">
                                    <span class="input-group-append">
                                        <span class="input-group-text">Kg</span>
                                    </span>
                                </div>
                            </td>
                            <td class="t-actions total text-right border-bottom-0 padding-top-10 gris-5 text-16" <?php
                            echo !$utilisateur->isDev() && !$utilisateur->isAdmin() ? 'colspan="2"' : '';
                            ?>><?php echo number_format(round($total,2),2,'.', ' '); ?> €                            
                            
                            
                        </td>
                        <td hidden class="t-actions text-right border-bottom-0 padding-top-10 gris-5 text-16"><input hidden class="totalHT" name="total[<?php echo $ligne->getId(); ?>]" value="<?php echo number_format(round($total,2),2,'.', ' '); ?>" >    </td>
                            <td class="t-actions text-right border-bottom-0 <?php
                            echo !$utilisateur->isDev() && !$utilisateur->isAdmin() ? 'd-none' : '';
                            ?>"><input type="text" class="form-control text-right paLigne" placeholder="0.00" value="<?php echo $ligne->getPa_ht() > 0 ? number_format($ligne->getPa_ht(),2,'.', '') : ''?>" data-id-ligne="<?php echo $ligne->getId(); ?>" data-id-produit="<?php echo $ligne->getId_produit(); ?>"/>
                                <input type="hidden" class="paLigneOld" value="<?php echo $ligne->getPa_ht(); ?>"/>
                            </td>
                            <td class="t-actions border-bottom-0">
                                <button type="button" class="btnSupprLigne btn btn-sm btn-danger">
                                    <i class="fa fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="t-actions border-top-0 text-left">
                                <div class="input-group">
                                    <span class="input-group-prepend <?php echo !$utilisateur->isDev() && !$utilisateur->isAdmin() ? 'd-none' : ''; ?>">
                                        <span class="input-group-text texte-fin t-11">Fournisseur</span>
                                    </span>
                                    <select class="form-control selectpicker idfrs" data-id-ligne="<?php echo $ligne->getId();?>">
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
                            </td>
                        </tr>
                    <?php }
                    ?>
                    </tbody>
                    <tfoot class="<?php echo empty($facture->getLignes()) ? '' : 'hid'; ?>">
                        <tr>
                            <td colspan="7" class="padding-50 bg-c text-center texte-fin text-28 gris-9">
                                <i class="fa fa-exclamation-circle mb-2"></i><p class="mb-0">Facture vide</p>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-2">
                <div class="alert alert-dark">
                    <div class="input-group">
                        <span class="input-group-prepend">
                            <span class="input-group-text texte-fin text-12">Date</span>
                        </span>
                        <input type="text" name="date" class="form-control datepicker text-center" placeholder="jj/mm/aaaa" value="<?php echo Outils::dateSqlToFr($facture->getDate()); ?>"/>
                    </div>
                    <div class="input-group mt-2">
                        <span class="input-group-prepend">
                            <span class="input-group-text texte-fin text-12">Commande</span>
                        </span>
                        <input type="text" name="num_cmd" class="form-control text-center" placeholder="N° de commande" value="<?php echo $facture->getNum_cmd(); ?>"/>
                    </div>
                    <a href="gc-factures.php<?php echo '?filters='.$filters; ?>" class="btn btn-secondary form-control text-left mt-2 btnAnnuler"><i class="fa fa-undo fa-fw mr-2"></i>Annuler</a>
                    <button type="button" class="btn btn-secondary form-control text-left mt-2 btnRegenere"><i class="fa fa-sync-alt fa-fw mr-2"></i>Regénérer le PDF</button>
                    <button type="button" class="btn btn-success form-control text-left mt-2 btnUpdFacture"><i class="fa fa-save fa-fw mr-2"></i>Enregistrer</button>
                </div>
                <div class="alert alert-warning mt-3 texte-fin text-12 hid" id="infoNewLigne">
                    <i class="fa fa-info-circle mr-1"></i>
                    Attention, l'ajout de nouvelles lignes ne peut en aucun cas impacter le contenu du stock ! Ces lignes de facturation sont libres. Pour toute relation avec le stock produit, frais, de négoce ou la gestion de TVA, créez un BL avec les produits correspondants.
                </div>
            </div>
        </div>
    </div>
</form>
<?php
include_once('includes/footer.php');
