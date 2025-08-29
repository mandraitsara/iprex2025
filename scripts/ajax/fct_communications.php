<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax COMMUNICATIONS
------------------------------------------------------*/

// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$communicationsManager = new CommunicationsManager($cnx);
$vuesManager = new VueManager($cnx);
$logsManager = new LogManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}


/* ------------------------------------
MODE - Modale Communication (admin)
------------------------------------*/
function modeModalCommunication() {

    global
	    $utilisateur,
	    $communicationsManager,
        $cnx;

	    $vuesManager = new VueManager($cnx);

    // On vérifie qu'on est bien loggé
    if (!isset($_SESSION['logged_user'])) { exit;}

	$communication_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$communication    = $communication_id > 0 ? $communicationsManager->getCommunication($communication_id) : new Communication([]);

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) {
		$utilisateur = unserialize($_SESSION['logged_user']);
    }

	// Retour Titre
	echo '<i class="fa fa-book-open gris-9 mr-2"></i>';
	echo $communication_id > 0 ? $communication->getNom() : "Nouvelle communication&hellip;";

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>

	<form class="container-fluid" id="formCommunicationAddUpd" <?php echo $communication_id == 0 ? 'enctype="multipart/form-data"' : ''; ?>>
        <input type="hidden" name="mode" value="saveCommunication"/>
        <input type="hidden" name="communication_id" id="communication_id" value="<?php echo $communication_id; ?>"/>


		<div class="row">
			<div class="col-8 input-group mb-3">

                <?php
                // Si nouvelle communication : upload Parcourir...
                if ( $communication_id == 0) { ?>

                <div class="input-group text-16">
                    <div class="input-group-prepend">
                            <span class="input-group-text" id="inputGroupFileAddon01"><i
                                        class="fa fa-file-pdf mr-2 gris-9"></i> Fichier PDF</span>
                    </div>
                    <div class="custom-file pointeur">
                        <input type="file" class="custom-file-input" id="inputGroupFile01"
                               aria-describedby="inputGroupFileAddon01">
                        <label class="custom-file-label nom-fichier-a-uploader text-left text-14 pt-2 text-info" for="inputGroupFile01">Cliquez ici pour sélectionnez un fichier...</label>
                    </div>
                </div>

                <?php
                // Si édition, on ne permet pas un ré-upload, on affiche le nom de fichier
                } else { ?>


                    <div class="input-group text-16">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="inputGroupFileAddon01"><i
                                        class="fa fa-file-pdf mr-2 gris-9"></i> Fichier PDF</span>
                        </div>
                        <div class="custom-file pointeur">
                            <input type="text" class="form-control" readonly value="<?php echo $communication->getFichier(); ?>">

                        </div>
                    </div>


                <?php
                } // FIN test add/upd
                ?>

            </div>

            <div class="col-4 input-group mb-3">

                <div class="input-group-prepend">
                    <span class="input-group-text">Vues</span>
                </div>
                <select class="selectpicker form-control show-tick" multiple data-actions-box="true" data-selected-text-format="count > 1" name="id_vue" title="Sélectionnez..."  <?php echo $communication_id > 0 ? 'disabled' : ''; ?>>
					<?php
					foreach ($vuesManager->getVuesListe([]) as $vue) { ?>
                        <option value="<?php echo $vue->getId(); ?>" <?php echo $vue->getId() == $communication->getId_vue() ? 'selected' : ''; ?>><?php echo $vue->getNom(); ?></option>
					<?php } // FIN boucle pays clients
					?>
                </select>
            </div>

		</div>

        <div class="row">

            <div class="col-8 input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Titre</span>
                </div>
                <input type="text" class="form-control" placeholder="Titre ou libellé du document" name="nom"  maxlength="100" value="<?php  echo $communication->getNom(); ?>" />

            </div>

            <div class="col-4 input-group">

                <div class="input-group-prepend">
                    <span class="input-group-text">Dossier</span>
                </div>
                <select class="selectpicker form-control show-tick" name="chemin" title="Sélectionnez...">
                    <option value="/" <?php echo  $communication->getChemin() == '' || $communication->getChemin() == '/' ? 'selected' : ''; ?>>/</option>
					<?php
					$racine = __CBO_UPLOADS_PATH__.'com';
					getDirectory($racine, 0, 'select',  $communication->getChemin());
					?>
                </select>

            </div>


        </div>
        <div class="row">

            <div class="col-12 text-right mt-3">

                <input type="checkbox" class="togglemaster" data-toggle="toggle" name="activation"
					<?php echo $communication->isActif() ? 'checked' : ''; ?>
                       data-on="Activé"
                       data-off="Désactivé"
                       data-onstyle="success"
                       data-offstyle="danger"
                />
            </div>
        </div>

	</form>
	<?php

	exit;
} // FIN mode





/* ------------------------------------
MODE - Affiche la liste des communications
------------------------------------*/
function modeShowListeCommunications() {

	global
	    $mode,
		$utilisateur,
        $communicationsManager;

	$params['show_inactifs'] 	= true;

	$filtre_recherche   =  isset($_REQUEST['filtre_recherche'])     ? trim($_REQUEST['filtre_recherche'])   : '';
	$filtre_vue         =  isset($_REQUEST['filtre_vue'])           ? trim($_REQUEST['filtre_vue'])         : '';
	$filtre_actif       =  isset($_REQUEST['filtre_actif']) && $_REQUEST['filtre_actif'] != '' ? intval($_REQUEST['filtre_actif'])     : -1;


	if ($filtre_recherche != '' ) { $params['recherche'] = $filtre_recherche;  }
	if ($filtre_vue      != '' ) { $params['vue'] = $filtre_vue;              }
	if ($filtre_actif     > -1  ) { $params['actif'] = $filtre_actif;          }

	$listeCommunications = $communicationsManager->getListeCommunications($params);


	// Si aucun communication a afficher
	if (empty($listeCommunications)) { ?>

        <div class="alert alert-danger">
            <i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucun communication !</strong>
        </div>

    <?php

	// Sinon, affichage de la liste des communications
	} else { ?>


        <table class="admin w-100">
            <thead>
                <tr>
				    <?php
                    // On affiche l'ID que si on est développeur
                    if ($utilisateur->isDev()) { ?><th class="w-court-admin-cell">ID</th><?php } ?>

                    <th>Titre</th>
                    <th>Dossier</th>
                    <th>Vue</th>
                    <th>Date</th>
                    <th class="text-center w-court-admin-cell">Actif</th>
                    <th class="text-center t-actions w-court-admin-cell">Ouvrir</th>
                    <th class="text-center t-actions w-court-admin-cell">Détails</th>
                </tr>
            </thead>
            <tbody>
			    <?php

                // Boucle sur les communications
				foreach ($listeCommunications as $communication) {
				    
					?>
                    <tr>
						<?php
						// On affiche l'ID que si on est développeur
                        if ($utilisateur->isDev()) { ?>
                            <td class="w-court-admin-cell d-none d-xl-table-cell"><span class="badge badge-pill badge-warning"><?php echo $communication->getId();?></span></td>
                        <?php } ?>
                        <td class="text-18"><?php echo $communication->getNom();?></td>
                        <td><code class="text-12 gris-3"><?php echo $communication->getChemin();
								echo substr($communication->getChemin(),-1,1) != '/' ? '/' : '';
                        //echo $communication->getFichier()?></code></td>
                        <td><span class="badge badge-info text-14"><?php echo $communication->getNom_vue(); ?></span></td>
                        <td><?php echo Outils::dateSqlToFr($communication->getDate()); ?></td>
                        <td class="text-center w-court-admin-cell"><i class="fa fa-fw fa-lg fa-<?php echo $communication->isActif() ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i></td>
                        <td class="t-actions w-court-admin-cell"><a href="<?php echo __CBO_UPLOADS_URL__.'com/'.$communication->getCheminFichier(); ?>" class="btn btn-sm btn-info"><i class="fa fa-external-link-alt"></i></a></td>
                        <td class="t-actions w-court-admin-cell"><button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalCommunication" data-communication-id="<?php
                            echo $communication->getId(); ?>"><i class="fa fa-edit"></i> </button></td>
                        </tr>
                <?php
				} // FIN boucle communications ?>
            </tbody>
        </table>
	<?php } // FIN test communications à afficher
    exit;
} // FIN mode


/* ---------------------------------------
MODE - Supprime une communication
---------------------------------------*/
function modeSupprCommunication() {

    global
        $communicationsManager,
        $logsManager;

    //  -> On ne supprime pas réellement pour les raisons de traçabilité, on passe le champ "supprime" à 1

    // On récupère l'ID de l'communication, si il n'est pas clairement identifié, on ne va pas plus loin
    $id_communication = isset($_REQUEST['id_communication']) ? intval($_REQUEST['id_communication']) : 0;
    if ($id_communication == 0) { exit; }

    // Instanciation de l'objet Communication
    $communication = $communicationsManager->getCommunication($id_communication);

    // On passe le statut à supprimé
	$communication->setSupprime(1);

    // On désactive
	$communication->setActif(0);


	// Si la mise à jour s'est bien passé en BDD
    if ($communicationsManager->saveCommunication($communication)) {

        // On sauvegarde dans les LOGS
        $log = new Log([]);
        $log->setLog_texte("Suppression d'une communication (champ 'supprime' à 1) : ID #" . $id_communication);
        $log->setLog_type('warning');
		$logsManager->saveLog($log);

    } // FIN test suppression OK pour Log
    exit;
} // FIN mode


/* ------------------------------------------
MODE - Vérifie si existe déjà (admin/modale)
-------------------------------------------*/
function modeCheckExisteDeja() {

    global
        $communicationsManager;

    $id     = isset($_REQUEST['id'])        ? (int)$_REQUEST['id']      : 0;
    $genlot = isset($_REQUEST['genlot'])    ? trim($_REQUEST['genlot'])    : '';
    $numagr = isset($_REQUEST['numagr'])    ? trim($_REQUEST['numagr']) : '';

	echo $communicationsManager->checkExisteDeja($genlot, $numagr, $id) ? 1 : 0;

} // FIN mode

/* ------------------------------------------
MODE - Uploade + save une communication
-------------------------------------------*/
function modeSaveCommunication() {


	global
	$cnx,
	$communicationsManager;

	$logsManager = new LogManager($cnx);

	// Récupération des variables hors fichier
    $id_communication = isset($_REQUEST['communication_id']) ? intval($_REQUEST['communication_id']) : 0;
	$id_vue      = isset($_REQUEST['id_vue'])    ? $_REQUEST['id_vue']           : '';
	$actif       = isset($_REQUEST['actif'])     ? intval($_REQUEST['actif'])    : 0;
	$nom         = isset($_REQUEST['nom'])       ? trim($_REQUEST['nom'])        : '';
	$cheminCourt = isset($_REQUEST['chemin'])    ? trim($_REQUEST['chemin'])     : '';


    $ids_vues = explode(',', $id_vue);

	$nom = str_replace('#et#', '&', $nom);
	if ($cheminCourt == '/') { $cheminCourt = ''; }

    if (isset($_REQUEST['activation'])) { $actif = 1; }




	// Si nouvelle com, on traite avec l'upload
	if ($id_communication == 0) {

		// SI la vue ne peut être identifié, on retourne une erreur (sauf en upd car elle est disabled)
		if (!$ids_vues || !is_array($ids_vues) || count($ids_vues) == 1 && $ids_vues[0] == '') { echo '-1'; exit; }


		// On vérifie l'intégrité du fichier
		$fichiers = isset($_FILES) ? $_FILES : false;
		if (!$fichiers) { echo '-2'; exit;}
		$fichier = isset($fichiers['file']) && !empty($fichiers['file']) ? $fichiers['file'] : false;
		if (!$fichier) { echo '-2'; exit;}

		// On vérifie les extensions autorisées
		$extension = trim(strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION)));
		$listeTypes = ['pdf', 'PDF'];
		if (!in_array($extension, $listeTypes)) { echo 0; exit; }

		// Si pas de nom, on le génère avec la date
		if ($nom == '') { $nom = 'Communication du  ' . date('d/m/Y'); }

		// On crée le dossier s'il n'existe pas encore
		$chemin = __CBO_UPLOADS_PATH__."com/".$cheminCourt;
		if (!file_exists($chemin)) {
			if (!mkdir($chemin, 0777, true)) {
				echo '-3'; exit;
			}
		} // FIN test création du dossier du lot dans /upload

		// On formate le nom du fichier
		$nomFichier = date('YmdHis').'.'.$extension;

		// On tente d'uploader le fichier
		if(!move_uploaded_file($fichier['tmp_name'], $chemin.'/'.$nomFichier)) {
			echo '-4'; exit;
		} // FIN upload fichier


        // Pour toutes les vues sélectionnées...
        foreach ($ids_vues as $id_vue) {

            // ... on crée autant de communications que de vues
			$com = new Communication([]);
			$com->setNom($nom);
			$com->setSupprime(0);
			$com->setActif($actif);
			$com->setDate(date('Y-m-d H:i:s'));
			$com->setId_vue(intval($id_vue));
			$com->setFichier($nomFichier);
			$com->setChemin($cheminCourt);
			$communicationsManager->saveCommunication($com);
        } // FIN boucle sur les id_vue

		// On enregistre en BDD


		$logTexte = 'Upload du fichier "'.$nomFichier.'" dans le dossier /COM pour communication Front';

    // Sinon (update)...
	} else {

		$com = $communicationsManager->getCommunication($id_communication);
	    if (!$com instanceof Communication) { echo '-6'; exit; }

		// Si on déplace le fichier de dossier
		if ($com->getChemin() != $cheminCourt) {

			$chemin = __CBO_UPLOADS_PATH__."com/";
		    $source = $chemin . $com->getChemin();
		    $dest = $chemin . $cheminCourt;

		    if (substr($source,-2) == '//') {
				$source = substr($source,0,-1);
            } else if (substr($source,-1) != '/') {
				$source.= '/';
            }
			if (substr($dest,-2) == '//') {
				$dest = substr($dest,0,-1);
			} else if (substr($dest,-1) != '/') {
				$dest.= '/';
			}

			$dest = str_replace('//', '/', $dest);

			// SI on arrive à copier le fichier...
			if (copy($source.$com->getFichier(), $dest.$com->getFichier())) {

			    // ...on supprime l'original...
			    unlink($source.$com->getFichier());

				// ... et on déplace met à jour le chemin pour toutes les coms qui ont le meme fichier
				$communicationsManager->updateCheminByFichier($com->getFichier(), $cheminCourt);
            } // FIN test copie


        } // FIN déplacement du fichier

		//$com->setId_vue($id_vue);
		$com->setActif($actif);
		$com->setNom($nom);
		$com->setChemin($cheminCourt);

		$logTexte = 'Mise à jour de la communication Front ID #'.$id_communication;

    } // FIN test add/upd

	// Gestion de l'erreur
	if (!$communicationsManager->saveCommunication($com)) {
		echo '-5'; exit;
	} // Fin enrestrement en BDD

	// On log
	$log = new Log([]);
	$log->setLog_type('success');
	$log->setLog_texte($logTexte);
	$logsManager->saveLog($log);

	echo '1';
	exit;

} // FIN mode


/* ------------------------------------------
MODE - Contenu de la modale en front
Affiche les documents de la vue
-------------------------------------------*/
function modeModalCommunicationVue() {

    global $communicationsManager, $cnx;

    // Identification et contrôle de la vue...
    $vue_code = isset($_REQUEST['vue_code']) ? strtolower($_REQUEST['vue_code']) : '';
    if ($vue_code == '') { echo "<b>ERREUR</b><p>Identification de la vue impossible...</p><p>Code erreur : <code>RYUCGCAD</code></p>"; }

    $vuesManager = new VueManager($cnx);

    // Instanciation de l'objet Vue pour contrôle
    $vue = $vuesManager->getVueByCode($vue_code);
	if (!$vue instanceof Vue) { echo "<b>ERREUR</b><p>Instanciation de l'objet vue impossible...</p><p>Code erreur : <code>ZCFOLFHA</code></p>"; }

	$dossier = isset($_REQUEST['dossier']) ? $_REQUEST['dossier'] : '/';

    // Récupération de la liste des communications de la vue
    // (tous les dossiers, afin de ne pas avoir à chercher dans une arborescence alors que de toute façon il n'y a rien)
    $params = ['vue' => $vue->getId()];
    $coms = $communicationsManager->getListeCommunications($params);

    // Si aucune communication pour la vue...
    if (empty($coms)) { ?>

        <div class="alert alert-warning mb-0">
            <i class="fa fa-eye-slash pb-2 fa-lg"></i><br>
            Aucun document disponible pour la vue <?php echo $vue->getNom(); ?>
        </div>

    <?php
        exit;
    } // Fin aucune communication ?>

    <div class="alert alert-secondary mb-0">

    <?php
    // ON affiche les dossiers
	$racine = __CBO_UPLOADS_PATH__.'com';
    $chemin = isset($_REQUEST['dossier']) ?  str_replace(__CBO_UPLOADS_PATH__ . 'com/', '',$_REQUEST['dossier']) : '/';
	$resDos = isset($_REQUEST['dossier']) ? $_REQUEST['dossier'] : '';
	$path   = $racine.'/'.$chemin;
	$ignore = array( '.', '..' );

	$path = str_replace('//', '/', $path);

	// Debug truc bizare en prod... O_o
	$chemin = str_replace('/var/www/iprexdev/html/uploads/com/','/', $chemin);

	// On construit le fil d'ariane
    $cheminArray    = explode('/', $chemin);
    $ariane         = '';
    $separateur     = '<i class="fa fa-chevron-right ml-1 mr-1 text-12"></i>';
    $i              = 0;
    foreach ($cheminArray as $chem) {
        $i++;
		$ariane.= trim($chem) != '' ? $chem : '';
		$ariane.= $i < count($cheminArray) &&  trim($chem) != '' ? $separateur : '';
    }

	// Valeur du data pour le chemin retour et appel récursif : on prends le chemin actuel sauf le dernier sous-dossier (/)
	$chemin_retour = substr($chemin, 0, strrpos( $chemin, '/'));
	if ($chemin_retour == '' && $resDos != '') {
		$chemin_retour = '/';
	}

	// Si on a un chemin de retour précis, on affiche le bouton pour remonter d'un niveau
	if (trim($chemin_retour) != '') { ?>
        <button type="button" class="btn btn-secondary btn-large btnDossier" data-vue-code="<?php echo $vue_code; ?>" data-dossier="<?php echo $chemin_retour != '/' ? $chemin_retour : ''; ?>"><i class="fa fa-reply"></i></button>
		<?php
	}

    // Affichage du fil d'arianne si au dessus de la racine
    if ($ariane != '') { ?>
        <span class="text-14 gris-7 ml-2"><i class="fa fa-folder-open mr-1"></i> <?php echo $ariane; ?></span>
		<?php
	} else { ?>
        <span class="text-14 gris-7 ml-2"><i class="fa fa-folder-open mr-1"></i> <?php echo $vue->getNom(); ?></span>

	<?php }
    ?>
        <hr>
        <div class="row">
            <div class="col-5">
                <?php
				// Debug truc bizare en prod... O_o
				$path = str_replace('/var/www/iprexdev/html/uploads/com/var/www/iprexdev/html/uploads/com/', '/var/www/iprexdev/html/uploads/com/', $path);

				// On parcours le chemin ciblé
				$dh = @opendir( $path );

                $nbSsDossiers = 0;

				// Tant qu'on trouve du contenu...
				while( false !== ( $file = readdir( $dh ) ) ) {

					// ... et qu'on ne l'ignore pas... (".."/".")
					if (!in_array($file, $ignore)) {

						// ... que c'est bien lui-même un dossier...
						if (is_dir("$path/$file")) {

							$nbSsDossiers++;
							// ... alors on formate le chemin pour le sous-dossier suivant et son affichage...
							$chemin_sous_dossier = str_replace(str_replace('//', '/', __CBO_UPLOADS_PATH__) . 'com/', '', $path . '/' . $file);
							$sousDossierArray = explode('/', $chemin_sous_dossier);

							// ...qu'on affiche sous forme d'icone de dossier.
							?>
                            <button type="button" class="btn btn-dark btn-large mb-1 form-control text-left btnDossier" data-vue-code="<?php
							echo $vue_code; ?>" data-dossier="<?php
							echo $chemin_sous_dossier; ?>"><i class="fa fa-folder mr-2"></i><?php
									echo $sousDossierArray[(int)count($sousDossierArray)-1]; ?></button>
							<?php

						} // FIN test dossier
					} // FIN test contenu non ignoré
				} // FIN boucle contenu dossier ciblé

                if ($nbSsDossiers == 0) { ?>
                    <p class="text-14 gris-7"><i class="fa fa-ban mr-1"></i>Aucun sous-dossier</p>
				<?php }

                ?>
            </div>
            <div class="col-7">
                <?php
				// Formatage nécéssaire suite retour récursif à la racine
				if ($chemin == '') { $chemin = '/'; }

				// On recharge les communications a afficher mais on ne prends que celle du dossier en cours
				$params = ['vue' => $vue->getId(), 'chemin' => $chemin];
				$coms = $communicationsManager->getListeCommunications($params);

				// Si rien à afficher...
				if (empty($coms)) { ?>
                    <p class="text-14 gris-7"><i class="fa fa-ban mr-1"></i>Dossier vide <?php

                        if ($nbSsDossiers > 0) {
                            echo '<br><i class="fa fa-hand-point-left mr-1"></i>Sélectionnez un sous-dossier ci-contre pour en afficher le contenu&hellip;';
                        } else if (trim($chemin_retour) != '') {
							echo '<br><i class="fa fa-reply mr-1"></i>Retournez au dossier précédent en appuyant sur la flèche&hellip;';
						}
                        ?></p>
				<?php }

				// ... sinon, on boucle sur les communications
				$i = 0;
				foreach ($coms as $com) {

					$i++; // Compteur pour mise en forme du margin
					if (substr($chemin,0,1) != '/') { $chemin = '/'.$chemin; }
					if (substr($chemin,-1,1) != '/') { $chemin.= '/'; }

					?>
                    <span data-href="<?php echo __CBO_UPLOADS_URL__.'com'.$chemin.$com->getFichier(); ?>" class="btnShowCom form-control btn btn-info text-left text-18 <?php echo $i < count($coms) ? 'mb-3' : ''; ?>">
            <span class="float-left"><i class="fa fa-file-pdf fa-lg mr-2"></i></span>
            <span class="pt-1 "><?php echo $com->getNom();?></span>
        </span>

					<?php
				} // FIN boucle sur les communications
                ?>

            </div>
        </div>
    </div>
    <?php
    exit;

} // FIN mode


/* ------------------------------------------
MODE - Contenu de la modale Dossiers
-------------------------------------------*/
function modeModalDossiers() {

	global $communicationsManager, $cnx;

	// On récupère les dossiers et sous-dossiers
    $racine = __CBO_UPLOADS_PATH__.'com';
    echo '<table class="w-100 arborescence">';
	getDirectory($racine, 0,  'table');
	echo '<tr><td colspan="3"><button type="button" class="btn btn-info padding-2-10 btnAddDossier"><i class="fa fa-plus text-12"></i><span class="texte-fin text-11 ml-1">Dossier</span></button></td></tr>';
	echo '</table>';

	exit;

} // FIN mode


/* ------------------------------------------
FONCTION DEPORTEE - Affiche le contenu
récursif d'un dossier
-------------------------------------------*/
function getDirectory( $path = '.', $level = 0, $mode = 'table', $selected = '') {

    // Mode =  Table / Select

	$ignore = array( '.', '..' );

	$dh = @opendir( $path );

	if ($mode == 'table') {
		$ifa = '<i class="fa fa-folder-open gris-7 mr-1"></i>';

		$btnAdd = '<button type="button" class="btn btn-info padding-2-10 btnAddDossier ml-2"><i class="fa fa-plus text-12"></i><span class="texte-fin text-11 ml-1">Sous-dossier</span></button>';
		$btnDel = '<button type="button" class="btn btn-danger padding-2-10 btnDelDossier ml-2"><i class="fa fa-trash-alt text-12"></i><span class="texte-fin text-11 ml-1">Supprimer</span></button>';

		$spaces = str_repeat( '&mdash;', ( $level * 1 ) );
    } else if ($mode == 'select') {
		$spaces = str_repeat( '&mdash;/', ( $level * 1 ) ) . '&nbsp;';
	}



		while( false !== ( $file = readdir( $dh ) ) ){

		if( !in_array( $file, $ignore ) ){

			if (is_dir("$path/$file")) {

			    $chemin = str_replace(__CBO_UPLOADS_PATH__.'com/', '',$path.'/'.$file);

			    // Mode table (modale)
				if ($mode == 'table') { ?>

				    <tr data-dossier="<?php echo $chemin; ?>">
                        <td><?php echo $spaces.$ifa.$file; ?></td>
                        <td class="text-right"><?php echo $btnAdd; ?></td>
                        <td class="text-right"><?php echo $btnDel; ?></td>
                    </tr>
                    <?php

			    // Mode select
				} else if ($mode == 'select') { ?>

                    <option value="<?php echo $chemin; ?>" <?php echo $selected == $chemin ? 'selected' : ''; ?>><?php echo trim($spaces) == '&nbsp;' ? '/' : ''; echo $spaces.$file; ?></option>

			    <?php
                } // FIN test mode rendu

				getDirectory( "$path/$file", ($level+1) , $mode, $selected);

			}
		}
	}
	closedir( $dh );

	return true;

} // FIN fonction déportée d'affichage de l'arborescende des dossiers

/* ------------------------------------------
MODE - Supprime un dossier
-------------------------------------------*/
function modeSupprimeDossier() {

    global $communicationsManager;

    $dossier = isset($_REQUEST['dossier']) ? trim($_REQUEST['dossier']) : '';
    if ($dossier == '') { exit; }

	$racine = __CBO_UPLOADS_PATH__.'com/';
    $cible  = $racine.$dossier.'/';

	$dh = @opendir( $cible );

    // On déplace les fichiers vers la racine
	while( false !== ( $fichier = readdir( $dh ) ) ){

	   if ($fichier == '.' || $fichier === '..') { continue; }

	   // On récupère les fichiers en BDD
        $com_liste = $communicationsManager->getCommunicationsByFichier($fichier);
	    if (empty($com_liste))  { continue; }

	    // On boucle sur les communication utilisant ce fichier
		foreach ($com_liste as $com) {

			// On modifie le dossier en BDD
			$com->setChemin('/');
			if (!$communicationsManager->saveCommunication($com)) { continue; }


	    } // FIN boucle

        if (!copy($cible.$fichier, $racine.$fichier)) { continue; }

        unlink($cible.$fichier);

	} // FIN boucle

    rmdir($cible);

    exit;
} // FIN mode


/* ------------------------------------------
MODE - Crée un dossier
-------------------------------------------*/
function modeCreerDossier() {

	global $communicationsManager;

	$nom    = isset($_REQUEST['nom'])    ? trim($_REQUEST['nom'])    : '';
	$parent = isset($_REQUEST['parent']) ? trim($_REQUEST['parent']) : '';
	if ($nom == '') { exit; }
	if ($parent == '/') { $parent = ''; }

	$nom    = str_replace('#et#', "&", $nom);
	$parent = str_replace('#et#', "&", $parent);

	$racine = __CBO_UPLOADS_PATH__.'com/'.$parent;

	mkdir($racine.$nom);

    exit;

} // FIN mode