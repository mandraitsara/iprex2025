<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax EXPEDITION
------------------------------------------------------*/
ini_set('display_errors', 1);
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

$prpManager = new PrpOpsManager($cnx);
$fonctionNom = 'mode' . ucfirst($mode);
if (function_exists($fonctionNom)) {
    $fonctionNom();
}

/* ------------------------------------------
FONCTION - Message d'erreur standard
-------------------------------------------*/
function erreur()
{
?>
    <div class="alert alert-danger text-center">
        <i class="fa fa-exclamation-triangle fa-3x mb-2"></i><br>
        <p>Erreur de récupération des données !</p>
    </div>
    <?php
    exit;
} // FIN fonctions


/* ------------------------------------------
MODE - Charge le contenu d'une étape de vue
-------------------------------------------*/
function modeChargeEtapeVue()
{

    global $cnx, $utilisateur, $prpManager;



    $etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;



    /** ----------------------------------------
     * DEV - On affiche l'étape pour débug
     *  ----------------------------------- */
    if ($utilisateur->isDev()) { ?>
        <div class="dev-etape-vue"><i class="fa fa-user-secret fa-lg mr-1"></i>Etape <kbd><?php echo $etape; ?></kbd></div>
    <?php } // FIN test DEV


    /** ----------------------------------------
     * Etape        : 0
     * Description  : Selection de l'action
     *  ----------------------------------- */
    if ($etape == 0) { ?>

        <div class="row justify-content-md-center mt-5">


            <div class="col-3">
                <button type="button" class="btn btn-info btn-lg padding-50 form-control btnAction" data-etape="1">
                    <i class="fa fa-thermometer-three-quarters fa-2x mb-2 margin-left-15"></i>
                    <i class="fa fa-check position-relative" style="top:-5px; "></i>
                    <div>Surveillance du PRP OP<p class="texte-fin text-14">Contrôle de la température et des emballages</p>
                    </div>
                </button>
            </div>

            <div class="col-3">
                <button type="button" class="btn btn-secondary btn-lg padding-50 form-control btnAction" data-etape="20">
                    <i class="fa fa-pallet fa-2x mb-2 margin-left-15"></i>
                    <i class="fa fa-retweet position-relative" style="top:-5px; "></i>
                    <div>Palettes et crochets<p class="texte-fin text-14">Echanges libres hors PRP OP</p>
                    </div>
                </button>
            </div>


        </div>

    <?php } // FIN étape



    /** ----------------------------------------
     * Etape        : 1 / 20
     * Description  : Selection du transporteur
     *  ----------------------------------- */
    if ($etape == 1 || $etape == 20) {


        $fa1 = $etape == 1 ? 'thermometer-three-quarters' : 'pallet';
        $fa2 = $etape == 1 ? 'check' : 'retweet';
        $txt1 = $etape == 1 ? 'Surveillance du PRP OP Expédition' : 'Palettes et crochets';
        $txt2 = $etape == 1 ? 'Contrôle de la température et des emballages' : 'Echanges libres hors PRP OP';
    ?>

        <h2 class="gris-9 mt-4 text-center">
            <i class="fa fa-<?php echo $fa1; ?> margin-left-15"></i>
            <i class="fa fa-<?php echo $fa2; ?> position-relative text-16" style="left:-5px;top:-5px;"></i>
            <?php echo $txt1; ?> <p class="texte-fin text-22"><?php echo $txt2; ?></p>
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
                    <div class="card bg-dark text-white pointeur carte-exp-trans" data-id-trans="<?php echo $trans->getId(); ?>">
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
     * Description  : Selection du BL/client
     *  ----------------------------------- */
    if ($etape == 2) {

        $tiersManager = new TiersManager($cnx);
        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }

        // On propose les BL de ce transporteur ayant été générés il y a moins d'une semaine et pour lesquels on a pas encore fait de PRP OP
        if ($prp->getId_transporteur() == 0) {
            exit("Identification du transporteur échouée !");
        }

        // Récupération des BL éligibles
        $bls = $prpManager->getListeBlsAexpedier($prp);
    ?>


        <h2 class="gris-9 mt-4 text-center">
            <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
            <i class="fa fa-check position-relative text-16" style="left:-5px;top:-5px;"></i>
            Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages</p>
        </h2>


        <h4 class="text-center">Sélectionnez le ou les BL <i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>

        <div class="row justify-content-md-center">

            <?php

            if (empty($bls)) {?>
                <div class="alert alert-warning padding-50 text-center"><span class="text-24">Aucun client disponible pour ce transporteur !</span>
                    <p class="text-16"><em>Seuls les clients ayant un BL généré, daté de moins de 10 jours et non expédié peuvent apparaitre ici.<br>Contactez un adminstrateur...</em></p>
                </div>
            <?php }

            // Boucle sur les BL possibles
            foreach ($bls as $bl) {

                // On idientifie le client du BL
                $client = $tiersManager->getTiers($bl->getId_tiers_livraison());
                if (!$client instanceof Tiers) {
                    continue;
                }
                $trans = $tiersManager->getTiers($prp->getId_transporteur());
                if (!$trans instanceof Tiers) {
                    continue;
                }
            ?>

                <div class="col-2 mb-3">
                    <div class="card bg-dark text-white pointeur carte-exp-bl" data-id-bl="<?php echo $bl->getId(); ?>">
                        <div class="card-header text-center texte-fin"><?php echo $trans->getNom(); ?></div>
                        <div class="card-body text-center height-150 text-20"><?php echo $client->getNom(); ?></div>
                        <div class="card-footer text-center texte-fin text-20 text-warning "><?php echo $bl->getNum_bl(); ?></div>
                    </div>


                </div>

            <?php } // FIN boucle clients

            ?>
        </div>


    <?php } // FIN étape

    /** ----------------------------------------
     * Etape        : 3
     * Description  : Confirmation de la date
     *  ----------------------------------- */
    if ($etape == 3) {

        $blManager = new BlManager($cnx);

        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }

        // On prends la date du premier BL (oui oui c'est arbitraire)
        $bl = $blManager->getBl($prp->getBls()[0]);
        if (!$bl instanceof Bl) {
            $bl = new Bl(['date' => "0000-00-00"]);
        }

        // On prends la date si on en a déjà une dans l'objet, sinon celle du dernier BL du client
        $date = $prp->getDate() != '' && $prp->getDate() != '0000-00-00' ? $prp->getDate() : date('Y-m-d');

        // En cas de problème, on prends la date du jour
        if ($date == '' || !Outils::verifDateSql($date)) {
            $date = date('Y-m-d');
        }

        $dateArray = explode('-', $date);
        $jour = isset($dateArray[2]) ? intval($dateArray[2]) : 0;
        $mois = isset($dateArray[1]) ? intval($dateArray[1]) : 0;
        $an = isset($dateArray[0]) ? intval($dateArray[0]) : 0;

    ?>

        <h2 class="gris-9 mt-4 text-center">
            <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
            <i class="fa fa-check position-relative text-16" style="left:-5px;top:-5px;"></i>
            Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages</p>
        </h2>
        <h4 class="text-center">Confirmez la date du contrôle <i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>

        <div class="row justify-content-md-center">
            <div class="col-2">
                <select class="selectpicker form-control selectpicker-tactile prp-jour" data-size="12">
                    <?php
                    for ($j = 1; $j <= 31; $j++) { ?>
                        <option value="<?php echo $j; ?>" <?php echo $j == $jour ? 'selected' : ''; ?>><?php echo $j; ?></option>
                    <?php }
                    ?>
                </select>
            </div>
            <div class="col-4">
                <select class="selectpicker form-control selectpicker-tactile prp-mois" data-size="11">
                    <?php
                    for ($m = 1; $m <= 12; $m++) { ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $mois ? 'selected' : ''; ?>><?php echo ucfirst(Outils::getMoisIntListe()[$m]); ?></option>
                    <?php }
                    ?>
                </select>
            </div>
            <div class="col-2">
                <select class="selectpicker form-control selectpicker-tactile prp-an" data-size="12">
                    <?php
                    for ($a = intval(date('Y')) - 1; $a <= intval(date('Y')); $a++) { ?>
                        <option value="<?php echo $a; ?>" <?php echo $a == $an ? 'selected' : ''; ?>><?php echo $a; ?></option>
                    <?php }
                    ?>
                </select>
            </div>
        </div>
        <div class="row justify-content-md-center">
            <div class="col-2 mt-3">
                <button type="button" class="btn btn-success btn-lg form-control padding-20 btnValideDate"><i class="fa fa-check mr-1"></i> Valider</button>
            </div>
        </div>

    <?php } // FIN étape

    /** ----------------------------------------
     * Etape        : 4
     * Description  : Conformité de commande
     *  ----------------------------------- */
    if ($etape == 4) {

        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }

    ?>

        <h2 class="gris-9 mt-4 text-center">
            <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
            <i class="fa fa-check position-relative text-16" style="left:-5px;top:-5px;"></i>
            Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages</p>
        </h2>
        <h4 class="text-center">Conformité de la commande <i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
        <div class="row justify-content-md-center mt-3">

            <div class="col-3">
                <button type="button" class="btn btn-danger btn-lg form-control padding-25 text-30 btnCmdConforme" data-conformite="0"><i class="fa fa-times fa-lg mr-3"></i>Non Conforme</button>
            </div>

            <div class="col-3">
                <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnCmdConforme" data-conformite="1"><i class="fa fa-check fa-lg mr-3"></i>Conforme</button>
            </div>


        <?php } // FIN étape

    /** ----------------------------------------
     * Etape        : 5
     * Description  : T° Surface
     *  ----------------------------------- */
    if ($etape == 5) {

        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }

        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
                <i class="fa fa-check position-relative text-16" style="left:-5px;top:-5px;"></i>
                Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages</p>
            </h2>
            <h4 class="text-center">T° en surface entre 2 colis (emballe) <i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
            <div class="row justify-content-md-center mt-3">

                <div class="col-4">
                    <div class="alert alert-secondary">
                        <div class="input-group clavier">
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
                        </div>
                    </div>
                </div> <!-- FIN col gauche pour pavé numérique -->
                <div class="col-4">
                    <div class="row">
                        <div class="col-12 input-group">
                            <input type="text" class="form-control text-6em text-center" placeholder="00.00" value="<?php echo $prp->getT_surface() != 0 ? $prp->getT_surface() : ''; ?>" id="temp" maxlength="15" />
                            <div class="input-group-append"><span class="input-group-text text-48"> °C&nbsp;</span></div>
                            <div class="input-group-append"><button type="button" class="btn btn-warning text-48 btnSupprChar"><i class="fa fa-backspace"></i></button></div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col">
                            <div class="alert alert-secondary mb-0">
                                Contrôle des produits avec le thermomètre portatif à sonde.
                                <ul class="margin-top-5">
                                    <li>Pour les produits frais :
                                        <p class="texte-fin text-13">Si la température du produit > limite en surface : prise de la température à coeur.<br>
                                            Si elle est <= 4°C pour la viande fraiche : conforme. Sinon destruction de la marchandise.</p>
                                    </li>
                                    <li>Pour les produits surgelés :
                                        <p class="texte-fin text-13">Si la températeur est > -18°C mais < -15°C (en absence de panne) : prologation du stockage en CF négative, sinon destruction de la marchandise.</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <button type="button" class="form-control mb-2 btn btn-danger btn-large text-18 padding-20 btnTempConforme" data-conformite="0">
                                <i class="fa fa-times mr-1"></i> Non-conforme</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="form-control mb-2 btn btn-success btn-large text-18 padding-20 btnTempConforme" data-conformite="1">
                                <i class="fa fa-check mr-1"></i> Conforme</button>
                        </div>
                    </div>
                </div> <!-- FIN col droite -->
            </div> <!-- FIN conteneur ROW -->

        <?php } // FIN étape

    /** ----------------------------------------
     * Etape        : 6
     * Description  : T° Camion
     *  ----------------------------------- */
    if ($etape == 6) {

        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }

        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
                <i class="fa fa-check position-relative text-16" style="left:-5px;top:-5px;"></i>
                Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages</p>
            </h2>
            <h4 class="text-center">T° de la caisse du camion avant chargement<i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
            <div class="row justify-content-md-center mt-3">

                <div class="col-4">
                    <div class="alert alert-secondary">
                        <div class="input-group clavier">
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
                        </div>
                    </div>
                </div> <!-- FIN col gauche pour pavé numérique -->
                <div class="col-4">
                    <div class="row">
                        <div class="col-12 input-group">
                            <input type="text" class="form-control text-6em text-center" placeholder="00.00" value="<?php echo $prp->getT_camion() != 0 ? $prp->getT_camion() : ''; ?>" id="temp" maxlength="15" />
                            <div class="input-group-append"><span class="input-group-text text-48"> °C&nbsp;</span></div>
                            <div class="input-group-append"><button type="button" class="btn btn-warning text-48 btnSupprChar"><i class="fa fa-backspace"></i></button></div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col">
                            <div class="alert alert-secondary mb-0">
                                Contrôle de la température de la caisse avec le thermomètre laser.
                                <p>Si la température de la caisse est non-conforme : attente avant chargement de la mise en température conforme.</p>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <button type="button" class="form-control mb-2 btn btn-danger btn-large text-18 padding-20 btnTempConforme" data-conformite="0">
                                <i class="fa fa-times mr-1"></i> Non-conforme</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="form-control mb-2 btn btn-success btn-large text-18 padding-20 btnTempConforme" data-conformite="1">
                                <i class="fa fa-check mr-1"></i> Conforme</button>
                        </div>
                    </div>
                </div> <!-- FIN col droite -->
            </div> <!-- FIN conteneur ROW -->

        <?php } // FIN étape

    /** ----------------------------------------
     * Etape        : 7
     * Description  : Conformité emballage
     *  ----------------------------------- */
    if ($etape == 7) {

        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }

        //Récupérer le BL associé à cette prp
        $bls = $prpManager->getBlAexpedier($prp);

        #Récuperer le lot associé à chaque BL pour le suivi de contrôle
        global $lotBl;
        $lotBl = [];


        foreach ($bls as $bl) {
            if ($bl !== false) {
                $reflexionClass = new ReflectionClass($bl);
                $lignesProprety = $reflexionClass->getProperty('lignes');
                $lignesProprety->setAccessible(true);
                $lignes = $lignesProprety->getValue($bl);


                foreach ($lignes as $ligne) {
                    $ligneReflection = new ReflectionClass($ligne);
                    $idLotProprety = $ligneReflection->getProperty('id_lot');
                    $idLotProprety->setAccessible(true);

                    $idLot = $idLotProprety->getValue($ligne);
                    $lotBl[] = $idLot;
                }
            }
        }


        $pvisuManager =  new PvisuApresManager($cnx);
        $pointsControlesManager = new PointsControleManager($cnx);
        $points = $pointsControlesManager->getListePointsControles(false, 4);

        $bo = false;

        $id_apres = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_apres > 0) {
            // Admin - edite un avant
            $pvisu = $pvisuManager->getPvisuApres($id_apres);
            $bo = true;
        } else {
            // On récupère les données enregistrées du jour s'il y en a
            $pvisu = $pvisuManager->getPvisuApresJour('', false);
        }
        if (!$pvisu instanceof PvisuApres) {
            $pvisu = new PvisuApres([]);
        }

        $visuPoints = $pvisuManager->getListePvisuApresPoints($pvisu, true);
        if (!is_array($visuPoints)) {
            $visuPoints = [];
        }

        if ($pvisu->getDate() == '') {
            $pvisu->setDate(date('Y-m-d'));
        }

        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
                <i class="fa fa-check position-relative text-16" style="left:-5px;top:-5px;"></i>
                Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages</p>
            </h2>
            <h4 class="text-center mb-3 ">Conformité emballage palette filmée <i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
            <form class="col-6 offset-3 mb-3 justify-content-md-center" id="pointControleExpedition">
                <input type="hidden" name="mode" value="savePvisuApres" />
                <input type="hidden" name="id_prp" value="<?php echo $prp->getId(); ?>" />
                <?php
                    global $lotBl;
                    foreach ($lotBl as $value) {
                        ?>
                        <input type="hidden" name="id_lot[]" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" />
                    <?php
                     }
                ?>
                <div class="mb-3">
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
                <div class="row justify-content-md-center mt-3 col-12">

                    <div class="col-6">
                        <button type="button" class="btn btn-danger btn-lg form-control padding-25 text-30 btnEmbConforme" data-conformite="0" id="non-conforme-button"><i class="fa fa-times fa-lg mr-3"></i>Non Conforme</button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-success btn-lg form-control padding-25 text-30 btnEmbConforme" data-conformite="1" id="conforme-button"><i class="fa fa-check fa-lg mr-3"></i>Conforme</button>
                    </div>
            </form>
        <?php } // FIN étape

    /** ----------------------------------------
     * Etape        : 8
     * Description  : Echange des palettes + poids
     *  ----------------------------------- */
    if ($etape == 8) {

        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }
        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }
        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
                <i class="fa fa-check text-16" style="left:-5px;top:-5px;"></i>
                Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages'</p>
            </h2>
            <h4 class="text-center">Gestion des palettes<i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
            <div class="row justify-content-md-center mt-3">

                <div class="col-4">
                    <div class="alert alert-secondary">
                        <div class="input-group clavier">
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
                            <div class="col-4"><button type="button" class="form-control mb-2 btn btn-dark btn-large" data-valeur="S"><i class="fa fa-eraser"></i></button></div>
                        </div>
                    </div>
                </div> <!-- FIN col gauche pour pavé numérique -->
                <div class="col-4">
                    <div class="row">
                        <div class="col-12 text-18"><i class="fa fa-arrow-right gris-7 flechePoids"></i> Poids palettes :</div>
                        <div class="col-12 input-group">
                            <input type="text" class="form-control text-5em text-center" placeholder="00.00" value="<?php
                                                                                                                    echo $prp->getPalettes_poids() != 0 ? $prp->getPalettes_poids() : ''; ?>" id="poids" maxlength="7" />
                            <div class="input-group-append"><span class="input-group-text text-48"> Kg&nbsp;</span></div>
                            <div class="input-group-append"><button type="button" class="btn btn-warning text-48 btnSupprChar"><i class="fa fa-backspace"></i></button></div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12 text-18">Quantité reçue :</div>
                        <div class="col-12 input-group">
                            <div class="input-group-prepend"><button type="button" class="btn btn-dark text-48 btnMoins pl-5 pr-5"><i class="fa fa-minus"></i></button></div>
                            <input type="text" class="form-control text-4em text-center" placeholder="0" value="<?php
                                                                                                                echo $prp->getPalettes_recues() != 0 ? $prp->getPalettes_recues() : ''; ?>" id="palettes_recues" maxlength="3" />
                            <div class="input-group-append"><button type="button" class="btn btn-dark text-48 btnPlus pl-5 pr-5"><i class="fa fa-plus"></i></button></div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12 text-18">Quantité rendue :</div>
                        <div class="col-12 input-group">
                            <div class="input-group-prepend"><button type="button" class="btn btn-dark text-48 btnMoins pl-5 pr-5"><i class="fa fa-minus"></i></button></div>
                            <input type="text" class="form-control text-4em text-center" placeholder="0" value="<?php
                                                                                                                echo $prp->getPalettes_rendues() != 0 ? $prp->getPalettes_rendues() : ''; ?>" id="palettes_rendues" maxlength="3" />
                            <div class="input-group-append"><button type="button" class="btn btn-dark text-48 btnPlus pl-5 pr-5"><i class="fa fa-plus"></i></button></div>
                        </div>
                    </div>
                </div> <!-- FIN col droite -->


            </div> <!-- FIN conteneur ROW -->
            <div class="row justify-content-md-center mt-3">
                <div class="col-3">
                    <button type="button" class="btn btn-success form-control text-18 padding-20 btnValider"><i class="fa fa-check fa-lg mr-3"></i>Valider</button>
                </div>
            </div>

        <?php } // FIN étape


    /** ----------------------------------------
     * Etape        : 21
     * Description  : Echange des palettes hors PRP (sans poids)
     *  ----------------------------------- */
    if ($etape == 21) {

        $id_transporteur = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_transporteur == 0) {
            exit('Identification du transporteur échouée !');
        }
        $tiersManager = new TiersManager($cnx);
        $transporteur = $tiersManager->getTiers($id_transporteur);
        if (!$transporteur instanceof Tiers) {
            exit('Instanciation du transporteur échouée !');
        }
        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-pallet margin-left-15"></i>
                <i class="fa fa-retweet text-16" style="left:-5px;top:-5px;"></i>
                Palettes et crochets <p class="texte-fin text-22">Echanges libres hors PRP OP</p>
            </h2>
            <h4 class="text-center">Gestion des palettes<i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
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
            <div class="row justify-content-md-center mt-3">
                <div class="col-3">
                    <button type="button" class="btn btn-success form-control text-18 padding-20 btnValider"><i class="fa fa-check fa-lg mr-3"></i>Valider</button>
                </div>
            </div>

        <?php } // FIN étape


    /** ----------------------------------------
     * Etape        : 9
     * Description  : Echange des crochets
     *  ----------------------------------- */
    if ($etape == 9) {

        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }

        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
                <i class="fa fa-check position-relative text-16" style="left:-5px;top:-5px;"></i>
                Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages</p>
            </h2>
            <h4 class="text-center">Gestion des crochets<i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>

            <div class="row justify-content-md-center mt-3">
                <div class="col-4">
                    <div class="row mt-2">
                        <div class="col-12 text-18">Quantité reçue :</div>
                        <div class="col-12 input-group">
                            <div class="input-group-prepend"><button type="button" class="btn btn-dark text-48 btnMoins pl-5 pr-5"><i class="fa fa-minus"></i></button></div>
                            <input type="text" class="form-control text-4em text-center" placeholder="0" value="<?php echo $prp->getCrochets_recus() != 0 ? $prp->getCrochets_recus() : ''; ?>" id="crochets_recus" maxlength="3" />
                            <div class="input-group-append"><button type="button" class="btn btn-dark text-48 btnPlus pl-5 pr-5"><i class="fa fa-plus"></i></button></div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12 text-18">Quantité rendue :</div>
                        <div class="col-12 input-group">
                            <div class="input-group-prepend"><button type="button" class="btn btn-dark text-48 btnMoins pl-5 pr-5"><i class="fa fa-minus"></i></button></div>
                            <input type="text" class="form-control text-4em text-center" placeholder="0" value="<?php echo $prp->getCrochets_rendus() != 0 ? $prp->getCrochets_rendus() : ''; ?>" id="crochets_rendus" maxlength="3" />
                            <div class="input-group-append"><button type="button" class="btn btn-dark text-48 btnPlus pl-5 pr-5"><i class="fa fa-plus"></i></button></div>
                        </div>
                    </div>
                </div> <!-- FIN col droite -->


            </div> <!-- FIN conteneur ROW -->
            <div class="row justify-content-md-center mt-4">
                <div class="col-3">
                    <button type="button" class="btn btn-success form-control text-18 padding-20 btnValider"><i class="fa fa-check fa-lg mr-3"></i>Valider</button>
                </div>
            </div>

        <?php } // FIN étape


    /** ----------------------------------------
     * Etape        : 22
     * Description  : Echange des crochets (libre)
     *  ----------------------------------- */
    if ($etape == 22) {

        $donnees = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        if ($donnees == '') {
            exit('Récupération des données échouée !');
        }

        $donneesArray = explode('|', $donnees);
        $id_transporteur = isset($donneesArray[0]) ? intval($donneesArray[0]) : 0;
        $palettes_recues = isset($donneesArray[1]) ? intval($donneesArray[1]) : 0;
        $palettes_rendues = isset($donneesArray[2]) ? intval($donneesArray[2]) : 0;

        $tiersManager = new TiersManager($cnx);

        $transporteur = $tiersManager->getTiers($id_transporteur);
        if (!$transporteur instanceof Tiers) {
            exit('Instanciation du transporteur échouée !');
        }

        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-pallet margin-left-15"></i>
                <i class="fa fa-retweet position-relative text-16" style="left:-5px;top:-5px;"></i>
                Palettes et crochets <p class="texte-fin text-22">Echanges libres hors PRP OP</p>
            </h2>
            <h4 class="text-center">Gestion des crochets<i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>

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
                    <button type="button" class="btn btn-success form-control text-18 padding-20 btnValider"><i class="fa fa-check fa-lg mr-3"></i>Valider</button>
                </div>
            </div>

        <?php } // FIN étape


    /** ----------------------------------------
     * Etape        : 10
     * Description  : Validation finale
     *  ----------------------------------- */
    if ($etape == 10) {

        $id_prp = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id_prp == 0) {
            exit('Identification du PRP échouée !');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('Instanciation du PRP échouée !');
        }

        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-thermometer-three-quarters margin-left-15"></i>
                <i class="fa fa-check position-relative text-16" style="left:-5px;top:-5px;"></i>
                Surveillance du PRP OP Expédition <p class="texte-fin text-22">Contrôle de la température et des emballages</p>
            </h2>
            <h4 class="text-center">Signature du transporteur<i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
            <div class="row justify-content-md-center mt-1">


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

        <?php } // FIN étape


    /** ----------------------------------------
     * Etape        : 23
     * Description  : Validation finale (Libre)
     *  ----------------------------------- */
    if ($etape == 23) {

        $donnees = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        if ($donnees == '') {
            exit('Récupération des données échouée !');
        }
        $donneesArray = explode('|', $donnees);
        $id_transporteur = isset($donneesArray[0]) ? intval($donneesArray[0]) : 0;
        $palettes_recues = isset($donneesArray[1]) ? intval($donneesArray[1]) : 0;
        $palettes_rendues = isset($donneesArray[2]) ? intval($donneesArray[2]) : 0;
        $crochets_recus = isset($donneesArray[3]) ? intval($donneesArray[3]) : 0;
        $crochets_rendus = isset($donneesArray[4]) ? intval($donneesArray[4]) : 0;

        if ($id_transporteur == 0) {
            exit('Identification du transporteur échouée !');
        }
        $tiersManager = new TiersManager($cnx);
        $transporteur = $tiersManager->getTiers($id_transporteur);
        if (!$transporteur instanceof Tiers) {
            exit('Instanciation du transporteur échouée !');
        }

        ?>

            <h2 class="gris-9 mt-4 text-center">
                <i class="fa fa-pallet margin-left-15"></i>
                <i class="fa fa-retweet position-relative text-16" style="left:-5px;top:-5px;"></i>
                Palettes et crochets <p class="texte-fin text-22">Echanges libres hors PRP OP</p>
            </h2>
            <h4 class="text-center">Signature du transporteur<i class="fa fa-angle-down fa-lg ml-2 vmiddle"></i></h4>
            <div class="row justify-content-md-center mt-1">




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

        <?php } // FIN étape

    exit;
} // FIN mode







/* ------------------------------------------
MODE - Charge le ticket
-------------------------------------------*/
function modeChargeTicket()
{

    global
        $cnx, $utilisateur;

    $prpManager = new PrpOpsManager($cnx);
    $tiersManager = new TiersManager($cnx);

    // Récupération des variables
    $etape = isset($_REQUEST['etape']) ? intval($_REQUEST['etape']) : 0;
    $identifiant = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
    $prp = $prpManager->getPrpOp($identifiant);


        ?>
        <input type="hidden" name="id_prp" value="<?php echo $identifiant; ?>" id="id_prp" />
        <?php

        /** ----------------------------------------
         * TICKET
         * Etape        : 0 Point d'entrée
         *  ----------------------------------- */
        if ($etape == 0) { ?>
            <div class="mb-4"><i class="fa fa-chevron-left mr-1"></i>
                Sélectionnez une action&hellip;
            </div>
            <?php

            // On récupère les PRP saisis du jour
            $liste = $prpManager->getListePrpOps(['ajd' => true]);
            echo empty($liste) ? '' : '<ul class="alert alert-secondary">';
            foreach ($liste as $prpjour) { ?>
                <li>
                    <i class="fa fa-thermometer-three-quarters text-18"></i>
                    <i class="fa fa-check position-relative text-12 mr-2" style="top:-5px;"></i>
                    <?php echo $prpjour->getNom_transporteur(); ?>
                    <ul><?php
                        foreach ($prpjour->getBls() as $bl) {
                            echo '<li class="texte-fin mb-0 text-12">' . $bl->getNom_client() . ' (' . $bl->getNum_bl() . ')</li>';
                        }
                        ?></ul>
                </li>
            <?php }
            echo empty($liste) ? '' : '</ul>';
        }

        if ($utilisateur->isDev()) {
            echo '<div><kbd>Etape ' . $etape . ' | ID ' . $identifiant . '</kbd></div>';
        }

        if ($etape == 1 || $etape >= 20) { ?>
            <button type="button" class="btn btn-warning btn-lg form-control btnRetourEtape0 text-left mb-3" data-id-prp="<?php echo  $prp instanceof PrpOp ? $prp->getId() : 0; ?>"><i class="fa fa-undo fa-lg vmiddle mr-2"></i>Annuler</button>
        <?php } else if ($etape > 1 && $identifiant > 0) { ?>
            <button type="button" class="btn btn-secondary btn-lg form-control btnRetourEtape text-left mb-3" data-id-etape="<?php echo $etape - 1; ?>" data-id-prp="<?php echo $identifiant; ?>"><i class="fa fa-undo fa-lg vmiddle mr-2"></i>Retour</button>

        <?php }

        if ($etape == 21) {
            $id_transporteur = $identifiant;
            if ($id_transporteur == 0) {
                exit('Identification du transporteur échouée !');
            }
            $tiersManager = new TiersManager($cnx);
            $transporteur = $tiersManager->getTiers($id_transporteur);
            if (!$transporteur instanceof Tiers) {
                exit('Instanciation du transporteur échouée !');
            }
            echo '<h5>' . $transporteur->getNom() . '</h5>';
        }
        if ($etape == 22) {
            $donneesArray = explode('|', $identifiant);
            $id_transporteur = isset($donneesArray[0]) ? intval($donneesArray[0]) : 0;
            $palettes_recues = isset($donneesArray[1]) ? intval($donneesArray[1]) : 0;
            $palettes_rendues = isset($donneesArray[2]) ? intval($donneesArray[2]) : 0;

            if ($id_transporteur == 0) {
                exit('Identification du transporteur échouée !');
            }
            $tiersManager = new TiersManager($cnx);
            $transporteur = $tiersManager->getTiers($id_transporteur);
            if (!$transporteur instanceof Tiers) {
                exit('Instanciation du transporteur échouée !');
            }
            echo '<h5>' . $transporteur->getNom() . '</h5>';
            echo '<h6><i class="fa fa-arrow-down mr-1 fa-fw"></i>Palettes reçues : ' . $palettes_recues . '</h6>';
            echo '<h6><i class="fa fa-arrow-up mr-1 fa-fw"></i>Palettes rendues : ' . $palettes_rendues . '</h6>';
        }
        if ($etape == 23) {
            $donneesArray = explode('|', $identifiant);
            $id_transporteur = isset($donneesArray[0]) ? intval($donneesArray[0]) : 0;
            $palettes_recues = isset($donneesArray[1]) ? intval($donneesArray[1]) : 0;
            $palettes_rendues = isset($donneesArray[2]) ? intval($donneesArray[2]) : 0;
            $crochets_recus = isset($donneesArray[3]) ? intval($donneesArray[3]) : 0;
            $crochets_rendus = isset($donneesArray[4]) ? intval($donneesArray[4]) : 0;

            if ($id_transporteur == 0) {
                exit('Identification du transporteur échouée !');
            }
            $tiersManager = new TiersManager($cnx);
            $transporteur = $tiersManager->getTiers($id_transporteur);
            if (!$transporteur instanceof Tiers) {
                exit('Instanciation du transporteur échouée !');
            }
            echo '<h5>' . $transporteur->getNom() . '</h5>';
            echo '<h6><i class="fa fa-arrow-down mr-1 fa-fw"></i>Palettes reçues : ' . $palettes_recues . '</h6>';
            echo '<h6><i class="fa fa-arrow-up mr-1 fa-fw"></i>Palettes rendues : ' . $palettes_rendues . '</h6>';
            echo '<h6><i class="fa fa-arrow-down mr-1 fa-fw"></i>Crochets reçus : ' . $crochets_recus . '</h6>';
            echo '<h6><i class="fa fa-arrow-up mr-1 fa-fw"></i>Crochets rendus : ' . $crochets_rendus . '</h6>';
        }

        if ($etape == 2) { ?>
            <button type="button" class="btn btn-success hid btn-lg form-control btnSelectBlsOk text-left mb-3"><i class="fa fa-check fa-lg vmiddle mr-2"></i>Sélectionner</button>
            <?php }


        if ($etape > 2 && $prp instanceof PrpOp) {

            foreach ($prp->getBls() as $bl) {
                if ($bl instanceof Bl) { ?>
                    <h5><?php echo $bl->getNum_bl(); ?></h5>
                <?php }
                $trans = $tiersManager->getTiers($bl->getId_tiers_transporteur());
                if ($trans instanceof Tiers) { ?>
                    <h6 class="pl-2"><i class="fa fa-chevron-right mr-1 gris-c"></i> <?php echo $trans->getNom(); ?></h6>
                <?php }
                $client = $tiersManager->getTiers($bl->getId_tiers_livraison());
                if ($client instanceof Tiers) { ?>
                    <h6 class="pl-2"><i class="fa fa-chevron-right mr-1 gris-c"></i> <?php echo $client->getNom(); ?></h6>
                <?php }
            } // FIN Boucle BLs




            if (Outils::verifDateSql($prp->getDate())) { ?>
                <h5><i class="fa fa-fw fa-calendar mr-2 gris-9"></i><?php echo Outils::dateSqlToFr($prp->getDate()); ?></h5>
            <?php }
            if ($prp->getCmd_conforme() > -1) {
                $ifa = $prp->getCmd_conforme() == 0 ? 'times' : 'check';
                $css = $prp->getCmd_conforme() == 0 ? 'danger' : 'success';
                $siz =  $prp->getCmd_conforme() == 0 ? 'text-18' : '';
                $txt = $prp->getCmd_conforme() == 0 ? 'Non-conforme' : 'Conforme';
            ?>
                <h5 class="<?php echo $siz; ?>"><i class="fa fa-fw fa-<?php echo $ifa; ?> text-<?php echo $css; ?> mr-2"></i><?php echo $txt; ?> à la commande</h5>
            <?php }
            if ((int)$prp->getT_surface_conforme() > -1) {
                $ifa = $prp->getT_surface_conforme() == 0 ? 'times' : 'check';
                $css = $prp->getT_surface_conforme() == 0 ? 'danger' : 'success';
                $siz = $prp->getT_surface_conforme() == 0 ? 'text-18' : '';
                $txt = $prp->getT_surface_conforme() == 0 ? 'Non-conforme' : 'Conforme';
            ?>
                <h5><i class="fa fa-fw fa-thermometer gris-9 mr-2"></i> Surface <?php echo number_format($prp->getT_surface(), 2, '.', ''); ?>°C</h5>
                <h5 class="<?php echo $siz; ?>"><i class="fa fa-fw fa-<?php echo $ifa; ?> text-<?php echo $css; ?> mr-2"></i>T° surface <?php echo $txt; ?></h5>
            <?php }
            if ((int)$prp->getT_camion_conforme() > -1) {
                $ifa = $prp->getT_camion_conforme() == 0 ? 'times' : 'check';
                $css = $prp->getT_camion_conforme() == 0 ? 'danger' : 'success';
                $siz = $prp->getT_camion_conforme() == 0 ? 'text-18' : '';
                $txt = $prp->getT_camion_conforme() == 0 ? 'Non-conforme' : 'Conforme';
            ?>
                <h5><i class="fa fa-fw fa-thermometer gris-9 mr-2"></i> Camion <?php echo number_format($prp->getT_camion(), 2, '.', ''); ?>°C</h5>
                <h5 class="<?php echo $siz; ?>"><i class="fa fa-fw fa-<?php echo $ifa; ?> text-<?php echo $css; ?> mr-2"></i>T° camion <?php echo $txt; ?></h5>
            <?php }

            if ($prp->getEmballage_conforme() > -1) {
                $ifa = $prp->getEmballage_conforme() == 0 ? 'times' : 'check';
                $css = $prp->getEmballage_conforme() == 0 ? 'danger' : 'success';
                $siz = $prp->getEmballage_conforme() == 0 ? 'text-18' : '';
                $txt = $prp->getEmballage_conforme() == 0 ? 'Non-conforme' : 'Conforme';
            ?>
                <h5 class="<?php echo $siz; ?>"><i class="fa fa-fw fa-<?php echo $ifa; ?> text-<?php echo $css; ?> mr-2"></i>Emballage <?php echo $txt; ?> </h5>
            <?php }

            if ($prp->getPalettes_poids() > 0) { ?>
                <h5><i class="fa fa-fw fa-weight gris-9 mr-2"></i>Palettes : <?php echo number_format($prp->getPalettes_poids(), 3, '.', ''); ?> Kg</h5>
            <?php }
            if ($prp->getPalettes_recues() > 0) { ?>
                <h5><i class="fa fa-fw fa-sign-in-alt gris-9 mr-2"></i>Palettes reçues : <?php echo $prp->getPalettes_recues(); ?></h5>
            <?php }
            if ($prp->getPalettes_rendues() > 0) { ?>
                <h5><i class="fa fa-fw fa-sign-out-alt gris-9 mr-2"></i>Palettes rendues : <?php echo $prp->getPalettes_rendues(); ?></h5>
            <?php }
            if ($prp->getCrochets_recus() > 0) { ?>
                <h5><i class="fa fa-fw fa-sign-in-alt gris-9 mr-2"></i>Crochets reçus : <?php echo $prp->getCrochets_recus(); ?></h5>
            <?php }
            if ($prp->getCrochets_rendus() > 0) { ?>
                <h5><i class="fa fa-fw fa-sign-out-alt gris-9 mr-2"></i>Crochets rendus : <?php echo $prp->getCrochets_rendus(); ?></h5>
    <?php }
        } // FIN test PRP et étape > 0


    } // FIN charge ticket


    // Crée un nouveau PRP
    function modeCreatePrpOp()
    {

        global $prpManager;

        // On purge les PRP abandonnés
        $prpManager->delPrpAbandonnes();

        $prp = new PrpOp([]);
        $prp->setId_user(0); // Zéro pour enregistrer une base, l'id user sert a signer quand c'est terminé
        $id_prp = $prpManager->savePrpOp($prp);
        echo intval($id_prp);
        exit;
    }


    function modeSavePvisuApres()
    {
        global $cnx, $utilisateur;


        $prpManager = new PrpOpsManager($cnx);

        $id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
        if ($id_prp == 0) {
            exit('-1');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('-2');
        }

        $save = false;


        $conf_emb = isset($_REQUEST['conf_emb']) ? intval($_REQUEST['conf_emb']) : -1;
        if ($conf_emb > -1) {
            $prp->setEmballage_conforme($conf_emb);
            $save = true;
        }


        $points = isset($_REQUEST['point']) && is_array($_REQUEST['point']) ? $_REQUEST['point'] : [];
        $lotBlValues = isset($_REQUEST['id_lot']) && $_REQUEST['id_lot'] ? $_REQUEST['id_lot'] : [];

        $pvisuApresManager = new PvisuApresManager($cnx);
        $pointsControleManager = new PointsControleManager($cnx);
        $lotManager = new LotManager($cnx);
        
        if ($lotBlValues) {
            foreach ($lotBlValues as $id_lot) {
                $lot = $lotManager->getLot($id_lot);
                if (!$lot instanceof Lot) {
                    exit('-4');
                }

                //Création d'un nouveau pvisuAfter
                $pvisu = new PvisuApres([]);
                $pvisu->setCommentaires('');
                $pvisu->setId_user($utilisateur->getId());
                $pvisu->setId_lot($id_lot);
                $pvisu->setDate(date('Y-m-d'));

                //Enregistrement du nouveau psvisuApres
                $id_pvisu = $pvisuApresManager->savePvisuApres($pvisu);
                if ((int)$id_pvisu == 0) {
                    exit('-5');
                }
                $pvisu->setId($id_pvisu);

                if (empty($points)) {
                    exit('-2');
                }

                if (!$pvisuApresManager->savePvisuApresPoints($pvisu, $points)) {
                    exit(-4);
                }
                $pvisu->setId_user($utilisateur->getId());
            }
        } else {
            echo "Une erreur survenue ou pourriez-vous verifier s\'il avait de numero de lot...";
        }


        // Enregistrement si modifs
        if ($save) {
            $prpManager->savePrpOp($prp);
        }


        echo $id_prp;
        exit;
    }



    // Enregistre les modifs d'un PRP
    function modeSavePrp()
    {

        global $cnx, $utilisateur;

        $prpManager = new PrpOpsManager($cnx);

        $id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
        if ($id_prp == 0) {
            exit('-1');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('-2');
        }

        $save = false;

        $id_transporteur = isset($_REQUEST['id_trans']) ? intval($_REQUEST['id_trans']) : 0;
        if ($id_transporteur > 0) {
            $prp->setId_transporteur($id_transporteur);
            $save = true;
        }

        $ids_bls = isset($_REQUEST['ids_bls']) ? $_REQUEST['ids_bls'] : '';

        if ($ids_bls != '') {
            $ids_bls = explode(',', $ids_bls);
            $prpManager->savePrpOpBls($prp, $ids_bls);
        }

        $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        if ($date != '' && Outils::verifDateSql($date)) {
            $prp->setDate($date);
            $save = true;
        }

        $conf_cmd = isset($_REQUEST['conf_cmd']) ? intval($_REQUEST['conf_cmd']) : -1;
        if ($conf_cmd > -1) {
            $prp->setCmd_conforme($conf_cmd);
            $save = true;
        }

        $t_surface = isset($_REQUEST['t_surface']) ? floatval($_REQUEST['t_surface']) : '';
        if ($t_surface != '') {
            $prp->setT_surface($t_surface);
            $save = true;
        }

        $conf_t_surface = isset($_REQUEST['conf_t_surface']) ? intval($_REQUEST['conf_t_surface']) : -1;
        if ($conf_t_surface > -1) {
            $prp->setT_surface_conforme($conf_t_surface);
            $save = true;
        }

        $t_camion = isset($_REQUEST['t_camion']) ? floatval($_REQUEST['t_camion']) : '';
        if ($t_camion != '') {
            $prp->setT_camion($t_camion);
            $save = true;
        }

        $conf_t_camion = isset($_REQUEST['conf_t_camion']) ? intval($_REQUEST['conf_t_camion']) : -1;
        if ($conf_t_camion > -1) {
            $prp->setT_camion_conforme($conf_t_camion);
            $save = true;
        }

        $conf_emb = isset($_REQUEST['conf_emb']) ? intval($_REQUEST['conf_emb']) : -1;
        if ($conf_emb > -1) {
            $prp->setEmballage_conforme($conf_emb);
            $save = true;
        }

        if (isset($_REQUEST['fin'])) {
            $prp->setId_user($utilisateur->getId());
            $prp->setDate_add(date('Y-m-d H:i:s'));
            $save = true;
            saveSoldeCrochetsPalettesTransporteur($prp);
        }

        $poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : -1;
        if ($poids >= 0) {
            $prp->setPalettes_poids($poids);
            $save = true;
        }

        $palettes_recues = isset($_REQUEST['palettes_recues']) ? floatval($_REQUEST['palettes_recues']) : -1;
        if ($palettes_recues >= 0) {
            $prp->setPalettes_recues($palettes_recues);
            $save = true;
        }

        $palettes_rendues = isset($_REQUEST['palettes_rendues']) ? floatval($_REQUEST['palettes_rendues']) : -1;
        if ($palettes_rendues >= 0) {
            $prp->setPalettes_rendues($palettes_rendues);
            $save = true;
        }

        $crochets_recus = isset($_REQUEST['crochets_recus']) ? floatval($_REQUEST['crochets_recus']) : -1;
        if ($crochets_recus >= 0) {
            $prp->setCrochets_recus($crochets_recus);
            $save = true;
        }

        $crochets_rendus = isset($_REQUEST['crochets_rendus']) ? floatval($_REQUEST['crochets_rendus']) : -1;
        if ($crochets_rendus >= 0) {
            $prp->setCrochets_rendus($crochets_rendus);
            $save = true;
        }

        if ($save) {
            $prpManager->savePrpOp($prp);
        }

        echo $id_prp;
        exit;
    } // FIN mode


    // Supprime un PRP (annulé)
    function modeSupprPrp()
    {

        global $prpManager;

        $id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
        if ($id_prp == 0) {
            exit('-1');
        }

        $prp = $prpManager->getPrpOp($id_prp);
        if (!$prp instanceof PrpOp) {
            exit('-2');
        }

        echo $prpManager->supprPrpOp($prp) ? '1' : '0';
        exit;
    } // FIN mode

    // Enregistre le solde de crochets/palettes du transporteur à la validation
    function saveSoldeCrochetsPalettesTransporteur(PrpOp $prp)
    {

        global $cnx;
        $tiersManager = new TiersManager($cnx);
        $transporteur = $tiersManager->getTiers($prp->getId_transporteur());
        if (!$transporteur instanceof Tiers) {
            return false;
        }

        $solde_palettes = $transporteur->getSolde_palettes();
        $solde_palettes += $prp->getPalettes_rendues();
        $solde_palettes -= $prp->getPalettes_recues();

        $solde_crochets = $transporteur->getSolde_crochets();
        $solde_crochets += $prp->getCrochets_rendus();
        $solde_crochets -= $prp->getCrochets_recus();

        $transporteur->setSolde_palettes($solde_palettes);
        $transporteur->setSolde_crochets($solde_crochets);
        return $tiersManager->saveTiers($transporteur);
    } // FIN fonction déportée

    // Enregistre une signature en SVG
    function modeSaveSignature()
    {

        global $cnx;

        $id_prp = isset($_REQUEST['id_prp']) ? intval($_REQUEST['id_prp']) : 0;
        $image = isset($_REQUEST['image']) ? $_REQUEST['image'] : '';
        if ($image == '') {
            exit('-1');
        }
        if ($id_prp == 0) {
            exit('-2');
        }

        $base64_str = str_replace('data:image/png;base64,', '', $image);
        $base64_str = str_replace(' ', '+', $base64_str);
        $decoded = base64_decode($base64_str);

        $png_url = __CBO_UPLOADS_PATH__ . 'signatures/exp/' . $id_prp . ".png";

        $resSign = file_put_contents($png_url, $decoded);

        if (!$resSign) {
            $log = new Log([]);
            $log->setLog_texte('Echec enregistrement image signature PRP #' . $id_prp);
            $log->setLog_type('danger');
            $logsManager = new LogManager($cnx);
            $logsManager->saveLog($log);
        }
        exit;
    } // FIN mode

    // Crée un PRP directement depuis un BL (Bon de transfert redirigé vers PRP)
    function modeCreatePrpFromBt()
    {

        global $cnx, $prpManager;

        $id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
        if ($id_bl == 0) {
            exit('-1');
        }

        // On assigne le transporteur paramétré pour le client (dépot) configuré pour être celui des bons de transfert
        $configManager = new ConfigManager($cnx);
        $id_clt_stk_conf = $configManager->getConfig('bt_clt');
        $id_clt_stk = $id_clt_stk_conf instanceof Config ? intval($id_clt_stk_conf->getValeur()) : 0;

        if ($id_clt_stk == 0) {
            exit('-2');
        } // Dépot (clt) des bons de transfert non identifié (ne devrait jamais arriver ici)

        $tiersManager = new TiersManager($cnx);
        $clt_bt = $tiersManager->getTiers($id_clt_stk);
        if (!$clt_bt instanceof Tiers) {
            exit((-3));
        } // Dépot (clt) des bons de transfert non instancié (ne devrait jamais arriver ici)

        $id_transporteur = intval($clt_bt->getId_transporteur()); // On ne bloque pas si il est à zéro, car NTUI mais peu d'incidence ici

        $prp = new PrpOp([]);
        $prp->setId_user(0); // Zéro pour enregistrer une base, l'id user sert a signer quand c'est terminé
        $prp->setId_transporteur($id_transporteur);
        $id_prp = $prpManager->savePrpOp($prp);

        $prp->setId($id_prp);
        if (!$prpManager->savePrpOpBls($prp, [$id_bl])) {
            exit('-4');
        } // Echec enregistrement du BL pour ce PRP

        $logsManager = new LogManager($cnx);
        $log = new Log([]);
        $log->setLog_type('info');
        $log->setLog_texte("Création du PRP-OP #" . $id_prp . " suite création bon de transfert #" . $id_bl);
        $logsManager->saveLog($log);

        echo intval($id_prp);
    } // FIN mode


    // Enregsitre les données d'échanges hors PRP (libre)
    function modeSavePalettesCrochetsLibres()
    {
        global $cnx, $utilisateur;

        $donnees = isset($_REQUEST['donnees']) ? $_REQUEST['donnees'] : '';
        if ($donnees == '') {
            exit('Récupération des données échouée !');
        }
        $donneesArray = explode('|', $donnees);
        $id_transporteur = isset($donneesArray[0]) ? intval($donneesArray[0]) : 0;
        $palettes_recues = isset($donneesArray[1]) ? intval($donneesArray[1]) : 0;
        $palettes_rendues = isset($donneesArray[2]) ? intval($donneesArray[2]) : 0;
        $crochets_recus = isset($donneesArray[3]) ? intval($donneesArray[3]) : 0;
        $crochets_rendus = isset($donneesArray[4]) ? intval($donneesArray[4]) : 0;
        $image = isset($_REQUEST['image']) ? $_REQUEST['image'] : '';

        $logManager = new LogManager($cnx);

        // On enregistre les palettes dans la table  pe_retour_palettes_rcp
        if ($palettes_recues > 0 || $palettes_rendues > 0) {
            $retourPalettes = new RetourPaletteRcp();
            $retourPalettes->setDate_retour(date('Y-m-d'));
            $retourPalettes->setDate_add(date('Y-m-d H:i:s'));
            $retourPalettes->setId_transporteur($id_transporteur);
            $retourPalettes->setPalettes_recues($palettes_recues);
            $retourPalettes->setPalettes_rendues($palettes_rendues);
            $retourPalettes->setId_user($utilisateur->getId());
            $retourPalettesManager = new RetourPaletteRcpsManager($cnx);
            $id_rcp = $retourPalettesManager->saveRetourPaletteRcp($retourPalettes);
            if (intval($id_rcp) > 0) {

                // On associe l'image à l'entrée dans retour_palettes si il y a des palettes et une signature, sinon tant pis pour la signature
                if ($image != '') {
                    $base64_str = str_replace('data:image/png;base64,', '', $image);
                    $base64_str = str_replace(' ', '+', $base64_str);
                    $decoded = base64_decode($base64_str);
                    $png_url = __CBO_UPLOADS_PATH__ . 'signatures/rcp/' . intval($id_rcp) . ".png";
                    file_put_contents($png_url, $decoded);
                }

                $tiersManager = new TiersManager($cnx);
                $transporteur = $tiersManager->getTiers($id_transporteur);
                if (!$transporteur instanceof Tiers) {
                    exit('Erreur instanciation transporteur ' . $id_transporteur);
                }

                $solde_palettes = $transporteur->getSolde_palettes();
                $solde_palettes += $palettes_rendues;
                $solde_palettes -= $palettes_recues;

                $transporteur->setSolde_palettes($solde_palettes);
                if (!$tiersManager->saveTiers($transporteur)) {
                    exit('Erreur save solde palettes transporteur');
                }

                $log = new Log();
                $log->setLog_type('info');
                $log->setLog_texte('Enregistrement de retour palettes hors PRP (libre) depuis expédition');
                $logManager->saveLog($log);
            } else {
                exit('Erreur enregistrement palettes RetourPaletteRcp');
            }
        } // FIN palettes

        // On enregistre les crochets dans la table  pe_lot_reception avec un id_lot à 0
        if ($crochets_recus > 0 || $crochets_rendus > 0) {

            $lotReceptionManager = new LotReceptionManager($cnx);
            $lotReception = new LotReception([]);
            $lotReception->setId_lot(0);
            $lotReception->setObservations("Retour crochets manuels");
            $lotReception->setCrochets_recus($crochets_recus);
            $lotReception->setCrochets_rendus($crochets_rendus);
            $lotReception->setId_transporteur($id_transporteur);
            $lotReception->setId_user($utilisateur->getId());
            $lotReception->setDate_confirmation(date('Y-m-d H:i:s'));
            if ($lotReceptionManager->saveLotReception($lotReception)) {

                $tiersManager = new TiersManager($cnx);
                $transporteur = $tiersManager->getTiers($id_transporteur);
                if (!$transporteur instanceof Tiers) {
                    exit('Erreur instanciation transporteur ' . $id_transporteur);
                }

                $solde_crochets = $transporteur->getSolde_crochets();
                $solde_crochets += $crochets_rendus;
                $solde_crochets -= $crochets_recus;

                $transporteur->setSolde_crochets($solde_crochets);
                if (!$tiersManager->saveTiers($transporteur)) {
                    exit('Erreur save solde crochets transporteur');
                }

                $log = new Log();
                $log->setLog_type('info');
                $log->setLog_texte('Enregistrement de retour crochets hors PRP (libre) depuis expédition');
                $logManager->saveLog($log);
            } else {
                exit('Erreur enregistrement crochets LotReception');
            }
        } // FIN crochets

        echo '1';
        exit;
    } // FIN mode