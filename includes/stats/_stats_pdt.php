<?php
$date_du    = isset($_REQUEST['date_du'])   && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['date_du'])) ? $_REQUEST['date_du'] : '';
$date_au    = isset($_REQUEST['date_au'])   && Outils::verifDateSql(Outils::dateFrToSql($_REQUEST['date_au'])) ? $_REQUEST['date_au'] : '';
$mois       = isset($_REQUEST['mois'])      && intval($_REQUEST['mois'])  > 0             ? $_REQUEST['mois']    : '';
$annee      = isset($_REQUEST['annee'])     && intval($_REQUEST['annee']) > 0             ? $_REQUEST['annee']   : '';
$id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit']) : 0;
$id_espece = isset($_REQUEST['id_espece']) ? intval($_REQUEST['id_espece']) : 0;

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
	$dt->modify('-1 month');
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
	'id_produit'  => $id_produit,
	'id_espece'   => $id_espece,
	'sep_fam'     => $sep_fam,
	'sep_clt'     => $sep_clt,
]; ?>
<ul class="nav nav-tabs">
	<li class="nav-item"><a class="nav-link <?php echo $onglet == 'pdtGeneral' || $onglet == '' ? 'active' : ''; ?>" data-toggle="tab" href="#pdtGeneral">Général</a></li>
	<li class="nav-item"><a class="nav-link <?php echo $onglet == 'pdtsClts' ? 'active' : ''; ?>" data-toggle="tab" href="#pdtsClts">Produit</a></li>
	<li class="nav-item"><a class="nav-link <?php echo $onglet == 'famPdtClts' ? 'active' : ''; ?>" data-toggle="tab" href="#famPdtClts">Famille</a></li>
	<li class="nav-item"><a class="nav-link <?php echo $onglet == 'pdtsOrigines' ? 'active' : ''; ?>" data-toggle="tab" href="#pdtsOrigines">Origines</a></li>
	<li class="nav-item"><a class="nav-link <?php echo $onglet == 'pdtCheval' ? 'active' : ''; ?>" data-toggle="tab" href="#pdtCheval">Faire un cheval</a></li>
</ul>
<div class="tab-content">
	<?php
	include('_stats_pdt_general.php');
	include('_stats_pdt_produit.php');
	include('_stats_pdt_famille.php');
	include('_stats_pdt_origines.php');
	include('_stats_pdt_cheval.php');
	?>
</div> <!-- FIN conteneur des onglets -->
