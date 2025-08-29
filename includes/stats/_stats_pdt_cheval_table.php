<div class="row mt-2">
    <div class="col">

        <?php
        if ($nb_chevaux == 0 && $nb_heures == 0) { ?>
            <div class="alert alert-info padding-50 text-center">
                <i class="fa fa-info-circle fa-3x mb-2"></i>
                <p class="mb-0">Complétez les valeurs ci-dessus et cliquez sur "Calculer"...</p>
            </div>
		<?php } else if ($nb_chevaux == 0 || $nb_heures == 0 ) { ?>
            <div class="alert alert-danger padding-50 text-center">
                <i class="fa fa-exclamation-circle fa-3x mb-2"></i>
                <p class="text-28">Données incomplètes</p>
                <p class="mb-0">Vérifiez les quantités saisies...</p>
            </div>
        <?php } else {
            if ($nb_heures == 0) { $nb_heures = 1; }
            if ($nb_chevaux == 0) { $nb_chevaux = 1; }
            $cheval_heure =  $nb_heures / $nb_chevaux;
            ?>

            <h2>FAIRE UN CHEVAL</h2>
            <div class="row">
                <div class="col-3">
                    <span class="badge badge-info padding-25 text-50 text-center"><?php echo number_format($cheval_heure,2,'.', ' '); ?></span>
                    <span class="text-28 gris-5">cheval à l'heure</span>
                </div>
            </div>

        <?php }
        ?>
    </div> <!-- FIN emplacement graph -->
</div> <!-- FIN row des résultats -->