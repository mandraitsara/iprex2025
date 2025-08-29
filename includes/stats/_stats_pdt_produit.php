<div id="pdtsClts" class="tab-pane <?php echo $onglet == 'pdtsClts' ? 'active' : 'fade'; ?>">
	<?php
	include('_stats_pdt_produit_form.php')
	?>
	<div class="row mt-2">
		<div class="col-6 col-xl-8">
			<h2>Produit <?php

				$produit = $id_produit > 0 ? $produitsManager->getProduit($id_produit, false) : new Produit([]);
				if (!$produit instanceof Produit) { $produit = new Produit([]); }

				echo $produit->getNom() != '' ? ' - '.$produit->getNom() : ''; ?></h2>
			<?php if ($id_produit == 0) { ?>
				<div class="alert alert-info padding-20 text-center">Sélectionnez un produit</div>
			<?php } else {

				include('_stats_pdt_produit_table.php');
				include('_stats_pdt_produit_marges.php');
				?>
			<?php } ?>
		</div>
		<div class="col-6 col-xl-4">
			<?php if ($id_produit > 0) {
				include('_stats_pdt_produit_graphs.php');
			  } ?>
		</div>
	</div>
</div>