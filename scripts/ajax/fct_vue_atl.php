 <?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax VUE ATELIER
------------------------------------------------------*/

// Initialisation du mode d'appel
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';
// Instanciation des Managers
$logsManager = new LogManager($cnx);
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
MODE - Charge le contenu d'une étape de vue
-------------------------------------------*/
function modeChargeEtapeVue()
{
    global $utilisateur, $cnx;

    $etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;

    $na = '<span class="badge badge-warning badge-pill text-14">Non renseigné</span>';
    /** ----------------------------------------
     * DEV - On affiche l'étape pour débug
     *  ----------------------------------- */
    if ($utilisateur->isDev()) { ?>
        <div class="dev-etape-vue"><i class="fa fa-user-secret fa-lg mr-1"></i>Etape <kbd><?php echo $etape; ?></kbd>
        </div>
    <?php } // FIN test DEV

    // Si le suivi de nettoyage avant prod n'a pas été signé on bloque
    $pvisuManager = new PvisuAvantManager($cnx);
    $pvisuAvant = $pvisuManager->getPvisuAvantJour('', false);
      
    if (intval($pvisuAvant->getId()) == 0) {
        $vuesManager = new VueManager($cnx);
        $vueNet = $vuesManager->getVueByCode('net');
        $url = $vueNet instanceof Vue ? $vueNet->getUrl() : '';
        ?>
        <div class="alert alert-danger mt-3 text-center padding-50">
            <i class="fa fa-exclamation-circle fa-5x"></i>
            <p class="text-28">Contrôle avant production non signé !</p>
            <p class="text-16">Effectuez le suivi de nettoyage et signez le contrôle avant de continer...</p>
            <?php if ($url != '') { ?>
                <div class="mt-2"><a href="<?php echo __CBO_ROOT_URL__ . $url . '/avant'; ?>"
                                     class="btn btn-secondary btn-lg padding-20"><i
                                class="fa fa-2x fa-clipboard-check mn-1"></i><br>Contrôle avant production</a></div>
            <?php } ?>
        </div>
        <?php exit;
    }


    /** ----------------------------------------
     * Etape        : 1
     * Description  : Sélection du lot
     *  ----------------------------------- */
    if ($etape == 1) {
        ?>
        <div class="row">
            <div class="col mt-3">
                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Sélectionnez le lot concerné :</h4>
            </div>
        </div>
        <?php
        $lotsManager = new LotManager($cnx);
        $pvisuManager = new PvisuPendantManager($cnx);
        $listeLot = $lotsManager->getListeLotsByVue('atl');
        // Si aucun lot en atelier
        if (empty($listeLot)) { ?>
            <div class="col alert alert-secondary mt-3 padding-50">
                <h2 class="mb-0 text-secondary text-center"><i class="fa fa-exclamation-circle fa-2x mb-3"></i>
                    <p>Aucune lot disponible en atelier&hellip;</p>
                </h2>
            </div>
            <?php
        } // FIN test aucun lot atelier
        // Boucle sur les lots en atelier
        foreach ($listeLot as $lotvue) {

            // Lot nettoyage signé (pvisu pendant) ?
            $pvisu = $pvisuManager->getPvisuPendantJourByLot($lotvue);
            $cssPvisu = $pvisu instanceof PvisuPendant && (int)$pvisu->getId() > 0 ? 'pvisuok' : 'pvisuko';


            ?>
            <div class="card text-white mb-3 carte-lot d-inline-block mr-3 <?php echo $cssPvisu; ?>"
                 style="max-width: 20rem;background-color: <?php echo $lotvue->getCouleur(); ?>"
                 data-id-lot="<?php echo $lotvue->getId(); ?>" data-etape-suivante="4">
                <div class="card-header text-36"><?php echo $lotvue->getNumlot(); ?></div>
                <div class="card-body">
                    <table>
                        <tr>
                            <td>Espèce</td>
                            <th><?php echo $lotvue->getNom_espece($na); ?></th>
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
                            <th><?php echo $lotvue->getNom_abattoir() != '' ? $lotvue->getNom_abattoir() : $na; ?>
                                <br><span class="texte-fin"><?php echo $lotvue->getNumagr_abattoir(); ?></span></th>
                        </tr>
                        <tr>
                            <td>Réception</td>
                            <th><?php echo $lotvue->getDate_reception() != '' && $lotvue->getDate_reception() != '0000-00-00' ? Outils::getDate_only_verbose($lotvue->getDate_reception(), true, false) : $na; ?></th>
                        </tr>
                        <tr>
                            <td>Poids</td>
                            <th><?php echo $lotvue->getPoids_reception() > 0 ? number_format($lotvue->getPoids_reception(), 3, '.', ' ') . ' kgs' : $na; ?></th>
                        </tr>
                    </table>
                </div> <!-- FIN body carte -->
            </div> <!-- FIN carte -->
            <?php
        } // FIN boucle sur les lots en atelier

        exit;
    } // FIN ETAPE

    /** ----------------------------------------
     * Etape        : 2
     * Description  : Sélection de l'emballage
     *  ----------------------------------- */
    if ($etape == 2) {
        // On vérifie qu'on a bien un lot valide
        $lotManager = new LotManager($cnx);
        $id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $lot = $lotManager->getLot($id_lot);
        if ($id_lot == 0 || !$lot instanceof Lot) {
            exit;
        }


        if ($lot->getComposition() != 2) {
            $lotManager->addQuantiemeIfNotExist($lot);
        }

        $logManager = new LogManager($cnx);
        $log = new Log([]);
        $log->setLog_type('info');
        $log->setLog_texte('Ajout du quantième au lot #' . $id_lot);
        $logManager->saveLog($log);

        $consommablesManager = new ConsommablesManager($cnx);
        $consommablesManager->repareEnCours();

        ?>
        <div class="row mt-3 align-content-center">

            <!-- ETIQUETTES -->

            <div class="col-12 text-center" id="etiquettesAtl">
                <div class="alert alert-secondary">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Edition des étiquetages :</h4>
                    <div class="row justify-content-md-center" id="containerCategories">

                        <?php
                        // On récupère les catégories de produit
                        $categoriesManager = new ProduitCategoriesManager($cnx);
                        $listeCategories = $categoriesManager->getListeProduitCategories();

                        // Boucle sur les catégories de produits
                        foreach ($listeCategories as $cate) { ?>

                            <div class="col-2 mb-3">
                                <button type="button" class="btn btn-large btn-primary form-control text-<?php
                                // On adapte la taille du texte en fonction de la longueur
                                echo strlen($cate->getNom()) > 22 ? '14 padding-20 line-height-30' : '20 padding-20-40';

                                ?> text-uppercase btnCategorie" data-id-categorie="<?php echo $cate->getId(); ?>">
                                    <?php echo $cate->getNom(); ?>
                                </button>
                            </div>

                            <?php
                        } // FIN boucle sur les catégories
                        ?>
                    </div>
                    <div class="row justify-content-md-center" id="containerEtiquettesProduits"></div>

                </div>
            </div>
            <!-- EMBALLAGES -->
            <div class="col-12 text-center">
                <div class="alert alert-secondary">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3"></i>Emballages disponibles en Atelier :</h4>
                    <div class="row" id="containerListeEmballages">
                        <?php
                        // Fonction déportée pour pagination Ajax
                        modeListeCartesEmballabe();
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        exit;
    } // FIN ETAPE

    if ($etape == 4) {
        $froidManager = new FroidManager($cnx);
        $produitManager = new ProduitManager($cnx);
        $lomatManager = new LomaManager($cnx);
        // Récupération des variables
        $id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        // Récupération l'information du produit Controle LOMA ATELIER debut
        $froidProduits = $produitManager->getProduitByNomCourt("LOMA ATELIER DEBUT");
        $id_pdt = $froidProduits->getId();

        // Vérification produit froid
        $checkProduitFroid = $froidManager->getFroidProduitObjetSansFroid($id_lot, $id_pdt);
        if (!$checkProduitFroid instanceof FroidProduit) {
            // Creation du nouveau froid
            $id_froid = addPdtFroid($id_lot, $id_pdt);
            $checkProduitFroid = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
        }

        $id_lot_pdt_froid = intval($checkProduitFroid->getId_lot_pdt_froid()) > 0 ? $checkProduitFroid->getId_lot_pdt_froid() : 0;
        $froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);

        $froid = $froidManager->getFroid($froidProduit->getId_froid());
        if (!$froid instanceof Froid) {
            erreurLot();
            exit;
        }

        // Si on a pas encore passé les tests "avant" au niveau de l'objet Froid, on affiche le formulaire pour les test avant, sinon on passe direct au produit
        $avantAfaire = $froid->getTest_avant_fe() < 0; // -1 sur un des tests = pas fait
        if ($utilisateur->isDev()) {
            $_SESSION['infodevvue'] = 'FroidProduit [' . $id_lot_pdt_froid . ']';
            $_SESSION['infodevvue'] = '<br>Froid [' . $froid->getId() . ']';
            $_SESSION['infodevvue'] = '<br>Lot [' . $froidProduit->getId_lot() . ']';
        }

        $loma = $lomatManager->getLomaByIdLotPdtFroid($id_lot_pdt_froid);
        
       
        $is_verified = false;
        if (!$loma) {
            $is_verified = false;
        } else {
            if ($loma->getTest_pdt() >= 0) {
                $is_verified = true;
            }
        }

        // Formulaire contrôle LOMA
        ?>
        <form id="controleLoma" <?php if ($is_verified): ?> class="hidden" <?php endif; ?> >
            <input type="hidden" name="id_lot_pdt_froid" value="<?php echo $id_lot_pdt_froid; ?>"/>
            <input type="hidden" name="id_lot" value="<?php echo $froidProduit->getId_lot(); ?>"/>
            <input type="hidden" name="id_froid" value="<?php echo $froid->getId(); ?>"/>
            <input type="hidden" name="mode" value="saveLoma"/>

            <div class="row">
                <div class="col text-center header-loma">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3 mt-3"></i>Surveillance du contrôle de détection
                        métallique
                        LOMA :</h4>
                    <div class="badge badge-dark text-24 badge-pill mt-3 <?php echo $avantAfaire ? 'hid' : ''; ?>">
                        Passage avant départ en atelier
                    </div>
                    <div class="badge badge-dark text-24 badge-pill mt-3 <?php echo $avantAfaire ? '' : 'hid'; ?>">
                        Test départ lot en atelier
                    </div>
                </div>
            </div>
            <div class="row mt-3 masque-clavier-virtuel">
                <?php if ($avantAfaire) { ?>
                    <div class="col-6 offset-3 tests-plaquettes">

                        <div class="alert alert-secondary row">


                            <div class="col-4 text-center loma-test-btns">
                                <h4>Test non ferreux</h4>
                                <p>Taille : 4mm</p>
                                <button type="button"
                                        class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light"
                                        data-test="nfe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                <button type="button"
                                        class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light"
                                        data-test="nfe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                            </div>

                            <div class="col-4 text-center loma-test-btns">
                                <h4>Test inox</h4>
                                <p>Taille : 5.5mm</p>
                                <button type="button"
                                        class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light"
                                        data-test="inox" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                <button type="button"
                                        class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light"
                                        data-test="inox" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                            </div>


                            <div class="col-4 text-center loma-test-btns">
                                <h4>Test ferreux</h4>
                                <p>Taille : 4mm</p>
                                <button type="button"
                                        class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light"
                                        data-test="fe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                                <button type="button"
                                        class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light"
                                        data-test="fe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                            </div>

                            <div class="col-12 text-center">
                                <p class="small pt-2 gris-7">Les tests doivent sonner pour être validés.</p>
                            </div>

                        </div>

                    </div>
                    <?php
                }

                ?>

                <div class="col-5"></div>
                <div class="col-2 offset-5 ml-4 alert alert-dark test-produit <?php echo $avantAfaire ? 'hid' : ''; ?>">
                    <div class="row">
                        <div class="col-12 text-center loma-test-btns">

                            <h4>Test départ lot</h4>
                            <p>Détection corps étranger</p>
                            <button type="button"
                                    class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma padding-20-10 border-light"
                                    data-test="pdt" data-resultat="1"><i class="fa fa-exclamation-triangle fa-lg"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-success btn-lg text-center form-control btn-loma padding-20-10 border-light"
                                    data-test="pdt" data-resultat="0"><i class="fa fa-check fa-lg"></i></button>

                        </div>
                        <div class="col-12 text-center">
                            <p class="small pt-2 gris-7">Ne doit pas sonner pour être validé.</p>
                        </div>

                    </div>
                </div>

            </div>
            <div class="row mt-3 loma-commentaires" id="commentaire_etape_4">
                <div class="col-6 offset-3">
                    <div class="alert alert-secondary">
                        <div class="row">
                            <div class="col-9">
                                <label>Commentaires :</label>
                                <textarea name="commentaires" class="form-control" id="champ_clavier"></textarea>
                            </div>
                            <div class="col-3">
                                <label>&nbsp;</label>
                                <button type="button"
                                        class="btn btn-lg btn-info padding-20-10 form-control btn-valid-loma"><i
                                            class="fa fa-check fa-lg mb-2"></i><br/>Terminé
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            <div class="resultats-tests d-none">
                <input type="hidden" name="resultest_nfe" value="-1"/>
                <input type="hidden" name="resultest_inox" value="-1"/>
                <input type="hidden" name="resultest_fe" value="-1"/>
                <input type="hidden" name="resultest_pdt" value="-1"/>
            </div>
        </form>
        <?php

        exit;

    }  // FIN ETAPE


    if ($etape == 5) {
        $froidManager = new FroidManager($cnx);
        // Récupération des variables
        $id_froid = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        $froidManager = new FroidManager($cnx);
        $produitManager = new ProduitManager($cnx);
        $lomatManager = new LomaManager($cnx);
        // Récupération des variables
        $id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        // Récupération l'information du produit Controle LOMA ATELIER debut
        $froidProduits = $produitManager->getProduitByNomCourt("LOMA ATELIER FIN");
        $id_pdt = $froidProduits->getId();

        // Vérification produit froid
        $checkProduitFroid = $froidManager->getFroidProduitObjetSansFroid($id_lot, $id_pdt);
        if (!$checkProduitFroid instanceof FroidProduit) {
            // Creation du nouveau froid
            $id_froid = addPdtFroid($id_lot, $id_pdt);
            $checkProduitFroid = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
        }

        $id_lot_pdt_froid = intval($checkProduitFroid->getId_lot_pdt_froid()) > 0 ? $checkProduitFroid->getId_lot_pdt_froid() : 0;
        $froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);

        $froid = $froidManager->getFroid($froidProduit->getId_froid());
        if (!$froid instanceof Froid) { ?>
            <div class="alert alert-danger padding-50 mt-5">
                <h4>ERREUR !</h4>
                <p>Identification du traitement froid impossible...</p>
            </div>
            <?php exit;
        }
        $id_froid = $froidProduit->getId_froid();
        if ($utilisateur->isDev()) {
            $_SESSION['infodevvue'] = 'Froid [' . $id_froid . ']';
        }


        // Formulaire contrôle LOMA
        ?>
        <form id="controleLomaFin">
            <input type="hidden" name="id_lot_pdt_froid" value="<?php echo $id_lot_pdt_froid; ?>"/>
            <input type="hidden" name="id_lot" value="<?php echo $froidProduit->getId_lot(); ?>"/>
            <input type="hidden" name="id_froid" value="<?php echo $froid->getId(); ?>"/>
            <input type="hidden" name="mode" value="saveLomaApres"/>

            <div class="row">
                <div class="col text-center header-loma-fin">
                    <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3 mt-3"></i>Surveillance du contrôle de détection
                        métallique
                        LOMA :</h4>
                    <div class="badge badge-dark text-24 badge-pill mt-3">Passage des tests après</div>
                </div>
            </div>
            <div class="row mt-3 masque-clavier-virtuel">

                <div class="col-6 offset-3 tests-plaquettes-fin">

                    <div class="alert alert-secondary row">


                        <div class="col-4 text-center loma-test-btns-fin">
                            <h4>Test non ferreux</h4>
                            <p>Taille : 4mm</p>
                            <button type="button"
                                    class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma-fin padding-20-10 border-light"
                                    data-test="nfe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                            <button type="button"
                                    class="btn btn-success btn-lg text-center form-control btn-loma-fin padding-20-10 border-light"
                                    data-test="nfe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                        </div>

                        <div class="col-4 text-center loma-test-btns-fin">
                            <h4>Test inox</h4>
                            <p>Taille : 5.5mm</p>
                            <button type="button"
                                    class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma-fin padding-20-10 border-light"
                                    data-test="inox" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                            <button type="button"
                                    class="btn btn-success btn-lg text-center form-control btn-loma-fin padding-20-10 border-light"
                                    data-test="inox" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                        </div>


                        <div class="col-4 text-center loma-test-btns-fin">
                            <h4>Test ferreux</h4>
                            <p>Taille : 4mm</p>
                            <button type="button"
                                    class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma-fin padding-20-10 border-light"
                                    data-test="fe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                            <button type="button"
                                    class="btn btn-success btn-lg text-center form-control btn-loma-fin padding-20-10 border-light"
                                    data-test="fe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                        </div>

                        <div class="col-12 text-center">
                            <p class="small pt-2 gris-7">Les tests doivent sonner pour être validés.</p>
                        </div>
                    </div>
                </div>

            </div>
            <div class="resultats-fin-tests d-none">
                <input type="hidden" name="resultest_fin_nfe" value="-1"/>
                <input type="hidden" name="resultest_fin_inox" value="-1"/>
                <input type="hidden" name="resultest_fin_fe" value="-1"/>
                <input type="hidden" name="resultest_fin_pdt" value="0"/>
            </div>
        </form>
        <?php
        exit;

    } // FIN ETAPE


    if ($etape == 6) {
        $froidManager = new FroidManager($cnx);
        $produitManager = new ProduitManager($cnx);
        $lomatManager = new LomaManager($cnx);
        // Récupération des variables
        $id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        // Récupération l'information du produit Controle LOMA ATELIER debut
        $froidProduits = $produitManager->getProduitByNomCourt("LOMA ATELIER DEBUT");
        $id_pdt = $froidProduits->getId();

        // Vérification produit froid
        $checkProduitFroid = $froidManager->getFroidProduitObjetSansFroid($id_lot, $id_pdt);
        if (!$checkProduitFroid instanceof FroidProduit) {
            // Creation du nouveau froid
            $id_froid = addPdtFroid($id_lot, $id_pdt);
            $checkProduitFroid = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
        }

        $id_lot_pdt_froid = intval($checkProduitFroid->getId_lot_pdt_froid()) > 0 ? $checkProduitFroid->getId_lot_pdt_froid() : 0;
        $froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);

        $froid = $froidManager->getFroid($froidProduit->getId_froid());
        if (!$froid instanceof Froid) {
            erreurLot();
            exit;
        }

        // Si on a pas encore passé les tests "avant" au niiveau de l'objet Froid, on affiche le formulaire pour les test avant, sinon on passe direct au produit
        $avantAfaire = $froid->getTest_avant_fe() < 0; // -1 sur un des tests = pas fait
        if ($utilisateur->isDev()) {
            $_SESSION['infodevvue'] = 'FroidProduit [' . $id_lot_pdt_froid . ']';
            $_SESSION['infodevvue'] = '<br>Froid [' . $froid->getId() . ']';
            $_SESSION['infodevvue'] = '<br>Lot [' . $froidProduit->getId_lot() . ']';
        }

        // Formulaire contrôle LOMA
        ?>
        <form id="controleLomaEncours">
        <input type="hidden" name="id_lot_pdt_froid" value="<?php echo $id_lot_pdt_froid; ?>"/>
        <input type="hidden" name="id_lot" value="<?php echo $froidProduit->getId_lot(); ?>"/>
        <input type="hidden" name="id_froid" value="<?php echo $froid->getId(); ?>"/>
        <input type="hidden" name="incident_type" value="11"/>
        <input type="hidden" name="mode" value="saveLomaEncours"/>

        <div class="row">
            <div class="col text-center header-loma">
                <h4><i class="fa fa-angle-down fa-lg ml-2 mr-3 mt-3"></i>Surveillance du contrôle de détection
                    métallique
                    LOMA :</h4>
                <div class="badge badge-dark text-24 badge-pill mt-3 <?php echo $avantAfaire ? 'hid' : ''; ?>">
                    Lot sur atelier en cours
                </div>
                <div class="badge badge-dark text-24 badge-pill mt-3 <?php echo $avantAfaire ? '' : 'hid'; ?>">
                    Test en cours du lot en atelier
                </div>
            </div>
        </div>
        <div class="row mt-3 masque-clavier-virtuel">
        <?php if ($avantAfaire) { ?>
            <div class="col-6 offset-3 tests-encours-plaquettes">

                <div class="alert alert-secondary row">
                    <div class="col-4 text-center loma-test-btns-encours">
                        <h4>Test non ferreux</h4>
                        <p>Taille : 4mm</p>
                        <button type="button"
                                class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma-encours padding-20-10 border-light"
                                data-test="nfe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                        <button type="button"
                                class="btn btn-success btn-lg text-center form-control btn-loma-encours padding-20-10 border-light"
                                data-test="nfe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                    </div>

                    <div class="col-4 text-center loma-test-btns-encours">
                        <h4>Test inox</h4>
                        <p>Taille : 5.5mm</p>
                        <button type="button"
                                class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma-encours padding-20-10 border-light"
                                data-test="inox" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                        <button type="button"
                                class="btn btn-success btn-lg text-center form-control btn-loma-encours padding-20-10 border-light"
                                data-test="inox" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                    </div>


                    <div class="col-4 text-center loma-test-btns-encours">
                        <h4>Test ferreux</h4>
                        <p>Taille : 4mm</p>
                        <button type="button"
                                class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma-encours padding-20-10 border-light"
                                data-test="fe" data-resultat="0"><i class="fa fa-times fa-lg"></i></button>
                        <button type="button"
                                class="btn btn-success btn-lg text-center form-control btn-loma-encours padding-20-10 border-light"
                                data-test="fe" data-resultat="1"><i class="fa fa-check fa-lg"></i></button>
                    </div>

                    <div class="col-12 text-center">
                        <p class="small pt-2 gris-7">Les tests doivent sonner pour être validés.</p>
                    </div>

                </div>
            </div>
            <?php
        }
            ?>

            <div class="col-5"></div>
            <div class="col-2 offset-5 ml-4 alert alert-dark test-encours-produit <?php echo $avantAfaire ? 'hid' : ''; ?>">
                <div class="row">
                    <div class="col-12 text-center loma-test-btns-encours">

                        <h4>Test lot en cours </h4>
                        <p>Détection corps étranger</p>
                        <button type="button"
                                class="btn btn-danger btn-lg text-center form-control mb-2 btn-loma-encours padding-20-10 border-light"
                                data-test="pdt" data-resultat="1"><i class="fa fa-exclamation-triangle fa-lg"></i>
                        </button>
                        <button type="button"
                                class="btn btn-success btn-lg text-center form-control btn-loma-encours padding-20-10 border-light"
                                data-test="pdt" data-resultat="0"><i class="fa fa-check fa-lg"></i></button>

                    </div>
                    <div class="col-12 text-center">
                        <p class="small pt-2 gris-7">Ne doit pas sonner pour être validé.</p>
                    </div>

                </div>
            </div>

            </div>
            <div class="row mt-3 loma-commentaires-encours">
                <div class="col-6 offset-3">
                    <div class="alert alert-secondary">
                        <div class="row">
                            <div class="col-9">
                                <label>Commentaires :</label>
                                <textarea name="commentaires" class="form-control" id="champ_clavier_encours"></textarea>
                            </div>
                            <div class="col-3">
                                <label>&nbsp;</label>
                                <button type="button"
                                        class="btn btn-lg btn-info padding-20-10 form-control btn-valid-loma-encours"><i
                                            class="fa fa-check fa-lg mb-2"></i><br/>Terminé
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="resultats-tests-encours d-none">
                <input type="hidden" name="resultest_encours_nfe" value="-1"/>
                <input type="hidden" name="resultest_encours_inox" value="-1"/>
                <input type="hidden" name="resultest_encours_fe" value="-1"/>
                <input type="hidden" name="resultest_encours_pdt" value="-1"/>
            </div>
            </form>
            <?php
    }

    exit;
} // FIN mode

function modeListeCartesEmballabe()
{
    global $cnx;
    $consommablesManager = new ConsommablesManager($cnx);
    $vuesManager = new VueManager($cnx);
    // Préparation pagination (Ajax)
    $nbResultPpage = 16;
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $filtresPagination = '?mode=listeCartesEmballabe';
    $start = ($page - 1) * $nbResultPpage;
    $params = [
        'id_vue' => $vuesManager->getVueByCode('atl')->getId(),
        'get_emb' => true,
        'has_encours' => true,
        'start' => $start,
        'nb_result_page' => $nbResultPpage,
        'lot_nonsuppr' => true
    ];
    $famillesListe = $consommablesManager->getListeConsommablesFamilles($params);
    $nbResults = $consommablesManager->getNb_results();
    $pagination = new Pagination($page);
    $pagination->setUrl($filtresPagination);
    $pagination->setNb_results($nbResults);
    $pagination->setAjax_function(true);
    $pagination->setNb_results_page($nbResultPpage);
    $pagination->setNb_apres(2);
    $pagination->setNb_avant(2);
    $nbPdtOnThePage = count($famillesListe);

    foreach ($famillesListe as $fam) { ?>
        <div class="col-2 mb-3">
            <div class="card bg-secondary text-white carte-emb" data-id-fam="<?php echo $fam->getId(); ?>"
                 data-id-emb-encours="<?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : 0; ?>">
                <div class="card-header">
                    <?php echo $fam->getCode(); ?>
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-0"><?php echo $fam->getNom(); ?></h5>
                    <span class="badge badge-dark text-16 d-block margin-top-15"><?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getNumlot_frs() : '?'; ?>
					<span class="text-12 margin-top-5 texte-fin d-block"><?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getNom_frs() : ''; ?></span></span>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col">
                            <button type="button"
                                    class="btn btn-danger padding-20-10 border-light form-control btn-emb-defectueux"
                                    data-id-emb="<?php
                                    echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : ''; ?>">
                                <i class="fa fa-exclamation-triangle fa-lg"></i></button>
                        </div>
                        <div class="col">
                            <button type="button"
                                    class="btn btn-info padding-20 border-light form-control btn-emb-change"
                                    data-id-old-emb="<?php echo $fam->getEmb_encours() instanceof Consommable ? $fam->getEmb_encours()->getId() : ''; ?>">
                                <i class="fa fa-retweet fa-lg"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } // FIN boucle sur les emballages
    // Pagination (aJax)
    if (isset($pagination)) {
        // Si on a moins de 3 blocs de libres à droite, on va a la ligne
        $nbCasse = [4, 5, 10, 11, 16, 17];
        if (in_array($nbPdtOnThePage, $nbCasse)) { ?>
            <div class="clearfix"></div>
        <?php }
        $pagination->setNature_resultats('produit');
        echo($pagination->getPaginationBlocs());
    } // FIN test pagination

} // FIN mode

/* ------------------------------------------
MODE -
-------------------------------------------*/
function modeChargeTicketLot()
{
    global
    $cnx,
    $utilisateur;
    $etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;
    $identifiant = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
    $na = '<span class="badge badge-warning badge-pill text-14">Non renseigné !</span>';
    $err = '<span class="badge danger badge-pill text-14">ERREUR !</span>';
    // ETAPE 1 - Sélectionnez un lot
    if ($etape == 1) { ?>
        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot="0">
            &mdash;
        </div>
        <input id="lotid_photo" type="hidden" value="0">
        <p class="mt-2 text-center">Sélectionez un lot&hellip;</p>
        <?php
    } // FIN étape 1
    
    // ETAPE 2
    if ($etape == 2) {
        $lotManager = new LotManager($cnx);
        $lot = $lotManager->getLot($identifiant);
        if ($identifiant == '' || !$lot instanceof Lot) {
            echo $err;
            exit;
        }
        ?>
        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1" id="numLotTicket"
             data-id-lot=" <?php echo $lot->getId(); ?>">
            <?php echo $lot->getNumlot();
            // Si PAS abats, on affiche le quantième...
            if ($lot->getComposition() != 2) {
                echo Outils::getJourAnByDate(date('Y-m-d'));
            } ?>
        </div>

        <input id="lotid_photo" type="hidden" value="<?php echo $lot->getId(); ?>">

        <h4 class="margin-top-15 margin-bottom-0 nopadding">Défectueux :</h4>
        <table id="ticketLotInfoStock"
               class="table-suivi-stock-emb"><?php showEmballagesDefectueux($lot->getId()); ?></table>

        <h4 class="margin-top-15 margin-bottom-0 nopadding">Changements de rouleaux :</h4>
        <table id="ticketLotInfoChmgt"
               class="table-suivi-stock-emb"><?php showEmballageChangeLot($lot->getId()); ?></table>


        <?php
        // SI on des incidents...
        $incidentsManager = new IncidentsManager($cnx);
        if ($incidentsManager->asLotIncidentsCommentaire($lot->getId())) {
            ?>
            <button type="button" class="btn btn-warning btn-lg form-control btnCommentaires text-left mb-3"
                    data-toggle="modal" data-target="#modalCommentairesFront"
                    data-id-lot="<?php echo $lot->getId(); ?>">
                <i class="fa fa-info-circle fa-lg fa-fw vmiddle mr-2"></i>Infos incidents
            </button>
        <?php } ?>

        <!-- Bouton changement de vue -->
        <button type="button" class="btn btn-secondary btn-lg form-control btnChangeVue text-left text-18">
            <i class="fa fa-undo fa-lg fa-fw vmiddle mr-2"></i>
            Retour
        </button>

        <?php
    } // FIN ETAPE

    // TOUTES ETAPES
    // Bouton lien vers Nettoyage (Sans restriction de profil pour l'instant)
    if ($etape == 2) {

        // On teste si le lot a déjà été validé pour le nettoyage pendant
        $pvisuManager = new PvisuPendantManager($cnx);
        $pvisu = $pvisuManager->getPvisuPendantJourByLot($lot);
        if (!$pvisu instanceof PvisuPendant) {
            $pvisu = new PvisuPendant([]);
        }
        $cssBtnValider = (int)$pvisu->getId() > 0 ? 'secondary' : 'info';
        $txtBtnValider = (int)$pvisu->getId() > 0 ? ' Nettoyage validé' : ' Nettoyage à valider&hellip;';

       
        #Verification si la detection loma du lot en cours a été effectué
        $id_lot = $lot->getId();

        $froidManager = new FroidManager($cnx);
        $produitManager = new ProduitManager($cnx);
        $froidProduits = $produitManager->getProduitByNomCourt("LOMA ATELIER DEBUT");
        $id_pdt = $froidProduits->getId();

        $checkProduitFroid = $froidManager->getFroidProduitObjetSansFroid($id_lot, $id_pdt);
        if (!$checkProduitFroid instanceof FroidProduit) {
            // Creation du nouveau froid
            $id_froid = addPdtFroid($id_lot, $id_pdt);
            $checkProduitFroid = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
        }

        $id_lot_pdt_froid = intval($checkProduitFroid->getId_lot_pdt_froid()) > 0 ? $checkProduitFroid->getId_lot_pdt_froid() : 0;
        $froidProduit = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);




        ?>
        <!-- Bouton changement de vue -->
        <a href="<?php echo __CBO_ROOT_URL__; ?>nettoyage/pendant-<?php echo (int)$lot->getId(); ?>"
            class="btn btn-<?php echo $cssBtnValider; ?> btn-lg mt-3 form-control text-left text-18 " id="nettoyage_atl">
            <i class="fa fa-clipboard-check fa-fw vmiddle fa-lg mr-2 margin-top--5"></i>
            <?php echo $txtBtnValider; ?>
        </a>

        <button type="button" class="btn btn-warning btn-lg form-control btnIncident mt-3 text-left" data-toggle="modal"
                data-target="#modalIncidentFront"><i class="fa fa-exclamation-triangle fa-lg fa-fw vmiddle mr-2"></i>Déclarer un incident
        </button>
        <button type="button" class="btn btn-warning btn-lg form-control btnLomaEncours mt-3 text-center">
            Détection LOMA sur le lot en cours
        </button>
        <button type="button" class="btn btn-warning btn-lg form-control btnLomaFin mt-3 text-center">
            Contrôle LOMA - Fin du lot
        </button>
        <?php
    }

    if($etape == 5){
        //$id_lot = isset($_REQUEST['id_lot'])?intval($_REQUEST['id_lot']):'';
        $lotManager = new LotManager($cnx);
        $lot = $lotManager->getLot($identifiant);             
         // On teste si le lot a déjà été validé pour le nettoyage pendant
         ?>
        <p class="mt-2 text-center">Lot concerné&hellip;</p>
        <div class="alert alert-secondary text-34 text-center pl-0 pr-0 mb-1" id="numLotTicket" data-id-lot="0">
            <?php echo $lot->getNumlot();
            // Si PAS abats, on affiche le quantième...
            if ($lot->getComposition() != 2) {
                echo Outils::getJourAnByDate(date('Y-m-d'));
            }
            ?>
        </div>  
        
        <?php
    }
} // FIN mode

// Fonction déportée pour mise à jour du détail des changements de rouleaux d'emballage du lot
function showEmballageChangeLot($id_lot)
{

    global
    $cnx,
    $consommablesManager;

    if (!$consommablesManager instanceof ConsommablesManager) {
        $consommablesManager = new ConsommablesManager($cnx);
    }

    // On récupère les changements de rouleaux du jour pour ce lot
    $changements = $consommablesManager->getEmballagesChangementRouleauJour($id_lot);

    if (!$changements || empty($changements)) { ?>
        <tr>
            <td colspan="2">Aucun changement aujourd'hui</td>
        </tr>
    <?php }


    foreach ($changements as $donnees) {

        ?>
        <tr>
            <td><?php echo $donnees['nom']; ?></td>
            <td class="text-right hook-badge-emb-ticket">
                <span class="badge badge-info badge-pill text-16"><?php echo $donnees['nb']; ?></span>
            </td>
            <!--<td colspan="2"><?php // echo $donnees['nom']; ?>
				<br>
                <i class="fa fa-retweet"></i><span class="text-info ml-1 mr-1"><?php // echo $donnees['precedent'] ?></span> <i class="fa fa-long-arrow-alt-right"></i><span class="text-info ml-1 mr-1"><?php // echo $donnees['actuel'] ?></span>
            </td>-->
        </tr>
        <?php

    } // FIN boucle





} // FIN fonction

// Fonction déportée pour mise à jour du détail des rouleaux d'emballage du lot
function showEmballagesDefectueux($id_lot)
{

    global
    $cnx,
    $consommablesManager;

    if (!$consommablesManager instanceof ConsommablesManager) {
        $consommablesManager = new ConsommablesManager($cnx);
    }
    // On récupère les emballages pour le lot
    $emballagesLot = $consommablesManager->getListeEmballagesTicket(['id_lot' => $id_lot]);

    // Si il n'y en a aucun encore, on associe tous les emballages « en cours » dont la famille est associée à la vue « Atelier ».
    if (empty($emballagesLot)) {
        $vueManager = new VueManager($cnx);
        $lotManager = new LotManager($cnx);
        $vueAtl = $vueManager->getVueByCode('atl');
        if (!$vueAtl instanceof Vue) { ?>
            <div class="alert alert-danger">Identification de la vue impossible.<br>Code Erreur : <code>U41ECPS6</code></div><?php exit;
        }
        $lot = $lotManager->getLot($id_lot);
        if (!$lot instanceof Lot) { ?>
            <div class="alert alert-danger">Identification du lot impossible.<br>Code Erreur : <code>CDLUUCVI</code>
            </div><?php exit;
        }

        if ($consommablesManager->setEmballagesVue($vueAtl, $lot)) {

            // Log
            $log = new Log([]);
            $log->setLog_type('info');
            $log->setLog_texte("[ATL] Association des emballages au lot ID " . $id_lot);
            $logsManager = new LogManager($cnx);
            $logsManager->saveLog($log);

        }
    } // FIN test aucun emballage


    $defectueux = $consommablesManager->getEmballagesDefectueuxJour($id_lot);

    if (!$defectueux || empty($defectueux)) { ?>
        <tr>
            <td colspan="2">Aucun défectueux aujourd'hui</td>
        </tr>
    <?php }

    foreach ($defectueux as $donnees) {

        ?>
        <tr>
            <td><?php echo $donnees['nom']; ?></td>
            <td class="text-right hook-badge-emb-ticket">
                <span class="badge badge-danger badge-pill text-16"><?php echo $donnees['qte']; ?></span>
            </td>
        </tr>
        <?php
    } // FIN boucle
    return true;
} // FIN fonction


// Vérifie si c'est l'heure d'afficher une alarme (opérateurs)
function modeCheckAlerteModale()
{
    global $cnx;
    $configManager = new ConfigManager($cnx);

    $heuresConfig = $configManager->getConfig('heures_alarmes');
    if (!$heuresConfig instanceof Config) {
        exit;
    }

    $heures = explode(',', $heuresConfig->getValeur());
    foreach ($heures as $heure) {
        $hmin = explode(':', $heure);
        $h = intval($hmin[0]);
        $m = intval($hmin[1]);
        if ($h == intval(date('H')) && $m == intval(date('i'))) {
            echo '1';
            exit;
        }
    }

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
                <button type="button"
                        class="btn btn-danger btn-large padding-20-40 text-20 form-control btnDeclareTypeIncident"
                        data-type-incident="<?php echo $typeIncident->getId(); ?>">
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


    <input type="hidden" name="mode" value="addIncident"/>
    <input type="hidden" name="type" value="<?php echo $incidentType->getId(); ?>"/>
    <input type="hidden" name="id_lot" value="<?php echo $id_lot; ?>"/>
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
            <textarea class="form-control" placeholder="Commentaire obligatoire..." id="champ_clavier"
                      name="incident_commentaire"></textarea>
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
MODE - Attribue un contrôle LOMA nécessaire au produit
----------------------------------------------------*/
function modeControleLomaPdt()
{

    global $cnx, $logsManager;

    // Vérification des variables
    $id_pdt = isset($_REQUEST['id_pdt']) ? intval($_REQUEST['id_pdt']) : 0;
    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
    $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
    if ($id_pdt == 0 || $id_lot == 0 || $id_froid == 0) {
        echo '-2';
        exit;
    }

    // On récupère l'Id_lot_pdt_froid
    $froidManager = new FroidManager($cnx);
    $froidProduit = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid);
    if (!$froidProduit instanceof FroidProduit) {
        exit;
    }

    $froidManager->setProduitLoma($froidProduit);

    $log = new Log([]);
    $log->setLog_type('info');
    $log->setLog_texte("[ATL] Définition du contrôle LOMA requis sur le produit ID_LOT_PDT_FROID " . $froidProduit->getId_lot_pdt_froid());
    $logsManager->saveLog($log);

    echo $froidProduit->getId_lot_pdt_froid();

    exit;

} // FIN mode


/* ---------------------------------------------------------
MODE -  Enregistre un contrôle Loma sur un produit
----------------------------------------------------------*/
function modeSaveLoma()
{
    global $cnx, $utilisateur, $logsManager;

    // Récupération des variables
    $id_lot_pdt_froid = isset($_REQUEST['id_lot_pdt_froid']) ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
    if ($id_lot_pdt_froid == 0) {
        exit('250INXYW');
    }

    $resultest_nfe = isset($_REQUEST['resultest_nfe']) ? intval($_REQUEST['resultest_nfe']) : -1;  // 1 = OK
    $resultest_fe = isset($_REQUEST['resultest_fe']) ? intval($_REQUEST['resultest_fe']) : -1;  // 1 = OK
    $resultest_inox = isset($_REQUEST['resultest_inox']) ? intval($_REQUEST['resultest_inox']) : -1;  // 1 = OK
    $resultest_pdt = isset($_REQUEST['resultest_pdt']) ? intval($_REQUEST['resultest_pdt']) : -1;  // 0 = OK

    $controleOk = $resultest_nfe == 0 && $resultest_fe == 0 && $resultest_inox == 0 && $resultest_pdt == 1;

    // Si on a testé les plaquettes (loma test avant)
    if ($resultest_nfe >= 0) {

        $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
        $froidManager = new FroidManager($cnx);
        $froid = $froidManager->getFroid($id_froid);
        if (!$froid instanceof Froid) {
            exit('ERR_OBJFROID_' . $id_froid);
        }

        // On vérifie qu'on écrase pas les tests déjà enregistré quand même...
        if ($froid->getTest_avant_fe() < 0) {

            $froid->setTest_avant_fe($resultest_fe);
            $froid->setTest_avant_nfe($resultest_nfe);
            $froid->setTest_avant_inox($resultest_inox);
            if (!$froidManager->saveFroid($froid)) {
                exit('ERR_SAVEFROID_TESTSAVANT_' . $id_froid);
            }

            // Log
            $log = new Log([]);
            $log->setLog_type('info');
            $log->setLog_texte("[ATL] Enregistrement contrôle LOMA AVANT sur froid #" . $id_froid);
            $logsManager->saveLog($log);

        } // FIN test de non-écrabouillement des datas

    } // FIN test loma avant


    $commentaires = isset($_REQUEST['commentaires']) ? trim(strip_tags(htmlspecialchars($_REQUEST['commentaires']))) : '';

    // Ici on est ok... on procède
    $lomaManager = new LomaManager($cnx);

    // On tente de récupérer si un loma existe déjà pour ce produit dans cette op pour MAJ
    $loma = $lomaManager->getLomaByIdLotPdtFroid($id_lot_pdt_froid);
    if (!$loma instanceof Loma) {
        $loma = new Loma([]);
        $loma->setId_lot_pdt_froid($id_lot_pdt_froid);
    }

    $loma->setTest_pdt($resultest_pdt);
    $loma->setCommentaire($commentaires);
    $loma->setDate_test(date('Y-m-d H:i:s'));
    $loma->setId_user_visa($utilisateur->getId());

    $ressave = $lomaManager->saveLoma($loma);
    if (is_numeric($ressave)) {
        $loma->setId($ressave);
    }

    // SI le loma est bien enregistré en base, on enregistre la demande de validation admin + gestion alerte
    if ($ressave) {

        // Log
        $log = new Log([]);
        $log->setLog_type('info');
        $log->setLog_texte("[ATL] Contrôle LOMA sur produit ID_LOT_PDT_FROID " . $id_lot_pdt_froid);
        $logsManager->saveLog($log);

        // Validation admin
        $vueManager = new VueManager($cnx);
        $froidManager = new FroidManager($cnx);

        // On récupère l'objet de la Vue
        $vue = $vueManager->getVueByCode('atl');
        if (!$vue instanceof Vue) {
            exit('3V1GV2E8');
        }
        if (intval($loma->getId()) == 0) {
            exit('MYWYY8QB');
        }

        // On récupère le lot depuis le lotpdtfroid
        $lotpdtFroid = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
        if (!$lotpdtFroid instanceof FroidProduit) {
            exit('NONMNT2Y');
        }
        $id_lot = $lotpdtFroid->getId_lot();


        $validation = new Validation([]);
        $validation->setType(3); // 3 = Loma
        $validation->setId_vue($vue->getId());
        $validation->setId_liaison($loma->getId());
        $validationManager = new ValidationManager($cnx);
        if ($validationManager->saveValidation($validation)) {
            $validationManager->addValidationLot($validation, $id_lot);
        }

        // On vérifie que l'alerte est activée
        $configManager = new ConfigManager($cnx);
        $activationAlerte = $configManager->getConfig('alerte4_actif');
        if ($activationAlerte instanceof Config && intval($activationAlerte->getValeur()) == 1) {

            // Si le test est pas glop...
            if (!$controleOk) {

                $texte = $resultest_pdt == 1 ? 'Contrôle LOMA positif sur produit' : 'Test non détecté';

                $alerteManager = new AlerteManager($cnx);
                $froidManager = new FroidManager($cnx);
                $id_froid_type = intval($froidManager->getFroidTypeByCode('atl'));
                $alerte = new Alerte([]);
                $alerte->setId_lot($id_lot);
                $alerte->setType(3);            // Type 3 = Loma
                $alerte->setId_froid($lotpdtFroid->getId_froid());
                $alerte->setId_froid_type($id_froid_type);
                $alerte->setNumlot($lotpdtFroid->getNumlot());
                $alerte->setDate(date('Y-m-d H:i:s'));
                $alerte->setId_user($utilisateur->getId());
                $alerte->setNom_user($utilisateur->getNomComplet());
                $alerte->setValeur($texte);
                if ($alerteManager->saveAlerte($alerte)) {
                    $alerteManager->envoiMailAlerte($alerte);
                }


            } // FIN test en alerte
        } // FIN test alerte loma active
    } // FIN test LOMA bien enregistré

    echo $ressave ? '' : 'VNWJ0952';

    exit;

} // FIN mode

function modeSaveLomaApres()
{

    global $cnx, $logsManager,$utilisateur;

    $froidManager = new FroidManager($cnx);

    $id_lot_pdt_froid = isset($_REQUEST['id_lot_pdt_froid']) ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
    if ($id_lot_pdt_froid == 0) {
        exit('250INXYW');
    }

    // Récupération des variables
    $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;

    $froid = $froidManager->getFroid($id_froid);
    if (!$froid instanceof Froid) {
        exit('ERR_OBJFROID_' . $id_froid);
    }

    $resultest_nfe = isset($_REQUEST['resultest_fin_nfe']) ? intval($_REQUEST['resultest_fin_nfe']) : -1;  // 1 = OK
    $resultest_fe = isset($_REQUEST['resultest_fin_fe']) ? intval($_REQUEST['resultest_fin_fe']) : -1;  // 1 = OK
    $resultest_inox = isset($_REQUEST['resultest_fin_inox']) ? intval($_REQUEST['resultest_fin_inox']) : -1;  // 1 = OK
    $resultest_pdt = isset($_REQUEST['resultest_fin_pdt']) ? intval($_REQUEST['resultest_fin_pdt']) : -1;  // 1 = OK

    $controleOk = $resultest_nfe == 0 && $resultest_fe == 0 && $resultest_inox == 0 && $resultest_pdt == 1;

    $froid->setTest_apres_fe($resultest_fe);
    $froid->setTest_apres_nfe($resultest_nfe);
    $froid->setTest_apres_inox($resultest_inox);
    if (!$froidManager->saveFroid($froid)) {
        exit('ERR_SAVEFROID_TESTSAPRES_' . $id_froid);
    }

    // Si on a testé les plaquettes (loma test apres)
    if ($resultest_nfe >= 0) {

        $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
        $froidManager = new FroidManager($cnx);
        $froid = $froidManager->getFroid($id_froid);
        if (!$froid instanceof Froid) {
            exit('ERR_OBJFROID_' . $id_froid);
        }

        // On vérifie qu'on écrase pas les tests déjà enregistré quand même...
        if ($froid->getTest_apres_fe() < 0) {

            $froid->setTest_apres_fe($resultest_fe);
            $froid->setTest_apres_nfe($resultest_nfe);
            $froid->setTest_apres_inox($resultest_inox);
            if (!$froidManager->saveFroid($froid)) {
                exit('ERR_SAVEFROID_TESTSAVANT_' . $id_froid);
            }

            // Log
            $log = new Log([]);
            $log->setLog_type('info');
            $log->setLog_texte("[ATL] Enregistrement contrôle LOMA APRES sur froid #" . $id_froid);
            $logsManager->saveLog($log);

        } // FIN test de non-écrabouillement des datas

    } // FIN test loma apres


    $commentaires = isset($_REQUEST['commentaires']) ? trim(strip_tags(htmlspecialchars($_REQUEST['commentaires']))) : '';

    // Ici on est ok... on procède
    $lomaManager = new LomaManager($cnx);

    // On tente de récupérer si un loma existe déjà pour ce produit dans cette op pour MAJ
    $loma = $lomaManager->getLomaByIdLotPdtFroid($id_lot_pdt_froid);
    if (!$loma instanceof Loma) {
        $loma = new Loma([]);
        $loma->setId_lot_pdt_froid($id_lot_pdt_froid);
    }

    $loma->setTest_pdt($resultest_pdt);
    $loma->setCommentaire($commentaires);
    $loma->setDate_test(date('Y-m-d H:i:s'));
    $loma->setId_user_visa($utilisateur->getId());

    $ressave = $lomaManager->saveLoma($loma);
    if (is_numeric($ressave)) {
        $loma->setId($ressave);
    }

    // SI le loma est bien enregistré en base, on enregistre la demande de validation admin + gestion alerte
    if ($ressave) {

        // Log
        $log = new Log([]);
        $log->setLog_type('info');
        $log->setLog_texte("[ATL] Contrôle LOMA sur produit ID_LOT_PDT_FROID " . $id_lot_pdt_froid);
        $logsManager->saveLog($log);

        // Validation admin
        $vueManager = new VueManager($cnx);
        $froidManager = new FroidManager($cnx);

        // On récupère l'objet de la Vue
        $vue = $vueManager->getVueByCode('atl');
        if (!$vue instanceof Vue) {
            exit('3V1GV2E8');
        }
        if (intval($loma->getId()) == 0) {
            exit('MYWYY8QB');
        }

        // On récupère le lot depuis le lotpdtfroid
        $lotpdtFroid = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
        if (!$lotpdtFroid instanceof FroidProduit) {
            exit('NONMNT2Y');
        }
        $id_lot = $lotpdtFroid->getId_lot();


        $validation = new Validation([]);
        $validation->setType(3); // 3 = Loma
        $validation->setId_vue($vue->getId());
        $validation->setId_liaison($loma->getId());
        $validationManager = new ValidationManager($cnx);
        if ($validationManager->saveValidation($validation)) {
            $validationManager->addValidationLot($validation, $id_lot);
        }

        // On vérifie que l'alerte est activée
        $configManager = new ConfigManager($cnx);
        $activationAlerte = $configManager->getConfig('alerte4_actif');
        if ($activationAlerte instanceof Config && intval($activationAlerte->getValeur()) == 1) {

            // Si le test est pas glop...
            if (!$controleOk) {

                $texte = $resultest_pdt == 1 ? 'Contrôle LOMA positif sur produit' : 'Test non détecté';

                $alerteManager = new AlerteManager($cnx);
                $froidManager = new FroidManager($cnx);
                $id_froid_type = intval($froidManager->getFroidTypeByCode('atl'));
                $alerte = new Alerte([]);
                $alerte->setId_lot($id_lot);
                $alerte->setType(3);            // Type 3 = Loma
                $alerte->setId_froid($lotpdtFroid->getId_froid());
                $alerte->setId_froid_type($id_froid_type);
                $alerte->setNumlot($lotpdtFroid->getNumlot());
                $alerte->setDate(date('Y-m-d H:i:s'));
                $alerte->setId_user($utilisateur->getId());
                $alerte->setNom_user($utilisateur->getNomComplet());
                $alerte->setValeur($texte);
                if ($alerteManager->saveAlerte($alerte)) {
                    $alerteManager->envoiMailAlerte($alerte);
                }


            } // FIN test en alerte
        } // FIN test alerte loma active
    } // FIN test LOMA bien enregistré

    // Log
    $log = new Log([]);
    $log->setLog_type('info');
    $log->setLog_texte("[ATL] Enregistrement contrôle LOMA APRES sur froid #" . $id_froid);
    $logsManager->saveLog($log);

    echo '1';
    exit;


}

// FIN mode

/* ---------------------------------------------------------
MODE -  Enregistre un contrôle Loma sur un produit
----------------------------------------------------------*/
function modeSaveLomaEncours()
{
    global $cnx, $utilisateur, $logsManager;

    // Récupération des variables
    $id_lot_pdt_froid = isset($_REQUEST['id_lot_pdt_froid']) ? intval($_REQUEST['id_lot_pdt_froid']) : 0;
    if ($id_lot_pdt_froid == 0) {
        exit('250INXYW');
    }

    $resultest_pdt = isset($_REQUEST['resultest_encours_pdt']) ? intval($_REQUEST['resultest_encours_pdt']) : -1;  // 0 = OK

    $controleOk =  $resultest_pdt == 1;

    $commentaire = isset($_REQUEST['commentaires']) ? trim(strip_tags(htmlspecialchars($_REQUEST['commentaires']))) : '';

    $type = isset($_REQUEST['incident_type']) ? intval($_REQUEST['incident_type']) : 0;
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

    if (strlen($commentaire) != 0 && $controleOk) {
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
    }


    $lomaManager = new LomaManager($cnx);
    #Enregistrement du controle Loma du produit en cours dans la base de donnée 
    $loma = $lomaManager->getLomaByIdLotPdtFroid($id_lot_pdt_froid);
    if (!$loma instanceof Loma) {
        $loma = new Loma([]);
        $loma->setId_lot_pdt_froid($id_lot_pdt_froid);
    }

    $loma->setId_lot_pdt_froid($id_lot_pdt_froid);
    $loma->setTest_pdt($resultest_pdt);
    $loma->setCommentaire($commentaire);
    $loma->setDate_test(date('Y-m-d H:i:s'));
    $loma->setId_user_visa($utilisateur->getId());


    $ressave = $lomaManager->saveLoma($loma);
    if (is_numeric($ressave)) {
        $loma->setId($ressave);
    }

    if($ressave){
        $log = new Log([]);
        $log->setLog_type('info');
        $log->setLog_texte("[ATL] Contrôle LOMA sur produit ID_LOT_PDT_FROID " . $id_lot_pdt_froid);
        $logsManager->saveLog($log);

        $vueManager = new VueManager($cnx);
        $froidManager = new FroidManager($cnx);

        $vue = $vueManager->getVueByCode('atl');
        if (!$vue instanceof Vue) {
            exit('3V1GV2E8');
        }
        if (intval($loma->getId()) == 0) {
            exit('MYWYY8QB');
        }
        // On récupère le lot depuis le lotpdtfroid
        $lotpdtFroid = $froidManager->getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid);
        if (!$lotpdtFroid instanceof FroidProduit) {
            exit('NONMNT2Y');
        }
        $id_lot = $lotpdtFroid->getId_lot();

        $validation = new Validation([]);
        $validation->setType(3); // 3 = Loma
        $validation->setId_vue($vue->getId());
        $validation->setId_liaison($loma->getId());
        $validationManager = new ValidationManager($cnx);
        if ($validationManager->saveValidation($validation)) {
            $validationManager->addValidationLot($validation, $id_lot);
        }


        $configManager = new ConfigManager($cnx);
        $activationAlerte = $configManager->getConfig('alerte4_actif');
        if ($activationAlerte instanceof Config && intval($activationAlerte->getValeur()) == 1) {

            // Si le test est pas glop...
            if (!$controleOk) {

                $texte = $resultest_pdt == 1 ? 'Contrôle LOMA positif sur produit' : 'Test non détecté';

                $alerteManager = new AlerteManager($cnx);
                $froidManager = new FroidManager($cnx);
                $id_froid_type = intval($froidManager->getFroidTypeByCode('atl'));
                $alerte = new Alerte([]);
                $alerte->setId_lot($id_lot);
                $alerte->setType(3);            // Type 3 = Loma
                $alerte->setId_froid($lotpdtFroid->getId_froid());
                $alerte->setId_froid_type($id_froid_type);
                $alerte->setNumlot($lotpdtFroid->getNumlot());
                $alerte->setDate(date('Y-m-d H:i:s'));
                $alerte->setId_user($utilisateur->getId());
                $alerte->setNom_user($utilisateur->getNomComplet());
                $alerte->setValeur($texte);
                if ($alerteManager->saveAlerte($alerte)) {
                    $alerteManager->envoiMailAlerte($alerte);
                }


            } // FIN test en alerte
        }


    }

    echo '';
    exit;

} // FIN mode

function addPdtFroid($id_lot_loma = 0, $id_pdt_loma = 0)
{

    global $utilisateur, $cnx, $logsManager;

    if (!$utilisateur instanceof User) {
        exit('-1');
    }

    // Récupération des variables
    $id_pdt = isset($_REQUEST['id_pdt']) ? intval($_REQUEST['id_pdt']) : $id_pdt_loma;
    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : $id_lot_loma;
    $id_froid = isset($_REQUEST['id_froid']) ? intval($_REQUEST['id_froid']) : 0;
    $nb_colis = isset($_REQUEST['nb_colis']) ? intval($_REQUEST['nb_colis']) : 0;
    $nb_colis_add = isset($_REQUEST['nb_colis_add']) ? intval($_REQUEST['nb_colis_add']) : $nb_colis;
    $poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0.0;
    $poids_add = isset($_REQUEST['poids_add']) ? floatval($_REQUEST['poids_add']) : $poids;
    $quantieme = isset($_REQUEST['quantieme']) ? trim($_REQUEST['quantieme']) : '';
    $id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
    $id_compo = isset($_REQUEST['id_compo']) ? intval($_REQUEST['id_compo']) : 0;
    $new_palette = isset($_REQUEST['new_palette']) ? boolval($_REQUEST['new_palette']) : false;


    // Instanciation des managers
    $froidManager = new FroidManager($cnx);
    $lotsManager = new LotManager($cnx);
    $palettesManager = new PalettesManager($cnx);

    // Gestion des erreurs
    if ($id_lot == 0) {
        exit('-1');
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        exit('-1');
    }

    $lot->setDate_maj(date('Y-m-d H:i:s'));


    // v1.1, on commence par créer l'OP de froid si elle n'existe pas
    if ($id_froid == 0) {

        $typeCgl = $froidManager->getFroidTypeByCode('atl');
        if (!$typeCgl || $typeCgl == 0) {
            exit('-3');
        }

        $froid = new Froid([]);
        $froid->setId_type($typeCgl);
        $froid->setId_user_maj($utilisateur->getId());
        $froid->setDate_entree(date('Y-m-d H:i:s'));
        $id_froid = $froidManager->saveFroid($froid);

        if (!$id_froid || $id_froid == 0) {
            exit('-4');
        }
        // Puisqu'on viens de créer l'op de froid, le lot concerné est officiellement dans la vue atelier
        $lotsManager->addLotVue($lot, 'atl');       // Affecte le lot à la vue atelier

        // Log
        $log = new Log([]);
        $log->setLog_type('info');
        $log->setLog_texte("[ATL] Création du traitement OP froid ID " . $id_froid . " à l'ajout du premier produit.");
        $logsManager->saveLog($log);

    } // FIN test id froid

    if ($id_froid == 0) {
        exit('-5');
    }

    // On tente de récupérer l'objet pdt/lot/froid

    $update = true;

    // On tente de récupérer l'objet pdt/lot/froid/quantieme/palette
    $pdtLotFroid = $froidManager->getFroidProduitObjet($id_lot, $id_pdt, $id_froid, $quantieme, $id_palette);
    if (!$pdtLotFroid instanceof FroidProduit) {
        $update = false;
        // v1.1 On crée le pdt/lot/froid si on a pas pu le récupérer
        $pdtLotFroid = new FroidProduit([]);
        $pdtLotFroid->setId_lot($id_lot);
        $pdtLotFroid->setId_pdt($id_pdt);
        $pdtLotFroid->setId_froid($id_froid);
    }

    $pdtLotFroid->setId_palette($id_palette);
    if (strtolower($quantieme) != 'a' && $lot->getComposition() != 2 && $quantieme != '<br') {
        $pdtLotFroid->setQuantieme($quantieme);
        $lotsManager->addQuantiemeIfNotExist($id_lot, $quantieme);
    }

    // On met à jour la date et l'user pour histo
    if ($update) {
        $pdtLotFroid->setDate_maj(date('Y-m-d H:i:s'));
        $pdtLotFroid->setUser_maj($utilisateur->getId());

    } else {
        $pdtLotFroid->setDate_add(date('Y-m-d H:i:s'));
        $pdtLotFroid->setUser_add($utilisateur->getId());
    }
    $id_pdtfroid = $froidManager->saveFroidProduit($pdtLotFroid);

    $lotsManager->saveLot($lot);

    // On enregistre l'id_lot_pdt_froid dans la table des compositions de palettes
    $id_lot_pdt_froid = $pdtLotFroid->getId_lot_pdt_froid() > 0 ? $pdtLotFroid->getId_lot_pdt_froid() : $id_pdtfroid;

    // Si on change de palette, l'id_pdt_froid de la compo est le nouveau
    if ($new_palette) {
        $id_lot_pdt_froid = $id_pdtfroid;
    }

    // Une compo a été créé dynamiquement à l'étape 3
    if ($id_lot_pdt_froid > 0) {
        $palettesManager = new PalettesManager($cnx);
        $compo = $palettesManager->getComposition($id_compo);
        if ($compo instanceof PaletteComposition) {


            $compo->setId_lot_pdt_froid($id_lot_pdt_froid);
            $compo->setSupprime(0);                       // On retire le flag "supprimé" qui empèche de faire n'importe quoi si on s'amuse a cliquer sur plein de palettes dans l'édition

            // on met à jour le poids et le nb de colis de la compo car il a pu être modifié apres la selection de la palette
            $compo->setPoids($poids);
            $compo->setNb_colis($nb_colis);

            $palettesManager->savePaletteComposition($compo);
            $palettesManager->purgeComposSupprimeesByFroidProduit($id_lot_pdt_froid);
        }
    }

    // Une fois le produit froid créé et la compo intégrée, on calcul le total pour mettre à jour le froidproduit
    $pdtLotFroid->setPoids($palettesManager->getPoidsTotalFroidProduit($id_lot_pdt_froid));
    $pdtLotFroid->setNb_colis($palettesManager->getNbColisTotalFroidProduit($id_lot_pdt_froid));
    $froidManager->saveFroidProduit($pdtLotFroid);

    $logVerbe = $update ? "Modification" : "Ajout";

    // Log
    $log = new Log([]);
    $log->setLog_type('info');
    $log->setLog_texte("[ATL] " . $logVerbe . " du produit ID " . $id_pdt . " sur le lot ID " . $id_lot . " à l'OP froid ID " . $id_froid . " quantième " . $quantieme . ", nb_colis = " . $nb_colis . " poids = " . $poids);
    $logsManager->saveLog($log);

    // Retourne l'id de l'OP de froid pour maj CallBack aJax
    return $id_froid;
}

