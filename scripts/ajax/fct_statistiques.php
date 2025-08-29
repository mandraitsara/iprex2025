<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax PRP
------------------------------------------------------*/
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

$fraisFonctionnementManager = new FraisFonctionnementManager($cnx);
$statsManager 				= new StatistiquesManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------
MODE - Genère PDF
------------------------------------*/
function modeGenerePdf() {

	global $cnx;

    $onglet = isset($_REQUEST['onglet']) ? $_REQUEST['onglet'] : '';
    if ($onglet == '') { exit('ERR_ONGLET_NULL'); }

    $fonctionOnglet = 'fct'.ucfirst($onglet);
	if (!function_exists($fonctionOnglet)) { exit('ERR_FUNCTION_'.strtoupper($fonctionOnglet).'_NOT_EXIST'); }

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = '<style type="text/css"> * { margin:0; padding: 0; }  .header img.logo { width: 200px; } .text-right { text-align: right; } .text-center { text-align: center; } .table { border-collapse: collapse; } .table-donnees th { font-size: 11px; } .table-liste th { font-size: 9px; background-color: #d5d5d5; padding:3px; } .table-liste td { font-size: 9px; padding: 3px; border-bottom: 1px solid #ccc;} .table-liste td.no-bb { border-bottom: none; padding-bottom: 0px; } .table-liste tr.soustotal td { background-color: #d5d5d5; } .titre { background-color: teal; color: #fff; padding: 3px; text-align: center; font-weight: normal; font-size: 14px; } .w100 { width: 100%; } .w75 { width: 75%; } .w65 { width: 65%; } .w45 { width: 45%; } .w55 { width: 55%; } .w70 { width: 70%; } .w80 { width: 80%; } .w50 { width: 50%; } .w40 { width: 40%; } .w25 { width: 25%; } .w33 { width: 33%; } .w34 { width: 34%; } .w20 { width: 20%; } .w30 { width: 30%; } .w15 { width: 15%; } .w35 { width: 35%; }  .w30 { width: 30%; } .w5 { width: 5%; } .w10 { width: 10%; } .w15 { width: 15%; } .text-6 { font-size: 6px; } .text-7 { font-size: 7px; } .text-8 { font-size: 8px; } .text-9 { font-size: 9px; } .text-10 { font-size: 10px; } .text-11 { font-size: 11px; } .text-12 { font-size: 12px; } .text-14 { font-size: 14px; } .text-16 { font-size: 16px; } .text-18 { font-size: 18px; } .text-20 { font-size: 20px; } .gris-3 { color:#333; } .gris-5 { color:#555; } .gris-7 { color:#777; } .gris-9 { color:#999; } .gris-c { color:#ccc; } .gris-d { color:#d5d5d5; } .gris-e { color:#e5e5e5; } .mt-0 { margin-top: 0px; } .mt-2 { margin-top: 2px; } .mt-5 { margin-top: 5px; } .mt-10 { margin-top: 10px; } .mt-15 { margin-top: 15px; } .mt-20 { margin-top: 20px; } .mt-25 { margin-top: 25px; } .mt-50 { margin-top: 50px; } .mb-0 { margin-bottom: 0px; } .mb-2 { margin-bottom: 2px; } .mb-5 { margin-bottom: 5px; } .mb-10 { margin-bottom: 10px; } .mb-15 { margin-bottom: 15px; } .mb-20 { margin-bottom: 20px; } .mb-25 { margin-bottom: 25px; } .mb-50 { margin-bottom: 50px; } .mr-0 { margin-right: 0px; } .mr-2 { margin-right: 2px; } .mr-5 { margin-right: 5px; } .mr-10 { margin-right: 10px; } .mr-15 { margin-right: 15px; } .mr-20 { margin-right: 20px; } .mr-25 { margin-right: 25px; } .mr-50 { margin-right: 50px; } .ml-0 { margin-left: 0px; } .ml-2 { margin-left: 2px; } .ml-5 { margin-left: 5px; } .ml-10 { margin-left: 10px; } .ml-15 { margin-left: 15px; } .ml-20 { margin-left: 20px; } .ml-25 { margin-left: 25px; } .ml-50 { margin-left: 50px; } .pt-0 { padding-top: 0px; } .pt-2 { padding-top: 2px; } .pt-5 { padding-top: 5px; } .pt-10 { padding-top: 10px; } .pt-15 { padding-top: 15px; } .pt-20 { padding-top: 20px; } .pt-25 { padding-top: 25px; } .pt-50 { padding-top: 50px; } .pb-0 { padding-bottom: 0px; } .pb-2 { padding-bottom: 2px; } .pb-5 { padding-bottom: 5px; } .pb-10 { padding-bottom: 10px; } .pb-15 { padding-bottom: 15px; } .pb-20 { padding-bottom: 20px; } .pb-25 { padding-bottom: 25px; } .pb-50 { padding-bottom: 50px; } .pr-0 { padding-right: 0px; } .pr-2 { padding-right: 2px; } .pr-5 { padding-right: 5px; } .pr-10 { padding-right: 10px; } .pr-15 { padding-right: 15px; } .pr-20 { padding-right: 20px; } .pr-25 { padding-right: 25px; } .pr-50 { padding-right: 50px; } .pl-0 { padding-left: 0px; } .pl-2 { padding-left: 2px; } .pl-5 { padding-left: 5px; } .pl-10 { padding-left: 10px; } .pl-15 { padding-left: 15px; } .pl-20 { padding-left: 20px; } .pl-25 { padding-left: 25px; } .pl-50 { padding-left: 50px; } .text-danger { color: #d9534f; } .vtop { vertical-align: top; } .br-1 { border-right: 1px solid #999; } .table-prp td {word-break:break-all;vertical-align: top; border:1px solid #777; padding:2px; font-size:10px;} tr.t-header td { text-align: center; background-color: #ddd; } tr.sous-total td { background-color: #add0d0; } h2 { font-weight: normal; font-size: .8em; margin-bottom: 5px; } .filtres { margin-bottom: 2px;} .bd-2 { border-right:2px solid #555; } </style>';


	$content.= $fonctionOnglet();
	$content.= ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/'.strtolower($onglet).'*.pdf') as $fichier) {
		unlink($fichier);
	}

	// Pour la comparaison de période on est en paysage, mais pour le reste en portrait
	$orientation = strtolower($onglet) == 'cdp' ? 'L' : 'P';

	try {
		$nom_fichier = strtolower($onglet).date('is').'.pdf';
		$html2pdf = new HTML2PDF($orientation, 'A4', 'fr', false, 'ISO-8859-15');
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($content));;
		$savefilepath = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
		echo __CBO_TEMP_URL__.$nom_fichier;
	}
	catch(HTML2PDF_exception $e) {
		exit;
	}

} // FIN mode


/* ------------------------------------
FONCTION - Params pour PRODUITS
------------------------------------*/
function getParamsActionPdt() {

	$date_du    = isset($_REQUEST['date_du'])   && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['date_du'])) ? $_REQUEST['date_du'] : '';
	$date_au    = isset($_REQUEST['date_au'])   && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['date_au'])) ? $_REQUEST['date_au'] : '';
	$mois       = isset($_REQUEST['mois'])      && intval($_REQUEST['mois'])  > 0             ? $_REQUEST['mois']    : '';
	$annee      = isset($_REQUEST['annee'])     && intval($_REQUEST['annee']) > 0             ? $_REQUEST['annee']   : '';
	$id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit']) : 0;
	$id_espece  = isset($_REQUEST['id_espece']) ? intval($_REQUEST['id_espece']) : 0;
	$id_client  = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
	$id_groupe  = isset($_REQUEST['id_groupe']) ? intval($_REQUEST['id_groupe']) : 0;
	$sep_fam    = isset($_REQUEST['sep_fam']);
	$sep_clt    = isset($_REQUEST['sep_clt']);

	// Si on a borné une période + une année ou un mois, alors on ne prends que le mois et l'année
	if (($date_du != '' || $date_au != '') && ($annee != '' || $mois != '')) {
		$date_du = '';
		$date_au = '';
	}
	// Si on a mis un mois sans année, on ne prends que la période
	if ($mois != '' && $annee == '') {
		$mois = '';
	}

	if ($date_du == '' && $annee == '') {
		// par défaut, on affiche les facture qui ont moins d'un an
		$dt = new DateTime(date('Y-m-d'));
		$dt->modify('-1 year');
		$date_du = $dt->format('d/m/Y');
	}

	return [
		'date_du'     => $date_du,
		'date_au'     => $date_au,
		'mois'        => $mois,
		'annee'       => $annee,
		'id_produit'  => $id_produit,
		'id_espece'   => $id_espece,
		'id_client'   => $id_client,
		'id_groupe'   => $id_groupe,
		'sep_fam'     => $sep_fam,
		'sep_clt'     => $sep_clt,
	];

} // FIN fonction


/* ------------------------------------
FONCTION - Contenu PDF onglet
------------------------------------*/
function fctPdtGeneral() {

	global $statsManager, $fraisFonctionnementManager;

	$params 	= getParamsActionPdt();
	$date_du 	= $params['date_du'];
	$date_au 	= $params['date_au'];
	$mois 		= $params['mois'];
	$annee 		= $params['annee'];
	$sep_clt	= $params['sep_clt'];
	$sep_fam	= $params['sep_fam'];
	$na 		= '-';
	$euro = ' <span style="font-size:7px;"> EUR</span>';
	$hide_marges = isset($_REQUEST['hide_marges']);
	$show_marges = !$hide_marges;

	// Frais de fonctionnement sur la période
	$ff = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee);

	$periode = $date_du != '' ? 'Du ' . $date_du.' ' : '';
	$periode.= $date_au != '' ? 'au ' . $date_au.'' : '';
	if ($date_du != '' && $date_au == '') { $periode = 'A partir ' . strtolower($periode); }
	$periode.= intval($mois) > 0 ? $mois.' ' : '';
	$periode.= intval($annee) > 0 ? $annee.' ' : '';

	$content = '<div class="header">
                <table class="table w100 mt-15">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Statistiques - Tous les produits</span><br><span class="text-12">'.$periode.'</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>';


	$liste = $statsManager->getStatsProduitsGeneral($params);


	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	$colspan = $sep_clt ? 9 : 8;
	if ($hide_marges) { $colspan-=2; }

	$wColProduit = 10;
	//$wColProduit+= !$sep_clt ? 15 : 0;
	$wColProduit+= !$sep_clt ? 25 : 0;
	$wColProduit+= !$show_marges ? 20 : 0;

	$content.= '<table class="table table-liste w100 mt-5"><tr><th class="w5">Code</th><th class="w'.$wColProduit.'">Produit</th> ';
	$content.= $sep_clt ? '<th class="w25">Client</th>' : '';
	$content.= '<th class="text-center nowrap w10">Poids total facturé</th>';
	$content.= '<th class="text-center nowrap w10">Nb de colis/pièces</th>';
	$content.= '<th class="text-right nowrap w10">CA</th>';
	$content.= $show_marges ? '<th class="text-right nowrap w10">Marge brute</th><th class="text-right nowrap w10">Marge nette</th>' : '';
	$content.= '<th class="t-actions text-right nowrap w10">Part du CA</th></tr>';

	if (empty($liste)) { $content.= '<tr><td class="text-center w100" colspan="'.$colspan.'">Aucune donnée pour la période sélectionnée...</td></tr>'; }
	else {

		$total_poids = 0;
		$total_qte = 0;
		$total_ca = 0;
		$pourcentageTotal = 0;

		// On récupère le Total CA pour le calcul du pourcentage pour le graph
		// ON NE PEUX PAS AVOIR LES MEMES TOTAUX SUR PRODUITS ET CLIENTS, CAR LES AVOIRS SE COMPTABILISENT AU CLIENT, PAS AU PRODUIT !
		$total_ca = $statsManager->getTotalCaPerdiod($params);

		$id_fam_prec = 0;
		$fam_nom_prec = '';

		$total_poids_fam = 0;
		$total_qte_fam = 0;
		$total_ca_fam = 0;
		$total_marge_brute = 0;
		$total_marge_nette = 0;
		$pourcentage_fam = 0;

		$colspan = $sep_clt ? 3 : 2;

		$arrondir = false;
		$deci = $arrondir ? 0 : 2;

		// Tri par CA si pas de tri personnalisé
		if (!$sep_fam && !$sep_clt) {
			usort($liste, function($a, $b) {
				return $a['ca'] < $b['ca'] ? 1 : -1;
			});
		}

		// Boucle sur les résultats
		foreach ($liste as $stat) {
			if ((int)$stat['vendu_piece'] == 0) {
				$total_poids += floatval($stat['poids']);
			} else {
				$total_qte += intval($stat['qte']);
			}

			$pourcentage = round((round($stat['ca'], 2) * 100) / round($total_ca, 2), 2);
			$pourcentageTotal += $pourcentage;

			// On prends les produits pour le graph si on regroupe pas par famille
			if (!$sep_fam) {
				$graph_labels .= '["' . $stat['pdt_nom'] . ' - ' . $pourcentage . '%"],';
				$graph_datas .= round($stat['ca'], 0) . ',';
			}

			$id_fam = isset($stat['id_fam']) ? intval($stat['id_fam']) : 0;
			$nom_fam = isset($stat['nom_fam']) ? trim($stat['nom_fam']) : '';

			// SI on sépare par famille et qu'on a changé de famille, on affiche le sous-total
			if ($sep_fam && $id_fam_prec > 0 && $id_fam_prec != $id_fam) {


				$wSousTotal = $wColProduit - 5;
				if ($show_marges) {
					//$wSousTotal-=20;
				}
				//$wSousTotal = $wColProduit + 15;

				$content .= '<tr class="sous-total">
			<td colspan="' . $colspan . '" class="w' . $wSousTotal . '">Sous-total ' . $fam_nom_prec . '</td>
			<td class="text-right w10">' . number_format($total_poids_fam, 3, '.', '') . '</td>';
				$content .= '<td class="text-center w10">' . $total_qte_fam . '</td>';
				$content .= '<td class="text-right w10">' . number_format($total_ca_fam, $deci, '.', '') . '</td>';
				$content .= $show_marges ? '
				<td class="text-right w10">' . number_format($total_marge_brute, $deci, '.', ' ') . '</td>
				<td class="text-right w10">' . number_format($total_marge_nette, $deci, '.', ' ') . '</td>' : '';
				$content .= '<td class="t-actions text-right w10">' . $pourcentage_fam . ' %</td>
		</tr>';

				// On intègre les données pour le graph pour la famille
				$graph_labels .= '["' . $fam_nom_prec . ' - ' . $pourcentage_fam . '%"],';
				$graph_datas .= round($total_ca_fam, 0) . ',';

				// On réinitilise les compteur avec la valeur du produit de la boucle (première passe nouvelle famille)
				if ((int)$stat['vendu_piece'] == 0) {
					$total_poids_fam = floatval($stat['poids']);
				} else {
					$total_qte_fam = intval($stat['qte']);
				}


				$total_ca_fam = floatval($stat['ca']);
				$pourcentage_fam = $pourcentage;

				// Sinon, on continue dans la même famille si on est dans l'affichage regroupé par famille
			} else if ($sep_fam) {

				// Dans ce cas, on cumule les compteurs
				if ((int)$stat['vendu_piece'] == 0) {
					$total_poids_fam += floatval($stat['poids']);
				} else {
					$total_qte_fam += intval($stat['qte']);
				}


				$total_ca_fam += floatval($stat['ca']);
				$pourcentage_fam += $pourcentage;

			} // FIN sous-total par famille après changement de famille

			$tarif_frs = isset($stat['tarif_frs']) ? floatval($stat['tarif_frs']) : 0;
			$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
			$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1 - ($pourcentage / 100)));

			$total_marge_brute += $marge_brute;
			$total_marge_nette += $marge_nette;

			$pdtCode = isset($stat['pdt_code']) ? $stat['pdt_code'] : $na;
			$pdtNom = isset($stat['pdt_nom']) ? $stat['pdt_nom'] : $na;
			$poids = isset($stat['poids']) && (int)$stat['vendu_piece'] == 0 ? number_format($stat['poids'], 3, '.', '') . ' Kg' : $na;
			$qte = isset($stat['qte']) && (int)$stat['vendu_piece'] == 1 ? $stat['qte'] : $na;
			$ca = isset($stat['ca']) ? number_format($stat['ca'], $deci, '.', '') . $euro : $na;

			$content .= '<tr>
		<td class="w5">' . $pdtCode . '</td>
		<td class="w'.$wColProduit.'">' . $pdtNom . '</td>';
			$content .= $sep_clt ? '<td class="w15">' . $stat['nom_clt'] . '</td>' : '';
			$content .= '
		<td class="text-right w10">' . $poids . '</td>';
			$content .= '<td class="text-center w10">' . $qte . '</td>';
			$content .= '<td class="text-right w10">' . $ca . '</td>';
			$content .= $show_marges ? '<td class="text-right w10">' . number_format($marge_brute, $deci, '.', ' ') . $euro.'</td>
			<td class="text-right w10">' . number_format($marge_nette, $deci, '.', ' ') . $euro.'</td>' : '';
			$content .= '
		<td class="t-actions text-right w10">' . $pourcentage . ' %</td>
	</tr>';

			// On met à jour le numéro de famille pour comparaison avant la prochaine boucle
			if ($sep_fam) {
				$id_fam_prec = $id_fam;
				$fam_nom_prec = $nom_fam;
			}

		} // FIN boucle sur les résultats

		// Dernier sous-total par famille après la fin de boucle
		if ($sep_fam) {

			$wSousTotal = $wColProduit - 5;
			if ($show_marges) {
				//$wSousTotal-=20;
			}

			$content .= '
	<tr class="sous-total">
		<td colspan="' . $colspan . '" class="w' . $wSousTotal . '" >Sous-total ' . $fam_nom_prec . '</td>
		<td class="text-right w10">' . number_format($total_poids_fam, 3, '.', ' ') . ' Kg</td>';
			$content .= '<td class="text-center w10">' . $total_qte_fam . '</td>';
			$content .= '<td class="text-right w10">' . number_format($total_ca_fam, $deci, '.', ' ') . $euro.' </td>';
			$content .= $show_marges ? '
			<td class="text-right w10">' . number_format($total_marge_brute, $deci, '.', ' ') . $euro.' </td>
			<td class="text-right w10">' . number_format($total_marge_nette, $deci, '.', ' ') . $euro.' </td>' : '';
			$content .= '
		<td class="t-actions text-right w10">' . $pourcentage_fam . ' %</td>
	</tr>';

		}
		$wSousTotal = $show_marges ? $wColProduit -5 : $wColProduit;
		$pourcentageTotal = 100;
		$content .= '
		<tr>
			<th colspan="' . $colspan . '" class="w'.$wSousTotal.'">Total produits</th>
			<th class="text-right w10">' . number_format($total_poids, 3, '.', ' ') . ' Kg</th>';
			$content .= '<th class="text-center w10"></th>';
			$content .= '<th class="text-right w10">' . number_format($total_ca, $deci, '.', ' ') . $euro.' </th>';
				$content .= $show_marges ? '
				<th class="text-right w10">' . number_format($total_marge_brute, $deci, '.', ' ') . $euro.' </th>
				<th class="text-right w10">' . number_format($total_marge_nette, $deci, '.', ' ') . $euro.' </th>' : '';
				$content .= '
			<th class="t-actions text-right w10">' . $pourcentageTotal . ' %</th>
		</tr>
		';
	}

	$content.= '</table>';

	// Marges
	if ($show_marges) {

		$content.= '
		<table class="table table-liste w100 mt-25">
			<tr>
				<td class="w80">CA sur la période</td>
				<th class="text-right w20">'.number_format($total_ca,$deci,'.', ' ').$euro.' </th>
			</tr>
			<tr>
				<td class="w80">Prix d\'achats</td>
				<th class="text-right w20">'.number_format($total_ca - $total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge Brute</td>
				<th class="text-right w20">'. number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge brute</td>
				<th class="text-right w20">'. number_format(($total_marge_brute * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
			<tr>
				<td class="w80">Frais de fonctionnement</td>
				<th class="text-right w20">'.number_format($ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge nette</td>
				<th class="text-right w20">'.number_format($total_marge_brute-$ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge nette</td>
				<th class="text-right w20">'. number_format((($total_marge_brute-$ff) * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
		</table>';
	} // FIN marges
	
	
	return $content;

} // FIN fonction


/* ------------------------------------
FONCTION - Contenu PDF onglet
------------------------------------*/
function fctPdtsClts() {

	global $statsManager, $fraisFonctionnementManager, $cnx;

	$produitsManager = new ProduitManager($cnx);

	$params 	= getParamsActionPdt();
	$date_du 	= $params['date_du'];
	$date_au 	= $params['date_au'];
	$mois 		= $params['mois'];
	$annee 		= $params['annee'];
	$id_produit	= $params['id_produit'];
	$na 		= 'N/A';
	$euro 		= ' <span style="font-size:7px;"> EUR</span>';
	$hide_marges = isset($_REQUEST['hide_marges']);
	$show_marges = !$hide_marges;

	// Frais de fonctionnement sur la période
	$ff = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee);

	$periode = $date_du != '' ? 'Du ' . $date_du.' ' : '';
	$periode.= $date_au != '' ? 'au ' . $date_au.'' : '';
	if ($date_du != '' && $date_au == '') { $periode = 'A partir ' . strtolower($periode); }
	$periode.= intval($mois) > 0 ? $mois.' ' : '';
	$periode.= intval($annee) > 0 ? $annee.' ' : '';

	$produit = $id_produit > 0 ? $produitsManager->getProduit($id_produit, false) :false;
	if (!$produit instanceof Produit) {
		return 'Aucun produit sélectionné !';
	}



	$content = '<div class="header">
                <table class="table w100 mt-15">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Statistiques - Produit</span><br><span class="text-12">'.$periode.'</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div><p class="text-center text-16 mt-5 mb-15">'.$produit->getNom().'</p>';


	$liste = $statsManager->getStatsProduitClients($params);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	$colspan = 8;
	if ($hide_marges) { $colspan-=2; }

	//$wColProduit = 35;
	$wColProduit = 40;
	$wColProduit+= !$show_marges ? 20 : 0;

	if (empty($liste)) {
		return $content.='<p>Aucune donnée pour la période sélectionnée...</p>';
	}

	$content.= '<table class="table table-liste w100 mt-5"><tr><th class="w10">Code</th><th class="w'.$wColProduit.'">Client</th> ';
	$content.= '<th class="text-center nowrap w10">Poids total facturé</th>';
	//$content.= '<th class="text-center nowrap w10">Nb de colis/pièces</th>';
	$content.= '<th class="text-right nowrap w10">CA</th>';
	$content.= $show_marges ? '<th class="text-right nowrap w10">Marge brute</th><th class="text-right nowrap w10">Marge nette</th>' : '';
	$content.= '<th class="t-actions text-right nowrap w10">Part du CA</th></tr>';

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$total_marge_brute = 0;
	$total_marge_nette = 0;
	$pourcentageTotal = 0;

	$graph_labels = '';
	$graph_datas = '';

	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

	// Tri par CA
	usort($liste, function($a, $b) {
		return $a['ca'] < $b['ca'] ? 1 : -1;
	});

	// Boucle sur les résultats
	foreach ($liste as $stat) {

		$total_poids += floatval($stat['poids']);
		$total_qte += intval($stat['qte']);

		$pourcentage = round((round($stat['ca'], 2) * 100) / round($total_ca, 2), 2);
		$pourcentageTotal += $pourcentage;
		$graph_labels .= '["' . $stat['clt_nom'] . ' - ' . $pourcentage . '%"],';
		$graph_datas .= round($stat['ca'], 0) . ',';

		$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
		$ca = isset($stat['ca']) ? floatval($stat['ca']) : 0;
		$qte = isset($stat['qte']) ? floatval($stat['qte']) : 0;
		$poids = isset($stat['poids']) ? floatval($stat['poids']) : 0;

		$multQte = $produit->isVendu_piece() ? $qte : $poids;

		// Marge brute = ca - tarif_fr * qte (si à la piece) ou poids

		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1 - ($pourcentage / 100)));

		$total_marge_brute += $marge_brute;
		$total_marge_nette += $marge_nette;

		$clt_code = isset($stat['clt_code']) ? $stat['clt_code'] : $na;
		$clt_nom = isset($stat['clt_nom']) ? $stat['clt_nom'] : $na;
		$poids = isset($stat['poids']) ? number_format($stat['poids'],3,'.', '').' Kg' : $na;
		$qte = isset($stat['qte']) ? $stat['qte'] : $na;
		$ca = isset($stat['ca']) ? number_format($stat['ca'],2,'.', '').$euro : $na;

		$content.= '
		<tr>
			<td class="w10">'. $clt_code . '</td>
			<td class="w'.$wColProduit.'">'.$clt_nom.'</td>
			<td class="text-right w10">'.$poids.'</td>';
		//$content.= '<td class="text-center w10">'.$qte.'</td>';
		$content.= '<td class="text-right w10">'.$ca.'</td>';
		if ($show_marges) {
			$content.= '	
				<td class="text-right w10">'. number_format($marge_brute,$deci, '.', ' ').$euro.'</td>
				<td class="text-right w10">'. number_format($marge_nette,$deci, '.', ' ').$euro.'</td>';
		}
		$content.= '
			<td class="t-actions text-right w10">'.number_format($pourcentage,2,'.', ' ').' %</td>
		</tr>';
	} // FIN boucle sur les résultats

	$content.= '
	<tr>
		<th colspan="2" class="w'.($wColProduit+10).'">Total clients</th>
		<th class="text-right w10">'. number_format($total_poids,3,'.', '').' Kg</th>';
	//$content.= '<th class="text-center w10">'. $total_qte.'</th>';
	$content.= '<th class="text-right w10">'. number_format($total_ca,$deci,'.', '').$euro.'</th>';
		if ($show_marges) {
			$content.= '
				<th class="text-right w10">'.number_format($total_marge_brute,$deci, '.', ' ').$euro.'</th>
				<th class="text-right w10">'.number_format($total_marge_nette,$deci, '.', ' ').$euro.'</th>';
		}
	$pourcentageTotal = 100;
	$content.= '
			<th class="t-actions text-right w10">'.$pourcentageTotal.'%</th>
		</tr>
	</table>';

	if ($show_marges) {

		$content.= '
		<table class="table table-liste w100 mt-25">
			<tr>
				<td class="w80">CA sur la période</td>
				<th class="text-right w20">'.number_format($total_ca,$deci,'.', ' ').$euro.' </th>
			</tr>
			<tr>
				<td class="w80">Prix d\'achats</td>
				<th class="text-right w20">'.number_format($total_ca - $total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge Brute</td>
				<th class="text-right w20">'. number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge brute</td>
				<th class="text-right w20">'. number_format(($total_marge_brute * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
			<tr>
				<td class="w80">Frais de fonctionnement</td>
				<th class="text-right w20">'.number_format($ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge nette</td>
				<th class="text-right w20">'.number_format($total_marge_brute-$ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge nette</td>
				<th class="text-right w20">'. number_format((($total_marge_brute-$ff) * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
		</table>';

	} // FIN marges

	return $content;

} // FIN fonction


/* ------------------------------------
FONCTION - Contenu PDF onglet
------------------------------------*/
function fctFamPdtClts() {

	global $statsManager, $fraisFonctionnementManager, $cnx;

	$especesManager = new ProduitEspecesManager($cnx);

	$params 	= getParamsActionPdt();
	$date_du 	= $params['date_du'];
	$date_au 	= $params['date_au'];
	$mois 		= $params['mois'];
	$annee 		= $params['annee'];
	$id_espece	= $params['id_espece'];
	$na 		= 'N/A';
	$euro 		= ' <span style="font-size:7px;"> EUR</span>';
	$hide_marges = isset($_REQUEST['hide_marges']);
	$show_marges = !$hide_marges;

	// Frais de fonctionnement sur la période
	$ff = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee);

	$periode = $date_du != '' ? 'Du ' . $date_du.' ' : '';
	$periode.= $date_au != '' ? 'au ' . $date_au.'' : '';
	if ($date_du != '' && $date_au == '') { $periode = 'A partir ' . strtolower($periode); }
	$periode.= intval($mois) > 0 ? $mois.' ' : '';
	$periode.= intval($annee) > 0 ? $annee.' ' : '';

	$espece = $id_espece > 0 ? $especesManager->getProduitEspece($id_espece) : new ProduitEspece([]);
	if (!$espece instanceof ProduitEspece) {
		return 'Aucune famille sélectionné !';
	}

	$content = '<div class="header">
                <table class="table w100 mt-15">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Statistiques - Famille</span><br><span class="text-12">'.$periode.'</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div><p class="text-center text-16 mt-5 mb-15">'.$espece->getNom().'</p>';


	$liste = $statsManager->getStatsEspeceClients($params);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	$colspan = 8;
	if ($hide_marges) { $colspan-=2; }

	//$wColProduit = 35;
	$wColProduit = 40;
	$wColProduit+= !$show_marges ? 20 : 0;

	if (empty($liste)) {
		return $content.='<p>Aucune donnée pour la période sélectionnée...</p>';
	}

	$content.= '<table class="table table-liste w100 mt-5"><tr><th class="w10">Code</th><th class="w'.$wColProduit.'">Client</th> ';
	$content.= '<th class="text-center nowrap w10">Poids total facturé</th>';
	//$content.= '<th class="text-center nowrap w10">Nb de colis/pièces</th>';
	$content.= '<th class="text-right nowrap w10">CA</th>';
	$content.= $show_marges ? '<th class="text-right nowrap w10">Marge brute</th><th class="text-right nowrap w10">Marge nette</th>' : '';
	$content.= '<th class="t-actions text-right nowrap w10">Part du CA</th></tr>';

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$pourcentageTotal = 0;

	$graph_labels = '';
	$graph_datas = '';
	$total_marge_brute = 0;
	$total_marge_nette = 0;

	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

	// Tri par CA
	usort($liste, function($a, $b) {
		return $a['ca'] < $b['ca'] ? 1 : -1;
	});


	// Boucle sur les résultats
	foreach ($liste as $stat) {

		$total_poids+= floatval($stat['poids']);
		$total_qte+=   intval($stat['qte']);

		$pourcentage =  round((round($stat['ca'],2) * 100) / round($total_ca,2),2);
		$pourcentageTotal+=$pourcentage;

		$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1-($pourcentage/100)));

		$total_marge_brute+=$marge_brute;
		$total_marge_nette+=$marge_nette;

		$clt_code = isset($stat['clt_code']) ? $stat['clt_code'] : $na;
		$clt_nom = isset($stat['clt_nom']) ? $stat['clt_nom'] : $na;
		$poids = isset($stat['poids']) ? number_format($stat['poids'],3,'.', '') .' Kg' : $na;
		$qte = isset($stat['qte']) ? $stat['qte'] : $na;
		$ca = isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').$euro : $na;

		$content.= '
		<tr>
			<td class="w10">'.$clt_code.'</td>
			<td class="w'.$wColProduit.'">'.$clt_nom.'</td>
			<td class="text-right w10">'.$poids.'</td>';
		//$content.= '<td class="text-center w10">'.$qte.'</td>';
		$content.= '<td class="text-right w10">'.$ca.'</td>';
			if ($show_marges) {
				$content.= '
				<td class="text-right w10">'.number_format($marge_brute,$deci, '.', ' ').$euro.'</td>
				<td class="text-right w10">'.number_format($marge_nette,$deci, '.', ' ').$euro.'</td>';
			}
			$content.= '
				<td class="text-right t-actions w10">'. number_format($pourcentage,2,'.', ' ').' %</td>
			</tr>';

	} // FIN boucle sur les résultats

	$content.= '
		<tr>
			<th colspan="2" class="w'.($wColProduit+5).'">Total clients</th>
			<th class="text-right w10">'.number_format($total_poids,3,'.', '').' Kg</th>';
	//$content.= '<th class="text-center w10">'. $total_qte.'</th>';
	$content.= '<th class="text-right w10">'. number_format($total_ca,$deci,'.', '').$euro.'</th>';
        if ($show_marges) {
			$content.= '
        		<th class="text-right w10">'. number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
				<th class="text-right w10">'. number_format($total_marge_nette,$deci,'.', ' ').$euro.'</th>';
        }
	$pourcentageTotal = 100;
        $content.= '
			<th class="text-right t-actions w10">'.$pourcentageTotal.' %</th>
		</tr>
	</table>';

	if ($show_marges) {

		$content.= '
		<table class="table table-liste w100 mt-25">
			<tr>
				<td class="w80">CA sur la période</td>
				<th class="text-right w20">'.number_format($total_ca,$deci,'.', ' ').$euro.' </th>
			</tr>
			<tr>
				<td class="w80">Prix d\'achats</td>
				<th class="text-right w20">'.number_format($total_ca - $total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge Brute</td>
				<th class="text-right w20">'. number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge brute</td>
				<th class="text-right w20">'. number_format(($total_marge_brute * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
			<tr>
				<td class="w80">Frais de fonctionnement</td>
				<th class="text-right w20">'.number_format($ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge nette</td>
				<th class="text-right w20">'.number_format($total_marge_brute-$ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge nette</td>
				<th class="text-right w20">'. number_format((($total_marge_brute-$ff) * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
		</table>';

	} // FIN marges

    return $content;

} // FIN fonction


/* ------------------------------------
FONCTION - Contenu PDF onglet
------------------------------------*/
function fctPdtsOrigines() {

	global $statsManager, $fraisFonctionnementManager, $cnx;

	$params 	= getParamsActionPdt();
	$date_du 	= $params['date_du'];
	$date_au 	= $params['date_au'];
	$mois 		= $params['mois'];
	$annee 		= $params['annee'];
	$na 		= 'N/A';
	$euro 		= ' <span style="font-size:7px;"> EUR</span>';
	$hide_marges = isset($_REQUEST['hide_marges']);
	$show_marges = !$hide_marges;

	// Frais de fonctionnement sur la période
	$ff = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee);

	$periode = $date_du != '' ? 'Du ' . $date_du.' ' : '';
	$periode.= $date_au != '' ? 'au ' . $date_au.'' : '';
	if ($date_du != '' && $date_au == '') { $periode = 'A partir ' . strtolower($periode); }
	$periode.= intval($mois) > 0 ? $mois.' ' : '';
	$periode.= intval($annee) > 0 ? $annee.' ' : '';


	$content = '<div class="header">
                <table class="table w100 mt-15">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Statistiques - Origines</span><br><span class="text-12">'.$periode.'</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table></div>';


	$liste = $statsManager->getStatsProduitsOrigines($params);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs

	if (empty($liste)) {
		return $content.='<p>Aucune donnée pour la période sélectionnée...</p>';
	}

	$content.= '<table class="table table-liste w100 mt-5"><tr><th class="w35">Origine</th> ';
	$content.= '<th class="text-right nowrap w20">Poids total facturé</th>';
	//$content.= '<th class="text-center nowrap w15">Nb de colis/pièces</th>';
	$content.= '<th class="text-right nowrap w20">CA</th>';
	$content.= '<th class="text-right nowrap w15">Prix moyen au kg</th>';
	$content.= '<th class="t-actions text-right nowrap w10">Part du CA</th></tr>';

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$total_pmkg  = 0;
	$pourcentageTotal = 0;

	$graph_labels = '';
	$graph_datas = '';

	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

	// Tri par CA
	usort($liste, function($a, $b) {
		return $a['ca'] < $b['ca'] ? 1 : -1;
	});


	// Boucle sur les résultats
	foreach ($liste as $stat) {

		$total_poids+= floatval($stat['poids']);
		$total_qte+=   intval($stat['qte']);

		$pourcentage =  round((round($stat['ca'],2) * 100) / round($total_ca,2),2);
		$pourcentageTotal+=$pourcentage;

		$ca = isset($stat['ca']) ? intval($stat['ca']) : 0;
		$poids = isset($stat['poids']) ? floatval($stat['poids']) : 0;
		$pmkilo = $poids > 0 ? $ca / $poids : 0;
		$total_pmkg+=$pmkilo;

		$pays = isset($stat['pays_nom']) ? $stat['pays_nom'] : $na;
		$poids = isset($stat['poids']) ? number_format($stat['poids'],3,'.', '').' Kg' : $na;
		$qte = isset($stat['qte']) ? $stat['qte'] : $na;
		$ca = isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').$euro : $na;

		$content.= '
			<tr>
				<td class="w35">'.$pays.'</td>
				<td class="text-right w20">'.$poids.'</td>';
		//$content.= '<td class="text-center w15">'.$qte.'</td>';
		$content.= '<td class="text-right w20">'.$ca.'</td>
				<td class="text-right w15">'.number_format($pmkilo,2,'.', '').$euro.'</td>
				<td class="text-right w10 t-actions">'.$pourcentage.' %</td>
			</tr>';
		} // FIN boucle sur les résultats

	$content.= '
		<tr>
			<th class="w35">Total origines</th>
			<th class="text-right w20">'. number_format($total_poids,3,'.', '').' kg</th>';
	//$content.= '<th class="text-center w15">'. $total_qte .'</th>';

	$pourcentageTotal = 100;

	$content.= '<th class="text-right w20">'. number_format($total_ca,$deci,'.', '').$euro.'</th>
			<th class="text-right w15">'. number_format($total_pmkg,2,'.', '').$euro.'</th>
			<th class="text-right t-actions w10">'. $pourcentageTotal.' %</th>
		</tr>
	</table>';


	return $content;

} // FIN fonction

/* --
----------------------------------
FONCTION - Contenu PDF onglet
------------------------------------*/
function fctCltGeneral() {

	global $statsManager, $fraisFonctionnementManager, $cnx;

	$params 	= getParamsActionPdt();
	$date_du 	= $params['date_du'];
	$date_au 	= $params['date_au'];
	$mois 		= $params['mois'];
	$annee 		= $params['annee'];
	$na 		= '-';
	$euro 		= ' <span style="font-size:7px;"> EUR</span>';
	$hide_marges = isset($_REQUEST['hide_marges']);
	$show_marges = !$hide_marges;

	// Frais de fonctionnement sur la période
	$ff = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee);

	$periode = $date_du != '' ? 'Du ' . $date_du.' ' : '';
	$periode.= $date_au != '' ? 'au ' . $date_au.'' : '';
	if ($date_du != '' && $date_au == '') { $periode = 'A partir ' . strtolower($periode); }
	$periode.= intval($mois) > 0 ? $mois.' ' : '';
	$periode.= intval($annee) > 0 ? $annee.' ' : '';


	$content = '<div class="header">
                <table class="table w100 mt-15">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Statistiques - Tous les Clients</span><br><span class="text-12">'.$periode.'</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>';


	$liste = $statsManager->getStatsClientsGeneral($params);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	$colspan = 8;
	if ($hide_marges) { $colspan-=2; }

	//$wColProduit = 35;
	$wColProduit = 30;
	$wColProduit+= !$show_marges ? 20 : 0;

	if (empty($liste)) {
		return $content.='<p>Aucune donnée pour la période sélectionnée...</p>';
	}

	$content.= '<table class="table table-liste w100 mt-5"><tr><th class="w10">Code</th><th class="w'.$wColProduit.'">Client</th> ';
	$content.= '<th class="text-center nowrap w10">Poids total facturé</th>';
	$content.= '<th class="text-center nowrap w10">Nb de colis/pièces</th>';
	$content.= '<th class="text-right nowrap w10">CA</th>';
	$content.= $show_marges ? '<th class="text-right nowrap w10">Marge brute</th><th class="text-right nowrap w10">Marge nette</th>' : '';
	$content.= '<th class="t-actions text-right nowrap w10">Part du CA</th></tr>';


$total_poids = 0;
$total_qte   = 0;
$total_ca    = 0;
$total_marge_brute = 0;
$total_marge_nette = 0;
$pourcentageTotal = 0;

$graph_labels = '';
$graph_datas = '';

// On récupère le Total CA pour le calcul du pourcentage pour le graph
//foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }
// ON NE PEUX PAS AVOIR LES MEMES TOTAUX SUR PRODUITS ET CLIENTS, CAR LES AVOIRS SE COMPTABILISENT AU CLIENT, PAS AU PRODUIT !
$total_ca = $statsManager->getTotalCaPerdiod($params);


// Tri par CA
usort($liste, function($a, $b) {
	return $a['ca'] < $b['ca'] ? 1 : -1;
});

// Boucle sur les résultats
foreach ($liste as $stat) {

	$total_poids+= floatval($stat['poids']);
	$total_qte+=   intval($stat['qte']);

	$pourcentage =  round((round($stat['ca'],2) * 100) / round($total_ca,2),2);
	$pourcentageTotal+=$pourcentage;
	$graph_labels.=  '["'.$stat['clt_nom'].' - '.$pourcentage.'%"],';
	$graph_datas.= round($stat['ca'],0).',';

	$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
	$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1-($pourcentage/100)));

	$total_marge_brute+=$marge_brute;
	$total_marge_nette+=$marge_nette;

	$clt_code = isset($stat['clt_code']) ? $stat['clt_code'] : $na;
	$clt_nom = isset($stat['clt_nom']) ? $stat['clt_nom'] : $na;
	$poids = isset($stat['poids']) && floatval($stat['poids']) > 0 ? number_format($stat['poids'],3,'.', '').' Kg' : $na;
	$qte= isset($stat['qte']) && floatval($stat['poids']) == 0 ? $stat['qte'] : $na;
	$ca = isset($stat['ca']) ? number_format($stat['ca'],2,'.', '').$euro : $na;

	$content.= '
		<tr>
			<td class="w10">'.$clt_code.'</td>
			<td class="w'.$wColProduit.'">'.$clt_nom.'</td>';
	$content.= '<td class="text-right w10">'.$poids.'</td>';
	$content.= '<td class="text-center w10">'.$qte.'</td>
			<td class="text-right w10">'.$ca.'</td>';
		if ($show_marges) {
			$content.= '
				<td class="text-right w10">'. number_format($marge_brute,$deci, '.', ' ').$euro.'</td>
				<td class="text-right w10">'. number_format($marge_nette,$deci, '.', ' ').$euro.'</td>';
		}
		$content.= '
			<td class="text-right t-actions w10">'. $pourcentage.' %</td>
		</tr>';

	} // FIN boucle sur les résultats

	$content.= '
		<tr>
			<th colspan="2" class="w'.($wColProduit).'">Total clients</th>
			<th class="text-right w10">'. number_format($total_poids,3,'.', '').' Kg</th>';
	$content.= '<th class="text-center w10"></th>';
	$content.= '<th class="text-right w10">'. number_format($total_ca,$deci,'.', '').$euro.'</th>';
			if ($show_marges) {
				$content.= '	
				<th class="text-right w10">'. number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
				<th class="text-right w10">'. number_format($total_marge_nette,$deci,'.', ' ').$euro.'</th>';
			}
	$pourcentageTotal = 100;
			$content.= '
				<th class="text-right t-actions w10">'. $pourcentageTotal.' %</th>
		</tr>
	</table>';

	if ($show_marges) {

		$content.= '
		<table class="table table-liste w100 mt-25">
			<tr>
				<td class="w80">CA sur la période</td>
				<th class="text-right w20">'.number_format($total_ca,$deci,'.', ' ').$euro.' </th>
			</tr>
			<tr>
				<td class="w80">Prix d\'achats</td>
				<th class="text-right w20">'.number_format($total_ca - $total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge Brute</td>
				<th class="text-right w20">'. number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge brute</td>
				<th class="text-right w20">'. number_format(($total_marge_brute * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
			<tr>
				<td class="w80">Frais de fonctionnement</td>
				<th class="text-right w20">'.number_format($ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge nette</td>
				<th class="text-right w20">'.number_format($total_marge_brute-$ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge nette</td>
				<th class="text-right w20">'. number_format((($total_marge_brute-$ff) * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
		</table>';

	} // FIN marges

	return $content;

} // FIN fonction


/* ------------------------------------
FONCTION - Contenu PDF onglet
------------------------------------*/
function fctCltPdts() {

	global $statsManager, $fraisFonctionnementManager, $cnx;

	$tiersManager = new TiersManager($cnx);

	$params 	= getParamsActionPdt();
	$date_du 	= $params['date_du'];
	$date_au 	= $params['date_au'];
	$mois 		= $params['mois'];
	$annee 		= $params['annee'];
	$id_client	= $params['id_client'];
	$na 		= 'N/A';
	$euro 		= ' <span style="font-size:7px;"> EUR</span>';
	$hide_marges = isset($_REQUEST['hide_marges']);
	$show_marges = !$hide_marges;

	// Frais de fonctionnement sur la période
	$ff = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee);

	$periode = $date_du != '' ? 'Du ' . $date_du.' ' : '';
	$periode.= $date_au != '' ? 'au ' . $date_au.'' : '';
	if ($date_du != '' && $date_au == '') { $periode = 'A partir ' . strtolower($periode); }
	$periode.= intval($mois) > 0 ? $mois.' ' : '';
	$periode.= intval($annee) > 0 ? $annee.' ' : '';

	$client = $id_client > 0 ? $tiersManager->getTiers($id_client) : false;
	if (!$client instanceof Tiers) {
		return 'Aucun client sélectionné !';
	}

	$content = '<div class="header">
                <table class="table w100 mt-15">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Statistiques - Client</span><br><span class="text-12">'.$periode.'</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div><p class="text-center text-16 mt-5 mb-15">'.$client->getNom().'</p>';

	$liste = $statsManager->getStatsClientProduits($params);

	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	//$wColProduit = 35;
	$wColProduit = 40;
	$wColProduit+= !$show_marges ? 20 : 0;

	if (empty($liste)) {
		return $content.='<p>Aucune donnée pour la période sélectionnée...</p>';
	}

	$content.= '<table class="table table-liste w100 mt-5"><tr><th class="w10">Code</th><th class="w'.$wColProduit.'">Produit</th> ';
	$content.= '<th class="text-center nowrap w10">Poids total facturé</th>';
	//$content.= '<th class="text-center nowrap w10">Nb de colis/pièces</th>';
	$content.= '<th class="text-right nowrap w10">CA</th>';
	$content.= $show_marges ? '<th class="text-right nowrap w10">Marge brute</th><th class="text-right nowrap w10">Marge nette</th>' : '';
	$content.= '<th class="t-actions text-right nowrap w10">Part du CA</th></tr>';

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$total_marge_brute = 0;
	$total_marge_nette = 0;

	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

	// Tri par CA
	usort($liste, function($a, $b) {
		return $a['ca'] < $b['ca'] ? 1 : -1;
	});

	// Boucle sur les résultats
	foreach ($liste as $stat) {


		$total_poids += floatval($stat['poids']);
		$total_qte += intval($stat['qte']);

		$pourcentage = round((round($stat['ca'], 2) * 100) / round($total_ca, 2), 2);
		$pourcentageTotal += $pourcentage;

		$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;

		// Marge brute = ca - tarif_fr * qte (si à la piece) ou poids

		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1 - ($pourcentage / 100)));

		$total_marge_brute += $marge_brute;
		$total_marge_nette += $marge_nette;



		$ca = isset($stat['ca']) ? floatval($stat['ca']) : 0;

		$ca = isset($stat['ca']) ? floatval($stat['ca']) : 0;
		$pdt_code = isset($stat['pdt_code']) ? $stat['pdt_code'] : $na;
		$pdt_nom = isset($stat['pdt_nom']) ? $stat['pdt_nom'] : $na;
		$poids = isset($stat['poids']) ? number_format($stat['poids'],3,'.', '') . ' Kg': $na;
		$qte = isset($stat['qte']) ? $stat['qte'] : $na;
		$ca = isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').$euro : $na;

		$mb = $ca > 0 ? number_format($marge_brute,$deci, '.', ' ').$euro : $na;
		$mn = $ca > 0 ? number_format($marge_nette,$deci, '.', ' ').$euro : $na;

		$content.='
			<tr>
				<td class="w10">'.$pdt_code.'</td>
				<td class="w'.$wColProduit.'">'.$pdt_nom.'</td>
				<td class="text-right w10">'.$poids.'</td>';
		//$content.= '<td class="text-center w10">'.$qte.'</td>';
		$content.= '<td class="text-right w10">'.$ca.'</td>';
				if ($show_marges) {
					$content.='
					<td class="text-right w10">'. $mb .'</td>
					<td class="text-right w10">'. $mn.'</td>';
				}
				$content.='
				<td class="text-right t-actions w10">'.$pourcentage.' %</td>
			</tr>';

		} // FIN boucle sur les résultats

	$content.='
		<tr>
			<th colspan="2" class="w'.($wColProduit+5).'">Total produits</th>
			<th class="text-right w10">'.number_format($total_poids,3,'.', '').' Kg</th>';
	//$content.= '<th class="text-center w10">'. $total_qte.'</th>';
	$content.= '<th class="text-right w10">'. number_format($total_ca,$deci,'.', '').$euro.'</th>';
			if ($show_marges) {
				$content.='
				<th class="text-right w10">'. number_format($total_marge_brute,$deci, '.', ' ').$euro.'</th>
				<th class="text-right w10">'. number_format($total_marge_nette,$deci, '.', ' ').$euro.'</th>';

			}
	$pourcentageTotal = 100;
			$content.='
			<th class="text-right t-actions w10">'. $pourcentageTotal.' %</th>
		</tr>
	</table>
	<p class="mt-15 text-11">Sont présentés ici tous les produits spécifiques au client, qu\'ils soient facturés ou non sur la période sélectionnée.</p>';

	if ($show_marges) {

		$content.= '
		<table class="table table-liste w100 mt-25">
			<tr>
				<td class="w80">CA sur la période</td>
				<th class="text-right w20">'.number_format($total_ca,$deci,'.', ' ').$euro.' </th>
			</tr>
			<tr>
				<td class="w80">Prix d\'achats</td>
				<th class="text-right w20">'.number_format($total_ca - $total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge Brute</td>
				<th class="text-right w20">'. number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge brute</td>
				<th class="text-right w20">'. number_format(($total_marge_brute * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
			<tr>
				<td class="w80">Frais de fonctionnement</td>
				<th class="text-right w20">'.number_format($ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge nette</td>
				<th class="text-right w20">'.number_format($total_marge_brute-$ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge nette</td>
				<th class="text-right w20">'. number_format((($total_marge_brute-$ff) * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
		</table>';

	} // FIN marges

	return $content;

} // FIN fonction


/* ------------------------------------
FONCTION - Contenu PDF onglet
------------------------------------*/
function fctCltGrp() {

	global $statsManager, $fraisFonctionnementManager, $cnx;

	$tiersManager = new TiersManager($cnx);

	$params 	= getParamsActionPdt();
	$date_du 	= $params['date_du'];
	$date_au 	= $params['date_au'];
	$mois 		= $params['mois'];
	$annee 		= $params['annee'];
	$id_groupe	= $params['id_groupe'];
	$na 		= 'N/A';
	$euro 		= ' <span style="font-size:7px;"> EUR</span>';
	$hide_marges = isset($_REQUEST['hide_marges']);
	$show_marges = !$hide_marges;

	// Frais de fonctionnement sur la période
	$ff = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee);

	$periode = $date_du != '' ? 'Du ' . $date_du.' ' : '';
	$periode.= $date_au != '' ? 'au ' . $date_au.'' : '';
	if ($date_du != '' && $date_au == '') { $periode = 'A partir ' . strtolower($periode); }
	$periode.= intval($mois) > 0 ? $mois.' ' : '';
	$periode.= intval($annee) > 0 ? $annee.' ' : '';

	$groupe = $id_groupe > 0 ? $tiersManager->getTiersGroupe($id_groupe) : false;
	if (!$groupe instanceof TiersGroupe) {
		return 'Aucun groupe sélectionné !';
	}

	$content = '<div class="header">
                <table class="table w100 mt-15">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-16">Statistiques - Groupe Client</span><br><span class="text-12">'.$periode.'</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div><p class="text-center text-16 mt-5 mb-15">'.$groupe->getNom().'</p>';

	$liste = $statsManager->getStatsGroupeProduits($params);

	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	//$wColProduit = 35;
	$wColProduit = 40;
	$wColProduit+= !$show_marges ? 20 : 0;

	if (empty($liste)) {
		return $content.='<p>Aucune donnée pour la période sélectionnée...</p>';
	}

	$content.= '<table class="table table-liste w100 mt-10"><tr><th class="w5">Code</th><th class="w'.$wColProduit.'">Produit</th> ';
	$content.= '<th class="text-center nowrap w10">Poids total facturé</th>';
	//$content.= '<th class="text-center nowrap w10">Nb de colis/pièces</th>';
	$content.= '<th class="text-right nowrap w10">CA</th>';
	$content.= $show_marges ? '<th class="text-right nowrap w10">Marge brute</th><th class="text-right nowrap w10">Marge nette</th>' : '';
	$content.= '<th class="t-actions text-right nowrap w10">Part du CA</th></tr>';

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$pourcentageTotal = 0;
	$total_marge_brute = 0;
	$total_marge_nette = 0;
	$graph_labels = '';
	$graph_datas = '';

	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

	// Tri par CA
	usort($liste, function($a, $b) {
		return $a['ca'] < $b['ca'] ? 1 : -1;
	});

	// Boucle sur les résultats
	foreach ($liste as $stat) {
		$total_poids+= floatval($stat['poids']);
		$total_qte+=   intval($stat['qte']);

		$pourcentage =  round((round($stat['ca'],2) * 100) / round($total_ca,2),2);
		$pourcentageTotal += $pourcentage;
		$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1-($pourcentage/100)));
		if (intval($stat['ca']) == 0) {  $marge_nette = 0; }

		$total_marge_brute+= $marge_brute;
		$total_marge_nette+=$marge_nette;

		$ca = isset($stat['ca']) ? floatval($stat['ca']) : 0;

		$pdt_code = isset($stat['pdt_code']) ? $stat['pdt_code'] : $na;
		$pdt_nom = isset($stat['pdt_nom']) ? $stat['pdt_nom'] : $na;
		$poids = isset($stat['poids']) ? number_format($stat['poids'],3,'.', '').' Kg' : $na;
		$qte = isset($stat['qte']) ? $stat['qte'] : $na;
		$ca = isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').$euro : $na;

		$content.='
		<tr>
			<td class="w10">'.$pdt_code.'</td>
			<td class="w'.$wColProduit.'">'.$pdt_nom.'</td>
			<td class="text-right w10">'.$poids.'</td>';
		//$content.= '<td class="text-center w10">'.$qte.'</td>';
		$content.= '<td class="text-right w10">'.$ca.'</td>';
			if ($show_marges) {
				$content.='
				<td class="text-right w10">'. number_format($marge_brute,$deci, '.', ' ').$euro.'</td>
				<td class="text-right w10">'. number_format($marge_nette,$deci, '.', ' ').$euro.'</td>';
			}
			$content.='
			<td class="text-right t-actions w10">'.$pourcentage.' %</td>
		</tr>';

	} // FIN boucle sur les résultats

	$content.='
		<tr>
			<th colspan="2" class="w'.(10+$wColProduit).'">Total produits</th>
			<th class="text-right w10">'.number_format($total_poids,3,'.', '').' Kg</th>';
	//$content.= '<th class="text-center w10">'. $total_qte.'</th>';
	$content.= '<th class="text-right w10">'. number_format($total_ca,$deci,'.', '').$euro.'</th>';
			if ($show_marges) {
				$content.='
					<th class="text-right w10">'. number_format($total_marge_brute,$deci, '.', ' ').$euro.'</th>
					<th class="text-right w10">'. number_format($total_marge_nette,$deci, '.', ' ').$euro.'</th>';
			}
	$pourcentageTotal = 100;
			$content.='
			<th class="text-right t-actions w10">'. $pourcentageTotal.' %</th>
		</tr>
	</table>';

	if ($show_marges) {

		$content.= '
		<table class="table table-liste w100 mt-25">
			<tr>
				<td class="w80">CA sur la période</td>
				<th class="text-right w20">'.number_format($total_ca,$deci,'.', ' ').$euro.' </th>
			</tr>
			<tr>
				<td class="w80">Prix d\'achats</td>
				<th class="text-right w20">'.number_format($total_ca - $total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge Brute</td>
				<th class="text-right w20">'. number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge brute</td>
				<th class="text-right w20">'. number_format(($total_marge_brute * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
			<tr>
				<td class="w80">Frais de fonctionnement</td>
				<th class="text-right w20">'.number_format($ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Marge nette</td>
				<th class="text-right w20">'.number_format($total_marge_brute-$ff,$deci,'.', ' ').$euro.'</th>
			</tr>
			<tr>
				<td class="w80">Taux de marge nette</td>
				<th class="text-right w20">'. number_format((($total_marge_brute-$ff) * 100) / $total_ca,$deci,'.', ' ').' %</th>
			</tr>
		</table>';

	} // FIN marges

	return $content;

} // FIN fonction


/* ------------------------------------
FONCTION - Contenu PDF onglet
------------------------------------*/
function fctCdp() {

	global $statsManager, $fraisFonctionnementManager, $cnx;

	$na 		= 'N/A';
	$euro 		= ' <span style="font-size:7px;"> EUR</span>';

	$p1_date_du   = isset($_REQUEST['p1_date_du'])  && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['p1_date_du'])) ? $_REQUEST['p1_date_du'] : '';
	$p2_date_du   = isset($_REQUEST['p2_date_du'])  && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['p2_date_du'])) ? $_REQUEST['p2_date_du'] : '';
	$p1_date_au   = isset($_REQUEST['p1_date_au'])  && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['p1_date_au'])) ? $_REQUEST['p1_date_au'] : '';
	$p2_date_au   = isset($_REQUEST['p2_date_au'])  && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['p2_date_au'])) ? $_REQUEST['p2_date_au'] : '';
	$p1_mois      = isset($_REQUEST['p1_mois'])     && intval($_REQUEST['p1_mois'])  > 0             ? $_REQUEST['p1_mois']    : '';
	$p2_mois      = isset($_REQUEST['p2_mois'])     && intval($_REQUEST['p2_mois'])  > 0             ? $_REQUEST['p2_mois']    : '';
	$p1_annee     = isset($_REQUEST['p1_annee'])    && intval($_REQUEST['p1_annee']) > 0             ? $_REQUEST['p1_annee']   : '';
	$p2_annee     = isset($_REQUEST['p2_annee'])    && intval($_REQUEST['p2_annee']) > 0             ? $_REQUEST['p2_annee']   : '';
	$id_client = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
	$id_groupe = isset($_REQUEST['id_groupe']) ? intval($_REQUEST['id_groupe']) : 0;
	$id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit']) : 0;
	$id_espece = isset($_REQUEST['id_espece']) ? intval($_REQUEST['id_espece']) : 0;

	// Si on a borné une période + une année ou un mois, alors on ne prends que le mois et l'année
	if (($p1_date_du != '' || $p1_date_du != '') && ($p1_annee != '' || $p1_mois != '')) {
		$p1_date_du = '';
		$p1_date_au = '';
	}
	// Si on a borné une période + une année ou un mois, alors on ne prends que le mois et l'année
	if (($p2_date_du != '' || $p2_date_du != '') && ($p2_annee != '' || $p2_mois != '')) {
		$p2_date_du = '';
		$p2_date_au = '';
	}
	// Si on a mis un mois sans année, on ne prends que la période
	if ($p1_mois != '' && $p1_annee == '') {
		$p1_mois = '';
	}
	// Si on a mis un mois sans année, on ne prends que la période
	if ($p2_mois != '' && $p2_annee == '') {
		$p2_mois = '';
	}

	if ($p1_date_du == '' && $p1_annee == '') {
		// par défaut, on affiche les facture qui ont moins d'un an
		$dt = new DateTime(date('Y-m-d'));
		$dt->modify('-1 year');
		$p1_date_du = $dt->format('d/m/Y');
	}

	if ($p2_date_du == '' && $p2_annee == '') {
		// par défaut, on affiche les facture qui ont moins d'un an
		$dt = new DateTime(date('Y-m-d'));
		$dt->modify('-1 year');
		$p2_date_du = $dt->format('d/m/Y');
	}



	// Si on a séléctionné un groupe, on ne tiens pas compte du client
	if ($id_groupe > 0) { $id_client = 0; }

	// Si on a sélectionné une famille, on ne tiens pas compte du produit
	if ($id_espece > 0) { $id_produit = 0; }

	// Si on a sélectionné un produit, on ne tiens pas compte du client
	if ($id_produit > 0) { $id_client = 0; $id_groupe = 0; }

	// Si on a un client et une famille, on ne retiens que la famille
	if ($id_client > 0 && $id_espece > 0) { $id_client = 0; }

	// Si on a un groupe et une famille, on ne retiens que la famille
	if ($id_groupe > 0 && $id_espece > 0) { $id_groupe = 0; }

	// Frais de fonctionnement sur la période
	$ff_p1 = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($p1_date_du, $p1_date_au, $p1_mois, $p1_annee);
	$ff_p2 = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($p2_date_du, $p2_date_au, $p2_mois, $p2_annee);

	$params_1 = [
		'date_du'     => $p1_date_du,
		'date_au'     => $p1_date_au,
		'mois'        => $p1_mois,
		'annee'       => $p1_annee,
		'id_client'   => $id_client,
		'id_groupe'   => $id_groupe,
		'id_produit'  => $id_produit,
		'id_espece'   => $id_espece
	];

	$params_2 = [
		'date_du'     => $p2_date_du,
		'date_au'     => $p2_date_au,
		'mois'        => $p2_mois,
		'annee'       => $p2_annee,
		'id_client'   => $id_client,
		'id_groupe'   => $id_groupe,
		'id_produit'  => $id_produit,
		'id_espece'   => $id_espece
	];

	if ($p1_date_du == '' && $p1_annee == '' && $p2_date_du == '' && $p2_annee == '') { return '<p>Sélectionnez deux périodes à comparer...</p>'; }

	$p1_txt = '';
	$p1_txt.= $p1_mois != '' ? Outils::getMoisListe()[$p1_mois].' ' : '';
	$p1_txt.= $p1_date_du != '' && $p1_date_au == '' ? 'A partir du ' : '';
	$p1_txt.= $p1_date_du != '' && $p1_date_au != '' ? 'Du ' : '';
	$p1_txt.= $p1_date_du != '' ? $p1_date_du . ' ' : '';
	$p1_txt.= $p1_date_au != '' ? ' au ' . $p1_date_au . ' ' : '';
	$p1_txt.= trim($p1_txt) == '' && $p1_annee  != '' ? 'Année ' . $p1_annee : '';
	$p1_txt.= $p1_mois != '' && $p1_annee != '' ? $p1_annee : '';

	$p2_txt = '';
	$p2_txt.= $p2_mois != '' ? Outils::getMoisListe()[$p2_mois].' ' : '';
	$p2_txt.= $p2_date_du != '' && $p2_date_au == '' ? 'A partir du ' : '';
	$p2_txt.= $p2_date_du != '' && $p2_date_au != '' ? 'Du ' : '';
	$p2_txt.= $p2_date_du != '' ? $p2_date_du . ' ' : '';
	$p2_txt.= $p2_date_au != '' ? ' au ' . $p2_date_au . ' ' : '';
	$p2_txt.= trim($p2_txt) == '' && $p2_annee  != '' ? 'Année ' . $p2_annee : '';
	$p2_txt.= $p2_mois != '' && $p2_annee != '' ? $p2_annee : '';

	$na 		= 'N/A';
	$euro 		= ' <span style="font-size:7px;"> EUR</span>';
	$hide_marges = isset($_REQUEST['hide_marges']);
	$show_marges = !$hide_marges;

	$colonne_txt = $id_produit > 0 || $id_espece > 0 ? 'Client' : 'Produit';
	$champPrefixe = $id_produit > 0 || $id_espece > 0 ? 'clt' : 'pdt';
	$champ_code = $champPrefixe.'_code';
	$champ_nom = $champPrefixe.'_nom';

	if ($id_produit > 0) { $methodeStat = 'getStatsProduitClients';	}
	else if ($id_client > 0) { $methodeStat = 'getStatsClientProduits'; }
	else if ($id_espece > 0) { $methodeStat = 'getStatsEspeceClients'; }
	else if ($id_groupe > 0) { $methodeStat = 'getStatsGroupeProduits'; }
	else { $methodeStat = 'getStatsProduitsGeneral'; }

	$periode1 = $p1_date_du != '' ? 'Du ' . $p1_date_du.' ' : '';
	$periode1.= $p1_date_au != '' ? 'au ' . $p1_date_au.'' : '';
	if ($p1_date_du != '' && $p1_date_au == '') { $periode1 = 'A partir ' . strtolower($periode1); }
	$periode1.= intval($p1_mois) > 0 ? $p1_mois.' ' : '';
	$periode1.= intval($p1_annee) > 0 ? $p1_annee.' ' : '';

	$periode2 = $p2_date_du != '' ? 'Du ' . $p2_date_du.' ' : '';
	$periode2.= $p2_date_au != '' ? 'au ' . $p2_date_au.'' : '';
	if ($p2_date_du != '' && $p2_date_au == '') { $periode2 = 'A partir ' . strtolower($periode2); }
	$periode2.= intval($p2_mois) > 0 ? $p2_mois.' ' : '';
	$periode2.= intval($p2_annee) > 0 ? $p2_annee.' ' : '';

	$wColProduit = 35;
	$wColProduit+= !$show_marges ? 20 : 0;

	$content = '<div class="header">
					<table class="table w100 mt-15">
						<tr>
							<td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
							<td class="w34 text-center pt-10">
								<span class="text-16">Statistiques</span><br><span class="text-12">Comparaison de périodes</span>
							</td>
							<td class="w33 text-right text-14">
								<p class="text-18"><b>IPREX</b></p>
								<p class="text-11 gris-9">Intranet PROFIL EXPORT</p>
							</td>
						</tr>                
					</table>
               </div>';


	// Colonne gauche
	$content.= '<table class="table w100">
					<tr>
						<td class="w100" valign="top"><p class="text-center mt-15"><b>'.strtoupper($periode1).'</b></p> </td></tr></table>';
	$content.='
							<table class="table table-liste w100 mt-5">
								<tr>
									<th class="w5">Code</th>
									<th class="w'.$wColProduit.'">'. $colonne_txt.'</th>
									<th class="text-right w10">Poids</th>
									<th class="text-center w10">Colis/blocs</th>
									<th class="text-right w10">CA</th>';
									if ($show_marges) {
										$content.='
											<th class="text-right w10">Marge brute</th>
											<th class="text-right w10">Marge nette</th>';
									}
	$content.='
									<th class="text-right t-actions w10">Part du CA</th>
								</tr>';

	$liste = $statsManager->$methodeStat($params_1);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	if (empty($liste)) {
		$colspan = $show_marges ? 8 : 6;
		$content.='
								<tr><td colspan="'. $colspan.'" class="w100 text-center">Aucune donnée pour la période sélectionnée...</td></tr>';
	}

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$pourcentageTotal = 0;
	$total_marge_brute = 0;
	$total_marge_nette = 0;
	$graph_labels = '';
	$graph_datas = '';

	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

	// Boucle sur les résultats
	foreach ($liste as $stat) {

		$total_poids+= floatval($stat['poids']);
		$total_qte+=   intval($stat['qte']);

		$pourcentage =  round((round($stat['ca'],0) * 100) / round($total_ca,0),0);
		$pourcentageTotal+=$pourcentage;

		$graph_labels.=  '["'.$stat[$champ_nom].' - '.$pourcentage.'%"],';
		$graph_datas.= round($stat['ca'],0).',';

		$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff_p1 : $marge_brute - ($ff_p1 * (1-($pourcentage/100)));

		$total_marge_brute+=$marge_brute;
		$total_marge_nette+=$marge_nette;

		$cc = isset($stat[$champ_code]) ? $stat[$champ_code] : $na;
		$cn = isset($stat[$champ_nom]) ? $stat[$champ_nom] : $na;
		$poids = isset($stat['poids']) ? number_format($stat['poids'],3,'.', '').' Kg' : $na;
		$qte = isset($stat['qte']) ? $stat['qte'] : $na;
		$ca = isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').$euro : $na;

		$content.='
								<tr>
									<td class="w5">'.$cc.'</td>
									<td class="w'.$wColProduit.'">'.$cn.'</td>
									<td class="text-right w10">'.$poids.'</td>
									<td class="text-center w10">'.$qte.'</td>
									<td class="text-right w10">'.$ca.'</td>';
									if ($show_marges) {
										$content.='
											<td class="text-right w10">'.  number_format($marge_brute,$deci, '.', ' ').$euro.'</td>
											<td class="text-right w10">'.  number_format($marge_nette,$deci, '.', ' ').$euro.'</td>';
									}
									$content.='
										<td class="text-right w10 t-actions">'. $pourcentage.' %</td>
								</tr>';
	
	} // FIN boucle sur les résultats

		$content.='
							<tr>
								<th colspan="2" class="w'.(5+$wColProduit).'">Total '.strtolower($colonne_txt).'s</th>
								<th class="text-right w10">'. number_format($total_poids,3,'.', '').' Kg</th>
								<th class="text-center w10">'. $total_qte.'</th>
								<th class="text-right w10">'.number_format($total_ca,$deci,'.', '').$euro .'</th>';
								if ($show_marges) {
									$content.='
										<th class="text-right w10">'.number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
										<th class="text-right w10">'. number_format($total_marge_nette,$deci,'.', ' ').$euro.'</th>';
								}
		$pourcentageTotal = 100;
		$content.='
								<th class="text-right w10 t-actions">'. $pourcentageTotal.' %</th>
							</tr>
						</table>';


	// Colonne droite
	$content.= '<table class="table w100">
					<tr>
						<td class="w100" valign="top"><p class="text-center mt-15"><b>'.strtoupper($periode2).'</b></p></td></tr></table>';

	$content.='
					<table class="table table-liste w100 mt-5">
							<tr>
								<th class="w5">Code</th>
								<th class="w'.$wColProduit.'">'. $colonne_txt.'</th>
								<th class="text-right w10">Poids</th>
								<th class="text-center w10">Colis/blocs</th>
								<th class="text-right w10">CA</th>';
				if ($show_marges) {
					$content.='
										<th class="text-right w10">Marge brute</th>
										<th class="text-right w10">Marge nette</th>';
				}
				$content.='
								<th class="text-right t-actions w10">Part du CA</th>
						</tr>';

	$liste = $statsManager->$methodeStat($params_2);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	if (empty($liste)) {
		$colspan = $show_marges ? 8 : 6;
		$content.='
						<tr><td colspan="'. $colspan.'" class="w100 text-center">Aucune donnée pour la période sélectionnée...</td></tr>';
	}

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$pourcentageTotal = 0;
	$total_marge_brute = 0;
	$total_marge_nette = 0;
	$graph_labels = '';
	$graph_datas = '';

	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

	// Boucle sur les résultats
	foreach ($liste as $stat) {

		$total_poids+= floatval($stat['poids']);
		$total_qte+=   intval($stat['qte']);

		$pourcentage =  round((round($stat['ca'],0) * 100) / round($total_ca,0),0);
		$pourcentageTotal+=$pourcentage;

		$graph_labels.=  '["'.$stat[$champ_nom].' - '.$pourcentage.'%"],';
		$graph_datas.= round($stat['ca'],0).',';

		$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff_p2 : $marge_brute - ($ff_p2 * (1-($pourcentage/100)));

		$total_marge_brute+=$marge_brute;
		$total_marge_nette+=$marge_nette;

		$cc = isset($stat[$champ_code]) ? $stat[$champ_code] : $na;
		$cn = isset($stat[$champ_nom]) ? $stat[$champ_nom] : $na;
		$poids = isset($stat['poids']) ? number_format($stat['poids'],3,'.', '').' Kg' : $na;
		$qte = isset($stat['qte']) ? $stat['qte'] : $na;
		$ca = isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').$euro : $na;

		$content.='
						<tr>
							<td class="w5">'.$cc.'</td>
							<td class="w'.$wColProduit.'">'.$cn.'</td>
							<td class="text-right w10">'.$poids.'</td>
							<td class="text-center w10">'.$qte.'</td>
							<td class="text-right w10">'.$ca.'</td>';
						if ($show_marges) {
							$content.='
									<td class="text-right w10">'.  number_format($marge_brute,$deci, '.', ' ').$euro.'</td>
									<td class="text-right w10">'.  number_format($marge_nette,$deci, '.', ' ').$euro.'</td>';
						}
						$content.='
								<td class="text-right w10 t-actions">'. $pourcentage.' %</td>
						</tr>';

	} // FIN boucle sur les résultats

	$content.='
						<tr>
							<th colspan="2" class="w'.(5+$wColProduit).'">Total '.strtolower($colonne_txt).'s</th>
							<th class="text-right w10">'. number_format($total_poids,3,'.', '').' Kg</th>
							<th class="text-center w10">'. $total_qte.'</th>
							<th class="text-right w10">'.number_format($total_ca,$deci,'.', '').$euro .'</th>';
					if ($show_marges) {
						$content.='
									<th class="text-right w10">'.number_format($total_marge_brute,$deci,'.', ' ').$euro.'</th>
									<th class="text-right w10">'. number_format($total_marge_nette,$deci,'.', ' ').$euro.'</th>';
					}
					$pourcentageTotal = 100;
					$content.='
							<th class="text-right w10 t-actions">'. $pourcentageTotal.' %</th>
						</tr>
					</table>';


	// Fin tableau à deux colonnes
	return $content;

} // FIN fonction