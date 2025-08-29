<div id="cltGrp" class="tab-pane <?php echo $onglet == 'cltGrp' ? 'active' : 'fade'; ?>">

	<?php include('_stats_clt_groupe_form.php'); ?>


	<div class="row mt-2">
		<div class="col-6 col-xl-8">
			<?php
			$groupe = $id_groupe > 0 ? $tiersManager->getTiersGroupe($id_groupe) : new TiersGroupe([]);
			if (!$groupe instanceof TiersGroupe) { $groupe = new TiersGroupe([]); }

			?>
			<h2>Groupe <?php echo $groupe->getNom() != '' ? ' - '.$groupe->getNom() : ''; ?></h2>
			<?php if ($id_groupe == 0) { ?>
				<div class="alert alert-info padding-20 text-center">Sélectionnez un groupe de clients</div>
			<?php } else {


				include('_stats_clt_groupe_table.php');
				include('_stats_clt_groupe_marges.php');
			 } ?>
		</div>
		<div class="col-6 col-xl-4">
			<?php if ($id_groupe > 0) {
				include('_stats_clt_groupe_graphs.php');
				 }
			?>
		</div>

	</div>

</div>