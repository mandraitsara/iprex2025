<div id="pdtGeneral" class="tab-pane <?php echo $onglet == 'pdtGeneral' || $onglet == '' ? 'active' : 'fade'; ?>">
	<?php
	include('_stats_pdt_general_form.php');
	?>

	<div class="row mt-2">
		<div class="col-6 col-xl-8">

			<?php
			include('_stats_pdt_general_table.php');
			include('_stats_pdt_general_marges.php');
			?>

		</div>
		<div class="col-6 col-xl-4">
			<?php
			include('_stats_pdt_general_graphs.php');
			?>

		</div>
	</div>
</div>