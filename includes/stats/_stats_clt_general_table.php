<h2>Général : tous les clients</h2>
<table class="table admin table-v-middle table-stats">
	<thead>
	<tr>
		<th>Code</th>
		<th>Client</th>
		<th class="text-right nowrap">Poids total facturé</th>
		<th class="text-center nowrap">Nb de colis/blocs facturés</th>
		<th class="text-right nowrap">Chiffre d'affaires</th>
        <?php if ($show_marges) { ?>
		<th class="text-right nowrap">Marge brute</th>
		<th class="text-right nowrap">Marge nette</th>
        <?php } ?>
		<th class="text-right nowrap t-actions">Part du CA</th>
	</tr>
	</thead>
	<tbody>
	<?php

	$liste = $statsManager->getStatsClientsGeneral($params);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	if (empty($liste)) { ?>
		<tr><td colspan="<?php echo $show_marges ? 8 : 6; ?>" class="padding-20 text-center bg-warning">Aucune donnée pour la période sélectionnée...</td></tr>
	<?php }


	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$total_marge_brute = 0;
	$total_marge_nette = 0;
	$pourcentageTotal = 0;

	$graph_labels = '';
	$graph_datas = '';


	/***
	 * seuille
	 */
	$seuil_75 = 0;
	$chiffre_affaires_cumule = 0;
	
	$graph_labels_seuille = '';
	$graph_datas_seuille = '';

    // on ne peux pas avoir les memes totaux sur produits et clients, car les avoirs se comptabilisent au client, pas au produit
	$total_ca = $statsManager->getTotalCaPerdiod($params);


	$seuil_75 = $total_ca * 0.75;
	//calcule seuille 
	foreach ($liste as $stat) {
		$chiffre_affaires_cumule += $stat['ca'];
		if ($chiffre_affaires_cumule >= $seuil_75) {
			break;
		}
	}

	if($total_ca != 0)
		$pourcentagechiffre_affaires_cumule =  round((round($chiffre_affaires_cumule,2) * 100) / round($total_ca,2),2);

	$graph_labels_seuille.=  "[\"Plus gros clients\"],[\"Autres clients\"]";
	$graph_datas_seuille.= round($chiffre_affaires_cumule,0).','.round($total_ca-$chiffre_affaires_cumule,0);


	function cmp($b, $a) {
		if ($a['ca'] == $b['ca']) { return 0; }
		return ($a['ca'] < $b['ca']) ? -1 : 1;
	}
	usort($liste, "cmp");

	// Boucle sur les résultats
	foreach ($liste as $stat) {

		$total_poids+= floatval($stat['poids']);
        if (floatval($stat['poids']) == 0) {
			$total_qte+=   intval($stat['qte']);
        }


		$pourcentage = $total_ca > 0 ? round((round($stat['ca'],2) * 100) / round($total_ca,2),2) : 0;

		$pourcentageTotal+=$pourcentage;
		$graph_labels.=  '["'.$stat['clt_nom'].' - '.$pourcentage.'%"],';
		$graph_datas.= round($stat['ca'],0).',';

		$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1-($pourcentage/100)));

		$total_marge_brute+=$marge_brute;
		$total_marge_nette+=$marge_nette;
		?>

		<tr>
			<td><?php echo isset($stat['clt_code']) ? $stat['clt_code'] : $na; ?></td>
			<td><?php echo isset($stat['clt_nom']) ? $stat['clt_nom'] : $na; ?></td>
			<td class="text-right"><?php echo isset($stat['poids']) && floatval($stat['poids']) > 0 ? number_format($stat['poids'],3,'.', '').' Kg' : $na; ?></td>
			<td class="text-center"><?php echo isset($stat['qte']) && floatval($stat['poids']) == 0 ? $stat['qte'] : $na; ?></td>
			<td class="text-right"><?php echo isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', ' ').' €' : $na; ?></td>
            <?php if ($show_marges) { ?>
			<td class="text-right <?php echo $marge_brute <=0 ? 'text-danger' : 'text-success';?>"><?php echo  number_format($marge_brute,$deci, '.', ' '); ?> €</td>
			<td class="text-right <?php echo $marge_nette <=0 ? 'text-danger' : 'text-success';?>"><?php echo  number_format($marge_nette,$deci, '.', ' '); ?> €</td>
            <?php } ?>
			<td class="text-right t-actions"><?php echo $pourcentage; ?> %</td>
		</tr>

	<?php } // FIN boucle sur les résultats

	$graph_labels = substr($graph_labels,0,-1);
	$graph_datas = substr($graph_datas,0,-1);

	?>
	</tbody>
	<tfoot>
	<tr>
		<th colspan="2">Total clients</th>
		<th class="text-right nowrap"><?php echo  number_format($total_poids,3,'.', ' '); ?> Kg</th>
		<th class="text-center"><?php // echo $total_qte ; ?></th>
		<th class="text-right nowrap"><?php echo  number_format($total_ca,$deci,'.', ' ') ; ?> €</th>
        <?php if ($show_marges) { ?>
		<th class="text-right nowrap"><?php echo number_format($total_marge_brute,$deci,'.', ' '); ?> €</th>
		<th class="text-right nowrap"><?php echo number_format($total_marge_nette,$deci,'.', ' '); ?> €</th>
        <?php } ?>
		<th class="text-right t-actions nowrap"><?php // echo  $pourcentageTotal ; ?>100.00 %</th>
	</tr>
	</tfoot>
</table>
<?php echo "Seuil de 75 % : " . number_format($seuil_75, 2, ',', ' ') . " €<br>"; echo "Chiffre d'affaires cumulé : " . number_format($chiffre_affaires_cumule, 2, ',', ' ') . " €<br>"; echo "Pourcentage Chiffre d'affaires cumulé : " . $pourcentagechiffre_affaires_cumule . " %<br>";?>