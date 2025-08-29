<h2>Origines</h2>
<table class="table admin table-v-middle table-stats">
	<thead>
	<tr>
		<th>Origine</th>
		<th class="text-right nowrap">Poids total facturé</th>
		<th class="text-right nowrap">Chiffre d'affaires</th>
		<th class="text-right nowrap">Prix moyen au kg</th>
		<th class="text-right nowrap t-actions">Part du CA</th>
	</tr>
	</thead>
	<tbody>
	<?php

	$liste = $statsManager->getStatsProduitsOrigines($params);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	if (empty($liste)) { ?>
		<tr><td colspan="6" class="padding-20 text-center bg-warning">Aucune donnée pour la période sélectionnée...</td></tr>
	<?php }

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$total_pmkg  = 0;
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
	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }


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
	// Boucle sur les résultats
	foreach ($liste as $stat) {

		$total_poids+= floatval($stat['poids']);
		$total_qte+=   intval($stat['qte']);
		if($total_ca != 0)
			$pourcentage =  round((round($stat['ca'],2) * 100) / round($total_ca,2),2);
		$pourcentageTotal+=$pourcentage;
		$graph_labels.=  '["'.$stat['pays_nom'].' - '.$pourcentage.'%"],';
		$graph_datas.= round($stat['ca'],0).',';

		$ca = isset($stat['ca']) ? intval($stat['ca']) : 0;
		$poids = isset($stat['poids']) ? floatval($stat['poids']) : 0;
		$pmkilo = $poids > 0 ? $ca / $poids : 0;
		$total_pmkg+=$pmkilo;
		?>

		<tr>
			<td><?php echo isset($stat['pays_nom']) ? $stat['pays_nom'] : $na; ?></td>
			<td class="text-right"><?php echo isset($stat['poids']) ? number_format($stat['poids'],3,'.', '').' Kg' : $na; ?></td>
			<td class="text-right"><?php echo isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').' €' : $na; ?></td>
			<td class="text-right"><?php echo number_format($pmkilo,2,'.', ''); ?> €</td>
			<td class="text-right t-actions"><?php echo $pourcentage; ?> %</td>
		</tr>

	<?php } // FIN boucle sur les résultats

	$graph_labels = substr($graph_labels,0,-1);
	$graph_datas = substr($graph_datas,0,-1);

	?>
	</tbody>
	<tfoot>
	<tr>
		<th>Total origines</th>
		<th class="text-right"><?php echo  number_format($total_poids,3,'.', ''); ?> kg</th>
		<th class="text-right"><?php echo  number_format($total_ca,$deci,'.', '') ; ?> €</th>
		<th class="text-right"><?php echo  number_format($total_pmkg,2,'.', '') ; ?> €</th>
		<th class="text-right t-actions"><?php

			$pourcentageTotal = 100;
            echo  $pourcentageTotal ; ?> %</th>
	</tr>
	</tfoot>
</table>
<?php echo "Seuil de 75 % : " . number_format($seuil_75, 2, ',', ' ') . " €<br>"; echo "Chiffre d'affaires cumulé : " . number_format(isset($chiffre_affaires_cumule), 2, ',', ' ') . " €<br>"; echo "Pourcentage Chiffre d'affaires cumulé : " . isset($pourcentagechiffre_affaires_cumule) . " %<br>";?>