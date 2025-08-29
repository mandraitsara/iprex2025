<div id="pdtsOrigines" class="tab-pane <?php echo $onglet == 'pdtsOrigines' ? 'active' : 'fade'; ?>">
	<?php
	include('_stats_pdt_origines_form.php')
	?>

	<div class="row mt-2">
		<div class="col-6 col-xl-8">
			<?php
			include('_stats_pdt_origines_table.php');
			/*include('_stats_pdt_origines_marges.php');*/
			?>
		</div>
		<div class="col-6 col-xl-4">
			<?php
			include('_stats_pdt_origines_graphs.php')
			?>

		</div>
	</div>
</div>