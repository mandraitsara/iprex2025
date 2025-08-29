<div id="famPdtClts" class="tab-pane <?php echo $onglet == 'famPdtClts' ? 'active' : 'fade'; ?>">
	<?php
	include('_stats_pdt_famille_form.php')
	?>

	<div class="row mt-2">
		<div class="col-6 col-xl-8">
			<h2>Famille<?php
				$espece = $id_espece > 0 ? $especesManager->getProduitEspece($id_espece) : new ProduitEspece([]);
				if (!$espece instanceof ProduitEspece) { $espece = new ProduitEspece([]); }
				echo $espece->getNom() != '' ? ' - '.$espece->getNom() : ''; ?></h2>
			<?php if ($id_espece == 0) { ?>
				<div class="alert alert-info padding-20 text-center">Sélectionnez une famille de produits</div>
			<?php } else {

				include('_stats_pdt_famille_table.php');
				include('_stats_pdt_famille_marges.php');
				?>

			<?php } ?>
		</div> <!-- FIN emplacement tableau -->
		<div class="col-6 col-xl-4">
			<?php if ($id_espece > 0) {

				include('_stats_pdt_famille_graphs.php');
			 } ?>
		</div> <!-- FIN emplacement graph -->
	</div> <!-- FIN row des résultats -->
</div> <!-- FIN conteneur onglet famille -->