<form action="<?php echo basename($_SERVER['PHP_SELF']);?>" method="post" id="formPdtGeneral">
	<input type="hidden" name="action" value="pdt"/>
	<input type="hidden" name="onglet" value="pdtGeneral"/>
    <input type="hidden" name="mode" value="generePdf"/>
	<div class="row">
		<div class="col-3">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">Du</span>
				</div>
				<input type="text" class="form-control datepicker border-right-0" placeholder="-" name="date_du" value="<?php echo $date_du; ?>">
				<div class="input-group-prepend">
					<span class="input-group-text">au</span>
				</div>
				<input type="text" class="form-control datepicker" placeholder="-" name="date_au" value="<?php
				if ($date_au != '') { echo $date_au; } ?>">
			</div>
		</div>
		<div class="col-3">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">Mois</span>
				</div>
				<select class="selectpicker show-tick form-control" name="mois" title="-">
					<option value="0" title="Tous">Toute l'année / personnalisé</option>
					<option data-divider="true"></option>
					<?php
					foreach(Outils::getMoisListe() as $mois_num => $mois_txt) { ?>
						<option value="<?php echo $mois_num; ?>" <?php echo intval($mois) == intval($mois_num) ? 'selected' : ''; ?>><?php
							echo ucfirst($mois_txt); ?></option>
					<?php }
					?>
				</select>

				<div class="input-group-prepend">
					<span class="input-group-text border-left-0">Année</span>
				</div>
				<select class="selectpicker show-tick form-control" name="annee" title="-" data-size="5">
					<option value="0">Personnalisé</option>
					<option data-divider="true"></option>
					<?php
					for ($an = intval(date('Y')); $an > 2018; $an--) { ?>
						<option value="<?php echo $an; ?>" <?php echo intval($annee) == intval($an) ? 'selected' : ''; ?>><?php echo $an; ?></option>
					<?php }
					?>
				</select>
			</div>
		</div>
		<div class="col">
            <div class="row">
                <div class="col-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Par famille</span>
                        </div>
                        <input type="checkbox"
                               class="togglemaster"
                               data-toggle="toggle"
                               data-on="Oui"
                               data-off="Non"
                               data-onstyle="info"
                               data-offstyle="secondary"
                               name="sep_fam"
							<?php echo $sep_fam ? 'checked' : '' ; ?>
                        />
                    </div>
                </div>
                <div class="col-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Par client</span>
                        </div>
                        <input type="checkbox"
                               class="togglemaster"
                               data-toggle="toggle"
                               data-on="Oui"
                               data-off="Non"
                               data-onstyle="info"
                               data-offstyle="secondary"
                               name="sep_clt"
							<?php echo $sep_clt ? 'checked' : '' ; ?>
                        />
                    </div>
                </div>
                <div class="col-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Marges</span>
                        </div>
                        <input type="checkbox"
                               class="togglemaster"
                               data-toggle="toggle"
                               data-on="Non"
                               data-off="Oui"
                               data-onstyle="secondary"
                               data-offstyle="info"
                               name="hide_marges"
							<?php echo $hide_marges ? 'checked' : '' ; ?>
                        />
                    </div>
                </div>
            </div>
		</div>

		<div class="col-1 text-right">
			<button type="submit" class="btn btn-info"><i class="fa fa-check mr-1"></i> Afficher</button>
		</div>
	</div>
</form>