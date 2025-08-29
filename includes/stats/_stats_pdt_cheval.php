<div id="pdtCheval" class="tab-pane <?php echo $onglet == 'pdtCheval' ? 'active' : 'fade'; ?>">
	<?php

    $nb_chevaux = isset($_REQUEST['nb_chevaux']) ? intval($_REQUEST['nb_chevaux']) : 0;
    $nb_personnes = isset($_REQUEST['nb_personnes']) ? intval($_REQUEST['nb_personnes']) : 0;
    $nb_heures = isset($_REQUEST['nb_heures']) ? intval($_REQUEST['nb_heures']) : 0;

	include('_stats_pdt_cheval_form.php');
	include('_stats_pdt_cheval_table.php');
	?>
</div> <!-- FIN conteneur onglet famille -->