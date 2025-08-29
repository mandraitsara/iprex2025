<h2>Général : tous les produits</h2>
<table class="table admin table-v-middle table-stats">
	<thead>
	<tr>
		<th>Code</th>
		<th>Produit</th>
		<?php if ($sep_clt) { ?><th>Client</th><?php } ?>
		<?php if ($sep_fam) { ?><th>Famille/Espèce</th><?php } ?>
		<th class="text-right nowrap">Poids total facturé</th>
		<th class="text-center nowrap">Nb de colis/pièces</th>
		<th class="text-right nowrap">Chiffre d'affaires</th>
        <?php if ($show_marges) { ?>
		<th class="text-right nowrap">Marge brute</th>
		<th class="text-right nowrap">Marge nette</th>
        <?php } ?>
		<th class="t-actions text-right nowrap">Part du CA</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$liste = $statsManager->getStatsProduitsGeneral($params);

	if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
	$colspan = $sep_clt ? 9 : 8;
	if ($hide_marges) { $colspan-=2; }
	if (empty($liste)) { ?>
		<tr><td colspan="<?php echo $colspan; ?>" class="padding-20 text-center bg-warning">Aucune donnée pour la période sélectionnée...</td></tr>
	<?php }

	$total_poids = 0;
	$total_qte   = 0;
	$total_ca    = 0;
	$pourcentageTotal = 0;

	$graph_labels = '';
	$graph_datas = '';

	// On récupère le Total CA pour le calcul du pourcentage pour le graph
	// on ne peux pas avoir les memes totaux sur produits et clients, car les avoirs se comptabilisent au client, pas au produit
	$total_ca = $statsManager->getTotalCaPerdiod($params);

	$id_fam_prec = 0;
	$fam_nom_prec = '';

	$total_poids_fam = 0;
	$total_qte_fam   = 0;
	$total_ca_fam    = 0;
	$total_marge_brute = 0;
	$total_marge_nette = 0;
	$pourcentage_fam = 0;

	$colspan = $sep_clt ? 3 : 2;

	// Boucle sur les résultats
	foreach ($liste as $stat) {
		$total_poids+= floatval($stat['poids']);
        if ((int)$stat['vendu_piece'] != 0) {
			$total_qte+=   intval($stat['qte']);
        }

        if ($arrondir) {
			$pourcentage = $total_ca > 0 ? round((round($stat['ca'],0) * 100) / round($total_ca,0),0) : 0;
        } else {
			$pourcentage = $total_ca > 0 ? round(($stat['ca'] * 100) / $total_ca,2) : 0;
        }
		$pourcentageTotal+=$pourcentage;

		// On prends les produits pour le graph si on regroupe pas par famille
		if (!$sep_fam) {
			$graph_labels .= '["' . $stat['pdt_nom'] . ' - ' . $pourcentage . '%"],';
			$graph_datas .= $arrondir ? round($stat['ca'], 0) . ',' : $stat['ca'] . ',';
		}

		$id_fam = isset($stat['id_fam']) ? intval($stat['id_fam']) : 0;
		$nom_fam = isset($stat['nom_fam']) ? trim($stat['nom_fam']) : '';

		// Si on sépare par famille et qu'on a changé de famille, on affiche le sous-total
		if ($sep_fam && $id_fam_prec > 0 && $id_fam_prec != $id_fam) {
			$colspan = 3;
			?>

			<tr class="sous-total">
				<td colspan="<?php echo $colspan; ?>">Sous-total <?php echo $fam_nom_prec;?></td>
				<td class="text-right"><?php echo number_format($total_poids_fam,3,'.', '').' Kg'; ?></td>
				<td class="text-center"><?php echo $total_qte_fam; ?></td>
				<td class="text-right"><?php echo number_format($total_ca_fam,$deci,'.', ' '). ' €'; ?></td>
                <?php if ($show_marges) { ?>
				<td class="text-right"><?php echo number_format($total_marge_brute, $deci, '.', ' '). ' €';?></td>
				<td class="text-right"><?php echo number_format($total_marge_nette, $deci, '.', ' '). ' €';?></td>
                <?php } ?>
				<td class="t-actions text-right"><?php echo $pourcentage_fam; ?> %</td>
			</tr>

			<?php

			// On intègre les données pour le graph pour la famille
			$graph_labels .= '["' .$fam_nom_prec . ' - ' . $pourcentage_fam . '%"],';
			$graph_datas .= round($total_ca_fam, 0) . ',';

			// On réinitilise les compteur avec la valeur du produit de la boucle (première passe nouvelle famille)
			if ((int)$stat['vendu_piece'] == 0) {
				$total_poids_fam = floatval($stat['poids']);
			} else {
				$total_qte_fam = intval($stat['qte']);
			}

			$total_ca_fam = floatval($stat['ca']);
			$pourcentage_fam =$pourcentage;

			// Sinon, on continue dans la même famille si on est dans l'affichage regroupé par famille
		} else if ($sep_fam) {

			// Dans ce cas, on cumule les compteurs
            if ((int)$stat['vendu_piece'] == 0) {
				$total_poids_fam+= floatval($stat['poids']);
			} else {
				$total_qte_fam+= intval($stat['qte']);
			}


			$total_ca_fam+= floatval($stat['ca']);
			$pourcentage_fam+=$pourcentage;

		} // FIN sous-total par famille après changement de famille

		$tarif_frs  = isset($stat['tarif_frs']) ? floatval($stat['tarif_frs']) : 0;
		$marge_brute  = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
		$ca         = isset($stat['ca']) ? floatval($stat['ca']) : 0;
		$qte        = isset($stat['qte']) ? floatval($stat['qte']) : 0;
		$poids      = isset($stat['poids']) ? floatval($stat['poids']) : 0;
		$piece      = isset($stat['vendu_piece']) && intval($stat['vendu_piece']) == 1;

		$marge_nette = $pourcentage == 100 ? $marge_brute - $ff : $marge_brute - ($ff * (1-($pourcentage/100)));

		$total_marge_brute+=$marge_brute;
		$total_marge_nette+=$marge_nette;

		?>

		<tr>
			<td><?php echo isset($stat['pdt_code']) ? $stat['pdt_code'] : $na; ?></td>
			<td><?php echo isset($stat['pdt_nom']) ? $stat['pdt_nom'] : $na; ?></td>
			<?php if ($sep_fam) { ?>
				<td><?php echo $stat['nom_fam']; ?></td>
			<?php } ?>
			<?php if ($sep_clt) { ?>
				<td><?php echo $stat['nom_clt']; ?></td>
			<?php } ?>
			<td class="text-right"><?php echo isset($stat['poids']) && (int)$stat['vendu_piece'] == 0 ? number_format($stat['poids'],3,'.', '').' Kg' : $na; ?></td>
			<td class="text-center"><?php echo isset($stat['qte']) && (int)$stat['vendu_piece'] == 1 ? $stat['qte'] : $na; ?></td>
			<td class="text-right"><?php echo isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', ' '). ' €' : $na; ?></td>
            <?php if ($show_marges) { ?>
			<td class="text-right <?php echo $marge_brute <=0 ? 'text-danger' : 'text-success';?>"><?php echo  number_format($marge_brute,$deci, '.', ' '); ?> €</td>
			<td class="text-right <?php echo $marge_nette <=0 ? 'text-danger' : 'text-success';?>"><?php echo  number_format($marge_nette,$deci, '.', ' '); ?> €</td>
            <?php } ?>
			<td class="t-actions text-right"><?php echo number_format($pourcentage,2,'.', ' '); ?> %</td>
		</tr>

		<?php

		// On met à jour le numéro de famille pour comparaison avant la prochaine boucle
		if ($sep_fam) { $id_fam_prec = $id_fam; $fam_nom_prec = $nom_fam;}

	} // FIN boucle sur les résultats

	// Dernier sous-total par famille après la fin de boucle
	if ($sep_fam) {
		?>

		<tr class="sous-total">
			<td colspan="<?php echo $colspan; ?>">Sous-total <?php echo $fam_nom_prec;?></td>
			<td class="text-right"><?php echo number_format($total_poids_fam,3,'.', ' '); ?> Kg</td>
			<td class="text-center"><?php echo $total_qte_fam; ?></td>
			<td class="text-right"><?php echo number_format($total_ca_fam,$deci,'.', ' '); ?> €</td>
            <?php if ($show_marges) { ?>
			<td class="text-right"><?php echo number_format($total_marge_brute, $deci, '.', ' ');?> €</td>
			<td class="text-right"><?php echo number_format($total_marge_nette, $deci, '.', ' ');?> €</td>
            <?php } ?>
			<td class="t-actions text-right"><?php echo $pourcentage_fam; ?> %</td>
		</tr>

		<?php
		// On intègre les données pour le graph pour la famille
		$graph_labels .= '["' .$fam_nom_prec . ' - ' . $pourcentage_fam . '%"],';
		$graph_datas .= round($total_ca_fam, 0) . ',';
	}

	$graph_labels = substr($graph_labels,0,-1);
	$graph_datas = substr($graph_datas,0,-1);
	?>
	</tbody>
	<tfoot>
	<tr>
		<th colspan="<?php echo $colspan; ?>">Total produits</th>
		<th class="text-right nowrap"><?php echo  number_format($total_poids,3,'.', ' '); ?> Kg</th>
		<th class="text-center nowrap"><?php // echo $total_qte ; ?></th>
		<th class="text-right nowrap"><?php echo number_format($total_ca,$deci,'.', ' ') ; ?> €</th>
        <?php if ($show_marges) { ?>
		<th class="text-right"><?php echo number_format($total_marge_brute, $deci, '.', ' ');?> €</th>
		<th class="text-right"><?php echo number_format($total_marge_nette, $deci, '.', ' ');?> €</th>
        <?php }

        ?>
		<th class="t-actions text-right nowrap">100.00 %</th>
	</tr>
	</tfoot>
</table>