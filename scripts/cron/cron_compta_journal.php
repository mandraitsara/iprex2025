<?php
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
ini_set('display_errors' ,1);
/* ******************************************************************************
 *  TACHE CRON
 *  Génère le journal des ventes et l'envoie à la compta
 *
 *  (c) Cédric Bouillon 2021
 *  INTERSED
 ****************************************************************************** */
$skipAuth = true;
$test = true;

require_once dirname( __FILE__ ).'/../php/config.cli.php';
require_once dirname( __FILE__ ).'/../../class/Cron.class.php';
require_once dirname( __FILE__ ).'/../../class/CronsManager.class.php';
require_once dirname( __FILE__ ).'/../../class/Log.class.php';
require_once dirname( __FILE__ ).'/../../class/LogManager.class.php';
require_once dirname( __FILE__ ).'/../../vendor/html2pdf/html2pdf.class.php';
define('__CBO_CSS_URL__', dirname( __FILE__ ).'/../../css/');

$cronsManager = new CronsManager($cnx);
$cron = $cronsManager->getCronByFileName(basename(__FILE__));
if (!$cron instanceof Cron) { exit('Non déclaré en BSDD'); }

if (!$test) {
	if (!$cron->isActif()) { exit('Inactif'); }
    $cron->setExecution(date('Y-m-d H:i:s'));
	$cronsManager->saveCron($cron);
}



/* ******************************************************************************
 *  PARAMETRES
 ****************************************************************************** */
$email_to = $test ? 'ppactol@boostervente.com' :  'valerie.rostang@carpentrasexpertscomptables.fr';
$email_from = 'contact@profilexport.fr';
$nom_cron	= 'Génération et envoir du journal des ventes pour la comptabilité';
$chemin = dirname( __FILE__ ).'/../../temp/';
$nom_fichier = "journal_des_ventes.pdf";

/* ******************************************************************************
 *  FIN PARAMETRES
 ****************************************************************************** */
$show_debug = isset($_REQUEST['debug']);

if ($show_debug) { ?>

	<!DOCTYPE html><html>
	<head>
		<meta charset="utf-8"/>
		<meta name="robots" content="noindex, nofollow">
		<title>iPrex</title>
		<style type="text/css">
			body { font-family: "Courier New", Courier, monospace; margin: 0; padding: 10px; }
			body * { padding: 0;}

		</style>
	</head>
	<body>
	<h1>CRON iPrex</h1><hr>
	<h2><?php echo $nom_cron; ?></h2><hr>

<?php }
require_once dirname( __FILE__ ).'/../../vendor/html2pdf/html2pdf.class.php';
ob_start();

$margeEnTetePdt = 25;

$content_fichier = genereContenuPdf();
$content_header = genereHeaderPdf();
$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
$contentPdf.= $content_fichier;
$contentPdf.= '</page>'. ob_get_clean();

// Enregistrement de la version page unique
try {
	$marges = [7, 12, 7, 12];
	$nom_fichier = 'journal_des_ventes.pdf';
	$html2pdf = new HTML2PDF('L', 'A4', 'fr', false, 'ISO-8859-15', $marges);
	$html2pdf->pdf->SetAutoPageBreak(false, 0);
	$html2pdf->setDefaultFont('helvetica');
	$html2pdf->writeHTML(utf8_decode($contentPdf));

	$savefilepath = dirname( __FILE__ ).'/../../temp/' . $nom_fichier;
    //$savefilepath = __CBO_ROOT_PATH__ . '/temp/' . $nom_fichier;
	$html2pdf->Output($savefilepath, 'F');
} catch (HTML2PDF_exception $e) {
	var_dump($e);
	exit;
}


function genereHeaderPdf() {

	$date_du = date("Y-m-d", strtotime("first day of previous month"));
	$date_au = date("Y-m-d", strtotime("last day of previous month"));

/*	$date_du = '2021-12-01';
	$date_au = '2021-12-31';*/

	$html = '
        <table class="w100">
            <tr>
                <td class="w50">'.ucfirst(Outils::getDate_verbose(date('Y-m-d H:i:s'), true, ' à ')).'</td>
                <td class="w50 text-right">Page [[page_cu]]/[[page_nb]]</td>
            </tr>
        </table>
        <table class="w100">
            <tr>
                <td class="w100 text-center">Journal des factures du '.Outils::dateSqlToFr($date_du).' au '.Outils::dateSqlToFr($date_au).'</td>
            </tr>
            <tr>
             <td class="w100 text-center">Montants en EUR</td>
            </tr>
        </table>
        <table class="table compta mt-12">
            <tr>
                <th class="w8">Numéro</th>
                <th class="w10">Client</th>
                <th class="w24">Nom</th>
                <th class="w10 text-center">Date</th>
                <th class="w6 text-center">Base TVA<br>5.5%</th>
                <th class="w6 text-center">Base TVA<br>20%</th>
                <th class="w6 text-center">Base<br>Intracom</th>
                <th class="w6 text-center">Base<br>Export</th>
                <th class="w8 text-center">Montant<br>HT</th>
                <th class="w6 text-center">TVA</th>
                <th class="w10 text-center">Montant<br>TTC</th>
            </tr>
        </table>';

	return $html;
}

function genereContenuPdf() {

	$ids_pays_ue = [4,2,6,21,18,5,24,7,25,11,33,16,17,9,19,8,10,32];
	//$ids_pays_ue = [1,2,3,6,8,11,12,13,14,16]; (DEV)
	$paysFrance = [32, 1];
	//$paysFrance = [15, 4]; (DEV)

	$total_general_base55= 0;
	$total_general_base20 = 0;
	$total_general_ht = 0;
	$total_general_tva55 = 0;
	$total_general_tva20 = 0;
	$total_general_ttc = 0;
	$total_general_base_intracom = 0;
	$total_general_base_export = 0;

    global $cnx, $margeEnTetePdt;
	// Récupération de toutes les factures à transmettre
	$date_du = date("Y-m-d", strtotime("first day of previous month"));
	$date_au = date('Y-m').'-01';

	//$date_du = '2021-12-01';
	//$date_au = '2022-01-01';

	$query_factures = 'SELECT f.`id`,
                        f.`num_facture`,
                        f.`total_ttc`,
                        DATE_FORMAT(f.`date`, "%d/%m/%Y") AS datemvt,
                        DATE_FORMAT(DATE_ADD(f.`date`, INTERVAL 1 YEAR), "%d%m%Y") AS dateexp,
                        f.`montant_ht` AS ht,
                        f.`montant_interbev` AS interbev,
                        t.`code` AS codeclt,
                        t.`nom` AS nomclt,
                        IF (a1.`id_pays` IS NOT NULL, a1.`id_pays`, IFNULL(a2.`id_pays`, 0)) AS id_pays
                    FROM `pe_factures` f
                        JOIN `pe_tiers` t ON t.`id` = f.`id_tiers_facturation`
                        LEFT JOIN `pe_adresses` a1 ON a1.`id_tiers` = f.`id_tiers_facturation` AND a1.`type` = 0
                        LEFT JOIN `pe_adresses` a2 ON a2.`id_tiers` = f.`id_tiers_facturation` AND a2.`type` = 1
                    WHERE f.`supprime` = 0 
                      AND f.`date` >= "'.$date_du.'" AND f.`date` < "'.$date_au.'"  
                      GROUP BY  f.`id`
                    ORDER BY  f.`date`, f.`num_facture` ';

	// if ($test) {
	// 	var_dump($query_factures);
	// }
	
	$query = $cnx->prepare($query_factures);
	$query->execute();

	$html = ' <table class="table compta">';

    $resultats = $query->fetchAll(PDO::FETCH_ASSOC);
    if (empty($resultats)) {
        $html.= '<tr><td colspan="10" class="text-center">Aucune facture pour la période</td></tr>';
    } else {
		foreach ($resultats as $fact) {

			$query_liste_lignes = 'SELECT DISTINCT `tva` AS taux FROM `pe_facture_lignes` WHERE `tva` != 0 AND `id_facture` = ' . (int)$fact['id'];

			$query4 = $cnx->prepare($query_liste_lignes);
			$query4->execute();

			$liste_tvas = [];
			$tvas_facture = [];
			$tva = 0.0;

			foreach ($query4->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

				$taux = isset($donnee['taux']) ? floatval($donnee['taux']) : 0;

				if ($taux == 0) { continue; }

				// pour chaque taux, on récupère le total de tva payé
				$query_total = 'SELECT SUM(`prix_vente` * `taux_tva`) AS total FROM `pev_marges_factures` WHERE `id_facture` = ' . (int)$fact['id'] . ' AND `taux_tva`*100 = ' . $taux;

				$query5= $cnx->prepare($query_total);
				$query5->execute();
				$donnees2 = $query5->fetch(PDO::FETCH_ASSOC);
				$montant = $donnees2 && isset($donnees2['total']) ? floatval($donnees2['total']) : 0;
				if ($montant == 0) { continue; }

				$tvas_facture[(string)$taux] = round($montant,2);

			} // FIN boucle taux

			$query_liste = "SELECT ff.`id`, ff.`nom`, ff.`type`, ff.`id_taxe`, ff.`valeur`, ff.`id_facture`, IF (t.`id` IS NOT NULL, t.`nom`, '') AS taxe_nom,  IF (t.`id` IS NOT NULL, t.`taux`, '') AS taxe_taux
							FROM `pe_facture_frais` ff
								LEFT JOIN `pe_taxes` t ON t.`id` = ff.`id_taxe`
						WHERE ff.`id_facture` =  ".(int)$fact['id']." 
							ORDER BY ff.`nom` DESC";
			$query7 = $cnx->prepare($query_liste);
			$query7->execute();

			$ht     = floatval($fact['ht']) < 0 ? floatval($fact['ht']) * -1 : floatval($fact['ht']);
			$interbev = floatval($fact['interbev']) < 0 ? floatval($fact['interbev']) * -1 : floatval($fact['interbev']);

			$total_ht_tva_20 = 0.0;
			$total_ht_tva_10 = 0.0;
			$total_ht_tva_55 = 0.0;
			$total_ht_intracom = 0.0;
			$total_ht_export = 0.0;

			foreach ($query7->fetchAll(PDO::FETCH_ASSOC) as $donnees7) {
				$taxeTaux = isset($donnees7['taxe_taux']) ? floatval($donnees7['taxe_taux']) : 0;
				$taxeType = isset($donnees7['type']) ? intval($donnees7['type']) : 0;
				$taxeValeur = isset($donnees7['valeur']) ? floatval($donnees7['valeur']) : 0;
				if ($taxeTaux == 0) {
					if (in_array(intval($fact['id_pays']), $ids_pays_ue)) {
						$total_ht_intracom += round($taxeValeur,2);
					} else if (!in_array(intval($fact['id_pays']), $paysFrance)) {
						$total_ht_export += round($taxeValeur,2);
					}
                    continue;
                }
                if ($taxeTaux == 20) {
					$total_ht_tva_20 += round($taxeValeur,2);
                } else if ($taxeTaux == 10) {
					$total_ht_tva_10 += round($taxeValeur,2);
				}  else if ($taxeTaux == 5.5) {
					$total_ht_tva_55 += round($taxeValeur,2);
				}

				if (!isset($tvas_facture[(string)$taxeTaux])) {
					$tvas_facture[(string)$taxeTaux] = 0;
				}
				if ($taxeType == 0) {
					$tvaFrais = $taxeValeur * ($taxeTaux/100);
				} else {
					$tvaFrais = ($ht * ($taxeValeur / 100)) * ($taxeTaux/100);
				}
				$montant_tva+=$tvaFrais;
				$tvas_facture[(string)$taxeTaux]+= $tvaFrais;
			}



			if ($interbev > 0) {

				$total_ht_tva_55+=round($interbev,2);


				$tvaInterbev = round($interbev * 0.055,2);

				if (!isset($tvas_facture['5.5'])) {
					$tvas_facture['5.5'] = 0.0;
				}
				$tvas_facture['5.5']+=$tvaInterbev;
			}


			foreach ($tvas_facture as $taux => $tvadue) {
				$tva+=$tvadue;
			}

			// Boucle sur les lignes de facture pour attribuer au compte concerné
			$query_liste_lignes2 = 'SELECT ROUND(`taux_tva`*100,2) AS taux_tva, `prix_vente` FROM `pev_marges_factures` WHERE `id_produit` <> 0 AND `id_facture` = ' . (int)$fact['id']  . ' ';
			$query8 = $cnx->prepare($query_liste_lignes2);
			$query8->execute();

			foreach ($query8->fetchAll(PDO::FETCH_ASSOC) as $donnees8) {

				$taux_tva_ligne = isset($donnees8['taux_tva']) ? floatval($donnees8['taux_tva']) : 0.0;
				$prix_vente_ligne = isset($donnees8['prix_vente']) ? floatval($donnees8['prix_vente']) : 0.0;

				// SI pays européen

				if (in_array(intval($fact['id_pays']), $ids_pays_ue)) {
					$total_ht_intracom += round($prix_vente_ligne,2);

					$tva = 0;
					$interbev = 0;

					// SInon SI Export : pas dans les pays #32 (EU FRANCE et FRANCE)
				} else if (!in_array(intval($fact['id_pays']), $paysFrance)) {
					$total_ht_export+=round($prix_vente_ligne,2);
					$tva = 0;
					$interbev = 0;
					// Sinon c'est qu'on a de la TVA
				} else {

					if ($taux_tva_ligne == 20) {
						$total_ht_tva_20+= round($prix_vente_ligne,2);
					} else if ($taux_tva_ligne == 10) {
						$total_ht_tva_10+=round($prix_vente_ligne,2);
					} else {
						$total_ht_tva_55+=round($prix_vente_ligne,2);
					}
				}
			}


            $total_ht = $total_ht_intracom + $total_ht_export + $total_ht_tva_55 + $total_ht_tva_20 + $total_ht_tva_10;

            $totalFloat = $total_ht;
            if ($total_ht_tva_55 != 0  && isset($tvas_facture['5.5']) && floatval($tvas_facture['5.5']) != 0) {
                $totalFloat = $totalFloat + $tvas_facture['5.5'];
				$total_general_base55+=$total_ht_tva_55;
				$total_general_tva55+= $tvas_facture['5.5'];
            }
            if ($total_ht_tva_20 != 0  && isset($tvas_facture['20']) && floatval($tvas_facture['20']) != 0) {
                $totalFloat = $totalFloat + $tvas_facture['20'];
				$total_general_base20+= $total_ht_tva_20;
				$total_general_tva20+= $tvas_facture['20'];
            }
			$total_general_base_intracom+= $total_ht_intracom;
			$total_general_base_export+= $total_ht_export;
			$total_general_ht+= $total_ht;
			$total_general_ttc+=$totalFloat;

            $total = number_format($totalFloat,2,'.', ' ');

            $num_facture = trim(strtoupper($fact['num_facture']));


			$ht_tva_55 = $total_ht_tva_55 != 0 ? number_format($total_ht_tva_55,2,'.', ' ') : '';
			$ht_tva_20 = $total_ht_tva_20 != 0 ? number_format($total_ht_tva_20,2,'.', ' ') : '';

			$ht_intracom = $total_ht_intracom != 0 ? number_format($total_ht_intracom,2,'.', ' ') : '';
			$ht_export = $total_ht_export != 0 ? number_format($total_ht_export,2,'.', ' ') : '';

            $tva55 = $total_ht_tva_55 != 0 && isset($tvas_facture['5.5']) && floatval($tvas_facture['5.5']) != 0 ? $tvas_facture['5.5'] : '';
            $tva20 = $total_ht_tva_20 != 0 && isset($tvas_facture['20']) && floatval($tvas_facture['20']) != 0 ? $tvas_facture['20'] : '';

			$tvaTotale = number_format(floatval($tva55) + floatval($tva20),2,'.', ' ');




			$html.= '
            <tr>
                <td class="w8">'.$num_facture.'</td>
                <td class="w10">'.strtoupper($fact['codeclt']).'</td>
                <td class="w24">'.strtoupper($fact['nomclt']).'</td>
                <td class="w10 text-center">'.strtoupper($fact['datemvt']).'</td>
                <td class="w6 text-right">'.$ht_tva_55.'</td>
                <td class="w6 text-right">'.$ht_tva_20.'</td>
                <td class="w6 text-right">'.$ht_intracom.'</td>
                <td class="w6 text-right">'.$ht_export.'</td>
                <td class="w8 text-right">'.number_format($total_ht,2,'.', ' ').'</td>
                <td class="w6 text-right">'.$tvaTotale.'</td>
                <td class="w10 text-right">'.$total.'</td>
            </tr>
      ';





		} // FIN boucle
    }
	$html.= '</table>';


	$total_general_tva = $total_general_tva55+$total_general_tva20;

	$html.= '<table class="table compta mt-15">
<tr>
<th colspan="8" class="w100">Total général</th>
</tr>
<tr>
<td class="w52">'.count($resultats).' factures</td>
<td class="w6 text-right">'.number_format($total_general_base55,2,'.', ' ').'</td>
<td class="w6 text-right">'.number_format($total_general_base20,2,'.', ' ').'</td>
<td class="w6 text-right">'.number_format($total_general_base_intracom,2,'.', ' ').'</td>
<td class="w6 text-right">'.number_format($total_general_base_export,2,'.', ' ').'</td>
<td class="w8 text-right">'.number_format($total_general_ht,2,'.', ' ').'</td>
<td class="w6 text-right">'.number_format($total_general_tva,2,'.', ' ').'</td>
<td class="w10 text-right">'.number_format($total_general_ttc,2,'.', ' ').'</td>
</tr>

';
	$html.= '</table>';
    return $html;
}





if ($show_debug) { ?>
    <p>Fichier "<?php echo $nom_fichier; ?>" généré avec succès</p>
<?php }


// Envoi par e-mail

echo $show_debug ? '<p>Envoi par mail à '.$email_to.'... ' : '';

$titre = "Journal des ventes Profil Export";
$contenu = "Ci-joint le journal des ventes des dernières factures pour PROFIL EXPORT.<br>Ceci est un mail automatique, ne pas y répondre directement.";
$fichier =  $chemin.$nom_fichier;



if(envoiMail([$email_to], $email_from, $titre, utf8_decode($contenu), 0, [], [$fichier])){  //Envoi du mail
	echo $show_debug ? 'Réussi :)</p>' : '';
	if (!$show_debug) {
		$logsManager = new LogManager($cnx);
		$log = new Log();
		$log->setLog_type('success');
		$log->setLog_texte("[CRON] - Envoi du journal des ventes à ".$email_to." réussi.");
		$logsManager->saveLog($log);
	}
} else {
	echo $show_debug ? 'ECHEC de l\'envoi ! :(</p>' : '';
	if (!$show_debug) {
		envoiMail(['ppactol@boostervente.com'], $email_from, "ERREUR CRON IPREX", utf8_decode("[CRON] - Echec de l'Envoi du journal des ventes à ".$email_to." !"));
	}
}

if ($show_debug) { ?>
	</body>
</html>
<?php }
