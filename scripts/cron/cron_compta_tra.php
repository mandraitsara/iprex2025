<?php
ini_set('display_errors' ,1);
/* ******************************************************************************
 *  TACHE CRON
 *  Génère un fichier TRA et l'envoie à la compta
 *
 *  (c) Cédric Bouillon 2021
 *  INTERSED
 ****************************************************************************** */
require_once dirname( __FILE__ ).'/../php/config.cli.php';
require_once dirname( __FILE__ ).'/../../class/Cron.class.php';
require_once dirname( __FILE__ ).'/../../class/CronsManager.class.php';
require_once dirname( __FILE__ ).'/../../class/Log.class.php';
require_once dirname( __FILE__ ).'/../../class/LogManager.class.php';

$cronsManager = new CronsManager($cnx);
$cron = $cronsManager->getCronByFileName(basename(__FILE__));
if (!$cron instanceof Cron) { exit('Non déclaré en BSDD'); }
if (!$cron->isActif()) { exit('Inactif'); }
$cron->setExecution(date('Y-m-d H:i:s'));
$cronsManager->saveCron($cron);

$test = true;

/* ******************************************************************************
 *  PARAMETRES
 ****************************************************************************** */
$email_to = $test ? 'ppactol@boostervente.com' :  'anneMarie.GRAVOTTA@carpentrasexpertscomptables.fr';

$email_from = 'info@profilexport.fr';
$nom_cron	= 'Génération et envoi du fichier TRA pour la comptabilité';
$chemin = str_replace('scripts/cron', 'temp',__DIR__).'/';
$nom_fichier = "export.csv";

/* ******************************************************************************
 *  FIN PARAMETRES
 ****************************************************************************** */
$show_debug = isset($_REQUEST['debug']);


function addEspaces($txt, $longueur) {
    $txt =  enleveAccents((string)$txt);
    $ltxt = strlen($txt);
    if ($longueur <= $ltxt) { return ""; }
    $chaine = "";
    for ($i = $ltxt; $i < $longueur; $i++) {
		$chaine.=" ";
    }
    return $chaine;

} // FIN fct

function cutTexte($txt, $longueur) {
	$txt =  enleveAccents($txt);

	if (strlen($txt) <= $longueur) { return $txt; }

	return substr($txt,0,$longueur);

} // FIN FCT

function enleveAccents($chaine) {
	$string= strtr($chaine,
		"ÀÁÂàÄÅàáâàäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏ" .
		"ìíîïÙÚÛÜùúûüÿÑñ",
		"aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn");
	return $string;
} // FIN fct

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

// Création/ouverture du fichier
$tra = fopen($chemin.$nom_fichier, 'w');
$separateur = ';';

// Entête "!"
fwrite($tra, "");

$ligne = "journal"              . $separateur;
$ligne.= "dateFacture"          . $separateur;
$ligne.= "noCompte"             . $separateur;
$ligne.= "noFacture"            . $separateur;
$ligne.= "libelle"              . $separateur;
$ligne.= "dateEcheance"         . $separateur;
$ligne.= "DC"                   . $separateur;
$ligne.= "montant"              . $separateur;
$ligne.= "tauxTVA"              . $separateur;
$ligne.= "codePays"             . $separateur;
$ligne.= "devise"               . $separateur;
$ligne.= "soldeSigne"           . $separateur;

fwrite($tra, $ligne."\n");


// Factures
// Récupération de toutes les factures à transmettre
$date_du = date("Y-m-d", strtotime("first day of previous month"));
$date_au = date('Y-m').'-01';

$query_factures = 'SELECT f.`id`,
                        f.`num_facture`,
                        DATE_FORMAT(f.`date`, "%d%m%Y") AS datemvt,
                        DATE_FORMAT(DATE_ADD(f.`date`, INTERVAL 1 YEAR), "%d%m%Y") AS dateexp,
                        f.`montant_ht` AS ht,
                        f.`montant_interbev` AS interbev,
                        t.`code_comptable` AS compte,
                        t.`nom` AS nomclt,
						p.`iso`,
						t.`id_groupe` AS groupeclt,
                        IF (a1.`id_pays` IS NOT NULL, a1.`id_pays`, IFNULL(a2.`id_pays`, af.`id_pays`)) AS id_pays
                    FROM `pe_factures` f
                        JOIN `pe_tiers` t ON t.`id` = f.`id_tiers_facturation`
						JOIN `pe_adresses` af ON af.`id_tiers` = f.`id_tiers_facturation`
                        LEFT JOIN `pe_adresses` a1 ON a1.`id_tiers` = f.`id_tiers_livraison` AND a1.`type` = 0
                        LEFT JOIN `pe_adresses` a2 ON a2.`id_tiers` = f.`id_tiers_livraison` AND a2.`type` = 1
						LEFT JOIN `pe_pays` p ON  af.`id_pays` = p.`id`
                    WHERE f.`supprime` = 0 
                      AND f.`date` >= "'.$date_du.'" AND f.`date` < "'.$date_au.'"  ';

// $query_factures.= " AND num_facture IN ('FA2303008','FA2303009') ";

$query_factures.= $test ? " " : '
                      AND (f.`date_compta` IS NULL
                               OR f.`date_compta` = "0000-00-00 00:00:00" 
                               OR f.`date_compta` = "") ';

$query_factures.= ' GROUP BY  f.`id` ORDER BY f.`num_facture` ';

// if ($test){
// 	echo '<br />'.$query_factures;
// }

$query2 = $cnx->prepare($query_factures);
$query2->execute();

$ids_factures = [];
$facts = $query2->fetchAll(PDO::FETCH_ASSOC);

if (empty($facts)) {
    echo $show_debug ? '<p>Aucune facture à traiter...</p></body></html>' : '';
    exit;
}

$ids_pays_ue = [4,2,6,21,18,5,24,7,25,11,33,16,17,9,19,8,10,32];

foreach ($facts as $fact) {

	$ids_factures[] = (int)$fact['id'];


	$query_liste_lignes = 'SELECT DISTINCT `tva` AS taux FROM `pe_facture_lignes` WHERE `tva` != 0 AND `id_facture` = ' . (int)$fact['id'];
	
	// if ($test){
	// 	echo '<br />RQ : sélection des taux de TVA : '.$query_liste_lignes;
	// }

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
		
		// if ($test){
		// 	echo '<br />RQ pour total TVA payée : '.$query_total;
		// }

		$query5= $cnx->prepare($query_total);
		$query5->execute();
		$donnees2 = $query5->fetch(PDO::FETCH_ASSOC);
		$montant = $donnees2 && isset($donnees2['total']) ? floatval($donnees2['total']) : 0;
		if ($montant == 0) { continue; }

		$tvas_facture[(string)$taux] = round($montant,2);

	} // FIN boucle taux

    // Frais additionnels
	$query_liste = "SELECT ff.`id`, ff.`nom`, ff.`type`, ff.`id_taxe`, ff.`valeur`, ff.`id_facture`, IF (t.`id` IS NOT NULL, t.`nom`, '') AS taxe_nom,  IF (t.`id` IS NOT NULL, t.`taux`, '') AS taxe_taux
							FROM `pe_facture_frais` ff
								LEFT JOIN `pe_taxes` t ON t.`id` = ff.`id_taxe`
						WHERE ff.`id_facture` =  ".(int)$fact['id']." 
							ORDER BY ff.`nom` DESC";

	// if ($test){
	// 	echo '<br />RQ Frais additionnems : '.$query_liste;
	// }
	
	$query7 = $cnx->prepare($query_liste);
	$query7->execute();

	$ht     = floatval($fact['ht']) < 0 ? floatval($fact['ht']) * -1 : floatval($fact['ht']);
	$interbev = floatval($fact['interbev']) < 0 ? floatval($fact['interbev']) * -1 : floatval($fact['interbev']);

    $total_ht_frais_france      = 0.0;
    $total_ht_frais_intracom    = 0.0;
    $total_ht_frais_export      = 0.0;

	foreach ($query7->fetchAll(PDO::FETCH_ASSOC) as $donnees7) {
        $taxeTaux = isset($donnees7['taxe_taux']) ? floatval($donnees7['taxe_taux']) : 0;
        $taxeType = isset($donnees7['type']) ? intval($donnees7['type']) : 0;
        $taxeValeur = isset($donnees7['valeur']) ? floatval($donnees7['valeur']) : 0;

		// SI pays européen
		if (in_array(intval($fact['id_pays']), $ids_pays_ue)) {
			$total_ht_frais_intracom+=$taxeValeur;
		// SInon SI Export : pas dans les pays #32 (EU FRANCE et FRANCE)
    	} else if (!in_array(intval($fact['id_pays']), [32, 1])) {
			$total_ht_frais_export+=$taxeValeur;
		// Sinon c'est qu'on a de la TVA
	    } else {
			$total_ht_frais_france+=$taxeValeur;
        }

		if ($taxeTaux == 0) { continue; }
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

		$tvaInterbev = round($interbev * 0.055,2);
		if (!isset($tvas_facture['5.5'])) {
			$tvas_facture['5.5'] = 0.0;
		}
		$tvas_facture['5.5']+=$tvaInterbev;
	}

    foreach ($tvas_facture as $taux => $tvadue) {
		$tva+=$tvadue;
    }

	$total_ht_tva_20 	= 0.0;
	$total_ht_tva_10 	= 0.0;
	$total_ht_tva_55 	= 0.0;
	$total_ht_intracom 	= 0.0;
	$total_ht_export 	= 0.0;

    // Boucle sur les lignes de facture pour attribuer au compte concerné
    $query_liste_lignes2 = 'SELECT ROUND(`taux_tva`*100,2) AS taux_tva, `prix_vente` FROM `pev_marges_factures` WHERE `id_produit` <> 0 AND `id_facture` = ' . (int)$fact['id']  . ' ';
	$query8 = $cnx->prepare($query_liste_lignes2);
	$query8->execute();

	// if ($test){
	// 	echo '<br />'.$query_liste_lignes2;
	// }

	foreach ($query8->fetchAll(PDO::FETCH_ASSOC) as $donnees8) {

        $taux_tva_ligne = isset($donnees8['taux_tva']) ? floatval($donnees8['taux_tva']) : 0.0;
        $prix_vente_ligne = isset($donnees8['prix_vente']) ? floatval($donnees8['prix_vente']) : 0.0;

		// if ($test){
		// 	echo '<br />Taux TVA ligne : '.$taux_tva_ligne;
		// }

		// SI pays européen
		// if ($test){
		// 	echo '<br />id_pays : '.$fact['id_pays'];
		// }

		if (in_array(intval($fact['id_pays']), $ids_pays_ue)) {
			$total_ht_intracom += round($prix_vente_ligne,2);

			$tva = 0;
			$interbev = 0;

		// Sinon Si Export : pas dans les pays #32 (EU FRANCE et FRANCE)
		} else if (!in_array(intval($fact['id_pays']), [32, 1])) {
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


    // Pour chaque facture on a 3 lignes de compte : 411 (Total), 44571400 (TVA) et 70600100 (Résultat net)

	// Date par défaut en cas de problème / jjmmaaaa (cf doc Cegid)
	$dateMvt = strlen($fact['datemvt']) == 8 ? $fact['datemvt'] : "01011970"; // Valeur par défaut CEGID pour ce champ
	$dateExp = strlen($fact['dateexp']) == 8 ? $fact['dateexp'] : "01011900"; // Valeur par défaut CEGID pour ce champ

	// Valeurs

	//code pays

	$code_pays = $fact['iso'];




	$tvaInHt = $tva < 0 ? $tva*-1 : $tva;

    $sens1   = floatval($fact['ht']) > 0 ? "D" : "C";   // Pour les lignes "Total"
    $sens2   = floatval($fact['ht']) < 0 ? "D" : "C";   // Pour les lignes "TVA" / "Net"
	
	$soldeSigne = $sens1 == "D" ? 'positif' : 'negatif';
    $htTxt = number_format($ht, 2, ',', '');
    $tvaTxt = number_format($tvaInHt, 2, ',', '');


	$total = 0;
    if ($tva != 0) {
        $total = $total + $tvaInHt;
    }
	if ($total_ht_tva_20 != 0) {
		if ($total_ht_tva_20 < 0) { $total = $total + $total_ht_tva_20*-1;; }
		else { $total = $total + $total_ht_tva_20; }
	}
	if ($total_ht_tva_10 != 0) {
		if ($total_ht_tva_10 < 0) { $total = $total + $total_ht_tva_10*-1;; }
		else { $total = $total + $total_ht_tva_10; }
	}

	if ($total_ht_tva_55 != 0) {
		if ($total_ht_tva_55 < 0) { $total = $total + $total_ht_tva_55*-1;; }
        else { $total = $total + $total_ht_tva_55; }
	}
	if ($total_ht_intracom != 0) {
		if ($total_ht_intracom < 0) { $total = $total + $total_ht_intracom*-1;; }
		else { $total = $total + $total_ht_intracom; }
	}
	if ($total_ht_export != 0) {
		if ($total_ht_export < 0) { $total = $total + $total_ht_export*-1;; }
		else { $total = $total + $total_ht_export; }
	}
	if ($interbev != 0) {
		if ($interbev < 0) { $total = $total + $interbev*-1;; }
		else { $total = $total + $interbev; }
	}
	if ($total_ht_frais_france != 0) {
		if ($total_ht_frais_france < 0) { $total = $total + $total_ht_frais_france*-1;; }
		else { $total = $total + $total_ht_frais_france; }
	}
	if ($total_ht_frais_intracom != 0) {
		if ($total_ht_frais_intracom < 0) { $total = $total + $total_ht_frais_intracom*-1;; }
		else { $total = $total + $total_ht_frais_intracom; }
	}
	if ($total_ht_frais_export != 0) {
		if ($total_ht_frais_export < 0) { $total = $total + $total_ht_frais_export*-1;; }
		else { $total = $total + $total_ht_frais_export; }
	}



    $totalTxt = number_format($total, 2, ',', '');
    $compteAux = trim(strtoupper($fact['compte']));
    if (substr($compteAux, 0, 1) === 'C') {
    	$compteAux = substr($compteAux, 1);
	}

	$num_facture = trim(strtoupper($fact['num_facture']));

	$libMvt = trim($fact['nomclt']). ' ';
	$groupe = $fact['groupeclt'];
	$libMvt = cutTexte(trim($libMvt), 35);

    // TOTAL (411)
    $ligne = "VT"                   . $separateur;
    $ligne.= $dateMvt               . $separateur;
    $ligne.= "411" . $compteAux     . $separateur;
    $ligne.= $num_facture           . $separateur;
    $ligne.= $libMvt                . $separateur;
    $ligne.= $dateExp               . $separateur;
    $ligne.= $sens1                 . $separateur;
    $ligne.= $totalTxt              . $separateur;
    $ligne.= ""                     . $separateur;
	$ligne.= $code_pays             . $separateur;	
    $ligne.= "EUR"                  . $separateur;
	$ligne.= $soldeSigne            . $separateur;

	

	fwrite($tra, $ligne."\n");

	// TVA
    if ($tva != 0) {

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
        $ligne.= "44570000"             . $separateur;
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $tvaTxt                . $separateur;
        $ligne.= ""                     . $separateur;		
        $ligne.= "EUR"                  . $separateur;

		fwrite($tra, $ligne."\n");

    }

    // 08/11/2021 - Nouveau code comptable envoyé par Mme Rostang, les lignes doivent distinguer les comptes concernés + l'interbev à la fin
    //  70700300	VENTES MARCHANDISES 20%
    if ($total_ht_tva_20 != 0) {
		if ($total_ht_tva_20 < 0) { $total_ht_tva_20*=-1; }
		$htTxt = number_format($total_ht_tva_20, 2, ',', '');

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
        $ligne.= "70700300"             . $separateur;
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= "20"                   . $separateur;
        $ligne.= "EUR"                  . $separateur;

		fwrite($tra, $ligne."\n");
    
    }

	//  70700200	VENTES MARCHANDISES 10%
	if ($total_ht_tva_10 != 0) {
		if ($total_ht_tva_10 < 0) { $total_ht_tva_10*=-1; }
        $htTxt = number_format($total_ht_tva_10, 2, ',', '');
        
        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
        $ligne.= "70700200"             . $separateur;
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= "10 %"                   . $separateur;
        $ligne.= "EUR"                  . $separateur;

		fwrite($tra, $ligne."\n");

	}

	//  70700000	VENTES MARCHANDISES 5.5%
	if ($total_ht_tva_55 != 0) {
        if ($total_ht_tva_55 < 0) { $total_ht_tva_55*=-1; }
        $htTxt = number_format($total_ht_tva_55, 2, ',', '');

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
		if ($groupe == 2){
			$ligne.= "70700001" . $separateur;
		} else {
			$ligne.= "70700000" . $separateur;
		}
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= "5.5 %"                . $separateur;
        $ligne.= "EUR"                  . $separateur;

		fwrite($tra, $ligne."\n");

	}

 	//  70701000	VENTES MARCHANDISES INTRACOM
	if ($total_ht_intracom != 0) {
		if ($total_ht_intracom < 0) { $total_ht_intracom*=-1; }
		$htTxt = number_format($total_ht_intracom, 2, ',', '');

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
        $ligne.= "70701000"             . $separateur;
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= "10 %"                   . $separateur;
        $ligne.= "EUR"                  . $separateur;
        
        fwrite($tra, $ligne."\n");

	}

	//  70710000	VENTES MARCHANDISES EXPORT
	if ($total_ht_export != 0) {
		if ($total_ht_export < 0) { $total_ht_export*=-1; }
		$htTxt = number_format($total_ht_export, 2, ',', '');

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
        $ligne.= "70710000"             . $separateur;
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= "EUR"                  . $separateur;
        
        fwrite($tra, $ligne."\n");

	}

    // 46100000     C.I.E INTERBEV
	if ($interbev != 0) {
		if ($interbev < 0) { $interbev*=-1; }
		$htTxt = number_format($interbev, 2, ',', '');

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
        $ligne.= "70710000"             . $separateur;
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= "EUR"                  . $separateur;
        
        fwrite($tra, $ligne."\n");

	}

    // 70810000 PRESTATIONS TRANSPORT FRANCE
    if ($total_ht_frais_france != 0) {
		if ($total_ht_frais_france < 0) { $total_ht_frais_france*=-1; }
		$htTxt = number_format($total_ht_frais_france, 2, ',', '');

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
		if ($groupe == 2){
			$ligne.= "70810001" . $separateur;
		} else {
			$ligne.= "70810000" . $separateur;
		}
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= "20 %"                   . $separateur;
        $ligne.= "EUR"                  . $separateur;
        
        fwrite($tra, $ligne."\n");

    }

	// 70811000 PRESTATIONS TRANSPORT INTRACOMM
	if ($total_ht_frais_intracom != 0) {
		if ($total_ht_frais_intracom < 0) { $total_ht_frais_intracom*=-1; }
        $htTxt = number_format($total_ht_frais_intracom, 2, ',', '');

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
        $ligne.= "70811000"             . $separateur;
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= "EUR"                  . $separateur;
        
        fwrite($tra, $ligne."\n");

	}

	// 70811100 PRESTATIONS TRANSPORT EXPORT
	if ($total_ht_frais_export != 0) {
		if ($total_ht_frais_export < 0) { $total_ht_frais_export*=-1; }
		$htTxt = number_format($total_ht_frais_export, 2, ',', '');

        $ligne = "VT"                   . $separateur;
        $ligne.= $dateMvt               . $separateur;
        $ligne.= "70811100"             . $separateur;
        $ligne.= $num_facture           . $separateur;
        $ligne.= $libMvt                . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= $sens2                 . $separateur;
        $ligne.= $htTxt                 . $separateur;
        $ligne.= ""                     . $separateur;
        $ligne.= "EUR"                  . $separateur;
        
        fwrite($tra, $ligne."\n");

	}


} // FIN boucle factures

// Fermeture du fichier
fclose($tra);

if ($show_debug) { ?>

    <p>Fichier "<?php echo $nom_fichier; ?>" généré avec succès</p>

<?php }


// Envoi par e-mail

echo $show_debug ? '<p>Envoi par mail à '.$email_to.'... ' : '';

$titre   = "Factures PROFIL EXPORT";
$titre2  = "Factures PROFIL EXPORT - COPIE";
$contenu = "Ci-joint le fichier TRA des dernières factures pour PROFIL EXPORT.<br>Ceci est un mail automatique, ne pas y répondre directement.";
$fichier =  $chemin.$nom_fichier;

$logsManager = new LogManager($cnx);
// Envoi pour vérification sans traitement
envoiMail(['ppactol@boostervente.com'], $email_from, $titre2, utf8_decode($contenu), 0, [], [$fichier]);

// Envoi au destinataire
if(envoiMail([$email_to], $email_from, $titre, utf8_decode($contenu), 0, [], [$fichier])){  //Envoi du mail
	echo $show_debug ? 'Réussi :)</p>' : '';
	if (!$show_debug) {

		$log = new Log();
		$log->setLog_type('success');
		$log->setLog_texte("[CRON] - Envoi du fichier TRA à ".$email_to." réussi.");
		$logsManager->saveLog($log);
	}

	// Update des factures avec la date pour le champ "date_compta" si le mail a bien été envoyé
    foreach ($ids_factures as $id) {

		$query_upd = 'UPDATE `pe_factures` SET `date_compta` = NOW() WHERE `id` = ' . (int)$id;
		$query3 = $cnx->prepare($query_upd);
		$query3->execute();

    } // FIN boucle
    $p = count($ids_factures) > 1 ? 's' : '';
	echo $show_debug ? '<p>'.count($ids_factures).' facture'.$p.' enregistrée'.$p.' comme transmie'.$p.' à la compta en date du jour.</p>' : '';
	$log = new Log();
	$log->setLog_type('success');
	$log->setLog_texte('[CRON] - '.count($ids_factures).' facture'.$p.' enregistrée'.$p.' comme transmie'.$p.' à la compta en date du jour.');
	$logsManager->saveLog($log);


} else {
	echo $show_debug ? 'ECHEC ! :(</p><p>Factures non définies comme envoyées en BDD mais fichier généré dans /temp/</p>' : '';
	if (!$show_debug) {
		envoiMail(['ppactol@boostervente.com'], $email_from, "ERREUR CRON IPREX", utf8_decode("[CRON] - Echec de l'Envoi du fichier TRA à ".$email_to." !<br>Factures non définies comme envoyées en BDD mais fichier généré dans /temp/"));
	}
}

if ($show_debug) { ?>
	</body>
</html>
<?php }
