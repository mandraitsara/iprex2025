<?php /**
------------------------------------------------------------------------
INCLUDE PHP - MODALE

Copyright (C) 2020 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2020 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */ ?>
<!-- Modale Planning Nettoyage -->
<div class="modal" id="modalPlanNett" data-backdrop="static" data-keyboard="false">
    <input type="hidden" id="modalPlanNettIdUser" value="0"/>
	<div class="modal-dialog modal-dialog-centered modal-full">
		<div class="modal-content bg-light">
			<!-- Modal Header -->
			<div class="modal-header bg-secondary text-white">
				<p class="modal-title"><i class="fa fa-calendar-day mr-1"></i> Planning nettoyage</p>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>

			<!-- Modal body -->
            <div class="modal-body text-center bg-white text-20">
                <div id="modalPlanNettBody" class="text-28 text-center margin-top--10">
                    <i class="fa fa-spin fa-spinner fa-2x"></i>
                </div>
            </div>

			<!-- Modal footer -->
            <div class="modal-footer d-block bg-c padding-15">
                <div class="row">
                    <div class="col-4 text-left">
                        <button type="button" class="btn btn-primary btn-lg btnPlanNettZone text-left">
                            <i class="fa fa-street-view fa-lg fa-fw mr-2"></i>
                            Zone/Agent
                        </button>
                        <button type="button" class="btn btn-primary btn-lg btnPlanNettGen text-left ml-2">
                            <i class="fa fa-list fa-lg fa-fw mr-2"></i>
                            Afficher tout
                        </button>

                    </div>
                    <div class="col-4 text-center ">
                        <!-- Bouton pour les agents d'entretiens, toujours visible -->
                        <button type="button" class="btn btn-info btn-lg btnSignerAgentEntretien text-left">
                            <i class="fa fa-signature fa-lg fa-fw mr-2"></i>
                            Signature nettoyage
                        </button>
                    </div>
                    <div class="col-4 text-right ">
                        <button type="button" class="btn btn-secondary btn-lg ml-3" data-dismiss="modal"><i class="fa fa-times margin-right-10"></i>Fermer</button>
                    </div>
                </div>
            </div>
		</div>
	</div>
</div>