<?php
ini_set('display_errors' ,1);
/* ******************************************************************************
 *  TACHE CRON
 *  Génère un fichier pour la déclaration d'échanges de biens (DEB) et l'envoie à la compta
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

// Traite les factures du mois précédent le mois d'exécution, mais paramètrage possible en get
$mois = isset($_REQUEST['mois']) ? intval($_REQUEST['mois']) : 0;
$annee = isset($_REQUEST['an']) ? intval($_REQUEST['an']) : 0;

if ($mois == 0 || $annee == 0) {
    $mois = date("m",strtotime("-1 month"));
    $annee = date("Y",strtotime("-1 month"));
} else if (strlen((string)$mois) < 2) {
    $mois = '0'.(int)$mois;
} if (strlen((string)$annee) == 2) {
    $annee = '20'.$annee;
}
/* ******************************************************************************
 *  PARAMETRES
 ****************************************************************************** */
$email_to = 'ppactol@boostervente.com'; // DEV
// $email_to = 'valerie.rostang@carpentrasexpertscomptables.fr'; // PROD
$email_from = 'contact@profilexport.fr';
$nom_cron	= 'Génération et envoir du CSV pour la déclaration d\'échanges de biens à la comptabilité';
$chemin = str_replace('scripts/cron', 'temp',__DIR__).'/';
$nom_fichier = "PROFIL_EXPORT_DEB_".$mois."-".$annee.".csv";

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
	<h2><?php echo $nom_cron; ?></h2>
    <h3>Période : <?php echo $mois.'/'.$annee; ?></h3><hr>

<?php }

$fp = fopen($chemin.$nom_fichier, 'w');
$arrayCsv = [];
$arrayCsv[] = ['Ligne', 'Nomenclature produit', 'Pays dest. prov.', 'Regime', 'Valeur', 'Masse nette', 'Unites supplementaires', 'Nature transaction', 'Mode transport', 'Departement', 'Pays d\'origine', 'Numero d\'identification de l\'acquereur C.E.', 'Reference facture'];
//$arrayCsv[] = array_map("utf8_decode",  ['N° ligne', 'Nomenclature produit', 'Pays dest. prov.', 'Régime', 'Valeur marchande', 'Masse nette', 'Unités supplémentaires', 'Nature transaction', 'Mode transport', 'Département', 'Pays d\'origine', 'Numéro d\'identification de l\'acquéreur C.E.', 'Référence facture'] );

// On récupère toutes les factures de cette période expédiés hors france
$query_factures = 'SELECT p.`nomenclature`, y.`iso`, fl.`id_facture`, fl.`id_produit`, t.`tva_intra`, f.`num_facture`
                        FROM `pe_facture_lignes` fl
                            JOIN `pe_factures` f ON f.`id` = fl.`id_facture`
                            LEFT JOIN `pe_produits` p ON p.`id` = fl.`id_produit`
                            LEFT JOIN `pe_adresses` a ON a.`id` = f.`id_adresse_livraison`
                            LEFT JOIN `pe_adresses` a2 ON a2.`id` = f.`id_adresse_facturation`
                            LEFT JOIN `pe_pays` y ON y.`id` = a.`id_pays`
                            LEFT JOIN `pe_pays` y2 ON y2.`id` = a2.`id_pays`
                            LEFT JOIN `pe_tiers` t ON t.`id` = f.`id_tiers_facturation`
                        WHERE f.`supprime` = 0 
                            AND fl.`supprime` = 0 
                            AND (UPPER(y.`iso`) IN ("AT", "BE", "BG", "CY", "CZ", "DE", "FN", "DK", "EE", "ES", "FI", "GB", "GR", "HU", "IE", "IT", "LT", "LU", "LV", "MT", "NL", "PL", "PT", "RO", "SE", "SI", "SK")
                             OR UPPER(y2.`iso`) IN ("AT", "BE", "BG", "CY", "CZ", "DE", "FN", "DK", "EE", "ES", "FI", "GB", "GR", "HU", "IE", "IT", "LT", "LU", "LV", "MT", "NL", "PL", "PT", "RO", "SE", "SI", "SK"))
                            AND f.`montant_ht` > 0            
                            AND MONTH(f.`date`) = '.$mois.'   
                            AND YEAR(f.`date`) = '.$annee.'
                        ORDER BY f.`date`, fl.`id`';
$query = $cnx->prepare($query_factures);
$query->execute();

$lignesFacturesBrutes = $query->fetchAll(PDO::FETCH_ASSOC);
if (empty($lignesFacturesBrutes)) { exit('Aucune ligne de facture'); }

$lignesProduitFacture = [];

// Boucles sur les lignes de factures brutes (il faut les regrouper en produits uniques par facture)
foreach ($lignesFacturesBrutes as $lf) {

    $unicite = $lf['id_facture'].'|'.$lf['id_produit'];
    if (!isset($lignesProduitFacture[$unicite])) {
        $lignesProduitFacture[$unicite] = [];
    }

	if (!isset($lignesProduitFacture[$unicite]['nomenclature'])) {
		$lignesProduitFacture[$unicite]['nomenclature'] = $lf['nomenclature'];
	}

	if (!isset($lignesProduitFacture[$unicite]['iso'])) {
		$lignesProduitFacture[$unicite]['iso'] = $lf['iso'];
	}

	if (!isset($lignesProduitFacture[$unicite]['tva_intra'])) {
		$lignesProduitFacture[$unicite]['tva_intra'] = $lf['tva_intra'];
	}

	if (!isset($lignesProduitFacture[$unicite]['num_facture'])) {
		$lignesProduitFacture[$unicite]['num_facture'] = $lf['num_facture'];
	}

	// Valeurs unies de la vue
    $query_vue = 'SELECT SUM(`prix_vente`) AS valeur , SUM(`poids`) AS masse, SUM(IF(`vendu_piece` = 1, `qte`, 0)) AS unites 
       FROM `pev_marges_factures` WHERE `id_facture` = ' . $lf['id_facture'] . ' AND  `id_produit` = ' . $lf['id_produit'];
	$query2 = $cnx->prepare($query_vue);
	$query2->execute();
	$resVue = $query2->fetch(PDO::FETCH_ASSOC);

	if (!isset($lignesProduitFacture[$unicite]['valeur'])) {
		$lignesProduitFacture[$unicite]['valeur'] = $resVue['valeur'];
	}

	if (!isset($lignesProduitFacture[$unicite]['masse'])) {
		$lignesProduitFacture[$unicite]['masse'] = ceil($resVue['masse']);
	}

	if (!isset($lignesProduitFacture[$unicite]['unites'])) {
		$lignesProduitFacture[$unicite]['unites'] = ceil($resVue['unites']);
	}



} // FIN boucle sur les lignes de factures brutes

/*
 * on boucle sur les factures de la période en export (memes wheres)
 *
 */


$l = 0;
foreach ($lignesProduitFacture as $fact) {
    $l++;
    $nomenclature = $fact['nomenclature'] != '' ? $fact['nomenclature'] : '';
    $pays = $fact['iso'] != '' ? strtoupper($fact['iso']) : '';
    $valeur = (float)$fact['valeur'] > 0 ? number_format(floatval($fact['valeur']),2,',', '') : '';
	$masse = (float)$fact['masse'] > 0 ? number_format(floatval($fact['masse']),3,',', '') : '';
	$unites = (int)$fact['unites'] > 0 ? number_format(intval($fact['unites']),0,',', '') : '';
	$tvaintra = $fact['tva_intra'] != '' ? trim(strtoupper($fact['tva_intra'])) : '';
	$numfacture = $fact['num_facture'] != '' ? trim(strtoupper($fact['num_facture'])) : '';
	$arrayCsv[] = [
	    $l,
		$nomenclature,
        $pays,
        '21',
		$valeur,
        '',
        $masse,
		$unites,
        '11',
        '38',
		'3',
		$tvaintra,
		$numfacture
    ];
}
echo $show_debug ? '<p>Ecriture du CSV dans <code>'.$chemin.$nom_fichier.'</code></p>' : '';
foreach ($arrayCsv as $fields) {
	fputcsv($fp, $fields, ';');
}
fclose($fp);
// Envoi par e-mail

echo $show_debug ? '<p>Envoi par mail à '.$email_to.'... ' : '';

$titre = "Prodouane : Factures Profil Export ".$mois."/".$annee."";
$contenu = "Ci-joint le fichier CSV pour la déclaration d'échanges de bien sur la période ".$mois."/".$annee." chez PROFIL EXPORT.<br>Ceci est un mail automatique, ne pas y répondre directement.";
$fichier =  $chemin.$nom_fichier;


if(envoiMail([$email_to], $email_from, $titre, utf8_decode($contenu), 0, [], [$fichier])){  //Envoi du mail
	echo $show_debug ? 'Réussi :)</p>' : '';
    if (!$show_debug) {
		$logsManager = new LogManager($cnx);
		$log = new Log();
		$log->setLog_type('success');
		$log->setLog_texte("[CRON] - Envoi du fichier DEB sur la période ".$mois."/".$annee." à ".$email_to." réussi.");
		$logsManager->saveLog($log);
    }

} else {
	echo $show_debug ? 'Envoi du mail échoué ! :(</p>' : '';
	if (!$show_debug) {
		envoiMail(['pole-web@intersed.fr'], $email_from, "ERREUR CRON IPREX", utf8_decode("[CRON] - Echec de l'Envoi du fichier DEB sur la période ".$mois."/".$annee." à ".$email_to." !"));
	}
}

if ($show_debug) { ?>
	</body>
</html>
<?php }

function enleveAccents($chaine) {
	$string= strtr($chaine,
		"ÀÁÂàÄÅàáâàäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏ" .
		"ìíîïÙÚÛÜùúûüÿÑñ",
		"aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn");
	return $string;
} // FIN fct
