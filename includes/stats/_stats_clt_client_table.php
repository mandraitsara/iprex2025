<table class="table admin table-v-middle table-stats">
	<thead>
	<tr>
		<th>Code</th>
		<th>Produit</th>
		<th class="text-right nowrap">Total facturé</th>
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
    	$liste = $statsManager->getStatsClientProduits($params);
	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	if (empty($liste)) { ?>
		<tr><td colspan="<?php echo $show_marges ? 8 : 6; ?>" class="padding-20 text-center bg-warning">Aucune donnée pour la période et le client sélectionné...</td></tr>
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

		$pourcentage =  round((round($stat['ca'],2) * 100) / round($total_ca,2),2);
		$pourcentageTotal += $pourcentage;
		$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1-($pourcentage/100)));
		if (intval($stat['ca']) == 0) {  $marge_nette = 0; }

		$total_marge_brute+= $marge_brute;
		$total_marge_nette+=$marge_nette;

		$graph_labels.=  '["'.$stat['pdt_nom'].' - '.$pourcentage.'%"],';
		$graph_datas.= round($stat['ca'],0).',';
		// $graph_datas.= floatval($stat['ca']);

		$ca = isset($stat['ca']) ? floatval($stat['ca']) : 0;

		$qteOuPoidsFacture = $na;
        if (isset($stat['poids']) && $stat['poids'] > 0 ) {
			$qteOuPoidsFacture = number_format($stat['poids'],3,'.', '') . ' Kg';
        } else if (isset($stat['qte']) && $stat['qte'] > 0 ) {
			$qteOuPoidsFacture = $stat['qte'];
        }
		?>
		<tr>
			<td><?php echo isset($stat['pdt_code']) ? $stat['pdt_code'] : $na; ?></td>
			<td><?php echo isset($stat['pdt_nom']) ? $stat['pdt_nom'] : $na; ?></td>
			<td class="text-right"><?php echo $qteOuPoidsFacture; ?></td>
			<td class="text-right"><?php echo isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').' €' : $na; ?></td>
            <?php if ($show_marges) { ?>
			<td class="text-right <?php echo $marge_brute <=0 && $ca > 0 ? 'text-danger' : 'text-success';?>"><?php echo $ca > 0 ? number_format($marge_brute,$deci, '.', ' ').' €':  $na; ?></td>
			<td class="text-right <?php echo $marge_nette <=0 && $ca > 0 ? 'text-danger' : 'text-success';?>"><?php echo $ca > 0 ? number_format($marge_nette,$deci, '.', ' ').' €' : $na;  ?></td>
            <?php } ?>
			<td class="text-right t-actions"><?php echo $pourcentage; ?> %</td>
		</tr>

	<?php } // FIN boucle sur les résultats

	$graph_labels = substr($graph_labels,0,-1);
	$graph_datas = substr($graph_datas,0,-1);

    $totalQteOuPoids = $total_poids > 0 ? number_format($total_poids,3,'.', '').' Kg' : $total_qte;

	?>
	</tbody>
	<tfoot>
	<tr>
		<th colspan="2">Total produits</th>
		<th class="text-right"><?php echo $totalQteOuPoids ?></th>
		<th class="text-right"><?php echo  number_format($total_ca,0,'.', '') ; ?> €</th>
        <?php if ($show_marges) { ?>
		<th class="text-right"><?php echo number_format($total_marge_brute,0, '.', ' '); ?> €</th>
		<th class="text-right"><?php echo number_format($total_marge_nette,0, '.', ' '); ?> €</th>
        <?php } ?>
		<th class="text-right t-actions"><?php
			$pourcentageTotal = 100;
            echo  $pourcentageTotal ; ?> %</th>
	</tr>
	</tfoot>


</table>
<?php echo "Seuil de 75 % : " . number_format($seuil_75, 2, ',', ' ') . " €<br>"; echo "Chiffre d'affaires cumulé : " . number_format($chiffre_affaires_cumule, 2, ',', ' ') . " €<br>"; echo "Pourcentage Chiffre d'affaires cumulé : " . $pourcentagechiffre_affaires_cumule . " %<br>";?>
<div class="alert alert-info texte-fin"><i class="fa fa-info-circle mr-1"></i> Sont présentés ici tous les produits spécifiques au client, qu'ils soient facturés ou non sur la période sélectionnée.</div>