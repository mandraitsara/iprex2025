<div id="cltPdts" class="tab-pane <?php echo $onglet == 'cltPdts' ? 'active' : 'fade'; ?>">

	<?php include('_stats_clt_client_form.php'); ?>

	<div class="row mt-2">
		<div class="col-6 col-xl-8">
			<?php
			$client = $id_client > 0 ? $tiersManager->getTiers($id_client) : new Tiers([]);
			if (!$client instanceof Tiers) { $client = new Tiers([]); }
			?>
			<h2>Client<?php echo $client->getNom() != '' ? ' - '.$client->getNom() : ''; ?></h2>
			<?php if ($id_client == 0) { ?>
				<div class="alert alert-info padding-20 text-center">Sélectionnez un client</div>
			<?php } else {
				include('_stats_clt_client_table.php');
				include('_stats_clt_client_marges.php');
			 } ?>
		</div>
		<div class="col-6 col-xl-4">
			<?php if ($client->getId() > 0) {
				include('_stats_clt_client_graphs.php');
			 } // FIN test client pour affichage du graph
			?>
		</div>
	</div>
</div>