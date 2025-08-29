<?php
/**
------------------------------------------------------------------------
INCLUDE PHP - VUE - Réception

Copyright (C) 2018 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.2
@since     2018

------------------------------------------------------------------------
 */
if ($utilisateur->getProfil_id() == $idProfilNettoyage) { ?>
    <div class="alert alert-secondary margin-top-15 text-center text-40 gris-7 padding-50">

        <i class="fa fa-info-circle gris-9 fa-2x"></i><br>
        Sélectionnez la zone puis appuyez sur le bouton « Planning Nettoyage ».</div>
<?php }
?>
<div id="vue" class="row vue-rcp <?php echo $utilisateur->getProfil_id() == $idProfilNettoyage ? 'd-none' : ''; ?>">
    <?php
	$lotsManager  = new LotManager($cnx);
	$lotsNegoceManager = new LotNegoceManager($cnx);

	// Sélection du lot : récupération des lots au stade de récéption...
    $listeLots = $lotsManager->getListeLotsByVue('rcp');

    // On récupère également les lots de négoce...
    $listeLotsNegoce = $lotsNegoceManager->getListeLotNegoces(
            ['visibles' => true, 'non_receptionnes' => true]
    );
    ?>
            <!-- Conteneur gauche pour les étapes -->
        <div class="col-12 col-md-9 col-lg-8 col-xl-10">
                <!-- Conteneur de l'étape 1 -->
                <div id="etape1" class="etape-workflow">

					<?php
					// Si aucun lot en reception
					if (empty($listeLots) && empty($listeLotsNegoce)) { ?>


                            <div class="alert alert-danger mt-3 padding-50">
                                <h2 class="mb-0 text-secondary text-center"><i class="fa fa-info-circle fa-2x mb-3"></i>
                                    <p>Aucun lot disponible pour cette vue.</p>
                                    <!-- <p class="mb-0 mt-4"><a href="<?php echo $_SERVER["REQUEST_URI"]; ?>" class="btn btn-secondary btn-lg verify-link"><i class="fa fa-sync-alt mr-2"></i> Vérifier à nouveau&hellip;</a></p> -->
                                    <p class="mb-0 mt-4"><a href="" class="btn btn-secondary btn-lg verify-link"><i class="fa fa-sync-alt mr-2"></i> Vérifier à nouveau&hellip;</a></p>
                                </h2>
                            </div>

						<?php

						// Des lots ont été trouvés...
                        } else { ?>

                        <!-- Titre de l'étape -->
                        <div class="row">
                            <div class="col mt-3">
                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Sélectionnez le lot a réceptionner :</h4>
                            </div>
                        </div>


                        <!-- Corps de l'étape : affichage des cartes de lots disponiles dans la vue -->
                        <div class="row">
                            <div class="col">
								<?php
								$na = '<span class="badge badge-warning badge-pill text-14">Non renseigné</span>';

								// Boucle sur les lots dispos
								foreach ($listeLots as $lotvue) {

									// On défini l'étape suivante si des infos de base sont manquantes ou si tout a déjà été complété par un admin
									if ($lotvue->getDate_reception() == '' || $lotvue->getDate_reception() == '0000-00-00') {

										// Etape 2 : confirmation de réception du lot
										$etapeSuivante = 2;

									} else {

										// Etape 3 : Contrôle températures
										$etapeSuivante = 3;
									}

									// Affichages de la carte...
									?>
                                    <div class="card text-white mb-3 carte-lot d-inline-block mr-3" style="max-width: 20rem; background-color: <?php echo $lotvue->getCouleur(); ?>" data-id-lot="<?php
									echo $lotvue->getId(); ?>" data-etape-suivante="<?php echo $etapeSuivante; ?>" data-composition="<?php
									echo $lotvue->getComposition();?>"  data-composition-viande="<?php echo $lotvue->getComposition_viande();?>">

                                        <div class="card-header text-36"><?php echo $lotvue->getNumlot(); ?></div>
                                        <div class="card-body">
                                            <table>
                                                <tr>
                                                    <td>Espèce</td>
                                                    <th><?php echo $lotvue->getNom_espece() != '-' ? strtoupper($lotvue->getNom_espece()) : $na ; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Origine</td>
                                                    <th><?php echo $lotvue->getNom_origine() != '' ? $lotvue->getNom_origine() : $na; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Fournisseur</td>
                                                    <th><?php echo $lotvue->getNom_fournisseur() != '' ? $lotvue->getNom_fournisseur() : $na; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Abattoir</td>
                                                    <th><?php echo $lotvue->getNom_abattoir() != '' ? $lotvue->getNom_abattoir() : $na ; ?><br><span class="texte-fin"><?php echo $lotvue->getNumagr_abattoir();?></span></th>
                                                </tr>
                                                <tr>
                                                    <td>Poids Abattoir</td>
                                                    <th><?php echo $lotvue->getPoids_abattoir() > 0 ? number_format($lotvue->getPoids_abattoir(),3, '.', ' ') . ' kgs' : $na; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Réception</td>
                                                    <th><?php echo $lotvue->getDate_reception() != '' && $lotvue->getDate_reception() != '0000-00-00'  ?  Outils::getDate_only_verbose($lotvue->getDate_reception(), true, false) : $na; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Poids Réception</td>
                                                    <th><?php echo $lotvue->getPoids_reception() > 0 ? number_format($lotvue->getPoids_reception(),3, '.', ' ') . ' kgs' : $na; ?></th>
                                                </tr>

                                            </table>
                                        </div> <!-- FIN body carte -->
                                    </div> <!-- FIN carte -->

								<?php } // FIN boucle sur les lots

								// On boucle ensuite sur les lots de négoce
								foreach ($listeLotsNegoce as $lotneg) {

									// On défini l'étape suivante si des infos de base sont manquantes ou si tout a déjà été complété par un admin
									if ($lotneg->getDate_entree() == '' || $lotneg->getDate_entree() == '0000-00-00') {

										// Etape 7 : réception d'un lot de négoce
										$etapeSuivante = 7;

									} else {

										// Etape 7 : réception d'un lot de négoce
										$etapeSuivante = 7;
									}
									?>
                                    <div class="card text-white mb-3 carte-lot d-inline-block mr-3" style="max-width: 20rem; background-color: <?php echo $lotneg->getCouleur(); ?>" data-id-lot="<?php
									echo $lotneg->getId(); ?>" data-etape-suivante="<?php echo $etapeSuivante; ?>" id="carteLotNeg<?php echo $lotneg->getId(); ?>">

                                        <div class="card-header text-36">NEGOCE ID<?php echo $lotneg->getId(); ?></div>
                                        <div class="card-body">
                                            <table>
                                                <tr>
                                                    <td>Espèce</td>
                                                    <th><?php echo $lotneg->getNom_espece() != '-' ? strtoupper($lotneg->getNom_espece()) : $na ; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Fournisseur</td>
                                                    <th><?php echo $lotneg->getNom_fournisseur() != '' ? $lotneg->getNom_fournisseur() : $na; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Poids BL</td>
                                                    <th><?php echo $lotneg->getPoids_bl() > 0 ? number_format($lotneg->getPoids_bl(),3, '.', ' ') . ' kgs' : $na; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Entrée</td>
                                                    <th><?php echo $lotneg->getDate_entree() != '' && $lotneg->getDate_entree() != '0000-00-00'  ?  Outils::getDate_only_verbose($lotneg->getDate_entree(), true, false) : $na; ?></th>
                                                </tr>
                                                <tr>
                                                    <td>Poids Réception</td>
                                                    <th><?php echo $lotneg->getPoids_reception() > 0 ? number_format($lotneg->getPoids_reception(),3, '.', ' ') . ' kgs' : $na; ?></th>
                                                </tr>

                                            </table>
                                        </div> <!-- FIN body carte -->
                                    </div> <!-- FIN carte -->
									<?php

								} // FIN boucle sur les lots de négoce
								?>
                            </div> <!-- FIN col pour les cartes -->
                        </div> <!-- FIN row pour les cartes -->

					<?php } ?>



                </div> <!-- FIN etape 1 -->

                       <!-- Conteneur de l'étape 2 (Date réception) -->
                <div id="etape2" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 2 -->

                <!-- Conteneur de l'étape 3 (Températures) -->
                <div id="etape3" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 3 -->

                <!-- Conteneur de l'étape 4 (Etat visuel / Photo) -->
                <div id="etape4" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 4 -->

                <!-- Conteneur de l'étape 5 (Conformité / Observations) -->
                <div id="etape5" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 5 -->

                <!-- Conteneur de l'étape 6 (FIN / Récap) -->
                <div id="etape6" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 6 -->

                <!-- Conteneur de l'étape 7 (Lot de négoce) -->
                <div id="etape7" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 7 -->

                <!-- Conteneur de l'étape 8 (Lot de négoce) -->
                <div id="etape8" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 8 -->

                <!-- Conteneur de l'étape 10 (Gestion de crochets) -->
                <div id="etape10" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 10 -->

                <!-- Conteneur de l'étape 11 (Retour palettes) -->
                <div id="etape11" class="hid"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 11 -->

                <!-- Conteneur de l'étape 12 (Sélection du transporteur si crochets) -->
                <div id="etape12" class="etapes-suivantes etape-workflow"><i class="fa fa-spin fa-spinner fa-2x mt-3"></i></div>
                <!-- FIN étape 12  -->





        </div> <!-- FIN conteneur gauche pour les étapes -->

    <!-- TICKET droit du lot sélectionné -->
    <div id="ticketLot" class="col-12 col-md-3 col-lg-4 col-xl-2 bg-dark">

        <!-- Contenu ajax du ticket -->
        <div id="ticketLotContent"></div>

        <!-- Bouton Retour palettes -->
        <button type="button" class="btn btn-info btn-lg form-control btnRetourPalettes mb-3 text-left"><i class="fa fa-pallet fa-fw vmiddle mr-2"></i>Retour palettes</button>
        <button type="button" class="btn btn-secondary btn-lg form-control btnAnnulerPalettes hid mb-3 text-left"><i class="fa fa-undo fa-fw vmiddle mr-2"></i>Annuler</button>

        <?php

        if (!empty($listeLots) || !empty($listeLotsNegoce)) { ?>
            <!-- Bouton incident -->
            <button type="button" class="btn btn-warning btn-lg form-control btnsTicketsLot btnIncident mb-3 text-left" data-toggle="modal" data-target="#modalIncidentFront"><i class="fa fa-exclamation-triangle fa-lg fa-fw vmiddle mr-2"></i>Déclarer un incident</button>

            <!-- Bouton se changement de lot -->
            <button type="button" class="btn btn-secondary btn-lg form-control btnsTicketsLot btnChangeLot text-left"><i class="fa fa-backspace fa-fw fa-lg vmiddle mr-2"></i>Sélectionner un autre lot</button>
		<?php }
        ?>



    </div> <!-- FIN ticket lot sélectionné -->





</div>

<?php
// Intégration des modales
include_once('includes/modales/modal_commentaires_front.php');
include_once('includes/modales/modal_signature_plannet.php');
include_once('includes/modales/modal_composition_viande.php');
include_once('includes/modales/modal_incident_front.php');
include_once('includes/modales/modal_confirm_temp.php');