<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax VUE RECEPTION
------------------------------------------------------*/
ini_set('display_errors', 1);

// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$logsManager = new LogManager($cnx);
$lotsManager = new LotManager($cnx);

$fonctionNom = 'mode' . ucfirst($mode);
if (function_exists($fonctionNom)) {
    $fonctionNom();
}

/* ------------------------------------------
FONCTION - Message d'erreur sur ticket lot
-------------------------------------------*/
function erreurLot()
{
?>
    <div class="alert alert-danger text-center">
        <i class="fa fa-exclamation-triangle fa-3x mb-2"></i><br>
        <p>Erreur de récupération du lot !</p>
    </div>
    <?php
    exit;
} // FIN fonctions


/* ------------------------------------------
MODE - Charge l'info d'un lot dans la ticket
-------------------------------------------*/
function modeChargeTicketLot()
{

    global
        $utilisateur,
        $lotsManager,
        $cnx;

    $id_lot = isset($_REQUEST['id_lot']) ? $_REQUEST['id_lot'] : 0;
    if (substr($_REQUEST['id_lot'], 0, 1) !=  'N') {
        if ($id_lot == 0) {
            erreurLot();
        };
    }

    $etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;
    if ($etape == 7 || $etape == 8 || substr($id_lot, 0, 1) == 'N') {
        $id_lot = str_replace('N', '', $id_lot);
        $lotsNegoceManager = new LotNegoceManager($cnx);
        $lotNegoce = $lotsNegoceManager->getLotNegoce($id_lot);
        if (!$lotNegoce instanceof LotNegoce) {
            erreurLot();
            exit;
        }

        $na = '<span class="badge badge-warning badge-pill text-14">Non renseigné !</span>';
    ?>
        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1">
            ID<?php echo $lotNegoce->getId(); ?>
            <p class="margin-bottom-0 margin-top--15 text-center padding-left-15 padding-right-15"><span class="badge badge-dark form-control text-14">LOT DE NÉGOCE</span></p>
            <input id="lotid_photo" type="hidden" value="N<?php echo $id_lot; ?>">
        </div>
        <table>
            <?php if ($utilisateur->isDev()) { ?>
                <tr>
                    <td><i class="fa fa-user-secret mr-1 fa-lg gris-c"></i>ID #</td>
                    <th><kbd><?php echo $lotNegoce->getId(); ?></kbd></th>
                </tr>
            <?php } ?>
            <tr>
                <td>Espèce</td>
                <th><?php echo $lotNegoce->getNom_espece() != '' ? $lotNegoce->getNom_espece() : $na;
                    if (strpos(strtolower($lotNegoce->getNom_espece()), 'gibier') !== false) {
                        echo '<span class="ml-1">(';
                        echo $lotNegoce->getChasse() == 0 ? 'Elevage' : 'Chasse';
                        echo ')</span>';
                    }
                    ?></th>
            </tr>
            <tr>
                <td>Fournisseur</td>
                <th><?php echo $lotNegoce->getNom_fournisseur() != '' ? $lotNegoce->getNom_fournisseur() : $na; ?></th>
            </tr>
            <tr>
                <td>Poids BL</td>
                <th><?php echo $lotNegoce->getPoids_bl() != '' ? $lotNegoce->getPoids_bl() : $na; ?></th>
            </tr>
            <tr>
                <td>Poids Réception</td>
                <th><?php echo $lotNegoce->getPoids_reception() != '' ? $lotNegoce->getPoids_reception() : $na; ?></th>
            </tr>
            <tr>
                <td>Date d'entrée</td>
                <th><?php echo $lotNegoce->getDate_entree() != '' ? $lotNegoce->getDate_entree() : $na; ?></th>
            </tr>
        </table>


        </div>
        <?php

        // Incidents
        $incidentsManager = new IncidentsManager($cnx);
        $params = ['id_lot' => $id_lot, 'negoce' => 1];
        $incidentsLot = $incidentsManager->getIncidentsListe($params);

        if (empty($incidentsLot)) {
            exit;
        }

        ?>
        <div class="alert alert-dark bg-dark">
            <ul><?php
                foreach ($incidentsLot as $incident) {

                    $de = in_array(substr(strtolower($incident->getNom_type_incident()), 0, 1), ['a', 'e', 'i', 'o', 'u', 'é', 'è', 'à']) ? "d'" : "de ";
                ?>

                    <li class="text-fin text-16 text-warning">Incident <?php echo $de . strtolower($incident->getNom_type_incident()) . ' ' . $incident->getCodeIncident(); ?></li>

                <?php } ?>
            </ul>
            <button type="button" class="btn btn-secondary btn-lg form-control btnCommentaires text-left" data-toggle="modal" data-target="#modalCommentairesFront" data-id-lot="N<?php echo $id_lot; ?>">
                <i class="fa fa-info-circle fa-lg fa-fw vmiddle mr-2"></i>Infos incidents
            </button>
        </div>
    <?php

        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        erreurLot();
    };

    $na = '<span class="badge badge-warning badge-pill text-14">Non renseigné !</span>';

    $etat_visuel           = $lot->getReception() instanceof LotReception ? $lot->getReception()->getEtat_visuel() : -1;
    $etat_visuel_verbose   = $lot->getReception() instanceof LotReception ? $lot->getReception()->getEtat_visuel_verbose() : 'N/A';

    $validation           = $lot->getReception() instanceof LotReception ? $lot->getReception()->getConformite() : -1;
    $validation_verbose   = $lot->getReception() instanceof LotReception ? $lot->getReception()->getConformite_verbose() : 'N/A';

    ?>
    <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1">
        <?php echo $lot->getNumlot(); ?>
        <input id="lotid_photo" type="hidden" value="<?php echo $id_lot; ?>">
    </div>

    <table>
        <?php if ($utilisateur->isDev()) { ?>
            <tr>
                <td><i class="fa fa-user-secret mr-1 fa-lg gris-c"></i>ID #</td>
                <th><kbd><?php echo $lot->getId(); ?></kbd></th>
            </tr>
        <?php } ?>

        <tr>
            <td>Composition</td>
            <th><?php echo $lot->getComposition_verbose() != '-' ? $lot->getComposition_verbose() : $na;

                if ($lot->getComposition_viande() > 0) {
                    echo ' ' . $lot->getComposition_viande_verbose(true);
                }

                ?></th>
        </tr>
        <tr>
            <td>Abattoir</td>
            <th><?php echo $lot->getNom_abattoir() != '' ? $lot->getNom_abattoir() : $na; ?></th>
        </tr>
        <tr>
            <td>Origine</td>
            <th><?php echo $lot->getNom_origine() != '' ? $lot->getNom_origine() : $na; ?></th>
        </tr>
        <tr>
            <td>Abattage</td>
            <th><?php echo $lot->getDate_abattage() != '' && $lot->getDate_abattage() != '0000-00-00' ?  Outils::getDate_only_verbose($lot->getDate_abattage(), true, false) : $na; ?></th>
        </tr>
        <tr>
            <td>Réception</td>
            <th class="ticket-date-reception"><?php echo $lot->getDate_reception() != '' && $lot->getDate_reception() != '0000-00-00'  ?  Outils::getDate_only_verbose($lot->getDate_reception(), true, false) : $na; ?></th>
        </tr>
        <tr>
            <td>Poids (abattage)</td>
            <th class="text-18 ticket-poids_abattoir"><?php echo $lot->getPoids_abattoir() > 0 ? number_format($lot->getPoids_abattoir(), 3, '.', ' ') . ' kgs' : $na; ?></th>
        </tr>
        <tr>
            <td>Poids (réception)</td>
            <th class="text-18 ticket-poids_reception"><?php echo $lot->getPoids_reception() > 0 ? number_format($lot->getPoids_reception(), 3, '.', ' ') . ' kgs' : $na; ?></th>
        </tr>
        <tr class="ticket-temperatures-conteneur <?php echo $lot->getReception() == null ? 'ticket-hide' : ''; ?>">
            <td>Température :</td>
            <th class="ticket-temperatures"><?php
                                            if ($lot->getReception() instanceof LotReception) {

                                                // Abats (une seule température
                                                if ($lot->getComposition() == 2) {
                                                    echo $lot->getReception()->getTemp() . '°C';
                                                } else {
                                                    echo '<em>D</em>' . $lot->getReception()->getTemp_d() . ' °C<br>';
                                                    echo '<em>M</em>' . $lot->getReception()->getTemp_m() . ' °C<br>';
                                                    echo '<em>F</em>' . $lot->getReception()->getTemp_f() . ' °C';
                                                }
                                            } else {
                                                echo $na;
                                            } ?></th>
        </tr>
        <tr class="ticket-etat-visuel-conteneur <?php echo $etat_visuel == -1 ? 'ticket-hide' : ''; ?>">
            <td>Etat visuel :</td>
            <th class="ticket-etat-visuel"><?php echo $etat_visuel_verbose; ?></th>
        </tr>
        <tr class="ticket-validation-conteneur <?php echo $validation == -1 ? 'ticket-hide' : ''; ?>">
            <td>Validation :</td>
            <th class="ticket-validation"><?php echo $validation_verbose; ?></th>
        </tr>

    </table>

    <?php
    // Incidents
    $incidentsManager = new IncidentsManager($cnx);
    $params = ['id_lot' => $id_lot];
    $incidentsLot = $incidentsManager->getIncidentsListe($params);
    if (empty($incidentsLot)) {
        exit;
    }

    ?>
    <div class="alert alert-dark bg-dark">
        <ul><?php
            foreach ($incidentsLot as $incident) {

                $de = in_array(substr(strtolower($incident->getNom_type_incident()), 0, 1), ['a', 'e', 'i', 'o', 'u', 'é', 'è', 'à']) ? "d'" : "de ";
            ?>

                <li class="text-fin text-16 text-warning">Incident <?php echo $de . strtolower($incident->getNom_type_incident()) . ' ' . $incident->getCodeIncident(); ?></li>

            <?php } ?>
        </ul>
        <button type="button" class="btn btn-secondary btn-lg form-control btnCommentaires text-left" data-toggle="modal" data-target="#modalCommentairesFront" data-id-lot="<?php echo $id_lot; ?>">
            <i class="fa fa-info-circle fa-lg fa-fw vmiddle mr-2"></i>Infos incidents
        </button>
    </div>
    <?php

    exit;
} // FIN mode

/* ------------------------------------------
MODE - Charge le contenu d'une étape de vue
       (Pour le retour des palettes)
-------------------------------------------*/
function modeChargeEtapeRetourPalettes()
{

    global $cnx, $utilisateur;

    $etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;

    /** ----------------------------------------
     * Etape        : 1
     * Description  : Selection du transporteur
     *  ----------------------------------- */
    if ($etape == 1) {
    ?>
        <h2 class="gris-9 mt-4 mb-4 text-center">
            <i class="fa fa-pallet margin-left-15"></i>
            <i class="fa fa-undo position-relative text-16" style="left:-5px;top:-5px;"></i>
            Retour de palettes
        </h2>

        <h4 class="text-center">Sélectionnez le transporteur <i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
        <div class="row justify-content-md-center">
            <?php
            // On récupère les transporteurs
            $tiersManager = new TiersManager($cnx);
            $transporteurs = $tiersManager->getListeTransporteurs();

            if (empty($transporteurs)) { ?>
                <div class="alert alert-warning padding-50 text-center"><span class="text-24">Aucun transporteur disponible !</span>
                    <p class="text-16"><em>Contactez un adminstrateur...</em></p>
                </div>
            <?php }

            // Boucle sur les transporteurs possibles
            foreach ($transporteurs as $trans) {
            ?>
                <div class="col-2 mb-3">
                    <div class="card bg-dark text-white pointeur carte-trans" data-id-trans="<?php echo $trans->getId(); ?>">
                        <div class="card-body text-center height-150 text-28"><?php echo $trans->getNom(); ?></div>
                        <div class="card-footer text-center texte-fin "><i class="fa fa-truck-loading"></i></div>
                    </div>
                </div>

            <?php } // FIN boucle clients
            ?>
        </div>
    <?php

    } // FIN étape

    /** ----------------------------------------
     * Etape        : 2
     * Description  : Nb de palettes
     *  ----------------------------------- */
    if ($etape == 2) {

        $id_trans = isset($_REQUEST['id_trans']) ? intval($_REQUEST['id_trans']) : 0;
        if ($id_trans == 0) {
            exit('Identification transporteur échoué !');
        }

        $tiersManager = new TiersManager($cnx);

        $trans = $tiersManager->getTiers($id_trans);
        if (!$trans instanceof Tiers) {
            exit('Instanciation du transporteur #' . $id_trans . ' échouée !');
        }

    ?>


        <input type="hidden" id="idTrans" value="<?php echo $id_trans; ?>" />
        <div class="row align-content-center">
            <div class="col text-center">
                <h2 class="gris-9 mt-4 mb-4 text-center">
                    <i class="fa fa-pallet margin-left-15"></i>
                    <i class="fa fa-undo position-relative text-16" style="left:-5px;top:-5px;"></i>
                    Retour de palettes<p class="texte-fin text-22"><?php echo $trans->getNom(); ?></p>
                </h2>

                <div class="row justify-content-md-center mt-3">
                    <div class="col-4">
                        <div class="row mt-2">
                            <div class="col-12 text-18">Quantité reçue :</div>
                            <div class="col-12 input-group">
                                <div class="input-group-prepend"><button type="button" class="btn btn-dark text-48 btnMoins pl-5 pr-5"><i class="fa fa-minus"></i></button></div>
                                <input type="text" class="form-control text-4em text-center" placeholder="0" value="" id="palettes_recues" maxlength="3" />
                                <div class="input-group-append"><button type="button" class="btn btn-dark text-48 btnPlus pl-5 pr-5"><i class="fa fa-plus"></i></button></div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12 text-18">Quantité rendue :</div>
                            <div class="col-12 input-group">
                                <div class="input-group-prepend"><button type="button" class="btn btn-dark text-48 btnMoins pl-5 pr-5"><i class="fa fa-minus"></i></button></div>
                                <input type="text" class="form-control text-4em text-center" placeholder="0" value="" id="palettes_rendues" maxlength="3" />
                                <div class="input-group-append"><button type="button" class="btn btn-dark text-48 btnPlus pl-5 pr-5"><i class="fa fa-plus"></i></button></div>
                            </div>
                        </div>
                    </div> <!-- FIN col droite -->


                </div> <!-- FIN conteneur ROW -->
                <div class="row justify-content-md-center mt-4">
                    <div class="col-3">
                        <button type="button" class="btn btn-success form-control text-18 padding-20 btnValiderPalettes"><i class="fa fa-check fa-lg mr-3"></i>Valider</button>
                    </div>
                </div>

            </div>
        </div>
    <?php

    } // FIN étape

    /** ----------------------------------------
     * Etape        : 3
     * Description  : Date
     *  ----------------------------------- */
    if ($etape == 3) {

        $id_trans = isset($_REQUEST['id_trans']) ? intval($_REQUEST['id_trans']) : 0;
        if ($id_trans == 0) {
            exit('Identification transporteur échoué !');
        }

        $tiersManager = new TiersManager($cnx);

        $trans = $tiersManager->getTiers($id_trans);
        if (!$trans instanceof Tiers) {
            exit('Instanciation du transporteur #' . $id_trans . ' échouée !');
        }

        $palettes_recues = isset($_REQUEST['palettes_recues']) ? intval($_REQUEST['palettes_recues']) : 0;
        $palettes_rendues = isset($_REQUEST['palettes_rendues']) ? intval($_REQUEST['palettes_rendues']) : 0;
        if ($palettes_recues == 0 && $palettes_rendues == 0) {
            exit('Récupération des quantités échangées échouée !');
        }

        $ecart = $palettes_recues - $palettes_rendues;

    ?>
        <input type="hidden" id="idTrans" value="<?php echo $id_trans; ?>" />
        <input type="hidden" id="palettes_recues" value="<?php echo $palettes_recues; ?>" />
        <input type="hidden" id="palettes_rendues" value="<?php echo $palettes_rendues; ?>" />
        <div class="row align-content-center">
            <div class="col text-center">
                <h2 class="gris-9 mt-4 mb-4 text-center">
                    <i class="fa fa-pallet margin-left-15"></i>
                    <i class="fa fa-undo position-relative text-16" style="left:-5px;top:-5px;"></i>
                    Retour de palettes<p class="texte-fin text-22"><?php echo $trans->getNom() . '(' . $ecart . ')'; ?></p>
                </h2>

                <div class="row justify-content-md-center mt-3">
                    <div class="col-2">
                        <select class="selectpicker form-control selectpicker-tactile retpal-jour" data-size="12">
                            <?php
                            for ($j = 1; $j <= 31; $j++) { ?>
                                <option value="<?php echo $j; ?>" <?php echo $j == date('d') ? 'selected' : ''; ?>><?php echo $j; ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <select class="selectpicker form-control selectpicker-tactile retpal-mois" data-size="11">
                            <?php
                            for ($m = 1; $m <= 12; $m++) { ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == date('m') ? 'selected' : ''; ?>><?php echo ucfirst(Outils::getMoisIntListe()[$m]); ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>
                    <div class="col-2">
                        <select class="selectpicker form-control selectpicker-tactile retpal-an" data-size="12">
                            <?php
                            for ($a = intval(date('Y')) - 1; $a <= intval(date('Y')); $a++) { ?>
                                <option value="<?php echo $a; ?>" <?php echo $a == date('Y') ? 'selected' : ''; ?>><?php echo $a; ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row justify-content-md-center">
                    <div class="col-2 mt-3">
                        <button type="button" class="btn btn-success btn-lg form-control padding-20 btnValideRetPalDate"><i class="fa fa-check mr-1"></i> Valider</button>
                    </div>
                </div>
            <?php

        } // FIN étape

        /** ----------------------------------------
         * Etape        : 4
         * Description  : Signature et fin
         *  ----------------------------------- */
        if ($etape == 4) {

            $id_trans = isset($_REQUEST['id_trans']) ? intval($_REQUEST['id_trans']) : 0;
            if ($id_trans == 0) {
                exit('Identification transporteur échoué !');
            }

            $tiersManager = new TiersManager($cnx);

            $trans = $tiersManager->getTiers($id_trans);
            if (!$trans instanceof Tiers) {
                exit('Instanciation du transporteur #' . $id_trans . ' échouée !');
            }

            $palettes_recues = isset($_REQUEST['palettes_recues']) ? intval($_REQUEST['palettes_recues']) : 0;
            $palettes_rendues = isset($_REQUEST['palettes_rendues']) ? intval($_REQUEST['palettes_rendues']) : 0;
            if ($palettes_recues == 0 && $palettes_rendues == 0) {
                exit('Récupération des quantités échangées échouée !');
            }

            $ecart = $palettes_recues - $palettes_rendues;

            $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
            if (!Outils::verifDateSql($date)) {
                $date = date('Y-m-d');
            }
            ?>

                <input type="hidden" id="idTrans" value="<?php echo $id_trans; ?>" />
                <input type="hidden" id="palettes_recues" value="<?php echo $palettes_recues; ?>" />
                <input type="hidden" id="palettes_rendues" value="<?php echo $palettes_rendues; ?>" />
                <input type="hidden" id="dateRetPal" value="<?php echo $date; ?>" />

                <div class="row align-content-center">
                    <div class="col text-center">
                        <h2 class="gris-9 mt-4 mb-4 text-center">
                            <i class="fa fa-pallet margin-left-15"></i>
                            <i class="fa fa-undo position-relative text-16" style="left:-5px;top:-5px;"></i>
                            Retour de palettes<p class="texte-fin text-22"><?php echo $trans->getNom() . '(' . $ecart . ')'; ?></p>
                        </h2>

                        <div class="row justify-content-md-center mt-1">
                            <div class="col-12">
                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Signature du transporteur :</h4>
                            </div>
                            <div class="col-12">
                                <div id="signature"></div>
                            </div>

                            <div class="col-1">
                                <button type="button" class="btn btn-warning btn-lg form-control padding-25 text-30 btnEffacer"><i class="fa fa-eraser fa-lg"></i></button>
                            </div>
                            <div class="col-3">
                                <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnFin"><i class="fa fa-check fa-lg mr-3"></i>Terminé</button>
                            </div>
                        </div>

                    </div>
                </div>
            <?php

        } // FIN étape

        /** ----------------------------------------
         * Etape        : 5
         * Description  : Enregistrement
         *  ----------------------------------- */
        if ($etape == 5) {

            $id_trans = isset($_REQUEST['id_trans']) ? intval($_REQUEST['id_trans']) : 0;
            if ($id_trans == 0) {
                exit('Identification transporteur échoué !');
            }

            $palettes_recues = isset($_REQUEST['palettes_recues']) ? intval($_REQUEST['palettes_recues']) : 0;
            $palettes_rendues = isset($_REQUEST['palettes_rendues']) ? intval($_REQUEST['palettes_rendues']) : 0;
            if ($palettes_recues == 0 && $palettes_rendues == 0) {
                exit('Récupération des quantités échangées échouée !');
            }

            $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
            if (!Outils::verifDateSql($date)) {
                $date = date('Y-m-d');
            }

            $signature = isset($_REQUEST['signature']) ? $_REQUEST['signature'] : '';

            $retourPaletteRcp = new RetourPaletteRcp([]);
            $retourPaletteRcp->setId_transporteur($id_trans);
            $retourPaletteRcp->setDate_retour($date);;
            $retourPaletteRcp->setPalettes_recues($palettes_recues);
            $retourPaletteRcp->setPalettes_rendues($palettes_rendues);
            $retourPaletteRcp->setId_user($utilisateur->getId());
            $retourPaletteRcp->setDate_add(date('Y-m-d H:i:s'));

            $retourPaletteRcpManager = new RetourPaletteRcpsManager($cnx);
            $id_retpal = $retourPaletteRcpManager->saveRetourPaletteRcp($retourPaletteRcp);
            if (intval($id_retpal) == 0) {
                exit;
            }



            $ecart = $palettes_recues - $palettes_rendues;
            if ($ecart != 0) {
                $tiersManager = new TiersManager($cnx);
                $trans = $tiersManager->getTiers($id_trans);
                if (!$trans instanceof Tiers) {
                    exit;
                }
                $newSolde = $trans->getSolde_palettes() + $ecart;
                $trans->setSolde_palettes($newSolde);
                if (!$tiersManager->saveTiers($trans)) {
                    exit;
                }
            }


            // On enregistre la signature si il y en a une
            if ($signature != '') {
                $base64_str = str_replace('data:image/png;base64,', '', $signature);
                $base64_str = str_replace(' ', '+', $base64_str);
                $decoded = base64_decode($base64_str);
                $png_url = __CBO_UPLOADS_PATH__ . 'signatures/rcp/' . intval($id_retpal) . ".png";
                file_put_contents($png_url, $decoded);
            }

            echo '1';
            exit;
        } // FIN étape
        exit;
    } // FIN mode

    /* ------------------------------------------
MODE - Charge le contenu d'une étape de vue
-------------------------------------------*/
    function modeChargeEtapeVue()
    {

        global $cnx, $utilisateur, $lotsManager;

        $etape  = isset($_REQUEST['etape'])     ? intval($_REQUEST['etape'])            : 0;
        $id_lot = isset($_REQUEST['id_lot'])    ? intval($_REQUEST['id_lot'])           : 0;

        if ($etape == 0 || $id_lot == 0) {
            erreurLot();
            exit;
        }
        $lot = $lotsManager->getLot($id_lot);
        if (!$lot instanceof Lot) {
            erreurLot();
        };

        /** ----------------------------------------
         * DEV - On affiche l'étape pour débug
         *  ----------------------------------- */
        if ($utilisateur->isDev()) { ?>
                <div class="dev-etape-vue"><i class="fa fa-user-secret fa-lg mr-1"></i>Etape <kbd><?php echo $etape; ?></kbd></div>
            <?php } // FIN test DEV

        /** ----------------------------------------
         * Etape        : 2
         * Description  : Compléter les infos du lot
         *  ----------------------------------- */
        if ($etape == 2) {    ?>


                <div class="row mt-5 align-content-center">
                    <div class="col text-center">
                        <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Confirmer la réception du lot :</h4>
                        <button type="button" class="btn btn-lg btn-success text-30 padding-50 btn-confirme-reception text-center"><i class="fa fa-truck mr-2 fa-lg"></i><br>Lot réceptionné aujourd'hui <i class="fa fa-check-square fa-sm ml-2"></i> </button>
                    </div>
                </div>

            <?php
            exit;
        } // FIN ETAPE


        /** ----------------------------------------
         * Etape        : 3
         * Description  : Contrôle températures
         *  ----------------------------------- */
        if ($etape == 3) {

            $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
            if ($id_lot == 0) {
                erreurLot();
            };

            $lot = $lotsManager->getLot($id_lot);
            if (!$lot instanceof Lot) {
                erreurLot();
            };


            /**
             * Si Abats, on a juste la température du camion
             * Si viande, on controle 3 niveau de températures
             */

            ?>
                <div class="row mt-5 align-content-center">
                    <div class="col-5 text-center">
                        <div class="alert alert-secondary">
                            <div class="input-group clavier-temperature">
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="1">1</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="2">2</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="3">3</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="4">4</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="5">5</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="6">6</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="7">7</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="8">8</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="9">9</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur=".">.</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="0">0</button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur="S">+/-</button></div>
                                <div class="col-8"><button type="button" class="form-control mb-2 btn btn-success btn-large" data-valeur="V" data-id-lot="<?php echo $lot->getId(); ?>"><i class="fa fa-check"></i></button></div>
                                <div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger btn-large" data-valeur="C"><i class="fa fa-backspace"></i></button></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-7">
                        <form class="alert alert-secondary" id="formTemperatures">
                            <input type="hidden" name="mode" value="actionSaveTemperatures" />
                            <input type="hidden" name="id_lot" value="<?php echo $lot->getId(); ?>" />
                            <?php
                            // Si viande : 3 niveaux de température
                            if ($lot->getComposition() == 1 && $lot->getComposition_viande() != 1) { ?>

                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Témpératures:</h4>
                                <div class="row margin-bottom-20">
                                    <div class="col input-group">
                                        <div class="input-group-prepend prepend-fixe-temp">
                                            <span class="input-group-text text-center text-48">D</span>
                                        </div>
                                        <input type="text" class="form-control text-5em text-center" placeholder="00.00" value="" name="tempd" maxlength="15" />
                                        <div class="input-group-append">
                                            <span class="input-group-text text-48"> °C&nbsp;</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row margin-bottom-20">
                                    <div class="col input-group">
                                        <div class="input-group-prepend prepend-fixe-temp">
                                            <span class="input-group-text text-center text-48">M</span>
                                        </div>
                                        <input type="text" class="form-control text-5em text-center" placeholder="00.00" value="" name="tempm" maxlength="15" />
                                        <div class="input-group-append">
                                            <span class="input-group-text text-48"> °C&nbsp;</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col input-group">
                                        <div class="input-group-prepend prepend-fixe-temp">
                                            <span class="input-group-text text-center text-48">F</span>
                                        </div>
                                        <input type="text" class="form-control text-5em text-center" placeholder="00.00" value="" name="tempf" maxlength="15" />
                                        <div class="input-group-append">
                                            <span class="input-group-text text-48"> °C&nbsp;</span>
                                        </div>
                                    </div>
                                </div>

                            <?php
                                // Abats : 1 température
                            } else { ?>

                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Témpérature :</h4>
                                <div class="row">
                                    <div class="col input-group">
                                        <input type="text" class="form-control text-6em text-center" placeholder="00.00" value="" name="temp" maxlength="15" />
                                        <div class="input-group-append">
                                            <span class="input-group-text text-48"> °C&nbsp;</span>
                                        </div>
                                    </div>
                                </div>

                            <?php
                            } // FIN test type composition pour températures
                            ?>


                        </form>
                        <?php
                        $configManager = new ConfigManager($cnx);
                        $config_tmp_rcp_min = $configManager->getConfig('tmp_rcp_' . $lot->getClefConfigCompositionTemp() . '_min');
                        $config_tmp_rcp_max = $configManager->getConfig('tmp_rcp_' . $lot->getClefConfigCompositionTemp() . '_max');
                        $config_tmp_rcp_tol = $configManager->getConfig('tmp_rcp_' . $lot->getClefConfigCompositionTemp() . '_tol');

                        if ($config_tmp_rcp_min instanceof Config && $config_tmp_rcp_max instanceof Config && $config_tmp_rcp_tol instanceof Config) {
                        ?>
                            <div class="alert alert-light temp-controles" data-temp-controle-min="<?php echo intval($config_tmp_rcp_min->getValeur()); ?>" data-temp-controle-max="<?php echo intval($config_tmp_rcp_tol->getValeur()); ?>">
                                <div class="row">
                                    <div class="col">
                                        <p class="gris-7" id="consignesTemp"><i class="fa fa-info-circle mr-1"></i>Températures de réception pour conformité :
                                            de <span class="text-18 text-info"><?php
                                                                                echo intval($config_tmp_rcp_min->getValeur()); ?>°C</span> à <span class="text-18 text-info"><?php
                                                                                                                                                                                echo intval($config_tmp_rcp_max->getValeur()); ?>°C</span> avec tolérance <span class="text-18 text-info"><?php
                                                                                                                                                                                                                                                                                            echo intval($config_tmp_rcp_tol->getValeur()); ?>°C</span> <?php
                                                                                                                                                                                                                                                                                                                                                        echo $lot->getComposition_viande_verbose(true) != '' ? $lot->getComposition_viande_verbose(true) : '(abats)'; ?>

                                        </p>
                                    </div>
                                </div>
                            <?php
                        } // FIN test instancition des objets de configuration des températures de réception
                            ?>

                            <div class="d-none alert alert-danger temperatureInvalide">
                                <i class="fa fa-exclamation-circle fa-3x float-left mr-3"></i> <strong>Attention !</strong>
                                <p>Température <span class="type-temperature-invalide-txt"></span> invalide.</p>
                            </div>
                            </div>
                    </div>


                <?php
                exit;
            } // FIN ETAPE


            /** ----------------------------------------
             * Etape        : 4
             * Description  : Etat visuel / Photo
             *  ----------------------------------- */
            if ($etape == 4) {

                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    erreurLot();
                };

                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    erreurLot();
                };

                ?>
                    <div class="row mt-5 align-content-center">
                        <div class="col-6 offset-3 mb-3">
                            <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Etat visuel du lot :</h4>
                        </div>
                        <div class="col-3 offset-3">
                            <button type="button" class="btn btn-danger btn-lg form-control padding-25 text-30 btnValideEtatVisuel" data-id-lot="<?php echo $lot->getId(); ?>" data-etat-visuel="0"><i class="fa fa-times fa-lg mr-3"></i>Contestable</button>
                        </div>
                        <div class="col-3">
                            <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnValideEtatVisuel" data-id-lot="<?php echo $lot->getId(); ?>" data-etat-visuel="1"><i class="fa fa-check fa-lg mr-3"></i>Satisfaisant</button>
                        </div>
                    </div>

                <?php
            } // FIN ETAPE

            /** ----------------------------------------
             * Etape        : 5
             * Description  : Conformité / Observations
             *  ----------------------------------- */
            if ($etape == 5) {

                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    erreurLot();
                };

                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    erreurLot();
                };

                $pvisuManager =  new PvisuAvantManager($cnx);
                $pointsControlesManager = new PointsControleManager($cnx);
                $points = $pointsControlesManager->getListePointsControles(false, 3);

                $bo = false;

                $id_avant = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
                if ($id_avant > 0) {
                    // Admin - edite un avant
                    $pvisu = $pvisuManager->getPvisuAvant($id_avant);
                    $bo = true;
                } else {
                    // On récupère les données enregistrées du jour s'il y en a
                    $pvisu = $pvisuManager->getPvisuAvantJour('', false);
                }
                if (!$pvisu instanceof PvisuAvant) {
                    $pvisu = new PvisuAvant([]);
                }

                $visuPoints = $pvisuManager->getListePvisuAvantPoints($pvisu, true);
                if (!is_array($visuPoints)) {
                    $visuPoints = [];
                }

                if ($pvisu->getDate() == '') {
                    $pvisu->setDate(date('Y-m-d'));
                }
                ?>
                    <div>
                        <form class="alert alert-secondary row mt-5 align-content-center bg-white border-0" id="observationsReception">
                            <input type="hidden" name="id_lot" value="<?php echo $lot->getId(); ?>" />
                            <input type="hidden" name="mode" value="actionConformiteReception" />
                            <input type="hidden" name="conformite" value="" />
                            <input type="hidden" name="bo" value="<?php echo $bo ? '1' : '0'; ?>" />
                            <div class="col-6 offset-3 mb-3">
                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Observations :</h4>                                
                                <textarea class="form-control text-20" placeholder="Remarques sur la réception du lot (facultatif)..." name="observations" id="champ_clavier">
                                    <?php
                                    // Si on a déjà une reception avec des obeservations, on prérempli pour ne pas avoir à ressaisir si l'opérateur est revenu en arrière
                                    if ($lot->getReception() instanceof LotReception) {
                                       
                                        echo strip_tags($lot->getReception()->getObservations());
                                    } // FIN test récept
                                    ?>
                                </textarea>
                            </div>
                            <div class="col-6 offset-3 mb-3">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <td class="border-0 text-28 gris-9 pt-2"><?php echo Outils::getDate_only_verbose($pvisu->getDate(), true, false); ?></td>
                                            <td class="border-0 text-center text-success pt-4 pb-2"><i class="fa fa-check mr-1"></i> Satisfaisant</td>
                                            <td class="border-0 text-center nowrap text-danger pt-4 pb-2"><i class="fa fa-times mr-1"></i>Non-satisfaisant</td>
                                        </tr>
                                    </thead>
                                    <?php
                                    foreach ($points as $point) { ?>
                                        <tr class="<?php echo $point->getId_parent() == 0 ? 'bg-info text-white ' : ''; ?>">
                                            <td class="<?php echo $point->getId_parent() == 0 ? 'pl-3 text-28' : 'pl-5 text-18'; ?>">
                                                <?php echo $point->getNom(); ?>
                                            </td>
                                            <td class="text-center ichecktout">
                                                <?php if ($point->getId_parent() == 0 && (int)$pvisu->getId_user_validation() == 0) { ?>
                                                    <button type="button" class="btn btn-success btn-sm border padding-5-10 margin-top-5 btnToutOk" data-id-parent="<?php echo $point->getId(); ?>"><i class="fa fa-check mr-1"></i> Tout OK</button>
                                                <?php } else if ($point->getId_parent() == 0 && $bo) { ?>

                                                <?php } else if ($point->getId_parent() > 0) { ?>
                                                    <input type="radio" class="icheck icheck-pvisu icheck-vert parent-<?php
                                                                                                                        echo $point->getId_parent(); ?>" name="point[<?php echo $point->getId(); ?>]" value="1" <?php
                                                                                                                                                                                                                echo isset($visuPoints[$point->getId()]) && (int)$visuPoints[$point->getId()] == 1 ? 'checked' : '';
                                                                                                                                                                                                                echo (int)$pvisu->getId_user_validation() > 0 && !$bo ? ' disabled' : ''; ?>>
                                                <?php } ?>

                                            </td>
                                            <td class="text-center ichecktout">
                                                <?php if ($point->getId_parent() > 0) { ?>
                                                    <input type="radio" class="icheck icheck-pvisu icheck-rouge" name="point[<?php echo $point->getId(); ?>]" value="0" <?php
                                                                                                                                                                        echo isset($visuPoints[$point->getId()]) && (int)$visuPoints[$point->getId()] == 0 ? 'checked' : '';
                                                                                                                                                                        echo (int)$pvisu->getId_user_validation() > 0 && !$bo ? ' disabled' : ''; ?>>
                                                <?php } ?>
                                            </td>

                                        </tr>
                                    <?php } // FIN boucle sur les points de contrôle
                                    ?>
                                </table>
                            </div>
                            <div class="col-6 offset-3 mb-3">
                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Validation du réceptionniste :</h4>
                                <div class="alert alert-info text-center">Vérification de la conformité des estampilles sanitaires prévues<br>lors de la création des lots et en réelles lors de la réception. </div>
                            </div>
                            <?php

                            // On compare la température de réception du lot avec la configuration pour forcer une éventuelle non-conformité
                            $configManager          = new ConfigManager($cnx);
                            $lotReceptionManager    = new LotReceptionManager($cnx);

                            $config_tmp_rcp_min     = $configManager->getConfig('tmp_rcp_' . $lot->getClefConfigCompositionTemp() . '_min');
                            $config_tmp_rcp_tol     = $configManager->getConfig('tmp_rcp_' . $lot->getClefConfigCompositionTemp() . '_tol');
                            $lotReception           = $lotReceptionManager->getLotReceptionByIdLot($id_lot);

                            $temperatureHorsNormes = false;

                            if (
                                $lotReception &&
                                $lotReception instanceof LotReception &&
                                $config_tmp_rcp_min instanceof Config &&
                                $config_tmp_rcp_tol instanceof Config
                            ) {

                                // Abats
                                if ($lot->getComposition() == 2) {

                                    // KO si la température (unique) est < au minium (0°)
                                    if (intval($lotReception->getTemp()) <  intval($config_tmp_rcp_min->getValeur())) {
                                        $temperatureHorsNormes = true;
                                    }

                                    // KO si la température (unique) est > au maximal toléré
                                    if (intval($lotReception->getTemp()) >  intval($config_tmp_rcp_tol->getValeur())) {
                                        $temperatureHorsNormes = true;
                                    }

                                    // Viandes
                                } else {

                                    // On retiens la plus faible des 3 températures
                                    $temp_faible = min([
                                        intval($lotReception->getTemp_d()),
                                        intval($lotReception->getTemp_m()),
                                        intval($lotReception->getTemp_f())
                                    ]);

                                    // Et la plus forte
                                    $temp_forte = max([
                                        intval($lotReception->getTemp_d()),
                                        intval($lotReception->getTemp_m()),
                                        intval($lotReception->getTemp_f())
                                    ]);

                                    // KO si la température la plus faible est < au minimum (0°C)
                                    if (intval($temp_faible) <  intval($config_tmp_rcp_min->getValeur())) {
                                        $temperatureHorsNormes = true;
                                    }

                                    // KO si la température la plus élevée est > au maximal toléré
                                    if (intval($temp_forte) >  intval($config_tmp_rcp_tol->getValeur())) {
                                        $temperatureHorsNormes = true;
                                    }
                                } // FIN test composition

                            } // FIN test instanciation des objets requis




                            ?>

                            <div class="col-3 offset-3">
                                <button type="button" class="btn btn-danger btn-lg form-control padding-25 text-30 btnConformiteLot" data-conformite="0" id="non-conforme-button"><i class="fa fa-times fa-lg mr-3"></i>Non Conforme</button>
                            </div>
                            <div class="col-3">
                            <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnConformiteLot" data-conformite="1" id="conforme-button" <?php echo $temperatureHorsNormes ? 'disabled' : ''; ?>><i class="fa fa-check fa-lg mr-3"></i>Conforme</button>
                            </div>



                        </form>
                    </div>


                    <?php

                    if ($temperatureHorsNormes) { ?>
                        <div class="row mt-5 align-content-center">
                            <div class="col-6 offset-3 mb-3">
                                <div class="alert alert-danger">
                                    <h5 class="text-uppercase"><i class="fa fa-exclamation-circle mr-2"></i>Températures hors norme</h5>
                                    <hr>
                                    <p>Les relevés de températures saisis ne correspondent pas aux critères tolérés !<br>La conformité du lot ne peut être validée.
                                        Contactez un responsable.<br>
                                        Cliquez sur <span class="badge badge-danger text-16">Non Conforme</span> pour continuer&hellip;</p>
                                </div>
                            </div>
                        </div>


                    <?php }
                } // FIN ETAPE

                /** ----------------------------------------
                 * Etape        : 6
                 * Description  : FIN Réception / Récap'
                 *  ----------------------------------- */
                if ($etape == 6) {

                    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                    if ($id_lot == 0) {
                        erreurLot();
                    };

                    $lot = $lotsManager->getLot($id_lot);
                    if (!$lot instanceof Lot) {
                        erreurLot();
                    };
                    ?>
                    <div class="row mt-5 justify-content-center">
                        <div class="col-6 mb-3 text-center">
                            <div class="alert alert-secondary padding-50">
                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Réception du lot <?php echo $lot->getNumlot(); ?></h4>
                                <div class="row">
                                    <div class="col-6 offset-3">
                                        <button type="button" class="btn btn-lg btn-success text-30 mt-3 btnConfirmerReception w-100" data-id-lot="<?php
                                                                                                                                                    echo $lot->getId(); ?>"><i class="fa fa-clipboard-check fa-lg mr-3"></i> Confirmer<p class="mb-0 mt-1 text-20">Réception terminée</p></button>
                                    </div>
                                    <div class="col-6 offset-3">
                                        <button type="button" class="btn btn-lg btn-danger text-26 mt-1 btnRetourEtape10 w-100 padding-20-40" data-id-lot="<?php echo $lot->getId(); ?>">
                                            <i class="fa fa-undo fa-lg mr-2"></i> Retour
                                        </button>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                <?php
                } // FIN ETAPE

                /** ----------------------------------------
                 * Etape        : 7
                 * Description  : Lot de négoce - T°
                 *  ----------------------------------- */
                if ($etape == 7) {

                    $lotNegoceManager = new LotNegoceManager($cnx);
                    $lotNegoce = $lotNegoceManager->getLotNegoce($id_lot);
                    if (!$lotNegoce instanceof LotNegoce) {
                        erreurLot();
                        exit;
                    }
                ?>
                    <div class="row mt-5 align-content-center">
                        <div class="col-5 text-center">
                            <div class="alert alert-secondary">
                                <div class="input-group clavier-temperature">
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="1">1</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="2">2</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="3">3</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="4">4</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="5">5</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="6">6</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="7">7</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="8">8</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="9">9</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur=".">.</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large" data-valeur="0">0</button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur="S">+/-</button></div>
                                    <div class="col-8"><button type="button" class="form-control mb-2 btn btn-success btn-large" data-valeur="V" data-id-lot="<?php
                                                                                                                                                                echo $lotNegoce->getId(); ?>"><i class="fa fa-check"></i></button></div>
                                    <div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger btn-large" data-valeur="C"><i class="fa fa-backspace"></i></button></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-7">
                            <form class="alert alert-secondary" id="formTemperatureNegoce">
                                <input type="hidden" name="mode" value="actionSaveTemperatureNegoce" />
                                <input type="hidden" name="id_lot" value="<?php echo $lotNegoce->getId(); ?>" />

                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Témpérature :</h4>
                                <div class="row">
                                    <div class="col input-group">
                                        <input type="text" class="form-control text-6em text-center" placeholder="00.00" value="" name="temp" maxlength="15" />
                                        <div class="input-group-append">
                                            <span class="input-group-text text-48"> °C&nbsp;</span>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="d-none alert alert-danger temperatureInvalide">
                                <i class="fa fa-exclamation-circle fa-3x float-left mr-3"></i> <strong>Attention !</strong>
                                <p>Température <span class="type-temperature-invalide-txt"></span> invalide.</p>
                            </div>
                        </div>
                    </div>




                    <?php
                    exit;
                } // FIN ETAPE

                /** ----------------------------------------
                 * Etape        : 8
                 * Description  : Lot de négoce - Confirm
                 *  ----------------------------------- */
                if ($etape == 8) {

                    $lotNegoceManager = new LotNegoceManager($cnx);
                    $lotNegoce = $lotNegoceManager->getLotNegoce($id_lot);
                    if (!$lotNegoce instanceof LotNegoce) {
                        erreurLot();
                        exit;
                    }

                    // Si pas de date d'entrée, on demande de confirmé la reception du lot
                    if ($lotNegoce->getDate_entree() == '' || $lotNegoce->getDate_entree() == '0000-00-00') { ?>

                        <div class="row mt-5 align-content-center">
                            <div class="col text-center">
                                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Confirmer la réception du lot :</h4>
                                <button type="button" class="btn btn-lg btn-success text-30 padding-50 btn-confirme-reception text-center"><i class="fa fa-truck mr-2 fa-lg"></i><br>Lot réceptionné aujourd'hui <i class="fa fa-check-square fa-sm ml-2"></i> </button>
                            </div>
                        </div>
                    <?php
                        // Lot déja réceptionné
                    } else {

                    ?>
                        <div class="row mt-5 align-content-center">
                            <div class="col-3"></div>
                            <div class="col-6 text-center">
                                <div class="alert alert-info text-28 padding-50">
                                    Lot réceptionné le <?php echo Outils::dateSqlToFr($lotNegoce->getDate_entree()); ?>
                                </div>
                            </div>
                        </div>
                    <?php

                    } // FIN test lot entré
                    ?>



                <?php
                    exit;
                } // FIN ETAPE


                /** ----------------------------------------
                 * Etape        : 10
                 * Description  : Gestion stock crochets
                 *  ----------------------------------- */
                if ($etape == 10) {    ?>


                    <div class="row mt-5 align-content-center">
                        <div class="col text-center">
                            <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Gestion du stock crochets</h4>

                            <div class="row justify-content-md-center mt-3">
                                <div class="col-4">
                                    <div class="row mt-2">
                                        <div class="col-12 text-18">Quantité reçue :</div>
                                        <div class="col-12 input-group">
                                            <div class="input-group-prepend"><button type="button" class="btn btn-dark text-48 btnMoins pl-5 pr-5"><i class="fa fa-minus"></i></button></div>
                                            <input type="text" class="form-control text-4em text-center" placeholder="0" value="" id="crochets_recus" maxlength="3" />
                                            <div class="input-group-append"><button type="button" class="btn btn-dark text-48 btnPlus pl-5 pr-5"><i class="fa fa-plus"></i></button></div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12 text-18">Quantité rendue :</div>
                                        <div class="col-12 input-group">
                                            <div class="input-group-prepend"><button type="button" class="btn btn-dark text-48 btnMoins pl-5 pr-5"><i class="fa fa-minus"></i></button></div>
                                            <input type="text" class="form-control text-4em text-center" placeholder="0" value="" id="crochets_rendus" maxlength="3" />
                                            <div class="input-group-append"><button type="button" class="btn btn-dark text-48 btnPlus pl-5 pr-5"><i class="fa fa-plus"></i></button></div>
                                        </div>
                                    </div>
                                </div> <!-- FIN col droite -->


                            </div> <!-- FIN conteneur ROW -->
                            <div class="row justify-content-md-center mt-4">
                                <div class="col-3">
                                    <button type="button" class="btn btn-success form-control text-18 padding-20 btnValiderCrochets"><i class="fa fa-check fa-lg mr-3"></i>Valider</button>
                                </div>
                            </div>

                        </div>
                    </div>

                <?php
                    exit;
                } // FIN ETAPE

                /** ----------------------------------------
                 * Etape        : 12
                 * Description  : Sélection transporteur
                 *  ----------------------------------- */
                if ($etape == 12) { ?>

                    <div class="row mt-5 align-content-center">
                        <div class="col text-center">
                            <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Sélection du transporteur pour l'échange de crochets</h4>

                            <div class="row justify-content-md-center mt-3">

                                <?php
                                // On récupère les transporteurs
                                $tiersManager = new TiersManager($cnx);
                                $transporteurs = $tiersManager->getListeTransporteurs();

                                if (empty($transporteurs)) { ?>
                                    <div class="alert alert-warning padding-50 text-center"><span class="text-24">Aucun transporteur disponible !</span>
                                        <p class="text-16"><em>Contactez un adminstrateur...</em></p>
                                    </div>
                                <?php }

                                // Boucle sur les transporteurs possibles
                                foreach ($transporteurs as $trans) {
                                ?>
                                    <div class="col-2 mb-3">
                                        <div class="card bg-dark text-white pointeur carte-trans" data-id-trans="<?php echo $trans->getId(); ?>">
                                            <div class="card-body text-center height-150 text-28"><?php echo $trans->getNom(); ?></div>
                                            <div class="card-footer text-center texte-fin "><i class="fa fa-truck-loading"></i></div>
                                        </div>
                                    </div>

                                <?php } // FIN boucle clients
                                ?>

                            </div> <!-- FIN conteneur ROW -->

                        </div>
                    </div>

                <?php } // FIN étape


                exit;
            } // FIN mode


            /* -------------------------------------------
MODE - ACTION : Enregistre la réception du lot
--------------------------------------------*/
            function modeActionValideReception()
            {

                global
                    $cnx,
                    $utilisateur,
                    $lotsManager;

                // On vérifie qu'on a bien un lot valise et un utilisateur en session...
                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    exit;
                }
                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit;
                }
                if (!isset($utilisateur) || !$utilisateur instanceof User) {
                    exit;
                }

                // Enregistrement des données dans le lot...
                $lot->setDate_reception(date('Y-m-d'));
                $lot->setDate_maj(date('Y-m-d- H:i:s'));
                $lot->setId_user_maj($utilisateur->getId());

                if (!$lotsManager->saveLot($lot)) {
                    exit;
                }

                // Log
                $log = new Log([]);
                $log->setLog_type('info');
                $log->setLog_texte("[RCP] Validation de la réception du lot ID " . $id_lot);
                $logsManager = new LogManager($cnx);
                $logsManager->saveLog($log);

                echo 3; // Etape suivante

                exit;
            } // FIN mode

            /* -----------------------------------------------------
MODE - ACTION : Enregistre la réception du lot de négoce
------------------------------------------------------*/
            function modeActionValideReceptionNegoce()
            {

                global
                    $cnx,
                    $utilisateur;

                $lotNegoceManage = new LotNegoceManager($cnx);

                // On vérifie qu'on a bien un lot valise et un utilisateur en session...
                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    exit;
                }
                $lot = $lotNegoceManage->getLotNegoce($id_lot);
                if (!$lot instanceof LotNegoce) {
                    exit;
                }
                if (!isset($utilisateur) || !$utilisateur instanceof User) {
                    exit;
                }

                // Enregistrement des données dans le lot...
                $lot->setDate_entree(date('Y-m-d'));
                $lot->setDate_maj(date('Y-m-d- H:i:s'));
                $lot->setId_user_maj($utilisateur->getId());

                if (!$lotNegoceManage->saveLotNegoce($lot)) {
                    exit;
                }

                // Validation admin
                $vuesManager = new VueManager($cnx);
                $vueRcp = $vuesManager->getVueByCode('rcp');
                if (!$vueRcp instanceof Vue) {
                    exit;
                }
                $validation = new Validation([]);
                $validation->setType(4); // Type 4 = Réception lot de négoce
                $validation->setId_vue($vueRcp->getId());
                $validation->setId_liaison($id_lot);
                $validationManager = new ValidationManager($cnx);
                if ($validationManager->saveValidation($validation)) {
                    // On associe tous le lot en relationnel
                    $validationManager->addValidationLot($validation, $id_lot, true);
                }

                // Log
                $log = new Log([]);
                $log->setLog_type('info');
                $log->setLog_texte("[RCP] Validation de la réception du lot de négoce ID " . $id_lot);
                $logsManager = new LogManager($cnx);
                $logsManager->saveLog($log);

                exit;
            } // FIN mode


            /* -----------------------------------------------
MODE - ACTION : Enregistre la température (Negoce)
------------------------------------------------*/
            function modeActionSaveTemperatureNegoce()
            {

                global
                    $cnx,
                    $utilisateur,
                    $lotsManager;

                $lotsNegoceManager = new LotNegoceManager($cnx);

                // On vérifie qu'on a bien un lot valise, une date de DLC et un utilisateur en session...
                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    exit('A');
                }

                // On évite false ou 0.0 car on peux avoir une température à 0°
                $temp   = isset($_REQUEST['temp'])  ? floatval($_REQUEST['temp'])  : 999;


                // Si aucune température
                if ($temp > 900) {
                    exit('C');
                }

                // On vérifie que l'objet lot existe bien, on récupère l'objet réception et on vérifie l'user
                $lotNegoce = $lotsNegoceManager->getLotNegoce($id_lot);
                if (!$lotNegoce instanceof LotNegoce) {
                    exit('D');
                }
                if (!isset($utilisateur) || !$utilisateur instanceof User) {
                    exit('F');
                }

                // Enregistrement des données dans le lot...

                $lotNegoce->setTemp($temp);
                $lotNegoce->setDate_maj(date('Y-m-d H:i:s'));
                $lotNegoce->setId_user_maj($utilisateur->getId());

                if (!$lotsNegoceManager->saveLotNegoce($lotNegoce)) {
                    exit('G');
                }

                // Log
                $log = new Log([]);
                $log->setLog_type('info');
                $log->setLog_texte("[RCP] Saisie de la température de réception : " . $temp . "]C. Lot de négoce ID " . $id_lot);
                $logsManager = new LogManager($cnx);
                $logsManager->saveLog($log);

                // Etape suivante :  (étape 8)
                echo 8;

                exit;
            } // FIN mode

            /* -------------------------------------------
MODE - ACTION : Enregistre les températures
--------------------------------------------*/
            function modeActionSaveTemperatures()
            {

                global
                    $cnx,
                    $utilisateur,
                    $lotsManager;

                // On vérifie qu'on a bien un lot valise, une date de DLC et un utilisateur en session...
                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    exit('A');
                }

                // On évite false ou 0.0 car on peux avoir une température à 0°
                $temp   = isset($_REQUEST['temp'])  ? floatval($_REQUEST['temp'])  : 999;
                $temp_d = isset($_REQUEST['tempd']) ? floatval($_REQUEST['tempd']) : 999;
                $temp_m = isset($_REQUEST['tempm']) ? floatval($_REQUEST['tempm']) : 999;
                $temp_f = isset($_REQUEST['tempf']) ? floatval($_REQUEST['tempf']) : 999;

                // Si on est en 3 temp (viande) mais qu'une des trois est vide,
                if ($temp == 999 && ($temp_d + $temp_m + $temp_f > 900)) {
                    exit('B');
                }

                // Si aucune température
                if (($temp_d + $temp_m + $temp_f > 900) && $temp > 900) {
                    exit('C');
                }

                // On vérifie que l'objet lot existe bien, on récupère l'objet réception et on vérifie l'user
                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit('D');
                }
                $lotReceptionManager = new LotReceptionManager($cnx);

                $lotReception = $lotReceptionManager->getLotReceptionByIdLot($id_lot);
                if (!$lotReception || !$lotReception instanceof LotReception) {
                    $lotReception =  new LotReception([]);
                    $lotReception->setId_lot($id_lot);
                }
                if (!isset($utilisateur) || !$utilisateur instanceof User) {
                    exit('F');
                }

                // Enregistrement des données dans le lot...

                $logTemp = '';

                // Une seule température (abats)
                if ($temp < 900) {
                    $lotReception->setTemp($temp);
                    $logTemp = $temp . '°C';
                } else {
                    $lotReception->setTemp_d($temp_d);
                    $lotReception->setTemp_m($temp_m);
                    $lotReception->setTemp_f($temp_f);
                    $logTemp = 'D = ' . $temp_d . '°C, M = ' . $temp_m . '°C, F = ' . $temp_f . '°C';
                }
                if (!$lotReceptionManager->saveLotReception($lotReception)) {
                    exit('G');
                }

                $lot->setDate_maj(date('Y-m-d- H:i:s'));
                $lot->setId_user_maj($utilisateur->getId());
                if (!$lotsManager->saveLot($lot)) {
                    exit('H');
                }

                // Log
                $log = new Log([]);
                $log->setLog_type('info');
                $log->setLog_texte("[RCP] Saisie des températures de réception : " . $logTemp . ". Lot ID " . $id_lot);
                $logsManager = new LogManager($cnx);
                $logsManager->saveLog($log);

                // Etape suivante :  (étape 4)
                echo 4;

                exit;
            } // FIN mode



            /* -------------------------------------------
MODE - ACTION : Enregistre l'état visuel
--------------------------------------------*/
            function modeActionValideEtatVisuel()
            {

                global
                    $cnx,
                    $utilisateur,
                    $lotsManager;

                // On vérifie qu'on a bien un lot valise, un etat visuel conforme et un utilisateur en session...
                $id_lot     = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                $etat      = isset($_REQUEST['etat']) ? intval($_REQUEST['etat']) : -1;
                if ($id_lot == 0 || $etat < 0 || $etat > 1) {
                    exit;
                }
                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit;
                }
                if (!isset($utilisateur) || !$utilisateur instanceof User) {
                    exit;
                }

                $logEtat = $etat == 0 ? "Contestable" : "Satisfaisant";

                // On recherche si on a un objet pour la réception du lot, sinon on le crée :
                $receptionManager = new LotReceptionManager($cnx);

                $lotReception = $receptionManager->getLotReceptionByIdLot($id_lot);
                if (!$lotReception || !$lotReception instanceof LotReception) {
                    $lotReception =  new LotReception([]);
                    $lotReception->setId_lot($id_lot);
                }

                $lotReception->setEtat_visuel($etat);
                $receptionManager->saveLotReception($lotReception);

                // On enregistre la date et heure de mise à jour au niveau du lot
                $lot->setDate_maj(date('Y-m-d- H:i:s'));
                $lot->setId_user_maj($utilisateur->getId());
                if (!$lotsManager->saveLot($lot)) {
                    exit;
                }

                // Log
                $log = new Log([]);
                $log->setLog_type('info');
                $log->setLog_texte("[RCP] Etat visuel " . $logEtat . " du lot ID " . $id_lot);
                $logsManager = new LogManager($cnx);
                $logsManager->saveLog($log);

                // Etape suivante : conformité (étape 5)
                echo 5;

                exit;
            } // FIN mode


            /* -------------------------------------------------
MODE - ACTION : Valide la conformité / observations
--------------------------------------------------*/
            function modeActionConformiteReception()
            {

                global
                    $cnx,
                    $utilisateur,
                    $lotsManager;

                

                // Récupération des variables du formulaire
                $id_lot         = isset($_REQUEST['id_lot'])        ? intval($_REQUEST['id_lot']) : 0;
                $conformite     = isset($_REQUEST['conformite'])    ? intval($_REQUEST['conformite']) : -1;
                $observations   = isset($_REQUEST['observations'])  ? nl2br(strip_tags($_REQUEST['observations'])) : '';
                $points = isset($_REQUEST['point']) && is_array($_REQUEST['point']) ? $_REQUEST['point'] : [];

               
                // On vérifie qu'on a bien un lot valide, une conformité et un utilisateur en session...
                if ($id_lot == 0 || $conformite < 0 || $conformite > 1) {
                    exit;
                }
                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit;
                }
                if (!isset($utilisateur) || !$utilisateur instanceof User) {
                    exit;
                }

                $logConformite = $conformite == 1 ? 'Conformité' : 'Non conformité';

                // On récupère l'objet Réception du lot
                $receptionManager   = new LotReceptionManager($cnx);
                $lotReception       = $receptionManager->getLotReceptionByIdLot($id_lot);

                // Si on a pas d'objet Reception, c'est par normal car on normalement du passer par l'étape précédente qui y enregistre l'état visuel
                if (!$lotReception || !$lotReception instanceof LotReception) {

                    // On le crée quand même au cas où si ça devait arriver... ?!?...
                    // Il n'aurait alors juste pas d'Etat visuel (Verbose à "N/A")
                    $lotReception =  new LotReception([]);
                    $lotReception->setId_lot($id_lot);
                } // FIN test objet Reception

                // Observation / Conformité
                $lotReception->setObservations($observations);
                $lotReception->setConformite($conformite);

                // User / Date de validation / Enregistrement
                $lotReception->setId_user($utilisateur->getId());
                $lotReception->setDate_confirmation(date('Y-m-d H:i:s'));
                $receptionManager->saveLotReception($lotReception);

                // On enregistre la date et heure de mise à jour au niveau du lot
                $lot->setDate_maj(date('Y-m-d- H:i:s'));
                $lot->setId_user_maj($utilisateur->getId());


                if (!$lotsManager->saveLot($lot)) {
                    exit;
                }

                // Log
                $log = new Log([]);
                $log->setLog_type('info');
                $log->setLog_texte("[RCP] " . $logConformite . " de la réception du lot ID " . $id_lot);
                $logsManager = new LogManager($cnx);
                $logsManager->saveLog($log);

                // SI non conforme, gestion des alertes !
                if ($conformite == 0) {

                    $configManager = new ConfigManager($cnx);
                    $alerte1_actif = $configManager->getConfig('alerte1_actif');
                    if ($alerte1_actif instanceof Config) {
                        if ((int)$alerte1_actif->getValeur() == 1) {

                            // Création de l'alerte pour le système d'alerte en BDD
                            $alerte = new Alerte([]);
                            $alerte->setId_lot($id_lot);
                            $alerte->setNumlot($lot->getNumlot());
                            $alerte->setType(1);
                            $alerte->setDate(date('Y-m-d H:i:s'));
                            $alerte->setValeur($conformite);
                            $alerte->setId_user($utilisateur->getId());
                            $alerte->setNom_user($utilisateur->getNomComplet());
                            $alertesManager = new AlerteManager($cnx);
                            if ($alertesManager->saveAlerte($alerte)) {

                                // Envoi de l'alerte par e-mail
                                $alertesManager->envoiMailAlerte($alerte, $observations);
                            } // FIN test enregistrement alerte en BDD
                        } // FIN test alerte active
                    } // FIN test configuration alerte instanciée
                } // FIN test sur non conforme pour gestion des alertes


                
                $pvisuAvantManager = new PvisuAvantManager($cnx);
	            $pointsControleManager = new PointsControleManager($cnx);
                $lotManager = new LotManager($cnx);
                 
                //Récupération du lot
                $lot = $lotManager->getLot($id_lot);
                if (!$lot instanceof Lot) {exit('-4');}

                //Création d'un nouveau pvisuAvant
                $pvisu = new PvisuAvant([]);
                $pvisu->setCommentaires('');
                $pvisu->setId_user($utilisateur->getId());
                $pvisu->setId_lot($id_lot);
                $pvisu->setDate(date('Y-m-d'));

                //Enregistrer le nouveau pvisuAvant
                $id_pvisu = $pvisuAvantManager->savePvisuAvant($pvisu);
                if ((int)$id_pvisu == 0) {
                    exit('-5');
                }
                $pvisu->setId($id_pvisu);

                if (empty($points)) { exit('-2');}

                if (!$pvisuAvantManager->savePvisuAvantPoints($pvisu, $points)) { exit('-4'); }
                $pvisu->setCommentaires($observations);
	            $pvisu->setId_user($utilisateur->getId());
    

                // Etape suivante : Gestion crochets oui/non (étape 10)
                echo 10;
                exit;
            } // FIN mode


            /* -------------------------------------------------
MODE - ACTION : Valide le stock crochets
--------------------------------------------------*/
            function modeActionValideCrochets()
            {

                global
                    $cnx,
                    $utilisateur,
                    $lotsManager;

                $id_lot     = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    exit;
                }
                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit;
                }
                if (!isset($utilisateur) || !$utilisateur instanceof User) {
                    exit;
                }


                // On récupère l'objet Réception du lot
                $receptionManager   = new LotReceptionManager($cnx);
                $lotReception       = $receptionManager->getLotReceptionByIdLot($id_lot);
                if (!$lotReception instanceof LotReception) {
                    exit;
                }


                $crochets_recus  = isset($_REQUEST['crochets_recus'])  ? intval($_REQUEST['crochets_recus'])  : 0;
                $crochets_rendus = isset($_REQUEST['crochets_rendus']) ? intval($_REQUEST['crochets_rendus']) : 0;

                $lotReception->setCrochets_recus($crochets_recus);
                $lotReception->setCrochets_rendus($crochets_rendus);

                if (!$receptionManager->saveLotReception($lotReception)) {
                    exit;
                }

                $etape_suivante = $crochets_recus > 0 || $crochets_rendus > 0 ? 12 : 6;


                // Etape suivante : Terminer la réception / Sélection du transporteur
                echo $etape_suivante;
                exit;
            } // FIN mode


            /* -------------------------------------------------
MODE - ACTION : Termine la réception
--------------------------------------------------*/
            function modeActionTermineReception()
            {
                global
                    $cnx,
                    $utilisateur,
                    $lotsManager;
                // On vérifie qu'on a bien un lot valide et un utilisateur en session...
                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    exit;
                }
                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit;
                }
                if (!isset($utilisateur) || !$utilisateur instanceof User) {
                    exit;
                }
                $vuesManager = new VueManager($cnx);
                $vueRcp = $vuesManager->getVueByCode('rcp');
                if (!$vueRcp instanceof Vue) {
                    exit;
                }
                // Validation admin
                $validation = new Validation([]);
                $validation->setType(1);
                $validation->setId_vue($vueRcp->getId());
                $validation->setId_liaison($id_lot);
                $validationManager = new ValidationManager($cnx);
                if ($validationManager->saveValidation($validation)) {
                    // On associe tous le lot en relationnel
                    $validationManager->addValidationLot($validation, $id_lot);
                }
                $lotsManager->removeLotVue($lot, 'rcp');    // Retire le lot de la vue RECEPTION
                $lotsManager->addLotVue($lot, 'atl');       // Affecte le lot à la vue ATELIER
                // On remet à jour la date de réception
                $lot->setDate_reception(date('Y-m-d'));
                // Pour les statistiques d'admin (dashboard), on enregistre la date de début de production
                $lot->setDate_prod(date('Y-m-d H:i:s'));
                // On associe la date d'atelier pour le nettoyage avant et fin prod (pvisu)
                $lot->setDate_atelier(date('Y-m-d H:i:s'));
                $lotsManager->saveLot($lot);

                // Log
                $log = new Log([]);
                $log->setLog_type('info');
                $log->setLog_texte("[RCP] Clôture de la réception du lot ID " . $id_lot);
                $logsManager = new LogManager($cnx);
                $logsManager->saveLog($log);

                exit;
            }  // FIN mode

            /* -------------------------------------------------
MODE - ACTION : Enregistre la composition viande
--------------------------------------------------*/
            function modeSaveCompisitionViande()
            {

                global $cnx, $lotsManager;

                // On vérifie qu'on a bien un lot valide
                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                if ($id_lot == 0) {
                    exit;
                }
                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit;
                }

                // Valeur
                $composition_viande = isset($_REQUEST['composition_viande']) ? intval($_REQUEST['composition_viande']) : 0;
                if ($composition_viande < 1 || $composition_viande > 2) {
                    exit;
                }

                $logCompo = $composition_viande == 1 ? "sous-vide" : "carcasse";

                $lot->setComposition_viande($composition_viande);
                $lotsManager->saveLot($lot);

                // Log
                $log = new Log([]);
                $log->setLog_type('info');
                $log->setLog_texte("[RCP] Spécification du type de viande " . $logCompo . " pour le lot ID " . $id_lot);
                $logsManager = new LogManager($cnx);
                $logsManager->saveLog($log);

                exit;
            } // FIN mode

            /* -------------------------------------------------
MODE - Enregistre en commentaire de température HS
--------------------------------------------------*/
            function modeSaveCommentaireTempHs()
            {

                global $cnx, $utilisateur;

                // Récupération des données
                $id_froid     =  0; // Vue RCP : pas d'ID froid
                $id_lot       = isset($_REQUEST['id_lot'])        ? intval($_REQUEST['id_lot'])               : 0;
                $commentaire  = isset($_REQUEST['commentaires'])  ? trim(nl2br($_REQUEST['commentaires']))    : '';

                // Vérification des données
                if ($id_froid + $id_lot == 0 || strlen($commentaire) == 0 || !isset($utilisateur) ||  !$utilisateur instanceof User) {
                    exit('-1');
                }

                $com = new Commentaire([]);
                $dateCom = date('Y-m-d H:i:s');
                $com->setDate($dateCom);
                $com->setId_user($utilisateur->getId());
                $com->setId_lot($id_lot);
                $com->setId_froid($id_froid);
                $com->setCommentaire($commentaire);
                $comManager = new CommentairesManager($cnx);

                $comManager->saveCommentaire($com);


                exit;
            } // FIN mode

            /* ---------------------------------------------------
MODE - Modale sélection du type d'incident a déclarer
----------------------------------------------------*/
            function modeSelectTypeIncidentModale()
            {

                global $cnx;

                // On retournes les types d'incidents existants pour choix du type (int)
                ?>
                <div class="row">
                    <div class="col text-center mb-3 gris-5 text-18">
                        Type d'incident à signaler :
                    </div>
                </div>
                <div class="row">

                    <?php
                    $incidentsManager = new IncidentsManager($cnx);
                    $listeTypes = $incidentsManager->getListeTypesIncidents([]);

                    // Boucle sur les types d'incidents définis dans la classe
                    foreach ($listeTypes as $typeIncident) { ?>
                        <div class="col mb-2">
                            <button type="button" class="btn btn-danger btn-large padding-20-40 text-20 form-control btnDeclareTypeIncident" data-type-incident="<?php echo $typeIncident->getId(); ?>">
                                <?php echo $typeIncident->getNom(); ?>
                            </button>
                        </div>
                    <?php }


                    ?>
                </div>

            <?php
                exit;
            } // FIN mode


            /* ---------------------------------------------------
MODE - Modale déclaration d'un incident (commentaire)
----------------------------------------------------*/
            function modeModalDeclareIncident()
            {

                global $cnx;

                $id_type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
                if ($id_type == 0) {
                    echo 'ERREUR !<br>Identification du type impossible...<br>Code erreur : D6XTAD52';
                    exit;
                }

                $id_lot = isset($_REQUEST['id_lot']) ? $_REQUEST['id_lot'] : 0;
                if (substr($_REQUEST['id_lot'], 0, 1) != 'N') {
                    if ($id_lot == 0) {
                        echo 'ERREUR !<br>Identification du lot impossible...<br>Code erreur : N4FOGYTZ';
                        exit;
                    }
                }

                $incidentManager = new IncidentsManager($cnx);
                $incidentType = $incidentManager->getTypeIncident($id_type);
            ?>



                <input type="hidden" name="mode" value="addIncident" />
                <input type="hidden" name="type" value="<?php echo $incidentType->getId(); ?>" />
                <input type="hidden" name="id_lot" value="<?php echo $id_lot; ?>" />
                <div class="row">
                    <div class="col text-center mb-3 gris-5 text-18">
                        Commentaire sur l'incident <?php
                                                    $de = in_array(substr(strtolower($incidentType->getNom()), 0, 1), ['a', 'e', 'i', 'o', 'u', 'é', 'è', 'à']) ? "d'" : "de ";
                                                    echo $de . strtolower($incidentType->getNom());
                                                    ?> :
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <textarea class="form-control" placeholder="Commentaire obligatoire..." id="champ_clavier" name="incident_commentaire"></textarea>
                    </div>
                </div>
            <?php
            } // FIN mode

            /* ---------------------------------------------------
MODE - Enregistre un nouvel incident + commentaire
----------------------------------------------------*/
            function modeAddIncident()
            {

                global $utilisateur, $cnx;

                $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
                if ($type == 0) {
                    exit('type 0');
                }

                $negoce = 0;
                $id_lot = isset($_REQUEST['id_lot']) ? $_REQUEST['id_lot'] : 0;
                if (substr($_REQUEST['id_lot'], 0, 1) != 'N') {
                    if ($id_lot == 0) {
                        exit('id_lot 0');
                    }
                } else {
                    $id_lot = str_replace('N', '', $id_lot);
                    $negoce = 1;
                }

                $commentaire = isset($_REQUEST['incident_commentaire']) ? trim($_REQUEST['incident_commentaire']) : '';
                if (strlen($commentaire) == 0) {
                    exit('incident_commentaire vide');
                }

                $incidentManager = new IncidentsManager($cnx);
                $commentairesManager = new CommentairesManager($cnx);

                $incident = new Incident([]);
                $incident->setId_user($utilisateur->getId());
                $incident->setId_lot($id_lot);
                $incident->setNegoce($negoce);
                $incident->setDate(date('Y-m-d H:i:s'));
                $incident->setType_incident($type);


                $typeIncident = $incidentManager->getTypeIncident($type);

                $incidentManager->saveIncident($incident);

                $de = in_array(substr(strtolower($typeIncident->getNom()), 0, 1), ['a', 'e', 'i', 'o', 'u', 'é', 'è', 'à']) ? "d'" : "de ";

                $incident_commentaire = "Incident " . $de . strtolower($typeIncident->getNom()) . " : " . $commentaire;

                $com = new Commentaire([]);
                $com->setDate(date('Y-m-d H:i:s'));
                $com->setId_lot($id_lot);
                $com->setNegoce($negoce);
                $com->setIncident(1);
                $com->setId_user($utilisateur->getId());
                $com->setCommentaire($incident_commentaire);
                $commentairesManager->saveCommentaire($com);

                exit;
            } // FIN mode

            /* ---------------------------------------------------
MODE - Enregistre le transporteur (si crochets)
----------------------------------------------------*/
            function modeSaveTransporteurCrochets()
            {

                global $cnx, $lotsManager;

                // On vérifie qu'on a bien un lot valide
                $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
                $id_trans = isset($_REQUEST['id_trans']) ? intval($_REQUEST['id_trans']) : 0;
                if ($id_lot == 0 || $id_trans == 0) {
                    exit;
                }
                $lot = $lotsManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit;
                }

                $lotReceptionManager = new LotReceptionManager($cnx);

                $lotReception = $lotReceptionManager->getLotReceptionByIdLot($id_lot);
                if (!$lotReception instanceof LotReception) {
                    exit;
                }
                $lotReception->setId_transporteur($id_trans);
                echo $lotReceptionManager->saveLotReception($lotReception) ? 1 : 0;

                $tiersManager = new TiersManager($cnx);
                $trans = $tiersManager->getTiers($id_trans);
                if (!$trans instanceof Tiers) {
                    exit;
                }

                $ecartCrochet = $lotReception->getCrochets_recus() - $lotReception->getCrochets_rendus();
                if ($ecartCrochet != 0) {
                    $newSolde = $trans->getSolde_crochets() + $ecartCrochet;
                    $trans->setSolde_crochets($newSolde);
                    $tiersManager->saveTiers($trans);
                }

                exit;
            } // FIN mode
