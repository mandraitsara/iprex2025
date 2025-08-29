<div id="cltGeneral" class="tab-pane <?php echo $onglet == 'cltGeneral' || $onglet == '' ? 'active' : 'fade'; ?>">

	<?php include('_stats_clt_general_form.php'); ?>

	<!-- Tableau stats clients général -->
	<div class="row mt-2">
		<div class="col-6 col-xl-8">

			<?php
			include('_stats_clt_general_table.php');
			include('_stats_clt_general_marges.php');
			?>

		</div>
		<div class="col-6 col-xl-4">
			<?php include('_stats_clt_general_graphs.php');	?>
		</div>
	</div>

</div>