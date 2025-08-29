<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax LOTS
------------------------------------------------------*/
ini_set('display_errors', 0);
error_reporting(0); // Évite les problèmes de génération de PDF suite à des Warning
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

/* ------------------------------------
MODE - Génration d'un numéro de lot
------------------------------------*/
function modeGenereNumLot()
{

    $regexDateFr =  '#^(([1-2]\d|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9]\d{2})$#';

    $date       = isset($_REQUEST['date']) && preg_match($regexDateFr, $_REQUEST['date']) ? Outils::dateFrToSql($_REQUEST['date']) : '';
    $abattoir   = isset($_REQUEST['abattoir'])  ? trim(strtoupper($_REQUEST['abattoir'])) : '';
    $origine    = isset($_REQUEST['origine'])   ? trim(strtoupper($_REQUEST['origine']))  : '';

    if ($date == '' || $abattoir == '' || $origine == '') {
        exit;
    }

    /*
     * Année sur 2 chiffres (19) (auto)
     * Code abatoir sur 2 chifres (20)
     * Semaine d'abattage sur 2 chiffres (35)
     * Jour de la semaine (A, B, C...)
     * Origine sur 2 ou 3 lettres (ES, FRA...)
     */

    // Année de l'abattage
    $dateAbattageDt = new DateTime($date);
    $numlot = $dateAbattageDt->format('y');



    // Code abatoir
    $numlot .= $abattoir;

    // Semaine d'abattage
    $datetime = new DateTime($date);
    $numlot .= $datetime->format('W');

    // Jour de la semaine (w)
    $jours = [0 => 'G', 1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F'];
    $numlot .= $jours[$datetime->format('w')];

    // Origine
    $numlot .= $origine;

    echo $numlot;

    exit;
} // FIN mode

/* ------------------------------------
MODE - Enregistre un nouveau lot
------------------------------------*/
function modeAddLot()
{

    global
        $cnx,
        $lotsManager,
        $logsManager;

    $especesManager = new ProduitEspecesManager($cnx);

    $regexDateFr     =  '#^(([1-2]\d|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9]\d{2})$#';

    $numlot          = isset($_REQUEST['numlot'])        ? trim(strtoupper($_REQUEST['numlot'])) : '';
    $id_abattoir     = isset($_REQUEST['id_abattoir'])   ? intval($_REQUEST['id_abattoir'])      : 0;
    $id_origine      = isset($_REQUEST['id_origine'])    ? intval($_REQUEST['id_origine'])       : 0;
    $id_fournisseur  = isset($_REQUEST['id_fournisseur']) ? intval($_REQUEST['id_fournisseur'])  : 0;
    $date_abattage   = isset($_REQUEST['date_abattage']) && preg_match($regexDateFr, $_REQUEST['date_abattage']) ? Outils::dateFrToSql($_REQUEST['date_abattage']) : '';

    $reception       = isset($_REQUEST['date_reception']) && preg_match($regexDateFr, $_REQUEST['date_reception']) ? Outils::dateFrToSql($_REQUEST['date_reception']) : '';
    $poids_abattoir     = isset($_REQUEST['poids_abattoir'])   ? floatval(str_replace(',', '.', $_REQUEST['poids_abattoir']))  : 0.0;
    $poids_reception = isset($_REQUEST['poids_reception'])  ? floatval(str_replace(',', '.', $_REQUEST['poids_reception'])) : 0.0;
    $composition     = isset($_REQUEST['composition'])    ?  $_REQUEST['composition'] : 0;


    // Fonction de retour erreur
    function erreur($numlot)
    {
        header('Location: ../../admin-lots-add.php?e=' . $numlot);
        return false;
    }
    if ($numlot == '') {
        erreur('');
    }


    // Si c'est un lot Abats et qu'on a pas le A, on le rajoute
    if (substr($composition, 0, 1) == 'A' && strtoupper(substr($numlot, -1)) != 'A') {
        $numlot = $numlot . 'A';

        // Si c'est pas un lot Abats et qu'on a un A, on l'enlève.
    } else if (substr($composition, 0, 1) != 'A' && strtoupper(substr($numlot, -1)) == 'A') {
        $numlot = substr($numlot, 0, -1);
    } // FIN contrôle du numéro de lot

    $lot = new Lot([]);
    $lot->setNumlot($numlot);
    $lot->setDate_add(date('Y-m-d H:i:s'));
    $lot->setVisible(1);
    $lot->setId_fournisseur($id_fournisseur);

    if ($composition != 'va') {
        $compo = substr($composition, 0, 1) == 'A' ? 2 : 1;
        $lot->setId_espece(str_replace('A', '', $composition));
        $lot->setComposition($compo);
    } else {
        $lot->setId_espece($especesManager->getIdEspeceViande());
        $lot->setComposition(1);
    }

    // Champs optionnels
    if ($id_origine > 0) {
        $lot->setId_origine($id_origine);
    }
    if ($id_abattoir > 0) {
        $lot->setId_abattoir($id_abattoir);
    }
    if ($date_abattage != '') {
        $lot->setDate_abattage($date_abattage);
    }

    if ($reception != '') {
        $lot->setDate_reception($reception);
    }
    if ($poids_abattoir > 0.0) {
        $lot->setPoids_abattoir($poids_abattoir);
    }
    if ($poids_reception > 0.0) {
        $lot->setPoids_reception($poids_reception);
    }

    $id_lot =  $lotsManager->saveLot($lot);
    // Si lot créé, on associe éventuellement la vue et on Log
    if (intval($id_lot) > 0) {

        // Si on dispose des infos nécessaires, on place la vue sur Reception
        if ($id_origine > 0 && $id_abattoir > 0 && $date_abattage != '') {

            $vuesManager = new VueManager($cnx);
            $vueReception = $vuesManager->getVueByCode('rcp');
            $lotVue = new LotVue([]);
            $lotVue->setId_lot($id_lot);
            $lotVue->setId_vue($vueReception->getId());
            $lotVue->setDate_entree(date('Y-m-d H:i:s'));
            $lotVuesManager = new LotVueManager($cnx);
            $lotVuesManager->saveLotVue($lotVue);
        }

        $log = new Log([]);
        $log->setLog_type('success');
        $log->setLog_texte('Création du lot ' . $numlot);
        $logsManager->saveLog($log);

        // Si erreur, on reviens...
    } else {
        erreur($numlot);
        exit;
    } // FIN test création lot

    // Si on a un double lot, on crée le second en "A" pour abats
    if ($composition == 'va') {
        $lot_abats = clone $lot;
        $lot_abats->setId(''); // Pour éviter un update de la méthode Save
        $lot_abats->setNumlot($lot->getNumlot() . 'A');
        $lot_abats->setComposition(2);
        $lot_abats->setId_espece($especesManager->getIdEspeceAbats());

        // On n'intègre pas le poids abattoir pour le lot Abats (demande client sur site du 25/06/2019)
        $lot_abats->setPoids_abattoir(0);

        // Si lot créé, on Log

        $id_lot_abats = $lotsManager->saveLot($lot_abats);
        if (intval($id_lot_abats) > 0) {

            // On associe aussi la vue pour le second lot créé
            if (!isset($vuesManager)) {
                $vuesManager = new VueManager($cnx);
            }
            if (!isset($vueReception)) {
                $vueReception = $vuesManager->getVueByCode('rcp');
            }
            $lotVueA = new LotVue([]);
            $lotVueA->setId_lot($id_lot_abats);
            $lotVueA->setId_vue($vueReception->getId());
            $lotVueA->setDate_entree(date('Y-m-d H:i:s'));
            if (!isset($lotVuesManager)) {
                $lotVuesManager = new LotVueManager($cnx);
            }
            $lotVuesManager->saveLotVue($lotVueA);


            $log = new Log([]);
            $log->setLog_type('success');
            $log->setLog_texte('Création du lot ' . $lot_abats->getNumlot());
            $logsManager->saveLog($log);

            // Si erreur, on reviens...
        } else {
            erreur($lot_abats->getNumlot());
            exit;
        } // FIN test création lot
    }

    // Récupération de l'abattoir pour Bizerba
    $abattoirManager = new AbattoirManager($cnx);
    $abattoir = $abattoirManager->getAbattoir($id_abattoir);

    // Si on a pas les données nécéssaires, on envoie pas vers Bizerba...
    if (!$abattoir instanceof Abattoir || $date_abattage == '' || trim($abattoir->getNumagr()) == '') {
        header('Location: ../../admin-lots.php?erbz');
        exit;
    }

    $bzok = envoiLotBizerba($lot, $abattoir);

    // Si on a un lot double, on envoie aussi le lot Abats
    if ($bzok && isset($lot_abats)) {
        $bzok = envoiLotBizerba($lot_abats, $abattoir, true);
    }


    $paramBz = !$bzok ? '?erbz' : '';
    header('Location: ../../admin-lots.php' . $paramBz);
    exit;
} // FIN mode

/* --------------------------------------
MODE - Retourne la liste des lots (aJax)
---------------------------------------*/
function modeShowListeLots()
{

    global
        $mode,
        $lotsManager,
        $utilisateur,
        $cnx;

    if (!isset($utilisateur) || !$utilisateur) {
        exit('Session expirée ! Reconnectez-vous pour continuer...');
    }

    $statut = isset($_REQUEST['statut']) ? intval($_REQUEST['statut']) : 1; // 1 = En cours | 0 = Terminé
    $params['statut'] = $statut;

    $recherche = isset($_REQUEST['recherche']) ? trim($_REQUEST['recherche']) : '';

    // Préparation pagination (Ajax)
    $nbResultPpage      = 20;
    $page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $filtresPagination  = '?mode=' . $mode;
    $filtresPagination .= '&statut=' . $statut;
    $start              = ($page - 1) * $nbResultPpage;

    $params['start']             = $start;
    $params['nb_result_page']     = $nbResultPpage;
    //$params['order'] 			= $statut == 1 ? 'date_maj' : 'date_out';
    $params['order']             = 'id';
    $params['recherche']             = $recherche;

    $listeLots = $lotsManager->getListeLots($params);

    $txtStatut = $statut == 1 ? ' en cours' : ' terminé';

    // Si aucun lot
    if (empty($listeLots)) { ?>

        <div class="alert alert-secondary text-center">
            <i class="far fa-clock mb-2 mt-2 fa-5x"></i>
            <p class="text-24 mb-0">Aucun lot <?php echo $txtStatut ?>&hellip;</p>
            <p class="small texte-fin">Cette page est actualisée chaque minute.</p>
        </div>

    <?php

        // Des lots ont été trouvés...
    } else {

        $incidentsManager = new IncidentsManager($cnx);

        // Liste non vide, construction de la pagination...
        $nbResults  = $lotsManager->getNb_results();
        $pagination = new Pagination($page);

        $pagination->setUrl($filtresPagination);
        $pagination->setNb_results($nbResults);
        $pagination->setAjax_function(true);
        $pagination->setNb_results_page($nbResultPpage);

    ?>
        <div class="alert alert-danger d-lg-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i>
            <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-lg-table">
            <thead>
                <tr>
                    <?php
                    // On affiche l'ID que si on est développeur
                    if ($utilisateur->isDev()) { ?><th class="w-mini-admin-cell d-none d-xl-table-cell">ID</th><?php } ?>

                    <th>Numéro de lot</th>
                    <!-- <th class="d-none d-xl-table-cell">Composition</th>-->
                    <th class="d-none d-xl-table-cell">Espèce</th>
                    <?php if ($statut == 0) { ?>
                        <th>Sorti le</th>
                    <?php } ?>
                    <th>Fournisseur</th>
                    <th class="text-right">Poids (Abattoir)</th>
                    <th class="text-right">Poids (Réception)</th>
                    <th class="text-center">Incidents</th>

                    <?php if ($statut == 1) { ?>
                        <th class="w-court-admin-cell text-center">Vues actuelles</th>
                        <th class="t-actions w-mini-admin-cell text-center">Test traçabilité</th>
                        <th class="t-actions w-mini-admin-cell text-center">Visible</th>
                        <th class="t-actions w-mini-admin-cell text-center">Modifier</th>
                    <?php } ?>
                    <th class="t-actions w-mini-admin-cell text-center">Documents</th>
                    <th class="t-actions w-mini-admin-cell text-center">Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $na = '<i class="far fa-question-circle text-danger fa-lg"></i>';
                $btnPoidsReception = '<button type="button" class="btn btn-sm btn-secondary btnPoidsReceptionLot margin-right-50"><i class="fa fa-weight"></i></button>';

                foreach ($listeLots as $lot) {

                ?>
                    <tr data-id-lot="<?php echo $lot->getId(); ?>">
                        <?php
                        // On affiche l'ID que si on est développeur
                        if ($utilisateur->isDev()) { ?>
                            <td class="w-mini-admin-cell d-none d-xl-table-cell"><span class="badge badge-pill badge-warning"><?php echo $lot->getId(); ?></span></td>
                        <?php } ?>
                        <td class="text-22"><?php echo $lot->getNumlot(); ?></td>
                        <!-- <td class="d-none d-xl-table-cell"><?php /*echo $lot->getComposition_verbose('—');*/ ?></td>-->
                        <td class="d-none d-xl-table-cell"><?php echo $lot->getNom_espece('—'); ?></td>
                        <?php if ($statut == 0) { ?>
                            <td><?php echo $lot->getDate_out() !== '' ? ucfirst(Outils::getDate_verbose($lot->getDate_out())) : $na; ?></td>
                        <?php } ?>
                        <td><?php echo $lot->getNom_fournisseur($na); ?></td>

                        <td class="text-right"><?php

                                                if ($lot->getComposition() == 2) {
                                                    echo '&mdash;';
                                                } else {
                                                    echo $lot->getPoids_abattoir() != '' ? '<code class="text-dark text-20">' . number_format($lot->getPoids_abattoir(), 3, '.', '') . '</code> Kg' : $na;
                                                }
                                                ?></td>

                        <td class="text-right"><?php echo $lot->getPoids_reception() != '' ? '<code class="text-dark text-20">' . number_format($lot->getPoids_reception(), 3, '.', '') . '</code> Kg' : $btnPoidsReception; ?></td>

                        <td class="text-center">
                            <?php
                            $params = ['id_lot' => $lot->getId()];
                            $incidents = $incidentsManager->getIncidentsListe($params);
                            if (empty($incidents)) { ?>
                                <span class="gris-9">&mdash;</span>
                            <?php }

                            foreach ($incidents as $incident) { ?>

                                <span class="fa-stack ico-incident pointeur text-12"
                                    data-id-incident="<?php echo $incident->getId() ?>"
                                    data-verbose="<?php echo $incident->getNom_type_incident(); ?>"
                                    data-date="Le <?php echo Outils::getDate_verbose($incident->getDate(), false, ' à '); ?>"
                                    data-user="<?php echo $incident->getNom_user(); ?>">
                                    <i class="fas fa-bookmark fa-stack-2x text-danger"></i>
                                    <i class="fas fa-exclamation-triangle fa-stack-1x fa-inverse margin-top--2"></i>
                                </span>

                            <?php }

                            // affichage des incidents s'il y en
                            ?>

                        </td>


                        <?php if ($statut == 1) { ?>

                            <td class="w-court-admin-cell"><?php
                                                            if (is_array($lot->getVues()) && !empty($lot->getVues())) {

                                                                $firstVue = true;
                                                                foreach ($lot->getVues() as $lotvue) {

                                                                    if ($lotvue->getVue() instanceof Vue) { ?>
                                            <span class="badge badge-info form-control text-12 texte-fin <?php echo !$firstVue ? 'margin-top-5' : ''; ?>"><?php echo $lotvue->getVue()->getNom(); ?></span>
                                        <?php } else { ?>
                                            echo '&mdash;';
                                    <?php } // FN test instanciation objet Vue

                                                                    $firstVue = false;
                                                                } // FIN boucle vues

                                                            } else { ?>
                                    <span class="badge badge-danger form-control text-14">Terminé</span>
                                <?php } // FIN test vues
                                ?>
                            </td>
                            <td class="t-actions w-court-admin-cell">
                                <input type="checkbox" class="togglemaster switch-tracabilite-lot text-12"
                                    data-toggle="toggle"
                                    data-on="Fait"
                                    data-off="A faire"
                                    data-onstyle="success"
                                    data-offstyle="secondary"
                                    data-size="small"
                                    <?php
                                    // Statut coché
                                    echo $lot->getTest_tracabilite() != null ? 'checked' : ''; ?> />
                            </td>
                            <td class="t-actions w-mini-admin-cell">
                                <input type="checkbox" class="togglemaster switch-visibilite-lot"
                                    data-toggle="toggle"
                                    data-on="Oui"
                                    data-off="Non"
                                    data-onstyle="success"
                                    data-offstyle="danger"
                                    data-size="small"
                                    <?php
                                    // Statut coché
                                    echo $lot->isVisible()  ? 'checked' : ''; ?> />
                            </td>
                            <td class="t-actions w-mini-admin-cell"><button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#modalLotEdit" data-lot-id="<?php
                                                                                                                                                                                    echo $lot->getId(); ?>"><i class="fa fa-edit"></i> </button>
                            </td>
                        <?php } ?>
                        <td class="t-actions w-mini-admin-cell"><button type="button" class="btn  btn-secondary" data-toggle="modal" data-target="#modalLotDocs" data-lot-id="<?php
                                                                                                                                                                                echo $lot->getId(); ?>"><i class="fas fa-copy"></i> </button>
                        </td>
                        <td class="t-actions w-mini-admin-cell"><button type="button" class="btn  btn-secondary" data-toggle="modal" data-target="#modalLotInfo" data-lot-id="<?php
                                                                                                                                                                                echo $lot->getId(); ?>"><i class="fa fa-ellipsis-h"></i> </button>
                        </td>
                    </tr>
                <?php } // FIN boucle lots
                ?>
            </tbody>
        </table>
        <?php

        // Pagination (aJax)
        if (isset($pagination)) {
            // Pagination bas de page, verbose...
            $pagination->setVerbose_pagination(1);
            $pagination->setVerbose_position('right');
            $pagination->setNature_resultats('lot');
            $pagination->setNb_apres(2);
            $pagination->setNb_avant(2);

            echo ($pagination->getPaginationHtml());
        } // FIN test pagination

        ?>
        <?php if ($statut == 1) { ?>
            <div class="clearfix"></div>
            <div class="alert alert-secondary mt-2 opacity-06 text-14 d-none d-xl-block">
                <i class="fa fa-info-circle fa-lg mr-2"></i>Afin d'être disponible sur la vue <em>Réception</em>, un nouveau lot doit être associé à une origine, un abattoir et une date d'abattage. Les informations de base des lots comprenant une date de réception ne sont plus modifiables.
            </div>
    <?php } // FIN info lot en cours

    } // FIN test résultats trouvés

    exit;
} // FIN mode

/* --------------------------------------
MODE - Modale détails info du lot
--------------------------------------*/
function modeModalLotInfo()
{

    global $cnx, $lotsManager, $utilisateur;

    $lotsManager->updateComposBlArchives();


    $produitsManager = new ProduitManager($cnx);
    $facturesManager = new FacturesManager($cnx);
    $produitsManager->cleanBlLignesSupprimees();

    $id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id_lot == 0) {
        echo '-1';
        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        echo '-1';
        exit;
    }

    $lotsManager->updatePoidsProduitsFromCompos($id_lot);

    echo '<i class="fa fa-box"></i><span class="gris-7 ">Lot</span> ' . $lot->getNumlot();

    echo '^'; // Séparateur Title / Body

    // Objet Reception du lot ?
    $reception = $lot->getReception() instanceof LotReception;

    // Pour les commentaires (on rajouter les ids dans la boucle des traitements s'il y en a)
    $id_froids = [];
    ?>

    <!-- NAVIGATION ONGLETS -->
    <ul class="nav nav-tabs margin-top--10" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" role="tab" href="#general" aria-selected="true"><i class="fa fa-sm fa-info-circle gris-b mr-2"></i>Général</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#rcp"><i class="fa fa-sm fa-truck fa-flip-horizontal gris-b mr-2"></i>Réception</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#pdts"><i class="fa fa-sm fa-barcode gris-b mr-2"></i>Produits</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#cglsrg"><i class="fa fa-sm fa-snowflake gris-b mr-2"></i>Traitements</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#stk"><i class="fa fa-sm fa-layer-group gris-b mr-2"></i>Stock</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#lom"><i class="fa fa-sm fa-clipboard-check gris-b mr-2"></i>Contrôles</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#emb"><i class="fa fa-sm fa-boxes gris-b mr-2"></i>Emballages</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#net"><i class="fa fa-sm fa-shower gris-b mr-2"></i>Nettoyage</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" role="tab" href="#com"><i class="fa fa-sm fa-comment-dots gris-b mr-2"></i>Commentaires</a></li>
    </ul>
    <!-- FIN NAVIGATION ONGLETS -->

    <!-- CONTENEUR ONGLETS -->
    <div class="tab-content">

        <!-- ONGLET GENERAL -->
        <div id="general" class="tab-pane fade show active" role="tabpanel" data-id-lot="<?php echo $lot->getId(); ?>">

            <div class="row">
                <div class="col-3 margin-top-10 ">
                    <div class="alert alert-dark text-center">

                        <?php

                        // Récupération des quantièmes du lot
                        $quantiemes = $lotsManager->getLotQuantiemes($lot);
                        if (empty($quantiemes)) { ?>
                            <h2 <?php echo strlen($lot->getNumlot()) > 10 ? 'class="text-26"' : ''; ?>><?php echo $lot->getNumlot(); ?></h2>
                        <?php
                        } else if (count($quantiemes) == 1) { ?>
                            <h2 <?php echo strlen($lot->getNumlot() . $quantiemes[0]) > 10 ? 'class="text-26"' : ''; ?>><?php echo $lot->getNumlot() . $quantiemes[0]; ?></h2>
                            <?php
                        } else {
                            $i = 1;
                            foreach ($quantiemes as $quantieme) { ?>
                                <h2 class="text-24 <?php echo $i < count($quantiemes) ? 'mb-0' : ''; ?>"><?php echo $lot->getNumlot() . $quantieme; ?></h2>
                                <?php $i++;
                            }
                        }

                        if (is_array($lot->getVues()) && !empty($lot->getVues())) {

                            $firstVue = true;
                            foreach ($lot->getVues() as $lotvue) {

                                if ($lotvue->getVue() instanceof Vue) { ?>
                                    <span class="badge badge-info form-control text-14 <?php echo !$firstVue ? 'margin-top-5' : ''; ?>"><?php echo $lotvue->getVue()->getNom(); ?></span>
                            <?php } else {
                                    echo '&mdash;';
                                } // FN test instanciation objet Vue

                                $firstVue = false;
                            } // FIN boucle vues

                        } else { ?>

                            <span class="badge badge-danger form-control text-14">Hors production</span>

                        <?php } // FIN test vues
                        ?>

                    </div>
                    <?php

                    // SI le lot n'a pas été envoyé à Bizerba, on propose de l'envoyer (si activé)...

                    $configManager = new ConfigManager($cnx);
                    $config_bizerba_actif = $configManager->getConfig('biz_actif');
                    $bizerba_actif = $config_bizerba_actif instanceof Config &&  intval($config_bizerba_actif->getValeur()) == 1;
                    if ($lot->getBizerba() == null && $bizerba_actif) { ?>
                        <button type="button" class="btn btn-warning form-control btnBizerba"><i class="fa fa-share-alt mr-2"></i>Envoyer vers Bizerba&hellip;</button>
                    <?php } else if ($lot->getBizerba() == null && !$bizerba_actif) { ?>
                        <div class="alert alert-danger text-13 texte-fin text-center">
                            <i class="fa fa-exclamation-triangle mb-1"></i><br>L'envoi des fichers vers BizTrack<br>est désactivé !
                        </div>
                    <?php }


                    // Si lot terminé, on propose de le remettre en production
                    $datesOut = ['', '0000-00-00', '0000-00-00 00:00:00'];
                    if (!is_array($lot->getVues()) || empty($lot->getVues()) || !in_array($lot->getDate_out(), $datesOut)) { ?>

                        <button type="button" class="btn btn-success form-control btn-reopenlot"><i class="fa fa-lock-open mr-2"></i> Ré-ouvrir&hellip;</button>

                    <?php } // FIN test lot terminé pour bouton remise en prod
                    ?>


                </div>
                <div class="col-9 margin-top-10 position-relative">

                    <table class="table table-border table-v-middle text-14 table-padding-4-8">
                        <tr>
                            <th class="nowrap">Fournisseur :</th>
                            <td class="text-center"><?php echo $lot->getNom_fournisseur(); ?></td>
                            <th class="nowrap">Produits :</th>
                            <td class="text-20 text-center"><?php echo $lotsManager->getNbProduitsByLot($lot); ?></td
                                </tr>
                        <tr>
                            <th class="nowrap">Espèce :</th>
                            <td class="text-center"><?php echo $lot->getNom_espece();
                                                    echo $lot->getComposition_viande_verbose() != '' ? ' ' . $lot->getComposition_viande_verbose(true)  : ''; ?></td>
                            <th class="nowrap">Composition :</th>
                            <td class="text-center"><?php echo $lot->getComposition_viande_verbose() != '' ? $lot->getComposition_viande_verbose(true)  : '&mdash;';  ?></td>
                        </tr>
                        <tr>
                            <th class="nowrap">Abattoir :</th>
                            <td class="bt-c text-center"><?php echo $lot->getNom_abattoir(); ?></td>
                            <th class="nowrap">Agrément :</th>
                            <td class="text-center"><?php echo $lot->getNumagr_abattoir(); ?></td>
                        </tr>
                        <tr>
                            <th class="nowrap">Date d'abattage :</th>
                            <td class="text-center"><?php echo
                                                    $lot->getDate_abattage() != '' && $lot->getDate_abattage() != '0000-00-00'
                                                        ? Outils::getDate_only_verbose($lot->getDate_abattage(), true, false) : '&mdash;';
                                                    ?></td>
                            <th class="nowrap">Origine :</th>
                            <td class="text-center"><?php echo $lot->getId_origine() > 0 ? $lot->getNom_origine() : '&mdash;' ?></td>
                        </tr>

                        <tr>
                            <th class="nowrap">Poids abattoir :</th>
                            <td class="text-20 text-center"><?php echo $lot->getPoids_abattoir() > 0 ? number_format($lot->getPoids_abattoir(), 3, '.', ' ') . ' <span class="texte-fin text-14">Kg</span>' : '&mdash;'; ?></td>
                            <th class="nowrap">Poids réception :</th>
                            <td class="text-20 text-center"><?php echo $lot->getPoids_reception() > 0 ? number_format($lot->getPoids_reception(), 3, '.', ' ') . ' <span class="texte-fin text-14">Kg</span>' : '&mdash;'; ?></td>
                        </tr>
                        <tr>

                            <th class="nowrap">Date réception :</th>
                            <td class="text-center"><?php echo
                                                    $lot->getDate_reception() != '' && $lot->getDate_reception() != '0000-00-00'
                                                        ? Outils::getDate_only_verbose($lot->getDate_reception(), true, false) : '&mdash;';
                                                    ?></td>
                            <th class="nowrap">Ecart à réception :</th>
                            <td class="text-20 text-center"><?php
                                                            echo $lot->getPoids_abattoir() > 0 && $lot->getPoids_reception() > 0
                                                                ? number_format($lot->getPoids_reception() - $lot->getPoids_abattoir(), 3) . ' <span class="texte-fin text-14">Kg</span>'
                                                                : '&mdash;'; ?></td>
                        </tr>
                    </table>
                    <?php
                    // Affichage des incidents s'il y en a...
                    $incidentsManager = new IncidentsManager($cnx);
                    $params = ['id_lot' => $lot->getId()];
                    $incidents = $incidentsManager->getIncidentsListe($params);
                    if (!empty($incidents)) { ?>
                        <div class="alert alert-danger">
                            <ul class="nomargin">
                                <?php
                                foreach ($incidents as $incident) { ?>
                                    <li class="mb-1">
                                        <span class="fa-stack fa-sm mr-1">
                                            <i class="fas fa-circle fa-stack-2x text-danger"></i>
                                            <i class="fas fa-exclamation fa-stack-1x fa-inverse"></i>
                                        </span>
                                        Incident <?php
                                                    $de = in_array(substr(strtolower($incident->getNom_type_incident()), 0, 1), ['a', 'e', 'i', 'o', 'u', 'é', 'è', 'à']) ? "d'" : "de ";
                                                    echo $de . strtolower($incident->getNom_type_incident()); ?>
                                        <span class="text-13 gris-3 ml-1"> le <?php echo Outils::getDate_verbose($incident->getDate(), false, ' à '); ?>
                                            par <?php echo $incident->getNom_user(); ?></span>
                                    </li>
                                <?php }
                                ?>
                            </ul>
                        </div>
                    <?php } ?>
                    <div class="texte-fin text-12 gris-5">
                        <?php
                        // Affichage du test de traçabilité si réalisé
                        if ($lot->getTest_tracabilite() != null) { ?>

                            <i class="fa fa-clipboard-check mr-1"></i> Test de traçabilité validé le <?php echo Outils::getDate_verbose($lot->getTest_tracabilite(), false, ' à '); ?>

                        <?php }

                        // Affichage de la date d'envoi vers Bizerba
                        if ($lot->getBizerba() != null) { ?>
                            <i class="fa fa-share-alt mr-1 <?php echo $lot->getTest_tracabilite() != null ? 'ml-3' : '' ?>"></i> Envoyé à Bizerba le <?php echo Outils::getDate_verbose($lot->getBizerba(), false, ' à '); ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary btnReBizerba text-10 ml-1 padding-top-0 padding-bottom-0 padding-left-5 padding-right-5 margin-top--3"><i class="fa fa-redo-alt mr-1"></i> Envoyer à nouveau</button>
                        <?php }

                        ?>
                    </div>

                    <code class="text-12 gris-c abs-br padding-top-10 margin-right-15">#<?php echo $lot->getId(); ?></code>
                </div>

            </div>
        </div><!-- FIN ONGLET GENERAL -->

        <!-- ONGLET RECEPTION -->
        <div id="rcp" class="tab-pane fade" role="tabpanel">
            <div class="row">
                <div class="col">
                    <?php if ($reception) { ?>
                        <table class="table table-border table-v-middle text-14 table-padding-4-8">
                            <tr>
                                <th class="nowrap">Date de réception :</th>
                                <td class="bt-c text-center"><?php echo
                                                                $lot->getDate_reception() != '' && $lot->getDate_reception() != '0000-00-00'
                                                                    ? Outils::getDate_only_verbose($lot->getDate_reception(), true, false) : '&mdash;';
                                                                ?></td>
                                <th class="nowrap">Poids receptionné :</th>
                                <td class="text-20 text-center"><?php echo $lot->getPoids_reception() > 0 ? number_format($lot->getPoids_reception(), 3, '.', ' ') . ' <span class="texte-fin text-14">Kg</span>' : '&mdash;'; ?></td>
                            </tr>
                            <tr>
                                <th class="nowrap">Etat visuel :</th>
                                <td class="text-16 text-center">
                                    <?php

                                    // Si le lot n'a pas encore l'état visuel de renseigné..
                                    if ($lot->getReception()->getEtat_visuel() < 0) { ?>
                                        <span class="text-14 texte-fin gris-5"><i class="fa fa-sm mr-1 fa-fw fa-clock"></i>En cours&hellip;</span>
                                    <?php
                                        // Lot visuel renseigné...
                                    } else { ?>

                                        <span class="fa-stack fa-sm">
                                            <i class="fas fa-circle fa-stack-2x text-<?php echo $lot->getReception()->getEtat_visuel() == 1 ? 'success' : 'danger'; ?>"></i>
                                            <i class="fas fa-<?php echo $lot->getReception()->getEtat_visuel() == 1 ? 'check' : 'exclamation'; ?> fa-stack-1x fa-inverse"></i>
                                        </span>
                                        <?php echo $lot->getReception()->getEtat_visuel_verbose(); ?>
                                    <?php
                                    } // FIN test état visuel renseigné 
                                    ?>
                                </td>
                                <th class="nowrap" rowspan="2">Températures :</th>

                                <?php
                                if ($lot->getComposition() == 2 || $lot->getComposition_viande() == 1) { ?>

                                    <td class="text-center" rowspan="2"><code class="text-dark text-20"><?php echo number_format($lot->getReception()->getTemp(), 2, '.', ''); ?></code><span class="gris-7 text-14">°C</span></td>

                                <?php } else { ?>
                                    <td rowspan="2">
                                        <ul class="no-margin">
                                            <li>
                                                <span class="badge badge-info badge-pill badge-dmf text-14">D</span><code class="text-dark text-20"><?php
                                                                                                                                                    echo number_format($lot->getReception()->getTemp_d(), 2, '.', ''); ?></code><span class="gris-7 text-14">°C</span>
                                            </li>
                                            <li>
                                                <span class="badge badge-info badge-pill badge-dmf text-14">M</span><code class="text-dark text-20"><?php
                                                                                                                                                    echo number_format($lot->getReception()->getTemp_m(), 2, '.', ''); ?></code><span class="gris-7 text-14">°C</span>
                                            </li>
                                            <li>
                                                <span class="badge badge-info badge-pill badge-dmf text-14">F</span><code class="text-dark text-20"><?php
                                                                                                                                                    echo number_format($lot->getReception()->getTemp_f(), 2, '.', ''); ?></code><span class="gris-7 text-14">°C</span>
                                            </li>
                                        </ul>
                                    </td>
                                <?php
                                } // FIN test type composition
                                ?>
                            </tr>
                            <tr>
                                <th class="nowrap">Conformité :</th>
                                <td class="text-16 text-center  <?php
                                                                if ($lot->getReception()->getConformite() == 1) {
                                                                    echo 'bg-success text-white';
                                                                } else if ($lot->getReception()->getConformite() == 0) {
                                                                    echo 'bg-danger text-white';
                                                                } ?>">
                                    <?php
                                    // Si le lot n'a pas encore la conformité de renseignée..
                                    if ($lot->getReception()->getConformite() < 0) { ?>
                                        <span class="text-14 texte-fin gris-5"><i class="fa fa-sm mr-1 fa-fw fa-clock"></i>En cours&hellip;</span>
                                    <?php
                                        // Conformité renseignée...
                                    } else { ?>
                                        <i class="fa mr-1 fa-fw fa-<?php
                                                                    echo $lot->getReception()->getConformite() == 1 ? 'check' : 'exclamation';
                                                                    ?>"></i><?php echo $lot->getReception()->getConformite_verbose(); ?>
                                    <?php
                                    } // FIN test conformité renseignée 
                                    ?>
                                </td>
                            </tr>
                            <tr>

                                <th class="nowrap">Visa réceptionniste :</th>
                                <td class="text-center">
                                    <?php
                                    // Si la réception n'a pas été ecnorre validée...
                                    if (!$lot->getReception()->isConfirmee()) { ?>
                                        <span class="text-14 texte-fin gris-5"><i class="fa fa-sm mr-1 fa-fw fa-clock"></i>En cours&hellip;</span>
                                    <?php
                                        // Réception validée...
                                    } else { ?><i class="fa fa-file-signature gris-e5 fa-fw text-36 float-left padding-top-2"></i>
                                    <?php echo $lot->getReception()->getUser_nom() . '<br>' . Outils::getDate_verbose($lot->getReception()->getDate_confirmation(), false, ' - ');
                                    } // FIN test validation
                                    ?>
                                </td>
                                <th class="nowrap">Validation responsable :</th>
                                <td class="text-center">
                                    <?php
                                    // Si la réception n'a pas été ecnorre validée...
                                    if (!$lot->getReception()->isValidee()) { ?>
                                        <span class="text-14 texte-fin gris-5"><i class="fa fa-sm mr-1 fa-fw fa-clock"></i>En cours&hellip;</span>
                                    <?php
                                        // Réception validée...
                                    } else { ?><i class="fa fa-file-signature gris-e5 fa-fw text-36 float-left padding-top-2"></i>
                                    <?php echo $lot->getReception()->getValidateur_nom() . '<br>' . Outils::getDate_verbose($lot->getReception()->getValidation_date(), false, ' - ');
                                    } // FIN test validation
                                    ?>
                                </td>
                            </tr>
                        </table>

                        <?php if (trim($lot->getReception()->getObservations()) != '') { ?>
                            <h4 class="text-18">Observations : <?php echo trim($lot->getReception()->getObservations()) == ''
                                                                    ?  '<em class="texte-12 mr-2 gris-7">Aucune</em>' : '' ?></h4>
                            <p class="border border-secondary rounded padding-15 text-14"><?php echo $lot->getReception()->getObservations(); ?></p>
                        <?php } // FIN test observation

                        // Lot non encore reçu
                    } else { ?>

                        <div class="text-center padding-50 text-24 gris-7">
                            <i class="fa fa-clock fa-lg mr-1"></i>
                            <p>Lot non réceptionné&hellip;</p>
                        </div>

                    <?php } // FIN test objet Reception
                    ?>
                </div>
            </div>

        </div><!-- FIN ONGLET RECEPTION -->

        <!-- ONGLET PRODUITS -->
        <div id="pdts" class="tab-pane fade" role="tabpanel">

            <?php

            // Fonction déportée pour maj depuis pagination ajax sans recharger toute la modale
            modeShowListeProduitsDetailsLot($lot->getId());

            ?>

        </div><!-- FIN ONGLET PRODUITS -->

        <!-- ONGLET TRAITEMENTS  -->
        <div id="cglsrg" class="tab-pane fade" role="tabpanel">

            <?php

            // On récupère les OP de froid associées au lot...
            $froidManager = new FroidManager($cnx);
            $listeFroids = $froidManager->getFroidsListeByLot($id_lot);


            // Si pas de froid trouvé pour ce lot...
            if (empty($listeFroids)) { ?>

                <div class="text-center padding-50 text-24 gris-7">
                    <i class="fa fa-snowflake fa-lg mr-1"></i>
                    <p>Aucun traitement sur ce lot&hellip;</p>
                </div>

            <?php
                // On a trouvé des OP de froid
            } else {
            ?>
                <table class="admin w-100">
                    <thead>
                        <tr>
                            <th>Traitement</th>
                            <th>Code</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">Durée</th>
                            <th class="text-center">Cycle</th>
                            <th class="text-right">T° début</th>
                            <th class="text-right">T° fin</th>
                            <th class="text-center">Produits</th>
                            <th class="text-right">Poids (kg)</th>
                            <th class="text-center">Conformité</th>
                        </tr>
                    </thead>
                    <?php

                    $validationsManager = new ValidationManager($cnx);

                    // Boucle sur les produits
                    foreach ($listeFroids as $opFroid) {

                        $id_froids[] = $opFroid->getId();

                        $poidsTotal         = $froidManager->getPoidsFroid($opFroid);
                        $poidsTotalFroid    = $poidsTotal > 0 ? number_format($froidManager->getPoidsFroid($opFroid), 3, '.', '') : '&mdash;';
                        $nbProduits         = $froidManager->getNbProduitsFroid($opFroid);
                    ?>

                        <tr>
                            <td><?php echo $opFroid->getType_nom(); ?></td>
                            <td><?php echo strtoupper($opFroid->getCode()) . sprintf("%04d", $opFroid->getId()); ?></td>
                            <td class="text-center"><?php echo $opFroid->isEnCours() ? Outils::dateSqlToFr($opFroid->getDate_entree()) : '&mdash;'; ?></td>
                            <td class="text-center"><?php echo $opFroid->isSortie() ? Outils::ecartDatesHeures($opFroid->getDate_entree(), $opFroid->getDate_sortie()) : '&mdash;'; ?></td>
                            <td class="text-center nowrap"><?php

                                                            if ($opFroid->getNuit() == 0) {
                                                                echo 'Jour';
                                                            } else {
                                                                $dt = new DateTime($opFroid->getDate_entree());
                                                                $cycleWe = intval($dt->format('w')) == 5;
                                                                echo $cycleWe ? 'Week-end' : 'Nuit';

                                                                $courbe_temp = $validationsManager->getCourbeTempFroid($opFroid->getId());

                                                                if ($courbe_temp == 0) { ?>
                                        <i class="fa fa-exclamation-triangle ml-1 text-danger"></i>
                                    <?php } else if ($courbe_temp == 1) { ?>
                                        <i class="fa fa-check ml-1 text-success"></i>
                                <?php }
                                                            }

                                ?>
                            </td>
                            <td class="text-right"><?php echo $opFroid->getTemp_debut() != '' ?  $opFroid->getTemp_debut() . '°C' : '&mdash;'; ?></td>
                            <td class="text-right"><?php echo $opFroid->getTemp_fin() != '' ?  $opFroid->getTemp_fin() . '°C' : '&mdash;'; ?></td>
                            <td class="text-center"><?php echo $nbProduits; ?></td>
                            <td class="text-right"><?php echo $poidsTotalFroid; ?></td>
                            <td class="text-center"><?php if ($opFroid->getConformite() < 0) {
                                                        echo '&mdash;';
                                                    } else { ?>
                                    <i class="fa fa-fw margin-right-5 fa-<?php
                                                                            echo $opFroid->getConformite() == 1 ? 'check' : 'exclamation-triangle text-danger'; ?>"></i><?php
                                                                                                                                                                        echo $opFroid->getConformite() == 1 ? 'Conforme' : 'Non conforme'; ?>
                                <?php } ?>
                            </td>
                        </tr>

                    <?php
                    } // FIN boucle sur les produits
                    ?>
                </table>
            <?php
            } // FIN test OPs de froid

            ?>


        </div><!-- FIN ONGLET FROID  -->

        <!-- ONGLET CONTROLE LOMA  -->
        <div id="lom" class="tab-pane fade" role="tabpanel">


            <?php
            // Gestion de la pagination en mode déporté.
            modeShowLomasLot($id_lot);

            ?>

        </div><!-- FIN ONGLET CONTROLE LOMA  -->

        <!-- ONGLET EMBALLAGES  -->
        <div id="emb" class="tab-pane fade" role="tabpanel">
            <?php
            modeShowListeEmballagesDetailsLot($id_lot); // Gestion de la pagination, mode déporté
            ?>
        </div><!-- FIN ONGLET EMBALLAGES -->

        <!-- ONGLET NETTOYAGE  -->
        <div id="net" class="tab-pane fade padding-15" role="tabpanel">

            <?php
            $pvPendantManager    = new PvisuPendantManager($cnx);
            $pvPendants          = $pvPendantManager->getPvisuPendantByLot($lot);

            //Affichage des controles visuels  à la récecption 
            $pvAvantManager = new PvisuAvantManager($cnx);
            $pvAvants = $pvAvantManager->getPvisuAvantByLot($lot);

            //Affichage des controles visuels à la expedition
            $pvApresManager = new PvisuApresManager($cnx);
            $pvApres = $pvApresManager->getPvisuApresByLot($lot);



            if (empty($pvPendants) && empty($pvAvants) && empty($pvApres)) { ?>
                <div class="text-center padding-50 text-24 gris-7">
                    <i class="fa fa-shower fa-lg mr-1"></i>
                    <p>Aucun contrôle visuel disponnible pour ce lot&hellip;</p>
                </div>

            <?php } else {

            ?>
                <div class="row">

                    <div class="col-12">
                        <div class="row">


                            <?php
                            // Pvisu Avant
                            if (!empty($pvAvants)) {
                            ?>
                                <div class="col-12 bg-info text-white text-20 text-center border-left">Vérification propreté visuelle avant la production</div>
                                <?php
                                foreach ($pvAvants as $pvAvant) {
                                    if ($pvAvant instanceof PvisuAvant) {
                                        $visuPoints = $pvAvantManager->getListePvisuAvantPoints($pvAvant);
                                ?>
                                        <div class="col-6 texte-fin text-12 bg-info text-white border-left">Date du contrôle : <?php echo Outils::getDate_only_verbose($pvAvant->getDate(), true); ?></div>
                                        <div class="col-6 texte-fin text-12 text-right bg-info text-white">Responsable du contrôle : <?php echo $pvAvant->getNom_user(); ?></div>

                                        <table class="table">
                                            <?php
                                            foreach ($visuPoints as $point) {
                                                $etatTxt = (int)$point->getEtat() == 1 ? 'Satisfaisant' : 'Non-satisfaisant';
                                                $etatCss = (int)$point->getEtat() == 1 ? 'success' : 'danger';

                                                $id_point_controle = (int)$point->getId_point_controle();

                                                $pointControleManager = new PointsControleManager($cnx);
                                                $pointControleValue = $pointControleManager->getPointControle($id_point_controle);
                                            ?>
                                                <tr>
                                                    <td class="texte-fin text-12"><?php echo $pointControleValue->getNom(); ?></td>
                                                    <td class="texte-fin text-12 nowrap text-<?php echo $etatCss ?>"><?php echo $etatTxt; ?></td>
                                                </tr>

                                            <?php

                                            } // FIN boucle points
                                            ?>
                                        </table>
                                        <?php
                                        if (trim($pvAvant->getCommentaires()) != '') { ?>
                                            <div class="col-12 texte-fin text-12"><?php echo $pvAvant->getCommentaires(); ?></div>
                                        <?php }
                                    } else {  ?>
                                        <div class="col-12 text-center gris-9">Aucun contrôle...</div>
                            <?php } // FIN Pvisu Avant
                                }
                            }
                            ?>

                            <?php
                            if (!empty($pvPendants)) {
                            ?>
                                <div class="col-12 bg-info text-white text-20 text-center border-left">Vérification propreté visuelle pendant la production</div>
                                <?php
                                // Pvisu PENDANT
                                foreach ($pvPendants as $pvPendant) {
                                    if ($pvPendant instanceof PvisuPendant) {
                                        $visuPoints = $pvPendantManager->getListePvisuPendantPoints($pvPendant);
                                ?>
                                        <div class="col-6 texte-fin text-12 bg-info text-white border-left">Date du contrôle : <?php echo Outils::getDate_only_verbose($pvPendant->getDate(), true); ?></div>
                                        <div class="col-6 texte-fin text-12 text-right bg-info text-white">Responsable du contrôle : <?php echo $pvPendant->getNom_user(); ?></div>

                                        <table class="table">
                                            <?php
                                            foreach ($visuPoints as $point) {
                                                $fichenc = (int)$point->getFiche_nc() == 1 ? 'Fiche NC' : '';
                                                $etatTxt = (int)$point->getEtat() == 1 ? 'Satisfaisant' : 'Non-satisfaisant';
                                                $etatCss = (int)$point->getEtat() == 1 ? 'success' : 'danger';

                                            ?>
                                                <tr>
                                                    <td class="texte-fin text-12"><?php echo $point->getNom(); ?></td>
                                                    <td class="texte-fin text-12 nowrap text-<?php echo $etatCss ?>"><?php echo $etatTxt; ?></td>
                                                    <td class="texte-fin text-12"><?php echo $point->getNom_action(); ?></td>
                                                    <td class="texte-fin text-12 nowrap"><?php echo $fichenc; ?></td>
                                                </tr>


                                            <?php

                                            } // FIN boucle points
                                            ?>
                                        </table>
                                        <?php
                                        if (trim($pvPendant->getCommentaires()) != '') { ?>
                                            <div class="col-12 texte-fin text-12"><?php echo $pvPendant->getCommentaires(); ?></div>
                                        <?php }
                                    } else { ?>
                                        <div class="col-12 text-center gris-9">Aucun contrôle...</div>
                                <?php } // FIN Pvisu PENDANT
                                }

                                ?>
                            <?php
                            }
                            ?>
                            <?php
                            if (!empty($pvApres)) {
                            ?>
                                <div class="col-12 bg-info text-white text-20 text-center border-left">Vérification propreté visuelle après la production</div>
                                <?php
                                foreach ($pvApres as $pvApress) {
                                    if ($pvApress instanceof PvisuApres) {
                                        $visuPoints = $pvApresManager->getListePvisuApresPoints($pvApress);
                                ?>
                                        <div class="col-6 texte-fin text-12 bg-info text-white border-left">Date du contrôle : <?php echo Outils::getDate_only_verbose($pvApress->getDate(), true); ?></div>
                                        <div class="col-6 texte-fin text-12 text-right bg-info text-white">Responsable du contrôle : <?php echo $pvApress->getNom_user(); ?></div>

                                        <table class="table">
                                            <?php
                                            foreach ($visuPoints as $point) {
                                                $etatTxt = (int)$point->getEtat() == 1 ? 'Satisfaisant' : 'Non-satisfaisant';
                                                $etatCss = (int)$point->getEtat() == 1 ? 'success' : 'danger';

                                                $id_point_controle = (int)$point->getId_point_controle();

                                                $pointControleManager = new PointsControleManager($cnx);
                                                $pointControleValue = $pointControleManager->getPointControle($id_point_controle);
                                            ?>
                                                <tr>
                                                    <td class="texte-fin text-12"><?php echo $pointControleValue->getNom(); ?></td>
                                                    <td class="texte-fin text-12 nowrap text-<?php echo $etatCss ?>"><?php echo $etatTxt; ?></td>
                                                </tr>

                                            <?php

                                            } // FIN boucle points
                                            ?>
                                        </table>
                                        <?php
                                        if (trim($pvApress->getCommentaires()) != '') { ?>
                                            <div class="col-12 texte-fin text-12"><?php echo $pvApress->getCommentaires(); ?></div>
                                        <?php }
                                    } else {  ?>
                                        <div class="col-12 text-center gris-9">Aucun contrôle...</div>
                            <?php } // FIN Pvisu Avant
                                }
                            }
                            ?>




                        </div>
                    </div>
                </div>
            <?php
            } // FIN test aucun Pvisu
            ?>


        </div><!-- FIN ONGLET NETTOYAGE  -->

        <!-- ONGLET COMMENTAIRES  -->
        <div id="com" class="tab-pane fade" role="tabpanel">

            <?php
            $commentairesManager = new CommentairesManager($cnx);
            $params = [
                'id_lot'      => $id_lot,
                'id_froids'   => $id_froids
            ];
            $listeCom = $commentairesManager->getListeCommentaires($params);

            // Si pas de commentaire pour ce lot ou ses traitements...
            if (empty($listeCom)) { ?>

                <div class="text-center padding-50 text-24 gris-7">
                    <i class="fa fa-comment-slash fa-lg mr-1"></i>
                    <p>Aucun commentaire sur ce lot ou ses traitements&hellip;</p>
                </div>

                <?php
                // On a trouvé des OP de froid
            } else {

                $userManager = new UserManager($cnx);

                $i = 0;
                foreach ($listeCom as $com) {
                    $i++;
                    $userCom = $userManager->getUser($com->getId_user());
                    $nomUser_com = $userCom instanceof User ? $userCom->getNomComplet() : '';

                ?>

                    <div class="com <?php echo $i == 1 ? 'premiercom' : ''; ?>"><?php echo nl2br(strip_tags($com->getCommentaire())); ?>
                        <p class="texte-fin text-right gris-7 text-12 margin-bottom-2"><?php echo $nomUser_com . ', ' . Outils::getDate_verbose($com->getDate()); ?></p>
                    </div>

            <?php } // FIn boucle sur les messages

            }
            ?>


        </div><!-- FIN ONGLET COMMENTAIRES -->


        <!-- ONGLET STOCK  -->
        <div id="stk" class="tab-pane fade" role="tabpanel">

            <?php
            $blManager = new BlManager($cnx);
            $htmlTableStock = '';
            $total_poids_stock = 0;
            $total_poids_expedie = 0;

            // ON récupère tout ce qui est stock (compos) pour ce lot, hors BL
            $produitsStockTous = $produitsManager->getProduitsStock(['lot' => $id_lot, 'hors_bl' => true]);

            if (empty($produitsStockTous)) {
                $htmlTableStock .= '
                <div class="text-center padding-15 text-24 gris-7">
                  <p>Aucun produit en stock sur ce lot.</p>
                </div>';
            } else {


                $colspan = $utilisateur->isDev() ? 7 : 6;
                $colDev = $utilisateur->isDev() ? '<th class="w-100px"><i class="fa fa-user-secret mr-1"></i>Compo</th>' : '';
                $htmlTableStock .= '
            <table class="admin w-100 table-striped">
                <thead>
                <tr><th colspan="' . $colspan . '" class="text-center bg-info">En stock</th></tr>
                <tr>' . $colDev . '
                    <th>Dépot</th>
                    <th>Produit</th>
                    <th class="text-right">Poids Traitement</th>
                    <th class="text-right">Poids Frais</th>
                    <th class="text-right d-none">Poids total</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">DLC/DLUO</th>
                </tr>
                </thead>
                <tbody>';

                $produitsStock = $produitsManager->organiseDonneesProduitsStock($produitsStockTous);



                foreach ($produitsStock as $pdtstock) {

                    $poidsTotalLigne = (float)$pdtstock->getPoids_froid() + (float)$pdtstock->getPoids_frais();

                    $cssPoidsFroid = $pdtstock->getPoids_froid() == 0 ? 'gris-9' : '';
                    $cssPoidsFrais = $pdtstock->getPoids_frais() == 0 ? 'gris-9' : '';
                    $colDev = $utilisateur->isDev() ? '<td><code>' . $pdtstock->getId_compo() . '</code></td>' : '';
                    $htmlTableStock .= '
                    <tr>' . $colDev . '
                        <td>' . $pdtstock->getNom_client() . '</td>
                        <td>' . $pdtstock->getNom_produit() . '</td>
                        <td class="text-right nowrap ' . $cssPoidsFroid . '">' . number_format($pdtstock->getPoids_froid(), 3, '.', ' ') . ' kg</td>
                        <td class="text-right nowrap ' . $cssPoidsFrais . '">' . number_format($pdtstock->getPoids_frais(), 3, '.', ' ') . ' kg</td>
                        <td class="text-right nowrap d-none">' . number_format($poidsTotalLigne, 3, '.', ' ') . ' kg</td>
                        <td class="text-center">' . Outils::dateSqlToFr($pdtstock->getDate_froid()) . '</td>
                        <td class="text-center">' . Outils::dateSqlToFr($pdtstock->getDate_dlc()) . '</td>
                    </tr>';

                    $total_poids_stock += $pdtstock->getPoids_frais() + $pdtstock->getPoids_froid();
                }
                $htmlTableStock .= '
                </tbody>
            </table>';
            } // FIN test produits en stock pour le lot

            $produitsStockExpediesBrut = $produitsManager->getProduitsStock(['lot' => $id_lot, 'en_bl' => true]);

            // On rajoute les produits hors stock générés depuis BL manuels (donc expédiés)
            $produitsHorsStocks = $produitsManager->getProduitsHorsStockByLot($id_lot);
            // Et on les merge avec le reste
            $produitsStockExpedies = array_merge($produitsStockExpediesBrut, $produitsHorsStocks);

            if (empty($produitsStockExpedies)) {
                $htmlTableStock .= '
                    <div class="text-center padding-15 text-24 gris-7">
                        <p>Aucun produit en BL sur ce lot.</p>
                    </div>';
            } else {

                $colspan = $utilisateur->isDev() ? 9 : 8;
                $colDev = $utilisateur->isDev() ? '<th class="w-100px"><i class="fa fa-user-secret mr-1"></i>Compo</th>' : '';

                $htmlTableStock .= '
            <table class="admin w-100 table-lot-stock-expedies">
                <thead>
                <tr><th colspan="' . $colspan . '" class="text-center bg-primary">Expédié</th></tr>
                <tr>' . $colDev . '
                    <th>Client</th>
                    <th>Produit</th>
                    <th class="text-right">Poids Traitement</th>
                    <th class="text-right">Poids Frais</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">DLC/DLUO</th>
                    <th class="text-center">BL/BT</th>
                    <th class="text-center">Facture</th>
                </tr>
                </thead>
                <tbody>';

                $orderPrestashopManager = new OrdersPrestashopManager($cnx);
                $tiersManager = new TiersManager($cnx);
                $id_client_web = $tiersManager->getId_client_web();
                $produitsStockExp = $produitsManager->getProduitsExpediesLot($id_lot);

                // On rattache le nombre de client + le nombre de BL par id_produit parmis la liste
                $pdt_clients = [];
                $pdt_bls = [];
                foreach ($produitsStockExp as $pdtStock) {
                    if (!isset($pdt_clients[$pdtStock->getId_produit()])) {
                        $pdt_clients[$pdtStock->getId_produit()] = [];
                    }
                    if (!isset($pdt_clients[$pdtStock->getId_produit()][$pdtStock->getId_client()])) {
                        $pdt_clients[$pdtStock->getId_produit()][$pdtStock->getId_client()] = true;
                    }
                    if (!isset($pdt_bls[$pdtStock->getId_produit()])) {
                        $pdt_bls[$pdtStock->getId_produit()] = [];
                    }
                    if (!isset($pdt_bls[$pdtStock->getId_produit()][$pdtStock->getId_bl()])) {
                        $pdt_bls[$pdtStock->getId_produit()][$pdtStock->getId_bl()] = true;
                    }
                } // FIN boucle

                $nb_clients_pdt = [];
                $nb_bls_pdt = [];

                foreach ($pdt_clients as $id_pdt => $clients) {
                    if (!isset($nb_clients_pdt[$id_pdt])) {
                        $nb_clients_pdt[$id_pdt] = 0;
                    }
                    $nb_clients_pdt[$id_pdt] += count($clients);;
                }

                foreach ($pdt_bls as $id_pdt => $bls) {
                    if (!isset($nb_bls_pdt[$id_pdt])) {
                        $nb_bls_pdt[$id_pdt] = 0;
                    }
                    $nb_bls_pdt[$id_pdt] += count($bls);;
                }

                $max_iterations_client_produit = [];
                foreach ($produitsStockExp as $pdtstock) {
                    if (!isset($max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()])) {
                        $max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()] = 0;
                    }
                    $max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()]++;;
                }



                $iterations_produit = [];
                $iterations_client_produit = [];
                $total_poids_produit_traitement = [];
                $total_poids_produit_frais = [];







                foreach ($produitsStockExp as $pdtstock) {




                    if (!isset($iterations_produit[$pdtstock->getId_produit()])) {
                        $iterations_produit[$pdtstock->getId_produit()] = 0;
                    }
                    if (!isset($iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()])) {
                        $iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()] = 1;
                    }


                    $colDev     = $utilisateur->isDev() ? '<td><code>' . $pdtstock->getId_compo() . '</code></td>' : '';
                    $colDevide = $utilisateur->isDev() ? '<td></td>' : '';

                    $date_ligne = $pdtstock->getDate() != '' ? Outils::dateSqlToFr($pdtstock->getDate()) : '';
                    $date_dlc = $pdtstock->getDate_dlc() != '' ? Outils::dateSqlToFr($pdtstock->getDate_dlc()) : '';

                    $num_facture = $pdtstock->getNum_facture() != '' ? '<a class="btn btn-link d-block p-0 text-left" target="_blank" href="gc-factures.php?i=' . base64_encode($pdtstock->getId_facture()) . '">' . $pdtstock->getNum_facture() . '</a>' : '';

                    $num_bl = $pdtstock->getNum_bl() != '' ? '<a class="btn btn-link d-block p-0 text-left" target="_blank" href="gc-bls.php?i=' . base64_encode($pdtstock->getId_bl()) . '">' . $pdtstock->getNum_bl() . '</a>' : '';

                    $poids_traitement = $pdtstock->getPoids_froid() > 0 ? number_format($pdtstock->getPoids_froid(), 3, '.', '') . ' kg' : '';
                    $poids_frais = $pdtstock->getPoids_frais() > 0 ? number_format($pdtstock->getPoids_frais(), 3, '.', '') . ' kg' : '';

                    $uniciteR = $pdtstock->getId_produit() . '|' . $pdtstock->getId_client() . '|' . $pdtstock->getId_bl() . '|' . $pdtstock->getId_facture();


                    // Un seul client, un seul BL pour ce produit
                    if ((isset($nb_clients_pdt[$pdtstock->getId_produit()]) && $nb_clients_pdt[$pdtstock->getId_produit()] == 1)
                        && ($max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()]) < 2
                    ) {

                        $htmlTableStock .= '
                           <tr>' . $colDev . '
                                <td>' . $pdtstock->getNom_client() . '</td>
                                <td>' . $pdtstock->getNom_produit() . '</td>
                                <td class="text-right">' . $poids_traitement . '</td>
                                <td class="text-right">' . $poids_frais . '</td>
                                <td class="text-center">' . $date_ligne . '</td>
                                <td class="text-center">' . $date_dlc . '</td>
                                <td class="text-center">' . $num_bl . '</td>
                                <td class="text-center">' . $num_facture . '</td>
                            </tr>';
                    } else {

                        if (!isset($total_poids_produit_traitement[$pdtstock->getId_produit()])) {
                            $total_poids_produit_traitement[$pdtstock->getId_produit()] = 0.0;
                        }

                        if (!isset($total_poids_produit_frais[$pdtstock->getId_produit()])) {
                            $total_poids_produit_frais[$pdtstock->getId_produit()] = 0.0;
                        }

                        if (!isset($total_poids_client_traitement[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()])) {
                            $total_poids_client_traitement[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()] = 0.0;
                        }

                        if (!isset($total_poids_client_frais[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()])) {
                            $total_poids_client_frais[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()] = 0.0;
                        }

                        $total_poids_produit_traitement[$pdtstock->getId_produit()] += $pdtstock->getPoids_froid();
                        $total_poids_produit_frais[$pdtstock->getId_produit()] += $pdtstock->getPoids_frais();

                        $total_poids_client_traitement[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()] += $pdtstock->getPoids_froid();
                        $total_poids_client_frais[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()] += $pdtstock->getPoids_frais();

                        // En-tête à la première itération du produit
                        if ($iterations_produit[$pdtstock->getId_produit()] == 0) {

                            $htmlTableStock .= '
                               <tr>' . $colDevide . '
                                    <td></td>
                                    <td>' . $pdtstock->getNom_produit() . '</td>
                                    <td class="text-right">{{totalPoidsPdtTraitement_' . $pdtstock->getId_produit() . '}}</td>
                                    <td class="text-right">{{totalPoidsPdtFrais_' . $pdtstock->getId_produit() . '}}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>';
                        } // FIN en-tête à la 1ere itération du produit

                        $nomClient = $iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()] == 1
                            ? $pdtstock->getNom_client() :  '';



                        $web = $id_client_web > 0 && $pdtstock->getId_client() == $id_client_web;
                        if ($web) {
                            $od = $orderPrestashopManager->getOrderLigneByIdBl($pdtstock->getId_bl());
                            if ($od instanceof OrderDetailPrestashop) {
                                $nomClient .= ' Commande ' . $od->getReference_order();
                            }
                        }

                        $htmlTableStock .= '
                               <tr class="lot-stock-expedies-client">' . $colDev . '
                                    <td>' . $nomClient . '</td>
                                    <td></td>
                                    <td class="text-right">' . $poids_traitement . '</td>
                                    <td class="text-right">' . $poids_frais . '</td>
                                    <td class="text-center">' . $date_ligne . '</td>
                                    <td class="text-center">' . $date_dlc . '</td>
                                    <td class="text-center">' . $num_bl . '</td>
                                    <td class="text-center">' . $num_facture . '</td>
                                </tr>';

                        // Total à la dernière itération du client pour ce produit
                        if (
                            $iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()] ==
                            $max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()]
                        ) {
                            $htmlTableStock .= '
                               <tr class="lot-stock-expedies-total-client">' . $colDevide . '
                                    <td>Total client</td>
                                    <td></td>
                                    <td class="text-right">{{totalPoidsClientTraitement_' . $pdtstock->getId_produit() . '_' . $pdtstock->getId_client() . '}}</td>
                                    <td class="text-right">{{totalPoidsClientFrais_' . $pdtstock->getId_produit() . '_' . $pdtstock->getId_client() . '}}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>';
                        } // FIN en-tête à la 1ere itération du produit

                    } // FIN test plusieurs clients/Bl pour le même produit

                    $iterations_produit[$pdtstock->getId_produit()]++;
                    $iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()]++;
                    $total_poids_expedie += $pdtstock->getPoids_frais() + $pdtstock->getPoids_froid();
                } // FIN boucle sur les produits expédiés



                foreach ($total_poids_produit_traitement as $id_pdt => $poidsTotal) {
                    $poidsAffiche = $poidsTotal > 0 ? number_format($poidsTotal, 3, '.', ' ') . ' kg' : '';
                    $htmlTableStock = str_replace('{{totalPoidsPdtTraitement_' . $id_pdt . '}}', $poidsAffiche, $htmlTableStock);
                }

                foreach ($total_poids_produit_frais as $id_pdt => $poidsTotal) {
                    $poidsAffiche = $poidsTotal > 0 ? number_format($poidsTotal, 3, '.', ' ') . ' kg' : '';
                    $htmlTableStock = str_replace('{{totalPoidsPdtFrais_' . $id_pdt . '}}', $poidsAffiche, $htmlTableStock);
                }

                foreach ($total_poids_client_traitement as $code => $poidsTotal) {
                    $poidsAffiche = $poidsTotal > 0 ? number_format($poidsTotal, 3, '.', ' ') . ' kg' : '';
                    $htmlTableStock = str_replace('{{totalPoidsClientTraitement_' . $code . '}}', $poidsAffiche, $htmlTableStock);
                }

                foreach ($total_poids_client_frais as $code => $poidsTotal) {
                    $poidsAffiche = $poidsTotal > 0 ? number_format($poidsTotal, 3, '.', ' ') . ' kg' : '';
                    $htmlTableStock = str_replace('{{totalPoidsClientFrais_' . $code . '}}', $poidsAffiche, $htmlTableStock);
                }

                /*$produitsStockExp = $produitsManager->organiseDonneesProduitsStock($produitsStockExpedies);
                foreach ($produitsStockExp as $pdtstock) {

                    $nomsClients = [];
                    $nums_bls = $pdtstock->getNums_bls() != null ? $pdtstock->getNums_bls() : [];
                    if (!empty($nums_bls)) {
                        foreach ($nums_bls as $id_bl => $num_bl) {
                            $n = $blManager->getNomClientBl($id_bl);
                            if ($n != '') {
                                $nomsClients[$n] = $n;
                            }
                        }
                    }
                    $nomsClientsTxt = empty($nomsClients) ? '&mdash;' : implode('<br>', $nomsClients);

                    $poidsTotalLigne = (float)$pdtstock->getPoids_froid() + (float)$pdtstock->getPoids_frais();
                    $cssPoidsFroid = $pdtstock->getPoids_froid() == 0 ? 'gris-9' : '';
                    $cssPoidsFrais = $pdtstock->getPoids_frais() == 0 ? 'gris-9' : '';
                    $colDev = $utilisateur->isDev() ? '<td><code>'.$pdtstock->getId_compo().'</code></td>' : '';

                    $dateDlc = $pdtstock->getDate_dlc() != '' ? Outils::dateSqlToFr($pdtstock->getDate_dlc()) : '&mdash;';

                    $htmlTableStock.='
                <tr>'.$colDev.'
                    <td>'.$nomsClientsTxt.'</td>
                    <td>'. $pdtstock->getNom_produit().'</td>
                    <td class="text-right nowrap '.$cssPoidsFroid.'">'. number_format($pdtstock->getPoids_froid(),3,'.',' ').' kg</td>
                    <td class="text-right nowrap '.$cssPoidsFrais.'">'. number_format($pdtstock->getPoids_frais(),3,'.',' ').' kg</td>
                    <td class="text-right nowrap d-none">'. number_format($poidsTotalLigne,3,'.',' ').' kg</td>


                    <td class="text-center">'. Outils::dateSqlToFr($pdtstock->getDate_froid()).'</td>
                    <td class="text-center">'. $dateDlc.'</td>
                    <td class="pl-0">';

                    if (!empty($nums_bls)) {
                        foreach ($nums_bls as $id_bl => $num_bl) {
                            $htmlTableStock.= $id_bl > 0
                                ? '<a class="btn btn-link d-block p-0 text-left" target="_blank" href="gc-bls.php?i='.base64_encode($id_bl).'">'.$num_bl.'</a>'
                                : '';
                        }
                    }

                    $htmlTableStock.='
                       </td>
                    <td class="pl-0">';

                    // On récupère les factures associées au BL s'il y en a
                    if (!empty($nums_bls)) {
                        foreach ($nums_bls as $id_bl => $num_bl) {
                            if ($id_bl > 0) {

                                $factures = $facturesManager->getNumFacturesByBl($id_bl);
                                if (!empty($factures)) {
                                    foreach ($factures as $fact_id => $fact_num) {
                                        $htmlTableStock.= '
                                    <a class="btn btn-link d-block p-0 text-left" target="_blank" href="gc-factures.php?i='. base64_encode($fact_id).'">'.$fact_num.'</a>';
                                    }
                                }
                            } // FIN test BL
                        }
                    }
                    $htmlTableStock.= '</td>
                </tr>';

                    $total_poids_expedie+=$pdtstock->getPoids_frais() + $pdtstock->getPoids_froid();
                }*/


                $htmlTableStock .= '
                </tbody>
            </table>';
            } // FIN test produits expédiés


            $total_poids_stk_exp = $total_poids_stock + $total_poids_expedie;
            $ecart = $lot->getPoids_reception() > 0 ? ((($lot->getPoids_reception() - $total_poids_stk_exp) * 100) / $lot->getPoids_reception()) * -1 : 0;
            $cssBadge = 'success';

            if ($ecart > 2.1 || $ecart < -2.1) {
                $cssBadge = 'danger';
            } else if ($ecart == 0) {
                $cssBadge = 'secondary';
            }
            ?>
            <div class="alert alert-secondary">
                <div class="row">
                    <div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Total poids en stock</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo number_format($total_poids_stock, 3, '.', ' '); ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>
                    <div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Total poids expédié</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo number_format($total_poids_expedie, 3, '.', ' '); ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>
                    <div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Total poids stock + expédié</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo number_format($total_poids_stk_exp, 3, '.', ' '); ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>
                    <div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Poids receptionné</p>
                        <span class="badge badge-secondary"><code class="gris-e5 text-22"><?php echo
                                                                                            $lot->getPoids_reception() > 0 ? number_format($lot->getPoids_reception(), 3, '.', ' ') : '-'; ?></code><span class="texte-fin text-14"> kg</span></span>
                    </div>
                    <div class="col text-center">
                        <p class="texte-fin text-12 nomargin">Ecart receptionné</p>

                        <span class="badge badge-<?php echo $cssBadge; ?>"><code class="gris-e5 text-22"><?php echo
                                                                                                            $lot->getPoids_reception() > 0 ? number_format($ecart, 1, '.', '') : '-'; ?></code><span class="texte-fin text-14"> %</span></span>
                    </div>
                </div>
            </div>
            <?php
            echo $htmlTableStock;



            ?>

        </div><!-- FIN ONGLET STOCK -->



    </div> <!-- FIN CONTENEUR ONGLETS -->

<?php
    exit;
} // FIN mode


/* --------------------------------------
MODE - Modale édition du lot
---------------------------------------*/
function modeModalLotEdit()
{

    global
        $cnx,
        $lotsManager;

    $abattoirManager    = new AbattoirManager($cnx);
    $paysManager         = new PaysManager($cnx);
    $incidentsManager   = new IncidentsManager($cnx);
    $validationManager  = new ValidationManager($cnx);

    $id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id_lot == 0) {
        echo '-1';
        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        echo '-1';
        exit;
    }

    echo '<i class="fa fa-box"></i><span class="gris-7 ">Lot</span> ' . $lot->getNumlot();

    echo '^'; // Séparateur Title / Body

    // Si on a une validation de réception (signée ou non) en BDD, il n'est plus modifiable
    $modifiable = !$lot->getReception() instanceof LotReception;

?>
    <form class="row" id="formUpdLot">
        <input type="hidden" name="mode" value="updLot" />
        <input type="hidden" name="id_lot" id="updLotIdLot" value="<?php echo $id_lot; ?>" />
        <div class="col">
            <div class="alert alert-dark">
                <div class="row">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-barcode fa-stack-1x fa-inverse text-16 gris-e"></i>
                            </span>Numéro de lot :</label>
                    </div>
                    <div class="col-7">
                        <div class="input-group">
                            <input type="text" class="form-control text-24" placeholder="Numéro de lot" name="numlot" id="updNumLot" value="<?php echo $lot->getNumlot(); ?>" <?php
                                                                                                                                                                                echo !$modifiable ? 'readonly' : ''; ?> />
                            <div class="invalid-feedback">Ce numéro de lot est invalide ou est déjà attribué !</div>
                        </div>
                    </div>
                </div>
                <div class="row">


                </div>
                <div class="row mt-2">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-horse fa-stack-1x fa-inverse text-14 gris-e"></i>
                            </span>Espèce :</label>
                    </div>

                    <div class="col-7">
                        <select class="selectpicker show-tick form-control" id="espece" name="id_espece" title="Sélectionnez..." <?php
                                                                                                                                    echo !$modifiable ? 'disabled' : ''; ?>>
                            <?php
                            $especesManager = new ProduitEspecesManager($cnx);
                            foreach ($especesManager->getListeProduitEspeces() as $espece) { ?>
                                <option value="<?php echo $espece->getId(); ?>" <?php echo $espece->getId() == $lot->getId_espece() ? 'selected' : ''; ?>><?php echo $espece->getNom(); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                </div>
                <div class="row">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-industry fa-stack-1x fa-inverse text-14 gris-e"></i>
                            </span>Fournisseur :</label>
                    </div>

                    <div class="col-7">
                        <select class="selectpicker show-tick form-control" name="id_fournisseur" title="Sélectionnez..." <?php
                                                                                                                            echo !$modifiable ? 'disabled' : ''; ?>>
                            <?php
                            $tiersManager = new TiersManager($cnx);
                            foreach ($tiersManager->getListeFournisseurs([]) as $frs) { ?>
                                <option value="<?php echo $frs->getId(); ?>" <?php echo $frs->getId() == $lot->getId_fournisseur() ? 'selected' : ''; ?>><?php echo $frs->getNom(); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                </div>
            </div>
            <div class="alert alert-dark">
                <div class="row">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-skull-crossbones fa-stack-1x fa-inverse text-12 gris-e"></i>
                            </span>Abattoir :</label>
                    </div>
                    <div class="col-7">
                        <select class="selectpicker show-tick form-control" name="id_abattoir" title="Sélectionnez..." <?php
                                                                                                                        echo !$modifiable ? 'disabled' : ''; ?>>
                            <?php
                            foreach ($abattoirManager->getListeAbattoirs([]) as $abattoir) { ?>
                                <option value="<?php echo $abattoir->getId(); ?>" data-subtext="<?php echo $abattoir->getGenlot(); ?>" <?php
                                                                                                                                        echo $lot->getId_abattoir() == $abattoir->getId() ? 'selected' : '';
                                                                                                                                        ?>><?php echo $abattoir->getNom(); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="far fa-calendar-alt fa-stack-1x fa-inverse text-14 gris-e"></i>
                            </span>Date d'abattage :</label>
                    </div>
                    <div class="col-7">
                        <div class="input-group">
                            <input type="text" class="datepicker form-control" placeholder="Sélectionnez..." name="date_abattage" id="updDateAbattage" value="<?php
                                                                                                                                                                echo Outils::dateSqlToFr($lot->getDate_abattage()); ?>" <?php
                                                                                                                                                                                                                        echo !$modifiable ? 'disabled' : ''; ?> />

                            <div class="input-group-append">
                                <span class="input-group-text"><i class="far fa-calendar-alt gris-5 fa-lg"></i></span>
                            </div>
                            <div class="invalid-feedback">Date invalide !</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-globe-americas fa-stack-1x fa-inverse text-16 gris-e"></i>
                            </span>Origine :</label>
                    </div>
                    <div class="col-7">
                        <select class="selectpicker show-tick form-control" title="Sélectionnez..." name="id_origine" <?php
                                                                                                                        echo !$modifiable ? 'disabled' : ''; ?>>
                            <?php
                            foreach ($paysManager->getListePays() as $pays) { ?>
                                <option value="<?php echo $pays->getId(); ?>" data-subtext="<?php echo $pays->getCode(); ?>" <?php
                                                                                                                                echo $lot->getId_origine() == $pays->getId() ? 'selected' : '';
                                                                                                                                ?>><?php echo $pays->getNom(); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="alert alert-dark">
                <div class="row">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-truck fa-flip-horizontal fa-stack-1x fa-inverse text-12 gris-e"></i>
                            </span>Date de récéption :</label>
                    </div>
                    <div class="col-7">
                        <div class="input-group">
                            <input type="text" class="datepicker form-control" placeholder="Sélectionnez..." name="date_reception" id="updDateReception" value="<?php
                                                                                                                                                                echo Outils::dateSqlToFr($lot->getDate_reception()); ?>" />
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="far fa-calendar-alt gris-5 fa-lg"></i></span>
                            </div>
                            <div class="invalid-feedback">Date invalide !</div>
                        </div>
                    </div>
                </div>
                <?php

                if ($lot->getComposition() != 2) { ?>
                    <div class="row">
                        <div class="col-5">
                            <label class="pt-1 gris-5">
                                <span class="fa-stack text-14 gris-9 mr-1">
                                    <i class="fas fa-circle fa-stack-2x"></i>
                                    <i class="fas fa-weight fa-stack-1x fa-inverse text-16 gris-e"></i>
                                </span>Poids abattoir:</label>
                        </div>
                        <div class="col-7">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="0000.000" name="poids_abattoir" value="<?php echo $lot->getPoids_abattoir() > 0.0 ? $lot->getPoids_abattoir() : ''; ?>" />
                                <div class="input-group-append">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } // FIN test abats 
                ?>
                <div class="row">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-weight fa-stack-1x fa-inverse text-16 gris-e"></i>
                            </span>Poids réception:</label>
                    </div>
                    <div class="col-7">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="0000.000" name="poids_reception" value="<?php echo $lot->getPoids_reception() > 0.0 ? $lot->getPoids_reception() : ''; ?>" />
                            <div class="input-group-append">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-dark">
                <div class="row">
                    <div class="col-5">
                        <label class="pt-1 gris-5">
                            <span class="fa-stack text-14 gris-9 mr-1">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fas fa-exclamation fa-stack-1x fa-inverse text-18 gris-e"></i>
                            </span>Nouvel incident :</label>
                    </div>
                    <div class="col-7">
                        <div class="input-group">
                            <select class="selectpicker show-tick form-control" title="Sélectionnez..." name="incident">
                                <option value="0">Aucun</option>
                                <option data-divider="true"></option>
                                <?php
                                $listeIncidentsTypes = $incidentsManager->getListeTypesIncidents([]);
                                foreach ($listeIncidentsTypes as $incidentType) { ?>
                                    <option value="<?php echo $incidentType->getId(); ?>"><?php echo $incidentType->getNom(); ?></option>
                                <?php }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <textarea class="form-control d-none" placeholder="Commentaire obligatoire sur l'incident..." name="incident_commentaire"></textarea>
                    </div>
                </div>
            </div>

        </div>
    </form>
    <?php
    echo '^'; // Séparateur Body / Footer

    // Si le lot est pret c'est à dire qu'il a une date d'abattage, un abattoir et une originie, on ne peux plus le supprimer mais on peux le sortir du lot
    if (!$modifiable) { ?>
        <button type="button" class="btn btn-info btn-sm btnSortieLot mr-1"><i class="fa fa-sign-out-alt fa-lg vmiddle mr-1"></i> Sortie du lot</button>
        <button type="button" class="btn btn-success btn-sm btnSaveLot"><i class="fa fa-save fa-lg vmiddle mr-1"></i> Enregistrer</button>
    <?php
        // Sinon (il n'est pas encore en circuit) on peux supprimer mais pas le sortir du lot
    } else { ?>
        <button type="button" class="btn btn-success btn-sm btnSaveLot mr-1"><i class="fa fa-save fa-lg vmiddle mr-1"></i> Enregistrer</button>
        <button type="button" class="btn btn-danger btn-sm btnDelLot"><i class="fa fa-times fa-lg vmiddle mr-1"></i> Supprimer</button>
    <?php
    } // FIN test mises à jour

    exit;
} // FIN mode

/* ------------------------------------
MODE - Enregistre les modifs d'un lot
------------------------------------*/
function modeUpdLot()
{

    global
        $cnx,
        $logsManager,
        $lotsManager,
        $utilisateur;

    $numlot          = isset($_REQUEST['numlot'])          ? trim(strtoupper($_REQUEST['numlot']))                : '';
    $date_abattage   = isset($_REQUEST['date_abattage'])   ? Outils::dateFrToSql($_REQUEST['date_abattage'])      : '';
    $date_reception  = isset($_REQUEST['date_reception'])  ? Outils::dateFrToSql($_REQUEST['date_reception'])     : '';
    $id_abattoir     = isset($_REQUEST['id_abattoir'])     ? intval($_REQUEST['id_abattoir'])                     : 0;
    $id_origine      = isset($_REQUEST['id_origine'])      ? intval($_REQUEST['id_origine'])                      : 0;
    $id_fournisseur  = isset($_REQUEST['id_fournisseur'])  ? intval($_REQUEST['id_fournisseur'])                  : 0;
    $id_lot          = isset($_REQUEST['id_lot'])          ? intval($_REQUEST['id_lot'])                          : 0;
    $poids_abattoir  = isset($_REQUEST['poids_abattoir'])  ? floatval(str_replace(',', '.', $_REQUEST['poids_abattoir']))  : 0.0;
    $poids_reception = isset($_REQUEST['poids_reception']) ? floatval(str_replace(',', '.', $_REQUEST['poids_reception'])) : 0.0;
    $id_espece       = isset($_REQUEST['id_espece'])       ? intval($_REQUEST['id_espece'])                       : 0;
    $type_incident   = isset($_REQUEST['incident'])        ? intval($_REQUEST['incident'])                        : 0;
    $incident_commentaire = isset($_REQUEST['incident_commentaire']) ? trim($_REQUEST['incident_commentaire'])    : '';

    // Si on a du mal à récupérer le lot, on retourne une erreur
    if ($id_lot == 0) {
        echo '-2';
        exit;
    }
    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        echo '-2';
        exit;
    }

    // On revérifie que le numéro de lot n'est pas déjà attribué à un autre...
    if ($numlot == '' || $lotsManager->checkLotExiste($numlot, $id_lot)) {
        echo '-1';
        exit;
    }

    if ($lot->getNumlot()           != $numlot && $numlot != '') {
        $lot->setNumlot($numlot);
    }
    if ($lot->getDate_abattage()    != $date_abattage && $date_abattage != '') {
        $lot->setDate_abattage($date_abattage);
    }
    if ($lot->getDate_reception()   != $date_reception) {
        $lot->setDate_reception($date_reception);
    }
    if ($lot->getId_abattoir()      != $id_abattoir && $id_abattoir > 0) {
        $lot->setId_abattoir($id_abattoir);
    }
    if ($lot->getId_origine()       != $id_origine && $id_origine > 0) {
        $lot->setId_origine($id_origine);
    }
    if ($lot->getPoids_abattoir()   != $poids_abattoir) {
        $lot->setPoids_abattoir($poids_abattoir);
    }
    if ($lot->getPoids_reception()  != $poids_reception) {
        $lot->setPoids_reception($poids_reception);
    }
    /*    if ($lot->getComposition()      != $composition && $composition != '')      { $lot->setComposition($composition);        }*/
    if ($lot->getId_espece()      != $id_espece && $id_espece > 0) {
        $lot->setId_espece($id_espece);
    }
    if ($lot->getId_fournisseur()      != $id_fournisseur && $id_fournisseur > 0) {
        $lot->setId_fournisseur($id_fournisseur);
    }

    // Si pas de vue actuelle et que les champs nécessaires sont remplis, on place la vue sur Reception
    if ((!$lot->getVues() || empty($lot->getVues())) &&  $date_abattage != '' && $id_abattoir > 0 && $id_origine > 0) {

        $vuesManager = new VueManager($cnx);
        $vueReception = $vuesManager->getVueByCode('rcp');
        $lotVue = new LotVue([]);
        $lotVue->setId_lot($lot->getId());
        $lotVue->setId_vue($vueReception->getId());
        $lotVue->setDate_entree(date('Y-m-d H:i:s'));
        $lotVuesManager = new LotVueManager($cnx);
        $lotVuesManager->saveLotVue($lotVue);

        // Si le lot n'est plus pret suite à l'upd, on retire les vues du lot pour le passer en lot provisoire
    } else if (!$lot->isReady()) {

        //$lotVuesManager = new LotVueManager($cnx);
        //$lotVuesManager->purgeVuesLot($lot->getId());

    } // FIN tests mises à jour de la vue


    // Si un incident est ajouté et qu'on a bien un commentaire...
    if ($type_incident > 0 && $incident_commentaire != '') {

        $incidentManager = new IncidentsManager($cnx);
        $commentairesManager = new CommentairesManager($cnx);

        $incident = new Incident([]);
        $incident->setId_user($utilisateur->getId());
        $incident->setId_lot($lot->getId());
        $incident->setDate(date('Y-m-d H:i:s'));
        $incident->setType_incident($type_incident);

        $incidentManager->saveIncident($incident);

        $nom_incident = strtolower(Incident::TYPES_INCIDENTS[$type_incident]);
        if ($nom_incident && strlen($nom_incident) > 0) {
            $incident_commentaire = "Incident d'" . $nom_incident . " : " . $incident_commentaire;
        }

        $com = new Commentaire([]);
        $com->setDate(date('Y-m-d H:i:s'));
        $com->setId_lot($lot->getId());
        $com->setIncident(1);
        $com->setId_user($utilisateur->getId());
        $com->setCommentaire($incident_commentaire);
        $commentairesManager->saveCommentaire($com);
    }


    // Si des modifications ont eue lieu, on enregistre...
    if (!empty($lot->attributs)) {
        if (!$lotsManager->saveLot($lot)) {
            echo '-3';
            exit;

            // SI modif OK, on log...
        } else {

            $log = new Log([]);
            $log->setLog_type('info');

            $texteLog = 'Modification du lot ' . $numlot . ' (';
            foreach ($lot->attributs as $attrib) {
                $texteLog .= $attrib . ', ';
            }
            $texteLog = substr($texteLog, 0, -2);
            $texteLog .= ')';

            $log->setLog_texte($texteLog);
            $logsManager->saveLog($log);
        } // FIN test modif

    } else {
        echo '-4';
    }

    exit;
} // FIN mode

/* ------------------------------------
MODE - Sortie d'un lot
------------------------------------*/
function modeSortieLot()
{

    global
        $cnx,
        $logsManager,
        $lotsManager;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;

    // Si on a du mal à récupérer le lot, on retourne une erreur
    if ($id_lot == 0) {
        echo '-1';
        exit;
    }
    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        echo '-1';
        exit;
    }

    $lot->setDate_out(date('Y-m-d H:i:s'));
    if (!$lotsManager->saveLot($lot)) {
        echo '-1';

        // SI sortie OK, on supprime les vues et on log...
    } else {

        $lotVuesManager = new LotVueManager($cnx);
        $lotVuesManager->purgeVuesLot($lot->getId());

        $log = new Log([]);
        $log->setLog_type('info');
        $log->setLog_texte("Sortie du lot " . $lot->getNumlot());
        $logsManager->saveLog($log);
    } // FIN test modif

    exit;
} // FIN mode

/* ------------------------------------
MODE - Suppression d'un lot
------------------------------------*/
function modeSupprimeLot()
{

    global
        $logsManager,
        $lotsManager;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;

    // Si on a du mal à récupérer le lot, on retourne une erreur
    if ($id_lot == 0) {
        echo '-1';
        exit;
    }
    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        echo '-1';
        exit;
    }

    $lot->setSupprime(1);
    $lot->setNumlot('!_' . $lot->getNumlot() . '_');
    if (!$lotsManager->saveLot($lot)) {
        echo '-1';

        // SI suppression OK
    } else {

        // FInalement on supprime le lot carément et toutes ses relations ! (ça déconne et pas le temps de voir ça) !!! :(
        $lotsManager->killLot($id_lot);

        // Log
        $log = new Log([]);
        $log->setLog_type('warning');
        $log->setLog_texte("Suppression du lot " . $lot->getNumlot());
        $logsManager->saveLog($log);
    } // FIN test modif

    exit;
} // FIN mode

/* --------------------------------------
MODE - Téléchargement CSV (Traçabilité)
--------------------------------------*/
function modeTelecharger()
{

    global
        $cnx,
        $logsManager,
        $lotsManager;

    // Filtres
    $regexDateFr        =  '#^(([1-2][0-9]|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9][0-9]{2})$#';

    $filtre_numlot      = isset($_REQUEST['filtre_numlot'])     ? trim(strtoupper($_REQUEST['filtre_numlot']))      : '';
    $filtre_emballage   = isset($_REQUEST['filtre_emballage'])  ? trim(strtoupper($_REQUEST['filtre_emballage']))   : '';
    $filtre_ipf         = isset($_REQUEST['filtre_ipf'])        ? trim(strtoupper($_REQUEST['filtre_ipf']))         : '';

    $filtre_debut       = isset($_REQUEST['filtre_debut'])      && preg_match($regexDateFr, $_REQUEST['filtre_debut'])  ? $_REQUEST['filtre_debut']  : '';
    $filtre_fin         = isset($_REQUEST['filtre_fin'])        && preg_match($regexDateFr, $_REQUEST['filtre_fin'])    ? $_REQUEST['filtre_fin']    : '';

    $filtre_abattoirs       = '';
    $filtre_origines        = '';
    $filtre_clients         = '';
    $filtre_produits        = '';

    if (isset($_REQUEST['filtre_abattoirs'])) {
        $filtre_abattoirs_array = is_array($_REQUEST['filtre_abattoirs']) ? $_REQUEST['filtre_abattoirs'] : explode(',', $_REQUEST['filtre_abattoirs']);
        $filtre_abattoirs       = implode(',', $filtre_abattoirs_array);
    }
    if (isset($_REQUEST['filtre_origines'])) {
        $filtre_origines_array = is_array($_REQUEST['filtre_origines']) ? $_REQUEST['filtre_origines'] : explode(',', $_REQUEST['filtre_origines']);
        $filtre_origines       = implode(',', $filtre_origines_array);
    }
    if (isset($_REQUEST['filtre_clients'])) {
        $filtre_clients_array = is_array($_REQUEST['filtre_clients']) ? $_REQUEST['filtre_clients'] : explode(',', $_REQUEST['filtre_clients']);
        $filtre_clients       = implode(',', $filtre_clients_array);
    }
    if (isset($_REQUEST['filtre_produits'])) {
        $filtre_produits_array = is_array($_REQUEST['filtre_produits']) ? $_REQUEST['filtre_produits'] : explode(',', $_REQUEST['filtre_produits']);
        $filtre_produits       = implode(',', $filtre_produits_array);
    }

    $params['numlot']           = $filtre_numlot;
    $params['ipf']              = intval(str_replace('ipf', '', trim(strtolower($filtre_ipf))));
    $params['emballage']        = $filtre_emballage;
    $params['origines']         = $filtre_origines;
    $params['abattoirs']        = $filtre_abattoirs;
    $params['clients']          = $filtre_clients;
    $params['produits']         = $filtre_produits;
    $params['date_debut']       = $filtre_debut != '' ? Outils::dateFrToSql($filtre_debut)  : '';
    $params['date_fin']         = $filtre_fin   != '' ? Outils::dateFrToSql($filtre_fin)    : '';

    $liste_resultats = $lotsManager->getListeLots($params);

    $data = [];
    $data[] = [utf8_decode('iPrex - Recherche traçabilité')];
    $txt_legende = 'Filtres : ';
    $txt_legende .= $filtre_numlot != '' ? 'Numéro de lot = ' . $filtre_numlot . ' ' : '';
    $txt_legende .= $filtre_ipf != '' ? 'IPF = ' . $filtre_ipf . ' ' : '';
    $txt_legende .= $filtre_emballage != '' ? 'Emballages = ' . $filtre_emballage . ' ' : '';
    $txt_legende .= $filtre_origines != '' ? 'Origines = ' . $filtre_origines . ' ' : '';
    $txt_legende .= $filtre_clients != '' ? 'Clients = ' . $filtre_clients . ' ' : '';
    $txt_legende .= $filtre_produits != '' ? 'Produits = ' . $filtre_produits . ' ' : '';
    $txt_legende .= $filtre_debut != '' ? 'A partir du ' . $filtre_debut . ' ' : '';
    $txt_legende .= $filtre_fin != '' ? 'Jusqu\'au ' . $filtre_fin . ' ' : '';

    $data[] = [utf8_decode($txt_legende)];
    $data[] = ['Lot', utf8_decode('Réception'), 'Origine', 'Abattoir', 'Etat'];

    foreach ($liste_resultats as $res) {

        $data[] = [
            $res->getNumlot(),
            utf8_decode(Outils::getDate_only_verbose($res->getDate_reception(), true, false)),
            utf8_decode($res->getNom_origine()),
            utf8_decode($res->getNom_abattoir()),

            !$res->isEnCours()
                ? 'Sorti le ' . utf8_decode(Outils::getDate_only_verbose($res->getDate_out(), true, false))
                : 'En production'
        ];
    } // FIN boucle

    // On efface les anciens fichiers du même type créés précedemment dans le /temp
    foreach (glob(__CBO_ROOT_PATH__ . "/temp/iprex-results-*.csv") as $aVirer) {
        unlink($aVirer);
    }

    $fichier = 'temp/iprex-results-' . date('ymdHis') . '.csv';

    $fp = fopen(__CBO_ROOT_PATH__ . '/' . $fichier, 'wb');
    foreach ($data as $line) {

        fputcsv($fp, $line, ';');
    }
    fclose($fp);
    echo __CBO_ROOT_PATH__ . $fichier;

    // On logue le téléchargement du CSV
    $log = new Log([]);
    $log->setLog_type('info');
    $log->setLog_texte('Téléchargement CSV de traçabilité');
    $logsManager->saveLog($log);

    exit;
} // FIN mode


/* -----------------------------------------------------------------------
MODE - Enregistement rapide du poids de récéption (BO admin) - Modale
------------------------------------------------------------------------*/
function modeModalPoidsReception()
{

    global
        $lotsManager;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
    if ($id_lot == 0) {
        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        exit;
    }
    ?>
    <div class="alert alert-secondary">
        <div class="row">
            <div class="col-6">
                <h2 class="gris-9 text-38"><span class="text-18">Lot</span> <?php echo $lot->getNumlot(); ?></h2>
            </div>
            <div class="col-6">
                <span class="gris-9">
                    Poids abattoir :</span>
                <p class="gris-5 text-20"><span id="poidsAbattoir"><?php echo $lot->getPoids_abattoir() > 0 ? $lot->getPoids_abattoir() : '&mdash;'; ?></span> kg</p>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <div class="input-group">
                    <input type="text" class="form-control text-28 text-right" id="inputPoidsReception" placeholder="0000.000" name="poids_livraison" value="" data-lot-id="<?php echo $lot->getId(); ?>" />
                    <div class="input-group-append">
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <span class="gris-9">Ecart :</span>
                <p class="gris-5 text-20"><span id="ecartPoidsReception">&mdash;</span> kg</p>
            </div>
        </div>
    </div>


    <?php
    exit;
} // FIN mode

/* -----------------------------------------------------------------------
MODE - Enregistement rapide du poids de récéption (BO admin) - Action
------------------------------------------------------------------------*/
function modeSavePoidsReception()
{

    global
        $logsManager,
        $lotsManager;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
    if ($id_lot == 0) {
        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        exit;
    }

    $poids = isset($_REQUEST['poids']) ? floatval(str_replace(',', '.', $_REQUEST['poids'])) : 0;
    if ($poids == 0) {
        exit;
    }

    $lot->setPoids_reception($poids);
    if ($lotsManager->saveLot($lot)) {
        $log = new Log([]);
        $log->setLog_type('success');
        $log->setLog_texte('Enregistrement du poids de réception du lot #' . $id_lot);
        $logsManager->saveLog($log);
    }
    exit;
} // FIN mode

/* -----------------------------------------------------------------------
MODE/FONCTION INTERNE - Liste produit détail lot (call include + pagination)
-----------------------------------------------------------------------*/
function modeShowListeProduitsDetailsLot($id_lot = 0)
{

    global $cnx, $utilisateur;

    // Préparation des variables
    $params                 = [];
    $params['id_lot']         = $id_lot;
    $params['orderbyfroid'] = true;
    //$params['meme_si_pas_compo'] 	 = true;
    $froidManager     = new FroidManager($cnx);
    $listePdtsLot = $froidManager->getListeLotProduits($params);

    // Aucun Produit
    if (empty($listePdtsLot)) { ?>
        <div class="text-center padding-50 text-24 gris-7">
            <i class="fa fa-box-open fa-lg mr-1"></i>
            <p>Aucun produit&hellip;</p>
        </div>
    <?php
        // Liste des produits
    } else { ?>

        <table class="admin w-100 table-striped">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Espèce</th>
                    <th>Traitement</th>
                    <th class="text-center">Quantième</th>
                    <th class="text-center">Palette</th>
                    <th class="text-right">Poids</th>
                    <th class="text-center">OP Début</th>
                    <th class="text-center">OP Fin</th>
                </tr>
            </thead>
            <tbody>
                <?php


                // On prépare la liste pour les regroupements de produits
                $listePreparee = $listePdtsLot;

                usort($listePreparee, function ($a, $b) {
                    return $a->getProduit()->getId() < $b->getProduit()->getId() ? 1 : -1;
                });


                $listeRegroupeeTraitements  = [];
                $listeRegroupeeFrais        = [];
                $listeRegroupeeHorsStock    = [];

                $poidsTotalParProduitTraitement = [];
                $poidsTotalParProduitFrais      = [];
                $poidsTotalParProduitHorsStock  = [];

                foreach ($listePreparee as $pdtlot) {

                    // Traitement
                    if ($pdtlot->getId_lot_pdt_froid() > 0) {
                        if (!isset($listeRegroupeeTraitements[$pdtlot->getProduit()->getId()])) {
                            $listeRegroupeeTraitements[$pdtlot->getProduit()->getId()] = [];
                        }
                        if (!isset($poidsTotalParProduitTraitement[$pdtlot->getProduit()->getId()])) {
                            $poidsTotalParProduitTraitement[$pdtlot->getProduit()->getId()] = 0;
                        }
                        $listeRegroupeeTraitements[$pdtlot->getProduit()->getId()][] = $pdtlot;
                        $poidsTotalParProduitTraitement[$pdtlot->getProduit()->getId()] += $pdtlot->getPoids();
                        // Frais
                        //} else if ($pdtlot->getIs_froid() == '1') {
                    } else {
                        if (!isset($listeRegroupeeFrais[$pdtlot->getProduit()->getId()])) {
                            $listeRegroupeeFrais[$pdtlot->getProduit()->getId()] = [];
                        }
                        if (!isset($poidsTotalParProduitFrais[$pdtlot->getProduit()->getId()])) {
                            $poidsTotalParProduitFrais[$pdtlot->getProduit()->getId()] = 0;
                        }
                        $listeRegroupeeFrais[$pdtlot->getProduit()->getId()][] = $pdtlot;
                        $poidsTotalParProduitFrais[$pdtlot->getProduit()->getId()] += $pdtlot->getPoids();
                        // Hors stock
                    }
                    /*             else {
                                 if (!isset($listeRegroupeeHorsStock[$pdtlot->getProduit()->getId()])) {
                                     $listeRegroupeeHorsStock[$pdtlot->getProduit()->getId()] = [];
                                 }
                                 if (!isset($poidsTotalParProduitHorsStock[$pdtlot->getProduit()->getId()])) {
                                     $poidsTotalParProduitHorsStock[$pdtlot->getProduit()->getId()] = 0;
                                 }
                                 $listeRegroupeeHorsStock[$pdtlot->getProduit()->getId()][] = $pdtlot;
                                 $poidsTotalParProduitHorsStock[$pdtlot->getProduit()->getId()]+= $pdtlot->getPoids();
                             }*/
                } // FIN boucle sur la liste préparée de base

                // Traitements
                $poidsTotalLot        = 0;
                $sous_total_pdt_poids = 0;
                $sous_total_pdt_colis = 0;

                if (!empty($listeRegroupeeTraitements)) {
                    // Boucle sur tous les produits en traitement
                    foreach ($listeRegroupeeTraitements as $pdtlots) {
                        $first = true;
                        // Boucle sur les produits identiques
                        foreach ($pdtlots as $pdtlot) {
                            if (!strstr($pdtlot->getProduit()->getNom(), 'LOMA')) {
                                //chercher le poids de produits haché
                                $poids_sans_hac =  $pdtlot->getFroidCode() == 'HAC' ? 0 : $pdtlot->getPoids();                                
                                $sous_total_pdt_poids = $sous_total_pdt_poids + $poids_sans_hac;             
                                                              
                                $sous_total_pdt_colis = $sous_total_pdt_colis + $pdtlot->getNb_colis();
                                $nbPdts = count($pdtlots);
                                $multi = $nbPdts > 1;
                                // Si plusieurs produits, on regroupe dynamiquement
                                if ($multi && $first) { ?>
                                    <tr>
                                        <td>
                                            <i class="far fa-plus-square mr-1 gris-7 pointeur" data-toggle="collapse" data-target=".collapsepdt<?php echo $pdtlot->getId_pdt(); ?>"></i>
                                            <?php echo $pdtlot->getProduit()->getNom(); ?>
                                            <span class="badge badge-pill badge-secondary text-12 ml-1"><?php echo $nbPdts; ?></span>
                                        </td>
                                        <td><?php echo $pdtlot->getProduit()->getNom_espece(); ?></td>
                                        <td><i class="fa fa-ellipsis-h gris-9"></i></td>
                                        <td colspan="2"></td>
                                        <td class="text-right"><?php
                                                                echo isset($poidsTotalParProduitTraitement[$pdtlot->getId_pdt()])
                                                                    ? number_format($poidsTotalParProduitTraitement[$pdtlot->getId_pdt()], 3, '.', ' ') . ' Kg' : '-'; ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                <?php
                                    $first = false;
                                } // FIn test plusieurs produits à regrouper
                                ?>
                                <tr <?php echo $multi ? 'class="collapse out collapsepdt' . $pdtlot->getId_pdt() . ' multi-pdt"' : ''; ?>>
                                    <td><?php
                                        echo $multi ? '<i class="fa fa-level-up-alt fa-rotate-90 ml-2 mr-2 "></i>' : '';
                                        echo $pdtlot->getProduit()->getNom(); ?></td>
                                    <td><?php echo $pdtlot->getProduit()->getNom_espece(); ?></td>                                    
                                    <td><?php echo $pdtlot->getOpFroid() != '' ? $pdtlot->getOpFroid()  : strtoupper($pdtlot->getType_froid_nom()); ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getQuantieme() != '' ? $pdtlot->getQuantieme() : '&mdash;'; ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getNumero_palette() > 0 ? $pdtlot->getNumero_palette() : '&mdash;'; ?></td>
                                    <td class="text-right"><?php  echo $pdtlot->getFroidCode()!='HAC' ? number_format($pdtlot->getPoids(), 3, '.', ' ').'Kg' : '&mdash;'; ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getUser_debut() != '' ?  $pdtlot->getUser_debut()  :  '&mdash;'; ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getUser_fin() != '' ? $pdtlot->getUser_fin()  :  '&mdash;'; ?></td>
                                </tr>
                    <?php
                            } // FIN condition des produits LOMA atelier

                        } // FIN boucle sur les produits identiques

                    } // FIN boucle traitements
                    $poidsTotalLot += $sous_total_pdt_poids;
                    // Total Traitements
                    // On affiche le sous-total
                    ?>
                    <tr class="soustotal">
                        <td colspan="5">Total des produits en traitement</td>
                        <td class="text-right"><?php echo number_format($sous_total_pdt_poids, 3, '.', ' '); ?> kg</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php

                } // FIN traitements

                // Frais
                $sous_total_pdt_poids = 0;
                $sous_total_pdt_colis = 0;
                if (!empty($listeRegroupeeFrais)) {
                    // Boucle sur tous les produits frais
                    foreach ($listeRegroupeeFrais as $pdtlots) {
                        $first = true;
                        // Boucle sur les produits identiques
                        foreach ($pdtlots as $pdtlot) {
                            if (!strstr($pdtlot->getProduit()->getNom(), 'LOMA')) {
                                $sous_total_pdt_poids = $sous_total_pdt_poids + $pdtlot->getPoids();
                                $sous_total_pdt_colis = $sous_total_pdt_colis + $pdtlot->getNb_colis();
                                $nbPdts = count($pdtlots);
                                $multi = $nbPdts > 1;
                                // Si plusieurs produits, on regroupe dynamiquement
                                if ($multi && $first) { ?>
                                    <tr>
                                        <td>
                                            <i class="far fa-plus-square mr-1 gris-7 pointeur" data-toggle="collapse" data-target=".collapsepdt<?php echo $pdtlot->getId_pdt(); ?>"></i>
                                            <?php echo $pdtlot->getProduit()->getNom(); ?>
                                            <span class="badge badge-pill badge-secondary text-12 ml-1"><?php echo $nbPdts; ?></span>
                                        </td>
                                        <td><?php echo $pdtlot->getProduit()->getNom_espece(); ?></td>
                                        <td>FRAIS</td>
                                        <td colspan="2"></td>
                                        <td class="text-right"><?php
                                                                echo isset($poidsTotalParProduitFrais[$pdtlot->getId_pdt()])
                                                                    ? number_format($poidsTotalParProduitFrais[$pdtlot->getId_pdt()], 3, '.', ' ') . ' Kg' : '-'; ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                <?php
                                    $first = false;
                                } // FIn test plusieurs produits à regrouper

                                ?>
                                <tr <?php echo $multi ? 'class="collapse out collapsepdt' . $pdtlot->getId_pdt() . '"' : ''; ?>>
                                    <td><?php echo $pdtlot->getProduit()->getNom(); ?></td>
                                    <td><?php echo $pdtlot->getProduit()->getNom_espece(); ?></td>
                                    <td><?php echo $pdtlot->getOpFroid() != '' ? $pdtlot->getOpFroid()  : strtoupper($pdtlot->getType_froid_nom()); ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getQuantieme() != '' ? $pdtlot->getQuantieme() : '&mdash;'; ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getNumero_palette() > 0 ? $pdtlot->getNumero_palette() : '&mdash;'; ?></td>
                                    <td class="text-right poids"><?php echo number_format($pdtlot->getPoids(), 3, '.', ' '); ?> Kgs</td>
                                    <td class="text-center"><?php echo $pdtlot->getUser_debut() != '' ?  $pdtlot->getUser_debut()  :  '&mdash;'; ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getUser_fin() != '' ? $pdtlot->getUser_fin()  :  '&mdash;'; ?></td>
                                </tr>
                    <?php
                            } // FIN Boucle sur les produits identiques

                        } // FIN condition des produits LOMA atelier

                    } // FIN boucle frais
                    $poidsTotalLot += $sous_total_pdt_poids;
                    // Total frais
                    // On affiche le sous-total
                    ?>
                    <tr class="soustotal">
                        <td colspan="5">Total des produits frais & hors stock</td>
                        <td class="text-right"><?php echo number_format($sous_total_pdt_poids, 3, '.', ' '); ?> kg</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php
                } // FIN frais

                // Hors stock
                $sous_total_pdt_poids = 0;
                $sous_total_pdt_colis = 0;
                if (!empty($listeRegroupeeHorsStock)) {
                    // Boucle sur tous les produits hors stock
                    foreach ($listeRegroupeeHorsStock as $pdtlots) {
                        $first = true;

                        // Boucle sur les produits identiques
                        foreach ($pdtlots as $pdtlot) {
                            if (!strstr($pdtlot->getProduit()->getNom(), 'LOMA')) {
                                $sous_total_pdt_poids = $sous_total_pdt_poids + $pdtlot->getPoids();
                                $sous_total_pdt_colis = $sous_total_pdt_colis + $pdtlot->getNb_colis();
                                $nbPdts = count($pdtlots);
                                $multi = $nbPdts > 1;
                                // Si plusieurs produits, on regroupe dynamiquement
                                if ($multi && $first) { ?>
                                    <tr>
                                        <td>
                                            <i class="far fa-plus-square mr-1 gris-7 pointeur" data-toggle="collapse" data-target=".collapsepdt<?php echo $pdtlot->getId_pdt(); ?>"></i>
                                            <?php echo $pdtlot->getProduit()->getNom(); ?>
                                            <span class="badge badge-pill badge-secondary text-12 ml-1"><?php echo $nbPdts; ?></span>
                                        </td>
                                        <td><?php echo $pdtlot->getProduit()->getNom_espece(); ?></td>
                                        <td>HORS STOCK</td>
                                        <td colspan="2"></td>
                                        <td class="text-right"><?php
                                                                echo isset($poidsTotalParProduitHorsStock[$pdtlot->getId_pdt()])
                                                                    ? number_format($poidsTotalParProduitHorsStock[$pdtlot->getId_pdt()], 3, '.', ' ') . ' Kg' : '-'; ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                <?php
                                    $first = false;
                                } // FIn test plusieurs produits à regrouper
                                ?>
                                <tr <?php echo $multi ? 'class="collapse out collapsepdt' . $pdtlot->getId_pdt() . '"' : ''; ?>>
                                    <td><?php echo $pdtlot->getProduit()->getNom(); ?></td>
                                    <td><?php echo $pdtlot->getProduit()->getNom_espece(); ?></td>
                                    <td><?php echo $pdtlot->getOpFroid() != '' ? $pdtlot->getOpFroid()  : strtoupper($pdtlot->getType_froid_nom()); ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getQuantieme() != '' ? $pdtlot->getQuantieme() : '&mdash;'; ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getNumero_palette() > 0 ? $pdtlot->getNumero_palette() : '&mdash;'; ?></td>
                                    <td class="text-right"><?php echo number_format($pdtlot->getPoids(), 3, '.', ' '); ?> Kg</td>
                                    <td class="text-center"><?php echo $pdtlot->getUser_debut() != '' ?  $pdtlot->getUser_debut()  :  '&mdash;'; ?></td>
                                    <td class="text-center"><?php echo $pdtlot->getUser_fin() != '' ? $pdtlot->getUser_fin()  :  '&mdash;'; ?></td>
                                </tr>
                    <?php
                            }  // FIN condition des produits LOMA atelier
                        } // FIN boucle sur les produits identiques
                    } // FIN boucle hors stock
                    $poidsTotalLot += $sous_total_pdt_poids;
                    // Total hors stock
                    // On affiche le sous-total
                    ?>
                    <tr class="soustotal">
                        <td colspan="5">Total des produits hors stock</td>
                        <td class="text-right"><?php echo number_format($sous_total_pdt_poids, 3, '.', ' '); ?> kg</td>
                        <td></td>
                        <td></td>
                    </tr>
                <?php
                } // FIN hors stock


                ?>
            </tbody>
        </table>
        <span class="float-left gris-5 text-14 padding-left-10 margin-top-10 margin-right-20"><i class="fa fa-weight mr-1 gris-9"></i> Poids total des produits du lot : <?php
                                                                                                                                                                            //echo $froidManager->getPoidsLot($id_lot);
                                                                                                                                                                            echo number_format($poidsTotalLot, 3, '.', ' ');
                                                                                                                                                                            ?> kg.</span>


        <div class="clearfix"></div>
    <?php



    } // FIN test produits






} // FIN fonction


// Ancienne méthode
function modeShowListeProduitsDetailsLotOld($id_lot = 0)
{

    global $cnx, $utilisateur;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : $id_lot;
    if ($id_lot == 0) {
        return false;
    }

    $froidManager = new FroidManager($cnx);

    $params = [];


    $params['id_lot']              = $id_lot;
    $params['orderbyfroid']      = true;
    $params['meme_si_pas_compo']      = true;

    $listePdtsLot = $froidManager->getListeLotProduits($params);

    // Si aucun produit
    if (empty($listePdtsLot)) { ?>

        <div class="text-center padding-50 text-24 gris-7">
            <i class="fa fa-box-open fa-lg mr-1"></i>
            <p>Aucun produit&hellip;</p>
        </div>

    <?php
        // Des produits ont été trouvés
    } else {

        // Liste non vide, construction de la pagination...
        /*$nbResults  = $froidManager->getNb_results();
        $pagination = new Pagination($page);
        $pagination->setUrl($filtresPagination);
        $pagination->setNb_results($nbResults);
        $pagination->setAjax_function(true);
        $pagination->setNb_results_page($nbResultPpage);*/


        // Liste des produits du lot

        $poidsTotalLot = 0.0;

    ?>
        <table class="admin w-100 table-striped">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Espèce</th>
                    <th>Traitement</th>
                    <th class="text-center">Quantième</th>
                    <th class="text-center">Palette</th>
                    <th class="text-right">Poids</th>
                    <th class="text-center">OP Début</th>
                    <th class="text-center">OP Fin</th>
                </tr>
            </thead>
            <tbody>
                <?php

                // On prépare la liste pour les regroupements de produits
                $listePreparee = [];
                foreach ($listePdtsLot as $pdtlot) {
                    $unicite = $pdtlot->getId_pdt();
                    $listePreparee[$unicite][] = $pdtlot;
                } // FIN boucle pour préparation
                $old_id_lot_pdt_froid = 0;
                $old_is_froid = -1;
                $start = true;
                // Boucle sur les produits regroupés à l'identique
                foreach ($listePreparee as $pdtlots) {

                    // On compte le nombre de produits distincts
                    $nbPdts = count($pdtlots);

                    // Calcul du poids total
                    $poidsTotal = 0;
                    foreach ($pdtlots as $pdtlot) {
                        $poidsTotal = $poidsTotal + $pdtlot->getPoids();
                    }

                    if ((int)$pdtlot->getId_lot_pdt_froid() == 0 && (int)$old_id_lot_pdt_froid >= 0 && !$start) {
                        echo '<tr><td colspan="8">-Sous total traitement</td></tr>';
                    } else if ((int)$pdtlot->getIs_froid() == 0 && $old_is_froid == 1) {
                        echo '<tr><td colspan="8">-Sous total frais</td></tr>';
                    }
                    $start = false;
                    // Boucle sur les produits distincts
                    foreach ($pdtlots as $i => $pdtlot) {



                        // Si il y en a plusieurs et qu'on est sur le premier, on affiche en plus la ligne de récap
                        if ($i == 0 && $nbPdts > 1) { ?>

                            <tr>
                                <td><?php
                                    if ($utilisateur->isDev()) {
                                        echo '<span class="text-11 gris-9 mr-2"><i class="fa fa-user-secret mr-1"></i>is_froid #' . $pdtlot->getIs_froid()  . ' | old #' . $old_is_froid . '</span>';
                                    }
                                    ?>
                                    <i class="far fa-plus-square mr-1 gris-7 pointeur" data-toggle="collapse" data-target=".collapsepdt<?php echo $pdtlot->getId_pdt(); ?>"></i>
                                    <?php echo $pdtlot->getProduit()->getNom();
                                    if ($nbPdts > 1) { ?>
                                        <span class="badge badge-pill badge-secondary text-12 ml-1"><?php echo $nbPdts; ?></span>
                                    <?php } ?>
                                </td>
                                <td colspan="4"></td>
                                <td class="text-right"><?php echo $poidsTotal; ?> Kg</td>
                                <td colspan="3"></td>
                            </tr>
                        <?php } // FIN ligne de recap à déplier

                        // Début ligne produit distinct
                        ?>

                        <tr <?php
                            // Si ligne produit regroupée, alors on affiche les classes de collapse
                            if ($nbPdts > 1) { ?>class="collapse out collapsepdt<?php echo $pdtlot->getId_pdt(); ?>" <?php } ?>>

                            <td><?php
                                if ($utilisateur->isDev()) {
                                    echo '<span class="text-11 gris-9 mr-2"><i class="fa fa-user-secret mr-1"></i>is_froid #' . $pdtlot->getIs_froid() . ' | old #' . $old_is_froid . '</span>';
                                }

                                // Si ligne produit regroupée, on affiche le décallage avec la flèche avant le nom du produit
                                if ($nbPdts > 1) { ?>
                                    <i class="fa fa-angle-right ml-2 mr-1 gris-9"></i>
                                <?php } ?>
                                <?php echo $pdtlot->getProduit()->getNom(); ?>
                            </td>
                            <td><?php echo $pdtlot->getProduit()->getNom_espece(); ?></td>
                            <td><?php
                                echo $pdtlot->getOpFroid() != '' ? $pdtlot->getOpFroid()  : strtoupper($pdtlot->getType_froid_nom()); ?></td>
                            <td class="text-center"><?php echo $pdtlot->getQuantieme() != '' ? $pdtlot->getQuantieme() : '&mdash;'; ?></td>
                            <td class="text-center"><?php echo $pdtlot->getNumero_palette() > 0 ? $pdtlot->getNumero_palette() : '&mdash;'; ?></td>
                            <td class="text-right"><?php echo number_format($pdtlot->getPoids(), 3, '.', ' '); ?> Kg</td>
                            <td class="text-center"><?php echo $pdtlot->getUser_debut() != '' ?  $pdtlot->getUser_debut()  :  '&mdash;'; ?></td>
                            <td class="text-center"><?php echo $pdtlot->getUser_fin() != '' ? $pdtlot->getUser_fin()  :  '&mdash;'; ?></td>
                        </tr>

                <?php
                        $poidsTotalLot += $pdtlot->getPoids();
                    } // FIN boucle sur les produits
                    $old_id_lot_pdt_froid = $pdtlot->getId_lot_pdt_froid();
                    $old_is_froid = $pdtlot->getIs_froid();
                } // FIN boucle sur les produits (array)
                if ($old_is_froid == 0 && $old_id_lot_pdt_froid == 0) {
                    echo '<tr><td colspan="8">.Sous total Hors stock</td></tr>';
                } else if ($old_is_froid == 1 && $old_id_lot_pdt_froid == 0) {
                    echo '<tr><td colspan="8">.Sous total frais</td></tr>';
                } else {
                    echo '<tr><td colspan="8">.Sous total Traitement</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <span class="float-left gris-5 text-14 padding-left-10 margin-top-10 margin-right-20"><i class="fa fa-weight mr-1 gris-9"></i> Poids total des produits du lot : <?php
                                                                                                                                                                            //echo $froidManager->getPoidsLot($id_lot);
                                                                                                                                                                            echo number_format($poidsTotalLot, 3, '.', ' ');
                                                                                                                                                                            ?> kg.</span>


        <div class="clearfix"></div>
    <?php
    } // FIN test produits trouvés

} // FIN fonction

/* ----------------------------------------------------------------------------
MODE/FONCTION INTERNE - Liste emballages détail lot (call include + pagination)
-----------------------------------------------------------------------------*/
function modeShowListeEmballagesDetailsLot($id_lot = 0)
{

    global $cnx, $lotsManager;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : $id_lot;

    if ($id_lot == 0) {
        return false;
    }

    $lot = $lotsManager->getLot($id_lot);

    if (!$lot instanceof Lot) {
        return false;
    }

    // On récupère tous les emballages associés au lot et aux traitements concernés (pagination)
    $consommablesManager = new ConsommablesManager($cnx);

    // On boucle sur les produits du lot
    $froidManager = new FroidManager($cnx);
    $params['id_lot'] = $id_lot;
    $listePdtsLot = $froidManager->getListeLotProduits($params);

    if (empty($listePdtsLot)) { ?>
        <div class="text-center padding-50 text-24 gris-7">
            <i class="fa fa-boxes fa-lg mr-1"></i>
            <p>Aucun produit associé&hellip;</p>
        </div>

    <?php return true;
    }

    $htmlEmballages       = '';
    $htmlEmballagesFooter = '</table><div class="clearfix"></div>';
    $htmlEmballagesHeader = '<table class="admin w-100">

            <thead>
            <tr>
                <th>Produit</th>
                <th>Famille</th>
                <th>Fournisseur</th>
                <th>Numlot</th>
                <th class="text-center">Déféctueux</th>
            </tr>
            </thead>';


    // Boucle sur les produits pour emballage associé
    $ids_pdts = [];
    foreach ($listePdtsLot as $froidPdt) {

        $pdt4emb = $froidPdt->getProduit();

        // On ne prends qu'une fois chaque produit
        if (in_array($pdt4emb->getId(), $ids_pdts)) {
            continue;
        }

        // On affiche les emballages pour ce produit
        $params = [];
        $params['id_lot']             = $id_lot;
        $params['id_produit']         = $pdt4emb->getId();
        $listEmbslot = $consommablesManager->getListeEmballagesByLot($params);
        if (empty($listEmbslot)) {
            $ids_pdts[] = $pdt4emb->getId();
            continue;
        }

        foreach ($listEmbslot as $emb) {

            $htmlEmballages .= '
            <tr>
                <td>' . $pdt4emb->getNom() . '</td>
                <td>' . $emb->getNom_famille() . '</td>
                <td>' . $emb->getNom_frs() . '</td>
                <td>' . $emb->getNumlot_frs() . '</td>
                <td class="text-center">' . count($emb->getDefectueux()) . '</td>
            </tr>';
        } // FIN boucle sur les produits


        // On intègre le produit dans l'array des produits déja traités
        $ids_pdts[] = $pdt4emb->getId();
    } // FIN boucle sur les produits pour emballage

    if (strlen($htmlEmballages) > 0) {
        echo $htmlEmballagesHeader . $htmlEmballages . $htmlEmballagesFooter;
    } else { ?>
        <div class="text-center padding-50 text-24 gris-7">
            <i class="fa fa-boxes fa-lg mr-1"></i>
            <p>Aucun emballage associé&hellip;</p>
        </div>
    <?php }

    return true;
} // FIN fonction

/* ----------------------------------------------------------------------------
MODE/FONCTION INTERNE - Liste loma lot (call include + pagination)
-----------------------------------------------------------------------------*/
function modeShowLomasLot($id_lot = 0)
{

    global $cnx;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : $id_lot;
    if ($id_lot == 0) {
        return false;
    }

    // On récupère tous les contrôles loma liés au produits du lot
    $lomaManager = new LomaManager($cnx);

    // Préparation pagination (Ajax)
    $nbResultPpage      = 20;
    $page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $filtresPagination  = '?mode=showLomasLot';
    $filtresPagination .= '&id_lot=' . $id_lot;
    $start              = ($page - 1) * $nbResultPpage;

    $params = [];
    $params['start']             = $start;
    $params['nb_result_page']     = $nbResultPpage;
    $params['id_lot']             = $id_lot;



    $listeLomas = $lomaManager->getLomaListe($params);

    // Si pas de Loma trouvé pour ce lot...
    if (empty($listeLomas)) { ?>

        <div class="text-center padding-50 text-24 gris-7">
            <i class="fa fa-clipboard-check fa-lg mr-1"></i>
            <p>Aucun contrôle LOMA réalisé sur ce lot&hellip;</p>
        </div>

    <?php
        // On a trouvé des lomas
    } else {

        // On affiche les tests avant/apres
        $id_froid =

            // Liste non vide, construction de la pagination...
            $nbResults  = $lomaManager->getNb_results();
        $pagination = new Pagination($page);

        $pagination->setUrl($filtresPagination);
        $pagination->setNb_results($nbResults);
        $pagination->setAjax_function(true);
        $pagination->setNb_results_page($nbResultPpage);

    ?>
        <table class="admin w-100">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Traitement</th>
                    <th class="nowrap">Tests avant</th>
                    <th class="nowrap">Tests apres</th>
                    <th>Produit</th>
                    <th>Date</th>
                    <th>Corps</th>
                    <th>Comm.</th>
                    <th>Opérateur</th>
                </tr>
            </thead>
            <?php
            // Boucle sur les loma

            $froidManager = new FroidManager($cnx);

            foreach ($listeLomas as $loma) { ?>

                <tr>
                    <td><code class="nowrap">L<?php echo sprintf("%03d", $loma->getId()); ?></code></td>
                    <td><?php echo strtoupper($loma->getCode_froid()) . sprintf("%04d", $loma->getId_froid()); ?></td>
                    <td class="nowrap"><?php
                                        $froid = $froidManager->getFroid($loma->getId_froid());
                                        $avant_fe = $froid->getTest_avant_fe() == 1 ? 'success' : 'danger';
                                        $avant_nf = $froid->getTest_avant_nfe() == 1 ? 'success' : 'danger';
                                        $avant_in = $froid->getTest_avant_inox() == 1 ? 'success' : 'danger';

                                        if ($froid->getTest_avant_fe() + $froid->getTest_avant_nfe() + $froid->getTest_avant_inox() < 0) {
                                            echo '';
                                        } else {
                                            echo '<span class="badge badge-' . $avant_fe . ' texte-fin text-11 mr-1 cursor-help" title="Test ferreux (3mm)">FE</span>';
                                            echo '<span class="badge badge-' . $avant_nf . ' texte-fin text-11 mr-1 cursor-help" title="Test non-ferreux (5.5mm)">NF</span>';
                                            echo '<span class="badge badge-' . $avant_in . ' texte-fin text-11 mr-1 cursor-help" title="Test INOX (5.5mm)">IN</span>';
                                        }




                                        ?></td>
                    <td class="nowrap"><?php

                                        $apres_fe = $froid->getTest_apres_fe() == 1 ? 'success' : 'danger';
                                        $apres_nf = $froid->getTest_apres_nfe() == 1 ? 'success' : 'danger';
                                        $apres_in = $froid->getTest_apres_inox() == 1 ? 'success' : 'danger';
                                        if ($froid->getTest_apres_fe() + $froid->getTest_apres_nfe() + $froid->getTest_apres_inox() < 0) {
                                            echo '';
                                        } else {
                                            echo '<span class="badge badge-' . $apres_fe . ' texte-fin text-11 mr-1 cursor-help" title="Test ferreux (3mm)">FE</span>';
                                            echo '<span class="badge badge-' . $apres_nf . ' texte-fin text-11 mr-1 cursor-help" title="Test non-ferreux (5.5mm)">NF</span>';
                                            echo '<span class="badge badge-' . $apres_in . ' texte-fin text-11 mr-1 cursor-help" title="Test INOX (5.5mm)">IN</span>';
                                        }





                                        ?></td>
                    <td><?php echo $loma->getNom_produit(); ?></td>
                    <td><?php echo Outils::dateSqlToFr($loma->getDate_test()) . ' ';
                        echo Outils::getHeureOnly($loma->getDate_test()); ?></td>
                    <td>
                        <span class="badge text-14 badge-<?php echo $loma->getTest_pdt() == 0 ? 'success' : 'danger'; ?>">
                            <i class="fa fa-<?php echo $loma->getTest_pdt() == 1 ? 'bell' : 'times'; ?> fa-fw"></i>
                        </span>
                    </td>
                    <td><?php
                        if ($loma->getCommentaire() == '') { ?>
                            &mdash;
                        <?php } else { ?>
                            <a tabindex="0" class="cbo-popover" data-toggle="popover" title="Commentaire" data-content="<?php
                                                                                                                        echo strip_tags(htmlspecialchars($loma->getCommentaire()));
                                                                                                                        ?>" data-container="#lom" data-placement="left"><i class="fa fa-comment-dots text-info fa-lg pointeur" data-trigger="focus"></i></a>
                        <?php }
                        ?>
                    </td>
                    <td><?php echo $loma->getNom_user(); ?></td>
                </tr>

            <?php
            } // FIN boucle sur les loma


            ?>
        </table><?php

                // Pagination (aJax)
                if (isset($pagination)) {
                    // Pagination bas de page, verbose...
                    $pagination->setVerbose_pagination(1);
                    $pagination->setVerbose_position('right');
                    $pagination->setNature_resultats('contrôle');
                    $pagination->setNb_apres(2);
                    $pagination->setNb_avant(2);

                    echo ($pagination->getPaginationHtml());
                } // FIN test pagination
                ?>

        <div class="clearfix"></div>

    <?php
    } // FIN test OPs de froid
} // FIN fonction

/* ----------------------------------------------------------------------------
MODE - Génère un PDF des infos détaillés du lot
-----------------------------------------------------------------------------*/
function modeGenerePdf()
{

    global
        $lotsManager;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
    if ($id_lot == 0) {
        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        exit;
    }

    require_once(__CBO_ROOT_PATH__ . '/vendor/html2pdf/html2pdf.class.php');

    ob_start();
    $content = genereContenuPdf($lot);
    $content .= ob_get_clean();

    // On supprime tous les fichiers du même genre sur le serveur
    foreach (glob(__CBO_ROOT_PATH__ . '/temp/iprexlot-*.pdf') as $fichier) {
        unlink($fichier);
    }


    try {
        $nom_fichier = 'iprexlot-' . sprintf("%04d", $id_lot) . '-' . date('is') . '.pdf';
        $html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15');
        $html2pdf->pdf->SetAutoPageBreak(false, 0);
        $html2pdf->setDefaultFont('helvetica');        
        $html2pdf->writeHTML(utf8_decode($content));;
        $savefilepath = __CBO_ROOT_PATH__ . '/temp/' . $nom_fichier;
        $html2pdf->Output($savefilepath, 'F');
        echo __CBO_TEMP_URL__ . $nom_fichier;
    } catch (HTML2PDF_exception $e) {
        exit;
    }

    exit;
} // FIN fonction

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML du lot pour le PDF
-----------------------------------------------------------------------------*/
function genereContenuPdf(Lot $lot)
{

    global $cnx, $lotsManager;

    $orderPrestashopManager = new OrdersPrestashopManager($cnx);
    $tiersManager = new TiersManager($cnx);
    $id_client_web = $tiersManager->getId_client_web();

    // HEAD
    $contenu = '<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style type="text/css">
  
    * { margin:0; padding: 0; }
  
    .header { border-bottom: 2px solid #ccc; }
    .header img.logo { width: 200px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .table { border-collapse: collapse; }
    .table-donnees th { font-size: 11px; }
    .table-liste th { font-size: 9px; background-color: #d5d5d5; padding:3px; }
    .table-liste td { font-size: 9px; padding-top: 3px; padding-bottom: 3px; border-bottom: 1px solid #ccc;}
    .table-liste td.no-bb { border-bottom: none; padding-bottom: 0px; }
    .table-liste tr.soustotal td { background-color: #d5d5d5; }
    .titre {
       background-color: teal;
       color: #fff;
       padding: 3px;
       text-align: center;
       font-weight: normal;
       font-size: 14px;
    }
    
    table.vtop td { vertical-align: top; }
    
    .w100 { width: 100%; }
    .w75 { width: 75%; }
    .w50 { width: 50%; }
    .w40 { width: 40%; }
    .w25 { width: 25%; }
    .w33 { width: 33%; }
    .w34 { width: 34%; }
    .w30 { width: 30%; }
    .w20 { width: 20%; }
    .w30 { width: 30%; }
    .w15 { width: 15%; }
    .w35 { width: 35%; }
    .w5  { width: 5%;  }
    .w10 { width: 10%; }
    .w15 { width: 15%; }
    
    .text-6 { font-size: 6px; }
    .text-7 { font-size: 7px; }
    .text-8 { font-size: 8px; }
    .text-9 { font-size: 9px; }
    .text-10 { font-size: 10px; }
    .text-11 { font-size: 11px; }
    .text-12 { font-size: 12px; }
    .text-14 { font-size: 14px; }
    .text-16 { font-size: 16px; }
    .text-18 { font-size: 18px; }
    .text-20 { font-size: 20px; }
    
    .gris-3 { color:#333; }
    .gris-5 { color:#555; }
    .gris-7 { color:#777; }
    .gris-9 { color:#999; }
    .gris-c { color:#ccc; }
    .gris-d { color:#d5d5d5; }
    .gris-e { color:#e5e5e5; }
    
    .mt-0 { margin-top: 0px; }
    .mt-2 { margin-top: 2px; }
    .mt-5 { margin-top: 5px; }
    .mt-10 { margin-top: 10px; }
    .mt-15 { margin-top: 15px; }
    .mt-20 { margin-top: 20px; }
    .mt-25 { margin-top: 25px; }
    .mt-50 { margin-top: 50px; }
    
    .mb-0 { margin-bottom: 0px; }
    .mb-2 { margin-bottom: 2px; }
    .mb-5 { margin-bottom: 5px; }
    .mb-10 { margin-bottom: 10px; }
    .mb-15 { margin-bottom: 15px; }
    .mb-20 { margin-bottom: 20px; }
    .mb-25 { margin-bottom: 25px; }
    .mb-50 { margin-bottom: 50px; }
    
    .mr-0 { margin-right: 0px; }
    .mr-2 { margin-right: 2px; }
    .mr-5 { margin-right: 5px; }
    .mr-10 { margin-right: 10px; }
    .mr-15 { margin-right: 15px; }
    .mr-20 { margin-right: 20px; }
    .mr-25 { margin-right: 25px; }
    .mr-50 { margin-right: 50px; }
    
    .ml-0 { margin-left: 0px; }
    .ml-2 { margin-left: 2px; }
    .ml-5 { margin-left: 5px; }
    .ml-10 { margin-left: 10px; }
    .ml-15 { margin-left: 15px; }
    .ml-20 { margin-left: 20px; }
    .ml-25 { margin-left: 25px; }
    .ml-50 { margin-left: 50px; }
    
    .pt-0 { padding-top: 0px; }
    .pt-2 { padding-top: 2px; }
    .pt-5 { padding-top: 5px; }
    .pt-10 { padding-top: 10px; }
    .pt-15 { padding-top: 15px; }
    .pt-20 { padding-top: 20px; }
    .pt-25 { padding-top: 25px; }
    .pt-50 { padding-top: 50px; }
    
    .pb-0 { padding-bottom: 0px; }
    .pb-2 { padding-bottom: 2px; }
    .pb-5 { padding-bottom: 5px; }
    .pb-10 { padding-bottom: 10px; }
    .pb-15 { padding-bottom: 15px; }
    .pb-20 { padding-bottom: 20px; }
    .pb-25 { padding-bottom: 25px; }
    .pb-50 { padding-bottom: 50px; }
    
    .pr-0 { padding-right: 0px; }
    .pr-2 { padding-right: 2px; }
    .pr-5 { padding-right: 5px; }
    .pr-10 { padding-right: 10px; }
    .pr-15 { padding-right: 15px; }
    .pr-20 { padding-right: 20px; }
    .pr-25 { padding-right: 25px; }
    .pr-50 { padding-right: 50px; }
    
    .pl-0 { padding-left: 0px; }
    .pl-2 { padding-left: 2px; }
    .pl-5 { padding-left: 5px; }
    .pl-10 { padding-left: 10px; }
    .pl-15 { padding-left: 15px; }
    .pl-20 { padding-left: 20px; }
    .pl-25 { padding-left: 25px; }
    .pl-50 { padding-left: 50px; }
    
    .text-danger { color: #d9534f; }
    
    table.table-lot-stock-expedies tr td {
        background-color: #e5e5e5;
    }
    
    table.table-lot-stock-expedies tr.lot-stock-expedies-total-client td {
        border-top: 1px solid #cccccc;
        border-bottom: none;
        color:#777;
    }
    
    table.table-lot-stock-expedies tr.lot-stock-expedies-client td {
        border-bottom: none;
    }
    
    table.table-lot-stock-expedies tr.lot-stock-expedies-client td,
    table.table-lot-stock-expedies tr.lot-stock-expedies-total-client td {
        background-color: #f5f5f5;
    }

  </style> 
</head>
<body>';

    $contenu .=  genereEntetePagePdf($lot);

    // PAGE 1

    // GENERAL

    // Préparation des variables
    $na             = '<span class="gris-9 text-11"><i>Non renseigné</i></span>';
    $tiret          = '<span class="gris-9 text-11"><i>-</i></span>';
    $dateAbattage   = $lot->getDate_abattage() != '' && $lot->getDate_abattage() != '0000-00-00'
        ? Outils::getDate_only_verbose($lot->getDate_abattage(), true, false) : '&mdash;';
    $nomOrigine     = $lot->getId_origine() > 0 ? $lot->getNom_origine() : '&mdash;';
    $poidsAbattoir  = $lot->getPoids_abattoir() > 0
        ? number_format($lot->getPoids_abattoir(), 3, '.', ' ') . ' Kg' : $na;
    $poidsReception = $lot->getPoids_reception() > 0
        ? number_format($lot->getPoids_reception(), 3, '.', ' ') . ' Kg' : $na;
    $dateReception  = $lot->getDate_reception() != '' && $lot->getDate_reception() != '0000-00-00'
        ? Outils::getDate_only_verbose($lot->getDate_reception(), true, false) : $na;;
    $ecartReception = $lot->getPoids_abattoir() > 0 && $lot->getPoids_reception() > 0
        ? number_format($lot->getPoids_reception() - $lot->getPoids_abattoir(), 3) . ' <span class="texte-fin text-14">Kg</span>' : $na;;
    $composition   =  $lot->getComposition_viande_verbose() != '' ? $lot->getComposition_viande_verbose(true)  : '-';

    // Génération du contenu HTML
    $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Informations générales</th></tr></table>';
    $contenu .= '<table class="table table-donnees w100 mt-10">
                     <tr>
                        <th class="w20">Fournisseur :</th>
                        <td class="w30">' . $lot->getNom_fournisseur() . '</td>
                        <th class="w20">Nombre de produits :</th>
                        <td class="w30 pt-5 text-right">' . $lotsManager->getNbProduitsByLot($lot) . '</td>
                     </tr>
                     <tr>
                        <th class="w20">Espèce :</th>
                        <td class="w30">' . $lot->getNom_espece() . '</td>
                        <th class="w20">Composition :</th>
                        <td class="w30 pt-5 text-right">' . $composition . '</td>
                     </tr>
                     <tr>
                        <th class="w20">Abattoir :</th>
                        <td class="w30">' . $lot->getNom_abattoir() . '</td>
                        <th class="w20">Agrément  :</th>
                        <td class="w30 text-right">' . $lot->getNumagr_abattoir() . '</td>
                    </tr>
                    <tr>
                        <th class="w20 pt-5">Date d\'abattage :</th>
                        <td class="w30 pt-5">' . $dateAbattage . '</td>
                        <th class="w20 pt-5">Origine   :</th>
                        <td class="w30 pt-5 text-right">' . $nomOrigine . '</td>
                    </tr>
                    <tr>
                        <th class="w20 pt-5">Poids abattoir :</th>
                        <td class="w30 pt-5">' . $poidsAbattoir . '</td>
                        <th class="w20 pt-5">Poids réception :</th>
                        <td class="w30 pt-5 text-right">' . $poidsReception . '</td>
                    </tr>
                    <tr>
                        <th class="w20 pt-5">Date de réception :</th>
                        <td class="w30 pt-5">' . $dateReception . '</td>
                        <th class="w20 pt-5">Ecart à réception :</th>
                        <td class="w30 pt-5 text-right">' . $ecartReception . '</td>
                    </tr>

                </table>';

    // Affichage du test de traçabilité si réalisé
    if ($lot->getTest_tracabilite() != null) {
        $contenu .= '<table class="table table-donnees w100 mt-10">';
        $contenu .= '<tr><td class="w100 gris-9 text-11">Test de traçabilité validé le ' . Outils::getDate_verbose($lot->getTest_tracabilite(), false, ' à ') . '</td></tr>';
        $contenu .= '</table>';
    } // FIN test traçabilité

    // Incidents
    $incidentsManager = new IncidentsManager($cnx);
    $params = ['id_lot' => $lot->getId()];
    $incidents = $incidentsManager->getIncidentsListe($params);
    if (!empty($incidents)) {
        $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Incidents</th></tr></table>';
        $contenu .= '<table class="table table-donnees w100 mt-10 text-11">
                        <tr>
                            <th class="w20">Incident</th>
                            <th class="w40 text-center">Date</th>
                            <th class="w40 text-center">Signalé par</th>
                        </tr>';

        foreach ($incidents as $incident) {

            $contenu .= '<tr>
                                    <td class="w20 pt-5">' . $incident->getNom_type_incident() . '</td>
                                    <td class="w40 pt-5 text-center">' . Outils::getDate_verbose($incident->getDate(), false) . '</td>
                                    <td class="w40 pt-5 text-center">' . $incident->getNom_user() . '</td>
                                    </tr>';
        }
        $contenu .= '<tr><td colspan="3" class="w100 pt-5"> </td></tr>
                <tr>
                    <td colspan="3" class="w100 pt-5 text-center gris-9 text-11"><i>Plus de détails dans la partie "Commentaires" ci-après.</i></td>
                </tr>
            </table>';
    } // FIN test incidents



    // RECEPTION


    if ($lot->getReception() != null) {

        // Préparation des variables
        $statutReception        = $lot->getDate_reception() !== '' && $lot->getDate_reception() != '0000-00-00' ? 'Réceptionné' : 'Non réceptionné';
        $etatVisuel             = $lot->getReception()->getEtat_visuel() < 0 ? $na : '';
        $etatVisuel             = $etatVisuel == '' ? $lot->getReception()->getEtat_visuel_verbose() : $etatVisuel;
        $etatVisuelCss          = $lot->getReception()->getEtat_visuel() == 0 ? 'text-danger' : '';
        $temperaturesReception  = $lot->getComposition() == 2 || $lot->getComposition_viande() == 1
            ? number_format($lot->getReception()->getTemp(), 2, '.', '') . '°C'
            : '<table><tr><td>D</td><td class="text-right pl-15">' . number_format($lot->getReception()->getTemp_d(), 2, '.', '') . '°C </td></tr>'
            . '<tr><td>M</td><td class="text-right pl-15">' . number_format($lot->getReception()->getTemp_m(), 2, '.', '') . '°C </td></tr>'
            . '<tr><td>F</td><td class="text-right pl-15">' . number_format($lot->getReception()->getTemp_f(), 2, '.', '') . '°C</td></tr></table>';

        $conformite             = $lot->getReception()->getConformite() < 0 ? $na : $lot->getReception()->getConformite_verbose();
        $conformiteCss          = $lot->getReception()->getConformite() == 0 ? 'text-danger' : '';
        $nomReceptionniste      = $lot->getReception()->isConfirmee() ? $lot->getReception()->getUser_nom() : $na;
        $visaReceptionniste     = $lot->getReception()->isConfirmee() ? Outils::getDate_verbose($lot->getReception()->getDate_confirmation(), false, ' - ') : $na;
        $nomResponsableRcp      = $lot->getReception()->isValidee() ? $lot->getReception()->getValidateur_nom() : $na;
        $visaResponsableRcp     = $lot->getReception()->isValidee() ? Outils::getDate_verbose($lot->getReception()->getValidation_date(), false, ' - ') : $na;

        // Génération du contenu HTML
        $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Réception</th></tr></table>';
        $contenu .= '<table class="table table-donnees w100 mt-10">
                    <tr>
                        <th class="w20">Statut :</th>
                        <td class="w30">' . $statutReception . '</td>
                        <th class="w20">Etat visuel :</th>
                        <td class="w30 text-right ' . $etatVisuelCss . '">' . $etatVisuel . '</td>
                    </tr>
                     <tr>
                        <th class="w20 pt-5" valign="top">Températures :</th>
                        <td class="w30 pt-5">' . $temperaturesReception . '</td>
                        <th class="w20 pt-5" valign="top">Conformité :</th>
                        <td class="w30 text-right pt-5 ' . $conformiteCss . '" valign="top">' . $conformite . '</td>
                    </tr>
                    <tr>
                        <th class="w20 pt-5">Receptionniste :</th>
                        <td class="w30 pt-5">' . $nomReceptionniste . '</td>
                        <th class="w20 pt-5">Visa :</th>
                        <td class="w30 text-right pt-5">' . $visaReceptionniste . '</td>
                    </tr>
                    <tr>
                        <th class="w20 pt-5">Responsable :</th>
                        <td class="w30 pt-5">' . $nomResponsableRcp . '</td>
                        <th class="w20 pt-5">Visa :</th>
                        <td class="w30 text-right pt-5">' . $visaResponsableRcp . '</td>
                    </tr>
                </table>';
    } else {
        $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Réception</th></tr></table>';
        $contenu .= '<table class="table table-donnees w100 mt-10">
                    <tr><td class="w100 text-center gris-9 text-11"><i>Non réceptionné</i></td></tr>
                </table>';
    }

    // PRODUIT

    // Préparation des variables
    $params                 = [];
    $params['id_lot']         = $lot->getId();
    $params['orderbyfroid'] = true;
    //$params['meme_si_pas_compo'] 	 = true;
    $froidManager     = new FroidManager($cnx);
    $listePdtsLot = $froidManager->getListeLotProduits($params);

    // Génération du contenu HTML
    $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Produits</th></tr></table>';
    $contenu .= '<table class="table table-liste w100 mt-10">';

    // Aucun Produit
    if (empty($listePdtsLot)) {

        $contenu .= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun produit</i></td></tr>';

        // Liste des produits
    } else {

        // Sur le PDF on regroupe toujours les produits identiques
        $regroupement = true;
        $wDesignation  = $regroupement ? '40' : '15';

        $contenu .= '<tr>
                        <th class="' . $wDesignation . '">Désignation</th>
                        <th class="w15">Espèce</th>';
        $contenu .= $regroupement ? '' : '
                        <th class="w15">Traitement</th>
                        <th class="w5 text-center">Quantième</th>
                        <th class="w5 text-center">Palette</th>';
        // <th class="w5 text-center">Colis</th>
        $contenu .=      '<th class="w15 text-right">Poids</th>
                    </tr>';


        // Gestion des sous-totaux par produit
        $id_pdt                 = 0;
        $sous_total_pdt_poids   = 0;
        $sous_total_pdt_colis   = 0;
        $nb_pdts_idem           = 1;


        // On prépare la liste pour les regroupements de produits
        $listePreparee = $listePdtsLot;

        usort($listePreparee, function ($a, $b) {
            return $a->getProduit()->getId() < $b->getProduit()->getId() ? 1 : -1;
        });

        // Regroupement des produits en séparant congelés puis frais
        if ($regroupement) {

            $listeRegroupeeTraitements = [];
            $listeRegroupeeFrais = [];
            $listeRegroupeeHorsStock = [];

            foreach ($listePreparee as $pdtlot) {

                // Traitement
                if ($pdtlot->getId_lot_pdt_froid() > 0) {
                    if (!isset($listeRegroupeeTraitements[$pdtlot->getProduit()->getId()])) {
                        $listeRegroupeeTraitements[$pdtlot->getProduit()->getId()] = $pdtlot;
                    } else {
                        $produitLot = $listeRegroupeeTraitements[$pdtlot->getProduit()->getId()];
                        $poidsTotal = $produitLot->getPoids() + $pdtlot->getPoids();
                        $colisTotal = $produitLot->getNb_colis() + $pdtlot->getNb_colis();
                        $produitLot->setPoids($poidsTotal);
                        $produitLot->setNb_colis($colisTotal);
                        $listeRegroupeeTraitements[$pdtlot->getProduit()->getId()] = $produitLot;
                    }
                    // Frais
                } else {
                    //} else if ($pdtlot->getIs_froid() == '1') {
                    if (!isset($listeRegroupeeFrais[$pdtlot->getProduit()->getId()])) {
                        $listeRegroupeeFrais[$pdtlot->getProduit()->getId()] = $pdtlot;
                    } else {
                        $produitLot = $listeRegroupeeFrais[$pdtlot->getProduit()->getId()];
                        $poidsTotal = $produitLot->getPoids() + $pdtlot->getPoids();
                        $colisTotal = $produitLot->getNb_colis() + $pdtlot->getNb_colis();
                        $produitLot->setPoids($poidsTotal);
                        $produitLot->setNb_colis($colisTotal);
                        $listeRegroupeeFrais[$pdtlot->getProduit()->getId()] = $produitLot;
                    }

                    // Hors stock
                }
                /*                else {
                                    if (!isset($listeRegroupeeHorsStock[$pdtlot->getProduit()->getId()])) {
                                        $listeRegroupeeHorsStock[$pdtlot->getProduit()->getId()] = $pdtlot;
                                    } else {
                                        $produitLot = $listeRegroupeeHorsStock[$pdtlot->getProduit()->getId()];
                                        $poidsTotal = $produitLot->getPoids() + $pdtlot->getPoids();
                                        $colisTotal = $produitLot->getNb_colis() + $pdtlot->getNb_colis();
                                        $produitLot->setPoids($poidsTotal);
                                        $produitLot->setNb_colis($colisTotal);
                                        $listeRegroupeeHorsStock[$pdtlot->getProduit()->getId()] = $produitLot;
                                    }
                                }*/
            } // FIN boucle sur la liste préparée de base

            // Traitements
            $poidsTotalLot        = 0;
            $sous_total_pdt_poids = 0;
            $sous_total_pdt_colis = 0;
            if (!empty($listeRegroupeeTraitements)) {
                foreach ($listeRegroupeeTraitements as $pdtlot) {

                    $sous_total_pdt_poids = $sous_total_pdt_poids + $pdtlot->getPoids();

                    
                    $sous_total_pdt_colis = $sous_total_pdt_colis + $pdtlot->getNb_colis();

                    $contenu .= '<tr>
                                <td class="' . $wDesignation . '">' . $pdtlot->getProduit()->getNom() . '</td>
                                <td class="w15">' . $pdtlot->getProduit()->getNom_espece() . '</td>';
                    //<td class="w5 text-center">' . $pdtlot->getNb_colis() .'</td>
                    $contenu .= '<td class="w15 text-right">' . number_format($pdtlot->getPoids(), 3, '.', ' ') . ' kg</td>
                            </tr>';
                } // FIN boucle traitements
                $poidsTotalLot += $sous_total_pdt_poids;
                // Total Traitements
                // On affiche le sous-total
                $contenu .= '<tr class="soustotal">
                            <td class="' . $wDesignation . '">Total des produits en traitement</td>
                            <td class="w15"></td>';
                //            <td class="w5 text-center">'.$sous_total_pdt_colis.'</td>
                $contenu .= '<td class="w15 text-right">' . $sous_total_pdt_poids . ' kg</td>
                        </tr>';
            } // FIN traitements

            // Frais
            $sous_total_pdt_poids = 0;
            $sous_total_pdt_colis = 0;
            if (!empty($listeRegroupeeFrais)) {
                foreach ($listeRegroupeeFrais as $pdtlot) {

                    $sous_total_pdt_poids = $sous_total_pdt_poids + $pdtlot->getPoids();
                    $sous_total_pdt_colis = $sous_total_pdt_colis + $pdtlot->getNb_colis();

                    $contenu .= '<tr>
                                <td class="' . $wDesignation . '">' . $pdtlot->getProduit()->getNom() . '</td>
                                <td class="w15">' . $pdtlot->getProduit()->getNom_espece() . '</td>';
                    //<td class="w5 text-center">' . $pdtlot->getNb_colis() .'</td>
                    $contenu .= '<td class="w15 text-right">' . number_format($pdtlot->getPoids(), 3, '.', ' ') . ' kg</td>
                            </tr>';
                } // FIN boucle frais
                $poidsTotalLot += $sous_total_pdt_poids;
                // Total frais
                // On affiche le sous-total
                $contenu .= '<tr class="soustotal">
                            <td class="' . $wDesignation . '">Total des produits frais & hors stock</td>
                            <td class="w15"></td>';
                //<td class="w5 text-center">'.$sous_total_pdt_colis.'</td>
                $contenu .= '<td class="w15 text-right">' . $sous_total_pdt_poids . ' kg</td>
                        </tr>';
            } // FIN frais

            // Hors stock
            $sous_total_pdt_poids = 0;
            $sous_total_pdt_colis = 0;
            if (!empty($listeRegroupeeHorsStock)) {
                foreach ($listeRegroupeeHorsStock as $pdtlot) {

                    $sous_total_pdt_poids = $sous_total_pdt_poids + $pdtlot->getPoids();
                    $sous_total_pdt_colis = $sous_total_pdt_colis + $pdtlot->getNb_colis();

                    $contenu .= '<tr>
                                <td class="' . $wDesignation . '">' . $pdtlot->getProduit()->getNom() . '</td>
                                <td class="w15">' . $pdtlot->getProduit()->getNom_espece() . '</td>
                                <td class="w5 text-center">' . $pdtlot->getNb_colis() . '</td>
                                <td class="w10 text-right">' . number_format($pdtlot->getPoids(), 3, '.', ' ') . ' kg</td>
                            </tr>';
                } // FIN boucle hors stock
                $poidsTotalLot += $sous_total_pdt_poids;
                // Total hors stock
                // On affiche le sous-total
                $contenu .= '<tr class="soustotal">
                            <td class="' . $wDesignation . '">Total des produits hors stock</td>
                            <td class="w15"></td>
                            <td class="w5 text-center">' . $sous_total_pdt_colis . '</td>
                            <td class="w10 text-right">' . $sous_total_pdt_poids . ' kg</td>
                        </tr>';
            } // FIN hors stock




            // SI ancien système non regroupé
        } else {
            $poidsTotalLot = 0.0;
            foreach ($listePreparee as $pdtlot) {

                $traitementPdt  = $pdtlot->getOpFroid()    != ''  ? $pdtlot->getOpFroid()    : "FRAIS";

                $quantieme      = intval($pdtlot->getQuantieme()) > 0 ? $pdtlot->getQuantieme() : '-';
                $palette        = $pdtlot->getNumero_palette() > 0 ? $pdtlot->getNumero_palette() : '-';

                $sous_total_pdt_poids = $sous_total_pdt_poids + $pdtlot->getPoids();
                $sous_total_pdt_colis = $sous_total_pdt_colis + $pdtlot->getNb_colis();

                // Gestion des sous-totaux par produit : si on change de produit
                if (!$regroupement && $pdtlot->getProduit()->getId() != $id_pdt) {

                    // ON échape la première ligne
                    if ($id_pdt > 0) {

                        // On retire la valeur de la ligne en cours
                        $sous_total_pdt_colis = $sous_total_pdt_colis - $pdtlot->getNb_colis();
                        $sous_total_pdt_poids = $sous_total_pdt_poids - $pdtlot->getPoids();

                        // On affiche le sous-total
                        $contenu .= '<tr class="soustotal">
                            <td class="' . $wDesignation . '">SOUS-TOTAL</td>
                            <td class="w15"></td>
                            <td class="w15"></td>
                            <td class="w5 text-center"></td>
                            <td class="w5 text-center"></td>
                            <td class="w5 text-center">' . $sous_total_pdt_colis . '</td>
                            <td class="w10 text-right">' . $sous_total_pdt_poids . ' kg</td>
                        </tr>';

                        // on réinitialise les compteurs
                        $sous_total_pdt_poids =  $pdtlot->getPoids();
                        $sous_total_pdt_colis =  $pdtlot->getNb_colis();
                        $nb_pdts_idem           = 1;
                    } // FIN test première ligne

                    // On note qu'on est sur un nouveau produit
                    $id_pdt = $pdtlot->getProduit()->getId();

                    // Gestion des sous-totaux par produit : si on est sur le même produit
                } else {

                    $nb_pdts_idem++;
                } // FIN test changement de produit

                $contenu .= '<tr>
                            <td class="' . $wDesignation . '">' . $pdtlot->getProduit()->getNom() . '</td>
                            <td class="w15">' . $pdtlot->getProduit()->getNom_espece() . '</td>';
                $contenu .= $regroupement ? '' : '<td class="w15">' . $traitementPdt . '</td>
                            <td class="w5 text-center">' . $quantieme . '</td>
                            <td class="w5 text-center">' . $palette . '</td>';
                $contenu .= '    <td class="w5 text-center">' . $pdtlot->getNb_colis() . '</td>
                            <td class="w10 text-right">' . number_format($pdtlot->getPoids(), 3, '.', ' ') . ' kg</td>
                        </tr>';

                $poidsTotalLot += $pdtlot->getPoids();
            } // FIN boucle sur les produits
        } // FIN regroupement





    } // FIN test produits

    // On affiche le sous-total
    $contenu .= $regroupement ? '' : '<tr class="soustotal">
                            <td class="' . $wDesignation . '">SOUS-TOTAL</td>
                            <td class="w15"></td>
                            <td class="w15"></td>
                            <td class="w5 text-center"></td>
                            <td class="w5 text-center"></td>
                            <td class="w5 text-center">' . $sous_total_pdt_colis . '</td>
                            <td class="w10 text-right">' . $sous_total_pdt_poids . ' kg</td>
                        </tr>';

    // on réinitialise les compteurs
    $sous_total_pdt_poids =  $pdtlot->getPoids();
    $sous_total_pdt_colis =  $pdtlot->getNb_colis();
    $nb_pdts_idem           = 1;

    $poidsTotalLotTxt = number_format($poidsTotalLot, 3, '.', ' ');
    //$contenu.= '<tr><td colspan="7" class="w100 text-center">Poids total des produits du lot : '. $froidManager->getPoidsLot($lot->getId()) .' kg.</td></tr>';
    $contenu .= '<tr><td colspan="7" class="w100 text-center">Poids total des produits du lot : ' . $poidsTotalLotTxt . ' kg.</td></tr>';
    $contenu .= '</table>';


    // TRAITEMENTS

    // Préparation des variables
    $froidManager = new FroidManager($cnx);
    $listeFroids = $froidManager->getFroidsListeByLot($lot->getId());

    // Génération du contenu HTML
    $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Traitements</th></tr></table>';
    $contenu .= '<table class="table table-liste w100 mt-10">';

    // ID des op de froid pour les commentaires
    $id_froids = [];

    // Aucun traitement
    if (empty($listeFroids)) {

        $contenu .= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun traitement</i></td></tr>';

        // Liste des traitements
    } else {


        $contenu .= '<tr>
                        <th class="w10">Traitement</th>
                        <th class="w10">Code</th>
                        <th class="w10">Date</th>
                        <th class="w10">Durée</th>
                        <th class="w10">Cycle</th>
                        <th class="w10 text-right">T° début</th>
                        <th class="w10 text-right">T° fin</th>
                        <th class="w10 text-center">Produits</th>
                        <th class="w10 text-right">Poids (kg)</th>
                        <th class="w10 text-right">Conformité</th>
                    </tr>';

        $validationsManager = new ValidationManager($cnx);

        foreach ($listeFroids as $opFroid) {

            $id_froids[] = $opFroid->getId(); // Pour les commentaires

            $poidsTotal         = $froidManager->getPoidsFroid($opFroid);
            $poidsTotalFroid    = $poidsTotal > 0 ? number_format($froidManager->getPoidsFroid($opFroid), 3, '.', '') : '&mdash;';
            $nbProduits         = $froidManager->getNbProduitsFroid($opFroid);

            $traimtDate          = $opFroid->isEnCours()            ? Outils::dateSqlToFr($opFroid->getDate_entree())                                   : $tiret;
            $traimtDuree         = $opFroid->isSortie()             ? Outils::ecartDatesHeures($opFroid->getDate_entree(), $opFroid->getDate_sortie())  : $tiret;
            $traimtTdebut        = $opFroid->getTemp_debut() != ''  ?  $opFroid->getTemp_debut() . '°C'                                                 : $tiret;
            $traimtTfin          = $opFroid->getTemp_fin() != ''    ?  $opFroid->getTemp_fin() . '°C'                                                   : $tiret;
            $traimtConformite    = $opFroid->getConformite() == 1   ? 'Conforme' : 'Non conforme';
            $traimtConformite    = $opFroid->getConformite() < 0    ? $tiret : $traimtConformite;
            $traimtConformiteCss = $opFroid->getConformite() == 0   ? 'text-danger' : '';


            $cycle = 'Jour';
            if ($opFroid->getNuit() == 1) {
                $dt = new DateTime($opFroid->getDate_entree());
                $cycleWe = intval($dt->format('w')) == 5;
                $cycle = $cycleWe ? 'Week-end' : 'Nuit';

                $courbe_temp = $validationsManager->getCourbeTempFroid($opFroid->getId());

                if ($courbe_temp == 0) {
                    $cycle .= ' (ERR)';
                } else if ($courbe_temp == 1) {
                    $cycle .= ' (OK)';
                }
            }



            $contenu .= '<tr>
                            <td class="w10">' . $opFroid->getType_nom() . '</td>
                            <td class="w10">' . strtoupper($opFroid->getCode()) . sprintf("%04d", $opFroid->getId()) . '</td>
                            <td class="w10">' . $traimtDate . '</td>
                            <td class="w10">' . $traimtDuree . '</td>
                            <td class="w10">' . $cycle . '</td>
                            <td class="w10 text-right">' . $traimtTdebut . '</td>
                            <td class="w10 text-right">' . $traimtTfin . '</td>
                            <td class="w10 text-center">' . $nbProduits . '</td>
                            <td class="w10 text-right">' . $poidsTotalFroid . '</td>
                            <td class="w10 text-right ' . $traimtConformiteCss . '">' . $traimtConformite . '</td>
                        </tr>';
        } // FIN boucle sur les traitements

    } // FIN test traitements

    $contenu .= '</table>';


    // STOCK

    $produitsManager = new ProduitManager($cnx);
    $produitsStockTous = $produitsManager->getProduitsStock(['lot' => $lot->getId(), 'hors_bl' => true]);

    // Génération du contenu HTML
    $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Stock</th></tr></table>';
    $contenu .= '<table class="table table-liste w100 mt-10 vtop">';

    // Aucun stock
    if (empty($produitsStockTous)) {

        $contenu .= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun produit en stock sur ce lot</i></td></tr>';

        // Liste des pdts en stock
    } else {

        //$total_poids_restant = 0;
        $total_poids_stock = 0;

        $facturesManager = new FacturesManager($cnx);
        $contenu .= '<tr>
                        <th class="w20">Dépot</th>
                        <th class="w50">Produit</th>
                        <th class="w15 text-right">Poids traitement</th>
                        <th class="w15 text-right">Poids frais</th>

                    </tr>';

        $produitsStock = $produitsManager->organiseDonneesProduitsStock($produitsStockTous);

        foreach ($produitsStock as $pdtstock) {

            $poidsTraitement = $pdtstock->getPoids_froid() > 0 ? number_format($pdtstock->getPoids_froid(), 3, '.', ' ') . ' kg' : '-';
            $poidsFrais = $pdtstock->getPoids_frais() > 0 ? number_format($pdtstock->getPoids_frais(), 3, '.', ' ') . ' kg' : '-';

            $contenu .= '<tr>
                <td class="w20">' . $pdtstock->getNom_client() . '</td>
                <td class="w50">' . $pdtstock->getNom_produit() . '</td>
                <td class="text-right w15">' . $poidsTraitement . '</td>
                <td class="text-right w15">' . $poidsFrais . '</td>
            </tr>';

            //$total_poids_restant+=$pdtstock->getPoids_restant();
            $total_poids_stock += $pdtstock->getPoids_frais() + $pdtstock->getPoids_froid();
        }
    }

    $contenu .= '</table>';

    // EXPEDIE
    $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Expedié</th></tr></table>';
    $contenu .= '<table class="table table-liste w100 mt-10 vtop table-lot-stock-expedies">';


    $produitsStockExp = $produitsManager->getProduitsExpediesLot($lot->getId());
    if (empty($produitsStockExp)) {
        $contenu .= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun produit en BL sur ce lot</i></td></tr>';
    } else {



        $contenu .= '<tr>
					 <th class="w20">Client</th>
					 <th class="w40">Produit</th>
					 <th class="w10 text-right">Poids traitement</th>
					 <th class="w10 text-right">Poids frais</th>
					 <th class="w10 text-center pl-5">BL/BT</th>
					 <th class="w10 text-center pl-5">Facture</th>
				 </tr>';

        // On rattache le nombre de client + le nombre de BL par id_produit parmis la liste
        $pdt_clients = [];
        $pdt_bls = [];
        foreach ($produitsStockExp as $pdtStock) {
            if (!isset($pdt_clients[$pdtStock->getId_produit()])) {
                $pdt_clients[$pdtStock->getId_produit()] = [];
            }
            if (!isset($pdt_clients[$pdtStock->getId_produit()][$pdtStock->getId_client()])) {
                $pdt_clients[$pdtStock->getId_produit()][$pdtStock->getId_client()] = true;
            }
            if (!isset($pdt_bls[$pdtStock->getId_produit()])) {
                $pdt_bls[$pdtStock->getId_produit()] = [];
            }
            if (!isset($pdt_bls[$pdtStock->getId_produit()][$pdtStock->getId_bl()])) {
                $pdt_bls[$pdtStock->getId_produit()][$pdtStock->getId_bl()] = true;
            }
        } // FIN boucle

        $nb_clients_pdt = [];
        $nb_bls_pdt = [];

        foreach ($pdt_clients as $id_pdt => $clients) {
            if (!isset($nb_clients_pdt[$id_pdt])) {
                $nb_clients_pdt[$id_pdt] = 0;
            }
            $nb_clients_pdt[$id_pdt] += count($clients);;
        }

        foreach ($pdt_bls as $id_pdt => $bls) {
            if (!isset($nb_bls_pdt[$id_pdt])) {
                $nb_bls_pdt[$id_pdt] = 0;
            }
            $nb_bls_pdt[$id_pdt] += count($bls);;
        }

        $max_iterations_client_produit = [];
        foreach ($produitsStockExp as $pdtstock) {
            if (!isset($max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()])) {
                $max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()] = 0;
            }
            $max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()]++;;
        }

        $iterations_produit = [];
        $iterations_client_produit = [];
        $total_poids_produit_traitement = [];
        $total_poids_produit_frais = [];

        foreach ($produitsStockExp as $pdtstock) {


            if (!isset($iterations_produit[$pdtstock->getId_produit()])) {
                $iterations_produit[$pdtstock->getId_produit()] = 0;
            }
            if (!isset($iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()])) {
                $iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()] = 1;
            }


            $num_facture = $pdtstock->getNum_facture() != '' ? $pdtstock->getNum_facture() : '';

            $num_bl = $pdtstock->getNum_bl() != '' ? $pdtstock->getNum_bl() : '';

            $poids_traitement = $pdtstock->getPoids_froid() > 0 ? number_format($pdtstock->getPoids_froid(), 3, '.', '') . ' kg' : '';
            $poids_frais = $pdtstock->getPoids_frais() > 0 ? number_format($pdtstock->getPoids_frais(), 3, '.', '') . ' kg' : '';

            // Un seul client, un seul BL pour ce produit
            if ((isset($nb_clients_pdt[$pdtstock->getId_produit()]) && $nb_clients_pdt[$pdtstock->getId_produit()] == 1)
                && (isset($nb_bls_pdt[$pdtstock->getId_produit()]) && $nb_bls_pdt[$pdtstock->getId_produit()] == 1)
            ) {

                $contenu .= '
                               <tr>
                                    <td class="w20">' . $pdtstock->getNom_client() . '</td>
                                    <td class="w40">' . $pdtstock->getNom_produit() . '</td>
                                    <td class="w10 text-right">' . $poids_traitement . '</td>
                                    <td class="w10 text-right">' . $poids_frais . '</td>
                                    <td class="w10 text-center">' . $num_bl . '</td>
                                    <td class="w10 text-center">' . $num_facture . '</td>
                                </tr>';
            } else {

                if (!isset($total_poids_produit_traitement[$pdtstock->getId_produit()])) {
                    $total_poids_produit_traitement[$pdtstock->getId_produit()] = 0.0;
                }

                if (!isset($total_poids_produit_frais[$pdtstock->getId_produit()])) {
                    $total_poids_produit_frais[$pdtstock->getId_produit()] = 0.0;
                }

                if (!isset($total_poids_client_traitement[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()])) {
                    $total_poids_client_traitement[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()] = 0.0;
                }

                if (!isset($total_poids_client_frais[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()])) {
                    $total_poids_client_frais[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()] = 0.0;
                }

                $total_poids_produit_traitement[$pdtstock->getId_produit()] += $pdtstock->getPoids_froid();
                $total_poids_produit_frais[$pdtstock->getId_produit()] += $pdtstock->getPoids_frais();

                $total_poids_client_traitement[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()] += $pdtstock->getPoids_froid();
                $total_poids_client_frais[$pdtstock->getId_produit() . '_' . $pdtstock->getId_client()] += $pdtstock->getPoids_frais();

                // En-tête à la première itération du produit
                if ($iterations_produit[$pdtstock->getId_produit()] == 0) {

                    $contenu .= '
                                   <tr>
                                        <td class="w20"></td>
                                        <td class="w40">' . $pdtstock->getNom_produit() . '</td>
                                        <td class="w10 text-right">{{totalPoidsPdtTraitement_' . $pdtstock->getId_produit() . '}}</td>
                                        <td class="w10 text-right">{{totalPoidsPdtFrais_' . $pdtstock->getId_produit() . '}}</td>
                                        <td class="w10"></td>
                                        <td class="w10"></td>
                                    </tr>';
                } // FIN en-tête à la 1ere itération du produit

                $nomClient = $iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()] == 1
                    ? $pdtstock->getNom_client() :  '';

                $web = $id_client_web > 0 && $pdtstock->getId_client() == $id_client_web;
                if ($web) {
                    $od = $orderPrestashopManager->getOrderLigneByIdBl($pdtstock->getId_bl());
                    if ($od instanceof OrderDetailPrestashop) {
                        $nomClient .= ' Commande ' . $od->getReference_order();
                    }
                }

                $contenu .= '
                                   <tr class="lot-stock-expedies-client">
                                        <td class="w20">' . $nomClient . '</td>
                                        <td class="w40"></td>
                                        <td class="w10 text-right">' . $poids_traitement . '</td>
                                        <td class="w10 text-right">' . $poids_frais . '</td>
                                        <td class="w10 text-center">' . $num_bl . '</td>
                                        <td class="w10 text-center">' . $num_facture . '</td>
                                    </tr>';

                // Total à la dernière itération du client pour ce produit
                if (
                    $iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()] ==
                    $max_iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()]
                ) {
                    $contenu .= '
                                   <tr class="lot-stock-expedies-total-client">
                                        <td class="w20">Total client</td>
                                        <td class="w40"></td>
                                        <td class="w10 text-right">{{totalPoidsClientTraitement_' . $pdtstock->getId_produit() . '_' . $pdtstock->getId_client() . '}}</td>
                                        <td class="w10 text-right">{{totalPoidsClientFrais_' . $pdtstock->getId_produit() . '_' . $pdtstock->getId_client() . '}}</td>
                                        <td class="w10"></td>
                                        <td class="w10"></td>
                                    </tr>';
                } // FIN en-tête à la 1ere itération du produit

            } // FIN test plusieurs clients/Bl pour le même produit

            $iterations_produit[$pdtstock->getId_produit()]++;
            $iterations_client_produit[$pdtstock->getId_produit() . '|' . $pdtstock->getId_client()]++;
            $total_poids_expedie += $pdtstock->getPoids_frais() + $pdtstock->getPoids_froid();
        } // FIN boucle sur les produits expédiés



        foreach ($total_poids_produit_traitement as $id_pdt => $poidsTotal) {
            $poidsAffiche = $poidsTotal > 0 ? number_format($poidsTotal, 3, '.', ' ') . ' kg' : '';
            $contenu = str_replace('{{totalPoidsPdtTraitement_' . $id_pdt . '}}', $poidsAffiche, $contenu);
        }

        foreach ($total_poids_produit_frais as $id_pdt => $poidsTotal) {
            $poidsAffiche = $poidsTotal > 0 ? number_format($poidsTotal, 3, '.', ' ') . ' kg' : '';
            $contenu = str_replace('{{totalPoidsPdtFrais_' . $id_pdt . '}}', $poidsAffiche, $contenu);
        }

        foreach ($total_poids_client_traitement as $code => $poidsTotal) {
            $poidsAffiche = $poidsTotal > 0 ? number_format($poidsTotal, 3, '.', ' ') . ' kg' : '';
            $contenu = str_replace('{{totalPoidsClientTraitement_' . $code . '}}', $poidsAffiche, $contenu);
        }

        foreach ($total_poids_client_frais as $code => $poidsTotal) {
            $poidsAffiche = $poidsTotal > 0 ? number_format($poidsTotal, 3, '.', ' ') . ' kg' : '';
            $contenu = str_replace('{{totalPoidsClientFrais_' . $code . '}}', $poidsAffiche, $contenu);
        }
    } // FIN test produits expédiés trouvés



    /* $produitsStockExpediesBrut = $produitsManager->getProduitsStock(['lot' => $lot->getId(), 'en_bl' => true]);

     // On rajoute les produits hors stock générés depuis BL manuels (donc expédiés)
     $produitsHorsStocks = $produitsManager->getProduitsHorsStockByLot($lot->getId());
     // Et on les merge avec le reste
     $produitsStockExpedies = array_merge($produitsStockExpediesBrut, $produitsHorsStocks);

     // Aucun stock
     if (empty($produitsStockExpedies)) {

         $contenu.= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun produit en BL sur ce lot</i></td></tr>';

         // Liste des pdts en stock
     } else {

//$total_poids_restant = 0;
         $total_poids_expedie = 0;

         $facturesManager = new FacturesManager($cnx);
         $contenu .= '<tr>
                     <th class="w20">Client</th>
                     <th class="w40">Produit</th>
                     <th class="w10 text-right">Poids traitement</th>
                     <th class="w10 text-right">Poids frais</th>
                     <th class="w10 text-center pl-5">BL/BT</th>
                     <th class="w10 text-center pl-5">Facture</th>
                 </tr>';

         $produitsStock = $produitsManager->organiseDonneesProduitsStock($produitsStockExpedies);
         $blManager = new BlManager($cnx);
         foreach ($produitsStock as $pdtstock) {

             $nums_bls = $pdtstock->getNums_bls() != null ? $pdtstock->getNums_bls() : [];
             $facts = [];
             $numeros_bl = '';
             $numeros_factures = '';
             if (!empty($nums_bls)) {
                 $numeros_bl = implode('<br>', $nums_bls);
                 foreach ($nums_bls as $id_bl => $num_bl) {
                     $factures = $facturesManager->getNumFacturesByBl($id_bl);
                     if (!empty($factures)) {
                         foreach ($factures as $fact_id => $fact_num) {
                             $facts[] = $fact_num;
                         }
                     }
                 }
                 $numeros_factures = implode('<br>', $facts);
             }

             $nomsClients = [];
             $nums_bls = $pdtstock->getNums_bls() != null ? $pdtstock->getNums_bls() : [];
             if (!empty($nums_bls)) {
                 foreach ($nums_bls as $id_bl => $num_bl) {
                     $n = $blManager->getNomClientBl($id_bl);
                     if ($n != '') {
                         $nomsClients[$n] = $n;
                     }
                 }
             }

             $nomsClientsTxt = empty($nomsClients) ? '&mdash;' : implode('<br>', $nomsClients);

             $poidsTraitement = $pdtstock->getPoids_froid() > 0 ? number_format($pdtstock->getPoids_froid(), 3, '.', ' ').' kg' : '-';
             $poidsFrais = $pdtstock->getPoids_frais() > 0 ? number_format($pdtstock->getPoids_frais(), 3, '.', ' ').' kg' : '-';

             $contenu .= '<tr>
             <td class="w20">' . $nomsClientsTxt . '</td>
             <td class="w34">' . $pdtstock->getNom_produit() . '</td>
             <td class="text-right w10">' . $poidsTraitement . '</td>
             <td class="text-right w10">' . $poidsFrais . '</td>
             <td class="text-left w10 pl-5">' . $numeros_bl . '</td>
             <td class="text-left w10 pl-5">' . $numeros_factures . '</td>
         </tr>';

             //$total_poids_restant+=$pdtstock->getPoids_restant();
             $total_poids_expedie += $pdtstock->getPoids_frais() + $pdtstock->getPoids_froid();
         }

     }*/
    $contenu .= '</table>';
    $total_poids_stk_exp = $total_poids_stock + $total_poids_expedie;
    $ecart = $lot->getPoids_reception() > 0 ? ((($lot->getPoids_reception() - $total_poids_stk_exp) * 100) / $lot->getPoids_reception()) * -1 : 0;

    $contenu .= '<table class="table table-liste w100 mt-10">
                        <tr>
                            <th class="w20 text-center">Poids en stock</th>
                            <th class="w20 text-center">Poids expédié</th>
                            <th class="w20 text-center">Total stock+expédié</th>
                            <th class="w20 text-center">Poids receptionné</th>
                            <th class="w20 text-center">Ecart receptionné</th>
                        </tr>
                        <tr>
                            <td class="w20 text-center">' . number_format($total_poids_stock, 3, '.', ' ') . ' kg</td>
                            <td class="w20 text-center">' . number_format($total_poids_expedie, 3, '.', ' ') . ' kg</td>
                            <td class="w20 text-center">' . number_format($total_poids_stk_exp, 3, '.', ' ') . ' kg</td>
                            <td class="w20 text-center">' . number_format((float)$lot->getPoids_reception(), 3, '.', ' ') . ' kg</td>
                            <td class="w20 text-center">' . number_format($ecart, 1, '.', ' ') . ' %</td>
                        </tr>';
    $contenu .= '</table>';


    // CONTROLES LOMA

    // Préparation des variables
    $lomaManager = new LomaManager($cnx);
    $params                 = [];
    $params['id_lot']         = $lot->getId();
    $listeLomas = $lomaManager->getLomaListe($params);

    // Génération du contenu HTML
    $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Contrôles de détection métallique</th></tr></table>';
    $contenu .= '<table class="table table-liste w100 mt-10">';

    // Aucun contrôle loma
    if (empty($listeLomas)) {

        $contenu .= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun contrôle</i></td></tr>';

        // Liste des lomas
    } else {

        $froidManager = new FroidManager($cnx);

        $contenu .= '<tr>
                        <th class="w5">N°</th>
                        <th class="w10">Traitement</th>
                        <th class="w15">Test 1</th>
                        <th class="w15">Test 2</th>
                        <th class="w20">Produit</th>
                        <th class="w10">Date</th>
                        <th class="w10">Corps</th>
                        <th class="w15">Opérateur</th>
                    </tr>';

        foreach ($listeLomas as $loma) {

            $froid = $froidManager->getFroid($loma->getId_froid());
            if (!$froid instanceof Froid) {
                $froid = new Froid([]);
            } // Gestion des erreurs

            $testAvantCss = $froid->getTest_avant_fe() + $froid->getTest_avant_nfe() + $froid->getTest_avant_inox() == 3 ? '' : 'text-danger';
            $testApresCss = $froid->getTest_apres_fe() + $froid->getTest_apres_nfe() + $froid->getTest_apres_inox() == 3 ? '' : 'text-danger';

            $testAvant = $froid->getTest_avant_fe() + $froid->getTest_avant_nfe() + $froid->getTest_avant_inox() . '/3 détectés';
            $testApres = $froid->getTest_apres_fe() + $froid->getTest_apres_nfe() + $froid->getTest_apres_inox() . '/3 détectés';

            if ($froid->getTest_avant_fe() + $froid->getTest_avant_nfe() + $froid->getTest_avant_inox() < 0) {
                $testAvant = '';
            }
            if ($froid->getTest_apres_fe() + $froid->getTest_apres_nfe() + $froid->getTest_apres_inox() < 0) {
                $testApres = '';
            }

            $lomaPdt        = $loma->getTest_pdt()  == 0 ? 'Aucun'       : 'Détecté';
            $lomaPdtCss     = $loma->getTest_pdt() == 1 ? 'text-danger' : '';
            $lomaComm       = trim($loma->getCommentaire()) != '' ? true : false;
            $lomaCommCss    = $lomaComm ? 'no-bb' : '';

            $contenu .= '<tr>
                            <td class="w5 ' . $lomaCommCss . '">L' .  sprintf("%03d", $loma->getId()) . '</td>
                            <td class="w10 ' . $lomaCommCss . '">' . strtoupper($loma->getCode_froid()) . sprintf("%04d", $loma->getId_froid()) . '</td>
                            <td class="w15 ' . $lomaCommCss . ' ' . $testAvantCss . '">' . $testAvant . '</td>
                            <td class="w15 ' . $lomaCommCss . ' ' . $testApresCss . '">' . $testApres . '</td>
                            <td class="w20 ' . $lomaCommCss . '">' . $loma->getNom_produit() . '</td>
                            <td class="w10 ' . $lomaCommCss . '">' . date('d/m/Y H:i', strtotime($loma->getDate_test())) . '</td>
                            <td class="w10 ' . $lomaCommCss . ' ' . $lomaPdtCss . '">' . $lomaPdt . '</td>
                            <td class="w15 ' . $lomaCommCss . '">' . $loma->getNom_user() . '</td>

                        </tr>';

            if ($lomaComm) {
                $contenu .= '<tr>
                                <td colspan="9"><p><i>' . strip_tags(htmlspecialchars($loma->getCommentaire())) . '</i></p></td>
                            </tr>';
            } // FIN test commentaires
        } // FIN boucle sur les lomas
    } // FIN test lomas

    $contenu .= '</table>';


    // EMBALLAGES

    // Préparation des variables
    $consommablesManager = new ConsommablesManager($cnx);
    $params                 = [];
    $params['id_lot']         = $lot->getId();
    $listEmbslot = $consommablesManager->getListeEmballagesByLot($params);


    // Génération du contenu HTML
    $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Emballages</th></tr></table>';
    $contenu .= '<table class="table table-liste w100 mt-10">';


    // On boucle sur les produits du lot
    $froidManager = new FroidManager($cnx);
    $params['id_lot'] = $lot->getId();
    $listePdtsLot = $froidManager->getListeLotProduits($params);
    if (empty($listePdtsLot)) {
        $contenu .= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun produit associé à emballages</i></td></tr>';
    } else {

        $contenuEmballagesHeader = '<tr>
                        <th class="w30">Produit</th>
                        <th class="w25">Famille</th>
                        <th class="w20">Fournisseur</th>
                        <th class="w15">Numlot</th>
                        <th class="w10 text-center">Déféctueux</th>
                    </tr>';


        // Boucle sur les produits pour emballage associé
        $ids_pdts = [];
        $contenuEmballages = '';
        foreach ($listePdtsLot as $froidPdt) {

            $pdt4emb = $froidPdt->getProduit();

            // On ne prends qu'une fois chaque produit
            if (in_array($pdt4emb->getId(), $ids_pdts)) {
                continue;
            }

            // On affiche les emballages pour ce produit
            $params = [];
            $params['id_lot']             = $lot->getId();
            $params['id_produit']         = $pdt4emb->getId();
            $listEmbslot = $consommablesManager->getListeEmballagesByLot($params);
            if (empty($listEmbslot)) {
                $ids_pdts[] = $pdt4emb->getId();
                continue;
            }

            foreach ($listEmbslot as $emb) {

                $contenuEmballages .= '<tr>
                        <th class="w30">' . $pdt4emb->getNom() . '</th>
                        <th class="w25">' . $emb->getNom_famille() . '</th>
                        <th class="w20">' . $emb->getNom_frs() . '</th>
                        <th class="w15">' . $emb->getNumlot_frs() . '</th>
                        <th class="w10 text-center">' . count($emb->getDefectueux()) . '</th>
                    </tr>';
            } // FIN boucle sur les produits


            // On intègre le produit dans l'array des produits déja traités
            $ids_pdts[] = $pdt4emb->getId();
        } // FIN boucle sur les produits pour emballage

        if (strlen($contenuEmballages) > 0) {
            $contenu .= $contenuEmballagesHeader . $contenuEmballages;
        } else {
            $contenu .= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun emballage associé</i></td></tr>';
        }
    } // FIN test produits
    $contenu .= '</table>';




    // NETTOYAGE
    $pvPendantManager       = new PvisuPendantManager($cnx);
    $pvPendants              = $pvPendantManager->getPvisuPendantByLot($lot);


    if (!$pvPendants && empty($pvPendants)) {
        //if (!$pvAvant && !$pvPendant && !$pvApres) {

        $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Vérification propreté visuelle</th></tr></table>';
        $contenu .= '<p class="text-center gris-9 text-11 mt-10"><i>Aucun contrôle effectué pour ce lot</i></p>';
    } else { // FIN aucun Pvisu - on a au moins un pvisu

        $contenu .= '';

        $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Vérification propreté visuelle pendant la production</th></tr></table>';

        // Pvisu PENDANT
        foreach ($pvPendants as $pvPendant) {
            if ($pvPendant instanceof PvisuPendant) {
                $visuPoints = $pvPendantManager->getListePvisuPendantPoints($pvPendant);

                $contenu .= '<table class="table table-liste w100 mt-10">';
                $contenu .= '<tr><td class="w50">Date du contrôle : ' . Outils::getDate_only_verbose($pvPendant->getDate(), true) . '</td>';
                $contenu .= '<td class="w50 text-right">Responsable du contrôle : ' . $pvPendant->getNom_user() . '</td></tr>';
                $contenu .= '</table>';

                $contenu .= '<table class="table table-liste w100 mt-10">';


                foreach ($visuPoints as $point) {
                    $fichenc = (int)$point->getFiche_nc() == 1 ? 'Fiche NC' : '';
                    $etatTxt = (int)$point->getEtat() == 1 ? 'Satisfaisant' : 'Non-satisfaisant';


                    $contenu .= '
                                <tr>
                                    <td style="width: 60%">' . $point->getNom() . '</td>
                                    <td style="width: 15%">' . $etatTxt . '</td>
                                    <td style="width: 15%">' . $point->getNom_action() . '</td>
                                    <td style="width: 10%">' . $fichenc . '</td>
                                </tr>';
                } // FIN boucle points


                $contenu .= '</table>';

                if (trim($pvPendant->getCommentaires()) != '') {

                    $contenu .= '<p class="text-9 mt-5"><span class="gris-7">Commentaires :</span><br>' . $pvPendant->getCommentaires() . '</p>';
                }
            } // FIN Pvisu APRES
            else {
                $contenu .= '<p class="text-center gris-9 text-11 mt-10"><i>Aucun contrôle</i></p>';
            }
        }
    } // FIN test aucun Pvisu




    // COMMENTAIRES

    $commentairesManager = new CommentairesManager($cnx);
    $params = [
        'id_lot'      => $lot->getId(),
        'id_froids'   => $id_froids
    ];
    $listeCom = $commentairesManager->getListeCommentaires($params);

    // Génération du contenu HTML
    $contenu .= '<table class="table w100 mt-15"><tr><th class="w100 titre">Commentaires</th></tr></table>';
    $contenu .= '<table class="table table-liste w100 mt-10">';

    // Aucun commentaires
    if (empty($listeCom)) {

        $contenu .= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun commentaire sur ce lot ou ses traitements</i></td></tr>';

        // Liste des commentaires
    } else {

        $contenu .= '<tr>
                        <th class="w75">Commentaire</th>
                        <th class="w10 text-center">Date</th>
                        <th class="w15 text-right">Auteur</th>
                    </tr>';

        $userManager = new UserManager($cnx);

        foreach ($listeCom as $com) {

            $userCom = $userManager->getUser($com->getId_user());
            $nomUser_com = $userCom instanceof User ? $userCom->getNomComplet() : '';


            $contenu .= '<tr>
                            <td class="w75">' . nl2br(strip_tags($com->getCommentaire())) . '</td>
                            <td class="w10 text-center">' . Outils::getDate_verbose($com->getDate()) . '</td>
                            <td class="w15 text-right">' . $nomUser_com . '</td>
                        </tr>';
        } // FIN boucle sur les commentaires

    } // FIN test commentaires

    $contenu .= '</table>';


    // FOOTER
    $contenu .= '<table class="w100 gris-9">
                    <tr>
                        <td class="w50 text-8">Document édité le ' . date('d/m/Y') . ' à ' . date('H:i:s') . '</td>
                        <td class="w50 text-right text-6">&copy; 2019 IPREX / INTERSED </td>
                    </tr>
                </table>
            </body>
        </html>';


    $contenu = str_replace('Œ', 'OE', $contenu);

    // RETOUR CONTENU
    return $contenu;
} // FIN fonction déportée


/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le header du PDF (logo, n° de lot...)
-----------------------------------------------------------------------------*/
function genereEntetePagePdf(Lot $lot)
{

    global $cnx;
    $lotsManager = new LotManager($cnx);

    $quantiemes = $lotsManager->getLotQuantiemes($lot);

    if (count($quantiemes) == 1) {
        $numlot = $lot->getNumlot() . $quantiemes[0];
    } else {
        $numlot = $lot->getNumlot();
    }

    $detailsQuantiemes = '';
    if (count($quantiemes) > 1) {
        $detailsQuantiemes .= '<br><span class="text-9 ml-15">Quantièmes : ';
        foreach ($quantiemes as $quantieme) {
            $detailsQuantiemes .= '<span class="text-10"><b>' . $quantieme . '</b></span> - ';
        }
        $detailsQuantiemes = substr($detailsQuantiemes, 0, -3);
        $detailsQuantiemes .= '</span>';
    }

    $entete = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="' .__CBO_ROOT_PATH__. 'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-12">LOT</span> <span class="text-16"><b>' . $numlot . '</b>' . $detailsQuantiemes . '</span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-12 gris-7">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>';

    return $entete;
} // FIN fonction déportée


/* ----------------------------------------------------------------------------
MODE - Change la visibilité d'un lot (swith admin)
-----------------------------------------------------------------------------*/
function modeChangeVisibilite()
{

    global $lotsManager;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
    $visible = isset($_REQUEST['visible']) ? intval($_REQUEST['visible']) : -1;
    if ($id_lot == 0 || $visible < 0 || $visible > 1) {
        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        exit;
    }

    $lot->setVisible($visible);
    echo $lotsManager->saveLot($lot) ? 1 : 0;
    exit;
} // FIN mode

/* ----------------------------------------------------------------------------
MODE - Change la traçabilité d'un lot (swith admin)
-----------------------------------------------------------------------------*/
function modeChangeTracabilite()
{

    global $lotsManager;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
    $tracabilite = isset($_REQUEST['tracabilite']) ? intval($_REQUEST['tracabilite']) : -1;
    if ($id_lot == 0 || $tracabilite < 0 || $tracabilite > 1) {
        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        exit;
    }

    $lot->setTest_tracabilite($tracabilite > 0 ? date('Y-m-d H:i:s') : null);
    echo $lotsManager->saveLot($lot) ? 1 : 0;
    exit;
} // FIN mode



/* ----------------------------------------------------------------------------
MODE - Réouvre un lot terminé
-----------------------------------------------------------------------------*/
function modeReouvrirLotTermine()
{

    global $lotsManager, $cnx;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
    if ($id_lot == 0) {
        exit;
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        exit;
    }

    $log = new Log([]);

    if ($lotsManager->reouvreLot($lot)) {
        $codeVue = $lot->getReception() instanceof LotReception ? 'atl' : 'rcp';
        $lotsManager->addLotVue($lot, $codeVue);
        $log->setLog_type('info');
        $texteLog = 'Réouverture du lot terminé ' . $lot->getNumlot() . ', sortie initiale : ' . $lot->getDate_out();
    } else {
        $log->setLog_type('warning');
        $texteLog = 'ECHEC de réouverture du lot terminé ' . $lot->getNumlot();
    }
    $log->setLog_texte($texteLog);
    $logsManager = new LogManager($cnx);
    $logsManager->saveLog($log);
    exit;
} // FIN mode

/* ----------------------------------------------------------------------------
MODE - Supprime un incident
-----------------------------------------------------------------------------*/
function modeSupprIncident()
{

    global $cnx;

    $id_incident = isset($_REQUEST['id_incident']) ? intval($_REQUEST['id_incident']) : 0;

    if ($id_incident == 0) {
        exit;
    }

    $incidentsManager = new IncidentsManager($cnx);
    $incident = $incidentsManager->getIncident($id_incident);

    echo $incidentsManager->supprimeIncident($incident) ? '1' : '0';
    exit;
} // FIN mode


/* ----------------------------------------------------------------------------
MODE - Modale commentaires du lot (front) pour les incidents du lot
-----------------------------------------------------------------------------*/
function modeModalCommentairesFront()
{

    global $cnx;

    $id_lot = isset($_REQUEST['id_lot']) ? $_REQUEST['id_lot'] : 0;
    $negoce = 0;
    if (substr($id_lot, 0, 1) == 'N') {
        $id_lot = str_replace('N', '', $id_lot);
        $negoce = 1;
    }

    if ($id_lot == 0) {
        echo "ERREUR !<br>Identification du lot impossible !<br>Code erreur : XJMYKCKW";
        exit;
    }

    $commentairesManager = new CommentairesManager($cnx);

    ?>
    <input type="hidden" name="id_lot_cominc" value="<?php echo $_REQUEST['id_lot']; ?>" />
    <?php
    $params = [
        'id_lot'   => $id_lot,
        'negoce' => $negoce,
        'incident' => true
    ];

    $coms = $commentairesManager->getListeCommentaires($params);

    if (empty($coms)) { ?>
        <div class="alert alert-secondary text-center padding-20">Aucune information complémentaire disponible...</div>
    <?php
        exit;
    } ?>


    <?php
    $c = 0;
    foreach ($coms as $com) {
        $c++;
    ?>
        <div class="alert alert-secondary padding-20 <?php echo $c == count($coms) ? 'mb-0' : ''; ?>">
            <p class="text-12 texte-fin gris-7 mb-1"><?php echo Outils::getDate_verbose($com->getDate(), false, ' à '); ?></p>
            <p class="nomargin"><?php echo $com->getCommentaire(); ?></p>
        </div>
    <?php }

    exit;
} // FIN mode


/* ----------------------------------------------------------------------------
MODE - Modale commentaires du lot (front) pour les incidents du lot : AJOUT
-----------------------------------------------------------------------------*/
function modeModalCommentairesFrontAdd()
{

    global $cnx;

    $id_lot = isset($_REQUEST['id_lot']) ? $_REQUEST['id_lot'] : 0;
    if ($id_lot === 0) {
        echo "ERREUR !<br>Identification du lot impossible !<br>Code erreur : QZTPLBMU";
        exit;
    }

    ?>
    <input type="hidden" name="mode" value="addCommentaireFront" />
    <input type="hidden" name="id_lot" value="<?php echo $id_lot; ?>" />
    <div class="row">
        <div class="col text-center mb-3 gris-5 text-18">
            Nouveau commentaire sur le lot :
        </div>
    </div>
    <div class="row">
        <div class="col">
            <textarea class="form-control" placeholder="Commentaire obligatoire..." id="champ_clavier" name="incident_commentaire"></textarea>
        </div>
    </div>
<?php

    exit;
} // FIN mode


/* ---------------------------------------------------------------------------------
MODE - Modale commentaires du lot (front) pour les incidents du lot : AJOUT SAVE !
----------------------------------------------------------------------------------*/
function modeAddCommentaireFront()
{

    global $cnx, $utilisateur;

    $id_lot = isset($_REQUEST['id_lot']) ? $_REQUEST['id_lot'] : 0;
    $incident_commentaire = isset($_REQUEST['incident_commentaire']) ? trim($_REQUEST['incident_commentaire'])    : '';

    if ($id_lot === 0) {
        echo '-1';
        exit;
    }
    if (strlen($incident_commentaire) == 0) {
        echo '-2';
        exit;
    }

    $commentairesManager = new CommentairesManager($cnx);

    $negoce = 0;
    if (substr($id_lot, 0, 1) == 'N') {
        $id_lot = str_replace('N', '', $id_lot);
        $negoce = 1;
    }

    $com = new Commentaire([]);
    $com->setDate(date('Y-m-d H:i:s'));
    $com->setId_lot($id_lot);
    $com->setNegoce($negoce);
    $com->setIncident(1);
    $com->setId_user($utilisateur->getId());
    $com->setCommentaire($incident_commentaire);

    echo $commentairesManager->saveCommentaire($com) != false ? 1 : 0;
    exit;
} // FIN mode


/* ---------------------------------------------------------------------------------
MODE - Active/désactive un type d'incident (BO)
----------------------------------------------------------------------------------*/
function modeActivationTypeIncident()
{

    global $cnx;

    $incidentManager = new IncidentsManager($cnx);

    $valeur     = isset($_REQUEST['valeur'])     ? intval($_REQUEST['valeur'])         : -1;
    $id_type     = isset($_REQUEST['id_type'])     ? intval($_REQUEST['id_type'])         : 0;

    if ($id_type == 0 || $valeur < 0) {
        exit;
    }

    $incidentType = $incidentManager->getTypeIncident($id_type);
    if (!$incidentType instanceof IncidentType) {
        exit;
    }

    $incidentType->setActif($valeur);
    echo $incidentManager->saveTypeIncident($incidentType) ? 1 : 0;
    exit;
} // FIN mode

/* ---------------------------------------------------------------------------------
FONCTION - Vérifie la cohérence entre le poids produit et palette d'un produit
----------------------------------------------------------------------------------*/
function checkCoherencePoids()
{

    global  $cnx;

    $produitsManager = new ProduitManager($cnx);

    return $produitsManager->cleanCoherencePoids();
} // FIN fonction

/* ---------------------------------------------------------------------------------
FONCTION DEPORTEE - Génère et place le fichier du lot pour Bizerba
----------------------------------------------------------------------------------*/
function envoiLotBizerba($lot, $abattoir, $decale = false)
{

    global $lotsManager, $logsManager, $cnx;

    if (!$lot instanceof Lot || !$abattoir instanceof Abattoir) {
        return false;
    }

    // On récupère la configuration...
    $configManager = new ConfigManager($cnx);
    $config_bizerba_actif = $configManager->getConfig('biz_actif');

    // Si désactivé, on retourne true (false génèrerai un message d'erreur)...
    if (!$config_bizerba_actif instanceof Config || intval($config_bizerba_actif->getValeur()) != 1) {
        return true;
    }

    $envoiOk = Outils::envoiLotBizerba($lot, $abattoir, $decale);


    // On teste si le fichier a bien été créé et qu'il n'est pas vide
    if ($envoiOk) {

        // Si c'est bon (#vasyfrancky) on enregistre l'info en BDD
        $lot->setBizerba(date('Y-m-d H:i:s'));
        $lotsManager->saveLot($lot);

        $log = new Log([]);
        $log->setLog_type('success');
        $log->setLog_texte('Envoi du lot ' . $lot->getNumlot() . ' vers Bizerba OK');
        $logsManager->saveLog($log);
    } else {
        $log = new Log([]);
        $log->setLog_type('danger');
        $log->setLog_texte('Echec de l\'envoi du lot ' . $lot->getNumlot() . ' vers Bizerba !');
        $logsManager->saveLog($log);
        return false;
    }

    return true;
} // FIN fonction

/* ---------------------------------------------------------------------------------
MODE - Envoi manuel d'un lot à Bizerba
----------------------------------------------------------------------------------*/
function modeEnvoiLotBizerna()
{

    global $lotsManager, $cnx;

    $id_lot = isset($_REQUEST['id_lot']) ? intval($_REQUEST['id_lot']) : 0;
    if ($id_lot == 0) {
        exit('LLAGG1LB');
    }

    $lot = $lotsManager->getLot($id_lot);
    if (!$lot instanceof Lot) {
        exit('4WMC12C0');
    }

    $abattoirManager = new AbattoirManager($cnx);
    $abattoir = $abattoirManager->getAbattoir($lot->getId_abattoir());

    if (!$abattoir instanceof Abattoir) {
        exit('ZY9Q8L6B');
    }

    if (!envoiLotBizerba($lot, $abattoir)) {
        exit('6F944H5B');
    }

    echo '1';
    exit;
} // FIN mode


/* ---------------------------------------------------------------------------------
MODE - Crée un nouveau lot de regroupement
----------------------------------------------------------------------------------*/
function modeAddNewLotRegroupement()
{

    global $lotsManager, $logsManager, $utilisateur;

    $numlot = isset($_REQUEST['numlot']) ? trim(strtoupper($_REQUEST['numlot'])) : '';
    if ($numlot == '') {
        exit;
    }

    $lotR = new LotRegroupement([]);
    $lotR->setSupprime(0);
    $lotR->setDate_add(date('Y-m-d H:i:s'));
    $lotR->setUser_id($utilisateur->getId());
    $lotR->setStatut(0);
    $lotR->setNumlot($numlot);

    $id_lot_r = $lotsManager->saveLotRegroupement($lotR);

    $log = new Log([]);
    if ($id_lot_r) {
        $log->setLog_type('success');
        $log->setLog_texte('Création du lot de regroupement ' . $numlot);
    } else {
        $log->setLog_type('danger');
        $log->setLog_texte('ECHEC lors de la création du lot de regroupement ' . $numlot);
    }
    $logsManager->saveLog($log);

    echo intval($id_lot_r);
    exit;
} // FIN mode

/* ---------------------------------------------------------------------------------
MODE - Retire l'association de compositions à un lot de regroupement
----------------------------------------------------------------------------------*/
function modeSupprAssociationComposLotR()
{

    global $lotsManager, $cnx;

    $ids_compos = isset($_REQUEST['ids_compos']) ? explode(',', $_REQUEST['ids_compos']) : [];

    if (empty($ids_compos)) {
        exit;
    }

    $paletteManager = new PalettesManager($cnx);

    foreach ($ids_compos as $id_compo) {

        $compo = $paletteManager->getComposition($id_compo);
        if (!$compo instanceof PaletteComposition) {
            exit;
        }

        $compo->setId_lot_regroupement(0);
        if (!$paletteManager->savePaletteComposition($compo)) {
            exit;
        }
    }

    // Ok, maintenant si le lot de regroupement est vide, on le supprime
    $lotsManager->supprimeLotsRegroupementsVides();
    exit;
} // FIN mode

/* ---------------------------------------------------------------------------------
MODE - Vérifie l'existance d'un numéro de lot (BL manuel)
----------------------------------------------------------------------------------*/
function modeCheckNumLotExiste()
{
    global $lotsManager, $cnx;

    $numlot = isset($_REQUEST['numlot']) ? trim($_REQUEST['numlot']) : '';
    if ($numlot == '') {
        exit;
    }

    // Si les 3 derniers caractèrs sont des chiffres (quantièmes), on les supprime
    if (preg_match('/^[0-9]*$/', (substr($numlot, -3)))) {
        $numlot = substr($numlot, 0, -3);
    }

    echo $lotsManager->checkLotExiste($numlot) ? 1 : 0;
    exit;
} // FIN mode