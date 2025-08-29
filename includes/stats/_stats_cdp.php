<?php
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

$arrondir = false;
$deci = $arrondir ? 0 : 2;

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

include('_stats_cdp_form.php');
include('_stats_cdp_tables.php');