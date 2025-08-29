<?php
$date_du   = isset($_REQUEST['date_du'])  && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['date_du'])) ? $_REQUEST['date_du'] : '';
$date_au   = isset($_REQUEST['date_au'])  && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['date_au'])) ? $_REQUEST['date_au'] : '';
$mois      = isset($_REQUEST['mois'])     && intval($_REQUEST['mois'])  > 0             ? $_REQUEST['mois']    : '';
$annee     = isset($_REQUEST['annee'])    && intval($_REQUEST['annee']) > 0             ? $_REQUEST['annee']   : '';
$id_client = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
$id_groupe = isset($_REQUEST['id_groupe']) ? intval($_REQUEST['id_groupe']) : 0;

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


$arrondir = false;
$deci = $arrondir ? 0 : 2;

// Frais de fonctionnement sur la période
$ff = $fraisFonctionnementManager->getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee);

$params = [
	'date_du'     => $date_du,
	'date_au'     => $date_au,
	'mois'        => $mois,
	'annee'       => $annee,
	'id_client'   => $id_client,
	'id_groupe'   => $id_groupe
];

?>
<ul class="nav nav-tabs">
	<li class="nav-item"><a class="nav-link <?php echo $onglet == 'cltGeneral' || $onglet == '' ? 'active' : ''; ?>" data-toggle="tab" href="#cltGeneral">Général</a></li>
	<li class="nav-item"><a class="nav-link <?php echo $onglet == 'cltPdts' ? 'active' : ''; ?>" data-toggle="tab" href="#cltPdts">Client</a></li>
	<li class="nav-item"><a class="nav-link <?php echo $onglet == 'cltGrp' ? 'active' : ''; ?>" data-toggle="tab" href="#cltGrp">Groupe</a></li>
</ul>
<div class="tab-content">
	<?php
    include('_stats_clt_general.php');
    include('_stats_clt_client.php');
    include('_stats_clt_groupe.php');
    ?>

</div>
