<div class="row mt-2">

	<?php
	if ($p1_date_du == '' && $p1_annee == '' && $p2_date_du == '' && $p2_annee == '') { ?>
		<div class="col-12"><div class="alert alert-info padding-20 text-center">Sélectionnez deux périodes à comparer...</div></div>
	<?php } else {

		$p1_txt = '';
		$p1_txt.= $p1_mois != '' ? Outils::getMoisListe()[$p1_mois].' ' : '';
		$p1_txt.= $p1_date_du != '' && $p1_date_au == '' ? 'A partir du ' : '';
		$p1_txt.= $p1_date_du != '' && $p1_date_au != '' ? 'Du ' : '';
		$p1_txt.= $p1_date_du != '' ? $p1_date_du . ' ' : '';
		$p1_txt.= $p1_date_au != '' ? ' au ' . $p1_date_au . ' ' : '';
		$p1_txt.= trim($p1_txt) == '' && $p1_annee  != '' ? 'Année ' . $p1_annee : '';
		$p1_txt.= $p1_mois != '' && $p1_annee != '' ? $p1_annee : '';

		$p2_txt = '';
		$p2_txt.= $p2_mois != '' ? Outils::getMoisListe()[$p2_mois].' ' : '';
		$p2_txt.= $p2_date_du != '' && $p2_date_au == '' ? 'A partir du ' : '';
		$p2_txt.= $p2_date_du != '' && $p2_date_au != '' ? 'Du ' : '';
		$p2_txt.= $p2_date_du != '' ? $p2_date_du . ' ' : '';
		$p2_txt.= $p2_date_au != '' ? ' au ' . $p2_date_au . ' ' : '';
		$p2_txt.= trim($p2_txt) == '' && $p2_annee  != '' ? 'Année ' . $p2_annee : '';
		$p2_txt.= $p2_mois != '' && $p2_annee != '' ? $p2_annee : '';

		$colonne_txt = $id_produit > 0 || $id_espece > 0 ? 'Client' : 'Produit';
		$champPrefixe = $id_produit > 0 || $id_espece > 0 ? 'clt' : 'pdt';
		$champ_code = $champPrefixe.'_code';
		$champ_nom = $champPrefixe.'_nom';

		if ($id_produit > 0) { $methodeStat = 'getStatsProduitClients';	}
		else if ($id_client > 0) { $methodeStat = 'getStatsClientProduits'; }
		else if ($id_espece > 0) { $methodeStat = 'getStatsEspeceClients'; }
		else if ($id_groupe > 0) { $methodeStat = 'getStatsGroupeProduits'; }
		else { $methodeStat = 'getStatsProduitsGeneral'; }
		?>

		<div class="col-6">
			<h2><?php echo $p1_txt; ?></h2>
			<table class="table admin table-v-middle table-stats">
				<thead>
				<tr>
					<th>Code</th>
					<th><?php echo $colonne_txt; ?></th>
					<th class="text-right">Poids</th>
					<th class="text-center">Colis/blocs</th>
					<th class="text-right">CA</th>
                    <?php if ($show_marges) { ?>
					<th class="text-right">Marge brute</th>
					<th class="text-right">Marge nette</th>
                    <?php } ?>
					<th class="text-right t-actions">Part du CA</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$liste = $statsManager->$methodeStat($params_1);
				if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
				if (empty($liste)) {
				    $colspan = $show_marges ? 8 : 6;
				    ?>
					<tr><td colspan="<?php echo $colspan; ?>" class="padding-20 text-center bg-warning">Aucune donnée pour la période sélectionnée...</td></tr>
				<?php }


				$total_poids = 0;
				$total_qte   = 0;
				$total_ca    = 0;
				$pourcentageTotal = 0;
				$total_marge_brute = 0;
				$total_marge_nette = 0;
				$graph_labels = '';
				$graph_datas = '';

				// On récupère le Total CA pour le calcul du pourcentage pour le graph
				foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

				// Boucle sur les résultats
				foreach ($liste as $stat) {

					$total_poids+= floatval($stat['poids']);
					$total_qte+=   intval($stat['qte']);

					$pourcentage =  round((round($stat['ca'],0) * 100) / round($total_ca,0),0);
					$pourcentageTotal+=$pourcentage;

					$graph_labels.=  '["'.$stat[$champ_nom].' - '.$pourcentage.'%"],';
					$graph_datas.= round($stat['ca'],0).',';

					$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
					$marge_nette = $pourcentage == 100 ? $marge_brute - $ff_p1 : $marge_brute - ($ff_p1 * (1-($pourcentage/100)));

					$total_marge_brute+=$marge_brute;
					$total_marge_nette+=$marge_nette;
					?>

					<tr>
						<td><?php echo isset($stat[$champ_code]) ? $stat[$champ_code] : $na; ?></td>
						<td><?php echo isset($stat[$champ_nom]) ? $stat[$champ_nom] : $na; ?></td>
						<td class="text-right nowrap"><?php echo isset($stat['poids']) ? number_format($stat['poids'],3,'.', '').' Kg' : $na; ?></td>
						<td class="text-center"><?php echo isset($stat['qte']) ? $stat['qte'] : $na; ?></td>
						<td class="text-right nowrap"><?php echo isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', ''). ' €' : $na; ?></td>
                        <?php if ($show_marges) { ?>
						<td class="text-right nowrap <?php echo $marge_brute <=0 ? 'text-danger' : 'text-success';?>"><?php echo  number_format($marge_brute,$deci, '.', ' ').' €'; ?></td>
						<td class="text-right nowrap <?php echo $marge_nette <=0 ? 'text-danger' : 'text-success';?>"><?php echo  number_format($marge_nette,$deci, '.', ' ').' €'; ?></td>
                        <?php } ?>
						<td class="text-right nowrap t-actions"><?php echo $pourcentage; ?> %</td>
					</tr>

				<?php } // FIN boucle sur les résultats

				$graph_labels = substr($graph_labels,0,-1);
				$graph_datas = substr($graph_datas,0,-1);

				?>
				</tbody>
				<tfoot>
				<tr>
					<th colspan="2">Total <?php echo strtolower($colonne_txt); ?>s</th>
					<th class="text-right nowrap"><?php echo  number_format($total_poids,3,'.', ''); ?> Kg</th>
					<th class="text-center"><?php echo $total_qte ; ?></th>
					<th class="text-right nowrap"><?php echo  number_format($total_ca,$deci,'.', '') ; ?> €</th>
                    <?php if ($show_marges) { ?>
					<th class="text-right nowrap"><?php echo number_format($total_marge_brute,$deci,'.', ' '); ?> €</th>
					<th class="text-right nowrap"><?php echo number_format($total_marge_nette,$deci,'.', ' '); ?> €</th>
                    <?php } ?>
					<th class="text-right nowrap  t-actions"><?php echo  $pourcentageTotal ; ?> %</th>
				</tr>
				</tfoot>
			</table>
		</div>
		<div class="col-6">
			<h2><?php echo $p2_txt; ?></h2>
			<table class="table admin table-v-middle table-stats">
				<thead>
				<tr>
					<th>Code</th>
					<th><?php echo $colonne_txt; ?></th>
					<th class="text-right">Poids</th>
					<th class="text-center">Colis/blocs</th>
					<th class="text-right">CA</th>
                    <?php if ($show_marges) { ?>
					<th class="text-right">Marge brute</th>
					<th class="text-right">Marge nette</th>
                    <?php } ?>
					<th class="text-right t-actions">Part du CA</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$liste = $statsManager->$methodeStat($params_2);
				if (!$liste || !is_array($liste)) { $liste = []; } // Gestion des erreurs
				if (empty($liste)) {
					$colspan = $show_marges ? 8 : 6;
				    ?>
					<tr><td colspan="<?php echo $colspan; ?>" class="padding-20 text-center bg-warning">Aucune donnée pour la période sélectionnée...</td></tr>
				<?php }

				$total_poids = 0;
				$total_qte   = 0;
				$total_ca    = 0;
				$pourcentageTotal = 0;
				$total_marge_brute = 0;
				$total_marge_nette = 0;
				$graph_labels = '';
				$graph_datas = '';

				// On récupère le Total CA pour le calcul du pourcentage pour le graph
				foreach ($liste as $stat) { $total_ca+=    floatval($stat['ca']); }

				// Boucle sur les résultats
				foreach ($liste as $stat) {

					$total_poids+= floatval($stat['poids']);
					$total_qte+=   intval($stat['qte']);

					$pourcentage =  round((round($stat['ca'],0) * 100) / round($total_ca,0),0);
					$pourcentageTotal+=$pourcentage;

					$graph_labels.=  '["'.$stat[$champ_nom].' - '.$pourcentage.'%"],';
					$graph_datas.= round($stat['ca'],0).',';

					$marge_brute = isset($stat['marge_brute']) ? floatval($stat['marge_brute']) : 0;
					$marge_nette = $pourcentage == 100 ? $marge_brute - $ff_p2 : $marge_brute - ($ff_p2 * (1-($pourcentage/100)));

					$total_marge_brute+=$marge_brute;
					$total_marge_nette+=$marge_nette;
					?>

					<tr>
						<td><?php echo isset($stat[$champ_code]) ? $stat[$champ_code] : $na; ?></td>
						<td><?php echo isset($stat[$champ_nom]) ? $stat[$champ_nom] : $na; ?></td>
						<td class="text-right nowrap nowrap"><?php echo isset($stat['poids']) ? number_format($stat['poids'],3,'.', '').' Kg' : $na; ?></td>
						<td class="text-center nowrap"><?php echo isset($stat['qte']) ? $stat['qte'] : $na; ?></td>
						<td class="text-right nowrap"><?php echo isset($stat['ca']) ? number_format($stat['ca'],$deci,'.', '').' €' : $na; ?></td>
                        <?php if ($show_marges) { ?>
						<td class="text-right nowrap <?php echo $marge_brute <=0 ? 'text-danger' : 'text-success';?>"><?php echo  number_format($marge_brute,$deci, '.', ' '); ?> €</td>
						<td class="text-right nowrap <?php echo $marge_nette <=0 ? 'text-danger' : 'text-success';?>"><?php echo  number_format($marge_nette,$deci, '.', ' '); ?> €</td>
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
					<th colspan="2">Total <?php echo strtolower($colonne_txt); ?>s</th>
					<th class="text-right nowrap"><?php echo  number_format($total_poids,3,'.', ''); ?> Kg</th>
					<th class="text-center"><?php echo $total_qte ; ?></th>
					<th class="text-right nowrap"><?php echo  number_format($total_ca,$deci,'.', '') ; ?> €</th>
                    <?php if ($show_marges) { ?>
					<th class="text-right nowrap"><?php echo number_format($total_marge_brute,$deci,'.', ' '); ?> €</th>
					<th class="text-right nowrap"><?php echo number_format($total_marge_nette,$deci,'.', ' '); ?> €</th>
                    <?php } ?>
					<th class="text-right nowrap t-actions"><?php
						$pourcentageTotal = 100;
                        echo  $pourcentageTotal ; ?> %</th>
				</tr>
				</tfoot>
			</table>
		</div>

	<?php } // FIN test périodes

	?>
</div>