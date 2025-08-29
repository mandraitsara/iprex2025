<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax FOURNISSEURS
------------------------------------------------------*/
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$tiersManager = new TiersManager($cnx);
$logsManager = new LogManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------
MODE - Modale Fournisseur (admin)
------------------------------------*/
function modeModalFournisseur() {

    global
	    $cnx,
	    $utilisateur,
	    $tiersManager;

    // On vérifie qu'on est bien loggé
    if (!isset($_SESSION['logged_user'])) { exit;}

	$fournisseur_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$fournisseur    = $fournisseur_id > 0 ? $tiersManager->getTiers($fournisseur_id) : new Tiers([]);

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) {
		$utilisateur = unserialize($_SESSION['logged_user']);
    }


	// Retour Titre
	echo '<i class="fa fa-industry"></i>';
	echo $fournisseur_id > 0 ? $fournisseur->getNom() : "Nouveau fournisseur&hellip;";

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>

	<form class="container-fluid" id="formFournisseurAddUpd">
        <input type="hidden" name="mode" value="saveFournisseur"/>
        <input type="hidden" name="fournisseur_id" id="input_id" value="<?php echo $fournisseur_id; ?>"/>
		<div class="row">
			<div class="col-12 input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">Nom</span>
                </div>
                <input type="text" class="form-control" placeholder="Nom ou raison sociale" name="nom" id="input_nom" value="<?php echo $fournisseur->getNom(); ?>">

            </div>
        </div>
        <div class="row">
            <div class="col-12 col-lg-4 input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-landmark gris-9"></i></span>
                </div>
                <input type="text" class="form-control" placeholder="TVA Intracommunaitaire" name="tva_intra" id="input_tva_intra" value="<?php echo $fournisseur->getTva_intra(); ?>">
            </div>


            <div class="col-12 col-lg-4 input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-flag gris-9"></i></span>
                </div>
                <select class="selectpicker form-control show-tick" name="famille" id="input_famille" title="Famille">
					<?php
					foreach ($tiersManager->getTiersFamillesListe() as $famille) { ?>
                        <option value="<?php echo $famille->getId(); ?>" <?php
						echo $fournisseur->getId_famille() == $famille->getId() ? ' selected ' : '';
						?>><?php echo $famille->getNom(); ?></option>
					<?php } // FIN boucle pays frs
					?>
                </select>
            </div>   
		
        </div>
        <div class="row">
            <div class="col-12 input-group mt-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">Agrément</span>
                </div>
                <input type="text" class="form-control" placeholder="Numéro d'agrément" name="numagr" id="numagr" value="<?php echo $fournisseur->getNumagr() ;?>">                
            </div>
        </div>
            <div class="col-12 col-lg-4 text-right">
                <input type="checkbox" class="togglemaster" data-toggle="toggle" name="activation"
					<?php echo $fournisseur->isActif() ? 'checked' : ''; ?>
                       data-on="Activé"
                       data-off="Désactivé"
                       data-onstyle="success"
                       data-offstyle="danger"
                />
            </div>
		</div>
        <?php
        		// Si nouveau fournisseur, on propose la création d'un premier contact + des adresses
				if ($fournisseur->getId() == 0) { ?>

                <div class="row mt-3">
                    <div class="col">
                        <div class="alert alert-secondary">
                            <div class="row">
                                <div class="col-12">
                                    <p class="small mb-1"><i class="fa fa-map-marked-alt mr-1 gris-9"></i> Adresse principale</p><hr class="mt-0">
                                </div>
                                <div class="col-12 mb-2">
                                    <input type="text" name="adr_nom" class="form-control" placeholder="Raison sociale (si différente)" value=""/>
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="text" name="adr_adresse_1" class="form-control" placeholder="Adresse" value=""/>
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="text" name="adr_adresse_2" class="form-control" placeholder="Complément d'adresse" value=""/>
                                </div>
                                <div class="col-3 mb-2">
                                    <input type="text" name="adr_cp" class="form-control" placeholder="Code postal" value=""/>
                                </div>
                                <div class="col-5 mb-2">
                                    <input type="text" name="adr_ville" class="form-control" placeholder="Ville" value=""/>
                                </div>
                                <div class="col-4 mb-2">
                                    <select class="selectpicker form-control" title="Pays" name="adr_id_pays">
                                        <?php
                                        $paysManager = new PaysManager($cnx);
                                        foreach ($paysManager->getListePays() as $pays) { ?>
                                            <option value="<?php echo $pays->getId(); ?>"><?php echo $pays->getNom(); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col">
                        <div class="alert alert-secondary">
                            <div class="row">
                                <div class="col-12">
                                    <p class="small mb-1"><i class="fa fa-user mr-1 gris-9"></i> Contact principal</p><hr class="mt-0">
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="text" name="ctc_nom" class="form-control" placeholder="Nom" value=""/>
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="text" name="ctc_prenom" class="form-control" placeholder="Prénom" value=""/>
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="text" name="ctc_tel" class="form-control" placeholder="Téléphone" value=""/>
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="text" name="ctc_mobile" class="form-control" placeholder="Mobile" value=""/>
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="text" name="ctc_fax" class="form-control" placeholder="Fax" value=""/>
                                </div>
                                <div class="col-12 mb-2">
                                    <input type="text" name="ctc_mail" class="form-control" placeholder="Adresse e-mail" value=""/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
		<?php } // FIN test nouveau fournisseur ?>

	</form>
    <div class="row mt-2">
        <div class="col doublon d-none">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle mr-1"></i> Un autre fournisseur porte déjà ce nom !
            </div>
        </div>
    </div>

	<?php
	// Séparateur Body/Footer pour le callback aJax
	echo '^';

	// Retour boutons footer si fournisseur existant (bouton supprimer)
    if ($fournisseur_id > 0) {
	?>
	    <button type="button" class="btn btn-danger btn-sm btnSupprimeFournisseur">
            <i class="fa fa-times fa-lg vmiddle mr-1"></i> Supprimer
	    </button>
	<?php
    } // FIN test édition fournisseur existant
	exit;
} // FIN mode


/* ------------------------------------
MODE - Enregistre un fournisseur (add/upd)
------------------------------------*/
function modeSaveFournisseur() {

    global
        $cnx,
        $tiersManager;

	// Vérification des données
	$fournisseur_id  = isset($_REQUEST['fournisseur_id'])   ? intval($_REQUEST['fournisseur_id'])      : 0;
	$nom             = isset($_REQUEST['nom'])              ? strtoupper(trim($_REQUEST['nom']))    : '';
	$activation      = isset($_REQUEST['activation'])       ? 1 : 0;
	$famille         = isset($_REQUEST['famille'])          ? intval($_REQUEST['famille']) : 0;
	$numagr         = isset($_REQUEST['numagr'])          ? strtoupper(trim($_REQUEST['numagr'])) : '';

	// Coordonées

	$telephone      = isset($_REQUEST['telephone'])     ? trim(strip_tags($_REQUEST['telephone']))  : '';

	$tva_intra      = isset($_REQUEST['tva_intra'])     ? trim(strip_tags($_REQUEST['tva_intra']))  : '';

	$adr_nom = isset($_REQUEST['adr_nom']) ?  trim(strip_tags($_REQUEST['adr_nom']))  : '';
	$adr_adresse_1 = isset($_REQUEST['adr_adresse_1']) ?  trim(strip_tags($_REQUEST['adr_adresse_1']))  : '';
	$adr_adresse_2 = isset($_REQUEST['adr_adresse_2']) ?  trim(strip_tags($_REQUEST['adr_adresse_2']))  : '';
	$adr_cp = isset($_REQUEST['adr_cp']) ?  trim(strip_tags($_REQUEST['adr_cp']))  : '';
	$adr_ville = isset($_REQUEST['adr_ville']) ?  trim(strip_tags($_REQUEST['adr_ville']))  : '';
	$adr_id_pays = isset($_REQUEST['adr_id_pays']) ?  intval($_REQUEST['adr_id_pays'])  : 0;

	$ctc_nom = isset($_REQUEST['ctc_nom']) ?  trim(strip_tags($_REQUEST['ctc_nom']))  : '';
	$ctc_prenom = isset($_REQUEST['ctc_prenom']) ?  trim(strip_tags($_REQUEST['ctc_prenom']))  : '';
	$ctc_tel = isset($_REQUEST['ctc_tel']) ?  trim(strip_tags($_REQUEST['ctc_tel']))  : '';
	$ctc_mobile = isset($_REQUEST['ctc_mobile']) ?  trim(strip_tags($_REQUEST['ctc_mobile']))  : '';
	$ctc_fax = isset($_REQUEST['ctc_fax']) ?  trim(strip_tags($_REQUEST['ctc_fax']))  : '';
	$ctc_mail = isset($_REQUEST['ctc_mail']) ?  trim(strip_tags($_REQUEST['ctc_mail']))  : '';

	$ctc_fax        = str_replace('#', '+', $ctc_fax);
	$ctc_tel        = str_replace('#', '+', $ctc_tel);
	$ctc_mobile     = str_replace('#', '+', $ctc_mobile);


	$nom = htmlspecialchars(str_replace('#ET#', '&', $nom));

	// Si pas de prénom, de nom, de profil ou de code, on ne vas pas plus loin...
	if ($nom == '') {
		echo '-1';
		exit;
	} // FIN test champs requis

	// Instanciation de l'objet TIERS (hydraté ou vide)
	$tiers = $fournisseur_id > 0 ? $tiersManager->getTiers($fournisseur_id) : new Tiers([]);

	// mise à jour des champs de base

	$tiers->setNom($nom);
	$tiers->setDate_maj(date('Y-m-d H:i:s'));
	$tiers->setActif($activation);

	$tiers->setType(2); // Type de tiers = fournisseur
    $tiers->setId_famille($famille);
	$tiers->setTva_intra($tva_intra);

    //ajout de numéro agrément

    $tiers->setNumagr($numagr);


	// Si création, on enregistre la date
	if ($fournisseur_id == 0) {
		$tiers->setDate_add(date('Y-m-d H:i:s'));
	}

    // Enregistrement et retour pour callBack ajax
    $retour = $tiersManager->saveTiers($tiers);

    // Logs
    $logsManager = new LogManager($cnx);
    $log = new Log([]);
    if ($retour) {
		$log->setLog_type('info');
		if ($fournisseur_id == 0) {
		    $log->setLog_texte("Création d'un nouveau fournisseur : " . $tiers->getNom());
        } else {
			$log->setLog_texte("Mise à jour des informations du tiers (fournisseur) #" . (int)$fournisseur_id);
        }
    } else {
		$log->setLog_type('danger');
		if ($fournisseur_id == 0) {
			$log->setLog_texte("ERREUR lors de la création d'un nouveau fournisseur : " . $nom);
		} else {
			$log->setLog_texte("ERREUR lors de la mise à jour des informations de tiers (fournisseur) #" . (int)$fournisseur_id);
		}
    } // FIN test retour création/maj pour log

	$logsManager->saveLog($log);

	// Si création du premier contact et des adresses (nouveau client)
	if (intval($retour) > 1 && $fournisseur_id == 0) {

		// SI contact
		if ($ctc_nom != '' || $ctc_prenom != '') {

			$ctc = new Contact([]);
			$ctc->setSupprime(0);
			$ctc->setId_tiers(intval($retour));
			$ctc->setNom($ctc_nom);
			$ctc->setPrenom($ctc_prenom);
			$ctc->setTelephone($ctc_tel);
			$ctc->setMobile($ctc_mobile);
			$ctc->setFax($ctc_fax);
			$ctc->setEmail($ctc_mail);
			$ctc->setDate_add(date('y-m-d h:i:s'));

			$contactsManager = new ContactManager($cnx);
			$retour_ctc = $contactsManager->saveContact($ctc);

			$logCtc = new Log([]);
			$logCtc_type = $retour_ctc ? 'info' : 'danger';
			$logCtc_texte = $retour_ctc ? 'Création du premier contact du fournisseur #'.intval($retour) : 'ERREUR lors de la création du premier contact du nouveau fournisseur '.$tiers->getNom();
			$logCtc->setLog_type($logCtc_type);
			$logCtc->setLog_texte($logCtc_texte);
			$logsManager->saveLog($logCtc);

		} // FIN test contact

		// Si adresse de livraison
		if ($adr_adresse_1 != '') {

			$adressesManager = new AdresseManager($cnx);

			$adresse_fact = new Adresse([]);
			$adresse_fact->setId_tiers(intval($retour));
			$adresse_fact->setNom($adr_nom);
			$adresse_fact->setSupprime(0);
			$adresse_fact->setAdresse_1($adr_adresse_1);
			$adresse_fact->setAdresse_2($adr_adresse_2);
			$adresse_fact->setCp($adr_cp);
			$adresse_fact->setVille($adr_ville);
			$adresse_fact->setId_pays($adr_id_pays);
			$adresse_fact->setType(0);

			$retour_adr_fact = $adressesManager->saveAdresse($adresse_fact);
			$logAdrFact = new Log([]);
			$logAdrFact_type = $retour_adr_fact ? 'info' : 'danger';
			$logAdrFact_texte = $retour_adr_fact
				? 'Création de la première adresse de facturation du fournisseur #'.intval($retour)
				: 'ERREUR lors de la création de la première adresse de facturation du nouveau fournisseur '.$tiers->getNom();
			$logAdrFact->setLog_type($logAdrFact_type);
			$logAdrFact->setLog_texte($logAdrFact_texte);
			$logsManager->saveLog($logAdrFact);

		} // FIN test adresse


	} // FIN test création nouveau client

	echo $retour !== false ? '1' : '0';
	exit;
} // FIN mode


/* ------------------------------------
MODE - Affiche la liste des fournisseurs
------------------------------------*/
function modeShowListeFournisseurs() {

    global
	    $mode,
		$utilisateur,
        $tiersManager;

	// Préparation pagination (Ajax)
	$nbResultPpage      = 15;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode='.$mode;
	$start              = ($page-1) * $nbResultPpage;

	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;
	$params['show_inactifs'] 	= true;

	$filtre_recherche   =  isset($_REQUEST['filtre_recherche'])  ? trim($_REQUEST['filtre_recherche'])   : '';
	$filtre_pays        =  isset($_REQUEST['filtre_pays'])       ? intval($_REQUEST['filtre_pays'])        : 0;
	$filtre_actif       =  isset($_REQUEST['filtre_actif']) && $_REQUEST['filtre_actif'] != ''     ? intval($_REQUEST['filtre_actif'])     : -1;
	$filtre_famille     =  isset($_REQUEST['filtre_famille'])     ? intval($_REQUEST['filtre_famille'])     : 0;

	if ($filtre_recherche != '' ) { $params['recherche'] = $filtre_recherche;   $filtresPagination.= '&filtre_recherche='.$filtre_recherche; }
	if ($filtre_pays      > 0   ) { $params['id_pays'] = $filtre_pays;             $filtresPagination.= '&filtre_pays='.$filtre_pays;           }
	if ($filtre_actif     > -1  ) { $params['actif'] = $filtre_actif;           $filtresPagination.= '&filtre_actif='.$filtre_actif;         }
	if ($filtre_famille   >  0  ) { $params['famille'] = $filtre_famille;       $filtresPagination.= '&filtre_famille='.$filtre_famille;     }

	$listeFournisseurs = $tiersManager->getListeFournisseurs($params);

	// Si aucun fournisseur a afficher
	if (empty($listeFournisseurs)) { ?>

        <div class="alert alert-danger">
            <i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucun fournisseur !</strong>
        </div>

    <?php

	// Sinon, affichage de la liste des fournisseurs
	} else {

		// Liste non vide, construction de la pagination...
		$nbResults  = $tiersManager->getNb_results();
		$pagination = new Pagination($page);

		$pagination->setUrl($filtresPagination);
		$pagination->setNb_results($nbResults);
		$pagination->setAjax_function(true);
		$pagination->setNb_results_page($nbResultPpage);
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);
	    ?>

        <div class="alert alert-danger d-md-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-md-table">
            <thead>
                <tr>
				    <?php
                    // On affiche l'ID que si on est développeur
                    if ($utilisateur->isDev()) { ?><th class="w-court-admin-cell d-none d-xl-table-cell">ID</th><?php } ?>

                    <th>Nom</th>
                    <th>Famille</th>
                    <th>Pays</th>
                    <th>Contact</th>
                    <th class="text-center w-court-admin-cell">Actif</th>
                    <th class="t-actions w-mini-admin-cell">Adresses</th>
                    <th class="t-actions w-mini-admin-cell">Contacts</th>
                    <th class="t-actions w-mini-admin-cell">Détails</th>
                </tr>
            </thead>
            <tbody>
			    <?php
                // Boucle sur les fournisseurs
				foreach ($listeFournisseurs as $fournisseur) {
					?>
                    <tr>
						<?php
						// On affiche l'ID que si on est développeur
                        if ($utilisateur->isDev()) { ?>
                            <td class="w-court-admin-cell d-none d-xl-table-cell"><span class="badge badge-pill badge-warning"><?php echo $fournisseur->getId();?></span></td>
                        <?php } ?>
                        <td class="text-18"><?php echo $fournisseur->getNom() != '' ? $fournisseur->getNom() : '&mdash;'; ?></td>
                        <td><?php echo $fournisseur->getId_famille() != '' ? $fournisseur->getFamille() : '&mdash;'; ?></td>
                        <td><?php echo $fournisseur->getPays() != '' ? $fournisseur->getPays('<br>') : '&mdash;'; ?></td>
                        <td><?php echo $fournisseur->getContact() != '' ? $fournisseur->getContact('<br>') : '&mdash;';?></td>
                        <td class="text-center w-court-admin-cell"><i class="fa fa-fw fa-lg fa-<?php echo $fournisseur->isActif() ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i></td>
                        <td class="t-actions w-mini-admin-cell"><button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalAdresses" data-frs-id="<?php
							echo $fournisseur->getId(); ?>"><i class="fa fa-map-marker-alt fa-fw"></i> </button></td>
                        <td class="t-actions w-mini-admin-cell"><button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalContacts" data-frs-id="<?php
							echo $fournisseur->getId(); ?>"><i class="fa fa-user-friends fa-fw"></i> </button></td>
                        <td class="t-actions w-mini-admin-cell"><button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalFournisseur" data-frs-id="<?php
                            echo $fournisseur->getId(); ?>"><i class="fa fa-edit"></i> </button></td>
                        </tr>
                <?php
				} // FIN boucle fournisseurs ?>
            </tbody>
        </table>
	<?php

		// Pagination (aJax)
		if (isset($pagination)) {
			// Pagination bas de page, verbose...
			$pagination->setVerbose_pagination(1);
			$pagination->setVerbose_position('right');
			$pagination->setNature_resultats('fournisseur');
			$pagination->setNb_apres(2);
			$pagination->setNb_avant(2);

			echo ($pagination->getPaginationHtml());
		} // FIN test pagination

	} // FIN test fournisseurs à afficher
    exit;
} // FIN mode


/* ---------------------------------------
MODE - Supprime un fournisseur
---------------------------------------*/
function modeSupprFournisseur() {

    global
        $tiersManager,
        $logsManager;

    //  -> On ne supprime pas réellement pour les raisons de traçabilité, on passe le champ "supprime" à 1

    // On récupère l'ID du fournisseur, si il n'est pas clairement identifié, on ne va pas plus loin
    $id_fournisseur = isset($_REQUEST['id_fournisseur']) ? intval($_REQUEST['id_fournisseur']) : 0;
    if ($id_fournisseur == 0) { exit; }

    // Instanciation de l'objet Tiers
    $fournisseur = $tiersManager->getTiers($id_fournisseur);

    // On passe le statut à supprimé
	$fournisseur->setSupprime(1);

    // On désactive
	$fournisseur->setActif(0);

    // On enregistre la date de modification
	$fournisseur->setDate_maj(date('Y-m-d H:i:s'));

	// Si la mise à jour s'est bien passé en BDD
    if ($tiersManager->saveTiers($fournisseur)) {

        // On sauvegarde dans les LOGS
        $log = new Log([]);
        $log->setLog_texte("Suppression d'un fournisseur (champ 'supprime' à 1) : ID #" . $id_fournisseur);
        $log->setLog_type('warning');
		$logsManager->saveLog($log);

    } // FIN test suppression OK pour Log
    exit;
} // FIN mode


/* ------------------------------------------
MODE - Vérifie si existe déjà (admin/modale)
-------------------------------------------*/
function modeCheckExisteDeja() {

    global $tiersManager;

    $id     = isset($_REQUEST['id'])        ? (int)$_REQUEST['id']      : 0;
    $nom    = isset($_REQUEST['nom'])       ? trim($_REQUEST['nom'])    : '';

	echo $tiersManager->checkExisteDeja($nom, $id) ? 1 : 0;

} // FIN mode

/* ------------------------------------------
MODE - Modale gestion des familles
-------------------------------------------*/
function modeModalTiersFamilles() {

	global $tiersManager, $utilisateur;

	if (!$utilisateur->isDev()) { ?>
        <div class="alert alert-danger">Accès non autorisé.</div>
        <?php
	    exit;
	}

	// Récupération de la liste des familles de tiers
	$liste_familles = $tiersManager->getTiersFamillesListe();

	// Si aucune famille
	if (empty($liste_familles)) { ?>
	    <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle mr-2"></i>Aucune famille existante...
        </div
        </div>>
        <?php
        exit;
	} // FIN aucune famille

	// Zone supérieure : liste des familles...
	?>
    <div class="alert alert-secondary">
        <div class="row">
            <?php
            // Liste des familles
            foreach ($liste_familles as $fam) { ?>

            <div class="col-6 pb-2">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Libellé" name="nom" value=" <?php echo htmlentities($fam->getNom()); ?>">
                    <div class="input-group-append">
                        <?php // Si on a aucun tiers associé à cette famille, on propose de la supprime
                        if ($fam->getNb_tiers() == 0) {
							?>
                            <button class="btn btn-danger btnSupprFamille" type="button" data-id-famille="<?php echo $fam->getId(); ?>"><i class="fa fa-trash-alt"></i></button>
							<?php
						} else { ?>
                            <span class="input-group-text"><span class="badge badge-pill badge-secondary texte-fin"><?php echo $fam->getNb_tiers(); ?></span></span>
                        <?php
                        } // FIN test aucun tiers non supprimé associé
                        ?>
                        <button class="btn btn-success btnUpdNomFamille" type="button" data-id-famille="<?php echo $fam->getId(); ?>"><i class="fa fa-check"></i></button>
                    </div>
                </div>
            </div>
            <?php
            } // FIN liste des familles
            ?>
        </div>
    </div>
	<?php

    // Zone inférieure : ajout nouvelle famille...
	?>
    <div class="row">
        <form class="col" id="formNewFamille">
            <input type="hidden" name="mode" value="saveNewFamille" />
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-plus-square mr-1"></i>Nouvelle famille :</span>
                </div>
                <input type="text" class="form-control" placeholder="Libellé" name="nom" value="">
                <div class="input-group-append">
                    <button type="button" class="btn btn-success btnAddNewFamille"><i class="fa fa-check mr-1"></i> Ajouter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger mt-3">
                <i class="fa fa-user-secret fa-3x float-left mr-3"></i>
                <p>La gestion des familles est réservée aux développeurs ! Ne pas changer les noms et ID qui sont utilisés pour détecter les fournisseurs de consommables et de viande.</p>
            </div>
        </div>
    </div>



	<?php

} // FIN mode

/* ------------------------------------------
MODE - Enregistre une nouvelle famille
-------------------------------------------*/
function modeSaveNewFamille() {

    global $tiersManager, $logsManager;

    $nom = isset($_REQUEST['nom']) ? trim($_REQUEST['nom']) : '';
    if (strlen($nom) == 0) { exit; }

	$famille = new TiersFamille([]);
	$nom = str_replace('#et#', '&', strtolower($nom));
    $famille->setNom($nom);

	$retour = $tiersManager->saveTiersFamille($famille);

	// Logs
	$log = new Log([]);
	if ($retour) {
		$log->setLog_type('info');
		$log->setLog_texte("Création d'une nouvelle famille de fournisseurs : " . $nom);
	} else {
		$log->setLog_type('danger');
		$log->setLog_texte("ERREUR lors de la création  d'une nouvelle famille de fournisseurs : " . $nom);

	} // FIN test retour Save

	$logsManager->saveLog($log);

    exit;
} // FIN mode

/* ------------------------------------------
MODE - Supprime une famille de tiers (modale)
-------------------------------------------*/
function modeSupprFamille() {

	global $tiersManager, $logsManager;

	$id_fam = isset($_REQUEST['id_fam']) ? intval($_REQUEST['id_fam']) : 0;
	if ($id_fam == 0) { echo '-1'; exit; }

	$famille = $tiersManager->getTiersFamille($id_fam);
	if (!$famille instanceof TiersFamille) { echo '-2'; exit; }

	// Si on a des tiers non supprimés encore associés à cette famille, pas possible de supprimer !
    if ($famille->getNb_tiers() > 0) { echo '-3'; exit; }

    $nbTiersFamilleSuppr = $tiersManager->getNbTiersFamille($famille, true);

	// Si on des tiers supprimés (flag supprimé = 1) toujours ratachés, on ne fais que flaguer aussi la famille à "supprimé = 1"
	if ($nbTiersFamilleSuppr > 0) {

		$famille->setSupprime(1);
		echo $tiersManager->saveTiersFamille($famille) ? '1' : '0';

    // Sinon, rien ne relie cette famille à un tiers, on peux donc sans danger la supprimer en BDD (et pan !)
    } else {

		echo $tiersManager->supprTiersFamille($famille) ? '1' : '0';

    } // FIN test mode de suppression

    exit;

} // FIN mode

/* ---------------------------------------------------
MODE - Modifie le nom d'une famille de tiers (modale)
----------------------------------------------------*/
function modeUpdNomTiersFamille() {

	global $tiersManager, $logsManager;

	$id_fam = isset($_REQUEST['id_fam']) ? intval($_REQUEST['id_fam']) : 0;
	if ($id_fam == 0) { echo '-1'; exit; }

	$famille = $tiersManager->getTiersFamille($id_fam);
	if (!$famille instanceof TiersFamille) { echo '-2'; exit; }

    $nom = isset($_REQUEST['nom']) ? trim($_REQUEST['nom']) : '';
	if (strlen($nom) == 0) { echo '-3'; exit; }

	$nom = str_replace('#et#', '&', $nom);
	$nom = str_replace('#sq#', "'", $nom);
	$nom = str_replace('#dq#', '"', $nom);

	$ancienNom = $famille->getNom();
	$famille->setNom($nom);

	$retour = $tiersManager->saveTiersFamille($famille);

	// Logs
	$log = new Log([]);
	if ($retour) {
		$log->setLog_type('info');
		$log->setLog_texte("Modification du nom de la famille de tiers : " . $ancienNom . " en " . $nom);

	} else {
		$log->setLog_type('danger');
		$log->setLog_texte("ERREUR lors de la modification du nom de la famille de tiers : " . $ancienNom . " en " . $nom);

	} // FIN test retour création/maj pour log

	$logsManager->saveLog($log);

	echo $retour ? 1 : 0;

} // FIN mode

/* ------------------------------------------
MODE - Export en PDF
-------------------------------------------*/
function modeExportPdf() {

	global $tiersManager;

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = genereContenuPdf();
	$content .= ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/iprexfrs-*.pdf') as $fichier) {
		unlink($fichier);
	}

	try {
		$nom_fichier = 'iprexfrs-'.date('is').'.pdf';
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15');
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($content));;
		$savefilepath = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
		echo __CBO_TEMP_URL__.$nom_fichier;
	}
	catch(HTML2PDF_exception $e) {
		exit;
	}

	exit;


} // FIN mode

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML pour le PDF
-----------------------------------------------------------------------------*/
function genereContenuPdf() {

	global $cnx, $tiersManager;

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
    .titre {
       background-color: teal;
       color: #fff;
       padding: 3px;
       text-align: center;
       font-weight: normal;
       font-size: 14px;
    }
    .recap {
       background-color: #ccc;
       padding: 3px;
       text-align: center;
       font-weight: normal;
       font-size: 10px;
    }
    
    .w100 { width: 100%; }
    .w75 { width: 75%; }
    .w50 { width: 50%; }
    .w40 { width: 40%; }
    .w25 { width: 25%; }
    .w33 { width: 33%; }
    .w34 { width: 34%; }
    .w20 { width: 20%; }
    .w30 { width: 30%; }
    .w15 { width: 15%; }
    .w35 { width: 35%; }
    .w5 { width: 5%; }
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
    
  </style> 
</head>
<body>';

	$contenu.=  genereEntetePagePdf();

	// PAGE 1

	// GENERAL

	// Préparation des variables
	$na             = '<span class="gris-9 text-11"><i>Non renseigné</i></span>';
	$tiret          = '<span class="gris-9 text-11"><i>-</i></span>';

	// Préparation des variables
	$params = [];
	$liste = $tiersManager->getListeFournisseurs($params);
	$adressesManager = new AdresseManager($cnx);
	$contactsManager = new ContactManager($cnx);



	// Génération du contenu HTML
	$contenu.= '<table class="table table-liste w100 mt-10">';

	// Aucun frs
	if (empty($liste)) {

		$contenu.= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun fournisseur</i></td></tr>';

	// Liste des Frs
	} else {


		foreach ($liste as $item) {

			$actif        = $item->getActif() > 0 ? '' : '  - Désactivé';
			$famille      = trim($item->getFamille()) != '' ? ' ('. $item->getFamille().')' : '';

			$contenu.= '<tr>
                            <th class="w100">' . $item->getNom() .$famille.$actif.'</th>
                        </tr>';

			// On intègre les adresses
			$adressesClient = $adressesManager->getListeAdresses(['id_tiers' => $item->getID()]);

			foreach ($adressesClient as $adresse) {
				$contenu.= '<tr>
                                <td class="w100">';
				$contenu.= count($adressesClient) > 1 ? Adresse::TYPES[$adresse->getType()] . ' : ' : 'Adresse : ';
				$contenu.= $adresse->getNom() != '' ? $adresse->getNom() . ' ' : '';
				$contenu.= $adresse->getAdresse_ligne();
				$contenu.= '</td></tr>';
			}

			// On intègre les contacts
			$contactsClient = $contactsManager->getListeContacts(['id_tiers' => $item->getId()]);
			foreach ($contactsClient as $contact) {
				$contenu.= '<tr>
                                <td class="w100">';
				$contenu.= trim($contact->getNom_complet()) != '-' ? $contact->getNom_complet() : '';
				$contenu.= $contact->getTelephone() != '' ? ' Tél : ' . $contact->getTelephone() . ' ' : '';
				$contenu.= $contact->getMobile() != '' ? ' Mobile : ' . $contact->getMobile() . ' ' : '';
				$contenu.= $contact->getFax() != '' ? ' Fax : ' . $contact->getFax() . ' ' : '';
				$contenu.= $contact->getEmail() != '' ? ' ' . $contact->getEmail() . ' ' : '';
				$contenu.= '</td></tr>';
			}



		} // FIN boucle sur les produits


	} // FIN test produits

	$contenu.= '</table>';

	$contenu.= '<table class="table w100 mt-15"><tr><th class="w100 recap">Nombre de fournisseurs : '. count($liste) .'</th></tr></table>';
	// FOOTER
	$contenu.= '<table class="w100 gris-9">
                    <tr>
                        <td class="w50 text-8">Document édité le '.date('d/m/Y').' à '.date('H:i:s').'</td>
                        <td class="w50 text-right text-6">&copy; 2019 IPREX / INTERSED </td>
                    </tr>
                </table>
            </body>
        </html>';



	// RETOUR CONTENU
	return $contenu;

} // FIN fonction déportée

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le header du PDF (logo...)
-----------------------------------------------------------------------------*/
function genereEntetePagePdf() {

	global $cnx;

	$entete = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            Liste des fournisseurs au '.date("d/m/Y").'
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


/* ------------------------------------
MODE - Modale Contacts frs (admin)
------------------------------------*/
function modeModalContacts() {

	global
	$utilisateur,
	$tiersManager,
	$cnx;

	// On vérifie qu'on est bien loggé
	if (!isset($_SESSION['logged_user'])) { exit;}

	$frs_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($frs_id == 0) { exit('ERREUR^Identification du fournisseur impossible !'); }
	$frs = $tiersManager->getTiers($frs_id);
	if (!$frs instanceof Tiers) { exit('ERREUR^Instanciation du fournisseur impossible !'); }

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) { $utilisateur = unserialize($_SESSION['logged_user']); }

	$id_contact = isset($_REQUEST['id_contact']) ? intval($_REQUEST['id_contact']) : 0; // Pour édition d'un contact

	$contactsManager = new ContactManager($cnx);
	$contactEdit = $id_contact > 0 ? $contactsManager->getContact($id_contact) : new Contact([]);
	if (!$contactEdit instanceof Contact) { $contactEdit = new Contact(); $id_contact = 0;}

	// Retour Titre
	echo '<i class="fa fa-user-friends"></i> Contacts de ' . $frs->getNom();

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>
    <div class="row">
        <div class="col-8">
			<?php
			if (empty($frs->getContacts())) { ?>
                <div class="alert alert-warning text-center padding-50">
                    Aucun contact pour ce fournisseur&hellip;
                </div>
			<?php } else { ?>
                <table class="table admin table-v-middle table-fine">
                    <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Télephone</th>
                        <th>Mobile</th>
                        <th>E-mail</th>
                        <th class="t-actions w-mini-admin-cell text-center">Modif.</th>
                        <th class="t-actions w-mini-admin-cell text-center">Suppr.</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ($frs->getContacts() as $contact) { ?>
                        <tr data-id="<?php echo $contact->getId(); ?>" data-id-frs="<?php echo $contact->getId_tiers(); ?>" <?php echo $id_contact == $contact->getId() ? 'class="bg-info text-white"' : ''; ?>>
                            <td><?php echo strtoupper($contact->getNom());?></td>
                            <td><?php echo ucwords(strtolower($contact->getPrenom()));?></td>
                            <td><?php echo trim($contact->getTelephone()) != '' ? trim($contact->getTelephone()) : '&mdash;';?></td>
                            <td><?php echo trim($contact->getMobile()) != '' ? trim($contact->getMobile()) : '&mdash;';?></td>
                            <td><?php echo trim($contact->getEmail()) != '' ? '<a href="mailto:'.trim($contact->getEmail()).'" class="texte-fin text-info">'.trim($contact->getEmail()).'</a>' : '&mdash;';?></td>
                            <td class="<?php echo $id_contact != $contact->getId() ? 't-actions' : ''; ?> text-center">
								<?php if ($id_contact == $contact->getId()) { ?>
                                    <i class="fa fa-edit fa-lg"></i>
								<?php } else { ?>
                                    <button type="button" class="btn btn-sm btn-secondary btnEditContact"><i class="fa fa-edit"></i></button>
								<?php } ?>
                            </td>
                            <td class="<?php echo $id_contact != $contact->getId() ? 't-actions' : ''; ?> text-center">
								<?php if ($id_contact == $contact->getId()) { ?>
                                    <i class="fa fa-arrow-right fa-lg"></i>
								<?php } else { ?>
                                    <button type="button" class="btn btn-sm btn-danger btnSupprContact"><i class="fa fa-trash-alt"></i></button>
								<?php } ?>
                            </td>
                        </tr>
					<?php }
					?>
                    </tbody>
                </table>
			<?php } ?>
        </div>
        <div class="col-4">
            <div class="alert alert-secondary">
                <p class="nomargin gris-5"><i class="fa <?php echo $id_contact == 0 ? 'fa-plus-square' : 'fa-edit'; ?> mr-1 gris-9"></i> <?php echo $id_contact == 0 ? 'Nouveau' : 'Modification'; ?> contact fournisseur :</p>
                <hr class="margin-5">
                <form id="addUpdContactFrs">
                    <input type="hidden" name="mode" value="addUpdContact"/>
                    <input type="hidden" name="id_contact" value="<?php echo $id_contact; ?>"/>
                    <input type="hidden" name="id_frs" value="<?php echo $frs_id; ?>"/>
                    <div class="row mb-2">
                        <div class="col-6">
                            <span class="texte-fin text-13">Nom :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="Nom de famille" name="nom" maxlength="255" value="<?php echo $id_contact > 0 ? $contactEdit->getNom() : ''; ?>"/>
                            </div>
                        </div>
                        <div class="col-6">
                            <span class="texte-fin text-13">Prénom :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="Prénom" name="prenom" maxlength="255" value="<?php echo $id_contact > 0 ? $contactEdit->getPrenom() : ''; ?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <span class="texte-fin text-13">Téléphone :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="Numéro principal" name="telephone" maxlength="16" value="<?php echo $id_contact > 0 ? $contactEdit->getTelephone() : ''; ?>"/>
                            </div>
                        </div>
                        <div class="col-6">
                            <span class="texte-fin text-13">Mobile :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="Numéro portable" name="mobile" maxlength="16" value="<?php echo $id_contact > 0 ? $contactEdit->getMobile() : ''; ?>"/>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col">
                            <span class="texte-fin text-13">Adresse e-mail :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="exemple@domaine.com" name="email" maxlength="128" value="<?php echo $id_contact > 0 ? $contactEdit->getEmail() : ''; ?>"/>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-success form-control btnAddUpdContact">
                                <i class="fa fa-check mr-1"></i> <?php echo $id_contact > 0 ? 'Enregistrer' : 'Ajouter'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
	<?php
	exit;

} // FIN mode

/* ------------------------------------
MODE - Modale Adresses frs (admin)
------------------------------------*/
function modeModalAdresses() {

	global
	$utilisateur,
	$tiersManager,
	$cnx;

	// On vérifie qu'on est bien loggé
	if (!isset($_SESSION['logged_user'])) { exit;}

	$frs_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($frs_id == 0) { exit('ERREUR^Identification du fournisseur impossible !'); }
	$frs = $tiersManager->getTiers($frs_id);
	if (!$frs instanceof Tiers) { exit('ERREUR^Instanciation du fournisseur impossible !'); }

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) { $utilisateur = unserialize($_SESSION['logged_user']); }

	$paysManager = new PaysManager($cnx);

	$id_adresse = isset($_REQUEST['id_adresse']) ? intval($_REQUEST['id_adresse']) : 0; // Pour édition d'une adresse

	$adressesManager = new AdresseManager($cnx);
	$adresseEdit = $id_adresse > 0 ? $adressesManager->getAdresse($id_adresse) : new Adresse([]);
	if (!$adresseEdit instanceof Adresse) { $adresseEdit = new Adresse(); $id_adresse = 0;}

	// Retour Titre
	echo '<i class="fa fa-map-marker-alt"></i> Adresses de ' . $frs->getNom();

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>
    <div class="row">
        <div class="col-8">
			<?php
			if (empty($frs->getAdresses())) { ?>
                <div class="alert alert-warning text-center padding-50">
                    Aucune adresse pour ce fournisseur&hellip;
                </div>
			<?php } else { ?>
                <table class="table admin table-v-middle table-fine">
                    <thead>
                    <tr>
                        <th>Adresse</th>
                        <th>Complément</th>
                        <th>CP</th>
                        <th>Ville</th>
                        <th>Pays</th>
                        <th>Type</th>
                        <th class="t-actions w-mini-admin-cell text-center">Modif.</th>
                        <th class="t-actions w-mini-admin-cell text-center">Suppr.</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ($frs->getAdresses() as $adresse) { ?>
                        <tr data-id="<?php echo $adresse->getId(); ?>" data-id-frs="<?php echo $adresse->getId_tiers(); ?>" <?php echo $id_adresse == $adresse->getId() ? 'class="bg-info text-white"' : ''; ?>>
                            <td><?php echo $adresse->getAdresse_1();?></td>
                            <td><?php echo $adresse->getAdresse_2() != '' ? $adresse->getAdresse_2() : '&mdash;';?></td>
                            <td><?php echo $adresse->getCp();?></td>
                            <td><?php echo $adresse->getVille();?></td>
                            <td><?php echo $adresse->getNom_pays();?></td>
                            <td><?php echo Adresse::TYPES[$adresse->getType()];?></td>
                            <td class="<?php echo $id_adresse != $adresse->getId() ? 't-actions' : ''; ?> text-center">
								<?php if ($id_adresse == $adresse->getId()) { ?>
                                    <i class="fa fa-edit fa-lg"></i>
								<?php } else { ?>
                                    <button type="button" class="btn btn-sm btn-secondary btnEditAdresse"><i class="fa fa-edit"></i></button>
								<?php } ?>
                            </td>
                            <td class="<?php echo $id_adresse != $adresse->getId() ? 't-actions' : ''; ?> text-center">
								<?php if ($id_adresse == $adresse->getId()) { ?>
                                    <i class="fa fa-arrow-right fa-lg"></i>
								<?php } else { ?>
                                    <button type="button" class="btn btn-sm btn-danger btnSupprAdresse"><i class="fa fa-trash-alt"></i></button>
								<?php } ?>
                            </td>
                        </tr>
					<?php }
					?>
                    </tbody>
                </table>
			<?php } ?>
        </div>
        <div class="col-4">
            <div class="alert alert-secondary">
                <p class="nomargin gris-5"><i class="fa <?php echo $id_adresse == 0 ? 'fa-plus-square' : 'fa-edit'; ?> mr-1 gris-9"></i> <?php echo $id_adresse == 0 ? 'Nouvelle' : 'Modification'; ?> adresse fournisseur :</p>
                <hr class="margin-5">
                <form id="addUpdAdresseFrs">
                    <input type="hidden" name="mode" value="addUpdAdresse"/>
                    <input type="hidden" name="id_adresse" value="<?php echo $id_adresse; ?>"/>
                    <input type="hidden" name="id_frs" value="<?php echo $frs_id; ?>"/>
                    <div class="row mb-2">
                        <div class="col">
                            <span class="texte-fin text-13">Adresse :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="Numéro, voie..." name="adresse_1" maxlength="255" value="<?php echo $id_adresse > 0 ? $adresseEdit->getAdresse_1() : ''; ?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col">
                            <span class="texte-fin text-13">Complément d'adresse :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="Bâtiement, étage..." name="adresse_2" maxlength="255" value="<?php echo $id_adresse > 0 ? $adresseEdit->getAdresse_2() : ''; ?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">
                            <span class="texte-fin text-13">Code postal :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="CP" name="cp" maxlength="10" value="<?php echo $id_adresse > 0 ? $adresseEdit->getCp() : ''; ?>"/>
                            </div>
                        </div>
                        <div class="col-8">
                            <span class="texte-fin text-13">Ville :</span>
                            <div class="input-group">
                                <input type="text" class="form-control texte-fin text-13" placeholder="Ville" name="ville" maxlength="255" value="<?php echo $id_adresse > 0 ? $adresseEdit->getVille() : ''; ?>"/>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-7">
                            <span class="texte-fin text-13">Pays:</span>
                            <div class="input-group">
                                <select class="selectpicker form-control show-tick" name="id_pays" data-size="5">
									<?php
									foreach ($paysManager->getListePays() as $pays) { ?>
                                        <option value="<?php echo $pays->getId(); ?>" <?php
										if ($id_adresse == 0) {
											echo strtolower($pays->getIso()) == 'fr' ? 'selected' : '';
										} else {
											echo 	$adresseEdit->getId_pays() == $pays->getId() ? 'selected' : '';
										} ?>><?php echo $pays->getNom();?></option>
									<?php }
									?>
                                </select>
                            </div>
                        </div>
                        <div class="col-5">
                            <span class="texte-fin text-13">Type :</span>
                            <div class="input-group">
                                <input type="checkbox"
                                       name="type"
                                       class="togglemaster"
                                       data-toggle="toggle"
                                       data-on="Livraison"
                                       data-off="Facturation"
                                       data-onstyle="secondary"
                                       data-offstyle="secondary"
									<?php echo $id_adresse > 0 && $adresseEdit->getType() == 1 ? 'checked' : ''; ?>
                                />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-success form-control btnAddUpdAdresse">
                                <i class="fa fa-check mr-1"></i> <?php echo $id_adresse > 0 ? 'Enregistrer' : 'Ajouter'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
	<?php
	exit;

} // FIN mode

/* -------------------------------------------------
MODE - Enregistre un contact (add/upd)
--------------------------------------------------*/
function modeAddUpdContact() {

	global $cnx, $logsManager;

	$contactManager = new ContactManager($cnx);

	$id_contact = isset($_REQUEST['id_contact'])    ? intval($_REQUEST['id_contact'])   : 0;
	$id_frs     = isset($_REQUEST['id_frs'])        ? intval($_REQUEST['id_frs'])       : 0;
	$nom        = isset($_REQUEST['nom'])           ? trim($_REQUEST['nom'])            : '';
	$prenom     = isset($_REQUEST['prenom'])        ? trim($_REQUEST['prenom'])         : '';
	$telephone  = isset($_REQUEST['telephone'])     ? trim($_REQUEST['telephone'])      : '';
	$mobile     = isset($_REQUEST['mobile'])        ? trim($_REQUEST['mobile'])         : '';
	$email      = isset($_REQUEST['email'])         ? trim($_REQUEST['email'])          : '';

	if ($nom == '') { $nom = '-'; }

	$contact = $id_contact > 0 ? $contactManager->getContact($id_contact) : new Contact([]);
	if (!$contact instanceof Contact) { exit('RQ9C6O9A/'.$id_contact); }
	if ($id_frs == 0) { exit('KWY1BAIA'); }

	$contact->setId_tiers($id_frs);
	$contact->setNom(strtoupper($nom));
	$contact->setPrenom(ucwords(strtolower($prenom)));
	$contact->setTelephone($telephone);
	$contact->setMobile($mobile);
	$contact->setEmail($email);
	if ($id_contact == 0) {
		$contact->setDate_add(date('Y-m-d H:i:s'));
	} else {
		$contact->setDate_maj(date('Y-m-d H:i:s'));
	}

	if (!$contactManager->saveContact($contact)) { exit('U0PD0ZEQ/'.$id_contact); }

	$log = new Log([]);
	$log->setLog_type('info');
	$logtexte = $id_contact > 0 ? "Modification du contact ID " . $id_contact : "Création d'un nouveau contact pour le fournisseur ID " . $id_frs;
	$log->setLog_texte($logtexte);
	$logsManager->saveLog($log);

	echo '1';
	exit;

} // FIN mode

/* -------------------------------------------------
MODE - Enregistre une adresse (add/upd)
--------------------------------------------------*/
function modeAddUpdAdresse() {

	global $cnx, $logsManager;

	$adressesManager = new AdresseManager($cnx);

	$id_adresse = isset($_REQUEST['id_adresse'])    ? intval($_REQUEST['id_adresse'])   : 0;
	$id_frs     = isset($_REQUEST['id_frs'])        ? intval($_REQUEST['id_frs'])       : 0;
	$adresse_1  = isset($_REQUEST['adresse_1'])     ? trim($_REQUEST['adresse_1'])      : '';
	$adresse_2  = isset($_REQUEST['adresse_2'])     ? trim($_REQUEST['adresse_2'])      : '';
	$cp         = isset($_REQUEST['cp'])            ? trim($_REQUEST['cp'])             : '';
	$ville      = isset($_REQUEST['ville'])         ? trim($_REQUEST['ville'])          : '';
	$id_pays    = isset($_REQUEST['id_pays'])       ? intval($_REQUEST['id_pays'])      : 0;
	$type       = isset($_REQUEST['type'])          ? 1 : 0;

	$adresse = $id_adresse > 0 ? $adressesManager->getAdresse($id_adresse) : new Adresse([]);
	if (!$adresse instanceof Adresse) { exit('KMZ1LOCR/'.$id_adresse); }
	if ($id_frs == 0) { exit('6WM0FE6U'); }
	if ($id_pays == 0) { exit('8WARE2YL'); }

	$adresse->setId_tiers($id_frs);
	$adresse->setAdresse_1($adresse_1);
	$adresse->setAdresse_2($adresse_2);
	$adresse->setCp($cp);
	$adresse->setVille($ville);
	$adresse->setId_pays($id_pays);
	$adresse->setType($type);

	if (!$adressesManager->saveAdresse($adresse)) { exit('RJ0506B0/'.$id_adresse); }

	$log = new Log([]);
	$log->setLog_type('info');
	$logtexte = $id_adresse > 0 ? "Modification de l'adresse ID " . $id_adresse : "Création d'une nouvelle adresse pour le fournisseur ID " . $id_frs;
	$log->setLog_texte($logtexte);
	$logsManager->saveLog($log);

	echo '1';
	exit;

} // FIN mode

/* -------------------------------------------------
MODE - Supprime une adresse
--------------------------------------------------*/
function modeSupprAdresse() {

	global $cnx, $logsManager;

	$adressesManager = new AdresseManager($cnx);

	$id_adresse = isset($_REQUEST['id_adresse'])    ? intval($_REQUEST['id_adresse'])   : 0;
	if ($id_adresse == 0) { exit('KU4YM963'); }
	$adresse = $adressesManager->getAdresse($id_adresse);
	if (!$adresse instanceof Adresse) { exit('KU8JCT2O/'.$id_adresse); }

	$adresse->setSupprime(1);
	if (!$adressesManager->saveAdresse($adresse)) { exit('AQVFDJID/'.$id_adresse); }

	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Suppression de l'adresse ID " . $id_adresse);
	$logsManager->saveLog($log);

	echo '1';
	exit;

} // FIN mode

/* -------------------------------------------------
MODE - Supprime un contact
--------------------------------------------------*/
function modeSupprContact() {

	global $cnx, $logsManager;

	$contactsManager = new ContactManager($cnx);

	$id_contact = isset($_REQUEST['id_contact'])    ? intval($_REQUEST['id_contact'])   : 0;
	if ($id_contact == 0) { exit('PU75UR0T'); }
	$contact = $contactsManager->getContact($id_contact);
	if (!$contact instanceof Contact) { exit('C3VQ0XYL/'.$id_contact); }

	$contact->setSupprime(1);
	if (!$contactsManager->saveContact($contact)) { exit('Z6HO0KR5/'.$id_contact); }

	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Suppression du contact ID " . $id_contact);
	$logsManager->saveLog($log);

	echo '1';
	exit;

} // FIN mode

