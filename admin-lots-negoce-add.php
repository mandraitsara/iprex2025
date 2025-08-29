<?php
/**
------------------------------------------------------------------------
PAGE - ADMIN - Lots - Créations

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

$h1     = 'Nouveau lot de négoce';
$h1fa   = 'fa-fw fa-sort fa-rotate-90';

include('includes/header.php');

$abattoirManager    = new AbattoirManager($cnx);
$paysManager        = new PaysManager($cnx);
$especesManager     = new ProduitEspecesManager($cnx);
$produitManager = new ProduitManager($cnx); 
?>
<div class="container-fluid">
    <div class="row justify-content-md-center padding-top-25">
        <div class="col-12 col-md-10 col-lg-6 col-xl-5 alert alert-secondary">
            <h2 class="bb-c pb-1"><i class="fa fa-box gris-7 mr-2"></i> Création d'un nouveau lot Negoce&hellip;</h2>
            <form id="formLot" action="scripts/ajax/fct_lots_negoce.php" method="post">
                <input type="hidden" name="mode" value="addLotNegoce"/>
                <div class="row mt-4 mb-3">
                   <div class="col-9">
                       <div class="input-group input-group-lg mb-3">
                           <div class="input-group-prepend">
                               <span class="input-group-text"><i class="fa fa-edit"></i></span>
                           </div>
                           <input type="text" class="form-control text-24 text-uppercase"  placeholder="Numero BL" name="num_bl" maxlength="50" id="num_bl"/>
                           <div class="invalid-feedback">Ce numéro de lot est invalide !</div>
                           <?php
                           ?>
                       </div>
                   </div>
                   <div class="col-3">
                       <div class="text-center">
                           <button type="button" class="btn btn-lg btn-success btnAddLot"><i class="fa fa-check mr-1"></i> Créer</button>
                       </div>
                   </div>
                </div>
				<?php if (isset($_REQUEST['e'])) { ?>
                    <div class="alert alert-danger"><i class="fa fa-exclamation-triangle mr-4 fa-2x v-middle"></i><span>Création du lot <?php echo trim(strtoupper(strip_tags($_REQUEST['e']))); ?> impossible </span></div>
				<?php }?>
                <div class="row">
                    <div class="col">
                        <div class="alert alert-dark">                            
                            <div class="row">
                                <div class="col-5">
                                    <label class="pt-1 gris-5">
                                        <span class="fa-stack text-14 gris-9 mr-1">
                                          <i class="fas fa-circle fa-stack-2x"></i>
                                          <i class="fas fa-industry fa-stack-1x fa-inverse text-12 gris-e"></i>
                                        </span>Fournisseur :</label>
                                </div>
                                <div class="col-7">
                                    <select class="selectpicker show-tick form-control" id="fournisseur" name="id_fournisseur" title="Sélectionnez...">
										<?php
										$tiersManager = new TiersManager($cnx);
										foreach ($tiersManager->getListeFournisseurs([]) as $frs) { ?>
                                            <option value="<?php echo $frs->getId(); ?>"><?php echo $frs->getNom(); ?></option>
										<?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5">
                                    <label class="pt-1 gris-5">
                                        <span class="fa-stack text-14 gris-9 mr-1">
                                          <i class="fas fa-circle fa-stack-2x"></i>
                                          <i class="fas fa-truck fa-flip-horizontal fa-stack-1x fa-inverse text-12 gris-e"></i>
                                    </span>Date de récéption :</label>
                                </div>
                                <div class="col-7">
                                    <div class="input-group">
                                        <input type="text" id="date_reception" class="datepicker form-control" placeholder="Sélectionnez..." name="date_reception"/>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="far fa-calendar-alt gris-5 fa-lg"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>                                            
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                           

                            <?php
							$configManager = new ConfigManager($cnx);
							$config_bizerba_actif = $configManager->getConfig('biz_actif');
							if (!$config_bizerba_actif instanceof Config || intval($config_bizerba_actif->getValeur()) != 1) { ?>
                                <div class="alert alert-danger mt-2 mb-0">
                                    <i class="fa fa-exclamation-triangle mr-1"></i> <strong class="mr-1">ATTENTION</strong> L'envoi des fichers vers BizTrack est désactivé !
                                </div>
                            <?php }  ?>
                        </div>

                        <?php if(isset($_REQUEST['b'])) {
							$statutShow = intval(base64_decode($_REQUEST['b']));
					        $txtLien = $statutShow == 1 ? ' en cours' : ' terminés';
							?>
                            <a href="admin-lots.php?s=<?php echo
                                base64_encode($statutShow); ?>" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Annuler et revenir à la liste des lots <?php
                                echo $txtLien; ?></a>
                        <?php } ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
include('includes/footer.php');