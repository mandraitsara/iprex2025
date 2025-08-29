<?php
/**
------------------------------------------------------------------------
PAGE - ADMIN - Gescom - Factures

Copyright (C) 2020 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2020 Intersed
@version   1.0
@since     2020

------------------------------------------------------------------------
 */
require_once 'scripts/php/config.php';
require_once 'scripts/php/check_admin.php';

$h1     = 'Factures';
$h1fa   = 'fa-fw fa-file-invoice-dollar';

$facturesManager = new FacturesManager($cnx);

$id_facture = isset($_REQUEST['i'])         ? intval(base64_decode($_REQUEST['i'])) : 0;
$filters    = isset($_REQUEST['filters'])   ? base64_decode($_REQUEST['filters'])   : '';

$filter_page        = 1;
$filter_date_du     = '';
$filter_date_au     = '';
$filter_numfact     = '';
$filter_numcmd      = '';
$filter_factavoirs  = '';
$filter_id_client   = '';
$filter_reglee      = '';

if ($filters != '') {
    parse_str($filters, $output);
    $filter_page =isset($output['page']) ? $output['page'] : 1;
    $filter_date_du =isset($output['date_du']) ? $output['date_du'] : '';
	$filter_date_au =isset($output['date_au']) ? $output['date_au'] : '';
	$filter_numfact =isset($output['numfact']) ? $output['numfact'] : '';
	$filter_numcmd =isset($output['numcmd']) ? $output['numcmd'] : '';
	$filter_factavoirs =isset($output['factavoirs']) ? $output['factavoirs'] : '';
	$filter_id_client =isset($output['id_client']) ? $output['id_client'] : '';
	$filter_reglee =isset($output['reglee']) ? $output['reglee'] : '';
	$id_facture =isset($output['id']) ? intval($output['id']) : '';
}

if ($id_facture > 0) {
    $facture = $facturesManager->getFacture($id_facture);
    if ($facture instanceof Facture) {
        $h1 = 'Facture ' . $facture->getNum_facture();
    }
}

include('includes/header.php');
?>
<div class="container-fluid page-admin">
    <form class="row mb-3" id="filtres">
        <input type="hidden" name="mode" value="getListeFactures"/>
        <input type="hidden" name="page" value="<?php echo $filter_page; ?>"/>
        <input type="hidden" name="id" value="<?php echo $id_facture; ?>"/>
        <div class="col-10">
            <div class="row">
                <div class="col-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Du</span>
                        </div>
                        <input type="text" class="form-control datepicker border-right-0" placeholder="Date" name="date_du" value="<?php
                        if ($filter_date_du != '') {
                            echo $filter_date_du;
                        } else {
							// par défaut, au chargement, on affiche les facture qui ont moins de 3 mois
							$dt = new DateTime(date('Y-m-d'));
							$dt->modify('-3 month');

							if ($dt->format('Y-m-d') < '2021-11-01') {
								$dt = new DateTime(date('2021-11-01'));
							}
							echo $id_facture > 0 ? '' : $dt->format('d/m/Y');
                        }
                        ?>">
                        <div class="input-group-prepend">
                            <span class="input-group-text">au</span>
                        </div>
                        <input type="text" class="form-control datepicker" placeholder="Date" name="date_au" value="<?php echo $filter_date_au; ?>">
                    </div>
                </div>
                <div class="col-1">
                    <input type="text" class="form-control" placeholder="Numéro" name="numfact" value="<?php
                    if (isset($facture)) {
						echo $facture instanceof Facture ? $facture->getNum_facture() : '';
                    } else if ($filter_numfact != '') {
                        echo $filter_numfact;
					}?>">
                </div>
                <div class="col-1">
                    <input type="text" class="form-control" placeholder="Commande" name="numcmd" value="<?php echo $filter_numcmd; ?>">
                </div>
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-address-card gris-9"></i></span>
                        </div>
                        <select class="selectpicker form-control show-tick" name="id_client[]" title="Client" data-live-search="true"  multiple   data-selected-text-format = "count > 1" data-live-search-placeholder="Rechercher">
                            <option value="0">Tous</option>
                            <option data-divider="true"></option>
							<?php
							$tiersManager = new TiersManager($cnx);
							$listeClients = $tiersManager->getListeClients([]);
							if (is_array($listeClients)) {
								foreach ($listeClients as $clt) { ?>
                                    <option value="<?php echo $clt->getId(); ?>" <?php
                                    echo $filter_id_client ==  $clt->getId() ? 'selected' : '';
                                    ?>><?php echo $clt->getNom(); ?></option>
								<?php }
							}
							?>
                        </select>
                    </div>
                </div>
                <div class="col-2">
                    <select class="selectpicker form-control show-tick" name="reglee" title="Règlement">
                        <option value="-1" selected title="Règlement">Toutes</option>
                        <option data-divider="true"></option>
                        <option value="1" <?php echo $filter_reglee == '1' ? 'selected' : ''; ?>>Réglées</option>
                        <option value="0" <?php echo $filter_reglee == '0' ? 'selected' : ''; ?>>Non réglées</option>
                    </select>
                </div>
                <div class="col-2">
                    <input type="hidden" name="nb_result_p_page" value="25"/>
                    <select class="selectpicker form-control show-tick" name="factavoirs" title="Factures & avoirs">
                        <option value="">Factures & avoirs</option>
                        <option data-divider="true"></option>
                        <option value="f" <?php echo $filter_factavoirs == 'f' ? 'selected' : ''; ?>>Factures</option>
                        <option value="a" <?php echo $filter_factavoirs == 'a' ? 'selected' : ''; ?>>Avoirs</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-2 boutons-droite text-right">
            <div class="row">
                <div class="col-8">
                    <button type="button" class="btn btn-info form-control btnRecherche"><i class="fa fa-search mr-1 fa-fw"></i> Rechercher&hellip;</button>
                </div>
                <div class="col-4">
                    <a href="gc-factures.php" class="btn btn-secondary form-control"><i class="fa fa-backspace mr-1 fa-fw"></i></a>
                </div>
            </div>
        </div>
    </form>
    <div class="row">
        <div class="col-12 col-xl-10" id="listeFactures"><i class="fa fa-spin fa-spinner fa-2x"></i></div>
        <div class="col-12 col-xl-2 text-right boutons-droite">
            <div class="alert alert-secondary">
                <a href="gc-facture-creation.php" class="btn btn-info form-control text-left pl-3 btnCreerBl"><i class="fa fa-plus-circle fa-fw fa-lg mr-2"></i>Nouvelle facture&hellip;</a>
                <span class="btn btn-info form-control text-left pl-3 mt-2" data-toggle="modal" data-target="#modalAvoir"><i class="fa fa-plus-circle fa-fw fa-lg mr-2"></i>Créer un avoir&hellip;</span>
                <a href="admin-modes-reglement.php" class="btn btn-dark form-control text-left pl-3 mt-2"><i class="fa fa-money-bill fa-fw fa-lg mr-2"></i>Modes de règlements&hellip;</a>
                <a href="gc-taxes.php" class="btn btn-dark form-control text-left pl-3 mt-2"><i class="fa fa-university fa-fw fa-lg mr-2"></i>TVA&hellip;</a>
                <span class="btn btn-dark form-control text-left pl-3 mt-2"  data-toggle="modal" data-target="#modalInterbev"><i class="fa fa-file-pdf fa-fw fa-lg mr-2"></i>Interbev mensuel&hellip;</span><a href="" target="_blank" download id="lienPdf"></a>
                <a href="gc-all.php" class="btn btn-secondary form-control text-left pl-3 mt-2"><i class="fa fa-file-invoice-dollar fa-fw fa-lg mr-2"></i>Tous les documents&hellip;</a>
            </div>
        </div>
    </div>
</div>
<?php
include('includes/modales/modal_envoi_mail.php');
include('includes/modales/modal_reglement_facture.php');
include('includes/modales/modal_facture_frais.php');
include('includes/modales/modal_interbev.php');
include('includes/modales/modal_avoir.php');
include('includes/footer.php');