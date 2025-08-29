<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax Factures
------------------------------------------------------*/
ini_set('display_errors',1); // PPL

error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning

// En provenance d'une tache CRON, on skip le fonctionnement natif du controleur ajax
if (!isset($fromCron) || $fromCron == false) {
	// Initialisation du mode d'appel
	$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

    // Intégration de la configuration du FrameWork et des autorisations
	require_once '../php/config.php';

    // Instanciation des Managers
	$facturesManager = new FacturesManager($cnx);
	$fonctionNom = 'mode'.ucfirst($mode);
	if (function_exists($fonctionNom)) {
		$fonctionNom();
	}
}

/* ------------------------------------------------
MODE - Crée une nouvelle facture depuis des ID BLs
-------------------------------------------------*/
function modeCreateFactureFromBls() {

	global $cnx;

	$id_bls = isset($_REQUEST['id_bls']) ? $_REQUEST['id_bls'] : '';
    $supprStock = isset($_REQUEST['supprStock']) && intval($_REQUEST['supprStock']) == 1;

	$facture = new Facture([]);
	$facture->setDate_add(date('Y-m-d H:i:s'));
	$facture->setDate(date('Y-m-d'));

	$facturesManager = new FacturesManager($cnx);
	$blManager = new BlManager($cnx);
	$id_bls_array = explode(',', $id_bls);
	if (empty($id_bls_array)) { exit('-1'); }

	$montant_ht = 0.0;
	$montant_tva = 0.0;
	$montant_interbev = 0.0;
	$num_cmd_array = [];


	// On vérifie qu'une facture n'a pas déjà été générée pour ce BL !
	// Boucle sur les Bls à intégrer à la facture
    $ok = false;
	foreach ($id_bls_array as $id_bl) {
		$factDeja = $facturesManager->getFactureByBl($id_bl);
		if ($factDeja instanceof Facture) { continue; }
		$ok = true;
	}
    if (!$ok) { exit; }


	// Boucle sur les Bls à intégrer à la facture
	foreach ($id_bls_array as $id_bl) {

		$bl = $blManager->getBl($id_bl, false, true);
		if (!$bl instanceof Bl) { exit('-2'); } // SI un BL de ne peut pas être intégré, on ne crée pas de facture incomplète



		// On prends les données du dernier Bl...
		$id_tiers_livraison = $bl->getId_tiers_livraison();
		$id_tiers_facturation = $bl->getId_tiers_facturation();
		$id_tiers_transporteur = $bl->getId_tiers_transporteur();
		$id_adresse_facturation = $bl->getId_adresse_facturation();
		$id_adresse_livraison = $bl->getId_adresse_livraison();

		// On passe les BLs concernés en générés car certains sont encore en attente
		$bl->setStatut(2);
        $blManager->saveBl($bl);

		$montant_ht+= $blManager->getTotalHt($bl);

		$num_cmd_array[$bl->getNum_cmd()] = $bl->getNum_cmd();

	} // FIN boucle sur les BL

	$facture->setId_tiers_livraison($id_tiers_livraison);
	$facture->setId_tiers_facturation($id_tiers_facturation);
	$facture->setId_tiers_transporteur($id_tiers_transporteur);
	$facture->setId_adresse_facturation($id_adresse_facturation);
	$facture->setId_adresse_livraison($id_adresse_livraison);

	$facture->setMontant_ht($montant_ht);
	$facture->setNum_cmd(implode('/', $num_cmd_array));

	$num_fact = $facturesManager->getNextNumeroFacture();

    echo $num_fact;

	$facture->setNum_facture($num_fact);


	$id_facture = $facturesManager->saveFacture($facture);
	if (!$id_facture || intval($id_facture) == 0) { exit('-3'); }
	$facture->setId($id_facture);

	// On enregistre la liaison entre la facture et ses BLs
	if (!$facturesManager->saveLiaisonFactureBls($id_facture, $id_bls_array)) { exit('-4');	}


	// On sauvegarde les lignes de la facture (persistance indépendante des données du BL)
	if (!$facturesManager->saveLignesFactureFromBl($id_facture, $id_bls_array)) { exit('-5');	}

	// On rattache les lignes à l'objet
    $facture->setLignes($facturesManager->getListeFactureLignes(['id_facture' => $id_facture]));

	$facture->setMontant_interbev($facturesManager->getInterbevFacture($facture));
	$facture->setMontant_tva($facturesManager->getTvaFacture($facture));

	// On rattache les BLs liés à la facture
	$bls = $blManager->getListeBl(['id_facture' => $facture->getId()]);
	$facture->setBls($bls);

	// On crée le PDF de la facture...
	if (!generePdfFacture($facture)) { exit('-6');}


	// On log...
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Création de la facture #" . $id_facture);
	$logManager = new LogManager($cnx);
	$logManager->saveLog($log);

	// Si on supprime le stock et cloture les palettes
	if ($supprStock) {

	    $palettesManager = new PalettesManager($cnx);

		$palettesManager->archiveComposEtCloturePaletteByFacture($id_facture);


		$log2 = new Log([]);
		$log2->setLog_type('info');
		$log2->setLog_texte("Archivage des compos et cloture des palettes de la facture #" . $id_facture);
		$logManager->saveLog($log2);
    }




	exit('1');

} // FIN mode

// Retourne la liste des factures (BO
function modeGetListeFactures() {

	global $facturesManager, $mode, $cnx, $utilisateur;

	$tiersManager = new TiersManager($cnx);

	$id_client_web = $tiersManager->getId_client_web();

	$nbResultPpage_defaut = 25;

	$page             = isset($_REQUEST['page'])             ? intval($_REQUEST['page'])               : 1;
	$id               = isset($_REQUEST['id'])               ? intval($_REQUEST['id'])                 : 0;
	$date_du          = isset($_REQUEST['date_du'])          ? trim($_REQUEST['date_du'])              : '';
	$date_au          = isset($_REQUEST['date_au'])          ? trim($_REQUEST['date_au'])              : '';
	$numfact          = isset($_REQUEST['numfact'])  && $id == 0        ? trim(strtoupper($_REQUEST['numfact']))  : '';
	$numcmd           = isset($_REQUEST['numcmd'])  ? trim(strtoupper($_REQUEST['numcmd']))   : '';
	$factavoirs        = isset($_REQUEST['factavoirs'])     ? strtolower($_REQUEST['factavoirs'])     : '';
	$id_clients       = isset($_REQUEST['id_client'])        ? $_REQUEST['id_client']          : '';
	$reglee           = isset($_REQUEST['reglee'])           ? intval($_REQUEST['reglee'])             : -1;
	$nbResultPpage    = isset($_REQUEST['nb_result_p_page']) ? intval($_REQUEST['nb_result_p_page'])   : 0;
	if ($nbResultPpage == 0) { $nbResultPpage = $nbResultPpage_defaut; }

    if (is_array($id_clients)) {
		$id_clients = implode(',', $id_clients);
    }

	if (trim($numfact) != '') {
		$date_du = '';
		$date_au = '';
		$id = 0;
		$id_clients = '';
		$numcmd = '';
		$reglee = -1;
		$page = 1;
	}


	if ($date_du != '') {
		$date_du = Outils::dateFrToSql($date_du);
		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
    }
	if ($date_au != '') {
		$date_au = Outils::dateFrToSql($date_au);
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }
	}

	$filters = 'page='.$page.'&date_du='.$date_du.'&date_au='.$date_au.'&numfact='.$numfact.'&numcmd='.$numcmd.'&factavoirs='.$factavoirs.'&id_client='.$id_clients.'&reglee='.$reglee.'&id='.$id;

	// Préparation pagination (Ajax)
	$filtresPagination  = '?mode='.$mode.'&date_du='.$date_du.'&date_au='.$date_au.'&numfact='.$numfact.'&numcmd='.$numcmd.'&factavoirs='.$factavoirs.'&id_client='.$id_clients.'&reglee='.$reglee.'&id='.$id;
	$start              = ($page-1) * $nbResultPpage;

	$params = [
		'id'                => $id,
		'id_clients'         => $id_clients,
		'date_du'           => $date_du,
		'date_au'           => $date_au,
		'num_fact'          => $numfact,
		'num_cmd'           => $numcmd,
		'reglee'            => $reglee,
		'bls'          		=> true,
		'lignes'          	=> false,
		'start'             => $start,
		'nb_result_page'    => $nbResultPpage,
		'factavoirs'        => $factavoirs
	];


	$liste = $facturesManager->getListeFactures($params);


	$factureReglementsManager = new FactureReglementsManager($cnx);
    if ($reglee != -1) {

        foreach ($liste as $k => $facture) {
			$reste = $factureReglementsManager->getResteApayerFacture($facture);
			if ($reste == 0.01 || $reste == -0.01) { $reste = 0; }
            if (($reglee == 0 && $reste == 0) || ($reglee == 1 && $reste != 0)) {
               // unset($liste[$k]);
            }
        }

    }

	$nbResults  = $facturesManager->getNb_results();

	//if ($reglee == -1) {
		$pagination = new Pagination($page);
		$pagination->setUrl($filtresPagination);
		$pagination->setNb_results($nbResults);
		$pagination->setAjax_function(true);
		$pagination->setNb_results_page($nbResultPpage);
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);
	//}

	if (empty($liste)) { ?>

		<div class="alert alert-warning">
			Aucune facture n'a été trouvée...
		</div>

	<?php  } else { ?>
		<table class="table admin table-v-middle">
			<thead>
			<tr>
				<th <?php echo $utilisateur->isDev() ? '' : 'd-none'; ?>>ID</th>
				<th>Type</th>
				<th>Date</th>
				<th>Numéro</th>
				<th>Commande</th>
				<th>Client</th>
				<th class="text-center">Total TTC</th>
				<th class="text-center d-none">Frais</th>
				<th class="text-center">Réglé</th>
				<th class="text-center d-none">TVA</th>
				<th class="text-center d-none">Interbev</th>
                <th class="text-center">BL</th>
                <!--<th class="t-actions text-center w-mini-admin-cell">Supprimer</th>-->
                <th class="t-actions text-center w-mini-admin-cell">Frais</th>
                <th class="t-actions text-center w-mini-admin-cell">Editer</th>
                <th class="t-actions text-center w-mini-admin-cell">PDF</th>
                <th class="t-actions text-center w-mini-admin-cell">Envoyer</th>
                <th class="t-actions text-center w-mini-admin-cell">Réglement</th>
            </tr>
			</thead>
			<tbody>
			<?php
			foreach ($liste as $facture) {
				$totalInterbev = 0.0;
				foreach ($facture->getLignes() as $l) {
					$totalInterbev+= round($l->getInterbev(),2);
				}
				if ($facture->getMontant_interbev() != $totalInterbev) {
					$facture->setMontant_interbev($totalInterbev);
					$facturesManager->saveFacture($facture);
				}

				if ($facture->getTotal_ttc() == 0 && $facture->getMontant_ttc() != 0) {
					$facture->setTotal_ttc($facture->getMontant_ttc());
					$facturesManager->saveFacture($facture);
				}

				$avoir = $facture->getTotal_ttc() < 0;
				$isWeb = $id_client_web == $facture->getId_tiers_facturation();

				$client = $tiersManager->getTiers($facture->getId_tiers_facturation());
                if (!$client instanceof Tiers) {
					$client = $tiersManager->getTiers($facture->getId_tiers_livraison());
				}
				if (!$client instanceof Tiers) {
					continue;
				}

				$reste = $factureReglementsManager->getResteApayerFacture($facture);

				if ($reste == 0.01 || $reste == -0.01) { $reste = 0; }

				$regle = round($facture->getTotal_ttc(),2) - round($reste,2);

				if ($regle == 0.01 || $regle == -0.01) { $reste = 0; }

				// Avoir ?
				if ($facture->getTotal_ttc() < 0) {

					$regle = round($facture->getTotal_ttc(), 2) + round($reste * -1, 2);
					if ($regle == 0) { $cssRegle = 'danger'; }
					else if ($regle < 0 && $regle != $facture->getTotal_ttc()) { $cssRegle = 'info'; }
					else { $cssRegle = 'success'; }

    			// Factures
				} else {

					if ($regle == 0) { $cssRegle = 'danger'; }
					else if ($regle > 0 && $regle != $facture->getTotal_ttc()) { $cssRegle = 'info'; }
					else { $cssRegle = 'success'; }

				} // Fin avoir

				$frais = 0;


				$compta = $facture->getDate_compta() != '' && $facture->getDate_compta() != '0000-00-00 00:00:00';
				?>
				<tr>
                    <td <?php echo $utilisateur->isDev() ? '' : 'd-none'; ?>><code><?php echo $facture->getId();?></code></td>
					<td class="w-mini-admin-cell"><?php echo $avoir ? 'Avoir' : 'Facture'; ?></td>
					<td><?php echo Outils::dateSqlToFr($facture->getDate()); ?></td>
					<td class="numfact"><?php echo $facture->getNum_facture(); ?></td>
					<td><?php echo $facture->getNum_cmd() != '' ? $facture->getNum_cmd() : '&mdash;'; ?></td>
                    <td><a href="gc-all.php?idclt=<?php echo base64_encode($client->getId()); ?>"><?php echo $client->getNom();?></a>
					</td>
					<td class="text-center <?php echo $facture->getTotal_ttc() == 0 ? 'gris-9' : ''; ?>"><?php echo number_format($facture->getTotal_ttc() + $frais,2,'.', ' '); ?> €</td>
					<td class="text-center d-none <?php echo intval($frais) == 0 ? 'gris-9' : ''; ?>"><?php echo number_format($frais,2,'.', ' '); ?> €</td>
					<td class="text-center text-<?php echo $cssRegle; ?>"><?php echo number_format($regle,2,'.', ' '); ?> €</td>
					<td class="text-center d-none <?php echo $facture->getMontant_tva() == 0 ? 'gris-9' : ''; ?>"><?php echo number_format($facture->getMontant_tva(),2,'.', ' '); ?> €</td>
					<td class="text-center d-none <?php echo $facture->getMontant_interbev() == 0 ? 'gris-9' : ''; ?>"><?php echo number_format($facture->getMontant_interbev(),2,'.', ' '); ?> €</td>
                    <td class="text-center">
                        <?php
                        if (empty($facture->getBls())) {
                            echo '<span class="gris-9">&mdash;</span>';
						}

                        foreach ($facture->getBls() as $bl) { ?>
                            <a href="gc-bls.php?i=<?php echo base64_encode($bl->getId()); ?>" class="text-info texte-fin text-13 d-block"><?php echo $bl->getCode(); ?></a>
                        <?php } ?>
                    </td>
 <!--                   <td class="t-actions text-center"><button type="button" class="btn btn-sm btn-<?php /*echo $compta ? 'outline-secondary' : 'danger'; */?> btnSupprFacture <?php /*echo $compta ? 'disabled' : ''; */?>" <?php /*echo $compta ? 'disabled' : ''; */?> data-id="<?php /*echo $facture->getId(); */?>"><i class="fa fa-fw fa-<?php /*echo $compta ? 'ban' : 'trash-alt';*/?>"></i></button></td>-->


                    <td class="t-actions text-center">
                        <?php
                        if ($facture->getDate_compta() == '') { ?>
                            <button type="button" class="btn btn-sm btn-secondary <?php echo $avoir  ? 'disabled' : ''; ?>" <?php echo $avoir  ? 'disabled' : ''; ?> data-toggle="modal" data-id="<?php echo $facture->getId(); ?>" data-target="#modalFactureFrais"><i class="fa fa-fw fa-cash-register"></i></button>
						<?php } else { ?>
                            <button type="button" title="Facture comptabilisée" class="btn btn-sm btn-secondary disabled" disabled><i class="fa fa-fw fa-ban"></i></button>
						<?php }
                        ?>

                    </td>


                    <td class="t-actions text-center">
						<?php
						if (!$compta) { ?>
                            <a href="facture-upd.php?filters=<?php echo base64_encode($filters);?>&f=<?php echo $compta ? base64_encode(0) : base64_encode($facture->getId()); ?>" <?php echo $compta  ? 'disabled' : ''; ?> class="btn btn-sm btn-secondary <?php echo $compta  ? 'disabled' : ''; ?>"><i class="fa fa-fw fa-edit"></i></a>
						<?php } else { ?>
                            <button type="button" title="Facture comptabilisée" class="btn btn-sm btn-secondary disabled" disabled><i class="fa fa-fw fa-ban"></i></button>
						<?php }
						?>

                    </td>
                    <td class="t-actions text-center">
                        <?php
						$dossier_facture =  $facturesManager->getDossierFacturePdf($facture, false);
                        if (file_exists(__CBO_ROOT_PATH__.$dossier_facture.$facture->getFichier())) { ?>
                            <a target="_blank" href="<?php echo __CBO_ROOT_URL__.$dossier_facture.$facture->getFichier(); ?>" class="btn btn-sm btn-secondary"><i class="fa fa-fw fa-file-pdf"></i></a>
						<?php } else { ?>
                            <button type="button" class="btn btn-sm btn-secondary disabled" disabled><i class="fa fa-fw fa-ban"></i></button>
						<?php }
                        ?>
                       </td>

                    <td class="t-actions text-center">
                        <?php
				if (file_exists(__CBO_ROOT_PATH__.$dossier_facture.$facture->getFichier())) { ?>

                    <button type="button" class="btn btn-sm btn-<?php
					echo $facture->getDate_envoi() != '' ? 'success' : 'secondary';
					?> btnEnvoiFactureMail" data-id-facture="<?php echo $facture->getId(); ?>" data-id-clt="<?php echo $facture->getId_tiers_facturation(); ?>" ><i class="fa fa-fw fa-paper-plane"></i></button>
				<?php } else { ?>
                    <button type="button" class="btn btn-sm btn-secondary disabled" disabled><i class="fa fa-fw fa-ban"></i></button>
				<?php }
					?>

                        </td>
                    <td class="t-actions text-center">


                        <?php

						// Factures
						if ($facture->getTotal_ttc() >= 0) {

							if ($reste == 0) { $cssReglement = 'success'; $txtReglement = 'Acquittée'; }
							else if ($reste < 0) { $cssReglement = 'warning'; $txtReglement = 'Trop perçu'; }
							else if ($reste > 0 && $reste != round($facture->getTotal_ttc(),2)) { $cssReglement = 'warning'; $txtReglement = 'Partiel'; }
							else { $cssReglement = 'danger'; $txtReglement = 'Non réglée';} ?>

                            <button type="button" class="btn btn-sm btn-<?php echo $cssReglement; ?> w-100 texte-fin text-12" data-id="<?php echo $facture->getId(); ?>" data-toggle="modal" data-target="#modalReglement"><?php echo $txtReglement; ?></button>

                        <?php
						// Avoirs
						} else {

                            if ($reste == 0) {
								$txtReglement = 'Déduit';
								$cssReglement = 'success';
                            } else if ($reste != round($facture->getTotal_ttc(),2)) {
								$txtReglement = number_format(((round($facture->getTotal_ttc(),2) - $reste)*-1), 2, '.', ' ');
								$cssReglement = 'warning';
							} else {
								$txtReglement = 'A déduire';
								$cssReglement = 'danger';
							}
                            ?>

                            <span class="c-default btn btn-sm btn-<?php echo $cssReglement; ?> w-100 texte-fin text-12"><?php echo $txtReglement; ?></span><?php


						/*	if ($reste == 0) { $cssReglement = 'success'; $txtReglement = 'Acquittée'; }
							else if ($reste > 0) { $cssReglement = 'warning'; $txtReglement = 'Trop perçu'; }
							else if ($reste < 0 && $reste != round($facture->getTotal_ttc(),2)) { $cssReglement = 'warning'; $txtReglement = 'Partiel'; }
							else { $cssReglement = 'danger'; $txtReglement = 'Non réglée';}*/

						} // FIN factures/avoirs



						?>



                       </td>
				</tr>
			<?php }


			?>
			</tbody>
		</table>
	<?php }

	// Pagination (aJax)
	if (isset($pagination)) {
		// Pagination bas de page, verbose...
		$pagination->setVerbose_pagination(1);
		$pagination->setVerbose_position('right');
		$pagination->setNature_resultats('facture');
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);

		echo ($pagination->getPaginationHtml());
	} // FIN test pagination
	?>


	<?php
	exit;

} // FIN mode


// Génère le PDF de la facture
function generePdfFacture(Facture $facture) {

    global $cnx, $facturesManager;

	$facturesManager->checkTvaTiers($facture);

	$configManager = new ConfigManager($cnx);
	$pdf_top_factures = $configManager->getConfig('pdf_top_factures');
	$margeEnTetePdt = $pdf_top_factures instanceof Config ?  (int)$pdf_top_factures->getValeur() : 0;

    if ($facture->getId_adresse_facturation() == 0) {
		$facture->setId_adresse_facturation($facture->getId_adresse_livraison());
		$facturesManager->saveFacture($facture);
    }

    $web = $facturesManager->isFactureWeb($facture);


    $tiersManager = new TiersManager($cnx);
    $client = $tiersManager->getTiers($facture->getId_tiers_facturation());
    if ($client instanceof Tiers) {
        if ($client->getTva() == 0 && $facture->getMontant_interbev() > 0 || $web) {
            $facture->setMontant_interbev(0);
            $facturesManager->saveFacture($facture);
            foreach ($facture->getLignes() as $l) {
                $l->setTarif_interbev(0);
                $facturesManager->saveFactureLigne($l);
            }
			$facture = $facturesManager->getFacture($facture->getId());
        }
    }

    if ($web) {
        $facture = $facturesManager->calculeReductionsCommandesWeb($facture);
    }

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');
	ob_start();

	$content_fichier = genereContenuPdf($facture);
	$content_header = genereHeaderPdf($facture);
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $content_fichier;
	$contentPdf.= '</page>'. ob_get_clean();


	// Enregistrement de la version page unique
	try {
		$marges = [7, 12, 7, 12];
		$nom_fichier = $facture->getFichier();
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15', $marges);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($contentPdf));

		$dossier_facture =  $facturesManager->getDossierFacturePdf($facture);
		$savefilepath = __CBO_ROOT_PATH__ . $dossier_facture . $nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
	} catch (HTML2PDF_exception $e) {
		vd($e);
		return false;
	}

    // Suite à des problèmes d'arrondis à la première génération, on recalcule
	$facturesManager->recalculeMontantHtFacture($facture);
	$interbev = $facturesManager->getInterbevFacture($facture);
	$facture->setMontant_interbev($interbev);
	$facturesManager->saveFacture($facture);
	$facturesManager->razDateEnvoiFacture($facture);

	return true;


} // FIN fonction

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML de la facture
-----------------------------------------------------------------------------*/
function genereContenuPdf(Facture $facture) {

	global $cnx, $facturesManager;

	// SI la date ne correspond pas au numéro de facture on modifie le numéro de facture

	$date_mois = substr($facture->getDate(), 5,2);
	$date_an = substr($facture->getDate(), 2,2);

	$num_mois = substr($facture->getNum_facture(), 4,2);
	$num_an = substr($facture->getNum_facture(), 2,2);

	if ($date_mois != $num_mois || $date_an != $num_an) {
		$av =  substr($facture->getNum_facture(),0,2) == 'AV';
		$date = '20'.$date_an.'-'.$date_mois.'-01';

		$numFact = $facturesManager->getNextNumeroFacture($av, $date);
		if (strlen($numFact) < 7) {  exit('ERREUR REGENERE NUM FACT');}

		$facture->setNum_facture($numFact);
		$facturesManager->saveFacture($facture);
	}

	$tiersManager = new TiersManager($cnx);

	$avoir = $facture->getMontant_ht() < 0;

	$client = $tiersManager->getTiers($facture->getId_tiers_facturation());
	if (!$client instanceof Tiers) {
		$client = $tiersManager->getTiers($facture->getId_tiers_livraison());
	}
	if (!$client instanceof Tiers) { $client = new Tiers([]); } // Gestion des erreurs

    if ($client->getTva() == 0) {
        $facture->setMontant_tva(0);
    }

	$traductionsManager = new TraductionsManager($cnx);
	$id_langue = $client->getId_langue();

	$contenu ='<table class="table table-blfact w100">';
	$total_poids 	= 0.0;
	$total_montant 	= 0.0;
	$total_interbev = 0.0;
	$base_tva 		= 0.0;
	$montant_tva 	= 0.0;
	$tva 			= false;


    // On intègre les lignes si on ne les a pas déjà (génération d'avoir par exemple)
	if (!is_array($facture->getLignes())) {
	    $lignes = $facturesManager->getListeFactureLignes(['id_facture' => $facture->getId(), 'id_langue' => $id_langue]);
	    $facture->setLignes($lignes);;
    }

	$wExport = 26;
	if ($facture->getMontant_tva() == 0) { $wExport+= 12; }
	if ($facture->getMontant_interbev() == 0) { $wExport+= 12; }

	$id_client_web = $tiersManager->getId_client_web();
	$web = $id_client_web > 0 && $facture->getId_tiers_facturation() == $id_client_web;

	if ($web) {
		$orderPrestashopManager = new OrdersPrestashopManager($cnx);
		$orderPrestashopManager->cleanIdOrderDetailsLignesBl();

		// On récupère les commandes web de la facture

		//$ordersPrestaShop = $orderPrestashopManager->getOrdersPrestashopByFacture($facture);

		$total_livraison = $orderPrestashopManager->getLivraisonsOrdersFacture($facture);

		// On supprime les frais additionnels de transport auto-générés web pour cette facture
		$facturesManager->supprFactureFraisLivraisonWeb($facture);

		$taxesManager = new TaxesManager($cnx);
		$id_taxe = $taxesManager->getIdTaxeByTaux(20);

        if ($total_livraison > 0) {
			// On crée le nouveau frais de livraison
			$fraisLivraison = new FactureFrais([]);
			$fraisLivraison->setId_facture($facture->getId());
			$fraisLivraison->setNom($facturesManager->getLibelle_frais_livraison_web());
			$fraisLivraison->setValeur($total_livraison);
			$fraisLivraison->setId_taxe($id_taxe);
			$fraisLivraison->setType(0);
			$facturesManager->saveFactureFrais($fraisLivraison);
        }

	} // FIN test client web

    $pasVendusPiece = 0;

	foreach ($facture->getLignes() as $ligne) {

        $facturesManager->saveMontantInterbevLigne($ligne);

        if ($ligne->getProduit()->getVendu_piece() != 1) {
			$pasVendusPiece++;
        }

		$total_poids+=$ligne->getPoids();
        $total_qte+=$ligne->getQte();

		$total_montant+=round($ligne->getTotal(),2);
		$total_interbev+=round($ligne->getInterbev(),2);

		if ($ligne->getTva() != 0) {
		    $tva = true;
			$base_tva+=round($ligne->getTotal(),2);
		}

		$designation = '';
		if ($ligne->getDesignation() != '') {
			$designation = $ligne->getDesignation();

		} else {

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

			}// FIN test désignation libre / nom du produit

			if ((int)$ligne->getId_produit() > 0) {
				$designation.= $ligne->getNumlot() != '' ? '<p>'.$traductionsManager->getTrad('lot', $id_langue) . ' : ' . $ligne->getNumlot().'</p>' : '';
				$designation.= $ligne->getOrigine() != '' ? '<p>'.$traductionsManager->getTrad('origine', $id_langue) . ' : ' . $ligne->getOrigine().'</p>' : '';
				$designation.= $ligne->getProduit()->getEan13() != '' ? '<p>EAN : ' . $ligne->getProduit()->getEan13().'</p>' : '';
			}
		}

		// Si client web, on récup l'order_ligne si on l'a
		if ($web) {

			$od = $orderPrestashopManager->getOrderLigneByIdLigneFacture($ligne->getId());
			if ($od instanceof OrderDetailPrestashop) {
				$designation.= '<br>Commande Web '.$od->getReference_order() . '<br>'.$od->getNom().'<br>'.$od->getNom_client();
			}

		} // FIN web

        // DEBUG symbole non géré par HTML2PDF
        $designation =str_replace('œ', 'oe', $designation);
        $designation =str_replace('Œ', 'OE', $designation);
        $designation =str_replace('&OElig;', 'OE', $designation);
        $designation =str_replace('&oelig;', 'oe', $designation);
        $designation =str_replace('&#140;', 'OE', $designation);
        $designation =str_replace('&#156;', 'oe', $designation);


        $qte = $ligne->getProduit()->getVendu_piece() == 1 ? $ligne->getQte() : number_format($ligne->getPoids() ,3,'.', ' ').'kg';

        // Pour les commandes PrestaShop on a pas d'ID produit mais c'est la quantité qu'on remonte malgré tout :
        if ($ligne->getPoids() == 0 && $ligne->getId_produit() == 0) { $qte = $ligne->getQte(); }

        $code = intval($ligne->getCode()) > 0 ? $ligne->getCode() : '';

        // Pour les factures prestashop, on récupère la référence entre moustaches dans la désignation
        if ($code == '') {
			preg_match('~{(.*?)}~', $designation, $output);
            if (isset($output[1])) {
                $code = $output[1];
				$designation = str_replace('{'.$output[1].'}', '', $designation);
            }
        } // FIN code depuis prestashop

        // Pas une erreur si ci-dessous on teste le PUHT, c'est pour quand meme afficher 0 si on est pas en commande web !
		//$qteTxt = $qte > 0 ? $qte : '';
		$qteTxt =  ($ligne->getProduit()->getVendu_piece() == 1 || $ligne->getProduit()->getId() == 0) && $ligne->getQte() > 0 ? $ligne->getQte()  : '-';
		$poidsTxt = $ligne->getPoids() > 0 ? number_format($ligne->getPoids() ,3,'.', ' ').'kg' : '-';
        //$puHtTxt = $ligne->getPu_ht() > 0 ? number_format($ligne->getPu_ht() ,2,'.', ' ') : '';
        $puHtTxt = number_format($ligne->getPu_ht() ,2,'.', ' ');
        $mTvaTxt = $ligne->getMontant_tva() != 0 ? number_format($ligne->getMontant_tva() ,2,'.', ' ') : '-';
        $mInterbev = $ligne->getInterbev() != 0 ? number_format($ligne->getInterbev() ,2,'.', ' ') : '-';
        // $totalTxt = $ligne->getPu_ht() > 0 ? number_format($ligne->getTotal() ,2,'.', ' ') : '-';
        $totalTxt = $ligne->getTotal() != 0 ? number_format($ligne->getTotal() ,2,'.', ' ') : '-';

		$contenu .= '<tr>';
		$contenu .= '<td class="w8 border-l border-r">' . $code . '</td>';
		$contenu .= '<td class="w'.$wExport.' border-l border-r">' . $designation . '</td>';
		$contenu .= '<td class="w10 border-l border-r text-right">' . $poidsTxt . '</td>';
		$contenu .= '<td class="w10 border-l border-r text-right">' . $qteTxt . '</td>';
		$contenu .= '<td class="w10 border-l border-r text-right">' . $puHtTxt . '</td>';
		$contenu .=  $facture->getMontant_tva() != 0 ? '<td class="w12 border-l border-r text-right">' . $mTvaTxt. '</td>' : '';
		$contenu .=  $facture->getMontant_interbev() > 0 ?'<td class="w12 border-l border-r text-right">' . $mInterbev. '</td>' : '';
		$contenu .= '<td class="w12 border-l border-r text-right">' . $totalTxt . '</td>';
		$contenu .= '</tr>';

	} // FIN boucle sur les lignes

	$facture = $facturesManager->saveMontantInterbevFactureFromLignes($facture);

    $totPoidsLib = $total_qte == 0 ?  $traductionsManager->getTrad('poids_total', $id_langue) : 'Total';
    $totPoids = $total_poids > 0 ? number_format($total_poids ,3,'.', ' '). ' kg' : '';
    $totQte = $pasVendusPiece == count($facture->getLignes()) || $total_qte == 0 ? '-' : $total_qte;
    $totalAffiche = $totPoids;
    $totalAffiche.= $totPoids != '' && $totQte != '-' && $total_qte != 0 ? '(' : '';
    $totalAffiche.= $total_qte != 0 ? $total_qte : '';
    $totalAffiche.= $totPoids != '' && $totQte != '-'  && $total_qte != 0 ? ')' : '';

	if ($totPoids > 0 || $totQte > 0) {
			$contenu .= '<tr>';
			$contenu .= '<td class="w8 border-l border-r"></td>';
			$contenu .= '<td class="w'.$wExport.' border-l border-r text-right">' . $totPoidsLib .  '</td>';
			$contenu .= '<td class="w10 border-l border-r text-right">' . $totPoids. ' </td>';
			$contenu .= '<td class="w10 border-l border-r text-right">' . $totQte. ' </td>';
			$contenu .= '<td class="w10 border-l border-r text-right"></td>';
			$contenu .=  $facture->getMontant_tva() != 0 ? '<td class="w12 border-l border-r text-right"></td>' : '';
			$contenu .=  $facture->getMontant_interbev() > 0 ? '<td class="w12 border-l border-r text-right"></td>' : '';
			$contenu .= '<td class="w12 border-l border-r text-right"></td>';
			$contenu .= '</tr>';
        }


    // Intégration des frais additionnels
	$frais = $facturesManager->getListeFactureFrais($facture->getId());

	if (!empty($frais)) {

		// Boucle sur les frais
		foreach ($frais as $fra) {

			$infopc = $fra->getType() == 1 ? ' ('.$fra->getValeur() . ' %) ' : '';
			if ($fra->getType() == 0) {
				$frais_montant = $fra->getValeur();
			} else {
				$frais_montant = $total_montant * ($fra->getValeur() / 100);
			}
			$total_du+= $frais_montant;

/*			$libelleFrais = $fra->getNom();
			$libelleFrais.= $infopc;
			$libelleFrais.= $fra->getTaxe_taux() > 0 ? ' ['.$traductionsManager->getTrad('tva', $id_langue).' '.$fra->getTaxe_taux().'%]' : '';*/
            $codeFrais = strtoupper(substr(str_replace([' ', ',', '-'],'',Outils::removeAccents($fra->getNom())),0,5));
			$fraisTva = $fra->getTaxe_taux() > 0 ? $frais_montant * ($fra->getTaxe_taux() / 100) : 0;
			$fraisTvaTxt = $fraisTva > 0 ? number_format($fraisTva,2,'.', ' ') : '-';
            $fraisTtc = $fra->getTaxe_taux() > 0 ? $frais_montant * (1 +($fra->getTaxe_taux() / 100)) : $frais_montant;

			$contenu .= '<tr>';
			$contenu .= '<td class="w8 border-l border-r">' . $codeFrais . '</td>';
			$contenu .= '<td class="w'.$wExport.' border-l border-r">' . $fra->getNom() . '</td>';
			$contenu .= '<td class="w10 border-l border-r text-right">-</td>';
			$contenu .= '<td class="w10 border-l border-r text-right">-</td>';
			$contenu .= '<td class="w10 border-l border-r text-right">' . number_format($frais_montant,2,'.', ' ') . '</td>';
			$contenu .=  $facture->getMontant_tva() != 0 ? '<td class="w12 border-l border-r text-right">' . $fraisTvaTxt . '</td>' : '';
			$contenu .=  $facture->getMontant_interbev() > 0 ?'<td class="w12 border-l border-r text-right">-</td>' : '';
			$contenu .= '<td class="w12 border-l border-r text-right">' . number_format($frais_montant,2,'.', ' ') . '</td>';
			$contenu .= '</tr>';


			$total_montant+=$frais_montant;
			$base_tva+=round($frais_montant,2);
		} // FIN boucle sur les frais

	} // FIN frais


	$contenu.='</table>';

	$contenu.='<table class="table table-blfact w100">';

	$soumiTva =  $tva && $facture->getMontant_tva() != 0  && $client->getTva() == 1 ? '' : $traductionsManager->getTrad('non_tva', $id_langue);

	if (strtolower(substr($facture->getNum_facture(),0,2)) == 'av') {
		$soumiTva = '';
    }

	//$wExportA = $facture->getMontant_tva() == 0 && $facture->getMontant_interbev() == 0 ? 78 : 76;
	$wExportA = $facture->getMontant_tva() == 0 && $facture->getMontant_interbev() == 0 ? 68 : 66;
	//$wExportB = $facture->getMontant_tva() == 0 && $facture->getMontant_interbev() == 0 ? 10 : 12;
	$wExportB = $facture->getMontant_tva() == 0 && $facture->getMontant_interbev() == 0 ? 20 : 22;

	$contenu .= '<tr>';
	$contenu .= '<td class="w'.$wExportA.' border-b-0 border-l border-r text-center"></td>';
	$contenu .= '<td class="w'.$wExportB.' border-l border-r text-right" style="font-size: 8px;">'.$traductionsManager->getTrad('montant', $id_langue).' '.$traductionsManager->getTrad('ht', $id_langue).'</td>';
	$contenu .= '<td class="w12 border-l border-r text-right">'.number_format($total_montant, 2,'.', ' ').'</td>';
	$contenu .= '</tr>';


	if ((float)$total_interbev != 0) {
		$contenu .= '<tr>';
		$contenu .= '<td class="w'.$wExportA.' border-b-0 border-l border-r text-center"></td>';
		$contenu .= '<td class="w'.$wExportB.' border-l border-r text-right" style="font-size: 8px;">'.$traductionsManager->getTrad('interbev', $id_langue).'</td>';
		$contenu .= '<td class="w12 border-l border-r text-right">'.number_format($total_interbev, 2,'.', ' ').'</td>';
		$contenu .= '</tr>';
    }


	// Ici au lieu de ci-dessous, boucle sur les différents taux de tva de la facture, et pour chacun, une ligne "TVA XX %" -> total de ce taux
    $tvas_facture = $facturesManager->getTvasFacture($facture);



	// On intègre les TVA des frais s'il y en a
	foreach ($frais as $f) {
	    if ((float)$f->getTaxe_taux() == 0) { continue; }
        if (!isset($tvas_facture[(string)$f->getTaxe_taux()])) {
			$tvas_facture[(string)$f->getTaxe_taux()] = 0;
        }
		if ($f->getType() == 0) {
			$tvaFrais = $f->getValeur() * ($f->getTaxe_taux()/100);
		} else {
			$tvaFrais = ($total_montant * ($f->getValeur() / 100)) * ($f->getTaxe_taux()/100);
		}
		//$montant_tva+=$tvaFrais;
        $tt = (string)$f->getTaxe_taux() == '5' ? '5.5' : (string)$f->getTaxe_taux();
		$tvas_facture[$tt]+= $tvaFrais;

    } // FIn boucle sur les frais additionnel pour prise en compte des TVA

    // On rajoute l'interbev à la TVA 5.5
	if ((float)$total_interbev != 0) {
        // On récupère le taux par la config
        if (!isset($configManager)) {
			$configManager = new ConfigManager($cnx);
        }
        $tva_interbev = $configManager->getConfig('tva_interbev');
        if (!$tva_interbev instanceof Config) { exit('ERREUR RECUP TAUX TVA INTERBEV !'); }
		$taxesManager = new TaxesManager($cnx);
        $taxeInterbevTva = $taxesManager->getTaxe((int)$tva_interbev->getValeur());
        if (!$taxeInterbevTva instanceof Taxe) { exit('ERREUR RECUP TAXE TVA POUR INTERBEV !'); }

		$tvaInterbev = $total_interbev * ($taxeInterbevTva->getTaux()/100);

        if (!isset($tvas_facture[(string)$taxeInterbevTva->getTaux()])) {
			$tt = (string)$taxeInterbevTva->getTaux() == '5' ? '5.5' : (string)$taxeInterbevTva->getTaux();
			$tvas_facture[$tt] = 0.0;
        }
		$tt = (string)$taxeInterbevTva->getTaux() == '5' ? '5.5' : (string)$taxeInterbevTva->getTaux();
		$tvas_facture[$tt]+=$tvaInterbev;
		$base_tva+=round($total_interbev,2);
	}

    if (!empty($tvas_facture) && $client->getTva() == 1) {
		$contenu .= '<tr>';
		$contenu .= '<td class="w'.$wExportA.' border-b-0 border-l border-r text-center">' . $soumiTva .  '</td>';
		$contenu .= '<td class="w'.$wExportB.' border-l border-r text-right" style="font-size: 8px;">'.$traductionsManager->getTrad('base_tva', $id_langue).'</td>';
		$contenu .= '<td class="w12 border-l border-r text-right">'.number_format($base_tva, 2,'.', ' ').'</td>';
		$contenu .= '</tr>';
    }



    if ($client->getTva() == 1 && !empty($tvas_facture) ) {
/*		foreach ($tvas_facture as $pourcentage => $montantTvaTaux) {
            if ((string)$pourcentage == '5') {
				unset($tvas_facture['5']);
                if (!isset($tvas_facture['5.5'])) {
					$tvas_facture['5.5'] = 0;
                }
				$tvas_facture['5.5']+= $montantTvaTaux;
            }
		}*/
		foreach ($tvas_facture as $pourcentage => $montantTvaTaux) {

			$contenu .= '<tr>';
			$contenu .= '<td class="w'.$wExportA.' border-b-0 border-l border-r text-center"></td>';
			$contenu .= '<td class="w'.$wExportB.' border-l border-r text-right" style="font-size: 8px;">'. $traductionsManager->getTrad('tva', $id_langue).' '. (string)$pourcentage.'%'.'</td>';
			$contenu .= '<td class="w12 border-l border-r text-right">'.number_format($montantTvaTaux, 2,'.', ' ').'</td>';
			$contenu .= '</tr>';
			$montant_tva+=round($montantTvaTaux,2);
		} // FIN boucle sur les taux de tva
    }


    if ($client->getTva() == 0) {
	    $montant_tva = 0;
    }

	//$total_du = round($total_montant,2) + round($montant_tva,2) + round($total_interbev,2);
	$total_du = round(round($total_montant,2) + round($total_interbev,2) + round($montant_tva,2),2);

    $facture->setTotal_ttc($total_du);
    $facturesManager->saveFacture($facture);

    $isPaysExport = $facturesManager->isPaysExport($facture);

    $blocTxtTvaExport = $isPaysExport ?  'txt_exotva_export' : 'txt_exotva';


    $exoTva = $client->getTva() == 0 ? $traductionsManager->getTrad($blocTxtTvaExport, $id_langue) : '';

	$contenu .= '<tr>';
	$contenu .= '<td class="w'.$wExportA.' border-l border-r text-center"></td>';
	$contenu .= '<td class="w'.$wExportB.' border-l border-r text-right" style="font-size: 8px;">'.$traductionsManager->getTrad('total_du', $id_langue).'</td>';
	//$contenu .= '<td class="w12 border-l border-r text-right"  style="font-size: 12px;">'.number_format($total_du, 2,'.', ' ').'</td>';
	$contenu .= '<td class="w12 border-l border-r text-right"  style="font-size: 12px;">'.number_format($total_du, 2,'.', ' ').'</td>';
	$contenu .= '</tr>';

	$contenu.='</table>';

	$contenu.='<table class="table table-blfact w100 mt-15">';
	$contenu.='<tr>';
	$contenu.='<td class="w50" style="font-size: 8px;" style="font-size: 8px;">'.nl2br(str_replace('€', ' euro',$traductionsManager->getTrad('txt_escompte', $id_langue))) .'</td>';
	$contenu.='<td class="w50 text-right"  style="font-size: 8px;">'.$exoTva.'</td>';
	$contenu.='</tr>';
	$contenu.='</table>';

	$nbBls = count($facture->getBls());
	$numBl = '';
	if ($nbBls > 1) {
		$numBlArrays = [];
		foreach ($facture->getBls() as $blFact) {
			if ($blFact instanceof Bl) {
				$numBlArrays[] = $blFact->getNum_bl();
			}
		}
		$numBl = implode(' ', $numBlArrays);
	}
	$contenu.= $nbBls > 1 ? '<p class="text-8 mt-10">'.$numBl.'</p>' : '';

	// Message fixe client
	if (trim($client->getMessage()) != '') {
		$contenu.='<p class="text-8">'.nl2br(strip_tags($client->getMessage())).'</p>';
	}

	//$contenu.= '</div>';


	$contenu = str_replace('Œ', 'OE', $contenu);
	// RETOUR CONTENU
	return $contenu;

} // FIN fonction déportée

// Supprime une facture
function modeSupprFacture() {

    global $facturesManager, $cnx;

	$logsManager = new LogManager($cnx);

    $id_facture = isset($_REQUEST['id_facture']) ? intval($_REQUEST['id_facture']) : 0;
    if ($id_facture == 0) { exit('-1'); }

    $facture = $facturesManager->getFacture($id_facture, false);
    if (!$facture instanceof Facture) { exit('-2'); }

	$facture->setSupprime(1);
    $res = $facturesManager->saveFacture($facture);

	echo $res ? 1 : 0;
	$logTexte = $res ? 'S' : 'Echec de la s';
	$logTexte.='uppression de la facture (flag) #'.$id_facture;
	$logtype = $res ? 'info' : 'danger';
	$log = new Log([]);
	$log->setLog_texte($logTexte);
	$log->setLog_type($logtype);
	$logsManager->saveLog($log);

    exit;



} // FIN mode


// Modale de sélection de l'adresse pour envoi d'une facture à un client par mail
function modeModalEnvoiMail() {

	global $cnx, $facturesManager;

	$id_facture = isset($_REQUEST['id_facture']) ? intval($_REQUEST['id_facture']) : 0;
	if ($id_facture == 0) { exit; }

    $facture = $facturesManager->getFacture($id_facture);
    if (!$facture instanceof Facture) { exit; }

	$tiers = [$facture->getId_tiers_facturation(), $facture->getId_tiers_livraison()];

	$contactsManager = new ContactManager($cnx);
	$contacts = $contactsManager->getListeContacts(['id_tiers' => implode(',', $tiers)]);

	$emails = [];
	foreach ($contacts as $ctc) {

		if (!Outils::verifMail($ctc->getEmail())) { continue; }

		$tmp = [];
		$tmp['nom'] = $ctc->getNom_complet();
		$tmp['id'] = $ctc->getId();

		$emails[$ctc->getEmail()] = $tmp;
	}

	// Si aucune adresse e-mail
	if (empty($emails)) { ?>
        <div class="alert alert-warning">
          <i class="fa fa-exclamation-circle mr-1"></i> Aucune adresse e-mail valide renseignée pour ce client !
        </div>
		<?php }
	?>

    <div class="row mb-0">
        <input type="hidden" id="idFactureFromModaleMail" value="<?php echo $facture->getId(); ?>"/>
        <?php
	    if (!empty($emails)) { ?>
        <div class="col-12 texte-fin text-13">
            Sélectionnez les destinataires :
        </div>
        <div class="col-12 mb-2">
            <select class="selectpicker form-control" multiple>
				<?php
				foreach ($emails as $mail => $donnees) {

					$nom = isset($donnees['nom']) ? trim($donnees['nom']) : '';
					$id = isset($donnees['id']) ? intval($donnees['id']) : 0;
					if ($id == 0) { continue; }
					?>
                    <option value="<?php echo $id; ?>" data-subtext="<?php echo $nom; ?>"><?php echo $mail; ?></option>
				<?php }
				?>
            </select>
        </div>
        <?php } ?>
        <div class="col-12 texte-fin text-13 mt-2">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Autre</span>
                </div>
                <input type="text" id="emailcustom" placeholder="exemple@domaine.com" value="" class="form-control"/>
            </div>
        </div>
		<?php
		if ($facture->getDate_envoi() != '') { ?>
            <div class="col-6">
                <div class="alert alert-info mt-3 texte-fin text-12">
                    <i class="fa fa-info-circle mr-1 text-infof"></i>
                    Déjà envoyée le <?php echo Outils::dateSqlToFr($facture->getDate_envoi());
                    ?>
                </div>
            </div>
		<?php }
		?>
        <div class="col-<?php echo $facture->getDate_envoi() != '' ? '6' : '12';?> text-right">
            <div class="mt-3 texte-fin text-13 ">
                Recevoir une copie

                <input type="checkbox" class="togglemaster"
                       data-toggle              = "toggle"
                       data-on                  = "Oui"
                       data-off                 = "Non"
                       data-onstyle             = "info"
                       data-offstyle            = "secondary"
                       data-height                = "20"
                       checked
                />
            </div>
        </div>
    </div>

	<?php

} // FIN mode


// Envoi de la facture au client par e-mail
function modeEnvoiPdfClient() {

	global $cnx, $facturesManager, $conf_email; // $conf_email = from

	// Plus d'infos sur : https://shorturl.at/cxyJ7

	$tiersManager = new TiersManager($cnx);

	$id_facture = isset($_REQUEST['id_facture']) ? intval($_REQUEST['id_facture']) : 0;
	if ($id_facture == 0) { exit('-1'); }

	$facture = $facturesManager->getFacture($id_facture, false);
    if (!$facture instanceof Facture) { exit('-2'); }

	$ids_ctcs = isset($_REQUEST['id_ctc']) ? explode(',',$_REQUEST['id_ctc']) : [];

	$autre_mail = isset($_REQUEST['mail']) ? trim(strtolower($_REQUEST['mail'])) : '';
	if ($autre_mail != '' && !Outils::verifMail($autre_mail)) { $autre_mail = ''; }

	if ($autre_mail == '' && empty($ids_ctcs)) { exit; }

	$client_id = $facture->getId_tiers_livraison() > 0 ? $facture->getId_tiers_livraison() : $facture->getId_tiers_facturation();
	if ($client_id == 0) { exit; }
	$client = $tiersManager->getTiers($client_id);
	if (!$client instanceof Tiers) { exit; }

	$contactsManager = new ContactManager($cnx);

	foreach ($ids_ctcs as $id_ctc) {
		$ctc = $contactsManager->getContact($id_ctc);
		if (!$ctc instanceof Contact) { continue; }
		if (!Outils::verifMail($ctc->getEmail())) { continue; }
		$dest[] = $ctc->getEmail();
	}

	if ($autre_mail != '') {
		$dest[] = $autre_mail;
	}

	if (empty($dest)) { exit; }

	$cc = isset($_REQUEST['cc']) ? intval($_REQUEST['cc']) : 0;
	if ($cc == 1) {
		$configManager = new ConfigManager($cnx);
		$cc_mails = $configManager->getConfig('cc_mails');
		$mails_cc = explode(';', $cc_mails->getValeur());
		$dest_cc = [];
		foreach ($mails_cc as $mcc) {
			$mcc = trim(strtolower($mcc));
			if (!Outils::verifMail($mcc)) { continue; }
			$dest_cc[] = $mcc;
		}
	}

	$dossier_facture =  $facturesManager->getDossierFacturePdf($facture);
	$chemin = __CBO_ROOT_PATH__.$dossier_facture.$facture->getFichier();

	$traductionsManager = new TraductionsManager($cnx);
	$titre = 'PROFIL EXPORT - '. $traductionsManager->getTrad('facture', $client->getId_langue());

	if (strtolower(substr($facture->getNum_facture(),0,2)) == 'av') {
		$titre = 'PROFIL EXPORT - '. $traductionsManager->getTrad('avoir', $client->getId_langue());
	}

	$texte = nl2br($traductionsManager->getTrad('mail_facture', $client->getId_langue()));
	$nom = isset($ctc) ? $ctc->getNom_complet() : '';
	$texte = str_replace('[NOM]', $nom, $texte);
	$texte = str_replace('[NUMFACT]', $facture->getNum_facture(), $texte);

	if (strtolower(substr($facture->getNum_facture(),0,2)) == 'av') {
		$texte = str_replace('facture', 'avoir', $texte);
	}

	$contenu = Outils::formatContenuMailClient($texte);
	if (!Outils::envoiMail($dest, $conf_email, $titre, utf8_decode($contenu), 0, $dest_cc, [$chemin])) {
        echo '0'; exit;
    }

    echo '1';
	$facture->setDate_envoi(date('Y-m-d H:i:s'));

	if ($facturesManager->saveFacture($facture)) {
		$log = new Log();
		$log->setLog_type('info');
		$log->setLog_texte("Envoi par mail de la facture #".$facture->getId());
		$logsManager = new LogManager($cnx);
		$logsManager->saveLog($log);
	}
	exit;

} // FIN mode




// Ajoute une ligne de produit à une nouvelle facture (affichage, pas BDD)
function modeAddLigneNouvelleFacture() {

	global $cnx, $utilisateur;

	$produitsManager = new ProduitManager($cnx);
	$paysManager = new PaysManager($cnx);

	$id_pdt = isset($_REQUEST['id_pdt']) ? intval($_REQUEST['id_pdt']) : 0;
	$id_pays = isset($_REQUEST['id_pays']) ? intval($_REQUEST['id_pays']) : 0;
	$nb_colis = isset($_REQUEST['nb_colis']) ? intval($_REQUEST['nb_colis']) : 0;
	$qte = isset($_REQUEST['qte']) ? intval($_REQUEST['qte']) : 1;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0;
	$pu_ht = isset($_REQUEST['pu_ht']) ? floatval($_REQUEST['pu_ht']) : 0;
	$pa_ht = isset($_REQUEST['pa_ht']) ? floatval($_REQUEST['pa_ht']) : 0;
	$numlot = isset($_REQUEST['numlot']) ? trim($_REQUEST['numlot']) : '';
	if ($id_pdt == 0) { exit; }

	$produit = $produitsManager->getProduit($id_pdt);
	if (!$produit instanceof Produit) { exit; }

	$pays = $id_pays > 0 ? $paysManager->getPays($id_pays) : false;
	if (!$pays instanceof Pays) { $pays = new Pays([]); }

	$na = '<span class="gris-9">&mdash;</span>';
    $multiplicateur = $poids > 0 ? $poids : $qte;
    $total = $pu_ht * $multiplicateur;


	$retour = json_encode([
	        'id_pdt' => $id_pdt,
	        'id_pays' => $id_pays,
	        'nb_colis' => $nb_colis,
	        'qte' => $qte,
	        'poids' => $poids,
	        'pu_ht' => $pu_ht,
	        'pa_ht' => $pa_ht,
	        'numlot' => $numlot
    ]);

    ?>
    <tr>
        <td class="id_pdt" data-id="<?php echo $produit->getId();?>"><?php echo $produit->getCode();?></td>
        <td><?php echo $produit->getNom();?></td>
        <td><?php echo $pays->getNom();?></td>
        <td><?php echo $numlot != '' ? $numlot : $na;?></td>
        <td class="text-center"><?php echo $nb_colis > 0 ? $nb_colis : $na;?></td>
        <td class="text-center qte"><?php echo $qte;?></td>
        <td class="text-right"><?php echo $poids > 0 ? $poids : $na;?></td>
        <td class="text-right pu_ht"><?php echo $pu_ht > 0 ? $pu_ht : $na;?></td>
        <td class="text-right total_prix"><?php echo $total > 0 ? $total : $na;?></td>
        <td class="text-right pa_ha <?php echo !$utilisateur->isDev() && !$utilisateur->isAdmin() ? 'd-none' : ''; ?>"><?php echo $pa_ha > 0 ? number_format($pa_ha,2,'.',' ') : '0.00';?></td>
        <td class="t-actions text-center"><button type="button" class="btn btn-sm btn-danger btnSupprLigneFacture"><i class="fa fa-trash-alt"></i></button></td>
    </tr>
    ^
    <input type="hidden" name="produits[]" class="pdt<?php echo $produit->getId(); ?>" value='<?php echo $retour; ?>'>
    <?php
    exit;
} // FIN mode


// Enregistre une facturée créé manuellement sans BL
function modeSaveFacture() {

    global $facturesManager, $cnx;

	$produitsManager = new ProduitManager($cnx);
	$paysManager = new PaysManager($cnx);

    $num_cmd = isset($_REQUEST['num_cmd']) ? trim($_REQUEST['num_cmd']): '';
    $id_t_fact = isset($_REQUEST['id_t_fact']) ? intval($_REQUEST['id_t_fact']) : 0;
    $id_t_livr = isset($_REQUEST['id_t_livr']) ? intval($_REQUEST['id_t_livr']) : 0;
    $id_adresse = isset($_REQUEST['id_adresse']) ? intval($_REQUEST['id_adresse']) : 0;
    $id_transp = isset($_REQUEST['id_transp']) ? intval($_REQUEST['id_transp']) : 0;
    $date = isset($_REQUEST['date']) ? Outils::dateFrToSql($_REQUEST['date']) : date('Y-m-d');
    if (!Outils::verifDateSql($date)) { $date = date('Y-m-d'); }

    $produits = isset($_REQUEST['produits']) ? $_REQUEST['produits'] : [];

    if ($id_t_fact == 0 && $id_t_livr == 0) { exit('-1'); }
    if (!is_array($produits) || empty($produits)) { exit('-2'); }

    $facture = new Facture([]);
	$facture->setDate($date);
	$facture->setDate_add(date('Y-m-d H:i:s'));
	$facture->setNum_cmd($num_cmd);
	$facture->setId_tiers_facturation($id_t_fact);
	$facture->setId_tiers_livraison($id_t_livr);
	$facture->setId_adresse_facturation($id_adresse);
	$facture->setId_tiers_transporteur($id_transp);

	$num_fact = $facturesManager->getNextNumeroFacture();
	$facture->setNum_facture($num_fact);

	$res = $facturesManager->saveFacture($facture);
	if (!$res || intval($res) == 0) { exit('-3'); }

	$id_facture = intval($res);
	$facture->setId($id_facture);
	$total_ht = 0.0;
	foreach ($produits as $pdtJson) {

	    $pdt_donnees = json_decode($pdtJson, true);
        $id_pdt = isset($pdt_donnees['id_pdt']) ? intval($pdt_donnees['id_pdt']) : 0;
        $id_pays = isset($pdt_donnees['id_pays']) ? intval($pdt_donnees['id_pays']) : 0;
        $nb_colis = isset($pdt_donnees['nb_colis']) ? intval($pdt_donnees['nb_colis']) : 0;
        $qte = isset($pdt_donnees['qte']) ? intval($pdt_donnees['qte']) : 0;
        $poids = isset($pdt_donnees['poids']) ? floatval($pdt_donnees['poids']) : 0.0;
        $pu_ht = isset($pdt_donnees['pu_ht']) ? floatval($pdt_donnees['pu_ht']) : 0.0;
        $pa_ht = isset($pdt_donnees['pa_ht']) ? floatval($pdt_donnees['pa_ht']) : 0.0;
        $numlot = isset($pdt_donnees['numlot']) ? trim($pdt_donnees['numlot']) : '';



        if ($id_pdt == 0) { continue; }

        $tva = $produitsManager->getTvaProduit($id_pdt);
		if ($tva < 0) { $tva = 0; }
		$interbev = 0;
		if ($id_pays > 0) {
			$pays = $paysManager->getPays($id_pays);
			if ($pays instanceof Pays) {
				if (strtoupper($pays->getIso() != 'FR')) {
					$interbev = $facturesManager->getTarifInterbevLigneFacture($id_pdt, $id_adresse);
				}
			}
        }

		$pdt = $produitsManager->getProduit($id_pdt, false);
		if ($pdt instanceof Produit) {
			$vendu_piece = $pdt->isVendu_piece() ? 1 : 0;
		    $mult = $pdt->isVendu_piece() ?  $qte : $poids;
		    if ($mult == 0) { $mult = 1; }
			$total_ht+= ($pu_ht * $mult);
        }




		$facture_ligne = new FactureLigne([]);
		$facture_ligne->setId_facture($id_facture);
		$facture_ligne->setDate_add(date('Y-m-d H:i:s'));
		$facture_ligne->setNb_colis($nb_colis);
		$facture_ligne->setPoids($poids);
		$facture_ligne->setId_produit($id_pdt);
		$facture_ligne->setNumlot($numlot);
		$facture_ligne->setVendu_piece($vendu_piece);
		$facture_ligne->setId_pays($id_pays);
		$facture_ligne->setPu_ht($pu_ht);
		$facture_ligne->setPa_ht($pa_ht);
		$facture_ligne->setQte($qte);
		$facture_ligne->setTva($tva);
		$facture_ligne->setTarif_interbev($interbev);
		$res = $facturesManager->saveFactureLigne($facture_ligne);

		// Si on échoue à enregistrer ne serait-ce qu'un seul produit, on annule tout !
		if (!$res || intval($res) == 0) {
		    $facturesManager->supprFacture($facture);
		    exit('-4');
		}

    } // FIN boucle sur les produits

    // On ré-hydrate l'objet Facture pour intégrer les lignes
	$facture2 = $facturesManager->getFacture($id_facture);

	$facture2->setMontant_interbev($facturesManager->getInterbevFacture($facture));
	$facture2->setMontant_tva($facturesManager->getTvaFacture($facture));
	$facture2->setMontant_ht($total_ht);
	$facturesManager->saveFacture($facture2);

	// On crée le PDF de la facture...
	if (!generePdfFacture($facture2)) { exit('-5');}

	// On log...
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Création de la facture manuelle (hors BL) #" . $id_facture);
	$logManager = new LogManager($cnx);
	$logManager->saveLog($log);

    echo '1';exit;

} // FIN mode


// Retourne la liste des frais d'une facture (modale)
function modeGetFraisFactureModale() {

	global $facturesManager;
	$id_facture = isset($_REQUEST['id_facture']) ? intval($_REQUEST['id_facture']) : 0;
	if ($id_facture == 0) { exit; }

	$frais = $facturesManager->getListeFactureFrais($id_facture);
	if (empty($frais)) { exit; }

	?>
    <table class="table admin table-blfact table-v-middle">
        <thead>
            <tr>
                <th>Frais</th>
                <th>Valeur</th>
                <th>TVA</th>
                <th>Suppr.</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($frais as $fra) {
            if ($fra->getValeur() == 0) { continue; }
            ?>
            <tr>
                <td class="nowrap"><?php echo $fra->getNom();?></td>
                <td class="text-right w-150px"><?php echo $fra->getValeur();
                echo $fra->getType() == 0 ? ' €' : ' %';
                ?></td>
                <td class="nowrap"><?php echo (int)$fra->getTaxe_taux() > 0 ? $fra->getTaxe_taux().' %' : '&mdash;';?></td>
                <td class="t-actions text-center w-75px">
                    <button type="button" class="btn btn-danger btn-sm btnSupprFactureFrais" data-id="<?php echo $fra->getId(); ?>"><i class="fa fa-trash-alt"></i></button>
                </td>
            </tr>
		<?php } ?>
        </tbody>
    </table>
    <?php
    exit;
} // FIN mode

// Supprime une ligne de frais de facture
function modeSupprFactureFrais() {

	global $facturesManager, $cnx;

	$id_frais = isset($_REQUEST['id_frais']) ? intval($_REQUEST['id_frais']) : 0;
	if ($id_frais == 0) { exit('-1'); }

	$frais = $facturesManager->getFactureFrais($id_frais);
	if (!$frais instanceof FactureFrais) { exit('-2'); }

	$facture = $facturesManager->getFacture($frais->getId_facture());
	if (!$facture instanceof Facture) { exit('-3'); }

	if ($facture->getDate_compta() != '') { exit; }

	if (!$facturesManager->suppprimeFactureFrais($frais)) { exit('0'); }

	// On regénère le PDF sans ce frais...
	if (!generePdfFacture($facture)) { $facturesManager->suppprimeFactureFrais($frais); exit('-6'); }

	// Log
	$logManager = new LogManager($cnx);
	$log = new Log([]);
	$log->setLog_texte("Suppression de la ligne de frais #" . $id_frais." à la facture #" . $frais->getId_facture());
	$log->setLog_type('info');
	$logManager->saveLog($log);

	exit('1');

} // FIN mode


// Ajoute un frais de facture et regénère le PDF
function modeAddFactureFrais() {

    global $facturesManager, $cnx;

    $id_facture = isset($_REQUEST['id_facture']) ? intval($_REQUEST['id_facture']) : 0;
    $valeur = isset($_REQUEST['valeur']) ? floatval(str_replace(',', '.', $_REQUEST['valeur'])) : 0;
    $nom = isset($_REQUEST['nom']) ? strtoupper(trim($_REQUEST['nom'])) : '';
    $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
    $id_taxe = isset($_REQUEST['id_taxe']) ? intval($_REQUEST['id_taxe']) : 0;
    if ($id_facture == 0) { exit('-1'); }
    if ($nom == '') { exit('-2'); }
    if ($valeur == 0) { exit('-3'); }

	$facture = $facturesManager->getFacture($id_facture);
	if (!$facture instanceof Facture) { exit('-4'); }

	if ($facture->getDate_compta() != '') { exit; }

    $frais = new FactureFrais([]);
    $frais->setId_facture($id_facture);
	$frais->setNom($nom);
	$frais->setValeur($valeur);
	$frais->setId_taxe($id_taxe);
	$frais->setType($type);
	$id_frais = $facturesManager->saveFactureFrais($frais);
	if (intval($id_frais) == 0) { exit('-5'); }

    // PDF... En cas d'échec on supprime les frais qu'on a ajouté pour toujours avoir une cohérence PDF/BDD
    if (!generePdfFacture($facture)) { $facturesManager->suppprimeFactureFrais($frais); exit('-6'); }

	// Log
	$logManager = new LogManager($cnx);
	$log = new Log([]);
	$log->setLog_texte("Ajout d'une ligne de frais #" . $id_frais." à la facture #" . $id_facture);
	$log->setLog_type('info');
	$logManager->saveLog($log);

    exit('1'); // Retour CallBack Ajax

} // FIN mode


/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère les lignes de titre du tableau (header)
-----------------------------------------------------------------------------*/
function getDebutTableauFacture(Facture $facture) {

	global $cnx;
	$traductionsManager = new TraductionsManager($cnx);

	$contenu= '';
	return $contenu;
} // FIN fonction

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML du header
-----------------------------------------------------------------------------*/
function genereHeaderPdf(Facture $facture, $html_additionnel = '') {

	global $cnx;
	$documentsManager = new DocumentManager($cnx);
	$traductionsManager = new TraductionsManager($cnx);

	$avoir = $facture->getMontant_ht() < 0;
	$type = $avoir ? 'avoir' : 'facture';

	$tiersManager = new TiersManager($cnx);
	$client = $tiersManager->getTiers($facture->getId_tiers_livraison());
	if (!$client instanceof Tiers) {
		$client = $tiersManager->getTiers($facture->getId_tiers_facturation());
	}
	if (!$client instanceof Tiers) { $client = new Tiers([]); } // Gestion des erreurs
	$id_langue = $client->getId_langue();
	$contenu = $documentsManager->getHeaderDocumentPdf($client, 'f', $type, $id_langue, true, true, $facture->getNum_facture(), $html_additionnel);

	$clt_livraison = $tiersManager->getTiers($facture->getId_tiers_livraison());
	if (!$clt_livraison instanceof Tiers) { $clt_livraison = new Tiers([]); }
	$adressesManager =  new AdresseManager($cnx);
	$adresse_livraison_obj = $adressesManager->getTiersAdresse($clt_livraison, 'l');
	$adresse_livraison = '';
    if ($adresse_livraison_obj instanceof Adresse) {
		if ($adresse_livraison_obj->getNom() != '') {
			$adresse_livraison = $adresse_livraison_obj->getNom().' ';
        } else {
			$adresse_livraison = $clt_livraison->getNom(). ' ';
        }
		$adresse_livraison.= $adresse_livraison_obj->getAdresse_ligne();
    }

	$contenu.='<div id="livraisonA">';
	$contenu.= $avoir || $adresse_livraison != '' ? $traductionsManager->getTrad('livraison_a', $id_langue).' '.$adresse_livraison : ' &nbsp; ';
	$contenu.='<p class="pt-5">';
	$contenu.=$client->getTva_intra() != '' ? $traductionsManager->getTrad('ident_intra', $id_langue) .' : '.$client->getTva_intra() : ' &nbsp; ';
	$contenu.='</p></div>';

	$date_echeance = new DateTime($facture->getDate());
	$dateIntervale =  (int)$client->getEcheance() > 0 ? 'P'. $client->getEcheance().'D' : 'P1D';
	$date_echeance->add(new DateInterval($dateIntervale));
	$date_echeance_format = $date_echeance->format('d/m/Y');

    $nbBls = count($facture->getBls());
	$numBl = '';
    if ($nbBls == 1) {
        $blfact = isset($facture->getBls()[0]) ? $facture->getBls()[0] : '';
        $numBl = $blfact instanceof Bl ? $blfact->getNum_bl() : '';
    }


	$espace = '<span class="espace"> - </span>';
	$contenu.='<table class="table table-blfact no-bb w100 mt-15">';
	$contenu.='<tr class="">';
	$contenu.='<td class="w100 text-center">';
	$contenu.= $traductionsManager->getTrad($type, $id_langue) . ' N° ' . $facture->getNum_facture();
	$contenu.= $espace.$traductionsManager->getTrad('date', $id_langue) . ' : ' . Outils::dateSqlToFr($facture->getDate());
	$contenu.= $avoir ? '' : $espace.$traductionsManager->getTrad('reglement', $id_langue) . ' : ' . $client->getEcheance() . ' ' . $traductionsManager->getTrad('jours', $id_langue) ;
	$contenu.= $avoir ? '' : $espace.$traductionsManager->getTrad('echeance', $id_langue) . ' : ' . $date_echeance_format ;
	$contenu.= $facture->getNum_cmd() != '' ? $espace.$traductionsManager->getTrad('num_cmd', $id_langue) . ' : ' . $facture->getNum_cmd() : '';
	$contenu.= $nbBls == 1 ? $espace.$numBl : '';
	$contenu.= $espace.$traductionsManager->getTrad('fact_etablie', $id_langue) .' Euros';
	$contenu.='</td>';
	$contenu.='</tr>';
	$contenu.='</table>';

	$wExport = 26;
	if ($facture->getMontant_tva() == 0) { $wExport+= 12; }
	if ($facture->getMontant_interbev() == 0) { $wExport+= 12; }

	$contenu.='<table class="table table-blfact w100 mt-15">';
	$contenu.='<tr class="entete">';
	$contenu.='<td class="w8 border-r" style="font-size: 8px;">'.$traductionsManager->getTrad('code', $id_langue).'</td>';
	$contenu.='<td class="w'.$wExport.' border-l border-r" style="font-size: 8px;">'.$traductionsManager->getTrad('designation', $id_langue).'</td>';
	$contenu.='<td class="w10 text-right border-l border-r" style="font-size: 8px;">'.$traductionsManager->getTrad('poids', $id_langue).'</td>';
	$contenu.='<td class="w10 text-right border-l border-r" style="font-size: 8px;">'.$traductionsManager->getTrad('quantite', $id_langue).'</td>';
	$contenu.='<td class="w10 text-right border-l border-r" style="font-size: 8px;">'.$traductionsManager->getTrad('prix_unit', $id_langue).'</td>';
	$contenu.= $facture->getMontant_tva() != 0 ? '<td class="w12 text-right border-l border-r" style="font-size: 8px;">'.$traductionsManager->getTrad('tva', $id_langue).'</td>' : '';
	$contenu.=$facture->getMontant_interbev() != 0 ? '<td class="w12 text-right border-l" style="font-size: 8px;">'.$traductionsManager->getTrad('interbev', $id_langue).'</td>' : '';
	$contenu.='<td class="w12 text-right border-l border-r" style="font-size: 8px;">'.$traductionsManager->getTrad('montant', $id_langue).' '.$traductionsManager->getTrad('ht', $id_langue).'</td>';
	$contenu.='</tr>';
	$contenu.='</table>';

	return $contenu;

} // FIN fonction


// Supprime le règlement d'une facture
function modeSupprReglementFacture() {

	global $facturesManager, $utilisateur, $cnx;

	$id_reglement = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id_reglement == 0) { exit('-1'); }

	$factureReglemetnManager = new FactureReglementsManager($cnx);
	echo $factureReglemetnManager->supprimeReglement($id_reglement) ? '1' : '0';

    exit;

} // FIN mode

// Modale règlement d'une facture
function modeModaleReglementFacture() {

	global $facturesManager, $utilisateur, $cnx;

	$id_facture = isset($_REQUEST['id_facture']) ? intval($_REQUEST['id_facture']) : 0;
	if ($id_facture == 0) { exit("Echec de l'identification de la facture (ID)"); }

	$facture = $facturesManager->getFacture($id_facture, true);
	if (!$facture instanceof Facture) { exit("Echec de l'instanciation de l'objet Facture") ;}

	$factureReglementsManager = new FactureReglementsManager($cnx);

	$reglements = $factureReglementsManager->getListeFactureReglements($facture);

    // réglement des avoirs liés
    $reglements_avoirs = $factureReglementsManager->getListeAvoirsLiesReglements($facture);

	?>
    <form id="formNewReglement">
    <div class="row">
        <div class="col-5">
            <h3 class="gris-9 text-28"><span class="text-14">Facture</span> <?php echo $facture->getNum_facture();?> <span class="text-14">du <?php echo Outils::dateSqlToFr($facture->getDate()); ?> de <?php echo number_format($facture->getTotal_ttc(),2,'.', ' ');?>€</span> </h3>
            <?php
			if (empty($reglements) && empty($reglements_avoirs)) { ?>
                <div class="alert alert-secondary padding-50 text-center gris-9">Aucun règlement pour cette facture</div>
			<?php } else {

                if (!empty($reglements)) { ?>
                    <table class="table admin table-v-middle">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Mode</th>
                            <th class="text-right">Montant</th>
                            <th class="w-25px"></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php
						$totalRegle = 0;
						foreach ($reglements as $reglement) {
							$totalRegle+=$reglement->getMontant();
							?>
                            <tr>
                                <td><?php echo Outils::dateSqlToFr($reglement->getDate());?></td>
                                <td><?php echo $reglement->getNom_mode();?></td>
                                <td class="text-right"><?php echo number_format($reglement->getMontant(),2,'.', ' ');?> €</td>
                                <td class="text-right pr-0"><button type="button" class="btn btn-danger btn-sm btnSupprReglement" data-id="<?php echo $reglement->getId(); ?>"><i class="fa fa-times"></i></button></td>
                            </tr>
						<?php }
						?>
                        </tbody>
                    </table>
				<?php }

				if (!empty($reglements_avoirs)) { ?>
                    <table class="table admin table-v-middle mt-1">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Avoir</th>
                            <th class="text-right">Montant</th>
                            <th class="w-25px"></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php
						$totalRegle = 0;
						foreach ($reglements_avoirs as $reglement_avoir) {
							?>
                            <tr>
                                <td><?php echo Outils::dateSqlToFr($reglement_avoir->getDate());?></td>
                                <td><?php echo $reglement_avoir->getNom_mode();?></td>
                                <td class="text-right"><?php echo number_format($reglement_avoir->getMontant(),2,'.', ' ');?> €</td>
                                <td class="text-right pr-0"><button type="button" class="btn btn-danger btn-sm btnSupprReglement" data-id="<?php echo $reglement_avoir->getId(); ?>"><i class="fa fa-times"></i></button></td>
                            </tr>
						<?php }
						?>
                        </tbody>
                    </table>
				<?php }

			    $reste = 0;

                if (round($totalRegle,2) == round($facture->getTotal_ttc(),2)) {
                    $css = 'success';
                    $txt = 'Facture acquittée';
                    $ifa = 'check';
				} else if (round($totalRegle,2) > round($facture->getTotal_ttc(),2)) {
					$tp= round($totalRegle,2) - round($facture->getTotal_ttc(),2);
					$css = 'warning';
					$txt = 'Trop perçu de ' . number_format($tp, 2, '.', ' ') . ' € !';
					$ifa = 'exclamation-triangle';
				} else if ($totalRegle > 0 && round($totalRegle,2) < round($facture->getTotal_ttc(),2)) {
					$reste= round($facture->getTotal_ttc(),2) - round($totalRegle,2);
					$css = 'warning';
					$txt = 'Reste à solder ' . number_format($reste, 2, '.', ' ') . ' €';
					$ifa = 'exclamation-circle';
				} else {
					$reste = $facture->getTotal_ttc();
                    $css='warning';
                    $txt = 'Facture non reglée';
					$ifa = 'exclamation-triangle';
				}


                ?>
                    <div class="alert alert-<?php echo $css; ?>"><i class="fa fa-<?php echo $ifa; ?> mr-1"></i><?php echo $txt; ?></div>


			<?php } // FIN test règlements



			if (!isset($reste)) { $reste = $facture->getTotal_ttc(); }
			if (!isset($totalRegle)) { $totalRegle = 0; }
			$bloque = round($totalRegle,2) >= round($facture->getTotal_ttc(),2);

			// Avoirs
			if ( $facture->getTotal_ttc() < 0) {

                $reste*=-1;
				$bloque = round($totalRegle,2) <= round($facture->getTotal_ttc(),2);

			} // FIN avoirs



			?>
        </div>


            <div class="col-4  <?php echo $bloque ? 'opacity-05' : ''; ?>">
                <h3 class="gris-9 text-18">Nouveau règlement :</h3>
                <input type="hidden" name="mode" value="saveReglement"/>
                <input type="hidden" name="id_facture" value="<?php echo $id_facture; ?>"/>
                <div class="row">
                    <div class="col-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Date</span>
                            </div>
                            <input type="text" <?php echo $bloque ? 'disabled' : ''; ?> class="form-control text-20 <?php echo $bloque ? '' : 'datepicker pointeur'; ?> text-center" placeholder="Date" name="date" value="<?php echo date('d/m/Y'); ?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="far fa-calendar-alt gris-5 fa-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Montant</span>
                            </div>
                            <input type="text" <?php echo $bloque ? 'disabled' : ''; ?> class="form-control text-20 <?php echo $bloque ? '' : 'pointeur'; ?> text-center" placeholder="0.00" name="montant" value="<?php echo round($reste,2); ?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa fa-euro-sign gris-5"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Mode</span>
                            </div>
                            <select class="selectpicker form-control" <?php echo $bloque ? 'disabled' : ''; ?> title="Mode de règlement" name="id_mode">
								<?php
								$modesManager = new ModesReglementManager($cnx);
								foreach ($modesManager->getListeModesReglements() as $mode) { ?>
                                    <option value="<?php echo $mode->getId();?>"><?php echo $mode->getNom();?></option>
								<?php }

								?>
                            </select>
                        </div>
                    </div>

                </div>
            </div>
            <div class="col-3  <?php echo $bloque ? 'opacity-05' : ''; ?>">
                <h3 class="gris-9 text-18">Avoir déduit :</h3>
                <div class="row">
                <?php
				$avoirs = $facturesManager->getAvoirsPossiblesFacture($facture);
                if (empty($avoirs)) { ?>
                    <div class="col-12"><div class="alert alert-warning texte-fin text-12 padding-20-40 text-center">Aucun avoir disponible<br>ce mois pour ce client !</div></div>
				<?php } else { ?>
                    <div class="col-12 mb-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Avoir</span>
                            </div>
                            <select class="selectpicker form-control" <?php echo $bloque ? 'disabled' : ''; ?> title="" name="id_avoir">
								<?php
                                $montantAvoir = 0;
                                foreach ($avoirs as $a) {
									$montantAvoir = intval($a['selected']) == 1 ? floatval($a['total_ttc'])*-1 : $montantAvoir;
                                    ?>
                                    <option value="<?php echo $a['id_avoir']; ?>" <?php echo intval($a['selected']) == 1 ? 'selected' : ''; ?>><?php echo $a['num_avoir']; ?></option>
								<?php }

                                if ($montantAvoir == 0) {
									$montantAvoir = floatval($a['total_ttc'])*-1;
								}

								// Liste des avoirs du même mois pour le meme client avec selected sur l'avoir en lien avec la facture si il y en a 1.
								// Pour ne par faire une requette dans chaque passage de la boucle, on fait en amont une requete qui nous renvoie un tableau avec tous les avoirs, l'id_client et les id_factures associés
								// [id_client] => { array des avoirs : [id_facture] => id_avoir }
								?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Le</span>
                            </div>
                            <input type="text" <?php echo $bloque ? 'disabled' : ''; ?> class="form-control text-20 <?php echo $bloque ? '' : 'datepicker pointeur'; ?> text-center" placeholder="Date" name="date_avoir_reglement" value="<?php echo date('d/m/Y'); ?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="far fa-calendar-alt gris-5 fa-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Montant</span>
                            </div>
                            <input type="text" <?php echo $bloque ? 'disabled' : ''; ?> class="form-control text-20 <?php echo $bloque ? '' : 'pointeur'; ?> text-center" placeholder="0.00" name="montant_avoir" value="<?php echo $montantAvoir > 0 ? number_format($montantAvoir,2, '.', '') : ''; ?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fa fa-euro-sign gris-5"></i></span>
                            </div>
                        </div>
                    </div>
				<?php }

                ?>



                </div>
            </div>




    </div>


    </form>
    <?php
	exit;

} // FIN mode


function modeSaveReglement() {

    global $facturesManager, $cnx;

    $id_facture             = isset($_REQUEST['id_facture'])            ? intval($_REQUEST['id_facture'])                               : 0;
    $date                   = isset($_REQUEST['date'])                  ? Outils::dateFrToSql(trim($_REQUEST['date']))                  : '';
    $date_avoir_reglement   = isset($_REQUEST['date_avoir_reglement'])  ? Outils::dateFrToSql(trim($_REQUEST['date_avoir_reglement']))  : '';
	$id_mode                = isset($_REQUEST['id_mode'])               ? intval($_REQUEST['id_mode'])                                  : 0;
	$id_avoir               = isset($_REQUEST['id_avoir'])              ? intval($_REQUEST['id_avoir'])                                 : 0;
	$montant                = isset($_REQUEST['montant'])               ? floatval($_REQUEST['montant'])                                : 0;
	$montant_avoir          = isset($_REQUEST['montant_avoir'])         ? floatval($_REQUEST['montant_avoir'])                          : 0;

	$logManager                 = new LogManager($cnx);
	$factureReglemetnManager    = new FactureReglementsManager($cnx);
	$modesReglementsManager     = new ModesReglementManager($cnx);

    // Si reglement facture
	if ($id_facture > 0 && $id_mode > 0 && $montant > 0 && ($date != '' && Outils::verifDateSql($date))) {

		$facture = $facturesManager->getFacture($id_facture, false);

		if (!$facture instanceof Facture) { exit('ERREUR INSTANCIATION FACTURE #'.$id_facture); }
		if ($facture->getMontant_ht() < 0) { exit('ERREUR FACTURE #'.$id_facture.' NEGATIVE'); }

		$reglement = new FactureReglement([]);
		$reglement->setDate($date);
		$reglement->setId_facture($id_facture);
		$reglement->setId_mode($id_mode);
		$reglement->setMontant($montant);

		if (!$factureReglemetnManager->saveFactureReglement($reglement)) { exit('ERREUR ENREGISTREMENT REGLEMENT SUR FACTURE #'.$id_facture); }

		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("Ajout d'un règlement de ".(float)$montant." EUR sur la facture #".$id_facture);
		$logManager->saveLog($log);

    } // FIN règlement facture

    // Si règlement avoir
    if ($id_avoir > 0 && $montant_avoir != 0) {

        if ($montant_avoir > 0) { $montant_avoir*= -1; }

        if ($date_avoir_reglement == '' || !Outils::verifDateSql($date_avoir_reglement)) { $date_avoir_reglement = date('Y-m-d'); }

		$avoir          = $facturesManager->getFacture($id_avoir, false);
		$id_mode_avoir  = $modesReglementsManager->getIdModeDefaut();

		if (!$avoir instanceof Facture) { exit('ERREUR INSTANCIATION AVOIR #'.$id_avoir); }
		if ($avoir->getMontant_ht() > 0) { exit('ERREUR AVOIR #'.$id_avoir.' POSITIF'); }

		$reste = $factureReglemetnManager->getResteApayerFacture($avoir);

		if ($montant_avoir*-1 > $reste*-1) { $montant_avoir = $reste; }

		$reglement = new FactureReglement([]);
		$reglement->setDate($date_avoir_reglement);
		$reglement->setId_facture($id_avoir);
		$reglement->setId_mode($id_mode_avoir);
		$reglement->setMontant($montant_avoir);

		if (!$factureReglemetnManager->saveFactureReglement($reglement)) { exit('ERREUR ENREGISTREMENT REGLEMENT SUR AVOIR #'.$id_avoir); }

		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("Ajout d'un règlement de ".(float)$montant_avoir." EUR sur l'avoir #".$id_avoir);
		$logManager->saveLog($log);

    } // FIN règlement avoir

	exit('1');

} // FIN mode


// Genere le PDF mensuel interbev
function modeGenerePdfInterbevMensuel() {

    global $facturesManager, $cnx;

    $mois = isset($_REQUEST['mois']) ? $_REQUEST['mois'] : '';
    $annee = isset($_REQUEST['annee']) ? $_REQUEST['annee'] : '';

    if ($annee == '') { $annee = date('Y'); }
    if ($mois == '') { $mois = date('m'); }

	// HEAD
	$tiersManager = new TiersManager($cnx);

	$content_header = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center text-20 pt-10">
                            INTERBEV '.strtoupper(Outils::getMoisListe()[$mois]) . ' ' . $annee .'
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-12 gris-7">Intranet PROFIL EXPORT</p>
                            <p class="text-10 gris-7">Montants en euros</p>
                        </td>
                    </tr>                
                </table>
               </div>';


	/*

		Date | N° Facture |Client | Poids | Taux | Montant Intebev
		=====================================
	    SOUS TOTAL TAUX 0.012
	    =====================================
	    SOUS TOTAL TAUX 0.018
	    =====================================
		TOTAL INTERBEV AOUT 2020 : XXXX € / Poids
	 */
	$contenu ='<table class="table admin w100">
                <thead>
                <tr>
                    <th class="w10 text-10">Date</th>
                    <th class="w15 text-10">Facture</th>
                    <th class="w30 text-10">Client</th>
                    <th class="w20 text-10 text-right">Poids (kg)</th>
                    <th class="w10 text-10 text-right">Taux (%)</th>
                    <th class="w15 text-10 text-right">Interbev</th>
                </tr>
                </thead><tbody>';


    $configManager = new ConfigManager($cnx);
	$conf_interbev_gros = $configManager->getConfig('interbev_gros');
	$conf_interbev_autres = $configManager->getConfig('interbev_autres');
    if (!$conf_interbev_gros instanceof Config) { exit('ERREUR RECUPERATION TAUX GROS');}
    if (!$conf_interbev_autres instanceof Config) { exit('ERREUR RECUPERATION TAUX AUTRES');}


	$liste_gros = $facturesManager->getInterbevMoisTaux($mois, $annee, $conf_interbev_gros->getValeur());
	$liste_autres = $facturesManager->getInterbevMoisTaux($mois, $annee, $conf_interbev_autres->getValeur());

	$total_gros_poids = 0.0;
	$total_autres_poids = 0.0;
	$total_gros_euro = 0;
	$total_autres_euro = 0;
	foreach ($liste_gros as $donnes) {

        if (!isset($donnes['montant_interbev'])) { continue; }

		$total_gros_poids+= round($donnes['poids'],3);
		$total_gros_euro+= round($donnes['montant_interbev'],2);

		$contenu.='<tr>
                    <td class="w10 text-10">'.Outils::dateSqlToFr($donnes['date']).'</td>
                    <td class="w15 text-10">'.$donnes['num_facture'].'</td>
                    <td class="w30 text-10">'.$donnes['nom'].'</td>
                    <td class="w20 text-10 text-right">'.number_format(round($donnes['poids'],3),3,'.', ' ').'</td>
                    <td class="w10 text-10 text-right">'.$conf_interbev_gros->getValeur().'</td>
                    <td class="w15 text-10 text-right">'.number_format(round($donnes['montant_interbev'],2),2,'.', ' ').'</td>
                </tr>';
    }

	if (!empty($liste_gros)) {
		$contenu.='<tr><th class="w55 text-10" colspan="3">SOUS-TOTAL produits de gros</th>
                    <th class="w20 text-10 text-right">'.number_format($total_gros_poids,3,'.', ' ').'</th>
                    <th class="w10 text-10 text-right">'.$conf_interbev_gros->getValeur().'</th>
                    <th class="w15 text-10 text-right">'.number_format($total_gros_euro,2,'.', ' ').'</th></tr>';
	}

	foreach ($liste_autres as $donnes) {

		$total_autres_poids+= round($donnes['poids'],3);
		$total_autres_euro+= round($donnes['montant_interbev'],2);

		$contenu.='<tr>
                    <td class="w10 text-10 ">'.Outils::dateSqlToFr($donnes['date']).'</td>
                    <td class="w15 text-10">'.$donnes['num_facture'].'</td>
                    <td class="w30 text-10">'.$donnes['nom'].'</td>
                    <td class="w20 text-10 text-right">'.number_format(round($donnes['poids'],3),3,'.', ' ').'</td>
                    <td class="w10 text-10 text-right">'.$conf_interbev_autres->getValeur().'</td>
                    <td class="w15 text-10 text-right">'.number_format(round($donnes['montant_interbev'],2),2,'.', ' ').'</td>
                </tr>';
	}

	if (!empty($liste_autres)) {
		$contenu.='<tr><th class="w55 text-10" colspan="3">SOUS-TOTAL autres produits</th>
                    <th class="w20 text-10 text-right">'.number_format($total_autres_poids,3,'.', ' ').'</th>
                    <th class="w10 text-10 text-right">'.$conf_interbev_autres->getValeur().'</th>
                    <th class="w15 text-10 text-right">'.number_format($total_autres_euro,2,'.', ' ').'</th></tr>';
	}

	if (empty($liste_gros) && empty($liste_autres)) {
		$contenu.='<tr><td colspan="6" class="w100 text-center pt-50 pb-50">Aucune facture soumise à la taxe interbev dans ce mois</td></tr>';
    }

	$nbtxt = count($liste_autres) + count($liste_gros) . ' facture';
	$nbtxt.= count($liste_autres) + count($liste_gros) > 1 ? 's' : '';

	$contenu.='</tbody><tfoot><tr>
                    <th class="w55 text-10" colspan="3">TOTAL '.$nbtxt.'</th>
                    <th class="w20 text-10 text-right">'.number_format($total_autres_poids + $total_gros_poids,3,'.', ' ').'</th>
                    <th class="w10 text-10 text-right"></th>
                    <th class="w15 text-10 text-right">'.number_format($total_autres_euro + $total_gros_euro,2,'.', ' ').'</th>
                </tr></tfoot></table>';


	$configManager = new ConfigManager($cnx);
	$pdf_top_interbev = $configManager->getConfig('pdf_top_interbev');
	$margeEnTetePdt = $pdf_top_interbev instanceof Config ?  (int)$pdf_top_interbev->getValeur() : 0;

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');
	ob_start();
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $contenu;
	$contentPdf.= '</page>'. ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/ipreinterbev-*.pdf') as $fichier) {
		unlink($fichier);
	}

	try {
		$nom_fichier = 'ipreinterbev-'.$mois.$annee.'.pdf';
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15');
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

// Genere le PDF mensuel interbev (OLD - NPU)
function modeGenerePdfInterbevMensuelOld() {

	global $facturesManager, $cnx;

	$mois = isset($_REQUEST['mois']) ? $_REQUEST['mois'] : '';
	$annee = isset($_REQUEST['annee']) ? $_REQUEST['annee'] : '';

	if ($annee == '') { $annee = date('Y'); }
	if ($mois == '') { $mois = date('m'); }

	// HEAD

	$content_header = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center text-20 pt-10">
                            INTERBEV '.strtoupper(Outils::getMoisListe()[$mois]) . ' ' . $annee .'
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-12 gris-7">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>';


	/*

		Date | N° Facture |Client | Montant Interbev
		=====================================
		TOTAL INTERBEV AOUT 2020 : XXXX €
	 */
	$contenu ='<table class="table admin w100">
                <thead>
                <tr>
                    <th class="w15">Date</th>
                    <th class="w20">Facture</th>
                    <th class="w40">Client</th>
                    <th class="w25 text-right">Interbev (EUR)</th>
                </tr>
                </thead><tbody>';


	$tiersManager = new TiersManager($cnx);

	$liste = $facturesManager->getInterbevMois($mois, $annee);
	$total = 0;
	foreach ($liste as $facture) {

		$client = $tiersManager->getTiers($facture->getId_tiers_facturation());
		$total+= round(floatval($facture->getMontant_interbev()),2);
		$contenu.='<tr>
                    <td class="w15">'.Outils::dateSqlToFr($facture->getDate()).'</td>
                    <td class="w20">'.$facture->getNum_facture().'</td>
                    <td class="w40">'.$client->getNom().'</td>
                    <td class="w25 text-right">'.number_format(round($facture->getMontant_interbev(),2),2,'.', ' ').'</td>
                </tr>';
	}
	if (empty($liste)) {
		$contenu.='<tr><td colspan="4" class="w100 text-center pt-50 pb-50">Aucune facture soumise à la taxe interbev dans ce mois</td></tr>';
	}

	$nbtxt = count($liste) . ' facture';
	$nbtxt.= count($liste) > 1 ? 's' : '';

	$contenu.='</tbody><tfoot><tr>
                    <th class="w75" colspan="3">TOTAL '.$nbtxt.'</th>
                    <th class="w25 text-right">'.number_format($total,2,'.', ' ').'</th>
                </tr></tfoot></table>';


	$configManager = new ConfigManager($cnx);
	$pdf_top_interbev = $configManager->getConfig('pdf_top_interbev');
	$margeEnTetePdt = $pdf_top_interbev instanceof Config ?  (int)$pdf_top_interbev->getValeur() : 0;

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');
	ob_start();
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $contenu;
	$contentPdf.= '</page>'. ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/ipreinterbev-*.pdf') as $fichier) {
		unlink($fichier);
	}

	try {
		$nom_fichier = 'ipreinterbev-'.$mois.$annee.'.pdf';
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15');
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

// Modale créer un avoir
function modeModaleAvoir() {

    global $cnx, $facturesManager;

    $id_client = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
    $ids_facture = isset($_REQUEST['ids_facture']) && is_array($_REQUEST['ids_facture']) ? $_REQUEST['ids_facture'] : [];
    $ids_facture_ligne = isset($_REQUEST['ids_facture_ligne']) && is_array($_REQUEST['ids_facture_ligne']) ? $_REQUEST['ids_facture_ligne'] : [];

    if ($id_client == 0) { $ids_facture = []; }
    if (empty($ids_facture)) { $ids_facture_ligne = []; }

    $tiersMaanger = new TiersManager($cnx);
    $listeClients = $tiersMaanger->getListeClients();
	$skip1 = false;
    ?>
    <input type="hidden" name="mode" value="modaleAvoir"/>
    <div class="row">
        <div class="col-12">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Client</span>
                </div>
                <select class="selectpicker show-tick form-control select-id-client" name="id_client" data-live-search="true" data-live-search-placeholder="Rechercher" title="Sélectionnez" data-size="12">
                    <?php
                    foreach ($listeClients as $clt) { ?>
                        <option value="<?php echo $clt->getId(); ?>" <?php echo $id_client == $clt->getId() ? 'selected' : '';?> ><?php echo $clt->getNom(); ?></option>
                    <?php }
                    ?>
                </select>
            </div>
        </div>

        <?php if ($id_client == 0) { ?>
            <div class="col-12"><div class="mt-1 alert alert-info texte-fin text-12"><i class="fa fa-info-circle mr-1"></i>
                Sélectionnez le client concerné.
            </div></div>
		<?php } else {

            // On ne prends que les factures ayant moins d'un an


			$facturesClient = $facturesManager->getListeFactures([
				'id_client'  => $id_client,
			    'nb_result_page' => 10,
				'bls'        => false,
				'lignes'     => false,
				'factavoirs' => 'f']);

			if (!$facturesClient || empty($facturesClient)) { ?>

                <div class="col-12"><div class="mt-1 alert alert-warning texte-fin text-12"><i class="fa fa-exclamation-circle mr-1"></i>
                    Aucune facture disponible pour ce client !
                </div></div>

			<?php $skip1 = true; } else { ?>

                <div class="col-12 mt-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Factures</span>
                        </div>
                        <select class="selectpicker show-tick form-control select-ids-facture" multiple data-size="12" name="ids_facture[]" data-selected-text-format="count > 1" >
							<?php
                            foreach ($facturesClient as $fact) { ?>
                                <option value="<?php echo $fact->getId(); ?>" <?php
                                echo in_array($fact->getId(),$ids_facture) ? 'selected' : '';
                                ?>>Facture <?php echo $fact->getNum_facture() . ' du ' . Outils::dateSqlToFr($fact->getDate()) . ' pour ' . number_format($fact->getMontant_ht(), 2, '.', ' '); ?></option>
							<?php }
							?>
                        </select>
                    </div>
                </div>

			<?php } // FIN test favtures trouvées pour ce client ?>



		<?php } // FIN test client sélectioné

        // Si on a au moins déjà sélectionné une facture
        if (empty($ids_facture) && $id_client > 0 && !$skip1) { ?>

            <div class="col-12"><div class="mt-1 alert alert-info texte-fin text-12"><i class="fa fa-info-circle mr-1"></i>
                Sélectionnez la ou les factures sur lesquelles portent l'avoir.
            </div></div>

		<?php } else if ($id_client > 0 && !empty($ids_facture)) {

            $lignes = $facturesManager->getListeFactureLignes(['ids_facture' => $ids_facture]);

			if ((!$facturesClient || empty($facturesClient)) &&  !$skip1) { ?>

                <div class="col-12"><div class="mt-1 alert alert-danger texte-fin text-12"><i class="fa fa-exclamation-circle mr-1"></i>
                    Aucune ligne disponible pour les factures sélectionnées !
                </div></div>

			<?php } else if (!$skip1) { ?>

                <div class="col-12 mt-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Ligne</span>
                        </div>
                        <?php
                        // Retrait de l'attribut MULTIPLE pour ne pas pouvoir sélectionner plusieurs lignes,
                        // car si un montant ou un poids est rentrée manuellement, on ne sais plus quelle correspondance établir !!!
                        ?>
                        <select class="selectpicker show-tick form-control select-ids-facture-ligne" data-live-search="true" data-live-search-placeholder="Rechercher" data-selected-text-format="count > 1"  title="Sélectionnez" data-size="12" name="ids_facture_ligne[]">
							<?php
							foreach ($lignes as $ligne) {

							    if (!$ligne->getProduit() instanceof Produit) { $ligne->setProduit(new Produit([])); } // Gestion des erreurs
							    ?>
                                <option value="<?php echo $ligne->getId(); ?>" data-subtext="<?php echo $ligne->getNum_facture(); ?>" <?php
								echo in_array($ligne->getId(),$ids_facture_ligne) ? 'selected' : '';
								?>><?php echo $ligne->getProduit()->getNom() . ' pour ' . number_format($ligne->getTotal(), 2, '.', ' '); ?> €</option>
							<?php }
							?>
                        </select>
                    </div>
                </div>

                <?php if (empty($ids_facture_ligne)) { ?>
                <div class="col-12"><div class="mt-1 alert alert-info texte-fin text-12"><i class="fa fa-info-circle mr-1"></i>
                    Sélectionnez la ou les lignes de produit sur lesquelles portent l'avoir.
                </div></div>
                <?php }
             } // FIN test lignes de factures trouvées ?>


		<?php } // FIN test factur(e)s sélectionnée(s)


        $montant_avoir = 0;
		$poids_avoir = 0;
		$nb_colis_avoir = 0;
		$nb_qte_avoir = 0;
        // Si factures sélectionnées mais pas de ligne, montant de l'avoir = somme des totaux factures
        if (!empty($ids_facture) && empty($ids_facture_ligne)) {

            $montant_avoir = $facturesManager->getTotalFactures($ids_facture);
            $poids_avoir = $facturesManager->getTotalPoidsFactures($ids_facture);
            $nb_colis_avoir = $facturesManager->getTotalColisFactures($ids_facture);
            $nb_qte_avoir = $facturesManager->getTotalQteFactures($ids_facture);

        // Si factures sélectionnées + lignes, montant de l'avoir = somme des lignes
		} else if  (!empty($ids_facture) && !empty($ids_facture_ligne)) {
			$montant_avoir = $facturesManager->getTotalLignes($ids_facture_ligne);
			$poids_avoir =$facturesManager->getTotalPoidsLignes($ids_facture_ligne);
			$nb_colis_avoir =$facturesManager->getTotalColisLignes($ids_facture_ligne);
			$nb_qte_avoir =$facturesManager->getTotalQteLignes($ids_facture_ligne);

		} // Si pas de facture sélectionnée, vide

        ?>


    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="alert alert-secondary">
                <div class="row">
                    <div class="col pr-0">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Poids</span>
                            </div>
                            <input type="text" name="poids" value="<?php echo $poids_avoir > 0 ? $poids_avoir : ''; ?>" placeholder="0.000" class="form-control text-right" />
                            <div class="input-group-append">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-2 pr-0">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Colis</span>
                            </div>
                            <input type="text" name="nb_colis" value="<?php echo $nb_colis_avoir > 0 ? $nb_colis_avoir : ''; ?>" placeholder="0" class="form-control text-center" />
                        </div>
                    </div>
                    <div class="col-2 pr-0">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Qté</span>
                            </div>
                            <input type="text" name="qte" value="<?php echo $nb_qte_avoir > 0 ? $nb_qte_avoir : ''; ?>" placeholder="0" class="form-control text-center" />
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Montant</span>
                            </div>
                            <input type="text" name="montant" value="<?php echo $montant_avoir > 0 ? number_format($montant_avoir,'2', '.', '') : ''; ?>" placeholder="0.00" class="form-control text-right" />
                            <div class="input-group-append">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-1 hid mb-0" id="messageErreurConteneur">
        <div class="col-12">
            <div class="alert alert-danger padding-20 text-center"><i class="fa fa-times-circle fa-2x mr-b"></i><div id="messageErreur"></div></div>
        </div>
    </div>
    <?php



    exit;

} // FIN mode
    
// Création d'un avoir depuis modale
function modeCreerAvoir() {

	global $cnx, $facturesManager;

	$id_client          = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
	$ids_facture        = isset($_REQUEST['ids_facture']) && is_array($_REQUEST['ids_facture']) ? $_REQUEST['ids_facture'] : [];
	$ids_facture_ligne  = isset($_REQUEST['ids_facture_ligne']) && is_array($_REQUEST['ids_facture_ligne']) ? $_REQUEST['ids_facture_ligne'] : [];
	$montant            = isset($_REQUEST['montant']) ? floatval($_REQUEST['montant']) : 0;
	$poids              = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 1;
	$nb_colis           = isset($_REQUEST['nb_colis']) ? intval($_REQUEST['nb_colis']) : 1;
	$qte                = isset($_REQUEST['qte']) ? intval($_REQUEST['qte']) : 1;
	if ($montant < 0) { $montant*= -1;} // Si le montant a été saisi négativement

	// Si pas de client
	if ($id_client == 0) { exit('-1');}

	// Si ni montant ni facture
	if (empty($ids_facture) && empty($ids_facture_ligne) && $montant == 0) { exit('-2'); }

	// Si on a sélectionné plusieurs lignes et que le total ne correspond pas, on ne peut pas savoir sur quelle ligne on veut bidouiller le montant !
    if (count($ids_facture_ligne) > 1 && $montant > 0) {
        if ($facturesManager->getTotalLignes($ids_facture_ligne) != $montant) {
            exit('-3');
        }
    }

	$tiersManager = new TiersManager($cnx);
	$client = $tiersManager->getTiers($id_client);
	if (!$client instanceof Tiers) { exit('-4'); }

	// Création de l'avoir
	$avoir = new Facture([]);
	$avoir->setDate(date('Y-m-d'));
	$avoir->setDate_add(date('Y-m-d H:i:s'));

    // Si on a aucune facture sélectionné, on se base sur le client
    if (empty($ids_facture)) {
		if (!isset($client->getAdresses()[0]) || !$client->getAdresses()[0] instanceof Adresse) { exit('-5'); }
		$id_adresse_facturation = $client->getAdresses()[0]->getId();
		$id_adresse_livraison = $id_adresse_facturation;
		$id_transporteur = $client->getId_transporteur();
		$num_cmd = 'Avoir libre';
		$montant = $montant > 0 ? $montant*-1 : $montant; // On s'assure que le montant est négatif
		$avoir->setMontant_ht($montant);


	// Une facture
    } else if (count($ids_facture) == 1){
        $facture = $facturesManager->getFacture($ids_facture[0]);
		if (!$facture instanceof Facture) { exit('-6'); }
		$id_adresse_facturation = $facture->getId_adresse_facturation();
		$id_transporteur = $facture->getId_tiers_transporteur();
		$num_cmd = $facture->getNum_cmd();

		// SI pas de lignes précisée (toute la facture est en avoir)
        if (empty($ids_facture_ligne)) {
			$montant_avoir = $facture->getMontant_ht() > 0 ? $facture->getMontant_ht()*-1 : $facture->getMontant_ht(); // On s'assure que le montant est négatif
            if ($montant_avoir == 0) { $montant_avoir = $montant; }
			$montant_avoir = $montant_avoir > 0 ? $montant_avoir*-1 : $montant_avoir; // On s'assure que le montant est négatif
			$avoir->setMontant_ht($montant_avoir);
        }


	// Plusieurs factures
    } else {

		$nums_cmds = [];
		$montant_pf = 0;

        // On boucle sur les factures et on récupère les données de la dernière pour les tiers
        foreach ($ids_facture as $id_facture) {

            $facture = $facturesManager->getFacture($id_facture);
            if (!$facture instanceof Facture) { continue; }

			$id_adresse_facturation = $facture->getId_adresse_facturation();
			$id_transporteur = $facture->getId_tiers_transporteur();
			$nums_cmds[] = $facture->getNum_cmd();
			$montant_pf+=$facture->getMontant_ht();

        } // FIn boucle sur les factures

		// SI pas de lignes précisée (toute la facture est en avoir)
		if (empty($ids_facture_ligne)) {
			$montant_pf = $montant_pf > 0 ? $montant_pf*-1 : $montant_pf; // On s'assure que le montant est négatif
			$avoir->setMontant_ht($montant_pf);
		}

        if (count($nums_cmds) == 1) { $num_cmd = $nums_cmds[0]; }
        else if (empty($nums_cmds)) { $num_cmd = 'Avoir sur factures'; }
        else { $num_cmd = implode('/', $nums_cmds); }
        if ($num_cmd == '/') { $num_cmd = 'Avoir sur factures'; }

    } // FIN récupération des données des factures sources

	$avoir->setNum_cmd($num_cmd);
	$avoir->setId_tiers_facturation($id_client);
	$avoir->setId_adresse_facturation($id_adresse_facturation);
	$avoir->setId_tiers_transporteur($id_transporteur);

	$num_fact = $facturesManager->getNextNumeroFacture(true);
	$avoir->setNum_facture($num_fact);

	$id_avoir = $facturesManager->saveFacture($avoir);
	if (intval($id_avoir) == 0) { exit('-7');}

    // Si pas de ligne, et pas de facture
    if (empty($ids_facture_ligne) && empty($ids_facture)) {
        $ligne = new FactureLigne([]);
		$ligne->setId_facture($id_avoir);
		$ligne->setId_facture_avoir(0);
		$ligne->setId_produit(0);
		$ligne->setId_pays(0);
		$ligne->setNumlot('');
		$ligne->setPoids($poids);
		$ligne->setNb_colis($nb_colis);
		$ligne->setQte($qte);
		$pu_ht = $avoir->getMontant_ht() > 0 ? $avoir->getMontant_ht()*-1 : $avoir->getMontant_ht(); // On s'assure que le montant est négatif
		$ligne->setPu_ht($pu_ht);
		$ligne->setTva(0);
		$ligne->setTarif_interbev(0);
		$ligne->setDate_add(date('Y-m-d H:i:s'));
		echo $facturesManager->saveFactureLigne($ligne) ? '1' : '0';
		generePdfFacture($avoir);
		exit;

    // Si on a pas de lignes mais qu'on a une facture, on crée la ligne pour la totalité de la facture
    } else if (empty($ids_facture_ligne) && count($ids_facture) == 1) {

		$facture = $facturesManager->getFacture($ids_facture[0]);
		if (!$facture instanceof Facture) {
			exit('-6');
		}

		$ligne = new FactureLigne([]);
		$ligne->setId_facture($id_avoir);
		$ligne->setId_facture_avoir($facture->getId());
		$ligne->setId_produit(0);
		$ligne->setId_pays(0);
		$ligne->setNumlot('');
		$ligne->setPoids($poids);
		$ligne->setNb_colis($nb_colis);
		$ligne->setQte($qte);
		$pu_ht = $avoir->getMontant_ht() > 0 ? $avoir->getMontant_ht() * -1 : $avoir->getMontant_ht(); // On s'assure que le montant est négatif
		$ligne->setPu_ht($pu_ht);
		$ligne->setTva(0);
		$ligne->setTarif_interbev(0);
		$ligne->setDate_add(date('Y-m-d H:i:s'));
		echo $facturesManager->saveFactureLigne($ligne) ? '1' : '0';
		generePdfFacture($avoir);
		exit;

	// Si on a pas de ligne mais plusieurs factures
	} else if (empty($ids_facture_ligne) && count($ids_facture) > 1) {

        // On crée une ligne par facture en divisant le montant
        foreach ($ids_facture as $id_facture) {

			$pu_ht = $avoir->getMontant_ht() > 0 ? $avoir->getMontant_ht() * -1 : $avoir->getMontant_ht(); // On s'assure que le montant est négatif
            $pu_ht_final = round($pu_ht / count($ids_facture),2);

			$facture = $facturesManager->getFacture($id_facture);
			if (!$facture instanceof Facture) {
				exit('-6');
			}

			$ligne = new FactureLigne([]);
			$ligne->setId_facture($id_avoir);
			$ligne->setId_facture_avoir($facture->getId());
			$ligne->setId_produit(0);
			$ligne->setId_pays(0);
			$ligne->setNumlot('');
			$ligne->setPoids($poids);
			$ligne->setNb_colis($nb_colis);
			$ligne->setQte($qte);

			$ligne->setPu_ht($pu_ht_final);
			$ligne->setTva(0);
			$ligne->setTarif_interbev(0);
			$ligne->setDate_add(date('Y-m-d H:i:s'));
			if (!$facturesManager->saveFactureLigne($ligne)) { exit('0'); }

        } // FIN boucle factures

		generePdfFacture($avoir);
        exit('1');


    // SI on a une ou plusieurs lignes (on a forcément une ou plusieurs factures)
    } else {

        $montant_ht = 0;
        if (!isset($produitManager)) {
			$produitManager = new ProduitManager($cnx);
		}

        // On reprend les lignes passées
        foreach ($ids_facture_ligne as $id_facture_ligne) {

            $ligne_originale = $facturesManager->getFactureLigneFromVue($id_facture_ligne);
            if (!$ligne_originale instanceof FactureLigne) { continue; }

			$tva = $produitManager->getTvaProduit($ligne_originale->getId_produit());
            //if ($tva > 0) { $tva*=-1; }

			$ligne = new FactureLigne([]);
			$ligne->setId_facture($id_avoir);
			$ligne->setId_facture_avoir($ligne_originale->getId_facture());
			$ligne->setId_produit($ligne_originale->getId_produit());
			$ligne->setId_pays($ligne_originale->getId_pays());
			$ligne->setNumlot($ligne_originale->getNumlot());
			$ligne->setPoids($poids);
			$ligne->setNb_colis($nb_colis);
			$ligne->setQte($qte);
			//$pu_ht = $montant > 0 ? $montant *-1 : $montant;
			//$pu_ht = $ligne_originale->getPu_ht() > 0 ? $ligne_originale->getPu_ht()*-1 : $ligne_originale->getPu_ht(); // On s'assure que le montant est négatif
			$multiplicateur = $poids > 1 ? $poids : $qte;
            if ($multiplicateur < 1) { $multiplicateur = 1; }
            $pu_ht = $montant / $multiplicateur;
            if ($pu_ht > 0) { $pu_ht*=-1; }
			$ligne->setPu_ht($pu_ht);
			$ligne->setTva($tva);
			$ligne->setTarif_interbev(0);
			$ligne->setDate_add(date('Y-m-d H:i:s'));
			if (!$facturesManager->saveFactureLigne($ligne)) { exit('0'); }
			//$montant_ht+=($pu_ht * $multiplicateur);
            if ($montant > 0) { $montant*=-1; }
			$montant_ht+=$montant;
			//$montant_ht+=$ligne_originale->getTotal()*-1;

        } // FIN boucle sur les lignes d'origine

        // On met à jous le total de l'avoir
		$montant_ht = $montant_ht > 0 ? $montant_ht*-1 : $montant_ht; // On s'assure que le montant est négatif
		$montant = $montant > 0 ? $montant*-1 : $montant; // On s'assure que le montant est négatif

        // SI on a forcé un montant un peu différent (uniquement si une seule ligne)
        if (count($ids_facture_ligne) == 1 && $montant != $montant_ht) {
	/*		$montant_ht = $montant;
			$montant_ht = $montant_ht > 0 ? $montant_ht*-1 : $montant_ht; // On s'assure que le montant est négatif
			$ligne->setPu_ht($montant_ht);
			$facturesManager->saveFactureLigne($ligne);*/
        }

        $avoir->setMontant_ht($montant_ht);
        if (!$facturesManager->saveFacture($avoir)) { exit('-9'); }

    } // FIN test lignes

    // On met à jour le montant de la facture

	generePdfFacture($avoir);
	exit('1');
} // FIN mode

function modeUpdFacture() {

    global $cnx, $facturesManager;

    $logManager = new LogManager($cnx);

	$id_facture = isset($_REQUEST['id_facture']) ? intval($_REQUEST['id_facture']) : 0;
	if ($id_facture == 0) { exit('ERR_ID_FACT_0'); }
	$facture = $facturesManager->getFacture($id_facture);
	if (!$facture instanceof Facture) { exit('ERR_INSTOBJ_FACT');}

	$designations = isset($_REQUEST['designation']) && is_array($_REQUEST['designation']) ? $_REQUEST['designation'] : [];
	$pus_ht = isset($_REQUEST['pu_ht']) && is_array($_REQUEST['pu_ht']) ? $_REQUEST['pu_ht'] : [];
	$qtes = isset($_REQUEST['qte']) && is_array($_REQUEST['qte']) ? $_REQUEST['qte'] : [];
	$poids = isset($_REQUEST['poids']) && is_array($_REQUEST['poids']) ? $_REQUEST['poids'] : [];

	if (empty($designations)) { exit('ERR_DESIGNATIONS_EMPTY');}
	if (empty($pus_ht)) { exit('ERR_PUHT_EMPTY');}
	if (empty($qtes)) { exit('ERR_QTE_EMPTY');}
	if (empty($poids)) { exit('ERR_POIDS_EMPTY');}

	// Date et N°commande
    $numCmd = isset($_REQUEST['num_cmd']) ? trim($_REQUEST['num_cmd']) : '';
    $date = isset($_REQUEST['date']) ? Outils::dateFrToSql($_REQUEST['date']) : '';
	if (Outils::verifDateSql($date)) {
	    $facture->setDate($date);
    }
	$facture->setNum_cmd($numCmd);
	if (!$facturesManager->saveFacture($facture)) { exit('ERR_SAVE_FACT_#'.$id_facture);}

	// Lignes déjà en BDD
    foreach ($designations as $id_ligne => $designation) {
        $ligne = $facturesManager->getFactureLigne($id_ligne);
		if (!$ligne instanceof FactureLigne) { exit('ERR_INSTOBJ_LIGNE_#'.$id_ligne); }

		// Si on a supprimé la ligne
		if (floatval($qtes[$id_ligne]) == 0 || floatval($pus_ht[$id_ligne]) == 0) {
			$ligne->setSupprime(1);
			if (!$facturesManager->saveFactureLigne($ligne)) { exit('ERR_SAVE_SUPPR_LIGNE_#'.$id_ligne);}
			// Log SupprLigne
            $log = new Log([]);
			$log->setLog_type('warning');
			$log->setLog_texte('Suppression (flag) de la ligne de facture #id_ligne : '.$id_ligne);
			$logManager->saveLog($log);
			continue;
		}

		$ligne->setDesignation($designation);
        if (!isset($pus_ht[$id_ligne])) { exit('ERR_PUHT_LIGNE_#'.$id_ligne); }
        if (!isset($qtes[$id_ligne])) { exit('ERR_QTE_LIGNE_#'.$id_ligne); }
        if (!isset($poids[$id_ligne])) { exit('ERR_POIDS_LIGNE_#'.$id_ligne); }
        $ligne->setPu_ht(floatval($pus_ht[$id_ligne]));
		$ligne->setQte($qtes[$id_ligne]);
		$ligne->setPoids($poids[$id_ligne]);
/*        if ($ligne->isVendu_piece()) {
            $ligne->setQte($qtes[$id_ligne]);
        } else {
			$ligne->setPoids($qtes[$id_ligne]);
        }*/
        if (!$facturesManager->saveFactureLigne($ligne)) { exit('ERR_SAVE_LIGNE_#'.$id_ligne);}

    }

    // Nouvelles lignes

    $new_lines_designation = isset($_REQUEST['new_line_designation']) && is_array($_REQUEST['new_line_designation'])  ? $_REQUEST['new_line_designation'] : [];
    $new_line_pu_ht = isset($_REQUEST['new_line_pu_ht']) && is_array($_REQUEST['new_line_pu_ht'])  ? $_REQUEST['new_line_pu_ht'] : [];
    $new_line_qte = isset($_REQUEST['new_line_qte']) && is_array($_REQUEST['new_line_qte'])  ? $_REQUEST['new_line_qte'] : [];
    //$new_line_unite = isset($_REQUEST['new_line_unite']) && is_array($_REQUEST['new_line_unite'])  ? $_REQUEST['new_line_unite'] : [];

    // Si pas de nouvelles lignes, terminé, retour positif
    // Ne devrait pas arriver car on au moins la ligne de référence donc une entrée dans l'array, à zéro.
    if (empty($new_line_qte)) { exit('1'); }

    // On prends la quantité pour référence de bouclage
    foreach ($new_line_qte as $k => $nl_qte) {

        // On recupère le PU et la désignation correspondant à cet index
		$nl_puht = isset($new_line_pu_ht[$k]) ? floatval($new_line_pu_ht[$k]) : 0;
		$nl_design = isset($new_lines_designation[$k]) ? trim($new_lines_designation[$k]) : '';
		//$unite = isset($new_line_unite[$k]) ? trim($new_line_unite[$k]) : '';


		// Là si c'est effectivement vide, on passe...
		if (intval($nl_puht) == 0 || intval($nl_qte) == 0 || $nl_design == '') { continue; }

		// NTUI ! (surtout chez ce client)
		if ($nl_qte < 0 && $nl_puht > 0) { $nl_puht*=-1; }

		// Nouvelle ligne
		$nl = new FactureLigne([]);
		$nl->setSupprime(0);
		$nl->setPu_ht($nl_puht);
		$nl->setDesignation($nl_design);
		$nl->setDate_add(date('Y-m-d H:i:s'));
		$nl->setId_produit(0);
		$nl->setTarif_interbev(0);
		$nl->setTva(0);
		$nl->setNb_colis(1);
		$nl->setId_facture($id_facture);
		$nl->setId_facture_avoir(0);
		$nl->setId_produit(0);
		$nl->setId_pays(0);
		$nl->setNumlot('');
		$nl->setNb_colis(0);
		//if ($unite == 'poids') { $nl->setPoids(floatval($nl_qte)); $nl->setQte(1); }
        //else { $nl->setQte(floatval($nl_qte)); $nl->setPoids(0); }
		$nl->setQte(floatval($nl_qte)); $nl->setPoids(0);

		if (!$facturesManager->saveFactureLigne($nl)) { exit('ERR_SAVE_NEW_LIGNE_INDEX#'.$k);}
		// Log SupprLigne
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Ajout manuel d\'une  nouvelle ligne sur la facture #'.$id_facture);
		$logManager->saveLog($log);

	} // FIN boucle sur les nouvelles lignes

    // On recalcule le montant ht de la facture
    if (!$facturesManager->recalculeMontantHtFacture($facture)) { exit('ERR_RECALC_HT_FACT');}

    // Il faut a présent regénérer le PDF de la facture
    if (!generePdfFacture($facture)) { exit('ERR_REGNR_PDF');}

	$log = new Log([]);
	$log->setLog_type('warning');
	$log->setLog_texte('Modification manuelle de la facture #'.$id_facture);
	$logManager->saveLog($log);

	exit('1');

} // FIN mode


function modeRegenerePdfFacture() {

    global $facturesManager;

    $id_facture = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id_facture == 0) { exit('-1'); }

    $facture = $facturesManager->getFacture($id_facture);
    if (!$facture instanceof Facture) { exit('-2'); }

    if (!generePdfFacture($facture)) { exit('-3');}
    if (!$facturesManager->recalculeMontantHtFacture($facture)) { exit('-4'); }
    $interbev = $facturesManager->getInterbevFacture($facture);
	$facture->setMontant_interbev($interbev);
    if (!$facturesManager->saveFacture($facture)) { exit('-5');}
    echo '1';
    exit;

} // FIN mode


function modeChangePaLigne() {
	global $facturesManager, $logManager;

	$id_ligne = isset($_REQUEST['id_ligne']) ? intval($_REQUEST['id_ligne']) : 0;
	$pa = isset($_REQUEST['pa']) ? floatval($_REQUEST['pa']) : 0;
	if ($id_ligne == 0) { exit; }

	$ligne = $facturesManager->getFactureLigne($id_ligne);
	if (!$ligne instanceof FactureLigne) { exit; }

	$ligne->setPa_ht($pa);

	$res = $facturesManager->saveFactureLigne($ligne) ? 1 : 0;
	echo $res ? 1 : 0;

	// Il faut aussi changer le prix pour toutes les autres lignes ayant le meme id_pdt, id_lot et id_palette si le BL est regroupé
	$autresLignes = $facturesManager->getFactureLignesMemeProduitFromLigne($ligne);

	if (!empty($autresLignes)) {
		foreach ($autresLignes as $ligneFacture) {
			if ($ligneFacture instanceof FactureLigne) {
				$ligneFacture->setPa_ht($pa);
				$res2 = $facturesManager->saveFactureLigne($ligneFacture);
				$log = new Log([]);
				$logTxt = $res2
					? 'Changement du prix d\'achat sur ligne de facture #'.$ligneFacture->getId(). ' (auto par regroupement depuis modif ligne #'.$id_ligne.')'
					: 'Echec lors du changement du prix d\'achat auto regroupement sur la ligne de facture #'.$ligneFacture->getId(). ' depuis modif sur ligne #'.$id_ligne;
				$logType = $res ? 'info' : 'danger';
				$log->setLog_texte($logTxt);
				$log->setLog_type($logType);
				$logManager->saveLog($log);
			}
		}
	}

	$log = new Log([]);
	$logTxt = $res
		? 'Changement du prix d\'achat sur la ligne de facture #'.$id_ligne
		: 'Echec lors du changement du prix d\'achat sur la ligne de facture #'.$id_ligne;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;

}

function modeUpdFrsLigne() {

    global $facturesManager, $cnx;

    $logsManager = new LogManager($cnx);

    $id_frs = isset($_REQUEST['id_frs']) ? intval($_REQUEST['id_frs']) : 0;
    $id_ligne = isset($_REQUEST['id_ligne']) ? intval($_REQUEST['id_ligne']) : 0;

    if ($id_frs == 0) { exit('ERREUR fournisseur non identifié !');}
    if ($id_ligne == 0) { exit('ERREUR ligne de facture non identifiée !');}

    $ligne = $facturesManager->getFactureLigne($id_ligne);
    if (!$ligne instanceof FactureLigne) { exit('ERREUR instanciation ligne !');}

    $ligne->setId_frs($id_frs);
    if (!$facturesManager->saveFactureLigne($ligne)) { exit('ERREUR enregistrement ligne !'); }

    $log = new Log();
    $log->setLog_type('info');
    $log->setLog_texte("Attribution du fournisseur #".$id_frs." pour la ligne de facture " . $id_ligne);
    $logsManager->saveLog($log);
    exit('1');

}


function modeMarquerFactureEnvoyee() {

    global $facturesManager, $cnx;

    $id_facture = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id_facture == 0) { exit("ERREUR facture non identifée !"); }

    $facture = $facturesManager->getFacture($id_facture, false);
    if (!$facture instanceof Facture) { exit("ERREUR instanciation objet facture échoué sur ID #".$id_facture);}

    $facture->setDate_envoi(date('Y-m-d H:i:s'));
    if (!$facturesManager->saveFacture($facture)) {
        exit('ERREUR enregsitrement facture !');
    }

	$logManager = new LogManager($cnx);
    $log = new Log();
    $log->setLog_type('info');
    $log->setLog_texte('Facture #' . $id_facture . ' marquée manuellement comme envoyée.');
    $logManager->saveLog($log);
    echo '1';
    exit;

}

function modeChangeQteLigne(){
	global $facturesManager, $cnx;
	$id_facture = isset($_REQUEST['id_ligne_fct']) ? intval($_REQUEST['id_ligne_fct']) : 0;
	$qte = isset($_REQUEST['qte']) ? intval($_REQUEST['qte']) : 0;





}