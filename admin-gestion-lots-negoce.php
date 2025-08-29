<?php
/**
------------------------------------------------------------------------
PAGE - ADMIN - Lots

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
$statutShow = isset($_REQUEST['ng']) ? intval(base64_decode($_REQUEST['ng'])) : 1;

$h1 = 'Liste des lots negoce';
$h1.= $statutShow == 1 ? ' en cours' : ' terminés';
$h1fa = $statutShow == 1 ? 'fa-fw fa-clock' : 'fa-fw fa-flag-checkered';
$validationsManager = new ValidationManager($cnx);
$nbAvalider = $validationsManager->getNbAValider();

include('includes/header.php');

if (isset($_REQUEST['erbz'])) { ?>
    <div class="alert alert-danger alert-dismissible fade show margin-top--8 margin-bottom-8" role="alert">
        <i class="fa fa-exclamation-triangle mr-1"></i> <strong>ATTENTION ! Le lot n'a pas été envoyé vers Bizeba.</strong> <span class="texte-fin ml-2 text-12">Ceci peut-être dû à des données incomplètes (date d'abattage, numéro d'agrément)... Vous pouvez renvoyer un lot vers Bizerba depuis sa fenêtre "Détails".</span>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php } // FIN message d'erreur Bizerba
?>
<div class="container-fluid page-admin">
    <div class="row">
        <div class="col-12 col-xl-10" id="listeLots" data-statut="<?php echo $statutShow;?>"><i class="fa fa-spin fa-spinner fa-2x"></i></div>
        <div class="col-12 col-xl-2 text-right boutons-droite">
            <a href="admin-lots-negoce-add.php?b=<?php echo base64_encode($statutShow); ?>" class="btn btn-info form-control text-left pl-3"><i class="fa fa-plus-square fa-lg mr-2"></i> Créer un nouveau lot negoce&hellip;</a>
            <div class="alert alert-secondary margin-top-15">            
            <?php if ($statutShow == 0) {?>
                <a href="admin-gestion-lots-negoce.phps=<?php echo base64_encode(1); ?>" class="btn btn-dark mt-2 form-control text-left pl-3"><i class="fa fa-clock fa-lg mr-2"></i> Voir les lots en cours</a>
            <?php } else {?>
                <a href="admin-lots-negoce.php?s=<?php echo base64_encode(0); ?>" class="btn btn-dark mt-2 form-control text-left pl-3"><i class="fa fa-flag-checkered fa-lg mr-2"></i> Voir les lots terminés</a>
            <?php } ?>
                <div class="input-group mt-2">
                    <input type="text" class="form-control" placeholder="Rechercher un lot..." id="recherche_numlot" value=""/>
                    <div class="input-group-append">
                        <span class="input-group-text pointeur"><i class="fa fa-search gris-5 fa-lg"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include('includes/modales/modal_lot_info.php'); //pour le modal de details.
//include('includes/modales/modal_lot_edit.php');
// include('includes/modales/modal_lot_docs.php');
// include('includes/modales/modal_lot_poids.php');
// include('includes/modales/modal_lot_incident.php');
// include('includes/footer.php');