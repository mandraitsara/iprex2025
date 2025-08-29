<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax PROFILS
------------------------------------------------------*/

// Initialisation du mode d'appel
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$documentsTypeManager 	= new DocumentsTypeManager($cnx);
$documentManager 		= new DocumentManager($cnx);
$logsManager 			= new LogManager($cnx);
$lotsManager 			= new LotManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------
MODE - Switch l'activation d'un type
------------------------------------*/
function modeActivationType() {

    global
        $documentsTypeManager;

	$valeur 	= isset($_REQUEST['valeur']) 	? intval($_REQUEST['valeur']) 		: -1;
	$id_type 	= isset($_REQUEST['id_type']) 	? intval($_REQUEST['id_type']) 		: 0;

	if ($id_type == 0 || $valeur < 0) { exit; }

	$documentsType = $documentsTypeManager->getDocumentsType($id_type);
	if (!$documentsType instanceof DocumentsType) { exit; }

	// On vérifie que le type n'est pas vérouillé ou utilisé par l'API
	if ($documentsType->isLocked() || (int)$documentsType->getApi() == 1) { exit; }

	$documentsType->setActif($valeur);
	echo $documentsTypeManager->saveDocumentsType($documentsType) ? 1 : 0;
	exit;

} // FIN mode


/* --------------------------------------
MODE - Modale documents du lot
---------------------------------------*/
function modeModalLotDocs() {

    global
	    $lotsManager,
		$documentManager,
		$documentsTypeManager,
		$cnx;

	$id_lot = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id_lot == 0) { echo '-1'; exit; }

	$type_lot = isset($_REQUEST['type_lot']) ? intval($_REQUEST['type_lot']) : 0;

	// Type 0 : lot de production
    if ($type_lot == 0) {
		$lot = $lotsManager->getLot($id_lot);
		if (!$lot instanceof Lot) { echo '-1'; exit; }
    }
    // Type 1 : lot de négoce
	else if ($type_lot == 1) {
	    $lotsNegoceManager = new LotNegoceManager($cnx);
		$lot = $lotsNegoceManager->getLotNegoce($id_lot);
		if (!$lot instanceof LotNegoce) { echo '-1'; exit; }

	} else { echo '-1'; exit(); }

    $txtType = $type_lot == 1 ? ' de négoce' : '';
	echo '<i class="fa fa-box"></i><span class="gris-7 ">Lot'.$txtType. ' ' . $lot->getNom_produit().'</span> ';

	echo '^'; // Séparateur Title / Body

	$params = ['lot_id' => $lot->getId(), 'supprime' => 0, 'type_lot' => $type_lot];

	$liste_docs = $documentManager->getListeDocuments($params);

	$showonly = isset($_REQUEST['showonly']);

	?>
    <div class="row retour-upload-ok d-none mb-2">
        <div class="col-12">
            <div class="alert alert-success  mb-0 alert-dismissible fade show" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-check fa-lg mr-1"></i>
                <span class="text-16">Document ajouté.</span>
            </div>
        </div>
    </div>

    <div id="listeDocumentsLotModale"><?php majListeDocumentsLotModale($lot->getId(), $showonly, $type_lot); ?></div>
    <?php if (!$showonly) { ?>
        <form action="#" method="post" enctype="multipart/form-data"
              class="alert alert-secondary margin-top-20 mb-0 form-upload-doc">
            <div class="row">
                <div class="col-12 gris-7 padding-left-20 mb-2 bb-c">
                    <i class="fa fa-plus-square mr-1 gris-7"></i>Ajouter un document&hellip;
                </div>
            </div>
            <div class="row mb-2">
                <div class="form-group col-12 mb-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="inputGroupFileAddon01"><i
                                        class="fa fa-file mr-2 gris-9"></i> Fichier</span>
                        </div>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="inputGroupFile01"
                                   aria-describedby="inputGroupFileAddon01">
                            <label class="custom-file-label nom-fichier-a-uploader" for="inputGroupFile01">Cliquez ici
                                pour sélectionnez un fichier...</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-6 mb-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-tag gris-9"></i></span>
                        </div>
                        <input type="text" placeholder="Description" class="form-control nom-document" value="">
                    </div>
                </div>

                <div class="form-group col-4 mb-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Type</span>
                        </div>
                        <select class="selectpicker show-tick form-control type-document-id" id="user_select_profil"
                                data-title="Sélectionnez...">
							<?php
							foreach ($documentsTypeManager->getListeDocumentsTypes(false) as $documents_type) { ?>
                                <option value="<?php echo $documents_type->getId(); ?>"><?php echo $documents_type->getNom(); ?></option>
							<?php } ?>
                        </select>
                    </div>
                </div>


                <div class="form-group col-2 mb-0 text-right">
                    <button type="button" class="btn btn-info btnUpload" data-lot-id="<?php echo $lot->getId(); ?>"><i
                                class="fa fa-check mr-1"></i>Ajouter
                    </button>
                </div>
            </div>

            <div class="row retour-upload-erreur d-none mt-2">
                <div class="col-12">
                    <div class="alert alert-danger  mb-0">
                        <i class="fa fa-exclamation-triangle fa-lg mr-1"></i>
                        <strong>Intégration du fichier impossible !</strong>
                        <p class="mb-0 text-14">Le fichier ne respecte pas les conditions requises, vérifier les formats
                            de fichiers et la taille maximale autorisés :<br>
							<?php
							$configManager = new ConfigManager($cnx);
							$extdocs = $configManager->getConfig('extdocs');
							$listeTypes = explode(',', $extdocs->getValeur());
							foreach ($listeTypes as $typedoc) { ?>
                                <span class="badge badge-secondary badge-pill text-12">.<?php echo trim(strtolower($typedoc)); ?></span>
							<?php } ?>
                            <span class="badge badge-warning badge-pill text-12">< <?php echo ini_get('upload_max_filesize'); ?>o</span>
                        </p>
                    </div>
                </div>
            </div>

        </form>

		<?php
	} // FIN test show only
	exit;

} // FIN mode

/* ----------------------------------------
MODE - Maj liste documents du lot (modale)
-----------------------------------------*/
function modeMajListeDocumentsLot() {

    $lot_id = isset($_REQUEST['lot_id']) ? intval($_REQUEST['lot_id']) : 0;
    if ($lot_id == 0) { exit; }

	majListeDocumentsLotModale($lot_id);

    exit;

} // FIN mode


/* ------------------------------------
MODE - Upload du fichier en aJax
------------------------------------*/
function modeUploadDoc() {

    global
        $cnx,
		$documentManager,
		$logsManager;

    // Récupération des variables hors fichier
	$lot_id     = isset($_REQUEST['lot_id'])    ? intval($_REQUEST['lot_id'])   : 0;
	$type_id    = isset($_REQUEST['type_id'])   ? intval($_REQUEST['type_id'])  : 0;
	$type_lot    = isset($_REQUEST['type_lot'])   ? intval($_REQUEST['type_lot'])  : 0;
	$nom        = isset($_REQUEST['nom'])       ? trim($_REQUEST['nom'])        : '';

	// SI le lot ne peut être identifié, on retourne une erreur
	if ($lot_id == 0) { echo '-1'; exit; }

	// On vérifie l'intégrité du fichier
    $fichiers = isset($_FILES) ? $_FILES : false;
	if (!$fichiers) { echo '-2'; exit;}
	$fichier = isset($fichiers['file']) && !empty($fichiers['file']) ? $fichiers['file'] : false;
	if (!$fichier) { echo '-2'; exit;}

	// On vérifie les extensions autorisées
	$extension = trim(strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION)));
	$configManager = new ConfigManager($cnx);
	$extdocs = $configManager->getConfig('extdocs');
	$listeTypes = explode(',',$extdocs->getValeur());
	if (!in_array($extension, $listeTypes)) { echo 0; exit; }

	// Si pas de nom, on précise l'extenstion du fichier ~~~~ déprécié 26/08/2019
	//if ($nom == '') { $nom = 'Fichier ' . strtoupper($extension); }

	// Si pas de nom, on reprend le nom du type de document (26/08/2019)
    if ($nom == '') {
        // On récupère l'objet de type de document
        $documentTypeManager = new DocumentsTypeManager($cnx);
        $documentType = $documentTypeManager->getDocumentsType($type_id);
        if ($documentType instanceof DocumentsType) {

            $nom = $documentType->getNom();

        // Si le type de document n'existe pas on reprend l'exention (ça ne devrais pas arriver normalement)
        } else { $nom = 'Fichier ' . strtoupper($extension); }

    } // FIN pas de titre

	// On crée le dossier pour les documents du lot s'il n'existe pas encore
    $chemin = __CBO_UPLOADS_PATH__.$lot_id;
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

    // On enregistre en BDD
	$doc = new Document([]);
	$doc->setNom($nom);
	$doc->setSupprime(0);
	$doc->setDate(date('Y-m-d H:i:s'));
	$doc->setFilename($nomFichier);
	$doc->setLot_id($lot_id);
	$doc->setType_lot($type_lot);
	$doc->setType_id($type_id);

	// Gestion de l'erreur
	if (!$documentManager->saveDocument($doc)) {
		echo '-5'; exit;
    } // Fin enrestrement du doc en BDD

    // On log
    $log = new Log([]);
	$log->setLog_type('success');
	$log->setLog_texte('Upload du fichier "'.$nomFichier.'" dans le dossier du lot #'.$lot_id);
    $logsManager->saveLog($log);

    echo '1';
    exit;

} // FIN mode

/* ------------------------------------
FONCTION DEPORTEE
Maj liste documents du lot (modale)
------------------------------------*/
function majListeDocumentsLotModale($lot_id, $showonly = false, $type_lot = 0) {

    global
        $documentManager,
        $utilisateur;

    $type_lot = isset($_REQUEST['type_lot']) ? intval($_REQUEST['type_lot']) : 0;

	$params = ['lot_id' => $lot_id, 'supprime' => 0, 'type_lot' => $type_lot];
	$liste_docs = $documentManager->getListeDocuments($params);

?>

        <table class="admin w-100">
            <thead>
                <tr>
					<?php
					// On affiche l'ID que si on est développeur
					if ($utilisateur->isDev()) { ?><th class="w-minimax-admin-cell">ID</th><?php } ?>
                    <th>Titre</th>
                    <th>Type</th>
                    <th>Date</th>
                    <?php if (!$showonly) {?>
                    <th class="t-actions w-mini-admin-cell">Supprimer</th>
                    <?php } ?>
                    <th class="t-actions w-mini-admin-cell">Ouvrir</th>
                </tr>
            </thead>
            <tbody>

            <?php if ($type_lot == 0) { ?>
                <!-- Synthèse PDF auto-générée (lots de production)-->
                <tr>
					<?php
					if ($utilisateur->isDev()) { ?>
                        <td class="w-minimax-admin-cell"><span class="badge badge-pill badge-warning">-</span></td>
					<?php } ?>
                    <td>Synthèse du lot</td>
                    <td>Auto-généré</td>
                    <td>&mdash;</td>
					<?php if (!$showonly) { ?>
                        <td class="t-actions w-mini-admin-cell">
                            <button type="button" class="btn btn-sm btn-danger" disabled><i
                                        class="fa fa-fw fa-times"></i></button>
                        </td>
					<?php } ?>
                    <td class="t-actions w-mini-admin-cell"><a href="" target="_blank" download id="lienPdfLotDoc"></a>
                        <button type="button" class="btn btn-info btn-sm btn-doc-genere-pdf"
                                data-id-lot="<?php echo $lot_id; ?>"><i class="fa fa-fw fa-external-link-alt"></i>
                        </button>
                    </td>
                </tr>
                <!-- FIN synthèse PDF auto-générée -->

				<?php
			} else if (empty($liste_docs)) { ?>
                <tr>
                    <td colspan="<?php
                    $colspan = 4;
					$colspan = $utilisateur->isDev() ? $colspan+1 : $colspan;
					$colspan = !$showonly ? $colspan+1 : $colspan;
                    echo $colspan; ?>" class="padding-20 text-center gris-9">
                        Aucun document disponible pour ce lot...
                    </td>
                </tr>
            <?php }
			foreach ($liste_docs as $doc) {

			    // Si le fichier n'existe pas sur le serveur, on le passe en statut supprimé et on ne l'affiche pas...
			    if (!file_exists(__CBO_UPLOADS_PATH__.$lot_id.'/'.$doc->getFilename())) {
					$doc->setSupprime(1);
					$documentManager->saveDocument($doc);
			        continue;
			    } // FIN test erreur fichier sur le serveur
			    ?>

                <tr>
					<?php
					// On affiche l'ID que si on est développeur
					if ($utilisateur->isDev()) { ?>
                        <td class="w-minimax-admin-cell"><span class="badge badge-pill badge-warning"><?php echo $doc->getId();?></span></td>
					<?php } ?>
                    <td><?php echo $doc->getNom(); ?></td>
                    <td><?php echo $doc->getType_nom(); ?></td>
                    <td><?php echo Outils::getDate_verbose($doc->getDate(), false);?></td>
				    <?php if (!$showonly) {?>
                    <td class="t-actions w-mini-admin-cell">
                        <button type="button" class="btn btn-sm btn-danger btnSupprDoc" data-doc-id="<?php echo $doc->getId(); ?>"><i class="fa fa-fw fa-times"></i></button>
                    </td>
                    <?php } ?>
                    <td class="t-actions w-mini-admin-cell">
                        <a href="<?php echo __CBO_UPLOADS_URL__.$lot_id.'/'.$doc->getFilename();?>" class="btn btn-info btn-sm" target="_blank"><i class="fa fa-fw fa-external-link-alt"></i></a>
                    </td>
                </tr>

            <?php
			} // FIn boucle
            ?>
            </tbody>
        </table>

	<?php


    return true;

} // FIN fonction déportée

/* ------------------------------------
MODE - Supprime un document
------------------------------------*/
function modeSupprDoc() {

    global
        $documentManager,
        $logsManager;

    // On ne supprime pas réeelement le document ici, on passe le statut "supprime" à 1 en BDD
    // Une action depuis l'interface DEV et une tâche CRON permet de nettoyer réellement les fichiers et la base

    $type_lot = isset($_REQUEST['type_lot']) ? intval($_REQUEST['type_lot']) : 0;

    $doc_id = isset($_REQUEST['doc_id']) ? intval($_REQUEST['doc_id']) : 0;
    if ($doc_id == 0) { exit; }

    $doc = $documentManager->getDocument($doc_id);
    if (!$doc instanceof Document) { exit; }

    $doc->setSupprime(1);

    // Si enregistrement ok, on log
    if ($documentManager->saveDocument($doc)) {

		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression (flag) du document "'.$doc->getFilename().'" pour le lot #'.$doc->getLot_id());
		$logsManager->saveLog($log);

    } // FIN test save bdd ok

    // On rafraichis la liste des docs du lot
	majListeDocumentsLotModale($doc->getLot_id(), false, $type_lot);
    exit;

} // FIN mode

/* ------------------------------------
MODE - Restaure un document
------------------------------------*/
function modeRestaureDoc() {

    global
        $documentManager,
        $logsManager;

	$doc_id = isset($_REQUEST['doc_id']) ? intval($_REQUEST['doc_id']) : 0;
	if ($doc_id == 0) { exit; }

	$doc = $documentManager->getDocument($doc_id);
	if (!$doc instanceof Document) { exit; }

	$doc->setSupprime(0);

	// Si enregistrement ok, on log
	if ($documentManager->saveDocument($doc)) {

		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte('Restauration du document "'.$doc->getFilename().'" pour le lot #'.$doc->getLot_id());
		$logsManager->saveLog($log);

	} // FIN test save bdd ok

	// On rafraichis la liste des docs du lot
	exit;

} // FIN mode

/* ------------------------------------
MODE - Purge définitivement un document
------------------------------------*/
function modePurgeDoc() {

    global
	    $documentManager,
		$logsManager;

	$doc_id = isset($_REQUEST['doc_id']) ? intval($_REQUEST['doc_id']) : 0;
	if ($doc_id == 0) { exit; }

	$doc = $documentManager->getDocument($doc_id);
	if (!$doc instanceof Document) { exit; }

	// On supprime le fichier sur le serveur
	$chemin = __CBO_UPLOADS_PATH__.$doc->getLot_id().'/'.$doc->getFilename();

	// Si le fichier existe pas (O_o) on ne va pas plus loin...
	if (!file_exists($chemin)) { exit; }

	// Si erreur lors de la suppression, on exit...
	if (!unlink($chemin)) { exit; }

    // Suppression définitive en BDD du document
	if ($documentManager->deleteDocument($doc)) {

	    // Si ok on log
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Purge du fichier et du document "'.$doc->getFilename().'" pour le lot #'.$doc->getLot_id());
		$logsManager->saveLog($log);

    } // FIN test suppression en BDD

	exit;

} // FIN mode