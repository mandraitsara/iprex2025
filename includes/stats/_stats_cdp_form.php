<form action="<?php echo basename($_SERVER['PHP_SELF']);?>" method="post" class="bg-c padding-20" id="formCdp">
	<input type="hidden" name="action" value="cdp"/>
    <input type="hidden" name="mode" value="generePdf"/>
    <input type="hidden" name="onglet" value="cdp"/>

	<div class="row">
		<div class="col-6">
			<div class="alert alert-secondary">
				<div class="row"><div class="col"><h2 class="mt-0 text-center">Période 1</h2></div></div>
				<div class="row">
					<div class="col-6">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text">Du</span>
							</div>
							<input type="text" class="form-control datepicker border-right-0" placeholder="-" name="p1_date_du" value="<?php echo $p1_date_du; ?>">
							<div class="input-group-prepend">
								<span class="input-group-text">au</span>
							</div>
							<input type="text" class="form-control datepicker" placeholder="-" name="p1_date_au" value="<?php
							if ($p1_date_au != '') { echo $p1_date_au; } ?>">
						</div>
					</div>
					<div class="col-6">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text">Mois</span>
							</div>
							<select class="selectpicker show-tick form-control" name="p1_mois" title="-">
								<option value="0" title="Tous">Toute l'année / personnalisé</option>
								<option data-divider="true"></option>
								<?php
								foreach(Outils::getMoisListe() as $mois_num => $mois_txt) { ?>
									<option value="<?php echo $mois_num; ?>" <?php echo intval($p1_mois) == intval($mois_num) ? 'selected' : ''; ?>><?php
										echo ucfirst($mois_txt); ?></option>
								<?php }
								?>
							</select>

							<div class="input-group-prepend">
								<span class="input-group-text border-left-0">Année</span>
							</div>
							<select class="selectpicker show-tick form-control" name="p1_annee" title="-" data-size="5">
								<option value="0">Personnalisé</option>
								<option data-divider="true"></option>
								<?php
								for ($an = intval(date('Y')); $an > 2018; $an--) { ?>
									<option value="<?php echo $an; ?>" <?php echo intval($p1_annee) == intval($an) ? 'selected' : ''; ?>><?php echo $an; ?></option>
								<?php }
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-6">
			<div class="alert alert-secondary">
				<div class="row"><div class="col"><h2 class="mt-0 text-center">Période 2</h2></div></div>
				<div class="row">
					<div class="col-6">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text">Du</span>
							</div>
							<input type="text" class="form-control datepicker border-right-0" placeholder="-" name="p2_date_du" value="<?php echo $p2_date_du; ?>">
							<div class="input-group-prepend">
								<span class="input-group-text">au</span>
							</div>
							<input type="text" class="form-control datepicker" placeholder="-" name="p2_date_au" value="<?php
							if ($p2_date_au != '') { echo $p2_date_au; } ?>">
						</div>
					</div>
					<div class="col-6">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text">Mois</span>
							</div>
							<select class="selectpicker show-tick form-control" name="p2_mois" title="-">
								<option value="0" title="Tous">Toute l'année / personnalisé</option>
								<option data-divider="true"></option>
								<?php
								foreach(Outils::getMoisListe() as $mois_num => $mois_txt) { ?>
									<option value="<?php echo $mois_num; ?>" <?php echo intval($p2_mois) == intval($mois_num) ? 'selected' : ''; ?>><?php
										echo ucfirst($mois_txt); ?></option>
								<?php }
								?>
							</select>

							<div class="input-group-prepend">
								<span class="input-group-text border-left-0">Année</span>
							</div>
							<select class="selectpicker show-tick form-control" name="p2_annee" title="-" data-size="5">
								<option value="0">Personnalisé</option>
								<option data-divider="true"></option>
								<?php
								for ($an = intval(date('Y')); $an > 2018; $an--) { ?>
									<option value="<?php echo $an; ?>" <?php echo intval($p2_annee) == intval($an) ? 'selected' : ''; ?>><?php echo $an; ?></option>
								<?php }
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">

		<div class="col">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">Client</span>
				</div>
				<select class="selectpicker show-tick form-control" name="id_client" title="Sélectionnez" data-live-search="true" data-live-search-placeholder="Rechercher">
					<option value="0">Tous</option>
					<option data-divider="true"></option>
					<?php
					foreach($tiersManager->getListeClients() as $client) { ?>
						<option value="<?php echo $client->getId(); ?>" <?php echo $id_client == $client->getId() ? 'selected' : ''; ?>><?php
							echo $client->getNom(); ?></option>
					<?php }
					?>
				</select>
			</div>
		</div>

		<div class="col">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">Groupe</span>
				</div>
				<select class="selectpicker show-tick form-control" name="id_groupe" title="Sélectionnez">
					<option value="0">Tous</option>
					<option data-divider="true"></option>
					<?php
					foreach($tiersManager->getListeTiersGroupes() as $grp) { ?>
						<option value="<?php echo $grp->getId(); ?>" <?php echo $id_groupe == $grp->getId() ? 'selected' : ''; ?>><?php
							echo $grp->getNom(); ?></option>
					<?php }
					?>
				</select>
			</div>
		</div>

		<div class="col">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">Produit</span>
				</div>
				<select class="selectpicker show-tick form-control" name="id_produit" title="Sélectionnez" data-live-search="true" data-live-search-placeholder="Rechercher">
					<option value="0">Tous</option>
					<option data-divider="true"></option>
					<?php
					foreach($produitsManager->getListeProduits() as $pdtSelect) { ?>
						<option value="<?php echo $pdtSelect->getId(); ?>" <?php echo $id_produit == $pdtSelect->getId() ? 'selected' : ''; ?>><?php
							echo $pdtSelect->getNom(); ?></option>
					<?php }
					?>
				</select>
			</div>
		</div>

		<div class="col">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text">Famille</span>
				</div>
				<select class="selectpicker show-tick form-control" name="id_espece" title="Sélectionnez">
					<option value="0">Toutes</option>
					<option data-divider="true"></option>
					<?php
					foreach($especesManager->getListeProduitEspeces(true) as $espSelect) { ?>
						<option value="<?php echo $espSelect->getId(); ?>" <?php echo $id_espece == $espSelect->getId() ? 'selected' : ''; ?>><?php
							echo $espSelect->getNom(); ?></option>
					<?php }
					?>
				</select>
			</div>
		</div>

        <div class="col-2 pr-0">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Marges</span>
                </div>
                <input type="checkbox"
                       class="togglemaster h-38"
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

		<div class="col-1 pl-0 text-right">
			<button type="submit" class="btn btn-info"><i class="fa fa-check mr-1"></i> Afficher</button>
		</div>
	</div>
</form>