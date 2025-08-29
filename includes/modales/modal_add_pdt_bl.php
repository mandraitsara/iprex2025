<?php /**
------------------------------------------------------------------------
INCLUDE PHP - MODALE - Ajouter produit BL (Admin)

Copyright (C) 2020 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2020 Intersed
@version   1.0
@since     2020

------------------------------------------------------------------------
 */


$produitsManager = new ProduitManager($cnx);
$listeProduits = $produitsManager->getListeProduits();
?>
<div class="modal" id="modalAddProduitBl" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<!-- Modal Header -->
			<div class="modal-header">
                <p class="modal-title gris-5""><i class="fa fa-plus-circle"></i> Ajouter un produit au BL</p>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>

			<!-- Modal body -->
            <form class="modal-body" id="modalAddProduitBlBody">
                <input type="hidden" name="mode" value="addLigneProduitBl"/>
                <input type="hidden" name="id_bl" value=""/>
                <input type="hidden" name="id_bl" value=""/>
                <input type="hidden" name="id_pdt_negoce" value="0"/>
                <input type="hidden" name="id_bl_ligne" value="0"/>
                <input type="hidden" name="id_client" value=""/>
                <input type="hidden" name="colis_web" value="0"/>
                <input type="hidden" name="poids_web" value="0"/>
                <input type="hidden" name="qte_web" value="0"/>


                <div class="row mb-2">
                    <div class="col">
                        <div class="alert alert-secondary mb-0">
                            <p class="gris-5 mb-0"><i class="fa fa-caret-down mr-1 gris-9"></i> Sélectionnez un produit à ajouter au BL :</p>
                            <select class="selectpicker form-control show-tick" id="selectProduitNewBl" title="Sélectionnez un produit..." data-live-search="true" data-live-search-placeholder="Rechercher par nom ou code">
                                <?php foreach ($listeProduits as $pdt) {
                                    if (trim(strtolower($pdt->getNoms()[1])) == 'transport') { continue; }
                                    ?>
                                    <option value="<?php echo $pdt->getId(); ?>" data-subtext="<?php echo $pdt->getCode(); ?>"><?php echo $pdt->getNoms()[1]; ?></option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row" id="produitsNewBl"></div>
            </form>

            <!-- Modal footer -->
            <div class="modal-footer row padding-15">
                    <button type="button" class="btn btn-sm btn-success hid" id="btnAddPdtBl"><i class="fa fa-check mr-1"></i>Ajouter au BL</button>
                <button type="button" class="btn btn-secondary btn-sm ml-2" data-dismiss="modal">Annuler</button>
            </div>

		</div>
	</div>
</div>