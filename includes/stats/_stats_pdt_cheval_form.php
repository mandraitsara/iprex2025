<form action="<?php echo basename($_SERVER['PHP_SELF']);?>" method="post">
	<input type="hidden" name="action" value="pdt"/>
	<input type="hidden" name="onglet" value="pdtCheval"/>
	<div class="row">

		<div class="col-2">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">Nb de chevaux reçus</span>
				</div>
			    <input type="text" class="form-control text-center" name="nb_chevaux" placeholder="0" value="<?php echo $nb_chevaux > 0 ? intval($nb_chevaux) : ''; ?>"/>
			</div>
		</div>

        <div class="col-2 d-none">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Nb de personnes</span>
                </div>
                <input type="text" class="form-control text-center" name="nb_personnes" placeholder="0" value="<?php echo $nb_personnes > 0 ? intval($nb_personnes) : ''; ?>"/>
            </div>
        </div>
        <div class="col-2">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Nb d'heures</span>
                </div>
                <input type="text" class="form-control text-center" name="nb_heures" placeholder="0" value="<?php echo $nb_heures > 0 ? intval($nb_heures) : ''; ?>"/>
            </div>
        </div>

		<div class="col text-right">
			<button type="submit" class="btn btn-info"><i class="fa fa-calculator mr-1"></i> Calculer</button>
		</div>
	</div>
</form>