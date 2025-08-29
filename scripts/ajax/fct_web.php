<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax WEB
------------------------------------------------------*/
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning

// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';
// Instanciation des Managers
$prestaShopOrdersManager = new OrdersPrestashopManager($cnx);
$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

// Retourne la liste des commandes web
function modeGetListeCommandes() {

	global $prestaShopOrdersManager;
	$prestaShopOrdersManager->cleanIdOrderDetailsLignesBl();
	$params = [
		'traites' => false // On ne prends que celles qui ne sont pas traitées
    ];
	$liste = $prestaShopOrdersManager->getListeOrdersPrestashop($params);
	if (empty($liste)) { ?>
		<div class="alert alert-info">
			Aucune commande web à traiter...
		</div>
	<?php  } else { ?>
		<table class="table table-web admin table-v-middle">
            <thead>
            <tr>
                <th>Date</th>
                <th class="text-center nowrap">ID Order</th>
                <th class="text-center">Référence</th>
                <th>Client</th>
                <th>Transporteur</th>
                <th class="text-right nowrap">Réductions TTC</th>
                <th class="text-right nowrap">Livraison TTC</th>
                <th class="text-right nowrap">Total HT</th>
                <th class="text-right nowrap">Total TTC</th>
                <th class="t-actions w-mini-admin-cell text-center">BL</th>
                <th class="t-actions w-mini-admin-cell text-center">Traitée</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($liste as $order) {
                $nbPdts = is_array($order->getOrder_details()) ? count($order->getOrder_details()) : 0;
                $nbPdtsTraites = $prestaShopOrdersManager->getNbLignesTraitees($order);
                ?>
                <tr class="order" data-id="<?php echo $order->getId(); ?>">
                    <td><?php echo Outils::dateSqlToFr($order->getDate_facture()); ?></td>
                    <td class="text-center"><?php echo $order->getId_order(); ?></td>
                    <td class="text-center"><?php echo $order->getReference(); ?></td>
                    <td><?php echo $order->getNom_client(); ?></td>
                    <td><?php echo $order->getTransporteur(); ?></td>
                    <td class="text-right"><?php echo $order->getReductions_ttc() == 0 ? '-' : number_format($order->getReductions_ttc(),2,'.', ' ') . ' €'; ?></td>
                    <td class="text-right"><?php echo $order->getLivraison_ttc() == 0 ? '-' : number_format($order->getLivraison_ttc(),2,'.', ' ') . ' €'; ?></td>
                    <td class="text-right"><?php echo number_format($order->getTotal_ht(),2,'.', ' '); ?> €</td>
                    <td class="text-right"><?php echo number_format($order->getTotal_ttc(),2,'.', ' '); ?> €</td>
                    <td></td>
                    <td class="t-actions text-center">
                        <button type="button" class="btn btn-sm <?php
                        echo $nbPdtsTraites < $nbPdts ? 'btn-secondary disabled' :  'btn-info btnTraitee'; ?>" <?php
                        echo $nbPdtsTraites < $nbPdts ? 'disabled' : ''; ?>><i class="fa fa-fw fa-<?php
                        echo$nbPdtsTraites < $nbPdts ? 'ban' : 'check';	?>"></i></button>
                    </td>
                </tr>
                <?php
                // Boucle sur les lignes
                if (empty($order->getOrder_details())) { ?>
                    <tr class="order-detail"><td colspan="9" class="text-center">Aucun produit !</td></tr>
                <?php } else {
                    $nbOd = count($order->getOrder_details());
                    foreach ($order->getOrder_details() as $od) {
                        $od_livraison =  round($order->getLivraison_ttc() / $nbOd,2);
                        $od_reductions =  round($order->getReductions_ttc() / $nbOd,2);
                        if ($od->getQte() == 0) { $od->setQte(1); }
                        $remise_totale_par_article = round(($order->getReductions_ht() / $nbOd),2);
                        $remise_unitaire_par_article = round(($remise_totale_par_article / $od->getQte()),2);
                        $prix_remise_ht = $od->getPu_ht() - $remise_unitaire_par_article;
                        $tva_taux = (($od->getPu_ttc() / $od->getPu_ht()) -1) * 100;
                        $prix_remise_ttc = $prix_remise_ht * (1+($tva_taux/100));
                        ?>
                        <tr class="order-detail" data-id-order="<?php echo $order->getId_order(); ?>" data-id="<?php echo $od->getId(); ?>">
                            <td colspan="4"><?php echo $od->getNom(); ?></td>
                            <td>Quantité : <?php echo $od->getQte(); ?>
                                <span class="ml-3 texte-fin">PU HT :</span> <?php
                                if ($order->getReductions_ht() == 0) {
                                    echo number_format($od->getPu_ht(),2, '.', ' ') . ' €';
                                } else {
                                    echo '<span class="texte-fin prix-barre d-inline-block mr-2">'.number_format($od->getPu_ht(),2, '.', ' ') . ' €</span> ' . number_format($prix_remise_ht,2, '.', ' ') . ' €';
                                }
                                ?>
                            </td>
                            <td class="text-right"><?php echo $od_reductions == 0 ? '' : number_format($od_reductions,2, '.', ' ') . ' €'; ?></td>
                            <td class="text-right"><?php echo $od_livraison == 0 ? '' : number_format($od_livraison,2, '.', ' ') . ' €'; ?></td>
                            <td class="text-right nowrap"><?php
                               /*
                               *  PU_HT remisé :
                               * Remise totale par article = total remise HT de la commande / nb articles commande
                               * Remise unitaire par article = Remise totale par article / quantité
                               * Prix remisé =  PU avant remise - Remise unitaire par article
                               */
                                if ($order->getReductions_ht() == 0) {
                                    echo number_format($od->getPu_ht() * $od->getQte(),2, '.', ' ') . ' €';
                                } else {
                                    echo '<span class="texte-fin prix-barre d-inline-block mr-2">'.number_format($od->getPu_ht() * $od->getQte(),2, '.', ' ') . ' €</span> ' . number_format($prix_remise_ht * $od->getQte(),2, '.', ' ') . ' €';
                                }
                                ?></td>
                            <td class="text-right nowrap"><?php
                                if ($order->getReductions_ht() == 0) {
                                    echo number_format($od->getPu_ttc() * $od->getQte(),2, '.', ' ') . ' €';
                                } else {
                                    echo '<span class="texte-fin prix-barre d-inline-block mr-2">'.number_format($od->getPu_ttc() * $od->getQte(),2, '.', ' ') . ' €</span> ' . number_format($prix_remise_ttc * $od->getQte() ,2, '.', ' ') . ' €';
                                }
                                ?></td>
                            <td class="text-center nowrap">
                                <?php
                                if ($od->getId_bl_ligne() > 0) { ?>
                                    <a class="text-info" href="bl-addupd.php?bo&i=<?php echo base64_encode($od->getId_bl()); ?>">
                                        <?php echo $od->getNum_bl(); ?></a>
                                    <span class="badge badge-danger pointeur btnDesassocieLigneBl" data-id-od="<?php echo $od->getId(); ?>"><i class="fa fa-times"></i></span>
                                <?php } else { ?>
                                    <input type="checkbox" class="icheck">
                                <?php }
                                ?>
                            </td>
                            <td></td>
                        </tr>
                    <?php }
                }
            } // FIN BOUCLE orders
            ?>
            </tbody>
		</table>
	<?php }
	exit;

} // FIN mode

function modeOrderTraitee() {

	global $prestaShopOrdersManager, $cnx;
	$logsManager = new LogManager($cnx);
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('ERREUR IDENTIFICATION ID 0 !');}
	$order = $prestaShopOrdersManager->getOrdersPrestashop($id);
	if (!$order instanceof OrderPrestashop) { exit('ERREUR INSTANCIATION OBJET #'.$id); }
	$order->setTraitee(1);
	if (!$prestaShopOrdersManager->saveOrdersPrestashop($order)) { exit('ERREUR ENREGISTREMENT ! #'.$id); }
	$log = new Log();
	$log->setLog_type('info');
	$log->setLog_texte("Commande prestashop #".$id. ' (id_order '.$order->getId_order().') marquée comme traitée.');
	$logsManager->saveLog($log);
	echo '1';
	exit;

} // FIN mode


function modeModaleDetailOrder() {

	global $prestaShopOrdersManager;
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('ERREUR IDENTIFICATION ID 0 !');}
	$order = $prestaShopOrdersManager->getOrdersPrestashop($id);
	if (!$order instanceof OrderPrestashop) { exit('ERREUR INSTANCIATION OBJET #'.$id); }
	$orderDetails = $prestaShopOrdersManager->getListeOrderDetailPrestashops(['id_order' => $order->getId_order()]);
	?>
	<div class="row">
		<div class="col-12 text-left margin-top--10">
			<div class="row">
				<div class="col-3 text-left">
					<div class="alert alert-secondary">
						<table class="table-v-top w-100">
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Référence </td>
								<td class="text-14"><b><?php echo $order->getReference(); ?></b></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Date </td>
								<td class="text-14"><?php echo Outils::dateSqlToFr($order->getDate_facture()); ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Produits </td>
								<td class="text-14"><?php echo is_array($orderDetails) ? count($orderDetails) : 0; ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Client </td>
								<td class="text-14"><?php echo $order->getNom_client(); ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Adresse </td>
								<td class="text-14"><?php echo $order->getAdresse() ; ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Transporteur </td>
								<td class="text-14"><?php echo $order->getTransporteur() ; ?></td>
							</tr>
						</table>
					</div>
					<div class="alert alert-secondary">
						<table class="table-v-top w-100">
							<tr>
								<td></td>
								<td class="texte-fin text-right text-13">HT</td>
								<td class="texte-fin text-right text-13">TTC</td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Total</td>
								<td class="text-14 text-right"><b><?php echo number_format($order->getTotal_ht(),2,'.', ' '). ' €'; ?></b></td>
								<td class="text-14 text-right"><b><?php echo number_format($order->getTotal_ttc(),2,'.', ' '). ' €'; ?></b></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Produits</td>
								<td class="text-14 text-right"><?php echo number_format($order->getTotal_produits_ht(),2,'.', ' '). ' €'; ?></td>
								<td class="text-14 text-right"><?php echo number_format($order->getTotal_produits_ttc(),2,'.', ' '). ' €'; ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Réductions</td>
								<td class="text-14 text-right"><?php echo number_format($order->getReductions_ht(),2,'.', ' '). ' €'; ?></td>
								<td class="text-14 text-right"><?php echo number_format($order->getReductions_ttc(),2,'.', ' '). ' €'; ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">Livraison</td>
								<td class="text-14 text-right"><?php echo number_format($order->getLivraison_ht(),2,'.', ' '). ' €'; ?></td>
								<td class="text-14 text-right"><?php echo number_format($order->getLivraison_ttc(),2,'.', ' '). ' €'; ?></td>
							</tr>
						</table>
					</div>
					<div class="alert alert-secondary">
						<p class="nomargin text-14">Identifiants Prestashop :</p>
						<table class="table-v-top w-100">
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">ID order </td>
								<td class="text-14"><?php echo $order->getId_order(); ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap pr-2">ID client </td>
								<td class="text-14"><?php echo $order->getId_client(); ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap  pr-2">ID adresse </td>
								<td class="text-14"><?php echo $order->getId_adresse(); ?></td>
							</tr>
							<tr>
								<td class="texte-fin text-13 nowrap  pr-2">ID transporteur </td>
								<td class="text-14"><?php echo $order->getId_transporteur(); ?></td>
							</tr>
						</table>
					</div>
				</div>
				<div class="col">
					<?php
					if (!is_array($orderDetails) || empty($orderDetails)) { ?>
						<div class="alert alert-danger text-center"><i class="fa fa-exclamation-triangle mr-1"></i> Aucun produit dans cette commande !</div>
					<?php } else { ?>
						<table class="table admin table-striped text-14">
							<thead>
							<tr>
								<th class="text-left">Référence</th>
								<th class="text-left">Article</th>
								<th class="text-center">Quantité</th>
								<th class="text-right">PU HT</th>
								<th class="text-right">PU TTC</th>
								<th class="t-actions text-center">BL</th>
							</tr>
							</thead>
							<tbody>
							<?php
							foreach ($orderDetails as $orderDetail) { ?>
								<tr>
									<td><?php echo $orderDetail->getRef();?></td>
									<td><?php echo $orderDetail->getNom();?></td>
									<td class="text-center"><?php echo $orderDetail->getQte();?></td>
									<td class="text-right"><?php echo number_format($orderDetail->getPu_ht(),2,'.', ' ');?> €</td>
									<td class="text-right"><?php echo number_format($orderDetail->getPu_ttc(),2,'.', ' ');?> €</td>
									<td class="t-actions text-center">
										<?php
										if ($orderDetail->getId_bl_ligne() > 0) { ?>
											<a class="text-info" target="_blank" href="gc-bls.php?i=<?php echo base64_encode($orderDetail->getId_bl()); ?>">
												<?php echo $orderDetail->getNum_bl(); ?></a>
                                            <span class="badge badge-danger pointeur btnDesassocieLigneBl" data-id-od="<?php echo $orderDetail->getId(); ?>"><i class="fa fa-times"></i></span>
										<?php } else { ?>
                                            <span class="text-danger text-12 texte-fin">Non affecté</span>
										<?php }
										?>
									</td>
								</tr>
							<?php } // FIN boucle sur les lignes
							?>
							</tbody>
						</table>
					<?php
					} // FIN test produits
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
	exit;

} // FIN mode

// Export PDF de toutes les commandes
function modeExportPdf() {

    global $prestaShopOrdersManager;
	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');
	ob_start();
	$margeEnTetePdt = 18;
	$content_fichier = genereContenuPdf();
	$content_header = getHeaderPdf(false);
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $content_fichier;
	$contentPdf.= '</page>';
	$rech          	   = isset($_REQUEST['rech'])         	  ? trim($_REQUEST['rech'])           		: '';
	$date_du           = isset($_REQUEST['date_du'])          ? trim($_REQUEST['date_du'])              : '';
	$date_au           = isset($_REQUEST['date_au'])          ? trim($_REQUEST['date_au'])              : '';
	if ($date_du != '') {
		$date_du = Outils::dateFrToSql($date_du);
		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
	}
	if ($date_au != '') {
		$date_au = Outils::dateFrToSql($date_au);
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }
	}
	$params = [
		'traites'          => false, // On ne prends que celles qui ne sont pas traitées
		'rech'             => $rech,
		'date_du'          => $_REQUEST['date_du'],
		'date_au'          => $_REQUEST['date_au']
	];
	$liste = $prestaShopOrdersManager->getListeOrdersPrestashop($params);
    if (!empty($liste)) {
        foreach ($liste as $order) {
			$content_header = getHeaderPdf($order);
			$contentPdf.= '<page backtop="'.(int)$margeEnTetePdt.'mm">';
			$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
			$contentPdf.= genereContenuPdfCommande($order);
			$contentPdf.= '</page>';
        }
    }
	$contentPdf.= ob_get_clean();
	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/iprexcmdsweb-*.pdf') as $fichier) {
		unlink($fichier);
	}
	try {
		$marges = [7, 12, 7, 12];
		$nom_fichier = 'iprexcmdsweb-'.date('is').'.pdf';
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15', $marges);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($contentPdf));;
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
FONCTION DEPORTEE - Génère le contenu HTML pour le PDF (TOUTES LES COMMANDES)
-----------------------------------------------------------------------------*/
function genereContenuPdf() {

	global $prestaShopOrdersManager;
	// Préparation des variables
	$rech          	   = isset($_REQUEST['rech'])         	  ? trim($_REQUEST['rech'])           		: '';
	$date_du           = isset($_REQUEST['date_du'])          ? trim($_REQUEST['date_du'])              : '';
	$date_au           = isset($_REQUEST['date_au'])          ? trim($_REQUEST['date_au'])              : '';
	if ($date_du != '') {
		$date_du = Outils::dateFrToSql($date_du);
		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
	}
	if ($date_au != '') {
		$date_au = Outils::dateFrToSql($date_au);
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }
	}
	$params = [
		'traites'          => false, // On ne prends que celles qui ne sont pas traitées
		'rech'             => $rech,
		'date_du'          => $_REQUEST['date_du'],
		'date_au'          => $_REQUEST['date_au']
	];
	$liste = $prestaShopOrdersManager->getListeOrdersPrestashop($params);
    // Génération du contenu HTML
	$contenu = '<table class="table table-liste w100 mt-10">';
    if (empty($liste)) {
        $contenu.= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucune commande web</i></td></tr>';
    } else {
		$contenu.='
                    <tr>
                        <th class="w10">Date</th>
                        <th class="w10">Référence</th>
                        <th class="w25">Client</th>
                        <th class="w30">Transporteur</th>
                        <th class="w10 text-center">Produits</th>
                        <th class="w15 text-right">Total TTC</th>
                    </tr>';
        foreach ($liste as $order) {
            $nbPdts = is_array($order->getOrder_details()) ? count($order->getOrder_details()) : 0;
            $contenu.= '
                        <tr>
                            <td class="w10">'.Outils::dateSqlToFr($order->getDate_facture()).'</td>
                            <td class="w10">'.$order->getReference().'</td>
                            <td class="w25">'.$order->getNom_client().'</td>
                            <td class="w30">'.$order->getTransporteur().'</td>
                            <td class="w10 text-center">'.$nbPdts.'</td>
                            <td class="w15 text-right">'.number_format($order->getTotal_ttc(),2,'.', ' ').' EUR</td>
                        </tr>';
        } // FIN boucle sur les commandes
    } // FIN test commandes
	$contenu.= '</table>';
    $contenu.= '<table class="table w100 mt-15"><tr><th class="w100 recap">Nombre de commandes web à traiter : '. count($liste) .'</th></tr></table>';
	// FOOTER
	$contenu.= '<table class="w100 gris-9">
                    <tr>
                        <td class="w50 text-8">Document édité le '.date('d/m/Y').' à '.date('H:i:s').'</td>
                        <td class="w50 text-right text-6">&copy; 2021 IPREX / KOESIO </td>
                    </tr>
                </table>';
	// RETOUR CONTENU
	return $contenu;

} // FIN fonction déportée


function getHeaderPdf($order = false) {

	$content_header = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td><td class="w33 text-center text-14">';
    $content_header.= $order instanceof OrderPrestashop ? 'Commande web N°<b>' . $order->getId_order().'</b>' : 'Liste des commandes web';
    $content_header.= '</td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-12 gris-7">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>';
    return $content_header;

} // FIN fonction déportés


// Contenu d'une page PDF de commande web (détail)
function genereContenuPdfCommande($order) {

	$nbPdts = is_array($order->getOrder_details()) ? count($order->getOrder_details()) : 0;
	$contenu = '
      <table class="table w100">
        <tr>
            <td class="w33 text-11">Référence <b class="text-12">: '.$order->getReference().'</b></td>
            <td class="w33 text-11 text-center">Date : <b class="text-12">'.Outils::dateSqlToFr($order->getDate_facture()).'</b></td>
            <td class="w34 text-11 text-right">Nombre de produits : <b class="text-12">'.$nbPdts.'</b></td>
        </tr>
        </table>
        <table class="table w100">
        <tr>
            <td class="w50 text-11">Client : <b>'.$order->getNom_client().'</b></td>
            <td class="w50 text-11 text-right">Transporteur : <b>'.$order->getTransporteur().'</b></td>
        </tr>
    </table>
    <table class="admin w100 mt-15">
        <tr>
            <th class="w20">Référence</th>
            <th class="w70">Article</th>
            <th class="w10 text-center">Quantité</th>
        </tr>';
    foreach ($order->getOrder_details() as $od) {
		$contenu.= '   <tr>
            <td class="w20">'.$od->getRef().'</td>
            <td class="w70">'.$od->getNom().'</td>
            <td class="w10 text-center">'.$od->getQte().'</td>
        </tr>';
    }
    $contenu.= '</table>';
    return $contenu;

} // FIN fonction déportée

function modeModaleCommandesWebForBl() {

	global $prestaShopOrdersManager;

    $id_bl_ligne = isset($_REQUEST['id_bl_ligne']) ? intval($_REQUEST['id_bl_ligne']) : 0;
    if ($id_bl_ligne == 0) { exit('ERREUR ID BL LIGNE 0 !'); }
	$liste = $prestaShopOrdersManager->getListeOrdersPrestashop();
    if (empty($liste)) { ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle fa-2x"></i>
            <p>Aucune commande web à traiter !</p>
        </div>
        <div class="col-12" id="webOrdersDetails">
        </div>
        <?php
        exit;
    } ?>
    <input type="hidden" id="idBlLigneOrderDetail" value="<?php echo $id_bl_ligne;?>"/>
    <div class="col-12">
        <div class="alert alert-secondary">
            <p class="gris-5 text-14 text-left mb-0"><i class="fa fa-caret-down mr-1 gris-9"></i>Sélectionnez la commande Web concernée :</i></p>
            <select class="selectpicker form-control" id="webOrders" title="- Sélectionnez -">
                <?php
                foreach ($liste as $order) { ?>
                    <option value="<?php echo $order->getId_order() ?>"><?php echo $order->getId_order(); ?> - Référence <?php echo $order->getReference(); ?> du <?php echo Outils::dateSqlToFr($order->getDate_facture()); ?> pour <?php echo $order->getNom_client(); ?></option>
				<?php }
                ?>
            </select>
        </div>
    </div>
    <div class="col-12" id="webOrdersDetails">
    </div>
	<?php
    exit;

} // FIN mode

function modeModaleSelectOrderDetailForBl() {

	global $prestaShopOrdersManager;
    $id_order = isset($_REQUEST['id_order']) ? intval($_REQUEST['id_order']) : 0;
    if ($id_order == 0) { exit('ERREUR ID ORDER 0 !'); }
    $prestaShopOrdersManager->cleanIdOrderDetailsLignesBl();
	$ods = $prestaShopOrdersManager->getListeOrderDetailPrestashops(['id_order' => $id_order, 'no_bl' => true]);
    if (!is_array($ods) || empty($ods)) { ?>
        <div class="alert alert-danger">
            Aucun produit restant à traiter pour cette commande !
            <p class="text-12 mb-0">Demandez à un administrateur de marquer cette commande comme traitée.</p>
        </div>
        <?php
        exit;
    } ?>
        <div class="alert alert-secondary">
            <p class="gris-5 text-14 text-left mb-0"><i class="fa fa-caret-down mr-1 gris-9"></i>Sélectionnez le produit correspondant :</i></p>
            <select class="selectpicker form-control" id="webOrderDetail">
				<?php
				foreach ($ods as $od) { ?>
                    <option value="<?php echo $od->getId() ?>"><?php echo $od->getNom(); ?></option>
				<?php }
				?>
            </select>
        </div>
        <button type="button" class="btn btn-success" id="btnAssocierWebBlLigne"><i class="fa fa-check mr-1"></i> Associer ce produit</button>
    <?php
    exit;
} // FIN mode


function modeAssocieBlLigneOrderDetail() {

	global $prestaShopOrdersManager, $cnx;
	$logManager = new LogManager($cnx);
	$id_order_detail = isset($_REQUEST['id_order_detail']) ? intval($_REQUEST['id_order_detail']) : 0;
	if ($id_order_detail == 0) { exit('ERREUR ID ORDER DETAIL 0 !'); }
	$id_bl_ligne = isset($_REQUEST['id_bl_ligne']) ? intval($_REQUEST['id_bl_ligne']) : 0;
	if ($id_bl_ligne == 0) { exit('ERREUR ID BL LIGNE 0 !'); }
    $od = $prestaShopOrdersManager->getOrderDetailPrestashop($id_order_detail);
	$od->setId_bl_ligne($id_bl_ligne);
    if (!$prestaShopOrdersManager->saveOrderDetailPrestashop($od)) { exit('ERREUR ENREGISTREMENT !'); }
    // On applique la quantité et le pu_ht de la commande web à la ligne de BL
	if (!$prestaShopOrdersManager->affectePuQteFromOrderDetailToBlLigne($id_order_detail,$id_bl_ligne)) {
		$log = new Log();
		$log->setLog_type('danger');
		$log->setLog_texte("Erreur enregistrement automatique quantité et pu_ht sur Bl_ligne #".$id_bl_ligne." depuis order_detail #".$id_order_detail);
		$logManager->saveLog($log);
    }
    // Si rémise sur l'order, on l'applique au prorata sur le pu_ht de la ligne de bl
	if (!$prestaShopOrdersManager->setRemiseLigneBlFromOrder($id_order_detail, $id_bl_ligne)) {
		$log = new Log();
		$log->setLog_type('danger');
		$log->setLog_texte("Erreur enregistrement remise sur Bl_ligne #".$id_bl_ligne." associé a order_detail #".$id_order_detail);
		$logManager->saveLog($log);
    }
	$log = new Log();
	$log->setLog_type('info');
	$log->setLog_texte("Association de la ligne de Bl #".$id_bl_ligne." à l'order_detail Prestashop #".$id_order_detail);
	$logManager->saveLog($log);
    exit('1');

} // FIN mode


function modeDesassocieWebLigne() {

	global $prestaShopOrdersManager, $cnx;
	$id_order_detail = isset($_REQUEST['id_order_detail']) ? intval($_REQUEST['id_order_detail']) : 0;
	if ($id_order_detail == 0) { exit('ERREUR ID ORDER DETAIL 0 !'); }
	$id_bl_ligne = isset($_REQUEST['id_bl_ligne']) ? intval($_REQUEST['id_bl_ligne']) : 0;
	$od = $prestaShopOrdersManager->getOrderDetailPrestashop($id_order_detail);
	$od->setId_bl_ligne(0);
	if (!$prestaShopOrdersManager->saveOrderDetailPrestashop($od)) { exit('ERREUR ENREGISTREMENT !'); }
    $logTxt = $id_bl_ligne > 0
        ? "Retrait de l'association de la ligne de Bl #".$id_bl_ligne." avec l'order_detail Prestashop #".$id_order_detail." depuis le BL"
        : "Retrait de l'association de la ligne de Bl sur l'order_detail Prestashop #".$id_order_detail."  depuis l'admin des commandes web ";
	$log = new Log();
	$logManager = new LogManager($cnx);
	$log->setLog_type('info');
	$log->setLog_texte($logTxt);
	$logManager->saveLog($log);
	exit('1');

} // FIN mode

// Idem ci-dessus mais supprime la ligne de BL
function modeSupprimeWebLigne() {

	global $prestaShopOrdersManager, $cnx;
	$id_order_detail = isset($_REQUEST['id_order_detail']) ? intval($_REQUEST['id_order_detail']) : 0;
	if ($id_order_detail == 0) { exit('ERREUR ID ORDER DETAIL 0 !'); }
	$od = $prestaShopOrdersManager->getOrderDetailPrestashop($id_order_detail);
	$id_bl_ligne = $od->getId_bl_ligne();
    if (intval($id_bl_ligne) == 0) { exit('ERREUR IDENTIFICATION BL LIGNE !'); }
	$od->setId_bl_ligne(0);
	if (!$prestaShopOrdersManager->saveOrderDetailPrestashop($od)) { exit('ERREUR ENREGISTREMENT !'); }
    $blManager = new BlManager($cnx);
	$ligne = $blManager->getBlLigne($id_bl_ligne);
    if (!$ligne instanceof BlLigne) { exit('ERREUR INSTANCIATION LIGNE BL !'); }
    $ligne->setSupprime(1);
    if (!$blManager->saveBlLigne($ligne)) { exit('ERREUR SUPPRESSION LIGNE BL !'); }
	$logTxt = "Suppression (flag) de la ligne de Bl #".$id_bl_ligne." par désaciciation de l'order_detail Prestashop #".$id_order_detail." depuis les commandes web.";
	$log = new Log();
	$logManager = new LogManager($cnx);
	$log->setLog_type('info');
	$log->setLog_texte($logTxt);
	$logManager->saveLog($log);
	exit('1');

} // FIN mode


// Création d'un Bl avec les lignes de order_detail sélectionnés
function modeCreerBlFromOrderDetails() {

	global $prestaShopOrdersManager, $cnx;
    $blManager      = new BlManager($cnx);
	$tiersManager   = new TiersManager($cnx);
	$logManager     = new LogManager($cnx);
    $ids_od = isset($_REQUEST['ids_od']) ? explode(',', $_REQUEST['ids_od']) : [];
    if (empty($ids_od) || intval($ids_od[0]) == 0) { exit("ERREUR IDENTIFICATION LIGNES !");}
	$ods = $prestaShopOrdersManager->getListeOrderDetailPrestashops(['ids_order_detail' => $ids_od]);
    if (!$ods || empty($ods)) { exit("ERREUR INSTANCIATION DES OBJETS ORDER_DETAILS !"); }
    // on récupère les orders et le numéro de commande du BL est celui de l'oder si il n'y en a qu'un, sinon "WEB"+idsod
    $refs       = [];
    $transport  = [];
    foreach ($ods as $od) {
		$refs[$od->getRef()] = $od->getRef();
        $order = $prestaShopOrdersManager->getOrdersPrestashop($od->getId_order(), true);
        if (!$order instanceof OrderPrestashop) { exit("ERREUR INSTANCIATION ORDER #".$od->getId_order()." DEPUIS ORDER_DETAIL #".$od->getId());}
		$transport[$order->getId_transporteur()] =  $order->getTransporteur();
    }
	$numCmd = count($refs) == 1 && reset($refs) != false
       ? reset($refs)
       : 'WEB'.implode('',$ids_od);
	$id_transporteur = 0;
    if (count($transport) == 1) {
      $tiersTransports = $tiersManager->getListeTiers(['show_inactifs' => true, 'recherche' => reset($transport)]);
      if (count($tiersTransports) == 1) {
          if ($tiersTransports[0] instanceof Tiers) {
			  $id_transporteur = $tiersTransports[0]->getId();
          }
      }
    }
    if ($id_transporteur == 0) {
		$tiersTransports = $tiersManager->getListeTiers(['show_inactifs' => true, 'recherche' => "retrait"]);
		if (count($tiersTransports) == 1) {
			if ($tiersTransports[0] instanceof Tiers) {
				$id_transporteur = $tiersTransports[0]->getId();
			}
		}
    }
	$num_bl = $blManager->getNextNumeroBl();
    $id_web = $tiersManager->getId_client_web();
    if ((int)$id_web == 0) { exit("ERREUR RECUPERATION CLIENT WEB !");}
	$id_frs_profilexport = $tiersManager->getIdProfilExport();
	$bl = new Bl([]);
	$bl->setBt(0);
	$bl->setSupprime(0);
	$bl->setDate_add(date('Y-m-d H:i:s'));
	$bl->setDate(date('Y-m-d'));
	$bl->setNum_cmd($numCmd);
	$bl->setStatut(1);
	$bl->setId_tiers_facturation($id_web);
	$bl->setId_tiers_livraison($id_web);
	$bl->setId_tiers_transporteur($id_transporteur);
	$bl->setRegroupement(0);
	$bl->setChiffrage(0);
	$bl->setNum_bl($num_bl);
	$id_bl = $blManager->saveBl($bl);
	if ((int)$id_bl == 0) { exit("ECHEC LORS DE LA CREATION DU BL !"); }
	$bl->setId($id_bl);
	$log = new Log();
	$log->setLog_type('info');
	$log->setLog_texte("Création du BL #".$id_bl." depuis les commandes web, order_details #".$_REQUEST['ids_od']);
	$logManager->saveLog($log);
    $produitsManager = new ProduitManager($cnx);
	$id_produit_web = $produitsManager->getIdProduitWeb();
    // Boucle lignes order_details -> bl_ligne
    foreach ($ods as $od) {
		$tva_taux = (($od->getPu_ttc() / $od->getPu_ht()) -1) * 100;
		if ((string)$tva_taux != '5.5' && (string)$tva_taux != '20') { $tva_taux = 0; }
		$poids = floatval(preg_replace("/[^0-9]/", "", Outils::trouverSousChaine($od->getNom(), "Poids", "kg")));
        $nbOd = $prestaShopOrdersManager->getNbOrdersDetailsPrestashop($od->getId_order());
        /*
         *  PU_HT remisé :
         * Remise totale par article = total remise HT de la commande / nb articles commande
         * Remise unitaire par article = Remise totale par article / quantité
         * Prix remisé =  PU avant remise - Remise unitaire par article
         */
		if ($od->getQte() == 0) { $od->setQte(1); }
		$order = $prestaShopOrdersManager->getOrdersPrestashop($od->getId_order(), true);
		if (!$order instanceof OrderPrestashop) { exit("ERREUR INSTANCIATION ORDER #".$od->getId_order()." DEPUIS ORDER_DETAIL #".$od->getId());}
		$remise_totale_par_article = round(($order->getReductions_ht() / $nbOd),2);
        $remise_unitaire_par_article = round(($remise_totale_par_article / $od->getQte()),2);
		$prix_remise_ht = $od->getPu_ht() - $remise_unitaire_par_article;
		$ligne = new BlLigne([]);
		$ligne->setDate_add(date('Y-m-d H:i:s'));
		$ligne->setSupprime(0);
		$ligne->setId_compo(0);
		$ligne->setId_bl($id_bl);
		$ligne->setNb_colis(1);
		$ligne->setId_produit($id_produit_web);
        $ligne->setPoids($poids);
        $ligne->setQte($od->getQte());
		$ligne->setPu_ht($prix_remise_ht);
        $ligne->setTva($tva_taux);
        $ligne->setId_frs($id_frs_profilexport);
		$id_ligne_bl = $blManager->saveBlLigne($ligne);
        if ((int)$id_ligne_bl == 0 || !$id_ligne_bl) {
            $blManager->supprBl($bl);
            exit("ERREUR CREATION LIGNE BL POUR ORDER_DETAIL #".$od->getId());
        }
		$od->setId_bl_ligne($id_ligne_bl);
		if (!$prestaShopOrdersManager->saveOrderDetailPrestashop($od)) {
			$blManager->supprBl($bl);
            exit('ERREUR ENREGISTREMENT LIAISON BL/ORDER DETAIL !');
        }
		$log = new Log();
		$log->setLog_type('info');
		$log->setLog_texte("Création de la ligne de BL #".$id_ligne_bl." sur BL #".$id_bl." depuis les commandes web, order_detail #".$od->getId());
		$logManager->saveLog($log);
	} // FIN boucle order_details
    echo "1";exit;

} // FIN mode

// Liste des BL web dans un select pour l'ajout ou la creation
function modeGetSelectListeBlsWeb() {

    global $cnx;
    $blsManager = new BlManager($cnx);
    $tiersManager = new TiersManager($cnx);
	$id_web = $tiersManager->getId_client_web();
    $bls = $blsManager->getListeBl(['id_client' => $id_web, 'facture' => 0]);
    // Si aucun BL en cours pour le web, on force la création directe
    if (empty($bls)) {
        exit('1');
    }
    // SInon on affiche le select :
    ?>
    <p class="text-left mt-0 mb-1 texte-fin text-12">Ajouter au BL <i class="fa fa-level-down-alt v-bottom"></i></p>
    <div class="input-group mb-2">
        <select class="selectpicker form-control selectBl2add">
        <option value="0">Nouveau BL</option>
        <option data-divider="true"></option>
        <?php
        foreach ($bls as $bl) { ?>
            <option value="<?php echo $bl->getId(); ?>"><?php echo $bl->getNum_bl(); ?></option>
		<?php }
        ?>
        </select>
        <div class="input-group-append">
            <span class="input-group-text pointeur bg-info btnBlSelected"><i class="fa fa-check fa-sm text-white"></i></span>
        </div>
    </div>
    <?php
    exit;

} // FIN mode

function modeAddBlFromOrderDetails() {

	global $prestaShopOrdersManager, $cnx;
	$blManager      = new BlManager($cnx);
	$tiersManager   = new TiersManager($cnx);
	$logManager     = new LogManager($cnx);
	$ids_od = isset($_REQUEST['ids_od']) ? explode(',', $_REQUEST['ids_od']) : [];
	if (empty($ids_od) || intval($ids_od[0]) == 0) { exit("ERREUR IDENTIFICATION LIGNES !");}
    $id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
    if ($id_bl == 0) { exit('ERREUR IDENTIFICATION ID BL !'); }
	$id_frs_profilexport = $tiersManager->getIdProfilExport();
    $bl = $blManager->getBl($id_bl, false);
    $id_web = $tiersManager->getId_client_web();
    if ($bl->getId_tiers_facturation() != $id_web && $bl->getId_tiers_livraison() != $id_web) { exit('ERREUR BL HORS WEB !'); }
	$ods = $prestaShopOrdersManager->getListeOrderDetailPrestashops(['ids_order' => $ids_od]);
	if (!$ods || empty($ods)) { exit("ERREUR INSTANCIATION DES OBJETS ORDER_DETAILS !"); }
	$produitsManager = new ProduitManager($cnx);
	$id_produit_web = $produitsManager->getIdProduitWeb();
	// Boucle lignes order_details -> bl_ligne
	foreach ($ods as $od) {
		$tva_taux = (($od->getPu_ttc() / $od->getPu_ht()) -1) * 100;
		if ((string)$tva_taux != '5.5' && (string)$tva_taux != '20') { $tva_taux = 0; }
		$poids = floatval(preg_replace("/[^0-9]/", "", Outils::trouverSousChaine($od->getNom(), "Poids", "kg")));
		$ligne = new BlLigne([]);
		$ligne->setDate_add(date('Y-m-d H:i:s'));
		$ligne->setSupprime(0);
		$ligne->setId_compo(0);
		$ligne->setId_bl($id_bl);
		$ligne->setNb_colis(1);
		$ligne->setId_produit($id_produit_web);
		$ligne->setPoids($poids);
		$ligne->setQte($od->getQte());
		$ligne->setPu_ht($od->getPu_ht());
		$ligne->setTva($tva_taux);
		$ligne->setId_frs($id_frs_profilexport);
		$id_ligne_bl = $blManager->saveBlLigne($ligne);
		if ((int)$id_ligne_bl == 0 || !$id_ligne_bl) {
			$blManager->supprBl($bl);
			exit("ERREUR CREATION LIGNE BL POUR ORDER_DETAIL #".$od->getId());
		}
		$od->setId_bl_ligne($id_ligne_bl);
		if (!$prestaShopOrdersManager->saveOrderDetailPrestashop($od)) {
			$blManager->supprBl($bl);
			exit('ERREUR ENREGISTREMENT LIAISON BL/ORDER DETAIL !');
		}
		$log = new Log();
		$log->setLog_type('info');
		$log->setLog_texte("Création de la ligne de BL #".$id_ligne_bl." sur BL existant #".$id_bl." depuis les commandes web, order_detail #".$od->getId());
		$logManager->saveLog($log);
	} // FIN boucle order_details
    echo '1';
    exit;

} // FIN mode