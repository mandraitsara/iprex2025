<?php

ini_set('display_errors',1);
require_once dirname( __FILE__ ).'/../scripts/php/config.cli.php';
require_once dirname( __FILE__ ).'/../class/Cron.class.php';
require_once dirname( __FILE__ ).'/../class/CronsManager.class.php';
require_once dirname( __FILE__ ).'/../class/Log.class.php';
require_once dirname( __FILE__ ).'/../class/LogManager.class.php';
require_once dirname( __FILE__ ).'/../class/Config.class.php';
require_once dirname( __FILE__ ).'/../class/ConfigManager.class.php';
require_once dirname( __FILE__ ).'/../class/OrderDetailPrestashop.class.php';
require_once dirname( __FILE__ ).'/../class/OrderPrestashop.class.php';
require_once dirname( __FILE__ ).'/../class/OrdersPrestashopManager.class.php';

$debug = isset($_REQUEST['debug']);
$id_shop = $_REQUEST['idshop'];

echo '<br />';
var_dump($id_shop);
echo '<br />';

$configManager = new ConfigManager($cnx);
$email_from = 'contact@profilexport.fr';
$web_url = $configManager->getConfig('web_url');
$web_api = $configManager->getConfig('web_api');

echo '<br />';
var_dump($web_url);
echo '<br />';

echo '<br />';
var_dump($web_api);
echo '<br />';

if (!$web_url instanceof Config) {
	exit('ERREUR WEB URL NON CONFIGUREE !');
}
if (!$web_api instanceof Config) {
	exit('ERREUR WEB API KEY NON CONFIGUREE !');
}
$clef = $web_api->getValeur();

echo '<br />';
var_dump($clef);
echo '<br />';

if ($id_shop == 2) {
	// https://www.steakaloustik.com/
	$url = 'https://www.steakaloustik.com/';
} else {
	// https://www.steakapapa.com/
	$url = $web_url->getValeur();
}

$cronsManager = new CronsManager($cnx);
$cron = $cronsManager->getCronByFileName(basename(__FILE__));

echo '<br />';
var_dump($cron);
echo '<br />';

if (!$cron instanceof Cron) { exit('Non déclaré en BSDD'); }
if (!$cron->isActif()) { exit('Inactif'); }
$cron->setExecution(date('Y-m-d H:i:s'));
$cronsManager->saveCron($cron);

$dest = ['ppactol@boostervente.com'];
$from = 'contact@profilexport.fr';
$titre = 'Erreur webservice Prestashop';
$logsManager = new LogManager($cnx);

if ($debug) { ?>
	<!doctype html>
	<html lang="en">
	<head>
	<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<title>IPREX CRON DEBUG</title>
		<style>
			body {
				background: #333;
				color: #fff;
				font-family: "Courier New", Courier, monospace;
				font-size: .8em;
			}
		</style>
	</head>
	<body>
	<pre style="font-size: 1.2em;color:lawngreen">######################################
  _____ _____  _____  ________   __
 |_   _|  __ \|  __ \|  ____\ \ / /
   | | | |__) | |__) | |__   \ V /
   | | |  ___/|  _  /|  __|   > <
  _| |_| |    | | \ \| |____ / . \
 |_____|_|    |_|  \_\______/_/ \_\

######################################
 > Console debug CRON
 > CWF Framework
 @ Cédric Bouillon 2021-2022
######################################</pre>
<?php }
$finHtml = '</body></html>';


// https://devdocs.prestashop.com/1.7/webservice/resources/

try {
	require_once('PSWebServiceLibrary.php');
	$webService = new PrestaShopWebservice($url, $clef, false);

	$erreurs = [];
	$ok 	 = [];

	// On récupère les commandes de moins d'un mois afin de ne pas récupérer trop de données inutilement
	$date_min = date('Y-m-d', strtotime('-1 month'));
	$date_max = date('Y-m-d', strtotime('+1 year'));

	$xmlOrders = $webService->get([
		'resource' => 'orders',
		'date' => 1,
		'filter[invoice_date]' => '['.$date_min.','.$date_max.']',
		'filter[current_state]' => '[2]'
	]);
	$orders = $xmlOrders->orders->children();
    
	echo '<br />';
	var_dump($orders);
	echo '<br />';
	echo '<br />';

	// Aucune commande récente
	if (empty($orders)) {
		$log = new Log();
		$log->setLog_type('info');
		$log->setLog_texte("[CRON] - Webservice Prestashop : aucune commande à importer");
		$logsManager->saveLog($log);
		echo $debug ? '> Aucune commande à importer.'.$finHtml : '';
		exit;
	}

	// On récupére les ID_order en BDD déjà importés côté IPREX

	$ordersPrestashopManager = new OrdersPrestashopManager($cnx);
	$id_orders_deja = $ordersPrestashopManager->getIdsOrdersPrestashop($date_min);

	$commandes = []; // Objets Iprex
	$commandesDetails = []; // Objets Iprex

	// On boucle sur les commandes trouvées
	$t = 0;
	foreach ($orders as $donnees) {
		$champs = $donnees->attributes();
		$id_order = intval($champs['id']);

		// On ne prends pas en compte les commandes déjà importées
		if (in_array($id_order, $id_orders_deja)) {
			continue;
		}
		$t++;

		$xmlOrder = $webService->get([
			'resource' => 'orders',
			'id' => $id_order
		]);

		$commande = new OrderPrestashop();

		$order 					= $xmlOrder->order->children();
		$id_adresse_facturation = (int)$order->id_address_invoice;
		$id_transporteur = (int)$order->id_carrier;
		// Adresse (facturation, on se fout de la livraison ici)
		$xmlAdresse = $webService->get([
			'resource' => 'addresses',
			'id' => $id_adresse_facturation
		]);
		$adresseDonnees = $xmlAdresse->address->children();
		$adresse = (string)$adresseDonnees->address1;
		$adresse.= (string)$adresseDonnees->address2 != '' ? ' '.$adresseDonnees->address2 : '';
		$adresse.= ' '.$adresseDonnees->postcode;
		$adresse.= ' '.$adresseDonnees->city;

		// Transporteur (nom)
		$xmlTrans = $webService->get([
			'resource' => 'carriers',
			'id' => $id_transporteur
		]);
		$transDonnees = $xmlTrans->carrier->children();

		$commande->setId_order($id_order);
		$commande->setReference((string)$order->reference);
		$commande->setReductions_ht((float)$order->total_discounts_tax_excl);
		$commande->setReductions_ttc((float)$order->total_discounts_tax_incl);
		$commande->setTotal_ht((float)$order->total_paid_tax_excl);
		$commande->setTotal_ttc((float)$order->total_paid_tax_incl);
		$commande->setTotal_produits_ht((float)$order->total_products);
		$commande->setTotal_produits_ttc((float)$order->total_products_wt);
		$commande->setLivraison_ht((float)$order->total_shipping_tax_excl);
		$commande->setLivraison_ttc((float)$order->total_shipping_tax_incl);
		$commande->setDate_facture((string)$order->invoice_date);
		$commande->setId_adresse($id_adresse_facturation);
		$commande->setId_client((int)$order->id_customer);
		$commande->setId_transporteur($id_transporteur);
		$commande->setAdresse($adresse);
		$commande->setNom_client($adresseDonnees->firstname . ' ' . $adresseDonnees->lastname);
		$commande->setTransporteur((string)$transDonnees->name);
		$commande->setDate_import(date('Y-m-d H:i:s'));

		$commandes[] = $commande;

		// Produits
		$order_rows = $order->associations->order_rows;
		foreach ($order_rows->order_row as $pdt) {

			$commandeDetail = new OrderDetailPrestashop();
			$commandeDetail->setId_order($id_order);
			$commandeDetail->setQte((float)$pdt->product_quantity);
			$commandeDetail->setNom((string)$pdt->product_name);
			$commandeDetail->setRef((string)$pdt->product_reference);
			$commandeDetail->setPu_ht((float)$pdt->unit_price_tax_excl);
			$commandeDetail->setPu_ttc((float)$pdt->unit_price_tax_incl);
			$commandesDetails[] = $commandeDetail;

		} // FIN boucle sur les produits

	} // FIN boucle sur les factures

	foreach ($commandes as $cmd) {
		if ($ordersPrestashopManager->saveOrdersPrestashop($cmd)) {
			$txt = 'Enregistrement IPREX de la commande PrestaShop ID order #'.$cmd->getId_order();


			$log = new Log();
			$log->setLog_type('success');
			$log->setLog_texte("[CRON] - Enregistrement dans IPREX de la commande PrestaShop ID order #".$cmd->getId_order());
			$logsManager->saveLog($log);

			echo $debug ? $txt.'<br>' : '';
			$ok[] = $txt;
		} else {
			$txt = 'Erreur enregistrement IPREX de la commande PrestaShop ID order #'.$cmd->getId_order();
			echo $debug ? $txt.'<br>' : '';
			$erreurs[] = $txt;
		}
	}

	foreach ($commandesDetails as $cmdet) {
		if ($ordersPrestashopManager->saveOrderDetailPrestashop($cmdet)) {
			$txt = 'Enregistrement IPREX ligne de détail Ref '.$cmdet->getRef().' de la commande PrestaShop ID order #'.$cmd->getId_order();
			echo $debug ? $txt.'<br>' : '';
			$ok[] = $txt;
		} else {
			$txt = 'Erreur enregistrement IPREX ligne de détail Ref '.$cmdet->getRef().' de la commande PrestaShop ID order #'.$cmdet->getId_order();
			echo $debug ? $txt.'<br>' : '';
			$erreurs[] = $txt;
		}
	}

	if (!empty($erreurs)) {
		foreach ($erreurs as $err) {
			$log = new Log();
			$log->setLog_type('danger');
			$log->setLog_texte($err);
			$logsManager->saveLog($log);
		}
		envoiMail(['ppactol@boostervente.com'], $email_from, "ERREUR CRON IPREX", utf8_decode("[Webservice Prestashop] - ".$err));
	}

	if (!empty($ok)) {
		foreach ($ok as $o) {
			$log = new Log();
			$log->setLog_type('success');
			$log->setLog_texte($o);
			$logsManager->saveLog($log);
		}
	}

	echo $debug && $t == 0 ? '> Aucune nouvelle commande à importer.' : '';
	echo $debug ? '<hr>> Traitement terminé.'.$finHtml : '';

	// génération des factures iprex + pdf a part dans une autre cron

} catch(PrestaShopWebserviceException $ex) {
	$log = new Log();
	$log->setLog_type('danger');
	$log->setLog_texte($titre. ' : '.$ex->getMessage());
	$logsManager->saveLog();
	Outils::envoiMail($dest, $from, $titre, $ex->getMessage());
	exit;
}
