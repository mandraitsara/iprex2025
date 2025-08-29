<?php
/**
------------------------------------------------------------------------
PAGE - ADMIN - Clients

Copyright (C) 2018 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */
require_once 'scripts/php/config.php';
require_once 'scripts/php/check_admin.php';

// 1 = En cours / 0 = Terminés
$statutShow = isset($_REQUEST['s']) ? intval(base64_decode($_REQUEST['s'])) : 1;

$h1     = 'Gestion des BL de lots de negoce';
$h1fa   = $statutShow == 1 ? 'fa-fw fa-sort fa-rotate-90' : 'fa-fw fa-sign-out-alt';

$h1.= $statutShow == 0 ? ' expédiés' : '';

include('includes/header.php');

$tiersManager = new TiersManager($cnx);

?>
<div class="container-fluid page-admin">
    <div class="alert alert-danger d-xl-none d-none d-md-block">
        <i class="fa fa-exclamation-circle text-28 mr-1 v-middle"></i>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher les filtres et les codes&hellip;
    </div>
    <div class="row margin-bottom-50">
        <div class="col-12 col-xl-10" id="listeLotsNegoce" data-statut="<?php echo $statutShow;?>"><i class="fa fa-spin fa-spinner fa-2x"></i></div>
        <div class="col-12 col-xl-2 boutons-droite text-right d-none d-md-block">
            <div class="alert alert-secondary">
                <a href="admin-lots-negoce-add.php" class="btn btn-info form-control text-left pl-3"><i class="fa fa-plus-square fa-lg mr-2"></i>Nouveau lot de négoce&hellip;</a>
                <a  href="admin-lots-negoce.php?s=<?php echo base64_encode($statutShow == 0 ? 1 : 0); ?>"  class="btn btn-dark form-control text-left pl-3 mt-2"><i class="fa fa-<?php echo $statutShow == 1 ? 'sign-out-alt' : 'sort fa-rotate-90'; ?> fa-lg mr-2"></i>Lots de négoce <?php echo $statutShow == 1 ? 'expédiés' : 'en cours'; ?></a>
            </div>
        </div>
    </div>
</div>
<?php
include('includes/modales/modal_lot_neg_produits.php');
include('includes/modales/modal_lot_edit.php');
include('includes/modales/modal_lot_docs.php');
include('includes/modales/modal_lot_incident.php');
include('includes/footer.php');